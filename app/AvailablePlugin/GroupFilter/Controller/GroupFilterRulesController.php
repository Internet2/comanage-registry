<?php
/**
 * COmanage Registry Group Data Filter Rules Controller
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
 * @package       registry-plugin
 * @since         COmanage Registry v3.3.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::uses("StandardController", "Controller");

class GroupFilterRulesController extends StandardController {
  // Class name, used by Cake
  public $name = "GroupFilterRules";
  
  public $requires_co = true;
  
  // Establish pagination parameters for HTML views

  public $paginate = array(
    'limit' => 25,
    'order' => array(
      'ordr' => 'asc'
    )
  );
  
  /**
   * Callback before views are rendered.
   *
   * @since  COmanage Registry v3.3.0
   */

  function beforeRender() {
    parent::beforeRender();
    
    $gfid = null;
    
    if($this->action == 'add' || $this->action == 'index' || $this->action == 'order') {
      // Accept groupfilter ID from the url
      
      if(!empty($this->request->params['named']['groupfilter'])) {
        $gfid = filter_var($this->request->params['named']['groupfilter'],FILTER_SANITIZE_SPECIAL_CHARS);
      }
    } elseif($this->action == 'edit' || $this->action == 'view') {
      if(!empty($this->request->params['pass'][0])) {
        $gfid = $this->GroupFilterRule->field('group_filter_id', array('GroupFilterRule.id' => $this->request->params['pass'][0]));
      }
    }
    
    if($gfid) {
      // Look up the record, including the parent Data Filter to get the description
      
      $args = array();
      $args['conditions']['GroupFilter.id'] = $gfid;
      $args['contain'] = false;
      // We can't use contain(DataFilter) probably because we're not setting up the
      // dynamic relations correctly
//      $args['contain'] = array('DataFilter');
      
      $groupfilter = $this->GroupFilterRule->GroupFilter->find('first', $args);
      
      if(!empty($groupfilter)) {
        $this->set('vv_groupfilter', $groupfilter['GroupFilter']);

        // Relations aren't autobinding DataFilter...
        $DataFilter = ClassRegistry::init('DataFilter');
        
        $args = array();
        $args['conditions']['DataFilter.id'] = $groupfilter['GroupFilter']['data_filter_id'];
        $args['contain'] = false;
        
        $datafilter = $DataFilter->find('first', $args);

        if(!empty($datafilter)) {
          $this->set('vv_datafilter', $datafilter['DataFilter']);
        }
      }
    }
  }
  
  /**
   * Determine the CO ID based on some attribute of the request.
   * This method is intended to be overridden by model-specific controllers.
   *
   * @since  COmanage Registry v3.3.0
   * @return Integer CO ID, or null if not implemented or not applicable.
   * @throws InvalidArgumentException
   */

  protected function calculateImpliedCoId($data = null) {
    // If a group filter is specified, use it to get to the CO ID

    $gf = null;

    if(in_array($this->action, array('add', 'index', 'order', 'reorder'))
       && !empty($this->params->named['groupfilter'])) {
      $gf = $this->params->named['groupfilter'];
    } elseif(!empty($this->request->data['GroupFilter']['group_filter_id'])) {
      $gf = $this->request->data['GroupFilter']['group_filter_id'];
    }

    if($gf) {
      // Map Group Filter to Data Filter to CO... we could also do this as a join
      
      $dataFilterId = $this->GroupFilterRule->GroupFilter->field('data_filter_id', 
                                                                 array('GroupFilter.id' => $gf));
      
      // Relations aren't autobinding DataFilter...
      $DataFilter = ClassRegistry::init('DataFilter');
      
      $coId = $DataFilter->field('co_id', array('DataFilter.id' => $dataFilterId));
      
      if($coId) {
        return $coId;
      } else {
        throw new InvalidArgumentException(_txt('er.notfound', array(_txt('ct.group_filters.1'), $coef)));
      }
    }

    return parent::calculateImpliedCoId();
  }
  
  /**
   * Determine the conditions for pagination of the index view, when rendered via the UI.
   *
   * @since  COmanage Registry v3.3.0
   * @return Array An array suitable for use in $this->paginate
   */

  function paginationConditions() {
    // Only retrieve attributes in the current enrollment flow

    $ret = array();

    $ret['conditions']['GroupFilterRule.group_filter_id'] = $this->request->params['named']['groupfilter'];

    return $ret;
  }
  
  /**
   * Perform a redirect back to the controller's default view.
   * - postcondition: Redirect generated
   *
   * @since  COmanage Registry v3.3.0
   */

  function performRedirect() {
    // Append the enrollment flow ID to the redirect

    if(isset($this->request->data['GroupFilterRule']['group_filter_id']))
      $gfid = $this->request->data['GroupFilterRule']['group_filter_id'];
    elseif(isset($this->request->params['named']['groupfilter']))
      $gfid = filter_var($this->request->params['named']['groupfilter'],FILTER_SANITIZE_SPECIAL_CHARS);

    $this->redirect(array(
      'plugin' => 'group_filter',
      'controller' => 'group_filter_rules',
      'action' => 'index',
      'groupfilter' => $gfid)
    );
  }
  
  /**
   * Authorization for this Controller, called by Auth component
   * - precondition: Session.Auth holds data used for authz decisions
   * - postcondition: $permissions set with calculated permissions
   *
   * @since  COmanage Registry v3.3.0
   * @return Array Permissions
   */
  
  function isAuthorized() {
    $roles = $this->Role->calculateCMRoles();
    
    // Construct the permission set for this user, which will also be passed to the view.
    $p = array();
    
    // Add a new Group Filter Rule?
    $p['add'] = $roles['cmadmin'] || $roles['coadmin'];
    
    // Delete an existing Group Filter Rule?
    $p['delete'] = $roles['cmadmin'] || $roles['coadmin'];
    
    // Edit an existing Group Filter Rule?
    $p['edit'] = $roles['cmadmin'] || $roles['coadmin'];
    
    // View all existing Group Filter Rules?
    $p['index'] = $roles['cmadmin'] || $roles['coadmin'];
    
    // Edit an existing Group Filter Rule's order?
    $p['order'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // Modify ordering for display via AJAX 
    $p['reorder'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // View an existing Group Filter Rule?
    $p['view'] = $roles['cmadmin'] || $roles['coadmin'];
    
    $this->set('permissions', $p);
    return($p[$this->action]);
  }
}
