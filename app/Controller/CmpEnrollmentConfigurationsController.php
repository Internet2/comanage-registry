<?php
/**
 * COmanage Registry CMP Enrollment Configuration Controller
 *
 * Copyright (C) 2011-12 University Corporation for Advanced Internet Development, Inc.
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
 * @copyright     Copyright (C) 2011-12 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.3
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */

App::uses("StandardController", "Controller");
  
class CmpEnrollmentConfigurationsController extends StandardController {
  // Class name, used by Cake
  public $name = "CmpEnrollmentConfigurations";
  
  // When using additional controllers, we must also specify our own
  public $uses = array('CmpEnrollmentConfiguration', 'OrgIdentity');
  
  // Establish pagination parameters for HTML views
  public $paginate = array(
    'limit' => 25,
    'order' => array(
      'CmpEnrollmentConfiguration.name' => 'asc'
    )
  );
  
  /**
   * Callback after controller methods are invoked but before views are rendered.
   * - precondition: Request Handler component has set $this->request
   * - postcondition: Set $cmp_ef_attribute_order
   *
   * @since  COmanage Registry v0.3
   */
  
  function beforeRender() {
    // Set the list of attribute order for the view to render
    
    $this->set('cmp_ef_attribute_order', $this->CmpEnrollmentConfiguration->getStandardAttributeOrder());
    parent::beforeRender();
  }
  
  /**
   * Perform any dependency checks required prior to a write (add/edit) operation.
   * - postcondition: Session flash message updated (HTML) or HTTP status returned (REST)
   *
   * @since  COmanage Registry v0.3
   * @param  Array Request data
   * @param  Array Current data
   * @return boolean true if dependency checks succeed, false otherwise.
   *
   */
  
  function checkWriteFollowups($reqdata, $curdata = null) {
    if($this->action == 'edit')
    {
      // Check to see if the pool org identities setting has been changed, and
      // if so perform the appropriate updates. At the moment, we only do this
      // on edit and not add since when we add the one and only CMP enrollment
      // config there are no existing org identities.
      
      if(isset($curdata)
         && ($curdata['CmpEnrollmentConfiguration']['pool_org_identities']
             != $reqdata['CmpEnrollmentConfiguration']['pool_org_identities']))
      {
        if($reqdata['CmpEnrollmentConfiguration']['pool_org_identities'])
        {
          // Enable pooling
          
          if(!$this->OrgIdentity->pool())
          {
            $this->Session->setFlash(_txt('er.orgp.pool'), '', array(), 'info');
            return(false);
          }
        }
        else
        {
          // Disable pooling
          
          if(!$this->OrgIdentity->unpool())
          {
            $this->Session->setFlash(_txt('er.orgp.unpool'), '', array(), 'info');
            return(false);
          }
        }
      }
    }
    
    return(true);
  }
  
  /**
   * Authorization for this Controller, called by Auth component
   * - precondition: Session.Auth holds data used for authz decisions
   * - postcondition: $permissions set with calculated permissions
   *
   * @since  COmanage Registry v0.3
   * @return Array Permissions
   */
  
  function isAuthorized() {
    $cmr = $this->calculateCMRoles();
    
    // Construct the permission set for this user, which will also be passed to the view.
    $p = array();
    
    // Determine what operations this user can perform
    
    // Currently, there is only one CMP Enrollment Configuration per platform.
    // As such, most permissions are denied, even for CMP admins.
    // There is no view-only option, so that is set to false, too.
    
    // Add a new CMP Enrollment Configuration?
    $p['add'] = false;
    
    // Delete an existing CMP Enrollment Configuration?
    $p['delete'] = false;
    
    // Edit an existing CMP Enrollment Configuration?
    $p['edit'] = $cmr['cmadmin'];
    
    // View all existing CMP Enrollment Configurations?
    $p['index'] = false;
    
    // Select a CMP Enrollment Configuration?
    $p['select'] = $cmr['admin'];
    
    // View an existing CMP Enrollment Configuration?
    $p['view'] = false;

    $this->set('permissions', $p);
    return($p[$this->action]);
  }
  
  /**
   * Select a CMP Enrollment Configuration to operate over.
   * - postcondition: If no CMP Enrollment Configuration exists, one is created
   * - postcondition: Default CMP Enrollment Attributes are created or updated
   * - postcondition: A redirect is issued to the CMP Enrollment Configuration
   *
   * @since  COmanage Registry v0.3
   */
  
  function select() {
    $fid = -1;
    
    // We currently only allow one CMP enrollment configuration per platform.
    // See if there is one, if not create it. Then redirect to edit.
    
    $ef = $this->CmpEnrollmentConfiguration->findDefault();
    
    if(empty($ef))
    {
      // Not found, create it
      
      $ef['CmpEnrollmentConfiguration'] = array(
        'name' => 'CMP Enrollment Configuration',
        'self_enroll' => false,
        'self_require_authn' => false,
        'admin_enroll' => AdministratorEnum::CoAdmin,
        'admin_confirm_email' => true,
        'admin_require_authn' => false,
        'attrs_from_ldap' => false,
        'attrs_from_saml' => false,
        'status' => StatusEnum::Active
      );
      
      if($this->CmpEnrollmentConfiguration->save($ef))
      {
        $fid = $this->CmpEnrollmentConfiguration->id;
      }
      else
      {
        $this->Session->setFlash(_txt('er.efcf.init'), '', array(), 'error');
        $this->redirect(array('controller' => 'pages', 'action' => 'menu'));
        return;
      }
    }
    else
    {
      $fid = $ef['CmpEnrollmentConfiguration']['id'];
    }
    
    // Check for default CMP Enrollment Configuration Attributes. This may or may not be
    // the ideal place to do this.
    
    function defined_attribute($attrs, $attr, $type=null)
    {
      // A local helper function to determine if $attr is already defined in $attrs
      
      foreach(array_keys($attrs) as $k)
      {
        if($attrs[$k]['CmpEnrollmentAttribute']['attribute'] == $attr)
        {
          if(!defined($type)
             || (defined($attrs[$k]['CmpEnrollmentAttribute']['type'])
                 && $attrs[$k]['CmpEnrollmentAttribute']['type'] == $type))
            return(true);
        }
      }
      
      return(false);
    }
    
    // It'd be nice to used find('list'), but we don't have a unique key other than 'id'.
    // (There can be multiple rows with the same 'attribute' but different 'type'.)
    // The attributes in this list need to be kept in sync with the model (getStandardAttributeOrder).
    
    $attrs = $this->CmpEnrollmentConfiguration->CmpEnrollmentAttribute->findAllByCmpEnrollmentConfigurationId($fid);
    
    $newattrs = array();
    
    if(!defined_attribute($attrs, 'names:honorific', NameEnum::Official))
    {
      $newattrs[]['CmpEnrollmentAttribute'] = array(
        'cmp_enrollment_configuration_id' => $fid,
        'attribute'                       => 'names:honorific',
        'type'                            => NameEnum::Official,
        'required'                        => RequiredEnum::Optional
      );
    }
    
    if(!defined_attribute($attrs, 'names:given', NameEnum::Official))
    {
      $newattrs[]['CmpEnrollmentAttribute'] = array(
        'cmp_enrollment_configuration_id' => $fid,
        'attribute'                       => 'names:given',
        'type'                            => NameEnum::Official,
        'required'                        => RequiredEnum::Required,
        'ldap_name'                       => 'givenName',
        'saml_name'                       => 'givenName'
      );
    }
    
    if(!defined_attribute($attrs, 'names:middle', NameEnum::Official))
    {
      $newattrs[]['CmpEnrollmentAttribute'] = array(
        'cmp_enrollment_configuration_id' => $fid,
        'attribute'                       => 'names:middle',
        'type'                            => NameEnum::Official,
        'required'                        => RequiredEnum::Optional
      );
    }
    
    if(!defined_attribute($attrs, 'names:family', NameEnum::Official))
    {
      $newattrs[]['CmpEnrollmentAttribute'] = array(
        'cmp_enrollment_configuration_id' => $fid,
        'attribute'                       => 'names:family',
        'type'                            => NameEnum::Official,
        'required'                        => RequiredEnum::Optional,
        'ldap_name'                       => 'sn',
        'saml_name'                       => 'sn'
      );
    }
    
    if(!defined_attribute($attrs, 'names:suffix', NameEnum::Official))
    {
      $newattrs[]['CmpEnrollmentAttribute'] = array(
        'cmp_enrollment_configuration_id' => $fid,
        'attribute'                       => 'names:suffix',
        'type'                            => NameEnum::Official,
        'required'                        => RequiredEnum::Optional
      );
    }
    
    if(!defined_attribute($attrs, 'affiliation'))
    {
      $newattrs[]['CmpEnrollmentAttribute'] = array(
        'cmp_enrollment_configuration_id' => $fid,
        'attribute'                       => 'affiliation',
        'required'                        => RequiredEnum::Optional,
        'ldap_name'                       => 'edu_person_affiliation',
        'saml_name'                       => 'edu_person_affiliation'
      );
    }
    
    if(!defined_attribute($attrs, 'title'))
    {
      $newattrs[]['CmpEnrollmentAttribute'] = array(
        'cmp_enrollment_configuration_id' => $fid,
        'attribute'                       => 'title',
        'required'                        => RequiredEnum::Optional,
        'ldap_name'                       => 'title',
        'saml_name'                       => 'title'
      );
    }
    
    if(!defined_attribute($attrs, 'o'))
    {
      $newattrs[]['CmpEnrollmentAttribute'] = array(
        'cmp_enrollment_configuration_id' => $fid,
        'attribute'                       => 'o',
        'required'                        => RequiredEnum::Optional,
        'ldap_name'                       => 'o',
        'saml_name'                       => 'o'
      );
    }
    
    if(!defined_attribute($attrs, 'ou'))
    {
      $newattrs[]['CmpEnrollmentAttribute'] = array(
        'cmp_enrollment_configuration_id' => $fid,
        'attribute'                       => 'ou',
        'required'                        => RequiredEnum::Optional,
        'ldap_name'                       => 'ou',
        'saml_name'                       => 'ou'
      );
    }

    if(!defined_attribute($attrs, 'identifiers:identifier', IdentifierEnum::ePPN))
    {
      $newattrs[]['CmpEnrollmentAttribute'] = array(
        'cmp_enrollment_configuration_id' => $fid,
        'attribute'                       => 'identifiers:identifier',
        'type'                            => IdentifierEnum::ePPN,
        'required'                        => RequiredEnum::Required,
        'ldap_name'                       => 'eduPersonPrincipalName',
        'saml_name'                       => 'eduPersonPrincipalName'
      );
    }

    if(!defined_attribute($attrs, 'email_addresses:mail', ContactEnum::Office))
    {
      $newattrs[]['CmpEnrollmentAttribute'] = array(
        'cmp_enrollment_configuration_id' => $fid,
        'attribute'                       => 'email_addresses:mail',
        'type'                            => ContactEnum::Office,
        'required'                        => RequiredEnum::Required,
        'ldap_name'                       => 'mail',
        'saml_name'                       => 'mail'
      );
    }
    
    if(!defined_attribute($attrs, 'telephone_numbers:number', ContactEnum::Office))
    {
      $newattrs[]['CmpEnrollmentAttribute'] = array(
        'cmp_enrollment_configuration_id' => $fid,
        'attribute'                       => 'telephone_numbers:number',
        'type'                            => ContactEnum::Office,
        'required'                        => RequiredEnum::Optional,
        'ldap_name'                       => 'telephoneNumber',
        'saml_name'                       => 'telephoneNumber'
      );
    }
    
    if(!defined_attribute($attrs, 'addresses:line1', ContactEnum::Office))
    {
      $newattrs[]['CmpEnrollmentAttribute'] = array(
        'cmp_enrollment_configuration_id' => $fid,
        'attribute'                       => 'addresses:line1',
        'type'                            => ContactEnum::Office,
        'required'                        => RequiredEnum::Optional,
        'ldap_name'                       => 'street',
        'saml_name'                       => 'street'
      );
    }
    
    if(!defined_attribute($attrs, 'addresses:line2', ContactEnum::Office))
    {
      $newattrs[]['CmpEnrollmentAttribute'] = array(
        'cmp_enrollment_configuration_id' => $fid,
        'attribute'                       => 'addresses:line2',
        'type'                            => ContactEnum::Office,
        'required'                        => RequiredEnum::Optional
      );
    }
    
    if(!defined_attribute($attrs, 'addresses:locality', ContactEnum::Office))
    {
      $newattrs[]['CmpEnrollmentAttribute'] = array(
        'cmp_enrollment_configuration_id' => $fid,
        'attribute'                       => 'addresses:locality',
        'type'                            => ContactEnum::Office,
        'required'                        => RequiredEnum::Optional,
        'ldap_name'                       => 'l',
        'saml_name'                       => 'l'
      );
    }
    
    if(!defined_attribute($attrs, 'addresses:state', ContactEnum::Office))
    {
      $newattrs[]['CmpEnrollmentAttribute'] = array(
        'cmp_enrollment_configuration_id' => $fid,
        'attribute'                       => 'addresses:state',
        'type'                            => ContactEnum::Office,
        'required'                        => RequiredEnum::Optional,
        'ldap_name'                       => 'st',
        'saml_name'                       => 'st'
      );
    }
    
    if(!defined_attribute($attrs, 'addresses:postal_code', ContactEnum::Office))
    {
      $newattrs[]['CmpEnrollmentAttribute'] = array(
        'cmp_enrollment_configuration_id' => $fid,
        'attribute'                       => 'addresses:postal_code',
        'type'                            => ContactEnum::Office,
        'required'                        => RequiredEnum::Optional,
        'ldap_name'                       => 'postalCode',
        'saml_name'                       => 'postalCode'
      );
    }
    
    if(!defined_attribute($attrs, 'addresses:country', ContactEnum::Office))
    {
      $newattrs[]['CmpEnrollmentAttribute'] = array(
        'cmp_enrollment_configuration_id' => $fid,
        'attribute'                       => 'addresses:country',
        'type'                            => ContactEnum::Office,
        'required'                        => RequiredEnum::Optional,
        'ldap_name'                       => 'c'
      );
    }
    
    if(!empty($newattrs))
    {
      $this->CmpEnrollmentConfiguration->CmpEnrollmentAttribute->SaveAll($newattrs);
    }
    
    // Redirect to the configuration edit page
    
    $this->redirect(array('controller' => 'cmp_enrollment_configurations',
                          'action' => 'edit',
                          $fid));
  }
}
