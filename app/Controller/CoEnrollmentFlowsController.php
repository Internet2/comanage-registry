<?php
/**
 * COmanage Registry CO Enrollment Flows Controller
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
 * @since         COmanage Registry v0.3
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
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
  
  public $uses = array('CoEnrollmentFlow', 'CmpEnrollmentConfiguration');
  
  public $edit_contains = array(
    'CoEnrollmentAuthenticator',
    'CoEnrollmentFlowAuthzCoGroup',
    'CoEnrollmentFlowAuthzCou',
    'CoEnrollmentSource'
  );
  
  public $view_contains = array(
    'CoEnrollmentFlowAuthzCoGroup',
    'CoEnrollmentFlowAuthzCou'
  );
  
  /**
   * Insert the default Enrollment Flow templates for the current CO.
   *
   * @since  COmanage Registry v0.9.2
   */
  
  public function addDefaults() {
    try {
      $this->CoEnrollmentFlow->addDefaults($this->cur_co['Co']['id']);
      
      $this->Flash->set(_txt('rs.ef.defaults'), array('key' => 'success'));
    }
    catch(Exception $e) {
      $this->Flash->set($e->getMessage(), array('key' => 'error'));
    }
    
    // redirect back to index page
    $this->performRedirect();
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
    if(!$this->request->is('restful')) {
      $pool = $this->CmpEnrollmentConfiguration->orgIdentitiesPooled();
      
      $this->set('cous', $this->Co->Cou->allCous($this->cur_co['Co']['id'], "hash"));
      
      $args = array();
      $args['conditions']['CoGroup.co_id'] = $this->cur_co['Co']['id'];
      $args['conditions']['CoGroup.status'] = SuspendableStatusEnum::Active;
      $args['order'] = array('CoGroup.name ASC');
      
      $this->set('co_groups', $this->Co->CoGroup->find("list", $args));
      
      if($this->CmpEnrollmentConfiguration->orgIdentitiesFromCOEF()
         && $this->CmpEnrollmentConfiguration->enrollmentAttributesFromEnv()) {
        $this->set('vv_attributes_from_env', true);
      }
      
      if(!$pool) {
        // Pull the set of available pipelines. This is only possible for unpooled.
        $args = array();
        $args['conditions']['CoPipeline.status'] = SuspendableStatusEnum::Active;
        $args['conditions']['CoPipeline.co_id'] = $this->cur_co['Co']['id'];
        $args['fields'] = array('CoPipeline.id', 'CoPipeline.name');
        $args['contain'] = false;
        
        $this->set('vv_co_pipelines', $this->CoEnrollmentFlow->CoPipeline->find('list', $args));
      }
      
      // Provide a list of org identity sources
      $args = array();
      $args['conditions']['OrgIdentitySource.co_id'] = $this->cur_co['Co']['id'];
      $args['conditions']['OrgIdentitySource.status'] = SuspendableStatusEnum::Active;
      $args['contain'] = false;
      
      $this->set('vv_avail_ois', $this->CoEnrollmentFlow->Co->OrgIdentitySource->find('all', $args));
      
      // Provide a list of message templates
      $args = array();
      $args['conditions']['co_id'] = $this->cur_co['Co']['id'];
      $args['conditions']['status'] = SuspendableStatusEnum::Active;
      $args['conditions']['context'] = array(
        MessageTemplateEnum::EnrollmentApproval,
        MessageTemplateEnum::EnrollmentFinalization,
        MessageTemplateEnum::EnrollmentVerification
      );
      $args['fields'] = array(
        'CoEnrollmentFlowAppMessageTemplate.id',
        'CoEnrollmentFlowAppMessageTemplate.description',
        'CoEnrollmentFlowAppMessageTemplate.context'
      );
      
      $this->set('vv_message_templates',
                 $this->CoEnrollmentFlow->CoEnrollmentFlowAppMessageTemplate->find('list', $args));
      
      // Pull the set of available themes
      $args = array();
      $args['conditions']['CoTheme.co_id'] = $this->cur_co['Co']['id'];;
      $args['order'] = array('CoTheme.name ASC');
      
      $this->set('vv_co_themes', $this->CoEnrollmentFlow->Co->CoTheme->find("list", $args));
      
      // Pull the set of available authenticators
      $args = array();
      $args['conditions']['Authenticator.co_id'] = $this->cur_co['Co']['id'];
      $args['conditions']['Authenticator.status'] = SuspendableStatusEnum::Active;
      $args['contain'] = false;
      
      $this->set('vv_authenticators', $this->CoEnrollmentFlow->Co->Authenticator->find('list', $args));
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
          $this->Flash->set(_txt('er.ef.authz.gr',
                                 array(_txt('en.enrollment.authz', null, $reqdata['CoEnrollmentFlow']['authz_level']))),
                            array('key' => 'error'));
          
          return false;
        }
      } elseif($reqdata['CoEnrollmentFlow']['authz_level'] == EnrollmentAuthzEnum::CouAdmin
               || $reqdata['CoEnrollmentFlow']['authz_level'] == EnrollmentAuthzEnum::CouPerson) {
        if(!isset($reqdata['CoEnrollmentFlow']['authz_cou_id'])
           || $reqdata['CoEnrollmentFlow']['authz_cou_id'] == "") {
          $this->Flash->set(_txt('er.ef.authz.cou',
                                 array(_txt('en.enrollment.authz', null, $reqdata['CoEnrollmentFlow']['authz_level']))),
                            array('key' => 'error'));
          
          return false;
        }
      }
    }
    
    return true;
  }
  
  /**
   * Duplicate an existing Enrollment Flow.
   * - postcondition: Redirect issued
   *
   * @since  COmanage Registry v0.9.2
   * @param  Integer $id CO Enrollment Flow ID
   */
  
  public function duplicate($id) {
    try {
      $this->CoEnrollmentFlow->duplicate($id);
      $this->Flash->set(_txt('rs.copy-a1', array(_txt('ct.enrollment_flows.1'))), array('key' => 'success'));
    }
    catch(Exception $e) {
      $this->Flash->set($e->getMessage(), array('key' => 'error'));
    }
    
    $this->performRedirect();
  }
  
  /**
   * Authorization for this Controller, called by Auth component
   * - precondition: Session.Auth holds data used for authz decisions
   * - postcondition: $permissions set with calculated permissions
   *
   * @since  COmanage Registry v0.3
   * @return Array Permissions
   */
  
  function isAuthorized() {
    $roles = $this->Role->calculateCMRoles();
    
    // Construct the permission set for this user, which will also be passed to the view.
    $p = array();
    
    // Determine what operations this user can perform
    
    // Add a new CO Enrollment Flow?
    $p['add'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // Add/restore default CO Enrollment Flows?
    $p['addDefaults'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // Delete an existing CO Enrollment Flow?
    $p['delete'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // Duplicate an existing CO Enrollment Flow?
    $p['duplicate'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // Edit an existing CO Enrollment Flow?
    $p['edit'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // View all existing CO Enrollment Flows?
    $p['index'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // Select a CO Enrollment Flow to create a petition from?
    // Any logged in person can get to this page, however which enrollment flows they
    // see will be determined dynamically.
    $p['select'] = $roles['user'];
    $p['search'] = $roles['user'];
    
    // View an existing CO Enrollment Flow?
    $p['view'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    $this->set('permissions', $p);
    return $p[$this->action];
  }
  
  /**
   * For Models that accept a CO ID, find the provided CO ID.
   * - precondition: A coid must be provided in $this->request (params or data)
   *
   * @since  COmanage Registry v0.9.2
   * @return Integer The CO ID if found, or -1 if not
   */
  
  public function parseCOID($data = null) {
    if($this->action == 'addDefaults') {
      if(isset($this->request->params['named']['co'])) {
        return $this->request->params['named']['co'];
      }
    }
    
    return parent::parseCOID();
  }
  
  /**
   * Perform a redirect back to the controller's default view.
   * - postcondition: Redirect generated
   *
   * @since  COmanage Registry v0.3
   */
  
  function performRedirect() {
    // On add, redirect to collect attributes
    
    if($this->action == 'add') {
      $this->redirect(array('controller' => 'co_enrollment_attributes',
                            'action' => 'add',
                            'coef' => $this->CoEnrollmentFlow->id,
                            'co' => $this->cur_co['Co']['id']));
    } elseif($this->action != 'edit') {
      parent::performRedirect();
    }
  }
  
  /**
   * Select an enrollment flow to create a petition from.
   * - postcondition: $co_enrollment_flows set
   *
   * @since  COmanage Registry v0.5
   */
  
  function select() {
    // Set page title
    $this->set('title_for_layout', _txt('ct.co_enrollment_flows.pl'));
    
    // Check if we have been redirected by search
    $enrollmentFlowName = isset($this->request->params['named']['search.eofName']) ? strtolower($this->request->params['named']['search.eofName']) : "";
    // Start with a list of enrollment flows
    // Use server side pagination
    $this->paginate['conditions'] = array();
    $this->paginate['conditions']['CoEnrollmentFlow.co_id'] = $this->cur_co['Co']['id'];
    $this->paginate['conditions']['CoEnrollmentFlow.status'] = TemplateableStatusEnum::Active;
    if($enrollmentFlowName != ""){
      $this->paginate['conditions']['LOWER(CoEnrollmentFlow.name) LIKE'] = "%{$enrollmentFlowName}%";
    }
    $this->paginate['contain'] = false;
    
    $this->Paginator->settings = $this->paginate;
    $flows =  $this->Paginator->paginate('CoEnrollmentFlow');
    
    // Walk through the list of flows and see which ones this user is authorized to run
    
    $authedFlows = array();
    $roles = $this->Role->calculateCMRoles();
    
    foreach($flows as $f) {
      // pass $role to model->authorize
      
      if($roles['cmadmin']
         || $this->CoEnrollmentFlow->authorize($f,
                                               $this->Session->read('Auth.User.co_person_id'),
                                               $this->Session->read('Auth.User.username'),
                                               $this->Role)) {
        $authedFlows[] = $f;
      }
    }
    
    $this->set('co_enrollment_flows', $authedFlows);
  }


    /**
   * Insert search parameters into URL for index.
   * - postcondition: Redirect generated
   *
   * @since  COmanage Registry v3.3
   */
  
  public function search() {
    $url['action'] = 'select';
    
    // build a URL will all the search elements in it
    // the resulting URL will be
    // example.com/registry/co_people/select/co:2?search.givenName:albert/search.familyName:einstein
    foreach($this->data['search'] as $field=>$value){
      if(!empty($value)) {
        $url['search.'.$field] = trim($value);
      }
    }
    $url['co'] = $this->cur_co['Co']['id'];
    // redirect the user to the url
    $this->redirect($url, null, true);
  }
}
