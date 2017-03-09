<?php
/**
 * COmanage Registry CO Extended Attribute Model
 *
 * Portions licensed to the University Corporation for Advanced Internet
 * Development, Inc. ("UCAID") under one or more contributor license agreements.
 * See the NOTICE file distributed with this work for additional information
 * regarding copyright ownership.
 *
 * UCAID licenses this file to you under the Apache License, Version 2.0
 * (the "License"); you may not use this file except in compliance with the
 * License. You may obtain a copy of the License at:
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.2
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */
  
class CoExtendedAttribute extends AppModel {
  // Define class name for cake
  public $name = "CoExtendedAttribute";
  
  // Current schema version for API
  public $version = "1.0";
  
  // Add behaviors
  public $actsAs = array('Containable');
  
  // Association rules from this model to other models
  public $belongsTo = array("Co");                     // A CO has zero or more extended attributes
  
  // Default display field for cake generated views
  public $displayField = "display_name";
  
  // Default ordering for find operations
  public $order = array("CoExtendedAttribute.name");
  
  // Validation rules for table elements
  public $validate = array(
    'co_id' => array(
      'rule' => 'numeric',
      'required' => true,
      'message' => 'A CO ID must be provided'
    ),
    'name' => array(
      'rule' => array('custom', '/^[a-z0-9]*$/'),
      'required' => true,
      'message' => 'A name must be provided and consist of lowercase, alphanumeric characters'
    ),
    'display_name' => array(
      'rule' => array('validateInput'),
      'required' => true,
      'message' => 'A name must be provided'
    ),
    'type' => array(
      'rule' => array('inList', array('INTEGER', 'TIMESTAMP', 'VARCHAR(32)')),
      'required' => true,
      'message' => 'A valid data type must be provided'
    ),
    'indx' => array(
      'rule' => array('boolean')
    )
  );
  
  /**
   * Dynamically assemble validation rules for an extended attribute.
   *
   * @since  COmanage Registry v0.5
   * @param  integer CO ID of attribute
   * @param  integer Name of Extended Attribute
   * @return Array Validation rules, in the standard Cake format
   */
  
  public function validationRules($coId, $name) {
    $ret = array();
    
    // Pull the type of the attribute and put together a suitable array
    
    $extAttr = $this->findByCoIdAndName($coId, $name);
    
    if($extAttr) {
      switch($extAttr['CoExtendedAttribute']['type']) {
      case ExtendedAttributeEnum::Integer:
        $ret['rule'] = array('numeric');
        break;
      case ExtendedAttributeEnum::Timestamp:
        $ret['rule'] = array('validateTimestamp');
        break;
      case ExtendedAttributeEnum::Varchar32:
        $ret['rule'] = array('maxLength', 128);
        break;
      }
    }
    
    return $ret;
  }
}
