<?php
/**
 * COmanage Registry Elector DataFilter Model
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
 * @since         COmanage Registry v4.1.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

class ElectorDataFilter extends AppModel
{
  // Required by COmanage Plugins
  public $cmPluginType = "datafilter";

  // Document foreign keys
  public $cmPluginHasMany = array();

  // Add behaviors
  public $actsAs = array('Changelog' => array('priority' => 5),
                         'Containable');

  // Association rules from this model to other models
  public $belongsTo = array("DataFilter");

  public $hasMany = array(
    "ElectorDataFilter.ElectorDataFilterPrecedence" => array('dependent' => true)
  );

  // The context(s) this filter supports
  public $supportedContexts = array(
    DataFilterContextEnum::ProvisioningTarget
  );

  // Default display field for cake generated views
  public $displayField = "attribute_name";

  // Validation rules for table elements
  public $validate = array(
    'data_filter_id' => array(
      'rule' => 'numeric',
      'required' => true
    ),
    'attribute_name' => array(
      'content' => array(
        'rule' => array('inList', array("Name",
                                        "Url",
                                        "Address",
                                        "EmailAddress",
                                        "Identifier",
                                        "TelephoneNumber")),
        'required' => true,
        'allowEmpty' => false,
        'message' => 'A valid status must be selected'
      )
    ),
    'outbound_attribute_type' => array(
      'rule' => 'notBlank',
      'required' => true,
      'allowEmpty' => false
    ),
    'tie_break_mode' => array(
      'content' => array(
        'rule' => array('inList', array(TieBreakReplacementModeEnum::Oldest,
                                        TieBreakReplacementModeEnum::Newest)),
        'required' => true,
        'allowEmpty' => false,
        'message' => 'A valid status must be selected'
      )
    ),
    'replacement_mode' => array(
      'content' => array(
        'rule' => array('inList', array(ReplacementModeEnum::Replace,
                                        ReplacementModeEnum::Insert)),
        'required' => true,
        'allowEmpty' => false,
        'message' => 'A valid status must be selected'
      )
    ),
  );

  /**
   * Expose menu items.
   *
   * @ since COmanage Registry v4.1.0
   * @ return Array with menu location type as key and array of labels, controllers, actions as values.
   */

  public function cmPluginMenus() {
    return array();
  }

  /**
   * Perform the data filter operation.
   *
   * @since  COmanage Registry v4.1.0
   * @param  DataFilterContextEnum $context
   * @param  Integer               $dataFilterId Data Filter ID
   * @param  Array                 $data         Array of (eg: provisioning) data
   * @return Array                               Array of data, in the same format as passed
   * @throws InvalidArgumentException
   */

  public function filter($context, $dataFilterId, $provisioningData) {
    if($context !== DataFilterContextEnum::ProvisioningTarget) {
      throw new RuntimeException('NOT IMPLEMENTED');
    }

    // Pull our configuration
    $args = array();
    $args['conditions']['ElectorDataFilter.data_filter_id'] = $dataFilterId;
    $args['contain'] = array(
      'ElectorDataFilterPrecedence' => array(
        'conditions' => array(
          'ElectorDataFilterPrecedence.deleted != true',
          'ElectorDataFilterPrecedence.elector_data_filter_precedence_id is null'
        )
      )
    );

    $cfg = $this->find('first', $args);

    if(empty($cfg['ElectorDataFilter']['outbound_attribute_type'])
       || empty($cfg['ElectorDataFilter']['attribute_name'])) {
      throw new InvalidArgumentException(_txt('er.elector_data_filter.cfg'));
    }

    // No precedences are configured
    if(empty($cfg['ElectorDataFilterPrecedence'])
       || empty($provisioningData[ $cfg["ElectorDataFilter"]["attribute_name"] ])) {
      return $provisioningData;
    }

    // Sort the precedences
    $edtf_precedences_sorted = Hash::sort($cfg['ElectorDataFilterPrecedence'], "{n}.ordr", 'asc', 'numeric');
    // Extract the list of inbound types
    $inbound_types = Hash::extract($edtf_precedences_sorted, '{n}.inbound_attribute_type');

    foreach ($inbound_types as $idx => $attr_type) {
      // Extract the Models with matching attribute types
      $attr = Hash::extract($provisioningData[ $cfg["ElectorDataFilter"]["attribute_name"] ],'{n}[type=' . $attr_type . ']');
      if(!empty($attr)) {
        // Construct the source foreign key
        $source_fk = "source_" . Inflector::underscore($cfg['ElectorDataFilter']['attribute_name']) . "_id";
        if(!empty($attr[0][$source_fk]) && $attr[0][$source_fk] > 0) {
          // Get the OIS plugin used to pipeline this email to the CO Person
          $args = array();
          $args['conditions'][$cfg['ElectorDataFilter']['attribute_name'] . '.id'] = $attr[0]['id'];
          $args['contain'] = array('SourceEmailAddress' => array('OrgIdentity' => array('OrgIdentitySourceRecord' => array('OrgIdentitySource'))));

          $mdl = ClassRegistry::init($cfg['ElectorDataFilter']['attribute_name']);
          $ois = $mdl->find('first', $args);

          // Continue If there is not OrgIdentitySource or the id of the Source does not the one from the Precedence configuration
          if(empty($ois['SourceEmailAddress']["OrgIdentity"]["OrgIdentitySourceRecord"]["OrgIdentitySource"]["id"])
             || $ois['SourceEmailAddress']["OrgIdentity"]["OrgIdentitySourceRecord"]["OrgIdentitySource"]["id"] != $cfg['ElectorDataFilterPrecedence'][$idx]['org_identity_source_id']) {
            continue;
          }
        }

        /*
        // TIE BREAK ACTION
        */
        // Sort by date and use the first element
        if($cfg["ElectorDataFilter"]["tie_break_mode"] === TieBreakReplacementModeEnum::Newest) {
          // Newest first
          usort($attr, 'cmg_date_compare');
          $attr = array_reverse($attr);
        } elseif($cfg["ElectorDataFilter"]["tie_break_mode"] === TieBreakReplacementModeEnum::Oldest) {
          // Oldest first
          usort($attr, 'cmg_date_compare');
        }

        // Translate the type to the outbound configuration
        $attr[0]["type"] = strtolower($cfg["ElectorDataFilter"]["outbound_attribute_type"]);
        // XXX This is a record we configure on the fly. As a result we set the id to null
        //     in order to protect any other plugin that might make use of the data. At the same
        //     time we pass the id to the refid column.
        $attr[0]["data_filter_elector_refid"] = $attr[0]["id"];
        $attr[0]["id"] = null;

        /*
        // ELECTOR MODE
        */
        if($cfg["ElectorDataFilter"]["replacement_mode"] === ReplacementModeEnum::Insert) {
          // Insert a New Record
          $provisioningData[ $cfg["ElectorDataFilter"]["attribute_name"] ][] = $attr[0];
          break;
        } elseif($cfg["ElectorDataFilter"]["replacement_mode"] === ReplacementModeEnum::Replace) {
          // Replace all the records with only one
          unset($provisioningData[ $cfg["ElectorDataFilter"]["attribute_name"] ]);
          $provisioningData[ $cfg["ElectorDataFilter"]["attribute_name"] ][] = $attr[0];
          break;
        }

      } // IF
    } // Foreach

    return $provisioningData;
  }
}