<?php
/**
 * COmanage Registry Organizational Source Model
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
 * @since         COmanage Registry v4.4.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

class OrganizationSource extends AppModel {
  // Define class name for cake
  public $name = "OrganizationSource";
  
  // Current schema version for API
  public $version = "1.0";
  
  // Add behaviors
  public $actsAs = array('Changelog' => array('priority' => 5),
                         'Containable');
  
  // Association rules from this model to other models
  public $belongsTo = array(
    'Co'
  );

  public $hasMany = array(
    "OrganizationSourceRecord" => array(
      'dependent' => true
    )
  );
  
  public $hasManyPlugins = array(
    "orgsource" => array(
      'coreModelFormat' => '%s'
    )
  );
  
  // Default display field for cake generated views
  public $displayField = "description";
  
  // Validation rules for table elements
  public $validate = array(
    'co_id' => array(
      'content' => array(
        'rule' => 'numeric',
        'required' => true,
        'allowEmpty' => false
      )
    ),
    'description' => array(
      'rule' => array('validateInput'),
      'required' => true,
      'allowEmpty' => false
    ),
    'plugin' => array(
      // XXX This should be a dynamically generated list based on available plugins
      'rule' => 'notBlank',
      'required' => true,
      'allowEmpty' => false
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
      'allowEmpty' => false
    ),
    'sync_mode' => array(
      'rule' => array(
        'inList',
        array(
          OrgSyncModeEnum::Accrual,
          OrgSyncModeEnum::Full,
          OrgSyncModeEnum::Manual,
          OrgSyncModeEnum::Update
        )
      ),
      'required' => true,
      'allowEmpty' => false
    )
  );

  // The backend plugin madel, cached
  protected $pmodel = null;
  
  /**
   * Callback after model save.
   *
   * @since  COmanage Registry v4.4.0
   * @param  Boolean $created True if new model is saved (ie: add)
   * @param  Array $options Options, as based to model::save()
   * @return Boolean True on success
   */
  
  public function afterSave($created, $options = Array()) {
    if($created) {
      // Create an instance of the plugin source.
      
      $pluginName = $this->data['OrganizationSource']['plugin'];
      $modelName = $pluginName;
      $pluginModelName = $pluginName . "." . $modelName;
      
      $source = array();
      $source[$modelName]['organization_source_id'] = $this->id;
      
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
   * @since COmanage Registry v4.4.0
   * @param Integer $id OrganizationSource ID
   * @throws InvalidArgumentException
   */
  
  public function bindPluginBackendModel($id) {
    // This function only needs to be called in non-MVC contexts (eg: via OrgSyncJob),
    // since OrganizationSourcesController will bind the associated models.

    // Pull the plugin information associated with $id
    
    $args = array();
    $args['conditions']['OrganizationSource.id'] = $id;
    // Only bind active sources
    $args['conditions']['OrganizationSource.status'] = SuspendableStatusEnum::Active;
    // We need the related model to pass to the backend, but we don't know it yet.
    $args['contain'] = false;
    
    $os = $this->find('first', $args);

    if(empty($os)) {
      throw new InvalidArgumentException(_txt('er.notfound', array(_txt('ct.organization_sources.1'), $params['os_id'])));
    }

    // Create the relation

    $this->bindModel(array('hasOne' => array($os['OrganizationSource']['plugin'])), false);
  }

  /**
   * Obtain the model for the instantiated backend.
   * 
   * @since  COmange Registry v4.4.0
   * @param  int  $id   Plugin instantiation ID
   * @return object     Plugin model
   */

  protected function getPluginModel(int $id) {
    if($this->pmodel) {
      return $this->pmodel;
    }

    // Unlike Org Identity Source, we only need the main plugin model

    $args = array();
    $args['conditions']['OrganizationSource.id'] = $id;
    $args['conditions']['OrganizationSource.status'] = SuspendableStatusEnum::Active;
    $args['contain'] = false;
    
    $os = $this->find('first', $args);

    if(empty($os)) {
      throw new InvalidArgumentException(_txt('er.notfound', array(_txt('ct.organization_sources.1'), $params['os_id'])));
    }

    $m = $os['OrganizationSource']['plugin'];

    // While we're here, get the plugin its configuration if it doesn't have it already

    if(!$this->$m->getConfig()) {
      $cfg = $this->$m->find('first', array($m.'.id' => $id));

      $this->$m->setConfig($cfg);
    }

    $this->pmodel = $this->$m;

    return $this->$m;
  }
  
  /**
   * Retrieve a record from an Organization Source Backend.
   *
   * @since  COmanage Registry v4.4.0
   * @param  Integer $id OrganizationSource to search
   * @param  String $key Record key to retrieve
   * @return Array Raw record and Array in Organization format
   * @throws InvalidArgumentException
   */
  
  public function retrieve($id, $key) {
    $pModel = $this->getPluginModel($id);

    return $pModel->retrieve($key);
  }
  
  /**
   * Perform a search against an Organization Source Backend.
   *
   * @since  COmanage Registry v4.4.0
   * @param  Integer $id OrganizationSource to search
   * @param  Array $attributes Array in key/value format, where key is the same as returned by searchAttributes()
   * @return Array Array in Organization format
   * @throws InvalidArgumentException
   */
  
  public function search($id, $attributes) {
    $pModel = $this->getPluginModel($id);

    return $pModel->search($attributes);
  }

  /**
   * Obtain the set of searchable attributes for the Organization Source.
   *
   * @since  COmanage Registry v4.4.0
   * @param  Integer $id OrganizationSource to search
   * @return Array Array of searchable attributes
   * @throws InvalidArgumentException
   */
  
  public function searchableAttributes($id) {
    $pModel = $this->getPluginModel($id);

    return $pModel->searchableAttributes();
  }
  
  /**
   * Sync an existing organizational identity record based on a result from an Org Identity Source.
   *
   * @since  COmanage Registry v4.4.0
   * @param  Integer $id              OrganizationSource to query
   * @param  String  $sourceKey       Record key to retrieve as basis of Org Identity
   * @param  Integer $actorCoPersonId CO Person ID of actor syncing new Org Identity
   * @param  Integer $coJobId         If being run as part of a CO Job, the CO Job ID
   * @param  Boolean $force           If true, force a sync even if the source record has not changed
   * @param  Boolean $processDeletes  If true, delete Organizations no longer available in the backend
   * @return Array                    'id' is ID of Organization, and 'status' is "synced", "unchanged", or "removed"
   * @throws InvalidArgumentException
   * @throws RuntimeException
   */
  
  public function syncOrganization(
    $id, 
    $sourceKey,
// XXX Do we need $actorCoPersonId? We're not recording history...
    $actorCoPersonId=null,
    $coJobId=null,
    $force=false,
    $processDeletes=false
  ) {
    // Unlike OrgIdentitySource, we don't use create, only sync.

    // The related models we support
    $relatedModels = array(
      'Address',
      'AdHocAttribute',
      'Contact',
      'EmailAddress',
      'Identifier',
      'TelephoneNumber',
      'Url'
    );

    // Get our configuration
    $pModel = $this->getPluginModel($id);
    $oscfg = $pModel->getConfig();

    // Pull the current record from the backend
    $backendRecord = null;

    try {
      $backendRecord = $this->retrieve($id, $sourceKey);
    }
    catch(InvalidArgumentException $e) {
      // Record not found, fall through, we'll process this below
    }
    // Let other exceptions bubble up, including OverflowExceptions (> 1 record returned)

    // Next see if we have an OrganizationSourceRecord
    
    $args = array();
    $args['conditions']['OrganizationSourceRecord.organization_source_id'] = $id;
    $args['conditions']['OrganizationSourceRecord.source_key'] = $sourceKey;
    $args['contain'] = array('Organization' => $relatedModels);

    $osr = $this->OrganizationSourceRecord->find('first', $args);

    if(empty($backendRecord)) {
      if(!empty($osr['Organization']['id'])) {
        if($processDeletes) {
          // If we are processing deletes, it means we are in Full mode, and so we should
          // actually (try to) delete the specified Organization. (If the admin wants to
          // keep these Organizations around, they can use Accrual or Update modes.)
          // There _should_ be an OSR since we shouldn't know about an Organization that
          // no longer has a backend record otherwise, but we'll check just in case.

          $this->OrganizationSourceRecord->Organization->delete($osr['Organization']['id']);
        }

        // We'll note the backend record was removed, whether or not we delet it
        if($coJobId) {
          $this->Co->CoJob->CoJobHistoryRecord->record($coJobId,
                                                        $sourceKey,
                                                        $processDeletes ?  _txt('rs.os.src.deleted') : _txt('rs.os.src.removed'),
                                                        null,
                                                        null,
                                                        JobStatusEnum::Complete);
        }
        
        return array(
          'id' => $osr['Organization']['id'],
          'status' => $processDeletes ? 'deleted' : 'removed'
        );
      } else {
        throw new RuntimeException("Unexpected empty Organization ID when retrieving $sourceKey");
      }
    }

    // Start a transaction
    $dbc = $this->getDataSource();
    $dbc->begin();

    if(empty($osr)) {
      // This is a new record, start by creating an Organization Source Record
      // and a new Organization. The backend record is more or less in the format
      // we need, including for related models.

      $org = $backendRecord['rec'];

      // Inject the CO ID
      $org['Organization']['co_id'] = $oscfg['OrganizationSource']['co_id'];

      $org['OrganizationSourceRecord'] = array(
        'organization_source_id'  => $id,
        'source_key'              => $sourceKey,
        'source_record'           => $backendRecord['raw'],
        'last_update'             => date('Y-m-d H:i:s'),
      );

      $this->OrganizationSourceRecord->Organization->clear();
      $this->OrganizationSourceRecord->Organization->id = null;

      if(!$this->OrganizationSourceRecord->Organization->saveAssociated($org, array('trustVerified' => true))) {
        $dbc->rollback();
        throw new RuntimeException(_txt('er.db.save-a', array('Organization saveAssociated')));
      }

      $dbc->commit();

      if($coJobId) {
        $this->Co->CoJob->CoJobHistoryRecord->record($coJobId,
                                                      $sourceKey,
                                                      _txt('rs.os.src.synced'),
                                                      null,
                                                      null,
                                                      JobStatusEnum::Complete);
      }
      
      return array(
        'id' => $this->OrganizationSourceRecord->Organization->id,
        'status' => 'synced'
      );
    } else {
      // We're updating an existing record

      if(!$force) {
        // Check to see if the record change

        if(!empty($osr['OrganizationSourceRecord']['source_record'])
           && !empty($backendRecord['raw'])
           && ($osr['OrganizationSourceRecord']['source_record'] == $backendRecord['raw'])) {
          // Record is unchanged

          if($coJobId) {
            $this->Co->CoJob->CoJobHistoryRecord->record($coJobId,
                                                          $sourceKey,
                                                          _txt('rs.os.src.unchanged'),
                                                          null,
                                                          null,
                                                          JobStatusEnum::Complete);
          }

          $dbc->commit();

          return array(
            'id' => $osr['Organization']['id'],
            'status' => 'unchanged'
          );
        }
      }

      // We'll first update the Organization and OrganizationSourceRecord by basically resaving them
      $org = array();
      $org['Organization'] = $backendRecord['rec']['Organization'];
      $org['OrganizationSourceRecord'] = array(
        'organization_source_id'  => $id,
        'source_key'              => $sourceKey,
        'source_record'           => $backendRecord['raw'],
        'last_update'             => date('Y-m-d H:i:s'),
      );

      // Inject the primary and foreign keys
      $org['Organization']['id'] = $osr['Organization']['id'];
      $org['Organization']['co_id'] = $osr['Organization']['co_id'];
      $org['OrganizationSourceRecord']['id'] = $osr['OrganizationSourceRecord']['id'];

      $this->OrganizationSourceRecord->Organization->clear();
      $this->OrganizationSourceRecord->Organization->id = null;

      if(!$this->OrganizationSourceRecord->Organization->saveAssociated($org, array('trustVerified' => true))) {
        $dbc->rollback();
        throw new RuntimeException(_txt('er.db.save-a', array('Organization saveAssociated')));
      }

      foreach($relatedModels as $m) {
        // Because there are no primary keys in the source data, we have to guess if the
        // current record is known or not. This effectively means we can only add or delete
        // (or leave existing records unchanged).

        // This is based on OrgIdentitySource::syncOrgIdentity()

        // Pointer to model $m describes (eg $Identifier)
        $model = $this->OrganizationSourceRecord->Organization->$m;
        // Model key used by changelog, eg identifier_id
        $mkey = Inflector::underscore($model->name) . '_id';
        // Model in pluralized format, eg email_addresses
        $mpl = Inflector::tableize($model->name);
        // Model (singular) in localized text string
        $mlang = _txt('ct.' . $mpl . '.1');
        
        // Records obtained from the Organization Source
        $newRecords = isset($backendRecord['rec'][$m]) ? $backendRecord['rec'][$m] : array();
        // Records attached to the current Organization
        $curRecords = array();
        
        // Map each current record into a "new" Organization record and prepare for comparison
        
        foreach($osr['Organization'][$m] as $curOrgRecord) {
          $curRecord = $curOrgRecord;

          // changesForModel will get rid of most metadata keys for us
          // unset($curRecord['co_department_id']);
          unset($curRecord['organization_id']);
          
          $curRecords[ $curOrgRecord['id'] ] = $curRecord;
        }
        
        // Now that the lists are ready, walk through them and process any changes.
        // First look for old records to delete (including changed records that we'll delete and add).
        
        foreach($curRecords as $curRecord) {
          $found = false;
          
          foreach($newRecords as $i => $newRecord) {
            $diff = $model->compareChanges($m, $newRecord, $curRecord);

            if(empty($diff)) {
              // $curRecord has a corresponding $newRecord, so there's no change to process.
              // Also remove $newRecord from $newRecords so we don't have to compare it again
              // in the next step.
              
              unset($newRecords[$i]);
              $found = true;
              break;
            }
          }
          
          if(!$found) {
            // Remove $curRecord
            $model->delete($curRecord['id']);
          }
        }
        
        // Now look for new records to add.
        
        foreach($newRecords as $newRecord) {
          // Since we've already found all records that are the same in both arrays,
          // we simply add each remaining new record. Insert the Organization ID
          // to link the record.
          
          $newrec = array();
          $newrec[$m] = $newRecord;
          $newrec[$m]['organization_id'] = $osr['Organization']['id'];
          
          $model->clear();
        
          if(!$model->save($newrec[$m],
                            // We honor the email verified status provided by the source
                            ($m == 'EmailAddress' ? array('trustVerified' => true) : array()))) {
            // In this case we'll trigger the rollback early so we can end the transaction
            // and attempt to record a failure record. (The final rollback should become a no-op.)
            $dbc->rollback();
            throw new RuntimeException(_txt('er.db.save-a', array($m)));
          }
        }
      }

      $dbc->commit();

      if($coJobId) {
        $this->Co->CoJob->CoJobHistoryRecord->record($coJobId,
                                                      $sourceKey,
                                                      _txt('rs.os.src.synced'),
                                                      null,
                                                      null,
                                                      JobStatusEnum::Complete);
      }
      
      return array(
        'id' => $osr['Organization']['id'],
        'status' => 'synced'
      );
    }
  }
  
  /**
   * Sync all records in an Organization Source.
   *
   * @since  COmanage Registry v4.4.0
   * @param  Array   $id        Organization Source ID
   * @param  integer $coJobId   CO Job ID
   * @param  Boolean $force     If true, force a sync even if the source record has not changed
   * @return boolean            True on success
   * @throws RuntimeException
   */
  
  public function syncOrganizationSource($id, $coJobId, $force=false) {
    // We operate based on the configured sync_mode:
    //  Accrual: Add new records from the source, and update any existing records
    //  Full: As for Accrual, but delete any records that are no longer present in the source
    //  Update: Only update records we already know about, no deletes
    //  Manual: No automatic syncing, manually sync individual records only

    // We don't check here that the source is in Manual mode in case an admin
    // wants to manually force a sync. (syncAll honors that setting.)
    // - But then what mode do we use? Accrual? Full? Update?
    //   Maybe we should check the setting
    
    $this->bindPluginBackendModel($id);

    $pModel = $this->getPluginModel($id);
    $oscfg = $pModel->getConfig();

    if($oscfg['OrganizationSource']['sync_mode'] == OrgSyncModeEnum::Manual) {
      throw new RuntimeException(_txt('er.os.sync.manual', array($oscfg['OrganizationSource']['id'])));
    }

    // Both of the initial backends (Federation and ROR) have dedicated endpoints to
    // obtain the full dataset, and neither offers a changelist option. We'll first
    // give the backends a chance to download those datasets, which can also be used
    // for update queries (since, eg, ROR has a rate limit, and also it will be slower
    // to issue one network call per existing record). (A base version of preRunChecks()
    // is defined in OrganizationSourceBackend, so we don't need to use method_exists().)

    $pModel->preRunChecks($coJobId);

    // Next pull the set of records in the backend

    $counts = array(
      'deleted' => 0,       // Removed from backend _and_ Organization was deleted
      'removed' => 0,       // Removed from backend
      'synced' => 0,
      'unchanged' => 0,
      'error' => 0
    );

    $inventory = array();
    $processDeletes = $oscfg['OrganizationSource']['sync_mode'] == OrgSyncModeEnum::Full;

    if($oscfg['OrganizationSource']['sync_mode'] == OrgSyncModeEnum::Accrual
       || $oscfg['OrganizationSource']['sync_mode'] == OrgSyncModeEnum::Full) {
      // This will throw an error if not supported, or return a list of source keys
      $inventory = $pModel->inventory();

      if(empty($inventory)) {
        // Sanity check, something maybe went wrong

        throw new RuntimeException('er.os.sync.empty');
      }

      foreach($inventory as $sourceKey) {
        if($this->Co->CoJob->canceled($coJobId)) { return false; }

        try {
          $result = $this->syncOrganization(
            $id,
            $sourceKey,
            null,
            $coJobId,
            $force,
            $processDeletes
          );

          $counts[ $result['status'] ]++;
        }
        catch(Exception $e) {
          $counts['error']++;

          $this->Co->CoJob->CoJobHistoryRecord->record($coJobId,
                                                        $sourceKey,
                                                        $e->getMessage(),
                                                        null,
                                                        null,
                                                        JobStatusEnum::Failed);
        }
      }
    }
    
    if($oscfg['OrganizationSource']['sync_mode'] == OrgSyncModeEnum::Full
       || $oscfg['OrganizationSource']['sync_mode'] == OrgSyncModeEnum::Update) {
      // Pull the current set of synced records and update them one at a time.
      // In full mode, $inventory will be processed already, so we can skip any records
      // present there (which is presumably most of them; the rest are presumably deletes).

      $args = array();
      $args['conditions']['OrganizationSourceRecord.organization_source_id'] = $id;
      $args['contain'] = false;

      $orgRecords = $this->OrganizationSourceRecord->find('all', $args);

      if(!empty($orgRecords)) {
        foreach($orgRecords as $rec) {
          if($oscfg['OrganizationSource']['sync_mode'] == OrgSyncModeEnum::Full
            && in_array($rec['OrganizationSourceRecord']['source_key'], $inventory)) {
            // Skip records we already processed above
            continue;
          }

          if($this->Co->CoJob->canceled($coJobId)) { return false; }
          
          try {
            $result = $this->syncOrganization(
              $id,
              $rec['OrganizationSourceRecord']['source_key'],
              null,
              $coJobId,
              $force,
              $processDeletes
            );

            $counts[ $result['status'] ]++;
          }
          catch(Exception $e) {
            $counts['error']++;

            $this->Co->CoJob->CoJobHistoryRecord->record($coJobId,
                                                         $rec['OrganizationSourceRecord']['source_key'],
                                                         $e->getMessage(),
                                                         null,
                                                         null,
                                                         JobStatusEnum::Failed);
          }
        }
      }
    }

    $this->Co->CoJob->finish($coJobId, json_encode($counts));

    return true;
  }
}