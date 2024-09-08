<?php
/**
 * COmanage Registry Organizations Controller
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
 * @since         COmanage Registry v4.0.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::uses("StandardController", "Controller");

class OrganizationsController extends StandardController {
  // Class name, used by Cake
  public $name = "Organizations";
  
  // Establish pagination parameters for HTML views
  public $paginate = array(
    'limit' => 25,
    'order' => array(
      'Organization.name' => 'asc'
    )
  );
  
  // This controller needs a CO to be set
  public $requires_co = true;
  
  public $delete_contains = array(
  );

  public $edit_contains = array(
    'Address',
    'AdHocAttribute',
    'Contact',
    'EmailAddress',
    'Identifier',
    'OrganizationSourceRecord' => array('OrganizationSource'),
    'TelephoneNumber',
    'Url'
  );
  
  public $view_contains = array(
    'Address',
    'AdHocAttribute',
    'Contact',
    'EmailAddress',
    'Identifier',
    'OrganizationSourceRecord' => array('OrganizationSource'),
    'TelephoneNumber',
    'Url'
  );
  
  /**
   * Search Block fields configuration
   *
   * @since  COmanage Registry v4.4.0
   */

  public function searchConfig($action) {
    if($action == 'index') { 
      return array(
        'search.name' => array(
          'type'    => 'text',
          'label'   => _txt('fd.name')
        ),
        'search.orgSource' => array(
          'label' => _txt('ct.organization_sources.1'),
          'type' => 'select',
          'empty'   => _txt('op.select.all'),
          'options' => $this->viewVars['vv_org_sources'],
        )
      );
    }
  }
  
  /**
   * Callback after controller methods are invoked but before views are rendered.
   *
   * @since  COmanage Registry v4.0.0
   */

  function beforeRender() {
    if(!$this->request->is('restful')) {
      $types = $this->Organization->types($this->cur_co['Co']['id'], 'type');
      $this->set('vv_available_types', $types);
      
      // Mappings for extended types
      $this->set('vv_addresses_types', $this->Organization->Address->types($this->cur_co['Co']['id'], 'type'));
      $this->set('vv_email_addresses_types', $this->Organization->EmailAddress->types($this->cur_co['Co']['id'], 'type'));
      $this->set('vv_identifiers_types', $this->Organization->Identifier->types($this->cur_co['Co']['id'], 'type'));
      $this->set('vv_telephone_numbers_types', $this->Organization->TelephoneNumber->types($this->cur_co['Co']['id'], 'type'));
      $this->set('vv_urls_types', $this->Organization->Url->types($this->cur_co['Co']['id'], 'type'));

      // Available Organization Sources, for filtering
      $args = array();
      $args['conditions']['OrganizationSource.status'] = SuspendableStatusEnum::Active;
      $args['conditions']['OrganizationSource.co_id'] = $this->cur_co['Co']['id'];
      $args['fields'] = array('id', 'description');
      $args['contain'] = false;

      $this->set('vv_org_sources', $this->Organization->OrganizationSourceRecord->OrganizationSource->find('list', $args));
    }

    parent::beforeRender();
  }

  /**
   * Determine the conditions for pagination of the index view, when rendered via the UI.
   *
   * @since  COmanage Registry v4.4.0
   * @return Array An array suitable for use in $this->paginate
   * @throws InvalidArgumentException
   */

  function paginationConditions() {
    $ret = array();

    // Filter by Organization Name
    if(!empty($this->request->params['named']['search.name'])) {
      $searchterm = $this->request->params['named']['search.name'];
      $searchterm = str_replace(urlencode("/"), "/", $searchterm);
      $searchterm = str_replace(urlencode(" "), " ", $searchterm);
      $searchterm = trim(strtolower($searchterm));
      $ret['conditions']['LOWER(Organization.name) LIKE'] = "%$searchterm%";
    }
/* This doesn't work as is
    // Filter by Source Key
    if(!empty($this->request->params['named']['search.source_key'])) {
      // Cake will auto-join the table
      $ret['conditions']['OrganizationSourceRecord.source_key'] = $this->request->params['named']['search.source_key'];
    }
*/
    // Filter by Organization Source
    if(!empty($this->request->params['named']['search.orgSource'])) {
      // Cake will auto-join the table
      $ret['conditions']['OrganizationSourceRecord.organization_source_id'] = $this->request->params['named']['search.orgSource'];
    }

    return $ret;
  }

  /**
   * Authorization for this Controller, called by Auth component
   * - precondition: Session.Auth holds data used for authz decisions
   * - postcondition: $permissions set with calculated permissions
   *
   * @since  COmanage Registry v4.0.0
   * @return Array Permissions
   */
  
  function isAuthorized() {
    $roles = $this->Role->calculateCMRoles();             // What was authenticated
    
    // Construct the permission set for this user, which will also be passed to the view.
    $p = array();
    
    // Determine what operations this user can perform
    
    // Add a new Organization?
    $p['add'] = ($roles['cmadmin'] || $roles['coadmin']);

    // Assign (autogenerate) Identifiers?
    // Note that CoIdentifierAssignments Controller allows couadmin even though
    // we don't. Probably need to rationalize this.
    $p['assign'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // Delete an existing Organization?
    $p['delete'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // Edit an existing Organization?
    $p['edit'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // View all existing Organization?
    $p['index'] = ($roles['cmadmin'] || $roles['coadmin'] || $roles['comember']);
    $p['search'] = $p['index'];
    
    // View an existing Organization?
    $p['view'] = ($roles['cmadmin'] || $roles['coadmin'] || $roles['comember']);

    // View identifiers? This correlates with IdentifiersController
    $p['identifiers'] = ($roles['cmadmin']
                         || $roles['coadmin']
                         || $roles['couadmin']);

    $this->set('permissions', $p);
    return $p[$this->action];
  }
}
