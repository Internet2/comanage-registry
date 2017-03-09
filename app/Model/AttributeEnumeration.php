<?php
/**
 * COmanage Registry Attribute Enumeration Model
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
 * @package       registry
 * @since         COmanage Registry v2.0.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */
  
class AttributeEnumeration extends AppModel {
  // Define class name for cake
  public $name = "AttributeEnumeration";
  
  // Current schema version for API
  public $version = "1.0";
  
  // Association rules from this model to other models
  public $belongsTo = array('Co');
  
  // Default display field for cake generated views
  public $displayField = "optvalue";
  
  public $actsAs = array('Containable',
                         'Changelog' => array('priority' => 5));
  
  // Validation rules for table elements
  public $validate = array(
    'co_id' => array(
      'rule' => 'numeric',
      'required' => false,
      'allowEmpty' => true
    ),
    'attribute' => array(
      // Also need to add to supportedAttrs(), below
      'rule' => array('inList', array('CoPersonRole.title',
                                      'CoPersonRole.o',
                                      'CoPersonRole.ou',
                                      'OrgIdentity.title',
                                      'OrgIdentity.o',
                                      'OrgIdentity.ou')),
      'required' => true,
      'message' => 'A supported attribute must be provided'
    ),
    'optvalue' => array(
      // optvalue is not required so blank options (ie: "") can be defined
      'rule' => array('maxLength', 128),
      'required' => false,
      'allowEmpty' => true
    ),
    'status' => array(
      'rule' => array('inList', array(SuspendableStatusEnum::Active,
                                      SuspendableStatusEnum::Suspended)),
      'required' => true,
      'message' => 'A valid status must be selected'
    )
  );
  
  // Enum type hints
  
  public $cm_enum_types = array(
    'status' => 'SuspendableStatusEnum'
  );
  
  /**
   * Determine if there are any defined, active enumerations for a specific attribute.
   *
   * @since  COmanage Registry v2.0.0
   * @param  Integer CO ID (or null for CMP level attributes)
   * @param  String Attribute, of the form Model.attribute (or null for all)
   * @param  String Format ('all' or 'list', as for Cake find, or 'validation' to return as a validation rule)
   * @param  Boolean True if org identities are pooled and org identity enumerations should also be retrieved, false otherwise
   * @return Array Formatted as requested
   */
  
  public function active($coId, $attribute, $format='list', $pooled=false) {
    $args = array();
    if($coId && $pooled) {
      // Very specific syntax required to pull CO ID of a specific value OR null
      $args['conditions']['OR'] = array(
        array('AttributeEnumeration.co_id' => $coId),
        array('AttributeEnumeration.co_id' => null)
      );
    } else {
      $args['conditions']['AttributeEnumeration.co_id'] = $coId;
    }
    if($attribute) {
      $args['conditions']['AttributeEnumeration.attribute'] = $attribute;
    }
    $args['conditions']['AttributeEnumeration.status'] = SuspendableStatusEnum::Active;
    $args['order'][] = 'AttributeEnumeration.optvalue';
    
    if($format == 'list' || $format == 'validation') {
      // When generating selects, we need the key and the value to both be the selectable option
      $args['fields'] = array('AttributeEnumeration.optvalue',
                              'AttributeEnumeration.optvalue');
      
      if(!$attribute) {
        // Group by attribute
        $args['fields'][] = 'AttributeEnumeration.attribute';
      }
    }
    
    $enums = $this->find(($format == 'validation' ? 'list' : $format), $args);
    
    if($format == 'validation') {
      $ret = array();
      
      if(!empty($enums)) {
        $ret[] = 'inList';
        $ret[] = array_values($enums);
      }
      
      return $ret;
    } else {
      return $enums;
    }
  }
  
  /**
   * Actions to take before a save operation is executed.
   *
   * @since  COmanage Registry v2.0.0
   */
  
  public function beforeSave($options = array()) {
    // We temporarily load NormalizationBehavior so we can normalize the enumerations
    // before saving (assuming normalizations are enabled). We do this so that
    // the enumeration value matches the actual value saved (since the operational
    // record save would normalize the selected value, which would then no longer
    // match the configured option).
    
    $this->Behaviors->load('Normalization');
    
    // We need to restructure the data to fit what Normalizers expect
    $data = array();
    
    $a = explode('.', $this->data['AttributeEnumeration']['attribute'], 2);
    $data[ $a[0] ][ $a[1] ] = $this->data['AttributeEnumeration']['optvalue'];
    
    if(!empty($this->data['AttributeEnumeration']['co_id'])) {
      $newdata = $this->normalize($data, $this->data['AttributeEnumeration']['co_id']);
    } else {
      // Normalizations not currently supported for org identity data (CO-1172)
      $newdata = $data;
    }
    
    // Now that we have a result, copy it back into the record we want to save
    $this->data['AttributeEnumeration']['optvalue'] = $newdata[ $a[0] ][ $a[1] ];
    
    $this->Behaviors->unload('Normalization');
    
    return true;
  }
  
  /**
   * Assemble a list of supported attributes, suitable for use in generating a select button.
   *
   * @since  COmanage Registry 2.0.0
   * @param  Boolean $pooled Whether or not OrgIdentities are pooled
   * @param  Boolean $orgOnly Whether or not to only return OrgIdentity attributes
   * @return Array Hash of supported attributes, with model name as key and localized text as value
   */
  
  public function supportedAttrs($pooled = false, $orgOnly = false) {
    $ret = array();
    
    // Also need to add to $validate, above
    if(!$orgOnly) {
      $ret['CoPersonRole.title'] = _txt('fd.title') . " (" . _txt('ct.co_person_roles.1') . ")";
      $ret['CoPersonRole.o'] = _txt('fd.o') . " (" . _txt('ct.co_person_roles.1') . ")";
      $ret['CoPersonRole.ou'] = _txt('fd.ou') . " (" . _txt('ct.co_person_roles.1') . ")";
    }
    
    if(!$pooled || $orgOnly) {
      // Either org identities are not pooled (in which case we return OrgIdentity
      // attributes with the CO Person attributes) or we have been asked only for
      // org identity attributes (for CMP level configuration)
      $ret['OrgIdentity.title'] = _txt('fd.title') . " (" . _txt('ct.org_identities.1') . ")";
      $ret['OrgIdentity.o'] = _txt('fd.o') . " (" . _txt('ct.org_identities.1') . ")";
      $ret['OrgIdentity.ou'] = _txt('fd.ou') . " (" . _txt('ct.org_identities.1') . ")";
    }
    
    return $ret;
  }
}
