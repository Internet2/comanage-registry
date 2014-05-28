<?php
/**
 * COmanage Registry CO Self Service Permission Model
 *
 * Copyright (C) 2014 University Corporation for Advanced Internet Development, Inc.
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
 * @copyright     Copyright (C) 2014 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.9
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */
  
class CoSelfServicePermission extends AppModel {
  // Define class name for cake
  public $name = "CoSelfServicePermission";
  
  // Current schema version for API
  public $version = "1.0";
  
  // Add behaviors
  public $actsAs = array('Containable');
  
  // Association rules from this model to other models
  public $belongsTo = array(
    "Co"
  );
  
  // Default display field for cake generated views
  public $displayField = "field";
  
  // Validation rules for table elements
  public $validate = array(
    'co_id' => array(
      'rule' => 'numeric',
      'required' => true,
      'allowEmpty' => false
    ),
    'model' => array(
      'rule' => 'notEmpty',
      'required' => true,
      'allowEmpty' => false
    ),
    'type' => array(
      'rule' => 'notEmpty',
      'required' => false,
      'allowEmpty' => true
    ),
    'permission' => array(
      'rule' => array('inList',
                      array(
                        PermissionEnum::None,
                        PermissionEnum::ReadOnly,
                        PermissionEnum::ReadWrite
                      )),
      'required' => true,
      'allowEmpty' => false
    )
  );
  
  /**
   * Assemble a list of supported models, suitable for use in generating a select.
   *
   * @since  COmanage Registry 0.9
   * @return Array Two hashes: 'models' with model name as key and localized text as value
   *                           'types' with model name as key and another array as value,
   *                            holding type name as key and localized text as value
   */
  
  public function supportedAttrs() {
    $ret = array();
    
    $ret['models'] = array(
      'Address'         => _txt('ct.addresses.1'),
      'EmailAddress'    => _txt('ct.email_addresses.1'),
      'Name'            => _txt('ct.names.1'),
      'TelephoneNumber' => _txt('ct.telephone_numbers.1')
    );
    
    // So we don't need to synchronize the valid types, we'll dynamically construct
    // them from the model validation rules.
    
    foreach(array_keys($ret['models']) as $m) {
      $model = ClassRegistry::init($m);
      
      foreach($model->validate['type']['content']['rule'][1] as $t) {
        $ret['types'][$m][$t] = _txt($model->cm_enum_lang['type'], null, $t);
      }
    }
    
    return $ret;
  }
}
