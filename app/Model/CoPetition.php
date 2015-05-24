<?php
/**
 * COmanage Registry CO Petition Model
 *
 * Copyright (C) 2011-15 University Corporation for Advanced Internet Development, Inc.
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
 * @copyright     Copyright (C) 2011-15 University Corporation for Advanced Internet Development, Inc.
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
      'rule' => array('inList', array(PetitionStatusEnum::Active,
                                      PetitionStatusEnum::Approved,
                                      PetitionStatusEnum::Confirmed,
                                      PetitionStatusEnum::Created,
                                      PetitionStatusEnum::Declined,
                                      PetitionStatusEnum::Denied,
                                      PetitionStatusEnum::Duplicate,
                                      PetitionStatusEnum::Finalized,
                                      PetitionStatusEnum::PendingApproval,
                                      PetitionStatusEnum::PendingConfirmation)),
      'required' => true,
      'message' => 'A valid status must be selected'
    )
  );
  
  // Enum type hints
  
  public $cm_enum_types = array(
    'status' => 'StatusEnum'
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
    foreach($efAttrs as $efAttr) {
      // The model might be something like EnrolleeCoPersonRole or EnrolleeCoPersonRole.Name
      // or EnrolleeCoPersonRole.TelephoneNumber.0. However, since we only adjust validation
      // rules for top-level attributes, the first type is the only one we care about.
      
      $m = explode('.', $efAttr['model'], 3);
      
      if(count($m) == 1) {
        if($m[0] == $model) {
          $xfield = $this->$model->validator()->getField($efAttr['field']);
          
          if($xfield && $xfield->getRule('content')) {
            $xreq = (isset($efAttr['required']) && $efAttr['required']);
            
            $xfield->getRule('content')->required = $xreq;
            $xfield->getRule('content')->allowEmpty = !$xreq;
            
            if($xreq) {
              $xfield->getRule('content')->message = _txt('er.field.req');
            }
            
            if($model == 'EnrolleeCoPersonRole' && $efAttr['field'] == 'affiliation') {
              // Affiliation is an extended type, so we need to update the validation
              // rule to pass the COID.  Set the actual validation rule to be match the
              // enrollment configuration.
              
              // Should we do this for all attributes, as is the case in validateRelated()? (CO-907)
              
              if(!empty($efAttr['validate']['content']['rule'])) {
                $xfield->getRule('content')->rule = $efAttr['validate']['content']['rule'];
              }
            }
          }
        }
      }
    }
  }
  
  /**
   * Possibly assign identifiers for a petition.
   *
   * @since  COmanage Registry v0.9.4
   * @param Integer $id CO Petition ID
   * @param Integer $actorCoPersonId CO Person ID for actor
   * @return Boolean True if successful, false otherwise
   */
  
  public function assignIdentifiers($id, $actorCoPersonId) {
    // This function should only be called once the decision has been made that identifiers
    // should be assigned.
    
    $ret = true;
    
    $coPersonID = $this->field('enrollee_co_person_id', array('CoPetition.id' => $id));
    $coID = $this->field('co_id', array('CoPetition.id' => $id));
    
    if($coID && $coPersonID) {
      $res = $this->EnrolleeCoPerson->Identifier->assign($coID, $coPersonID, $actorCoPersonId);
      
      if(!empty($res)) {
        // See if any identifiers were assigned, and if so create a history record
        $assigned = array();
        
        foreach(array_keys($res) as $idType) {
          if($res[$idType] == 1) {
            $assigned[] = $idType;
          } elseif($res[$idType] != 2) {
            // It'd probably be helpful if we caught this error somewhere...
            $ret = false;
          }
        }
        
        if(!empty($assigned)) {
          try {
            $this->CoPetitionHistoryRecord->record($id,
                                                   $actorCoPersonId,
                                                   PetitionActionEnum::IdentifiersAssigned,
                                                   _txt('rs.ia.ok') . " (" . implode(',', $assigned) . ")");
          }
          catch(Exception $e) {
            $ret = false;
          }
        }
      }
    }
    
    return $ret;
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
      
      // Skip metadata fields
      if($efAttr['field'] == 'co_enrollment_attribute_id'
         || $efAttr['field'] == 'type'
         || $efAttr['field'] == 'language'
         || $efAttr['field'] == 'primary_name') {
        
        continue;
      }
      
      if($efAttr['hidden'] && !$efAttr['default']) {
        // Skip hidden fields because they aren't user-editable, unless they are default attributes
        continue;
      }
      
      if($efAttr['field'] == 'login'
         && (strncmp($efAttr['attribute'], 'i:identifier', 12)==0
             || strncmp($efAttr['attribute'], 'p:identifier', 12)==0)) {
        // For identifiers, skip login since it's not the primary element and it's
        // hard to tell if it's empty or not (since it's boolean)
        
        continue;
      }
      
      if(isset($efAttr['mvpa_required'])) {
        if($efAttr['mvpa_required']) {
          // This attribute is part of an MVPA that is required, so stop
          return false;
        } else {
          // Treat this attribute as optional, but check if it's set
          
          if(!empty($data[ $efAttr['field'] ])) {
            return false;
          } else {
            continue;
          }
        }
      }
      
      if(isset($efAttr['required']) && $efAttr['required']) {
        // We found a required flag, so stop
        
        return false;
      }
      
      if(!empty($data[ $efAttr['field'] ])) {
        // Field is set, so stop
        
        return false;
      }
    }
    
    return true;
  }
  
  /**
   * Filter Enrollment Attributes for those that were in effect at the time a
   * set of Petition Attributes were created.
   * 
   * @since  COmanage Registry v0.9.4
   * @param  Array $enrollmentAttributes Enrollment Attributes as obtained from CoEnrollmentAttribute::enrollmentFlowAttributes
   * @param  Array $petitionAttributes Petition Attributes, as a hash of enrollment attribute ID and creation timestamp
   * @return Array Enrollment Attributes in effect, in the same format as $enrollmentAttributes
   */
  
  public function filterHistoricalAttributes($enrollmentAttributes, $petitionAttributes) {
    // The attributes we want to keep, using the master parent ID as the key.
    $keep = array();
    
    // Track the earliest create time from any attribute. We'll use this as an
    // approximation to determine when the attributes were collected.
    $createTime = PHP_INT_MAX;
    
    // Determining which (historical) attribute to return is a bit tricky.
    // For example, an optional attribute may not be recorded in $petitionAttributes,
    // so we can't just use that as an authoritative source. We start by assembling
    // the keys of $petitionAttributes for attributes with definitions.
    
    foreach($enrollmentAttributes as $ea) {
      if(isset($petitionAttributes[ $ea['id'] ])) {
        if(isset($ea['CoEnrollmentAttribute']['co_enrollment_attribute_id'])) {
          // Not the parent attribute
          $keep[ $ea['CoEnrollmentAttribute']['co_enrollment_attribute_id'] ] = $ea['id'];
        } else {
          // Parent attribute
          $keep[ $ea['id'] ] = $ea['id'];
        }
        
        $eaTime = strtotime($petitionAttributes[ $ea['id'] ]);
        
        if($eaTime < $createTime) {
          $createTime = $eaTime;
        }
      }
    }
    
    // Now handle undefined attributes. We'll keep the most recent definition
    // that is no later than $createTime.
    
    $defaultKeep = array();
    $defaultKeepTimes = array();
    
    foreach($enrollmentAttributes as $ea) {
      $parentId = null;
      
      if(isset($ea['CoEnrollmentAttribute']['co_enrollment_attribute_id'])) {
        // Not the parent attribute
        $parentId = $ea['CoEnrollmentAttribute']['co_enrollment_attribute_id'];
      } else {
        // Parent attribute
        $parentId = $ea['id'];
      }
      
      $eaTime = strtotime($ea['CoEnrollmentAttribute']['created']);
      
      if(!isset($keep[$parentId])
         && $eaTime <= $createTime) {
        // There was no value for this attribute, so start tracking,
        // or replace the previously selected attribute to keep.
        
        if(!isset($defaultKeep[$parentId])
           || $eaTime > $defaultKeepTimes[$parentId]) {
          $defaultKeep[$parentId] = $ea['id'];
          $defaultKeepTimes[$parentId] = $eaTime;
        }
      }
    }
    
    // Re-assemble the attributes to keep. Make sure we don't return any deleted.
    
    $keepById = array_flip(array_merge($keep, $defaultKeep));
    
    $ret = array();
    
    foreach($enrollmentAttributes as $ea) {
      if(isset($keepById[ $ea['id'] ])) {
        $ret[] = $ea;
      }
    }
    
    return $ret;
  }
  
  /**
   * Convert attributes from "hierarchical" operational model format to "flat" petition format.
   *
   * @since  COmanage Registry v0.9.4
   * @param  Integer $enrollmentFlowID Enrollment Flow ID
   * @param  Integer $coPetitionID     CO Petition ID
   * @param  Array   $orgData          Array of OrgIdentity attributes (and related models)
   * @param  Array   $coData           Array of CoPerson attributes (and related models)
   * @param  Array   $coRoleData       Array of CoPersonRole attributes (and related models)
   * @param  Array   $requestData      Original request data from form
   * @return Array Array of attributes in petition format
   */
  
  protected function flattenAttributes($enrollmentFlowID, $coPetitionID, $orgData, $coData, $coRoleData, $requestData) {
    // Return array
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
    
    if(!empty($requestData['CoPetitionAttribute'])) {
      // These are "special" attributes that only get recorded in the petition,
      // they're not copied to the person record.
      
      foreach($requestData['CoPetitionAttribute'] as $key => $value) {
        if($key == 'textfield' && isset($attrIDs['e:'.$key])) {
          // Simply copy this value to an attribute value
          
          $petitionAttrs['CoPetitionAttribute'][] = array(
            'co_petition_id' => $coPetitionID,
            'co_enrollment_attribute_id' => $attrIDs['e:'.$key],
            'attribute' => $key,
            'value' => $value
          );
        }
      }
    }
    
    return $petitionAttrs;
  }
  
  /**
   * Convert attributes from "flat" petition format to "hierarchical" operational model format.
   *
   * @since  COmanage Registry v0.9.4
   * @param  Integer $coPetitionID     CO Petition ID
   * @return Array Array of attributes in operational format
   * @throws InvalidArgumentException
   */
  
  protected function inflateAttributes($id) {
    $ret = array();
    
    // Pull the attribute values along with their definitions
    
    $args = array();
    $args['conditions']['CoPetition.id'] = $id;
    $args['contain'][] = 'CoPetitionAttribute';
    $args['contain']['CoPetitionAttribute'][] = 'CoEnrollmentAttribute';
    
    $attrs = $this->find('first', $args);
    
    if(empty($attrs)) {
      throw new InvalidArgumentException(_txt('er.notfound', array(_txt('ct.co_petitions.1'), $id)));
    }
    
    foreach($attrs['CoPetitionAttribute'] as $attr) {
      // Figure out what type of attribute this is
      $a = explode(':', $attr['CoEnrollmentAttribute']['attribute']);
      
      // We skip case 'e' (enrollment-only attributes) since they don't end up
      // in the operational record.
      
      if($a[0] == 'g') {
        // Group member
        $ret['CoPerson']['CoGroupMember'][ $attr['co_enrollment_attribute_id'] ][ $attr['attribute'] ] = $attr['value'];
      } elseif($a[0] == 'i' || $a[0] == 'm' || $a[0] == 'p') {
        // MVPA -- reconnect based on co_enrollment_attribute_id
        
        switch($a[0]) {
          case 'i':
            $pmodel = 'OrgIdentity';
            break;
          case 'm':
            $pmodel = 'CoPersonRole';
            break;
          case 'p':
            $pmodel = 'CoPerson';
            break;
        }
        
        $model = Inflector::classify($a[1]);
        
        $ret[$pmodel][$model][ $attr['co_enrollment_attribute_id'] ][ $attr['attribute'] ] = $attr['value'];
        // Type may already be set, but we're just clobbering it with the same value
        $ret[$pmodel][$model][ $attr['co_enrollment_attribute_id'] ]['type'] = $a[2];
      } elseif($a[0] == 'o') {
        // Org Identity attribute
        $ret['OrgIdentity'][ $a[1] ] = $attr['value'];
      } elseif($a[0] == 'r') {
        // CO Person Role attribute
        $ret['CoPersonRole'][ $a[1] ] = $attr['value'];
      } elseif($a[0] == 'x') {
        // Extended attribute
        $ret['CoPersonRole']['Co'.$attrs['CoPetition']['co_id'].'PersonExtendedAttribute'][ $attr['attribute'] ] = $attr['value'];
      }
    }
    
    return $ret;
  }
  
  /**
   * Create a new CO Petition.
   *
   * @since  COmanage Registry v0.9.4
   * @param  Integer Enrollment Flow ID
   * @param  Integer CO ID to attach the petition to
   * @param  Integer CO Person ID of the petitioner
   * @return Integer ID of newly created Petition
   * @throws RunTimeException
   */
  
  public function initialize($enrollmentFlowID, $coId, $petitionerId=null) {
    $this->CoEnrollmentFlow->id = $enrollmentFlowID;
    $efName = $this->CoEnrollmentFlow->field('name');
    
    $coPetitionData = array();
    $coPetitionData['CoPetition']['co_enrollment_flow_id'] = $enrollmentFlowID;
    $coPetitionData['CoPetition']['co_id'] = $coId;
    $coPetitionData['CoPetition']['status'] = PetitionStatusEnum::Created;
    
    // If we don't have a petitioner, generate a token for use in linking pages
    
    if($petitionerId) {
      $coPetitionData['CoPetition']['petitioner_co_person_id'] = $petitionerId;
    } else {
      $coPetitionData['CoPetition']['petitioner_token'] = Security::generateAuthKey();
    }
    
    $this->create();
    
    if(!$this->save($coPetitionData)) {
      throw new RuntimeException(_txt('er.db.save'));
    }
    
    $coPetitionID = $this->id;
    
    // Create a Petition History Record
    
    try {
      $this->CoPetitionHistoryRecord->record($coPetitionID,
                                             $petitionerId,
                                             PetitionActionEnum::Created,
                                             _txt('rs.pt.create.from',
                                                  array($efName . " (" . $enrollmentFlowID . ")")));
    }
    catch(Exception $e) {
      throw new RuntimeException(_txt('er.db.save'));
    }
    
    return $coPetitionID;
  }
  
  /**
   * Link an existing CO Person to a CO Petition.
   *
   * @since  COmanage Registry v0.9.4
   * @param  Integer CO Petition ID
   * @param  Integer CO Person ID to link
   * @param  Integer CO Person ID of the petitioner
   * @return Boolean True on success
   * @throws RunTimeException
   */
  
  public function linkCoPerson($coPetitionId, $coPersonId, $petitionerId) {
    $this->id = $coPetitionId;
    
    if(!$this->saveField('enrollee_co_person_id', $coPersonId)) {
      throw new RuntimeException(_txt('er.db.save'));
    }
    
    // Create a Petition History Record
    
    try {
      $this->CoPetitionHistoryRecord->record($coPetitionId,
                                             $petitionerId,
                                             PetitionActionEnum::IdentityLinked,
                                             _txt('rs.pt.link.cop', array($coPersonId)));
    }
    catch(Exception $e) {
      throw new RuntimeException(_txt('er.db.save'));
    }
    
    return true;
  }
  
  /**
   * Resend an invite for a Petition.
   * - postcondition: Invite sent
   *
   * @since  COmanage Registry v0.7
   * @param  Integer CO Petition ID
   * @param  Integer CO Person ID of actor sending the invite
   * @throws InvalidArgumentException
   * @return String Address the invitation was resent to
   */
  
  function resend($coPetitionId, $actorCoPersonId) {
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
    $args['conditions']['EnrolleeOrgIdentity.id'] = $this->field('enrollee_org_identity_id');
    $args['contain'] = array('EmailAddress', 'PrimaryName');

    $org = $this->EnrolleeOrgIdentity->find('first', $args);
    
    if(empty($org['EmailAddress'])) {
      throw new InvalidArgumentException(_txt('er.orgp.nomail',
                                              array(generateCn($org['PrimaryName']),
                                                    $args['conditions']['EnrolleeOrgIdentity.id'])));
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
                                        $actorCoPersonId,
                                        $org['EmailAddress'][0]['mail'],
                                        $enrollmentFlow['CoEnrollmentFlow']['notify_from'],
                                        $this->Co->field('name',
                                                         array('Co.id' => $enrollmentFlow['CoEnrollmentFlow']['co_id'])),
                                        !empty($enrollmentFlow['CoEnrollmentFlow']['verification_subject'])
                                        ? $enrollmentFlow['CoEnrollmentFlow']['verification_subject'] : null,
                                        !empty($enrollmentFlow['CoEnrollmentFlow']['verification_body'])
                                        ? $enrollmentFlow['CoEnrollmentFlow']['verification_body'] : null,
                                        null,
                                        !empty($enrollmentFlow['CoEnrollmentFlow']['invitation_validity'])
                                        ? $enrollmentFlow['CoEnrollmentFlow']['invitation_validity'] : null);
    
    // Update the CO Petition with the new invite ID
    
    if(!$this->saveField('co_invite_id', $coInviteId)) {
      throw new RuntimeException(_txt('er.db.save'));
    }
    
    // Add petition history record
    
    try {
      $this->CoPetitionHistoryRecord->record($coPetitionId,
                                             $this->field('petitioner_co_person_id'),
                                             PetitionActionEnum::InviteSent,
                                             _txt('rs.inv.sent', array($org['EmailAddress'][0]['mail'])));
    }
    catch(Exception $e) {
      throw new RuntimeException(_txt('er.db.save'));
    }
    
    return $org['EmailAddress'][0]['mail'];
  }

  /**
   * Save (add/update) Petition attributes, including updates to operational models.
   *
   * @since  COmanage Registry v0.9.4
   * @param  Integer $id CO Petition ID
   * @param  Integer $enrollmentFlowId Enrollment Flow ID
   * @param  Array $requestData Attributes from submitted Petition
   * @param  Integer CO Person ID of the petitioner
   * @return True on success
   * @throws InvalidArgumentException
   * @throws RuntimeException
   * @todo Support update (currently only add supported)
   */
  
  public function saveAttributes($id, $enrollmentFlowId, $requestData, $petitionerId) {
    if(!$id) {
      throw new InvalidArgumentException(_txt('er.notprov.id', array(_txt('ct.petitions.1'))));
    }
    
    // Start a transaction
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
    
    $efAttrs = $this->CoEnrollmentFlow->CoEnrollmentAttribute->enrollmentFlowAttributes($enrollmentFlowId);
    
    // And info about this petition
    
    $args = array();
    $args['conditions']['CoPetition.id'] = $id;
    $args['contain'] = false;
    
    $petition = $this->find('first', $args);
    
    if(!$petition) {
      throw new InvalidArgumentException(_txt('er.notfound', array(_txt('ct.petitions.1'), $id)));
    }
    
    // Set for future saveFields
    $this->id = $id;
    
    // Obtain a list of attributes that are to be copied to the CO Person (Role) from the Org Identity
    
    $cArgs = array();
    $cArgs['conditions']['CoEnrollmentAttribute.co_enrollment_flow_id'] = $enrollmentFlowId;
    $cArgs['conditions']['CoEnrollmentAttribute.copy_to_coperson'] = true;
    $cArgs['fields'] = array('CoEnrollmentAttribute.id', 'CoEnrollmentAttribute.attribute');
    $copyAttrs = $this->CoEnrollmentFlow->CoEnrollmentAttribute->find('list', $cArgs);
    
    // Track various identifiers
    
    $orgIdentityId = (!empty($petition['CoPetition']['enrollee_org_identity_id'])
                      ? $petition['CoPetition']['enrollee_org_identity_id']
                      : null);
    $coPersonId = (!empty($petition['CoPetition']['enrollee_co_person_id'])
                   ? $petition['CoPetition']['enrollee_co_person_id']
                   : null);
    $coPersonRoleId = (!empty($petition['CoPetition']['enrollee_co_person_role_id'])
                       ? $petition['CoPetition']['enrollee_co_person_role_id']
                       : null);
    
    // We need to create a CO/Org Identity Link if either is new
    $createLink = false;
    
    // Track validation
    
    $fail = false;
    $orgData = array();
    $coData = array();
    $coRoleData = array();
    
    // Validate the provided attributes
    
    $CmpEnrollmentConfiguration = ClassRegistry::init('CmpEnrollmentConfiguration');
    
    if($CmpEnrollmentConfiguration->orgIdentitiesFromCOEF()) {
      // Platform is configured to pull org identities from the form.
      // We're keeping this for now for two possible use cases: batch loading of
      // org identities (CO-76) (with subsequent enrollment matching to existing org id)
      // and enrollment without org identities (CO-870).
      
      try {
        $orgData = $this->validateModel('EnrolleeOrgIdentity', $requestData, $efAttrs);
      }
      catch(Exception $e) {
        // Validation failed
        $fail = true;
      }
    }
    
    try {
      $coData = $this->validateModel('EnrolleeCoPerson', $requestData, $efAttrs);
    }
    catch(Exception $e) {
      // Validation failed
      $fail = true;
    }
    
    try {
      $coRoleData = $this->validateModel('EnrolleeCoPersonRole', $requestData, $efAttrs);
    }
    catch(Exception $e) {
      // Validation failed
      $fail = true;
    }
    
    if($fail) {
      // Validation failed
      $dbc->rollback();
      throw new InvalidArgumentException(_txt('er.fields'));
    }
    
    // Create operational records if the petition is not already linked to one
    
    if(!empty($orgData) && !$orgIdentityId) {
      // We might need to inject the CO ID
      // XXX Don't do this or other injections on update, when that gets implemented
      
      $CmpEnrollmentConfiguration = ClassRegistry::init('CmpEnrollmentConfiguration');
      
      if(!$CmpEnrollmentConfiguration->orgIdentitiesPooled())
        $orgData['EnrolleeOrgIdentity']['co_id'] = $petition['CoPetition']['co_id'];
      
      // Save the Org Identity. All the data is validated, so don't re-validate it.
      
      if($this->EnrolleeOrgIdentity->saveAssociated($orgData, array("validate" => false, "atomic" => true))) {
        $orgIdentityId = $this->EnrolleeOrgIdentity->id;
        $createLink = true;
        
        // Update the petition with the new identifier
        $this->saveField('enrollee_org_identity_id', $orgIdentityId);
        
        // Create a history record
        try {
          $this->EnrolleeOrgIdentity->HistoryRecord->record(null,
                                                            null,
                                                            $orgIdentityId,
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
    
    if(!empty($coData) && !$coPersonId) {
      // Insert some additional attributes
      $coData['EnrolleeCoPerson']['co_id'] = $petition['CoPetition']['co_id'];
      $coData['EnrolleeCoPerson']['status'] = StatusEnum::Pending;
      
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
      
      if($this->EnrolleeCoPerson->saveAssociated($coData, array("validate" => false, "atomic" => true))) {
        $coPersonId = $this->EnrolleeCoPerson->id;
        $createLink = true;
        
        // Update the petition with the new identifier
        $this->saveField('enrollee_co_person_id', $coPersonId);
        
        // Create a history record
        try {
          $this->EnrolleeCoPerson->HistoryRecord->record($coPersonId,
                                                         null,
                                                         $orgIdentityId,
                                                         $petitionerId,
                                                         ActionEnum::CoPersonAddedPetition);
          
          // And add an explicit record for each group membership
          
          if(!empty($coData['CoGroupMember'])) {
            foreach($coData['CoGroupMember'] as $gm) {
              // Map the group ID to its name
              
              $groupName = $this->EnrolleeCoPerson
                                ->CoGroupMember
                                ->CoGroup
                                ->field('name',
                                        array('CoGroup.id' => $gm['co_group_id']));
              
              $this->EnrolleeCoPerson
                   ->HistoryRecord
                   ->record($coPersonId,
                            null,
                            null,
                            $petitionerId,
                            ActionEnum::CoGroupMemberAdded,
                            _txt('rs.grm.added-p',
                                 array($groupName,
                                       $gm['co_group_id'],
                                       _txt($gm['member'] ? 'fd.yes' : 'fd.no'),
                                       _txt($gm['owner'] ? 'fd.yes' : 'fd.no'))));
            }
          }
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
    
    if(!empty($coRoleData) && !$coPersonRoleId) {
      // Insert some additional attributes
      $coRoleData['EnrolleeCoPersonRole']['co_person_id'] = $coPersonId;
      $coRoleData['EnrolleeCoPersonRole']['status'] = StatusEnum::Pending;
      
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
        $coPersonRoleId = $this->EnrolleeCoPersonRole->id;
        
        // Update the petition with the new identifier
        $this->saveField('enrollee_co_person_role_id', $coPersonRoleId);
        
        // And COU ID, if set
        if(!empty($coRoleData['EnrolleeCoPersonRole']['cou_id'])) {
          $this->saveField('cou_id', $coRoleData['EnrolleeCoPersonRole']['cou_id']);
        }
        
        // And Sponsor ID, if set
        if(!empty($coRoleData['EnrolleeCoPersonRole']['sponsor_co_person_id'])) {
          $this->saveField('sponsor_co_person_id', $coRoleData['EnrolleeCoPersonRole']['sponsor_co_person_id']);
        }
        
        // Create a history record
        try {
          $this->EnrolleeCoPerson->HistoryRecord->record($coPersonId,
                                                         $coPersonRoleId,
                                                         $orgIdentityId,
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
    
    if($createLink) {
      // Create a CO Org Identity Link
      
      $coOrgLink = array();
      $coOrgLink['CoOrgIdentityLink']['org_identity_id'] = $orgIdentityId;
      $coOrgLink['CoOrgIdentityLink']['co_person_id'] = $coPersonId;
      
      if($this->EnrolleeCoPerson->CoOrgIdentityLink->save($coOrgLink)) {
        // Create a history record
        try {
          $this->EnrolleeCoPerson->HistoryRecord->record($coPersonId,
                                                         $coPersonRoleId,
                                                         $orgIdentityId,
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
    }
    
    // Flatten the attributes to store in the petition
    
    try {
      $petitionAttrs = $this->flattenAttributes($enrollmentFlowId, $id, $orgData, $coData, $coRoleData, $requestData);
    }
    catch(Exception $e) {
      $dbc->rollback();
      throw new RuntimeException($e->getMessage());
    }
    
    // Try to save. Note that saveMany doesn't expect the Model name as an array
    // component, unlike all the other saves.
    
    if(!$this->CoPetitionAttribute->saveMany($petitionAttrs['CoPetitionAttribute'])) {
      $dbc->rollback();
      throw new RuntimeException(_txt('er.db.save'));
    }
    
    // Add a co_petition_history_record
    
    try {
      $this->CoPetitionHistoryRecord->record($id,
                                             $petitionerId,
                                             PetitionActionEnum::AttributesUpdated,
                                             _txt('rs.pt.attr.upd'));
    }
    catch(Exception $e) {
      $dbc->rollback();
      throw new RuntimeException(_txt('er.db.save'));
    }
    
    // Record agreements to Terms and Conditions, if any
    
    if(!empty($requestData['CoTermsAndConditions'])) {
      $tAndCMode = $this->CoEnrollmentFlow->field('t_and_c_mode',
                                                  array('CoEnrollmentFlow.id' => $enrollmentFlowId));
      
      foreach(array_keys($requestData['CoTermsAndConditions']) as $coTAndCId) {
        try {
          // Currently, T&C is only available via a petition when authn is required.
          // The array value should be the authenticated identifier as set by the view.
          
          $this->Co->CoTermsAndConditions->CoTAndCAgreement->record($coTAndCId,
                                                                    $coPersonId,
                                                                    $coPersonId,
                                                                    $requestData['CoTermsAndConditions'][$coTAndCId]);
          
          // Also create a Petition History Record of the agreement
          
          $tcenum = null;
          $tccomment = "";
          $tcdesc = $this->Co->CoTermsAndConditions->field('description',
                                                           array('CoTermsAndConditions.id' => $coTAndCId))
                  . " (" . $coTAndCId . ")";
          
          switch($tAndCMode) {
            case TAndCEnrollmentModeEnum::ExplicitConsent:
              $tcenum = PetitionActionEnum::TCExplicitAgreement;
              $tccomment = _txt('rs.pt.tc.explicit', array($tcdesc));
              break;
            case TAndCEnrollmentModeEnum::ImpliedConsent:
              $tcenum = PetitionActionEnum::TCImpliedAgreement;
              $tccomment = _txt('rs.pt.tc.implied', array($tcdesc));
              break;
            default:
              throw new InvalidArgumentException("Unknown Terms and Conditions Mode: $tAndCMode");
              break;
          }
          
          $this->CoPetitionHistoryRecord->record($id,
                                                 $petitionerId,
                                                 $tcenum,
                                                 $tccomment);
        }
        catch(Exception $e) {
          $dbc->rollback();
          throw new RuntimeException(_txt('er.db.save'));
        }
      }
    }
    
    // Generate a notification for this new petition, if configured
    $notificationGroup = $this->CoEnrollmentFlow->field('notification_co_group_id',
                                                         array('CoEnrollmentFlow.id' => $enrollmentFlowId));
    
    if(!empty($notificationGroup) && !empty($coData['PrimaryName'])) {
      $efName = $this->CoEnrollmentFlow->field('name',
                                               array('CoEnrollmentFlow.id' => $enrollmentFlowId));
      
      $this->Co
           ->CoGroup
           ->CoNotificationRecipientGroup
           ->register($coPersonId,
                      null,
                      $petitionerId,
                      'cogroup',
                      $notificationGroup,
                      ActionEnum::CoPetitionCreated,
                      _txt('rs.pt.create.not', array(generateCn($coData['PrimaryName']), $efName)),
                      array(
                        'controller' => 'co_petitions',
                        'action'     => 'view',
                        'id'         => $id));
    }
    
    // Commit
    $dbc->commit();
    
    return true;
  }
  
  /**
   * Send enrollee approval notification for a Petition.
   * - postcondition: Notification sent
   *
   * @since  COmanage Registry v0.9.4
   * @param  Integer CO Petition ID
   * @param  Integer CO Person ID of actor sending the notification
   * @return True on success
   * @throws InvalidArgumentException
   */
  
  public function sendApprovalNotification($id, $actorCoPersonId) {
    // First we need some info from the petition and enrollment flow
    
    $args = array();
    $args['conditions']['CoPetition.id'] = $id;
    $args['contain'][] = 'CoEnrollmentFlow';
    $args['contain'][] = 'Co';
    $args['contain']['EnrolleeOrgIdentity'] = array('EmailAddress', 'PrimaryName');
    
    $pt = $this->find('first', $args);
    
    if(!$pt) {
      throw new InvalidArgumentException(_txt('er.notfound', array(_txt('ct.co_petitions.1'), $id)));
    }
    
    if(isset($pt['CoEnrollmentFlow']['notify_on_approval'])
       && $pt['CoEnrollmentFlow']['notify_on_approval']) {
      // We'll embed some email logic here (similar to that in CoInvite), since we don't
      // have a notification infrastructure yet. This should get refactored when CO-207
      // is addressed. (Be sure to remove the reference to App::uses('CakeEmail'), above.)
      
      // Which address should we send to? How about the one we sent the invitation to...
      // but we can't guarantee access to that since the invitation will have been
      // discarded. So we use the same logic as resend() above and sendConfirmation() below.
      
      if(!empty($pt['EnrolleeOrgIdentity']['EmailAddress'])) {
        $toEmail = null;
        
        // Which email do we pick? Ultimately we could look at type and/or verified,
        // but for now we'll just pick the first one. Note array_shift will muck with $pt,
        // but we don't need it anymore.
        
        $ea = array_shift($pt['EnrolleeOrgIdentity']['EmailAddress']);
        
        if(empty($ea['mail'])) {
          throw new RuntimeException(_txt('er.orgp.nomail',
                                          array(generateCn($pt['EnrolleeOrgIdentity']['PrimaryName']),
                                                $pt['EnrolleeOrgIdentity']['id'])));
        }
        
        $toEmail = $ea['mail'];
        
        $notifyFrom = $pt['CoEnrollmentFlow']['notify_from'];
        $subjectTemplate = $pt['CoEnrollmentFlow']['approval_subject'];
        $bodyTemplate = $pt['CoEnrollmentFlow']['approval_body'];
        $coName = $pt['Co']['name'];
        
        // Try to send the notification
        
        $email = new CakeEmail('default');
        
        $substitutions = array(
          'CO_NAME' => $coName
        );
        
        try {
          $msgSubject = processTemplate($subjectTemplate, $substitutions);
          $msgBody = processTemplate($bodyTemplate, $substitutions);
          
          $email->emailFormat('text')
                ->to($toEmail)
                ->subject($msgSubject);
          
          // If this enrollment has a default email address set, use it, otherwise leave in the default for the site.
          if(!empty($notifyFrom)) {
            $email->from($notifyFrom);
          }
          
          // Send the email
          $email->send($msgBody);
          
          // And cut a history record
          
          $this->CoPetitionHistoryRecord->record($id,
                                                 $actorCoPersonId,
                                                 PetitionActionEnum::NotificationSent,
                                                 _txt('rs.nt.sent', array($toEmail)));
        }
        catch(Exception $e) {
          // We don't want to fail, but we will at least record that something went wrong
          
          $this->CoPetitionHistoryRecord->record($id,
                                                 $actorCoPersonId,
                                                 PetitionActionEnum::NotificationSent,
                                                 _txt('er.nt.send', array($toEmail, $e->getMessage())));
        }
      } else {
        // We don't want to fail, but we will at least record that something went wrong
        
        $this->CoPetitionHistoryRecord->record($id,
                                               $actorCoPersonId,
                                               PetitionActionEnum::NotificationSent,
                                               _txt('er.nt.email'));
      }
    }
    
    return true;
  }
  
  /**
   * Send approvers notification for a Petition.
   * - postcondition: Notification sent
   *
   * @since  COmanage Registry v0.9.4
   * @param  Integer CO Petition ID
   * @param  Integer CO Person ID of actor sending the notification
   * @return True on success
   * @throws InvalidArgumentException
   */
  
  public function sendApproverNotification($id, $actorCoPersonId) {
    // First we need some info from the petition and enrollment flow
    
    $args = array();
    $args['conditions']['CoPetition.id'] = $id;
    $args['contain'][] = 'CoEnrollmentFlow';
    $args['contain'][] = 'Cou';
    $args['contain']['EnrolleeOrgIdentity'][] = 'PrimaryName';
    
    $pt = $this->find('first', $args);
    
    if(!$pt) {
      throw new InvalidArgumentException(_txt('er.notfound', array(_txt('ct.co_petitions.1'), $id)));
    }
    
    $cogroupids = array();
    
    if(!empty($pt['CoEnrollmentFlow']['approver_co_group_id'])) {
      $cogroupids[] = $pt['CoEnrollmentFlow']['approver_co_group_id'];
    } else {
      // We need to look up the appropriate admin group(s). Start with the CO Admins.
      
      try {
        $cogroupids[] = $this->Co->CoGroup->adminCoGroupId($pt['CoPetition']['co_id']);
      }
      catch(Exception $e) {
        $fail = true;
      }
      
      // To see if we should notify COU Admins, we need to see if this petition was
      // attached to a COU
      
      if(!empty($pt['Cou']['name'])) {
        // Use the COU name so we can map to its admin group
        
        try {
          $cogroupids[] = $this->Co->CoGroup->adminCoGroupId($pt['CoPetition']['co_id'], $pt['Cou']['name']);
        }
        catch(Exception $e) {
          $fail = true;
        }
      }
    }
    
    // Now that we have a list of groups, register the notifications
    // -- we don't fail on notification failures
    
    foreach($cogroupids as $cgid) {
      $this->Co
           ->CoGroup
           ->CoNotificationRecipientGroup
           ->register($pt['CoPetition']['enrollee_co_person_id'],
                      null,
                      $actorCoPersonId,
                      'cogroup',
                      $cgid,
                      ActionEnum::CoPetitionUpdated,
                      _txt('rs.pt.status', array(generateCn($pt['EnrolleeOrgIdentity']['PrimaryName']),
                                                 _txt('en.status.pt', null, $pt['CoPetition']['status']),
                                                 _txt('en.status.pt', null, PetitionStatusEnum::PendingApproval),
                                                 $pt['CoEnrollmentFlow']['name'])),
                      array(
                        'controller' => 'co_petitions',
                        'action'     => 'view',
                        'id'         => $id
                      ),
                      true);
    }
    
    return true;
  }
  
  /**
   * Send a confirmation (invite) for a Petition.
   * - postcondition: Invite sent
   *
   * @since  COmanage Registry v0.9.4
   * @param  Integer CO Petition ID
   * @param  Integer CO Person ID of actor sending the invite
   * @throws InvalidArgumentException
   * @return String Address the invitation was sent to
   */
  
  public function sendConfirmation($id, $actorCoPersonId) {
    // Just let any exceptions fall through
    
    $args = array();
    $args['conditions']['CoPetition.id'] = $id;
    $args['contain']['EnrolleeOrgIdentity'] = array('EmailAddress', 'PrimaryName');
    
    $pt = $this->find('first', $args);
    
    if(empty($pt)) {
      throw new InvalidArgumentException(_txt('er.notfound', array(_txt('ct.co_petitions.1'), $id)));
    }
    
    if(empty($pt['EnrolleeOrgIdentity']['EmailAddress'])) {
      throw new RuntimeException(_txt('er.orgp.nomail',
                                      array(generateCn($pt['EnrolleeOrgIdentity']['PrimaryName']),
                                            $pt['EnrolleeOrgIdentity']['id'])));
    }
    
    $toEmail = null;
    
    // Which email do we pick? Ultimately we could look at type and/or verified,
    // but for now we'll just pick the first one. If this logic changes, resend()
    // will also need to be updated.
    // Note array_shift will muck with $pt, but we don't need it anymore.
    
    $ea = array_shift($pt['EnrolleeOrgIdentity']['EmailAddress']);
    
    if(empty($ea['mail'])) {
      throw new RuntimeException(_txt('er.orgp.nomail',
                                      array(generateCn($pt['EnrolleeOrgIdentity']['PrimaryName']),
                                            $pt['EnrolleeOrgIdentity']['id'])));
    }
    
    $toEmail = $ea['mail'];
    
    // Now we need some info from the enrollment flow
    
    $args = array();
    $args['conditions']['CoEnrollmentFlow.id'] = $pt['CoPetition']['co_enrollment_flow_id'];
    $args['contain'][] = 'Co';
    
    $ef = $this->CoEnrollmentFlow->find('first', $args);
    
    if(!$ef) {
      throw new InvalidArgumentException(_txt('er.notfound', array(_txt('ct.co_enrollment_flows.1'),
                                                                   $pt['CoPetition']['co_enrollment_flow_id'])));
    }
    
    // We can now send the invitation
    $coInviteId = $this->CoInvite->send($pt['CoPetition']['enrollee_co_person_id'],
                                        $pt['CoPetition']['enrollee_org_identity_id'],
                                        $actorCoPersonId,
                                        $toEmail,
                                        $ef['CoEnrollmentFlow']['notify_from'],
                                        $ef['Co']['name'],
                                        $ef['CoEnrollmentFlow']['verification_subject'],
                                        $ef['CoEnrollmentFlow']['verification_body'],
                                        null,
                                        $ef['CoEnrollmentFlow']['invitation_validity']);
    
    // Add the invite ID to the petition record
    
    $this->id = $id;
    $this->saveField('co_invite_id', $coInviteId);
    
    // And add a petition history record
    
    try {
      $this->CoPetitionHistoryRecord->record($id,
                                             $actorCoPersonId,
                                             PetitionActionEnum::InviteSent,
                                             _txt('rs.inv.sent', array($toEmail)));
    }
    catch(Exception $e) {
      $dbc->rollback();
      throw new RuntimeException(_txt('er.db.save'));
    }
    
    return $toEmail;
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
    $coID = $this->field('co_id');
    
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
    
    // We may need these later
    $coPersonID = $this->field('enrollee_co_person_id');
    $coPersonRoleID = $this->field('enrollee_co_person_role_id');    
    
    if($curStatus == StatusEnum::PendingConfirmation) {
      // A Petition can go from Pending Confirmation to Pending Approval, Approved, or Denied.
      // It can also go to Confirmed, though we'll override that.
      
      if($newStatus == StatusEnum::Approved
         || $newStatus == StatusEnum::Confirmed
         || $newStatus == StatusEnum::Declined
         || $newStatus == StatusEnum::Denied
         || $newStatus == StatusEnum::Duplicate
         || $newStatus == StatusEnum::PendingApproval) {
        $valid = true;
      }
      
      // If newStatus is Confirmed create an additional history record.
      
      if($newStatus == StatusEnum::Confirmed) {
        try {
          $this->CoPetitionHistoryRecord->record($id,
                                                 $actorCoPersonID,
                                                 PetitionActionEnum::InviteConfirmed);
        }
        catch (Exception $e) {
          throw new RuntimeException($e->getMessage());
        }
      }
    } elseif($curStatus == StatusEnum::PendingApproval) {
      // A Petition can go from PendingApproval to Approved or Denied
      
      if($newStatus == StatusEnum::Approved
         || $newStatus == StatusEnum::Denied
         || $newStatus == StatusEnum::Duplicate) {
        $valid = true;
      }
    } elseif($curStatus == StatusEnum::Approved) {
      // On finalization, set the CO Person and CO Person Role to Active.
      
      if($newStatus == PetitionStatusEnum::Finalized) {
        $valid = true;
        $newPetitionStatus = PetitionStatusEnum::Finalized;
        $newCoPersonStatus = StatusEnum::Active;
      }
    } elseif($curStatus == PetitionStatusEnum::Created) {
      if($newStatus == PetitionStatusEnum::Finalized) {
        $newPetitionStatus = PetitionStatusEnum::Finalized;
        $newCoPersonStatus = StatusEnum::Active;
      }
      
      $valid = true;
    } else {
      // For now accept all other status transitions. It might make sense to drop
      // the validity check completely.
      
      $valid = true;
    }
    
    // If a CO Person Role is defined update the CO Person (& Role) status,
    // but not if the new petition status is Finalized (since that doesn't apply
    // to the person/role).
    
    if($coPersonRoleID && $newPetitionStatus != PetitionStatusEnum::Finalized) {
      $newCoPersonStatus = $newPetitionStatus;
    }
    
    if($valid) {
      // Process the new status
      $fail = false;
      
      // Start a transaction
      $dbc = $this->getDataSource();
      $dbc->begin();
      
      // Update the Petition status, if it changed.
      
      if($curStatus != $newPetitionStatus) {
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
          $comment = null;
          
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
            case StatusEnum::Duplicate:
              $petitionAction = PetitionActionEnum::FlaggedDuplicate;
              break;
            default:
              $petitionAction = PetitionActionEnum::StatusUpdated;
              $comment = _txt('rs.pt.status.h', array(_txt('en.status.pt', null, $curStatus),
                                                      _txt('en.status.pt', null, $newPetitionStatus)));
              break;
          }
          
          if($petitionAction) {
            try {
              $this->CoPetitionHistoryRecord->record($id,
                                                     $actorCoPersonID,
                                                     $petitionAction,
                                                     $comment);
            }
            catch (Exception $e) {
              $fail = true;
            }
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
      
      // Recalculate the overall CO Person status
      
      if(!$fail && isset($newCoPersonStatus)) {
        if($coPersonID) {
          try {
            $this->EnrolleeCoPerson->recalculateStatus($coPersonID);
          }
          catch(Exception $e) {
            $fail = true;
          }
        } else {
          $fail = true;
        }
      }
      
      // If this is a denial of a petition pending confirmation, clear out the
      // pending invitation.
      
      if(!$fail && $curStatus == StatusEnum::PendingConfirmation
         && $newPetitionStatus == StatusEnum::Denied) {
        $inviteid = $this->field('co_invite_id');
        
        if($inviteid) {
          if($this->saveField('co_invite_id', null)) {
            $this->CoInvite->delete($inviteid);
          }
        }
      }
      
      // Register some notifications. We'll need the enrollee's name for this.
      
      if($coPersonID) {
        $args = array();
        $args['conditions']['EnrolleeCoPerson.id'] = $coPersonID;
        $args['contain'][] = 'PrimaryName';
        
        $enrollee = $this->EnrolleeCoPerson->find('first', $args);
        
        if(!empty($enrollmentFlow['CoEnrollmentFlow']['notification_co_group_id'])) {
          // If there is a notification group defined, send info on the status change
          // -- we don't fail on notification failures
          
          $this->Co
               ->CoGroup
               ->CoNotificationRecipientGroup
               ->register($coPersonID,
                          null,
                          $actorCoPersonID,
                          'cogroup',
                          $enrollmentFlow['CoEnrollmentFlow']['notification_co_group_id'],
                          ActionEnum::CoPetitionUpdated,
                          _txt('rs.pt.status', array(generateCn($enrollee['PrimaryName']),
                                                     _txt('en.status.pt', null, $curStatus),
                                                     _txt('en.status.pt', null, $newPetitionStatus),
                                                     $enrollmentFlow['CoEnrollmentFlow']['name'])),
                          array(
                            'controller' => 'co_petitions',
                            'action'     => 'view',
                            'id'         => $id));
        }
      }
      
      if($curStatus == StatusEnum::PendingApproval
         && ($newPetitionStatus == StatusEnum::Approved
             || $newPetitionStatus == StatusEnum::Denied)) {
        // Clear any approval notifications -- we don't fail on notification failures
        
        $this->Co
             ->CoGroup
             ->CoNotificationRecipientGroup
             ->resolveFromSource(array(
                                  'controller' => 'co_petitions',
                                  'action'     => 'view',
                                  'id'         => $id
                                ),
                                $actorCoPersonID);
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
        // For now, we assume the identifier type is ePPN. This probably isn't right,
        // and should be customizable. (CO-460)
        
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
          
          $args = array();
          $args['conditions'][] = 'Identifier.org_identity_id IS NOT NULL';
          $args['conditions']['Identifier.identifier'] = $loginIdentifier;
          $args['conditions']['Identifier.type'] = IdentifierEnum::ePPN;
          $args['conditions']['Identifier.status'] = StatusEnum::Active;
          
          $CmpEnrollmentConfiguration = ClassRegistry::init('CmpEnrollmentConfiguration');
          
          if(!$CmpEnrollmentConfiguration->orgIdentitiesPooled()) {
            // If org identities are not pooled, we need to join against org identity
            // to filter on CO
            
            $args['joins'][0]['table'] = 'org_identities';
            $args['joins'][0]['alias'] = 'OrgIdentity';
            $args['joins'][0]['type'] = 'INNER';
            $args['joins'][0]['conditions'][0] = 'Identifier.org_identity_id=OrgIdentity.id';
          }
          
          $i = $this->EnrolleeOrgIdentity->Identifier->findForUpdate($args['conditions'], array('identifier'));
          
          if(!empty($i)) {
            throw new RuntimeException(_txt('er.ia.exists', array($loginIdentifier)));
          }
          
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
        
        // Store the authenticated identifier in the petition. We don't do this as
        // a foreign key because (1) there is no corresponding co_enrollment_attribute
        // and (2) the identifier might subsequently be deleted from the identifiers table.
        
        if(!$this->saveField('authenticated_identifier', $loginIdentifier)) {
          throw new RuntimeException(_txt('er.db.save'));
        }
        
        // Create a petition history record
        
        $this->CoPetitionHistoryRecord->record($id,
                                               $actorCoPersonId,
                                               PetitionActionEnum::IdentifierAuthenticated,
                                               _txt('rs.pt.id.auth', array($loginIdentifier)));
      } else {
        throw new InvalidArgumentException(_txt('er.notprov.id', array('ct.org_identities.1')));
      }
    }
  }
  
  /**
   * Validate a model's worth of data provided in a petition, including related models.
   *
   * @since  COmanage Registry v0.9.4
   * @param  string $pmodel Name of model to validate
   * @param  Array $requestData Data as submitted in the petition
   * @param  Array $efAttrs Enrollment flow attribute configuration
   * @return Array of validated attributes
   * @throws RuntimeException
   */
  
  protected function validateModel($pmodel, $requestData, $efAttrs) {
    $ret = array();
    
    if(!empty($requestData[$pmodel])) {
      // Adjust validation rules for top level attributes only (OrgIdentity, CO Person, CO Person Role)
      // and validate those models without validating the associated models.
      
      // We'll start building an array of org data to save as we validate the provided data.
      
      $ret[$pmodel] = $this->$pmodel->filterModelAttributes($requestData[$pmodel]);
      
      // Dynamically adjust validation rules according to the enrollment flow
      $this->adjustValidationRules($pmodel, $efAttrs);
      
      // Manually validate OrgIdentity
      $this->$pmodel->set($ret);
      
      // Make sure to use invalidFields(), which won't try to validate (possibly
      // missing) related models.
      $errFields = $this->$pmodel->invalidFields();
      
      if(!empty($errFields)) {
        $fail = true;
      }
      
      // Now validate related models
      
      $v = $this->validateRelated($pmodel, $requestData, $ret, $efAttrs);
      
      if($v) {
        $ret = $v;
      } else {
        throw new RuntimeException(_txt('er.validation'));
      }
    }
    
    return $ret;
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
            
            foreach($efAttrs as $efAttr) {
              if($efAttr['id'] == $instance) {
                // Should this be consolidated with adjustValidationRules()? (CO-907)
                
                // Make sure the validation rule matches the required status of this attribute
                $xfield = $this->$primaryModel->$model->validator()->getField($efAttr['field']);
                
                if($xfield) {
                  $xreq = (isset($efAttr['required']) && $efAttr['required']);
                  
                  $xfield->getRule('content')->required = $xreq;
                  $xfield->getRule('content')->allowEmpty = !$xreq;
                  
                  if($xreq) {
                    $xfield->getRule('content')->message = _txt('er.field.req');
                  }
                  
                  // Set the actual validation rule to be match the enrollment configuration.
                  // This is especially necessary for extended types.
                  
                  if(!empty($efAttr['validate']['content']['rule'])) {
                    $xfield->getRule('content')->rule = $efAttr['validate']['content']['rule'];
                  }
                }
                // else not a relevant field (eg: co_enrollment_attribute_id)
              }
            }
            
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
          
          $vrule = $efAttr['validate']['content'];
          $vreq = (isset($efAttr['required']) && $efAttr['required']);
          
          $vrule['required'] = $vreq;
          $vrule['allowEmpty'] = !$vreq;
          $vrule['message'] = _txt('er.field.req');
          
          $this->$primaryModel->$model->validator()->add($efAttr['field'],
                                                         'content',
                                                         $vrule);
          
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
