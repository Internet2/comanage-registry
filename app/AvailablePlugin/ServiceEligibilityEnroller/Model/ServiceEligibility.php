<?php
/**
 * COmanage Registry Service Eligibility Model
 *
 * Portions licensed to the University Corporation for Advanced Internet
 * Development, Inc. ("UCAID") under one or more contributor license agreements.
 * See the NOTICE file distributed with this work for additional information
 * regarding copyright ownership.
 *
 * UCAID licenses this file to you under the Apache License, Version 2.0
 * (the "License"); you may not use this file except in compliance with the
 * License. You may obtain a copy of the License at:
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 * 
 * @link          https://www.internet2.edu/comanage COmanage Project
 * @package       registry-plugin
 * @since         COmanage Registry v4.1.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

class ServiceEligibility extends AppModel {
  // Add behaviors
  public $actsAs = array('Containable', 'Changelog' => array('priority' => 5));
  
  // Association rules from this model to other models
  public $belongsTo = array(
    'ServiceEligibilityEnroller',
    'CoPersonRole',
    'CoService'
  );
  
  // Default display field for cake generated views
  public $displayField = "id";
  
  // Validation rules for table elements
  public $validate = array(
    'co_service_id' => array(
      'rule' => 'numeric',
      'required' => true,
      'allowEmpty' => false
    ),
    'co_person_role_id' => array(
      'rule' => 'numeric',
      'required' => true,
      'allowEmpty' => false
    )
  );
  
  /**
   * Add a Service Eligibility.
   *
   * @since  COmanage Registry v4.1.0
   * @param  int     $coPersonRoleId   CO Person Role ID
   * @param  int     $coServiceId      CO Service ID
   * @param  int     $actorCoPersonId  Actor CO Person ID
   * @param  int     $coPetitionId     CO Petition ID, if in an Enrollment Flow
   * @param  boolean $provision        Whether to fire provisioning on CO Group Member save
   * @throws InvalidArgumentException
   * @throws RuntimeException
   */
  
  public function add($coPersonRoleId, $coServiceId, $actorCoPersonId, $coPetitionId=null, $provision=true) {
    // We need a CO ID, which we'll pull from $coPersonRoleId since findCoForRecord
    // will use that for the model.
    
    $coId = $this->CoPersonRole->findCoForRecord($coPersonRoleId);
    
    if(!$coId) {
      throw new InvalidArgumentException(_txt('er.co.specify'));
    }
    
    $coPersonId = $this->CoPersonRole->field('co_person_id', array('CoPersonRole.id' => $coPersonRoleId));
    
    // Pull the Service Description
    
    $serviceDesc = $this->CoService->field('description', array('CoService.id' => $coServiceId));
    
    // Pull the settings for this CO
    
    $Settings = ClassRegistry::init('ServiceEligibilityEnroller.ServiceEligibilitySetting');
    
    $allowMultiple = $Settings->field('allow_multiple', array('ServiceEligibilitySetting.co_id' => $coId));
    
    $this->_begin();
    
    // First check that the requested pair does not already exist
    
    $args = array();
    $args['conditions']['ServiceEligibility.co_person_role_id'] = $coPersonRoleId;
    $args['conditions']['ServiceEligibility.co_service_id'] = $coServiceId;
    $args['fields'] = array('id');
    
    $current = $this->findForUpdate($args['conditions'], $args['fields']);
    
    if(!empty($current)) {
      $this->_rollback();
      throw new RuntimeException(_txt('er.serviceeligibilityenroller.exists'));
    }
    
    // If we don't allow multiple, remove any existing entry
    
    if(!$allowMultiple) {
      $this->remove($coPersonRoleId, null, $actorCoPersonId);
    }
    
    // Create the new eligibility
    
    $data = array(
      'co_person_role_id' => $coPersonRoleId,
      'co_service_id'     => $coServiceId
    );
    
    $this->clear();
    $this->save($data, array('provision' => $provision));
    
    // Insert a History Record
    
    $this->CoPersonRole->HistoryRecord->record(
      $coPersonId,
      $coPersonRoleId,
      null,
      $actorCoPersonId,
      // It's not really worth enum'ing this to ActionEnum::ServiceEligibilityAdded
      // since nothing else uses it and this comment will make it grep'able
      'pASE',
      _txt('pl.serviceeligibilityenroller.history.add', array($serviceDesc))
    );
    
    if($coPetitionId) {
      // Insert a Petition History Record
      
      $this->CoPersonRole->CoPetition->CoPetitionHistoryRecord->record(
        $coPetitionId,
        $actorCoPersonId,
        // It's not really worth enum'ing this to PetitionActionEnum::ServiceEligibilityAdded
        // since nothing else uses it and this comment will make it grep'able
        'pSE',
        _txt('pl.serviceeligibilityenroller.history.add', array($serviceDesc))
      );
    }
    
    // Add the group membership
    try {
      $this->CoService->setServiceGroupMembership($coServiceId,
                                                  null,
                                                  $coPersonId,
                                                  $actorCoPersonId,
                                                  "join",
                                                  $provision);
    }
    catch(Exception $e) {
      $this->_rollback();
      throw new RuntimeException($e->getMessage());
    }    
    
    $this->_commit();
  }
  
  /**
   * Determine the available Services that can be used with Service Eligibilities.
   *
   * @since  COmanage Registry v4.1.0
   * @param  int   $coId CO ID
   * @return array       Array of CO Services and associated CO Groups
   */
  
  public function availableServices($coId) {
    $args = array();
    $args['conditions']['CoService.co_id'] = $coId;
    $args['conditions']['CoService.status'] = SuspendableStatusEnum::Active;
    $args['conditions'][] = 'CoService.co_group_id IS NOT NULL';
    $args['contain'] = array('CoGroup');
    
    $services = $this->CoService->find('all', $args);
    
    for($i = 0;$i < count($services);$i++) {
      // Remove any service associated with an automatic or non-standard group
      
      if($services[$i]['CoGroup']['auto'] 
         || $services[$i]['CoGroup']['group_type'] != GroupEnum::Standard) {
        unset($services[$i]);
      }
    }
    
    return $services;
  }
  
  /**
   * Remove a Service Eligibility.
   *
   * This is remove rather than delete because we use a different function
   * signature than AppModel::delete.
   *
   * @since  COmanage Registry v4.1.0
   * @param  int     $coPersonRoleId   CO Person Role ID
   * @param  int     $coServiceId      CO Service ID
   * @param  int     $actorCoPersonId  Actor CO Person ID
   * @param  boolean $provision        Whether to fire provisioning on CO Group Member save
   * @param  boolean $skipGroup        Whether to skip processing CO Group Membership removal
   * @throws InvalidArgumentException
   * @throws RuntimeException
   */
  
  public function remove($coPersonRoleId, $coServiceId, $actorCoPersonId, $provision=true, $skipGroup=false) {
    // We need a CO ID, which we'll pull from $coPersonRoleId since findCoForRecord
    // will use that for the model.
    
    $coId = $this->CoPersonRole->findCoForRecord($coPersonRoleId);
    
    if(!$coId) {
      throw new InvalidArgumentException(_txt('er.co.specify'));
    }
    
    $coPersonId = $this->CoPersonRole->field('co_person_id', array('CoPersonRole.id' => $coPersonRoleId));
    
    // We might already be in a transaction (from add), and if we are we have to
    // be careful about rolling back on error.
    $alreadyInTxn = $this->inTxn;
    
    if(!$alreadyInTxn) {
      $this->_begin();
    }
    
    // Find the requested service. If $coServiceId is specified (ie: by clicking
    // the "REMOVE" button) we should only get one row, but there is an edge
    // case where allow_multiple is disabled, and then add() calls us and we
    // have multiple rows to remove.
    
    $args = array();
    $args['conditions']['ServiceEligibility.co_person_role_id'] = $coPersonRoleId;
    if($coServiceId) {
      $args['conditions']['ServiceEligibility.co_service_id'] = $coServiceId;
    }
    $args['fields'] = array('id', 'co_service_id');
    
    $current = $this->findForUpdate($args['conditions'], $args['fields']);
    
    if(empty($current)) {
      // There's nothing to do, most likely because $coServiceId is null and
      // there are no matching rows. If we were already in a transaction, return
      // WITHOUT rolling back.
      
      if($alreadyInTxn) {
        return;
      }
      
      $this->_rollback();
      
      if($coServiceId) {
        // Pretty much all the cases where we get here aren't real errors
        //throw new RuntimeException(_txt('er.serviceeligibilityenroller.none'));
      }
      
      return;
    }
    
    foreach($current as $se) {
      // Remove the Service Eligibility
      
      $this->delete($se['ServiceEligibility']['id']);
      
      // Pull the Service Description for this service.
      
      $serviceDesc = $this->CoService->field('description', array('CoService.id' => $se['ServiceEligibility']['co_service_id']));
      
      // Insert a History Record
      
      $this->CoPersonRole->HistoryRecord->record(
        $coPersonId,
        $coPersonRoleId,
        null,
        $actorCoPersonId,
        // It's not really worth enum'ing this to ActionEnum::ServiceEligibilityDeleted
        // since nothing else uses it and this comment will make it grep'able
        'pDSE',
        _txt('pl.serviceeligibilityenroller.history.remove', array($serviceDesc))
      );
      
      if(!$skipGroup) {
        // Remove the group membership
        try {
          $this->CoService->setServiceGroupMembership($se['ServiceEligibility']['co_service_id'],
                                                      null,
                                                      $coPersonId,
                                                      $actorCoPersonId,
                                                      "leave",
                                                      $provision);
        }
        catch(Exception $e) {
          $this->_rollback();
          throw new RuntimeException($e->getMessage());
        }
      }
    }
    
    $this->_commit();
  }
  
  /**
   * Remove a Service Eligibility by CO Group ID
   *
   * @since  COmanage Registry v4.1.0
   * @param  int $coPersonId CO Person ID
   * @param  int $coGroupId  CO Group ID
   */
  
  public function removeByGroup($coPersonId, $coGroupId) {
    // First, see if there are any services associated with this group.
    
    $args = array();
    $args['conditions']['CoService.co_group_id'] = $coGroupId;
    $args['conditions']['CoService.status'] = SuspendableStatusEnum::Active;
    $args['fields'] = array('id', 'description');
    $args['contain'] = false;
    
    $services = $this->CoService->find('list', $args);
    
    if(empty($services)) {
      // Nothing to do
      return;
    }
    
    // Next find the roles for this person. For now, we ignore status when
    // removing by group.
    
    $args = array();
    $args['conditions']['CoPersonRole.co_person_id'] = $coPersonId;
    $args['fields'] = array('id', 'status');
    $args['contain'] = false;
    
    $roles = $this->CoPersonRole->find('list', $args);
    
    if(empty($roles)) {
      // Nothing to do
      return;
    }
    
    foreach($services as $coServiceId => $desc) {
      foreach($roles as $coPersonRoleId => $status) {
        // We don't provision or update group memberships because we got here
        // after a group deletion... so there's nothing to remove and nothing
        // to provision.
        $this->remove($coPersonRoleId, $coServiceId, null, false, true);
      }
    }
  }
  
  /**
   * Remove a Service Eligibility by CO Group Member ID
   *
   * @since  COmanage Registry v4.1.0
   * @param  int $coGroupMemberId  CO Group Member ID
   */
  
  public function removeByGroupMemberId($coGroupMemberId) {
    // We're probably being called during afterDelete, which is a bit confusing
    // since the delete has already happened. However, thanks to ChangelogBehavior
    // we can pull the old record and figure out the subjects we care about.
    
    $args = array();
    $args['conditions']['CoGroupMember.id'] = $coGroupMemberId;
    $args['contain'] = false;
    
    $oldMembership = $this->CoPersonRole->CoPerson->CoGroupMember->find('first', $args);
    
    if(!empty($oldMembership)) {
      $this->removeByGroup($oldMembership['CoGroupMember']['co_person_id'],
                           $oldMembership['CoGroupMember']['co_group_id']);
    }
  }
  
  /**
   * Remove a Service Eligibility by CO Person Role ID
   *
   * @since  COmanage Registry v4.1.0
   * @param  int $coGroupMemberId  CO Person Role ID
   */
  
  public function removeByRoleId($coPersonRoleId) {
    $this->remove($coPersonRoleId, null, null);
  }
  
  /**
   * Obtain the set of Service for a CO Person, sorted by CO Person Role.
   *
   * @since  COmanage Registry v4.1.0
   * @param  int   $coPersonId CO Person ID
   * @return array             Array of CO Service IDs, grouped by CO Person Role ID
   */
  public function servicesByRole($coPersonId) {
    // First, pull all roles for the CO Person (currently ignoring status)
    
    $args = array();
    $args['conditions']['CoPersonRole.co_person_id'] = $coPersonId;
    $args['fields'] = array('id', 'status');
    $args['contain'] = false;
    
    $roleIds = $this->CoPersonRole->find('list', $args);
    
    // Now we can pull the eligibilities
    
    $args = array();
    $args['conditions']['ServiceEligibility.co_person_role_id'] = array_keys($roleIds);
    $args['contain'] = false;
    
    $eligibilities = $this->find('all', $args);
    
    // Group by role ID
    
    $ret = array();
    
    foreach($eligibilities as $e) {
      $ret[ $e['ServiceEligibility']['co_person_role_id'] ][] = $e['ServiceEligibility']['co_service_id'];
    }
    
    return $ret;
  }
}
