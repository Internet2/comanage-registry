<?php
/**
 * COmanage Registry Duplicate Account Enroller Model
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

class DuplicateAccountEnroller extends AppModel {
  // Required by COmanage Plugins
  public $cmPluginType = "enroller";

  // Document foreign keys
  public $cmPluginHasMany = array(
    "CoEnrollmentFlowWedge" => "DuplicateAccountEnroller"
  );

  // Add behaviors
  public $actsAs = array('Containable', 'Changelog' => array('priority' => 5));

  // Association rules from this model to other models
  public $belongsTo = array("CoEnrollmentFlowWedge");

  public $hasMany = array();

  // Default display field for cake generated views
  public $displayField = "co_enrollment_flow_wedge_id";

  // Validation rules for table elements
  public $validate = array(
    'co_enrollment_flow_wedge_id' => array(
      'rule' => 'numeric',
      'required' => true,
      'allowEmpty' => false
    ),
    'env_remote_user' => array(
      'rule' => '/.*/',
      'required' => true,
      'allowEmpty' => false
    ),
    'type' => array(
      'content' => array(
        'rule' => array('validateExtendedType',
          array('attribute' => 'Identifier.type',
                'default' => array(IdentifierEnum::AffiliateSOR,
                                   IdentifierEnum::Badge,
                                   IdentifierEnum::Enterprise,
                                   IdentifierEnum::ePPN,
                                   IdentifierEnum::ePTID,
                                   IdentifierEnum::ePUID,
                                   IdentifierEnum::GuestSOR,
                                   IdentifierEnum::HRSOR,
                                   IdentifierEnum::Mail,
                                   IdentifierEnum::National,
                                   IdentifierEnum::Network,
                                   IdentifierEnum::OIDCsub,
                                   IdentifierEnum::OpenID,
                                   IdentifierEnum::ORCID,
                                   IdentifierEnum::ProvisioningTarget,
                                   IdentifierEnum::Reference,
                                   IdentifierEnum::SamlPairwise,
                                   IdentifierEnum::SamlSubject,
                                   IdentifierEnum::SORID,
                                   IdentifierEnum::StudentSOR,
                                   IdentifierEnum::UID))),
        'required' => true,
        'allowEmpty' => false
      )
    ),
    'redirect_url' => array(
      'rule' => '/.*/',
      'required' => false,
      'allowEmpty' => true
    )
  );

  /**
   * Perform a Match Request.
   *
   * @since  COmanage Registry v4.3.0
   * @param  integer $id                    CO Petition ID
   * @return boolean                          An array of options on potential match, or true if enrollment should continue
   */

//  public function performMatch($id) {
//    // This is probably already set, but just in case.
//    $this->id = $id;
//
//    // Pull the petition and Enrollment Flow configuration
//
//    $args = array();
//    $args['conditions']['CoPetition.id'] = $id;
//    $args['contain'] = array('CoEnrollmentFlow');
//
//    $pt = $this->find('first', $args);
//
//    // Pull the enrollment flow match server configuration
//
//    if($pt['CoEnrollmentFlow']['match_policy'] != EnrollmentMatchPolicyEnum::External
//      || empty($pt['CoEnrollmentFlow']['match_server_id'])) {
//      throw new InvalidArgumentException(_txt('er.notprov.id', array(_txt('ct.match_servers.1'))));
//    }
//
//    if(empty($pt['CoPetition']['enrollee_co_person_id'])) {
//      throw new InvalidArgumentException(_txt('er.notprov.id', array(_txt('ct.co_people.1'))));
//    }
//
//    $args = array();
//    // We don't have an exact index on this combo, but at least looking at the
//    // Postgres query plan it doesn't seem necessary.
//    $args['conditions'][] = 'Identifier.co_person_id IS NOT NULL';
//    $args['conditions']['Identifier.identifier'] = $referenceId;
//    $args['conditions']['Identifier.status'] = SuspendableStatusEnum::Active;
//    $args['conditions']['Identifier.type'] = IdentifierEnum::Reference;
//    $args['contain'] = false;
//
//    return !empty($this->EnrolleeCoPerson->Identifier->find('first', $args));
//  }

  /**
   * Expose menu items.
   *
   * @since COmanage Registry v4.3.0
   * @return Array with menu location type as key and array of labels, controllers, actions as values.
   */

  public function cmPluginMenus() {
    return array();
  }
}