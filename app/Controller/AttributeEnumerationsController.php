<?php
/**
 * COmanage Registry Attribute Enumerations Controller
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
 * @since         COmanage Registry v2.0.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::uses("StandardController", "Controller");

class AttributeEnumerationsController extends StandardController {
  // Class name, used by Cake
  public $name = "AttributeEnumerations";
  
  // When using additional models, we must also specify our own
  public $uses = array('AttributeEnumeration',
                       'CmpEnrollmentConfiguration');

  // Establish pagination parameters for HTML views
  public $paginate = array(
    'limit' => 25,
    'order' => array(
      'attribute' => 'asc',
      'optvalue' => 'asc'
    )
  );
  
  // This controller needs a CO to be set, but only if Org Identities are not pooled
  public $requires_co = false;
  
  /**
   * Callback before other controller methods are invoked or views are rendered.
   *
   * @since  COmanage Registry v2.0.0
   */
  
  function beforeFilter() {
    if(!$this->CmpEnrollmentConfiguration->orgIdentitiesPooled()) {
      $this->requires_co = true;
    }
    
    parent::beforeFilter();
  }
  
  /**
   * Callback before views are rendered.
   *
   * @since  COmanage Registry v2.0.0
   */
  
  function beforeRender() {
    parent::beforeRender();
    
    // Provide a list of supported attributes for the attribute select menu
    $pool = $this->CmpEnrollmentConfiguration->orgIdentitiesPooled();
    
    $this->set('vv_supported_attrs', $this->AttributeEnumeration->supportedAttrs($pool,
                                                                                 ($pool && empty($cur_co))));
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
    // We operate slightly differently if org identities are pooled vs not.
    // If they are pooled, only a CMP admin can operate when no CO ID is specified
    // (including when the specified record has no CO ID)
    
    $pool = $this->CmpEnrollmentConfiguration->orgIdentitiesPooled();
    
    // Tracking whether CO Admins are allowed to make changes
    $coadminok = false;
    
    if($pool) {
      if($this->action == 'index' || $this->action == 'add') {
        // If a CO is specified then a CO admin is permitted
        
        if(!empty($this->cur_co['Co']['id'])) {
          $coadminok = true;
        }
      } elseif(!empty($this->request->params['pass'][0])) {
        // Delete / Edit / View
        try {
          $coid = $this->AttributeEnumeration->findCoForRecord($this->request->params['pass'][0]);
          
          if($coid == $this->cur_co['Co']['id']) {
            $coadminok = true;
          }
        }
        catch(InvalidArgumentException $e) {
          // No CO found for record
          $coadminok = false;
        }
      }
    } else {
      // Not pooled, so CO admins can always make changes
      
      $coadminok = true;
    }
    
    $roles = $this->Role->calculateCMRoles();
    
    // Construct the permission set for this user, which will also be passed to the view.
    $p = array();
    
    // Determine what operations this user can perform
    
    // Add a new Attribute Enumeration?
    $p['add'] = ($roles['cmadmin'] || ($coadminok && $roles['coadmin']));
    
    // Delete an existing Attribute Enumeration?
    $p['delete'] = ($roles['cmadmin'] || ($coadminok && $roles['coadmin']));
    
    // Edit an existing Attribute Enumeration?
    $p['edit'] = ($roles['cmadmin'] || ($coadminok && $roles['coadmin']));
    
    // View all existing Attribute Enumeration?
    $p['index'] = ($roles['cmadmin'] || ($coadminok && $roles['coadmin']));
    
    // View an existing Attribute Enumeration?
    $p['view'] = ($roles['cmadmin'] || ($coadminok && $roles['coadmin']));
    
    $this->set('permissions', $p);
    return $p[$this->action];
  }
  
  /**
   * Determine the conditions for pagination of the index view, when rendered via the UI.
   *
   * @since  COmanage Registry v2.0.0
   * @return Array An array suitable for use in $this->paginate
   */
  
  function paginationConditions() {
    // Only retrieve types for the current CO
    
    $ret = array();
    
    if(isset($this->cur_co)) {
      $ret['conditions']['AttributeEnumeration.co_id'] = $this->cur_co['Co']['id'];
    } else {
      $ret['conditions']['AttributeEnumeration.co_id'] = null;
    }
    
    return $ret;
  }
}
