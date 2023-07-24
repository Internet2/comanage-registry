<?php
/**
 * COmanage Registry OIS Registration Model
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


class OisRegistration extends AppModel {
  public $name = "OisRegistration";

  // Add behaviors
  public $actsAs = array('Containable');

  // Association rules from this model to other models
  public $belongsTo = array(
    "EligibilityWidget.CoEligibilityWidget",
    "OrgIdentitySource"
  );

  // Default display field for cake generated views
  public $displayField = "OisRegistration.description";

  // Validation rules for table elements
  public $validate = array(
    'co_eligibility_widget_id' => array(
      'rule' => 'numeric',
      'required' => true,
      'allowEmpty' => false
    ),
    'org_identity_source_id' => array(
      'rule' => 'numeric',
      'required' => true,
      'allowEmpty' => false
    ),
    'description' => array(
      'rule' => array('custom', '/^[A-Za-z0-9-_\s.]*$/'),
      'required' => true,
      'allowEmpty' => false,
      'message' => 'A description must be provided and consist of alphanumeric characters and space, dash or underscore'
    ),
    'ordr' => array(
      'rule' => 'numeric',
      'required' => false, // XXX We disable presence validation since we will handle it manually in the beforeSave Callback
      'allowEmpty' => true,
    ),
  );

  /**
   * Actions to take after a save operation is executed.
   *
   * @since  COmanage Registry v4.3.0
   * @param  boolean $created True if a new record was created (rather than update)
   * @param  array   $options As passed into Model::save()
   */

  public function afterSave($created, $options = array()) {
    $this->_commit();

    return true;
  }

  /**
   * Actions to take before a save operation is executed.
   *
   * @since  COmanage Registry v4.3.0
   */

  public function beforeSave($options = array()) {
    // Start a transaction -- we'll commit in afterSave.

    $this->_begin();

    if(empty($this->data['OisRegistration']['ordr'])) {
      // Find the current high value and add one
      $n = 1;

      $args = array();
      $args['fields'][] = "MAX(OisRegistration.ordr) as m";
      $args['conditions']['OisRegistration.co_eligibility_widget_id'] = $this->data['OisRegistration']['co_eligibility_widget_id'];
      $args['order'][] = "m";

      $o = $this->find('first', $args);

      if(!empty($o[0]['m'])) {
        $n = $o[0]['m'] + 1;
      }

      $this->data['OisRegistration']['ordr'] = $n;
    }

    return true;
  }

  /**
   * Obtain the CO ID for a record.
   *
   * @since  COmanage Registry v4.3.0
   * @param  integer Record to retrieve for
   * @return integer Corresponding CO ID, or NULL if record has no corresponding CO ID
   * @throws InvalidArgumentException
   * @throws RunTimeException
   */

  public function findCoForRecord($id) {
    $args = array();
    $args['conditions']['OisRegistration.id'] = $id;
    $args['contain'] = array('CoEligibilityWidget' => array('CoDashboardWidget' => array("CoDashboard")));

    $rec = $this->find('first', $args);

    if(!empty($rec["CoEligibilityWidget"]["CoDashboardWidget"]["CoDashboard"]["co_id"])) {
      return $rec["CoEligibilityWidget"]["CoDashboardWidget"]["CoDashboard"]["co_id"];
    }

    return parent::findCoForRecord($id);
  }

  /**
   * Obtain Parent record.
   *
   * @since  COmanage Registry v4.3.0
   * @param  integer  Parent Id to retrieve record for
   * @param  integer  Child Id to retrieve record for
   * @return array    Records from Database
   * @throws InvalidArgumentException
   * @throws RunTimeException
   */

  public function findParentRecord($parentId = null, $childId = null) {
    if(empty($parentId) && empty($childId)) {
      return array();
    }

    $args = array();
    if(!empty($childId)) {
      $args['joins'][0]['table']                            = 'cm_ois_registrations';
      $args['joins'][0]['alias']                            = 'OisRegistration';
      $args['joins'][0]['type']                             = 'INNER';
      $args['joins'][0]['conditions'][0]                    = 'CoEligibilityWidget.id=OisRegistration.co_eligibility_widget_id';
      $args['conditions']['OisRegistration.id'] = $childId;
    } else {
      $args['conditions']['CoEligibilityWidget.id'] = $parentId;
    }
    $args['contain'] = array(
      'CoDashboardWidget' => array('CoDashboard'),
      'OisRegistration'
    );

    return $this->CoEligibilityWidget->find('first', $args);
  }

}