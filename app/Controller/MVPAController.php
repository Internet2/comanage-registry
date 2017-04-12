<?php
/**
 * COmanage Registry Multi-Value Person Attribute (MVPA) Controller
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
 * @since         COmanage Registry v0.1
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::uses("StandardController", "Controller");

class MVPAController extends StandardController {
  // MVPAs require a Person ID (CO or Org)
  public $requires_person = true;
  
  /**
   * Callback before other controller methods are invoked or views are rendered.
   * - postcondition: requires_co possibly set
   *
   * @since  COmanage Registry v0.4
   */
  
  function beforeFilter() {
    // MVPA controllers may or may not require a CO, depending on how
    // the CMP Enrollment Configuration is set up. Check and adjust before
    // beforeFilter is called.
    
    // Get a pointer to our model
    $req = $this->modelClass;
    $model = $this->$req;
    
    // For HTML views, require CO for proper rendering.
    
    $this->loadModel('CmpEnrollmentConfiguration');
    $pool = $this->CmpEnrollmentConfiguration->orgIdentitiesPooled();
    
    if(!$pool) {
      $this->requires_co = true;
      
      // Associate the CO model
      $this->loadModel('Co');
    }
    
    // The views will also need this
    $this->set('pool_org_identities', $pool);
    
    parent::beforeFilter();
    
    // Dynamically adjust validation rules to include the current CO ID for dynamic types.
    
    $vrule = $model->validate['type']['content']['rule'];
    $vrule[1]['coid'] = $this->cur_co['Co']['id'];
    
    $model->validator()->getField('type')->getRule('content')->rule = $vrule;
  }
  
  /**
   * Callback after controller methods are invoked but before views are rendered.
   * - precondition: Request Handler component has set $this->request
   * - postcondition: Set $sponsors
   *
   * @since  COmanage Registry v0.9
   */

  public function beforeRender() {
    // Get a pointer to our model
    $req = $this->modelClass;
    $model = $this->$req;
    
    if(!$this->request->is('restful')){
      // Provide a hint as to available types for this model
      
      $pid = $this->parsePersonID();
      
      if(!empty($pid['orgidentityid'])) {
        // Org identities use the default model types, and self service does not apply
        
        $this->set('vv_available_types', $model->defaultTypes('type'));
      } else {
        // When attached to a CO Person or Role, figure out the available extended
        // types and then filter for self service permissions
        
        $availableTypes = $model->types($this->cur_co['Co']['id'], 'type');
        
        if(!empty($this->viewVars['permissions']['selfsvc'])
           && !$this->Role->isCoOrCouAdmin($this->Session->read('Auth.User.co_person_id'),
                                           $this->cur_co['Co']['id'])) {
          // For models supporting self service permissions, adjust the available types
          // in accordance with the configuration (but not if self is an admin)
          
          foreach(array_keys($availableTypes) as $k) {
            // We use edit for the permission even if we're adding or viewing because
            // add has different semantics for calculatePermission (whether or not the person
            // can add a new item).
            if(!$this->Co->CoSelfServicePermission->calculatePermission($this->cur_co['Co']['id'],
                                                                       $req,
                                                                       'edit',
                                                                       $k)) {
              unset($availableTypes[$k]);
            }
          }
        }
        
        $this->set('vv_available_types', $availableTypes);
      }
    }
    
    parent::beforeRender();
  }
  
  /**
   * Perform any dependency checks required prior to a write (add/edit) operation.
   * This method is intended to be overridden by model-specific controllers.
   *
   * @since  COmanage Registry v0.9
   * @param  Array Request data
   * @param  Array Current data
   * @return boolean true if dependency checks succeed, false otherwise.
   */
  
  function checkWriteDependencies($reqdata, $curdata = null) {
    // Get a pointer to our model
    $req = $this->modelClass;
    $model = $this->$req;
    
    if(!empty($this->viewVars['permissions']['selfsvc'])
       && !$this->Role->isCoOrCouAdmin($this->Session->read('Auth.User.co_person_id'),
                                       $this->cur_co['Co']['id'])) {
      // Update validation rules based on self-service permissions
      
      $defaultPerm = $this->viewVars['permissions']['selfsvc'][$req]['*'];
      $perms = array();
      
      if($defaultPerm == PermissionEnum::ReadWrite) {
        // Default is readwrite, so start with the current types and remove those
        // explicitly not permitted
        
        $perms = $model->validator()->getfield('type')->getRule('content')->rule[1];
        
        foreach(array_keys($this->viewVars['permissions']['selfsvc'][$req]) as $a) {
          if($a != '*' // Skip default
             && $this->viewVars['permissions']['selfsvc'][$req][$a] != PermissionEnum::ReadWrite) {
            $i = array_search($a, $perms);
            
            if($i !== false) {
              unset($perms[$i]);
            }
          }
        }
      } else {
        // Default is readonly, so start with nothing and add in types explicitly permitted
        
        foreach(array_keys($this->viewVars['permissions']['selfsvc'][$req]) as $a) {
          if($a != '*' // Skip default
             && $this->viewVars['permissions']['selfsvc'][$req][$a] == PermissionEnum::ReadWrite) {
            $perms[] = $a;
          }
        }
      }
      
      // Update the validation rule
      $model->validator()->getfield('type')->getRule('content')->rule[1] = $perms;
    }
    
    return true;
  }
  
  /**
   * Generate history records for a transaction. This method is intended to be
   * overridden by model-specific controllers, and will be called from within a
   * try{} block so that HistoryRecord->record() may be called without worrying
   * about catching exceptions.
   *
   * @since  COmanage Registry v0.8.4
   * @param  String Controller action causing the change
   * @param  Array Data provided as part of the action (for add/edit)
   * @param  Array Previous data (for delete/edit)
   * @return boolean Whether the function completed successfully (which does not necessarily imply history was recorded)
   */
  
  public function generateHistory($action, $newdata, $olddata) {
    $req = $this->modelClass;
    $model = $this->$req;
    $modelpl = Inflector::tableize($req);
    
    // Build a change string
    $cstr = "";
    
    switch($action) {
      case 'add':
        $cstr = _txt('rs.added-a3', array(_txt('ct.'.$modelpl.'.1')));
        break;
      case 'delete':
        $cstr = _txt('rs.deleted-a3', array(_txt('ct.'.$modelpl.'.1')));
        break;
      case 'edit':
        $cstr = _txt('rs.edited-a3', array(_txt('ct.'.$modelpl.'.1')));
        break;
    }
    
    $cstr .= ": " . $model->changesToString($newdata, $olddata, $this->cur_co['Co']['id']);
    
    switch($action) {
      case 'add':
      case 'edit':
        if(!empty($newdata[$req]['org_identity_id'])) {
          $model->OrgIdentity->HistoryRecord->record(null,
                                                     null,
                                                     $newdata[$req]['org_identity_id'],
                                                     $this->Session->read('Auth.User.co_person_id'),
                                                     ActionEnum::OrgIdEditedManual,
                                                     $cstr);
        } elseif(!empty($newdata[$req]['co_person_role_id'])) {
          // Map CO Person Role to CO Person
          $copid = $model->CoPersonRole->field('co_person_id', array('CoPersonRole.id' => $newdata[$req]['co_person_role_id']));
          
          $model->CoPersonRole->HistoryRecord->record($copid,
                                                      $newdata[$req]['co_person_role_id'],
                                                      null,
                                                      $this->Session->read('Auth.User.co_person_id'),
                                                      ActionEnum::CoPersonEditedManual,
                                                      $cstr);
        } elseif(!empty($newdata[$req]['co_person_id'])) {
          $model->CoPerson->HistoryRecord->record($newdata[$req]['co_person_id'],
                                                  null,
                                                  null,
                                                  $this->Session->read('Auth.User.co_person_id'),
                                                  ActionEnum::CoPersonEditedManual,
                                                  $cstr);
        }
        break;
      case 'delete':
        if(!empty($olddata[$req]['org_identity_id'])) {
          $model->OrgIdentity->HistoryRecord->record(null,
                                                     null,
                                                     $olddata[$req]['org_identity_id'],
                                                     $this->Session->read('Auth.User.co_person_id'),
                                                     ActionEnum::OrgIdEditedManual,
                                                     $cstr);
        } elseif(!empty($olddata[$req]['co_person_role_id'])) {
          // Map CO Person Role to CO Person
          $copid = $model->CoPersonRole->field('co_person_id', array('CoPersonRole.id' => $olddata[$req]['co_person_role_id']));
          
          $model->CoPersonRole->HistoryRecord->record($copid,
                                                      $olddata[$req]['co_person_role_id'],
                                                      null,
                                                      $this->Session->read('Auth.User.co_person_id'),
                                                      ActionEnum::CoPersonEditedManual,
                                                      $cstr);
        } elseif(!empty($olddata[$req]['co_person_id'])) {
          $model->CoPerson->HistoryRecord->record($olddata[$req]['co_person_id'],
                                                  null,
                                                  null,
                                                  $this->Session->read('Auth.User.co_person_id'),
                                                  ActionEnum::CoPersonEditedManual,
                                                  $cstr);
        }
        break;
    }
    
    return true;
  }
}
