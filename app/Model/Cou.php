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
  // This breaks with tree behavior, see https://bugs.internet2.edu/jira/browse/CO-230
  //  public $order = array("Cou.name");
  
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

  public $actsAs = array('Tree');

  /**
   * Obtain all COUs within a specified CO.
   *
   * @since  COmanage Registry v0.4
   * @param  integer CO ID
   * @param  string Format, one of "names", "ids", or "hash" of id => name
   * @return Array List or hash of member COUs, as specified by $format
   */
  
  public function allCous($coId, $format="hash") {
    $args['conditions']['Cou.co_id'] = $coId;
    
    $cous = $this->find("list", $args);
    
    if($cous) {
      switch($format) {
      case 'names':
        return(array_values($cous));
        break;
      case 'ids':
        return(array_keys($cous));
        break;
      default:
        return($cous);
        break;
      }
    }
    
    return(array());
  }

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
  
  public function childCous($parentCou, $co_id) {
    // Convert names to id numbers
    $conditions = array("Cou.name"  => $parentCou,
                        "Cou.co_id" => $co_id);
    $parentData = $this->find('all', array('conditions' => $conditions));
    $parentData = Set::extract($parentData, '{n}.Cou.id');

    // Get children
    $allChildren = array();
    if($parentData != NULL)
    {
      foreach($parentData as $parent)
      {
        $thisChildren = $this->children($parent, false, 'name');
        if($thisChildren != NULL)
          $allChildren = array_merge($allChildren, $thisChildren);
      }
    }
    $allChildren = Set::extract($allChildren, '{n}.Cou.name');

    if($allChildren != NULL)
      return(array_merge($parentCou, $allChildren));
    else
      return($parentCou);
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

    // Check for NULL to avoid warning/error from array_search (See CO-240)
    if($childrenList == NULL)
      return false;
    else
      return(array_search($couNode, $childrenList));
  }
}
