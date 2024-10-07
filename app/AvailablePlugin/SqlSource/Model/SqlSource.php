<?php
/**
 * COmanage Registry SQL OrgIdentitySource Model
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
 * @link          https://www.internet2.edu/comanage COmanage Project
 * @package       registry-plugin
 * @since         COmanage Registry v4.1.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

class SqlSource extends AppModel {
  // Required by COmanage Plugins
  public $cmPluginType = "orgidsource";
  
  // Document foreign keys
  public $cmPluginHasMany = array();
  
  // Request SQL servers
  public $cmServerType = ServerEnum::SqlServer;
  
  // Association rules from this model to other models
  public $belongsTo = array(
    "OrgIdentitySource",
    "Server"
  );
  
  public $hasMany = array();
  
  // Default display field for cake generated views
  public $displayField = "source_table";
  
  public $actsAs = array('Containable');
  
  // Validation rules for table elements
  public $validate = array(
    'org_identity_source_id' => array(
      'rule' => 'numeric',
      'required' => true,
      'allowEmpty' => false
    ),
    'table_mode' => array(
      'rule' => array(
        'inList',
        array(
          SqlSourceTableModeEnum::Flat,
          SqlSourceTableModeEnum::Relational
        )
      ),
      'required' => true,
      'allowEmpty' => false
    ),
    'source_table' => array(
      // We need to constrain the table name here not just for SQL conformance
      // but because SqlSourceBackend will construct raw SQL queries using the
      // source_table name.
// Commit with PMO 1156
      'rule' => '/^[a-zA-Z0-9_\-\.]+$/',
      'required' => true,
      'allowEmpty' => false,
      'message' => 'Source Table Name must consist only of alphanumeric characters, dots, dashes, and underscores'
    ),
    'server_id' => array(
      'content' => array(
        'rule' => 'notBlank',
        'required' => true,
        'allowEmpty' => false,
        'unfreeze' => 'CO'
      )
    ),
    // The various type fields are only required if table_mode is Flat
    'address_type' => array(
      'content' => array(
        'rule' => array('validateExtendedType',
                        array('attribute' => 'Address.type',
                              'default' => array(ContactEnum::Campus,
                                                 ContactEnum::Home,
                                                 ContactEnum::Office,
                                                 ContactEnum::Postal))),
        'required' => false,
        'allowEmpty' => true
      )
    ),
    'email_address_type' => array(
      'content' => array(
        'rule' => array('validateExtendedType',
                        array('attribute' => 'EmailAddress.type',
                              'default' => array(EmailAddressEnum::Delivery,
                                                 EmailAddressEnum::Forwarding,
                                                 EmailAddressEnum::MailingList,
                                                 EmailAddressEnum::Official,
                                                 EmailAddressEnum::Personal,
                                                 EmailAddressEnum::Preferred,
                                                 EmailAddressEnum::Recovery))),
        'required' => false,
        'allowEmpty' => true
      )
    ),
    'identifier_type' => array(
      'content' => array(
        'rule' => array('validateExtendedType',
                        array('attribute' => 'Identifier.type',
                              'default' => array(IdentifierEnum::AffiliateSOR,
                                                 IdentifierEnum::Badge,
                                                 IdentifierEnum::Enterprise,
                                                 IdentifierEnum::ePPN,
                                                 IdentifierEnum::ePTID,
                                                 IdentifierEnum::ePUID,
                                                 IdentifierEnum::GuestSOR,
                                                 IdentifierEnum::HRSOR,
                                                 IdentifierEnum::Mail,
                                                 IdentifierEnum::National,
                                                 IdentifierEnum::Network,
                                                 IdentifierEnum::OIDCsub,
                                                 IdentifierEnum::OpenID,
                                                 IdentifierEnum::ORCID,
                                                 IdentifierEnum::ProvisioningTarget,
                                                 IdentifierEnum::Reference,
                                                 IdentifierEnum::SamlPairwise,
                                                 IdentifierEnum::SamlSubject,
                                                 IdentifierEnum::SORID,
                                                 IdentifierEnum::StudentSOR,
                                                 IdentifierEnum::UID))),
        'required' => false,
        'allowEmpty' => true
      )
    ),
    'name_type' => array(
      'content' => array(
        'rule' => array('validateExtendedType',
                        array('attribute' => 'Name.type',
                              'default' => array(NameEnum::Alternate,
                                                 NameEnum::Author,
                                                 NameEnum::FKA,
                                                 NameEnum::Official,
                                                 NameEnum::Preferred))),
        'required' => false,
        'allowEmpty' => true
      )
    ),
    'telephone_number_type' => array(
      'content' => array(
        'rule' => array('validateExtendedType',
                        array('attribute' => 'TelephoneNumber.type',
                              'default' => array(ContactEnum::Campus,
                                                 ContactEnum::Fax,
                                                 ContactEnum::Home,
                                                 ContactEnum::Mobile,
                                                 ContactEnum::Office))),
        'required' => false,
        'allowEmpty' => true
      )
    ),
    'url_type' => array(
      'content' => array(
        'rule' => array('validateExtendedType',
                        array('attribute' => 'Url.type',
                              'default' => array(UrlEnum::Official,
                                                 UrlEnum::Personal))),
        'required' => false,
        'allowEmpty' => true
      )
    )
  );
  
  /**
   * Expose menu items.
   * 
   * @since COmanage Registry v4.1.0
   * @return Array with menu location type as key and array of labels, controllers, actions as values.
   */
  
  public function cmPluginMenus() {
    return array();
  }
}
