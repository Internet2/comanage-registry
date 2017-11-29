<?php
/**
 * COmanage Registry CO Mailman3 Provisioner Target Model
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
 * @package       registry-plugin
 * @since         COmanage Registry v3.1.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::uses("CoProvisionerPluginTarget", "Model");
App::uses('CoHttpClient', 'Lib');

class CoMailmanProvisionerTarget extends CoProvisionerPluginTarget {
  // Define class name for cake
  public $name = "CoMailmanProvisionerTarget";
  
  // Add behaviors
  public $actsAs = array('Containable');
  
  // Association rules from this model to other models
  public $belongsTo = array('CoProvisioningTarget');
  
  public $hasMany = array('MailmanProvisioner.CoMailmanList' => array('dependent' => true));
  
  // Default display field for cake generated views
  public $displayField = "serverurl";
  
  // Validation rules for table elements
  public $validate = array(
    'co_provisioning_target_id' => array(
      'rule' => 'numeric',
      'required' => true
    ),
    'serverurl' => array(
      'rule' => array('url', true),
      'required' => true,
      'allowEmpty' => false
    ),
    'adminuser' => array(
      'rule' => 'notBlank',
      'required' => true,
      'allowEmpty' => false
    ),
    'password' => array(
      'rule' => 'notBlank',
      'required' => true,
      'allowEmpty' => false
    ),
    'domain' => array(
      'rule' => 'notBlank',
      'required' => true,
      'allowEmpty' => false
    ),
    'pref_identifier_type' => array(
      'rule' => array('validateExtendedType',
                      array('attribute' => 'EmailAddress.type',
                            'default' => array(EmailAddressEnum::Delivery,
                                               EmailAddressEnum::Forwarding,
                                               EmailAddressEnum::MailingList,
                                               EmailAddressEnum::Official,
                                               EmailAddressEnum::Personal,
                                               EmailAddressEnum::Preferred,
                                               EmailAddressEnum::Recovery))),
      'required' => false,
      'allowEmpty' => false
    ),
  );
  
  /**
   * Actions to take before a save operation is executed.
   *
   * @since  COmanage Registry v3.1.0
   * @throws RuntimeException
   */
  
  public function beforeSave($options = array()) {
    $Http = new CoHttpClient();
    
    // Test connectivity by trying to see if the domain is registered
    $Http->setBaseUrl($this->data['CoMailmanProvisionerTarget']['serverurl']);
    $Http->configAuth('Basic',
                      $this->data['CoMailmanProvisionerTarget']['adminuser'],
                      $this->data['CoMailmanProvisionerTarget']['password']);
    
    $results = $Http->get($Http->buildUrl('/3.1/domains/' . $this->data['CoMailmanProvisionerTarget']['domain']));
    
    // If the domain is not there, we'll get a 404
    if($results->code == 404) {
      $domain = array(
        'mail_host'   => $this->data['CoMailmanProvisionerTarget']['domain'],
        'description' => _txt('pl.mailmanprovisioner.desc.default')
      );
      
      // Add the domain
      $results = $Http->post($Http->buildUrl('/3.1/domains'), $domain);
      
      if($results->code != 201) {
        throw new RuntimeException($results->reasonPhrase);
      }
    }
    
    return true;
  }
  
  /**
   * Delete an Email List.
   * 
   * @since  COmanage Registry v3.1.0
   * @param  Object  $Http            CoHttpClient
   * @param  Integer $id              CoMailmanProvisionerTarget ID
   * @param  String  $domain          Provisioner domain
   * @param  Integer $coEmailListId   CoEmailList ID
   * @param  String  $listname        Mailing list name
   * @param  String  $listDescription Mailing list description
   * @param  Integer $actorCoPersonId CoPerson ID of Actor
   * @throws RuntimeException
   * @return boolean true
   */
  
  protected function deleteList($Http, $id, $coEmailListId, $actorCoPersonId) {
    // Find the mailman list ID
    
    $listId = null;
    
    $args = array();
    $args['conditions']['CoMailmanList.co_mailman_provisioner_target_id'] = $id;
    $args['conditions']['CoMailmanList.co_email_list_id'] = $coEmailListId;
    $args['contain'] = false;
    
    $mailmanList = $this->CoMailmanList->find('first', $args);
    
    if(!empty($mailmanList)) {
      $listId = $mailmanList['CoMailmanList']['mailman_list_identifier'];
      $results = $Http->delete($Http->buildUrl('/3.1/lists/' . $listId));
      
      if($results->code == 204) {
        // Create a history record
        
        $this->CoMailmanList
             ->CoEmailList
             ->HistoryRecord
             ->record(null,
                      null,
                      null,
                      $actorCoPersonId,
                      ActionEnum::CoEmailListDeleted,
                      _txt('pl.mailmanprovisioner.rs.list.del', array($listId)),
                      null,
                      $coEmailListId);
      }
      // Ignore any other results for now
    }
    
    return true;
  }
  
  /**
   * Determine the Mailman ID for a CO Person. If none is known, a records
   * will be created on the Mailman server.
   * 
   * @since  COmanage Registry v3.1.0
   * @param  Object           $Http                   CoHttpClient
   * @param  Integer          $coProvisioningTargetId CoProvisioningTarget ID
   * @param  Integer          $coPersonId             CoPerson ID
   * @param  Array            $primaryName            Array of member's primary name
   * @param  ArrayObject      $prefAddress            Array describing member's preferred address
   * @param  Array            $emailAddresses         Array of member's email addresses
   * @param  Integer          $actorCoPersonId        CoPerson ID of Actor
   * @throws RuntimeException
   * @return String Mailman ID
   */
  
  protected function getPersonMailmanId($Http,
                                        $coProvisioningTargetId,
                                        $coPersonId,
                                        $primaryName,
                                        $prefAddress,
                                        $emailAddresses,
                                        $actorCoPersonId) {    
    $mailmanId = null;
    
    // First see if we have a mailman user ID for this Person
    $args = array();
    $args['conditions']['Identifier.co_person_id'] = $coPersonId;
    $args['conditions']['Identifier.type'] = IdentifierEnum::ProvisioningTarget;
    $args['conditions']['Identifier.co_provisioning_target_id'] = $coProvisioningTargetId;
    $args['contain'] = false;
    
    $mid = $this->CoProvisioningTarget->Co->CoPerson->Identifier->find('first', $args);
    
    if(!empty($mid)) {
      // Grab the mailman user ID
      
      $mailmanId = $mid['Identifier']['identifier'];
    } else {
      // No mailman user ID. In order to create one we need an email address.
      
      $mailmanUser = array(
        'display_name'  => generateCn($primaryName),
        'email'         => $prefAddress['mail']
      );
      
      // Add the user
      $results = $Http->post($Http->buildUrl('/3.1/users'), $mailmanUser);
      
      // Note if the email address happens to already exist, this will throw 400.
      // It's unclear what to do then... we could grab the user ID and link it to
      // this person, but what if a separate person happened to register with the
      // same address? So for now we'll just throw an error.
      
      if($results->code != 201) {
        throw new RuntimeException($results->body);
      }
      
      // The new ID is in the location header
      $mailmanId = basename($results->headers['location']);
      
      // Store it
      $args = array(
        'Identifier' => array(
          'identifier'                => $mailmanId,
          'co_person_id'              => $coPersonId,
          'type'                      => IdentifierEnum::ProvisioningTarget,
          'login'                     => false,
          'status'                    => SuspendableStatusEnum::Active,
          'co_provisioning_target_id' => $coProvisioningTargetId
        )
      );
      
      $this->CoProvisioningTarget->Co->CoPerson->Identifier->clear();
      $this->CoProvisioningTarget->Co->CoPerson->Identifier->save($args);
    }
    
    // Get the list of known email addresses for this user
    $results = $Http->get($Http->buildUrl('/3.1/users/' . $mailmanId . '/addresses'));
    
    $curAddresses = array();
    $curPrefAddress = null;
    
    if($results->code == 200) {
      $json = json_decode($results->body);
      
      if(!empty($json->entries)) {
        foreach($json->entries as $e) {
          $curAddresses[] = $e->email;
          
          // Is this the current preferred address?
          // (!) This uses the new patch described below
          if(isset($e->preferred) && $e->preferred) {
            $curPrefAddress = $e->email;
          }
        }
      }
    }
    
    // For each known CO Person address, add it to the mailman user if not already there
    
    $toadd = array_diff(Hash::extract($emailAddresses, '{n}.mail'), $curAddresses);
    
    foreach($toadd as $a) {
      // Add this address to the user.
      
      $results = $Http->post($Http->buildUrl('/3.1/users/' . $mailmanId . '/addresses'),
                             array('email' => $a));
      // For now we'll ignore the results
    }
    
    // For each mailman address, if it is no longer associated with the CO Person
    // delete it.
    
    $toremove = array_diff($curAddresses, Hash::extract($emailAddresses, '{n}.mail'));
    
    foreach($toremove as $a) {
      // Delete this address.
      
      $results = $Http->delete($Http->buildUrl('/3.1/addresses/' . $a));
      // For now we'll ignore the results
    }
    
    // Set the preferred address, if need be
    // (!) This relies on a custom patch, until the resolution of
    //     https://gitlab.com/mailman/mailman/issues/240
    
    if($prefAddress['mail'] && ($curPrefAddress != $prefAddress['mail'])) {
      $results = $Http->patch($Http->buildUrl('/3.1/users/' . $mailmanId),
                              array('preferred_address' => $prefAddress['mail']));
      
      // We expect a 204 on success, but will accept anything in the 2xx range
      if($results->code < 200 || $results->code > 299) {
        throw new RuntimeException($results->body);
      }
      
      $this->CoMailmanList
           ->CoEmailList
           ->HistoryRecord
           ->record($coPersonId,
                    null,
                    null,
                    $actorCoPersonId,
                    ActionEnum::CoPersonProvisioned,
                    _txt('pl.mailmanprovisioner.rs.pref', array($prefAddress['mail'])));
    }
    
    return $mailmanId;
  }
  
  /**
   * Calculate the preferred email address for a CO Person.
   * 
   * @since  COmanage Registry v3.1.0
   * @param  Array  $emailAddresses      Array of CO Person email addresses
   * @param  String $preferredEmailType  Preferred email address type (or null)
   * @return Array  Array of EmailAddress information for the preferred address
   * @throws InvalidArgumentException
   */
  
  protected function preferredEmailAddress($emailAddresses, $preferredEmailType) {
    // We select a preferred address as follows:
    // - If there is an address of the configured preferred type, select it
    // - Otherwise, the preference is
    //   Mailing List > Delivery > Preferred > Forwarding > Official > Personal > Recovery > any other
    // - If there is more than one address of the selected type, select the lowest ID
    // - For now, we ignore verified status
    
    $eTypes = array(
      EmailAddressEnum::MailingList,
      EmailAddressEnum::Delivery,
      EmailAddressEnum::Preferred,
      EmailAddressEnum::Forwarding,
      EmailAddressEnum::Official,
      EmailAddressEnum::Personal,
      EmailAddressEnum::Recovery
    );
    
    if(empty($emailAddresses)) {
      throw new InvalidArgumentException(_txt('er.mailmanprovisioner.pref.none'));
    }
    
    if($preferredEmailType) {
      // Push this to the top of the list
      array_unshift($eTypes, $preferredEmailType);
    }
    
    // $emailAddresses is usually going to be small (O(1)), so we don't bother re-keying
    // it to a hash.
    
    foreach($eTypes as $et) {
      $found = null;
      
      foreach($emailAddresses as $ea) {
        if(isset($ea['type']) && $ea['type'] == $et) {
          if(!$found
             || $found['id'] > $ea['id']) {
            // This is our current candidate
            $found = $ea;
          }
        }
      }
      
      if($found) {
        return $found;
      }
    }
    
    // If not found, take the address with the lowest ID

    $found = null;
    
    foreach($emailAddresses as $ea) {
      if(!$found
         || $found['id'] > $ea['id']) {
        // This is our current candidate
        $found = $ea;
      }
    }

    return $found;
  }

  /**
   * Provision for the specified CO Person.
   *
   * @since  COmanage Registry v3.1.0
   * @param  Array CO Provisioning Target data
   * @param  ProvisioningActionEnum Registry transaction type triggering provisioning
   * @param  Array Provisioning data, populated with ['CoPerson'] or ['CoGroup']
   * @return Boolean True on success
   * @throws RuntimeException
   */
  
  public function provision($coProvisioningTargetData, $op, $provisioningData) {    
    // First determine what to do
    $deleteList = false;
    $syncList = false;
    $syncListMembers = false;
    $syncPerson = false;
    
    switch($op) {
      case ProvisioningActionEnum::CoEmailListAdded:
      case ProvisioningActionEnum::CoEmailListReprovisionRequested:
      case ProvisioningActionEnum::CoEmailListUpdated:
        // We don't really want to syncListMembers on CoEmailListUpdated unless
        // Status is changing from/to Active, but we don't have a good way to tell
        // that's what changed. So changing Description might take a while because
        // we have to reprovision all users (or at least check their status).
        $syncListMembers = true;
        $syncList = true;
        break;
      case ProvisioningActionEnum::CoEmailListDeleted:
        $deleteList = true;
        break;
      case ProvisioningActionEnum::CoGroupReprovisionRequested:
        // We don't support this yet because we'd need to loop over all lists
        // the group is associated with. That's not necessarily a problem, just
        // some extra work.
        throw new LogicException('NOT IMPLEMENTED');
        break;
      case ProvisioningActionEnum::CoPersonAdded:
      case ProvisioningActionEnum::CoPersonEnteredGracePeriod:
      case ProvisioningActionEnum::CoPersonExpired:
      case ProvisioningActionEnum::CoPersonPetitionProvisioned:
      case ProvisioningActionEnum::CoPersonPipelineProvisioned:
      case ProvisioningActionEnum::CoPersonReprovisionRequested:
      case ProvisioningActionEnum::CoPersonUnexpired:
      case ProvisioningActionEnum::CoPersonUpdated:
        $syncPerson = true;
        break;
      case ProvisioningActionEnum::CoPersonDeleted:
        // We don't do anything here because typically we don't have any useful
        // information to process, and we've probably deprovisioned due to
        // status change/group membership loss/etc.
        break;
      default:
        // Ignore all other actions. Note group membership changes
        // are typically handled as CoPersonUpdated events.
        return true;
        break;
    }
    
    // If we have something to do, build an HTTP Client
    $Http = null;
    
    if($deleteList || $syncList || $syncListMembers || $syncPerson) {
      $Http = new CoHttpClient();
      
      $Http->setBaseUrl($coProvisioningTargetData['CoMailmanProvisionerTarget']['serverurl']);
      $Http->configAuth('Basic',
                        $coProvisioningTargetData['CoMailmanProvisionerTarget']['adminuser'],
                        $coProvisioningTargetData['CoMailmanProvisionerTarget']['password']);
    }
    
    if($deleteList) {
      $this->deleteList($Http,
                        $coProvisioningTargetData['CoMailmanProvisionerTarget']['id'],
                        $provisioningData['CoEmailList']['id'],
                        CakeSession::read('Auth.User.co_person_id'));
    }
    
    if($syncList) {
      $this->syncList($Http,
                      $coProvisioningTargetData['CoMailmanProvisionerTarget']['id'],
                      $coProvisioningTargetData['CoMailmanProvisionerTarget']['domain'],
                      $provisioningData['CoEmailList']['id'],
                      $provisioningData['CoEmailList']['name'],
                      $provisioningData['CoEmailList']['description'],
                      CakeSession::read('Auth.User.co_person_id'));  
    }
    
    if($syncListMembers) {
      $this->syncListMembers($Http,
                             $coProvisioningTargetData['CoMailmanProvisionerTarget']['id'],
                             $coProvisioningTargetData['CoMailmanProvisionerTarget']['co_provisioning_target_id'],
                             $coProvisioningTargetData['CoMailmanProvisionerTarget']['pref_email_type'],
                             $provisioningData['CoEmailList']['id'],
                             $provisioningData['CoEmailList']['status'],
                             CakeSession::read('Auth.User.co_person_id'));  
    }
    
    if($syncPerson) {
      $this->syncPerson($Http,
                        $coProvisioningTargetData['CoMailmanProvisionerTarget']['id'],
                        $coProvisioningTargetData['CoMailmanProvisionerTarget']['co_provisioning_target_id'],
                        $coProvisioningTargetData['CoMailmanProvisionerTarget']['pref_email_type'],
                        $provisioningData['CoPerson']['id'],
                        $provisioningData['PrimaryName'],
                        $provisioningData['EmailAddress'],
                        Hash::extract($provisioningData, 'CoGroupMember.{n}.CoGroup.EmailListMember.0'),
                        Hash::extract($provisioningData, 'CoGroupMember.{n}.CoGroup.EmailListAdmin.0'),
                        Hash::extract($provisioningData, 'CoGroupMember.{n}.CoGroup.EmailListModerator.0'),
                        CakeSession::read('Auth.User.co_person_id'));  
    }
    
    return true;
  }
  
  /**
   * Subscribe a CO Person to a Mailman list.
   * 
   * @since  COmanage Registry v3.1.0
   * @param  Object  $Http            CoHttpClient
   * @param  Integer $coPersonId      CO Person ID of list member
   * @param  String  $mailmanId       Mailman ID for member
   * @param  Integer $coEmailListId   CO Email List ID of membership-eligible group
   * @param  String  $mailmanListId   Mailman ID for list
   * @param  String  $listRole        List role ('member', 'moderator', 'owner')
   * @param  Integer $actorCoPersonId CO Person ID of actor
   * @throws RuntimeException
   * @return Boolean true on success
   */
  
  protected function subscribe($Http,
                               $coPersonId, 
                               $mailmanId,
                               $coEmailListId,
                               $mailmanListId,
                               $listRole,
                               $actorCoPersonId) {
    $results = $Http->post($Http->buildUrl('/3.1/members'),
                           array('list_id' => $mailmanListId,
                                 'subscriber' => $mailmanId,
                                 'role' => $listRole,
                                 'pre_verified' => true,
                                 'pre_confirmed' => true,
                                 'pre_approved' => true));
    
    if($results->code != 409) {
      // 409 is already subscribed, so ignore
      // 201 is success
      
      $cmt = ($results->code >= 200 && $results->code < 300)
              ? _txt('pl.mailmanprovisioner.rs.sub', array($mailmanListId, $listRole))
              : $results->body;
       
      $this->CoMailmanList
           ->CoEmailList
           ->HistoryRecord
           ->record($coPersonId,
                    null,
                    null,
                    $actorCoPersonId,
                    ActionEnum::CoPersonProvisioned,
                    $cmt,
                    null,
                    $coEmailListId);
    }
    
    if($results->code != 409 && $results->code != 201) {
      throw new RuntimeException($results->body);
    }
    
    return true;
  }
  
  /**
   * Synchronize an Email List.
   * 
   * @since  COmanage Registry v3.1.0
   * @param  Object  $Http            CoHttpClient
   * @param  Integer $id              CoMailmanProvisionerTarget ID
   * @param  String  $domain          Provisioner domain
   * @param  Integer $coEmailListId   CoEmailList ID
   * @param  String  $listname        Mailing list name
   * @param  String  $listDescription Mailing list description
   * @param  Integer $actorCoPersonId CoPerson ID of Actor
   * @throws RuntimeException
   * @return boolean true
   */
  
  protected function syncList($Http, $id, $domain, $coEmailListId, $listname, $listDescription, $actorCoPersonId) {
    // Do we have a CoMailmanList record for this list already?
    
    $listId = null;
    
    $args = array();
    $args['conditions']['CoMailmanList.co_mailman_provisioner_target_id'] = $id;
    $args['conditions']['CoMailmanList.co_email_list_id'] = $coEmailListId;
    $args['contain'] = false;
    
    $mailmanList = $this->CoMailmanList->find('first', $args);
    
    if(empty($mailmanList)) {
      // Create the list
      
      $listname = $listname . '@' . $domain;
      
      $results = $Http->post($Http->buildUrl('/3.1/lists'), array('fqdn_listname' => $listname));
      
      if($results->code == 201
         || ($results->code == 400 && $results->body == 'Mailing list exists')) {
        // On 200, the listname is in the location header, but for list exists we need to query for it
        
        $results = $Http->get($Http->buildUrl('/3.1/lists/' . $listname));
        
        if($results->code != 200) {
          throw new RuntimeException($results->body);
        }
        
        $json = json_decode($results->body);
        
        $data = array(
          'co_mailman_provisioner_target_id' => $id,
          'co_email_list_id' => $coEmailListId,
          'mailman_list_identifier' => $json->list_id
        );
        
        $listId = $json->list_id;
        
        $this->CoMailmanList->save($data);
        
        $this->CoMailmanList
             ->CoEmailList
             ->HistoryRecord
             ->record(null,
                      null,
                      null,
                      $actorCoPersonId,
                      ActionEnum::CoEmailListProvisioned,
                      _txt('pl.mailmanprovisioner.rs.list', array($listId)),
                      null,
                      $coEmailListId);
      } else {
        throw new RuntimeException($results->body);
      }
    } else {
      $listId = $mailmanList['CoMailmanList']['mailman_list_identifier'];
    }
    
    // Sync the description (which may already be set). Note we can't set the
    // description when we create the list, and we'd have to make a call to get
    // the current description on update, so we may as well just issue the patch.
    
    $results = $Http->patch($Http->buildUrl('/3.1/lists/' . $listId . '/config'),
                            array('description' => $listDescription));
    // We sort of don't care about $results here
    
    return true;
  }
  
  /**
   * Synchronize an Email List.
   * 
   * @since  COmanage Registry v3.1.0
   * @param  Object                $Http                   CoHttpClient
   * @param  Integer               $id                     CoMailmanProvisionerTarget ID
   * @param  Integer               $coProvisioningTargetId CoProvisioningTarget ID
   * @param  EmailAddressEnum      $preferredEmailType     Preferred email address type, or null
   * @param  Integer               $coEmailListId          CoEmailList ID
   * @param  SuspendableStatusEnum $coEmailListStatus      CoEmailList Status
   * @param  Integer               $actorCoPersonId        CoPerson ID of Actor
   * @throws InvalidArgumentException
   * @throws RuntimeException
   * @return boolean true
   */
  
  protected function syncListMembers($Http,
                                     $id,
                                     $coProvisioningTargetId,
                                     $preferredEmailType,
                                     $coEmailListId,
                                     $coEmailListStatus,
                                     $actorCoPersonId) {
    // Find the mailman list ID for this list
    $listId = null;
    
    $args = array();
    $args['conditions']['CoMailmanList.co_mailman_provisioner_target_id'] = $id;
    $args['conditions']['CoMailmanList.co_email_list_id'] = $coEmailListId;
    $args['contain'] = array('CoEmailList');
    
    $mailmanList = $this->CoMailmanList->find('first', $args);
    
    if(!$mailmanList) {
      throw new InvalidArgumentException(_txt('er.notfound', array(_txt('er.mailmanprovisioner.listid.none'), $coEmailListId)));
    }
    
    $listId = $mailmanList['CoMailmanList']['mailman_list_identifier'];

    // Map COmanage list roles to Mailman list roles
    $listType = array(
      'members_co_group_id' => 'member',
      'admins_co_group_id' => 'owner',
      'moderators_co_group_id' => 'moderator'
    );

    foreach($listType as $gid => $listRole) {
      if(empty($mailmanList['CoEmailList'][$gid])) {
        // No associated list for this role, try the next one
        continue;
      }
      
      // Pull the current subscribers of the list with the specified role
      $results = $Http->get($Http->buildUrl('/3.1/lists/' . urlencode($listId) . '/roster/' . $listRole));
      
      if($results->code != 200) {
        throw new RuntimeException($results->body);
      }
      
      $json = json_decode($results->body, true);
      $currentRosterIds = array();
      
      // We need to reference by user ID for adds but member ID for deletes.
      // Build a custom hash for easier lookup. Note a user ID can have Multiple
      // member entries for different roles.
      if(!empty($json['entries'])) {
        foreach($json['entries'] as $e) {
          $userid = basename($e['user']);
          
          // Since we already have the stripped user id, insert it
          // in $e to make it easier to find later.
          $e['_user_id'] = $userid;
          
          $currentRosterIds[$userid] = $e;
        }
      }
      
      // Track the IDs we've seen
      $groupMembers = array();
      $groupMailmanIds = array();
      
      if($coEmailListStatus == SuspendableStatusEnum::Active) {
        // Pull the membership of the group associated with the list
        $args = array();
        $args['conditions']['CoGroupMember.co_group_id'] = $mailmanList['CoEmailList'][$gid];
        $args['conditions']['CoGroupMember.member'] = true;
        $args['contain']['CoPerson'] = array('EmailAddress',
                                             // We only need Identifiers for this provisioning target.
                                             // While Containable allows us to filter, Changelog doesn't
                                             // currently support that. So we pull all Identifiers and
                                             // filter later with Hash.
                                             'Identifier',
                                             'PrimaryName');
        
        $groupMembers = $this->CoProvisioningTarget
                             ->Co
                             ->CoGroup
                             ->CoGroupMember
                             ->find('all', $args);
        
        foreach($groupMembers as $gm) {
          try {
            // If we can't find a preferred email address, this will throw an exception
            $prefAddress = $this->preferredEmailAddress($gm['CoPerson']['EmailAddress'], $preferredEmailType);
            
            $mailmanId = $this->getPersonMailmanId($Http,
                                                   $coProvisioningTargetId,
                                                   $gm['CoPerson']['id'],
                                                   $gm['CoPerson']['PrimaryName'],
                                                   $prefAddress,
                                                   $gm['CoPerson']['EmailAddress'],
                                                   $actorCoPersonId);
            
            $groupMailmanIds[] = $mailmanId;
            
            if(!isset($currentRosterIds[$mailmanId])) {
              // Subscribe this person to the list
            
              $this->subscribe($Http,
                               $gm['CoPerson']['id'],
                               $mailmanId,
                               $coEmailListId,
                               $listId,
                               $listRole,
                               $actorCoPersonId);
            }
          }
          catch(Exception $e) {
            // Ignore results, on subscribe error a history record will be made.
            // XXX We should probably record history on preferredEmailAddress failure though...
          }
        }
      }
      
      // Unsubscribe anyone who is a member but not in the group
      
      // Extract the mailman user IDs
      $muids = Hash::extract($currentRosterIds, '{s}._user_id');
      
      $unsubscribeIds = array_diff($muids, $groupMailmanIds);
      
      foreach($unsubscribeIds as $mailmanId) {
        // Make sure we have a list membership ID to unsubscribe
        if(!empty($currentRosterIds[$mailmanId]['member_id'])) {
          try {
            // We need to map $mailmanId to a CO Person ID
            $coPersonId = $this->CoProvisioningTarget
                               ->Co
                               ->CoPerson
                               ->Identifier
                               ->field('co_person_id',
                                       array('Identifier.identifier' => $mailmanId,
                                             'Identifier.co_provisioning_target_id' => $coProvisioningTargetId,
                                             'Identifier.type' => IdentifierEnum::ProvisioningTarget));
            
            // Delete keys on the list membership ID, which is not the user ID
            $this->unsubscribe($Http,
                               $coPersonId,
                               $currentRosterIds[$mailmanId]['member_id'],
                               $coEmailListId,
                               $listId,
                               $listRole,
                               $actorCoPersonId);
          }
          catch(Exception $e) {
            // For now we ignore errors and move on
          }      
        }
      }
    }
    
    return true;
  }
  
  /**
   * Synchronize an CO Person with all relevant Email Lists.
   * 
   * @since  COmanage Registry v3.1.0
   * @param  Object           $Http                   CoHttpClient
   * @param  Integer          $id                     CoMailmanProvisionerTarget ID
   * @param  Integer          $coProvisioningTargetId CoProvisioningTarget ID
   * @param  EmailAddressEnum $preferredEmailType     Preferred email address type, or null
   * @param  Integer          $coPersonId             CoPerson ID
   * @param  Array            $primaryName            Array of person's primary name
   * @param  Array            $emailAddresses         Array of person's email addresses
   * @param  Array            $memberCoEmailLists     Array of person's email lists as implied by groups
   * @param  Array            $adminCoEmailLists      Array of person's admin email lists as implied by groups
   * @param  Array            $moderatorCoEmailLists  Array of person's moderator email lists as implied by groups
   * @param  Integer          $actorCoPersonId        CoPerson ID of Actor
   * @throws RuntimeException
   * @return boolean          true
   */
  
  protected function syncPerson($Http,
                                $id,
                                $coProvisioningTargetId,
                                $preferredEmailType,
                                $coPersonId,
                                $primaryName,
                                $emailAddresses,
                                $memberCoEmailLists,
                                $adminCoEmailLists,
                                $moderatorCoEmailLists,
                                $actorCoPersonId) {
    // If we can't find a preferred email address, this will throw an exception
    $prefAddress = $this->preferredEmailAddress($emailAddresses, $preferredEmailType);
    
    $mailmanId = $this->getPersonMailmanId($Http,
                                           $coProvisioningTargetId,
                                           $coPersonId,
                                           $primaryName,
                                           $prefAddress,
                                           $emailAddresses,
                                           $actorCoPersonId);
    
    // We now need the set of mailmain list IDs. We could pull them as needed, but
    // for now we can pull them all at once since we don't expect that many.
    
    $listIds = null;
    
    $args = array();
    $args['conditions']['CoMailmanList.co_mailman_provisioner_target_id'] = $id;
    $args['fields'] = array('CoMailmanList.co_email_list_id', 'CoMailmanList.mailman_list_identifier');
    
    $listIds = $this->CoMailmanList->find('list', $args);
    // We'll need the inverse list, too
    $mailmanListIds = array_flip($listIds);
    
    // Get the current lists this user is a member of. We assume the preferred address
    // is the membership address of record.
    
    $curMemberships = array();
    
    $results = $Http->get($Http->buildUrl('/3.1/addresses/' . $prefAddress['mail'] . '/memberships'));
    
    // Without this flag json_decode interprets the int as a float.
    // (This shouldn't be needed anymore since mailman > 3.1 no longer uses ints as IDs,
    // but since a hex number can still be a long int, we'll leave it here.)
    $json = json_decode($results->body, false, 512, JSON_BIGINT_AS_STRING);
    
    if(!empty($json->entries)) {
      foreach($json->entries as $m) {
        // A person can have multiple memberships, one for each roles.
        // The member_id uniquely identifies list+user+role.
        $curMemberships[ $m->list_id ][ $m->role ] = $m->member_id;
      }
    }
    
    // Add memberships for lists that have a group membership but no list membership
    
    // Map COmanage list roles to Mailman list roles
    $listType = array(
      'memberCoEmailLists' => 'member',
      'adminCoEmailLists' => 'owner',
      'moderatorCoEmailLists' => 'moderator'
    );
    
    foreach($listType as $coEmailLists => $listRole) {
      foreach($$coEmailLists as $l) {
        // $l['id'] is actually CoEmailList.id
        if(isset($listIds[ $l['id'] ])) {
          $mailmanListId = $listIds[ $l['id'] ];
          
          if(!isset($curMemberships[$mailmanListId][$listRole])) {
            try {
              // Not currently subscribed with this role, subscribe
              $this->subscribe($Http,
                               $coPersonId,
                               $mailmanId,
                               $l['id'],
                               $mailmanListId,
                               $listRole,
                               $actorCoPersonId);
            }
            catch(Exception $e) {
              // For now we ignore errors (they'll be captured in history) and move on
            }
          }
        }
        // else no matching mailing list, maybe throw error?
      }
    }
    
    // Remove memberships for lists that don't have a corresponding group membership
    
    foreach($curMemberships as $mailmanListId => $m) {
      foreach($m as $listRole => $membershipId) {
        if(isset($mailmanListIds[$mailmanListId])) {
          // Map to CO Email List ID
          $lid = $mailmanListIds[$mailmanListId];
          
          // Figure out the array for this role
          $coEmailLists = array_search($listRole, $listType);
          
          if(!Hash::check($$coEmailLists, '{n}[id='.$lid.'].id')) {
            try {
              // Delete keys on the list membership ID, which is not the user ID
              $this->unsubscribe($Http,
                                 $coPersonId,
                                 $membershipId,
                                 $lid,
                                 $mailmanListId,
                                 $listRole,
                                 $actorCoPersonId);
            }
            catch(Exception $e) {
              // For now we ignore errors and move on
            }
          }
        }
      }
    }
    
    return true;
  }
  
  /**
   * Unsubscribe a CO Person from a Mailman list.
   * 
   * @since  COmanage Registry v3.1.0
   * @param  Object  $Http            CoHttpClient
   *
   * 
   * @param  Integer $coPersonId      CO Person ID of list member
   * @param  String  $mailmanId       Mailman ID for member
   * @param  Integer $coEmailListId   CO Email List ID of membership-eligible group
   * @param  String  $mailmanListId   Mailman ID for list
   * @param  String  $listRole        List role ('member', 'moderator', 'owner') to remove
   * @param  Integer $actorCoPersonId CO Person ID of actor
   * @throws RuntimeException
   * @return Boolean true on success
   */
  
  protected function unsubscribe($Http,
                                 $coPersonId,
                                 $membershipId,
                                 $coEmailListId,
                                 $mailmanListId,
                                 $listRole,
                                 $actorCoPersonId) {
    // Delete keys on the list membership ID, which is not the user ID
    // (membership ID = user ID + list ID + list role)
    $results = $Http->delete($Http->buildUrl('/3.1/members/' . $membershipId));
    
    // 204 on success
    $cmt = ($results->code >= 200 && $results->code < 300)
            ? _txt('pl.mailmanprovisioner.rs.unsub', array($mailmanListId, $listRole))
            : $results->body;
    
    $this->CoMailmanList
         ->CoEmailList
         ->HistoryRecord
         ->record($coPersonId,
                  null,
                  null,
                  $actorCoPersonId,
                  ActionEnum::CoPersonProvisioned,
                  $cmt,
                  null,
                  $coEmailListId);
    
    return true;
  }
  
  /**
    * Test a Mailman server to verify that the connection available is valid.
    *
    * @since  COmanage Registry v3.1.0
    * @param  String Server URL
    * @param  String Bind DN
    * @param  String Password
    * @param  String Base DN (People)
    * @param  String Base DN (Group)
    * @return Boolean True if parameters are valid
    * @throws RuntimeException
    */

  public function verifyMailmanServer($serverUrl, $adminUser, $password) {
    $Http = new CoHttpClient();
    
    // Test connectivity by trying to get a list of domains
    $Http->setBaseUrl($serverUrl);
    $Http->configAuth('Basic', $adminUser, $password);
    
    $results = $Http->get($Http->buildUrl('/3.1/domains'));
    
    if($results->code != 200) {
      $jres = json_decode($results->body);
      
      throw new RuntimeException($jres->description);
    }
    
    return true;
  }
}
