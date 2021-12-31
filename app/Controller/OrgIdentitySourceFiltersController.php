<?php
/**
 * COmanage Registry Org Identity Source Data Filters Controller
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
 * @link          https://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v4.1.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::uses("StandardController", "Controller");

class OrgIdentitySourceFiltersController extends StandardController {
  // Class name, used by Cake
  public $name = "OrgIdentitySourceFilters";
  
  // Establish pagination parameters for HTML views
  public $paginate = array(
    'limit' => 25,
    'order' => array(
      'ordr' => 'asc'
    )
  );
  
  // This controller needs a CO to be set
  public $requires_co = true;

  // We want to contain the plugins, but we don't know what they are yet.
  // We'll add them in beforeFilter(). (Don't use recursive here or we'll pull
  // all affiliated OIS records, which would be bad.)
  public $view_contains = array(
    'DataFilter'
  );
  
  public $edit_contains = array(
    'DataFilter'
  );
  
  /**
   * Callback after controller methods are invoked but before views are rendered.
   * - precondition: Request Handler component has set $this->request
   *
   * @since  COmanage Registry v4.1.0
   */

  public function beforeRender() {
    parent::beforeRender();
    
    // Figure out our Org Identity Source ID
    
    $oisid = null;

    if($this->action == 'add' || $this->action == 'index' || $this->action == 'order') {
      // Accept oisid from the url or the form
      
      if(!empty($this->request->params['named']['oisid'])) {
        $oisid = filter_var($this->request->params['named']['oisid'],FILTER_SANITIZE_SPECIAL_CHARS);
      } elseif(!empty($this->request->data['OrgIdentitySourceFilter']['org_identity_source_id'])) {
        $oisid = $this->request->data['OrgIdentitySourceFilter']['org_identity_source_id'];
      }
    } elseif(($this->action == 'edit' || $this->action == 'delete')
             && !empty($this->request->params['pass'][0])) {
      // Map the org identity source from the requested object

      $coptid = $this->OrgIdentitySourceFilter->field('org_identity_source_id',
                                                      array('id' => $this->request->params['pass'][0]));
    }

    $oisname = $this->OrgIdentitySourceFilter->OrgIdentitySource->field('description', array('OrgIdentitySource.id' => $oisid));

    // Override page title
    $this->set('title_for_layout', _txt('ct.org_identity_source_filters.pl') . " (" . $oisname . ")");
    $this->set('vv_ois_name', $oisname);
    $this->set('vv_ois_id', $oisid);
    
    // Pull the set of available data filters.
    
    $args = array();
    $args['conditions']['DataFilter.co_id'] = $this->cur_co['Co']['id'];
    $args['conditions']['DataFilter.status'] = SuspendableStatusEnum::Active;
    $args['conditions']['DataFilter.context'] = DataFilterContextEnum::OrgIdentitySource;
    $args['fields'] = array('id', 'description');
    $args['order'] = 'description';
    $args['contain'] = false;
    
    $this->set('vv_available_filters', $this->OrgIdentitySourceFilter->DataFilter->find('list', $args));
  }
  
  /**
   * Determine the CO ID based on some attribute of the request.
   * This method is intended to be overridden by model-specific controllers.
   *
   * @since  COmanage Registry v4.1.0
   * @return Integer CO ID, or null if not implemented or not applicable.
   * @throws InvalidArgumentException
   */
  
  protected function calculateImpliedCoId($data = null) {
    // If a provisioning target is specified, use it to get to the CO ID
    
    $oisid = null;
    
    if(in_array($this->action, array('add', 'index', 'order', 'reorder'))
       && !empty($this->params->named['oisid'])) {
      $oisid = $this->params->named['oisid'];
    } elseif(!empty($this->request->data['OrgIdentitySourceFilter']['org_identity_source_id'])) {
      $oisid = $this->request->data['OrgIdentitySourceFilter']['org_identity_source_id'];
    }
    
    if($oisid) {
      // Map Org Identity Source to CO

      $coId = $this->OrgIdentitySourceFilter->OrgIdentitySource->field('co_id',
                                                                       array('id' => $oisid));

      if($coId) {
        return $coId;
      } else {
        throw new InvalidArgumentException(_txt('er.notfound', array(_txt('ct.org_identity_source.1'), $coef)));
      }
    }
    
    return parent::calculateImpliedCoId();
  }
  
  /**
   * Authorization for this Controller, called by Auth component
   * - precondition: Session.Auth holds data used for authz decisions
   * - postcondition: $permissions set with calculated permissions
   *
   * @since  COmanage Registry v4.1.0
   * @return Array Permissions
   */
  
  function isAuthorized() {
    $roles = $this->Role->calculateCMRoles();
    
    // Construct the permission set for this user, which will also be passed to the view.
    $p = array();

    // Add a new Org Identity Source Filter?
    $p['add'] = $roles['cmadmin'] || $roles['coadmin'];
    
    // Delete an existing Org Identity Source Filter?
    $p['delete'] = $roles['cmadmin'] || $roles['coadmin'];
    
    // Edit an existing Org Identity Source Filter?
    $p['edit'] = $roles['cmadmin'] || $roles['coadmin'];
    
    // View all existing Org Identity Source Filters?
    $p['index'] = $roles['cmadmin'] || $roles['coadmin'];
    
    // Edit an existing Org Identity Source Filter's order?
    $p['order'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // Modify ordering for display via AJAX 
    $p['reorder'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // View an existing Org Identity Source Filter?
    $p['view'] = $roles['cmadmin'] || $roles['coadmin'];
    
    $this->set('permissions', $p);
    return $p[$this->action];
  }

  /**
   * Determine the conditions for pagination of the index view, when rendered via the UI.
   *
   * @since  COmanage Registry v4.1.0
   * @return Array An array suitable for use in $this->paginate
   */

  function paginationConditions() {
    // Only retrieve filters for the current org identity source

    $ret = array();

    $ret['conditions']['OrgIdentitySourceFilter.org_identity_source_id'] = $this->request->params['named']['oisid'];

    return $ret;
  }
    
  /**
   * Perform a redirect back to the controller's default view.
   * - postcondition: Redirect generated
   *
   * @since  COmanage Registry v4.1.0
   */
  
  function performRedirect() {
    // Append the org identity source ID to the redirect

    if(isset($this->request->data['OrgIdentitySourceFilter']['org_identity_source_id']))
      $oisid = $this->request->data['OrgIdentitySourceFilter']['org_identity_source_id'];
    elseif(isset($this->request->params['named']['oisid']))
      $oisid = filter_var($this->request->params['named']['oisid'],FILTER_SANITIZE_SPECIAL_CHARS);

    $this->redirect(array('controller' => 'org_identity_source_filters',
                          'action' => 'index',
                          'oisid' => $oisid));
  }
}
