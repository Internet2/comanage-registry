<?php
/**
 * COmanage Registry CMP Enrollment Attribute Model
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
 * @since         COmanage Registry v0.3
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

class CmpEnrollmentAttribute extends AppModel {
  // Define class name for cake
  public $name = "CmpEnrollmentAttribute";
  
  // Current schema version for API
  public $version = "1.0";
  
  // Association rules from this model to other models
  public $belongsTo = array("CmpEnrollmentConfiguration");     // A CMP Enrollment Attribute is part of a CMP Enrollment Configuration
    
  // Default display field for cake generated views
  public $displayField = "attribute";
  
  // Default ordering for find operations
  public $order = array("attribute");
  
  // Validation rules for table elements
  public $validate = array(
    'attribute' => array(
      'rule' => 'notBlank',
      'required' => true,
      'message' => 'An attribute must be provided'
    ),
    'type' => array(
      'rule' => '/.*/',
      'required' => false,
      'allowEmpty' => true
    ),
    'required' => array(
      'rule' => array('range', -2, 2)
    ),
    'ldap_name' => array(
      'rule' => '/.*/',
      'required' => false,
      'allowEmpty' => true
    ),
    'saml_name' => array(
      'rule' => '/.*/',
      'required' => false,
      'allowEmpty' => true
    ),
  );

  /**
   * Obtain the list of attributes available for loading into an Org Identity.
   *
   * @since  COmanage Registry v0.8.2
   * @return Array Array of available attributes
   */
  
  public function availableAttributes() {
    // Attributes should be listed in the order they are to be rendered in.
    // The various _name fields are default values that can be overridden.
    // 'required' applies when Enable Attributes Via CO Enrollment Flow is false.
    // Attribute types are forced to Official since they come from an "official" source.
    
    $attributes = array(
      'names:honorific' => array(
        'type'      => NameEnum::Official,
        'required'  => RequiredEnum::Optional,
        'label'     => _txt('fd.name.honorific') . " (" . _txt('en.name.type', null, NameEnum::Official) . ")",
        'desc'      => _txt('fd.name.h.desc'),
        'env_name'  => '',
        'ldap_name' => '',
        'saml_name' => ''
      ),
      'names:given' => array(
        'type'      => NameEnum::Official,
        'required'  => RequiredEnum::Required,
        'label'     => _txt('fd.name.given') . " (" . _txt('en.name.type', null, NameEnum::Official) . ")",
        'env_name'  => 'CMP_EF_GIVENNAME',
        'ldap_name' => 'givenName',
        'saml_name' => 'givenName'
      ),
      'names:middle' => array(
        'type'      => NameEnum::Official,
        'required'  => RequiredEnum::Optional,
        'label'     => _txt('fd.name.middle') . " (" . _txt('en.name.type', null, NameEnum::Official) . ")",
        'env_name'  => '',
        'ldap_name' => '',
        'saml_name' => ''
      ),
      'names:family' => array(
        'type'      => NameEnum::Official,
        'required'  => RequiredEnum::Optional,
        'label'     => _txt('fd.name.family') . " (" . _txt('en.name.type', null, NameEnum::Official) . ")",
        'env_name'  => 'CMP_EF_SN',
        'ldap_name' => 'sn',
        'saml_name' => 'sn'
      ),
      'names:suffix' => array(
        'type'      => NameEnum::Official,
        'required'  => RequiredEnum::Optional,
        'label'     => _txt('fd.name.suffix') . " (" . _txt('en.name.type', null, NameEnum::Official) . ")",
        'desc'      => _txt('fd.name.s.desc'),
        'env_name'  => '',
        'ldap_name' => '',
        'saml_name' => ''
      ),
      'affiliation' => array(
        'required'  => RequiredEnum::Optional,
        'label'     => _txt('fd.affiliation'),
        'env_name'  => 'CMP_EF_AFFILIATION',
        'ldap_name' => 'edu_person_affiliation',
        'saml_name' => 'edu_person_affiliation'
      ),
      'title' => array(
        'required'  => RequiredEnum::Optional,
        'label'     => _txt('fd.title'),
        'env_name'  => 'CMP_EF_TITLE',
        'ldap_name' => 'title',
        'saml_name' => 'title'
      ),
      'o' => array(
        'required'  => RequiredEnum::Optional,
        'label'     => _txt('fd.o'),
        'env_name'  => 'CMP_EF_O',
        'ldap_name' => 'o',
        'saml_name' => 'o'
      ),
      'ou' => array(
        'required'  => RequiredEnum::Optional,
        'label'     => _txt('fd.ou'),
        'env_name'  => 'CMP_EF_OU',
        'ldap_name' => 'ou',
        'saml_name' => 'ou'
      ),
      'email_addresses:mail' => array(
        'type'      => EmailAddressEnum::Official,
        'required'  => RequiredEnum::Required,
        'label'     => _txt('fd.email_address.mail') . " (" . _txt('en.email_address.type', null, EmailAddressEnum::Official) . ")",
        'env_name'  => 'CMP_EF_MAIL',
        'ldap_name' => 'mail',
        'saml_name' => 'mail'
      ),
      'telephone_numbers:number' => array(
        'type'      => ContactEnum::Office,
        'required'  => RequiredEnum::Optional,
        'label'     => _txt('fd.telephone_number.number') . " (" . _txt('en.telephone_number.type', null, ContactEnum::Office) . ")",
        'env_name'  => 'CMP_EF_TELEPHONENUMBER',
        'ldap_name' => 'telephoneNumber',
        'saml_name' => 'telephoneNumber'
      ),
      'addresses:street' => array(
        'type'      => ContactEnum::Office,
        'required'  => RequiredEnum::Optional,
        'label'     => _txt('fd.address.street') . " (" . _txt('en.address.type', null, ContactEnum::Office) . ")",
        'env_name'  => 'CMP_EF_STREET',
        'ldap_name' => 'street',
        'saml_name' => 'street'
      ),
      'addresses:locality' => array(
        'type'      => ContactEnum::Office,
        'required'  => RequiredEnum::Optional,
        'label'     => _txt('fd.address.locality') . " (" . _txt('en.address.type', null, ContactEnum::Office) . ")",
        'env_name'  => 'CMP_EF_L',
        'ldap_name' => 'l',
        'saml_name' => 'l'
      ),
      'addresses:state' => array(
        'type'      => ContactEnum::Office,
        'required'  => RequiredEnum::Optional,
        'label'     => _txt('fd.address.state') . " (" . _txt('en.address.type', null, ContactEnum::Office) . ")",
        'env_name'  => 'CMP_EF_ST',
        'ldap_name' => 'st',
        'saml_name' => 'st'
      ),
      'addresses:postal_code' => array(
        'type'      => ContactEnum::Office,
        'required'  => RequiredEnum::Optional,
        'label'     => _txt('fd.address.postal_code') . " (" . _txt('en.address.type', null, ContactEnum::Office) . ")",
        'env_name'  => 'CMP_EF_POSTALCODE',
        'ldap_name' => 'postalCode',
        'saml_name' => 'postalCode'
      ),
      'addresses:country' => array(
        'type'      => ContactEnum::Office,
        'label'     => _txt('fd.address.country') . " (" . _txt('en.address.type', null, ContactEnum::Office) . ")",
        'env_name'  => 'CMP_EF_C',
        'required'  => RequiredEnum::Optional,
        'ldap_name' => 'c',
        'saml_name' => ''
      )
    );
    
    return $attributes;
  }
}
