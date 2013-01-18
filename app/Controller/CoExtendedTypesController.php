<?php
/**
 * COmanage Registry CO Extended Types Controller
 *
 * Copyright (C) 2013 University Corporation for Advanced Internet Development, Inc.
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
 * @copyright     Copyright (C) 2013 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.6
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */

App::uses("StandardController", "Controller");

class CoExtendedTypesController extends StandardController {
  // Class name, used by Cake
  public $name = "CoExtendedTypes";
  
  // When using additional models, we must also specify our own
  public $uses = array('CoExtendedType', 'Identifier');
  
  // Establish pagination parameters for HTML views
  public $paginate = array(
    'limit' => 25,
    'order' => array(
      'name' => 'asc'
    )
  );
  
  // This controller needs a CO to be set
  public $requires_co = true;
  
  /**
   * Callback before other controller methods are invoked or views are rendered.
   * - postcondition: Redirect may be issued
   *
   * @since  COmanage Registry v0.6
   */
  
  function beforeFilter() {
    parent::beforeFilter();
    
    // If no attribute parameter is provided, and this is not index (which will figure it
    // out on it's own) redirect to index. We skip this entire check on POST, since the
    // model validation rules will handle everything.
    
    if(!$this->restful && $this->request->is('get')) {
      if(!isset($this->params['named']['attr'])) {
        if($this->action == 'index') {
          // Currently, we only support one attr so we can simply append it and redirect.
          // This will clearly need to change at some point. When it does, index.ctp should
          // not render anything after the select if $attr is not defined.
          // Relatedly, we may also need to override perform_redirect to set the right URL
          // after a POST operation is handled.
          $this->redirect(array('controller' => 'co_extended_types',
                                'action' => 'index',
                                'co' => $this->cur_co['Co']['id'],
                                'attr' => 'Identifier'));
        } else {
          // Redirect to index to get the attr parameter. (We shouldn't have gotten to /add
          // or whatever anyway without one, unless someone is manually munging URLs.)
          $this->redirect(array('controller' => 'co_extended_types',
                                'action' => 'index',
                                'co' => $this->cur_co['Co']['id']));
        }
      } else {
        // Make sure attr is valid.
        
        if(!array_key_exists(Sanitize::html($this->params['named']['attr']),
                             $this->CoExtendedType->supportedAttrs())) {
          $this->redirect(array('controller' => 'co_extended_types',
                                'action' => 'index',
                                'co' => $this->cur_co['Co']['id']));
        }
      }
      
      // Provide a list of supported attributes for the attribute select menu
      $this->set('supported_attrs', $this->CoExtendedType->supportedAttrs());
    }
  }
  
  /**
   * Perform any dependency checks required prior to a delete operation.
   * - postcondition: Session flash message updated (HTML) or HTTP status returned (REST)
   *
   * @since  COmanage Registry v0.6
   * @param  Array Current data
   * @return boolean true if dependency checks succeed, false otherwise.
   */
  
  function checkDeleteDependencies($curdata) {
    // Don't allow delete if there are any attributes still using this type
    
    // First check that there are no identifiers of this type attached to any CO People
    // within the CO.
    
    if($this->Identifier->typeInUse($curdata['CoExtendedType']['name'],
                                    $this->cur_co['Co']['id'])) {
      if($this->restful)
        $this->restResultHeader(403, "Type In Use");
      else
        $this->Session->setFlash(_txt('er.et.inuse', array($curdata['CoExtendedType']['name'])), '', array(), 'error');
      
      return false;
    }

// XXX implement this check
// enrollment flows with type of a defined attribute -- need to do this check on status change to Suspended
    
    return true;
  }
  
  /**
   * Perform any dependency checks required prior to a write (add/edit) operation.
   *
   * @since  COmanage Registry v0.6
   * @param  Array Request data
   * @param  Array Current data
   * @return boolean true if dependency checks succeed, false otherwise.
   */
  
  function checkWriteDependencies($reqdata, $curdata = null) {
    if($this->restful) {
      // Make sure the specified attribute is valid now, since we use it before we'll
      // get to a save
      
      $this->CoExtendedType->set($reqdata);
      
      if(!$this->CoExtendedType->validates(array('fieldList' => array('attribute')))) {
        $this->restResultHeader(400, "Invalid Fields");
        $this->set('invalid_fields', $this->CoExtendedType->invalidFields());
        
        return false;
      }
    }
    
    if($curdata == null) {
      // Only do this check on add() (ie: where there is no current data). We do this
      // check at checkWriteDependencies rather than checkWriteFollowups because at
      // follow up time there will be an extended type, so anyDefined will return true.
      
      if(!$this->CoExtendedType->anyDefined($this->cur_co['Co']['id'],
                                            $reqdata['CoExtendedType']['attribute'],
                                            false)) {
        // If there are no active extended types yet, copy in all the default types.
        // This simplifies extending attributes since we don't have to worry about
        // cleaning up any attributes that may have been defined already with the
        // default types. The downside is if a new default type is added in the future,
        // it won't automatically be available. That might really be a good thing, though.
        
        if(!$this->CoExtendedType->addDefault($this->cur_co['Co']['id'],
                                              $reqdata['CoExtendedType']['attribute'])) {
          if($this->restful)
            $this->restResultHeader(500, "Default Copy Error");
          else
            $this->Session->setFlash(_txt('er.et.default'), '', array(), 'error');
          
          return false;
        }
      }
    }
    
    if(!isset($curdata)
       || ($curdata['CoExtendedType']['name'] != $reqdata['CoExtendedType']['name'])) {
      // Make sure the name doesn't exist for the attribute for this CO
      $args['conditions']['CoExtendedType.name'] = $reqdata['CoExtendedType']['name'];
      $args['conditions']['CoExtendedType.co_id'] = $reqdata['CoExtendedType']['co_id'];
      $args['conditions']['CoExtendedType.attribute'] = $reqdata['CoExtendedType']['attribute'];
      
      $x = $this->CoExtendedType->find('count', $args);
      
      if($x > 0) {
        if($this->restful)
          $this->restResultHeader(403, "Name In Use");
        else
          $this->Session->setFlash(_txt('er.et.exists', array($reqdata['CoExtendedType']['name'])), '', array(), 'error');
          
        return false;
      }
    }
    
    return true;
  }
  
  /**
   * Obtain all Extended Types of the currently specified attribute.
   *
   * @since  COmanage Registry v0.6
   */

  public function index() {
    if(!$this->restful) {
      // Set some hints for the view before we invoke the standard behavior
      
      // Are there any extended types defined?
      $this->set('any_extended', $this->CoExtendedType->anyDefined($this->cur_co['Co']['id'],
                                                                   $this->request->params['named']['attr']));
    }
    
    parent::index();
  }
  
  /**
   * Authorization for this Controller, called by Auth component
   * - precondition: Session.Auth holds data used for authz decisions
   * - postcondition: $permissions set with calculated permissions
   *
   * @since  COmanage Registry v0.6
   * @return Array Permissions
   */
  
  function isAuthorized() {
    $roles = $this->Role->calculateCMRoles();
    
    // Construct the permission set for this user, which will also be passed to the view.
    $p = array();
    
    // Determine what operations this user can perform
    
    // Add a new Extended Type?
    $p['add'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // Delete an existing Extended Type?
    $p['delete'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // Edit an existing Extended Type?
    $p['edit'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // View all existing Extended Type?
    $p['index'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // View an existing Extended Type?
    $p['view'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    $this->set('permissions', $p);
    return $p[$this->action];
  }
  
  /**
   * Determine the conditions for pagination of the index view, when rendered via the UI.
   *
   * @since  COmanage Registry v0.6
   * @return Array An array suitable for use in $this->paginate
   */
  
  function paginationConditions() {
    // Only retrieve types for the current attribute and for the current CO
    
    $ret = array();
    
    if(isset($this->cur_co)) {
      $ret['CoExtendedType.co_id'] = $this->cur_co['Co']['id'];
    }
    
    if(isset($this->request->params['named']['attr'])) {
      $ret['CoExtendedType.attribute'] = $this->request->params['named']['attr'];
    }
    
    return $ret;
  }
  
  /**
   * Perform a redirect back to the controller's default view.
   * - postcondition: Redirect generated
   *
   * @since  COmanage Registry v0.6
   */
  
  function performRedirect() {
    // Make sure the attribute is included in the URL
    
    if(isset($this->request->params['named']['attr'])) {
      $attr = $this->request->params['named']['attr'];
    } else {
      $attr = $this->request->data['CoExtendedType']['attribute'];
    }
    
    $this->redirect(array('action' => 'index',
                          'co' => $this->cur_co['Co']['id'],
                          'attr' => $attr));
  }
}
