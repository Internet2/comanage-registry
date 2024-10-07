<?php
/**
 * COmanage Registry Org Sync Job Model
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
 * @since         COmanage Registry v4.4.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::uses("CoJobBackend", "Model");

class OrgSyncJob extends CoJobBackend {
  /**
   * Execute the requested Job.
   *
   * @since  COmanage Registry v4.4.0
   * @param  int   $coId    CO ID
   * @param  CoJob $CoJob   CO Job Object, id available at $CoJob->id
   * @param  array $params  Array of parameters, as requested via parameterFormat()
   * @throws InvalidArgumentException
   * @throws RuntimeException
   */
  
  public function execute($coId, $CoJob, $params) {
    // This is based on SyncJob, and could plausibly be merged with it with some minor refactoring.

    // $CoSetting = ClassRegistry::init('CoSetting');
    $OrganizationSource = ClassRegistry::init('OrganizationSource');
    
    // First see if syncing is enabled
    /* Should we just honor the OIS setting? - In PE make that a global "enable sync" setting
    if(!$CoSetting->oisSyncEnabled($coId)) {
      $CoJob->finish($CoJob->id, _txt('sh.job.sync.ois.disabled'), JobStatusEnum::Failed);
    }*/
    
    try {
      if(!empty($params['os_id'])) {
        // Sync the specified source
        
        if(!empty($params['source_key'])) {
          // We can directly call syncOrganization for both creates and updates.

          try {
            // This will verify os_id exists and add the hasOne relation to the plugin.
            // We only need to do that here, since the batch sync calls will do this.
            $OrganizationSource->bindPluginBackendModel($params['os_id']);
            
            $result = $OrganizationSource->syncOrganization(
              $params['os_id'],
              $params['source_key'],
              null,
              $CoJob->id,
              true
            );

            // syncOrganization will insert a Job Record
          }
          catch(Exception $e) {
            $CoJob->CoJobHistoryRecord->record($CoJob->id,
                                               $params['source_key'],
                                               $e->getMessage(),
                                               null,
                                               null,
                                               JobStatusEnum::Failed);
            
            throw new RuntimeException($e->getMessage());
          }
        } else {
          // Sync all records
          $OrganizationSource->syncOrganizationSource(
            $params['os_id'],
            $CoJob->id,
            (isset($params['force']) && $params['force'])
          );
        }
      } else {
        // Sync all sources
        throw new RuntimeException("sync all sources not implemented");
        // XXX need to bindPluginBackendModel for each OS
        //     need to check that sync mode is not Manual
        // $OrgIdentitySource->syncAll($CoJob->id, $coId, (isset($params['force']) && $params['force']));
        // $CoJob->finish($CoJob->id, _txt('pl.syncjob.done'));
      }    
    }
    catch(Exception $e) {
      $CoJob->finish($CoJob->id, $e->getMessage(), JobStatusEnum::Failed);
    }
  }
  
  /**
   * Obtain the list of parameters supported by this Job.
   *
   * @since  COmanage Registry v4.4.0
   * @return Array Array of supported parameters.
   */
  
  public function parameterFormat() {
    $params = array(
      'force' => array(
        'help'     => _txt('pl.orgsyncjob.arg.force'),
        'type'     => 'bool',
        'required' => false
      ),
      'os_id' => array(
        'help'     => _txt('pl.orgsyncjob.arg.os_id'),
        'type'     => 'int',
        'required' => false
      ),
      'source_key' => array(
        'help'     => _txt('pl.orgsyncjob.arg.source_key'),
        'type'     => 'string',
        'required' => false
      )
    );
    
    return $params;
  }
}
