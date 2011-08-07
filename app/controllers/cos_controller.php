<?php
  /*
   * COmanage Gears CO Controller
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

  class CosController extends StandardController {
   // Class name, used by Cake
    var $name = "Cos";
    
    // Cake Components used by this Controller
    var $components = array('RequestHandler',  // For REST
                            'Security',
                            'Session');
    
    // Establish pagination parameters for HTML views
    var $paginate = array(
      'limit' => 25,
      'order' => array(
        'name' => 'asc'
      )
    );
    
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
      //
      // Returns:
      // - true if dependency checks succeed, false otherwise.
      
      // Make sure this request isn't trying to delete the COmanage CO

      $name = $this->Co->field('name');

      if($name == "COmanage")
      {
        if($this->restful)
          $this->restResultHeader(403, "Cannot Remove COmanage CO");
        else
          $this->Session->setFlash(_txt('er.co.cm.rm'), '', array(), 'error');
          
        return(false);
      }
        
      return(true);
    }
    
    function checkWriteDependencies($curdata = null)
    {
      // Perform any dependency checks required prior to a write (add/edit) operation.
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
      
      if(isset($curdata))
      {
        // Changes to COmanage CO are not permitted
        
        if($curdata['Co']['name'] == "COmanage")
        {
          if($this->restful)
            $this->restResultHeader(403, "Cannot Edit COmanage CO");
          else
            $this->Session->setFlash(_txt('er.co.cm.edit'), '', array(), 'error');
            
          return(false);
        }
      }
      
      if(!isset($curdata)
         || ($curdata['Co']['name'] != $this->data['Co']['name']))
      {
        // Make sure name doesn't exist
        $x = $this->Co->findByName($this->data['Co']['name']);
        
        if(!empty($x))
        {
          if($this->restful)
            $this->restResultHeader(403, "Name In Use");
          else
            $this->Session->setFlash(_txt('er.co.exists', $this->data['Co']['name']), '', array(), 'error');          
  
          return(false);
        }
      }
      
      return(true);
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
      
      // Construct the permission set for this user, which will also be passed to the view.
      $p = array();
      
      // Determine what operations this user can perform
      
      // Add a new CO?
      $p['add'] = $cmr['cmadmin'];
      
      // Delete an existing CO?
      $p['delete'] = $cmr['cmadmin'];
      
      // Edit an existing CO?
      $p['edit'] = $cmr['cmadmin'];
      
      // View all existing COs?
      $p['index'] = $cmr['cmadmin'];
      
      // Select a CO under which to operate?
      $p['select'] = ($cmr['cmadmin'] || $cmr['admin'] || $cmr['user']);
      // Select from all COs, even if not a member?
      $p['select-all'] = $cmr['cmadmin'];
      
      // View an existing CO?
      $p['view'] = $cmr['cmadmin'];

      $this->set('permissions', $p);
      return($p[$this->action]);
    }
    
    function select()
    {
      // Select the CO for the current session. 
      //
      // Parameters (in $this->data):
      // - CO to select (optional)
      //
      // Preconditions:
      // (1) Session.Auth holds data used for authz decisions
      //
      // Postconditions:
      // (1) If no CO is selected and no COs exist, the 'COmanage' CO is created
      //     and a redirect issued
      // (2) If no CO is selected and the user is a member of exactly one CO,
      //     that CO is selected and a redirect issued
      // (3) If no CO is selected and the user is a member of more than one CO,
      //     $cos is set and the view rendered
      //
      // Returns:
      //   Nothing      
      
      if(empty($this->data))
      {
        // Set page title
        $this->set('title_for_layout', _txt('op.select-a', array(_txt('ct.cos.1'))));

        if($this->Session->check('Auth.User.org_identity_id'))
        {
          // Retrieve the list of the user's COs, but for admins we want all COs
          
          if(isset($this->viewVars['permissions']['select-all']) && $this->viewVars['permissions']['select-all'])
            $ucos = $this->Co->find('all');
          else
          {
            $dbo = $this->Co->getDataSource();

            $params = array(
              'joins' => array(0 => array('table' => $dbo->fullTableName($this->Co->CoPerson),
                                          'alias' => 'CoPerson',
                                          'type' => 'INNER',
                                          'conditions' => array('Co.id=CoPerson.co_id')),
                               1 => array('table' => $dbo->fullTableName($this->Co->CoPerson->CoOrgIdentityLink),
                                          'alias' => 'CoOrgIdentityLink',
                                          'type' => 'INNER',
                                          'conditions' => array('CoPerson.id=CoOrgIdentityLink.co_person_id'))),
              'conditions' => array('CoOrgIdentityLink.org_identity_id' => $this->Session->read('Auth.User.org_identity_id'))
            );
            
            $ucos = $this->Co->find('all', $params);
          }
          
          if(count($ucos) == 0)
          {
            // No memberships... could be because there are no COs
  
            $cos = $this->Co->find('all');
            
            if(count($cos) == 0)
            {
              // If there aren't any COs yet, create the first one
            
              $this->Co->set('name', 'COmanage');
              $this->Co->set('description', _txt('co.cm.desc'));
              $this->Co->set('status', 'A');
              $this->Co->save();  
              
              $this->Session->setFlash(_txt('co.init'), '', array(), 'info');
              // XXX in this (and the other) redirects, we may need to preserve the arguments also
              // (eg: session time out before clicking /co_person/select/3 to invite org persion id 3 to co)
              // Or we should set the CO some other way (including persistent cookie or configuration or url gears/myco/foobar)
              // Maybe do something with routes?
              // -- see new model in co_people_controller
              $r = array('controller' => $this->Session->read('co-select.controller'),
                         'action' => $this->Session->read('co-select.action'),
                         'co' => 'COmanage');
        
              $this->redirect(array_merge($r, $this->Session->read('co-select.args')));
            }
            else
            {
              $this->Session->setFlash(_txt('co.nomember'), '', array(), 'error');
              $this->redirect(array('controller' => 'pages', 'action' => 'menu'));
            }
          }
          elseif(count($ucos) == 1)
          {
            // Exactly one CO found
  
            $r = array('controller' => $this->Session->read('co-select.controller'),
                       'action' => $this->Session->read('co-select.action'),
                       'co' => $ucos[0]['Co']['id']);
    
            $this->redirect(array_merge($r, $this->Session->read('co-select.args')));
          }
          else
          {
            // Multiple COs found
            
            $this->set('cos', $ucos);
          }
        }
      }
      else
      {
        // Return from form to select CO

        $r = array('controller' => $this->Session->read('co-select.controller'),
                   'action' => $this->Session->read('co-select.action'),
                   'co' => $this->data['Co']['co']);

        $this->redirect(array_merge($r, $this->Session->read('co-select.args')));
      }
    }
  }
?>