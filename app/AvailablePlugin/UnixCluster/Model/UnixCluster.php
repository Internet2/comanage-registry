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
 * @since         COmanage Registry v3.4.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::uses("ClusterInterface", "Model");

class UnixCluster extends ClusterInterface {
  // Define class name for cake
  public $name = "UnixCluster";

  // Required by COmanage Plugins
  public $cmPluginType = "cluster";
	
	// Add behaviors
  public $actsAs = array('Containable');
	
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
    'username_type' => array(
      'content' => array(
        'rule' => array('validateExtendedType',
                        array('attribute' => 'Identifier.type',
                              'default' => array(IdentifierEnum::ePPN,
                                                 IdentifierEnum::ePTID,
                                                 IdentifierEnum::Mail,
                                                 IdentifierEnum::Network,
                                                 IdentifierEnum::OpenID,
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
                                                 IdentifierEnum::OpenID,
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
                                                 IdentifierEnum::OpenID,
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
                                                 IdentifierEnum::OpenID,
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
	 * Obtain the current Cluster status for a CO Person.
	 *
	 * @since  COmanage Registry v3.4.0
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
