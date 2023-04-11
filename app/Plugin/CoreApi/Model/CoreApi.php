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
App::uses('Utility', 'Validation');

class CoreApi extends AppModel {
  // Define class name for cake
  public $name = "CoreApi";

  // Required by COmanage Plugins
  // XXX could have an "api" plugin type though there's not much value add for it atm
  public $cmPluginType = "other";
  
  // Add behaviors
  public $actsAs = array('Changelog', 'Containable');

  // List of allowed query parameters
  // 1. range, inList, comparison are all validation rules supported by CAKE core
  //    In runtime it will be translated to Validation::validation_rule, Validation::email
  // 2. https://www.php.net/manual/en/language.variables.external.php#language.variables.external.dot-in-names
  //    Currently the dot character is not a valid character for a PHP variable name. So co_person.status
  //    will be transformed to co_person_status.
  // XXX We are making the convention that the field will be placed last and will be a single word
  private $allowed_query_params = array(
    'limit' => array('integer' => array('range' => array(1, 1001))),
    'direction' => array('string' => array('inList' => array(array('asc' , 'desc')))),
    'page'  => array('integer' => array('comparison' => array('>=', 1))),
    'co_person_status' => array('string' => array('custom' => array('/^[A-Za-z]{1,10}$/'))),
    // XXX For consistency, even if we do not have a custom rule the string has to be followed by an empty array
    'identifier' => array('string' => array()),
  );

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
        'required' => false,
        'allowEmpty' => true
      )
    ),
    'api' => array(
      'content' => array(
        'rule' => array('inList', array(CoreApiEnum::CoPersonRead,
                                        CoreApiEnum::CoPersonWrite,
                                        CoreApiEnum::MatchCallback)),
        'required' => true,
        'allowEmpty' => false
      )
    ),
    'index_response_type' => array(
      'content' => array(
        'rule' => array('inList', array(ResponseTypeEnum::Full,
                                        ResponseTypeEnum::IdentifierList)),
        'required' => false,
        'allowEmpty' => true
      )
    ),
    'expunge_on_delete' => array(
      'rule' => 'boolean',
      'required' => false,
      'allowEmpty' => true
    )
  );

  /**
   * Actions to take before a validate operation is executed.
   *
   * @since  COmanage Registry v4.1.0
   */

  public function beforeValidate($options = array())
  {
    if(empty($this->data['CoreApi']["api"])) {
      return false;
    }

    $person_core_api_keys = array(CoreApiEnum::CoPersonRead, CoreApiEnum::CoPersonWrite);
    if(!in_array($this->data['CoreApi']["api"], $person_core_api_keys)) {
      $this->validate["index_response_type"]["content"]["required"] = false;
      $this->validate["index_response_type"]["content"]["allowEmpty"] = true;
    }

    return true;
  }

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
   * Create a new CO Person record
   *
   * @since  COmanage Registry v4.1.0
   * @param integer  $coId     CO ID
   * @param array    $reqData  Array of request data
   * @param integer  $actorApiUserId  Core API User ID making the request
   * @throws InvalidArgumentException
   * @throws RuntimeException
   */
  public function createV1($coId, $reqData, $actorApiUserId) {
    // Start a transaction
    $dbc = $this->getDataSource();
    $dbc->begin();

    if(empty($reqData)) {
      // This probably means JSON failed to parse, or that the Content-Type
      // header is NOT application/json
      throw new InvalidArgumentException(_txt('er.coreapi.json'));
    }

    try {
      // This is somewhat similar to OrgIdentitySource and CoPipeline

      $co_person_id = null;
      foreach(array('CoPerson') as $model) {
        $co_person_id = $this->upsertRecord($coId,
                                            null,
                                            $this->Co->$model,
                                            $reqData[$model],
                                            null,
                                            'co_id',
                                            $coId,
                                            $actorApiUserId);
      }

      // Related models are multi-valued, start with OrgIdentity on its own,
      // since it actually has a parent key of CO in the current data model.

      foreach(array('OrgIdentity') as $model) {
        $accessedRecords[$model] = array();

        if(!empty($reqData[$model])) {
          foreach($reqData[$model] as $m) {
            $recordId = $this->upsertRecord($coId,
                                            $co_person_id,
                                            $this->Co->$model,
                                            $m,
                                            null,
                                            'co_id',
                                            $coId,
                                            $actorApiUserId);

            // Track that we've seen this record
            $accessedRecords[$model][$recordId] = $m;

            // We also need to insert a new CoOrgIdentityLink.
            $this->linkOrgIdentity($co_person_id, $recordId, $actorApiUserId);
          }
        }
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
        $accessedRecords[$model] = array();

        if(!empty($reqData[$model])) {
          foreach($reqData[$model] as $m) {
            $recordId = $this->upsertRecord($coId,
                                            $co_person_id,
                                            $this->Co->CoPerson->$model,
                                            $m,
                                            null,
                                            'co_person_id',
                                            $co_person_id,
                                            $actorApiUserId);
            // Track that we've seen this record, for checking what to delete
            $accessedRecords[$model][$recordId] = $m;
          }
        }
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
        // We use the $accessedRecords version rather than $reqData because it will
        // have newly created parent keys (eg: for a CO Person Role that was
        // added during this operation).

        if(!empty($accessedRecords[$parentModel])) {
          foreach($accessedRecords[$parentModel] as $id => $accessed) {
            if($parentModel == 'OrgIdentity') {
              // Skip read-only Org Identities
              if($this->Co->OrgIdentity->readOnly($id)) {
                continue;
              }
            }

            foreach($relatedModels as $model) {
              // While there can be multiple associated models across multiple roles,
              // $current will have them attached to each role. Thus, we can track
              // $accessedIds on a per-role basis, as opposed to across all roles.

              $accessedIds = array();

              if(!empty($accessed[$model])) {
                foreach($accessed[$model] as $m) {
                  $accessedIds[] = $this->upsertRecord($coId,
                                                       $co_person_id,
                                                       // We just need the associated model, it
                                                       // doesn't matter how we got there so we
                                                       // use OrgIdentity since it is the superset
                                                        $this->Co->OrgIdentity->$model,
                                                        $m,
                                                        null,
                                                        Inflector::underscore($parentModel).'_id',
                                                        $id,
                                                        $actorApiUserId);
                }
              }
            }
          }
        }
      }

      // Assign Identifiers
      // Disable provisioning since we will bulk provision at the end
      // This will return an array describing which, if any, identifiers were assigned,
      // but we don't do anything with the result here
      $this->Co->CoPerson->Identifier->assign('CoPerson', $co_person_id, null, false, $actorApiUserId);

      // Handle plugin models
      $plugins = $this->loadApiPlugins();

      foreach(array_keys($plugins) as $pluginName) {
        if(!empty($plugins[$pluginName]['permittedModels'])) {
          foreach($plugins[$pluginName]['permittedModels'] as $model) {
            // Note we're not checking here that the plugin is instantiated.
            // As a proxy for that, we'll use $current[$model] since that is
            // based on instantiations. (If we don't get back at least an empty
            // $model, then the plugin is not instantiated.)
            if(isset($reqData[$model])) {
              $pModel = ClassRegistry::init($pluginName . "." . $model);
              $authenticator_fk = Inflector::underscore($pluginName) . '_id';

              if(!empty($reqData[$model])) {
                foreach($reqData[$model] as $m) {
                  // The CoreAPI client has to provide the authenticator foreign key in order
                  // for the record to be saved.
                  if(!empty($m[$authenticator_fk])) {
                    $recordId = $this->upsertRecord($coId,
                                                    $co_person_id,
                                                    $pModel,
                                                    $m,
                                                    $reqData[$model],
                                                    'co_person_id',
                                                    $co_person_id,
                                                    $actorApiUserId);
                    $accessedRecords[$model][$recordId] = $m;
                  }
                }
              }
            }
          }
        }
      }

      $dbc->commit();

      // Trigger provisioning
      $this->Co->CoPerson->manualProvision(null, $co_person_id, null, ProvisioningActionEnum::CoPersonUpdated);
      // Return the identifier
      return $accessedRecords["Identifier"];
    }
    catch(Exception $e) {
      $dbc->rollback();
      $ecode = ($e->getCode() !== null) ? $e->getCode() : -1;
      throw new RuntimeException($e->getMessage(), $ecode);
    }
  }

  /**
   * Transition CO Person to status delete. This implies that all CO Person Roles
   * will be transitioned to status deleted as well
   *
   * @since  COmanage Registry v4.1.0
   * @param  integer $coId           CO ID
   * @param  string  $identifier     Identifier to query
   * @param  string  $identifierType Identifier type
   * @return void
   */
  public function deleteV1($coId, $identifier, $identifierType) {
    // Start a transaction
    $dbc = $this->getDataSource();
    $dbc->begin();

    try {
      // Start by trying to retrieve the current record. This will throw an error
      // if not found

      $current = $this->pullCoPerson($coId, $identifier, $identifierType);

      if(empty($current['CoPerson']['id'])) {
        throw new InvalidArgumentException(_txt('er.coreapi.coperson'));
      }

      // If the CO Person has no Role
      if($current["CoPerson"]["status"] !== StatusEnum::Deleted
         && empty($current["CoPersonRole"])) {
        // Clear here and below in case we're run in a loop
        $person_data = array(
          'id'      => $current['CoPerson']['id'],
          'co_id'   => $coId,   // Required field
          'status'  => StatusEnum::Deleted
        );
        $this->Co->CoPerson->clear();
        if(!$this->Co->CoPerson->save($person_data, array('provision' => false))) {
          throw new RuntimeException(_txt('er.db.save-a', array('CoPerson')));
        }
      }

      // If the CO Person has at least one Role
      if($current["CoPerson"]["status"] !== StatusEnum::Deleted
         && !empty($current["CoPersonRole"])) {
        $values = array();
        foreach($current["CoPersonRole"] as $role) {
          // Unless we do not provide the required fields the model validation will fail
          $values[] = array(
            'id'           => $role['id'],
            'co_person_id' => $role['co_person_id'],   // Required field
            'affiliation'  => $role['affiliation'],    // Required field
            'status'       => StatusEnum::Deleted
          );
        }

        $this->Co->CoPerson->CoPersonRole->clear();
        // Disable Model validation. We do not provide co_person_id and affiliation which are required
        if(!$this->Co->CoPerson->CoPersonRole->saveMany($values, array('provision' => false))) {
          throw new RuntimeException(_txt('er.db.save-a', array('CoPerson')));
        }
      }

      // Commit all changes to the databases
      $dbc->commit();

      // Trigger provisioning
      $this->Co->CoPerson->manualProvision(null, $current['CoPerson']['id'], null, ProvisioningActionEnum::CoPersonUpdated);
    }
    catch(Exception $e) {
      $dbc->rollback();
      $ecode = ($e->getCode() !== null) ? $e->getCode() : -1;
      throw new RuntimeException($e->getMessage(), $ecode);
    }
  }

  /**
   * Delete associated models that were not provided in the update request.
   *
   * @since  COmanage Registry v4.0.0
   * @param  integer $coPersonId      CO Person ID
   * @param  Model   $model           Model to examine
   * @param  array   $currentSet      Array of current $model records for CO Person
   * @param  array   $seenIds         Array of $model IDs that were seen in the update request
   * @param  integer $actorApiUserId  Actor API User ID
   */
  
  protected function deleteOmitted($coPersonId, $model, $currentSet, $seenIds, $actorApiUserId) {
    $modelName = $model->name;
    $table = Inflector::tableize($modelName);
    
    if(!empty($currentSet)) {
      foreach($currentSet as $m) {
        if(!empty($m['id'])
           // id will be empty for a new related model of (eg) CoPersonRole;
           // we just want to skip over those
           && !in_array($m['id'], $seenIds)) {
          if($modelName == 'CoGroupMember') {
            // As a special case, skip automatic groups
            
            $auto = $this->Co->CoGroup->field('auto', array('CoGroup.id' => $m['co_group_id']));
            
            if($auto) {
              continue;
            }
          }
          
          if($modelName == 'OrgIdentity') {
            // We have to manually remove the CoOrgIdentityLink. While we don't
            // want provisioning to run, we do want ChangelogBehavior, so we
            // find the record ID before manually deleting.
            
            $args = array();
            $args['conditions']['CoOrgIdentityLink.co_person_id'] = $coPersonId;
            $args['conditions']['CoOrgIdentityLink.org_identity_id'] = $m['id'];
            $args['contain'] = false;
            
            // There should only one link
            $links = $model->CoOrgIdentityLink->find('first', $args);
            
            if(!empty($links)) {
              $model->CoOrgIdentityLink->_provision = false;
              
              $model->CoOrgIdentityLink->delete($links['CoOrgIdentityLink']['id']);
              
              $model->CoOrgIdentityLink->_provision = true;
            }
          }
          
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
                                                     _txt('pl.coreapi.rs.edited-a4', array(_txt("ct.$table.1"), $cstr)),
                                                     null, null, null,
                                                     $actorApiUserId);
        }
      }
    }
  }

  /**
   * This is a wrapper around CoPerson->expunge. Regardless of its status the CoPerson
   * will be expunged
   *
   * @since  COmanage Registry v4.1.0
   * @param  integer $coId               CO ID
   * @param  string  $identifier         Identifier to query
   * @param  string  $identifierType     Identifier type
   * @param  integer $actorApiUserId Identifier of CO Person performing expunge
   * @return boolean True on success
   * @throws InvalidArgumentException
   */
  public function expungeV1($coId, $identifier, $identifierType, $actorApiUserId) {
    try {
      // Start by trying to retrieve the current record. This will throw an error
      // if not found

      $current = $this->pullCoPerson($coId, $identifier, $identifierType);

      if(empty($current['CoPerson']['id'])) {
        throw new InvalidArgumentException(_txt('er.coreapi.coperson'));
      }

      return $this->Co->CoPerson->expunge($current['CoPerson']['id'],
                                          null,
                                          $actorApiUserId);
    }
    catch(Exception $e) {
      $ecode = ($e->getCode() !== null) ? $e->getCode() : -1;
      throw new RuntimeException($e->getMessage(), $ecode);
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
      } elseif(in_array($k, array('actor_identifier',
                                  'created',
                                  'deleted',
                                  'id',
                                  'modified',
                                  'revision',
                                  // MVPA keys
                                  'co_department_id',
                                  'co_person_id',
                                  'org_identity_id',
                                  'organization_id',
                                  'source_ad_hoc_attribute_id',
                                  'source_address_id',
                                  'source_email_address_id',
                                  'source_identifier_id',
                                  'source_name_id',
                                  'source_org_identity_id',
                                  'source_telephone_number_id',
                                  $mfk))) {
        // Skip metadata that might have been included in the record, along
        // with mvpa foreign keys which are frozen, and will be re-inserted by
        // upsertRecord().
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
          } elseif(in_array($k, array('actor_identifier',
                                      'co_provisioning_target_id',
                                      'created',
                                      'deleted',
                                      'id',
                                      'modified',
                                      'revision',
                                      'source_ad_hoc_attribute_id',
                                      'source_address_id',
                                      'source_email_address_id',
                                      'source_identifier_id',
                                      'source_name_id',
                                      'source_org_identity_id',
                                      'source_telephone_number_id',
                                      $mfk))
                   || ($modelName != 'CoGroupMember' && $k == 'co_group_id')) {
            // Move the value to metadata
            $newa['meta'][$k] = $v;
          } elseif(in_array($k, array('co_department_id',
                                      'co_person_id',
                                      'org_identity_id',
                                      'organization_id'))) {
            // These are parent keys for MVPAs, which are either implied by the
            // parent object or null, so we skip them
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
   * @param  integer $coPersonId     CO Person ID
   * @param  integer $orgIdentityId  Org Identity ID
   * @param  integer $actorApiUserId Actor API User ID
   * @return boolean                True on success
   * @todo This really belongs in Model/CoPerson.php or OrgIdentity.php
   */
  
  protected function linkOrgIdentity($coPersonId, $orgIdentityId, $actorApiUserId) {
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
                                               _txt('pl.coreapi.rs.linked'),
                                               null, null, null,
                                               $actorApiUserId);
    
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
   * Parse query parameters
   *
   * @since  COmanage Registry v4.1.0
   * @param array   $queryParams  List of query parameters
   * @return array
   */
  public function parseQueryParams($queryParams) {
    if(empty($queryParams)) {
      return $queryParams;
    }

    $queryParamsNew = array();
    foreach ($queryParams as $attr => $req_value) {
      if(strpos($attr, '_') === false) {
        $queryParamsNew[$attr] = $req_value;
        // We are looking for model_attribute params
        continue;
      }
      $param_pieces = explode('_', $attr);
      $field = array_pop($param_pieces);
      $model_underscored = implode('_', $param_pieces);
      // Construct the enumeraton. e.g. if the field is status and the value is asctive then
      // ucfirst($field) . 'Enum::' . ucfirst($req_value) => StatusEnum::Active
      $cmodel = Inflector::classify($model_underscored);
      $cvalue = constant(ucfirst($field) . 'Enum::' . ucfirst($req_value));
      $cmg_attr = $cmodel . '.' . $field;
      // Transform request params to COmanage internal terminology
      $queryParamsNew[$cmg_attr] = $cvalue;
    }
    return $queryParamsNew;
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
   * Perform a CO People Read API v1 request.
   *
   * @since  COmanage Registry v4.1.0
   * @param  integer $coId           CO ID
   * @param  array   $co_people      Paginators Query Result Set
   * @return array                   Array of CO People data
   * @throws InvalidArgumentException
   */

  public function readV1Index($coId, $co_people) {
    // First try to map the requested information to a CO Person record.
    // This is similar to CoPerson::idsForIdentifier, but that has some old
    // legacy code we want to avoid.

    $cop_index = array();
    foreach ($co_people as $person) {
      // Promote OrgIdentity to top level. This interface doesn't permit relinking
      // identities, and in v5 CoOrgIdentityLink goes away anyway.

      if(!empty($person['CoOrgIdentityLink'])) {
        foreach($person['CoOrgIdentityLink'] as $link) {
          if(!empty($link['OrgIdentity'])) {
            $person['OrgIdentity'][] = $link['OrgIdentity'];
          }
        }
      }

      unset($person['CoOrgIdentityLink']);

      // We need to manually pull Authenticator and Cluster data.
      $person = array_merge($person, $this->Co->Authenticator->marshallProvisioningData($coId, $person['CoPerson']['id']));
      $person = array_merge($person, $this->Co->Cluster->marshallProvisioningData($coId, $person['CoPerson']['id'], false));

      $cop = $this->filterMetadataOutbound($person, "CoPerson");
      $cop_index[] = $cop;
    }

    return $cop_index;
  }

  /**
   * Perform an "upsert" of a potentially multi-valued model.
   * 
   * @since  COmanage Registry v4.0.0
   * @param  integer $coId            CO ID
   * @param  integer $coPersonId      CO Person ID
   * @param  object  $model           Cake model
   * @param  array   $record          Inbound record
   * @param  array   $currentSet      Current set of records for $model and $coPersonId
   * @param  string  $parentKey       This model's parent key
   * @param  integer $parentValue     This model's parent key value
   * @param  integer $actorApiUserId  Actor API User ID
   * @return integer              Record ID for newly upserted record
   * @todo   This should be part of CoPerson or AppModel or something
   */
  
  protected function upsertRecord($coId,
                                  $coPersonId,
                                  $model,
                                  $record,
                                  $currentSet,
                                  $parentKey,
                                  $parentValue,
                                  $actorApiUserId) {
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

    // The currentSet might be an empty array. In that case this will simulate an expunge
    // todo: Test the update with an empty array to verify that it will do the right thing
    if($modelName == 'CoPerson'
       && isset($currentSet)) {
      // This currently only supports update, not creation of a new CO Person
      // Filter the current record, then restore the record id.
      $filteredCurrent[$modelName] = $this->filterMetadataInbound($currentSet[0], $modelName);
      $filteredCurrent[$modelName]['id'] = $coPersonId;
      
      // Require $id to be the ID we looked up, not what was provided in the
      // request metadata
      $filteredIn[$modelName]['id'] = $coPersonId;
      
      // Diff the array to see if we should save
    } elseif(!empty($filteredIn[$modelName]['id'])) {
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
    } else {
      // Add
      
      // There is no current record to compare against
    }
    
    // Diff the arrays to see if we should save, which we always will on add
    $cstr = $model->changesToString($filteredIn, $filteredCurrent);
    
    if(!empty($cstr)) {
      // There was a change. Process it by saving the model and creating a history record.
      
      // We need to inject the CO ID for validating extended types
      foreach($model->validate as $attr => $acfg) {
        if(isset($acfg['content']['rule'][0])
           && $acfg['content']['rule'][0] == 'validateExtendedType') {
          // Inject the current CO so validateExtendedType() works correctly

          $vrule = $acfg['content']['rule'];
          $vrule[1]['coid'] = $coId;

          $model->validator()->getField($attr)->getRule('content')->rule = $vrule;
        }
      }
      
      $saveOptions = array(
        // Provisioning will be manually run after all processing is done
        'provision' => false
      );
      
      if($modelName == 'EmailAddress') {
        // We accept the verified flag as is
        $saveOptions['trustVerified'] = true;
      }
      
      if($modelName == 'UnixClusterAccount'
         || $modelName == 'UnixClusterGroup') {
        // Some clients might not want to track the meta id, in which case they'll
        // submit every record as "new" each time they send an update. This mostly
        // works (albeit noisily, since every record is added and deleted), but
        // because we process adds before deletes it fails for Identifier and
        // the UnixCluster models, which have uniqueness checks. Processing
        // deletes before adds is somewhat complicated because of how we track
        // $seenRecords, so as a workaround we disable availability checking by
        // turning safeties off. This isn't ideal, but it seems to be the least
        // bad option short of a significant rewrite.
        $saveOptions['safeties'] = 'off';
      }
      
      $model->clear();
      $model->save($filteredIn, $saveOptions);
      
      if(!empty($model->validationErrors)) {
        throw new RuntimeException(_txt('er.fields.api', array($modelName . ": " . implode(",", array_keys($model->validationErrors)))));
      }
      
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
                                                 _txt('pl.coreapi.rs.edited-a4', array(_txt("ct.$table.1"), $cstr)),
                                                 null, null, null,
                                                 $actorApiUserId);
    }
    
    return $id;
  }

  /**
   * Perform a CO Person Write API v1 request.
   *
   * @since  COmanage Registry v4.0.0
   * @param  integer $coId             CO ID
   * @param  string  $identifier       Identifier to search on
   * @param  string  $identifierType   Identifier type
   * @param  array   $reqData          Array of request data
   * @param  integer $actorApiUserId   Actor API User ID
   * @throws InvalidArgumentException
   * @throws RuntimeException
   * @todo upsert isn't really the correct term...
   */
  
  public function upsertV1($coId, $identifier, $identifierType, $reqData, $actorApiUserId) {
    // Start a transaction
    $dbc = $this->getDataSource();
    $dbc->begin();
    
    if(empty($reqData)) {
      // This probably means JSON failed to parse, or that the Content-Type
      // header is NOT application/json
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
        $this->upsertRecord($coId,
                            $current['CoPerson']['id'],
                            $this->Co->$model, 
                            $reqData[$model], 
                            array($current[$model]),
                            'co_id',
                            $coId,
                            $actorApiUserId);
      }
      
      // Related models are multi-valued, start with OrgIdentity on its own,
      // since it actually has a parent key of CO in the current data model.
      
      foreach(array('OrgIdentity') as $model) {
        $seenRecords[$model] = array();

        if(!empty($reqData[$model])) {
          foreach($reqData[$model] as $m) {
            if(!empty($m['meta']['id'])) {
              // Skip read-only Org Identities
              if($this->Co->OrgIdentity->readOnly($m['meta']['id'])) {
                $seenRecords[$model][ $m['meta']['id'] ] = $m;
                continue;
              }
            }
            
            $recordId = $this->upsertRecord($coId,
                                            $current['CoPerson']['id'], 
                                            $this->Co->$model,
                                            $m,
                                            (!empty($current[$model]) ? $current[$model] : array()),
                                            'co_id',
                                            $coId,
                                            $actorApiUserId);
            
            // Track that we've seen this record, for checking what to delete
            $seenRecords[$model][$recordId] = $m;
            
            // If we insert a new OrgIdentity (as determined by no provided
            // record key), we also need to insert a new CoOrgIdentityLink.
            if(empty($m['meta']['id'])) {
              $this->linkOrgIdentity($current['CoPerson']['id'], $recordId, $actorApiUserId);
            }
          }
        }
        
        // See if any of the current entries were omitted, if so delete them
        $this->deleteOmitted($current['CoPerson']['id'], 
                             $this->Co->$model,
                             $current[$model],
                             array_keys($seenRecords[$model]),
                             $actorApiUserId);
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
            if($model == 'CoGroupMember') {
              // As a special case, skip automatic groups (this check should
              // really be done on the model...)
              
              $auto = $this->Co->CoGroup->field('auto', array('CoGroup.id' => $m['co_group_id']));
              
              if($auto) {
                continue;
              }
            }
            
            $recordId = $this->upsertRecord($coId,
                                            $current['CoPerson']['id'], 
                                            $this->Co->CoPerson->$model,
                                            $m,
                                            (!empty($current[$model]) ? $current[$model] : array()),
                                            'co_person_id',
                                            $current['CoPerson']['id'],
                                            $actorApiUserId);
            
            // Track that we've seen this record, for checking what to delete
            $seenRecords[$model][$recordId] = $m;
          }
        }
        
        // See if any of the current entries were omitted, if so delete them
        $this->deleteOmitted($current['CoPerson']['id'], 
                             $this->Co->CoPerson->$model,
                             (!empty($current[$model]) ? $current[$model] : array()),
                             array_keys($seenRecords[$model]),
                             $actorApiUserId);
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
            if($parentModel == 'OrgIdentity') {
              // Skip read-only Org Identities
              if($this->Co->OrgIdentity->readOnly($id)) {
                continue;
              }
            }
            
            foreach($relatedModels as $model) {
              // While there can be multiple associated models across multiple roles,
              // $current will have them attached to each role. Thus we can track
              // $seenIds on a per-role basis, as opposed to across all roles.
              
              $seenIds = array();
              
              // Pull current set using Hash
              $currentSet = Hash::extract($current, "$parentModel.{n}[id=$id].$model.{n}");
              
              if(!empty($seen[$model])) {
                foreach($seen[$model] as $m) {
                  $seenIds[] = $this->upsertRecord($coId,
                                                   $current['CoPerson']['id'], 
                                                   // We just need the associated model, it
                                                   // doesn't matter how we got there so we
                                                   // use OrgIdentity since it is the superset
                                                   $this->Co->OrgIdentity->$model,
                                                   $m,
                                                   $currentSet,
                                                   Inflector::underscore($parentModel).'_id',
                                                   $id,
                                                   $actorApiUserId);
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
                                     $seenIds,
                                     $actorApiUserId);
              }
            }
          }     
        }
      }
      
      // Assign Identifiers. We do this on update (not just create) because
      // the CO Person might have been given a CO Group Membership that grants
      // eligibility for an Identifier Assignment. (This is basically equivalent
      // to all Enrollment Flows triggering Identifier Assignments, even if it
      // might be eg an account linking flow.)
      // Disable provisioning since we will bulk provision at the end
      // This will return an array describing which, if any, identifiers were assigned,
      // but we don't do anything with the result here
      $this->Co->CoPerson->Identifier->assign('CoPerson', $current['CoPerson']['id'], null, false, $actorApiUserId);
      
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
                  $recordId = $this->upsertRecord($coId,
                                                  $current['CoPerson']['id'], 
                                                  $pModel,
                                                  $m,
                                                  $current[$model],
                                                  'co_person_id',
                                                  $current['CoPerson']['id'],
                                                  $actorApiUserId);
                  
                  // Track that we've seen this record, for checking what to delete
                  $seenRecords[$model][$recordId] = $m;
                }
              }

              // See if any of the current entries were omitted, if so delete them
              $this->deleteOmitted($current['CoPerson']['id'], 
                                   $pModel,
                                   $current[$model],
                                   array_keys($seenRecords[$model]),
                                   $actorApiUserId);
            }
          }
        }
      }
      
      $dbc->commit();
      
      // Trigger provisioning
      $this->Co->CoPerson->manualProvision(null, $current['CoPerson']['id'], null, ProvisioningActionEnum::CoPersonUpdated);
    }
    catch(Exception $e) {
      $dbc->rollback();
      $ecode = ($e->getCode() !== null) ? $e->getCode() : -1;
      throw new RuntimeException($e->getMessage(), $ecode);
    }
  }

  /**
   * Validate query parameters
   *
   * @since  COmanage Registry v4.1.0
   * @param  array $queryParams
   */
  public function validateQueryParams($queryParams) {
    if(empty($queryParams)) {
      return array();
    }
    // Extract the allowed param names
    $allowed_params = array_keys($this->allowed_query_params);

    // Filter the ones that we do not accept
    // Deep copy queryParams array
    $queryParamsArrayObject = new ArrayObject($queryParams);
    $queryParamsCopy = $queryParamsArrayObject->getArrayCopy();
    foreach ($queryParamsCopy as $key => $value) {
      if(!in_array($key, $allowed_params)) {
        unset($queryParams[$key]);
      }
    }
    $queryParamsArrayObject = null;

    // Validate the ones we accept
    foreach ($this->allowed_query_params as $param => $validation_rule) {
      if(empty($queryParams[$param]))
        continue;
       // Parameter type
      $param_type = key($validation_rule);
      if(!settype($queryParams[$param], $param_type)) {
        unset($queryParams[$param]);
        continue;
      }
      // We have no custom validation rule for this query param
      if(!is_null(key($validation_rule[$param_type]))) {
        // Parameter validation rule
        $param_validation_rule = key($validation_rule[$param_type]);
        // Parameter validation rule options
        $param_validation_options = $validation_rule[$param_type][$param_validation_rule];
        if (!Validation::$param_validation_rule($queryParams[$param], ...$param_validation_options)) {
          unset($queryParams[$param]);
        }
      }
    }
    return $queryParams;
  }
}
