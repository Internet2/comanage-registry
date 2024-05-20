<?php
/**
 * COmanage Registry CO Job History Records Controller
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
 * @since         COmanage Registry v3.1.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::uses("StandardController", "Controller");

class CoJobHistoryRecordsController extends StandardController {
  // Class name, used by Cake
  public $name = "CoJobHistoryRecords";
  
  // Establish pagination parameters for HTML views
  public $paginate = array(
    'limit' => 50,
    'order' => array(
      'CoJobHistoryRecord.id' => 'desc'
    )
  );
  
  // This controller needs a CO to be set
  public $requires_co = true;
  
  public $view_contains = array(
    'CoJob',
    'CoPerson' => array('PrimaryName'),
    'OrgIdentity' => array('PrimaryName')
  );

  /**
   * Search Block fields configuration
   *
   * @since  COmanage Registry v4.0.0
   */

  public function searchConfig($action) {
    if($action == 'index') {                   // Index
      return array(
        'search.comment' => array(
          'type'    => 'text',
          'label'   => _txt('fd.comment')
        ),
        'search.key' => array(
          'type'    => 'text',
          'label'   => _txt('fd.key')
        ),
        'search.status' => array(
          'type'    => 'select',
          'label'   => _txt('fd.status'),
          'empty'   => _txt('op.select.all'),
          'options' => _txt('en.status.job'),
        ),
      );
    }
  }

  /**
   * Determine the CO ID based on some attribute of the request.
   * This method is intended to be overridden by model-specific controllers.
   *
   * @since  COmanage Registry v3.1.0
   * @return Integer CO ID, or null if not implemented or not applicable.
   * @throws InvalidArgumentException
   */
  
  protected function calculateImpliedCoId($data = null) {
    $copersontype = null;
    
    if(!empty($this->request->params['named']['jobid'])) {
      // Determine CO via CO Job Id
      $coId = $this->CoJobHistoryRecord->CoJob->field('co_id',
                                                      array('id' => $this->request->params['named']['jobid']));
      
      if($coId) {
        return $coId;
      } else {
        throw new InvalidArgumentException(_txt('er.notfound',
                                                array(_txt('ct.co_jobs.1'),
                                                      filter_var($this->request->params['named']['jobid'],FILTER_SANITIZE_SPECIAL_CHARS))));
      }
    } elseif(!empty($this->request->params['pass'][0])) {
      // We need to pull the CO Job to get the CO
      
      $coJobId = $this->CoJobHistoryRecord->field('co_job_id',
                                                  array('id' => $this->request->params['pass'][0]));
      
      if($coJobId) {
        $coId = $this->CoJobHistoryRecord->CoJob->field('co_id', array('id' => $coJobId));
        
        if($coId) {
          return $coId;
        } else {
          throw new InvalidArgumentException(_txt('er.notfound',
                                                  array(_txt('ct.co_jobs.1'),
                                                        filter_var($coJobId,FILTER_SANITIZE_SPECIAL_CHARS))));
        }
      } else {
        throw new InvalidArgumentException(_txt('er.notfound',
                                                array(_txt('ct.co_job_history_records.1'),
                                                      filter_var($coJobId,FILTER_SANITIZE_SPECIAL_CHARS))));
      }
    }
    
    return parent::calculateImpliedCoId();
  }
  
  /**
   * Authorization for this Controller, called by Auth component
   * - precondition: Session.Auth holds data used for authz decisions
   * - postcondition: $permissions set with calculated permissions
   *
   * @since  COmanage Registry v3.1.0
   * @return Array Permissions
   */
  
  function isAuthorized() {
    $roles = $this->Role->calculateCMRoles();
    
    // Construct the permission set for this user, which will also be passed to the view.
    $p = array();
    
    // Determine what operations this user can perform
    // For now, only CO admins can view Job History. For COU Admins, we'd need logic
    // similar to HistoryRecords::isAuthorized().
    
    // View all CO Job History Records?
    $p['search'] = $p['index'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // View this CO Job History Record?
    $p['view'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    $this->set('permissions', $p);
    return $p[$this->action];
  }
  
  /**
   * Determine the conditions for pagination of the index view, when rendered via the UI.
   *
   * @since  COmanage Registry v3.1.0
   * @return Array An array suitable for use in $this->paginate
   * @throws InvalidArgumentException
   */
  
  function paginationConditions() {
    // Only retrieve entries for the requested Job or Person
    
    $ret = array();
    
    if(!empty($this->request->params['named']['jobid'])) {
      $ret['conditions']['CoJobHistoryRecord.co_job_id'] = $this->request->params['named']['jobid'];
    } elseif(!empty($this->request->params['named']['copersonid'])) {
      $ret['conditions']['CoJobHistoryRecord.co_person_id'] = $this->request->params['named']['copersonid'];
    } elseif(!empty($this->request->params['named']['orgidentityid'])) {
      $ret['conditions']['CoJobHistoryRecord.org_identity_id'] = $this->request->params['named']['orgidentityid'];
    }
    
    if(!empty($this->request->params['named']['search.comment'])) {
      // Note that comment is not currently indexed. In theory, since we filter on jobid
      // the number of records that need to be searched should be limited, and so should
      // still complete in a reasonable amount of time. However, this may need to be revisited.
      // (Also, we don't currently support lowercase indexes.)
      $searchterm = $this->request->params['named']['search.comment'];
      $searchterm = str_replace(urlencode("/"), "/", $searchterm);
      $searchterm = str_replace(urlencode(" "), " ", $searchterm);
      $searchterm = trim(strtolower($searchterm));
      $ret['conditions']['LOWER(CoJobHistoryRecord.comment) LIKE'] = "%$searchterm%";
    }
    if(!empty($this->request->params['named']['search.key'])) {
      $ret['conditions']['CoJobHistoryRecord.key'] = $this->request->params['named']['search.key'];
    }
    if(!empty($this->request->params['named']['search.status'])) {
      $ret['conditions']['CoJobHistoryRecord.status'] = $this->request->params['named']['search.status'];
    }
    
    return $ret;
  }

  /**
   * View a specific CO Job History Record.
   *
   * @since  COmanage Registry v3.1.0
   * @param  Integer $id CO Job History Record ID
   */
  
  public function view($id) {
    parent::view($id);
    if(!isset($this->request->params["named"]["render"])
       || $this->request->params["named"]["render"] !== 'norm') {
      $this->set('title_for_layout', $id);
      $this->layout = 'lightbox';
    }
  }
}
