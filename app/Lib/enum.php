<?php
/**
 * COmanage Registry Enumerations
 *
 * Copyright (C) 2010-14 University Corporation for Advanced Internet Development, Inc.
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
 * @copyright     Copyright (C) 2010-14 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.1
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */

class ActionEnum
{
  // Codes beginning with 'X' (eg: 'XABC') are reserved for local use
  const CoGroupMemberAdded              = 'ACGM';
  const CoGroupMemberDeleted            = 'DCGM';
  const CoGroupMemberEdited             = 'ECGM';
  const CoPersonAddedManual             = 'ACPM';
  const CoPersonAddedPetition           = 'ACPP';
  const CoPersonEditedManual            = 'ECPM';
  const CoPersonEditedPetition          = 'ECPP';
  const CoPersonManuallyProvisioned     = 'PCPM';
  const CoPersonMatchedPetition         = 'MCPP';
  const CoPersonProvisioned             = 'PCPA';
  const CoPersonStatusRecalculated      = 'RCPS';
  const CoPersonRoleAddedManual         = 'ACRM';
  const CoPersonRoleAddedPetition       = 'ACRP';
  const CoPersonRoleDeletedManual       = 'DCRM';
  const CoPersonRoleEditedExpiration    = 'ECRX';
  const CoPersonRoleEditedManual        = 'ECRM';
  const CoPersonRoleEditedPetition      = 'ECRP';
  const CoPersonRoleRelinked            = 'LCRM';
  const CoPersonOrgIdLinked             = 'LOCP';
  const CoPersonOrgIdUnlinked           = 'UOCP';
  const CoPetitionCreated               = 'CPPC';
  const CoPetitionUpdated               = 'CPUP';
  const CoTAndCAgreement                = 'TCAG';
  const CoTAndCAgreementBehalf          = 'TCAB';
  const CommentAdded                    = 'CMNT';
  const EmailAddressVerified            = 'EMLV';
  const EmailAddressVerifyCanceled      = 'EMLC';
  const EmailAddressVerifyReqSent       = 'EMLS';
  const ExpirationPolicyMatched         = 'EXPM';
  const HistoryRecordActorExpunged      = 'HRAE';
  const IdentifierAutoAssigned          = 'AIDA';
  const InvitationConfirmed             = 'INVC';
  const InvitationDeclined              = 'INVD';
  const InvitationExpired               = 'INVE';
  const InvitationSent                  = 'INVS';
  const NameAdded                       = 'ANAM';
  const NameDeleted                     = 'DNAM';
  const NameEdited                      = 'ENAM';
  const NamePrimary                     = 'PNAM';
  const NotificationAcknowledged        = 'NOTA';
  const NotificationCanceled            = 'NOTX';
  const NotificationDelivered           = 'NOTD';
  const NotificationParticipantExpunged = 'NOTE';
  const NotificationResolved            = 'NOTR';
  const OrgIdAddedManual                = 'AOIM';
  const OrgIdAddedPetition              = 'AOIP';
  const OrgIdEditedLoginEnv             = 'EOIE';
  const OrgIdEditedManual               = 'EOIM';
  const OrgIdEditedPetition             = 'EOIP';
  const ProvisionerAction               = 'PRVA';
  const ProvisionerFailed               = 'PRVX';
  const SshKeyAdded                     = 'SSHA';
  const SshKeyDeleted                   = 'SSHD';
  const SshKeyEdited                    = 'SSHE';
  const SshKeyUploaded                  = 'SSHU';
}

class AdministratorEnum
{
  const NoAdmin       = 'N';
  const CoOrCouAdmin  = 'C';
  const CoAdmin       = 'O';
/*    
  public $from_api = array(
    "NoAdmin"       => NoAdmin,
    "CoOrCouAdmin"  => CoOrCouAdmin,
    "CoAdmin"       => CoAdmin
  );

  public $to_api = array(
    NoAdmin       => "NoAdmin",
    CoOrCouAdmin  => "CoOrCouAdmin",
    CoAdmin       => "CoAdmin"
  );*/
}

class AffiliationEnum
{
  const Faculty       = 'faculty';
  const Student       = 'student';
  const Staff         = 'staff';
  const Alum          = 'alum';
  const Member        = 'member';
  const Affiliate     = 'affiliate';
  const Employee      = 'employee';
  const LibraryWalkIn = 'librarywalkin';
  
  // Mapping to the controlled vocabulary. Suitable for use (eg) writing to LDAP.
  public static $eduPersonAffiliation = array(
    AffiliationEnum::Faculty       => 'Faculty',
    AffiliationEnum::Student       => 'Student',
    AffiliationEnum::Staff         => 'Staff',
    AffiliationEnum::Alum          => 'Alum',
    AffiliationEnum::Member        => 'Member',
    AffiliationEnum::Affiliate     => 'Affiliate',
    AffiliationEnum::Employee      => 'Employee',
    AffiliationEnum::LibraryWalkIn => 'Library Walk-In'
  );
}

class ContactEnum
{
  const Fax         = 'fax';
  const Home        = 'home';
  const Mobile      = 'mobile';
  const Office      = 'office';
  const Postal      = 'postal';
  const Forwarding  = 'forwarding';
/*
  public $from_api = array(
    "Fax"         => Fax,
    "Home"        => Home,
    "Mobile"      => Mobile,
    "Office"      => Office,
    "Postal"      => Postal,
    "Forwarding"  => Forwarding
  );

  public $to_api = array(
    Fax         => "Fax",
    Home        => "Home",
    Mobile      => "Mobile",
    Office      => "Office",
    Postal      => "Postal",
    Forwarding  => "Forwarding"
  );*/
}

class EmailAddressEnum {
  const Delivery      = 'delivery';
  const Forwarding    = 'forwarding';
  const Official      = 'official';
  const Personal      = 'personal';
}

class EnrollmentAuthzEnum {
  const CoAdmin       = 'CA';
  const CoGroupMember = 'CG';
  const CoOrCouAdmin  = 'A';
  const CoPerson      = 'CP';
  const CouAdmin      = 'UA';
  const CouPerson     = 'UP';
  const None          = 'N';
}

class EnrollmentMatchPolicyEnum {
  const Advisory  = "A";
  const Automatic = "M";
  const None      = "N";
  const Self      = "S";
}

class ExtendedAttributeEnum {
  const Integer   = 'INTEGER';
  const Timestamp = 'TIMESTAMP';
  const Varchar32 = 'VARCHAR(32)';
}

class NSFCitizenshipEnum
{
  const USCitizen            = 'US';
  const USPermanentResident  = 'P';
  const Other                = 'O';
}

class NSFDisabilityEnum
{
  const Hearing     = 'H';
  const Visual      = 'V';
  const Mobility    = 'M';
  const Other       = 'O';

}

class NSFEthnicityEnum
{
  const Hispanic    = 'H';
  const NotHispanic = 'N';
}

class NSFGenderEnum
{
  const Male        = 'M';
  const Female      = 'F';
}

class IdentifierAssignmentEnum
{
  const Random     = 'R';
  const Sequential = 'S';
}

class IdentifierAssignmentExclusionEnum
{
  const Confusing     = 'C';
  const Offensive     = 'O';
  const Superstitious = 'S';
}

class IdentifierEnum
{
  const ePPN    = 'eppn';
  const ePTID   = 'eptid';
  const Mail    = 'mail';
  const OpenID  = 'openid';
  const UID     = 'uid';
}

class LinkLocationEnum
{
  const topBar  = 'topbar';
}

class NameEnum
{
  const Alternate = 'alternate';
  const Author    = 'author';
  const FKA       = 'fka';
  const Official  = 'official';
  const Preferred = 'preferred';
  
/*    public $from_api = array(
    'Author'    => Author,
    'FKA'       => FKA,
    'Official'  => Official,
    'Preferred' => Preferred
  );
  
  public $to_api = array(
    Author    => 'Author',
    FKA       => 'FKA',
    Official  => 'Official',
    Preferred => 'Preferred'
  );*/
}

class NotificationStatusEnum
{
  const Acknowledged          = 'A';
  const Canceled              = 'X';
  const Deleted               = 'D';
  const PendingAcknowledgment = 'PA';
  const PendingResolution     = 'PR';
  const Resolved              = 'R';
}

class NSFRaceEnum
{
  const Asian            = 'A';
  const AmericanIndian   = 'I';
  const Black            = 'B';
  const NativeHawaiian   = 'N';
  const White            = 'W';
}

class PermissionEnum
{
  const None      = 'N';
  const ReadOnly  = 'RO';
  const ReadWrite = 'RW';
}

class PermittedCharacterEnum
{
  const AlphaNumeric       = 'AN';
  const AlphaNumDotDashUS  = 'AD';
  const AlphaNumDDUSQuote  = 'AQ';
  const Any                = 'AL';
}

class PetitionActionEnum
{
  const Approved                = 'PY';
  const CommentAdded            = 'CM';
  const Created                 = 'PC';
  const Declined                = 'PX';
  const Denied                  = 'PN';
  const Finalized               = 'PF';
  const FlaggedDuplicate        = 'FD';
  const IdentifierAuthenticated = 'ID';
  const IdentifiersAssigned     = 'IA';
  const InviteConfirmed         = 'IC';
  const InviteSent              = 'IS';
  const NotificationSent        = 'NS';
  const TCExplicitAgreement     = 'TE';
  const TCImpliedAgreement      = 'TI';
}

// The status of a provisioning plugin
class ProvisionerStatusEnum
{
  const AutomaticMode       = 'A';
  const Disabled            = 'X';
  const ManualMode          = 'M';
}

// The action for which a plugin may want to act on
class ProvisioningActionEnum
{
  const CoGroupAdded                  = 'GA';
  const CoGroupDeleted                = 'GD';
  const CoGroupReprovisionRequested   = 'GR';
  const CoGroupUpdated                = 'GU';
  const CoPersonAdded                 = 'PA';
  const CoPersonDeleted               = 'PD';
  const CoPersonEnteredGracePeriod    = 'PG';
  const CoPersonExpired               = 'PX';
  const CoPersonReprovisionRequested  = 'PR';
  const CoPersonUnexpired             = 'PY';
  const CoPersonUpdated               = 'PU';
}

// The status of a provisioned target
class ProvisioningStatusEnum
{
  const NotProvisioned      = 'N';
  const Provisioned         = 'P';
  const Queued              = 'Q';
  const Unknown             = 'X';
}

class RequiredEnum
{
  const Required      = 1;
  const Optional      = 0;
  const NotPermitted  = -1;
  /*
  public $from_api = array(
    'Required'      => Required,
    'Optional'      => Optional,
    'NotPermitted'  => NotPermitted
  );
  
  public $to_api = array(
    Required      => 'Required',
    Optional      => 'Optional',
    NotPermitted  => 'NotPermitted'
  );*/
}

// We use the actual field names here to simplify form rendering
class RequiredAddressFieldsEnum
{
  const Line1                       = "line1";
  const Line1CityStatePostal        = "line1,locality,state,postal_code";
  const Line1CityStatePostalCountry = "line1,locality,state,postal_code,country";
}

class RequiredNameFieldsEnum
{
  const Given       = "given";
  const GivenFamily = "given,family";
}

class SshKeyTypeEnum
{
  // Protocol v2
  const DSA         = 'DSA';
  const RSA         = 'RSA';
  // Protocol v1
  const RSA1        = 'RSA1';
}

class StatusEnum
{
  const Active              = 'A';
  const Approved            = 'Y';
  const Confirmed           = 'C';
  const Deleted             = 'D';
  const Denied              = 'N';
  const Duplicate           = 'D2';
  const Expired             = 'XP';
  const GracePeriod         = 'GP';
  const Invited             = 'I';
  const Pending             = 'P';
  const PendingApproval     = 'PA';
  const PendingConfirmation = 'PC';
  const Suspended           = 'S';
  const Declined            = 'X';
  /*
  public $from_api = array(
    "Active"    => Active,
    "Deleted"   => Deleted,
    "Invited"   => Invited,
    "Pending"   => Pending,
    "Suspended" => Suspended,
    "Declined"  => Declined
  );
  
  public $to_api = array(
    Active    => "Active",
    Deleted   => "Deleted",
    Invited   => "Invited",
    Pending   => "Pending",
    Suspended => "Suspended",
    Declined  => "Declined"
  );*/
}

class SuspendableStatusEnum
{
  const Active              = 'A';
  const Suspended           = 'S';
}

class TAndCEnrollmentModeEnum
{
  const ExplicitConsent = 'EC';
  const ImpliedConsent  = 'IC';
  const SplashPage      = 'S';
  const Ignore          = 'X';
}

class TAndCLoginModeEnum
{
  const NotEnforced        = 'X';
  const RegistryLogin      = 'R';
  const DisableAllServices = 'D';
}

// Old style enums below, deprecated
// In order to switch away from them, AppController::convertRestPost
// and checkRestPost must be rewritten, as well as Model/CoEnrollmentAttribute::enrollmentFlowAttributes.
// Check for other references as well.
// See also new use of Model::validEnumsForSelect.

global $affil_t, $affil_ti;
global $contact_t, $contact_ti;
global $extattr_t, $extattr_ti;
global $identifier_t, $identifier_ti;
global $name_t, $name_ti;
global $ssh_ti;  // Used for ldap and github provisioner
global $status_t, $status_ti;

$affil_t = array(
  'faculty' => 'Faculty',
  'student' => 'Student',
  'staff' => 'Staff',
  'alum' => 'Alum',
  'member' => 'Member',
  'affiliate' => 'Affiliate',
  'employee' => 'Employee',
  'librarywalkin' => 'Library Walk-In'    
);

$affil_ti = array(
  'Faculty' => 'faculty',
  'Student' => 'student',
  'Staff' => 'staff',
  'Alum' => 'alum',
  'Member' => 'member',
  'Affiliate' => 'affiliate',
  'Employee' => 'employee',
  'Library Walk-In' => 'librarywalkin'    
);

$contact_t = array(
  'fax' => 'Fax',
  'home' => 'Home',
  'mobile' => 'Mobile',
  'office' => 'Office',
  'postal' => 'Postal',
  'forwarding' => 'Forwarding'
);

$contact_ti = array(
  'Fax' => 'fax',
  'Home' => 'home',
  'Mobile' => 'mobile',
  'Office' => 'office',
  'Postal' => 'postal',
  'Forwarding' => 'forwarding'
);

/*
$extattr_t = array(
  'INTEGER' => 'INTEGER',
  'TIMESTAMP' => 'TIMESTAMP',
  'VARCHAR(32)' => 'VARCHAR(32)'
);

$extattr_ti = array(
  'INTEGER' => 'I',
  'TIMESTAMP' => 'T',
  'VARCHAR(32)' => 'V3'
);
*/

$identifier_t = array(
  'eppn' => 'ePPN',
  'eptid' => 'ePTID',
  'mail' => 'Mail',
  'openid' => 'OpenID',
  'uid' => 'UID'
);

$identifier_ti = array(
  'ePPN' => 'eppn',
  'ePTID' => 'eptid',
  'Mail' => 'mail',
  'OpenID' => 'openid',
  'UID' => 'uid'
);

$name_t = array(
  'alternate' => 'Alternate',
  'author' => 'Author',
  'fka' => 'FKA',
  'official' => 'Official',
  'preferred' => 'Preferred'
);

$name_ti = array(
  'Alternate' => 'alternate',
  'Author' => 'author',
  'FKA' => 'fka',
  'Official' => 'official',
  'Preferred' => 'preferred'
);

$ssh_ti = array(
  'DSA'  => 'ssh-dsa',
  'RSA'  => 'ssh-rsa',
  'RSA1' => 'ssh-rsa1'
);

$status_t = array(
  'A'  => 'Active',
  'D'  => 'Deleted',
  'D2' => 'Duplicate',
  'I'  => 'Invited',
  'N'  => 'Denied',
  'P'  => 'Pending',
  'PA' => 'PendingApproval',
  'S'  => 'Suspended',
  'X'  => 'Declined',
  'Y'  => 'Approved'
);

$status_ti = array(
  'Active'          => 'A',
  'Deleted'         => 'D',
  'Duplicate'       => 'D2',
  'Invited'         => 'I',
  'Denied'          => 'N',
  'Pending'         => 'P',
  'PendingApproval' => 'PA',
  'Suspended'       => 'S',
  'Declined'        => 'X',
  'Approved'        => 'Y'
);
