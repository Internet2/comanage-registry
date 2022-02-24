<?php
/**
 * COmanage Registry CO Provisioning Target Controller
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
 * @since         COmanage Registry v0.8
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::uses("StandardController", "Controller");

class CoProvisioningTargetsController extends StandardController {
  // Class name, used by Cake
  public $name = "CoProvisioningTargets";
  
  // Establish pagination parameters for HTML views
  public $paginate = array(
    'limit' => 25,
    'order' => array(
      'ordr' => 'asc'
    )
  );
  
  public $uses = array('CoProvisioningTarget', 'CoJob');
  
  // This controller needs a CO to be set
  public $requires_co = true;
  
  public $edit_contains = array();
  
  public $view_contains = array();

  /**
   * @since  COmanage Registry v4.0.2
   * @var null
   */

  private $_sModel = null;

  /**
   * @since  COmanage Registry v4.0.2
   * @var null
   */

  private $_sId = null;

  /**
   * @since  COmanage Registry v4.0.2
   * @var string
   */

  private $_sAction = ProvisioningActionEnum::CoPersonReprovisionRequested;

  /**
   * Callback before other controller methods are invoked or views are rendered.
   * - postcondition: $plugins set
   *
   * @since  COmanage Registry v0.8
   * @throws InvalidArgumentException
   */   
  
  function beforeFilter() {
    parent::beforeFilter();
    
    $plugins = $this->loadAvailablePlugins('provisioner');
    
    // Bind the models so Cake can magically pull associated data. Note this will
    // create associations with *all* provisioner plugins, not just the one that
    // is actually associated with this ProvisioningTarget. Given that most installations
    // will only have a handful of provisioners, that seems OK (vs parsing the request
    // data to figure out which type of Plugin we should bind).
    
    foreach(array_values($plugins) as $plugin) {
      $pmodel = "Co" . $plugin . "Target";
      
      $this->CoProvisioningTarget->bindModel(array('hasOne'
                                                   => array($pmodel
                                                            => array('dependent' => true))));
      
      $this->edit_contains[] = $pmodel;
      $this->view_contains[] = $pmodel;
    }
    
    $this->set('plugins', $plugins);
  }
  
  /**
   * Callback after controller methods are invoked but before views are rendered.
   *
   * @since  COmanage Registry v2.0.0
   */

  function beforeRender() {
    if(!$this->request->is('restful')) {
      $args = array();
      $args['conditions']['CoGroup.co_id'] = $this->cur_co['Co']['id'];
      $args['conditions']['CoGroup.status'] = SuspendableStatusEnum::Active;
      $args['order'] = array('CoGroup.name ASC');
      $args['contain'] = false;

      $this->set('vv_available_groups', $this->Co->CoGroup->find("list", $args));
      
      // Pull the list of Org Identity Sources
      $args = array();
      $args['conditions']['OrgIdentitySource.co_id'] = $this->cur_co['Co']['id'];
      $args['conditions']['OrgIdentitySource.status'] = SuspendableStatusEnum::Active;
      $args['order'] = array('OrgIdentitySource.description ASC');
      $args['contain'] = false;
      
      $this->set('vv_org_identity_sources', $this->Co->OrgIdentitySource->find("list", $args));
      
      // Pull the list of Data Filters
      $args = array();
      $args['conditions']['DataFilter.co_id'] = $this->cur_co['Co']['id'];
      $args['conditions']['DataFilter.status'] = SuspendableStatusEnum::Active;
      $args['order'] = array('DataFilter.description ASC');
      $args['contain'] = false;
      
      $this->set('vv_data_filters', $this->Co->DataFilter->find("list", $args));
    }
    
    parent::beforeRender();
  }

  /**
   * Perform any dependency checks required prior to a delete operation.
   * - postcondition: Session flash message updated (HTML) or HTTP status returned (REST)
   *
   * @since  COmanage Registry v0.8
   * @param  Array Current data
   * @return boolean true if dependency checks succeed, false otherwise.
   */

  function checkDeleteDependencies($curdata) {
    // Annoyingly, the read() call in standardController resets the associations made
    // by the bindModel() call in beforeFilter(), above. Beyond that, deep down in
    // Cake's Model, a find() is called as part of the delete() which also resets the associations.
    // So we have to manually delete any dependencies.
    
    // Use the previously obtained list of plugins as a guide
    $plugins = $this->viewVars['plugins'];
    
    foreach(array_values($plugins) as $plugin) {
      $model = "Co" . $plugin . "Target";
      
      if(!empty($curdata[$model]['id'])) {
        // (CO-1988)Remove the plugin object from the instance and enforce the creation of a new one
        if(!empty(ClassRegistry::getObject($model))) {
          ClassRegistry::removeObject($model);
        }
        $this->loadModel($plugin . "." . $model);
        $this->$model->delete($curdata[$model]['id']);
      }
    }
    
    return true;
  }
  
  /**
   * Perform any followups following a write operation.  Note that if this
   * method fails, it must return a warning or REST response, but that the
   * overall transaction is still considered a success (add/edit is not
   * rolled back).
   * This method is intended to be overridden by model-specific controllers.
   *
   * @since  COmanage Registry v0.8
   * @param  Array Request data
   * @param  Array Current data
   * @param  Array Original request data (unmodified by callbacks)
   * @return boolean true if dependency checks succeed, false otherwise.
   */
  
  function checkWriteFollowups($reqdata, $curdata = null, $origdata = null) {
    if(!$curdata) {
      // Create an instance of the plugin provisioning target. We do this here to avoid
      // an inconsistent state where the co_provisioning_target is created without a
      // corresponding plugin record.
      
      // A better check would be to see if there is an existing corresponding row
      // (rather than !$curdata) since we don't fail if the initial attempt to create
      // the row fails.
      
      $pluginName = $reqdata['CoProvisioningTarget']['plugin'];
      $modelName = 'Co'. $pluginName . 'Target';
      $pluginModelName = $pluginName . "." . $modelName;
      
      $target = array();
      $target[$modelName]['co_provisioning_target_id'] = $this->CoProvisioningTarget->id;
      
      // Note that we have to disable validation because we want to create an empty row.
      $this->loadModel($pluginModelName);
      if(!$this->$modelName->save($target, false)) {
        return false;
      }
      $this->_targetid = $this->$modelName->id;
    }
    
    return true;
  }  
  
  /**
   * Authorization for this Controller, called by Auth component
   * - precondition: Session.Auth holds data used for authz decisions
   * - postcondition: $permissions set with calculated permissions
   *
   * @since  COmanage Registry v0.8
   * @return Array Permissions
   */
  
  function isAuthorized() {
    $roles = $this->Role->calculateCMRoles();
    
    // Is this a record we can manage?
    $managed = false;
    
    if(!empty($roles['copersonid'])
       && !empty($this->request->params['named']['copersonid'])
       && $this->action == 'provision') {
      $managed = $this->Role->isCoOrCouAdminForCoPerson($roles['copersonid'],
                                                        $this->request->params['named']['copersonid']);
    }
    
    // Construct the permission set for this user, which will also be passed to the view.
    $p = array();
    
    // Determine what operations this user can perform
    
    // Add a new CO Provisioning Target?
    $p['add'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // Delete an existing CO Provisioning Target?
    $p['delete'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // Edit an existing CO Provisioning Target?
    $p['edit'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // View all existing CO Provisioning Targets?
    $p['index'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // Edit an existing CO Provisioning Target's order?
    $p['order'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // (Re)provision an existing CO Person?
    $p['provision'] = ($roles['cmadmin']
                       || $roles['coadmin'] 
                       || ($managed && $roles['couadmin']));
    
    // (Re)provision all CO People?
    $p['provisionall'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // Modify ordering for display via AJAX 
    $p['reorder'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // View an existing CO Provisioning Target?
    $p['view'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    $this->set('permissions', $p);
    return $p[$this->action];
  }
  
  /**
   * For Models that accept a CO ID, find the provided CO ID.
   * - precondition: A coid must be provided in $this->request (params or data)
   *
   * @since  COmanage Registry v1.0.3
   * @return Integer The CO ID if found, or -1 if not
   */
  
  public function parseCOID($data = NULL) {
    if($this->action == 'order'
       || $this->action == 'reorder') {
      if(isset($this->request->params['named']['co'])) {
        return $this->request->params['named']['co'];
      }
    }

    // The direct ancestor of CoProvisioningTarget is CO Model. As a result
    // Api->requestedCOID will not be able to extract the CO Id
    // We will calculate the CO ID here
    if($this->request->is('restful')) {
      if (!empty($this->request->params['named']['copersonid'])) {
        $this->_sId = $this->request->params['named']['copersonid'];
        $this->_sModel = 'CoPerson';
      } elseif (!empty($this->request->params['named']['cogroupid'])) {
        $this->_sId = $this->request->params['named']['cogroupid'];
        $this->_sModel = 'CoGroup';
        $this->_sAction = ProvisioningActionEnum::CoGroupReprovisionRequested;
      } elseif (!empty($this->request->params['named']['coemaillistid'])) {
        $this->_sId = $this->request->params['named']['coemaillistid'];
        $this->_sModel = 'CoEmailList';
        $this->_sAction = ProvisioningActionEnum::CoEmailListReprovisionRequested;
      } elseif (!empty($this->request->params['named']['coserviceid'])) {
        $this->_sId = $this->request->params['named']['coserviceid'];
        $this->_sModel = 'CoService';
        $this->_sAction = ProvisioningActionEnum::CoServiceReprovisionRequested;
      } else {
        $this->Api->restResultHeader(500, "Bad Request");
      }

      // Make sure the subject is in the same CO as $id

      $args = array();
      $args['joins'][0]['table'] = Inflector::tableize($this->_sModel);
      $args['joins'][0]['alias'] = $this->_sModel;
      $args['joins'][0]['type'] = 'INNER';
      $args['joins'][0]['conditions'][0] = 'CoProvisioningTarget.co_id=' . $this->_sModel . '.co_id';
      $args['conditions'][$this->_sModel . '.id'] = $this->_sId;
      $args['conditions']['CoProvisioningTarget.id'] = $this->request->params["pass"][0];
      $args['contain'] = false;

      $provisioner = $this->CoProvisioningTarget->find('first', $args);

      if (empty($provisioner)) {
        // XXX this could also be co provisioning target not found -- do a separate find to check?
        $this->Api->restResultHeader(404, $args['joins'][0]['alias'] . " Not Found");
        return;
      }

      return $provisioner['CoProvisioningTarget']['co_id'];
    }
    
    return parent::parseCOID();
  }
  
  /**
   * Perform a redirect back to the controller's default view.
   * - postcondition: Redirect generated
   *
   * @since  COmanage Registry v0.8
   */
  
  function performRedirect() {
    if($this->action == 'add' && !empty($this->request->data['CoProvisioningTarget']['plugin'])) {
      // Redirect to the appropriate plugin to set up whatever it wants
      
      $pluginName = filter_var($this->request->data['CoProvisioningTarget']['plugin'],FILTER_SANITIZE_SPECIAL_CHARS);
      $modelName = 'Co'. $pluginName . 'Target';
      $pluginModelName = $pluginName . "." . $modelName;
      
      $target = array();
      $target['plugin'] = Inflector::underscore($pluginName);
      $target['controller'] = Inflector::tableize($modelName);
      $target['action'] = 'edit';
      $target[] = $this->_targetid;
      $target['co'] = $this->cur_co['Co']['id'];
      
      $this->redirect($target);
    } else {
      parent::performRedirect();
    }
  }
  
  /**
   * Execute (re)provisioning for the specified CO Person or CO Group.
   * - precondition: CO Person ID or CO Group ID passed via named parameter
   * - postcondition: Provisioning queued or executed
   *
   * @param integer CO Provisioning Target ID
   * @since COmanage Registry v0.8
   */
  
  function provision($id) {
    if($this->request->is('restful')) {
      // Attach ProvisionerBehavior and manually invoke provisioning
      
      try {
        $mdl = $this->_sModel;
        $this->CoProvisioningTarget->Co->$mdl->Behaviors->load('Provisioner');
        $this->CoProvisioningTarget->Co->$mdl->manualProvision($id,
                                                                  ($mdl == 'CoPerson' ? $this->_sId : null),
                                                                  ($mdl == 'CoGroup' ? $this->_sId : null),
                                                                  $this->_sAction,
                                                                  ($mdl == 'CoEmailList' ? $this->_sId : null),
                                                                  null, // CoGroupMemberId
                                                                  null, // ActorCoPersonId
                                                                  ($mdl == 'CoService' ? $this->_sId : null));
      }
      catch(InvalidArgumentException $e) {
        switch($e->getMessage()) {
          case _txt('er.cop.unk'):
            $this->Api->restResultHeader(404, $mdl . " Not Found");
            break;
          case _txt('er.copt.unk'):
            $this->Api->restResultHeader(404, "CoProvisioningTarget Not Found");
            break;
          default:
            $this->Api->restResultHeader(500, $e->getMessage());
            break;
        }
      }
      catch(RuntimeException $e) {
        $this->Api->restResultHeader(500, $e->getMessage());
      }
    }
  }
  
  /**
   * Run Provisioning for a specific provisioning target.
   *
   * @since  COmanage Registry v3.3.0
   */
  
  public function provisionall($id) {
    // Queue a CO Job to reprovision all subjects for the specified target
    
    try {
      $jobid = $this->CoJob->register($this->cur_co['Co']['id'],
                                      'CoreJob.Provision',
                                      null,
                                      "",
                                      // Update with CO-1729
                                      _txt('rs.jb.started.web', array($this->Session->read('Auth.User.username'), $this->Session->read('Auth.User.co_person_id'))),
                                      true,
                                      false,
                                      array(
                                        'co_provisioning_target_id' => $id,
                                        'record_type' => 'All'
                                      ));
      
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
        'action' => 'index',
        'co' => $this->cur_co['Co']['id']
      ));
    }
  }
}
