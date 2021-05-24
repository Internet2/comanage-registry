<?php
/**
 * COmanage Registry CO Petition Model
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
 * @since         COmanage Registry v0.3
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::uses('CakeEmail', 'Network/Email');

class CoPetition extends AppModel {
  // Define class name for cake
  public $name = "CoPetition";
  
  // Current schema version for API
  public $version = "1.0";
  
  // Add behaviors
  public $actsAs = array('Containable',
                         // We need linkable to run first to set up the query,
                         // changelog to run next to clean it up, and then
                         // containable (which actually doesn't do anything here)
                         'Linkable.Linkable' => array('priority' => 4),
                         'Changelog' => array('priority' => 5));
  
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
    "ArchivedOrgIdentity" => array(
      'className' => 'OrgIdentity',
      'foreignKey' => 'archived_org_identity_id'
    ),
    "PetitionerCoPerson" => array(
      'className' => 'CoPerson',
      'foreignKey' => 'petitioner_co_person_id'
    ),
    "SponsorCoPerson" => array(
      'className' => 'CoPerson',
      'foreignKey' => 'sponsor_co_person_id'
    )
  );
  
  public $hasMany = array(
    // A CO Petition has zero or more CO Petition Attributes
    "CoPetitionAttribute" => array('dependent' => true),
    // A CO Petition has zero or more CO Petition History Records
    "CoPetitionHistoryRecord" => array('dependent' => true),
    "OrgIdentitySourceRecord"
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
      'content' => array(
        'rule' => 'numeric',
        'required' => false,
        'allowEmpty' => true,
        'unfreeze' => 'CO'
      )
    ),
    'enrollee_org_identity_id' => array(
      'content' => array(
        'rule' => 'numeric',
        'required' => false,
        'allowEmpty' => true,
        'unfreeze' => 'CO'
      )
    ),
    'enrollee_co_person_id' => array(
      'content' => array(
        'rule' => 'numeric',
        'required' => false,
        'allowEmpty' => true,
        'unfreeze' => 'CO'
      )
    ),
    'enrollee_co_person_role_id' => array(
      'content' => array(
        'rule' => 'numeric',
        'required' => false,
        'allowEmpty' => true,
        'unfreeze' => 'CO'
      )
    ),
    'petitioner_co_person_id' => array(
      'content' => array(
        'rule' => 'numeric',
        'required' => false,
        'allowEmpty' => true,
        'unfreeze' => 'CO'
      )
    ),
    'sponsor_co_person_id' => array(
      'content' => array(
        'rule' => 'numeric',
        'required' => false,
        'allowEmpty' => true,
        'unfreeze' => 'CO'
      )
    ),
    'approver_co_person_id' => array(
      'content' => array(
        'rule' => 'numeric',
        'required' => false,
        'allowEmpty' => true,
        'unfreeze' => 'CO'
      )
    ),
    'co_invite_id' => array(
      'content' => array(
        'rule' => 'numeric',
        'required' => false,
        'allowEmpty' => true,
        'unfreeze' => 'CO'
      )
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
              // Use model's existing message if there is one
              if(empty($xfield->getRule('content')->message)) {
                $xfield->getRule('content')->message = _txt('er.field.req');
              }
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
   * Possibly assign cluster accounts for a petition.
   *
   * @since  COmanage Registry v3.3.0
   * @param  Integer $id CO Petition ID
   * @param  Integer $actorCoPersonId CO Person ID for actor
   */
  
  public function assignClusterAccounts($id, $actorCoPersonId) {
    $coPersonID = $this->field('enrollee_co_person_id', array('CoPetition.id' => $id));
    $enrollmentFlowID = $this->field('co_enrollment_flow_id', array('CoPetition.id' => $id));
    //$coID = $this->field('co_id', array('CoPetition.id' => $id));
    
    if($coPersonID) {
      $clusters = $this->CoEnrollmentFlow
                       ->CoEnrollmentCluster
                       ->active($enrollmentFlowID);

      $clusterIds = array();
      
      // XXX Could use Hash?
      foreach($clusters as $c) {
        if($c['CoEnrollmentCluster']['enabled']) {
          $clusterIds[] = $c['Cluster']['id'];
        }
      }

      if($clusterIds) {
        $res = $this->CoEnrollmentFlow
                    ->CoEnrollmentCluster
                    ->Cluster
                    ->assign($coPersonID, $actorCoPersonId, $clusterIds);
      } else {
        $res = array();
      }
      
      if(!empty($res)) {
        // Create Petition History Records for any results of interest
        
        foreach($res as $desc => $result) {
          $str = false;
          
          if($result === true) {
            $str = _txt('rs.cluster.acct.ok', array($desc));
          } else {
            $str = $result;
          }
          
          if($str !== false) {
            try {
              $this->CoPetitionHistoryRecord->record($id,
                                                     $actorCoPersonId,
                                                     PetitionActionEnum::ClusterAccountAutoCreated,
                                                     $str);
            }
            catch(Exception $e) {
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
    
    if($coPersonID) {
      $res = $this->EnrolleeCoPerson->Identifier->assign('CoPerson', $coPersonID, $actorCoPersonId, false);
      
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
         || $efAttr['field'] == 'primary_name'
         || $efAttr['field'] == 'status') {
        
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
   * Check the eligibility for a CO Petition.
   *
   * @since  COmanage Registry v2.0.0
   * @param  Integer $id CO Petition ID
   * @param  Integer $actorCoPersonId CO Person ID of the person triggering the relink
   * @throws InvalidArgumentException
   */
  
  public function checkEligibility($id, $actorCoPersonId) {
    // The initial implementation only uses email address to query the OIS backends.
    // Pull the enrollee Org Identity and look for a verified email address.
    
    // Start a transaction
    $dbc = $this->getDataSource();
    $dbc->begin();
    
    // Pull the petition and enrollment flow configuration
    $args = array();
    $args['conditions']['CoPetition.id'] = $id;
    $args['contain']['CoEnrollmentFlow']['CoEnrollmentSource'][] = 'OrgIdentitySource';
    
    $pt = $this->find('first', $args);
    
    // Make sure we're configured with at least one plugin with an OISSearch mode.
    // Track which plugins are in which mode, we'll need it later for checking if
    // required plugins were matched.
    
    // These arrays will be of the form id => boolean, where id is the Enrollment Source ID
    // and boolean is false if no matching record found, true if found.
    $searchSources = array();
    $requiredSources = array();
    
    if(!empty($pt['CoEnrollmentFlow']['CoEnrollmentSource'])) {
      foreach($pt['CoEnrollmentFlow']['CoEnrollmentSource'] as $es) {
        // If an OIS is suspended, we treat it as though it weren't attached in the first place
        if(isset($es['OrgIdentitySource']['status'])
           && $es['OrgIdentitySource']['status'] == SuspendableStatusEnum::Active) {
          if($es['org_identity_mode'] == EnrollmentOrgIdentityModeEnum::OISSearch) {
            $searchSources[ $es['id'] ] = false;
          } elseif($es['org_identity_mode'] == EnrollmentOrgIdentityModeEnum::OISSearchRequired) {
            $requiredSources[ $es['id'] ] = false;
          }
        }
      }
    }
    
    if(empty($searchSources) && empty($requiredSources)) {
      // Nothing to do
      $dbc->rollback();
      return;
    }
    
    if(empty($pt['CoPetition']['enrollee_co_person_id'])) {
      $dbc->rollback();
      throw new InvalidArgumentException(_txt('er.notprov.id', array(_txt('ct.co_people.1'))));
    }
    
    $emailAddresses = array();
    
    if(empty($pt['CoPetition']['enrollee_org_identity_id'])) {
      // If no Org Identity is attached to the petition, we may be attempting to
      // refresh eligibility (ie: recheck Enrollment Sources for updated matches
      // since the initial enrollment). Pull all verified addresses associated with the CO Person.
      
      $args = array();
      $args['joins'][0]['table'] = 'co_org_identity_links';
      $args['joins'][0]['alias'] = 'CoOrgIdentityLink';
      $args['joins'][0]['type'] = 'INNER';
      $args['joins'][0]['conditions'][0] = 'CoOrgIdentityLink.org_identity_id=EmailAddress.org_identity_id';
      $args['joins'][1]['table'] = 'co_people';
      $args['joins'][1]['alias'] = 'CoPerson';
      $args['joins'][1]['type'] = 'INNER';
      $args['joins'][1]['conditions'][0] = 'CoOrgIdentityLink.co_person_id=CoPerson.id';
      $args['conditions']['CoPerson.id'] = $pt['CoPetition']['enrollee_co_person_id'];
      $args['conditions']['EmailAddress.verified'] = true;
      $args['contain'] = false;
      
      $emailAddresses = $this->EnrolleeOrgIdentity->EmailAddress->find('all', $args);
    } else {
      // For each verified email address we find associated with the Org Identity in the
      // petition (typically only zero or one), query all configured OIS backends.
      // If a match is required, the petition will automatically transition to a denied state.
      
      // It's plausible we could look for email addresses attached to the CO Person
      // record, but we don't have a use case for that yet.
      
      $args = array();
      $args['conditions']['EmailAddress.org_identity_id'] = $pt['CoPetition']['enrollee_org_identity_id'];
      $args['conditions']['EmailAddress.verified'] = true;
      $args['contain'] = false;
      
      $emailAddresses = $this->EnrolleeOrgIdentity->EmailAddress->find('all', $args);
    }
    
    // Pull names as well if we need them for verify_family_name
    $names = array();
    
    if($es['verify_family_name']) {
      $args = array();
      $args['joins'][0]['table'] = 'co_org_identity_links';
      $args['joins'][0]['alias'] = 'CoOrgIdentityLink';
      $args['joins'][0]['type'] = 'INNER';
      $args['joins'][0]['conditions'][0] = 'CoOrgIdentityLink.org_identity_id=Name.org_identity_id';
      $args['joins'][1]['table'] = 'co_people';
      $args['joins'][1]['alias'] = 'CoPerson';
      $args['joins'][1]['type'] = 'INNER';
      $args['joins'][1]['conditions'][0] = 'CoOrgIdentityLink.co_person_id=CoPerson.id';
      $args['conditions']['CoPerson.id'] = $pt['CoPetition']['enrollee_co_person_id'];
      $args['contain'] = false;
      
      $names = $this->EnrolleeOrgIdentity->Name->find('all', $args);
    }
    
    foreach($emailAddresses as $ea) {
      if(!empty($ea['EmailAddress']['mail'])) {
        foreach($pt['CoEnrollmentFlow']['CoEnrollmentSource'] as $es) {
          if(isset($es['OrgIdentitySource']['status'])
           && $es['OrgIdentitySource']['status'] == SuspendableStatusEnum::Active) {
            if($es['org_identity_mode'] == EnrollmentOrgIdentityModeEnum::OISSearch
               || $es['org_identity_mode'] == EnrollmentOrgIdentityModeEnum::OISSearchRequired) {
              // Since this is search and not retrieve, it's technically possible to get
              // more than one result back from a source, if (eg) there are multiple records
              // with the same email address. It's not exactly clear what to do in that situation,
              // so for now we just add each record.
              
              try {
                $oisResults = $this->Co->OrgIdentitySource->search($es['org_identity_source_id'],
                                                                   array('mail' => $ea['EmailAddress']['mail']));
              }
              catch(Exception $e) {
                // It's not really clear what to do on a failure, other than than we want to
                // keep going. For now we'll record the failure in the log, but we need a better
                // way to handle this. Also, this isn't I18n'd.
                
                $this->log("ERROR: OrgIdentitySource " . $es['org_identity_source_id'] . " : " . $e->getMessage());
                continue;
              }
              
              foreach($oisResults as $sourceKey => $oisRecord) {
                // The name from the source is available in $oisRecord
                if($es['verify_family_name']
                   && !empty($oisRecord['Name'])) {
                  $matched = false;
                  
                  // There could be more than one name from the source, though
                  // generally there will just be the Primary Name.
                  $oisnames = array();
                  
                  foreach($oisRecord['Name'] as $n) {
                    // XXX Should we strip out non-alpha?
                    $oisnames[] = strtolower($n['family']);
                  }
                  
                  foreach($names as $n) {
                    if(!empty($n['Name']['family'])
                       && in_array(strtolower($n['Name']['family']), $oisnames)) {
                      $matched = true;
                      break;
                    }
                  }
                  
                  if(!$matched) {
                    // Log petition history and move on (ie: don't createOrgIdentity)                      
                    
                    $this->CoPetitionHistoryRecord->record($id,
                                                           $actorCoPersonId,
                                                           PetitionActionEnum::IdentityNotLinked,
                                                           _txt('rs.pt.ois.link.name', array($es['OrgIdentitySource']['description'],
                                                                                             $es['org_identity_source_id'])));
                    
                    continue;
                  }
                }

                // createOrgIdentity will also create the link to the CO Person. It may also
                // run a pipeline (if configured). Which Pipeline we want to run is a bit confusing,
                // since the Enrollment Flow, the OIS, and the CO can all have a pipeline configured.
                // The normal priority is EF > OIS > CO (as per OrgIdentity.php. However, since a
                // given EF can only create a single Org Identity, Org Identities created here aren't
                // attached to the Petition and therefore aren't considered to have been created
                // by an Enrollment Flow. So the Pipeline that will execute is either the one
                // attached to the OIS, or if none the one attached to the CO.
                
                try {
                  $newOrgIdentityId = $this->Co->OrgIdentitySource->createOrgIdentity($es['org_identity_source_id'],
                                                                                      $sourceKey,
                                                                                      $actorCoPersonId,
                                                                                      $pt['CoPetition']['co_id'],
                                                                                      $pt['CoPetition']['enrollee_co_person_id'],
                                                                                      false);
                  
                  // Note that we found something
                  if($es['org_identity_mode'] == EnrollmentOrgIdentityModeEnum::OISSearch) {
                    $searchSources[ $es['id'] ] = true;
                  } elseif($es['org_identity_mode'] == EnrollmentOrgIdentityModeEnum::OISSearchRequired) {
                    $requiredSources[ $es['id'] ] = true;
                  }
                  
                  $this->CoPetitionHistoryRecord->record($id,
                                                         $actorCoPersonId,
                                                         PetitionActionEnum::IdentityLinked,
                                                         _txt('rs.pt.ois.link', array($newOrgIdentityId,
                                                                                      $es['OrgIdentitySource']['description'],
                                                                                      $es['org_identity_source_id'])));
                } 
                catch(OverflowException $e) {
                  // If there's already an org identity associated with the OIS, we
                  // definitely don't link it, but should we throw an error of some
                  // sort? It's a bit complicated... it could be a duplicate
                  // enrollment... or we're rechecking eligibility... or there's already
                  // an org identity linked to the same CO Person that this enrollment
                  // is linked to, which might be OK in some circumstances. For now, we
                  // won't do anything, just continue.
                }
                // else let the exception pass back up the stack
              }
            }
          }
        }
      }
    }

    // Make sure we found a match against each required source
    if(!empty($requiredSources)) {
      foreach($requiredSources as $esid => $found) {
        if(!$found) {
          // At least one required source was not matched, so deny this petition
          
          $this->updateStatus($id,
                              PetitionStatusEnum::Denied,
                              $actorCoPersonId);
          
          // Add petition history
          
          $this->CoPetitionHistoryRecord->record($id,
                                                 $actorCoPersonId,
                                                 PetitionActionEnum::EligibilityFailed);
        }
      }
    }

    $dbc->commit();
    
    return;
  }
  
  /**
   * Determine the current step for a CO Petition.
   *
   * @since  COmanage Registry v1.0.0
   * @param  Integer $id CO Petition ID
   * @return String Step label
   * @throws InvalidArgumentException
   */
  
  public function currentStep($id) {
    // This is designed more for an administrator than an exact representation of the
    // step. That is, collectIdentifier is usually the current step for a very brief
    // moment, so we don't handle it here. But we could at some point.
    
    $status2step = array(
      PetitionStatusEnum::Active              => 'done',
      PetitionStatusEnum::Approved            => 'finalize',
      PetitionStatusEnum::Confirmed           => 'waitForApproval',
      PetitionStatusEnum::Created             => 'petitionerAttributes',  // or selectEnrollee
      PetitionStatusEnum::Declined            => 'done',
      PetitionStatusEnum::Denied              => 'done',
      PetitionStatusEnum::Duplicate           => 'done',
      PetitionStatusEnum::Finalized           => 'done',
      PetitionStatusEnum::PendingApproval     => 'waitForApproval',
      PetitionStatusEnum::PendingConfirmation => 'waitForConfirmation'
    );
    
    // Pull the status of the petition
    $status = $this->field('status', array('CoPetition.id' => $id));
    
    if(!$status) {
      throw new InvalidArgumentException(_txt('er.notfound', array(_txt('ct.co_petitions.1'), $id)));
    }
    
    // And the configured steps
    $efId = $this->field('co_enrollment_flow_id', array('CoPetition.id' => $id));
    
    $configuredSteps = $this->CoEnrollmentFlow->configuredSteps($efId);
    
    // $configuredSteps should be roughly in order, so walk through looking for what we
    // think is the next step. Return it if configured, or keep going if not.
    
    $found = false;
    
    foreach($configuredSteps as $step => $settings) {
      if($step == $status2step[$status]) {
        if($settings['enabled'] != RequiredEnum::NotPermitted) {
          return $status2step[$status];
        }
        
        $found = true;
      } elseif($found && $settings['enabled'] != RequiredEnum::NotPermitted) {
        // This is the next configured step
        return $step;
      }
    }
    
    return $status2step[$status];
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
      
      // We actually want the modified time, not the create time since
      // ChangelogBehavior "edits in place".
      $eaTime = strtotime($ea['CoEnrollmentAttribute']['modified']);
      
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
   * @param  String  URL to redirect to after enrollment, decoded
   * @return Integer ID of newly created Petition
   * @throws RuntimeException
   */
  
  public function initialize($enrollmentFlowID, $coId, $petitionerId=null, $returnUrl=null) {
    $this->CoEnrollmentFlow->id = $enrollmentFlowID;
    $efName = $this->CoEnrollmentFlow->field('name');
    
    $coPetitionData = array();
    $coPetitionData['CoPetition']['co_enrollment_flow_id'] = $enrollmentFlowID;
    $coPetitionData['CoPetition']['co_id'] = $coId;
    $coPetitionData['CoPetition']['status'] = PetitionStatusEnum::Created;
    $coPetitionData['CoPetition']['return_url'] = $returnUrl;
    
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
   * @param  Integer Enrollment Flow ID
   * @param  Integer CO Petition ID
   * @param  Integer CO Person ID to link
   * @param  Integer CO Person ID of the petitioner
   * @return Boolean True on success
   * @throws RuntimeException
   */
  
  public function linkCoPerson($enrollmentFlowID, $coPetitionId, $coPersonId, $petitionerId) {
    $this->id = $coPetitionId;
    
    if(!$this->saveField('enrollee_co_person_id', $coPersonId)) {
      throw new RuntimeException(_txt('er.db.save'));
    }
    
    // Pull the enrollment flow name
    $efName = $this->CoEnrollmentFlow->field('name', array('CoEnrollmentFlow.id' => $enrollmentFlowID));
    
    // Create a Petition History Record
    
    try {
      $this->CoPetitionHistoryRecord->record($coPetitionId,
                                             $petitionerId,
                                             PetitionActionEnum::IdentityLinked,
                                             _txt('rs.pt.link.cop', array($coPersonId)));
      
      // Also create a regular History Record to make it easier to see petitions
      // for existing records
      
      $this->EnrolleeCoPerson->HistoryRecord->record($coPersonId,
                                                     null,
                                                     null,
                                                     $petitionerId,
                                                     ActionEnum::CoPetitionUpdated,
                                                     _txt('rs.pt.link', array($efName)));
    }
    catch(Exception $e) {
      throw new RuntimeException(_txt('er.db.save'));
    }
    
    return true;
  }
  
  
  /**
   * Link an existing Org Identity to a CO Petition.
   *
   * @since  COmanage Registry v2.0.0
   * @param  Integer Enrollment Flow ID
   * @param  Integer CO Petition ID
   * @param  Integer Org Identity ID to link
   * @param  Integer CO Person ID of the petitioner
   * @return Boolean True on success
   * @throws RuntimeException
   */
  
  public function linkOrgIdentity($enrollmentFlowID, $coPetitionId, $orgIdentityId, $petitionerId) {
    $this->id = $coPetitionId;
    
    if(!$this->saveField('enrollee_org_identity_id', $orgIdentityId)) {
      throw new RuntimeException(_txt('er.db.save'));
    }
    
    // Pull the enrollment flow name
    $efName = $this->CoEnrollmentFlow->field('name', array('CoEnrollmentFlow.id' => $enrollmentFlowID));
    
    // Create a Petition History Record
    
    try {
      $this->CoPetitionHistoryRecord->record($coPetitionId,
                                             $petitionerId,
                                             PetitionActionEnum::IdentityLinked,
                                             _txt('rs.pt.link.org', array($orgIdentityId)));
      
      // Also create a regular History Record to make it easier to see petitions
      // for existing records
      
      $this->EnrolleeCoPerson->HistoryRecord->record(null,
                                                     null,
                                                     $orgIdentityId,
                                                     $petitionerId,
                                                     ActionEnum::CoPetitionUpdated,
                                                     _txt('rs.pt.link', array($efName)));
    }
    catch(Exception $e) {
      throw new RuntimeException(_txt('er.db.save'));
    }
    
    return true;
  }
  
  /**
   * Relink a Petition and the associated CO Person to an already existing Org Identity.
   * This function should be called from within a transaction.
   *
   * @param Integer $id CO Petition ID
   * @param Integer $targetOrgIdentityId Org Identity ID to relink to
   * @param Integer $actorCoPersonId CO Person ID of the person triggering the relink
   * @param String  $mode How to handle original identity: 'delete', 'merge', 'replace'
   * @throws RuntimeException
   */
  
  public function relinkOrgIdentity($id, $targetOrgIdentityId, $actorCoPersonId, $mode='delete') {
    // This is probably already set, but just in case.
    $this->id = $id;
    
    // Pull the org identity links for the EnrolleeOrgIdentity and make sure
    // it is ONLY attached to the EnrolleeCoPersonId. If so, relink and delete.
    
    $enrolleeCoPId = $this->field('enrollee_co_person_id');
    $petitionOrgId = $this->field('enrollee_org_identity_id');
    
    $args = array();
    $args['conditions']['CoOrgIdentityLink.org_identity_id'] = $petitionOrgId;
    
    $lnks = $this->EnrolleeOrgIdentity->CoOrgIdentityLink->findForUpdate($args['conditions'],
                                                                         array('id', 'co_person_id'));
    
    if(empty($lnks)
       || (count($lnks)==1
           && $lnks[0]['CoOrgIdentityLink']['co_person_id'] == $enrolleeCoPId)) {
      // There are no links from the enrollee org identity or there is exactly one link
      // and it is to the enrollee CO Person, so we're clear to proceed. First, relink
      // the petition and the CO Org Identity Link.
     
      if(!$this->saveField('enrollee_org_identity_id', $targetOrgIdentityId)) {
        throw new RuntimeException(_txt('er.db.save'));
      }
      
      if(!empty($lnks[0]['CoOrgIdentityLink']['id'])) {
        // This should only match the row retrieved above
        
        // If there is already a link between $targetOrgIdentityId and $enrolleeCoPId,
        // then we just want to delete this (new) link, rather than rewrite it to
        // create a duplicate link.
        
        $args = array();
        $args['conditions']['CoOrgIdentityLink.org_identity_id'] = $targetOrgIdentityId;
        $args['conditions']['CoOrgIdentityLink.co_person_id'] = $enrolleeCoPId;
        
        if($this->EnrolleeOrgIdentity->CoOrgIdentityLink->find('count', $args) > 0) {
          // Should really only ever be 0 or 1... but anyway, we have an existing link
          // so just toss this link
          
          $this->EnrolleeOrgIdentity->CoOrgIdentityLink->delete($lnks[0]['CoOrgIdentityLink']['id']);
        } else {
          $this->EnrolleeOrgIdentity->CoOrgIdentityLink->id = $lnks[0]['CoOrgIdentityLink']['id'];
          
          if(!$this->EnrolleeOrgIdentity->CoOrgIdentityLink->saveField('org_identity_id', $targetOrgIdentityId /*$petitionOrgId*/)) {
            throw new RuntimeException(_txt('er.db.save'));
          }
        }
      }
      
      if($mode == 'merge') {
        // Merge values/related models before deleting the duplicate identity
        
        // Pull the OrgIdentities and related data
        $args = array();
        $args['conditions']['EnrolleeOrgIdentity.id'] = $petitionOrgId;
        $args['contain'] = array(
          'Address',
          'EmailAddress',
          'Identifier',
          'Name',
          'TelephoneNumber'
        );
        
        $newOrgId = $this->EnrolleeOrgIdentity->find('first', $args);
        
        $args['conditions']['EnrolleeOrgIdentity.id'] = $targetOrgIdentityId;
        
        $curOrgId = $this->EnrolleeOrgIdentity->find('first', $args);
        
        foreach($args['contain'] as $m) {
          if(!empty($newOrgId[$m])) {
            foreach($newOrgId[$m] as $newm) {
              // Check to see if this record already exists in the target Org Identity.
              // If so, we don't want to copy it.
              $found = false;
              
              foreach($curOrgId[$m] as $curm) {
                $diff = $this->EnrolleeOrgIdentity->$m->compareChanges($m, $newm, $curm);
                
                if(empty($diff)) {
                  // We found a matching record, so don't copy this one over
                  $found = true;
                  break;
                }
              }
              
              if(!$found) {
                // Rekey this record by updating the foreign key
                $this->EnrolleeOrgIdentity->$m->id = $newm['id'];
                $this->EnrolleeOrgIdentity->$m->saveField('org_identity_id', $targetOrgIdentityId);
              
                if($m == 'Name' && $newm['primary_name']) {
                  // Make sure we're not creating a new primary name.
                  // (The existing record should already have one.)
                  $this->EnrolleeOrgIdentity->$m->saveField('primary_name', false);
                }
              }
            }
          }
        }
      }
      
      // Delete the duplicate org identity. This will trigger ChangelogBehavior,
      // so the id will still be valid even after the delete.
      
      try {
        $this->EnrolleeOrgIdentity->delete($petitionOrgId);
        
        // Create some history records and a petition history record
        
        $this->EnrolleeCoPerson->HistoryRecord->record($enrolleeCoPId,
                                                       null,
                                                       $petitionOrgId,
                                                       $actorCoPersonId,
                                                       ActionEnum::CoPersonOrgIdUnlinked);
        
        $this->EnrolleeCoPerson->HistoryRecord->record($enrolleeCoPId,
                                                       null,
                                                       $petitionOrgId,
                                                       $actorCoPersonId,
                                                       ActionEnum::OrgIdDeletedPetition,
                                                       _txt('rs.pt.org.del'));
        
        $this->EnrolleeCoPerson->HistoryRecord->record($enrolleeCoPId,
                                                       null,
                                                       $targetOrgIdentityId,
                                                       $actorCoPersonId,
                                                       ActionEnum::CoPersonOrgIdLinked,
                                                       _txt('rs.pt.relink.org', array($targetOrgIdentityId)));
        
        $this->CoPetitionHistoryRecord->record($id,
                                               $actorCoPersonId,
                                               PetitionActionEnum::IdentityLinked,
                                               _txt('rs.pt.relink.org', array($targetOrgIdentityId)));
      }
      catch(Exception $e) {
        throw new RuntimeException($e->getMessage());
      }
    } else {
      // One or more links to an existing CO Person that's not the enrollee were found.
      // We can't automatically clean up, so throw an error.
      
      $this->CoPetitionHistoryRecord->record($id,
                                             $actorCoPersonId,
                                             PetitionActionEnum::IdentityRelinked,
                                             _txt('er.pt.relink.org'));
      
      throw new RuntimeException(_txt('er.pt.relink.org'));
    }
  }
  
  /**
   * Relink a Petition and the associated CO Person Role to already existing Org Identity and CO Person.
   * This function should be called from within a transaction.
   *
   * @param Integer $id CO Petition ID
   * @param Integer $targetOrgIdentityId Org Identity ID to relink to
   * @param Integer $targetCoPersonId CO Person ID to relink to
   * @param Integer $actorCoPersonId CO Person ID of the person triggering the relink
   * @param EnrollmentDupeModeEnum $mode How to handle duplicate
   * @throws RuntimeException
   * @throws OverflowException
   */
  
  public function relinkRole($id, $targetOrgIdentityId, $targetCoPersonId, $actorCoPersonId, $mode) {
    // This is probably already set, but just in case.
    $this->id = $id;
    
    // Pull the org identity links for the EnrolleeOrgIdentity and make sure
    // it is ONLY attached to the EnrolleeCoPersonId, and vice versa.
    // If so, relink and delete.
    
    $petitionCoPId = $this->field('enrollee_co_person_id');
    $petitionOrgId = $this->field('enrollee_org_identity_id');
    $petitionRoleId = $this->field('enrollee_co_person_role_id');
    $petitionCouId = $this->field('cou_id');
    
    if(!$petitionRoleId) {
      throw new RuntimeException(_txt('er.copr.none'));
    }
    
    $args = array();
    $args['conditions']['CoOrgIdentityLink.org_identity_id'] = $petitionOrgId;
    
    $lnks = $this->EnrolleeOrgIdentity->CoOrgIdentityLink->findForUpdate($args['conditions'],
                                                                         array('id', 'co_person_id'));
    
    if(empty($lnks)
       || (count($lnks)==1
           && $lnks[0]['CoOrgIdentityLink']['co_person_id'] == $petitionCoPId)) {
      // There are no links from the enrollee org identity or there is exactly one link
      // and it is to the enrollee CO Person. Now check the reverse.
      
      $args = array();
      $args['conditions']['CoOrgIdentityLink.co_person_id'] = $petitionCoPId;
      
      $lnks2 = $this->EnrolleeOrgIdentity->CoOrgIdentityLink->findForUpdate($args['conditions'],
                                                                            array('org_identity_id'));
      
      if(empty($lnks2)
         || (count($lnks2)==1
             && $lnks2[0]['CoOrgIdentityLink']['org_identity_id'] == $petitionOrgId)) {
        $copr = null;
        
        if($mode == EnrollmentDupeModeEnum::NewRoleCouCheck
           && $petitionCouId) {
          // If $targetCoPersonId already has an active role in $petitionCouId throw an error
          
          $args = array();
          $args['conditions']['EnrolleeCoPersonRole.co_person_id'] = $targetCoPersonId;
          $args['conditions']['EnrolleeCoPersonRole.cou_id'] = $petitionCouId;
          $args['conditions']['EnrolleeCoPersonRole.status'] = StatusEnum::Active;
          
          $copr = $this->EnrolleeCoPersonRole->findForUpdate($args['conditions'],
                                                             array('id'));
          
          if(!empty($copr)) {
            throw new OverflowException(_txt('er.pt.dupe.cou'));
          }
        }
        
        // Now we're clear to proceed. First, relink the petition.
        
        if(!$this->saveField('enrollee_org_identity_id', $targetOrgIdentityId)
           || !$this->saveField('enrollee_co_person_id', $targetCoPersonId)) {
          throw new RuntimeException(_txt('er.db.save'));
        }
        
        // We don't need to update any CoOrgIdentityLinks because we haven't changed
        // any such association. (We should have been passed target identities that were
        // already linked.)
        
        // Update the role to be attached to the target CO Person ID.
        
        if($petitionRoleId) {
          $this->EnrolleeCoPersonRole->id = $petitionRoleId;
          
          if(!$this->EnrolleeCoPersonRole->saveField('co_person_id', $targetCoPersonId)) {
            throw new RuntimeException(_txt('er.db.save'));
          }
        }
        
        // Delete the duplicate identities. This will trigger ChangelogBehavior,
        // so the relevant ids will still be valid even after the delete.
        
        try {
          $this->EnrolleeOrgIdentity->delete($petitionOrgId);
          $this->EnrolleeCoPerson->delete($petitionCoPId);
          
          // No need to delete the CoOrgIdentityLink since CoPerson cascading
          // delete will catch that.
          
          // Create some history records and a petition history record
          
          $this->EnrolleeCoPerson->HistoryRecord->record($targetCoPersonId,
                                                         $petitionRoleId,
                                                         null,
                                                         $actorCoPersonId,
                                                         ActionEnum::CoPersonRoleRelinked,
                                                         _txt('rs.pt.relink.role', array($targetCoPersonId)));
          
          $this->EnrolleeCoPerson->HistoryRecord->record(null,
                                                         null,
                                                         $petitionOrgId,
                                                         $actorCoPersonId,
                                                         ActionEnum::OrgIdDeletedPetition,
                                                         _txt('rs.pt.org.del'));          
          
          $this->EnrolleeCoPerson->HistoryRecord->record($petitionCoPId,
                                                         null,
                                                         null,
                                                         $actorCoPersonId,
                                                         ActionEnum::CoPersonDeletedPetition,
                                                         _txt('rs.pt.cop.del'));          
          
          $this->CoPetitionHistoryRecord->record($id,
                                                 $actorCoPersonId,
                                                 PetitionActionEnum::IdentityRelinked,
                                                 _txt('rs.pt.relink.role', array($targetCoPersonId)));
        }
        catch(Exception $e) {
          throw new RuntimeException($e->getMessage());
        }
      } else {
        // One or more links to an existing Org Identity that's not the enrollee were found.
        // We can't automatically clean up, so throw an error.
        
        $this->CoPetitionHistoryRecord->record($id,
                                               $actorCoPersonId,
                                               PetitionActionEnum::IdentityLinked,
                                               _txt('er.pt.relink.role.o'));
        
        throw new RuntimeException(_txt('er.pt.relink.role.o'));
      }
    } else {
      // One or more links to an existing CO Person that's not the enrollee were found.
      // We can't automatically clean up, so throw an error.
      
      $this->CoPetitionHistoryRecord->record($id,
                                             $actorCoPersonId,
                                             PetitionActionEnum::IdentityLinked,
                                             _txt('er.pt.relink.role.c'));
      
      throw new RuntimeException(_txt('er.pt.relink.role.c'));
    }
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
  
  public function resend($id, $actorCoPersonId) {
    // We basically hand off to sendConfirmation(), but first unlink any existing invite.
    
    // Petition status must be Pending Confirmation
    
    $this->id = $id;
    
    if($this->field('status') != StatusEnum::PendingConfirmation) {
      throw new InvalidArgumentException(_txt('er.pt.resend.status'));
    }
    
    // Unlink any existing invite
    
    if(!$this->saveField('co_invite_id', null)) {
      throw new RuntimeException(_txt('er.db.save'));
    }
    
    return $this->sendConfirmation($id, $actorCoPersonId);
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
      
      if($this->EnrolleeOrgIdentity->saveAssociated($orgData, array("validate" => false,
                                                                    "atomic" => true,
                                                                    "provision" => false))) {
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
        throw new RuntimeException(_txt('er.db.save-a', array('OrgIdentity')));
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
    }
    
    if(!empty($coData)) {
      if($coPersonId) {
        // A CO Person ID might have been created by a pipeline, or might be
        // pre-existing from a select action
        $coData['EnrolleeCoPerson']['id'] = $coPersonId;
      } else {
        // Insert some initial attributes
        $coData['EnrolleeCoPerson']['co_id'] = $petition['CoPetition']['co_id'];
        $coData['EnrolleeCoPerson']['status'] = StatusEnum::Pending;
      }
      
      // Save the CO Person Data
      
      if($this->EnrolleeCoPerson->saveAssociated($coData, array("validate" => false,
                                                                "atomic" => true,
                                                                "provision" => false))) {
        if(!$coPersonId) {
          $coPersonId = $this->EnrolleeCoPerson->id;
          $createLink = true;
        }
        
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
        throw new RuntimeException(_txt('er.db.save-a', array('CoPerson')));
      }
    }
    
    if(!empty($coRoleData) && !$coPersonRoleId) {
      // Insert some additional attributes
      $coRoleData['EnrolleeCoPersonRole']['co_person_id'] = $coPersonId;
      $coRoleData['EnrolleeCoPersonRole']['status'] = StatusEnum::Pending;
      
      // Set the current timezone for CoPersonRole::afterSave
      $this->EnrolleeCoPersonRole->setTimeZone($this->tz);
      
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
      
      if($this->EnrolleeCoPersonRole->saveAssociated($coRoleData, array("validate" => false,
                                                                        "atomic" => true,
                                                                        "provision" => false))) {
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
        throw new RuntimeException(_txt('er.db.save-a', array('CoPersonRole')));
      }
    }
    
    if($createLink && $orgIdentityId && $coPersonId) {
      // Create a CO Org Identity Link
      
      $coOrgLink = array();
      $coOrgLink['CoOrgIdentityLink']['org_identity_id'] = $orgIdentityId;
      $coOrgLink['CoOrgIdentityLink']['co_person_id'] = $coPersonId;
      
      // CoOrgIdentityLink is not currently provisioner-enabled, but we'll disable
      // provisioning just in case that changes in the future.
      if($this->EnrolleeCoPerson->CoOrgIdentityLink->save($coOrgLink, array("provision" => false))) {
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
        throw new RuntimeException(_txt('er.db.save-a', array('CoOrgIdentityLink')));
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
      throw new RuntimeException(_txt('er.db.save-a', array('CoPetition')));
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
      throw new RuntimeException(_txt('er.db.save-a', array('CoPetitionHistoryRecord')));
    }
    
    // Record agreements to Terms and Conditions, if any
    // check value of first row if it is bigger than 0
    // 0 means that we will not take it into account
    // CoTermsAndConditions should be always present at form even if it does not have a value for security reasons
    if(!empty($requestData['CoTermsAndConditions']) && (!isset($requestData['CoTermsAndConditions'][0]) || $requestData['CoTermsAndConditions'][0] != 0)) {
      $tAndCMode = $this->CoEnrollmentFlow->field(
        't_and_c_mode',
        array('CoEnrollmentFlow.id' => $enrollmentFlowId)
      );

      foreach(array_keys($requestData['CoTermsAndConditions']) as $coTAndCId) {
        try {
          // Currently, T&C is only available via a petition when authn is required.
          // The array value should be the authenticated identifier as set by the view.
          
          $this->Co->CoTermsAndConditions->CoTAndCAgreement->record($coTAndCId,
                                                                    $coPersonId,
                                                                    $coPersonId,
                                                                    $requestData['CoTermsAndConditions'][$coTAndCId],
                                                                    false);
          
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
          throw new RuntimeException(_txt('er.db.save-a', array('CoTermsAndConditions')));
        }
      }
    }

    if($createLink && $coPersonId) {
      // If we created a new CO Person, check to see if we also created any
      // Org Identities via Org Identity (Enrollment) Sources. If so, create
      // links to those as well.
      
      $args = array();
      $args['conditions']['OrgIdentitySourceRecord.co_petition_id'] = $id;
      $args['fields'] = array('id', 'org_identity_id');
      $args['contain'] = false;
      
      $pOrgIds = $this->EnrolleeOrgIdentity
                      ->OrgIdentitySourceRecord
                      ->find('list', $args);
      
      foreach($pOrgIds as $pid => $porgid) {
        // Create a CO Org Identity Link, if we haven't already
        
        if($porgid == $orgIdentityId) {
          continue;
        }
        
        $coOrgLink = array();
        $coOrgLink['CoOrgIdentityLink']['org_identity_id'] = $porgid;
        $coOrgLink['CoOrgIdentityLink']['co_person_id'] = $coPersonId;
        
        // CoOrgIdentityLink is not currently provisioner-enabled, but we'll disable
        // provisioning just in case that changes in the future.
        
        $this->EnrolleeCoPerson->CoOrgIdentityLink->clear();
        
        if($this->EnrolleeCoPerson->CoOrgIdentityLink->save($coOrgLink, array("provision" => false))) {
          // Create a history record
          try {
            $this->EnrolleeCoPerson->HistoryRecord->record($coPersonId,
                                                           $coPersonRoleId,
                                                           $porgid,
                                                           $petitionerId,
                                                           ActionEnum::CoPersonOrgIdLinked);
          }
          catch(Exception $e) {
            $dbc->rollback();
            throw new RuntimeException($e->getMessage());
          }
        } else {
          $dbc->rollback();
          throw new RuntimeException(_txt('er.db.save-a', array('CoOrgIdentityLink')));
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
   * Send enrollee approval (or denial or finalization) notification for a Petition.
   * - postcondition: Notification sent
   *
   * @since  COmanage Registry v0.9.4
   * @param  Integer $id CO Petition ID
   * @param  Integer $actorCoPersonId CO Person ID of actor sending the notification
   * @param  String $action 'approval' (includes denial) or 'finalize'
   * @return True on success
   * @throws InvalidArgumentException
   */
  
  public function sendApprovalNotification($id, $actorCoPersonId, $action='approval') {
    // First we need some info from the petition and enrollment flow
    
    $args = array();
    $args['conditions']['CoPetition.id'] = $id;
/*    $args['contain']['CoEnrollmentFlow'] = array(
      'CoEnrollmentFlowAppMessageTemplate',
      'CoEnrollmentFlowDenMessageTemplate',
      'CoEnrollmentFlowFinMessageTemplate'
    );*/
    $args['contain']['EnrolleeCoPerson'] = array('PrimaryName', 'Identifier');
    $args['contain']['EnrolleeCoPerson']['CoPersonRole'][] = 'Cou';
    $args['contain']['EnrolleeCoPerson']['CoPersonRole']['SponsorCoPerson'][] = 'PrimaryName';
    $args['contain']['EnrolleeOrgIdentity'] = array('EmailAddress', 'PrimaryName');
    
    $pt = $this->find('first', $args);
    
    if(!$pt) {
      throw new InvalidArgumentException(_txt('er.notfound', array(_txt('ct.co_petitions.1'), $id)));
    }
    
    // For some reason (cough, cake, cough) the commented out contain isn't pulling
    // the related models since the addition of CoEnrollmentFlowDenMessageTemplate,
    // so we manually pull the Enrollment Flow configuration and templates.
    
    $args = array();
    $args['conditions']['CoEnrollmentFlow.id'] = $pt['CoPetition']['co_enrollment_flow_id'];
    $args['contain'] = array(
      'CoEnrollmentFlowAppMessageTemplate',
      'CoEnrollmentFlowDenMessageTemplate',
      'CoEnrollmentFlowFinMessageTemplate'
    );
    
    $ef = $this->CoEnrollmentFlow->find('first', $args);
    
    if(!$ef) {
      throw new InvalidArgumentException(_txt('er.notfound', array(_txt('ct.co_enrollment_flows.1'), $pt['CoPetition']['co_enrollment_flow_id'])));
    }
    
    if(isset($ef['CoEnrollmentFlow']['notify_on_' . $action])
       && $ef['CoEnrollmentFlow']['notify_on_' . $action]) {
      // As of v2.0.0, this uses the notification infrastructure instead of its own
      // email code. A side effect is that all new users will have one notification
      // pending acknowledgment when they login... it might be better for it to
      // automatically expire shortly after being sent (CO-852).
      
      $enrolleeName = "?";
      
      if(!empty($pt['EnrolleeCoPerson']['PrimaryName'])) {
        $enrolleeName = generateCn($pt['EnrolleeCoPerson']['PrimaryName']);
      } elseif(!empty($pt['EnrolleeOrgIdentity']['PrimaryName'])) {
        $enrolleeName = generateCn($pt['EnrolleeOrgIdentity']['PrimaryName']);
      }
      
      // Pull the message components from the template (as of v2.0.0) or configuration
      // (now deprecated), if either is set. (Finalize only supports templates.)
      
      $subject = null;
      $body = null;
      $cc = null;
      $bcc = null;
      $comment = null;
      $format = MessageFormatEnum::Plaintext;
      
      $subs = array(
        'APPROVER_COMMENT' => (!empty($pt['CoPetition']['approver_comment'])
                               ? $pt['CoPetition']['approver_comment'] : null),
        'CO_PERSON' => generateCn($pt['EnrolleeCoPerson']['PrimaryName']),
        'NEW_COU'   => (!empty($pt['EnrolleeCoPerson']['CoPersonRole'][0]['Cou']['name'])
                        ? $pt['EnrolleeCoPerson']['CoPersonRole'][0]['Cou']['name'] : null),
        'SPONSOR'   => (!empty($pt['EnrolleeCoPerson']['CoPersonRole'][0]['SponsorCoPerson']['PrimaryName'])
                        ? generateCn($pt['EnrolleeCoPerson']['CoPersonRole'][0]['SponsorCoPerson']['PrimaryName']) : null)
      );
      
      // Create substitution rules for any defined identifiers.
      // Note if multiple identifiers of a given type are found,
      // we'll concatenate them.
      
      if($action == 'approval') {
        // As of Registry v3.3.0, we also support notification on denial. To figure
        // out which template to use, we check the petition status
        
        if($pt['CoPetition']['status'] == PetitionStatusEnum::Denied) {
          if(!empty($ef['CoEnrollmentFlowDenMessageTemplate']['id'])) {
            // Deny
            list($body, $subject, $format, $cc, $bcc) = $this->CoEnrollmentFlow
                                                             ->CoEnrollmentFlowDenMessageTemplate
                                                             ->getMessageTemplateFields($ef['CoEnrollmentFlowDenMessageTemplate']);

          } else {
            // No template, nothing to do
            return true;
          }
        } else {
          if(!empty($ef['CoEnrollmentFlowAppMessageTemplate']['id'])) {
            // Approve
            list($body, $subject, $format, $cc, $bcc) = $this->CoEnrollmentFlow
                                                             ->CoEnrollmentFlowAppMessageTemplate
                                                             ->getMessageTemplateFields($ef['CoEnrollmentFlowAppMessageTemplate']);
          } else {
            if(!empty($ef['CoEnrollmentFlow']['approval_subject'])) {
              $subject = $ef['CoEnrollmentFlow']['approval_subject'];
            }
            
            if(!empty($ef['CoEnrollmentFlow']['approval_body'])) {
              $body = $ef['CoEnrollmentFlow']['approval_body'];
            }
          }
        }
        
        $comment = _txt('rs.pt.status', array($enrolleeName,
                                              _txt('en.status.pt', null, PetitionStatusEnum::PendingApproval),
                                              _txt('en.status.pt', null, $pt['CoPetition']['status']),
                                              $ef['CoEnrollmentFlow']['name']));
      } else {
        if(!empty($ef['CoEnrollmentFlowFinMessageTemplate']['id'])) {
          // Finalize
          list($body, $subject, $format, $cc, $bcc) = $this->CoEnrollmentFlow
                                                           ->CoEnrollmentFlowFinMessageTemplate
                                                           ->getMessageTemplateFields($ef['CoEnrollmentFlowFinMessageTemplate']);
          $comment = _txt('rs.pt.final');
        } else {
          // No template, nothing to do
          return true;
        }
      }
      
      $subject = processTemplate($subject, $subs, $pt['EnrolleeCoPerson']['Identifier']);
      $body = processTemplate($body, $subs, $pt['EnrolleeCoPerson']['Identifier']);
      
      $this->Co
           ->CoPerson
           ->CoNotificationRecipient
           ->register($pt['CoPetition']['enrollee_co_person_id'],
                      null,
                      $actorCoPersonId,
                      'coperson',
                      $pt['CoPetition']['enrollee_co_person_id'],
                      ActionEnum::CoPetitionUpdated,
                      $comment,
                      array(
                        'controller' => 'co_petitions',
                        'action'     => 'view',
                        'id'         => $id
                      ),
                      false,
                      $ef['CoEnrollmentFlow']['notify_from'],
                      $subject,
                      $body,
                      $cc,
                      $bcc,
                      $format);
      
      // And cut a history record
      
      $this->CoPetitionHistoryRecord->record($id,
                                             $actorCoPersonId,
                                             PetitionActionEnum::NotificationSent,
                                             _txt('rs.nt.sent', array($enrolleeName)));
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
    $args['contain']['EnrolleeCoPerson'][] = 'PrimaryName';
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
      
      if(!empty($pt['Cou']['id'])) {
        try {
          $cogroupids[] = $this->Co->CoGroup->adminCoGroupId($pt['CoPetition']['co_id'], $pt['Cou']['id']);
        }
        catch(Exception $e) {
          $fail = true;
        }
      }
    }
    
    // Now that we have a list of groups, register the notifications
    // -- we don't fail on notification failures
    
    $enrolleeName = "?";
    
    if(!empty($pt['EnrolleeCoPerson']['PrimaryName'])) {
      $enrolleeName = generateCn($pt['EnrolleeCoPerson']['PrimaryName']);
    } elseif(!empty($pt['EnrolleeOrgIdentity']['PrimaryName'])) {
      $enrolleeName = generateCn($pt['EnrolleeOrgIdentity']['PrimaryName']);
    }
    
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
                      _txt('rs.pt.status', array($enrolleeName,
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
    $args['contain']['EnrolleeCoPerson'] = array(
      'EmailAddress',
      'PrimaryName'
    );
    $args['contain']['EnrolleeCoPerson']['CoPersonRole'][] = 'Cou';
    $args['contain']['EnrolleeCoPerson']['CoPersonRole']['SponsorCoPerson'][] = 'PrimaryName';
    $args['contain']['EnrolleeOrgIdentity'] = array(
      'EmailAddress',
      'OrgIdentitySourceRecord',
      'PrimaryName'
    );
    
    $pt = $this->find('first', $args);
    
    if(empty($pt)) {
      throw new InvalidArgumentException(_txt('er.notfound', array(_txt('ct.co_petitions.1'), $id)));
    }
    
    // Look for an email address to confirm. Pending the big email confirmation
    // rewrite currently scheduled for v4.0.0, we first look for an address
    // associated with the Org Identity, and then for an address associated with
    // the CO Person. We skip already verified addresses, as well as those
    // associated with Org Identity Sources (since we can't update those
    // Org Identities).
    
    $toEmail = null;
    
    if(!empty($pt['EnrolleeOrgIdentity']['EmailAddress'])
       // If there's an OrgIdentitySourceRecord we can't write to any 
       // associated EmailAddress, so skip this OrgIdentity
       && empty($pt['EnrolleeOrgIdentity']['OrgIdentitySourceRecord'])) {
      foreach($pt['EnrolleeOrgIdentity']['EmailAddress'] as $ea) {
        if(!$ea['verified']) {
          // Use this address
          $toEmail = $ea;
          break;
        }
      }
    }
    
    if(!$toEmail) {
      // No Org Identity Email, check the CO Person record
      
      if(!empty($pt['EnrolleeCoPerson']['EmailAddress'])) {
        foreach($pt['EnrolleeCoPerson']['EmailAddress'] as $ea) {
          if(!$ea['verified']) {
            // Use this address
            $toEmail = $ea;
            break;
          }
        }
      }
    }
    
    if(!$toEmail) {
      throw new RuntimeException(_txt('er.pt.mail',
                                      array(generateCn($pt['EnrolleeCoPerson']['PrimaryName']))));
    }
    
    // Now we need some info from the enrollment flow
    
    $args = array();
    $args['conditions']['CoEnrollmentFlow.id'] = $pt['CoPetition']['co_enrollment_flow_id'];
    $args['contain'] = array('Co', 'CoEnrollmentFlowVerMessageTemplate');
    
    $ef = $this->CoEnrollmentFlow->find('first', $args);
    
    if(!$ef) {
      throw new InvalidArgumentException(_txt('er.notfound', array(_txt('ct.co_enrollment_flows.1'),
                                                                   $pt['CoPetition']['co_enrollment_flow_id'])));
    }
    
    // Pull the message components from the template (as of v2.0.0) or configuration
    // (now deprecated), if either is set.
    
    $subject = null;
    $body = null;
    $cc = null;
    $bcc = null;
    $format = MessageFormatEnum::Plaintext;

    // Generate additional substitutions to supplement those handled by CoInvites.
    // This is separate from the substitutions managed by CoNotification.
    $subs = array(
      'CO_PERSON' => (!empty($pt['EnrolleeCoPerson']['PrimaryName'])
                      ? generateCn($pt['EnrolleeCoPerson']['PrimaryName']) : _txt('fd.enrollee.new')),
      'NEW_COU'   => (!empty($pt['EnrolleeCoPerson']['CoPersonRole'][0]['Cou']['name'])
                      ? $pt['EnrolleeCoPerson']['CoPersonRole'][0]['Cou']['name'] : null),
      'SPONSOR'   => (!empty($pt['EnrolleeCoPerson']['CoPersonRole'][0]['SponsorCoPerson']['PrimaryName'])
                      ? generateCn($pt['EnrolleeCoPerson']['CoPersonRole'][0]['SponsorCoPerson']['PrimaryName']) : null)
    );
    
    if(!empty($ef['CoEnrollmentFlowVerMessageTemplate']['id'])) {
      // Verification Email
      list($body, $subject, $format, $cc, $bcc) = $this->CoEnrollmentFlow
                                                       ->CoEnrollmentFlowVerMessageTemplate
                                                       ->getMessageTemplateFields($ef['CoEnrollmentFlowVerMessageTemplate']);
    } else {
      if(!empty($ef['CoEnrollmentFlow']['verification_subject'])) {
        $subject = $ef['CoEnrollmentFlow']['verification_subject'];
      }
      
      if(!empty($ef['CoEnrollmentFlow']['verification_body'])) {
        $body = $ef['CoEnrollmentFlow']['verification_body'];
      }
    }
    
    // We can now send the invitation
    $coInviteId = $this->CoInvite->send($pt['CoPetition']['enrollee_co_person_id'],
                                        $pt['CoPetition']['enrollee_org_identity_id'],
                                        $actorCoPersonId,
                                        $toEmail['mail'],
                                        $ef['CoEnrollmentFlow']['notify_from'],
                                        $ef['Co']['name'],
                                        $subject,
                                        $body,
                                        null,
                                        $ef['CoEnrollmentFlow']['invitation_validity'],
                                        $cc,
                                        $bcc,
                                        $subs,
                                        $format);
    
    // Add the invite ID to the petition record
    
    $this->id = $id;
    $this->saveField('co_invite_id', $coInviteId);
    
    // And add a petition history record
    
    try {
      $this->CoPetitionHistoryRecord->record($id,
                                             $actorCoPersonId,
                                             PetitionActionEnum::InviteSent,
                                             _txt('rs.inv.sent', array($toEmail['mail'])));
    }
    catch(Exception $e) {
      $dbc->rollback();
      throw new RuntimeException(_txt('er.db.save-a', array('CoPetitionHistoryRecord')));
    }
    
    return $toEmail['mail'];
  }
  
  /**
   * Update the status of a CO Petition.
   * - precondition: The Petition must be in a state suitable for the desired new status.
   * - postcondition: The new status may be altered according to the enrollment configuration.
   *
   * @since  COmanage Registry v0.5
   * @param  integer    $id              CO Petition ID
   * @param  StatusEnum $newStatus       Target status
   * @param  integer    $actorCoPersonID CO Person ID of person causing update
   * @param  string     $comment         For supported status changes, the actor's comment
   * @throws InvalidArgumentException
   * @throws RuntimeException
   */
  
  public function updateStatus($id, $newStatus, $actorCoPersonID, $comment="") {
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
    
    if($newStatus == PetitionStatusEnum::Confirmed
       && $curStatus != StatusEnum::PendingConfirmation) {
      // A Petition can only go to Confirmed if it was previously PendingConfirmation
      throw new InvalidArgumentException(_txt('er.pt.status', array($curStatus, $newStatus)));
    }
    
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
    } elseif($newStatus == PetitionStatusEnum::Finalized) {
      // On finalization, set the CO Person and CO Person Role to Active.
      
      $valid = true;
      $newPetitionStatus = PetitionStatusEnum::Finalized;
      $newCoPersonStatus = StatusEnum::Active;
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
            throw new RuntimeException(_txt('er.db.save-a', array('CoPetition::updateStatus')));
          }
          // And save the approver's comment, if set
          if($comment != ""
             && !$this->saveField('approver_comment', $comment)) {
               throw new RuntimeException(_txt('er.db.save-a', array('CoPetition::updateStatus')));
          }
        }
        
        // Write a Petition History Record
        
        if(!$fail) {
          $petitionAction = null;
          $hComment = null;
          
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
              $hComment = _txt('rs.pt.status.h', array(_txt('en.status.pt', null, $curStatus),
                                                      _txt('en.status.pt', null, $newPetitionStatus)));
              break;
          }
          
          if($petitionAction) {
            if($comment != "" && $hComment == null) {
              $hComment = _txt('rs.pt.status.c', array(_txt('en.action.petition', null, $petitionAction), $comment));
            }
            
            try {
              $this->CoPetitionHistoryRecord->record($id,
                                                     $actorCoPersonID,
                                                     $petitionAction,
                                                     $hComment);
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
          
          // This will also trigger recalculation of overall CO Person status
          $this->EnrolleeCoPersonRole->saveField('status', $newCoPersonStatus, array('provision' => false));
          
          try {
            // Create a history record
            $this->EnrolleeCoPersonRole->HistoryRecord->record($coPersonID,
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
   * Org Identity. Duplicate Org Identities may also be consolidated.
   * - postcondition: Identifier attached to Org Identity
   *
   * @since  COmanage Registry v0.7
   * @param  Integer CO Petition ID
   * @param  String Login Identifier
   * @param  Integer Actor CO Person ID
   * @throws InvalidArgumentException
   * @throws OverflowException
   * @throws RuntimeException
   */
  
  public function validateIdentifier($id, $loginIdentifier, $actorCoPersonId) {
    // Find the enrollment flow associated with this petition to determine some configuration parameters.
    // It's arguable that we should pass certain actions back to the controller ta handle, such as
    // handling duplicates, but for now it's easier to handle this all in one transaction.
    // Perhaps some refactoring may make sense in the future, though.
    
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
        // Start a transaction
        $dbc = $this->getDataSource();
        $dbc->begin();
        
        // For now, we assume the identifier type is ePPN. This probably isn't right,
        // and should be customizable. (CO-460)
        
        $args = array();
        $args['conditions']['Identifier.identifier'] = $loginIdentifier;
        $args['conditions']['Identifier.org_identity_id'] = $orgId;
        $args['conditions']['Identifier.type'] = IdentifierEnum::ePPN;
        
        $identifier = $this->EnrolleeOrgIdentity->Identifier->findForUpdate($args['conditions'],
                                                                            array('Identifier.login',
                                                                                  'Identifier.id'));
        
        if(!empty($identifier[0]['Identifier'])) {
          // The authenticated identifier is already associated with the enrollee org identity
          
          // Make sure login flag is set
          
          if(!$identifier[0]['Identifier']['login']) {
            $this->EnrolleeOrgIdentity->Identifier->id = $identifier[0]['Identifier']['id'];
            
            if(!$this->EnrolleeOrgIdentity->Identifier->saveField('login', true, array('provision' => false))) {
              $dbc->rollback();
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
              $dbc->rollback();
              throw new RuntimeException($e->getMessage());
            }
          }
        } else {
          // Add the identifier and update petition and org identity history, but first check
          // to see if it's already in use.
          
          $args = array();
          $args['conditions'][] = 'Identifier.org_identity_id IS NOT NULL';
          $args['conditions']['Identifier.identifier'] = $loginIdentifier;
          $args['conditions']['Identifier.type'] = IdentifierEnum::ePPN;
          $args['conditions']['Identifier.status'] = StatusEnum::Active;
          $args['joins'] = array();
          
          $CmpEnrollmentConfiguration = ClassRegistry::init('CmpEnrollmentConfiguration');
          
          if(!$CmpEnrollmentConfiguration->orgIdentitiesPooled()) {
            // If org identities are not pooled, we need to join against org identity
            // to filter on CO
            
            $args['joins'][0]['table'] = 'org_identities';
            $args['joins'][0]['alias'] = 'OrgIdentity';
            $args['joins'][0]['type'] = 'INNER';
            $args['joins'][0]['conditions'][0] = 'Identifier.org_identity_id=OrgIdentity.id';
            $args['conditions']['OrgIdentity.co_id'] = $this->field('co_id');
          }
          
          $identifier2 = $this->EnrolleeOrgIdentity->Identifier->findForUpdate($args['conditions'],
                                                                               array('Identifier.identifier',
                                                                                     'Identifier.org_identity_id'),
                                                                               $args['joins']);
          
          if(!empty($identifier2)) {
            // We have an org identity attached to this identifier. See if there are
            // any links to CO People in the current CO.
            
            $args = array();
            $args['conditions']['CoOrgIdentityLink.org_identity_id'] = $identifier2[0]['Identifier']['org_identity_id'];
            $args['conditions']['CoPerson.co_id'] = $this->field('co_id');
            $args['joins'][0]['table'] = 'co_people';
            $args['joins'][0]['alias'] = 'CoPerson';
            $args['joins'][0]['type'] = 'INNER';
            $args['joins'][0]['conditions'][0] = 'CoOrgIdentityLink.co_person_id=CoPerson.id';
            
            $cop = $this->EnrolleeOrgIdentity->CoOrgIdentityLink->findForUpdate($args['conditions'],
                                                                                array('co_person_id'),
                                                                                $args['joins']);
            
            if(!empty($cop)) {
              // Duplicate: There is already a CO Person with this identifier in this CO.
              
              if(!isset($enrollmentFlow['CoEnrollmentFlow']['duplicate_mode'])
                 || $enrollmentFlow['CoEnrollmentFlow']['duplicate_mode'] == EnrollmentDupeModeEnum::Duplicate) {
                // Flag this petition and its associated identity as a duplicate
                
                // We want to flag as duplicate and commit, but then throw an exception
                // back up the stack so an error can be rendered for the user
                
                try {
                  $this->updateStatus($id,
                                      StatusEnum::Duplicate,
                                      $actorCoPersonId);
                  
                  // While we're here, grab the authenticated identifier, which would otherwise
                  // be done below
                  
                  $this->saveField('authenticated_identifier', $loginIdentifier);
                  
                  // Create a petition history record
                  
                  $this->CoPetitionHistoryRecord->record($id,
                                                         $actorCoPersonId,
                                                         PetitionActionEnum::IdentifierAuthenticated,
                                                         _txt('rs.pt.id.auth', array($loginIdentifier)));
                }
                catch(Exception $e) {
                  $dbc->rollback();
                  throw new RuntimeException($e->getMessage());
                }
                
                $dbc->commit();
                throw new OverflowException(_txt('er.pt.duplicate', array($loginIdentifier)));
              } else {
                // Maybe merge...
                
                try {
                  if($enrollmentFlow['CoEnrollmentFlow']['duplicate_mode'] == EnrollmentDupeModeEnum::Merge) {
                    // relinkOrgIdentity will delete the extra org identity
                    $this->relinkOrgIdentity($id,
                                             $identifier2[0]['Identifier']['org_identity_id'],
                                             $actorCoPersonId,
                                             'merge');
                  } else {
                    // relinkRole() will delete extra co person and org identity
                    $this->relinkRole($id,
                                      $identifier2[0]['Identifier']['org_identity_id'],
                                      $cop[0]['CoOrgIdentityLink']['co_person_id'],
                                      $actorCoPersonId,
                                      $enrollmentFlow['CoEnrollmentFlow']['duplicate_mode']);
                  }
                }
                catch(OverflowException $e) {
                  // Mode is NewRoleCouCheck and an existing role in the same COU was found.
                  // Convert to duplicate.
                  
                  $this->updateStatus($id,
                                      StatusEnum::Duplicate,
                                      $actorCoPersonId);
                  
                  // While we're here, grab the authenticated identifier, which would otherwise
                  // be done below
                  
                  $this->saveField('authenticated_identifier', $loginIdentifier);
                  
                  // Create a petition history record
                  
                  $this->CoPetitionHistoryRecord->record($id,
                                                         $actorCoPersonId,
                                                         PetitionActionEnum::IdentifierAuthenticated,
                                                         _txt('rs.pt.id.auth', array($loginIdentifier)));
                  
                  $dbc->commit();
                  throw new OverflowException(_txt('er.pt.duplicate', array($loginIdentifier)));
                }
                catch(Exception $e) {
                  $dbc->rollback();
                  throw new RuntimeException($e->getMessage());
                }
              }
            } else {
              // Merge: There is no CO Person with this identifier, so relink the petition
              // and the enrollee co person to the org identity we found and clean up the
              // one created as part of the petition.
              
              try {
                $this->relinkOrgIdentity($id,
                                         $identifier2[0]['Identifier']['org_identity_id'],
                                         $actorCoPersonId);
              }
              catch(Exception $e) {
                $dbc->rollback();
                
                // An Exception may also indicate manual intervention required, but we
                // don't actually handle it separately from a coding standpoint... we just
                // want the error message to get rendered.
                
                throw new RuntimeException($e->getMessage());
              }
            }
          } else {
            // Identifier not found, so attach it to the enrollee org identity
            
            $identifier3 = array();
            $identifier3['Identifier']['identifier'] = $loginIdentifier;
            $identifier3['Identifier']['org_identity_id'] = $orgId;
            $identifier3['Identifier']['type'] = IdentifierEnum::ePPN;
            $identifier3['Identifier']['login'] = true;
            $identifier3['Identifier']['status'] = StatusEnum::Active;
            
            if(!$this->EnrolleeOrgIdentity->Identifier->save($identifier3, array('provision' => false))) {
              $dbc->rollback();
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
              $dbc->rollback();
              throw new RuntimeException($e->getMessage());
            }
          }
        }
        
        // Store the authenticated identifier in the petition. We don't do this as
        // a foreign key because (1) there is no corresponding co_enrollment_attribute
        // and (2) the identifier might subsequently be deleted from the identifiers table.
        // (Though with changelog behavior #2 is no longer an issue.)
        
        try {
          $this->saveField('authenticated_identifier', $loginIdentifier);
        }
        catch(Exception $e) {
          $dbc->rollback();
          throw new RuntimeException($e->getMessage());
        }
        
        // Create a petition history record
        
        $this->CoPetitionHistoryRecord->record($id,
                                               $actorCoPersonId,
                                               PetitionActionEnum::IdentifierAuthenticated,
                                               _txt('rs.pt.id.auth', array($loginIdentifier)));
        
        $dbc->commit();
      } else {
        throw new InvalidArgumentException(_txt('er.notprov.id', array(_txt('ct.org_identities.1'))));
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
      
      // Manually validate data
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
                    // Use model's existing message if there is one
                    if(empty($xfield->getRule('content')->message)) {
                      $xfield->getRule('content')->message = _txt('er.field.req');
                    }
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
  
  /**
   * Validate a token associated with a Petition
   *
   * @since  COmanage Registry v3.3.0
   * @param  Integer $id    CO Petition ID
   * @param  Integer $token Token to verify
   * @param  PetitionStatusEnum $petitionStatus If provided, require the petition to be in this status
   * @return string         "petitioner" or "enrollee", according to the token validated
   * @throws InvalidArgumentException
   * @throws RuntimeException
   * @todo   Update other token validation code to use this function
   */

  public function validateToken($id, $token, $petitionStatus=null) {
    // First pull the petition record
    
    $args = array();
    $args['conditions']['CoPetition.id'] = $id;
    $args['contain'] = false;
    
    $pt = $this->find('first', $args);
    
    if(!$pt) {
      throw new InvalidArgumentException(_txt('er.notfound', array(_txt('ct.co_petitions.1'), $id)));
    }
    
    // If requested, check the status of the petition
    if($petitionStatus && $pt['CoPetition']['status'] != $petitionStatus) {
      throw new RuntimeException(_txt('er.status.not', array($petitionStatus)));
    }
    
    if($token == $pt['CoPetition']['petitioner_token']) {
      return 'petitioner';
    }
    
    if($token == $pt['CoPetition']['enrollee_token']) {
      return 'enrollee';
    }
    
    throw new InvalidArgumentException(_txt('er.token'));
  }
}
