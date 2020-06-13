<?php
/**
 * COmanage Registry Group Name DataFilter Model
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

class GroupNameFilter extends AppModel {
  // Required by COmanage Plugins
  public $cmPluginType = "datafilter";
  
  // Document foreign keys
  public $cmPluginHasMany = array();
  
  // Add behaviors
  public $actsAs = array('Changelog' => array('priority' => 5),
                         'Containable');
  
  // Association rules from this model to other models
  public $belongsTo = array("DataFilter");
  
  // Default display field for cake generated views
  public $displayField = "identifier_type";
  
  // Validation rules for table elements
  public $validate = array(
    'data_filter_id' => array(
      'rule' => 'numeric',
      'required' => true
    ),
    'identifier_type' => array(
      'rule' => array('validateInput'),
      'required' => true,
      'allowEmpty' => false
    )
  );
  
  /**
   * Expose menu items.
   * 
   * @ since COmanage Registry v3.3.0
   * @ return Array with menu location type as key and array of labels, controllers, actions as values.
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
    $args['GroupNameFilter.data_filter_id'] = $dataFilterId;
    $args['contain'] = false;
    
    $cfg = $this->find('first', $args);
    
    if(empty($cfg['GroupNameFilter']['identifier_type'])) {
      throw new InvalidArgumentException(_txt('er.groupnamefilter.cfg'));
    }
    
    if(!empty($provisioningData['CoGroup'])) {
      // Provisioning a single CO Group
      // Do we have an identifier of the configured type?
      
      $id = Hash::extract($provisioningData['Identifier'], '{n}[type='.$cfg['GroupNameFilter']['identifier_type'].']');
      
      if(!empty($id[0]['identifier'])) {
        // It's possible we'll get more than one identifier, in which case we
        // only use the first
        
        $provisioningData['CoGroup']['name'] = $id[0]['identifier'];
      }
    } elseif(!empty($provisioningData['CoPerson']) && !empty($provisioningData['CoGroupMember'])) {
      // Provisioning a CO Person, who may have zero or more group memberships attached
      for($i = 0;$i < count($provisioningData['CoGroupMember']);$i++) {
        // Do we have an identifier of the configured type?
        
        $id = Hash::extract($provisioningData['CoGroupMember'][$i]['CoGroup']['Identifier'],
                            '{n}[type='.$cfg['GroupNameFilter']['identifier_type'].']');
        
        if(!empty($id[0]['identifier'])) {
          // It's possible we'll get more than one identifier, in which case we
          // only use the first
          
          $provisioningData['CoGroupMember'][$i]['CoGroup']['name'] = $id[0]['identifier'];
        }
      }
    }
    
    return $provisioningData;
  }
}
