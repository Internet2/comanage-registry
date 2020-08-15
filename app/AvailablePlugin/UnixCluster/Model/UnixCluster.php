<?php
/**
 * COmanage Registry Unix Cluster Model
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

App::uses("ClusterInterface", "Model");

class UnixCluster extends ClusterInterface {
  // Define class name for cake
  public $name = "UnixCluster";

  // Required by COmanage Plugins
  public $cmPluginType = "cluster";
	
	// Add behaviors
  public $actsAs = array('Containable',
                         'Changelog' => array('priority' => 5));
	
  // Document foreign keys
  public $cmPluginHasMany = array(
    "CoGroup"  => array("UnixClusterGroup"),
    "CoPerson" => array("UnixClusterAccount")
  );
	
	// Association rules from this model to other models
	public $belongsTo = array(
		"Cluster"
	);
	
	public $hasMany = array(
    "UnixCluster.UnixClusterAccount",
    "UnixCluster.UnixClusterGroup"
	);
	
  // Default display field for cake generated views
  public $displayField = "cluster_id";
	
  // Validation rules for table elements
  public $validate = array(
    'cluster_id' => array(
      'rule' => 'numeric',
      'required' => true,
			'allowEmpty' => false
		),
    'sync_mode' => array(
      'content' => array(
        'rule' => array('inList',
                        array(UnixClusterSyncEnum::Full,
                              UnixClusterSyncEnum::Manual)),
        'required' => true,
        'allowEmpty' => false
      )
    ),
    'username_type' => array(
      'content' => array(
        'rule' => array('validateExtendedType',
                        array('attribute' => 'Identifier.type',
                              'default' => array(IdentifierEnum::ePPN,
                                                 IdentifierEnum::ePTID,
                                                 IdentifierEnum::Mail,
                                                 IdentifierEnum::Network,
                                                 IdentifierEnum::OIDCsub,
                                                 IdentifierEnum::OpenID,
                                                 IdentifierEnum::SamlPairwise,
                                                 IdentifierEnum::SamlSubject,
                                                 IdentifierEnum::UID))),
        'required' => true,
        'allowEmpty' => false
      )
    ),
    'uid_type' => array(
      'content' => array(
        'rule' => array('validateExtendedType',
                        array('attribute' => 'Identifier.type',
                              'default' => array(IdentifierEnum::ePPN,
                                                 IdentifierEnum::ePTID,
                                                 IdentifierEnum::Mail,
                                                 IdentifierEnum::Network,
                                                 IdentifierEnum::OIDCsub,
                                                 IdentifierEnum::OpenID,
                                                 IdentifierEnum::SamlPairwise,
                                                 IdentifierEnum::SamlSubject,
                                                 IdentifierEnum::UID))),
        'required' => true,
        'allowEmpty' => false
      )
    ),
    'default_shell' => array(
      'content' => array(
        'rule' => array('inList',
                        array(UnixShellEnum::Bash,
                              UnixShellEnum::Csh,
                              UnixShellEnum::Dash,
                              UnixShellEnum::Ksh,
                              UnixShellEnum::Sh,
                              UnixShellEnum::Tcsh,
                              UnixShellEnum::Zsh)),
        'required' => true,
        'allowEmpty' => false
      )
    ),
    'homedir_prefix' => array(
      'rule' => '/^\/.*/',
      'required' => true,
      'allowEmpty' => false
    ),
    'homedir_subdivisions' => array(
      'rule' => 'numeric',
      'required' => false,
      'allowEmpty' => true
    ),
    'groupname_type' => array(
      'content' => array(
        'rule' => array('validateExtendedType',
                        array('attribute' => 'Identifier.type',
                              'default' => array(IdentifierEnum::ePPN,
                                                 IdentifierEnum::ePTID,
                                                 IdentifierEnum::GID,
                                                 IdentifierEnum::Mail,
                                                 IdentifierEnum::Network,
                                                 IdentifierEnum::OIDCsub,
                                                 IdentifierEnum::OpenID,
                                                 IdentifierEnum::SamlPairwise,
                                                 IdentifierEnum::SamlSubject,
                                                 IdentifierEnum::UID))),
        'required' => true,
        'allowEmpty' => false
      )
    ),
    'gid_type' => array(
      'content' => array(
        'rule' => array('validateExtendedType',
                        array('attribute' => 'Identifier.type',
                              'default' => array(IdentifierEnum::ePPN,
                                                 IdentifierEnum::ePTID,
                                                 IdentifierEnum::GID,
                                                 IdentifierEnum::Mail,
                                                 IdentifierEnum::Network,
                                                 IdentifierEnum::OIDCsub,
                                                 IdentifierEnum::OpenID,
                                                 IdentifierEnum::SamlPairwise,
                                                 IdentifierEnum::SamlSubject,
                                                 IdentifierEnum::UID))),
        'required' => true,
        'allowEmpty' => false
      )
    ),
    'default_co_group_id' => array(
      'rule' => 'numeric',
      'required' => false,
      'allowEmpty' => true
    )
	);
	
  /**
   * Expose menu items.
   * 
   * @ since COmanage Registry v3.3.0
   * @ return Array with menu location type as key and array of labels, controllers, actions as values.
   */
	
  public function cmPluginMenus() {
  	return array();
  }
  
  /**
   * Assign accounts for the specified CO Person.
   *
   * @since  COmanage Registry v3.3.0
   * @param  Array   $cluster    Array of Cluster configuration
   * @param  Integer $coPersonId CO Person ID
   * @return Boolean             True if an account was created, false if an account already existed
   * @throws RuntimeException
	 */
	
  public function assign($cluster, $coPersonId) {
    // There is related - but not identical - logic in UnixClusterListener::updateUnixClusterAccount.
    
    // Start a transaction
    $dbc = $this->getDataSource();
    $dbc->begin();
    
    try {
      // Pull our configuration
      $args = array();
      $args['conditions']['UnixCluster.cluster_id'] = $cluster['Cluster']['id'];
      $args['contain'] = false;
      
      $unixCluster = $this->find('first', $args);
      
      if(!$unixCluster) {
        throw new RuntimeException(_txt('er.notfound', array('Cluster', $cluster['Cluster']['id'])));
      }
      
      // Do we already have a Cluster Account for this CO Person? If so, any
      // additional accounts must be manually created.
      
  		$args = array();
  		$args['conditions']['UnixClusterAccount.unix_cluster_id'] = $unixCluster['UnixCluster']['id'];
  		$args['conditions']['UnixClusterAccount.co_person_id'] = $coPersonId;
  		
      if($this->UnixClusterAccount->find('count', $args) > 0) {
        return false;
      }
      
      // No account, so create one in accordance with the UnixCluster configuration.
      // We're passed the $cluster config because of how we're invoked, but we need
      // to pull the CO Person record to get various attributes we need.
      
      $args = array();
      $args['conditions']['CoPerson.id'] = $coPersonId;
      // This will ensure the CO Person is in the CO
      $args['conditions']['CoPerson.co_id'] = $cluster['Cluster']['co_id'];
      $args['contain'] = array(
        'Identifier' => array('conditions' => array('Identifier.status' => SuspendableStatusEnum::Active)),
        'PrimaryName'
      );
      
      $coPerson = $this->Cluster->Co->CoPerson->find('first', $args);
      
      if(!$coPerson) {
        throw new RuntimeException(_txt('er.cop.unk'));
      }
      
      // Make sure we have the necessary identifiers
      
      $username = Hash::extract($coPerson['Identifier'], '{n}[type='. $unixCluster['UnixCluster']['username_type'] .']');
      $uid = Hash::extract($coPerson['Identifier'], '{n}[type='. $unixCluster['UnixCluster']['uid_type'] .']');
      
      if(!$username || !$uid) {
        throw new RuntimeException(_txt('er.cluster.acct.ids'));
      }
      
      $acct = array(
        'unix_cluster_id' => $unixCluster['UnixCluster']['id'],
        'co_person_id'    => $coPersonId,
        'login_shell'     => $unixCluster['UnixCluster']['default_shell'],
        'status'          => StatusEnum::Active,
        'sync_mode'       => $unixCluster['UnixCluster']['sync_mode'],
        'valid_from'      => null,
        'valid_through'   => null
      );
      
      $acct['gecos'] = $this->calculateGecos($coPerson['PrimaryName']);
      $acct['username'] = $username[0]['identifier'];
      $acct['uid'] = $uid[0]['identifier'];
      
      // Construct the home directory
      $acct['home_directory'] = $this->calculateHomeDirectory(
        $unixCluster['UnixCluster'],
        $username[0]['identifier']
      );
      
      // Figure out a default group
      if(!empty($unixCluster['UnixCluster']['default_co_group_id'])) {
        // First, make sure $coPersonId is a member of $primary_co_group_id
        if(!$this->Cluster
                 ->Co
                 ->CoGroup
                 ->CoGroupMember
                 ->isMember($unixCluster['UnixCluster']['default_co_group_id'], $coPersonId)) {
          throw new RuntimeException(_txt('er.cluster.acct.grmem'));
        }
        
        $acct['primary_co_group_id'] = $unixCluster['UnixCluster']['default_co_group_id'];
      } else {
        // Is there already a CO Group with a groupname_type of $username? If so, use it
        $args = array();
        $args['conditions']['Identifier.identifier'] = $username[0]['identifier'];
        $args['conditions']['Identifier.type'] = $unixCluster['UnixCluster']['groupname_type'];
        $args['conditions'][] = 'Identifier.co_group_id IS NOT NULL';
        $args['conditions']['Identifier.status'] = SuspendableStatusEnum::Active;
        
        // There should be at most one
        $userCoGroupId = $this->Cluster
                              ->Co
                              ->CoGroup
                              ->Identifier
                              ->field('co_group_id', $args['conditions']);
        
        if(!empty($userCoGroupId)) {
          $acct['primary_co_group_id'] = $userCoGroupId;
        } else {
          // Create a new CO Group, make CO Person a member and owner of it, and assign
          // $groupname_type of $username and a $gid_type of $uid. Creating a new CO Group
          // will also provision it.
          
          $g = array(
            'CoGroup' => array(
              'co_id' => $cluster['Cluster']['co_id'],
              'name' => _txt('pl.unixcluster.fd.co_group_id.new.name', array($username[0]['identifier'])),
              'description' => _txt('pl.unixcluster.fd.co_group_id.new.desc', array($username[0]['identifier'])),
              'open' => false,
              'status' => SuspendableStatusEnum::Active,
              'cou_id' => null,
              'group_type' => GroupEnum::Standard,
              'auto' => false
            )
          );
          
          $this->Cluster->Co->CoGroup->clear();
          
          if(!$this->Cluster->Co->CoGroup->save($g)) {
            throw new RuntimeException(_txt('er.db.save-a', array('UnixCluster::assign CoGroup')));
          }
          
          // Attach the necessary identifiers
          
          $ids = array(
            array(
              'Identifier' => array(
                'identifier' => $username[0]['identifier'],
                'type' => $unixCluster['UnixCluster']['groupname_type'],
                'login' => false,
                'status' => SuspendableStatusEnum::Active,
                'co_group_id' => $this->Cluster->Co->CoGroup->id
              )
            ),
            array(
              'Identifier' => array(
                'identifier' => $uid[0]['identifier'],
                'type' => $unixCluster['UnixCluster']['gid_type'],
                'login' => false,
                'status' => SuspendableStatusEnum::Active,
                'co_group_id' => $this->Cluster->Co->CoGroup->id
              )
            )
          );
          
          // We need to inject the CO so extended types can be saved
          $this->Cluster->Co->CoGroup->Identifier->validate['type']['content']['rule'][1]['coid'] = $cluster['Cluster']['co_id'];
        
          if(!$this->Cluster->Co->CoGroup->Identifier->saveMany($ids)) {
            throw new RuntimeException(_txt('er.db.save-a', array('UnixCluster::assign Identifier')));
          }
          
          // Make the CO Person an owner and member of their new group
          
          $gm = array(
            'CoGroupMember' => array(
              'co_group_id' => $this->Cluster->Co->CoGroup->id,
              'co_person_id' => $coPersonId,
              'owner' => true,
              'member' => true
            )
          );
          
          $this->Cluster->Co->CoGroup->CoGroupMember->clear();
          
          if(!$this->Cluster->Co->CoGroup->CoGroupMember->save($gm)) {
            throw new RuntimeException(_txt('er.db.save-a', array('UnixCluster::assign CoGroupMember')));
          }
          
          $acct['primary_co_group_id'] = $this->Cluster->Co->CoGroup->id;
          
          // Add the CO Group to the Unix Cluster
          
          $ucg = array(
            'UnixClusterGroup' => array(
              'unix_cluster_id' => $unixCluster['UnixCluster']['id'],
              'co_group_id' => $this->Cluster->Co->CoGroup->id
            )
          );
          
          $this->UnixClusterGroup->clear();
          
          if(!$this->UnixClusterGroup->save($ucg)) {
            throw new RuntimeException(_txt('er.db.save-a', array('UnixCluster::assign UnixClusterGroup')));
          }
        }
      }
      
      // Finally ready to save the new account
      $this->UnixClusterAccount->clear();
      
      if(!$this->UnixClusterAccount->save($acct)) {
        throw new RuntimeException(_txt('er.db.save-a', array('UnixCluster::assign UnixClusterAccount')));
      }
      
      // Note history record is created by Cluster::assign(), which is generally
      // how we are called.
      
      $dbc->commit();
    }
    catch(Exception $e) {
      $dbc->rollback();
      throw new RuntimeException($e->getMessage());
    }
    
    return true;
  }
  
  /**
   * Calculate the GECOS field value for a Unix Cluster Account.
   *
   * @since  COmanage Registry v3.3.0
   * @param  array  $name       Array of Name
   * @return string             GECOS string
   * @throws RuntimeException
	 */
  
  public function calculateGecos($name) {
    // We only use Primary Name for gecos, though we could probably leverage
    // Address and TelephoneNumber somehow... Also, we don't append trailing
    // commas for the moment (and there isn't a spec that says we should) but
    // we might add them later.

    if(!empty($name)) {
      return generateCn($name);
    }
    
    throw new RuntimeException(_txt('er.unixcluster.gecos.pname'));
  }
  
  /**
   * Calculate the home directory for a Unix Cluster Account.
   *
   * @since  COmanage Registry v3.3.0
   * @param  array  $unixCluster Array of Unix Cluster configurtion
   * @param  string $identifier  Identifier to use as basis for home directory
   * @return string              Home Directory
	 */
  
  public function calculateHomeDirectory($unixCluster, $identifier) {
    $homedirAffix = $identifier;
    
    if(!empty($unixCluster['homedir_subdivisions'])
       && $unixCluster['homedir_subdivisions'] > 0) {
      $infix = "";
      
      for($i = 0;$i < $unixCluster['homedir_subdivisions'];$i++) {
        $infix .= $identifier[$i] . "/";
      }
      
      $homedirAffix = $infix . $homedirAffix;
    }
    
    return $unixCluster['homedir_prefix'] . "/" . $homedirAffix;
  }
  
  /**
   * Declare searchable models.
   *
   * @since  COmanage Registry v3.3.0
   * @return Array Array of searchable models
   */

  public function cmPluginSearchModels() {
    return array(
      'UnixCluster.UnixClusterAccount' => array(
        'displayField' => 'username',
        'permissions' => array('cmadmin', 'coadmin')
      )
    );
  }
  
	/**
	 * Obtain the current Cluster status for a CO Person.
	 *
	 * @since  COmanage Registry v3.3.0
	 * @param  integer $coPersonId			CO Person ID
	 * @return Array Array with values
	 * 							 comment: Human readable string, visible to the CO Person
	 */
	
	public function status($coPersonId) {
		// Are there any Unix Cluster Accounts for this person?
		
		$args = array();
		$args['conditions']['UnixClusterAccount.unix_cluster_id'] = $this->pluginCfg['UnixCluster']['id'];
		$args['conditions']['UnixClusterAccount.co_person_id'] = $coPersonId;
		$args['contain'] = false;
		
		$accounts = $this->UnixClusterAccount->find('all', $args);
		
		return array(
			'comment' => _txt('pl.unixcluster.accounts.registered', array(count($accounts)))
		);
	}
}
