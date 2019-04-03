<?php
/**
 * COmanage Registry CO Announcement Channel Model
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
 * @since         COmanage Registry v3.2.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

class CoAnnouncementChannel extends AppModel {
  // Define class name for cake
  public $name = "CoAnnouncementChannel";
  
  // Current schema version for API
  public $version = "1.0";
  
  // Association rules from this model to other models
  public $belongsTo = array(
    "Co",
    "AuthorCoGroup" => array(
      'className' => 'CoGroup',
      'foreignKey' => 'author_co_group_id'
    ),
    "ReaderCoGroup" => array(
      'className' => 'CoGroup',
      'foreignKey' => 'reader_co_group_id'
    )
  );
  
  public $hasMany = array(
    "CoAnnouncement" => array('dependent' => true),
    "CoAnnouncementsWidget" => array('dependent' => true)
  );
  
  // Default display field for cake generated views
  public $displayField = "name";
  
  public $actsAs = array('Containable',
                         'Changelog' => array('priority' => 5));
  
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
    'status' => array(
      'rule' => array('inList', array(SuspendableStatusEnum::Active,
                                      SuspendableStatusEnum::Suspended)),
      'required' => true,
      'allowEmpty' => false
    ),
    'author_co_group_id' => array(
      'rule' => 'numeric',
      'required' => true,
      'allowEmpty' => false
    ),
    'reader_co_group_id' => array(
      'rule' => 'numeric',
      'required' => false,
      'allowEmpty' => true
    ),
    'register_notifications' => array(
      'rule' => 'boolean',
      'required' => true,
      'allowEmpty' => false
    ),
    'publish_html' => array(
      'rule' => 'boolean',
      'required' => true,
      'allowEmpty' => false
    )
  );
  
  /**
   * Perform a keyword search.
   *
   * @since  COmanage Registry v3.2.0
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
          'LOWER(CoAnnouncementChannel.name) LIKE' => '%' . strtolower($t) . '%',
        )
      );
    }

    $args['conditions']['CoAnnouncementChannel.co_id'] = $coId;
    $args['order'] = array('CoAnnouncementChannel.name');
    $args['contain'] = false;

    return $this->find('all', $args);
  }
}
