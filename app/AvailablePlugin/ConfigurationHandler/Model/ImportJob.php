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
  public $name = "ImportJob";

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
//    "CoIdentifierAssignment", // blgTo Co , CoGroup, ExtendedTypes (Required since they are used for validation)
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

  private $_salt = null;


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

    $Co = ClassRegistry::init('Co');
    $dbc = $Co->getDataSource();
    try {
      // Validate the parameters
      $this->validateParams($coId, $params);

      // Check if the configuration file exists
      $config_filename = LOCAL . "Config" . DS . $params["config-file"];
      if(!file_exists($config_filename)) {
        throw new InvalidArgumentException( _txt('er.configuration_handler.config_file.invalid-a', array($config_filename)));
      }

      // check if the configuration file is readable
      if (!is_readable($config_filename)) {
        throw new InvalidArgumentException( _txt('er.configuration_handler.config_file.unreadable-a', array($config_filename)));
      }

      // Parse the file
      $file_contents = file_get_contents($config_filename);
      if($file_contents === false) {
        throw new InvalidArgumentException( _txt('er.configuration_handler.config_file.noparse-a', array($config_filename)));
      }

      $this->_salt = $params['salt'];

      // Get the plugins and append them at the end of the MODELS_IMPORT list
      $model_imports =  self::MODELS_IMPORT;
      $model_imports = array_merge($model_imports, $this->getPluginList());

      // parse json to an associative array
      $configuration_to_import = $this->jsonDecode($file_contents, true);
      $dbc->begin();

      $old_to_new_mapper = array();
      foreach ($model_imports as $immodel) {
        $this->log("Importing {$immodel} Configuration", LOG_INFO);
        $curModel = ClassRegistry::init($immodel);
        // The Plugin models have the format DirectoryName.ModelName. The configuration files uses the class name as the key
        // of the stored record
        $model_name_partials = explode(".", $immodel);
        if(in_array($immodel, array(
          "SqlServer", // blgTo Server
          "Oauth2Server", // blgTo Server
          "HttpServer", // blgTo Server
          "KafkaServer", // blgTo Server
          "MatchServer", // blgTo Server
        ))) {
          // hasOne
          $records_to_import = Hash::extract($configuration_to_import, '{n}.' . ($model_name_partials[1] ?? $model_name_partials[0])) ;
        } else {
          // hasMany
          $records_to_import = Hash::extract($configuration_to_import, '{n}.' . ($model_name_partials[1] ?? $model_name_partials[0]) . '.{n}');
        }
        // There are no records for this model. So go to the next
        if(empty($records_to_import)) {
          continue;
        }

        $num_of_records = count($records_to_import);
        foreach ($records_to_import as $idx => $record) {
          // Construct the final data
          $data = $this->constructData($record,
                                       $curModel,
                                       $old_to_new_mapper,
                                       $coId);

          // CO-1320
          // If the server_type is LDAP skip. It is not supported anymore
          if($curModel->name == 'Server'
             && $data['server_type'] == 'LD') {
            continue 1;
          }

          $importKey = $data['ref'];
          unset($data['ref']);
          $curModel->clear();

          // XXX Disable callbacks for the following callback.
          $disable_callbacks = array(
            'CoEnrollmentFlowWedge',
            'CoDashboardWidget',
            'OrgIdentitySource',
            'CoSqlProvisionerTarget',
            'Server'                     // Do not allow the afterSave to run
          );

          try {
            // Are we performing a creation or an update??
            $curModel->clear();
            $curModel->id = $this->checkForRecordDuplicate($data, $curModel);
            $curModel->set($data);

            $skip_validation = false;
            if (!$curModel->validates()) {
              $errors = $curModel->validationErrors;
              if($curModel->name == "CoEnrollmentAttribute") {
                // CoEnrollmentAttributes require CoEnrollmentAttributesDefault Data to save correctly
                // The import process imports the records one by one and uses the newly created IDs to
                // create the new Foreign keys.
                // If the only error in the list of validationErrors refers to the `er.field.hidden.req`
                // error i will skip the validation and allow the save. It will be fixed later when the
                // CoEnrollmentAttributeDefault records will be imported
                if(count($errors) == 1
                   && isset($errors["hidden"])) {
                  $errors['hidden'] = array_unique($errors["hidden"]);
                  if(count($errors["hidden"]) == 1
                     && $errors["hidden"][0] == _txt('er.field.hidden.req')) {
                    // disable validation for this record
                    $skip_validation = true;
                  }
                }
              }
            }

            $saveOptions = array(
              'provision' => false,
              'callbacks' => !in_array($curModel->name, $disable_callbacks),
              'validate' => !$skip_validation
            );

            if(in_array($curModel->name,
                        array('Oauth2Server',
                              'CoSqlProvisionerTarget'))
            ) {
              // We will skip afterSave for Oauth2Server since we want to import the data as is
              // and there is no record id from previous transactions.
              $saveOptions['safeties'] = 'off';
            }

            if(!$curModel->save($data, $saveOptions)) {
              $this->log(__METHOD__ . "::invalid_fields::message: " . print_r($curModel->invalidFields(), true), LOG_ERROR);
              $this->log(__METHOD__ . "::data: " . print_r($data, true), LOG_DEBUG);
              $this->log(__METHOD__ . "::Model Name: " . $curModel->name, LOG_DEBUG);
              $dbc->rollback();
              throw new RuntimeException(_txt('er.db.save'));
            }
          } catch (Exception $e) {
            // Here we throw only if the save returns false and we continue otherwise. Currently, for the Models
            // this will happen is when there is a duplicate. As a result we can import over and over again
            // without causing the import to break after the first time.
            if ($e->getMessage() == _txt('er.db.save')) {
              throw new RuntimeException(_txt('er.db.save'));
            }
            $this->log(__METHOD__ . "::Model Name: {$curModel->name}\n {$e->getMessage()}", LOG_WARNING);
          }

          // Add the new ID to the mapper.
          $old_to_new_mapper[$importKey] = (int)$curModel->id;

          // Recover in the case of Tree structures
          if($curModel->Behaviors->enabled('Tree')) {
            $curModel->recover('parent');
          }

          // Print the progress percentage
          $this->cliLogPercentage($idx+1, $num_of_records);
        }

        // Just leave a new line to the output
        fwrite(STDOUT, "\n");
      }

      // Is this a dry run?
      if($params["dry"]) {
        $dbc->rollback();
      } else {
        $dbc->commit();
      }

      if($CoJob->id) {
        if($params["dry"]) {
          $CoJob->finish($CoJob->id, _txt('pl.configuration_handler.dryrun.done-a', array($this->name)));
        } else {
          $CoJob->finish($CoJob->id, _txt('pl.configuration_handler.done-a', array($this->name)));
        }
        fwrite(STDOUT, "\n");
      }
    }
    catch(Exception $e) {
      $dbc->rollback();
      $CoJob->finish($CoJob->id, $e->getMessage(), JobStatusEnum::Failed);
      fwrite(STDOUT, "\n");
      $this->log(__METHOD__ . "::Exception message::" . $e->getMessage(), LOG_ERROR);
    }
  }

  /**
   * Print formatted cli percentage
   *
   * @since  COmanage Registry v4.3.0
   * @param  int    $done      Number of iterations completed
   * @param  int    $total     Total number of iterations
   * @return string            Formated string with line return offset
   */

  public function cliLogPercentage($done, $total) {
    $perc = floor(($done / $total) * 100);
    $left = 100 - $perc;
    $out = sprintf("\033[0G\033[2K[%'={$perc}s>%-{$left}s] - $perc%% -- $done/$total", "", "");
    fwrite(STDOUT, $out);
  }

  /**
   * Check for an existing configuration record in the new deployment
   *
   * @since  COmanage Registry v4.3.0
   * @param array   $importRecord         Array with the new record values
   * @param object  $Model                Class Instance of the Model
   *
   * @return int|null                     The Record ID when updating, null when creating a new record
   *
   * @todo the combination $fk_exception and $exceptions arrays could become the `isUniqueChangelog` fields under
   *        each model validation configuration. Then we could extract the columns to query directly from that
   *        configuration
   */
  public function checkForRecordDuplicate($importRecord, $Model) {
    // We omit the ones that are directly associated with the CO
    // The $validate Model array contains no information that could indicate uniqueness. As a result we have to do it
    // manually here.
    $fk_exception = array(
      // One reference but not the CO ID
      "DictionaryEntry" => array("dictionary_id"), //blgTo Dictionary
      "SqlServer" => array("server_id"), // blgTo Server
      "Oauth2Server" => array("server_id"), // blgTo Server
      "HttpServer" => array("server_id"), // blgTo Server
      "KafkaServer" => array("server_id"), // blgTo Server
      "MatchServer" => array("server_id"), // blgTo Server
      "MatchServerAttribute" => array("match_server_id"), // blgTo MatchServer
      "CoGroupNesting" => array("co_group_id"), // blgTo CoGroup
      "CoDashboardWidget" => array("co_dashboard_id"), //blgTo CoDashboard
      "CoEnrollmentAttribute" => array("co_enrollment_flow_id"), // blgTo CoEnrollmentFlow
      "CoEnrollmentAttributeDefault" => array("co_enrollment_attribute_id"), // blgTo CoEnrollmentAttribute
      "CoEnrollmentFlowWedge" => array("co_enrollment_flow_id"), // blgTo CoEnrollmentFlow
      "CoEnrollmentSource" => array("co_enrollment_flow_id", "org_identity_source_id"), // blgTo CoEnrollmentFlow, OrgIdentitySource
      "CoGroupOisMapping" => array("co_group_id"), // blgTo CoGroup, OrgIdentitySource
      "OrgIdentitySourceFilter" => array("org_identity_source_id"), // blgTo OrgIdentitySource, DataFilter
      "CoProvisioningTargetFilter" => array("co_provisioning_target_id", "data_filter_id"), // blgTo CoProvisioningTarget, DataFilter
      "EnvSource" => array("org_identity_source_id"), // blgTo OrgIdentitySource
      "SqlSource" => array("org_identity_source_id", "server_id"), // blgTo OrgIdentitySource, Server
      "OrcidSource" => array("org_identity_source_id", "server_id"), // blgTo OrgIdentitySource, Server
      "CoSqlProvisionerTarget" => array("co_provisioning_target_id", "server_id"), // blgTo CoProvisioningTarget, Server
    );


    // XXX In order to check for duplicates we need a combination of value fields and foreign keys
    //     For example for the ApiUser we want a unique username per CO. Since we introduced the
    //     ChangelogBehavior the isUnique validator function does not work and we have a manual approach
    //     to the problem through using beforeValidate and beforeSave callback. Here we will have to
    //     add some configuration foreach model. We know the value fields but we need to configure what
    //     the foreign keys will be as well.

    $fields_to_query = $this->importKeyConstuct($Model);
    $args = array();
    if(empty($fk_exception[$Model->name])) {
      $args['conditions'][$Model->name . '.co_id'] = $importRecord['co_id'];
    } else {
      $fks = $fk_exception[$Model->name];
      foreach($fks as $fk) {
        $args['conditions'][$Model->name . '.' . $fk] = $importRecord[$fk];
      }
    }
    foreach ($fields_to_query as $field) {
      [$modelName, $clmn] = explode('.', $field);
      $args['conditions'][$field] = $importRecord[$clmn];
      // API username has the CO Id as a prefix. They need special handling when importing
      if($Model->name == "ApiUser" && $field == "ApiUser.username") {
        $args['conditions'][$field] = "co_" . $importRecord['co_id'] . "." . $importRecord[$clmn];
      }
    }
    // For groups we need to leave out the auto ones
    if($Model->name == 'CoGroup') {
      $args['conditions'][] = "{$Model->name}.auto IS NOT TRUE";
      $args['conditions']["{$Model->name}.group_type"] = GroupEnum::Standard;
    }

    $args['contain'] = false;

    $record = $Model->find('first', $args);

    // If there is no record return
    if(empty($record)) {
      return null;
    }
    // Return the record id for updating
    return $record[$Model->name]['id'];
  }


  /**
   * Decrypt a string value using openssl decrypt and salt
   *
   * @param string $data  String to decrypt
   * @since  COmanage Registry v4.3.0
   */

  function decrypt($data) {
    $encryption_method = 'aes-256-cbc';

    // To decrypt, split the encrypted data from our IV - our unique separator used was "::"
    list($encrypted_data, $iv) = explode('::', base64_decode($data), 2);
    $original_value = openssl_decrypt($encrypted_data, $encryption_method, $this->_salt, 0, $iv);

    if($original_value === false) {
      throw new RuntimeException(_txt('er.configuration_handler.decrypt.failed'));
    }

    return $original_value;
  }

  /**
   * Construct the importKey using the following rules
   * - If an exception is defined for the Model use that. (Very few fields should require exceptions.) Otherwise
   * - If the name field is defined use that.
   * - If the description field is defined use that.
   *
   * @param  object  $Model  Class Instance of the Model
   * @since  COmanage Registry v4.3.0
   *
   * @return array   The list of fields to query the new with the old data
   */
  public function importKeyConstuct($Model) {
    // CoSettings is a special Model which exists in all COs and will always be updated
    if($Model->name == "CoSetting") {
      return array();
    }

    $exceptions = array(
      // One reference, to the Co Id
      // XXX We get the CO ID from the command line
      "ApiUser" => array('username'),
      "CoExtendedType" => array('attribute', 'name'),
      "CoLocalization" => array('lkey', 'language'),
      "CoNavigationLink" => array('url'),
      "CoSelfServicePermission" => array('model', 'type'),
      "CoIdentifierAssignment" => array('description', 'identifier_type'),
      "AttributeEnumeration" => array('attribute', 'optvalue'),
      "DictionaryEntry" => array('value', 'code'),
      "SqlServer" => array('type', 'hostname'),
      "Oauth2Server" => array('serverurl'),
      "HttpServer" => array('serverurl'),
      "KafkaServer" => array('brokers'),
      "MatchServer" => array('serverurl'),
      "MatchServerAttribute" => array('attribute', 'type'),
      "CoEnrollmentAttributeDefault" => array('value'),
      "CoGroupOisMapping" => array('attribute', 'comparison', 'pattern'),
    );

    $mdl_schema = $Model->schema();
    $clmns = array_keys($mdl_schema);

    if(isset($Model->name)
       && !empty($exceptions[$Model->name])) {
      return preg_filter('/^/', "{$Model->name}.", $exceptions[$Model->name]);
    } else if(in_array('name', $clmns)) {
      return array($Model->name . ".name");
    } else if(in_array('description', $clmns)) {
      return array($Model->name . ".description");
    } else {
      return array();
    }
  }

  /**
   * Get the plugin names and append them at the end of the Model import list
   *
   * @since  COmanage Registry v4.3.0
   *
   * @return array   The list of models
   */
  public function getPluginList() {
    $plugin_imports = array();
    // Make sure to update this list in duplicate() as well
    foreach(array("CoProvisioningTarget",
                  "DataFilter",
                  "OrgIdentitySource") as $plg_parent_model) {
      $m = ClassRegistry::init($plg_parent_model);

      // Load all the Configured plugins and disable the Changelog Behavior config if any
      // XXX For the case of CoDashboardWidget plugins we will call the beforeDelete callback in the Model itself
      $modelPluginTypes = !empty($m->hasManyPlugins)
                                 ? array_keys($m->hasManyPlugins)
                                 : array();
      foreach($modelPluginTypes as $pluginType) {
        $plugins = $this->loadAvailablePlugins($pluginType);
        foreach($plugins as $pluginName => $plugin) {
          $pluginClassName = $pluginName;
          if(!empty($m->hasManyPlugins)
            && isset($m->hasManyPlugins[$pluginType]['coreModelFormat'])) {
            $corem = sprintf($m->hasManyPlugins[$pluginType]['coreModelFormat'], $plugin->name);
            $pluginClassName = "{$plugin->name}.{$corem}";
          }


          $plugin_imports[] = $pluginClassName;

          $plugin_dependent_import_list = $this->getPluginNestedList($pluginClassName);
          if(!empty($plugin_dependent_import_list)) {
            $plugin_imports = array_merge($plugin_imports, $plugin_dependent_import_list);
          }
        }
      }

    }

    return $plugin_imports;

  }

  /**
   * Get the plugin's full nested list of hasMany dependencies
   * @param  string[]   &$list_of_nested list of hasMany Models
   *
   * @return string[]   List of Model names
   *@since  COmanage Registry v4.3.0
   *
   */

  public function getPluginNestedList($plugin_class_name, &$list_of_nested = array()) {
    $pluginClass = ClassRegistry::init($plugin_class_name);
    $plugin = explode(".", $plugin_class_name)[0];
    if(!empty($pluginClass->hasMany)) {
      foreach ($pluginClass->hasMany as $model => $options) {
        $mmodelClassName = $plugin . "." . $model;
        // Dive deeper
        $this->getPluginNestedList($mmodelClassName, $list_of_nested);
        // Save the models
        $list_of_nested[] = $mmodelClassName;
      }
    }

    return $list_of_nested;
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

      // Boolean values must be 0 or 1 in order to pass the allowEmpty=false validation rule
      if($properties['type'] == 'boolean' && isset($data[$clmn])) {
        $data[$clmn] = filter_var($data[$clmn], FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
      }

      // Decrypt the password and client_secret
      if(in_array($clmn, array('password', 'client_secret'))) {
        $data[$clmn] = $this->decrypt($data[$clmn]);
        continue;
      }

      // The ApiUser name has a prefix constructed by the CO ID remove the old one and prefix the new
      if($Model->name == 'ApiUser'
         && $clmn == 'username') {
        $username = explode('.', $data['username']);
        $data['username'] = $username[1];
        continue;
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

    } // foreach

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
      'config-file' => array(
        'help'     => _txt('pl.configuration_handler.arg.config_file'),
        'short'   => 'g',
        'type'     => 'string',
        'required' => true
      ),
      'dry' => array(
        'short'   => 'd',
        'help'    => _txt('pl.configuration_handler.arg.dry_run'),
        'type' => "bool",
        'default' => false,
        'required' => false
      ),
      'salt' => array(
        'help'     => _txt('pl.configuration_handler.arg.salt'),
        'type'     => 'string',
        'short'    => 'e', # encrypt
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
    if(empty($params['config-file'])) {
      throw new InvalidArgumentException( _txt('er.configuration_handler.config_file.empty'));
    }

    return true;
  }
}