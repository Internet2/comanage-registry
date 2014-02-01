<?php
/**
 * COmanage Registry CO Notificaations Controller
 *
 * Copyright (C) 2014 University Corporation for Advanced Internet Development, Inc.
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
 * @copyright     Copyright (C) 2014 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.9
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */

App::uses("StandardController", "Controller");

class CoNotificationsController extends StandardController {
  // Class name, used by Cake
  public $name = "CoNotifications";
  
  // Establish pagination parameters for HTML views
  public $paginate = array(
    'limit' => 25,
    'order' => array(
      'CoNotifications.created' => 'desc'
    )
  );
  
  // This controller needs a CO to be set
  public $requires_co = true;
  
  // For rendering views, we need more information than just the various ID numbers
  // stored in a petition.
  public $view_contains = array(
    'SubjectCoPerson' => 'PrimaryName',
    'ActorCoPerson' => 'PrimaryName',
    'RecipientCoPerson' => 'PrimaryName',
    'RecipientCoGroup',
    'ResolverCoPerson' => 'PrimaryName'
  );
  
  /**
   * Acknowledge the specified notification.
   * - postcondition: CO Invitation status set to 'Acknowledged'
   * - postcondition: Session flash message updated (HTML)
   *
   * @since  COmanage Registry v0.9
   * @param  Integer $id CO Notification ID
   */
  
  public function acknowledge($id) {
    try {
      $this->CoNotification->acknowledge($id, $this->Session->read('Auth.User.co_person_id'));
    }
    catch(Exception $e) {
      $this->Session->setFlash($e->getMessage(), '', array(), 'error');
    }
    
    $this->Session->setFlash(_txt('rs.nt.ackd'), '', array(), 'success');
    
    // Not really clear where to redirect to
    $this->redirect("/");
  }
  
  /**
   * Determine the CO ID based on some attribute of the request.
   * This method is intended to be overridden by model-specific controllers.
   *
   * @since  COmanage Registry v0.9
   * @return Integer CO ID, or null if not implemented or not applicable.
   * @throws InvalidArgumentException
   */
  
  protected function calculateImpliedCoId() {
    // Determine CO from Subject CO Person ID
    
    if(!empty($this->request->params['pass'][0])) {
      $args = array();
      $args['conditions']['CoNotification.id'] = $this->request->params['pass'][0];
      $args['joins'][0]['table'] = 'co_notifications';
      $args['joins'][0]['alias'] = 'CoNotification';
      $args['joins'][0]['type'] = 'INNER';
      $args['joins'][0]['conditions'][0] = 'SubjectCoPerson.id=CoNotification.subject_co_person_id';
      $args['contain'] = false;
      
      $coPerson = $this->CoNotification->SubjectCoPerson->find('first', $args);
      
      if(!empty($coPerson['SubjectCoPerson']['co_id'])) {
        return $coPerson['SubjectCoPerson']['co_id'];
      } else {
        throw InvalidArgumentException(_txt('er.notfound',
                                            array(_txt('ct.co_notifications.1'),
                                                  Sanitize::html($this->request->params['pass'][0]))));
      }
    }
    
    return null;
  }
  
  /**
   * Cancel the specified notification.
   * - postcondition: CO Invitation status set to 'Canceled'
   * - postcondition: Session flash message updated (HTML)
   *
   * @since  COmanage Registry v0.9
   * @param  Integer $id CO Notification ID
   */
  
  public function cancel($id) {
    try {
      $this->CoNotification->cancel($id, $this->Session->read('Auth.User.co_person_id'));
    }
    catch(Exception $e) {
      $this->Session->setFlash($e->getMessage(), '', array(), 'error');
    }
    
    $this->Session->setFlash(_txt('rs.nt.cxld'), '', array(), 'success');
    
    // Not really clear where to redirect to
    $this->redirect("/");
  }
  
  /**
   * Authorization for this Controller, called by Auth component
   * - precondition: Session.Auth holds data used for authz decisions
   * - postcondition: $permissions set with calculated permissions
   *
   * @since  COmanage Registry v0.9
   * @return Array Permissions
   */
  
  function isAuthorized() {
    $roles = $this->Role->calculateCMRoles();
    
    // Construct the permission set for this user, which will also be passed to the view.
    $p = array();
    
    // Determine what operations this user can perform
    
    // Acknowledge (but not necessary resolve!) this notification?
    $p['acknowledge'] = ($roles['cmadmin']
                         || $roles['coadmin']
                         || (!empty($this->request->params['pass'][0])
                             && $this->Role->isNotificationRecipient($this->request->params['pass'][0],
                                                                     $roles['copersonid'])));
    
    // Cancel this notification?
    $p['cancel'] = ($roles['cmadmin']
                    || $roles['coadmin']
                    || (!empty($this->request->params['pass'][0])
                        && $this->Role->isNotificationSender($this->request->params['pass'][0],
                                                             $roles['copersonid'])));
    
    // View this notification?
    $p['view'] = ($roles['cmadmin']
                  || $roles['coadmin']
                  || (!empty($this->request->params['pass'][0])
                      && $this->Role->isNotificationParticipant($this->request->params['pass'][0],
                                                                $roles['copersonid'])));
    
    $this->set('permissions', $p);
    return $p[$this->action];
  }
}
