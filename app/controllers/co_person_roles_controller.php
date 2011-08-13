<?php
  /*
   * COmanage Gears CO Person Roles Controller
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
    
    // This controller allows a COU to be set
    var $allows_cou = true;

    // For CO Person group renderings, we need all CoGroup data, so we need more recursion
    var $edit_recursion = 2;
    var $view_recursion = 2;
    
    function add()
    {
      // Add a CO Person Role Object.
      //
      // Parameters (in $this->data):
      // - Model specific attributes
      //
      // Preconditions:
      //     None
      //
      // Postconditions:
      // (1) On success, new Object created
      // (2) Session flash message updated (HTML) or HTTP status returned (REST)
      // (3) $<object>_id or $invalid_fields set (REST)
      //
      // Returns:
      //   Nothing
      
      if(!$this->restful)
      {
        // Create a stub person role. It's unclear that title should
        // autopopulate, and if it need not it's further unclear that we
        // really need to set this variable.
        
        $cop = $this->viewVars['co_people'];
        $copr['CoPersonRole']['title'] = $cop[0]['CoOrgIdentityLink'][0]['OrgIdentity']['title'];
        $copr['CoPersonRole']['co_person_id'] = $cop[0]['CoPerson']['id'];
        
        $this->set('co_person_roles', array(0 => $copr));
      }
      
      parent::add();
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
      
      parent::beforeFilter();
      
      if(!$this->restful && $this->action != 'editself')
      {
        // We need CO Person information for the view as well. We also want Name,
        // so we increase recursion.
        
        $copid = -1;
        
        // Might be passed in the URL (as per add)
        if(!empty($this->params['named']['copersonid']))
          $copid = $this->params['named']['copersonid'];
        // Might be determined from the CO Person Role (as per edit/view)
        elseif(!empty($this->data['CoPersonRole']['co_person_id']))
          $copid = $this->data['CoPersonRole']['co_person_id'];
        // Might need to look it up from the person role
        elseif(!empty($this->params['pass'][0]))
          $copid = $this->CoPersonRole->field('co_person_id', array('id' => $this->params['pass'][0]));
        
        $this->CoPersonRole->CoPerson->recursive = 2;
        $cop = $this->CoPersonRole->CoPerson->findById($copid);
        
        if($cop)
          $this->set('co_people', array(0 => $cop));
        else
        {
          $this->Session->setFlash(_txt('er.cop.unk-a', array($copid)), '', array(), 'error');
          $this->redirect(array('controller' => 'co_people', 'action' => 'index', 'co' => $this->cur_co['Co']['id']));
        }
      }
      
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
          
          $cops = $this->CoPersonRole->CoPerson->findByCoPersonRoleId($this->params['id']);
          
          if(!empty($cops))
            $this->cur_co = $this->CoPersonRole->CoPerson->Co->findById($cops['CoPerson']['co_id']);
        }
        elseif(isset($this->params['url']['coid']))
        {
          // Request for all members of a CO
          
          $this->cur_co = $this->CoPersonRole->CoPerson->Co->findById($this->params['url']['coid']);
        }
        // We don't currently support requests for all CO people (regardless of CO).
        // To do so, we'd have to extract the CO ID on a per-CO person basis, which
        // wouldn't be terribly efficient.
      }
      
      $c = $this->CoPersonRole->CoPerson->Co->CoExtendedAttribute->find('count',
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
      
      // Check that the COU ID provided points to an existing COU.

      if(empty($this->data['CoPersonRole']['cou_id']))
      {
        $this->restResultHeader(403, "COU Does Not Exist");
        return(false);
      }      
      
      $a = $this->CoPersonRole->Cou->findById($this->data['CoPersonRole']['cou_id']);

      if(empty($a))
      {
        $this->restResultHeader(403, "COU Does Not Exist");
        return(false);
      }      
      
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
      if(isset($this->viewVars['co_people'][0]['Name']))
        return(generateCn($this->viewVars['co_people'][0]['Name']));
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
      
      if($cmr['comember'] && $cmr['copersonid'] && isset($this->params['pass'][0]))
      {
        // We need to see if the person role ID passed in maps to the authenticated CO person
        
        $copid = $this->CoPersonRole->field('co_person_id', array('id' => $this->params['pass'][0]));
        
        if($copid && $copid == $cmr['copersonid'])
          $self = true;
      }

      // Construct the permission set for this user, which will also be passed to the view.
      $p = array();
      
      // Determine what operations this user can perform
      
      // Add a new CO Person Role?
      $p['add'] = ($cmr['cmadmin'] || $cmr['coadmin'] || $cmr['subadmin']);
      
      // Delete an existing CO Person Role?
      $p['delete'] = ($cmr['cmadmin'] || $cmr['coadmin']);
      
      // Edit an existing CO Person Role?
      $p['edit'] = ($cmr['cmadmin'] || $cmr['coadmin'] || $self);

      // Are we trying to edit our own record?  If so, we need to track
      // both permission for the controller to invoke the method ('editself'),
      // and pass a hint to the view to tell it that an admin should be able
      // to edit their own fields anyway ('editselfv'). Kind of confusing.
      // Hopefully this can go away with a proper implementation of ACLs on
      // fields.
      
      // If we're an admin, we act as an admin, not self.
      // XXX Unclear that we still need these
      $p['editself'] = $self;
      $p['editselfv'] = $self && !$cmr['cmadmin'] && !$cmr['coadmin'] && !$cmr['subadmin'];
      
      // View all existing CO Person Roles (or a COU's worth)?
      $p['index'] = ($cmr['cmadmin'] || $cmr['coadmin'] || $cmr['subadmin']);
      
      // View an existing CO Person Role?
      $p['view'] = ($cmr['cmadmin'] || $cmr['coadmin'] || $self);
      
      // Determine which COUs a person can manage.
      if($cmr['cmadmin'] || $cmr['coadmin'])
        $p['cous'] = $this->CoPersonRole->Cou->find("list",
                                                    array("conditions" =>
                                                          array("co_id" => $this->cur_co['Co']['id'])));      
      elseif($cmr['subadmin'])
        $p['cous'] = $this->CoPersonRole->Cou->find("list",
                                                    array("conditions" =>
                                                          array("co_id" => $this->cur_co['Co']['id'],
                                                                "name" => $cmr['couadmin'])));
      else
        $p['cous'] = array();
      
      // COUs are handled a bit differently. We need to authorize operations that
      // operate on a per-person basis accordingly.
      
      if($cmr['subadmin'] && !empty($p['cous']))
      {
        if(!empty($this->params['pass'][0]))
        {
          // If the target person is in a COU managed by the COU admin, grant permission
          
          $dbo = $this->CoPersonRole->getDataSource();
          
          $tcous = $this->CoPersonRole->Cou->find("list",
                                                  array("joins" =>
                                                        array(array('table' => $dbo->fullTableName($this->CoPersonRole),
                                                                    'alias' => 'CoPersonRole',
                                                                    'type' => 'INNER',
                                                                    'conditions' => array('Cou.id=CoPersonRole.cou_id'))),
                                                        "conditions" =>
                                                        array('CoPersonRole.id' => $this->params['pass'][0])));
          
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
      
      // On add, redirect to edit view again so MVPAs are available.
      // For everything else, return to co_people
     
      if($this->action == 'add')
        $this->redirect(array('action' => 'edit', $this->CoPersonRole->id, 'co' => $this->cur_co['Co']['id']));
      else
        $this->redirect(array('controller' => 'co_people',
                              'action' => 'edit',
                              $this->viewVars['co_people'][0]['CoPerson']['id'],
                              'co' => $this->cur_co['Co']['id']));
    }
  }
?>