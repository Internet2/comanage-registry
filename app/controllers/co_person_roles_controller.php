<?php
  /*
   * COmanage Gears CO Person Rolew Controller
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

  class CoPersonRolesController extends StandardController {
    var $name = "CoPersonRoles";
    var $components = array('RequestHandler',  // For REST
                            'Security',
                            'Session');
    var $helpers = array('Time');
    var $paginate = array(
      'limit' => 25,
      'order' => array(
        'Name.family' => 'asc',
        'Name.given' => 'asc'
      )
    );
    
    // This controller needs a CO to be set
    var $requires_co = true;

    // For CO Person group renderings, we need all CoGroup data, so we need more recursion
    var $edit_recursion = 2;
    var $view_recursion = 2;

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
      
      parent::beforeFilter();
      
      // If there are any extended attributes defined for this CO,
      // dynamically bind the CO table of attributes to the model.
      
      if($this->restful && !isset($this->cur_co))
      {
        // Calls to co_people via the REST controller won't have a CO set (except
        // when retrieving all members of a CO) so we have to figure out the CO
        // from the person requested.
        
        if(isset($this->params['id']))
        {
          // Request for an individual
          
          $cops = $this->CoPersonRole->CoPersonSource->findByCoPersonRoleId($this->params['id']);
          
          if(!empty($cops))
            $this->cur_co = $this->CoPersonRole->CoPersonSource->Co->findById($cops['CoPersonSource']['co_id']);
        }
        elseif(isset($this->params['url']['coid']))
        {
          // Request for all members of a CO
          
          $this->cur_co = $this->CoPersonRole->CoPersonSource->Co->findById($this->params['url']['coid']);
        }
        // We don't currently support requests for all CO people (regardless of CO).
        // To do so, we'd have to extract the CO ID on a per-CO person basis, which
        // wouldn't be terribly efficient.
      }
      
      $c = $this->CoPersonRole->CoPersonSource->Co->CoExtendedAttribute->find('count',
                                                                              array('conditions' =>
                                                                                    array('co_id' => $this->cur_co['Co']['id'])));
      
      if($c > 0)
      {
        $cl = 'Co' . $this->cur_co['Co']['id'] . 'PersonExtendedAttribute';
        
        $this->CoPersonRole->bindModel(array('hasOne' =>
                                             array($cl => array('className' => $cl,
                                                                'dependent' => true))),
                                       false);
      }
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
        
        // The same applies for Extended Attributes, except we have to figure
        // out the appropriate name.
        
        foreach(array_keys($curdata) as $ak)
        {
          if(preg_match('/Co[0-9]+PersonExtendedAttribute/', $ak))
            $this->data[$ak]['id'] = $curdata[$ak]['id'];
        }
      }

      return(true);
    }
    
    function compare($id)
    {
      // Retrieve CO and Org attributes for comparison.
      //
      // Parameters:
      // - id: CO Person Role identifier
      //
      // Preconditions:
      // (1) <id> must exist
      //
      // Postconditions:
      // (1) $<object>s set (with one member) if found
      // (2) HTTP status returned (REST)
      // (3) Session flash message updated (HTML) on suitable error 
      //
      // Returns:
      //   Nothing
      
      // This is pretty similar to the standard view or edit methods.
      // We'll just retrieve and set the Org Person, then invoke view.
      // (We could invoke edit instead, presumably.)
      
      $cop = $this->CoPersonRole->CoPersonSource->findByCoPersonRoleId($id);
      
      if(!empty($cop))
      {
        $orgp = $this->CoPersonRole->CoPersonSource->OrgIdentity->findById($cop['CoPersonSource']['org_identity_id']);
        
        if(!empty($cop))
        {
          $this->set("org_identities", array(0 => $orgp));
          
          $this->view($id);
        }
      }
    }
    
    function editself()
    {
      // Determine our CO person role ID and redirect to edit.
      //
      // Parameters:
      //   None
      //
      // Preconditions:
      // (1) User must be authenticated.
      //
      // Postconditions:
      // (1) Redirect issued.
      //
      // Returns:
      //   Nothing
      
      $cmr = $this->calculateCMRoles();
      
      if(isset($cmr['copersonroleid']))
        $this->redirect(array('action' => 'edit', $cmr['copersonroleid'], 'co' => $this->cur_co['Co']['id']));
      else
      {
        $this->Session->setFlash(_txt('op.cop.none'), '', array(), 'error');
        $this->redirect(array('action' => 'index', 'co' => $this->cur_co['Co']['id']));
      }
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
      if(isset($this->data['Name']))
        return(generateCn($this->data['Name']));
      else
        return("(?)");
    }

    function invite()
    {
      // Invite the person identified by the org person ID to a CO
      //
      // Parameters (in $this->params):
      // - orgpersonid: ID of Org Person to invite
      // - co: ID of CO to invite to
      //
      // Preconditions:
      //     None
      //
      // Postconditions:
      // (1) $co_person set
      // (2) Session flash message updated (HTML) on suitable error
      //
      // Returns:
      //   Nothing
      
      $orgp = $this->CoPersonRole->CoPersonSource->OrgIdentity->findById($this->params['named']['orgidentityid']);
      
      if(!empty($orgp))
      {
        if(!$this->restful)
        {
          // Set page title
          $this->set('title_for_layout', _txt('op.inv-a', generateCn($orgp['Name'])));
        }

        // Construct a CoPersonRole from the OrgIdentity.  We only populate defaulted values.
        
        $cop['Name'] = $orgp['Name'];
        $cop['CoPersonRole']['title'] = $orgp['OrgIdentity']['title']; // XXX unclear that title should autopopulate
        $cop['CoPersonSource'][0]['org_identity_id'] = $orgp['OrgIdentity']['id'];
        
        $this->set('co_person_roles', array(0 => $cop));
      }
      else
      {
        $this->Session->setFlash(_txt('op.orgp-unk-a', array($this->params['named']['orgidentityid'])), '', array(), 'error');
        $this->redirect(array('action' => 'index', 'co' => $this->cur_co['Co']['id']));
      }
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
      
      if($cmr['comember'] && $cmr['copersonroleid'] &&
         ((isset($this->params['pass'][0]) && ($cmr['copersonroleid'] == $this->params['pass'][0]))
          ||
          ($this->action == 'editself' && isset($cmr['copersonroleid']))))
        $self = true;

      // Construct the permission set for this user, which will also be passed to the view.
      $p = array();
      
      // Determine what operations this user can perform
      
      // Add a new CO Person?
      $p['add'] = ($cmr['cmadmin'] || $cmr['coadmin'] || $cmr['subadmin']);
      // Via invite?
      $p['invite'] = ($cmr['cmadmin'] || $cmr['coadmin'] || $cmr['subadmin']);
      
      // Compare CO attributes and Org attributes?
      $p['compare'] = ($cmr['cmadmin'] || $cmr['coadmin'] || $self);
      
      // Delete an existing CO Person?
      $p['delete'] = ($cmr['cmadmin'] || $cmr['coadmin']);
      
      // Edit an existing CO Person?
      $p['edit'] = ($cmr['cmadmin'] || $cmr['coadmin'] || $self);

      // Are we trying to edit our own record?  If so, we need to track
      // both permission for the controller to invoke the method ('editself'),
      // and pass a hint to the view to tell it that an admin should be able
      // to edit their own fields anyway ('editselfv'). Kind of confusing.
      // Hopefully this can go away with a proper implementation of ACLs on
      // fields.
      
      // If we're an admin, we act as an admin, not self.
      $p['editself'] = $self;
      $p['editselfv'] = $self && !$cmr['cmadmin'] && !$cmr['coadmin'] && !$cmr['subadmin'];
      
      // View all existing CO People (or a COU's worth)?
      $p['index'] = ($cmr['cmadmin'] || $cmr['coadmin'] || $cmr['subadmin']);
      
      // View an existing CO Person?
      $p['view'] = ($cmr['cmadmin'] || $cmr['coadmin'] || $self);
      
      // Determine which COUs a person can manage.
      if($cmr['cmadmin'] || $cmr['coadmin'])
        $p['cous'] = $this->CoPersonRole->CoPersonSource->Cou->find("list",
                                                                    array("conditions" =>
                                                                          array("co_id" => $this->cur_co['Co']['id'])));      
      elseif($cmr['subadmin'])
        $p['cous'] = $this->CoPersonRole->CoPersonSource->Cou->find("list",
                                                                    array("conditions" =>
                                                                          array("co_id" => $this->cur_co['Co']['id'],
                                                                                "name" => $cmr['couadmin'])));
      else
        $p['cous'] = array();
      
      // COUs are handled a bit differently. The rendering of /index by standard
      // controller will only list people in the COUs managed by the COU admin,
      // so we don't have to worry about rendering view/edit/delete links on a
      // per-person basis for that. However, all the other operations here
      // operate on a per-person basis, and we need to authorizer those
      // accordingly.
      
      if($cmr['subadmin'] && !empty($p['cous']))
      {
        if(!empty($this->params['pass'][0]))
        {
          // If the target person is in a COU managed by the COU admin, grant permission
          
          $dbo = $this->CoPersonRole->getDataSource();
          
          $tcous = $this->CoPersonRole->CoPersonSource->Cou->find("list",
                                                                  array("conditions" =>
                                                                        array('CoPersonSource.co_person_role_id' => $this->params['pass'][0]),
                                                                        "joins" =>
                                                                        array(array('table' => $dbo->fullTableName($this->CoPersonRole->CoPersonSource),
                                                                                    'alias' => 'CoPersonSource',
                                                                                    'type' => 'INNER',
                                                                                    'conditions' => array('Cou.id=CoPersonSource.cou_id')))));
          
          $a = array_intersect($tcous, $p['cous']);
  
          if(!empty($a))
          {
            // CO Person is a member of at least one COU that the COU admin manages
            
            $p['compare'] = true;
            $p['delete'] = true;
            $p['edit'] = true;
            $p['view'] = true;
          }
        }
        else
        {
          if($p['index'])
          {
            // We grant additional permissions so the appropriate buttons render
            // on the assumption that any row that renders is for an individual
            // that this COU admin can manage, and that anyway we'll check the
            // authz on a per-person basis (the above portion of this if/else)
            // when an individual is selected. This probably isn't ideal -- it
            // might be better to have separate render and action permissions --
            // but it'll do.
            
            $p['compare'] = true;
            $p['delete'] = true;
            $p['edit'] = true;
            $p['view'] = true;
          }
        }
      }
      
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
      
      // On add, redirect to send view for notification of invite
            
      if($this->action == 'add')
        $this->redirect(array('controller' => 'co_invites',
                              'action' => 'send',
                              'copersonroleid' => $this->CoPersonRole->id,
                              'co' => $this->cur_co['Co']['id']));
      else
        parent::performRedirect();
    }
  }
?>