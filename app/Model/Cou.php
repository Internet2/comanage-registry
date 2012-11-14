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
    "CoPetition",
    "CoEnrollmentFlowAuthzCou" => array(
      'className' => 'CoEnrollmentFlow',
      'foreignKey' => 'authz_cou_id'
    )
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

  public $actsAs = array('Containable', 'Tree');

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
   * Obtain the child COUs of a COU.
   *
   * @since  COmanage Registry v0.3
   * @param  String Name of Parent COU
   * @param  Integer CO ID for Parent COU
   * @param  Boolean Whether or not to return $parentCou in the results
   * @return Array List of COU IDs and Names
   * @throws InvalidArgumentException
   */
  
  public function childCous($parentCou, $co_id, $includeParent=false) {
    // Find $parentCou
    
    $args = array();
    $args['conditions']['Cou.name'] = $parentCou;
    $args['conditions']['Cou.co_id'] = $co_id;
    $args['contain'] = false;
    
    $parent = $this->find('first', $args);
    
    // Find children
    
    if(isset($parent['Cou']['id'])) {
      $children = $this->children($parent['Cou']['id'],
                                  false,
                                  array('id', 'name'));
      
      $ret = array();
      
      if($includeParent) {
        $ret[ $parent['Cou']['id'] ] = $parent['Cou']['name'];
      }
      
      foreach($children as $child) {
        $ret[ $child['Cou']['id'] ] = $child['Cou']['name'];
      }
      
      return $ret;
    } else {
      throw new InvalidArgumentException(_txt('er.unknown'), array($parentCou));
    }
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
   * @param  integer Head of the branch to be searched
   * @param  integer Node to be looked for
   * @return boolean True if child, false otherwise
   */

  public function isChildCou($couBranch, $couNode) {

    // Get list of all children of $couBranch
    $childrenArrays = $this->children($couBranch, false, 'id');
    $childrenList = Set::extract($childrenArrays, '{n}.Cou.id');

    // Check for NULL to avoid warning/error from array_search (See CO-240)
    if(($childrenList != NULL)
      && (array_search($couNode, $childrenList) !== false)) {
        // Node was found in the branch
        return true;
    }
    return false;
  }
}
