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
      'Name.family' => 'asc',
      'Name.given' => 'asc'
    )
  );
  
  // This controller needs a CO to be set
  public $requires_co = true;

  // Edit and view need Name for rendering view
  public $edit_contains = array(
    'CoGroup',
    // XXX Adding a condition for the nested model does not work
    // 'CoPerson' => array('PrimaryName' => array('conditions' => array('PrimaryName.primary_name' => true))),
    // As a result i fetch all the names and i will filter at the View Side
    'CoPerson' => array('Name'),
    'CoGroupNesting' => array('CoGroup')
  );

  // We need the Group
  public $delete_contains = array(
    'CoGroup'
  );

  public $view_contains = array(
    'CoGroup',
    // XXX Adding a condition for the nested model does not work
    // 'CoPerson' => array('PrimaryName' => array('conditions' => array('PrimaryName.primary_name' => true))),
    // As a result i fetch all the names and i will filter at the View Side
    'CoPerson' => array('Name'),
    'CoGroupNesting' => array('CoGroup')
  );
  
  // We need to track the group ID under certain circumstances to enable performRedirect
  private $gid = null;
  
  // Determined during isAuthorized() and used for applying restrictions on rest operations 
  private $isCoAdmin = false;
  private $isCmpAdmin = false;

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

    // Filter primary Name
    $primary_name = array_filter(
      $gm["CoPerson"]["Name"],
      static function($item) {
        return $item['primary_name'] == true;
      }
    );
    $this->set('title_for_layout', _txt('op.grm.title', array(_txt('op.edit'),
                                                              $gm['CoGroup']['name'],
                                                              generateCn($primary_name[0]))));
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
    $actorCoPersonId = $this->request->is('restful') ? null : $this->Session->read('Auth.User.co_person_id');
    $actorApiUserId = $this->request->is('restful') ? $this->Auth->User('id') : null;

    switch($action) {
      case 'add':
        $this->CoGroupMember->CoPerson->HistoryRecord->record($newdata['CoGroupMember']['co_person_id'],
                                                              null,
                                                              null,
                                                              $actorCoPersonId,
                                                              ActionEnum::CoGroupMemberAdded,
                                                              _txt('rs.grm.added', array($newdata['CoGroup']['name'],
                                                                                         $newdata['CoGroup']['id'],
                                                                                         _txt($newdata['CoGroupMember']['member'] ? 'fd.yes' : 'fd.no'),
                                                                                         _txt($newdata['CoGroupMember']['owner'] ? 'fd.yes' : 'fd.no'))),
                                                              $olddata['CoGroup']['id'],
                                                              null, null,
                                                              $actorApiUserId);
        break;
      case 'delete':
        $this->CoGroupMember->CoPerson->HistoryRecord->record($olddata['CoGroupMember']['co_person_id'],
                                                              null,
                                                              null,
                                                              $actorCoPersonId,
                                                              ActionEnum::CoGroupMemberDeleted,
                                                              _txt('rs.grm.deleted',
                                                                   array($olddata['CoGroup']['name'], $olddata['CoGroup']['id'])),
                                                              $olddata['CoGroup']['id'],
                                                              null, null,
                                                              $actorApiUserId);
        break;
      case 'edit':
        $this->CoGroupMember->CoPerson->HistoryRecord->record($olddata['CoGroupMember']['co_person_id'],
                                                              null,
                                                              null,
                                                              $actorCoPersonId,
                                                              ActionEnum::CoGroupMemberEdited,
                                                              _txt('rs.grm.edited', array($olddata['CoGroup']['name'],
                                                                                          $olddata['CoGroup']['id'],
                                                                                          _txt($olddata['CoGroupMember']['member'] ? 'fd.yes' : 'fd.no'),
                                                                                          _txt($olddata['CoGroupMember']['owner'] ? 'fd.yes' : 'fd.no'),
                                                                                          _txt($newdata['CoGroupMember']['member'] ? 'fd.yes' : 'fd.no'),
                                                                                          _txt($newdata['CoGroupMember']['owner'] ? 'fd.yes' : 'fd.no'))),
                                                              $olddata['CoGroup']['id'],
                                                              null, null,
                                                              $actorApiUserId);
        break;
    }
    
    return true;
  }

  /**
   * Obtain / list all CO Group Members.
   * - postcondition: $<object>s set on success (REST or HTML), using pagination (HTML only)
   * - postcondition: HTTP status returned (REST)
   * - postcondition: Session flash message updated (HTML) on suitable error
   *
   * @since  COmanage Registry v0.9.3
   */
  
  function index() {
    $req = $this->modelClass;
    $model = $this->$req;
    $requestKeys = array();

    if(!$this->request->is('restful')) {
      // XXX Only when member or owner is part of the query parameters we
      //     do things the CoGroupMembersController way
      if(!empty($this->request->params['named']['search.members'])
        || !empty($this->request->params['named']['search.owners'])) {
        parent::index();
      } else {
        $this->paginationConditions();
      }

      // We are querying for the CoGroup even though we have it in the pagination in case
      // the dataset returns empty. If it does, we will have no CoGroup to retrieve the information
      // we need.

      $args = array();
      $args['conditions']['CoGroup.id'] = $this->gid;
      $args['contain'] = false;

      $coGroup = $this->CoGroupMember->CoGroup->find('first', $args);

      $this->set('co_group', $coGroup);
      return;
    }

    if (!$this->isCoAdmin && !$this->isCmpAdmin) {
      $this->Api->restResultHeader(HttpStatusCodesEnum::HTTP_UNAUTHORIZED, _txt('er.http.401'));
    }

    $this->set('vv_model_version', $this->CoGroupMember->version);

    try {
      if(!empty($this->params['url']['cogroupid'])
         && empty($this->params['url']['copersonid'])) {
        $group_members = $this->CoGroupMember->findRecord(array($this->params['url']['cogroupid'] => 'CoGroup'));
        $searching_for = 'CoGroup';
        $this->set('co_group_members', $this->Api->convertRestResponse($group_members ?? []));
        return;
      } elseif(!empty($this->params['url']['copersonid'])
               && empty($this->params['url']['cogroupid'])) {
        $group_members = $this->CoGroupMember->findRecord(array($this->params['url']['copersonid'] => 'CoPerson'));
        $searching_for = 'CoPerson';
        $this->set('co_group_members', $this->Api->convertRestResponse($group_members ?? []));
        return;
      } elseif(!empty($this->params['url']['copersonid'])
               && !empty($this->params['url']['cogroupid'])) {
        $group_members = $this->CoGroupMember->findRecord(array($this->params['url']['copersonid'] => 'CoPerson',
                                                                $this->params['url']['cogroupid'] => 'CoGroup'));
        $searching_for = 'CoPerson/CoGroup';
        $this->set('co_group_members', $this->Api->convertRestResponse($group_members ?? []));
        return;
      }

      if((!empty($this->params['url']['copersonid'])
             || !empty($this->params['url']['cogroupid']))
          && empty($group_members)) {
        $this->Api->restResultHeader(204, _txt('er.grm.none-a'));
      }

      if($this->isCmpAdmin) {
        $group_members = $this->CoGroupMember->findRecord();
        $searching_for = 'Platform';
        $this->set('co_group_members', $this->Api->convertRestResponse($group_members ?? []));
        return;
      }

      $this->Api->restResultHeader(HttpStatusCodesEnum::HTTP_NO_CONTENT, _txt('er.mt.unknown', array($searching_for)));
      return;

    }
    catch(InvalidArgumentException $e) {
      $this->Api->restResultHeader(HttpStatusCodesEnum::HTTP_NOT_FOUND, _txt('er.mt.unknown', array("Configuration")));
      return;
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
    
    if(($this->action == 'add' || $this->action == 'updateGroup' || $this->action == 'addMemberById')
       && isset($this->request->data['CoGroupMember']['co_group_id'])) {
      $this->gid = filter_var($this->request->data['CoGroupMember']['co_group_id'], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH | FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_BACKTICK);
    } elseif(($this->action == 'delete' || $this->action == 'edit' || $this->action == 'view')
           && isset($this->request->params['pass'][0])) {
      $this->gid = $this->CoGroupMember->field('co_group_id', array('CoGroupMember.id' => $this->request->params['pass'][0]));
    } elseif(($this->action == 'select' || $this->action == 'index')) {
      if(!empty($this->request->params['named']['cogroup'])) {
        $this->gid = filter_var($this->request->params['named']['cogroup'], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH | FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_BACKTICK);
      } elseif(!empty($this->request->params['pass'][0])) {
        $this->gid = $this->CoGroupMember->field('co_group_id', array('CoGroupMember.id' => $this->request->params['pass'][0]));
      }
    }
    
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
    
    // If this user is an CMP or CO admin, make this known to the controller:
    $this->isCmpAdmin = $roles['cmadmin'];
    $this->isCoAdmin = $roles['coadmin'];

    // Construct the permission set for this user, which will also be passed to the view.
    $p = array();
    
    // Add a new member to a group?
    // XXX probably need to check if group is open here and in delete
    $p['add'] = !$readOnly && ($roles['cmadmin'] || $roles['coadmin'] || $managed);
    
    // Delete a member from a group?
    $p['delete'] = !$readOnly && ($roles['cmadmin'] || $roles['coadmin'] || $managed);
    
    // Edit members of a group?
    $p['edit'] = !$readOnly && ($roles['cmadmin'] || $roles['coadmin'] || $managed);
    
    // View a list of members of a group?
    $p['index'] = ($roles['cmadmin'] || $roles['coadmin'] || $managed || $member);
    
    // Select from a list of potential members to add?
    $p['select'] = !$readOnly && ($roles['cmadmin'] || $roles['coadmin'] || $managed);
    
    // Update accepts a CO Person's worth of potential group memberships and performs the appropriate updates
    $p['update'] = !$readOnly && ($roles['cmadmin'] || $roles['coadmin'] || $roles['comember']);
    
    // Select from a list of potential members to add?
    $p['updateGroup'] = !$readOnly && ($roles['cmadmin'] || $roles['coadmin'] || $managed);

    // Add member by ID
    $p['addMemberById'] = !$readOnly && ($roles['cmadmin'] || $roles['coadmin'] || $managed);

    // (Re)provision an existing CO Group? This permission grants access to the Provisioned Services tab
    $p['provision'] = ($roles['cmadmin'] || $roles['coadmin'] || $roles['couadmin']);
    
    // Search / filter a list of members in the select list?
    // We need co member so group owners can search for purposes of adding members
    $p['search'] = $roles['cmadmin'] || $roles['coadmin'] || $managed || $roles['comember'];
    
    // View members of a group?
    $p['view'] = ($roles['cmadmin'] || $roles['coadmin'] || $managed || $member);

    // View nested group? This determines if we build links to nested groups for a user.
    // We skip $managed because the user may have no rights to view the source group.
    $p['viewNestedGroup'] = ($roles['cmadmin'] || $roles['coadmin'] || $roles['couadmin']);

    // View any user's canvas page? This is used when creating links to CoPerson canvas from the select view.
    // Limit this to platform and CO admins
    $p['viewUserCanvas'] = ($roles['cmadmin'] || $roles['coadmin']);
    
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
      $params = array('controller'     => 'co_group_members',
                      'action'         => 'select',
                      'cogroup'        => $this->gid,
                      'search.members' => '1',
                      'search.owners'  => '1'
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
   * Search Block fields configuration
   *
   * @since  COmanage Registry v4.3.0
   */

  public function searchConfig($action) {
    if($action === 'index'                   // Index
      || $action === 'select') {             // Select
      return array(
        'search.givenName' => array(
          'label' => _txt('fd.name.given'),
          'type' => 'text',
        ),
        'search.familyName' => array(
          'label' => _txt('fd.name.family'),
          'type' => 'text',
        ),
        'search.mail' => array(
          'label' => _txt('fd.email_address.mail'),
          'type' => 'text',
        ),
        'search.identifier' => array(
          'label' => _txt('fd.description'),
          'type' => 'text',
        ),
        'search.status' => array(
          'label' => _txt('fd.status'),
          'type' => 'select',
          'empty'   => _txt('op.select.all'),
          'options' => _txt('en.status.susp'),
        ),
        'search.nested' => array(
          'label' => _txt('fd.nested'),
          'type' => 'select',
          'empty'   => _txt('op.select.all'),
          'options' => _txt('en.nested.filters'),
        ),
        'search.members' => array(
          'label' => _txt('fd.members'),
          'type' => 'checkbox',
          'group' => _txt('fd.members'),
          'column' => 0
        ),
        'search.owners' => array(
          'label' => _txt('fd.owners'),
          'group' => _txt('fd.members'),
          'type' => 'checkbox',
          'column' => 0
        ),
      );
    }
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
    // Render the member select list to the browser
    $this->index();
  }

  /**
   * Determine the conditions for pagination of the index view, when rendered via the UI.
   *
   * @since  COmanage Registry v4.3.0
   * @return Array An array suitable for use in $this->paginate
   */
  public function paginationConditions() {
    $pagcond = array();
    // No group ID?
    if(!$this->gid) {
      throw new InvalidArgumentException(_txt('er.notprov.id', array(_txt('ct.co_groups.1'))));
    }

    if($this->requires_co) {
      $pagcond['conditions']['CoPerson.co_id'] = $this->cur_co['Co']['id'];
    }

    // We also need to pass member/ownership in these groups for the view.
    // Note we do this differently than above for no particular reason.
    $args = array();
    $args['conditions']['CoGroupMember.co_group_id'] = $this->gid;
    $args['conditions']['AND'][] = array(
      'OR' => array(
        'CoGroupMember.member' => true,
        'CoGroupMember.owner' => true,
      )
    );

    $this->set('vv_group_members_count', $this->CoGroupMember->find('count', $args));

    // Filtering by name operates using any name, so preferred or other names
    // can also be searched. However, filter by letter ("familyNameStart") only
    // works on PrimaryName so that the results match the index list.

    // Filter by Given name
    if(!empty($this->request->params['named']['search.givenName'])) {
      $searchterm = $this->request->params['named']['search.givenName'];
      $searchterm = str_replace(urlencode("/"), "/", $searchterm);
      $searchterm = str_replace(urlencode(" "), " ", $searchterm);
      $searchterm = trim(strtolower($searchterm));
      // We set up LOWER() indices on these columns (CO-1006)
      $pagcond['conditions']['LOWER(Name.given) LIKE'] = "%$searchterm%";
    }

    // Filter by Family name
    if(!empty($this->request->params['named']['search.familyName'])) {
      $searchterm = $this->request->params['named']['search.familyName'];
      $searchterm = str_replace(urlencode("/"), "/", $searchterm);
      $searchterm = str_replace(urlencode(" "), " ", $searchterm);
      $searchterm = trim(strtolower($searchterm));
      $pagcond['conditions']['LOWER(Name.family) LIKE'] = "%$searchterm%";
    }

    $jcnt = 0;
    if(!empty($this->request->params['named']['search.givenName'])
      || !empty($this->request->params['named']['search.familyName'])) {
      $pagcond['conditions']['Name.primary_name'] = true;
      $pagcond['joins'][$jcnt]['table'] = 'names';
      $pagcond['joins'][$jcnt]['alias'] = 'Name';
      $pagcond['joins'][$jcnt]['type'] = 'INNER';
      $pagcond['joins'][$jcnt]['conditions'][0] = 'Name.co_person_id=CoPerson.id';
      $jcnt++;
    }

    // Filter by start of Primary Family name (starts with searchterm)
    if(!empty($this->request->params['named']['search.familyNameStart'])) {
      $searchterm = $this->request->params['named']['search.familyNameStart'];
      $searchterm = str_replace(urlencode("/"), "/", $searchterm);
      $searchterm = str_replace(urlencode(" "), " ", $searchterm);
      $searchterm = trim(strtolower($searchterm));
      $pagcond['conditions']['LOWER(PrimaryName.family) LIKE'] = "$searchterm%";
    }

    // Filter by email address
    if(!empty($this->request->params['named']['search.mail'])) {
      $searchterm = $this->request->params['named']['search.mail'];
      $searchterm = str_replace(urlencode("/"), "/", $searchterm);
      $searchterm = str_replace(urlencode(" "), " ", $searchterm);
      $searchterm = trim(strtolower($searchterm));
      $pagcond['conditions']['LOWER(EmailAddress.mail) LIKE'] = "%$searchterm%";
      $pagcond['joins'][$jcnt]['table'] = 'email_addresses';
      $pagcond['joins'][$jcnt]['alias'] = 'EmailAddress';
      $pagcond['joins'][$jcnt]['type'] = 'INNER';
      $pagcond['joins'][$jcnt]['conditions'][0] = 'EmailAddress.co_person_id=CoPerson.id';
      $jcnt++;

      // See also the note below about searching org identities for identifiers.
    }

    // Filter by identifier
    if(!empty($this->request->params['named']['search.identifier'])) {
      $searchterm = $this->request->params['named']['search.identifier'];
      $searchterm = str_replace(urlencode("/"), "/", $searchterm);
      $searchterm = str_replace(urlencode(" "), " ", $searchterm);
      $searchterm = trim(strtolower($searchterm));
      $pagcond['conditions']['LOWER(Identifier.identifier) LIKE'] = "%$searchterm%";
      $pagcond['joins'][$jcnt]['table'] = 'identifiers';
      $pagcond['joins'][$jcnt]['alias'] = 'Identifier';
      $pagcond['joins'][$jcnt]['type'] = 'INNER';
      $pagcond['joins'][$jcnt]['conditions'][0] = 'Identifier.co_person_id=CoPerson.id';
      $jcnt++;

      // We also want to search on identifiers attached to org identities.
      // This requires a fairly complicated join that doesn't quite work right
      // and that Cake doesn't really support in our current model configuration.
      // This probably needs to be implemented as part of CO-819, or perhaps
      // using a custom paginator.
    }

    // Filter by status
    if(!empty($this->request->params['named']['search.status'])) {
      $searchterm = $this->request->params['named']['search.status'];
      $pagcond['conditions']['CoPerson.status'] = $searchterm;
    }

    // Filter by members, owners, and nested (direct/indirect) membership
    // index() view always limits its output to members and owners
    // select() view does not
    // XXX Paginate CoGroupMembers
    if(!empty($this->request->params['named']['search.members'])
       || !empty($this->request->params['named']['search.owners'])) {


      // We have already joined the table so skip
      if(empty($this->request->params['named']['search.givenName'])
        && empty($this->request->params['named']['search.familyName'])) {
        $pagcond['conditions']['Name.primary_name'] = true;
        $pagcond['joins'][$jcnt]['table'] = 'names';
        $pagcond['joins'][$jcnt]['alias'] = 'Name';
        $pagcond['joins'][$jcnt]['type'] = 'INNER';
        $pagcond['joins'][$jcnt]['conditions'][0] = 'Name.co_person_id=CoGroupMember.co_person_id';
        $jcnt++;
    }

      // Include member/owner filters?
      if(!empty($this->request->params['named']['search.owners'])
          && empty($this->request->params['named']['search.members'])) {
        // The "owner" filter is directly selected, but not "member" - show only owners.
        // Note that this will (quite rightly) be a null set if indirect nested membership is also selected
        $pagcond['conditions'][] = "CoGroupMember.owner = true";
      } elseif(!empty($this->request->params['named']['search.members'])
               && empty($this->request->params['named']['search.owners'])) {
        // The "member" filter is selected, but not "owner" - show only members.
        $pagcond['conditions'][] = "CoGroupMember.member = true";
      } elseif((!empty($this->request->params['named']['search.members'])
                   && !empty($this->request->params['named']['search.owners']))
               || ((empty($this->request->params['named']['search.members'])
                    && empty($this->request->params['named']['search.owners']))
                   && !empty($this->request->params['named']['search.nested']))) {
        // Always limit to members and owners if:
        // - we're in the index() view or
        // - both member and owner filters are selected or
        // - neither member nor owner filters are selected but the nesting filter is
        $pagcond['conditions']['OR'] = array();
        $pagcond['conditions']['OR'][] = "CoGroupMember.member = true";
        $pagcond['conditions']['OR'][] = "CoGroupMember.owner = true";
      }

      // Filter by Group Id
      $pagcond['conditions']['CoGroupMember.co_group_id'] = $this->gid;

      // Nested groups
      if(!empty($this->request->params['named']['search.nested'])
        && in_array($this->request->params['named']['search.nested'], _txt('en.nested.filters'))) {
        $searchterm = $this->request->params['named']['search.nested'];
        if ($searchterm == NestedEnum::Direct) {
          $cond_rule = "CoGroupMember.co_group_nesting_id IS NULL";
        } else if ($searchterm == NestedEnum::Indirect) {
          $cond_rule = "CoGroupMember.co_group_nesting_id IS NOT NULL";
        }
        $pagcond['conditions'][] = $cond_rule;
      }

      $pagcond['sortlist'] = array('Name.family', 'Name.given');

      return $pagcond;
    }

    // Paginate CoPeople
    // We need to manually add this in for some reason. (It should have been
    // added automatically by Cake based on the CoPerson Model definition of
    // PrimaryName.)
    $pagcond['conditions']['PrimaryName.primary_name'] = true;

    $pagcond['order'] = array(
      'PrimaryName.family' => 'asc',
      'PrimaryName.given' => 'asc'
    );

    $pagcond['contain'] = array(
      'Co',
      'CoPersonRole' => array('CoPetition', 'Cou'),
      'EmailAddress',
      'Identifier',
      'Name',
      'PrimaryName' => array('conditions' => array('PrimaryName.primary_name' => true)),
      'Url',
      'CoGroupMember' => array(
        'conditions' => array(
          'co_group_id' => $this->gid,
          'OR' => array(
            "CoGroupMember.member = true",
            "CoGroupMember.owner = true"
          )
        ),
        'CoGroup',
        'CoGroupNesting' => array('CoGroup')
      )
    );

    if(!empty($pagcond['conditions'])) {
      $this->paginate['conditions'] = $pagcond['conditions'];
    }

    if(!empty($pagcond['order'])) {
      $this->paginate['order'] = $pagcond['order'];
    }

    if(!empty($pagcond['fields'])) {
      $this->paginate['fields'] = $pagcond['fields'];
    }

    if(!empty($pagcond['group'])) {
      $this->paginate['group'] = $pagcond['group'];
    }

    if(!empty($pagcond['joins'])) {
      $this->paginate['joins'] = $pagcond['joins'];
    }

    if(isset($pagcond['contain'])) {
      $this->paginate['contain'] = $pagcond['contain'];
    }

    // Used either to enumerate which fields can be used for sorting, or
    // explicitly naming sortable fields for complex relations (ie: using
    // linkable behavior).
    $sortlist = array();

    if(!empty($local['sortlist'])) {
      $sortlist = $local['sortlist'];
    }

    $this->Paginator->settings = $this->paginate;

    $this->set('co_people', $this->Paginator->paginate('CoPerson', array(), $sortlist));
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

      // Issue redirect - default to members and owners filter
      $this->redirect(array('controller' => 'co_group_members',
        'action'     => 'select',
        'cogroup'    => $this->request->data['CoGroupMember']['co_group_id'],
        'co'         => $this->cur_co['Co']['id'],
        'search.members:1',
        'search.owners:1'));
    }
  }
  
  /**
   * Add a member to a group directly by CoPerson ID.
   * - precondition: $this->request->data holds CoGroupMember
   * - postcondition: CoPerson added to a group as a member
   *
   * @since COmanage Registry v4.0
   */
  public function addMemberById() {
    if(!$this->request->is('restful')) {
      $gid = $this->request->data['CoGroupMember']['co_group_id'];
      $gnm = $this->request->data['CoGroupMember']['co_group_name'];
      $pid = $this->request->data['CoGroupMember']['co_person_id'];
      $plb = $this->request->data['CoGroupMember']['co_person_label'];
      try {
        if($this->CoGroupMember->isMember($gid,$pid)) {
          // CoPerson is already a member
          $this->Flash->set(_txt('er.grm.already',array($plb,$gnm)), array('key' => 'error'));
        } else {
          // Add CoPerson to the group
          $this->CoGroupMember->addByGroupName($pid,$gnm);
          $this->Flash->set(_txt('rs.grm.added-d',array($plb,$gnm)), array('key' => 'success'));
        }
      }
      catch(Exception $e) {
        $this->Flash->set($e->getMessage(), array('key' => 'error'));
      }

      // Issue redirect - default to members and owners filter
      $this->redirect(array('controller' => 'co_group_members',
        'action'     => 'select',
        'cogroup'    => $gid,
        'search.members:1',
        'search.owners:1'));
    }
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

      // Filter primary Name
      $primary_name = array_filter(
        $gm["CoPerson"]["Name"],
        static function($item) {
          return $item['primary_name'] == true;
        }
      );
      $this->set('title_for_layout', _txt('op.grm.title', array(_txt('op.view'),
                                                                $gm['CoGroup']['name'],
                                                                $primary_name[0])));
    }
  }
}
