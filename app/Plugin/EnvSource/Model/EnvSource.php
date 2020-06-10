<?php
/**
 * COmanage Registry Env OrgIdentitySource Model
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
 * @since         COmanage Registry v3.1.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

class EnvSource extends AppModel {
  // Required by COmanage Plugins
  public $cmPluginType = "orgidsource";
  
  // Document foreign keys
  public $cmPluginHasMany = array();
  
  // Association rules from this model to other models
  public $belongsTo = array("OrgIdentitySource");
  
  // Default display field for cake generated views
//  public $displayField = "env_name_given";
  
  // Validation rules for table elements
  public $validate = array(
    'org_identity_source_id' => array(
      'rule' => 'numeric',
      'required' => true,
      'message' => 'An Org Identity Source ID must be provided'
    ),
    'env_name_honorific' => array(
      'rule' => 'notBlank',
      'required' => false,
      'allowEmpty' => true
    ),
    'env_name_given' => array(
      'rule' => 'notBlank',
      'required' => false,
      'allowEmpty' => true
    ),
    'env_name_middle' => array(
      'rule' => 'notBlank',
      'required' => false,
      'allowEmpty' => true
    ),
    'env_name_family' => array(
      'rule' => 'notBlank',
      'required' => false,
      'allowEmpty' => true
    ),
    'env_name_suffix' => array(
      'rule' => 'notBlank',
      'required' => false,
      'allowEmpty' => true
    ),
    'env_affiliation' => array(
      'rule' => 'notBlank',
      'required' => false,
      'allowEmpty' => true
    ),
    'env_title' => array(
      'rule' => 'notBlank',
      'required' => false,
      'allowEmpty' => true
    ),
    'env_o' => array(
      'rule' => 'notBlank',
      'required' => false,
      'allowEmpty' => true
    ),
    'env_ou' => array(
      'rule' => 'notBlank',
      'required' => false,
      'allowEmpty' => true
    ),
    'env_mail' => array(
      'rule' => 'notBlank',
      'required' => false,
      'allowEmpty' => true
    ),
    'env_telephone_number' => array(
      'rule' => 'notBlank',
      'required' => false,
      'allowEmpty' => true
    ),
    'env_address_street' => array(
      'rule' => 'notBlank',
      'required' => false,
      'allowEmpty' => true
    ),
    'env_address_locality' => array(
      'rule' => 'notBlank',
      'required' => false,
      'allowEmpty' => true
    ),
    'env_address_state' => array(
      'rule' => 'notBlank',
      'required' => false,
      'allowEmpty' => true
    ),
    'env_address_postalcode' => array(
      'rule' => 'notBlank',
      'required' => false,
      'allowEmpty' => true
    ),
    'env_address_country' => array(
      'rule' => 'notBlank',
      'required' => false,
      'allowEmpty' => true
    ),
    'env_identifier_eppn' => array(
      'rule' => 'notBlank',
      'required' => false,
      'allowEmpty' => true
    ),
    'env_identifier_eppn_login' => array(
      'rule' => 'boolean',
      'required' => false,
      'allowEmpty' => true
    ),
    'env_identifier_eptid' => array(
      'rule' => 'notBlank',
      'required' => false,
      'allowEmpty' => true
    ),
    'env_identifier_eptid_login' => array(
      'rule' => 'boolean',
      'required' => false,
      'allowEmpty' => true
    ),
    'env_identifier_epuid' => array(
      'rule' => 'notBlank',
      'required' => false,
      'allowEmpty' => true
    ),
    'env_identifier_epuid_login' => array(
      'rule' => 'boolean',
      'required' => false,
      'allowEmpty' => true
    ),
    'env_identifier_orcid' => array(
      'rule' => 'notBlank',
      'required' => false,
      'allowEmpty' => true
    ),
    'env_identifier_orcid_login' => array(
      'rule' => 'boolean',
      'required' => false,
      'allowEmpty' => true
    ),
    'env_identifier_sorid' => array(
      'rule' => 'notBlank',
      // Note SORID is required
      'required' => true,
      'allowEmpty' => false
    ),
    'env_identifier_sorid_login' => array(
      'rule' => 'boolean',
      'required' => false,
      'allowEmpty' => true
    ),
    'env_identifier_network' => array(
      'rule' => 'notBlank',
      'required' => false,
      'allowEmpty' => true
    ),
    'env_identifier_network_login' => array(
      'rule' => 'boolean',
      'required' => false,
      'allowEmpty' => true
    )
  );
  
  /**
   * Obtain the list of attributes available for loading into an Org Identity.
   *
   * @since  COmanage Registry v3.1.0
   * @return Array Array of available attributes
   */
  
  public function availableAttributes() {
    // Attributes should be listed in the order they are to be rendered in.
    // The various _name fields are default values that can be overridden.
    // Attribute types are forced to Official since they come from an "official" source.
    
    // The key is the column name in cm_env_sources
    $attributes = array(
      'env_identifier_sorid' => array(
        'label'    => _txt('fd.identifier.identifier') . " (" . _txt('en.identifier.type', null, IdentifierEnum::SORID) . ")",
        'default'  => 'ENV_OIS_SORID',
        'required' => true,
        'desc'     => _txt('pl.envsource.sorid.desc'),
        'canLogin' => false
      ),
      'env_name_honorific' => array(
        'label'   => _txt('fd.name.honorific') . " (" . _txt('en.name.type', null, NameEnum::Official) . ")"
      ),
      'env_name_given' => array(
        'label'   => _txt('fd.name.given') . " (" . _txt('en.name.type', null, NameEnum::Official) . ")",
        'default' => 'ENV_OIS_NAME_GIVEN'
      ),
      'env_name_middle' => array(
        'label'   => _txt('fd.name.middle') . " (" . _txt('en.name.type', null, NameEnum::Official) . ")"
      ),
      'env_name_family' => array(
        'label'   => _txt('fd.name.family') . " (" . _txt('en.name.type', null, NameEnum::Official) . ")",
        'default' => 'ENV_OIS_NAME_FAMILY'
      ),
      'env_name_suffix' => array(
        'label'   => _txt('fd.name.suffix') . " (" . _txt('en.name.type', null, NameEnum::Official) . ")"
      ),
      'env_identifier_eppn' => array(
        'label'   => _txt('fd.identifier.identifier') . " (" . _txt('en.identifier.type', null, IdentifierEnum::ePPN) . ")",
        'default' => 'ENV_OIS_EPPN',
        'canLogin'=> true
      ),
      'env_identifier_eptid' => array(
        'label'   => _txt('fd.identifier.identifier') . " (" . _txt('en.identifier.type', null, IdentifierEnum::ePTID) . ")",
        'default' => 'ENV_OIS_EPTID',
        'canLogin'=> true
      ),
      'env_identifier_epuid' => array(
        'label'   => _txt('fd.identifier.identifier') . " (" . _txt('en.identifier.type', null, IdentifierEnum::ePUID) . ")",
        'default' => 'ENV_OIS_EPUID',
        'canLogin'=> true
      ),
      'env_identifier_orcid' => array(
        'label'   => _txt('fd.identifier.identifier') . " (" . _txt('en.identifier.type', null, IdentifierEnum::ORCID) . ")",
        'default' => 'ENV_OIS_ORCID',
        'canLogin'=> true
      ),
      'env_identifier_network' => array(
        'label'   => _txt('fd.identifier.identifier') . " (" . _txt('en.identifier.type', null, IdentifierEnum::Network) . ")",
        'default' => 'ENV_OIS_NETWORK',
        'canLogin'=> true
      ),
      'env_mail' => array(
        'label'   => _txt('fd.email_address.mail') . " (" . _txt('en.email_address.type', null, EmailAddressEnum::Official) . ")",
        'default' => 'ENV_OIS_MAIL',
      ),
      'env_affiliation' => array(
        'label'   => _txt('fd.affiliation'),
        'default' => 'ENV_OIS_AFFILIATION'
      ),
      'env_title' => array(
        'label'   => _txt('fd.title'),
        'default' => 'ENV_OIS_TITLE'
      ),
      'env_o' => array(
        'label'   => _txt('fd.o'),
        'default' => 'ENV_OIS_O'
      ),
      'env_ou' => array(
        'label'   => _txt('fd.ou'),
        'default' => 'ENV_OIS_OU'
      ),
      'env_telephone_number' => array(
        'label'   => _txt('fd.telephone_number.number') . " (" . _txt('en.telephone_number.type', null, ContactEnum::Office) . ")",
        'default' => 'ENV_OIS_TELEPHONE'
      ),
      'env_address_street' => array(
        'label'   => _txt('fd.address.street') . " (" . _txt('en.address.type', null, ContactEnum::Office) . ")",
        'default' => 'ENV_OIS_STREET'
      ),
      'env_address_locality' => array(
        'label'   => _txt('fd.address.locality') . " (" . _txt('en.address.type', null, ContactEnum::Office) . ")",
        'default' => 'ENV_OIS_LOCALITY'
      ),
      'env_address_state' => array(
        'label'   => _txt('fd.address.state') . " (" . _txt('en.address.type', null, ContactEnum::Office) . ")",
        'default' => 'ENV_OIS_STATE'
      ),
      'env_address_postalcode' => array(
        'label'   => _txt('fd.address.postal_code') . " (" . _txt('en.address.type', null, ContactEnum::Office) . ")",
        'default' => 'ENV_OIS_POSTALCODE'
      ),
      'env_address_country' => array(
        'label'   => _txt('fd.address.country') . " (" . _txt('en.address.type', null, ContactEnum::Office) . ")",
        'default' => 'ENV_OIS_COUNTRY'
      )
    );
    
    return $attributes;
  }
  
  /**
   * Expose menu items.
   * 
   * @ since COmanage Registry v3.1.0
   * @ return Array with menu location type as key and array of labels, controllers, actions as values.
   */
  
  public function cmPluginMenus() {
    return array();
  }
}
