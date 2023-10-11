<?php
/**
 * COmanage Registry CO Eligibility Widget Model
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

App::uses("CoDashboardWidgetBackend", "Model");

class CoEligibilityWidget extends CoDashboardWidgetBackend {
  // Define class name for cake
  public $name = "CoEligibilityWidget";

  // Add behaviors
  public $actsAs = array('Containable');

  // Association rules from this model to other models
  public $belongsTo = array(
    "CoDashboardWidget"
  );

  public $hasMany = array(
    "EligibilityWidget.OisRegistration" => array('dependent' => true)
  );


  // Validation rules for table elements
  public $validate = array(
    'co_dashboard_widget_id' => array(
      'rule' => 'numeric',
      'required' => true,
      'allowEmpty' => false
    ),
    'mode' => array(
      'content' => array(
        // For now we only support Organizational Identity Sources
        'rule' => array('inList', array(RegistrationModeEnum::OIS)),
        'required' => true,
        'allowEmpty' => false
      )
    )
  );

  /**
   * Obtain all COUs within a specified CO.
   *
   * @since  COmanage Registry v4.3.0
   * @param  integer CO ID
   * @return Array List of COU Id, Name
   * @todo This function is currently not used since it refers to a non supported mode
   */

  public function allCous($coId) {
    $args = array();
    $args['conditions']['Cou.co_id'] = $coId;
    $args['order'] = 'Cou.name ASC';
    $args['fields'] = array('Cou.name', 'Cou.id');
    $args['contain'] = false;

    $Cou = ClassRegistry::init('Cou');
    $cous = $Cou->find("all", $args);

    return Hash::extract($cous, '{n}.Cou') ?? array();
  }

  /**
   * Obtain all Organization Identity Sources enabled under Eligibility plugin
   * These OIS are linked to COUs and will facilitate the COU enrollment process
   *
   * @param  integer Eligibility Widget Id
   *
   * @return Array [OisRegistration, OisRegistration<List>]
   * @since  COmanage Registry v4.3.0
   */

  public function allOisRegistration($ewid) {
    $args = array();
    $args['conditions']['OisRegistration.co_eligibility_widget_id'] = $ewid;
    $args['contain'] = array(
      'OrgIdentitySource' => array(
        'CoPipeline' => array(
          'conditions' => array(
            'CoPipeline.deleted IS NOT TRUE',
            'CoPipeline.co_pipeline_id IS NULL',
            // We required a COU to be linked to this COU
            'CoPipeline.sync_cou_id IS NOT NULL',
          )
        )
      )
    );

    $ois_list = $this->OisRegistration->find('all', $args);
//    $this->OisRegistration->getDataSource()->getLog(false, false);
    $all_cou_ids = Hash::extract($ois_list, '{n}.OrgIdentitySource.CoPipeline.sync_cou_id');

    // We should never get in here. Though if for any reason the cou id list is empty the query
    // will retrieve everything
    if(empty($all_cou_ids)) {
      return [array(), Hash::extract($ois_list, '{n}.OisRegistration') ?? array()];
    }

    // Get all COUs
    $args = array();
    $args['conditions']['Cou.id'] = array_unique($all_cou_ids);
    $args['contain'] = false;
    $Cou = ClassRegistry::init('Cou');
    $cous_resp = $Cou->find('all', $args);

    $all_cous = Hash::extract($cous_resp, '{n}.Cou');
    // From the SyncCou i need to get all the user's active COUs
    return [$all_cous ?? array(), Hash::extract($ois_list, '{n}.OisRegistration') ?? array()];
  }

  /**
   * Obtain all Person OrgIdentities created from an OrgIdentitySource
   *
   * @since  COmanage Registry v4.3.0
   * @param  integer CO ID
   * @return Array List of COU Id, Name
   */

  public function allPersonOrgIdentityFromSource($coId, $coPersonId) {
    // We all Person OrgIdentities that are linked to Source and are associated with a Role

    $args = array();
    $args['joins'][0]['table'] = 'co_org_identity_links';
    $args['joins'][0]['alias'] = 'CoOrgIdentityLink';
    $args['joins'][0]['type'] = 'INNER';
    $args['joins'][0]['conditions'][0] = 'CoOrgIdentityLink.org_identity_id=OrgIdentity.id';
    $args['conditions']['CoOrgIdentityLink.co_person_id'] = $coPersonId;
    $args['conditions'][] = 'PipelineCoPersonRole.source_org_identity_id IS NOT NULL';
    $args['conditions']['OrgIdentity.co_id'] = $coId;
    $args['contain'] = false;

    $OrgIdentity = ClassRegistry::init('OrgIdentity');
    $org_identities = $OrgIdentity->find('all', $args);

    return $org_identities;

  }

  /**
   * Check Person eligibility
   *
   * @since  COmanage Registry v4.3.0
   * @param  integer $oidId       Organizational Source ID
   * @param  integer $coId        CO ID
   * @param  integer $coPersonId  CO Person ID
   * @throws InvalidArgumentException
   * @return void
   */

  public function checkEligibility($oidId, $coId, $coPersonId) {
    // Pull all CoPerson's verified emails
    $args = array();
    $args['conditions']['EmailAddress.co_person_id'] = $coPersonId;
    $args['conditions']['EmailAddress.verified'] = true;
    $args['contain'] = false;

    $EmailAddress = ClassRegistry::init('EmailAddress');
    $emailAddresses = $EmailAddress->find('all', $args);

    $ret = array();
    if(empty($emailAddresses)) {
      return $ret;
    }

    // Get the OrgIdentitySource
    $args = array();
    $args['conditions']['OrgIdentitySource.id'] = $oidId;
    $args['contain'] = false;

    $OrgIdentitySource = ClassRegistry::init('OrgIdentitySource');
    $orgIdentitySource = $OrgIdentitySource->find('first', $args);

    // Since the request is probably comming from REST i will check if plugin
    // full-fill the requirements
    if($orgIdentitySource['OrgIdentitySource']['sync_mode'] != SyncModeEnum::Query
       || $orgIdentitySource['OrgIdentitySource']['status'] != SuspendableStatusEnum::Active
       || is_null($orgIdentitySource['OrgIdentitySource']['co_pipeline_id'])) {
      throw new InvalidArgumentException(_txt('pl.er.eligibilitywidget.ois.inappropriate'));
    }

    foreach($emailAddresses as $ea) {
      if (!empty($ea['EmailAddress']['mail'])) {
        try {
          $oisResults = $OrgIdentitySource->search($oidId, array('mail' => $ea['EmailAddress']['mail']));
        } catch(Exception $e) {
          $this->log("ERROR: OrgIdentitySource " . $oidId . " : " . $e->getMessage());

          // If no match is found, an error is rendered, advising the user to confirm the correct email address
          // and perhaps add it via Self Service. This message should either be configurable
          // via localization or a redirect URL.

          throw new RuntimeException(_txt('pl.er.eligibilitywidget.no.match-1'));
        }

        $ret[$ea['EmailAddress']['mail']] = array();
        foreach($oisResults as $sourceKey => $oisRecord) {
          // createOrgIdentity will also create the link to the CO Person. It may also
          // run a pipeline (if configured). Which Pipeline we want to run is a bit confusing,
          // since the Enrollment Flow, the OIS, and the CO can all have a pipeline configured.
          // The normal priority is EF > OIS > CO (as per OrgIdentity.php. However, since a
          // given EF can only create a single Org Identity, Org Identities created here aren't
          // attached to the Petition and therefore aren't considered to have been created
          // by an Enrollment Flow. So the Pipeline that will execute is either the one
          // attached to the OIS, or if none the one attached to the CO.

          try {
            $newOrgIdentityId = $OrgIdentitySource->createOrgIdentity($oidId,
                                                                      $sourceKey,
                                                                      $coPersonId,
                                                                      $coId,
                                                                      $coPersonId,
                                                                      false);

            $ret[$ea['EmailAddress']['mail']][] = $newOrgIdentityId;
            $EmailAddress->CoPerson->HistoryRecord->record($coPersonId,
                                                           null,
                                                           $newOrgIdentityId,
                                                           $coPersonId,
                                                           PetitionActionEnum::IdentityLinked,
                                                           _txt('rs.pt.ois.link', array($newOrgIdentityId,
                                                             $orgIdentitySource['OrgIdentitySource']['description'],
                                                             $oidId)));
          }
          catch(OverflowException $e) {
            // If there's already an org identity associated with the OIS, we
            // definitely don't link it,
            // We will sync the orgIdentity.
            $info = $OrgIdentitySource->syncOrgIdentity($oidId, $sourceKey);
            $ret[$ea['EmailAddress']['mail']] = $info;
          }
          // else let the exception pass back up the stack
        }

      }
    }

    return $ret;
  }

  /**
   * Sync
   *
   * @since  COmanage Registry v4.3.0
   * @param  integer $coPersonRoleId  CO Person ID
   * @throws InvalidArgumentException
   * @throws RuntimeException
   * @return void
   */
  public function syncEligibility($coPersonRoleId) {
    // Get the OrgIdentitySource from the Role id
    $args = array();
    $args['joins'][0]['table'] = 'co_person_roles';
    $args['joins'][0]['alias'] = 'CoPersonRole';
    $args['joins'][0]['type'] = 'INNER';
    $args['joins'][0]['conditions'][1] = 'CoPersonRole.source_org_identity_id=OrgIdentitySourceRecord.org_identity_id';
    $args['conditions']['CoPersonRole.id'] = $coPersonRoleId;
    $args['conditions'][] = 'CoPersonRole.deleted is not true';
    $args['conditions'][] = 'CoPersonRole.co_person_role_id is null';
    $args['conditions'][] = 'OrgIdentitySourceRecord.deleted is not true';
    $args['conditions'][] = 'OrgIdentitySourceRecord.org_identity_source_record_id is null';
    $args['contain'] = false;
    $args['fields'] = array(
      'OrgIdentitySourceRecord.org_identity_source_id',
      'OrgIdentitySourceRecord.sorid',
      'OrgIdentitySourceRecord.source_record'
    );

    $OrgIdentitySource = ClassRegistry::init('OrgIdentitySource');
    $orgIdentitySourceRecord = $OrgIdentitySource->OrgIdentitySourceRecord->find('first', $args);

    // Since the request is probably comming from REST i will check if plugin
    // full-fill the requirements
    if(empty($orgIdentitySourceRecord)) {
      throw new InvalidArgumentException(_txt('pl.er.eligibilitywidget.ois.norecord'));
    }

    $sourceKey = $orgIdentitySourceRecord["OrgIdentitySourceRecord"]["sorid"];
    $oisId = $orgIdentitySourceRecord["OrgIdentitySourceRecord"]["org_identity_source_id"];

    try {
      $info = $OrgIdentitySource->syncOrgIdentity($oisId, $sourceKey);
    } catch (Exception $e) {

    }
    return $info;
  }

  /**
   * Obtain all COU memberships
   *
   * @since  COmanage Registry v4.3.0
   * @param  integer   CO Person ID
   * @param  Array     Person Role Status
   * @param  Boolean   Is this Role associated to an Organization Identity Source
   * @return Array   List of COU Id, Name
   */

  public function personCouMembership($copersonid,
                                      $status=[StatusEnum::Active, StatusEnum::GracePeriod],
                                      $fromOis=false) {
    $CoPersonRole = ClassRegistry::init('CoPersonRole');

    $args = array();
    $args['joins'][0]['table'] = 'cous';
    $args['joins'][0]['alias'] = 'Cou';
    $args['joins'][0]['type'] = 'INNER';
    $args['joins'][0]['conditions'][] = 'CoPersonRole.cou_id=Cou.id';
    $args['joins'][0]['conditions'][] = 'Cou.deleted IS NOT TRUE';
    $args['joins'][0]['conditions'][] = 'Cou.cou_id IS NULL';
    $args['conditions']['CoPersonRole.co_person_id'] = $copersonid;
    $args['conditions'][] = 'CoPersonRole.deleted IS NOT true';
    $args['conditions'][] = 'CoPersonRole.co_person_role_id IS NULL';
    $args['conditions']['CoPersonRole.status'] = $status;
    $args['fields'] = array('Cou.id', 'Cou.name');
    if($fromOis) {
      $args['joins'][1]['table'] = 'org_identity_source_records';
      $args['joins'][1]['alias'] = 'OrgIdentitySourceRecord';
      $args['joins'][1]['type'] = 'INNER';
      $args['joins'][1]['conditions'][] = 'CoPersonRole.source_org_identity_id=OrgIdentitySourceRecord.org_identity_id';
      $args['joins'][1]['conditions'][] = 'OrgIdentitySourceRecord.deleted IS NOT TRUE';
      $args['joins'][1]['conditions'][] = 'OrgIdentitySourceRecord.org_identity_source_record_id IS NULL';
      $args['joins'][2]['table'] = 'org_identity_sources';
      $args['joins'][2]['alias'] = 'OrgIdentitySource';
      $args['joins'][2]['type'] = 'INNER';
      $args['joins'][2]['conditions'][] = 'OrgIdentitySourceRecord.org_identity_source_id=OrgIdentitySource.id';
      $args['joins'][2]['conditions'][] = 'OrgIdentitySource.deleted IS NOT TRUE';
      $args['joins'][2]['conditions'][] = 'OrgIdentitySource.org_identity_source_id IS NULL';
      $args['joins'][3]['table'] = 'ois_registrations';
      $args['joins'][3]['alias'] = 'OisRegistration';
      $args['joins'][3]['type'] = 'INNER';
      $args['joins'][3]['conditions'][0] = 'OisRegistration.org_identity_source_id=OrgIdentitySource.id';

      $args['conditions'][] = 'CoPersonRole.source_org_identity_id IS NOT NULL';
      $args['fields'] = array(
        'Cou.id',
        'Cou.name',
        'CoPersonRole.source_org_identity_id',
        'OrgIdentitySource.id',
        'OisRegistration.id'
      );
    }
    $args['contain'] = false;

    // XXX OisRegistration does not have ChangelongBehavior enabled. As result we will
    //     unload it from the calling model because it throws an exception

    $CoPersonRole->Behaviors->unload('Changelog');

    $roles = $CoPersonRole->find('all', $args);
    if(empty($roles)) {
      return array();
    }

    $cous = Hash::extract($roles, '{n}.Cou');

    // For Mode 2 and the use case of Organizational Identity Source
    // we need the OIS id. As a result we parse our table, extract the id
    // and inject it back to the COU table for short
    if($fromOis) {
      $cous = Hash::flatten($roles);
    }

    return $cous;
  }

}