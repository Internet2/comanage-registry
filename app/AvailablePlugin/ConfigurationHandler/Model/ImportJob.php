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

class ImportJob extends CoJobBackend {
  /**
   * All supported models
   * All the models below need to be direct CO descendants
   *
   */
  const  MODELS_IMPORT = array(
    // One reference, to the Co Id
    // XXX We get the CO ID from the command line
    "ApiUser", // blgTo Co
    "CoExtendedType", // blgTo Co
    "Server", // blgTo Co
    "CoGroup", // blgTo Co
    "Cou", // blgTo Co
    "CoTheme", // blgTo Co
    "CoLocalization", // blgTo Co
    "CoNavigationLink", // blgTo Co
    "CoMessageTemplate", // blgTo Co
    "CoSelfServicePermission", // blgTo Co
    "Dictionary", // blgTo Co
    "DataFilter", // blgTo Co

    "CoDashboard", // blgTo Co , CoGroup
    "CoIdentifierAssignment", // blgTo Co , CoGroup
    "VettingStep", // blgTo Co , CoGroup
    "CoIdentifierValidator", // blgTo Co , CoExtendedType
    "CoTermsAndConditions", // blgTo Co, Cou
    "AttributeEnumeration", // blgTo Co, Dictionary

    // One reference but not the CO ID
    "DictionaryEntry", //blgTo Dictionary
    "SqlServer", // blgTo Server
    "Oauth2Server", // blgTo Server
    "HttpServer", // blgTo Server
    "KafkaServer", // blgTo Server
    "MatchServer", // blgTo Server
    "MatchServerAttribute", // blgTo MatchServer
    "CoGroupNesting", // blgTo CoGroup

    "CoDashboardWidget", //blgTo CoDashboard

    "CoEnrollmentFlow", // blgTo Co, CoGroup, CoMessageTemplate, CoTheme, MatchServer
    "CoEnrollmentFlowWedge", // blgTo CoEnrollmentFlow
    "CoEnrollmentAttribute", // blgTo CoEnrollmentFlow
    "CoEnrollmentAttributeDefault", // blgTo CoEnrollmentAttribute

    "CoExpirationPolicy", // blgTo Co, Cou, CoGroup, CoMessageTemplate
    "CoPipeline", // blgTo Co, Cou, CoEnrollmentFlow, MatchServer
    "OrgIdentitySource", // blgTo Co, CoPipeline
    "CoEnrollmentSource", // blgTo CoEnrollmentFlow, OrgIdentitySource
    "CoProvisioningTarget", // blgTo Co, CoGroup, OrgIdentitySource
    "CoGroupOisMapping", // blgTo CoGroup, OrgIdentitySource

    "OrgIdentitySourceFilter", // blgTo OrgIdentitySource, DataFilter
    "CoProvisioningTargetFilter", // blgTo CoProvisioningTarget, DataFilter

    "CoSetting", // blgTo Co, CoGroup, CoTheme, CoPipeline, CoDashboard
  );

  /**
   * Execute the requested Job.
   *
   * @param int $coId CO ID
   * @param CoJob $CoJob CO Job Object, id available at $CoJob->id
   * @param array $params Array of parameters, as requested via parameterFormat()
   * @throws InvalidArgumentException
   * @since  COmanage Registry v4.3.0
   */

  public function execute($coId, $CoJob, $params) {
    $CoJob->update($CoJob->id,
                   null,
                   null,
                   _txt('pl.configuration_handler.export.start'));

    try {
      // Validate the parameters
      $this->validateParams($coId, $params);

      // Check if the configuration file exists
      $config_filename = LOCAL . "Config" . DS . $params["filename"];
      if(!file_exists($config_filename)) {
        throw new InvalidArgumentException( _txt('er.configuration_handler.filename.invalid-a', array($config_filename)));
      }

      // check if the configuration file is readable
      if (!is_readable($config_filename)) {
        throw new InvalidArgumentException( _txt('er.configuration_handler.filename.unreadable-a', array($config_filename)));
      }

      // Parse the file
      $file_contents = file_get_contents($config_filename);
      if($file_contents === false) {
        throw new InvalidArgumentException( _txt('er.configuration_handler.filename.noparse-a', array($config_filename)));
      }

      // parse json to an associative array
      $configuration_to_import = $this->jsonDecode($file_contents, true);

      $Co = ClassRegistry::init('Co');
      $dbc = $Co->getDataSource();
      $dbc->begin();

      $old_to_new_mapper = array();
      foreach (self::MODELS_IMPORT as $immodel) {
        $this->log("Importing {$immodel} Configuration", LOG_INFO);
        $curModel = ClassRegistry::init($immodel);
        $records_to_import = Hash::extract($configuration_to_import, '{n}.' . $immodel . '.{n}');
        // There are no records for this model. So go to the next
        if(empty($records_to_import)) {
          continue;
        }

        foreach ($records_to_import as $record) {
          $data = $this->constructData($record,
                                       $curModel,
                                       $old_to_new_mapper,
                                       $coId);

          // CO-1320
          // If the server_type is LDAP skip. It is not supported anymore
          // XXX Comment out
          if($curModel->name == 'Server'
             && $data['server_type'] == 'LD') {
            $this->log(__METHOD__ . "::Data skipped => " . print_r($data, true), LOG_ALERT);
            $this->log(__METHOD__ . "::LDAP server type is not supported any more", LOG_ALERT);
            continue;
          }


          $importKey = $data['key'];
          unset($data['key']);
          $curModel->clear();
          if(!$curModel->save($data, array(
            'provision' => false,
            'callbacks' => true,
            'validate' => true
          ))) {
            $this->log(__METHOD__ . "::invalid_fields::message" . print_r($curModel->invalidFields(), true), LOG_ERROR);
            $this->log(__METHOD__ . "::Model Name: " . $curModel->name, LOG_ERROR);
            $dbc->rollback();
            throw new RuntimeException(_txt('er.db.save'));
          }

          // Add the new ID to the mapper.
          $old_to_new_mapper[$importKey] = $curModel->id;

          // Recover in the case of Tree structures
          if($curModel->Behaviors->enabled('Tree')) {
            $curModel->recover('parent');
          }
        }

      }

//      $dbc->commit();
      $dbc->rollback();

      if($CoJob->id) {
        $CoJob->finish($CoJob->id, _txt('pl.configuration_handler.done'));
      }
    }
    catch(Exception $e) {
      $dbc->rollback();
      $CoJob->finish($CoJob->id, $e->getMessage(), JobStatusEnum::Failed);
    }
  }


  /**
   * The constructData restores the base64 text fields and finds the newly created foreign_key IDs
   *
   * @param array  $data          The record as read from the configuration file
   * @param Object $Model         The initiated Model
   * @param array  $mmapper       A set of unique key mapped to an id. The key comes from the configuration file
   *                              and id is the newly created value during the configuration restoration
   *
   * @since  COmanage Registry v4.3.0
   *
   * @return array             The complete data ready to be saved
   */
  public function constructData($data, $Model, $mmapper, $coId) {
    if(empty($data)) {
      return array();
    }

    $mdl_schema = $Model->schema();
    foreach ($mdl_schema as $clmn => $properties) {
      if($clmn == 'id') {
        continue;
      }

      // The ApiUser name has a prefix constructed by the CO ID remove the old one and prefix the new
      if($Model->name == 'ApiUser'
         && $clmn == 'username') {
        $username = explode('.', $data['username']);
        $data['username'] = $username[1];
      }

      // Restore the base64 encoding
      if ($properties['type'] === "text") {
        if(strpos($data[$clmn], "base64::") !== false) {
          // Get only the payload
          $value = str_replace("base64::", "", $data[$clmn]);
          $data[$clmn] = base64_decode($value);
        }
      }

      // Get the newly created foreign key ids
      foreach($Model->belongsTo as $rmodel => $roptions) {
        // Iterate until we find the association match for the current foreign key
        if($roptions['foreignKey'] == $clmn) {
          // Retrieve the current foreign key value from the mapper
          // For the case of co_id foreign key i will get the value from the parameters list
          $data[$clmn] = $clmn == 'co_id' ? $coId : ($mmapper[ $data[$clmn] ] ?? null);
          break;
        }
      }

    }

    return $data;
  }


  /**
   * Wrapper for JSON encoding that throws when an error occurs.
   *
   * @param string $value       The value being encoded
   * @param bool   $associative When true it will return an associative array. When false it will return an object
   * @param int    $depth       Set the maximum depth. Must be greater than zero.
   *
   * @throws InvalidArgumentException if the JSON cannot be decoded.
   *
   * @link https://www.php.net/manual/en/function.json-decode.php
   */
  public function jsonDecode($value, $associative = false, $depth = 512) {
    $ret = json_decode($value, $associative, $depth);
    if (JSON_ERROR_NONE !== json_last_error()) {
      throw new InvalidArgumentException('json_encode error: ' . json_last_error_msg());
    }

    return $ret;
  }


  /**
   * Obtain the list of parameters supported by this Job.
   *
   * @since  COmanage Registry v4.3.0
   * @return Array Array of supported parameters.
   */

  public function parameterFormat() {
    $params = array(
      // XXX The filename should be provided without the path
      'filename' => array(
        'help'     => _txt('pl.provisionerjob.arg.filename'),
        'type'     => 'string',
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
    if(empty($params['filename'])) {
      throw new InvalidArgumentException( _txt('er.configuration_handler.filename.empty'));
    }


    return true;
  }

}