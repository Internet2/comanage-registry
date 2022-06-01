<?php
/**
 * COmanage Registry Service Eligibility Enroller Event Listener
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
 * @link          https://www.internet2.edu/comanage COmanage Project
 * @package       registry-plugin
 * @since         COmanage Registry v4.1.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */
  
App::uses('CakeEventListener', 'Event');

class ServiceEligibilityListener implements CakeEventListener {
  /**
   * Define our listener(s)
   *
   * @since  COmanage Registry v3.3.0
   * @return Array Array of events and associated function names
   */

  public function implementedEvents() {
    return array(
      'Model.afterDelete' => 'removeEligibility',
      'Model.afterSave'   => 'removeEligibility'
    );
  }
  
  /**
   * Handle an event that may imply the removal of a Service Eligibility.
   *
   * @since  COmanage Registry v4.1.0
   * @param  CakeEvent $event Cake Event
   * @return boolean          True to continue the event flow
   */
  public function removeEligibility(CakeEvent $event) {
    $subject = $event->subject();
    // We need to cache data from the event, since the above is apparently a
    // pointer or something, and doesn't necessarily persist as long as we need it
    $subjectData = $subject->data;
    $subjectId = $subject->id;
    $subjectName = $subject->name;
    
    $eventName = $event->name();
    
    // First see if this is a model we're interested in. We only automatically
    // remove eligibilities, we don't try to add them back in. (For CoGroupMember
    // we don't know which role to attach the eligibility to. For CoPersonRole,
    // we would have to track what eligibilities the subject previously had.)
    
    if($subjectName == 'CoGroupMember') {
      // If there is a Service associated with the target CO Group, and the
      // subject has an eligibility for that Service by ANY role, remove the
      // eligibility. Note we don't actually know if this group membership
      // implied anything, we hand off to ServiceEligibility to figure it out.
      
      $ServiceEligibility = ClassRegistry::init('ServiceEligibilityEnroller.ServiceEligibility');
      
      if($eventName == 'Model.afterDelete') {
        // The CoGroupMember id is $subjectId.
        
        $ServiceEligibility->removeByGroupMemberId($subjectId);
      } else {
        // We have a CoGroupMember object in $subjectData
        
        if(!$subjectData['CoGroupMember']['member']) {
          $ServiceEligibility->removeByGroup($subjectData['CoGroupMember']['co_person_id'],
                                             $subjectData['CoGroupMember']['co_group_id']);
        }
      }
    } elseif($subjectName == 'CoPersonRole') {
      // If the role status is Declined, Deleted, Denied, Duplicate, Expired, or
      // Suspended, we remove any associated eligibility. We specifically do NOT
      // want to remove the eligibility when transitioning through various
      // petition status like PendingApproval.
      
      $ServiceEligibility = ClassRegistry::init('ServiceEligibilityEnroller.ServiceEligibility');
      
      if($eventName == 'Model.afterDelete') {
        // The CoPersonRole id is $subjectId.
        
        $ServiceEligibility->removeByRoleId($subjectId);
      } elseif(!empty($subjectData['CoPersonRole']['id'])) {
        // We have a CoPersonRole object in $subjectData. Note we might get here
        // with $subjectData['EnrolleeCoPersonRole'] via an Enrollment Flow, but
        // we can ignore that since there's no role yet to remove an eligibilty
        // from.
        
        $status = null;
        
        if(!empty($subjectData['CoPersonRole']['status'])) {
          $status = $subjectData['CoPersonRole']['status'];
        } else {
          // We got here via saveField, pull the status
          
          $CoPersonRole = ClassRegistry::init('CoPersonRole');
          $status = $CoPersonRole->field('status', array('CoPersonRole.id' => $subjectData['CoPersonRole']['id']));
        }
        
        if(in_array($status,
                    array(
                      StatusEnum::Declined,
                      StatusEnum::Deleted,
                      StatusEnum::Denied,
                      StatusEnum::Duplicate,
                      StatusEnum::Expired,
                      StatusEnum::Suspended
                    ))) {
          $ServiceEligibility->removeByRoleId($subjectData['CoPersonRole']['id']);
        }
      }
    }
    
    return true;
  }
}