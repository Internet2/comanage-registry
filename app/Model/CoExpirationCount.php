<?php
/**
 * COmanage Registry CO Expiration Count Model
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
 * @package       registry-plugin
 * @since         COmanage Registry v2.0.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

class CoExpirationCount extends AppModel {
  // Define class name for cake
  public $name = "CoExpirationCount";
  
  // Add behaviors
  public $actsAs = array('Containable',
                         'Changelog' => array('priority' => 5));
  
  // Association rules from this model to other models
  public $belongsTo = array(
    "CoExpirationPolicy",
    "CoPersonRole"
  );
  
  // Default display field for cake generated views
  public $displayField = "co_expiration_policy_id";
  
  // Validation rules for table elements
  public $validate = array(
    'co_expiration_policy_id' => array(
      'rule' => 'numeric',
      'required' => true,
      'allowEmpty' => false
    ),
    'co_person_role_id' => array(
      'rule' => 'numeric',
      'required' => true,
      'allowEmpty' => false
    ),
    'expiration_count' => array(
      'rule' => 'numeric',
      'required' => true,
      'allowEmpty' => false
    )
  );
  
  /**
   * Obtain the current expiration notification count for the specified role and policy.
   *
   * @since  COmanage Registry v2.0.0
   * @param  Integer $coExpirationPolicyId CO Expiration Policy ID
   * @param  Integer $coPersonRoleId       CO Person Role ID
   * @return Integer Current count
   */
  
  public function count($coExpirationPolicyId, $coPersonRoleId) {
    $ret = 0;
    
    // We don't currently try to validate either foreign key.
    $args = array();
    $args['conditions']['CoExpirationCount.co_expiration_policy_id'] = $coExpirationPolicyId;
    $args['conditions']['CoExpirationCount.co_person_role_id'] = $coPersonRoleId;
    $args['contain'] = false;
    
    $ret = $this->field('expiration_count', $args['conditions']);
   
    return (integer)$ret;
  }
  
  /**
   * Increment the current expiration notification count for the specified role and policy.
   *
   * @since  COmanage Registry v2.0.0
   * @param  Integer $coExpirationPolicyId CO Expiration Policy ID
   * @param  Integer $coPersonRoleId       CO Person Role ID
   * @return Integer New count
   */
  
  public function increment($coExpirationPolicyId, $coPersonRoleId) {
    $ret = 0;
    
    // Is there already a count?
    
    $dbc = $this->getDataSource();
    $dbc->begin();
    
    // We don't currently try to validate either foreign key.
    $args = array();
    $args['conditions']['CoExpirationCount.co_expiration_policy_id'] = $coExpirationPolicyId;
    $args['conditions']['CoExpirationCount.co_person_role_id'] = $coPersonRoleId;
    $args['contain'] = false;
    
    $count = $this->findForUpdate($args['conditions'], array('id', 'expiration_count'));
    
    $this->clear();
    
    if(!empty($count)) {
      $ret = $count[0]['CoExpirationCount']['expiration_count'] + 1;
      
      $this->id = $count[0]['CoExpirationCount']['id'];
      $this->saveField('expiration_count', $ret);
    } else {
      $ret = 1;
      
      $count = array(
        'co_expiration_policy_id' => $coExpirationPolicyId,
        'co_person_role_id'       => $coPersonRoleId,
        'expiration_count'        => $ret
      );
      
      $this->save($count);
    }
    
    $dbc->commit();
    
    return $ret;
  }
  
  /**
   * Reset the current expiration notification count for the specified role,
   * based on the indication of changed attributes associated with the role.
   * Only counts associated with the changed attributes will be reset.
   *
   * @since  COmanage Registry v2.0.0
   * @param  Integer $coId           CO ID
   * @param  Integer $coPersonRoleId CO Person Role ID
   */
  
  public function reset($coId,
                        $coPersonRoleId,
                        $affilChanged=false,
                        $couChanged=false,
                        $sponsorChanged=false,
                        $statusChanged=false,
                        $validThroughChanged=false) {
    if(!$couChanged && !$affilChanged && !$validThroughChanged && !$statusChanged && !$sponsorChanged) {
      // Nothing to do, just return
      return;
    }
    
    // First find the relevant expiration policies
    $args = array();
    $args['conditions']['CoExpirationPolicy.co_id'] = $coId;
    $args['conditions']['CoExpirationPolicy.status'] = SuspendableStatusEnum::Active;
    if($affilChanged) {
      $args['conditions']['OR'][] = 'CoExpirationPolicy.cond_affiliation IS NOT NULL';
    }
    if($couChanged) {
      $args['conditions']['OR'][] = 'CoExpirationPolicy.cond_cou_id IS NOT NULL';
    }
    if($sponsorChanged) {
      $args['conditions']['OR']['CoExpirationPolicy.cond_sponsor_invalid'] = true;
    }
    if($statusChanged) {
      $args['conditions']['OR'][] = 'CoExpirationPolicy.cond_status IS NOT NULL';
    }
    if($validThroughChanged) {
      $args['conditions']['OR'][] = 'CoExpirationPolicy.cond_after_expiry IS NOT NULL';
      $args['conditions']['OR'][] = 'CoExpirationPolicy.cond_before_expiry IS NOT NULL';
    }
    $args['fields'] = array('CoExpirationPolicy.id', 'CoExpirationPolicy.description');
    $args['contain'] = false;
    
    $policies = $this->CoExpirationPolicy->find('list', $args);
    
    if(!empty($policies)) {
      // Now perform a delete
      $args = array();
      $args['conditions']['CoExpirationCount.co_person_role_id'] = $coPersonRoleId;
      $args['conditions']['CoExpirationCount.co_expiration_policy_id'] = array_keys($policies);
      $args['fields'] = array('CoExpirationCount.id', 'CoExpirationCount.expiration_count');
      $args['contain'] = false;
      
      $counts = $this->find('list', $args);
      
      foreach($counts as $cid => $cnt) {
        $this->delete($cid);
      }
    }
    // else nothing to do
    
    return;
  }
}
