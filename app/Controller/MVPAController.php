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
  // MVPAs require a Person ID (CO or Org, or Dept as of v3.1.0)
  public $requires_person = true;

  // Use the lightbox layout for view
  public function view($id) {
    parent::view($id);
    if(!isset($this->request->params["named"]["render"])
       || $this->request->params["named"]["render"] !== 'norm') {
        $req = $this->modelClass;
        $model = $this->$req;
        $modelpl = Inflector::tableize($req);
        $this->set('title_for_layout', _txt('ct.' . $modelpl . '.1'));
        $this->layout = 'lightbox';
    }
  }
  
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
    $modelpl = Inflector::tableize($req);
    
    if(!$this->request->is('restful')){
      // Provide a hint as to available types for this model
      
      $pid = $this->parsePersonID();

      // Set the person info for view usage
      $this->set('vv_pid', $pid);
      
      // We should have a name useful for breadcrumbs in the associated data

      if(!empty($this->viewVars[$modelpl][0]['CoDepartment']['name'])) {
        $this->set('vv_bc_name', $this->viewVars[$modelpl][0]['CoDepartment']['name']);
      } elseif(!empty($this->viewVars[$modelpl][0]['CoPerson']['PrimaryName']['id'])) {
        $this->set('vv_bc_name', generateCn($this->viewVars[$modelpl][0]['CoPerson']['PrimaryName']));
      } elseif(!empty($this->viewVars[$modelpl][0]['CoPersonRole']['CoPerson']['PrimaryName']['id'])) {
        if(!empty($this->viewVars[$modelpl][0]['CoPersonRole']['title'])) {
          $this->set('vv_bc_name', $this->viewVars[$modelpl][0]['CoPersonRole']['title']);
        } else {
          $this->set('vv_bc_name', _txt('ct.co_person_roles.1'));
        }
        // Also set a parent breadcrumb of the Person
        $this->set('vv_pbc_id', $this->viewVars[$modelpl][0]['CoPersonRole']['CoPerson']['id']);
        $this->set('vv_pbc_name', generateCn($this->viewVars[$modelpl][0]['CoPersonRole']['CoPerson']['PrimaryName']));
      } elseif(!empty($this->viewVars[$modelpl][0]['Organization']['name'])) {
        $this->set('vv_bc_name', $this->viewVars[$modelpl][0]['Organization']['name']);
      } elseif(!empty($this->viewVars[$modelpl][0]['OrgIdentity']['PrimaryName']['id'])) {
        $this->set('vv_bc_name', generateCn($this->viewVars[$modelpl][0]['OrgIdentity']['PrimaryName']));
      } elseif($this->action == 'add') {
        // We need to manually pull a name for the breadcrumbs
        if(!empty($pid['codeptid'])) {
          $this->set('vv_bc_name', $model->CoDepartment->field('name', array('CoDepartment.id' => $pid['codeptid'])));
        } elseif(!empty($pid['cogroupid'])) {
          $this->set('vv_bc_name', $model->CoGroup->field('name', array('CoGroup.id' => $pid['cogroupid'])));
        } elseif(!empty($pid['copersonid'])) {
          $args = array();
          $args['conditions']['CoPerson.id'] = $pid['copersonid'];
          $args['contain'][] = 'PrimaryName';
          
          $p = $model->CoPerson->find('first', $args);
          
          $this->set('vv_bc_name', generateCn($p['PrimaryName']));
        } elseif(!empty($pid['copersonroleid'])) {
          $args = array();
          $args['conditions']['CoPersonRole.id'] = $pid['copersonroleid'];
          $args['contain']['CoPerson'] = 'PrimaryName';
          
          $p = $model->CoPersonRole->find('first', $args);
          
          // Set the bc name to the role's title
          if(!empty($p['CoPersonRole']['title'])) {
            $this->set('vv_bc_name', $p['CoPersonRole']['title']);
          } else {
            $this->set('vv_bc_name', _txt('ct.co_person_roles.1'));
          }
          
          // But also set a parent breadcrumb of the Person
          $this->set('vv_pbc_id', $p['CoPerson']['id']);
          $this->set('vv_pbc_name', generateCn($p['CoPerson']['PrimaryName']));
        } elseif(!empty($pid['organizationid'])) {
          $this->set('vv_bc_name', $model->Organization->field('name', array('Organization.id' => $pid['organizationid'])));
        } elseif(!empty($pid['orgidentityid'])) {
          $args = array();
          $args['conditions']['OrgIdentity.id'] = $pid['orgidentityid'];
          $args['contain'][] = 'PrimaryName';
          
          $p = $model->OrgIdentity->find('first', $args);
          
          $this->set('vv_bc_name', generateCn($p['PrimaryName']));
        }
      } else {
        // Default to using the model label
        $this->set('vv_bc_name', _txt('ct.'.$modelpl.'.1')); 
      }
    }
    
    parent::beforeRender();


    if(!$this->request->is('restful')) {
      $view_var_keys = array_keys(Hash::flatten($this->viewVars));
      // Find if any Source var is present
      $re_source = '/.*\.?(Source\w+)\..*/m';
      $has_source = (boolean)preg_grep ($re_source, $view_var_keys);
      if (!empty($pid['orgidentityid'])
          || ( !empty($pid['copersonid']
               && $has_source) )
      ) {
        // Org identities use the default model types, and self service does not apply

        $this->set('vv_available_types', $model->defaultTypes('type'));
      } else {
        // When attached to a CO Person or Role, figure out the available extended
        // types and then filter for self service permissions

        $availableTypes = $model->types($this->cur_co['Co']['id'], 'type');

        if (!empty($this->viewVars['permissions']['selfsvc'])
          && !$this->Role->isCoOrCouAdmin(
            $this->Session->read('Auth.User.co_person_id'),
            $this->cur_co['Co']['id']
          )) {
          // For models supporting self service permissions, adjust the available types
          // in accordance with the configuration (but not if self is an admin)

          foreach (array_keys($availableTypes) as $k) {
            // We use edit for the permission even if we're adding or viewing because
            // add has different semantics for calculatePermission (whether or not the person
            // can add a new item).
            if (!$this->Co->CoSelfServicePermission->calculatePermission(
              $this->cur_co['Co']['id'],
              $req,
              'edit',
              $k
            )) {
              unset($availableTypes[$k]);
            }
          }
        }

        $this->set('vv_available_types', $availableTypes);
      }
    }
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
        
        $perms = $model->validator()->getfield('type')->getRule('content')->rule[1]['default'];
        
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
      $model->validator()->getfield('type')->getRule('content')->rule[1]['default'] = $perms;
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

    $actorCoPersonId = $this->request->is('restful') ? null : $this->Session->read('Auth.User.co_person_id');
    $actorApiUserId = $this->request->is('restful') ? $this->Auth->User('id') : null;

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
                                                     $actorCoPersonId,
                                                     ActionEnum::OrgIdEditedManual,
                                                     $cstr,
                                                     null, null, null,
                                                     $actorApiUserId);
        } elseif(!empty($newdata[$req]['co_group_id'])) {
          $model->CoGroup->HistoryRecord->record(null,
                                                 null,
                                                 null,
                                                 $actorCoPersonId,
                                                 ActionEnum::CoGroupEdited,
                                                 $cstr,
                                                 $newdata[$req]['co_group_id'],
                                                 null, null,
                                                 $actorApiUserId);
        } elseif(!empty($newdata[$req]['co_person_role_id'])) {
          // Map CO Person Role to CO Person
          $copid = $model->CoPersonRole->field('co_person_id', array('CoPersonRole.id' => $newdata[$req]['co_person_role_id']));
          
          $model->CoPersonRole->HistoryRecord->record($copid,
                                                      $newdata[$req]['co_person_role_id'],
                                                      null,
                                                      $actorCoPersonId,
                                                      ActionEnum::CoPersonEditedManual,
                                                      $cstr,
                                                      null, null, null,
                                                      $actorApiUserId);
        } elseif(!empty($newdata[$req]['co_person_id'])) {
          $model->CoPerson->HistoryRecord->record($newdata[$req]['co_person_id'],
                                                  null,
                                                  null,
                                                  $actorCoPersonId,
                                                  ActionEnum::CoPersonEditedManual,
                                                  $cstr,
                                                  null, null, null,
                                                  $actorApiUserId);
        }
        break;
      case 'delete':
        if(!empty($olddata[$req]['org_identity_id'])) {
          $model->OrgIdentity->HistoryRecord->record(null,
                                                     null,
                                                     $olddata[$req]['org_identity_id'],
                                                     $actorCoPersonId,
                                                     ActionEnum::OrgIdEditedManual,
                                                     $cstr,
                                                     null, null, null,
                                                     $actorApiUserId);
        } elseif(!empty($olddata[$req]['co_group_id'])) {
          $model->CoGroup->HistoryRecord->record(null,
                                                 null,
                                                 null,
                                                 $actorCoPersonId,
                                                 ActionEnum::CoGroupEdited,
                                                 $cstr,
                                                 $olddata[$req]['co_group_id'],
                                                 null, null,
                                                 $actorApiUserId);
        } elseif(!empty($olddata[$req]['co_person_role_id'])) {
          // Map CO Person Role to CO Person
          $copid = $model->CoPersonRole->field('co_person_id', array('CoPersonRole.id' => $olddata[$req]['co_person_role_id']));
          
          $model->CoPersonRole->HistoryRecord->record($copid,
                                                      $olddata[$req]['co_person_role_id'],
                                                      null,
                                                      $actorCoPersonId,
                                                      ActionEnum::CoPersonEditedManual,
                                                      $cstr,
                                                      null, null, null,
                                                      $actorApiUserId);
        } elseif(!empty($olddata[$req]['co_person_id'])) {
          $model->CoPerson->HistoryRecord->record($olddata[$req]['co_person_id'],
                                                  null,
                                                  null,
                                                  $actorCoPersonId,
                                                  ActionEnum::CoPersonEditedManual,
                                                  $cstr,
                                                  null, null, null,
                                                  $actorApiUserId);
        }
        break;
    }
    
    return true;
  }
}
