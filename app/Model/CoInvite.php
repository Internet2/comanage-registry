<?php
/**
 * COmanage Registry CO Invite Model
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
 * @since         COmanage Registry v0.1
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::uses('CakeEmail', 'Network/Email');

class CoInvite extends AppModel {
  // Define class name for cake
  public $name = "CoInvite";
  
  // Current schema version for API
  public $version = "1.0";
  
  // Add behaviors
  public $actsAs = array('Containable',
                         'Changelog' => array('priority' => 5));
  
  // Association rules from this model to other models
  public $belongsTo = array("CoPerson",
                            "EmailAddress");
  
  public $hasOne = array("CoPetition");
  
  // Default display field for cake generated views
  public $displayField = "invitation";
  
  // Default ordering for find operations
  public $order = "expires";
  
  // Validation rules for table elements
  public $validate = array(
    'invitation' => array(
      'rule' => 'alphaNumeric',
      'required' => true
    ),
    'co_person_id' => array(
      'rule' => 'notBlank',
      'required' => true
    ),
    'mail' => array(
      'rule' => 'email',
      'required' => false,
      'allowEmpty' => true
    ),
    'email_address_id' => array(
      'rule' => 'numeric',
      'required' => false,
      'allowEmpty' => true
    ),
    'expires' => array(
      'rule' => '/.*/',  // The 'date' rule is too constraining
      'required' => false,
      'allowEmpty' => true
    )
  );
  
  /**
   * Process the reply to an invite.
   *
   * @since  COmanage Registry v0.7
   * @param  Integer CO Invite ID
   * @param  Boolean If true, confirm the invitation; if false, decline it
   * @param  String Login identifier, if authentication is needed for this invite
   * @throws InvalidArgumentException
   * @throws OutOfBoundsException
   * @throws RuntimeException
   */
  
  public function processReply($inviteId, $confirm, $loginIdentifier=null) {
    // Start a transaction
    $dbc = $this->getDataSource();
    $dbc->begin();
    
    $args = array();
    $args['conditions']['CoInvite.invitation'] = $inviteId;
    $args['contain'] = array('CoPerson', 'CoPetition', 'EmailAddress');
    
    $invite = $this->find('first', $args);
    
    if(!empty($invite)) {
      $verifyEmail = !empty($invite['CoInvite']['email_address_id']);
      
      // Check invite validity
      
      if(time() < strtotime($invite['CoInvite']['expires'])) {
        if($verifyEmail) {
          // Verifying an email address
          
          try {
            // XXX Note we're passing a bunch of stuff just so verify() can create a history record,
            // which we do below anyway. Refactor as part of CO-753.
            $this->CoPerson->EmailAddress->verify($invite['EmailAddress']['org_identity_id'],
                                                  $invite['EmailAddress']['co_person_id'],
                                                  $invite['CoInvite']['mail'],
                                                  $invite['EmailAddress']['co_person_id']);
          }
          catch(Exception $e) {
            $dbc->rollback();
            throw new RuntimeException($e->getMessage());
          }
        } elseif(isset($invite['CoPetition']['id'])) {
          // Before we can delete the invitation, we need to unlink it from the petition
          
          $this->CoPetition->id = $invite['CoPetition']['id'];
          $this->CoPetition->saveField('co_invite_id', null);
        } else {
          // Default (ie: non-enrollment flow) behavior: update CO Person
          
          $this->CoPerson->id = $invite['CoPerson']['id'];
          
          if(!$this->CoPerson->saveField('status', $confirm ? StatusEnum::Active : StatusEnum::Declined)) {
            $dbc->rollback();
            throw new RuntimeException(_txt('er.cop.nf', array($invite['CoPerson']['id'])));
          }
        }
        
        // Mark the email address associated with this invite as verified.
        
        if($confirm) {
          // For historical reasons, we don't attach the email_address_id to the invite
          // when we're in a petition context. (That's how we distinguish petition
          // vs not, above.) This all needs to get rewritten (currently scheduled
          // for v4.0.0), but in the mean time we have to check both Org Identity
          // and CO Person attached Email Addresses, since we might have confirmed
          // either one.
          
          $orgId = null;
          
          if(isset($invite['CoPetition']['enrollee_org_identity_id'])) {
            $orgId = $invite['CoPetition']['enrollee_org_identity_id'];
          } elseif(empty($invite['CoPetition'])) {
            // Try to find the org identity associated with this invite
            
            $args = array();
            $args['conditions']['CoOrgIdentityLink.co_person_id'] = $invite['CoPerson']['co_person_id'];
            $args['conditions']['EmailAddress.mail'] = $invite['CoInvite']['mail'];
            $args['joins'][0]['table'] = 'cm_email_addresses';
            $args['joins'][0]['alias'] = 'EmailAddress';
            $args['joins'][0]['type'] = 'INNER';
            $args['joins'][0]['conditions'][0] = 'CoOrgIdentityLink.org_identity_id=EmailAddress.org_identity_id';
            $args['contain'] = false;
            
            // This *should* generate one result...
            $link = $this->CoPerson->CoOrgIdentityLink->find('first', $args);
            
            if(!empty($link['CoOrgIdentityLink']['org_identity_id'])) {
              $orgId = $link['CoOrgIdentityLink']['org_identity_id'];
            }
          }
          
          if($orgId) {
            try {
              $this->CoPerson->EmailAddress->verify($orgId,
                                                    $invite['CoPetition']['enrollee_co_person_id'],
                                                    $invite['CoInvite']['mail'],
                                                    $invite['CoPetition']['enrollee_co_person_id']);
            }
            catch(Exception $e) {
              $dbc->rollback();
              throw new RuntimeException($e->getMessage());
            }
          }
        }
        
        // Toss the invite
        $this->delete($invite['CoInvite']['id']);
      } else {
        if(!empty($invite['CoPetition']['id'])) {
          // Before we can delete the invitation, we need to unlink it from the petition
          
          $this->CoPetition->id = $invite['CoPetition']['id'];
          $this->CoPetition->saveField('co_invite_id', null);
        }
        
        $this->delete($invite['CoInvite']['id']);
        
        // Record a history record that the invitation expired
        try {
          $this->CoPerson->HistoryRecord->record($invite['CoInvite']['co_person_id'],
                                                 null,
                                                 null,
                                                 null,
                                                 ActionEnum::InvitationExpired,
                                                 _txt('er.inv.exp.use'));
        }
        catch(Exception $e) {
          $dbc->rollback();
          throw new RuntimeException($e->getMessage());
        }
        
        // Commit here, don't roll back!
        $dbc->commit();
        throw new OutOfBoundsException(_txt('er.inv.exp'));
      }
      
      // Create a history record
      
      if($verifyEmail) {
        // XXX Note that EmailAddress->verify() will also record almost the same history message on success.
        // This should probably be refactored as part of CO-753.
        $hcopid = $invite['EmailAddress']['co_person_id'];
        $hcoprid = null;
        $horgid = $invite['EmailAddress']['org_identity_id'];
        $hactorid = $hcopid; // XXX Should it be mapped from $loginidentifier instead? (review as part of CO-753)
        $hop = $confirm ? ActionEnum::EmailAddressVerified : ActionEnum::EmailAddressVerifyCanceled;
        $htxt = _txt(($confirm ? 'rs.ev.ver-a' : 'rs.ev.cxl-a'),
                     array($invite['EmailAddress']['mail'], $loginIdentifier));
      } else {
        $hcopid = $invite['CoInvite']['co_person_id'];
        $hcoprid = isset($invite['CoPetition']['enrollee_co_person_role_id']) ? $invite['CoPetition']['enrollee_co_person_role_id'] : null;
        $horgid = isset($invite['CoPetition']['enrollee_org_identity_id']) ? $invite['CoPetition']['enrollee_org_identity_id'] : null;
        $hactorid = $invite['CoPetition']['enrollee_co_person_id'];
        $hop = $confirm ? ActionEnum::InvitationConfirmed : ActionEnum::InvitationDeclined;
        $htxt = _txt(($confirm ? 'rs.inv.conf-a' : 'rs.inv.dec-a'),
                     array($invite['CoInvite']['mail']));
      }
      
      try {
        $this->CoPerson->HistoryRecord->record($hcopid,
                                               $hcoprid,
                                               $horgid,
                                               $hactorid,
                                               $hop,
                                               $htxt);
      }
      catch(Exception $e) {
        $dbc->rollback();
        throw new RuntimeException($e->getMessage());
      }
    } else {
      $dbc->rollback();
      throw new InvalidArgumentException(_txt('er.inv.nf'));
    }
    
    $dbc->commit();
  }
  
  /**
   * Create and send an invitation. Any existing invitation for the CO Person will be removed.
   *
   * @since  COmanage Registry v0.7
   * @param  Integer CO Person ID associated with the invitation
   * @param  Integer Org Identity ID associated with the invitation
   * @param  Integer CO Person ID of actor sending the invite
   * @param  String Email Address to send the invite to
   * @param  String Email Address to send the invite from
   * @param  String CO Name (to pass into invite)
   * @param  String Subject text (for configured templates stored in the database)
   * @param  String Template text (for configured templates stored in the database)
   * @param  Integer Email Address ID to verify
   * @param  Integer Time, in minutes, the invitation is valid for (default = 1440 = 1 day)
   * @param  String Comma separated list of addresses to cc
   * @param  String Comma separated list of addresses to bcc
   * @param  Array Substitutions for message template (to supplement those handled natively)
   * @return Integer CO Invitation ID
   * @throws RuntimeException
   * @todo The function signature has evolved organically and is a bit of a mess, clean up as part of CO-753
   */
  
  public function send($coPersonId,
                       $orgIdentityID,
                       $actorPersonId,
                       $toEmail,
                       $fromEmail=null,
                       $coName,
                       $subject=null,
                       $template=null,
                       $emailAddressID=null,
                       $expiry=null,
                       $cc=null,
                       $bcc=null,
                       $subs=array()) {
    // Toss any prior invitations for $coPersonId to $toEmail
    
    try {
      $this->deleteAll(array('CoInvite.co_person_id' => $coPersonId,
                             'CoInvite.mail' => $toEmail),
                       false,
                       // We need callbacks to fire for Changelog
                       true);
    }
    catch(Exception $e) {
      throw new RuntimeException($e->getMessage());
    }
    
    $invite = array();
    $invite['CoInvite']['co_person_id'] = $coPersonId;
    $invite['CoInvite']['invitation'] = Security::generateAuthKey();
    $invite['CoInvite']['mail'] = $toEmail;
    // XXX date format may not be portable
    $invite['CoInvite']['expires'] = date('Y-m-d H:i:s', strtotime('+' . ($expiry ? $expiry : DEF_INV_VALIDITY) . ' minutes'));
    if($emailAddressID) {
      $invite['CoInvite']['email_address_id'] = $emailAddressID;
    }
    
    $this->create($invite);
    
    if($this->save()) {
      // Try to send the invite
      
      // Set up and send the invitation via email
      $email = new CakeEmail('default');
      
      $substitutions = array_merge($subs, array(
        'CO_NAME'   => $coName,
        'INVITE_URL' => Router::url(array(
                                    'plugin'     => null,
                                    'controller' => 'co_invites',
                                    'action'     => 'reply',
                                    $invite['CoInvite']['invitation']
                                   ),
                                   true)
      ));
      
      try {
        if($template) {
          if($subject) {
            $msgSubject = processTemplate($subject, $substitutions);
          } else {
            $msgSubject = _txt('em.invite.subject', array($coName));
          }
          
          $msgBody = processTemplate($template, $substitutions);
          
          // If this enrollment has a default email address set, use it, otherwise leave in the default for the site.
          if($fromEmail) {
            $email->from($fromEmail);
          }
          
          // If cc's or bcc's were set, convert to an array
          if($cc) {
            $email->cc(explode(',', $cc));
          }
          
          if($bcc) {
            $email->bcc(explode(',', $bcc));
          }
          
          $email->emailFormat('text')
                ->to($toEmail)
                ->subject($msgSubject)
                ->send($msgBody);
        } else {
          $viewVariables = array(
            'co_name'   => $coName,
            'invite_id' => $invite['CoInvite']['invitation']
          );
          
          $email->template('coinvite', 'basic')
                ->emailFormat('text')
                ->to($toEmail)
                ->viewVars($viewVariables)
                ->subject(_txt('em.invite.subject', array($coName)));

          // If this enrollment has a default email address set, use it, otherwise leave in the default for the site.
          if($fromEmail) {
            $email->from($fromEmail);
          }

         $email->send();
        }
      } catch(Exception $e) {
        throw new RuntimeException($e->getMessage());
      }
      
      // Create a history record
      
      if($emailAddressID) {
        $haction = ActionEnum::EmailAddressVerifyReqSent;
        $htxt = _txt('rs.ev.sent', array($toEmail));
      } else {
        $haction = ActionEnum::InvitationSent;
        $htxt = _txt('rs.inv.sent', array($toEmail));
      }
      
      try {
        $this->CoPerson->HistoryRecord->record($coPersonId,
                                               null,
                                               $orgIdentityID,
                                               $actorPersonId,
                                               $haction,
                                               $htxt);
      }
      catch(Exception $e) {
        throw new RuntimeException($e->getMessage());
      }
      
      return $this->id;
    } else {
      throw new RuntimeException(_txt('er.db.save'));
    }
  }
}
