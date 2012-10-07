<?php
/**
 * COmanage Registry CO Invite Model
 *
 * Copyright (C) 2010-12 University Corporation for Advanced Internet Development, Inc.
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
 * @copyright     Copyright (C) 2011-12 University Corporation for Advanced Internet Development, Inc.
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
  public $belongsTo = array("CoPerson");   // An invitation belongs to a CO Person
  
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
      'required' => false
    ),
    'expires' => array(
      'rule' => '/.*/',  // The 'date' rule is too constraining
      'required' => false
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
  
  function processReply($inviteId, $confirm, $loginIdentifier=null) {
    $args = array();
    $args['conditions']['CoInvite.invitation'] = $inviteId;
    
    // Start a transaction
    $dbc = $this->getDataSource();
    $dbc->begin();
    
    $invite = $this->find('first', $args);
    
    if($invite) {
      // Check invite validity
      
      if(time() < strtotime($invite['CoInvite']['expires'])) {
        if(isset($invite['CoPetition']['id'])) {
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
            
            // Update status to Approved. If this petition requires approval, updateStatus()
            // will detect that and change the status to PendingApproval instead.
            
            try {
              $this->CoPetition->updateStatus($invite['CoPetition']['id'],
                                              StatusEnum::Approved,
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
              $this->CoPerson->EmailAddress->verify($orgId, $invite['CoInvite']['mail'], $invite['CoPetition']['enrollee_co_person_id']);
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
        $this->delete($invite['CoInvite']['id']);
        // Commit here, don't roll back!
        $dbc->commit();
        throw new OutOfBoundsException(_txt('er.inv.exp'));
      }
      
      // Create a history record
      
      try {
        $this->CoPerson->HistoryRecord->record($invite['CoInvite']['co_person_id'],
                                               isset($invite['CoPetition']['enrollee_co_person_role_id']) ? $invite['CoPetition']['enrollee_co_person_role_id'] : null,
                                               isset($invite['CoPetition']['enrollee_org_identity_id']) ? $invite['CoPetition']['enrollee_org_identity_id'] : null,
                                               $invite['CoPetition']['enrollee_co_person_id'],
                                               ($confirm ? ActionEnum::InvitationConfirmed : ActionEnum::InvitationDeclined),
                                               _txt(($confirm ? 'rs.inv.conf-a' : 'rs.inv.dec-a'), array($invite['CoInvite']['mail'])));
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
   * @return Integer CO Invitation ID
   * @throws RuntimeException
   */
  
  function send($coPersonId, $orgIdentityID, $actorPersonId, $toEmail, $fromEmail=null, $coName) {
    // Toss any prior invitations for $coPersonId to $toEmail
    
    $this->deleteAll(array('co_person_id' => $coPersonId,
                           'mail' => $toEmail));
    
    $invite = array();
    $invite['CoInvite']['co_person_id'] = $coPersonId;
    $invite['CoInvite']['invitation'] = Security::generateAuthKey();
    $invite['CoInvite']['mail'] = $toEmail;
    // XXX make expiration time configurable
    // XXX date format may not be portable
    $invite['CoInvite']['expires'] = date('Y-m-d H:i:s', strtotime('+1 day'));
    
    $this->create($invite);
    
    if($this->save()) {
      // Try to send the invite
      
      // Set up and send the invitation via email
      $email = new CakeEmail('default');
      
      $viewVariables = array();
      $viewVariables['invite_id'] = $invite['CoInvite']['invitation'];
      $viewVariables['co_name'] = $coName;
      
      try {
        $email->template('coinvite', 'basic')
              ->emailFormat('text')
              ->to($toEmail)
              ->viewVars($viewVariables)
              ->subject(_txt('em.invite.subject', array($coName)));
        
        // If this enrollment has a default email address set, use it, otherwise leave in the default for the site.
        if($fromEmail) {
          $email->from($fromEmail);
        }
        
        // Send the email
        $email->send();
      } catch(Exception $e) {
        throw new RuntimeException($e->getMessage());
      }
      
      // Create a history record
      
      try {
        $this->CoPerson->HistoryRecord->record($coPersonId,
                                               null,
                                               $orgIdentityID,
                                               $actorPersonId,
                                               ActionEnum::InvitationSent,
                                               _txt('rs.inv.sent', array($toEmail)));
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
