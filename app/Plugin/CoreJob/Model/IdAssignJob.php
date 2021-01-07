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
 * @since         COmanage Registry v4.0.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::uses("CoJobBackend", "Model");
App::uses("PaginatedSqlIterator", "Lib");

class IdAssignJob extends CoJobBackend {
  /**
   * Perform identifier assignment.
   *
   * @since  COmanage Registry v3.3.0
   * @param  CoJob   $CoJob      CoJob object
   * @param  String  $objType    Object Type ("CoDepartment", "CoGroup", "CoPerson")
   * @param  Integer $objId      Object ID
   */
  
  protected function assign($CoJob, $objType, $objId) {
    $Identifier = ClassRegistry::init('Identifier');
    
    $res = $Identifier->assign($objType, $objId, null);
    
    $coPersonId = ($objType == 'CoPerson' ? $objId : null);
    
    foreach($res as $t => $r) {
      $rkey = $objType . "/" . $objId . ":" . $t;
      
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
    $total = 0;
    
    $models = array('CoDepartment', 'CoGroup', 'CoPerson');
    $Iterators = array();
    
    if(empty($params['object_id'])) {
      // In order to get a useful total count for setPercentComplete, we need to
      // loop through each model twice, here to do the total count and then below
      // to do the actual work. We'll cache the iterators.
      
      foreach($models as $m) {
        if(!empty($params['object_type']) && $m != $params['object_type']) {
          continue;
        }
        
        $conditions = array(
          $m.'.co_id' => $coId
        );
        
        if($m != 'CoDepartment') {
          $conditions[$m.'.status'] = StatusEnum::Active;
        }
        
        if($m == 'CoGroup') {
          // Automatic groups do not currently support identifiers (CO-1829)
          $conditions['CoGroup.auto'] = false;
        }
        
        $Iterators[$m] = new PaginatedSqlIterator(
          ClassRegistry::init($m),
          $conditions
        );
        
        $total += $Iterators[$m]->count();
      }
    }
    
    foreach($models as $m) {
      if(!empty($params['object_type']) && $m != $params['object_type']) {
        continue;
      }
      
      if(!empty($params['object_id'])) {
        $this->assign($CoJob, $m, $params['object_id']);
        
        $count++;
      } else {
        // We already have the iterator from before, so now we can query it
        
        $CoJob->CoJobHistoryRecord->record($CoJob->id,
                                           null,
                                           _txt('pl.idassignerjob.count', array($Iterators[$m]->count(), $m)),
                                           null,
                                           null,
                                           JobStatusEnum::Notice);
        
        foreach($Iterators[$m] as $k => $v) {
          if($CoJob->canceled($CoJob->id)) { return false; }
          
          $this->assign($CoJob, $m, $v[$m]['id']);
          
          $count++;
          
          if($count % 10 == 0) {
            $CoJob->setPercentComplete($CoJob->id, ($count * 100)/$total); 
          }
        }
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
      'object_type' => array(
        'help'     => _txt('pl.idassignerjob.arg.object_type'),
        'type'     => 'select',
        'choices'  => array('CoDepartment', 'CoGroup', 'CoPerson'),
        'required' => false
      ),
      'object_id' => array(
        'help'     => _txt('pl.idassignerjob.arg.object_id'),
        'type'     => 'int',
        'required' => false
      )
    );
    
    return $params;
  }
}
