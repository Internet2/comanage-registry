<?php
/**
 * COmanage Registry Sponsor Manager Plugin Language File
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
 * @package       registry-plugin
 * @since         COmanage Registry v4.1.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */
  
global $cm_lang, $cm_texts;

// When localizing, the number in format specifications (eg: %1$s) indicates the argument
// position as passed to _txt.  This can be used to process the arguments in
// a different order than they were passed.

$cm_sponsor_manager_texts['en_US'] = array(
  // Titles, per-controller
  'ct.sponsor_manager_settings.1'   => 'Sponsor Manager Setting',
  'ct.sponsor_manager_settings.pl'  => 'Sponsor Manager Settings',
  
  // Enumerations
  'en.sponsormanager.review_filter' => array(
    ReviewFilterEnum::Default   => 'Default',
    ReviewFilterEnum::All       => 'All Sponsored Roles',
    ReviewFilterEnum::Expired   => 'All Expired Sponsored Roles',
    ReviewFilterEnum::Upcoming  => 'Roles With Upcoming Expirations'
  ),
  
  // Error messages
  'er.sponsormanager.petition'          => 'Associated Petition is not in cancelable status',
  'er.sponsormanager.renewal_window'    => 'The requested role is not within the renewal window',
  
  // Plugin texts
  'pl.sponsormanager.cancel'            => 'Cancel',
  'pl.sponsormanager.cancel.confirm'    => 'Are you sure you want to immediately terminate the enrollment for this role for %1$s?',
  'pl.sponsormanager.canceled'          => 'Role Enrollment Canceled',
  'pl.sponsormanager.email_type'        => 'Email Address Type',
  'pl.sponsormanager.email_type.desc'   => 'Email Address Type to display in review list',
  'pl.sponsormanager.expire'            => 'Expire',
  'pl.sponsormanager.expired'           => 'Expired',
  'pl.sponsormanager.expire.confirm'    => 'Are you sure you want to immediately expire this role for %1$s?',
  'pl.sponsormanager.identifier_type'   => 'Identifier Type',
  'pl.sponsormanager.identifier_type.desc'  => 'Identifier Type to display in review list',
  'pl.sponsormanager.lookahead_window'      => 'Expiration Look Ahead Window',
  'pl.sponsormanager.lookahead_window.desc' => 'CO Person Roles expiring within this many days (as well as those already expired) will show by default',
  'pl.sponsormanager.renew'             => 'Renew',
  'pl.sponsormanager.renew.confirm'     => 'Are you sure you want to immediately renew this role for %1$s?',
  'pl.sponsormanager.renewal_term'      => 'Renewal Term',
  'pl.sponsormanager.renewal_term.desc' => 'Number of days renewed CO Person Roles will be valid from (calculated from date of renewal)',
  'pl.sponsormanager.renewal_window'    => 'Renewal Availability Window',
  'pl.sponsormanager.renewal_window.desc' => 'CO Person Roles will be available for renewal starting this many days before expiration',
  'pl.sponsormanager.renewal_window.info' => 'Only roles with expiration dates of %1$s or earlier are eligible for renewal',
  'pl.sponsormanager.renewed'           => 'Role renewed through %1$s UTC',
  'pl.sponsormanager.show_affiliation'  => 'Show Affiliation',
  'pl.sponsormanager.show_cou'          => 'Show COU',
  'pl.sponsormanager.show_o'            => 'Show Organization',
  'pl.sponsormanager.show_title'        => 'Show Title',
  'pl.sponsormanager.sponsor'           => 'Roles Sponsored By %1$s',
  'pl.sponsormanager.view'              => 'View Sponsored Roles'
);
