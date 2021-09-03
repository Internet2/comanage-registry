<?php
/**
 * COmanage Registry CO Jira Provisioner Target Model
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
 * @since         COmanage Registry v3.3.2
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::uses("CoProvisionerPluginTarget", "Model");

class CoJiraProvisionerTarget extends CoProvisionerPluginTarget {
  // Define class name for cake
  public $name = "CoJiraProvisionerTarget";
  
  // Add behaviors
  public $actsAs = array('Containable');
  
  // Association rules from this model to other models
  public $belongsTo = array(
    "CoProvisioningTarget",
    "ProvisionCoGroup" => array(
      'className' => 'CoGroup',
      'foreignKey' => 'provision_co_group_id'
    ),
    "Server"
  );
  
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
      'content' => array(
        'rule' => 'numeric',
        'required' => true,
        'unfreeze' => 'CO'
      )
    ),
    'username_type' => array(
      'content' => array(
        'rule' => array('validateExtendedType',
                        array('attribute' => 'Identifier.type',
                              'default' => array(IdentifierEnum::ePPN,
                                                 IdentifierEnum::ePTID,
                                                 IdentifierEnum::Mail,
                                                 IdentifierEnum::OIDCsub,
                                                 IdentifierEnum::OpenID,
                                                 IdentifierEnum::SamlPairwise,
                                                 IdentifierEnum::SamlSubject,
                                                 IdentifierEnum::UID))),
        'required' => true,
        'allowEmpty' => false
      )
    ),
    'provision_co_group_id' => array(
      'content' => array(
        'rule' => 'numeric',
        'required' => false,
        'allowEmpty' => true,
        'unfreeze' => 'CO'
      )
    ),
    'query_by_username' => array(
      'rule' => 'boolean'
    ),
    'deactivate' => array(
      'rule' => 'boolean'
    ),
    'group_type' => array(
      'content' => array(
        'rule' => array('validateExtendedType',
                        array('attribute' => 'Identifier.type',
                              'default' => array(IdentifierEnum::Badge,
                                                 IdentifierEnum::Enterprise,
                                                 IdentifierEnum::GID,
                                                 IdentifierEnum::Reference,
                                                 IdentifierEnum::SORID))),
        'required' => false,
        'allowEmpty' => true
      )
    ),
    'group_name' => array(
      'rule' => 'boolean'
    ),
    'remove_unknown' => array(
      'rule' => 'boolean'
    ),
  );
  
  /**
   * Add member by name to a Jira group.
   *
   * @since  COmanage Registry v3.3.2
   * @param  String $groupName Name of the Jira group
   * @param  String $name      Jira name
   * @return Void
   * @throws RuntimeException
   */

  protected function addMember($groupName, $name) {
    // Note that the Jira API calls for adding or removing a
    // user only take username and not the user key.
    $member = array("name" => $name);
    $response = $this->Http->post("/rest/api/2/group/user?groupname=" . urlencode($groupName), json_encode($member));
    if($response->code = 201) {
      // Success
    } elseif($response->code == 400) {
      // User already in group so ignore.
    } else {
      throw new RuntimeException($response->reasonPhrase);
    }
  }

  /**
   * Get all CO Groups for the CO by name used for provisioning.
   *
   * @since  COmanage Registry v3.3.2
   * @param  Integer $coId                    CO ID
   * @param  String  $groupIdentifierType     The identifier type indicating whether group is to be provisioned
   * @param  Boolean $groupNameFromIdentifier Whether the name of group in Jira is the identifier value
   * @return Array                           Array of CO Group names
   */

  protected function allCoGroupsByName($coId, $groupIdentifierType, $groupNameFromIdentifier) {
    $groups = array();

    $args = array();
    $args['conditions']['CoGroup.co_id'] = $coId;
    $args['conditions']['CoGroup.status'] = SuspendableStatusEnum::Active;
    $args['contain'] = 'Identifier';

    $coGroups = $this->CoProvisioningTarget->Co->CoGroup->find('all', $args);

    foreach($coGroups as $g) {
      if(!empty($groupIdentifierType)) {
        if(!empty($g['Identifier'])){
          foreach($g['Identifier'] as $identifier) {
            if($identifier['type'] == $groupIdentifierType && $identifier['status'] == SuspendableStatusEnum::Active) {
              if($groupNameFromIdentifier) {
                $groups[] = $identifier['identifier'];
              } else {
                $groups[] = $g['CoGroup']['name'];
              }
            }
          }
        }
      } else {
        $groups[] = $g['CoGroup']['name'];
      }
    }

    return $groups;
  }

  /**
   * Create a group in Jira.
   *
   * @since  COmanage Registry v3.3.32
   * @param  Integer  $groupName Name of the group in Jira
   * @return Void
   * @throws RuntimeException
   */

  protected function createGroup($groupName) {
    $group = array("name" => $groupName);
    $response = $this->Http->post("/rest/api/2/group", json_encode($group));
    if($response->code = 201) {
      // Success
    } elseif($response->code = 400) {
      // Group already existed, ignore.
    } else {
      throw new RuntimeException($response->reasonPhrase);
    }
  }

  /**
   * Create HTTP client connected to Jira server
   *
   * @since   COmanage Registry v3.3.2
   * @param   Array $coProvisioningTargetData Provisioning target data as passed to provision function
   * @return  Void
   * @throws  InvalidArgumentException
   *
   */

  protected function createHttpClient($coProvisioningTargetData) {
      $args = array();
      $args['conditions']['Server.id'] = $coProvisioningTargetData['CoJiraProvisionerTarget']['server_id'];
      $args['conditions']['Server.status'] = SuspendableStatusEnum::Active;
      $args['contain'] = array('HttpServer');

      $CoProvisioningTarget = new CoProvisioningTarget();
      $srvr = $CoProvisioningTarget->Co->Server->find('first', $args);
      
      if(empty($srvr)) {
        throw new InvalidArgumentException(_txt('er.notfound', array(_txt('ct.http_servers.1'), $coProvisioningTargetData['CoJiraProvisionerTarget']['server_id'])));
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

  /**
   * Create user in Jira.
   *
   * @since  COmanage Registry v3.3.2
   * @param  Array   $user                   Array of Jira user property values
   * @param  Integer $coPersonId             CO Person ID
   * @param  Integer $coProvisioningTargetId ID for the provisioning target
   * @return Void
   * @throws RuntimeException
   */

  protected function createUser($user, $coPersonId, $coProvisioningTargetId) {
    $response = $this->Http->post("/rest/api/2/user", json_encode($user));
      
    if($response->code != 201) {
      throw new RuntimeException($response->reasonPhrase);
    }

    // The key is available in $response->body->key.
    $body = json_decode($response->body);
    
    $jiraUserKey = $body->key;
    
    // Store it.
    $args = array(
      'Identifier' => array(
        'identifier'                => $jiraUserKey,
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
  
  /**
   * Delete a CO Group from Jira.
   * 
   * @since  COmanage Registry v3.3.2
   * @param  Array            $coGroup CoGroup
   * @throws RuntimeException
   * @return boolean          true
   */
  
  protected function deleteGroup($coProvisioningTargetData, $coGroup) {
    $groupIdentifierType = $coProvisioningTargetData['CoJiraProvisionerTarget']['group_type'];
    $groupNameFromIdentifier = $coProvisioningTargetData['CoJiraProvisionerTarget']['group_name'];

    $hasIdentifier = false;

    if(!empty($groupIdentifierType)) {
      // Group identifiers are not passed so query to find them.
      $args = array();
      $args['conditions']['Identifier.co_group_id'] = $coGroup['id'];
      $args['conditions']['Identifier.type'] = $groupIdentifierType;
      $args['contain'] = false;
    
      $gid = $this->CoProvisioningTarget->Co->CoPerson->Identifier->find('first', $args);

      if(!empty($gid)) {
        $hasIdentifier = true;
        $groupIdentifier = $gid['Identifier']['identifier'];
      }
    }

    if(!empty($groupIdentifierType) && $hasIdentifier && $groupNameFromIdentifier) {
      $groupName = $groupIdentifier;
    } else {
      $groupName = $coGroup['name'];
    }

    $response = $this->Http->delete("/rest/api/2/group?groupname=" . urlencode($groupName));
    
    if($response->code != 200) {
      throw new RuntimeException($response->reasonPhrase);
    }
    
    return true;
  }
  
  /**
   * Delete a CO Person from Jira.
   * 
   * @since  COmanage Registry v3.3.2
   * @param  Integer          $coProvisioningTargetId CoProvisioningTarget ID
   * @param  IdentifierEnum   $usernameType           Username type
   * @param  Integer          $coPersonId             CoPerson ID
   * @param  Array            $identifiers            Array of person's identifiers
   * @throws RuntimeException
   * @return boolean          true
   */
  
  protected function deletePerson($coProvisioningTargetData,
                                  $coPersonId,
                                  $identifiers) {
    $coProvisioningTargetId = $coProvisioningTargetData['CoJiraProvisionerTarget']['co_provisioning_target_id'];
    $usernameType = $coProvisioningTargetData['CoJiraProvisionerTarget']['username_type'];
    $deactivate = $coProvisioningTargetData['CoJiraProvisionerTarget']['deactivate'];

    // Find the Jira User Key for this person.
    $args = array();
    $args['conditions']['Identifier.co_person_id'] = $coPersonId;
    $args['conditions']['Identifier.type'] = IdentifierEnum::ProvisioningTarget;
    $args['conditions']['Identifier.co_provisioning_target_id'] = $coProvisioningTargetId;
    $args['contain'] = false;
    
    $cid = $this->CoProvisioningTarget->Co->CoPerson->Identifier->find('first', $args);

    // We may be called more than once so return true if we cannot find the Jira User Key.
    if(empty($cid)) {
      return true;
    }

    $jiraUserKey = $cid['Identifier']['identifier'];

    if($deactivate) {
      $user = array('active' => false);
      $response = $this->Http->put("/rest/api/2/user?key=" . urlencode($jiraUserKey), json_encode($user));
      if($response->code != 200) {
        throw new RuntimeException($response->reasonPhrase);
      }
    } else {
      $response = $this->Http->delete("/rest/api/2/user?key=" . urlencode($jiraUserKey));
      if($response->code != 204) {
        throw new RuntimeException($response->reasonPhrase);
      }
      
      // Delete the Jira User Key since it is no longer valid.
      $this->CoProvisioningTarget->Co->CoPerson->Identifier->clear();
      $this->CoProvisioningTarget->Co->CoPerson->Identifier->id = $cid['Identifier']['id'];
      $this->CoProvisioningTarget->Co->CoPerson->Identifier->delete();
    }

    return true;
  }

  /**
   * Get CoGroup members by username, filtered by provision group if defined.
   * 
   * @since  COmanage Registry v3.3.2
   * @param  Array            $coGroup            CoGroup
   * @param  IdentifierEnum   $usernameType       Username type
   * @param  Integer          $provisionGroupId   provision group ID
   * @return Array of usernames
   */

  protected function getCoGroupMembersByName($coGroup, $usernameType, $provisionGroupId) {
    // Pull the list of CO Group Members.
    // Note findForCoGroup supports pagination, in case we need to scale there.
    $coGroupMembers = $this->CoProvisioningTarget->Co->CoGroup->CoGroupMember->findForCoGroup($coGroup['id'], null, null, null, true);

    // If membership in a provisioning group is required then filter the members.
    if(!empty($provisionGroupId)) {
      $provisionGroupMembersById = $this->provisionGroupMembersById($provisionGroupId);

      foreach($coGroupMembers as $i => $m) {
        $coPersonId = $m['CoGroupMember']['co_person_id'];
        if(!in_array($coPersonId, $provisionGroupMembersById)) {
          unset($coGroupMembers[$i]);  
        }
      }
    } 

    $coGroupMembersByName = $this->CoProvisioningTarget->Co->CoGroup->CoGroupMember->mapCoGroupMembersToIdentifiers($coGroupMembers, $usernameType);

    return $coGroupMembersByName;
  }

  /**
   * Find the identifier of specified type for CO Group
   *
   * @since  COmanage Registry v3.3.2
   * @param  Array  $coGroup             CO Group
   * @param  String $groupIdentifierType The identifier type
   * @return String
   */

  protected function groupIdentifier($coGroup, $groupIdentifierType) {
    // Note that coGroup is passed without the top level key 'CoGroup'
    $args = array();
    $args['conditions']['Identifier.co_group_id'] = $coGroup['id'];
    $args['conditions']['Identifier.type'] = $groupIdentifierType;
    $args['contain'] = false;
  
    $gid = $this->CoProvisioningTarget->Co->CoPerson->Identifier->find('first', $args);

    if(!empty($gid)) {
      $groupIdentifier = $gid['Identifier']['identifier'];
    } else {
      $groupIdentifier = null;
    }

    return $groupIdentifier;
  }

  /**
   * Convert array of CoGroupMemberships to array of group names.
   *
   * @since  COmanage Registry v3.3.2
   * @param  Array   $groupMembers            Memberships with associated CoGroup and Identifier
   * @param  String  $groupIdentifierType     The identifier type indicating whether group is to be provisioned
   * @param  Boolean $groupNameFromIdentifier Whether the name of group in Jira is the identifier value
   * @return Array
   */
  
  protected function groupMembershipsByName($groupMembers, $groupIdentifierType, $groupNameFromIdentifier) {
    $groupsByName = array();
    $groupIdentifierRequired = !empty($groupIdentifierType);

    foreach($groupMembers as $m) {
      if($groupIdentifierRequired) {
        if(!empty($m['CoGroup']['Identifier'])) {
          foreach($m['CoGroup']['Identifier'] as $identifier) {
            if(($identifier['type'] == $groupIdentifierType) && ($identifier['status'] == SuspendableStatusEnum::Active)) {
              if($groupNameFromIdentifier) {
                $groupsByName[] = $identifier['identifier'];
              } else {
                $groupsByName[] = $m['CoGroup']['name'];
              }
            }
          }
        }
      } else {
        $groupsByName[] = $m['CoGroup']['name'];
      }
    }

    return $groupsByName;
  }

  /**
   * Determine if a Jira username is attached to a CO Person
   *
   * @since  COmanage Registry v3.3.2
   * @param  String  $name         Jira username
   * @param  String  $usernameType Identifier type for the Jira user name
   * @param  Integer $coId         CO ID  
   * @return Array
   */

  protected function isExistingCoPersonByName($name, $usernameType, $coId) {
    $known = false;
    $coPersonId = null;

    $args = array();
    $args['conditions']['Identifier.identifier'] = $name;
    $args['conditions']['Identifier.type'] = $usernameType;
    $args['conditions']['Identifier.status'] = SuspendableStatusEnum::Active;
    $args['contain'] = 'CoPerson';

    $identifier = $this->CoProvisioningTarget->Co->CoPerson->Identifier->find('first', $args);
    if(!empty($identifier['CoPerson'])
       && ($identifier['CoPerson']['co_id'] == $coGroup['co_id'])
       && ($identifier['CoPerson']['status'] == StatusEnum::Active || $identifier['CoPerson']['status'] == StatusEnum::GracePersion)) {
      $known = true;
      $coPersonId = $identifier['CoPerson']['id'];
    }

    return array($known, $coPersonId);
  }

  /** 
   * Determine if a Jira group is provisioned
   *
   * @since  COmanage Registry v3.3.2
   * @param  String $groupName Name of group in Jira
   * @return Array            Array of Boolean and Array of group members by name
   * @throws RuntimeException
   */

  protected function isJiraGroupProvisioned($groupName) {
    $isLast = false;
    $url = "";
    $isProvisioned = false;
    $provisionedMembersByName = array();

    while(!$isLast) {
      if(empty($url)) {
        $url = "/rest/api/2/group/member?groupname=" . urlencode($groupName);
      }
      $this->log("FOO BAR url is " . print_r($url, true));
      $response = $this->Http->get($url);
      if($response->code == 404) {
        $isProvisioned = false;
        break;
      } elseif($response->code == 200) {
        $isProvisioned = true;
        $provisionedData = json_decode($response->body);
        $isLast = $provisionedData->isLast;
        if(!$isLast) {
          $nextPage = $provisionedData->nextPage;
          $url = parse_url($nextPage, PHP_URL_PATH) . "?" . parse_url($nextPage, PHP_URL_QUERY);

          $this->log("FOO isLast is " . print_r($isLast, true));
        }
        foreach($provisionedData->values as $m) {
          $provisionedMembersByName[] = $m->name;
        }
      } else {
      // Throw an error, might be better to examine body but we don't really know the format
      throw new RuntimeException($response->reasonPhrase);
      }
    }

    return array($isProvisioned, $provisionedMembersByName);
  }

  /**
   * Determine if a user is provisioned to Jira
   *
   * @since  COmanage Registry v3.3.2
   * @param  String  $jiraUsername           Jira username
   * @param  Integer $coPersonId             CO Person ID
   * @param  Integer $coProvisioningTargetId Provisioning target ID
   * @param  Boolean $queryByUsername        Whether to query Jira by username in addition to key
   * @return Array   
   * @throws RuntimeException
   */
  
  protected function isUserProvisioned($jiraUsername, $coPersonId, $coProvisioningTargetId, $queryByUsername) {
    $isProvisioned = false;
    $provisionedData = null;

    $args = array();
    $args['conditions']['Identifier.co_person_id'] = $coPersonId;
    $args['conditions']['Identifier.type'] = IdentifierEnum::ProvisioningTarget;
    $args['conditions']['Identifier.co_provisioning_target_id'] = $coProvisioningTargetId;
    $args['contain'] = false;
    
    $cid = $this->CoProvisioningTarget->Co->CoPerson->Identifier->find('first', $args);

    if(!empty($cid)) {
      $jiraUserKey = $cid['Identifier']['identifier'];
      
      $response = $this->Http->get("/rest/api/2/user?key=" . urlencode($jiraUserKey) . '&expand=groups');
      
      // We have a Jira User Key but cannot find the user with it so throw exception.
      if($response->code != 200) {
        throw new RuntimeException($response->reasonPhrase);
      }

      $isProvisioned = true;
      $provisionedData = json_decode($response->body);
    } elseif ($queryByUsername ){
      $response = $this->Http->get("/rest/api/2/user?username=" . urlencode($jiraUsername) . '&expand=groups');
      
      if ($response->code == 404) {
        // User is not found.
      } elseif($response->code == 200) {
        $isProvisioned = true;
        $provisionedData = json_decode($response->body);

        $jiraUserKey = $provisionedData->key;
        
        // Store it.
        $args = array(
          'Identifier' => array(
            'identifier'                => $jiraUserKey,
            'co_person_id'              => $coPersonId,
            'type'                      => IdentifierEnum::ProvisioningTarget,
            'login'                     => false,
            'status'                    => SuspendableStatusEnum::Active,
            'co_provisioning_target_id' => $coProvisioningTargetId
          )
        );
        
        $this->CoProvisioningTarget->Co->CoPerson->Identifier->clear();
        $this->CoProvisioningTarget->Co->CoPerson->Identifier->save($args);
      } else {
        throw new RuntimeException($response->reasonPhrase);
      }
    }

    return array($isProvisioned, $provisionedData);
  }

  /**
   * Provision for the specified CO Person.
   *
   * @since  COmanage Registry v3.3.2
   * @param  Array                  $coProvisioningTargetData CO Provisioning Target data
   * @param  ProvisioningActionEnum $op                       Registry transaction type triggering provisioning
   * @param  Array                  $provisioningData         Provisioning data, populated with ['CoPerson'] or ['CoGroup']
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
      $this->createHttpClient($coProvisioningTargetData);
    }
    
    if($deleteGroup) {
      $this->deleteGroup($coProvisioningTargetData,
                         $provisioningData['CoGroup']);
    }
    
    if($deletePerson) {
      $this->deletePerson($coProvisioningTargetData,
                          $provisioningData['CoPerson']['id'],
                          $provisioningData['Identifier']);
    }

    if($syncGroup) {
      $this->syncGroup($coProvisioningTargetData,
                       $provisioningData['CoGroup']);
    }
    
    if($syncPerson) {
      $this->syncPerson($coProvisioningTargetData,
                        $provisioningData['CoPerson']['id'],
                        $provisioningData['CoPerson']['status'],
                        $provisioningData['PrimaryName'],
                        $provisioningData['EmailAddress'],
                        $provisioningData['Identifier'],
                        $provisioningData['CoGroupMember']);
    }
  }

  /**
   * Get the members of the provision group by CO Person ID.
   * 
   * @since  COmanage Registry v3.3.2
   * @param  Integer          $provisionGroupId       provision group ID
   * @return boolean          true
   */

  protected function provisionGroupMembersById($provisionGroupId) {
    $provisionGroupMembersById = array();

    if(!empty($provisionGroupId)) {
      $provisionGroupMembers = $this->CoProvisioningTarget->Co->CoGroup->CoGroupMember->findForCoGroup($provisionGroupId, null, null, null, true);
      if(!empty($provisionGroupMembers)) {
        foreach($provisionGroupMembers as $m) {
          $provisionGroupMembersById[] = $m['CoGroupMember']['co_person_id'];
        }
      }
    } 

    return $provisionGroupMembersById;
  }

  /**
   * Remove member from group in Jira
   *
   * @since  COmanage Registry v3.3.2
   * @param  String $groupName Name of group in Jira
   * @param  String $name      Jira username for member
   * @return Void
   * @throws RuntimeException
   */

  protected function removeMember($groupName, $name) {
    $response = $this->Http->delete("/rest/api/2/group/user?groupname=" . urlencode($groupName) . "&username=" . urlencode($name));
    if($response->code = 200) {
      // Success
    } else {
      throw new RuntimeException($response->reasonPhrase);
    }
  }

  /**
   * Determine the provisioning status of this target.
   *
   * @since  COmanage Registry v3.3.2
   * @param  Integer $coProvisioningTargetId CO Provisioning Target ID
   * @param  Model   $Model                  Model being queried for status (eg: CoPerson, CoGroup,
   *                                         CoEmailList, COService)
   * @param  Integer $id                     $Model ID to check status for
   * @return Array ProvisioningStatusEnum, Timestamp of last update in epoch seconds, Comment
   * @throws InvalidArgumentException If $coPersonId not found
   * @throws RuntimeException For other errors
   */

  public function status($coProvisioningTargetId, $model, $id) {
    $ret = array();
    $ret['status'] = ProvisioningStatusEnum::NotProvisioned;
    $ret['timestamp'] = null;
    $ret['comment'] = "";

    // Pull the provisioning target configuration.
    $args = array();
    $args['conditions']['CoJiraProvisionerTarget.co_provisioning_target_id'] = $coProvisioningTargetId;
    $args['contain'] = false;

    $coProvisioningTargetData = $this->find('first', $args);

    $this->createHttpClient($coProvisioningTargetData);

    if($model->name == 'CoPerson') {
      $usernameType = $coProvisioningTargetData['CoJiraProvisionerTarget']['username_type'];
      $queryByUsername = $coProvisioningTargetData['CoJiraProvisionerTarget']['query_by_username'];
      $provisionGroupId = $coProvisioningTargetData['CoJiraProvisionerTarget']['provision_co_group_id'];

      // Pull the CO Person record
      $args = array();
      $args['conditions']['CoPerson.id'] = $id;
      $args['contain'] = array();
      $args['contain'][] = 'Identifier';
      $args['contain'][] = 'CoGroupMember';

      $coPerson = $this->CoProvisioningTarget->Co->CoPerson->find('first', $args);

      $jiraUsername = null;
      foreach($coPerson['Identifier'] as $identifier) {
        if($identifier['type'] == $usernameType && $identifier['status'] = SuspendableStatusEnum::Active) {
          $jiraUsername = $identifier['identifier'];
        }
      }

      $eligible = true;
      if(!empty($provisionGroupId)) {
        $eligible = false;
        foreach($coPerson['CoGroupMember'] as $m) {
          if($m['co_group_id'] == $provisionGroupId) {
            $eligible = true;
            break;
          }
        }
      }

      if(!$eligible) {
        $ret['status'] = ProvisioningStatusEnum::NotProvisioned;
        $ret['comment'] = "Ineligible (not in required group)";
      } else {
        if(isset($jiraUsername)) {
          list($isProvisioned, $provisionedData) = $this->isUserProvisioned($jiraUsername, $id, $coProvisioningTargetId, $queryByUsername);
          if($isProvisioned) {
            $ret['status'] = ProvisioningStatusEnum::Provisioned;
          }
        }
      }
    }

    if($model->name == 'CoGroup') {
      $groupIdentifierType = $coProvisioningTargetData['CoJiraProvisionerTarget']['group_type'];
      $groupNameFromIdentifier = $coProvisioningTargetData['CoJiraProvisionerTarget']['group_name'];

      // Pull the CO Group record
      $args = array();
      $args['conditions']['CoGroup.id'] = $id;
      $args['contain'] = array();
      $args['contain'][] = 'Identifier';

      $coGroup = $this->CoProvisioningTarget->Co->CoGroup->find('first', $args);

      $identifierRequired = !empty($groupIdentifierType);
      $hasIdentifier = false;

      if($identifierRequired) {
        $groupIdentifier = $this->groupIdentifier($coGroup['CoGroup'], $groupIdentifierType);
        if($groupIdentifier) {
          $hasIdentifier = true;
        }
      }

      $eligible = true;
      if($identifierRequired && !$hasIdentifier) {
        $eligible = false;
      }

      if($eligible) {
        if($identifierRequired && $hasIdentifier && $groupNameFromIdentifier) {
          $groupName = $groupIdentifier;
        } else {
          $groupName = $coGroup['name'];
        }

        list($isProvisioned, $provisionedMembersByName) = $this->isJiraGroupProvisioned($groupName);

        if($isProvisioned) {
          $ret['status'] = ProvisioningStatusEnum::Provisioned;
        }
      } else {
        $ret['status'] = ProvisioningStatusEnum::NotProvisioned;
        $ret['comment'] = "Ineligible (does not have required identifier)";
      }
    }

    return $ret;
  }
  
  /**
   * Synchronize a CO Group.
   * 
   * @since  COmanage Registry v3.3.2
   * @param  Integer          $coProvisioningTargetId CoProvisioningTarget ID
   * @param  Array            $coGroup                CoGroup
   * @param  IdentifierEnum   $usernameType           Username type
   * @throws RuntimeException
   * @return boolean          true
   */

  protected function syncGroup($coProvisioningTargetData,
                               $coGroup) {
    if(!empty($coGroup['CoPerson'])) {
      // This is a single-person operation, probably a membership add or removal.
      // Since we probably handled this already via a CoPerson update, we'll skip
      // doing anything here.
      
      return true;
    }

    $coProvisioningTargetId = $coProvisioningTargetData['CoJiraProvisionerTarget']['co_provisioning_target_id'];
    $usernameType = $coProvisioningTargetData['CoJiraProvisionerTarget']['username_type'];
    $groupIdentifierType = $coProvisioningTargetData['CoJiraProvisionerTarget']['group_type'];
    $groupNameFromIdentifier = $coProvisioningTargetData['CoJiraProvisionerTarget']['group_name'];
    $provisionGroupId = $coProvisioningTargetData['CoJiraProvisionerTarget']['provision_co_group_id'];
    $removeUnknown = $coProvisioningTargetData['CoJiraProvisionerTarget']['remove_unknown'];

    $identifierRequired = !empty($groupIdentifierType);
    $hasIdentifier = false;

    if($identifierRequired) {
      $groupIdentifier = $this->groupIdentifier($coGroup, $groupIdentifierType);
      if($groupIdentifier) {
        $hasIdentifier = true;
      }
    }

    if($identifierRequired && $hasIdentifier && $groupNameFromIdentifier) {
      $groupName = $groupIdentifier;
    } else {
      $groupName = $coGroup['name'];
    }

    // Is the group provisioned?
    list($isProvisioned, $provisionedMembersByName) = $this->isJiraGroupProvisioned($groupName);

    if($isProvisioned) {
      if(($identifierRequired && $hasIdentifier) || !$identifierRequired) {
        $coGroupMembersByName = $this->getCoGroupMembersByName($coGroup, $usernameType, $provisionGroupId);

        // Add memberships for anyone missing them.
        foreach($coGroupMembersByName as $name) {
          if(!in_array($name, $provisionedMembersByName)) {
            $this->addMember($groupName, $name);
          }
        }

        $provisionGroupMembersById = $this->provisionGroupMembersById($provisionGroupId);

        // Remove memberships for anyone not in the CoGroup if we
        // know the user, or are configured to remove unknown users.
        
        foreach($provisionedMembersByName as $name) {
          if(!in_array($name, $coGroupMembersByName)) {
            $remove = false;
            // Do we know this user?
            list($isKnown, $coPersonId) = $this->isExistingCoPersonByName($name, $usernameType, $coGroup['co_id']);

            if($isKnown) {
              // If a provision group is defined only remove if the user
              // is in the provision group.
              if(!empty($provisionGroupId)) {
                if(in_array($coPersonId, $provisionGroupMembersById)) {
                  $remove = true;
                }    
              } else {
                $remove = true;
              }
            } else {
              // We do not know this user so only remove if so configured.
              if($removeUnknown) {
                $remove = true;
              }
            }
            if($remove) {
              $this->removeMember($groupName, $name);
            }
          }
        } 
      } else {
        // Delete group since it does not have the identifier but is provisioned.
        $this->deleteGroup($coProvisioningTargetData, $coGroup);
      }
    } else {
      if(($identifierRequired && $hasIdentifier) || !$identifierRequired) {
        // Create the group and add members.
        $this->createGroup($groupName);
        $coGroupMembersByName = $this->getCoGroupMembersByName($coGroup, $usernameType, $provisionGroupId);
        foreach($coGroupMembersByName as $name) {
          $this->addMember($groupName, $name);
        }
      } else {
        // Not provisioned and does not have identifier so noop.
      }
    }

    return true;
  }
  
  /**
   * Synchronize a CO Person.
   * 
   * @since  COmanage Registry v3.2.0
   * @param  Array            $coProvisioningTargetData  CoProvisioningTargetData
   * @param  IdentifierEnum   $usernameType              Username type
   * @param  Integer          $coPersonId                CoPerson ID
   * @param  JobStatusEnum    $coPersonStatus            CoPerson Status
   * @param  Array            $primaryName               Array of person's primary name
   * @param  Array            $emailAddresses            Array of person's email addresses
   * @param  Array            $identifiers               Array of person's identifiers
   * @param  Array            $groupMembers              Array of person's group memberships
   * @throws RuntimeException
   * @return boolean          true
   */
  
  protected function syncPerson($coProvisioningTargetData,
                                $coPersonId,
                                $coPersonStatus,
                                $primaryName,
                                $emailAddresses,
                                $identifiers,
                                $groupMembers) {
    $coProvisioningTargetId = $coProvisioningTargetData['CoJiraProvisionerTarget']['co_provisioning_target_id'];
    $usernameType = $coProvisioningTargetData['CoJiraProvisionerTarget']['username_type'];
    $groupIdentifierType = $coProvisioningTargetData['CoJiraProvisionerTarget']['group_type'];
    $groupNameFromIdentifier = $coProvisioningTargetData['CoJiraProvisionerTarget']['group_name'];
    $provisionGroupId = $coProvisioningTargetData['CoJiraProvisionerTarget']['provision_co_group_id'];
    $queryByUsername = $coProvisioningTargetData['CoJiraProvisionerTarget']['query_by_username'];

    // Find the identifier of the requested username type.
    // Note similar logic in deletePerson.
    $ids = Hash::extract($identifiers, '{n}[type='.$usernameType.']');

    if(empty($ids)) {
      throw new RuntimeException(_txt('er.jiraprovisioner.id.none', array($usernameType)));
    }

    $jiraUsername = $ids[0]['identifier'];
      
    if(empty($emailAddresses[0]['mail'])) {
      // Jira requires an email address
      throw new RuntimeException(_txt('er.jiraprovisioner.mail.none'));
    }
    
    // Is there a provisioning group defined and is the user a member?
    $provisionGroup = false;
    $provisionGroupMember = false;
    if(!empty($provisionGroupId)) {
      $provisionGroup = true;
      foreach($groupMembers as $g) {
        if($g['co_group_id'] == $provisionGroupId) {
          $provisionGroupMember = true;
          break;
        }
      }
    }

    $jiraGroups = array();

    // Construct the user data
    $user = array(
      'name' => $jiraUsername,
      // XXX We could use Authenticator passwords here...
      'password' => bin2hex(random_bytes(8)),
      'active' => (in_array($coPersonStatus, array(StatusEnum::Active, StatusEnum::GracePeriod))
                  ? 'true' : 'false'),
      // XXX Which email? For now just the first...
      'emailAddress' => $emailAddresses[0]['mail'],
      'displayName' => generateCn($primaryName),
      'applicationKeys' => array('jira-core')
    );

    // Is the user provisioned?
    list($isProvisioned, $jiraUserData) = $this->isUserProvisioned($jiraUsername, $coPersonId, $coProvisioningTargetId, $queryByUsername);

    if($isProvisioned) {
      if(($provisionGroup && $provisionGroupMember) || !$provisionGroup) {
        // Update the user if necessary.
        $updateRequired = false;

        if(!empty($jiraUserData->name) && $jiraUserData->name != $user['name']) {
          $updateRequired = true;
        }

        if(isset($jiraUserData->active) && $jiraUserData->active != $user['active']) {
          $updateRequired = true;
        }

        if(!empty($jiraUserData->emailAddress) && $jiraUserData->emailAddress != $user['emailAddress']) {
          $updateRequired = true;
        }

        if(!empty($jiraUserData->displayName) && $jiraUserData->displayName != $user['displayName']) {
          $updateRequired = true;
        }

        if($updateRequired) {
          $this->updateUser($user, $jiraUserData->key);
        }
      } else {
        // User is provisioned and is not member of provision group so deactivate or delete.
        $this->deletePerson($coProvisioningTargetData, $coPersonId, $identifiers);

        // Since the user has been deactivated (or deleted) we want to remove them
        // from groups in Jira, so set the passed in list of group memberships to be
        // empty and the group membership reconcilliation code below will remove
        // the memberships.
        $groupMembers = array();
      }
    }
    else {
      if(($provisionGroup && $provisionGroupMember) || !$provisionGroup) {
        $this->createUser($user, $coPersonId, $coProvisioningTargetId);
      } else {
        // User is not provisioned and is not member of provision group so noop.
      }
    } 

    // Get CO Group memberships by name.
    $groupsByName = $this->groupMembershipsByName($groupMembers, $groupIdentifierType, $groupNameFromIdentifier);

    // Get Jira group memberships by name.
    $jiraGroups = array();
    if(isset($jiraUserData)) {
      foreach($jiraUserData->groups->items as $g) {
        $jiraGroups[] = $g->name;
      }
    }

    // Get all CO Groups by their name.
    $allCoGroupsByName = $this->allCoGroupsByName($groupMembers[0]['CoGroup']['co_id'], $groupIdentifierType, $groupNameFromIdentifier);

    // Add missing membership in Jira.
    foreach($groupsByName as $g) {
      if(!in_array($g, $jiraGroups)) {
        $this->addMember($g, $jiraUsername);
      }
    }

    // Remove memberships in Jira.
    foreach($jiraGroups as $g) {
      if(!in_array($g, $groupsByName)) {
        if(!empty($groupIdentifierType)) {
          // If this is a known group then remove the membership in Jira.
          if(in_array($g, $allCoGroupsByName)) {
            $this->removeMember($g, $jiraUsername);
          }
        } else {
          $this->removeMember($g, $jiraUsername);
        }
      }
    }

    return true;
  }

  /**
   * Update a user in Jira.
   *
   * @since  COmanage Registry v3.3.2
   * @param  Array  $user Array of user attributes
   * @param  String $key  Jira user key
   * @return Void
   * @throws RuntimeException
   */

  protected function updateUser($user, $key) {
    $response = $this->Http->put("/rest/api/2/user?key=" . urlencode($key), json_encode($user));
    
    if($response->code != 200) {
      throw new RuntimeException($response->reasonPhrase);
    }
  }
}
