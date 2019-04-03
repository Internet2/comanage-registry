<?php
/**
 * COmanage Authentication Events Controller
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

class AuthenticationEventsController extends StandardController {
  // Class name, used by Cake
  public $name = "AuthenticationEvents";
  
  // When using additional models, we must also specify our own
  public $uses = array('AuthenticationEvent',
                       'CoPerson',
                       'Identifier');
  
  // Establish pagination parameters for HTML views
  public $paginate = array(
    'limit' => 25,
    'order' => array(
      'AuthenticationEvent.id' => 'desc'
    )
  );
  
  public $requires_co = false;
  public $requires_person = false;
  
  /**
   * Authorization for this Controller, called by Auth component
   * - precondition: Session.Auth holds data used for authz decisions
   * - postcondition: $permissions set with calculated permissions
   *
   * @since  COmanage Registry v2.0.0
   * @return Array Permissions
   */
  
  function isAuthorized() {
    // Unlike most other controllers, this one only supports viewing data. Authentication Events
    // are generally created by other parts of the application, invoking the model. To enforce
    // this, we simply don't set permission for most actions.
    
    $roles = $this->Role->calculateCMRoles();
    
    $managed = false;
    $self = false;
    
    if(!empty($this->request->params['named']['identifier'])) {
      // For index views, we need to make sure the viewer has permission to see
      // records associated with the requested person.
      
      $u = $this->Session->read('Auth.User.username');
      
      if(!empty($this->request->params['named']['identifier']) && !empty($u)
         && $u == $this->request->params['named']['identifier']) {
        $self = true;
      }
      
      $pool = $this->CmpEnrollmentConfiguration->orgIdentitiesPooled();
      
      // Authentication events only apply to Org Identities, so if org identities are
      // pooled then any admin can "manage" the identity. Otherwise we need to see if
      // the identifier is registered in a CO that the current user is an admin for.
      
      if($pool) {
        $managed = $roles['admin'] || $roles['subadmin'];
      } else {
        // We need to figure out if the current user is an admin for the requested identifier.
        // Since we don't require a CO, we only know the current user's Org Identities, and
        // need to leverage that to see if they are an admin in the appropriate CO.
        
        // First, get the CO Person IDs associated with the current user.
        $copids = $this->CoPerson->idsForIdentifier($u, null, true);
        
        // Next, get the Org Identities associated with the requested identifier
        $args = array();
        $args['conditions']['Identifier.identifier'] = $this->request->params['named']['identifier'];
        $args['conditions']['Identifier.login'] = true;
        $args['conditions']['Identifier.status'] = SuspendableStatusEnum::Active;
        $args['conditions'][] = 'Identifier.org_identity_id IS NOT NULL';
        $args['fields'] = array('Identifier.id', 'Identifier.org_identity_id');
        $args['contain'] = false;
        
        $orgids = $this->Identifier->find('list', $args);
        
        // Finally, see if any $copid is an admin for any org identity
        
        foreach($copids as $copid) {
          foreach(array_values($orgids) as $orgid) {
            if($this->Role->isCoOrCouAdminForOrgIdentity($copid, $orgid)) {
              $managed = true;
              break 2;
            }
          }
        }
      }
    }
    
    // Construct the permission set for this user, which will also be passed to the view.
    $p = array();
    
    // Determine what operations this user can perform
    
    // View authentication events? We allow the authenticated identifier to view their own.
    // We could allow $self to view own records, but for the moment we don't (for no specific reason).
    
    $p['index'] = ($roles['cmadmin'] || $managed || $self);
    
    $this->set('permissions', $p);
    return $p[$this->action];
  }
  
  /**
   * Determine the conditions for pagination of the index view, when rendered via the UI.
   *
   * @since  COmanage Registry v2.0.0
   * @return Array An array suitable for use in $this->paginate
   */

  public function paginationConditions() {
    $pagcond = array();
    
    if(!empty($this->request->params['named']['identifier'])) {
      $pagcond['conditions']['AuthenticationEvent.authenticated_identifier'] = $this->request->params['named']['identifier'];
    }
    
    return $pagcond;
  }
}
