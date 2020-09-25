<?php
/**
 * COmanage Registry Dictionary Model
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

class Dictionary extends AppModel {
  // Define class name for cake
  public $name = "Dictionary";
  
  // Association rules from this model to other models
  public $belongsTo = array("Co");
  
  public $hasMany = array(
    "DictionaryEntry" => array('dependent' => true)
  );
  
  // Default display field for cake generated views
  public $displayField = "description";
  
  public $actsAs = array('Containable',
                         'Changelog' => array('priority' => 5));
  
  // Validation rules for table elements
  public $validate = array(
    'co_id' => array(
      'rule' => 'numeric',
      'required' => true,
      'allowEmpty' => false
    ),
    'description' => array(
      'rule' => array('validateInput'),
      'required' => true,
      'allowEmpty' => false
    ),
    'mode' => array(
      'rule' => array('inList', array(DictionaryModeEnum::Department,
                                      DictionaryModeEnum::Organization,
                                      DictionaryModeEnum::Standard)),
      'required' => true,
      'allowEmpty' => false
    )
  );
  
  /**
   * Obtain the entries in this Dictionary.
   *
   * @since  COmanage Registry v4.0.0
   * @param  integer $id Dictionary ID
   * @return array       Array with two keys: 'dictionary' (entries) and 'coded' (boolean, true if entries are coded)
   * @throws InvalidArgumentException
   */
  
  public function entries($id) {
    // First determine the mode of the Dictionary
    
    $mode = $this->field('mode', array('Dictionary.id' => $id));
    $coId = $this->field('co_id', array('Dictionary.id' => $id));
    
    if(!$mode) {
      throw new InvalidArgumentException(_txt('er.notfound', array('ct.dictionaries.1', $id)));
    }
    
    $ret = array(
      'coded' => false,
      'dictionary' => array()
    );
    
    switch($mode) {
      case DictionaryModeEnum::Department:
        $args = array();
        $args['conditions']['CoDepartment.co_id'] = $coId;
        $args['order'] = 'CoDepartment.name ASC';
        $args['fields'] = array('id', 'name');
        
        $ret['dictionary'] = $this->Co->CoDepartment->find('list', $args);
        $ret['coded'] = true;
        break;
      case DictionaryModeEnum::Organization:
        $args = array();
        $args['conditions']['Organization.co_id'] = $coId;
        $args['order'] = 'Organization.name ASC';
        $args['fields'] = array('id', 'name');
        
        $ret['dictionary'] = $this->Co->Organization->find('list', $args);
        $ret['coded'] = true;
        break;
      case DictionaryModeEnum::Standard:
        // Pull the list of Dictionary Entries
        $args = array();
        $args['conditions']['DictionaryEntry.dictionary_id'] = $id;
        $args['order'] = array('DictionaryEntry.ordr', 'DictionaryEntry.value');
        $args['contain'] = false;
        // Because code is optional, we can't use find('list'). We also have to manually
        // build the array to return.
        
        $dict = $this->DictionaryEntry->find('all', $args);
        
        foreach($dict as $d) {
          if(!empty($d['DictionaryEntry']['code'])) {
            $ret['dictionary'][ $d['DictionaryEntry']['code'] ] = $d['DictionaryEntry']['value'];
            // If any entry is coded, we treat the whole dictionary as coded
            $ret['coded'] = true;
          } else {
            $ret['dictionary'][] = $d['DictionaryEntry']['value'];
          }
        }
        break;
      default:
        throw new LogicException('NOT IMPLEMENTED');
        break;
    }
    
    return $ret;
  }
  
  /**
   * Determine if a specific value is a valid Dictionary Entry.
   *
   * @since  COmanage Registry v4.0.0
   * @param  integer  $id    Dictionary ID
   * @param  string   $value Value to check
   * @return boolean         True if valid, false otherwise
   * @throws InvalidArgumentException
   */
  
  public function isValidEntry($id, $value) {
    $mode = $this->field('mode', array('Dictionary.id' => $id));
    $coId = $this->field('co_id', array('Dictionary.id' => $id));
    
    if(!$mode) {
      throw new InvalidArgumentException(_txt('er.notfound', array('ct.dictionaries.1', $id)));
    }
    
    switch($mode) {
      case DictionaryModeEnum::Department:
        // $value is actually CoDepartment.id. We simply pull the co_id and make
        // sure it matches (and exists).
        $recCoId = $this->Co->CoDepartment->field('co_id', array('CoDepartment.id' => (int)$value));
        
        return ($recCoId && $recCoId == $coId);
        break;
      case DictionaryModeEnum::Organization:
        $recCoId = $this->Co->Organization->field('co_id', array('Organization.id' => (int)$value));
        
        return ($recCoId && $recCoId == $coId);
        break;
      case DictionaryModeEnum::Standard:
        // Make sure $value is valid. This is slightly tricky because $value could
        // be a code or a value, but we only want to consider value when code is
        // empty. Note these checks are intentionally case sensitive, since they
        // should come from a prepopulated list and match exactly.
        
        $args = array();
        $args['conditions']['DictionaryEntry.dictionary_id'] = $id;
        $args['conditions']['OR'][] = array('DictionaryEntry.code' => $value);
        $args['conditions']['OR'][] = array(
          'DictionaryEntry.code' => null,
          'DictionaryEntry.value' => $value
        );
        
        return (bool)$this->DictionaryEntry->find('count', $args);
        break;
      default:
        throw new LogicException('NOT IMPLEMENTED');
        break;
    }
  }
}
