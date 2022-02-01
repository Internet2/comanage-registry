<?php
/**
 * COmanage Registry CO Pipeline Model
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
 * @since         COmanage Registry v2.0.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

class CoPipeline extends AppModel {
  // Define class name for cake
  public $name = "CoPipeline";
  
  // Current schema version for API
  public $version = "1.0";
  
  // Association rules from this model to other models
  public $belongsTo = array(
    "Co",
    "CoEnrollmentFlow",
    "MatchServer" => array(
      'className'  => 'Server',
      'foreignKey' => 'match_server_id'
    ),
    "SyncCou" => array(
      'className' => 'Cou',
      'foreignKey'=>'sync_cou_id'
    ),
    "ReplaceCou" => array(
      'className' => 'Cou',
      'foreignKey'=>'sync_replace_cou_id'
    )
  );
  
  public $hasMany = array(
    'CoSetting' => array(
      'foreignKey' => 'default_co_pipeline_id'
    ),
    'OrgIdentitySource'
  );
  
  // Default display field for cake generated views
  public $displayField = "name";
  
  public $actsAs = array('Containable',
                         'Changelog' => array('priority' => 5));
  
  // Validation rules for table elements
  public $validate = array(
    'co_id' => array(
      'rule' => 'numeric',
      'required' => true,
      'allowEmpty' => false,
      'message' => 'A CO ID must be provided'
    ),
    'name' => array(
      'rule' => array('validateInput'),
      'required' => true,
      'allowEmpty' => false
    ),
    'status' => array(
      'rule' => array('inList', array(SuspendableStatusEnum::Active,
                                      SuspendableStatusEnum::Suspended)),
      'required' => true,
      'message' => 'A valid status must be selected'
    ),
    'match_strategy' => array(
      'rule' => array('inList', array(MatchStrategyEnum::EmailAddress,
                                      MatchStrategyEnum::External, 
                                      MatchStrategyEnum::Identifier,
                                      MatchStrategyEnum::NoMatching)),
      'required'   => true,
      'allowEmpty' => false
    ),
    'match_type' => array(
      // We should really use validateExtendedType, but it's a bit tricky since
      // it's dependent on match_strategy. We'd need a new custom validation rule.
      'rule' => array('maxLength', 32),
      // Required only when match_strategy = Identifier
      'required'   => false,
      'allowEmpty' => true
    ),
    'sync_on_add' => array(
      'rule'       => 'boolean',
      'required'   => false,
      'allowEmpty' => true
    ),
    'sync_on_update' => array(
      'rule'       => 'boolean',
      'required'   => false,
      'allowEmpty' => true
    ),
    'sync_on_delete' => array(
      'rule'       => 'boolean',
      'required'   => false,
      'allowEmpty' => true
    ),
    'sync_coperson_status' => array(
      'rule' => array('inList', array(StatusEnum::Active,
                                      StatusEnum::Approved,
                                      StatusEnum::Confirmed,
                                      StatusEnum::Declined,
                                      StatusEnum::Deleted,
                                      StatusEnum::Denied,
                                      StatusEnum::Duplicate,
                                      StatusEnum::Expired,
                                      StatusEnum::GracePeriod,
                                      StatusEnum::Invited,
                                      StatusEnum::Pending,
                                      StatusEnum::PendingApproval,
                                      StatusEnum::PendingConfirmation,
                                      StatusEnum::Suspended)),
      'required' => false,
      'allowEmpty' => true
    ),
    'create_role' => array(
      'rule'       => 'boolean',
      'required'   => false,
      'allowEmpty' => true
    ),
    'sync_cou_id' => array(
      'content' => array(
        'rule' => 'numeric',
        'required' => false,
        'allowEmpty' => true,
        'unfreeze' => 'CO'
      )
    ),
    'sync_replace_cou_id' => array(
      'content' => array(
        'rule' => 'numeric',
        'required' => false,
        'allowEmpty' => true,
        'unfreeze' => 'CO'
      )
    ),
    'sync_status_on_delete' => array(
      'rule' => array('inList', array(StatusEnum::Deleted,
                                      StatusEnum::Expired,
                                      StatusEnum::GracePeriod,
                                      StatusEnum::Suspended)),
      'required'   => false,
      'allowEmpty' => true
    ),
    'sync_identifier_type' => array(
      'content' => array(
        'rule' => array('validateInput'),
        'required' => false,
        'allowEmpty' => true
      )
    ),
    'co_enrollment_flow_id' => array(
      'content' => array(
        'rule' => 'numeric',
        'required' => false,
        'allowEmpty' => true,
        'unfreeze' => 'CO'
      )
    )
  );
  
  /**
   * Create a Petition using the specified Enrollment Flow.
   *
   * @since  COmanage Registry v3.3.0
   * @param  int $enrollmentFlowId CO Enrollment Flow ID to create the Petition in
   * @param  int $orgIdentityId    Org Identity ID to attach as Enrollee Org Identity
   * @param  int $coPersonId       CO Person ID to attach as Enrollee CO Person
   * @throws InvalidArgumentException
   */
  
  public function createPetition($enrollmentFlowId, $orgIdentityId, $coPersonId) {
    // Pull the CO ID from the CO Person ID
    $coId = $this->Co->CoPerson->field('co_id', array('CoPerson.id' => $coPersonId));
    
    if(!$coId) {
      throw new InvalidArgumentException(_txt('er.notfound',
                                              array(_txt('ct.co_people.1', $coPersonId))));
    }
    
    $status = $this->Co->CoEnrollmentFlow->field('status', array('CoEnrollmentFlow.id' => $enrollmentFlowId));
    
    if($status != TemplateableStatusEnum::Active) {
      throw new InvalidArgumentException(_txt('er.status.not', array(_txt('en.status.temp', null, TemplateableStatusEnum::Active))));
    }
    
    // Initialize the petition
    $coPetitionId = $this->Co->CoPetition->initialize($enrollmentFlowId,
                                                      $coId,
                                                      null,
                                                      null);
    
    $this->Co->CoPetition->linkOrgIdentity($enrollmentFlowId, $coPetitionId, $orgIdentityId, null);
    
    $this->Co->CoPetition->linkCoPerson($enrollmentFlowId, $coPetitionId, $coPersonId, null);

    // Trigger the email confirmation

    $this->Co->CoPetition->sendConfirmation($coPetitionId, null);

    $this->Co->CoPetition->updateStatus($coPetitionId,
                                        PetitionStatusEnum::PendingConfirmation,
                                        null);
  }
  
  /**
   * Execute a CO Pipeline. Note: This function should be called from within
   * a transaction.
   *
   * @since  COmanage Registry v2.0.0
   * @param  Integer $id CO Pipeline to execute
   * @param  Integer $orgIdentityId Source Org Identity to run
   * @param  SyncActionEnum $syncAction Add, Update, or Delete
   * @param  Integer $actorCoPersonId CO Person ID of actor, if interactive
   * @param  Boolean $provision Whether to execute provisioning
   * @param  String  $oisRawRecord If the Org Identity came from an Org Identity Source, the raw record
   * @param  Integer $oisRecordId  If the Org Identity came from an Org Identity Source, the OIS Record ID
   * @return Boolean True on success
   * @throws InvalidArgumentException
   */
  
  public function execute($id, $orgIdentityId, $syncAction, $actorCoPersonId=null, $provision=true, $oisRawRecord=null, $oisRecordId=null) {
    // Make sure we have a valid action
    
    if(!in_array($syncAction, array(SyncActionEnum::Add,
                                    SyncActionEnum::Delete,
                                    SyncActionEnum::Relink,
                                    SyncActionEnum::Unlink,
                                    SyncActionEnum::Update))) {
      throw new InvalidArgumentException(_txt('er.unknown',
                                              array(filter_var($syncAction,
                                                FILTER_SANITIZE_STRING,FILTER_FLAG_STRIP_HIGH |
                                                FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_BACKTICK)))); /* was Cake's Sanitize::paranoid */
    }
    
    // And that $orgIdentityId is in the CO.
    
    $args = array();
    $args['conditions']['OrgIdentity.id'] = $orgIdentityId;
    $args['contain'] = false;
    
    $orgIdentity = $this->Co->OrgIdentity->find('first', $args);
    
    if(!$orgIdentity) {
      throw new InvalidArgumentException(_txt('er.notfound',
                                              array(_txt('ct.org_identities.1', $orgIdentityId))));
    }
    
    $args = array();
    $args['conditions']['CoPipeline.id'] = $id;
    $args['contain'] = false;
    
    $pipeline = $this->find('first', $args);
    
    if(empty($pipeline)) {
      throw new InvalidArgumentException(_txt('er.notfound', array(_txt('ct.co_pipelines.1'), $id)));
    }
    
    // We shouldn't need to do this check, but just in case...
    if($pipeline['CoPipeline']['co_id'] != $orgIdentity['OrgIdentity']['co_id']) {
      throw new InvalidArgumentException(_txt('er.co.notmember'));
    }
    
    // See if we are configured for the requested action. Note that Unlink/Relink
    // are always processed when requested since they are corrective actions.
    
    if(($syncAction == SyncActionEnum::Add && !$pipeline['CoPipeline']['sync_on_add'])
       ||
       ($syncAction == SyncActionEnum::Update && !$pipeline['CoPipeline']['sync_on_update'])
       ||
       ($syncAction == SyncActionEnum::Delete && !$pipeline['CoPipeline']['sync_on_delete'])) {
      return true;
    }
    
    // We need to find a CO Person to operate on.
    $targetIds = $this->findTargetCoPersonId($pipeline, $orgIdentityId, $actorCoPersonId);
    
    if(!$targetIds['co_person_id']) {
      // What we do here depends on the sync action. On add, we create a new CO Person.
      // On update, we do not and abort. This will be a bit confusing if something goes wrong
      // during an initial add, but short of a "force" (manual operation), there's
      // not much else to do. On delete we also abort.
      
      if($syncAction != SyncActionEnum::Add) {
        // If we don't have a CO Person record on an update or delete, there's
        // nothing to do. For relink, we expect the new target to already exist.
        // (Relink to a "new" person should be submitted as an Add, since if
        // "sync on add" is disabled we shouldn't create a new CO Person.)
        return true;
      }
    }
    
    if(($syncAction == SyncActionEnum::Delete
        && !empty($pipeline['CoPipeline']['sync_status_on_delete']))
       || $syncAction == SyncActionEnum::Unlink) {
      $this->processDelete($pipeline, $orgIdentityId, $actorCoPersonId, $provision, $syncAction);
    } else {
      // Pull the full set of attributes needed for syncOrgIdentityToCoPerson.
      // We need to do this after findTargetCoPersonId sice that function might
      // store a Reference Identifier returned from a Match server.
      
      $args = array();
      $args['conditions']['OrgIdentity.id'] = $orgIdentityId;
      $args['contain'] = array(
        'Name',
        'PrimaryName',
        'Address',
        'AdHocAttribute',
        'EmailAddress',
        'Identifier',
        'TelephoneNumber',
        'OrgIdentitySourceRecord',
        // These will pull associated models that were created via the Pipeline
        'PipelineCoPersonRole',
        'Url'
      );
      
      // We did just pull OrgIdentity above, but now we need the associated models
      $orgIdentity = $this->Co->OrgIdentity->find('first', $args);
      
      if(!$orgIdentity) {
        // This really shouldn't happen since we already verified $orgIdentityId
        throw new InvalidArgumentException(_txt('er.notfound',
                                                array(_txt('ct.org_identities.1', $orgIdentityId))));
      }
      
      $coPersonId = $this->syncOrgIdentityToCoPerson($pipeline,
                                                     $syncAction,
                                                     $orgIdentity,
                                                     $targetIds['co_person_id'],
                                                     $actorCoPersonId,
                                                     $provision,
                                                     $oisRawRecord);
     
      // If we are creating a new CO Person and we got a Reference ID from
      // findTargetCoPersonId, then we want to save the Reference ID to the
      // new CO Person record.
      
      if($coPersonId && $targetIds['reference_identifier']) {
        if($oisRecordId) {
          // Attach the Reference Identifier to the OIS Record
          $this->Co->OrgIdentity->OrgIdentitySourceRecord->clear();
          $this->Co->OrgIdentity->OrgIdentitySourceRecord->id = $oisRecordId;
          $this->Co->OrgIdentity->OrgIdentitySourceRecord->saveField('reference_identifier', $targetIds['reference_identifier']);
        }
        
        if(!$targetIds['co_person_id']) {
          // If there wasn't already a CO Person record, attach the Reference
          // Identifier to the newly created CO Person
          
          $identifier = array(
            'identifier'      => $targetIds['reference_identifier'],
            'type'            => IdentifierEnum::Reference,
            'login'           => false,
            'status'          => SuspendableStatusEnum::Active,
            'co_person_id'    => $coPersonId
          );
          
          $this->Co->CoPerson->Identifier->clear();
          $this->Co->CoPerson->Identifier->save($identifier);
          
          // Cut history
          $this->Co->CoPerson->HistoryRecord->record($coPersonId,
                                                     null,
                                                     null,
                                                     $actorCoPersonId,
                                                     ActionEnum::ReferenceIdentifierObtained,
                                                     _txt('rs.match', array($targetIds['reference_identifier'])));
        }
      } elseif($targetIds['co_person_id']
               && $oisRecordId
               && $pipeline['CoPipeline']['match_strategy'] == MatchStrategyEnum::External) {
        // If we have a CO Person ID already and we are using an External match strategy,
        // then if we have a reference ID on the OIS record issue an update match attributes
        // request. Note we might not actually be updating any relevant attributes, but
        // for the moment this seems like the most logical place to do this.
        
        $referenceId = $this->Co->OrgIdentity->OrgIdentitySourceRecord->field('reference_identifier',
                                                                              array('OrgIdentitySourceRecord.id' => $oisRecordId));
        
        if($referenceId) {
          // We use $referenceId as an indicator that this was a pipeline initiated
          // match request, but don't actually use it in the request.
          
          $this->Co->Server->MatchServer->updateMatchAttributes(
            $pipeline['CoPipeline']['match_server_id'],
            $orgIdentityId
          );
        }
        
        // Cut history
        $this->Co->CoPerson->HistoryRecord->record($coPersonId,
                                                   null,
                                                   null,
                                                   $actorCoPersonId,
                                                   ActionEnum::MatchAttributesUpdated);
      }
    }
    
    if($syncAction == SyncActionEnum::Add || $syncAction == SyncActionEnum::Relink) {
      if(!empty($pipeline['CoPipeline']['sync_replace_cou_id'])) {
        // See if there is already a role in the specified COU for this CO Person,
        // and if so expire it. (This will typically only be useful with a Match Strategy.)
        
        try {
          $this->ReplaceCou->CoPersonRole->expire($targetIds['co_person_id'],
                                                  $pipeline['CoPipeline']['sync_replace_cou_id'],
                                                  $actorCoPersonId);
        }
        catch(Exception $e) {
          // For now ignore any failure
        }
      }
      
      if($coPersonId && !empty($pipeline['CoPipeline']['co_enrollment_flow_id'])) {
        $this->createPetition($pipeline['CoPipeline']['co_enrollment_flow_id'],
                              $orgIdentity['OrgIdentity']['id'],
                              $coPersonId);
      }
    }
  }
  
  /**
   * Find the target CO Person ID for the Pipeline.
   * If a matching, unlinked Person is found, a new CoOrgIdentityLink will be created.
   *
   * @since  COmanage Registry v2.0.0
   * @param  Array   $pipeline Array of Pipeline configuration data
   * @param  Integer $orgIdentityId Source Org Identity to run query for
   * @param  Integer $actorCoPersonId CO Person ID of actor, if interactive
   * @return Array   Array of co_person_id and reference_identifier, either or both of which may be null
   * @throws InvalidArgumentException
   * @throws RuntimeException
   */
  
  protected function findTargetCoPersonId($pipeline, $orgIdentityId, $actorCoPersonId=null) {
    $coPersonId = null;
    $referenceId = null;
      
    // We can assume a CO ID since Pipelines do not support pooled org identities
    $coId = $this->Co->OrgIdentity->field('co_id', array('OrgIdentity.id' => $orgIdentityId));
    
    if(!$coId) {
      throw new InvalidArgumentException(_txt('er.notprov.id', array(_txt('ct.cos.1'))));
    }
    
    // First option is to see if the Org Identity is already linked to a CO Person ID.
    // Since org identities are not pooled, the org identity shouldn't have been linked
    // to a CO Person record outside of the CO, so we don't need to cross check the CO ID
    // of the CO Person.
    
    $coPersonId = $this->Co->CoPerson->CoOrgIdentityLink->field('co_person_id',
                                                                array('CoOrgIdentityLink.org_identity_id'
                                                                      => $orgIdentityId));
    
    if($coPersonId) {
      return array(
        'co_person_id' => $coPersonId,
        'reference_identifier' => null
      );
    }
    
    // If not, then execute the appropriate Match Strategy.
    
    if($pipeline['CoPipeline']['match_strategy'] == MatchStrategyEnum::EmailAddress
       || $pipeline['CoPipeline']['match_strategy'] == MatchStrategyEnum::Identifier) {
      // Try to find a single CO Person in the current CO matching the specified
      // attribute. Note we're searching against CO People not Org Identities (but
      // using the new Org Identity's attribute).
      
      $model = $pipeline['CoPipeline']['match_strategy'] == MatchStrategyEnum::EmailAddress
               ? 'EmailAddress'
               : 'Identifier';
      
      // First, we need a record of the specified type, attached to the Org Identity.
      
      $args = array();
      $args['conditions'][$model.'.org_identity_id'] = $orgIdentityId;
      // Identifier requires type, but email address does not
      if($pipeline['CoPipeline']['match_strategy'] == MatchStrategyEnum::Identifier
         || !empty($pipeline['CoPipeline']['match_type'])) {
        $args['conditions'][$model.'.type'] = $pipeline['CoPipeline']['match_type'];
      }
      $args['contain'] = false;
      
      $orgRecords = $this->Co->CoPerson->$model->find('all', $args);
      
      // In the unlikely event we get more than one match, we'll try them all
      
      foreach($orgRecords as $o) {
        $args = array();
        // EmailAddress is case insensitive, but Identifier is not
        if($pipeline['CoPipeline']['match_strategy'] == MatchStrategyEnum::EmailAddress) {
          $args['conditions']['LOWER(EmailAddress.mail)'] = strtolower($o['EmailAddress']['mail']);
        } else {
          $args['conditions']['Identifier.identifier'] = $o['Identifier']['identifier'];
        }
        if($pipeline['CoPipeline']['match_strategy'] == MatchStrategyEnum::Identifier
           || !empty($pipeline['CoPipeline']['match_type'])) {
          $args['conditions'][$model.'.type'] = $pipeline['CoPipeline']['match_type'];
        }
        $args['conditions']['CoPerson.co_id'] = $pipeline['CoPipeline']['co_id'];
        $args['joins'][0]['table'] = 'co_people';
        $args['joins'][0]['alias'] = 'CoPerson';
        $args['joins'][0]['type'] = 'INNER';
        $args['joins'][0]['conditions'][0] = 'CoPerson.id=' . $model . '.co_person_id';
        // Make this a distinct select so we don't get tripped on (eg) the same email
        // address being listed twice for the same CO Person (eg from multiple OIS records)
        $args['fields'] = array('DISTINCT ' . $model . '.co_person_id');
        $args['contain'] = false;
        
        $matchingRecords = $this->Co->CoPerson->$model->find('all', $args);
        
        if(count($matchingRecords) == 1) {
          $coPersonId = $matchingRecords[0][$model]['co_person_id'];
          break;
        } elseif(count($matchingRecords) > 1) {
          // Multiple matching records shouldn't happen, throw an error
          throw new InvalidArgumentException(_txt('er.pi.match.multi', array(_txt('en.match.strategy',
                                                                                  null,
                                                                                  $pipeline['CoPipeline']['match_strategy']))));
        }
        // else No Match
      }
    } elseif($pipeline['CoPipeline']['match_strategy'] == MatchStrategyEnum::External) {
      // Call out to an ID Match server via the CIFER/TIER/ITAP API
      
      if(empty($pipeline['CoPipeline']['match_server_id'])) {
        throw new InvalidArgumentException(_txt('er.notprov.id', array(_txt('ct.match_servers.1'))));
      }
      
      // On error, including 202, an exception is thrown and we don't continue.
      // If we get a Reference ID back, look for an existing CO Person with it.
      
      $referenceId = $this->Co->Server->MatchServer->requestReferenceIdentifier(
        $pipeline['CoPipeline']['match_server_id'],
        $orgIdentityId
      );
      
      $this->Co->OrgIdentity->HistoryRecord->record(null,
                                                    null,
                                                    $orgIdentityId,
                                                    $actorCoPersonId,
                                                    ActionEnum::ReferenceIdentifierObtained,
                                                    _txt('rs.match', array($referenceId)));
      
      $args = array();
      $args['conditions']['Identifier.identifier'] = $referenceId;
      $args['conditions']['Identifier.type'] = IdentifierEnum::Reference;
      $args['conditions']['CoPerson.co_id'] = $pipeline['CoPipeline']['co_id'];
      $args['joins'][0]['table'] = 'co_people';
      $args['joins'][0]['alias'] = 'CoPerson';
      $args['joins'][0]['type'] = 'INNER';
      $args['joins'][0]['conditions'][0] = 'CoPerson.id=Identifier.co_person_id';
      // Make this a distinct select so we don't get tripped on (eg) the same identifier
      // address being listed twice for the same CO Person (eg from multiple OIS records)
      $args['fields'] = array('DISTINCT Identifier.co_person_id');
      $args['contain'] = false;
      
      $matchingRecords = $this->Co->CoPerson->Identifier->find('all', $args);
      
      if(count($matchingRecords) == 1) {
        $coPersonId = $matchingRecords[0]['Identifier']['co_person_id'];
      } elseif(count($matchingRecords) > 1) {
        // Multiple matching records shouldn't happen, throw an error
        throw new InvalidArgumentException(_txt('er.pi.match.multi', array(_txt('en.match.strategy',
                                                                                null,
                                                                                $pipeline['CoPipeline']['match_strategy']))));
      }
      // else No Match
    }
    // else No Matching
    
    if($coPersonId) {
      // If we found a match, create a link and update history
      $coOrgLink = array();
      $coOrgLink['CoOrgIdentityLink']['org_identity_id'] = $orgIdentityId;
      $coOrgLink['CoOrgIdentityLink']['co_person_id'] = $coPersonId;
      
      $this->Co->CoPerson->CoOrgIdentityLink->clear();
      
      // CoOrgIdentityLink is not currently provisioner-enabled, but we'll disable
      // provisioning just in case that changes in the future. Also, Tell
      // CoOrgIdentityLink not to run the pipeline since we're already in one.
      if(!$this->Co->CoPerson->CoOrgIdentityLink->save($coOrgLink, array("provision" => false, "pipeline" => false))) {
        throw new RuntimeException(_txt('er.db.save-a', array('CoOrgIdentityLink')));
      }
      
      $this->Co->CoPerson->HistoryRecord->record($coPersonId,
                                                 null,
                                                 $orgIdentityId,
                                                 $actorCoPersonId,
                                                 ActionEnum::CoPersonOrgIdLinked);
      
      $this->Co->CoPerson->HistoryRecord->record($coPersonId,
                                                 null,
                                                 $orgIdentityId,
                                                 $actorCoPersonId,
                                                 ActionEnum::CoPersonMatchedPipelne,
                                                 _txt('rs.pi.match', array($pipeline['CoPipeline']['name'],
                                                                           $pipeline['CoPipeline']['id'],
                                                                           _txt('en.match.strategy',
                                                                                null,
                                                                                $pipeline['CoPipeline']['match_strategy']))));
    }
    
    // Return whatever we found.
    
    return array(
      'co_person_id' => $coPersonId,
      'reference_identifier' => $referenceId
    );
  }
  
  /**
   * Process an Org Identity delete action.
   *
   * @since  COmanage Registry v2.0.0
   * @param  Array $coPipeline Array of CO Pipeline configuration
   * @param  Integer $orgIdentityId Org Identity ID
   * @param  Integer $actorCoPersonId CO Person ID of actor
   * @param  Boolean $provision Whether to trigger provisioning
   * @param  SyncActionEnum $syncAction Action triggering delete
   * @return Boolean true on success
   */
  
  protected function processDelete($coPipeline, $orgIdentityId, $actorCoPersonId=null, $provision=true, $syncAction=SyncActionEnum::Delete) {
    // First, find the role associated with this Org Identity and update the status
    
    $args = array();
    $args['conditions']['CoPersonRole.source_org_identity_id'] = $orgIdentityId;
    $args['contain'] = false;
    
    $roles = $this->Co->CoPerson->CoPersonRole->find('all', $args);
    
    if(!empty($roles)) {
      // There should be only one such role, but that could change over time
      foreach($roles as $role) {
        $roleId = $role['CoPersonRole']['id'];
        
        // Update the role to the specified status
        $this->Co->CoPerson->CoPersonRole->clear();
        $this->Co->CoPerson->CoPersonRole->id = $roleId;
        // This will also recalculate Person status
        $this->Co->CoPerson->CoPersonRole->saveField('status',
                                                     $coPipeline['CoPipeline']['sync_status_on_delete'],
                                                     array('provision' => $provision));
        
        // Create history
        $this->Co->CoPerson->HistoryRecord->record($role['CoPersonRole']['co_person_id'],
                                                   $roleId,
                                                   $orgIdentityId,
                                                   $actorCoPersonId,
                                                   ActionEnum::CoPersonRoleEditedPipeline,
                                                   _txt('rs.pi.role.status',
                                                        array(_txt('en.status', null, $coPipeline['CoPipeline']['sync_status_on_delete']))));
      }
    }
    
    // We also need to delete any group memberships associated with this org identity.
    // This is similar to the code in syncOrgIdentityToCoPerson, below.
    
    $args = array();
    $args['conditions']['CoGroupMember.source_org_identity_id'] = $orgIdentityId;
    $args['contain'][] = 'CoGroup';
    
    $memberships = $this->Co->CoPerson->CoGroupMember->find('all', $args);
    
    if(!empty($memberships)) {
      foreach($memberships as $gm) {
        if(!$this->Co->CoPerson->CoGroupMember->delete($gm['CoGroupMember']['id'], array("provision" => $provision))) {
          throw new RuntimeException(_txt('er.db.save-a', array('CoGroupMember')));
        }
        
        // Cut history
        $this->Co->CoPerson->HistoryRecord->record($gm['CoGroupMember']['co_person_id'],
                                                   null,
                                                   $orgIdentityId,
                                                   $actorCoPersonId,
                                                   ActionEnum::CoGroupMemberDeletedPipeline,
                                                   _txt('rs.grm.deleted',
                                                        array($gm['CoGroup']['name'],
                                                              $gm['CoGroupMember']['co_group_id'])),
                                                   $gm['CoGroupMember']['co_group_id']);
        
        $this->Co->CoPerson->HistoryRecord->record($gm['CoGroupMember']['co_person_id'],
                                                   null,
                                                   $orgIdentityId,
                                                   $actorCoPersonId,
                                                   ActionEnum::CoGroupMemberDeletedPipeline,
                                                   _txt('rs.pi.sync-a', array(_txt('ct.co_group_members.1'),
                                                                              $coPipeline['CoPipeline']['name'],
                                                                              $coPipeline['CoPipeline']['id'])),
                                                   $gm['CoGroupMember']['co_group_id']);
      }
    }
    
    // On a Sync Delete (OIS source drops record) we keep the various attributes
    // on the CO Person record in order to maintain the integrity of the record.
    // (ie: Though the OIS source deleted its record, we simply flag the record
    // as deleted or expired - we don't actually delete it.) For unlinking,
    // however, we need to purge these attributes since they'd confuse the
    // original record.
    
    if($syncAction == SyncActionEnum::Unlink) {
      $models = array(
        'EmailAddress',
        'Identifier',
        'Name',
        'Url'
      );
      
      // For each model, remove values that trace back to their source attributes
      // in $orgIdentityId.
      
      foreach($models as $m) {
        $mkey = 'source_' . Inflector::underscore($m) . '_id';
        
        $args = array();
        $args['conditions'][$m.'.org_identity_id'] = $orgIdentityId;
        $args['contain'] = false;
        
        $objs = $this->Co->CoPerson->$m->find('all', $args);
        
        if(!empty($objs)) {
          foreach($objs as $o) {
            // deleteAll on a single object with callbacks=true basically saves
            // us the trouble of having to separately find and delete.
            
            $conditions = array(
              $m.'.'.$mkey => $o[$m]['id']
            );
            
            $this->Co->CoPerson->$m->deleteAll($conditions, false, true);
          }
        }
      }
    }
    
    return true;
  }
  
  /**
   * Sync Org Identity attributes to a CO Person record. Suitable for add or update
   * sync actions.
   *
   * @since  COmanage Registry v2.0.0
   * @param  Array   $coPipeline       Array of CO Pipeline configuration
   * @param  SyncActionEnum $syncAction Add, Update, or Delete
   * @param  Array   $orgIdentity      Array of Org Identity data and related models
   * @param  Integer $targetCoPersonId Target CO Person ID, if known
   * @param  Integer $actorCoPersonId  CO Person ID of actor
   * @param  Boolean $provision        Whether to trigger provisioning
   * @param  String  $oisRawRecord     If the Org Identity came from an Org Identity Source, the raw record
   * @param  Boolean $safeties         Whether to operate with safeties off
   * @return Integer                   CO Person ID on success
   */
  
  protected function syncOrgIdentityToCoPerson($coPipeline, 
                                               $syncAction,
                                               $orgIdentity, 
                                               $targetCoPersonId=null, 
                                               $actorCoPersonId=null,
                                               $provision=true,
                                               $oisRawRecord=null,
                                               $safeties="on") {
    $coPersonId = $targetCoPersonId;
    $coPersonRoleId = null;
    $doProvision = false; // We did something provision-worthy
    
    // If there is no CO Person ID provided, the first thing we need to do is
    // create a new CO Person record and link it to the Org Identity.
    
    if(!$coPersonId) {
      // First create the CO Person
      
      $coPerson = array(
        'CoPerson' => array(
          'co_id'  => $orgIdentity['OrgIdentity']['co_id'],
          'status' => empty($coPipeline['CoPipeline']['sync_coperson_status']) ? StatusEnum::Active : $coPipeline['CoPipeline']['sync_coperson_status']
        )
      );
      
      if(!empty($orgIdentity['OrgIdentity']['date_of_birth'])) {
        $coPerson['CoPerson']['date_of_birth'] = $orgIdentity['OrgIdentity']['date_of_birth'];
      }
      
      // Clear here and below in case we're run in a loop
      $this->Co->CoPerson->clear();
      
      if(!$this->Co->CoPerson->save($coPerson, array("provision" => false, "safeties" => $safeties))) {
        throw new RuntimeException(_txt('er.db.save-a', array('CoPerson')));
      }
      
      $coPersonId = $this->Co->CoPerson->id;
      
      // Now link it to the Org Identity
      
      $orgLink = array(
        'CoOrgIdentityLink' => array(
          'co_person_id'    => $coPersonId,
          'org_identity_id' => $orgIdentity['OrgIdentity']['id']
        )
      );
      
      $this->Co->CoPerson->CoOrgIdentityLink->clear();
      
      // Tell CoOrgIdentityLink not to run the pipeline since we're already in one
      if(!$this->Co->CoPerson->CoOrgIdentityLink->save($orgLink, array("provision" => false, 
                                                                       "pipeline"  => false,
                                                                       "safeties" => $safeties))) {
        throw new RuntimeException(_txt('er.db.save-a', array('CoOrgIdentityLink')));
      }
      
      // And create a Primary Name. We use the source's Primary Name here, but
      // we don't actually link it to the source. This is in case the source record
      // goes away and we want to ensure we still have a name record attached to the
      // CO Person. (Under most circumstances the OIS name will be preserved, but eg
      // an admin might try to clear out all associated data.)
      
      $name = array(
        'Name' => array(
          'co_person_id'   => $coPersonId,
          'honorific'      => $orgIdentity['PrimaryName']['honorific'],
          'given'          => $orgIdentity['PrimaryName']['given'],
          'middle'         => $orgIdentity['PrimaryName']['middle'],
          'family'         => $orgIdentity['PrimaryName']['family'],
          'suffix'         => $orgIdentity['PrimaryName']['suffix'],
          'type'           => $orgIdentity['PrimaryName']['type'],
          'primary_name'   => true,
//          'source_name_id' => $orgIdentity['PrimaryName']['id'],
        )
      );
      
      $this->Co->CoPerson->Name->clear();
      
      // We need to inject the CO so extended types can be saved
      $this->Co->CoPerson->Name->validate['type']['content']['rule'][1]['coid'] = $orgIdentity['OrgIdentity']['co_id'];
      
      if(!$this->Co->CoPerson->Name->save($name, array("provision" => false, "safeties" => $safeties))) {
        throw new RuntimeException(_txt('er.db.save-a', array('Name')));
      }
      
      // Cut history
      $this->Co->CoPerson->HistoryRecord->record($coPersonId,
                                                 null,
                                                 $orgIdentity['OrgIdentity']['id'],
                                                 $actorCoPersonId,
                                                 ActionEnum::CoPersonAddedPipeline,
                                                 _txt('rs.pi.sync-a', array(_txt('ct.co_people.1'),
                                                                            $coPipeline['CoPipeline']['name'],
                                                                            $coPipeline['CoPipeline']['id'])));
      
      $this->Co->CoPerson->HistoryRecord->record($coPersonId,
                                                 null,
                                                 $orgIdentity['OrgIdentity']['id'],
                                                 $actorCoPersonId,
                                                 ActionEnum::CoPersonOrgIdLinked);
      
      $doProvision = true;
    }
    
    if($coPipeline['CoPipeline']['create_role']) {
      // Construct a CO Person Role and compare against existing.
      
      // Figure out a target affiliation, defaulting to Member if we can't find anything else
      // (since it's required by CoPersonRole).
      $affil = AffiliationEnum::Member;
      
      if(!empty($coPipeline['CoPipeline']['sync_affiliation'])) {
        // Use the configured affil
        $affil = $coPipeline['CoPipeline']['sync_affiliation'];
      } elseif(!empty($orgIdentity['OrgIdentity']['affiliation'])) {
        // Use the org identity's affil
        $affil = $orgIdentity['OrgIdentity']['affiliation'];
      }
      
      $newCoPersonRole = array(
        'CoPersonRole' => array(
          'affiliation' => $affil,
          // Set the cou_id even if null so the diff operates correctly
          'cou_id'               => $coPipeline['CoPipeline']['sync_cou_id'],
          'o'                    => $orgIdentity['OrgIdentity']['o'],
          'ou'                   => $orgIdentity['OrgIdentity']['ou'],
          'title'                => $orgIdentity['OrgIdentity']['title'],
          'valid_from'           => $orgIdentity['OrgIdentity']['valid_from'],
          'valid_through'        => $orgIdentity['OrgIdentity']['valid_through'],
          'status'               => StatusEnum::Active,
          // Note the inbound record will contain 'manager_identifier' and
          // 'sponsor_identifier'. Those are populated below, these are so
          // comparison against the CoPersonRole record works.
          'manager_co_person_id' => null,
          'sponsor_co_person_id' => null
        )
      );
      
      // If manager_identifier and/or sponsor_identifier are set, try to map
      // them to an existing CO Person ID.
      
      foreach(array('manager', 'sponsor') as $r) {
        if(!empty($orgIdentity['OrgIdentity'][$r.'_identifier'])) {
          // Try to map the identifier to a CO Person ID
          
          $args = array();
          $args['conditions']['Identifier.identifier'] = $orgIdentity['OrgIdentity'][$r.'_identifier'];
          $args['conditions']['Identifier.status'] = SuspendableStatusEnum::Active;
          if(!empty($coPipeline['CoPipeline']['sync_identifier_type'])) {
            $args['conditions']['Identifier.type'] = $coPipeline['CoPipeline']['sync_identifier_type'];
          }
          $args['conditions'][] = 'Identifier.co_person_id IS NOT NULL';
          $args['contain'] = false;
          
          // The above conditions might match more than one Identifier, so we only
          // look at the first.
          $tIdentifier = $this->Co->CoPerson->Identifier->find('first', $args);
          
          if(!empty($tIdentifier)) {
            $newCoPersonRole['CoPersonRole'][$r.'_co_person_id'] = $tIdentifier['Identifier']['co_person_id'];
          }
          // else just fail silently and process what we can
        }
      }
      
      // Next see if there is a role associated with this OrgIdentity.
      
      if(!empty($orgIdentity['PipelineCoPersonRole']['id'])) {
        $newCoPersonRole['CoPersonRole']['id'] = $orgIdentity['PipelineCoPersonRole']['id'];
        
        $curCoPersonRole = array();
        
        foreach(array('id',
                      'affiliation',
                      'cou_id',
                      'manager_co_person_id',
                      'o',
                      'ou',
                      'sponsor_co_person_id',
                      'title',
                      'valid_from',
                      'valid_through',
                      'status') as $attr) {
          $curCoPersonRole['CoPersonRole'][$attr] = $orgIdentity['PipelineCoPersonRole'][$attr];
        }
        
        // Diff array to see if we should save
        $cstr = $this->Co->CoPerson->CoPersonRole->changesToString($newCoPersonRole,
                                                                   $curCoPersonRole);
        
        if(!empty($cstr)) {
          // Cut the history diff here, since we don't want this on an add
          // (If the save fails later the parent transaction will roll this back)
          
          $this->Co->OrgIdentity->HistoryRecord->record($coPersonId,
                                                        $orgIdentity['PipelineCoPersonRole']['id'],
                                                        null,
                                                        $actorCoPersonId,
                                                        ActionEnum::CoPersonRoleEditedPipeline,
                                                        _txt('rs.edited-a4', array(_txt('ct.co_person_roles.1'),
                                                                                   $cstr)));
        } else {
          // No change, unset $newCoPersonRole to indicate not to bother saving
          $newCoPersonRole = array();
          
          // But note the coPersonRoleId so related model diffs calculate correctly.
          $coPersonRoleId = $orgIdentity['PipelineCoPersonRole']['id'];
        }
      } else {
        // No current person role, so just save as is
      }
      
      if(!empty($newCoPersonRole)) {
        // Save the updated record and cut history
        
        // Link the role before saving
        $newCoPersonRole['CoPersonRole']['co_person_id'] = $coPersonId;
        $newCoPersonRole['CoPersonRole']['source_org_identity_id'] = $orgIdentity['OrgIdentity']['id'];
        
        $this->Co->CoPerson->CoPersonRole->clear();
        
        // We need to inject the CO so extended types can be saved
        $this->Co->CoPerson->CoPersonRole->validate['affiliation']['content']['rule'][1]['coid'] = $orgIdentity['OrgIdentity']['co_id'];
        
        if(!$this->Co->CoPerson->CoPersonRole->save($newCoPersonRole, array("provision" => false, "safeties" => $safeties))) {
          throw new RuntimeException(_txt('er.db.save-a', array('CoPersonRole')));
        }
        
        $coPersonRoleId = $this->Co->CoPerson->CoPersonRole->id;
        
        // Cut history
        $this->Co->CoPerson->HistoryRecord->record($coPersonId,
                                                   $coPersonRoleId,
                                                   $orgIdentity['OrgIdentity']['id'],
                                                   $actorCoPersonId,
                                                   ActionEnum::CoPersonRoleAddedPipeline,
                                                   _txt('rs.pi.sync-a', array(_txt('ct.co_person_roles.1'),
                                                                              $coPipeline['CoPipeline']['name'],
                                                                              $coPipeline['CoPipeline']['id'])));
        
        $doProvision = true;
      }
    }
    
    // Next handle associated models
    
    // Supported associated models and their parent relation
    $models = array(
      'Address'         => 'co_person_role_id',
      'AdHocAttribute'  => 'co_person_role_id',
      'EmailAddress'    => 'co_person_id',
      'Identifier'      => 'co_person_id',
      'Name'            => 'co_person_id',
      'TelephoneNumber' => 'co_person_role_id',
      'Url'             => 'co_person_id'
    );
    
    foreach($models as $m => $pkey) {
      // Model key used by changelog, eg identifier_id
      $mkey = Inflector::underscore($m) . '_id';
      // Model in pluralized format, eg email_addresses
      $mpl = Inflector::tableize($m);
      // Parent value ($coPersonId or $coPersonRoleId)
      $pval = null;
      // Pointer to model $m describes (eg $Identifier)
      $model = null;
      
      if($pkey == 'co_person_id') {
        $pval = $coPersonId;
        $model = $this->Co->CoPerson->$m;
      } elseif($pkey == 'co_person_role_id') {
        // We only process role related attributes if we have a role ID,
        // which implies create_role is true.
        
        if(!$coPersonRoleId)
          continue;
        
        $pval = $coPersonRoleId;
        $model = $this->Co->CoPerson->CoPersonRole->$m;
      }
      
      // Records attached to the Org Identity
      $newRecords = array();
      // Records attached to the CO Person/Role
      $curRecords = array();
      
      // Map each org record into a "new" CO Person record, keyed on the org record's id
      
      foreach($orgIdentity[$m] as $orgRecord) {
        // Construct the new record
        $newRecord = $orgRecord;
        
        // Get rid of metadata keys
        foreach(array('id',
                      'org_identity_id',
                      'created',
                      'modified',
                      $mkey,
                      'revision',
                      'deleted',
                      // We drop login (from Identifier) since CO Person Identifiers
                      // aren't flagged for login (that's an Org Identity Identifier thing).
                      'login',
                      // We explicitly get rid of any primary name attribute since we
                      // should already have a primary name for the CO Person (either existing
                      // or created above), and we don't want the OIS to directly change it.
                      // (Election strategies should do that.)
                      'primary_name',
                      'actor_identifier') as $k) {
          unset($newRecord[$k]);
        }
        
        // And link the record
        $newRecord[$pkey] = $pval;
        $newRecord['source_' . $mkey] = $orgRecord['id'];
        
        $newRecords[ $orgRecord['id'] ] = $newRecord;
      }
      
      // Get the set of current CO Person records and prepare them for comparison.
      // If $safeties are off, we assume there are no current records.
      
      if($safeties != "off") {
        $args = array();
        $args['conditions'][$m.'.'.$pkey] = $pval;
        // We only want the records that were derived from $orgIdentity['OrgIdentity']['id'].
        // This turns out to be surprisingly hard to figure out, partly because joining back
        // to the same table is messy, and partly because we may be trying to trace back to
        // a deleted record. To start, we'll filter out anything without a source_id...
        // those couldn't have come from an OrgIdentity.
        $args['conditions'][] = $m.'.source_' . $mkey . ' IS NOT NULL';
        $args['contain'] = false;
        
        $recs = $model->find('all', $args);
        
        foreach($recs as $a) {
          // First we pull the org identity of the source record. By retrieving
          // based on ID, ChangelogBehavior will return deleted records as well,
          // which we need here.
          $linkedOrgIdentityId = $model->field('org_identity_id', array($m.'.id' => $a[$m]['source_'.$mkey]));
          
          if($linkedOrgIdentityId != $orgIdentity['OrgIdentity']['id']) {
            // This didn't come from the Org Identity we're interest in, so skip it
            continue;
          }
          
          $curRecord = $a[$m];
          
          // Get rid of metadata keys
          foreach(array('org_identity_id',
                        'created',
                        'modified',
                        $mkey,
                        'revision',
                        'deleted',
                        'login',
                        'primary_name',
                        'actor_identifier') as $k) {
            unset($curRecord[$k]);
          }
          
          $curRecords[ $curRecord['source_' . $mkey] ] = $curRecord;
        }
      }
      
      // Now that the lists are ready, walk through them and process any changes
      
      foreach($newRecords as $id => $nr) {
        if(isset($curRecords[$id])) {
          // This is an update, not an add, so perform a comparison.
          
          // Inject the record ID.
          $newRecords[$id]['id'] = $curRecords[$id]['id'];
          
          // XXX Normalized data will make a non-diff appear as a diff (CO-1336)
          $cstr = $model->changesToString(array($m => $newRecords[$id]),
                                          array($m => $curRecords[$id]));
          
          if(!empty($cstr)) {
            // Cut the history diff here, since we already have the change string
            // (If the save fails later the parent transaction will roll this back)
            
            $this->Co->OrgIdentity->HistoryRecord->record($coPersonId,
                                                          null,
                                                          null,
                                                          $actorCoPersonId,
                                                          ActionEnum::CoPersonEditedPipeline,
                                                          _txt('rs.edited-a4', array(_txt('ct.'.$mpl.'.1'),
                                                                                     $cstr)));
          } else {
            // No change, unset record to indicate not to bother saving
            unset($newRecords[$id]);
          }
          
          // Unset current record so we don't see it as a delete
          unset($curRecords[$id]);
        } else {
          // This is an add. Cut the history diff here since we already calculated it
          // for update.
          $oldrec = array();
          $oldrec[$m] = array();
          $newrec = array();
          $newrec[$m][] = $newRecords[$id];
          
          $cstr = $model->changesToString($newrec, $oldrec);
          
          $this->Co->OrgIdentity->HistoryRecord->record($coPersonId,
                                                        null,
                                                        null,
                                                        $actorCoPersonId,
                                                        ActionEnum::CoPersonEditedPipeline,
                                                        _txt('rs.edited-a4', array(_txt('ct.'.$mpl.'.1'),
                                                                                   $cstr)));
        }
        
        // If the record is still valid record history (do this here since it's easier, we'll rollback on save failure)
        if(isset($curRecords[$id])) {
          $this->Co->CoPerson->HistoryRecord->record($coPersonId,
                                                     null,
                                                     $orgIdentity['OrgIdentity']['id'],
                                                     $actorCoPersonId,
                                                     ActionEnum::CoPersonEditedPipeline,
                                                     _txt('rs.pi.sync-a', array(_txt('ct.'.$mpl.'.1'),
                                                                                $coPipeline['CoPipeline']['name'],
                                                                                $coPipeline['CoPipeline']['id'])));
        }
      }
      
      // And finally process the save for any remaining records
      foreach($newRecords as $srcid => $nr) {
        $model->clear();
        
        // We need to inject the CO so extended types can be saved
        $model->validate['type']['content']['rule'][1]['coid'] = $orgIdentity['OrgIdentity']['co_id'];
        
        // For identifiers and email addresses, we want to skip availability checking
        // since we might be writing multiple versions of the same attribute (from
        // different org identity sources).
        
        // For email addresses, we generally want to honor the verified status,
        // *unless* we're configured to trigger an Enrollment Flow. In that
        // case, we need an unverified email address for the confirmation to be
        // sent, but only for add/relink (as per execute(), above).
        
        $trustVerified = true;
        
        if($syncAction == SyncActionEnum::Add || $syncAction == SyncActionEnum::Relink) {
          $trustVerified = empty($coPipeline['CoPipeline']['co_enrollment_flow_id']);
        }
        
        if(!$model->save($nr, array("provision" => false,
                                    "safeties" => $safeties,
                                    "skipAvailability" => true,
                                    "trustVerified" => $trustVerified))) {
          
          throw new RuntimeException(_txt('er.db.save-a',
                                          array($m . " (" . join(',', array_keys($model->validationErrors)). ")")));
        }
        
        $doProvision = true;
      }
      
      // Everything remaining in $curRecords is an obsolete record to be deleted
      foreach($curRecords as $srcid => $cr) {
        $model->delete($cr['id']);
        
        // Record history
        $oldrec = array();
        $oldrec[$m][] = $cr;
        $newrec = array();
        $newrec[$m] = array();
        
        $cstr = $model->changesToString($newrec, $oldrec);
        
        $this->Co->CoPerson->HistoryRecord->record($coPersonId,
                                                   null,
                                                   $orgIdentity['OrgIdentity']['id'],
                                                   $actorCoPersonId,
                                                   ActionEnum::CoPersonEditedPipeline,
                                                   $cstr);
        
        $doProvision = true;
      }
    }
    
    // If the OrgIdentity came from an OIS, see if there are mapped group memberships
    $memberGroups = array();
    
    // We need the raw record passed in vs pulling it from OrgIdentitySourceRecord
    // because the letter may have a hashed version that we can't parse.
    if(!empty($orgIdentity['OrgIdentitySourceRecord']['org_identity_source_id'])
       && !empty($oisRawRecord)) {
      $groupAttrs = $this->OrgIdentitySource
                         ->resultToGroups($orgIdentity['OrgIdentitySourceRecord']['org_identity_source_id'],
                                          $oisRawRecord);
      $mappedGroups = $this->OrgIdentitySource
                           ->CoGroupOisMapping
                           ->mapGroups($orgIdentity['OrgIdentitySourceRecord']['org_identity_source_id'],
                                       $groupAttrs);
      
      if(!empty($mappedGroups)) {
        // Pull the Group Names
        $args = array();
        $args['conditions']['CoGroup.id'] = array_keys($mappedGroups);
        $args['contain'] = false;
        
        $memberGroups = $this->Co->CoGroup->find('all', $args);
      }
    }
    
    // Group memberships are a bit trickier than other MVPAs, since we can't have
    // multiple memberships in the same group. So we only add a membership if there
    // is no existing membership (not if there is no existing membership linked
    // to this pipeline), and we only delete memberships linked to this pipeline
    // if there is no longer eligibility.
    
    // Start by pulling the list of current group memberships.
    
    $args = array();
    $args['conditions']['CoGroupMember.co_person_id'] = $coPersonId;
    $args['conditions']['CoGroupMember.member'] = true;
    $args['contain'] = false;
    
    if($safeties != "off") {
      $curGroupMemberships = $this->Co->CoGroup->CoGroupMember->find('all', $args);
    } else {
      // We assume no current memberships
      $curGroupMemberships = array();
    }
    
    // For each mapped group membership, create the membership if it doesn't exist
    
    foreach($memberGroups as $gm) {
      $curGm = Hash::extract($curGroupMemberships, '{n}.CoGroupMember[co_group_id='.$gm['CoGroup']['id'].']');
      
      if(!$curGm) {
        // Create a membership
        $newGroupMember = array(
          'CoGroupMember' => array(
            'co_group_id'            => $gm['CoGroup']['id'],
            'co_person_id'           => $coPersonId,
            'member'                 => true,
            'owner'                  => false,
            'valid_from'             => $mappedGroups[ $gm['CoGroup']['id'] ]['valid_from'],
            'valid_through'          => $mappedGroups[ $gm['CoGroup']['id'] ]['valid_through'],
            'source_org_identity_id' => $orgIdentity['OrgIdentity']['id']
          )
        );
        
        $this->Co->CoPerson->CoGroupMember->clear();
        
        if(!$this->Co->CoPerson->CoGroupMember->save($newGroupMember, array("provision" => false, "safeties" => $safeties))) {
          throw new RuntimeException(_txt('er.db.save-a', array('CoGroupMember')));
        }
        
        // Cut history
        $this->Co->CoPerson->HistoryRecord->record($coPersonId,
                                                   null,
                                                   $orgIdentity['OrgIdentity']['id'],
                                                   $actorCoPersonId,
                                                   ActionEnum::CoGroupMemberAddedPipeline,
                                                   _txt('rs.grm.added', array($gm['CoGroup']['name'],
                                                                              $gm['CoGroup']['id'],
                                                                              _txt($newGroupMember['CoGroupMember']['member'] ? 'fd.yes' : 'fd.no'),
                                                                              _txt($newGroupMember['CoGroupMember']['owner'] ? 'fd.yes' : 'fd.no'))),
                                                   $gm['CoGroup']['id']);
        
        $this->Co->CoPerson->HistoryRecord->record($coPersonId,
                                                   null,
                                                   $orgIdentity['OrgIdentity']['id'],
                                                   $actorCoPersonId,
                                                   ActionEnum::CoGroupMemberAddedPipeline,
                                                   _txt('rs.pi.sync-a', array(_txt('ct.co_group_members.1'),
                                                                              $coPipeline['CoPipeline']['name'],
                                                                              $coPipeline['CoPipeline']['id'])),
                                                   $gm['CoGroup']['id']);
        
        $doProvision = true;
      } else {
        // Make sure validity dates are in sync. We could do a role check here too
        // but we don't currently support anything other than member. Note we only
        // update group memberships from our source identity, so if the person was
        // manually added to a group we won't updated it.
        
        if($curGm[0]['source_org_identity_id'] == $orgIdentity['OrgIdentity']['id']) {
          // For now we just check valid from/through`
          if(($curGm[0]['valid_from'] != $mappedGroups[ $curGm[0]['co_group_id'] ]['valid_from'])
             || ($curGm[0]['valid_through'] != $mappedGroups[ $curGm[0]['co_group_id'] ]['valid_through'])) {
            $newGroupMember = array(
              'CoGroupMember' => array(
                'id'                     => $curGm[0]['id'],
                'co_group_id'            => $curGm[0]['co_group_id'],
                'co_person_id'           => $curGm[0]['co_person_id'],
                'member'                 => true,
                'owner'                  => false,
                'valid_from'             => $mappedGroups[ $curGm[0]['co_group_id'] ]['valid_from'],
                'valid_through'          => $mappedGroups[ $curGm[0]['co_group_id'] ]['valid_through'],
                'source_org_identity_id' => $curGm[0]['source_org_identity_id']
              )
            );
            
            $this->Co->CoPerson->CoGroupMember->clear();
            
            if(!$this->Co->CoPerson->CoGroupMember->save($newGroupMember, array("provision" => false, "safeties" => $safeties))) {
              throw new RuntimeException(_txt('er.db.save-a', array('CoGroupMember')));
            }
            
            // Cut history
            $this->Co->CoPerson->HistoryRecord->record($coPersonId,
                                                       null,
                                                       $curGm[0]['source_org_identity_id'],
                                                       $actorCoPersonId,
                                                       ActionEnum::CoGroupMemberEditedPipeline,
                                                       $this->Co
                                                            ->CoGroup
                                                            ->CoGroupMember
                                                            ->changesToString($newGroupMember,
                                                                              array('CoGroupMember' => $curGm[0])),
                                                       $gm['CoGroup']['id']);
            
            $this->Co->CoPerson->HistoryRecord->record($coPersonId,
                                                       null,
                                                       $orgIdentity['OrgIdentity']['id'],
                                                       $actorCoPersonId,
                                                       ActionEnum::CoGroupMemberEditedPipeline,
                                                       _txt('rs.pi.sync-a', array(_txt('ct.co_group_members.1'),
                                                                                  $coPipeline['CoPipeline']['name'],
                                                                                  $coPipeline['CoPipeline']['id'])),
                                                       $gm['CoGroup']['id']);
            
            $doProvision = true;
          }
        }
      }
    }
    
    if($safeties != "off") {
      // Walk through current list of Group Memberships and remove any associated
      // with this pipeline and not present in $memberGroups.
      
      foreach($curGroupMemberships as $cgm) {
        if($cgm['CoGroupMember']['source_org_identity_id']
           && $cgm['CoGroupMember']['source_org_identity_id'] == $orgIdentity['OrgIdentity']['id']) {
          // This group came from this source org identity, is it still valid?
          // (Cake's Hash syntax is a bit obscure...)
          $gid = $cgm['CoGroupMember']['co_group_id'];
          
          if(!Hash::check($memberGroups, '{n}.CoGroup[id='.$gid.'].id')) {
            // Not a valid group membership anymore, delete it. We need to pull the
            // group to get the name for history.
            
            $gname = $this->Co->CoGroup->field('name', array('CoGroup.id' => $gid));
            
            if(!$this->Co->CoPerson->CoGroupMember->delete($cgm['CoGroupMember']['id'], array("provision" => false))) {
              throw new RuntimeException(_txt('er.db.save-a', array('CoGroupMember')));
            }
            
            // Cut history
            $this->Co->CoPerson->HistoryRecord->record($coPersonId,
                                                       null,
                                                       $orgIdentity['OrgIdentity']['id'],
                                                       $actorCoPersonId,
                                                       ActionEnum::CoGroupMemberDeletedPipeline,
                                                       _txt('rs.grm.deleted', array($gname, $gid)),
                                                       $gid);
            
            $this->Co->CoPerson->HistoryRecord->record($coPersonId,
                                                       null,
                                                       $orgIdentity['OrgIdentity']['id'],
                                                       $actorCoPersonId,
                                                       ActionEnum::CoGroupMemberDeletedPipeline,
                                                       _txt('rs.pi.sync-a', array(_txt('ct.co_group_members.1'),
                                                                                  $coPipeline['CoPipeline']['name'],
                                                                                  $coPipeline['CoPipeline']['id'])),
                                                       $gid);
            
            $doProvision = true;
          }
        }
      }
    }
    
    if($provision && $doProvision) {
      // Maybe assign identifiers. We use $doProvision here since that tracks whether
      // any changes were made. While we're primarily interested in a new CO Person
      // being created, it's also possible that another attribute being sync'd could
      // create circumstances in which an Identifier Assignment can now be completed.
      // We also follow the $provision flag since if we don't want to provision we're
      // probably in an enrollment flow, which means we should wait until the appropriate
      // step to assign identifiers as well.
        
      // This will return an array describing which, if any, identifiers were assigned,
      // but we don't do anything with the result here
      $this->Co->CoPerson->Identifier->assign('CoPerson', $coPersonId, $actorCoPersonId, false);
    
      // Trigger provisioning
      
      // In typical cases, manualProvision will not generate an exception since
      // ProvisionerBehavior::provisionPeople/Groups will suppress them. But there
      // are some theoretical circumstances that can generate an exception, and
      // we don't want to fail the entire operation due to a provisioner error.
      $this->Co->CoPerson->Behaviors->load('Provisioner');
      
      try {
        $this->Co->CoPerson->manualProvision(null, $coPersonId, null, ProvisioningActionEnum::CoPersonPipelineProvisioned);
      }
      catch(Exception $e) {
        // XXX we should probably log this somehow
      }
    }
    
    return $coPersonId;
  }
}
