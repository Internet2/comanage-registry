<?php
/**
 * COmanage Registry Bulk Load Shell
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
 * @package       registry
 * @since         COmanage Registry v3.3.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

  App::import('Controller', 'AppController');
  App::import('Model', 'ConnectionManager');

  // XXX need to flag for replacement with DBAL
  // App::import doesn't handle this correctly
  require(APP . '/Vendor/adodb5/adodb.inc.php');
  
  class BulkLoadShell extends AppShell {
    var $uses = array('CoGroup',
                      'CoOrgIdentityLink',
                      'CoPerson',
                      'CoPipeline',
                      'HistoryRecord',
                      'OrgIdentity',
                      'OrgIdentitySource',
                      'OrgIdentitySourceRecord');
    
    // Tables to drop indexes for during bulk load
    // This is effectively also the list of supported models...
    var $dropIndexes = array(
      'addresses',
      'co_org_identity_links',
      'co_group_members',
      'co_people',
      'co_person_roles',
      'email_addresses',
      'history_records',
      'identifiers',
      'names',
      'org_identities',
      'org_identity_source_records',
      'telephone_numbers',
      'url'
    );
    
    function main()
    {
      // Pull all Org Identity Sources for later use
      $args = array();
      $args['conditions']['OrgIdentitySource.status'] = SuspendableStatusEnum::Active;
      $args['contain'] = 'CoPipeline';
      
      $cfgs = $this->OrgIdentitySource->find('all', $args);
      
      // Re-sort the results by OIS ID (which are unique across as CO IDs)
      
      $oisConfigs = array();
      
      foreach($cfgs as $cfg) {
        $oisConfigs[ $cfg['OrgIdentitySource']['id'] ] = $cfg;
      }
      
      // Use the ConnectionManager to get the database config to pass to adodb.
      $db = ConnectionManager::getDataSource('default');
      
      $db_driver = explode("/", $db->config['datasource'], 2);
      
      if($db_driver[0] != 'Database') {
        throw new RuntimeException("Unsupported db_method: " . $db_driver[0]);
      }

      $db_driverName = $db_driver[1];
      if(preg_match("/mysql/i", $db_driverName) && PHP_MAJOR_VERSION >= 7) {
        $db_driverName = 'mysqli';
      }

      $dbc = ADONewConnection($db_driverName);
      $dict = NewDataDictionary($dbc);
      
      if($dbc->Connect($db->config['host'],
                       $db->config['login'],
                       $db->config['password'],
                       $db->config['database'])) {
        // Note we don't load plugin schemas since we don't currently need to do anything with them
        $schemaFile = APP . '/Config/Schema/schema.xml';
        
        $schema = Xml::toArray(Xml::build(file_get_contents($schemaFile)));
        
        // Drop the indexes of the tables we'll write to to improve write performance
        $this->out(date('Y-m-d H:i:s ') . _txt('sh.bl.indexes.off'));
        
        foreach($schema['schema']['table'] as $table) {
          if(in_array($table['@name'], $this->dropIndexes)) {
            $this->out(date('Y-m-d H:i:s ') . $table['@name'] . "...");
            
            foreach($table['index'] as $index) {
              $sql = $dict->dropIndexSql(
                $db->config['prefix'] . $index['@name'], 
                $db->config['prefix'] . $table['@name']
              );
              
              $dict->executeSqlArray($sql);
            }
          }
        }
        
        foreach($this->args as $infile) {
          $line = 1;
          $this->out(date('Y-m-d H:i:s ') . _txt('sh.bl.file.in', array($infile)));
          
          // Open the input file and read the metadata
          $handle = fopen($infile, "r");
          
          if(!$handle) {
            $this->out(date('Y-m-d H:i:s ') . _txt('er.file.read', array($infile)));
            continue;
          }
          
          $metajson = fgets($handle);
          
          $meta = json_decode($metajson, true);
          
          if(!$meta) {
            $this->out(date('Y-m-d H:i:s ') . _txt('er.sh.bl.meta'));
            continue;
          }
          
          if(empty($meta['meta']['co_id'])) {
            $this->out(date('Y-m-d H:i:s ') . _txt('er.notprov.id', array('ct.cos.1')));
            continue;
          }
          
          while(($recordjson = fgets($handle)) !== false) {
            $line++;
            
            $record = json_decode($recordjson, true);
            
            if(!$record) {
              $this->out(date('Y-m-d H:i:s ') . _txt('er.sh.bl.json', array($infile, $line)));
              continue;
            }
            
            // Inject the CO ID
            $record['CoPerson']['co_id'] = $meta['meta']['co_id'];
            
            // Until v5, we need to pull the Org Identities out and save them
            // separately, while also creating a CoOrgIdentityLink
            
            $orgIdentities = array();
            $orgIdentitySourceRecords = array();
            
            if(!empty($record['OrgIdentity'])) {
              $orgIdentities = $record['OrgIdentity'];
              unset($record['OrgIdentity']);
              
              // Also inject the CO ID - pooled Org Identities are not supported
              for($i = 0;$i < count($orgIdentities);$i++) {
                $orgIdentities[$i]['OrgIdentity']['co_id'] = $meta['meta']['co_id'];
              }
            }
            
            // We take OIS Records and run Pipelines to create CO Person Roles.
            // This is easier than trying to create CO Person Roles manually and
            // link manually created OIS Records to them.
            
            if(!empty($record['OrgIdentitySourceRecord'])) {
              $orgIdentitySourceRecords = $record['OrgIdentitySourceRecord'];
              unset($record['OrgIdentitySourceRecord']);
              
              // Also inject the CO ID
              for($i = 0;$i < count($orgIdentitySourceRecords);$i++) {
                // This double nesting is correct
                $orgIdentitySourceRecords[$i]['OrgIdentity']['OrgIdentity']['co_id'] = $meta['meta']['co_id'];
              }
            }
            
            try {
              $db->begin();
              
              $this->CoPerson->clear();
              $this->CoPerson->saveAssociated($record, 
                                              array(
                                                "validate" => false,
                                                "atomic" => true,
                                                "deep" => true,
                                                "provision" => false,
                                                // safeties = off disables a bunch of before/afterSave logic
                                                "safeties" => "off"
                                              ));
              
              $coPersonId = $this->CoPerson->id;
              $this->out(date('Y-m-d H:i:s ') . _txt('sh.bl.record.id', array($line, 'co_person_id', $coPersonId)));
              
              // Add a History Record
              $this->HistoryRecord->record($coPersonId,
                                           null,
                                           null,
                                           null,
                                           ActionEnum::CoPersonAddedBulk);
              
              foreach($orgIdentities as $orgrec) {
                // Inject a CoOrgIdentityLink and then save
                $orgrec['CoOrgIdentityLink'] = array(
                  array('co_person_id' => $coPersonId)
                );
                
                // Save
                $this->OrgIdentity->clear();
                $this->OrgIdentity->saveAssociated($orgrec, 
                                                   array(
                                                     "validate" => false,
                                                     "atomic" => true,
                                                     "deep" => true,
                                                     "provision" => false,
                                                     // safeties = off disables a bunch of before/afterSave logic
                                                     "safeties" => "off"
                                                   ));
                
                // Add a History Record
                $this->HistoryRecord->record($coPersonId,
                                             null,
                                             $this->OrgIdentity->id,
                                             null,
                                             ActionEnum::OrgIdAddedBulk);
                
                $this->out(date('Y-m-d H:i:s ') . _txt('sh.bl.record.id', array($line, 'org_identity_id', $this->OrgIdentity->id)));
              }
              
              foreach($orgIdentitySourceRecords as $oisrec) {
                // Because we can't get the IDs from multiple associated records
                // (ie: more than one Identifier), and syncOrgIdentityToCoPerson
                // requires those IDs to create source_foo_id links, we need to
                // manually save most associated models.
                
                $rec = $oisrec;
                // We want just the OrgIdentity, not the associated models
                $rec['OrgIdentity'] = $oisrec['OrgIdentity']['OrgIdentity'];
                
                
                $this->OrgIdentitySourceRecord->clear();
                // Note we expect an OrgIdentity to have been passed in
                $this->OrgIdentitySourceRecord->saveAssociated($oisrec,
                                                               array(
                                                                 "validate" => false,
                                                                 "atomic" => true,
                                                                 "deep" => true,
                                                                 "provision" => false,
                                                                 "safeties" => "off"
                                                               ));
                
                $oisRecordId = $this->OrgIdentitySourceRecord->id;
                $orgId = $this->OrgIdentitySourceRecord->OrgIdentity->id;
                
                $this->out(date('Y-m-d H:i:s ') . _txt('sh.bl.record.id', array($line, 'org_identity_source_id', $oisRecordId)));
                
                // Inject the newly assigned Org ID
                $oisrec['OrgIdentity']['OrgIdentity']['id'] = $orgId;
                
                // Manually create a CoOrgIdentityLink
                $rec = array(
                  'co_person_id' => $coPersonId,
                  'org_identity_id' => $orgId
                );
                
                $this->CoOrgIdentityLink->clear();
                $this->CoOrgIdentityLink->save($rec,
                                               array(
                                                 "validate" => false,
                                                 "provision" => false,
                                                 "safeties" => "off"
                                               ));
                
                // Walk through the associated models and save each one individually.
                // We need to do this in order to get the IDs that are assigned.
                
                foreach(array_keys($oisrec['OrgIdentity']) as $m) {
                  if($m == 'OrgIdentity') {
                    // Already processed
                    continue;
                  }
                  
                  for($i = 0;$i < count($oisrec['OrgIdentity'][$m]);$i++) {
                    $rec = $oisrec['OrgIdentity'][$m][$i];
                    
                    // Inject the org_identity_id
                    $rec['org_identity_id'] = $orgId;
                    
                    $this->OrgIdentity->$m->clear();
                    $this->OrgIdentity->$m->save($rec,
                                                 array(
                                                   "validate" => false,
                                                   "atomic" => true,
                                                   "deep" => true,
                                                   "provision" => false,
                                                   "safeties" => "off"
                                                 ));
                    
                    $rec['id'] = $this->OrgIdentity->$m->id;
                    $oisrec['OrgIdentity'][$m][$i] = $rec;
                  }
                }
                
                // We could check that org_identity_source_id is a valid ID...
                $this->CoPipeline->syncOrgIdentityToCoPerson($oisConfigs[ $oisrec['org_identity_source_id'] ], 
                                                             $oisrec['OrgIdentity'], 
                                                             $coPersonId, 
                                                             null,
                                                             false,
                                                             $oisrec['source_record'],
                                                             false);
              }
              
              $db->commit();
            }
            catch(Exception $e) {
              $this->rollback();
              $this->out(date('Y-m-d H:i:s ') . _txt('er.sh.bl.error', array($line, $e->getMessage())));
              continue;
            }
          }

          fclose($handle);
        }
        
        // Reset the table indexes, *except* co_group_members, since we'll
        // recalculate group memberships after this (so reconcile() can pull
        // co_people with indexes)
        
        $this->out(date('Y-m-d H:i:s ') . _txt('sh.bl.indexes.on'));
        
        // This is mostly copy/paste from above
        foreach($schema['schema']['table'] as $table) {
          if($table['@name'] == 'co_group_members') {
            continue;
          }
          
          if(in_array($table['@name'], $this->dropIndexes)) {
            $this->out(date('Y-m-d H:i:s ') . $table['@name'] . "...");
            
            foreach($table['index'] as $index) {
              // Note we don't handle UNIQUE constraints... are there any we need to worry about?
              $sql = $dict->createIndexSql(
                $db->config['prefix'] . $index['@name'], 
                $db->config['prefix'] . $table['@name'],
                $index['col']
              );
              
              $dict->executeSqlArray($sql);
            }
          }
        }

        $this->out(date('Y-m-d H:i:s ') . _txt('sh.bl.groups.auto'));
        
        $args = array();
        $args['conditions']['CoGroup.co_id'] = $meta['meta']['co_id'];
        $args['conditions']['CoGroup.auto'] = true;
        $args['fields'] = array('id', 'name');
        $args['contain'] = false;
        
        $autoGroups = $this->CoGroup->find('list', $args);
        
        foreach($autoGroups as $gid => $gname) {
          $this->out(date('Y-m-d H:i:s ') . $gname . "...");
          $this->CoGroup->reconcile($gid, "off");
        }
        
        $this->out(date('Y-m-d H:i:s ') . _txt('sh.bl.indexes.on'));
        
        // This is mostly copy/paste from above
        foreach($schema['schema']['table'] as $table) {
          if($table['@name'] == 'co_group_members') {
            $this->out(date('Y-m-d H:i:s ') . $table['@name'] . "...");
            
            foreach($table['index'] as $index) {
              // Note we don't handle UNIQUE constraints... are there any we need to worry about?
              $sql = $dict->createIndexSql(
                $db->config['prefix'] . $index['@name'], 
                $db->config['prefix'] . $table['@name'],
                $index['col']
              );
              
              $dict->executeSqlArray($sql);
            }
          }
        }
        
        $this->out(date('Y-m-d H:i:s ') . _txt('op.done'));
        
        $dbc->Disconnect();
      }
    }
  }
