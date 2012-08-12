<?php
/**
 * COmanage History Record Controller
 *
 * Copyright (C) 2012 University Corporation for Advanced Internet Development, Inc.
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
 * @copyright     Copyright (C) 2012 University Corporation for Advanced Internet Development, Inc.
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
      'created' => 'desc'
    ),
    'contain' => array(
      'ActorCoPerson.Name',
      'CoPerson.Name',
      'OrgIdentity.Name'
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
      
      // We need an Org ID, CO Person ID to retrieve on. 
      
      // Use server side pagination
      
      if(!empty($this->params['named']['copersonid'])) {
        $this->set('history_records',
                   $this->paginate('HistoryRecord',
                                   array('HistoryRecord.co_person_id' => $this->params['named']['copersonid'])));
      } elseif(!empty($this->params['named']['orgidentityid'])) {
        // Org ID is a bit tricky when org identities are pooled, because we shouldn't pull
        // history for that Org ID related to COs other than the current one.
        
        $pool = $this->CmpEnrollmentConfiguration->orgIdentitiesPooled();
        
        $this->paginate['conditions'] = array('HistoryRecord.org_identity_id' => $this->params['named']['orgidentityid']);
        
        if($pool) {
          // XXX This should be replaced with a clever Cake query that joins CoPerson where
          // co_people.co_id = $this->cur_co['Co']['id'], but for the moment that's not
          // working, so we'll simply constrain to records with no CO Person associated.
          
          $this->paginate['conditions']['CoPerson.id'] = null;
        }
        
        $this->set('history_records', $this->paginate('HistoryRecord'));
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
    
    $cmr = $this->calculateCMRoles();
    
    // Construct the permission set for this user, which will also be passed to the view.
    $p = array();
    
    // Determine what operations this user can perform
    
    // Add history records?
    // For now, this is only permitted via the REST API. Otherwise various operations trigger
    // history records, not user-driven views.
    $p['add'] = ($this->restful && $cmr['cmadmin']);
    
    // View history records?
    // We could allow $self to view own records, but for the moment we don't (for no specific reason)
    $p['index'] = ($cmr['cmadmin'] || $cmr['coadmin']);
    
    $this->set('permissions', $p);
    return($p[$this->action]);
  }
}
