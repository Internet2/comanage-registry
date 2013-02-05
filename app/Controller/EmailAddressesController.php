<?php
/**
 * COmanage Registry Email Addresses Controller
 *
 * Copyright (C) 2010-13 University Corporation for Advanced Internet Development, Inc.
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
 * @copyright     Copyright (C) 2010-13 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.1
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
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
    
    // In order to manipulate an email address, the authenticated user must have permission
    // over the associated Org Identity or CO Person. For add action, we accept
    // the identifier passed in the URL, otherwise we lookup based on the record ID.
    
    $managed = false;
    
    if(!empty($roles['copersonid'])) {
      switch($this->action) {
      case 'add':
        if(!empty($pids['copersonid'])) {
          $managed = $this->Role->isCoOrCouAdminForCoPerson($roles['copersonid'],
                                                            $pids['copersonid']);
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
          } elseif(!empty($emailaddress['EmailAddress']['org_identity_id'])) {
            $managed = $this->Role->isCoOrCouAdminForOrgidentity($roles['copersonid'],
                                                                 $emailaddress['EmailAddress']['org_identity_id']);
          }
        }
        break;
      }
    }
    
    // It's not really clear that people should always be able to edit their own email addresses.
    // For now, we won't enable self-service, pending requirements review. (See CO-92.)
    
    // Self is true if this is an add operation & the current user's own person role/org id is in the url
    // OR for other operations the record is attached to the current user's person role/org id.
    $self = false;
    
    // Construct the permission set for this user, which will also be passed to the view.
    $p = array();
    
    // Add a new Email Address?
    $p['add'] = ($roles['cmadmin']
                 || ($managed && ($roles['coadmin'] || $roles['couadmin']))
                 || $self);
    
    // Delete an existing Email Address?
    $p['delete'] = ($roles['cmadmin']
                    || ($managed && ($roles['coadmin'] || $roles['couadmin']))
                    || $self);
    
    // Edit an existing Email Address?
    $p['edit'] = ($roles['cmadmin']
                  || ($managed && ($roles['coadmin'] || $roles['couadmin']))
                  || $self);
    
    // View all existing Email Addresses?
    // Currently only supported via REST since there's no use case for viewing all
    $p['index'] = $this->restful && ($roles['cmadmin'] || $roles['coadmin']);
    
    // View an existing Email Address?
    $p['view'] = ($roles['cmadmin']
                  || ($managed && ($roles['coadmin'] || $roles['couadmin']))
                  || $self);
    
    $this->set('permissions', $p);
    return $p[$this->action];
  }

}
