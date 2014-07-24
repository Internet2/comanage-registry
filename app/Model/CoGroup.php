<?php
/**
 * COmanage Registry CO Group Model
 *
 * Copyright (C) 2011-14 University Corporation for Advanced Internet Development, Inc.
 * 
 * Licensed under the Apache License, Version 2.0 (the "License"); you may not use this file except in compliance with
 * the License. You may obtain a copy of the License at
 * 
 * http://www.apache.org/licenses/LICENSE-2.0
 * 
 * Unless required by applicable law or agreed to in writing, software distributed under
 * the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY
 * KIND, either express or implied. See the License for the specific language governing
 * permissions and limitations under the License.
 *
 * @copyright     Copyright (C) 2011-14 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.1
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */
  
class CoGroup extends AppModel {
  // Define class name for cake
  public $name = "CoGroup";
  
  // Current schema version for API
  public $version = "1.0";
  
  // Association rules from this model to other models
  public $hasMany = array(
    // A CoGroup has zero or more members
    "CoGroupMember" => array('dependent' => true),
    "CoEnrollmentFlowApproverCoGroup" => array(
      'className' => 'CoEnrollmentFlow',
      'foreignKey' => 'approver_co_group_id'
    ),
    "CoEnrollmentFlowAuthzCoGroup" => array(
      'className' => 'CoEnrollmentFlow',
      'foreignKey' => 'authz_co_group_id'
    ),
    "CoEnrollmentFlowNotificationGroup" => array(
      'className' => 'CoEnrollmentFlow',
      'foreignKey' => 'notification_co_group_id'
    ),
    "CoNotificationRecipientGroup" => array(
      'className' => 'CoNotification',
      'foreignKey' => 'recipient_co_group_id'
    ),
    "CoNotificationSubjectGroup" => array(
      'className' => 'CoNotification',
      'foreignKey' => 'subject_co_group_id'
    ),
    "CoProvisioningExport" => array('dependent' => true)
  );

  public $belongsTo = array("Co");           // A CoGroup is attached to one CO
   
  // Default display field for cake generated views
  public $displayField = "name";
  
  // Default ordering for find operations
// XXX CO-296 Toss default order?
//  public $order = array("CoGroup.name");
  
  public $actsAs = array('Containable', 'Provisioner');

  // Validation rules for table elements
  public $validate = array(
    'co_id' => array(
      'rule' => 'numeric',
      'required' => true,
      'message' => 'A CO ID must be provided'
    ),
    'name' => array(
      'rule' => 'notEmpty',
      'required' => true,
      'message' => 'A name must be provided'
    ),
    'description' => array(
      'rule' => '/.*/',
      'required' => false,
      'allowEmpty' => true
    ),
    'open' => array(
      'rule' => array('boolean')
    ),
    'status' => array(
      'rule' => array('inList', array(SuspendableStatusEnum::Active,
                                      SuspendableStatusEnum::Suspended)),
      'required' => true,
      'message' => 'A valid status must be selected'
    )
  );
  
  // Enum type hints
  
  public $cm_enum_types = array(
    'status' => 'status_t'
  );

  /**
   * Obtain all groups for a CO person.
   *
   * @since  COmanage Registry v0.6
   * @param  Integer CO Person ID
   * @param  Integer Maximium number of results to retrieve (or null)
   * @param  Integer Offset to start retrieving results from (or null)
   * @param  String Field to sort by (or null)
   * @return Array Group information, as returned by find
   * @todo   XXX Rewrite to a custom find type
   */
  
  function findForCoPerson($coPersonId, $limit=null, $offset=null, $order=null) {
    $args = array();
    $args['joins'][0]['table'] = 'co_group_members';
    $args['joins'][0]['alias'] = 'CoGroupMember';
    $args['joins'][0]['type'] = 'INNER';
    $args['joins'][0]['conditions'][0] = 'CoGroup.id=CoGroupMember.co_group_id';
    $args['conditions']['CoGroup.status'] = StatusEnum::Active;
    $args['conditions']['CoGroupMember.co_person_id'] = $coPersonId;
    $args['conditions']['OR']['CoGroupMember.member'] = 1;
    $args['conditions']['OR']['CoGroupMember.owner'] = 1;
    $args['contain'] = false;
    
    if($limit) {
      $args['limit'] = $limit;
    }
    
    if($offset) {
      $args['offset'] = $offset;
    }
    
    if($order) {
      $args['order'] = $order;
    }
    
    return $this->find('all', $args);
  }
  
  /**
   * Determine the current status of the provisioning targets for this CO Group.
   *
   * @since  COmanage Registry v0.8.2
   * @param  Integer CO Group ID
   * @return Array Current status of provisioning targets
   * @throws RuntimeException
   */
  
  public function provisioningStatus($coGroupId) {
    // First, obtain the list of active provisioning targets for this group's CO.
    
    $args = array();
    $args['joins'][0]['table'] = 'co_groups';
    $args['joins'][0]['alias'] = 'CoGroup';
    $args['joins'][0]['type'] = 'INNER';
    $args['joins'][0]['conditions'][0] = 'CoGroup.co_id=CoProvisioningTarget.co_id';
    $args['conditions']['CoGroup.id'] = $coGroupId;
    $args['conditions']['CoProvisioningTarget.status !='] = ProvisionerStatusEnum::Disabled;
    $args['contain'] = false;
    
    $targets = $this->Co->CoProvisioningTarget->find('all', $args);
    
    if(!empty($targets)) {
      // Next, for each target ask the relevant plugin for the status for this group.
      
      // We may end up querying the same Plugin more than once, so maintain a cache.
      $plugins = array();
      
      for($i = 0;$i < count($targets);$i++) {
        $pluginModelName = $targets[$i]['CoProvisioningTarget']['plugin']
                         . ".Co" . $targets[$i]['CoProvisioningTarget']['plugin'] . "Target";
        
        if(!isset($plugins[ $pluginModelName ])) {
          $plugins[ $pluginModelName ] = ClassRegistry::init($pluginModelName, true);
          
          if(!$plugins[ $pluginModelName ]) {
            throw new RuntimeException(_txt('er.plugin.fail', array($pluginModelName)));
          }
        }
        
        $targets[$i]['status'] = $plugins[ $pluginModelName ]->status($targets[$i]['CoProvisioningTarget']['id'],
                                                                      null,
                                                                      $coGroupId);
      }
    }
    
    return $targets;
  }
}
