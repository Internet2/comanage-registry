<?php
/**
 * COmanage Registry OrgIdentity Controller
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
 * @copyright     Copyright (C) 2010-12 University Corporation for Advanced Internet Development, Inc.
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
      'Name.family' => 'asc',
      'Name.given' => 'asc'
    )
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
   * Callback after controller methods are invoked but before views are rendered.
   * - precondition: Request Handler component has set $this->request->params
   * - postcondition: If a CO must be specifed, a named parameter may be set.
   *
   * @since  COmanage Registry v0.2
   */
  
  function beforeRender() {
    $this->set('cmp_ef_attribute_order', $this->CmpEnrollmentConfiguration->getStandardAttributeOrder());

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
    $this->OrgIdentity->CoOrgIdentityLink->recursive = 2;
    
    $coppl = $this->OrgIdentity->CoOrgIdentityLink->findAllByOrgIdentityId($curdata['OrgIdentity']['id']);

    if(!empty($coppl))
    {
      // The OrgIdentity is a member of at least one CO.  This needs to be
      // manually resolved, since (eg) it may be desirable to associate the
      // CO Person with a new OrgIdentity (if, say, somebody changes affiliation).
          
      // Generate an array of CO Person ID and CO ID/Name or a message
      // for the views to render.
          
      if($this->restful)
      {
        $memberships = array();
          
        for($i = 0; $i < count($coppl); $i++)
        {
          $memberships[$coppl[$i]['CoPerson']['Co']['id']] = $coppl[$i]['CoPerson']['Co']['name'];
        }
            
        $this->restResultHeader(403, "CoPerson Exists");
        $this->set('memberships', $memberships);
      }
      else
      {
        $cs = "";
        
        for($i = 0; $i < count($coppl); $i++)
          $cs .= ($i > 0 ? "," : "") . $coppl[$i]['CoPerson']['Co']['name'];
        
        $this->Session->setFlash(_txt('er.comember',
                                      array(generateCn($coppl[0]['OrgIdentity']['Name']),
                                            Sanitize::html($cs))),
                                 '', array(), 'error');
      }
      
      return(false);
    }
    
    return(true);
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
    // Set page title
    $this->set('title_for_layout', _txt('op.find.inv'));

    // XXX we currently don't validate $coid since we just pass it back to the
    // co_person controller, which will validate it
    $this->set('cur_co', $this->OrgIdentity->CoOrgIdentityLink->CoPerson->Co->findById($this->request->params['named']['co']));

    // Use server side pagination
    
    if(isset($this->viewVars['pool_org_identities'])) {
      $this->set('org_identities',
                 $this->paginate('OrgIdentity',
                                 array("OrgIdentity.co_id" => $this->cur_co['Co']['id'])));
    } else {
      $this->set('org_identities', $this->paginate('OrgIdentity'));
    }
    
    // Don't user server side pagination
    //$this->set('org_identities', $this->CoPersonRole->OrgIdentity->find('all'));
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
    elseif(isset($this->data['Name']))
      return(generateCn($this->data['Name']));
    elseif(isset($c['Name']))
      return(generateCn($c['Name']));
    else
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
        // We don't handle delete since the org identity and its associated history
        // is about to be deleted
        break;
      case 'edit':
        $this->OrgIdentity->HistoryRecord->record(null,
                                                  null,
                                                  $this->OrgIdentity->id,
                                                  $this->Session->read('Auth.User.co_person_id'),
                                                  ActionEnum::OrgIdEditedManual,
                                                  _txt('en.action', null, ActionEnum::OrgIdEditedManual) . ": " .
                                                  $this->changesToString($newdata, $olddata, array('OrgIdentity', 'Name')));
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
    $cmr = $this->calculateCMRoles();
    
    // Is this our own record?
    $self = false;

    if($cmr['user'] && $cmr['orgidentities'] && isset($this->request->params['pass'][0]))
    {
      // Walk through the list of org identities and see if this one matches
      
      foreach($cmr['orgidentities'] as $o)
      {
        if($o['org_id'] == $this->request->params['pass'][0])
        {
          $self = true;
          break;
        }
      }
    }
    
    // Construct the permission set for this user, which will also be passed to the view.
    $p = array();
    
    // Determine what operations this user can perform. This varies according to
    // whether or not organizational identities are pooled -- if they are, we need
    // to restrict access to only org identities in the same CO.
    
    $this->loadModel('CmpEnrollmentConfiguration');
    
    if($this->CmpEnrollmentConfiguration->orgIdentitiesPooled()) {
      // Add a new Org Person?
      $p['add'] = ($cmr['cmadmin'] || $cmr['admin'] || $cmr['subadmin']);
      
      // Via LDAP query?
      $p['addvialdap'] = ($cmr['cmadmin'] || $cmr['admin'] || $cmr['subadmin']);
      $p['selectvialdap'] = ($cmr['cmadmin'] || $cmr['admin'] || $cmr['subadmin']);
      
      // Delete an existing Org Person?
      $p['delete'] = ($cmr['cmadmin'] || $cmr['admin'] || $cmr['subadmin']);
      
      // Edit an existing Org Person?
      $p['edit'] = ($cmr['cmadmin'] || $cmr['admin'] || $cmr['subadmin']);
      
      // Find an Org Person to add to a CO?
      $p['find'] = ($cmr['cmadmin'] || $cmr['admin'] || $cmr['subadmin']);
  
      // View all existing Org People?
      $p['index'] = ($cmr['cmadmin'] || $cmr['admin'] || $cmr['subadmin']);
      
      // View an existing Org Person?
      $p['view'] = ($cmr['cmadmin'] || $cmr['admin'] || $cmr['subadmin'] || $self);
    } else {
      // Add a new Org Person?
      $p['add'] = ($cmr['cmadmin'] || $cmr['coadmin'] || $cmr['couadmin']);
      
      // Via LDAP query?
      $p['addvialdap'] = ($cmr['cmadmin'] || $cmr['coadmin'] || $cmr['couadmin']);
      $p['selectvialdap'] = ($cmr['cmadmin'] || $cmr['coadmin'] || $cmr['couadmin']);
      
      // Delete an existing Org Person?
      $p['delete'] = ($cmr['cmadmin'] || $cmr['coadmin'] || $cmr['couadmin']);
      
      // Edit an existing Org Person?
      $p['edit'] = ($cmr['cmadmin'] || $cmr['coadmin'] || $cmr['couadmin']);
      
      // Find an Org Person to add to a CO?
      $p['find'] = ($cmr['cmadmin'] || $cmr['coadmin'] || $cmr['couadmin']);
  
      // View all existing Org People?
      $p['index'] = ($cmr['cmadmin'] || $cmr['coadmin'] || $cmr['couadmin']);
      
      // View an existing Org Person?
      $p['view'] = ($cmr['cmadmin'] || $cmr['coadmin'] || $cmr['couadmin'] || $self);
    }
    
    $this->set('permissions', $p);
    return($p[$this->action]);
  }

  /**
   * Perform a redirect back to the controller's default view.
   * - postcondition: Redirect generated
   *
   * @since  COmanage Registry v0.2
   */
  
  function performRedirect() {
    // On add, redirect to edit view again so MVPAs are available

    //$this->Session->setFlash('"' . generateCn($this->data['Name']) . '" Added', '', array(), 'success');
    
    if($this->action == 'add')
      $this->redirect(array('action' => 'edit',
                            $this->OrgIdentity->id,
                            'co' => (isset($this->viewVars['cur_co']['Co']['id']) ? $this->viewVars['cur_co']['Co']['id'] : false)));
    else
      parent::performRedirect();
  }

  function selectvialdap()
  {
    // XXX need to
    //  Sanitize::html
    //  I18N
    //  Set title_for_layout
    
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
