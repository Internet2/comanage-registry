<?php
/**
 * COmanage Registry Meem Enroller CO Petitions Controller
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
 * @since         COmanage Registry v4.0.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::uses('CoPetitionsController', 'Controller');

/**
 * This plugin is intended to be attached to two different types of enrollment flow,
 * a Self Signup flow and an Authenticator Setup flow. For now, at least, we can
 * determine which type of flow we are in based on the configuration, so we don't
 * need an explicit configuration setting.
 */

class MeemEnrollerCoPetitionsController extends CoPetitionsController {
  // Class name, used by Cake
  public $name = "MeemEnrollerCoPetitions";

  // Note CoPetition should come first for inheritance reasons
  public $uses = array(
    "CoPetition",
    "MeemEnroller.MeemEnroller",
    "MeemEnroller.MeemMfaStatus",
    "Authenticator",
    "CoEnrollmentAuthenticator",
    "CoGroupMember",
    "HistoryRecord"
  );
  
  /**
   * Process Collect Identifier.
   *
   * This step is only executed during Self Signup. If the current flow ID is
   * mfa_co_enrollment_flow_id, we immediately return.
   *
   * @since  COmanage Registry v4.0.0
   * @param  int    $id       CO Petition ID
   * @param  string $onFinish URL to redirect to when the step is completed
   * @throws InvalidArgumentException
   * @throws RuntimeException
   */
  
  protected function execute_plugin_collectIdentifier($id, $onFinish) {
    // First pull our configuration
    
    $efwid = $this->viewVars['vv_efwid'];
    
    $args = array();
    $args['conditions']['MeemEnroller.co_enrollment_flow_wedge_id'] = $efwid;
    $args['contain'] = false;
    
    $cfg = $this->MeemEnroller->find('first', $args);
    
    if(!empty($cfg['MeemEnroller']['mfa_co_enrollment_flow_id'])) {
      // See if the petition is associated with the MFA enrollment flow. If so,
      // there is nothing for us to do and we return immediately.
      
      $efId = $this->CoPetition->field('co_enrollment_flow_id', array('CoPetition.id' => $id));
      
      if($efId == $cfg['MeemEnroller']['mfa_co_enrollment_flow_id']) {
        $this->redirect($onFinish);
      }
    }
    
    // Next determine the enrollee's CO Person ID
    $coPersonID = $this->CoPetition->field('enrollee_co_person_id', array('CoPetition.id' => $id));
    
    if(!$coPersonID) {
      throw new InvalidArgumentException(_txt('er.notprov.id', array(_txt('ct.co_people.1'))));
    }
    
    $idpId = (!empty($cfg['MeemEnroller']['env_idp']) ? getenv($cfg['MeemEnroller']['env_idp']) : null);
    $didMfa = (!empty($cfg['MeemEnroller']['env_mfa']) ? (getenv($cfg['MeemEnroller']['env_mfa']) === 'yes') : false);
    
    if(!empty($cfg['MeemEnroller']['env_idp']) && !$idpId) {
      throw new RuntimeException(_txt('er.meemenroller.env_idp', array($cfg['MeemEnroller']['env_idp'])));
    }
    
    if($idpId) {
      // Record the IdP identifier and whether the IdP signaled MFA
      
      $status = array(
        'MeemMfaStatus' => array(
          'meem_enroller_id' => $cfg['MeemEnroller']['id'],
          'co_person_id'     => $coPersonID,
          'idp_identifier'   => $idpId,
          'mfa_asserted'     => $didMfa
        )
      );
      
      $this->MeemMfaStatus->clear();
      $this->MeemMfaStatus->save($status);
    }
    
    // If the IdP did not signal MFA, add the Enrollee to the MFA Exempt Group
    // (if configured)
    
    if(!$didMfa && !empty($cfg['MeemEnroller']['mfa_exempt_co_group_id'])) {
      $grmem = array(
        'CoGroupMember' => array(
          'co_group_id'   => $cfg['MeemEnroller']['mfa_exempt_co_group_id'],
          'co_person_id'  => $coPersonID,
          'member'        => true,
          'owner'         => false
        )
      );
      
      if(!empty($cfg['MeemEnroller']['mfa_initial_exemption'])) {
        $grmem['CoGroupMember']['valid_from'] = strftime("%F %T", time());
        $grmem['CoGroupMember']['valid_through'] = strftime("%F %T", (time() + ($cfg['MeemEnroller']['mfa_initial_exemption'] * 3600)));
      }
      
      $this->CoGroupMember->clear();
      $this->CoGroupMember->save($grmem);
      
      // Record History
      
      $this->HistoryRecord->record($coPersonID,
                                   null,
                                   null,
                                   $coPersonID,
                                   ActionEnum::CoGroupMemberAdded,
                                   _txt('pl.meemenroller.mfa_exempt.added', array($cfg['MeemEnroller']['mfa_initial_exemption'])),
                                   $cfg['MeemEnroller']['mfa_exempt_co_group_id']);
    }
    
    $this->redirect($onFinish);
  }
  
  /**
   * Process Establish Authenticators.
   *
   * This steps is only executed during Authenticator Setup. In the Self Signup
   * flow there are no authenticators and so the plugin step will not execute.
   *
   * @since  COmanage Registry v4.0.0
   * @param  int    $id       CO Petition ID
   * @param  string $onFinish URL to redirect to when the step is completed
   */
  
  protected function execute_plugin_establishAuthenticators($id, $onFinish) {
    // We don't need to check the configuration since only the Authenticator
    // Enrollment Flow should have Establish Authenticators enabled (and
    // therefore this plugin would fire)
    
    // First pull our configuration
    
    $efwid = $this->viewVars['vv_efwid'];
    
    $args = array();
    $args['conditions']['MeemEnroller.co_enrollment_flow_wedge_id'] = $efwid;
    $args['contain'] = false;
    
    $cfg = $this->MeemEnroller->find('first', $args);
    
    // Next determine the enrollee's CO Person ID
    $coPersonID = $this->CoPetition->field('enrollee_co_person_id', array('CoPetition.id' => $id));
    
    if($coPersonID
       && !empty($cfg['MeemEnroller']['mfa_exempt_co_group_id'])) {
      // Figure out if the Enrollee has actually set up an Authenticator. To do this,
      // we pull the Enrollment Authenticator configuration for this Enrollment
      // Flow and see if the Enrollee has an Authenticator of that type. We only
      // require one Authenticator to be establised if more than one is configured.
      
      $args = array();
      $args['conditions']['CoEnrollmentAuthenticator.co_enrollment_flow_id'] = $this->CoPetition->field('co_enrollment_flow_id', array('CoPetition.id' => $id));
      $args['conditions']['CoEnrollmentAuthenticator.required'] = array(RequiredEnum::Required, RequiredEnum::Optional);
      $args['contain'] = false;
      
      $authenticators = $this->CoEnrollmentAuthenticator->find('all', $args);

      $active = false;
      
      // We need to bind thi plugins for status()
      $plugins = $this->loadAvailablePlugins('authenticator');
      
      // Bind the models so Cake can magically pull associated data. Note this will
      // create associations with *all* authenticator plugins, not just the one that
      // is actually associated with this Authenticator. Given that most installations
      // will only have a handful of authenticators, that seems OK (vs parsing the request
      // data to figure out which type of Plugin we should bind).
      
      foreach(array_values($plugins) as $plugin) {
        $this->Authenticator->bindModel(array('hasOne' => array($plugin => array('dependent' => true))), false);
      }
      
      foreach($authenticators as $a) {
        $s = $this->Authenticator->status($a['CoEnrollmentAuthenticator']['authenticator_id'], $coPersonID);
        
        if($s['status'] == AuthenticatorStatusEnum::Active) {
          $active = true;
          break;
        }
      }
      
      if($active) {
        // Remove the group membership, if present
        
        $this->CoGroupMember->deleteAll(
          array('CoGroupMember.co_group_id'  => $cfg['MeemEnroller']['mfa_exempt_co_group_id'],
                'CoGroupMember.co_person_id' => $coPersonID),
          false,
          true
        );
        
        
        // Record History
        
        $this->HistoryRecord->record($coPersonID,
                                     null,
                                     null,
                                     $coPersonID,
                                     ActionEnum::CoGroupMemberDeleted,
                                     _txt('pl.meemenroller.mfa_exempt.deleted'),
                                     $cfg['MeemEnroller']['mfa_exempt_co_group_id']);
      }
    }
    
    $this->redirect($onFinish);
  }

  /**
   * Process Provision.
   *
   * This step only runs during Self Signup. In the Authenticator Setup flow,
   * the execute_plugin_establishAuthenticators step will have removed the
   * membership in mfa_exempt_co_group_id, and so there will be nothing to do here.
   *
   * @since  COmanage Registry v4.0.0
   * @param  int    $id       CO Petition ID
   * @param  string $onFinish URL to redirect to when the step is completed
   */
  
  protected function execute_plugin_provision($id, $onFinish) {
    // First pull our configuration
    
    $efwid = $this->viewVars['vv_efwid'];
    
    $args = array();
    $args['conditions']['MeemEnroller.co_enrollment_flow_wedge_id'] = $efwid;
    $args['contain'] = false;
    
    $cfg = $this->MeemEnroller->find('first', $args);
    
    // We also need to see if Approval is required (if so, don't redirect since
    // that would confuse the approver).
    $coEnrollmentFlowID = $this->CoPetition->field('co_enrollment_flow_id', array('CoPetition.id' => $id));
    
    if($coEnrollmentFlowID) {
      $requiresApproval = $this->CoPetition->CoEnrollmentFlow->field('approval_required', array('CoEnrollmentFlow.id' => $coEnrollmentFlowID));
      
      if(!$requiresApproval
         && !empty($cfg['MeemEnroller']['mfa_exempt_co_group_id'])
         && !empty($cfg['MeemEnroller']['mfa_co_enrollment_flow_id'])
         && $cfg['MeemEnroller']['enable_reminder_page']) {
        // Determine the enrollee's CO Person ID
        $coPersonID = $this->CoPetition->field('enrollee_co_person_id', array('CoPetition.id' => $id));
        
        if($coPersonID) {
          // If the CO Person is a member of the exemption group, redirect them to
          // a splash page to start the MFA enrollment
          
          if($this->CoGroupMember->isMember($cfg['MeemEnroller']['mfa_exempt_co_group_id'], $coPersonID)) {
            $args = array(
              'CoGroupMember.co_group_id' => $cfg['MeemEnroller']['mfa_exempt_co_group_id'],
              'CoGroupMember.co_person_id' => $coPersonID,
              'CoGroupMember.member' => true
            );
            
            $validThrough = $this->CoGroupMember->field('valid_through', $args);
            $countdown = -1;
            
            if(!empty($validThrough)) {
              $countdown = strtotime($validThrough) - time();
            }
            
            $redirect = array(
              'plugin'     => 'meem_enroller',
              'controller' => 'meem_reminders',
              'action'     => 'remind',
              $cfg['MeemEnroller']['id'],
              '?'          => array(
                'efid'      => $cfg['MeemEnroller']['mfa_co_enrollment_flow_id'],
                'countdown' => $countdown,
                'return'    => htmlspecialchars(Router::url($onFinish, true))
              )
            );
            
            $this->redirect($redirect);
          }
        }
      }
    }
    
    $this->redirect($onFinish);
  }
}
