<?php
/**
 * COmanage Registry CO Group Controller
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
  
  public $delete_contains = array(
  );
  
  public $edit_contains = array(
    'CoGroupNesting' => array('CoGroup'),
    'SourceCoGroupNesting' => array('TargetCoGroup'),
    'EmailListAdmin',
    'EmailListMember',
    'EmailListModerator'
  );
  
  public $view_contains = array(
  );
  
  /**
   * Callback to set relevant tab to open when redirecting to another page
   * - precondition:
   * - postcondition: Auth component is configured
   * - postcondition:
   *
   * @since  COmanage Registry v0.8
   */

  function beforeFilter() {
    $this->redirectTab = 'group';

    parent::beforeFilter();
  }

  /**
   * Perform any dependency checks required prior to a delete operation.
   * This method is intended to be overridden by model-specific controllers.
   * - postcondition: Session flash message updated (HTML) or HTTP status returned (REST)
   *
   * @since  COmanage Registry v0.9.4
   * @param  Array Current data
   * @return boolean true if dependency checks succeed, false otherwise.
   */
  
  function checkDeleteDependencies($curdata) {
    // It would be preferable to move this to beforeDelete, but ChangelogBehavior
    // prevents that since beforeDelete callbacks don't fire.
    
    if(isset($curdata['CoGroup']['group_type'])
       && $curdata['CoGroup']['group_type'] != GroupEnum::Standard) {
      if($this->request->is('restful')) {
        $this->Api->restResultHeader(403, "This group cannot be deleted");
      } else {
        $this->Flash->set(_txt('er.gr.delete'), array('key' => 'error'));
      }
      return false;
    }
    
    return true;
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
    if(!isset($curdata) || ($curdata['CoGroup']['name'] != $reqdata['CoGroup']['name'])) {
      // Disallow names beginning with 'admin' if the current user is not an admin.
      
      if(!$this->viewVars['permissions']['admin']) {
        if($reqdata['CoGroup']['name'] == 'admin'
           || strncmp($reqdata['CoGroup']['name'], 'admin:', 6) == 0) {
          if($this->request->is('restful')) {
            $this->Api->restResultHeader(403, "Name Reserved");
          } else {
            $this->Flash->set(_txt('er.gr.res'), array('key' => 'error'));
          }
          
          return false;
        }
      }
      
      // Disallow names beginning with 'CO:', which are reserved.
      if(strncmp($reqdata['CoGroup']['name'], 'CO:', 3) == 0) {
        if($this->request->is('restful')) {
          $this->Api->restResultHeader(403, "Name Reserved");
        } else {
          $this->Flash->set(_txt('er.gr.reserved'), array('key' => 'error'));
        }
  
        return false;
      }
      
      // Make sure name doesn't exist within this CO.
      
      $x = $this->CoGroup->find('all', array('conditions' =>
                                             array('CoGroup.name' => $reqdata['CoGroup']['name'],
                                                   'CoGroup.co_id' => $this->cur_co['Co']['id'])));
      
      if(!empty($x)) {
        if($this->request->is('restful')) {
          $this->Api->restResultHeader(403, "Name In Use");
        } else {
          $this->Flash->set(_txt('er.gr.exists', array($reqdata['CoGroup']['name'])), array('key' => 'error'));
        }
        
        return false;
      }
    }
    
    // Do not allow edits to automatic groups. This probably isn't exactly right, as
    // ultimately it should be possible to (eg) change the description of an automatic group.
    if($curdata['CoGroup']['auto']) {
      if($this->request->is('restful')) {
        $this->Api->restResultHeader(403, "Automatic groups may not be edited directly");
      } else {
        $this->Flash->set(_txt('er.gr.auto.edit'), array('key' => 'error'));
      }
      
      return false;
    }
    
    return true;
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
   * @param  Array Original request data (unmodified by callbacks)
   * @return boolean true if dependency checks succeed, false otherwise.
   */
  
  function checkWriteFollowups($reqdata, $curdata = null, $origdata = null) {
    // Add the co person as owner/member of the new group, but only via HTTP
    
    if(!$this->request->is('restful') && $this->action == 'add') {
      $cos = $this->Session->read('Auth.User.cos');
      
      // Member of current CO? (Platform admin wouldn't be)
      if(isset($cos) && isset($cos[ $this->cur_co['Co']['name'] ]['co_person_id'])) {
        $a['CoGroupMember'] = array(
          'co_group_id' => $this->CoGroup->id,
          'co_person_id' => $this->Session->read('Auth.User.co_person_id'),
          'owner' => true,
          'member' => true
        );
        
        if(!$this->CoGroup->CoGroupMember->save($a)) {
          $this->Flash->set(_txt('er.gr.init'), array('key' => 'information'));
          return false;
        }
      }
    }
    
    return true;
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
    
    if(!$this->request->is('restful') && $this->request->is('get')) {
      // Retrieve the set of all group members for group with ID $id.
      // Specify containable behavior to get necessary relations.
      
      $this->set('vv_co_group_members', $this->CoGroup->findSortedMembers($id));
    }
    
    // Invoke the StandardController edit
    parent::edit($id);
  }
  
  /**
   * Generate history records for a transaction. This method is intended to be
   * overridden by model-specific controllers, and will be called from within a
   * try{} block so that HistoryRecord->record() may be called without worrying
   * about catching exceptions.
   *
   * @since  COmanage Registry v1.0.0
   * @param  String Controller action causing the change
   * @param  Array Data provided as part of the action (for add/edit)
   * @param  Array Previous data (for delete/edit)
   * @return boolean Whether the function completed successfully (which does not necessarily imply history was recorded)
   */
  
  public function generateHistory($action, $newdata, $olddata) {
    switch($action) {
      case 'add':
        $this->CoGroup->HistoryRecord->record(null,
                                              null,
                                              null,
                                              $this->Session->read('Auth.User.co_person_id'),
                                              ActionEnum::CoGroupAdded,
                                              _txt('rs.gr.added', array($newdata['CoGroup']['name'])),
                                              $this->CoGroup->id);
        break;
      case 'delete':
        $this->CoGroup->HistoryRecord->record(null,
                                               null,
                                               null,
                                               $this->Session->read('Auth.User.co_person_id'),
                                               ActionEnum::CoGroupDeleted,
                                               _txt('rs.gr.deleted', array($olddata['CoGroup']['name'])),
                                               $this->CoGroup->id);
        break;
      case 'edit':
        $this->CoGroup->HistoryRecord->record(null,
                                               null,
                                               null,
                                               $this->Session->read('Auth.User.co_person_id'),
                                               ActionEnum::CoGroupEdited,
                                               _txt('en.action', null, ActionEnum::CoGroupEdited) . ": " .
                                               $this->CoGroup->changesToString($newdata, $olddata, $this->cur_co['Co']['id']),
                                               $this->CoGroup->id);
        break;
    }
    
    return true;
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
    if($this->request->is('restful') && !empty($this->params['url']['copersonid'])) {
      // We need to retrieve via a join, which StandardController::index() doesn't
      // currently support.
      
      try {
        $groups = $this->CoGroup->findForCoPerson($this->params['url']['copersonid']);
        
        if(!empty($groups)) {
          $this->set('co_groups', $this->Api->convertRestResponse($groups));
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
    $roles = $this->Role->calculateCMRoles();
    
    $own = array();
    $member = array();
    $managed = false;
    $managedp = false;
    $readonly = false;
    $self = false;
    
    if(!empty($roles['copersonid'])) {
      // XXX Shouldn't this just use CoGroupMember->findCoPersonGroupRoles?
      $args = array();
      $args['conditions']['CoGroupMember.co_person_id'] = $roles['copersonid'];
      $args['conditions']['CoGroupMember.owner'] = true;
      // Only pull currently valid group memberships
      $args['conditions']['AND'][] = array(
        'OR' => array(
          'CoGroupMember.valid_from IS NULL',
          'CoGroupMember.valid_from < ' => date('Y-m-d H:i:s', time())
        )
      );
      $args['conditions']['AND'][] = array(
        'OR' => array(
          'CoGroupMember.valid_through IS NULL',
          'CoGroupMember.valid_through > ' => date('Y-m-d H:i:s', time())
        )
      );
      $args['contain'] = false;
      
      $own = $this->CoGroup->CoGroupMember->find('all', $args);
      
      $args = array();
      $args['conditions']['CoGroupMember.co_person_id'] = $roles['copersonid'];
      $args['conditions']['CoGroupMember.member'] = true;
      // Only pull currently valid group memberships
      $args['conditions']['AND'][] = array(
        'OR' => array(
          'CoGroupMember.valid_from IS NULL',
          'CoGroupMember.valid_from < ' => date('Y-m-d H:i:s', time())
        )
      );
      $args['conditions']['AND'][] = array(
        'OR' => array(
          'CoGroupMember.valid_through IS NULL',
          'CoGroupMember.valid_through > ' => date('Y-m-d H:i:s', time())
        )
      );
      $args['contain'] = false;
      
      $member = $this->CoGroup->CoGroupMember->find('all', $args);
      
      if(!empty($this->request->params['pass'][0])) {
        $managed = $this->Role->isGroupManager($roles['copersonid'], $this->request->params['pass'][0]);
      }
      
      if(!empty($this->request->params['named']['copersonid'])) {
        $managedp = $this->Role->isCoAdminForCoPerson($roles['copersonid'],
                                                      $this->request->params['named']['copersonid']);
        if($roles['copersonid'] == $this->request->params['named']['copersonid']) {
          $self = true;
        }
      } elseif ($roles['copersonid'] == $this->Session->read('Auth.User.co_person_id')) {
        $self = true;
      }
    }
    
    if(!empty($this->request->params['pass'][0])) {
      $readonly = $this->CoGroup->readOnly(filter_var($this->request->params['pass'][0], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH | FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_BACKTICK));
    }
    
    // Construct the permission set for this user, which will also be passed to the view.
    $p = array();
    
    // Determine what operations this user can perform
    
    // Add a new Group?
    $p['add'] = ($roles['cmadmin'] || $roles['coadmin'] || $roles['comember']);
    
    // Create an admin Group?
    $p['admin'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // Delete an existing Group?
    $p['delete'] = (!$readonly && ($roles['cmadmin'] || $managed));
    
    // Edit an existing Group?
    $p['edit'] = (!$readonly && ($roles['cmadmin'] || $managed));
    
    // View history for an existing Group?
    $p['history'] = ($roles['cmadmin'] || $roles['coadmin'] || $managed);
    
    // View all existing Groups?
    $p['index'] = ($roles['cmadmin'] || $roles['coadmin'] || $roles['comember']);
    $p['search'] = $p['index'];
    
    // Nest a Group within another Group?
    // This aligns with CoGroupNestingsController::isAuthorized
    $p['nest'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // Reconcile memberships in a members group?
    $p['reconcile'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    if($this->action == 'index' && $p['index']
       && ($roles['cmadmin'] || $roles['coadmin'])) {
      // Set all permissions for admins so index view links render.
      
      $p['delete'] = true;
      $p['edit'] = true;
      $p['reconcile'] = true;
      $p['view'] = true;
    }
    
    if(isset($own)) {
      // Set array of groups where person is owner
      
      $p['owner'] = array();
      
      foreach($own as $g) {
        $p['owner'][] = $g['CoGroupMember']['co_group_id'];
      }
    }
    
    if(isset($member)) {
      // Set array of groups where person is member
      $p['member'] = array();
      
      foreach($member as $g) {
        $p['member'][] = $g['CoGroupMember']['co_group_id'];
      }
    }
    
    // (Re)provision an existing CO Group?
    $p['provision'] = ($roles['cmadmin'] || $roles['coadmin'] || $roles['couadmin']);

    // Select from a list of potential Groups to join?
    $p['select'] = ($roles['cmadmin']
                    || ($managedp && ($roles['coadmin'] || $roles['couadmin']))
                    || $self);
    
    // Select from any Group (not just open or owned)?
    $p['selectany'] = ($roles['cmadmin']
                       || ($managedp && ($roles['coadmin'] || $roles['couadmin'])));
    
    // View an existing Group?
    $p['view'] = ($roles['cmadmin'] || $roles['coadmin'] || $managed);
    
    if($this->action == 'view'
       && isset($this->request->params['pass'][0])) {
      // Adjust permissions for members and open groups
      
      if(isset($member) && in_array($this->request->params['pass'][0], $p['member']))
        $p['view'] = true;
      
      $args = array();
      $args['conditions']['CoGroup.id'] = $this->request->params['pass'][0];
      $args['contain'] = false;
      
      $g = $this->CoGroup->find('first', $args);
      
      if(!empty($g) && isset($g['CoGroup']['open']) && $g['CoGroup']['open']) {
        $p['view'] = true;
      }
    }
    
    $this->set('permissions', $p);
    return $p[$this->action];
  }

  /**
   * Find the provided CO ID from the query string for the reconcile action
   * or invoke the parent method.
   * - precondition: A coid should be provided in the query string
   *
   * @since  COmanage Registry v1.0.4
   * @return Integer The CO ID if found, or -1 if not
   */
  
  public function parseCOID($data = null) {
    if($this->action == 'reconcile') {
      // CakePHP safely sets to null if not found in query string.
      $coId = $this->request->query('coid');
      if ($coId) {
        return $coId;
      }
    }
    
    return parent::parseCOID();
  }
  
  /**
   * Obtain provisioning status for CO Group
   *
   * @param  integer CO Group ID
   * @since  COmanage Registry v0.8.2
   */
  
  function provision($id) {
    if(!$this->request->is('restful')) {
      // Pull some data for the view to be able to render
      $this->set('co_provisioning_status', $this->CoGroup->provisioningStatus($id));
      
      $args = array();
      $args['conditions']['CoGroup.id'] = $id;
      $args['contain'] = false;
      
      $this->set('co_group', $this->CoGroup->find('first', $args));
      $this->set('title_for_layout',
                 _txt('fd.prov.status.for',
                      array(filter_var($this->viewVars['co_group']['CoGroup']['name'],FILTER_SANITIZE_SPECIAL_CHARS))));
    }
  }
  
  /**
   * Reconcile existence of automatic groups and memberships.
   * - postcondition: HTTP status returned (REST)
   * - postcondition: Redirect issued (HTML)
   * 
   * @since COmanage Registry v0.9.3
   * @param integer CO Group ID of automatic group to reconcile, or null to reconcile existence of automatic groups
   */
  
  function reconcile($id = null) {
    if(!$this->request->is('restful')) {
      // Not currently supported
      $this->performRedirect();
    }
  
    // If no id then reconcile the existence of the default groups
    if(!$id) {
      $coId = $this->request->query('coid');
      
      if(!isset($coId)) {
        $this->Api->restResultHeader(404, 'CO Unknown');
        return;
      }
      
      $args = array();
      $args['conditions']['Co.id'] = $coId;
      $args['contain'] = false;
      $co = $this->CoGroup->Co->find('first', $args);
      
      if(empty($co)) {
        $this->Api->restResultHeader(404, 'CO Unknown');
        return;
      }
      
      try {
        $this->CoGroup->addDefaults($coId);
      }
      catch(Exception $e) {
        $this->Api->restResultHeader(500, $e->getMessage());
        return;        
      }
      
      // Now find and return all automatic groups for the CO.
      $args = array();
      $args['conditions']['CoGroup.co_id'] = $coId;
      $args['conditions']['CoGroup.auto'] = true;
      $args['contain'] = false;
      $groups = $this->CoGroup->find('all', $args);
      
      $this->set('co_groups', $this->Api->convertRestResponse($groups));
      $this->Api->restResultHeader(200, 'OK');
      return; 
    } else {
      // Find the group with the input id.
      try {
        $this->CoGroup->reconcile($id);
        $this->Api->restResultHeader(200, 'OK');
      }
      catch(Exception $e) {
        $this->Api->restResultHeader(500, $e->getMessage());
        return; 
      }      
      
      return;
    }
  }

  /**
   * Callback after controller methods are invoked but before views are rendered.
   *
   * @since  COmanage Registry v0.9.4
   */

  public function beforeRender() {
    global $cm_lang, $cm_texts;
    $this->set('vv_statuses', $cm_texts[ $cm_lang ]['en.status.susp']);
    $this->set('vv_status_open', $cm_texts[ $cm_lang ]['en.status.open']);
    $this->set('vv_status_bool', $cm_texts[ $cm_lang ]['en.status.bool']);
    $this->set('vv_group_type', $cm_texts[ $cm_lang ]['en.group.type']);

    parent::beforeRender();
  }

  /**
   * Insert search parameters into URL for index.
   * - postcondition: Redirect generated
   *
   * @since  COmanage Registry v3.3
   */

  public function search() {
    // If a person ID is provided, we're in select mode
    if(!empty($this->data['CoGroups']['select'])) {
      $url['action'] = 'select';
      $url['copersonid'] = filter_var($this->data['CoPerson']['id'], FILTER_SANITIZE_SPECIAL_CHARS);
    } else {
      // Back to the index
      $url['action'] = 'index';
    }

    $url['co'] = $this->cur_co['Co']['id'];

    // build a URL will all the search elements in it
    // the resulting URL will be similar to example.com/registry/co_groups/index/co:2/search.status:S
    foreach($this->data['search'] as $field=>$value){
      if(!empty($value)) {
        $url['search.'.$field] = $value;
      }
    }

    // redirect the user to the url
    $this->redirect($url, null, true);
  }

  /**
   * Determine the conditions for pagination of the index view, when rendered via the UI.
   *
   * @since  COmanage Registry v3.3
   * @return Array An array suitable for use in $this->paginate
   */

  public function paginationConditions() {
    $pagcond = array();

    // Use server side pagination

    if($this->requires_co) {
      $pagcond['conditions']['CoGroup.co_id'] = $this->cur_co['Co']['id'];
    }

    // Filter by group name
    if(!empty($this->params['named']['search.groupName'])) {
      $searchterm = strtolower($this->params['named']['search.groupName']);
      $pagcond['conditions']['LOWER(CoGroup.name) LIKE'] = "%$searchterm%";
    }

    // Filter by group description
    if(!empty($this->params['named']['search.groupDesc'])) {
      $searchterm = strtolower($this->params['named']['search.groupDesc']);
      $pagcond['conditions']['LOWER(CoGroup.description) LIKE'] = "%$searchterm%";
    }

    // Filter by status
    if(!empty($this->params['named']['search.status'])) {
      $searchterm = $this->params['named']['search.status'];
      $pagcond['conditions']['CoGroup.status'] = $searchterm;
    }

    // Filter by openness
    if(!empty($this->params['named']['search.open'])) {
      $searchterm = $this->params['named']['search.open'];
      $pagcond['conditions']['CoGroup.open'] = $searchterm;
    }

    // Filter by management type (automatic / manual)
    if(!empty($this->params['named']['search.auto'])) {
      $searchterm = $this->params['named']['search.auto'];
      $pagcond['conditions']['CoGroup.auto'] = $searchterm;
    }

    // Filter by group type
    if(!empty($this->params['named']['search.group_type'])) {
      $searchterm = $this->params['named']['search.group_type'];
      $pagcond['conditions']['CoGroup.group_type'] = $searchterm;
    }

    return $pagcond;
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
    $args['conditions']['CoPerson.id'] = (!empty($this->request->params['named']['copersonid'])
                                          ? $this->request->params['named']['copersonid']
                                          // Default to the current user
                                          : $this->Session->read('Auth.User.co_person_id'));
    $args['contain'] = array('PrimaryName');
    
    $coPerson = $this->CoGroup->CoGroupMember->CoPerson->find('first', $args);

    if(!empty($coPerson)) {
      // Set name for page title
      $this->set('name_for_title', filter_var(generateCn($coPerson['PrimaryName']),FILTER_SANITIZE_SPECIAL_CHARS));
      $this->set('vv_co_person_id', $coPerson['CoPerson']['id']);
    } else {
      // Most likely CMP admin trying to view "their" groups in a CO they're not actually a member of
      $this->Flash->set(_txt('er.co.notmember'), array('key' => 'error'));
      $this->performRedirect();
    }
    
    // XXX proper authz here is probably something like "(all open CO groups
    // and all CO groups that I own) that CO Person isn't already a member of)"
    
    // XXX Don't use server side pagination
    // $params['conditions'] = array($req.'.co_id' => $this->params['named']['co']); or ['url']['coid'] for REST
    // $this->set('co_groups', $model->find('all', $params));

    // Pagination conditions must be pulled in explicitly because select() is not part of StandardController
    $pagcond = CoGroupsController::paginationConditions();
    $this->paginate['conditions'] = $pagcond['conditions'];
    
    /*$this->paginate['contain'] = array(
      'CoGroupMember' => array(
        'conditions' => array('CoGroupMember.co_person_id' => $coPerson['CoPerson']['id']),
        'CoPerson' => array('PrimaryName')
      )
    );*/
    $this->paginate['contain'] = array(
      'CoGroupMember' => array('conditions' => array('CoGroupMember.co_person_id' => $coPerson['CoPerson']['id']), 'CoPerson' => array('PrimaryName'))
    );

    $this->Paginator->settings = $this->paginate;
    $this->set('co_groups', $this->Paginator->paginate('CoGroup'));
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
    if(!$this->request->is('restful')) {
      $this->set('vv_co_group_members', $this->CoGroup->findSortedMembers($id));
    }
    
    // Invoke the StandardController view
    parent::view($id);
  }
}
