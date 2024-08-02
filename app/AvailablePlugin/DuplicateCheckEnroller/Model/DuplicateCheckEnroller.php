<?php
/**
 * COmanage Registry Duplicate Check Enroller Plugin Model
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

class DuplicateCheckEnroller extends AppModel {
  // Required by COmanage Plugins
  public $cmPluginType = "enroller";

  // Document foreign keys
  public $cmPluginHasMany = array(
    "CoEnrollmentFlowWedge" => "DuplicateCheckEnroller"
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
    'identifier_type' => array(
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
      'content' => array(
        'rule' => '/.*/',
        'required' => false,
        'allowEmpty' => true
      ),
      'length' => array(
        'rule' => array('maxLength', 1024),
        'required' => false,
        'allowEmpty' => true,
        'message' => 'URL length should be at most 512 characters.'
      ),
    )
  );

  /**
   * Check for CO Person Identifier duplicates.
   *
   * @since  COmanage Registry v4.3.0
   * @param  integer $coId                    CO ID
   * @param  string  $env_remote_user         Identifier from Env
   * @param  string  $ident_type              Identifier Type
   * @return Array
   */

  public function searchCoPersonDuplicate($coId, $env_remote_user, $ident_type) {
    $args = array();
    $args['joins'][0]['table'] = 'co_people';
    $args['joins'][0]['alias'] = 'CoPerson';
    $args['joins'][0]['type'] = 'INNER';
    $args['joins'][0]['conditions'][0] = 'Identifier.co_person_id=CoPerson.id';
    $args['conditions']['Identifier.identifier'] = $env_remote_user;
    $args['conditions']['Identifier.status'] = SuspendableStatusEnum::Active;
    $args['conditions']['Identifier.type'] = $ident_type;
    $args['conditions']['CoPerson.co_id'] = $coId;
    $args['conditions']['CoPerson.status'] = array(StatusEnum::Active, StatusEnum::GracePeriod);
    $args['fields'] = array('CoPerson.id');
    $args['contain'] = false;

    $Identifier = ClassRegistry::init('Identifier');
    return $Identifier->find('first', $args);
  }


  /**
   * Check for CO Person Identifier duplicates through OrgIdentity.
   *
   * @since  COmanage Registry v4.3.0
   * @param  integer $coId                    CO ID
   * @param  string  $env_remote_user         Identifier from Env
   * @param  string  $ident_type              Identifier Type
   * @return Array
   */

  public function findOrgIdentityDuplicate($coId, $env_remote_user, $ident_type) {
    $args = array();
    $args['joins'][0]['table'] = 'org_identities';
    $args['joins'][0]['alias'] = 'OrgIdentity';
    $args['joins'][0]['type'] = 'INNER';
    $args['joins'][0]['conditions'][0] = 'Identifier.org_identity_id=OrgIdentity.id';
    $args['joins'][1]['table'] = 'co_org_identity_links';
    $args['joins'][1]['alias'] = 'CoOrgIdentityLink';
    $args['joins'][1]['type'] = 'INNER';
    $args['joins'][1]['conditions'][0] = 'OrgIdentity.id=CoOrgIdentityLink.org_identity_id';
    $args['joins'][2]['table'] = 'co_people';
    $args['joins'][2]['alias'] = 'CoPerson';
    $args['joins'][2]['type'] = 'INNER';
    $args['joins'][2]['conditions'][0] = 'CoPerson.id=CoOrgIdentityLink.co_person_id';
    $args['conditions']['Identifier.identifier'] = $env_remote_user;
    $args['conditions']['Identifier.status'] = SuspendableStatusEnum::Active;
    $args['conditions']['Identifier.type'] = $ident_type;
    $args['conditions']['OrgIdentity.co_id'] = $coId;
    $args['conditions']['CoPerson.status'] = array(StatusEnum::Active, StatusEnum::GracePeriod);
    $args['fields'] = array('CoPerson.id', 'OrgIdentity.id', 'Identifier.id');
    $args['contain'] = false;

    $Identifier = ClassRegistry::init('Identifier');
    return $Identifier->find('first', $args);
  }

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