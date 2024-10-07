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
  public $name = "ExportJob";

  /**
   * All supported models
   * All the models below need to be direct CO descendants
   *
   */

  const  MODELS_EXPORT = array(
    "ApiUser",
    "AttributeEnumeration",
    "CoDashboard" => array("CoDashboardWidget"),
    "CoEnrollmentFlow" => array(
      "CoPipeline",
      "CoEnrollmentFlowWedge",
      "CoEnrollmentSource",
      "CoEnrollmentAttribute" => array("CoEnrollmentAttributeDefault")
    ),
    "CoExpirationPolicy",
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
    "CoProvisioningTarget",
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
      "MatchServer" => array("MatchServerAttribute"),
    ),
    "VettingStep"
  );

  private $_salt = null;

  /**
   * Encrypt a string value using openssl encrypt and salt
   *
   * @param string $data  String to encrypt
   * @since  COmanage Registry v4.3.0
   */

  function encrypt($data) {
    $encryption_method = 'aes-256-cbc';
    // Generate an initialization vector
    $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($encryption_method));
    // Encrypt the data using AES 256 encryption in CBC mode using our encryption key and initialization vector.
    $encrypted = openssl_encrypt($data, $encryption_method, $this->_salt, 0, $iv);
    // The $iv is just as important as the key for decrypting, so save it with our encrypted data using a unique separator (::)
    return base64_encode($encrypted . '::' . $iv);
  }

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
      $models_for_export = array();
      if($params['model_list'] == "All") {
        $models_for_export = self::MODELS_EXPORT;
      } else if(strpos($params['model_list'], ",") !== false) {
        $models_for_export = explode(",", $params['model_list']);
      } else {
        $models_for_export[] = $params['model_list'];
      }

      $this->_salt = $params['salt'];

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

        $mModelClass = ClassRegistry::init($mmodel);

        // Get all the records for this CoModel
        // Also fetch all the contain data foreach record
        $records = $this->getModelRecords('co_id', $coId, $mmodel);
        $mdata = array();
        foreach ($records as $record) {
          // Prepare the data
          $mdata[$mmodel][] = $this->filterMetadataInbound($record,
                                                           $mmodel,
                                                           self::MODELS_EXPORT[$mmodel] ?? array(),
                                                           $record,
                                                           $mdata,
                                                           $mmodel);

          // This model is a plugin wrapper.
          // I will fetch all the plugins and add them in the configuration file
          if(in_array($mmodel, array("CoProvisioningTarget",
                                     "DataFilter",
                                     "OrgIdentitySource"))
          ) {
            $fk_column = Inflector::underscore($mmodel) . '_id';
            // Get all the enabled plugins of that type
            // Load all the Configured plugins
            $modelPluginTypes = !empty($mModelClass->hasManyPlugins)
              ? array_keys($mModelClass->hasManyPlugins)
              : array();
            foreach($modelPluginTypes as $pluginType) {
              $plugins = $this->loadAvailablePlugins($pluginType);
              foreach($plugins as $pluginName => $plugin) {
                $pluginClassName = $pluginName;
                if(!empty($mModelClass->hasManyPlugins)
                  && isset($mModelClass->hasManyPlugins[$pluginType]['coreModelFormat'])) {
                  $corem = sprintf($mModelClass->hasManyPlugins[$pluginType]['coreModelFormat'], $plugin->name);
                  $pluginClassName = "{$plugin->name}.{$corem}";
                }

                $pluginClass = ClassRegistry::init($pluginClassName);
                $plugin_records = $this->getModelRecords($fk_column, $record[$mmodel]["id"], $pluginClassName, true);
                foreach ($plugin_records as $plugin_record) {
                  $mdata[$pluginClass->name][] = $this->filterMetadataInbound($plugin_record,
                                                                              $pluginClass->name,
                                                                              array_keys($pluginClass->hasMany) ?? array(),
                                                                              $plugin_record,
                                                                              $mdata,
                                                                              $pluginClass->name);
                }
              }
            }
          } // Plugin records

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

      // This is not a shell script as a result we can not use $this->>out
      $out = sprintf("\e[96m%s \e[0m\n", _txt('pl.configuration_handler.export.complete', array($config_filename)));
      fwrite(STDOUT, $out);

      if($CoJob->id) {
        $CoJob->finish($CoJob->id, _txt('pl.configuration_handler.done-a', array($this->name)));
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
   * @param  array  $dataset          Record to examine
   * @param  string $modelName        Name of model being examined
   * @param  string $hasManyOneList   List of models that are currently supported from the configuration self::MODELS_EXPORT
   * @param  array  $record           Record retrieved from database
   * @param  array  $mdata            Reference to the json configuration array
   * @return array                    Formatted record
   */

  public function filterMetadataInbound($dataset, $modelName, $hasManyOneList, $record, &$mdata, $path = '') {
    // Get the data we need from the record and move them into $ret
    // In the process create the associations placeholders
    $ret = array();

    // Get a pointer to our model
    $Model = ClassRegistry::init($modelName);
    // We are doing this here since for the case of the plugins the $modelName we feed the function with will be
    // Name.name. This is needed in order to intantiate the Class properly. Then we get the name from the object.
    $modelName = $Model->name;
    $path_partial = '';

    if(!empty($hasManyOneList)) {
      foreach($hasManyOneList as $key_model => $value_model) {
        $nxt_model = is_int($key_model) ? $value_model : $key_model;
        $model_hasMany_keys = array_keys($Model->hasMany);
        $model_hasOne_keys = array_keys($Model->hasOne);
        // For has many relationships the path should also contain the index parser
        // We need to check if the model association is hasOne or hasMany
        $path_partial_with_prefix = null;
        if(in_array($nxt_model, $model_hasMany_keys)) {
          // XXX The way we implement it here we get all the nested records. We do not
          //     get the ones corresponding to each individual record
          $path_partial = "{$nxt_model}.{n}";

          // If the $path is of the form Model.{n} then we need to prepend the {n} extractor
          $path_explode = explode('.', $path);
          if(array_pop($path_explode) == '{n}') {
            // We do not want to alter the $path_partial variable because we will need the correct
            // path below. The reason why we are add the prefix is that CAKEPHP returns a list
            // of records when extracting them from a dataset using a trailing {n} extractor.
            // The same extractor has be prepended in our query template in order to match our new
            // dataset
            $path_partial_with_prefix = '{n}.' . $path_partial;
          }
        } else if(in_array($nxt_model, $model_hasOne_keys)) {
          $path_partial = $nxt_model;
        }

        // This linked model has no record. We continue to the next one.
        // CAKEPHP will return a record with all the values set to null when using the contain
        // feature because you use the default configuration which queries using LEFT JOIN
        // In order to find out if the record has any value you use the array_filter
        $next_values = $record[$nxt_model] ?? Hash::extract($record, $path_partial_with_prefix ?? $path_partial);
        $check_null_values = array_filter($next_values);

        if(empty($check_null_values)) {
          continue;
        }

        $tmp_data = $this->filterMetadataInbound($dataset,
                                                 $nxt_model,
                                                 $hasManyOneList[$nxt_model] ?? array(),
                                                 $next_values ?? $dataset,
                                                 $mdata,
                                                 empty($path) ? $path_partial : "{$path}.{$path_partial}");

        // Initialize the array
        if(empty($mdata[$nxt_model])) {
          $mdata[$nxt_model] = array();
        }
        $mdata[$nxt_model] = array_merge($mdata[$nxt_model], $tmp_data);
      }
    }

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
//      ...$assc_keys,
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
        $this->constructRecord($data, $Model, $meta_fields, $assc_keys, $tmp);
        $ret[] = $tmp;
      }
    } else {
      if(empty(array_filter($dataset_to_parse))) {
        // Record with null values
        return;
      }
      // hasOne
      $this->constructRecord($dataset_to_parse,  $Model, $meta_fields, $assc_keys, $ret);
    }

    return $ret;
  }


  /**
   *
   *
   * @since  COmanage Registry v4.3.0
   * @param array $data
   * @param Model $Model
   * @param array $meta_fields

   * @param array $assc_keys
   * @param array $ret
   *
   * @return void
   */
  public function constructRecord($data,  $Model, $meta_fields, $assc_keys, &$ret) {
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
            $ret['ref'] = strtolower($Model->name . $clmn . $data[$clmn]);
          } else if(in_array($clmn, array('password', 'client_secret'))) {
            $ret[$clmn] = $this->encrypt($data[$clmn]);
          } else {
            // This is a foreign key to a belongs to Model
            if(in_array($clmn, $assc_keys, true)) {
              foreach($Model->belongsTo as $rmodel => $roptions) {
                // Iterate until we find the association match for the current foreign key
                if($roptions['foreignKey'] != $clmn) {
                  continue;
                }
                $multi_part_className = explode(".", $roptions['className']);
                // $multi_part_className[1]: when we have a dependent model inside a plugin
                // $multi_part_className[0]: otherwise
                $place_holder_hashed_value = strtolower(($multi_part_className[1] ?? $multi_part_className[0]) . "id" . ($data[$clmn] ?? 0) );
                $ret[$clmn] = isset($data[$clmn]) ? $place_holder_hashed_value : null;
              }
            } else {
              // Just copy the value
              $ret[$clmn] = $data[$clmn];
            }
          }
        }
      }
    }
  }

  /**
   * Retrieve Model records from the database
   *
   * @param string  $fk_column   Foreign key column name
   * @param int     $fk_value    Foreign key value
   * @param string  $pmodel      Model name in Class format
   * @param bool    $isPlugin    Is this a plugin Model
   *
   * @return array  model database records
   */
  public function getModelRecords($fk_column, $fk_value, $pmodel, $isPlugin = false) {
    $pModel = ClassRegistry::init($pmodel);

    // For groups we need to leave out the auto ones
    $args = array();
    $args['conditions'][$pModel->name . '.' . $fk_column] = $fk_value;
    if($pModel->name == 'CoGroup') {
      $args['conditions'][] = "{$pModel->name}.auto IS NOT TRUE";
      $args['conditions']["{$pModel->name}.group_type"] = GroupEnum::Standard;
    }
    // For enrollment flows we will exclude the default templates
    if($pModel->name == 'CoEnrollmentFlow') {
      // XXX In order to make it more dynamic we should refactor the core code and decouple the
      //     $templates = array(); from the addDefaults function
      $templates = array(
        // Enrollment Flow Template Names
        _txt('fd.ef.tmpl.arl'),
        _txt('fd.ef.tmpl.csp'),
        _txt('fd.ef.tmpl.inv'),
        _txt('fd.ef.tmpl.lnk'),
        _txt('fd.ef.tmpl.ssu')
      );
      $args['conditions']['NOT']['CoEnrollmentFlow.name'] =  $templates;
    }
    // Since the COU is a Tree structure we want the ones with no parent first. These are the parent nodes
    // and will allow a successful re-construction of the Tree
    if($pModel->Behaviors->enabled('Tree')) {
      // We will order by parent_id using the NULLS FIRST option.
      // PostgreSQL needs the NULLS FIRST in order to put the null at the top
      // We will treat this as the default
      $args['order'] = $pModel->name . '.parent_id ASC NULLS FIRST';

      // What should we do in the case of MySQL
      $db = $pModel->getDataSource();
      $db_driver = explode("/", $db->config['datasource'], 2);

      $db_driverName = $db_driver[1];
      if(preg_match("/mysql/i", $db_driverName)) {
        // MySQL treats NULLs as less than the rest of the values and sorts them at the top
        $args['order'] = $pModel->name . '.parent_id ASC';
      }

    }

    // We want to contain all the belongsTo associations, as well as the hasMany or hasOne we allow. This will make things easier
    // for the COU model. The COU model hasMany Roles which we do not need. Excluding the CoPersonRoles from the contain list will
    // speed up the process
    // For the case of plugins we assume that the hasMany array will always be associative since there has to be the dependent
    // property set to true.
    $args['contain'] = $isPlugin ? array_keys($pModel->hasMany) : array_merge(self::MODELS_EXPORT[$pModel->name] ?? array(), array_keys($pModel->belongsTo));

    if (is_array($args['contain'])
        && ($key = array_search("Co", $args['contain'])) !== false) {
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
   * @since  COmanage Registry v4.3.0
   * @return Array Array of supported parameters.
   */

  public function parameterFormat() {
    $params = array(
      'model_list' => array(
        'help'     => _txt('pl.configuration_handler.arg.models_list'),
        'type'     => 'select',
        'short'    => 'l', # list
        'choices'  => array_merge(
          array('All'),
          array_keys(self::MODELS_EXPORT)
        ),
        'required' => true
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
    if(empty($params['model_list']) || $params['model_list'] == "All") {
      return true;
    }

    // This is a list of Models
    if(strpos($params['model_list'], ',') !== false) {
      // This is a CSV list
      $list = explode(',', $params['model_list']);
      $diff = array_diff($list, self::MODELS_EXPORT);
      if(count($diff) > 0) {
        $diff_to_string = implode(",", $diff);
        $this->log(__METHOD__ . "::message " . _txt('er.configuration_handler.model_list.invalid-a', array($diff_to_string)), LOG_ERROR);
        throw new InvalidArgumentException( _txt('er.configuration_handler.model_list.invalid-a', array($diff_to_string)));
      }
      return true;
    }

    // This is only one Model which we need to get the configuration for
    if(!in_array($params['model_list'], self::MODELS_EXPORT)) {
      $this->log(__METHOD__ . "::message " . _txt('er.configuration_handler.model_list.invalid-a', array($params['model_list'])), LOG_ERROR);
      throw new InvalidArgumentException( _txt('er.configuration_handler.model_list.invalid-a', array($params['model_list'])));
    }

    return true;
  }

}