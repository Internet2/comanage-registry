<?php
/**
 * COmanage Registry CO Group Nesting Model
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
  
class CoGroupNesting extends AppModel {
  // Define class name for cake
  public $name = "CoGroupNesting";
  
  // Current schema version for API
  public $version = "1.0";
  
  // Association rules from this model to other models
  public $belongsTo = array(
    // A CoGroupNesting is attached to one CoGroup
    "CoGroup",
    // A CoGroupMember is attached to one CoPerson
    "TargetCoGroup" => array(
      'className' => 'CoGroup',
      'foreignKey' => 'target_co_group_id'
    )
  );
  
  public $hasMany = array(
    "CoGroupMember"
  );
  
  // Default display field for cake generated views
  public $displayField = "co_group_id";
  
  // Default ordering for find operations
// XXX CO-296 Toss default order?
//  public $order = array("co_person_id");
  
  public $actsAs = array('Containable',
                         'Provisioner',
                         'Changelog' => array('priority' => 5));

  // Validation rules for table elements
  public $validate = array(
    'co_group_id' => array(
      'content' => array(
        'rule' => 'numeric',
        'required' => true
      )
    ),
    'target_co_group_id' => array(
      'content' => array(
        'rule' => 'numeric',
        'required' => true
      )
    ),
    'negate' => array(
      'rule' => array('boolean'),
      'required' => false,
      'allowEmpty' => true
    )
  );
  
  /**
   * Execute logic after model delete.
   *
   * @since  COmanage Registry v3.3.0
   */

  public function afterDelete() {
    // We need to reconcile the parent group after a delete (ie: after the nesting
    // is removed) to remove any indirect group memberships. Since this model is
    // changelog enabled, the references are still valid.
    
    $parentCoGroupId = $this->field('target_co_group_id');
    
    if($parentCoGroupId) {
      $this->CoGroup->reconcile($parentCoGroupId);
    }
    
    return true;
  }
  
  /**
   * Callback after model save.
   *
   * @since  COmanage Registry v3.3.0
   * @param  Boolean $created True if new model is saved (ie: add)
   * @param  Array $options Options, as based to model::save()
   * @return Boolean True on success
   */

  public function afterSave($created, $options = Array()) {
    // On any save operation we trigger a reconciliation of the parent group.
    // This should only ever happen on an add, since we don't (currently)
    // allow an update of an existing nesting, and deletes are handled separately.
    
    if(!empty($this->data['CoGroupNesting']['target_co_group_id'])) {
      $this->CoGroup->reconcile($this->data['CoGroupNesting']['target_co_group_id']);
    }
    
    return true;
  }
  
  /**
   * Actions to take before a save operation is executed.
   *
   * @since  COmanage Registry v3.3.0
   * @throws InvalidArgumentException
   */

  public function beforeSave($options = array()) {
    if(!empty($this->data['CoGroupNesting'])) {
      // Nested and parent groups cannot be the same
      if($this->data['CoGroupNesting']['co_group_id'] == $this->data['CoGroupNesting']['target_co_group_id']) {
        throw new InvalidArgumentException(_txt('er.gr.nest.same'));
      }
      
      // Nested group cannot already be a member of the parent group
      $args = array();
      $args['conditions']['CoGroupNesting.co_group_id'] = $this->data['CoGroupNesting']['co_group_id'];
      $args['conditions']['CoGroupNesting.target_co_group_id'] = $this->data['CoGroupNesting']['target_co_group_id'];
      if(!empty($this->data['CoGroupNesting']['id'])) {
        $args['conditions']['CoGroupNesting.id <>'] = $this->data['CoGroupNesting']['id'];
      }

      if($this->find('count', $args) > 0) {
        throw new InvalidArgumentException(_txt('er.gr.nest.dupe'));
      }
      
      // Nested group cannot create a loop. To check, we pull any nestings where
      // our target group is the parent and make sure there are no nested groups
      // that are our parent. We continue until we run out of children to check
      // or we reach max recursion.
      $this->checkNestedLoop($this->data['CoGroupNesting']['target_co_group_id'],
                             $this->data['CoGroupNesting']['co_group_id']);
    }
    
    return true;
  }
  
  /**
   * Check a proposed nesting for loops.
   * 
   * @param  integer $targetCoGroupId Proposed Target CO Group ID
   * @param  integer $nestedCoGroupId Source CO Group ID to check for looping
   * @param  integer $depth           Current depth, for recursion maximum depth
   * @return boolean                  True if no loops were detected
   * @throws InvalidArgumentException
   * @throws RuntimeException
   */
  
  protected function checkNestedLoop($targetCoGroupId, $nestedCoGroupId, $depth=1) {
    if($depth > 10) {
      throw new RuntimeException(_txt('er.gr.nest.max'));
    }
    
    $args = array();
    $args['conditions']['CoGroupNesting.target_co_group_id'] = $nestedCoGroupId;
    $args['fields'] = array('co_group_id', 'target_co_group_id');
    
    $children = $this->find('list', $args);
    
    if(!empty($children)) {
      foreach($children as $gid => $pgid) {
        if($gid == $targetCoGroupId) {
          throw new InvalidArgumentException(_txt('er.gr.nest.loop'));
        }
        
        $this->checkNestedLoop($targetCoGroupId, $gid, $depth+1);
      }
    }
    
    return true;
  }
  
  /**
   * Obtain the CO ID for a record.
   *
   * @since  COmanage Registry v3.3.0
   * @param  integer Record to retrieve for
   * @return integer Corresponding CO ID, or NULL if record has no corresponding CO ID
   * @throws InvalidArgumentException
   * @throws RuntimeException
   */
  
  public function findCoForRecord($id) {
    // We need to get the co id via the group
    
    $args = array();
    $args['conditions']['CoGroupNesting.id'] = $id;
    $args['contain'][] = 'CoGroup';

    $gr = $this->find('first', $args);

    if(!empty($gr['CoGroup']['co_id'])) {
      return $gr['CoGroup']['co_id'];
    }

    return parent::findCoForRecord($id);
  }
}
