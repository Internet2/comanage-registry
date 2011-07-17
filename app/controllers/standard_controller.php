<?php
  /*
   * COmanage Gears Standard Controller
   * Parent for Typical Controllers
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

  class StandardController extends AppController {
    function add()
    {
      // Add a Standard Object.
      //
      // Parameters (in $this->data):
      // - Model specific attributes
      //
      // Preconditions:
      //     None
      //
      // Postconditions:
      // (1) On success, new Object created
      // (2) Session flash message updated (HTML) or HTTP status returned (REST)
      // (3) $<object>_id or $invalid_fields set (REST)
      //
      // Returns:
      //   Nothing

      // Get a pointer to our model
      $req = $this->modelClass;
      $model = $this->$req;
      $modelid = $this->modelKey . "_id";
      $modelpl = Inflector::tableize($req);

      if($this->restful)
      {
        // Reformat the request to HTTP POST format and validate it
        
        if(!$this->convertRequest()
           || !$this->checkPost())
          return;
      }
      else
      {
        // Set page title
        $this->set('title_for_layout', _txt('op.add.new', array(_txt('ct.' . $modelpl . '.1'))));
        
        if(empty($this->data))
        {
          // Nothing to do yet... return to let the form render
          
          if($this->requires_person)
            $this->checkPersonID();
          
          return;
        }
      }

      if($this->requires_person && $this->checkPersonID() < 1)
        return;

      // Perform model specific checks

      if(!$this->checkWriteDependencies())
        return;

      // Finally, try to save

      if($model->saveAll($this->data))
      {
        if(!$this->checkWriteFollowups())
        {
          if(!$this->restful)
            $this->performRedirect();
          
          return;
        }
        
        if($this->restful)
        {
          $this->restResultHeader(201, "Added");
          $this->set($modelid, $model->id);
        }
        else
        {
          // Redirect to index view

          $this->Session->setFlash(_txt('rs.added-a', array(Sanitize::html($this->generateDisplayKey())), '', array(), 'success'));
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
        {
          $this->Session->setFlash($this->fieldsErrorToString($model->invalidFields()), '', array(), 'error');
        }
      }
    }
    
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
      
      return(true);
    }
    
    function checkWriteDependencies($curdata = null)
    {
      // Perform any dependency checks required prior to a write (add/edit) operation.
      // This method is intended to be overridden by model-specific controllers.
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
      
      return(true);
    }
    
    function checkWriteFollowups()
    {
      // Perform any followups following a write operation.  Note that if this
      // method fails, it must return a warning or REST response, but that the
      // overall transaction is still considered a success (add/edit is not
      // rolled back).
      // This method is intended to be overridden by model-specific controllers.
      // 
      // Parameters:
      //   None
      //
      // Preconditions:
      // (1) $this->data holds request data
      //
      // Postconditions:
      // (1) Session flash message updated (HTML) or HTTP status returned (REST) on error
      //
      // Returns:
      // - true if dependency checks succeed, false otherwise.
      
      return(true);      
    }
    
    function delete($id)
    {
      // Delete a Standard Object.
      // 
      // WARNING: If model dependents are set, this method will delete all associated data.
      //
      // Parameters:
      // - id: Object identifier (eg: cm_cos:id) representing object to be deleted
      //
      // Preconditions:
      // (1) <id> must exist
      //
      // Postconditions:
      // (1) Session flash message updated (HTML) or HTTP status returned (REST)
      // (2) On success, all related data (any table with an <object>_id column) is deleted
      //
      // Returns:
      //   Nothing
      
      // Get a pointer to our model
      $req = $this->modelClass;
      $model = $this->$req;

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
      
      $op = $model->read();  // read() populates $this->data
      
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
      
      // Remove the object

      if($model->delete($id))
      {
        if($this->restful)
          $this->restResultHeader(200, "Deleted");
        else
          $this->Session->setFlash(_txt('er.deleted-a', array(Sanitize::html($name))), '', array(), 'success');
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
          $this->checkPersonID("force", $op);
        else
          $this->performRedirect();
      }
    }
    
    function edit($id)
    {
      // Update a Standard Object.
      //
      // Parameters:
      // - id: Object identifier (eg: cm_co_groups:id) representing object to be retrieved
      //
      // Parameters (in $this->data):
      // - Model specific attributes
      //
      // Preconditions:
      // (1) <id> must exist
      //
      // Postconditions:
      // (1) If $this->data is empty, $<object>s set (HTML)
      // (2) If $this->data is set, on success, object is updated
      // (3) If $this->data is set, session flash message updated (HTML) or HTTP status returned (REST)
      // (4) If $this->data is set, $invalid_fields set on suitable error (REST)
      //
      // Returns:
      //   Nothing
      
      // Get a pointer to our model
      $req = $this->modelClass;
      $model = $this->$req;
      $modelpl = Inflector::tableize($req);

      $model->id = $id;

      if(isset($this->edit_recursion) && empty($curdata))
        $model->recursive = $this->edit_recursion;

      if(!$this->restful)
      {
        // Set page title
        $this->set('title_for_layout', _txt('op.edit-a', array(_txt('ct.' . $modelpl . '.1'))));
      }

      // Make sure $id exists
      
      $curdata = $model->read();

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

      if($this->restful)
      {
        // Reformat the request to HTTP POST format and validate it
        
        if(!$this->convertRequest()
           || !$this->checkPost())
          return;
      }
      else
      {
        if(empty($this->data))
        {
          // Nothing to do yet... return current data and let the form render

          $this->data = $curdata;
          $this->set($modelpl, array(0 => $curdata));
          
          if($this->requires_person)
          {
            // We need to call checkPersonID to set a redirect path.  We could also check $ret['rc'],
            // but we'll do that on form submit anyway.
            
            $this->checkPersonID('set', $curdata);
          }
          
          return;
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
      $this->data[$req]['id'] = $id;
      
      // Set the view var since views require it on error... we need this
      // before any further returns
      $this->set($modelpl, array(0 => $curdata));

      // Perform model specific checks

      if(!$this->checkWriteDependencies($curdata))
        return;

      // Finally, try to save

      if($model->saveAll($this->data))
      {
        if(!$this->checkWriteFollowups())
        {
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
          $this->Session->setFlash($this->fieldsErrorToString($model->invalidFields()), '', array(), 'error');
      }
    }
    
    function generateDisplayKey($c = null)
    {
      // Generate a display key to be used in messages such as "Item Added".
      //
      // Parameters:
      // - c: A cached object (eg: from prior to a delete)
      //
      // Preconditions:
      //     None
      //
      // Postconditions:
      // (1) A string to be included for display.
      //
      // Returns:
      //   Nothing
 
      // Get a pointer to our model
      $req = $this->modelClass;
      $model = $this->$req;

      if(isset($c[$req][$model->displayField]))
        return($c[$req][$model->displayField]);
      elseif(isset($this->data[$model->displayField]))
        return($this->data[$model->displayField]);
      elseif(isset($this->data[$req][$model->displayField]))
        return($this->data[$req][$model->displayField]);
      else
        return("(?)");
    }
    
    function index()
    {
      // Obtain all Standard Objects (of the model's type).
      //
      // Parameters:
      //   None
      //
      // Preconditions:
      //     None
      //
      // Postconditions:
      // (1) $<object>s set on success (REST or HTML), using pagination (HTML only)
      // (2) HTTP status returned (REST)
      // (3) Session flash message updated (HTML) on suitable error
      //
      // Returns:
      //   Nothing
      
      // Get a pointer to our model
      $req = $this->modelClass;
      $model = $this->$req;
      $modelpl = Inflector::tableize($req);
      $modelid = $this->modelKey . "_id";

      if(isset($this->view_recursion))
        $model->recursive = $this->view_recursion;

      if($this->restful)
      {
        // Don't user server side pagination

        if($this->requires_person)
        {
          if(!empty($this->params['url']['copersonroleid']))
          {
            $t = $model->findAllByCoPersonRoleId($this->params['url']['copersonroleid']);
            
            if(empty($t))
            {
              $this->restResultHeader(404, "CO Person Role Unknown");
              return;
            }

            $this->set($modelpl, $this->convertResponse($t));
          }
          elseif(!empty($this->params['url']['orgidentityid']))
          {
            $t = $model->findAllByOrgIdentityId($this->params['url']['orgidentityid']);
  
            if(empty($t))
            {
              $this->restResultHeader(404, "Org Identity Unknown");
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
  
            if(isset($model->CoPersonSource))
            {
              if(!$model->CoPersonSource->Co->findById($this->params['url']['coid']))
              {
                $this->restResultHeader(404, "CO Unknown");
                return;
              }
              
              $dbo = $model->getDataSource();
              
              $params['joins'][] = array('table' => $dbo->fullTableName($model->CoPersonSource),
                                         'alias' => 'CoPersonSource',
                                         'type' => 'INNER',
                                         'conditions' => array(
                                           $req.'.id=CoPersonSource.'.$modelid
                                        ));
              $params['conditions'] = array('CoPersonSource.co_id' => $this->params['url']['coid']);
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
  
            if(isset($this->CoPersonSource))
            {
              // Currently, only CoPersonSource will get here, so we don't also check for
              // (eg) $model->CoPersonSource, and we don't need to do a join like above
              
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
          if(!empty($this->params['named']['copersonroleid']))
          {
            $q = $req . ".co_person_role_id ='";
            $this->set($modelpl, $this->paginate($req, array($q => $this->params['named']['copersonroleid'])));
          }
          elseif(!empty($this->params['named']['orgidentityid']))
          {
            $q = $req . ".org_identity_id ='";
            $this->set($modelpl, $this->paginate($req, array($q => $this->params['named']['orgidentityid'])));
          }
          else
          {
            // Although requires_person is true, the UI sort of permits
            // retrieval of all items of a given type

            $this->set($modelpl, $this->paginate($req));
          }
        }
        else
        {
          if(isset($this->cur_co))
          {
            if(isset($model->CoPersonSource))
            {
              if(!empty($this->cur_cous))
              {
                // If we're retrieving based on CoPersonSource and $this->cur_cous
                // is set, only retrieve records relevant to the COUs specified
                // (ie: those the COU Admin has access to)
              
                $dbo = $model->getDataSource();
                
                $this->paginate['conditions'] = array(
                  'CoPersonSource.co_id' => $this->cur_co['Co']['id'],
                  'Cou.name' => $this->cur_cous
                );
                
                $this->paginate['joins'][] = array(
                  'table' => $dbo->fullTableName($model->CoPersonSource->Cou),
                  'alias' => 'Cou',
                  'type' => 'INNER',
                  'conditions' => array('CoPersonSource.cou_id=Cou.id')
                );
              }
              else
              {
                // Otherwise retrieve "normally"
                
                $this->paginate['conditions'] = array(
                  'CoPersonSource.co_id' => $this->cur_co['Co']['id']
                );
                
                // XXX unclear this is the right default join since
                // CoPersonRole is hardcoded, but it's also not clear what
                // other "normal" view gets here
                $this->paginate['joins'][] = array(
                  'table' => 'cm_co_person_sources',
                  'alias' => 'CoPersonSource',
                  'type' => 'INNER',
                  'conditions' => array(
                    'CoPersonRole.id=CoPersonSource.co_person_role_id'
                  )
                );
              }
            }
            else
            {
              $this->paginate['conditions'] = array(
                $req.'.co_id' => $this->cur_co['Co']['id']
              );
            }
          }
          
          $this->set($modelpl, $this->paginate($req));
        }
      }
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
      
      if($this->requires_person)
        $this->redirect($this->viewVars['redirect']);
      if(isset($this->cur_co))
        $this->redirect(array('action' => 'index', 'co' => $this->cur_co['Co']['id']));
      else
        $this->redirect(array('action' => 'index'));
    }

    function view($id)
    {
      // Retrieve a Standard Object
      //
      // Parameters:
      // - id: Object identifier (eg: cm_co_groups:id) representing object to be retrieved
      //
      // Preconditions:
      // (1) <id> must exist
      //
      // Postconditions:
      // (1) $<object>s set (with one member) if found
      // (2) HTTP status returned (REST)
      // (3) Session flash message updated (HTML) on suitable error 
      //
      // Returns:
      //   Nothing
      
      // Get a pointer to our model
      $req = $this->modelClass;
      $model = $this->$req;
      $modelpl = Inflector::tableize($req);

      if(isset($this->view_recursion))
        $model->recursive = $this->view_recursion;

      $model->id = $id;
      $obj = $model->read();
      
      if(empty($obj))
      {
        if($this->restful)
          $this->restResultHeader(404, $req . " Unknown");
        else
        {
          $this->Session->setFlash(_txt('er.notfound', array(_txt('ct.' . $modelpl . '.1'), $id)), '', array(), 'error');
          $this->performRedirect();
        }
      }
      else
      {
        if($this->restful)
        {
          $this->set($modelpl, $this->convertResponse(array(0 => $obj)));
          $this->restResultHeader(200, "OK");
        }
        else
        {
          // Set page title
          $this->set('title_for_layout', _txt('op.view-a', array(_txt('ct.' . $modelpl . '.1'))));

          $this->set($modelpl, array(0 => $obj));

          if($this->requires_person)
          {
            // We need to call checkPersonID to set a redirect path.
            
            $this->checkPersonID("set", $obj);
          }
        }
      }
    }
  }
?>