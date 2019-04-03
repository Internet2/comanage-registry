<?php
/**
 * COmanage Registry Password Authenticator Model
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

App::uses("AuthenticatorBackend", "Model");

class PasswordAuthenticator extends AuthenticatorBackend {
  // Define class name for cake
  public $name = "PasswordAuthenticator";

  // Required by COmanage Plugins
  public $cmPluginType = "authenticator";
	
	// Add behaviors
  public $actsAs = array('Containable');
	
  // Document foreign keys
  public $cmPluginHasMany = array(
    "CoPerson" => array("Password")
	);
	
	// Association rules from this model to other models
	public $belongsTo = array(
		"Authenticator"
	);
	
	public $hasMany = array(
		"PasswordAuthenticator.Password"
	);
	
  // Default display field for cake generated views
  public $displayField = "description";
	
  // Validation rules for table elements
  public $validate = array(
    'authenticator_id' => array(
      'rule' => 'numeric',
      'required' => true,
			'allowEmpty' => false
		),
		'min_length' => array(
      'rule' => 'numeric',
			'required' => false,
			'allowEmpty' => true
		),
		'max_length' => array(
      'rule' => 'numeric',
			'required' => false,
			'allowEmpty' => true
		),
    'format_crypt_php' => array(
      'rule' => array('boolean'),
      'required' => false,
      'allowEmpty' => true
    ),
    'format_plaintext' => array(
      'rule' => array('boolean'),
      'required' => false,
      'allowEmpty' => true
    ),
    'format_sha1_ldap' => array(
      'rule' => array('boolean'),
      'required' => false,
      'allowEmpty' => true
    )
	);
	
	// Does we support multiple authenticators per instantiation?
	public $multiple = false;
  
  /**
   * Expose menu items.
   * 
   * @ since COmanage Registry v3.1.0
   * @ return Array with menu location type as key and array of labels, controllers, actions as values.
   */
	
  public function cmPluginMenus() {
  	return array();
  }
	
	/**
   * Obtain current data suitable for passing to manage().
   *
   * @since  COmanage Registry v3.1.0
   * @param  integer $id				 Authenticator ID
   * @param  integer $backendId  Authenticator Backend ID
   * @param  integer $coPersonId CO Person ID
   * @return Array As returned by find
   * @throws RuntimeException
	 */
	
	public function current($id, $backendId, $coPersonId) {
		$args = array();
		$args['conditions']['Password.password_authenticator_id'] = $backendId;
		$args['conditions']['Password.co_person_id'] = $coPersonId;
		$args['contain'] = false;
		
		return $this->Password->find('all', $args);
	}
	
	/**
	 * Manage Authenticator data, as submitted from the view.
	 *
	 * @since  COmanage Registry v3.1.0
	 * @param  Array   $data					  Array of Authenticator data submitted from the view
	 * @param  integer $actorCoPersonId Actor CO Person ID
	 * @return string Human readable (localized) result comment
	 * @throws InvalidArgumentException
	 * @throws RuntimeException
	 */
	
	public function manage($data, $actorCoPersonId) {
		$minlen = $this->pluginCfg['PasswordAuthenticator']['min_length'] ?: 8;
		$maxlen = $this->pluginCfg['PasswordAuthenticator']['max_length'] ?: 64;
		
		// Check minimum length
		if(strlen($data['Password']['password']) < $minlen) {
			throw new InvalidArgumentException(_txt('er.passwordauthenticator.len.min', array($minlen)));
		}
		
		// Check maximum length
		if(strlen($data['Password']['password']) > $maxlen) {
			throw new InvalidArgumentException(_txt('er.passwordauthenticator.len.max', array($maxlen)));
		}
		
		// Check that passwords match
		if($data['Password']['password'] != $data['Password']['password2']) {
			throw new InvalidArgumentException(_txt('er.passwordauthenticator.match'));
		}
    
    // Make sure we have a CO Person ID to operate over
    if(empty($data['Password']['co_person_id'])) {
      throw new InvalidArgumentException(_txt('er.notprov.id', array(_txt('ct.co_people.1'))));
    }
		
		// First see if there are any existing records
		$this->_begin();
		
		$args = array();
		$args['conditions']['Password.password_authenticator_id'] = $data['Password']['password_authenticator_id'];
		$args['conditions']['Password.co_person_id'] = $data['Password']['co_person_id'];
		$args['conditions']['Password.password_type'] = PasswordEncodingEnum::Crypt;
		$args['contain'] = false;
		
		$currec = $this->Password->find('first', $args);
		
		if(!empty($currec)
			 && $data['Password']['co_person_id'] == $actorCoPersonId) {
			// The current password is required
			
			if(!password_verify($data['Password']['passwordc'], $currec['Password']['password'])) {
				throw new InvalidArgumentException(_txt('er.passwordauthenticator.current'));
			}
		}
    
    // Delete any existing password for the user. We do it this way in case the
    // plugin configuration is changed. We skip callbacks so as to not trigger
    // provisioning (and they aren't required since this table is not Changelog
    // enabled).
    $this->Password->deleteAll(array('Password.co_person_id' => $data['Password']['co_person_id']));
		
		// We'll store one entry per hashing type. We always store CRYPT
		// so we can use the native php routines (which require PHP 5.5+).
    // Enabling SSHA requires PHP 7 for random_bytes.
    
    // We could use something like https://multiformats.io/multihash, but the
    // password_type column basically accomplishes the same thing.
		
    $pData = array();
    
    if(true || $this->pluginCfg['PasswordAuthenticator']['format_crypt_php']) {
      // We use password_hash, which due to various portability issues with crypt
      // is really only useful with password_verify.
      
      $pData[] = array(
        'password_authenticator_id' => $data['Password']['password_authenticator_id'],
				'co_person_id'							=> $data['Password']['co_person_id'],
        'password'									=> password_hash($data['Password']['password'], PASSWORD_DEFAULT),
        'password_type'							=> PasswordEncodingEnum::Crypt
      );
    }
    
    if($this->pluginCfg['PasswordAuthenticator']['format_sha1_ldap']) {
      // Salted SHA1 isn't really a great algorithm (and our salt generation)
      // could probably be better, but OpenLDAP doesn't support a better option
      // out of the box.
      
      $salt = substr(bin2hex(random_bytes(8)),0,4);
      $shapwd = base64_encode(sha1($data['Password']['password'].$salt, true) . $salt);
      
      $pData[] = array(
        'password_authenticator_id' => $data['Password']['password_authenticator_id'],
				'co_person_id'							=> $data['Password']['co_person_id'],
        'password'									=> $shapwd,
        'password_type'							=> PasswordEncodingEnum::SSHA
      );
    }
    
    if($this->pluginCfg['PasswordAuthenticator']['format_plaintext']) {
      // Other than being easily readable by admins, plaintext is arguably not
      // that much less secure than the other supported options...
      
      $pData[] = array(
        'password_authenticator_id' => $data['Password']['password_authenticator_id'],
				'co_person_id'							=> $data['Password']['co_person_id'],
        'password'									=> $data['Password']['password'],
        'password_type'							=> PasswordEncodingEnum::Plain
      );
    }
		
		if(!$this->Password->saveMany($pData)) {
			$this->_rollback();
			throw new RuntimeException(_txt('er.db.save-a', array('Password')));
		}
		
		$comment = _txt('pl.passwordauthenticator.saved',
									  array($this->pluginCfg['Authenticator']['description']));
		
		$this->Authenticator
				 ->Co
				 ->CoPerson
				 ->HistoryRecord->record($data['Password']['co_person_id'],
																 null,
																 null,
																 $actorCoPersonId,
																 ActionEnum::AuthenticatorEdited,
																 $comment);
		
		$this->_commit();
		
		return $comment;
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
    // Perform the reset. We simply delete any authenticators for the specified CO Person.
		
		$args = array();
		$args['conditions']['Password.password_authenticator_id'] = $this->pluginCfg['PasswordAuthenticator']['id'];
		$args['conditions']['Password.co_person_id'] = $coPersonId;
		
		// Note deleteAll will not trigger callbacks by default
		$this->Password->deleteAll($args['conditions']);
    
    // And record some history
		
		$comment = _txt('pl.passwordauthenticator.reset',
									  array($this->pluginCfg['Authenticator']['description']));
		
		$this->Authenticator
				 ->Co
				 ->CoPerson
				 ->HistoryRecord->record($coPersonId,
																 null,
																 null,
																 $actorCoPersonId,
																 ActionEnum::AuthenticatorDeleted,
																 $comment);
		
		// We always return true
		return true;
	}
	
	/**
	 * Obtain the current Authenticator status for a CO Person.
	 *
	 * @since  COmanage Registry v3.1.0
	 * @param  integer $coPersonId			CO Person ID
	 * @return Array Array with values
	 * 							 status: AuthenticatorStatusEnum
	 * 							 comment: Human readable string, visible to the CO Person
	 */
	
	public function status($coPersonId) {
		// Is there a password for this person?
		
		$args = array();
		$args['conditions']['Password.password_authenticator_id'] = $this->pluginCfg['PasswordAuthenticator']['id'];
		$args['conditions']['Password.co_person_id'] = $coPersonId;
    // We constrain to type CRYPT since we require that type
		$args['conditions']['Password.password_type'] = PasswordEncodingEnum::Crypt;
		$args['contain'] = false;
		
		$modtime = $this->Password->field('modified', $args['conditions']);
		
		if($modtime) {
			return array(
				'status' => AuthenticatorStatusEnum::Active,
				// Note we don't currently have access to local timezone setting (see OrgIdentity for example)
				'comment' => _txt('pl.passwordauthenticator.mod', array($modtime))
			);
		}
		
		return array(
			'status' => AuthenticatorStatusEnum::NotSet,
			'comment' => _txt('fd.set.not')
		);
	}
}
