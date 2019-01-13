<?php
/**
 * COmanage Registry CO Petition Controller
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
 * @since         COmanage Registry v0.5
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::uses("StandardController", "Controller");

class CoPetitionsController extends StandardController {
  public $name = "CoPetitions";
  
  public $helpers = array('Time');
  
  // When using additional models, we must also specify our own
  public $uses = array('CoPetition', 'CmpEnrollmentConfiguration', 'OrgIdentitySource');
  
  public $paginate = array(
    'limit' => 25,
    'link' => array(
      'ApproverCoPerson' => array(
        'class' => 'CoPerson',
        'ApproverPrimaryName' => array(
          'class' => 'Name',
          'conditions' => array(
            // Linkable behavior doesn't seem to be able to handle multiple joins
            // against the same table, so we manually specify the join condition for
            // each name. We then have to explicitly filter on primary name so as
            // not to produce multiple rows in the join for alternate names the
            // CO Person might have.
            'exactly' => 'ApproverPrimaryName.co_person_id = ApproverCoPerson.id AND ApproverPrimaryName.primary_name = true'
          )
        )
      ),
      'CoEnrollmentFlow',
      'Cou',
      'EnrolleeCoPerson' => array(
        'EnrolleePrimaryName' => array(
          'class' => 'Name',
          'conditions' => array(
            'exactly' => 'EnrolleePrimaryName.co_person_id = EnrolleeCoPerson.id AND EnrolleePrimaryName.primary_name = true')
        )
      ),
      'PetitionerCoPerson' => array(
        'class' => 'CoPerson',
        'PetitionerPrimaryName' => array(
          'class' => 'Name',
          'conditions' => array(
            'exactly' => 'PetitionerPrimaryName.co_person_id = PetitionerCoPerson.id AND PetitionerPrimaryName.primary_name = true')
        )
      ),
      'SponsorCoPerson' => array(
        'class' => 'CoPerson',
        'SponsorPrimaryName' => array(
          'class' => 'Name',
          'conditions' => array(
            'exactly' => 'SponsorPrimaryName.co_person_id = SponsorCoPerson.id AND SponsorPrimaryName.primary_name = true')
        )
      )
    ),
    'order' => array(
      'modified' => 'desc'
    ),
    // contain moved to linkable for CO-896, don't restore since it blanks out associations (breaking linkable)
    'contain' => false
  );
  
  // This controller needs a CO to be set
  public $requires_co = true;
  
  // For rendering views, we need more information than just the various ID numbers
  // stored in a petition.
  public $view_contains = array(
    'ApproverCoPerson' => 'PrimaryName',
    'EnrolleeCoPerson' => 'PrimaryName',
    'EnrolleeOrgIdentity' => 'PrimaryName',
    'PetitionerCoPerson' => 'PrimaryName',
    'SponsorCoPerson' => 'PrimaryName',
    'CoPetitionHistoryRecord' => array(
      'ActorCoPerson' => array(
        'PrimaryName'
      )
    ),
    'CoEnrollmentFlow',
    'CoInvite',
    'Cou',
    'OrgIdentitySourceRecord' => array(
      'OrgIdentity' => 'PrimaryName',
      'OrgIdentitySource'
    )
  );
  
  // Cached copy of enrollment flow ID, once determined
  protected $cachedEnrollmentFlowID = -1;
  
  // Index of next steps. This ordering may be a bit unintuitive, since often a
  // step leads to a next step when the predecessor is not configured to run.
  // There are also steps that result in temporarily exiting the flow, so what
  // appears to be the next step doesn't necessarily actually run.
  
  // Be very careful before changing the order of these steps, or inserting new ones.
  
  // Here are the required tasks when adding a new step:
  // - Figure out the correct ordering of the step and insert it into $nextSteps
  // -- dispatch() has special logic for the last step (ie: provision) that will need updating
  //    if insterting a step after "provision"
  // - Update CoEnrollmentFlow::configuredSteps()
  // - Add an appropriate STEP function (eg: approve()), and update isAuthorized()
  // - Add an appropriate execute_STEP function (eg: execute_approve())
  // -- Be sure to disable provisioning for each save if the new step runs before provision, see
  //    https://spaces.internet2.edu/display/COmanage/Provisioning+From+Registry#ProvisioningFromRegistry-AutomaticProvisioning
  // - Add a language key for 'ef.step.STEP' (eg: 'ef.step.approve')
  // - Update the documentation at https://spaces.internet2.edu/pages/viewpage.action?pageId=87756108
  // - Update the diagram at https://spaces.internet2.edu/display/COmanage/Registry+Enrollment+Flow+Diagram
  
  protected $nextSteps = array(
    // We run selectEnrollee before selectOrgIdentity so that OIS pipelines
    // can be forced to match an already selected CO Person (CO-1299).
    'start'                        => 'selectEnrollee',
    'selectEnrollee'               => 'selectOrgIdentity',
    'selectOrgIdentity'            => 'petitionerAttributes',
    'petitionerAttributes'         => 'sendConfirmation',
    'sendConfirmation'             => 'waitForConfirmation',
    // execution continues here if confirmation not required
    'waitForConfirmation'          => 'checkEligibility',
    'checkEligibility'             => 'sendApproverNotification',
    // We have both redirectOnConfirm and waitForApproval because depending on the
    // confirmation we might have different paths to completing the processConfirmation step
    'sendApproverNotification'     => 'waitForApproval',
    // If approval is not required, then we'll continue here
    'waitForApproval'              => 'finalize',
    // execution continues at finalize if approval not required
    // processConfirmation is re-entry point following confirmation
    'processConfirmation'          => 'collectIdentifier',
    'collectIdentifier'            => 'checkEligibility',
    // approve is re-entry point following approval
    'approve'                      => 'finalize',
    'finalize'                     => 'provision',
    'provision'                    => 'redirectOnConfirm'
  );
  
  /**
   * Add a CO Petition.
   *
   * @since  COmanage Registry v0.5
   * @throws RuntimeException
   */

  function add() {
    if(!$this->request->is('restful')) {
      // For compatibility reasons, redirect to /start. (This can ultimately be tossed.)
      
      if(!empty($this->request->params['named']['coef'])) {
        $redirect = array(
          'controller' => 'co_petitions',
          'action'     => 'start',
          'coef'       => $this->request->params['named']['coef']
        );
        
        $this->redirect($redirect);
      }
    } else {
      // REST API gets standard behavior
      
      parent::add();
    }
  }
  
  /**
   * Approve a petition.
   *
   * @since  COmanage Registry v0.5
   * @param  Integer Petition ID
   */
  
  public function approve($id) {
    $this->dispatch('approve', $id);
  }
  
  /**
   * Callback before other controller methods are invoked or views are rendered.
   * - postcondition: If invalid enrollment flow provided, session flash message set
   *
   * @since  COmanage Registry v0.5
   */
  
  function beforeFilter() {
    $steps = null;
    
    if($this->enrollmentFlowID() > -1) {
      $steps = $this->CoPetition->CoEnrollmentFlow->configuredSteps($this->enrollmentFlowID());
    }
    
    if(!$this->request->is('restful')) {
      if(!in_array($this->action, array('approve', 'finalize', 'provision', 'redirectOnConfirm', 'start', 'view'))) {
        // If the petition is Finalized (or Denied/Declined), no further actions are permitted
        // (except the post processing actions of provisioning and redirection). We also need
        // to allow approve and finalize so plugins can run.
        
        $status = $this->CoPetition->field('status', array('CoPetition.id' => $this->parseCoPetitionId()));
        
        if($status && in_array($status, 
                               array(PetitionStatusEnum::Declined,
                                     PetitionStatusEnum::Denied,
                                     PetitionStatusEnum::Duplicate,
                                     PetitionStatusEnum::Finalized))) {
          $this->Flash->set(_txt('er.pt.readonly'), array($status));
          $this->redirect("/");
        }
      }
      
      // Under certain circumstances, we may wish to drop authentication.
      $noAuth = false;
      
      if($this->action == 'add') {
        // add just redirects to start
        $noAuth = true;
      } elseif($this->action == 'index') {
        // In order to search for petitions by Org Identity, we may need to not require a CO
        // (ie: if org identities are pooled)
        
        $pool = $this->CmpEnrollmentConfiguration->orgIdentitiesPooled();
        
        if($pool) {
          $this->requires_co = false;
        }
        
        $this->set('pool_org_identities', $pool);
      } elseif(isset($steps[$this->action])) {
        if($steps[$this->action]['role'] == EnrollmentRole::Petitioner
           || $steps[$this->action]['role'] == EnrollmentRole::Enrollee) {
          // Pull the enrollment flow configuration to determine what we should do
          
          $args = array();
          $args['conditions']['CoEnrollmentFlow.id'] = $this->enrollmentFlowID();
          $args['contain'] = false;
          
          $ef = $this->CoPetition->CoEnrollmentFlow->find('first', $args);
          
          if(empty($ef)) {
            $this->Flash->set(_txt('er.coef.unk'), array('key' => 'error'));
          } elseif($steps[$this->action]['role'] == EnrollmentRole::Petitioner
                   && isset($ef['CoEnrollmentFlow']['authz_level'])
                   && ($ef['CoEnrollmentFlow']['authz_level'] == EnrollmentAuthzEnum::None
                       // We special case AuthUser as well since an authenticated but unregistered
                       // user will not have a valid CO record yet
                       || $ef['CoEnrollmentFlow']['authz_level'] == EnrollmentAuthzEnum::AuthUser)
                   // We need isAuthorized() to run to populate $permissions
                   && $this->isAuthorized()) {
            // This enrollment flow doesn't require authentication for the petitioner.
            // Drop it completely for the 'start' step. For the others, we should have
            // a token that matches the token in the petition.
            
            if($this->action == 'start') {
              $noAuth = true;
            } else {
              // Once we have an authenticated identifier we no longer accept tokens.
              // We don't explicitly throw an error because we'll ultimately want to
              // support petition editing (CO-431).
              $authId = $this->CoPetition->field('authenticated_identifier', array('CoPetition.id' => $this->parseCoPetitionId()));
              
              if(!$authId) {
                $token = $this->CoPetition->field('petitioner_token', array('CoPetition.id' => $this->parseCoPetitionId()));
                $passedToken = $this->parseToken();
                
                if($token && $token != '' && $passedToken
                   && $token == $passedToken) {
                  $noAuth = true;
                  
                  // Dump the token into a viewvar in case needed
                  $this->set('vv_petition_token', $token);
                } else {
                  $this->Flash->set(_txt('er.token'), array('key' => 'error'));
                  $this->redirect("/");
                }
              }
            }
          } elseif($steps[$this->action]['role'] == EnrollmentRole::Enrollee
                   && (!isset($ef['CoEnrollmentFlow']['require_authn'])
                       || !$ef['CoEnrollmentFlow']['require_authn'])
                   // We need isAuthorized() to run to populate $permissions
                   && $this->isAuthorized()) {
            // This enrollment flow doesn't require authentication for the enrollee.
            // Redirected from CO Invites controller, we should have a token that
            // matches the token in the petition.
            
            // Once we have an authenticated identifier we no longer accept tokens
            // We don't explicitly throw an error because we'll ultimately want to
            // support petition editing (CO-431).
            $authId = $this->CoPetition->field('authenticated_identifier', array('CoPetition.id' => $this->parseCoPetitionId()));
            
            if(!$authId) {
              $token = $this->CoPetition->field('enrollee_token', array('CoPetition.id' => $this->parseCoPetitionId()));
              $passedToken = $this->parseToken();
              
              if($token && $token != '' && $passedToken
                 && $token == $passedToken) {
                $noAuth = true;
                
                // Dump the token into a viewvar in case needed
                $this->set('vv_petition_token', $token);
              } else {
                $this->Flash->set(_txt('er.token'), array('key' => 'error'));
                $this->redirect("/");
              }
            }
          }
        }
      }
      
      if($noAuth) {
        $this->Auth->allow($this->action);
        
        if(!$this->Session->check('Auth.User.name')) {
          // If authentication is not required, and we're not authenticated as
          // a valid user, hide the login/logout button to minimize confusion
          
          $this->set('noLoginLogout', true);
        }
      }
    }
    
    parent::beforeFilter();
    
    // Dynamically adjust validation rules to include the current CO ID for dynamic types.
    
    $vrule = $this->CoPetition->EnrolleeCoPerson->Identifier->validate['type']['content']['rule'];
    $vrule[1]['coid'] = $this->cur_co['Co']['id'];
    
    $this->CoPetition->EnrolleeCoPerson->Identifier->validator()->getField('type')->getRule('content')->rule = $vrule;
    
    if(!empty($this->viewVars['vv_tz'])) {
      // Set the current timezone, primarily for saveAttributes()
      $this->CoPetition->setTimeZone($this->viewVars['vv_tz']);
    }
  }
  
  /**
   * Callback after controller methods are invoked but before views are rendered.
   * - precondition: Request Handler component has set $this->request->params
   * - postcondition: If a CO must be specifed, a named parameter may be set.
   * - postcondition: $co_enrollment_attributes may be set.
   *
   * @since  COmanage Registry v0.5
   */
  
  function beforeRender() {
    // As a general rule, any viewvars that needs to be used by /view as well as one
    // or more execute_ steps should be set here.
    
    if(!$this->request->is('restful')) {
      $enrollmentFlowID = $this->enrollmentFlowID();
      
      // Set the enrollment flow ID to make it easier to carry forward through failed submissions
      $this->set('co_enrollment_flow_id', $enrollmentFlowID);
      $this->set('vv_co_petition_id', $this->parseCoPetitionId());
      
      if(in_array($this->action, array('petitionerAttributes', 'view'))) {
        $defaultValues = array();
        
        if($enrollmentFlowID) {
          // Provide default values for name for self enrollment.
          
          $p['match_policy'] = $this->CoPetition->CoEnrollmentFlow->field('match_policy',
                                                                          array('CoEnrollmentFlow.id' => $enrollmentFlowID));
          
          if($p['match_policy'] == EnrollmentMatchPolicyEnum::Self) {
            $defName = $this->Session->read('Auth.User.name');
            
            if(!empty($defName)) {
              // Populate select attributes only
              $defaultValues['EnrolleeOrgIdentity.Name']['honorific'] = $defName['honorific'];
              $defaultValues['EnrolleeOrgIdentity.Name']['given'] = $defName['given'];
              $defaultValues['EnrolleeOrgIdentity.Name']['middle'] = $defName['middle'];
              $defaultValues['EnrolleeOrgIdentity.Name']['family'] = $defName['family'];
              $defaultValues['EnrolleeOrgIdentity.Name']['suffix'] = $defName['suffix'];
            }
          }
        }
        
        $this->loadModel('CmpEnrollmentConfiguration');
        
        $envValues = false;
        $enrollmentAttributes = $this->CoPetition
                                     ->CoEnrollmentFlow
                                     ->CoEnrollmentAttribute
                                     ->enrollmentFlowAttributes($this->enrollmentFlowID(),
                                                                $defaultValues,
                                                                // For viewing a petition, we want the archived attributes as well
                                                                $this->action == 'view');
        
        if($this->action == 'view') {
          // Pull the current attribute values
          $vArgs = array();
          $vArgs['conditions']['CoPetitionAttribute.co_petition_id'] = $this->CoPetition->id;
          $vArgs['fields'] = array(
            'CoPetitionAttribute.attribute',
            'CoPetitionAttribute.value',
            'CoPetitionAttribute.co_enrollment_attribute_id'
          );
          $vAttrs = $this->CoPetition->CoPetitionAttribute->find("list", $vArgs);
          
          $this->set('co_petition_attribute_values', $vAttrs);
          
          // For viewing a petition, we want the attributes defined at the time the
          // petition attributes were submitted. This turns out to be somewhat
          // complicated to determine, so we hand it off for filtering.
          
          // We need a slightly different set of data here. Strictly speaking we
          // should do a select distinct, but practically it won't matter since
          // all petition attributes for a given enrollment attribute will have
          // approximately the same created time.
          
          // This is duplicated in CoInvitesController.
          $vArgs = array();
          $vArgs['conditions']['CoPetitionAttribute.co_petition_id'] = $this->CoPetition->id;
          $vArgs['fields'] = array(
            'CoPetitionAttribute.co_enrollment_attribute_id',
            'CoPetitionAttribute.created'
          );
          $vAttrs = $this->CoPetition->CoPetitionAttribute->find("list", $vArgs);
          
          $enrollmentAttributes = $this->CoPetition->filterHistoricalAttributes($enrollmentAttributes, $vAttrs);
        }
        
        if($this->action != 'view') {
          // Deprecated for removal in 4.0.0
          if($this->CmpEnrollmentConfiguration->orgIdentitiesFromCOEF()) {
            // If enrollment flows can populate org identities, then see if we're configured
            // to pull environment variables. If so, for this configuration they simply
            // replace modifiable default values.
            
            $envValues = $this->CmpEnrollmentConfiguration->enrollmentAttributesFromEnv();
            
            if($envValues) {
              // This flow might be configured to ignore authoritative values
              $ignoreAuthValues = $this->CoPetition
                                       ->CoEnrollmentFlow->field('ignore_authoritative',
                                                                 array('CoEnrollmentFlow.id' => $enrollmentFlowID));
              
              if(!$ignoreAuthValues) {
                $enrollmentAttributes = $this->CoPetition
                                             ->CoEnrollmentFlow
                                             ->CoEnrollmentAttribute
                                             ->mapEnvAttributes($enrollmentAttributes,
                                                                $envValues);
              }
            }
          }
          
          $enrollmentAttributes = $this->CoPetition
                                       ->CoEnrollmentFlow
                                       ->CoEnrollmentAttribute
                                       ->mapEnvAttributes($enrollmentAttributes, array());
        }
        
        $this->set('co_enrollment_attributes', $enrollmentAttributes);
      }
      
      if(in_array($this->action, array('petitionerAttributes', 'view'))) {
        // Pull any relevant Terms and Conditions that must be agreed to. We only do this
        // if authentication is required (otherwise we can't really assert who agreed),
        // and only for CO-wide T&C (ie: those without a COU ID specified). There's not
        // necessarily a reason why we couldn't prompt for COU specific T&C, if the petition
        // adjusted dynamically to the COU being enrolled in, but we don't have a use case
        // for it at the moment.
        
        $authn = $this->CoPetition->CoEnrollmentFlow->field('require_authn',
                                                            array('CoEnrollmentFlow.id' => $enrollmentFlowID));
        
        // We'll also run T&C if authz != None, since we'll have an authenticated user that way, too
        $authz = $this->CoPetition->CoEnrollmentFlow->field('authz_level',
                                                            array('CoEnrollmentFlow.id' => $enrollmentFlowID));
        
        if($authn || $authz != EnrollmentAuthzEnum::None) {
          $tArgs = array();
          $tArgs['conditions']['CoTermsAndConditions.co_id'] = $this->cur_co['Co']['id'];
          $tArgs['conditions']['CoTermsAndConditions.cou_id'] = null;
          $tArgs['conditions']['CoTermsAndConditions.status'] = SuspendableStatusEnum::Active;
          $tArgs['contain'] = false;
          
          $this->set('vv_terms_and_conditions',
                     $this->CoPetition->Co->CoTermsAndConditions->find('all', $tArgs));
          
          // Also pass through the T&C Mode
          
          $tcmode = $this->CoPetition
                         ->CoEnrollmentFlow->field('t_and_c_mode',
                                                   array('CoEnrollmentFlow.id' => $enrollmentFlowID));
          
          $this->set('vv_tandc_mode', (!empty($tcmode) ? $tcmode : TAndCEnrollmentModeEnum::ExplicitConsent));
        }
      }
      
      if($enrollmentFlowID > -1 && !isset($this->viewVars['vv_configured_steps'])) {
        // This might have been set in dispatch()
        $this->set('vv_configured_steps', $this->CoPetition->CoEnrollmentFlow->configuredSteps($enrollmentFlowID));
      }
    }
    
    parent::beforeRender();
  }
  
  /**
   * Determine the CO ID based on some attribute of the request.
   * This method is intended to be overridden by model-specific controllers.
   *
   * @since  COmanage Registry v0.8.5
   * @return Integer CO ID, or null if not implemented or not applicable.
   * @throws InvalidArgumentException
   */
  
  protected function calculateImpliedCoId($data = null) {
    if($this->enrollmentFlowID() != -1
       && ($this->action == 'add'  // Leave add for now since it redirects to start
           || in_array($this->action, array_keys($this->nextSteps)))) {
      // Map enrollment flow ID to CO
      
      $coId = $this->CoPetition->CoEnrollmentFlow->field('co_id',
                                                         array('id' => $this->enrollmentFlowID()));
  
      if($coId) {
        return $coId;
      } else {
        throw new InvalidArgumentException(_txt('er.coef.unk'));
      }
    }
    
    // Or try the default behavior
    return parent::calculateImpliedCoId();
  }
  
  /**
   * Check eligibility prior to approval
   *
   * @since  COmanage Registry v2.0.0
   * @param  Integer $id CO Petition ID
   */
  
  public function checkEligibility($id) {
    $this->dispatch('checkEligibility', $id);
  }
  
  /**
   * Collect identifiers following email confirmation
   *
   * @since  COmanage Registry v0.9.4
   * @param  Integer $id CO Petition ID
   */
  
  public function collectIdentifier($id) {
    $this->dispatch('collectIdentifier', $id);
  }
  
  /**
   * Execute an OIS plugin in Identify mode
   *
   * @since  COmanage Registry v3.1.0
   * @param  Integer $id CO Petition ID
   */
  
  public function collectIdentifierIdentify($id) {
    // This is similar to selectOrgIdentityAuthenticate, perhaps they should be merged...
    
    // At this point we're running within a plugin (which extends CoPetitionsController).
    // Pull some configuration data and pass off to the plugin's entry point.
    
    $args = array();
    $args['conditions']['OrgIdentitySource.id'] = $this->request->params['named']['oisid'];
    $args['contain'] = false;
    
    $oiscfg = $this->OrgIdentitySource->find('first', $args);
    
    if(empty($oiscfg)) {
      $this->Flash->set(_txt('er.notfound',
                             array('ct.org_identity_sources.1', filter_var($this->request->params['named']['oisid'],FILTER_SANITIZE_SPECIAL_CHARS))),
                        array('key' => 'error'));
      $this->redirect("/");
    }
    
    // Determine the current CO Person ID, who at this point is presumed to be the
    // Actor since we're in an Enrollee driven phase of enrollment.
   
    $enrolleeCoPersonId = $this->CoPetition->field('enrollee_co_person_id', array('CoPetition.id' => $id));
    
    // Construct a redirect URL
    $onFinish = $this->generateDoneRedirect('collectIdentifier', $id, null, $oiscfg['OrgIdentitySource']['id']);
    $this->set('vv_on_finish_url', $onFinish);
    
    $fname = "execute_plugin_collectIdentifierIdentify";
    
    try {
      $this->$fname($id, $oiscfg, $onFinish, $enrolleeCoPersonId);
    }
    catch(Exception $e) {
      $this->Flash->set($e->getMessage(), array('key' => 'error'));
      
      // Log the error into the petition history
      $this->CoPetition
           ->CoPetitionHistoryRecord
           ->record($id,
                    $enrolleeCoPersonId,
                    PetitionActionEnum::StepFailed,
                    $e->getMessage());
           
      $this->performRedirect(); 
    }
    
    // Make sure we don't issue a redirect
    return;
  }
  
  /**
   * Dispatch a step. This function will determine what step is being executed
   * and call the appropriate execute_ function, handoff to a plugin, or redirect
   * to the next step, as appropriate.
   *
   * @since  COmanage Registry v0.9.4
   * @param  String $step Current step name
   * @param  Integer $id CO Petition ID, if known
   */
  
  protected function dispatch($step, $id=null) {
    // Determine the relevant enrollment flow ID
    $efId = $this->enrollmentFlowID();
    
    if($efId == -1) {
      $this->Flash->set(_txt('er.coef.unk'), array('key' => 'error'));
      $this->performRedirect();
    }
    
    // Make sure this enrollment flow is active
    $status = $this->CoPetition->CoEnrollmentFlow->field('status',
                                                         array('CoEnrollmentFlow.id' => $efId));
    
    if($status != TemplateableStatusEnum::Active) {
      $this->Flash->set(_txt('er.ef.active'), array('key' => 'error'));
      $this->performRedirect();
    }
    
    if(!$id && $step != 'start') {
      $this->Flash->set(_txt('er.notprov.id', array(_txt('ct.co_petitions.1'))), array('key' => 'error'));
      $this->performRedirect();
    }
    
    // Obtain the configured petition steps
    $steps = $this->CoPetition->CoEnrollmentFlow->configuredSteps($efId);
    
    $this->set('vv_configured_steps', $steps);
    $this->set('vv_current_step', $step);
    
    // Is step configured?
    if(!isset($steps[$step])) {
      $this->Flash->set(_txt('er.unknown', array($step)), array('key' => 'error'));
      $this->performRedirect();
    }
    
    if($steps[$step]['enabled'] != RequiredEnum::NotPermitted) {
      // Set some view vars
      $this->set('title_for_layout',
                 $this->CoPetition->CoEnrollmentFlow->field('name',
                                                            array('CoEnrollmentFlow.id' => $efId)));
      
      if(isset($this->request->params['named']['done'])) {
        // Run the next plugin, if applicable
        
        $plugins = $this->loadAvailablePlugins('enroller', 'simple');
        
        // Make sure $plugins is in alphabetical order so we know which one to
        // try next.
        sort($plugins);
        
        // If there is garbage in $done or if it's "core", array_search will return
        // a zero/false index which will tell us to start from the first entry in
        // $plugins.
        $current = array_search($this->request->params['named']['done'], $plugins);
        
        if(!is_integer($current)) {
          $current = 0;
        } else {
          // Move to the next item
          $current++;
        }
        
        for($i = $current;$i < count($plugins);$i++) {
          // Redirect to the plugin
          
          $redirect = array(
            'plugin'     => Inflector::underscore($plugins[$i]),
            'controller' => Inflector::underscore($plugins[$i]) . '_co_petitions',
            'action'     => $step
          );
          
          // Append petition ID or enrollment flow ID according to what we know
          if($id) {
            $redirect[] = $id;
          } elseif($this->parseCoPetitionId()) {
            $redirect[] = $this->parseCoPetitionId();
          } else {
            $redirect['coef'] = $efId;
          }
          
          // If there is a token attached to the petition, insert it into the URL
          $token = null;
          
          if($steps[$step]['role'] == EnrollmentRole::Petitioner) {
            $token = $this->CoPetition->field('petitioner_token', array('CoPetition.id' => $id));
          } elseif($steps[$step]['role'] == EnrollmentRole::Enrollee) {
            $token = $this->CoPetition->field('enrollee_token', array('CoPetition.id' => $id));
          }
          
          if($token) {
            $redirect['token'] = $token;
          }
          
          // If we're in the start step and a return URL is present, insert that
          if($step == 'start' && !empty($this->request->params['named']['return'])) {
            $redirect['return'] = $this->request->params['named']['return'];
          }
          
          $this->redirect($redirect);
          break;
        }
      } else {
        // Run the step. This will typically happen first, unless we're now in
        // a plugin. (Plugins extend this controller.)
        
        $curPlugin = null;
        
        if(!empty($this->request->params['plugin'])) {
          $curPlugin = Inflector::classify($this->request->params['plugin']);
        }
        
        // Generate hint URL for where to go when the step is completed
        $onFinish = $this->generateDoneRedirect($step, $id, $curPlugin);
        $this->set('vv_on_finish_url', $onFinish);
        
        // Run the step
        $fname = null;
        
        if($curPlugin) {
          // We're executing on behalf of a plugin. (We require the format
          // execute_plugin_STEP so we can distinguish from core workflow steps,
          // since plugins extend CoPetitionsController.)
          $fname = "execute_plugin_" . $step;
          
          if(!is_callable(array($this,$fname))) {
            // This plugin does not implement anything for this step, redirect to the next step
            $this->redirect($onFinish);
          }
          
          try {
            $this->$fname($id, $onFinish);
          }
          catch(Exception $e) {
            $this->Flash->set($e->getMessage(), array('key' => 'error'));
            
            // Log the error into the petition history
            $this->CoPetition
                 ->CoPetitionHistoryRecord
                 ->record($id,
                          $this->Session->read('Auth.User.co_person_id'),
                          PetitionActionEnum::StepFailed,
                          $e->getMessage());
            
            // Don't redirect since it will mask the actual error
            //$this->performRedirect(); 
          }
          
          // Make sure we don't issue a redirect
          return;
        } elseif($steps[$step]['enabled'] == RequiredEnum::Required) {
          // We run the core workflow step, but only if it's Required (vs Optional)
          $fname = "execute_" . $step;
          
          try {
            $this->$fname($id);
          }
          catch(Exception $e) {
            $this->Flash->set($e->getMessage(), array('key' => 'error'));
            
            // Log the error into the petition history
            $this->CoPetition
                 ->CoPetitionHistoryRecord
                 ->record($id,
                          $this->Session->read('Auth.User.co_person_id'),
                          PetitionActionEnum::StepFailed,
                          $e->getMessage());
            
            // Don't redirect since it will mask the actual error
            //$this->performRedirect();
          }
          
          // Make sure we don't issue a redirect
          return;
        } elseif($steps[$step]['enabled'] == RequiredEnum::Optional) {
          // Redirect into the plugins to see if any want to run
          
          $this->redirect($onFinish);
        }
      }
    }
    
    // If we've completed the start step, before redirecting to the next step
    // create a new petition artifact and use that for redirection purposes
    
    $ptid = $id;
    
    if($step == 'start') {
      // $id is null
      
      try {
        // Pull the CO ID from the enrollment flow
        $coId = $this->CoPetition->CoEnrollmentFlow->field('co_id',
                                                           array('CoEnrollmentFlow.id' => $efId));
        
        // We only record the CO Person ID if authorization is required. If not required,
        // we don't record it even if there is a valid login session. This is for
        // consistency, though it is a bit arbitrary. If this decision changes,
        // beforeFilter() will need to be updated to not check a token if there is
        // a co_person_id in the petition record.
        
        $petitionerCoPersonId = null;
        
        $authz = $this->CoPetition->CoEnrollmentFlow->field('authz_level',
                                                            array('CoEnrollmentFlow.id' => $efId));
        
        if($authz != EnrollmentAuthzEnum::None) {
          // We only want the CO Person ID if it's in the current CO, otherwise we
          // want a token to be generated so subsequent authn checks succeed.
          
          $curCoPersonId = $this->Session->read('Auth.User.co_person_id');
          
          if($this->Role->isCoPerson($curCoPersonId, $coId)) {
            $petitionerCoPersonId = $curCoPersonId;
          }
        }
        
        // If a return URL was provided, store it for later use
        $returnUrl = null;
        
        if(!empty($this->request->params['named']['return'])) {
          // Because the URL is in a parameter, we expect it to be encoded.
          // We use base64 to avoid weird parsing errors with partially
          // visible URLs in a URL.
          
          // base64 encoding can generate some HTML special characters.
          // We could urlencode, but that creates various confusion with different
          // parts of the web transaction possibly urldecoding prematurely, so
          // instead we substitute the problematic characters with others. See
          // discussion in CO-1667 and https://stackoverflow.com/questions/1374753/passing-base64-encoded-strings-in-url
          $returnUrl = base64_decode(str_replace(array(".", "_", "-"),
                                                 // This mapping is the same as the one used by the YUI library.
                                                 // RFC 4648 base64url is another option, but strangely doesn't
                                                 // map the padding character (=).
                                                 array("+", "/", "="),
                                                 $this->request->params['named']['return']));
        }
        
        $ptid = $this->CoPetition->initialize($efId,
                                              $coId,
                                              $petitionerCoPersonId,
                                              $returnUrl);
      }
      catch(Exception $e) {
        $this->Flash->set($e->getMessage(), array('key' => 'error'));
        $this->performRedirect();
      }
    }
    
    // If we get here, redirect to the next step
    
    if($step == 'provision'
       && $steps['redirectOnConfirm']['enabled'] == RequiredEnum::NotPermitted) {
      // If we've completed the provision step, we're done, unless redirectOnConfirm
      // is set. It is set when there is no approval step AND email confirmation
      // is required.
      
      $this->Flash->set(_txt('rs.pt.final'), array('key' => 'success'));
      
      $this->execute_redirectOnFinalize($id);
    } else {
      // Firefox has a hardcoded redirect limit (default: 20) that we can actually
      // run into, especially if there are plugins defined and certain steps are
      // skipped (such as approval). To work around it, at the end of each step
      // we'll redirect to the next step using a meta refresh on a page we actually
      // deliver. As long as the number of plugins is less than the redirect limit,
      // this should workaround the problem. (If we need to support > ~20 enroller
      // plugins, we'll need to do this same workaround for all redirects.)
      // http://kb.mozillazine.org/Network.http.redirection-limit
      
      $redirect = array(
        'controller' => 'co_petitions',
        'action'     => $this->nextSteps[$step],
        $ptid
      );
      
      // If there is a token attached to the petition, insert it into the URL
      
      $token = null;
      
      if($steps[$step]['role'] == EnrollmentRole::Petitioner) {
        $token = $this->CoPetition->field('petitioner_token', array('CoPetition.id' => $ptid));
      } elseif($steps[$step]['role'] == EnrollmentRole::Enrollee) {
        $token = $this->CoPetition->field('enrollee_token', array('CoPetition.id' => $ptid));
      }
      
      if($token) {
        $redirect['token'] = $token;
      }
      
      // Set the redirect target in a view var so the view can generate the redirect
      $this->set('vv_meta_redirect_target', $redirect);
      $this->set('vv_next_step', _txt('ef.step.' . $this->nextSteps[$step]));
      
      $this->layout = 'redirect';
      $this->render('nextStep');
    }
  }
  
  /**
   * Dispatch enrollment source plugins.
   *
   * @param Integer $id          CO Petition ID
   * @param String  $action      Enrollment Flow Step name
   * @param Array   $authsources Array of Enrollment Sources
   */
  
  protected function dispatch_enrollment_sources($id, $action, $authsources) {
    if(!empty($authsources)) {
      // Find the next plugin to run
      $current = 0;
      
      if(!empty($this->request->params['named']['piddone'])) {
        // First check $authsources
        
        for($c = 0;$c < count($authsources);$c++) {
          if($authsources[0]['OrgIdentitySource']['id'] == $this->request->params['named']['piddone']) {
            // The specified plugin ID matches, so this is the config we just completed
            $current = $c+1;
            break;
          }
        }
      }
      
      // Before we continue, if we haven't yet linked an OrgIdentity into
      // the petition see if we have one to link. We do this here because
      // we want to deterministically pick the first source to create an
      // Org Identity (so admins can configure appropriately).
      // We need this so that steps like sendConfirmation will work correctly.
      // (This is primarily for selectOrgIdentity in Authenticate mode.)
      
      $pOrgIdentityId = $this->CoPetition->field('enrollee_org_identity_id', array('CoPetition.id' => $id));
      
      if(!$pOrgIdentityId) {
        $args = array();
        $args['conditions']['OrgIdentitySourceRecord.co_petition_id'] = $id;
        $args['conditions'][] = 'OrgIdentitySourceRecord.org_identity_id IS NOT NULL';
        $args['contain'] = false;
        
        $newOrgId = $this->CoPetition->OrgIdentitySourceRecord->find('first', $args);
        
        if(!empty($newOrgId['OrgIdentitySourceRecord']['org_identity_id'])) {
          $this->CoPetition->linkOrgIdentity($this->cachedEnrollmentFlowID,
                                             $id,
                                             $newOrgId['OrgIdentitySourceRecord']['org_identity_id'],
                                             $this->Session->read('Auth.User.co_person_id'));
          $pCoPersonId = $this->CoPetition->field('enrollee_co_person_id', array('CoPetition.id' => $id));

          if($pCoPersonId) {
            // If there is already a CO Person attached to the Petition (from selectEnrollee),
            // create a CoOrgIdentityLink for that CO Person to this Org Identity.
            // (This would typically be for an account linking flow.)
            // Otherwise, we don't create an org identity link since saveAttributes will do that.
            
            $coOrgLink = array();
            $coOrgLink['CoOrgIdentityLink']['org_identity_id'] = $newOrgId['OrgIdentitySourceRecord']['org_identity_id'];
            $coOrgLink['CoOrgIdentityLink']['co_person_id'] = $pCoPersonId;

            // CoOrgIdentityLink is not currently provisioner-enabled, but we'll disable
            // provisioning just in case that changes in the future. We'll also ignore
            // any error in the unlikely event there is already a link in place.
            
            try {
              $this->CoPetition->EnrolleeCoPerson->CoOrgIdentityLink->save($coOrgLink, array("provision" => false));
            }
            catch(Exception $e) {

            }
          } else {
            // If there is no CO Person in the Petition but there *is* a CO Person linked
            // to the Org Identity, link the CO Person into the petition. It was probably
            // created by a Pipeline (probably via an Enrollment Source in authenticate mode).
            
            $linkCoPersonId = $this->CoPetition
                                   ->EnrolleeCoPerson
                                   ->CoOrgIdentityLink->field('co_person_id',
                                                              array('CoOrgIdentityLink.org_identity_id' => $newOrgId['OrgIdentitySourceRecord']['org_identity_id']));
            
            if($linkCoPersonId) {
              $this->CoPetition->linkCoPerson($this->cachedEnrollmentFlowID,
                                              $id,
                                              $linkCoPersonId,
                                              $this->Session->read('Auth.User.co_person_id'));
            }
          }
        }
      }
      
      if($current < count($authsources)) {
        // Redirect into the next plugin
        
        $plugin = $authsources[$current]['OrgIdentitySource']['plugin'];
        
        $redirect = array(
          'plugin'     => Inflector::underscore($plugin),
          'controller' => Inflector::underscore($plugin) . '_co_petitions',
          'action'     => $action,
          $id,
          'oisid'      => $authsources[$current]['OrgIdentitySource']['id'] 
        );
        
        // If we're in an unauthenticated flow, we need to append a token.
        $enrolleeToken = $this->CoPetition->field('enrollee_token', array('CoPetition.id' => $id));
        $petitionerToken = $this->CoPetition->field('petitioner_token', array('CoPetition.id' => $id));

        $steps = $this->CoPetition->CoEnrollmentFlow->configuredSteps($this->enrollmentFlowID());
        
        if(isset($steps[$action]) && $steps[$action]['role'] == EnrollmentRole::Enrollee) {
          $token = empty($enrolleeToken) ? $petitionerToken : $enrolleeToken;
        } else {
          $token = empty($petitionerToken) ? $enrolleeToken : $petitionerToken;
        }

        if($token) {
          $redirect['token'] = $token;
        }

        $this->redirect($redirect);
      }
    }
  }
  
  /**
   * Flag a petition as a duplicate.
   * - precondition: $id must exist and be in 'Pending Approval' or 'Pending Confirmation' state
   * - postcondition: On error, session flash message set
   * - postcondition: Redirect generated
   *
   * @since  COmanage Registry v0.9.1
   * @param  Integer Petition ID
   */
  
  public function dupe($id) {
    try {
      $this->CoPetition->updateStatus($id,
                                      StatusEnum::Duplicate,
                                      $this->Session->read('Auth.User.co_person_id'));
      
      $this->Flash->set(_txt('rs.pt.dupe'), array('key' => 'success'));
    }
    catch (Exception $e) {
      $this->Flash->set($e->getMessage(), array('key' => 'error'));
    }
    
    $this->performRedirect();
  }
  
  /**
   * Determine the requested Enrollment Flow ID.
   * - precondition: An enrollment flow ID should be specified as a named query parameter or in form data.
   *
   * @since  COmanage Registry v0.5
   * @return Integer CO Enrollment Flow ID if found, or -1 otherwise
   */
  
  function enrollmentFlowID() {
    if($this->cachedEnrollmentFlowID > -1) {
      return $this->cachedEnrollmentFlowID;
    }
    
    $coPetitionId = $this->parseCoPetitionId();
    
    if($coPetitionId) {
      // Don't trust the coef parameter, but look up the enrollment flow
      // associated with this ID
      
      $coef = $this->CoPetition->field('co_enrollment_flow_id',
                                       array('CoPetition.id' => $coPetitionId));
      
      if($coef) {
        $this->cachedEnrollmentFlowID = $coef;
      }
    } elseif(isset($this->request->params['named']['coef'])) {
      // calculateImpliedCO should verify this is valid and in the current CO
      $this->cachedEnrollmentFlowID = $this->request->params['named']['coef'];
    } elseif(isset($this->request->data['CoPetition']['co_enrollment_flow_id'])) {
      // We can trust this element since form tampering checks mean it's the
      // same value the view emitted.
      $this->cachedEnrollmentFlowID = $this->request->data['CoPetition']['co_enrollment_flow_id'];
    }
    
    return $this->cachedEnrollmentFlowID;
  }
  
  /**
   * Execute CO Petition 'approve' step
   *
   * @since  COmanage Registry v0.9.4
   * @param  Integer $id CO Petition ID
   * @throws Exception
   */
  
  protected function execute_approve($id) {
    // Let any Exceptions pass through
    
    // As of v3.2.0, execute_approve handles both approve and deny.
    $action = PetitionStatusEnum::Denied;
    $result = _txt('rs.pt.deny');
    $comment = "";
    
    if(!empty($this->request->data['action'])
       && $this->request->data['action'] == _txt('op.approve')) {
      $action = PetitionStatusEnum::Approved;
      $result = _txt('rs.pt.approve');
    }
    
    if(!empty($this->request->data['CoPetition']['approver_comment'])) {
      $comment = $this->request->data['CoPetition']['approver_comment'];
    }
    
    $this->CoPetition->updateStatus($id,
                                    $action,
                                    $this->Session->read('Auth.User.co_person_id'),
                                    $comment);
    
    $this->Flash->set($result, array('key' => 'success'));

    // Send out approval or denial notification, if configured
    $this->CoPetition->sendApprovalNotification($id, $this->Session->read('Auth.User.co_person_id'));
    
    // The step is done
    $this->redirect($this->generateDoneRedirect('approve', $id));    
  }
  
  /**
   * Execute CO Petition 'checkEligibility' step
   *
   * @since  COmanage Registry v2.0.0
   * @param Integer $id CO Petition ID
   * @throws Exception
   */
  
  protected function execute_checkEligibility($id) {
    $this->CoPetition->checkEligibility($id, $this->Session->read('Auth.User.co_person_id'));
    
    // The step is done
    
    $this->redirect($this->generateDoneRedirect('checkEligibility', $id));    
  }
  
  /**
   * Execute CO Petition 'collectIdentifier' step
   *
   * @since  COmanage Registry v0.9.4
   * @param Integer $id CO Petition ID
   * @throws Exception
   */
  
  protected function execute_collectIdentifier($id) {
    // If there are any attached enrollment sources in Identify mode, run those.
    // Otherwise, use the default validateIdentifier() logic.
    
    $authsources = $this->CoPetition
                        ->CoEnrollmentFlow
                        ->CoEnrollmentSource
                        ->activeSources($this->cachedEnrollmentFlowID,
                                        EnrollmentOrgIdentityModeEnum::OISIdentify);
    
    if(!empty($authsources)) {
      // If there are plugins to run, we might redirect here. Once done, we'll fall through.
      $this->dispatch_enrollment_sources($id, 'collectIdentifierIdentify', $authsources);
    } else {
      // If a login identifier was provided, attach it to the org identity if not already present
      
      $loginIdentifier = $this->Session->read('Auth.User.username');
      
      if($loginIdentifier) {
        // Validate the identifier, even if null. (If null but authn was required, we'll
        // get an Exception, which will ultimately pass back up to a redirect.)
        
        // Let most Exceptions pass through
        
        try {
          $coPersonId = $this->CoPetition->field('enrollee_co_person_id', array('CoPetition.id' => $id));
          
          $this->CoPetition->validateIdentifier($id,
                                                $loginIdentifier,
                                                $coPersonId);
        }
        catch(OverflowException $e) {
          // validateIdentifier flagged this as a dupe, so make sure that error message
          // gets presented to the end user. We have to specifically send the user to /
          // to make sure the error doesn't get replaced with "Permission Denied"
          
          $this->Flash->set($e->getMessage(), array('key' => 'error'));
          $this->redirect("/");
        }
      }
    }
    
    // The step is done
    
    $this->redirect($this->generateDoneRedirect('collectIdentifier', $id));    
  }
  
  /**
   * Execute CO Petition 'finalize' step
   *
   * @since  COmanage Registry v0.9.4
   * @param Integer $id CO Petition ID
   * @throws Exception
   */
  
  protected function execute_finalize($id) {
    // If not Denied, set the person status to Active and the petition status to Final
    // Let any Exceptions pass through
    
    $curStatus = $this->CoPetition->field('status', array('CoPetition.id' => $id));
    
    if($curStatus != PetitionStatusEnum::Declined
       && $curStatus != PetitionStatusEnum::Denied) {
      // Possibly assign identifiers. Do this before updating status because we
      // want the identifiers to exist prior to provisioning (and specifically,
      // LDAP DN construction), which happens when the CO Person status goes to Active.
      
      $this->CoPetition->assignIdentifiers($id,
                                           $this->Session->read('Auth.User.co_person_id'),
                                           false);
      
      // This also updates the CO Person/Role to Active
      $this->CoPetition->updateStatus($id,
                                      PetitionStatusEnum::Finalized,
                                      $this->Session->read('Auth.User.co_person_id'));
    }
    
    // The step is done
    
    $this->redirect($this->generateDoneRedirect('finalize', $id));  
  }
  
  /**
   * Execute CO Petition 'petitionerAttributes' step
   *
   * @since  COmanage Registry v0.9.4
   * @param Integer $id CO Petition ID
   * @throws Exception
   */
  
  protected function execute_petitionerAttributes($id) {
    // When this is called, it's just a GET to render the form. POST processing is
    // handled by petitionerAttributes(), which doesn't call dispatch() on POST.
  }
  
  /**
   * Execute CO Petition 'processConfirmation' step
   *
   * @since  COmanage Registry v0.9.4
   * @param Integer $id CO Petition ID
   * @throws Exception
   */
  
  protected function execute_processConfirmation($id) {
    // At this point, the invitation will already have been processed and unlinked.
    // We just need to update petition status.
    
    $newStatus = $this->request->params['named']['confirm'] == 'true'
                 ? PetitionStatusEnum::Confirmed
                 : PetitionStatusEnum::Declined;
    
    if(!empty($this->request->params['named']['confirm'])) {
      $coPersonId = $this->CoPetition->field('enrollee_co_person_id', array('CoPetition.id' => $id));
      
      $this->CoPetition->updateStatus($id, $newStatus, $coPersonId);
    } else {
      // Throw an exception
      throw new InvalidArgumentException(_txt('er.reply.unk'));
    }
    
    // The step is done. However, we only want to proceed if the invitation was
    // confirmed. If it was declined, the flow ends.
    
    if($newStatus == PetitionStatusEnum::Confirmed) {
      $this->redirect($this->generateDoneRedirect('processConfirmation', $id));
    } else {
      // We don't really have a well defined place to redirect to on decline,
      // so just go to root. We don't finalize here because in the future we
      // could allow reactivation of a declined enrollment.
      $this->redirect('/');
    }
  }
  
  /**
   * Execute CO Petition 'provision' step
   *
   * @since  COmanage Registry v1.0.1
   * @param Integer $id CO Petition ID
   * @throws Exception
   */
  
  protected function execute_provision($id) {
    // First pull the current status of the petition
    
    $status = $this->CoPetition->field('status', array('CoPetition.id' => $id));
    
    if($status == PetitionStatusEnum::Finalized) {
      // We also need the enrollee
      $coPersonId = $this->CoPetition->field('enrollee_co_person_id', array('CoPetition.id' => $id));
      
      if($coPersonId) {
        // Get to CoPerson via Co so we don't get confused by 'Enrollee'CoPerson
        $this->CoPetition->Co->CoPerson->Behaviors->load('Provisioner');
        $this->CoPetition->Co->CoPerson->manualProvision(null, $coPersonId, null, ProvisioningActionEnum::CoPersonPetitionProvisioned);
      }
      
      // Send finalization notification, if configured. We do this here rather
      // than in execute_finalize so the provisioners have a chance to run
      // before the notification goes out.
        
      $this->CoPetition->sendApprovalNotification($id, $this->Session->read('Auth.User.co_person_id'), 'finalize');
    }
    // else petition is declined/denied, no need to fire provisioners or send finalization message
    
    // The step is done
    
    $this->redirect($this->generateDoneRedirect('provision', $id));
  }
  
  /**
   * Execute CO Petition 'redirectOnConfirm' step
   *
   * @since  COmanage Registry v0.9.4
   * @param Integer $id CO Petition ID
   * @throws Exception
   */
  
  protected function execute_redirectOnConfirm($id) {
    // Figure out where to redirect the enrollee to
    $targetUrl = $this->CoPetition->CoEnrollmentFlow->field('redirect_on_confirm',
                                                            array('CoEnrollmentFlow.id' => $this->cachedEnrollmentFlowID));
    
    if(!$targetUrl || $targetUrl == "") {
      // Force a logout since we probably just made a change to information relevant
      // for login (such as linking an account).
      
      $this->Flash->set(_txt('rs.pt.relogin'), array('key' => 'success'));
      $targetUrl = "/auth/logout";
    }
    // else we suppress the flash message, since it may not make sense in context
    // or may appear "randomly" (eg: if the targetUrl is outside the Cake framework)
    
    $this->redirect($targetUrl);
  }
  
  /**
   * Execute CO Petition 'redirectOnFinalize' step
   *
   * @since  COmanage Registry v3.1.0
   * @param Integer $id CO Petition ID
   * @throws Exception
   */
  
  protected function execute_redirectOnFinalize($id) {
    // Figure out where to redirect the enrollee to
    
    $targetUrl = $this->CoPetition->field('return_url', array('CoPetition.id' => $id));

    if($targetUrl) {
      // Check that this URL is whitelisted
      
      $whiteList = $this->CoPetition->CoEnrollmentFlow->field('return_url_whitelist',
                                                              array('CoEnrollmentFlow.id' => $this->cachedEnrollmentFlowID));
      
      if(!empty($whiteList)) {
        $found = false;
        
        foreach(preg_split('/\R/', $whiteList) as $u) {
          if(preg_match($u, $targetUrl)) {
            $found = true;
            break;
          }
        }
        
        if(!$found) {
          // No match, so ignore
          
          $targetUrl = null;
        }
      } else {
        // No whitelisted URLs, so ignore return_url
        $targetUrl = null;
      }
    }
    
    if(!$targetUrl || $targetUrl == "") {
      $targetUrl = $this->CoPetition->CoEnrollmentFlow->field('redirect_on_finalize',
                                                              array('CoEnrollmentFlow.id' => $this->cachedEnrollmentFlowID));
    }
    
    if(!$targetUrl || $targetUrl == "") {
      // We're done with the enrollment, use the default redirect behavior
      $this->performRedirect();
    }
    // else we suppress the flash message, since it may not make sense in context
    // or may appear "randomly" (eg: if the targetUrl is outside the Cake framework)
    
    $this->redirect($targetUrl);
  }
  
  /**
   * Execute CO Petition 'redirectOnSubmit' step
   *
   * @since  COmanage Registry v0.9.4
   * @param Integer $id CO Petition ID
   * @throws Exception
   */
  
  protected function execute_redirectOnSubmit($id) {
    $matchPolicy = $this->CoPetition->CoEnrollmentFlow->field('match_policy',
                                                              array('CoEnrollmentFlow.id' => $this->cachedEnrollmentFlowID));
    
    $authzLevel = $this->CoPetition->CoEnrollmentFlow->field('authz_level',
                                                             array('CoEnrollmentFlow.id' => $this->cachedEnrollmentFlowID));
    
    $authnReq = $this->CoPetition->CoEnrollmentFlow->field('require_authn',
                                                           array('CoEnrollmentFlow.id' => $this->cachedEnrollmentFlowID));
    
    if($authnReq && $matchPolicy == EnrollmentMatchPolicyEnum::Self) {
      // Clear any session for account linking
      $this->Flash->set(_txt('rs.pt.login'), array('key' => 'success'));
      $this->redirect("/auth/logout");
    } elseif($authzLevel == EnrollmentAuthzEnum::None
             || $authzLevel == EnrollmentAuthzEnum::AuthUser) {
      // Figure out where to redirect the petitioner to
      $targetUrl = $this->CoPetition->CoEnrollmentFlow->field('redirect_on_submit',
                                                              array('CoEnrollmentFlow.id' => $this->cachedEnrollmentFlowID));
      
      if(!$targetUrl || $targetUrl == "") {
        // Default redirect is to /, which isn't really a great target
        
        $this->Flash->set(_txt('rs.pt.create.self'), array('key' => 'success'));
        $targetUrl = "/";
      }
      // else we suppress the flash message, since it may not make sense in context
      // or may appear "randomly" (eg: if the targetUrl is outside the Cake framework)
      
      $this->redirect($targetUrl);
    } else {
      // Standard redirect
      $this->Flash->set(_txt('rs.pt.create'), array('key' => 'success'));
      $this->performRedirect();
    }
  }
  
  /**
   * Execute CO Petition 'selectEnrollee' step
   *
   * @since  COmanage Registry v0.9.4
   * @param Integer $id CO Petition ID
   * @throws Exception
   */
  
  protected function execute_selectEnrollee($id) {
    $matchPolicy = $this->CoPetition->CoEnrollmentFlow->field('match_policy',
                                                              array('CoEnrollmentFlow.id' => $this->cachedEnrollmentFlowID));
    
    if($matchPolicy == EnrollmentMatchPolicyEnum::Self) {
      // Grab the current CO Person ID and store it in the petition
      
      $this->CoPetition->linkCoPerson($this->cachedEnrollmentFlowID,
                                      $id,
                                      $this->Session->read('Auth.User.co_person_id'),
                                      $this->Session->read('Auth.User.co_person_id'));
    } elseif($matchPolicy == EnrollmentMatchPolicyEnum::Select) {
      if(!empty($this->request->params['named']['copersonid'])) {
        // We're back from the people picker. Grab the requested CO Person ID and store it
        
        $this->CoPetition->linkCoPerson($this->cachedEnrollmentFlowID,
                                        $id,
                                        $this->request->params['named']['copersonid'],
                                        $this->Session->read('Auth.User.co_person_id'));
      } else {
        // Redirect into the CO Person picker
        
        $r = array(
          'plugin'       => null,
          'controller'   => 'co_people',
          'action'       => 'select',
          'copetitionid' => $id
        );
        
        $this->redirect($r);
      }
    }
    
    $this->redirect($this->generateDoneRedirect('selectEnrollee', $id));
  }
  
  /**
   * Execute CO Petition 'selectOrgIdentity' step
   *
   * @since  COmanage Registry v2.0.0
   * @param Integer $id CO Petition ID
   * @throws Exception
   */
  
  protected function execute_selectOrgIdentity($id) {
    // We need the authz level to know how to handle this step
    $authzLevel = $this->CoPetition->CoEnrollmentFlow->field('authz_level',
                                                             array('CoEnrollmentFlow.id' => $this->cachedEnrollmentFlowID));
        
    if($authzLevel == EnrollmentAuthzEnum::CoAdmin
       || $authzLevel == EnrollmentAuthzEnum::CoOrCouAdmin
       || $authzLevel == EnrollmentAuthzEnum::CouAdmin) {
      // We're operating as an admin, so we use the OIS selector
      // to manually pick an org identity to link to the enrollment.
      // Note since any CO/U admin can query any OIS backend, we don't
      // perform any further checking (eg that orgidentityid came from
      // a configured OIS on the return).

      // We don't actually need the list of Enrollment Sources here,
      // we just need to verify that at least one is defined.
      
      $args = array();
      $args['conditions']['CoEnrollmentSource.co_enrollment_flow_id'] = $this->cachedEnrollmentFlowID;
      $args['conditions']['CoEnrollmentSource.org_identity_mode'] = EnrollmentOrgIdentityModeEnum::OISSelect;
      
      $selectcount = $this->CoPetition->CoEnrollmentFlow->CoEnrollmentSource->find('count', $args);

      if($selectcount > 0) {
        if(!empty($this->request->params['named']['orgidentityid'])) {
          // We're back from the org identity (source) selector.
          // Grab the requested Org Identity ID and store it.
          
          $this->CoPetition->linkOrgIdentity($this->cachedEnrollmentFlowID,
                                             $id,
                                             $this->request->params['named']['orgidentityid'],
                                             $this->Session->read('Auth.User.co_person_id'));
          
          // If we don't already have a CO Person, and if the OIS was attached
          // to a pipeline and that pipeline created a CO Person we should attach
          // that CO Person to the petition.
          
          $pCoPersonId = $this->CoPetition->field('enrollee_co_person_id', array('CoPetition.id' => $id));
          
          if(!$pCoPersonId) {
            $pCoPersonId = $this->CoPetition
                                ->EnrolleeOrgIdentity
                                ->CoOrgIdentityLink
                                ->field('co_person_id',
                                        array('CoOrgIdentityLink.org_identity_id'
                                              => $this->request->params['named']['orgidentityid']));
            
            if($pCoPersonId) {
              // Link this CO Person ID to the petition
              
              $this->CoPetition->linkCoPerson($this->cachedEnrollmentFlowID,
                                              $id,
                                              $pCoPersonId,
                                              $this->Session->read('Auth.User.co_person_id'));
            }
          }
        } else {
          // Redirect into the OIS Selector
          
          $r = array(
            'plugin'       => null,
            'controller'   => 'org_identity_sources',
            'action'       => 'select',
            'copetitionid' => $id
          );
          
          $this->redirect($r);
        }
      }
    } else {
      // We're not an admin, so execute the configured Enrollment Sources
      // (OIS plugins). We first look for any in Authenticate mode and redirect
      // into them. This operates as a plugin-within-a-plugin model, sort of
      // similar to what dispatch() does for plugins in general. However, here
      // we use configuration (not raw plugins), and we have a specified order.
    
      // Before we get started, pull the list of Authenticate and Claim sources.
      // While Enrollment Sources cannot be suspended, Org Identity Sources can.
      
      $authsources = $this->CoPetition
                          ->CoEnrollmentFlow
                          ->CoEnrollmentSource
                          ->activeSources($this->cachedEnrollmentFlowID,
                                          EnrollmentOrgIdentityModeEnum::OISAuthenticate);
  
      $claimsources = $this->CoPetition
                           ->CoEnrollmentFlow
                           ->CoEnrollmentSource
                           ->activeSources($this->cachedEnrollmentFlowID,
                                           EnrollmentOrgIdentityModeEnum::OISClaim);
      
      // If there are plugins to run, we might redirect here. Once done, we'll fall through.
      $this->dispatch_enrollment_sources($id, 'selectOrgIdentityAuthenticate', $authsources);
      
      // Now that we're done with Sources in Authenticate mode, move on to
      // those in Claim mode
      
// XXX Claim mode is disabled until this can be updated for Enrollment Sources. (CO-1280)
      if(0
         && !empty($claimsources)) {
        // For self service "claiming" of an org identity, we need to verify control
        // of the identity somehow. There are two plausible ways to do this:
        // (1) Authenticate the user and check the authenticated identifier against
        //     an identifier in the org identity record. This option is not currently
        //     supported
        // (2) Send a confirmation to an email address provided by the enrollee,
        //     then search for Org Identities that match.
        // Either way, we don't want to create the org identity until the appropriate
        // process has been completed.
        
        // Since we only support (2), that's our mode
        $this->set('vv_ois_mode', 'email');
        
        if(!empty($this->request->data['OrgIdentitySource']['mail'])) {
          $this->set('vv_ois_mail', filter_var($this->request->data['OrgIdentitySource']['mail'],FILTER_SANITIZE_SPECIAL_CHARS));
          
          if(!empty($this->request->data['OrgIdentitySource']['token'])) {
            // We're back from the form with a token. Verify it, then search for
            // matching Org Identity Source records. (Note OrgIdentitySource.token
            // is what the user entered, while CoPetition.token is used to link
            // unauthenticated steps across petitions.)
            
// XXX where is token verified?
            $this->set('vv_ois_mode', 'email-select');
            
// XXX note this searches *all* Active backends, not just those in a query mode or whatever
            $sourceRecords = $this->OrgIdentitySource->searchAllByEmail($this->request->data['OrgIdentitySource']['mail'],
                                                                        (!empty($this->cur_co['Co']['id'])
                                                                         ? $this->cur_co['Co']['id']
                                                                         : null));
            
            $this->set('vv_ois_candidates', $sourceRecords);
          } elseif(!empty($this->request->data['OrgIdentitySource']['selection'])) {
            // We're back from the selector with a record to use. selection is of the
            // form #/key, where # is the relevant OrgIdentitySource:id.
            
            $s = explode('/', $this->request->data['OrgIdentitySource']['selection'], 2);
            
            try {
// XXX update this to query all EnrollmentSources in Claim mode
// XXX also probably want to set provision=false
              $orgId = $this->OrgIdentitySource->createOrgIdentity($s[0], $s[1], null, (!empty($this->cur_co['Co']['id'])
                                                                                        ? $this->cur_co['Co']['id']
                                                                                        : null));
              
// XXX don't want to do this where more than 1 org identity can be linked
              $this->CoPetition->linkOrgIdentity($this->cachedEnrollmentFlowID,
                                                 $id,
                                                 $orgId,
                                                 // XXX this probably isn't set yet
                                                 $this->Session->read('Auth.User.co_person_id'));
              
              $this->redirect($this->generateDoneRedirect('selectEnrollee', $id));
            }
            catch(OverflowException $e) {
              $this->Flash->set($e->getMessage(), array('key' => 'error'));
            }
          } else {
            // We're back from the form with an email address. Generate a token and
            // send a confirmation email to that address.
            
            // XXX not yet implemented, as we want to base this on a refactored
            // replacement for CoInvites. (CO-753)
            
            $this->set('vv_ois_mode', 'email-token');
          }
        }
        
        // Don't generate redirect, we want the view to render
        return;
      }
    }
    
    // We're done, complete the step    
    $this->redirect($this->generateDoneRedirect('selectOrgIdentity', $id));
  }
  
  /**
   * Execute CO Petition 'sendApproverNotification' step
   *
   * @since  COmanage Registry v0.9.4
   * @param Integer $id CO Petition ID
   * @throws Exception
   */
  
  protected function execute_sendApproverNotification($id) {
    $this->CoPetition->sendApproverNotification($id, $this->Session->read('Auth.User.co_person_id'));
    
    $this->CoPetition->updateStatus($id,
                                    PetitionStatusEnum::PendingApproval, 
                                    $this->Session->read('Auth.User.co_person_id'));
    
    // The step is done
    
    $this->redirect($this->generateDoneRedirect('sendApproverNotification', $id));
  }
  
  /**
   * Execute CO Petition 'sendConfirmation' step
   *
   * @since  COmanage Registry v0.9.4
   * @param Integer $id CO Petition ID
   * @throws Exception
   */
  
  protected function execute_sendConfirmation($id) {
    $this->CoPetition->sendConfirmation($id, $this->Session->read('Auth.User.co_person_id'));
    
    $this->CoPetition->updateStatus($id,
                                    PetitionStatusEnum::PendingConfirmation, 
                                    $this->Session->read('Auth.User.co_person_id'));
    
    // The step is done
    
    $debug = Configure::read('debug');
    
    if(!$debug) {
      $this->redirect($this->generateDoneRedirect('sendConfirmation', $id));
    } else {
      // We need to populate the view var to render the debug link
      $coInviteId = $this->CoPetition->field('co_invite_id',
                                             array('CoPetition.id' => $id));
      
      if($coInviteId) {
        $args = array();
        $args['conditions']['CoInvite.id'] = $coInviteId;
        $args['contain'] = false;
        
        $this->set('vv_co_invite', $this->CoPetition->CoInvite->find('first', $args));
      }
    }
  }
  
  /**
   * Execute CO Petition 'start' step
   *
   * @since  COmanage Registry v0.9.4
   * @param Integer $id CO Petition ID, should be null
   * @throws Exception
   */
  
  protected function execute_start($id) {
    $introText = $this->CoPetition->CoEnrollmentFlow->field('introduction_text',
                                                            array('CoEnrollmentFlow.id' => $this->cachedEnrollmentFlowID));
    
    if(!empty($introText)) {
      // Render the start view
      
      $this->set('vv_intro_text', $introText);
    } else {
      // The step is done
      
      $this->redirect($this->generateDoneRedirect('start', $id));
    }
  }
  
  /**
   * Execute CO Petition 'waitForConfirmation' step
   *
   * @since  COmanage Registry v0.9.4
   * @param Integer $id CO Petition ID
   * @throws Exception
   */
  
  protected function execute_waitForApproval($id) {
    // If approval_required is false, this step is skipped.
    // If true, we've sent the notification already, so we just need to issue a suitable redirect.
    
    // Figure out where to redirect the petitioner to
    $targetUrl = $this->CoPetition->CoEnrollmentFlow->field('redirect_on_confirm',
                                                            array('CoEnrollmentFlow.id' => $this->cachedEnrollmentFlowID));
    
    if(!$targetUrl || $targetUrl == "") {
      // Default redirect is to /, which isn't really a great target. We could
      // redirect to the dashboard for the CO, but we may yet require approval.
      // At least / will generate an informational message for the user.
      
      $this->Flash->set(_txt('rs.pt.confirm'), array('key' => 'success'));
      $targetUrl = "/";
    }
    // else we suppress the flash message, since it may not make sense in context
    // or may appear "randomly" (eg: if the targetUrl is outside the Cake framework)
    
    $this->redirect($targetUrl);
  }
  
  /**
   * Execute CO Petition 'waitForConfirmation' step
   *
   * @since  COmanage Registry v0.9.4
   * @param Integer $id CO Petition ID
   * @throws Exception
   */
  
  protected function execute_waitForConfirmation($id) {
    // If email_confirmation_mode is None, this step is skipped.
    // If true, we've sent the confirmation already, so we just need to issue a suitable redirect.
    
    $this->execute_redirectOnSubmit($id);
  }
  
  /**
   * Finalize the CO Petition
   *
   * @since  COmanage Registry v0.9.4
   * @param  Integer $id CO Petition ID
   */
  
  public function finalize($id) {
    $this->dispatch('finalize', $id);
  }
  
  /**
   * Generate a redirect for use after completion of a step.
   *
   * @since  COmanage Registry v0.9.4
   * @param  String  $step        Current step
   * @param  Integer $id          CO Petition ID, if known
   * @param  String  $curPlugin   Current plugin name, or null
   * @param  String  $curPluginId Current plugin ID (for OIS plugins), or null
   * @return Array URL in Cake array format
   */
  
  protected function generateDoneRedirect($step, $id=null, $curPlugin=null, $curPluginId=null) {
    $ret = array(
      'plugin'     => null,
      'controller' => 'co_petitions',
      'action'     => $step
    );
    
    if($id) {
      $ret[] = $id;
    } else {
      $ret['coef'] = $this->cachedEnrollmentFlowID;
    }
    
    if($step == 'start' && !empty($this->request->params['named']['return'])) {
      // Propagate the return URL since we don't store it until the step is done
      $ret['return'] = $this->request->params['named']['return'];
    }
    
    $token = $this->parseToken();
    
    if($token) {
      $ret['token'] = $token;
    }
    
    if($curPluginId) {
      $ret['piddone'] = $curPluginId;
    } else {
      $ret['done'] = ($curPlugin ? $curPlugin : 'core');
    }
    
    return $ret;
  }
  
  /**
   * Authorization for this Controller, called by Auth component
   * - precondition: Session.Auth holds data used for authz decisions
   * - postcondition: $permissions set with calculated permissions
   *
   * @since  COmanage Registry v0.5
   * @return Array Permissions
   */
  
  function isAuthorized() {
    $roles = $this->Role->calculateCMRoles();
    
    // Construct the permission set for this user, which will also be passed to the view.
    $p = array();
    
    // We determine certain permissions based on the user's role to the specified
    // petition or flow
    
    $canInitiate = false;
    $isPetitioner = false;
    $isEnrollee = false;
    $isApprover = false;
    
    // If an enrollment flow was specified, check the authorization for that flow
    
    if($this->enrollmentFlowID() != -1) {
      $canInitiate = $roles['cmadmin']
                     || $this->CoPetition->CoEnrollmentFlow->authorizeById($this->enrollmentFlowID(),
                                                                           $roles['copersonid'],
                                                                           $this->Session->read('Auth.User.username'),
                                                                           $this->Role);
    }
    
    // If a petition was specified, check the authorizations for that petition
    
    $petitionId = $this->parseCoPetitionId();
    
    if($petitionId) {
      // Current values from petition
      $args = array();
      $args['conditions']['CoPetition.id'] = $petitionId;
      $args['contain'] = false;
      
      $pt = $this->CoPetition->find('first', $args);
      
      if(!$pt) {
        $this->Flash->set(_txt('er.notfound', array(_txt('ct.co_petitions.1', $petitionId))), array('key' => 'error'));
        $this->redirect('/');
      }
      
      $curPetitioner = $pt['CoPetition']['petitioner_co_person_id'];
      $curEnrollee = $pt['CoPetition']['enrollee_co_person_id'];
      $petitionerToken = $pt['CoPetition']['petitioner_token'];
      $enrolleeToken = $pt['CoPetition']['enrollee_token'];
      
      // Select admins can also act as the petitioner
      $isPetitioner = $roles['cmadmin']
                      || $roles['coadmin']
                      || ($roles['couadmin'] && $this->Role->isCouAdminForCoPerson($roles['copersonid'], $curPetitioner))
                      || ($curPetitioner && ($curPetitioner == $roles['copersonid']))
                      || ($petitionerToken != '' && $petitionerToken == $this->parseToken());
      
      // Select admins can also act as the enrollee
      $isEnrollee = $roles['cmadmin']
                    || $roles['coadmin']
                    || ($roles['couadmin'] && $this->Role->isCouAdminForCoPerson($roles['copersonid'], $curEnrollee))
                    || ($curEnrollee && ($curEnrollee == $roles['copersonid']))
                    || ($enrolleeToken != '' && $enrolleeToken == $this->parseToken());
      
      $isApprover = $roles['cmadmin'] || $this->Role->isApproverForFlow($roles['copersonid'],
                                                                        $this->enrollmentFlowID(),
                                                                        $petitionId);
    }
    
    // Add a new CO Petition? When not restful, this is just a redirect to start
    $p['add'] = (!$this->request->is('restful') || $roles['cmadmin']);
    
    // Delete an existing CO Petition?
    // For now, this is restricted to CMP and CO Admins, until we have a better policy
    $p['delete'] = $roles['cmadmin'] || $roles['coadmin'];
    
    // Flag an existing CO Petition as a duplicate?
    $p['dupe'] = $isApprover;
    
    // Edit an existing CO Petition?
    $p['edit'] = false;
    
    // We don't allow editing at the moment, but we do allow adding comments.
    // This permission correlates to CoPetitionHistoryRecords::add
    $p['addhistory'] = ($roles['cmadmin'] || $roles['coadmin']
                        || ($canInitiate && $roles['couadmin']));
    
    // Match against existing CO People? If the match policy is Advisory, we
    // allow matching to take place as long as $canInitiate is also true. (Note we don't
    // necessarily have a petition ID.)
    // Note this same permission exists in CO People
    
    $p['match_policy'] = $this->CoPetition->CoEnrollmentFlow->field('match_policy',
                                                                    array('CoEnrollmentFlow.id' => $this->enrollmentFlowID()));
    $p['match'] = (($roles['cmadmin'] || $canInitiate)
                   &&
                   ($p['match_policy'] == EnrollmentMatchPolicyEnum::Advisory));
    
    $pool = isset($this->viewVars['pool_org_identities']) && $this->viewVars['pool_org_identities'];
    
    // View all existing CO Petitions?
    // Before adjusting this, see paginationConditions(), below
    $p['index'] = ($roles['cmadmin']
                   || $roles['coadmin'] || $roles['couadmin']
                   // Only allow "any admin" if org identities are pooled and
                   // we don't have a CO Person ID for the user (so we're in an
                   // Org Identity context, such as search by Org Identity ID)
                   || ($pool
                       && !$roles['copersonid'] 
                       && ($roles['admin'] || $roles['subadmin']))
                   || $this->Role->isApprover($roles['copersonid']));
    
    // Search all existing CO Petitions?
    $p['search'] = $p['index'];
    
    // Resend invitations?
    $p['resend'] = ($roles['cmadmin']
                    || $roles['coadmin']
                    || ($canInitiate && $roles['couadmin'])
                    || $isPetitioner);
    
    // View an existing CO Petition? We allow the usual suspects to view a Petition, even
    // if they don't have permission to edit it. Also approvers need to be able to see the Petition.
    $p['view'] = ($roles['cmadmin'] || $roles['coadmin'] || $roles['couadmin'] || $isApprover);
    
    if($this->action == 'index' && $p['index']) {
      // These permissions may not be exactly right, but they only apply when rendering
      // the index view
      
      $p['add'] = true;  // This is really permission to run co_enrollment_flows/select
      $p['delete'] = ($roles['cmadmin'] || $roles['coadmin']);
      $p['edit'] = $p['delete'];  // For now, delete and edit are restricted
      $p['resend'] = ($roles['cmadmin'] || $roles['coadmin'] || $roles['couadmin']);
      $p['view'] = true;  // Approvers will have the petitions they can see filtered by the controller
    }
    
    // View Enrollment Attribute definitions? This is for link generation only, the actual
    // authz is in CoEnrollmentAttributesController.
    
    $p['viewEA'] = $roles['cmadmin'] || $roles['coadmin'];
    
    // Execute the various phases involved in a CO Petition?
    // We need to know which phases are configured for certain permissions.
    $steps = null;
    
    if($this->enrollmentFlowID() > -1) {
      $steps = $this->CoPetition->CoEnrollmentFlow->configuredSteps($this->enrollmentFlowID());
      
      // Initiating a Petition gets us to the point of collecting petitioner attributes
      $p['start'] = $canInitiate;
      // Once there is a petitioner attached, we restrict who can run the associated steps
      $p['selectOrgIdentity'] = $isPetitioner;
      // OIS Plugin steps for selectOrgIdentity get the same permissions
      $p['selectOrgIdentityAuthenticate'] = $p['selectOrgIdentity'];
      $p['selectOrgIdentityClaim'] = $p['selectOrgIdentity'];
      $p['selectEnrollee'] = $isPetitioner;
      $p['petitionerAttributes'] = $isPetitioner;
      $p['sendConfirmation'] = $isPetitioner;
      $p['waitForConfirmation'] = $isPetitioner;
      // The petition then gets handed off to the enrollee
      $p['processConfirmation'] = $isEnrollee;
      $p['collectIdentifier'] = $isEnrollee;
      // OIS Plugin steps for collectIdentifier get the same permissions
      $p['collectIdentifierIdentify'] = $p['collectIdentifier'];
      // Eligibility steps could be triggered by petitioner or enrollee, according to configuration
      if($steps['checkEligibility']['role'] == EnrollmentRole::Enrollee) {
        // Confirmation required, so eligibility steps get triggered by enrollee
        $p['checkEligibility'] = $isEnrollee;
      } else {
        // Eligibility triggered by petitioner
        $p['checkEligibility'] = $isPetitioner;
      }
      // Approval steps could be triggered by petitioner or enrollee, according to configuration
      if($steps['sendApproverNotification']['role'] == EnrollmentRole::Enrollee) {
        // Confirmation required, so approval steps get triggered by enrollee
        $p['sendApproverNotification'] = $isEnrollee;
        $p['waitForApproval'] = $isEnrollee;
      } else {
        // Approval triggered by petitioner
        $p['sendApproverNotification'] = $isPetitioner;
        $p['waitForApproval'] = $isPetitioner;
      }
      // Actual approval is handled by the approver
      $p['approve'] = $isApprover;
      // Finalize and finalize steps could be reached by anyone, in theory
      foreach(array('finalize', 'provision') as $xstep) {
        switch($steps[$xstep]['role']) {
          case EnrollmentRole::Approver:
            $p[$xstep] = $isApprover;
            break;
          case EnrollmentRole::Enrollee:
            $p[$xstep] = $isEnrollee;
            break;
          case EnrollmentRole::Petitioner:
            $p[$xstep] = $isPetitioner;
            break;
          default:
            // Shouldn't get here...
            $p[$xstep] = false;
            break;
        }
      }
      if($steps['redirectOnConfirm']['role'] == EnrollmentRole::Enrollee) {
        $p['redirectOnConfirm'] = $isEnrollee;
      } else {
        $p['redirectOnConfirm'] = false;
      }
    }
    
    $this->set('permissions', $p);
    return $p[$this->action];
  }
  
  /**
   * Continue on to the next step of a petition.
   *
   * @since  COmanage Registry v1.0.3
   */

  protected function nextStep() {
    // This is not actually called. dispatch() will render the next_step view
    // when starting a new step... no need to explicitly route via this action.
  }
  
  /**
   * Determine the conditions for pagination of the index view, when rendered via the UI.
   *
   * @since  COmanage Registry v0.8.3
   * @return Array An array suitable for use in $this->paginate
   */
  
  function paginationConditions() {
    $pagcond = array();
    
    // Use server side pagination
    
    if($this->requires_co) {
      $pagcond['conditions']['CoPetition.co_id'] = $this->cur_co['Co']['id'];
    }
    
    // Filter by status
    if(!empty($this->params['named']['search.status'])) {
      $searchterm = $this->params['named']['search.status'];
      $pagcond['conditions']['CoPetition.status'] = $searchterm;
    }
    
    // Filter by CO Person ID
    if(!empty($this->params['named']['search.copersonid'])) {
      $pagcond['conditions']['CoPetition.enrollee_co_person_id'] = $this->params['named']['search.copersonid'];
    }
    
    // Filter by Org Identity ID
    if(!empty($this->params['named']['search.orgidentityid'])) {
      $pagcond['conditions']['CoPetition.enrollee_org_identity_id'] = $this->params['named']['search.orgidentityid'];
      
      if(!$this->requires_co) {
        // This is a bit complicated... we need to filter records based on the COs for which
        // the current user is an admin of some sort.
        
        // Pull org_identity_id from session -- in theory there can be more than one, though... sigh
        $efs = $this->Role->approverForByOrgIdentities(Hash::extract($this->Session->read('Auth.User.org_identities'), "{n}.org_id"));
        
        $pagcond['conditions']['CoPetition.co_enrollment_flow_id'] = $efs;
      }
    }
    
    // Potentially filter by enrollment flow ID. Our assumption is that if we make it
    // here the person has authorization to see at least some Petitions. Either they
    // are a CO or COU admin (in which case the following list will be empty) or they
    // are an approver by group (in which case the following list will not be empty).
    // We explicitly consider CMP admins to have the same permissions even if they
    // are not in the CO.
    
    // This isn't exactly right, though... what we really want for COU admins is
    // to know which petitions the admin can approve. However, a COU admin may have
    // approval privileges based on the COU a petition is attached to (rather than
    // the enrollment flow), which requires examining all Petitions. XXX Perhaps a
    // future enhancement.
    
    $coPersonId = $this->Session->read('Auth.User.co_person_id');
    $username = $this->Session->read('Auth.User.username');
    
    if(!$this->Role->isCoOrCouAdmin($coPersonId, $this->cur_co['Co']['id'])
       // We need an explicit check for CMP admin, who should have superuser privs
       && !$this->Role->identifierIsCmpAdmin($username)) {
      // approverFor will return groups even for a CO/COU admin, so don't check it for admins
      $efs = $this->Role->approverFor($coPersonId);
      
      if(!empty($efs)) {
        $pagcond['conditions']['CoPetition.co_enrollment_flow_id'] = $efs;
      } else {
        // We shouldn't normally get here, as isAuthorized should filter anyone without
        // an approval role, but just in case we'll insert an invalid ID that won't ever match
        $pagcond['conditions']['CoPetition.co_enrollment_flow_id'] = -1;
      }
    }
    
    // Because we're using Linkable behavior to join deeply nested models, we need to
    // explicitly state which fields can be used for sorting.
    
    $pagcond['sortlist'] = array(
      'ApproverPrimaryName.family',
      'CoPetition.created',
      'CoPetition.modified',
      'CoPetition.status',
      'Cou.name',
      'EnrolleePrimaryName.family',
      'PetitionerPrimaryName.family',
      'SponsorPrimaryName.family'
    );
    
    // Don't use contain
    $pagcond['contain'] = false;
    
    return $pagcond;
  }
  
  /**
   * Parse a CO Petition ID from the request.
   *
   * @since  COmanage Registry v0.9.4
   * @return Integer CO Petition ID, or null
   */

  protected function parseCoPetitionId() {
    if(!empty($this->request->params['pass'][0])) {
      return $this->request->params['pass'][0];
    } elseif(!empty($this->request->data['CoPetition']['id'])) {
      return $this->request->data['CoPetition']['id'];
    }
    
    return null;
  }
  
  /**
   * Parse a petitioner or enrollee token from the request.
   *
   * @since  COmanage Registry v0.9.4
   * @return String Token, or null
   */

  protected function parseToken() {
    $token = null;
    
    if(!empty($this->request->params['named']['token'])) {
      $token = $this->request->params['named']['token'];
    } elseif(!empty($this->request->data['CoPetition']['token'])) {
      $token = $this->request->data['CoPetition']['token'];
    }
    
    // Dump the token into a viewvar in case needed
    $this->set('vv_petition_token', $token);
    
    return $token;
  }
  
  /**
   * Perform a redirect back to the controller's default view.
   * - postcondition: Redirect generated
   *
   * @since  COmanage Registry v0.5
   */
  
  function performRedirect() {
    // If we're not an admin, we should redirect to a page that the petitioner
    // is likely to be able to see. Otherwise try to determine where the admin is
    // likely to want to go.
    
    if($this->action == 'add'
       && $this->viewVars['permissions']['index']) {
      // After submission on add, we go back to CO People
      
      $this->redirect(array(
        'controller' => 'co_people',
        'action' => 'index',
        'co' => $this->cur_co['Co']['id']
      ));
    } elseif(!empty($this->request->params['pass'][0])
             && $this->viewVars['permissions']['view']
             // For delete we want to go back to the petition index
             && $this->action != 'delete') {
      // A petition ID is set, redirect back to the same petition (since it has
      // probably just been updated and this way we can provide the latest version)
      
      $this->redirect(array(
        'plugin'      => null,
        'controller'  => 'co_petitions',
        'action'      => 'view',
        $this->request->params['pass'][0]
      ));
    } elseif($this->viewVars['permissions']['index']) {
      // For admins, return to the list of petitions pending approval. For admins,
      // this is probably where they'll want to go. For others, they probably won't
      // have permission and will end up at /... we might want to fix that at
      // some point.
      
      $this->redirect(array(
        'controller'    => 'co_petitions',
        'action'        => 'index',
        'co'            => $this->cur_co['Co']['id'],
        'sort'          => 'created',
        'direction'     => 'desc',
        'search.status' => array(
          StatusEnum::PendingApproval
        )
      ));
    } else {
      $coPersonId = $this->Session->read('Auth.User.co_person_id');
      
      if($coPersonId) {
        // Redirect to the person's identity page
        $this->redirect(array(
          'plugin'     => null,
          'controller' => 'co_people',
          'action'     => 'canvas',
          $coPersonId
        ));
      } else {
        // Don't know where to go...
        $this->redirect('/');
      }
    }
  }
  
  /**
   * Collect CO Petition attributes from the petitioner
   *
   * @since  COmanage Registry v0.9.4
   */
  
  public function petitionerAttributes() {
    if($this->request->is('get')) {
      $this->dispatch('petitionerAttributes', $this->parseCoPetitionId());
    } else {
      // We've already been dispatched (rendered the form) and now we're back
      // for form submission/processing
      
      try {
        $this->CoPetition->saveAttributes($this->parseCoPetitionId(),
                                          $this->enrollmentFlowID(),
                                          $this->request->data,
                                          $this->Session->read('Auth.User.co_person_id'));
        
        // We could calculate and execute the next plugin or step directly,
        // but that would require some refactoring.
        $this->redirect($this->generateDoneRedirect('petitionerAttributes',
                                                    $this->parseCoPetitionId()));
      }
      catch(InvalidArgumentException $e) {
        // Validation failed
        $this->Flash->set($e->getMessage(), array('key' => 'error'));
        $this->dispatch('petitionerAttributes', $this->parseCoPetitionId());
      }
      catch(Exception $e) {
        $this->Flash->set($e->getMessage(), array('key' => 'error'));
        $this->log($e->getMessage());
        $this->performRedirect();
      }
    }
  }
  
  /**
   * Re-entry point following petition confirmation
   *
   * @since  COmanage Registry v0.9.4
   * @param  Integer $id CO Petition ID
   */
  
  public function processConfirmation($id) {
    $this->dispatch('processConfirmation', $id);
  }
  
  /**
   * Provision following approval of a petition.
   *
   * @since  COmanage Registry v1.0.1
   * @param  Integer Petition ID
   */
  
  public function provision($id) {
    $this->dispatch('provision', $id);
  }
  
  /**
   * Redirect on confirmation of a CO Petition
   *
   * @since  COmanage Registry v0.9.4
   * @param  Integer $id CO Petition ID
   */
  
  public function redirectOnConfirm($id) {
    $this->dispatch('redirectOnConfirm', $id);
  }
  
  /**
   * Resend an invitation associated with a Petition.
   * - precondition: Petition exists in a Pending Confirmation state
   * - postcondition: Invitation sent
   *
   * @since  COmanage Registry v0.7
   * @param  Integer CO Petition ID
   */
  
  public function resend($id) {
    $recipient = null;
    
    try {
      $recipient = $this->CoPetition->resend($id, $this->Session->read('Auth.User.co_person_id'));
    }
    catch(Exception $e) {
      $this->Flash->set($e->getMessage(), array('key' => 'error'));
    }
    
    if($recipient) {
      $this->Flash->set(_txt('rs.inv.sent', array($recipient)), array('key' => 'success'));
    }
    
    // Always redirect to the petition, regardless of how we got here.
    
    $this->redirect(array(
      'controller' => 'co_petitions',
      'action' => 'view',
      $id
    ));
  }
  
  /**
   * Select the enrollee for a new CO Petition
   *
   * @since  COmanage Registry v0.9.4
   * @param  Integer $id CO Petition ID
   */
  
  public function selectEnrollee($id) {
    $this->dispatch('selectEnrollee', $id);
  }
  
  /**
   * Select the org identity for a new CO Petition
   *
   * @since  COmanage Registry v2.0.0
   * @param  Integer $id CO Petition ID
   */
  
  public function selectOrgIdentity($id) {
    $this->dispatch('selectOrgIdentity', $id);
  }
  
  /**
   * Execute an OIS plugin in Authenticate mode
   *
   * @since  COmanage Registry v2.0.0
   * @param  Integer $id CO Petition ID
   */
  
  public function selectOrgIdentityAuthenticate($id) {
    // At this point we're running within a plugin (which extends CoPetitionsController).
    // Pull some configuration data and pass off to the plugin's entry point.
    
    $args = array();
    $args['conditions']['OrgIdentitySource.id'] = $this->request->params['named']['oisid'];
    $args['contain'] = false;
    
    $oiscfg = $this->OrgIdentitySource->find('first', $args);
    
    if(empty($oiscfg)) {
      $this->Flash->set(_txt('er.notfound',
                             array('ct.org_identity_sources.1', filter_var($this->request->params['named']['oisid'],FILTER_SANITIZE_SPECIAL_CHARS))),
                        array('key' => 'error'));
      $this->redirect("/");
    }
    
    // Construct a redirect URL
    $onFinish = $this->generateDoneRedirect('selectOrgIdentity', $id, null, $oiscfg['OrgIdentitySource']['id']);
    $this->set('vv_on_finish_url', $onFinish);
    
    $fname = "execute_plugin_selectOrgIdentityAuthenticate";
    
    try {
      $this->$fname($id, $oiscfg, $onFinish, $this->Session->read('Auth.User.co_person_id'));
    }
    catch(Exception $e) {
      $this->Flash->set($e->getMessage(), array('key' => 'error'));
      
      // Log the error into the petition history
      $this->CoPetition
           ->CoPetitionHistoryRecord
           ->record($id,
                    $this->Session->read('Auth.User.co_person_id'),
                    PetitionActionEnum::StepFailed,
                    $e->getMessage());
           
      $this->performRedirect(); 
    }
    
    // Make sure we don't issue a redirect
    return;
  }
  
  /**
   * Send approver notification for a new CO Petition
   *
   * @since  COmanage Registry v0.9.4
   * @param  Integer $id CO Petition ID
   */
  
  public function sendApproverNotification($id) {
    $this->dispatch('sendApproverNotification', $id);
  }
  
  /**
   * Send enrollee email address verification confirmation email for a new CO Petition
   *
   * @since  COmanage Registry v0.9.4
   * @param  Integer $id CO Petition ID
   */
  
  public function sendConfirmation($id) {
    $this->dispatch('sendConfirmation', $id);
  }
  
  /**
   * Start a new CO Petition
   *
   * @since  COmanage Registry v0.9.4
   */
  
  public function start() {
    $this->dispatch('start');
  }
  
  /**
   * View a CO Petition.
   * 
   * @since  COmanage Registry v0.9.4
   * @param  Integer $id CO Petition ID
   */
  
  function view($id) {
    // The current step is determined by the status of the petition
    $this->set('vv_current_step', $this->CoPetition->currentStep($id));
    
    parent::view($id);
    
    // Set the title
    
    if(!$this->request->is('restful')) {
      $this->set('title_for_layout',
                 _txt('op.' . $this->action . '-f',
                      array(_txt('ct.co_petitions.1'),
                            (!empty($this->viewVars['co_petitions'][0]['EnrolleeCoPerson']['PrimaryName'])
                             ? generateCn($this->viewVars['co_petitions'][0]['EnrolleeCoPerson']['PrimaryName'])
                             : _txt('fd.enrollee.new'))
                            )));
    }
  }
  
  /**
   * "Placeholder" step to allow for plugins to run after approval is sent
   *
   * @since  COmanage Registry v0.9.4
   * @param  Integer $id CO Petition ID
   */
  
  public function waitForApproval($id) {
    $this->dispatch('waitForApproval', $id);
  }
  
  /**
   * "Placeholder" step to allow for plugins to run after confirmation is sent
   *
   * @since  COmanage Registry v0.9.4
   * @param  Integer $id CO Petition ID
   */
  
  public function waitForConfirmation($id) {
    $this->dispatch('waitForConfirmation', $id);
  }
}
