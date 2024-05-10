<?php

/**
 * COmanage Registry Orcid Token Model
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
 * @since         COmanage Registry v4.4.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */
class OrcidToken extends AppModel
{
  // Add behaviors
  public $actsAs = array(
    'Containable'
  );

  // Association rules from this model to other models
  public $belongsTo = array(
    'OrcidSource',
  );

  // Default display field for cake generated views
  public $displayField = 'orcid_identifier';

  /**
   * Actions to take before a validate operation is executed.
   *
   * @since  COmanage Registry v0.9.1
   */

  public function beforeValidate($options = array()) {
//    if(!empty($this->data["OrcidToken"]["scopes"])) {
//      $this->data["OrcidToken"]["scopes"] = str_replace(" ",",",trim($this->data["OrcidToken"]["scopes"]));
//    }
    //Encrypt key here in case validation failed to have the encrypted key and beforeRender function work properly
    $key = Configure::read('Security.salt');
    Configure::write('Security.useOpenSsl', true);
    foreach(array(
      'id_token',
      'access_token',
      'refresh_token'
            ) as $column) {
      if(!empty($this->data['OrcidToken'][$column])) {
        $payload = base64_encode(Security::encrypt($this->data['OrcidToken'][$column], $key));
        $stored_key = ($this->id !== null) ? $this->field($column, ['id' => $this->id]) : '';
        if($stored_key !== $payload) {
          $this->data['OrcidToken'][$column] = $payload;
        }
      }
    }
  }

  // Validation rules for table elements
  public $validate = array(
    'orcid_source_id' => array(
      'content' => array(
        'rule'       => 'numeric',
        'required'   => true,
        'allowEmpty' => false
      )
    ),
    'orcid_identifier'    => array(
      'content' => array(
        'rule'       => 'alphaNumeric',
        'required'   => true,
        'allowEmpty' => false
      )
    ),
    'access_token'    => array(
      'content' => array(
        'rule'       => 'notBlank',
        'required'   => true,
        'allowEmpty' => false
      )
    ),
    'id_token'    => array(
      'content' => array(
        'rule'       => 'notBlank',
        'required'   => false,
        'allowEmpty' => true
      )
    ),
    'refresh_token'    => array(
      'content' => array(
        'rule'       => 'notBlank',
        'required'   => false,
        'allowEmpty' => true
      )
    ),
  );
}