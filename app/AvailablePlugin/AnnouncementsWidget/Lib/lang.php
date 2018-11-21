<?php
/**
 * COmanage Registry Announcements Widget Plugin Language File
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
  
global $cm_lang, $cm_texts;

// When localizing, the number in format specifications (eg: %1$s) indicates the argument
// position as passed to _txt.  This can be used to process the arguments in
// a different order than they were passed.

$cm_announcements_widget_texts['en_US'] = array(
  // Titles, per-controller
  'ct.co_announcement_channels.1'  => 'Announcement Channel',
  'ct.co_announcement_channels.pl' => 'Announcement Channels',
  'ct.co_announcements.1'  => 'Announcement',
  'ct.co_announcements.pl' => 'Announcements',
  'ct.co_announcements_widgets.1'  => 'Announcements Widget',
  'ct.co_announcements_widgets.pl' => 'Announcements Widgets',
  
  // Error messages
//  'er.announcementswidget.foobar'        => 'Some error here',
  
  // Plugin texts
  'pl.announcementswidget.author'      => 'Author Group',
  'pl.announcementswidget.author.desc' => 'Members of this group can add announcements to this channel',
  'pl.announcementswidget.body'        => 'Message Body',
  'pl.announcementswidget.none'        => 'No announcements',
  'pl.announcementswidget.nt.add'      => 'Announcement Posted',
  'pl.announcementswidget.nt.edit'     => 'Announcement Updated',
  'pl.announcementswidget.notify'      => 'Send Notifications',
  'pl.announcementswidget.notify.desc' => 'Register notifications for announcements in this channel (sent to the members of the Reader Group)',
  'pl.announcementswidget.posted'      => 'Posted',
  'pl.announcementswidget.postedby'    => 'Posted By',
  'pl.announcementswidget.publish_html' => 'Publish HTML',
  'pl.announcementswidget.publish_html.desc' => 'If not enabled, HTML tags will be stripped when rendering announcements in this channel.<br />ENABLE ONLY FOR TRUSTED AUTHOR GROUPS.',
  'pl.announcementswidget.reader'      => 'Reader Group',
  'pl.announcementswidget.reader.desc' => 'Members of this group can view announcements in this channel, leave blank for public visibility',
  'pl.announcementswidget.view_all'         => 'View All Announcements',
);
