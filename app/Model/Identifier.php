<?php
/**
 * COmanage Registry Identifier Model
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
 * @package       registry
 * @since         COmanage Registry v0.1
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

class Identifier extends AppModel {
  // Define class name for cake
  public $name = "Identifier";
  
  // Current schema version for API
  public $version = "1.0";
  
  // Association rules from this model to other models
  public $belongsTo = array(
    // An identifier may be attached to a CO Department
    "CoDepartment",
    "CoGroup",
    // An identifier may be attached to a CO Person
    "CoPerson",
    // An identifier may be created from a Provisioner
    "CoProvisioningTarget",
    // An Ad Hoc Attribute may be attached to an Organization
    "Organization",
    // An identifier may be attached to an Org Identity
    "OrgIdentity",
    // An identifier created from a Pipeline has a Source Identifier
    "SourceIdentifier" => array(
      'className' => 'Identifier',
      'foreignKey' => 'source_identifier_id'
    )
  );
  
  // Default display field for cake generated views
  public $displayField = "Identifier.identifier";
  
  // Default ordering for find operations
//  public $order = array("Identifier.identifier");
  
  public $actsAs = array('Containable',
                         'Normalization' => array('priority' => 4),
                         'Provisioner',
                         'Changelog' => array('priority' => 5));
  
  // Validation rules for table elements
  // Validation rules must be named 'content' for petition dynamic rule adjustment
  public $validate = array(
    'identifier' => array(
      'content' => array(
        // Identifier must have at least one non-space character in order to avoid
        // errors (eg: with provisioning ldap)
        'rule' => 'notBlank',
        'required' => true,
        'allowEmpty' => false
      ),
      // We perform basic input validation because end users can input identifiers
      // via Enrollment Flows. This could cause problems with identifiers of very
      // specific formats, but for now we don't have any use cases.
      // See also Identifier Self Service (CO-1255).
      'filter' => array(
        'rule' => array('validateInput')
      )
    ),
    'type' => array(
      'content' => array(
        'rule' => array('validateExtendedType',
                        array('attribute' => 'Identifier.type',
                              'default' => array(IdentifierEnum::AffiliateSOR,
                                                 IdentifierEnum::Badge,
                                                 IdentifierEnum::Enterprise,
                                                 IdentifierEnum::ePPN,
                                                 IdentifierEnum::ePTID,
                                                 IdentifierEnum::ePUID,
                                                 IdentifierEnum::GuestSOR,
                                                 IdentifierEnum::HRSOR,
                                                 IdentifierEnum::Mail,
                                                 IdentifierEnum::National,
                                                 IdentifierEnum::Network,
                                                 IdentifierEnum::OIDCsub,
                                                 IdentifierEnum::OpenID,
                                                 IdentifierEnum::ORCID,
                                                 IdentifierEnum::ProvisioningTarget,
                                                 IdentifierEnum::Reference,
                                                 IdentifierEnum::SamlPairwise,
                                                 IdentifierEnum::SamlSubject,
                                                 IdentifierEnum::SORID,
                                                 IdentifierEnum::StudentSOR,
                                                 IdentifierEnum::UID))),
        'required' => true,
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
        'rule' => array('inList', array(SuspendableStatusEnum::Active,
                                        SuspendableStatusEnum::Suspended)),
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
    ),
    'co_department_id' => array(
      'content' => array(
        'rule' => 'numeric',
        'required' => false,
        'allowEmpty' => true
      )
    ),
    'co_group_id' => array(
      'content' => array(
        'rule' => 'numeric',
        'required' => false,
        'allowEmpty' => true
      )
    ),
    'source_identifier_id' => array(
      'content' => array(
        'rule' => 'numeric',
        'required' => false,
        'allowEmpty' => true
      )
    ),
    'co_provisioning_target_id' => array(
      'content' => array(
        'rule' => 'numeric',
        'required' => false,
        'allowEmpty' => true
      )
    )
  );
  
  // Enum type hints
  
  public $cm_enum_types = array(
    'status' => 'SuspendableStatusEnum',
  );
  
  /**
   * Perform CoEnrollmentFlow model upgrade steps for version 2.0.0.
   * This function should only be called by UpgradeVersionShell.
   *
   * @since  COmanage Registry v2.0.0
   */

  public function _ug110() {
    // v2.0.0 uses SuspendableStatusEnum instead of StatusEnum. We need to replace any
    // instances of 'D' with 'S'.
    // v2.0.0 was previously v1.1.0.
    
    // We use updateAll here which doesn't fire callbacks (including ChangelogBehavior).
    // We actually want to update archived rows so that petitions render properly
    // (ie: so they show the confirmation steps as relevant).
    
    $this->updateAll(array('Identifier.status' => "'" . SuspendableStatusEnum::Suspended . "'"),
                     array('Identifier.status' => StatusEnum::Deleted));
  }
  
  /**
   * Actions to take after a save operation is executed.
   *
   * @since  COmanage Registry v2.0.0
   * @param  boolean $created True if a new record was created (rather than update)
   * @param  array   $options As passed into Model::save()
   */

  public function afterSave($created, $options = array()) {
    // Commit any in-progress transaction
    $this->_commit();
  }
  
  /**
   * Autoassign identifiers for a CO Person.
   *
   * @since  COmanage Registry v0.6
   * @param  String  Object Type ("CoDepartment", "CoGroup", "CoPerson")
   * @param  Integer Object ID
   * @param  Integer Actor CO Person ID
   * @param  Boolean Whether or not to run provisioners on save
   * @param  Integer Actor API User ID
   * @return Array Success for each attribute, where the key is the attribute assigned and the value is 1 for success, 2 for already assigned, or an error string
   * @throws Exception
   * @throws InvalidArgumentException
   * @throws OverflowException
   */  
  
  function assign($objType, $objId, $actorCoPersonId, $provision=true, $actorApiUserId=null) {
    $ret = array();
    
    // First, pull the CO ID from the object record
    $coId = $this->$objType->field('co_id', array($objType.'.id' => $objId));
    
    if(!$coId) {
      throw new InvalidArgumentException(_txt('er.co.fail'));
    }
    
    // Next, see if there are any identifiers to autoassign for this CO. This will return the
    // same thing if the answer is "no" or if the answer is "invalid CO ID".
    $contexts = array(
      'CoDepartment' => IdentifierAssignmentContextEnum::CoDepartment,
      'CoGroup'      => IdentifierAssignmentContextEnum::CoGroup,
      'CoPerson'     => IdentifierAssignmentContextEnum::CoPerson
    );
    
    $args = array();
    $args['conditions']['CoIdentifierAssignment.co_id'] = $coId;
    $args['conditions']['CoIdentifierAssignment.context'] = $contexts[$objType];
    $args['conditions']['CoIdentifierAssignment.status'] = SuspendableStatusEnum::Active;
    $args['order'][] = 'CoIdentifierAssignment.ordr';
    $args['contain'] = false;
    
    $identifierAssignments = $this->CoPerson->Co->CoIdentifierAssignment->find('all', $args);
    
    if(!empty($identifierAssignments)) {
      // Loop through each identifier and request assignment.
      $cnt = 0;
      
      foreach($identifierAssignments as $ia) {
        // Assign will throw an error if an identifier of this type already exists.
        
        try {
          // We don't provision here, but instead once below after all identifiers are assigned
          $this->CoPerson->Co->CoIdentifierAssignment->assign($ia, $objType, $objId, $actorCoPersonId, false, $actorApiUserId);
          $ret[ $ia['CoIdentifierAssignment']['identifier_type'] ] = 1;
          $cnt++;
        }
        catch(OverflowException $e) {
          // An identifier already exists of this type for this CO Person/CO Group
          $ret[ $ia['CoIdentifierAssignment']['identifier_type'] ] = 2;
        }
        catch(Exception $e) {
          $ret[ $ia['CoIdentifierAssignment']['identifier_type'] ] = $e->getMessage();
        }
      }
      
      if($cnt > 0 && $provision && $objType != 'CoDepartment') {
        // At least one identifier was assigned, so fire provisioning
        // Currently, CoDepartments do not support provisioning
        
        $this->$objType->Behaviors->load('Provisioner');
        
        if($objType == 'CoGroup') {
          $this->CoGroup->manualProvision(null, null, $objId, ProvisioningActionEnum::CoGroupUpdated);
        } else {
          $this->CoPerson->manualProvision(null, $objId, null, ProvisioningActionEnum::CoPersonUpdated);
        }
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
   * @param  String  Object Type ("CoDepartment", "CoGroup", "CoPerson")
   * @param  Integer Object ID
   * @param  String Type of candidate identifier
   * @return Boolean True if an identifier of the specified type is already assigned, false otherwise
   */
  
  public function assigned($objType, $objId, $identifierType) {
    $fk = Inflector::underscore($objType) . "_id";
    
    $args = array();
    $args['conditions']['Identifier.'.$fk] = $objId;
    $args['conditions']['Identifier.type'] = $identifierType;
    $args['conditions']['Identifier.status'] = SuspendableStatusEnum::Active;
    
    $r = $this->findForUpdate($args['conditions'], array('identifier'));
    
    return !empty($r);
  }
  
  /**
   * Actions to take before a save operation is executed.
   *
   * @since  COmanage Registry v2.0.0
   * @param  array   $options As passed into Model::save()
   * @return Boolean True to continue with save, false to abort
   */

  public function beforeSave($options = array()) {
    if(isset($options['safeties']) && $options['safeties'] == 'off') {
      return true;
    }
    
    // We don't allow duplicate identifiers for either CoPerson or CoGroup,
    // but we (currently) don't enforce duplicate checking for CoDepartment
    // or OrgIdentity.
    
    if(!empty($this->data['Identifier']['co_person_id'])
       || !empty($this->data['Identifier']['co_group_id'])) {
      // If this is an edit operation, check if the identifier itself is being changed.
      // If not, we don't need to recheck availability.
      
      if(!empty($this->data['Identifier']['id'])) {
        // Pull the current record
        
        $args = array();
        $args['conditions']['Identifier.id'] = $this->data['Identifier']['id'];
        $args['contain'] = false;
        
        $curData = $this->find('first', $args);
        
        // Both the identifier and the type must be unchanged for us to skip this check
        if(!empty($curData['Identifier']['identifier'])
           && $curData['Identifier']['identifier'] == $this->data['Identifier']['identifier']
           && !empty($curData['Identifier']['type'])
           && $curData['Identifier']['type'] == $this->data['Identifier']['type']) {
          return true;
        }
      }
      
      // Start a transaction -- we'll commit in afterSave
      
      $this->_begin();
      
      // If availability checks were already run (ie: by CoIdentifierAssignment::assign)
      // we can skip the checks here. However, we allow the begin() so that we have the
      // correct number of nested begin/commit calls when afterSave() fires.
      
      if(!isset($options['skipAvailability']) || !$options['skipAvailability']) {
        $objectModel = (!empty($this->data['Identifier']['co_group_id']) ? 'CoGroup' : 'CoPerson');
        
        $coId = $this->$objectModel->field('co_id', array($objectModel.'.id' => $this->data['Identifier'][Inflector::underscore($objectModel).'_id']));
        
        // Run the internal availability check. This will remain consistent until
        // afterSave, though we can't assert the same for any external services
        // the plugins check.
        
        try {
          $this->checkAvailability($this->data['Identifier']['identifier'],
                                   $this->data['Identifier']['type'],
                                   $coId,
                                   false,
                                   $objectModel);
        }
        catch(Exception $e) {
          // Roll back the transaction and re-throw the exception
          $this->_rollback();
          
          $eclass = get_class($e);
          throw new $eclass($e->getMessage(), $e->getCode());
        }
      }
    }
    // else we currently don't do anything with org identity identifiers
    
    return true;
  }
  
  /**
   * Obtain the CO ID for a record.
   *
   * @since  COmanage Registry v3.3.0
   * @param  integer Record to retrieve for
   * @return integer Corresponding CO ID, or NULL if record has no corresponding CO ID
   * @throws InvalidArgumentException
   * @throws RuntimeException
   */

  public function findCoForRecord($id) {
    // The CO for the Identifier is available in whatever related model is populated

    $args = array();
    $args['conditions'][$this->alias.'.id'] = $id;
    $args['contain'] = array(
      'CoDepartment',
      'CoGroup',
      'CoPerson',
      'Organization',
      'OrgIdentity'
    );

    $identifier = $this->find('first', $args);
    
    if(!empty($identifier)) {
      foreach($args['contain'] as $m) {
        if(!empty($identifier[$m]['co_id'])) {
          return $identifier[$m]['co_id'];
        }
      }
    }
    
    throw new InvalidArgumentException(_txt('er.notfound', array(_txt('ct.identifiers.1'), $identifier)));
  }
  
  /**
   * Perform a keyword search.
   *
   * @since  COmanage Registry v3.1.0
   * @param  integer $coId  CO ID to constrain search to
   * @param  string  $q     String to search for
   * @param  integer $limit Search limit
   * @return Array Array of search results, as from find('all)
   */
  
  public function search($coId, $q, $limit) {
    $args = array();
    $args['conditions']['Identifier.identifier'] = $q;
    $args['conditions']['OR'] = array(
      'CoPerson.co_id' => $coId,
      'CoGroup.co_id' => $coId
    );
    $args['order'] = array('Identifier.identifier');
    $args['limit'] = $limit;
    $args['contain'] = array(
      'CoGroup',
      'CoPerson' => 'PrimaryName'
    );
    
    return $this->find('all', $args);
  }
}
