<?php
  /*
   * COmanage Gears CO Person Controller
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
  
  include APP."controllers/standard_controller.php";

  class CoPeopleController extends StandardController {
    var $name = "CoPeople";
    var $components = array('RequestHandler',  // For REST
                            'Security',
                            'Session');
    var $helpers = array('Time');
    var $paginate = array(
      'limit' => 25,
      'order' => array(
        'Name.family' => 'asc',
        'Name.given' => 'asc'
      )
    );
    // This controller needs a CO to be set
    var $requires_co = true;
    // For CO Person edit renderings, we need all CoGroup data, so we need more recursion
    var $edit_recursion = 2;

    function checkWriteDependencies($curdata = null)
    {
      // Perform any dependency checks required prior to a write (add/edit) operation.
      // This method is intended to be overridden by model-specific controllers.
      //
      // Parameters:
      // - For edit operations, $curdata will hold current data
      //
      // Preconditions:
      // (1) $this->data holds request data
      //
      // Postconditions:
      // (1) Session flash message updated (HTML) or HTTP status returned (REST) on error
      //
      // Returns:
      // - true if dependency checks succeed, false otherwise.
      
      if($this->restful && $curdata != null)
      {
        // For edit operations, Name ID needs to be passed so we replace rather than add.
        // However, the current API spec doesn't pass the Name ID (since the name is
        // embedded in the Person), so we need to copy it over here.
        
        $this->data['Name']['id'] = $curdata['Name']['id'];
      }

      return(true);
    }
    
    function editself()
    {
      // Determine our CO person ID and redirect to edit.
      //
      // Parameters:
      //   None
      //
      // Preconditions:
      // (1) User must be authenticated.
      //
      // Postconditions:
      // (1) Redirect issued.
      //
      // Returns:
      //   Nothing
 
      $cmr = $this->calculateCMRoles();
      
      if(isset($cmr['copersonid']))
        $this->redirect(array('action' => 'edit', $cmr['copersonid'], 'co' => $this->cur_co['Co']['id']));
      else
      {
        $this->Session->setFlash(_txt('op.cop.none'), '', array(), 'error');
        $this->redirect(array('action' => 'index', 'co' => $this->cur_co['Co']['id']));
      }
    }
    
    function generateDisplayKey($c = null)
    {
      // Generate a display key to be used in messages such as "Item Added".
      //
      // Parameters:
      // - c: A cached object (eg: from prior to a delete)
      //
      // Parameters:
      //   None
      //
      // Preconditions:
      //     None
      //
      // Postconditions:
      // (1) A string to be included for display.
      //
      // Returns:
      //   Nothing
 
      // Get a pointer to our model
      $req = $this->modelClass;
      $model = $this->$req;

      if(isset($c[$req][$model->displayField]))
        return($c[$req][$model->displayField]);
      if(isset($this->data['Name']))
        return(generateCn($this->data['Name']));
      else
        return("(?)");
    }

    function invite()
    {
      // Invite the person identified by the org person ID to a CO
      //
      // Parameters (in $this->params):
      // - orgpersonid: ID of Org Person to invite
      // - co: ID of CO to invite to
      //
      // Preconditions:
      //     None
      //
      // Postconditions:
      // (1) $co_person set
      // (2) Session flash message updated (HTML) on suitable error
      //
      // Returns:
      //   Nothing
      
      $orgp = $this->CoPerson->CoPersonSource->OrgPerson->findById($this->params['named']['orgpersonid']);
      
      if(!empty($orgp))
      {
        if(!$this->restful)
        {
          // Set page title
          $this->set('title_for_layout', _txt('op.inv-a', generateCn($orgp['Name'])));
        }

        // Construct a CoPerson from the OrgPerson.  We only populate defaulted values.
        
        $cop['Name'] = $orgp['Name'];
        $cop['CoPerson']['title'] = $orgp['OrgPerson']['title']; // XXX unclear that title should autopopulate
        $cop['CoPersonSource'][0]['org_person_id'] = $orgp['OrgPerson']['id'];
        
        $this->set('co_people', array(0 => $cop));
      }
      else
      {
        $this->Session->setFlash(_txt('op.orgp-unk-a', $this->params['named']['orgpersonid']), '', array(), 'error');
        $this->redirect(array('action' => 'index', 'co' => $this->cur_co['Co']['id']));
      }
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
      
      // Is this our own record?
      $self = false;
      
      if($cmr['comember'] && $cmr['copersonid'] &&
         ((isset($this->params['pass'][0]) && ($cmr['copersonid'] == $this->params['pass'][0]))
          ||
          ($this->action == 'editself' && isset($cmr['copersonid']))))
        $self = true;

      // Construct the permission set for this user, which will also be passed to the view.
      $p = array();
      
      // Determine what operations this user can perform
      
      // Add a new CO Person?
      $p['add'] = ($cmr['cmadmin'] || $cmr['coadmin']);
      // Via invite?
      $p['invite'] = ($cmr['cmadmin'] || $cmr['coadmin']);
      
      // Delete an existing CO Person?
      $p['delete'] = ($cmr['cmadmin'] || $cmr['coadmin']);
      
      // Edit an existing CO Person?
      $p['edit'] = ($cmr['cmadmin'] || $cmr['coadmin'] || $self);

      // Are we trying to edit our own record?
      $p['editself'] = $self;
      
      // View all existing CO People?
      $p['index'] = ($cmr['cmadmin'] || $cmr['coadmin']);
      
      // View an existing CO Person?
      $p['view'] = ($cmr['cmadmin'] || $cmr['coadmin'] || $self);

      $this->set('permissions', $p);
      return($p[$this->action]);
    }

    function performRedirect()
    {
      // Perform a redirect back to the controller's default view.
      //
      // Parameters:
      //   None
      //
      // Preconditions:
      //     None
      //
      // Postconditions:
      // (1) Redirect generated
      //
      // Returns:
      //   Nothing
      
      // On add, redirect to send view for notification of invite
            
      if($this->action == 'add')
        $this->redirect(array('controller' => 'co_invites',
                              'action' => 'send',
                              'copersonid' => $this->CoPerson->id,
                              'co' => $this->cur_co['Co']['id']));
      else
        parent::performRedirect();
    }
  }
?>