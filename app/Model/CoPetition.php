<?php
/**
 * COmanage Registry CO Petition Model
 *
 * Copyright (C) 2011-12 University Corporation for Advanced Internet Development, Inc.
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
 * @copyright     Copyright (C) 2011-12 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.3
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */

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
    'status' => array(
      'rule' => array('inList', array(StatusEnum::Approved,
                                      StatusEnum::Declined,
                                      StatusEnum::Denied,
                                      StatusEnum::Invited,
                                      StatusEnum::PendingApproval)),
      'required' => true,
      'message' => 'A valid status must be selected'
    )
  );
  
  // Enum type hints
  
  public $cm_enum_types = array(
    'status' => 'status_t'
  );
  
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
  
  function createPetition($enrollmentFlowID, $coId, $requestData, $petitionerId) {
    // Don't cleverly rename this to create(), since there is already a create method
    // in the default Model.
    
    // We have to do a bunch of non-standard work here. We're passed a bunch of data
    // for other models, basically enough to create a Person/Role. We need to use
    // it to create appropriate records, then create a Petition record with the
    // appropriate entries.
    
    $orgIdentityID = null;
    $coPersonID = null;
    
    // Track whether or not our processing was successful.
    
    $fail = false;
    
    // Validate the data presented. Do this manually since enrollment flow rules may not match
    // up with model rules. Start by pulling the enrollment attributes configuration.
    
    $efAttrs = $this->CoEnrollmentFlow->CoEnrollmentAttribute->enrollmentFlowAttributes($enrollmentFlowID);
    
    // Obtain a list of enrollment flow attributes' required status for use later.
    
    $fArgs = array();
    $fArgs['conditions']['CoEnrollmentAttribute.co_enrollment_flow_id'] = $enrollmentFlowID;
    $fArgs['fields'] = array('CoEnrollmentAttribute.id', 'CoEnrollmentAttribute.required');
    $reqAttrs = $this->CoEnrollmentFlow->CoEnrollmentAttribute->find("list", $fArgs);
    
    // For each defined attribute, update the loaded model validation definition to match
    // the enrollment flow attribute definition. (The CoEnrollmentAttribute model has already
    // done the logic to figure out what validation should really be.) We do this so that
    // when we try saveAll later, the validation rules Cake checks match what the admin
    // configured.
    
    foreach($efAttrs as $efAttr) {
      // co_enrollment_attribute_id is a special attribute with no validation
      if($efAttr['field'] == 'co_enrollment_attribute_id') continue;
      
      // The model might be something like EnrolleeCoPersonRole or EnrolleeCoPersonRole.Name
      // or EnrolleeCoPersonRole.TelephoneNumber.0. Split and handle accordingly.
      
      $m = explode('.', $efAttr['model'], 3);
      
      if(count($m) == 1) {
        $this->$m[0]->validate[ $efAttr['field'] ] = $efAttr['validate'];
        
        $this->$m[0]->validate[ $efAttr['field'] ]['required'] = $efAttr['required'];
        $this->$m[0]->validate[ $efAttr['field'] ]['allowEmpty'] = !$efAttr['required'];
      } else {
        if(preg_match('/Co[0-9]+PersonExtendedAttribute/', $m[1])) {
          // Extended attributes require a bit more work. First, dynamically bind
          // the extended attribute to the model if we haven't already.
          
          if(!isset($this->EnrolleeCoPersonRole->$m[1])) {
            $bArgs = array();
            $bArgs['hasOne'][ $m[1] ] = array(
              'className' => $m[1],
              'dependent' => true
            );
            
            $this->EnrolleeCoPersonRole->bindModel($bArgs, false);
          }
          
          // Extended attributes generally won't have validate by Cake set since their models are
          // dynamically bound, so grabbing validation rules from $efAttr is a win.
        }
        
        $this->$m[0]->$m[1]->validate[ $efAttr['field'] ] = $efAttr['validate'];
        
        $this->$m[0]->$m[1]->validate[ $efAttr['field'] ]['required'] = $efAttr['required'];
        $this->$m[0]->$m[1]->validate[ $efAttr['field'] ]['allowEmpty'] = !$efAttr['required'];
        
        // Temporary hack for CO-368. When the Petition save writes the Identifier model, the
        // Identifier model has no way of receiving the CO ID. So for now we change the validation
        // rule for validateExtendedType.
        
        if(isset($this->$m[0]->$m[1]->validate[ $efAttr['field'] ]['rule'][0])
           && $this->$m[0]->$m[1]->validate[ $efAttr['field'] ]['rule'][0] == 'validateExtendedType') {
          // Change rule[0] to inList and rule[1] to the list as returned by CoExtendedType::active
          
          $this->$m[0]->$m[1]->validate[ $efAttr['field'] ]['rule'] = array();
          $this->$m[0]->$m[1]->validate[ $efAttr['field'] ]['rule'][] = 'inList';
          
          $coExtendedType = ClassRegistry::init('CoExtendedType');
          $this->$m[0]->$m[1]->validate[ $efAttr['field'] ]['rule'][] = array_keys($coExtendedType->active($coId,
                                                                                                            $m[1],
                                                                                                            'list'));
        }
      }
    }
    
    // Start a transaction
    
    $dbc = $this->getDataSource();
    $dbc->begin();
    
    // We need to manually construct an Org Identity, at least for now until
    // they're populated some other way (eg: SAML/LDAP). We'll need to add a
    // reconciliation hook at some point. Save this prior to saving CO Person
    // so Cake doesn't get confused with attaching Names (to the Org Identity
    // vs the CO Person).
    
    $orgData = array();
    
    $CmpEnrollmentConfiguration = ClassRegistry::init('CmpEnrollmentConfiguration');
    
    if($CmpEnrollmentConfiguration->orgIdentitiesFromCOEF()) {
      // Platform is configured to pull org identities from the form.
      
      $orgData['EnrolleeOrgIdentity'] = $requestData['EnrolleeOrgIdentity'];
      
      // Attach this org identity to this CO (if appropriate)
      
      if(!$CmpEnrollmentConfiguration->orgIdentitiesPooled())
        $orgData['EnrolleeOrgIdentity']['co_id'] = $coId;
      
      // Everything we need is in $this->request->data['EnrolleeOrgIdentity'].
      // Filter the data to pull related models up a level and, if optional and
      // not provided, drop the model entirely to avoid validation errors.
      
      $orgData = $this->EnrolleeOrgIdentity->filterRelatedModel($orgData, $reqAttrs);
      
      if($this->EnrolleeOrgIdentity->saveAll($orgData)) {
        $orgIdentityID = $this->EnrolleeOrgIdentity->id;
      } else {
        // We don't fail immediately on error because we want to run validate on all
        // the data we save in the various saveAll() calls so the appropriate fields
        // are highlighted at once.
        
        $fail = true;
      }
    } else {
      // The Org Identity will need to be populated via some other way,
      // such as via attributes pulled during login.
      
      throw new RuntimeException("Not implemented");
    }
    
    // Next, populate a CO Person and CO Person Role, statuses = pending.
    
    $coData = array();
    $coData['EnrolleeCoPerson'] = $requestData['EnrolleeCoPerson'];
    $coData['EnrolleeCoPerson']['co_id'] = $coId;
    $coData['EnrolleeCoPerson']['status'] = StatusEnum::PendingApproval;
    
    // Filter the data to pull related models up a level and, if optional and
    // not provided, drop the model entirely to avoid validation errors.
    
    $coData = $this->EnrolleeCoPerson->filterRelatedModel($coData, $reqAttrs);
    
    if($this->EnrolleeCoPerson->saveAll($coData)) {
      $coPersonID = $this->EnrolleeCoPerson->id;
    } else {
      // We don't fail immediately on error because we want to run validate on all
      // the data we save in the various saveAll() calls so the appropriate fields
      // are highlighted at once.
      
      $fail = true;
    }
    
    $coRoleData = array();
    $coRoleData['EnrolleeCoPersonRole'] = $requestData['EnrolleeCoPersonRole'];
    $coRoleData['EnrolleeCoPersonRole']['status'] = StatusEnum::PendingApproval;
    $coRoleData['EnrolleeCoPersonRole']['co_person_id'] = $coPersonID;
    
    // Filter the data to pull related models up a level and, if optional and
    // not provided, drop the model entirely to avoid validation errors.
    
    $coRoleData = $this->EnrolleeCoPersonRole->filterRelatedModel($coRoleData, $reqAttrs);
    
    foreach(array_keys($requestData['EnrolleeCoPersonRole']) as $m) {
      if(preg_match('/Co[0-9]+PersonExtendedAttribute/', $m)) {
        // Pull the data up a level. We don't do the same filtering here since
        // extended attributes are flat (ie: no related models).
        
        $coRoleData[$m] = $requestData['EnrolleeCoPersonRole'][$m];
        unset($coRoleData['EnrolleeCoPersonRole'][$m]);
      }
    }
    
    if($this->EnrolleeCoPersonRole->saveAll($coRoleData)) {
      $coPersonRoleID = $this->EnrolleeCoPersonRole->id;
    } else {
      // We need to fold any extended attribute validation errors into the CO Person Role
      // validation errors in order for FormHandler to be able to see them.
      
      foreach(array_keys($requestData['EnrolleeCoPersonRole']) as $m) {
        if(preg_match('/Co[0-9]+PersonExtendedAttribute/', $m)) {
          $f = $this->EnrolleeCoPersonRole->$m->invalidFields();
          
          if(!empty($f)) {
            $this->EnrolleeCoPersonRole->validationErrors[$m] = $f;
          }
        }
      }
      
      // We don't fail immediately on error because we want to run validate on all
      // the data we save in the various saveAll() calls so the appropriate fields
      // are highlighted at once.
      
      $fail = true;
    }
    
    if($fail) {
      // From here, if any save fails it's probably a coding error since there are no
      // form fields that need validation. (We're using data from above, for the most
      // part). As such, if $fail gets set to true at any point, we don't need to
      // continue save()ing data.
      
      $dbc->rollback();
      throw new InvalidArgumentException(_txt('er.fields'));
    }
    
    // Create a CO Org Identity Link
    
    $coOrgLink = array();
    $coOrgLink['CoOrgIdentityLink']['org_identity_id'] = $orgIdentityID;
    $coOrgLink['CoOrgIdentityLink']['co_person_id'] = $coPersonID;
    
    if(!$this->EnrolleeCoPerson->CoOrgIdentityLink->save($coOrgLink)) {
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
    $coPetitionData['CoPetition']['enrollee_co_person_role_id'] = $coPersonRoleID;
    
    // Figure out the petitioner person ID. As of now, it is the authenticated
    // person completing the form. This could be NULL if a CMP admin who is not
    // a member of the CO initiates the petition.
    
    $coPetitionData['CoPetition']['petitioner_co_person_id'] = $petitionerId;
    $coPetitionData['CoPetition']['status'] = StatusEnum::PendingApproval;
    
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
    
    // Finally, CO Person Role data
    
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
    
    // Add a co_petition_history_record.
    
    try {
      $this->CoPetitionHistoryRecord->record($coPetitionID,
                                             $petitionerId,
                                             PetitionActionEnum::Created);
    }
    catch(Exception $e) {
      $dbc->rollback();
      throw new RuntimeException(_txt('er.db.save'));
    }
    
    $dbc->commit();
    
    return($this->id);
  }

  /**
   * Update the status of a CO Petition.
   * - precondition: The Petition must be in a state suitable for the desired new status.
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
    $newCoPersonStatus = null;
    
    if($curStatus == StatusEnum::PendingApproval) {
      // A Petition can go from PendingApproval to Approved or Denied
      
      if($newStatus == StatusEnum::Approved
         || $newStatus == StatusEnum::Denied) {
        $valid = true;
        $newCoPersonStatus = $newStatus;
      }
    }
    
    if($valid) {
      // Process the new status
      $fail = false;
      
      // Start a tronsaction
      $dbc = $this->getDataSource();
      $dbc->begin();
      
      // Update the Petition status
      
      $this->saveField('status', $newStatus);
      
      // If this is an approval, update the approver field as well
      
      if($newStatus == StatusEnum::Approved) {
        $this->saveField('approver_co_person_id', $actorCoPersonID);
      }
      
      // Write a Petition History Record
      
      if(!$fail) {
        $petitionAction = null;
        
        switch($newStatus) {
          case StatusEnum::Approved:
            $petitionAction = PetitionActionEnum::Approved;
            break;
          case StatusEnum::Denied:
            $petitionAction = PetitionActionEnum::Denied;
            break;
        }
        
        try {
          $this->CoPetitionHistoryRecord->record($id,
                                                 $actorCoPersonID,
                                                 $petitionAction);
        }
        catch (Exception $e) {
          $fail = true;
        }
      }
      
      // Update CO Person Role state
      
      if(!$fail && isset($newCoPersonStatus)) {
        $coPersonRoleID = $this->field('enrollee_co_person_role_id');
        
        if($coPersonRoleID) {
          $this->EnrolleeCoPersonRole->id = $coPersonRoleID;
          $this->EnrolleeCoPersonRole->saveField('status', $newCoPersonStatus);
        } else {
          $fail = true;
        }
      }
      
      // Maybe update CO Person state, but only if it's currently Pending Approval
      
      if(!$fail && isset($newCoPersonStatus)) {
        $coPersonID = $this->field('enrollee_co_person_id');
        
        if($coPersonID) {
          $this->EnrolleeCoPerson->id = $coPersonID;
          
          $curCoPersonStatus = $this->EnrolleeCoPerson->field('status');
          
          if(isset($curCoPersonStatus)
             && ($curCoPersonStatus == StatusEnum::PendingApproval)) {
            $this->EnrolleeCoPerson->saveField('status', $newCoPersonStatus);
          }
          // else not a fail
        } else {
          $fail = true;
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
}
