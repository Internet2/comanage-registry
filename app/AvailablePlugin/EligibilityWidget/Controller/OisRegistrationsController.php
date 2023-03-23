<?php
/**
 * COmanage Registry Transfer PreserveA ppointments Controller
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
 * @since         COmanage Registry v4.3.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::uses("StandardController", "Controller");
class OisRegistrationsController extends StandardController {

  // Class name, used by Cake
  public $name = "OisRegistrations";

  public $uses = array(
    // XXX OisRegistration must go first!
    'EligibilityWidget.OisRegistration',
    'EligibilityWidget.CoEligibilityWidget',
    'LigoMouTransferEnroller.LigoMouTransferEnroller',
    'OrgIdentitySource'
  );

  public $edit_contains = array(
    'CoEligibilityWidget' => array('CoDashboardWidget' => array('CoDashboard')),
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
  );

  /**
   * Callback after controller methods are invoked but before views are rendered.
   * - precondition: Request Handler component has set $this->request
   *
   * @since  COmanage Registry v4.3.0
   */

  public function beforeRender() {
    parent::beforeRender();

    if($this->action == 'add') {
      // Pull the types from the parent table
      if (empty($this->request->params["named"]["cewid"])) {
        throw new InvalidArgumentException(
          _txt('pl.er.ois_registration.cewid.specify'),
          HttpStatusCodesEnum::HTTP_BAD_REQUEST
        );
      }

      $args = array();
      $args['conditions']['CoEligibilityWidget.id'] = $this->request->params["named"]["cewid"];
      $args['contain'] = array('CoDashboardWidget' => array('CoDashboard'));

      $co_eligibility_widgets = $this->CoEligibilityWidget->find('all', $args);
      if(empty($co_eligibility_widgets)) {
        throw new InvalidArgumentException(_txt('er.notfound',
                                                array(_txt('ct.co_eligibility_widgets.1'),
                                                  filter_var($this->request->params["named"]["cewid"],FILTER_SANITIZE_SPECIAL_CHARS))));
      }
      // Eligiblity Widget configuration
      $this->set('co_eligibility_widgets', $co_eligibility_widgets);

      // Get the Organizational Identity Sources already used by the Plugin
      $args = array();
      $args['conditions']['OisRegistration.co_eligibility_widget_id'] = $this->request->params["named"]["cewid"];
      $args['contain'] = false;

      $oises_used = $this->OisRegistration->find('all', $args);
      $oises_used_list = Hash::extract($oises_used, '{n}.OisRegistration.org_identity_source_id');
      $this->set("vv_ois_list_used", $oises_used_list);
    } elseif($this->action == 'edit') {
      $args = array();
      $args['conditions']['CoEligibilityWidget.id'] = $this->viewVars["ois_registrations"][0]["CoEligibilityWidget"]["id"];
      $args['contain'] = array('CoDashboardWidget' => array('CoDashboard'));

      $co_eligibility_widgets = $this->CoEligibilityWidget->find('all', $args);
      if(empty($co_eligibility_widgets)) {
        throw new InvalidArgumentException(_txt('er.notfound',
                                                array(_txt('ct.co_eligibility_widgets.1'),
                                                  filter_var($this->request->params["named"]["cewid"],FILTER_SANITIZE_SPECIAL_CHARS))));
      }
      // Eligibility Widget configuration
      $this->set('co_eligibility_widgets', $co_eligibility_widgets);

      // Get the Organizational Identity Sources already used by the Plugin
      $args = array();
      $args['conditions']['OisRegistration.co_eligibility_widget_id'] = $this->viewVars["ois_registrations"][0]["CoEligibilityWidget"]["id"];
      $args['contain'] = false;

      $oises_used = $this->OisRegistration->find('all', $args);
      $oises_used_list = Hash::extract($oises_used, '{n}.OisRegistration.org_identity_source_id');
      $this->set("vv_ois_list_used", $oises_used_list);
    }


    // Get all the Active and in Query Sync Mode
    // OrganizationIdentitySources
    // Query:
    // Similar to Enrollment Sources Search mode, query the Organizational Identity Source for any records matching verified email addresses of all Org Identities,
    // looking for new matching records to link. Also update (or delete, if appropriate) existing records.
    //
    //  (warning) Query mode should only be used for Organizational Identity Sources attached to a Registry Pipeline configured for email address-based matching.
    //  Otherwise, linking to existing CO People may not happen correctly.
    //  In Query mode, if a Organizational Identity Source is queried for an email address and the Source returns a record with a different email address
    //  (eg: the person changed their email address in the other system), by default a new Org Identity (and probably CO Person) will be created.
    //  This is because Registry has not confirmed the alternate email address and cannot trust the Organizational Identity Source asserting a record linkage.
    //  This corresponds to the Email Mismatch Mode of Create New Org Identity. Alternately, Email Mismatch Mode can be set to Ignore, in which case no action is taken.
    //  In Query mode, by default the Organizational Identity Source will be re-queried for all email addresses, even those already attached
    //  to an Org Identity associated with the Source. This is to allow for the checking of additional records associated with the same email address.
    //  However, this can also create a large number of extra queries, if the Source is known not to create such records (or if such records are not of interest).
    //  To disable this behavior, set (tick the box for) Do Not Query for Known Email Addresses.
    $args = array();
    $args['conditions']['OrgIdentitySource.status'] = SuspendableStatusEnum::Active;
    $args['conditions']['OrgIdentitySource.sync_mode'] = SyncModeEnum::Query;
    $args['conditions']['OrgIdentitySource.co_id']=  $this->cur_co['Co']['id'];
    $args['conditions'][] = 'OrgIdentitySource.co_pipeline_id IS NOT NULL';
    $args['contain'] = array(
      'CoPipeline' => array(
          'conditions' => array(
            'CoPipeline.deleted IS NOT TRUE',
            'CoPipeline.co_pipeline_id IS NULL',
            // We required a COU to be linked to this COU
            'CoPipeline.sync_cou_id IS NOT NULL',
          ),
          'SyncCou'
        )
    );

    $oises = $this->OrgIdentitySource->find('all', $args);

    // Re-sort the results by OIS ID (which are unique across as CO IDs)

    $oisConfigs = Hash::combine($oises, '{n}.OrgIdentitySource.id', '{n}.OrgIdentitySource.description');
    $this->set("vv_ois_list", $oisConfigs);
    $this->set("vv_ois", $oises);

    if ($this->request->action == 'add') {
      $this->set('title_for_layout', _txt('op.add.new', array(_txt('ct.ois_registrations.1'))));
    }
  }

  /**
   * Determine the CO ID based on some attribute of the request.
   * This method is intended to be overridden by model-specific controllers.
   *
   * @since  COmanage Registry v4.3.0
   * @return Integer CO ID, or null if not implemented or not applicable.
   * @throws InvalidArgumentException
   */

  protected function calculateImpliedCoId($data = null) {
    if(empty($this->request->params["named"]["cewid"]) && $this->action == "add") {
      throw new InvalidArgumentException(_txt('pl.er.ois_registration..cewid.specify'), HttpStatusCodesEnum::HTTP_BAD_REQUEST);
    }

    if(empty($this->request->params["pass"][0]) && in_array($this->action, array("delete", "edit"))) {
      throw new InvalidArgumentException(_txt('pl.er.eligibilitywidget.id.specify'), HttpStatusCodesEnum::HTTP_BAD_REQUEST);
    }

    $eligibility_widget = array();
    if(!empty($this->request->params["pass"][0])) {
      $eligibility_widget = $this->OisRegistration->findParentRecord(null, $this->request->params["pass"][0]);
    } else {
      $eligibility_widget = $this->OisRegistration->findParentRecord($this->request->params["named"]["cewid"]);
    }

    if(empty($eligibility_widget)) {
      if(!empty($this->request->params["pass"][0])) {
        throw new InvalidArgumentException(_txt('er.notfound',
                                                array(_txt('ct.ois_registrations.1'),
                                                  filter_var($this->request->params["pass"][0],FILTER_SANITIZE_SPECIAL_CHARS))));
      }
      throw new InvalidArgumentException(_txt('er.notfound',
                                              array(_txt('ct.eligibility_widgets.1'),
                                                filter_var($this->request->params["named"]["cewid"],FILTER_SANITIZE_SPECIAL_CHARS))));
    }

    $coId = $eligibility_widget["CoDashboardWidget"]["CoDashboard"]["co_id"];
    if($coId) {
      return $coId;
    }

    return parent::calculateImpliedCoId();
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

    // Determine what operations this user can perform

    // Add
    $p['add'] = ($roles['cmadmin'] || $roles['coadmin']);

    // Delete
    $p['delete'] = ($roles['cmadmin'] || $roles['coadmin']);

    // Edit
    $p['edit'] = ($roles['cmadmin'] || $roles['coadmin']);

    // View
    $p['index'] = ($roles['cmadmin'] || $roles['coadmin']);

    // View
    $p['view'] = ($roles['cmadmin'] || $roles['coadmin']);

    $this->set('permissions', $p);
    return($p[$this->action]);
  }

  /**
   * Perform a redirect back to the controller's default view.
   * - postcondition: Redirect generated
   *
   * @since  COmanage Registry v4.3.0
   */

  public function performRedirect() {
    $target = array();
    $target['plugin'] = 'eligibility_widget';
    $target['controller'] = "co_eligibility_widgets";
    $target['action'] = 'edit';
    if(!empty($this->request->query["cewid"])) {
      $target[] = $this->request->query["cewid"];
    } elseif (!empty($this->OisRegistration->data['CoEligibilityWidget']['id'])) {
      $target[] = $this->OisRegistration->data['CoEligibilityWidget']['id'];
    } else {
      $target[] = $this->data['OisRegistration']['co_eligibility_widget_id'];
    }

    $this->redirect($target);
  }

}