<?php
/**
 * COmanage Registry Identifiers Controller
 *
 * Copyright (C) 2010-12 University Corporation for Advanced Internet Development, Inc.
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
 * @since         COmanage Registry v0.1
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */

App::uses("MVPAController", "Controller");

class IdentifiersController extends MVPAController {
  // Class name, used by Cake
  public $name = "Identifiers";
  
  // Establish pagination parameters for HTML views
  public $paginate = array(
    'limit' => 25,
    'order' => array(
      'identifier' => 'asc'
    )
  );
  
  /**
   * Autoassign identifiers for a CO Person.
   * - precondition: $this->request->params holds CO ID and CO Person ID
   * - postcondition: Session flash message updated (HTML) or HTTP status returned (REST)
   * - postcondition: Redirect issued (HTML)
   *
   * @since  COmanage Registry v0.6
   */  
  
  function assign() {
    if($this->restful) {
      // We manually run a few of the steps in StandardController
      
      if(!$this->checkRestPost($this->request->data)) {
        return;
      }
      
      $coid = $this->cur_co['Co']['id'];
      
      $personData = $this->parsePersonID($this->request->data);
      
      if(isset($personData['copersonid'])) {
        $copersonid = $personData['copersonid'];
      } else {
        $this->restResultHeader(403, "No Person Specified");
        return;
      }
    } else {
      // While the controller doesn't require_co, this method does.
      
      $coid = $this->parseCOID($this->request->data);
      $copersonid = Sanitize::html($this->request->params['named']['copersonid']);
    }
    
    if($coid != -1) {
      // Assign the identifiers, then walk through the result array and generate a flash message
      $res = $this->Identifier->assign($coid, $copersonid);
      
      if(!empty($res)) {
        // Loop through the results and build result messages
        
        $errs = "";             // Unexpected errors
        $assigned = array();    // Identifiers that were assigned
        $existed = array();     // Identifiers that already existed
        
        foreach(array_keys($res) as $type) {
          if($res[$type] == 2) {
            $existed[] = $type;
          } elseif($res[$type] == 1) {
            $assigned[] = $type;
          } else {
            $errs .= $type . ": " . $res[$type] . "<br />\n";
          }
        }
        
        if($this->restful) {
          if($errs != "") {
            $this->restResultHeader(500, "Other Error");
          } else {
            $this->restResultHeader(200, "OK");
          }
        } else {
          if($errs != "") {
            $this->Session->setFlash($errs, '', array(), 'error');
          }
          
          if(!empty($assigned)) {
            $this->Session->setFlash(_txt('rs.ia.ok') . " (" . implode(',', $assigned) . ")",
                                     '', array(), 'success');
          }
          
          if(!empty($existed)) {
            $this->Session->setFlash(_txt('er.ia.already') . " (" . implode(',', $existed) . ")",
                                     '', array(), 'info');
          }
        }
      } else {
        if($this->restful) {
          $this->restResultHeader(200, "OK");
        } else {
          $this->Session->setFlash(_txt('er.ia.none'), '', array(), 'info');
        }
      }
    } else {
      if($this->restful) {
        $this->restResultHeader(403, "CO Does Not Exist");
      } else {
        $this->Session->setFlash(_txt('er.co.unk'), '', array(), 'error');
      }
    }
    
    if(!$this->restful) {
      // Redirect to CO Person view
      $rargs['controller'] = 'co_people';
      $rargs['action'] = 'edit';
      $rargs[] = $copersonid;
      $rargs['co'] = $this->cur_co['Co']['id'];
      
      $this->redirect($rargs);
    }
  }
  
  /**
   * Callback before other controller methods are invoked or views are rendered.
   * - postcondition: Redirect may be issued
   *
   * @since  COmanage Registry v0.6
   */
  
  function beforeFilter() {
    parent::beforeFilter();
    
    // Identifier supports Extended Types. Figure out what types are defined
    // in order for the views to render properly.
    
    $this->set('identifier_types', $this->Identifier->types($this->cur_co['Co']['id']));
  }
  
  /**
   * Perform any dependency checks required prior to a write (add/edit) operation.
   * This method is intended to be overridden by model-specific controllers.
   * - postcondition: Session flash message updated (HTML) or HTTP status returned (REST)
   *
   * @since  COmanage Registry v0.6
   * @param  Array Request data
   * @param  Array Current data
   * @return boolean true if dependency checks succeed, false otherwise.
   */
  
  function checkWriteDependencies($reqdata, $curdata = null) {
    // Check to make sure that a given identifier of a given type is not already
    // in use within a CO. In order to prevent potential conflicts with Identifier
    // Assignment (eg: an admin manually changes an identifier to one that would
    // be next assigned sequentially) this method creates a transaction that
    // checkWriteFollowups commits.
    
    if(isset($this->cur_co)) {
      $dbc = $this->Identifier->getDataSource();
      
      if(isset($this->cur_co)) {
        $dbc->begin();
      }
      
      if(!$this->Identifier->checkAvailability($reqdata['Identifier']['identifier'],
                                               $reqdata['Identifier']['type'],
                                               $this->cur_co['Co']['id'])) {
        if($this->restful)
          $this->restResultHeader(403, "Identifier In Use");
        else
          $this->Session->setFlash(_txt('er.ia.exists', array(Sanitize::html($reqdata['Identifier']['identifier']))), '', array(), 'error');   
        
        $dbc->rollback();
        return false;
      }
    }
    // else don't do this check for org identities
    
    return true;
  }
  
  /**
   * Perform any followups following a write operation.  Note that if this
   * method fails, it must return a warning or REST response, but that the
   * overall transaction is still considered a success (add/edit is not
   * rolled back).
   * This method is intended to be overridden by model-specific controllers.
   *
   * @since  COmanage Registry v0.6
   * @param  Array Request data
   * @param  Array Current data
   * @return boolean true if dependency checks succeed, false otherwise.
   */
  
  function checkWriteFollowups($reqdata) {
    $dbc = $this->Identifier->getDataSource();
    
    if(isset($this->cur_co)) {
      // Commit under all circumstances
      $dbc->commit();
    }
    
    return true;
  }
 
  /**
   * Delete an Identifiers Object
   * - precondition: <id> must exist
   * - postcondition: Session flash message updated (HTML) or HTTP status returned (REST)
   * - postcondition: On success, all related data (any table with an <object>_id column) is deleted
   *
   * @since  COmanage Registry v0.7
   * @param  integer Object identifier (eg: cm_co_groups:id) representing object to be deleted
   */  
  function delete($id) {
    $this->redirectTab = 'id';

    parent::delete($id);
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
    $pids = $this->parsePersonID($this->request->data);
    
    // If we're manipulating an Org Person, any CO admin or COU admin can edit,
    // but if we're manipulating a CO Person, only the CO admin or appropriate
    // COU admin (an admin of a COU in the current CO) can edit
    
    $admin = false;
    
    if(($pids['copersonid'] && ($cmr['coadmin'] || $cmr['couadmin']))
       || ($pids['orgidentityid'] && ($cmr['admin'] || $cmr['coadmin'] || $cmr['subadmin'])))
      $admin = true;
    
    // Construct the permission set for this user, which will also be passed to the view.
    $p = array();
    
    // Determine what operations this user can perform
    
    // Add a new Identifier?
    $p['add'] = ($cmr['cmadmin'] || $admin);
    
    // Assign (autogenerate) Identifiers?
    $p['assign'] = ($cmr['cmadmin'] || $admin);
    
    // Delete an existing Identifier?
    $p['delete'] = ($cmr['cmadmin'] || $admin);
    
    // Edit an existing Identifier?
    $p['edit'] = ($cmr['cmadmin'] || $admin);
    
    // View all existing Identifier?
    $p['index'] = ($cmr['cmadmin'] || $admin);
    
    // View an existing Identifier?
    $p['view'] = ($cmr['cmadmin'] || $admin);

    $this->set('permissions', $p);
    return($p[$this->action]);
  }

  /**
   * Perform a redirect back to the controller's default view.
   * - postcondition: Redirect generated
   *
   * @since  COmanage Registry v0.7
   */
  
  function performRedirect() {

    $this->redirectTab = 'id';

    parent::performRedirect();
  }

}
