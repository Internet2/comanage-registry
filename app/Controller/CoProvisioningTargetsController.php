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
  
  // This controller needs a CO to be set
  public $requires_co = true;
  
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
      $this->CoProvisioningTarget->bindModel(array('hasOne'
                                                   => array("Co" . $plugin . "Target"
                                                            => array('dependent' => true))));
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

      $this->set('vv_available_groups', $this->Co->CoGroup->find("list", $args));
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
   * Obtain all CO Provisioning Targets
   *
   * @since  COmanage Registry v0.9.2
   */

  public function index() {
    parent::index();
    
    if(!$this->request->is('restful')) {
      // Pull the list of CO Person IDs and CO Group IDs to faciliate "Reprovision All".
      // We include all people and groups, even those not active, so we can unprovision
      // as needed.
      
      $args = array();
      $args['conditions']['CoPerson.co_id'] = $this->cur_co['Co']['id'];
      $args['fields'] = array('CoPerson.id', 'CoPerson.status');
      $args['order'] = array('CoPerson.id' => 'asc');
      $args['contain'] = false;
      
      $this->set('vv_co_people', $this->CoProvisioningTarget->Co->CoPerson->find('list', $args));
      
      $args = array();
      $args['conditions']['CoGroup.co_id'] = $this->cur_co['Co']['id'];
      $args['fields'] = array('CoGroup.id', 'CoGroup.status');
      $args['order'] = array('CoGroup.id' => 'asc');
      $args['contain'] = false;
      
      $this->set('vv_co_groups', $this->CoProvisioningTarget->Co->CoGroup->find('list', $args));
    }
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
      $copersonid = null;
      $cogroupid = null;
      
      if(!empty($this->request->params['named']['copersonid'])) {
        $copersonid = $this->request->params['named']['copersonid'];
      } elseif(!empty($this->request->params['named']['cogroupid'])) {
        $cogroupid = $this->request->params['named']['cogroupid'];
      } else {
        $this->Api->restResultHeader(500, "Bad Request");
      }
      
      // Make sure copersonid or cogroupid is in the same CO as $id
      
      $args = array();
      if($copersonid) {
        $args['joins'][0]['table'] = 'co_people';
        $args['joins'][0]['alias'] = 'CoPerson';
        $args['joins'][0]['type'] = 'INNER';
        $args['joins'][0]['conditions'][0] = 'CoProvisioningTarget.co_id=CoPerson.co_id';
        $args['conditions']['CoProvisioningTarget.id'] = $id;
        $args['conditions']['CoPerson.id'] = $copersonid;
        $args['contain'] = false;
      } else {
        $args['joins'][0]['table'] = 'co_groups';
        $args['joins'][0]['alias'] = 'CoGroup';
        $args['joins'][0]['type'] = 'INNER';
        $args['joins'][0]['conditions'][0] = 'CoProvisioningTarget.co_id=CoGroup.co_id';
        $args['conditions']['CoProvisioningTarget.id'] = $id;
        $args['conditions']['CoGroup.id'] = $cogroupid;
        $args['contain'] = false;
      }
      
      if($this->CoProvisioningTarget->find('count', $args) < 1) {
        // XXX this could also be co provisioning target not found -- do a separate find to check?
        $this->Api->restResultHeader(404, $args['joins'][0]['alias'] . " Not Found");
        return;
      }
      
      // Attach ProvisionerBehavior and manually invoke provisioning
      
      try {
        if($copersonid) {
          $this->CoProvisioningTarget->Co->CoPerson->Behaviors->load('Provisioner');
          $this->CoProvisioningTarget->Co->CoPerson->manualProvision($id, $copersonid, null);
        } else {
          $this->CoProvisioningTarget->Co->CoGroup->Behaviors->load('Provisioner');
          $this->CoProvisioningTarget->Co->CoGroup->manualProvision($id, null, $cogroupid, ProvisioningActionEnum::CoGroupReprovisionRequested);
        }
      }
      catch(InvalidArgumentException $e) {
        switch($e->getMessage()) {
          case _txt('er.cop.unk'):
            $this->Api->restResultHeader(404, $args['joins'][0]['alias'] . " Not Found");
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
}
