<?php
/**
 * COmanage Registry Organizational Identity Source Model
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
    'Co',
    'CoPipeline'
  );
  
  public $hasMany = array(
    "CoEnrollmentSource" => array(
      'dependent' => true
    ),
    "CoGroupOisMapping" => array(
      'dependent' => true
    ),
    "CoProvisioningTarget" => array(
      'foreignKey' => 'provision_co_group_id'
    ),
    "OrgIdentitySourceRecord" => array(
      'dependent' => true
    )
  );
  
  public $hasManyPlugins = array(
    "orgidsource" => array(
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
    ),
    'sync_mode' => array(
      'rule' => array(
        'inList',
        array(
          SyncModeEnum::Full,
          SyncModeEnum::Manual,
          SyncModeEnum::Query,
          SyncModeEnum::Update
        )
      ),
      'required' => true,
    ),
    'sync_query_mismatch_mode' => array(
      'rule' => array(
        'inList',
        array(
          OrgIdentityMismatchEnum::CreateNew,
          OrgIdentityMismatchEnum::Ignore
        )
      ),
      'required' => false,
      'allowEmpty' => true
    ),
    'sync_query_skip_known' => array(
      'rule' => array('boolean'),
      'required' => false,
      'allowEmpty' => true
    ),
    'sync_on_user_login' => array(
      'rule' => array('boolean'),
      'required' => false,
      'allowEmpty' => true
    ),
    'co_pipeline_id' => array(
      'content' => array(
        'rule' => 'numeric',
        'required' => false,
        'allowEmpty' => true,
        'unfreeze' => 'CO'
      )
    ),
    'hash_source_record' => array(
      'rule' => array('boolean'),
      'required' => false,
      'allowEmpty' => true
    )
  );
  
  // Retrieved data, cached for later use. We don't use ->data here to avoid
  // interfering with Cake mechanics.
  protected $cdata = null;
  
  /**
   * Callback after model save.
   *
   * @since  COmanage Registry v2.0.0
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
   * @since COmanage Registry v2.0.0
   * @param Integer $id OrgIdentitySource ID
   * @return Object Plugin Backend Model reference
   * @throws InvalidArgumentException
   */
  
  protected function bindPluginBackendModel($id) {
    // Pull the plugin information associated with $id
    
    $args = array();
    $args['conditions']['OrgIdentitySource.id'] = $id;
    // We need the related model to pass to the backend, but we don't know it yet.
    $args['contain'] = false;
    
    $ois = $this->find('first', $args);
    
    if(empty($ois)) {
      throw new InvalidArgumentException(_txt('er.notfound', array(_txt('ct.org_identity_sources.1'), $id)));
    }
    
    $pmodel = $ois['OrgIdentitySource']['plugin'];
    
    // Now pull the plugin configuration
    $args = array();
    $args['conditions'][$pmodel . '.org_identity_source_id'] = $id;
    $args['contain'] = false;
    
    // Load the source model
    $smodel = $pmodel . '.' . $pmodel;
    $SPlugin = ClassRegistry::init($smodel);
    
    $source = $SPlugin->find('first', $args);
    
    if(empty($source[$pmodel])) {
      throw new InvalidArgumentException(_txt('er.notfound', array($pmodel, $id)));
    }
    
    $ois[$pmodel] = $source[$pmodel];
    
    // Store for possible later use
    $this->cdata = $ois;
    
    // Load the backend model
    $bmodel = $pmodel . '.' . $pmodel . 'Backend';
    $Backend = ClassRegistry::init($bmodel);
    
    // And give it its configuration
    if(!isset($ois[ $pmodel ])) {
      // The related model isn't set, bind it
      
      $this->bindModel(array('hasOne' => array($pmodel)), false);
      
      $args = array();
      $args['conditions'][$pmodel.'.org_identity_source_id'] = $id;
      $args['contain'] = false;
      
      $pl = $this->$pmodel->find('first', $args);
      
      $Backend->setConfig($pl[ $pmodel ]);
      // XXX need to find
    } else {
      $Backend->setConfig($ois[ $pmodel ]);
    }
    
    return $Backend;
  }

  /**
   * Create a new organizational identity record based on a result from an Org Identity Source.
   *
   * @since  COmanage Registry v2.0.0
   * @param  Integer $id OrgIdentitySource to query
   * @param  String  $sourceKey Record key to retrieve as basis of new Org Identity
   * @param  Integer $actorCoPersonId CO Person ID of actor creating new Org Identity
   * @param  Integer $coId CO ID, if org identities are not pooled
   * @param  Integer $targetCoPersonId CO Person ID to link new Org Identity to, if already known
   * @param  Boolean $provision Whether to execute provisioning
   * @param  Integer $coPetitionId If executing as part of a petition, the CO Petition ID
   * @return Integer ID of new Org Identity
   * @throws InvalidArgumentException
   * @throws OverflowException
   * @throws RuntimeException
   */
  
  public function createOrgIdentity($id,
                                    $sourceKey,
                                    $actorCoPersonId=null,
                                    $coId=null,
                                    $targetCoPersonId=null,
                                    $provision=true,
                                    $coPetitionId=null) {
    // Unlike CoPipeline::syncOrgIdentityToCoPerson, we have a separate call
    // for create vs update. This is because $Backend->retrieve() will return
    // data in a format that is more or less ready for a direct save.
    
    // First make sure we don't already have a record for id+sourceKey
    
    $args = array();
    $args['conditions']['OrgIdentitySourceRecord.org_identity_source_id'] = $id;
    $args['conditions']['OrgIdentitySourceRecord.sorid'] = $sourceKey;
    // Finding via a join to OrgIdentity bypasses ChangelogBehavior, so we need to
    // manually exclude those records.
    $args['conditions']['OrgIdentitySourceRecord.deleted'] = false;
    $args['conditions'][] = 'OrgIdentitySourceRecord.org_identity_source_record_id IS NULL';
    
    $cnt = $this->OrgIdentitySourceRecord->OrgIdentity->find('count', $args);
    
    if($cnt > 0) {
      throw new OverflowException(_txt('er.ois.linked'));
    }
    
    // Pull record from source
    $brec = $this->retrieve($id, $sourceKey);
    
    // This will throw an exception if invalid
    $this->validateOISRecord($brec);
    
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
      'source_record'          => (!empty($brec['hash'])
                                   ? $brec['hash']
                                   : (isset($brec['raw'])
                                      ? $brec['raw']
                                      : null)),
      'last_update'            => date('Y-m-d H:i:s'),
      'co_petition_id'         => $coPetitionId
    );
    
    // Start a transaction
    $dbc = $this->getDataSource();
    $dbc->begin();
    
    $this->OrgIdentitySourceRecord->OrgIdentity->clear();
    $this->OrgIdentitySourceRecord->OrgIdentity->id = null;
    
    if(!$this->OrgIdentitySourceRecord->OrgIdentity->saveAssociated($orgid, array('trustVerified' => true))) {
      $dbc->rollback();
      throw new RuntimeException(_txt('er.db.save-a', array('OrgIdentity saveAssociated')));
    }
    
    $orgIdentityId = $this->OrgIdentitySourceRecord->OrgIdentity->id;
    
    // Cut a history record
    try {
      $this->OrgIdentitySourceRecord->OrgIdentity->HistoryRecord->record(null,
                                                                         null,
                                                                         $orgIdentityId,
                                                                         $actorCoPersonId,
                                                                         ActionEnum::OrgIdAddedSource,
                                                                         _txt(($coPetitionId ? 'rs.org.src.new.pet' : 'rs.org.src.new'),
                                                                              array($this->cdata['OrgIdentitySource']['description'],
                                                                                    $this->cdata['OrgIdentitySource']['id'])));
      
      // Also attach a history record to the petition, if appropriate
      
      if($coPetitionId) {
        $this->Co->CoPetition->CoPetitionHistoryRecord->record($coPetitionId,
                                                               $actorCoPersonId,
                                                               PetitionActionEnum::OrgIdentitySourced,
                                                               _txt('rs.org.src.new',
                                                                    array($this->cdata['OrgIdentitySource']['description'],
                                                                          $this->cdata['OrgIdentitySource']['id'])));
      }
    }
    catch(Exception $e) {
      $dbc->rollback();
      throw new RuntimeException($e->getMessage());
    }
    
    if($targetCoPersonId) {
      // Create an Org Identity Link, since we already know the OrgIdentity ID and
      // CoPerson ID. This will also ensure the pipeline finds the correct CO Person
      // record to act on.
      
      $coOrgLink = array();
      $coOrgLink['CoOrgIdentityLink']['org_identity_id'] = $orgIdentityId;
      $coOrgLink['CoOrgIdentityLink']['co_person_id'] = $targetCoPersonId;
      
      // Clear the link in case we're called in loop
      $this->Co->CoPerson->CoOrgIdentityLink->clear();
      
      // CoOrgIdentityLink is not currently provisioner-enabled, but we'll disable
      // provisioning just in case that changes in the future.
      
      if($this->Co->CoPerson->CoOrgIdentityLink->save($coOrgLink, array("provision" => false))) {
        // Create a history record
        try {
          $this->Co->CoPerson->HistoryRecord->record($targetCoPersonId,
                                                     null,
                                                     $orgIdentityId,
                                                     $actorCoPersonId,
                                                     ActionEnum::CoPersonOrgIdLinked,
                                                     _txt('rs.org.src.link', array($this->cdata['OrgIdentitySource']['description'],
                                                                                   $this->cdata['OrgIdentitySource']['id'])));
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
    
    // Invoke pipeline, if configured
    try {
      $this->executePipeline($id,
                             $orgIdentityId,
                             SyncActionEnum::Add,
                             $actorCoPersonId,
                             $provision,
                             $brec['raw'],
                             $this->OrgIdentitySourceRecord->OrgIdentity->OrgIdentitySourceRecord->id);
    }
    catch(Exception $e) {
      $dbc->rollback();
      throw new RuntimeException($e->getMessage());
    }
    
    // Commit
    $dbc->commit();
    
    return $this->OrgIdentitySourceRecord->OrgIdentity->id;
  }
  
  /**
   * Execute the appropriate pipeline for the specified Org Identity.
   *
   * @since  COmanage Registry v2.0.0
   * @param  Integer $id OrgIdentitySource
   * @param  Integer $orgIdentityId OrgIdentity ID
   * @param  Integer $actorCoPersonId CO Person ID of actor creating new Org Identity
   * @param  String  $syncAction "add", "update", or "delete"
   * @param  String  $oisRawRecord The raw record
   * @param  Integer $oisRecordId  The OIS Record ID
   * @param  Boolean $provision Whether to execute provisioning
   */
  
  protected function executePipeline($id, $orgIdentityId, $action, $actorCoPersonId, $provision=true, $oisRawRecord=null, $oisRecordId=null) {
    $pipelineId = $this->OrgIdentitySourceRecord->OrgIdentity->pipeline($orgIdentityId);
    
    if($pipelineId) {
      return $this->CoPipeline->execute($pipelineId, $orgIdentityId, $action, $actorCoPersonId, $provision, $oisRawRecord, $oisRecordId);
    }
    // Otherwise, no pipeline to run, so just return success.
    
    return true;
  }
  
  /**
   * Instantiate the backend model for the specified Source.
   *
   * @since  COmanage Registry v3.1.0
   * @param  Integer $id Organizational Identity Source ID
   * @return Model Plugin backend
   * @throws InvalidArgumentException
   */
  
  public function instantiateBackendModel($id) {
    // We could just make bindPluginBackendModel public, but this
    // allows a layer of indirection in case we need to change the
    // internal (protected) call.
    
    return $this->bindPluginBackendModel($id);
  }
  
  /**
   * Obtain the changelist from a backend.
   *
   * @since  COmanage Registry v3.1.0
   * @param  Integer $id        OrgIdentitySource to query
   * @param  Integer $lastStart Time of start of last request, or 0 if no previous request
   * @param  Integer $curStart  Time of start of current request
   * @return Array Array of source keys that have changed, or false if not supported by backend
   * @throws InvalidArgumentException
   */
  
  public function obtainChangeList($id, $lastStart, $curStart) {
    // Pull changelist from source
    
    $Backend = $this->bindPluginBackendModel($id);
    
    return $Backend->getChangeList($lastStart, $curStart);
  }
  
  /**
   * Obtain all source keys from a backend.
   *
   * @since  COmanage Registry v2.0.0
   * @param  Integer $id OrgIdentitySource to query
   * @return Array Array of source keys
   * @throws DomainException, if backend does not support this query
   * @throws InvalidArgumentException
   */
  
  public function obtainSourceKeys($id) {
    // Pull keys from source
    
    $Backend = $this->bindPluginBackendModel($id);
    
    return $Backend->inventory();
  }
  
  /**
   * Map a raw result into a list of group attributes suitable for mapping.
   *
   * @since  COmanage Registry v2.0.0
   * @param  Integer $id OrgIdentitySource ID
   * @param  String $raw Raw result record
   * @return Array Attributes configured for group processing
   * @throws InvalidArgumentException
   */
  
  public function resultToGroups($id, $raw) {
    $Backend = $this->bindPluginBackendModel($id);
    
    return $Backend->resultToGroups($raw);
  }
  
  /**
   * Retrieve a record from an Org Identity Source Backend.
   *
   * @since  COmanage Registry v2.0.0
   * @param  Integer $id OrgIdentitySource to search
   * @param  String $key Record key to retrieve
   * @return Array Raw record and Array in OrgIdentity format
   * @throws InvalidArgumentException
   */
  
  public function retrieve($id, $key) {
    $Backend = $this->bindPluginBackendModel($id);
    
    $ret = $Backend->retrieve($key);
    
    if(!empty($ret['orgidentity'])) {
      // If we got a result check to see if we're configured to construct an eppn
      
      if(!empty($this->cdata['OrgIdentitySource']['eppn_identifier_type'])) {
        // First see if the backend already generated an eppn. If so, we won't.
        // While we're seaching, also look for the attribute we want to use.
        $existing = false;
        $value = null;
        
        if(!empty($ret['orgidentity']['Identifier'])) {
          foreach($ret['orgidentity']['Identifier'] as $id) {
            if(!empty($id['type'])) {
              if($id['type'] == $this->cdata['OrgIdentitySource']['eppn_identifier_type']) {
                $value = $id['identifier'];
              } elseif($id['type'] == IdentifierEnum::ePPN) {
                $existing = true;
                break;
              }
            }
          }
        }
        
        if(!$existing && $value) {
          // Inject an eppn into the identifier result
          
          if(!empty($this->cdata['OrgIdentitySource']['eppn_suffix'])) {
            $value .= '@' . $this->cdata['OrgIdentitySource']['eppn_suffix'];
          }
          
          $ret['orgidentity']['Identifier'][] = array(
            'identifier' => $value,
            'type'       => IdentifierEnum::ePPN,
            'status'     => StatusEnum::Active,
            'login'      => true
          );
        }
      }
    }
    
    if($this->cdata['OrgIdentitySource']['hash_source_record']) {
      // Create a hash of the raw record. We could also support backend
      // specific change detection (eg: etags) here instead.
      
      if(!empty($ret['raw'])) {
        $ret['hash'] = md5($ret['raw']);
      }
    }
    
    // Inject a SORID
    $ret['orgidentity']['Identifier'][] = array(
      'identifier' => $key,
      'type'       => IdentifierEnum::SORID,
      'status'     => StatusEnum::Active,
      'login'      => false
    );
    
    return $ret;
  }
  
  /**
   * Perform a search against an Org Identity Source Backend.
   *
   * @since  COmanage Registry v2.0.0
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
   * Search all available backends for records matching the specified email address.
   *
   * @since  COmanange Registry v2.0.0
   * @param  String $email Email address to use as a search key
   * @param  Integer $coId CO ID, if org identities not pooled
   * @return Array Array of search results and (if available) associated Org Identities, sorted by backend
   * @throws InvalidArgumentException
   * @deprecated since v2.0.0 CoEnrollmentSources make this function no longer useful
   */
  
  public function searchAllByEmail($email, $coId=null) {
    $ret = array();
    
    // First we need to figure out what plugins we have available.
    
    $args = array();
    $args['conditions']['OrgIdentitySource.status'] = SuspendableStatusEnum::Active;
    if($coId) {
      $args['conditions']['OrgIdentitySource.co_id'] = $coId;
    }
    $args['contain'] = false;
    
    $sources = $this->find('all', $args);
    
    if(empty($sources)) {
      throw new InvalidArgumentException(_txt('er.ois.search.none'));
    }
    
    foreach($sources as $s) {
      $candidates = $this->search($s['OrgIdentitySource']['id'], array('mail' => $email));
      
      foreach($candidates as $key => $c) {
        // Key results by source ID in case different sources return the same keys
        
        // See if there is an associated org identity for the candidate
        
        $args = array();
        $args['conditions']['OrgIdentitySourceRecord.org_identity_source_id'] = $s['OrgIdentitySource']['id'];
        $args['conditions']['OrgIdentitySourceRecord.sorid'] = $key;
        $args['contain'] = array('OrgIdentity');
        
        $ret[ $s['OrgIdentitySource']['id'] ][$key] =
          $this->OrgIdentitySourceRecord->find('first', $args);
        
        // Append the source record retrieved from the backend
        $ret[ $s['OrgIdentitySource']['id'] ][$key]['OrgIdentitySourceData'] = $c;
        
        // And the source info itself
        $ret[ $s['OrgIdentitySource']['id'] ][$key]['OrgIdentitySource'] = $s['OrgIdentitySource'];
      }
    }
    
    return $ret;
  }

  /**
   * Obtain the set of searchable attributes for the Org Identity Source Backend.
   *
   * @since  COmanage Registry v2.0.0
   * @param  Integer $id OrgIdentitySource to search
   * @return Array Array of searchable attributes
   * @throws InvalidArgumentException
   */
  
  public function searchableAttributes($id) {
    $Backend = $this->bindPluginBackendModel($id);
    
    return $Backend->searchableAttributes();
  }
  
  /**
   * Sync all Org Identity Sources. Intended primarily for use by JobShell
   *
   * @since  COmanage Registry v2.0.0
   * @param  integer $coJobId CO Job ID
   * @param  integer $coId    CO ID
   * @param  Boolean $force   If true, force a sync even if the source record has not changed
   * @return boolean True on success
   * @throws RuntimeException
   */
  
  public function syncAll($coJobId, $coId, $force=false) {
    $errors = array();
    
    // Select all org identity sources where status=active
    
    $args = array();
    $args['conditions']['OrgIdentitySource.co_id'] = $coId;
    $args['conditions']['OrgIdentitySource.status'] = SuspendableStatusEnum::Active;
    // Don't automatically sync sources that are in Manual mode
    $args['conditions']['OrgIdentitySource.sync_mode !='] = SyncModeEnum::Manual;
    $args['contain'] = false;
    
    $sources = $this->find('all', $args);
    $doneCnt = 0;
    
    foreach($sources as $src) {
      if($this->Co->CoJob->canceled($coJobId)) { return false; }
      
      try {
        $this->Co->CoJob->update($coJobId, null, null, _txt('jb.ois.sync.start', array($src['OrgIdentitySource']['description'])));
        
        // We don't pass in $coJobId because we want a new Job to be created with
        // the results just from that source.
        $this->syncOrgIdentitySource($src, $force);
        
        $this->Co->CoJob->CoJobHistoryRecord->record($coJobId,
                                                     $src['OrgIdentitySource']['id'],
                                                     _txt('jb.ois.sync.full.finish'),
                                                     null,
                                                     null,
                                                     JobStatusEnum::Complete);
      }
      catch(Exception $e) {
        // What do we do with the exception? We don't want to abort the run,
        // so we'll assemble them and then re-throw them later.
        $errors[] = $e->getMessage();
        
        $this->Co->CoJob->CoJobHistoryRecord->record($coJobId,
                                                     $src['OrgIdentitySource']['id'],
                                                     $e->getMessage(),
                                                     null,
                                                     null,
                                                     JobStatusEnum::Failed);
      }
      
      $doneCnt++;
      $this->Co->CoJob->setPercentComplete($coJobId, floor(($doneCnt * 100)/count($sources)));
    }
    
    if(!empty($errors)) {
      throw new RuntimeException(implode(';', $errors));
    }
    
    return true;
  }
  
  /**
   * Perform a sync of any Org Identity associated with any CO Person associated
   * with a specified login identifier, where the Org Identity is attached to an
   * Org Identity Source in "sync_on_login" mode.
   * Intended to be called at login, ie: not in the context of a specific CO.
   * Org Identity Pooling is NOT supported.
   *
   * @since  COmanage Registry v3.1.0
   * @param  integer $identifier Login identifier
   */
  
  public function syncByIdentifier($identifier) {
    // Look up all org identities associated with this identifier, which also have
    // an associated Org Identity Source with sync_on_user_login set. For each
    // matching identifier, perform a sync.
    
    $args = array();
    $args['conditions']['Identifier.identifier'] = $identifier;
    $args['conditions']['Identifier.status'] = SuspendableStatusEnum::Active;
    $args['conditions']['Identifier.login'] = true;
    // But actually what we want is any Org Identity associated with any
    // CO Person associated with $identifier.
    // That's a lot of nesting!
    $args['contain']['OrgIdentity']['CoOrgIdentityLink']['CoPerson']['CoOrgIdentityLink']['OrgIdentity']['OrgIdentitySourceRecord'] = 'OrgIdentitySource';
    
    $ids = $this->Co->CoPerson->Identifier->find('all', $args);
    
    // The set of Org Identities we want is buried in the result. Note we can get more
    // than one result in $ids since we're querying across COs, but within the result
    // we should only get one CO's worth of stuff.
    
    foreach($ids as $id) {
      if(!empty($id['OrgIdentity']['CoOrgIdentityLink'][0]['CoPerson']['CoOrgIdentityLink'])) {
        // This is the set of Org Identities within this CO that $identifier is associated with
        
        foreach($id['OrgIdentity']['CoOrgIdentityLink'][0]['CoPerson']['CoOrgIdentityLink'] as $lnk) {
          if(!empty($lnk['OrgIdentity']['OrgIdentitySourceRecord']['OrgIdentitySource']['id'])
             && $lnk['OrgIdentity']['OrgIdentitySourceRecord']['OrgIdentitySource']['sync_on_user_login']) {
            // This is an OrgIdentity that was created from a source, and that source
            // is set to sync, so do the sync.
            
            try {
              $ret = $this->syncOrgIdentity($lnk['OrgIdentity']['OrgIdentitySourceRecord']['OrgIdentitySource']['id'],
                                            $lnk['OrgIdentity']['OrgIdentitySourceRecord']['sorid'],
                                            // The actor is implicitly the CO Person associated with this record
                                            $id['OrgIdentity']['CoOrgIdentityLink'][0]['CoPerson']['id']);
              
              if($ret['status'] != 'unchanged') {
                // Cut a history record
                $this->OrgIdentitySourceRecord->OrgIdentity->HistoryRecord->record($id['OrgIdentity']['CoOrgIdentityLink'][0]['CoPerson']['id'],
                                                                                   null,
                                                                                   $lnk['OrgIdentity']['id'],
                                                                                   $id['OrgIdentity']['CoOrgIdentityLink'][0]['CoPerson']['id'],
                                                                                   ($ret['status'] == 'removed'
                                                                                    ? ActionEnum::OrgIdRemovedSource
                                                                                    : ActionEnum::OrgIdEditedSource),
                                                                                   _txt('rs.ois.sync.login'));
              }
            }
            catch(Exception $e) {
              // XXX We don't want to break the loop on an exception, and it's not really
              // clear what we should do with it. We log it so we don't lose it entirely,
              // but perhaps we should register as a Notification to admins? Or render a
              // popup to the user logging in?
              $this->log(_txt('er.ois.sync.login', array($identifier, $e->getMessage())));
            }
          }
        }
      }
    }
  }
  
  /**
   * Sync an existing organizational identity record based on a result from an Org Identity Source.
   *
   * @since  COmanage Registry v2.0.0
   * @param  Integer $id              OrgIdentitySource to query
   * @param  String  $sourceKey       Record key to retrieve as basis of Org Identity
   * @param  Integer $actorCoPersonId CO Person ID of actor syncing new Org Identity
   * @param  Integer $jobId           If being run as part of a CO Job, the CO Job ID
   * @param  Boolean $force           If true, force a sync even if the source record has not changed
   * @return Array                    'id' is ID of Org Identity, and 'status' is "synced", "unchanged", or "removed"
   * @throws InvalidArgumentException
   * @throws RuntimeException
   */
  
  public function syncOrgIdentity($id, $sourceKey, $actorCoPersonId=null, $jobId=null, $force=false) {
    // Pull record from source
    $brec = null;
    
    try {
      $brec = $this->retrieve($id, $sourceKey);
    }
    catch(InvalidArgumentException $e) {
      // The record is no longer available in the source
    }
    catch(Exception $e) {
      // Rethrow the exception
      throw new RuntimeException($e->getMessage());
    }
    
    if($brec) {
      // If we got a record, check that it is valid.
      // This will throw an exception if invalid.
      $this->validateOISRecord($brec);
    }
    
    // Start a transaction
    $dbc = $this->getDataSource();
    $dbc->begin();
    
    // We need to try/catch everything from here on since an uncaught exception will leave an open
    // db transaction, which will rollback *everything* that comes after it (not just this one sync).
    // The advantage of this is that we don't need to have a bunch of conditional rollbacks... if
    // anything triggers an exception the catch will run the rollback.
    
    try {
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
        'AdHocAttribute',
        'EmailAddress',
        'Identifier',
        'Name',
        'TelephoneNumber',
        'Url'
      );
      
      // XXX We should use findForUpdate here, but that doesn't support contains yet
      $curorgid = $this->OrgIdentitySourceRecord->OrgIdentity->find('first', $args);
      
      if(!isset($curorgid['OrgIdentity']['id'])) {
        $this->rollback();
        
        if($jobId) {
          // Do this after the rollback so we don't lose it
          $this->Co->CoJob->CoJobHistoryRecord->record($jobId,
                                                       $sourceKey,
                                                       _txt('er.ois.nolink'),
                                                       null,
                                                       null,
                                                       JobStatusEnum::Failed);
        }
        
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
      
      $crec = (!empty($brec['hash'])
               ? $brec['hash']
               : (isset($brec['raw'])
                  ? $brec['raw']
                  : null));
      
      if(!$force
         && ((isset($cursrcrec['OrgIdentitySourceRecord']['source_record'])
              && $crec == $cursrcrec['OrgIdentitySourceRecord']['source_record'])
             || // was record previously deleted?
             (!$crec && (!isset($cursrcrec['OrgIdentitySourceRecord']['source_record'])
                         || !$cursrcrec['OrgIdentitySourceRecord']['source_record'])))) {
        // Source record has not changed, so don't bother doing anything
        
        if($jobId) {
          $this->Co->CoJob->CoJobHistoryRecord->record($jobId,
                                                       $sourceKey,
                                                       _txt('rs.org.src.unchanged'),
                                                       null,
                                                       $curorgid['OrgIdentity']['id'],
                                                       JobStatusEnum::Complete);
        }
        
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
        $str = _txt('rs.org.src.rm',
                    array($this->cdata['OrgIdentitySource']['description'],
                          $this->cdata['OrgIdentitySource']['id']));
        
        $this->OrgIdentitySourceRecord->OrgIdentity->HistoryRecord->record(null,
                                                                           null,
                                                                           $this->OrgIdentitySourceRecord->OrgIdentity->id,
                                                                           $actorCoPersonId,
                                                                           ActionEnum::OrgIdRemovedSource,
                                                                           $str);
        
        if($jobId) {
          $this->Co->CoJob->CoJobHistoryRecord->record($jobId,
                                                       $sourceKey,
                                                       $str,
                                                       null,
                                                       $curorgid['OrgIdentity']['id'],
                                                       JobStatusEnum::Complete);
        }
        
        $status = 'removed';
      } else {
        // Since there is a difference in the record, we'll create a History Record
        // indicating that we are syncing. We'll create additional model specific
        // History Records below.
        
        // $str is used again at the end of this else clause
        $str = _txt('rs.org.src.sync',
                    array($this->cdata['OrgIdentitySource']['description'],
                          $this->cdata['OrgIdentitySource']['id']));
        
        $this->OrgIdentitySourceRecord->OrgIdentity->HistoryRecord->record(null,
                                                                           null,
                                                                           $curorgid['OrgIdentity']['id'],
                                                                           $actorCoPersonId,
                                                                           ActionEnum::OrgIdEditedSource,
                                                                           $str);
  
        // Construct an Org Identity and compare it against the existing one.
        // Copy the existing id and co_id to the new record.
        
        $newOrgId = array();
        $newOrgId['OrgIdentity'] = $brec['orgidentity']['OrgIdentity'];
        $newOrgId['OrgIdentity']['id'] = $curorgid['OrgIdentity']['id'];
        $newOrgId['OrgIdentity']['co_id'] = $curorgid['OrgIdentity']['co_id'];
        // Set the status (just in case)
        $newOrgId['OrgIdentity']['status'] = OrgIdentityStatusEnum::Synced;
        
        // Make sure all OrgIdentity keys are set, even if null. This will allow
        // a value to go from set to not set (eg: a valid from date is NULL'd.)
        
        $nullAttrs = array_diff_key($this->OrgIdentitySourceRecord->OrgIdentity->validate,
                                    $newOrgId['OrgIdentity']);
        
        // We're sort of behaving like array_fill_keys() here
        foreach(array_keys($nullAttrs) as $k) {
          $newOrgId['OrgIdentity'][$k] = null;
        }
        
        // Diff array to see if we should save
        $cstr = $this->OrgIdentitySourceRecord->OrgIdentity->changesToString($newOrgId,
                                                                             $curorgid);
        
        if(!empty($cstr)) {
          // Update the OrgIdentity
          
          $this->OrgIdentitySourceRecord->OrgIdentity->clear();
          
          if(!$this->OrgIdentitySourceRecord->OrgIdentity->save($newOrgId)) {
            // In this case we'll trigger the rollback early so we can end the transaction
            // and attempt to record a failure record. (The final rollback should become a no-op.)
            $dbc->rollback();
            
            if($jobId) {
              // Do this after the rollback so we don't lose it
              $this->Co->CoJob->CoJobHistoryRecord->record($jobId,
                                                           $sourceKey,
                                                           _txt('er.db.save-a', array('OrgIdentity')),
                                                           null,
                                                           $curorgid['OrgIdentity']['id'],
                                                           JobStatusEnum::Failed);
            }
        
            throw new RuntimeException(_txt('er.db.save-a', array('OrgIdentity')));
          } else {
            // Cut history
            $this->OrgIdentitySourceRecord->OrgIdentity->HistoryRecord->record(null,
                                                                               null,
                                                                               $curorgid['OrgIdentity']['id'],
                                                                               $actorCoPersonId,
                                                                               ActionEnum::OrgIdEditedSource,
                                                                               $cstr);
          }
        }
  
        // Next handle associated models. This is based on CoPipeline::syncOrgIdentityToCoPerson.
        
        // Supported associated models
        $models = array(
          'Address',
          'AdHocAttribute',
          'EmailAddress',
          'Identifier',
          'Name',
          'TelephoneNumber',
          'Url'
        );
        
        foreach($models as $m) {
          // Pointer to model $m describes (eg $Identifier)
          $model = $this->OrgIdentitySourceRecord->OrgIdentity->$m;
          // Model key used by changelog, eg identifier_id
          $mkey = Inflector::underscore($model->name) . '_id';
          // Model in pluralized format, eg email_addresses
          $mpl = Inflector::tableize($model->name);
          // Model (singular) in localized text string
          $mlang = _txt('ct.' . $mpl . '.1');
          
          // Records obtained from the Org Identity Source
          $newRecords = isset($brec['orgidentity'][$m]) ? $brec['orgidentity'][$m] : array();
          // Records attached to the current Org Identity
          $curRecords = array();
          
          // Map each current record into a "new" Org Identity record and prepare for comparison
          
          foreach($curorgid[$m] as $curOrgRecord) {
            $curRecord = $curOrgRecord;
            
            // Get gid of most metadata keys
            foreach(array('created',
                          'modified',
                          $mkey,
                          'revision',
                          'deleted',
                          'actor_identifier') as $k) {
              unset($curRecord[$k]);
            }
            
            $curRecords[ $curOrgRecord['id'] ] = $curRecord;
          }
          
          // Now that the lists are ready, walk through them and process any changes.
          
          // Unlike in CoPipeline::sync, we don't have a key to map an attribute back to
          // its source record, so we do a bit of calculation. While we could enforce a key
          // as part of the OIS API, that might be tricky for some backends to implement
          // (eg: an LdapSource record with two email addresses can't guarantee it will
          // always retrieve them in the same order), and the only benefit of such a
          // requirement would be that we could do an update rather than a delete and add.
          
          // This loop-search isn't necessarily the most efficient, but in most cases we're
          // dealing with O(1) MVPA records. (eg: An OIS record will typically have 0, 1, or
          // maybe 2 EmailAddresses attached to it.)
          
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
              
              // Record history
              $oldrec = array();
              $oldrec[$m][] = $curRecord;
              $newrec = array();
              $newrec[$m] = array();
              
              $cstr = $model->changesToString($newrec, $oldrec);
              
              $this->OrgIdentitySourceRecord->OrgIdentity->HistoryRecord->record(null,
                                                                                 null,
                                                                                 $curorgid['OrgIdentity']['id'],
                                                                                 $actorCoPersonId,
                                                                                 ActionEnum::OrgIdEditedSource,
                                                                                 $cstr);
            }
          }
          
          // Now look for new records to add.
          
          foreach($newRecords as $newRecord) {
            // Since we've already found all records that are the same in both arrays,
            // we simply add each remaining new record. Insert the Org Identity ID
            // to link the record.
            
            $oldrec = array();
            $oldrec[$m][] = array();
            $newrec = array();
            $newrec[$m][0] = $newRecord;
            $newrec[$m][0]['org_identity_id'] = $curorgid['OrgIdentity']['id'];
            
            $model->clear();
          
            if(!$model->save($newrec[$m][0],
                             // We honor the email verified status provided by the source
                             ($m == 'EmailAddress' ? array('trustVerified' => true) : array()))) {
              // In this case we'll trigger the rollback early so we can end the transaction
              // and attempt to record a failure record. (The final rollback should become a no-op.)
              $dbc->rollback();
              
              if($jobId) {
                // Do this after the rollback so we don't lose it
                $this->Co->CoJob->CoJobHistoryRecord->record($jobId,
                                                             $sourceKey,
                                                             _txt('er.db.save-a', array($m)),
                                                             null,
                                                             $curorgid['OrgIdentity']['id'],
                                                             JobStatusEnum::Failed);
              }
              
              throw new RuntimeException(_txt('er.db.save-a', array($m)));
            }
            
            // Record history
            
            $cstr = $model->changesToString($newrec, $oldrec);
            
            $this->OrgIdentitySourceRecord->OrgIdentity->HistoryRecord->record(null,
                                                                               null,
                                                                               $curorgid['OrgIdentity']['id'],
                                                                               $actorCoPersonId,
                                                                               ActionEnum::OrgIdEditedSource,
                                                                               $cstr);
          }
        }
  
        // Update the Source Record
        
        $oisrec = array();
        $oisrec['OrgIdentitySourceRecord'] = array(
          'org_identity_source_id' => $id,
          'sorid'                  => $sourceKey,
          'source_record'          => $crec,
          'last_update'            => date('Y-m-d H:i:s')
        );
        
        if(!empty($cursrcrec['OrgIdentitySourceRecord']['id'])) {
          $oisrec['OrgIdentitySourceRecord']['id'] = $cursrcrec['OrgIdentitySourceRecord']['id'];
        }
        
        if(!empty($cursrcrec['OrgIdentitySourceRecord']['reference_identifier'])) {
          $oisrec['OrgIdentitySourceRecord']['reference_identifier'] = $cursrcrec['OrgIdentitySourceRecord']['reference_identifier'];
        }
        
        try {
          $this->OrgIdentitySourceRecord->clear();
          $this->OrgIdentitySourceRecord->save($oisrec);
        }
        catch(Exception $e) {
          $dbc->rollback();
          
          if($jobId) {
            // Do this after the rollback so we don't lose it
            $this->Co->CoJob->CoJobHistoryRecord->record($jobId,
                                                         $sourceKey,
                                                         $e->getMessage(),
                                                         null,
                                                         $curorgid['OrgIdentity']['id'],
                                                         JobStatusEnum::Failed);
          }
          
          throw new RuntimeException($e->getMessage());
        }
        
        if($jobId) {
          $this->Co->CoJob->CoJobHistoryRecord->record($jobId,
                                                       $sourceKey,
                                                       $str,
                                                       null,
                                                       $curorgid['OrgIdentity']['id'],
                                                       JobStatusEnum::Complete);
        }
        
        $status = 'synced';
      }
      
      // Invoke pipeline, if configured
      if($status == 'removed' || $status == 'synced') {
        // For now, we rerun the pipeline even if the org identity did not change.
        // This should allow more obvious behavior if (eg) the pipeline definition
        // is updated.
        
        try {
          $this->executePipeline($id,
                                 $curorgid['OrgIdentity']['id'],
                                 ($status == 'removed')
                                 ? SyncActionEnum::Delete
                                 : SyncActionEnum::Update,
                                 $actorCoPersonId,
                                 true,
                                 (!empty($brec['raw']) ? $brec['raw'] : null),
                                 $cursrcrec['OrgIdentitySourceRecord']['id']);
        }
        catch(Exception $e) {
          $dbc->rollback();
          
          if($jobId) {
            // Do this after the rollback so we don't lose it
            $this->Co->CoJob->CoJobHistoryRecord->record($jobId,
                                                         $sourceKey,
                                                         $e->getMessage(),
                                                         null,
                                                         $curorgid['OrgIdentity']['id'],
                                                         JobStatusEnum::Failed);
          }
          
          throw new RuntimeException($e->getMessage());
        }
      }
      
      // Commit
      $dbc->commit();
      
      return array('id' => $curorgid['OrgIdentity']['id'], 'status' => $status);
    }
    catch(InvalidArgumentException $e) {
      // Rollback then rethrow the exception (preserving the exception type)
      
      $dbc->rollback();
      throw new InvalidArgumentException($e->getMessage());
    }
    catch(Exception $e) {
      // Rollback then rethrow the exception
      
      $dbc->rollback();
      throw new RuntimeException($e->getMessage());
    }
  }
  
  /**
   * Sync an Org Identity Source.
   *
   * @since  COmanage Registry v2.0.0
   * @param  Array   $orgIdentitySource Org Identity Source to process
   * @param  Boolean $force             If true, force a sync even if the source record has not changed
   * @param  integer $coJobId           CO Job ID, if already registered
   * @return boolean                    True on success
   * @throws RuntimeException
   */
  
  public function syncOrgIdentitySource($orgIdentitySource, $force=false, $coJobId=null) {
    // We don't check here that the source is in Manual mode in case an admin
    // wants to manually force a sync. (syncAll honors that setting.)
    
    // First see if org identities are pooled, since we don't support that
    $CmpEnrollmentConfiguration = ClassRegistry::init('CmpEnrollmentConfiguration');
    
    if($CmpEnrollmentConfiguration->orgIdentitiesPooled()) {
      throw new RuntimeException(_txt('er.pooling'));
    }
    
    // Figure out the last time we started a job for this source, primarily for changelist.
    // We use start_time rather than complete_time because it's safer (we might perform an
    // extra sync rather than miss something that updated after the job started processing).
    // Note lastStart will only examine Jobs that are in Complete status.
    $lastStart = $this->Co->CoJob->lastStart($orgIdentitySource['OrgIdentitySource']['co_id'],
                                             "CoreJob.Sync",
                                             $orgIdentitySource['OrgIdentitySource']['id']);
    
    // We'll need the set of records associated with this source.
    // This is probably not the most efficient approach...
    // XXX We only need this in FULL mode if the backend supports changedRecords,
    // so perhaps only pull if required? Though we also use the count in the
    // job summary for changelist mode.
    $args = array();
    $args['conditions']['OrgIdentitySourceRecord.org_identity_source_id'] = $orgIdentitySource['OrgIdentitySource']['id'];
    $args['contain'] = false;
    
    $orgRecords = $this->OrgIdentitySourceRecord->find('all', $args);
    
    // Register a new CoJob. This will throw an exception if a job is already in progress.
    // Note we might already have $coJobId, if SyncJob started a single sync (vs sync all).
    // In that case, update that Job rather than create a new one.
    
    $jobId = $coJobId;
    
    if($coJobId) {
      // We don't want to finish the job later, either
      $this->Co->CoJob->update($coJobId,
                               $orgIdentitySource['OrgIdentitySource']['id'],
                               $orgIdentitySource['OrgIdentitySource']['sync_mode'],
                               _txt('fd.ois.record.count',
                                    array($orgIdentitySource['OrgIdentitySource']['description'],
                                          count($orgRecords))));
    } else {
      $jobId = $this->Co->CoJob->register($orgIdentitySource['OrgIdentitySource']['co_id'],
                                          "CoreJob.Sync",
                                          $orgIdentitySource['OrgIdentitySource']['id'],
                                          $orgIdentitySource['OrgIdentitySource']['sync_mode'],
                                          _txt('fd.ois.record.count',
                                               array($orgIdentitySource['OrgIdentitySource']['description'],
                                                     count($orgRecords))));
      
      // Flag the Job as started
      $this->Co->CoJob->start($jobId);
    }
    
    // Count results of various types
    $resCnt = array(
      'error'     => 0,
      'new'       => 0,
      'removed'   => 0,
      'synced'    => 0,
      'unchanged' => 0
    );
    
    if($orgIdentitySource['OrgIdentitySource']['sync_mode'] == SyncModeEnum::Full
       || $orgIdentitySource['OrgIdentitySource']['sync_mode'] == SyncModeEnum::Query
       || $orgIdentitySource['OrgIdentitySource']['sync_mode'] == SyncModeEnum::Update) {
      // For each OrgIdentity linked to this source, run SyncOrgIdentity,
      // which will perform updates and deletes
      
      $this->Co->CoJob->CoJobHistoryRecord->record($jobId,
                                                   null,
                                                   _txt('jb.ois.sync.update.start', array(count($orgRecords))),
                                                   null,
                                                   null,
                                                   JobStatusEnum::Notice);
      
      // First see if the backend supports changedRecords, which relies on the backend
      // to tell us what changed. Note we may get identifiers for records that are not
      // currently synced, so we need to check first if they're known and if not ignore them.
      
      // Get the timestamp of the current job
      $curStart = $this->Co->CoJob->startTime($jobId);
      
      // If $force is true, we can't use changelists since they only return updated records.
      $changelist = false;
      
      if(!$force) {
        try {
          $changelist = $this->obtainChangeList($orgIdentitySource['OrgIdentitySource']['id'],
                                                $lastStart,
                                                $curStart);
        }
        catch(Exception $e) {
          // On error, fail the job rather than risk inconsistent sync state
          
          if(!$coJobId) {
            $this->Co->CoJob->finish($jobId, $e->getMessage(), JobStatusEnum::Failed);
          }
          throw new RuntimeException($e->getMessage());
        }
      }
      
      if($changelist !== false) {
        $known = 0;   // The number of records reported changed that we already know about
        
        foreach($changelist as $srckey) {
          if($this->Co->CoJob->canceled($jobId)) { return false; }
          
          try {
            // syncOrgIdentity does NOT create a new org identity if none exists, it
            // just records an error in the job history. But that's too noisy, so we
            // do a check here in order to silently ignore unknown SORIDs.
            
            $args = array();
            $args['conditions']['OrgIdentitySourceRecord.org_identity_source_id'] = $orgIdentitySource['OrgIdentitySource']['id'];
            $args['conditions']['OrgIdentitySourceRecord.sorid'] = $srckey;
            $args['contain'] = false;
            
            // XXX We should probably do this in a transaction with findForUpdate
            $oisrec = $this->OrgIdentitySourceRecord->find('all', $args);
            
            if(count($oisrec) > 0) {  // $cnt should only be 0 or 1, what if for some reason it's > 1?
              $r = $this->syncOrgIdentity($orgIdentitySource['OrgIdentitySource']['id'],
                                          $srckey,
                                          null,
                                          $jobId,
                                          $force);
              
              $resCnt[ $r['status'] ]++;
              $known++;
            }
          }
          catch(Exception $e) {
            // XXX we should really record this error somewhere (or does syncorgidentity do that for us?)
            $resCnt['error']++;
          }
          
          // Update the unchanged count
          $resCnt['unchanged'] = count($orgRecords) - $known;
        }
        
        $this->Co->CoJob->CoJobHistoryRecord->record($jobId,
                                                     null,
                                                     _txt('jb.ois.sync.update.changed',
                                                          array($known, count($changelist))),
                                                     null,
                                                     null,
                                                     JobStatusEnum::Notice);
      } else {
        // Changelist not supported, perform per-record sync
        
        foreach($orgRecords as $rec) {
          if($this->Co->CoJob->canceled($jobId)) { return false; }
          
          try {
            $r = $this->syncOrgIdentity($rec['OrgIdentitySourceRecord']['org_identity_source_id'],
                                        $rec['OrgIdentitySourceRecord']['sorid'],
                                        null,
                                        $jobId,
                                        $force);
            
            $resCnt[ $r['status'] ]++;
          }
          catch(Exception $e) {
            // XXX we should really record this error somewhere (or does syncorgidentity do that for us?)
            $resCnt['error']++;
          }
        }
      }
      
      $this->Co->CoJob->CoJobHistoryRecord->record($jobId,
                                                   null,
                                                   _txt('jb.ois.sync.update.finish'),
                                                   null,
                                                   null,
                                                   JobStatusEnum::Notice);
    }
    
    // We do the other tasks after update so we don't update a record we just synced
    
    if($orgIdentitySource['OrgIdentitySource']['sync_mode'] == SyncModeEnum::Full) {
      // For each record in the source, if there is no OrgIdentity linked
      // run createOrgIdentity
      
      $sourceKeys = array();
      $knownKeys = array();
      $newKeys = array();
      
      try {
        $sourceKeys = $this->obtainSourceKeys($orgIdentitySource['OrgIdentitySource']['id']);
        
        // Determine the set of already known source keys
        $knownKeys = Hash::extract($orgRecords, '{n}.OrgIdentitySourceRecord.sorid');
        
        // And finally the set of unknown (new) keys
        $newKeys = array_diff($sourceKeys, $knownKeys);
      }
      catch(Exception $e) {
        $eclass = get_class($e);
        $err = $e->getMessage();
        
        if($eclass == 'DomainException') {
          // We're misconfigured, the backend does not support inventory().
          // We'll log an error and keep going.
          
          $err = _txt('er.ois.sync.full.inventory');
        }
        
        // Create a job history record to record the error
        
        $resCnt['error']++;
        
        $this->Co->CoJob->CoJobHistoryRecord->record($jobId,
                                                     null,
                                                     $err,
                                                     null,
                                                     null,
                                                     JobStatusEnum::Failed);
      }
      
      $this->Co->CoJob->CoJobHistoryRecord->record($jobId,
                                                   null,
                                                   _txt('jb.ois.sync.full.start', array(count($sourceKeys), count($knownKeys), count($newKeys))),
                                                   null,
                                                   null,
                                                   JobStatusEnum::Notice);

      foreach($newKeys as $newKey) {
        if($this->Co->CoJob->canceled($jobId)) { return false; }
        
        // This is basically the same logic as used in SyncModeEnum::Query, below
        try {
          $newOrgIdentityId = $this->createOrgIdentity($orgIdentitySource['OrgIdentitySource']['id'],
                                                       $newKey,
                                                       null,
                                                       $orgIdentitySource['OrgIdentitySource']['co_id']);
          
          $resCnt['new']++;
          
          // Create a job history record
          
          $this->Co->CoJob->CoJobHistoryRecord->record($jobId,
                                                       $newKey,
                                                       _txt('rs.org.src.new',
                                                            array($orgIdentitySource['OrgIdentitySource']['description'],
                                                                  $orgIdentitySource['OrgIdentitySource']['id'])),
                                                       null,
                                                       $newOrgIdentityId);
        } 
        catch(OverflowException $e) {
          // There's already an associated identity. We could log a message,
          // but that seems like it'll get noisy. We don't increment a counter
          // either since we should have counted this in 'synced' already.
        }
        catch(Exception $e) {
          // Create a job history record to record the error
          
          $resCnt['error']++;
          
          $this->Co->CoJob->CoJobHistoryRecord->record($jobId,
                                                       $newKey,
                                                       $e->getMessage(),
                                                       null,
                                                       null,
                                                       JobStatusEnum::Failed);
        }        
      }
      
      $this->Co->CoJob->CoJobHistoryRecord->record($jobId,
                                                   null,
                                                   _txt('jb.ois.sync.full.finish'),
                                                   null,
                                                   null,
                                                   JobStatusEnum::Notice);
    }
    
    if($orgIdentitySource['OrgIdentitySource']['sync_mode'] == SyncModeEnum::Query) {
      // For each OrgIdentity (in the current CO), if there are any verified
      // email addresses, query this OIS for any new records to sync. (We've already
      // covered updates, above.)
      
      // There is some overlap with CoPetition::checkEligibility
      
      // We pull via EmailAddress since we don't need OrgIdentities without a verified email.
      // We don't currently look at CO Person Email Addresess, though we could.
      // We do pull EmailAddresses synced from OrgIdentitySources, meaning OIS 1 could
      // trigger a pull from OIS 2 if the configurations are set up appropriately.
      
      $args = array();
      $args['conditions']['EmailAddress.verified'] = true;
      $args['conditions'][] = 'EmailAddress.org_identity_id IS NOT NULL';
      // Since org identities are not pooled, constrain to current CO
      $args['joins'][0]['table'] = 'org_identities';
      $args['joins'][0]['alias'] = 'OrgIdentity';
      $args['joins'][0]['type'] = 'INNER';
      $args['joins'][0]['conditions'][0] = 'OrgIdentity.id=EmailAddress.org_identity_id';
      $args['conditions']['OrgIdentity.co_id'] = $orgIdentitySource['OrgIdentitySource']['co_id'];
      // Don't pull duplicate email addresses
      $args['fields'][] = 'DISTINCT EmailAddress.mail';
      $args['contain'] = false;
      
      // Might want to add caching here at some point, maybe using CakeCache
      $emails = $this->Co->OrgIdentity->EmailAddress->find('all', $args);
      
      // Extracted email addresses (not Cake format)
      $emailList = array();
      
      if(isset($orgIdentitySource['OrgIdentitySource']['sync_query_skip_known'])
         && $orgIdentitySource['OrgIdentitySource']['sync_query_skip_known']) {
        // If sync_query_skip_known don't pull email addresses where the org_identity_id is
        // already associated with a Source Record in this Source. (We should be able to do
        // this as a subselect, but Cake makes it unnecessarily hard to do so, and also
        // doesn't fire callbacks so Changelog queries aren't added.)
        
        $subArgs = array();
        $subArgs['joins'][0]['table'] = 'org_identities';
        $subArgs['joins'][0]['alias'] = 'OrgIdentity';
        $subArgs['joins'][0]['type'] = 'INNER';
        $subArgs['joins'][0]['conditions'][0] = 'OrgIdentity.id=EmailAddress.org_identity_id';
        $subArgs['joins'][1]['table'] = 'org_identity_source_records';
        $subArgs['joins'][1]['alias'] = 'OrgIdentitySourceRecord';
        $subArgs['joins'][1]['type'] = 'INNER';
        $subArgs['joins'][1]['conditions'][0] = 'OrgIdentity.id=OrgIdentitySourceRecord.org_identity_id';
        $subArgs['conditions'][] = 'EmailAddress.org_identity_id IS NOT NULL';
        $subArgs['conditions']['OrgIdentitySourceRecord.org_identity_source_id'] = $orgIdentitySource['OrgIdentitySource']['id'];
        $subArgs['fields'][] = 'DISTINCT EmailAddress.mail';
        $subArgs['contain'] = false;
        
        $knownEmails = $this->Co->OrgIdentity->EmailAddress->find('all', $subArgs);
        
        // It's not clear from the documentation, but Hash::diff returns the items in the
        // first array that are not in the second array. (It does not care about items in the
        // second array that are not in the first array.) However, that doesn't seem to work
        // (the result set is way too large), so instead we'll extract and use native PHP diff.
        
        $e2 = Hash::extract($emails, '{n}.EmailAddress.mail');
        $ke2 = Hash::extract($knownEmails, '{n}.EmailAddress.mail');
        
        $emailList = array_diff($e2, $ke2);
      } else {
        // Reformat $emails to be consistent with array_diff format
        
        $emailList = Hash::extract($emails, '{n}.EmailAddress.mail');
      }
      
      $this->Co->CoJob->CoJobHistoryRecord->record($jobId,
                                                   null,
                                                   _txt('jb.ois.sync.query.start', array(count($emailList), count($emails))),
                                                   null,
                                                   null,
                                                   JobStatusEnum::Notice);
      
      foreach($emailList as $ea) {
        if($this->Co->CoJob->canceled($jobId)) { return false; }
        
        // Since this is search and not retrieve, it's technically possible to get
        // more than one result back from a source, if (eg) there are multiple records
        // with the same email address. It's not exactly clear what to do in that situation,
        // so for now we just add each record.
        
        try {
          $oisResults = $this->search($orgIdentitySource['OrgIdentitySource']['id'],
                                      array('mail' => $ea));
        }
        catch(Exception $e) {
          // We would create a Job History Record, but we don't have a source key
          // XXX I18n
          
          $this->log("ERROR: OrgIdentitySource " . $orgIdentitySource['OrgIdentitySource']['id'] . " : " . $e->getMessage());
          continue;
        }
        
        foreach($oisResults as $sourceKey => $oisRecord) {
          // createOrgIdentity may run a pipeline and/or link to a CO Person. In our current
          // context, we don't know which CO Person it would be desirable to link against,
          // so we leave it up to the attached pipeline to decide. If the pipeline is configured
          // for email match, the right thing will happen. Otherwise, we'll probably cause a new
          // CO Person record to be created.
          
          // This is similar logic as SyncModeEnum::Full, above
          
          // We walk through the response and look for an email address that matches the
          // one we are searching on. Some backends could return different addresses from
          // the one we sent, eg: if a person changed their email address in the backend
          // data source and the source maintained as a "previous" address.
          
          // For now, we require the email address we searched on to be in the results. If it
          // isn't, the OIS configuration determines what we do. In ignore mode, we'll simply
          // log an error and move on to the next result. In "create new identity" mode, we'll
          // continue on to createOrgIdentity, which will handle the situation appropriately.
          
          // We do this search regardless of mode since even if we create a new Org Identity,
          // the logging information might help an admin figure out what happened.
          $emailMatched = false;
          
          foreach($oisRecord['EmailAddress'] as $oea) {
            // Email check is case insensitive so foo@domain and FOO@DOMAIN match.
            if(strtolower($oea['mail']) == strtolower($ea)) {
              $emailMatched = true;
              break;
            }
          }
          
          if(!$emailMatched) {
            // We didn't find the email address we were searching for. Create a job history
            // record to record the situation, and continue to the next record.
            
            $resCnt['error']++;
            
            $this->Co->CoJob->CoJobHistoryRecord->record($jobId,
                                                         $sourceKey,
                                                         _txt('er.ois.mismatch', array($ea)),
                                                         null,
                                                         null,
                                                         JobStatusEnum::Notice);
            
            if(isset($orgIdentitySource['OrgIdentitySource']['sync_query_mismatch_mode'])
               && $orgIdentitySource['OrgIdentitySource']['sync_query_mismatch_mode'] == OrgIdentityMismatchEnum::Ignore) {
              continue;
            }
          }
          
          try {
            // The first thing createOrgIdentity does is check for an existing record,
            // so we'll let that check happen there
            
            $newOrgIdentityId = $this->createOrgIdentity($orgIdentitySource['OrgIdentitySource']['id'],
                                                         $sourceKey,
                                                         null,
                                                         $orgIdentitySource['OrgIdentitySource']['co_id']);
            
            $resCnt['new']++;
            
            // Create a job history record
            
            $this->Co->CoJob->CoJobHistoryRecord->record($jobId,
                                                         $sourceKey,
                                                         _txt('rs.org.src.new',
                                                              array($orgIdentitySource['OrgIdentitySource']['description'],
                                                                    $orgIdentitySource['OrgIdentitySource']['id'])),
                                                         null,
                                                         $newOrgIdentityId);
          } 
          catch(OverflowException $e) {
            // There's already an associated identity. We could log a message,
            // but that seems like it'll get noisy. We don't increment a counter
            // either since we should have counted this in 'synced' already.
          }
          catch(Exception $e) {
            // Create a job history record to record the error
            
            $resCnt['error']++;
            
            $this->Co->CoJob->CoJobHistoryRecord->record($jobId,
                                                         $sourceKey,
                                                         $e->getMessage() . " (" . $ea . ")",
                                                         null,
                                                         null,
                                                         JobStatusEnum::Failed);
          }
        }
      }
      
      $this->Co->CoJob->CoJobHistoryRecord->record($jobId,
                                                   null,
                                                   _txt('jb.ois.sync.query.finish'),
                                                   null,
                                                   null,
                                                   JobStatusEnum::Notice);
    }
    
    if(!$coJobId) {
      $this->Co->CoJob->finish($jobId, json_encode($resCnt));
    }
    
    return true;
  }
  
  /**
   * Validate Org Identity Source Record
   *
   * @since  COmanage Registry v2.0.0
   * @param  Array   $backendRecord Record from OIS Backend
   * @throws InvalidArgumentException
   */
  
  public function validateOISRecord($backendRecord) {
    // For now, we just check that a primary (given) name is specified.
    // We could plausibly support more complex validation later.
    
    if(empty($backendRecord['orgidentity'])) {
      throw new InvalidArgumentException(_txt('er.ois.noorg'));
    }
    
    $primaryFound = false;
    
    foreach($backendRecord['orgidentity']['Name'] as $n) {
      if(isset($n['primary_name']) && $n['primary_name']) {
        $primaryFound = true;
        break;
      }
    }
    
    if(!$primaryFound) {
      throw new InvalidArgumentException(_txt('er.ois.val.name'));
    }
    
    // Manually run validation to report fields that won't save()
    
    if(!$this->Co->OrgIdentity->validateAssociated($backendRecord['orgidentity'])) {
      // We can easily get the field names for invalid entries, but it's a bit harder
      // to get a meaningful error message (we just get "content", and don't have
      // a good mapping to look up).
      
      $err = "";
      
      foreach($this->Co->OrgIdentity->validationErrors as $key1 => $content) {
        // This can be a direct key for an OrgIdentity field, or a nested array
        // for a related model
        
        if(is_array($content[0])) {
          foreach($content[0] as $key2 => $content2) {
            $err .= $key1 . ":" . $key2 . ",";
          }
        } else {
          $err .= $key1 . ",";
        }
      }
      
      $err = _txt('er.fields.api', array(rtrim($err, ",")));
      
      throw new InvalidArgumentException($err);
    }
  }
}