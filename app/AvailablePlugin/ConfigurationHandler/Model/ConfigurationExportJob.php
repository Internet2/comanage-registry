<?php
/**
 * COmanage Registry ConfigurationExport Model
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

class ConfigurationExportJob extends CoJobBackend {
  // Required by COmanage Plugins
  public $cmPluginType = "job";

  /**
   * All supported models
   * All the models below need to be direct CO descendants
   *
   */
  const  MODELS_SUPPORTED = array(
    "ApiUser",
    "AttributeEnumeration",
    "CoDashboard",
    "CoDashboardWidget",
    "CoEnrollmentAttribute",
    "CoEnrollmentAttributeDefault",
    "CoEnrollmentFlow",
    "CoEnrollmentFlowWedge",
    "CoEnrollmentSource",
    "CoExpirationPolicy",
    "CoExtendedAttribute",
    "CoExtendedType",
    "CoGroup",
    "CoGroupNesting",
    "CoGroupOisMapping",
    "CoIdentifierAssignment",
    "CoIdentifierValidator",
    "CoLocalization",
    "CoMessageTemplate",
    "CoNavigationLink",
    "CoPipeline",
    "CoProvisioningTarget",
    "CoProvisioningTargetFilter",
    "CoSelfServicePermission",
    "CoSetting",
    "CoTermsAndCondititions",
    "CoTheme",
    "Cou",
    "DataFilter",
    "Dictionary",
    "DictionaryEntry",
    "HttpServer",
    "KafkaServer",
    "MatchServer",
    "MatchServerAttribute",
    "NavigationLink",
    "Oauth2Server",
    "OrgIdentitySource",
    "OrgIdentitySourceFilter",
    "Server",
    "SqlServer",
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
      $config_filename = LOCAL . DS . "Config" . DS . "configuration_co{$coId}_{$timestamp}.json";

      foreach ($models_for_export as $exmodel) {
        // Construct the table name
        $records = $this->getModelRecords($coId, $exmodel);
        $mdata = array();
        foreach ($records as $record) {
          // Prepare the data
          $mdata[] = $this->filterMetadataInbound($record, $exmodel);
        }
        file_put_contents($config_filename,
                          $this->jsonEncode($mdata),
                          FILE_APPEND | LOCK_EX);
      }

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
   * @param  array  $record    Record to examine
   * @param  string $modelName Name of model being examined
   * @return array             Record, with metadata filtered
   */

  public function filterMetadataInbound($record, $modelName) {
    // Get the data we need from the record and move them into $ret
    // In the process create the associations place holders
    $ret = array();

    // Get a pointer to our model
    $Model = ClassRegistry::init($modelName);

    // hasMany
    foreach($Model->hasMany as $rmodel => $roptions) {
      foreach($record[$rmodel] as $idx => $has_many_record) {
        $place_holder_string = '@' . $modelName->name . $rmodel . "_hasMany.{$idx}@";
        $place_holder_hashed_value = md5($modelName->name . $record[$modelName->name]['id'] . $rmodel . $has_many_record['id']);
        // Handle the case were we created a virtual class name, e.g. the following example is from the EnrollmentFlows
        //     "CoEnrollmentFlowNotificationCoGroup" => array(
        //      'className' => 'CoGroup',
        //      'foreignKey' => 'notification_co_group_id'
        //    ),
        if(isset($roptions['className'])) {
          $place_holder_string = '@' . $modelName->name . $roptions['className'] . "_hasMany.{$idx}@";
          $place_holder_hashed_value = md5($modelName->name . $record[$modelName->name]['id'] . $roptions['className'] . $has_many_record['id']);
        }
        $ret[$place_holder_string] = $place_holder_hashed_value;
      }
    }

    // hasOne
    foreach($Model->hasOne as $rmodel => $roptions) {
      $place_holder_string = '@' . $modelName->name . $rmodel . '_hasOne@';
      // because we only have one level contain, which means that we will have an associative array of Model=>data
      $place_holder_hashed_value = md5($modelName->name . $record[$modelName->name]['id'] . $rmodel . $record[$rmodel]['id']);
      if(isset($roptions['className'])) {
        $place_holder_string = '@' . $modelName->name . $roptions['className'] . '_hasOne@';
        $place_holder_hashed_value = md5($modelName->name . $record[$modelName->name]['id'] . $roptions['className'] . $record[$roptions['className']]['id']);
      }
      $ret[$place_holder_string] = $place_holder_hashed_value;
    }

    // belongsTo
    foreach($Model->belongsTo as $rmodel => $roptions) {
      $place_holder_string = '@' . $rmodel . $modelName->name . '_belongsTo@';
      $place_holder_hashed_value = md5($rmodel . $record[$rmodel]['id'] . $modelName->name . $record[$modelName->name]['id']);
      if(isset($roptions['className'])) {
        $place_holder_string = '@' . $roptions['className'] . $modelName->name . '_belongsTo@';
        $place_holder_hashed_value = md5($roptions['className'] . $record[$roptions['className']]['id'] . $modelName->name . $record[$modelName->name]['id']);
      }
      $ret[$place_holder_string] = $place_holder_hashed_value;
    }

    // manyToMany
    // XXX Currently we have no model using this relationship type

    // TODO the above does not take into consideration the special case of COUs that use
    // the tree behavior
    //     'lft',
    //    'rght',
    //    'parent_id

    // Get the available Columns from the Schema
    $mdl_columns = array_keys($Model->schema());
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
      'id',
      'modified',
      'revision',
      $mfk
    ];

    foreach ($mdl_columns as $clmn => $type) {
      if(!in_array($clmn, $meta_fields,true)) {
        // Just copy the value
        $ret[$clmn] = $record[$clmn];
      }
    }

    return $ret;
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
    $pModel = ClassRegistry::init($pmodel->name);

    $args = array();
    $args['conditions'][$pmodel . '.co_id'] = $coid;
    $args['contain'] = true;

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