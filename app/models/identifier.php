<?php
  /*
   * COmanage Gears Identifier Model
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

  class Identifier extends AppModel {
    // Define class name for cake
    var $name = "Identifier";
    
    // Association rules from this model to other models
    var $belongsTo = array("CoPerson",         // An identifier may be attached to a CO Person
                           "OrgIdentity");     // An identifier may be attached to an Org Identity
    
    // Default display field for cake generated views
    var $displayField = "identifier";
    
    // Default ordering for find operations
    var $order = array("identifier");
    
    // Validation rules for table elements
    var $validate = array(
      // Don't require any element since $belongsTo saves won't validate if they're empty
      'identifier_t' => array(
        'rule' => array('inList', array('eppn', 'eptid', 'mail', 'openid'))
      ),
      'login' => array(
        'rule' => array('boolean')
      )
    );
    
    // Enum type hints
    
    var $cm_enum_types = array(
      'type' => 'identifier_t'
    );
  }
?>