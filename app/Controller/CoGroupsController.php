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
    'EmailListModerator',
    'Identifier'
  );
  
  public $view_contains = array(
    'CoGroupNesting' => array('CoGroup'),
    'SourceCoGroupNesting' => array('TargetCoGroup'),
    'EmailListAdmin',
    'EmailListMember',
    'EmailListModerator',
    'Identifier'
  );
  
  /**
   * Callback after controller methods are invoked but before views are rendered.
   *
   * @since  COmanage Registry v0.9.4
   */

  public function beforeRender() {
    if(!$this->request->is('restful')) {
      global $cm_lang, $cm_texts;
      $this->set('vv_statuses', $cm_texts[ $cm_lang ]['en.status.susp']);
      $this->set('vv_status_open', $cm_texts[ $cm_lang ]['en.status.open']);
      $this->set('vv_status_bool', $cm_texts[ $cm_lang ]['en.status.bool']);
      $this->set('vv_group_type', $cm_texts[ $cm_lang ]['en.group.type']);
      
      $idTypes = $this->CoGroup->Identifier->types($this->cur_co['Co']['id'], 'type');

      $this->set('vv_types', array('Identifier'   => $idTypes));
      
      // Determine if there are any identifier assignments for this CO.
      
      $args = array();
      $args['conditions']['CoIdentifierAssignment.co_id'] = $this->cur_co['Co']['id'];
      $args['conditions']['CoIdentifierAssignment.context'] = IdentifierAssignmentContextEnum::CoGroup;
      $args['conditions']['CoIdentifierAssignment.status'] = SuspendableStatusEnum::Active;
      $args['contain'] = false;
      
      $this->set('co_identifier_assignments', $this->Co->CoIdentifierAssignment->find('all', $args));
    }

    parent::beforeRender();
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
    if(isset($curdata['CoGroup']['auto']) && $curdata['CoGroup']['auto']) {
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
    
    if(!$this->request->is('restful')) {

      if($this->action == 'add') {
        $cos = $this->Session->read('Auth.User.cos');
        $this->redirectTarget = array('action' => 'edit', $this->CoGroup->id);

        // Member of current CO?
        if (isset($cos[$this->cur_co['Co']['name']]['co_person_id'])) {
          $a['CoGroupMember'] = array(
            'co_group_id' => $this->CoGroup->id,
            'co_person_id' => $this->Session->read('Auth.User.co_person_id'),
            'owner' => true,
            'member' => true
          );

          if (!$this->CoGroup->CoGroupMember->save($a)) {
            $this->Flash->set(_txt('er.gr.init'), array('key' => 'information'));
            return false;
          }
        }
      }

      if($this->action == 'edit') {
        // return to the index view with default filters in place after edit
        $this->redirectTarget = array(
          'controller' => 'co_groups',
          'action' => 'index',
          'co' => filter_var($this->cur_co['Co']['id'],FILTER_SANITIZE_SPECIAL_CHARS),
          'search.auto' => 'f',
          'search.noadmin' => '1'
        );
      }

    }
    
    return true;
  }

  /**
   * List group nestings for a CO Group.
   * - precondition: Model specific attributes in $this->request->data (optional)
   * - precondition: <id> must exist
   * - postcondition: On GET, $<object>s set (HTML)
   *
   * @since  COmanage Registry v4.0.0
   * @param  integer Object identifier (eg: cm_co_groups:id) representing object to be retrieved
   */

  function nest($id) {
    // Invoke the StandardController based on permissions to edit the group
    if($this->viewVars['permissions']['edit']) {
      parent::edit($id);
    } else {
      parent::view($id);
    }
  }

  /**
   * Show the Email Lists for a group
   * - precondition: Model specific attributes in $this->request->data (optional)
   * - precondition: <id> must exist
   * - postcondition: On GET, $<object>s set (HTML)
   *
   * @since  COmanage Registry v4.0.0
   * @param  integer Object identifier (eg: cm_co_groups:id) representing object to be retrieved
   */

  function email_lists($id) {
    // Invoke the StandardController based on permissions to edit the group
    if($this->viewVars['permissions']['edit']) {
      parent::edit($id);
    } else {
      parent::view($id);
    }
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
    $actorCoPersonId = $this->request->is('restful') ? null : $this->Session->read('Auth.User.co_person_id');
    $actorApiUserId = $this->request->is('restful') ? $this->Auth->User('id') : null;

    switch($action) {
      case 'add':
        $this->CoGroup->HistoryRecord->record(null,
                                              null,
                                              null,
                                              $actorCoPersonId,
                                              ActionEnum::CoGroupAdded,
                                              _txt('rs.gr.added', array($newdata['CoGroup']['name'])),
                                              $this->CoGroup->id,
                                              null, null,
                                              $actorApiUserId);
        break;
      case 'delete':
        $this->CoGroup->HistoryRecord->record(null,
                                              null,
                                              null,
                                              $actorCoPersonId,
                                              ActionEnum::CoGroupDeleted,
                                              _txt('rs.gr.deleted', array($olddata['CoGroup']['name'])),
                                              $this->CoGroup->id,
                                              null, null,
                                              $actorApiUserId);
        break;
      case 'edit':
        $this->CoGroup->HistoryRecord->record(null,
                                              null,
                                              null,
                                              $actorCoPersonId,
                                              ActionEnum::CoGroupEdited,
                                              _txt('en.action', null, ActionEnum::CoGroupEdited) . ": " .
                                              $this->CoGroup->changesToString($newdata, $olddata, $this->cur_co['Co']['id']),
                                              $this->CoGroup->id,
                                              null, null,
                                              $actorApiUserId);
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
      $this->set('vv_model_version', $this->CoGroup->version);
      
      try {
        $groups = $this->CoGroup->findForCoPerson($this->params['url']['copersonid']);
        $this->set('co_groups', $this->Api->convertRestResponse(
          empty($groups) ? [] : $groups
        ));
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
    
    $managed = false;
    $managedp = false;
    $readonly = false;
    $self = false;
    
    if(!empty($roles['copersonid'])) {
      $curlRoles = $this->CoGroup->CoGroupMember->findCoPersonGroupRoles($roles['copersonid']);
      
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
    
    // If this is a read only record and edit was requested, try redirecting to
    // view instead. (We're probably coming back from editing an MVPA attached
    // to an automatic group, see CO-1829.)
    if($readonly && $this->action == 'edit') {
      $this->redirect(array(
        'controller'  => 'co_groups',
        'action'      => 'view',
        filter_var($this->request->params['pass'][0],FILTER_SANITIZE_SPECIAL_CHARS)
      ));
    }

    // Calculate managed for the API User
    $managed = $managed || ($roles["apiuser"] && $roles["coadmin"]);

    // Construct the permission set for this user, which will also be passed to the view.
    $p = array();
    
    // Determine what operations this user can perform
    
    // Add a new Group?
    $p['add'] = ($roles['cmadmin'] || $roles['coadmin'] || $roles['comember']);
    
    // Create an admin Group?
    $p['admin'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // Assign (autogenerate) Identifiers? (Same logic is in IdentifiersController)
    // Note we allow couadmin here beceause IdentifiersController allows it,
    // which allows it for CoPerson identifier assignment. It's not clear if that's
    // exactly the right permission here, but we probably don't want to allow
    // $managed either, so maybe this is an OK compromise.
    $p['assign'] = ($roles['cmadmin']
                    || $roles['coadmin'] || ($managed && $roles['couadmin']));
    
    // Delete an existing Group?
    $p['delete'] = (!$readonly && ($roles['cmadmin'] || $managed));
    
    // Edit an existing Group?
    $p['edit'] = (!$readonly && ($roles['cmadmin'] || $managed));
    
    // View history for an existing Group?
    $p['history'] = ($roles['cmadmin'] || $roles['coadmin'] || $managed);
    
    // View all existing Groups?
    $p['index'] = ($roles['cmadmin'] || $roles['coadmin'] || $roles['comember']);
    $p['search'] = $p['index'];
    
    // Nest a Group within another Group? Delete a nested group?
    // These align with CoGroupNestingsController::isAuthorized
    $p['buildnest'] = !$readonly && ($roles['cmadmin'] || $roles['coadmin'] || $roles['couadmin']);
    $p['deletenest'] = !$readonly && ($roles['cmadmin'] || $roles['coadmin'] || $roles['couadmin']);

    // Edit or View Identifiers?
    // This aligns with IdentifiersController::isAuthorized
    $p['editids'] = ($roles['cmadmin'] || $roles['coadmin']);
    $p['viewids'] = ($roles['cmadmin'] || $roles['coadmin'] || $roles['couadmin']);
    
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
    
    $p['member'] = !empty($curlRoles['member']) ? $curlRoles['member'] : array();
    $p['owner'] = !empty($curlRoles['owner']) ? $curlRoles['owner'] : array();
    
    // Determine if the current user is a member of the group.
    // $managed is already defined for owner.
    $member = false;
    if(!empty($this->request->params['pass'][0])) {
      $member = in_array($this->request->params['pass'][0], $p['member']);  
    }
    
    // View provisioning? This permission grants access to the Provisioned Services tab
    $p['provision'] = ($roles['cmadmin'] || $roles['coadmin'] || $roles['couadmin']);
    
    // (Re)provision an exsiting CO Group? This pemrmission grants access to the "Provision" buttons.
    $p['do_provisioning'] = ($roles['cmadmin'] || $roles['coadmin']);

    // Select from a list of potential Groups to join?
    $p['select'] = ($roles['cmadmin'] || $roles['coadmin']
                    || ($managedp && $roles['couadmin'])
                    || $self);
    
    // Select from any Group (not just open or owned)?
    $p['selectany'] = ($roles['cmadmin'] || $roles['coadmin']
                       || ($managedp && $roles['couadmin']));
    
    // View an existing Group?
    $p['view'] = ($roles['cmadmin'] || $roles['coadmin'] || $managed);
    
    // Viewing members, email lists, and nested groups is available to all group members.
    // Access to specific actions is controlled in the view.
    $p['members'] = $roles['cmadmin'] || $roles['coadmin'] || $managed || $member;
    $p['email_lists'] = $roles['cmadmin'] || $roles['coadmin'] || $managed || $member;
    $p['nest'] =  $roles['cmadmin'] || $roles['coadmin'] || $managed || $member;

    // Edit Group members
    $p['edit_members'] = !$readonly && ($roles['cmadmin'] || $roles['coadmin'] || $managed);

    // Edit email lists?
    // This aligns with CoEmailLists::isAuthorized
    $p['edit_email_lists'] = ($roles['cmadmin'] || $roles['coadmin']);

    if($this->action == 'view'
       && isset($this->request->params['pass'][0])) {
      // Adjust permissions for members and open groups
      
      if(!empty($p['member']) && in_array($this->request->params['pass'][0], $p['member']))
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

    if($this->request->is('restful')) {
      if($this->request->method() == "GET"
         && isset($this->request->query["copersonid"])) {
        return $this->CoGroup->Co->CoPerson->field('co_id',
                                                    array('id' => $this->request->query["copersonid"]));
      }
    }

    return parent::parseCOID($data);
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
      if($this->viewVars['permissions']['edit']) {
        $titleText = 'op.edit-a';
      } else {
        $titleText = 'op.view-a';
      }
      $this->set('title_for_layout',
        _txt($titleText,
          array(filter_var($this->viewVars['co_group']['CoGroup']['name'],FILTER_SANITIZE_SPECIAL_CHARS))));
      $this->set('vv_subtitle',
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

    // build a URL will all the search elements in it
    // the resulting URL will be similar to example.com/registry/co_groups/index/co:2/search.status:S
    foreach($this->data['search'] as $field=>$value){
      if(!empty($value)) {
        $url['search.'.$field] = $value;
      }
    }

    $url['co'] = $this->cur_co['Co']['id'];

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
      if($searchterm=='f') {
        $pagcond['conditions']['CoGroup.auto'] = false;
      } else {
        $pagcond['conditions']['CoGroup.auto'] = true;
      }
    }

    // Filter by group type
    if(!empty($this->params['named']['search.group_type'])) {
      $searchterm = $this->params['named']['search.group_type'];
      $pagcond['conditions']['CoGroup.group_type'] = $searchterm;
    }

    // Exclude admin groups
    if(!empty($this->params['named']['search.noadmin'])) {
      $pagcond['conditions']['CoGroup.group_type <>'] = GroupEnum::Admins;
    }

    // Filter by membership and ownership
    // First get the CoPersonID to work against either the current user, or the user being acted on in select mode
    $coPersonId = $this->Session->read('Auth.User.co_person_id');
    if($this->action == 'select'
       && !empty($this->request->params['named']['copersonid'])) {
      $coPersonId = $this->request->params['named']['copersonid'];
    }

    // If both member and owner are selected, use a single join with "OR"
    if(!empty($this->params['named']['search.member']) && !empty($this->params['named']['search.owner'])) {
      $pagcond['joins'][] = array(
        'table' => 'co_group_members',
        'alias' => 'CoGroupMember',
        'type' => 'INNER',
        'conditions' => array(
          "CoGroupMember.co_person_id=" . $coPersonId,
          "CoGroupMember.co_group_id=CoGroup.id",
          'OR' => array(
            "CoGroupMember.member = true",
            "CoGroupMember.owner = true"
          )
        )
      );
    } else {
      // Otherwise filter for member and owner individually
      // Filter by member
      if (!empty($this->params['named']['search.member'])) {
        $pagcond['joins'][] = array(
          'table' => 'co_group_members',
          'alias' => 'CoGroupMember',
          'type' => 'INNER',
          'conditions' => array(
            "CoGroupMember.co_person_id=" . $coPersonId,
            "CoGroupMember.co_group_id=CoGroup.id",
            "CoGroupMember.member = true"
          )
        );
      }

      // Filter by owner
      if (!empty($this->params['named']['search.owner'])) {
        $pagcond['joins'][] = array(
          'table' => 'co_group_members',
          'alias' => 'CoGroupMember',
          'type' => 'INNER',
          'conditions' => array(
            "CoGroupMember.co_person_id=" . $coPersonId,
            "CoGroupMember.co_group_id=CoGroup.id",
            "CoGroupMember.owner = true"
          )
        );
      }
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
    $this->paginate['conditions'] = !empty($pagcond['conditions']) ? $pagcond['conditions'] : array();
    $this->paginate['joins'] = !empty($pagcond['joins']) ? $pagcond['joins'] : array();

    $this->paginate['contain'] = array(
      'CoGroupMember' => array(
        'conditions' => array('CoGroupMember.co_person_id' => $coPerson['CoPerson']['id']),
        'CoPerson' => array('PrimaryName'))
    );

    $this->Paginator->settings = $this->paginate;
    $this->set('co_groups', $this->Paginator->paginate('CoGroup'));
  }
}
