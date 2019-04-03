<?php
/**
 * COmanage Registry CO Enrollment Authenticator Model
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
 * @since         COmanage Registry v3.3.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

class CoEnrollmentAuthenticator extends AppModel {
  // Define class name for cake
  public $name = "CoEnrollmentAuthenticator";
  
  // Current schema version for API
  public $version = "1.0";
  
  // Add behaviors
  public $actsAs = array('Containable', 'Changelog' => array('priority' => 5));
  
  // Association rules from this model to other models
  public $belongsTo = array(
    "Authenticator",
    "CoEnrollmentFlow"
  );
  
  // Default display field for cake generated views
  public $displayField = "CoEnrollmentAuthenticator.id";
  
  // Default ordering for find operations
  public $order = array("CoEnrollmentAuthenticator.id");
  
  // Validation rules for table elements
  public $validate = array(
    'co_enrollment_flow_id' => array(
      'rule' => 'numeric',
      'required' => true,
      'allowEmpty' => false
    ),
    'authenticator_id' => array(
      'rule' => 'numeric',
      'required' => true,
      'allowEmpty' => false
    ),
    'required' => array(
      // XXX shouldn't we use in_array?
      'rule' => array('range', -2, 2)
    )
  );
  
  /**
   * Obtain a list of Enrollment Authenticators, where the underlying Authenticators
   * are active.
   *
   * @since  COmanage Registry v3.3.0
   * @param  Integer $efId Enrollment Flow ID
   * @return Array List of Enrollment Authenticators
   */
  
  public function active($efId) {
    $args = array();
    $args['conditions']['CoEnrollmentAuthenticator.co_enrollment_flow_id'] = $efId;
    // What we really want to do is something like this
    //    $args['joins'][0]['table'] = 'authenticators';
    //    $args['joins'][0]['alias'] = 'Authenticator';
    //    $args['joins'][0]['type'] = 'INNER';
    //    $args['joins'][0]['conditions'][0] = 'Authenticator.id=CoEnrollmentAuthenticator.authenticator_id';
    // and something like this
    //    $args['contain'][] = 'Authenticator.status = "' . SuspendableStatusEnum::Active . '"';
    // but we can't have both a join and a contain (duplicate tables in the SQL query).
    // If we just use the contain, we'll still get matching rows back, it's just that the
    // associated OrgIdentitySource will be empty. So we have to walk the results and clean them up.
    // This is similar to CoEnrollmentSource::activeSources.
    $args['contain'][] = 'Authenticator';
// XXX do we need to support ordr?    
//    $args['order'] = 'CoEnrollmentAuthenticator.ordr ASC';
    
    $ret = $this->find('all', $args);
    
    for($i = count($ret)-1;$i >= 0;$i--) {
      if(!isset($ret[$i]['Authenticator']['status'])
         || $ret[$i]['Authenticator']['status'] != SuspendableStatusEnum::Active) {
        // Not an active authenticator, pull it from the result set
        
        unset($ret[$i]);
      }
    }
    
    return $ret;
  }
}