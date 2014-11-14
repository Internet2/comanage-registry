<?php
/**
 * COmanage Registry CMP Enrollment Configuration Controller
 *
 * Copyright (C) 2011-13 University Corporation for Advanced Internet Development, Inc.
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
  
class CmpEnrollmentConfigurationsController extends StandardController {
  // Class name, used by Cake
  public $name = "CmpEnrollmentConfigurations";
  
  // When using additional models, we must also specify our own
  public $uses = array('CmpEnrollmentConfiguration', 'OrgIdentity');
  
  // Establish pagination parameters for HTML views
  public $paginate = array(
    'limit' => 25,
    'order' => array(
      'CmpEnrollmentConfiguration.name' => 'asc'
    )
  );
  
  /**
   * Callback after controller methods are invoked but before views are rendered.
   * - precondition: Request Handler component has set $this->request
   * - postcondition: Set $vv_availableAttributes
   *
   * @since  COmanage Registry v0.3
   */
  
  function beforeRender() {
    // Set the list of attribute order for the view to render
    
    $this->set('vv_availableAttributes',
               $this->CmpEnrollmentConfiguration->CmpEnrollmentAttribute->availableAttributes());
    
    parent::beforeRender();
  }
  
  /**
   * Perform any dependency checks required prior to a write (add/edit) operation.
   * - postcondition: Session flash message updated (HTML) or HTTP status returned (REST)
   *
   * @since  COmanage Registry v0.3
   * @param  Array Request data
   * @param  Array Current data
   * @return boolean true if dependency checks succeed, false otherwise.
   *
   */
  
  function checkWriteFollowups($reqdata, $curdata = null) {
    if($this->action == 'edit')
    {
      // Check to see if the pool org identities setting has been changed, and
      // if so perform the appropriate updates. At the moment, we only do this
      // on edit and not add since when we add the one and only CMP enrollment
      // config there are no existing org identities.
      
      if(isset($curdata)
         && ($curdata['CmpEnrollmentConfiguration']['pool_org_identities']
             != $reqdata['CmpEnrollmentConfiguration']['pool_org_identities']))
      {
        if($reqdata['CmpEnrollmentConfiguration']['pool_org_identities'])
        {
          // Enable pooling
          
          if(!$this->OrgIdentity->pool())
          {
            $this->Session->setFlash(_txt('er.orgp.pool'), '', array(), 'info');
            return(false);
          }
        }
        else
        {
          // Disable pooling
          
          if(!$this->OrgIdentity->unpool())
          {
            $this->Session->setFlash(_txt('er.orgp.unpool'), '', array(), 'info');
            return(false);
          }
        }
      }
    }
    
    return(true);
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
    $roles = $this->Role->calculateCMRoles();
    
    // Construct the permission set for this user, which will also be passed to the view.
    $p = array();
    
    // Determine what operations this user can perform
    
    // Currently, there is only one CMP Enrollment Configuration per platform.
    // As such, most permissions are denied, even for CMP admins.
    // There is no view-only option, so that is set to false, too.
    
    // Add a new CMP Enrollment Configuration?
    $p['add'] = false;
    
    // Delete an existing CMP Enrollment Configuration?
    $p['delete'] = false;
    
    // Edit an existing CMP Enrollment Configuration?
    $p['edit'] = $roles['cmadmin'];
    
    // View all existing CMP Enrollment Configurations?
    $p['index'] = false;
    
    // Select a CMP Enrollment Configuration?
    $p['select'] = $roles['admin'];
    
    // View an existing CMP Enrollment Configuration?
    $p['view'] = false;

    $this->set('permissions', $p);
    return $p[$this->action];
  }
  
  /**
   * Perform a redirect back to the controller's default view.
   * - postcondition: Redirect generated
   *
   * @since  COmanage Registry v0.9.1
   */
  
  function performRedirect() {
    // Back to the edit view we go...
    
    $this->redirect(array('action' => 'edit',
                          $this->viewVars['cmp_enrollment_configurations'][0]['CmpEnrollmentConfiguration']['id']));
    
  }
  
  /**
   * Select a CMP Enrollment Configuration to operate over.
   * - postcondition: If no CMP Enrollment Configuration exists, one is created
   * - postcondition: Default CMP Enrollment Attributes are created or updated
   * - postcondition: A redirect is issued to the CMP Enrollment Configuration
   *
   * @since  COmanage Registry v0.3
   */
  
  function select() {
    $fid = -1;
    
    // We currently only allow one CMP enrollment configuration per platform.
    // See if there is one, if not create it. Then redirect to edit.
    
    $ef = $this->CmpEnrollmentConfiguration->findDefault();
    
    if(empty($ef))
    {
      // XXX move to Model
      // Not found, create it
      
      $ef['CmpEnrollmentConfiguration'] = array(
        'name' => 'CMP Enrollment Configuration',
        'attrs_from_ldap' => false,
        'attrs_from_saml' => false,
        'status' => StatusEnum::Active
      );
      
      if($this->CmpEnrollmentConfiguration->save($ef))
      {
        $fid = $this->CmpEnrollmentConfiguration->id;
      }
      else
      {
        $this->Session->setFlash(_txt('er.efcf.init'), '', array(), 'error');
        $this->redirect(array('controller' => 'pages', 'action' => 'menu'));
        return;
      }
    }
    else
    {
      $fid = $ef['CmpEnrollmentConfiguration']['id'];
    }
    
    // Redirect to the configuration edit page
    
    $this->redirect(array('controller' => 'cmp_enrollment_configurations',
                          'action' => 'edit',
                          $fid));
  }
}
