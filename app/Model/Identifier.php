<?php
/**
 * COmanage Registry Identifier Model
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
 * @since         COmanage Registry v0.1
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */

class Identifier extends AppModel {
  // Define class name for cake
  public $name = "Identifier";
  
  // Current schema version for API
  public $version = "1.0";
  
  // Association rules from this model to other models
  public $belongsTo = array(
    // An identifier may be attached to a CO Person
    "CoPerson",
    // An identifier may be attached to an Org Identity
    "OrgIdentity"
  );
  
  // Default display field for cake generated views
  public $displayField = "identifier";
  
  // Default ordering for find operations
  public $order = array("identifier");
  
  // Validation rules for table elements
  public $validate = array(
    // Don't require any element since $belongsTo saves won't validate if they're empty
    'identifier' => array(
      'rule' => 'notEmpty',
      'required' => false
    ),
    'type' => array(
      'rule' => array('inList', array(IdentifierEnum::ePPN,
                                      IdentifierEnum::ePTID,
                                      IdentifierEnum::Mail,
                                      IdentifierEnum::OpenID,
                                      IdentifierEnum::UID)),
      'required' => false
    ),
    'login' => array(
      'rule' => array('boolean'),
      'required' => false
    ),
    'co_person_id' => array(
      'rule' => 'numeric',
      'required' => false
    ),
    'org_identity_id' => array(
      'rule' => 'numeric',
      'required' => false
    )
  );
  
  // Enum type hints
  
  public $cm_enum_types = array(
    'type' => 'identifier_t'
  );
}
