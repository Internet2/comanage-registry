<?php
/**
 * COmanage Registry Identifier Enroller Identifiers Controller
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
 * @since         COmanage Registry v4.0.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::uses("StandardController", "Controller");

class IdentifierEnrollerIdentifiersController extends StandardController {
  // Class name, used by Cake
  public $name = "IdentifierEnrollerIdentifiers";
  
  // Establish pagination parameters for HTML views
  public $paginate = array(
    'limit' => 25,
    'order' => array(
      'IdentifierEnrollerIdentifier.ordr' => 'asc',
      'IdentifierEnrollerIdentifier.label' => 'asc'
    )
  );
  
  // This controller needs a CO to be set
  public $requires_co = true;

  // Edit and view need Name for rendering view
  public $edit_contains = array(
  );

  public $view_contains = array(
  );
  
  // We need to track the Identifier Enroller ID under certain circumstances to enable performRedirect
  private $ieid = null;
  
  /**
   * Callback before other controller methods are invoked or views are rendered.
   * - postcondition: $plugins set
   *
   * @since  COmanage Registry v4.0.0
   * @throws InvalidArgumentException
   */
  
  function beforeFilter() {
    parent::beforeFilter();
    
    // Figure out our Unix Cluster ID. We do this here rather than in beforeRender
    // because we need $ucid on redirect after save.

    $ieid = null;

    if($this->action == 'add' || $this->action == 'delete' || $this->action == 'index') {
      // Accept ieid from the url or the form
      // For delete we should really grab it via $id before deleting the object,
      // but we only use it to redirect back to the index view

      if(!empty($this->request->params['named']['ieid'])) {
        $ieid = filter_var($this->request->params['named']['ieid'],FILTER_SANITIZE_SPECIAL_CHARS);
      } elseif(!empty($this->request->data['IdentifierEnrollerIdentifier']['identifier_enroller_id'])) {
        $ieid = filter_var($this->request->data['IdentifierEnrollerIdentifier']['identifier_enroller_id'],FILTER_SANITIZE_SPECIAL_CHARS);
      }
    } elseif(!empty($this->request->params['pass'][0])) {
      // Map the Identifier Enroller from the requested object

      $ieid = $this->IdentifierEnrollerIdentifier->field('identifier_enroller_id',
                                                         array('id' => $this->request->params['pass'][0]));
    }
    
    if(!empty($ieid)) {
      $args = array();
      $args['conditions']['IdentifierEnroller.id'] = $ieid;
      $args['contain'] = array('CoEnrollmentFlowWedge' => array('CoEnrollmentFlow'));
      
      $enroller = $this->IdentifierEnrollerIdentifier->IdentifierEnroller->find('first', $args);
      
      if($enroller) {
        $this->set('vv_identifier_enroller', $enroller);
        $this->ieid = $enroller['IdentifierEnroller']['id'];
      }
    }
  }
  
  /**
   * Callback after controller methods are invoked but before views are rendered.
   *
   * @since  COmanage Registry v4.0.0
   */

  function beforeRender() {
    parent::beforeRender();
    
    if(!$this->request->is('restful')) {
      // Pull the available identifier types
      
      $this->set('vv_identifier_types', $this->Co->CoPerson->Identifier->types($this->cur_co['Co']['id'], 'type'));
    }
  }
  
  /**
   * Determine the CO ID based on some attribute of the request.
   * This method is intended to be overridden by model-specific controllers.
   *
   * @since  COmanage Registry v4.0.0
   * @return Integer CO ID, or null if not implemented or not applicable.
   * @throws InvalidArgumentException
   */

  protected function calculateImpliedCoId($data = null) {
    // If an identifier enroller is specified, use it to get to the CO ID

    $ieid = null;

    if(in_array($this->action, array('add', 'index'))
       && !empty($this->params->named['ieid'])) {
      $ieid = $this->params->named['ieid'];
    } elseif(!empty($this->request->data['IdentifierEnroller']['identifier_enroller_id'])) {
      $ieid = $this->request->data['IdentifierEnroller']['identifier_enroller_id'];
    }

    if($ieid) {
      // Map Identifier Enroller to CO via Wedge and Enrollment Flow
      
      $wid = $this->IdentifierEnrollerIdentifier->IdentifierEnroller->field('co_enrollment_flow_wedge_id', array('IdentifierEnroller.id' => $ieid));
      
      if(!$wid) {
        throw new InvalidArgumentException(_txt('er.notfound', array(_txt('ct.identifier_enrollers.1'), $ieid)));
      }
      
      $efid = $this->IdentifierEnrollerIdentifier->IdentifierEnroller->CoEnrollmentFlowWedge->field('co_enrollment_flow_id', array('CoEnrollmentFlowWedge.id' => $wid));
      
      if(!$efid) {
        throw new InvalidArgumentException(_txt('er.notfound', array(_txt('ct.co_enrollment_flow_wedges.1'), $wid)));
      }

      $coId = $this->IdentifierEnrollerIdentifier->IdentifierEnroller->CoEnrollmentFlowWedge->CoEnrollmentFlow->field('co_id', array('CoEnrollmentFlow.id' => $efid));
      
      if(!$coId) {
        throw new InvalidArgumentException(_txt('er.notfound', array(_txt('ct.co_enrollment_flows.1'), $efid)));
      }
      
      return $coId;
    }

    // Or try the default behavior
    return parent::calculateImpliedCoId();
  }
  
  /**
   * Authorization for this Controller, called by Auth component
   * - precondition: Session.Auth holds data used for authz decisions
   * - postcondition: $permissions set with calculated permissions
   *
   * @since  COmanage Registry v4.0.0
   * @return Array Permissions
   */
  
  function isAuthorized() {
    $roles = $this->Role->calculateCMRoles();             // What was authenticated
    
    // Construct the permission set for this user, which will also be passed to the view.
    $p = array();
    
    // Determine what operations this user can perform
    
    // Add a new Identifier Enroller Identifier?
    $p['add'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // Delete an existing Identifier Enroller Identifier?
    $p['delete'] = ($roles['cmadmin'] || $roles['coadmin']);

    // Edit an existing Identifier Enroller Identifier?
    $p['edit'] = ($roles['cmadmin'] || $roles['coadmin']);

    // View all existing Identifier Enroller Identifiers?
    $p['index'] = ($roles['cmadmin'] || $roles['coadmin']);

    // View an existing Identifier Enroller Identifier?
    $p['view'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    $this->set('permissions', $p);
    return($p[$this->action]);
  }
  
  /**
   * Determine the conditions for pagination of the index view, when rendered via the UI.
   *
   * @since  COmanage Registry v4.0.0
   * @return Array An array suitable for use in $this->paginate
   */

  function paginationConditions() {
    // Only retrieve attributes in the current identifier enroller

    $ret = array();

    $ret['conditions']['IdentifierEnrollerIdentifier.identifier_enroller_id'] = $this->request->params['named']['ieid'];

    return $ret;
  }
  
  /**
   * Perform a redirect back to the controller's default view.
   * - postcondition: Redirect generated
   *
   * @since  COmanage Registry v4.0.0
   */
  
  function performRedirect() {
    // Figure out where to redirect back to based on how we were called
    
    if(isset($this->ieid)) {
      $params = array(
        'plugin'     => 'identifier_enroller',
        'controller' => 'identifier_enroller_identifiers',
        'action'     => 'index',
        'ieid'       => $this->ieid
      );
    } else {
      // A perhaps not ideal default, but we shouldn't get here
      $params = array(
        'plugin'     => null,
        'controller' => 'co_enrollment_flows',
        'action'     => 'index',
        'co'         => $this->cur_co['Co']['id']
      );
    }
    
    $this->redirect($params);
  }
}
