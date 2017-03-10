<?php
/**
 * COmanage Registry Regex Identifier Validator Model
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
 * @package       registry-plugin
 * @since         COmanage Registry v2.0.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

class RegexIdentifierValidator extends AppModel {
  // Required by COmanage Plugins
  public $cmPluginType = "identifiervalidator";
  
  public $cmPluginInstantiate = true;
  
  // Document foreign keys
  public $cmPluginHasMany = array();
  
  // Association rules from this model to other models
  public $belongsTo = array("CoIdentifierValidator");
  
  // Default display field for cake generated views
  public $displayField = "pattern";
  
  // Validation rules for table elements
  public $validate = array(
    'co_identifier_validator_id' => array(
      'rule' => 'numeric',
      'required' => true,
      'allowEmpty' => false
    ),
    'pattern' => array(
      'rule' => 'notBlank',
      'required' => true,
      'allowEmpty' => false
    )
  );
  
  /**
   * Expose menu items.
   * 
   * @ since COmanage Registry v2.0.0
   * @ return Array with menu location type as key and array of labels, controllers, actions as values.
   */
  
  public function cmPluginMenus() {
    return array();
  }
  
  /**
   * Validate an identifier (which could also be an email address).
   *
   * @since  COmanage Registry v2.0.0
   * @param  String  $identifier            The identifier (or email address) to be validated
   * @param  Array   $coIdentifierValidator CO Identifier Validator configuration
   * @param  Array   $coExtendedType        CO Extended Type configuration describing $identifier
   * @param  Array   $pluginCfg             Configuration information for this plugin, if instantiated
   * @return Boolean True if $identifier is valid and available
   * @throws InvalidArgumentException If $identifier is not of the correct format
   * @throws OverflowException If $identifier is already in use
   */
  
  public function validate($identifier, $coIdentifierValidator, $coExtendedType, $pluginCfg) {
    $match = preg_match($pluginCfg['pattern'], $identifier);
    
    if(!$match) {
      if($match === 0) {
        throw new InvalidArgumentException($coIdentifierValidator['description']);
      } else {
        // throw some error
        throw new RuntimeException(_txt('er.regexidentifier.error'));
      }
    }
    
    // If we made it here we have no objection
    return true;
  }
}
