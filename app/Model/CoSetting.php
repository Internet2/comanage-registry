<?php
/**
 * COmanage Registry CO Setting Model
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
 * @since         COmanage Registry v0.9.1
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

class CoSetting extends AppModel {
  // Define class name for cake
  public $name = "CoSetting";
  
  // Current schema version for API
  public $version = "1.0";
  
  // Association rules from this model to other models
  public $belongsTo = array(
    "Co",
    "SponsorCoGroup" => array(
      'className' => 'CoGroup',
      'foreignKey' => 'sponsor_co_group_id'
    ),
    "CoPipeline" => array(
      'foreignKey' => 'default_co_pipeline_id'
    ),
    "CoDashboard",
    "CoTheme"
  );
  
  // Default display field for cake generated views
  public $displayField = "co_id";
  
  public $actsAs = array('Containable');
  
  // Validation rules for table elements
  public $validate = array(
    'co_id' => array(
      'rule' => 'numeric',
      'required' => true,
      'allowEmpty' => false,
      'message' => 'A CO ID must be provided'
    ),
    'disable_expiration' => array(
      'rule' => 'boolean',
      'required' => false,
      'allowEmpty' => true
    ),
    'disable_ois_sync' => array(
      'rule' => 'boolean',
      'required' => false,
      'allowEmpty' => true
    ),
    'group_validity_sync_window' => array(
      'rule' => 'numeric',
      'required' => false,
      'allowEmpty' => true
    ),
    'enable_normalization' => array(
      'rule' => 'boolean',
      'required' => false,
      'allowEmpty' => true
    ),
    'enable_nsf_demo' => array(
      'rule' => 'boolean',
      'required' => false,
      'allowEmpty' => true
    ),
    'invitation_validity' => array(
      'rule' => 'numeric',
      'required' => false,
      'allowEmpty' => true
    ),
    'permitted_fields_name' => array(
      'rule' => '/.*/',
      'required' => false,
      'allowEmpty' => true
    ),
    'required_fields_addr' => array(
      'rule' => '/.*/',
      'required' => false,
      'allowEmpty' => true
    ),
    'required_fields_name' => array(
      'rule' => '/.*/',
      'required' => false,
      'allowEmpty' => true
    ),
    't_and_c_login' => array(
      'rule' => array('inList',
                      array(TAndCLoginModeEnum::NotEnforced,
                            TAndCLoginModeEnum::RegistryLogin)),
                            // DisableAllServices not currently supported (CO-928)
      'required' => false,
      'allowEmpty' => true
    ),
    'sponsor_eligibility' => array(
      'rule' => array('inList',
                      array(SponsorEligibilityEnum::CoAdmin,
                            SponsorEligibilityEnum::CoGroupMember,
                            SponsorEligibilityEnum::CoOrCouAdmin,
                            SponsorEligibilityEnum::CoPerson,
                            SponsorEligibilityEnum::None)),
      'required' => false,
      'allowEmpty' => true
    ),
    'sponsor_co_group_id' => array(
      'rule' => 'numeric',
      'required' => false,
      'allowEmpty' => true
    ),
    'default_co_pipeline_id' => array(
      'rule' => 'numeric',
      'required' => false,
      'allowEmpty' => true
    ),
    'elect_strategy_primary_name' => array(
      'rule' => array('inList',
                      array(ElectStrategyEnum::FIFO,
                            ElectStrategyEnum::Manual)),
      'required' => false,
      'allowEmpty' => true
    )
  );
  
  // Default values for each setting
  
  protected $defaultSettings = array(
    'disable_expiration'         => false,
    'disable_ois_sync'           => false,
    'enable_normalization'       => true,
    'enable_nsf_demo'            => false,
    'group_validity_sync_window' => DEF_GROUP_SYNC_WINDOW,
    'invitation_validity'        => DEF_INV_VALIDITY,
    'permitted_fields_name'      => PermittedNameFieldsEnum::HGMFS,
    'required_fields_addr'       => RequiredAddressFieldsEnum::Street,
    'required_fields_name'       => RequiredNameFieldsEnum::Given,
    'sponsor_co_group_id'        => null,
    'sponsor_eligibility'        => SponsorEligibilityEnum::CoOrCouAdmin,
    't_and_c_login_mode'         => TAndCLoginModeEnum::NotEnforced
  );
  
  /**
   * Create a CO Settings entry for the specified CO, populated with default values.
   *
   * @since  COmanage Registry v0.9.4
   * @param  Integer $coId CO ID
   * @return Integer CoSetting ID
   * @throws RuntimeException
   */
  
  public function addDefaults($coId) {
    // We don't check that a row doesn't exist already for $coId... that should have
    // been done before we were called. But even if it wasn't, the database UNIQUE
    // constraint will prevent the row from being added.
    
    $c = array();
    $c['CoSetting'] = $this->defaultSettings;
    $c['CoSetting']['co_id'] = $coId;
    
    try {
      $this->save($c);
    }
    catch(Exception $e) {
      throw new RuntimeException(_txt('er.db.save-a', array($e->getMessage())));
    }
    
    return $this->id;
  }
  
  /**
   * Determine if Expirations are enabled for the specified CO.
   *
   * @since  COmanage Registry v0.9.2
   * @param  integer $coId CO ID
   * @return boolean True if enabled, false otherwise
   */
  
  public function expirationEnabled($coId) {
    // Note we flip the value. The data model specifies "disabled" so that
    // the default (ie: no value present in the table) is enabled.
    return !$this->lookupValue($coId, 'disable_expiration');
  }
  
  /**
   * Determine the current configuration for CO Group Membership validity "look back" window.
   *
   * @since  COmanage Registry v3.2.0
   * @param  integer $coId CO ID
   * @return integer Group validity look back window in minutes
   */
  
  public function getGroupValiditySyncWindow($coId) {
    return $this->lookupValue($coId, 'group_validity_sync_window');
  }
  
  /**
   * Get the invitation validity for the specified CO.
   *
   * @since  COmanage Registry v0.9.1
   * @param  integer $coId CO ID
   * @return integer Invitation validity in minutes
   */
  
  public function getInvitationValidity($coId) {
    return $this->lookupValue($coId, 'invitation_validity');
  }
  
  /**
   * Get permitted name fields for the specified CO.
   *
   * @since  COmanage Registry v0.9.4
   * @param  integer $coId CO ID, or null to get default values
   * @return array Array of required fields
   */
  
  public function getPermittedNameFields($coId=null) {
    $ret = explode(",", $this->defaultSettings['permitted_fields_name']);
    
    if($coId) {
      $str = $this->lookupValue($coId, 'permitted_fields_name');
      
      if($str && $str != "") {
        $ret = explode(",", $str);
      }
    }
    
    return $ret;
  }

  /**
   * Get required address fields for the specified CO.
   *
   * @since  COmanage Registry v0.9.1
   * @param  integer $coId CO ID, or null to get default values
   * @return array Array of required fields
   */
  
  public function getRequiredAddressFields($coId=null) {
    $ret = explode(",", $this->defaultSettings['required_fields_addr']);
    
    if($coId) {
      $str = $this->lookupValue($coId, 'required_fields_addr');
      
      if($str && $str != "") {
        $ret = explode(",", $str);
      }
    }
    
    return $ret;
  }
  
  /**
   * Get required name fields for the specified CO.
   *
   * @since  COmanage Registry v0.9.1
   * @param  integer $coId CO ID, or null to get default values
   * @return array Array of required fields
   */
  
  public function getRequiredNameFields($coId=null) {
    $ret = explode(",", $this->defaultSettings['required_fields_name']);
    
    if($coId) {
      $str = $this->lookupValue($coId, 'required_fields_name');
      
      if($str && $str != "") {
        $ret = explode(",", $str);
      }
    }
    
    return $ret;
  }
  
  /**
   * Get sponsor eligibility mode.
   *
   * @since  COmanage Registry v1.0.0
   * @param  integer $coId CO ID
   * @return SponsorEligibilityEnum Sponsor Eligibility Mode
   */
  
  public function getSponsorEligibility($coId) {
    return $this->lookupValue($coId, 'sponsor_eligibility');
  }
  
  /**
   * Get sponsor eligibility group. The results of this call are only valid if
   * sponsor eligibility mode is SponsorEligibilityEnum::CoGroupMember.
   *
   * @since  COmanage Registry v1.0.0
   * @param  integer $coId CO ID
   * @return SponsorEligibilityEnum Sponsor Eligibility Mode
   * @throws InvalidArgumentException
   */
  
  public function getSponsorEligibilityCoGroup($coId) {
    // First check the mode
    $mode = $this->getSponsorEligibility($coId);
    
    if($mode != SponsorEligibilityEnum::CoGroupMember) {
      throw new InvalidArgumentException(_txt('er.setting'));
    }
    
    return $this->lookupValue($coId, 'sponsor_co_group_id');
  }
  
  /**
   * Get the T&C login mode for the specified CO.
   *
   * @since  COmanage Registry v0.9.1
   * @param  integer $coId CO ID
   * @return TAndCLoginModeEnum T&C Login Mode
   */
  
  public function getTAndCLoginMode($coId) {
    return $this->lookupValue($coId, 't_and_c_login_mode');
  }
  
  /**
   * Obtain a single CO setting.
   *
   * @since  COmanage Registry v0.9.1
   * @param  integer $coId CO ID
   * @param  string  $field Field name to retrieve, corresponding to column/field name
   * @return mixed   Value for setting
   */
  
  protected function lookupValue($coId, $field) {
    // We'll rely on Cake's caching here
    
    $args = array();
    $args['conditions']['CoSetting.co_id'] = $coId;
    $args['contain'] = false;
    
    $s = $this->find('first', $args);
    
    if(isset($s['CoSetting'][$field])) {
      return $s['CoSetting'][$field];
    } else {
      // If not present, return the default value
      return $this->defaultSettings[$field];
    }
  }
  
  /**
   * Determine if Normalizations are enabled for the specified CO.
   *
   * @since  COmanage Registry v0.9.2
   * @param  integer $coId CO ID
   * @return boolean True if enabled, false otherwise
   */
  
  public function normalizationsEnabled($coId) {
    return (boolean)$this->lookupValue($coId, 'enable_normalization');
  }
  
  /**
   * Determine if NSF Demographics are enabled for the specified CO.
   *
   * @since  COmanage Registry v0.9.1
   * @param  integer $coId CO ID
   * @return boolean True if enabled, false otherwise
   */
  
  public function nsfDemgraphicsEnabled($coId) {
    return (boolean)$this->lookupValue($coId, 'enable_nsf_demo');
  }
  
  /**
   * Determine if Org Identity Sync is enabled for the specified CO.
   *
   * @since  COmanage Registry v2.0.0
   * @param  integer $coId CO ID
   * @return boolean True if enabled, false otherwise
   */
  
  public function oisSyncEnabled($coId) {
    // Note we flip the value. The data model specifies "disabled" so that
    // the default (ie: no value present in the table) is enabled.
    return !$this->lookupValue($coId, 'disable_ois_sync');
  }
}
