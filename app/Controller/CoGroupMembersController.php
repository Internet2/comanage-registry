<?php
/**
 * COmanage Registry CO Group Member Controller
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
 * @copyright     Copyright (C) 2010-13 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.1
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */

App::uses("StandardController", "Controller");

class CoGroupMembersController extends StandardController {
  // Class name, used by Cake
  public $name = "CoGroupMembers";
  
  // Establish pagination parameters for HTML views
  public $paginate = array(
    'limit' => 25,
    'order' => array(
      'PrimaryName.family' => 'asc',
      'PrimaryName.given' => 'asc'
    )
  );
  
  // This controller needs a CO to be set
  public $requires_co = true;

  // Edit and view need recursion so we get Name for rendering view
  public $edit_recursion = 2;
  public $view_recursion = 2;
  
  // We need to track the group ID under certain circumstances to enable performRedirect
  private $gid = null;

  /**
   * Add one or more CO Group Members.
   * - precondition: Model specific attributes in $this->request->data (optional)
   * - postcondition: On success, new Object created
   * - postcondition: Session flash message updated (HTML) or HTTP status returned (REST)
   * - postcondition: $<object>_id or $invalid_fields set (REST)
   *
   * @since  COmanage Registry v0.1
   */
  
  function add() {
    if(!$this->restful)
    {
      // We can't just saveAll because Cake will create rows in the database for all
      // non-member non-owners, which is silly.  Create a version without those
      // entries (and properly structured).
      
      // Set page title
      $this->set('title_for_layout', _txt('op.add-a', array(_txt('ct.co_group_members.1'))));

      // We may get here via co_groups, in which case co_group_id is static, or
      // via co_people, in which case co_person_id is static
      
      $a = array('CoGroupMember' => array());

      foreach($this->request->data['CoGroupMember'] as $g)
      {
        // Must be a member or an owner to get a row created
        
        if(is_array($g)
           && ((isset($g['member']) && $g['member'])
               || (isset($g['owner']) && $g['owner'])))
        {
          $a['CoGroupMember'][] = array(
            'co_group_id' => (!empty($g['co_group_id'])
                              ? $g['co_group_id']
                              : $this->request->data['CoGroupMember']['co_group_id']),
            'co_person_id' => (!empty($g['co_person_id'])
                                    ? $g['co_person_id']
                                    : $this->request->data['CoGroupMember']['co_person_id']),
            'member' => $g['member'],
            'owner' => $g['owner']
          );
        }
      }
      
      if(count($a['CoGroupMember']) > 0)
      {
        // When using the Grouper dataSource we need to set the
        // atomic option for saveAll() to false.
        if (Configure::read('Grouper.COmanage.useGrouperDataSource')) {
          $atomic = false;
        } else {
          $atomic = true;
        }

        if($this->CoGroupMember->saveAll($a['CoGroupMember'], array('atomic' => $atomic)))
          $this->Session->setFlash(_txt('rs.added'), '', array(), 'success');
        else
          $this->Session->setFlash($this->fieldsErrorToString($this->CoGroupMember->invalidFields()), '', array(), 'error');
      }
      else
        $this->Session->setFlash(_txt('er.grm.none'), '', array(), 'info');
        
      $this->performRedirect();
    }
    else
      parent::add();
  }

  /**
   * Callback before other controller methods are invoked or views are rendered.
   * - precondition:
   * - postcondition: Auth component is configured 
   * - postcondition:
   *
   * @since  COmanage Registry v0.1
   * @throws UnauthorizedException (REST)
   */
  
  function beforeFilter() {
    // Strictly speaking, this controller doesn't require a CO except to
    // redirect/render views.  Since the REST API doesn't specify CO ID
    // we unset requires_co.  $this->restful gets set in beforeFilter, so
    // call the parent first.  (While requires_co is checked there for
    // non-REST, it is checked in checkPost for REST.)

    parent::beforeFilter();

    if($this->restful)
      $this->requires_co = false;

    // Sets tab to open for redirects back to tabbed pages
    $this->redirectTab = 'email';
  }
  
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
    // Make sure the Group exists
    
    $g = $this->CoGroupMember->CoGroup->find('first', 
                                             array('conditions' => 
                                              array('CoGroup.id' => $reqdata['CoGroupMember']['co_group_id'])
                                             )
                                            );
    
    if(empty($g))
    {
      if($this->restful)
        $this->restResultHeader(403, "CoGroup Does Not Exist");
      else
      {
        $this->Session->setFlash(_txt('er.gr.nf', array($reqdata['CoGroupMember']['co_group_id'])), '', array(), 'error');
        $this->performRedirect();
      }

      return(false);
    }

    // Make sure the CO Person exists
    
    $p = $this->CoGroupMember->CoPerson->find('first', 
                                              array('conditions' => 
                                               array('CoPerson.id' => $reqdata['CoGroupMember']['co_person_id'])
                                              )
                                             );
    
    if(empty($p))
    {
      if($this->restful)
        $this->restResultHeader(403, "CoPerson Does Not Exist");
      else
      {
        $this->Session->setFlash(_txt('er.cop.nf', array($reqdata['CoGroupMember']['co_person_id'])), '', array(), 'error');
        $this->performRedirect();
      }

      return(false);
    }
    
    if($this->action == 'add')
    {
      // Make sure the CO Person Role isn't already in the Group
      
      $x = $this->CoGroupMember->find('all', array('conditions' =>
                                                   array('CoGroupMember.co_group_id' => $reqdata['CoGroupMember']['co_group_id'],
                                                         'CoGroupMember.co_person_id' => $reqdata['CoGroupMember']['co_person_id'])));
      
      if(!empty($x))
      {
        if($this->restful)
          $this->restResultHeader(403, "CoPerson Already Member");
        else
        {
          $this->Session->setFlash(_txt('er.grm.already', array($reqdata['CoGroupMember']['co_person_id'],
                                                                $reqdata['CoGroupMember']['co_group_id'])),
                                   '', array(), 'error');
          $this->performRedirect();
        }

        return(false);
      }
    }
    
    // XXX We don't check that the CO Person is actually in the CO... should we?
    // And what if they're later removed from the CO?

    return(true);
  }
  
  /**
   * Generate history records for a transaction. This method is intended to be
   * overridden by model-specific controllers, and will be called from within a
   * try{} block so that HistoryRecord->record() may be called without worrying
   * about catching exceptions.
   *
   * @since  COmanage Registry v0.8
   * @param  String Controller action causing the change
   * @param  Array Data provided as part of the action (for add/edit)
   * @param  Array Previous data (for delete/edit)
   * @return boolean Whether the function completed successfully (which does not necessarily imply history was recorded)
   */
  
  public function generateHistory($action, $newdata, $olddata) {
    switch($action) {
      case 'add':
        $this->CoGroupMember->CoPerson->HistoryRecord->record($olddata['CoGroupMember']['co_person_id'],
                                                              null,
                                                              null,
                                                              $this->Session->read('Auth.User.co_person_id'),
                                                              ActionEnum::CoGroupMemberAdded,
                                                              _txt('rs.grm.added', array($olddata['CoGroup']['name'],
                                                                                         $olddata['CoGroup']['id'],
                                                                                         _txt($newdata['CoGroupMember']['member'] ? 'fd.yes' : 'fd.no'),
                                                                                         _txt($newdata['CoGroupMember']['owner'] ? 'fd.yes' : 'fd.no'))));
        break;
      case 'delete':
        $this->CoGroupMember->CoPerson->HistoryRecord->record($olddata['CoGroupMember']['co_person_id'],
                                                              null,
                                                              null,
                                                              $this->Session->read('Auth.User.co_person_id'),
                                                              ActionEnum::CoGroupMemberDeleted,
                                                              _txt('rs.grm.deleted', array($olddata['CoGroup']['name'],
                                                                                           $olddata['CoGroup']['id'])));
        break;
      case 'edit':
        $this->CoGroupMember->CoPerson->HistoryRecord->record($olddata['CoGroupMember']['co_person_id'],
                                                              null,
                                                              null,
                                                              $this->Session->read('Auth.User.co_person_id'),
                                                              ActionEnum::CoGroupMemberEdited,
                                                              _txt('rs.grm.edited', array($olddata['CoGroup']['name'],
                                                                                          $olddata['CoGroup']['id'],
                                                                                          _txt($olddata['CoGroupMember']['member'] ? 'fd.yes' : 'fd.no'),
                                                                                          _txt($olddata['CoGroupMember']['owner'] ? 'fd.yes' : 'fd.no'),
                                                                                          _txt($newdata['CoGroupMember']['member'] ? 'fd.yes' : 'fd.no'),
                                                                                          _txt($newdata['CoGroupMember']['owner'] ? 'fd.yes' : 'fd.no'))));
        break;
    }
    
    return true;
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
    $roles = $this->Role->calculateCMRoles();             // What was authenticated
    
    // Store the group ID in the controller object since performRedirect may need it
    
    if(($this->action == 'add' || $this->action == 'updateGroup')
       && isset($this->request->data['CoGroupMember']['co_group_id']))
      $this->gid = $this->request->data['CoGroupMember']['co_group_id'];
    elseif(($this->action == 'delete' || $this->action == 'edit' || $this->action == 'view')
           && isset($this->request->params['pass'][0]))
      $this->gid = $this->CoGroupMember->field('co_group_id', array('CoGroupMember.id' => $this->request->params['pass'][0]));
    elseif($this->action == 'select' && isset($this->request->params['named']['cogroup']))
      $this->gid = $this->request->params['named']['cogroup'];
    
    $managed = false;
    $owner = false;
    $member = false;
    
    if(!empty($roles['copersonid']) && isset($this->gid)) {
      $managed = $this->Role->isGroupManager($roles['copersonid'], $this->gid);
      
      $gm = $this->CoGroupMember->find('all', array('conditions' =>
                                                    array('CoGroupMember.co_group_id' => $this->gid,
                                                          'CoGroupMember.co_person_id' => $roles['copersonid'])));
      
      if(isset($gm[0]['CoGroupMember']['owner']) && $gm[0]['CoGroupMember']['owner'])
        $owner = true;
      
      if(isset($gm[0]['CoGroupMember']['member']) && $gm[0]['CoGroupMember']['member'])
        $member = true;
    }
    
    // Construct the permission set for this user, which will also be passed to the view.
    $p = array();
    
    // Add a new member to a group?
    // XXX probably need to check if group is open here and in delete
    $p['add'] = ($roles['cmadmin'] || $managed);
    
    // Delete a member from a group?
    $p['delete'] = ($roles['cmadmin'] || $managed);
    
    // Edit members of a group?
    $p['edit'] = ($roles['cmadmin'] || $managed);
    
    // View a list of members of a group?
    // This is for REST
    $p['index'] = ($this->restful && ($roles['cmadmin'] || $roles['coadmin']));
    
    // Select from a list of potential members to add?
    $p['select'] = ($roles['cmadmin'] || $managed);
    
    // Update accepts a CO Person's worth of potential group memberships and performs the appropriate updates
    $p['update'] = ($roles['cmadmin'] || $roles['comember']);
    
    // Select from a list of potential members to add?
    $p['updateGroup'] = ($roles['cmadmin'] || $managed);
    
    // View members of a group?
    $p['view'] = ($roles['cmadmin'] || $managed || $member);
    
    $this->set('permissions', $p);
    return $p[$this->action];
  }
  
  /**
   * Perform a redirect back to the controller's default view.
   * - postcondition: Redirect generated
   *
   * @since  COmanage Registry v0.1
   */
  
  function performRedirect() {
    // Figure out where to redirect back to based on how we were called
    
    $cop = null;
    
    if($this->action == 'add' && isset($this->request->data['CoGroupMember']['co_person_id']))
      $cop = $this->request->data['CoGroupMember']['co_person_id'];
    elseif($this->action == 'delete' && isset($this->request->params['named']['copersonid']))
      $cop = $this->request->params['named']['copersonid'];
      
    if(isset($cop)) {
      $params = array('controller' => 'co_people',
                      'action'     => 'edit',
                      $cop,
                      'co'         => $this->cur_co['Co']['id'],
                      'tab'        => 'group'
                     );
    } elseif(isset($this->gid)) {
      $params = array('controller' => 'co_groups',
                      'action'     => 'edit',
                      $this->gid,
                      'co'         => $this->cur_co['Co']['id']
                     );
    } else {
      // A perhaps not ideal default, but we shouldn't get here
      $params = array('controller' => 'co_groups',
                      'action'     => 'index',
                      'co'         => $this->cur_co['Co']['id']
                     );
    }
    
    $this->redirect($params);
  }

  /**
   * Select from a list of potential new group members.
   * - precondition: $this->request->params holds cogroup
   * - postcondition: $co_people set with potential new members
   * - postcondition: Session flash message updated on error (HTML)
   *
   * @since  COmanage Registry v0.1
   */
  
  function select() {
    // Find all available CO people
    
    $args = array();
    $args['joins'][0]['table'] = 'co_groups';
    $args['joins'][0]['alias'] = 'CoGroup';
    $args['joins'][0]['type'] = 'INNER';
    $args['joins'][0]['conditions'][0] = 'CoPerson.co_id=CoGroup.co_id';
    $args['conditions']['CoGroup.id'] = $this->request->params['named']['cogroup'];
    $args['order'][] = 'PrimaryName.family';
    $args['contain'][] = 'PrimaryName';
    
    $this->set('co_people', $this->CoGroupMember->CoPerson->find('all', $args));
    
    // Find current group members/owners, and rehash to make it easier for the
    // view to process
    
    $args = array();
    $args['conditions']['CoGroupMember.co_group_id'] = $this->request->params['named']['cogroup'];
    $args['recursive'] = -1;
    
    $coGroupMembers = $this->CoGroupMember->find('all', $args);
    $coGroupRoles = array();
    
    foreach($coGroupMembers as $m) {
      if(isset($m['CoGroupMember']['member']) && $m['CoGroupMember']['member']) {
        // Make it easy to find the corresponding co_group_member:id
        $coGroupRoles['members'][ $m['CoGroupMember']['co_person_id'] ] = $m['CoGroupMember']['id'];
      }
      
      if(isset($m['CoGroupMember']['owner']) && $m['CoGroupMember']['owner']) {
        // Make it easy to find the corresponding co_group_member:id
        $coGroupRoles['owners'][ $m['CoGroupMember']['co_person_id'] ] = $m['CoGroupMember']['id'];
      }
    }
    
    $this->set('co_group_roles', $coGroupRoles);
    
    // Also find the Group so that its details like name can be rendered
    
    $args = array();
    $args['conditions']['CoGroup.id'] = $this->request->params['named']['cogroup'];
    $args['contain'] = false;
    
    $this->set('co_group', $this->CoGroupMember->CoGroup->find('first', $args));
  }
  
  /**
   * Process an update to a CO Person's CO Group Memberships.
   * - precondition: $this->request->params holds coperson
   * - postcondition: Redirect generated
   *
   * @since  COmanage Registry v0.8
   */
  
  public function update() {
    if(!$this->restful) {
      try {
        $this->CoGroupMember->updateMemberships($this->request->data['CoGroupMember']['co_person_id'],
                                                $this->request->data['CoGroupMember']['rows'],
                                                $this->Session->read('Auth.User.co_person_id'));
        
        $this->Session->setFlash(_txt('rs.saved'), '', array(), 'success');
      }
      catch(Exception $e) {
        $this->Session->setFlash($e->getMessage(), '', array(), 'error');
      }
      
      // Issue redirect
      
      $this->redirect(array('controller' => 'co_groups',
                            'action'     => 'select',
                            'copersonid' => $this->request->data['CoGroupMember']['co_person_id'],
                            'co'         => $this->cur_co['Co']['id']));
    }
  }
  
  /**
   * Process an update to a CO Group's Memberships.
   * - precondition: $this->request->params holds cogroup
   * - postcondition: Redirect generated
   *
   * @since  COmanage Registry v0.8
   */
  
  public function updateGroup() {
    if(!$this->restful) {
      try {
        $this->CoGroupMember->updateGroupMemberships($this->request->data['CoGroupMember']['co_group_id'],
                                                     $this->request->data['CoGroupMember']['rows'],
                                                     $this->Session->read('Auth.User.co_person_id'));
        
        $this->Session->setFlash(_txt('rs.saved'), '', array(), 'success');
      }
      catch(Exception $e) {
        $this->Session->setFlash($e->getMessage(), '', array(), 'error');
      }
      
      // Issue redirect
      
      $this->redirect(array('controller' => 'co_groups',
                            'action'     => 'edit',
                            $this->request->data['CoGroupMember']['co_group_id'],
                            'co'         => $this->cur_co['Co']['id']));
    }
  }
}
