<?php
/**
 * COmanage Registry CO Enrollment Flow Model
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
 * @since         COmanage Registry v0.3
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

class CoEnrollmentFlow extends AppModel {
  // Define class name for cake
  public $name = "CoEnrollmentFlow";
  
  // Current schema version for API
  public $version = "1.0";
  
  // Add behaviors
  public $actsAs = array('Containable', 'Changelog' => array('priority' => 5));
  
  // Custom find types
  public $findMethods = array('authorized' => true);
  
  // Association rules from this model to other models
  public $belongsTo = array(
    "Co",
    "CoEnrollmentFlowApproverCoGroup" => array(
      'className' => 'CoGroup',
      'foreignKey' => 'approver_co_group_id'
    ),
    "CoEnrollmentFlowAuthzCoGroup" => array(
      'className' => 'CoGroup',
      'foreignKey' => 'authz_co_group_id'
    ),
    "CoEnrollmentFlowAuthzCou" => array(
      'className' => 'Cou',
      'foreignKey' => 'authz_cou_id'
    ),
    "CoEnrollmentFlowNotificationCoGroup" => array(
      'className' => 'CoGroup',
      'foreignKey' => 'notification_co_group_id'
    ),
    "CoEnrollmentFlowAppMessageTemplate" => array(
      'className' => 'CoMessageTemplate',
      'foreignKey' => 'approval_template_id'
    ),
    "CoEnrollmentFlowDenMessageTemplate" => array(
      'className' => 'CoMessageTemplate',
      'foreignKey' => 'denial_template_id'
    ),
    "CoEnrollmentFlowFinMessageTemplate" => array(
      // "Finalization" makes the label too long
      'className' => 'CoMessageTemplate',
      'foreignKey' => 'finalization_template_id'
    ),
    "CoEnrollmentFlowVerMessageTemplate" => array(
      // "Verification" makes the label too long
      'className' => 'CoMessageTemplate',
      'foreignKey' => 'verification_template_id'
    ),
    "CoTheme"
  );
  
  public $hasMany = array(
    // A CO Enrollment Flow has many CO Enrollment Attributes
    "CoEnrollmentAttribute" => array('dependent' => true),
    "CoEnrollmentAuthenticator" => array('dependent' => true),
    "CoEnrollmentSource" => array('dependent' => true),
    // A CO Enrollment Flow may have zero or more CO Petitions
    "CoPetition" => array('dependent' => true),
    "CoPipeline"
  );
  
  // Associated models that should be relinked to the archived attribute during Changelog archiving
  public $relinkToArchive = array('CoPetition');
  
  // Default display field for cake generated views
  public $displayField = "name";
  
  // Default ordering for find operations
// XXX Toss? CO-296
//  public $order = array("CoEnrollmentFlow.name");
  
  // Validation rules for table elements
  public $validate = array(
    'name' => array(
      'rule' => array('validateInput'),
      'required' => true,
      'message' => 'A name must be provided'
    ),
    'co_id' => array(
      'rule' => 'numeric',
      'required' => true,
      'message' => 'A CO ID must be provided'
    ),
    'authz_level' => array(
      'rule' => array('inList',
                      array(EnrollmentAuthzEnum::AuthUser,
                            EnrollmentAuthzEnum::CoAdmin,
                            EnrollmentAuthzEnum::CoGroupMember,
                            EnrollmentAuthzEnum::CoOrCouAdmin,
                            EnrollmentAuthzEnum::CoPerson,
                            EnrollmentAuthzEnum::CouAdmin,
                            EnrollmentAuthzEnum::CouPerson,
                            EnrollmentAuthzEnum::None))
    ),
    'authz_cou_id' => array(
      'rule' => 'numeric',
      'required' => false,
      'allowEmpty' => true
    ),
    'authz_co_group_id' => array(
      'rule' => 'numeric',
      'required' => false,
      'allowEmpty' => true
    ),
    'my_identity_shortcut' => array(
      'rule' => 'boolean'
    ),
    'match_policy' => array(
      'rule' => array('inList',
                      array(EnrollmentMatchPolicyEnum::Advisory,
                            EnrollmentMatchPolicyEnum::Automatic,
                            EnrollmentMatchPolicyEnum::None,
                            EnrollmentMatchPolicyEnum::Select,
                            EnrollmentMatchPolicyEnum::Self)),
      'required' => false,
      'allowEmpty' => true
    ),
    'approval_required' => array(
      'rule' => array('boolean')
    ),
    'approver_co_group_id' => array(
      'rule' => 'numeric',
      'required' => false,
      'allowEmpty' => true
    ),
    'verify_email' => array(
      // deprecated
      'rule' => array('boolean'),
      'required' => false,
      'allowEmpty' => true
    ),
    'email_verification_mode' => array(
      'rule' => array('inList',
                      array(VerificationModeEnum::Automatic,
                            VerificationModeEnum::None,
                            VerificationModeEnum::Review)),
      'required' => false,
      'allowEmpty' => true
    ),
    'invitation_validity' => array(
      'rule' => 'numeric',
      'required' => false,
      'allowEmpty' => true
    ),
    'require_authn' => array(
      'rule' => array('boolean'),
      'required' => false,
      'allowEmpty' => true
    ),
    'notification_co_group_id' => array(
      'rule' => 'numeric',
      'required' => false,
      'allowEmpty' => true
    ),
    'notify_from' => array(
      'rule' => 'email',
      'required' => false,
      'allowEmpty' => true
    ),
    'verification_subject' => array(
      'rule' => 'notBlank',
      'required' => false,
      'allowEmpty' => true
    ),
    'verification_body' => array(
      'rule' => 'notBlank',
      'required' => false,
      'allowEmpty' => true
    ),
    'verification_template_id' => array(
      'rule' => 'numeric',
      'required' => false,
      'allowEmpty' => true
    ),
    'notify_on_approval' => array(
      'rule' => array('boolean')
    ),
    'approval_template_id' => array(
      'rule' => 'numeric',
      'required' => false,
      'allowEmpty' => true
    ),
    'approval_subject' => array(
      'rule' => 'notBlank',
      'required' => false,
      'allowEmpty' => true
    ),
    'approval_body' => array(
      'rule' => 'notBlank',
      'required' => false,
      'allowEmpty' => true
    ),
    'denial_template_id' => array(
      'rule' => 'numeric',
      'required' => false,
      'allowEmpty' => true
    ),
    'notify_on_finalize' => array(
      'rule' => array('boolean')
    ),
    'finalization_template_id' => array(
      'rule' => 'numeric',
      'required' => false,
      'allowEmpty' => true
    ),
    'introduction_text' => array(
      'rule' => 'notBlank',
      'required' => false,
      'allowEmpty' => true
    ),
    'conclusion_text' => array(
      'rule' => 'notBlank',
      'required' => false,
      'allowEmpty' => true
    ),
    't_and_c_mode' => array(
      'rule' => array('inList',
                      array(TAndCEnrollmentModeEnum::ExplicitConsent,
                            TAndCEnrollmentModeEnum::ImpliedConsent,
                            // TAndCEnrollmentModeEnum::SplashPage, not implemented CO-923
                            TAndCEnrollmentModeEnum::Ignore))
    ),
    'redirect_on_submit' => array(
      'rule' => array('url', true),
      'required' => false,
      'allowEmpty' => true
    ),
    'redirect_on_confirm' => array(
      'rule' => array('url', true),
      'required' => false,
      'allowEmpty' => true
    ),
    'redirect_on_finalize' => array(
      'rule' => array('url', true),
      'required' => false,
      'allowEmpty' => true
    ),
    'ignore_authoritative' => array(
      'rule' => array('boolean'),
      'required' => false,
      'allowEmpty' => true
    ),
    'duplicate_mode' => array(
      'rule' => array('inList',
                      array(EnrollmentDupeModeEnum::Duplicate,
                            EnrollmentDupeModeEnum::Merge,
                            EnrollmentDupeModeEnum::NewRole,
                            EnrollmentDupeModeEnum::NewRoleCouCheck))
    ),
    'status' => array(
      'rule' => array('inList', array(TemplateableStatusEnum::Active,
                                      TemplateableStatusEnum::Suspended,
                                      TemplateableStatusEnum::Template))
    )
  );
  
  /**
   * Perform CoEnrollmentFlow model upgrade steps for version 2.0.0.
   * This function should only be called by UpgradeVersionShell.
   *
   * @since  COmanage Registry v2.0.0
   */

  public function _ug110() {
    // v2.0.0 replaces verify_email with email_verification_mode. We want v_e=false
    // to map to e_v_m=None, and v_e=true to map to e_v_n=Review.
    // v2.0.0 was originally v1.1.0.
    
    // We use updateAll here which doesn't fire callbacks (including ChangelogBehavior).
    // We actually want to update archived rows so that petitions render properly
    // (ie: so they show the confirmation steps as relevant). The old history is still
    // there since we're not getting rid of verify_email.
    
    $this->updateAll(array('CoEnrollmentFlow.email_verification_mode' => "'" . VerificationModeEnum::Review . "'"),
                     array('CoEnrollmentFlow.verify_email' => true));
    
    $this->updateAll(array('CoEnrollmentFlow.email_verification_mode' => "'" . VerificationModeEnum::None . "'"),
                     array('CoEnrollmentFlow.verify_email' => false));
  }
  
  /**
   * Add all default values for extended types for the specified CO.
   *
   * @since  COmanage Registry v0.9.2
   * @param  Integer CO ID
   * @return Boolean True on success
   * @throws RuntimeException
   */
  
  public function addDefaults($coId) {
    // We define each default template in an array, but we won't used saveAll or saveMany
    // here since we don't want to re-add a template if it is already defined.
    // We'll use the name of the template as the determining factor.
    
    // Determine some characteristics of this CO for purposes of assembling templates
    
    // Are org identities collectable from enrollment flows?
    $CmpEnrollmentConfiguration = ClassRegistry::init('CmpEnrollmentConfiguration');
    $orgIdentities = $CmpEnrollmentConfiguration->orgIdentitiesFromCOEF();
    
    // Are COUs defined?
    $couList = $this->Co->Cou->allCous($coId);
    $cous = !empty($couList);
    
    $templates = array();
    $templates[] = $this->templateAdditionalRole($coId, $orgIdentities, $cous);
    $templates[] = $this->templateConscriptionApproval($coId, $orgIdentities, $cous);
    $templates[] = $this->templateInvitation($coId, $orgIdentities, $cous);
    $templates[] = $this->templateSelfSignupApproval($coId, $orgIdentities, $cous);
    
    if($orgIdentities) {
      // Account linking only makes sense if org identity attributes are collectable
      $templates[] = $this->templateAccountLinking($coId, $orgIdentities, $cous);
    }
    
    foreach($templates as $t) {
      // See if there is already a flow with this name. If so, don't insert it.
      
      $args = array();
      $args['conditions']['CoEnrollmentFlow.co_id'] = $coId;
      $args['conditions']['CoEnrollmentFlow.name'] = $t['CoEnrollmentFlow']['name'];
      $args['contain'] = false;
      
      if(!$this->find('count', $args)) {
        if(!$this->saveAssociated($t)) {
          throw new RuntimeException('er.db.save');
        }
      }
    }
    
    return true;
  }
  
  /**
   * Determine if a CO Person is authorized to run an Enrollment Flow.
   *
   * @since  COmanage Registry v0.7
   * @param  Array CO Enrollment Flow, as returned by find
   * @param  Integer CO Person ID
   * @param  String Current authenticated identifier
   * @param  RoleComponent
   * @return Boolean True if the CO Person is authorized, false otherwise
   */
  
  public function authorize($coEF, $coPersonId, $authIdentifier, $Role) {
    // If no authz is required, return true before we bother with any other checks
    
    if($coEF['CoEnrollmentFlow']['authz_level'] == EnrollmentAuthzEnum::None) {
      // No authz required
      return true;
    }
    
    // If CO Person is a CO admin, they are always authorized
    
    if($coPersonId
       && $Role->isCoAdmin($coPersonId, $coEF['CoEnrollmentFlow']['co_id'])) {
      return true;
    }
    
    switch($coEF['CoEnrollmentFlow']['authz_level']) {
      case EnrollmentAuthzEnum::AuthUser:
        return !empty($authIdentifier);
        break;
      case EnrollmentAuthzEnum::CoAdmin:
        // We effectively already handled this, above
        break;
      case EnrollmentAuthzEnum::CoGroupMember:
        if($coPersonId
           && $Role->isCoGroupMember($coPersonId, $coEF['CoEnrollmentFlow']['authz_co_group_id'])) {
          return true;
        } 
        break;
      case EnrollmentAuthzEnum::CoOrCouAdmin:
        if($coPersonId
           && $Role->isCoOrCouAdmin($coPersonId, $coEF['CoEnrollmentFlow']['co_id'])) {
          return true;
        }
        break;
      case EnrollmentAuthzEnum::CoPerson:
        if($coPersonId
           && $Role->isCoPerson($coPersonId, $coEF['CoEnrollmentFlow']['co_id'])) {
          return true;
        }
        break;
      case EnrollmentAuthzEnum::CouAdmin:
        if($coPersonId
           && $Role->isCouAdmin($coPersonId, $coEF['CoEnrollmentFlow']['authz_cou_id'])) {
          return true;
        }
        break;
      case EnrollmentAuthzEnum::CouPerson:
        if($coPersonId
           && $Role->isCouPerson($coPersonId, $coEF['CoEnrollmentFlow']['co_id'], $coEF['CoEnrollmentFlow']['authz_cou_id'])) {
          return true;
        }
        break;
      case EnrollmentAuthzEnum::None:
        // We covered this already, above
        break;
    }
    
    // No matching Authz found
    return false;
  }
  
  /**
   * Determine if a CO Person is authorized to run an Enrollment Flow.
   *
   * @since  COmanage Registry v0.7
   * @param  Integer CO Enrollment Flow ID
   * @param  Integer CO Person ID
   * @param  String Current authenticated identifier
   * @param  RoleComponent
   * @return Boolean True if the CO Person is authorized, false otherwise
   */
  
  public function authorizeById($coEfId, $coPersonId, $authIdentifier, $Role) {
    // Retrieve the Enrollment Flow and pass it along
    
    $args = array();
    $args['conditions']['CoEnrollmentFlow.id'] = $coEfId;
    $args['conditions']['CoEnrollmentFlow.status'] = StatusEnum::Active;
    $args['contain'] = false;
    
    $ef = $this->find('first', $args);
    
    if(empty($ef)) {
      return false;
    }
    
    return $this->authorize($ef, $coPersonId, $authIdentifier, $Role);
  }
  
  /**
   * Obtain a list of configured steps for the Enrollment Flow, and attributes about those steps
   *
   * @param Integer $id Enrollment Flow ID
   * @return Array Array of configured steps, with step label as key and the value a hash of attributes about the step
   * @throws InvalidArgumentException
   */
  
  public function configuredSteps($id) {
    $args = array();
    $args['conditions']['CoEnrollmentFlow.id'] = $id;
    $args['contain'] = array('CoEnrollmentAttribute', 'CoEnrollmentSource');
    
    $ef = $this->find('first', $args);
    
    if(empty($ef)) {
      throw new InvalidArgumentException(_txt('er.notfound', array(_txt('ct.co_enrollment_attributes.1'), $id)));
    }
    
    // Construct an array based on the enrollment flow configuration.
    // Required: Step is configured to run
    // Optional: Step is not configured, but plugins may elect to execute
    // NotPermitted: Step is not configured and plugins may not run
    
    // The order of the steps in the array will be the order used to generate the
    // steps in the sidebar (enrollmentFlowSteps.ctp)
    
    $ret = array();
    
    // If introductory text was specified, it should be rendered.
    
    if(!empty($ef['CoEnrollmentFlow']['introduction_text'])) {
      $ret['start']['enabled'] = RequiredEnum::Required;
    } else {
      $ret['start']['enabled'] = RequiredEnum::Optional;
    }
    $ret['start']['role'] = EnrollmentRole::Petitioner;
    
    // selectOrgIdentity is dependent on the attached CoEnrollmentSources.
    // checkEligibility is as well, so we'll set it while we're here.
    // Assume NotPermitted until otherwise determined.
    $ret['selectOrgIdentity']['enabled'] = RequiredEnum::NotPermitted;
    $ret['selectOrgIdentity']['role'] = EnrollmentRole::Petitioner;
    
    $ret['checkEligibility']['enabled'] = RequiredEnum::NotPermitted;
    // Who runs this step depends on whether email confirmation is set, so we'll
    // set the role below
    
    if(!empty($ef['CoEnrollmentSource'])) {
      // Walk the list of sources and look for at least one in Authenticate,
      // Claim, or Select mode. This might not be the most efficient model if lots
      // of sources in Search mode are attached, but that should be a fairly
      // rare configuration.
      
      foreach($ef['CoEnrollmentSource'] as $es) {
        if($es['org_identity_mode'] == EnrollmentOrgIdentityModeEnum::OISAuthenticate
           || $es['org_identity_mode'] == EnrollmentOrgIdentityModeEnum::OISClaim
           || $es['org_identity_mode'] == EnrollmentOrgIdentityModeEnum::OISSelect) {
          $ret['selectOrgIdentity']['enabled'] = RequiredEnum::Required;
        } elseif($es['org_identity_mode'] == EnrollmentOrgIdentityModeEnum::OISSearch
                 || $es['org_identity_mode'] == EnrollmentOrgIdentityModeEnum::OISSearchRequired) {
          $ret['checkEligibility']['enabled'] = RequiredEnum::Required;
        }
        
        // We don't bother breaking the loop here because it'll be rare (though not
        // impossible) that both will be enabled, so we need to walk the full loop
        // even though most of the time it'll be a (trivial) waste of effort.
      }
    }
    
    // If match policy is appropriately set we run the selectEnrollee step.
    
    if(!empty($ef['CoEnrollmentFlow']['match_policy'])
       && ($ef['CoEnrollmentFlow']['match_policy'] == EnrollmentMatchPolicyEnum::Select
           || $ef['CoEnrollmentFlow']['match_policy'] == EnrollmentMatchPolicyEnum::Self)) {
      $ret['selectEnrollee']['enabled'] = RequiredEnum::Required;
    } else {
      $ret['selectEnrollee']['enabled'] = RequiredEnum::NotPermitted;
    }
    $ret['selectEnrollee']['role'] = EnrollmentRole::Petitioner;
    
    // If there are no attributes defined, petitionerAttributes becomes optional.
    
    if(!empty($ef['CoEnrollmentAttribute'])) {
      $ret['petitionerAttributes']['enabled'] = RequiredEnum::Required;
    } else {
      $ret['petitionerAttributes']['enabled'] = RequiredEnum::Optional;
    }
    $ret['petitionerAttributes']['role'] = EnrollmentRole::Petitioner;
        
    // If email confirmation is requested, run sendConfirmation and its helper waitForConfirmation.
    // We can only collect identifiers if email confirmation and authentication are both set.
    // Also enable the re-entry point following email delivery.
    
    if(isset($ef['CoEnrollmentFlow']['email_verification_mode'])
       && $ef['CoEnrollmentFlow']['email_verification_mode'] != VerificationModeEnum::None) {
      $ret['sendConfirmation']['enabled'] = RequiredEnum::Required;
      $ret['waitForConfirmation']['enabled'] = RequiredEnum::Required;
      $ret['processConfirmation']['enabled'] = RequiredEnum::Required;
      
      // Only collect identifier if authentication is required
      if(isset($ef['CoEnrollmentFlow']['require_authn'])
       && $ef['CoEnrollmentFlow']['require_authn']) {
        $ret['collectIdentifier']['enabled'] = RequiredEnum::Required;
      } else {
        $ret['collectIdentifier']['enabled'] = RequiredEnum::NotPermitted;

        if(!empty($ef['CoEnrollmentSource'])) {
          // Walk the list of sources and look for at least one in Identify mode.
          // Similar to the check a few lines above...
          
          foreach($ef['CoEnrollmentSource'] as $es) {
            if($es['org_identity_mode'] == EnrollmentOrgIdentityModeEnum::OISIdentify) {
              $ret['collectIdentifier']['enabled'] = RequiredEnum::Required;
            }
          }
        }
      }
      
      $ret['checkEligibility']['role'] = EnrollmentRole::Enrollee;
    } else {
      $ret['sendConfirmation']['enabled'] = RequiredEnum::NotPermitted;
      $ret['waitForConfirmation']['enabled'] = RequiredEnum::NotPermitted;
      $ret['processConfirmation']['enabled'] = RequiredEnum::NotPermitted;
      $ret['collectIdentifier']['enabled'] = RequiredEnum::NotPermitted;
      
      $ret['checkEligibility']['role'] = EnrollmentRole::Petitioner;
    }
    
    $ret['sendConfirmation']['role'] = EnrollmentRole::Petitioner;
    $ret['waitForConfirmation']['role'] = EnrollmentRole::Petitioner;
    $ret['processConfirmation']['role'] = EnrollmentRole::Enrollee;
    $ret['collectIdentifier']['role'] = EnrollmentRole::Enrollee;
    
    if($ret['sendConfirmation']['enabled'] == RequiredEnum::Required) {
      $ret['establishAuthenticators']['role'] = EnrollmentRole::Enrollee;
      $ret['sendApproverNotification']['role'] = EnrollmentRole::Enrollee;
      $ret['waitForApproval']['role'] = EnrollmentRole::Enrollee;
    } else {
      $ret['establishAuthenticators']['role'] = EnrollmentRole::Petitioner;
      $ret['sendApproverNotification']['role'] = EnrollmentRole::Petitioner;
      $ret['waitForApproval']['role'] = EnrollmentRole::Petitioner;
    }
    
    // Maybe collect authenticators
    
    if(isset($ef['CoEnrollmentFlow']['establish_authenticators'])
       && $ef['CoEnrollmentFlow']['establish_authenticators']) {
      $ret['establishAuthenticators']['enabled'] = RequiredEnum::Required;
    } else {
      $ret['establishAuthenticators']['enabled'] = RequiredEnum::NotPermitted;
    }
    
    // If approval is required, run the appropriate steps
    
    if(isset($ef['CoEnrollmentFlow']['approval_required'])
       && $ef['CoEnrollmentFlow']['approval_required']) {
      $ret['sendApproverNotification']['enabled'] = RequiredEnum::Required;
      $ret['waitForApproval']['enabled'] = RequiredEnum::Required;
      $ret['approve']['enabled'] = RequiredEnum::Required;
      // Redirect is handled by sendApprovalNotification
      $ret['redirectOnConfirm']['enabled'] = RequiredEnum::NotPermitted;
      $ret['redirectOnConfirm']['role'] = EnrollmentRole::Approver;
    } else {
      $ret['sendApproverNotification']['enabled'] = RequiredEnum::NotPermitted;
      $ret['waitForApproval']['enabled'] = RequiredEnum::NotPermitted;
      $ret['approve']['enabled'] = RequiredEnum::NotPermitted;
      
      // If verify_email we still need to redirectOnConfirm
      if(isset($ef['CoEnrollmentFlow']['email_verification_mode'])
         && $ef['CoEnrollmentFlow']['email_verification_mode'] != VerificationModeEnum::None) {
        $ret['redirectOnConfirm']['enabled'] = RequiredEnum::Required;
        $ret['redirectOnConfirm']['role'] = EnrollmentRole::Enrollee;
      } else {
        $ret['redirectOnConfirm']['enabled'] = RequiredEnum::NotPermitted;
        $ret['redirectOnConfirm']['role'] = EnrollmentRole::Petitioner;
      }
    }
    
    $ret['approve']['role'] = EnrollmentRole::Approver;
    
    // Provision and Finalize always run, though by whom varies
    
    $ret['finalize']['enabled'] = RequiredEnum::Required;
    $ret['provision']['enabled'] = RequiredEnum::Required;
    
    if($ret['sendConfirmation']['enabled'] == RequiredEnum::Required) {
      if($ret['sendApproverNotification']['enabled'] == RequiredEnum::Required) {
        $ret['finalize']['role'] = EnrollmentRole::Approver;
        $ret['provision']['role'] = EnrollmentRole::Approver;
      } else {
        $ret['finalize']['role'] = EnrollmentRole::Enrollee;
        $ret['provision']['role'] = EnrollmentRole::Enrollee;
      }
    } else {
      if($ret['sendApproverNotification']['enabled'] == RequiredEnum::Required) {
        $ret['finalize']['role'] = EnrollmentRole::Approver;
        $ret['provision']['role'] = EnrollmentRole::Approver;
      } else {
        $ret['finalize']['role'] = EnrollmentRole::Petitioner;
        $ret['provision']['role'] = EnrollmentRole::Petitioner;
      }
    }
    
    // Populate labels for each step
    
    foreach(array_keys($ret) as $step) {
      $ret[$step]['label'] = _txt('ef.step.' . $step);
    }
    
    if(isset($ef['CoEnrollmentFlow']['notify_on_finalize'])
       && $ef['CoEnrollmentFlow']['notify_on_finalize']) {
      // Change the text for the provision step to make it clearer
      // that a notification will also go out
      $ret['provision']['label'] = _txt('ef.step.provision.notify');
    }

    // Set values for the OIS related sub-steps
    $ret['selectOrgIdentityAuthenticate'] = $ret['selectOrgIdentity'];
    $ret['collectIdentifierIdentify'] = $ret['collectIdentifier'];
    
    return $ret;
  }
  
  /**
   * Duplicate an existing Enrollment Flow.
   *
   * @since  COmanage Registry v0.9.2
   * @param  Integer $id CO Enrollment Flow ID
   * @return Boolean True on success
   * @throws InvalidArgumentException
   * @throws RuntimeException
   */
  
  public function duplicate($id) {
    // First pull all the stuff we'll need to copy.
    
    $args = array();
    $args['conditions']['CoEnrollmentFlow.id'] = $id;
    $args['contain']['CoEnrollmentAttribute'][] = 'CoEnrollmentAttributeDefault';
    $args['contain'][] = 'CoEnrollmentSource';
    
    // This find will not pull archived or deleted attributes (as managed via
    // Changelog behavior), which seems about right. However, we'll want to clear
    // the attribute changelog metadata, below.
    $ef = $this->find('first', $args);
    
    if(empty($ef)) {
      throw new InvalidArgumentException(_txt('er.notfound', array(_txt('ct.co_enrollment_flows.1'), $id)));
    }
    
    // We need to rename the flow
    
    $ef['CoEnrollmentFlow']['name'] = _txt('fd.copy-a', array($ef['CoEnrollmentFlow']['name']));
    
    // And remove all the keys (Cake will re-key on save)
    
    unset($ef['CoEnrollmentFlow']['id']);
    unset($ef['CoEnrollmentFlow']['created']);
    unset($ef['CoEnrollmentFlow']['modified']);
    
    for($i = 0;$i < count($ef['CoEnrollmentAttribute']);$i++) {
      unset($ef['CoEnrollmentAttribute'][$i]['id']);
      unset($ef['CoEnrollmentAttribute'][$i]['co_enrollment_flow_id']);
      unset($ef['CoEnrollmentAttribute'][$i]['created']);
      unset($ef['CoEnrollmentAttribute'][$i]['modified']);
      // For changelog behavior
      unset($ef['CoEnrollmentAttribute'][$i]['revision']);
      unset($ef['CoEnrollmentAttribute'][$i]['co_enrollment_attribute_id']);
      unset($ef['CoEnrollmentAttribute'][$i]['actor_identifier']);
      
      for($j = 0;$j < count($ef['CoEnrollmentAttribute'][$i]['CoEnrollmentAttributeDefault']);$j++) {
        unset($ef['CoEnrollmentAttribute'][$i]['CoEnrollmentAttributeDefault'][$j]['id']);
        unset($ef['CoEnrollmentAttribute'][$i]['CoEnrollmentAttributeDefault'][$j]['co_enrollment_attribute_id']);
        unset($ef['CoEnrollmentAttribute'][$i]['CoEnrollmentAttributeDefault'][$j]['created']);
        unset($ef['CoEnrollmentAttribute'][$i]['CoEnrollmentAttributeDefault'][$j]['modified']);
        // For changelog behavior
        unset($ef['CoEnrollmentAttribute'][$i]['CoEnrollmentAttributeDefault'][$j]['revision']);
        unset($ef['CoEnrollmentAttribute'][$i]['CoEnrollmentAttributeDefault'][$j]['co_enrollment_attribute_id']);
        unset($ef['CoEnrollmentAttribute'][$i]['CoEnrollmentAttributeDefault'][$j]['actor_identifier']);
      }
    }
    
    for($i = 0;$i < count($ef['CoEnrollmentSource']);$i++) {
      unset($ef['CoEnrollmentSource'][$i]['id']);
      unset($ef['CoEnrollmentSource'][$i]['co_enrollment_flow_id']);
      unset($ef['CoEnrollmentSource'][$i]['created']);
      unset($ef['CoEnrollmentSource'][$i]['modified']);
      // For changelog behavior
      unset($ef['CoEnrollmentSource'][$i]['revision']);
      unset($ef['CoEnrollmentSource'][$i]['co_enrollment_source_id']);
      unset($ef['CoEnrollmentSource'][$i]['actor_identifier']);
    }
    
    // We explicitly disable validation here for a couple of reasons. First, we're
    // copying a record in the database, so the values should already be valid.
    // Second, this isn't always the case, because sometimes the data model gets
    // updated, introducing new fields. When this happens, the existing records
    // may have (eg) a null instead of a false (which validation would expect).
    // There's no real reason to fail to save in such a scenario.
    
    if(!$this->saveAssociated($ef, array('deep' => true, 'validate' => false))) {
      throw new RuntimeException(_txt('er.db.save'));
    }
    
    return true;
  }
  
  /**
   * Perform a keyword search.
   *
   * @since  COmanage Registry v3.1.0
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
          'LOWER(CoEnrollmentFlow.name) LIKE' => '%' . strtolower($t) . '%'
        )
      );
    }
    $args['conditions']['CoEnrollmentFlow.co_id'] = $coId;
    $args['order'] = array('CoEnrollmentFlow.name');
    $args['contain'] = false;
    
    return $this->find('all', $args);
  }
  
  /**
   * Generate a template for an Account Linking based enrollment flow.
   *
   * @param  Integer $coId          CO ID for template
   * @param  Boolean $orgIdentities True if org identity attributes may be collected (CMP setting)
   * @param  Boolean $cous          True if COUs are defined for this CO
   * @return Array Template in the usual Cake format
   */
  
  protected function templateAccountLinking($coId, $orgIdentities, $cous) {
    $ret = array();
    
    // Start with the enrollment flow configuration
    
    $ret['CoEnrollmentFlow'] = array(
      'name'                    => _txt('fd.ef.tmpl.lnk'),
      'co_id'                   => $coId,
      'approval_required'       => false,
      'status'                  => TemplateableStatusEnum::Template,
      'match_policy'            => EnrollmentMatchPolicyEnum::Self,
      'authz_level'             => EnrollmentAuthzEnum::CoPerson,
      'require_authn'           => true,
      'email_verification_mode' => VerificationModeEnum::Review,
      'notify_on_approval'      => false,
      't_and_c_mode'            => TAndCEnrollmentModeEnum::Ignore,
      'ignore_authoritative'    => true
    );
    
    // Define required attributes for this flow -- required here means the
    // flow will fail without these
    
    $ret['CoEnrollmentAttribute'] = array();
    $ordr = 1;
    
    if($orgIdentities) {
      $ret['CoEnrollmentAttribute'][] = array(
        'attribute'             => 'i:name:official',
        'required'              => RequiredEnum::Required,
        'label'                 => _txt('fd.name'),
        'ordr'                  => $ordr++
      );
      
      $ret['CoEnrollmentAttribute'][] = array(
        'attribute'             => 'i:email_address:official',
        'required'              => RequiredEnum::Required,
        'label'                 => _txt('fd.email_address.mail'),
        'ordr'                  => $ordr++
      );
    }
    
    return $ret;
  }
  
  /**
   * Generate a template for an Additional Role administrator based enrollment flow.
   *
   * @param  Integer $coId          CO ID for template
   * @param  Boolean $orgIdentities True if org identity attributes may be collected (CMP setting)
   * @param  Boolean $cous          True if COUs are defined for this CO
   * @return Array Template in the usual Cake format
   */
  
  protected function templateAdditionalRole($coId, $orgIdentities, $cous) {
    $ret = array();
    
    // Start with the enrollment flow configuration
    
    $ret['CoEnrollmentFlow'] = array(
      'name'                    => _txt('fd.ef.tmpl.arl'),
      'co_id'                   => $coId,
      'approval_required'       => false,
      'status'                  => TemplateableStatusEnum::Template,
      'match_policy'            => EnrollmentMatchPolicyEnum::Select,
      'authz_level'             => EnrollmentAuthzEnum::CoOrCouAdmin,
      'email_verification_mode' => VerificationModeEnum::None,
      'notify_on_approval'      => false,
      't_and_c_mode'            => TAndCEnrollmentModeEnum::Ignore
    );
    
    // Define required attributes for this flow -- required here means the
    // flow will fail without these
    
    $ret['CoEnrollmentAttribute'] = array();
    $ordr = 1;
    
    // COU, if COUs are enabled
    
    if($cous) {
      $ret['CoEnrollmentAttribute'][] = array(
        'attribute'             => 'r:cou_id',
        'required'              => RequiredEnum::Required,
        'label'                 => _txt('fd.cou'),
        'ordr'                  => $ordr++
      );
    }
    
    // CO Person Role affiliation
    
    $ret['CoEnrollmentAttribute'][] = array(
      'attribute'             => 'r:affiliation',
      'required'              => RequiredEnum::Required,
      'label'                 => _txt('fd.affiliation'),
      'ordr'                  => $ordr++
    );
    
    // Define additional attributes for this flow -- while we flag these as
    // optional, a deployer will likely want to flip them to required
    
    $ret['CoEnrollmentAttribute'][] = array(
      'attribute'             => 'r:title',
      'required'              => RequiredEnum::Optional,
      'label'                 => _txt('fd.title'),
      'ordr'                  => $ordr++
    );
    
    $ret['CoEnrollmentAttribute'][] = array(
      'attribute'             => 'r:sponsor_co_person_id',
      'required'              => RequiredEnum::Optional,
      'label'                 => _txt('fd.sponsor'),
      'ordr'                  => $ordr++
    );
    
    $ret['CoEnrollmentAttribute'][] = array(
      'attribute'             => 'r:valid_from',
      'required'              => RequiredEnum::Optional,
      'label'                 => _txt('fd.valid_from'),
      'ordr'                  => $ordr++
    );
    
    $ret['CoEnrollmentAttribute'][] = array(
      'attribute'             => 'r:valid_through',
      'required'              => RequiredEnum::Optional,
      'label'                 => _txt('fd.valid_through'),
      'ordr'                  => $ordr++
    );
    
    return $ret;
  }
  
  /**
   * Generate a template for a Conscription based enrollment flow.
   *
   * @param  Integer $coId          CO ID for template
   * @param  Boolean $orgIdentities True if org identity attributes may be collected (CMP setting)
   * @param  Boolean $cous          True if COUs are defined for this CO
   * @return Array Template in the usual Cake format
   */
  
  protected function templateConscriptionApproval($coId, $orgIdentities, $cous) {
    $ret = array();
    
    // Start with the enrollment flow configuration
    
    $ret['CoEnrollmentFlow'] = array(
      'name'                    => _txt('fd.ef.tmpl.csp'),
      'co_id'                   => $coId,
      'approval_required'       => true,
      'status'                  => TemplateableStatusEnum::Template,
      'match_policy'            => EnrollmentMatchPolicyEnum::Advisory,
      'authz_level'             => EnrollmentAuthzEnum::CoOrCouAdmin,
      'email_verification_mode' => VerificationModeEnum::None,
      'notify_on_approval'      => false,
      't_and_c_mode'            => TAndCEnrollmentModeEnum::Ignore
    );
    
    // Define required attributes for this flow -- required here means the
    // flow will fail without these
    
    $ret['CoEnrollmentAttribute'] = array();
    $ordr = 1;
    
    // COU, if COUs are enabled
    
    if($cous) {
      $ret['CoEnrollmentAttribute'][] = array(
        'attribute'             => 'r:cou_id',
        'required'              => RequiredEnum::Required,
        'label'                 => _txt('fd.cou'),
        'ordr'                  => $ordr++
      );
    }
    
    // If org identity attributes are enabled, define a name field and require
    // an email address
    
    if($orgIdentities) {
      $ret['CoEnrollmentAttribute'][] = array(
        'attribute'             => 'i:name:official',
        'required'              => RequiredEnum::Required,
        'label'                 => _txt('fd.name'),
        'ordr'                  => $ordr++
      );
      
      $ret['CoEnrollmentAttribute'][] = array(
        'attribute'             => 'i:email_address:official',
        'required'              => RequiredEnum::Required,
        'label'                 => _txt('fd.email_address.mail'),
        'ordr'                  => $ordr++
      );
    }
    
    // Collect a separate CO Person name
    $ret['CoEnrollmentAttribute'][] = array(
      'attribute'             => 'p:name:official',
      'required'              => RequiredEnum::Required,
      'label'                 => _txt('fd.name') . ' (' . _txt('en.name.type', null, NameEnum::Preferred). ')',
      'ordr'                  => $ordr++
    );
    
    // CO Person Role affiliation
    
    $ret['CoEnrollmentAttribute'][] = array(
      'attribute'             => 'r:affiliation',
      'required'              => RequiredEnum::Required,
      'label'                 => _txt('fd.affiliation'),
      'ordr'                  => $ordr++
    );
    
    // Define additional attributes for this flow -- while we flag these as
    // optional, a deployer will likely want to flip them to required
    
    if($orgIdentities) {
      $ret['CoEnrollmentAttribute'][] = array(
        'attribute'             => 'o:o',
        'required'              => RequiredEnum::Optional,
        'label'                 => _txt('fd.o'),
        'ordr'                  => $ordr++
      );
    }
    
    $ret['CoEnrollmentAttribute'][] = array(
      'attribute'             => 'r:title',
      'required'              => RequiredEnum::Optional,
      'label'                 => _txt('fd.title'),
      'ordr'                  => $ordr++
    );
    
    $ret['CoEnrollmentAttribute'][] = array(
      'attribute'             => 'r:sponsor_co_person_id',
      'required'              => RequiredEnum::Optional,
      'label'                 => _txt('fd.sponsor'),
      'ordr'                  => $ordr++
    );
    
    $ret['CoEnrollmentAttribute'][] = array(
      'attribute'             => 'r:valid_from',
      'required'              => RequiredEnum::Optional,
      'label'                 => _txt('fd.valid_from'),
      'ordr'                  => $ordr++
    );
    
    $ret['CoEnrollmentAttribute'][] = array(
      'attribute'             => 'r:valid_through',
      'required'              => RequiredEnum::Optional,
      'label'                 => _txt('fd.valid_through'),
      'ordr'                  => $ordr++
    );
    
    $ret['CoEnrollmentAttribute'][] = array(
      'attribute'             => 'm:telephone_number:mobile',
      'required'              => RequiredEnum::Optional,
      'label'                 => _txt('fd.telephone_number.number') . ' (' . _txt('en.telephone_number.type', null, ContactEnum::Mobile) . ')',
      'ordr'                  => $ordr++
    );
    
    return $ret;
  }
  
  /**
   * Generate a template for an Invitation based enrollment flow.
   *
   * @param  Integer $coId          CO ID for template
   * @param  Boolean $orgIdentities True if org identity attributes may be collected (CMP setting)
   * @param  Boolean $cous          True if COUs are defined for this CO
   * @return Array Template in the usual Cake format
   */
  
  protected function templateInvitation($coId, $orgIdentities, $cous) {
    $ret = array();
    
    // Start with the enrollment flow configuration
    
    $ret['CoEnrollmentFlow'] = array(
      'name'                    => _txt('fd.ef.tmpl.inv'),
      'co_id'                   => $coId,
      'approval_required'       => false,
      'status'                  => TemplateableStatusEnum::Template,
      'match_policy'            => EnrollmentMatchPolicyEnum::None,
      'authz_level'             => EnrollmentAuthzEnum::CoOrCouAdmin,
      'email_verification_mode' => VerificationModeEnum::Review,
      'notify_on_approval'      => true,
      't_and_c_mode'            => TAndCEnrollmentModeEnum::Ignore
    );
    
    // Define required attributes for this flow -- required here means the
    // flow will fail without these
    
    $ret['CoEnrollmentAttribute'] = array();
    $ordr = 1;
    
    // COU, if COUs are enabled
    
    if($cous) {
      $ret['CoEnrollmentAttribute'][] = array(
        'attribute'             => 'r:cou_id',
        'required'              => RequiredEnum::Required,
        'label'                 => _txt('fd.cou'),
        'ordr'                  => $ordr++
      );
    }
    
    // If org identity attributes are enabled, define a name field and copy it
    // to the CO Person, otherwise define a CO Person Name
    
    if($orgIdentities) {
      $ret['CoEnrollmentAttribute'][] = array(
        'attribute'             => 'i:name:official',
        'required'              => RequiredEnum::Required,
        'label'                 => _txt('fd.name'),
        'ordr'                  => $ordr++,
        'copy_to_coperson'      => true
      );
    } else {
      $ret['CoEnrollmentAttribute'][] = array(
        'attribute'             => 'p:name:official',
        'required'              => RequiredEnum::Required,
        'label'                 => _txt('fd.name'),
        'ordr'                  => $ordr++
      );
    }
    
    // If org identity attributes are enabled, require an email address
    
    if($orgIdentities) {
      $ret['CoEnrollmentAttribute'][] = array(
        'attribute'             => 'i:email_address:official',
        'required'              => RequiredEnum::Required,
        'label'                 => _txt('fd.email_address.mail'),
        'ordr'                  => $ordr++
      );
    }
    
    // CO Person Role affiliation
    
    $ret['CoEnrollmentAttribute'][] = array(
      'attribute'             => 'r:affiliation',
      'required'              => RequiredEnum::Required,
      'label'                 => _txt('fd.affiliation'),
      'ordr'                  => $ordr++
    );
    
    // Define additional attributes for this flow -- while we flag these as
    // optional, a deployer will likely want to flip them to required
    
    if($orgIdentities) {
      $ret['CoEnrollmentAttribute'][] = array(
        'attribute'             => 'o:o',
        'required'              => RequiredEnum::Optional,
        'label'                 => _txt('fd.o'),
        'ordr'                  => $ordr++
      );
    }
    
    $ret['CoEnrollmentAttribute'][] = array(
      'attribute'             => 'r:title',
      'required'              => RequiredEnum::Optional,
      'label'                 => _txt('fd.title'),
      'ordr'                  => $ordr++
    );
    
    return $ret;
  }
  
  /**
   * Generate a template for a Self Signup Approval enrollment flow.
   *
   * @param  Integer $coId          CO ID for template
   * @param  Boolean $orgIdentities True if org identity attributes may be collected (CMP setting)
   * @param  Boolean $cous          True if COUs are defined for this CO
   * @return Array Template in the usual Cake format
   */
  
  protected function templateSelfSignupApproval($coId, $orgIdentities, $cous) {
    $ret = array();
    
    // Start with the enrollment flow configuration
    
    $ret['CoEnrollmentFlow'] = array(
      'name'                    => _txt('fd.ef.tmpl.ssu'),
      'co_id'                   => $coId,
      'approval_required'       => true,
      'status'                  => TemplateableStatusEnum::Template,
      'match_policy'            => EnrollmentMatchPolicyEnum::None,
      'authz_level'             => EnrollmentAuthzEnum::None,
      'email_verification_mode' => VerificationModeEnum::Review,
      'notify_on_approval'      => true,
      't_and_c_mode'            => TAndCEnrollmentModeEnum::ImpliedConsent
    );
    
    // Define required attributes for this flow -- required here means the
    // flow will fail without these
    
    $ret['CoEnrollmentAttribute'] = array();
    $ordr = 1;
    
    // COU, if COUs are enabled
    
    if($cous) {
      $ret['CoEnrollmentAttribute'][] = array(
        'attribute'             => 'r:cou_id',
        'required'              => RequiredEnum::Required,
        'label'                 => _txt('fd.cou'),
        'ordr'                  => $ordr++
      );
    }
    
    // If org identity attributes are enabled, define a name field and copy it
    // to the CO Person, otherwise define a CO Person Name
    
    if($orgIdentities) {
      $ret['CoEnrollmentAttribute'][] = array(
        'attribute'             => 'i:name:official',
        'required'              => RequiredEnum::Required,
        'label'                 => _txt('fd.name'),
        'ordr'                  => $ordr++,
        'copy_to_coperson'      => true
      );
    } else {
      $ret['CoEnrollmentAttribute'][] = array(
        'attribute'             => 'p:name:official',
        'required'              => RequiredEnum::Required,
        'label'                 => _txt('fd.name'),
        'ordr'                  => $ordr++
      );
    }
    
    // If org identity attributes are enabled, require an email address
    
    if($orgIdentities) {
      $ret['CoEnrollmentAttribute'][] = array(
        'attribute'             => 'i:email_address:official',
        'required'              => RequiredEnum::Required,
        'label'                 => _txt('fd.email_address.mail'),
        'ordr'                  => $ordr++
      );
    }
    
    // CO Person Role affiliation
    
    $ret['CoEnrollmentAttribute'][] = array(
      'attribute'             => 'r:affiliation',
      'required'              => RequiredEnum::Required,
      'label'                 => _txt('fd.affiliation'),
      'ordr'                  => $ordr++
    );
    
    // Define additional attributes for this flow -- while we flag these as
    // optional, a deployer will likely want to flip them to required
    
    if($orgIdentities) {
      $ret['CoEnrollmentAttribute'][] = array(
        'attribute'             => 'o:o',
        'required'              => RequiredEnum::Optional,
        'label'                 => _txt('fd.o'),
        'ordr'                  => $ordr++
      );
    }
    
    return $ret;
  }
}
