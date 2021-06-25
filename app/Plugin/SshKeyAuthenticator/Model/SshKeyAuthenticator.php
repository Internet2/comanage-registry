<?php
/**
 * COmanage Registry SSH Key Authenticator Model
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

App::uses("AuthenticatorBackend", "Model");

class SshKeyAuthenticator extends AuthenticatorBackend {
  // Define class name for cake
  public $name = "SshKeyAuthenticator";

  // Required by COmanage Plugins
  public $cmPluginType = "authenticator";
  
  // Add behaviors
  public $actsAs = array('Containable');
  
  // Document foreign keys
  public $cmPluginHasMany = array(
    "CoPerson" => array("SshKey")
  );
  
  // Association rules from this model to other models
  public $belongsTo = array(
    "Authenticator"
  );
  
  public $hasMany = array(
    "SshKeyAuthenticator.SshKey"
  );
  
  // Default display field for cake generated views
  public $displayField = "authenticator_id";
  
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
   * @ since COmanage Registry v3.3.0
   * @ return Array with menu location type as key and array of labels, controllers, actions as values.
   */
  
  public function cmPluginMenus() {
    return array();
  }
  
  /**
   * Manage Authenticator data, as submitted from the view.
   *
   * @since  COmanage Registry v3.3.0
   * @param  Array   $data            Array of Authenticator data submitted from the view
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
   * @since  COmanage Registry v3.3.0
   * @param  integer $coPersonId      CO Person ID
   * @param  integer $actorCoPersonId Actor CO Person ID
   * @return boolean true on success
   */
  
  public function reset($coPersonId, $actorCoPersonId) {
    throw new RuntimeException('NOT IMPLEMENTED');
  }
  
  /**
   * Obtain the current Authenticator status for a CO Person.
   *
   * @since  COmanage Registry v3.3.0
   * @param  integer $coPersonId      CO Person ID
   * @return Array Array with values
   *               status: AuthenticatorStatusEnum
   *               comment: Human readable string, visible to the CO Person
   */
  
  public function status($coPersonId) {
    // Are there any SSH Keys for this person?
    
    $args = array();
    $args['conditions']['SshKey.ssh_key_authenticator_id'] = $this->pluginCfg['SshKeyAuthenticator']['id'];
    $args['conditions']['SshKey.co_person_id'] = $coPersonId;
    $args['contain'] = false;
    
    $keys = $this->SshKey->find('all', $args);
    
    if(count($keys) > 0) {
      return array(
        'status' => AuthenticatorStatusEnum::Active,
        'comment' => _txt('pl.sshkeyauthenticator.registered', array(count($keys)))
      );
    }
    
    return array(
      'status' => AuthenticatorStatusEnum::NotSet,
      'comment' => _txt('fd.set.not')
    );
  }
  
  /**
   * Perform SshKey model upgrade steps for version 3.3.0.
   * This function should only be called by UpgradeVersionShell.
   *
   * @since  COmanage Registry v3.3.0
   * @param  Integer $coId    CO ID
   */

  public function _ug330($coId) {
    // First see if there are any SSH Keys associated with people in this CO
    
    $args = array();
    $args['joins'][0]['table'] = 'co_people';
    $args['joins'][0]['alias'] = 'CoPerson';
    $args['joins'][0]['type'] = 'INNER';
    $args['joins'][0]['conditions'][0] = 'CoPerson.id=SshKey.co_person_id';
    $args['conditions']['CoPerson.co_id'] = $coId;
    $args['contain'] = false;
    
    $keys = $this->SshKey->find('list', $args);
    
    if(count($keys) > 0) {
      // Instantiate an SSH Key Authenticator, but only if there are existing
      // SSH Keys.
      
      $this->bindModel(
        array('belongsTo' => array('Authenticator'))
      );
      
      $this->Authenticator->bindModel(
        array('hasMany' => array('SshKeyAuthenticator'))
      );
      
      $authenticator = array(
        'Authenticator' => array(
          'co_id'       => $coId,
          'description' => _txt('ct.ssh_key_authenticators.1'),
          'plugin'      => 'SshKeyAuthenticator',
          'status'      => SuspendableStatusEnum::Active
        ),
        'SshKeyAuthenticator' => array()
      );
      
      if($this->Authenticator->save($authenticator)) {
        $sshauthenticator = array(
          'SshKeyAuthenticator' => array(
            'authenticator_id' => $this->Authenticator->id
          )
        );
        
        $this->save($sshauthenticator);
      } else {
        throw new RuntimeException(_txt('er.db.save-a', array('Authenticator')));
      }
      
      $skaid = $this->id;
      
      // This won't update changelog records
      $this->SshKey->updateAll(
        array('SshKey.ssh_key_authenticator_id' => $skaid),
        array('SshKey.id' => array_keys($keys))
      );
      
      // While we're mucking around, fix the internal enums so they're consistent
      // with the SSH key file labels. Confusingly, we have to manually quote the
      // new value (since it's a string), but not the old one.
      
      $this->SshKey->updateAll(
        array('SshKey.type' => "'ssh-dss'"),
        array('SshKey.type' => "DSA")
      );
      
      $this->SshKey->updateAll(
        array('SshKey.type' => "'ssh-rsa'"),
        array('SshKey.type' => "RSA")
      );
      
      $this->SshKey->updateAll(
        array('SshKey.type' => "'ssh-rsa1'"),
        array('SshKey.type' => "RSA1")
      );
      
      // These next were introduced recently enough that they may not even be in use
      $this->SshKey->updateAll(
        array('SshKey.type' => "'ecdsa-sha2-nistp256'"),
        array('SshKey.type' => "ECDSA")
      );

      $this->SshKey->updateAll(
        array('SshKey.type' => "'ecdsa-sha2-nistp384'"),
        array('SshKey.type' => "ECDSA384")
      );

      $this->SshKey->updateAll(
        array('SshKey.type' => "'ecdsa-sha2-nistp521'"),
        array('SshKey.type' => "ECDSA521")
      );
      
      $this->SshKey->updateAll(
        array('SshKey.type' => "'ssh-ed25519'"),
        array('SshKey.type' => "ED25519")
      );
    }
  }
}
