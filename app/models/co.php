<?php
  /*
   * COmanage Gears CO Model
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
  
  class Co extends AppModel {
    // Define class name for cake
    var $name = "Co";
    
    // Association rules from this model to other models
    var $hasMany = array("CoEnrollmentFlow" =>       // A CO has zero or more enrollment flows
                         array('dependent' => true),
                         "CoExtendedAttribute" =>    // A CO has zero or more extended attributes
                         array('dependent' => true),
                         "CoGroup" =>                // A CO has zero or more groups
                         array('dependent' => true),
                         "CoPerson" =>               // A CO can have zero or more CO people
                         array('dependent' => true),
                         "CoPetition" =>             // A CO can have zero or more petitions
                         array('dependent' => true),
                         "Cou" =>                    // A CO has zero or more COUs
                         array('dependent' => true));
    
    // Default display field for cake generated views
    var $displayField = "name";
    
    // Default ordering for find operations
    var $order = array("Co.name");
    
    // Validation rules for table elements
    var $validate = array(
      'name' => array(
        'rule' => 'notEmpty',
        'required' => true,
        'message' => 'A name must be provided'
      ),
      'status' => array(
        'rule' => array('inList', array('A', 'I')),
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