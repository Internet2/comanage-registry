<?php
/**
 * COmanage Registry SLIP Proofing Model
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

class SlipProofing extends AppModel {
  // Add behaviors
  public $actsAs = array('Containable', 
                         'Linkable.Linkable' => array('priority' => 4), 
                         'Changelog' => array('priority' => 5));
  
  // Association rules from this model to other models
  public $belongsTo = array(
    "SlipPlugin.SlipPlugin",
    "ClaimantCoPerson" => array(
      'className' => 'CoPerson',
      'foreignKey' => 'co_person_id'
    ),
    "InitiatorCoPerson" => array(
      'className' => 'CoPerson',
      'foreignKey' => 'initiator_co_person_id'
    ),
    "RegistrarCoPerson" => array(
      'className' => 'CoPerson',
      'foreignKey' => 'registrar_co_person_id'
    ),
    "VoucherCoPerson" => array(
      'className' => 'CoPerson',
      'foreignKey' => 'voucher_co_person_id'
    )
  );

  public $hasMany = array(
    'SlipPlugin.SlipAttachment' => array('dependent' => true),
    'SlipPlugin.SlipBinding' => array('dependent' => true),
    'SlipPlugin.SlipIdentityDocument' => array('dependent' => true)
  );

  // Default display field for cake generated views
  public $displayField = "id";
  
  // Validation rules for table elements
  public $validate = array(
    'slip_plugin_id' => array(
      'rule' => 'numeric',
      'required' => true,
      'allowEmpty' => false
    ),
    'co_person_id' => array(
      'rule' => 'numeric',
      'required' => true,
      'allowEmpty' => false
    ),
    'initiator_co_person_id' => array(
      'rule' => 'numeric',
      'required' => false,
      'allowEmpty' => true
    ),
    'registrar_co_person_id' => array(
      'rule' => 'numeric',
      'required' => false,
      'allowEmpty' => true
    ),
    'voucher_co_person_id' => array(
      'rule' => 'numeric',
      'required' => false,
      'allowEmpty' => true
    ),
    'assignment_time' => array(
      'content' => array(
        'rule' => 'datetime',
        'required' => false,
        'allowEmpty' => true
      )
    ),
    'vouch_request_time' => array(
      'content' => array(
        'rule' => 'datetime',
        'required' => false,
        'allowEmpty' => true
      )
    ),
    'vouch_time' => array(
      'content' => array(
        'rule' => 'datetime',
        'required' => false,
        'allowEmpty' => true
      )
    ),
    'resolution_time' => array(
      'content' => array(
        'rule' => 'datetime',
        'required' => false,
        'allowEmpty' => true
      )
    )
  );

  /**
   * Accept (assign to self) an Identity Proofing request.
   * 
   * @since  COmanage Registry v4.7.0
   * @param  $id                Slip Proofing ID
   * @param  $actorCoPersonId   Actor CO Person ID
   */

  public function accept($id, $actorCoPersonId) {
    $args = array();
    $args['conditions']['SlipProofing.id'] = $id;
    $args['contain'] = false;

    $request = $this->find('first', $args);

    if(empty($request)) {
      throw new InvalidArgumentException(_txt('er.notfound', array(_txt('ct.slip_proofings.1'), $id)));
    }

    if(in_array($request['SlipProofing']['status'], array(
      SlipProofingStatusEnum::Canceled,
      SlipProofingStatusEnum::Completed
    ))) {
      throw new InvalidArgumentException(_txt('er.slipplugin.accept'));
    }

    $this->clear();
    $this->id = $id;
    $this->saveField('registrar_co_person_id', $actorCoPersonId);
    $this->saveField('status', SlipProofingStatusEnum::Assigned);
    $this->saveField('assignment_time', date('Y-m-d H:i:s', time()));

    // Record history on CO Person

    $args = array();
    $args['conditions']['RegistrarCoPerson.id'] = $actorCoPersonId;
    $args['contain'] = array('PrimaryName');

    $registrar = $this->RegistrarCoPerson->find('first', $args);

    $this->ClaimantCoPerson->HistoryRecord->record(
      $request['SlipProofing']['co_person_id'],
      null,
      null,
      $actorCoPersonId,
      ActionEnum::IdentityProofingAssigned,
      _txt('pl.slipplugin.accepted.history', array($this->id, generateCn($registrar['PrimaryName'])))
    );

    // Resolve the notification

    $this->ClaimantCoPerson->CoNotificationSubject->resolveFromSource(
      array(
        'plugin' => 'slip_plugin',
        'controller' => 'slip_proofings',
        'action' => 'view',
        'id' => $id
      ),
      $actorCoPersonId
    );
  }

  /**
   * Assign a Proofing to the specificed Voucher.
   * 
   * @since  COmanage Registry v4.7.0
   * @param  $id        SLIP Proofing ID
   * @param  $id        Voucher CO Person ID
   * @param  $id        Actor CO Person ID
   * @return bool       true if assigned successfully
   * @throws InvalidArgumentException
   */

  public function assignVoucher($id, $voucherCoPersonId, $actorCoPersonId) {
    $args = array();
    $args['conditions']['SlipProofing.id'] = $id;
    $args['contain'] = array(
      'ClaimantCoPerson' => 'PrimaryName',
      'SlipPlugin' => 'VouchingMessageTemplate'
    );

    $request = $this->find('first', $args);

    if(empty($request)) {
      throw new InvalidArgumentException(_txt('er.notfound', array(_txt('ct.slip_proofings.1'), $id)));
    }

    if(!in_array($request['SlipProofing']['status'],
                 array(
                   SlipProofingStatusEnum::Assigned,
                   SlipProofingStatusEnum::Scheduled,
                   SlipProofingStatusEnum::PendingVouching
                 ))) {
      throw new InvalidArgumentException(_txt('er.slipplugin.vouch.assign'));
    }

    $this->clear();
    $this->id = $id;
    $this->saveField('voucher_co_person_id', $voucherCoPersonId);
    $this->saveField('status', SlipProofingStatusEnum::PendingVouching);
    $this->saveField('vouch_request_time', date('Y-m-d H:i:s', time()));

    // Generate Notification
    
    $args = array();
    $args['conditions']['ClaimantCoPerson.id'] = $request['ClaimantCoPerson']['id'];
    $args['contain'] = array('PrimaryName');

    $cp = $this->ClaimantCoPerson->find('first', $args);

    $substitutions = array(
      'CO_PERSON'         => generateCn($request['ClaimantCoPerson']['PrimaryName'])
    );

    // Construct subject and body

    $msgSubject = processTemplate($request['SlipPlugin']['VouchingMessageTemplate']['message_subject'], $substitutions);
    $msgBody = processTemplate($request['SlipPlugin']['VouchingMessageTemplate']['message_body'], $substitutions);

    $this->ClaimantCoPerson->CoNotificationSubject->register(
      $request['ClaimantCoPerson']['id'],
      null,
      $actorCoPersonId,
      'coperson',
      $voucherCoPersonId,
      ActionEnum::IdentityProofingAssigned,
      _txt('pl.slipplugin.assigned'),
      array(
        'plugin' => 'slip_plugin',
        'controller' => 'slip_proofings',
        'action' => 'vouch',
        'id' => $this->id
      ),
      true,
      null,
      $msgSubject,
      $msgBody,
      $request['SlipPlugin']['VouchingMessageTemplate']['cc'],
      $request['SlipPlugin']['VouchingMessageTemplate']['bcc'],
      $request['SlipPlugin']['VouchingMessageTemplate']['format']
    );

    return true;
  }

  /**
   * Create and attach an Identity Document to this Proofing Request.
   * 
   * @since  COmanage Registry v4.7.0
   * @param  $id        SLIP Proofing ID
   * @param  $data      Array of Identity Document data
   * @return int        SLIP Identity Document ID
   */

  public function attachDocument($id, $data) {
    // Identity Documents require a co_person_id, not a slip_proofing_id

    $idata = $data;

    $idata['co_person_id'] = $this->field('co_person_id', array('id' => $id));
    unset($idata['slip_proofing_id']);

    // Start a transaction

    $dbc = $this->getDataSource();
    $dbc->begin();

    // Create the Identity Document

    $this->SlipIdentityDocument->IdentityDocument->clear();

    if(!$this->SlipIdentityDocument->IdentityDocument->save($idata)) {
      throw new InvalidArgumentException(Hash::flatten($model->validationErrors));
    }

    // Attach the Identity Document to the Proofing Request

    $sid = array(
      'slip_proofing_id' => $id,
      'identity_document_id' => $this->SlipIdentityDocument->IdentityDocument->id
    );

    $this->SlipIdentityDocument->clear();
    $this->SlipIdentityDocument->save($sid);

    $dbc->commit();

    return $this->SlipIdentityDocument->id;
  }

  /**
   * Validate the Identity Proofing status of the specified CO Person ID, possibly creating
   * or revoking a membership in the validity group.
   * 
   * @since  COmanage Registry v4.7.0
   * @param  int    SLIP Plugin ID
   * @param  int    CO Person ID
   * @param  int    Actor CO Person ID
   * @return array  Array of criteria and status
   */

  public function calculate($slipPluginId, $coPersonId, $actorCoPersonId) {
    $ret = array(
      // 'result' is the calculation result, ie is this CO Person proofed?
      'result' => false,
      'slip_proofing' => null,
      'request' => array(
        'completed' => false,
        'comment'   => _txt('pl.slipplugin.result.proofing.incomplete')
      ),
      'documents' => array(
        'completed' => false,
        'comment'   => _txt('pl.slipplugin.result.docs.none')
      ),
      'bindings' => array(
        'completed' => false,
        'comment'   => _txt('pl.slipplugin.result.binding.none')
      ),
      'vouching' => array(
        'completed' => false,
        'comment'   => _txt('pl.slipplugin.result.vouching.incomplete')
      )
    );

    // Pull the relevant records, via Slip Proofings
    $args = array();
    $args['conditions']['SlipProofing.slip_plugin_id'] = $slipPluginId;
    $args['conditions']['SlipProofing.co_person_id'] = $coPersonId;
    $args['order'] = 'SlipProofing.modified DESC';
    $args['contain'] = array(
      'SlipPlugin' => array('ApprovalMessageTemplate'),
      'ClaimantCoPerson' => array('PrimaryName'),
      'SlipIdentityDocument' => 'IdentityDocument'
    );

    $info = $this->find('all', $args);

    if(!empty($info)) {
      // A Valid Identity Proofing requires all of the following:
      //
      // (1) An Identity Proofing request in "Completed" status with a valid Resolution time
      // (2) There must be at least one Identity Document with active Validity Dates
      // (3) For each Org Identity with a Login Identifier, there must be a
      //     Confirmed Authenticator Binding
      // (4) There must be a valid Voucher in the Identity Proofing request (a CO Person
      //     in Active status)

      // There can be more than one SlipProofing request, however because associated
      // documents (like Authenticator Bindings) are attached to a SlipProofing and not
      // directly to the CO Person we need to pick one to work with. (This is perhaps
      // something that can be improved in v5.) This will also allow for a clearer
      // UX in the the calculation result view.

      // Our preference is (based on Proofing status, with most recent as a tie breaker)
      // - Completed
      // - PendingVouching
      // - Assigned | Scheduled
      // - Initiated
      // - Canceled | Rejected | Revoked

      $statusRanks = array(
        SlipProofingStatusEnum::Completed           => 6,
        SlipProofingStatusEnum::Vouched             => 5,
        SlipProofingStatusEnum::PendingVouching     => 4,
        SlipProofingStatusEnum::Assigned            => 3,
        SlipProofingStatusEnum::Scheduled           => 3,
        SlipProofingStatusEnum::Initiated           => 2,
        SlipProofingStatusEnum::Canceled            => 1,
        SlipProofingStatusEnum::Rejected            => 1,
        SlipProofingStatusEnum::Revoked             => 1
      );

      $proofing = null;

      foreach($info as $p) {
        if(!$proofing) {
          // No candidate yet, pick this one

          $proofing = $p;
        } else {
          // If $p has a "better" status than $proofing, select $p instead.
          // For equivalent ranks we pick the first one we saw, which should be
          // the most recent.

          if($statusRanks[ $p['SlipProofing']['status'] ]
             > $statusRanks[ $proofing['SlipProofing']['status'] ]) {
            $proofing = $p;
          }
        }
      }

      $ret['slip_proofing'] = $proofing['SlipProofing'];

      // It's not clear this is the correct logic/test. eg: If a Registrar reassigns
      // a request to themself for somereason after it is vouched, we won't be able
      // to successfully pass this test. Maybe that's ok? The request would need to be
      // re-vouched, basically.
      $ret['request']['completed'] = in_array($proofing['SlipProofing']['status'],
                                              array(
                                                SlipProofingStatusEnum::Completed,
                                                SlipProofingStatusEnum::Vouched
                                              ));
      $ret['request']['comment'] = _txt('pl.slipplugin.en.status', null, $proofing['SlipProofing']['status']);

      // We'll check all criteria here even if early ones fail since we want to be able
      // to provide a complete overview to the view.

      // Check for at least one valid Identity Document

      if(!empty($proofing['SlipIdentityDocument'])) {
        foreach($proofing['SlipIdentityDocument'] as $sid) {
          if(!empty($sid['IdentityDocument']['id'])
             &&
             (!$sid['IdentityDocument']['valid_from']
              || strtotime($sid['IdentityDocument']['valid_from']) < time())
             &&
             (!$sid['IdentityDocument']['valid_through']
              || strtotime($sid['IdentityDocument']['valid_through']) > time())) {
            // This Identity Document is valid
            $ret['documents']['completed'] = true;
            $ret['documents']['comment'] = _txt('pl.slipplugin.result.docs.complete');
            break;
          }
        }
      }

      // Look for an Authenticator Binding for each attached Org Identity with a
      // login identifier. SlipBinding::status() will do the work for us.

      $bindings = $this->SlipBinding->status($proofing['SlipProofing']['id']);

      $byes = 0;
      $bno = 0;

      foreach($bindings as $b) {
        if($b['SlipBinding']['status'] == SlipBindingStatusEnum::Confirmed) {
          $byes++;
        } else {
          $bno++;
        }
      }

      if($byes > 0) {
        if($bno > 0) {
          // At least one Authenticator has not been Bound
          $ret['bindings']['comment'] = _txt('pl.slipplugin.result.binding.incomplete', array($byes, $bno));
        } else {
          // All Authenticators have been Bound
          $ret['bindings']['completed'] = true;
          $ret['bindings']['comment'] = _txt('pl.slipplugin.result.binding.complete');
        }
      }
      // For now, if there are no OrgIdentities then proofing fails, though it's not
      // clear when this situation would arise.

      // Check for vouching
      if(!empty($proofing['SlipProofing']['vouch_time'])) {
        $ret['vouching']['completed'] = true;
        $ret['vouching']['comment'] = _txt('pl.slipplugin.result.vouching.complete');
      }
    } else {
      // No Proofing Requests found

      $ret['request']['comment'] = _txt('pl.slipplugin.result.proofing.none');
    }

// debug($ret);
    // Calculate the overall result
    $ret['result'] = $ret['request']['completed']
                     && $ret['documents']['completed']
                     && $ret['bindings']['completed']
                     && $ret['vouching']['completed'];

    $CoGroupMember = ClassRegistry::init('CoGroupMember');

    if($ret['result']) {
// XXX document
      // If valid, the following actions are taken:
      //
      // (1) The request is marked resolved, if not already done
      // (2) The CO Person is added to the Proofing Valid Group (if not a member)
      // (3) A History Record is created
      // (4) All Attachments associated with the Identity Proofing are hard deleted
      // (5) A Notification is sent to the Claimant

      if($proofing['SlipProofing']['status'] != SlipProofingStatusEnum::Completed) {
        $this->clear();
        $this->id = $proofing['SlipProofing']['id'];
        $this->saveField('status', SlipProofingStatusEnum::Completed);
        $this->saveField('resolution_time', date('Y-m-d H:i:s', time()));
      }

      $CoGroupMember->setMembership(
        null,
        $proofing['SlipPlugin']['proofing_valid_co_group_id'], 
        $proofing['SlipProofing']['co_person_id'],
        true,
        false,
        $actorCoPersonId
      );

      $this->ClaimantCoPerson->HistoryRecord->record(
        $proofing['SlipProofing']['co_person_id'],
        null,
        null,
        $actorCoPersonId,
        ActionEnum::IdentityProofingCalculated,
        _txt('pl.slipplugin.calculate.ok')
      );

      // Because SlipAttachments do not use Changelog, we can delete them without callbacks
      $this->SlipAttachment->deleteAll(
        array('SlipAttachment.slip_proofing_id' => $proofing['SlipProofing']['id']),
        false, 
        false
      );

      // Send the Notification

      $substitutions = array(
        'CO_PERSON'         => generateCn($proofing['ClaimantCoPerson']['PrimaryName'])
      );

      // Construct subject and body

      $msgSubject = processTemplate($proofing['SlipPlugin']['ApprovalMessageTemplate']['message_subject'], $substitutions);
      $msgBody = processTemplate($proofing['SlipPlugin']['ApprovalMessageTemplate']['message_body'], $substitutions);

      $this->ClaimantCoPerson->CoNotificationSubject->register(
        $proofing['SlipProofing']['co_person_id'],
        null,
        $actorCoPersonId,
        'coperson',
        $proofing['SlipProofing']['co_person_id'],
        ActionEnum::IdentityProofingCompleted,
        _txt('pl.slipplugin.completed'),
        array(
          'plugin' => 'slip_plugin',
          'controller' => 'slip_proofings',
          'action' => 'view',
          'id' => $proofing['SlipProofing']['id']
        ),
        false,
        null,
        $msgSubject,
        $msgBody,
        $proofing['SlipPlugin']['ApprovalMessageTemplate']['cc'],
        $proofing['SlipPlugin']['ApprovalMessageTemplate']['bcc'],
        $proofing['SlipPlugin']['ApprovalMessageTemplate']['format']
      );
    } else {
      // If not valid, the following actions are taken:
      //
      // (1) The CO Person is removed from the Proofing Valid Group (if a member)
      // (2) A History Record is created

      // CoGroupMember::isMember will check for validity dates, but we want to clear
      // any records in the CoGroup regardless.

      $args = array();
      $args['conditions']['CoGroupMember.co_group_id'] = $proofing['SlipPlugin']['proofing_valid_co_group_id'];
      $args['conditions']['CoGroupMember.co_person_id'] = $proofing['SlipProofing']['co_person_id'];
      $args['conditions']['CoGroupMember.member'] = true;
      $args['contain'] = array('CoGroup');

      $memberships = $CoGroupMember->find('all', $args);

      if(!empty($memberships)) {
        foreach($memberships as $gm) {
          $CoGroupMember->delete($gm['CoGroupMember']['id']);

          $this->ClaimantCoPerson->HistoryRecord->record(
            $proofing['SlipProofing']['co_person_id'],
            null,
            null,
            $actorCoPersonId,
            ActionEnum::CoGroupMemberDeleted,
            _txt('rs.grm.deleted', array($gm['CoGroup']['name'], $gm['CoGroup']['id'])),
            $gm['CoGroup']['id']
          );
        }
      }

      $this->ClaimantCoPerson->HistoryRecord->record(
        $proofing['SlipProofing']['co_person_id'],
        null,
        null,
        $actorCoPersonId,
        ActionEnum::IdentityProofingCalculated,
        _txt('er.slipplugin.calculate')
      );
    }

    return $ret;
  }

  /**
   * Cancel an Identity Proofing request.
   * 
   * @since  COmanage Registry v4.7.0
   * @param  $id                Slip Proofing ID
   * @param  $actorCoPersonId   Actor CO Person ID
   */

  public function cancel($id, $actorCoPersonId) {
    $args = array();
    $args['conditions']['SlipProofing.id'] = $id;
    $args['contain'] = false;

    $request = $this->find('first', $args);

    if(empty($request)) {
      throw new InvalidArgumentException(_txt('er.notfound', array(_txt('ct.slip_proofings.1'), $id)));
    }

    if(in_array($request['SlipProofing']['status'], array(
      SlipProofingStatusEnum::Canceled,
      SlipProofingStatusEnum::Completed
    ))) {
      throw new InvalidArgumentException(_txt('er.slipplugin.cxl'));
    }

    $this->clear();
    $this->id = $id;
    $this->saveField('status', SlipProofingStatusEnum::Canceled);

    // Record history on CO Person

    $this->ClaimantCoPerson->HistoryRecord->record(
      $request['SlipProofing']['co_person_id'],
      null,
      null,
      $actorCoPersonId,
      ActionEnum::IdentityProofingCanceled,
      _txt('pl.slipplugin.canceled.history', array($this->id))
    );

    // Terminate the notification

    $this->ClaimantCoPerson->CoNotificationSubject->resolveFromSource(
      array(
        'plugin' => 'slip_plugin',
        'controller' => 'slip_proofings',
        'action' => 'view',
        'id' => $id
      ),
      $actorCoPersonId
    );
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
    // SlipProofings refer to SlipPlugins

    $args = array();
    $args['conditions'][$this->alias.'.id'] = $id;
    $args['contain'][] = 'SlipPlugin';

    $sp = $this->find('first', $args);

    if(!empty($sp['SlipPlugin']['co_id'])) {
      return $sp['SlipPlugin']['co_id'];
    } else {
      return parent::findCoForRecord($id);
    }
  }

  /**
   * Determine if the specified CO Person is the Voucher for the SLIP Proofing.
   * 
   * @since  COmanage Registry v4.7.0
   * @param  $id            SLIP Proofing ID
   * @param  $coPersonId    CO Person ID
   * @return bool           true if $coPersonId is the Voucher, false otherwise
   */

  public function isVoucher($id, $coPersonId) {
    $args = array();
    $args['conditions']['SlipProofing.id'] = $id;
    $args['contain'] = false;

    $request = $this->find('first', $args);

    return (!empty($request['SlipProofing']['voucher_co_person_id'])
            && $request['SlipProofing']['voucher_co_person_id'] == $coPersonId);
  }

  /**
   * Schedule an Identity Proofing request. Note this currently just updates the request status,
   * no actual scheduling is performed.
   * 
   * @since  COmanage Registry v4.7.0
   * @param  $id                Slip Proofing ID
   * @param  $actorCoPersonId   Actor CO Person ID
   */

  public function schedule($id, $actorCoPersonId) {
    $args = array();
    $args['conditions']['SlipProofing.id'] = $id;
    $args['contain'] = array('SlipPlugin' => 'SchedulingMessageTemplate');

    $request = $this->find('first', $args);

    if(empty($request)) {
      throw new InvalidArgumentException(_txt('er.notfound', array(_txt('ct.slip_proofings.1'), $id)));
    }

    if($request['SlipProofing']['status'] != SlipProofingStatusEnum::Assigned) {
      throw new InvalidArgumentException(_txt('er.slipplugin.schedule'));
    }

    $this->clear();
    $this->id = $id;
    $this->saveField('status', SlipProofingStatusEnum::Scheduled);

    // Record history on CO Person

    $this->ClaimantCoPerson->HistoryRecord->record(
      $request['SlipProofing']['co_person_id'],
      null,
      null,
      $actorCoPersonId,
      ActionEnum::IdentityProofingScheduled,
      _txt('pl.slipplugin.scheduled.history', array($this->id))
    );

    // Send a notification to the Claimant

    $args = array();
    $args['conditions']['ClaimantCoPerson.id'] = $request['SlipProofing']['co_person_id'];
    $args['contain'] = array('PrimaryName');

    $cp = $this->ClaimantCoPerson->find('first', $args);

    $substitutions = array(
      'CO_PERSON'         => generateCn($cp['PrimaryName'])
    );

    // Construct subject and body

    $msgSubject = processTemplate($request['SlipPlugin']['SchedulingMessageTemplate']['message_subject'], $substitutions);
    $msgBody = processTemplate($request['SlipPlugin']['SchedulingMessageTemplate']['message_body'], $substitutions);

    $this->ClaimantCoPerson->CoNotificationSubject->register(
      $request['SlipProofing']['co_person_id'],
      null,
      $actorCoPersonId,
      'coperson',
      $request['SlipProofing']['co_person_id'],
      ActionEnum::IdentityProofingScheduled,
      _txt('pl.slipplugin.scheduled.history', array($this->id)),
      array(
        'plugin' => 'slip_plugin',
        'controller' => 'slip_proofings',
        'action' => 'view',
        'id' => $id
      ),
      false,
      null,
      $msgSubject,
      $msgBody,
      $request['SlipPlugin']['SchedulingMessageTemplate']['cc'],
      $request['SlipPlugin']['SchedulingMessageTemplate']['bcc'],
      $request['SlipPlugin']['SchedulingMessageTemplate']['format']
    );
  }

  /**
   * Start an Identity Proofing request.
   * 
   * @since  COmanage Registry v4.7.0
   * @param  $slipPluginId          Slip Plugin ID
   * @param  $coPersonId            Claimant CO Person ID
   * @param  $initiatorCoPersonId   Initiator CO Person ID
   * @return SlipProofing Request ID
   * @throws RuntimeException
   */

  public function start($slipPluginId, $coPersonId, $initiatorCoPersonId=null) {
    // Pull our configuration

    $args = array();
    $args['conditions']['SlipPlugin.id'] = $slipPluginId;
    $args['contain'] = array('InitiationMessageTemplate');

    $cfg = $this->SlipPlugin->find('first', $args);

    if(!$cfg) {
      throw new InvalidArgumentException(_txt('er.unknown', array($slipPluginId)));
    }

    // Start a transaction with a read lock

    $dbc = $this->getDataSource();
    $dbc->begin();

    // Check if there's already a request in progress

    $args = array();
    $args['conditions']['SlipProofing.co_person_id'] = $coPersonId;
    $args['conditions']['NOT']['SlipProofing.status'] = array(
      SlipProofingStatusEnum::Canceled,
      SlipProofingStatusEnum::Completed
    );
    $args['contain'] = false;

    $requests = $this->findForUpdate($args['conditions'], array('id', 'status'));

    if(!empty($requests)) {
      throw new RuntimeException(_txt('er.slipplugin.start.inprogress', array($requests[0]['SlipProofing']['id'])));
    }

    $this->clear();

    $request = array(
      'slip_plugin_id'          => $slipPluginId,
      'co_person_id'            => $coPersonId,
      'initiator_co_person_id'  => $initiatorCoPersonId,
      'status'                  => SlipProofingStatusEnum::Initiated
    );

    $this->save($request);

    // Record history on CO Person

    $this->ClaimantCoPerson->HistoryRecord->record(
      $coPersonId,
      null,
      null,
      $initiatorCoPersonId,
      ActionEnum::IdentityProofingStarted,
      _txt('pl.slipplugin.initiated.history', array($this->id))
    );

    $dbc->commit();

    // Send notification to Registrar Group, assembling additional substitutions as needed

    $args = array();
    $args['conditions']['ClaimantCoPerson.id'] = $coPersonId;
    $args['contain'] = array('PrimaryName');

    $cp = $this->ClaimantCoPerson->find('first', $args);

    $substitutions = array(
      'CO_PERSON'         => generateCn($cp['PrimaryName'])
    );

    // Construct subject and body

    $msgSubject = processTemplate($cfg['InitiationMessageTemplate']['message_subject'], $substitutions);
    $msgBody = processTemplate($cfg['InitiationMessageTemplate']['message_body'], $substitutions);
    
    $this->ClaimantCoPerson->CoNotificationSubject->register(
      $coPersonId,
      null,
      $initiatorCoPersonId,
      'cogroup',
      $cfg['SlipPlugin']['registrar_co_group_id'],
      ActionEnum::IdentityProofingStarted,
      _txt('pl.slipplugin.initiated.history', array($this->id)),
      array(
        'plugin' => 'slip_plugin',
        'controller' => 'slip_proofings',
        'action' => 'view',
        'id' => $this->id
      ),
      true,
      null,
      $msgSubject,
      $msgBody,
      $cfg['InitiationMessageTemplate']['cc'],
      $cfg['InitiationMessageTemplate']['bcc'],
      $cfg['InitiationMessageTemplate']['format']
    );
  }

  /**
   * Vouch for an Identity Proofing request.
   * 
   * @since  COmanage Registry v4.7.0
   * @param  $id                Slip Proofing ID
   * @param  $actorCoPersonId   Actor (Voucher) CO Person ID
   * @param  $confirm           If true process as a confirmation, otherwise process as a rejection
   */

  public function vouch($id, $actorCoPersonId, $confirm=true) {
    $args = array();
    $args['conditions']['SlipProofing.id'] = $id;
    $args['contain'] = false;

    $request = $this->find('first', $args);

    if(empty($request)) {
      throw new InvalidArgumentException(_txt('er.notfound', array(_txt('ct.slip_proofings.1'), $id)));
    }

    if($request['SlipProofing']['status'] != SlipProofingStatusEnum::PendingVouching) {
      throw new InvalidArgumentException(_txt('er.slipplugin.vouch.status'));
    }

    // We don't check that $actor is also the $voucher since admins can access this function.

    $this->clear();
    $this->id = $id;
    $this->saveField('status', $confirm ? SlipProofingStatusEnum::Vouched : SlipProofingStatusEnum::Rejected);
    $this->saveField('vouch_time', date('Y-m-d H:i:s', time()));

    // Record history on CO Person
    $key = $confirm ? 'pl.slipplugin.confirmed.history' : 'pl.slipplugin.rejected.history';

    $this->ClaimantCoPerson->HistoryRecord->record(
      $request['SlipProofing']['co_person_id'],
      null,
      null,
      $actorCoPersonId,
      $confirm ? ActionEnum::IdentityProofingCompleted : ActionEnum::IdentityProofingCanceled,
      _txt($key, array($id))
    );

    // Terminate the notification

    $this->ClaimantCoPerson->CoNotificationSubject->resolveFromSource(
      array(
        'plugin' => 'slip_plugin',
        'controller' => 'slip_proofings',
        'action' => 'vouch',
        'id' => $id
      ),
      $actorCoPersonId
    );

    // Trigger a recalculation of the Claimaint's Proofing status

    $this->calculate(
      $request['SlipProofing']['slip_plugin_id'],
      $request['SlipProofing']['co_person_id'],
      $actorCoPersonId
    );
  }
}
