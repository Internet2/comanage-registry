<?php
/**
 * COmanage Registry Group DataFilter Model
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
 * @since         COmanage Registry v3.3.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

class GroupFilter extends AppModel {
  // Required by COmanage Plugins
  public $cmPluginType = "datafilter";
  
  // Document foreign keys
  public $cmPluginHasMany = array();
  
  // Add behaviors
  public $actsAs = array('Changelog' => array('priority' => 5),
                         'Containable');
  
  // Association rules from this model to other models
  public $belongsTo = array("DataFilter");
  
  public $hasMany = array("GroupFilter.GroupFilterRule" => array('dependent' => true));
  
  public $duplicatableModels = array(
    // Must explicitly list this model in the order it should be duplicated
    "GroupFilter" => array(
      "parent" => "DataFilter",
      "fk"     => "data_filter_id"
    ),
    "GroupFilterRule" => array(
      "parent" => "GroupFilter",
      "fk"     => "group_filter_id"
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
  
  /**
   * Expose menu items.
   * 
   * @since COmanage Registry v3.3.0
   * @return Array with menu location type as key and array of labels, controllers, actions as values.
   */
  
  public function cmPluginMenus() {
    return array();
  }
  
  /**
   * Perform the data filter operation.
   *
   * @since  COmanage Registry v3.3.0
   * @param  DataFilterContextEnum $context
   * @param  Integer               $dataFilterId Data Filter ID
   * @param  Array                 $data         Array of (eg: provisioning) data
   * @return Array                               Array of data, in the same format as passed
   * @throws InvalidArgumentException
   */
  
  public function filter($context, $dataFilterId, $provisioningData) {
    if($context != DataFilterContextEnum::ProvisioningTarget) {
      throw new RuntimeException('NOT IMPLEMENTED');
    }
    
    // Pull our configuration
    $args = array();
    $args['GroupFilter.data_filter_id'] = $dataFilterId;
    $args['contain'] = array('GroupFilterRule');
    
    $cfg = $this->find('first', $args);
    
    if(empty($cfg['GroupFilterRule'])) {
      throw new InvalidArgumentException(_txt('er.groupfilter.cfg'));
    }
    
    if(!empty($provisioningData['CoGroup'])) {
      // We're filtering one Group, which is currently being provisioned.
      
      // We have very similar logic in the elseif... could probably merge
      foreach($cfg['GroupFilterRule'] as $rule) {
        $nameMatch = preg_match($rule['name_pattern'], $provisioningData['CoGroup']['name']);
        
        if($nameMatch === false) {
          throw new RuntimeException(_txt('er.groupfilter.regex', array(preg_last_error())));
        }
        
        if((!$nameMatch && $rule['required'] == RequiredEnum::Required)
           || ($nameMatch && $rule['required'] == RequiredEnum::NotPermitted)) {
          // The name does not match a required pattern, or the name matches a
          // not permitted pattern; remove the group from the provisioning data
          
          return array();
          break;
        } elseif($nameMatch && $rule['required'] == RequiredEnum::Optional) {
          // The name matches, stop processing
          break;
        }
      }
    } elseif(!empty($provisioningData['CoPerson']) && !empty($provisioningData['CoGroupMember'])) {
      // Provisioning a CO Person, who may have zero or more group memberships attached
      // Walk the array backwards in case we remove elements
      for($i = count($provisioningData['CoGroupMember']) - 1;$i >= 0;$i--) {
        foreach($cfg['GroupFilterRule'] as $rule) {
          $nameMatch = preg_match($rule['name_pattern'], 
                                  $provisioningData['CoGroupMember'][$i]['CoGroup']['name']);
          
          if($nameMatch === false) {
            throw new RuntimeException(_txt('er.groupfilter.regex', array(preg_last_error())));
          }
          
          if((!$nameMatch && $rule['required'] == RequiredEnum::Required)
             || ($nameMatch && $rule['required'] == RequiredEnum::NotPermitted)) {
            // The name does not match a required pattern, or the name matches a
            // not permitted pattern; remove the group from the provisioning data
            
            unset($provisioningData['CoGroupMember'][$i]);
            break;
          } elseif($nameMatch && $rule['required'] == RequiredEnum::Optional) {
            // The name matches, stop processing
            break;
          }
        }
      }
    }
    
    return $provisioningData;
  }
}
