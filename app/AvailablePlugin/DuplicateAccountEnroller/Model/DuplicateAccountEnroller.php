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
    'redirect_url' => array(
      'rule' => '/.*/',
      'required' => false,
      'allowEmpty' => true
    )
  );

  /**
   * Return Pending Role
   *
   * @since  COmanage Registry v4.3.0
   * @param  Integer      $coId          CO  ID
   * @param  Integer      $coPetitionId  CO Petition  ID
   * @return array|null   CoPersonRole
   */

  public function getCoPersonRoleFromPetition($coId, $coPetitionId) {
    $args                                        = array();
    $args['joins'][0]['table']                   = 'cm_co_petitions';
    $args['joins'][0]['alias']                   = 'CoPetition';
    $args['joins'][0]['type']                    = 'INNER';
    $args['joins'][0]['conditions'][0]           = 'CoPetition.cou_id=Cou.id';
    $args['joins'][0]['conditions'][1]           = 'CoPetition.id=' . $coPetitionId;
    $args['conditions'][]                        = "CoPetition.enrollee_co_person_id=CoPersonRole.co_person_id";
    $args['conditions'][]                        = 'CoPersonRole.co_person_role_id IS NULL';
    $args['conditions'][]                        = 'CoPersonRole.deleted IS NOT TRUE';

    $CoPersonRole = ClassRegistry::init('CoPersonRole');
    return $CoPersonRole->find('first', $args);
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