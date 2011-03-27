<?php
  /*
   * COmanage Gears App Controller
   * Parent for all Controllers
   *
   * Version: $Revision$
   * Date: $Date$
   *
   * Copyright (C) 2010-2011 University Corporation for Advanced Internet Development, Inc.
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

  class AppController extends Controller {
    // Components available to all controllers
    var $components = array('Auth');
    
    // Determine if controller requires a CO to be selected
    var $requires_co = false;
    
    // The current CO we are working with (if any)
    var $cur_co = null;
    
    // Determine if controller is currently handling a RESTful request
    var $restful = false;
    
    // Determine if controller requires a Person ID to be provided
    var $requires_person = false;

    function authenticate($args)
    {
      // Manually authenticate via Basic Auth (for REST).
      //
      // Parameters:
      // - args: As provided by Security component callback invoker
      //
      // Preconditions:
      // (1) Security component is configured to call this function
      // (2) Auth controller has suitable authorization check configured
      //
      // Postconditions:
      // (1) On success, user is logged in via Auth component
      // (2) On failure, request is blackholed
      //
      // Returns:
      // - true if authentication and authorization is successful, false otherwise
        
      // Set the appropriate fields in $data, then hand off to Auth
      
      $data[ $this->Auth->fields['username'] ] = $args['username']; 
      $data[ $this->Auth->fields['password'] ] = $this->Auth->password($args['password']); 
      
      if($this->Auth->login($data)
         && $this->Auth->isAuthorized('controller', $this, $args['username']))
        return(true);

      $this->Security->blackHole($this, 'login'); 
      return(false);
    }
    
    function beforeFilter()
    {
      // Callback before other controller methods are invoked or views are rendered.
      //
      // Parameters:
      //   None
      //
      // Preconditions:
      // (1) Request Handler component has set $this->params and/or $this->data
      //
      // Postconditions:
      // (1) $this->restful is set if this is a RESTful transaction
      // (2) Auth component is configured
      // (3) Security component is configured
      // (4) If a CO must be specifed but is not yet, a redirect to the COs controller is issued (HTML)
      // (5) $this->cur_co and view var $cur_co are set (HTML)
      // (6) If CO is invalid, session error message is set (HTML)
      //
      // Returns:
      //   Nothing

      // Get a pointer to our model
      $req = $this->modelClass;
      if($req != 'Page')          // Page doesn't have an actual model
        $model = $this->$req;

      // First, determine if we're handling a RESTful request.
      // If so, we'll do a few things differently.

      if($this->params['url']['ext'] == 'xml'
         || $this->params['url']['ext'] == 'json')
      {
        // We assume XML and JSON requests are RESTful.  This also assumes the
        // default routing behavior described here:
        //  http://book.cakephp.org/view/1239/The-Simple-Setup
        
        $this->restful = true;  
      }
            
      if($this->requires_co && !$this->restful)
      {
        // Before we do anything else, check to see if a CO was provided if required.
        // (It might impact our authz decisions.)  If not, redirect to COs controller.
        // We can't check for RESTful here since $this->data isn't set yet for JSON.
          
        // The CO might be specified as a named parameter "co" or posted as "Co.id"
        // or posted as "Model.co_id".

        $coid = -1;
        
        if(isset($this->params['named']['co']))
          $coid = $this->params['named']['co'];
        elseif(isset($this->data['Co']['id']))
          $coid = $this->data['Co']['id'];
        elseif(isset($this->data[$this->modelClass]['co_id']))
          $coid = $this->data[$this->modelClass]['co_id'];          

        if($coid == -1)
        {
          $this->Session->write('co-select.controller', $this->params['controller']);
          $this->Session->write('co-select.action', $this->params['action']);
          $this->Session->write('co-select.args', array_merge($this->params['named'], $this->params['pass']));
          $this->redirect(array('controller' => 'cos', 'action' => 'select'));
        }
        else
        {
          // Retrieve CO Object.  If this model doesn't have a direct relationship, we'll temporarily bind the model
          
          // XXX This "find the CO" isn't really ideal
          if(isset($model->Co))
            $coptr = $model->Co;
          elseif(isset($model->CoPersonSource->Co))
            $coptr = $model->CoPersonSource->Co;
          elseif(isset($model->CoGroup->Co))
            $coptr = $model->CoGroup->Co;
          elseif(isset($model->CoPerson->CoPersonSource->Co))
            $coptr = $model->CoPerson->CoPersonSource->Co;
            
          if($coptr)
            $this->cur_co = $coptr->findById($coid);
          
          if(!empty($this->cur_co))
          {
            $this->set("cur_co", $this->cur_co);
            
            if($this->name == "CoPeople")
            {
              // XXX not clear why this should be here
            
              // Reconfigure Pagination
            
              $this->paginate['joins'] = array(
                array('table' => 'cm_co_person_sources',
                      'alias' => 'CoPersonSource',
                      'type' => 'INNER',
                      'conditions' => array(
                        'CoPerson.id=CoPersonSource.co_person_id'
                      )
                )
              );
              $this->paginate['conditions'] = array(
                'CoPersonSource.co_id' => $this->cur_co['Co']['id']
              );
            }
          }
          else
          {
            $this->Session->setFlash('Invalid CO ID ' . $coid, '', array(), 'error');
            $this->redirect(array('controller' => 'cos', 'action' => 'select'));
          }
        }
      }
      
      if($this->requires_person && !$this->restful)
      {
        // A CO might have been specified. If so, set up the pointer so other
        // methods (eg: calculateCMRoles) can see it.
        
        $coid = -1;
        
        if(isset($this->params['named']['co']))
          $coid = $this->params['named']['co'];
        elseif(isset($this->data['Co']['id']))
          $coid = $this->data['Co']['id'];
        elseif(isset($this->data[$this->modelClass]['co_id']))
          $coid = $this->data[$this->modelClass]['co_id'];          

        if($coid > -1)
        {
          // At least for now, we don't need to do "find the Co pointer" like we
          // did above
          $this->cur_co = $model->CoPerson->CoPersonSource->Co->findById($coid);
        }
      }
      
      if($this->restful)
      {
        // In order to use Basic Auth, we disable the Auth module restrictions
        // (We'll call it manually in authenticate().)
        
        $this->Auth->allow('*');
      }
      
      // Tell the Auth module to call the controller's isAuthorized() function
      $this->Auth->authorize = 'controller';

      if($this->restful)
      {
        // Set up the Security component
        $this->Security->loginOptions = array(
          'type' => 'basic',
          'realm' => 'REST',
          'login' => 'authenticate'   // Callback
        );
          
        $this->Security->loginUsers = array();
  
        // Require login for all methods 
        $this->Security->requireLogin();
          
        // But disable validation of POST data, which will be an XML document
        // (the security component doesn't know how to validate XML documents)
        $this->Security->validatePost = false;
      }
      
      // We need to retrieve roles before returning to the relevant controller.
      // This disables auto redirect (with the expectation that we'll manually
      // redirect in login())
      $this->Auth->autoRedirect = false;
    }
    
    function beforeRender()
    {
      // Callback after controller methods are invoked but before views are rendered.
      //
      // Parameters:
      //   None
      //
      // Preconditions:
      // (1) Request Handler component has set $this->params and/or $this->data
      //
      // Postconditions:
      // (1) If a CO must be specifed, a named parameter may be set.
      //
      // Returns:
      //   Nothing

      // Get a pointer to our model
      $req = $this->modelClass;
      if($req != 'Page')          // Page doesn't have an actual model
        $model = $this->$req;
        
      if(!$this->restful && $this->requires_co && isset($this->cur_co) && !isset($this->params['named']['co']))
      {
        // When a form is submitted but errors out, the CO passed via /co:##
        // is lost (because it had been converted to a POST parameter on submit).
        // Regenerate it.
        
        $this->params['named']['co'] = $this->cur_co['Co']['id'];
      }
    }
    
    function calculateCMRoles()
    {
      // Determine which COmanage platform roles the current user has.
      //
      // Parameters:
      //   None
      //
      // Preconditions:
      // (1) users_controller:login has run
      //
      // Postconditions:
      //     None
      //
      // Returns:
      // - An array with values of 'true' if the user has the specified role or
      //   'false' otherwise, with possible keys of
      //   - cmadmin: COmanage platform administrator
      //   - coadmin: Administrator of the current CO
      //   - comember: Member of the current CO
      //   - admin: Valid admin in any CO
      //   - user: Valid user in any CO (ie: to the platform)
      //   - apiuser: Valid API (REST) user (for now, API users are equivalent to cmadmins)
      //   - orgpersonid: Org Person ID of current user (or false)
      //   - copersonid: CO Person ID of current user in current CO (or false)
      
      $ret = array(
        'cmadmin' => false,
        'coadmin' => false,
        'comember' => false,
        'admin' => false,
        'user' => false,
        'apiuser' => false,
        'orgpersonid' => false,
        'copersonid' => false
      );
      
      // Retrieve session info
      $cos = $this->Session->read('Auth.User.cos');

      if(isset($cos))
      {
        // Platform admin?
        if(isset($cos['COmanage']['groups']['admin']['member']))
          $ret['cmadmin'] = $cos['COmanage']['groups']['admin']['member'];
        
        if(isset($this->cur_co))
        {
          // Admin of current CO?
          if(isset($cos[ $this->cur_co['Co']['name'] ]['groups']['admin']['member']))
            $ret['coadmin'] = $cos[ $this->cur_co['Co']['name'] ]['groups']['admin']['member'];
            
          // Member of current CO?
          if(isset($cos[ $this->cur_co['Co']['name'] ]['co_person_id']))
          {
            $ret['copersonid'] = $cos[ $this->cur_co['Co']['name'] ]['co_person_id'];
            $ret['comember'] = true;
            // Also store the co_person_id directly in the session to make it easier to find
            $this->Session->write('Auth.User.co_person_id', $ret['copersonid']);
          }
        }

        // Admin of any CO?
        foreach($cos as $c)
        {
          if(isset($c['groups']['admin']['member'])
             && $c['groups']['admin']['member'])
          {
            $ret['admin'] = true;
            break;
          }
        }
      }

      // Platform user?
      if($this->Session->check('Auth.User.name'))
        $ret['user'] = true;
      
      // API user?
      if($this->Session->check('Auth.User.api_user_id'))
      {
        $ret['apiuser'] = true;
        $ret['cmadmin'] = true;  // API users are currently platform admins
      }
      
      // Org Person?
      if($this->Session->check('Auth.User.org_person_id'))
        $ret['orgpersonid'] = $this->Session->read('Auth.User.org_person_id');

      return($ret);
    }
    
    function checkPersonID($redirectMode = "default", $data = null)
    {
      // For Models that accept either a CO Person ID or an Org Person ID,
      // verify that a valid person ID was specified.  Also, generate an array
      // suitable for redirecting back to the suitable controller.
      //
      // Parameters:
      // - redirectMode: "force" to force a redirect, "set" to set $redirect, or "default" to perform normal logic
      // - data: Retrieved attribute, with $data[$model]['co_person_id'] or $data[$model]['org_person_id'] set
      //
      // Preconditions:
      // (1) One of the following must be set (or $data):
      //     $this->params['named']['copersonid']
      //     $this->params['named']['orgpersonid']
      //     $this->data[$model]['co_person_id']
      //     $this->data[$model]['org_person_id']
      //
      // Postconditions:
      // (1) On error, a REST result will be generated (REST)
      // (2) On error, the session flash message will be set and a redirect will be generated (HTML)
      // (3) $redirect will be set (HTML)
      //
      // Returns:
      // - 1: OK
      // - 0: No person specified
      // - -1: Specified person does not exist
      
      // Get a pointer to our model
      $req = $this->modelClass;
      $model = $this->$req;

      $rc = 0;
      $redirect = array();
          
      // Find a person
      $pids = $this->parsePersonID($data);
      
      $copid = $pids['copersonid'];
      $orgpid = $pids['orgpersonid'];
      $co = null;
      
      if(!empty($this->params['named']['co']))
        $co = $this->params['named']['co'];
      elseif(!empty($this->data[$req]['co']))
        $co = $this->data[$req]['co'];
      
      if($copid != null)
      {
        $redirect['controller'] = 'co_people';

        $x = $model->CoPerson->findById($copid);
        
        if(empty($x))
        {
          $redirect['action'] = 'index';
          $rc = -1;
        }
        else
        {
          $redirect['action'] = 'edit';
          $redirect[] = $copid;
          if($co != null)
            $redirect['co'] = $co;
          $rc = 1;
        }
      }
      elseif($orgpid != null)
      {
        $redirect['controller'] = 'org_people';

        $x = $model->OrgPerson->findById($orgpid);
        
        if(empty($x))
        {
          $redirect['action'] = 'index';
          $rc = -1;
        }
        else
        {
          $redirect['action'] = 'edit';
          $redirect[] = $orgpid;
          $rc = 1;
        }
      }
      else
      {
        $redirect['controller'] = Inflector::tableize($model->name);
        $redirect['action'] = 'index';
      }
            
      if($redirectMode == "force")
        $this->redirect($redirect);
      elseif($redirectMode == "set")
        $this->set('redirect', $redirect);
      else
      {
        if($this->restful)
        {
          switch($rc)
          {
            case -1:
              $this->restResultHeader(403, "Person Does Not Exist");
              break;
            case 0:
              $this->restResultHeader(403, "No Person Specified");
              break;
          }
        }
        else
        {
          switch($rc)
          {
            case -1:
              $this->Session->setFlash("Person does not exist", '', array(), 'error');            
              $this->redirect($redirect);
              break;
            case 0:
              $this->Session->setFlash("No CO or Org Person specified", '', array(), 'error');            
              $this->redirect($redirect);
              break;
          }
        }
  
        $this->set('redirect', $redirect);
      }
      
      return($rc);
    }
    
    function checkPost()
    {
      // Verify that a POSTed document exists and matches the invoking controller.
      // This method is currently intended for RESTful transactions only.
      //
      // Parameters:
      //   None
      //
      // Preconditions:
      // (1) Any POST data has been parsed into $this->data
      //
      // Postconditions:
      // (1) If the correct request type was made but undefined fields were provided,
      //     those fields will be set in $invalid_fields
      // (2) On error, a REST result will be generated
      //
      // Returns:
      // - true if a POST was performed and matches the controller, false otherwise

      if(!empty($this->data))
      {
        // Currently, we expect all request documents to match the model name (ie: StudlySingular).
        
        $req = $this->modelClass;

        if(isset($this->data[$req]))
        {
          // Check if a CO is required that one was specified.
          // Note beforeFilter() may already have found a CO.
        
          if($this->requires_co && !isset($this->cur_co))
          {
            $coid = -1;
        
            if(isset($this->data[$this->modelClass]['co_id']))
              $coid = $this->data[$this->modelClass]['co_id'];          

            if($coid == -1)
            {
              $this->restResultHeader(403, "CO Does Not Exist");
              return(false);
            }
            else
            {
              // Retrieve CO Object.
              $model = $this->$req;
              
              // XXX This "find the CO" isn't really ideal
              if(isset($model->Co))
                $coptr = $model->Co;
              elseif(isset($model->CoPersonSource->Co))
                $coptr = $model->CoPersonSource->Co;
              elseif(isset($model->CoGroup->Co))
                $coptr = $model->CoGroup->Co;
              elseif(isset($model->CoPerson->CoPersonSource->Co))
                $coptr = $model->CoPerson->CoPersonSource->Co;
            
              if($coptr)
                $this->cur_co = $coptr->findById($coid);

              if(empty($this->cur_co))
              {
                $this->restResultHeader(403, "CO Does Not Exist");
                return(false);
              }
            }
          }
           
          // Finally, check the expected elements exist in the model (schema).
          // We only check top level at the moment (no recursion).
          
          $bad = array();
          
          foreach(array_keys($this->data[$req]) as $k)
          {
            // 'Person' is a special case that we interpret to mean either
            // 'COPerson' or 'OrgPerson', and to reference an id
            
            if(($k == 'Person' && (!isset($this->$req->_schema['org_person_id'])
                                   && !isset($this->$req->_schema['co_person_id'])))
               || // Other fields
               ($k != 'Person' && !isset($this->$req->_schema[Inflector::underscore($k)])))
              $bad[$k] = "Unknown Field";
          }
          
          // Check for Extended Attributes
          
          if(isset($this->cur_co))
          {
            $ea = "Co" . $this->cur_co['Co']['id'] . "PersonExtendedAttribute";
            
            if(isset($this->data[$ea]) && isset($this->$req->$ea->_schema))
            {
              foreach(array_keys($this->data[$ea]) as $k)
              {
                if(!isset($this->$req->$ea->_schema[Inflector::underscore($k)]))
                  $bad['ExtendedAttributes.'.$k] = "Unknown Field";
              }
            }
          }
          
          if(empty($bad))
            return(true);
          
          $this->restResultHeader(400, "Invalid Fields");
          $this->set('invalid_fields', $bad);
        }
        else
          $this->restResultHeader(400, "Bad Request");
      }
      else
        $this->restResultHeader(400, "Bad Request");
            
      return(false);
    }
    
    function convertRequest()
    {
      // Convert the body of a RESTful request from XML or JSON to DB format.
      // This method is currently intended for RESTful transactions only.
      //
      // Parameters:
      //   None
      //
      // Preconditions:
      // (1) $this->data holds request data
      //
      // Postconditions:
      // (1) $this->data converted to DB friendly formats
      // (2) Enum-type fields validated
      // (3) On error, HTTP status returned (REST)
      //
      // Returns:
      // - true if successful, false otherwise
      
      // Get a pointer to our model
      $req = $this->modelClass;
      $model = $this->$req;
      $modelcc = Inflector::pluralize($req);
      
      // See if we were passed json.  If so, convert it to standard format.
      // Based on http://trac.cakephp.org/ticket/6125
      
      if($this->RequestHandler->requestedWith('json'))
      {
        if(function_exists('json_decode'))
        {
          // Requires PHP >= 5.2
          $jsonData = json_decode(utf8_encode(trim(file_get_contents('php://input'))), true);
        }
        else
        {
          $this->restResultHeader(500, "json_decode Not Implemented");
          return(false);
        }
        
        if(!is_null($jsonData) && $jsonData !== false && isset($jsonData[$modelcc][0]))
        {
          // For now, inbound requests can only consist of one object.
          // There's not an obvious technical barrier to extending this.

          $this->data[$req] = $jsonData[$modelcc][0];
        }
        else
        {
          $this->restResultHeader(400, "Bad Request");
          return(false);
        }
      }
      elseif($this->RequestHandler->requestedWith('xml'))
      {
        // For now, inbound requests can only consist of one object.
        // There's not an obvious technical barrier to extending this.
        
        // We could also check that $modelcc is the correct version, but for now we don't.
        
        $d = $this->data[$modelcc][$req];
        
        unset($this->data);
        $this->data[$req] = $d;        
      }
      
      if(empty($this->data))
      {
        $this->restResultHeader(400, "Bad Request");
        return(false);
      }
      
      // Check version number.  For now, all inbound objects are version 1.0, so this
      // is easy.  When something gets rev'd, we'll need a way to track which version(s)
      // we support, probably in the model.
      //
      // We also need to remove the version from the request so that saveAll doesn't
      // get confused.

      if(!isset($this->data[$req]['Version']) || $this->data[$req]['Version'] != '1.0')
      {
        $this->restResultHeader(400, "Invalid Fields");
        $this->set('invalid_fields', array('Version' => 'Unknown version'));
        
        return(false);
      }
      else
        unset($this->data[$req]['Version']);

      // Convert keys from CamelCase (XML spec) to under_score (DB spec)
      $this->data[$req] = $this->requestToUnderScore($this->data[$req]);

      // Convert any enums from long form, and validate while we're at it
      if(!empty($model->cm_enum_types))
      {
        $bad = array();
        
        foreach(array_keys($model->cm_enum_types) as $k)
        {
          // Get a pointer to the enum, foo_ti
          // $$ and ${$} is PHP variable variable syntox
          $v = $model->cm_enum_types[$k] . "i";
          global $$v;
          
          if(isset(${$v}[ $this->data[$req][$k] ]))
          {
            $this->data[$req][$k] = ${$v}[ $this->data[$req][$k] ];
          }
          else
          {
            $bad[$k] = "Invalid value";
          }
          
          if(!empty($bad))
          {
            $this->restResultHeader(400, "Invalid Fields");
            $this->set('invalid_fields', $bad);
            
            return(false);
          }
        }
      }
      
      // Convert any booleans
      
      foreach(array_keys($model->validate) as $k)
      {
        if(isset($model->validate[$k]['rule']) && $model->validate[$k]['rule'][0] == 'boolean')
        {
          if($this->data[$req][$k] == 'True')
            $this->data[$req][$k] = true;
          else
            $this->data[$req][$k] = false;
        }
      }
      
      // If the XML doc represents a Person and has a Name attribute,
      // promote it up a level so saveAll sees it as a separate object
      
      if(($req == 'CoPerson' || $req == 'OrgPerson')
         && isset($this->data[$req]['name']))
      {
        global $name_ti;
        
        $this->data['Name'] = $this->data[$req]['name'];
        unset($this->data[$req]['name']);
        
        // Convert name type
      
        $this->data['Name']['type'] = $name_ti[ $this->data['Name']['type'] ];      
      }
      
      // Promote Extended Attributes up a level so saveAll sees them, too
      
      if($req == 'CoPerson' && isset($this->data[$req]['extended_attributes']))
      {
        $ea = "Co" . $this->cur_co['Co']['id'] . "PersonExtendedAttribute";
        
        $this->data[$ea] = $this->data[$req]['extended_attributes'];
        unset($this->data[$req]['extended_attributes']);
      }
      
      // Flatten the Person ID for models that use it
        
      if($this->requires_person)
      {
        if(!empty($this->data[$req]['person']) && isset($this->data[$req]['person']['type'])
           && isset($this->data[$req]['person']['id']))
        {
          if($this->data[$req]['person']['type'] == 'CO')
            $this->data[$req]['co_person_id'] = $this->data[$req]['person']['id'];
          elseif($this->data[$req]['person']['type'] == 'Org')
            $this->data[$req]['org_person_id'] = $this->data[$req]['person']['id'];
            
          unset($this->data[$req]['person']);
        }        
      }
      
      return(true);
    }

    function convertResponse($res)
    {
      // Convert a result to be suitable for REST views.
      //
      // Parameters:
      // - res: Result set, of the format returned by (eg) $this->find().
      //
      // Preconditions:
      //     None
      //
      // Postconditions:
      //     None
      //
      // Returns:
      // - A converted array.
      
      // We create a new array rather than edit $res in place because the foreach
      // gives us a copy, not a pointer
      $ret = array();

      // Get a pointer to our model
      $req = $this->modelClass;
      $model = $this->$req;
      
      foreach($res as $r)
      {        
        foreach(array_keys($r) as $m)
        {
          // For now, we only convert the model and Name (if set), since these
          // are the only pieces that the views need to render (and not all the
          // other related data the model defines).

          if($m == $req)
          {
            // Convert any enums to long form
            
            if(!empty($model->cm_enum_types))
            {
              foreach(array_keys($model->cm_enum_types) as $k)
              {
                // Get a pointer to the enum, foo_t
                // $$ and ${$} is PHP variable variable syntox
                $v = $model->cm_enum_types[$k];
                global $$v;
                
                if(isset(${$v}[ $r[$m][$k] ]))
                {
                  $rr[$m][$k] = ${$v}[ $r[$m][$k] ];
                }
                // else invalid value, but we'll skip that for now
              }
            }

            // Convert any booleans
            
            foreach(array_keys($model->validate) as $k)
            {
              if(isset($model->validate[$k]['rule']) && $model->validate[$k]['rule'][0] == 'boolean')
              {
                if($r[$m][$k])
                  $rr[$m][$k] = "True";
                else
                  $rr[$m][$k] = "False";
              }
            }
          }
          elseif($req != 'Name' && $m == 'Name' && isset($r['Name']['type']))
          {
            // We treat name specially
            
            // Convert type to long form
            global $name_t;
            
            $rr['Name']['type'] = $name_t[ $r['Name']['type'] ];
          }
          
          // Copy all other keys
          
          foreach(array_keys($r[$m]) as $k)
          {
            if(!isset($rr[$m][$k]))
              $rr[$m][$k] = $r[$m][$k];
          }

          if(isset($rr[$m]))
          {
            // Convert keys to CamelCase (XML spec) from under_score (DB spec)
            $rr[$m] = $this->responseToCamelCase($rr[$m]);
          }
        }

        $ret[] = $rr;
      }
            
      return($ret);
    }
    
    function fieldsErrorToString($fs)
    {
      // Generate an error string from a set of invalidFields.
      //
      // Parameters:
      // - fs: Array of invalid fields, as returned by $Model->invalidFields();
      //
      // Preconditions:
      //     None
      //
      // Postconditions:
      //     None
      //
      // Returns:
      // - The assembled string
      
      $s = "";
      
      foreach(array_keys($fs) as $f)
      {
        if(is_array($fs[$f]))
          $s .= $this->fieldsErrorToString($fs[$f]);
        else
          $s .= $f . ": " . $fs[$f] . "<br />\n";
      }
      
      return($s);
    }
    
    function parsePersonID($data = null)
    {
      // For Models that accept either a CO Person ID or an Org Person ID,
      // find the provided person ID.
      //
      // Parameters:
      // - data: Retrieved attribute, with $data[$model]['co_person_id'] or
      //         $data[$model]['org_person_id'] set
      //
      // Preconditions:
      // (1) One of the following must be set (or $data):
      //     $this->params['named']['copersonid']
      //     $this->params['named']['orgpersonid']
      //     $this->data[$model]['co_person_id']
      //     $this->data[$model]['org_person_id']
      //
      // Postconditions:
      //     None
      //
      // Returns: An array with the following elements:
      // - copersonid: CO Person ID if found, or null
      // - orgpersonid: Org Person ID if found, or null

      // Get a pointer to our model
      $req = $this->modelClass;
      $model = $this->$req;

      // Find a person
      $copid  = null;
      $orgpid = null;
      
      if(!empty($data[$req]['co_person_id']))
        $copid = $data[$req]['co_person_id'];
      elseif(!empty($data[$req]['org_person_id']))
        $orgpid = $data[$req]['org_person_id'];
      elseif(!empty($this->params['named']['copersonid']))
        $copid = $this->params['named']['copersonid'];
      elseif(!empty($this->params['named']['orgpersonid']))
        $orgpid = $this->params['named']['orgpersonid'];
      elseif(!empty($this->data[$req]['co_person_id']))
        $copid = $this->data[$req]['co_person_id'];
      elseif(!empty($this->data[$req]['org_person_id']))
        $orgpid = $this->data[$req]['org_person_id'];
      elseif(isset($this->params['pass'][0])
         && ($this->action == 'delete'
             || $this->action == 'edit'
             || $this->action == 'view'))
      {
        // If we still haven't found anything but we're a delete/edit/view
        // operation, a person ID could be implied by the model.
        
        $rec = $model->findById($this->params['pass'][0]);
        
        if(isset($rec[$req]['co_person_id']))
          $copid = $rec[$req]['co_person_id'];
        elseif(isset($rec[$req]['org_person_id']))
          $orgpid = $rec[$req]['org_person_id'];
      }
      
      return(array("copersonid" => $copid, "orgpersonid" => $orgpid));
    }
    
    function requestToUnderScore($a)
    {
      // Convert a Request array from CamelCase format (used by XML spec) to under_score
      // format (used by database).
      //
      // Parameters:
      // - a: Array holding request data
      //
      // Preconditions:
      //     None
      //
      // Postconditions:
      // (1) Array keys in $a are converted to underscore
      //
      // Returns:
      // - The underscored array
      
      foreach(array_keys($a) as $k)
      {
        if(is_array($a[$k]))
          $a[Inflector::underscore($k)] = $this->requestToUnderScore($a[$k]);
        else
          $a[Inflector::underscore($k)] = $a[$k];
        
        unset($a[$k]);
      }
      
      return($a);
    }
    
    function responseToCamelCase($a)
    {
      // Convert a Response array to CamelCase format (used by XML spec) from under_score
      // format (used by database).
      //
      // Parameters:
      // - a: Array holding response data
      //
      // Preconditions:
      //     None
      //
      // Postconditions:
      // (1) Array keys in $a are converted to CamelCase
      //
      // Returns:
      // - The underscored array

      foreach(array_keys($a) as $k)
      {
        if(is_array($a[$k]))
          $a[Inflector::camelize($k)] = $this->requestToUnderScore($a[$k]);
        else
          $a[Inflector::camelize($k)] = $a[$k];
        
        unset($a[$k]);
      }

      return($a);
    }
    
    function restResultHeader($status, $txt)
    {
      // Send a REST result HTTP header.
      //
      // Parameters:
      // - status: HTTP result code
      // - txt: HTTP result comment
      //
      // Preconditions:
      // (1) HTTP headers must not yet have been sent
      //
      // Postconditions:
      // (1) HTTP headers are sent
      //
      // Returns:
      //   Nothing
      
      $this->header("HTTP/1.0 " . $status . " " . $txt);
    }
  }
?>