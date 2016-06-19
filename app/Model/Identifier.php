<?php
/**
 * COmanage Registry Identifier Model
 *
 * Copyright (C) 2010-16 University Corporation for Advanced Internet Development, Inc.
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
 * @copyright     Copyright (C) 2010-16 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.1
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */

class Identifier extends AppModel {
  // Define class name for cake
  public $name = "Identifier";
  
  // Current schema version for API
  public $version = "1.0";
  
  // Association rules from this model to other models
  public $belongsTo = array(
    // An identifier may be attached to a CO Person
    "CoPerson",
    // An identifier may be attached to an Org Identity
    "OrgIdentity"
  );
  
  // Default display field for cake generated views
  public $displayField = "identifier";
  
  // Default ordering for find operations
  public $order = array("identifier");
  
  public $actsAs = array('Containable',
                         'Provisioner',
                         'Changelog' => array('priority' => 5));
  
  // Validation rules for table elements
  // Validation rules must be named 'content' for petition dynamic rule adjustment
  public $validate = array(
    // Don't require any element since $belongsTo saves won't validate if they're empty
    'identifier' => array(
      'content' => array(
        // Identifier must have at least one non-space character in order to avoid
        // errors (eg: with provisioning ldap)
        'rule' => 'notBlank',
        'required' => false,
        'allowEmpty' => true
      )
    ),
    'type' => array(
      'content' => array(
        'rule' => array('validateExtendedType',
                        array('attribute' => 'Identifier.type',
                              'default' => array(IdentifierEnum::ePPN,
                                                 IdentifierEnum::ePTID,
                                                 IdentifierEnum::Mail,
                                                 IdentifierEnum::OpenID,
                                                 IdentifierEnum::UID))),
        'required' => false,
        'allowEmpty' => false
      )
    ),
    'login' => array(
      'content' => array(
        'rule' => array('boolean'),
        'required' => false,
        'allowEmpty' => true
      )
    ),
    'status' => array(
      'content' => array(
        'rule' => array('inList', array(StatusEnum::Active,
                                        StatusEnum::Deleted)),
        'required' => true,
        'allowEmpty' => false
      )
    ),
    'co_person_id' => array(
      'content' => array(
        'rule' => 'numeric',
        'required' => false,
        'allowEmpty' => true
      )
    ),
    'org_identity_id' => array(
      'content' => array(
        'rule' => 'numeric',
        'required' => false,
        'allowEmpty' => true
      )
    )
  );
  
  // Enum type hints
  
  public $cm_enum_types = array(
    'status' => 'StatusEnum',
  );

  /**
   * Autoassign identifiers for a CO Person.
   *
   * @since  COmanage Registry v0.6
   * @param  Integer CO ID
   * @param  Integer CO Person ID
   * @param  Integer Actor CO Person ID
   * @return Boolean Whether or not to run provisioners on save
   * @return Array Success for each attribute, where the key is the attribute assigned and the value is 1 for success, 2 for already assigned, or an error string
   */  
  
  function assign($coId, $coPersonId, $actorCoPersonId, $provision=true) {
    $ret = array();
    
    // First, see if there are any identifiers to autoassign for this CO. This will return the
    // same thing if the answer is "no" or if the answer is "invalid CO ID".
    
    $args = array();
    $args['conditions']['Co.id'] = $coId;
    
    $identifierAssignments = $this->CoPerson->Co->CoIdentifierAssignment->find('all', $args);
    
    if(!empty($identifierAssignments)) {
      // Loop through each identifier and request assignment.
      $cnt = 0;
      
      foreach($identifierAssignments as $ia) {
        // Assign will throw an error if an identifier of this type already exists.
        
        try {
          $this->CoPerson->Co->CoIdentifierAssignment->assign($ia, $coPersonId, $actorCoPersonId);
          $ret[ $ia['CoIdentifierAssignment']['identifier_type'] ] = 1;
          $cnt++;
        }
        catch(OverflowException $e) {
          // An identifier already exists of this type for this CO Person
          $ret[ $ia['CoIdentifierAssignment']['identifier_type'] ] = 2;
        }
        catch(Exception $e) {
          $ret[ $ia['CoIdentifierAssignment']['identifier_type'] ] = $e->getMessage();
        }
      }
      
      if($cnt > 0 && $provision) {
        // At least one identifier was assigned, so fire provisioning
        
        $this->CoPerson->Behaviors->load('Provisioner');
        $this->CoPerson->manualProvision(null, $coPersonId, null, ProvisioningActionEnum::CoPersonUpdated);
      }
    }
    
    return $ret;
  }
  
  /**
   * Determine if an identifier of a given type is already assigned to a CO Person.
   * Only active identifiers are considered.
   *
   * IMPORTANT: This function should be called within a transaction to ensure
   * actions taken based on availability are atomic.
   *
   * @since  COmanage Registry v0.6
   * @param  Integer CO Person ID
   * @param  String Type of candidate identifier
   * @return Boolean True if an identifier of the specified type is already assigned, false otherwise
   */
  
  public function assigned($coPersonID, $identifierType) {
    $args = array();
    $args['conditions']['Identifier.co_person_id'] = $coPersonID;
    $args['conditions']['Identifier.type'] = $identifierType;
    $args['conditions']['Identifier.status'] = StatusEnum::Active;
    
    $r = $this->findForUpdate($args['conditions'], array('identifier'));
    
    return !empty($r);
  }
  
  /**
   * Check if an identifier is available for assignment. An identifier is available
   * if it is not defined (regardless of status) within the same CO.
   *
   * IMPORTANT: This function should be called within a transaction to ensure
   * actions taken based on availability are atomic.
   *
   * @since  COmanage Registry v0.6
   * @param  String Candidate identifier
   * @param  String Type of candidate identifier
   * @param  Integer CO ID
   * @return Boolean True if identifier is not in use, false otherwise
   */
  
  public function checkAvailability($identifier, $identifierType, $coId) {
    // In order to allow ensure that another process doesn't perform the same
    // availability check while we're running, we need to lock the appropriate
    // tables/rows at read time. We do this with findForUpdate instead of a normal find.
    
    $args = array();
    $args['conditions']['CoPerson.co_id'] = $coId;
    $args['conditions']['Identifier.identifier'] = $identifier;
    $args['conditions']['Identifier.type'] = $identifierType;
    $args['joins'][0]['table'] = 'co_people';
    $args['joins'][0]['alias'] = 'CoPerson';
    $args['joins'][0]['type'] = 'INNER';
    $args['joins'][0]['conditions'][0] = 'CoPerson.id=Identifier.co_person_id';
    $args['contain'] = false;
    
    $r = $this->findForUpdate($args['conditions'],
                              array('identifier'),
                              $args['joins']);
    
    return empty($r);
  }
}
