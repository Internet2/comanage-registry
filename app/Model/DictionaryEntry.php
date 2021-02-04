<?php
/**
 * COmanage Registry Dictionary Entry Model
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

class DictionaryEntry extends AppModel {
  // Define class name for cake
  public $name = "DictionaryEntry";
  
  // Association rules from this model to other models
  public $belongsTo = array("Dictionary");
  
  // Default display field for cake generated views
  public $displayField = "value";
  
  public $actsAs = array('Containable',
                         'Changelog' => array('priority' => 5));
  
  // Validation rules for table elements
  public $validate = array(
    'dictionary_id' => array(
      'rule' => 'numeric',
      'required' => true,
      'allowEmpty' => false
    ),
    'value' => array(
      'rule' => array('validateInput'),
      'required' => true,
      'allowEmpty' => false
    ),
    'code' => array(
      'rule' => array('validateInput'),
      'required' => false,
      'allowEmpty' => true
    ),
    'ordr' => array(
      'rule' => 'numeric',
      'required' => false,
      'allowEmpty' => true
    )
  );
  
  // XXX Should this really be in bootstrap.php?
  protected $dictionaryDir = APP . DS . 'Lib' . DS . 'Dictionaries';
  
  /**
   * Actions to take before a save operation is executed.
   *
   * @since  COmanage Registry v4.0.0
   */

  public function beforeSave($options = array()) {
    if(isset($this->data['DictionaryEntry']['code'])) {
      // This is a specific workaround for a more general issue described (for Cake 3)
      // here: https://github.com/cakephp/cakephp/issues/9678
      // This should get a more general solution as part of framework migration.
      
      if(empty($this->data['DictionaryEntry']['code'])) {
        // Make sure we use a null and not an empty string, to correspond with
        // the find() in Dictionary::isValidEntry()p
        $this->data['DictionaryEntry']['code'] = null;
      }
    }
    
    return true;
  }
  
  /**
   * Obtain the CO ID for a record.
   *
   * @since  COmanage Registry v4.0.0
   * @param  integer Record to retrieve for
   * @return integer Corresponding CO ID, or NULL if record has no corresponding CO ID
   * @throws InvalidArgumentException
   * @throws RunTimeException
   */

  public function findCoForRecord($id) {
    // Override the parent version since we need to retrieve via the dictionary

    // First get the dictionary
    $dict = $this->field('dictionary_id', array('DictionaryEntry.id' => $id));

    if(!$dict) {
      throw new InvalidArgumentException(_txt('er.notfound', array('ct.dictionary_entries.1', $id)));
    }

    $coId = $this->Dictionary->field('co_id', array("Dictionary.id" => $dict));

    if($coId) {
      return $coId;
    } else {
      throw new InvalidArgumentException(_txt('er.notfound', array('ct.dictionary.1', $dict)));
    }
  }
  
  /**
   * Obtain the list of predefined Dictionaries.
   *
   * @since  COmanage Registry v4.0.0
   * @return array  Inventory of Dictionaries, key and title
   */
  
  public function predefinedDictionaries() {
    $ret = array();
    
    // Look for json files in the dictionary directory
    $dfiles = preg_grep("/\.json$/", scandir($this->dictionaryDir));
    
    // Parse each file for the title, and if we successfully parse the document
    // add it to the return array, keyed on the file name
    
    foreach($dfiles as $d) {
      $inbound = file_get_contents($this->dictionaryDir . "/" . $d);
      
      if(!empty($inbound)) {
        $json = json_decode($inbound);
        
        if(!empty($json->title)) {
          $ret[ $d ] = $json->title;
        }
      }
    }
    
    return $ret;
  }
  
  /**
   * Upload a Dictionary from a File, in Dictionary File Format. See
   * https://spaces.at.internet2.edu/display/COmanage/Dictionaries
   *
   * @since  COmanage Registry v4.0.0
   * @param  integer $dictionaryId Dictionary ID to apply file to
   * @param  string  $fileName     Dictionary File to process
   * @param  bool    $replace      If true, replace any existing Dictionary Entries (otherwise merge)
   * @return bool                  true on success
   * @throws InvalidArgumentException
   */

  public function uploadFromFile($dictionaryId, $fileName, $replace) {
    $inbound = file_get_contents($fileName);
    
    if($inbound === false) {
      throw new InvalidArgumentException(_txt('er.file.read', $fileName));
    }
    
    $json = json_decode($inbound, true);

    // An empty file is a valid file, we'll simply not do anything or purge
    // the dictionary (according to $replace).

    // The only supported Dictionary File Format is currently "v1".
    if(!isset($json['dictionary']) || !isset($json['format']) || $json['format'] != 'v1') {
      throw new InvalidArgumentException(_txt('er.file.parse', array($fileName)));
    }
    
    $outbound = array();
    
    if($replace) {
      // We could do something clever and only delete those rows that are not
      // in $inbound, but the table isn't changelog enabled (at the moment) so
      // there's not much value add in doing so.
      
      // Note deleteAll will NOT trigger callbacks, but we don't need them here
      $this->deleteAll(array('DictionaryEntry.dictionary_id' => $dictionaryId));
      
      $outbound = $json['dictionary'];
    } else {
      // Merge the inbound entries with the existing entries. To do so, pull all
      // current entries and remove those present from $inbound. We only consider
      // "value" meaningful for comparison purposes, and we ignore code and order.
      
      $args = array();
      $args['conditions']['DictionaryEntry.dictionary_id'] = $dictionaryId;
      $args['fields'] = array('value', 'code');
      
      $current = $this->find('list', $args);
      
      // We can't use array_diff_assoc since if 'code' is empty PHP will use
      // the position of the value in the array to determine presence, which will
      // create a lot of noise.
      
      // Merge should be a pretty rare operation, so it should be reasonably
      // efficient to use $current as a hash based on value, then walk the
      // uploaded dictionary for values.
      
      foreach($json['dictionary'] as $e) {
        if(!array_key_exists($e['value'], $current)) {
          $outbound[] = $e;
        }
      }
    }
    
    if(!empty($outbound)) {
      // Inject the Dictionary ID before saving
      
      $outbound = Hash::insert($outbound, '{n}.dictionary_id', $dictionaryId);
      
      $this->saveMany($outbound);
    }
    
    return true;
  }
}
