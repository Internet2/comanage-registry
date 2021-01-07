<?php
/**
 * COmanage Registry Sync Job Model
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

class SyncJob extends CoJobBackend {
  /**
   * Execute the requested Job.
   *
   * @since  COmanage Registry v4.0.0
   * @param  int   $coId    CO ID
   * @param  CoJob $CoJob   CO Job Object, id available at $CoJob->id
   * @param  array $params  Array of parameters, as requested via parameterFormat()
   * @throws InvalidArgumentException
   * @throws RuntimeException
   */
  
  public function execute($coId, $CoJob, $params) {
    $CoSetting = ClassRegistry::init('CoSetting');
    $OrgIdentitySource = ClassRegistry::init('OrgIdentitySource');
    
    // First see if syncing is enabled
    
    if(!$CoSetting->oisSyncEnabled($coId)) {
      $CoJob->finish($CoJob->id, _txt('sh.job.sync.ois.disabled'), JobStatusEnum::Failed);
    }
    
    try {
      if(!empty($params['ois_id'])) {
        // Sync the specified source
        
        $args = array();
        $args['conditions']['OrgIdentitySource.id'] = $params['ois_id'];
        $args['contain'] = false;
        
        $ois = $OrgIdentitySource->find('first', $args);
        
        if(empty($ois)) {
          throw new InvalidArgumentException(_txt('er.notfound', array(_txt('ct.org_identity_sources.1'), $params['ois_id'])));
        }
        
        $OrgIdentitySource->syncOrgIdentitySource($ois, (isset($params['force']) && $params['force']), $CoJob->id);
      } else {
        // Sync all sources
        $OrgIdentitySource->syncAll($CoJob->id, $coId, (isset($params['force']) && $params['force']));
      }
      
      $CoJob->finish($CoJob->id, _txt('pl.syncjob.done'));
    }
    catch(Exception $e) {
      $CoJob->finish($CoJob->id, $e->getMessage(), JobStatusEnum::Failed);
    }
  }
  
  /**
   * Obtain the list of parameters supported by this Job.
   *
   * @since  COmanage Registry v4.0.0
   * @return Array Array of supported parameters.
   */
  
  public function parameterFormat() {
    $params = array(
      'force' => array(
        'help'     => _txt('pl.syncjob.arg.force'),
        'type'     => 'bool',
        'required' => false
      ),
      'ois_id' => array(
        'help'     => _txt('pl.syncjob.arg.ois_id'),
        'type'     => 'int',
        'required' => false
      )
    );
    
    return $params;
  }
}
