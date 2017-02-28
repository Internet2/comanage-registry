<?php
/**
 * COmanage Registry CO NSF Demographics Controller
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
      if($this->request->is('restful'))
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
    parent::beforeRender();
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
    if($this->request->is('restful')) {
      $personid = $reqdata['CoNsfDemographic'][0]['co_person_id'];
    } else {
      $personid = $reqdata['CoNsfDemographic']['co_person_id'];
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
      if($this->request->is('restful'))
        $this->Api->restResultHeader(403, "CoPerson Does Not Exist");
      else
        $this->Flash->set(_txt('er.cop.unk'), array('key' => 'error'));

      return false;
    }

    // Does a row exist in the database for this id?
    $args =  array(
      'conditions' => array(
        'CoNsfDemographic.co_person_id' => $personid,
        'CoPerson.co_id'                => $this->cur_co['Co']['id']
      )
    );
    $row = $this->CoNsfDemographic->find('first', $args);
    
    if(!empty($row['CoNsfDemographic']['id'])) {
      $rowId = $row['CoNsfDemographic']['id'];
    }

    // If a row for a CoPerson Id already exists when trying to add a new row, throw error
    if(!empty($rowId) && ($this->action == 'add'))
    {
      if($this->request->is('restful'))
        $this->Api->restResultHeader(403, "CoNsfDemographic Data Already Exists");
      else
        $this->Flash->set(_txt('er.nd.already'), array('key' => 'error'));

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
      if($this->request->is('restful'))
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
   * Authorization for this Controller, called by Auth component
   * - precondition: Session.Auth holds data used for authz decisions
   * - postcondition: $permissions set with calculated permissions
   *
   * @since  COmanage Registry v0.3
   * @return Array Permissions
   */
  
  function isAuthorized() {
    $roles = $this->Role->calculateCMRoles();
    
    $managed = false;
    $self = false;
    
    if(!empty($roles['copersonid'])) {
      if($this->action == 'add') {
        // Find the CO Person ID
        
        $pid = null;
        
        if(!empty($this->request->params['named']['copersonid'])) {
          $pid = $this->request->params['named']['copersonid'];
        } elseif(!empty($this->request->data['CoNsfDemographic']['co_person_id'])) {
          $pid = $this->request->data['CoNsfDemographic']['co_person_id'];
        }
        
        if($pid) {
          $managed = $this->Role->isCoOrCouAdminForCoPerson($roles['copersonid'], $pid);
          
          if($roles['copersonid'] == $pid) {
            $self = true;
          }
        }
      } elseif(!empty($this->request->params['pass'][0])) {
        // Determine the CO Person associated with this entry
        
        $copid = $this->CoNsfDemographic->field('co_person_id', array('CoNsfDemographic.id' => $this->request->params['pass'][0]));
        
        if($copid) {
          $managed = $this->Role->isCoOrCouAdminForCoPerson($roles['copersonid'], $copid);
          
          if($roles['copersonid'] == $copid) {
            $self = true;
          }
        }
      }
    }
    
    // Construct the permission set for this user, which will also be passed to the view.
    $p = array();
    
    // Add a new NSF Demographic Record?
    $p['add'] = ($roles['cmadmin']
                 || ($managed && ($roles['coadmin'] || $roles['couadmin']))
                 || $self);
    
    // Delete an existing NSF Demographic Record?
    $p['delete'] = ($roles['cmadmin']
                    || ($managed && ($roles['coadmin'] || $roles['couadmin']))
                    || $self);
    
    // Edit an existing NSF Demographic Record?
    $p['edit'] = ($roles['cmadmin']
                  || ($managed && ($roles['coadmin'] || $roles['couadmin']))
                  || $self);
    
    // View all existing NSF Demographic Records?
    $p['index'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // View an existing NSF Demographic Record?
    $p['view'] = ($roles['cmadmin']
                  || ($managed && ($roles['coadmin'] || $roles['couadmin']))
                  || $self);
    
    $this->set('permissions', $p);
    return $p[$this->action];
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
        'action'     => 'canvas',
        filter_var($this->params['data']['CoNsfDemographic']['co_person_id'],FILTER_SANITIZE_SPECIAL_CHARS)
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
