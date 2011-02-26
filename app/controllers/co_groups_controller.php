<?php
  /*
   * COmanage Gears CoGroup Controller
   *
   * Version: $Revision$
   * Date: $Date$
   *
   * Copyright (C) 2011 University Corporation for Advanced Internet Development, Inc.
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

  App::import('Sanitize');
  include APP."controllers/standard_controller.php";

  class CoGroupsController extends StandardController {
    // Class name, used by Cake
    var $name = "CoGroups";
    
    // Cake Components used by this Controller
    var $components = array('RequestHandler',  // For REST
                            'Security',
                            'Session');
    
    // Establish pagination parameters for HTML views
    var $paginate = array(
      'limit' => 25,
      'order' => array(
        'CoGroup.name' => 'asc'
      )
    );
    
    // This controller needs a CO to be set
    var $requires_co = true;

    function checkWriteDependencies($curdata = null)
    {
      // Perform any dependency checks required prior to a write (add/update) operation.
      //
      // Parameters:
      //   None
      //
      // Preconditions:
      // (1) $this->data holds request data
      //
      // Postconditions:
      // (1) Session flash message updated (HTML) or HTTP status returned (REST) on error
      //
      // Returns:
      // - true if dependency checks succeed, false otherwise.

      if(!isset($curdata)
         || ($curdata['CoGroup']['name'] != $this->data['CoGroup']['name']))
      {
        // Make sure name doesn't exist within this CO
        $x = $this->CoGroup->find('all', array('conditions' =>
                                               array('CoGroup.name' => $this->data['CoGroup']['name'],
                                                     'CoGroup.co_id' => $this->cur_co['Co']['id'])));
        
        if(!empty($x))
        {
          if($this->restful)
            $this->restResultHeader(403, "Name In Use");
          else
            $this->Session->setFlash(_txt('er.gr.exists', array($this->data['CoGroup']['name'])), '', array(), 'error');          
  
          return(false);
        }
      }

      return(true);
    }
    
    function checkWriteFollowups()
    {
      // Perform any followups following a write operation.  Note that if this
      // method fails, it must return a warning or REST response, but that the
      // overall transaction is still considered a success (add/edit is not
      // rolled back).
      // This method is intended to be overridden by model-specific controllers.
      // 
      // Parameters:
      //   None
      //
      // Preconditions:
      // (1) $this->data holds request data
      //
      // Postconditions:
      // (1) Session flash message updated (HTML) or HTTP status returned (REST) on error
      //
      // Returns:
      // - true if dependency checks succeed, false otherwise.

      // Add the co person as owner/member of the new group, but only via HTTP
      
      if(!$this->restful && $this->action == 'add')
      {
        $a['CoGroupMember'] = array(
          'co_group_id' => $this->CoGroup->id,
          'co_person_id' => $this->Session->read('Auth.User.co_person_id'),
          'owner' => true,
          'member' => true
        );
        
        if(!$this->CoGroup->CoGroupMember->save($a))
        {
          $this->Session->setFlash(_txt('er.gr.init'), '', array(), 'info');
          return(false);
        }
      }
      
      return(true);
    }

    function edit($id)
    {
      // Update a CO Group.
      
      // Mostly, we want the standard behavior.  However, we need to retrieve the
      // set of members when rendering the edit form.
      
      if(!$this->restful && empty($this->data))
      {
        $this->CoGroup->CoGroupMember->recursive = 2;
        $x = $this->CoGroup->CoGroupMember->find('all', array('conditions' =>
                                                              array('CoGroupMember.co_group_id' => $id)));
        
        $this->set('co_group_members', $x);
      }
      
      // Invoke the StandardController edit
      parent::edit($id);
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

      $cmr = $this->calculateCMRoles();             // What was authenticated
      $pids = $this->parsePersonID($this->data);    // What was requested

      if(!empty($cmr['copersonid']))
      {
        $own = $this->CoGroup->CoGroupMember->find('all', array('conditions' =>
                                                                array('CoGroupMember.co_person_id' => $cmr['copersonid'],
                                                                      'CoGroupMember.owner' => true)));
        $member = $this->CoGroup->CoGroupMember->find('all', array('conditions' =>
                                                             array('CoGroupMember.co_person_id' => $cmr['copersonid'],
                                                                   'CoGroupMember.member' => true)));
      }
      
      // Construct the permission set for this user, which will also be passed to the view.
      $p = array();
      
      // Determine what operations this user can perform
      
      // Add a new Group?
      $p['add'] = ($cmr['cmadmin'] || $cmr['coadmin'] || $cmr['comember']);
      
      // Delete an existing Group?
      $p['delete'] = ($cmr['cmadmin'] || $cmr['coadmin']);
      
      // Edit an existing Group?
      $p['edit'] = ($cmr['cmadmin'] || $cmr['coadmin']);
      
      // View all existing Group?
      $p['index'] = ($cmr['cmadmin'] || $cmr['coadmin'] || $cmr['comember']);
      
      // Select from a list of potential Groups to join?
      // XXX review this
      $p['select'] = ($cmr['cmadmin'] || $cmr['admin'] || $cmr['user']);
      
      // View an existing Group?
      $p['view'] = ($cmr['cmadmin'] || $cmr['coadmin']);

      if(isset($own))
      {
        // Set array of groups where person is owner
        
        $p['owner'] = array();
  
        foreach($own as $g)
          $p['owner'][] = $g['CoGroupMember']['co_group_id'];
      }

      if(isset($member))
      {
        // Set array of groups where person is member
        $p['member'] = array();
  
        foreach($member as $g)
          $p['member'][] = $g['CoGroupMember']['co_group_id'];
      }

      if(($this->action == 'delete' || $this->action == 'edit' || $this->action == 'view')
         && isset($this->params['pass'][0]))
      {
        // Adjust permissions for owners, members, and open groups

        if(isset($own) && in_array($this->params['pass'][0], $p['owner']))
        {
          $p['delete'] = true;
          $p['edit'] = true;
          $p['view'] = true;
        }
        
        if(isset($member) && in_array($this->params['pass'][0], $p['member']))
          $p['view'] = true;
        
        $g = $this->CoGroup->findById($this->params['pass'][0]);
        
        if($g)
        {
          if(isset($g['CoGroup']['open']) && $g['CoGroup']['open'])
            $p['view'] = true;
        }
      }

      $this->set('permissions', $p);
      return($p[$this->action]);
    }
    
    function select()
    {
      // Obtain groups available for a CO Person to join.
      //
      // Parameters (in $this->params['named'] for HTML):
      // - copersonid: CO Person to find groups for
      //
      // Preconditions:
      //     None
      //
      // Postconditions:
      // (1) $co_groups set (HTML)
      // (2) Session flash message updated on error (HTML)
      //
      // Returns:
      //   Nothing
      
      // Set page title
      $this->set('title_for_layout', _txt('op.select-a', array(_txt('ct.co_groups.1'))));

      // XXX proper authz here is probably something like "(all open CO groups
      // and all CO groups that I own) that CO Person isn't already a member of)"
      
      // XXX Don't user server side pagination
      // $params['conditions'] = array($req.'.co_id' => $this->params['named']['co']); or ['url']['coid'] for REST
      // $this->set('co_groups', $model->find('all', $params));

      // Use server side pagination
      $this->paginate['conditions'] = array(
        'CoGroup.co_id' => $this->cur_co['Co']['id']
      );

      $this->set('co_groups', $this->paginate('CoGroup'));
    }      
    
    function view($id)
    {
      // View a CO Group.
      
      // Mostly, we want the standard behavior.  However, we need to retrieve the
      // set of members when rendering the view form.
      
      if(!$this->restful)
      {
        $this->CoGroup->CoGroupMember->recursive = 2;
        $x = $this->CoGroup->CoGroupMember->find('all', array('conditions' =>
                                                              array('CoGroupMember.co_group_id' => $id)));
        
        $this->set('co_group_members', $x);
      }
      
      // Invoke the StandardController view
      parent::view($id);
    }
  }
?>