<?php
  /*
   * COmanage Registry CO Enrollment Attributes Controller
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

  include APP."controllers/standard_controller.php";
  
  class CoEnrollmentAttributesController extends StandardController {
    // Class name, used by Cake
    var $name = "CoEnrollmentAttributes";
    
    // Cake Components used by this Controller
    var $components = array('RequestHandler',  // For REST
                            'Security',
                            'Session');
    
    // Establish pagination parameters for HTML views
    var $paginate = array(
      'limit' => 25,
      'order' => array(
        'CoEnrollmentAttribute.attribute' => 'asc'
      )
    );

    function add()
    {
      // Add an Enrollment Attribute.
      //
      // Parameters (in $this->data):
      // - Model specific attributes
      //
      // Preconditions:
      //     None
      //
      // Postconditions:
      // (1) On success, new Enrollment Attribute created
      // (2) Session flash message updated (HTML) or HTTP status returned (REST)
      // (3) $<object>_id or $invalid_fields set (REST)
      //
      // Returns:
      //   Nothing
      
      // If order is not specified, figure out the highest current order and modify
      // the request. There's a slight but unimportant race condition here if two
      // attributes are added at exactly the same time... they might end up with
      // the same ordr value. It doesn't really matter, though, since multiple
      // attributes can have the same ordr.
      
      if(!empty($this->data) &&
         (!isset($this->data['CoEnrollmentAttribute']['ordr'])
          || $this->data['CoEnrollmentAttribute']['ordr'] == ''))
      {
        $args['fields'][] = "MAX(ordr)+1 as m";
        $args['order'][] = "m";
        
        $o = $this->CoEnrollmentAttribute->find('first', $args);
        
        if(!empty($o))
          $this->data['CoEnrollmentAttribute']['ordr'] = $o[0]['m'];
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

      // Strictly speaking, this controller doesn't require a CO except to redirect/render views.
      // Figure out the CO ID associated with the current enrollment flow. We'll specifically
      // not set $this->cur_co since it will break things like pagination setup.
      
      $coefid = -1;
      
      if(isset($this->params['named']['coef']))
        $coefid = $this->params['named']['coef'];
      elseif(isset($this->data))
        $coefid = $this->data['CoEnrollmentAttribute']['co_enrollment_flow_id'];
      
      $this->CoEnrollmentAttribute->CoEnrollmentFlow->id = $coefid;
      $coid = $this->CoEnrollmentAttribute->CoEnrollmentFlow->field('co_id');

      if(!empty($coid))
      {
        $this->set("coid", $coid);
        
        // Assemble the set of available attributes for the view to render
        
        $this->set('available_attributes', $this->CoEnrollmentAttribute->availableAttributes($coid));
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
      
      // Construct the permission set for this user, which will also be passed to the view.
      $p = array();
      
      // Determine what operations this user can perform
      
      // Add a new CO Enrollment Attribute?
      $p['add'] = ($cmr['cmadmin'] || $cmr['coadmin']);
      
      // Delete an existing CO Enrollment Attribute?
      $p['delete'] = ($cmr['cmadmin'] || $cmr['coadmin']);
      
      // Edit an existing CO Enrollment Attribute?
      $p['edit'] = ($cmr['cmadmin'] || $cmr['coadmin']);
      
      // View all existing CO Enrollment Attributes?
      $p['index'] = ($cmr['cmadmin'] || $cmr['coadmin']);
      
      // View an existing CO Enrollment Attributes?
      $p['view'] = ($cmr['cmadmin'] || $cmr['coadmin']);

      $this->set('permissions', $p);
      return($p[$this->action]);
    }

    function paginationConditions()
    {
      // Determine the conditions for pagination of the index view, when rendered
      // via the UI.
      //
      // Parameters:
      //   None
      //
      // Preconditions:
      //     None
      //
      // Postconditions:
      //     None
      //
      // Returns:
      // - An array suitable for use in $this->paginate
      
      // Only retrieve attributes in the current enrollment flow
      
      return(array(
        'CoEnrollmentAttribute.co_enrollment_flow_id' => $this->params['named']['coef']
      ));
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
      
      // Append the enrollment flow ID to the redirect
      
      if(isset($this->data['CoEnrollmentAttribute']['co_enrollment_flow_id']))
        $coefid = $this->data['CoEnrollmentAttribute']['co_enrollment_flow_id'];
      elseif(isset($this->params['named']['coef']))
        $coefid = Sanitize::html($this->params['named']['coef']);
      
      $this->redirect(array('controller' => 'co_enrollment_attributes',
                            'action' => 'index',
                            'coef' => $coefid));
    }
  }
?>