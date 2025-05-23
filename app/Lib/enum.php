<?php
/**
 * COmanage Registry Enumerations
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
 * @since         COmanage Registry v0.1
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

class ActionEnum
{
  // Codes beginning with 'X' (eg: 'XABC') are reserved for local use
  // Codes beginning with a lowercase 'p' (eg: 'pABC') are reserved for plugin use
  const AuthenticatorDeleted            = 'DAUT';
  const AuthenticatorEdited             = 'EAUT';
  const AuthenticatorStatusEdited       = 'EATS';
  const ClusterAccountAdded             = 'ACAM';
  const ClusterAccountAutoCreated       = 'ACAA';
  const ClusterAccountAutoEdited        = 'ECAA';
  const ClusterAccountDeleted           = 'DCAM';
  const ClusterAccountEdited            = 'ECAM';
  const CoEmailListAdded                = 'ACEL';
  const CoEmailListDeleted              = 'DCEL';
  const CoEmailListEdited               = 'ECEL';
  const CoEmailListManuallyProvisioned  = 'PCEM';
  const CoEmailListProvisioned          = 'PCEA';
  const CoGroupAdded                    = 'ACGR';
  const CoGroupAddedBulk                = 'ACGB';
  const CoGroupDeleted                  = 'DCGR';
  const CoGroupEdited                   = 'ECGR';
  const CoGroupManuallyProvisioned      = 'PCGM';
  const CoGroupMemberAdded              = 'ACGM';
  const CoGroupMemberAddedPipeline      = 'ACGL';
  const CoGroupMemberDeleted            = 'DCGM';
  const CoGroupMemberDeletedPipeline    = 'DCGL';
  const CoGroupMemberEdited             = 'ECGM';
  const CoGroupMemberEditedPipeline     = 'ECGL';
  const CoGroupMemberValidityTriggered  = 'VCGM';
  const CoGroupProvisioned              = 'PCGA';
  const CoPersonAddedBulk               = 'ACPB';
  const CoPersonAddedManual             = 'ACPM';
  const CoPersonAddedPetition           = 'ACPP';
  const CoPersonAddedPipeline           = 'ACPL';
  const CoPersonDeletedManual           = 'DCPM';
  const CoPersonDeletedPetition         = 'DCPP';
  const CoPersonEditedApi               = 'ECPA';
  const CoPersonEditedManual            = 'ECPM';
  const CoPersonEditedPetition          = 'ECPP';
  const CoPersonEditedPipeline          = 'ECPL';
  const CoPersonManuallyProvisioned     = 'PCPM';
  const CoPersonMatchedPetition         = 'MCPP';
  const CoPersonMatchedPipeline         = 'MCPL';
  const CoPersonProvisioned             = 'PCPA';
  const CoPersonStatusRecalculated      = 'RCPS';
  const CoPersonRoleAddedManual         = 'ACRM';
  const CoPersonRoleAddedPetition       = 'ACRP';
  const CoPersonRoleAddedPipeline       = 'ACRL';
  const CoPersonRoleDeletedManual       = 'DCRM';
  const CoPersonRoleEditedExpiration    = 'ECRX';
  const CoPersonRoleEditedManual        = 'ECRM';
  const CoPersonRoleEditedPetition      = 'ECRP';
  const CoPersonRoleEditedPipeline      = 'ECRL';
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
  const IdentityDocumentAdded           = 'AIDD';
  const IdentityDocumentEdited          = 'EIDD';
  const IdentityDocumentDeleted         = 'DIDD';
  const InTrash                         = 'TR';
  const InvitationConfirmed             = 'INVC';
  const InvitationDeclined              = 'INVD';
  const InvitationExpired               = 'INVE';
  const InvitationSent                  = 'INVS';
  const InvitationViewed                = 'INVV';
  const MatchAttributesUpdated          = 'UMAT';
  const NameAdded                       = 'ANAM';
  const NameDeleted                     = 'DNAM';
  const NameEdited                      = 'ENAM';
  const NamePrimary                     = 'PNAM';
  const NotificationAcknowledged        = 'NOTA';
  const NotificationCanceled            = 'NOTX';
  const NotificationDelivered           = 'NOTD';
  const NotificationParticipantExpunged = 'NOTE';
  const NotificationResolved            = 'NOTR';
  const OrgIdAddedBulk                  = 'AOIB';
  const OrgIdAddedManual                = 'AOIM';
  const OrgIdAddedPetition              = 'AOIP';
  const OrgIdAddedSource                = 'AOIS';
  const OrgIdDeletedManual              = 'DOIM';
  const OrgIdDeletedPetition            = 'DOIP';
  const OrgIdEditedLoginEnv             = 'EOIE';
  const OrgIdEditedManual               = 'EOIM';
  const OrgIdEditedPetition             = 'EOIP';
  const OrgIdEditedSource               = 'EOIS';
  const OrgIdRemovedSource              = 'ROIS';
  const ProvisionerAction               = 'PRVA';
  const ProvisionerFailed               = 'PRVX';
  const ReferenceIdentifierObtained     = 'OIDR';
  const VettingRequestCanceled          = 'VETX';
  const VettingRequestCompleted         = 'VETD';
  const VettingRequestRegistered        = 'VETC';
  const VettingRequestRequeued          = 'VETQ';
}

class AdministratorEnum
{
  const NoAdmin       = 'N';
  const CoOrCouAdmin  = 'C';
  const CoAdmin       = 'O';
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
  
  public static $fromEduPersonAffiliation = array(
    'Faculty'         => AffiliationEnum::Faculty,
    'Student'         => AffiliationEnum::Student,
    'Staff'           => AffiliationEnum::Staff,
    'Alum'            => AffiliationEnum::Alum,
    'Member'          => AffiliationEnum::Member,
    'Affiliate'       => AffiliationEnum::Affiliate,
    'Employee'        => AffiliationEnum::Employee,
    'Library Walk-In' => AffiliationEnum::LibraryWalkIn
  );
}

class AuthenticationEventEnum
{
  const ApiLogin               = 'AI';
  const RegistryLogin          = 'IN';
}

class AuthenticatorStatusEnum
{
  const Active                 = 'A';
  const Expired                = 'XP';
  const Locked                 = 'L';
  const NotSet                 = 'NS';
}

class ComparisonEnum
{
  const Contains               = 'CTS'; // Substr
  const ContainsInsensitive    = 'CTI';
  const Equals                 = 'EQS';
  const EqualsInsensitive      = 'EQI';
  const NotContains            = 'NCT';
  const NotContainsInsensitive = 'NCTI';
  const NotEquals              = 'NEQ';
  const NotEqualsInsensitive   = 'NEQI';
  const Regex                  = 'REGX';
}

class ContactEnum
{
  const Campus      = 'campus';
  const Fax         = 'fax';
  const Forwarding  = 'forwarding';
  const Home        = 'home';
  const Mobile      = 'mobile';
  const Office      = 'office';
  const Postal      = 'postal';
}

// Note ContactTypeEnum is for the default Extended Types associated with the Contact MVPA,
// whereas ContactEnum is for TelephoneNumber and Address
class ContactTypeEnum
{
  // These are from the SAML Metadata 2.0 spec
  const Administrative  = 'administrative';
  const Billing         = 'billing';
  const Other           = 'other';
  const Support         = 'support';
  const Technical       = 'technical';
}

class DataFilterContextEnum
{
  const OrgIdentitySource  = 'OS';
  const ProvisioningTarget = 'PT';
}

class DepartmentEnum {
  const Department          = 'department';
  const ResearchInstitute   = 'researchinstitute';
  const VO                  = 'vo';
}

class DictionaryModeEnum {
  const Department    = "OU";
  const Organization  = "O";
  const Standard      = "S";
}

class ElectStrategyEnum {
  const FIFO        = 'FI';
  const Manual      = 'M';
}

class EmailAddressEnum {
  const Delivery      = 'delivery';
  const Forwarding    = 'forwarding';
  const MailingList   = 'list';
  const Official      = 'official';
  const Personal      = 'personal';
  const Preferred     = 'preferred';
  const Recovery      = 'recovery';
}
  
class EnrollmentApprovalConfirmationModeEnum
{
  const Always        = 'AL';
  const Approval      = 'AP';
  const Denial        = 'D';
  const Never         = 'N';
}

class EnrollmentAuthzEnum {
  const AuthUser      = 'AU';
  const CoAdmin       = 'CA';
  const CoGroupMember = 'CG';
  const CoOrCouAdmin  = 'A';
  const CoPerson      = 'CP';
  const CouAdmin      = 'UA';
  const CouPerson     = 'UP';
  const None          = 'N';
}

class EnrollmentDupeModeEnum
{
  const Duplicate       = 'D';
  const Merge           = 'M';
  const NewRole         = 'R';
  const NewRoleCouCheck = 'C';
}

class EnrollmentMatchPolicyEnum {
  const Advisory  = "A";
  const External  = "E";
  const None      = "N";
  const Select    = "P";
  const Self      = "S";
}

class EnrollmentOrgIdentityModeEnum {
  const OISAuthenticate   = "OA";
  const OISClaim          = "OC";
  const OISIdentify       = "OI";
  const OISSearch         = "OS";
  const OISSearchRequired = "SR";
  const OISSelect         = "SL";
  const None              = "N";
}

class EnrollmentRole
{
  const Approver   = 'A';
  const Enrollee   = 'E';
  const Petitioner = 'P';
}

class EnrollmentFlowUIMode
{
  const Basic = 'B';
  const Full  = 'F';
}

class ExtendedAttributeEnum {
  const Integer   = 'INTEGER';
  const Timestamp = 'TIMESTAMP';
  const Varchar32 = 'VARCHAR(32)';
}

// Note CO or COU is determined by co_groups:cou_id
class GroupEnum
{
  const Standard      = "S";
  const ActiveMembers = "MA";
  const Admins        = "A";
  const AllMembers    = "M";
  const Approvers     = "AP";
  // XXX CO-1100, not yet supported

  const Clusters      = 'CL';
  const NestedAdmins  = "AN";
  const NestedMembers = "MN";
}

class HttpServerAuthType
{
  const Basic         = "BA";
  const Bearer        = "BE";
  const None          = "X";
}

// XXX [REF]https://httpstatuses.com
class HttpStatusCodesEnum
{
  // [Informational 1xx]
  const HTTP_CONTINUE                        = 100;
  const HTTP_SWITCHING_PROTOCOLS             = 101;

  // [Successful 2xx]
  const HTTP_OK                              = 200;
  const HTTP_CREATED                         = 201;
  const HTTP_ACCEPTED                        = 202;
  const HTTP_NONAUTHORITATIVE_INFORMATION    = 203;
  const HTTP_NO_CONTENT                      = 204;
  const HTTP_RESET_CONTENT                   = 205;
  const HTTP_PARTIAL_CONTENT                 = 206;

  // [Redirection 3xx]
  const HTTP_MULTIPLE_CHOICES                = 300;
  const HTTP_MOVED_PERMANENTLY               = 301;
  const HTTP_FOUND                           = 302;
  const HTTP_SEE_OTHER                       = 303;
  const HTTP_NOT_MODIFIED                    = 304;
  const HTTP_USE_PROXY                       = 305;
  const HTTP_UNUSED                          = 306;
  const HTTP_TEMPORARY_REDIRECT              = 307;

  // [Client Error 4xx]
  const errorCodesBeginAt                    = 400;
  const HTTP_BAD_REQUEST                     = 400;
  const HTTP_UNAUTHORIZED                    = 401;
  const HTTP_PAYMENT_REQUIRED                = 402;
  const HTTP_FORBIDDEN                       = 403;
  const HTTP_NOT_FOUND                       = 404;
  const HTTP_METHOD_NOT_ALLOWED              = 405;
  const HTTP_NOT_ACCEPTABLE                  = 406;
  const HTTP_PROXY_AUTHENTICATION_REQUIRED   = 407;
  const HTTP_REQUEST_TIMEOUT                 = 408;
  const HTTP_CONFLICT                        = 409;
  const HTTP_GONE                            = 410;
  const HTTP_LENGTH_REQUIRED                 = 411;
  const HTTP_PRECONDITION_FAILED             = 412;
  const HTTP_REQUEST_ENTITY_TOO_LARGE        = 413;
  const HTTP_REQUEST_URI_TOO_LONG            = 414;
  const HTTP_UNSUPPORTED_MEDIA_TYPE          = 415;
  const HTTP_REQUESTED_RANGE_NOT_SATISFIABLE = 416;
  const HTTP_EXPECTATION_FAILED              = 417;

  // [Server Error 5xx]
  const HTTP_INTERNAL_SERVER_ERROR           = 500;
  const HTTP_NOT_IMPLEMENTED                 = 501;
  const HTTP_BAD_GATEWAY                     = 502;
  const HTTP_SERVICE_UNAVAILABLE             = 503;
  const HTTP_GATEWAY_TIMEOUT                 = 504;
  const HTTP_VERSION_NOT_SUPPORTED           = 505;
}

class IdentifierAssignmentEnum
{
  const Plugin     = 'P';
  const Random     = 'R';
  const Sequential = 'S';
}

class IdentifierAssignmentContextEnum
{
  const CoDepartment = 'CD';
  const CoGroup      = 'CG';
  const CoPerson     = 'CP';
}

class IdentifierAssignmentExclusionEnum
{
  const Confusing     = 'C';
  const Offensive     = 'O';
  const Superstitious = 'S';
}

class IdentifierEnum
{
  const AffiliateSOR       = 'sor-affiliate';
  const Badge              = 'badge';
  const Enterprise         = 'enterprise';
  const EntityID           = 'entityid';
  const ePPN               = 'eppn';
  const ePTID              = 'eptid';
  const ePUID              = 'epuid';
  const GID                = 'gid';
  const GuestSOR           = 'sor-guest';
  const HRSOR              = 'sor-hr';
  const Mail               = 'mail';
  const Name               = 'name';
  const National           = 'national';
  const Network            = 'network';
  const OIDCsub            = 'oidcsub';
  const OpenID             = 'openid';
  const ORCID              = 'orcid';
  const ProvisioningTarget = 'provisioningtarget';
  const Reference          = 'reference';
  const SamlPairwise       = 'pairwiseid';
  const SamlSubject        = 'subjectid';
  const StudentSOR         = 'sor-student';
  const SORID              = 'sorid';
  const UID                = 'uid';
}
// These generally align with the TAP Core Schema
class IdentityDocumentEnum {
  const BirthCertificate  = 'BC';
  const DriversLicense    = 'DL';
  const Local             = 'L';
  const National          = 'N';
  const NonDriver         = 'ND';
  const Passport          = 'P';
  const Regional          = 'R';
  const Residency         = 'RC';
  const SelfAssertion     = 'SA';
  const Tribal            = 'T';
  const Visa              = 'V';
}

class IdentityVerificationMethodEnum {
  const None      = 'X';
  const Online    = 'O';
  const Physical  = 'P';
  const Remote    = 'R';
}

class JobStatusEnum
{
  const Canceled   = 'CX';
  const Complete   = 'OK';
  const Failed     = 'X';
  const InProgress = 'GO';
  const Notice     = 'NT';
  const Queued     = 'Q';
}

class LinkLocationEnum
{
  const topBar  = 'topbar';
}

class MatchStrategyEnum
{
  const EmailAddress = 'EA';
  const External     = 'EX';
  const Identifier   = 'ID';
  const NoMatching   = 'NO';
}

class MessageTemplateEnum
{
  const Authenticator          = 'AU';
  const EnrollmentApprover     = 'AP';
  const EnrollmentApproval     = 'EA';
  const EnrollmentFinalization = 'EF';
  const EnrollmentVerification = 'EV';
  const ExpirationNotification = 'XN';
  const Plugin                 = 'PL';
}

class MessageFormatEnum
{
    const Plaintext        = 'text';
    const HTML             = 'html';
    const PlaintextAndHTML = 'both';
}

class NameEnum
{
  const Alternate = 'alternate';
  const Author    = 'author';
  const FKA       = 'fka';
  const Official  = 'official';
  const Preferred = 'preferred';
}

class NestedEnum
{
  const Direct   = 'D';
  const Indirect = 'I';
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

class NSFRaceEnum
{
  const Asian            = 'A';
  const AmericanIndian   = 'I';
  const Black            = 'B';
  const NativeHawaiian   = 'N';
  const White            = 'W';
}

class Oauth2GrantEnum
{
  const AuthorizationCode = 'AC';
  const ClientCredentials = 'CC';
  // We don't currently support Implicit or Password Credentials
}

class OrganizationEnum {
  const Academic            = 'edu';
  const Archive             = 'archive';
  const Commercial          = 'com';
  const Facility            = 'facility';
  const Funder              = 'funder';
  const Government          = 'gov';
  const HealthCare          = 'health';
  const NonProfit           = 'nonprofit';
  const Other               = 'other';
}

class OrgIdentityMismatchEnum
{
  const CreateNew        = 'N';
  const Ignore           = 'X';
}

class OrgIdentityStatusEnum
{
  const Removed          = 'RM';
  const Synced           = 'SY';
}

class OrgSyncModeEnum
{
  const Accrual = 'A';
  const Full    = 'F';
  const Manual  = 'M';
  const Update  = 'U';
}

// XXX For any changes check also in CoPipeline::syncOrgIdentityToCoPerson
class OrgSyncAttributesEnum
{
  const Address         = 'Address';
  const AdHocAttribute  = 'AdHocAttribute';
  const EmailAddress    = 'EmailAddress';
  const Identifier      = 'Identifier';
  const Name            = 'Name';
  const TelephoneNumber = 'TelephoneNumber';
  const Url             = 'Url';
}

class PeoplePickerModeEnum
{
  const Manager   = 'M';
  const Sponsor   = 'S';
  const All       = 'AL';
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

// We use the actual field names here to simplify form rendering
class PermittedNameFieldsEnum
{
//  const Given       = "given";  Not currently allowed due to potential conflict with RequiredNameFieldsEnum
  const GF    = "given,family";
  const GMF   = "given,middle,family";
  const GFS   = "given,family,suffix";
  const GMFS  = "given,middle,family,suffix";
  const HGF   = "honorific,given,family";
  const HGMF  = "honorific,given,middle,family";
  const HGFS  = "honorific,given,family,suffix";
  const HGMFS = "honorific,given,middle,family,suffix";
}

class PetitionActionEnum
{
  const Approved                = 'PY';
  const AttributesUpdated       = 'AU';
  const ClusterAccountAutoCreated = 'CA';
  const CommentAdded            = 'CM';
  const Created                 = 'PC';
  const Declined                = 'PX';
  const Denied                  = 'PN';
  const EligibilityFailed       = 'EX';
  const Finalized               = 'PF';
  const FlaggedDuplicate        = 'FD';
  const IdentifierAuthenticated = 'ID';
  const IdentifiersAssigned     = 'IA';
  const IdentityDocumentAdded   = 'DA';
  const IdentityLinked          = 'IL';
  const IdentityNotLinked       = 'IX';
  const IdentityRelinked        = 'IR';
  const InviteConfirmed         = 'IC';
  const InviteSent              = 'IS';
  const InviteViewed            = 'IV';
  const MatchResult             = 'MR';
  const NotificationSent        = 'NS';
  const OrgIdentitySourced      = 'OC';
  const StatusUpdated           = 'SU';
  const StepFailed              = 'SX';
  const TCExplicitAgreement     = 'TE';
  const TCImpliedAgreement      = 'TI';
  const VettingCompleted        = 'VC';
  const VettingRequested        = 'VR';
}

class PetitionStatusEnum
{
  const Active              = 'A';
  const Approved            = 'Y';
  const Confirmed           = 'C';
  const Created             = 'CR';
  const Declined            = 'X';
  const Denied              = 'N';
  const Duplicate           = 'D2';
  const Finalized           = 'F';
  const PendingApproval     = 'PA';
  const PendingConfirmation = 'PC';
  const PendingVetting      = 'PV';
}

class ProvisionerModeEnum
{
  const AutomaticMode       = 'A';
  const Disabled            = 'X';
  const EnrollmentMode      = 'E';
  const QueueMode           = 'Q';
  const QueueOnErrorMode    = 'QE';
  const ManualMode          = 'M';
}

// The action for which a plugin may want to act on
class ProvisioningActionEnum
{
  const AuthenticatorUpdated            = 'AU';
  const CoEmailListAdded                = 'LA';
  const CoEmailListDeleted              = 'LD';
  const CoEmailListReprovisionRequested = 'LR';
  const CoEmailListUpdated              = 'LU';
  const CoGroupAdded                    = 'GA';
  const CoGroupDeleted                  = 'GD';
  const CoGroupReprovisionRequested     = 'GR';
  const CoGroupUpdated                  = 'GU';
  const CoPersonAdded                   = 'PA';
  const CoPersonDeleted                 = 'PD';
  const CoPersonEnteredGracePeriod      = 'PG';
  const CoPersonExpired                 = 'PX';
  const CoPersonPetitionProvisioned     = 'PP';  // Triggered after a petition is finalized
  const CoPersonPipelineProvisioned     = 'PL';  // Triggered after a pipeline is executed
  const CoPersonReprovisionRequested    = 'PR';
  const CoPersonUnexpired               = 'PY';
  const CoPersonUpdated                 = 'PU';
  const CoServiceAdded                  = 'SA';
  const CoServiceDeleted                = 'SD';
  const CoServiceReprovisionRequested   = 'SR';
  const CoServiceUpdated                = 'SU';
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
}

// We use the actual field names here to simplify form rendering
class RequiredAddressFieldsEnum
{
  const CityState                    = "locality,state";
  const Country                      = "country";
  const Postal                       = "postal_code";
  const Street                       = "street";
  const StreetCityStatePostal        = "street,locality,state,postal_code";
  const StreetCityStatePostalCountry = "street,locality,state,postal_code,country";
}

class RequiredNameFieldsEnum
{
  const Given       = "given";
  const GivenFamily = "given,family";
}

class ServerEnum
{
  // When adding a new server type, be sure to add it to ServersController::view_contains
  const HttpServer    = 'HT';
  const KafkaServer   = 'KA';
  const KdcServer     = 'KC';
// CO-1320
//  const LdapServer    = 'LD';
  const MatchServer   = 'MT';
  const Oauth2Server  = 'O2';
  // Generic SQL Server, not "MS SQL Server"
  const SqlServer     = 'SQ';
}

class SponsorEligibilityEnum {
  const CoAdmin       = 'CA';
  const CoGroupMember = 'CG';
  const CoOrCouAdmin  = 'A';
  const CoPerson      = 'CP';
  const None          = 'N';
}

class SqlServerEnum
{
  // Initially we only support Cake-supported types, though that should
  // probably expand at some point
  const Mysql     = 'MY';
  const Postgres  = 'PG';
  const SqlServer = 'MS';
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
  const Locked              = 'LK';
  const Pending             = 'P';
  const PendingApproval     = 'PA';
  const PendingConfirmation = 'PC';
  const PendingVetting      = 'PV';
  const Suspended           = 'S';
  const Declined            = 'X';

  public static $from_api = array(
    'Active'              => StatusEnum::Active,
    'Approved'            => StatusEnum::Approved,
    'Confirmed'           => StatusEnum::Confirmed,
    'Deleted'             => StatusEnum::Deleted,
    'Denied'              => StatusEnum::Denied,
    'Duplicate'           => StatusEnum::Duplicate,
    'Expired'             => StatusEnum::Expired,
    'GracePeriod'         => StatusEnum::GracePeriod,
    'Invited'             => StatusEnum::Invited,
    'Locked'              => StatusEnum::Locked,
    'Pending'             => StatusEnum::Pending,
    'PendingApproval'     => StatusEnum::PendingApproval,
    'PendingConfirmation' => StatusEnum::PendingConfirmation,
    'PendingVetting'      => StatusEnum::PendingVetting,
    'Suspended'           => StatusEnum::Suspended,
    'Declined'            => StatusEnum::Declined
  );
  
  public static $to_api = array(
    StatusEnum::Active              => 'Active',
    StatusEnum::Approved            => 'Approved',
    StatusEnum::Confirmed           => 'Confirmed',
    StatusEnum::Deleted             => 'Deleted',
    StatusEnum::Denied              => 'Denied',
    StatusEnum::Duplicate           => 'Duplicate',
    StatusEnum::Expired             => 'Expired',
    StatusEnum::GracePeriod         => 'GracePeriod',
    StatusEnum::Invited             => 'Invited',
    StatusEnum::Locked              => 'Locked',
    StatusEnum::Pending             => 'Pending',
    StatusEnum::PendingApproval     => 'PendingApproval',
    StatusEnum::PendingConfirmation => 'PendingConfirmation',
    StatusEnum::PendingVetting      => 'PendingVetting',
    StatusEnum::Suspended           => 'Suspended',
    StatusEnum::Declined            => 'Declined'
  );
}

class SuspendableStatusEnum
{
  const Active              = 'A';
  const Suspended           = 'S';

  public static $from_api = array(
    'Active'    => SuspendableStatusEnum::Active,
    'Suspended' => SuspendableStatusEnum::Suspended
  );
  
  public static $to_api = array(
    SuspendableStatusEnum::Active    => 'Active',
    SuspendableStatusEnum::Suspended => 'Suspended'
  );
}

class SyncActionEnum
{
  const Add    = 'A';
  const Delete = 'D';
  // Relink is basically add, but will execute regardless of sync_on_add setting
  const Relink = 'R';
  // Unlink is basically delete, but will execute regardless of sync_on_delete setting
  const Unlink = 'X';
  const Update = 'U';
}

class SyncModeEnum
{
  const Full   = 'F';
  const Manual = 'M';
  const Query  = 'Q';
  const Update = 'U';
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

class TemplateableStatusEnum
{
  const Active              = 'A';
  const Suspended           = 'S';
  const Template            = 'T';
  const InTrash             = 'TR';

  public static $from_api = array(
    'Active'    => TemplateableStatusEnum::Active,
    'Suspended' => TemplateableStatusEnum::Suspended,
    'Template'  => TemplateableStatusEnum::Template,
    'InTrash'   => TemplateableStatusEnum::InTrash
  );

  public static $to_api = array(
    TemplateableStatusEnum::Active    => 'Active',
    TemplateableStatusEnum::Suspended => 'Suspended',
    TemplateableStatusEnum::Template  => 'Template',
    TemplateableStatusEnum::InTrash   => 'InTrash'
  );
}

class TrueFalseEnum {
  const True = 't';
  const False = 'f';
}

class UrlEnum {
  const Official      = 'official';
  const Personal      = 'personal';
}

class VerificationModeEnum
{
  const Automatic       = 'A';
  const Review          = 'R';
  const SkipIfVerified  = 'V';
  const None            = 'X';
}

class VettingStatusEnum
{
  const Canceled      = 'X';    // Admin canceled vetting request
  const Error         = 'ER';   // Plugin returned error
  const Failed        = 'N';    // Plugin returned failure
  const Passed        = 'Y';    // Plugin returned success
  const PendingManual = 'PM';   // Plugin determined manual review is required
  const PendingResult = 'PR';   // Plugin submitted request to API/etc and is awaiting reply
  const Requested     = 'R';    // Vetting requested, but not complete
}

class VisibilityEnum
{
  const CoAdmin         = 'CA';
  const CoGroupMember   = 'CG';
  const CoMember        = 'CP';
  const Unauthenticated = 'P';
  
  public static $from_api = array(
    'CoAdmin'         => VisibilityEnum::CoAdmin,
    'CoGroupMember'   => VisibilityEnum::CoGroupMember,
    'CoMember'        => VisibilityEnum::CoMember,
    'Unauthenticated' => VisibilityEnum::Unauthenticated
  );
  
  public static $to_api = array(
    VisibilityEnum::CoAdmin         => 'CoAdmin',
    VisibilityEnum::CoGroupMember   => 'CoGroupMember',
    VisibilityEnum::CoMember        => 'CoMember',
    VisibilityEnum::Unauthenticated => 'Unauthenticated'
  );
}

// Old style enums below, deprecated
// We're mostly ready to drop these, but there are still a few places that
// reference them and need to be cleaned up.
// See also new use of Model::validEnumsForSelect.

global $affil_t, $affil_ti;
global $contact_t, $contact_ti;
global $extattr_t, $extattr_ti;
global $identifier_t, $identifier_ti;
global $name_t, $name_ti;
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

/**
 * Bootstrap plugin enums
 *
 * @since  COmanage Registry v2.0.0
 */
 
function _bootstrap_plugin_enum()
{
  $plugins = App::objects('plugin');
  
  foreach($plugins as $plugin) {
    // Plugin lang files could be under APP or LOCAL
    foreach(array(APP, LOCAL) as $dir) {
      $enumfile = $dir . '/Plugin/' . $plugin . '/Lib/enum.php';
      
      if(is_readable($enumfile)) {
        // Include the file
        include $enumfile;        
        break;
      }
    }
  }
}
