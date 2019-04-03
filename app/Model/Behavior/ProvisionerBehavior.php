<?php
/**
 * COmanage Registry Provisioner Behavior
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
 * @since         COmanage Registry v0.8
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

// Behaviors don't have access to sessions by default
App::uses('CakeSession', 'Model/Datasource');

class ProvisionerBehavior extends ModelBehavior {
  /**
   * Specify which statuses provision which type of data
   */
  
  protected $groupStatuses = array(
    StatusEnum::Active,
    StatusEnum::GracePeriod
  );
  
  protected $personStatuses = array(
    StatusEnum::Active,
    StatusEnum::Expired,
    StatusEnum::GracePeriod,
    StatusEnum::Suspended
  );
  
  protected $roleStatuses = array(
    StatusEnum::Active,
    StatusEnum::GracePeriod
  );
  
  /**
   * Handle provisioning following delete of Model.
   *
   * @since  COmanage Registry v0.8
   * @param  Model $model Model instance.
   * @return boolean true on success, false on failure
   */
  
  public function afterDelete(Model $model) {
    // Because Cake 2 doesn't support $options on delete callbacks, we need
    // a hack to determine provisioning. Cake 3 supports $options.
    if(isset($model->_provision) && $model->_provision === false) {
      // The save requested we skip provisioning
      return true;
    }
    
    // Note that in most cases this is just an edit. ie: deleting a telephone number is
    // CoPersonUpdated not CoPersonDeleted. In those cases, we can just call afterSave.
    
    if($model->name != 'CoPerson' && $model->name != 'CoGroup') {
      if($model->name == 'CoEmailList') {
        // We do want an explicit Delete operation for CoEmailList
        $model->data = $model->cacheData;
        
        return $this->determineProvisioning($model, false, ProvisioningActionEnum::CoEmailListDeleted);
      }
      
      if($model->name == 'CoGroupMember') {
        // For CoGroupMember, we need to restore the model data to have access to
        // the CoPerson and CoGroup we need to rewrite. (CO-663)
        
        $model->data = $model->cacheData;
      }
      
      return $this->afterSave($model, false);
    }
    
    return true;
  }
  
  /**
   * Handle provisioning following save of Model.
   *
   * @since  COmanage Registry v0.8
   * @param  Model $model Model instance
   * @param  boolean $created indicates whether the node just saved was created or updated
   * @return boolean true on success, false on failure
   * @throws InvalidArgumentException
   * @throws RuntimeException
   * @todo   Don't throw exceptions, since that breaks the REST API
   */
  
  public function afterSave(Model $model, $created, $options = array()) {
    if(isset($options['provision']) && $options['provision'] === false) {
      // The save requested we skip provisioning
      return true;
    }
    
    return $this->determineProvisioning($model, $created);
  }
  
  /**
   * Handle provisioning following (before) delete of Model.
   *
   * @since  COmanage Registry v0.8
   * @param  Model $model Model instance.
   * @return boolean true on success, false on failure
   */
  
  public function beforeDelete(Model $model, $cascade = true) {
    // Because Cake 2 doesn't support $options on delete callbacks, we need
    // a hack to determine provisioning. Cake 3 supports $options.
    if(isset($model->_provision) && $model->_provision === false) {
      // The save requested we skip provisioning
      return true;
    }
    
    // Note that in most cases this is just an edit. ie: deleting a telephone number is
    // CoPersonUpdated not CoPersonDeleted. However, in those cases we don't want to
    // process anything until afterDelete().
    
    // We will generally cache the data prior to delete in case we want to do
    // something interesting with it in afterDelete. This includes when a CoGroupMember
    // is removed, we need to know which CoPerson and CoGroup to rewrite, and we have to
    // do that in afterDelete (so the CoGroupMember doesn't show up anymore) (CO-663).
    
    // Always reread $model data to make sure we're handling delete of multiple
    // instances of the same model. StandardController::delete will often call
    // read(), so we might be doing some extra work in non-cascading deletes.
    
    $model->read();
    
    $model->cacheData = $model->data;
    
    if($model->name == 'CoGroup' || $model->name == 'CoPerson') {
      // Deleting a CoPerson or CoGroup needs to be handled specially.
      // We need to invoke the provisioners before the final model is deleted
      // so provisioners can manually clean up any database associations before
      // the delete of the final model.
      
      if(!empty($model->data[ $model->name ]['id'])) {
        // Invoke all provisioning plugins
        
        try {
          $this->invokePlugins($model,
                               $model->data,
                               $model->name == 'CoPerson'
                               ? ProvisioningActionEnum::CoPersonDeleted
                               : ProvisioningActionEnum::CoGroupDeleted);
        }    
        // What we really want to do here is catch the result (success or exception)
        // and set the appropriate session flash message, but we don't have access to
        // the current session, and anyway that doesn't cover RESTful interactions.
        // So instead we syslog (which is better than nothing).
        catch(InvalidArgumentException $e) {
          syslog(LOG_ERR, $e->getMessage());
          //throw new InvalidArgumentException($e->getMessage());
        }
        catch(RuntimeException $e) {
          syslog(LOG_ERR, $e->getMessage());
          //throw new RuntimeException($e->getMessage());
        }
      }
    }
    
    return true;
  }
  
  /**
   * Handle provisioning following (before) save of Model.
   *
   * @since  COmanage Registry v0.9
   * @param  Model $model Model instance.
   * @return boolean true on success, false on failure
   */
  
  public function beforeSave(Model $model, $options = array()) {
    // Cache a copy of the current data for comparison in afterSave. Currently only
    // used to detect if a person or group goes to or from Active status.
    
    if(($model->name == 'CoGroup'
        || $model->name == 'CoPerson'
        || $model->name == 'Identifier')
       // This will only be set on edit, not add
       && !empty($model->data[ $model->alias ]['id'])) {
      $args = array();
      $args['conditions'][ $model->alias.'.id'] = $model->data[ $model->alias ]['id'];
      $args['contain'] = false;
      
      $model->cacheData = $model->find('first', $args);
    }
    
    return true;
  }
  
  /**
   * Determine (and invoke) what processing is required based on the provisioning data
   * provided.
   *
   * @param  Model $model Model instance
   * @param  boolean $created indicates whether the node just saved was created or updated
   * @param  ProvisioningActionEnum $provisioningAction Provisioning action to pass to plugins
   * @return $return boolean true on success
   * @throws InvalidArgumentException
   */
  
  protected function determineProvisioning(Model $model, $created, $provisioningAction=null) {
    $pmodel = null;
    $pdata = null;
    $paction = null;

    // For our initial implementation, one of the following must be true for $model:
    //  - The model is CoEmailAddress
    //  - The model is CoGroup
    //  - The model is CoPerson
    //  - The model belongs to CoPerson, and co_person_id is set
    //  - The model belongs to CoPersonRole, and co_person_role_id is set
    //
    // If the model is CoGroupMember, both CoGroup and CoPerson provisioning are triggered.
    // Note: this is likely triggering extra work, since we'll get a pattern like (in LdapProvisioner)
    //  1 - Delete Group
    //  2 - Delete Group Member
    //  3 - Rewrite Group
    //  4 - Group does not exist, promote to Add
    //  5 - Add fails because group has no members
    //  6 - Rewrite Person
    // We should be able to skip 3 - 5, but to do so we'd need to know we were called
    // because a Group was deleted and not because (say) a Person was deleted, and at
    // the moment we don't have a way to do that.
    //
    // If the model is CoPerson or CoGroup and the status went to or from Active, we need
    // to rewrite group memberships under both the person and all their groups (or under
    // the group and all its people).
    
    if($model->name == 'CoEmailList') {
      // We can short-circuit the bulk of logic here
      
      // XXX note we only need to look at $id right now. if we need other attributes, merge
      // back into logic below (which handle eg saveField)
      $this->provisionEmailLists($model, array($model->id), $created, $provisioningAction);
      
      return true;
    }
    
    $syncGroups = false;
    
    // Track which group or groups need to be rewritten.
    $coGroupIds = array();
    // For a group update triggered by a person update (eg: person status change), this is
    // the subject CO Person ID (not to be confused with $coPersonId, used below)
    $copid = null;
    $gmodel = null;
    
    // If we were called with saveField() or via manualProvisioning, $model->data will be likely be empty.
    // We need to load it.
    
    if(empty($model->data)
       && $provisioningAction != ProvisioningActionEnum::CoPersonDeleted) {
      $args = array();
      $args['conditions'][$model->alias.'.id'] = $model->id;
      $args['contain'] = false;

      try {
        $model->data = $model->find('first', $args);
      }
      catch(Exception $e) {
        // XXX We should really report this somehow
        return;
      }
    }
    
    // If a CO Person status has changed to or from a status that triggers group provisioning,
    // find all the groups with which that person has an association and rewrite them.
    
    if($model->name == 'CoPerson') {
      if(isset($model->cacheData[ $model->alias ]['status'])
         && $model->cacheData[ $model->alias ]['status'] != $model->data[ $model->alias ]['status']
         && (in_array($model->cacheData[ $model->alias ]['status'], $this->groupStatuses)
             || in_array($model->data[ $model->alias ]['status'], $this->groupStatuses))) {
        // We have a CO Person status change to or from a relevant status. Trigger a rewrite
        // of all groups of which the person is a member.
        
        $syncGroups = true;
        $gmodel = $model->CoGroupMember->CoGroup;
        $copid = $model->data[ $model->alias ]['id'];
      }
    }
    
    // If identifiers have changed, resync groups as well since group memberships
    // may be keyed on one of them.
    
    if($model->name == 'Identifier') {
      if(!empty($model->data['Identifier']['co_person_id'])) {
        if(!isset($model->cacheData['Identifier']['identifier'])
           && !empty($model->data['Identifier']['identifier'])) {
          $syncGroups = true;
        } elseif(!empty($model->cacheData['Identifier']['modified'])
                 && !empty($model->data['Identifier']['modified'])
                 && ($model->cacheData['Identifier']['modified']
                     != $model->data['Identifier']['modified'])) {
          // Use modified as a proxy for seeing if anything has changed in the record
          
          $syncGroups = true;
        }
        
        if($syncGroups) {
          $gmodel = $model->CoPerson->CoGroupMember->CoGroup;
          $copid = $model->data['Identifier']['co_person_id'];
        }
      } elseif(!empty($model->cacheData['Identifier']['co_person_id'])) {
        // Identifier was deleted
        $gmodel = $model->CoPerson->CoGroupMember->CoGroup;
        $copid = $model->cacheData['Identifier']['co_person_id'];
      }
    }
    
    if($syncGroups) {
      $args = array();
      $args['conditions']['CoGroupMember.co_person_id'] = $copid;
      $args['fields'] = array('CoGroupMember.id', 'CoGroupMember.co_group_id');
      $args['contain'] = false;
      
      $gms = $gmodel->CoGroupMember->find('list', $args);
      
      if(!empty($gms)) {
        $coGroupIds = array_values($gms);
      }
    }
    
    if($model->name == 'CoGroup' || $model->name == 'CoGroupMember') {
      // Find the group id
      
      if($model->name == 'CoGroup'
         && !empty($model->data['CoGroup']['id'])) {
        $gmodel = $model;
        $coGroupIds[] = $model->data['CoGroup']['id'];
      } elseif(!empty($model->data[ $model->name ]['co_group_id'])) {
        $gmodel = $model->CoGroup;
        $coGroupIds[] = $model->data[ $model->name ]['co_group_id'];
        
        if(!empty($model->data[ $model->name ]['co_person_id'])) {
          // We need to pass the CO Person ID to marshallCoGroupData
          $copid = $model->data[ $model->name ]['co_person_id'];
        }
      } elseif(!empty($model->cacheData[ $model->name ]['co_group_id'])) {
        // eg: CoGroupMember deleted
        $gmodel = $model->CoGroup;
        $coGroupIds[] = $model->cacheData[ $model->name ]['co_group_id'];
        
        if(!empty($model->cacheData[ $model->name ]['co_person_id'])) {
          // We need to pass the CO Person ID to marshallCoGroupData
          $copid = $model->cacheData[ $model->name ]['co_person_id'];
        }
      }
    }
    
    if($model->name != 'CoGroup'
       // If a group goes to or from Active, we need to rewrite its members (or really their group memberships)
       || (isset($model->cacheData[ $model->alias ]['status'])
           && $model->cacheData[ $model->alias ]['status'] != $model->data[ $model->alias ]['status']
           && ($model->cacheData[ $model->alias ]['status'] == StatusEnum::Active
               || $model->data[ $model->alias ]['status'] == StatusEnum::Active))) {
      // First, find the co_person_id (directly or indirectly) and pull the record.
      // We could have more than one if an Org Identity is updated.
      
      $coPersonIds = array();
      
      if($model->name == 'CoPerson'
         && !empty($model->data['CoPerson']['id'])) {
        $pmodel = $model;
        $coPersonIds[] = $model->data['CoPerson']['id'];
      } elseif($model->alias == 'EnrolleeCoPerson'
         && !empty($model->data['EnrolleeCoPerson']['id'])) {
        // Petitions present an EnrolleeCoPerson, not a CoPerson
        $pmodel = $model;
        $coPersonIds[] = $model->data['EnrolleeCoPerson']['id'];
      } elseif(!empty($model->data[ $model->name ]['co_person_id'])) {
        $pmodel = $model->CoPerson;
        $coPersonIds[] = $model->data[ $model->name ]['co_person_id'];
      } elseif(!empty($model->cacheData[ $model->name ]['co_person_id'])) {
        $pmodel = $model->CoPerson;
        $coPersonIds[] = $model->cacheData[ $model->name ]['co_person_id'];
      } elseif(!empty($model->data[ $model->name ]['co_person_role_id'])) {
        $pmodel = $model->CoPersonRole->CoPerson;
        $coPersonIds[] = $model->CoPersonRole->field('co_person_id',
                                                    array('id' => $model->data[ $model->name ]['co_person_role_id']));
      } elseif(!empty($model->cacheData[ $model->name ]['co_person_role_id'])) {
        $pmodel = $model->CoPersonRole->CoPerson;
        $coPersonIds[] = $model->CoPersonRole->field('co_person_id',
                                                    array('id' => $model->cacheData[ $model->name ]['co_person_role_id']));
      } elseif($model->name == 'CoPersonRole' && !empty($model->data['CoPersonRole']['id'])) {
        // eg: for saveField called via CoExpirationPolicy::executePolicies()
        $pmodel = $model->CoPerson;
        $coPersonIds[] = $model->field('co_person_id',
                                      array('id' => $model->data['CoPersonRole']['id']));
      } elseif($model->name == 'Identifier'
               &&
               ((!empty($model->data['Identifier']['org_identity_id'])
                 && empty($model->data['Identifier']['co_person_id']))
                || (!empty($model->cacheData['Identifier']['org_identity_id'])
                 && empty($model->cacheData['Identifier']['co_person_id'])))) {
        // Identifiers from an org record can be provisioned into a CO Person record.
        // We need to map from the Org Identity ID to a CO Person ID, but the tricky
        // part here is that an Org Identity can map into multiple CO People.
        
        $args = array();
        $args['conditions']['CoOrgIdentityLink.org_identity_id'] =
          (!empty($model->data['Identifier']['org_identity_id'])
           ? $model->data['Identifier']['org_identity_id']
           : $model->cacheData['Identifier']['org_identity_id']);
        $args['fields'] = array('CoOrgIdentityLink.org_identity_id', 'CoOrgIdentityLink.co_person_id');
        $args['contain'] = false;
        
        $cpids = $model->OrgIdentity->CoOrgIdentityLink->find("list", $args);
        
        if(!empty($cpids)) {
          $coPersonIds = array_values($cpids);
        }
        
        $pmodel = $model->CoPerson;
      } elseif($model->name == 'CoGroup') {
        // Find the members of the group
        
        $args = array();
        $args['conditions']['CoGroupMember.co_group_id'] = $model->data[ $model->alias ]['id'];
        $args['fields'] = array('CoGroupMember.id', 'CoGroupMember.co_person_id');
        $args['contain'] = false;
        
        $gms = $model->CoGroupMember->find('list', $args);
        
        if(!empty($gms)) {
          $coPersonIds = array_values($gms);
          $pmodel = $model->CoGroupMember->CoPerson;
        }
      } else {
        // For the moment, we'll just return true here since we may be processing
        // a multi-model transaction (eg: unlinking a dependency before deleting a
        // parent model) or we may be saving OrgIdentity data.
        
        return true;
      }
    }
    
    // We need to be careful about the order in which we provision people and groups,
    // since if a person's identifier changes we may need the provisioner to update
    // its references (eg: DNs) before the group updates fire, and vice versa.
    // The order depends on which model we were called via.
    
    if($model->name == 'CoGroup') {
      if($gmodel) {
        $this->provisionGroups($model, $gmodel, $coGroupIds, $created, $provisioningAction, $copid);
      }
      // else we could be CoGroupMember being promoted to afterSave in the middle of
      // a CoGroup being deleted. In that scenario, we don't actually need to try
      // to re-provision the CoGroup, so just move on to the person.
      if($pmodel) {
        $this->provisionPeople($model, $pmodel, $coPersonIds, $created, $provisioningAction);
      }
    } else {
      if($pmodel) {
        $this->provisionPeople($model, $pmodel, $coPersonIds, $created, $provisioningAction);
      }
      if($gmodel) {
        // If $provisioningAction is CoPersonReprovisionRequested, switch the action
        // to CoGroupReprovisionRequested so plugins get a more coherent action.
        
        $this->provisionGroups($model, 
                               $gmodel,
                               $coGroupIds, 
                               $created, 
                               ($provisioningAction == ProvisioningActionEnum::CoPersonReprovisionRequested
                                ? ProvisioningActionEnum::CoGroupReprovisionRequested
                                : $provisioningAction), 
                               $copid);
      }
    }
    
    return true;
  }
  
  /**
   * Invoke a provisioning plugin.
   *
   * @since  COmanage Registry v0.8
   * @param  Array $coProvisioningTarget Array of CoProvisioningTarget data, as returned by find()
   * @param  Array $provisioningData Data to pass to plugin, as returned by marshallCoPersonData() or marshallCoGroupData()
   * @param  ProvisioningActionEnum $action Action triggering provisioning
   * @return boolean true on success, false on failure
   * @throws InvalidArgumentException
   * @throws RuntimeException
   */
  
  private function invokePlugin($coProvisioningTarget, $provisioningData, $action) {
    $pAction = $action; // We might override the provided action
    
    // Before we do anything else, see if this plugin is configured to only operate
    // on a specified group. We do this here rather than in marshallXData because
    // here we have access to the plugin configuration, and the action change is not
    // too intrusive. If we need to do more sophisticated manipulation of the provisioning
    // data we should move this to marshallXData.
    
    // Note there is similar logic in View/Standard/provision.ctp.
    
    if(!empty($coProvisioningTarget['provision_co_group_id'])) {
      if(!empty($provisioningData['CoPerson']['id'])) { 
        // Is this person a member of that group? We should be able to do this in a single
        // Hash::check call using [co_group_id=$foo], but that doesn't seem to actually work...
        
        if(!in_array($coProvisioningTarget['provision_co_group_id'],
                     Hash::extract($provisioningData, 'CoGroupMember.{n}.co_group_id'))) {
          // Switch to a delete. We'll leave the provisioning data itself untouched.
          $pAction = ProvisioningActionEnum::CoPersonDeleted;
        }
      } elseif(!empty($provisioningData['CoGroup']['id'])
               // Is this group the configured group?
               && ($coProvisioningTarget['provision_co_group_id']
                   != $provisioningData['CoGroup']['id'])) {
        // Switch to a delete. We'll leave the provisioning data itself untouched.
        $pAction = ProvisioningActionEnum::CoGroupDeleted;
      }
    }
    
    // Perform a similar check to see if there is an associated Org Identity Source
    // record that indicates we should skip provisioning.
    
    if(!empty($coProvisioningTarget['skip_org_identity_source_id'])
       &&
       !empty($provisioningData['CoPerson']['id'])
       &&
       in_array($coProvisioningTarget['skip_org_identity_source_id'],
                Hash::extract($provisioningData, 'CoOrgIdentityLink.{n}.OrgIdentity.OrgIdentitySourceRecord.org_identity_source_id'))) {
      // Switch to a delete. We'll leave the provisioning data itself untouched.
      $pAction = ProvisioningActionEnum::CoGroupDeleted;
    }
    
    if(!empty($coProvisioningTarget['plugin'])) {
      $pluginName = $coProvisioningTarget['plugin'];
      $modelName = 'Co'. $pluginName . 'Target';
      $pluginModelName = $pluginName . "." . $modelName;
      
      // We probably need to manually attach the model, since the find()s in the invoking
      // functions aren't using containable. (Otherwise the find would automatically bind
      // these models under $this->CoProvisioningTarget).
      $pluginModel = ClassRegistry::init($pluginModelName);
      
      $args = array();
      $args['conditions'][$modelName.'.co_provisioning_target_id'] = $coProvisioningTarget['id'];
      $args['contain'] = false;
      
      $pluginTarget = $pluginModel->find('first', $args);
      
      if(!empty($pluginTarget)) {
        try {
          $pluginModel->provision($pluginTarget,
                                  $pAction,
                                  $provisioningData);
          
          // Create/update the export record, unless this is a delete operation
          // (in which case we're about to delete the entity, so creating a record
          // will interfere with the delete).
          
          if($pAction != ProvisioningActionEnum::CoEmailListDeleted
             && $pAction != ProvisioningActionEnum::CoGroupDeleted
             && $pAction != ProvisioningActionEnum::CoPersonDeleted) {
            $pluginModel->CoProvisioningTarget->CoProvisioningExport->record(
              $coProvisioningTarget['id'],
              !empty($provisioningData['CoPerson']['id']) ? $provisioningData['CoPerson']['id'] : null,
              !empty($provisioningData['CoGroup']['id']) ? $provisioningData['CoGroup']['id'] : null,
              !empty($provisioningData['CoEmailList']['id']) ? $provisioningData['CoEmailList']['id'] : null
            );
          }
          
          // Cut a history record if we're provisioning a record (and not deleting it).
          
          if(!empty($provisioningData['CoEmailList']['id'])
             && $pAction != ProvisioningActionEnum::CoEmailListDeleted) {
            // It's a bit of a walk to get to HistoryRecord
            $pluginModel->CoProvisioningTarget->Co->CoEmailList->HistoryRecord->record(
              null,
              null,
              null,
              CakeSession::read('Auth.User.co_person_id'),
              ($pAction == ProvisioningActionEnum::CoEmailListReprovisionRequested
               ? ActionEnum::CoEmailListManuallyProvisioned
               : ActionEnum::CoEmailListProvisioned),
              _txt('rs.prov-a', array($coProvisioningTarget['description'])),
              null,
              $provisioningData['CoEmailList']['id']
            );
          } elseif(!empty($provisioningData['CoGroup']['id'])
             && $pAction != ProvisioningActionEnum::CoGroupDeleted) {
            // It's a bit of a walk to get to HistoryRecord
            $pluginModel->CoProvisioningTarget->Co->CoGroup->HistoryRecord->record(
              null,
              null,
              null,
              CakeSession::read('Auth.User.co_person_id'),
              ($pAction == ProvisioningActionEnum::CoGroupReprovisionRequested
               ? ActionEnum::CoGroupManuallyProvisioned
               : ActionEnum::CoGroupProvisioned),
              _txt('rs.prov-a', array($coProvisioningTarget['description'])),
              $provisioningData['CoGroup']['id']
            );
          } elseif(!empty($provisioningData['CoPerson']['id'])
             && $pAction != ProvisioningActionEnum::CoPersonDeleted) {
            // It's a bit of a walk to get to HistoryRecord
            $pluginModel->CoProvisioningTarget->Co->CoPerson->HistoryRecord->record(
              $provisioningData['CoPerson']['id'],
              null,
              null,
              CakeSession::read('Auth.User.co_person_id'),
              ($pAction == ProvisioningActionEnum::CoPersonReprovisionRequested
               ? ActionEnum::CoPersonManuallyProvisioned
               : ActionEnum::CoPersonProvisioned),
              _txt('rs.prov-a', array($coProvisioningTarget['description']))
            );
          }
        }
        catch(InvalidArgumentException $e) {
          $this->registerFailureNotification($coProvisioningTarget,
                                             (!empty($provisioningData['CoPerson']['id'])
                                              ? $provisioningData['CoPerson']['id'] : null),
                                             (!empty($provisioningData['CoGroup']['id'])
                                              ? $provisioningData['CoGroup']['id'] : null),
                                             (!empty($provisioningData['CoEmailList']['id'])
                                              ? $provisioningData['CoEmailList']['id'] : null),
                                             $e->getMessage());
          
          throw new InvalidArgumentException($e->getMessage());
        }
        catch(RuntimeException $e) {
          $this->registerFailureNotification($coProvisioningTarget,
                                             (!empty($provisioningData['CoPerson']['id'])
                                              ? $provisioningData['CoPerson']['id'] : null),
                                             (!empty($provisioningData['CoGroup']['id'])
                                              ? $provisioningData['CoGroup']['id'] : null),
                                             (!empty($provisioningData['CoEmailList']['id'])
                                              ? $provisioningData['CoEmailList']['id'] : null),
                                             $e->getMessage());
          
          throw new RuntimeException($e->getMessage());
        }
        
        // On success clear any pending notifications
        $this->resolveNotifications($coProvisioningTarget['id'],
                                    (!empty($provisioningData['CoPerson']['id'])
                                     ? $provisioningData['CoPerson']['id'] : null),
                                    (!empty($provisioningData['CoGroup']['id'])
                                     ? $provisioningData['CoGroup']['id'] : null));
      } else {
        throw new InvalidArgumentException(_txt('er.copt.unk'));
      }
    } else {
      throw new InvalidArgumentException(_txt('er.copt.unk'));
    }
    
    return true;
  }
  
  /**
   * Invoke all provisioning plugins.
   *
   * @since  COmanage Registry v0.8
   * @param  Model $model CoPerson or CoGroup Model
   * @param  Array $provisioningData Data to pass to plugins, as returned by marshallCoPersonData() or marshallCoGroupData()
   * @param  ProvisioningActionEnum $action Action triggering provisioning
   * @return boolean true on success
   * @throws RuntimeException
   */
  
  private function invokePlugins($model, $provisioningData, $action) {
    $err = "";
    
    // Pull the Provisioning Targets for this CO. We use the CO ID from $provisioningData.
    
    $args = array();
    $args['conditions']['CoProvisioningTarget.status'] = array(ProvisionerStatusEnum::AutomaticMode);
    if($action == ProvisioningActionEnum::CoPersonPetitionProvisioned) {
      // Also run provisioners in Enrollment Mode
      $args['conditions']['CoProvisioningTarget.status'][] = ProvisionerStatusEnum::EnrollmentMode;
    }
    if(isset($provisioningData[ $model->name ]['co_id'])) {
      $args['conditions']['CoProvisioningTarget.co_id'] = $provisioningData[ $model->name ]['co_id'];
    } else {
      throw new RuntimeException(_txt('er.co.specify'));
    }
    // In general, we want the ascending order, but for delete operations we want descending (CO-1356).
    // We use $personStatuses (and $action) to determine the order. There are very few circumstances
    // where (eg) a person should go from Active backwards to (eg) Confirmed, and in those we
    // probably want to deprovision anyway.
    
    if($action == ProvisioningActionEnum::CoPersonDeleted
       || $action == ProvisioningActionEnum::CoGroupDeleted
       || ($model->name == 'CoPerson'
           && !in_array($provisioningData[ $model->name ]['status'], $this->personStatuses))
       || ($model->name == 'CoGroup'
           && $provisioningData[ $model->name ]['status'] != SuspendableStatusEnum::Active)) {
      $args['order'] = array('CoProvisioningTarget.ordr DESC');
    } else {
      $args['order'] = array('CoProvisioningTarget.ordr ASC');
    }
    $args['contain'] = false;
    
    $targets = $model->Co->CoProvisioningTarget->find('all', $args);
    
    if(!empty($targets)) {
      foreach($targets as $target) {
        // Fire off each provisioning target
        
        try {
          $this->invokePlugin($target['CoProvisioningTarget'],
                              $provisioningData,
                              $action);
        }
        catch(InvalidArgumentException $e) {
          $err .= _txt('er.prov.plugin', array($target['CoProvisioningTarget']['description'], $e->getMessage())) . ";";
        }
        catch(RuntimeException $e) {
          $err .= _txt('er.prov.plugin', array($target['CoProvisioningTarget']['description'], $e->getMessage())) . ";";
        }
      }
    }
    
    if($err != "") {
      throw new RuntimeException(rtrim($err, ";"));
    }
    
    return true;
  }

  /**
   * Handle a manual provisioning request.
   *
   * @since  COmanage Registry v0.8
   * @param  Model $model Model instance
   * @param  integer $coProvisioningTargetId CO Provisioning Target to execute, or null for all
   * @param  integer $coPersonId CO Person to (re)provision
   * @param  integer $coGroupId CO Group to (re)provision
   * @param  ProvisioningActionEnum $provisioningAction Provisioning action to pass to plugins
   * @param  integer $coEmailListId CO Email List to (re)provision
   * @param  integer $coGroupMemberId CO Group Member to (re)provision
   * @return boolean true on success, false on failure
   * @throws InvalidArgumentException
   * @throws RuntimeException
   */
  
  public function manualProvision(Model $model,
                                  $coProvisioningTargetId=null,
                                  $coPersonId,
                                  $coGroupId=null,
                                  $provisioningAction=ProvisioningActionEnum::CoPersonReprovisionRequested,
                                  $coEmailListId=null,
                                  $coGroupMemberId=null) {
    // First marshall the provisioning data
    $provisioningData = array();
    
    // We handle things a bit differently if a CO Provisioning Target was specified
    // vs if not. In the former case, we perform a basic reprovision of exactly what
    // was requested. In the latter case, we operate more like afterSave.
    
    if($coProvisioningTargetId) {
      if($coPersonId) {
        // $model = CoPerson
        $provisioningData = $this->marshallCoPersonData($model, $coPersonId);
      } elseif($coGroupId) {
        // $model = CoGroup
        $provisioningData = $this->marshallCoGroupData($model, $coGroupId);
      } elseif($coEmailListId) {
        // $model = CoEmailList
        $provisioningData = $this->marshallCoEmailListData($model, $coEmailListId);
      }
      // XXX We don't currently support manual provisioning of 
      // CoGroupMember+coProvisioningTargetId because we don't have a use case
      
      // Find the associated Provisioning Target record
      
      $args = array();
      $args['conditions']['CoProvisioningTarget.id'] = $coProvisioningTargetId;
      // beforeFilter may have bound all the plugins (depending on how we were called),
      // so this find will pull the related models as well. However, to reduce the number
      // of database queries should a large number of plugins be installed, we'll use
      // containable behavior and make a second call for the plugin we want.
      $args['contain'] = false;
      
      // Currently, CoPerson and CoGroup are the only models that calls manualProvision, so we know
      // how to find CoProvisioningTarget
      $copt = $model->Co->CoProvisioningTarget->find('first', $args);
      
      if(!empty($copt)) {
        try {
          $this->invokePlugin($copt['CoProvisioningTarget'],
                              $provisioningData,
                              $provisioningAction);
        }
        catch(InvalidArgumentException $e) {
          throw new InvalidArgumentException($e->getMessage());
        }
        catch(RuntimeException $e) {
          throw new RuntimeException($e->getMessage());
        }
      } else {
        throw new InvalidArgumentException(_txt('er.copt.unk'));
      }
    } else {
      // Set the appropriate ID
      
      $model->clear();
      
      if($model->name == 'CoPerson' && $coPersonId) {
        $model->id = $coPersonId;
      } elseif($model->name == 'CoGroup' && $coGroupId) {
        $model->id = $coGroupId;
      } elseif($model->name == 'CoGroupMember' && $coGroupMemberId) {
        $model->id = $coGroupMemberId;
      } elseif($model->name == 'CoEmailList' && $coEmailListId) {
        $model->id = $coEmailListId;
      }
      
      return $this->determineProvisioning($model, false, $provisioningAction);
    }
    
    return true;
  }
  
  /**
   * Assemble CO Email List Data to pass to provisioning plugin(s).
   *
   * @since  COmanage Registry v3.1.0
   * @param  Model $coEmailListModel CO Email List Model instance
   * @param  integer $coEmailListId CO Email List to (re)provision
   * @return Array Array of CO Email List Data, as returned by find
   * @throws InvalidArgumentException
   */
  
  private function marshallCoEmailListData($coEmailListModel, $coEmailListId) {
    $args = array();
    $args['conditions']['CoEmailList.id'] = $coEmailListId;
    $args['contain'] = false;
    
    return $coEmailListModel->find('first', $args);
  }
  
  /**
   * Assemble CO Group Data to pass to provisioning plugin(s).
   *
   * @since  COmanage Registry v0.8.2
   * @param  Model $coGroupModel CO Group Model instance
   * @param  integer $coGroupId CO Group to (re)provision
   * @param  integer $coPersonId CO Person who triggered group update, if relevant
   * @return Array Array of CO Group Data, as returned by find
   * @throws InvalidArgumentException
   */
  
  private function marshallCoGroupData($coGroupModel, $coGroupId, $coPersonId=null) {
    // We can't pull all group member data because it won't scale. So we just pass
    // the group metadata, and if a person triggered the update we also pass that
    // person's information.
    
    $args = array();
    $args['conditions']['CoGroup.id'] = $coGroupId;
    $args['contain'] = false;
    
    $group = $coGroupModel->find('first', $args);
    
    if(!empty($group['CoGroup']['id']) && $coPersonId) {
      $args = array();
      $args['conditions']['CoPerson.id'] = $coPersonId;
      $args['contain'] = array('CoGroupMember' => array('conditions' => array('co_group_id ' => $coGroupId)));
      
      $person = $coGroupModel->Co->CoPerson->find('first', $args);
      
      if(!empty($person)) {
        // XXX Do we need to remove CoGroupMembers with invalid dates here?
        // Need a test case...
        $group['CoGroup'] = array_merge($group['CoGroup'], $person);
      }
    }
    
    return $group;
  }
  
  /**
   * Assemble CO Person Data to pass to provisioning plugin(s).
   *
   * @since  COmanage Registry v0.8
   * @param  Model $coPersonModel CO Person Model instance
   * @param  integer $coPersonId CO Person to (re)provision
   * @return Array Array of CO Person Data, as returned by find
   * @throws InvalidArgumentException
   */
  
  private function marshallCoPersonData($coPersonModel, $coPersonId) {
    $args = array();
    $args['conditions'][$coPersonModel->alias.'.id'] = $coPersonId;
    // Only pull related models relevant for provisioning
    $args['contain'] = array(
      'Co',
      'CoGroupMember' => array('CoGroup' => array('EmailListAdmin', 'EmailListMember', 'EmailListModerator')),
      // 'CoGroup'
      // 'CoGroupMember.CoGroup',
      'CoOrgIdentityLink' => array('OrgIdentity' => array('Identifier', 'OrgIdentitySourceRecord')),
      //'CoOrgIdentityLink',
      // We normally don't pull org identity data, but we'll make an exception
      // for Identifier to be able to expose eppn
      //'CoOrgIdentityLink.OrgIdentity.Identifier',
      'CoPersonRole' => array('Address', 'Cou', 'TelephoneNumber', 'order' => 'CoPersonRole.ordr ASC'),
      //'CoPersonRole',
      //'CoPersonRole.Address',
      //'CoPersonRole.Cou',
      //'CoPersonRole.TelephoneNumber',
      'CoTAndCAgreement' => array('CoTermsAndConditions'),
      'EmailAddress',
      'Identifier',
      'Name',
      'PrimaryName' => array('conditions' => array('PrimaryName.primary_name' => true)),
      'Url'
    );
    
    $coPersonData = $coPersonModel->find('first', $args);
    
    if(empty($coPersonData)) {
      throw new InvalidArgumentException(_txt('er.cop.unk'));
    }
    
    // Because Authenticators are handled via plugins (which might not be configured)
    // we need to handle them specially. Pull the set of authenticators and then
    // pull their model data. Note we assume FooAuthenticator, where Foo is the
    // corresponding model.
    
    $authplugins = preg_grep('/.*Authenticator$/', CakePlugin::loaded());
    
    foreach($authplugins as $authplugin) {
      // $authplugin = (eg) PasswordAuthenticator
      // $authmodel = (eg) Password
      $authmodel = substr($authplugin, 0, -13);
      
      // We should be able to just modify the 'contain' above, but for some reason
      // this isn't working (possibly cake resetting associations somewhere or maybe
      // due to Passward not being changelog enabled).
      $coPersonModel->bindModel(array('hasMany' => array($authplugin.'.'.$authmodel => array('dependent' => true))));
      
      $args = array();
      $args['conditions'][$authmodel.'.co_person_id'] = $coPersonId;
      // We also need the configuration ("Authenticator") status as well as
      // the status of this specific authenticator ("AuthenticatorStatus").
      // For some reason when called from SAMController::manage() (but not from other functions)
      // this contain picks up (eg) PasswordAuthenticator, but not Authenticator or AuthenticatorStatus.
      // So we have to make multiple calls.
//      $args['contain']['PasswordAuthenticator']['Authenticator'] = 'AuthenticatorStatus';
      $args['contain'][] = $authplugin;
      
      $authenticators = $coPersonModel->$authmodel->find('all', $args);
      
      // We only want Authenticators that are Active. (The Plugins decide what to do
      // with AuthenticatorStatus.)
      
      $coPersonData[$authmodel] = array();
      
      foreach($authenticators as $p) {
        // Now we need to pull the Authenticator and Status
        if(!empty($p[$authplugin]['authenticator_id'])) {
          $args = array();
          $args['conditions']['Authenticator.id'] = $p[$authplugin]['authenticator_id'];
          $args['contain'] = array(
            'AuthenticatorStatus' => array(
              'conditions' => array('AuthenticatorStatus.co_person_id' => $coPersonId)
            )
          );
          
          $aStatus = $coPersonModel->$authmodel->$authplugin->Authenticator->find('first', $args);
          
          if(isset($aStatus['Authenticator']['status'])
             && $aStatus['Authenticator']['status'] == SuspendableStatusEnum::Active) {
            // Reformat the data to match the main find
            $pd = $p[$authmodel];
            
            if(!empty($aStatus['AuthenticatorStatus'][0])) {
              $pd['AuthenticatorStatus'] = $aStatus['AuthenticatorStatus'][0];
            }
          
            $coPersonData[$authmodel][] = $pd;
          }
        }
      }
    }
    
    // At the moment, if a CO Person is not active we remove their Role Records
    // (even if those are active) and group memberships, but leave the rest of the
    // data in tact.
    
    // Remove any role records that are not valid for provisioning
    
    if(!empty($coPersonData['CoPersonRole'])) {
      for($i = (count($coPersonData['CoPersonRole']) - 1);$i >= 0;$i--) {
        // Count backwards so we don't trip over indices when we unset invalid roles.
        // The role record must have a valid status, be within validity window,
        // and be attached to a valid CO Person.
        
        if(!in_array($coPersonData[$coPersonModel->alias]['status'], $this->personStatuses)
           ||
           !in_array($coPersonData['CoPersonRole'][$i]['status'], $this->roleStatuses)
           ||
           (!empty($coPersonData['CoPersonRole'][$i]['valid_from'])
            && strtotime($coPersonData['CoPersonRole'][$i]['valid_from']) >= time())
           ||
           (!empty($coPersonData['CoPersonRole'][$i]['valid_through'])
            && strtotime($coPersonData['CoPersonRole'][$i]['valid_through']) < time())) {
          unset($coPersonData['CoPersonRole'][$i]);
        }
      }
    }
    
    // Remove any inactive identifiers
    
    if(!empty($coPersonData['Identifier'])) {
      for($i = (count($coPersonData['Identifier']) - 1);$i >= 0;$i--) {
        // Count backwards so we don't trip over indices when we unset invalid identifiers.
        
        if($coPersonData['Identifier'][$i]['status'] != StatusEnum::Active) {
          unset($coPersonData['Identifier'][$i]);
        }
      }
    }
    
    // Remove any inactive groups (ie: memberships attached to inactive groups
    // or those not within the validity window).
    
    if(!empty($coPersonData['CoGroupMember'])) {
      for($i = (count($coPersonData['CoGroupMember']) - 1);$i >= 0;$i--) {
        // Count backwards so we don't trip over indices when we unset invalid memberships.
        
        // We need for CO Person to be in a status that provisions *group* memberships here,
        // not a status for person provisioning. However, we always leave the AllMembers group
        // in place.
        
        if(!empty($coPersonData['CoGroupMember'][$i]['CoGroup']['group_type'])
           && $coPersonData['CoGroupMember'][$i]['CoGroup']['group_type'] == GroupEnum::AllMembers) {
          continue;
        }
        
        if(!in_array($coPersonData[$coPersonModel->alias]['status'], $this->groupStatuses)
           ||
           $coPersonData['CoGroupMember'][$i]['CoGroup']['status'] != StatusEnum::Active
           ||
           (!empty($coPersonData['CoGroupMember'][$i]['valid_from'])
            && strtotime($coPersonData['CoGroupMember'][$i]['valid_from']) >= time())
           ||
           (!empty($coPersonData['CoGroupMember'][$i]['valid_through'])
            && strtotime($coPersonData['CoGroupMember'][$i]['valid_through']) < time())) {
          unset($coPersonData['CoGroupMember'][$i]);
        }
      }
      
      if(count($coPersonData['CoGroupMember']) == 0) {
        unset($coPersonData['CoGroupMember']);
      }
    }
    
    // Remove any inactive org identities
    
    if(!empty($coPersonData['CoOrgIdentityLink'])) {
      for($i = (count($coPersonData['CoOrgIdentityLink']) - 1);$i >= 0;$i--) {
        // We don't currently look at Org Identity status since it's primarily used to track
        // OIS sync state. However, this could change in the future.
        
        if((!empty($coPersonData['CoOrgIdentityLink'][$i]['OrgIdentity']['valid_from'])
            && strtotime($coPersonData['CoOrgIdentityLink'][$i]['OrgIdentity']['valid_from']) >= time())
           ||
           (!empty($coPersonData['CoOrgIdentityLink'][$i]['OrgIdentity']['valid_through'])
            && strtotime($coPersonData['CoOrgIdentityLink'][$i]['OrgIdentity']['valid_through']) < time())) {
          unset($coPersonData['CoOrgIdentityLink'][$i]);
        }
      }
    }
    
    return $coPersonData;
  }
  
  /**
   * Provision email lists.
   *
   * @since  COmanage Registry v3.1.0
   * @param  Object                 $model              Invoking model
   * @param  Array                  $coEmailListIds     Array of Email List IDs to provision
   * @param  Boolean                $created            As passed to afterSave()
   * @param  ProvisioningActionEnum $provisioningAction Provisioning action to pass to plugins
   * @throws InvalidArgumentException
   * @throws RuntimeException
   */
  
  protected function provisionEmailLists($model, $coEmailListIds, $created, $provisioningAction) {
    foreach($coEmailListIds as $coEmailListId) {
      $emaillist = $this->marshallCoEmailListData($model, $coEmailListId);
      
      if(empty($emaillist)) {
        // XXX here and below (People/Groups), do we really want to abort if only one entry is not found?
        throw new InvalidArgumentException($e->getMessage());
      }
      
      $paction = $provisioningAction ? $provisioningAction : ProvisioningActionEnum::CoEmailListUpdated;
      
      if($created) {
        $paction = ProvisioningActionEnum::CoEmailListAdded;
      }
      
      // Invoke all provisioning plugins
      
      try {
        $this->invokePlugins($model,
                             $emaillist,
                             $paction);
      }    
      // What we really want to do here is catch the result (success or exception)
      // and set the appropriate session flash message, but we don't have access to
      // the current session, and anyway that doesn't cover RESTful interactions.
      // So instead we syslog (which is better than nothing).
      catch(InvalidArgumentException $e) {
        syslog(LOG_ERR, $e->getMessage());
        //throw new InvalidArgumentException($e->getMessage());
      }
      catch(RuntimeException $e) {
        syslog(LOG_ERR, $e->getMessage());
        //throw new RuntimeException($e->getMessage());
      }
    }
  }
  
  /**
   * Provision group data.
   *
   * @param  Object $model Invoking model
   * @param  Object $gmodel Group model
   * @param  Array $coGroupIds Array of group IDs to provision
   * @param  Boolean $created As passed to afterSave()
   * @param  ProvisioningActionEnum $provisioningAction Provisioning action to pass to plugins
   * @param  Integer $coPersonId CO Person who triggered group update, if relevant
   * @return Boolean
   * @throws InvalidArgumentException
   */
  
  protected function provisionGroups($model, $gmodel, $coGroupIds, $created, $provisioningAction, $coPersonId=null) {
    foreach($coGroupIds as $coGroupId) {
      try {
        $pdata = $this->marshallCoGroupData($gmodel, $coGroupId, $coPersonId);
      }
      catch(InvalidArgumentException $e) {
        throw new InvalidArgumentException($e->getMessage());
      }
      
      $paction = $provisioningAction ? $provisioningAction : ProvisioningActionEnum::CoGroupUpdated;
      
      // It's only an add operation if the model is CoGroup
      if($created && $model->name == 'CoGroup') {
        $paction = ProvisioningActionEnum::CoGroupAdded;
      }
      
      // Invoke all provisioning plugins
      
      try {
        $this->invokePlugins($gmodel,
                             $pdata,
                             $paction);
      }    
      // What we really want to do here is catch the result (success or exception)
      // and set the appropriate session flash message, but we don't have access to
      // the current session, and anyway that doesn't cover RESTful interactions.
      // So instead we syslog (which is better than nothing).
      catch(InvalidArgumentException $e) {
        syslog(LOG_ERR, $e->getMessage());
        //throw new InvalidArgumentException($e->getMessage());
      }
      catch(RuntimeException $e) {
        syslog(LOG_ERR, $e->getMessage());
        //throw new RuntimeException($e->getMessage());
      }
      catch(Exception $e) {
        syslog(LOG_ERR, $e->getMessage());
        //throw new RuntimeException($e->getMessage());
      }
    }
    
    return true;
  }
  
  /**
   * Provision people data.
   *
   * @param Object $model Invoking model
   * @param Object $gmodel Person model
   * @param Array $coPersonIds Array of person IDs to provision
   * @param Boolean $created As passed to afterSave()
   * @param  ProvisioningActionEnum $provisioningAction Provisioning action to pass to plugins
   * @return Boolean
   * @throws InvalidArgumentException
   */
  
  protected function provisionPeople($model, $pmodel, $coPersonIds, $created, $provisioningAction) {
    foreach($coPersonIds as $cpid) {
      // $cpid could be null during a delete operation. If so, we're probably
      // in the process of removing a related model, so just skip it.
      if(empty($cpid)) {
        continue;
      }
      
      try {
        $pdata = $this->marshallCoPersonData($pmodel, $cpid);
      }
      catch(InvalidArgumentException $e) {
        throw new InvalidArgumentException($e->getMessage());
      }
      
      // Re-key $pdata when the person alias is EnrolleeCoPerson to make
      // everything else works more smoothly
      
      if($model->alias == 'EnrolleeCoPerson' && !empty($pdata['EnrolleeCoPerson'])) {
        $pdata['CoPerson'] = $pdata['EnrolleeCoPerson'];
        unset($pdata['EnrolleeCoPerson']);
      }
      
      // Make sure CO Person data was retrieved (it won't be for certain operations
      // surrounding CO Person delete)
      
      if(empty($pdata['CoPerson'])) {
        continue;
      }
      
      // Determine the provisioning action
      
      $paction = $provisioningAction ? $provisioningAction : ProvisioningActionEnum::CoPersonUpdated;
      
      if($model->name == 'CoPerson') {
        if($created) {
          // It's only an add operation if the model is CoPerson
          $paction = ProvisioningActionEnum::CoPersonAdded;
        } elseif(!empty($model->cacheData[ $model->alias ]['status'])) {
          if($model->data[ $model->alias ]['status'] == StatusEnum::GracePeriod
             && $model->cacheData[ $model->alias ]['status'] != StatusEnum::GracePeriod) {
            $paction = ProvisioningActionEnum::CoPersonEnteredGracePeriod;
          } elseif($model->data[ $model->alias ]['status'] == StatusEnum::Expired
             && $model->cacheData[ $model->alias ]['status'] != StatusEnum::Expired) {
            $paction = ProvisioningActionEnum::CoPersonExpired;
          } elseif($model->data[ $model->alias ]['status'] != StatusEnum::Expired
             && $model->cacheData[ $model->alias ]['status'] == StatusEnum::Expired) {
            $paction = ProvisioningActionEnum::CoPersonUnexpired;
          }
        }
      }
      
      // Invoke all provisioning plugins
      
      try {
        $this->invokePlugins($pmodel,
                             $pdata,
                             $paction);
      }    
      // What we really want to do here is catch the result (success or exception)
      // and set the appropriate session flash message, but we don't have access to
      // the current session, and anyway that doesn't cover RESTful interactions.
      // So instead we syslog (which is better than nothing).
      catch(InvalidArgumentException $e) {
        syslog(LOG_ERR, $e->getMessage());
        //throw new InvalidArgumentException($e->getMessage());
      }
      catch(RuntimeException $e) {
        syslog(LOG_ERR, $e->getMessage());
        //throw new RuntimeException($e->getMessage());
      }
      catch(Exception $e) {
        syslog(LOG_ERR, $e->getMessage());
        //throw new RuntimeException($e->getMessage());
      }
    }
    
    return true;
  }
  
  /**
   * Register a Notification on failure
   *
   * @since  COmanage Registry v0.9.1
   * @param  integer $coProvisionerTarget CO Provisioner Target object
   * @param  integer $targetCoPersonId CO Person being provisioned
   * @param  integer $targetCoGroupId CO Group being provisioned
   * @param  integer $targetCoEmailListId CO Email List being provisioned
   * @param  string  $msg Error message
   */
  
  protected function registerFailureNotification($coProvisionerTarget,
                                                 $targetCoPersonId,
                                                 $targetCoGroupId,
                                                 $targetCoEmailListId,
                                                 $msg) {
    $Co = ClassRegistry::init('Co');
    
    if(!$targetCoEmailListId) {
      // Notifications don't currently have CO Email Lists has subjects,
      // so we only register notifications for people and groups.
      
      // We need to pull the admin group to notify
      $args = array();
      $args['conditions']['CoGroup.co_id'] = $coProvisionerTarget['co_id'];
      $args['conditions']['CoGroup.name'] = "admin";
      $args['contain'] = false;
      
      $cogr = $Co->CoGroup->find('first', $args);
      
      if($cogr) {
        // Assemble the source array for the notification
        $src = array();
        
        if(!empty($targetCoPersonId)) {
          $src['controller'] = 'co_people';
          $src['action'] = 'provision';
          $src['id'] = $targetCoPersonId;
        } elseif(!empty($targetCoGroupId)) {
          $src['controller'] = 'co_groups';
          $src['action'] = 'provision';
          $src['id'] = $targetCoGroupId;
        }
        
        $src['arg0'] = 'coprovisioningtargetid';
        $src['val0'] = $coProvisionerTarget['id'];
        
        // Register the notification
        $Co->CoPerson->CoNotificationSubject->register($targetCoPersonId,
                                                       $targetCoGroupId,
                                                       CakeSession::read('Auth.User.co_person_id'),
                                                       'cogroup',
                                                       $cogr['CoGroup']['id'],
                                                       ActionEnum::ProvisionerFailed,
                                                       _txt('er.prov.plugin', array($coProvisionerTarget['description'], $msg)),
                                                       $src,
                                                       true);
      }
    }
    
    // Record a history record
    try {
      $Co->CoPerson->HistoryRecord->record($targetCoPersonId,
                                           null,
                                           null,
                                           CakeSession::read('Auth.User.co_person_id'),
                                           ActionEnum::ProvisionerFailed,
                                           _txt('er.prov.plugin', array($coProvisionerTarget['description'], $msg)),
                                           $targetCoGroupId,
                                           $targetCoEmailListId);
    }
    catch(Exception $e) {
      // Unclear what we should be do here
    }
  }
  
  /**
   * Resolve any Notifications
   *
   * @since  COmanage Registry v0.9.1
   * @param  integer $coProvisionerTargetId CO Provisioner Target invoked
   * @param  integer $targetCoPersonId CO Person being provisioned
   * @param  integer $targetCoGroupId CO Group being provisioned
   */
  
  protected function resolveNotifications($coProvisionerTargetId,
                                          $targetCoPersonId,
                                          $targetCoGroupId) {
    $CoNotification = ClassRegistry::init('CoNotification');
    
    // Assemble the source array for the notification
    $src = array();
    
    if(!empty($targetCoPersonId)) {
      $src['controller'] = 'co_people';
      $src['action'] = 'provision';
      $src['id'] = $targetCoPersonId;
    } elseif(!empty($targetCoGroupId)) {
      $src['controller'] = 'co_groups';
      $src['action'] = 'provision';
      $src['id'] = $targetCoGroupId;
    } else {
      // Nothing to do
      return;
    }
    
    $src['arg0'] = 'coprovisioningtargetid';
    $src['val0'] = $coProvisionerTargetId;
    
    // Resolve any outstanding notifications
    $CoNotification->resolveFromSource($src, CakeSession::read('Auth.User.co_person_id'));
  }
}
