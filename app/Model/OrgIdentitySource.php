<?php
/**
 * COmanage Registry Organizational Identity Source Model
 *
 * Copyright (C) 2015 University Corporation for Advanced Internet Development, Inc.
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
 * @copyright     Copyright (C) 2015 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v1.1.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */

class OrgIdentitySource extends AppModel {
  // Define class name for cake
  public $name = "OrgIdentitySource";
  
  // Current schema version for API
  public $version = "1.0";
  
  // Add behaviors
  public $actsAs = array('Changelog' => array('priority' => 5),
                         'Containable');
  
  // Association rules from this model to other models
  public $belongsTo = array(
    // An Org Identity Source belongs to a CO, if org identities not pooled
    'Co'
  );
  
  public $hasMany = array(
    "OrgIdentitySourceRecord" => array(
      'dependent'  => true
    )
  );
  
  // Default display field for cake generated views
  public $displayField = "description";
  
  // Validation rules for table elements
  public $validate = array(
    'co_id' => array(
      'content' => array(
        'rule' => 'numeric',
        'required' => false,
        'allowEmpty' => true
      )
    ),
    'description' => array(
      'rule' => 'notBlank',
      'required' => false,
      'allowEmpty' => true
    ),
    'plugin' => array(
      // XXX This should be a dynamically generated list based on available plugins
      'rule' => 'notBlank',
      'required' => true,
      'message' => 'A plugin must be provided'
    ),
    'status' => array(
      'rule' => array(
        'inList',
        array(
          SuspendableStatusEnum::Active,
          SuspendableStatusEnum::Suspended
        )
      ),
      'required' => true,
      'message' => 'A valid status must be selected'
    )
  );
  
  /**
   * Callback after model save.
   *
   * @since  COmanage Registry v1.1.0
   * @param  Boolean $created True if new model is saved (ie: add)
   * @param  Array $options Options, as based to model::save()
   * @return Boolean True on success
   */
  
  public function afterSave($created, $options = Array()) {
    if($created) {
      // Create an instance of the plugin source.
      
      $pluginName = $this->data['OrgIdentitySource']['plugin'];
      $modelName = $pluginName;
      $pluginModelName = $pluginName . "." . $modelName;
      
      $source = array();
      $source[$modelName]['org_identity_source_id'] = $this->id;
      
      // Note that we have to disable validation because we want to create an empty row.
      if(!$this->$modelName->save($source, false)) {
        return false;
      }
    }
    
    return true;
  }

  /**
   * Bind the specified plugin's backend model
   *
   * @since COmanage Registry v1.1.0
   * @param Integer $id OrgIdentitySource ID
   * @return Object Plugin Backend Model reference
   * @throws InvalidArgumentException
   */
  
  protected function bindPluginBackendModel($id) {
    // Pull the plugin information associated with $id
    
    $args = array();
    $args['conditions']['OrgIdentitySource.id'] = $id;
    // Do not set contain = false, we need the related model to pass to the backend
    
    $ois = $this->find('first', $args);
    
    if(empty($ois)) {
      throw new InvalidArgumentException(_txt('er.notfound', array(_txt('ct.org_identity_sources.1'), $id)));
    }
    
    // Bind the backend model
    $bmodel = $ois['OrgIdentitySource']['plugin'] . '.' . $ois['OrgIdentitySource']['plugin'] . 'Backend';
    $Backend = ClassRegistry::init($bmodel);
    
    // And give it its configuration
    $Backend->setConfig($ois[ $ois['OrgIdentitySource']['plugin'] ]);
    
    return $Backend;
  }
  
  /**
   * Retrieve a record from an Org Identity Source Backend.
   *
   * @since  COmanage Registry v1.1.0
   * @param  Integer $id OrgIdentitySource to search
   * @param  String $key Record key to retrieve
   * @return Array Raw record and Array in OrgIdentity format
   * @throws InvalidArgumentException
   */
  
  public function retrieve($id, $key) {
    $Backend = $this->bindPluginBackendModel($id);
    
    return $Backend->retrieve($key);
  }
  
  /**
   * Perform a search against an Org Identity Source Backend.
   *
   * @since  COmanage Registry v1.1.0
   * @param  Integer $id OrgIdentitySource to search
   * @param  Array $attributes Array in key/value format, where key is the same as returned by searchAttributes()
   * @return Array Array in OrgIdentity format
   * @throws InvalidArgumentException
   */
  
  public function search($id, $attributes) {
    $Backend = $this->bindPluginBackendModel($id);
    
    return $Backend->search($attributes);
  }

  /**
   * Obtain the set of searchable attributes for the Org Identity Source Backend.
   *
   * @since  COmanage Registry v1.1.0
   * @param  Integer $id OrgIdentitySource to search
   * @return Array Array of searchable attributes
   * @throws InvalidArgumentException
   */
  
  public function searchableAttributes($id) {
    $Backend = $this->bindPluginBackendModel($id);
    
    return $Backend->searchableAttributes();
  }
}