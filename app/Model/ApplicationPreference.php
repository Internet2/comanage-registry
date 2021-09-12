<?php
/**
 * COmanage Registry Application Preference Model
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
 * @link          https://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v4.0.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

class ApplicationPreference extends AppModel {
  // Define class name for cake
  public $name = "ApplicationPreference";
  
  // Current schema version for API
  public $version = "1.0";
  
  // Add behaviors
  // Because ApplicationPreference isn't maintaining application data (it's
  // basically frontend state, there's no reason to enable ChangelogBehavior).
  public $actsAs = array('Containable');
  
  // Association rules from this model to other models
  public $belongsTo = array(
    "CoPerson"
  );
  
  // Default display field for cake generated views
  public $displayField = "ApplicationPreference.tag";
  
  // Default ordering for find operations
//  public $order = array("tag");
  
  // Validation rules for table elements
  public $validate = array(
    'tag' => array(
      'content' => array(
        'rule' => array('validateInput'),
        'required' => true,
        'allowEmpty' => false,
      )
    ),
    'value' => array(
      'content' => array(
        'rule' => array('validateInput'),
        'required' => false,
        'allowEmpty' => true
      )
    ),
    'co_person_id' => array(
      'content' => array(
        'rule' => 'numeric',
        'required' => true,
        'allowEmpty' => false
      )
    )
  );
  
  /**
   * Retrieve an Application Preference.
   * @param  int    $coPersonId CO Person ID
   * @param  string $tag        Tag (key)
   * @return string|null        Value if found, null otherwise
   */
  
  public function retrieve($coPersonId, $tag) {
    $args = array();
    $args['conditions']['ApplicationPreference.co_person_id'] = $coPersonId;
    $args['conditions']['ApplicationPreference.tag'] = $tag;
    $args['fields'] = array('tag', 'value');
    $args['contain'] = false;
    
    // There should only be one...
    $tags = $this->find('first', $args);
    
    if(!empty($tags['ApplicationPreference']['value'])) {
      return $tags['ApplicationPreference']['value'];
    }
    
    return null;
  }

  /**
   * Retrieve all Application Preferences for a user.
   * @param  int $coPersonId      CO Person ID
   * @return array                Array of values (possibly empty)
   */

  public function retrieveAll($coPersonId) {
    $args = array();
    $args['conditions']['ApplicationPreference.co_person_id'] = $coPersonId;
    $args['fields'] = array('tag', 'value');
    $args['contain'] = false;

    return $this->find('list', $args);
  }
  
  /**
   * Store an Application Preference.
   *
   * @since  COmanage Registry v4.0.0
   * @param  int    $coPersonId CO Person ID
   * @param  string $tag        Tag (key)
   * @param  string $value      Value
   * @return int                Application Preference ID
   * @throws RuntimeException
   */
  
  public function store($coPersonId, $tag, $value) {
    // We only allow one value per tag, so if we have a value do an update.
    // (We could also just delete any existing value, but this approach
    // preserves changelog.)
    
    $this->_begin();
    
    $pref = array(
      'co_person_id' => $coPersonId,
      'tag' => $tag,
      'value' => $value
    );
    
    $args = array();
    $args['conditions']['ApplicationPreference.co_person_id'] = $coPersonId;
    $args['conditions']['ApplicationPreference.tag'] = $tag;
    $args['contain'] = false;
    
    $ids = $this->findForUpdate($args['conditions'], array('id'));
    
    if(!empty($ids[0]['ApplicationPreference']['id'])) {
      // convert to an update
      $pref['id'] = $ids[0]['ApplicationPreference']['id'];
    }
    
    try {
      $this->save($pref);
      $this->_commit();
    } catch(Exception $e) {
      $this->_rollback();
      throw new RuntimeException($e->getMessage());
    }
    
    return $this->id;
  }
}
