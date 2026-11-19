<?php
/**
 * COmanage Registry SLIP Authenticator Binding Model
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
 * @since         COmanage Registry v4.7.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

class SlipBinding extends AppModel {
  // Add behaviors
  public $actsAs = array('Containable', 'Changelog' => array('priority' => 5));
  
  // Association rules from this model to other models
  public $belongsTo = array(
    "SlipPlugin.SlipProofing",
    "OrgIdentity"
  );

  // Default display field for cake generated views
  public $displayField = "id";
  
  // Validation rules for table elements
  public $validate = array(
    'slip_proofing_id' => array(
      'rule' => 'numeric',
      'required' => true,
      'allowEmpty' => false
    ),
    'org_identity_id' => array(
      'rule' => 'numeric',
      'required' => true,
      'allowEmpty' => false
    ),
    'status' => array(
      'content' => array(
        'rule' => array('inList', array(SlipBindingStatusEnum::Canceled,
                                        SlipBindingStatusEnum::Confirmed,
                                        SlipBindingStatusEnum::NotStarted,
                                        SlipBindingStatusEnum::PendingVerification,
                                        SlipBindingStatusEnum::Revoked)),
        'required' => true,
        'allowEmpty' => false
      )
    ),
    'authenticated_identifier' => array(
      'content' => array(
        'rule' => '/.*/',
        'required' => true,
        'allowEmpty' => false
      )
    ),
    'code' => array(
      'content' => array(
        'rule' => '/.*/',
        'required' => false,
        'allowEmpty' => true
      )
    ),
    'verification_time' => array(
      'content' => array(
        'rule' => 'datetime',
        'required' => false,
        'allowEmpty' => true
      )
    )
  );

  /**
   * Confirm a SLIP Authenticator Binding.
   * 
   * @since  COmanage Registry v4.7.0
   * @param  int      SLIP Binding ID
   * @param  string   Code
   * @throws InvalidArgumentException
   */

  public function confirm($id, $code) {
    // Pull the SLIP Binding request so we can check the status and code

    $args = array();
    $args['conditions'][$this->alias.'.id'] = $id;
    $args['contain'] = false;

    $sid = $this->find('first', $args);

    if(empty($sid)) {
      throw new InvalidArgumentException(_txt('er.slipplugin.bind', array($id)));
    }

    if($sid['SlipBinding']['status'] != SlipBindingStatusEnum::PendingVerification) {
      throw new InvalidArgumentException(_txt('er.slipplugin.bind.status', array(_txt('pl.slipplugin.en.binding', null, $sid['SlipBinding']['status']))));
    }

    // Force the code to be compared as a string, not a number or null (etc)
    if(empty($code) || (string)$code !== (string)$sid['SlipBinding']['code']) {
      throw new InvalidArgumentException(_txt('er.slipplugin.bind.code'));
    }

    // We have a valid code, update the Binding. We can leave the code in place since it now
    // has no value.

    $this->clear();
    $this->id = $id;
    $this->saveField('status', SlipBindingStatusEnum::Confirmed);
    $this->saveField('verification_time', date('Y-m-d H:i:s', time()));
  }

  /**
   * Obtain the CO ID for a record, overriding AppModel behavior.
   *
   * @since  COmanage Registry v4.7.0
   * @param  integer Record to retrieve for
   * @return integer Corresponding CO ID, or NULL if record has no corresponding CO ID
   * @throws InvalidArgumentException
   * @throws RuntimeException
   */

  public function findCoForRecord($id) {
    // SlipBindings refer to SlipProofings which refer to SlipPlugins

    $args = array();
    $args['conditions'][$this->alias.'.id'] = $id;
    $args['contain'] = array('SlipProofing' => array('SlipPlugin'));

    $sid = $this->find('first', $args);

    if(!empty($sid['SlipProofing']['SlipPlugin']['co_id'])) {
      return $sid['SlipProofing']['SlipPlugin']['co_id'];
    } else {
      return parent::findCoForRecord($id);
    }
  }

  /**
   * Generate an Authenticator Binding request for the specified Identifier.
   * 
   * @since  COmanage Registry v4.7.0
   * @param  integer  SLIP Proofing ID
   * @param  string   Identifier
   * @param  integer  Actor CO PersonID
   * @return integer  SLIP Binding ID
   */

  public function request($slipProofingId, $identifier, $actorCoPersonId) {
    // First see if there is an existing Binding request. If there is, we'll generate a new code
    // and update the existing record. Note status() should always return an array, if it
    // doesn't then $identifier is invalid.

    $bindings = $this->status($slipProofingId, $identifier);
    $binding = $bindings[0];

    if(empty($binding['SlipBinding']['authenticated_identifier'])) {
      throw new InvalidArgumentException(_txt('er.slipplugin.request.id'));
    }

    $binding['SlipBinding']['status'] = SlipBindingStatusEnum::PendingVerification;
    $binding['SlipBinding']['code'] = generateRandomNumericToken();
    unset($binding['SlipBinding']['mail']);

    // Save will calculate the upsert for us, injecting 'id' if it isn't already set.

    $this->clear();
    $this->save($binding);

    if(!empty($this->validationErrors)) {
      throw new RuntimeException(implode(",", array_keys($this->validationErrors)));
    }

    // Now send a Notification to the Claimant.

    $args = array();
    $args['conditions']['SlipProofing.id'] = $slipProofingId;
    $args['contain'] = array(
      'SlipPlugin' => array('BindingMessageTemplate')
    );

    $sp = $this->SlipProofing->find('first', $args);

    $substitutions = array(
      'IDENTIFIER'        => $identifier,
      'INVITE_URL'        => Router::url(array(
        'plugin'      => 'slip_plugin',
        'controller'  => 'slip_bindings',
        'action'      => 'bind',
        $this->id
      ),
      true)
    );

    // Construct subject and body

    $msgSubject = processTemplate($sp['SlipPlugin']['BindingMessageTemplate']['message_subject'], $substitutions);
    $msgBody = processTemplate($sp['SlipPlugin']['BindingMessageTemplate']['message_body'], $substitutions);

    $this->SlipProofing->ClaimantCoPerson->CoNotificationSubject->register(
      $sp['SlipProofing']['co_person_id'],
      null,
      $actorCoPersonId,
      'coperson',
      $sp['SlipProofing']['co_person_id'],
      ActionEnum::IdentityProofingRequested,
      _txt('pl.slipplugin.request.notify'),
      $substitutions['INVITE_URL'],
      false,
      null,
      $msgSubject,
      $msgBody,
      $sp['SlipPlugin']['BindingMessageTemplate']['cc'],
      $sp['SlipPlugin']['BindingMessageTemplate']['bcc'],
      $sp['SlipPlugin']['BindingMessageTemplate']['format']
    );

    return $this->id;
  }

  /**
   * Obtain the Authenticator Binding status associated with the specified SLIP Proofing request.
   * 
   * @since  COmanage Registry v4.7.0
   * @param  integer SLIP Proofing ID
   * @param  string  Identifier to return status for
   * @return array   Array of SLIP Bindings
   */

  public function status($slipProofingId, $identifier=null) {
    $ret = array();

    // First we need the CO Person from the SLIP Proofing request

    $coPersonId = $this->SlipProofing->field('co_person_id', array('id' => $slipProofingId));

    // Next, find the Org Identities associated with the CO Person

    $args = array();
    $args['conditions']['CoOrgIdentityLink.co_person_id'] = $coPersonId;
    $args['contain'] = array(
      'OrgIdentity' => array(
        // We pull Identifier because we only want records with a login Identifier
        'Identifier' => array('conditions' => array('login' => true))
      ),
      // We pull Email Address here as a convenience for request(), and also to throw
      // a warning if we can't reach the Claimant. Note we are intentionally looking at
      // the CO Person record for the Email Address, not the Org Identity, because we want
      // the CO to be able to manage how the Claimant is reached.
      'CoPerson' => 'EmailAddress'
    );

    $orgIds = $this->OrgIdentity->CoOrgIdentityLink->find('all', $args);

    // We non-deterministically pick the first Email Address returned and use it for all Org Identities
    $email = "";

    if(!empty($orgIds[0]['CoPerson']['EmailAddress'][0]['mail'])) {
      $email = $orgIds[0]['CoPerson']['EmailAddress'][0]['mail'];
    }

    // Then, for each Org Identity with an associated login Identifier, obtain the Binding status.
    // Note not every Org Identity may have an Authenticator Binding yet, if not we'll create an empty one.

    foreach($orgIds as $orgId) {
      if(!empty($orgId['OrgIdentity']['Identifier'][0]['identifier'])
         && (!$identifier || ($identifier == $orgId['OrgIdentity']['Identifier'][0]['identifier']))) {
        $args = array();
        $args['conditions']['SlipBinding.slip_proofing_id'] = $slipProofingId;
        $args['conditions']['SlipBinding.authenticated_identifier'] = $orgId['OrgIdentity']['Identifier'][0]['identifier'];
        $args['contain'] = false;

        $binding = $this->find('first', $args);

        if(!empty($binding)) {
          // Inject $email into the result
          $binding['SlipBinding']['mail'] = $email;

          $ret[] = $binding;
        } else {
          // Inject an empty record

          $ret[] = array(
            'SlipBinding' => array(
              'slip_proofing_id'          => $slipProofingId,
              'org_identity_id'           => $orgId['OrgIdentity']['id'],
              'status'                    => SlipBindingStatusEnum::NotStarted,
              'authenticated_identifier'  => $orgId['OrgIdentity']['Identifier'][0]['identifier'],
              'code'                      => null,
              'verification_time'         => null,
              // Note mail is not part of the data model, it's only here asa convenience
              'mail'                      => $email
            )
          );
        }
      }
    }
    
    return $ret;
  }
}
