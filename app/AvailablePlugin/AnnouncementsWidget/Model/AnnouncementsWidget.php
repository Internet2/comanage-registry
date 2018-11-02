<?php
/**
 * COmanage Registry Announcements Widget Model
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
 * @since         COmanage Registry v3.2.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

class AnnouncementsWidget extends AppModel {
  // Define class name for cake
  public $name = "AnnouncementsWidget";

  // Required by COmanage Plugins
  public $cmPluginType = "dashboardwidget";
	
	// Add behaviors
//  public $actsAs = array('Containable');
	
  // Document foreign keys
  public $cmPluginHasMany = array(
    "Co" => array("CoAnnouncementChannel"),
    "CoPerson" => array(
      "CoAnnouncementPosterCoPerson" => array(
        'className'  => 'CoAnnouncement',
        'foreignKey' => 'poster_co_person_id'
      )
    ),
    "CoGroup" => array(
      "CoAnnouncementChannelAuthorCoGroup" => array(
        'className'  => 'CoAnnouncementChannel',
        'foreignKey' => 'author_co_group_id'
      ),
      "CoAnnouncementChannelReaderCoGroup" => array(
        'className'  => 'CoAnnouncementChannel',
        'foreignKey' => 'reader_co_group_id'
      )
    )
	);
	
	// Association rules from this model to other models
	public $belongsTo = array(
	);
	
	public $hasMany = array(
	);
	
  // Default display field for cake generated views
//  public $displayField = "description";
	
  // Validation rules for table elements
  public $validate = array(
	);
  
  /**
   * Expose menu items.
   * 
   * @since  COmanage Registry v3.2.0
   * @return Array with menu location type as key and array of labels, controllers, actions as values.
   */
	
  public function cmPluginMenus() {
  	return array(
      "coconfig" => array(_txt('ct.co_announcement_channels.pl') =>
        array('icon'       => 'label',
              'controller' => 'co_announcement_channels',
              'action'     => 'index')),
      "comain" => array(_txt('ct.co_announcements.pl') =>
        array('icon'       => 'announcement',
              'controller' => 'co_announcements',
              'action'     => 'index',
              'sort'       => 'CoAnnouncement.created',
              'direction'  => 'desc',
              'filter'     => 'active'))
    );
  }
  
  /**
   * Declare searchable models.
   *
   * @since  COmanage Registry v3.2.0
   * @return Array Array of searchable models
   */
  
  public function cmPluginSearchModels() {
    return array(
      'AnnouncementsWidget.CoAnnouncement' => array(
        'displayField' => 'title',
        'permissions' => array('cmadmin', 'coadmin', 'couadmin', 'comember')
      ),
      'AnnouncementsWidget.CoAnnouncementChannel' =>  array(
        'displayField' => 'name',
        'permissions' => array('cmadmin', 'coadmin', 'couadmin')
      )
    );
  }
}
