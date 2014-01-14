<?php
/**
 * COmanage History Record Controller
 *
 * Copyright (C) 2012-3 University Corporation for Advanced Internet Development, Inc.
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
 * @copyright     Copyright (C) 2012-3 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.7
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */

App::uses("StandardController", "Controller");

// We extend StandardController primarily to get the REST stuff.

class HistoryRecordsController extends StandardController {
  // Class name, used by Cake
  public $name = "HistoryRecords";
  
  // When using additional models, we must also specify our own
  public $uses = array('HistoryRecord', 'CmpEnrollmentConfiguration');
  
  public $helpers = array('Time');
  
  // Establish pagination parameters for HTML views
  public $paginate = array(
    'limit' => 25,
    'order' => array(
      'HistoryRecord.id' => 'desc'
    ),
    'contain' => array(
      'ActorCoPerson.PrimaryName',
      'CoPerson.PrimaryName',
      'CoPersonRole',
      'OrgIdentity.PrimaryName'
    )
  );
  
  public $requires_person = true;
  
  // We really want to use contains to get Names attached to the various person/org identities,
  // but StandardController doesn't support that yet.
  public $view_recursion = 2;
  
  function index() {
    if(!$this->restful) {
      // We need to change enough of the standard behavior that it's easier just to reimplement.
      
      // Set page title
      $this->set('title_for_layout', _txt('ct.history_records.pl'));
      
      // We need an Org ID or a CO Person ID to retrieve on. We have to carefully craft our queries
      // in order to pull only records that the current user is authorized to see.
      
      // Use server side pagination
      
      if(!empty($this->params['named']['copersonid'])) {
        // CO Administrators can see all records, however COU Administrators can only see records
        // with no CO Person Role ID or where the CO Person Role ID is in a COU they administer.
        
        $args = array();
        $args['HistoryRecord.co_person_id'] = $this->params['named']['copersonid'];
        
        if(!empty($this->viewVars['permissions']['cous'])) {
          // Pull records in the COUs this user can see, as well as those with no COU attached.
          // Note a join isn't needed here because paginate+contain is already joining the right tables.
          
          $args['OR']['CoPersonRole.cou_id'] = array_keys($this->viewVars['permissions']['cous']);
          $args['OR'][] = 'CoPersonRole.cou_id IS NULL';
          $args['OR'][] = 'HistoryRecord.co_person_role_id IS NULL';
        } else {
          // This should catch the case where COUs aren't in use
          $args[] = 'HistoryRecord.co_person_role_id IS NULL';
        }
        
        $this->Paginator->settings = $this->paginate;
        $this->set('history_records', $this->Paginator->paginate('HistoryRecord', $args));
      } elseif(!empty($this->params['named']['orgidentityid'])) {
        // Org ID is a bit tricky when org identities are pooled, because we shouldn't pull
        // history for that Org ID related to COs other than the current one.
        // Note a join isn't needed here because paginate+contain is already joining the right tables.
        
        $pool = $this->CmpEnrollmentConfiguration->orgIdentitiesPooled();
        
        $args = array();
        $args['HistoryRecord.org_identity_id'] = $this->params['named']['orgidentityid'];
        
        if($pool) {
          $args['CoPerson.co_id'] = $this->cur_co['Co']['id'];
        }
        
        if(!empty($this->viewVars['permissions']['cous'])) {
          // Pull records in the COUs this user can see, as well as those with no COU attached.
          // Note a join isn't needed here because paginate+contain is already joining the right tables.
          
          $args['OR']['CoPersonRole.cou_id'] = array_keys($this->viewVars['permissions']['cous']);
          $args['OR'][] = 'CoPersonRole.cou_id IS NULL';
          $args['OR'][] = 'HistoryRecord.co_person_role_id IS NULL';
        } else {
          // This should catch the case where COUs aren't in use
          $args[] = 'HistoryRecord.co_person_role_id IS NULL';
        }
        
        $this->Paginator->settings = $this->paginate;
        $this->set('history_records', $this->Paginator->paginate('HistoryRecord', $args));
      } else {
        // Throw an error. This controller doesn't permit retrieve all history via the UI.
        
        $this->Session->setFlash(_txt('er.fields'), '', array(), 'error');
        
        // It's not really clear where we should redirect to, since we're missing the
        // parameter that would indicate where we came from.
        $this->redirect("/");
      }
    } else {
      // Check that a person was requested, throw error if not
      parent::index();
    }
  }

  /**
   * Authorization for this Controller, called by Auth component
   * - precondition: Session.Auth holds data used for authz decisions
   * - postcondition: $permissions set with calculated permissions
   *
   * @since  COmanage Registry v0.7
   * @return Array Permissions
   */
  
  function isAuthorized() {
    // Unlike most other controllers, this one only supports viewing data. History records
    // are generally created by other parts of the application, invoking the model. To enforce
    // this, we simply don't set permission for most actions.
    
    $roles = $this->Role->calculateCMRoles();
    $pids = $this->parsePersonID($this->request->data);
    
    $managed = false;
    
    // For index views, we need to make sure the viewer has permission to see
    // records associated with the requested person.
    
    if(!empty($roles['copersonid'])) {
      if(!empty($pids['copersonid'])) {
        $managed = $this->Role->isCoOrCouAdminForCoPerson($roles['copersonid'],
                                                          $pids['copersonid']);
      } elseif(!empty($pids['orgidentityid'])) {
        $managed = $this->Role->isCoOrCouAdminForOrgIdentity($roles['copersonid'],
                                                             $pids['orgidentityid']);
      }
    }
    
    // Construct the permission set for this user, which will also be passed to the view.
    $p = array();
    
    // Determine what operations this user can perform
    
    // Add history records?
    // For now, this is only permitted via the REST API. Otherwise various operations trigger
    // history records, not user-driven views.
    $p['add'] = ($this->restful && $roles['cmadmin']);
    
    // View history records?
    // We could allow $self to view own records, but for the moment we don't (for no specific reason)
    $p['index'] = ($roles['cmadmin']
                   || ($managed && ($roles['coadmin'] || $roles['couadmin'])));
    
    if($this->action == 'index' && $p['index']) {
      // Determine which COUs a person can manage, needed for index() to filter records
      
      if($roles['cmadmin'] || $roles['coadmin'])
        $p['cous'] = $this->HistoryRecord->CoPerson->CoPersonRole->Cou->allCous($this->cur_co['Co']['id']);
      elseif(!empty($roles['admincous']))
        $p['cous'] = $roles['admincous'];
      else
        $p['cous'] = array();
    }
    
    $this->set('permissions', $p);
    return $p[$this->action];
  }
}
