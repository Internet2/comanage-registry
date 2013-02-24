<?php
/**
 * COmanage Registry CO Provisioning Target Controller
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
 * @since         COmanage Registry v0.8
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */

App::uses("StandardController", "Controller");

class CoProvisioningTargetsController extends StandardController {
  // Class name, used by Cake
  public $name = "CoProvisioningTargets";
  
  // Establish pagination parameters for HTML views
  public $paginate = array(
    'limit' => 25,
    'order' => array(
      'description' => 'asc'
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
    
    // Bind the models so Cake can magically pull associated data
    
    foreach(array_values($plugins) as $plugin) {
      $this->CoProvisioningTarget->bindModel(array('hasOne'
                                                   => array("Co" . $plugin . "Target"
                                                            => array('dependent' => true))));
    }
    
    $this->set('plugins', $plugins);
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
      
      if(!empty($this->CoProvisioningTarget->data[$model]['id'])) {
        $this->loadModel($plugin . "." . $model);
        $this->$model->delete($this->CoProvisioningTarget->data[$model]['id']);
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
   * @return boolean true if dependency checks succeed, false otherwise.
   */
  
  function checkWriteFollowups($reqdata, $curdata = null) {
    // Create an instance of the plugin provisioning target. We do this here to avoid
    // an inconsistent state where the co_provisioning_target is created without a
    // corresponding plugin record.
    
    $pluginName = $reqdata['CoProvisioningTarget']['plugin'];
    $modelName = 'Co'. $pluginName . 'Target';
    $pluginModelName = $pluginName . "." . $modelName;
    
    $target = array();
    $target[$modelName]['co_provisioning_target_id'] = $this->CoProvisioningTarget->id;
    
    // Note that we have to disable validation because we want to create an empty row.
    $this->loadModel($pluginModelName);
    $this->$modelName->save($target, false);
    $this->_targetid = $this->$modelName->id;
    
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
    
    if(isset($roles['copersonid'])
       && $roles['copersonid']
       && isset($this->request->params['named']['copersonid'])
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
    
    // (Re)provision an existing CO Person?
    $p['provision'] = ($roles['cmadmin']
                       || ($managed && ($roles['coadmin'] || $roles['couadmin'])));
    
    // View an existing CO Provisioning Target?
    $p['view'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    $this->set('permissions', $p);
    return $p[$this->action];
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
      
      $pluginName = Sanitize::html($this->request->data['CoProvisioningTarget']['plugin']);
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
   * Execute (re)provisioning for the specified CO Person.
   * - precondition: CO Person ID passed via named parameter
   * - postcondition: Provisioning queued or executed
   *
   * @param integer CO Provisioning Target ID
   * @since COmanage Registry v0.8
   */
  
  function provision($id) {
    if($this->restful) {
      if(!empty($this->request->params['named']['copersonid'])) {
        // Find the associated Provisioning Target record
        
        $args = array();
        $args['conditions']['CoProvisioningTarget.id'] = $id;
        // Since beforeFilter bound all the plugins, this find will pull the related
        // models as well. However, to reduce the number of database queries should a
        // large number of plugins be installed, we'll use containable behavior and
        // make a second call for the plugin we want.
        $args['contain'] = false;
        
        $copt = $this->CoProvisioningTarget->find('first', $args);
        
        if(!empty($copt['CoProvisioningTarget']['plugin'])) {
          $pluginName = $copt['CoProvisioningTarget']['plugin'];
          $modelName = 'Co'. $pluginName . 'Target';
          $pluginModelName = $pluginName . "." . $modelName;
          
          // We need to manually attach the model, although if we weren't using containable
          // the above find would have done this automatically for us (under $this->CoProvisioningTarget).
          $this->loadModel($pluginModelName);
          
          $args = array();
          $args['conditions'][$modelName.'.co_provisioning_target_id'] = $id;
          $args['contain'] = false;
          
          $pluginTarget = $this->$modelName->find('first', $args);
          
          if(!empty($pluginTarget)) {
            $args = array();
            $args['conditions']['CoPerson.id'] = $this->request->params['named']['copersonid'];
            // Only pull related models relevant for provisioning
            $args['contain'] = array(
              'Co',
              'CoGroupMember',
              'CoOrgIdentityLink',
              'CoPersonRole',
              'CoPersonRole.Address',
              'CoPersonRole.Cou',
              'CoPersonRole.TelephoneNumber',
              'EmailAddress',
              'Identifier', 
              'Name'
            );
            
            $coPersonData = $this->CoProvisioningTarget->Co->CoPerson->find('first', $args);
            
            if(!empty($coPersonData)) {
              try {
                $this->$modelName->provision($pluginTarget,
                                             ProvisioningActionEnum::CoPersonReprovisionRequested,
                                             $coPersonData);
                
                $this->CoProvisioningTarget->Co->CoPerson->HistoryRecord->record(
                  $coPersonData['CoPerson']['id'],
                  null,
                  null,
                  $this->Session->read('Auth.User.co_person_id'),
                  ActionEnum::CoPersonManuallyProvisioned,
                  _txt('rs.prov-a', array($copt['CoProvisioningTarget']['description']))
                );
              }
              catch(RuntimeException $e) {
                $this->restResultHeader(500, $e->getMessage());
              }
            } else {
              $this->restResultHeader(404, "CoPerson Not Found");
            }
          } else {
            $this->restResultHeader(404, "CoProvisioningTarget Not Found");
          }
        } else {
          $this->restResultHeader(404, "CoProvisioningTarget Not Found");
        }
      } else {
        $this->restResultHeader(404, "CoPerson Not Found");
      }
    }
  }
}
