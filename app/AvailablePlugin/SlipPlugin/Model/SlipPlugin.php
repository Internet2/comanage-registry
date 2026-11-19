<?php
/**
 * COmanage Registry SLIP Plugin Model
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
 * @since         COmanage Registry v4.7.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

class SlipPlugin extends AppModel {
  // Required by COmanage Plugins
  public $cmPluginType = "other";
  
  // Document foreign keys
  public $cmPluginHasMany = array(
    "CoGroup" => array(
      "ProofingExemptCoGroupId" => array(
        'className' => 'SlipPlugin',
        'foreignKey' => 'proofing_exempt_co_group_id'
      ),
      "ProofingValidCoGroupId" => array(
        'className' => 'SlipPlugin',
        'foreignKey' => 'proofing_valid_co_group_id'
      ),
      "RegistrarCoGroupId" => array(
        'className' => 'SlipPlugin',
        'foreignKey' => 'registrar_co_group_id'
      )
    ),
    "CoPerson" => array(
      "ClaimantCoPersonId" => array(
        'className' => 'SlipProofings',
        'foreignKey' => 'co_person_id'
      ),
      "InitiatorCoPersonId" => array(
        'className' => 'SlipProofings',
        'foreignKey' => 'initiator_co_person_id'
      ),
      "RegistrarCoPersonId" => array(
        'className' => 'SlipProofings',
        'foreignKey' => 'registrar_co_person_id'
      ),
      "VoucherCoPersonId" => array(
        'className' => 'SlipProofings',
        'foreignKey' => 'voucher_co_person_id'
      )
    ),
    "CoMessageTemplate" => array(
      "InitiationTemplateId" => array(
        'className' => 'SlipPlugin',
        'foreignKey' => 'initiation_template_id'
      ),
      "SchedulingTemplateId" => array(
        'className' => 'SlipPlugin',
        'foreignKey' => 'scheduling_template_id'
      ),
      "BindingTemplateId" => array(
        'className' => 'SlipPlugin',
        'foreignKey' => 'binding_template_id'
      ),
      "VouchingTemplateId" => array(
        'className' => 'SlipPlugin',
        'foreignKey' => 'vouching_template_id'
      ),
      "ApprovalTemplateId" => array(
        'className' => 'SlipPlugin',
        'foreignKey' => 'approval_template_id'
      )
    ),
    "IdentityDocument" => array(
      "SlipIdentityDocumentId" => array(
        'className' => 'SlipIdentityDocument',
        'foreignKey' => "identity_document_id"
      )
    )
  );
  
  // Add behaviors
  public $actsAs = array('Containable', 'Changelog' => array('priority' => 5));
  
  // Association rules from this model to other models
  public $belongsTo = array(
    "Co",
    "ProofingExemptCoGroup" => array(
      'className' => 'CoGroup',
      'foreignKey' => 'proofing_exempt_co_group_id'
    ),
    "ProofingValidCoGroup" => array(
      'className' => 'CoGroup',
      'foreignKey' => 'proofing_valid_co_group_id'
    ),
    "RegistrarCoGroup" => array(
      'className' => 'CoGroup',
      'foreignKey' => 'registrar_co_group_id'
    ),
    "InitiationMessageTemplate" => array(
      'className' => 'CoMessageTemplate',
      'foreignKey' => 'initiation_template_id'
    ),
    "SchedulingMessageTemplate" => array(
      'className' => 'CoMessageTemplate',
      'foreignKey' => 'scheduling_template_id'
    ),
    "BindingMessageTemplate" => array(
      'className' => 'CoMessageTemplate',
      'foreignKey' => 'binding_template_id'
    ),
    "VouchingMessageTemplate" => array(
      'className' => 'CoMessageTemplate',
      'foreignKey' => 'vouching_template_id'
    ),
    "ApprovalMessageTemplate" => array(
      'className' => 'CoMessageTemplate',
      'foreignKey' => 'approval_template_id'
    )
  );

  public $hasMany = array(
    'SlipPlugin.SlipProofing' => array('dependent' => true),
  );

  // Default display field for cake generated views
  public $displayField = "id";
  
  // Validation rules for table elements
  public $validate = array(
    'co_id' => array(
      'rule' => 'numeric',
      'required' => true,
      'allowEmpty' => false
    ),
    'proofing_exempt_co_group_id' => array(
      'content' => array(
        'rule' => array('numeric'),
        'required' => false,
        'allowEmpty' => true,
        'unfreeze' => 'CO'
      )
    ),
    'proofing_valid_co_group_id' => array(
      'content' => array(
        'rule' => array('numeric'),
        'required' => true,
        'allowEmpty' => false,
        'unfreeze' => 'CO'
      )
    ),
    'registrar_co_group_id' => array(
      'content' => array(
        'rule' => array('numeric'),
        'required' => true,
        'allowEmpty' => false,
        'unfreeze' => 'CO'
      )
    ),
    'initiation_template_id' => array(
      'content' => array(
        'rule' => array('numeric'),
        'required' => true,
        'allowEmpty' => false,
        'unfreeze' => 'CO'
      )
    ),
    'scheduling_template_id' => array(
      'content' => array(
        'rule' => array('numeric'),
        'required' => false,
        'allowEmpty' => true,
        'unfreeze' => 'CO'
      )
    ),
    'binding_template_id' => array(
      'content' => array(
        'rule' => array('numeric'),
        'required' => true,
        'allowEmpty' => false,
        'unfreeze' => 'CO'
      )
    ),
    'vouching_template_id' => array(
      'content' => array(
        'rule' => array('numeric'),
        'required' => true,
        'allowEmpty' => false,
        'unfreeze' => 'CO'
      )
    ),
    'approval_template_id' => array(
      'content' => array(
        'rule' => array('numeric'),
        'required' => true,
        'allowEmpty' => false,
        'unfreeze' => 'CO'
      )
    )
  );
  
  /**
   * Expose menu items.
   * 
   * @since COmanage Registry v4.7.0
   * @return Array with menu location type as key and array of labels, controllers, actions as values.
   */
  
  public function cmPluginMenus() {
    return array(
      "coconfig" => array(_txt('ct.slip_plugins.pl') =>
        array('icon'       => 'verified',
              'controller' => 'slip_plugins',
              'action'     => 'index')
      ),
      "copeople" => array(_txt('ct.slip_proofings.pl') =>
        array('controller' => 'slip_proofings',
              'action'     => 'index')
      ),
      "canvas" => array(_txt('ct.slip_proofings.pl') =>
        array('icon'       => 'check',
              'controller' => 'slip_proofings',
              'action'     => 'index')
      )
    );
  }
  
  /**
   * Obtain the SLIP Plugin Configuration ID for the specified CO, creating one with
   * default values if it doesn't already exist.
   *
   * @since  COmanage Registry v4.7.0
   * @param  Integer $coId CO ID
   * @return Integer       Slip Plugin ID
   * @throws RuntimeException
   */

  public function getConfigId($coId) {
    // Do we already have a configuration for this CO?

    $args = array();
    $args['conditions']['SlipPlugin.co_id'] = $coId;
    $args['contain'] = false;

    $s = $this->find('first', $args);

    if($s) {
      return $s['SlipPlugin']['id'];
    }

    // Otherwise create a basic configuration

    $s = array();
    $s['SlipPlugin']['co_id'] = $coId;

    // We don't have a default Registrar Group, so we need to disable validation on save

    $this->clear();
    $this->save($s, false);

    return $this->id;
  }

  /**
   * Determine if the specified CO Person is a Registrar for the SLIP Plugin Configuration.
   * 
   * @since  COmanage Registry v4.7.0
   * @param  int    SLIP Plugin ID
   * @param  int    CO Person ID
   * @return bool   true if $coPersonId is a Registrar, false otherwise
   */

  public function isRegistrar($id, $coPersonId) {
    // We need the Registrar CO Group ID

    $registrarGroupId = $this->field('registrar_co_group_id', array('id' => $id));

    if($registrarGroupId) {
      return $this->RegistrarCoGroup
                  ->CoGroupMember
                  ->isMember($registrarGroupId, $coPersonId);
    }

    return false;
  }
}
