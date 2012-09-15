<?php
/**
 * COmanage Registry CO Enrollment Flows Controller
 *
 * Copyright (C) 2011-12 University Corporation for Advanced Internet Development, Inc.
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
 * @copyright     Copyright (C) 2011-12 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.3
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */

App::uses("StandardController", "Controller");
  
class CoEnrollmentFlowsController extends StandardController {
  // Class name, used by Cake
  public $name = "CoEnrollmentFlows";
  
  // Establish pagination parameters for HTML views
  public $paginate = array(
    'limit' => 25,
    'order' => array(
      'CoEnrollmentFlow.name' => 'asc'
    )
  );
  
  // This controller needs a CO to be set
  public $requires_co = true;
  
  /**
   * Authorization for this Controller, called by Auth component
   * - precondition: Session.Auth holds data used for authz decisions
   * - postcondition: $permissions set with calculated permissions
   *
   * @since  COmanage Registry v0.3
   * @return Array Permissions
   */
  
  function isAuthorized() {
    $cmr = $this->calculateCMRoles();
    
    // Construct the permission set for this user, which will also be passed to the view.
    $p = array();
    
    // Determine what operations this user can perform
    
    // Add a new CO Enrollment Flow?
    $p['add'] = ($cmr['cmadmin'] || $cmr['coadmin']);
    
    // Delete an existing CO Enrollment Flow?
    $p['delete'] = ($cmr['cmadmin'] || $cmr['coadmin']);
    
    // Edit an existing CO Enrollment Flow?
    $p['edit'] = ($cmr['cmadmin'] || $cmr['coadmin']);
    
    // View all existing CO Enrollment Flows?
    $p['index'] = ($cmr['cmadmin'] || $cmr['coadmin']);
    
    // Select a CO Enrollment Flow to create a petition from?
    // Any logged in person can get to this page, however which enrollment flows they
    // see will be determined dynamically.
    $p['select'] = $cmr['user'];
    
    // View an existing CO Enrollment Flow?
    $p['view'] = ($cmr['cmadmin'] || $cmr['coadmin']);

    $this->set('permissions', $p);
    return($p[$this->action]);
  }
  
  /**
   * Callback after controller methods are invoked but before views are rendered.
   * - precondition: Request Handler component has set $this->request->params
   * - postcondition: $cous may be set.
   * - postcondition: $co_groups may be set.
   *
   * @since  COmanage Registry v0.7
   */
  
  function beforeRender() {
    if(!$this->restful) {
      $this->set('cous', $this->Co->Cou->allCous($this->cur_co['Co']['id'], "hash"));
      
      $args = array();
      $args['conditions']['CoGroup.co_id'] = $this->cur_co['Co']['id'];
      
      $this->set('co_groups', $this->Co->CoGroup->find("list", $args));
    }
    
    parent::beforeRender();
  }
  
  /**
   * Perform any dependency checks required prior to a write (add/edit) operation.
   * This method is intended to be overridden by model-specific controllers.
   *
   * @since  COmanage Registry v0.7
   * @param  Array Request data
   * @param  Array Current data
   * @return boolean true if dependency checks succeed, false otherwise.
   */
  
  function checkWriteDependencies($reqdata, $curdata = null) {
    // Make sure that a COU ID or CO Group ID was provided, if appropriate.
    
    if(isset($reqdata['CoEnrollmentFlow']['authz_level'])) {
      if($reqdata['CoEnrollmentFlow']['authz_level'] == EnrollmentAuthzEnum::CoGroupMember) {
        if(!isset($reqdata['CoEnrollmentFlow']['authz_co_group_id'])
           || $reqdata['CoEnrollmentFlow']['authz_co_group_id'] == "") {
          $this->Session->setFlash(_txt('er.ef.authz.gr',
                                        array(_txt('en.enrollment.authz', null, $reqdata['CoEnrollmentFlow']['authz_level']))),
                                   '', array(), 'error');
          
          return false;
        }
      } elseif($reqdata['CoEnrollmentFlow']['authz_level'] == EnrollmentAuthzEnum::CouAdmin
               || $reqdata['CoEnrollmentFlow']['authz_level'] == EnrollmentAuthzEnum::CouPerson) {
        if(!isset($reqdata['CoEnrollmentFlow']['authz_cou_id'])
           || $reqdata['CoEnrollmentFlow']['authz_cou_id'] == "") {
          $this->Session->setFlash(_txt('er.ef.authz.cou',
                                        array(_txt('en.enrollment.authz', null, $reqdata['CoEnrollmentFlow']['authz_level']))),
                                   '', array(), 'error');
          
          return false;
        }
      }
    }
    
    return true;
  }
  
  /**
   * Perform a redirect back to the controller's default view.
   * - postcondition: Redirect generated
   *
   * @since  COmanage Registry v0.3
   */
  
  function performRedirect() {
    // On add, redirect to collect attributes
    
    if($this->action == 'add')
      $this->redirect(array('controller' => 'co_enrollment_attributes',
                            'action' => 'add',
                            'coef' => $this->CoEnrollmentFlow->id,
                            'co' => $this->cur_co['Co']['id']));
    else
      parent::performRedirect();
  }
  
  /**
   * Select an enrollment flow to create a petition from.
   * - postcondition: $co_enrollment_flows set
   *
   * @since  COmanage Registry v0.5
   */
  
  function select() {
    // Determine the Enrollment Flows for this CO and pass them to the view.
    // Currently, we don't check for COU-specific flows. 
    
    // Set page title
    $this->set('title_for_layout', _txt('ct.co_enrollment_flows.pl'));
    
    // Determine which enrollment flows the current user can see
    
    // XXX As of Cake 2.3 (which we're not currently using), the paginate accepts the 'findtype' parameter
    // instead of setting the 0'th index in the array.
    $this->paginate[0] = 'authorized';
    $this->paginate['conditions']['CoEnrollmentFlow.co_id'] = $this->cur_co['Co']['id'];
    // This parameter is for the custom find
    $this->paginate['authorizeCoPersonId'] = $this->Session->read('Auth.User.co_person_id');
    $this->paginate['contain'] = false;
    
    $this->set('co_enrollment_flows', $this->paginate('CoEnrollmentFlow'));
  }
}
