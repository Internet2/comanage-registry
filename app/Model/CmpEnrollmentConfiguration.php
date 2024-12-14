<?php
/**
 * COmanage Registry CMP Enrollment Configuration Model
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
 * @since         COmanage Registry v0.3
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

class CmpEnrollmentConfiguration extends AppModel {
  // Define class name for cake
  public $name = "CmpEnrollmentConfiguration";
  
  // Current schema version for API
  public $version = "1.0";
  
  // Add behaviors
  public $actsAs = array('Containable');
  
  // Association rules from this model to other models
  public $hasMany = array("CmpEnrollmentAttribute" =>   // A CMP Enrollment Configuration has many CMP Enrollment Attributes
                       array('dependent' => true));
  
  // Default display field for cake generated views
  public $displayField = "name";
  
  // Default ordering for find operations
  public $order = array("name");
  
  // Validation rules for table elements
  public $validate = array(
    'name' => array(
      'rule' => 'notBlank',
      'required' => true,
      'message' => 'A name must be provided'
    ),
    'attrs_from_ldap' => array(
      'rule' => array('boolean')
    ),
    'attrs_from_saml' => array(
      'rule' => array('boolean')
    ),
    'status' => array(
      'rule' => array('inList', array(StatusEnum::Active,
                                      StatusEnum::Suspended))
    ),
    'pool_org_identities' => array(
      'rule' => array('boolean')
    ),
    'eds_help_url' => array(
      // We can't set this to 'url' because url validation doesn't understand mailto:
      //'rule' => array('url', true),
      'rule' => 'notBlank',
      'required' => false,
      'allowEmpty' => true
    ),
    'eds_preferred_idps' => array(
      'rule' => 'notBlank',
      'required' => false,
      'allowEmpty' => true
    ),
    'eds_hidden_idps' => array(
      'rule' => 'notBlank',
      'required' => false,
      'allowEmpty' => true
    ),
    'redirect_on_logout' => array(
      'rule' => array('url', true),
      'required' => false,
      'allowEmpty' => true
    ),
    'app_base' => array(
      'rule' => 'notBlank',
      'required' => true,
      'message' => 'The webroot location must be provided'
    ),
    'authn_events_record_apiusers' => array(
      'rule' => 'boolean',
      'required' => false,
      'allowEmpty' => true
    ),
    'env_mfa' => array(
      'rule' => 'notBlank',
      'required' => false,
      'allowEmpty' => true
    ),
    'env_mfa_value' => array(
      'rule' => 'notBlank',
      'required' => false,
      'allowEmpty' => true
    )
  );
  
  /**
   * Create the default CMP Enrollment Configuration entry.
   *
   * @since  COmanage Registry v0.9.3
   * @param Boolean $pool Whether to pool org identities
   * @return integer ID of new CMP Enrollment Configuration
   * @throws RuntimeException
   */
  
  public function createDefault($pool = false) {
    $ef['CmpEnrollmentConfiguration'] = array(
      'name'                => 'CMP Enrollment Configuration',
      'attrs_from_coef'     => true,
      'attrs_from_env'      => false,
      'attrs_from_ldap'     => false,
      'attrs_from_saml'     => false,
      'pool_org_identities' => $pool,
      'status'              => StatusEnum::Active,
      'redirect_on_logout'  => null,
      'app_base'            => '/registry/',
      'authn_events_record_apiusers'  => true,
      'env_mfa'             => null,
      'env_mfa_value'       => null
    );
    
    if($this->save($ef)) {
      return $this->id;
    } else {
      throw new RuntimeException(_txt('er.db.save'));
    }
  }
  
  /**
   * Obtain the configurations for the Embedded Discovery Service
   *
   * @since  COmanage Registry v1.0.0
   * @return Array An array of CmpEnrollmentAttributes if enabled
   */
  
  public function edsConfiguration() {
    $args = array();
    $args['conditions']['CmpEnrollmentConfiguration.name'] = 'CMP Enrollment Configuration';
    $args['conditions']['CmpEnrollmentConfiguration.status'] = StatusEnum::Active;
    $args['fields'] = array('eds_help_url', 'eds_preferred_idps', 'eds_hidden_idps');
    $args['contain'] = false;
    
    return $this->find('first', $args);
  }


  /**
   * Get the App.base location
   *
   * @since  COmanage Registry v4.0.0
   * @return string App.base location
   */

  public function getAppBase() {
    $args = array();
    $args['conditions']['CmpEnrollmentConfiguration.name'] = 'CMP Enrollment Configuration';
    $args['conditions']['CmpEnrollmentConfiguration.status'] = StatusEnum::Active;
    $args['fields'] = array('CmpEnrollmentConfiguration.app_base');
    $args['contain'] = false;

    $cmp = $this->find('first', $args);

    return isset($cmp["CmpEnrollmentConfiguration"]["app_base"]) ? $cmp["CmpEnrollmentConfiguration"]["app_base"] : "";
  }

  /**
   * Are we allowed to keep record of the API User's authentication events
   *
   * @return boolean True if enabled, false otherwise
   * @since  COmanage Registry v4.4.0
   */

  public function getAuthnEventsRecordApiUsers() {
    $args = array();
    $args['conditions']['CmpEnrollmentConfiguration.name'] = 'CMP Enrollment Configuration';
    $args['conditions']['CmpEnrollmentConfiguration.status'] = StatusEnum::Active;
    $args['fields'] = array('CmpEnrollmentConfiguration.authn_events_record_apiusers');
    $args['contain'] = false;

    $cmp = $this->find('first', $args);

    return $cmp['CmpEnrollmentConfiguration']['authn_events_record_apiusers'] ?? '';
  }


  /**
   * Get the Logout Redirect URL specified in the Platform CO.
   *
   * @since  COmanage Registry v4.0.0
   * @return string Logout Redirect URL
   */

  public function getLogoutRedirectUrl() {
    $args = array();
    $args['conditions']['CmpEnrollmentConfiguration.name'] = 'CMP Enrollment Configuration';
    $args['conditions']['CmpEnrollmentConfiguration.status'] = StatusEnum::Active;
    $args['fields'] = array('CmpEnrollmentConfiguration.redirect_on_logout');
    $args['contain'] = false;

    $cmp = $this->find('first', $args);

    return isset($cmp["CmpEnrollmentConfiguration"]["redirect_on_logout"]) ? $cmp["CmpEnrollmentConfiguration"]["redirect_on_logout"] : "";
  }

  /**
   * Determine if MFA is required for this platform, and if so how to check.
   * 
   * @since  COmanage Registry v4.5.0
   * @return array      'var': Env Variable to check, 'value': Value that Env Variable should match
   */

  public function getMfaEnv() {
    $ret = array();

    $args = array();
    $args['conditions']['CmpEnrollmentConfiguration.name'] = 'CMP Enrollment Configuration';
    $args['conditions']['CmpEnrollmentConfiguration.status'] = StatusEnum::Active;
    $args['fields'] = array('CmpEnrollmentConfiguration.env_mfa', 'CmpEnrollmentConfiguration.env_mfa_value');
    $args['contain'] = false;

    $cmp = $this->find('first', $args);

    if(!empty($cmp['CmpEnrollmentConfiguration']['env_mfa'])
       && !empty($cmp['CmpEnrollmentConfiguration']['env_mfa_value'])) {
      $ret['var'] = $cmp['CmpEnrollmentConfiguration']['env_mfa'];
      $ret['value'] = !empty($cmp['CmpEnrollmentConfiguration']['env_mfa_value']) 
                      ? $cmp['CmpEnrollmentConfiguration']['env_mfa_value']
                      : "yes";
    }

    return $ret;
  }

  /**
   * Determine if enrollment attribute values may be obtained from the environment,
   * and if so which ones.
   *
   * @since  COmanage Registry v0.8.2
   * @return mixed An array of CmpEnrollmentAttributes if enabled, false otherwise
   */
  
  public function enrollmentAttributesFromEnv() {
    $args = array();
    $args['conditions']['CmpEnrollmentConfiguration.name'] = 'CMP Enrollment Configuration';
    $args['conditions']['CmpEnrollmentConfiguration.status'] = StatusEnum::Active;
    $args['contain'][] = 'CmpEnrollmentAttribute';
    
    $r = $this->find('first', $args);
    
    if(isset($r['CmpEnrollmentConfiguration']['attrs_from_env'])
       && $r['CmpEnrollmentConfiguration']['attrs_from_env']) {
      return $r['CmpEnrollmentAttribute'];
    }
    
    return false;
  }
  
  /**
   * Find the default (ie: active) CMP Enrollment Configuration for this platform.
   * - precondition: Initial setup (performed by select()) has been completed.
   *
   * @since  COmanage Registry v0.3
   * @return Array Of the form returned by find()
   */
  
  public function findDefault() {
    return($this->find('first',
                       array('conditions' =>
                             array('CmpEnrollmentConfiguration.name' => 'CMP Enrollment Configuration',
                                   'CmpEnrollmentConfiguration.status' => StatusEnum::Active))));
  }
  
  /**
   * Determine if organizational identities may be provided by CO enrollment
   * flows in the default (ie: active) CMP Enrollment Configuration for this platform.
   * - precondition: Initial setup (performed by select()) has been completed
   *
   * @since  COmanage Registry v0.5
   * @return boolean True if org identities may be provided by CO enrollment flows, false otherwise
   */
  
  public function orgIdentitiesFromCOEF() {
    $r = $this->find('first',
                     array('conditions' =>
                           array('CmpEnrollmentConfiguration.name' => 'CMP Enrollment Configuration',
                                 'CmpEnrollmentConfiguration.status' => StatusEnum::Active),
                           // We don't need to pull attributes, just the configuration
                           'contain' => false,
                           'fields' =>
                           array('CmpEnrollmentConfiguration.attrs_from_coef')));
    
    if(isset($r['CmpEnrollmentConfiguration']['attrs_from_coef'])) {
      return $r['CmpEnrollmentConfiguration']['attrs_from_coef'];
    }
    
    return false;
  }
  
  /**
   * Determine if organizational identities are pooled in the default (ie: active)
   * CMP Enrollment Configuration for this platform.
   * - precondition: Initial setup (performed by select()) has been completed
   *
   * @since  COmanage Registry v0.3
   * @return boolean True if org identities are pooled, false otherwise
   */
  
  public function orgIdentitiesPooled() {
    $r = $this->find('first',
                     array('conditions' =>
                           array('CmpEnrollmentConfiguration.name' => 'CMP Enrollment Configuration',
                                 'CmpEnrollmentConfiguration.status' => StatusEnum::Active),
                           // We don't need to pull attributes, just the configuration
                           'contain' => false,
                           'fields' =>
                           array('CmpEnrollmentConfiguration.pool_org_identities')));
    
    if(isset($r['CmpEnrollmentConfiguration']['pool_org_identities'])) {
      return $r['CmpEnrollmentConfiguration']['pool_org_identities'];
    }
    
    return false;
  }

  /**
   * Reset (clear) the MFA configuration so MFA is no longer required for access.
   * 
   * @since  COmanage Registry v4.5.0
   */

  public function resetMfaEnv() {
    $ret = array();

    $args = array();
    $args['conditions']['CmpEnrollmentConfiguration.name'] = 'CMP Enrollment Configuration';
    $args['conditions']['CmpEnrollmentConfiguration.status'] = StatusEnum::Active;
    $args['contain'] = false;

    $cmp = $this->find('first', $args);

    $cmp['CmpEnrollmentConfiguration']['env_mfa'] = null;
    $cmp['CmpEnrollmentConfiguration']['env_mfa_value'] = null;

    $this->save($cmp);

    return $ret;
  }

  /**
   * Perform CmpEnrollmentConfiguration model upgrade steps for version 4.4.0.
   * This function should only be called by UpgradeVersionShell.
   *
   * @since  COmanage Registry v4.0.0
   */

  public function _ug440() {
    // Set default Authentication Events Recording for API Users

    // We use updateAll here which doesn't fire callbacks (including ChangelogBehavior).
    $this->updateAll(
      array('CmpEnrollmentConfiguration.authn_events_record_apiusers'=> true)
    );
  }
}