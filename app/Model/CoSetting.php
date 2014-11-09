<?php
/**
 * COmanage Registry CO Setting Model
 *
 * Copyright (C) 2014 University Corporation for Advanced Internet Development, Inc.
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
 * @copyright     Copyright (C) 2014 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.9.1
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */

class CoSetting extends AppModel {
  // Define class name for cake
  public $name = "CoSetting";
  
  // Current schema version for API
  public $version = "1.0";
  
  // Association rules from this model to other models
  public $belongsTo = array("Co");
  
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
    'enable_expiration' => array(
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
    )
  );
  
  /**
   * Determine if Expirations are enabled for the specified CO.
   *
   * @since  COmanage Registry v0.9.2
   * @param  integer $coId CO ID
   * @return boolean True if enabled, false otherwise
   */
  
  public function expirationEnabled($coId) {
    $ret = true;
    
    try {
      // Note we flip the value. The data model specifies "disable" so that
      // the default (ie: no value present in the table) is enabled.
      $ret = !$this->lookupValue($coId, 'disable_expiration');
    }
    catch(UnderflowException $e) {
      // Use default value
    }
    
    return (boolean)$ret;
  }
  
  /**
   * Get the invitation validity for the specified CO.
   *
   * @since  COmanage Registry v0.9.1
   * @param  integer $coId CO ID
   * @return integer Invitation validity in minutes
   */
  
  public function getInvitationValidity($coId) {
    global $def_inv_validity;
    $ret = $def_inv_validity;
    
    try {
      $ret = $this->lookupValue($coId, 'invitation_validity');
    }
    catch(UnderflowException $e) {
      // Use default value
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
    // It would probably be better to get this from the model somehow
    $ret = explode(",", RequiredAddressFieldsEnum::Line1);
    
    if($coId) {
      try {
        $str = $this->lookupValue($coId, 'required_fields_addr');
        
        if($str && $str != "") {
          $ret = explode(",", $str);
        }
      }
      catch(UnderflowException $e) {
        // Use default value
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
    // It would probably be better to get this from the model somehow
    $ret = explode(",", RequiredNameFieldsEnum::Given);
    
    if($coId) {
      try {
        $str = $this->lookupValue($coId, 'required_fields_name');
        
        if($str && $str != "") {
          $ret = explode(",", $str);
        }
      }
      catch(UnderflowException $e) {
        // Use default value
      }
    }
    
    return $ret;
  }

  /**
   * Get the T&C login mode for the specified CO.
   *
   * @since  COmanage Registry v0.9.1
   * @param  integer $coId CO ID
   * @return TAndCLoginModeEnum T&C Login Mode
   */
  
  public function getTAndCLoginMode($coId) {
    $ret = TAndCLoginModeEnum::NotEnforced;
    
    try {
      $ret = $this->lookupValue($coId, 't_and_c_login_mode');
    }
    catch(UnderflowException $e) {
      // Use default value
    }
    
    return $ret;
  }
  
  /**
   * Obtain a single CO setting.
   *
   * @since  COmanage Registry v0.9.1
   * @param  integer $coId CO ID
   * @param  string  $field Field name to retrieve, corresponding to column/field name
   * @return mixed   Value for setting
   * @throws UnderflowException (if no row found)
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
      // If not present throw error to distinguish from null/0/false/etc
      throw new UnderflowException($coId);
    }
  }
  
  /**
   * Determine if NSF Demographics are enabled for the specified CO.
   *
   * @since  COmanage Registry v0.9.1
   * @param  integer $coId CO ID
   * @return boolean True if enabled, false otherwise
   */
  
  public function nsfDemgraphicsEnabled($coId) {
    $ret = false;
    
    try {
      $ret = $this->lookupValue($coId, 'enable_nsf_demo');
    }
    catch(UnderflowException $e) {
      // Use default value
    }
    
    return (boolean)$ret;
  }
}
