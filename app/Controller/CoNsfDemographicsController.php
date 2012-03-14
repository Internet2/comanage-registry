<?php
/**
 * COmanage Registry CO NSF Demographics Controller
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
 * @copyright     Copyright (C) 2011-12 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.3
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */

App::import('Sanitize');
App::uses("StandardController", "Controller");

class CoNsfDemographicsController extends StandardController {
  // Class name, used by Cake
  public $name = "CoNsfDemographics";

  // Establish pagination parameters for HTML views
  public $paginate = array(
    'limit' => 25,
    'order' => array(
      'co_person_id' => 'asc'
    )
  );

  public $requires_co = true;

  /**
   * Callback after controller methods are invoked but before views are rendered.
   * - postcondition: Set $race_options, $disability_options
   *
   * @since  COmanage Registry v0.3
   */
  
  function beforeRender() {
    global $cm_lang, $cm_texts;

    // Loop check only needed for the edit page, all options should be clear for add
    if($this->action == 'edit')
    {
      // Pass previously selected options
      if($this->restful)
        $options = $this->CoNsfDemographic->extractOptions($this->request['data']['CoNsfDemographics'][0]);
      else
        $options = $this->CoNsfDemographic->extractOptions($this->data['CoNsfDemographic']);

      if(isset($options['race']))
        $this->set('race_options', $options['race']);
      if(isset($options['disability']))
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
        if(isset($d['race']))
          sort($d['race']);
        if(isset($d['disability']))
          sort($d['disability']);

        // Overwrite default viewVars
        $factoredDemo[$key]['CoNsfDemographic']['race']       = $d['race'];
        $factoredDemo[$key]['CoNsfDemographic']['disability'] = $d['disability'];

        $this->set('co_nsf_demographics', $factoredDemo);
      }
    }
  }

  /**
   * Perform any dependency checks required prior to a write (add/edit) operation.
   * - postcondition: Session flash message updated (HTML) or HTTP status returned (REST)
   *
   * @since  COmanage Registry v0.3
   * @param  Array Request data
   * @param  Array Current data
   * @return boolean true if dependency checks succeed, false otherwise.
   */
  
  function checkWriteDependencies($reqdata, $curdata = null) {
    // Look up id to check validity
    if($this->restful) {
      $personid = $this->request['data']['CoNsfDemographics'][0]['CoPersonId'];
    } else {
      $cmr = $this->calculateCMRoles();
      $personid = $cmr['copersonid'];
    }

    $args =  array(
      'conditions' => array(
        'CoPerson.id' => $personid
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
        'CoNsfDemographic.co_person_id' => $personid,
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
    return true;
  }

  /**
   * Convert data to prepare for saving to database
   * - precondition: $this->request->data['CoNsfDemographic'] holds data to be saved
   * - postcondition: $this->request->data['CoNsfDemographic'] is modified
   *
   * @since  COmanage Registry v0.4
   */

  function convertData(){
    if(!empty($this->request->data))
    {
      // Data doesn't already exist so encode for writing
      if($this->restful)
        $encoded = $this->CoNsfDemographic->encodeOptions($this->request['data']['CoNsfDemographics'][0]);
      else
        $encoded = $this->CoNsfDemographic->encodeOptions($this->request->data['CoNsfDemographic']);

      if(isset($encoded['race']))
        $this->request->data['CoNsfDemographic']['race']     = $encoded['race'];
      if(isset($encoded['disability']))
        $this->request->data['CoNsfDemographic']['disability'] = $encoded['disability'];
    }
  }

  /**
   * Override add of StandardController to convert data before calling it
   * - precondition: $this->request->data['CoNsfDemographic'] holds data to be saved
   * - postcondition: $this->request->data['CoNsfDemographic'] is modified
   *
   * @since  COmanage Registry v0.4
   */

  function add() {
    $this->convertData();
    parent::add();
  }

  /**
   * Override edit of StandardController to convert data before calling it
   * - precondition: $this->request->data['CoNsfDemographic'] holds data to be saved
   * - postcondition: $this->request->data['CoNsfDemographic'] is modified
   *
   * @since  COmanage Registry v0.4
   * @param int id
   */

  function edit($id) {
    $this->convertData();
    parent::edit($id);
  }
  
  /**
   * Redirect to add if no record found.
   * - postcondition: Redirect issued
   * 
   * @since  COmanage Registry v0.3
   */

  function editSelf() {
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

  /**
   * Authorization for this Controller, called by Auth component
   * - precondition: Session.Auth holds data used for authz decisions
   * - postcondition: $permissions set with calculated permissions
   *
   * @since  COmanage Registry v0.3
   * @return Array Permissions
   */
  
  function isAuthorized() {
    $cmr = $this->calculateCMRoles();
    $pids = $this->parsePersonID($this->request->data);

    // Is this our own record?
    $self = false;

    // If a row is passed in, get copersonid associated with this row
    if(!empty($this->request->params['pass'][0]))
    {
      $args = array('conditions' => array('CoNsfDemographic.id' => $this->request->params['pass'][0]));
      $row = $this->CoNsfDemographic->find('first', $args);
    }

    // Can edit self if a member of the co and is not trying to edit someone else's record
    if($cmr['comember'] 
      && $cmr['copersonid'] 
      && (!isset($this->request->params['pass'][0]) 
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

  /**
   * Determine the conditions for pagination of the index view, when rendered via the UI.
   *
   * @since  COmanage Registry v0.3
   * @return Array An array suitable for use in $this->paginate
   */
  
  function paginationConditions() {
    if(isset($this->cur_co))
    {
      // Only retrieve members of the current CO
      return array('CoPerson.co_id' => $this->cur_co['Co']['id']);
    }

    return array();
  }

  /**
   * Perform a redirect back to the controller's default view.
   * - postcondition: Redirect generated
   *
   * @since  COmanage Registry v0.3
   */
  
  function performRedirect() {
    if(isset($this->params['data']['CoNsfDemographic']['co_person_id'])) {
      // If person's id is available, redirect to the person's edit page
      $args = array(
        'controller' => 'co_people',
        'action'     => 'edit',
        'co'         => $this->cur_co['Co']['id'],
        Sanitize::html($this->params['data']['CoNsfDemographic']['co_person_id'])
      );
    } elseif($this->viewVars['permissions']['index'] == true) {
      // If the id is not available and we have permission to view index, go there
      $args = array(
        'controller' => 'co_nsf_demographics',
        'action'     => 'index',
        'co'         => $this->cur_co['Co']['id']
      );
    } else {
      // Otherwise, just go to front page
      $args = '/';
    }

    $this->redirect($args);
  }
}
