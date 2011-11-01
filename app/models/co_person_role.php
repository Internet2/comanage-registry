<?php
  /*
   * COmanage Gears CO Person Role Model
   *
   * Version: $Revision$
   * Date: $Date$
   *
   * Copyright (C) 2010-2011 University Corporation for Advanced Internet Development, Inc.
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

  class CoPersonRole extends AppModel {
    // Define class name for cake
    var $name = "CoPersonRole";
    
    // Association rules from this model to other models
    var $belongsTo = array("Cou",             // A CO Org Person To is attached to one COU
                           "CoPerson");       // A CO Org Person To is attached to one CO Person
    
    var $hasMany = array("Address" =>                 // A person can have one or more address
                         array('dependent' => true),
                         "CoPetition" =>
                         array('dependent' => true,
                               'foreignKey' => 'enrollee_co_person_role_id'),
                         "TelephoneNumber" =>         // A person can have one or more telephone numbers
                         array('dependent' => true));

    // Default display field for cake generated views
    var $displayField = "CoPersonRole.id";
    
    // Default ordering for find operations
    var $order = array("CoPersonRole.id");
    
    // Validation rules for table elements
    var $validate = array(
      'edu_person_affiliation' => array(
        'rule' => array('inList', array('faculty', 'student', 'staff', 'alum', 'member', 'affiliate', 'employee', 'library-walk-in')),
        'required' => true
      ),
      'status' => array(
        'rule' => array('inList', array('A', 'D', 'I', 'P', 'S', 'X'))
      )
    );
    
    // Enum type hints
    
    var $cm_enum_types = array(
      'status' => 'status_t'
    );
  }
?>