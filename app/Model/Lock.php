<?php
/**
 * COmanage Registry Lock Model
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

class Lock extends AppModel {
  // Define class name for cake
  public $name = "Lock";
  
  // Current schema version for API
  public $version = "1.0";
  
  // Association rules from this model to other models
  public $belongsTo = array("Co");
  
  public $hasOne = array();
  
  // Default display field for cake generated views
  public $displayField = "label";
  
//  public $actsAs = array('Containable');
  
  // Validation rules for table elements
  public $validate = array(
    'co_id' => array(
      'rule' => 'numeric',
      'required' => false,
      'allowEmpty' => true
    ),
    'label' => array(
      'rule' => array('validateInput'),
      'required' => true,
      'allowEmpty' => false
    ),
    'pid' => array(
      'rule' => array('numeric'),
      'required' => true,
      'allowEmpty' => false
    )
  );
  
  /**
   * Obtain a lock.
   *
   * @since  COmanage Registry v3.3.0
   * @param  integer $coId  CO ID
   * @param  string  $label Lock label
   * @return integer        Lock ID
   * @throws RuntimeException
   */
  
  public function obtain($coId, $label) {
    // The unique constraint should be sufficient to avoid needing a mutex or similar.
    
    $lock = array(
      'co_id' => $coId,
      'label' => $label,
      'pid'   => getmypid()
    );
    
    try {
      $this->save($lock);
    }
    catch(PDOException $e) {
      // Most likely unique constraint violation
      
      $args = array();
      $args['conditions']['Lock.co_id'] = $coId;
      $args['conditions']['Lock.label'] = $label;
      $args['contain'] = false;
      
      $l = $this->find('first', $args);
      
      if(!empty($l)) {
        throw new RuntimeException(_txt('er.lock.exists', array($l['Lock']['id'], $l['Lock']['pid'], $l['Lock']['created'])));
      } else {
        // Rethrow the original exception
        throw new RuntimeException($e->getMessage());
      }
    }
    // Let other exceptions pass up the stack
    
    return $this->id;
  }
  
  /**
   * Release a lock.
   *
   * @since  COmanage Registry v3.3.0
   * @param  integer $id  Lock ID
   */
  
  public function release($id) {
    return $this->delete($id);
  }
}
