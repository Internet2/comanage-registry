<?php
  /*
   * COmanage Gears Organizational Identity Controller
   *
   * Version: $Revision$
   * Date: $Date$
   *
   * Copyright (C) 2010-2011 University Corporation for Advanced Internet Development, Inc.
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

  include APP."controllers/standard_controller.php";

  class OrgIdentitiesController extends StandardController {
    // Class name, used by Cake
    var $name = "OrgIdentities";
    
    // When using additional controllers, we must also specify our own
    var $uses = array('OrgIdentity', 'CmpEnrollmentConfiguration');
    
    // Cake Components used by this Controller
    var $components = array('RequestHandler',  // For REST
                            'Security',
                            'Session');
    
    // Establish pagination parameters for HTML views
    var $paginate = array(
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
    
    function beforeFilter()
    {
      // Callback before other controller methods are invoked or views are rendered.
      //
      // Parameters:
      //   None
      //
      // Preconditions:
      //     None
      //
      // Postconditions:
      // (1) Parent called
      //
      // Returns:
      //   Nothing
      
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
    
    function beforeRender()
    {
      // Callback after controller methods are invoked but before views are rendered.
      //
      // Parameters:
      //   None
      //
      // Preconditions:
      // (1) Request Handler component has set $this->params and/or $this->data
      //
      // Postconditions:
      // (1) If a CO must be specifed, a named parameter may be set.
      //
      // Returns:
      //   Nothing
      
      $this->set('cmp_ef_attribute_order', $this->CmpEnrollmentConfiguration->getStandardAttributeOrder());
    }
    
    function checkDeleteDependencies($curdata)
    {
      // Perform any dependency checks required prior to a delete operation.
      // This method is intended to be overridden by model-specific controllers.
      //
      // Parameters:
      // - curdata: Current data
      //
      // Preconditions:
      //     None
      //
      // Postconditions:
      // (1) Session flash message updated (HTML) or HTTP status returned (REST) on error
      // (2) $memberships set if Org Person is a member of any COs (REST)
      //
      // Returns:
      // - true if dependency checks succeed, false otherwise.
      
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

    function checkWriteDependencies($curdata = null)
    {
      // Perform any dependency checks required prior to a write (add/edit) operation.
      // This method is intended to be overridden by model-specific controllers.
      //
      // Parameters:
      // - For edit operations, $curdata will hold current data
      //
      // Preconditions:
      // (1) $this->data holds request data
      //
      // Postconditions:
      // (1) Session flash message updated (HTML) or HTTP status returned (REST) on error
      //
      // Returns:
      // - true if dependency checks succeed, false otherwise.
      
      if($this->restful && $curdata != null)
      {
        // For edit operations, Name ID needs to be passed so we replace rather than add.
        // However, the current API spec doesn't pass the Name ID (since the name is
        // embedded in the Person), so we need to copy it over here.
        
        $this->data['Name']['id'] = $curdata['Name']['id'];
      }

      return(true);
    }
    
    function find()
    {
      // Find an organizational identity to add to the co $coid.  This method doesn't
      // add or invite the person, but redirects back to co_person_role controller to
      // handle that.
      //
      // Parameters (in $this->params):
      // - coid: ID of CO to return to
      //
      // Preconditions:
      //     Nane
      //
      // Postconditions:
      // (1) $org_identities set on success, using pagination
      // (2) $cur_co set
      // (3) Session flash message updated (HTML) on suitable error
      //
      // Returns:
      //   Nothing

      // Set page title
      $this->set('title_for_layout', _txt('op.find.inv'));

      // XXX we currently don't validate $coid since we just pass it back to the
      // co_person controller, which will validate it
      $this->set('cur_co', $this->OrgIdentity->CoOrgIdentityLink->CoPerson->Co->findById($this->params['named']['co']));

      // Use server side pagination
      $this->set('org_identities', $this->paginate('OrgIdentity'));

      // Don't user server side pagination
      //$this->set('org_identities', $this->CoPersonRole->OrgIdentity->find('all'));
    }
        
    function generateDisplayKey($c = null)
    {
      // Generate a display key to be used in messages such as "Item Added".
      //
      // Parameters:
      // - c: A cached object (eg: from prior to a delete)
      //
      // Parameters:
      //   None
      //
      // Preconditions:
      //     None
      //
      // Postconditions:
      // (1) A string to be included for display.
      //
      // Returns:
      //   Nothing
 
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

    function isAuthorized()
    {
      // Authorization for this Controller, called by Auth component
      //
      // Parameters:
      //   None
      //
      // Preconditions:
      // (1) Session.Auth holds data used for authz decisions
      //
      // Postconditions:
      // (1) $permissions set with calculated permissions
      //
      // Returns:
      // - Array of permissions

      $cmr = $this->calculateCMRoles();
      
      // Is this our own record?
      $self = false;

      if($cmr['user'] && $cmr['orgidentities'] && isset($this->params['pass'][0]))
      {
        // Walk through the list of org identities and see if this one matches
        
        foreach($cmr['orgidentities'] as $o)
        {
          if($o['org_id'] == $this->params['pass'][0])
          {
            $self = true;
            break;
          }
        }
      }
      
      // Construct the permission set for this user, which will also be passed to the view.
      $p = array();
      
      // Determine what operations this user can perform
      
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

      $this->set('permissions', $p);
      return($p[$this->action]);
    }

    function performRedirect()
    {
      // Perform a redirect back to the controller's default view.
      //
      // Parameters:
      //   None
      //
      // Preconditions:
      //     None
      //
      // Postconditions:
      // (1) Redirect generated
      //
      // Returns:
      //   Nothing
      
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
?>