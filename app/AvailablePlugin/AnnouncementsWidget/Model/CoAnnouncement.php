<?php
/**
 * COmanage Registry CO Announcement Model
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

class CoAnnouncement extends AppModel {
  // Define class name for cake
  public $name = "CoAnnouncement";
  
  // Current schema version for API
  public $version = "1.0";
  
  // Association rules from this model to other models
  public $belongsTo = array(
    "CoAnnouncementChannel",
    "PosterCoPerson" => array(
      'className' => 'CoPerson',
      'foreignKey' => 'poster_co_person_id'
    )
  );
  
  // Default display field for cake generated views
  public $displayField = "title";
  
  public $actsAs = array('Containable',
                         'Changelog' => array('priority' => 5));
  
  // Validation rules for table elements
  public $validate = array(
    'co_announcement_channel_id' => array(
      'rule' => 'numeric',
      'required' => true,
      'allowEmpty' => false
    ),
    'poster_co_person_id' => array(
      'rule' => 'numeric',
      // Not required because it may be automatically set (and thus fail initial
      // validation) or an announcement could be added by a CMP admin (who has no
      // CO Person ID)
      'required' => false,
      'allowEmpty' => true
    ),
    'title' => array(
      'rule' => array('validateInput'),
      'required' => true,
      'allowEmpty' => false
    ),
    'body' => array(
      'rule' => 'notBlank',
      'required' => true,
      'allowEmpty' => false
    ),
    'valid_from' => array(
      'content' => array(
        'rule' => array('validateTimestamp'),
        'required' => false,
        'allowEmpty' => true
      )
    ),
    'valid_through' => array(
      'content' => array(
        'rule' => array('validateTimestamp'),
        'required' => false,
        'allowEmpty' => true
      )
    )
  );

  /**
   * Actions to take after a save operation is executed.
   *
   * @since  COmanage Registry v3.2.0
   * @param  boolean $created True if a new record was created (rather than update)
   * @param  array   $options As passed into Model::save()
   */

  public function afterSave($created, $options = array()) {
    // Is the channel configured for Notifications? If so, register them here.
    
    $args = array();
    $args['conditions']['CoAnnouncementChannel.id'] = $this->data['CoAnnouncement']['co_announcement_channel_id'];
    $args['contain'] = false;
    
    $channel = $this->CoAnnouncementChannel->find('first', $args);
    
    if(isset($channel['CoAnnouncementChannel']['register_notifications'])
       && $channel['CoAnnouncementChannel']['register_notifications']
       // There must be a reader group (we won't/can't send notifications to public channels)
       && !empty($channel['CoAnnouncementChannel']['reader_co_group_id'])) {
      // For now at least, we register notifications on $created or updated.
      
      $CoNotification = ClassRegistry::init('CoNotification');
      $CoNotification->register(
        null,
        $channel['CoAnnouncementChannel']['reader_co_group_id'],
        $this->data['CoAnnouncement']['poster_co_person_id'],
        'cogroup',
        $channel['CoAnnouncementChannel']['reader_co_group_id'],
        AnnouncementsActionEnum::AnnouncementAdded,
        ($created ? _txt('pl.announcementswidget.nt.add') : _txt('pl.announcementswidget.nt.edit')),
        array(
          'plugin'     => 'announcements_widget',
          'controller' => 'co_announcements',
          'action'     => 'view',
          $this->data['CoAnnouncement']['id']
        ),
        false,
        null, // XXX option to set from address?
        $this->data['CoAnnouncement']['title'],
        $this->data['CoAnnouncement']['body']
      );
    }
  }
  
  /**
   * Actions to take before a save operation is executed.
   *
   * @since  COmanage Registry v3.2.0
   */

  public function beforeSave($options = array()) {
    // Possibly convert the requested timestamps to UTC from browser time.

    if($this->tz) {
      $localTZ = new DateTimeZone($this->tz);

      if(!empty($this->data[$this->alias]['valid_from'])) {
        // This returns a DateTime object adjusting for localTZ
        $offsetDT = new DateTime($this->data[$this->alias]['valid_from'], $localTZ);

        // strftime converts a timestamp according to server localtime (which should be UTC)
        $this->data[$this->alias]['valid_from'] = strftime("%F %T", $offsetDT->getTimestamp());
      }

      if(!empty($this->data[$this->alias]['valid_through'])) {
        // This returns a DateTime object adjusting for localTZ
        $offsetDT = new DateTime($this->data[$this->alias]['valid_through'], $localTZ);

        // strftime converts a timestamp according to server localtime (which should be UTC)
        $this->data[$this->alias]['valid_through'] = strftime("%F %T", $offsetDT->getTimestamp());
      }
    }
  }
  
  /**
   * Obtain the CO ID for a record, overriding AppModel behavior.
   *
   * @since  COmanage Registry v3.2.0
   * @param  integer Record to retrieve for
   * @return integer Corresponding CO ID, or NULL if record has no corresponding CO ID
   * @throws InvalidArgumentException
   * @throws RuntimeException
   */

  public function findCoForRecord($id) {
    // CoAnnouncements get their CO via the CoAnnouncementChannel

    $args = array();
    $args['conditions'][$this->alias.'.id'] = $id;
    $args['contain'][] = 'CoAnnouncementChannel';

    $ann = $this->find('first', $args);
    
    if(!empty($ann['CoAnnouncementChannel']['co_id'])) {
      return $ann['CoAnnouncementChannel']['co_id'];
    } else {
      return parent::findCoForRecord($id);
    }
  }
  
  /**
   * Perform a keyword search.
   *
   * @since  COmanage Registry v3.2.0
   * @param  integer $coId  CO ID to constrain search to
   * @param  string  $q     String to search for
   * @param  integer $limit Search limit
   * @return Array Array of search results, as from find('all)
   */

  public function search($coId, $q, $limit) {
    // Make sure CoAnnouncementChannel is loaded
    ClassRegistry::init('AnnouncementsWidget.CoAnnouncementChannel');
    
    // Tokenize $q on spaces
    $tokens = explode(" ", $q);

    $args = array();
    $args['joins'][1]['table'] = 'co_announcement_channels';
    $args['joins'][1]['alias'] = 'CoAnnouncementChannel';
    $args['joins'][1]['type'] = 'INNER';
    $args['joins'][1]['conditions'][0] = 'CoAnnouncement.co_announcement_channel_id=CoAnnouncementChannel.id';
    
    foreach($tokens as $t) {
      $args['conditions']['AND'][] = array(
        'OR' => array(
          'LOWER(CoAnnouncement.title) LIKE' => '%' . strtolower($t) . '%',
          'LOWER(CoAnnouncement.body) LIKE' => '%' . strtolower($t) . '%'
        )
      );
    }

    $args['conditions']['CoAnnouncementChannel.co_id'] = $coId;
    $args['order'] = array('CoAnnouncement.created');
    $args['limit'] = $limit;
    $args['contain'] = false;

    return $this->find('all', $args);
  }
}
