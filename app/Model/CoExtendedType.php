<?php
/**
 * COmanage Registry CO Extended Type Model
 *
 * Copyright (C) 2012 University Corporation for Advanced Internet Development, Inc.
 * 
 * Licensed under the Apache License, Version 2.0 (the "License"); you may not use this file except in compliance with
 * the License. You may obtain a copy of the License at
 * 
 * http://www.apache.org/licenses/LICENSE-2.0
 * 
 * Unless required by applicable law or agreed to in writing, software distributed under
 * the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY
 * KIND, either express or implied. See the License for the specific language governing
 * permissions and limitations under the License.
 *
 * @copyright     Copyright (C) 2012 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.6
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */
  
class CoExtendedType extends AppModel {
  // Define class name for cake
  public $name = "CoExtendedType";
  
  // Current schema version for API
  public $version = "1.0";
  
  // Association rules from this model to other models
  public $belongsTo = array("Co");
  
  // Default display field for cake generated views
  public $displayField = "display_name";
  
  // Validation rules for table elements
  public $validate = array(
    'co_id' => array(
      'rule' => 'numeric',
      'required' => true,
      'message' => 'A CO ID must be provided'
    ),
    'attribute' => array(
      'rule' => array('inList', array('Identifier')),
      'required' => true,
      'message' => 'A supported attribute type must be provided'
    ),
    'name' => array(
      'rule' => 'alphaNumeric',
      'required' => true,
      'message' => 'A name must be provided and consist of alphanumeric characters'
    ),
    'display_name' => array(
      'rule' => 'notEmpty',
      'required' => true,
      'message' => 'A name must be provided'
    ),
    'status' => array(
      'rule' => array('inList', array(StatusEnum::Active,
                                      StatusEnum::Deleted,
                                      StatusEnum::Suspended)),
      'required' => true,
      'message' => 'A valid status must be selected'
    )
  );
  
  // Enum type hints
  
  public $cm_enum_types = array(
    'status' => 'status_t'
  );
  
  /**
   * Determine if there are any defined, active extended types for a specific attribute.
   *
   * @since  COmanage Registry v0.6
   * @param  Integer CO ID
   * @param  String Attribute
   * @param  String Format ('all' or 'list', as for Cake find)
   * @return Array List of defined extended types, keyed on extended type ID
   */
  
  public function active($coId, $attribute, $format='list') {
    $args = array();
    $args['conditions']['CoExtendedType.co_id'] = $coId;
    $args['conditions']['CoExtendedType.attribute'] = $attribute;
    $args['conditions']['CoExtendedType.status'] = StatusEnum::Active;
    $args['order'][] = 'CoExtendedType.display_name';
    
    if($format == 'list') {
      $args['fields'] = array('CoExtendedType.name', 'CoExtendedType.display_name');
    }
    
    return $this->find($format, $args);
  }
  
  /**
   * Determine if all default types are explicitly defined as extended types for a specific attribute.
   *
   * @since  COmanage Registry v0.6
   * @param  Integer CO ID
   * @param  String Attribute
   * @return Boolean Success
   */
  
  public function addDefault($coId, $attribute) {
    // We need the appropriate model for $attribute to manipulate the default types
    $model = ClassRegistry::init($attribute);
    
    $modelDefault = $model->defaultTypes();
    
    if(!empty($modelDefault)) {
      $defaultTypes = array();
      
      foreach(array_keys($modelDefault) as $name) {
        // build an array and SaveAll
        
        $defaultTypes[] = array(
          'co_id' => $coId,
          'attribute' => $attribute,
          'name' => $name,
          'display_name' => $modelDefault[$name],
          'status' => StatusEnum::Active
        );
      }
      
      return $this->saveMany($defaultTypes);
    }
    
    return false;
  }
  
  /**
   * Determine if there are any defined, active extended types for a specific attribute.
   *
   * @since  COmanage Registry v0.6
   * @param  Integer CO ID
   * @param  String Attribute
   * @param  Boolean Whether to restrict query to active extended types only
   * @return Boolean Whether or not there are any defined extended types
   */
  
  public function anyDefined($coId, $attribute, $active=true) {
    $args = array();
    $args['conditions']['CoExtendedType.co_id'] = $coId;
    $args['conditions']['CoExtendedType.attribute'] = $attribute;
    
    if($active) {
      $args['conditions']['CoExtendedType.status'] = StatusEnum::Active;
    }
    
    return (boolean)$this->find('count', $args);
  }
  
  /**
   * Assemble a list of supported attributes, suitable for use in generating a select button.
   *
   * @since  COmanage Registry 0.6
   * @return Array Hash of supported attributes, with model name as key and localized text as value
   */
  
  public function supportedAttrs() {
    $ret = array();
    $ret['Identifier'] = _txt('ct.identifiers.1');
    
    return $ret;
  }
}
