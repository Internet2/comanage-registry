<?php
/**
 * COmanage Registry Provisioner Job Model
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

class ProvisionerJob extends CoJobBackend {
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
                   $params['co_provisioning_target_id'],
                   $params['record_type'],
                   _txt('pl.provisionerjob.start', array($params['co_provisioning_target_id'], $params['record_type'])));
    
    // We're replicating some logic from CoProvisioningTargetsController::provision...
    
    // What models are we working with?
    $modelsTodo = array($params['record_type']);
    
    if($params['record_type'] == 'All') {
      $modelsTodo = array('CoEmailList', 'CoGroup', 'CoPerson', 'CoService');
    }
    
    // Track number of results
    $success = 0;
    $failed = 0;
    $modelCount = 0; // How many models we've worked with so far
    
    foreach($modelsTodo as $sModel) {
      // We need to manually assemble the model dependencies that ProvisionerBehavior
      // expects, since in Shell they aren't loaded automatically for some reason.
      $Model = ClassRegistry::init($sModel);
      $Model->Co = ClassRegistry::init('Co');
      $Model->Co->CoProvisioningTarget = ClassRegistry::init('CoProvisioningTarget');
      
      // Attach ProvisionerBehavior
      $Model->Behaviors->load('Provisioner');
      
      // Pull the list of object IDs to reprovision.
      $recordIds = array();
      
      // What provisioning action are we requesting?
      $sAction = null;
      
      switch($sModel) {
        case 'CoEmailList':
          $sAction = ProvisioningActionEnum::CoEmailListReprovisionRequested;
          break;
        case 'CoGroup':
          $sAction = ProvisioningActionEnum::CoGroupReprovisionRequested;
          break;
        case 'CoPerson':
          $sAction = ProvisioningActionEnum::CoPersonReprovisionRequested;
          break;
        case 'CoService':
          $sAction = ProvisioningActionEnum::CoServiceReprovisionRequested;
          break;
        default:
          throw new LogicException('NOT IMPLEMENTED');
          break;
      }
      
      if(!empty($params['record_id'])) {
        if($this->provision($CoJob, 
                            $Model,
                            $sAction,
                            $params['co_provisioning_target_id'],
                            $params['record_id'])) {
          $success++;
        } else {
          $failed++;
        }

        $recordIds[] = $params['record_id'];
      } else {
        // Pull IDs of all objects of the requested type
        
        $iterator = new PaginatedSqlIterator(
          $Model,
          array($sModel.'.co_id' => $coId),
          array($sModel.'.id', $sModel.'.status'),
          false
        );
        
        $total = $iterator->count();
        
        $CoJob->CoJobHistoryRecord->record($CoJob->id,
                                           null,
                                           _txt('pl.provisionerjob.count', array($total, $sModel)),
                                           null,
                                           null,
                                           JobStatusEnum::Notice);
        
        // For calculating totals, what percent of models have we processed?
        $modelsDone = $modelCount/count($modelsTodo);
        $modelFraction = 1/count($modelsTodo);
        
        foreach($iterator as $k => $v) {
          if($CoJob->canceled($CoJob->id)) { return false; }
          
          if($this->provision($CoJob, 
                              $Model,
                              $sAction,
                              $params['co_provisioning_target_id'],
                              $v[$sModel]['id'])) {
            $success++;
          } else {
            $failed++;
          }
          
          // If we're working with multiple models, this calculation is a bit off
          // since we don't pull all records at once, just the models that we're
          // currently working with.
          
          $pctDone = ($modelsDone * 100)
                     +
                     ($modelFraction * ((($success + $failed) * 100)/$total));
          
          $CoJob->setPercentComplete($CoJob->id, $pctDone); 
        }
      }
      
      $modelCount++;
    }
    
    $CoJob->finish($CoJob->id, _txt('pl.provisionerjob.finish', array(($success + $failed), $success, $failed)));
  }
  
  /**
   * Obtain the list of parameters supported by this Job.
   *
   * @since  COmanage Registry v3.3.0
   * @return Array Array of supported parameters.
   */
  
  public function parameterFormat() {
    $params = array(
      'co_provisioning_target_id' => array(
        'help'     => _txt('pl.provisionerjob.arg.co_provisioning_target_id'),
        'type'     => 'int',
        'required' => true
      ),
      'record_type' => array(
        'help'     => _txt('pl.provisionerjob.arg.record_type'),
        'type'     => 'select',
        'choices'  => array('CoEmailList', 'CoGroup', 'CoPerson'),
        'required' => true
      ),
      'record_id' => array(
        'help'     => _txt('pl.provisionerjob.arg.record_id'),
        'type'     => 'int',
        'required' => false
      )
    );
    
    return $params;
  }
  
  /**
   * Execute provisioning.
   *
   * @since  COmanage Registry v3.3.0
   * @param  CoJob                  $CoJob  CoJob object
   * @param  Model                  $Model  Model object
   * @param  ProvisioningActionEnum $action Provisioning action
   * @param  integer                $ptid   Provisioning Target ID
   * @param  integer                $rid    Record ID
   * @return boolean                        True if provisioning was successful, false otherwise
   */
  
  protected function provision($CoJob, $Model, $action, $ptid, $rid) {
    // Manually invoke provisioning. We don't want to abort on a single failure,
    // so catch exceptions for logging.
    
    try {
      $Model->manualProvision($ptid,
                              ($Model->name == 'CoPerson' ? $rid : null),
                              ($Model->name == 'CoGroup' ? $rid : null),
                              $action,
                              ($Model->name == 'CoEmailList' ? $rid : null),
                              null, // CoGroupMemberId
                              null, // ActorCoPersonId
                              ($Model->name == 'CoService' ? $rid : null));
                              
      $CoJob->CoJobHistoryRecord->record($CoJob->id,
                                         $rid,
                                         _txt('rs.prov.ok'),
                                         ($Model->name == 'CoPerson' ? $rid : null));
      
      return true;
    }
    catch(Exception $e) {
      $CoJob->CoJobHistoryRecord->record($CoJob->id,
                                         $rid,
                                         $e->getMessage(),
                                         ($Model->name == 'CoPerson' ? $rid : null),
                                         null,
                                         JobStatusEnum::Failed);
      
      return false;
    }
  }
}
