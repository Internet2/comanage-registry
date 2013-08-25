<?php
/**
 * COmanage Registry Provisioner Behavior
 *
 * Copyright (C) 2012-13 University Corporation for Advanced Internet Development, Inc.
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
 * @copyright     Copyright (C) 2012-13 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.8
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */

// Behaviors don't have access to sessions by default
App::uses('CakeSession', 'Model/Datasource');

// Direct calls using models necessary since may use Grouper data source.
App::uses('CoGroupMember', 'Model');
App::uses('CoGroup', 'Model');

class ProvisionerBehavior extends ModelBehavior {
  /**
   * Handle provisioning following delete of Model.
   *
   * @since  COmanage Registry v0.8
   * @param  Model $model Model instance.
   * @return boolean true on success, false on failure
   */
  
  public function afterDelete(Model $model) {
    // Note that in most cases this is just an edit. ie: deleting a telephone number is
    // CoPersonUpdated not CoPersonDeleted. In those cases, we can just call afterSave.
    // CoPerson and CoGroup were already handled by beforeDelete().
    
    if($model->name != 'CoPerson' && $model->name != 'CoGroup') {
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
  
  public function afterSave(Model $model, $created) {
    $pmodel = null;
    $pdata = null;
    $paction = null;
    
    // For our initial implementation, one of the following must be true for $model:
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
    
    if($model->name == 'CoGroup' || $model->name == 'CoGroupMember') {
      // First, find the co_group_id and pull the record
      
      $coGroupId = -1;
      
      if($model->name == 'CoGroup'
         && !empty($model->data['CoGroup']['id'])) {
        $pmodel = $model;
        $coGroupId = $model->data['CoGroup']['id'];
      } elseif(!empty($model->data[ $model->name ]['co_group_id'])) {
        $pmodel = $model->CoGroup;
        $coGroupId = $model->data[ $model->name ]['co_group_id'];
      }
      
      if($pmodel){
        try {
          $pdata = $this->marshallCoGroupData($pmodel, $coGroupId);
        }
        catch(InvalidArgumentException $e) {
          throw new InvalidArgumentException($e->getMessage());
        }
        
        $paction = ProvisioningActionEnum::CoGroupUpdated;
        
        // It's only an add operation if the model is CoPerson
        if($created && $model->name == 'CoGroup') {
          $paction = ProvisioningActionEnum::CoGroupAdded;
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
      } else {
        // We're probably CoGroupMember being promoted to afterSave in the middle of
        // a CoGroup being deleted. In that scenario, we don't actually need to try
        // to re-provision the CoGroup, so just move on to the person.
      }
    }
    
    if($model->name != 'CoGroup') {
      // First, find the co_person_id (directly or indirectly) and pull the record
      
      $coPersonId = -1;
      
      if($model->name == 'CoPerson'
         && !empty($model->data['CoPerson']['id'])) {
        $pmodel = $model;
        $coPersonId = $model->data['CoPerson']['id'];
      } elseif(!empty($model->data[ $model->name ]['co_person_id'])) {
        $pmodel = $model->CoPerson;
        $coPersonId = $model->data[ $model->name ]['co_person_id'];
      } elseif(!empty($model->data[ $model->name ]['co_person_role_id'])) {
        $pmodel = $model->CoPersonRole->CoPerson;
        $coPersonId = $model->CoPersonRole->field('co_person_id',
                                                  array('id' => $model->data[ $model->name ]['co_person_role_id']));
      } else {
        // For the moment, we'll just return true here since we may be processing
        // a multi-model transaction (eg: unlinking a dependency before deleting a
        // parent model) or we may be saving OrgIdentity data.
        
        return true;
      }
      
      try {
        $pdata = $this->marshallCoPersonData($pmodel, $coPersonId);
      }
      catch(InvalidArgumentException $e) {
        throw new InvalidArgumentException($e->getMessage());
      }
      
      // Determine the provisioning action
      
      // For now, we don't support CoPersonEnteredGracePeriod, CoPersonExpired,
      // or CoPersonUnexpired.
      
      $paction = ProvisioningActionEnum::CoPersonUpdated;
      
      // It's only an add operation if the model is CoPerson
      if($created && $model->name == 'CoPerson') {
        $paction = ProvisioningActionEnum::CoPersonAdded;
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
    }
    
    return true;
  }
  
  /**
   * Handle provisioning following (before) delete of Model.
   *
   * @since  COmanage Registry v0.8
   * @param  Model $model Model instance.
   * @return boolean true on success, false on failure
   */
  
  public function beforeDelete(Model $model, $cascade = true) {
    // Note that in most cases this is just an edit. ie: deleting a telephone number is
    // CoPersonUpdated not CoPersonDeleted. However, in those cases we don't want to
    // process anything until afterDelete().
    
    $mname = $model->name;
    
    // We will generally cache the data prior to delete in case we want to do
    // something interesting with it in afterDelete. As of this writing, the only
    // use case for this is when a CoGroupMember is removed, we need to know which
    // CoPerson and CoGroup to rewrite, and we have to do that in afterDelete
    // (so the CoGroupMember doesn't show up anymore) (CO-663).
    
    // Note that $model->data is generally populated by StandardController::delete
    // calling $model->read(), but for cascading deletes that function won't be called.
    
    if(empty($model->data)) {
      $model->read();
    }
    
    $model->cacheData = $model->data;
    
    if($mname != 'CoPerson' && $mname != 'CoGroup') {
      return true;
    }
    
    // However, deleting a CoPerson or CoGroup needs to be handled specially.
    
    
    if(!empty($model->data[ $mname ]['id'])) {
      // Invoke all provisioning plugins
      
      try {
        $this->invokePlugins($model,
                             $model->data,
                             $mname == 'CoPerson'
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
                                  $action,
                                  $provisioningData);
          
          // Create/update the export record
          
          $pluginModel->CoProvisioningTarget->CoProvisioningExport->record(
            $coProvisioningTarget['id'],
            !empty($provisioningData['CoPerson']['id']) ? $provisioningData['CoPerson']['id'] : null,
            !empty($provisioningData['CoGroup']['id']) ? $provisioningData['CoGroup']['id'] : null
          );
          
          // Cut a history record if we're provisioning a CO Person record.
          // Currently, there is no equivalent concept for CO Groups.
          
          if(!empty($provisioningData['CoPerson']['id'])) {
            // It's a bit of a walk to get to HistoryRecord
            $pluginModel->CoProvisioningTarget->Co->CoPerson->HistoryRecord->record(
              $provisioningData['CoPerson']['id'],
              null,
              null,
              CakeSession::read('Auth.User.co_person_id'),
              ($action == ProvisioningActionEnum::CoPersonReprovisionRequested
               ? ActionEnum::CoPersonManuallyProvisioned
               : ActionEnum::CoPersonProvisioned),
              _txt('rs.prov-a', array($coProvisioningTarget['description']))
            );
          }
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
    $args['conditions']['CoProvisioningTarget.status'] = ProvisionerStatusEnum::AutomaticMode;
    if(isset($provisioningData[ $model->name ]['co_id'])) {
      $args['conditions']['CoProvisioningTarget.co_id'] = $provisioningData[ $model->name ]['co_id'];
    } else {
      throw new RuntimeException(_txt('er.co.specify'));
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
   * @param  integer $coProvisioningTargetId CO Provisioning Target to execute
   * @param  integer $coPersonId CO Person to (re)provision (null if CO Group ID set)
   * @param  integer $coPersonId CO Group to (re)provision (null if CO Person ID set)
   * @return boolean true on success, false on failure
   * @throws InvalidArgumentException
   * @throws RuntimeException
   */
  
  public function manualProvision(Model $model, $coProvisioningTargetId, $coPersonId, $coGroupId=null) {
    // Find the associated Provisioning Target record
    
    $args = array();
    $args['conditions']['CoProvisioningTarget.id'] = $coProvisioningTargetId;
    // beforeFilter may have bound all the plugins (depending on how we were called),
    // so this find will pull the related models as well. However, to reduce the number
    // of database queries should a large number of plugins be installed, we'll use
    // containable behavior and make a second call for the plugin we want.
    $args['contain'] = false;
    
    // Currently, CoPerson and CoGroup are the only model that calls manualProvision, so we know
    // how to find CoProvisioningTarget
    $copt = $model->Co->CoProvisioningTarget->find('first', $args);
    
    if(!empty($copt)) {
      try {
        if($coPersonId) {
          // $model = CoPerson
          $provisioningData = $this->marshallCoPersonData($model, $coPersonId);
          
          $this->invokePlugin($copt['CoProvisioningTarget'],
                              $provisioningData,
                              ProvisioningActionEnum::CoPersonReprovisionRequested);
        } else {
          // $model = CoGroup
          $provisioningData = $this->marshallCoGroupData($model, $coGroupId);
          
          $this->invokePlugin($copt['CoProvisioningTarget'],
                              $provisioningData,
                              ProvisioningActionEnum::CoGroupReprovisionRequested);
        }
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
    
    return true;
  }
  
  /**
   * Assemble CO Group Data to pass to provisioning plugin(s).
   *
   * @since  COmanage Registry v0.8.2
   * @param  Model $coGroupModel CO Group Model instance
   * @param  integer $coGroupId CO Group to (re)provision
   * @return Array Array of CO Group Data, as returned by find
   * @throws InvalidArgumentException
   */
  
  private function marshallCoGroupData($coGroupModel, $coGroupId) {
    $args = array();
    $args['conditions']['CoGroup.id'] = $coGroupId;
    // Only pull related models relevant for provisioning
    $args['contain'][] = 'CoGroupMember';
    
    return $coGroupModel->find('first', $args);
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
    $args['conditions']['CoPerson.id'] = $coPersonId;
    // Only pull related models relevant for provisioning
    $args['contain'] = array(
      'Co',
      // Group information handled directly below to support Grouper use case.
      //'CoGroupMember',
      //'CoGroupMember.CoGroup',
      'CoOrgIdentityLink',
      // We normally don't pull org identity data, but we'll make an exception
      // for Identifier to be able to expose eppn
      'CoOrgIdentityLink.OrgIdentity.Identifier',
      'CoPersonRole',
      'CoPersonRole.Address',
      'CoPersonRole.Cou',
      'CoPersonRole.TelephoneNumber',
      'EmailAddress',
      'Identifier', 
      'Name'
    );
    
    $coPersonData = $coPersonModel->find('first', $args);
    
    // Directly query for all group memberships instead of using
    // relations in order to support Grouper use cases.
    $coGroupMemberModel = new CoGroupMember();
    
    $args = array();
    $args['conditions']['CoGroupMember.co_person_id'] = $coPersonId;

    $memberships = $coGroupMemberModel->find('all', $args);

    $coPersonData['CoGroupMember'] = array();
    foreach ($memberships as &$m) {
      $groupId = $m['CoGroupMember']['co_group_id'];
      $coGroupModel = new CoGroup();

      $args = array();
      $args['conditions']['CoGroup.id'] = $groupId;

      $group = $coGroupModel->find('first', $args);
      $m['CoGroupMember']['CoGroup'] = $group['CoGroup'];
      $coPersonData['CoGroupMember'][] = $m['CoGroupMember'];
    }

    if(empty($coPersonData)) {
      throw new InvalidArgumentException(_txt('er.cop.unk'));
    }
    
    // At the moment, if a CO Person is not active we remove their Role Records
    // (even if those are active) and group memberships, but leave the rest of the
    // data in tact.
    
    // Remove any role records that are not active
    
    for($i = (count($coPersonData['CoPersonRole']) - 1);$i >= 0;$i--) {
      // Count backwards so we don't trip over indices when we unset invalid roles.
      // The role record must have a valid status (for now: Active), be within validity window,
      // and be attached to a valid CO Person.
      
      if($coPersonData['CoPerson']['status'] != StatusEnum::Active
         ||
         $coPersonData['CoPersonRole'][$i]['status'] != StatusEnum::Active
         ||
         (!empty($coPersonData['CoPersonRole'][$i]['valid_from'])
          && strtotime($coPersonData['CoPersonRole'][$i]['valid_from']) >= time())
         ||
         (!empty($coPersonData['CoPersonRole'][$i]['valid_through'])
          && strtotime($coPersonData['CoPersonRole'][$i]['valid_through']) < time())) {
        unset($coPersonData['CoPersonRole'][$i]);
      }
    }
    
    // Remove any inactive identifiers
    
    for($i = (count($coPersonData['Identifier']) - 1);$i >= 0;$i--) {
      // Count backwards so we don't trip over indices when we unset invalid identifiers.
      
      if($coPersonData['Identifier'][$i]['status'] != StatusEnum::Active) {
        unset($coPersonData['Identifier'][$i]);
      }
    }
    
    // Remove any inactive groups (ie: memberships attached to inactive groups)
    
    for($i = (count($coPersonData['CoGroupMember']) - 1);$i >= 0;$i--) {
      // Count backwards so we don't trip over indices when we unset invalid memberships.
      
      if($coPersonData['CoPerson']['status'] != StatusEnum::Active
         ||
         $coPersonData['CoGroupMember'][$i]['CoGroup']['status'] != StatusEnum::Active) {
        unset($coPersonData['CoGroupMember'][$i]);
      }
    }
    
    return $coPersonData;
  }
}
