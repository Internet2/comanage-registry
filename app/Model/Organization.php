<?php
/**
 * COmanage Registry Organization Model
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
 * @since         COmanage Registry v3.1.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */
  
class Organization extends AppModel {
  // Define class name for cake
  public $name = "Organization";
  
  // Current schema version for API
  public $version = "1.0";
  
  // Add behaviors
  public $actsAs = array('Containable',
                         'Changelog' => array('priority' => 5));
  
  // Association rules from this model to other models
  public $belongsTo = array(
    "Co"
  );
  
  public $hasOne = array(
    "OrganizationSourceRecord" => array('dependent' => true)
  );
  
  public $hasMany = array(
    "Address" => array('dependent' => true),
    "AdHocAttribute" => array('dependent' => true),
    "Contact" => array('dependent' => true),
    "EmailAddress" => array('dependent' => true),
    "Identifier" => array('dependent' => true),
    "TelephoneNumber" => array('dependent' => true),
    "Url" => array('dependent' => true)
  );
  
  // Default display field for cake generated views
  public $displayField = "name";
  
  // Validation rules for table elements
  public $validate = array(
    'co_id' => array(
      'rule' => 'numeric',
      'required' => true,
      'allowEmpty' => false
    ),
    'name' => array(
      'rule' => array('validateInput'),
      'required' => true,
      'allowEmpty' => false
    ),
    'description' => array(
      'rule' => array('validateInput'),
      'required' => false,
      'allowEmpty' => true
    ),
    'type' => array(
      'content' => array(
        'rule' => array('validateExtendedType',
          array('attribute' => 'Organization.type',
                'default' => array(OrganizationEnum::Academic,
                                   OrganizationEnum::Commercial,
                                   OrganizationEnum::Government))),
        'required' => false,
        'allowEmpty' => true
      )
    ),
    'saml_scope' => array(
      'rule' => array('validateInput'),
      'required' => false,
      'allowEmpty' => true
    ),
    'logo_url' => array(
      'content' => array(
        'rule' => array('url', true),
        'required' => false,
        'allowEmpty' => true,
      ),
      'filter' => array(
        'rule' => array('validateInput',
                        array('filter' => FILTER_SANITIZE_URL))
      )
    )
  );

  /**
   * Lookup an Organization based on an Identifier associated with it.
   * 
   * @since  COmanage Registry v4.4.0
   * @param  integer $coId        CO ID
   * @param  string  $identifier  Identifier (currently all types are searched)
   * @return array                Array, as returned by find
   */

  public function lookupByIdentifier($coId, $identifier) {
    $args = array();
    $args['conditions']['Organization.co_id'] = $coId;
    $args['conditions']['Identifier.identifier'] = $identifier;
    $args['conditions']['Identifier.status'] = SuspendableStatusEnum::Active;
    $args['conditions']['Identifier.deleted'] = false;
    $args['conditions']['Identifier.identifier_id'] = null;
    $args['joins'][0]['table'] = 'identifiers';
    $args['joins'][0]['alias'] = 'Identifier';
    $args['joins'][0]['type'] = 'INNER';
    $args['joins'][0]['conditions'][0] = 'Identifier.organization_id=Organization.id';
    $args['contain'] = false;

    return $this->find('all', $args);
  }
  
  /**
   * Perform a keyword search.
   *
   * @since  COmanage Registry v4.0.0
   * @param  Integer $coId CO ID to constrain search to
   * @param  String  $q    String to search for
   * @return Array Array of search results, as from find('all)
   */
  
  public function search($coId, $q) {
    // Tokenize $q on spaces
    $tokens = explode(" ", $q);
    
    $args = array();
    foreach($tokens as $t) {
      $args['conditions']['AND'][] = array(
        'OR' => array(
          'LOWER(Organization.name) LIKE' => '%' . strtolower($t) . '%'
        )
      );
    }
    $args['conditions']['Organization.co_id'] = $coId;
    $args['order'] = array('Organization.name');
    $args['contain'] = false;

    return $this->find('all', $args);
  }
}
