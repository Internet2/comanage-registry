<?php
/**
 * COmanage Registry CO Group Member Controller
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

  // Edit and view need Name for rendering view
  public $edit_contains = array(
    'CoGroup',
    'CoPerson' => 'PrimaryName'
  );

  public $view_contains = array(
    'CoGroup',
    'CoPerson' => 'PrimaryName'
  );
  
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
    if(!$this->request->is('restful')) {
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
      
      if(count($a['CoGroupMember']) > 0) {
        if($this->CoGroupMember->saveAll($a['CoGroupMember']))
          $this->Flash->set(_txt('rs.added'), array('key' => 'success'));
        else
          $this->Flash->set($this->fieldsErrorToString($this->CoGroupMember->invalidFields()), array('key' => 'error'));
      } else {
        $this->Flash->set(_txt('er.grm.none'), array('key' => 'information'));
      }
        
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
    parent::beforeFilter();

    // Sets tab to open for redirects back to tabbed pages
    $this->redirectTab = 'email';
    
    if(!empty($this->viewVars['vv_tz'])) {
      // Set the current timezone, primarily for beforeSave
      $this->CoGroupMember->setTimeZone($this->viewVars['vv_tz']);
    }
  }

  /**
   * Determine the CO ID based on some attribute of the request.
   * This method is intended to be overridden by model-specific controllers.
   *
   * @since  COmanage Registry v0.8.5
   * @return Integer CO ID, or null if not implemented or not applicable.
   * @throws InvalidArgumentException
   */
  
  protected function calculateImpliedCoId($data = null) {
    $cogroupid = null;
    $copersonid = null;
    
    if(isset($this->params->named['cogroup'])) {
      $cogroupid = $this->params->named['cogroup'];
    } elseif(isset($this->request->data['CoGroupMember']['co_group_id'])) {
      $cogroupid = $this->request->data['CoGroupMember']['co_group_id'];
    } elseif(isset($this->request->data['CoGroupMember']['co_person_id'])) {
      $copersonid = $this->request->data['CoGroupMember']['co_person_id'];
    }
    
    if($cogroupid) {
      // Map CO group to CO
      
      $coId = $this->CoGroupMember->CoGroup->field('co_id',
                                                   array('id' => $cogroupid));
      
      if($coId) {
        return $coId;
      } else {
        throw new InvalidArgumentException(_txt('er.gr.nf', array($cogroupid)));
      }
    } elseif($copersonid) {
      // Map CO person to CO
      
      $coId = $this->CoGroupMember->CoPerson->field('co_id',
                                                    array('id' => $copersonid));
      
      if($coId) {
        return $coId;
      } else {
        throw new InvalidArgumentException(_txt('er.cop.unk-a', array($copersonid)));
      }
    }
    
    // Or try the default behavior
    return parent::calculateImpliedCoId();
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
    // Make sure the Group exists.
    
    $args = array();
    $args['conditions']['CoGroup.id'] = $reqdata['CoGroupMember']['co_group_id'];
    $args['contain'] = false;
    $g = $this->CoGroupMember->CoGroup->find('first', $args);
    
    if(empty($g)) {
      if($this->request->is('restful')) {
        $this->Api->restResultHeader(403, "CoGroup Does Not Exist");
      } else {
        $this->Flash->set(_txt('er.gr.nf', array($reqdata['CoGroupMember']['co_group_id'])), array('key' => 'error'));
        $this->performRedirect();
      }
      return false;
    }
    
    // Make sure this is not an automatic group.
    if(isset($g['CoGroup']['auto']) && $g['CoGroup']['auto']) {
      if($this->request->is('restful')) {
        $this->Api->restResultHeader(403, "Memberships in automatic groups can not be edited");
      } else {
        $this->Flash->set(_txt('er.gr.auto.edit'), array('key' => 'error'));
        $this->performRedirect();
      }
      return false;
    }

    // Make sure the CO Person exists.
    
    $args = array();
    $args['conditions']['CoPerson.id'] = $reqdata['CoGroupMember']['co_person_id'];
    $args['contain'] = false;
    $p = $this->CoGroupMember->CoPerson->find('first', $args);
    
    if(empty($p)) {
      if($this->request->is('restful')) {
        $this->Api->restResultHeader(403, "CoPerson Does Not Exist");
      } else {
        $this->Flash->set(_txt('er.cop.nf', array($reqdata['CoGroupMember']['co_person_id'])), array('key' => 'error'));
        $this->performRedirect();
      }

      return false;
    }
    
    if($this->action == 'add') {
      // Make sure the CO Person Role isn't already in the Group.
      
      $args = array();
      $args['conditions']['CoGroupMember.co_group_id'] = $reqdata['CoGroupMember']['co_group_id'];
      $args['conditions']['CoGroupMember.co_person_id'] = $reqdata['CoGroupMember']['co_person_id'];
      $args['contain'] = false;
      $x = $this->CoGroupMember->find('all', $args);
      
      if(!empty($x)) {
        if($this->request->is('restful')) {
          $this->Api->restResultHeader(403, "CoPerson Already Member");
        } else {
          $this->Flash->set(_txt('er.grm.already', array($reqdata['CoGroupMember']['co_person_id'],
                                                         $reqdata['CoGroupMember']['co_group_id'])),
                            array('key' => 'error'));
          $this->performRedirect();
        }
        
        return false;
      }
    }
    
    // XXX We don't check that the CO Person is actually in the CO... should we?
    
    return true;
  }
  
  /**
   * Update a Standard Object.
   *
   * @since  COmanage Registry v1.0.0
   * @param  integer Object identifier (eg: cm_co_groups:id) representing object to be retrieved
   */
  
  function edit($id) {
    // Call the parent then override the title
    
    parent::edit($id);
    
    $gm = $this->viewVars['co_group_members'][0];
    
    $this->set('title_for_layout', _txt('op.grm.title', array(_txt('op.edit'),
                                                              $gm['CoGroup']['name'],
                                                              generateCn($gm['CoPerson']['PrimaryName']))));
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
        $this->CoGroupMember->CoPerson->HistoryRecord->record($newdata['CoGroupMember']['co_person_id'],
                                                              null,
                                                              null,
                                                              $this->Session->read('Auth.User.co_person_id'),
                                                              ActionEnum::CoGroupMemberAdded,
                                                              _txt('rs.grm.added', array($newdata['CoGroup']['name'],
                                                                                         $newdata['CoGroup']['id'],
                                                                                         _txt($newdata['CoGroupMember']['member'] ? 'fd.yes' : 'fd.no'),
                                                                                         _txt($newdata['CoGroupMember']['owner'] ? 'fd.yes' : 'fd.no'))),
                                                              $olddata['CoGroup']['id']);
        break;
      case 'delete':
        $this->CoGroupMember->CoPerson->HistoryRecord->record($olddata['CoGroupMember']['co_person_id'],
                                                              null,
                                                              null,
                                                              $this->Session->read('Auth.User.co_person_id'),
                                                              ActionEnum::CoGroupMemberDeleted,
                                                              _txt('rs.grm.deleted', array($olddata['CoGroup']['name'],
                                                                                           $olddata['CoGroup']['id'])),
                                                              $olddata['CoGroup']['id']);
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
                                                                                          _txt($newdata['CoGroupMember']['owner'] ? 'fd.yes' : 'fd.no'))),
                                                              $olddata['CoGroup']['id']);
        break;
    }
    
    return true;
  }

  /**
   * Obtain all CO Group Members.
   * - postcondition: $<object>s set on success (REST or HTML), using pagination (HTML only)
   * - postcondition: HTTP status returned (REST)
   * - postcondition: Session flash message updated (HTML) on suitable error
   *
   * @since  COmanage Registry v0.9.3
   */
  
  function index() {
    if($this->request->is('restful') && !empty($this->params['url']['cogroupid'])) {
      // We need to retrieve via a join, which StandardController::index() doesn't
      // currently support.
      
      try {
        $groups = $this->CoGroupMember->findForCoGroup($this->params['url']['cogroupid']);
        
        if(!empty($groups)) {
          $this->set('co_group_members', $this->Api->convertRestResponse($groups));
        } else {
          $this->Api->restResultHeader(204, "CO Person Has No Groups");
          return;
        }
      }
      catch(InvalidArgumentException $e) {
        $this->Api->restResultHeader(404, "CO Person Unknown");
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
    $roles = $this->Role->calculateCMRoles();             // What was authenticated
    
    // Store the group ID in the controller object since performRedirect may need it
    
    if(($this->action == 'add' || $this->action == 'updateGroup')
       && isset($this->request->data['CoGroupMember']['co_group_id']))
      $this->gid = filter_var($this->request->data['CoGroupMember']['co_group_id'], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH | FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_BACKTICK);
    elseif(($this->action == 'delete' || $this->action == 'edit' || $this->action == 'view')
           && isset($this->request->params['pass'][0]))
      $this->gid = $this->CoGroupMember->field('co_group_id', array('CoGroupMember.id' => $this->request->params['pass'][0]));
    elseif($this->action == 'select' && isset($this->request->params['named']['cogroup']))
      $this->gid = filter_var($this->request->params['named']['cogroup'], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH | FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_BACKTICK);
    
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
    
    // Is this specified group read only?
    $readOnly = ($this->gid ? $this->CoGroupMember->CoGroup->readOnly($this->gid) : false);
    
    // Construct the permission set for this user, which will also be passed to the view.
    $p = array();
    
    // Add a new member to a group?
    // XXX probably need to check if group is open here and in delete
    $p['add'] = !$readOnly && ($roles['cmadmin'] || $managed);
    
    // Delete a member from a group?
    $p['delete'] = !$readOnly && ($roles['cmadmin'] || $managed);
    
    // Edit members of a group?
    $p['edit'] = !$readOnly && ($roles['cmadmin'] || $managed);
    
    // View a list of members of a group?
    // This is for REST
    $p['index'] = ($this->request->is('restful') && ($roles['cmadmin'] || $roles['coadmin']));
    
    // Select from a list of potential members to add?
    $p['select'] = !$readOnly && ($roles['cmadmin'] || $managed);
    
    // Update accepts a CO Person's worth of potential group memberships and performs the appropriate updates
    $p['update'] = !$readOnly && ($roles['cmadmin'] || $roles['comember']);
    
    // Select from a list of potential members to add?
    $p['updateGroup'] = !$readOnly && ($roles['cmadmin'] || $managed);
    
    // Search / filter a list of members in the select list?
    $p['search'] = !$readOnly && ($roles['cmadmin'] || $managed);
    
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
    
    if($this->action == 'add' && isset($this->request->data['CoGroupMember']['co_person_id'])) {
      $cop = $this->request->data['CoGroupMember']['co_person_id'];
    } elseif($this->action == 'delete'
             && isset($this->request->params['named']['return'])) {
      if($this->request->params['named']['return'] == 'person'
         && isset($this->request->params['named']['copersonid'])) {
        $cop = $this->request->params['named']['copersonid'];
      }
      // else return = group
    }
    
    if(isset($cop)) {
      $params = array('controller' => 'co_people',
                      'action'     => 'canvas',
                      $cop
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
    // Start by using paginate to pull the set of group members.

    if(!$this->gid) {
      throw new InvalidArgumentException(_txt('er.notprov.id', array(_txt('ct.co_groups.1'))));
    }
    
    $this->Paginator->settings = $this->paginate;
    $this->Paginator->settings['joins'][0] = array(
      'table'      => 'co_groups',
      'alias'      => 'CoGroup',
      'type'       => 'INNER',
      'conditions' => array('CoPerson.co_id=CoGroup.co_id')
    );
    $this->Paginator->settings['conditions'] = array('CoGroup.id' => $this->gid);

    // Gather up search filters, if present:
    // Filter by Given name
    if(!empty($this->params['named']['search.givenName'])) {
      $searchterm = strtolower($this->params['named']['search.givenName']);
      // We set up LOWER() indices on these columns (CO-1006)
      $this->Paginator->settings['conditions']['LOWER(Name.given) LIKE'] = "%$searchterm%";
    }
    // Filter by Family name
    if(!empty($this->params['named']['search.familyName'])) {
      $searchterm = strtolower($this->params['named']['search.familyName']);
      $this->Paginator->settings['conditions']['LOWER(Name.family) LIKE'] = "%$searchterm%";
    }
    // Now prepare the join on the names
    if(!empty($this->params['named']['search.givenName'])
      || !empty($this->params['named']['search.familyName'])) {
      $this->Paginator->settings['joins'][] = array(
        'table' => 'names',
        'alias' => 'Name',
        'type' => 'INNER',
        'conditions' => array(
          'Name.co_person_id=CoPerson.id'
        )
      );
    }

    // Filter by start of Primary Family name (starts with searchterm)
    if(!empty($this->params['named']['search.familyNameStart'])) {
      $searchterm = strtolower($this->params['named']['search.familyNameStart']);
      $this->Paginator->settings['conditions']['LOWER(PrimaryName.family) LIKE'] = "$searchterm%";
    }

    // Filter by email address
    if(!empty($this->params['named']['search.mail'])) {
      $searchterm = strtolower($this->params['named']['search.mail']);
      $this->Paginator->settings['conditions']['LOWER(EmailAddress.mail) LIKE'] = "%$searchterm%";
      $this->Paginator->settings['joins'][] = array(
        'table' => 'email_addresses',
        'alias' => 'EmailAddress',
        'type' => 'INNER',
        'conditions' => array(
          'EmailAddress.co_person_id=CoPerson.id'
        )
      );
    }

    // Filter by identifier
    if(!empty($this->params['named']['search.identifier'])) {
      $searchterm = strtolower($this->params['named']['search.identifier']);
      $this->Paginator->settings['conditions']['LOWER(Identifier.identifier) LIKE'] = "%$searchterm%";
      $this->Paginator->settings['joins'][] = array(
        'table' => 'identifiers',
        'alias' => 'Identifier',
        'type' => 'INNER',
        'conditions' => array(
          'Identifier.co_person_id=CoPerson.id'
        )
      );
    }

    // Filter by status
    if(!empty($this->params['named']['search.status'])) {
      $searchterm = $this->params['named']['search.status'];
      $this->Paginator->settings['conditions']['CoPerson.status'] = $searchterm;
    }

    // Filter by members and owners
    // If both member and owner are selected, use a single join with "OR"
    if(!empty($this->params['named']['search.members']) && !empty($this->params['named']['search.owners'])) {
      $this->Paginator->settings['joins'][] = array(
        'table' => 'co_group_members',
        'alias' => 'CoGroupMember',
        'type' => 'INNER',
        'conditions' => array(
          "CoGroupMember.co_person_id=CoPerson.id",
          "CoGroupMember.co_group_id=" . $this->gid,
          'OR' => array(
            "CoGroupMember.member = true",
            "CoGroupMember.owner = true"
          )
        )
      );
    } else {
      // Otherwise filter for members and owners individually
      // Filter by membership
      if (!empty($this->params['named']['search.members'])) {
        $this->Paginator->settings['joins'][] = array(
          'table' => 'co_group_members',
          'alias' => 'CoGroupMember',
          'type' => 'INNER',
          'conditions' => array(
            "CoGroupMember.co_person_id=CoPerson.id",
            "CoGroupMember.co_group_id=" . $this->gid,
            "CoGroupMember.member = true"
          )
        );
      }

      // Filter by ownership
      if (!empty($this->params['named']['search.owners'])) {
        $this->Paginator->settings['joins'][] = array(
          'table' => 'co_group_members',
          'alias' => 'CoGroupMember',
          'type' => 'INNER',
          'conditions' => array(
            "CoGroupMember.co_person_id=CoPerson.id",
            "CoGroupMember.co_group_id=" . $this->gid,
            "CoGroupMember.owner = true"
          )
        );
      }
    }
    
    $this->Paginator->settings['contain'] = array(
      // Make sure to contain only the CoGroupMembership we're interested in
      // This doesn't appear to actually work, so we'll pull the group membership separately
      // 'CoGroupMember' => array('conditions' => array('CoGroupMember.id' => $this->gid)),
      'PrimaryName'
    );

    $coPeople = $this->Paginator->paginate('CoPerson');

    $this->set('co_people', $coPeople);
    
    // Pull the CoGroupMemberships for the retrieved CoPeople
    $coPids = Hash::extract($coPeople, '{n}.CoPerson.id');
    
    $args = array();
    $args['conditions']['CoGroupMember.co_person_id'] = $coPids;
    $args['conditions']['CoGroupMember.co_group_id'] = $this->gid;
    $args['contain'] = false;
    
    $coGroupMembers = $this->CoGroupMember->find('all', $args);
    $coGroupRoles = array();
    
    // Make one pass through to facilitate rendering
    foreach($coGroupMembers as $m) {
      if(isset($m['CoGroupMember']['member']) && $m['CoGroupMember']['member']) {
        $coGroupRoles['members'][ $m['CoGroupMember']['co_person_id'] ] = $m['CoGroupMember']['id'];
      }
      
      if(isset($m['CoGroupMember']['owner']) && $m['CoGroupMember']['owner']) {
        $coGroupRoles['owners'][ $m['CoGroupMember']['co_person_id'] ] = $m['CoGroupMember']['id'];
      }
    }
    
    $this->set('co_group_roles', $coGroupRoles);
    
    // Also find the Group so that its details like name can be rendered
    
    $args = array();
    $args['conditions']['CoGroup.id'] = $this->gid;
    $args['contain'] = array('Co');
    
    $coGroup = $this->CoGroupMember->CoGroup->find('first', $args);
    
    $this->set('co_group', $coGroup);
  }
  
  /**
   * Process an update to a CO Person's CO Group Memberships.
   * - precondition: $this->request->params holds coperson
   * - postcondition: Redirect generated
   *
   * @since  COmanage Registry v0.8
   */
  
  public function update() {
    if(!$this->request->is('restful')) {
      $targetCoPersonId = $this->request->data['CoGroupMember']['co_person_id'];
      $userCoPersonId   = $this->Session->read('Auth.User.co_person_id');
      $requesterIsAdmin = $this->Role->isCoAdmin($userCoPersonId, $this->cur_co['Co']['id'])
                          || $this->Role->identifierIsCmpAdmin($this->Session->read('Auth.User.username'));
      
      try {
        $this->CoGroupMember->updateMemberships($targetCoPersonId,
                                                $this->request->data['CoGroupMember']['rows'],
                                                $userCoPersonId,
                                                $requesterIsAdmin);
        
        $this->Flash->set(_txt('rs.saved'), array('key' => 'success'));
      }
      catch(Exception $e) {
        $this->Flash->set($e->getMessage(), array('key' => 'error'));
      }
      
      // Issue redirect
      $redir = array();
      $redir['controller'] = 'co_groups';
      $redir['action']     = 'select';
      $redir['co']         = $this->cur_co['Co']['id'];

      // If the current user is not the same as the target CO Person for whom
      // memberships are being managed then include the copersonid parameter.
      if($targetCoPersonId != $userCoPersonId) {
        $redir['copersonid'] = $targetCoPersonId;
      }

      $this->redirect($redir);
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
    if(!$this->request->is('restful')) {
      try {
        $this->CoGroupMember->updateGroupMemberships($this->request->data['CoGroupMember']['co_group_id'],
                                                     $this->request->data['CoGroupMember']['rows'],
                                                     $this->Session->read('Auth.User.co_person_id'));
        
        $this->Flash->set(_txt('rs.saved'), array('key' => 'success'));
      }
      catch(Exception $e) {
        $this->Flash->set($e->getMessage(), array('key' => 'error'));
      }
      
      // Issue redirect
      
      $this->redirect(array('controller' => 'co_groups',
                            'action'     => 'edit',
                            $this->request->data['CoGroupMember']['co_group_id'],
                            'co'         => $this->cur_co['Co']['id']));
    }
  }

  /**
   * Insert search parameters into URL for members selection
   * - precondition: $this->request->params holds cogroup
   * - postcondition: Redirect generated
   *
   * @since  COmanage Registry v3.3.0
   */

  public function search() {
    $url['action'] = 'select';
    $url['cogroup'] = $this->request->data['CoGroupMember']['cogroup'];

    // Append the URL with all the search elements; the resulting URL will be similar to
    // example.com/registry/co_group_members/select/cogroup:xxx/search.givenName:albert/search.familyName:einstein
    foreach($this->data['search'] as $field=>$value){
      if(!empty($value)) {
        $url['search.'.$field] = $value;
      }
    }

    // redirect the user to the url
    $this->redirect($url, null, true);
  }
  
  /**
   * View a Standard Object.
   *
   * @since  COmanage Registry v1.0.0
   * @param  integer Object identifier (eg: cm_co_groups:id) representing object to be retrieved
   */
  
  function view($id) {
    // Call the parent then override the title
    
    parent::view($id);
    
    if(!$this->request->is('restful')) {
      $gm = $this->viewVars['co_group_members'][0];
      
      $this->set('title_for_layout', _txt('op.grm.title', array(_txt('op.view'),
                                                                $gm['CoGroup']['name'],
                                                                generateCn($gm['CoPerson']['PrimaryName']))));
    }
  }
}
