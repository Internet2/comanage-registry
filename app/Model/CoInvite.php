<?php
/**
 * COmanage Registry CO Invite Model
 *
 * Copyright (C) 2010-13 University Corporation for Advanced Internet Development, Inc.
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
 * @copyright     Copyright (C) 2011-13 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.1
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */

App::uses('CakeEmail', 'Network/Email');

class CoInvite extends AppModel {
  // Define class name for cake
  public $name = "CoInvite";
  
  // Current schema version for API
  public $version = "1.0";
  
  // Add behaviors
  public $actsAs = array('Containable');
  
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
      'rule' => 'notEmpty',
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
    $args = array();
    $args['conditions']['CoInvite.invitation'] = $inviteId;
    
    // Start a transaction
    $dbc = $this->getDataSource();
    $dbc->begin();
    
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
          // Let CoPetition handle the relevant updates
          
          // We don't bother checking if confirm_email is still true, since it's not really
          // clear how we should handle the enrollment flow configuration being changed
          // in the middle of an enrollment.
          
          if($confirm) {
            // If a login identifier was provided, attach it to the org identity if not already present.
            
            if($loginIdentifier) {
              // Validate the identifier, even if null. (If null but authn was required, we'll
              // get an Exception, which will ultimately pass back up to a redirect.)
              
              try {
                $this->CoPetition->validateIdentifier($invite['CoPetition']['id'],
                                                      $loginIdentifier,
                                                      $invite['CoPetition']['enrollee_co_person_id']);
              }
              catch(RuntimeException $e) {
                // Re-throw the exception
                $dbc->rollback();
                throw new RuntimeException($e->getMessage());
              }
            }
            
            // Update status to Confirmed. updateStatus() will promote to PendingApproval or whatever
            
            try {
              $this->CoPetition->updateStatus($invite['CoPetition']['id'],
                                              StatusEnum::Confirmed,
                                              $invite['CoPetition']['enrollee_co_person_id']);
            }
            catch(Exception $e) {
              $dbc->rollback();
              throw new RuntimeException($e->getMessage());
            }
          } else {
            // Simply deny the petition. We're not authenticated, so we just assume the
            // enrollee CO Person is also the actor.
            
            try {
              $this->CoPetition->updateStatus($invite['CoPetition']['id'],
                                              StatusEnum::Denied,
                                              $invite['CoPetition']['enrollee_co_person_id']);
            }
            catch(Exception $e) {
              $dbc->rollback();
              throw new RuntimeException($e->getMessage());
            }
          }
          
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
          // We're actually verifying an org identity email address even though we're
          // getting to the EmailAddress object via CoPerson
          
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
              $this->CoPerson->EmailAddress->verify($orgId, null, $invite['CoInvite']['mail'], $invite['CoPetition']['enrollee_co_person_id']);
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
   * Process a message template, replacing parameters with respective values.
   * Note this function is for configured templates (ie: those loaded from the
   * database) and not for Cake templates (ie: those loaded from View/Emails).
   *
   * @since  COmanage Registry v0.8.2
   * @param  String Template text
   * @param  Array Array of View Variables, used to replace parameters
   * @return String Processed template
   */
  
  // XXX Revert this to protected when CoPetition::updateStatus gets refactored
  public function processTemplate($template, $viewVars) {
    $searchKeys = array("(@CO_NAME)",
                        "(@INVITE_URL)");
    $replaceVals = array($viewVars['co_name'],
                         Router::url(array('controller' => 'co_invites', 'action' => 'reply', $viewVars['invite_id']), true));
    
    return str_replace($searchKeys, $replaceVals, $template);
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
   * @return Integer CO Invitation ID
   * @throws RuntimeException
   */
  
  public function send($coPersonId, $orgIdentityID, $actorPersonId, $toEmail, $fromEmail=null, $coName, $subject=null, $template=null, $emailAddressID=null) {
    // Toss any prior invitations for $coPersonId to $toEmail
    
    try {
      $this->deleteAll(array('CoInvite.co_person_id' => $coPersonId,
                             'CoInvite.mail' => $toEmail));
    }
    catch(Exception $e) {
      throw new RuntimeException($e->getMessage());
    }
    
    $invite = array();
    $invite['CoInvite']['co_person_id'] = $coPersonId;
    $invite['CoInvite']['invitation'] = Security::generateAuthKey();
    $invite['CoInvite']['mail'] = $toEmail;
    // XXX make expiration time configurable
    // XXX date format may not be portable
    $invite['CoInvite']['expires'] = date('Y-m-d H:i:s', strtotime('+1 day'));
    if($emailAddressID) {
      $invite['CoInvite']['email_address_id'] = $emailAddressID;
    }
    
    $this->create($invite);
    
    if($this->save()) {
      // Try to send the invite
      
      // Set up and send the invitation via email
      $email = new CakeEmail('default');
      
      $viewVariables = array();
      $viewVariables['invite_id'] = $invite['CoInvite']['invitation'];
      $viewVariables['co_name'] = $coName;
      
      try {
        if($template) {
          if($subject) {
            $msgSubject = $this->processTemplate($subject, $viewVariables);
          } else {
            $msgSubject = _txt('em.invite.subject', array($coName));
          }
          
          $msgBody = $this->processTemplate($template, $viewVariables);

          // If this enrollment has a default email address set, use it, otherwise leave in the default for the site.
          if($fromEmail) {
            $email->from($fromEmail);
          }
          
          $email->emailFormat('text')
                ->to($toEmail)
                ->subject($msgSubject)
                ->send($msgBody);
        } else {
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
