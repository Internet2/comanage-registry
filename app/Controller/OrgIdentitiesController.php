<?php
/**
 * COmanage Registry OrgIdentity Controller
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
 * @since         COmanage Registry v0.2
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::uses("StandardController", "Controller");

class OrgIdentitiesController extends StandardController {
  // Class name, used by Cake
  public $name = "OrgIdentities";
  
  // When using additional models, we must also specify our own
  public $uses = array('OrgIdentity',
                       'OrgIdentitySource',
                       'AttributeEnumeration',
                       'CmpEnrollmentConfiguration');
  
  public $paginate = array(
    'limit' => 25,
    'order' => array(
      'PrimaryName.family' => 'asc',
      'PrimaryName.given' => 'asc'
    )
  );
  
  public $delete_contains = array(
    'CoOrgIdentityLink' => array('CoPerson' => array('Co', 'PrimaryName')),
    'Name',
    'PrimaryName'
  );
  
  public $edit_contains = array(
    'Address',
    'AdHocAttribute',
    'Co',
    'CoOrgIdentityLink' => array('CoPerson' => array('Co', 'PrimaryName')),
    'EmailAddress',
    'Identifier',
    'Name',
    'OrgIdentitySourceRecord' => array('OrgIdentitySource'),
    'PrimaryName',
    'TelephoneNumber',
    'Url'
  );
  
  public $view_contains = array(
    'Address',
    'AdHocAttribute',
    'Co',
    'CoOrgIdentityLink' => array('CoPerson' => array('Co', 'PrimaryName')),
    'EmailAddress',
    'Identifier',
    'Name',
    'OrgIdentitySourceRecord' => array('OrgIdentitySource'),
// This causes slowness on MariaDB. See CO-1406.
//    'PipelineCoPersonRole',
    'PipelineCoGroupMember' => array('CoGroup'),
    'PrimaryName',
    'TelephoneNumber',
    'Url'
  );

  /**
   * Alphabet Search Bar configuration
   *
   * @since  COmanage Registry v4.0.0
   */

  public function alphabetSearchConfig($action)
  {
    $supported_views = array('index', 'find');
    if(in_array($action, $supported_views)) {
      return array(
        'search.familyNameStart' => array(
          'label' => _txt('me.alpha.label'),
        ),
      );
    }
  }

  /**
   * Search Block fields configuration
   *
   * @since  COmanage Registry v4.0.0
   */

  public function searchConfig($action) {
    $supported_views = array('index', 'find');
    if(in_array($action, $supported_views)) {
      return array(
        'search.givenName' => array(              // 1st row, left column
          'label' => _txt('fd.name.given'),
          'type' => 'text',
        ),
        'search.organization' => array(          // 1st row, right column
          'label' => _txt('fd.o'),
          'type' => 'text',
        ),
        'search.familyName' => array(           // 2nd row, left column
          'label' => _txt('fd.name.family'),
          'type' => 'text',
        ),
        'search.orgIdentitySource' => array(    // 2nd row, right column
          'label' => _txt('ct.org_identity_sources.1'),
          'type' => 'select',
          'empty'   => _txt('op.select.all'),
          'options' => $this->viewVars['vv_org_id_sources'],
        ),
        'search.mail' => array(                // 3rd row, left column
          'label' => _txt('fd.email_address.mail'),
          'type' => 'text',
        ),
        'search.department' => array(          // 3rd row, right column
          'label' => _txt('fd.ou'),
          'type' => 'text',
        ),
        'search.identifier' => array(          // 4th row, left column
          'label' => _txt('fd.identifier.identifier'),
          'type' => 'text',
        ),
        'search.affiliation' => array(         // 4th row, right column
          'label' => _txt('fd.affiliation'),
          'type' => 'select',
          'empty'   => _txt('op.select.all'),
          'options' => _txt('en.org_identity.affiliation'),
        ),
        'search.title' => array(              // 5th row, left column
          'label' => _txt('fd.title'),
          'type' => 'text',
        ),
      );
    }
  }


  /**
   * Callback before other controller methods are invoked or views are rendered.
   * - postcondition: requires_co possibly set
   *
   * @since  COmanage Registry v0.2
   */
  
  function beforeFilter() {
    // This controller may or may not require a CO, depending on how
    // the CMP Enrollment Configuration is set up. Check and adjust before
    // beforeFilter is called.
    
    $pool = $this->CmpEnrollmentConfiguration->orgIdentitiesPooled();
    
    if(!$pool) {
      $this->requires_co = true;
      
      // Associate the CO model
      $this->OrgIdentity->bindModel(array('belongsTo' => array('Co')));
    }
    
    // The views will also need this
    $this->set('pool_org_identities', $pool);
    
    parent::beforeFilter();
    
    // Pull attribute enumerations and adjust validation rules, if needed
    
    $coId = null;
    
    if($this->requires_co) {
      $coId = isset($this->cur_co['Co']['id']) ? $this->cur_co['Co']['id'] : -1;
    }
    
    $enums = array();
    
    $enums_o = $this->AttributeEnumeration->enumerations($coId, "OrgIdentity.o");
    
    if(!empty($enums_o)) {
      $enums['OrgIdentity.o'] = $enums_o;
    }
    
    $enums_ou = $this->AttributeEnumeration->enumerations($coId, "OrgIdentity.ou");
    
    if(!empty($enums_ou)) {
      $enums['OrgIdentity.ou'] = $enums_ou;
    }
    
    $enums_title = $this->AttributeEnumeration->enumerations($coId, "OrgIdentity.title");
    
    if(!empty($enums_title)) {
      $enums['OrgIdentity.title'] = $enums_title;
    }
    
    $this->set('vv_enums', $enums);
    
    if(!empty($this->viewVars['vv_tz'])) {
      // Set the current timezone, primarily for beforeSave
      $this->OrgIdentity->setTimeZone($this->viewVars['vv_tz']);
    }
    
    // Dynamically adjust validation rules to include the current CO ID for dynamic types.

    $vrule = $this->OrgIdentity->validate['affiliation']['content']['rule'];
    $vrule[1]['coid'] = isset($this->cur_co['Co']['id']) ? $this->cur_co['Co']['id'] : -1;
    $this->OrgIdentity->validator()->getField('affiliation')->getRule('content')->rule = $vrule;
  }
  
  /**
   * Callback after controller methods are invoked but before views are rendered.
   * - precondition: Request Handler component has set $this->request->params
   * - postcondition: If a CO must be specifed, a named parameter may be set.
   * - postcondition: $co_enrollment_attributes may be set.
   *
   * @since  COmanage Registry v2.0.0
   */
  
  function beforeRender() {
    // Views may need to know if Org Identity Sources are defined and enabled.
    
    $args = array();
    $args['conditions']['OrgIdentitySource.status'] = SuspendableStatusEnum::Active;
    if(!$this->viewVars['pool_org_identities']) {
      $args['conditions']['OrgIdentitySource.co_id'] = isset($this->cur_co['Co']['id']) ? $this->cur_co['Co']['id'] : -1;
    }
    $args['fields'] = array('id', 'description');
    $args['contain'] = false;
    
    $this->set('vv_org_id_sources', $this->OrgIdentitySource->find('list', $args));

    // Get the affiliations for display in the search filter bar
    global $cm_lang, $cm_texts;
    $this->set('vv_affiliations', $cm_texts[ $cm_lang ]['en.org_identity.affiliation']);

    // If an OrgIdentity was specified, see if there's an associated pipeline
    
    if(($this->action == 'edit' || $this->action == 'view')
       && isset($this->request->params['pass'][0])) {
      $pipeline = $this->OrgIdentity->pipeline($this->request->params['pass'][0]);
      
      if($pipeline) {
        $args = array();
        $args['conditions']['CoPipeline.id'] = $pipeline;
        $args['contain'] = false;
        
        $this->set('vv_pipeline', $this->OrgIdentity->Co->CoPipeline->find('first', $args));
      }
      
      // Pull any CO Person Role associated with this as the source org id. We should be able to get
      // this via $view_contains, but CO-1406.
      
      $args = array();
      $args['conditions']['PipelineCoPersonRole.source_org_identity_id'] = $this->request->params['pass'][0];
      $args['contain'] = false;
      
      $this->set('vv_co_person_roles', $this->OrgIdentity->PipelineCoPersonRole->find('first', $args));
      
      // Pull the list of linkable CO IDs from the model, then filter list according to
      // current user being CMP admin or CO admin
      
      $cos = array();
      
      if($this->Role->identifierIsCmpAdmin($this->Session->read('Auth.User.username'))) {
        // CMP Admins can do any linking, at least for now
        
        $cos = $this->OrgIdentity->linkableCos($this->request->params['pass'][0]);
      } else {
        foreach($this->OrgIdentity->linkableCos($this->request->params['pass'][0]) as $coid => $coname) {
          if($this->Role->isCoAdmin($this->Session->read('Auth.User.co_person_id'), $coid)) {
            $cos[$coid] = $coname;
          }
        }
      }
      
      $this->set('vv_linkable_cos', $cos);
    }
    
    if(!$this->request->is('restful') && !empty($this->cur_co['Co']['id'])) {
      // Mappings for extended types
      $this->set('vv_addresses_types', $this->OrgIdentity->Address->types($this->cur_co['Co']['id'], 'type'));
      $this->set('vv_email_addresses_types', $this->OrgIdentity->EmailAddress->types($this->cur_co['Co']['id'], 'type'));
      $this->set('vv_identifiers_types', $this->OrgIdentity->Identifier->types($this->cur_co['Co']['id'], 'type'));
      $this->set('vv_telephone_numbers_types', $this->OrgIdentity->TelephoneNumber->types($this->cur_co['Co']['id'], 'type'));
      $this->set('vv_urls_types', $this->OrgIdentity->Url->types($this->cur_co['Co']['id'], 'type'));
      // We intentionally use CoPersonRole types
      $this->set('vv_affiliation_types', $this->OrgIdentity->types($this->cur_co['Co']['id'], 'affiliation'));
    }
    
    parent::beforeRender();
  }
  
  /**
   * Perform any dependency checks required prior to a delete operation.
   * - postcondition: Session flash message updated (HTML) or HTTP status returned (REST)
   *
   * @since  COmanage Registry v0.2
   * @param  Array Current data
   * @return boolean true if dependency checks succeed, false otherwise.
   */
  
  function checkDeleteDependencies($curdata) {
    // We need to retrieve past the first order to get Name
    
    if(!empty($curdata['CoOrgIdentityLink'])) {
      // The OrgIdentity may be a member of at least one CO. In order to check we need
      // to walk the co org identity links and check that deleted status of any associated
      // CO people. (If a CO Person is deleted, ChangelogBehavior will flag the person as
      // deleted but the identity link will remain.)
      
      $memberships = array();
      
      foreach($curdata['CoOrgIdentityLink'] as $l) {
        // Skip this record if it's deleted or not current
        
        if((!isset($l['CoPerson']['deleted']) || !$l['CoPerson']['deleted'])
           && !$l['CoPerson']['co_person_id']) {
          $memberships[ $l['CoPerson']['Co']['id'] ] = $l['CoPerson']['Co']['name'];
        }
      }
      
      if(!empty($memberships)) {
        // The OrgIdentity is a member of at least one CO.  This needs to be
        // manually resolved, since (eg) it may be desirable to associate the
        // CO Person with a new OrgIdentity (if, say, somebody changes affiliation).
        
        if($this->request->is('restful')) {
          $this->Api->restResultHeader(403, "CoPerson Exists");
          $this->set('memberships', $memberships);
        } else {
          $this->Flash->set(_txt('er.comember',
                                 array(generateCn($curdata['PrimaryName']),
                                       filter_var(join(',', array_values($memberships))),FILTER_SANITIZE_SPECIAL_CHARS)),
                            array('key' => 'error'));
        }
        
        return false;
      }
    }
    
    return true;
  }
  
  /**
   * Find an organizational identity to add to the co $coid.  This method doesn't add or
   * invite the person, but redirects back to co_person_role controller to handle that.
   * - precondition: $this->request->params holds CO ID
   * - postcondition: $org_identities set on success
   * - postcondition: $cur_co set
   * - postcondition: Session flash message updated (HTML) on error
   *
   * @since  COmanage Registry v0.2
   */
  
  function find() {
    $coid = null;

    // Query required for the title construction
    if(!empty($this->request->params['named']['copersonid'])) {
      // Find the CO Person name
      $args = array();
      $args['conditions']['CoPerson.id'] = $this->request->params['named']['copersonid'];
      $args['contain'][] = 'PrimaryName';
      
      $cop = $this->OrgIdentity->CoOrgIdentityLink->CoPerson->find('first', $args);
      
      if(!empty($cop['PrimaryName'])) {
        $this->set('title_for_layout', _txt('op.find.link', array(generateCn($cop['PrimaryName']))));
      } else {
        $this->Flash->set(_txt('er.notfound',
                               array(_txt('ct.co_people.1'), filter_var($this->request->params['named']['copersonid'],FILTER_SANITIZE_SPECIAL_CHARS))),
                          array('key' => 'error'));
        $this->performRedirect();
      }
      
      $coid = $cop['CoPerson']['co_id'];
    } else {
      $this->set('title_for_layout', _txt('op.find.inv', array($this->cur_co['Co']['name'])));
      
      $coid = $this->cur_co['Co']['id'];
    }
    
    $args = array();
    $args['conditions']['Co.id'] = $coid;
    $args['contain'] = false;
    
    $this->set('cur_co', $this->Co->find('first', $args));
    
    // Use server side pagination

    $local = $this->paginationConditions();

    // XXX We could probaby come up with a better approach than manually enumerating
    // each field we want to copy...
    if(!empty($local['conditions'])) {
      $this->paginate['conditions'] = $local['conditions'];
    }

    if(!empty($local['fields'])) {
      $this->paginate['fields'] = $local['fields'];
    }

    if(!empty($local['group'])) {
      $this->paginate['group'] = $local['group'];
    }

    if(!empty($local['joins'])) {
      $this->paginate['joins'] = $local['joins'];
    }

    if(isset($local['contain'])) {
      $this->paginate['contain'] = $local['contain'];
    } elseif(isset($this->view_contains)) {
      $this->paginate['contain'] = $this->view_contains;
    }

    // Used either to enumerate which fields can be used for sorting, or
    // explicitly naming sortable fields for complex relations (ie: using
    // linkable behavior).
    $sortlist = array();

    if(!empty($local['sortlist'])) {
      $sortlist = $local['sortlist'];
    }

    $this->Paginator->settings = $this->paginate;

    if(!isset($this->viewVars['pool_org_identities'])
       || !$this->viewVars['pool_org_identities']) {
      $this->set('org_identities',
                 $this->Paginator->paginate(
                   'OrgIdentity',
                   array("OrgIdentity.co_id" => $this->cur_co['Co']['id']),
                   $sortlist));
    } else {
      $this->set('org_identities', $this->Paginator->paginate('OrgIdentity', array(), $sortlist));
    }
  }
  
  /**
   * Generate a display key to be used in messages such as "Item Added".
   *
   * @since  COmanage Registry v0.2
   * @param  Array A cached object (eg: from prior to a delete)
   * @return string A string to be included for display.
   */
  
  function generateDisplayKey($c = null) {
    // Get a pointer to our model
    $req = $this->modelClass;
    $model = $this->$req;
    
    if(isset($c[$req][$model->displayField]))
      return($c[$req][$model->displayField]);
    elseif(isset($this->data['PrimaryName']))
      return(generateCn($this->data['PrimaryName']));
    elseif(isset($c['PrimaryName']))
      return(generateCn($c['PrimaryName']));
    elseif(!empty($this->request->data['OrgIdentity']['id'])) {
      // Pull the PrimaryName
      $args = array();
      $args['conditions']['OrgIdentity.id'] = $this->request->data['OrgIdentity']['id'];
      $args['contain'][] = 'PrimaryName';
      
      $p = $this->OrgIdentity->find('first', $args);
      
      if($p) {
        return generateCn($p['PrimaryName']);
      }
    }
    
    return("(?)");
  }

  /**
   * Generate history records for a transaction. This method is intended to be
   * overridden by model-specific controllers, and will be called from within a
   * try{} block so that HistoryRecord->record() may be called without worrying
   * about catching exceptions.
   *
   * @since  COmanage Registry v0.7
   * @param  String Controller action causing the change
   * @param  Array Data provided as part of the action (for add/edit)
   * @param  Array Previous data (for delete/edit)
   * @return boolean Whether the function completed successfully (which does not necessarily imply history was recorded)
   */
  
  public function generateHistory($action, $newdata, $olddata) {
    $actorCoPersonId = $this->request->is('restful') ? null : $this->Session->read('Auth.User.co_person_id');
    $actorApiUserId = $this->request->is('restful') ? $this->Auth->User('id') : null;

    switch($action) {
      case 'add':
        $this->OrgIdentity->HistoryRecord->record(null,
                                                  null,
                                                  $this->OrgIdentity->id,
                                                  $actorCoPersonId,
                                                  ActionEnum::OrgIdAddedManual,
                                                  null, null, null, null,
                                                  $actorApiUserId);
        break;
      case 'delete':
        $this->OrgIdentity->HistoryRecord->record(null,
                                                  null,
                                                  $this->OrgIdentity->id,
                                                  $actorCoPersonId,
                                                  ActionEnum::OrgIdDeletedManual,
                                                  null, null, null, null,
                                                  $actorApiUserId);
        break;
      case 'edit':
        $comment = _txt('en.action', null, ActionEnum::OrgIdEditedManual)
          . ": "
          . $this->OrgIdentity->changesToString(
            $newdata, $olddata, (!empty($this->cur_co['Co']['id']) ? $this->cur_co['Co']['id'] : null)
          );
        $this->OrgIdentity->HistoryRecord->record(null,
                                                  null,
                                                  $this->OrgIdentity->id,
                                                  $actorCoPersonId,
                                                  ActionEnum::OrgIdEditedManual,
                                                  $comment,
                                                  null, null, null,
                                                  $actorApiUserId);
        break;
    }
    
    return true;
  }
  
  /**
   * Authorization for this Controller, called by Auth component
   * - precondition: Session.Auth holds data used for authz decisions
   * - postcondition: $permissions set with calculated permissions
   *
   * @since  COmanage Registry v0.1
   * @return Array Permissions
   */
  
  function isAuthorized() {
    $roles = $this->Role->calculateCMRoles();
    
    // Is this a record we (can) manage?
    $managed = false;
    
    if(isset($roles['copersonid'])
       && $roles['copersonid']
       && isset($this->request->params['pass'][0])
       && ($this->action == 'delete'
           || $this->action == 'edit'
           || $this->action == 'view')) {
      $managed = $this->Role->isCoOrCouAdminForOrgIdentity($roles['copersonid'],
                                                           $this->request->params['pass'][0]);
    }
    
    // Or are we requesting a CO we manage?
    $manager = false;
    
    if(isset($roles['copersonid'])
       && $roles['copersonid']
       && isset($this->request->params['named']['co'])
       && ($this->action == 'find')) {
      $managed = $this->Role->isCoOrCouAdmin($roles['copersonid'],
                                             $this->request->params['named']['co']);
    }
    
    // Is this our own record?
    $self = false;
    
    if($roles['user'] && $roles['orgidentities'] && isset($this->request->params['pass'][0])) {
      // Walk through the list of org identities and see if this one matches
      
      foreach($roles['orgidentities'] as $o) {
        if($o['org_id'] == $this->request->params['pass'][0]) {
          $self = true;
          break;
        }
      }
    }
    
    // Is this a read only record? True if it has an OrgIdentity Source Record.
    // As of the initial implementation, not even CMP admins can edit such a record.
    $readOnly = false;
    
    if($this->action == 'edit' && !empty($this->request->params['pass'][0])) {
      $readOnly = $this->OrgIdentity->readOnly($this->request->params['pass'][0]);
      
      if($readOnly) {
        // Proactively redirect to view. This will also prevent (eg) the REST API
        // from editing a read only record.
        $args = array(
          'controller' => 'org_identities',
          'action'     => 'view',
          filter_var($this->request->params['pass'][0],FILTER_SANITIZE_SPECIAL_CHARS)
        );
        
        $this->redirect($args);
      }
    }
    
    // Construct the permission set for this user, which will also be passed to the view.
    $p = array();
    
    // Determine what operations this user can perform. This varies according to
    // whether or not organizational identities are pooled -- if they aren't, we need
    // to restrict access to only org identities in the same CO.
    
    if($this->CmpEnrollmentConfiguration->orgIdentitiesPooled()) {
      // Add a new Org Identity?
      $p['add'] = ($roles['cmadmin'] || $roles['admin'] || $roles['subadmin']);
      
      // Delete an existing Org Identity?
      $p['delete'] = ($roles['cmadmin'] || $roles['admin'] || $roles['subadmin']);
      
      // Edit an existing Org Identity?
      $p['edit'] = !$readOnly && ($roles['cmadmin'] || $roles['admin'] || $roles['subadmin']);
      
      // Find an Org Identity to add to a CO?
      $p['find'] = ($roles['cmadmin'] || $roles['admin'] || $roles['subadmin']);
      
      // View history? This correlates with HistoryRecordsController
      $p['history'] = ($roles['cmadmin'] || $roles['admin'] || $roles['subadmin']);
      
      // View all existing Org Identity?
      $p['index'] = ($roles['cmadmin'] || $roles['admin'] || $roles['subadmin']);
      $p['search'] = $p['index'];
      
      // View job history? This correlates with CoJobHistoryRecordsController
      $p['jobhistory'] = ($roles['cmadmin'] || $roles['admin']);
      
      // Explicit linking of an Org Identity to a CO Person?
      $p['link'] = ($roles['cmadmin'] || $roles['admin']);
      
      // View petitions?
      $p['petitions'] = ($roles['cmadmin'] || $roles['admin'] || $roles['subadmin']);
      
      // Run a pipeline? Pipelines are not available with pooled org identities
      $p['pipeline'] = false;
      
      // View an existing Org Identity?
      $p['view'] = ($roles['cmadmin'] || $roles['admin'] || $roles['subadmin'] || $self);
      
      // View a Org Identity Source Record? (Matches OrgIdentitySourceRecordsController)
      $p['viewsource'] = $roles['cmadmin'] || $roles['coadmin'] || $roles['couadmin'];
    } else {
      // Add a new Org Identity?
      $p['add'] = ($roles['cmadmin'] || $roles['coadmin'] || $roles['couadmin']);
      
      // Delete an existing Org Identity?
      $p['delete'] = ($roles['cmadmin']
                      || $roles['coadmin'] || ($managed && $roles['couadmin']));
      
      // Edit an existing Org Identity?
      $p['edit'] = !$readOnly
                   && ($roles['cmadmin']
                       || $roles['coadmin'] || ($managed && $roles['couadmin']));
      
      // Find an Org Identity to add to a CO?
      $p['find'] = ($roles['cmadmin'] || $roles['coadmin'] || $roles['couadmin']);
      
      // View identifiers? This correlates with IdentifiersController
      $p['identifiers'] = ($roles['cmadmin']
                           || $roles['coadmin']
                           || ($managed && $roles['couadmin']));
      
      // View history? This correlates with HistoryRecordsController
      $p['history'] = ($roles['cmadmin']
                       || $roles['coadmin'] || ($managed && $roles['couadmin']));
      
      // View all existing Org Identity?
      $p['index'] = ($roles['cmadmin'] || $roles['coadmin'] || $roles['couadmin']);
      $p['search'] = $p['index'];

      if($this->action == 'index' && $p['index']) {
        // For rendering index, we currently assume that anyone who can view the
        // index can manipulate all records. This is probably right.
        
        $p['delete'] = true;
        $p['edit'] = true;
        $p['view'] = true;
      }
      
      // View job history? This correlates with CoJobHistoryRecordsController
      $p['jobhistory'] = ($roles['cmadmin'] || $roles['admin']);
      
      // Explicit linking of an Org Identity to a CO Person?
      $p['link'] = ($roles['cmadmin'] || $roles['admin']);
      
      // View petitions?
      $p['petitions'] = ($roles['cmadmin']
                         || $roles['coadmin'] || ($managed && $roles['couadmin']));
      
      // Run a pipeline? This correlates with CoPipelinesController
      // For now, this is only available to CMP and CO admins
      $p['pipeline'] = ($roles['cmadmin'] || $roles['coadmin']);
      
      // View an existing Org Identity?
      $p['view'] = ($roles['cmadmin']
                    || $roles['coadmin'] || ($managed && $roles['couadmin'])
                    || $self);
      
      // View a Org Identity Source Record? (Matches OrgIdentitySourceRecordsController)
      $p['viewsource'] = ($roles['cmadmin']
                          || $roles['coadmin'] || ($managed && $roles['couadmin']));
    }
    
    $this->set('permissions', $p);
    return $p[$this->action];
  }

  /**
   * Determine the conditions for pagination of the index view, when rendered via the UI.
   *
   * @since  COmanage Registry v0.8
   * @return Array An array suitable for use in $this->paginate
   */
  
  function paginationConditions() {
    $pagcond = array();
    
    // Set page title
    $this->set('title_for_layout', _txt('ct.org_identities.pl'));

    // Use server side pagination
    
    if($this->requires_co) {
      $pagcond['conditions']['OrgIdentity.co_id'] = $this->cur_co['Co']['id'];
    }

    // Filter by given name
    if(!empty($this->request->params['named']['search.givenName'])) {
      $searchterm = strtolower($this->request->params['named']['search.givenName']);
      $pagcond['conditions']['LOWER(PrimaryName.given) LIKE'] = "%$searchterm%";
    }

    // Filter by Family name
    if(!empty($this->request->params['named']['search.familyName'])) {
      $searchterm = strtolower($this->request->params['named']['search.familyName']);
      $pagcond['conditions']['LOWER(PrimaryName.family) LIKE'] = "%$searchterm%";
    }

    // Filter by start of Primary Family name (starts with searchterm)
    if(!empty($this->request->params['named']['search.familyNameStart'])) {
      $searchterm = strtolower($this->request->params['named']['search.familyNameStart']);
      $pagcond['conditions']['LOWER(PrimaryName.family) LIKE'] = "$searchterm%";
    }

    // Filter by Organization
    if(!empty($this->request->params['named']['search.organization'])) {
      // XXX CO-2171
      // XXX Temporary solution which will be removed in v5.0.0
      $searchterm = $this->request->params['named']['search.organization'];
      $dbc = $this->OrgIdentity->getDataSource();
      if ($dbc->config['datasource'] === 'Database/Postgres') {
        $pagcond['conditions']['OrgIdentity.o iLIKE'] = "%$searchterm%";
      } else {
        // XXX In MySQL, SQL patterns are case-insensitive by default
        // Note that SQLite LIKE operator is case-insensitive. It means "A" LIKE "a" is true.
        $pagcond['conditions']['OrgIdentity.o LIKE'] = "%$searchterm%";
      }
    }

    // Filter by Department
    if(!empty($this->request->params['named']['search.department'])) {
      $searchterm = strtolower($this->request->params['named']['search.department']);
      $pagcond['conditions']['LOWER(OrgIdentity.ou) LIKE'] = "%$searchterm%";
    }

    // Filter by title
    if(!empty($this->request->params['named']['search.title'])) {
      $searchterm = strtolower($this->request->params['named']['search.title']);
      $pagcond['conditions']['LOWER(OrgIdentity.title) LIKE'] = "%$searchterm%";
    }

    // Filter by affiliation
    if(!empty($this->request->params['named']['search.affiliation'])) {
      $searchterm = strtolower($this->request->params['named']['search.affiliation']);
      $pagcond['conditions']['OrgIdentity.affiliation LIKE'] = "%$searchterm%";
    }

    $jcnt = 0;
    // Filter by email address
    if(!empty($this->request->params['named']['search.mail'])) {
      $searchterm = strtolower($this->request->params['named']['search.mail']);
      $pagcond['conditions']['LOWER(EmailAddress.mail) LIKE'] = "%$searchterm%";
      $pagcond['joins'][$jcnt]['table'] = 'email_addresses';
      $pagcond['joins'][$jcnt]['alias'] = 'EmailAddress';
      $pagcond['joins'][$jcnt]['type'] = 'INNER';
      $pagcond['joins'][$jcnt]['conditions'][0] = 'EmailAddress.org_identity_id=OrgIdentity.id';
      $jcnt++;
    }
    
    // Filter by identifier
    if(!empty($this->request->params['named']['search.identifier'])) {
      $searchterm = strtolower($this->request->params['named']['search.identifier']);
      $pagcond['conditions']['LOWER(Identifier.identifier) LIKE'] = "%$searchterm%";
      $pagcond['joins'][$jcnt]['table'] = 'identifiers';
      $pagcond['joins'][$jcnt]['alias'] = 'Identifier';
      $pagcond['joins'][$jcnt]['type'] = 'INNER';
      $pagcond['joins'][$jcnt]['conditions'][0] = 'Identifier.org_identity_id=OrgIdentity.id';
    }
    
    // Filter by org identity source
    if(!empty($this->request->params['named']['search.orgIdentitySource'])) {
      // Cake will auto-join the table
      $pagcond['conditions']['OrgIdentitySourceRecord.org_identity_source_id'] = $this->request->params['named']['search.orgIdentitySource'];
    }
    
    return $pagcond;
  }


  /**
   * Perform a redirect back to the controller's default view.
   * - postcondition: Redirect generated
   *
   * @since  COmanage Registry v0.2
   */
  
  function performRedirect() {
    // On add, redirect to edit view again so MVPAs are available.
    // In general, also redirect back to edit as a logical destination

    if($this->action != 'delete') {
      $this->redirect(array('action' => 'edit',
                            $this->OrgIdentity->id));
    } else {
      parent::performRedirect();
    }
  }
  
  /**
   * Insert search parameters into URL for index.
   * - postcondition: Redirect generated
   *
   * @todo Duplicate CoPeopleController/move to StandardController
   * @since  COmanage Registry v0.8
   */
  
  function search() {
    // Construct the URL based on the action mode we're in (find, index)
    $action = key($this->data['RedirectAction']);

    $url['action'] = $action;
    foreach($this->data[$action] as $key => $value) {
      // pass parameters
      if(is_int($key) && isset($value['pass'])) {
        array_push($url, filter_var($value['pass'], FILTER_SANITIZE_SPECIAL_CHARS));
      } else {
        foreach ($value as $knamed => $vnamed) {
          $url[$knamed] = filter_var($vnamed, FILTER_SANITIZE_SPECIAL_CHARS);
        }
      }
    }

    // Append the URL with all the search elements; the resulting URL will be similar to
    // example.com/registry/co_people/index/search.givenName:albert/search.familyName:einstein
    foreach($this->data['search'] as $field=>$value){
      if(!empty($value)) {
        $url['search.'.$field] = urlencode($value);
      }
    }

    // We need a final parameter so email addresses don't get truncated as file extensions (CO-1271)
    $url = array_merge($url, array('op' => 'search'));
    
    // redirect the user to the url
    $this->redirect($url, null, true);
  }
}
