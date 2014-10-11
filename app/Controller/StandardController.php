<?php
/**
 * COmanage Registry Standard Controller
 *
 * Copyright (C) 2011-14 University Corporation for Advanced Internet Development, Inc.
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
 * @copyright     Copyright (C) 2011-14 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.1
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */

class StandardController extends AppController {
  // Placeholder, will get set by index()
  public $paginate = array();

  // Used for activating tabs on pages; will hold name of tab or NULL
  public $redirectTab = NULL;

  /**
   * Add a Standard Object.
   * - precondition: Model specific attributes in $this->request->data (optional)
   * - postcondition: On success, new Object created
   * - postcondition: Session flash message updated (HTML) or HTTP status returned (REST)
   * - postcondition: $<object>_id or $invalid_fields set (REST)
   *
   * @since  COmanage Registry v0.1
   */
  
  function add() {
    // Get a pointer to our model
    $req = $this->modelClass;
    $model = $this->$req;
    $modelid = $this->modelKey . "_id";
    $modelpl = Inflector::tableize($req);
    
    if($this->restful) {
      // Validate
      
      if(!$this->checkRestPost())
        return;
      
      // Reformat the request
      $data = $this->convertRestPost();
    } else {
      if(!isset($this->viewVars['title_for_layout'])) {
        // Set page title, if not already set
        $this->set('title_for_layout', _txt('op.add.new', array(_txt('ct.' . $modelpl . '.1'))));
      }
      
      if($this->request->is('get')) {
        // Nothing to do yet... return to let the form render
        
        if($this->requires_person)
          $this->checkPersonID("default", $this->request->data);
        
        return;
      }
      
      $data = $this->request->data;
    }

    if($this->requires_person && $this->checkPersonID("default", $data) < 1)
      return;

    // Perform model specific checks

    if(!$this->checkWriteDependencies($data))
      return;
    
    if($model->saveAll($data)) {
      if(!$this->recordHistory('add', $data)
         || !$this->checkWriteFollowups($data)) {
        if(!$this->restful) {
          $this->performRedirect();
        }
        
        return;
      }
      
      if($this->restful) {
        $this->restResultHeader(201, "Added");
        $this->set($modelid, $model->id);
      } else {
        // Redirect to index view

        $this->Session->setFlash(_txt('rs.added-a', array(Sanitize::html($this->generateDisplayKey()))), '', array(), 'success');
        $this->performRedirect();
      }
    } else {
      if($this->restful) {
        $fs = $model->invalidFields();
        
        if(!empty($fs)) {
          $this->restResultHeader(400, "Invalid Fields");
          $this->set('invalid_fields', $fs);
        } else {
          $this->restResultHeader(500, "Other Error");
        }
      } else {
        $this->Session->setFlash(_txt('er.fields'), '', array(), 'error');
        $this->regenerateForm();
      }
    }
  }
  
  /**
   * Perform any dependency checks required prior to a delete operation.
   * This method is intended to be overridden by model-specific controllers.
   * - postcondition: Session flash message updated (HTML) or HTTP status returned (REST)
   *
   * @since  COmanage Registry v0.1
   * @param  Array Current data
   * @return boolean true if dependency checks succeed, false otherwise.
   */
  
  function checkDeleteDependencies($curdata) {
    return(true);
  }
  
  /**
   * Perform any dependency checks required prior to a write (add/edit) operation.
   * This method is intended to be overridden by model-specific controllers.
   *
   * @since  COmanage Registry v0.1
   * @param  Array Request data
   * @param  Array Current data
   * @return boolean true if dependency checks succeed, false otherwise.
   */
  
  function checkWriteDependencies($reqdata, $curdata = null) {
    return true;
  }
  
  /**
   * Perform any followups following a write operation.  Note that if this
   * method fails, it must return a warning or REST response, but that the
   * overall transaction is still considered a success (add/edit is not
   * rolled back).
   * This method is intended to be overridden by model-specific controllers.
   *
   * @since  COmanage Registry v0.1
   * @param  Array Request data
   * @param  Array Current data
   * @return boolean true if dependency checks succeed, false otherwise.
   */
  
  function checkWriteFollowups($reqdata, $curdata = null) {
    return true;      
  }
  
  /**
   * Delete a Standard Object. WARNING: If model dependents are set, this method will delete all associated data.
   * - precondition: <id> must exist
   * - postcondition: Session flash message updated (HTML) or HTTP status returned (REST)
   * - postcondition: On success, all related data (any table with an <object>_id column) is deleted
   *
   * @since  COmanage Registry v0.1
   * @param  integer Object identifier (eg: cm_co_groups:id) representing object to be deleted
   */
  
  function delete($id) {
    // Get a pointer to our model
    $req = $this->modelClass;
    $model = $this->$req;
    
    // When deleting a model such as OrgIdentity that has a Name which is sorted by, Cake deletes
    // the name but then tries to do a find sorted by Name (which fails because it doesn't join
    // the table). There's probably a bug that needs to be fixed, but it's easier just to unset
    // the ordering on delete (which we don't need in the first place).
    unset($model->order);

    if(isset($this->delete_recursion))
      $model->recursive = $this->delete_recursion;
      
    // Cache the name before deleting, and also check that $id exists
    
    $model->id = $id;
    
    if(!isset($id) || $id < 1)
    {
      if($this->restful)
        $this->restResultHeader(400, "Invalid Fields");
      else
        $this->Session->setFlash(_txt('er.notprov.id', array($req)), '', array(), 'error');
      
      return;
    }
    
    // read() populates $this->request->data. Note it also resets any model associations
    // set via bindModel().
    $op = $model->read();
    
    if(empty($op))
    {
      if($this->restful)
        $this->restResultHeader(404, $req . " Unknown");
      else
        $this->Session->setFlash(_txt('er.notfound', array($req, $id)), '', array(), 'error');
      
      return;
    }

    $name = $this->generateDisplayKey($op);

    // Perform model specific checks

    if(!$this->checkDeleteDependencies($op))
    {
      if(!$this->restful)
        $this->performRedirect();
      return;
    }

    // Remove the object.

    if($model->delete($id))
    {
      if($this->recordHistory('delete', null, $op)) {
        if($this->restful)
          $this->restResultHeader(200, "Deleted");
        else
          $this->Session->setFlash(_txt('er.deleted-a', array(Sanitize::html($name))), '', array(), 'success');
      }
    }
    else
    {              
      if($this->restful)
        $this->restResultHeader(500, "Other Error");
      else
        $this->Session->setFlash(_txt('er.delete'), '', array(), 'error');
    }
    
    if(!$this->restful)
    {
      // Delete doesn't have a view, so we need to redirect back to index regardless of success
      
      if($this->requires_person)
      {
        $this->checkPersonID("force", $op);
      }
      else
      {
        $this->performRedirect();
      }
    }
  }
  
  /**
   * Update a Standard Object.
   * - precondition: Model specific attributes in $this->request->data (optional)
   * - precondition: <id> must exist
   * - postcondition: On GET, $<object>s set (HTML)
   * - postcondition: On POST success, object updated
   * - postcondition: On POST, session flash message updated (HTML) or HTTP status returned (REST)
   * - postcondition: On POST error, $invalid_fields set (REST)
   *
   * @since  COmanage Registry v0.1
   * @param  integer Object identifier (eg: cm_co_groups:id) representing object to be retrieved
   */
  
  function edit($id) {
    // Get a pointer to our model
    $req = $this->modelClass;
    $model = $this->$req;
    $modelpl = Inflector::tableize($req);

    $model->id = $id;
    
    if(isset($this->edit_contains)) {
      // New style: use containable behavior
      
      $args = array();
      
      $args['conditions'][$req.'.id'] = $id;
      $args['contain'] = $this->edit_contains;
      
      $curdata = $model->find('first', $args);
    } else {
      // Old style: use recursion (if set)
      
      if(isset($this->edit_recursion))
        $model->recursive = $this->edit_recursion;
      
      $curdata = $model->read();
    }
    
    if(empty($curdata))
    {
      if($this->restful)
        $this->restResultHeader(404, $req . " Unknown");
      else
      {
        $this->Session->setFlash(_txt('er.notfound', array(_txt('ct.' . $modelpl . '.1'), $id)), '', array(), 'error');
        $this->performRedirect();
      }
        
      return;
    }

    if(!$this->restful) {
      if(!isset($this->viewVars['title_for_layout'])) {
        // Set page title if not already set -- note we do similar logic in view()
        
        $t = _txt('ct.' . $modelpl . '.1');
        
        if(!empty($curdata['PrimaryName'])) {
          $t = generateCn($curdata['PrimaryName']);
        } elseif(!empty($curdata['Name'])) {
          $t = generateCn($curdata['Name']);
        } elseif(!empty($curdata[$req][ $model->displayField ])) {
          $t = $curdata[$req][ $model->displayField ];
        }
        
        if($this->requires_person) {
          if(!empty($curdata[$req]['co_person_id'])) {
            $t .= " (" . _txt('ct.co_people.1') . ")";
          } elseif(!empty($curdata[$req]['co_person_role_id'])) {
            $t .= " (" . _txt('ct.co_person_roles.1') . ")";
          } elseif(!empty($curdata[$req]['org_identity_id'])) {
            $t .= " (" . _txt('ct.org_identities.1') . ")";
          }
        }
        
        $this->set('title_for_layout', _txt('op.edit-a', array($t)));
      }
    }
    
    if($this->restful)
    {
      // Validate
      
      if(!$this->checkRestPost())
        return;
      
      // Reformat the request
      $data = $this->convertRestPost($curdata);
    }
    else
    {
      if($this->request->is('get'))
      {
        // Nothing to do yet... return current data and let the form render
        
        $this->request->data = $curdata;
        $this->set($modelpl, array(0 => $curdata));
        
        if($this->requires_person)
        {
          // We need to call checkPersonID to set a redirect path.  We could also check $ret['rc'],
          // but we'll do that on form submit anyway.
          
          $this->checkPersonID('set', $curdata);
        }
        
        return;
      }
      
      $data = $this->request->data;
      
      if(!isset($this->request->data[$req]['id'])) {
        // Make sure ID is available in the request in case the form errors out and
        // Cake needs to regenerate the POST target
        
        $this->request->data[$req]['id'] = $id;
      }
    }

    if($this->requires_person)
    {
      // We need exactly one of CO Person or Org Person, and it must exist.
      // (It's a bit pointless of a check since the HTML form doesn't allow the person ID to
      // be updated.)
            
      if($this->checkPersonID('default', $curdata) < 1)
        return;
    }

    // Make sure ID is set      
    $data[$req]['id'] = $id;
    
    // Set the view var since views require it on error... we need this
    // before any further returns.
    $this->set($modelpl, array(0 => $curdata));

    // Perform model specific checks

    if(!$this->checkWriteDependencies($data, $curdata))
      return;
    
    // Finally, try to save.

    if($model->saveAll($data))
    {
      // Update the view var in case the controller requires the updated values
      // for performRedirect or some other post-processing.
      
      $this->set($modelpl, array(0 => $data));
      
      if(!$this->recordHistory('edit', $data, $curdata)
         || !$this->checkWriteFollowups($data, $curdata)) {
        if(!$this->restful)
          $this->performRedirect();
        
        return;
      }

      if($this->restful)
        $this->restResultHeader(200, "OK");
      else
      {
        // Redirect to index view
        
        $this->Session->setFlash(_txt('rs.updated', array(Sanitize::html($this->generateDisplayKey()))), '', array(), 'success');
        $this->performRedirect();
      }
    }
    else
    {
      if($this->restful)
      {
        $fs = $model->invalidFields();
        
        if(!empty($fs))
        {
          $this->restResultHeader(400, "Invalid Fields");
          $this->set('invalid_fields', $fs);
        }
        else
        {
          $this->restResultHeader(500, "Other Error");
        }
      }
      else
        $this->Session->setFlash(_txt('er.fields'), '', array(), 'error');
    }
  }
  
  /**
   * Generate a display key to be used in messages such as "Item Added".
   *
   * @since  COmanage Registry v0.1
   * @param  Array A cached object (eg: from prior to a delete)
   * @return string A string to be included for display.
   */
  
  function generateDisplayKey($c = null) {
    // Get a pointer to our model
    $req = $this->modelClass;
    $model = $this->$req;
    
    if(isset($c[$req][$model->displayField])) {
      return $c[$req][$model->displayField];
    } elseif(isset($this->request->data[$model->displayField])) {
      return $this->request->data[$model->displayField];
    } elseif(isset($this->request->data[$req][$model->displayField])) {
      return $this->request->data[$req][$model->displayField];
    } elseif(strchr($model->displayField, '.')) {
      // Display field is of the form Model.field
      
      $m = explode('.', $model->displayField, 2);
      
      if(!empty($c[ $m[0] ][ $m[1] ])) {
        return $c[ $m[0] ][ $m[1] ];
      } elseif(!empty($this->request->data[ $m[0] ][ $m[1] ])) {
        return $this->request->data[ $m[0] ][ $m[1] ];
      }
    } else {
      return "(?)";
    }
  }
  
  /**
   * Generate history records for a transaction. This method is intended to be
   * overridden by model-specific controllers, and will be called from within a
   * try{} block so that HistoryRecord->record() may be called without worrying
   * about catching exceptions.
   *
   * @since  COmanage Registry v0.7
   * @param  String Controller action causing the change
   * @param  Array Data provided as part of the action (for add/edit)
   * @param  Array Previous data (for delete/edit)
   * @return boolean Whether the function completed successfully (which does not necessarily imply history was recorded)
   */
  
  public function generateHistory($action, $newdata, $olddata) {
    return true;
  }
  
  /**
   * Obtain all Standard Objects (of the model's type).
   * - postcondition: $<object>s set on success (REST or HTML), using pagination (HTML only)
   * - postcondition: HTTP status returned (REST)
   * - postcondition: Session flash message updated (HTML) on suitable error
   *
   * @since  COmanage Registry v0.1
   */

  public function index() {
    // Get a pointer to our model
    $req = $this->modelClass;
    $model = $this->$req;
    $modelpl = Inflector::tableize($req);
    $modelid = $this->modelKey . "_id";

    if(isset($this->view_recursion))
      $model->recursive = $this->view_recursion;
      
    // XXX The various sub-filters here (eg: findByCoPersonId) should be merged into
    // the new paginationConditions method.

    if($this->restful)
    {
      // Don't user server side pagination

      if($this->requires_person)
      {
        if(!empty($this->params['url']['copersonid']))
        {
          $t = $model->findAllByCoPersonId($this->params['url']['copersonid']);
          
          if(empty($t))
          {
            // We need to determine if copersonid is unknown or just
            // has no objects attached to it
            
            $o = $model->CoPerson->findById($this->params['url']['copersonid']);
            
            if(empty($o))
              $this->restResultHeader(404, "CO Person Unknown");
            else
              $this->restResultHeader(204, "CO Person Has No " . $req);
            
            return;
          }

          $this->set($modelpl, $this->convertResponse($t));
        }
        elseif(!empty($this->params['url']['copersonroleid']))
        {
          $t = $model->findAllByCoPersonRoleId($this->params['url']['copersonroleid']);
          
          if(empty($t))
          {
            // We need to determine if copersonroleid is unknown or just
            // has no objects attached to it
            
            $o = $model->CoPersonRole->findById($this->params['url']['copersonroleid']);
            
            if(empty($o))
              $this->restResultHeader(404, "CO Person Role Unknown");
            else
              $this->restResultHeader(204, "CO Person Role Has No " . $req);
            
            return;
          }

          $this->set($modelpl, $this->convertResponse($t));
        }
        elseif(!empty($this->params['url']['orgidentityid']))
        {
          $t = $model->findAllByOrgIdentityId($this->params['url']['orgidentityid']);

          if(empty($t))
          {
            // We need to determine if orgidentityid is unknown or just
            // has no objects attached to it
            
            $o = $model->OrgIdentity->findById($this->params['url']['orgidentityid']);
            
            if(empty($o))
              $this->restResultHeader(404, "Org Identity Unknown");
            else
              $this->restResultHeader(204, "Org Identity Has No " . $req);
            
            return;
          }
          
          $this->set($modelpl, $this->convertResponse($t));
        }
        else
        {
          // Although requires_person is true, the REST APIs generally permit
          // retrieval of all items of a given type
          
          $this->set($modelpl, $this->convertResponse($model->find('all')));
        }
      }
      else
      {
        $params = null;

        if($this->requires_co && !empty($this->params['url']['coid']))
        {
          // Only retrieve members of the requested CO.
          // For now, not specifying a CO is OK, we just retrieve all.
          // XXX need to do an authz check on this

          if(isset($model->CoPerson))
          {
            if(!$model->CoPerson->Co->findById($this->params['url']['coid']))
            {
              $this->restResultHeader(404, "CO Unknown");
              return;
            }
            
            $params['conditions'] = array('CoPerson.co_id' => $this->params['url']['coid']);
          }
          else
          {
            if(!$model->Co->findById($this->params['url']['coid']))
            {
              $this->restResultHeader(404, "CO Unknown");
              return;
            }
  
            $params['conditions'] = array($req.'.co_id' => $this->params['url']['coid']);
          }
        }
        elseif($this->allows_cou && !empty($this->params['url']['couid']))
        {
          // Only retrieve members of the requested COU.
          // For now, not specifying a COU is OK, we just retrieve all.
          // XXX need to do an authz check on this

          if(isset($this->CoPersonRole))
          {
            // Currently, only CoPersonRole will get here, so we don't also check for
            // (eg) $model->CoPersonRole, and we don't need to do a join like above
            
            if(!$model->Cou->findById($this->params['url']['couid']))
            {
              $this->restResultHeader(404, "COU Unknown");
              return;
            }
  
            $params['conditions'] = array($req.'.cou_id' => $this->params['url']['couid']);
          }
          else
          {
            $this->restResultHeader(500, "Other Error (Model Not Found)");
            return;
          }
        }

        $this->set($modelpl, $this->convertResponse($model->find('all', $params)));
      }
      
      $this->restResultHeader(200, "OK");
    }
    else
    {
      // Set page title
      $this->set('title_for_layout', _txt('ct.' . $modelpl . '.pl'));
      
      // Use server side pagination
      if($this->requires_person)
      {
        $this->Paginator->settings = $this->paginate;
        if(!empty($this->params['named']['copersonid']))
        {
          $q = $req . ".co_person_id = ";
          $this->set($modelpl, $this->Paginator->paginate($req, array($q => $this->params['named']['copersonid'])));
        }
        elseif(!empty($this->params['named']['copersonroleid']))
        {
          $q = $req . ".co_person_role_id = ";
          $this->set($modelpl, $this->Paginator->paginate($req, array($q => $this->params['named']['copersonroleid'])));
        }
        elseif(!empty($this->params['named']['orgidentityid']))
        {
          $q = $req . ".org_identity_id = ";
          $this->set($modelpl, $this->Paginator->paginate($req, array($q => $this->params['named']['orgidentityid'])));
        }
        else
        {
          // Although requires_person is true, the UI sort of permits
          // retrieval of all items of a given type
          
          $this->set($modelpl, $this->Paginator->paginate($req));
        }
      }
      else
      {
        // Configure pagination
        $this->paginate['conditions'] = $this->paginationConditions();
        $this->Paginator->settings = $this->paginate;
        
        $this->set($modelpl, $this->Paginator->paginate($req));
      }
    }
  }
  
  /**
   * Modify order of items via drag/drop; essentially like the index page plus an AJAX call
   *
   * @since  COmanage Registry v0.8.2
   */
  
  function order() {
    // Show more for ordering
    $this->paginate['limit'] = 200;

    $this->index();
  }

  /**
   * Save changes to the ordering made via drag/drop; called via AJAX.
   * - postcondition: Database modified
   *
   * @since  COmanage Registry v0.8.2
   */

  public function reorder() {
    // Get a pointer to our model
    $req = $this->modelClass;
    $model = $this->$req;

    foreach ($this->data[$req.'Id'] as $key => $value) {
      $model->id = $value;
      $model->saveField("ordr",$key + 1);
    }

    exit();
  }

  /**
   * Determine the conditions for pagination of the index view, when rendered via the UI.
   *
   * @since  COmanage Registry v0.1
   * @return Array An array suitable for use in $this->paginate
   */
  
  function paginationConditions() {
    // Get a pointer to our model
    $req = $this->modelClass;
    
    if(isset($this->cur_co))
    {
      // Only retrieve members of the current CO
      
      return(array(
        $req.'.co_id' => $this->cur_co['Co']['id']
      ));
    }

    return(array());
  }

  /**
   * Perform a redirect back to the controller's default view.
   * - postcondition: Redirect generated
   *
   * @since  COmanage Registry v0.1
   */
  
  function performRedirect() {
    if($this->requires_person) {
      if(empty($this->viewVars['redirect'])) {
        // $redirect doesn't seem to be set when deleting a name, so set it if missing
        $this->checkPersonID('set');
      }
      
      $redirect = $this->viewVars['redirect'];
      
      $this->set('redirect', $redirect);
      $this->redirect($redirect);
    } elseif(isset($this->cur_co)) {
      $this->redirect(array('action' => 'index', 'co' => Sanitize::html($this->cur_co['Co']['id'])));
    } else {
      $this->redirect(array('action' => 'index'));
    }
  }
  
  /**
   * Record history associated with an action. Note that, for now, failure to
   * record history DOES NOT roll back the original request. This may change
   * in a future release.
   *
   * @since  COmanage Registry v0.7
   * @param  String Controller action causing the change
   * @param  Array Data provided as part of the action (for add/edit)
   * @param  Array Previous data (for delete/edit)
   * @return boolean Whether the function completed successfully (which does not necessarily imply history was recorded)
   */
  
  function recordHistory($action, $newdata, $olddata=null) {
    // This function handles the framework of recording history.
    
    try {
      $this->generateHistory($action, $newdata, $olddata);
    }
    catch(Exception $e) {
      if($this->restful) {
        $this->restResultHeader(500, "Other Error: " . $e->getMessage());
      } else {
        $this->Session->setFlash($e->getMessage(), '', array(), 'info');
      }
      
      return false;
    }
    
    return true;
  }
  
  /**
   * Regenerate a form after validation/save fails.
   * This method is intended to be overridden.
   * - postcondition: Redirect generated
   *
   * @since  COmanage Registry v0.4
   */
  
  function regenerateForm() {
    // For most cases, we want the default Cake behavior, which is to render the
    // same view that the form was submitted to.
    
    return true;
  }

  /**
   * Insert search parameters into URL for index.
   * - postcondition: Redirect generated
   *
   * @since  COmanage Registry v0.8.3
   */
  
  function search() {
    // the page we will redirect to
    $url['action'] = 'index';
    
    // CoPeople uses "Search", but should use "search" like CoPetition (CO-906)
    
    // build a URL will all the search elements in it
    // the resulting URL will be 
    // example.com/registry/co_people/index/Search.givenName:albert/Search.familyName:einstein
    if(isset($this->data['Search'])) {
      foreach ($this->data['Search'] as $field=>$value){
        if(!empty($value)) {
          $url['Search.'.$field] = $value; 
        }
      }
    } elseif(isset($this->data['search'])) {
      foreach ($this->data['search'] as $field=>$value){
        if(!empty($value)) {
          $url['search.'.$field] = $value; 
        }
      }
    }
    
    // Insert CO into URL
    if(isset($this->cur_co['Co']['id'])) {
      $url['co'] = $this->cur_co['Co']['id'];
    }
    
    // redirect the user to the url
    $this->redirect($url, null, true);
  }

  /**
   * Retrieve a Standard Object.
   * - precondition: <id> must exist
   * - postcondition: $<object>s set (with one member)
   * - postcondition: HTTP status returned (REST)
   * - postcondition: Session flash message updated (HTML)
   *
   * @since  COmanage Registry v0.1
   * @param  integer Object identifier (eg: cm_co_groups:id) representing object to be retrieved
   */
  
  function view($id) {
    // Get a pointer to our model
    $req = $this->modelClass;
    $model = $this->$req;
    $modelpl = Inflector::tableize($req);
    
    $model->id = $id;
    
    if(isset($this->view_contains)) {
      // New style: use containable behavior
      
      $args = array();
      
      $args['conditions'][$req.'.id'] = $id;
      $args['contain'] = $this->view_contains;
      
      $obj = $model->find('first', $args);
    } else {
      // Old style: use recursion (if set)
      
      if(isset($this->view_recursion))
        $model->recursive = $this->view_recursion;
      
      $obj = $model->read();
    }
    
    if(empty($obj)) {
      if($this->restful) {
        $this->restResultHeader(404, $req . " Unknown");
      } else {
        $this->Session->setFlash(_txt('er.notfound', array(_txt('ct.' . $modelpl . '.1'), $id)), '', array(), 'error');
        $this->performRedirect();
      }
    } else {
      if($this->restful) {
        $this->set($modelpl, $this->convertResponse(array(0 => $obj)));
        $this->restResultHeader(200, "OK");
      } else {
        if(!isset($this->viewVars['title_for_layout'])) {
          // Set page title if not already set -- note we do similar logic in edit()
          
          $t = _txt('ct.' . $modelpl . '.1');
          
          if(!empty($obj['PrimaryName'])) {
            $t = generateCn($obj['PrimaryName']);
          } elseif(!empty($obj[$req][ $model->displayField ])) {
            $t = $obj[$req][ $model->displayField ];
          }
            
          if($this->requires_person) {
            if(!empty($obj[$req]['co_person_id'])) {
              $t .= " (" . _txt('ct.co_people.1') . ")";
            } elseif(!empty($obj[$req]['co_person_role_id'])) {
              $t .= " (" . _txt('ct.co_person_roles.1') . ")";
            } elseif(!empty($obj[$req]['org_identity_id'])) {
              $t .= " (" . _txt('ct.org_identities.1') . ")";
            }
          }
          
          $this->set('title_for_layout', _txt('op.view-a', array($t)));
        }
        
        $this->set($modelpl, array(0 => $obj));
        
        if($this->requires_person) {
          // We need to call checkPersonID to set a redirect path.
          
          $this->checkPersonID("set", $obj);
        }
      }
    }
  }
}
