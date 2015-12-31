<?php
/**
 * COmanage Registry Organizational Identity Source Model
 *
 * Copyright (C) 2015 University Corporation for Advanced Internet Development, Inc.
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
 * @copyright     Copyright (C) 2015 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v1.1.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */

class OrgIdentitySource extends AppModel {
  // Define class name for cake
  public $name = "OrgIdentitySource";
  
  // Current schema version for API
  public $version = "1.0";
  
  // Add behaviors
  public $actsAs = array('Changelog' => array('priority' => 5),
                         'Containable');
  
  // Association rules from this model to other models
  public $belongsTo = array(
    // An Org Identity Source belongs to a CO, if org identities not pooled
    'Co'
  );
  
  public $hasMany = array(
    "OrgIdentitySourceRecord" => array(
      'dependent'  => true
    )
  );
  
  // Default display field for cake generated views
  public $displayField = "description";
  
  // Validation rules for table elements
  public $validate = array(
    'co_id' => array(
      'content' => array(
        'rule' => 'numeric',
        'required' => false,
        'allowEmpty' => true
      )
    ),
    'description' => array(
      'rule' => 'notBlank',
      'required' => false,
      'allowEmpty' => true
    ),
    'plugin' => array(
      // XXX This should be a dynamically generated list based on available plugins
      'rule' => 'notBlank',
      'required' => true,
      'message' => 'A plugin must be provided'
    ),
    'status' => array(
      'rule' => array(
        'inList',
        array(
          SuspendableStatusEnum::Active,
          SuspendableStatusEnum::Suspended
        )
      ),
      'required' => true,
      'message' => 'A valid status must be selected'
    )
  );
  
  // Retrieved data, cached for later use. We don't use ->data here to avoid
  // interfering with Cake mechanics.
  protected $cdata = null;
  
  /**
   * Callback after model save.
   *
   * @since  COmanage Registry v1.1.0
   * @param  Boolean $created True if new model is saved (ie: add)
   * @param  Array $options Options, as based to model::save()
   * @return Boolean True on success
   */
  
  public function afterSave($created, $options = Array()) {
    if($created) {
      // Create an instance of the plugin source.
      
      $pluginName = $this->data['OrgIdentitySource']['plugin'];
      $modelName = $pluginName;
      $pluginModelName = $pluginName . "." . $modelName;
      
      $source = array();
      $source[$modelName]['org_identity_source_id'] = $this->id;
      
      // Note that we have to disable validation because we want to create an empty row.
      if(!$this->$modelName->save($source, false)) {
        return false;
      }
    }
    
    return true;
  }

  /**
   * Bind the specified plugin's backend model
   *
   * @since COmanage Registry v1.1.0
   * @param Integer $id OrgIdentitySource ID
   * @return Object Plugin Backend Model reference
   * @throws InvalidArgumentException
   */
  
  protected function bindPluginBackendModel($id) {
    // Pull the plugin information associated with $id
    
    $args = array();
    $args['conditions']['OrgIdentitySource.id'] = $id;
    // Do not set contain = false, we need the related model to pass to the backend
    
    $ois = $this->find('first', $args);
    
    if(empty($ois)) {
      throw new InvalidArgumentException(_txt('er.notfound', array(_txt('ct.org_identity_sources.1'), $id)));
    }
    
    // Store for possible later use
    $this->cdata = $ois;
    
    // Bind the backend model
    $bmodel = $ois['OrgIdentitySource']['plugin'] . '.' . $ois['OrgIdentitySource']['plugin'] . 'Backend';
    $Backend = ClassRegistry::init($bmodel);
    
    // And give it its configuration
    $Backend->setConfig($ois[ $ois['OrgIdentitySource']['plugin'] ]);
    
    return $Backend;
  }

  /**
   * Create a new organizational identity record based on a result from an Org Identity Source.
   *
   * @since  COmanage Registry v1.1.0
   * @param  Integer $id OrgIdentitySource to query
   * @param  String $sourceKey Record key to retrieve as basis of new Org Identity
   * @param  Integer $actorCoPersonId CO Person ID of actor creating new Org Identity
   * @param  Integer $coId CO ID, if org identities are not pooled
   * @return Integer ID of new Org Identity
   * @throws InvalidArgumentException
   * @throws OverflowException
   */
  
  public function createOrgIdentity($id, $sourceKey, $actorCoPersonId = null, $coId = null) {
    // First make sure we don't already have a record for id+sourceKey
    
    $args = array();
    $args['conditions']['OrgIdentitySourceRecord.org_identity_source_id'] = $id;
    $args['conditions']['OrgIdentitySourceRecord.sorid'] = $sourceKey;
    
    $cnt = $this->OrgIdentitySourceRecord->OrgIdentity->find('count', $args);
    
    if($cnt > 0) {
      throw new OverflowException(_txt('er.ois.linked'));
    }
    
    // Pull record from source
    
    $Backend = $this->bindPluginBackendModel($id);
    
    $brec = $Backend->retrieve($sourceKey);
    
    if(empty($brec['orgidentity'])) {
      throw new InvalidArgumentException(_txt('er.ois.noorg'));
    }
    
    $orgid = $brec['orgidentity'];
    
    // Maybe set the CO ID
    if($coId) {
      $orgid['OrgIdentity']['co_id'] = $coId;
    }
    
    // Set the status
    $orgid['OrgIdentity']['status'] = OrgIdentityStatusEnum::Synced;
    
    // Create a Source Record
    $orgid['OrgIdentitySourceRecord'] = array(
      'org_identity_source_id' => $id,
      'sorid'                  => $sourceKey,
      'source_record'          => isset($brec['raw']) ? $brec['raw'] : null,
      'last_update'            => date('Y-m-d H:i:s')
    );
    
    // Start a transaction
    $dbc = $this->getDataSource();
    $dbc->begin();
    
    $this->OrgIdentitySourceRecord->OrgIdentity->saveAssociated($orgid);
    
    // Cut a history record
    $this->OrgIdentitySourceRecord->OrgIdentity->HistoryRecord->record(null,
                                                                       null,
                                                                       $this->OrgIdentitySourceRecord->OrgIdentity->id,
                                                                       $actorCoPersonId,
                                                                       ActionEnum::OrgIdAddedSource,
                                                                       _txt('rs.org.src.new',
                                                                            array($this->cdata['OrgIdentitySource']['description'],
                                                                                  $this->cdata['OrgIdentitySource']['id'])));
    
    // Commit
    $dbc->commit();
    
    return $this->OrgIdentitySourceRecord->OrgIdentity->id;
  }
  
  /**
   * Retrieve a record from an Org Identity Source Backend.
   *
   * @since  COmanage Registry v1.1.0
   * @param  Integer $id OrgIdentitySource to search
   * @param  String $key Record key to retrieve
   * @return Array Raw record and Array in OrgIdentity format
   * @throws InvalidArgumentException
   */
  
  public function retrieve($id, $key) {
    $Backend = $this->bindPluginBackendModel($id);
    
    return $Backend->retrieve($key);
  }
  
  /**
   * Perform a search against an Org Identity Source Backend.
   *
   * @since  COmanage Registry v1.1.0
   * @param  Integer $id OrgIdentitySource to search
   * @param  Array $attributes Array in key/value format, where key is the same as returned by searchAttributes()
   * @return Array Array in OrgIdentity format
   * @throws InvalidArgumentException
   */
  
  public function search($id, $attributes) {
    $Backend = $this->bindPluginBackendModel($id);
    
    return $Backend->search($attributes);
  }

  /**
   * Obtain the set of searchable attributes for the Org Identity Source Backend.
   *
   * @since  COmanage Registry v1.1.0
   * @param  Integer $id OrgIdentitySource to search
   * @return Array Array of searchable attributes
   * @throws InvalidArgumentException
   */
  
  public function searchableAttributes($id) {
    $Backend = $this->bindPluginBackendModel($id);
    
    return $Backend->searchableAttributes();
  }
  
  /**
   * Sync an existing organizational identity record based on a result from an Org Identity Source.
   *
   * @since  COmanage Registry v1.1.0
   * @param  Integer $id OrgIdentitySource to query
   * @param  String $sourceKey Record key to retrieve as basis of new Org Identity
   * @param  Integer $actorCoPersonId CO Person ID of actor creating new Org Identity
   * @return Array 'id' is ID of Org Identity, and 'status' is "synced", "unchanged", or "removed"
   * @throws InvalidArgumentException
   */
  
  public function syncOrgIdentity($id, $sourceKey, $actorCoPersonId = null) {
    // Pull record from source
    
    $Backend = $this->bindPluginBackendModel($id);
    
    $brec = $Backend->retrieve($sourceKey);
    
    // Start a transaction
    $dbc = $this->getDataSource();
    $dbc->begin();
    
    // Find the existing org identity
    
    $args = array();
    $args['conditions']['OrgIdentitySourceRecord.org_identity_source_id'] = $id;
    $args['conditions']['OrgIdentitySourceRecord.sorid'] = $sourceKey;
    $args['joins'][0]['table'] = 'org_identity_source_records';
    $args['joins'][0]['alias'] = 'OrgIdentitySourceRecord';
    $args['joins'][0]['type'] = 'INNER';
    $args['joins'][0]['conditions'][0] = 'OrgIdentity.id=OrgIdentitySourceRecord.org_identity_id';
    $args['contain'] = array(
      'Address',
      'EmailAddress',
      'Identifier',
      'Name',
      'PrimaryName',
      'TelephoneNumber'
    );
    
    // XXX We should use findForUpdate here, but that doesn't support contains yet
    $curorgid = $this->OrgIdentitySourceRecord->OrgIdentity->find('first', $args);
    
    if(!isset($curorgid['OrgIdentity']['id'])) {
      $dbc->rollback();
      throw new InvalidArgumentException(_txt('er.ois.nolink'));
    }
    
    // Pull the OrgIdentitySourceRecord. Due to various subtleties (bugs?) around ChangelogBehavior
    // we can't get it out of the above find.
    
    $args = array();
    $args['conditions']['OrgIdentitySourceRecord.org_identity_source_id'] = $id;
    $args['conditions']['OrgIdentitySourceRecord.sorid'] = $sourceKey;
    $args['contain'] = false;
    
    $cursrcrec = $this->OrgIdentitySourceRecord->find('first', $args);
    
    $status = 'unknown';
    
    if(isset($brec['raw']) && isset($cursrcrec['OrgIdentitySourceRecord']['source_record'])
       && $brec['raw'] == $cursrcrec['OrgIdentitySourceRecord']['source_record']) {
      // Source record has not changed, so don't bother doing anything
      
      $status = 'unchanged';
    } elseif(empty($brec['orgidentity'])) {
      // The record is no longer available in the source. We'll update the Org Identity
      // to status = removed, but we won't delete it, especially since it could be a
      // flaky connection or bad data.
      
      $this->OrgIdentitySourceRecord->OrgIdentity->id = $curorgid['OrgIdentity']['id'];
      $this->OrgIdentitySourceRecord->OrgIdentity->saveField('status', OrgIdentityStatusEnum::Removed);
      
      // Update the OrgIdentitySourceRecord
      
      $orgsrc = array();
      
      $orgsrc['OrgIdentitySourceRecord'] = array(
        'org_identity_source_id' => $id,
        'sorid'                  => $sourceKey,
        'source_record'          => null,
        'last_update'            => date('Y-m-d H:i:s')
      );
      
      if(!empty($cursrcrec['OrgIdentitySourceRecord']['id'])) {
        $orgsrc['OrgIdentitySourceRecord']['id'] = $cursrcrec['OrgIdentitySourceRecord']['id'];
      }
      
      $this->OrgIdentitySourceRecord->save($orgsrc);
      
      // Cut a history record
      
      $this->OrgIdentitySourceRecord->OrgIdentity->HistoryRecord->record(null,
                                                                         null,
                                                                         $this->OrgIdentitySourceRecord->OrgIdentity->id,
                                                                         $actorCoPersonId,
                                                                         ActionEnum::OrgIdRemovedSource,
                                                                         _txt('rs.org.src.rm',
                                                                              array($this->cdata['OrgIdentitySource']['description'],
                                                                                    $this->cdata['OrgIdentitySource']['id'])));
      
      $status = 'removed';
    } else {
      // Start building a new org identity to save. There a quite a few details to pay attention to.
      // First, copy the existing id and co_id to the new record.
      
      $orgid = $brec['orgidentity'];
      $orgid['OrgIdentity']['id'] = $curorgid['OrgIdentity']['id'];
      $orgid['OrgIdentity']['co_id'] = $curorgid['OrgIdentity']['co_id'];
      // Set the status (just in case)
      $orgid['OrgIdentity']['status'] = OrgIdentityStatusEnum::Synced;
      
      // And relink PrimaryName
      $orgid['PrimaryName']['id'] = $curorgid['PrimaryName']['id'];
      
      // The above two will now update when we save. The hasMany models are trickier,
      // since we don't really know if there are multiple values which ones correspond
      // to which. On the one hand, most of the time we will have either zero or one
      // records. On the other hand, we want to support multiple values "properly".
      
      // Remove primary name from the name list, since we've already covered that.
      
      for($i = 0;$i < count($curorgid['Name']);$i++) {
        if(isset($curorgid['Name'][$i]['primary_name'])
           && $curorgid['Name'][$i]['primary_name']) {
          unset($curorgid['Name'][$i]);
          break;
        }
      }
      
      // A reasonable compromise to solve the multiple model problem would be to support one type
      // per object (eg: one official email address), as this should cover most use cases.
      
      // Walk the current data and map keys into the new data. If a current key does not
      // exist we'll need to delete it manually.
      
      $assocModels = array('Address', 'EmailAddress', 'Identifier', 'Name', 'TelephoneNumber');
      
      foreach($assocModels as $model) {
        foreach($curorgid[$model] as $mdata) {
          if(isset($mdata['id']) && !empty($mdata['type'])) {
            // Have we found this record in the new data?
            $found = false;
            
            if(!empty($orgid[$model])) {
              for($i = 0;$i < count($orgid[$model]);$i++) {
                if(isset($orgid[$model][$i]['type'])
                   && $orgid[$model][$i]['type'] == $mdata['type']) {
                  // Set the key to link the model
                  $orgid[$model][$i]['id'] = $mdata['id'];
                  
                  $found = true;
                  break;
                }
              }
            }
            
            if(!$found) {
              // This related model appears to no longer exist, remove it
              $this->OrgIdentitySourceRecord->OrgIdentity->$model->delete($mdata['id']);
            }
          }
        }
      }
      
      // Add the new Source Record and existing key
      
      $orgid['OrgIdentitySourceRecord'] = array(
        'org_identity_source_id' => $id,
        'sorid'                  => $sourceKey,
        'source_record'          => isset($brec['raw']) ? $brec['raw'] : null,
        'last_update'            => date('Y-m-d H:i:s')
      );
      
      if(!empty($cursrcrec['OrgIdentitySourceRecord']['id'])) {
        $orgid['OrgIdentitySourceRecord']['id'] = $cursrcrec['OrgIdentitySourceRecord']['id'];
      }
      
      $this->OrgIdentitySourceRecord->OrgIdentity->saveAssociated($orgid);
      
      // Cut a history record
      $cstr = $this->OrgIdentitySourceRecord->OrgIdentity->changesToString($orgid,
                                                                           $curorgid,
                                                                           null,
                                                                           $assocModels);
      
      $this->OrgIdentitySourceRecord->OrgIdentity->HistoryRecord->record(null,
                                                                         null,
                                                                         $this->OrgIdentitySourceRecord->OrgIdentity->id,
                                                                         $actorCoPersonId,
                                                                         ActionEnum::OrgIdEditedSource,
                                                                         _txt('rs.org.src.sync',
                                                                              array($this->cdata['OrgIdentitySource']['description'],
                                                                                    $this->cdata['OrgIdentitySource']['id'])));
      
      $this->OrgIdentitySourceRecord->OrgIdentity->HistoryRecord->record(null,
                                                                         null,
                                                                         $this->OrgIdentitySourceRecord->OrgIdentity->id,
                                                                         $actorCoPersonId,
                                                                         ActionEnum::OrgIdEditedSource,
                                                                         $cstr);
      
      $status = 'synced';
    }
    
    // Commit
    $dbc->commit();
    
    return array('id' => $curorgid['OrgIdentity']['id'], 'status' => $status);
  }
}