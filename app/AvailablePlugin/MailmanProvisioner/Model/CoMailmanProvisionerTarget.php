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
    
    $results = $Http->get($Http->buildUrl('/3.0/domains/' . $this->data['CoMailmanProvisionerTarget']['domain']));
    
    // If the domain is not there, we'll get a 404
    if($results->code == 404) {
      $domain = array(
        'mail_host'   => $this->data['CoMailmanProvisionerTarget']['domain'],
        'description' => _txt('pl.mailmanprovisioner.desc.default')
      );
      
      // Add the domain
      $results = $Http->post($Http->buildUrl('/3.0/domains'), $domain);
      
      if($results->code != 201) {
        throw new RuntimeException($results->reasonPhrase);
      }
    }
    
    return true;
  }
  
  /**
   * Calculate the preferred email address for a CO Person.
   * 
   * @since  COmanage Registry v3.1.0
   * @param  Array $emailAddresses           Array of CO Person email addresses
   * @param  Array $coProvisioningTargetData CO Provisioning Target data
   * @return Array Array of EmailAddress information for the preferred address
   * @throws InvalidArgumentException
   */
  
  protected function preferredEmailAddress($emailAddresses, $coProvisioningTargetData) {
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
    
    if(!empty($coProvisioningTargetData['pref_email_type'])) {
      // Push this to the top of the list
      array_unshift($eTypes, $coProvisioningTargetData['pref_email_type']);
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
    $syncList = false;
    $syncPerson = false;
    
    switch($op) {
      case ProvisioningActionEnum::CoEmailListAdded:
      case ProvisioningActionEnum::CoEmailListReprovisionRequested:
      case ProvisioningActionEnum::CoEmailListUpdated:
        $syncList = true;
        break;
      case ProvisioningActionEnum::CoEmailListDeleted:
//        $syncList = true;
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
//        $syncPerson = true;
        break;
      default:
        // Ignore all other actions. Note group membership changes
        // are typically handled as CoPersonUpdated events.
        return true;
        break;
    }
    
    // If we have something to do, build an HTTP Client
    $Http = null;
    
    if($syncList || $syncPerson) {
      $Http = new CoHttpClient();
  
      $Http->setBaseUrl($coProvisioningTargetData['CoMailmanProvisionerTarget']['serverurl']);
      $Http->configAuth('Basic',
                        $coProvisioningTargetData['CoMailmanProvisionerTarget']['adminuser'],
                        $coProvisioningTargetData['CoMailmanProvisionerTarget']['password']);
    }
    
    if($syncList) {
      // Do we have a CoMailmanList record for this list already?
      
      $listId = null;
      
      $args = array();
      $args['conditions']['CoMailmanList.co_mailman_provisioner_target_id'] = $coProvisioningTargetData['CoMailmanProvisionerTarget']['id'];
      $args['conditions']['CoMailmanList.co_email_list_id'] = $provisioningData['CoEmailList']['id'];
      $args['contain'] = false;
      
      $mailmanList = $this->CoMailmanList->find('first', $args);
      
      if(empty($mailmanList)) {
        // Create the list
        
        $listname = $provisioningData['CoEmailList']['name'] . '@'
                  . $coProvisioningTargetData['CoMailmanProvisionerTarget']['domain'];
        
        $results = $Http->post($Http->buildUrl('/3.0/lists'), array('fqdn_listname' => $listname));
        
        if($results->code == 201
           || ($results->code == 400 && $results->body == 'Mailing list exists')) {
          // On 200, the listname is in the location header, but for list exists we need to query for it
          
          $results = $Http->get($Http->buildUrl('/3.0/lists/' . $listname));
          
          if($results->code != 200) {
            throw new RuntimeException($results->body);
          }
          
          $json = json_decode($results->body);
          
          $data = array(
            'co_mailman_provisioner_target_id' => $coProvisioningTargetData['CoMailmanProvisionerTarget']['id'],
            'co_email_list_id' => $provisioningData['CoEmailList']['id'],
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
                        CakeSession::read('Auth.User.co_person_id'),
                        ActionEnum::CoEmailListProvisioned,
                        _txt('pl.mailmanprovisioner.rs.list', array($listId)),
                        null,
                        $provisioningData['CoEmailList']['id']);
        } else {
          throw new RuntimeException($results->body);
        }
      } else {
        $listId = $mailmanList['CoMailmanList']['mailman_list_identifier'];
      }
      
      // Sync the description (which may already be set). Note we can't set the
      // description when we create the list, and we'd have to make a call to get
      // the current description on update, so we may as well just issue the patch.
      
      $results = $Http->patch($Http->buildUrl('/3.0/lists/' . $listId . '/config'),
                              array('description' => $provisioningData['CoEmailList']['description']));
      // We sort of don't care about $results here
    }
    
    if($syncPerson) {
      $mailmanId = null;
      
      // If we can't find a preferred email address, this will throw an exception
        $prefAddress = $this->preferredEmailAddress($provisioningData['EmailAddress'],
                                                    $coProvisioningTargetData);
      
      // First see if we have a mailman user ID for this Person
      $args = array();
      $args['conditions']['Identifier.co_person_id'] = $provisioningData['CoPerson']['id'];
      $args['conditions']['Identifier.type'] = IdentifierEnum::ProvisioningTarget;
      $args['conditions']['Identifier.co_provisioning_target_id'] = $coProvisioningTargetData['CoMailmanProvisionerTarget']['co_provisioning_target_id'];
      $args['contain'] = false;
      
      $id = $this->CoProvisioningTarget->Co->CoPerson->Identifier->find('first', $args);
      
      if(!empty($id)) {
        // Grab the mailman user ID
        
        $mailmanId = $id['Identifier']['identifier'];
      } else {
        // No mailman user ID. In order to create one we need an email address.
        
        $mailmanUser = array(
          'display_name'  => generateCn($provisioningData['PrimaryName']),
          'email'         => $prefAddress['mail']
        );
        
        // Add the user
        $results = $Http->post($Http->buildUrl('/3.0/users'), $mailmanUser);
        
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
            'co_person_id'              => $provisioningData['CoPerson']['id'],
            'type'                      => IdentifierEnum::ProvisioningTarget,
            'login'                     => false,
            'status'                    => SuspendableStatusEnum::Active,
            'co_provisioning_target_id' => $coProvisioningTargetData['CoMailmanProvisionerTarget']['co_provisioning_target_id']
          )
        );
        
        $this->CoProvisioningTarget->Co->CoPerson->Identifier->clear();
        $this->CoProvisioningTarget->Co->CoPerson->Identifier->save($args);
      }
      
      // Get the list of known email addresses for this user
      $results = $Http->get($Http->buildUrl('/3.0/users/' . $mailmanId . '/addresses'));
      
      $curAddresses = array();
      $curPrefAddress = null;
      
      if($results->code == 200) {
        $json = json_decode($results->body);
        
        if(!empty($json->entries)) {
          foreach($json->entries as $e) {
            $curAddresses[] = $e->email;
            
            // Is this the current preferred address?
            // XXX This uses the new patch
            if(isset($e->preferred) && $e->preferred) {
              $curPrefAddress = $e->email;
            }
          }
        }
      }
      
      // For each known CO Person address, add it to the mailman user if not already there
      
      foreach($provisioningData['EmailAddress'] as $ea) {
        if(!in_array($ea['mail'], $curAddresses)) {
          // Add this address to the user.
          
          $results = $Http->post($Http->buildUrl('/3.0/users/' . $mailmanId . '/addresses'),
                                 array('email' => $ea['mail']));
          // For now we'll ignore the results
        }
      }
      
      // Set the preferred address, if need be
      // (!) This relies on a custom patch, until the resolution of
      //     https://gitlab.com/mailman/mailman/issues/240
      
      if($curPrefAddress && ($curPrefAddress != $prefAddress['mail'])) {
        $results = $Http->patch($Http->buildUrl('/3.0/users/' . $mailmanId),
                                array('preferred_address' => $prefAddress['mail']));
        
        // We expect a 204 on success, but will accept anything in the 2xx range
        if($results->code < 200 || $results->code > 299) {
          throw new RuntimeException($results->body);
        }
        
        $this->CoMailmanList
             ->CoEmailList
             ->HistoryRecord
             ->record($provisioningData['CoPerson']['id'],
                      null,
                      null,
                      CakeSession::read('Auth.User.co_person_id'),
                      ActionEnum::CoPersonProvisioned,
                      _txt('pl.mailmanprovisioner.rs.pref', array($prefAddress['mail'])));
      }
      
      // We now need the set of mailmain list IDs. We could pull them as needed, but
      // for now we can pull them all at once since we don't expect that many.
      
      $listIds = null;
      
      $args = array();
      $args['conditions']['CoMailmanList.co_mailman_provisioner_target_id'] = $coProvisioningTargetData['CoMailmanProvisionerTarget']['id'];
      $args['fields'] = array('CoMailmanList.co_email_list_id', 'CoMailmanList.mailman_list_identifier');
      
      $listIds = $this->CoMailmanList->find('list', $args);
      // We'll need the inverse list, too
      $mailmanListIds = array_flip($listIds);
      
      // Get the current lists this user is a member of. We assume the preferred address
      // is the membership address of record.
      
      $curGroups = Hash::extract($provisioningData, 'CoGroupMember.{n}.CoGroup.EmailListMember.0');
      $curMemberships = array();
      
      $results = $Http->get($Http->buildUrl('/3.0/addresses/' . $prefAddress['mail'] . '/memberships'));
      
      // Without this flag json_decode interprets the int as a float
      $json = json_decode($results->body, false, 512, JSON_BIGINT_AS_STRING);
      
      if(!empty($json->entries)) {
        foreach($json->entries as $m) {
          $curMemberships[ $m->list_id ] = array(
            'memberId' => $m->member_id,
            'role' => $m->role
          );
        }
      }
     
      // Add memberships for lists that have a group membership but no list membership
      
      foreach($curGroups as $g) {
        if(isset($listIds[ $g['id'] ])) {
          $mailmanListId = $listIds[ $g['id'] ];
          
          if(!isset($curMemberships[$mailmanListId])
             || $curMemberships[$mailmanListId]['role'] != 'member') {
            // Not currently a member, create a membership
            $results = $Http->post($Http->buildUrl('/3.0/members'),
                                   array('list_id' => $mailmanListId,
                                         'subscriber' => $mailmanId,
                                         'role' => 'member',
                                         'pre_verified' => true,
                                         'pre_confirmed' => true,
                                         'pre_approved' => true));
            
            if($results->code != 409) {
              // 409 is already subscribed, so ignore
              // 201 is success
              
              $cmt = ($results->code >= 200 && $results->code < 300)
                      ? _txt('pl.mailmanprovisioner.rs.sub', array($mailmanListId))
                      : $results->body;
              
              $this->CoMailmanList
                   ->CoEmailList
                   ->HistoryRecord
                   ->record($provisioningData['CoPerson']['id'],
                            null,
                            null,
                            CakeSession::read('Auth.User.co_person_id'),
                            ActionEnum::CoPersonProvisioned,
                            $cmt,
                            null,
                            $g['id']);
            }
          }
        }
        // else no matching mailing list, maybe throw error?
      }
      
      // Remove memberships for lists that don't have a corresponding group membership
      
      foreach($curMemberships as $mailmanListId => $m) {
        if(isset($mailmanListIds[$mailmanListId])) {
          // Map to CO Email List ID
          
          $gid = $mailmanListIds[$mailmanListId];
          
          if(!Hash::check($curGroups, '{n}[id='.$gid.'].id')
            // CO Person is no longer a member of the group
             && !empty($curMemberships[$mailmanListId]['memberId'])) {
            // Delete keys on the list membership ID, which is not the user ID
            $results = $Http->delete($Http->buildUrl('/3.0/members/' . $curMemberships[$mailmanListId]['memberId']));
            
            // 204 on success
            $cmt = ($results->code >= 200 && $results->code < 300)
                    ? _txt('pl.mailmanprovisioner.rs.unsub', array($mailmanListId))
                    : $results->body;
            
            $this->CoMailmanList
                 ->CoEmailList
                 ->HistoryRecord
                 ->record($provisioningData['CoPerson']['id'],
                          null,
                          null,
                          CakeSession::read('Auth.User.co_person_id'),
                          ActionEnum::CoPersonProvisioned,
                          $cmt,
                          null,
                          $gid);
          }
        }
      }
    }
    
    return true;
  }
}
