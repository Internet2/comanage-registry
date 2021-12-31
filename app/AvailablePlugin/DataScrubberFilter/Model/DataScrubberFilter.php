<?php
/**
 * COmanage Registry DataScrubberFilter Model
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
 * @link          https://www.internet2.edu/comanage COmanage Project
 * @package       registry-plugin
 * @since         COmanage Registry v4.1.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

class DataScrubberFilter extends AppModel {
  // Required by COmanage Plugins
  public $cmPluginType = "datafilter";
  
  // Document foreign keys
  public $cmPluginHasMany = array();
  
  // Add behaviors
  public $actsAs = array('Changelog' => array('priority' => 5),
                         'Containable');
  
  // Association rules from this model to other models
  public $belongsTo = array("DataFilter");
  
  public $hasMany = array("DataScrubberFilter.DataScrubberFilterAttribute" => array('dependent' => true));
  
  public $duplicatableModels = array(
    // Must explicitly list this model in the order it should be duplicated
    "DataScrubberFilter" => array(
      "parent" => "DataFilter",
      "fk"     => "data_filter_id"
    ),
    "DataScrubberFilterAttribute" => array(
      "parent" => "DataScrubberFilter",
      "fk"     => "data_scrubber_filter_id"
    )
  );
  
  // Default display field for cake generated views
  public $displayField = "data_filter_id";
  
  // Validation rules for table elements
  public $validate = array(
    'data_filter_id' => array(
      'rule' => 'numeric',
      'required' => true
    )
  );
  
  // The context(s) this filter supports
  public $supportedContexts = array(
    DataFilterContextEnum::OrgIdentitySource
  );
  
  /**
   * Expose menu items.
   * 
   * @since COmanage Registry v4.1.0
   * @return Array with menu location type as key and array of labels, controllers, actions as values.
   */
  
  public function cmPluginMenus() {
    return array();
  }
  
  /**
   * Perform the data filter operation.
   *
   * @since  COmanage Registry v4.1.0
   * @param  DataFilterContextEnum $context
   * @param  Integer               $dataFilterId Data Filter ID
   * @param  Array                 $data         Array of (eg: provisioning) data
   * @return Array                               Array of data, in the same format as passed
   * @throws InvalidArgumentException
   */
  
  public function filter($context, $dataFilterId, $data) {
    if($context != DataFilterContextEnum::OrgIdentitySource) {
      throw new RuntimeException('NOT IMPLEMENTED');
    }
    
    $ret = $data;
    
    // Pull our configuration
    $args = array();
    $args['conditions']['DataScrubberFilter.data_filter_id'] = $dataFilterId;
    $args['contain'] = array('DataScrubberFilterAttribute');
    
    $cfg = $this->find('first', $args);
    
    foreach($cfg['DataScrubberFilterAttribute'] as $attr) {
      if($attr['required'] == RequiredEnum::NotPermitted) {
        if(strncmp($attr['attribute'], "OrgIdentity.", 12)==0) {
          // For Org Identity attributes, we need to parse out the field name
          
          $field = substr($attr['attribute'], 12);
          
          unset($ret['OrgIdentity'][$field]);
        } else {
          // Everything else is a Model, and specifically a typed MVPA.
          
          if(!empty($attr['type'])) {
            // If type is not empty, remove only entries of that type.
            
            $ret[ $attr['attribute'] ] = Hash::remove($ret[ $attr['attribute'] ], '{n}[type='.$attr['type'].']');
          } else {
            // If type is empty, remove all entries of that type.
            
            unset($ret[ $attr['attribute'] ]);
            
            // Create an empty array for consistency with how Hash::remove works
            $ret[ $attr['attribute'] ] = array();
          }
        }
      }
      // else we simply ignore Required or Optional, though plausibly we could
      // throw an error if a Required attribute were not present
    }
    
    return $ret;
  }
}
