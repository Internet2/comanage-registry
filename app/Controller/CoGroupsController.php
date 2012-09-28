<?php
/**
 * COmanage Registry CO Group Controller
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

App::import('Sanitize');
App::uses("StandardController", "Controller");

class CoGroupsController extends StandardController {
  // Class name, used by Cake
  public $name = "CoGroups";
  
  // Establish pagination parameters for HTML views
  public $paginate = array(
    'limit' => 25,
    'order' => array(
      'CoGroup.name' => 'asc'
    )
  );
  
  // This controller needs a CO to be set
  public $requires_co = true;

  /**
   * Perform any dependency checks required prior to a write (add/edit) operation.
   * This method is intended to be overridden by model-specific controllers.
   * - postcondition: Session flash message updated (HTML) or HTTP status returned (REST)
   *
   * @since  COmanage Registry v0.1
   * @param  Array Request data
   * @param  Array Current data
   * @return boolean true if dependency checks succeed, false otherwise.
   */
  
  function checkWriteDependencies($reqdata, $curdata = null) {
    if(!isset($curdata)
       || ($curdata['CoGroup']['name'] != $reqdata['CoGroup']['name']))
    {
      // Disallow names beginning with 'admin' if the current user is not an admin
      
      if(!$this->viewVars['permissions']['admin'])
      {
        if($reqdata['CoGroup']['name'] == 'admin'
           || strncmp($reqdata['CoGroup']['name'], 'admin:', 6) == 0)
        {
          if($this->restful)
            $this->restResultHeader(403, "Name Reserved");
          else
            $this->Session->setFlash(_txt('er.gr.res'), '', array(), 'error');          
  
          return(false);
        }
      }
      
      // Make sure name doesn't exist within this CO
      
      $x = $this->CoGroup->find('all', array('conditions' =>
                                             array('CoGroup.name' => $reqdata['CoGroup']['name'],
                                                   'CoGroup.co_id' => $this->cur_co['Co']['id'])));
      
      if(!empty($x))
      {
        if($this->restful)
          $this->restResultHeader(403, "Name In Use");
        else
          $this->Session->setFlash(_txt('er.gr.exists', array($reqdata['CoGroup']['name'])), '', array(), 'error');          

        return(false);
      }
    }

    return(true);
  }
  
  /**
   * Perform any followups following a write operation.  Note that if this
   * method fails, it must return a warning or REST response, but that the
   * overall transaction is still considered a success (add/edit is not
   * rolled back).
   * This method is intended to be overridden by model-specific controllers.
   * - postcondition: Session flash message updated (HTML) or HTTP status returned (REST)
   *
   * @since  COmanage Registry v0.1
   * @param  Array Request data
   * @param  Array Current data
   * @return boolean true if dependency checks succeed, false otherwise.
   */
  
  function checkWriteFollowups($reqdata, $curdata = null) {
    // Add the co person as owner/member of the new group, but only via HTTP
    
    if(!$this->restful && $this->action == 'add')
    {
      $cos = $this->Session->read('Auth.User.cos');

      // Member of current CO? (Platform admin wouldn't be)
      if(isset($cos) && isset($cos[ $this->cur_co['Co']['name'] ]['co_person_id']))
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
    }
    
    return(true);
  }

  /**
   * Delete an Group Object
   * - precondition: <id> must exist
   * - postcondition: Session flash message updated (HTML) or HTTP status returned (REST)
   * - postcondition: On success, all related data (any table with an <object>_id column) is deleted
   *
   * @since  COmanage Registry v0.7
   * @param  integer Object identifier (eg: cm_co_groups:id) representing object to be deleted
   */  
  function delete($id) {
    $this->redirectTab = 'group';

    parent::delete($id);
  }

  /**
   * Update a CO Group.
   * - precondition: Model specific attributes in $this->request->data (optional)
   * - precondition: <id> must exist
   * - postcondition: On GET, $<object>s set (HTML)
   * - postcondition: On POST success, object updated
   * - postcondition: On POST, session flash message updated (HTML) or HTTP status returned (REST)
   * - postcondition: On POST error, $invalid_fields set (REST)
   *
   * @since  COmanage Registry v0.1
   * @param  integer Object identifier (eg: cm_co_groups:id) representing object to be retrieved
   */
  
  function edit($id) {
    // Mostly, we want the standard behavior.  However, we need to retrieve the
    // set of members when rendering the edit form.
    
    if(!$this->restful && $this->request->is('get'))
    {
      $this->CoGroup->CoGroupMember->recursive = 2;
      $x = $this->CoGroup->CoGroupMember->find('all', array('conditions' =>
                                                            array('CoGroupMember.co_group_id' => $id)));
      
      $this->set('co_group_members', $x);
    }
    
    // Invoke the StandardController edit
    parent::edit($id);
  }
  
  /**
   * Obtain all CO Groups.
   * - postcondition: $<object>s set on success (REST or HTML), using pagination (HTML only)
   * - postcondition: HTTP status returned (REST)
   * - postcondition: Session flash message updated (HTML) on suitable error
   *
   * @since  COmanage Registry v0.6
   */
  
  function index() {
    if($this->restful && !empty($this->params['url']['copersonid'])) {
      // We need to retrieve via a join, which StandardController::index() doesn't
      // currently support.
      
      try {
        $groups = $this->CoGroup->findForCoPerson($this->params['url']['copersonid']);
        
        if(!empty($groups)) {
          $this->set('co_groups', $groups);
          
          // We also need to pass member/ownership in these groups.
          
          $this->set('co_group_members',
                     $this->CoGroup->CoGroupMember->findCoPersonGroupRoles($this->params['url']['copersonid']));
        } else {
          $this->restResultHeader(204, "CO Person Has No Groups");
          return;
        }
      }
      catch(InvalidArgumentException $e) {
        $this->restResultHeader(404, "CO Person Unknown");
        return;
      }
    } else {
      parent::index();
    }
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
    $cmr = $this->calculateCMRoles();                      // What was authenticated
    $pids = $this->parsePersonID($this->request->data);    // What was requested

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
    
    // Create an admin Group?
    $p['admin'] = ($cmr['cmadmin'] || $cmr['coadmin']);
    
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
       && isset($this->request->params['pass'][0]))
    {
      // Adjust permissions for owners, members, and open groups

      if(isset($own) && in_array($this->request->params['pass'][0], $p['owner']))
      {
        $p['delete'] = true;
        $p['edit'] = true;
        $p['view'] = true;
      }
      
      if(isset($member) && in_array($this->request->params['pass'][0], $p['member']))
        $p['view'] = true;
      
      $g = $this->CoGroup->findById($this->request->params['pass'][0]);
      
      if($g)
      {
        if(isset($g['CoGroup']['open']) && $g['CoGroup']['open'])
          $p['view'] = true;
      }
    }

    $this->set('permissions', $p);
    return($p[$this->action]);
  }
  
  /**
   * Obtain groups available for a CO Person to join.
   * - precondition: $this->request->params holds copersonid XXX we don't do anything with this yet
   * - postcondition: $co_groups set (HTML)
   * - postcondition: Session flash message updated (HTML)
   *
   * @since  COmanage Registry v0.1
   */
  
  function select() {
    // Lookup the person in question to find their name
    
    $args = array();
    $args['conditions']['CoPerson.id'] = $this->request->params['named']['copersonid'];
    $args['contain'] = 'Name';
    
    $coPerson = $this->CoGroup->CoGroupMember->CoPerson->find('first', $args);
    
    if(!empty($coPerson)) {
      // Set name for page title
      $this->set('name_for_title', Sanitize::html(generateCn($coPerson['Name'])));
    }
    
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
  
  /**
   * Retrieve a CO Group.
   * - precondition: <id> must exist
   * - postcondition: $<object>s set (with one member)
   * - postcondition: HTTP status returned (REST)
   * - postcondition: Session flash message updated (HTML)
   *
   * @since  COmanage Registry v0.1
   * @param  integer Object identifier (eg: cm_co_groups:id) representing object to be retrieved
   */
  
  function view($id) {
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

  /**
   * Perform a redirect back to the controller's default view.
   * - postcondition: Redirect generated
   *
   * @since  COmanage Registry v0.7
   */
  
  function performRedirect() {

    $this->redirectTab = 'group';

    parent::performRedirect();
  }

}
