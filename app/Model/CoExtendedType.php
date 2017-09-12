<?php
/**
 * COmanage Registry CO Extended Type Model
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
 * @since         COmanage Registry v0.6
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */
  
class CoExtendedType extends AppModel {
  // Define class name for cake
  public $name = "CoExtendedType";
  
  // Current schema version for API
  public $version = "1.0";
  
  // Association rules from this model to other models
  public $belongsTo = array("Co");
  
  public $hasMany = array("CoIdentifierValidator");
  
  // Default display field for cake generated views
  public $displayField = "display_name";
  
  public $actsAs = array('Containable');
  
  // Validation rules for table elements
  public $validate = array(
    'co_id' => array(
      'rule' => 'numeric',
      'required' => true,
      'message' => 'A CO ID must be provided'
    ),
    'attribute' => array(
      // Also need to add to supportedAttrs(), below
      'rule' => array('inList', array('Address.type',
                                      'CoPersonRole.affiliation',
                                      'EmailAddress.type',
                                      'Identifier.type',
                                      'Name.type',
                                      'TelephoneNumber.type',
                                      'Url.type')),
      'required' => true,
      'message' => 'A supported attribute type must be provided'
    ),
    'name' => array(
      'rule' => 'alphaNumeric',
      'required' => true,
      'message' => 'A name must be provided and consist of alphanumeric characters'
    ),
    'display_name' => array(
      'rule' => array('validateInput'),
      'required' => true,
      'message' => 'A name must be provided'
    ),
    'edupersonaffiliation' => array(
      'rule' => array('inList', array(AffiliationEnum::Affiliate,
                                      AffiliationEnum::Alum,
                                      AffiliationEnum::Employee,
                                      AffiliationEnum::Faculty,
                                      AffiliationEnum::LibraryWalkIn,
                                      AffiliationEnum::Member,
                                      AffiliationEnum::Staff,
                                      AffiliationEnum::Student)),
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
   * Determine if there are any defined, active extended types for a specific attribute.
   *
   * @since  COmanage Registry v0.6
   * @param  Integer CO ID
   * @param  String Attribute, of the form Model.attribute
   * @param  String Format ('all' or 'list', as for Cake find, or 'keyed' for find keyed on ID)
   * @return Array List of defined extended types, keyed on extended type ID
   */
  
  public function active($coId, $attribute, $format='list') {
    return $this->definedTypes($coId, $attribute, $format, true);
  }
  
  /**
   * Add the default types for an attribute.
   *
   * @since  COmanage Registry v0.9.2
   * @param  Integer CO ID
   * @param  String Attribute, of the form Model.attribute
   * @return Boolean Success
   * @throws InvalidArgumentException
   * @throws RuntimeException
   */
  
  public function addDefault($coId, $attribute) {
    // Make sure $attribute is valid
    $supported = $this->supportedAttrs();
    
    if(!isset($supported[$attribute])) {
      throw new InvalidArgumentException(_txt('er.unknown', array($attribute)));
    }
    
    // Split $attribute
    $attr = explode('.', $attribute, 2);
    
    // We need the appropriate model for $attribute to manipulate the default types
    $model = ClassRegistry::init($attr[0]);
    
    $modelDefault = $model->defaultTypes($attr[1]);
    
    if(!empty($modelDefault)) {
      // Pull the set of extended types for the model
      $active = $this->definedTypes($coId, $attribute, 'list');
      
      $defaultTypes = array();
      
      foreach(array_keys($modelDefault) as $name) {
        // Walk through the default attribute types and insert any that don't
        // already exist into the extended types table.
        
        if(!isset($active[$name])) {
          $defaultTypes[] = array(
            'co_id' => $coId,
            'attribute' => $attribute,
            'name' => $name,
            'display_name' => $modelDefault[$name],
            'status' => SuspendableStatusEnum::Active
          );
        }
      }
      
      if(!empty($defaultTypes)) {
        if(!$this->saveMany($defaultTypes)) {
          throw new RuntimeException(_txt('er.db.save'));
        }
      }
    } else {
      throw new InvalidArgumentException(_txt('er.unknown', array($attr[1])));
    }
    
    return true;
  }
  
  /**
   * Add all default values for extended types for the specified CO.
   *
   * @since  COmanage Registry v0.9.2
   * @param  Integer CO ID
   * @return Boolean True on success
   * @throws RuntimeException
   */
  
  public function addDefaults($coId) {
    $attrs = $this->supportedAttrs();
    
    foreach(array_keys($attrs) as $t) {
      try {
        $this->addDefault($coId, $t);
      }
      catch(Exception $e) {
        throw new RuntimeException($e->getMessage());
      }
    }
    
    return true;
  }
  
  /**
   * Obtain a map of affiliations to eduPersonAffiliations.
   *
   * @since  COmanage Registry v0.9.2
   * @param  Integer $coId CO ID
   * @return Array Mapping from affiliation to eduPersonAffiliation
   */
  
  public function affiliationMap($coId) {
    $args = array();
    $args['conditions']['CoExtendedType.co_id'] = $coId;
    $args['conditions']['CoExtendedType.attribute'] = 'CoPersonRole.affiliation';
    $args['conditions']['CoExtendedType.status'] = SuspendableStatusEnum::Active;
    $args['fields'] = array('CoExtendedType.name', 'CoExtendedType.edupersonaffiliation');
    
    $ret = $this->find('list', $args);
    
    if(!empty($ret)) {
      global $cm_lang, $cm_texts;
      
      // Some mappings may be null. For those, if they are core edupersonaffiliations
      // set them to be themselves.
      
      foreach(array_keys($ret) as $a) {
        if($ret[$a] == null
           // Check to see if this is a core affiliation by looking in the language map
           && isset($cm_texts[ $cm_lang ]['en.co_person_role.affiliation'][$a])) {
          $ret[$a] = $a;
        }
      }
    }
    
    return $ret;
  }
  
  /**
   * Determine if there are any defined extended types for a specific attribute.
   *
   * @since  COmanage Registry v0.9.2
   * @param  Integer CO ID
   * @param  String Attribute, of the form Model.attribute
   * @param  String Format ('all' or 'list', as for Cake find, or 'keyed' for list keyed on ID)
   * @param  Boolean True if only active types should be returned
   * @return Array List of defined extended types, keyed on extended type ID
   */
  
  public function definedTypes($coId, $attribute, $format='list', $active=false) {
    $xformat = $format;
    
    $args = array();
    $args['conditions']['CoExtendedType.co_id'] = $coId;
    $args['conditions']['CoExtendedType.attribute'] = $attribute;
    if($active) {
      $args['conditions']['CoExtendedType.status'] = SuspendableStatusEnum::Active;
    }
    $args['order'][] = 'CoExtendedType.display_name';
    
    if($format == 'list') {
      $args['fields'] = array('CoExtendedType.name', 'CoExtendedType.display_name');
    } elseif($format == 'keyed') {
      $xformat = 'list';
      $args['fields'] = array('CoExtendedType.display_name');
    }
    
    return $this->find($xformat, $args);
  }
  
  /**
   * Assemble a list of supported attributes, suitable for use in generating a select button.
   *
   * @since  COmanage Registry 0.6
   * @return Array Hash of supported attributes, with model name as key and localized text as value
   */
  
  public function supportedAttrs() {
    $ret = array();
    
    // Also need to add to $validate, above
    $ret['Address.type'] = _txt('ct.addresses.1') . " (" . _txt('ct.co_person_roles.1') . ")";
    $ret['CoPersonRole.affiliation'] = _txt('fd.affiliation') . " (" . _txt('ct.co_person_roles.1') . ")";
    $ret['EmailAddress.type'] = _txt('ct.email_addresses.1') . " (" . _txt('ct.co_people.1') . ")";
    $ret['Identifier.type'] = _txt('ct.identifiers.1') . " (" . _txt('ct.co_people.1') . ")";
    $ret['Name.type'] = _txt('ct.names.1') . " (" . _txt('ct.co_people.1') . ")";
    $ret['TelephoneNumber.type'] = _txt('ct.telephone_numbers.1') . " (" . _txt('ct.co_person_roles.1') . ")";
    $ret['Url.type'] = _txt('ct.urls.1') . " (" . _txt('ct.co_people.1') . ")";
    
    return $ret;
  }
}
