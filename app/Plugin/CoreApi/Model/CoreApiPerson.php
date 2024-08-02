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

App::uses("CoreApi", "CoreApi.Model");

class CoreApiPerson extends CoreApi {
  // Define class name for cake
  public $name = "CoreApiPerson";

  public $mapper = "CoPerson";

  public $associated_models = array('CoGroupMember',
    // CoPersonRole here (and OrgIdentity above) will only process
    // the top level record. We'll handle related models below.
    'CoPersonRole',
    'EmailAddress',
    'Identifier',
    'Name',
    // In the current data model, OrgIdentity actually has CO
    // as a parent (though this will change)
    //'OrgIdentity',
    'Url');

  public $index_contains = array(
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

  public $related_models = array(
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

  public $view_contains = array(
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

  /**
   * Create a new CO Person record
   *
   * @since  COmanage Registry v4.1.0
   * @param integer  $coId     CO ID
   * @param array    $reqData  Array of request data
   * @param integer  $actorApiUserId  Core API User ID making the request
   * @param  string  $identifierType Identifier type
   * @throws InvalidArgumentException
   * @throws RuntimeException
   */
  public function createV1($coId, $reqData, $actorApiUserId, $identifierType) {
    $modelMapperName = $this->mapper;
    // Start a transaction
    $dbc = $this->Co->$modelMapperName->getDataSource();
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

      foreach($this->related_models as $parentModel => $relatedModels) {
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
      // XXX Organizations do not support assign identifiers
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

      // Return the list of identifiers that their type match the CORE API configuration
      $args = array();
      $args['conditions']['Identifier.co_person_id'] = $co_person_id;
      $args['conditions']['Identifier.type'] = $identifierType;
      $args['contain'] = false;

      $identifiers = $this->Co->CoPerson->Identifier->find('all', $args);
      $idents = array();
      foreach ($identifiers as $ident) {
        $idents[ $ident['Identifier']['id'] ] = array (
          'identifier' => $ident['Identifier']['identifier'],
          'type' => $ident['Identifier']['type'],
          'login' => $ident['Identifier']['login'],
          'status' => $ident['Identifier']['status'],
        );
      }
      // Return the identifier
      return $idents;
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
    $dbc = $this->Co->CoPerson->getDataSource();
    $dbc->begin();

    try {
      // Start by trying to retrieve the current record. This will throw an error
      // if not found

      $current = $this->pullRecord($coId, $identifier, $identifierType);

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

      $current = $this->pullRecord($coId, $identifier, $identifierType);

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

  protected function pullRecord($coId, $identifier, $identifierType) {
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
    $args['contain'] = $this->view_contains;

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
}