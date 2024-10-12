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
    "DictionaryEntry" => array('dependent' => true),
    "AttributeEnumeration"
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

  /**
   * Try to find a corresponding DictionaryEntry for the provided value.
   * 
   * @since  COmanage Registry v4.4.0
   * @param  integer  $id     Dictionary ID
   * @param  string   $value  Value to search for
   * @return string           Value if found, null otherwise
   */

  public function mapToEntry($id, $value) {
    $mode = $this->field('mode', array('Dictionary.id' => $id));
    $coId = $this->field('co_id', array('Dictionary.id' => $id));
    
    if(!$mode) {
      throw new InvalidArgumentException(_txt('er.notfound', array('ct.dictionaries.1', $id)));
    }

    // Note these checks are intentionally case sensitive, since they
    // should come from a prepopulated list and match exactly.

    switch($mode) {
      // For Organizations and CoDepartments, we use the name field, since that's what
      // we return in entries(), above. If there are multiple entries with the same name
      // we use whichever the database returns first.
      case DictionaryModeEnum::Department:
        return $this->Co->CoDepartment->field('id', array('CoDepartment.name' => $value,
                                                          'CoDepartment.co_id' => $coId));
        break;
      case DictionaryModeEnum::Organization:
        return $this->Co->Organization->field('id', array('Organization.name' => $value,
                                                          'Organization.co_id' => $coId));
        break;
      case DictionaryModeEnum::Standard:
        // As for isValidEntry(), this is slightly tricky because $value could
        // be a code or a value, but we only want to consider value when code is
        // empty.
        
        $args = array();
        $args['conditions']['DictionaryEntry.dictionary_id'] = $id;
        $args['conditions']['OR'][] = array('DictionaryEntry.code' => $value);
        $args['conditions']['OR'][] = array(
          'DictionaryEntry.code' => null,
          'DictionaryEntry.value' => $value
        );

        $entry = $this->DictionaryEntry->find('first', $args);
        
        if(!empty($entry)) {
          return !empty($entry['DictionaryEntry']['code']) ? $entry['DictionaryEntry']['code'] : $value;
        }
        break;
      default:
        throw new LogicException('NOT IMPLEMENTED');
        break;
    }

    return null;
  }
  
  /**
   * Given a string in the Dictionary, find the corresponding display value.
   *
   * @since  COmanage Registry v4.4.0
   * @param  integer  $id     Dictionary ID
   * @param  string   $value  Value to check
   * @return string           Display string
   * @throws InvalidArgumentException
   */
  
  public function mapToString($id, $value) {
    // This is similar to isValidEntry, above, however we try to find a display
    // string in accordance with our configuratio.

    $mode = $this->field('mode', array('Dictionary.id' => $id));
    $coId = $this->field('co_id', array('Dictionary.id' => $id));
    
    if(!$mode) {
      throw new InvalidArgumentException(_txt('er.notfound', array('ct.dictionaries.1', $id)));
    }
    
    switch($mode) {
      case DictionaryModeEnum::Department:
        // $value is actually CoDepartment.id. We check the co_id and return the name.
        $args = array();
        $args['conditions']['CoDepartment.id'] = (int)$value;
        // We shouldn't need to do this, but since the field is technically a string...
        $args['conditions']['CoDepartment.co_id'] = $coId;
        $args['contain'] = false;

        $dept = $this->Co->CoDepartment->find('first', $args);

        if(empty($dept)) {
          throw new InvalidArgumentException(_txt('er.notfound', array('ct.co_departments.1', $value)));
        }

        return $dept['CoDepartment']['name'];
        break;
      case DictionaryModeEnum::Organization:
        // Similar to Department, above
        $args = array();
        $args['conditions']['Organization.id'] = (int)$value;
        $args['conditions']['Organization.co_id'] = $coId;
        $args['contain'] = false;

        $org = $this->Co->Organization->find('first', $args);

        if(empty($org)) {
          throw new InvalidArgumentException(_txt('er.notfound', array('ct.organizations.1', $value)));
        }

        return $org['Organization']['name'];
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
        
        // In either case we want to return the value, not the code

        $entry = $this->DictionaryEntry->find('first', $args);

        if(empty($entry)) {
          throw new InvalidArgumentException(_txt('er.notfound', array('ct.dictionary_entries.1', $value)));
        }

        return $entry['DictionaryEntry']['value'];
        break;
      default:
        throw new LogicException('NOT IMPLEMENTED');
        break;
    }
  }
}
