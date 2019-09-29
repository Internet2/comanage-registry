<?php
/**
 * COmanage Registry CO SQL Provisioner Target Model
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

App::uses("CoProvisionerPluginTarget", "Model");

// App::import doesn't handle this correctly
require(APP . '/Vendor/adodb5/adodb.inc.php');
require(APP . '/Vendor/adodb5/adodb-xmlschema03.inc.php');

class CoSqlProvisionerTarget extends CoProvisionerPluginTarget {
  // Define class name for cake
  public $name = "CoSqlProvisionerTarget";
  
  // Add behaviors
  public $actsAs = array('Containable');
  
  // Association rules from this model to other models
  public $belongsTo = array("CoProvisioningTarget");
  
  // Default display field for cake generated views
  public $displayField = "server_id";
  
  // Request SQL servers
  public $cmServerType = ServerEnum::SqlServer;
  
  // Validation rules for table elements
  public $validate = array(
    'co_provisioning_target_id' => array(
      'rule' => 'numeric',
      'required' => true
    ),
    'server_id' => array(
      'rule' => 'numeric',
      'required' => true
    )
  );
  
  // The models we currently synchronize, not including CO Person, in the order
  // we want to process them.
  public $models = array(
    array(
      'table'  => 'sp_names',
      'name'   => 'SpName',
      'source' => 'Name',
      'parent' => 'CoPerson'
    ),
    array(
      'table'  => 'sp_identifiers',
      'name'   => 'SpIdentifier',
      'source' => 'Identifier',
      'parent' => 'CoPerson'
    ),
    array(
      'table'  => 'sp_email_addresses',
      'name'   => 'SpEmailAddress',
      'source' => 'EmailAddress',
      'parent' => 'CoPerson'
    ),
    array(
      'table'  => 'sp_urls',
      'name'   => 'SpUrl',
      'source' => 'Url',
      'parent' => 'CoPerson'
    ),
    array(
      'table'  => 'sp_co_t_and_c_agreements',
      'name'   => 'SpCoTAndCAgreement',
      'source' => 'CoTAndCAgreement',
      'parent' => 'CoPerson'
    ),
    array(
      'table'  => 'sp_co_group_members',
      'name'   => 'SpCoGroupMember',
      'source' => 'CoGroupMember',
      'parent' => 'CoPerson'
    ),
    array(
      'table'  => 'sp_co_person_roles',
      'name'   => 'SpCoPersonRole',
      'source' => 'CoPersonRole',
      'parent' => 'CoPerson'
    ),
    array(
      'table'  => 'sp_addresses',
      'name'   => 'SpAddress',
      'source' => 'Address',
      'parent' => 'CoPersonRole'
    ),
    array(
      'table'  => 'sp_telephone_numbers',
      'name'   => 'SpTelephoneNumber',
      'source' => 'TelephoneNumber',
      'parent' => 'CoPersonRole'
    )
  );
  
  // The primary/parent models being provisioned
  public $parentModels = array(
    'CoGroup' => array(
      'table'  => 'sp_co_groups',
      'name'   => 'SpCoGroup',
      'source' => 'CoGroup',
      'source_table' => 'co_groups',
      'parent' => 'Co'
    ),
    'CoPerson' => array(
      'table'  => 'sp_co_people',
      'name'   => 'SpCoPerson',
      'source' => 'CoPerson',
      'source_table' => 'co_people',
      'parent' => 'Co'
    )
  );
  
  // Models holding reference data (that ordinarily isn't provisioned)
  public $referenceModels = array(
    array(
      'table'  => 'sp_cous',
      'name'   => 'SpCou',
      'source' => 'Cou',
      'source_table' => 'cous',
      'parent' => 'Co'
    ),
    array(
      'table'  => 'sp_co_terms_and_conditions',
      // Ordinarily we'd call this SpCoTermsAndConditions, but it's not worth
      // fighting cake's inflector
      'name'   => 'SpCoTermsAndCondition',
      'source' => 'CoTermsAndConditions',
      'source_table' => 'co_terms_and_conditions',
      'parent' => 'Co'
    )
  );
  
  /**
   * Callback after model save.
   *
   * @since  COmanage Registry v3.3.0
   * @param  Boolean $created True if new model is saved (ie: add)
   * @param  Array $options Options, as based to model::save()
   * @return Boolean True on success
   */

  public function afterSave($created, $options = Array()) {
    if(!empty($this->data['CoSqlProvisionerTarget']['server_id'])) {
      // Pull the Server configuration and apply the database schema
      
      $args = array();
      $args['conditions']['Server.id'] = $this->data['CoSqlProvisionerTarget']['server_id'];
      $args['contain'] = array('SqlServer');
      
      $srvr = $this->CoProvisioningTarget->Co->Server->find('first', $args);
      
      $db_driverName = null;
      
      switch($srvr['SqlServer']['type']) {
        case SqlServerEnum::Mysql:
          $db_driverName = 'mysqli';
          break;
        case SqlServerEnum::Postgres:
          $db_driverName = 'postgres';
          break;
        case SqlServerEnum::SqlServer:
          $db_driverName = 'mssql';
          break;
        default:
          throw new InvalidArgumentException(_txt('er.unknown', array($srvr['Server']['server_type'])));
          break;
      }
      
      $dbc = ADONewConnection($db_driverName);

      if(!$dbc->Connect($srvr['SqlServer']['hostname'],
                       $srvr['SqlServer']['username'],
                       $srvr['SqlServer']['password'],
                       $srvr['SqlServer']['databas'])) {
        throw new RuntimeException(_txt('er.db.connect', array($dbc->ErrorMsg())));
      }
      
      $schemaFile = LOCAL . DS . 'Plugin' . DS . 'SqlProvisioner' . DS . 'Config' . DS . 'Schema' . DS . 'schema-target.xml';
      
      if(!is_readable($schemaFile)) {
        throw new RuntimeException(_txt('er.file.read', array($schemaFile)));
      }
      
      $schema = new adoSchema($dbc);
//        $schema->setPrefix($db->config['prefix']);
      // ParseSchema is generating bad SQL for Postgres. eg:
      //  ALTER TABLE cm_cos ALTER COLUMN id SERIAL
      // which (1) should be ALTER TABLE cm_cos ALTER COLUMN id TYPE SERIAL
      // and (2) SERIAL isn't usable in an ALTER TABLE statement
      // So we continue on error
      $schema->ContinueOnError(true);
      
      // Parse the database XML schema from file unless we are targeting MySQL
      // in which case we use an XSL style sheet to first modify the schema
      // so that boolean columns are cast to TINYINT(1) and the cakePHP
      // automagic works. See
      //
      // https://bugs.internet2.edu/jira/browse/CO-175
      //
      if($srvr['SqlServer']['type'] != SqlServerEnum::Mysql) {
        $sql = $schema->ParseSchema($schemaFile);
      } else {
        $xml = new DOMDocument;
        $xml->load($schemaFile);

        $xsl = new DOMDocument;
        $xsl->load(APP . '/Config/Schema/boolean_mysql.xsl');

        $proc = new XSLTProcessor;
        $proc->importStyleSheet($xsl);

        $sql = $schema->ParseSchemaString($proc->transformToXML($xml));
      }

      if($schema->ExecuteSchema($sql) != 2) { // !!!
        // We should throw an error here, but AdoDB doesn't reliably detect
        // the application of the schema after the first run.
      }

      $dbc->Disconnect();
      
      // Now populate (or update) the referece data
      
      // Just let any exceptions bubble up the stack
      $this->CoProvisioningTarget->Co->Server->SqlServer->connect($this->data['CoSqlProvisionerTarget']['server_id'], "targetdb");
      
      $coId = $this->CoProvisioningTarget->field('co_id', array('CoProvisioningTarget.id' => $this->data['CoSqlProvisionerTarget']['co_provisioning_target_id']));
      
      if($coId) {
        $this->syncReferenceData($coId);
        
        // We treat CO Groups sort of as reference data and sort of as operational data.
        // We'll populate them all here as though they were reference data, but then
        // we'll update them using normal CO Group Provisioning events.
        $this->syncAllGroups($coId);
      }
    }
  }
  
  /**
   * Remove a group from the target database, following a delete group operation.
   *
   * @since  COmanage Registry v3.3.0
   * @param  Array $provisioningData Array of provisioning data
   */
  
  protected function deleteGroup($provisioningData) {
    // We shouldn't need to delete CoGroupMemberships, but just in case...
    
    $Model = new Model(array(
      'table' => 'sp_co_group_members',
      'name'  => 'SpCoGroupMember',
      'ds'    => 'targetdb'
    ));
    
    $Model->deleteAll(array('co_group_id' => $provisioningData['CoGroup']['id']), false);
    
    // CoGroupMemberships should already have been deleted via person updates.
    
    $SpCoGroup = new Model(array(
      'table'  => $this->parentModels['CoGroup']['table'],
      'name'   => $this->parentModels['CoGroup']['name'],
      'ds'     => 'targetdb'
    ));
    
    $SpCoGroup->delete($provisioningData['CoGroup']['id'], false);
  }
  
  /**
   * Remove a person from the target database, following a delete person operation.
   *
   * @since  COmanage Registry v3.3.0
   * @param  Array $provisioningData Array of provisioning data
   */
  
  protected function deletePerson($provisioningData) {
    // We need to walk the model array backwards (ie: so we delete Addresses
    // before we delete CoPersonRoles)
    
    foreach(array_reverse($this->models) as $m) {
      $Model = new Model(array(
        'table' => $m['table'],
        'name'  => $m['name'],
        'ds'    => 'targetdb'
      ));
      
      if($m['parent'] == 'CoPersonRole') {
        foreach($provisioningData['CoPersonRole'] as $pr) {
          if(!empty($pr[ $m['source'] ])) {
            foreach($pr[ $m['source'] ] as $r) {
              $Model->delete($r['id'], false);
            }
          }
        }
      } else {
        if(!empty($provisioningData[ $m['source'] ])) {
          foreach($provisioningData[ $m['source'] ] as $r) {
            $Model->delete($r['id'], false);
          }
        }
      }
    }
    
    // Finally delete the CO Person record itself.
    
    $SpCoPerson = new Model(array(
      'table'  => $this->parentModels['CoPerson']['table'],
      'name'   => $this->parentModels['CoPerson']['name'],
      'ds'     => 'targetdb'
    ));
    
    $SpCoPerson->delete($provisioningData['CoPerson']['id'], false);
  }
  
  /**
   * Provision for the specified CO Person.
   *
   * @since  COmanage Registry v3.3.0
   * @param  Array CO Provisioning Target data
   * @param  ProvisioningActionEnum Registry transaction type triggering provisioning
   * @param  Array Provisioning data, populated with ['CoPerson'] or ['CoGroup']
   * @return Boolean True on success
   * @throws RuntimeException
   */
  
  public function provision($coProvisioningTargetData, $op, $provisioningData) {
    // First determine what to do
    $deleteGroup = false;
    $deletePerson = false;
    $syncGroup = false;
    $syncPerson = false;
    
    switch($op) {
      case ProvisioningActionEnum::CoGroupAdded:
      case ProvisioningActionEnum::CoGroupUpdated:
      case ProvisioningActionEnum::CoGroupReprovisionRequested:
        $syncGroup = true;
        break;
      case ProvisioningActionEnum::CoGroupDeleted:
        $deleteGroup = true;
        break;
      case ProvisioningActionEnum::CoPersonAdded:
      case ProvisioningActionEnum::CoPersonEnteredGracePeriod:
      case ProvisioningActionEnum::CoPersonExpired:
      case ProvisioningActionEnum::CoPersonPetitionProvisioned:
      case ProvisioningActionEnum::CoPersonPipelineProvisioned:
      case ProvisioningActionEnum::CoPersonReprovisionRequested:
      case ProvisioningActionEnum::CoPersonUnexpired:
      case ProvisioningActionEnum::CoPersonUpdated:
        $syncPerson = true;
        break;
      case ProvisioningActionEnum::CoPersonDeleted:
        $deletePerson = true;
        break;
      default:
        // Ignore all other actions. Note group membership changes
        // are typically handled as CoPersonUpdated events.
        return true;
        break;
    }
    
    // Just let any exceptions bubble up the stack
    $this->CoProvisioningTarget->Co->Server->SqlServer->connect($coProvisioningTargetData['CoSqlProvisionerTarget']['server_id'], "targetdb");
    
    // Start a transaction
    $dbc = $this->getDataSource('targetdb');
    $dbc->begin();
    
    try {
      if($syncPerson) {
        $this->syncPerson($provisioningData);
      } elseif($deletePerson) {
        $this->deletePerson($provisioningData);
      } elseif($syncGroup) {
        $this->syncGroup($provisioningData);
      } elseif($deleteGroup) {
        $this->deleteGroup($provisioningData);
      }
      
      $dbc->commit();
    }
    catch(Exception $e) {
      $dbc->rollback();
      throw new RuntimeException($e->getMessage());
    }
    
    return true;
  }
  
  /**
   * Sync all CO Groups. Intended to be called from afterSave to do an initial population.
   *
   * @since  COmanage Registry v3.3.0
   * @param  Integer $coId CO ID to sync groups for
   */
  
  public function syncAllGroups($coId) {
    $Model = new Model(array(
      'table' => $this->parentModels['CoGroup']['table'],
      'name'  => $this->parentModels['CoGroup']['name'],
      'ds'    => 'targetdb'
    ));
    
    // Instantiate the core (source) model and pull the records associated with this CO ID
    $SrcModel = ClassRegistry::init($this->parentModels['CoGroup']['source']);
    
    $args = array();
    $args['conditions'][ $this->parentModels['CoGroup']['source'].'.co_id' ] = $coId;
    $args['contain'] = false;
    
    $records = $SrcModel->find('all', $args);
    
    if(!empty($records)) {
      $Model->clear();
      
      $Model->saveMany(Hash::extract($records, '{n}.'.$this->parentModels['CoGroup']['source']),
                       array('validate' => false));
      
      // Remove any deleted records (those remaining in the reference table
      // that were not in $records)
      
      $Model->deleteAll(array('NOT' => array('id' => Hash::extract($records, '{n}.'.$this->parentModels['CoGroup']['source'].'.id'))), false);
    }
  }
  
  /**
   * Sync all reference data. Intended to be called from the Event listener,
   * which doesn't have an CO Provisioning Target context.
   *
   * @since  COmanage Registry v3.3.0
   * @param  Integer $coId CO ID to sync reference data for
   */
  
  public function syncAllReferenceData($coId) {
    // Pull all SqlProvisioner configurations for the CO
    
    $args = array();
    $args['joins'][0]['table'] = 'co_provisioning_targets';
    $args['joins'][0]['alias'] = 'CoProvisioningTarget';
    $args['joins'][0]['type'] = 'INNER';
    $args['joins'][0]['conditions'][0] = 'CoSqlProvisionerTarget.co_provisioning_target_id=CoProvisioningTarget.id';
    $args['conditions']['CoProvisioningTarget.co_id'] = $coId;
    $args['contain'] = false;
    
    $targets = $this->find('all', $args);
    
    // Loop through each configuration, instantiating a DataSource, then
    // performing the sync
    
    if(!empty($targets)) {
      foreach($targets as $t) {
        // We need a unique data source label for each target
        $sourceLabel = 'targetdb' . $t['CoSqlProvisionerTarget']['server_id'];
        
        // Just let any exceptions bubble up the stack
        $this->CoProvisioningTarget->Co->Server->SqlServer->connect($t['CoSqlProvisionerTarget']['server_id'], $sourceLabel);
        
        $this->syncReferenceData($coId, $sourceLabel);
      }
    }
  }
  
  /**
   * Sync a group to the target database, following a save group operation.
   *
   * @since  COmanage Registry v3.3.0
   * @param  Array $provisioningData Array of provisioning data
   */
  
  protected function syncGroup($provisioningData) {
    $SpCoGroup = new Model(array(
      'table'  => $this->parentModels['CoGroup']['table'],
      'name'   => $this->parentModels['CoGroup']['name'],
      'ds'     => 'targetdb'
    ));
    
    $SpCoGroup->clear();

    $data = array(
      'SpCoGroup' => $provisioningData['CoGroup']
    );
    
    // No need to validate anything, though we also don't have any validation rules
    $SpCoGroup->save($data, false);
    
    // We only save the group metadata, we do not update memberships.
    // That will be handled by the person update.
  }
  
  /**
   * Sync a person to the target database, following a save person operation.
   *
   * @since  COmanage Registry v3.3.0
   * @param  Array $provisioningData Array of provisioning data
   */
  
  protected function syncPerson($provisioningData) {
    // Start with CO Person

    $SpCoPerson = new Model(array(
      'table'  => $this->parentModels['CoPerson']['table'],
      'name'   => $this->parentModels['CoPerson']['name'],
      'ds'     => 'targetdb'
    ));
    
    $SpCoPerson->clear();

    $data = array(
      'SpCoPerson' => $provisioningData['CoPerson']
    );
    
    // No need to validate anything, though we also don't have any validation rules
    $SpCoPerson->save($data, false);

    // Loop through the models and sync the data
    
    foreach($this->models as $m) {
      $Model = new Model(array(
        'table' => $m['table'],
        'name'  => $m['name'],
        'ds'    => 'targetdb'
      ));
      
      // All associated models we care about are HasMany. For those that belong
      // to CoPersonRole, we also have to iterate through each provided Role.
      
      $records = array();
      $parentfk = 'co_person_id';
      $parentids = array();
      
      if($m['parent'] == 'CoPersonRole') {
        $records = Hash::extract($provisioningData['CoPersonRole'], '{n}.' . $m['source'] . '.{n}');
        $parentfk = 'co_person_role_id';
        $parentids = array_unique(Hash::extract($provisioningData['CoPersonRole'], '{n}.co_person_role_id'));
      } else {
        $records = $provisioningData[ $m['source'] ];
        $parentids[] = $provisioningData['CoPerson']['id'];
      }
      
      if(!empty($parentids)) {
        foreach($records as $d) {
          $Model->clear();
          
          // Since we're copying the source table's id column, we don't have to
          // check for an existing record. Cake will effectively upsert for us.
        
          $data = array(
            $m['name'] => $d
          );
          
          // XXX Should we filter records where source_*_id is not null? eg for name?
          // No, not for now since they are part of the CO person record, though maybe
          // we should add the source metadata or offer a "filter duplicate values" option.

          // No need to validate anything, though we also don't have any validation rules
          $Model->save($data, false);
        }
        
        // Now delete any records belonging to the parent record that aren't in
        // our active record set.
        
        $args = array($parentfk => $parentids);
        
        if(!empty($records)) {
          $args['NOT'] = array('id' => Hash::extract($records, '{n}.id'));
        }
        
        // For CoPersonRole, we need to manually delete the child models since
        // on a syncPerson() call we move forward through referenceModels.
        
        if($m['name'] == 'SpCoPersonRole') {
          // Figure out what roles are going to be deleted so we can delete the
          // associated child models.
          $deletedRoles = $Model->find('list', array('conditions' => $args, 'fields' => array('id')));
          
          if(!empty($deletedRoles)) {
            foreach($this->models as $cm) {
              // This child model has our current model as a parent, delete any relevant rows
              if($cm['parent'] == 'CoPersonRole') {
                // We could set up a local model registry so we don't have to
                // instantiate them all over the place...
                $CModel = new Model(array(
                  'table' => $cm['table'],
                  'name'  => $cm['name'],
                  'ds'    => 'targetdb'
                ));
                
                $cargs = array('co_person_role_id' => array_keys($deletedRoles));
                
                $CModel->deleteAll($cargs, false);
              }
            }
          }
        }
        
        $Model->deleteAll($args, false);
      }
    }
  }
  
  /**
   * Synchronize reference data to the target database.
   *
   * @since  COmanage Registry v3.3.0
   * @param  Integer $coId       CO ID
   * @param  string  $dataSource DataSource label
   */
  
  protected function syncReferenceData($coId, $dataSource='targetdb') {
    foreach($this->referenceModels as $m) {
      $Model = new Model(array(
        'table' => $m['table'],
        'name'  => $m['name'],
        'ds'    => $dataSource
      ));
      
      // Instantiate the core (source) model and pull the records associated with this CO ID
      $SrcModel = ClassRegistry::init($m['source']);
      
      $args = array();
      $args['conditions'][ $m['source'].'.co_id' ] = $coId;
      $args['contain'] = false;
      
      $records = $SrcModel->find('all', $args);
      
      if(!empty($records)) {
        $Model->clear();
        
        $Model->saveMany(Hash::extract($records, '{n}.'.$m['source']),
                         array('validate' => false));
        
        // Remove any deleted records (those remaining in the reference table
        // that were not in $records)
        
        $Model->deleteAll(array('NOT' => array('id' => Hash::extract($records, '{n}.'.$m['source'].'.id'))), false);
      }
    }
  }
}
