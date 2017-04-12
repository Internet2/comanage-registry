<?php
/**
 * COmanage Registry CO Message Template Model
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
 * @since         COmanage Registry v2.0.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */
  
class CoMessageTemplate extends AppModel {
  // Define class name for cake
  public $name = "CoMessageTemplate";
  
  // Current schema version for API
  public $version = "1.0";
  
  // Association rules from this model to other models
  public $belongsTo = array(
    "Co"
  );
  
  public $hasMany = array(
    // "Expiration" makes the label too long
    "CoExpActNotifyMessageTemplate" => array(
      'className' => 'CoExpirationPolicy',
      'foreignKey' => 'act_notification_template_id'
    ),
    "CoEnrollmentFlowApprovalMessageTemplate" => array(
      'className' => 'CoEnrollmentFlow',
      'foreignKey' => 'approval_template_id'
    ),
    "CoEnrollmentFlowFinMessageTemplate" => array(
      // "Finalization" makes the label too long
      'className' => 'CoEnrollmentFlow',
      'foreignKey' => 'finalization_template_id'
    ),
    "CoEnrollmentFlowVerMessageTemplate" => array(
      // "Verification" makes the label too long
      'className' => 'CoEnrollmentFlow',
      'foreignKey' => 'verification_template_id'
    )
  );

  // Default display field for cake generated views
  public $displayField = "description";
  
  public $actsAs = array('Containable',
                         'Changelog' => array('priority' => 5));
  
  // Validation rules for table elements
  public $validate = array(
    'co_id' => array(
      'rule' => 'numeric',
      'required' => true,
      'message' => 'A CO ID must be provided'
    ),
    'description' => array(
      'rule' => array('validateInput'),
      'required' => true,
      'allowEmpty' => false
    ),
    'context' => array(
      'rule' => array('inList', array(MessageTemplateEnum::EnrollmentApproval,
                                      MessageTemplateEnum::EnrollmentFinalization,
                                      MessageTemplateEnum::EnrollmentVerification,
                                      MessageTemplateEnum::ExpirationNotification)),
      'required' => true
    ),
    'cc' => array(
      // Cake email validation assumes one address, but we allow multiple comma separated
      // For now, we perform no meaningful validation
      'rule' => 'notBlank',
      'required' => false,
      'allowEmpty' => true
    ),
    'bcc' => array(
      'rule' => 'notBlank',
      'required' => false,
      'allowEmpty' => true
    ),
    'message_subject' => array(
      'rule' => 'notBlank',
      'required' => true,
      'allowEmpty' => false
    ),
    'message_body' => array(
      'rule' => 'notBlank',
      'required' => true,
      'allowEmpty' => false
    ),
    'status' => array(
      'rule' => array('inList', array(SuspendableStatusEnum::Active,
                                      SuspendableStatusEnum::Suspended)),
      'required' => true
    )
  );
  
  /**
   * Duplicate an existing Message Template.
   *
   * @since  COmanage Registry v2.0.0
   * @param  Integer $id CO Message Template ID
   * @return Boolean True on success
   * @throws InvalidArgumentException
   * @throws RuntimeException
   */
  
  public function duplicate($id) {
    // First pull all the stuff we'll need to copy.
    
    $args = array();
    $args['conditions']['CoMessageTemplate.id'] = $id;
    $args['contain'] = false;
    
    // This find will not pull archived or deleted attributes (as managed via
    // Changelog behavior), which seems about right. However, we'll want to clear
    // the attribute changelog metadata, below.
    $mt = $this->find('first', $args);
    
    if(empty($mt)) {
      throw new InvalidArgumentException(_txt('er.notfound', array(_txt('ct.co_message_templates.1'), $id)));
    }
    
    // We need to relabel the template
    
    $mt['CoMessageTemplate']['description'] = _txt('fd.copy-a', array($mt['CoMessageTemplate']['description']));
    
    // And remove any keys (Cake will re-key on save)
    
    unset($mt['CoMessageTemplate']['id']);
    unset($mt['CoMessageTemplate']['created']);
    unset($mt['CoMessageTemplate']['modified']);
    // For changelog behavior
    unset($mt['CoMessageTemplate']['revision']);
    unset($mt['CoMessageTemplate']['co_message_template_id']);
    unset($mt['CoMessageTemplate']['actor_identifier']);
    unset($mt['CoMessageTemplate']['deleted']);
    
    // We explicitly disable validation here for a couple of reasons. First, we're
    // copying a record in the database, so the values should already be valid.
    // Second, this isn't always the case, because sometimes the data model gets
    // updated, introducing new fields. When this happens, the existing records
    // may have (eg) a null instead of a false (which validation would expect).
    // There's no real reason to fail to save in such a scenario.
    
    if(!$this->save($mt, false)) {
      throw new RuntimeException(_txt('er.db.save'));
    }
    
    return true;
  }
}
