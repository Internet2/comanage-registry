<?php
/**
 * COmanage Registry Sponsor Manager Model
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

class SponsorManager extends AppModel {
  // Required by COmanage Plugins
  public $cmPluginType = "other";
  
  // Document foreign keys
  public $cmPluginHasMany = array(
    "Co" => array("SponsorManagerSetting")
  );
  
  /**
   * Expose menu items.
   * 
   * @since COmanage Registry v4.1.0
   * @return Array with menu location type as key and array of labels, controllers, actions as values.
   */
  
  public function cmPluginMenus() {
    return array(
      "canvas" => array(
        _txt('pl.sponsormanager.view') => array(
          'icon'        => 'person',
          'controller'  => 'sponsors',
          'action'      => 'review'
        )
      ),
      "coconfig" => array(
        _txt('ct.sponsor_manager_settings.pl') =>
          array('icon'       => 'view_list',
                'controller' => 'sponsor_manager_settings',
                'action'     => 'index')
      )
    );
  }
  
  /**
   * Cancel a Pending Enrollment.
   *
   * @since  COmanage Registry v4.1.0
   * @param  int  $coPersonRoleId   CO Person Role ID to cancel enrollment for
   * @param  int  $actorCoPersonId  Actor CO Person ID
   * @return bool                   True on success
   * @throws InvalidArgumentException
   */
  
  public function cancel($coPersonRoleId, $actorCoPersonId) {
    $CoPersonRole = ClassRegistry::init('CoPersonRole');
    $HistoryRecord = ClassRegistry::init('HistoryRecord');
    
    // Pull the CO Person ID, and while we're here look for a Petition.
    // Strictly speaking, a CO Person can be in Pending status without an
    // attached Petition, though most of the time there will be one.
    
    $args = array();
    $args['conditions']['CoPersonRole.id'] = $coPersonRoleId;
    $args['contain'] = array('CoPetition');
    
    $copr = $CoPersonRole->find('first', $args);
    
    if(!$copr) {
      throw new InvalidArgumentException(_txt('er.notfound', array(_txt('ct.co_person_roles.1', $coPersonRoleId))));
    }
    
    if(!empty($copr['CoPetition'][0]['id'])) {
      // Check the petition status, and if suitable deny it
      
      if(!in_array($copr['CoPetition'][0]['status'],
                   array(
                     PetitionStatusEnum::Confirmed,
                     PetitionStatusEnum::Created,
                     PetitionStatusEnum::PendingApproval,
                     PetitionStatusEnum::PendingConfirmation,
                     PetitionStatusEnum::PendingVetting
                   ))) {
        throw new InvalidArgumentException(_txt('er.sponsormanager.petition'));
      }
      
      // Setting the petition to denied status will update the role status
      $CoPersonRole->CoPetition->updateStatus($copr['CoPetition'][0]['id'],
                                              PetitionStatusEnum::Denied,
                                              $actorCoPersonId);
    } else {
      // Just switch the role status to denied
      $CoPersonRole->clear();
      $CoPersonRole->id = $coPersonRoleId;
      $CoPersonRole->saveField('status', StatusEnum::Denied);
    }
    
    return true;
  }
  
  /**
   * Expire a CO Person Role.
   *
   * @since  COmanage Registry v4.1.0
   * @param  int  $coPersonRoleId   CO Person Role ID to cancel enrollment for
   * @param  int  $actorCoPersonId  Actor CO Person ID
   * @return bool                   True on success
   * @throws InvalidArgumentException
   */
  
  public function expire($coPersonRoleId, $actorCoPersonId) {
    $CoPersonRole = ClassRegistry::init('CoPersonRole');
    
    $coPersonId = $CoPersonRole->field('co_person_id', array('CoPersonRole.id' => $coPersonRoleId));
    
    $validThrough = date('Y-m-d H:i:s', time()-1);
    
    $CoPersonRole->clear();
    $CoPersonRole->id = $coPersonRoleId;
    $CoPersonRole->saveField('valid_through', $validThrough);
    
    // We need to manually save the status since CoPersonRole::afterSave won't
    // see the status via saveField.
    $CoPersonRole->saveField('status', StatusEnum::Expired);

    $CoPersonRole->HistoryRecord->record(
      $coPersonId,
      $coPersonRoleId,
      null,
      $actorCoPersonId,
      'pSPX',
      _txt('pl.sponsormanager.expired')
    );
    
    return true;
  }
  
  /**
   * Renew a Pending Enrollment.
   *
   * @since  COmanage Registry v4.1.0
   * @param  array $settings         Sponsor Manager Settings
   * @param  int   $coPersonRoleId   CO Person Role ID to cancel enrollment for
   * @param  int   $actorCoPersonId  Actor CO Person ID
   * @return bool                    True on success
   * @throws InvalidArgumentException
   */
    
  public function renew($settings, $coPersonRoleId, $actorCoPersonId) {
    $CoPersonRole = ClassRegistry::init('CoPersonRole');
    
    $coPersonId = $CoPersonRole->field('co_person_id', array('CoPersonRole.id' => $coPersonRoleId));
    
    if(!$coPersonId) {
      throw new InvalidArgumentException(_txt('er.notfound', array(_txt('ct.co_person_roles.1', $coPersonRoleId))));
    }
    
    // Make sure the requested role is within the renewal window, if set
    if(!empty($settings['SponsorManagerSetting']['renewal_window'])) {
      $roleExpiration = $CoPersonRole->field('valid_through', array('CoPersonRole.id' => $coPersonRoleId));
      
      // If there is not currently an valid_through set, we'll allow "renewal"
      // at any time, which is really just attaching an expiration date to a
      // role that doesn't have one.
      
      if($roleExpiration) {
        // Calculate the start of the renewal window, as now+window. In other
        // words, if today is March 1 and the renewal window is 30 days, we'll
        // accept renewals on roles expiring March 31 or later.
        $renewalWindowStart = new DateTime('+' . $settings['SponsorManagerSetting']['renewal_window'] . ' days');
        
        // Diff the window start and the role expiration
        $interval = $renewalWindowStart->diff(new DateTime($roleExpiration));
        
        // If invert is 1, the diff is negative (meaning the window start is
        // _earlier_ than the expiration date) and therefore the role is within
        // the renewal window.
        
        if(!$interval->invert) {
          throw new InvalidArgumentException(_txt('er.sponsormanager.renewal_window'));
        }
      }
    }
    
    // If we make it here, we should process the renewal.
    
    $validThrough = date('Y-m-d 23:59:59', strtotime('+' . $settings['SponsorManagerSetting']['renewal_term'] . ' days'));
    
    $CoPersonRole->clear();
    $CoPersonRole->id = $coPersonRoleId;
    $CoPersonRole->saveField('valid_through', $validThrough);
    
    // We need to manually save the status since CoPersonRole::afterSave won't
    // see the status via saveField.
    $CoPersonRole->saveField('status', StatusEnum::Active);

    // Create a history record
    $result = _txt('pl.sponsormanager.renewed', array($validThrough));
    
    $CoPersonRole->HistoryRecord->record(
      $coPersonId,
      $coPersonRoleId,
      null,
      $actorCoPersonId,
      'pSPR',
      $result
    );
    
    // It's a bit weird to return the history string, but it's useful in the
    // controller and there's nothing else we need to return.
    return $result;
  }
}
