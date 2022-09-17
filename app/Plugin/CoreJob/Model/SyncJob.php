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
        
        if(!empty($params['source_key'])) {
          $targetCoPersonId = null;
          $orgIdentityId = null;
          $coPersonId = null;
          
          if(!empty($params['reference_id'])) {
            // See if there is already a CO Person ID with this Reference ID,
            // in which case we want to link to that identity.
            // This is similar to CoPipeline::findTargetCoPersonId().
            
            $args = array();
            $args['conditions']['Identifier.identifier'] = $params['reference_id'];
            $args['conditions']['Identifier.type'] = IdentifierEnum::Reference;
            $args['conditions']['CoPerson.co_id'] = $ois['OrgIdentitySource']['co_id'];
            $args['joins'][0]['table'] = 'co_people';
            $args['joins'][0]['alias'] = 'CoPerson';
            $args['joins'][0]['type'] = 'INNER';
            $args['joins'][0]['conditions'][0] = 'CoPerson.id=Identifier.co_person_id';
            // Make this a distinct select so we don't get tripped on (eg) the same identifier
            // address being listed twice for the same CO Person (eg from multiple OIS records)
            $args['fields'] = array('DISTINCT Identifier.co_person_id');
            $args['contain'] = false;

            $matchingRecords = $OrgIdentitySource->Co->CoPerson->Identifier->find('all', $args);

            if(count($matchingRecords) == 1) {
              $targetCoPersonId = $matchingRecords[0]['Identifier']['co_person_id'];
            } elseif(count($matchingRecords) > 1) {
              // Multiple matching records shouldn't happen, throw an error
              throw new InvalidArgumentException(_txt('er.syncjob.match', array($params['reference_id'])));
            }
            // else No Match
          }
          
          // We'll try createOrgIdentity first. If we get an OverflowException,
          // there is already a record so we'll try syncOrgIdentity instead.
          
          try {
            $orgIdentityId = $OrgIdentitySource->createOrgIdentity($params['ois_id'],
                                                                   $params['source_key'],
                                                                   null,
                                                                   $ois['OrgIdentitySource']['co_id'],
                                                                   $targetCoPersonId);
          }
          catch(OverflowException $e) {
            $result = $OrgIdentitySource->syncOrgIdentity($params['ois_id'],
                                                          $params['source_key'],
                                                          null,
                                                          $CoJob->id,
                                                          true);
            
            $orgIdentityId = $result['id'];
          }
          catch(Exception $e) {
            $OrgIdentitySource->Co->CoJob->CoJobHistoryRecord->record($CoJob->id,
                                                                      $params['source_key'],
                                                                      $e->getMessage(),
                                                                      null,
                                                                      null,
                                                                      JobStatusEnum::Failed);
            
            throw new RuntimeException($e->getMessage());
          }
          
          $coPersonId = $OrgIdentitySource->Co
                                          ->CoPerson
                                          ->CoOrgIdentityLink
                                          ->field('co_person_id', array('CoOrgIdentityLink.org_identity_id' => $orgIdentityId));
          
          // Insert a Job History Record, note that syncOrgIdentity() will update Job History
          $OrgIdentitySource->Co->CoJob->CoJobHistoryRecord->record($CoJob->id,
                                                                    $params['source_key'],
                                                                    _txt('rs.org.src.synced'),
                                                                    $coPersonId,
                                                                    $orgIdentityId);
          
          if(!empty($params['reference_id'])) {
            // Record History that there was a Manual Resolution Notification
            
            $OrgIdentitySource->Co->CoPerson->HistoryRecord->record($coPersonId,
                                                                    null,
                                                                    $orgIdentityId,
                                                                    null,
                                                                    ActionEnum::CoPersonMatchedPipeline,
                                                                    _txt('pl.syncjob.match.resolved'));
          }
        } else {
          // Sync all records
          $OrgIdentitySource->syncOrgIdentitySource($ois, (isset($params['force']) && $params['force']), $CoJob->id);
        }
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
      ),
      'reference_id' => array(
        'help'     => _txt('pl.syncjob.arg.reference_id'),
        'type'     => 'string',
        'required' => false
      ),
      'source_key' => array(
        'help'     => _txt('pl.syncjob.arg.source_key'),
        'type'     => 'string',
        'required' => false
      )
    );
    
    return $params;
  }
}
