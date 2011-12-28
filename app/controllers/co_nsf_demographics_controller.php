<?php
  /*
   * COmanage Registry CoNsfDemographics Controller
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

  App::import('Sanitize');
  include APP."controllers/standard_controller.php";

  class CoNsfDemographicsController extends StandardController {
    // Class name, used by Cake
    var $name = "CoNsfDemographics";

    // Cake Components used by this Controller
    var $components = array('RequestHandler',  // For REST
                            'Security',
                            'Session');

    // Establish pagination parameters for HTML views
    var $paginate = array(
      'limit' => 25,
      'order' => array(
        'co_person_id' => 'asc'
      )
    );

    var $requires_co = true;

    function paginationConditions()
    {
      // Overrides the default in standard controller to limit the results for the index page to the current CO
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

      if(isset($this->cur_co))
      {
        // Only retrieve members of the current CO
        return array('CoPerson.co_id' => $this->cur_co['Co']['id']);
      }

      return array();
    }

    function beforeRender()
    {
      // Perform translation of previously saved encoded attributes for edit or display
      //
      // Parameters:
      //     None
      //
      // Preconditions:
      //     None
      //
      // Postconditions:
      //     Sets race_options, disability_options
      //
      // Returns:
      //     None

      global $cm_lang, $cm_texts;

      // Loop check only needed for the edit page, all options should be clear for add
      if($this->action == 'edit')
      {
        // Pass previously selected options
        $options = $this->CoNsfDemographic->extractOptions($this->data['CoNsfDemographic']);
        $this->set('race_options', $options['race']);
        $this->set('disability_options', $options['disability']);
      }

      // Breaks out concatenated options for race and disability (without descriptions)
      if($this->action == 'index')
      {
        // Factor out demographics for display
        $factoredDemo = $this->viewVars['co_nsf_demographics'];
        foreach($factoredDemo as $key => $demo)
        {
          // Race and Disability
          $d = $this->CoNsfDemographic->extractOptions($demo['CoNsfDemographic'], true);
          sort($d['race']);
          sort($d['disability']);

          // Overwrite default viewVars
          $factoredDemo[$key]['CoNsfDemographic']['race']       = $d['race'];
          $factoredDemo[$key]['CoNsfDemographic']['disability'] = $d['disability'];

          $this->set('co_nsf_demographics', $factoredDemo);
        }
      }
    }

    function checkWriteDependencies($curdata = null)
    {
      // Before writing, check for existing data.  If none exists, concatenate data from the form
      // to store in one field in the database
      //
      // Parameters:
      // - For edit operations, $curdata will hold current data
      //
      // Preconditions:
      // (1) $this->data holds request data
      //
      // Postconditions:
      // None 
      //
      // Returns:
      // - true if dependency checks succeed, false otherwise.

      // Look up id
      $cmr = $this->calculateCMRoles();

      // Is this a valid co person id?
      $args =  array(
        'conditions' => array(
          'CoPerson.id' => $cmr['copersonid']
        )
      );
      $rowCount = $this->CoNsfDemographic->CoPerson->find('count', $args);

      // If not valid, return error
      if($rowCount < 1)
      {
        if($this->restful)
          $this->restResultHeader(403, "CoPerson Does Not Exist");
        else
          $this->Session->setFlash(_txt('er.cop.unk'), '', array(), 'error');

        return(false);
      }

      // Does a row exist in the database for this id?
      $args =  array(
        'conditions' => array(
          'CoNsfDemographic.co_person_id' => $cmr['copersonid'],
          'CoPerson.co_id'                => $this->cur_co['Co']['id']
        )
      );
      $row = $this->CoNsfDemographic->find('first', $args);
      $rowId = $row['CoNsfDemographic']['id'];

      // If a row for a CoPerson Id already exists when trying to add a new row, throw error
      if(!empty($rowId) && ($this->action == 'add'))
      {
        if($this->restful)
          $this->restResultHeader(403, "CoNsfDemographic Data Already Exists");
        else
          $this->Session->setFlash(_txt('er.nd.already'), '', array(), 'error');

        return false;
      }

      // Data doesn't already exist so encode for writing
      $encoded = $this->CoNsfDemographic->encodeOptions($this->params['data']['CoNsfDemographic']);

      $this->params['data']['CoNsfDemographic']['race']       = $encoded['race'];
      $this->params['data']['CoNsfDemographic']['disability'] = $encoded['disability'];

      return true;
    }

    function editSelf()
    {
      // Overrides default edit function of standard controller to redirect to add if not found
      //
      // Parameters:
      //   id - Object identifier representing object to be retrieved
      //
      // Preconditions:
      //   None
      //
      // Postconditions:
      //   None
      //
      // Returns:
      //   None

      // Look up id
      $cmr = $this->calculateCMRoles();

      // Does a row exist in the database for this id?
      $args =  array(
        'conditions' => array(
          'CoNsfDemographic.co_person_id' => $cmr['copersonid'],
          'CoPerson.co_id'                => $this->cur_co['Co']['id']
        )
      );
      $row = $this->CoNsfDemographic->find('first', $args);
      $rowId = $row['CoNsfDemographic']['id'];

      if(empty($rowId))
      {
        // No row exists, so add one
        $args = array(
          'action'       => 'add',
          'co'           => $this->cur_co['Co']['id'],
          'co_person_id' => $cmr['copersonid']
        );
      }
      else
      {
        // Row found so edit it
        $args = array(
          'action' => 'edit',
          $rowId,
          'co'     => $this->cur_co['Co']['id']
        );
      }

      $this->redirect($args);
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
      $pids = $this->parsePersonID($this->data);

      // Is this our own record?
      $self = false;

      // If a row is passed in, get copersonid associated with this row
      if(!empty($this->params['pass'][0]))
      {
        $args = array('conditions' => array('CoNsfDemographic.id' => $this->params['pass'][0]));
        $row = $this->CoNsfDemographic->find('first', $args);
      }

      // Can edit self if a member of the co and is not trying to edit someone else's record
      if($cmr['comember'] 
        && $cmr['copersonid'] 
        && (!isset($this->params['pass'][0]) 
            || ($cmr['copersonid'] == $row['CoNsfDemographic']['co_person_id'])))
      {
        $self = true;
      }

      // Construct the permission set for this user, which will also be passed to the view.
      $p = array();

      // Determine what operations this user can perform

      // Add a new Demographic?  Can be done if none exist yet
      $args = array('conditions' => array('CoNsfDemographic.id' => $cmr['copersonid']));
      $row = $this->CoNsfDemographic->find('first', $args);

      // Can only add a new one if admin or row doesn't already exist
      $p['add'] = ($cmr['cmadmin'] || $cmr['admin'] || ($self && empty($row)));

      // Delete an existing Demographic?
      $p['delete'] = ($cmr['cmadmin'] || $cmr['admin'] || $self);

      // Edit an existing Demographic?
      $p['edit'] = ($cmr['cmadmin'] || $cmr['admin'] || $self);

      // Edit own Demographic?
      $p['editself'] = ($cmr['cmadmin'] || $cmr['admin'] || $self);

      // View all existing Demographic?
      $p['index'] = ($cmr['cmadmin'] || $cmr['admin']);

      // View an existing Demographic?
      $p['view'] = ($cmr['cmadmin'] || $cmr['admin'] || $self);

      $this->set('permissions', $p);
      return $p[$this->action];
    }

    function performRedirect()
    {
      // Perform redirect to co_people instead of controller's default view.
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

      $args = array(
        'controller' => 'co_people',
        'action'     => 'edit',
        'co'         => $this->cur_co['Co']['id'],
        Sanitize::html($this->params['data']['CoNsfDemographic']['co_person_id'])
      );

      $this->redirect($args);
    }
  }
?>
