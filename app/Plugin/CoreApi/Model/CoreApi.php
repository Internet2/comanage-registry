<?php
/**
 * COmanage Registry Core API Model
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
 * @package       registry-plugin
 * @since         COmanage Registry v4.0.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::uses("AppModel", "Model");

class CoreApi extends AppModel {
  // Define class name for cake
  public $name = "CoreApi";

  // Required by COmanage Plugins
// XXX could have an "api" plugin type though there's not much value add for it atm
  public $cmPluginType = "other";
  
  // Add behaviors
  public $actsAs = array('Changelog', 'Containable');
  
  // Document foreign keys
  public $cmPluginHasMany = array(
    "ApiUser" => array("CoreApi"),
    "Co" => array("CoreApi")
  );
  
  // Association rules from this model to other models
  public $belongsTo = array(
    "ApiUser",
    "Co"
  );
  
  public $hasMany = array(
  );
  
  // Default display field for cake generated views
  public $displayField = "api_user_id";
  
  // Validation rules for table elements
  public $validate = array(
    'co_id' => array(
      'rule' => 'numeric',
      'required' => true,
      'allowEmpty' => false
    ),
    'status' => array(
      'content' => array(
        'rule' => array('inList', array(SuspendableStatusEnum::Active,
                                        SuspendableStatusEnum::Suspended)),
        'required' => true,
        'allowEmpty' => false
      )
    ),
    'api_user_id' => array(
      'rule' => 'numeric',
      'required' => true,
      'allowEmpty' => false
    ),
    'identifier_type' => array(
      'content' => array(
        'rule' => array('validateExtendedType',
                        array('attribute' => 'Identifier.type',
                              'default' => array(IdentifierEnum::AffiliateSOR,
                                                 IdentifierEnum::Badge,
                                                 IdentifierEnum::Enterprise,
                                                 IdentifierEnum::ePPN,
                                                 IdentifierEnum::ePTID,
                                                 IdentifierEnum::ePUID,
                                                 IdentifierEnum::GuestSOR,
                                                 IdentifierEnum::HRSOR,
                                                 IdentifierEnum::Mail,
                                                 IdentifierEnum::National,
                                                 IdentifierEnum::Network,
                                                 IdentifierEnum::OIDCsub,
                                                 IdentifierEnum::OpenID,
                                                 IdentifierEnum::ORCID,
                                                 IdentifierEnum::ProvisioningTarget,
                                                 IdentifierEnum::Reference,
                                                 IdentifierEnum::SamlPairwise,
                                                 IdentifierEnum::SamlSubject,
                                                 IdentifierEnum::SORID,
                                                 IdentifierEnum::StudentSOR,
                                                 IdentifierEnum::UID))),
        'required' => true,
        'allowEmpty' => false
      )
    ),
    'api' => array(
      'content' => array(
        'rule' => array('inList', array(CoreApiEnum::CoPersonRead,
                                        CoreApiEnum::CoPersonWrite)),
        'required' => true,
        'allowEmpty' => false
      )
    ),
  );
  
  /**
   * Expose menu items.
   * 
   * @since COmanage Registry v4.0.0
   * @return Array with menu location type as key and array of labels, controllers, actions as values.
   */
  
  public function cmPluginMenus() {
    return array(
      "coconfig" => array(_txt('ct.core_apis.pl') =>
      array('icon'       => "code",
            'controller' => "core_apis",
            'action'     => "index"))
    );
  }
  
  /**
   * Delete associated models that were not provided in the update request.
   *
   * @since  COmanage Registry v4.0.0
   * @param  integer $coPersonId CO Person ID
   * @param  Model   $model      Model to examine
   * @param  array   $currentSet Array of current $model records for CO Person
   * @param  array   $seenIds    Array of $model IDs that were seen in the update request
   */
  
  protected function deleteOmitted($coPersonId, $model, $currentSet, $seenIds) {
    $modelName = $model->name;
    $table = Inflector::tableize($modelName);
    
    if(!empty($currentSet)) {
      foreach($currentSet as $m) {
        if(!empty($m['id'])
           // id will be empty for a new related model of (eg) CoPersonRole;
           // we just want to skip over those
           && !in_array($m['id'], $seenIds)) {
          // We have to use the flag hack to disable provisioning because Cake 2
          // doesn't support options passed to delete()
          $model->_provision = false;
          
          $model->delete($m['id']);
          
          // Reset just in case
          $model->_provision = true;
          
          $cstr = $model->changesToString(array(), array($modelName => $m));
          
          $this->Co->CoPerson->HistoryRecord->record($coPersonId,
                                                     null,
                                                     null,
                                                     null,
                                                     ActionEnum::CoPersonEditedApi,
                                                     _txt('pl.coreapi.rs.edited-a4', array(_txt("ct.$table.1"), $cstr)));
        }
      }
    }
  }
  
  /**
   * Filter metadata from an inbound request. Associated models are not examined.
   *
   * @since  COmanage Registry v4.0.0
   * @param  array  $record    Record to examine
   * @param  string $modelName Name of model being examined
   * @return array             Record, with metadata filtered
   */
  
  protected function filterMetadataInbound($record, $modelName) {
    $ret = array();
    
    // Map the model (eg: CoPerson) to the changelog key (co_person_id)
    $mfk = Inflector::underscore($modelName) . "_id";
    
    foreach($record as $k => $v) {
      if($k == 'meta') {
        // We'll process this next
        continue;
      } elseif(gettype($v) == 'array') {
        // Skip related data
        continue;
      } elseif(in_array($k, array('actor_identifier', 'created', 'deleted', 'id', 'modified', 'revision', $mfk))) {
        // Skip metadata that might have been included in the record
      } else {
        // Just copy the value
        $ret[$k] = $v;
      }
    }
    
    if(!empty($record['meta']['id'])) {
      // We don't sanity check id values here, we just copy them over
      $ret['id'] = $record['meta']['id'];
    }
    
    return $ret;
  }
  
  /**
   * Filter metadata on an outbound record.
   *
   * @since  COmanage Registry v4.0.0
   * @param  array  $record    Record to filter
   * @param  string $modelName The Primary model name, or when recursing, the parent model name
   * @return array             Filtered record
   */
  
  protected function filterMetadataOutbound($record, $modelName=null) {
    $ret = array();
    
    if(!empty($record)) {
      // $m = (eg) CoPerson, CoPersonRole, etc, but maybe also an integer
      foreach($record as $m => $a) {
        $newa = array();
        
        // Map the model (eg: CoPerson) to the changelog key (co_person_id)
        $mfk = Inflector::underscore($modelName) . "_id";
        
        // $v might be a column ("id", "title") or a related model ("Address")
        foreach($a as $k => $v) {
          if(is_array($v)) {
            // Related model
            if(is_integer($k)) {
              // HasMany
              $f = $this->filterMetadataOutbound(array($k => $v), $m);
              $newa[$k] = $f[$k];
            } else {
              // HasOne
              $f = $this->filterMetadataOutbound(array($k => $v), $k);
              $newa[$k] = $f[$k];
            }
          } elseif(in_array($k, array('actor_identifier', 'created', 'deleted', 'id', 'modified', 'revision', $mfk))) {
            // Move the value to metadata
            $newa['meta'][$k] = $v;
          } else {
            // Just copy the value
            $newa[$k] = $v;
          }
        }
        
        $ret[$m] = $newa;
      }
    }
    
    return $ret;
  }
  
  /**
   * Filter related models from an inbound record.
   *
   * @since  COmanage Registry v4.0.0
   * @param  array $record Record to filter
   * @return array         Filtered record
   */
  
  protected function filterRelatedInbound($record) {
    // We only permit specific models, to avoid exploits where an API User
    // (which should, tbh, be trusted) inserts related data that is not
    // Person specific (ie: configuration).
    
    // We restrict here by model rather than field, since the goal here is to
    // verify the models, not the fields. :word: may not be exactly the right
    // constraint. Note this formatting will remove empty models (ie: where
    // the key is present but the value is an empty array).
    $permitted = array(
      '/^CoGroupMember\.[\d]+\.[\w]+$/',
      '/^CoOrgIdentityLink\.[\d]+\.[\w]+$/',
      '/^CoOrgIdentityLink\.[\d]+\.OrgIdentity\.[\w]+$/',
      '/^CoOrgIdentityLink\.[\d]+\.OrgIdentity\.Address\.[\d]+\.[\w]+$/',
      '/^CoOrgIdentityLink\.[\d]+\.OrgIdentity\.EmailAddress\.[\d]+\.[\w]+$/',
      '/^CoOrgIdentityLink\.[\d]+\.OrgIdentity\.Identifier\.[\d]+\.[\w]+$/',
      '/^CoOrgIdentityLink\.[\d]+\.OrgIdentity\.Name\.[\d]+\.[\w]+$/',
      '/^CoOrgIdentityLink\.[\d]+\.OrgIdentity\.TelephoneNumber\.[\d]+\.[\w]+$/',
      '/^CoOrgIdentityLink\.[\d]+\.OrgIdentity\.Url\.[\d]+\.[\w]+$/',
      '/^CoPerson\.[\w]+$/',
      '/^CoPersonRole\.[\d]+\.[\w]+$/',
      '/^CoPersonRole\.[\d]+\.Address\.[\d]+\.[\w]+$/',
      '/^CoPersonRole\.[\d]+\.TelephoneNumber\.[\d]+\.[\w]+$/',
      '/^EmailAddress\.[\d]+\.[\w]+$/',
      '/^Identifier\.[\d]+\.[\w]+$/',
      '/^Name\.[\d]+\.[\w]+$/',
      '/^Url\.[\d]+\.[\w]+$/'
    );
    
    // We'll also accept authenticator and cluster records...
    
    // We could call twice with a $pluginType, but loadAvailablePlugins just
    // calls ClassRegistry::init for everything since that's how it checks the
    // type, so that's twice as much work. 
    
    $plugins = $this->loadApiPlugins();
    
    foreach(array_keys($plugins) as $pluginName) {
      if(!empty($plugins[$pluginName]['permittedModels'])) {
        foreach($plugins[$pluginName]['permittedModels'] as $pm) {
          $permitted[] = "/^" . $pm . "\.[\d]+\.[\w]+$/";
        }
      }
    }
    
    $flat = Hash::flatten($record);
    
    foreach(array_keys($flat) as $k) {
      // We could probably collapse $permitted down to one really complex
      // regular expression, but this is more readable
      
      $ok = false;
      
      foreach($permitted as $p) {
        if(preg_match($p, $k)) {
          $ok = true;
          break;
        }
      }
      
      if(!$ok) {
        unset($flat[$k]);
      }
    }
    
    return Hash::expand($flat);
  }
  
  /**
   * Link an Org Identity to a CO Person.
   *
   * @since  COmanage Registry v4.0.0
   * @param  integer $coPersonId    CO Person ID
   * @param  integer $orgIdentityId Org Identity ID
   * @return boolean                True on success
   * @todo This really belongs in Model/CoPerson.php or OrgIdentity.php
   */
  
  protected function linkOrgIdentity($coPersonId, $orgIdentityId) {
    // Create a new link
    
    $link = array(
      'co_person_id'    => $coPersonId,
      'org_identity_id' => $orgIdentityId
    );
    
    $this->Co->CoPerson->CoOrgIdentityLink->save($link, array("provision" => false));
    
    // Create a new history record
    
    $this->Co->CoPerson->HistoryRecord->record($coPersonId,
                                               null,
                                               $orgIdentityId,
                                               null,
                                               ActionEnum::CoPersonEditedApi,
                                               _txt('pl.coreapi.rs.linked'));
    
    return true;
  }
  
  /**
   * Load API plugins.
   *
   * @since  COmanage Registry v4.0.0
   * @return array Array of plugin models
   */
  
  protected function loadApiPlugins() {
    $ret = array();
    
    // We could call twice with a $pluginType, but loadAvailablePlugins just
    // calls ClassRegistry::init for everything since that's how it checks the
    // type, so that's twice as much work. 
    $plugins = $this->loadAvailablePlugins();
    
    foreach($plugins as $p) {
      $pname = $p->name;
      $pmodel = $pname . "." . $pname;
      
      if($p->isPlugin('authenticator') || $p->isPlugin('cluster')) {
        // Store a pointer to the primary plugin model
        $ret[$pname]['plugin'] = $p;
        
        // We'll use cmPluginHasMany to figure out which models are acceptable
        if(!empty($p->cmPluginHasMany['CoPerson'])) {
          foreach($p->cmPluginHasMany['CoPerson'] as $pm) {
            $ret[$pname]['permittedModels'][] = $pm;
          }
        }
      }
    }
    
    return $ret;
  }
  
  /**
   * Pull a CO Person record, including associated models.
   *
   * @since  COmanage Registry v4.0.0
   * @param  integer $coId           CO ID
   * @param  string  $identifier     Identifier to query
   * @param  string  $identifierType Identifier type
   * @return array                   Array of CO Person data
   * @throws InvalidArgumentException
   * @todo This probably belongs in CoPerson.php
   */
  
  protected function pullCoPerson($coId, $identifier, $identifierType) {
    $args = array();
    $args['conditions']['Identifier.identifier'] = $identifier;
    $args['conditions']['Identifier.type'] = $identifierType;
    $args['conditions']['Identifier.status'] = SuspendableStatusEnum::Active;
    $args['conditions']['CoPerson.co_id'] = $coId;
// We allow people of any status to be pulled, though maybe we could offer a filter
//    $args['conditions']['CoPerson.status'] = array(StatusEnum::Active, StatusEnum::GracePeriod);
    $args['joins'][0]['table'] = 'identifiers';
    $args['joins'][0]['alias'] = 'Identifier';
    $args['joins'][0]['type'] = 'INNER';
    $args['joins'][0]['conditions'][0] = 'Identifier.co_person_id=CoPerson.id';
    // While we're here pull the data we need
    $args['contain'] = array(
      'CoPersonRole' => array(
        'Address',
        'AdHocAttribute',
        'TelephoneNumber'
      ),
      'CoGroupMember',
      'CoOrgIdentityLink' => array(
        'OrgIdentity' => array(
          'Address',
          'AdHocAttribute',
          'EmailAddress',
          'Identifier',
          'Name',
          'TelephoneNumber',
          'Url'
        ),
      ),
      'EmailAddress',
      'Identifier',
      'Name',
      'Url'
    );
    
    // find('first') won't result in two records, though if identifier is not
    // unique it's non-deterministic as to which record we'll retrieve.
    
    $cop = $this->Co->CoPerson->find('first', $args);
    
    if(empty($cop)) {
      throw new InvalidArgumentException(_txt('er.notfound', array(_txt('ct.identifiers.1'), filter_var($identifier,FILTER_SANITIZE_SPECIAL_CHARS))));
    }
    
    // Promote OrgIdentity to top level. This interface doesn't permit relinking
    // identities, and in v5 CoOrgIdentityLink goes away anyway.
    
    if(!empty($cop['CoOrgIdentityLink'])) {
      foreach($cop['CoOrgIdentityLink'] as $link) {
        if(!empty($link['OrgIdentity'])) {
          $cop['OrgIdentity'][] = $link['OrgIdentity'];
        }
      }
    }
    
    unset($cop['CoOrgIdentityLink']);
    
    // We need to manually pull Authenticator and Cluster data.
    $cop = array_merge($cop, $this->Co->Authenticator->marshallProvisioningData($coId, $cop['CoPerson']['id']));
    $cop = array_merge($cop, $this->Co->Cluster->marshallProvisioningData($coId, $cop['CoPerson']['id'], false));
    
    return $cop;
  }
  
  /**
   * Perform a CO Person Read API v1 request.
   *
   * @since  COmanage Registry v4.0.0
   * @param  integer $coId           CO ID
   * @param  string  $identifier     Identifier to search on
   * @param  string  $identifierType Identifier type
   * @return array                   Array of CO Person data
   * @throws InvalidArgumentException
   */
  
  public function readV1($coId, $identifier, $identifierType) {
    // First try to map the requested information to a CO Person record.
    // This is similar to CoPerson::idsForIdentifier, but that has some old
    // legacy code we want to avoid.
    
    $cop = $this->filterMetadataOutbound($this->pullCoPerson($coId, $identifier, $identifierType), "CoPerson");
    
    return $cop;
  }
  
  /**
   * Perform an "upsert" of a potentially multi-valued model.
   * 
   * @since  COmanage Registry v4.0.0
   * @param  integer $coPersonId  CO Person ID
   * @param  object  $model       Cake model
   * @param  array   $record      Inbound record
   * @param  array   $currentSet  Current set of records for $model and $coPersonId
   * @param  string  $parentKey   This model's parent key
   * @param  integer $parentValue This model's parent key value
   * @return integer              Record ID for newly upserted record
   * @todo   This should be part of CoPerson or AppModel or something
   */
  
  protected function upsertRecord($coPersonId, $model, $record, $currentSet, $parentKey, $parentValue) {
    $modelName = $model->name;
    $table = Inflector::tableize($modelName);
    $filteredIn = array();
    $filteredCurrent = array();
    $id = null;
    $cstr = null;
    
    // Clean up the metadata in the record
    $filteredIn[$modelName] = $this->filterMetadataInbound($record, $modelName);
    
    // Inject the parent key. We do this for both add (which requires it) and
    // update (to prevent rekeying a record via update).
    $filteredIn[$modelName][$parentKey] = $parentValue;
    
    if(!empty($filteredIn[$modelName]['id'])) {
      // Update
      $id = $filteredIn[$modelName]['id'];
      
      // Verify that id is in $currentSet. If it's not, throw an error since
      // the client can't specify a new ID.
      
      $current = Hash::extract($currentSet, "{n}[id=$id]");
      
      if(empty($current)) {
        throw new InvalidArgumentException(_txt('er.coreapi.id.invalid', array($id)));
      }
      
      // Filter the current record, then restore the record id.
      $filteredCurrent[$modelName] = $this->filterMetadataInbound($current[0], $modelName);
      $filteredCurrent[$modelName]['id'] = $id;
      
      // Diff the array to see if we should save
      $cstr = $model->changesToString($filteredIn, $filteredCurrent);
    } else {
      // Add
      
      if($modelName == 'CoPerson') {
        // We don't currently support CoPerson add, which would need a different
        // (POST) URL anyway
        throw new RuntimeException('CoPerson Add NOT IMPLEMENTED');
      }
    }
    
    // Diff the arrays to see if we should save, which we always will on add
    $cstr = $model->changesToString($filteredIn, $filteredCurrent);
    
    if(!empty($cstr)) {
      // There was a change. Process it by saving the model and creating a history record.
      
      $model->clear();
      // For EmailAddress, we accept the verified flag as is (other models will ignore it)
      $model->save($filteredIn, array("provision" => false, "trustVerified" => true));
      
      if(!$id) {
        // Added, so grab the ID to return it
        $id = $model->id;
      }
      
      // Try to link a foreign key if we can
      $orgId = null;
      $roleId = null;
      
      if($modelName == 'CoPersonRole') {
        $roleId = $id;
      } elseif($modelName == 'OrgIdentity') {
        $orgId = $id;
      } elseif($parentKey == 'co_person_role_id') {
        $roleId = $parentValue;
      } elseif($parentKey == 'org_identity_id') {
        $orgId = $parentValue;
      }

      $this->Co->CoPerson->HistoryRecord->record($coPersonId,
                                                 $roleId,
                                                 $orgId,
                                                 null,
                                                 ActionEnum::CoPersonEditedApi,
                                                 _txt('pl.coreapi.rs.edited-a4', array(_txt("ct.$table.1"), $cstr)));
    }
    
    return $id;
    
    // Diff the array to see if we should save
    $cstr = $this->Co->CoPerson->changesToString($filteredIn, $filteredCurrent);
    
    // We need to check array_diff in both directions.
    
    if(!empty($cstr)) {
      // There was a change. Process it by saving the model and creating a
      // history record.
      
      // $model->clear()
      $this->Co->CoPerson->save($filteredIn, array("provision" => false));
      
      $this->Co->OrgIdentity->HistoryRecord->record($coPersonId,
                                                    null,
                                                    null,
                                                    null,
                                                    ActionEnum::CoPersonEditedApi,
                                                    _txt('pl.coreapi.rs.edited-a4', array(_txt('ct.co_people.1'), $cstr)));
    }
  }
  
  /**
   * Perform a CO Person Write API v1 request.
   *
   * @since  COmanage Registry v4.0.0
   * @param  integer $coId           CO ID
   * @param  string  $identifier     Identifier to search on
   * @param  string  $identifierType Identifier type
   * @param  array   $reqData        Array of request data
   * @throws InvalidArgumentException
   * @throws RuntimeException
   * @todo upsert isn't really the correct term...
   */
  
  public function upsertV1($coId, $identifier, $identifierType, $reqData) {
    // Start a transaction
    $dbc = $this->getDataSource();
    $dbc->begin();
    
    if(empty($reqData)) {
      // This probably means JSON failed to parse
      throw new InvalidArgumentException(_txt('er.coreapi.json'));
    }

    try {
      // Start by trying to retrieve the current record. This will throw an error
      // if not found, which for now is OK because XXX in spite of our name we
      // currently only handle update(), not insert().
      
      // Track the related models because, in particular for CoPersonRole and OrgIdentity,
      // if a new related model is found (eg: a CO Person Role is added) we need
      // to be able to identify the newly assigned record key for purposes of
      // adding subrecords (eg: Address attached to the new CoPersonRole).
      // We'll also use the same storage for determining which records to delete.
      $seenRecords = array();
      
      $current = $this->pullCoPerson($coId, $identifier, $identifierType);
      
      if(empty($current['CoPerson']['id'])) {
        throw new InvalidArgumentException(_txt('er.coreapi.coperson'));
      }
      
      // This is somewhat similar to OrgIdentitySource and CoPipeline
      
      foreach(array('CoPerson') as $model) {
        $this->upsertRecord($current['CoPerson']['id'],
                            $this->Co->$model, 
                            $reqData[$model], 
                            array($current[$model]),
                            'co_id',
                            $coId);
      }
      
      // Related models are multi-valued, start with OrgIdentity on its own,
      // since it actually has a parent key of CO in the current data model.
      
      foreach(array('OrgIdentity') as $model) {
        $seenRecords[$model] = array();

        if(!empty($reqData[$model])) {
          foreach($reqData[$model] as $m) {
            $recordId = $this->upsertRecord($current['CoPerson']['id'], 
                                            $this->Co->$model,
                                            $m,
                                            $current[$model],
                                            'co_id',
                                            $coId);
            
            // Track that we've seen this record, for checking what to delete
            $seenRecords[$model][$recordId] = $m;
            
            // If we insert a new OrgIdentity (as determined by no provided
            // record key), we also need to insert a new CoOrgIdentityLink.
            if(empty($m['meta']['id'])) {
              $this->linkOrgIdentity($current['CoPerson']['id'], $recordId);
            }
          }
        }
        
        // See if any of the current entries were omitted, if so delete them
        $this->deleteOmitted($current['CoPerson']['id'], 
                             $this->Co->$model,
                             $current[$model],
                             array_keys($seenRecords[$model]));
      }

      foreach(array('CoGroupMember', 
                    // CoPersonRole here (and OrgIdentity above) will only process
                    // the top level record. We'll handle related models below.
                    'CoPersonRole',
                    'EmailAddress',
                    'Identifier',
                    'Name',
                    // In the current data model, OrgIdentity actually has CO
                    // as a parent (though this will change)
                    //'OrgIdentity',
                    'Url') as $model) {
        $seenRecords[$model] = array();
        
        if(!empty($reqData[$model])) {
          foreach($reqData[$model] as $m) {
            $recordId = $this->upsertRecord($current['CoPerson']['id'], 
                                            $this->Co->CoPerson->$model,
                                            $m,
                                            $current[$model],
                                            'co_person_id',
                                            $current['CoPerson']['id']);
            
            // Track that we've seen this record, for checking what to delete
            $seenRecords[$model][$recordId] = $m;
          }
        }
        
        // See if any of the current entries were omitted, if so delete them
        $this->deleteOmitted($current['CoPerson']['id'], 
                             $this->Co->CoPerson->$model,
                             $current[$model],
                             array_keys($seenRecords[$model]));
      }
      
      // Next handle related models for CoPersonRole and OrgIdentity.
      
      $related = array(
        'CoPersonRole' => array(
          'Address',
          'AdHocAttribute',
          'TelephoneNumber'
        ),
        'OrgIdentity' => array(
          'Address',
          'AdHocAttribute',
          'EmailAddress',
          'Identifier',
          'Name',
          'TelephoneNumber'
        )
      );
      
      foreach($related as $parentModel => $relatedModels) {
        // Note at this point we've validated CoPersonRole['meta']['id'] and
        // OrgIdentity['meta']['id'], since if the request included an invalid
        // foreign key upsertRecord would have thrown an error.
        
        // We use the $seenRecords version rather than $reqData because it will
        // have newly created parent keys (eg: for a CO Person Role that was
        // added during this operation).
        
        if(!empty($seenRecords[$parentModel])) {
          foreach($seenRecords[$parentModel] as $id => $seen) {
            foreach($relatedModels as $model) {
              // While there can be multiple associated models across multiple roles,
              // $current will have them attached to each role. Thus we can track
              // $seenIds on a per-role basis, as opposed to across all roles.
              
              $seenIds = array();
              
              // Pull current set using Hash
              $currentSet = Hash::extract($current, "$parentModel.{n}[id=$id].$model.{n}");
              
              if(!empty($seen[$model])) {
                foreach($seen[$model] as $m) {
                  $seenIds[] = $this->upsertRecord($current['CoPerson']['id'], 
                                                   // We just need the associated model, it
                                                   // doesn't matter how we got there so we
                                                   // use OrgIdentity since it is the superset
                                                   $this->Co->OrgIdentity->$model,
                                                   $m,
                                                   $currentSet,
                                                   Inflector::underscore($parentModel).'_id',
                                                   $id);
                }
              }
              
              if(!empty($seen[$model])) {
                // See if any of the current entries were omitted, if so delete them
                $this->deleteOmitted($current['CoPerson']['id'], 
                                     $this->Co->OrgIdentity->$model,
                                     // Note we're potentially passing in new related
                                     // models here, if we processed an entirely new
                                     // CO Person Role.
                                     $currentSet,
                                     $seenIds);
              }
            }
          }     
        }
      }
      
      // Handle plugin models (Authenticator, Cluster)
      
      $plugins = $this->loadApiPlugins();
      
      foreach(array_keys($plugins) as $pluginName) {
        if(!empty($plugins[$pluginName]['permittedModels'])) {
          foreach($plugins[$pluginName]['permittedModels'] as $model) {
            // Note we're not checking here that the plugin is instantiated.
            // As a proxy for that, we'll use $current[$model] since that is
            // based on instantiations. (If we don't get back at least an empty
            // $model, then the plugin is not instantiated.)
            if(isset($current[$model])) {
              $pModel = ClassRegistry::init($pluginName . "." . $model);
              
              $seenRecords[$model] = array();

              if(!empty($reqData[$model])) {
                foreach($reqData[$model] as $m) {
                  $recordId = $this->upsertRecord($current['CoPerson']['id'], 
                                                  $pModel,
                                                  $m,
                                                  $current[$model],
                                                  'co_person_id',
                                                  $current['CoPerson']['id']);
                  
                  // Track that we've seen this record, for checking what to delete
                  $seenRecords[$model][$recordId] = $m;
                }
              }

              // See if any of the current entries were omitted, if so delete them
              $this->deleteOmitted($current['CoPerson']['id'], 
                                   $pModel,
                                   $current[$model],
                                   array_keys($seenRecords[$model]));
            }
          }
        }
      }
      
      // Trigger provisioning
      $this->Co->CoPerson->manualProvision(null, $current['CoPerson']['id'], null, ProvisioningActionEnum::CoPersonUpdated);
      
      $dbc->commit();
    }
    catch(Exception $e) {
      $dbc->rollback();
      throw new RuntimeException($e->getMessage());
    }
    
    return;
  }
}
