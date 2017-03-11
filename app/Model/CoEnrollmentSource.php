<?php
/**
 * COmanage Registry CO Enrollment Source Model
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
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v2.0.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

class CoEnrollmentSource extends AppModel {
  // Define class name for cake
  public $name = "CoEnrollmentSource";
  
  // Current schema version for API
  public $version = "1.0";
  
  // Add behaviors
  public $actsAs = array('Containable', 'Changelog' => array('priority' => 5));
  
  // Association rules from this model to other models
  public $belongsTo = array(
    "CoEnrollmentFlow",
    "OrgIdentitySource"
  );
  
  // Default display field for cake generated views
  public $displayField = "CoEnrollmentSource.id";
  
  // Default ordering for find operations
  public $order = array("CoEnrollmentSource.id");
  
  // Validation rules for table elements
  public $validate = array(
    'co_enrollment_flow_id' => array(
      'rule' => 'numeric',
      'required' => true,
      'allowEmpty' => false
    ),
    'org_identity_source_id' => array(
      'rule' => 'numeric',
      'required' => true,
      'allowEmpty' => false
    ),
    'org_identity_mode' => array(
      'rule' => array('inList',
                      array(EnrollmentOrgIdentityModeEnum::OISAuthenticate,
// Claim mode currently not supported (CO-1280)
//                            EnrollmentOrgIdentityModeEnum::OISClaim,
                            EnrollmentOrgIdentityModeEnum::OISSearch,
                            EnrollmentOrgIdentityModeEnum::OISSearchRequired,
                            EnrollmentOrgIdentityModeEnum::OISSelect,
                            EnrollmentOrgIdentityModeEnum::None)),
      'required' => true,
      'allowEmpty' => false
    ),
    'ordr' => array(
      'rule' => 'numeric',
      'required' => false,
      'allowEmpty' => true
    )
  );
  
  /**
   * Obtain a list of Enrollment Sources, where the underlying Org Identity Sources
   * are active.
   *
   * @since  COmanage Registry v2.0.0
   * @param  Integer $efId Enrollment Flow ID
   * @param  EnrollmentOrgIdentityModeEnum $mode EnrollmentOrgIdentityMode
   * @return Array List of Enrollment Sources
   */
  
  public function activeSources($efId, $mode) {
    $args = array();
    $args['conditions']['CoEnrollmentSource.co_enrollment_flow_id'] = $efId;
    $args['conditions']['CoEnrollmentSource.org_identity_mode'] = $mode;
    // What we really want to do is something like this
    //    $args['joins'][0]['table'] = 'org_identity_sources';
    //    $args['joins'][0]['alias'] = 'OrgIdentitySource';
    //    $args['joins'][0]['type'] = 'INNER';
    //    $args['joins'][0]['conditions'][0] = 'OrgIdentitySource.id=CoEnrollmentSource.org_identity_source_id';
    // and something like this
    //    $args['contain'][] = 'OrgIdentitySource.status = "' . SuspendableStatusEnum::Active . '"';
    // but we can't have both a join and a contain (duplicate tables in the SQL query).
    // If we just use the contain, we'll still get matching rows back, it's just that the
    // associated OrgIdentitySource will be empty. So we have to walk the results and clean them up.
    $args['contain'][] = 'OrgIdentitySource';
    $args['order'] = 'CoEnrollmentSource.ordr ASC';
    
    $ret = $this->find('all', $args);
    
    for($i = count($ret)-1;$i >= 0;$i--) {
      if(!isset($ret[$i]['OrgIdentitySource']['status'])
         || $ret[$i]['OrgIdentitySource']['status'] != SuspendableStatusEnum::Active) {
        // Not an active source, pull it from the result set
        
        unset($ret[$i]);
      }
    }
    
    return $ret;
  }
}