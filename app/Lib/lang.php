<?php
/**
 * COmanage Registry Language File
 *
 * Copyright (C) 2011-14 University Corporation for Advanced Internet Development, Inc.
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
 * @copyright     Copyright (C) 2011-14 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.1
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */
  
global $cm_lang, $cm_texts, $cm_texts_orig;

// XXX move this to a master config
$cm_lang = "en_US";

// When localizing, the number in format specifications (eg: %1$s) indicates the argument
// position as passed to _txt.  This can be used to process the arguments in
// a different order than they were passed.

$cm_texts['en_US'] = array(
  // Application name
  'coordinate' =>     'COmanage Registry',
  
  // What a CO is called (abbreviated)
  'co' =>             'CO',
  'cos' =>            'COs',
  
  // What an Org is called
  'org' =>            'Organization',
  
  // API User texts
  'ap.note.privs' =>  'API Users are currently given full privileges to all Registry data. This is subject to change in a future release (<a href="https://bugs.internet2.edu/jira/browse/CO-91">CO-91</a>).',
  'ap.note.username' => 'The API username selected here cannot conflict with any identifier used by anyone to login to the platform',
  
  // Authnz
  'au.not' =>         'Not Logged In',
  
  // COs Controllers
  'co.cm.gradmin' =>  'COmanage Platform Administrators',
  'co.cm.desc' =>     'COmanage Registry Internal CO',
  'co.init' =>        'No COs found, initial CO created',
  'co.nomember' =>    'You are not a member of any COs',
  'co.select' =>      'Select the CO you wish to work with.',
  
  // Titles, per-controller
  'ct.addresses.1' =>           'Address',
  'ct.addresses.pl' =>          'Addresses',
  'ct.api_users.1' =>           'API User',
  'ct.api_users.pl' =>          'API Users',
  'ct.cmp_enrollment_configurations.1'  => 'CMP Enrollment Configuration',
  'ct.cmp_enrollment_configurations.pl' => 'CMP Enrollment Configurations',
  'ct.co_enrollment_attributes.1'  => 'CO Enrollment Attribute',
  'ct.co_enrollment_attributes.pl' => 'CO Enrollment Attributes',
  'ct.co_enrollment_flows.1'  => 'CO Enrollment Flow',
  'ct.co_enrollment_flows.pl' => 'CO Enrollment Flows',
  'ct.co_extended_attributes.1'  => 'Extended Attribute',
  'ct.co_extended_attributes.pl' => 'Extended Attributes',
  'ct.co_extended_types.1'  => 'Extended Type',
  'ct.co_extended_types.pl' => 'Extended Types',
  'ct.co_identifier_assignments.1' => 'Identifier Assignment',
  'ct.co_identifier_assignments.pl' => 'Identifier Assignments',
  'ct.co_group_members.1' =>    'Group Member',
  'ct.co_group_members.pl' =>   'Group Members',
  'ct.co_groups.1' =>           'Group',
  'ct.co_groups.pl' =>          'Groups',
  'ct.co_invites.1' =>          'Invite',
  'ct.co_localizations.1' =>    'Localization',
  'ct.co_localizations.pl' =>   'Localizations',
  'ct.co_navigation_links.1' => 'Navigation Link',
  'ct.co_navigation_links.pl' => 'Navigation Links',
  'ct.co_notifications.1' =>    'Notification',
  'ct.co_notifications.pl' =>   'Notifications',
  'ct.co_invites.pl' =>         'Invites',
  'ct.co_nsf_demographics.1'  => 'NSF Demographic Record',
  'ct.co_nsf_demographics.pl' => 'NSF Demographic Records',
  'ct.co_people.1' =>           'CO Person',
  'ct.co_people.pl' =>          'CO People',
  'ct.co_people.se' =>          'CO People Search',
  'ct.co_person_roles.1' =>     'CO Person Role',
  'ct.co_person_roles.pl' =>    'CO Person Roles',
  'ct.co_petitions.1' =>        'CO Petition',
  'ct.co_petitions.pl' =>       'CO Petitions',
  'ct.co_provisioning_targets.1'  => 'Provisioning Target',
  'ct.co_provisioning_targets.pl' => 'Provisioning Targets',
  'ct.co_terms_and_conditions.1'  => 'Terms and Conditions',
  'ct.co_terms_and_conditions.pl' => 'Terms and Conditions',
  'ct.cos.1' =>                 'CO',
  'ct.cos.pl' =>                'COs',
  'ct.cous.1' =>                'COU',
  'ct.cous.pl' =>               'COUs',
  'ct.email_addresses.1' =>     'Email Address',
  'ct.email_addresses.pl' =>    'Email Addresses',
  'ct.history_records.1' =>     'History Record',
  'ct.history_records.pl' =>    'History Records',
  'ct.identifiers.1' =>         'Identifier',
  'ct.identifiers.pl' =>        'Identifiers',
  'ct.names.1' =>               'Name',
  'ct.names.pl' =>              'Names',
  'ct.navigation_links.1' =>    'Navigation Link',
  'ct.navigation_links.pl' =>   'Navigation Links',
  'ct.org_identities.1' =>      'Organizational Identity',
  'ct.org_identities.se' =>     'Organizational Identity Search',
  'ct.org_identities.pl' =>     'Organizational Identities',
  'ct.organizations.1' =>       'Organization',
  'ct.organizations.pl' =>      'Organizations',
  'ct.telephone_numbers.1' =>   'Telephone Number',
  'ct.telephone_numbers.pl' =>  'Telephone Numbers',
  
  // Email Messages
  'em.approval.subject.ef'   => 'Petition to join (@CO_NAME) has been approved',
  'em.approval.body.ef'      => 'Your petition to join (@CO_NAME) as been approved. You may now log in to the platform.',
  'em.invite.subject'        => 'Invitation to join %1$s',
  'em.invite.subject.ef'     => 'Invitation to join (@CO_NAME)',
  'em.invite.subject.ver'    => 'Please confirm your email address (@CO_NAME)',
  'em.invite.body'           => 'You have been invited to join %1$s.  Please click the link below to accept or decline.',
  'em.invite.body.ef'        => 'You have been invited to join (@CO_NAME).
Please click the link below to accept or decline.

(@INVITE_URL)',
  'em.invite.body.ver'       => 'You or an administrator for @CO_NAME has added or updated an email address.
Please click the link below to confirm that this is a valid request.

(@INVITE_URL)

For questions regarding this process, please contact your administrator.',
  'em.invite.ok'             => 'Invitation has been emailed to %1$s',
  'em.invite.footer'         => 'This email was sent using %1$s.',

  // Enumerations, corresponding to enum.php
  // Default history comments
  'en.action' =>   array(
    ActionEnum::CoGroupMemberAdded          => 'CO Group Member Added',
    ActionEnum::CoGroupMemberEdited         => 'CO Group Member Edited',
    ActionEnum::CoGroupMemberDeleted        => 'CO Group Member Deleted',
    ActionEnum::CoPersonAddedManual         => 'CO Person Created (Manual)',
    ActionEnum::CoPersonAddedPetition       => 'CO Person Created (Petition)',
    ActionEnum::CoPersonEditedManual        => 'CO Person Edited',
    ActionEnum::CoPersonEditedPetition      => 'CO Person Edited (Petition)',
    ActionEnum::CoPersonManuallyProvisioned => 'CO Person Provisioned (Manual)',
    ActionEnum::CoPersonMatchedPetition     => 'CO Person Matched (Petition)',
    ActionEnum::CoPersonProvisioned         => 'CO Person Provisioned',
    ActionEnum::CoPersonRoleAddedManual     => 'CO Person Role Created (Manual)',
    ActionEnum::CoPersonRoleAddedPetition   => 'CO Person Role Created (Petition)',
    ActionEnum::CoPersonRoleDeletedManual   => 'CO Person Role Deleted (Manual)',
    ActionEnum::CoPersonRoleEditedManual    => 'CO Person Role Edited',
    ActionEnum::CoPersonRoleEditedPetition  => 'CO Person Role Edited (Petition)',
    ActionEnum::CoPersonOrgIdLinked         => 'CO Person and Org Identity Linked',
    ActionEnum::CoPersonOrgIdUnlinked       => 'CO Person and Org Identity Unlinked',
    ActionEnum::CoPetitionCreated           => 'CO Petition Created',
    ActionEnum::CoPetitionUpdated           => 'CO Petition Updated',
    ActionEnum::EmailAddressVerified        => 'Email Address Verified',
    ActionEnum::EmailAddressVerifyCanceled  => 'Email Address Verification Canceled',
    ActionEnum::EmailAddressVerifyReqSent   => 'Email Address Verification Sent',
    ActionEnum::HistoryRecordActorExpunged  => 'History Record Actor Expunged',
    ActionEnum::IdentifierAutoAssigned      => 'Identifier Auto Assigned',
    ActionEnum::InvitationConfirmed         => 'Invitation Confirmed',
    ActionEnum::InvitationDeclined          => 'Invitation Declined',
    ActionEnum::InvitationExpired           => 'Invitation Expired',
    ActionEnum::InvitationSent              => 'Invitation Sent',
    ActionEnum::NotificationAcknowledged    => 'Notification Acknowledged',
    ActionEnum::NotificationCanceled        => 'Notification Canceled',
    ActionEnum::NotificationDelivered       => 'Notification Delivered',
    ActionEnum::NotificationParticipantExpunged => 'Notification Participant Expunged',
    ActionEnum::NotificationResolved        => 'Notification Resolved',
    ActionEnum::OrgIdAddedManual            => 'Org Identity Created (Manual)',
    ActionEnum::OrgIdAddedPetition          => 'Org Identity Created (Petition)',
    ActionEnum::OrgIdEditedManual           => 'Org Identity Edited (Manual)',
    ActionEnum::OrgIdEditedPetition         => 'Org Identity Edited (Petition)',
  ),
  
  'en.action.petition' => array(
    PetitionActionEnum::Approved            => 'Petition Approved',
    PetitionActionEnum::Created             => 'Petition Created',
    PetitionActionEnum::Declined            => 'Petition Declined',
    PetitionActionEnum::Denied              => 'Petition Denied',
    PetitionActionEnum::Finalized           => 'Petition Finalized',
    PetitionActionEnum::IdentifiersAssigned => 'Identifiers Assigned',
    PetitionActionEnum::InviteConfirmed     => 'Invitation Confirmed',
    PetitionActionEnum::InviteSent          => 'Invitation Sent',
    PetitionActionEnum::NotificationSent    => 'Notification Sent'
  ),

  'en.admin' =>       array(AdministratorEnum::NoAdmin => 'None',
                            AdministratorEnum::CoAdmin => 'CO Admin',
                            AdministratorEnum::CoOrCouAdmin => 'CO or COU Admin'),
  
  'en.affil' =>       array(AffiliationEnum::Faculty       => 'Faculty',
                            AffiliationEnum::Student       => 'Student',
                            AffiliationEnum::Staff         => 'Staff',
                            AffiliationEnum::Alum          => 'Alum',
                            AffiliationEnum::Member        => 'Member',
                            AffiliationEnum::Affiliate     => 'Affiliate',
                            AffiliationEnum::Employee      => 'Employee',
                            AffiliationEnum::LibraryWalkIn => 'Library Walk-In'),

  'en.contact' =>     array(ContactEnum::Fax => 'Fax',
                            ContactEnum::Home => 'Home',
                            ContactEnum::Mobile => 'Mobile',
                            ContactEnum::Office => 'Office',
                            ContactEnum::Postal => 'Postal',
                            ContactEnum::Forwarding => 'Forwarding'),
  
  // Sub-type contacts since some aren't globally applicable
  'en.contact.address' =>  array(ContactEnum::Home => 'Home',
                                 ContactEnum::Office => 'Office',
                                 ContactEnum::Postal => 'Postal',
                                 ContactEnum::Forwarding => 'Forwarding'),
  
  'en.contact.mail' => array(
    EmailAddressEnum::Delivery => 'Delivery',
    EmailAddressEnum::Forwarding => 'Forwarding',
    EmailAddressEnum::Official => 'Official',
    EmailAddressEnum::Personal => 'Personal'
  ),
  
  'en.contact.phone' => array(ContactEnum::Fax => 'Fax',
                              ContactEnum::Home => 'Home',
                              ContactEnum::Mobile => 'Mobile',
                              ContactEnum::Office => 'Office'),

  'en.enrollment.authz' => array(
    EnrollmentAuthzEnum::CoAdmin        => 'CO Admin',
    EnrollmentAuthzEnum::CoGroupMember  => 'CO Group Member',
    EnrollmentAuthzEnum::CoOrCouAdmin   => 'CO or COU Admin',
    EnrollmentAuthzEnum::CoPerson       => 'CO Person',
    EnrollmentAuthzEnum::CouAdmin       => 'COU Admin',
    EnrollmentAuthzEnum::CouPerson      => 'COU Person',
    EnrollmentAuthzEnum::None           => 'None'
  ),
  
  'en.enrollment.match' => array(
    EnrollmentMatchPolicyEnum::Advisory  => 'Advisory',
    EnrollmentMatchPolicyEnum::Automatic => 'Automatic',
    EnrollmentMatchPolicyEnum::None      => 'None',
    EnrollmentMatchPolicyEnum::Self      => 'Self'
  ),
  
  'en.extattr' =>     array(ExtendedAttributeEnum::Integer => 'Integer',
                            ExtendedAttributeEnum::Timestamp => 'Timestamp',
                            ExtendedAttributeEnum::Varchar32 => 'String (32)'),

  'en.ia.algorithm' => array(IdentifierAssignmentEnum::Random => 'Random',
                             IdentifierAssignmentEnum::Sequential => 'Sequential'),

  'en.identifier' =>  array(IdentifierEnum::ePPN => 'ePPN',
                            IdentifierEnum::ePTID => 'ePTID',
                            IdentifierEnum::Mail => 'Mail',
                            IdentifierEnum::OpenID => 'OpenID',
                            IdentifierEnum::UID => 'UID'),
  
  // As a moderately arbitrary decision, the languages listed here those with at least
  // 100m speakers per Ethnologue (by way of wikipedia)
  //  http://en.wikipedia.org/wiki/List_of_languages_by_total_number_of_speakers
  // as well as any official languages of REFEDS participants not in the above list
  //  https://refeds.org/resources_list.html
  // The key is the ISO 639-2 two letter tag
  //  http://www.loc.gov/standards/iso639-2/ISO-639-2_utf-8.txt
  // See also http://people.w3.org/rishida/names/languages.html
  // and http://www.iana.org/assignments/language-subtag-registry/language-subtag-registry
  'en.language' => array(
    'af'      => 'Afrikaans',
    'ar'      => 'Arabic (العربية)',
    'bn'      => 'Bengali',
    'zh-Hans' => 'Chinese - Simplified (简体中文)',
    'zh-Hant' => 'Chinese - Traditional (繁體中文)',
    'hr'      => 'Croatian (Hrvatski)',
    'cs'      => 'Czech (čeština)',
    'da'      => 'Danish (Dansk)',
    'nl'      => 'Dutch (Nederlands) / Flemish',
    'en'      => 'English',
    'et'      => 'Estonian (Eesti Keel)',
    'fi'      => 'Finnish (Suomi)',
    'fr'      => 'French (Français)',
    'de'      => 'German (Deutsch)',
    'el'      => 'Greek (ελληνικά)',
    'he'      => 'Hebrew (עִבְרִית)',
    'hi'      => 'Hindi (हिंदी)',
    'hu'      => 'Hungarian (Magyar)',
    'id'      => 'Indonesian (Bahasa Indonesia)',
    'it'      => 'Italian (Italiano)',
    'ja'      => 'Japanese (日本語)',
    'ko'      => 'Korean (한국어)',
    'lv'      => 'Latvian (Latviešu Valoda)',
    'lt'      => 'Lithuanian (Lietuvių Kalba)',
    'ms'      => 'Malaysian (Bahasa Malaysia)',
    'no'      => 'Norwegian (Norsk)',
    'pl'      => 'Polish (Język Polski)',
    'pt'      => 'Portuguese (Português)',
    'ro'      => 'Romanian (Limba Română)',
    'ru'      => 'Russian (Pyccĸий)',
    'sr'      => 'Serbian (српски / Srpski)',
    'sl'      => 'Slovene (Slovenski Jezik)',
    'es'      => 'Spanish (Español)',
    'sv'      => 'Swedish (Svenska)',
    'tr'      => 'Turkish (Türkçe)',
    'ur'      => 'Urdu (اُردُو)'
  ),

  'en.name' =>        array(NameEnum::Alternate => 'Alternate',
                            NameEnum::Author => 'Author',
                            NameEnum::FKA => 'FKA',
                            NameEnum::Official => 'Official',
                            NameEnum::Preferred => 'Preferred'),

  'en.required' =>    array(RequiredEnum::Required => 'Required',
                            RequiredEnum::Optional => 'Optional',
                            RequiredEnum::NotPermitted => 'Not Permitted'),

  'en.status' =>      array(StatusEnum::Active              => 'Active',
                            StatusEnum::Approved            => 'Approved',
                            StatusEnum::Declined            => 'Declined',
                            StatusEnum::Deleted             => 'Deleted',
                            StatusEnum::Denied              => 'Denied',
                            StatusEnum::Invited             => 'Invited',
                            StatusEnum::Pending             => 'Pending',
                            StatusEnum::PendingApproval     => 'Pending Approval',
                            StatusEnum::PendingConfirmation => 'Pending Confirmation',
                            StatusEnum::Suspended           => 'Suspended'),
  
  'en.status.not' => array(
    NotificationStatusEnum::Acknowledged          => 'Acknowledged',
    NotificationStatusEnum::Canceled              => 'Canceled',
    NotificationStatusEnum::Deleted               => 'Deleted',
    NotificationStatusEnum::PendingAcknowledgment => 'Pending Acknowledgment',
    NotificationStatusEnum::PendingResolution     => 'Pending Resolution',
    NotificationStatusEnum::Resolved              => 'Resolved'
  ),
  
  'en.status.prov' => array(
    ProvisionerStatusEnum::AutomaticMode  => 'Automatic Mode',
    ProvisionerStatusEnum::ManualMode     => 'Manual Mode',
    ProvisionerStatusEnum::Disabled       => 'Disabled'
  ),
  
  'en.status.prov.desc' =>  'In automatic mode, provisioners are called automatically as needed<br />In manual mode, an administrator must invoke the provisioner',

  'en.status.prov.target' => array(
    ProvisioningStatusEnum::NotProvisioned => 'Not Provisioned',
    ProvisioningStatusEnum::Provisioned    => 'Provisioned',
    ProvisioningStatusEnum::Queued         => 'Queued',
    ProvisioningStatusEnum::Unknown        => 'Unknown'
  ),
  
  'en.status.susp' => array(
    SuspendableStatusEnum::Active              => 'Active',
    SuspendableStatusEnum::Suspended           => 'Suspended'
  ),
  
  // Navigation links
  'en.nav.location' =>     array(LinkLocationEnum::topBar => 'Top Bar'),

  // Demographics
  'en.nsf.gender' =>       array(NSFGenderEnum::Female => 'Female',
                                 NSFGenderEnum::Male   => 'Male'),

  'en.nsf.citizen' =>      array(NSFCitizenshipEnum::USCitizen           => 'U.S. Citizen',
                                 NSFCitizenshipEnum::USPermanentResident => 'U.S. Permanent Resident',
                                 NSFCitizenshipEnum::Other               => 'Other non-U.S. Citizen'),

  'en.nsf.ethnic' =>       array(NSFEthnicityEnum::Hispanic    => 'Hispanic or Latino',
                                 NSFEthnicityEnum::NotHispanic => 'Not Hispanic or Latino'),

  'en.nsf.ethnic.desc' =>       array(NSFEthnicityEnum::Hispanic => 'A person of Mexican, Puerto Rican, Cuban, South or Central American, or other Spanish culture or origin, regardless of race',),


  'en.nsf.race' =>         array(NSFRaceEnum::Asian          => 'Asian',
                                 NSFRaceEnum::AmericanIndian => 'American Indian or Alaskan Native',
                                 NSFRaceEnum::Black          => 'Black or African American',
                                 NSFRaceEnum::NativeHawaiian => 'Native Hawaiian or Pacific Islander',
                                 NSFRaceEnum::White          => 'White'
                                ),

  'en.nsf.race.desc' =>         array(NSFRaceEnum::Asian          => 'A person having origins in any of the original peoples of the Far East, Southeast Asia, or the Indian subcontinent including, for example, Cambodia, China, India, Japan, Korea, Malaysia, Pakistan, the Philippine Islands, Thailand, and Vietnam',
                                      NSFRaceEnum::AmericanIndian => 'A person having origins in any of the original peoples of North and South America (including Central America), and who maintains tribal affiliation or community attachment',
                                      NSFRaceEnum::Black          => 'A person having origins in any of the black racial groups of Africa',
                                      NSFRaceEnum::NativeHawaiian => 'A person having origins in any of the original peoples of Hawaii, Guan, Samoa, or other Pacific Islands',
                                      NSFRaceEnum::White          => 'A person having origins in any of the original peoples of Europe, the Middle East, or North Africa'),

  'en.nsf.disab' =>        array(NSFDisabilityEnum::Hearing  => 'Hearing Impaired',
                                 NSFDisabilityEnum::Visual   => 'Visual Impaired',
                                 NSFDisabilityEnum::Mobility => 'Mobility/Orthopedic Impairment',
                                 NSFDisabilityEnum::Other    => 'Other Impairment'),

  // Errors
  'er.auth' =>        'Not authenticated',
  'er.co.cm.edit' =>  'Cannot edit COmanage CO',
  'er.co.cm.rm' =>    'Cannot remove COmanage CO',
  'er.co.exists' =>   'A CO named "%1$s" already exists',
  'er.co.fail' =>     'Unable to find CO',
  'er.co.gr.admin' => 'CO created, but failed to create initial admin group',
  'er.co.none' =>     'No COs found (did you run setup.php?)',
  'er.co.mismatch' => 'Requested CO does not match CO of %1$s %2$s',
  'er.co.specify' =>  'No CO Specified',
  'er.co.unk' =>      'Unknown CO',
  'er.co.unk-a' =>    'Unknown CO "%1$s"',
  'er.coef.unk' =>    'Unknown CO Enrollment Flow',
  'er.comember' =>    '%1$s is a member of one or more COs (%2$s) and cannot be removed.',
  'er.coumember' =>   '%1$s is a member of one or more COUs that you do not manage (%2$s) and cannot be removed.',
  'er.cop.member' =>  '%1$s is already a member of %2$s and cannot be added again. However, an additional role may be added.',
  'er.cop.unk' =>     'Unknown CO Person',
  'er.cop.unk-a' =>   'Unknown CO Person "%1$s"',
  // XXX These should become er.copr (or tossed if not needed)
  'er.cop.nf' =>      'CO Person Role %1$s Not Found',
  'er.copr.exists' => '%1$s has one or more CO Person Roles and cannot be removed.',
  'er.copr.none' =>   'CO Person Role Not Provided',
  'er.copt.unk' =>    'Unknown CO Provisioning Target',
  'er.cou.copr' =>    'There are still one or more CO person role records in the COU %1$s, and so it cannot be deleted.',
  'er.cou.child' =>   'COUs with children can not be deleted',
  'er.cou.cycle' =>   'Parent is a descendant.  Cycles are not permitted.',
  'er.cou.exists' =>  'A COU named "%1$s" already exists',
  'er.cou.gr.admin' => 'COU created, but failed to create initial admin group',
  'er.cou.sameco' =>  'COUs must be in the same CO',
  'er.delete' =>      'Delete Failed',
  'er.deleted-a' =>   'Deleted "%1$s"',  // XXX is this an er or an rs?
  'er.db.connect' =>  'Failed to connect to database: %1$s',
  'er.db.schema' =>   'Possibly failed to update database schema',
  'er.db.save' =>     'Database save failed',
  'er.ea.alter' =>    'Failed to alter table for attribute',
  'er.ea.exists' =>   'An attribute named "%1$s" already exists within the CO',
  'er.ea.index' =>    'Failed to update index for attribute',
  'er.ea.table' =>    'Failed to create CO Extended Attribute table',
  'er.ea.table.d' =>  'Failed to drop CO Extended Attribute table',
  'er.ef.authz.cou' => 'A COU must be specified for authorization type "%1$s"',
  'er.ef.authz.gr' => 'A group must be specified for authorization type "%1$s"',
  'er.efcf.init' =>   'Failed to set up initial CMP Enrollment Configuration',
  'er.et.default' =>  'Failed to add default types',
  'er.et.exists' =>   'An extended type named "%1$s" already exists',
  'er.et.inuse' =>    'The extended type "%1$s" is in use by at least one attribute within this CO and cannot be removed.',
  'er.field.req' =>   'This field is required',
  'er.fields' =>      'Please recheck the highlighted fields',
  'er.file.write' =>  'Unable to open "%1$s" for writing',
  'er.gr.exists' =>   'A group named "%1$s" already exists within the CO',
  'er.gr.init' =>     'Group created, but failed to set initial owner/member',
  'er.gr.nf' =>       'Group %1$s Not Found',
  'er.gr.res' =>      'Groups named "admin" or prefixed "admin:" are reserved',
  'er.grm.already' => 'CO Person %1$s is already a member of group %2$s',
  'er.grm.nf' =>      'Group Member %1$s Not Found',
  'er.grm.none' =>    'No group memberships to add',
  'er.ia.already' =>  'Identifier already assigned',
  'er.ia.exists' =>   'The identifier "%1$s" is already in use',
  'er.ia.failed' =>   'Failed to find a unique identifier to assign',
  'er.ia.none' =>     'No identifier assignments configured',
  'er.id.unk' =>      'Unknown Identifier',
  'er.id.unk-a' =>    'Unknown Identifier "%1$s"',
  'er.inv.exp' =>     'Invitation Expired',
  'er.inv.exp.use' => 'Processing of invitation failed due to invitation expiration',
  'er.inv.nf' =>      'Invitation Not Found',
  'er.loc.exists' =>  'A localization already exists for the key "%1$s" and language "%2$s"',
  'er.nd.already'  => 'NSF Demographic data already exists for this person',
  'er.nm.primary' =>  '"%1$s" is the primary name and cannot be deleted',
  'er.nt.ack' =>      'Notification is not pending acknowledgment and cannot be acknowledged',
  'er.nt.cxl' =>      'Notification is not pending and cannot be canceled',
  'er.nt.email' =>    'Notification could not be sent because no email address was found',
  'er.nt.res' =>      'Notification is not pending resolution and cannot be resolved',
  'er.nt.send' =>     'Notification to %1$s failed (%2$s)',
  'er.notfound' =>    '%1$s "%2$s" Not Found',
  'er.notprov' =>     'Not Provided',
  'er.notprov.id' =>  '%1$s ID Not Provided',
  'er.person.noex' => 'Person does not exist',
  'er.person.none' => 'No CO Person, CO Person Role, or Org Identity specified',
  'er.plugin.fail' => 'Failed to load plugin "%1$s"',
  'er.plugin.prov.none' => 'There are no suitable plugins available. No provisioning targets can be added.',
  // er.prov is a javascript string and so cannot take a parameter
  'er.prov' =>        'Provisioning failed: ',
  'er.prov.plugin' => 'Provisioning failed for %1$s: %2$s',
  'er.pt.status' =>   'Change of petition status from %1$s to %2$s is not permitted',
  'er.pt.resend.status' => 'Cannot resend an invitation not in Pending Confirmation status',
  'er.reply.unk' =>   'Unknown Reply',
  'er.timeout' =>     'Your session has expired. Please login again.',
  'er.orgp.nomail' => '%1$s (Org Identity %2$s) has no known email address.<br />Add an email address and then try again.',
  'er.orgp.pool' =>   'Failed to pool organizational identities',
  'er.orgp.unk-a' =>  'Unknown Org Identity "%1$s"',
  'er.orgp.unpool' => 'Failed to unpool organizational identities',
  'er.unknown' =>     'Unknown value "%1$s"',
  
  'et.default' =>     'There are no Extended Types currently defined for this attribute. The default types are currently in use. When you create a new Extended Type, the default types will automatically be added to this list.',

  // Fields. Field names should match data model names to facilitate various auto-rendering.
  'fd.action' =>      'Action',
  'fd.actions' =>     'Actions',
  'fd.actor' =>       'Actor',
  'fd.actor.self' =>  'Self Signup',
  'fd.address' =>     'Address',
  // The next set must be named fd.model.validation-field
  'fd.address.country' => 'Country',
  'fd.address.language' => 'Language',
  'fd.address.line1' => 'Address Line 1',
  'fd.address.line2' => 'Address Line 2',
  'fd.address.locality' => 'City',
  'fd.address.postal_code' => 'ZIP/Postal Code',
  'fd.address.state' => 'State',
  'fd.address.fields.req' => 'An address must consist of at least these fields:',
  'fd.admin' =>       'Administrator',
  'fd.affiliation' => 'Affiliation',
  'fd.an.desc' =>     'Alphanumeric characters only',
  'fd.approver' =>    'Approver',
  'fd.attribute' =>   'Attribute',
  'fd.attr.env' =>    'Environment Variable Name',
  'fd.attr.ldap' =>   'LDAP Name',
  'fd.attr.saml' =>   'SAML Name',
  'fd.attrs.cop' =>   'Person Attributes',
  'fd.attrs.copr' =>  'Role Attributes',
  'fd.attrs.org' =>   'Organizational Attributes',
  'fd.attrs.pet' =>   'Petition Attributes',
  'fd.closed' =>      'Closed',
  'fd.comment' =>     'Comment',
  'fd.cou' =>         'COU',
  'fd.cou.nopar'  =>  'No COUs are available to be assigned parent',  
  'fd.co_people.status' => 'CO Person Status',
  'fd.created' =>     'Created',
  // Demographics fields
  'fd.de.persid'  =>  'Person ID',
  'fd.de.gender'  =>  'Gender',
  'fd.de.citizen' =>  'Citizenship',
  'fd.de.ethnic'  =>  'Ethnicity',
  'fd.de.race'    =>  'Race',
  'fd.de.disab'   =>  'Disability',
  'fd.default'    =>  'Default',
  'fd.desc' =>        'Description',
  'fd.directory' =>   'Directory',
  'fd.domain' =>      'Domain',
  // Enrollment configuration fields
  'fd.ea.attr.copy2cop' => 'Copy this attribute to the CO Person record',
  'fd.ea.ignauth' =>  'Ignore Authoritative Values',
  'fd.ea.ignauth.desc' => 'Ignore authoritative values for this attribute, such as those provided via environment variables, SAML, or LDAP',
  'fd.ea.desc'    =>  'Description',
  'fd.ea.desc.desc' => 'Descriptive text to be displayed when prompting for this attribute (like this text you\'re reading now)',
  'fd.ea.label'   =>  'Label',
  'fd.ea.label.desc' => 'The label to be displayed when prompting for this attribute as part of the enrollment process',
  'fd.ea.order'   =>  'Order',
  'fd.ea.order.desc' => 'The order in which this attribute will be presented (leave blank to append at the end of the current attributes)',
  'fd.ed.date.fixed'  => 'On',
  'fd.ed.date.next'   => 'On the next',
  'fd.ed.date.next-note' => '(year is ignored)',
  'fd.ed.date.offset' => 'days from the Petition creation',
  'fd.ed.default' =>  'Default Value',
  'fd.ed.modify'  =>  'Modifiable',
  'fd.ed.modify.desc' => 'If false, the Petitioner cannot change the default value placed into the Petition',
  'fd.ef.aea' =>      'Require Authentication For Administrator Enrollment',
  'fd.ef.aea.desc' => 'If administrator enrollment is enabled, require enrollees to authenticate to the platform in order to complete their enrollment',
  'fd.ef.aee' =>      'Require Email Confirmation For Administrator Enrollment',
  'fd.ef.aee.desc' => 'If administrator enrollment is enabled, require enrollees to confirm their email address in order to complete their enrollment',
  'fd.ef.abody' =>    'Approval Email Body',
  'fd.ef.abody.desc' => 'Body for email message sent after Petition is approved. Max 4000 characters.',
  'fd.ef.asub' =>     'Subject For Approval Email',
  'fd.ef.asub.desc' => 'Subject line for email message sent after Petition is approved.',
  'fd.ef.appr' =>     'Require Approval For Enrollment',
  'fd.ef.appr.desc' => 'If administrator approval is required, a Petition must be approved before the Enrollee becomes active.',
  'fd.ef.appr.gr' =>  'Members of this Group are authorized approvers (or else CO/COU admins by default)',
  'fd.ef.authn' =>    'Require Authentication',
  'fd.ef.authn.desc' => 'Require enrollee to authenticate in order to complete their enrollment',
  'fd.ef.authz' =>    'Enrollment Authorization',
  'fd.ef.authz.desc' => 'Authorization required to execute this enrollment flow, see <a href="https://spaces.internet2.edu/display/COmanage/Registry+Enrollment+Flow+Configuration#RegistryEnrollmentFlowConfiguration-EnrollmentAuthorization">Enrollment Authorization</a> for details',
  'fd.ef.ce' =>       'Require Confirmation of Email',
  'fd.ef.ce.desc' =>  'Confirm email addresses provided by sending a confirmation URL to the address',
  'fd.ef.coef' =>     'Enable Organizational Attributes Via CO Enrollment Flow',
  'fd.ef.coef.desc' => 'If enabled, allow organizational identity attributes to be collected via forms during CO enrollment flows (these attributes will be less authoritative than those obtained via LDAP or SAML)',
  'fd.ef.efn'      => 'From Address For Notifications',
  'fd.ef.efn.desc' => 'Email address notifications will come from',
  'fd.ef.env'      => 'Enable Environment Attribute Retrieval',
  'fd.ef.env.desc' => 'Examine the server environment for authoritative organizational identity attributes',
  'fd.ef.epx' =>      'Early Provisioning Executable',
  'fd.ef.epx.desc' => '(Need for this TBD)',
  'fd.ef.ignauth' =>  'Ignore Authoritative Values',
  'fd.ef.ignauth.desc' => 'Ignore authoritative values for all attributes for this enrollment flow, such as those provided via environment variables, SAML, or LDAP',
  'fd.ef.intro' =>    'Introduction',
  'fd.ef.intro.desc' => 'Optional text to display at the top of a Petition form',
  'fd.ef.ldap' =>     'Enable LDAP Attribute Retrieval',
  'fd.ef.ldap.desc' => 'If the enrollee is affiliated with an organization with a known LDAP server, query the LDAP server for authoritative attributes',
  'fd.ef.match' =>    'Identity Matching',
  'fd.ef.match.desc' => 'Identity Matching policy for this enrollment flow, see <a href="https://spaces.internet2.edu/display/COmanage/Registry+Enrollment+Flow+Configuration#RegistryEnrollmentFlowConfiguration-IdentityMatching">Identity Matching</a> for details',
  'fd.ef.noa' =>      'Notify On Active Status',
  'fd.ef.noa.desc' => 'Email address to notify upon status being set to active',
  'fd.ef.noap' =>     'Notify On Approved Status',
  'fd.ef.noap.desc' => 'Notify enrollee when Petition is approved',
  'fd.ef.noep' =>     'Notify On Early Provisioning',
  'fd.ef.noep.desc' => 'Email address to notify upon execution of early provisioning',
  'fd.ef.nogr' =>     'Notification Group',
  'fd.ef.nogr.desc' => 'Group to notify on new petitions and changes of petition status. (This is an informational notification. Separate notifications will be sent to approvers and enrollees, as appropriate.)',
  'fd.ef.nop' =>      'Notify On Provisioning',
  'fd.ef.nop.desc' => 'Email address to notify upon execution of provisioning',
  'fd.ef.pool' =>     'Pool Organizational Identities',
  'fd.ef.pool.desc' => 'If pooling is enabled, organizational identities -- as well as any attributes released by IdPs -- will be made available to all COs, regardless of which CO enrollment flows added them',
  'fd.ef.pool.on.warn' => 'Enabling pooling will delete any existing links between organizational identities and the COs which created them (when you click Save). This operation cannot be undone.',
  'fd.ef.pool.off.warn' => 'Disabling pooling will duplicate any organizational identities used by more than one CO (when you click Save). This operation cannot be undone.',
  'fd.ef.px' =>       'Provisioning Executable',
  'fd.ef.px.desc' =>  'Executable to call to initiate user provisioning',
  'fd.ef.rd.confirm' => 'Confirmation Redirect URL',
  'fd.ef.rd.confirm.desc' => 'URL to redirect to after the email address associated with the Petition is confirmed. Leave blank for account linking enrollment.',
  'fd.ef.rd.submit' => 'Submission Redirect URL',
  'fd.ef.rd.submit.desc' => 'URL to redirect to after Petition is submitted by someone who is not already in the CO.',
  'fd.ef.saml' =>     'Enable SAML Attribute Extraction',
  'fd.ef.saml.desc' => 'If the enrollee is authenticated via a SAML IdP with attributes released, examine the SAML assertion for authoritative attributes',
  'fd.ef.sea' =>      'Require Authentication For Self Enrollment',
  'fd.ef.sea.desc' => 'If self enrollment is enabled, require enrollees who are self-enrolling to authenticate to the platform',
  'fd.ef.vbody' =>    'Verification Email Body',
  'fd.ef.vbody.desc' => 'Body for email message sent as part of verification step. Max 4000 characters.',
  'fd.ef.vsub' =>     'Subject For Verification Email',
  'fd.ef.vsub.desc' => 'Subject line for email message sent as part of verification step.',
  // (End enrollment configuration fields)
  // This must be named fd.model.validation-field
  'fd.email_address.mail' => 'Email',
  'fd.email_address.verified' => 'Verified',
  'fd.email_address.unverified' => 'Unverified',
  'fd.email_address.verified.warn' => 'Editing a verified email address will make it unverified',
  'fd.enrollee' =>    'Enrollee',
  'fd.et.forattr' =>  'For Attribute',
  'fd.ev.for' =>      'Verification for %1$s',
  'fd.ev.verify' =>   'Verify email address %1$s',
  'fd.ev.verify.desc' => 'Please confirm %1$s is your email address by clicking the <b>Confirm</b> button, below.',
  'fd.false' =>       'False',
  'fd.group.desc.adm' => '%1$s Administrators',
  'fd.group.grmem' => 'Group Member',
  'fd.group.mem' =>   'Member',
  'fd.group.memin' => 'membership in "%1$s"',
  'fd.group.own' =>   'Owner',
  'fd.group.own.only' => 'Owner (only)',
  'fd.groups' =>      'Groups',
  'fd.hidden' =>      'Hidden',
  'fd.hidden.desc' => 'If true, this field will not be rendered during enrollment',
  'fd.history.pt' =>  'Petition History',
  // Identifier Assignment
  'fd.ia.algorithm' => 'Algorithm',
  'fd.ia.algorithm.desc' => 'The algorithm to use when generating identifiers',
  'fd.ia.exclusions' => 'Exclusions',
  'fd.ia.exclusions.desc' => '(Not yet implemented)',
  'fd.ia.format' =>   'Format',
  'fd.ia.format.desc' => 'See <a href="https://spaces.internet2.edu/display/COmanage/Configuring+Registry+Identifier+Assignment">Configuring Registry Identifier Assignment</a> for details',
  'fd.ia.format.prefab' => 'Select a Common Pattern',
  'fd.ia.format.p0' => 'Default (#)',
  'fd.ia.format.p1' => 'given.family(.#)@myvo.org',
  'fd.ia.format.p2' => 'given(.m).family(.#)@myvo.org',
  'fd.ia.format.p3' => 'gmf#@myvo.org',
  'fd.ia.maximum' =>  'Maximum',
  'fd.ia.maximum.desc' => 'The maximum value for randomly generated identifiers',
  'fd.ia.minimum' =>  'Minimum',
  'fd.ia.minimum.desc' => 'The minimum value for randomly generated identifiers, or the starting value for sequences',
  'fd.ia.type.email' => 'Email Type',
  'fd.ia.type.email.desc' => 'For Identifier Assignments applied to Type <i>Mail</i>, an Email Address (instead of an Identifier) will be created with this type (if not blank)',
  // The next set must be named fd.model.validation-field
  'fd.identifier.identifier' => 'Identifier',
  'fd.identifier.login' => 'Login',
  'fd.identifier.login.desc' =>  'Allow this identifier to login to Registry',
  'fd.ids' =>         'Identifiers',
  'fd.index' =>       'Index',
  'fd.inv.for' =>     'Invitation for %1$s',
  'fd.inv.to' =>      'Invitation to %1$s',
  'fd.key' =>         'Key',
  'fd.language' =>    'Language',
  'fd.lan.desc' =>    'Lowercase alphanumeric characters only',
  'fd.link.location' => 'Link Location',  
  'fd.link.order' =>  'Link Order',
  'fd.link.title' =>  'Link Title',
  'fd.link.url' =>    'Link URL',
  'fd.members' =>     'Members',
  'fd.modified' =>    'Modified',
  'fd.name' =>        'Name',
  'fd.name.affil'  => 'Name and Affiliation',
  'fd.name.d' =>      'Display Name',
  'fd.name.h.desc' => '(Dr, Hon, etc)',
  'fd.name.s.desc' => '(Jr, III, etc)',
  // The next set must be named fd.model.validation-field
  'fd.name.honorific' => 'Honorific',
  'fd.name.given'  => 'Given Name',
  'fd.name.middle' => 'Middle Name',
  'fd.name.family' => 'Family Name',
  'fd.name.suffix' => 'Suffix',
  'fd.name.language' => 'Language',
  'fd.name.primary_name' => 'Primary',
  'fd.name.fields.req' => 'A name must consist of at least these fields:',
  'fd.no' =>          'No',
  'fd.not.last' =>    'Last Notification',
  'fd.null' =>        'Null',
  'fd.o' =>           'Organization',
  'fd.open' =>        'Open',
  'fd.organization_id' => 'Organization ID',
  'fd.ou' =>          'Department',
  'fd.parent' =>      'Parent COU',
  'fd.password' =>    'Password',
  'fd.people' =>      '%1$s People',
  'fd.perms' =>       'Permissions',
  'fd.petitioner' =>  'Petitioner',
  'fd.plugin' =>      'Plugin',
  'fd.plugin.ptwarn' => 'Once a Provisioning Target has been created, the Plugin cannot be changed',
  'fd.prov.status.for' => 'Provisioning Status for %1$s',
  'fd.pt.required' => '&dagger; denotes required fields if you populate this section',
  'fd.recipient' =>   'Recipient',
  'fd.req' =>         '* denotes required field',
  'fd.required' =>    'Required',
  'fd.resolved' =>    'Resolved',
  'fd.roles' =>       'Roles',
  'fd.searchbase' =>  'Search Base',
  'fd.source' =>      'Source',
  'fd.sponsor' =>     'Sponsor',
  'fd.sponsor.desc' =>'(for continued membership)',
  'fd.status' =>      'Status',
  'fd.subject' =>     'Subject',
  'fd.tc.agree.desc' => 'You must agree to the following Terms and Conditions before continuing.<br />You must review the T&C before you can click <i>I Agree</i>, and you must agree before you can submit.',
  'fd.tc.agree.no' => 'Not Agreed',
  'fd.tc.agree.yes' => 'Agreed',
  'fd.tc.cou.desc' => 'If set, this T&C only applies to members of the specified COU',
  'fd.tc.for' =>      'Terms and Conditions for %1$s (%2$s)',
  'fd.tc.none' =>     'There are no applicable Terms and Conditions',
  'fd.tc.url.desc' => 'The URL to the Terms and Conditions, which will be displayed in a popup',
  // This must be named fd.model.validation-field
  'fd.telephone_number.number' => 'Phone',
  'fd.text' =>        'Text',
  'fd.text.original' => 'Original Translation',
  'fd.timestamp' =>   'Timestamp',
  'fd.title' =>       'Title',
  'fd.title.none' =>  'No Title',
  'fd.true' =>        'True',
  'fd.type' =>        'Type',
  'fd.type.warn' =>   'After an extended attribute is created, its type may not be changed',
  'fd.untitled' =>    'Untitled',
  'fd.url' =>         'URL',
  'fd.username.api' => 'API User Name',
  'fd.valid_from' =>  'Valid From',
  'fd.valid_from.desc' => '(leave blank for immediate validity)',
  'fd.valid_through' => 'Valid Through',
  'fd.valid_through.desc' => '(leave blank for indefinite validity)',
  'fd.yes' =>         'Yes',

  // Informational messages
  'in.groupmember.select' => 'This change will not take effect until the person becomes active.',
  'in.orgidentities'   => 'Organizational Identities represent a person\'s identity as asserted by a "home" institution, such as their University or a social identity provider.  Reading the documentation before editing them is advised.',
  
  // Menu
  'me.account'         => 'My Account',
  'me.changepassword'  => 'Change Password',
  'me.collaborations'  => 'Collaborations',
  'me.configuration'   => 'Configuration',
  'me.for'             => 'For %1$s',
  'me.identity'        => 'Identity',
  'me.label'           => 'Manage:',
  'me.people'          => 'People',
  'me.platform'        => 'Platform',
  'me.population'      => 'My Population',
  'me.tandc'           => 'Terms and Conditions',

  // Operations
  'op.accept' =>      'Accept',
  'op.ack' =>         'Acknowledge',
  'op.add' =>         'Add',
  'op.add-a' =>       'Add "%1$s"',
  'op.add.new' =>     'Add a New %1$s',
  'op.approve' =>     'Approve',
  'op.back' =>        'Back',
  'op.begin' =>       'Begin',
  'op.cancel' =>      'Cancel',
  'op.compare' =>     'Compare',
  'op.config' =>      'Configure',
  'op.confirm' =>     'Confirm',
  'op.db.ok' =>       'Database schema update successful',
  'op.db.schema' =>   'Loading schema from file %1$s...',
  'op.decline' =>     'Decline',
  'op.delete' =>      'Delete',
  'op.delete.consfdemographics' => 'this NSF demographic entry',
  'op.delete.ok' =>   'Are you sure you wish to remove "%1$s"? This action cannot be undone.',
  'op.deny' =>        'Deny',
  'op.edit' =>        'Edit',
  'op.edit.ea' =>     'Edit Enrollment Attributes',
  'op.edit-a' =>      'Edit %1$s',
  'op.edit-f' =>      'Edit %1$s for %2$s',
  'op.enroll' =>      'Enroll',
  'op.expunge' =>     'Expunge',
  'op.expunge-a' =>   'Expunge %1$s',
  'op.expunge.ack' => 'Check the box to confirm',
  'op.expunge.confirm' => 'Are you sure you wish to expunge %1$s? This operation cannot be undone.',
  'op.expunge.info' => 'Expunging will permanently delete',
  'op.expunge.info.cop' => 'The complete CO Person record for <a href="%2$s">%1$s</a>, including <a href="%3$s">history</a>',
  'op.expunge.info.copr' => '%1$s role record: <a href="%3$s">%2$s</a>',
  'op.expunge.info.hist' => '<a href="%2$s">%1$s history record(s)</a> will be updated to have no Actor',
  'op.expunge.info.not.act' => '<a href="%2$s">%1$s notification(s)</a> will be updated to have no Actor',
  'op.expunge.info.not.rec' => '<a href="%2$s">%1$s notification(s)</a> will be updated to have no Recipient',
  'op.expunge.info.not.res' => '<a href="%2$s">%1$s notification(s)</a> will be updated to have no Resolver',
  'op.expunge.info.org' => 'Org Identity: <a href="%2$s">%1$s</a>',
  'op.expunge.info.org.no' => 'The Org Identity <a href="%2$s">%1$s</a> will <b>not</b> be deleted because it is associated with at least one other CO Person record',
  'op.filter.status' => 'Filter by Status',
  'op.find.inv' =>    'Find a Person to Invite to %1$s',
  'op.gr.memadd' =>   'Manage %1$s Group Memberships',
  'op.grm.edit' =>    'Edit Members of %1$s Group %2$s',
  'op.grm.manage' =>  'Manage My Group Memberships',
  'op.history' =>     'View History',
  'op.id.auto' =>     'Autogenerate Identifiers',
  'op.id.auto.confirm' => 'Are you sure you wish to autogenerate identifiers?',
  'op.inv' =>         'Invite',
  'op.inv-a' =>       'Invite %1$s',
  'op.inv-t' =>       'Invite %1$s to %2$s',
  'op.inv.reply' =>   'Reply to Invitation',
  'op.inv.resend' =>  'Resend Invite',
  'op.inv.send' =>    'Send Invite',
  'op.manage.grm' =>  'Manage Group Memberships',
  'op.menu' =>        'Menu',
  'op.login' =>       'Login',
  'op.logout' =>      'Logout',
  'op.ok' =>          'OK',
  'op.petition' =>    'Petition',
  'op.petition.create' => 'Create Petition',
  'op.primary' =>     'Make Primary',
  'op.proceed.ok' =>  'Are you sure you wish to proceed?',
  'op.prov' =>        'Provision',
  'op.prov.confirm' => 'Are you sure you wish to (re)provision this record?',
  'op.prov.view' =>   'Provisioned Services',
  'op.prov.wait' =>   'Requesting provisioning, please wait...',
  'op.remove' =>      'Remove',
  'op.order.attr' =>  'Reorder Attributes',
  'op.reset' =>       'Reset Form',
  'op.save' =>        'Save',
  'op.select' =>      'Select',
  'op.select-a' =>    'Select a %1$s',
  'op.submit' =>      'Submit',
  'op.tc.agree' =>    'Agree to Terms and Conditions',
  'op.tc.agree.i' =>  'I Agree',
  'op.tc.review' =>   'Review Terms and Conditions',
  'op.tc.review.pt' => 'Review All Agreed To Terms and Conditions',
  'op.unlink' =>      'Unlink',
  'op.unlink.confirm' => 'Are you sure you wish to unlink this identity?',
  'op.verify' =>      'Verify',
  'op.view' =>        'View',
  'op.view-a' =>      'View %1$s',
  'op.view-f' =>      'View %1$s for %2$s',
  
  // Results
  'rs.added' =>       'Added',
  'rs.added-a' =>     '"%1$s" Added',
  'rs.added-a2' =>    '%1$s "%2$s" Added',
  'rs.added-a3' =>    '%1$s Added',
  'rs.deleted-a2' =>  '%1$s "%2$s" Deleted',
  'rs.deleted-a3' =>  '%1$s Deleted',
  'rs.edited-a3' =>   '%1$s Edited',
  'rs.ev.cxl' =>      'Verification of Email Address canceled',
  'rs.ev.cxl-a' =>    'Verification of Email Address %1$s canceled',
  'rs.ev.sent' =>     'Email verification request sent to %1$s',
  'rs.ev.ver' =>      'Email Address verified',
  'rs.ev.ver-a' =>    'Email Address %1$s verified by %2$s',
  'rs.expunged' =>    'Record Expunged',
  'rs.grm.added' =>   'Added to CO Group %1$s (%2$s) (member=%3$s, owner=%4$s)',
  'rs.grm.added-p' => 'Added to CO Group %1$s (%2$s) via Petition (member=%3$s, owner=%4$s)',
  'rs.grm.deleted' => 'Removed from CO Group %1$s (%2$s)',
  'rs.grm.edited' =>  'Edited CO Group Roles %1$s (%2$s) (from member=%3$s, owner=%4$s to member=%5$s, owner=%6$s)',
  'rs.hr.expunge' =>  'History Record %1$s actor removed as part of CO Person expunge',
  'rs.ia.ok' =>       'Identifiers Assigned',
  'rs.inv.conf' =>    'Invitation Confirmed',
  'rs.inv.conf-a' =>  'Invitation to %1$s confirmed',
  'rs.inv.dec' =>     'Invitation Declined',
  'rs.inv.dec-a' =>   'Invitation to %1$s declined',
  'rs.inv.sent' =>    'Invitation sent to %1$s',
  'rs.mail.verified' => 'Email Address "%1$s" verified',
  'rs.nm.primary' =>  'Primary name updated',
  'rs.nm.primary-a' => 'Primary name updated to "%1$s"',
  'rs.nt.ackd' =>     'Notification acknowledged',
  'rs.nt.ackd-a' =>   'Notification acknowledged: "%1$s"',
  'rs.nt.cxld' =>     'Notification canceled',
  'rs.nt.cxld-a' =>   'Notification canceled: "%1$s"',
  'rs.nt.delivered' => 'Notification delivered: "%1$s"',
  'rs.nt.delivered.email' => 'Notification delivered to %1$s: "%2$s"',
  'rs.nt.expunge' =>  'Notification %1$s %2$s removed as part of CO Person expunge',
  'rs.nt.resd-a' =>   'Notification resolved: "%1$s"',
  'rs.nt.sent' =>     'Approval notification sent to %1$s',
  'rs.prov-a' =>      'Provisioned %1$s',
  'rs.prov.ok' =>     'Provisioning completed successfully',
  'rs.pt.approve' =>  'Petition Approved',
  'rs.pt.confirm' =>  'Petition Confirmed',
  'rs.pt.create' =>   'Petition Created',
  'rs.pt.create.from' => 'Petition created from enrollment flow "%1$s"',
  'rs.pt.create.not' => 'Petition created for %1$s from enrollment flow "%2$s"',
  'rs.pt.create.self' => 'Petition Created. You may need to check your email for further information.', 
  'rs.pt.deny' =>     'Petition Denied',
  'rs.pt.id.attached' => 'Authenticated identifier "%1$s" attached to organizational identity',
  'rs.pt.id.login' => 'Identifier "%1$s" flagged for login',
  'rs.pt.login' =>    'Petition Created. You have been logged out, and an activation URL has been sent to your email address. Please click the link in that email to continue.',
  'rs.pt.relogin' =>  'Petition Confirmed. You have been logged out, and will need to login again for your new identity to take effect.',
  'rs.pt.status' =>   'Petition for %1$s changed status from %2$s to %3$s (%4$s)',
  'rs.tc.agree' =>    'Terms and Conditions "%1$s" agreed to',
  'rs.tc.agree.behalf' => 'Terms and Conditions "%1$s" agreed to on behalf of',
  'rs.tc.agree.ok' => 'Agreement to Terms and Conditions recorded',
  'rs.saved' =>       'Saved',
  'rs.updated' =>     '"%1$s" Updated',
  'rs.updated-a2' =>  '%1$s "%2$s" Updated',
  'rs.updated-a3' =>  '%1$s Updated',
  
  // Setup
  
  'se.cache.done' =>      'Done clearing cache',
  'se.cf.admin.given' =>  'Enter administrator\'s given name',
  'se.cf.admin.sn' =>     'Enter administrator\'s family name',
  'se.cf.admin.user' =>   'Enter administrator\'s login username',
  'se.cf.admin.salt' =>   'Enter >= 40 character security salt or blank for random',
  'se.cf.admin.seed' =>   'Enter >= 29 digit security seed or blank for random',
  'se.db.co' =>           'Creating COmanage CO',
  'se.db.cop' =>          'Adding Org Identity to CO',
  'se.db.group' =>        'Creating COmanage admin group',
  'se.db.op' =>           'Adding initial Org Identity',
  'se.security.salt' =>   'Creating security salt file',
  'se.security.seed' =>   'Creating security seed file',
  'se.done' =>            'Setup complete',
  'se.users.view' =>      'Creating users view'
);

// Make a copy of the original texts, since CoLocalizations can override them
$cm_texts_orig = $cm_texts;

/**
 * Render localized text
 *
 * @since  COmanage Registry 0.1
 * @param  string Index of message to render
 * @param  array Substitutions for variables within localized text
 * @param  integer If <key> represents an array, the index of the corresponding message
 * @return string Localized text
 */

function _txt($key, $vars=null, $index=null)
{
  global $cm_lang, $cm_texts;

  // XXX need to figure out how to pass arbitrary # of args to sprintf
  
  $s = (isset($index) ? $cm_texts[ $cm_lang ][$key][$index] : $cm_texts[ $cm_lang ][$key]);
  
  switch(count($vars))
  {
  case 1:
    return(sprintf($s, $vars[0]));
    break;
  case 2:
    return(sprintf($s, $vars[0], $vars[1]));
    break;
  case 3:
    return(sprintf($s, $vars[0], $vars[1], $vars[2]));
    break;
  case 4:
    return(sprintf($s, $vars[0], $vars[1], $vars[2], $vars[3]));
    break;
  case 5:
    return(sprintf($s, $vars[0], $vars[1], $vars[2], $vars[3], $vars[4]));
    break;
  case 6:
    return(sprintf($s, $vars[0], $vars[1], $vars[2], $vars[3], $vars[4], $vars[5]));
    break;
  default:
    return($s);
  }
}

/**
 * Bootstrap plugin texts
 *
 * @since  COmanage Registry v0.8
 */

function _bootstrap_plugin_txt()
{
  global $cm_lang, $cm_texts;
  
  $plugins = AppController::availablePlugins();
  
  foreach($plugins as $plugin) {
    $langfile = APP. '/Plugin/' . $plugin . '/Lib/lang.php';
    
    if(is_readable($langfile)) {
      // Include the file
      include $langfile;
      
      // And merge its texts for the current language
      $varName = 'cm_' . Inflector::underscore($plugin) . '_texts';
      
      $cm_texts[$cm_lang] = array_merge($cm_texts[$cm_lang], ${$varName}[$cm_lang]);
    }
  }
}