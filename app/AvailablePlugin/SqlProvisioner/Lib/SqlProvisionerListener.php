<?php
/**
 * COmanage Registry SQL Provisioner Plugin Listener
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

App::uses('CakeEventListener', 'Event');

class SqlProvisionerListener implements CakeEventListener {
  /**
   * Define our listener(s)
   *
   * @since  COmanage Registry v3.3.0
   * @return Array Array of events and associated function names
   */

  public function implementedEvents() {
    return array(
      // This listener picks up changes to the reference data models (which
      // normally do not trigger provisioning) in order to update the SP copies
      'Model.afterDelete' => 'syncReferenceData',
      'Model.afterSave'   => 'syncReferenceData'
    );
  }
  
  /**
   * Sync reference data for supported models.
   *
   * @since  COmanage Registry v3.3.0
   * @param  CakeEvent $event Cake Event
   */
  
  public function syncReferenceData(CakeEvent $event) {
    $subject = $event->subject();
    
    // Obtain a list of the models holding reference data to sync
    $CoSqlProvisionerTarget = ClassRegistry::init('SqlProvisioner.CoSqlProvisionerTarget');
    
    if(!empty($CoSqlProvisionerTarget->referenceModels)) {
      $referenceModels = Hash::extract($CoSqlProvisionerTarget->referenceModels, '{n}.source');
      
      // We only care about the specified models
      if(!empty($subject->name) && in_array($subject->name, $referenceModels)) {
        $coId = null;
        
        if(!empty($subject->data[ $subject->name ]['co_id'])) {
          // We accept the CO ID directly from the updated record
          $coId = $subject->data[ $subject->name ]['co_id'];
        } else {
          // Look up the CO ID
          $Model = ClassRegistry::init($subject->name);
          try {
            if(!empty($subject->id)) {
              $coId = $Model->findCoForRecord($subject->id);
            } elseif(!empty($subject->data[ $subject->name ]['id'])) {
              $coId = $Model->findCoForRecord($subject->data[ $subject->name ]['id']);
            }
          } catch (Exception $e) {
            // XXX After the CO hard delete action the findCoForRecord will always
            //     fail.
            return;
          }
        }
        
        if($coId) {
          $CoSqlProvisionerTarget->syncAllReferenceData($coId);
        }
      }
    }
  }
}