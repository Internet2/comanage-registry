<?php
/**
 * COmanage Registry COU Model
 *
 * Copyright (C) 2010-12 University Corporation for Advanced Internet Development, Inc.
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
 * @copyright     Copyright (C) 2010-12 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.2
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */
  
class Cou extends AppModel {
  // Define class name for cake
  public $name = "Cou";
  
  // Current schema version for API
  public $version = "1.0";
  
  // Association rules from this model to other models
  public $belongsTo = array(
    // A COU is attached to a CO
    "Co",
    // Also attached to a parent COU
    "ParentCou" => array(
      'className' => 'Cou',
      'foreignKey'=>'parent_id'
    ),
  );
  
  public $hasMany = array(
    "ChildCou" => array(
      'className' => 'Cou',
      'foreignKey'=>'parent_id'
    ),
    "CoPersonRole",
    "CoPetition"
  );

  // Default display field for cake generated views
  public $displayField = "name";
  
  // Default ordering for find operations
  public $order = array("Cou.name");
  
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
      'required' => false
    ),
    'parent_id' => array(
      // Strangely, when specified is numeric cake decides to make required = true
      'rule' => '/[0-9]*/',
      'required' => false
    ),
    'lft' => array(
      'rule' => 'numeric',
      'required' => false
    ),
    'rght' => array(
      'rule' => 'numeric',
      'required' => false
    )
  );

// XXX tree behavior appears to be throwing database errors on add
//  public $actsAs = array('Tree');

  /**
   * Generates dropdown option list for html for a COU.
   *
   * @since  COmanage Registry v0.3
   * @param  integer COU that needs parent options; NULL if new
   * @return Array Array of [id] => [name]
   */
  
  public function potentialParents($currentCou) {
    // Editing an existing COU requires removing it and its children
    if($currentCou != NULL)
    {
      // Find this COU and its children
      $childrenArrays = $this->children($currentCou, false, 'id');
      $childrenList = Set::extract($childrenArrays, '{n}.Cou.id');

      // Set up filter to ignore children
      $conditions = array(
                    'AND' => array(
                      array(
                        'NOT' => array(
                          array('Cou.id' => $childrenList),
                          array('Cou.id' => $currentCou)
                        )
                      ),
                      array(
                        array('Cou.co_id' => $this->data['Cou']['co_id'] )
                      )
                    )
                  );
    }
    // Create options list all other COUS in CO
    $optionArrays = $this->find('all', array('conditions' => $conditions) );
    $optionList = Set::combine($optionArrays, '{n}.Cou.id','{n}.Cou.name');

    return($optionList);
  }

  /**
   * Takes an array of names and returns array of them and their descendant COUs.
   *
   * @since  COmanage Registry v0.3
   * @param  Array COU(s) that need children listed
   * @return Array Names
   */
  
  public function childCous($parentCou) {
    // Convert names to id numbers
    // XXX COU names are not guaranteed to be unique
    $conditions = array("Cou.name" => $parentCou);
    $parentData = $this->find('all', array('conditions' => $conditions));
    $parentData = Set::extract($parentData, '{n}.Cou.id');

    // Get children
    $allChildren = array();
    foreach($parentData as $parent)
    {
      $allChildren = array_merge($allChildren, $this->children($parent, false, 'name'));
    }
    $allChildren = Set::extract($allChildren, '{n}.Cou.name');

    return(array_merge($parentCou, $allChildren));
  }

  /**
   * Check if couId is a member of the current CO.
   *
   * @since  COmanage Registry v0.3
   * @param  integer COU ID to check
   * @return boolean True if member, false otherwise
   */
  
  public function isCoMember($couId) {
    // Query for COU in this CO
    $conditions = array("Cou.id" => $couId);

    $dataOfCou = $this->find('first', array('conditions'=>$conditions)); 
    $coOfCou = $dataOfCou['Co']['id'];
    $currentCou = $this->data['Cou']['co_id'];

    return($coOfCou == $currentCou ? true : false);
  }

  /**
   * Check if couNode is a child of couBranch.
   *
   * @since  COmanage Registry v0.3
   * @param  Array Head of the branch to be searched
   * @param  string Node to be looked for
   * @return boolean True if child, false otherwise
   */
  
  public function isChildCou($couBranch, $couNode) {
    $childrenArrays = $this->children($couBranch, false, 'id');
    $childrenList = Set::extract($childrenArrays, '{n}.Cou.id');

    return(array_search($couNode, $childrenList));
  }
}
