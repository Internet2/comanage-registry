<?php
/**
 * COmanage Registry CO Person Roles Controller
 *
 * Copyright (C) 2010-16 University Corporation for Advanced Internet Development, Inc.
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
 * @copyright     Copyright (C) 2010-16 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.2
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */

App::uses("StandardController", "Controller");

class CoPersonRolesController extends StandardController {
  public $name = "CoPersonRoles";
  
  public $helpers = array('Time', 'Permission');
  
  public $uses = array('CoPersonRole', 'AttributeEnumeration');

  public $paginate = array(
    'limit' => 25,
    'order' => array(
      'PrimaryName.family' => 'asc',
      'PrimaryName.given' => 'asc'
    )
  );
  
  // This controller needs a CO to be set
  public $requires_co = true;
  
  // This controller allows a COU to be set
  public $allows_cou = true;

  public $edit_contains = array(
    'Address' => array('SourceAddress' => array('OrgIdentity' => array('OrgIdentitySourceRecord' => array('OrgIdentitySource')))),
    'CoPerson', // Used to check status recalculation on save
    'SponsorCoPerson' => array('PrimaryName'),
    'TelephoneNumber' => array('SourceTelephoneNumber' => array('OrgIdentity' => array('OrgIdentitySourceRecord' => array('OrgIdentitySource'))))
  );
  
  // We need various related models for index and search
  public $view_contains = array(
    'Address' => array('SourceAddress' => array('OrgIdentity' => array('OrgIdentitySourceRecord' => array('OrgIdentitySource')))),
    'Cou',
    'SponsorCoPerson' => array('PrimaryName'),
    'TelephoneNumber' => array('SourceTelephoneNumber' => array('OrgIdentity' => array('OrgIdentitySourceRecord' => array('OrgIdentitySource'))))
  );
  
  // The extended attributes for this CO
  public $extended_attributes = array();
  
  /**
   * Add a CO Person Role Object.
   * - precondition: Model specific attributes in $this->request->data (optional)
   * - postcondition: On success, new Object created
   * - postcondition: Session flash message updated (HTML) or HTTP status returned (REST)
   * - postcondition: $<object>_id or $invalid_fields set (REST)
   *
   * @since  COmanage Registry v0.2
   */
  
  function add() {
    if(!$this->request->is('restful') && $this->request->is('get')) {
      // Create a stub person role. It's unclear that title should
      // autopopulate, and if it need not it's further unclear that we
      // really need to set this variable.
      
      $cop = $this->viewVars['co_people'];
      $copr['CoPersonRole']['title'] = $cop[0]['CoOrgIdentityLink'][0]['OrgIdentity']['title'];
      $copr['CoPersonRole']['co_person_id'] = $cop[0]['CoPerson']['id'];
      
      $this->set('co_person_roles', array(0 => $copr));
    }
    
    parent::add();
    
    if(!$this->request->is('restful')) {
      // Append the person's name to the page title
      $this->set('title_for_layout',
                 $this->viewVars['title_for_layout'] . " (" . generateCn($this->viewVars['co_people'][0]['PrimaryName']) . ")");
    }
  }

  /**
   * Callback before other controller methods are invoked or views are rendered.
   * - postcondition: $pool_org_identities set
   *
   * @since  COmanage Registry v0.2
   */
  
  function beforeFilter() {
    parent::beforeFilter();
    
    if(!$this->request->is('restful')) {
      // We need CO Person information for the view as well. We also want Name,
      // so we increase recursion.
      
      $copid = -1;
      
      // Might be passed in the URL (as per add)
      if(!empty($this->request->params['named']['copersonid']))
        $copid = $this->request->params['named']['copersonid'];
      // Might be determined from the CO Person Role (as per edit/view)
      elseif(!empty($this->request->data['CoPersonRole']['co_person_id']))
        $copid = $this->request->data['CoPersonRole']['co_person_id'];
      // Might need to look it up from the person role
      elseif(!empty($this->request->params['pass'][0]))
        $copid = $this->CoPersonRole->field('co_person_id', array('id' => $this->request->params['pass'][0]));
      
      $args = array();
      $args['conditions']['CoPerson.id'] = $copid;
      $args['contain'] = array('CoOrgIdentityLink' => array('OrgIdentity'),
                               'PrimaryName');
      
      $cop = $this->CoPersonRole->CoPerson->find('all', $args);
      
      if($cop) {
        $this->set('co_people', $cop);
      } else {
        $this->Flash->set(_txt('er.cop.unk-a', array($copid)), array('key' => 'error'));
        $this->redirect(array('controller' => 'co_people', 'action' => 'index', 'co' => $this->cur_co['Co']['id']));
      }
    }
    
    // If there are any extended attributes defined for this CO,
    // dynamically bind the CO table of attributes to the model.
    
    if($this->request->is('restful') && !isset($this->cur_co)) {
      // Calls to co_person_roles via the REST controller won't have a CO set (except
      // when retrieving all members of a CO) so we have to figure out the CO
      // from the person requested.
      
      if(isset($this->request->params['id'])) {
        // Request for an individual
        
        $args['joins'][0]['table'] = 'co_people';
        $args['joins'][0]['alias'] = 'CoPerson';
        $args['joins'][0]['type'] = 'INNER';
        $args['joins'][0]['conditions'][0] = 'Co.id=CoPerson.co_id';
        $args['joins'][1]['table'] = 'co_person_roles';
        $args['joins'][1]['alias'] = 'CoPersonRole';
        $args['joins'][1]['type'] = 'INNER';
        $args['joins'][1]['conditions'][0] = 'CoPerson.id=CoPersonRole.co_person_id';
        $args['conditions']['CoPersonRole.id'] = $this->request->params['id'];
        $args['contain'] = false;
        
        $this->cur_co = $this->CoPersonRole->CoPerson->Co->find('first', $args);
      } elseif(isset($this->request->params['url']['coid'])) {
        // Request for all members of a CO
        
        $args = array();
        $args['conditions']['Co.id'] = $this->request->params['url']['coid'];
        $args['contain'] = false;
        
        $this->cur_co = $this->Co->find('first', $args);
      }
      // We don't currently support requests for all CO people (regardless of CO).
      // To do so, we'd have to extract the CO ID on a per-CO person basis, which
      // wouldn't be terribly efficient.
    }
    
    $this->extended_attributes = $this->CoPersonRole->CoPerson->Co->CoExtendedAttribute->find('all',
                                                                                              array('conditions' =>
                                                                                                    array('co_id' => $this->cur_co['Co']['id'])));
    
    if(!empty($this->extended_attributes)) {
      $cl = 'Co' . $this->cur_co['Co']['id'] . 'PersonExtendedAttribute';
      
      // With emulated changelog behavior, we want a hasOne relationship even though
      // the physical tables can have a many-to-one relationship. We therefore order by
      // created ASC to get the original record (changelog and emulated changelog
      // maintain the original foreign key as the active record), which has the current
      // values.
      $this->CoPersonRole->bindModel(array('hasOne' =>
                                           array($cl => array('className' => $cl,
                                                              'dependent' => true,
                                                              'order' => $cl.'.created ASC'))),
                                     false);
      
      // Set up the inverse binding
      $this->CoPersonRole->$cl->bindModel(array('belongsTo' => array('CoPersonRole')),
                                          false);
      
      // Dynamic models won't have behaviors attached, so add them here
      $this->CoPersonRole->$cl->Behaviors->attach('Normalization');
      
      // Changelog is bound in checkWriteFollowups -- see there for explanation
      
      // Make sure extended attributes show up as part of containable queries
      $this->edit_contains[] = $cl;
      $this->view_contains[] = $cl;
    }
    
    // Dynamically adjust validation rules to include the current CO ID for dynamic types.
    
    $vrule = $this->CoPersonRole->validate['affiliation']['content']['rule'];
    $vrule[1]['coid'] = $this->cur_co['Co']['id'];
    
    $this->CoPersonRole->validator()->getField('affiliation')->getRule('content')->rule = $vrule;
    
    // Pull attribute enumerations and adjust validation rules, if needed
    
    $coId = $this->cur_co['Co']['id'];
    
    $enums_o = $this->AttributeEnumeration->active($coId, "CoPersonRole.o");
    $this->set('vv_enums_o', $enums_o);
    
    $enums_ou = $this->AttributeEnumeration->active($coId, "CoPersonRole.ou");
    $this->set('vv_enums_ou', $enums_ou);
    
    $enums_title = $this->AttributeEnumeration->active($coId, "CoPersonRole.title");
    $this->set('vv_enums_title', $enums_title);
    
    if(!empty($this->viewVars['vv_tz'])) {
      // Set the current timezone, primarily for beforeSave
      $this->CoPersonRole->setTimeZone($this->viewVars['vv_tz']);
    }
  }

  /**
   * Callback after controller methods are invoked but before views are rendered.
   * - precondition: Request Handler component has set $this->request
   * - postcondition: Set $sponsors
   *
   * @since  COmanage Registry v0.9.2
   */

  public function beforeRender() {
    parent::beforeRender();
    
    if(!$this->request->is('restful')){
      // Mappings for extended types
      $this->set('vv_copr_address_types', $this->CoPersonRole->Address->types($this->cur_co['Co']['id'], 'type'));
      $this->set('vv_copr_affiliation_types', $this->CoPersonRole->types($this->cur_co['Co']['id'], 'affiliation'));
      $this->set('vv_copr_telephonenumber_types', $this->CoPersonRole->TelephoneNumber->types($this->cur_co['Co']['id'], 'type'));
      
      // Generate list of sponsors
      $this->set('vv_sponsors', $this->CoPersonRole->CoPerson->sponsorList($this->cur_co['Co']['id']));
      
      // Extended attributes
      $this->set('vv_extended_attributes', $this->extended_attributes);
    }
  }
  
  /**
   * Perform any dependency checks required prior to a write (add/edit) operation.
   * This method is intended to be overridden by model-specific controllers.
   * - postcondition: Session flash message updated (HTML) or HTTP status returned (REST)
   *
   * @since  COmanage Registry v0.2
   * @param  Array Request data
   * @param  Array Current data
   * @return boolean true if dependency checks succeed, false otherwise.
   */
  
  function checkWriteDependencies($reqdata, $curdata = null) {
    if($this->request->is('restful') && !empty($this->viewVars['permissions']['cous'])) {
      // Check that the COU ID provided points to an existing COU.
      
      if(empty($reqdata['CoPersonRole']['cou_id'])) {
        $this->Api->restResultHeader(403, "COU Does Not Exist");
        return false;
      }      
      
      $a = $this->CoPersonRole->Cou->findById($reqdata['CoPersonRole']['cou_id']);
      
      if(empty($a)) {
        $this->Api->restResultHeader(403, "COU Does Not Exist");
        return false;
      }
    }
    
    // Extended attributes are saved separately in checkWriteFollowups, so start
    // a transaction.
    
    $dbc = $this->CoPersonRole->getDataSource();
    $dbc->begin();
    
    return true;
  }
  
  /**
   * Perform any followups following a write operation.  Note that if this
   * method fails, it must return a warning or REST response, but that the
   * overall transaction is still considered a success (add/edit is not
   * rolled back).
   * This method is intended to be overridden by model-specific controllers.
   *
   * @since  COmanage Registry v0.9.2
   * @param  Array Request data
   * @param  Array Current data
   * @param  Array Original request data (unmodified by callbacks)
   * @return boolean true if dependency checks succeed, false otherwise.
   */
  
  function checkWriteFollowups($reqdata, $curdata = null, $origdata = null) {
    // This is basically a hack to normalize extended attributes, because Cake 2.x
    // won't see changes to associated data made by behaviors. (This is fixed in
    // Cake 3.) We can't do this in the model because of the order in which
    // saveAssociated() processes the associated models. (We're still in the save
    // for CoPersonRole when CoPersonRole::afterSave is called, so by the time
    // the save is called on the ExtendedAttributes, saveAssociated is done with
    // CoPersonRole)
    // https://github.com/cakephp/cakephp/issues/1765
    
    // We need to do a similar hack for handling Extended Attributes in the
    // REST API, but that's located in StandardController::add/edit.
    
    $eaModel = 'Co' . $this->cur_co['Co']['id'] . 'PersonExtendedAttribute';
    
    // $origdata should have the originally requested Extended Attributes.
    // $reqdata will hold their current (persisted) values, since StandardController
    // repopulated $reqdata post-save to account for normalizations, but we haven't
    // yet saved the extended attributes yet.
    
    if(!empty($origdata[$eaModel])) {
      // Create a temporary copy of the data to save
      $d = array(
        $eaModel => $origdata[$eaModel]
      );
      
      // Clear out metadata, if set (edit)
      unset($d[$eaModel]['created']);
      unset($d[$eaModel]['modified']);
      
      if(!empty($reqdata['CoPersonRole']['id'])) {
        // Point the record to the current CO Person Role
        $d[$eaModel]['co_person_role_id'] = $reqdata['CoPersonRole']['id'];
      }
      
      if($this->CoPersonRole->Behaviors->enabled('Changelog')
         && !empty($reqdata[$eaModel]['co_person_role_id'])) {
        // Because extended attributes don't carry the changelog metadata (parent
        // foreign key becomes a mess, among other reasons) we manually emulate the
        // desired outcome. We do it here rather than in a model because there's not
        // a clearly better place to do it.
        
        // WAS: PR-1 <=> EA-1
        // HAVE: PR-1 <=> EA-1 , PR-2
        // WANT: PR-1 <=> EA-2, PR-2 <=> EA-1
        
        // We check for co_person_role_id above to see if this is an edit operation.
        // On add we don't need to do anything special.
        
        // First, find the archived CoPersonRole. We want to copy the current EA values
        // (helpfully available in $reqdata) to a new row and link it to that CoPersonRole.
        // We should also have the current set of values in $reqdata, including the
        // extended attribute row ID we need to update.
        
        $args = array();
        $args['conditions']['CoPersonRole.id'] = $reqdata[$eaModel]['co_person_role_id'];
        $args['changelog']['revision'] = $reqdata['CoPersonRole']['revision'] - 1;
        $args['contain'] = false;
        
        $priorRole = $this->CoPersonRole->find('first', $args);
        
        if(!empty($priorRole['CoPersonRole']['id'])) {
          // Create archive of current (previous) extended attribute values
          
          $eaCopy = array();
          $eaCopy[$eaModel] = $reqdata[$eaModel];
          $eaCopy[$eaModel]['co_person_role_id'] = $priorRole['CoPersonRole']['id'];
          unset($eaCopy[$eaModel]['id']);
          unset($eaCopy[$eaModel]['created']);
          unset($eaCopy[$eaModel]['modified']);
          
          // Save the archive
          $this->CoPersonRole->$eaModel->save($eaCopy);
        }
        
        // Now we can just go ahead with the save of the operational record
      }
      
      $this->CoPersonRole->$eaModel->save($d);
      
      // Manually trigger history. The StandardController call would still have the
      // original attributes.
      $this->generateHistory('x'.$this->action, array_merge($reqdata, $d), $curdata);
    }
    
    // If the role status changed, check to see if the overall person status changed.
    // CoPersonRole::afterSave will do the actual recalculation, but we still want to
    // set a flash message.

    if(!empty($reqdata['CoPersonRole']['status'])
       && !empty($curdata['CoPersonRole']['status'])
       && $reqdata['CoPersonRole']['status'] != $curdata['CoPersonRole']['status']
       && !empty($reqdata['CoPerson']['status'])
       && !empty($curdata['CoPerson']['status'])
       && $reqdata['CoPerson']['status'] != $curdata['CoPerson']['status']) {
      // It seems like we would want to render $curdata, but confusingly StandardController rereads
      // the current record and passes it via $reqdata after the save.
      $this->Flash->set(_txt('rs.cop.recalc',
                             array(_txt('en.status', null, $reqdata['CoPerson']['status']))),
                        array('key' => 'information'));
    }
    
    if(isset($reqdata['CoPersonRole']['status'])
       && isset($origdata['CoPersonRole']['status'])
       && $reqdata['CoPersonRole']['status'] != $origdata['CoPersonRole']['status']) {
      $this->Flash->set(_txt('rs.copr.mod',
                             array(_txt('en.status', null, $origdata['CoPersonRole']['status']),
                                   _txt('en.status', null, $reqdata['CoPersonRole']['status']))),
                        array('key' => 'information'));
    }
    
    // Commit under pretty much all circumstances. If there was an error in the
    // meantime we won't be able to explicitly rollback, but the termination of the
    // request should cause that to happen.
    $dbc = $this->CoPersonRole->getDataSource();
    $dbc->commit();
    
    return true;
  }

  /**
   * Generate a display key to be used in messages such as "Item Added".
   *
   * @since  COmanage Registry v0.2
   * @param  Array A cached object (eg: from prior to a delete)
   * @return string A string to be included for display.
   */
  
  function generateDisplayKey($c = null) {
    // Get a pointer to our model
    $req = $this->modelClass;
    $model = $this->$req;
    
    if(isset($c[$req][$model->displayField]))
      return($c[$req][$model->displayField]);
    if(isset($this->request->data['PrimaryName']))
      return(generateCn($this->request->data['PrimaryName']));
    if(isset($this->viewVars['co_people'][0]['PrimaryName']))
      return(generateCn($this->viewVars['co_people'][0]['PrimaryName']));
    else
      return("(?)");
  }

  /**
   * Generate history records for a transaction. This method is intended to be
   * overridden by model-specific controllers, and will be called from within a
   * try{} block so that HistoryRecord->record() may be called without worrying
   * about catching exceptions.
   *
   * @since  COmanage Registry v0.7
   * @param  String Controller action causing the change
   * @param  Array Data provided as part of the action (for add/edit)
   * @param  Array Previous data (for delete/edit)
   * @return boolean Whether the function completed successfully (which does not necessarily imply history was recorded)
   */
  
  public function generateHistory($action, $newdata, $olddata) {
    switch($action) {
      // Because of the way extended attributes are saved in checkWriteFollowups,
      // we ignore generateHistory as requested by StandardController and instead wait
      // for calls from checkWriteFollowups.
      case 'add':
      case 'edit':
        return true;
        break;
      case 'xadd':
        $this->CoPersonRole->HistoryRecord->record($newdata['CoPersonRole']['co_person_id'],
                                                   $this->CoPersonRole->id,
                                                   null,
                                                   $this->Session->read('Auth.User.co_person_id'),
                                                   ActionEnum::CoPersonRoleAddedManual);
        break;
      case 'delete':
        $this->CoPersonRole->HistoryRecord->record($olddata['CoPersonRole']['co_person_id'],
                                                   $this->CoPersonRole->id,
                                                   null,
                                                   $this->Session->read('Auth.User.co_person_id'),
                                                   ActionEnum::CoPersonRoleDeletedManual);
        break;
      case 'xedit':
        $this->CoPersonRole->HistoryRecord->record($newdata['CoPersonRole']['co_person_id'],
                                                   $this->CoPersonRole->id,
                                                   null,
                                                   $this->Session->read('Auth.User.co_person_id'),
                                                   ActionEnum::CoPersonRoleEditedManual,
                                                   _txt('en.action', null, ActionEnum::CoPersonRoleEditedManual)
                                                   . " (" . $this->CoPersonRole->id . "):"
                                                   . $this->CoPersonRole->changesToString($newdata,
                                                                                          $olddata,
                                                                                          $this->cur_co['Co']['id'],
                                                                                          array('ExtendedAttribute'),
                                                                                          $this->extended_attributes));
        break;
    }
    
    return true;
  }
  
  /**
   * Authorization for this Controller, called by Auth component
   * - precondition: Session.Auth holds data used for authz decisions
   * - postcondition: $permissions set with calculated permissions
   *
   * @since  COmanage Registry v0.2
   * @return Array Permissions
   */
  
  function isAuthorized() {
    $roles = $this->Role->calculateCMRoles();
    
    // Is this our own record?
    $self = false;
    
    if($roles['comember'] && $roles['copersonid'] && isset($this->request->params['pass'][0])) {
      // We need to see if the person role ID passed in maps to the authenticated CO person
      
      $copid = $this->CoPersonRole->field('co_person_id', array('id' => $this->request->params['pass'][0]));
      
      if($copid && ($copid == $roles['copersonid']))
        $self = true;
    }
    
    // Is this a record we can manage?
    $managed = false;
    
    if(!empty($roles['copersonid'])
       && !empty($this->request->params['pass'][0])
       && ($this->action == 'delete'
           || $this->action == 'edit'
           || $this->action == 'view')) {
      $managed = $this->Role->isCoOrCouAdminForCoPersonRole($roles['copersonid'],
                                                            $this->request->params['pass'][0]);
    }
    
    // Construct the permission set for this user, which will also be passed to the view.
    $p = array();
    
    // Add a new CO Person Role?
    $p['add'] = ($roles['cmadmin'] || $roles['coadmin'] || $roles['couadmin']);
    
    // Determine which COUs a person can manage.
    if($roles['cmadmin'] || $roles['coadmin']) {
      // Note that here we get id => name while in CoPeopleController we just
      // get a list of names. This is to generate the pop-up on the edit form.
      $p['cous'] = $this->CoPersonRole->Cou->allCous($this->cur_co['Co']['id']);
    } elseif(!empty($roles['admincous'])) {
      $p['cous'] = $roles['admincous'];
    } else {
      $p['cous'] = array();
    }
    
    // Delete an existing CO Person Role?
    $p['delete'] = ($roles['cmadmin']
                    || ($managed && ($roles['coadmin'] || $roles['couadmin'])));
    
    // Edit an existing CO Person Role?
    $p['edit'] = ($roles['cmadmin']
                  || ($managed && ($roles['coadmin'] || $roles['couadmin']))
                  || $self);

    // Are we trying to edit our own record? 
    // If we're an admin, we act as an admin, not self.
    $p['editself'] = $self && !$roles['cmadmin'] && !$roles['coadmin'] && !$roles['couadmin'];
    
    // View all existing CO Person Roles (or a COU's worth)? (for REST API)
    $p['index'] = ($this->request->is('restful') && ($roles['cmadmin'] || $roles['coadmin']));
    
    if($this->action == 'index' && $p['index']) {
      // For rendering index, we currently assume that anyone who can view the
      // index can manipulate all records. This is fine for CMP and CO admins,
      // but a COU Admin can't edit role data for which they are not the admin.
      // (See also CO-505.)
      
      // It might be nice to pull all the people in the COU and pass a list
      // of CO Role IDs, but that would require pulling all the person role
      // records twice (again later in StandardController::index()). Since
      // $p['admincous'] has the appropriate COUs listed, will let the view
      // do a bit of work when rendering.
      
      // These permissions are person-level, and are probably not exactly right.
      // Specifically, delete could be problematic since a COU admin can't
      // delete a person with a COU role that the admin doesn't manage.
      // For now, we'll catch that in checkDeleteDependencies, and let the view
      // worry about what to render by checking the list of COUs.
      
      $p['delete'] = true;
      $p['edit'] = true;
      $p['view'] = true;
    }
    
    // Relink a Role to a different CO Person?
    $p['relink'] = $roles['cmadmin'] || $roles['coadmin'];
    
    if($self
       && (!$roles['cmadmin'] && !$roles['coadmin'] && !$roles['couadmin'])) {
      // Pull self service permissions if not an admin
      
      $p['selfsvc'] = $this->Co->CoSelfServicePermission->findPermissions($this->cur_co['Co']['id']);
    } else {
      $p['selfsvc'] = false;
    }
    
    // View an existing CO Person Role?
    $p['view'] = ($roles['cmadmin']
                  || ($roles['coadmin'] || $roles['couadmin'])
                  || $self);
    
    $this->set('permissions', $p);
    return $p[$this->action];
  }
  
  /**
   * Perform a redirect back to the controller's default view.
   * - postcondition: Redirect generated
   *
   * @since  COmanage Registry v0.2
   */
  
  function performRedirect() {
    // On add, redirect to edit view again so MVPAs are available.
    // For everything else, return to co_people
   
    if($this->action == 'add') {
      $params = array('action' => 'edit',
                      $this->CoPersonRole->id,
                      'co'     => $this->cur_co['Co']['id'],
                      'tab'    => 'role'
                     );
      $this->redirect($params);
    } else {
      $params = array('controller' => 'co_people',
                      'action'     => 'canvas',
                      $this->viewVars['co_people'][0]['CoPerson']['id']
                     );
      $this->redirect($params);
    }
  }
  
  /**
   * Move a CO Person Role from one CO Person to another.
   *
   * @param Integer $id CO Person Role ID to move
   * @since  COmanage Registry v0.9.1
   */

  public function relink($id) {
    if(!$this->request->is('restful')) {
      // The selection process is handled by CoPeopleController. We execute here.
      // We're only passed the field to update, not a full CO Person Role record,
      // so just execute a field update.
      
      if(!empty($this->request->data['CoPersonRole']['co_person_id'])) {
        // Pull the current Role and CO Person
        
        $args = array();
        $args['conditions']['CoPersonRole.id'] = $id;
        $args['contain']['CoPerson'] = 'PrimaryName';
        
        $copr = $this->CoPersonRole->find('first', $args);
        
        if(!empty($copr)) {
          $this->CoPersonRole->id = $copr['CoPersonRole']['id'];
          
          if($this->CoPersonRole->saveField('co_person_id', $this->request->data['CoPersonRole']['co_person_id'])) {
            // Assemble a result string that we'll use in a few places. Pull the Primary Name
            // for the new CO Person.
            
            $args = array();
            $args['conditions']['CoPerson.id'] = filter_var($this->request->data['CoPersonRole']['co_person_id'],FILTER_SANITIZE_SPECIAL_CHARS);
            $args['contain'][] = 'PrimaryName';
            
            $newcop = $this->CoPersonRole->CoPerson->find('first', $args);
            
            if($newcop) {
              $res = _txt('rs.moved.copr', array($copr['CoPersonRole']['title'],
                                                 filter_var($id,FILTER_SANITIZE_SPECIAL_CHARS),
                                                 generateCn($copr['CoPerson']['PrimaryName']),
                                                 $copr['CoPersonRole']['co_person_id'],
                                                 generateCn($newcop['PrimaryName']),
                                                 $newcop['CoPerson']['id']));
              
              $this->Flash->set($res, array('key' => 'success'));
              
              // Update history, once for old and once for new
              
              try {
                // Original
                $this->CoPersonRole->HistoryRecord->record($copr['CoPersonRole']['co_person_id'],
                                                           $copr['CoPersonRole']['id'],
                                                           null,
                                                           $this->Session->read('Auth.User.co_person_id'),
                                                           ActionEnum::CoPersonRoleRelinked,
                                                           $res);
                
                // New
                $this->CoPersonRole->HistoryRecord->record($newcop['CoPerson']['id'],
                                                           $copr['CoPersonRole']['id'],
                                                           null,
                                                           $this->Session->read('Auth.User.co_person_id'),
                                                           ActionEnum::CoPersonRoleRelinked,
                                                           $res);
              }
              catch(Exception $e) {
                $this->Flash->set($e->getMessage(), array('key' => 'error'));
              }
            } else {
              $this->Flash->set(_txt('er.notfound',
                                     array(_txt('ct.co_people.1'), filter_var($this->request->data['CoPersonRole']['co_person_id'],FILTER_SANITIZE_SPECIAL_CHARS))),
                                array('key' => 'error'));
            }
          } else {
            $this->Flash->set(_txt('er.db.save'), array('key' => 'error'));
          }
        } else {
          $this->Flash->set(_txt('er.cop.nf', array(filter_var($id,FILTER_SANITIZE_SPECIAL_CHARS))), array('key' => 'error'));
        }
      }
      
      $this->performRedirect();
    }
  }
}
