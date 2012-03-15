<?php
/**
 * COmanage Registry COU Controller
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

class CousController extends StandardController {
  // Class name, used by Cake
  public $name = "Cous";
  
  // Establish pagination parameters for HTML views
  public $paginate = array(
    'limit' => 25,
    'order' => array(
      'Cou.name' => 'asc'
    )
  );
  
  // This controller needs a CO to be set
  public $requires_co = true;

  /**
   * Perform filtering of COU parent options for dropdown.
   * - postcondition: parent_options set
   *
   * @since  COmanage Registry v0.3
   */
  
  function beforeRender() {
    // This loop is concerned with computing the options for parents 
    // to display for a dropdown menu or similar for the GUI when the 
    // user is editing or adding a COU.
    //
    // REST calls do not need to compute options for parents.
    if(!$this->restful) {
      // Loop check only needed for the edit page, model does not know CO for new COUs
      if($this->action == 'edit') {
        $options = $this->Cou->potentialParents($this->request->data['Cou']['id']);
      } else {
        $optionArrays = $this->Cou->findAllByCoId($this->cur_co['Co']['id']);
        $options = Set::combine($optionArrays, '{n}.Cou.id','{n}.Cou.name');
      }
      
      $this->set('parent_options', $options);
    }
    
    parent::beforeRender();
  }

  /**
   * Perform any dependency checks required prior to a delete operation.
   * This method is intended to be overridden by model-specific controllers.
   * - postcondition: Session flash message updated (HTML) or HTTP status returned (REST)
   *
   * @since  COmanage Registry v0.2
   * @param  Array Current data
   * @return boolean true if dependency checks succeed, false otherwise.
   */
  
  function checkDeleteDependencies($curdata) {
    $couppl = $this->Cou->CoPersonRole->findAllByCouId($curdata['Cou']['id']);
    
    if(!empty($couppl)) {
      // A COU can't be removed if anyone is still a member of it.
      
      if($this->restful)
        $this->restResultHeader(403, "CoPersonRole Exists");
      else
        $this->Session->setFlash(_txt('er.cou.copr', array($curdata['Cou']['name'])), '', array(), 'error');
      
      return(false);
    }
    // A COU can't be removed if it has children.

    $childCous = $curdata['ChildCou'];

    if(!empty($childCous)) {
      if($this->restful)
        $this->restResultHeader(403, "Child COU Exists");
      else
        $this->Session->setFlash(_txt('er.cou.child', array(Sanitize::html($curdata['Cou']['name']))), '', array(), 'error');

      return(false);
    }

    return(true);
  }

  /**
   * Perform any dependency checks required prior to a write (add/edit) operation.
   * This method is intended to be overridden by model-specific controllers.
   * - postcondition: Session flash message updated (HTML) or HTTP status returned (REST)
   *
   * @since  COmanage Registry v0.3
   * @param  Array Request data
   * @param  Array Current data
   * @return boolean true if dependency checks succeed, false otherwise.
   */
  
  function checkWriteDependencies($reqdata, $curdata = null) {
    if(!isset($curdata)
       || ($curdata['Cou']['name'] != $reqdata['Cou']['name'])) {
      // Make sure name doesn't exist within this CO
      $args['conditions']['Cou.name'] = $reqdata['Cou']['name'];
      $args['conditions']['Cou.co_id'] = $reqdata['Cou']['co_id'];
      
      $x = $this->Cou->find('all', $args);
      
      if(!empty($x))
      {
        if($this->restful)
          $this->restResultHeader(403, "Name In Use");
        else
          $this->Session->setFlash(_txt('er.cou.exists', array($reqdata['Cou']['name'])), '', array(), 'error');          

        return(false);
      }
    }
    
    // Parent COU must be in same CO as child

    // Name of parent
    $parentCou = $reqdata['Cou']['parent_id'];

    if(isset($parentCou) && $parentCou != "")
    {
      if($this->action != 'add')
      {
        // Parent not found in CO
        if(!($this->Cou->isCoMember($parentCou)))
        {
          if($this->restful)
            $this->restResultHeader(403, "Wrong CO");
          else
            $this->Session->setFlash(_txt('er.cou.sameco', array($reqdata['CoGroupMember']['co_group_id'])), '', array(), 'error');
          return(false);
        }

        // Check if parent would cause a loop
        if($this->Cou->isChildCou($reqdata['Cou']['id'], $parentCou))
        {
          if($this->restful)
            $this->restResultHeader(403, "Parent Would Create Cycle");
          else
            $this->Session->setFlash(_txt('er.cou.cycle', array($reqdata['CoGroupMember']['co_group_id'])), '', array(), 'error');
          return(false);
        }
      }
    }
    return(true);
  }

  /**
   * Perform any followups following a write operation.  Note that if this
   * method fails, it must return a warning or REST response, but that the
   * overall transaction is still considered a success (add/edit is not
   * rolled back).
   * This method is intended to be overridden by model-specific controllers.
   * - postcondition: Session flash message updated (HTML) or HTTP status returned (REST)
   *
   * @since  COmanage Registry v0.2
   * @param  Array Request data
   * @param  Array Current data
   * @return boolean true if dependency checks succeed, false otherwise.
   */
 
  function checkWriteFollowups($reqdata, $curdata = null) {
    // Create an admin Group for the new COU. As of now, we don't try to populate
    // it with the current user, since it may not be desirable for the current
    // user to be a member of the new CO.
    
    // Only do this via HTTP.
    
    if(!$this->restful && $this->action == 'add')
    {
      if(isset($this->Cou->id))
      {
        $a['CoGroup'] = array(
          'co_id' => $reqdata['Cou']['co_id'],
          'name' => 'admin:' . $reqdata['Cou']['name'],
          'description' => _txt('fd.group.desc.adm', array($reqdata['Cou']['name'])),
          'open' => false,
          'status' => 'A'
        );
        
        if(!$this->Cou->Co->CoGroup->save($a))
        {
          $this->Session->setFlash(_txt('er.cou.gr.admin'), '', array(), 'info');
          return(false);
        }
      }
    }

    return(true);
  }

  /**
   * Authorization for this Controller, called by Auth component
   * - precondition: Session.Auth holds data used for authz decisions
   * - postcondition: $permissions set with calculated permissions
   *
   * @since  COmanage Registry v0.2
   * @return Array Permissions
   */
  
  function isAuthorized() {
    $cmr = $this->calculateCMRoles();             // What was authenticated
    
    // Construct the permission set for this user, which will also be passed to the view.
    $p = array();
    
    // Determine what operations this user can perform
    
    // Add a new COU?
    $p['add'] = ($cmr['cmadmin'] || $cmr['coadmin']);
    
    // Delete an existing COU?
    $p['delete'] = ($cmr['cmadmin'] || $cmr['coadmin']);
    
    // Edit an existing COU?
    $p['edit'] = ($cmr['cmadmin'] || $cmr['coadmin']);
    
    // View all existing COUs?
    $p['index'] = ($cmr['cmadmin'] || $cmr['coadmin']);
    
    // View an existing COU?
    $p['view'] = ($cmr['cmadmin'] || $cmr['coadmin']);

    $this->set('permissions', $p);
    return($p[$this->action]);
  }
}
