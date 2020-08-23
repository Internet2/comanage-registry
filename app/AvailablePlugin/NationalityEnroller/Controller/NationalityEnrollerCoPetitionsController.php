<?php
/**
 * COmanage Registry Nationality Enroller Controller
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
 * @since         COmanage Registry v4.0.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::uses('CoPetitionsController', 'Controller');

class NationalityEnrollerCoPetitionsController extends CoPetitionsController {
  // Class name, used by Cake
  public $name = "NationalityEnrollerCoPetitions";

  public $uses = array('CoPetition',
                       'NationalityEnroller.NationalityEnroller',
                       'AttributeEnumeration',
                       'HistoryRecord',
                       'IdentityDocument');
  
  /**
   * Callback after controller methods are invoked but before views are rendered.
   * - precondition: Request Handler component has set $this->request
   * - postcondition: Set $sponsors
   *
   * @since  COmanage Registry v4.0.0
   */

  public function beforeRender() {
    parent::beforeRender();
    
    // Pull the list of enumerations for Self Assertion, if so configured
    
    $enums = array();
    
    // A bit redundant, but we need to work with the view element
    for($i = 1;$i <= $this->viewVars['vv_cfg']['NationalityEnroller']['collect_maximum'];$i++) {
      $enums['CoPetition.nationality_authority_'.$i] = $this->AttributeEnumeration->enumerations($this->cur_co['Co']['id'], "IdentityDocument.issuing_authority." . IdentityDocumentEnum::SelfAssertion);
    }
    
    $enums['CoPetition.residency_authority'] = $this->AttributeEnumeration->enumerations($this->cur_co['Co']['id'], "IdentityDocument.issuing_authority." . IdentityDocumentEnum::SelfAssertion);
    
    $this->set('vv_enums', $enums);
  }
  
  /**
   * Collect Identity Documents after initial attributes.
   *
   * @since  COmanage Registry v5.0.0
   * @param  Integer $id       CO Petition ID
   * @param  Array   $onFinish Redirect target on completion
   * @throws InvalidArgumentException
   */
  
  protected function execute_plugin_petitionerAttributes($id, $onFinish) {
    $efwid = $this->viewVars['vv_efwid'];
    
    // Pull our configuration
    $args = array();
    $args['conditions']['NationalityEnroller.co_enrollment_flow_wedge_id'] = $efwid;
    $args['contain'] = false;
    
    $cfg = $this->NationalityEnroller->find('first', $args);
    
    if(empty($cfg)) {
      throw new InvalidArgumentException(_txt('er.unknown', array($efwid)));
    }
    
    $this->set('vv_cfg', $cfg);
    
    if($this->request->is('post')) {
      // Find the CO Person for $id
      $coPersonId = $this->CoPetition->field('enrollee_co_person_id', array('CoPetition.id' => $id));
      
      if(!$coPersonId) {
        throw new InvalidArgumentException(_txt('er.notfound', array(_txt('ct.co_petitions.1'), $id)));
      }
      
      // Build an array of fields to process
      $authorities = array();
      
      for($i = 1;$i <= $cfg['NationalityEnroller']['collect_maximum'];$i++) {
        $authorities['nationality_authority_'.$i] = 'nationality';
      }
      
      if(isset($cfg['NationalityEnroller']['collect_residency'])
         && $cfg['NationalityEnroller']['collect_residency']) {
        $authorities['residency_authority'] = 'residency';
      }
      
      // Loop through nationality self-assertions
      foreach($authorities as $field => $subtype) {
        if(!empty($this->request->data['CoPetition'][$field])) {
          try {
            $document = array(
              'co_person_id'        => $coPersonId,
              'document_type'       => IdentityDocumentEnum::SelfAssertion,
              'document_subtype'    => $subtype,
              'issuing_authority'   => $this->request->data['CoPetition'][$field],
              'verification_method' => IdentityVerificationMethodEnum::None,
              'verifier_identifier' => $this->Session->read('Auth.User.username'),
              'verifier_comment'    => _txt('pl.nationalityenroller.comment', array($id))
            );
            
            $this->IdentityDocument->clear();
            
            $this->IdentityDocument->save($document);
            
            // Record history on the CO Person and Petition
            $this->HistoryRecord->record($coPersonId,
                                         null,
                                         null,
                                         $coPersonId,
                                         ActionEnum::IdentityDocumentAdded,
                                         $document['verifier_comment']);
            
            $this->CoPetition->CoPetitionHistoryRecord->record($id,
                                                               $coPersonId,
                                                               PetitionActionEnum::IdentityDocumentAdded,
                                                               $document['verifier_comment']);
          }
          catch(Exception $e) {
            throw new RuntimeException($e->getMessage());
          }
        }
      }
      
      $this->redirect($onFinish);
    }
    // else just let the form render
  }
}
