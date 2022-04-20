<?php
/**
 * COmanage Registry Sponsors Controller
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

App::uses("StandardController", "Controller");

class SponsorsController extends StandardController {
  // Class name, used by Cake
  public $name = "Sponsors";
  
  public $uses = array(
    // We use a placeholder here because otherwise cake thinks the first model
    // is the controller's actual model
    'SponsorManager.SponsorManager',
    'SponsorManager.SponsorManagerSetting',
    'CoGroupMember',
    'CoPerson',
    'CoPersonRole',
    'CoSetting',
    'HistoryRecord'
  );
  
  // Establish pagination parameters for HTML views
/*  public $paginate = array(
    'limit' => 25,
    'order' => array(
      'co_id' => 'asc'
    )
  );*/

  // This controller needs a CO to be set
  public $requires_co = true;
  
  /**
   * Cancel a pending enrollment.
   *
   * @since  COmanage Registry v4.1.0
   */
  
  public function cancel() {
    try {
      $this->SponsorManager->cancel($this->request->params['named']['copersonroleid'],
                                    $this->Session->read('Auth.User.co_person_id'));
      
      $this->Flash->set(filter_var(_txt('pl.sponsormanager.canceled'),FILTER_SANITIZE_SPECIAL_CHARS), array('key' => 'success'));
    }
    catch(Exception $e) {
      $this->Flash->set($e->getMessage(), array('key' => 'error'));
    }

    // Redirect to review
    $this->redirect(array(
      'plugin'      => 'sponsor_manager',
      'controller'  => 'sponsors',
      'action'      => 'review',
      'copersonid'  => $this->request->params['named']['copersonid'],
      '?'           => array('filter' => $this->request->params['named']['filter'])
    ));
  }
  
  /**
   * Expire a CO Person Role.
   *
   * @since  COmanage Registry v4.1.0
   */
  
  public function expire() {
    try {
      $this->SponsorManager->expire($this->request->params['named']['copersonroleid'],
                                    $this->Session->read('Auth.User.co_person_id'));
      
      $this->Flash->set(filter_var(_txt('pl.sponsormanager.expired'),FILTER_SANITIZE_SPECIAL_CHARS), array('key' => 'success'));
    }
    catch(Exception $e) {
      $this->Flash->set($e->getMessage(), array('key' => 'error'));
    }
    
    // Redirect to review
    $this->redirect(array(
      'plugin'      => 'sponsor_manager',
      'controller'  => 'sponsors',
      'action'      => 'review',
      'copersonid'  => $this->request->params['named']['copersonid'],
      '?'           => array('filter' => $this->request->params['named']['filter'])
    ));  
  }
  
  /**
   * Renew a CO Person Role.
   *
   * @since  COmanage Registry v4.1.0
   */
  
  public function renew() {
    try {
      $args = array();
      $args['conditions']['SponsorManagerSetting.co_id'] = $this->cur_co['Co']['id'];
      $args['contain'] = false;

      $settings = $this->SponsorManagerSetting->find('first', $args);
      
      $result = $this->SponsorManager->renew($settings, 
                                             $this->request->params['named']['copersonroleid'],
                                             $this->Session->read('Auth.User.co_person_id'));
      
      $this->Flash->set(filter_var($result,FILTER_SANITIZE_SPECIAL_CHARS), array('key' => 'success'));
    }
    catch(Exception $e) {
      $this->Flash->set($e->getMessage(), array('key' => 'error'));
    }

    // Redirect to review
    $this->redirect(array(
      'plugin'      => 'sponsor_manager',
      'controller'  => 'sponsors',
      'action'      => 'review',
      'copersonid'  => $this->request->params['named']['copersonid'],
      '?'           => array('filter' => $this->request->params['named']['filter'])
    ));
  }
  
  /**
   * Present a list of sponsored roles for review.
   *
   * @since  COmanage Registry v4.1.0
   */
  
  public function review() {
    if(empty($this->request->params['named']['copersonid'])) {
      $this->Flash->set(_txt('er.notprov.id', array(_txt('ct.co_people.1'))), array('key' => 'error'));
      
      return;
    }

    $sponsorCoPersonId = $this->request->params['named']['copersonid'];
    
    // Pull our configuration
    $args = array();
    $args['conditions']['SponsorManagerSetting.co_id'] = $this->cur_co['Co']['id'];
    $args['contain'] = false;
    
    $settings = $this->SponsorManagerSetting->find('first', $args);
    
    $this->set('vv_settings', $settings);
    
    // Pull the Sponsor's information
    $args = array();
    $args['conditions']['CoPerson.id'] = $sponsorCoPersonId;
    $args['contain'] = array('PrimaryName');
    
    $this->set('vv_sponsor', $this->CoPerson->find('first', $args));
    
    $filterMode = ReviewFilterEnum::Default;
    
    if(!empty($this->request->query['filter'])) {
      $filterMode = $this->request->query['filter'];
    }
    
    $conditions = array(
      'CoPersonRole.sponsor_co_person_id' => $sponsorCoPersonId
    );
    
    switch($filterMode) {
      case ReviewFilterEnum::All:
        // The default $conditions covers this
        break;
      case ReviewFilterEnum::Expired:
        $conditions['CoPersonRole.status'] = StatusEnum::Expired;
        break;
      case ReviewFilterEnum::Upcoming:
        // "Upcoming" expirations are those where the valid_through date is
        // less than (today + look ahead window) and greater than today.
        $conditions['CoPersonRole.valid_through <'] =
          date('Y-m-d 23:59:59', strtotime("+" . $settings['SponsorManagerSetting']['lookahead_window'] . " days"));
        $conditions['CoPersonRole.valid_through >'] =
          date('Y-m-d H:i:s', strtotime("now"));
        break;
      case ReviewFilterEnum::Default:
        $conditions['CoPersonRole.status'] = array(
          StatusEnum::Active,
          StatusEnum::Expired,
          StatusEnum::GracePeriod
        );
        $conditions['CoPersonRole.valid_through <'] =
          date('Y-m-d 23:59:59', strtotime("+" . $settings['SponsorManagerSetting']['lookahead_window'] . " days"));
        break;
    }
    
    $this->Paginator->settings = array(
      'CoPersonRole' => array(
        'conditions' => $conditions,
        'limit' => 25,
        'order' => array('CoPersonRole.valid_through' => 'asc'),
        'contain' => array(
          'CoPerson' => array(
            'PrimaryName',
            'Identifier',
            'EmailAddress'),
          'Cou'
        )
      )
    );
    
    $results = $this->Paginator->paginate('CoPersonRole');
    
    // We should be able to specify the conditions to filter on in contain(),
    // but that doesn't work probably due to ChangelogBehavior.
    if(!empty($settings['SponsorManagerSetting']['identifier_type'])) {
      $t = $settings['SponsorManagerSetting']['identifier_type'];
      foreach(array_keys($results) as $key) {
        $matches = Hash::extract($results[$key]['CoPerson']['Identifier'], '{n}[type='.$t.']');
        $results[$key]['CoPerson']['Identifier'] = $matches;
      }
    }
    
    if(!empty($settings['SponsorManagerSetting']['email_type'])) {
      $t = $settings['SponsorManagerSetting']['email_type'];
      
      foreach(array_keys($results) as $key) {
        $matches = Hash::extract($results[$key]['CoPerson']['EmailAddress'], '{n}[type='.$t.']');
        $results[$key]['CoPerson']['EmailAddress'] = $matches;
      }
    }
    
    $this->set('vv_sponsored_roles', $results);

    // Pull the set of available affiliations
    $this->set('vv_copr_affiliation_types', $this->CoPersonRole->types($this->cur_co['Co']['id'], 'affiliation'));
    
    // Pass on the set of filter modes
    global $cm_lang, $cm_texts;
    
    $this->set('vv_filter_modes', $cm_texts[$cm_lang]['en.sponsormanager.review_filter']);
  }
  
  /**
   * Authorization for this Controller, called by Auth component
   * - precondition: Session.Auth holds data used for authz decisions
   * - postcondition: $permissions set with calculated permissions
   *
   * @since  COmanage Registry v4.1.0
   * @return Array Permissions
   */
  
  function isAuthorized() {
    $roles = $this->Role->calculateCMRoles();
    
    // Construct the permission set for this user, which will also be passed to the view.
    $p = array();
    
    // Is the current user the user for whom sponsored records were requested?
    // Note for CO/COU Admins this will likely be false, but we'll grant them
    // permission below later.
    $isRequestedSponsor = false;
    
    if(!empty($this->request->params['named']['copersonid'])
       && $this->request->params['named']['copersonid'] == $this->Session->read('Auth.User.co_person_id')) {
      $isRequestedSponsor = true;
    }
    
    // Is the current user eligible to be a sponsor? Note this doesn't actually
    // check if the user is a sponsor for the *current role*, just that they are
    // eligible to be a sponsor at all.
    $canSponsor = false;
    
    switch($this->CoSetting->getSponsorEligibility($this->cur_co['Co']['id'])) {
      case SponsorEligibilityEnum::CoOrCouAdmin:
        if($roles['couadmin']) {
          $canSponsor = true;
          break;
        }
        // else fall through
      case SponsorEligibilityEnum::CoAdmin:
        $canSponsor = ($roles['cmadmin'] || $roles['coadmin']);
        break;
      case SponsorEligibilityEnum::CoGroupMember:
        $sponsorGroupId = $this->CoSetting->getSponsorEligibilityCoGroup($this->cur_co['Co']['id']);
        
        if($sponsorGroupId) {
          $canSponsor = $this->CoGroupMember->isMember($sponsorGroupId, $this->Session->read('Auth.User.co_person_id'));
        }
        break;
      case SponsorEligibilityEnum::CoPerson:
        $canSponsor = $roles['comember'];
        break;
      case SponsorEligibilityEnum::None:
      default:
        break;
    }

    // If we have a CO Person Role ID, determine if the current user is the sponsor
    $isSponsor = false;
    
    if($canSponsor && !empty($this->request->params['named']['copersonroleid'])) {
      $roleSponsor = $this->CoPersonRole->field('sponsor_co_person_id', array('CoPersonRole.id' => $this->request->params['named']['copersonroleid']));
      
      if(!empty($roleSponsor)) {
        $isSponsor = ($roleSponsor === $this->Session->read('Auth.User.co_person_id'));
      }
    }
    
    // Determine what operations this user can perform
    
    // Cancel an enrollment of the specified role?
    $p['cancel'] = ($roles['cmadmin'] || $roles['coadmin'] || $isSponsor);
    
    // Expire the specified role?
    $p['expire'] = ($roles['cmadmin'] || $roles['coadmin'] || $isSponsor);
    
    // Link to the CO Person Canvas for a role?
    $p['canvas'] = $roles['cmadmin'] || $roles['coadmin'];
    
    // Renew the specified role?
    $p['renew'] = ($roles['cmadmin'] || $roles['coadmin'] || $isSponsor);
    
    // Review Sponsored Roles?
    $p['review'] = ($roles['cmadmin'] || $roles['coadmin'] || $isRequestedSponsor);
    
    $this->set('permissions', $p);
    return($p[$this->action]);
  }
  
  /**
   * For Models that accept a CO ID, find the provided CO ID.
   * - precondition: A coid must be provided in $this->request (params or data)
   *
   * @since  COmanage Registry v4.1.0
   * @param  Array $data Array of data for calculating implied CO ID
   * @return Integer The CO ID if found, or -1 if not
   */

  function parseCOID($data = null) {
    if($this->action == 'review') {
      if(empty($this->request->params['named']['copersonid'])) {
        throw new InvalidArgumentException(_txt('er.notprov.id', array(_txt('ct.co_people.1'))));
      }
      
      return $this->CoPerson->findCoForRecord($this->request->params['named']['copersonid']);
    } elseif(in_array($this->action, array('cancel', 'expire', 'renew'))) {
      if(empty($this->request->params['named']['copersonroleid'])) {
        throw new InvalidArgumentException(_txt('er.notprov.id', array(_txt('ct.co_person_roles.1'))));
      }
      
      return $this->CoPersonRole->findCoForRecord($this->request->params['named']['copersonroleid']);
    }

    return parent::parseCOID();
  }
}
