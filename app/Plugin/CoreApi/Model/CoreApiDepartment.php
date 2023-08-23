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

class CoreApiDepartment extends CoreApi {
  // Define class name for cake
  public $name = "CoreApiDepartment";

  public $mapper = "CoDepartment";

  public $associated_models = array(
    "Address",
    "AdHocAttribute",
    "EmailAddress",
    "Identifier",
    "TelephoneNumber",
    "Url");

  public $index_contains = array(
    "Address",
    "AdHocAttribute",
    "EmailAddress",
    "Identifier",
    "TelephoneNumber",
    "Url"
  );

  public $related_models = array();

  public $view_contains = array(
    "Address",
    "AdHocAttribute",
    "EmailAddress",
    "Identifier",
    "TelephoneNumber",
    "Url"
  );

  /**
   * Create a new CoDepartment record
   *
   * @since  COmanage Registry v4.1.0
   * @param integer  $coId     CO ID
   * @param array    $reqData  Array of request data
   * @param integer  $actorApiUserId  Core API User ID making the request
   * @throws InvalidArgumentException
   * @throws RuntimeException
   */
  public function createV1($coId, $reqData, $actorApiUserId) {
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

      $co_department_id = $this->upsertRecord($coId,
                                             null,
                                             $this->Co->CoDepartment,
                                             $reqData['CoDepartment'],
                                             null,
                                             'co_id',
                                             $coId,
                                             $actorApiUserId);

      // Related models are multi-valued, start with OrgIdentity on its own,
      // since it actually has a parent key of CO in the current data model.

      foreach(array(
                "Address",
                "AdHocAttribute",
                "EmailAddress",
                "Identifier",
                "TelephoneNumber",
                "Url") as $model) {
        $accessedRecords[$model] = array();

        if(!empty($reqData[$model])) {
          foreach($reqData[$model] as $m) {
            $recordId = $this->upsertRecord($coId,
                                            $co_department_id,
                                            $this->Co->CoDepartment->$model,
                                            $m,
                                            null,
                                            'co_department_id',
                                            $co_department_id,
                                            $actorApiUserId);
            // Track that we've seen this record, for checking what to delete
            $accessedRecords[$model][$recordId] = $m;
          }
        }
      }

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
                                                    $co_department_id,
                                                    $pModel,
                                                    $m,
                                                    $reqData[$model],
                                                    'co_department_id',
                                                    $co_department_id,
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
   * Pull a CoDepartment record, including associated models.
   *
   * @since  COmanage Registry v4.0.0
   * @param  integer $coId           CO ID
   * @param  string  $identifier     Identifier to query
   * @param  string  $identifierType Identifier type
   * @return array                   Array of CO Person data
   * @throws InvalidArgumentException
   */

  protected function pullRecord($coId, $identifier, $identifierType) {
    $args = array();
    $args['conditions']['Identifier.identifier'] = $identifier;
    $args['conditions']['Identifier.type'] = $identifierType;
    $args['conditions']['Identifier.status'] = SuspendableStatusEnum::Active;
    $args['conditions']['CoDepartment.co_id'] = $coId;
    $args['joins'][0]['table'] = 'identifiers';
    $args['joins'][0]['alias'] = 'Identifier';
    $args['joins'][0]['type'] = 'INNER';
    $args['joins'][0]['conditions'][0] = 'Identifier.co_department_id=CoDepartment.id';
    // While we're here pull the data we need
    $args['contain'] = $this->view_contains;

    // find('first') won't result in two records, though if identifier is not
    // unique it's non-deterministic as to which record we'll retrieve.

    $org = $this->Co->CoDepartment->find('first', $args);

    if(empty($org)) {
      throw new InvalidArgumentException(_txt('er.notfound', array(_txt('ct.identifiers.1'), filter_var($identifier,FILTER_SANITIZE_SPECIAL_CHARS))));
    }
    return $org;
  }
}