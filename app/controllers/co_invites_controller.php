<?php
  /*
   * COmanage Gears CO Invite Controller
   *
   * Version: $Revision$
   * Date: $Date$
   *
   * Copyright (C) 2010-2011 University Corporation for Advanced Internet Development, Inc.
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
   */
  
  class CoInvitesController extends AppController {
    // We don't extend StandardController because there's so much unique stuff going on here
    var $name = "CoInvites";
    var $components = array('RequestHandler',  // For REST
                            'Security',
                            'Session');
    var $paginate = array(
      'limit' => 25,
      'order' => array(
        'expires' => 'asc',
      )
    );
    // This controller needs a CO to be set, but only for send
    var $requires_co = false;
    
    function add()
    {
      // Handle RESTful send() request, mapped to add() by default RESTful behavior.
      // See send() for details.
      
      $this->send();
    }
    
    function beforeFilter()
    {
      // Callback before other controller methods are invoked or views are rendered.
      //
      // Parameters:
      //   None
      //
      // Preconditions:
      //     None
      //
      // Postconditions:
      // (1) Parent called
      // (2) Auth component reconfigured to allow invite handling without login
      //
      // Returns:
      //   Nothing
      
      if($this->action == "send")
        $this->requires_co = true;

      // Since we're overriding, we need to call the parent to run the authz check
      parent::beforeFilter();
      
      // Allow invite handling to process without a login page
      $this->Auth->allow('confirm', 'decline', 'reply');
    }
    
    function confirm($inviteid)
    {
      // Confirm the requested invite
      //
      // Parameters:
      // - inviteid: ID invitation
      //
      // Preconditions:
      // (1) $invite must exist, be valid, and attached to a valid CO person
      //
      // Postconditions:
      // (1) CO Person status set to 'Active'
      // (2) $inviteid deleted
      // (3) Session flash message updated (HTML)
      //
      // Returns:
      //   Nothing
      
      $this->process_invite($inviteid, true);
    }
    
    function decline($inviteid)
    {
      // Decline the requested invite
      //
      // Parameters:
      // - inviteid: ID invitation
      //
      // Preconditions:
      // (1) $invite must exist, be valid, and attached to a valid CO person
      //
      // Postconditions:
      // (1) CO Person status set to 'Declined'
      // (2) $inviteid deleted
      // (3) Session flash message updated (HTML)
      //
      // Returns:
      //   Nothing
      
      $this->process_invite($inviteid, false);
    }
    
    function index()
    {
      // Handle RESTful confirm() or decline() request, mapped to index() by default RESTful behavior.
      //
      // Parameters (in $this->params['url']):
      // - inviteid: ID invitation
      // - reply: 'confirm' or 'decline'
      //
      // Preconditions:
      //    As for process_invite()
      //
      // Postconditions:
      //    As for process_invite()
      //
      // Returns:
      //   Nothing
      
      // Since we're not doing a traditional save, we need to manually validate
      // the fields sent

      $fs = array();
          
      if(!isset($this->params['url']['inviteid']))
        $fs['inviteid'] = _txt('er.notprov');
      if(isset($this->params['url']['reply']))
      {
        if($this->params['url']['reply'] != 'confirm' && $this->params['url']['reply'] != 'decline')
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
        $this->process_invite($this->params['url']['inviteid'], $this->params['url']['reply']);
    }

    function isAuthorized()
    {
      // Authorization for this Controller, called by Auth component
      //
      // Parameters:
      //   None
      //
      // Preconditions:
      // (1) Session.Auth holds data used for authz decisions
      //
      // Postconditions:
      // (1) $permissions set with calculated permissions
      //
      // Returns:
      // - Array of permissions

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
      $p['send'] = ($cmr['cmadmin'] || $cmr['coadmin'] || $cmr['subadmin']);

      $this->set('permissions', $p);
      return($p[$this->action]);
    }
    
    function process_invite($inviteid, $confirm)
    {
      // Process an invitation reply
      //
      // Parameters:
      // - inviteid: ID invitation
      // - confirm: If true, confirm the invitation; if false, decline it
      //
      // Preconditions:
      // (1) $invite must exist, be valid, and attached to a valid CO person
      //
      // Postconditions:
      // (1) CO Person status set
      // (2) $inviteid deleted
      // (3) Session flash message updated (HTML) or HTTP status returned (REST)
      //
      // Returns:
      //   Nothing
      
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
 
    function reply($inviteid)
    {
      // Find the requested invite and prompt the user to confirm or decline
      //
      // Parameters:
      // - inviteid: ID invite to reply to
      //
      // Preconditions:
      // (1) $inviteid must exist and not expired or validated
      // (2) The associated person must be in invited state
      //
      // Postconditions:
      // (1) $cur_co set to current CO on success
      // (2) $invite set on success
      // (3) $invitee set to CO Person on success
      // (4) Session flash message updated (HTML) on suitable error
      //
      // Returns:
      //   Nothing
      
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
        $cops = $this->CoInvite->CoPerson->CoPersonSource->findByCoPersonId($invitee['CoPerson']['id'], null, 'CoPersonSource.id');
        $co = $this->CoInvite->CoPerson->CoPersonSource->Co->findById($cops['CoPersonSource']['co_id']);
        
        $this->set('cur_co', $co);
        $this->set('invite', $invite);
        $this->set('invitee', $invitee);
      }
    }
    
    function send()
    {
      // Send an invitation to the CO person $cpid
      //
      // Parameters (in $this->params['named'] for HTML or $this->params['url'] for REST):
      // - copersonid: ID of Org Person to invite
      // - co: CO to invite to
      //
      // Preconditions:
      // (1) $orgpersonid must exist
      //
      // Postconditions:
      // (1) Email invitation sent to address on record (XXX not implemented)
      // (2) $orgpersonid set to 'Invited' status
      // (3) $cur_co set to current CO on success
      // (4) $invite set on success (HTML)
      // (5) $invitee set to CO Person on success (HTML)
      // (6) 
      // (6) $co_invite_id set on success (REST)
      // (7) Session flash message updated (HTML) or HTTP status returned (REST)
      //
      // Returns:
      //   Nothing
      
      if($this->restful)
      {
        if(!$this->convertRequest())
          // || !$this->checkPost())  // Our request format doesn't match our schema, so skip checkPost
          return;
        
        if(empty($this->data))
        {
          $this->restResultHeader(400, "Bad Request");
          return;
        }
        else
        {
          // Since we're not doing a traditional save, we need to manually validate
          // the fields sent

          $fs = array();
            
          if(isset($this->data['CoInvite']['co_id']))
          {
            // beforeFilter will check the CO for HTML, but not for REST
            
            $this->cur_co = $this->CoInvite->CoPerson->CoPersonSource->Co->findById($this->data['CoInvite']['co_id']);
            
            if(!$this->cur_co)
              $fs['CoId'] = _txt('er.co.unk');
          }
          else
            $fs['CoId'] = _txt('er.notprov');
          if(!isset($this->data['CoInvite']['co_person_id']))
            $fs['CoPersonId'] = _txt('er.notprov');
          
          if(count($fs) > 0)
          {
            $this->restResultHeader(400, "Invalid Fields");
            $this->set('invalid_fields', $fs);
            return;
          }

          $coid = $this->data['CoInvite']['co_id'];
          $cpid = $this->data['CoInvite']['co_person_id'];
        }
      }
      else
      {
        // Set page title
        $this->set('title_for_layout', _txt('op.inv.send'));

        $coid = $this->params['named']['co'];
        $cpid = $this->params['named']['copersonid'];
      }
      
      // Retrieve info about the Person
      
      $cop = $this->CoInvite->CoPerson->findById($cpid);

      if($cop)
      {
        // Toss any prior invitations for $cpid
  
        $this->CoInvite->deleteAll(array("co_person_id" => $cpid));

        // Find the associated Org Person to get an email address
        
        $cops = $this->CoInvite->CoPerson->CoPersonSource->findByCoPersonId($cpid, null, 'CoPersonSource.id');
        $orgp = $this->CoInvite->CoPerson->CoPersonSource->OrgPerson->findById($cops['CoPersonSource']['org_person_id']);
        
        // XXX We only check org person. What if Org Person has no address, but is officially
        // sourced (eg: via LDAP)?
        
        if(count($orgp['EmailAddress']) > 0)
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
            $this->Session->setFlash(_txt('er.orgp.nomail', array(generateCn($orgp['Name']), $orgp['OrgPerson']['id'])), '', array(), 'error');
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
?>