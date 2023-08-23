<?php
/**
 * COmanage Registry Standard Controller
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

class StandardController extends AppController {
  // Placeholder, will get set by index()
  public $paginate = array();

  // XXX Deprecated. Used for activating tabs on pages; will hold name of tab or NULL
  public $redirectTab = NULL;

  // Used in the performRedirect() function to pass in a specific redirect from a controller
  public $redirectTarget = NULL;

  // Deleting certain records requires forcing a hard delete so that changelog
  // behavior is skipped
  public $useHardDelete = false;

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
    
    $checkpid = -1;
    $data = array();
    
    if($this->request->is('restful')) {
      // Validate
      
      try {
        $this->Api->checkRestPost($this->cur_co['Co']['id']);
        $data[$req] = $this->Api->getData();
        
        if($this->request->is('restful') && !empty($data[$req]['extended_attributes'])) {
          // We need to specially handle extended attributes here. This really
          // belongs in CoPersonRole::beforeSave(), but in Cake 2 callbacks can't
          // modify associated data. We can't do this in ApiComponent because it
          // only returns one model's worth of data. We can't do this in
          // CoPersonRolesController because there isn't a suitable callback.
          // We do similar logic in edit(), below.
          
          $eaModel = 'Co' . $this->cur_co['Co']['id'] . 'PersonExtendedAttribute';
          
          $data[$eaModel] = $data[$req]['extended_attributes'];
          unset($data[$req]['extended_attributes']);
        }
      }
      catch(InvalidArgumentException $e) {
        // See if we have invalid fields
        $invalidFields = $this->Api->getInvalidFields();
        
        if($invalidFields) {
          // Pass them to the view
          $this->set('invalid_fields', $invalidFields);
        }
        
        $this->Api->restResultHeader($e->getCode(), $e->getMessage());
        return;
      }
      
      if($this->requires_person) {
        switch($this->checkPersonID("calculate", $data)) {
          case -1:
            $this->Api->restResultHeader(403, "Person Does Not Exist");
            return;
            break;
          case 0:
            $this->Api->restResultHeader(403, "No Person Specified");
            return;
            break;
          default:
            break;
        }
      }
    } else {
      if(!isset($this->viewVars['title_for_layout'])) {
        // Set page title, if not already set
        $this->set('title_for_layout', _txt('op.add.new', array(_txt('ct.' . $modelpl . '.1'))));
      }
      
      if($this->request->is('get')) {
        // Nothing to do yet... return to let the form render
        
        if($this->requires_person) {
          $this->checkPersonID("default", $this->request->data);
        }
        
        return;
      }
      
      $data = $this->request->data;
      
      if($this->requires_person && $this->checkPersonID() < 1)
        return;
    }

    // Perform model specific checks

    if(!$this->checkWriteDependencies($data))
      return;
    
    $err = "";
    $ret = false;
    
    try {
      $ret = $model->saveAll($data);
    }
    catch(Exception $e) {
      $err = filter_var($e->getMessage(),FILTER_SANITIZE_SPECIAL_CHARS);
    }
    
    if($ret) {
      // Reread the data so we account for any normalizations
      $data = $model->read();
      
      if(!$this->recordHistory('add', $data)
         || !$this->checkWriteFollowups($data)) {
        if(!$this->request->is('restful')) {
          $this->performRedirect();
        }
        
        return;
      }
      
      if($this->request->is('restful')) {
        $this->Api->restResultHeader(201, "Added");
        $this->set($modelid, $model->id);
      } else {
        // Redirect to index view
        $this->Flash->set(_txt('rs.added-a', array(filter_var($this->generateDisplayKey(),FILTER_SANITIZE_SPECIAL_CHARS))), array('key' => 'success'));
        $this->performRedirect();
      }
    } else {
      if($this->request->is('restful')) {
        $fs = $model->invalidFields();
        
        if(!empty($fs)) {
          $this->Api->restResultHeader(400, "Invalid Fields");
          $this->set('invalid_fields', $fs);
        } elseif ($e
                  && isset(_txt('en.http.status.codes')[$e->getCode()]) )  {
            $this->Api->restResultHeader($e->getCode(), _txt('en.http.status.codes')[$e->getCode()]);
            if(!empty($e->getMessage())) {
              $this->set('vv_error', $e->getMessage());
            }
        } else {
          $this->Api->restResultHeader(500, "Other Error");
        }
      } else {
        if(!empty($model->validationErrors)) {
          // Refactor the error messages
          $model->validationErrors = $model->filterValidationErrors(
            $model->validate,
            $model->validationErrors
          );
          $errors_list = array_values(Hash::flatten($model->validationErrors));
          $errors_list = array_unique(array_values($errors_list));
          foreach($errors_list as $error) {
            $this->Flash->set($error, array('key' => 'error'));
          }
        }
        $this->Flash->set($err ?: _txt('er.fields'), array('key' => 'error'));
        $this->regenerateForm();
      }
    }
  }
  
  /**
   * Callback after controller methods are invoked but before views are rendered.
   *
   * @since  COmanage Registry v0.9.4
   */

  public function beforeRender() {
    $mName = $this->modelClass;
    
    if($mName == 'CoPerson' || $mName == 'OrgIdentity' || $mName == 'CoGroupMember') {
      // Populate list of statuses for people searches
      
      global $cm_lang, $cm_texts;
      $this->set('vv_statuses', $cm_texts[ $cm_lang ]['en.status']);
    }
    
    // If we're in a plugin and the plugin specifies a Server type,
    // populate the available set of those servers
    
    if(!empty($this->$mName->cmServerType)) {
      $this->loadModel('Server');
      
      $args = array();
      $args['conditions']['Server.server_type'] = $this->$mName->cmServerType;
      $args['conditions']['Server.co_id'] = $this->cur_co['Co']['id'];
      $args['conditions']['Server.status'] = SuspendableStatusEnum::Active;
      $args['fields'] = array('id', 'description');
      $args['contain'] = false;
      
      $this->set('vv_servers', $this->Server->find('list', $args));
    }

    if(!$this->request->is('restful')) {
      // Include Search Block
      $this->set('vv_search_fields', $this->searchConfig($this->action));
      // Include alphabet Search bar
      $this->set('vv_alphabet_search', $this->alphabetSearchConfig($this->action));
    }

    parent::beforeRender();
  }

  /**
   * Search Block fields configuration
   *
   * @example
   *    if($action == 'index') {
   *      return array(
   *        'search.status' => array(
   *          'label' => _txt('fd.status'),
   *          'type' => 'select',
   *          'empty'   => _txt('op.select.all'),
   *          'options' => _txt('en.status.pt'),
   *        ),
   *        'search.sponsor' => array(
   *          'label' => _txt('fd.sponsor'),
   *          'type' => 'text',
   *        ),
   *        'qaxsbar' => array(
   *          'label' => _txt('fd.status') . ":",
   *          'type' => 'checkbox',
   *          'enum' => _txt('en.status.pt'),
   *          'field' => 'status',    // This is multivalue. e.g. search.status[0] = PA,search.status[0] = PC
   *        ),
   *      );
   *    }
   * @since  COmanage Registry v4.0.0
   */

  function searchConfig($action) {
    return array();
  }

  /**
   * Alphabet Search Bar configuration
   *
   * @example
   *    if($action == 'index') {
   *      return array(
   *        'search.familyNameStart' => array(
   *          'label' => _txt('me.alpha.label'),
   *        ),
   *      );
   *    }
   * @since  COmanage Registry v4.0.0
   */

  function alphabetSearchConfig($action) {
    return array();
  }

  /**
   * Callback before other controller methods are invoked or views are rendered.
   *
   * @since  COmanage Registry v3.3
   */

  function beforeFilter() {
    parent::beforeFilter();
    // Dynamically adjust validation rules to include the current CO ID for dynamic types.
    // Apply the rule only when the validateExtendedType function is used as a custom rule
    $model = $this->modelClass;
    if(!empty($this->$model->validate['type']['content']['rule'])
       && array_search('validateExtendedType', $this->$model->validate['type']['content']['rule'], true) !== null
       && !empty($this->cur_co['Co']['id'])) {
      $vrule = $this->$model->validate['type']['content']['rule'];
      $vrule[1]['coid'] = $this->cur_co['Co']['id'];
      $this->$model->validator()->getField('type')->getRule('content')->rule = $vrule;
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
    return true;
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
   * @param  Array Original request data (unmodified by callbacks)
   * @return boolean true if dependency checks succeed, false otherwise.
   */
  
  function checkWriteFollowups($reqdata, $curdata = null, $origdata = null) {
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
    
    if(!isset($id) || $id < 1) {
      if($this->request->is('restful')) {
        $this->Api->restResultHeader(400, "Invalid Fields");
      } else {
        $this->Flash->set(_txt('er.notprov.id', array($req)), array('key' => 'error'));
      }
      
      return;
    }
    
    // When deleting a model such as OrgIdentity that has a Name which is sorted by, Cake deletes
    // the name but then tries to do a find sorted by Name (which fails because it doesn't join
    // the table). There's probably a bug that needs to be fixed, but it's easier just to unset
    // the ordering on delete (which we don't need in the first place).
    unset($model->order);
    
    $args = array();
    $args['conditions'][$req.'.id'] = $id;
    $args['contain'] = false;

    if(isset($this->delete_contains)) {
      // Use containable behavior
      
      $args['contain'] = $this->delete_contains;
    }
    
    $curdata = $model->find('first', $args);
    
    // Cache the name before deleting, and also check that $id exists
    
    $model->id = $id;
    
    // read() populates $this->request->data. Note it also resets any model associations
    // set via bindModel().
    
    if(empty($curdata)) {
      if($this->request->is('restful')) {
        $this->Api->restResultHeader(404, $req . " Unknown");
      } else {
        $this->Flash->set(_txt('er.notfound', array($req, $id)), array('key' => 'error'));
      }
      
      return;
    }
    
    $name = $this->generateDisplayKey($curdata);
    
    // Perform model specific checks
    
    if(!$this->checkDeleteDependencies($curdata)) {
      if(!$this->request->is('restful'))
        $this->performRedirect();
      
      return;
    }
    
    if($this->useHardDelete) {
      // We need to walk the model relations and adjust Changelog behavior
      // for any related model. This is because beforeFind will still fire,
      // altering the list of records to be deleted, even though deleteAll doesn't
      // fire callbacks. We also need the behavior to clear internal foreign keys
      // so that we don't get foreign key errors when rows are not deleted in the
      // proper order.
      
      $model->reloadBehavior('Changelog', array('expunge' => true));
    }
    
    // Remove the object.
    $ret = false;
    $dataSource = $model->getDataSource();
    $dataSource->begin();
    try {
      $ret = $model->delete($id);
    } catch (PDOException $e) {
      $dataSource->rollback();
      if(!empty($e->errorInfo[2])) {
        $error_split = explode("\n", $e->errorInfo[2]);
        foreach ($error_split as $details) {
          $this->Flash->set($details, array('key' => 'information'));
        }
      } else {
        $this->Flash->set($e->getMessage(), array('key' => 'error'));
      }
    }
    
    if($ret) {
      $dataSource->commit();
      if($this->recordHistory('delete', null, $curdata)) {
        if($this->request->is('restful')) {
          $this->Api->restResultHeader(200, "Deleted");
        } else {
          $this->Flash->set(_txt('er.deleted-a', array(filter_var($name,FILTER_SANITIZE_SPECIAL_CHARS))), array('key' => 'success'));
        }
      }
    } else {
      $dataSource->rollback();
      if($this->request->is('restful')) {
        $this->Api->restResultHeader(500, "Other Error");
      } else {
        $this->Flash->set(_txt('er.delete'), array('key' => 'error'));
      }
    }
    
    if(!$this->request->is('restful')) {
      // Delete doesn't have a view, so we need to redirect back to index regardless of success
      
      if($this->requires_person) {
        $this->checkPersonID("force", $curdata);
      } else {
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
      // Old style
      
      $curdata = $model->read();
    }
    
    if(empty($curdata)) {
      if($this->request->is('restful')) {
        $this->Api->restResultHeader(404, $req . " Unknown");
      } else {
        $this->Flash->set(_txt('er.notfound', array(_txt('ct.' . $modelpl . '.1'), $id)), array('key' => 'error'));
        $this->performRedirect();
      }
      
      return;
    }

    // By default whenever we edit we redirect to the index view. If we decide to stay on the edit view then
    // title_for_layout will have the old value.
    if(!$this->request->is('restful')) {
      if(!isset($this->viewVars['title_for_layout'])) {
        $title = $model->calculateTitleForLayout($curdata, $this->requires_person);
        // Set page title if not already set -- note we do similar logic in view()
        $this->set('title_for_layout', _txt('op.edit-a', array($title)));
      }
    }
    
    if($this->request->is('restful')) {
      // Validate
      
      try {
        $this->Api->checkRestPost($this->cur_co['Co']['id']);
        $data[$req] = $this->Api->getData();
        
        if($this->request->is('restful') && !empty($data[$req]['extended_attributes'])) {
          // We need to specially handle extended attributes here. This really
          // belongs in CoPersonRole::beforeSave(), but in Cake 2 callbacks can't
          // modify associated data. We can't do this in ApiComponent because it
          // only returns one model's worth of data. We can't do this in
          // CoPersonRolesController because there isn't a suitable callback.
          // We do similar logic in add(), above.
          
          $eaModel = 'Co' . $this->cur_co['Co']['id'] . 'PersonExtendedAttribute';
          
          $data[$eaModel] = $data[$req]['extended_attributes'];
          unset($data[$req]['extended_attributes']);
          
          // Is there already a row ID for this person role? (edit, not add)
          $eaId = $model->$eaModel->field('id', array($eaModel . '.co_person_role_id' => $id));
          
          if($eaId) {
            $data[$eaModel]['id'] = $eaId;
          }
        }
      }
      catch(InvalidArgumentException $e) {
        // See if we have invalid fields
        $invalidFields = $this->Api->getInvalidFields();
        $this->set('vv_id', $id);

        if($invalidFields) {
          // Pass them to the view
          $this->set('invalid_fields', $invalidFields);
        } else {
          // We don't do this anywhere else, but we should, at least in API v2
          $this->set('vv_error', $e->getMessage());
        }
        
        $this->Api->restResultHeader($e->getCode(), $e->getMessage());
        return;
      }
      
      if($this->requires_person) {
        switch($this->checkPersonID("calculate", $data)) {
          case -1:
            $this->Api->restResultHeader(403, "Person Does Not Exist");
            return;
            break;
          case 0:
            $this->Api->restResultHeader(403, "No Person Specified");
            return;
            break;
          default:
            break;
        }
      }
    } else {
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
      
      if($this->requires_person) {
        // We need exactly one of CO Person or Org Person, and it must exist.
        // (It's a bit pointless of a check since the HTML form doesn't allow the person ID to
        // be updated.)
              
        if($this->checkPersonID('default', $curdata) < 1)
          return;
      }
    }
    
    // Make sure ID is set
    $data[$req]['id'] = $id;
    
    // Create a copy of data so checkWriteFollowups can see what modifications
    // were made by callbacks
    $origdata = $data;
    
    // Set the view var since views require it on error... we need this
    // before any further returns.
    $this->set($modelpl, array(0 => $curdata));

    // Perform model specific checks

    if(!$this->checkWriteDependencies($data, $curdata))
      return;
    
    if($req == 'CoPersonRole') {
      // We add a hack here for Extended Attributes. We remove them from the save so that
      // they can be saved separately by CoPersonRolesController::checkWriteFollowups.
      // We do this because for Cake 2 behaviors can't modify related models during a save,
      // and so the save takes place there. With ChangelogBehavior we want the save to
      // execute exactly once.
      
      $eaModel = 'Co' . $this->cur_co['Co']['id'] . 'PersonExtendedAttribute';
      unset($data[$eaModel]);
      
      // We'll reread the data after the save anyway, which will restore the (normalized)
      // Extended Attributes for purposes of history and followups.
    }

    $err = "";
    $ret = false;
    
    // Finally, try to save.

    try {
      $ret = $model->saveAll($data);
    }
    catch(Exception $e) {
      $err = filter_var($e->getMessage(),FILTER_SANITIZE_SPECIAL_CHARS);
    }
    
    if($ret) {
      // Reread the data so we account for any normalizations
      if(isset($this->edit_contains)) {
        // New style: use containable behavior
        
        $args = array();
        
        $args['conditions'][$req.'.id'] = $id;
        $args['contain'] = $this->edit_contains;
        
        $data = $model->find('first', $args);
      } else {
        // Old style
        
        $data = $model->read();
      }

      // Calculate the title after a successful save
      $title = $model->calculateTitleForLayout($data, $this->requires_person);
      $this->set('title_for_layout', _txt('op.edit-a', array($title)));

      // Update the view var in case the controller requires the updated values
      // for performRedirect or some other post-processing.
      
      $this->set($modelpl, array(0 => $data));
      
      if(!$this->recordHistory('edit', $data, $curdata)
         || !$this->checkWriteFollowups($data, $curdata, $origdata)) {
        if(!$this->request->is('restful')) {
          $this->performRedirect();
        }
        
        return;
      }

      if($this->request->is('restful')) {
        $this->Api->restResultHeader(200, "OK");
      } else {
        // Redirect to index view
        
        $this->Flash->set(_txt('rs.updated', array(filter_var($this->generateDisplayKey(),FILTER_SANITIZE_SPECIAL_CHARS))), array('key' => 'success'));
        $this->performRedirect();
      }
    } else {
      if($this->request->is('restful')) {
        $fs = $model->invalidFields();
        
        if(!empty($fs)) {
          $this->Api->restResultHeader(400, "Invalid Fields");
          $this->set('invalid_fields', $fs);
        } else {
          $this->Api->restResultHeader(500, "Other Error");
        }
      } else {
        if(!empty($model->validationErrors)) {
          // Refactor the error messages
          $model->validationErrors = $model->filterValidationErrors(
            $model->validate,
            $model->validationErrors
          );
          $errors_list = array_values(Hash::flatten($model->validationErrors));
          $errors_list = array_unique(array_values($errors_list));
          foreach($errors_list as $error) {
            $this->Flash->set($error, array('key' => 'error'));
          }
        }
        $this->Flash->set($err ?: _txt('er.fields'), array('key' => 'error'));
        
        if($req == 'CoPerson') {
          // For CO People, we need to redirect back to canvas
          $this->performRedirect();
        }
      }
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
    
    // XXX we explicitly support view_contains when processing paginationConditions,
    // even though we don't (currently) support it elsewhere in this function
    
    // XXX The various sub-filters here (eg: findByCoPersonId) should be merged into
    // the new paginationConditions method.
    
    // XXX Model specific logic should be moved to individual Controllers.

    if($this->request->is('restful')) {
      $this->set('vv_model_version', $model->version);
      
      if(!empty($this->request->query['search_identifier'])) {
        // XXX temporary implementation -- need more general approach (CO-1053)
        $args = array();
        $args['conditions']['Identifier.identifier'] = $this->request->query['search_identifier'];

        $orgPooled = $this->CmpEnrollmentConfiguration->orgIdentitiesPooled();
        if(!empty($this->params['url']['coid']) && !$orgPooled) {
          $args['conditions'][$model->name . '.co_id'] = $this->params['url']['coid'];
        }
        $args['joins'][0]['table'] = 'identifiers';
        $args['joins'][0]['alias'] = 'Identifier';
        $args['joins'][0]['type'] = 'INNER';
        $args['joins'][0]['conditions'][0] = $req . '.id=Identifier.' . $modelid;
        $args['contain'] = false;

        $t = $model->find('all', $args);

        $this->set($modelpl, $this->Api->convertRestResponse($t));
      } elseif(!empty($this->request->query['search_mail'])) {
        // XXX another hack (this time for Mail) that should be rewritten
        // as part of CO-1053
        $args = array();
        $args['conditions']['EmailAddress.mail'] = $this->request->query['search_mail'];

        $orgPooled = $this->CmpEnrollmentConfiguration->orgIdentitiesPooled();
        if(!empty($this->params['url']['coid']) && !$orgPooled) {
          $args['conditions'][$model->name . '.co_id'] = $this->params['url']['coid'];
        }

        $args['joins'][0]['table'] = 'email_addresses';
        $args['joins'][0]['alias'] = 'EmailAddress';
        $args['joins'][0]['type'] = 'INNER';
        $args['joins'][0]['conditions'][0] = $req . '.id=EmailAddress.' . $modelid;
        $args['contain'] = false;

        $t = $model->find('all', $args);
        
        $this->set($modelpl, $this->Api->convertRestResponse($t));
      } elseif(!empty($this->request->query['attribute'])) {
        // XXX another hack (this time for AttributeEnumerations) that should be rewritten
        // as part of CO-1053
        $args = array();
        $args['conditions']['AttributeEnumeration.attribute'] = $this->request->query['attribute'];
        
        if(!empty($this->request->params['url']['coid'])) {
          $args['conditions'][$model->name . '.co_id'] = $this->request->params['url']['coid'];
        }
        
        $this->set($modelpl, $this->Api->convertRestResponse($model->find('all', $args)));
      } elseif(!empty($model->permittedApiFilters)
               && !empty(array_intersect_key($model->permittedApiFilters, $this->params['url']))) {
        // We are filtering on a plugin specific key
        $keys = array_intersect_key($model->permittedApiFilters, $this->params['url']);
        
        $args = array();
        foreach(array_keys($keys) as $k) {
          if(!empty($this->params['url'][$k])) {
            $args['conditions'][$model->name.'.'.$k] = $this->params['url'][$k];
          }
        }
        $args['contain'] = false;
        
        $this->set($modelpl, $this->Api->convertRestResponse($model->find('all', $args)));
      } elseif($this->requires_person
               // XXX This is a bit of a hack, we should really refactor this
               || $req == 'CoOrgIdentityLink'
               // CO Person Role doesn't require person, but can still be searched that way.
               // (But we need to fall through to the other logic if copersonid is not specified, hack hack.)
               || ($req == 'CoPersonRole'
                   && !empty($this->params['url']['copersonid']))) {
        if(isset($this->params['url']['codeptid'])) {
          if(empty($this->params['url']['codeptid'])) {
            $this->Api->restResultHeader(400, "CO Department Not Specified");
            return;
          }
          
          $args = array();
          $args['conditions'][$model->name . '.co_department_id'] = $this->params['url']['codeptid'];
          $args['contain'] = false;
          
          $t = $model->find('all', $args);
          
          if(empty($t)) {
            // We need to determine if codeptid is unknown or just
            // has no objects attached to it
            
            $args = array();
            $args['conditions']['CoDepartment.id'] = $this->params['url']['codeptid'];
            $args['contain'] = false;
            
            if(!$model->CoDepartment->find('count', $args)) {
              $this->Api->restResultHeader(404, "CO Department Unknown");
            } else {
              $this->Api->restResultHeader(204, "CO Department Has No " . $req);
            }
            
            return;
          }
          
          $this->set($modelpl, $this->Api->convertRestResponse($t));
        } elseif(isset($this->params['url']['cogroupid'])) {
          if(empty($this->params['url']['cogroupid'])) {
            $this->Api->restResultHeader(400, "CO Group Not Specified");
            return;
          }
          
          $args = array();
          $args['conditions'][$model->name . '.co_group_id'] = $this->params['url']['cogroupid'];
          $args['contain'] = false;
          
          $t = $model->find('all', $args);
          
          if(empty($t)) {
            // We need to determine if cogroupid is unknown or just
            // has no objects attached to it
            
            $args = array();
            $args['conditions']['CoGroup.id'] = $this->params['url']['cogroupid'];
            $args['contain'] = false;
            
            if(!$model->CoGroup->find('count', $args)) {
              $this->Api->restResultHeader(404, "CO Group Unknown");
            } else {
              $this->Api->restResultHeader(204, "CO Group Has No " . $req);
            }
            
            return;
          }
          
          $this->set($modelpl, $this->Api->convertRestResponse($t));
        } elseif(isset($this->params['url']['copersonid'])) {
          if(empty($this->params['url']['copersonid'])) {
            $this->Api->restResultHeader(400, "CO Person Not Specified");
            return;
          }
          
          $args = array();
          $args['conditions'][$model->name . '.co_person_id'] = $this->params['url']['copersonid'];
          $args['contain'] = false;
          
          $t = $model->find('all', $args);
          
          if(empty($t)) {
            // We need to determine if copersonid is unknown or just
            // has no objects attached to it
            
            $args = array();
            $args['conditions']['CoPerson.id'] = $this->params['url']['copersonid'];
            $args['contain'] = false;
            
            if(!$model->CoPerson->find('count', $args)) {
              $this->Api->restResultHeader(404, "CO Person Unknown");
            } else {
              $this->Api->restResultHeader(204, "CO Person Has No " . $req);
            }
            
            return;
          }
          
          $this->set($modelpl, $this->Api->convertRestResponse($t));
        } elseif(isset($this->params['url']['copersonroleid'])) {
          if(empty($this->params['url']['copersonroleid'])) {
            $this->Api->restResultHeader(400, "CO Person Role Not Specified");
            return;
          }
          
          $args = array();
          $args['conditions'][$model->name . '.co_person_role_id'] = $this->params['url']['copersonroleid'];
          $args['contain'] = false;
          
          $t = $model->find('all', $args);
          
          if(empty($t)) {
            // We need to determine if copersonroleid is unknown or just
            // has no objects attached to it
            
            $args = array();
            $args['conditions']['CoPersonRole.id'] = $this->params['url']['copersonroleid'];
            $args['contain'] = false;
            
            if(!$model->CoPersonRole->find('count', $args)) {
              $this->Api->restResultHeader(404, "CO Person Role Unknown");
            } else {
              $this->Api->restResultHeader(204, "CO Person Role Has No " . $req);
            }
            
            return;
          }
          
          $this->set($modelpl, $this->Api->convertRestResponse($t));
        } elseif(isset($this->params['url']['organizationid'])) {
          if(empty($this->params['url']['organizationid'])) {
            $this->Api->restResultHeader(400, "Organization Not Specified");
            return;
          }
          
          $args = array();
          $args['conditions'][$model->name . '.organization_id'] = $this->params['url']['organizationid'];
          $args['contain'] = false;
          
          $t = $model->find('all', $args);
          
          if(empty($t)) {
            // We need to determine if codeptid is unknown or just
            // has no objects attached to it
            
            $args = array();
            $args['conditions']['Organization.id'] = $this->params['url']['organizationid'];
            $args['contain'] = false;
            
            if(!$model->Organization->find('count', $args)) {
              $this->Api->restResultHeader(404, "Organization Unknown");
            } else {
              $this->Api->restResultHeader(204, "Organization Has No " . $req);
            }
            
            return;
          }
          
          $this->set($modelpl, $this->Api->convertRestResponse($t));
        } elseif(isset($this->params['url']['orgidentityid'])) {
          if(empty($this->params['url']['orgidentityid'])) {
            $this->Api->restResultHeader(400, "Org Identity Not Specified");
            return;
          }
          
          $args = array();
          $args['conditions'][$model->name . '.org_identity_id'] = $this->params['url']['orgidentityid'];
          $args['contain'] = false;
          
          $t = $model->find('all', $args);
          
          if(empty($t)) {
            // We need to determine if orgidentityid is unknown or just
            // has no objects attached to it
            
            $args = array();
            $args['conditions']['OrgIdentity.id'] = $this->params['url']['orgidentityid'];
            $args['contain'] = false;
            
            if(!$model->OrgIdentity->find('count', $args)) {
              $this->Api->restResultHeader(404, "Org Identity Unknown");
            } else {
              $this->Api->restResultHeader(204, "Org Identity Has No " . $req);
            }
            
            return;
          }
          
          $this->set($modelpl, $this->Api->convertRestResponse($t));
        } else {
          // Although requires_person is true, the REST APIs generally permit
          // retrieval of all items of a given type
          
          $params = null;
          
          if($this->requires_co && !empty($this->params['url']['coid'])) {
            // Only retrieve members of the requested CO.
            // For now, not specifying a CO is OK, we just retrieve all.
            // XXX need to do an authz check on this
            
            if(isset($model->CoPerson)) {
              $args = array();
              $args['conditions']['Co.id'] = $this->params['url']['coid'];
              $args['contain'] = false;
              
              if(!$model->CoPerson->Co->find('count', $args)) {
                $this->Api->restResultHeader(404, "CO Unknown");
                return;
              }
              
              $params['conditions'] = array('CoPerson.co_id' => $this->params['url']['coid']);
            } else {
              $args = array();
              $args['conditions']['Co.id'] = $this->params['url']['coid'];
              $args['contain'] = false;
              
              if(!$model->Co->find('count', $args)) {
                $this->Api->restResultHeader(404, "CO Unknown");
                return;
              }
              
              $params['conditions'] = array($req.'.co_id' => $this->params['url']['coid']);
            }
          }
          
          $this->set($modelpl, $this->Api->convertRestResponse($model->find('all', $params)));
        }
      } else {
        $params = null;
        
        if($this->requires_co && !empty($this->params['url']['coid'])) {
          // Only retrieve members of the requested CO.
          // For now, not specifying a CO is OK, we just retrieve all.
          // XXX need to do an authz check on this

          if(isset($model->CoPerson)) {
            $args = array();
            $args['conditions']['Co.id'] = $this->params['url']['coid'];
            $args['contain'] = false;
            
            if(!$model->CoPerson->Co->find('count', $args)) {
              $this->Api->restResultHeader(404, "CO Unknown");
              return;
            }
            
            $params['conditions'] = array('CoPerson.co_id' => $this->params['url']['coid']);
          } else {
            $args = array();
            $args['conditions']['Co.id'] = $this->params['url']['coid'];
            $args['contain'] = false;
            
            if(!$model->Co->find('count', $args)) {
              $this->Api->restResultHeader(404, "CO Unknown");
              return;
            }
            
            $params['conditions'] = array($req.'.co_id' => $this->params['url']['coid']);
          }
        } elseif($this->allows_cou && !empty($this->params['url']['couid'])) {
          // Only retrieve members of the requested COU.
          // For now, not specifying a COU is OK, we just retrieve all.
          // XXX need to do an authz check on this

          if(isset($this->CoPersonRole)) {
            // Currently, only CoPersonRole will get here, so we don't also check for
            // (eg) $model->CoPersonRole, and we don't need to do a join like above
            
            $args = array();
            $args['conditions']['Cou.id'] = $this->params['url']['couid'];
            $args['contain'] = false;
            
            if(!$model->Cou->find('count', $args)) {
              $this->Api->restResultHeader(404, "COU Unknown");
              return;
            }
            
            $params['conditions'] = array($req.'.cou_id' => $this->params['url']['couid']);
          } else {
            $this->Api->restResultHeader(500, "Other Error (Model Not Found)");
            return;
          }
        } elseif(!empty($model->validate['cluster_id']) && !empty($this->params['url']['clusterid'])) {
          $args = array();
          $args['conditions']['Cluster.id'] = $this->params['url']['clusterid'];
          $args['contain'] = false;
          
          if(!$model->Cluster->find('count', $args)) {
            $this->Api->restResultHeader(404, "Cluster Unknown");
            return;
          }
          
          $params['conditions'] = array($req.'.cluster_id' => $this->params['url']['clusterid']);
        }
        
        // We don't ever need associated models
        $params['contain'] = false;
        
        $this->set($modelpl, $this->Api->convertRestResponse($model->find('all', $params)));
      }
      
      $this->Api->restResultHeader(200, "OK");
    }
    else
    {
      // Set page title
      $this->set('title_for_layout', _txt('ct.' . $modelpl . '.pl'));
      
      // Configure server side pagination
      
      $local = $this->paginationConditions();
      
      // XXX We could probaby come up with a better approach than manually enumerating
      // each field we want to copy...
      if(!empty($local['conditions'])) {
        $this->paginate['conditions'] = $local['conditions'];
      }
      
      if(!empty($local['fields'])) {
        $this->paginate['fields'] = $local['fields'];
      }
      
      if(!empty($local['group'])) {
        $this->paginate['group'] = $local['group'];
      }
      
      if(!empty($local['joins'])) {
        $this->paginate['joins'] = $local['joins'];
      }

      if(!empty($local['order'])) {
        $this->paginate['order'] = $local['order'];
      }
      
      if(isset($local['contain'])) {
        $this->paginate['contain'] = $local['contain'];
      } elseif(isset($this->view_contains)) {
        $this->paginate['contain'] = $this->view_contains;
      }
      
      // Used either to enumerate which fields can be used for sorting, or
      // explicitly naming sortable fields for complex relations (ie: using
      // linkable behavior).
      $sortlist = array();
      
      if(!empty($local['sortlist'])) {
        $sortlist = $local['sortlist'];
      }
      
      $this->Paginator->settings = $this->paginate;
      
      $this->set($modelpl, $this->Paginator->paginate($req, array(), $sortlist));
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

    if($this->request->is('restful')) {
      // Reformat the serialized order into Cake format for saving
      $data = array();
      
      foreach($this->data[$req.'Id'] as $key => $value) {
        $data[] = array(
          'id'   => $value,
          'ordr' => $key+1
        );
      }
      
      if($model->saveMany($data, array('fieldList' => array('ordr')))) {
        $this->Api->restResultHeader(200, "OK");
      } else {
        $this->Api->restResultHeader(500, "Database Save Failed");
      }
      
      // Make sure the response goes out
      $this->response->send();
    }
  }

  /**
   * Determine the conditions for pagination of the index view, when rendered via the UI.
   *
   * @since  COmanage Registry v0.1
   * @return Array An array suitable for use in $this->paginate
   */
  
  public function paginationConditions() {
    // Get a pointer to our model
    $req = $this->modelClass;
    
    $ret = array();
    
    if($this->requires_person) {
      if(!empty($this->params['named']['copersonid'])) {
        $ret['conditions'][$req.'.co_person_id'] = $this->params['named']['copersonid'];
      } elseif(!empty($this->params['named']['copersonroleid'])) {
        $ret['conditions'][$req.'.co_person_role_id'] = $this->params['named']['copersonroleid'];
      } elseif(!empty($this->params['named']['orgidentityid'])) {
        $ret['conditions'][$req.'.org_identity_id'] = $this->params['named']['orgidentityid'];
      } elseif(!empty($this->params['named']['codeptid'])) {
        $ret['conditions'][$req.'.co_department_id'] = $this->params['named']['codeptid'];
      } elseif(!empty($this->params['named']['orgid'])) {
        $ret['conditions'][$req.'.organization_id'] = $this->params['named']['orgid'];
      } else {
        // We previously allowed retrieval of all items of a given type
        // (eg: Addresses), but it's not really clear why and there wasn't really
        // a path to get there. So now we throw an error instead.
        
        throw new InvalidArgumentException(_txt('er.person.none'));
      }
    } elseif(!empty($this->cur_co)) {
      // Only retrieve members of the current CO
      $ret['conditions'][$req.'.co_id'] = $this->cur_co['Co']['id'];
    }
    
    return $ret;
  }
  
  /**
   * Determine the join conditions for pagination of the index view, when rendered via the UI.
   *
   * @since  COmanage Registry v0.9.2
   * @return Array An array suitable for use in $this->paginate
   */
  
  public function paginationJoins() {
    return null;
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
    } elseif(!empty($this->redirectTarget)) {
      // we are passing in a specific redirect (overriding the default redirect to 'index')
      $this->redirect($this->redirectTarget);
    } elseif(isset($this->cur_co)) {
      $this->redirect(array('action' => 'index', 'co' => filter_var($this->cur_co['Co']['id'],FILTER_SANITIZE_SPECIAL_CHARS)));
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
      if($this->request->is('restful')) {
        $this->Api->restResultHeader(500, "Other Error: " . $e->getMessage());
      } else {
        $this->Flash->set($e->getMessage(), array('key' => 'information'));
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
    // Construct the URL based on the action mode we're in (select, relink, index, link)
    $action = key($this->data['RedirectAction']);

    $url['action'] = $action;
    foreach($this->data[$action] as $key => $value) {
      // pass parameters
      if(is_int($key) && isset($value['pass'])) {
        array_push($url, filter_var($value['pass'], FILTER_SANITIZE_SPECIAL_CHARS));
      } else {
        foreach ($value as $knamed => $vnamed) {
          $url[$knamed] = filter_var($vnamed, FILTER_SANITIZE_SPECIAL_CHARS);
        }
      }
    }
    
    // build a URL will all the search elements in it
    // the resulting URL will be 
    // example.com/registry/co_people/index/search.givenName:albert/search.familyName:einstein
    if(isset($this->data['search'])) {
      foreach ($this->data['search'] as $field => $value){
        if(!empty($value)) {
          $url['search.'.$field] = $value;
        }
      }
    }

    if($this->request->params["named"]) {
      foreach ($this->request->params["named"] as $field => $value){
        if(!empty($value)) {
          $url[$field] = $value;
        }
      }
    }

    if(isset($this->cur_co['Co']['id'])) {
      // Insert CO into URL
      $url['co'] = $this->cur_co['Co']['id'];
    }

    // We need a final parameter so email addresses don't get truncated as file extensions (CO-1271)
    $url = array_merge($url, array('op' => 'search'));
    
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
      // Old style
      
      $obj = $model->read();
    }
    
    if(empty($obj)) {
      if($this->request->is('restful')) {
        $this->Api->restResultHeader(404, $req . " Unknown");
      } else {
        $this->Flash->set(_txt('er.notfound', array(_txt('ct.' . $modelpl . '.1'), $id)), array('key' => 'error'));
        $this->performRedirect();
      }
    } else {
      if($this->request->is('restful')) {
        $this->set('vv_model_version', $model->version);
        
        $this->set($modelpl, $this->Api->convertRestResponse(array(0 => $obj)));
        $this->Api->restResultHeader(200, "OK");
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
