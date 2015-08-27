<?php
/**
 * COmanage Registry OrgIdentity Controller
 *
 * Copyright (C) 2011-15 University Corporation for Advanced Internet Development, Inc.
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
 * @copyright     Copyright (C) 2011-15 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.2
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */

App::uses("StandardController", "Controller");

class OrgIdentitiesController extends StandardController {
  // Class name, used by Cake
  public $name = "OrgIdentities";
  
  // When using additional models, we must also specify our own
  public $uses = array('OrgIdentity', 'CmpEnrollmentConfiguration');
  
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
    'Co',
    'CoOrgIdentityLink' => array('CoPerson' => array('Co', 'PrimaryName')),
    'EmailAddress',
    'Identifier',
    'Name',
    'PrimaryName',
    'TelephoneNumber'
  );
  
  public $view_contains = array(
    'Address',
    'Co',
    'CoOrgIdentityLink' => array('CoPerson' => array('Co', 'PrimaryName')),
    'EmailAddress',
    'Identifier',
    'Name',
    'PrimaryName',
    'TelephoneNumber'
  );
  
  function addvialdap()
  {
    // Add a new Organizational Person by querying LDAP.
    //
    // Parameters:
    //   None
    //
    // Preconditions:
    // (1) Organizations (and their LDAP servers) must be defined
    //
    // Postconditions:
    // (1) $organizations is set
    //
    // Returns:
    //   Nothing
   
    // We render the view which returns to selectvialdap()
    
    // Set page title
    $this->set('title_for_layout', _txt('op.add.new', array(_txt('ct.' . $modelpl . '.1'))));

    $this->set('organizations', $this->OrgIdentity->Organization->find('all'));
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
    
    if(!$pool)
    {
      $this->requires_co = true;
      
      // Associate the CO model
      $this->OrgIdentity->bindModel(array('belongsTo' => array('Co')));
    }
    
    // The views will also need this
    $this->set('pool_org_identities', $pool);
    
    parent::beforeFilter();
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
                                       Sanitize::html(join(',', array_values($memberships))))),
                            array('key' => 'error'));
        }
        
        return false;
      }
    }
    
    return true;
  }

  /**
   * Generate the edit view.
   * - precondition: <id> must exist
   * - postcondition: $<object>s set (with one member) if found
   * - postcondition: HTTP status returned (REST)
   * - postcondition: Session flash message updated (HTML) on suitable error
   *
   * @since  COmanage Registry v0.9.1
   * @param  Integer Org Identity identifier
   */
  
  public function edit($id) {
    // We mostly want the standard behavior, but we need to determine if the org
    // identity is eligible to be linked into any CO and if so provide that info
    // to the view.
    
    parent::edit($id);
    
    // Pull the list of linkable CO IDs from the model, then filter list according to
    // current user being CMP admin or CO admin
    
    $cos = array();
    
    if($this->Role->identifierIsCmpAdmin($this->Session->read('Auth.User.username'))) {
      // CMP Admins can do any linking, at least for now
      
      $cos = $this->OrgIdentity->linkableCos($id);
    } else {
      foreach($this->OrgIdentity->linkableCos($id) as $coid => $coname) {
        if($this->Role->isCoAdmin($this->Session->read('Auth.User.co_person_id'), $coid)) {
          $cos[$coid] = $coname;
        }
      }
    }
    
    $this->set('vv_linkable_cos', $cos);
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
                               array(_txt('ct.co_people.1'), Sanitize::html($this->request->params['named']['copersonid']))),
                          array('key' => 'error'));
        $this->performRedirect();
      }
      
      $coid = $cop['CoPerson']['co_id'];
    } else {
      $this->set('title_for_layout', _txt('op.find.inv', array($this->cur_co['Co']['name'])));
      
      $coid = $this->cur_co['Co']['id'];
    }
    
    $this->set('cur_co', $this->OrgIdentity->CoOrgIdentityLink->CoPerson->Co->findById($coid));
    
    // Use server side pagination
    
    $this->Paginator->settings = $this->paginate;
    $this->Paginator->settings['contain'] = $this->view_contains;
    
    if(!isset($this->viewVars['pool_org_identities'])
       || !$this->viewVars['pool_org_identities']) {
      $this->set('org_identities',
                 $this->Paginator->paginate('OrgIdentity',
                                      array("OrgIdentity.co_id" => $this->cur_co['Co']['id'])));
    } else {
      $this->set('org_identities', $this->Paginator->paginate('OrgIdentity'));
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
    switch($action) {
      case 'add':
        $this->OrgIdentity->HistoryRecord->record(null,
                                                  null,
                                                  $this->OrgIdentity->id,
                                                  $this->Session->read('Auth.User.co_person_id'),
                                                  ActionEnum::OrgIdAddedManual);
        break;
      case 'delete':
        $this->OrgIdentity->HistoryRecord->record(null,
                                                  null,
                                                  $this->OrgIdentity->id,
                                                  $this->Session->read('Auth.User.co_person_id'),
                                                  ActionEnum::OrgIdDeletedManual);
        break;
      case 'edit':
        $this->OrgIdentity->HistoryRecord->record(null,
                                                  null,
                                                  $this->OrgIdentity->id,
                                                  $this->Session->read('Auth.User.co_person_id'),
                                                  ActionEnum::OrgIdEditedManual,
                                                  _txt('en.action', null, ActionEnum::OrgIdEditedManual) . ": " .
                                                  $this->OrgIdentity->changesToString($newdata,
                                                                                      $olddata,
                                                                                      (!empty($this->cur_co['Co']['id'])
                                                                                       ? $this->cur_co['Co']['id']
                                                                                       : null)));
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
    
    // Construct the permission set for this user, which will also be passed to the view.
    $p = array();
    
    // Determine what operations this user can perform. This varies according to
    // whether or not organizational identities are pooled -- if they aren't, we need
    // to restrict access to only org identities in the same CO.
    
    if($this->CmpEnrollmentConfiguration->orgIdentitiesPooled()) {
      // Add a new Org Person?
      $p['add'] = ($roles['cmadmin'] || $roles['admin'] || $roles['subadmin']);
      
      // Via LDAP query?
      $p['addvialdap'] = ($roles['cmadmin'] || $roles['admin'] || $roles['subadmin']);
      $p['selectvialdap'] = ($roles['cmadmin'] || $roles['admin'] || $roles['subadmin']);
      
      // Delete an existing Org Person?
      $p['delete'] = ($roles['cmadmin'] || $roles['admin'] || $roles['subadmin']);
      
      // Edit an existing Org Person?
      $p['edit'] = ($roles['cmadmin'] || $roles['admin'] || $roles['subadmin']);
      
      // Find an Org Person to add to a CO?
      $p['find'] = ($roles['cmadmin'] || $roles['admin'] || $roles['subadmin']);
      
      // View all existing Org People?
      $p['index'] = ($roles['cmadmin'] || $roles['admin'] || $roles['subadmin']);
      $p['search'] = $p['index'];
      
      // Explicit linking of an Org Identity to a CO Person?
      $p['link'] = ($roles['cmadmin'] || $roles['admin']);
      
      // View an existing Org Person?
      $p['view'] = ($roles['cmadmin'] || $roles['admin'] || $roles['subadmin'] || $self);
    } else {
      // Add a new Org Person?
      $p['add'] = ($roles['cmadmin'] || $roles['coadmin'] || $roles['couadmin']);
      
      // Via LDAP query?
      $p['addvialdap'] = ($roles['cmadmin'] || $roles['coadmin'] || $roles['couadmin']);
      $p['selectvialdap'] = ($roles['cmadmin'] || $roles['coadmin'] || $roles['couadmin']);
      
      // Delete an existing Org Person?
      $p['delete'] = ($roles['cmadmin']
                      || ($managed && ($roles['coadmin'] || $roles['couadmin'])));
      
      // Edit an existing Org Person?
      $p['edit'] = ($roles['cmadmin']
                    || ($managed && ($roles['coadmin'] || $roles['couadmin'])));
      
      // Find an Org Person to add to a CO?
      $p['find'] = ($roles['cmadmin'] || $roles['coadmin'] || $roles['couadmin']);
      
      // View all existing Org People?
      $p['index'] = ($roles['cmadmin'] || $roles['coadmin'] || $roles['couadmin']);
      $p['search'] = $p['index'];

      if($this->action == 'index' && $p['index']) {
        // For rendering index, we currently assume that anyone who can view the
        // index can manipulate all records. This is probably right.
        
        $p['delete'] = true;
        $p['edit'] = true;
        $p['view'] = true;
      }
      
      // Explicit linking of an Org Identity to a CO Person?
      $p['link'] = ($roles['cmadmin'] || $roles['admin']);
      
      // View an existing Org Person?
      $p['view'] = ($roles['cmadmin']
                    || ($managed && ($roles['coadmin'] || $roles['couadmin']))
                    || $self);
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
    if(!empty($this->params['named']['Search.givenName'])) {
      $searchterm = strtolower($this->params['named']['Search.givenName']);
      $pagcond['conditions']['LOWER(PrimaryName.given) LIKE'] = "%$searchterm%";
    }

    // Filter by Family name
    if(!empty($this->params['named']['Search.familyName'])) {
      $searchterm = strtolower($this->params['named']['Search.familyName']);
      $pagcond['conditions']['LOWER(PrimaryName.family) LIKE'] = "%$searchterm%";
    }

    // Filter by Organization
    if(!empty($this->params['named']['Search.organization'])) {
      $searchterm = strtolower($this->params['named']['Search.organization']);
      $pagcond['conditions']['LOWER(OrgIdentity.o) LIKE'] = "%$searchterm%";
    }

    // Filter by Department
    if(!empty($this->params['named']['Search.department'])) {
      $searchterm = strtolower($this->params['named']['Search.department']);
      $pagcond['conditions']['LOWER(OrgIdentity.ou) LIKE'] = "%$searchterm%";
    }

    // Filter by title
    if(!empty($this->params['named']['Search.title'])) {
      $searchterm = strtolower($this->params['named']['Search.title']);
      $pagcond['conditions']['LOWER(OrgIdentity.title) LIKE'] = "%$searchterm%";
    }

    // Filter by affiliation
    if(!empty($this->params['named']['Search.affiliation'])) {
      $searchterm = strtolower($this->params['named']['Search.affiliation']);
      $pagcond['conditions']['OrgIdentity.affiliation LIKE'] = "%$searchterm%";
    }
    
    // Filter by email address
    if(!empty($this->params['named']['Search.mail'])) {
      $searchterm = strtolower($this->params['named']['Search.mail']);
      $pagcond['conditions']['LOWER(EmailAddress.mail) LIKE'] = "%$searchterm%";
      $pagcond['joins'][] = array(
        'table' => 'email_addresses',
        'alias' => 'EmailAddress',
        'type' => 'INNER',
        'conditions' => array(
          'EmailAddress.org_identity_id=OrgIdentity.id' 
        )
      );
    }
    
    // Filter by identifier
    if(!empty($this->params['named']['Search.identifier'])) {
      $searchterm = strtolower($this->params['named']['Search.identifier']);
      $pagcond['conditions']['LOWER(Identifier.identifier) LIKE'] = "%$searchterm%";
      $pagcond['joins'][] = array(
        'table' => 'identifiers',
        'alias' => 'Identifier',
        'type' => 'INNER',
        'conditions' => array(
          'Identifier.org_identity_id=OrgIdentity.id' 
        )
      );
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
    // On add, redirect to edit view again so MVPAs are available

    if($this->action == 'add')
      $this->redirect(array('action' => 'edit',
                            $this->OrgIdentity->id,
                            'co' => (isset($this->viewVars['cur_co']['Co']['id']) ? $this->viewVars['cur_co']['Co']['id'] : false)));
    else
      parent::performRedirect();
  }
  
  /**
   * Insert search parameters into URL for index.
   * - postcondition: Redirect generated
   *
   * @since  COmanage Registry v0.8
   */
  function search() {
    // the page we will redirect to
    $url['action'] = 'index';
     
    // build a URL will all the search elements in it
    // the resulting URL will be 
    // example.com/registry/org_identities/index/Search.givenName:albert/Search.familyName:einstein
    foreach ($this->data['Search'] as $field=>$value){
      if(!empty($value))
        $url['Search.'.$field] = $value; 
    }

    // Include CO
    if($this->requires_co) {
      $url['co'] = $this->cur_co['Co']['id'];
    }
    
    // redirect the user to the url
    $this->redirect($url, null, true);
  }

  function selectvialdap()
  {
    // XXX need to
    //  Sanitize::html
    //  I18N
    //  Set title_for_layout
    // or just clean this out (along with add via ldap)
    
    // Query LDAP according to the args received and present possible matches to add as new organizational people.
    
    print_r($this->data);
    
    $org = $this->OrgIdentity->Organization->findById($this->data['OrgIdentity']['organization']);
    print_r($org['Organization']['directory']);
    
    // query ldap
    // collate results
    // pass to view (caching so no query required on return)
    
    if($org['Organization']['directory'] != "")
    {
      $ds = ldap_connect($org['Organization']['directory']);
      
      if($ds)
      {
        $r = ldap_bind($ds);
        
        if($r)
        {
          $sr = ldap_search($ds, $org['Organization']['searchbase'], "sn=" . $this->data['OrgIdentity']['sn']);
          
          if($sr)
          {
            $c = ldap_count_entries($ds, $sr);
            echo "Entries: " . $c . "<br />";
            
            $info = ldap_get_entries($ds, $sr);
            
            for($i = 0; $i < $info['count'];$i++)
            {
              echo "dn is: " . $info[$i]["dn"] . "<br />";
              echo "first cn entry is: " . $info[$i]["cn"][0] . "<br />";
              echo "first email entry is: " . $info[$i]["mail"][0] . "<br /><hr />";                
            }
          }
        }
        // else error check XXX
        
        ldap_close($ds);
      }
      // else error check XXX
    }
    // else warn XXX
  }
}
