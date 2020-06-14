<?php
/**
 * COmanage Registry CO Crowd Provisioner Target Model
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
 * @since         COmanage Registry v3.2.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::uses("CoProvisionerPluginTarget", "Model");

class CoCrowdProvisionerTarget extends CoProvisionerPluginTarget {
  // Define class name for cake
  public $name = "CoCrowdProvisionerTarget";
  
  // Add behaviors
  public $actsAs = array('Containable');
  
  // Association rules from this model to other models
  public $belongsTo = array("CoProvisioningTarget");
  
  // Default display field for cake generated views
  public $displayField = "server_id";
  
  // Request Http servers
  public $cmServerType = ServerEnum::HttpServer;
  
  protected $Http = null;
  
  // Validation rules for table elements
  public $validate = array(
    'co_provisioning_target_id' => array(
      'rule' => 'numeric',
      'required' => true
    ),
    'server_id' => array(
      'rule' => 'numeric',
      'required' => true
    ),
    'username_type' => array(
      'content' => array(
        'rule' => array('validateExtendedType',
                        array('attribute' => 'Identifier.type',
                              'default' => array(IdentifierEnum::ePPN,
                                                 IdentifierEnum::ePTID,
                                                 IdentifierEnum::Mail,
                                                 IdentifierEnum::OpenID,
                                                 IdentifierEnum::UID))),
        'required' => true,
        'allowEmpty' => false
      )
    )
  );
  
  /* We could sanity check the server configuration here, but we'd need to
     pull the HttpServer object to get it.
    
  public function beforeSave($options = array()) {
    
    return true;
  }*/
  
  /**
   * Provision for the specified CO Person.
   *
   * @since  COmanage Registry v3.2.0
   * @param  Array CO Provisioning Target data
   * @param  ProvisioningActionEnum Registry transaction type triggering provisioning
   * @param  Array Provisioning data, populated with ['CoPerson'] or ['CoGroup']
   * @return Boolean True on success
   * @throws RuntimeException
   */
  
  public function provision($coProvisioningTargetData, $op, $provisioningData) {
    // First determine what to do
    $deleteGroup = false;
    $syncGroup = false;
    $deletePerson = false;
    $syncPerson = false;

    switch($op) {
      case ProvisioningActionEnum::CoGroupAdded:
      case ProvisioningActionEnum::CoGroupUpdated:
      case ProvisioningActionEnum::CoGroupReprovisionRequested:
        $syncGroup = true;
        break;
      case ProvisioningActionEnum::CoGroupDeleted:
        $deleteGroup = true;
        break;
      case ProvisioningActionEnum::CoPersonAdded:
      case ProvisioningActionEnum::CoPersonEnteredGracePeriod:
      case ProvisioningActionEnum::CoPersonExpired:
      case ProvisioningActionEnum::CoPersonPetitionProvisioned:
      case ProvisioningActionEnum::CoPersonPipelineProvisioned:
      case ProvisioningActionEnum::CoPersonReprovisionRequested:
      case ProvisioningActionEnum::CoPersonUnexpired:
      case ProvisioningActionEnum::CoPersonUpdated:
        if($provisioningData['CoPerson']['status'] == StatusEnum::Deleted) {
          $deletePerson = true;
        } else {
          $syncPerson = true;
        }
        break;
      case ProvisioningActionEnum::CoPersonDeleted:
        $deletePerson = true;
        break;
      default:
        // Ignore all other actions. Note group membership changes
        // are typically handled as CoPersonUpdated events.
        return true;
        break;
    }
    
    if($deleteGroup || $syncGroup || $deletePerson || $syncPerson) {
      // Pull the Server configuation
      
      $args = array();
      $args['conditions']['Server.id'] = $coProvisioningTargetData['CoCrowdProvisionerTarget']['server_id'];
      $args['conditions']['Server.status'] = SuspendableStatusEnum::Active;
      $args['contain'] = array('HttpServer');

      $CoProvisioningTarget = new CoProvisioningTarget();
      $srvr = $CoProvisioningTarget->Co->Server->find('first', $args);
      
      if(empty($srvr)) {
        throw new InvalidArgumentException(_txt('er.notfound', array(_txt('ct.http_servers.1'), $coProvisioningTargetData['CoCrowdProvisionerTarget']['server_id'])));
      }
      
      $this->Http = new CoHttpClient(array(
        'ssl_verify_peer' => $srvr['HttpServer']['ssl_verify_peer'],
        'ssl_verify_host' => $srvr['HttpServer']['ssl_verify_host']
      ));
      
      $this->Http->setBaseUrl($srvr['HttpServer']['serverurl']);
      $this->Http->setRequestOptions(array(
        'header' => array(
          'Accept'        => 'application/json',
          'Content-Type'  => 'application/json'
        )
      ));
      $this->Http->configAuth('Basic',
                              $srvr['HttpServer']['username'],
                              $srvr['HttpServer']['password']);
    }
    
    if($deleteGroup) {
      $this->deleteGroup($provisioningData['CoGroup']);
    }
    
    if($deletePerson) {
      $this->deletePerson($coProvisioningTargetData['CoCrowdProvisionerTarget']['co_provisioning_target_id'],
                          $coProvisioningTargetData['CoCrowdProvisionerTarget']['username_type'],
                          $provisioningData['CoPerson']['id'],
                          $provisioningData['Identifier']);
    }

    if($syncGroup) {
      $this->syncGroup($coProvisioningTargetData['CoCrowdProvisionerTarget']['co_provisioning_target_id'],
                       $provisioningData['CoGroup'],
                       $coProvisioningTargetData['CoCrowdProvisionerTarget']['username_type']);
    }
    
    if($syncPerson) {
      $this->syncPerson($coProvisioningTargetData['CoCrowdProvisionerTarget']['co_provisioning_target_id'],
                        $coProvisioningTargetData['CoCrowdProvisionerTarget']['username_type'],
                        $provisioningData['CoPerson']['id'],
                        $provisioningData['CoPerson']['status'],
                        $provisioningData['PrimaryName'],
                        $provisioningData['EmailAddress'],
                        $provisioningData['Identifier'],
                        $provisioningData['CoGroupMember']);
    }
  }
  
  /**
   * Delete a CO Group from Crowd.
   * 
   * @since  COmanage Registry v3.2.0
   * @param  Array            $coGroup CoGroup
   * @throws RuntimeException
   * @return boolean          true
   */
  
  protected function deleteGroup($coGroup) {
    $response = $this->Http->delete("/usermanagement/1/group?groupname=" . urlencode($coGroup['name']));
    
    if($response->code != 204) {
      throw new RuntimeException($response->reasonPhrase);
    }
    
    return true;
  }
  
  /**
   * Delete a CO Person from Crowd.
   * 
   * @since  COmanage Registry v3.2.0
   * @param  Integer          $coProvisioningTargetId CoProvisioningTarget ID
   * @param  IdentifierEnum   $usernameType           Username type
   * @param  Integer          $coPersonId             CoPerson ID
   * @param  Array            $identifiers            Array of person's identifiers
   * @throws RuntimeException
   * @return boolean          true
   */
  
  protected function deletePerson($coProvisioningTargetId,
                                  $usernameType,
                                  $coPersonId,
                                  $identifiers) {
    // Find the identifier of the requested username type
    // Note similar logic in syncPerson
    $ids = Hash::extract($identifiers, '{n}[type='.$usernameType.']');

    if(empty($ids)) {
      throw new RuntimeException(_txt('er.crowdprovisioner.id.none', array($usernameType)));
    }
    
    $response = $this->Http->delete("/usermanagement/1/user?username=" . urlencode($ids[0]['identifier']));
    
    if($response->code != 204) {
      throw new RuntimeException($response->reasonPhrase);
    }
    
    if($coPersonId) {
      // Delete any linked Crowd ID since it's no longer valid.
      
      $args = array();
      $args['conditions']['Identifier.co_person_id'] = $coPersonId;
      $args['conditions']['Identifier.type'] = IdentifierEnum::ProvisioningTarget;
      $args['conditions']['Identifier.co_provisioning_target_id'] = $coProvisioningTargetId;
      
      $this->CoProvisioningTarget->Co->CoPerson->Identifier->deleteAll($args['conditions'], false, true);
    }

    return true;
  }
  
  /**
   * Synchronize a CO Group.
   * 
   * @since  COmanage Registry v3.2.0
   * @param  Integer          $coProvisioningTargetId CoProvisioningTarget ID
   * @param  Array            $coGroup                CoGroup
   * @param  IdentifierEnum   $usernameType           Username type
   * @throws RuntimeException
   * @return boolean          true
   */
  
  protected function syncGroup($coProvisioningTargetId,
                               $coGroup,
                               $usernameType) {
    if(!empty($coGroup['CoPerson'])) {
      // This is a single-person operation, probably a membership add or removal.
      // Since we probably handled this already via a CoPerson update, we'll skip
      // doing anything here.
      
      return true;
    }
    
    // We can't properly handle renaming for two reasons:
    // (1) Crowd doesn't support renaming a group (even via the UI)
    // (2) We aren't tracking the known Crowd name, so we can't detect a rename
    //     (Fixing this either requires a plugin specific tracking table, or
    //      adding Identifiers to groups (CO-1615) so the provisioning infrastructure
    //      can manage tracking the identifer, like it does for CO People.)
    //
    // If we addressed (2), we could delete the old group, or at least deprovision
    // the people associated with the old group. (Currently, we'll create the new
    // group and leave the old group untouched. As each person is reprovisioned,
    // the old group membership will be deleted.)
    
    $group = array(
      'name'        => $coGroup['name'],
      'description' => $coGroup['description'],
      'type'        => 'GROUP',
      'active'      => ($coGroup['status'] == SuspendableStatusEnum::Active)
    );
    
    $response = $this->Http->get("/usermanagement/1/group",
                                 array('groupname' => $coGroup['name']));
    
    if($response->code == 404) {
      // Group not found, create it
      
      $response = $this->Http->post("/usermanagement/1/group",
                                    json_encode($group));
      
      if($response->code != 201) {
        throw new RuntimeException($response->reasonPhrase);
      }
    } elseif($response->code == 200) {
      // Update the Group
      
      $response = $this->Http->put("/usermanagement/1/group?groupname=" . urlencode($coGroup['name']),
                                    json_encode($group));
      
      if($response->code != 200) {
        // XXX we need to log more context than reasonPhrase
        $this->log($response->reasonPhrase);
        throw new RuntimeException($response->reasonPhrase);
      }
    } else {
      // Throw an error, might be better to examine body but we don't really know the format
      throw new RuntimeException($response->reasonPhrase);
    }
    
    // Pull the list of CO Group Members
    
    // Note unlike the others this array is NOT keyed on the identifier, it is index (integer) keyed
    $curMembers = array();
    
    // Note findForCoGroup supports pagination, in case we need to scale there
    $gmembers = $this->CoProvisioningTarget->Co->CoGroup->CoGroupMember->findForCoGroup($coGroup['id'], null, null, null, true);
    
    if(!empty($gmembers)) {
      // We need to convert from groupmember IDs to identifiers as used by crowd
      
      $curMembers = $this->CoProvisioningTarget->Co->CoGroup->CoGroupMember->mapCoGroupMembersToIdentifiers($gmembers, $usernameType);
    }
    
    // Pull the list of Crowd Group Members
    
    $crowdMembers = array();
    
    $response = $this->Http->get("/usermanagement/1/group/user/direct",
                                 array("groupname" => $coGroup['name']));
    
    $users = json_decode($response->body);
    
    foreach($users->users as $u) {
      $crowdMembers[ $u->name ] = true;
    }
    
    // Add memberships for anyone missing them
    
    foreach($curMembers as $m) {
      if(isset($crowdMembers[$m])) {
        // Person is already a member, so skip this one
        continue;
      }
      
      // We assume the person exists already, but if they don't (typically because
      // the provisioner is deployed after some people were created) we won't do
      // anything to create them. Crowd will return a 400 that we'll ignore.
      
      $response = $this->Http->post("/usermanagement/1/group/user/direct?groupname=" . urlencode($coGroup['name']),
                                    json_encode(array("name" => $m)));
      
      if($response->code != 201
         // 400 indicates user does not exist, which we'll ignore at least for now
         && $response->code != 400) {
        throw new RuntimeException($response->reasonPhrase);
      }
    }
    
    // Remove memberships from anyone no longer in the Registry group
    
    foreach(array_keys($crowdMembers) as $m) {
      if(!in_array($m, $curMembers)) {
        $response = $this->Http->delete("/usermanagement/1/user/group/direct?username=" . urlencode($m) . "&groupname=" . urlencode($coGroup['name']));
      }
    }
    
    return true;
  }
  
  /**
   * Synchronize a CO Person.
   * 
   * @since  COmanage Registry v3.2.0
   * @param  Integer          $coProvisioningTargetId CoProvisioningTarget ID
   * @param  IdentifierEnum   $usernameType           Username type
   * @param  Integer          $coPersonId             CoPerson ID
   * @param  JobStatusEnum    $coPersonStatus         CoPerson Status
   * @param  Array            $primaryName            Array of person's primary name
   * @param  Array            $emailAddresses         Array of person's email addresses
   * @param  Array            $identifiers            Array of person's identifiers
   * @param  Array            $groupMembers           Array of person's group memberships
   * @throws RuntimeException
   * @return boolean          true
   */
  
  protected function syncPerson($coProvisioningTargetId,
                                $usernameType,
                                $coPersonId,
                                $coPersonStatus,
                                $primaryName,
                                $emailAddresses,
                                $identifiers,
                                $groupMembers) {
    // Find the identifier of the requested username type
    // Note similar logic in deletePerson
    $ids = Hash::extract($identifiers, '{n}[type='.$usernameType.']');

    if(empty($ids)) {
      throw new RuntimeException(_txt('er.crowdprovisioner.id.none', array($usernameType)));
    }
      
    if(empty($emailAddresses[0]['mail'])) {
      // Crowd requires an email address
      throw new RuntimeException(_txt('er.crowdprovisioner.mail.none'));
    }
    
    $crowdUsername = $ids[0]['identifier'];
    $crowdGroups = array();
    
    // Construct the user data
    $user = array(
      'name' => $crowdUsername,
      // XXX We could use Authenticator passwords here...
      'password' => array('value' => bin2hex(random_bytes(8))),
      'active' => (in_array($coPersonStatus, array(StatusEnum::Active, StatusEnum::GracePeriod))
                  ? 'true' : 'false'),
      'first-name' => $primaryName['given'],
      // Crowd requires a last name, but we may not have one
      'last-name' => !empty($primaryName['family']) ? $primaryName['family'] : '.',
      'display-name' => generateCn($primaryName),
      // XXX Which email? For now just the first...
      'email' => $emailAddresses[0]['mail']
    );
    
    // Do we already have a Crowd ID for this person?
    
    $args = array();
    $args['conditions']['Identifier.co_person_id'] = $coPersonId;
    $args['conditions']['Identifier.type'] = IdentifierEnum::ProvisioningTarget;
    $args['conditions']['Identifier.co_provisioning_target_id'] = $coProvisioningTargetId;
    $args['contain'] = false;
    
    $cid = $this->CoProvisioningTarget->Co->CoPerson->Identifier->find('first', $args);
    
    if(!empty($cid)) {
      // Grab the Crowd user ID and then update the record
      
      // The identifier is of the form directoryid:uuid, but we only need directoryid.
      $crowdId = $cid['Identifier']['identifier'];
      
      // $crowdId can only be used to GET a user, so we pull the current record
      // to see if we need to change the username before we do anythnig else.
      // Note this adds an API call that most of the time isn't needed... we could
      // also skip this and require manual intervention to change a username.
      
      $response = $this->Http->get("/usermanagement/1/user?key=" . urlencode($crowdId));
      
      if($response->code != 200) {
        throw new RuntimeException($response->reasonPhrase);
      }
      
      $rbody = json_decode($response->body);
      
      // The current name is in $rbody->name
      if(!empty($rbody->name) && $rbody->name != $crowdUsername) {
        // Rename the user
        
        $newname = array(
          'new-name' => $crowdUsername
        );

        $response = $this->Http->post("/usermanagement/1/user/rename?username=" . urlencode($rbody->name),
                                      json_encode($newname));
        
        if($response->code != 200) {
          throw new RuntimeException($response->reasonPhrase);
        }
      }
      
      $response = $this->Http->put("/usermanagement/1/user?username=" . urlencode($crowdUsername),
                                   json_encode($user));
      
      if($response->code != 204) {
        throw new RuntimeException($response->reasonPhrase);
      }
      
      // Get the current groups for the user
      
      // XXX note this is paginated for > 1000 groups
      $response = $this->Http->get("/usermanagement/1/user/group/direct?username=" . $crowdUsername);
      
      $groups = json_decode($response->body);
      
      foreach($groups->groups as $g) {
        $crowdGroups[ $g->name ] = true;
      }
    } else {
      // No Crowd ID, so create a new record
      
      $response = $this->Http->post("/usermanagement/1/user",
                                    json_encode($user));
      
      if($response->code != 201) {
        throw new RuntimeException($response->reasonPhrase);
      }

      // The identifier is available in $response->body->key as directoryid:uuid where uuid seems not be be used
      $rbody = json_decode($response->body);
      
      $crowdId = $rbody->key;
      
      // Store it
      $args = array(
        'Identifier' => array(
          'identifier'                => $crowdId,
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
    
    // Parse current group memberships into a simple array
    
    $curGroups = array();
    
    foreach($groupMembers as $gm) {
      if($gm['member']) {
        $curGroups[ $gm['CoGroup']['name'] ] = true;
      }
    }
    
    // Add group memberships for each group the person is a member of
    
    foreach(array_keys($curGroups) as $groupName) {
      if(isset($crowdGroups[$groupName])) {
        // Person is already a member, so skip this one
        continue;
      }
      
      // We assume the group exists already, but if it doesn't (typically because
      // the provisioner is deployed after some groups are created) we won't do
      // anything to create it. Crowd will return a 404 that we'll ignore.
      
      $response = $this->Http->post("/usermanagement/1/group/user/direct?groupname=" . urlencode($groupName),
                                    json_encode(array("name" => $crowdUsername)));
      
      if($response->code != 201
         // 404 indicates group does not exist, which we'll ignore at least for now
         && $response->code != 404) {
        throw new RuntimeException($response->reasonPhrase);
      }
    }
    
    // Remove group memberships for each group the person is no longer a member of.
    // This will remove memberships from groups Registry does not know about.
    
    foreach(array_keys($crowdGroups) as $groupName) {
      if(!isset($curGroups[$groupName])) {
        // Person is not a member in Registry but is in Crowd, so remove the membership
        
        $response = $this->Http->delete("/usermanagement/1/user/group/direct?username=" . urlencode($crowdUsername) . "&groupname=" . urlencode($groupName));
      }
    }
    
    return true;
  }
}
