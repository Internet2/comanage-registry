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
    var $uses = array(
      'Address',
      'AdHocAttribute',
      'CoGroup',
      'CoGroupMember',
      'CoOrgIdentityLink',
      'CoPerson',
      'CoPersonRole',
      'EmailAddress',
      'HistoryRecord',
      'Identifier',
      'Name',
      'OrgIdentity',
      'OrgIdentitySource',
      'OrgIdentitySourceRecord',
      'TelephoneNumber',
      'Url'
    );
    
    // Tables to drop indexes for during bulk load
    // This is effectively also the list of supported models...
    // Entries in this table must be sorted by foreign key dependencies
    var $dropIndexes = array(
      'org_identities',
      'co_people',
      'co_person_roles',
      'co_org_identity_links',
      'ad_hoc_attributes',
      'addresses',
      'co_groups',
      'co_group_members',
      'email_addresses',
      'history_records',
      'identifiers',
      'names',
      'org_identity_source_records',
      'telephone_numbers',
      'urls'
    );
    
    // Cross reference lookup table
    protected $xrefs = array();
    
    function main() {
      $coId = $this->args[0];
      
      if(!is_numeric($coId)) {
        throw new InvalidArgumentException("Non-numeric COID provided");
      }
      
      // Pull all Org Identity Sources for later use
      $args = array();
      $args['conditions']['OrgIdentitySource.status'] = SuspendableStatusEnum::Active;
      $args['conditions']['OrgIdentitySource.co_id']=  $coId;
      $args['contain'] = array('CoPipeline');
      
      $cfgs = $this->OrgIdentitySource->find('all', $args);
      
      // Re-sort the results by OIS ID (which are unique across as CO IDs)
      
      $oisConfigs = array();
      
      foreach($cfgs as $cfg) {
        $oisConfigs[ $cfg['OrgIdentitySource']['id'] ] = $cfg;
      }
      
      // Pull the Automatic CO Groups we may need to populate
      $args = array();
      $args['conditions']['CoGroup.co_id'] = $coId;
      $args['conditions']['CoGroup.auto'] = true;
      $args['contain'] = false;
      
      $grps = $this->CoGroup->find('all', $args);
      
      // Re-sort the results by type and COU ID
      
      $autoGroups = array();
      
      foreach($grps as $grp) {
        $cou_id = (!empty($grp['CoGroup']['cou_id']) ? $grp['CoGroup']['cou_id'] : "CO");
        
        $autoGroups[ $grp['CoGroup']['group_type'] ][ $cou_id ] = $grp['CoGroup']['id'];
      }

      // Open the input file and read the metadata
      $infile = $this->args[1];
      $handle = fopen($infile, "r");

      if(!$handle) {
        throw new InvalidArgumentException(_txt('er.file.read', array($infile)));
      }
      
      // Read the metadata block
      $metajson = fgets($handle);
      
      $meta = json_decode($metajson, true);
      
      // Additional models to look for
      $pluginModels = array();
      
      if(!empty($meta['meta']['pluginModels'])) {
        $pluginModels = $meta['meta']['pluginModels'];
        
        // Merge plugin models into the configuration
        
        foreach(array('CoPerson', 'CoGroup', 'Configuration') as $mt) {
          if(!empty($pluginModels[$mt])) {
            foreach($pluginModels[$mt] as $m) {
              // Make sure we're not overriding a core model. Note plugins risk
              // all sorts of chaos if they reimplement a core model, so we shouldn't
              // really need to test for this.
              $bits = explode(".", $m, 2);
              
              if(!in_array($bits[1], $this->uses)) {
                $tableName = Inflector::tableize($bits[1]);
                
                // Load the plugin model
                $this->loadModel($m);
                
                // Register the table name
                $this->dropIndexes[] = $tableName;
              }
            }
          }
        }
      }
      
      // Use the ConnectionManager to get the database config to pass to adodb.
      $db = ConnectionManager::getDataSource('default');
      
      // Obtain the current MAX IDs. We'll need to manage FKs ourself in order
      // to generate the bulk records to load.
      $maxIds = $this->getMaxIDs($db);
      
      // Records to load, keyed by table
      $outrecords = array();
      
      // Load records from the file
      $line = 1;
      
      $this->out(date('Y-m-d H:i:s ') . _txt('sh.bl.file.in', array($infile)));
      
      while(($recordjson = fgets($handle)) !== false) {
        $line++;
        
        $inrecord = json_decode($recordjson, true);
        
        if(!$inrecord) {
          $this->out(date('Y-m-d H:i:s ') . _txt('er.sh.bl.json', array($infile, $line)));
          continue;
        }
        
        $autoGroupTodo = array();
        
        if(!empty($inrecord['CoPerson'])) {
          // Process a CO Person record
          // Track which Automatic Group Memberships we should create, by COU ID
          $autoGroupTodo = array('CO' => array(
            'status' => $inrecord['CoPerson']['status'],
            'source_org_identity_id' => null
          ));
          
          // Start with the CO Person Record
          
          // We have to manually key the records
          $coPersonId = ++$maxIds['co_people'];
          
          // If there is a cross reference label, store the ID for lookup
          if(!empty($inrecord['meta']['xref'])) {
            $this->xrefs[ $inrecord['meta']['xref'] ] = $coPersonId;
          }
          
          // This will give us a fully populated record
          $outrecords['co_people'][] = $this->recordToRow($inrecord['CoPerson'], 
                                                          array('id' => $coPersonId,
                                                                'co_id' => $coId),
                                                          $this->getFields('CoPerson'));
          
          // Process CO Person models
          foreach(array('Name', 'Identifier', 'EmailAddress', 'CoGroupMember', 'HistoryRecord') as $m) {
            $table = Inflector::tableize($m);
            
            if(!empty($inrecord[$m])) {
              foreach($inrecord[$m] as $subrecord) {
                $id = ++$maxIds[$table];
                
                $outrecords[$table][] = $this->recordToRow($subrecord,
                                                           array('id' => $id,
                                                                 'co_person_id' => $coPersonId),
                                                           $this->getFields($m));
              }
            }
          }
          
          // Process CO Person Roles and associated models
          if(!empty($inrecord['CoPersonRole'])) {
            foreach($inrecord['CoPersonRole'] as $role) {
              $coPersonRoleId = ++$maxIds['co_person_roles'];
              
              $outrecords['co_person_roles'][] = $this->recordToRow($role,
                                                                    array('id' => $coPersonRoleId,
                                                                          'co_person_id' => $coPersonId),
                                                                    $this->getFields('CoPersonRole'));
              
              foreach(array('Address', 'AdHocAttribute', 'TelephoneNumber') as $m) {
                $table = Inflector::tableize($m);
                
                if(!empty($role[$m])) {
                  foreach($role[$m] as $subrecord) {
                    $id = ++$maxIds[$table];
                    
                    $outrecords[$table][] = $this->recordToRow($subrecord,
                                                               array('id' => $id,
                                                                     'co_person_role_id' => $coPersonRoleId),
                                                               $this->getFields($m));
                  }
                }
              }
              
              if(!empty($role['cou_id'])) {
                $autoGroupTodo[ $role['cou_id'] ] = array(
                  'status' => $role['status'],
                  'source_org_identity_id' => null
                );
              }
            }
          }
          
          // Inject a History Record
          $id = ++$maxIds['history_records'];
          
          $history = array(
            'id' => $id,
            'co_person_id' => $coPersonId,
            'action' => ActionEnum::CoPersonAddedBulk,
            'comment' => _txt('en.action', null, ActionEnum::CoPersonAddedBulk)
          );
          
          $outrecords['history_records'][] = $this->recordToRow($history, array(), $this->getFields('HistoryRecord'));
          
          // Process Org Identities and associated models
          if(!empty($inrecord['OrgIdentity'])) {
            foreach($inrecord['OrgIdentity'] as $orgIdentity) {
              if(!empty($orgIdentity['OrgIdentity'])) {
                // XXX This is copy-and-pasted below
                $orgIdentityId = ++$maxIds['org_identities'];
                
                $outrecords['org_identities'][] = $this->recordToRow($orgIdentity['OrgIdentity'],
                                                                     array('id' => $orgIdentityId,
                                                                           'co_id' => $coId),
                                                                     $this->getFields('OrgIdentity'));
                
                // We need to manually create the CoOrgIdentityLink
                $coOrgLinkId = ++$maxIds['co_org_identity_links'];
                
                $link = array(
                  'id'              => $coOrgLinkId,
                  'co_person_id'    => $coPersonId,
                  'org_identity_id' => $orgIdentityId
                );
                
                $outrecords['co_org_identity_links'][] = $this->recordToRow($link,
                                                                            array(),
                                                                            $this->getFields('CoOrgIdentityLink'));
                
                foreach(array('Address',
                              'AdHocAttribute',
                              'EmailAddress',
                              'HistoryRecord',
                              'Identifier',
                              'Name',
                              'TelephoneNumber') as $m) {
                  $table = Inflector::tableize($m);
                  
                  if(!empty($orgIdentity[$m])) {
                    foreach($orgIdentity[$m] as $subrecord) {
                      $id = ++$maxIds[$table];
                      
                      $outrecords[$table][] = $this->recordToRow($subrecord,
                                                                 array('id' => $id,
                                                                       'org_identity_id' => $orgIdentityId),
                                                                 $this->getFields($m));
                    }
                  }
                }
                
                // Inject a History Record
                $id = ++$maxIds['history_records'];
                
                $history = array(
                  'id' => $id,
                  'co_person_id' => $coPersonId,
                  'org_identity_id' => $orgIdentityId,
                  'action' => ActionEnum::OrgIdAddedBulk,
                  'comment' => _txt('en.action', null, ActionEnum::OrgIdAddedBulk)
                );
                
                $outrecords['history_records'][] = $this->recordToRow($history, array(), $this->getFields('HistoryRecord'));
              }
            }
          }
          
          if(!empty($inrecord['OrgIdentitySourceRecord'])) {
            foreach($inrecord['OrgIdentitySourceRecord'] as $oisRecord) {
              // First create the Org Identity
              // XXX this is copy and paste from above
              $orgIdentityId = ++$maxIds['org_identities'];
              
              $outrecords['org_identities'][] = $this->recordToRow($oisRecord['OrgIdentity']['OrgIdentity'],
                                                                   array('id' => $orgIdentityId,
                                                                         'co_id' => $coId),
                                                                   $this->getFields('OrgIdentity'));
              
              // We need to manually create the CoOrgIdentityLink
              $coOrgLinkId = ++$maxIds['co_org_identity_links'];
              
              $link = array(
                'id'              => $coOrgLinkId,
                'co_person_id'    => $coPersonId,
                'org_identity_id' => $orgIdentityId
              );
              
              $outrecords['co_org_identity_links'][] = $this->recordToRow($link,
                                                                          array(),
                                                                          $this->getFields('CoOrgIdentityLink'));
              
              // Create a CO Person Role, if configured
              $coPersonRoleId = null;
              
              if(!empty($oisConfigs[ $oisRecord['org_identity_source_id'] ]['CoPipeline'])) {
                $pipeline = $oisConfigs[ $oisRecord['org_identity_source_id'] ]['CoPipeline'];
                
                $coPersonRoleId = ++$maxIds['co_person_roles'];
                
                // We sort of reimplement pipeline logic here
                $role = array(
                  'id'                     => $coPersonRoleId,
                  'co_person_id'           => $coPersonId,
                  'cou_id'                 => (!empty($pipeline['sync_cou_id']) ? $pipeline['sync_cou_id'] : null),
                  'affiliation'            => (!empty($pipeline['sync_affiliation']) ? $pipeline['sync_affiliation'] : $oisRecord['OrgIdentity']['OrgIdentity']['affiliation']),
                  'status'                 => StatusEnum::Active,
                  'source_org_identity_id' => $orgIdentityId,
                );
                
                foreach(array('o', 'ou', 'title', 'valid_from', 'valid_through') as $a) {
                  if(!empty($oisRecord['OrgIdentity']['OrgIdentity'][$a])) {
                    $role[$a] = $oisRecord['OrgIdentity']['OrgIdentity'][$a];
                  }
                }
                
                $outrecords['co_person_roles'][] = $this->recordToRow($role, array(), $this->getFields('CoPersonRole'));
                
                if(!empty($role['cou_id'])) {
                  $autoGroupTodo[ $role['cou_id'] ] = array(
                    'status'                 => $role['status'],
                    // This should be NULL because these group memberships are
                    // ordinarily maintained by CoPersonRole::afterSave, not by
                    // the pipeline.
                    'source_org_identity_id' => null
                  );
                }
              }
              
              foreach(array('Address',
                            'AdHocAttribute',
                            'EmailAddress',
                            'HistoryRecord',
                            'Identifier',
                            'Name',
                            'TelephoneNumber',
                            'Url') as $m) {
                $table = Inflector::tableize($m);
                $sourcefk = 'source_' . Inflector::underscore($m) . '_id';
                
                if(!empty($oisRecord['OrgIdentity'][$m])) {
                  foreach($oisRecord['OrgIdentity'][$m] as $subrecord) {
                    $id = ++$maxIds[$table];
                    
                    $outrecords[$table][] = $this->recordToRow($subrecord,
                                                               array('id' => $id,
                                                                     'org_identity_id' => $orgIdentityId),
                                                               $this->getFields($m));
                    
                    // Also copy the record to the CO Person or Role
                    $copyid = ++$maxIds[$table];
                    
                    $fks = array(
                      'id'      => $copyid,
                      $sourcefk => $id
                    );
                    
                    if($m == 'Address' || $m == 'AdHocAttribute' || $m == 'TelephoneNumber') {
                      $fks['co_person_role_id'] = $coPersonRoleId;
                    } else {
                      $fks['co_person_id'] = $coPersonId;
                      
                      // Force primary name to false
                      if($m == 'Name') {
                        $subrecord['primary_name'] = false;
                      }
                    }
                    
                    $outrecords[$table][] = $this->recordToRow($subrecord, $fks, $this->getFields($m));
                  }
                }
              }
              
              // Inject a History Record
              $id = ++$maxIds['history_records'];
              
              $history = array(
                'id' => $id,
                'co_person_id' => $coPersonId,
                'org_identity_id' => $orgIdentityId,
                'action' => ActionEnum::OrgIdAddedBulk,
                'comment' => _txt('en.action', null, ActionEnum::OrgIdAddedBulk)
              );
              
              $outrecords['history_records'][] = $this->recordToRow($history, array(), $this->getFields('HistoryRecord'));
              
              // Now create the OIS Record
              $id = ++$maxIds['org_identity_source_records'];
              
              $outrecords['org_identity_source_records'][] = $this->recordToRow($oisRecord,
                                                                                array('id' => $id,
                                                                                      'org_identity_id' => $orgIdentityId),
                                                                                $this->getFields('OrgIdentitySourceRecord'));
              
              // Automatic group mapping is not supported because doing so requires invoking
              // the plugin backend, which will incur a performance hit the initial data load
              // should be prepared with desired group memberships configured
            }
          }
          
          // See if there are any plugin models in the record
          
          if(!empty($pluginModels['CoPerson'])) {
            foreach($pluginModels['CoPerson'] as $m) {
              $bits = explode(".", $m, 2);
              
              if(!empty($inrecord[ $bits[1] ])) {
                // Found it, walk the list
                $tableName = Inflector::tableize($bits[1]);
                
                if(isset($maxIds[$tableName])) {
                  foreach($inrecord[ $bits[1] ] as $r) {
                    $id = ++$maxIds[$tableName];
                    
                    $outrecords[$tableName][] = $this->recordToRow($r,
                                                                   array('id' => $id,
                                                                         'co_person_id' => $coPersonId),
                                                                   $this->getFields($bits[1]));
                  }
                }
              }
            }
          }
        } elseif(!empty($inrecord['CoGroup'])) {
          // Process a CO Group record

          // We have to manually key the records
          $coGroupId = ++$maxIds['co_groups'];
          
          // If there is a cross reference label, store the ID for lookup
          if(!empty($inrecord['meta']['xref'])) {
            $this->xrefs[ $inrecord['meta']['xref'] ] = $coGroupId;
          }
          
          // This will give us a fully populated record
          $outrecords['co_groups'][] = $this->recordToRow($inrecord['CoGroup'], 
                                                          array('id' => $coGroupId,
                                                                'co_id' => $coId),
                                                          $this->getFields('CoGroup'));
          // Process CO Group models
          foreach(array('Identifier', 'CoGroupMember', 'HistoryRecord') as $m) {
            $table = Inflector::tableize($m);
            
            if(!empty($inrecord[$m])) {
              foreach($inrecord[$m] as $subrecord) {
                $id = ++$maxIds[$table];
                
                $outrecords[$table][] = $this->recordToRow($subrecord,
                                                           array('id' => $id,
                                                                 'co_group_id' => $coGroupId),
                                                           $this->getFields($m));
              }
            }
          }
          
          // Inject a History Record
          $id = ++$maxIds['history_records'];
          
          $history = array(
            'id' => $id,
            'co_group_id' => $coGroupId,
            'action' => ActionEnum::CoGroupAddedBulk,
            'comment' => _txt('en.action', null, ActionEnum::CoGroupAddedBulk)
          );
          
          $outrecords['history_records'][] = $this->recordToRow($history, array(), $this->getFields('HistoryRecord'));
        } else {
          // This must be a Configuration plugin model
          
          if(!empty($pluginModels['Configuration'])) {
            foreach($pluginModels['Configuration'] as $m) {
              $bits = explode(".", $m, 2);
              
              if(!empty($inrecord[ $bits[1] ])) {
                // Found it, process the record
                $tableName = Inflector::tableize($bits[1]);
                
                if(isset($maxIds[$tableName])) {
                  $id = ++$maxIds[$tableName];
                  
                  $outrecords[$tableName][] = $this->recordToRow($inrecord[ $bits[1] ],
                                                                 array('id' => $id),
                                                                 $this->getFields($bits[1]));
                }
              }
            }
          }
        }
        
        // Create appropriate Automatic Group Memberships since we don't need an expensive
        // recalculation job to run
        foreach($autoGroupTodo as $couId => $cfg) {
          // Always add an Active Group Membership
          
          foreach(array(GroupEnum::AllMembers, GroupEnum::ActiveMembers) as $groupType) {
            if($groupType == GroupEnum::ActiveMembers 
               && !in_array($cfg['status'], array(StatusEnum::Active, StatusEnum::GracePeriod)))
              continue;
            
            $id = ++$maxIds['co_group_members'];
            
            $membership = array(
              'id' => $id,
              'co_group_id' => $autoGroups[$groupType][$couId], // $couId might be "CO"
              'co_person_id' => $coPersonId,
              'member' => true,
              'owner'  => false,
              'source_org_identity_id' => $cfg['source_org_identity_id']
            );
            
            $outrecords['co_group_members'][] = $this->recordToRow($membership, array(), $this->getFields('CoGroupMember'));
          }
        }
      }
      
      // Drop the indexes of the tables we'll write to to improve write performance
      $this->manageIndexes($db, 'drop', $pluginModels);
      
      // We use native Postgres calls in order to get access to the COPY FROM
      // command. This is a Postgres-specific command.
      
      $cxnstr = 'host=' . $db->config['host'] .
                        ' port=' . $db->config['port'] .
                        ' dbname=' . $db->config['database'] .
                        ' user=' . $db->config['login'] .
                        ' password=' . $db->config['password'];
      
      $dbh = pg_connect($cxnstr);
      
      pg_query($dbh, "BEGIN");
      
      $this->out(date('Y-m-d H:i:s ') . "Loading records to tables...");

      // We use $dropIndexes since it's ordered by foreign key dependencies
      foreach($this->dropIndexes as $table) {
        if(!empty($outrecords[$table])) {
          $this->out(date('Y-m-d H:i:s ') . "-> " . $table);
          
          // We can also use pg_put_line() to load rows incrementally
          pg_copy_from($dbh, 
                       $db->config['prefix'].$table . " (" . implode(',', $this->getFields(Inflector::classify($table))) . ")",
                       $outrecords[$table]);
          
          $this->out(date('Y-m-d H:i:s ') . "=> " . count($outrecords[$table]) . " records loaded");
        }
      }
      
      pg_query($dbh, "COMMIT");
      
      // Restart sequences
      $this->out(date('Y-m-d H:i:s ') . "Restarting sequences...");
      
      foreach(array_keys($maxIds) as $table) {
        pg_query($dbh, "ALTER SEQUENCE cm_" . $table . "_id_seq RESTART WITH " . ($maxIds[$table] + 1));
      }
      
      // Restore the database indexes
      $this->manageIndexes($db, 'create', $pluginModels);
    }
    
    /**
     * Get the fields for a given model, including metadata.
     *
     * @since  COmanage Registry v3.3.0
     * @param  Model $modelName Cake Model
     * @return Array            Array of fields
     */
    
    protected function getFields($modelName) {
      // Merge the core fields as defined via the model's validation with the
      // standard cake and changelog metadata fields.
      
      return array_merge(
        array_keys($this->$modelName->validate),
        array(
          'id',
          'created',
          'modified',
          // Changelog parent key, eg: co_person_id
          Inflector::underscore($modelName) . "_id",
          'revision',
          'deleted',
          'actor_identifier'
        )
      );
    }
    
    /**
     * Get the fields for a given model, including metadata.
     *
     * @since  COmanage Registry v3.3.0
     * @param  Model $modelName Cake Model
     * @return Array            Array of fields
     */
    
    public function getOptionParser() {
      $parser = parent::getOptionParser();
      
      $parser->addOption(
        'actor',
        array(
          'short'    => 'a',
          'help'     => 'actor_identifier for changelog column',
          'default'  => 'Bulk Load Shell'
        )
      )->addOption(
        'dbtype',
        array(
          'short'    => 't',
          'help'     => 'Target database type (only "postgres" is currently supported)',
          'choices'  => array('postgres'),
          'default'  => 'postgres'
        )
      )->addArgument(
        'co_id',
        array(
          'help'     => 'Target CO ID',
          'required' => true
        )
      )->addArgument(
        // We only support one file at a time because per-file metadata is hard
        // to deal with across multiple files.
        'infile',
        array(
          'help'     => 'Input data file in specified json format',
          'required' => true
        )
      )->description("Generate SQL for bulk load");
      
      return $parser;
    }
    
    /**
     * Get the maximum ID values for the tables of interest
     *
     * @since  COmanage Registry v3.3.0
     * @param  DatabaseConnection $db Database Connection Handle
     * @return Array                  Array of table names and maximum id values
     */
    
    protected function getMaxIDs($db) {
      $ret = array();
      
      foreach($this->dropIndexes as $table) {
        $max = $this->CoPerson->query("SELECT max(id) FROM " . $db->config['prefix'] . $table, false);
        
        $ret[$table] = $max[0][0]['max'];
        
        // Make sure everything is an integer
        if(!$ret[$table]) {
          $ret[$table] = 0;
        }
      }
      
      return $ret;
    }
    
    /**
     * Drop or (re)create table indexes.
     *
     * @since  COmanage Registry v3.3.0
     * @param  DatabaseConnection $db           Database connection handle
     * @param  string             $action       'create' or 'drop'
     * @param  array              $pluginModels Array of plugin models to process
     * @throws RuntimeException
     */
    
    protected function manageIndexes($db, $action, $pluginModels) {
      $db_driver = explode("/", $db->config['datasource'], 2);
      
      if($db_driver[0] != 'Database') {
        throw new RuntimeException("Unsupported db_method: " . $db_driver[0]);
      }
      
      // Boilerplate even though we only support postgres for bulk load
      $db_driverName = $db_driver[1];
      if(preg_match("/mysql/i", $db_driverName) && PHP_MAJOR_VERSION >= 7) {
        $db_driverName = 'mysqli';
      }
      
      $dbc = ADONewConnection($db_driverName);
      $dict = NewDataDictionary($dbc);
      
      // This code is similar to, but not identical to, DatabaseShell.
      if($dbc->Connect($db->config['host'],
                       $db->config['login'],
                       $db->config['password'],
                       $db->config['database'])) {
        $schemaFiles = array(
          APP . '/Config/Schema/schema.xml'
        );
        
        if(!empty($pluginModels)) {
          // This is actually an array of arrays, where the key is the parent
          // model, but we don't care about the parent model here.
          
          foreach(array_keys($pluginModels) as $pk) {
            foreach($pluginModels[$pk] as $pm) {
              $bits = explode(".", $pm, 2);
              
              // Plugins can be under either APP or LOCAL
              foreach(array(APP, LOCAL) as $dir) {
                // Check to see if the file exists/is readable
                $schemaFile = $dir . '/Plugin/' . $bits[0] . '/Config/Schema/schema.xml';

                if(is_readable($schemaFile)) {
                  $schemaFiles[] = $schemaFile;
                  break;
                }
              }
            }
          }
        }
        
        $this->out(date('Y-m-d H:i:s ') . _txt('sh.bl.indexes.' . ($action == 'drop' ? 'off' : 'on')));
        
        foreach($schemaFiles as $schemaFile) {
          $this->out(date('Y-m-d H:i:s ') . "-- " . $schemaFile);
          
          $schema = Xml::toArray(Xml::build(file_get_contents($schemaFile)));
          
          foreach($schema['schema']['table'] as $table) {
            if(in_array($table['@name'], $this->dropIndexes)) {
              $this->out(date('Y-m-d H:i:s ') . "-> ". $table['@name']);
              
              $indexes = array();
              
              if(!empty($table['@name'])) {
                // Annoyingly, $table['index'] might not be an array if there is
                // only one entry...
                if(isset($table['index']['@name'])) {
                  $indexes[] = $table['index'];
                } else {
                  $indexes = $table['index'];
                }
              }
              
              foreach($indexes as $index) {
                if($action == 'drop') {
                  $sql = $dict->dropIndexSql(
                    $db->config['prefix'] . $index['@name'], 
                    $db->config['prefix'] . $table['@name']
                  );
                } else {
                  $sql = $dict->createIndexSql(
                    $db->config['prefix'] . $index['@name'], 
                    $db->config['prefix'] . $table['@name'],
                    $index['col']
                  );
                }              
                
                $dict->executeSqlArray($sql);
              }
            }
          }
        }
      }
    }
    
    /**
     * Convert a record into a Postgres COPY row
     *
     * @since  COmanage Registry v3.3.0
     * @param  array  $record Record to copy
     * @param  array  $fks    Foreign keys, merged with $record
     * @param  array  $fields Array of permitted fields
     * @return string         Row suitable for Postgres COPY, including terminating newline
     */
    
    protected function recordToRow($record, $fks, $fields) {
      $delimiter = "\t";
      $nullString = '\\N';
      
      // Fully populate non-null record values
      $full = array_merge($record, $fks);
      
      // Add record metadata
      if(empty($record['created'])) {
        $full['created'] = date('Y-m-d H:i:s ');
      }
      if(empty($record['modified'])) {
        $full['modified'] = date('Y-m-d H:i:s ');
      }
      if(empty($record['revision'])) {
        $full['revision'] = 0;
      }
      if(empty($record['deleted'])) {
        $full['deleted'] = false;
      }
      if(empty($record['actor_identifier'])) {
        $full['actor_identifier'] = $this->params['actor'];
      }
      
      // Reformat each field if needed
      $formatted = array();
      
      foreach($fields as $f) {
        if(!isset($full[$f]) || is_null($full[$f])) {
          $formatted[$f] = $nullString;
        } elseif(is_bool($full[$f])) {
          $formatted[$f] = $full[$f] ? 't' : 'f';
        } else {
          $v = $full[$f];
          $m = null;
          
          // Process xrefs
          if(preg_match('/\@\{([a-zA-Z0-9]+)\}/', $v, $m)) {
            // $m[1] has the xref label to replace, $m[0] includes the @{}
            
            if(!empty($this->xrefs[ $m[1] ])) {
              $v = preg_replace('/'.$m[0].'/', $this->xrefs[ $m[1] ], $v);
            } else {
              $this->out(_txt('er.bulk.label.unknown', array($m[1])));
            }
          }
          // XXX also need to convert multiline to single line
          //     and escape instances of delimiter
          
          $formatted[$f] = $v;
        }
      }
      
      // Finally, collapse to a single row
      return implode($delimiter, $formatted) . "\n";
    }
  }
