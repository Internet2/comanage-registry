<?php
/**
 * COmanage Registry ID Assigner Job Model
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
 * @since         COmanage Registry v3.3.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::uses("CoJobBackend", "Model");
App::uses("PaginatedSqlIterator", "Lib");

class IdAssignerJob extends CoJobBackend {
  // Required by COmanage Plugins
  public $cmPluginType = "job";
  
  // Document foreign keys
  public $cmPluginHasMany = array();
  
  // Association rules from this model to other models
//  public $belongsTo = array("OrgIdentitySource");
  
  // Default display field for cake generated views
//  public $displayField = "env_name_given";
  
  // Validation rules for table elements
  public $validate = array();
  
  /**
   * Expose menu items.
   * 
   * @since COmanage Registry v3.3.0
   * @return Array with menu location type as key and array of labels, controllers, actions as values.
   */
  
  public function cmPluginMenus() {
    return array();
  }
  
  /**
   * Perform identifier assignment.
   *
   * @since  COmanage Registry v3.3.0
   * @param  CoJob   $CoJob      CoJob object
   * @param  integer $coId       CO ID
   * @param  integer $coPersonId CO Person ID
   */
  
  protected function assign($CoJob, $coId, $coPersonId) {
    $Identifier = ClassRegistry::init('Identifier');
    
    $res = $Identifier->assign($coId, $coPersonId, null);
    
    foreach($res as $t => $r) {
      $rkey = $coPersonId . ":" . $t;
      
      if($r === 1) {
        $CoJob->CoJobHistoryRecord->record($CoJob->id,
                                           $rkey,
                                           _txt('rs.ia.ok'),
                                           $coPersonId);
      } elseif($r === 2) {
        $CoJob->CoJobHistoryRecord->record($CoJob->id,
                                           $rkey,
                                           _txt('er.ia.already'),
                                           $coPersonId,
                                           null,
                                           JobStatusEnum::Notice);
      } else {
        $CoJob->CoJobHistoryRecord->record($CoJob->id,
                                           $rkey,
                                           $r,
                                           $coPersonId,
                                           null,
                                           JobStatusEnum::Failed);
      }
    }
  }
  
  /**
   * Execute the requested Job.
   *
   * @since  COmanage Registry v3.3.0
   * @param  int   $coId    CO ID
   * @param  CoJob $CoJob   CO Job Object, id available at $CoJob->id
   * @param  array $params  Array of parameters, as requested via parameterFormat()
   * @throws InvalidArgumentException
   * @throws RuntimeException
   */
  
  public function execute($coId, $CoJob, $params) {
    $CoJob->update($CoJob->id,
                   null,
                   null,
                   _txt('pl.idassignerjob.start'));
    
    $count = 0;
    
    // Iterate over the list of CO People. We stick with the current pattern of
    // only assigning identifiers for active people (as with enrollment).
    
    $CoPerson = ClassRegistry::init('CoPerson');
    
    if(!empty($params['co_person_id'])) {
      $this->assign($CoJob, $coId, $params['co_person_id']);
      
      $count++;
    } else {
      $iterator = new PaginatedSqlIterator(
        $CoPerson,
        array(
          'CoPerson.co_id' => $coId,
          'CoPerson.status' => StatusEnum::Active
        ),
        array('CoPerson.id', 'CoPerson.status')
      );
      
      $total = $iterator->count();
      
      foreach($iterator as $k => $v) {
        if($CoJob->canceled($CoJob->id)) { return false; }
        
        $this->assign($CoJob, $coId, $v['CoPerson']['id']);
        
        $count++;
        
        $CoJob->setPercentComplete($CoJob->id, ($count * 100)/$total); 
      }
    }
    
    $CoJob->finish($CoJob->id, _txt('pl.idassignerjob.finish', array($count)));
  }
  
  /**
   * Obtain the list of parameters supported by this Job.
   *
   * @since  COmanage Registry v3.3.0
   * @return Array Array of supported parameters.
   */
  
  public function parameterFormat() {
    $params = array(
      'co_person_id' => array(
        'help'     => _txt('pl.idassignerjob.arg.co_person_id'),
        'type'     => 'int',
        'required' => false
      )
    );
    
    return $params;
  }
}
