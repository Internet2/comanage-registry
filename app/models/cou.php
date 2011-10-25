<?php
  /*
   * COmanage Gears COU Model
   *
   * Version: $Revision$
   * Date: $Date$
   *
   * Copyright (C) 2011 University Corporation for Advanced Internet Development, Inc.
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
   */
  
  class Cou extends AppModel {
    // Define class name for cake
    var $name = "Cou";
    
    // Association rules from this model to other models
    var $belongsTo = array(
      "Co",                                           // A COU is attached to a CO
      "ParentCou" => array(
        'className' => 'Cou','foreignKey'=>'parent_id'
      ),                                              // Also attached to a parent COU
    );
    
    var $hasMany = array("CoPersonRole",
                         "ChildCou" => array('className' => 'Cou','foreignKey'=>'parent_id'),
                        );

    // Default display field for cake generated views
    var $displayField = "name";
    
    // Default ordering for find operations
    var $order = array("Cou.name");
    
    // Validation rules for table elements
    var $validate = array(
      'name' => array(
        'rule' => 'notEmpty',
        'required' => true,
        'message' => 'A name must be provided'
      )
    );

    var $actsAs = array('Tree');

    function potentialParents($currentCou)
    {
      // Generates dropdown option list for html for a COU
      //
      // Parameters:
      // - currentCou: COU that needs parent options; NULL if new
      // - currentCo: CO; only necessary for new COUs
      //
      // Preconditions:
      //     None
      //
      // Postconditions:
      //     None
      //
      // Returns:
      // - array of [id] => [name]

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

    function childCous($parentCou)
    {
      // Takes an array of names and returns array of them and their descendant COUs
      //
      // Parameters:
      // - parentCou: COU(s) that need children listed
      //
      // Preconditions:
      //     None
      //
      // Postconditions:
      //     None
      //
      // Returns:
      // - array of names

      // Convert names to id numbers
      $conditions = array("Cou.name" => $parentCou);
      $parentData = $this->find('all', array('conditions' => $conditions));
      $parentData = Set::extract($parentData, '{n}.Cou.id');

      // Get children
      $allChildren = array();
      foreach($parentData as $parent)
      {
        $allChildren = array_merge($allChildren, $this->children( $parent, false, 'name'));
      }
      $allChildren = Set::extract($allChildren, '{n}.Cou.name');

      return(array_merge($parentCou, $allChildren));
    }

    function isCoMember($couId) 
    {
      // Checks if couId is a member of the current CO
      //
      // Parameters:
      // - couId - ID to be checked
      //
      // Preconditions:
      //     None
      //
      // Postconditions:
      //     None
      //
      // Returns:
      // - boolean

      // Query for COU in this CO
      $conditions = array("Cou.id" => $couId);

      $dataOfCou = $this->find('first', array('conditions'=>$conditions)); 
      $coOfCou = $dataOfCou['Co']['id'];
      $currentCou = $this->data['Cou']['co_id'];

      return($coOfCou == $currentCou ? true : false);
    }

    function isChildCou($couBranch, $couNode) 
    {
      // Checks if couNode is a child of couBranch
      //
      // Parameters:
      // - couBranch: head of the branch to be searched
      // - couNode: node to be looked for
      //
      // Preconditions:
      //     None
      //
      // Postconditions:
      //     None
      //
      // Returns:
      // - boolean

      $childrenArrays = $this->children($couBranch, false, 'id');
      $childrenList = Set::extract($childrenArrays, '{n}.Cou.id');

      return(array_search($couNode, $childrenList));
    }
  }
?>
