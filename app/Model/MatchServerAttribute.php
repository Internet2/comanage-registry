<?php
/**
 * COmanage Registry Match Server Attribute Model
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
 * @package       registry
 * @since         COmanage Registry v4.0.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

class MatchServerAttribute extends AppModel {
  // Define class name for cake
  public $name = "MatchServerAttribute";
  
  // Current schema version for API
  public $version = "1.0";
  
  // Add behaviors
  public $actsAs = array('Containable');
  
  // Association rules from this model to other models
  public $belongsTo = array('MatchServer');
  
  // Default display field for cake generated views
  public $displayField = "attribute";
  
  // Validation rules for table elements
  public $validate = array(
    'match_server_id' => array(
      'rule' => array('numeric'),
      'required' => true,
      'allowEmpty' => false
    ),
    'attribute' => array(
      'rule' => 'notBlank',
      'required' => true,
      'allowEmpty' => false
    ),
    'type' => array(
      'rule' => 'notBlank',
      'required' => false,
      'allowEmpty' => true
    ),
    'required' => array(
      'rule' => array('range', -2, 2)
    )
  );
  
  /**
   * Obtain the CO ID for a record.
   *
   * @since  COmanage Registry v4.0.0
   * @param  integer Record to retrieve for
   * @return integer Corresponding CO ID, or NULL if record has no corresponding CO ID
   * @throws InvalidArgumentException
   * @throws RuntimeException
   */

  public function findCoForRecord($id) {
    // Override the parent version since we need to retrieve via the server config

    // First get the Match Server ID
    $msid = $this->field('match_server_id', array('MatchServerAttribute.id' => $id));

    if(!$msid) {
      throw new InvalidArgumentException(_txt('er.notfound', array('ct.match_server_attributes.1', $id)));
    }
    
    // Next get the Server
    $serverId = $this->MatchServer->field('server_id', array('MatchServer.id' => $msid));
    
    if(!$serverId) {
      throw new InvalidArgumentException(_txt('er.notfound', array('ct.match_servers.1', $id)));
    }

    $coId = $this->MatchServer->Server->field('co_id', array("Server.id" => $serverId));

    if($coId) {
      return $coId;
    } else {
      throw new InvalidArgumentException(_txt('er.notfound', array('ct.servers.1', $coef)));
    }
  }
  
  /**
   * Obtain the set of attributes supported for Match requests.
   *
   * @since  COmanage Registry v4.0.0
   * @return array Array of supported attributes
   */
  
  public function supportedAttributes() {
    return array(
      'dateOfBirth' => array(
        'label' => _txt('fd.date_of_birth'),
        'model' => 'OrgIdentity',
        // Note the use of singular "attribute", vs "attributes" for MVPAs
        'attribute' => 'date_of_birth',
        'type' => false,
        'wire' => 'dateOfBirth'
      ),
      'emailAddress' => array(
        'label' => _txt('fd.email_address.mail'),
        'model' => 'EmailAddress',
        // array values are "registry name" => "attribute dictionary name"
        'attributes' => array(
          'mail' => 'address'
        ),
        // type corresponds to CoExtendedType::attribute, and will automatically
        // be injected into the wire representation (ie: do not list it under
        // "attributes")
        'type' => "EmailAddress.type",
        'wire' => 'emailAddresses'
      ),
      'identifier' => array(
        'label' => _txt('fd.identifier.identifier'),
        'model' => 'Identifier',
        'attributes' => array(
          'identifier' => 'identifier'
        ),
        'type' => "Identifier.type",
        'wire' => 'identifiers'
      ),
      'name' => array(
        'label' => _txt('fd.name'),
        'model' => 'Name',
        'attributes' => array(
          'honorific' => 'prefix',
          'given' => 'given',
          'middle' => 'middle',
          'family' => 'family',
          'suffix' => 'suffix'
        ),
        'type' => "Name.type",
        'wire' => 'names'
      )
    );
  }
}
