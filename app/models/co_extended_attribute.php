<?php
  /*
   * COmanage Gears Per-CO Extended Attribute Model
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
  
  class CoExtendedAttribute extends AppModel {
    // Define class name for cake
    var $name = "CoExtendedAttribute";
    
    // Association rules from this model to other models
    var $belongsTo = array("Co");                     // A CO has zero or more extended attributes
    
    // Default display field for cake generated views
    var $displayField = "display_name";
    
    // Default ordering for find operations
    var $order = array("CoExtendedAttribute.name");
    
    // Validation rules for table elements
    var $validate = array(
      'name' => array(
        'rule' => 'alphaNumeric',
        'required' => true,
        'message' => 'A name must be provided and consist of alphanumeric characters'
      ),
      'display_name' => array(
        'rule' => 'notEmpty',
        'required' => true,
        'message' => 'A name must be provided'
      ),
      'type' => array(
        'rule' => array('inList', array('INTEGER', 'TIMESTAMP', 'VARCHAR(32)')),
        'required' => true,
        'message' => 'A valid data type must be provided'
      ),
      'index' => array(
        'rule' => array('boolean')
      )
    );
  }
?>