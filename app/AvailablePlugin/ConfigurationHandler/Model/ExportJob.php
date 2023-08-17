<?php
/**
 * COmanage Registry Export Job Model
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
 * @since         COmanage Registry v4.3.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::uses("CoJobBackend", "Model");

class ExportJob extends CoJobBackend {
  /**
   * All supported models
   * All the models below need to be direct CO descendants
   *
   */
  const  MODELS_SUPPORTED = array(
    "ApiUser",
    "AttributeEnumeration",
    "CoDashboard" => array("CoDashboardWidget"),
    "CoEnrollmentFlow" => array(
      "CoPipeline",
      "CoEnrollmentFlowWedge",
      "CoEnrollmentSource",
      "CoEnrollmentAttribute" =>
        array("CoEnrollmentAttributeDefault")
    ),
    "CoExpirationPolicy",
    "CoExtendedAttribute",
    "CoExtendedType",
    "CoGroup" => array(
      "CoGroupNesting",
      "CoGroupOisMapping",
    ), // For the groups i need to ignore the auto ones
    "CoIdentifierAssignment",
    "CoIdentifierValidator",
    "CoLocalization",
    "CoMessageTemplate",
    "CoNavigationLink",
    "CoPipeline",
    "CoProvisioningTarget" => array(
      "CoProvisioningTargetFilter"
    ),
    "DataFilter" => array(
      "OrgIdentitySourceFilter",
      "CoProvisioningTargetFilter",
    ),
    "CoSelfServicePermission",
    "CoSetting",
    "CoTermsAndConditions",
    "CoTheme",
    "Cou" => array(
      "CoTermsAndConditions"
    ),
    "Dictionary" => array(
      "DictionaryEntry",
      "AttributeEnumeration"
    ),
    "OrgIdentitySource" => array(
      "CoGroupOisMapping",
      "OrgIdentitySourceFilter",
    ),
    "Server" => array(
      "SqlServer",
      "Oauth2Server",
      "HttpServer",
      "KafkaServer",
      "MatchServer" => array(
        "MatchServerAttribute",
      ),
    ),
    "VettingStep"
  );

  /**
   * Execute the requested Job.
   *
   * @param int $coId CO ID
   * @param CoJob $CoJob CO Job Object, id available at $CoJob->id
   * @param array $params Array of parameters, as requested via parameterFormat()
   * @throws InvalidArgumentException
   * @since  COmanage Registry v4.0.0
   */

  public function execute($coId, $CoJob, $params) {
    $CoJob->update($CoJob->id,
                   null,
                   null,
                   _txt('pl.configuration_handler.export.start'));

    try {
      // Validate the parameters
      $this->validateParams($coId, $params);
      $models_for_export = array();
      if($params['model_list'] == "All") {
        $models_for_export = self::MODELS_SUPPORTED;
      } else if(strpos($params['model_list'], ",") !== false) {
        $models_for_export = explode(",", $params['model_list']);
      } else {
        $models_for_export[] = $params['model_list'];
      }

      // Create the file
      // configuration_co2_1690210974.json
      $timestamp = time();
      $config_filename = LOCAL . "Config" . DS . "configuration_co{$coId}_{$timestamp}.json";

      // Since we are building an array of json array objects we need to append the opening square bracket
      $idx = 0;
      file_put_contents($config_filename,
                        '[',
                        FILE_APPEND | LOCK_EX);
      foreach ($models_for_export as $exmodel => $dependents) {
        // Since list of models is a mix of an associative array and a simple list
        // we need to check if the first parameter is an int or a string
        $mmodel = is_int($exmodel) ? $dependents : $exmodel;
        // Get all the records for this CoModel
        // Also fetch all the contain data foreach record
        $records = $this->getModelRecords($coId, $mmodel);
        $mdata = array();
        foreach ($records as $record) {
          // Prepare the data
          $mdata[$mmodel][] = $this->filterMetadataInbound($record,
                                                           $mmodel,
                                                           self::MODELS_SUPPORTED[$mmodel] ?? array(),
                                                           $record,
                                                           $mmodel);
        }
        if(!empty($mdata)) {
          // since we are appending we need to add a comma before adding the new blob of data
          file_put_contents($config_filename,
                            $idx > 0 ? "," . $this->jsonEncode($mdata) : $this->jsonEncode($mdata),
                            FILE_APPEND | LOCK_EX);
          $idx++;
        }
      }

      file_put_contents($config_filename,
                        ']',
                        FILE_APPEND | LOCK_EX);

      if($CoJob->id) {
        $CoJob->finish($CoJob->id, _txt('pl.configuration_handler.done'));
      }
    }
    catch(Exception $e) {
      $CoJob->finish($CoJob->id, $e->getMessage(), JobStatusEnum::Failed);
    }
  }


  /**
   * Filter metadata from an inbound request. Associated models are not examined.
   *
   * @since  COmanage Registry v4.3.0
   * @param  array  $dataset       Record to examine
   * @param  string $modelName     Name of model being examined
   * @param  string $hasManyOneList   List of models that are currently supported from the configuration self::MODELS_SUPPORTED
   * @return array             Record, with metadata filtered
   */

  public function filterMetadataInbound($dataset, $modelName, $hasManyOneList, $record, $path = '') {
    // Get the data we need from the record and move them into $ret
    // In the process create the associations placeholders
    $ret = array();

    // Get a pointer to our model
    $Model = ClassRegistry::init($modelName);
    $path_partial = '';

    if(!empty($hasManyOneList)) {
      foreach($hasManyOneList as $key_model => $value_model) {
        $mmodel = is_int($key_model) ? $value_model : $key_model;
        $model_hasMany_keys = array_keys($Model->hasMany);
        $model_hasOne_keys = array_keys($Model->hasOne);
        // For has many relationships the path should also contain the index parser
        // We need to check if the model association is hasOne or hasMany
        if(in_array($mmodel, $model_hasMany_keys)) {
          // XXX The way we implement it here we get all the nested records. We do not
          //     get the ones corresponding to n=2 and then fetch all the level three.
          $path_partial = "{$mmodel}.{n}";
        } else if(in_array($mmodel, $model_hasOne_keys)) {
          $path_partial = $mmodel;
        }

        // This linked model has no record. We continue to the next one.
        // CAKEPHP will return a record with all the values set to null when using the contain
        // feature. In order to find out if the record has any value you use the array_filter

        $next_values = $record[$mmodel] ?? Hash::extract($record, $path_partial);
        $check_null_values = array_filter($next_values);

        if(empty($check_null_values)) {
          continue;
        }

        $ret[$mmodel][] = $this->filterMetadataInbound($dataset,
                                                       $mmodel,
                                                       $hasManyOneList[$mmodel] ?? array(),
                                                       $next_values ?? $dataset,
                                                       empty($path) ? $path_partial : "{$path}.{$path_partial}");
      }
    }

    // XXX The relationship structure is always one to many either we have a belongs to or a has many
    //     "@Cou::CoTermsAndConditions_hasMany.0@": "Cou13CoTermsAndConditions10",
    //     The COU with id 13 has many CoTerms and conditions
    //
    //     "@Cou::Cou_belongsTo@": "Cou0Cou13",
    //     The parent COU has many COUs. Currently the parent is null, this is why the id value is 0


    // hasMany
    foreach($Model->hasMany as $rmodel => $roptions) {
      // If the relationship is not defined in the supported models then we skip
      if( !Hash::check(self::MODELS_SUPPORTED, $Model->name . $roptions['className']) ) {
        continue;
      }

      if(!empty($dataset[$rmodel]) && is_array($dataset[$rmodel])) {
        foreach($dataset[$rmodel] as $idx => $has_many_record) {
          // Handle the case were we created a virtual class name, e.g. the following example is from the EnrollmentFlows
          //     "CoEnrollmentFlowNotificationCoGroup" => array(
          //      'className' => 'CoGroup',
          //      'foreignKey' => 'notification_co_group_id'
          //    ),
          $place_holder_string = '@' . $Model->name . "::" . $roptions['className'] . "_hasMany.{$idx}@";
          $place_holder_hashed_value = strtolower($roptions['className'] . "id" . ($has_many_record['id'] ?? 0));
          $ret[$place_holder_string] = $place_holder_hashed_value;
        }
      }
    }

    // hasOne
    foreach($Model->hasOne as $rmodel => $roptions) {
      // If the relationship is not defined in the supported models then we skip
      if( !Hash::check(self::MODELS_SUPPORTED, $Model->name . $roptions['className']) ) {
        continue;
      }

      $place_holder_string = '@' . $Model->name . "::" .  $roptions['className'] . '_hasOne@';
      $place_holder_hashed_value = strtolower($roptions['className'] . "id" . ($dataset[$rmodel]['id'] ?? 0));
      $ret[$place_holder_string] = $place_holder_hashed_value;
    }

    // belongsTo
    foreach($Model->belongsTo as $rmodel => $roptions) {
      // If we do not have any associated record continue to the next model
      if(empty($dataset[$rmodel]['id'])) {
        continue;
      }
      // For the case of COUs we will have a relationship of the type:
      // "@Cou::Cou_belongsTo@": "Cou0Cou13"
      // This is not the Changelog wich slipped into the configuration. It is the tree relationship
      // The ParentCou is a link to the COU itself and the foreign key is the parent_id column
      $place_holder_string = '@' . $roptions['className'] . "::" . $Model->name . '_belongsTo@';
      $place_holder_hashed_value = strtolower($roptions['className'] . "id" . ($dataset[$rmodel]['id'] ?? 0) ) ;
      $ret[$place_holder_string] = $place_holder_hashed_value;
    }

    // manyToMany
    // XXX Currently we have no model using this relationship type

    // Get the list of belongs_to associations and construct an exclude array
    $assc_keys = [];
    foreach ($Model->belongsTo as $rmodel => $roptions) {
      if(isset($roptions['foreignKey'])) {
        $assc_keys[] = $roptions['foreignKey'];
      } else {
        $assc_keys[] = Inflector::underscore(Inflector::classify($rmodel)) . "_id";
      }
    }

    $mfk = Inflector::underscore($modelName) . "_id";

    $meta_fields = [
      ...$assc_keys,
      'actor_identifier',
      'created',
      'deleted',
      // 'id', i will use the combination of model id and record value as my key value
      // We do not need lft and rght keys. We will recover the relationships, if needed
      // on import:
      // https://book.cakephp.org/2/en/core-libraries/behaviors/tree.html#data-integrity
      'lft',
      'rght',
      'modified',
      'revision',
      $mfk
    ];



    $dataset_to_parse = array();

    // CAKEPHP contain will fetch the first level of containing Models at the same level as the Model itself.
    // This means that for the case of Servers the MatchServers will be at the same level as Servers.
    // Then as we go deeper we need to look into each server type record table.
    // As a result, even though the path: Server.MatchServer.MatchServerAttribute is accurate the data will
    // not be constructed like that. We need to remove the first level of the path
    // We do remove the first level of the path only if a nested path is present. Which means only if
    // the dot delimiter is present
    if(strpos($path, ".") !== false) {
      $path_levels = explode(".", $path);
      array_shift($path_levels);
      $path = implode(".", $path_levels);
    }

    if(Hash::check($dataset, $path)) {
      // Nested
      $dataset_to_parse = Hash::extract($dataset, $path);
    }

    if(empty($dataset_to_parse)) {
      // we do nothing
      return;
    } else if(isset($dataset_to_parse[0])) {
      // hasMany
      foreach ($dataset_to_parse as $idx => $data) {
        if(empty(array_filter($data))) {
          // Record with null values
          return;
        }
        $tmp = array();
        $this->constructRecord($data, $Model, $meta_fields, $modelName, $tmp);
        $ret[] = $tmp;
      }
    } else {
      if(empty(array_filter($dataset_to_parse))) {
        // Record with null values
        return;
      }
      // hasOne
      $this->constructRecord($dataset_to_parse,  $Model, $meta_fields, $modelName, $ret);
    }

    return $ret;
  }


  /**
   *
   *
   * @since  COmanage Registry v4.3.0
   * @param $data
   * @param $Model
   * @param $meta_fields
   * @param $modelName
   * @param $ret
   *
   * @return void
   */
  public function constructRecord($data,  $Model, $meta_fields, $modelName, &$ret) {
    $mdl_schema = $Model->schema();
    foreach ($mdl_schema as $clmn => $properties) {
      // We might have hasMany or hasOne relationships the first is an array of records( find->all)
      // while the latter is just one record ( find->first )
      if (!in_array($clmn, $meta_fields, true)) {
        if ($properties['type'] === "text") {
          // Textarea fields might contain html or javascript code. Encode to base64
          // in order to avoid any problems. Also add a prefix that will dictate that this
          // is an encoded value
          $ret[$clmn] = !empty($data[$clmn]) ?
            "base64::" . base64_encode($data[$clmn])
            : $data[$clmn];
        } else {
          if ($clmn == "id") {
            $ret['key'] = strtolower($modelName . $clmn . $data[$clmn]);
          } else {
            // Just copy the value
            $ret[$clmn] = $data[$clmn];
          }
        }
      }
    }
  }

  /**
   * Retrieve Model records from the database
   *
   * @param int     $coid    CO Id
   * @param string  $pmodel  Model name in Class format
   *
   * @return array  model database records
   */
  public function getModelRecords($coid, $pmodel) {
    $pModel = ClassRegistry::init($pmodel);

    // For groups we need to leave out the auto ones
    $args = array();
    $args['conditions'][$pmodel . '.co_id'] = $coid;
    if($pModel->name == 'CoGroup') {
      $args['conditions'][] = "{$pmodel}.auto IS NOT TRUE";
      $args['conditions']["{$pmodel}.group_type"] = GroupEnum::Standard;
    }

    // We want to contain all the belongsTo associations, as well as the hasMany or hasOne we allow. This will make things easier
    // for the COU model. The COU model hasMany Roles which we do not need. Excluding the CoPersonRoles from the contain list will
    // speed up the process
    $args['contain'] = array_merge(self::MODELS_SUPPORTED[$pmodel] ?? array(), array_keys($pModel->belongsTo));

    if (($key = array_search("Co", $args['contain'])) !== false) {
      unset($args['contain'][$key]);
    }

    $records = $pModel->find('all', $args);

    return $records;
  }

  /**
   * Wrapper for JSON encoding that throws when an error occurs.
   *
   * @param mixed $value   The value being encoded
   * @param int   $options JSON encode option bitmask
   * @param int   $depth   Set the maximum depth. Must be greater than zero.
   *
   * @throws InvalidArgumentException if the JSON cannot be encoded.
   *
   * @link https://www.php.net/manual/en/function.json-encode.php
   */
  public function jsonEncode($value, $options = 0, $depth = 512) {
    $json = json_encode($value, $options, $depth);
    if (JSON_ERROR_NONE !== json_last_error()) {
      throw new InvalidArgumentException('json_encode error: ' . json_last_error_msg());
    }

    return $json;
  }


  /**
   * Obtain the list of parameters supported by this Job.
   *
   * @since  COmanage Registry v4.0.0
   * @return Array Array of supported parameters.
   */

  public function parameterFormat() {
    $params = array(
      'model_list' => array(
        'help'     => _txt('pl.provisionerjob.arg.models_list'),
        'type'     => 'select',
        'short'    => 'l',
        'choices'  => array('All', ...self::MODELS_SUPPORTED),
        'required' => true
      ),
    );

    return $params;
  }

  /**
   * Validate Export Configuration parameters.
   *
   * @since  COmanage Registry v4.3.0
   * @param  integer $coId   CO ID
   * @param  array   $params Array of parameters
   * @return boolean         true if parameters are valid
   * @throws InvalidArgumentException
   */

  protected function validateParams($coId, $params) {
    // Since no model_list is provided we will run the GC for all the supportedones.
    if(empty($params['model_list']) || $params['model_list'] == "All") {
      return true;
    }

    // This is a list of Models
    if(strpos($params['model_list'], ',') !== false) {
      // This is a CSV list
      $list = explode(',', $params['model_list']);
      $diff = array_diff($list, self::MODELS_SUPPORTED);
      if(count($diff) > 0) {
        $diff_to_string = implode(",", $diff);
        $this->log(__METHOD__ . "::message " . _txt('er.configuration_handler.model_list.invalid-a', array($diff_to_string)), LOG_ERROR);
        throw new InvalidArgumentException( _txt('er.configuration_handler.model_list.invalid-a', array($diff_to_string)));
      }
      return true;
    }

    // This is only one Model which we need to get the configuration for
    if(!in_array($params['model_list'], self::MODELS_SUPPORTED)) {
      $this->log(__METHOD__ . "::message " . _txt('er.configuration_handler.model_list.invalid-a', array($params['model_list'])), LOG_ERROR);
      throw new InvalidArgumentException( _txt('er.configuration_handler.model_list.invalid-a', array($params['model_list'])));
    }

    return true;
  }

}