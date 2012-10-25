<?php
/**
 * COmanage Registry Grouper Rest Client
 *
 * Copyright (C) 2012 University Corporation for Advanced Internet Development, Inc.
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
 * @copyright     Copyright (C) 2012 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.7
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */

App::uses('GrouperRestClientException', 'Grouper.Lib');
App::uses('HttpSocket', 'Network/Http');

/**
 * An instance is used to query Grouper using
 * the RESTful Grouper endpoints.
 */
class GrouperRestClient extends HttpSocket {

  private $httpSocket = null;
  private $defaultRequest = null;

  /**
   * Constructor for GrouperRestClient
   * - precondition: 
   * - postcondition: 
   *
   * @since  COmanage Directory 0.7
   * @return instance
   */
  function __construct($config = array(), $autoConnect = false) {
    parent::__construct($config, $autoConnect);

    $this->httpSocket = new HttpSocket();
    $this->defaultRequest = array(
      'method' => 'POST',
      'uri' => array(
        'scheme' => Configure::read('Grouper.scheme'),
        'host' => Configure::read('Grouper.host'),
        'port' => Configure::read('Grouper.port'),
        'user' => Configure::read('Grouper.user'),
        'pass' => Configure::read('Grouper.pass'),
        'path' => Configure::read('Grouper.basePath'),
        'query' => ''
        ),
      'header' => array(
        'Content-Type' => 'text/x-json'
        ),
      );
  }

  /**
   * Destructor for GrouperRestClient
   * - precondition: 
   * - postcondition: 
   *
   * @since  COmanage Directory 0.7
   * @return void
   */
  public function __destruct() {
  }

  /**
   * Add a member to a group
   * - precondition: 
   * - postcondition: 
   *
   * @since         COmanage Directory 0.7
   * @groupName     full name of group including stem(s)
   * @subject       subject to add to group
   * @return        void
   * @throws        GrouperRestClientException
   */
  public function addMember($groupName, $subject) {
    $request = array(
      'method' => 'PUT',
      'uri' => array(
        'path' => 'groups/' . urlencode($groupName) . '/members/' . urlencode($subject)
        )
      );

    $result = $this->request($request, 201);

    $success = $result->WsAddMemberLiteResult->resultMetadata->success;
    if ($success != 'T') {
      throw new GrouperRestClientException('Result from add member was not success');
    }

    return ;
  }

  /**
   * Assign attribute with value
   * - precondition: 
   * - postcondition: 
   *
   * @since               COmanage Directory 0.7
   * @attributeName       full name of attribute
   * @attributeAssignType see Grouper WS documentation, usually one of 'group', 'imm_mem'
   * @ownerName           full name of owner, usually the group name or the membership id
   * @value               value to assign
   * @return              void
   * @throws              GrouperRestClientException
   */
  protected function assignAttributeWithValue($attributeName, $attributeAssignType, $ownerName, $value) {
    $body = array(
      'WsRestAssignAttributesLiteRequest' => array(
        'actAsSubjectId' => 'GrouperSystem',
        'attributeAssignOperation' => 'assign_attr',
        'attributeAssignType' => $attributeAssignType,
        'attributeAssignValueOperation' => 'add_value',
        'valueSystem' => "$value",
        'wsAttributeDefNameName' => $attributeName
        )
      );

    if ($attributeAssignType == 'group') {
      $body['WsRestAssignAttributesLiteRequest']['wsOwnerGroupName'] = "$ownerName";
    } elseif ($attributeAssignType == 'imm_mem') {
      $body['WsRestAssignAttributesLiteRequest']['wsOwnerMembershipId'] = "$ownerName";
    }

    $body = json_encode($body);

    $request = array(
      'uri' => array(
        'path' => 'attributeAssignments'
        ),
      'body' => $body
      );

    $result = $this->request($request, 200);
    $success = $result->WsAssignAttributesLiteResults->resultMetadata->success;
    if ($success != 'T') {
      throw new GrouperRestClientException('Result from assign attribute with value to group was not success');
    }

    return;
  }

  /**
   * Assign multiple attributes with values
   * - precondition: 
   * - postcondition: 
   *
   * @since               COmanage Directory 0.7
   * @assignments         array with attribute names as keys and values as the assignment values
   * @attributeAssignType see Grouper WS documentation, usually one of 'group', 'imm_mem'
   * @ownerName           full name of owner, usually the group name or the membership id
   * @return              void
   * @throws              GrouperRestClientException
   */
  public function assignAttributeWithValueBatch($assignments, $attributeAssignType, $ownerName) {
    $body = array(
      'WsRestAssignAttributesBatchRequest' => array(
        'actAsSubjectId' => 'GrouperSystem',
        'wsAssignAttributeBatchEntries' => array()
        )
      );

    $entries = &$body['WsRestAssignAttributesBatchRequest']['wsAssignAttributeBatchEntries'];
    foreach($assignments as $name => $value) {
      $entry = array(
        'attributeAssignOperation' => 'assign_attr',
        'attributeAssignType' => $attributeAssignType,
        'wsAttributeDefNameLookup' => array(
          'name' => "$name"
        ),
        'attributeAssignValueOperation' => 'add_value',
        'values' => array(
          array('valueSystem' => "$value")
        )
      );
      if ($attributeAssignType == 'group') {
        $entry['wsOwnerGroupLookup'] = array('groupName' => "$ownerName");
      } elseif ($attributeAssignType == 'imm_mem') {
        $entry['wsOwnerMembershipLookup'] = array('uuid' => "$ownerName");
      }
      $entries[] = $entry;
    }

    $body = json_encode($body);

    $request = array(
      'uri' => array(
        'path' => 'attributeAssignments'
        ),
      'body' => $body
      );

    $result = $this->request($request, 200);

    $success = $result->WsAssignAttributesBatchResults->resultMetadata->success;
    if ($success != 'T') {
      throw new GrouperRestClientException('Result from assign attribute with value to group was not success');
    }

    return;
  }

  /**
   * Assign attribute to group with value
   * - precondition: 
   * - postcondition: 
   *
   * @since               COmanage Directory 0.7
   * @attributeName       full name of attribute
   * @groupName           full name of group
   * @value               value to assign
   * @return              void
   * @throws              GrouperRestClientException
   */
  public function assignAttributeWithValueToGroup($attributeName, $groupName, $value) {
    return $this->assignAttributeWithValue($attributeName, 'group', $groupName, $value);
  }

  /**
   * Assign attribute to immediate membership with value
   * - precondition: 
   * - postcondition: 
   *
   * @since               COmanage Directory 0.7
   * @attributeName       full name of attribute
   * @membershipId        memberbership Id for the immediate membership
   * @value               value to assign
   * @return              void
   * @throws              GrouperRestClientException
   */
  public function assignAttributeWithValueToImmediateMembership($attributeName, $membershipId, $value) {
    return $this->assignAttributeWithValue($attributeName, 'imm_mem', $membershipId, $value);
  }

  /**
   * Assign privileges to subject(s) in a group.
   * - precondition: Group and subjects exist.
   * - postcondition: Subjects have assigned privileges.
   *
   * @since               COmanage Directory 0.7
   * @group               full name of group
   * @privileges          array of string names of privileges
   * @subjects            array of subject identifiers
   * @return              void
   * @throws GrouperRestClientException
   */
  public function assignPrivilege($group, $privileges, $subjects, $subjectSourceId = null) {
    $subjectLookups = array();
    foreach($subjects as $s) {
      $subjectLookupsValue = array();
      $subjectLookupsValue['subjectId'] = $s;
      if (!empty($subjectSourceId)) {
        $subjectLookupsValue['subjectSourceId'] = $subjectSourceId;
      }
      $subjectLookups[] = $subjectLookupsValue;
    }
    $body = array(
      'WsRestAssignGrouperPrivilegesRequest' => array(
        'actAsSubjectLookup' => array(
          'subjectId' => 'GrouperSystem'
        ),
        'allowed' => 'T',
        'privilegeNames' => $privileges,
        'privilegeType' => 'access',
        'replaceAllExisting' => 'F',
        'wsGroupLookup' => array(
          'groupName' => $group
        ),
        'wsSubjectLookups' => $subjectLookups
      )
    );

    $body = json_encode($body);

    $request = array(
      'uri' => array(
        'path' => 'grouperPrivileges'
        ),
      'body' => $body
      );

    $result = $this->request($request, 200);
    $success = $result->WsAssignGrouperPrivilegesResults->resultMetadata->success;
    if ($success != 'T') {
      throw new GrouperRestClientException('Result from assign privileges was not success');
    }

    return;
  }

  /**
   * Get attribute assignments
   * - precondition: 
   * - postcondition: 
   *
   * @since               COmanage Directory 0.7
   * @attributeAssignType one of 'group','member','stem','imm_mem','any_mem'
   * @attributeNames      array of full attribute names
   * @valueType           type of value to filter with one of 'string', 'integer', 'floating'
   * @value               value to filter with
   * @return              mixed
   * @throws              GrouperRestClientException
   */
  public function getAttributeAssignments($attributeAssignType, $attributeNames, $valueType = null, $value = null) {
    $body = array(
      'WsRestGetAttributeAssignmentsRequest' => array(
        'actAsSubjectId' => 'GrouperSystem',
        'attributeAssignType' => $attributeAssignType,
        'includeAssignmentsOnAssignments' => 'F',
        )
      );

    $lookups = array();
    foreach($attributeNames as $name){
      $lookups[] = array(
        'name' => $name
        );
    }
    $body['WsRestGetAttributeAssignmentsRequest']['wsAttributeDefNameLookups'] = $lookups;

    if ($attributeAssignType == 'group'){
      $body['WsRestGetAttributeAssignmentsRequest']['includeGroupDetail'] = 'T';
    }

    if (isset($valueType) and isset($value)) {
      $body['WsRestGetAttributeAssignmentsRequest']['attributeDefValueType'] = $valueType;
      $body['WsRestGetAttributeAssignmentsRequest']['theValue'] = $value;
    }

    $body = json_encode($body);

    $request = array(
      'uri' => array(
        'path' => 'attributeAssignments'
        ),
      'body' => $body
      );

    $result = $this->request($request, 200);
    $success = $result->WsGetAttributeAssignmentsResults->resultMetadata->success;
    if ($success != 'T') {
      throw new GrouperRestClientException('Result from get attribute assignments was not success');
    }

    return $result;
  }

  /**
   * Get attribute assignments on a group
   * - precondition: 
   * - postcondition: 
   *
   * @since               COmanage Directory 0.7
   * @groupName           full name of group
   * @return              mixed
   * @throws GrouperRestClientException
   */
  public function getGroupAttributeAssignments($groupName) {
    $body = array(
      'WsRestGetAttributeAssignmentsRequest' => array(
        'actAsSubjectId' => 'GrouperSystem',
        'attributeAssignType' => 'group',
        'includeAssignmentsOnAssignments' => 'F',
        'includeGroupDetail' => 'T',
        'wsOwnerGroupLookups' => array(
            array('groupName' => $groupName)
          )
        )
      );

    $body = json_encode($body);

    $request = array(
      'uri' => array(
        'path' => 'attributeAssignments'
        ),
      'body' => $body
      );

    $result = $this->request($request, 200);
    $success = $result->WsGetAttributeAssignmentsResults->resultMetadata->success;
    if ($success != 'T') {
      throw new GrouperRestClientException('Result from get attribute assignments was not success');
    }

    $assignments = $result->WsGetAttributeAssignmentsResults->wsAttributeAssigns;
    $attributes = array();
    foreach($assignments as $a) {
      $key = $a->attributeDefNameName;
      $value = $a->wsAttributeAssignValues[0]->valueSystem;
      $attributes[$key] = $value;
    }

    return $attributes;
  }

  /**
   * Get group by attribute value
   * - precondition: 
   * - postcondition: 
   *
   * @since               COmanage Directory 0.7
   * @attributeName       full name of attribute
   * @valueType           type of value to filter with one of 'string', 'integer', 'floating'
   * @value               value to filter with
   * @return              mixed
   * @throws              GrouperRestClientException
   */
  public function getGroupsByAttributeValue($attributeName, $valueType, $value) {
    $result = $this->getAttributeAssignments('group', array($attributeName), $valueType, $value);
    $groups = $result->WsGetAttributeAssignmentsResults->wsGroups;
    return $groups;
  }

  /**
   * Get attribute assignments on an immediate membership
   * - precondition: 
   * - postcondition: 
   *
   * @since               COmanage Directory 0.7
   * @groupName           full name of group
   * @return              mixed
   * @throws GrouperRestClientException
   */
  public function getImmediateMembershipAttributeAssignments($immediateMembershipUuid) {
    $body = array(
      'WsRestGetAttributeAssignmentsRequest' => array(
        'actAsSubjectId' => 'GrouperSystem',
        'attributeAssignType' => 'imm_mem',
        'includeAssignmentsOnAssignments' => 'F',
        'includeGroupDetail' => 'T',
        'wsOwnerMembershipLookups' => array(
            array('uuid' => $immediateMembershipUuid)
          )
        )
      );

    $body = json_encode($body);

    $request = array(
      'uri' => array(
        'path' => 'attributeAssignments'
        ),
      'body' => $body
      );

    $result = $this->request($request, 200);

    $success = $result->WsGetAttributeAssignmentsResults->resultMetadata->success;
    if ($success != 'T') {
      throw new GrouperRestClientException('Result from get attribute assignments was not success');
    }

    $assignments = $result->WsGetAttributeAssignmentsResults->wsAttributeAssigns;
    $attributes = array();
    foreach($assignments as $a) {
      $key = $a->attributeDefNameName;
      $value = $a->wsAttributeAssignValues[0]->valueSystem;
      $attributes[$key] = $value;
    }

    return $attributes;
  }
  
  /**
   * Get attribute assignments on many immediate memberships
   * - precondition: 
   * - postcondition: 
   *
   * @since                     COmanage Directory 0.7
   * @immediateMembershipUuids  list of immediate membership uuids for which to get attribute assignments
   * @return                    array with Grouper owner membership Id as keys and values arrays
   *                            where the keys are the Grouper attributeDefNameName and values are the assigned values
   * @throws                    GrouperRestClientException
   */
  public function getImmediateMembershipAttributeAssignmentsBulk($immediateMembershipUuids) {
    $body = array(
      'WsRestGetAttributeAssignmentsRequest' => array(
        'actAsSubjectId' => 'GrouperSystem',
        'attributeAssignType' => 'imm_mem',
        'includeAssignmentsOnAssignments' => 'F',
        'includeGroupDetail' => 'T'
        )
      );

    $uuids = array();
    foreach($immediateMembershipUuids as $uuid) {
      $uuids[] = array('uuid' => $uuid);
    }

    $body['WsRestGetAttributeAssignmentsRequest']['wsOwnerMembershipLookups'] = $uuids;

    $body = json_encode($body);

    $request = array(
      'uri' => array(
        'path' => 'attributeAssignments'
        ),
      'body' => $body
      );

    $result = $this->request($request, 200);

    $success = $result->WsGetAttributeAssignmentsResults->resultMetadata->success;
    if ($success != 'T') {
      throw new GrouperRestClientException('Result from get attribute assignments was not success');
    }

    $rawAssignments = $result->WsGetAttributeAssignmentsResults->wsAttributeAssigns;
    $assignments = array();
    foreach($rawAssignments as $a) {
      $membership = $a->ownerMembershipId;
      $key = $a->attributeDefNameName;
      $value = $a->wsAttributeAssignValues[0]->valueSystem;
      if (!array_key_exists($membership, $assignments)) {
        $assignments[$membership] = array();
      }
      $assignments[$membership][$key] = $value;
    }

    return $assignments;
  }

  /**
   * Delete a member from a group
   * - precondition: Subject is a member of the Grouper group
   * - postcondition: Subject is no longer a member of the Grouper group
   *
   * @since         COmanage Directory 0.7
   * @groupName     full name of group including stem(s)
   * @subject       subject to remove from group
   * @return void
   * @throws        GrouperRestClientException
   */
  public function deleteMember($groupName, $subject) {

    $request = array(
      'method' => 'DELETE',
      'uri' => array(
        'path' => 'groups/' . urlencode($groupName) . '/members/' . urlencode($subject)
        )
      );

    $result = $this->request($request, 200);

    $success = $result->WsDeleteMemberLiteResult->resultMetadata->success;
    if ($success != 'T') {
      throw new GrouperRestClientException('Result from delete member was not success');
    }

    return ;
  }


  /**
   * Get immediate membership Id using group name and subject
   * - precondition: 
   * - postcondition: 
   *
   * @since               COmanage Directory 0.7
   * @groupName           full name of the group
   * @subjectId           subject Id
   * @return              membership Id as string
   * @throws              GrouperRestClientException
   */
  public function getImmediateMembershipId($groupName, $subjectId) {
    $body = array(
      'WsRestGetMembershipsRequest' => array(
        'actAsSubjectId' => 'GrouperSystem',
        'fieldName' => 'members',
        'memberFilter' => 'Immediate',
        'wsGroupLookups' => array(
          array(
            'groupName' => $groupName
            )
          ),
        'wsSubjectLookups' => array(
          array(
            'subjectId' => $subjectId
          )
        )
      )
    );

    $body = json_encode($body);

    $request = array(
      'uri' => array(
        'path' => 'memberships'
        ),
      'body' => $body
      );


    $result = $this->request($request, 200);
    $success = $result->WsGetMembershipsResults->resultMetadata->success;
    if ($success != 'T') {
      throw new GrouperRestClientException('Result from get memberships was not success');
    }

    $memberships = $result->WsGetMembershipsResults->wsMemberships;

    if (count($memberships) > 1){
      throw new GrouperRestClientException('Result from get memberships returned more than one membership');
      }

    $immediateMembershipId = $memberships[0]->immediateMembershipId;

    return $immediateMembershipId;
  }

  /**
   * Get multiple immediate membership Ids using group names and subjects
   * - precondition: 
   * - postcondition: 
   *
   * @since               COmanage Directory 0.7
   * @groupNames          array of full names of the groups
   * @subjects            array of subject Ids
   * @return              array where the keys are the subject id and the value
   *                      is a list, which each element an array where the keys are group name and 
   *                      the values are immediate membership Ids
   * @throws GrouperRestClientException
   */
  public function getManyImmediateMembershipId($groupNames, $subjects) {
    $body = array(
      'WsRestGetMembershipsRequest' => array(
        'actAsSubjectId' => 'GrouperSystem',
        'fieldName' => 'members',
        'memberFilter' => 'Immediate',
        'wsGroupLookups' => array(),
        'wsSubjectLookups' => array()
        )
    );

    foreach($groupNames as $g) {
      $body['WsRestGetMembershipsRequest']['wsGroupLookups'][] = array('groupName' => $g);
    }

    foreach($subjects as $s) {
      $body['WsRestGetMembershipsRequest']['wsSubjectLookups'][] = array('subjectId' => $s);
    }

    $body = json_encode($body);

    $request = array(
      'uri' => array(
        'path' => 'memberships'
        ),
      'body' => $body
      );

    $result = $this->request($request, 200);
    $success = $result->WsGetMembershipsResults->resultMetadata->success;
    if ($success != 'T') {
      throw new GrouperRestClientException('Result from get memberships was not success');
    }

    if (property_exists($result->WsGetMembershipsResults, 'wsMemberships')){
      $results = $result->WsGetMembershipsResults->wsMemberships;
      $memberships = array();

      foreach($results as $r) {
        $memberships[$r->subjectId][$r->groupName] = $r->immediateMembershipId;
      }
    } else {
      $memberships = array();
    }

    return $memberships;
  }

  /**
   * Get immediate memberships for a Grouper subject.
   * - precondition: 
   * - postcondition: 
   *
   * @since               COmanage Directory 0.7
   * @subject             subject
   * @groupScopePrefix    group name prefix required as scope       
   * @return              mixed
   * @throws GrouperRestClientException
   */
  public function getImmediateMemberships($subject, $groupScopePrefix = null) {
    $body = array(
      'WsRestGetMembershipsRequest' => array(
        'fieldName' => 'members',
        'memberFilter' => 'Immediate',
        'includeGroupDetail' => 'T',
        'includeSubjectDetail' => 'T',
        'wsSubjectLookups' => array(
            array('subjectId' => "$subject")
            )
        )
      );

    if (isset($groupScopePrefix)) {
      $body['WsRestGetMembershipsRequest']['scope'] = $groupScopePrefix;
    }

    $body = json_encode($body);

    $request = array(
      'uri' => array(
        'path' => 'memberships'
        ),
      'body' => $body
      );

    $result = $this->request($request, 200);
    $success = $result->WsGetMembershipsResults->resultMetadata->success;
    if ($success != 'T') {
      throw new GrouperRestClientException('Result from get memberships was not success');
    }

    return $result;
  }

  /**
   * Get members of a Grouper group.
   * - precondition: 
   * - postcondition: 
   *
   * @since               COmanage Directory 0.7
   * @names               array of group names
   * @return              array of subject ids
   * @throws              GrouperRestClientException
   */
  public function getMembersManyGroups($names) {
    $body = array(
      'WsRestGetMembersRequest' => array(
        'memberFilter' => 'Immediate',
        'includeGroupDetail' => 'T',
        'includeSubjectDetail' => 'T',
        'wsGroupLookups' => array(
          )
        )
      );

    foreach($names as $n) {
      $body['WsRestGetMembersRequest']['wsGroupLookups'][] = array('groupName' => $n);
    }

    $body = json_encode($body);

    $request = array(
      'uri' => array(
        'path' => 'groups'
        ),
      'body' => $body
      );

    $result = $this->request($request, 200);
    $success = $result->WsGetMembersResults->resultMetadata->success;
    if ($success != 'T') {
      throw new GrouperRestClientException('Result from get members was not success');
    }

    $results = $result->WsGetMembersResults->results;

    $members = array();
    foreach($results as $r) {
      $members[$r->wsGroup->name] = array();
      if (property_exists($r,'wsSubjects')){
        foreach($r->wsSubjects as $s){
          $members[$r->wsGroup->name][] = $s->id;
        }
      }
    }

    return $members;
  }

  /**
   * Get privileges assigned to subject(s) for a group.
   * - precondition: Group and subject exist.
   * - postcondition: Subject has assigned privileges.
   *
   * @since               COmanage Directory 0.7
   * @group               full name of group
   * @privileges          array of string names of privileges
   * @subject             subject Id
   * @return              array with names of privileges as keys and 0 or 1 for value
   * @throws              GrouperRestClientException
   */
  public function getPrivileges($group, $privileges, $subject) {
    $body = array(
      'WsRestGetGrouperPrivilegesLiteRequest' => array(
        'actAsSubjectId' => 'GrouperSystem',
        'groupName' => $group,
        'subjectId' => $subject
      )
    );

    $body = json_encode($body);

    $request = array(
      'uri' => array(
        'path' => 'grouperPrivileges'
        ),
      'body' => $body
      );

    $result = $this->request($request, 200);
    $success = $result->WsGetGrouperPrivilegesLiteResult->resultMetadata->success;
    if ($success != 'T') {
      throw new GrouperRestClientException('Result from get privileges was not success');
    }

    // Initialize all privileges requested to false since the 
    // query above only returns granted privileges.
    $assignedPrivileges = array();
    foreach($privileges as $p) {
      $assignedPrivileges[$p] = 0;
    }

    // Process the returned results and set any assigned privilege.
    $privilegeResults = $result->WsGetGrouperPrivilegesLiteResult->privilegeResults;
    foreach($privilegeResults as $r) {
      $priv = $r->privilegeName;
      if(array_key_exists($priv, $assignedPrivileges)) {
        $assignedPrivileges[$priv] = 1;
      }
    }

    return $assignedPrivileges;
  }

  /**
   * Create a new group or role in Grouper
   * - precondition: Group or role does not exist in Grouper
   * - postcondition: Group or role exists in Grouper
   *
   * @since             COmanage Directory 0.7
   * @groupName         full name of group including stem(s)
   * @description       description of the group 
   * @displayExtension  displayExtension for the group
   * @type              one of 'group' or 'role'
   * @return void
   * @throws GrouperRestClientException
   */
  public function groupSave($groupName, $description, $displayExtension, $type = 'group') {
    $body = array(
      'WsRestGroupSaveLiteRequest' => array(
        'actAsSubjectId' => 'GrouperSystem',
        'description' => $description,
        'displayExtension' => $displayExtension,
        'groupName' => $groupName,
        'typeOfGroup' => $type
        )
      );

    $body = json_encode($body);

    $request = array(
      'uri' => array(
        'path' => 'groups/' . urlencode($groupName)
        ),
      'body' => $body
      );

    $result = $this->request($request, 201);

    $success = $result->WsGroupSaveLiteResult->resultMetadata->success;
    if ($success != 'T') {
      throw new GrouperRestClientException('Result from group save was not success');
    }

    $resultCode = $result->WsGroupSaveLiteResult->resultMetadata->resultCode;
    if ($resultCode != 'SUCCESS_INSERTED') {
      throw new GrouperRestClientException('Group already existed');
    }

    return $result->WsGroupSaveLiteResult->wsGroup;
  }

  /**
   * Delete a group
   * - precondition: group exists in Grouper
   * - postcondition: group does not exist in Grouper
   *
   * @since       COmanage Directory 0.7
   * @groupName   full name of group including stem(s)
   * @return      void
   * @throws      GrouperRestClientException
   */
  public function groupDelete($groupName) {
    $request = array(
      'method' => 'DELETE',
      'uri' => array(
        'path' => 'groups/' . urlencode($groupName)
        )
      );

    $result = $this->request($request, 200);

    $success = $result->WsGroupDeleteLiteResult->resultMetadata->success;
    if ($success != 'T') {
      throw new GrouperRestClientException('Result from group delete was not success');
    }

    return ;
  }

  /**
   * Update an existing group
   * - precondition: group exists in Grouper
   * - postcondition: group exists in Grouper with updated name or description
   *
   * @since  COmanage Directory 0.7
   * @oldGroupName  old full name of group including stem(s)
   * @newGroupName  new full name of group including stem(s), may be same as old
   * @description description of the group 
   * @displayExtension display extension for the group 
   * @return void
   * @throws GrouperRestClientException
   */
  public function groupUpdate($oldGroupName, $newGroupName, $description, $displayExtension) {
    $body = array(
      'WsRestGroupSaveLiteRequest' => array(
        'saveMode' => 'UPDATE',
        'actAsSubjectId' => 'GrouperSystem',
        'description' => $description,
        'displayExtension' => $displayExtension,
        'groupName' => $newGroupName
        )
      );

    $body = json_encode($body);

    $request = array(
      'uri' => array(
        'path' => 'groups/' . urlencode($oldGroupName)
        ),
      'body' => $body
      );

    $result = $this->request($request, 201);

    $success = $result->WsGroupSaveLiteResult->resultMetadata->success;
    if ($success != 'T') {
      throw new GrouperRestClientException('Result from group save/update was not success');
    }

    $resultCode = $result->WsGroupSaveLiteResult->resultMetadata->resultCode;
    if ($resultCode != 'SUCCESS_UPDATED') {
        throw new GrouperRestClientException('Error updating group');
    }

    return ;
  }

  /**
   * Merge request arrays for HttpSocket instance
   * - precondition: 
   * - postcondition: 
   *
   * @since  COmanage Directory 0.7
   * @req1   first request array
   * @req2   second and dominant request array
   * @return void
   */
  private function mergeRequests($req1, $req2) {
    foreach ($req2 as $key => $value){
      if(array_key_exists($key, $req1) and is_array($value))
        $req1[$key] = $this->mergeRequests($req1[$key], $req2[$key]);
      else
        $req1[$key] = $value;
    }

    return $req1;
  }

  /**
   * Query Grouper using request array
   * - precondition: 
   * - postcondition: 
   *
   * @since         COmanage Directory 0.7
   * @request       request suitable for passing to HttpSocket instance
   * @expectedCode  expected return code from Grouper service
   * @return        decoded JSON response as object 
   * @throws        GrouperRestClientException
   */
  public function request(& $request, $expectedCode) {
    if (array_key_exists('uri', $request))
      if (array_key_exists('path', $request['uri']))
        $request['uri']['path'] = $this->defaultRequest['uri']['path'] . $request['uri']['path'];  

    $request = $this->mergeRequests($this->defaultRequest, $request);

    try {
      $response = $this->httpSocket->request($request);

    } catch (SocketException $e) {
      throw new GrouperRestClientException('Error querying Grouper: ' . $e->getMessage(), 1, $e);
    }

    $code = $response->code;
    if ($code != $expectedCode) {
      throw new GrouperRestClientException("Grouper returned code $code, expected was $expectedCode: $response");
    }

    try {
      $result = json_decode($response->body);
    } catch (Exception $e) {
      throw new GrouperRestClientException('Error decoding JSON from Grouper: ' . $e->getMessage(), 2, $e);
    }

    return $result;
  }

  /**
   * Remove privileges assigned to subject(s) in a group.
   * - precondition: Group and subjects exist with privileges.
   * - postcondition: Subjects no longer have assigned privileges.
   *
   * @since               COmanage Directory 0.7
   * @group               full name of group
   * @privileges          array of string names of privileges
   * @subjects            array of subject identifiers
   * @return              void
   * @throws              GrouperRestClientException
   */
  public function removePrivilege($group, $privileges, $subjects, $subjectSourceId = null) {
    $subjectLookups = array();
    foreach($subjects as $s) {
      $subjectLookupsValue = array();
      $subjectLookupsValue['subjectId'] = $s;
      if (!empty($subjectSourceId)) {
        $subjectLookupsValue['subjectSourceId'] = $subjectSourceId;
      }
      $subjectLookups[] = $subjectLookupsValue;
    }
    $body = array(
      'WsRestAssignGrouperPrivilegesRequest' => array(
        'actAsSubjectLookup' => array(
          'subjectId' => 'GrouperSystem'
        ),
        'allowed' => 'F',
        'privilegeNames' => $privileges,
        'privilegeType' => 'access',
        'replaceAllExisting' => 'F',
        'wsGroupLookup' => array(
          'groupName' => $group
        ),
        'wsSubjectLookups' => $subjectLookups
      )
    );

    $body = json_encode($body);

    $request = array(
      'uri' => array(
        'path' => 'grouperPrivileges'
        ),
      'body' => $body
      );

    $result = $this->request($request, 200);
    $success = $result->WsAssignGrouperPrivilegesResults->resultMetadata->success;
    if ($success != 'T') {
      throw new GrouperRestClientException('Result from removing privileges was not success');
    }

    return;
  }

  /**
   * Determine if a stem exists in Grouper
   * - precondition: 
   * - postcondition: 
   *
   * @since  COmanage Directory 0.7
   * @stemName  full name of stem
   * @return boolean
   * @throws GrouperRestClientException
   */
  public function stemExists($stemName) {
    $body = array(
      'WsRestFindStemsRequest' => array(
        'actAsSubjectLookup' => array(
          'subjectId' => 'GrouperSystem'
          ),
        'wsStemQueryFilter' => array(
          'stemName' => $stemName,
          'stemQueryFilterType' => 'FIND_BY_STEM_NAME'
          )
        )
      );

    $body = json_encode($body);

    $request = array(
      'uri' => array(
        'path' => 'stems',
        ),
      'body' => $body,
      );

    $result = $this->request($request, 200);
    $success = $result->WsFindStemsResults->resultMetadata->success;
    if ($success != 'T') {
      throw new GrouperRestClientException('Result from stem query was not success');
    }

    $stemExists = ! empty($result->WsFindStemsResults->stemResults);

    return $stemExists;
  }

  /**
   * Save or create a new stem
   * - precondition: 
   * - postcondition: 
   *
   * @since  COmanage Directory 0.7
   * @stemName  full name of stem
   * @description description of the stem 
   * @displayExtension displayExtension for the stem
   * @return void
   * @throws GrouperRestClientException
   */
  public function stemSave($stemName, $description, $displayExtension) {
    $body = array(
      'WsRestStemSaveLiteRequest' => array(
        'actAsSubjectId' => 'GrouperSystem',
        'description' => $description,
        'displayExtension' => $displayExtension,
        'stemName' => $stemName
        )
      );

    $body = json_encode($body);

    $request = array(
      'uri' => array(
        'path' => 'stems/' . urlencode($stemName)
        ),
      'body' => $body
      );

    $result = $this->request($request, 201);
    $success = $result->WsStemSaveLiteResult->resultMetadata->success;
    if ($success != 'T') {
      throw new GrouperRestClientException('Result from stem save was not success');
    }

    return $result->WsStemSaveLiteResult->wsStem;
  }

  /**
   * Update attribute value on stem
   * - precondition: 
   * - postcondition: 
   *
   * @since               COmanage Directory 0.7
   * @attributeName       full name of attribute
   * @stemName            full name of stem
   * @value               value to assign
   * @return              void
   * @throws              GrouperRestClientException
   */
  public function updateAttributeValueOnStem($attributeName, $stemName, $value) {
    $body = array(
      'WsRestAssignAttributesLiteRequest' => array(
        'actAsSubjectId' => 'GrouperSystem',
        'attributeAssignOperation' => 'assign_attr',
        'attributeAssignType' => 'stem',
        'attributeAssignValueOperation' => 'replace_values',
        'valueSystem' => $value,
        'wsAttributeDefNameName' => $attributeName,
        'wsOwnerStemName' => $stemName
        )
      );

    $body = json_encode($body);

    $request = array(
      'uri' => array(
        'path' => 'attributeAssignments'
        ),
      'body' => $body
      );

    $result = $this->request($request, 200);
    $success = $result->WsAssignAttributesLiteResults->resultMetadata->success;
    if ($success != 'T') {
      throw new GrouperRestClientException('Result from updating attribute value for stem was not success');
    }

    return ;
  }

  /**
   * Update attribute value
   * - precondition: 
   * - postcondition: 
   *
   * @since               COmanage Directory 0.7
   * @attributeName       full name of attribute
   * @attributeAssignType see Grouper WS documentation, usually one of 'group', 'imm_mem'
   * @ownerName           full name of owner, usually the group name or the membership id
   * @value               value to update/assign
   * @return              void
   * @throws              GrouperRestClientException
   */
  protected function updateAttributeWithValue($attributeName, $attributeAssignType, $ownerName, $value) {
    $body = array(
      'WsRestAssignAttributesLiteRequest' => array(
        'actAsSubjectId' => 'GrouperSystem',
        'attributeAssignOperation' => 'assign_attr',
        'attributeAssignType' => $attributeAssignType,
        'attributeAssignValueOperation' => 'replace_values',
        'valueSystem' => "$value",
        'wsAttributeDefNameName' => $attributeName
        )
      );

    if ($attributeAssignType == 'group') {
      $body['WsRestAssignAttributesLiteRequest']['wsOwnerGroupName'] = "$ownerName";
    } elseif ($attributeAssignType == 'imm_mem') {
      $body['WsRestAssignAttributesLiteRequest']['wsOwnerMembershipId'] = "$ownerName";
    }

    $body = json_encode($body);

    $request = array(
      'uri' => array(
        'path' => 'attributeAssignments'
        ),
      'body' => $body
      );

    $result = $this->request($request, 200);
    $success = $result->WsAssignAttributesLiteResults->resultMetadata->success;
    if ($success != 'T') {
      throw new GrouperRestClientException('Result from assign/update attribute with value to group/membership was not success');
    }

    return;
  }

  /**
   * Update attribute to group with value
   * - precondition: 
   * - postcondition: 
   *
   * @since               COmanage Directory 0.7
   * @attributeName       full name of attribute
   * @groupName           full name of group
   * @value               value to assign
   * @return              void
   * @throws              GrouperRestClientException
   */
  public function updateAttributeWithValueToGroup($attributeName, $groupName, $value) {
    return $this->updateAttributeWithValue($attributeName, 'group', $groupName, $value);
  }

  /**
   * Update attribute to immediate membership with value
   * - precondition: 
   * - postcondition: 
   *
   * @since               COmanage Directory 0.7
   * @attributeName       full name of attribute
   * @membershipId        memberbership Id for the immediate membership
   * @value               value to assign
   * @return              void
   * @throws              GrouperRestClientException
   */
  public function updateAttributeWithValueToImmediateMembership($attributeName, $membershipId, $value) {
    return $this->updateAttributeWithValue($attributeName, 'imm_mem', $membershipId, $value);
  }
}
