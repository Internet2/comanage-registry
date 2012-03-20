<?php
/**
 * COmanage Registry CO Enrollment Attributes Controller
 *
 * Copyright (C) 2011-12 University Corporation for Advanced Internet Development, Inc.
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
 * @copyright     Copyright (C) 2011-12 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.3
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */

App::uses("StandardController", "Controller");
  
class CoEnrollmentAttributesController extends StandardController {
  // Class name, used by Cake
  public $name = "CoEnrollmentAttributes";
  
  // Establish pagination parameters for HTML views
  public $paginate = array(
    'limit' => 25,
    'order' => array(
      'CoEnrollmentAttribute.attribute' => 'asc'
    )
  );

  /**
   * Add an Enrollment Attribute.
   * - precondition: Model specific attributes in $this->request->data (optional)
   * - postcondition: On success, new Object created
   * - postcondition: Session flash message updated (HTML) or HTTP status returned (REST)
   * - postcondition: $<object>_id or $invalid_fields set (REST)
   *
   * @since  COmanage Registry v0.3
   */
  
  function add() {
    if(!empty($this->request->data) &&
       (!isset($this->request->data['CoEnrollmentAttribute']['ordr'])
        || $this->request->data['CoEnrollmentAttribute']['ordr'] == '')) {
      $args['fields'][] = "MAX(ordr) as m";
      $args['conditions']['CoEnrollmentAttribute.co_enrollment_flow_id'] = Sanitize::paranoid($this->request->data['CoEnrollmentAttribute']['co_enrollment_flow_id']);
      $args['order'][] = "m";
      
      $o = $this->CoEnrollmentAttribute->find('first', $args);
      $n = 1;
      
      if(!empty($o)) {
        $n = $o[0]['m'] + 1;
      }
      
      if(!empty($o))
        $this->request->data['CoEnrollmentAttribute']['ordr'] = $n;
    }
    
    parent::add();
  }

  /**
   * Callback before other controller methods are invoked or views are rendered.
   * - postcondition: Auth component is configured 
   *
   * @since  COmanage Registry v0.3
   */
  
  function beforeFilter() {
    parent::beforeFilter();

    // Strictly speaking, this controller doesn't require a CO except to redirect/render views.
    // Figure out the CO ID associated with the current enrollment flow. We'll specifically
    // not set $this->cur_co since it will break things like pagination setup.
    
    $coefid = -1;
    
    if(isset($this->request->params['named']['coef']))
      $coefid = $this->request->params['named']['coef'];
    elseif(isset($this->request->data))
      $coefid = $this->request->data['CoEnrollmentAttribute']['co_enrollment_flow_id'];
    
    $this->CoEnrollmentAttribute->CoEnrollmentFlow->id = $coefid;
    $coid = $this->CoEnrollmentAttribute->CoEnrollmentFlow->field('co_id');

    if(!empty($coid))
    {
      $this->set("coid", $coid);
      
      // Assemble the set of available attributes for the view to render
      
      $this->set('available_attributes', $this->CoEnrollmentAttribute->availableAttributes($coid));
    }
  }
  
  /**
   * Authorization for this Controller, called by Auth component
   * - precondition: Session.Auth holds data used for authz decisions
   * - postcondition: $permissions set with calculated permissions
   *
   * @since  COmanage Registry v0.3
   * @return Array Permissions
   */
  
  function isAuthorized() {
    $cmr = $this->calculateCMRoles();
    
    // Construct the permission set for this user, which will also be passed to the view.
    $p = array();
    
    // Determine what operations this user can perform
    
    // Add a new CO Enrollment Attribute?
    $p['add'] = ($cmr['cmadmin'] || $cmr['coadmin']);
    
    // Delete an existing CO Enrollment Attribute?
    $p['delete'] = ($cmr['cmadmin'] || $cmr['coadmin']);
    
    // Edit an existing CO Enrollment Attribute?
    $p['edit'] = ($cmr['cmadmin'] || $cmr['coadmin']);
    
    // View all existing CO Enrollment Attributes?
    $p['index'] = ($cmr['cmadmin'] || $cmr['coadmin']);
    
    // View an existing CO Enrollment Attributes?
    $p['view'] = ($cmr['cmadmin'] || $cmr['coadmin']);

    $this->set('permissions', $p);
    return($p[$this->action]);
  }

  /**
   * Determine the conditions for pagination of the index view, when rendered via the UI.
   *
   * @since  COmanage Registry v0.3
   * @return Array An array suitable for use in $this->paginate
   */
  
  function paginationConditions() {
    // Only retrieve attributes in the current enrollment flow
    
    return(array(
      'CoEnrollmentAttribute.co_enrollment_flow_id' => $this->request->params['named']['coef']
    ));
  }
  
  /**
   * Perform a redirect back to the controller's default view.
   * - postcondition: Redirect generated
   *
   * @since  COmanage Registry v0.3
   */
  
  function performRedirect() {
    // Append the enrollment flow ID to the redirect
    
    if(isset($this->request->data['CoEnrollmentAttribute']['co_enrollment_flow_id']))
      $coefid = $this->request->data['CoEnrollmentAttribute']['co_enrollment_flow_id'];
    elseif(isset($this->request->params['named']['coef']))
      $coefid = Sanitize::html($this->request->params['named']['coef']);
    
    $this->redirect(array('controller' => 'co_enrollment_attributes',
                          'action' => 'index',
                          'coef' => $coefid));
  }
}
