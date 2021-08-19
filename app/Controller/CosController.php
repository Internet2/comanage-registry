<?php
/**
 * COmanage Registry CO Controller
 *
 * Portions licensed to the University Corporation for Advanced Internet
 * Development, Inc. ("UCAID") under one or more contributor license agreements.
 * See the NOTICE file distributed with this work for additional information
 * regarding copyright ownership.
 *
 * UCAID licenses this file to you under the Apache License, Version 2.0
 * (the "License"); you may not use this file except in compliance with the
 * License. You may obtain a copy of the License at:
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 * 
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.1
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::uses("StandardController", "Controller");

class CosController extends StandardController {
  // Class name, used by Cake
  public $name = "Cos";
    
  // Establish pagination parameters for HTML views
  public $paginate = array(
    'limit' => 25,
    'order' => array(
      'name' => 'asc'
    )
  );
  
  public $view_contains = array();
  
  public $edit_contains = array();
  
  public $delete_contains = array();
  
  // In order to delete a CO, we need to always use hard delete, since soft
  // deleting records will result in foreign key dependencies sticking around
  public $useHardDelete = true;
  
  /**
   * Perform any dependency checks required prior to a delete operation.
   * - postcondition: Session flash message updated (HTML) or HTTP status returned (REST)
   *
   * @since  COmanage Registry v0.1
   * @param  Array Current data
   * @return boolean true if dependency checks succeed, false otherwise.
   */
  
  function checkDeleteDependencies($curdata) {
    // Make sure this request isn't trying to delete the COmanage CO

    $name = $this->Co->field('name');

    if($name == DEF_COMANAGE_CO_NAME) {
      if($this->request->is('restful')) {
        $this->Api->restResultHeader(403, "Cannot Remove COmanage CO");
      } else {
        $this->Flash->set(_txt('er.co.cm.rm'), array('key' => 'error'));
      }
      
      return false;
    }
    
    return true;
  }
  
  /**
   * Perform any dependency checks required prior to a write (add/edit) operation.
   * - postcondition: Session flash message updated (HTML) or HTTP status returned (REST)
   *
   * @since  COmanage Registry v0.1
   * @param  Array Request data
   * @param  Array Current data
   * @return boolean true if dependency checks succeed, false otherwise.
   */
  
  function checkWriteDependencies($reqdata, $curdata = null) {
    if(isset($curdata)) {
      // Changes to COmanage CO are not permitted
      
      if($curdata['Co']['name'] == DEF_COMANAGE_CO_NAME) {
        if($this->request->is('restful')) {
          $this->Api->restResultHeader(403, "Cannot Edit COmanage CO");
        } else {
          $this->Flash->set(_txt('er.co.cm.edit'), array('key' => 'error'));
        }
        
        return false;
      }
    }
    
    if(!isset($curdata)
       || ($curdata['Co']['name'] != $reqdata['Co']['name'])) {
      // Make sure name doesn't exist
      $x = $this->Co->findByName($reqdata['Co']['name']);
      
      if(!empty($x)) {
        if($this->request->is('restful')) {
          $this->Api->restResultHeader(403, "Name In Use");
        } else {
          $this->Flash->set(_txt('er.co.exists', array($reqdata['Co']['name'])), array('key' => 'error')); 
        }
        
        return false;
      }
    }
    
    return true;
  }
   
  /**
   * Perform any followups following a write operation.  Note that if this
   * method fails, it must return a warning or REST response, but that the
   * overall transaction is still considered a success (add/edit is not
   * rolled back).
   * - postcondition: Session flash message updated (HTML) or HTTP status returned (REST)
   *
   * @since  COmanage Registry v0.1
   * @param  Array Request data
   * @param  Array Current data
   * @param  Array Original request data (unmodified by callbacks)
   * @return boolean true if dependency checks succeed, false otherwise.
   */
  
  function checkWriteFollowups($reqdata, $curdata = null, $origdata = null) {
    if(!empty($reqdata['Co']['name'])
       && !empty($curdata['Co']['name'])
       && $reqdata['Co']['name'] != $curdata['Co']['name']) {
      // The CO has been renamed, so update the relevant group descriptions.
      // (The CO name is not currently embedded in the group, just the description.)
      
      $this->Co->CoGroup->addDefaults($this->Co->id, null, true);
    }

    return true;
  }

  /**
   * Duplicate an existing CO.
   *
   * @since  COmanage Registry v3.2.0
   * @param  Integer $id CO ID
   */

  public function duplicate($id) {
    if($this->request->is('restful')) {
      if(!$id) {
        $this->Api->restResultHeader(400, "Invalid Fields");
      } else {
        try {
          $newCoId = $this->Co->duplicate($id);
          $this->Api->restResultHeader(201, "Added");
          $this->set('co_id', $newCoId);
        }
        catch(InvalidArgumentException $e) {
          $this->Api->restResultHeader(404, "CO Unknown");
        }
        catch(Exception $e) {
          $this->Api->restResultHeader(500, $e->getMessage());
        }
      }
    } else {
      try {
        $this->Co->duplicate($id);
        $this->Flash->set(_txt('rs.copy-a1', array(_txt('ct.cos.1'))), array('key' => 'success'));
      }
      catch(Exception $e) {
        $this->Flash->set($e->getMessage(), array('key' => 'error'));
      }

      $this->performRedirect();
    }
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
    $roles = $this->Role->calculateCMRoles();

    $readonly = false;


    if(!empty($this->request->params['pass'][0])) {
      $readonly = $this->Co->readonly(filter_var($this->request->params['pass'][0], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH | FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_BACKTICK));
    }


    // Construct the permission set for this user, which will also be passed to the view.
    $p = array();
    
    // Determine what operations this user can perform
    
    // Add a new CO?
    $p['add'] = $roles['cmadmin'];
    
    // Delete an existing CO?
    $p['deleteasync'] = $p['delete'] = (!$readonly && $roles['cmadmin']);
    
    // Duplicate an existing CO?
    $p['duplicate'] = $roles['cmadmin'];
    
    // Edit an existing CO?
    $p['edit'] = (!$readonly && $roles['cmadmin']);

    // Restore and CO marked as Garbage
    $p['restore'] = ($readonly && $roles['cmadmin']);

    // View all existing COs?
    $p['index'] = $roles['cmadmin'];
    
    // View an existing CO?
    $p['view'] = $roles['cmadmin'];
    
    $this->set('permissions', $p);
    return $p[$this->action];
  }
  
  /**
   * Select the CO for the current session.
   * - precondition: $this->request->data holds CO to select (optional)
   * - precondition: Session.Auth holds data used for authz decisions
   * - postcondition: If no CO is selected and no COs exist, the 'COmanage' CO is created and a redirect issued
   * - postcondition: If no CO is selected and the user is a member of exactly one CO, that CO is selected and a redirect issued
   * - postcondition: If no CO is selected and the user is a member of more than one CO, $cos is set and the view rendered
   *
   * @since  COmanage Registry v0.1
   */
  
  function select() {
    if(empty($this->request->data)) {
      // Set page title
      $this->set('title_for_layout', _txt('op.select-a', array(_txt('ct.cos.1'))));

      if($this->Session->check('Auth.User.cos')) {
        // Retrieve the list of the user's COs, but for admins we want all COs
        
        if(isset($this->viewVars['permissions']['select-all']) && $this->viewVars['permissions']['select-all'])
          $ucos = $this->Co->find('all');
        else {
          // Grab the COs from the session. We can't just use the session variable
          // because it's not a complete retrieval of CO data.
          
          $cos = $this->Session->read('Auth.User.cos');
          $coIds = array();
          
          foreach($cos as $co) {
            $coIds[] = $co['co_id'];
          }
          
          $args['conditions']['id'] = $coIds;
          $ucos = $this->Co->find('all', $args);
        }
        
        if(count($ucos) == 0) {
          // No memberships... could be because there are no COs
          
          $cos = $this->Co->find('all');
          
          if(count($cos) == 0) {
            $this->Flash->set(_txt('er.co.none'), array('key' => 'error'));
            $this->redirect(array('controller' => 'pages', 'action' => 'menu'));
          } else {
            $this->Flash->set(_txt('co.nomember'), array('key' => 'error'));
            $this->redirect(array('controller' => 'pages', 'action' => 'menu'));
          }
        }
        elseif(count($ucos) == 1) {
          // Exactly one CO found

          $r = array('controller' => $this->Session->read('co-select.controller'),
                     'action' => $this->Session->read('co-select.action'),
                     'co' => $ucos[0]['Co']['id']);
          
          if($this->Session->check('co-select.args'))
            $this->redirect(array_merge($r, $this->Session->read('co-select.args')));
          else
            $this->redirect($r);
        } else {
          // Multiple COs found
          
          $this->set('cos', $ucos);
        }
      }
    } else {
      // Return from form to select CO

      $r = array('controller' => $this->Session->read('co-select.controller'),
                 'action' => $this->Session->read('co-select.action'),
                 'co' => $this->data['Co']['co']);

      $this->redirect(array_merge($r, $this->Session->read('co-select.args')));
    }
  }

  /**
   * Restore the state of the CO to active
   *
   * @since  COmanage Registry v4.0.0
   */

  public function restore($id) {
    $this->Co->id = $id;
    $this->Co->saveField('status', TemplateableStatusEnum::Active);
    $coName = $this->Co->field('name', array('Co.id' => $id));
    $this->Flash->set(_txt('rs.updated', array(filter_var($coName,FILTER_SANITIZE_SPECIAL_CHARS))), array('key' => 'success'));

    // Issue a redirect to COs index
    $this->performRedirect();
  }


  /**
   * Schedule CO delete
   *
   * @since  COmanage Registry v4.0.0
   */

  public function deleteasync($id) {
    try {
      // Find the Job ID
      // Pull Jobs scheduled for Platform CO
      $comanage_coid = $this->Co->field("id", array("name" => DEF_COMANAGE_CO_NAME));
      $jobs = $this->Co->CoJob->jobsQueuedByType($comanage_coid, "CoreJob.GarbageCollector");
      if(!empty($jobs)) {
        $this->Co->id = $id;
        $this->Co->saveField('status', TemplateableStatusEnum::InTrash);
        $this->Flash->set(_txt('rs.jb.registered', array($jobs[0]['CoJob']['id'])), array('key' => 'success'));
      } else {
        $this->Flash->set( _txt('rs.jb.no', array("CoreJob.GarbageCollector") ), array('key' => 'error'));
      }
    }
    catch(Exception $e) {
      $this->Flash->set($e->getMessage(), array('key' => 'error'));
    } finally {
      $this->performRedirect();
    }
  }
}
