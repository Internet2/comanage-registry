<?php
/**
 * COmanage Registry CO Person Model
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

class CoPerson extends AppModel {
  // Define class name for cake
  public $name = "CoPerson";
  
  // Current schema version for API
  public $version = "1.0";
  
  // Add behaviors
  public $actsAs = array('Containable', 'Provisioner');
  
  // Association rules from this model to other models
  public $belongsTo = array("Co");                    // A CO Person Source is attached to one CO
  
  public $hasOne = array(
    "CoNsfDemographic" => array('dependent' => true),
    // A person can have one invite (per CO)
    "CoInvite" => array('dependent' => true),
    // An Org Identity has one Primary Name, which is a pointer to a Name
    "PrimaryName" => array(
      'className'  => 'Name',
      'conditions' => array('PrimaryName.primary_name' => true),
      'dependent'  => false,
      'foreignKey' => 'co_person_id'
    )
  );
  
  public $hasMany = array(
    // A person can have one or more groups
    "CoGroupMember" => array('dependent' => true),
    // A person can have more than one org identity
    "CoOrgIdentityLink" => array('dependent' => true),
    // A person can have one or more person roles
    "CoPersonRole" => array('dependent' => true),
    "CoPetitionApprover" => array(
      'className' => 'CoPetition',
      'dependent' => true,
      'foreignKey' => 'approver_co_person_id'
    ),
    "CoPetitionEnrollee" => array(
      'className' => 'CoPetition',
      'dependent' => true,
      'foreignKey' => 'enrollee_co_person_id'
    ),
    "CoPetitionPetitioner" => array(
      'className' => 'CoPetition',
      'dependent' => true,
      'foreignKey' => 'petitioner_co_person_id'
    ),
    "CoPetitionSponsor" => array(
      'className' => 'CoPetition',
      'dependent' => true,
      'foreignKey' => 'sponsor_co_person_id'
    ),
    // A person can be an actor on a petition and generate history
    "CoPetitionHistoryRecord" => array(
      'foreignKey' => 'actor_co_person_id'
    ),
    "CoProvisioningExport" => array('dependent' => true),
    "CoTAndCAgreement" => array('dependent' => true),
    // A person can have one or more email address
    "EmailAddress" => array('dependent' => true),
    // We allow dependent=true for co_person_id but not for actor_co_person_id (see CO-404).
    "HistoryRecord" => array(
      'dependent' => true,
      'foreignKey' => 'co_person_id'
    ),
    // A person can have many identifiers within a CO
    "Identifier" => array('dependent' => true),
    "Name" => array('dependent' => true)
  );

  // Default display field for cake generated views
  public $displayField = "PrimaryName.family";
  
  // Default ordering for find operations
// XXX CO-296 Toss default order?
//  public $order = array("CoPerson.id");
  
  // Validation rules for table elements
  public $validate = array(
    'co_id' => array(
      'rule' => 'numeric',
      'required' => true,
      'message' => 'A CO ID must be provided'
    ),
    'primary_name_id' => array(
      'rule' => 'numeric',
      'required' => false,
      'allowEmpty' => true
    ),
    'status' => array(
      'rule' => array('inList', array(StatusEnum::Active,
                                      StatusEnum::Approved,
                                      StatusEnum::Declined,
                                      StatusEnum::Deleted,
                                      StatusEnum::Denied,
                                      StatusEnum::Invited,
                                      StatusEnum::Pending,
                                      StatusEnum::PendingApproval,
                                      StatusEnum::PendingConfirmation,
                                      StatusEnum::Suspended)),
      'required' => true,
      'message' => 'A valid status must be selected'
    )
  );
  
  // Enum type hints
  
  public $cm_enum_types = array(
    'status' => 'status_t'
  );
  
  /**
   * Obtain all people associated with a Group
   *
   * @since  COmanage Registry v0.6
   * @param  Integer CO Group ID
   * @param  Integer Maximium number of results to retrieve (or null)
   * @param  Integer Offset to start retrieving results from (or null)
   * @return Array CoPerson information, as returned by find (with some associated data)
   */
  
  function findForCoGroup($coGroupId, $limit=null, $offset=null) {
    $args = array();
    $args['joins'][0]['table'] = 'co_group_members';
    $args['joins'][0]['alias'] = 'CoGroupMember';
    $args['joins'][0]['type'] = 'INNER';
    $args['joins'][0]['conditions'][0] = 'CoPerson.id=CoGroupMember.co_person_id';
    $args['conditions']['CoGroupMember.co_group_id'] = $coGroupId;
    $args['conditions']['OR']['CoGroupMember.member'] = 1;
    $args['conditions']['OR']['CoGroupMember.owner'] = 1;
    // We use contain here to pull data for VootController
    $args['contain'][] = 'PrimaryName';
    $args['contain'][] = 'EmailAddress';
    
    if($limit) {
      $args['limit'] = $limit;
    }
    
    if($offset) {
      $args['offset'] = $offset;
    }
    
    return $this->find('all', $args);
  }
  
  /**
   * Obtain the CO Person ID for an identifier (which must be Active).
   *
   * @since  COmanage Registry v0.6
   * @param  String Identifier
   * @param  String Identifier type (null for any type; not recommended)
   * @param  Boolean Login identifiers only
   * @return Array CO Person IDs
   * @throws InvalidArgumentException
   */
  
  public function idForIdentifier($coId, $identifier, $identifierType=null, $login=false) {
    // Notice confusing change in order of arguments due to which ones default to null/false
    
    try {
      $coPersonIds = $this->idsForIdentifier($identifier, $identifierType, $login, $coId);
    }
    catch(Exception $e) {
      throw new InvalidArgumentException($e->getMessage());
    }
    
    return $coPersonIds[0];
  }
  
  /**
   * Obtain all CO Person IDs for an identifier (which must be Active).
   *
   * @since  COmanage Registry v0.6
   * @param  String Identifier
   * @param  String Identifier type (null for any type; not recommended)
   * @param  Boolean Login identifiers only
   * @param  Integer CO ID (null for all matching COs)
   * @return Array CO Person IDs
   * @throws InvalidArgumentException
   */
  
  function idsForIdentifier($identifier, $identifierType=null, $login=false, $coId=null) {
    // First pull the identifier record
    
    $args = array();
    $args['conditions']['Identifier.identifier'] = $identifier;
    if($login) {
      $args['conditions']['Identifier.login'] = true;
    }
    $args['conditions']['Identifier.status'] = StatusEnum::Active;
    $args['contain'] = false;
    
    if($coId != null) {
      // Only pull records associated with this CO ID
      
      $args['joins'][0]['table'] = 'co_people';
      $args['joins'][0]['alias'] = 'CoPerson';
      $args['joins'][0]['type'] = 'LEFT';
      $args['joins'][0]['conditions'][0] = 'Identifier.co_person_id=CoPerson.id';
      $args['joins'][1]['table'] = 'org_identities';
      $args['joins'][1]['alias'] = 'OrgIdentity';
      $args['joins'][1]['type'] = 'LEFT';
      $args['joins'][1]['conditions'][0] = 'Identifier.org_identity_id=OrgIdentity.id';
      $args['conditions']['OR']['CoPerson.co_id'] = $coId;
      
      $CmpEnrollmentConfiguration = ClassRegistry::init('CmpEnrollmentConfiguration');
      
      if($CmpEnrollmentConfiguration->orgIdentitiesPooled()) {
        $args['conditions']['OR'][] = 'OrgIdentity.co_id IS NULL';
      } else {
        $args['conditions']['OR']['OrgIdentity.co_id'] = $coId;
      }
    }
    
    if($identifierType) {
      $args['conditions']['Identifier.type'] = $identifierType;
    }
    
    // If identifierType is null, we might get more than one record, in which
    // case we behave nondeterministically. We can't pull all records, since we
    // might get multiple people. Better to supply a type.
    $id = $this->Identifier->find('first', $args);
    
    if(!empty($id)) {
      if(isset($id['Identifier']['co_person_id'])) {
        // The identifier is attached to a CO Person, return that ID.
        
        return array($id['Identifier']['co_person_id']);
      } else {
        // Map the org identity to a CO person. We might pull more than one.
        // In this case, it's OK since they come back to the same org person.
        
        $args = array();
        $args['conditions']['CoOrgIdentityLink.org_identity_id'] = $id['Identifier']['org_identity_id'];
        $args['fields'][] = 'CoOrgIdentityLink.co_person_id';
        $args['contain'] = false;
        
        if($coId != null) {
          $args['joins'][0]['table'] = 'co_people';
          $args['joins'][0]['alias'] = 'CoPerson';
          $args['joins'][0]['type'] = 'INNER';
          $args['joins'][0]['conditions'][0] = 'CoOrgIdentityLink.co_person_id=CoPerson.id';
          $args['conditions']['CoPerson.co_id'] = $coId;
        }
        
        $links = $this->CoOrgIdentityLink->find('list', $args);
        
        if(!empty($links)) {
          return array_values($links);
        }
      }
      
      throw new InvalidArgumentException(_txt('er.cop.unk'));
    } else {
      throw new InvalidArgumentException(_txt('er.id.unk'));
    }
  }
  
  /**
   * Attempt to match existing records based on the provided criteria.
   *
   * @since  COmanage Registry v0.5
   * @param  integer Identifier of CO
   * @param  Array Hash of field name + search pattern pairs
   * @return Array CO Person records of matching individuals
   */
  
  public function match($coId, $criteria) {
    // XXX For now, we only support Name. That's not the right long term design.
    
    // We need to have at least one non-trivial condition
    if((!isset($criteria['Name.given']) || strlen($criteria['Name.given']) < 3)
       && (!isset($criteria['Name.family']) || strlen($criteria['Name.family']) < 3)) {
      return(array());
    }
    
    // To perform case insensitive searching, we convert everything to lowercase
    if(isset($criteria['Name.given'])) {
      $args['conditions']['LOWER(Name.given) LIKE'] = strtolower($criteria['Name.given']) . '%';
    }
    if(isset($criteria['Name.family'])) {
      $args['conditions']['LOWER(Name.family) LIKE'] = strtolower($criteria['Name.family']) . '%';
    }
    $args['conditions']['CoPerson.co_id'] = $coId;
    $args['joins'][0]['table'] = 'names';
    $args['joins'][0]['alias'] = 'Name';
    $args['joins'][0]['type'] = 'INNER';
    $args['joins'][0]['conditions'][0] = 'CoPerson.id=Name.co_person_id';
    $args['contain'][] = 'PrimaryName';
    $args['contain'][] = 'CoPersonRole';
    
    return $this->find('all', $args);
  }
  
  /**
   * Determine if an org identity is already associated with a CO.
   *
   * @since  COmanage Registry v0.3
   * @param  integer Identifier of CO
   * @param  integer Identifier of Org Identity
   * @return boolean true if $orgIdentityId is linked to $coId, false otherwise
   */
  
  public function orgIdIsCoPerson($coId, $orgIdentityId) {
    // Try to retrieve a link for this org identity id where the co person id
    // is a member of this CO
      
    $args['joins'][0]['table'] = 'co_org_identity_links';
    $args['joins'][0]['alias'] = 'CoOrgIdentityLink';
    $args['joins'][0]['type'] = 'INNER';
    $args['joins'][0]['conditions'][0] = 'CoPerson.id=CoOrgIdentityLink.co_person_id';
    $args['conditions']['CoOrgIdentityLink.org_identity_id'] = $orgIdentityId;
    $args['conditions']['CoPerson.co_id'] = $coId;
    $args['contain'] = false;
    
    $link = $this->find('first', $args);
    
    if(!empty($link)) {
      return true;
    }
    
    return false;
  }

  /**
   * Determine the current status of the provisioning targets for this CO Person.
   *
   * @since  COmanage Registry v0.8
   * @param  Integer CO Person ID
   * @return Array Current status of provisioning targets
   * @throws RuntimeException
   */
  
  public function provisioningStatus($coPersonId) {
    // First, obtain the list of active provisioning targets for this person's CO.
    
    $args = array();
    $args['joins'][0]['table'] = 'co_people';
    $args['joins'][0]['alias'] = 'CoPerson';
    $args['joins'][0]['type'] = 'INNER';
    $args['joins'][0]['conditions'][0] = 'CoPerson.co_id=CoProvisioningTarget.co_id';
    $args['conditions']['CoPerson.id'] = $coPersonId;
    $args['conditions']['CoProvisioningTarget.status !='] = ProvisionerStatusEnum::Disabled;
    $args['contain'] = false;
    
    $targets = $this->Co->CoProvisioningTarget->find('all', $args);
    
    if(!empty($targets)) {
      // Next, for each target ask the relevant plugin for the status for this person.
      
      // We may end up querying the same Plugin more than once, so maintain a cache.
      $plugins = array();
      
      for($i = 0;$i < count($targets);$i++) {
        $pluginModelName = $targets[$i]['CoProvisioningTarget']['plugin']
                         . ".Co" . $targets[$i]['CoProvisioningTarget']['plugin'] . "Target";
        
        if(!isset($plugins[ $pluginModelName ])) {
          $plugins[ $pluginModelName ] = ClassRegistry::init($pluginModelName, true);
          
          if(!$plugins[ $pluginModelName ]) {
            throw new RuntimeException(_txt('er.plugin.fail', array($pluginModelName)));
          }
        }
        
        $targets[$i]['status'] = $plugins[ $pluginModelName ]->status($targets[$i]['CoProvisioningTarget']['id'],
                                                                      $coPersonId);
      }
    }
    
    return $targets;
  }
  
  /**
   * Retrieve list of sponsors for display in dropdown.
   *
   * @since  COmanage Registry v0.3
   * @return Array Array with co_person id as keys and full name as values
   */
  
  public function sponsorList($co_id) {
    // Query database for people
    $args = array(
      'contain'    => array('PrimaryName'),
      'order'      => array('PrimaryName.family ASC'),
      'conditions' => array('CoPerson.co_id' => $co_id)
    );

    $nameData = $this->find('all', $args);

    // Make data human readable for dropdown, keyed by id
    $drop = array();

    foreach($nameData as $pers)
    {
      $drop[ $pers['CoPerson']['id'] ] = generateCn($pers['PrimaryName'], true);
    }
    return $drop;
  }
}
