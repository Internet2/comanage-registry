<?php
/**
 * COmanage Registry CO Petition Model
 *
 * Copyright (C) 2011-13 University Corporation for Advanced Internet Development, Inc.
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
 * @copyright     Copyright (C) 2011-13 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.3
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */

App::uses('CakeEmail', 'Network/Email');

class CoPetition extends AppModel {
  // Define class name for cake
  public $name = "CoPetition";
  
  // Current schema version for API
  public $version = "1.0";
  
  // Add behaviors
  public $actsAs = array('Containable');
  
  // Association rules from this model to other models
  public $belongsTo = array(
    "ApproverCoPerson" => array(
      'className' => 'CoPerson',
      'foreignKey' => 'approver_co_person_id'
    ),
    "Co",                // A CO Petition is associated with a CO
    "CoInvite",
    "Cou",               // A CO Petition may be associated with a COU
    "CoEnrollmentFlow",  // A CO Petition follows a CO Enrollment Flow
    "EnrolleeCoPerson" => array(
      'className' => 'CoPerson',
      'foreignKey' => 'enrollee_co_person_id'
    ),
    "EnrolleeCoPersonRole" => array(
      'className' => 'CoPersonRole',
      'foreignKey' => 'enrollee_co_person_role_id'
    ),
    "EnrolleeOrgIdentity" => array(
      'className' => 'OrgIdentity',
      'foreignKey' => 'enrollee_org_identity_id'
    ),
    "PetitionerCoPerson" => array(
      'className' => 'CoPerson',
      'foreignKey' => 'petitioner_co_person_id'),
    "SponsorCoPerson" => array(
      'className' => 'CoPerson',
      'foreignKey' => 'sponsor_co_person_id')
  );
  
  public $hasMany = array(
    // A CO Petition has zero or more CO Petition Attributes
    "CoPetitionAttribute" => array('dependent' => true),
    // A CO Petition has zero or more CO Petition History Records
    "CoPetitionHistoryRecord" => array('dependent' => true)
  );
  
  // Default display field for cake generated views
  public $displayField = "CoPetition.id";
  
  // Default ordering for find operations
// XXX CO-296 Toss default order? id will be ambiguous in some queries, but CoPetition.id
// breaks delete cascading since the model may be aliased to (eg) CoPetitionApprover.
//  public $order = array("id");
  
  // Validation rules for table elements
  public $validate = array(
    'co_enrollment_flow_id' => array(
      'rule' => 'numeric',
      'required' => false,
      'allowEmpty' => true
    ),
    'co_id' => array(
      'rule' => 'numeric',
      'required' => false,
      'allowEmpty' => true
    ),
    'cou_id' => array(
      'rule' => 'numeric',
      'required' => false,
      'allowEmpty' => true
    ),
    'enrollee_org_identity_id' => array(
      'rule' => 'numeric',
      'required' => false,
      'allowEmpty' => true
    ),
    'enrollee_co_person_id' => array(
      'rule' => 'numeric',
      'required' => false,
      'allowEmpty' => true
    ),
    'enrollee_co_person_role_id' => array(
      'rule' => 'numeric',
      'required' => false,
      'allowEmpty' => true
    ),
    'petitioner_co_person_id' => array(
      'rule' => 'numeric',
      'required' => false,
      'allowEmpty' => true
    ),
    'sponsor_co_person_id' => array(
      'rule' => 'numeric',
      'required' => false,
      'allowEmpty' => true
    ),
    'approver_co_person_id' => array(
      'rule' => 'numeric',
      'required' => false,
      'allowEmpty' => true
    ),
    'co_invite_id' => array(
      'rule' => 'numeric',
      'required' => false,
      'allowEmpty' => true
    ),
    'status' => array(
      'rule' => array('inList', array(StatusEnum::Approved,
                                      StatusEnum::Declined,
                                      StatusEnum::Denied,
                                      StatusEnum::Invited,
                                      StatusEnum::PendingApproval,
                                      StatusEnum::PendingConfirmation)),
      'required' => true,
      'message' => 'A valid status must be selected'
    )
  );
  
  // Enum type hints
  
  public $cm_enum_types = array(
    'status' => 'status_t'
  );
  
  /**
   * Adjust a model's validation rules for use in Petition validation.
   * - postcondition: Model's validation rules are updated
   *
   * @since  COmanage Registry v0.7
   * @param  String Model to be adjusted
   * @param  Array Enrollment Flow attributes, as returned by CoEnrollmentAttribute::enrollmentFlowAttributes()
   */
  
  public function adjustValidationRules($model, $efAttrs) {
    // XXX With Cake 2.2 we can use dynamic validation rules instead of mucking around this way (CO-353)
    
    foreach($efAttrs as $efAttr) {
      // The model might be something like EnrolleeCoPersonRole or EnrolleeCoPersonRole.Name
      // or EnrolleeCoPersonRole.TelephoneNumber.0. However, since we only adjust validation
      // rules for top-level attributes, the first type is the only one we care about.
      
      $m = explode('.', $efAttr['model'], 3);
      
      if(count($m) == 1) {
        if($m[0] == $model) {
          $this->$model->validate[ $efAttr['field'] ] = $efAttr['validate'];
          
          $this->$model->validate[ $efAttr['field'] ]['required'] = $efAttr['required'];
          $this->$model->validate[ $efAttr['field'] ]['allowEmpty'] = !$efAttr['required'];
        }
      }
    }
  }
  
  /**
   * Determine if a related Model is optional, and if so if it is empty (ie: not
   * provided in the petition).
   *
   * @since  COmanage Registry v0.7
   * @param  Integer CO Enrollment Attribute ID
   * @param  Array Data for model
   * @param  Array Enrollment Flow attributes, as returned by CoEnrollmentAttribute::enrollmentFlowAttributes()
   * @return Boolean True if the Model is optional and if it is empty, false otherwise
   */
  
  public function attributeOptionalAndEmpty($efAttrID, $data, $efAttrs) {
    // Since when we're called createPetition has already pulled $efAttrs from the
    // database, we traverse it looking for $efAttrID rather than do another database
    // call for just the relevant records.
    
    foreach($efAttrs as $efAttr) {
      // More than one entry can match a given attribute ID.
      
      if($efAttr['id'] != $efAttrID) {
        // Skip this one, it's not the attribute ID we're looking for
        continue;
      }
      
      if($efAttr['hidden']) {
        // Skip hidden fields because they aren't user-editable
        continue;
      }
      
      if($efAttr['field'] == 'login'
         && (strncmp($efAttr['attribute'], 'i:identifier', 12)==0
             || strncmp($efAttr['attribute'], 'p:identifier', 12)==0)) {
        // For identifiers, skip login since it's not the primary element and it's
        // hard to tell if it's empty or not (since it's boolean)
        
        continue;
      }
      
      if($efAttr['required']) {
        // We found a required flag, so stop
        
        return false;
      }
      
      if(isset($data[ $efAttr['field'] ]) &&
         $data[ $efAttr['field'] ] != "") {
        // Field is set, so stop
        
        return false;
      }
    }
    
    return true;
  }
  
  /**
   * Create a new CO Petition.
   *
   * @since  COmanage Registry v0.6
   * @param  Integer Enrollment Flow ID
   * @param  Integer CO ID to attach the petition to
   * @param  Array   Request data, as provided by (eg) $this->request->data
   * @param  Integer CO Person ID of the petitioner
   * @return Integer ID of newly created Petition
   * @throws InvalidArgumentException
   * @throws RunTimeException
   */
  
  public function createPetition($enrollmentFlowID, $coId, $requestData, $petitionerId) {
    // Don't cleverly rename this to create(), since there is already a create method
    // in the default Model.
    
    // We have to do a bunch of non-standard work here. We're passed a bunch of data
    // for other models, basically enough to create a Person/Role. We need to use
    // it to create appropriate records, then create a Petition record with the
    // appropriate entries.
    
    $orgIdentityID = null;
    $coPersonID = null;
    $coPersonRoleID = null;
    
    // Track whether or not our processing was successful.
    
    $fail = false;
    
    // Determine an initial status. We don't jump straight to Active, since post-creation actions may be required for that.
    
    $verifyEmail = $this->CoEnrollmentFlow->field('verify_email',
                                                   array('CoEnrollmentFlow.id' => $enrollmentFlowID));
    
    $requireAuthn = $this->CoEnrollmentFlow->field('require_authn',
                                                   array('CoEnrollmentFlow.id' => $enrollmentFlowID));
    
    $approvalPolicy = $this->CoEnrollmentFlow->field('approval_required',
                                                     array('CoEnrollmentFlow.id' => $enrollmentFlowID));
    
    $initialStatus = StatusEnum::Approved;
    
    if($verifyEmail || $requireAuthn) {
      $initialStatus = StatusEnum::PendingConfirmation;
    } elseif($approvalPolicy) {
      $initialStatus = StatusEnum::PendingApproval;
    }
    
    // Start a transaction. We don't really need to save until we validate CO Person Role
    // (which needs co_person_id), but for consistency we'll follow a validate/save/rollback-on-error
    // pattern.
    
    $dbc = $this->getDataSource();
    $dbc->begin();
    
    // Walk through the request data and validate it manually. We have to do it this
    // way because it's possible for an enrollment flow to define (say) two addresses,
    // one of which is required and one of which is optional. (We can't just directly
    // rely on Cake since enrollment flow rules may not match up with default model rules.)
    
    // We try validating all user provided data (ie: not the data we assemble ourselves,
    // such as the Petition), even if some failed, in order to generate the full
    // set of errors at once when re-rendering the petition form.
    
    // Start by pulling the enrollment attributes configuration.
    
    $efAttrs = $this->CoEnrollmentFlow->CoEnrollmentAttribute->enrollmentFlowAttributes($enrollmentFlowID);
    
    // Obtain a list of enrollment flow attributes' required status for use later.
    
    $fArgs = array();
    $fArgs['conditions']['CoEnrollmentAttribute.co_enrollment_flow_id'] = $enrollmentFlowID;
    $fArgs['fields'] = array('CoEnrollmentAttribute.id', 'CoEnrollmentAttribute.required');
    $reqAttrs = $this->CoEnrollmentFlow->CoEnrollmentAttribute->find("list", $fArgs);
    
    // Obtain a list of attributes that are to be copied to the CO Person (Role) from the Org Identity
    
    $cArgs = array();
    $cArgs['conditions']['CoEnrollmentAttribute.co_enrollment_flow_id'] = $enrollmentFlowID;
    $cArgs['conditions']['CoEnrollmentAttribute.copy_to_coperson'] = true;
    $cArgs['fields'] = array('CoEnrollmentAttribute.id', 'CoEnrollmentAttribute.attribute');
    $copyAttrs = $this->CoEnrollmentFlow->CoEnrollmentAttribute->find("list", $cArgs);
    
    // Adjust validation rules for top level attributes only (OrgIdentity, CO Person, CO Person Role)
    // and validate those models without validating the associated models.
    
    // We'll start building an array of org data to save as we validate the provided data.
    
    $orgData = array();
    
    $CmpEnrollmentConfiguration = ClassRegistry::init('CmpEnrollmentConfiguration');
    
    if($CmpEnrollmentConfiguration->orgIdentitiesFromCOEF()) {
      // Platform is configured to pull org identities from the form.
      
      // Assemble OrgIdentity attributes.
      
      $orgData['EnrolleeOrgIdentity'] = $this->EnrolleeOrgIdentity->filterModelAttributes($requestData['EnrolleeOrgIdentity']);
      
      // Attach this org identity to this CO (if appropriate)
      
      if(!$CmpEnrollmentConfiguration->orgIdentitiesPooled())
        $orgData['EnrolleeOrgIdentity']['co_id'] = $coId;
      
      // Dynamically adjust validation rules according to the enrollment flow
      $this->adjustValidationRules('EnrolleeOrgIdentity', $efAttrs);
      
      // Manually validate OrgIdentity
      $this->EnrolleeOrgIdentity->set($orgData);
      
      // Make sure to use invalidFields(), which won't try to validate (possibly
      // missing) related models.
      $errFields = $this->EnrolleeOrgIdentity->invalidFields();
      
      if(!empty($errFields)) {
        $fail = true;
      }
      
      // Now validate related models
      
      $v = $this->validateRelated("EnrolleeOrgIdentity", $requestData, $orgData, $efAttrs);
      
      if($v) {
        $orgData = $v;
      } else {
        $fail = true;
      }
    } else {
      // The Org Identity will need to be populated via some other way,
      // such as via attributes pulled during login.
      
      throw new RuntimeException("Not implemented");
    }
    
    if(!$fail && !empty($orgData)) {
      // Save the Org Identity. All the data is validated, so don't re-validate it.
      
      if($this->EnrolleeOrgIdentity->saveAssociated($orgData, array("validate" => false, "atomic" => true))) {
        $orgIdentityID = $this->EnrolleeOrgIdentity->id;
        
        // Create a history record
        try {
          $this->EnrolleeOrgIdentity->HistoryRecord->record(null,
                                                            null,
                                                            $orgIdentityID,
                                                            $petitionerId,
                                                            ActionEnum::OrgIdAddedPetition);
        }
        catch(Exception $e) {
          $dbc->rollback();
          throw new RuntimeException($e->getMessage());
        }
      } else {
        $dbc->rollback();
        throw new RuntimeException(_txt('er.db.save'));
      }
    }
    
    // Validate CO Person. As of this writing, there really isn't much to validate,
    // but that could change.
    
    $coData = array();
    
    // Check the Match policy for this Enrollment Flow.
    
    $matchPolicy = $this->CoEnrollmentFlow->field('match_policy',
                                                  array('CoEnrollmentFlow.id' => $enrollmentFlowID));
    
    if($matchPolicy == EnrollmentMatchPolicyEnum::Self) {
      // The enrollee is also the petitioner, so just take the petitioner's CO Person identity
      
      $coPersonID = $petitionerId;
      
      // Create a history record
      try {
        $this->EnrolleeCoPerson->HistoryRecord->record($coPersonID,
                                                       null,
                                                       $orgIdentityID,
                                                       $petitionerId,
                                                       ActionEnum::CoPersonMatchedPetition);
      }
      catch(Exception $e) {
        $dbc->rollback();
        throw new RuntimeException($e->getMessage());
      }
    } else {
      if(!empty($requestData['EnrolleeCoPerson'])) {
        $coData['EnrolleeCoPerson'] = $this->EnrolleeCoPerson->filterModelAttributes($requestData['EnrolleeCoPerson']);
      }
      $coData['EnrolleeCoPerson']['co_id'] = $coId;
      $coData['EnrolleeCoPerson']['status'] = $initialStatus;
      
      // Dynamically adjust validation rules according to the enrollment flow
      $this->adjustValidationRules('EnrolleeCoPerson', $efAttrs);
      
      // Manually validate CoPerson
      $this->EnrolleeCoPerson->create($coData);
      
      // Make sure to use invalidFields(), which won't try to validate (possibly
      // missing) related models.
      $errFields = $this->EnrolleeCoPerson->invalidFields();
      
      if(!empty($errFields)) {
        $fail = true;
      }
      
      // Now validate related models
      
      $v = $this->validateRelated("EnrolleeCoPerson", $requestData, $coData, $efAttrs);
      
      if($v) {
        $coData = $v;
      } else {
        $fail = true;
      }
      
      // Loop through all EmailAddresses, Identifiers, and Names to see if there are any
      // we should copy to the CO Person.
      
      foreach(array('EmailAddress', 'Identifier', 'Name') as $m) {
        if(!empty($orgData[$m])) {
          foreach(array_keys($orgData[$m]) as $a) {
            // $a will be the co_enrollment_attribute:id, so we can tell different
            // addresses apart
            if(isset($copyAttrs[$a])) {
              $coData[$m][$a] = $orgData[$m][$a];
            }
          }
        }
      }
      
      // PrimaryName shows up as a singleton, and so needs to be handled separately.
      
      if(!empty($orgData['PrimaryName']['co_enrollment_attribute_id'])
         && isset($copyAttrs[ $orgData['PrimaryName']['co_enrollment_attribute_id'] ])) {
        // Copy PrimaryName to the CO Person
        
        $coData['PrimaryName'] = $orgData['PrimaryName'];
      }
      
      // Save the CO Person Data
      
      if(!$fail) {
        if($this->EnrolleeCoPerson->saveAssociated($coData, array("validate" => false, "atomic" => true))) {
          $coPersonID = $this->EnrolleeCoPerson->id;
          
          // Create a history record
          try {
            $this->EnrolleeCoPerson->HistoryRecord->record($coPersonID,
                                                           null,
                                                           $orgIdentityID,
                                                           $petitionerId,
                                                           ActionEnum::CoPersonAddedPetition);
          }
          catch(Exception $e) {
            $dbc->rollback();
            throw new RuntimeException($e->getMessage());
          }
        } else {
          $dbc->rollback();
          throw new RuntimeException(_txt('er.db.save'));
        }
      }
    }
    
    // Validate CO Person Role, but only if CO Person Role data was provided
    
    $coRoleData = array();
    
    if(isset($requestData['EnrolleeCoPersonRole'])) {
      $coRoleData['EnrolleeCoPersonRole'] = $this->EnrolleeCoPersonRole->filterModelAttributes($requestData['EnrolleeCoPersonRole']);
      $coRoleData['EnrolleeCoPersonRole']['status'] = $initialStatus;
      $coRoleData['EnrolleeCoPersonRole']['co_person_id'] = $coPersonID;
      
      // Dynamically adjust validation rules according to the enrollment flow
      
      // XXX If we didn't generate a CO Person ID above for some reason, that validation will fail
      // here. With dynamic validation rules in Cake 2.2 we could drop that rule. (CO-353)
      $this->adjustValidationRules('EnrolleeCoPersonRole', $efAttrs);
      
      // Manually validate CoPersonRole
      $this->EnrolleeCoPersonRole->set($coRoleData);
      
      // Make sure to use invalidFields(), which won't try to validate (possibly
      // missing) related models.
      $errFields = $this->EnrolleeCoPersonRole->invalidFields();
      
      if(!empty($errFields)) {
        $fail = true;
      }
      
      // Now validate related models. This will handle Extended Attributes as well.
      
      $v = $this->validateRelated("EnrolleeCoPersonRole", $requestData, $coRoleData, $efAttrs);
      
      if($v) {
        $coRoleData = $v;
      } else {
        $fail = true;
      }
      
      // We're done validating user data at this point, so we can fail if there were
      // any validation errors.
      
      if($fail) {
        $dbc->rollback();
        throw new InvalidArgumentException(_txt('er.fields'));
      }
      
      // Loop through all Addresses and Telephone Numbers to see if there are any
      // we should copy to the CO Person Role.
      
      foreach(array('Address', 'TelephoneNumber') as $m) {
        if(!empty($orgData[$m])) {
          foreach(array_keys($orgData[$m]) as $a) {
            // $a will be the co_enrollment_attribute:id, so we can tell different
            // addresses apart
            if(isset($copyAttrs[$a])) {
              $coRoleData[$m][$a] = $orgData[$m][$a];
            }
          }
        }
      }
      
      // Save the CO Person Role data
      
      if($this->EnrolleeCoPersonRole->saveAssociated($coRoleData, array("validate" => false, "atomic" => true))) {
        $coPersonRoleID = $this->EnrolleeCoPersonRole->id;
        
        // Create a history record
        try {
          $this->EnrolleeCoPerson->HistoryRecord->record($coPersonID,
                                                         $coPersonRoleID,
                                                         $orgIdentityID,
                                                         $petitionerId,
                                                         ActionEnum::CoPersonRoleAddedPetition);
        }
        catch(Exception $e) {
          $dbc->rollback();
          throw new RuntimeException($e->getMessage());
        }
      } else {
        $dbc->rollback();
        throw new RuntimeException(_txt('er.db.save'));
      }
    }
    
    // Create a CO Org Identity Link
    
    $coOrgLink = array();
    $coOrgLink['CoOrgIdentityLink']['org_identity_id'] = $orgIdentityID;
    $coOrgLink['CoOrgIdentityLink']['co_person_id'] = $coPersonID;
    
    if($this->EnrolleeCoPerson->CoOrgIdentityLink->save($coOrgLink)) {
      // Create a history record
      try {
        $this->EnrolleeCoPerson->HistoryRecord->record($coPersonID,
                                                       $coPersonRoleID,
                                                       $orgIdentityID,
                                                       $petitionerId,
                                                       ActionEnum::CoPersonOrgIdLinked);
      }
      catch(Exception $e) {
        $dbc->rollback();
        throw new RuntimeException($e->getMessage());
      }
    } else {
      $dbc->rollback();
      throw new RuntimeException(_txt('er.db.save'));
    }
    
    $coPetitionID = null;
    
    // Assemble the Petition, status = pending. We have most of the identifiers
    // we need from the above saves.
    
    $coPetitionData = array();
    $coPetitionData['CoPetition']['co_enrollment_flow_id'] = $enrollmentFlowID;
    $coPetitionData['CoPetition']['co_id'] = $coId;
    
    if(isset($coRoleData['EnrolleeCoPersonRole']['cou_id'])) {
      $coPetitionData['CoPetition']['cou_id'] = $coRoleData['EnrolleeCoPersonRole']['cou_id'];
    }
    
    $coPetitionData['CoPetition']['enrollee_org_identity_id'] = $orgIdentityID;
    $coPetitionData['CoPetition']['enrollee_co_person_id'] = $coPersonID;
    
    if($coPersonRoleID) {
      $coPetitionData['CoPetition']['enrollee_co_person_role_id'] = $coPersonRoleID;
    }
    
    // Figure out the petitioner person ID. As of now, it is the authenticated
    // person completing the form. This could be NULL if a CMP admin who is not
    // a member of the CO initiates the petition.
    
    $coPetitionData['CoPetition']['petitioner_co_person_id'] = $petitionerId;
    $coPetitionData['CoPetition']['status'] = $initialStatus;
    
    if($this->save($coPetitionData)) {
      $coPetitionID = $this->id;
    } else {
      $dbc->rollback();
      throw new RuntimeException(_txt('er.db.save'));
    }
    
    // Store a copy of the attributes in co_petition_attributes. In order to do this,
    // we need to walk through the various submitted attributes and "flatten" them
    // into a format suitable for this table. Start with org data.
    
    $petitionAttrs = array();
    
    // Pull a mapping of attributes to attribute IDs
    
    $mArgs = array();
    $mArgs['conditions']['CoEnrollmentAttribute.co_enrollment_flow_id'] = $enrollmentFlowID;
    $mArgs['fields'] = array('CoEnrollmentAttribute.attribute', 'CoEnrollmentAttribute.id');
    $attrIDs = $this->CoEnrollmentFlow->CoEnrollmentAttribute->find("list", $mArgs);
    
    if(isset($orgData['EnrolleeOrgIdentity'])) {
      foreach(array_keys($orgData['EnrolleeOrgIdentity']) as $a) {
        // We need to find the attribute ID for this attribute. If not found, we'll
        // skip it (since it's probably something like co_id that we don't need to
        // store here).
        
        if(isset($attrIDs['o:'.$a])
           && isset($orgData['EnrolleeOrgIdentity'][$a])
           && $orgData['EnrolleeOrgIdentity'][$a] != '') {
          $petitionAttrs['CoPetitionAttribute'][] = array(
            'co_petition_id' => $coPetitionID,
            'co_enrollment_attribute_id' => $attrIDs['o:'.$a],
            'attribute' => $a,
            'value' => $orgData['EnrolleeOrgIdentity'][$a]
          );
        }
      }
      
      foreach(array_keys($orgData) as $m) {
        // Loop through the related models, which may or may not be hasMany.
        
        if($m == 'EnrolleeOrgIdentity')
          continue;
        
        if(isset($orgData[$m]['co_enrollment_attribute_id'])) {
          // hasOne
          
          foreach(array_keys($orgData[$m]) as $a) {
            if($a != 'co_enrollment_attribute_id'
               && isset($orgData[$m][$a])
               && $orgData[$m][$a] != '') {
              $petitionAttrs['CoPetitionAttribute'][] = array(
                'co_petition_id' => $coPetitionID,
                'co_enrollment_attribute_id' => $orgData[$m]['co_enrollment_attribute_id'],
                'attribute' => $a,
                'value' => $orgData[$m][$a]
              );                  
            }
          }
        } else {
          // hasMany
          
          foreach(array_keys($orgData[$m]) as $i) {
            foreach(array_keys($orgData[$m][$i]) as $a) {
              if($a != 'co_enrollment_attribute_id'
                 && isset($orgData[$m][$i][$a])
                 && $orgData[$m][$i][$a] != '') {
                $petitionAttrs['CoPetitionAttribute'][] = array(
                  'co_petition_id' => $coPetitionID,
                  'co_enrollment_attribute_id' => $orgData[$m][$i]['co_enrollment_attribute_id'],
                  'attribute' => $a,
                  'value' => $orgData[$m][$i][$a]
                );                  
              }
            }
          }
        }
      }
    }
    
    // CO Person doesn't currently have any direct attributes that we track.
    // Move on to related model attributes.
    
    foreach(array_keys($coData) as $m) {
      // Loop through the related models, which may or may not be hasMany.
      
      if($m == 'EnrolleeCoPerson')
        continue;
      
      if(isset($coData[$m]['co_enrollment_attribute_id'])) {
        // hasOne
        
        foreach(array_keys($coData[$m]) as $a) {
          if($a != 'co_enrollment_attribute_id'
             && isset($coData[$m][$a])
             && $coData[$m][$a] != '') {
            $petitionAttrs['CoPetitionAttribute'][] = array(
              'co_petition_id' => $coPetitionID,
              'co_enrollment_attribute_id' => $coData[$m]['co_enrollment_attribute_id'],
              'attribute' => $a,
              'value' => $coData[$m][$a]
            );                  
          }
        }
      } else {
        // hasMany
        
        foreach(array_keys($coData[$m]) as $i) {
          foreach(array_keys($coData[$m][$i]) as $a) {
            if($a != 'co_enrollment_attribute_id'
               && isset($coData[$m][$i][$a])
               && $coData[$m][$i][$a] != '') {
              $petitionAttrs['CoPetitionAttribute'][] = array(
                'co_petition_id' => $coPetitionID,
                'co_enrollment_attribute_id' => $coData[$m][$i]['co_enrollment_attribute_id'],
                'attribute' => $a,
                'value' => $coData[$m][$i][$a]
              );                  
            }
          }
        }
      }
    }
    
    // Next, CO Person Role data
    
    if(isset($coRoleData['EnrolleeCoPersonRole'])) {
      foreach(array_keys($coRoleData['EnrolleeCoPersonRole']) as $a) {
        // We need to find the attribute ID for this attribute. If not found, we'll
        // skip it (since it's probably something like co_id that we don't need to
        // store here).
        
        if(isset($attrIDs['r:'.$a])
           && isset($coRoleData['EnrolleeCoPersonRole'][$a])
           && $coRoleData['EnrolleeCoPersonRole'][$a] != '') {
          $petitionAttrs['CoPetitionAttribute'][] = array(
            'co_petition_id' => $coPetitionID,
            'co_enrollment_attribute_id' => $attrIDs['r:'.$a],
            'attribute' => $a,
            'value' => $coRoleData['EnrolleeCoPersonRole'][$a]
          );
        }
      }
      
      foreach(array_keys($coRoleData) as $m) {
        // Loop through the related models, which may or may not be hasMany.
        
        if($m == 'EnrolleeCoPersonRole')
          continue;
        
        if(isset($coRoleData[$m]['co_enrollment_attribute_id'])) {
          // hasOne
          
          foreach(array_keys($coRoleData[$m]) as $a) {
            if($a != 'co_enrollment_attribute_id'
               && isset($coRoleData[$m][$a])
               && $coRoleData[$m][$a] != '') {
              $petitionAttrs['CoPetitionAttribute'][] = array(
                'co_petition_id' => $coPetitionID,
                'co_enrollment_attribute_id' => $coRoleData[$m]['co_enrollment_attribute_id'],
                'attribute' => $a,
                'value' => $coRoleData[$m][$a]
              );                  
            }
          }
        } elseif(preg_match('/Co[0-9]+PersonExtendedAttribute/', $m)) {
          // Extended Attribute
          
          foreach(array_keys($coRoleData[$m]) as $a) {
            // We need to find the attribute ID for this attribute.
            
            if(isset($attrIDs['x:'.$a])
               && isset($coRoleData[$m][$a])
               && $coRoleData[$m][$a] != '') {
              $petitionAttrs['CoPetitionAttribute'][] = array(
                'co_petition_id' => $coPetitionID,
                'co_enrollment_attribute_id' => $attrIDs['x:'.$a],
                'attribute' => $a,
                'value' => $coRoleData[$m][$a]
              );                  
            }
          }
        } else {
          // hasMany
          
          foreach(array_keys($coRoleData[$m]) as $i) {
            foreach(array_keys($coRoleData[$m][$i]) as $a) {
              if($a != 'co_enrollment_attribute_id'
                 && isset($coRoleData[$m][$i][$a])
                 && $coRoleData[$m][$i][$a] != '') {
                $petitionAttrs['CoPetitionAttribute'][] = array(
                  'co_petition_id' => $coPetitionID,
                  'co_enrollment_attribute_id' => $coRoleData[$m][$i]['co_enrollment_attribute_id'],
                  'attribute' => $a,
                  'value' => $coRoleData[$m][$i][$a]
                );                  
              }
            }
          }
        }
      }
    }
    
    // Finally, try to save. Note that saveMany doesn't expect the Model name as an array
    // component, unlike all the other saves.
    
    if(!$this->CoPetitionAttribute->saveMany($petitionAttrs['CoPetitionAttribute'])) {
      $dbc->rollback();
      throw new RuntimeException(_txt('er.db.save'));
    }
    
    // Add a co_petition_history_record
    
    try {
      $this->CoPetitionHistoryRecord->record($coPetitionID,
                                             $petitionerId,
                                             PetitionActionEnum::Created);
    }
    catch(Exception $e) {
      $dbc->rollback();
      throw new RuntimeException(_txt('er.db.save'));
    }
    
    // Record agreements to Terms and Conditions, if any
    
    if(!empty($requestData['CoTermsAndConditions'])) {
      foreach(array_keys($requestData['CoTermsAndConditions']) as $coTAndCId) {
        try {
          // Currently, T&C is only available via a petition when authn is required.
          // The array value should be the authenticated identifier as set by the view.
          
          $this->Co->CoTermsAndConditions->CoTAndCAgreement->record($coTAndCId,
                                                                    $coPersonID,
                                                                    $coPersonID,
                                                                    $requestData['CoTermsAndConditions'][$coTAndCId]);
        }
        catch(Exception $e) {
          $dbc->rollback();
          throw new RuntimeException(_txt('er.db.save'));
        }
      }
    }
    
    // Send email invite if configured
    
    if($verifyEmail) {
      // We need an email address to send to. Since we don't have a mechanism for
      // picking from multiple at the moment, we just pick the first one provided
      // (which in most cases will be sufficient).
      
      $toEmail = "";
      
      if(isset($orgData['EmailAddress'])) {
        // EmailAddresses are indexed by email_address_id, so we need to figure one.
        // We don't use array_shift since we don't want to muck with the array.
        
        $i = array_keys($orgData['EmailAddress']);
        
        if(count($i) > 0) {
          $toEmail = $orgData['EmailAddress'][ $i[0] ]['mail'];
        }
      }
      
      if($toEmail != "") {
        $notifyFrom = $this->CoEnrollmentFlow->field('notify_from',
                                                     array('CoEnrollmentFlow.id' => $enrollmentFlowID));
        
        $subjectTemplate = $this->CoEnrollmentFlow->field('verification_subject',
                                                           array('CoEnrollmentFlow.id' => $enrollmentFlowID));
        
        $bodyTemplate = $this->CoEnrollmentFlow->field('verification_body',
                                                        array('CoEnrollmentFlow.id' => $enrollmentFlowID));
        
        $coName = $this->Co->field('name', array('Co.id' => $coId));
        
        $coInviteId = $this->CoInvite->send($coPersonID,
                                            $orgIdentityID,
                                            $petitionerId,
                                            $toEmail,
                                            $notifyFrom,
                                            $coName,
                                            $subjectTemplate,
                                            $bodyTemplate);
        
        // Add the invite ID to the petition record
        
        $this->saveField('co_invite_id', $coInviteId);
        
        // And add a petition history record
        
        try {
          $this->CoPetitionHistoryRecord->record($coPetitionID,
                                                 $petitionerId,
                                                 PetitionActionEnum::InviteSent,
                                                 _txt('rs.inv.sent', array($toEmail)));
        }
        catch(Exception $e) {
          $dbc->rollback();
          throw new RuntimeException(_txt('er.db.save'));
        }
      } else {
        $dbc->rollback();
        throw new RuntimeException(_txt('er.orgp.nomail', array(generateCn($orgData['Name']), $orgIdentityID)));
      }
    }
    
    $dbc->commit();
    
    return $this->id;
  }
  
  /**
   * Resend an invite for a Petition.
   * - postcondition: Invite sent
   *
   * @since  COmanage Registry v0.7
   * @param  Integer CO Petition ID
   * @throws InvalidArgumentException
   * @return String Address the invitation was resent to
   */
  
  function resend($coPetitionId) {
    // We don't set up a transaction because once the invite goes out we've basically
    // committed (and it doesn't make sense to execute a rollback), and we're mostly
    // doing reads before that.
    
    // Petition status must be Pending Confirmation
    
    $this->id = $coPetitionId;
    
    if($this->field('status') != StatusEnum::PendingConfirmation) {
      throw new InvalidArgumentException(_txt('er.pt.resend.status'));
    }
    
    // There must be an email address associated with the org identity associated with this petition
    
    $args = array();
    $args['conditions']['EmailAddress.org_identity_id'] = $this->field('enrollee_org_identity_id');
    $args['contain'] = false;
    
    $email = $this->EnrolleeOrgIdentity->EmailAddress->find('first', $args);
    
    if(empty($email)) {
      throw new InvalidArgumentException(_txt('er.notfound',
                                              array('ct.email_addresses.1',
                                                    $args['conditions']['EmailAddress.org_identity_id'])));
    }
    
    // Unlink any existing invite
    
    if(!$this->saveField('co_invite_id', null)) {
      throw new RuntimeException(_txt('er.db.save'));
    }
    
    // Find enrollment flow
    
    $args = array();
    $args['conditions']['CoEnrollmentFlow.id'] = $this->field('co_enrollment_flow_id');
    $args['contain'] = false;
    
    $enrollmentFlow = $this->CoEnrollmentFlow->find('first', $args);
    
    if(empty($enrollmentFlow)) {
      throw new InvalidArgumentException(_txt('er.notfound',
                                              array('ct.co_enrollment_flows.1',
                                                    $args['conditions']['CoEnrollmentFlow.id'])));
    }
    
    // Resend invite
    
    $coInviteId = $this->CoInvite->send($this->field('enrollee_co_person_id'),
                                        $this->field('enrollee_org_identity_id'),
                                        $this->field('petitioner_co_person_id'),
                                        $email['EmailAddress']['mail'],
                                        $enrollmentFlow['CoEnrollmentFlow']['notify_from'],
                                        $this->Co->field('name',
                                                         array('Co.id' => $enrollmentFlow['CoEnrollmentFlow']['co_id'])));
    
    // Update the CO Petition with the new invite ID
    
    if(!$this->saveField('co_invite_id', $coInviteId)) {
      throw new RuntimeException(_txt('er.db.save'));
    }
    
    // Add petition history record
    
    try {
      $this->CoPetitionHistoryRecord->record($coPetitionId,
                                             $this->field('petitioner_co_person_id'),
                                             PetitionActionEnum::InviteSent,
                                             _txt('rs.inv.sent', array($email['EmailAddress']['mail'])));
    }
    catch(Exception $e) {
      throw new RuntimeException(_txt('er.db.save'));
    }
    
    return $email['EmailAddress']['mail'];
  }

  /**
   * Update the status of a CO Petition.
   * - precondition: The Petition must be in a state suitable for the desired new status.
   * - postcondition: The new status may be altered according to the enrollment configuration.
   *
   * @since  COmanage Registry v0.5
   * @param  Integer CO Petition ID
   * @param  StatusEnum Target status
   * @param  Integer CO Person ID of person causing update
   * @throws InvalidArgumentException
   * @throws RuntimeException
   */
  
  function updateStatus($id, $newStatus, $actorCoPersonID) {
    // Try to find the status of the requested petition
    
    $this->id = $id;
    $curStatus = $this->field('status');
    
    if(!$curStatus) {
      throw new InvalidArgumentException(_txt('er.notfound', array(_txt('ct.co_petitions.1'), $id)));
    }
    
    // Do we have a valid new status? If so, do we need to update CO Person status?
    
    $valid = false;
    $newPetitionStatus = $newStatus;
    $newCoPersonStatus = null;
    
    // Find the enrollment flow associated with this petition to determine some configuration parameters
    
    $args = array();
    $args['conditions']['CoEnrollmentFlow.id'] = $this->field('co_enrollment_flow_id');
    $args['contain'] = false;
    
    $enrollmentFlow = $this->CoEnrollmentFlow->find('first', $args);
    
    if(empty($enrollmentFlow)) {
      throw new InvalidArgumentException(_txt('er.notfound',
                                              array('ct.co_enrollment_flows.1', $args['conditions']['CoEnrollmentFlow.id'])));
    }
    
    if($curStatus == StatusEnum::PendingConfirmation) {
      // A Petition can go from Pending Confirmation to Pending Approval, Approved, or Denied.
      // It can also go to Confirmed, though we'll override that.
      
      if($newStatus == StatusEnum::Approved
         || $newStatus == StatusEnum::Confirmed
         || $newStatus == StatusEnum::Denied
         || $newStatus == StatusEnum::PendingApproval) {
        $valid = true;
      }
      
      // If newStatus is Confirmed, set to PendingApproval or Approved as necessary,
      // and create an additional history record.
      
      if($newStatus == StatusEnum::Confirmed) {
        if($enrollmentFlow['CoEnrollmentFlow']['approval_required']) {
          $newPetitionStatus = StatusEnum::PendingApproval;
        } else {
          $newPetitionStatus = StatusEnum::Approved;
        }
        
        try {
          $this->CoPetitionHistoryRecord->record($id,
                                                 $actorCoPersonID,
                                                 PetitionActionEnum::InviteConfirmed);
        }
        catch (Exception $e) {
          throw new RuntimeException($e->getMessage());
        }
      }
    }
    
    if($curStatus == StatusEnum::PendingApproval) {
      // A Petition can go from PendingApproval to Approved or Denied
      
      if($newStatus == StatusEnum::Approved
         || $newStatus == StatusEnum::Denied) {
        $valid = true;
      }
    }
    
    // If a CO Person Role is defined update the CO Person (& Role) status
    
    $coPersonRoleID = $this->field('enrollee_co_person_role_id');    
    
    if($coPersonRoleID) {
      $newCoPersonStatus = $newPetitionStatus;
      
      // XXX This is temporary for CO-321 since there isn't currently a way for an approved person
      // to become active. This should be dropped when a more workflow-oriented mechanism is implemented.
      if($newPetitionStatus == StatusEnum::Approved) {
        $newCoPersonStatus = StatusEnum::Active;
      }
    }
    
    if($valid) {
      // Process the new status
      $fail = false;
      
      // Start a transaction
      $dbc = $this->getDataSource();
      $dbc->begin();
      
      // Update the Petition status
      
      if(!$this->saveField('status', $newPetitionStatus)) {
        throw new RuntimeException(_txt('er.db.save'));
      }
      
      // If this is an approval or a denial, update the approver field as well
      
      if($newPetitionStatus == StatusEnum::Approved
         || $newPetitionStatus == StatusEnum::Denied) {
        if(!$this->saveField('approver_co_person_id', $actorCoPersonID)) {
          throw new RuntimeException(_txt('er.db.save'));
        }
      }
      
      // Write a Petition History Record
      
      if(!$fail) {
        $petitionAction = null;
        
        switch($newPetitionStatus) {
          case StatusEnum::Approved:
            $petitionAction = PetitionActionEnum::Approved;
            break;
          case StatusEnum::Confirmed:
            // We already recorded this history above, so don't do it again here
            //$petitionAction = PetitionActionEnum::InviteConfirmed;
            break;
          case StatusEnum::Denied:
            $petitionAction = PetitionActionEnum::Denied;
            break;
        }
        
        if($petitionAction) {
          try {
            $this->CoPetitionHistoryRecord->record($id,
                                                   $actorCoPersonID,
                                                   $petitionAction);
          }
          catch (Exception $e) {
            $fail = true;
          }
        }
      }
      
      // Update CO Person Role state
      
      if(!$fail && isset($newCoPersonStatus)) {
        if($coPersonRoleID) {
          $this->EnrolleeCoPersonRole->id = $coPersonRoleID;
          $curCoPersonRoleStatus = $this->EnrolleeCoPersonRole->field('status');
          $this->EnrolleeCoPersonRole->saveField('status', $newCoPersonStatus);
          
          // Create a history record
          try {
            $this->EnrolleeCoPersonRole->HistoryRecord->record($this->field('enrollee_co_person_id'),
                                                               $coPersonRoleID,
                                                               null,
                                                               $actorCoPersonID,
                                                               ActionEnum::CoPersonRoleEditedPetition,
                                                               _txt('en.action', null, ActionEnum::CoPersonRoleEditedPetition) . ": "
                                                               . _txt('en.status', null, $curCoPersonRoleStatus) . " > "
                                                               . _txt('en.status', null, $newCoPersonStatus));
          }
          catch(Exception $e) {
            $fail = true;
          }
        } else {
          $fail = true;
        }
      }
      
      // Maybe update CO Person state, but only if it's currently Pending Approval or Pending Confirmation
      
      if(!$fail && isset($newCoPersonStatus)) {
        $coPersonID = $this->field('enrollee_co_person_id');
        
        if($coPersonID) {
          $this->EnrolleeCoPerson->id = $coPersonID;
          
          $curCoPersonStatus = $this->EnrolleeCoPerson->field('status');
          
          if(isset($curCoPersonStatus)
             && ($curCoPersonStatus == StatusEnum::PendingApproval
                 || $curCoPersonStatus == StatusEnum::PendingConfirmation)) {
            $this->EnrolleeCoPerson->saveField('status', $newCoPersonStatus);
            
            // Create a history record
            try {
              $newdata = array();
              $olddata = array();
              $newdata['CoPerson']['status'] = $newCoPersonStatus;
              $olddata['CoPerson']['status'] = $curCoPersonStatus;
              
              $this->EnrolleeCoPerson->HistoryRecord->record($coPersonID,
                                                             null,
                                                             null,
                                                             $actorCoPersonID,
                                                             ActionEnum::CoPersonEditedPetition,
                                                             _txt('en.action', null, ActionEnum::CoPersonEditedPetition) . ": "
                                                             . _txt('en.status', null, $curCoPersonStatus) . " > "
                                                             . _txt('en.status', null, $newCoPersonStatus));
            }
            catch(Exception $e) {
              $fail = true;
            }
          }
          // else not a fail
        } else {
          $fail = true;
        }
      }
      
      // Maybe assign identifiers, but only for new approvals
      
      if(!$fail && $newPetitionStatus == StatusEnum::Approved) {
        $coID = $this->field('co_id');
        $coPersonID = $this->field('enrollee_co_person_id');
        
        if($coID && $coPersonID) {
          $res = $this->EnrolleeCoPerson->Identifier->assign($coID, $coPersonID);
          
          if(!empty($res)) {
            // See if any identifiers were assigned, and if so create a history record
            $assigned = array();
            
            foreach(array_keys($res) as $idType) {
              if($res[$idType] == 1) {
                $assigned[] = $idType;
              } elseif($res[$idType] != 2) {
                // It'd probably be helpful if we caught this error somewhere...
                $fail = true;
              }
            }
            
            if(!empty($assigned)) {
              try {
                $this->CoPetitionHistoryRecord->record($id,
                                                       $actorCoPersonID,
                                                       PetitionActionEnum::IdentifiersAssigned,
                                                       _txt('rs.ia.ok') . " (" . implode(',', $assigned) . ")");
              }
              catch (Exception $e) {
                $fail = true;
              }
            }
          }
        }
      }
      
      // Send an approval notification, if configured
      
      if(!$fail && $newPetitionStatus == StatusEnum::Approved) {
        $enrollmentFlowID = $this->field('co_enrollment_flow_id');
        
        $notify = $this->CoEnrollmentFlow->field('notify_on_approval',
                                                 array('CoEnrollmentFlow.id' => $enrollmentFlowID));
        
        if($notify) {
          // We'll embed some email logic here (similar to that in CoInvite), since we don't
          // have a notification infrastructure yet. This should get refactored when CO-207
          // is addressed. (Be sure to remove the reference to App::uses('CakeEmail'), above.)
          
          // Which address should we send to? How about the one we sent the invitation to...
          // but we can't guarantee access to that since the invitation will have been
          // discarded. So we use the same logic as resend(), above.
          
          $args = array();
          $args['conditions']['EmailAddress.org_identity_id'] = $this->field('enrollee_org_identity_id');
          $args['contain'] = false;
          
          $email = $this->EnrolleeOrgIdentity->EmailAddress->find('first', $args);
          
          if(isset($email['EmailAddress']['mail']) && $email['EmailAddress']['mail'] != "") {
            $toEmail = $email['EmailAddress']['mail'];
            
            $notifyFrom = $this->CoEnrollmentFlow->field('notify_from',
                                                         array('CoEnrollmentFlow.id' => $enrollmentFlowID));
            
            $subjectTemplate = $this->CoEnrollmentFlow->field('approval_subject',
                                                              array('CoEnrollmentFlow.id' => $enrollmentFlowID));
            
            $bodyTemplate = $this->CoEnrollmentFlow->field('approval_body',
                                                           array('CoEnrollmentFlow.id' => $enrollmentFlowID));
            
            $coName = $this->Co->field('name', array('Co.id' => $this->field('co_id')));
            
            // Try to send the notification
            
            $email = new CakeEmail('default');
            
            $viewVariables = array();
            $viewVariables['co_name'] = $coName;
            $viewVariables['invite_id'] = "";  // Only set because CoInvite::processTemplate requires it
            
            try {
              // XXX We use CoInvite's processTemplate, which isn't specific to CoInvite.
              // However, that should be refactored as part of the Notification work
              // so template processing happens in a more generic location.
              // Note at that point processTemplate (if it still exists) should be made
              // protected again.
              $msgSubject = $this->CoInvite->processTemplate($subjectTemplate, $viewVariables);
              $msgBody = $this->CoInvite->processTemplate($bodyTemplate, $viewVariables);
              
              $email->emailFormat('text')
                    ->to($toEmail)
                    ->subject($msgSubject)
                    ->message($msgBody);
              
              // If this enrollment has a default email address set, use it, otherwise leave in the default for the site.
              if(!empty($notifyFrom)) {
                $email->from($notifyFrom);
              }
              
              // Send the email
              $email->send();
              
              // And cut a history record
              
              $this->CoPetitionHistoryRecord->record($id,
                                                     $actorCoPersonID,
                                                     PetitionActionEnum::NotificationSent,
                                                     _txt('rs.nt.sent', array($toEmail)));
            } catch(Exception $e) {
              // We don't want to fail, but we will at least record that something went wrong
              
              $this->CoPetitionHistoryRecord->record($id,
                                                     $actorCoPersonID,
                                                     PetitionActionEnum::NotificationSent,
                                                     _txt('er.nt.send', array($toEmail, $e->getMessage())));
            }
          } else {
            // We don't want to fail, but we will at least record that something went wrong
            
            $this->CoPetitionHistoryRecord->record($id,
                                                   $actorCoPersonID,
                                                   PetitionActionEnum::NotificationSent,
                                                   _txt('er.nt.email'));
          }
        }
      }
      
      if(!$fail) {
        // Commit
        
        $dbc->commit();
      } else {
        // Rollback
        
        $dbc->rollback();
        throw new RuntimeException(_txt('er.db.save'));
      }
    } else {
      throw new InvalidArgumentException(_txt('er.pt.status', array($curStatus, $newStatus)));
    }
  }
  
  /**
   * Validate an identifier obtained via authentication, possibly attaching it to the
   * Org Identity.
   * - postcondition: Identifier attached to Org Identity
   *
   * @since  COmanage Registry v0.7
   * @param  Integer CO Petition ID
   * @param  String Login Identifier
   * @param  Integer Actor CO Person ID
   * @throws InvalidArgumentException
   * @throws RuntimeException
   */
  
  public function validateIdentifier($id, $loginIdentifier, $actorCoPersonId) {
    // Find the enrollment flow associated with this petition to determine some configuration parameters
    
    $this->id = $id;
    
    $args = array();
    $args['conditions']['CoEnrollmentFlow.id'] = $this->field('co_enrollment_flow_id');
    $args['contain'] = false;
    
    $enrollmentFlow = $this->CoEnrollmentFlow->find('first', $args);
    
    if(empty($enrollmentFlow)) {
      throw new InvalidArgumentException(_txt('er.notfound',
                                              array('ct.co_enrollment_flows.1', $args['conditions']['CoEnrollmentFlow.id'])));
    }
    
    if(!$loginIdentifier) {
      // If authn is required but loginidentifier is null, throw an exception
      // (otherwise don't do anything)
      
      if($enrollmentFlow['CoEnrollmentFlow']['require_authn']) {
        throw new RuntimeException(_txt('er.auth'));
      }
    } else {
      // If the identifier is already linked to the org identity, do nothing
      
      $orgId = $this->field('enrollee_org_identity_id');
      
      if($orgId) {
        // For now, we assume the identifier type is ePPN. XXX This probably isn't right,
        // and should be customizable.
        
        $args = array();
        $args['conditions']['Identifier.identifier'] = $loginIdentifier;
        $args['conditions']['Identifier.org_identity_id'] = $orgId;
        $args['conditions']['Identifier.type'] = IdentifierEnum::ePPN;
        
        $identifier = $this->EnrolleeOrgIdentity->Identifier->find('first', $args);
        
        if(!empty($identifier)) {
          // Make sure login flag is set
          
          if(!$identifier['Identifier']['login']) {
            $this->EnrolleeOrgIdentity->Identifier->id = $identifier['Identifier']['id'];
            
            if(!$this->EnrolleeOrgIdentity->Identifier->saveField('login', true)) {
              throw new RuntimeException(_txt('er.db.save'));
            }
            
            // Create a history record
            
            try {
              $this->EnrolleeCoPerson->HistoryRecord->record(null,
                                                             null,
                                                             $orgId,
                                                             $actorCoPersonId,
                                                             ActionEnum::OrgIdEditedPetition,
                                                             _txt('rs.pt.id.login', array($loginIdentifier)));
            }
            catch(Exception $e) {
              throw new RuntimeException($e->getMessage());
            }
          }
        } else {
          // Add the identifier and update petition and org identity history
          
          $identifier = array();
          $identifier['Identifier']['identifier'] = $loginIdentifier;
          $identifier['Identifier']['org_identity_id'] = $orgId;
          $identifier['Identifier']['type'] = IdentifierEnum::ePPN;
          $identifier['Identifier']['login'] = true;
          $identifier['Identifier']['status'] = StatusEnum::Active;
          
          if(!$this->EnrolleeOrgIdentity->Identifier->save($identifier)) {
            throw new RuntimeException(_txt('er.db.save'));
          }
          
          // Create a history record
          
          try {
            $this->EnrolleeCoPerson->HistoryRecord->record(null,
                                                           null,
                                                           $orgId,
                                                           $actorCoPersonId,
                                                           ActionEnum::OrgIdEditedPetition,
                                                           _txt('rs.pt.id.attached', array($loginIdentifier)));
          }
          catch(Exception $e) {
            throw new RuntimeException($e->getMessage());
          }
        }
      } else {
        throw new InvalidArgumentException(_txt('er.notprov.id', array('ct.org_identities.1')));
      }
    }
  }
  
  /**
   * Validate related model data, and assemble it for saving.
   *
   * @since  COmanage Registry v0.7
   * @param  String Primary (parent) model
   * @param  Array Request data, as submitted to createPetition()
   * @param  Array Data assembled so far for saving (Validated data will be added to this array)
   * @param  Array Enrollment Flow attributes, as returned by CoEnrollmentAttribute::enrollmentFlowAttributes()
   * @return Array Array of updated validated data, or null on validation error
   */
  
  private function validateRelated($primaryModel, $requestData, $validatedData, $efAttrs) {
    $ret = $validatedData;
    $err = false;
    
    // If there isn't anything set in $requestData, just return the validated data
    if(empty($requestData[$primaryModel])) {
      return $ret;
    }
    
    // Because the petition form includes skeletal information for related models
    // (co_enrollment_attribute_id, type, etc), we don't need to worry about required
    // models not being submitted if the petitioner doesn't complete the field.
    
    $relatedModels = $this->$primaryModel->filterRelatedModels($requestData[$primaryModel]);
    
    // We don't need to tweak the validation rules, but we do need to check if optional
    // models are empty.
    
    // Extended Type validation should just magically work.
    
    if(isset($relatedModels['hasOne'])) {
      foreach(array_keys($relatedModels['hasOne']) as $model) {
        // Make sure validation only sees this model's data
        $data = array();
        $data[$model] = $relatedModels['hasOne'][$model];
        
        $this->$primaryModel->$model->set($data);
        
        // Make sure to use invalidFields(), which won't try to validate (possibly
        // missing) related models.
        $errFields = $this->$primaryModel->$model->invalidFields();
        
        if(!empty($errFields)) {
          // These errors are going to get attached to $this->model by default, which means when
          // the petition re-renders, FormHelper won't display them. They need to be attached to
          // $this->$primaryModel, keyed as though they were validated along with $primaryModel.
          // We'll fix that keying here.
          
          $this->$primaryModel->validationErrors[$model] = $errFields;
          $err = true;
        } else {
          // Add this entry to the validated data being assembled
          
          $ret[$model] = $relatedModels['hasOne'][$model];
        }
      }
    }
    
    if(isset($relatedModels['hasMany'])) {
      foreach(array_keys($relatedModels['hasMany']) as $model) {
        foreach(array_keys($relatedModels['hasMany'][$model]) as $instance) {
          // Skip related models that are optional and empty
          if(!$this->attributeOptionalAndEmpty($instance, $relatedModels['hasMany'][$model][$instance], $efAttrs)) {
            // Make sure validation only sees this model's data
            $data = array();
            $data[$model] = $relatedModels['hasMany'][$model][$instance];
            
            $this->$primaryModel->$model->set($data);
            
            // Make sure to use invalidFields(), which won't try to validate (possibly
            // missing) related models.
            $errFields = $this->$primaryModel->$model->invalidFields();
            
            if(!empty($errFields)
               // If the only error is co_person_id, ignore it since saveAssociated
               // will automatically key the record
               && (count(array_keys($errFields)) > 1
                   || !isset($errFields['co_person_id']))) {
              // These errors are going to get attached to $this->model by default, which means when
              // the petition re-renders, FormHelper won't display them. They need to be attached to
              // $this->$primaryModel, keyed as though they were validated along with $primaryModel.
              // We'll fix that keying here.
              
              $this->$primaryModel->validationErrors[$model][$instance] = $errFields;
              $err = true;
            } else {
              // Add this entry to the $data being assembled. As an exception, if we have a name
              // of type official promote it to a HasOne relationship, since it will be considered
              // a primary name.
              
              if($model == 'Name'
                 && $relatedModels['hasMany'][$model][$instance]['type'] == NameEnum::Official) {
                $ret['PrimaryName'] = $relatedModels['hasMany'][$model][$instance];
                $ret['PrimaryName']['primary_name'] = true;
              } else {
                $ret[$model][$instance] = $relatedModels['hasMany'][$model][$instance];
              }
            }
          }
        }
      }
    }
    
    if(preg_match('/.*CoPersonRole$/', $primaryModel)) {
      // Handle Extended Attributes specially, as usual. To find them, we have to walk
      // the configured attributes.
      
      foreach($efAttrs as $efAttr) {
        $m = explode('.', $efAttr['model'], 3);
        
        if(count($m) == 2
           && preg_match('/Co[0-9]+PersonExtendedAttribute/', $m[1])) {
          $model = $m[1];
          
          // First, dynamically bind the extended attribute to the model if we haven't already.
          
          if(!isset($this->$primaryModel->$model)) {
            $bArgs = array();
            $bArgs['hasOne'][ $model ] = array(
              'className' => $model,
              'dependent' => true
            );
            
            $this->$primaryModel->bindModel($bArgs, false);
          }
          
          // Extended attributes generally won't have validate by Cake set since their models are
          // dynamically bound, so grabbing validation rules from $efAttr is a win.
          
          $this->$primaryModel->$m[1]->validate[ $efAttr['field'] ] = $efAttr['validate'];
          $this->$primaryModel->$m[1]->validate[ $efAttr['field'] ]['required'] = $efAttr['required'];
          $this->$primaryModel->$m[1]->validate[ $efAttr['field'] ]['allowEmpty'] = !$efAttr['required'];
          
          // Make sure validation only sees this model's data
          $data = array();
          $data[$model] = $relatedModels['extended'][$model];
          
          $this->$primaryModel->$model->set($data);
          
          // Make sure to use invalidFields(), which won't try to validate (possibly
          // missing) related models.
          $errFields = $this->$primaryModel->$model->invalidFields();
          
          if(!empty($errFields)) {
            // These errors are going to get attached to $this->model by default, which means when
            // the petition re-renders, FormHelper won't display them. They need to be attached to
            // $this->$primaryModel, keyed as though they were validated along with $primaryModel.
            // We'll fix that keying here.
            
            $this->$primaryModel->validationErrors[$model] = $errFields;
            $err = true;
          } else {
            // Add this entry to the $coData being assembled
            
            $ret[$model] = $relatedModels['extended'][$model];
          }
        }
      }
    }
    
    if($err) {
      return null;
    } else {
      return $ret;
    }
  }
}
