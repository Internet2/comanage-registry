<?php
/**
 * COmanage Registry Configuration Labels Controller
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
 * @since         COmanage Registry v4.4.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::uses('StandardController', 'Controller');

class ConfigurationLabelsController extends StandardController {
  // Class name, used by Cake
  public $name = 'ConfigurationLabels';
  
  // Establish pagination parameters for HTML views
  public $paginate = array(
    'limit' => 25,
    'order' => array(
      'ConfigurationLabel.label' => 'asc'
    )
  );

  public $uses = array('ConfigurationLabel', 'Cou');
  
  // This controller needs a CO to be set
  public $requires_co = true;

  /**
   * Perform any dependency checks required prior to a delete operation.
   * - postcondition: Session flash message updated (HTML) or HTTP status returned (REST)
   *
   * @since  COmanage Registry v4.4.0
   * @param  Array Current data
   * @return boolean true if dependency checks succeed, false otherwise.
   */

  public function checkDeleteDependencies($curdata) {
    // Since we do not have a linking table, we need to maintain the hasMany list
    // manually for now
    $hasMany = array(
      'Cou'
    );

    if(!empty($curdata['ConfigurationLabel']['id'])) {
      // Remove the node from the tree before deleting it
      foreach ($hasMany as $model) {
        $args = array();
        $args['conditions']["LOWER({$model}.configuration_labels) LIKE"] = '%' . strtolower($curdata['ConfigurationLabel']['label']) . '%';
        $args['conditions']["{$model}.co_id"] = $curdata['ConfigurationLabel']['co_id'];
        $args['contain'] = false;

        if($this->Cou->find('count', $args) > 0) {
          $this->Flash->set(
            _txt('er.label.inuse.by', array($curdata['ConfigurationLabel']['label'], $model)),
            array('key' => 'error'));
          return false;
        }
      }
    }
  }

  /**
   * Authorization for this Controller, called by Auth component
   * - precondition: Session.Auth holds data used for authz decisions
   * - postcondition: $permissions set with calculated permissions
   *
   * @since  COmanage Registry v2.0.0
   * @return Array Permissions
   */
  
  function isAuthorized() {
    $roles = $this->Role->calculateCMRoles();
    
    // Construct the permission set for this user, which will also be passed to the view.
    $p = array();
    
    // Determine what operations this user can perform
    
    // Add a new Label?
    $p['add'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // Delete an existing Label?
    $p['delete'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // Edit an existing Label?
    $p['edit'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // View all existing Labels?
    $p['index'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // View an existing Label?
    $p['view'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    $this->set('permissions', $p);
    return $p[$this->action];
  }
}
