<?php
/**
 * COmanage Registry Dictionary Vetter Model
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
 * @link          https://www.internet2.edu/comanage COmanage Project
 * @package       registry-plugin
 * @since         COmanage Registry v4.1.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

class DictionaryVetter extends AppModel {
  // Required by COmanage Plugins
  public $cmPluginType = "vetter";
  
  // Document foreign keys
  public $cmPluginHasMany = array(
    "Dictionary" => "DictionaryVetter"
  );
    
  // Add behaviors
  public $actsAs = array('Containable',
                         'Changelog' => array('priority' => 5));
  
  // Association rules from this model to other models
  public $belongsTo = array(
    "VettingStep",
    "Dictionary"
  );
  
  // Validation rules for table elements
  public $validate = array(
    'vetting_step_id' => array(
      'rule' => 'numeric',
      'required' => true
    ),
    'dictionary_id' => array(
      'content' => array(
        'rule' => 'numeric',
        'required' => true,
        'allowEmpty' => false,
        'unfreeze' => 'CO'
      )
    ),
    'check_identity_documents' => array(
      'rule' => 'boolean'
    ),
    'check_organizations' => array(
      'rule' => 'boolean'
    )
  );
  
  /**
   * Expose menu items.
   * 
   * @since COmanage Registry v4.1.0
   * @return Array with menu location type as key and array of labels, controllers, actions as values.
   */
  
  public function cmPluginMenus() {
    return array();
  }
  
  /**
   * Perform vetting for the requested CO Person ID.
   *
   * @since  COmanage Registry v4.1.0
   * @param  int   $vettingStepId Vetting Step ID
   * @param  int   $coPersonId    CO Person ID
   * @param  int   $coPetitionId  CO Petition ID
   * @return array                Array with three keys: "result" (VettingStatusEnum), "comment" (string), "raw" (string)
   */
  
  public function vet($vettingStepId, $coPersonId, $coPetitionId=null) {
    try {
      // Pull our configuration
      $args = array();
      $args['conditions']['DictionaryVetter.vetting_step_id'] = $vettingStepId;
      $args['contain'] = array('Dictionary');
      
      $cfg = $this->find('first', $args);
      
      if(empty($cfg['Dictionary']['id'])) {
        throw new InvalidArgumentException(_txt('er.dictionaryvetter.none'));
      }
      
      // Pull the CO Person information
      $args = array();
      $args['conditions']['CoPerson.id'] = $coPersonId;
      $args['contain'] = array('CoPersonRole', 'IdentityDocument');
      
      $coPerson = $this->Dictionary->Co->CoPerson->find('first', $args);

      if(empty($coPerson['CoPerson']['id'])) {
        throw new InvalidArgumentException(_txt('er.notfound', array(_txt('ct.co_people.1'), $coPersonId)));
      }
      
      // From here, we return Passed unless a test fails
      
      if($cfg['DictionaryVetter']['check_identity_documents']
         && !empty($coPerson['IdentityDocument'])) {
          // Check each issuing_authority we find
        foreach($coPerson['IdentityDocument'] as $doc) {
          if(!empty($doc['issuing_authority'])) {
            if($this->Dictionary->isValidEntry($cfg['DictionaryVetter']['dictionary_id'], $doc['issuing_authority'])) {
              throw new RuntimeException(_txt('pl.dictionaryvetter.failed.identity_document', array($doc['id'], $doc['issuing_authority'])));
            }
          }
        }
      }
      
      if($cfg['DictionaryVetter']['check_organizations']) {
        if(!empty($coPerson['CoPersonRole'])) {
          foreach($coPerson['CoPersonRole'] as $copr) {
            if(!empty($copr['o']) && (int)$copr['o'] > 0) {
              $args = array();
              $args['conditions']['Organization.id'] = $coPerson['CoPersonRole'][0]['o'];
              $args['contain'] = array('Address');
              
              $org = $this->VettingStep->Co->Organization->find('first', $args);
              
              if(!empty($org['Address'][0]['country'])) {
                if($this->Dictionary->isValidEntry($cfg['DictionaryVetter']['dictionary_id'], $org['Address'][0]['country'])) {
                  throw new RuntimeException(_txt('pl.dictionaryvetter.failed.organization', array($org['Organization']['name'], $copr['id'], $org['Address'][0]['country'])));
                }
              }
            }
          }
        }
      }
      
      return array(
        'comment' => 'Test complete',
        'result'  => VettingStatusEnum::Passed,
        'raw'     => json_encode(array('result' => _txt('pl.dictionaryvetter.passed')))
      );
    }
    catch(InvalidArgumentException $e) {
      // Configuration error or similar
      
      return array(
        'comment' => $e->getMessage(),
        'result'  => VettingStatusEnum::Error,
        'raw'     => json_encode(array('error' => $e->getMessage()))
      );
    }
    catch(RuntimeException $e) {
      // Failed vetting
      
      return array(
        'comment' => $e->getMessage(),
        'result'  => VettingStatusEnum::Failed,
        'raw'     => json_encode(array('result' => $e->getMessage()))
      );
    }
  }
}