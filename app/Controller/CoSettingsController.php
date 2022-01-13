<?php
/**
 * COmanage Registry CO Settings Controller
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
 * @since         COmanage Registry v0.9.1
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::uses("StandardController", "Controller");

class CoSettingsController extends StandardController {
  // Class name, used by Cake
  public $name = "CoSettings";
  
  public $uses = array('CoSetting', 'CmpEnrollmentConfiguration', 'CoExtendedType');
  
  // Establish pagination parameters for HTML views
  public $paginate = array(
    'limit' => 25,
    'order' => array(
      'CoSettings.co_id' => 'asc'
    )
  );
  
  // This controller needs a CO to be set
  public $requires_co = true;
  
  /**
   * Add a CO Setting
   *
   * @since  COmanage Registry v0.9.1
   */
  
  public function add() {
    // add() here deviates a bit from the typical behavior, since CO Setting has
    // a 1-1 relationship to CO. We first check to see if there is an existing
    // CO Setting record for the current CO. If there isn't we create it. Then,
    // we redirect to edit(), which behaves more normally.
    
    $settingId = null;
    
    $args = array();
    $args['conditions']['CoSetting.co_id'] = $this->cur_co['Co']['id'];
    $args['contain'] = false;
    
    $c = $this->CoSetting->find('first', $args);
    
    if($c) {
      $settingId = $c['CoSetting']['id'];
    } else {
      // Create the record.
      
      try {      
        $settingId = $this->CoSetting->addDefaults($this->cur_co['Co']['id']);
      }
      catch(Exception $e) {
        $this->Flash->set($e->getMessage(), array('key' => 'error'));
        $this->redirect('/');
      }
    }
    
    // Redirect to edit
    
    $this->redirect(array('action' => 'edit', $settingId));
  }

  public function job($id) {
    $this->Co = ClassRegistry::init('Co');
    // Get the delay interval from Platform Settings
    $plfm_id = $this->CoSetting->field('co_id', array('CoSetting.id' => $id));
    $interval = $this->CoSetting->getGarbageCollectionWindow($plfm_id);
    // Actor username
    $username_identifier = $this->Session->read('Auth.User.username');
    // Actor Id
    $person_id = $this->Co->CoPerson->idForIdentifier($plfm_id, $username_identifier);
    // Queue a CO Delete Job
    try {
      // We will need a requeue Interval, this will be the same as the queue one.
      $jobid = $this->Co->CoJob->register(
        $plfm_id,                         // $coId
        'CoreJob.GarbageCollector',       // $jobType
        null,                             // $jobTypeFk
        "",                               // $jobMode
        _txt('rs.jb.started.web', array($username_identifier, $person_id)), // $summary
        true,                             // $queued
        false,                            // $concurrent
        array(                            // $params
          'object_type' => 'Co',
        ),
        0,                                // $delay (in seconds)
        $interval                         // $requeueInterval (in seconds)
      );

      $this->Flash->set(_txt('rs.jb.registered', array($jobid)), array('key' => 'success'));

      // Issue a redirect to the job
      $this->redirect(array(
        'controller' => 'co_jobs',
        'action' => 'view',
        $jobid
      ));
    }
    catch(Exception $e) {
      $this->Flash->set($e->getMessage(), array('key' => 'error'));

      $this->redirect(array(
        'controller' => 'co_settings',
        'action' => 'edit',
        $id
      ));
    }
  }

  /**
   * Callback after controller methods are invoked but before views are rendered.
   *
   * @since  COmanage Registry v1.0.0
   */
  
  function beforeRender() {
    if(!$this->request->is('restful')) {
      $pool = $this->CmpEnrollmentConfiguration->orgIdentitiesPooled();
      
      $args = array();
      $args['conditions']['CoGroup.co_id'] = $this->cur_co['Co']['id'];
      $args['order'] = array('CoGroup.name ASC');
      
      $this->set('vv_co_groups', $this->Co->CoGroup->find("list", $args));
      
      if(!$pool) {
        // Pull the set of available pipelines. This is only possible for unpooled.
        $args = array();
        $args['conditions']['CoPipeline.status'] = SuspendableStatusEnum::Active;
        $args['conditions']['CoPipeline.co_id'] = $this->cur_co['Co']['id'];
        $args['fields'] = array('CoPipeline.id', 'CoPipeline.name');
        $args['contain'] = false;
        
        $this->set('vv_co_pipelines', $this->CoSetting->Co->CoPipeline->find('list', $args));
      }
      
      // Pull the set of available themes
      $args = array();
      $args['conditions']['CoTheme.co_id'] = $this->cur_co['Co']['id'];;
      $args['order'] = array('CoTheme.name ASC');
      
      $this->set('vv_co_themes', $this->Co->CoTheme->find("list", $args));
      
      // Pull the set of available dashboards
      $args = array();
      $args['conditions']['CoDashboard.co_id'] = $this->cur_co['Co']['id'];;
      $args['order'] = array('CoDashboard.name ASC');
      
      $this->set('vv_co_dashboards', $this->Co->CoDashboard->find("list", $args));

      // Pull Jobs scheduled for Platform CO
      $co_name = $this->Co->field('name', array('id' => $this->cur_co['Co']['id']));
      if($co_name === DEF_COMANAGE_CO_NAME) {
        $this->set(
          'vv_jobs_queued',
          $this->Co->CoJob->jobsQueuedByType($this->cur_co['Co']['id'], "CoreJob.GarbageCollector")
        );
      }

      // Pull extended types for setting Person Picker display field values
      $this->set('vv_person_picker_email_types', $this->Co->CoExtendedType->active($this->cur_co['Co']['id'], 'EmailAddress.type', 'list'));
      $this->set('vv_person_picker_identifier_types', $this->Co->CoExtendedType->active($this->cur_co['Co']['id'], 'Identifier.type', 'list'));
    }
    
    parent::beforeRender();
  }
  
  /**
   * Determine the CO ID based on some attribute of the request.
   *
   * @since  COmanage Registry v0.9.1
   * @return Integer CO ID, or null if not implemented or not applicable.
   * @throws InvalidArgumentException
   */
  
  protected function calculateImpliedCoId($data = null) {
    // if add(), accept what's in the URL
    
    return parent::calculateImpliedCoId();
  }
  
  /**
   * Perform any dependency checks required prior to a write (add/edit) operation.
   * - postcondition: Session flash message updated (HTML) or HTTP status returned (REST)
   *
   * @since  COmanage Registry v1.0.0
   * @param  Array Request data
   * @param  Array Current data
   * @return boolean true if dependency checks succeed, false otherwise.
   */
  
  function checkWriteDependencies($reqdata, $curdata = null) {
    if($reqdata['CoSetting']['sponsor_eligibility'] == SponsorEligibilityEnum::CoGroupMember
       && empty($reqdata['CoSetting']['sponsor_co_group_id'])) {
      $this->Flash->set(_txt('er.setting.gr'), array('key' => 'error'));
      
      return false;
    }
    
    return true;
  }
  
  /**
   * Update a CO Setting.
   * - precondition: Model specific attributes in $this->request->data (optional)
   * - precondition: <id> must exist
   * - postcondition: On GET, $<object>s set (HTML)
   * - postcondition: On POST success, object updated
   * - postcondition: On POST, session flash message updated (HTML) or HTTP status returned (REST)
   * - postcondition: On POST error, $invalid_fields set (REST)
   *
   * @since  COmanage Registry v0.9.1
   * @param  integer Object identifier (eg: cm_co_groups:id) representing object to be retrieved
   */
  
  public function edit($id) {
    parent::edit($id);
    
    // Create a more useful title
    $this->set('title_for_layout', _txt('op.edit-f',
                                        array(_txt('ct.co_settings.pl'),
                                              $this->viewVars['co_settings'][0]['Co']['name'])));
  }
  
  /**
   * Generate a display key to be used in messages such as "Item Added".
   *
   * @since  COmanage Registry v0.9.1
   * @param  Array A cached object (eg: from prior to a delete)
   * @return string A string to be included for display.
   */
  
  function generateDisplayKey($c = null) {
    // In this case, we always use the string "CO Settings"
    
    return _txt('ct.co_settings.pl');
  }
  
  /**
   * Authorization for this Controller, called by Auth component
   * - precondition: Session.Auth holds data used for authz decisions
   * - postcondition: $permissions set with calculated permissions
   *
   * @since  COmanage Registry v0.8.3
   * @return Array Permissions
   */
  
  function isAuthorized() {
    $roles = $this->Role->calculateCMRoles();
    
    // Construct the permission set for this user, which will also be passed to the view.
    $p = array();
    
    // Determine what operations this user can perform
    
    // Add a new CO Setting set?
    $p['add'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // Edit CO Settings?
    $p['job'] = $p['edit'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    $this->set('permissions', $p);
    return $p[$this->action];
  }
  
  /**
   * Perform a redirect back to the controller's default view.
   * - postcondition: Redirect generated
   *
   * @since  COmanage Registry v0.9.1
   */
  
  function performRedirect() {
    // Back to the edit view we go...
    
    $this->redirect(array('action' => 'edit',
                          $this->viewVars['co_settings'][0]['CoSetting']['id']));
    
  }
}
