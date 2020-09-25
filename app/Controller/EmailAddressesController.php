<?php
/**
 * COmanage Registry Email Addresses Controller
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

App::uses("MVPAController", "Controller");

class EmailAddressesController extends MVPAController {
  // Class name, used by Cake
  public $name = "EmailAddresses";
  
  // Establish pagination parameters for HTML views
  public $paginate = array(
    'limit' => 25,
    'order' => array(
      'mail' => 'asc'
    )
  );
  
  public $edit_contains = array(
    'CoDepartment',
    'CoPerson' => array('PrimaryName'),
    'Organization',
    'OrgIdentity' => array('PrimaryName')
  );

  public $view_contains = array(
    'CoDepartment',
    'CoPerson' => array('PrimaryName'),
    'Organization',
    'OrgIdentity' => array('OrgIdentitySourceRecord' => array('OrgIdentitySource'),
                           'PrimaryName'),
    'SourceEmailAddress'
  );
  
  /**
   * Callback to set relevant tab to open when redirecting to another page
   * - precondition:
   * - postcondition: Auth component is configured
   * - postcondition:
   *
   * @since  COmanage Registry v0.8
   */

  function beforeFilter() {
    $this->redirectTab = 'email';

    parent::beforeFilter();
  }

  /**
   * Authorization for this Controller, called by Auth component
   * - precondition: Session.Auth holds data used for authz decisions
   * - postcondition: $permissions set with calculated permissions
   *
   * @since  COmanage Registry v0.1
   * @return Array Permissions
   */
  
  function isAuthorized() {
    $roles = $this->Role->calculateCMRoles();
    $pids = $this->parsePersonID($this->request->data);
    
    // Is this a read only record? True if it belongs to an Org Identity that has
    // an OrgIdentity Source Record. As of the initial implementation, not even
    // CMP admins can edit such a record.
    
    $readOnly = false;
    
    if($this->action == 'edit' && !empty($this->request->params['pass'][0])) {
      $sourceAttributeId = (bool)$this->EmailAddress->field('source_email_address_id', array('id' => $this->request->params['pass'][0]));

      if($sourceAttributeId) {
        $readOnly = true;
      } else {
        $orgIdentityId = $this->EmailAddress->field('org_identity_id', array('id' => $this->request->params['pass'][0]));
        
        if($orgIdentityId) {
          $readOnly = $this->EmailAddress->OrgIdentity->readOnly($orgIdentityId);
        }
      }
    }
    
    if($readOnly) {
      // Proactively redirect to view. This will also prevent (eg) the REST API
      // from editing a read only record.
      $args = array(
        'controller' => 'email_addresses',
        'action'     => 'view',
        filter_var($this->request->params['pass'][0],FILTER_SANITIZE_SPECIAL_CHARS)
      );
      
      $this->redirect($args);
    }
    
    // In order to manipulate an email address, the authenticated user must have permission
    // over the associated Org Identity or CO Person. For add action, we accept
    // the identifier passed in the URL, otherwise we lookup based on the record ID.
    
    $managed = false;
    $self = false;
    $emailaddress = null;
    
    if(!empty($roles['copersonid'])) {
      switch($this->action) {
      case 'add':
        if(!empty($pids['copersonid'])) {
          $managed = $this->Role->isCoOrCouAdminForCoPerson($roles['copersonid'],
                                                            $pids['copersonid']);
          
          if($pids['copersonid'] == $roles['copersonid']) {
            $self = true;
          }
        } elseif(!empty($pids['orgidentityid'])) {
          $managed = $this->Role->isCoOrCouAdminForOrgIdentity($roles['copersonid'],
                                                               $pids['orgidentityid']);
        }
        break;
      case 'delete':
      case 'edit':
      case 'view':
        if(!empty($this->request->params['pass'][0])) {
          // look up $this->request->params['pass'][0] and find the appropriate co person id or org identity id
          // then pass that to $this->Role->isXXX
          $args = array();
          $args['conditions']['EmailAddress.id'] = $this->request->params['pass'][0];
          $args['contain'] = false;
          
          $emailaddress = $this->EmailAddress->find('first', $args);
          
          if(!empty($emailaddress['EmailAddress']['co_person_id'])) {
            $managed = $this->Role->isCoOrCouAdminForCoPerson($roles['copersonid'],
                                                              $emailaddress['EmailAddress']['co_person_id']);
            
            if($emailaddress['EmailAddress']['co_person_id'] == $roles['copersonid']) {
              $self = true;
            }
          } elseif(!empty($emailaddress['EmailAddress']['org_identity_id'])) {
            $managed = $this->Role->isCoOrCouAdminForOrgidentity($roles['copersonid'],
                                                                 $emailaddress['EmailAddress']['org_identity_id']);
          }
        }
        break;
      }
    }
    
    // Construct the permission set for this user, which will also be passed to the view.
    $p = array();
    
    // Self service is a bit complicated because permission can vary by type.
    // Self service only applies to CO Person-attached attributes.
    
    $selfperms = array(
      'add'    => false,
      'delete' => false,
      'edit'   => false,
      'view'   => false
    );
    
    if($self) {
      foreach(array_keys($selfperms) as $a) {
        $selfperms[$a] = $this->EmailAddress
                              ->CoPerson
                              ->Co
                              ->CoSelfServicePermission
                              ->calculatePermission($this->cur_co['Co']['id'],
                                                    'EmailAddress',
                                                    $a,
                                                    ($a != 'add' && !empty($emailaddress['EmailAddress']['type']))
                                                     ? $emailaddress['EmailAddress']['type'] : null);
      }
      
      $p['selfsvc'] = $this->Co->CoSelfServicePermission->findPermissions($this->cur_co['Co']['id']);
    } else {
      $p['selfsvc'] = null;
    }
    
    // Add a new Email Address?
    $p['add'] = ($roles['cmadmin']
                 || ($managed && ($roles['coadmin'] || $roles['couadmin']))
                 || $selfperms['add']);
    
    // Delete an existing Email Address?
    $p['delete'] = ($roles['cmadmin']
                    || ($managed && ($roles['coadmin'] || $roles['couadmin']))
                    || $selfperms['delete']);
    
    // Edit an existing Email Address?
    $p['edit'] = ($roles['cmadmin']
                  || ($managed && ($roles['coadmin'] || $roles['couadmin']))
                  || $selfperms['edit']);
    
    // View all existing Email Addresses?
    // Currently only supported via REST since there's no use case for viewing all
    $p['index'] = $this->request->is('restful') && ($roles['cmadmin'] || $roles['coadmin']);
    
    // Generate an email verification request?
    // This needs to correlate with CoInvitesController.
    $p['verifyEmailAddress'] = (!empty($this->request->params['pass'][0])
                                && ($roles['cmadmin']
                                    || $this->Role->canRequestVerificationOfEmailAddress($roles['copersonid'],
                                                                                         $this->request->params['pass'][0])));
    
    // View an existing Email Address?
    $p['view'] = ($roles['cmadmin']
                  || ($managed && ($roles['coadmin'] || $roles['couadmin']))
                  || $selfperms['view']);
    
    $this->set('permissions', $p);
    return $p[$this->action];
  }
}
