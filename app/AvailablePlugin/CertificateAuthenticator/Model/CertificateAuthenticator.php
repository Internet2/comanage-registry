<?php
/**
 * COmanage Registry Certificate Authenticator Model
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

class CertificateAuthenticator extends AuthenticatorBackend {
  // Define class name for cake
  public $name = "CertificateAuthenticator";

  // Required by COmanage Plugins
  public $cmPluginType = "authenticator";
	
	// Add behaviors
  public $actsAs = array('Containable');
	
  // Document foreign keys
  public $cmPluginHasMany = array(
    "CoPerson" => array("Certificate")
  );
	
	// Association rules from this model to other models
	public $belongsTo = array(
		"Authenticator"
	);
	
	public $hasMany = array(
		"CertificateAuthenticator.Certificate"
	);
	
  // Default display field for cake generated views
  public $displayField = "description";
	
  // Validation rules for table elements
  public $validate = array(
    'authenticator_id' => array(
      'rule' => 'numeric',
      'required' => true,
			'allowEmpty' => false
		)
	);
	
	// Does we support multiple authenticators per instantiation?
	public $multiple = true;
  
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
    // XXX this could probably move to AuthenticatorBackend as a default
		$args = array();
		$args['conditions']['Certificate.certificate_authenticator_id'] = $backendId;
		$args['conditions']['Certificate.co_person_id'] = $coPersonId;
		$args['contain'] = false;
		
		return $this->Certificate->find('all', $args);
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
		throw new RuntimeException('NOT IMPLEMENTED');
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
	  throw new RuntimeException('NOT IMPLEMENTED');
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
		// Are there any certificates for this person?
		
		$args = array();
		$args['conditions']['Certificate.certificate_authenticator_id'] = $this->pluginCfg['CertificateAuthenticator']['id'];
		$args['conditions']['Certificate.co_person_id'] = $coPersonId;
		$args['contain'] = false;
		
		$certs = $this->Certificate->find('all', $args);
		
		if(count($certs) > 0) {
			// XXX we could also check valid from/through dates while we're here
			
			return array(
				'status' => AuthenticatorStatusEnum::Active,
				'comment' => _txt('pl.certificateauthenticator.registered', array(count($certs)))
			);
		}
		
		return array(
			'status' => AuthenticatorStatusEnum::NotSet,
			'comment' => _txt('fd.set.not')
		);
	}
}
