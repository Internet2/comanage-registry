<?php
/**
 * COmanage Registry Identity Documents Controller
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
 * @since         COmanage Registry v4.0.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::uses("StandardController", "Controller");

class IdentityDocumentsController extends StandardController {
  // Class name, used by Cake
  public $name = "IdentityDocuments";
  
  public $uses = array('IdentityDocument', 
                       'AttributeEnumeration',
                       'HistoryRecord');
  
  // Establish pagination parameters for HTML views
  public $paginate = array(
    'limit' => 25,
    'order' => array(
      'document_type' => 'asc'
    )
  );
  
  // This controller needs a CO to be set
  public $requires_co = true;
  
  public $requires_person = true;
  
  /**
   * Callback before other controller methods are invoked or views are rendered.
   * - postcondition: $plugins set
   *
   * @since  COmanage Registry v4.0.0
   * @throws InvalidArgumentException
   */
  
  function beforeFilter() {
    parent::beforeFilter();
    
    if(!empty($this->viewVars['vv_tz'])) {
      // Set the current timezone, primarily for beforeSave
      $this->IdentityDocument->setTimeZone($this->viewVars['vv_tz']);
    }
  }
  
  /**
   * Callback after controller methods are invoked but before views are rendered.
   * - precondition: Request Handler component has set $this->request
   * - postcondition: Set $sponsors
   *
   * @since  COmanage Registry v4.0.0
   */

  public function beforeRender() {
    parent::beforeRender();
    
    // Pull the list(s) of enumerations (based on type), if so configured
    $enums = array();
    
    $reflectionClass = new ReflectionClass('IdentityDocumentEnum');
    
    foreach($reflectionClass->getConstants() as $label => $key) {
      $enums['IdentityDocument.issuing_authority.'.$key] = $this->AttributeEnumeration->enumerations($this->cur_co['Co']['id'], "IdentityDocument.issuing_authority." . $key);
    }

    $this->set('vv_enums', $enums);
  }
  
  /**
   * Generate history records for a transaction. This method is intended to be
   * overridden by model-specific controllers, and will be called from within a
   * try{} block so that HistoryRecord->record() may be called without worrying
   * about catching exceptions.
   *
   * @since  COmanage Registry v4.0.0
   * @param  String Controller action causing the change
   * @param  Array Data provided as part of the action (for add/edit)
   * @param  Array Previous data (for delete/edit)
   * @return boolean Whether the function completed successfully (which doesnot necessarily imply history was recorded)
   */

  public function generateHistory($action, $newdata, $olddata) {
    $comment = " (";
    
    if(!empty($newdata['IdentityDocument']['document_type'])) {
      $comment .= $newdata['IdentityDocument']['document_type'];
    } elseif(!empty($olddata['IdentityDocument']['document_type'])) {
      $comment .= $olddata['IdentityDocument']['document_type'];
    }
    
    if(!empty($newdata['IdentityDocument']['document_subtype'])) {
      $comment .= ":" . $newdata['IdentityDocument']['document_subtype'];
    } elseif(!empty($olddata['IdentityDocument']['document_subtype'])) {
      $comment .= ":" . $olddata['IdentityDocument']['document_subtype'];
    }
    
    if(!empty($newdata['IdentityDocument']['issuing_authority'])) {
      $comment .= ":" . $newdata['IdentityDocument']['issuing_authority'];
    } elseif(!empty($olddata['IdentityDocument']['issuing_authority'])) {
      $comment .= ":" . $olddata['IdentityDocument']['issuing_authority'];
    }
    
    $comment .= ")";
    
    switch($action) {
      case 'add':
        $this->HistoryRecord->record($newdata['IdentityDocument']['co_person_id'],
                                     null,
                                     null,
                                     $this->Session->read('Auth.User.co_person_id'),
                                     ActionEnum::IdentityDocumentAdded,
                                     _txt('en.action', null, ActionEnum::IdentityDocumentAdded) . $comment);
        break;
      case 'delete':
        $this->HistoryRecord->record($olddata['IdentityDocument']['co_person_id'],
                                     null,
                                     null,
                                     $this->Session->read('Auth.User.co_person_id'),
                                     ActionEnum::IdentityDocumentDeleted,
                                     _txt('en.action', null, ActionEnum::IdentityDocumentDeleted) . $comment);
        break;
      case 'edit':
        $this->HistoryRecord->record($olddata['IdentityDocument']['co_person_id'],
                                     null,
                                     null,
                                     $this->Session->read('Auth.User.co_person_id'),
                                     ActionEnum::IdentityDocumentEdited,
                                     _txt('en.action', null, ActionEnum::IdentityDocumentEdited) . $comment);
        break;
    }
    
    return true;
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
    $roles = $this->Role->calculateCMRoles();           // Who we authenticated as
    $pids = $this->parsePersonID($this->request->data); // Who we're asking for
    
    // Is this a record we can manage?
    $managed = false;
    $self = false;
    
    if(!empty($roles['copersonid'])
       && !empty($this->request->params['named']['copersonid'])) {
      if($roles['copersonid'] == $this->request->params['named']['copersonid']) {
        $self = true;
      }
      
      $managed = $this->Role->isCoOrCouAdminForCoPerson($roles['copersonid'],
                                                        $this->request->params['named']['copersonid']);
    }
    
    // Construct the permission set for this user, which will also be passed to the view.
    $p = array();
    
    // Determine what operations this user can perform.
    
    // Add a new Identity Document?
    $p['add'] = ($roles['cmadmin']
                 || $roles['coadmin']
                 || ($managed && $roles['couadmin']));
    
    // Delete an existing Identity Document?
    $p['delete'] = ($roles['cmadmin']
                    || $roles['coadmin']
                    || ($managed && $roles['couadmin']));
    
    // Edit an existing Identity Document?
    $p['edit'] = ($roles['cmadmin']
                  || $roles['coadmin']
                  || ($managed && $roles['couadmin']));
    
    // View all existing Identity Document?
    $p['index'] = ($roles['cmadmin']
                   || $roles['coadmin']
                   || ($managed && $roles['couadmin']));
    
    // View an existing Identity Document?
    $p['view'] = ($roles['cmadmin']
                  || $roles['coadmin']
                  || ($managed && $roles['couadmin']));
    
    $this->set('permissions', $p);
    return $p[$this->action];
  }
  
  /**
   * Perform a redirect back to the controller's default view.
   * - postcondition: Redirect generated
   *
   * @since  COmanage Registry v4.0.0
   */
  
  function performRedirect() {
    // StandardController will redirect to the Person Canvas, but we want the
    // Identity Document index
    
    $p = $this->parsePersonID($this->request->data);
    
    $target = array();
    $target['controller'] = 'identity_documents';
    $target['action'] = 'index';
    $target['copersonid'] = filter_var($p['copersonid'],FILTER_SANITIZE_SPECIAL_CHARS);
    
    $this->redirect($target);
  }
}
