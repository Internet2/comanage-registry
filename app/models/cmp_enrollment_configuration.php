<?php
  /*
   * COmanage Registry CMP Enrollment Configuration Model
   *
   * Version: $Revision$
   * Date: $Date$
   *
   * Copyright (C) 2011 University Corporation for Advanced Internet Development, Inc.
   * 
   * Licensed under the Apache License, Version 2.0 (the "License"); you may not use this file except in compliance with
   * the License. You may obtain a copy of the License at
   * 
   * http://www.apache.org/licenses/LICENSE-2.0
   * 
   * Unless required by applicable law or agreed to in writing, software distributed under
   * the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY
   * KIND, either express or implied. See the License for the specific language governing
   * permissions and limitations under the License.
   * 
   */

  class CmpEnrollmentConfiguration extends AppModel {
    // Define class name for cake
    var $name = "CmpEnrollmentConfiguration";
    
    // Add behaviors
    var $actsAs = array('Containable');
    
    // Association rules from this model to other models
    var $hasMany = array("CmpEnrollmentAttribute" =>   // A CMP Enrollment Configuration has many CMP Enrollment Attributes
                         array('dependent' => true));
    
    // Default display field for cake generated views
    var $displayField = "name";
    
    // Default ordering for find operations
    var $order = array("name");
    
    // Validation rules for table elements
    var $validate = array(
      'name' => array(
        'rule' => 'notEmpty',
        'required' => true,
        'message' => 'A name must be provided'
      ),
      'self_enroll' => array(
        'rule' => array('boolean')
      ),
      'self_require_authn' => array(
        'rule' => array('boolean')
      ),
      'admin_enroll' => array(
        'rule' => array('inList', array(AdministratorEnum::NoAdmin,
                                        AdministratorEnum::CoOrCouAdmin,
                                        AdministratorEnum::CoAdmin))
      ),
      'admin_confirm_email' => array(
        'rule' => array('boolean')
      ),
      'admin_require_authn' => array(
        'rule' => array('boolean')
      ),
      'attrs_from_ldap' => array(
        'rule' => array('boolean')
      ),
      'attrs_from_saml' => array(
        'rule' => array('boolean')
      ),
      'status' => array(
        'rule' => array('inList', array(StatusEnum::Active,
                                        StatusEnum::Suspended))
      )
    );
    
    function findDefault()
    {
      // Find the default (ie: active) CMP Enrollment Configuration for this platform.
      //
      // Parameters:
      //   None
      //
      // Preconditions:
      // (1) Initial setup (performed by select()) has been completed
      //
      // Postconditions:
      //     None
      //
      // Returns:
      // - Array of the form returned by find()
      
      return($this->find('first',
                         array('conditions' =>
                               array('CmpEnrollmentConfiguration.name' => 'CMP Enrollment Configuration',
                                     'CmpEnrollmentConfiguration.status' => StatusEnum::Active))));
    }
    
    function getStandardAttributeOrder($model=null)
    {
      // Obtain the standard order for rendering lists of attributes.
      //
      // Parameters:
      // - model: Calling model, used to determine associations (needed for SaveAll on data)
      //
      // Preconditions:
      //     None
      //
      // Postconditions:
      //     None
      //
      // Returns:
      // - Array of arrays, each of which defines 'attr', 'type', and 'label'.
      
      global $cm_lang, $cm_texts;
      
      // This is a function rather than a var so _txt evaluates.
      // The attributes in this list need to be kept in sync with the controller (select()).
      
      if(isset($model))
      {
        // Determine association types so appropriate form elements can be rendered
        $address_assoc = (isset($model->hasOne['Address'])
                          ? 'hasone'
                          : (isset($model->hasMany['Address']) ? 'hasmany' : null));
        $email_assoc = (isset($model->hasOne['EmailAddress'])
                        ? 'hasone'
                        : (isset($model->hasMany['EmailAddress']) ? 'hasmany' : null));
        $id_assoc = (isset($model->hasOne['Identifier'])
                     ? 'hasone'
                     : (isset($model->hasMany['Identifier']) ? 'hasmany' : null));
        $name_assoc = (isset($model->hasOne['Name'])
                       ? 'hasone'
                       : (isset($model->hasMany['Name']) ? 'hasmany' : null));
        $phone_assoc = (isset($model->hasOne['TelephoneNumber'])
                        ? 'hasone'
                        : (isset($model->hasMany['TelephoneNumber']) ? 'hasmany' : null));
      }
      else
      {
        $address_assoc = null;
        $email_assoc = null;
        $id_assoc = null;
        $name_assoc = null;
        $phone_assoc = null;
      }
      
      return(array(
        array('attr' => 'names:honorific',
              'type' => NameEnum::Official,
              'label' => _txt('fd.name.h'),
              'desc' => _txt('fd.name.h.desc'),
              'assoc' => $name_assoc),
        array('attr' => 'names:given',
              'type' => NameEnum::Official,
              'label' => _txt('fd.name.g'),
              'assoc' => $name_assoc),
        array('attr' => 'names:middle',
              'type' => NameEnum::Official,
              'label' => _txt('fd.name.m'),
              'assoc' => $name_assoc),
        array('attr' => 'names:family',
              'type' => NameEnum::Official,
              'label' => _txt('fd.name.f'),
              'assoc' => $name_assoc),
        array('attr' => 'names:suffix',
              'type' => NameEnum::Official,
              'label' => _txt('fd.name.s'),
              'desc' => _txt('fd.name.s.desc'),
              'assoc' => $name_assoc),
        array('attr' => 'affiliation',
              'type' => null,
              'label' => _txt('fd.affiliation'),
              'select' => array('options' => $cm_texts[ $cm_lang ]['en.affil'],
                                'default' => 'member')),
        array('attr' => 'title',
              'type' => null,
              'label' => _txt('fd.title')),
        array('attr' => 'o',
              'type' => null,
              'label' => _txt('fd.o')),
        array('attr' => 'ou',
              'type' => null,
              'label' => _txt('fd.ou')),
        array('attr' => 'identifiers:identifier',
              'type' => IdentifierEnum::ePPN,
              'label' => _txt('en.identifier', null, IdentifierEnum::ePPN),
              'assoc' => $id_assoc),
        array('attr' => 'email_addresses:mail',
              'type' => ContactEnum::Office,
              'label' => _txt('fd.mail'),
              'assoc' => $email_assoc),
        array('attr' => 'telephone_numbers:number',
              'type' => ContactEnum::Office,
              'label' => _txt('fd.phone'),
              'assoc' => $phone_assoc),
        array('attr' => 'addresses:line1',
              'type' => ContactEnum::Office,
              'label' => _txt('fd.address.1'),
              'assoc' => $address_assoc),
        array('attr' => 'addresses:line2',
              'type' => ContactEnum::Office,
              'label' => _txt('fd.address.2'),
              'assoc' => $address_assoc),
        array('attr' => 'addresses:locality',
              'type' => ContactEnum::Office,
              'label' => _txt('fd.city'),
              'assoc' => $address_assoc),
        array('attr' => 'addresses:state',
              'type' => ContactEnum::Office,
              'label' => _txt('fd.state'),
              'assoc' => $address_assoc),
        array('attr' => 'addresses:postal_code',
              'type' => ContactEnum::Office,
              'label' => _txt('fd.postal'),
              'assoc' => $address_assoc),
        array('attr' => 'addresses:country',
              'type' => ContactEnum::Office,
              'label' => _txt('fd.country'),
              'assoc' => $address_assoc)
      ));
    }
    
    function orgIdentitiesPooled()
    {
      // Determine if organizational identities are pooled in the default
      // (ie: active) CMP Enrollment Configuration for this platform.
      //
      // Parameters:
      //   None
      //
      // Preconditions:
      // (1) Initial setup (performed by select()) has been completed
      //
      // Postconditions:
      //     None
      //
      // Returns:
      // - True if org identities are pooled, false otherwise.
      
      $r = $this->find('first',
                       array('conditions' =>
                             array('CmpEnrollmentConfiguration.name' => 'CMP Enrollment Configuration',
                                   'CmpEnrollmentConfiguration.status' => StatusEnum::Active),
                             // We don't need to pull attributes, just the configuration
                             'contain' => false,
                             'fields' =>
                             array('CmpEnrollmentConfiguration.pool_org_identities')));
      
      return($r['CmpEnrollmentConfiguration']['pool_org_identities']);
    }
  }
?>