<?php
/**
 * COmanage Registry COU Controller
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
 * @since         COmanage Registry v0.2
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::uses("StandardController", "Controller");

class CousController extends StandardController {
  // Class name, used by Cake
  public $name = "Cous";
  
  // Establish pagination parameters for HTML views
  public $paginate = array(
    'limit' => 25,
    'order' => array(
      'Cou.name' => 'asc'
    )
  );
  
  // This controller needs a CO to be set
  public $requires_co = true;
  
  public $delete_contains = array(
    'ChildCou'
  );

  public $edit_contains = array(
    'ParentCou'
  );
  
  public $view_contains = array(
    'ParentCou'
  );
  
  /**
   * Perform filtering of COU parent options for dropdown.
   * - postcondition: parent_options set
   *
   * @since  COmanage Registry v0.3
   */
 
  function beforeRender() {
    // This loop is concerned with computing the options for parents 
    // to display for a dropdown menu or similar for the GUI when the 
    // user is editing or adding a COU.
    //
    // REST calls do not need to compute options for parents.
    if(!$this->request->is('restful')) {
      if($this->action == 'edit' || $this->action == 'add') {

      switch ($this->action) {
        case 'edit':
          $couId = $this->request->data['Cou']['id'];
          $coId  = $this->request->data['Cou']['co_id'];
          break;

        case 'add':
          $couId = null;
          $coId = $this->cur_co['Co']['id'];
          break;
      }

      $options = $this->Cou->potentialParents($couId, $coId);
      $this->set('parent_options', $options);
      }
    }

    // XXX This block should execute before its parent. The parent needs the $vv_cou_list
    if(!$this->request->is('restful')
       && $this->action == 'index') {
      // Get the full list of COUs
      $cous_all = $this->Cou->allCous($this->cur_co["Co"]["id"]);
      asort($cous_all, SORT_STRING);
      // `Any` option will return all COUs with a parent
      // `None` option will return all COUs with parent equal to null
      $vv_cou_list[_txt('op.select.opt.any')] = _txt('op.select.opt.any');
      $vv_cou_list[_txt('op.select.opt.none')] = _txt('op.select.opt.none');
      $vv_cou_list[_txt('fd.cou.list')] = $cous_all;
      $this->set('vv_cou_list', $vv_cou_list);
    }
    
    parent::beforeRender();
  }

  /**
   * Search Block fields configuration
   *
   * @since  COmanage Registry v4.0.0
   */

  public function searchConfig($action) {
    if($action == 'index') {                   // Index
      return array(
        'search.couName' => array(
          'label' => _txt('fd.name'),
          'type'  => 'text',
        ),
        'search.couDesc' => array(
          'type'    => 'text',
          'label'   => _txt('fd.description'),
        ),
        'search.parentCou' => array(
          'type'    => 'select',
          'label'   => _txt('fd.parent'),
          'empty'   => _txt('op.select.all'),
          'options' => $this->viewVars['vv_cou_list'],
        ),
      );
    }
  }


  /**
   * Perform any dependency checks required prior to a delete operation.
   * This method is intended to be overridden by model-specific controllers.
   * - postcondition: Session flash message updated (HTML) or HTTP status returned (REST)
   *
   * @since  COmanage Registry v0.2
   * @param  Array Current data
   * @return boolean true if dependency checks succeed, false otherwise.
   */
  
  function checkDeleteDependencies($curdata) {
    $couppl = $this->Cou->CoPersonRole->findAllByCouId($curdata['Cou']['id']);
    
    if(!empty($couppl)) {
      // A COU can't be removed if anyone is still a member of it.
      
      if($this->request->is('restful')) {
        $this->Api->restResultHeader(403, "CoPersonRole Exists");
      } else {
        $this->Flash->set(_txt('er.cou.copr', array($curdata['Cou']['name'])), array('key' => 'error'));
      }
      
      return false;
    }
    
    // A COU can't be removed if it has children.
    
    $childCous = $curdata['ChildCou'];
    
    if(!empty($childCous)) {
      if($this->request->is('restful')) {
        $this->Api->restResultHeader(403, "Child COU Exists");
      } else {
        $this->Flash->set(_txt('er.cou.child', array(filter_var($curdata['Cou']['name'],FILTER_SANITIZE_SPECIAL_CHARS))), array('key' => 'error'));
      }
      
      return false;
    }

    return true;
  }

  /**
   * Perform any dependency checks required prior to a write (add/edit) operation.
   * This method is intended to be overridden by model-specific controllers.
   * - postcondition: Session flash message updated (HTML) or HTTP status returned (REST)
   *
   * @since  COmanage Registry v0.3
   * @param  Array Request data
   * @param  Array Current data
   * @return boolean true if dependency checks succeed, false otherwise.
   */
  
  function checkWriteDependencies($reqdata, $curdata = null) {
    if(!isset($curdata)
       || ($curdata['Cou']['name'] != $reqdata['Cou']['name'])) {
      // Make sure name doesn't exist within this CO
      $args['conditions']['Cou.name'] = $reqdata['Cou']['name'];
      $args['conditions']['Cou.co_id'] = $reqdata['Cou']['co_id'];
      
      $x = $this->Cou->find('all', $args);
      
      if(!empty($x)) {
        if($this->request->is('restful')) {
          $this->Api->restResultHeader(403, "Name In Use");
        } else {
          $this->Flash->set(_txt('er.cou.exists', array($reqdata['Cou']['name'])), array('key' => 'error')); 
        }
        
        return false;
      }
    }
    
    // Parent COU must be in same CO as child

    // Name of parent
    $parentCou = (!empty($reqdata['Cou']['parent_id']) 
                  ? $reqdata['Cou']['parent_id']
                  : "");

    if(isset($parentCou) && $parentCou != "") {
      if($this->action != 'add') {
        // Parent not found in CO
        if(!$this->Cou->isInCo($parentCou, $reqdata['Cou']['co_id'])) {
          if($this->request->is('restful')) {
            $this->Api->restResultHeader(403, "Wrong CO");
          } else {
            $this->Flash->set(_txt('er.cou.sameco', array($reqdata['Cou']['name'])), array('key' => 'error'));
          }
          
          return false;
        }
        
        // Check if parent would cause a loop
        if($this->Cou->isChildCou($reqdata['Cou']['id'], $parentCou)) {
          if($this->request->is('restful')) {
            $this->Api->restResultHeader(403, "Parent Would Create Cycle");
          } else {
            $this->Flash->set(_txt('er.cou.cycle', array($reqdata['CoGroupMember']['co_group_id'])), array('key' => 'error'));
          }
          
          return false;
        }
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
   * - postcondition: Session flash message updated (HTML) or HTTP status returned (REST)
   *
   * @since  COmanage Registry v0.2
   * @param  Array Request data
   * @param  Array Current data
   * @param  Array Original request data (unmodified by callbacks)
   * @return boolean true if dependency checks succeed, false otherwise.
   */
  
  function checkWriteFollowups($reqdata, $curdata = null, $origdata = null) {
    if($this->action == 'edit') {
      if(!empty($reqdata['Cou']['name'])
         && !empty($curdata['Cou']['name'])
         && $reqdata['Cou']['name'] != $curdata['Cou']['name']) {
        // The COU has been renamed, so update the relevant group names
        
        $this->Cou->Co->CoGroup->addDefaults($reqdata['Cou']['co_id'], $this->Cou->id, true);
      }
    }
    
    return true;
  }

  /**
   * Determine the conditions for pagination of the index view, when rendered via the UI.
   *
   * @since  COmanage Registry v4.0.0
   * @return Array An array suitable for use in $this->paginate
   */

  public function paginationConditions() {
    $ret = array();

    // COU Name
    $cou_name = isset($this->request->params['named']['search.couName']) ? $this->request->params['named']['search.couName'] : "";
    // COU Description
    $cou_description = isset($this->request->params['named']['search.couDesc']) ? strtolower($this->request->params['named']['search.couDesc']) : "";
    // Parent COU
    $parent_couid = isset($this->request->params['named']['search.parentCou']) ? $this->request->params['named']['search.parentCou'] : "";

    $ret['conditions']['Cou.co_id'] = $this->cur_co['Co']['id'];
    if(!empty($cou_name)) {
      $ret['conditions']['Cou.name LIKE'] = "%$cou_name%";
    }
    if(!empty($cou_description)) {
      $ret['conditions']['LOWER(Cou.description) LIKE'] = "%{$cou_description}%";
    }
    if(!empty($parent_couid)) {
      if($parent_couid == _txt('op.select.opt.any')) {
        $ret['conditions'][] = 'Cou.parent_id IS NOT NULL';
      } elseif($parent_couid == _txt('op.select.opt.none')) {
        $ret['conditions'][] = 'Cou.parent_id IS NULL';
      } else {
        $ret['conditions']['Cou.parent_id'] = $parent_couid;
      }
    }
    if(isset($this->view_contains)) {
      $ret['contain'] = $this->view_contains;
    }

    return $ret;
  }

  /**
   * Authorization for this Controller, called by Auth component
   * - precondition: Session.Auth holds data used for authz decisions
   * - postcondition: $permissions set with calculated permissions
   *
   * @since  COmanage Registry v0.2
   * @return Array Permissions
   */
  
  function isAuthorized() {
    $roles = $this->Role->calculateCMRoles();             // What was authenticated
    
    // Construct the permission set for this user, which will also be passed to the view.
    $p = array();
    
    // Determine what operations this user can perform
    
    // Add a new COU?
    $p['add'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // Delete an existing COU?
    $p['delete'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // Edit an existing COU?
    $p['edit'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // View all existing COUs?
    $p['index'] = ($roles['cmadmin'] || $roles['coadmin']);
    $p['search'] = $p['index'];

    // View an existing COU?
    $p['view'] = ($roles['cmadmin'] || $roles['coadmin']);

    $this->set('permissions', $p);
    return $p[$this->action];
  }
}
