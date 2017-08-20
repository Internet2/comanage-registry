<?php
/**
 * COmanage Registry Salesforce OrgIdentitySource Model
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
 * @since         COmanage Registry v3.1.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

class SalesforceSource extends AppModel {
  // Required by COmanage Plugins
  public $cmPluginType = "orgidsource";
  
  // Document foreign keys
  public $cmPluginHasMany = array();
  
  // Association rules from this model to other models
  public $belongsTo = array("OrgIdentitySource");
  
  // Default display field for cake generated views
  public $displayField = "serverurl";
  
  // Validation rules for table elements
  public $validate = array(
    'org_identity_source_id' => array(
      'rule' => 'numeric',
      'required' => true,
      'allowEmpty' => false
    ),
    'serverurl' => array(
      'rule' => array('url', true),
      'required' => true,
      'allowEmpty' => false
    ),
    'clientid' => array(
      'rule' => 'notBlank',
      'required' => true,
      'allowEmpty' => false
    ),
    'client_secret' => array(
      'rule' => 'notBlank',
      'required' => true,
      'allowEmpty' => false
    ),
    'refresh_token' => array(
      'rule' => 'notBlank',
      'required' => false,
      'allowEmpty' => true
    ),
    'search_contacts' => array(
      'rule' => 'boolean'
    ),
    'search_users' => array(
      'rule' => 'boolean'
    ),
    'custom_objects' => array(
      'rule' => 'notBlank',
      'required' => false,
      'allowEmpty' => true
    ),
    'access_token' => array(
      'rule' => 'notBlank',
      'required' => false,
      'allowEmpty' => true
    ),
    'instance_url' => array(
      'rule' => array('url', true),
      'required' => false,
      'allowEmpty' => true
    ),
    'groupable_attrs' => array(
      'rule' => 'notBlank',
      'required' => false,
      'allowEmpty' => true
    )
  );
  
  /**
   * Actions to take before a save operation is executed.
   *
   * @since  COmanage Registry v3.1.0
   * @return Boolean
   */

  public function beforeSave($options = array()) {
    // If there is as access or refresh token, see if any "critical"
    // element has changed, and if so clear the token.
    
    if(!empty($this->data['SalesforceSource']['serverurl'])) {
      // Check for serverurl, since saveField() won't provide most attributes
      // (and is used to update the access token anyway, which we don't want
      // to do this check for).
      
      // checkWriteFollowups will warn after save if no oauth token is set.

      $args = array();
      $args['conditions']['SalesforceSource.id'] = $this->data['SalesforceSource']['id'];
      $args['contain'] = false;
      
      $curdata = $this->find('first', $args);
      
      if(!empty($curdata['SalesforceSource']['access_token'])
         || !empty($curdata['SalesforceSource']['refresh_token'])) {
        if(($this->data['SalesforceSource']['serverurl']
            != $curdata['SalesforceSource']['serverurl'])
           ||
           ($this->data['SalesforceSource']['clientid']
            != $curdata['SalesforceSource']['clientid'])
           ||
           ($this->data['SalesforceSource']['client_secret']
            != $curdata['SalesforceSource']['client_secret'])) {
          // Reset the tokens
          $this->data['SalesforceSource']['access_token'] = null;
          $this->data['SalesforceSource']['refresh_token'] = null;
        }
      }
    }
  
    return true;
  }
  
  /**
   * Expose menu items.
   * 
   * @ since COmanage Registry v2.0.0
   * @ return Array with menu location type as key and array of labels, controllers, actions as values.
   */
  
  public function cmPluginMenus() {
    return array();
  }
}
