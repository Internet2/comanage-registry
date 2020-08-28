<?php
/**
 * COmanage Registry Dictionary Identifier Validator Model
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
 * @since         COmanage Registry v4.0.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

class DictionaryIdentifierValidator extends AppModel {
  // Required by COmanage Plugins
  public $cmPluginType = "identifiervalidator";
  
  public $cmPluginInstantiate = true;
  
  // Document foreign keys
  public $cmPluginHasMany = array(
    "Dictionary" => "DictionaryIdentifierValidator"
  );
  
  // Association rules from this model to other models
  public $belongsTo = array(
    "CoIdentifierValidator",
    "Dictionary"
  );
  
  // Default display field for cake generated views
  public $displayField = "mode";
  
  // Validation rules for table elements
  public $validate = array(
    'co_identifier_validator_id' => array(
      'rule' => 'numeric',
      'required' => true,
      'allowEmpty' => false
    ),
    'dictionary_id' => array(
      'rule' => 'numeric',
      'required' => true,
      'allowEmpty' => false
    ),
    'mode' => array(
      'rule' => array('inList', array(ComparisonEnum::ContainsInsensitive,
                                      ComparisonEnum::EqualsInsensitive)),
      'required' => true,
      'allowEmpty' => false
    )
  );
  
  /**
   * Expose menu items.
   * 
   * @since COmanage Registry v4.0.0
   * @return Array with menu location type as key and array of labels, controllers, actions as values.
   */
  
  public function cmPluginMenus() {
    return array();
  }
  
  /**
   * Validate an identifier (which could also be an email address).
   *
   * @since  COmanage Registry v4.0.0
   * @param  String  $identifier            The identifier (or email address) to be validated
   * @param  Array   $coIdentifierValidator CO Identifier Validator configuration
   * @param  Array   $coExtendedType        CO Extended Type configuration describing $identifier
   * @param  Array   $pluginCfg             Configuration information for this plugin, if instantiated
   * @return Boolean True if $identifier is valid and available
   * @throws InvalidArgumentException If $identifier is not of the correct format
   * @throws OverflowException If $identifier is already in use
   */
  
  public function validate($identifier, $coIdentifierValidator, $coExtendedType, $pluginCfg) {
    // We need to compare $identifier against all DictionaryEntries. In theory we
    // could do this on the DB server with clever SQL queries, but it's a little
    // clearer to just pull the records and filter in PHP.
    
    $args = array();
    $args['conditions']['DictionaryEntry.dictionary_id'] = $pluginCfg['dictionary_id'];
    $args['fields'] = array('value', 'id');
    
    $entries = $this->Dictionary->DictionaryEntry->find('list', $args);
    
    array_map('strtolower', $entries);
    
    if($pluginCfg['mode'] == ComparisonEnum::ContainsInsensitive) {
      foreach(array_keys($entries) as $e) {
        if(strpos(strtolower($identifier), $e) !== false) {
          throw new InvalidArgumentException(_txt('er.dictidentifier.match'));
        }
      }
    } else {
      // We can do this as a simple key check
      
      if(array_key_exists(strtolower($identifier), $entries)) {
        throw new InvalidArgumentException(_txt('er.dictidentifier.match'));
      }
    }
    
    // If we made it here we have no objection
    return true;
  }
}
