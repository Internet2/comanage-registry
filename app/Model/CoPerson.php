<?php
/**
 * COmanage Registry CO Person Model
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

class CoPerson extends AppModel {
  // Define class name for cake
  public $name = "CoPerson";
  
  // Current schema version for API
  public $version = "1.0";
  
  // Add behaviors
  public $actsAs = array('Containable',
                         'Provisioner',
                         'Changelog' => array('priority' => 5));
  
  // Association rules from this model to other models
  public $belongsTo = array("Co");                    // A CO Person Source is attached to one CO
  
  public $hasOne = array(
    "CoNsfDemographic" => array('dependent' => true),
    // A person can have one invite (per CO)
    "CoInvite" => array('dependent' => true),
    // An CO Person has one Primary Name, which is a pointer to a Name
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
    // It's OK to delete notifications where this Person is the subject, but that's it.
    "CoNotificationSubject" => array(
      'className' => 'CoNotification',
      'foreignKey' => 'subject_co_person_id',
      'dependent' => true
    ),
    "CoNotificationActor" => array(
      'className' => 'CoNotification',
      'foreignKey' => 'actor_co_person_id'
    ),
    "CoNotificationRecipient" => array(
      'className' => 'CoNotification',
      'foreignKey' => 'recipient_co_person_id'
    ),
    "CoNotificationResolver" => array(
      'className' => 'CoNotification',
      'foreignKey' => 'resolver_co_person_id'
    ),
    // A person can have more than one org identity
    "CoOrgIdentityLink" => array('dependent' => true),
    // A person can have one or more person roles
    "CoPersonRole" => array('dependent' => true),
    "CoPetitionApprover" => array(
      'className' => 'CoPetition',
      'dependent' => false,
      'foreignKey' => 'approver_co_person_id'
    ),
    "CoPetitionEnrollee" => array(
      'className' => 'CoPetition',
      // The only time we want to delete a petition when deleting a CO Person
      // is if person is the enrollee.
      'dependent' => true,
      'foreignKey' => 'enrollee_co_person_id'
    ),
    "CoPetitionPetitioner" => array(
      'className' => 'CoPetition',
      'dependent' => false,
      'foreignKey' => 'petitioner_co_person_id'
    ),
    "CoPetitionSponsor" => array(
      'className' => 'CoPetition',
      'dependent' => false,
      'foreignKey' => 'sponsor_co_person_id'
    ),
    // A person can be an actor on a petition and generate history
    "CoPetitionHistoryRecord" => array(
      'foreignKey' => 'actor_co_person_id'
    ),
    "CoTAndCAgreement" => array('dependent' => true),
    // A person can have one or more email address
    "EmailAddress" => array('dependent' => true),
    // We allow dependent=true for co_person_id but not for actor_co_person_id (see CO-404).
    "HistoryRecord" => array(
      'dependent' => true,
      'foreignKey' => 'co_person_id'
    ),
    "HistoryRecordActor" => array(
      'className' => 'HistoryRecord',
      'foreignKey' => 'actor_co_person_id'
    ),
    // A person can have many identifiers within a CO
    "Identifier" => array('dependent' => true),
    "Name" => array('dependent' => true),
    // Make this last so it doesn't get recreated by ProvisionerBehavior when
    // deleting a CO person
    "CoProvisioningExport" => array('dependent' => true),
    // A person can have one or more URL
    "Url" => array('dependent' => true),
  );

  // Default display field for cake generated views
  public $displayField = "PrimaryName.family";
  
  // Default ordering for find operations
// XXX CO-296 Toss default order?
//  public $order = array("CoPerson.id");
  
  // Validation rules for table elements
  // Validation rules must be named 'content' for petition dynamic rule adjustment
  public $validate = array(
    'co_id' => array(
      'content' => array(
        'rule' => 'numeric',
        'required' => true,
        'message' => 'A CO ID must be provided'
      )
    ),
    'status' => array(
      'content' => array(
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
        'required' => true,
        'message' => 'A valid status must be selected'
      )
    ),
    'timezone' => array(
      'content' => array(
        'rule' => array('validateTimeZone'),
        'required' => false,
        'allowEmpty' => true
      )
    ),
    'date_of_birth' => array(
      'content' => array(
        'rule' => array('date'),
        'required' => false,
        'allowEmpty' => true
      )
    )
  );
  
  // Enum type hints
  
  public $cm_enum_txt = array(
    'status' => 'en.status'
  );
  
  public $cm_enum_types = array(
    'status' => 'StatusEnum'
  );
  
  /**
   * Execute logic after a CO Person save operation.
   * For now manage membership of CO Person in members group.
   *
   * @since  COmanage Registry v0.9.3
   * @param  boolean true if a new record was created (rather than update)
   * @param  array, the same passed into Model::save()
   * @return none
   */
  
  public function afterSave($created, $options = array()) {
    if(isset($options['safeties']) && $options['safeties'] == 'off') {
      return true;
    }
    
    // Manage CO person membership in the CO members group.
    // This is similar to CoPersonRole::reconcileCouMembersGroupMemberships.
    
    $eligible = false;
    $isMember = false;
    
    $provision = (isset($options['provision']) ? $options['provision'] : true);
    
    $coPersonId = $this->id;
    $coId = $this->field('co_id');
    $status = $this->field('status');
    // This is similar logic to CoPersonRole and CoGroup::reconcileAutomaticGroup
    $activeEligible = ($status == StatusEnum::Active || $status == StatusEnum::GracePeriod);
    $allEligible = ($status != StatusEnum::Deleted);
    
    $this->CoGroupMember->syncMembership(GroupEnum::ActiveMembers, null, $coPersonId, $activeEligible, $provision);
    $this->CoGroupMember->syncMembership(GroupEnum::AllMembers, null, $coPersonId, $allEligible, $provision);
    
    // We also need to update any COU members groups. Start by pulling the list of COUs,
    // in id => name format.
    
    $cous = $this->Co->Cou->allCous($coId);
    
    if(!empty($cous)) {
      // We need to pull the COUs this CO Person has a role for (even if not active).
      // If the Person is not active, then we'll also remove the Role active group(s).
      
      $args = array();
      $args['conditions']['CoPersonRole.co_person_id'] = $coPersonId;
      $args['conditions'][] = 'CoPersonRole.cou_id IS NOT NULL';
      // If a person has more than one COU membership, we'll lose all but one of the
      // role IDs, but currently we don't need them.
      $args['fields'] = array('CoPersonRole.cou_id', 'CoPersonRole.id');
      $args['contain'] = false;
      
      $roles = $this->CoPersonRole->find('list', $args);
      
      // Walk the COUs
      
      foreach($cous as $couId => $couName) {
        // If the CO Person is not $allEligible, then no COU groups are eligible either.
        if($allEligible && !empty($roles[$couId])) {
          // The CO Person has at least one role in this COU, so let CoPersonRole sync things.
          // Note we only need to do this once per COU, as reconcileCouMembersGroupMemberships
          // will correctly handle multiple roles in the same COU.
          
          $this->CoPersonRole->reconcileCouMembersGroupMemberships($roles[$couId],
                                                                   $this->CoPersonRole->alias,
                                                                   $provision,
                                                                   $activeEligible);
        } else {
          // Make sure there are no memberships for this COU
          
          $this->CoGroupMember->syncMembership(GroupEnum::ActiveMembers, $couId, $coPersonId, false, $provision);
          $this->CoGroupMember->syncMembership(GroupEnum::AllMembers, $couId, $coPersonId, false, $provision);
        }
      }
    }
  }
  
  /**
   * Cascades model deletes through associated hasMany and hasOne child records.
   * This is based on lib/Cake/Model/Model.php:_deleteDependent, but modified
   * to disable provisioning. This could go in AppModel, but better would be to
   * upgrade to Cake 3, which supports $options in delete callbacks.
   *
   * @since  COmanage Registry v2.0.0
   * @param  string $id ID of record that was deleted
   * @return void
   */
	protected function deleteDependent($id) {
    // Manually trigger beforeDelete. AppModel::delete overrides standard
    // delete but doesn't trigger beforeDelete. That might be a bigger issue,
    // and maybe one to figure out as part of framework migration.
    $this->beforeDelete(true);

		if (!empty($this->__backAssociation)) {
			$savedAssociations = $this->__backAssociation;
			$this->__backAssociation = array();
		}

		foreach (array_merge($this->hasMany, $this->hasOne) as $assoc => $data) {
			if ($data['dependent'] !== true) {
				continue;
			}

			$Model = $this->{$assoc};

			if ($data['foreignKey'] === false && $data['conditions'] && in_array($this->name, $Model->getAssociated('belongsTo'))) {
				$Model->recursive = 0;
				$conditions = array($this->escapeField(null, $this->name) => $id);
			} else {
				$Model->recursive = -1;
				$conditions = array($Model->escapeField($data['foreignKey']) => $id);
				if ($data['conditions']) {
					$conditions = array_merge((array)$data['conditions'], $conditions);
				}
			}

      $Model->_provision = false;
			if (isset($data['exclusive']) && $data['exclusive']) {
				$Model->deleteAll($conditions);
			} else {
				$records = $Model->find('all', array(
					'conditions' => $conditions, 'fields' => $Model->primaryKey
				));
        
        if (!empty($records)) {
          foreach ($records as $record) {
            $currentRecord = $Model->find('first', array(
              'conditions' => array('id' => $record[$Model->alias][$Model->primaryKey])
            ));
            if (isset(current($currentRecord)['deleted']) && current($currentRecord)['deleted'] != true) {
              $Model->delete($record[$Model->alias][$Model->primaryKey]);
            }
					}
				}
			}
      $Model->_provision = true;
		}

		if (isset($savedAssociations)) {
			$this->__backAssociation = $savedAssociations;
		}
	}
  
  /**
   * Completely purge a CO Person. This will cascade deletes past where normal
   * relations would permit, and update history and notifications where the CO Person
   * has a role beyond subject.
   *
   * @since  COmanage Registry v0.8.5
   * @param  integer Identifier of CO Person
   * @param  integer Identifier of CO Person performing expunge
   * @return boolean True on success
   * @throws InvalidArgumentException
   */
  
  public function expunge($coPersonId, $expungerCoPersonId) {
    $coperson = $this->findForExpunge($coPersonId);
    
    if(!$coperson) {
      throw new InvalidArgumentException(_txt('er.cop.unk-a', array($coPersonId)));
    }
    
    // Dynamically bind extended attributes
    
    $c = $this->Co->CoExtendedAttribute->find('count',
                                              array('conditions' =>
                                                    array('co_id' => $coperson['CoPerson']['co_id'])));
    
    if($c > 0) {
      $cl = 'Co' . $coperson['CoPerson']['co_id'] . 'PersonExtendedAttribute';
      
      $this->CoPersonRole->bindModel(array('hasOne' =>
                                           array($cl => array('className' => $cl,
                                                              'dependent' => true))),
                                     false);
    }
    
    // Start a transaction
    $dbc = $this->getDataSource();
    $dbc->begin();
    
    // Set the person to Deleted to prevent notification errors as the expunge is processed.
    
    // As of v2.0.0, we disable provisioning after this save. Note that some models
    // are not ProvisionerBehavior-enabled (eg: CoNotification, CoOrgIdentityLink,
    // HistoryRecord, OrgIdentity), and so there is no need to explicitly disable
    // provisioning for these.
    
    $this->id = $coPersonId;
    
    // We don't provision here since afterSave will recalculate members groups,
    // retriggering provision (and potentially reprovisioning).
    
    // Don't update Person status to Deleted since deleting the roles (via deleteDependent)
    // may recalculate it back to Active, in turn creating additional group memberships
    // that then need to be re-deleted.
//    $this->saveField('status', StatusEnum::Deleted, array('provision' => false));
    
    // Rewrite any Notification where this person is an actor, recipient, or resolver
    
    foreach($coperson['CoNotificationActor'] as $n) {
      $this->CoNotificationActor->expungeParticipant($n['id'], 'actor', $expungerCoPersonId);
    }
    
    foreach($coperson['CoNotificationRecipient'] as $n) {
      $this->CoNotificationActor->expungeParticipant($n['id'], 'recipient', $expungerCoPersonId);
    }
    
    foreach($coperson['CoNotificationResolver'] as $n) {
      $this->CoNotificationActor->expungeParticipant($n['id'], 'resolver', $expungerCoPersonId);
    }
    
    // Rewrite any History Records where this person is an actor but not a recipient
    // (since those will be purged shortly anyway)
    
    foreach($coperson['HistoryRecordActor'] as $h) {
      if($h['co_person_id'] != $coPersonId) {
        $this->HistoryRecord->expungeActor($h['id'], $expungerCoPersonId);
      }
    }
    
    // Manually delete org identities since they will not cascade via org identity link.
    // Only do this where there are no other CO People linked to the org identity.
    // Note we're walking two links here... the first is all Org Identities attached
    // to the current CO Person, then the second is all CO People attached to each
    // of those Org Identities.
    
    // We need to do this before deleting the CO Person due to some deep Cake error
    // when selecting the dependency data related to the Org Identity to prepare for
    // deletion generating an invalid SELECT statement and throwing an error.
    
    foreach($coperson['CoOrgIdentityLink'] as $lnk) {
      if(count($lnk['OrgIdentity']['CoOrgIdentityLink']) <= 1) {
        if(!empty($lnk['OrgIdentity']['CoOrgIdentityLink'][0]['id'])) {
          // We need to manually remove this link since it hasn't been removed via
          // the CO Person record yet.
          $this->CoOrgIdentityLink->delete($lnk['OrgIdentity']['CoOrgIdentityLink'][0]['id']);
        }
        
        $this->CoOrgIdentityLink->OrgIdentity->delete($lnk['OrgIdentity']['id']);
      }
    }
    
    // Delete the CO Person. Note that normally (CoPeopleController:checkDeleteDependencies)
    // we verify that each COU the CO Person belongs to can be admin'd by the currently authenticated
    // CO Person. However, at the moment CO People can only be deleted by CO and CMP admins, so there
    // is no need for this check.
    
    // We first delete all dependencies and then delete the CO Person itself (again with cascading
    // to dependencies). The reason for this is that, depending on what order Cake deletes the
    // dependencies in, new history records might be created for the CO Person as a side effect of
    // the delete (typically because provisioning fires off). After _deleteDependent, we should be
    // left with only minimal new residue which the normal delete() will clean up.
    
    $this->deleteDependent($coPersonId);

    // We want this delete to (de)provision
    $this->delete($coPersonId);
    
    // Need to check if there was an error since we can't see if something failed
    // with provisioners. Note this only catches SQL issues, not general provisioner errors.
    if($dbc->lastError() != null) {
      throw new RuntimeException($dbc->lastError());
    }
    
    $dbc->commit();
    
    return true;
  }
  
  /**
   * Filter the results from a People Picker find, based on the search mode.
   *
   * @since  COmanage Registry v3.3.0
   * @param  integer $coId        CO ID
   * @param  integer $coPersonIds Array of CO Person IDs to filter
   * @param  string  $mode        Search mode to apply filters fore
   * @return array                Array of filtered CO Person records
   */
  
  public function filterPicker($coId, $coPersonIds, $mode) {
    $ret = array();
    
    if(!empty($coPersonIds)) {
      $args = array();
      if($mode == PeoplePickerModeEnum::Sponsor) {
        // Build Sponsor conditions first and then add to them
        try {
          // sponsorFilter will filter Active records
          $args = $this->sponsorFilter($coId);
        }
        catch(InvalidArgumentException $e) {
          // Sponsors are disabled, so return no results
          return $ret;
        }
      }
      $args['conditions']['CoPerson.id'] = $coPersonIds;
      $args['contain'] = array('PrimaryName', 'Identifier', 'EmailAddress');
      $args['order'] = array('PrimaryName.family ASC', 'PrimaryName.given ASC');
      
      $ret = $this->find('all', $args);
    }
    
    return $ret;
  }
  
  /**
   * Perform a find for a CO Person, but pull exactly the associated data needed
   * for an expunge operation.
   *
   * @since  COmanage Registry v0.8.5
   * @param  Integer CO Person ID
   * @return Array CoPerson information, as returned by find (with some associated data)
   */
  
  public function findForExpunge($coPersonId) {
    $args = array();
    $args['conditions']['CoPerson.id'] = $coPersonId;
    $args['contain'][] = 'PrimaryName';
    $args['contain'][] = 'Co';
    $args['contain'][] = 'CoPersonRole';
    $args['contain']['CoPersonRole'][] = 'Cou';
    $args['contain'][] = 'CoOrgIdentityLink';
    $args['contain']['CoOrgIdentityLink'][] = 'OrgIdentity';
    // This next line pulls all links for the OrgIdentity, not just the one related to this CO Person
    $args['contain']['CoOrgIdentityLink']['OrgIdentity'][] = 'CoOrgIdentityLink';
    $args['contain']['CoOrgIdentityLink']['OrgIdentity'][] = 'Identifier';
    $args['contain']['CoOrgIdentityLink']['OrgIdentity'][] = 'PrimaryName';
    $args['contain'][] = 'CoNotificationActor';
    $args['contain'][] = 'CoNotificationRecipient';
    $args['contain'][] = 'CoNotificationResolver';
    $args['contain'][] = 'HistoryRecordActor';
    
    return $this->find('first', $args);
  }
  
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
    // Only pull currently valid group memberships
    $args['conditions']['AND'][] = array(
      'OR' => array(
        'CoGroupMember.valid_from IS NULL',
        'CoGroupMember.valid_from < ' => date('Y-m-d H:i:s', time())
      )
    );
    $args['conditions']['AND'][] = array(
      'OR' => array(
        'CoGroupMember.valid_through IS NULL',
        'CoGroupMember.valid_through > ' => date('Y-m-d H:i:s', time())
      )
    );
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
    
    return (!empty($coPersonIds[0]) ? $coPersonIds[0] : null);
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
    $ret = array();
    
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
    
    // We might get more than one record, especially if no CO ID and/or type was specified.
    
    $ids = $this->Identifier->find('all', $args);
    
    if(!empty($ids)) {
      foreach($ids as $i) {
        if(isset($i['Identifier']['co_person_id'])) {
          // The identifier is attached to a CO Person, return that ID.
          
          $ret[] = $i['Identifier']['co_person_id'];
        } else {
          // Map the org identity to a CO person. We might pull more than one.
          // In this case, it's OK since they come back to the same org person.
          
          $args = array();
          $args['conditions']['CoOrgIdentityLink.org_identity_id'] = $i['Identifier']['org_identity_id'];
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
            foreach(array_values($links) as $v) {
              $ret[] = $v;
            }
          } else {
            // We don't want to throw an error here because we don't want a single
            // OrgIdentity without a corresponding CoPerson to prevent other CoPeople
            // from being returned.
//            throw new InvalidArgumentException(_txt('er.cop.unk'));
          }
        }
      }
    } else {
      throw new InvalidArgumentException(_txt('er.id.unk'));
    }
    
    return $ret;
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
    $args = array();
    $args['joins'] = array();

    foreach($criteria as $mdl => $queryParams) {
      // Add the conditions
      foreach($queryParams as $field => $value){
        $args['conditions']['LOWER(' . $mdl . '.' . $field . ') LIKE'] = strtolower($value) . '%';
      }
      // Join the table to the main model
      $args_tmp = array();
      $args_tmp['table'] = Inflector::tableize($mdl);
      $args_tmp['alias'] = $mdl;
      $args_tmp['type'] = 'INNER';
      $args_tmp['conditions'][0] = 'CoPerson.id=' . $mdl . '.co_person_id';
      array_push($args['joins'], $args_tmp);
      unset($args_tmp);
    }

    if(!empty($args)) {
      $args['conditions']['CoPerson.co_id'] = $coId;
      $args['contain'][] = 'PrimaryName';
      $args['contain'][] = 'CoPersonRole';
    } else {
      return [];
    }

    return $this->find('all', $args);
  }

   /**
   * Generate the set of querable fields for CoPerson REST API.
   * The returned array should be of the form requestField => Model.Field
   * Where the requestField is the query/name parameter we got from the request and
   * the Model.Field is the actual Model and Field we need to query
   *
   * @since  COmanage Registry v3.3.0
   * @return Array As specified
   */

  public function querableFields() {
    return array(
      'given'    => 'Name.given',
      'family'   => 'Name.family',
      'mail'     => 'EmailAddress.mail'
    );
  }

  /**
   * Validate the field values from the request
   *
   * @since  COmanage Registry v3.3.0
   * @param  Array The array of the query/named parameters from the request
   * @return Array Criteria array formated properly for the needs of function match().
   *         If nothing matched we return an empty array.
   */
  public function validateRequestData($request) {
    $criteria = array();
    $invalidFields = array();
    $unProcessedFields = array();

    foreach ($request as $field => $value) {
      if ($field === 'coid') {
        continue;
      }
      // Find any fields that are not defined in the querable List of Fields
      if(!array_key_exists($field, $this->querableFields())) {
        $unProcessedFields[$field] = $value;
        continue;
      }
      // Any field must be at list 3 characters long
      // The email field must be validated
      if(strlen($value) < 3
         || ($field === "mail" && !Validation::email($value))) {
        $invalidFields[$field] = $value;
        continue;
      }
      // Create the criteria
      $mdlField = explode('.', $this->querableFields()[$field]);
      $criteria[$mdlField[0]][$mdlField[1]] = $value;
    }

    return array($criteria, sizeof($invalidFields), sizeof($unProcessedFields));
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
   * Recalculate the status of a CO Person based on the attached CO Person Roles.
   *
   * @since  COmanage Registry v0.9.2
   * @param  Integer $id CO Person ID
   * @param  Boolean $provision Whether to run provisioners
   * @return StatusEnum New status
   * @throws RuntimeException
   */
  
  public function recalculateStatus($id, $provision=true) {
    $newStatus = null;
    
    // We rank status by "preference". More "preferred" statuses rank higher.
    // To faciliate comparison, we'll convert this to an integer value and store
    // it in a hash. Most preferred numbers are larger so we can say things like
    // Active > Expired. Possibly this should go somewhere else, if useful. (CO-1360)
    
    $statusRanks = array(
      // Active statuses are most preferred
      StatusEnum::Active                => 14,
      StatusEnum::GracePeriod           => 13,
      
      // Next come expired statuses, since there may be provisioned skeletal records
      // that need to be maintained
      StatusEnum::Suspended             => 12,
      StatusEnum::Expired               => 11,
      
      // Then invitation statuses
      StatusEnum::Approved              => 10,
      StatusEnum::PendingApproval       => 9,
      StatusEnum::Confirmed             => 8,
      StatusEnum::PendingConfirmation   => 7,
      StatusEnum::Invited               => 6,
      StatusEnum::Pending               => 5,  // It's not clear this is used for anything
      
      // Denied and Declined are below expired since other roles are more likely to have been used
      StatusEnum::Denied                => 4,
      StatusEnum::Declined              => 3,
      
      // Finally, we generally don't want Deleted or Duplicate unless all roles are deleted or duplicates
      StatusEnum::Deleted               => 2,
      StatusEnum::Duplicate             => 1
    );
    
    // Start by pulling the roles for this person
    
    $args = array();
    $args['conditions']['CoPersonRole.co_person_id'] = $id;
    $args['contain'] = false;
    
    $roles = $this->CoPersonRole->find('all', $args);
    
    foreach($roles as $role) {
      if(!$newStatus) {
        // This is the first role, just set the new status to it
        
        $newStatus = $role['CoPersonRole']['status'];
      } else {
        // Check if this role's status is more preferable than the current status
        
        if($statusRanks[ $role['CoPersonRole']['status'] ] > $statusRanks[$newStatus]) {
          $newStatus = $role['CoPersonRole']['status'];
        }
      }
    }
    
    if($newStatus) {
      $this->id = $id;
      
      // Pull the current value
      $curStatus = $this->field('status');
      
      if($newStatus != $curStatus) {
        $coId = $this->field('co_id');
        
        // Update the CO Person status
        $this->saveField('status', $newStatus, array('provision' => $provision));
        
        // Record history
        try {
          $this->HistoryRecord->record($role['CoPersonRole']['co_person_id'],
                                       null,
                                       null,
                                       null,
                                       ActionEnum::CoPersonStatusRecalculated,
                                       _txt('rs.cop.recalc',
                                            array(_txt('en.status', null, $newStatus))));
        }
        catch(Exception $e) {
          throw new RuntimeException($e->getMessage());
        }
      }
      // else nothing to do, status is unchanged
    }
    // else no roles, leave status unchanged
    
    return $newStatus;
  }
  
  /**
   * Construct a set of find() args to filter People Picker records for
   * Sponsor eligibility ("Sponsor Mode").
   *
   * @since  COmanage Registry v3.3.0
   * @param  integer $coId CO ID
   * @return array         Array of find conditions
   * @throws InvalidArgumentException
   */
  
  protected function sponsorFilter($coId) {
    $ret = array();
    
    // For eligibility by group(s), the group IDs to check
    $groupIds = array();
    
    // First we need the current setting(s).
    $mode = $this->Co->CoSetting->getSponsorEligibility($coId);
    
    // Similar logic below in sponsorList()
    switch($mode) {
      case SponsorEligibilityEnum::CoOrCouAdmin:
        // First pull the list of COUs
        // XXX we could probably optimize this for large number of COUs by adding a call
        //     to pull where group_type=GroupEnum::Admins and cou_id is NOT NULL
        $cous = $this->Co->Cou->allcous($coId, "ids");
        
        foreach($cous as $couId) {
          // Find the admin group ID
          $groupIds[] = $this->Co->CoGroup->adminCoGroupId($coId, $couId);
        }
        // Fall through, we want the CO Admin group as well
      case SponsorEligibilityEnum::CoAdmin:
        // Find the admin group ID
        $groupIds[] = $this->Co->CoGroup->adminCoGroupId($coId);
        break; 
      case SponsorEligibilityEnum::CoGroupMember:
        // Find the configured group
        $groupId = $this->Co->CoSetting->getSponsorEligibilityCoGroup($coId);
        
        if($groupId) {
          $groupIds[] = $groupId;
        }
        break;
      case SponsorEligibilityEnum::CoPerson:
        // This setting only includes Active CO People
        $ret['conditions']['CoPerson.status'] = StatusEnum::Active;
        break;
      case SponsorEligibilityEnum::None:
        throw new InvalidArgumentException('No sponsors');
        break;
    }
    
    if(!empty($groupIds)) {
      // Build query conditions to restrict the sponsor search to the identified
      // CO Group IDs
      
      $ret['joins'][0]['table'] = 'co_group_members';
      $ret['joins'][0]['alias'] = 'CoGroupMember';
      $ret['joins'][0]['type'] = 'INNER';
      $ret['joins'][0]['conditions'][0] = 'CoPerson.id=CoGroupMember.co_person_id';
      $ret['conditions'][] = 'CoGroupMember.co_group_member_id IS NULL';
      $ret['conditions'][] = 'CoGroupMember.deleted IS NOT true';
      $ret['conditions']['CoGroupMember.co_group_id'] = $groupIds;
      
      // Only pull currently valid group memberships
      $ret['conditions']['AND'][] = array(
        'OR' => array(
          'CoGroupMember.valid_from IS NULL',
          'CoGroupMember.valid_from < ' => date('Y-m-d H:i:s', time())
        )
      );
      $ret['conditions']['AND'][] = array(
        'OR' => array(
          'CoGroupMember.valid_through IS NULL',
          'CoGroupMember.valid_through > ' => date('Y-m-d H:i:s', time())
        )
      );
    }
    
    return $ret;
  }
  
  /**
   * Retrieve list of sponsors for display in dropdown. This function will throw
   * an OverflowException when there are too many potential sponsors to populate
   * a select list, and so the People Picker should be used instead.
   *
   * @since  COmanage Registry v0.3
   * @param  integer CO ID
   * @return Array   Array with co_person id as keys and full name as values; array will be empty if sponsoring is disabled
   * @throws OverflowException
   */
  
  public function sponsorList($coId) {
    $ret = array();
    
    // For eligibility by group(s), the group IDs to check
    $groupIds = array();
    
    // First we need the current setting(s).
    $mode = $this->Co->CoSetting->getSponsorEligibility($coId);
    
    switch($mode) {
      case SponsorEligibilityEnum::CoOrCouAdmin:
        // First pull the list of COUs
        $cous = $this->Co->Cou->allcous($coId, "ids");
        
        foreach($cous as $couId) {
          // Find the admin group ID
          $groupIds[] = $this->Co->CoGroup->adminCoGroupId($coId, $couId);
        }
        // Fall through, we want the CO Admin group as well
      case SponsorEligibilityEnum::CoAdmin:
        // Find the admin group ID
        $groupIds[] = $this->Co->CoGroup->adminCoGroupId($coId);
        break;
      case SponsorEligibilityEnum::CoGroupMember:
        // Find the configured group
        $groupId = $this->Co->CoSetting->getSponsorEligibilityCoGroup($coId);
        
        if($groupId) {
          $groupIds[] = $groupId;
        }
        break;
      case SponsorEligibilityEnum::CoPerson:
        // Any Active CO Person may be a sponsor
        $args = array();
        $args['conditions']['CoPerson.co_id'] = $coId;
        $args['conditions']['CoPerson.status'] = StatusEnum::Active;
        
        // If we have more than 50 records, disable enumeration and require
        // use of the people picker
        if($this->find('count', $args) > 50) {
          throw new OverflowException("Use People Picker");
        }
        
        $args['contain'][] = 'PrimaryName';
        $args['order'] = array('PrimaryName.family ASC');
        
        $people = $this->find('all', $args);
        
        // Assemble the list, using generateCn
        foreach($people as $p) {
          $ret[ $p['CoPerson']['id'] ] = generateCn($p['PrimaryName']);
        }
        break;
      case SponsorEligibilityEnum::None:
        // Just return an empty array
        break;
      default:
        throw new InvalidArgumentException(_txt('er.unknown', $mode));
        break;
    }
    
    if(!empty($groupIds)) {
      // Find the Active people in the group
      $args = array();
      $args['conditions']['CoGroupMember.co_group_id'] = $groupIds;
      $args['conditions']['CoPerson.status'] = StatusEnum::Active;
      // Only pull currently valid group memberships
      $args['conditions']['AND'][] = array(
        'OR' => array(
          'CoGroupMember.valid_from IS NULL',
          'CoGroupMember.valid_from < ' => date('Y-m-d H:i:s', time())
        )
      );
      $args['conditions']['AND'][] = array(
        'OR' => array(
          'CoGroupMember.valid_through IS NULL',
          'CoGroupMember.valid_through > ' => date('Y-m-d H:i:s', time())
        )
      );
      
      if($this->CoGroupMember->find('count', $args) > 50) {
        throw new OverflowException("Use People Picker");
      }
      
      $args['contain']['CoPerson'] = 'PrimaryName';
      
      $members = $this->CoGroupMember->find('all', $args);
      
      // Sort the results by last name
      $sorted = Hash::sort($members, '{n}.CoPerson.PrimaryName.family', 'asc');
      
      // And finally key the results. This will also eliminate dupes (by overwriting the same key).
      foreach($sorted as $s) {
        $ret[ $s['CoPerson']['id'] ] = generateCn($s['CoPerson']['PrimaryName']);
      }
    }
    
    return $ret;
  }
  
  /**
   * Timezone validation.
   *
   * @since  COmanage Registry v1.0.0
   * @return Boolean True if valid timezone provided, false otherwise
   */
  
  public function validateTimeZone($check) {
    return in_array($check['timezone'], array_values(timezone_identifiers_list()));
  }
}
