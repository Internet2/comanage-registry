<?php
/**
 * COmanage History Record Controller
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
 * @since         COmanage Registry v0.7
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
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
      'ActorCoPerson' => array('PrimaryName'),
      'CoPerson' => array('PrimaryName'),
      'CoPersonRole',
      'OrgIdentity' => array('PrimaryName'),
    )
  );
  
  public $requires_person = true;
  
  public $view_contains = array(
    'ActorCoPerson' => 'PrimaryName',
    'CoGroup',
    'CoPerson' => 'PrimaryName',
    'CoPersonRole',
    'OrgIdentity' => 'PrimaryName'
  );

  // Use the lightbox layout for view
  public function view($id) {
    parent::view($id);
    $this->set('title_for_layout', _txt('ct.history_records.1'));
    $this->layout = 'lightbox';
  }

  /**
   * Add a History Record.
   * - precondition: Model specific attributes in $this->request->data (optional)
   * - postcondition: On success, new Object created
   * - postcondition: Session flash message updated (HTML) or HTTP status returned (REST)
   * - postcondition: $<object>_id or $invalid_fields set (REST)
   *
   * @since  COmanage Registry v0.9
   */
  
  public function add() {
    parent::add();

    if(!$this->request->is('restful')) {
      // Override page title
      $n = array();
      
      if(!empty($this->request->params['named']['copersonid'])) {
        $args = array();
        $args['conditions']['CoPerson.id'] = $this->request->params['named']['copersonid'];
        $args['contain'][] = 'PrimaryName';
        
        $n = $this->HistoryRecord->CoPerson->find('first', $args);
      } elseif(!empty($this->request->params['named']['orgidentityid'])) {
        $args = array();
        $args['conditions']['OrgIdentity.id'] = $this->request->params['named']['orgidentityid'];
        $args['contain'][] = 'PrimaryName';
        
        $n = $this->HistoryRecord->OrgIdentity->find('first', $args);
      }
      
      if(!empty($n['PrimaryName'])) {
        $this->set('title_for_layout', $this->viewVars['title_for_layout'] . " (" . generateCn($n['PrimaryName']) . ")");
        $this->set('display_name', generateCn($n['PrimaryName']));
      }
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
  
  protected function calculateImpliedCoId($data = NULL) {
    // We generally want the default behavior, unless an actorcoperson was specified.
    
    if(!empty($this->request->params['named']['actorcopersonid'])) {
      $coId = $this->HistoryRecord->ActorCoPerson->field('co_id',
                                                         array('id' => $this->request->params['named']['actorcopersonid']));
      
      if($coId) {
        return $coId;
      } else {
        throw new InvalidArgumentException(_txt('er.notfound',
                                                array(_txt('ct.co_people.1'),
                                                      filter_var($this->request->params['named']['actorcopersonid'],FILTER_SANITIZE_SPECIAL_CHARS))));
      }
    } elseif(!empty($this->request->params['named']['cogroupid'])) {
      $coId = $this->HistoryRecord->CoGroup->field('co_id',
                                                   array('id' => $this->request->params['named']['cogroupid']));
      
      if($coId) {
        return $coId;
      } else {
        throw new InvalidArgumentException(_txt('er.notfound',
                                                array(_txt('ct.co_groups.1'),
                                                      filter_var($this->request->params['named']['cogroupid'],FILTER_SANITIZE_SPECIAL_CHARS))));
      }
    } elseif(!empty($this->request->params['pass'][0])) {
      // This one's tricky, how do we determine the CO of a history record? 
      // We pull the record and check the Actor CO Person ID, which will generally be populated.
      
      $coId = $this->HistoryRecord->findCoForRecord($this->request->params['pass'][0]);
      
      if($coId) {
        return $coId;
      } else {
        throw new InvalidArgumentException(_txt('er.notfound',
                                                array(_txt('ct.history_records.1'),
                                                      filter_var($this->request->params['pass'][0],FILTER_SANITIZE_SPECIAL_CHARS))));
      }
    }
    
    return parent::calculateImpliedCoId();
  }
  
  /**
   * Obtain all History Records.
   * - postcondition: $<object>s set on success (REST or HTML), using pagination (HTML only)
   * - postcondition: HTTP status returned (REST)
   * - postcondition: Session flash message updated (HTML) on suitable error
   *
   * @since  COmanage Registry v0.7
   */
  
  public function index() {
    if(!$this->request->is('restful')) {
      // We need to change enough of the standard behavior that it's easier just to reimplement.
      
      // Set page title
      $this->set('title_for_layout', _txt('ct.history_records.pl'));
      
      // We need an Org ID or a CO Person ID to retrieve on. We have to carefully craft our queries
      // in order to pull only records that the current user is authorized to see.
      
      // Use server side pagination
      
      if(!empty($this->request->params['named']['copersonid'])
         || !empty($this->request->params['named']['actorcopersonid'])) {
        // CO Administrators can see all records, however COU Administrators can only see records
        // with no CO Person Role ID or where the CO Person Role ID is in a COU they administer.
        
        $args = array();
        if(!empty($this->request->params['named']['copersonid'])) {
          $args['HistoryRecord.co_person_id'] = $this->request->params['named']['copersonid'];
        } elseif(!empty($this->request->params['named']['actorcopersonid'])) {
          $args['HistoryRecord.actor_co_person_id'] = $this->request->params['named']['actorcopersonid'];
        }
        
        if(!empty($this->viewVars['permissions']['cous'])) {
          // Pull records in the COUs this user can see, as well as those with no COU attached.
          // Note a join isn't needed here because paginate+contain is already joining the right tables.
          
          $args['OR']['CoPersonRole.cou_id'] = array_keys($this->viewVars['permissions']['cous']);
          $args['OR'][] = 'CoPersonRole.cou_id IS NULL';
          $args['OR'][] = 'HistoryRecord.co_person_role_id IS NULL';
        }
        // else COUs aren't in use. (Only admins can invoke /index, and the view var
        // will be populated for any admin.)
        
        $this->Paginator->settings = $this->paginate;
        $this->set('history_records', $this->Paginator->paginate('HistoryRecord', $args));
      } elseif(!empty($this->request->params['named']['orgidentityid'])) {
        // Org ID is a bit tricky when org identities are pooled, see below.
        // Note a join isn't needed here because paginate+contain is already joining the right tables.
        
        $pool = $this->CmpEnrollmentConfiguration->orgIdentitiesPooled();
        
        $args = array();
        $args['HistoryRecord.org_identity_id'] = $this->request->params['named']['orgidentityid'];
        
        if(!$pool) {
          if(!empty($this->viewVars['permissions']['cous'])) {
            // Pull records in the COUs this user can see, as well as those with no COU attached.
            // Note a join isn't needed here because paginate+contain is already joining the right tables.
            
            $args['OR']['CoPersonRole.cou_id'] = array_keys($this->viewVars['permissions']['cous']);
            $args['OR'][] = 'CoPersonRole.cou_id IS NULL';
            $args['OR'][] = 'HistoryRecord.co_person_role_id IS NULL';
          }
          // else COUs aren't in use. (Only admins can invoke /index, and the view var
          // will be populated for any admin.)
        }
        // else any admin can see history (and only admins can invoke /index)
        
        $this->Paginator->settings = $this->paginate;
        $this->set('history_records', $this->Paginator->paginate('HistoryRecord', $args));
      } elseif(!empty($this->request->params['named']['cogroupid'])) {
        $args = array();
        $args['HistoryRecord.co_group_id'] = $this->request->params['named']['cogroupid'];
        
        $this->Paginator->settings = $this->paginate;
        $this->set('history_records', $this->Paginator->paginate('HistoryRecord', $args));
      } else {
        // Throw an error. This controller doesn't permit retrieve all history via the UI.
        
        $this->Flash->set(_txt('er.fields'), array('key' => 'error'));
        
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
    $groupManaged = false;
    
    // For index views, we need to make sure the viewer has permission to see
    // records associated with the requested person.
    
    $pool = $this->CmpEnrollmentConfiguration->orgIdentitiesPooled();
    
    if(!empty($roles['copersonid'])) {
      if($this->action == 'view') {
        if(!empty($this->request->params['pass'][0])) {
          // We need to pull the record to determine authz
          
          $args = array();
          $args['conditions']['HistoryRecord.id'] = $this->request->params['pass'][0];
          $args['contain'] = false;
          
          $hr = $this->HistoryRecord->find('first', $args);
          
          if(!empty($hr)) {
            if(!empty($hr['HistoryRecord']['co_person_id'])) {
              $managed = $this->Role->isCoOrCouAdminForCoPerson($roles['copersonid'],
                                                                $hr['HistoryRecord']['co_person_id']);
            } elseif(!empty($hr['HistoryRecord']['org_identity_id'])) {
              $managed = $this->Role->isCoOrCouAdminForOrgIdentity($roles['copersonid'],
                                                                   $hr['HistoryRecord']['org_identity_id']);
            }
            
            // Calculate $groupManaged separately, since a group owner may not otherwis.
            // have permission based on the subject of the record
            if(!empty($hr['HistoryRecord']['co_group_id'])) {
              $groupManaged = $this->Role->isGroupManager($roles['copersonid'],
                                                          $hr['HistoryRecord']['co_group_id']);
            }
          }
        }
      } else {
        // Note the order of evaluation is important here. Since group managers might
        // not be CO/U admins, we evaluate that last. If a non-admin group manager tries
        // to post a CO Person or Org Identity ID in the URL, this will result in Permission
        // Denied. This is intentional, since otherwise index() will build a query with
        // these IDs (ie: there is no further authz check there).
        
        if(!empty($pids['copersonid'])) {
          $managed = $this->Role->isCoOrCouAdminForCoPerson($roles['copersonid'],
                                                            $pids['copersonid']);
        } elseif(!empty($pids['orgidentityid'])) {
          $managed = $this->Role->isCoOrCouAdminForOrgIdentity($roles['copersonid'],
                                                               $pids['orgidentityid']);
        } elseif(!empty($this->request->params['named']['cogroupid'])) {
          $groupManaged = $this->Role->isGroupManager($roles['copersonid'], $this->request->params['named']['cogroupid']);
        }
      }
    } elseif($pool) {
      // We won't get here for a CO Person's history even if pooled because roles[copersonid]
      // will be set. This is intentional -- we prefer to know the CO Person ID of the
      // current user if possible. That only won't be the case when pooled and examining org
      // identity history, since there is no CO in the current context.
      
      if(!empty($pids['orgidentityid'])) {
        $managed = $this->Role->isCoOrCouAdminForOrgIdentity(null,
                                                             $pids['orgidentityid'],
                                                             $this->Session->read('Auth.User.username'));
      }
    }
    
    // Construct the permission set for this user, which will also be passed to the view.
    $p = array();
    
    // Determine what operations this user can perform
    
    // Add history records?
    $p['add'] = ($roles['cmadmin']
                 || ($managed && ($roles['coadmin'] || $roles['couadmin']
                                  || ($pool &&
                                      ($roles['admin'] || $roles['subadmin'])))));
    
    // View history records?
    // We could allow $self to view own records, but for the moment we don't (for no specific reason)
    $p['index'] = ($roles['cmadmin']
                   || ($managed && ($roles['coadmin'] || $roles['couadmin']
                                    || ($pool &&
                                        ($roles['admin'] || $roles['subadmin']))))
                   || $groupManaged);
    
    if($this->action == 'index' && $p['index']) {
      // Determine which COUs a person can manage, needed for index() to filter records
      
      if($roles['cmadmin'] || $roles['coadmin']) {
        $p['cous'] = $this->HistoryRecord->CoPerson->CoPersonRole->Cou->allCous($this->cur_co['Co']['id']);
      } elseif(!empty($roles['admincous'])) {
        $p['cous'] = $roles['admincous'];
      } else {
        // This should only be empty if there are no COUs. A COU Admin must be an
        // admin of at least one COU. It will also be empty if org identities are pooled
        // since there are no COUs in the current context.
        $p['cous'] = array();
      }
    }
    
    // View a single history record?
    $p['view'] = ($roles['cmadmin']
                  || ($managed && ($roles['coadmin'] || $roles['couadmin']
                                   || ($pool &&
                                       ($roles['admin'] || $roles['subadmin']))))
                  || $groupManaged);
    
    $this->set('permissions', $p);
    return $p[$this->action];
  }

  /**
   * Redirect to 'index' view if orgidentityid and coid are defined, otherwise use default redirect.
   *
   * @since  COmanage Registry v2.0.2
   */
  function performRedirect() {
    if (isset($this->request->params['named']['orgidentityid'], $this->cur_co['Co']['id'])) {
      $redirectUrl = array(
        'controller'    => Inflector::tableize($this->modelClass),
        'action'        => 'index',
        'orgidentityid' => filter_var($this->request->params['named']['orgidentityid'], FILTER_SANITIZE_SPECIAL_CHARS),
        'co'            => filter_var($this->cur_co['Co']['id'], FILTER_SANITIZE_SPECIAL_CHARS));
      $this->redirect($redirectUrl);
    } else {
      parent::performRedirect();
    }
  }
}
