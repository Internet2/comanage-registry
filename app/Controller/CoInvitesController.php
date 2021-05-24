<?php
/**
 * COmanage Registry CO Invite Controller
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

class CoInvitesController extends AppController {
  // We don't extend StandardController because there's so much unique stuff going on here
  public $name = "CoInvites";
  
  public $helpers = array('Time');
  
  public $paginate = array(
    'limit' => 25,
    'order' => array(
      'expires' => 'asc',
    )
  );
  
  public $uses = array('CoInvite', 'CoOrgIdentityLink', 'EmailAddress');
  
  // This controller needs a CO to be set, but only for send
  public $requires_co = false;
  
  /**
   * Handle RESTful send() request, mapped to add() by default.
   * See send() for details.
   *
   * @since  COmanage Registry v0.1
   */
  
  function add() {
    $this->send();
  }
  
  /**
   * Confirm the requested invite, requiring authentication
   * - precondition: $invite must exist, be valid, and attached to a valid CO person
   * - postcondition: CO Person status set to 'Active' and/or CO Petition updated
   * - postcondition: $inviteid deleted
   * - postcondition: Session flash message updated (HTML)
   *
   * @since  COmanage Registry v0.7
   * @param  Integer Invitation ID
   */
  
  public function authconfirm($inviteid) {
    // This behaves just like confirm(), except that authentication is required to get here.
    
    $this->process_invite($inviteid, true, $this->Session->read('Auth.User.username'));
  }
  
  /**
   * Verify the email address, requiring authentication
   * - precondition: $invite must exist, be valid, and attached to a valid CO person
   * - postcondition: Email Address set to valid
   * - postcondition: $inviteid deleted
   * - postcondition: Session flash message updated (HTML)
   *
   * @since  COmanage Registry v0.8.4
   * @param  Integer Invitation ID
   */
  
  public function authverify($inviteid) {
    // We basically do the same thing as authconfirm().
    
    $this->process_invite($inviteid, true, $this->Session->read('Auth.User.username'));
  }
  
  /**
   * Callback before other controller methods are invoked or views are rendered.
   * - postcondition: Auth component is configured 
   *
   * @since  COmanage Registry v0.1
   * @throws UnauthorizedException (REST)
   */
  
  function beforeFilter() {
    if($this->action == "send") {
      $this->requires_co = true;
    }
    
    // Since we're overriding, we need to call the parent to run the authz check
    parent::beforeFilter();
    
    // Allow invite handling to process without a login page
    $this->Auth->allow('confirm', 'decline', 'reply');
  }
  
  /**
   * Determine the CO ID based on some attribute of the request.
   * This method is intended to be overridden by model-specific controllers.
   *
   * @since  COmanage Registry v0.8.4
   * @return Integer CO ID, or null if not implemented or not applicable.
   * @throws InvalidArgumentException
   */
  
  protected function calculateImpliedCoId($data = null) {
    if($this->action == "add" && !empty($data['co_person_id'])) {
      // For historical reasons, CO ID is also passed in, but we need to ignore
      // it and lookup via the CO Person ID.

      $coId = $this->CoInvite->CoPerson->field('co_id',
                                               array('id' => $data['co_person_id']));

      if($coId) {
        return $coId;
      } else {
        throw new InvalidArgumentException(_txt('er.notfound',
                                                array(_txt('ct.co_people.1'),
                                                      filter_var($data['co_person_id'],FILTER_SANITIZE_SPECIAL_CHARS))));
      }
    }

    if($this->action == "confirm"
       || $this->action == "authconfirm"
       || $this->action == "reply") {
      // Identifier assignment requires the CO ID to be set, but since CO ID isn't
      // provided as an explicit parameter, beforeFilter can't find it.
      
      $args = array();
      $args['conditions']['CoInvite.invitation'] = $this->request->params['pass'][0];
      $args['joins'][0]['table'] = 'co_invites';
      $args['joins'][0]['alias'] = 'CoInvite';
      $args['joins'][0]['type'] = 'INNER';
      $args['joins'][0]['conditions'][0] = 'CoPerson.id=CoInvite.co_person_id';
      $args['contain'] = false;
      
      $coPerson = $this->CoInvite->CoPerson->find('first', $args);
      
      if(!empty($coPerson['CoPerson']['co_id'])) {
        return $coPerson['CoPerson']['co_id'];
      } else {
        throw new InvalidArgumentException(_txt('er.notfound',
                                                array(_txt('ct.co_invites.1'),
                                                      filter_var($this->request->params['pass'][0],FILTER_SANITIZE_SPECIAL_CHARS))));
      }
    }
    
    if($this->action == "send" && !empty($this->request->params['named']['copersonid'])) {
      $coId = $this->CoInvite->CoPerson->field('co_id',
                                               array('id' => $this->request->params['named']['copersonid']));
      
      if($coId) {
        return $coId;
      } else {
        throw new InvalidArgumentException(_txt('er.notfound',
                                                array(_txt('ct.co_people.1'),
                                                      filter_var($this->request->params['named']['copersonid'],FILTER_SANITIZE_SPECIAL_CHARS))));
      }
    }
    
    return null;
  }
  
  /**
   * Confirm the requested invite
   * - precondition: $invite must exist, be valid, and attached to a valid CO person
   * - postcondition: CO Person status set to 'Active' and/or CO Petition updated
   * - postcondition: $inviteid deleted
   * - postcondition: Session flash message updated (HTML)
   *
   * @since  COmanage Registry v0.1
   * @param  Integer Invitation ID
   */
  
  function confirm($inviteid) {
    $this->process_invite($inviteid, true);
  }
  
  /**
   * Decline the requested invite
   * - precondition: $invite must exist, be valid, and attached to a valid CO person
   * - postcondition: CO Person status set to 'Declined'
   * - postcondition: $inviteid deleted
   * - postcondition: Session flash message updated (HTML)
   *
   * @since  COmanage Registry v0.1
   * @param  Integer Invitation ID
   */
  
  function decline($inviteid) {
    $this->process_invite($inviteid, false);
  }
  
  /**
   * Handle RESTful confirm() or decline() request, mapped to index() by default RESTful behavior.
   * See process_invite() for additional information.
   * - precondition: $this->request->query holds invite ID and reply operation
   *
   * @since  COmanage Registry v0.1
   */
  
  function index() {
    // Since we're not doing a traditional save, we need to manually validate
    // the fields sent

    $fs = array();
    
    if(!isset($this->request->query['inviteid']))
      $fs['inviteid'] = _txt('er.notprov');
    if(isset($this->request->query['reply']))
    {
      if($this->request->query['reply'] != 'confirm'
         && $this->request->query['reply'] != 'decline')
        $fs['reply'] = _txt('er.reply.unk');
    }
    else
      $fs['reply'] = _txt('er.notprov');
        
    if(count($fs) > 0)
    {
      $this->Api->restResultHeader(400, "Invalid Fields");
      $this->set('invalid_fields', $fs);
    }
    else
      $this->process_invite($this->request->query['inviteid'],
                            ($this->request->query['reply'] == 'confirm'));
  }

  /**
   * Authorization for this Controller, called by Auth component
   * - precondition: Session.Auth holds data used for authz decisions
   * - postcondition: $permissions set with calculated permissions
   *
   * @since  COmanage Registry v0.1
   * @return Array Permissions
   */
  
  function isAuthorized() {
    $roles = $this->Role->calculateCMRoles();
    
    $managed = false;
    
    if(!empty($roles['copersonid'])
       && $this->action == 'send'
       && !empty($this->request->params['named']['copersonid'])) {
      $managed = $this->Role->isCoOrCouAdminForCoPerson($roles['copersonid'],
                                                        $this->request->params['named']['copersonid']);
    }
    
    // Construct the permission set for this user, which will also be passed to the view.
    $p = array();
    
    // Determine what operations this user can perform
    
    // Send an invite? (REST only)
    $p['add'] = $roles['apiuser'] && ($roles['cmadmin'] || $roles['coadmin']);
    
    // Confirm an invite? (HTML, auth required)
    $p['authconfirm'] = true;
    
    // Verify an email address?
    $p['authverify'] = true;

    // Confirm an invite? (HTML only)
    $p['confirm'] = true;
    
    // Decline an invite? (HTML only)
    $p['decline'] = true;
    
    // Confirm or decline an invite? (REST only)
    $p['index'] = $roles['apiuser'] && ($roles['cmadmin'] || $roles['coadmin']);
    
    // Reply to an invite? (HTML only)
    $p['reply'] = true;
    
    // Send an invite? (HTML only)
    
    $p['send'] = ($roles['cmadmin']
                  || $roles['coadmin'] || ($managed && $roles['couadmin']));
    
    // Request verification of an email address?
    // This needs to correlate with EmailAddressesController
    $p['verifyEmailAddress'] = (!empty($this->request->params['named']['email_address_id'])
                                ? ($roles['cmadmin']
                                   ||
                                   $this->Role->canRequestVerificationOfEmailAddress($roles['copersonid'],
                                                                                     $this->request->params['named']['email_address_id']))
                                : false);
    
    $this->set('permissions', $p);
    return $p[$this->action];
  }
  
  /**
   * Process an invitation reply.
   * - precondition: invite must exist, be valid, and attached to a valid CO person
   * - postcondition: CO Person status set
   * - postcondition: $inviteid deleted
   * - postcondition: Session flash message updated (HTML) or HTTP status returned (REST)
   *
   * @since  COmanage Registry v0.1
   * @param  Integer ID invitation
   * @param  Boolean If true, confirm the invitation; if false, decline it
   * @param  String Authenticated login identifier, if set
   */
  
  protected function process_invite($inviteid, $confirm, $loginIdentifier=null) {
    // Grab the invite info in case we need it later (we're about to delete it)
    $args = array();
    $args['conditions']['CoInvite.invitation'] = $inviteid;
    $args['contain'] = array('CoPetition');
    
    $invite = $this->CoInvite->find('first', $args);
    
    if(!$this->request->is('restful')) {
      // Set page title
      $this->set('title_for_layout', _txt('op.inv.reply'));
    }
    
    try {
      $this->CoInvite->processReply($inviteid, $confirm, $loginIdentifier);
    }
    catch(InvalidArgumentException $e) {
      if($this->request->is('restful')) {
        if($e->getMessage() == _txt('er.inv.nf')) {
          $this->Api->restResultHeader(404, "CoInvite Unknown");
        } else {
          $this->Api->restResultHeader(400, "CoPerson Unknown");
        }
      } else {
        $this->Flash->set($e->getMessage(), array('key' => 'error'));
        $this->redirect("/");
        return;
      }
    }
    catch(OutOfBoundsException $e) {
      if($this->request->is('restful')) {
        $this->Api->restResultHeader(403, "Expired");
      } else {
        $this->Flash->set($e->getMessage(), array('key' => 'error'));
        $this->redirect("/");
        return;
      }
    }
    catch(Exception $e) {
      if($e->getMessage() == _txt('er.auth')) {
        // This invitation requires authentication, so issue a redirect
        $this->redirect(array('action' => 'authconfirm', $inviteid));
        return;
      } else {
        if($this->request->is('restful')) {
          $this->Api->restResultHeader(500, "Other Error");
        } else {
          $this->Flash->set($e->getMessage(), array('key' => 'error'));
          $this->redirect("/");
          return;
        }
      }
    }
    
    if($this->request->is('restful')) {
      $this->Api->restResultHeader(200, "Deleted");
    } else {
      // If the invite was attached to a CO petition, re-enter the enrollment flow
      
      if(!empty($invite['CoPetition']['id'])) {
        $redirect = array(
          'controller' => 'co_petitions',
          'action'     => 'processConfirmation',
          $invite['CoPetition']['id'],
          'confirm'    => ($confirm ? "true" : "false")
        );
        
        // For now, we always create an enrollee token. Pretty much every currently
        // supported enrollment flow involves a new Org Identity for the CO, so
        // there won't be an existing CO Person identity linked to use instead.
        // At some point (ie: additional Role enrollment; CO-310) this will change.
        
        $token = Security::generateAuthKey();
        
        $this->CoInvite->CoPetition->id = $invite['CoPetition']['id'];
        $this->CoInvite->CoPetition->saveField('enrollee_token', $token);
        
        $redirect['token'] = $token;
        
        $this->redirect($redirect);
      } elseif(!empty($invite['CoInvite']['email_address_id'])) {
        $this->Flash->set($confirm ? _txt('rs.ev.ver') : _txt('rs.ev.cxl'), array('key' => 'success'));
      } else {
        $this->Flash->set($confirm ? _txt('rs.inv.conf') : _txt('rs.inv.dec'), array('key' => 'success'));
      }
      
      // If a login identifier was provided, force a logout
      if($loginIdentifier) {
        $this->redirect("/auth/logout");
      } else {
        $this->redirect("/");
      }
    }
  }

  /**
   * Find the requested invite and prompt the user to confirm or decline.
   * - precondition: $inviteid must exist and not expired or validated
   * - precondition: The associated person must be in invited state
   * - postcondition: $invite set on success
   * - postcondition: $invitee set to CO Person on success
   * - postcondition: Session flash message updated (HTML) on suitable error
   *
   * @since  COmanage Registry v0.1
   * @param Integer ID invitation
   */
  
  function reply($inviteid) {
    $args = array();
    $args['conditions']['CoInvite.invitation'] = $inviteid;
    $args['contain'] = array('CoPetition', 'EmailAddress');
    
    $invite = $this->CoInvite->find('first', $args);
    
    if(!$invite) {
      $this->Flash->set(_txt('er.inv.nf'), array('key' => 'error'));
      // XXX what if this->restful?
    } else {
      // Database foreign key constraints should prevent inconsistencies here, so extra
      // error checking shouldn't be needed
      $args = array();
      $args['conditions']['CoPerson.id'] = $invite['CoInvite']['co_person_id'];
      $args['contain'] = array('Co', 'PrimaryName');
      
      $invitee = $this->CoInvite->CoPerson->find('first', $args);
      $coName = $this->CoInvite->CoPerson->Co->field('name', array('Co.id' => $invitee['CoPerson']['co_id']));
      
      $this->set('invite', $invite);
      $this->set('invitee', $invitee);
      
      if(!$this->request->is('restful')) {
        // Set page title
        if(!empty($invite['CoInvite']['email_address_id'])) {
          $this->set('title_for_layout', _txt('fd.ev.verify', array($invite['EmailAddress']['mail'])));
        } else {
          $this->set('title_for_layout', _txt('fd.inv.to', array($coName)));
        }
      }
      
      // Record that the invitee clicked the link
      $this->CoInvite->CoPerson->HistoryRecord->record($invitee['CoPerson']['id'],
                                                       null,
                                                       null,
                                                       // For now we just assume it's the CO Person?
                                                       $invitee['CoPerson']['id'],
                                                       ActionEnum::InvitationViewed);
      
      // We also want to pull the enrollment flow and petition attributes, if appropriate
      
      if(isset($invite['CoPetition']['id'])) {
        // It turn out this is a bit complicated due to how ChangelogBehavior is
        // applied to the relevant models. There is overlap here with code from
        // CoPetitionsController.
        
        // Start by figuring out the enrollment flow and pulling all the attributes
        // defined for that flow, including archived.
        
        $args = array();
        $args['conditions']['CoEnrollmentFlow.id'] = $invite['CoPetition']['co_enrollment_flow_id'];
        $args['contain'][] = 'CoEnrollmentAttribute';
        
        $enrollmentFlow = $this->CoInvite->CoPetition->CoEnrollmentFlow->find('first', $args);
        
        // Record the view to the petition history as well
        $this->CoInvite->CoPetition->CoPetitionHistoryRecord->record($invite['CoPetition']['id'],
                                                                     $invitee['CoPerson']['id'],
                                                                     PetitionActionEnum::InviteViewed);
        
        // Before we do anything else, check the verification mode. If it's Automatic,
        // we simply redirect into confirm or authconfirm as appropriate. Otherwise,
        // we want to render the confirmation page. (If the mode is now None, ie: the
        // admin changed the mode for this enrollment flow, we could probably act like
        // Automatic mode, but for now we'll leave it as Review.)
        
        if(isset($enrollmentFlow['CoEnrollmentFlow']['email_verification_mode'])
           && $enrollmentFlow['CoEnrollmentFlow']['email_verification_mode'] == VerificationModeEnum::Automatic) {
          $this->redirect(array(
            'controller' => 'co_invites',
            'action'     => $enrollmentFlow['CoEnrollmentFlow']['require_authn'] ? 'authconfirm' : 'confirm',
            $inviteid
          ));
        }
        
        // Not in Automatic mode, so prep for the view to render
        
        $this->set('co_enrollment_flow', $enrollmentFlow);
        
        $plugins = $this->loadAvailablePlugins('confirmer', 'simple');
        
        // Make sure $plugins is in alphabetical order so we have some sort of order.
        sort($plugins);
        
        if(!empty($plugins)) {
          // Walk through the identified plugins until one decides it wants to handle the request.
          
          foreach($plugins as $plugin) {
            if($this->$plugin->willHandle((!empty($invitee['Co']['id']) ? $invitee['Co']['id'] : null),
                                   (!empty($invite['CoInvite']['id']) ? $invite['CoInvite'] : null),
                                   (!empty($invite['CoPetition']['id']) ? $invite['CoPetition'] : null))) {
              // Issue a redirect into the plugin
              
              $target = array();
              $target['plugin'] = Inflector::underscore($plugin);
              $target['controller'] = Inflector::tableize($plugin);
              $target['action'] = 'reply';
              $target[] = $inviteid;
              
              $this->redirect($target);
            }
          }
        }
        
        $enrollmentAttributes = $this->CoInvite
                                     ->CoPetition
                                     ->CoEnrollmentFlow
                                     ->CoEnrollmentAttribute
                                     ->enrollmentFlowAttributes($invite['CoPetition']['co_enrollment_flow_id'], array(), true);
        
        // Pull the petition metadata.
        
        $pArgs = array();
        $pArgs['conditions']['CoPetition.id'] = $invite['CoPetition']['id'];
        $pArgs['contain'][] = 'CoPetitionHistoryRecord';
        $pArgs['contain']['CoPetitionHistoryRecord']['ActorCoPerson'] = 'PrimaryName';
        
        $petition = $this->CoInvite->CoPetition->find('all', $pArgs);
        
        $this->set('co_petitions', $petition);
        
        // For viewing a petition, we want the attributes defined at the time the
        // petition attributes were submitted. This turns out to be somewhat
        // complicated to determine, so we hand it off for filtering.
        
        // We need a slightly different set of data here. Strictly speaking we
        // should do a select distinct, but practically it won't matter since
        // all petition attributes for a given enrollment attribute will have
        // approximately the same created time.
        
        // This is duplicated from CoPetitionsController.
        
        // We pull the same attributes twice, because we need them in two different
        // formats. (This could presumably be refactored at some point.) First, grab
        // them so we can filter them historically.
        
        $vArgs = array();
        $vArgs['conditions']['CoPetitionAttribute.co_petition_id'] = $invite['CoPetition']['id'];
        $vArgs['fields'] = array(
          'CoPetitionAttribute.co_enrollment_attribute_id',
          'CoPetitionAttribute.created'
        );
        $vAttrs = $this->CoInvite->CoPetition->CoPetitionAttribute->find("list", $vArgs);
        
        $enrollmentAttributes = $this->CoInvite->CoPetition->filterHistoricalAttributes($enrollmentAttributes, $vAttrs);
        
        // Now pull the attributes again, but this time in the format used by the view.
        
        $vArgs = array();
        $vArgs['conditions']['CoPetitionAttribute.co_petition_id'] = $invite['CoPetition']['id'];
        $vArgs['fields'] = array(
          'CoPetitionAttribute.attribute',
          'CoPetitionAttribute.value',
          'CoPetitionAttribute.co_enrollment_attribute_id'
        );
        
        $vAttrs = $this->CoInvite->CoPetition->CoPetitionAttribute->find("list", $vArgs);
        
        $this->set('co_petition_attribute_values', $vAttrs);
        
        $this->set('co_enrollment_attributes', $enrollmentAttributes);
      }
    }
  }
  
  /**
   * Send an invitation to the CO person $cpid.
   * - precondition: $this->request->params holds CO Person ID to invite and CO to invite to
   * - precondition: $copersonid must exist
   * - postcondition: Email invitation sent to address on record (XXX not implemented)
   * - postcondition: $copersonid set to 'Invited' status
   * - postcondition: $cur_co set to current CO on success
   * - postcondition: $invite set on success (HTML)
   * - postcondition: $invitee set to CO Person on success (HTML)
   * - postcondition: $co_invite_id set on success (REST)
   * - postcondition: Session flash message updated (HTML) or HTTP status returned (REST)
   *
   * @since  COmanage Registry v0.1
   */
  
  function send() {
    if($this->request->is('restful')) {
      $this->Api->parseRestRequestDocument();
      
      $data = $this->Api->getData();
      
      if(empty($data)) {
        $this->Api->restResultHeader(400, "Bad Request");
        return;
      }
      
      // Since we're not doing a traditional save, we need to manually validate
      // the fields sent
      
      $fs = array();
      
      try {
        $coId = $this->CoInvite->CoPerson->findCoForRecord($data['co_person_id']);
      }
      catch(InvalidArgumentException $e) {
        $fs['CoId'] = _txt('er.co.unk');
      }
      
      if(!isset($data['co_person_id'])) {
        $fs['CoPersonId'] = _txt('er.notprov');
      }
      
      if(count($fs) > 0) {
        $this->Api->restResultHeader(400, "Invalid Fields");
        $this->set('invalid_fields', $fs);
        return;
      }
      
      $cpid = $data['co_person_id'];
    } else {
      // Set page title
      $this->set('title_for_layout', _txt('op.inv.send'));
      
      $cpid = $this->request->params['named']['copersonid'];
    }
    
    // Retrieve info about the Person
    
    $args = array();
    $args['conditions']['CoPerson.id'] = $cpid;
    $args['contain'] = array('PrimaryName');
    
    $cop = $this->CoInvite->CoPerson->find('first', $args);

    if($cop)
    {
      // Find the associated Org Identity to get an email address
      
// XXX fix this getting link to get org identity
// Use containable to pull attached org identities
      // This assumes one CO Person has exactly one CO Org Identity Link,
      // which might be true now but probably won't always be true
      
      $args = array();
      $args['conditions']['CoOrgIdentityLink.co_person_id'] = $cpid;
      $args['contain'] = false;
      
      $lnk = $this->CoInvite->CoPerson->CoOrgIdentityLink->find('first', $args);
      
      if(isset($lnk)) {
        $args = array();
        $args['conditions']['OrgIdentity.id'] = $lnk['CoOrgIdentityLink']['org_identity_id'];
        $args['contain'] = array('EmailAddress', 'PrimaryName');
        
        $orgp = $this->CoInvite->CoPerson->CoOrgIdentityLink->OrgIdentity->find('first', $args);
      }
      
      // XXX We only check org person. What if Org Identity has no address, but is officially
      // sourced (eg: via LDAP)?
      
      if(isset($orgp) && count($orgp['EmailAddress']) > 0)
      {
        // XXX There could be multiple email addresses, we'll use the first one
        // (but we could allow one to be selected)
        
        try {
          $this->CoInvite->send($cpid,
                                $orgp['OrgIdentity']['id'],
                                $this->Session->read('Auth.User.co_person_id'),
                                $orgp['EmailAddress'][0]['mail'],
                                null,
                                $this->cur_co['Co']['name'],
                                null,
                                null,
                                null,
                                $this->CoInvite->CoPerson->Co->CoSetting->getInvitationValidity($this->cur_co['Co']['id']));
          
          $this->Flash->set(_txt('em.invite.ok', array($orgp['EmailAddress'][0]['mail'])), array('key' => 'success'));
        }
        catch(Exception $e) {
          $this->Flash->set($e->getMessage(), array('key' => 'error'));
        }
      
        // Set CO Person status to I
        // XXX probably don't want to do this if status = A.  May need a new password reset status.
        
        $this->CoInvite->CoPerson->id = $cpid;
        
        if($this->CoInvite->CoPerson->saveField('status', 'I'))
        {
          if($this->request->is('restful'))
          {
            // $this->Api->restResultHeader(201, "Sent");
            $this->Api->restResultHeader(501, "Not Implemented");
            $this->set('co_invite_id', $this->CoInvite->id);
          }
          else
          {
            $this->set('cur_co', $this->cur_co);
            $this->set('invite', $this->CoInvite->findById($this->CoInvite->id));
            $this->set('invitee', $cop);
          }
        }
        else
        {
          if($this->request->is('restful'))
          {
            $this->Api->restResultHeader(400, "Invalid Fields");
            $this->set('invalid_fields', $this->CoInvite->invalidFields());
          }
          else
            $this->Flash->set($this->fieldsErrorToString($this->CoInvite->CoPerson->invalidFields()), array('key' => 'error'));
        }
      }
      else
      {
        if($this->request->is('restful')) {
          $this->Api->restResultHeader(400, "No Email Address");
        } else {
          $this->Flash->set(_txt('er.orgp.nomail', array(generateCn($orgp['PrimaryName']), $orgp['OrgIdentity']['id'])), array('key' => 'error'));
          $this->redirect(array('controller' => 'co_people', 'action' => 'index', 'co' => $this->cur_co['Co']['id']));
        }
      }
    }
    else
    {
      if($this->request->is('restful')) {
        $this->Api->restResultHeader(400, "Invalid Fields");
        $this->set('invalid_fields', array('CoPersonId' => _txt('er.cop.unk')));
      } else {
        $this->Flash->set(_txt('er.cop.nf', array($cpid)), array('key' => 'error'));
      }
    }

    $debug = Configure::read('debug');
    if(!$debug) {
      // Redirect to My Population when no debugging so that 
      // user sees flash message that email was sent with invitation.
      // Otherwise when debugging user will see link to the invitation
      // to help debugging and testing.
      $nextPage = array('controller' => 'co_people',
                        'action'     => 'index',
                        'co'         => $this->cur_co['Co']['id']);
      $this->redirect($nextPage);
    }
  }
  
  /**
   * Send an email address verification request.
   * - precondition: $this->request->params holds Email Address ID to verify
   * - postcondition: Email invitation sent to address
   * - postcondition: Session flash message updated (HTML) or HTTP status returned (REST)
   *
   * @since COmanage Registry v0.8.4
   * @todo Add rest support
   */
  
  public function verifyEmailAddress() {
    if(!empty($this->request->params['named']['email_address_id'])) {
      $args = array();
      $args['conditions']['EmailAddress.id'] = $this->request->params['named']['email_address_id'];
      $args['contain'] = false;
      
      $ea = $this->EmailAddress->find('first', $args);
      
      if(!empty($ea)) {
        // We currently need a CO Person ID, even if verifying an org identity email address.
        // CoInvite stores the CO Person ID and uses the Org Identity ID in generating history.
        // For now, we'll just map using co_org_identity_links and hope we pull the right
        // record. This should get fixed as part of CO-753.
        
        $largs = array();
        $largs['contain'] = false;
        
        if(!empty($ea['EmailAddress']['co_person_id'])) {
          $largs['conditions']['CoOrgIdentityLink.co_person_id'] = $ea['EmailAddress']['co_person_id'];
        } elseif(!empty($ea['EmailAddress']['org_identity_id'])) {
          $largs['conditions']['CoOrgIdentityLink.org_identity_id'] = $ea['EmailAddress']['org_identity_id'];
        }
        
        $lnk = $this->CoOrgIdentityLink->find('first', $largs);
        
        if(!empty($lnk)) {
          if($this->request->is('restful')) {
             // XXX implement this (CO-754)
            throw new RuntimeException("Not implemented");
          } else {
            try {
              $this->CoInvite->send($lnk['CoOrgIdentityLink']['co_person_id'],
                                    $lnk['CoOrgIdentityLink']['org_identity_id'],
                                    $this->Session->read('Auth.User.co_person_id'),
                                    $ea['EmailAddress']['mail'],
                                    null, // use default from address
                                    (!empty($this->cur_co['Co']['name']) ? $this->cur_co['Co']['name'] : _txt('er.unknown')),
                                    _txt('em.invite.subject.ver'),
                                    _txt('em.invite.body.ver'),
                                    $ea['EmailAddress']['id']);
              
              $this->set('vv_co_invite', $this->CoInvite->findById($this->CoInvite->id));
              $this->set('vv_recipient', $ea);
              
              $this->Flash->set(_txt('rs.ev.sent', array($ea['EmailAddress']['mail'])), array('key' => 'success'));
            }
            catch(Exception $e) {
              $this->Flash->set($e->getMessage(), array('key' => 'error'));
            }
            
            $debug = Configure::read('debug');
            
            if(!$debug) {
              if(!empty($ea['EmailAddress']['co_person_id'])) {
                // Redirect to the CO Person view
                $nextPage = array('controller' => 'co_people',
                                  'action'     => 'canvas',
                                  $lnk['CoOrgIdentityLink']['co_person_id']);
              } elseif(!empty($ea['EmailAddress']['org_identity_id'])) {
                // Redirect to the CO Person view
                $nextPage = array('controller' => 'org_identities',
                                  'action'     => 'edit',
                                  $lnk['CoOrgIdentityLink']['org_identity_id']);
              }
              
              $nextPage['tab'] = 'email';
              
              $this->redirect($nextPage);
            }
          }
        } else {
          $this->Flash->set(_txt('er.person.noex'), array('key' => 'error'));
        }
      } else {
        $this->Flash->set(_txt('er.notfound',
                               array(_txt('ct.email_addresses.1'),
                                     filter_var($this->request->params['named']['email_address_id'],FILTER_SANITIZE_SPECIAL_CHARS))),
                          array('key' => 'error'));
      }
    } else {
      $this->Flash->set(_txt('er.notprov.id', array(_txt('ct.email_addresses.1'))), array('key' => 'error'));
    }
  }

  /**
   * Determine the requested Enrollment Flow ID.
   *
   * @since  COmanage Registry v3.3
   * @return Integer CO Enrollment Flow ID if found, or -1 otherwise
   */

  function enrollmentFlowID() {
    // Get the inviteId
    $inviteId = !empty($this->request->params["pass"][0]) ? $this->request->params["pass"][0] : '';
    // Grab the invite info in case we need it later (we're about to delete it)
    $args = array();
    $args['conditions']['CoInvite.invitation'] = $inviteId;
    $args['contain'] = array('CoPetition');

    $invite = $this->CoInvite->find('first', $args);
    $efId = !empty($invite["CoPetition"]["co_enrollment_flow_id"]) ? $invite["CoPetition"]["co_enrollment_flow_id"] : -1;
    return $efId;
  }
}
