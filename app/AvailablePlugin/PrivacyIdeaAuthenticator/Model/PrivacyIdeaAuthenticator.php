<?php
/**
 * COmanage Registry Privacy IDEA Authenticator Model
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

App::uses("AuthenticatorBackend", "Model");
App::uses("PrivacyIdea", "PrivacyIdeaAuthenticator.Model");

class PrivacyIdeaAuthenticator extends AuthenticatorBackend {
  // Define class name for cake
  public $name = "PrivacyIdeaAuthenticator";

  // Required by COmanage Plugins
  public $cmPluginType = "authenticator";
	
	// Add behaviors
  public $actsAs = array('Containable');
	
  // Document foreign keys
  public $cmPluginHasMany = array(
    "CoPerson" => array("TotpToken")
	);
	
	// Association rules from this model to other models
	public $belongsTo = array(
		"Authenticator",
    "Server",
    "ValidationServer" => array(
      'className' => 'Server',
      'foreignKey' => 'validation_server_id'
    )
	);
	
	public $hasMany = array(
		"PrivacyIdeaAuthenticator.TotpToken"
	);
	
  // Default display field for cake generated views
  public $displayField = "realm";
	
  // Request HTTP servers
  public $cmServerType = ServerEnum::HttpServer;
  
  // Validation rules for table elements
  public $validate = array(
    'authenticator_id' => array(
      'rule' => 'numeric',
      'required' => true,
			'allowEmpty' => false
		),
    'server_id' => array(
      'rule' => 'numeric',
      'required' => true,
			'allowEmpty' => false
		),
    'validation_server_id' => array(
      'rule' => 'numeric',
      'required' => false,
			'allowEmpty' => true
		),
    'realm' => array(
      'rule' => array('validateInput'),
      'required' => true,
			'allowEmpty' => false
    ),
    'token_type' => array(
      'rule' => array('inList', array(PrivacyIDEATokenTypeEnum::TOTP)),
      'required' => true,
      'allowEmpty' => false
    ),
		'identifier_type' => array(
      'content' => array(
        'rule' => array('validateExtendedType',
                        array('attribute' => 'Identifier.type',
                              'default' => array(IdentifierEnum::ePPN,
                                                 IdentifierEnum::ePTID,
                                                 IdentifierEnum::Mail,
                                                 IdentifierEnum::OpenID,
                                                 IdentifierEnum::UID))),
  			'required' => true,
  			'allowEmpty' => false
      )
		)
	);
	
	// Do we support multiple authenticators per instantiation?
	public $multiple = true;
  
  /**
   * Expose menu items.
   * 
   * @since  COmanage Registry v3.1.0
   * @return Array with menu location type as key and array of labels, controllers, actions as values.
   */
	
  public function cmPluginMenus() {
  	return array();
  }
	
	/**
   * Obtain current data suitable for passing to manage() and provisioners.
   *
   * @since  COmanage Registry v4.0.0
   * @param  integer $id				 Authenticator ID
   * @param  integer $backendId  Authenticator Backend ID
   * @param  integer $coPersonId CO Person ID
   * @return Array As returned by find
   * @throws RuntimeException
	 */

	public function current($id, $backendId, $coPersonId) {
    $args = array();
    $args['conditions']['TotpToken.privacy_idea_authenticator_id'] = $backendId;
    $args['conditions']['TotpToken.co_person_id'] = $coPersonId;
    $args['contain'] = false;
    
    return $this->TotpToken->find('all', $args);
	}
  
  /**
   * Perform backend specific actions on a lock operation.
   *
   * @since  COmanage Registry v4.0.0
   * @param  integer $id         Authenticator ID
   * @param  integer $coPersonId CO Person ID
   * @return Boolean             true on success
   * @throws RuntimeException
   */
  
  public function lock($id, $coPersonId) {
    $args = array();
    $args['conditions']['PrivacyIdeaAuthenticator.authenticator_id'] = $id;
    $args['contain'] = false;
    
    $piAuthenticator = $this->find('first', $args);
    
    if(empty($piAuthenticator)) {
      throw new InvalidArgumentException(_txt('er.notfound', array('ct.authenticators.1', $id)));
    }
    
    $PrivacyIdea = new PrivacyIdea();
    $PrivacyIdea->manageLock($piAuthenticator['PrivacyIdeaAuthenticator'], $coPersonId);
    
    return true;
  }
	
	/**
	 * Reset Authenticator data for a CO Person.
	 *
	 * @since  COmanage Registry v3.1.0
	 * @param  integer $coPersonId			CO Person ID
	 * @param  integer $actorCoPersonId Actor CO Person ID
	 * @return boolean true on success
	 */
  
  public function reset($coPersonId, $actorCoPersonId) {
		// It's not immediately obvious what we should do on a reset... so for now
    // we return false until we have better requirements.
		return false;
	}
	
	/**
	 * Obtain the current Authenticator status for a CO Person.
	 *
	 * @since  COmanage Registry v4.0.0
	 * @param  integer $coPersonId			CO Person ID
	 * @return Array Array with values
	 * 							 status: AuthenticatorStatusEnum
	 * 							 comment: Human readable string, visible to the CO Person
	 */
	
	public function status($coPersonId) {
    // We can have more than one Authenticator, but only of the type token_type.
    // For now, we only work with TotpTokens.
    
    $status = AuthenticatorStatusEnum::NotSet;
    $comment = _txt('fd.set.not');
    
    $args = array();
    $args['conditions']['TotpToken.co_person_id'] = $coPersonId;
    $args['contain'] = false;
    
    $tokens = $this->TotpToken->find('all', $args);
    
    if(count($tokens) > 0) {
      $confirmed = Hash::extract($tokens, '{n}.TotpToken[confirmed=true]');
      
      $status = AuthenticatorStatusEnum::Active;
      $comment = _txt('pl.privacyideaauthenticator.status', array(count($tokens), count($confirmed)));
    }
		
		return array(
			'status' => $status,
			'comment' => $comment
		);
	}
  
  /**
   * Perform backend specific actions on an unlock operation.
   *
   * @since  COmanage Registry v4.0.0
   * @param  integer $id         Authenticator ID
   * @param  integer $coPersonId CO Person ID
   * @return Boolean             true on success
   * @throws RuntimeException
   */
  
  public function unlock($id, $coPersonId) {
    $args = array();
    $args['conditions']['PrivacyIdeaAuthenticator.authenticator_id'] = $id;
    $args['contain'] = false;
    
    $piAuthenticator = $this->find('first', $args);
    
    if(empty($piAuthenticator)) {
      throw new InvalidArgumentException(_txt('er.notfound', array('ct.authenticators.1', $id)));
    }
    
    $PrivacyIdea = new PrivacyIdea();
    $PrivacyIdea->manageLock($piAuthenticator['PrivacyIdeaAuthenticator'], $coPersonId, true);
    
    return true;
  }
}
