<?php
  /*
   * COmanage Gears CoGroup Model
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
  
  class CoGroup extends AppModel {
    // Define class name for cake
    var $name = "CoGroup";
    
    // Association rules from this model to other models
    var $hasMany = array("CoGroupMember" =>          // A CoGroup has zero or more members
                         array('dependent' => true));

    var $belongsTo = array("Co");                    // A CoGroup is attached to one CO
     
    // Default display field for cake generated views
    var $displayField = "name";
    
    // Default ordering for find operations
    var $order = array("CoGroup.name");
    
    // Validation rules for table elements
    var $validate = array(
      'name' => array(
        'rule' => 'notEmpty',
        'required' => true,
        'message' => 'A name must be provided'
      ),
      'open' => array(
        'rule' => array('boolean')
      ),
      'status' => array(
        'rule' => array('inList', array('A', 'S')),
        'required' => true,
        'message' => 'A valid status must be selected'
      )
    );
    
    // Enum type hints
    
    var $cm_enum_types = array(
      'status' => 'status_t'
    );
  }
?>