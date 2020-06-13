<?php
/**
 * COmanage Registry API OrgIdentitySource Model
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
 * @since         COmanage Registry v3.3.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

class ApiSource extends AppModel {
  // Required by COmanage Plugins
  public $cmPluginType = "orgidsource";
  
  // Document foreign keys
  public $cmPluginHasMany = array();
  
  // Association rules from this model to other models
  public $belongsTo = array(
    "ApiUser",
    "OrgIdentitySource"
  );
  
  // Default display field for cake generated views
  public $displayField = "sor_label";
  
  public $actsAs = array('Containable');
  
  // Validation rules for table elements
  public $validate = array(
    'org_identity_source_id' => array(
      'rule' => 'numeric',
      'required' => true,
      'message' => 'An Org Identity Source ID must be provided'
    ),
    'sor_label' => array(
      'rule' => 'alphaNumeric',
      'required' => true,
      'allowEmpty' => false
    ),
    'api_user_id' => array(
      'rule' => 'notBlank',
      'required' => false,
      'allowEmpty' => true
    )
  );
  
  /**
   * Expose menu items.
   * 
   * @ since COmanage Registry v3.3.0
   * @ return Array with menu location type as key and array of labels, controllers, actions as values.
   */
  
  public function cmPluginMenus() {
    return array();
  }
  
  /**
   * Actions to take before a save operation is executed.
   *
   * @since  COmanage Registry v3.3.0
   */

  public function beforeSave($options = array()) {
    if(!empty($this->data['ApiSource']['sor_label'])) {
      // Make sure sor_label isn't already in use in this CO
      
      $coId = $this->OrgIdentitySource->field('co_id', array('OrgIdentitySource.id' => 
                                                             $this->data['ApiSource']['org_identity_source_id']));
      
      $args = array();
      $args['joins'][0]['table'] = 'org_identity_sources';
      $args['joins'][0]['alias'] = 'OrgIdentitySource';
      $args['joins'][0]['type'] = 'INNER';
      $args['joins'][0]['conditions'][0] = 'OrgIdentitySource.id=ApiSource.org_identity_source_id';
      $args['conditions']['OrgIdentitySource.co_id'] = $coId;
      $args['conditions']['ApiSource.sor_label'] = $this->data['ApiSource']['sor_label'];
      // We don't want to test against our own record
      $args['conditions']['ApiSource.id NOT'] = $this->data['ApiSource']['id'];
      $args['contain'] = false;
      
      $recs = $this->find('count', $args);
      
      if($recs > 0) {
        throw new InvalidArgumentException(_txt('er.apisource.label.inuse', array($this->data['ApiSource']['sor_label'])));
      }
    }
    
    return true;
  }
}
