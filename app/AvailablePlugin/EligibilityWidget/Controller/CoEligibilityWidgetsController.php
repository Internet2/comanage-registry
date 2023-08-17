<?php
/**
 * COmanage Registry CO Eligibility Widgets Controller
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
 * @since         COmanage Registry v4.3.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::uses("SDWController", "Controller");

class CoEligibilityWidgetsController extends SDWController {
  // Class name, used by Cake
  public $name = "CoEligibilityWidgets";
  
  public $uses = array(
    'EligibilityWidget.CoEligibilityWidget',
    'EligibilityWidget.OisRegistration',
    'CoPersonRole',
    'OrgIdentitySource'
  );

  public $view_contains = array(
    'CoDashboardWidget' => array('CoDashboard'),
    'OisRegistration' => array(
      'OrgIdentitySource' => array(
        'conditions' => array(
          'OrgIdentitySource.deleted != true',
          'OrgIdentitySource.org_identity_source_id IS NULL',
          'OrgIdentitySource.org_identity_source_id IS NULL',
          'OrgIdentitySource.co_pipeline_id IS NOT NULL',
          "OrgIdentitySource.sync_mode" => SyncModeEnum::Query,
          "OrgIdentitySource.status" => SuspendableStatusEnum::Active,
        ),
        'CoPipeline' => array('SyncCou')
      )
    )
  );

  public $edit_contains = array(
    'CoDashboardWidget' => array('CoDashboard'),
    'OisRegistration' => array(
      'OrgIdentitySource' => array(
        'conditions' => array(
          'OrgIdentitySource.deleted != true',
          'OrgIdentitySource.org_identity_source_id IS NULL',
          'OrgIdentitySource.org_identity_source_id IS NULL',
          'OrgIdentitySource.co_pipeline_id IS NOT NULL',
          "OrgIdentitySource.sync_mode" => SyncModeEnum::Query,
          "OrgIdentitySource.status" => SuspendableStatusEnum::Active,
        ),
        'CoPipeline' => array('SyncCou')
      )
    )
  );

  /**
   * Callback before other controller methods are invoked or views are rendered.
   * - postcondition: If invalid enrollment flow provided, session flash message set
   *
   * @since  COmanage Registry v4.3.0
   */

  public function beforeFilter() {
    parent::beforeFilter();

    // Pass the config
    $cfg = $this->CoEligibilityWidget->getConfig();
    $this->set('vv_config', $cfg);

    // For ajax i accept only json format
    if( $this->request->is('ajax') ) {
      $this->RequestHandler->addInputType('json', array('json_decode', true));
    }
  }

  /**
   * Active CoPerson Roles
   *
   * @since  COmanage Registry v4.3.0
   */

  public function active($id) {
    $this->request->allowMethod('ajax');
    $this->layout = 'ajax';

    if (empty($this->request->params['pass'][0])) {
      throw new BadRequestException(_txt('pl.er.eligibilitywidget.param.notfound', array(_txt('ct.eligibility_widget.1') . ' Id')));
    }
    if (empty($this->reqCoPersonId)) {
      throw new BadRequestException(_txt('pl.er.eligibilitywidget.req.copersonid'));
    }

    if($this->viewVars["vv_config"]["CoEligibilityWidget"]["mode"] == RegistrationModeEnum::COU) {
      $active_memberships = $this->CoEligibilityWidget->personCouMembership($this->reqCoPersonId);
      $active_cou_ids     = Hash::extract($active_memberships, '{n}.id');
    }

    if($this->viewVars["vv_config"]["CoEligibilityWidget"]["mode"] == RegistrationModeEnum::OIS) {
      $active_memberships = $this->CoEligibilityWidget->personCouMembership($this->reqCoPersonId,
                                                                            [StatusEnum::Active, StatusEnum::GracePeriod],
                                                                            true);
      $active_memberships = Hash::expand($active_memberships);
      $active_cou_ids = Hash::extract($active_memberships, '{n}.Cou.id');

      $this->set('vv_active_cou_ids', $active_cou_ids);
      $active_ois_ids = Hash::extract($active_memberships, '{n}.OisRegistration.id');
      $this->set('vv_active_ois_ids', $active_ois_ids);
    }


    $this->set('vv_active_cou_ids', $active_cou_ids);
    $data = array(
      'cous' => $active_cou_ids ?? [],
      'oises' => $active_ois_ids ?? []
    );
    $this->set(compact('data')); // Pass $data to the view
    $this->set('_serialize', 'data');
  }

  /**
   * All CoPerson Roles
   *
   * @since  COmanage Registry v4.3.0
   */

  public function all($id) {
    $this->request->allowMethod('ajax');
    $this->layout = 'ajax';

    if (empty($this->request->params['pass'][0])) {
      throw new BadRequestException(_txt('pl.er.eligibilitywidget.param.notfound', array(_txt('ct.eligibility_widget.1') . ' Id')));
    }

    if (empty($this->reqCoPersonId)) {
      // stick to exception based error handling.
      throw new BadRequestException(_txt('pl.er.eligibilitywidget.req.copersonid'));
    }

    // We will always have all cous list. In mode 1 it will be the entire list or the configured
    // white list. For the case of OIS mode it will be the list of COUs connected through a pipeline
    if($this->viewVars["vv_config"]["CoEligibilityWidget"]["mode"] == RegistrationModeEnum::COU) {
      // Calculate the COU dropdown
      $all_cous = $this->CoEligibilityWidget->allCous($this->cur_co['Co']['id']);
    }

    if($this->viewVars["vv_config"]["CoEligibilityWidget"]["mode"] == RegistrationModeEnum::OIS) {
      // Calculate the OIS list dropdown
      [$all_cous, $all_oises_list] = $this->CoEligibilityWidget->allOisRegistration($this->viewVars["vv_config"]["CoEligibilityWidget"]["id"]);
      $this->set('vv_all_oises', $all_oises_list);
      $this->set('vv_all_cous', $all_cous);
    }

    $data = array(
      'cous' => $all_cous ?? [],
      'oises' => $all_oises_list ?? []
    );
    $this->set(compact('data')); // Pass $data to the view
    $this->set('_serialize', 'data');
  }

  /**
   * Assign CoPerson Roles
   *
   * @since  COmanage Registry v4.3.0
   */

  public function assign($id) {
    $this->request->allowMethod('ajax');
    $this->layout = 'ajax';

    if (empty($this->request->params['pass'][0])) {
      $this->log(__METHOD__ . "::message " . _txt('pl.er.eligibilitywidget.param.notfound', array(_txt('ct.eligibility_widget.1') . ' Id')), LOG_ERROR);
      throw new BadRequestException(_txt('pl.er.eligibilitywidget.param.notfound', array(_txt('ct.eligibility_widget.1') . ' Id')));
    }
    if (empty($this->request->data['cou_id'])) {
      $this->log(__METHOD__ . "::message " . _txt('pl.er.eligibilitywidget.param.notfound', array('cou_id')), LOG_ERROR);
      throw new BadRequestException(_txt('pl.er.eligibilitywidget.param.notfound', array('cou_id')));
    }
    if(empty($this->request->data['co_person_id'])) {
      $this->log(__METHOD__ . "::message " . _txt('pl.er.eligibilitywidget.param.notfound', array('co_person_id')), LOG_ERROR);
      throw new BadRequestException(_txt('pl.er.eligibilitywidget.param.notfound', array('co_person_id')));
    }

    // I need to verify that the CO Person is part of the CO
    $copersonid = $this->request->data['co_person_id'];
    if(!$this->Role->isCoPerson($copersonid, $this->cur_co["Co"]["id"])) {
      $this->log(__METHOD__ . "::message " . _txt('er.cop.nf', array($copersonid)), LOG_ERROR);
      throw new NotFoundException(_txt('er.cop.nf', array($copersonid)));
    }

    // Assign the CO Person Role

    $copr = array(
      'CoPersonRole' => array(
        'co_person_id'   => $this->request->data['co_person_id'],
        'cou_id'   => $this->request->data['cou_id'],
        'affiliation'    => AffiliationEnum::Member,
        'status'         => StatusEnum::Active
      )
    );

    if(!$this->CoPersonRole->save($copr)) {
      $this->log(__METHOD__ . "::message " . _txt('er.db.save'), LOG_ERROR);
      throw new InternalErrorException(_txt('er.db.save'));
    }

    $role = $this->CoPersonRole->findById($this->CoPersonRole->id);
    $this->Api->restResultHeader(HttpStatusCodesEnum::HTTP_CREATED,  _txt('fd.created'));
    $this->set(array(
                 'CoPersonRole' => $role,
                 '_serialize' => array('role')
               ));

//    $this->set(compact('role')); // Pass $data to the view
//    $this->set('_serialize', 'role');
  }

  /**
   * Render the widget according to the requested user and current configuration.
   *
   * @since  COmanage Registry v4.3.0
   * @param  Integer $id CO Services Widget ID
   */
  
  public function display($id) {
    // Return the CoPerson ID and CO ID
    $this->set('vv_co_person_id', $this->reqCoPersonId);
    $this->set('vv_co_id', $this->cur_co['Co']['id']);

    if($this->viewVars["vv_config"]["CoEligibilityWidget"]["mode"] == RegistrationModeEnum::COU) {
      // Calculate the COU dropdown
      // XXX Keep the following code since it will become part of the mode 1 functionality
      $all_cous = $this->CoEligibilityWidget->allCous($this->cur_co['Co']['id']);
      $this->set('vv_all_cous', $all_cous);
      $this->set('vv_all_cous_list', Hash::combine($all_cous, '{n}.id', '{n}.name'));

      // I can not filter the Person Roles by status via the REST API. As a result i need to fetch the active ones
      // dataset.
      // XXX Probably move this to its own endpoint
      $active_memberships = $this->CoEligibilityWidget->personCouMembership($this->reqCoPersonId);
      $active_cou_ids = Hash::extract($active_memberships, '{n}.id');
      $this->set('vv_active_cou_ids', $active_cou_ids);
    }

    if($this->viewVars["vv_config"]["CoEligibilityWidget"]["mode"] == RegistrationModeEnum::OIS) {
      // XXX Mode 2
      // Dropdown
      // We need to fetch all the configured Entries from the OisRegistration and render the description
      [$all_cous, $all_oises_list] = $this->CoEligibilityWidget->allOisRegistration($this->viewVars["vv_config"]["CoEligibilityWidget"]["id"]);
      $this->set('vv_all_oises', $all_oises_list);
      // We will always have all cous list. In mode 1 it will be the entire list or the configured
      // white list. For the case of OIS mode it will be the list of COUs connected through a pipeline
      $this->set('vv_all_cous', $all_cous);
      $this->set('vv_all_cous_list', Hash::combine($all_cous, '{n}.id', '{n}.name'));

      $active_memberships = $this->CoEligibilityWidget->personCouMembership($this->reqCoPersonId,
                                                                            [StatusEnum::Active, StatusEnum::GracePeriod],
                                                                            true);
      $active_memberships = Hash::expand($active_memberships);
      $active_cou_ids = Hash::extract($active_memberships, '{n}.Cou.id');
      $this->set('vv_active_cou_ids', $active_cou_ids);
      $active_ois_ids = Hash::extract($active_memberships, '{n}.OisRegistration.id');
      $this->set('vv_active_ois_ids', $active_ois_ids);
    }
  }

  /**
   * Call OrgIdentitySource::search($id, array('mail' => $mail) for each verified Email Address.
   * and grand the configured roles to the user
   *
   * @since  COmanage Registry v4.3.0
   */

  public function eligibility($id) {
    $this->request->allowMethod('ajax');
    $this->layout = 'ajax';

    if (empty($this->request->params['pass'][0])) {
      $this->log(__METHOD__ . "::message " . _txt('pl.er.eligibilitywidget.param.notfound', array(_txt('ct.eligibility_widget.1') . ' Id')), LOG_ERROR);
      throw new BadRequestException(_txt('pl.er.eligibilitywidget.param.notfound', array(_txt('ct.eligibility_widget.1') . ' Id')));
    }

    if (empty($this->request->data['ois_id'])) {
      $this->log(__METHOD__ . "::message " . _txt('pl.er.eligibilitywidget.param.notfound', array('ois_id')), LOG_ERROR);
      throw new BadRequestException(_txt('pl.er.eligibilitywidget.param.notfound', array('ois_id')));
    }
    if(empty($this->request->data['co_person_id'])) {
      $this->log(__METHOD__ . "::message " . _txt('pl.er.eligibilitywidget.param.notfound', array('co_person_id')), LOG_ERROR);
      throw new BadRequestException(_txt('pl.er.eligibilitywidget.param.notfound', array('co_person_id')));
    }

    // I need to verify that the CO Person is part of the CO
    $copersonid = $this->request->data['co_person_id'];
    if(!$this->Role->isCoPerson($copersonid, $this->cur_co["Co"]["id"])) {
      $this->log(__METHOD__ . "::message " . _txt('er.cop.nf', array($copersonid)), LOG_ERROR);
      throw new NotFoundException(_txt('er.cop.nf', array($copersonid)));
    }

    try{
      $oisList = $this->CoEligibilityWidget->checkEligibility($this->request->data['ois_id'],
                                                              $this->cur_co["Co"]["id"],
                                                              $this->request->data['co_person_id']);
    } catch(Exception $e) {
      $this->log(__METHOD__ . "::message " .$e->getMessage(), LOG_ERROR);
      // Double quotes are not JSON accepted
      throw new BadRequestException(str_replace('"', "", $e->getMessage()));
    }

    $unmatched_emails = array_filter($oisList,
      static function ($item) {
      return empty($item);
    });

    $data = array(
      'oisList' => $oisList,
      'unmatched' => array_keys($unmatched_emails),
      'message' => !empty($unmatched_emails) ? _txt('pl.er.eligibilitywidget.no.match') : _txt('op.ok')
    );
    $this->Api->restResultHeader(!empty($unmatched_emails) ? HttpStatusCodesEnum::HTTP_OK : HttpStatusCodesEnum::HTTP_CREATED);
    $this->set(compact('data')); // Pass $data to the view
    $this->set('_serialize', 'data');
  }

  /**
   * Get CO Person Roles
   *
   * @since  COmanage Registry v4.3.0
   */

  public function personroles($id) {
    $this->request->allowMethod('ajax');
    $this->layout = 'ajax';

    if (empty($this->request->params['pass'][0])) {
      $this->log(__METHOD__ . "::message " . _txt('pl.er.eligibilitywidget.param.notfound', array(_txt('ct.eligibility_widget.1') . ' Id')), LOG_ERROR);
      throw new BadRequestException(_txt('pl.er.eligibilitywidget.param.notfound', array(_txt('ct.eligibility_widget.1') . ' Id')));
    }
    if(empty($this->request->query["copersonid"])) {
      $this->log(__METHOD__ . "::message " . _txt('pl.er.eligibilitywidget.param.notfound', array('copersonid')), LOG_ERROR);
      throw new BadRequestException(_txt('pl.er.eligibilitywidget.param.notfound', array('copersonid')));
    }

    // I need to verify that the CO Person is part of the CO
    $copersonid = $this->request->query["copersonid"];
    if(!$this->Role->isCoPerson($copersonid, $this->cur_co["Co"]["id"])) {
      throw new NotFoundException(_txt('er.cop.nf', array($copersonid)));
    }

    $cfg = $this->CoEligibilityWidget->getConfig();

    try{
      $args = array();
      $args['conditions']['CoPersonRole.co_person_id'] = $copersonid;
      // We only care about the ones that belong to a COU
      $args['conditions'][] = 'CoPersonRole.cou_id IS NOT NULL';
      if($cfg['CoEligibilityWidget']["mode"] == RegistrationModeEnum::OIS) {
        $args['conditions'][] = 'CoPersonRole.source_org_identity_id IS NOT NULL';
      }
      $args['contain'] = false;

      $roles = $this->CoPersonRole->find('all', $args);
    } catch(Exception $e) {
      $this->log(__METHOD__ . "::message " .$e->getMessage(), LOG_ERROR);
      // Double quotes are not JSON accepted
      throw new BadRequestException(str_replace('"', "", $e->getMessage()));
    }

    $data = array(
      'CoPersonRoles' => Hash::extract($roles, '{n}.CoPersonRole')
    );
    $this->Api->restResultHeader(HttpStatusCodesEnum::HTTP_OK);
    $this->set(compact('data')); // Pass $data to the view
    $this->set('_serialize', 'data');
  }


  /**
   * Sync/Update OrgIdentitySources
   *
   * @since  COmanage Registry v4.3.0
   */

  public function sync($id) {
    $this->request->allowMethod('ajax');
    $this->layout = 'ajax';

    if(empty($this->request->query["copersonrole"])) {
      $this->log(__METHOD__ . "::message " . _txt('pl.er.eligibilitywidget.param.specify'), LOG_ERROR);
      throw new InvalidArgumentException(_txt('pl.er.eligibilitywidget.param.specify'));
    }

    try{
      $data = $this->CoEligibilityWidget->syncEligibility($this->request->query["copersonrole"]);
    } catch(Exception $e) {
      $this->log(__METHOD__ . "::message " .$e->getMessage(), LOG_ERROR);
      throw new BadRequestException(str_replace('"', "", $e->getMessage()));
    }

    $this->set(compact('data')); // Pass $data to the view
    $this->set('_serialize', 'data');
  }

  /**
   * Authorization for this Controller, called by Auth component
   * - precondition: Session.Auth holds data used for authz decisions
   * - postcondition: $permissions set with calculated permissions
   *
   * @since  COmanage Registry v4.3.0
   * @return Array Permissions
   */
  
  function isAuthorized() {
    $roles = $this->Role->calculateCMRoles();
    
    // Determine what operations this user can perform
    
    // Construct the permission set for this user, which will also be passed to the view.
    // Ask the parent to calculate the display permission, based on the configuration.
    // Note that the display permission is set at the Dashboard, not Dashboard Widget level.
    $p = $this->calculateParentPermissions($roles);
    
    // Delete an existing CO Eligibility Widget?
    $p['delete'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // Edit an existing CO Eligibility Widget?
    $p['edit'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // View an existing CO Eligibility Widget?
    $p['view'] = ($roles['cmadmin'] || $roles['coadmin']);

    // Assign a role a CO Person?
    $p['assign'] = ($roles['cmadmin'] || $roles['coadmin'] || $roles['comember']);

    // Get all active Roles
    $p['active'] = ($roles['cmadmin'] || $roles['coadmin'] || $roles['comember']);

    // Get all COUs
    $p['all'] = ($roles['cmadmin'] || $roles['coadmin'] || $roles['comember']);

    // Assign a role a CO Person via an OrgIdentity Source
    $p['eligibility'] = ($roles['cmadmin'] || $roles['coadmin'] || $roles['comember']);

    // Sync CO Person Role via an OrgIdentity Source
    $p['sync'] = ($roles['cmadmin'] || $roles['coadmin'] || $roles['comember']);

    // Get CoPersonRoles. Self service access to CoPersonRoles for the CO Person.
    $p['personroles'] = ($roles['cmadmin'] || $roles['coadmin'] || $roles['comember']);

    $this->set('permissions', $p);
    return($p[$this->action]);
  }

  /**
   * Override the default sanity check performed in AppController
   *
   * @since  COmanage Registry v4.3.0
   * @return Boolean True if sanity check is successful
   */

  public function verifyRequestedId() {
    return true;
  }


  /**
   * For Models that accept a CO ID, find the provided CO ID.
   * - precondition: A coid must be provided in $this->request (params or data)
   *
   * @since  COmanage Registry v4.3.0
   * @return Integer The CO ID if found, or -1 if not
   */

  public function parseCOID($data = null) {
    if($this->action == 'display') {
      return parent::parseCOID($data);
    }
    $cfg = $this->CoEligibilityWidget->getConfig();
    $this->CoEligibilityWidget->CoDashboardWidget->id = $cfg['CoEligibilityWidget']["co_dashboard_widget_id"];
    $this->CoEligibilityWidget->CoDashboardWidget->CoDashboard->id = $this->CoEligibilityWidget->CoDashboardWidget->field('co_dashboard_id');
    $co_id = $this->CoEligibilityWidget->CoDashboardWidget->CoDashboard->field('co_id');

    if(!empty($co_id)) {
      return $co_id;
    }
  }
}
