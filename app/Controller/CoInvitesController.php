<?php
/**
 * COmanage Registry CO Invite Controller
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
 * @copyright     Copyright (C) 2010-12 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.1
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */

class CoInvitesController extends AppController {
  // We don't extend StandardController because there's so much unique stuff going on here
  public $name = "CoInvites";
  
  public $paginate = array(
    'limit' => 25,
    'order' => array(
      'expires' => 'asc',
    )
  );
  
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
   * Callback before other controller methods are invoked or views are rendered.
   * - postcondition: Auth component is configured 
   *
   * @since  COmanage Registry v0.1
   * @throws UnauthorizedException (REST)
   */
  
  function beforeFilter() {
    if($this->action == "send")
      $this->requires_co = true;

    // Since we're overriding, we need to call the parent to run the authz check
    parent::beforeFilter();
    
    // Allow invite handling to process without a login page
    $this->Auth->allow('confirm', 'decline', 'reply');
  }
  
  /**
   * Confirm the requested invite
   * - precondition: $invite must exist, be valid, and attached to a valid CO person
   * - postcondition: CO Person status set to 'Active'
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
      $this->restResultHeader(400, "Invalid Fields");
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
    $cmr = $this->calculateCMRoles();
    
    // Construct the permission set for this user, which will also be passed to the view.
    $p = array();
    
    // Determine what operations this user can perform
    
    // Send an invite? (REST only)
    $p['add'] = $cmr['apiuser'];

    // Confirm an invite? (HTML only)
    $p['confirm'] = true;
    
    // Decline an invite? (HTML only)
    $p['decline'] = true;
    
    // Confirm or decline an invite? (REST only)
    $p['index'] = $cmr['apiuser'];
    
    // Reply to an invite? (HTML only)
    $p['reply'] = true;
    
    // Send an invite? (HTML only)
    $p['send'] = ($cmr['cmadmin'] || $cmr['coadmin'] || !empty($cmr['couadmin']));

    $this->set('permissions', $p);
    return($p[$this->action]);
  }
  
  /**
   * Process an invitation reply.
   * - precondition: invite must exist, be valid, and attached to a valid CO person
   * - postcondition: CO Person status set
   * - postcondition: $inviteid deleted
   * - postcondition: Session flash message updated (HTML) or HTTP status returned (REST)
   *
   * @since  COmanage Registry v0.1
   * @param Integer ID invitation
   * @param Boolean If true, confirm the invitation; if false, decline it
   */
  
  function process_invite($inviteid, $confirm) {
    if(!$this->restful)
    {
      // Set page title
      $this->set('title_for_layout', _txt('op.inv.reply'));
    }
    
    $invite = $this->CoInvite->findByInvitation($inviteid);
    
    if(!$invite)
    {
      if($this->restful)
      {
        $this->restResultHeader(404, "CoInvite Unknown");
        return;
      }
      else
        $this->Session->setFlash(_txt('er.inv.nf'), '', array(), 'error');
    }
    else
    {
      // Check invite validity
      
      if(time() < strtotime($invite['CoInvite']['expires']))
      {
        // Update CO Person
        
        $this->CoInvite->CoPerson->id = $invite['CoPerson']['id'];
        
        if($this->CoInvite->CoPerson->saveField('status', $confirm ? 'A' : 'X'))
        {
          if($this->restful)
            $this->restResultHeader(200, "Deleted");
          else
            $this->Session->setFlash($confirm ? _txt('rs.inv.conf') : _txt('rs.inv.dec'), '', array(), 'success');
        }
        else
        {
          if($this->restful)
            $this->restResultHeader(400, "CoPerson Unknown");
          else
            $this->Session->setFlash(_txt('er.cop.nf', $invite['CoPerson']['id']), '', array(), 'error');
        }
      }
      else
      {
        if($this->restful)
          $this->restResultHeader(403, "Expired");
        else
          $this->Session->setFlash(_txt('er.inv.exp'), '', array(), 'error');
      }

      // Toss the invite

      $this->CoInvite->delete($invite['CoInvite']['id']);
    }
  }

  /**
   * Find the requested invite and prompt the user to confirm or decline.
   * - precondition: $inviteid must exist and not expired or validated
   * - precondition: The associated person must be in invited state
   * - postcondition: $cur_co set to current CO on success
   * - postcondition: $invite set on success
   * - postcondition: $invitee set to CO Person on success
   * - postcondition: Session flash message updated (HTML) on suitable error
   *
   * @since  COmanage Registry v0.1
   * @param Integer ID invitation
   */
  
  function reply($inviteid) {
    if(!$this->restful)
    {
      // Set page title
      $this->set('title_for_layout', _txt('op.inv.reply'));
    }

    $invite = $this->CoInvite->findByInvitation($inviteid);
    
    if(!$invite)
      $this->Session->setFlash(_txt('er.inv.nf'), '', array(), 'error');
    else
    {
      // Database foreign key constraints should prevent inconsistencies here, so extra
      // error checking shouldn't be needed
      
      $invitee = $this->CoInvite->CoPerson->findById($invite['CoInvite']['co_person_id']);
      $co = $this->CoInvite->CoPerson->Co->findById($invitee['CoPerson']['co_id']);
      
      $this->set('cur_co', $co);
      $this->set('invite', $invite);
      $this->set('invitee', $invitee);
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
   * @param Integer ID invitation
   */
  
  function send() {
    if($this->restful)
    {
      $data = $this->convertRestPost();
      
      if(!$data)
        // || !$this->checkPost())  // Our request format doesn't match our schema, so skip checkPost
        return;
      
      if(empty($data))
      {
        $this->restResultHeader(400, "Bad Request");
        return;
      }
      else
      {
        // Since we're not doing a traditional save, we need to manually validate
        // the fields sent

        $fs = array();
          
        if(isset($data['CoInvite']['co_id']))
        {
          // beforeFilter will check the CO for HTML, but not for REST
          
          $this->cur_co = $this->CoInvite->CoPerson->Co->findById($data['CoInvite']['co_id']);
          
          if(!$this->cur_co)
            $fs['CoId'] = _txt('er.co.unk');
        }
        else
          $fs['CoId'] = _txt('er.notprov');
        if(!isset($data['CoInvite']['co_person_id']))
          $fs['CoPersonId'] = _txt('er.notprov');
        
        if(count($fs) > 0)
        {
          $this->restResultHeader(400, "Invalid Fields");
          $this->set('invalid_fields', $fs);
          return;
        }

        $coid = $data['CoInvite']['co_id'];
        $cpid = $data['CoInvite']['co_person_id'];
      }
    }
    else
    {
      // Set page title
      $this->set('title_for_layout', _txt('op.inv.send'));

      $coid = $this->request->params['named']['co'];
      $cpid = $this->request->params['named']['copersonid'];
    }
    
    // Retrieve info about the Person
    
    $cop = $this->CoInvite->CoPerson->findById($cpid);

    if($cop)
    {
      // Toss any prior invitations for $cpid

      $this->CoInvite->deleteAll(array("co_person_id" => $cpid));

      // Find the associated Org Identity to get an email address
      
// XXX fix this getting link to get org identity
      // This assumes one CO Person has exactly one CO Org Identity Link,
      // which might be true now but probably won't always be true

      $lnk = $this->CoInvite->CoPerson->CoOrgIdentityLink->findByCoPersonId($cpid);
      
      if(isset($lnk))
        $orgp = $this->CoInvite->CoPerson->CoOrgIdentityLink->OrgIdentity->findById($lnk['CoOrgIdentityLink']['org_identity_id']);
      
      // XXX We only check org person. What if Org Identity has no address, but is officially
      // sourced (eg: via LDAP)?
      
      if(isset($orgp) && count($orgp['EmailAddress']) > 0)
      {
        // XXX There could be multiple email addresses, we'll use the first one
        // (but could allow the inviter to select one)
        
        // XXX make expiration time configurable      
        $invite = array("CoInvite" => array('co_person_id' => $cpid,
                                            'invitation' => Security::generateAuthKey(),
                                            'mail' => $orgp['EmailAddress'][0]['mail'],
                                            'expires' => date('Y-m-d H:i:s', strtotime('+1 day'))));  // XXX date format may not be portable

        $this->CoInvite->create($invite);
        
        if($this->CoInvite->save())
        {
          // XXX email the invitation, don't just render it
  
          // Set CO Person status to I
          // XXX probably don't want to do this if status = A.  May need a new password reset status.
  
          $this->CoInvite->CoPerson->id = $cpid;
          
          if($this->CoInvite->CoPerson->saveField('status', 'I'))
          {
            if($this->restful)
            {
              // $this->restResultHeader(201, "Sent");
              $this->restResultHeader(501, "Not Implemented");
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
            if($this->restful)
            {
              $this->restResultHeader(400, "Invalid Fields");
              $this->set('invalid_fields', $this->CoInvite->invalidFields());
            }
            else
              $this->Session->setFlash($this->fieldsErrorToString($this->CoInvite->CoPerson->invalidFields()), '', array(), 'error');
          }
        }
        else
        {
          if($this->restful)
          {
            $this->restResultHeader(400, "Invalid Fields");
            $this->set('invalid_fields', $this->CoInvite->invalidFields());
          }
          else
            $this->Session->setFlash($this->fieldsErrorToString($this->CoInvite->invalidFields()), '', array(), 'error');
        }
      }
      else
      {
        if($this->restful)
          $this->restResultHeader(400, "No Email Address");
        else
        {
          $this->Session->setFlash(_txt('er.orgp.nomail', array(generateCn($orgp['Name']), $orgp['OrgIdentity']['id'])), '', array(), 'error');
          $this->redirect(array('controller' => 'co_people', 'action' => 'index', 'co' => $this->cur_co['Co']['id']));
        }
      }
    }
    else
    {
      if($this->restful)
      {
        $this->restResultHeader(400, "Invalid Fields");
        $this->set('invalid_fields', array('CoPersonId' => _txt('er.cop.unk')));
      }
      else
        $this->Session->setFlash(_txt('er.cop.nf', array($cpid)), '', array(), 'error');
    }
  }
}
