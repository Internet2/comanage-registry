<?php
/**
 * Application level Controller
 *
 * This file is application-wide controller file. You can put all
 * application-wide controller-related methods here.
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       registry
 * @since         COmanage Registry v0.1, CakePHP(tm) v 0.2.9
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('Controller', 'Controller');
App::uses('Sanitize', 'Utility');

/**
 * This is a placeholder class.
 * Create the same file in app/Controller/AppController.php
 *
 * Add your application-wide methods in the class below, your controllers
 * will inherit them.
 *
 * @package       Cake.Controller
 * @link http://book.cakephp.org/view/957/The-App-Controller
 */
class AppController extends Controller {
  // All controllers use these components
  public $components = array('Auth',
                             'RequestHandler', // For REST
                             'Security',
                             'Session');
  
  // Determine if controller requires a CO to be selected
  public $requires_co = false;
  
  // Determine if controller allows a COU to be selected
  public $allows_cou = false;
  
  // The current CO we are working with (if any)
  public $cur_co = null;
  
  // Determine if controller is currently handling a RESTful request
  public $restful = false;
  
  // Determine if controller requires a Person ID to be provided
  public $requires_person = false;

  /**
   * Callback before other controller methods are invoked or views are rendered.
   * - precondition:
   * - postcondition: Auth component is configured 
   * - postcondition:
   *
   * @since  COmanage Registry v0.1
   * @throws UnauthorizedException (REST)
   */   
  
  public function beforeFilter() {
    // Tell the Auth module to call the controller's isAuthorized() function.
    $this->Auth->authorize = 'controller';
    
    // First, determine if we're handling a RESTful request.
    // If so, we'll do a few things differently.
    
    if(isset($this->request->params['ext'])
       && ($this->request->params['ext'] == 'xml'
           || $this->request->params['ext'] == 'json')) {
      // We assume XML and JSON requests are RESTful.  This also assumes the
      // default routing behavior described here:
      //  http://book.cakephp.org/view/1239/The-Simple-Setup
      
      $this->restful = true;  
    }
  
    if($this->requires_co && !$this->restful) {
      // Before we do anything else, check to see if a CO was provided if required.
      // (It might impact our authz decisions.)  If not, redirect to COs controller.
      // We can't check for RESTful here since $this->data isn't set yet for post data.
        
      // The CO might be specified as a named parameter "co" or posted as "Co.id"
      // or posted as "Model.co_id".

      $coid = -1;
      
      if(isset($this->params['named']['co']))
        $coid = $this->params['named']['co'];
      elseif(isset($this->request->data['Co']['id']))
        $coid = $this->request->data['Co']['id'];
      elseif(isset($this->request->data[$this->modelClass]['co_id']))
        $coid = $this->request->data[$this->modelClass]['co_id'];

      if($coid == -1) {
        $this->Session->write('co-select.controller', $this->params['controller']);
        $this->Session->write('co-select.action', $this->params['action']);
        $this->Session->write('co-select.args', array_merge($this->params['named'], $this->params['pass']));
        $this->redirect(array('controller' => 'cos', 'action' => 'select'));
      } else {
        // Retrieve CO Object.  If this model doesn't have a direct relationship, we'll temporarily bind the model
        
        if(!isset($this->Co)) {
          // There might be a CO object under another object (eg: CoOrgIdentityLink),
          // but it's easier if we just explicitly load the model
          
          $this->loadModel('Co');
        }

        $this->cur_co = $this->Co->findById($coid);
        
        if(!empty($this->cur_co)) {
          $this->set("cur_co", $this->cur_co);
        } else {
          $this->Session->setFlash(_txt('er.co.unk-a', array($coid)), '', array(), 'error');
          $this->redirect(array('controller' => 'cos', 'action' => 'select'));
        }
      }
    }
    
    if($this->restful) {
      // Set up basic auth and attempt to login the API user
      
      $this->Auth->authenticate = array('Basic');
      
      if(!$this->Auth->login()) {
        $this->restResultHeader(401, "Unauthorized");
        // We force an exit here to prevent any views from rendering, but also
        // to prevent Cake from dumping the default layout
        $this->response->send();
        exit;
      }
      
      // Disable validation of POST data, which will be an XML document
      // (the security component doesn't know how to validate XML documents)
      $this->Security->validatePost = false;
      $this->Security->csrfCheck = false;
    }
    
    if($this->name == 'Pages' && (!$this->Session->check('Auth.User'))) {
      // Allow the front page to render without authentication. If there is an
      // authenticated user, we want Auth to run to set up authorizations.
      $this->Auth->allow('*');
    }
  }
  
  /**
   * Determine which COmanage platform roles the current user has.
   * - precondition: UsersController::login has run
   * - postcondition: $this->cur_cous set or updated if current user is a subadmin but not an admin
   *
   * @since  COmanage Registry v0.1
   * @return Array An array with values of 'true' if the user has the specified role or 'false' otherwise, with possible keys of
   * - cmadmin: COmanage platform administrator
   * - coadmin: Administrator of the current CO
   * - couadmin: Administrator of one or more COUs within the current CO (rather than set to true, the COUs are enumerated in an array)
   * - comember: Member of the current CO
   * - admin: Valid admin in any CO
   * - subadmin: Valid admin for any COU
   * - user: Valid user in any CO (ie: to the platform)
   * - apiuser: Valid API (REST) user (for now, API users are equivalent to cmadmins)
   * - orgidentityid: Org Identity ID of current user (or false)
   * - copersonid: CO Person ID of current user in current CO (or false)
   */
  
  public function calculateCMRoles() {
    global $group_sep;

    $ret = array(
      'cmadmin' => false,
      'coadmin' => false,
      'couadmin' => false,
      'comember' => false,
      'admin' => false,
      'subadmin' => false,
      'user' => false,
      'apiuser' => false,
      'orgidentityid' => false,
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
          
        // Admin of COU within current CO?
        if(isset($cos[ $this->cur_co['Co']['name'] ]['groups']))
        {
          // COU admins are members of groups named admin{sep}{COU} within the CO
          
          foreach(array_keys($cos[ $this->cur_co['Co']['name'] ]['groups']) as $g)
          {
            $ga = explode($group_sep, $g, 2);
            
            if($ga[0] == "admin" && !empty($ga[1])
               && isset($cos[ $this->cur_co['Co']['name'] ]['groups'][$g]['member'])
               && $cos[ $this->cur_co['Co']['name'] ]['groups'][$g]['member'])
            {
              $ret['couadmin'][] = $ga[1];
            }
          }

          if(!empty($ret['couadmin']))
          {
            // Include children
            $this->loadModel('Cou');

            $ret['couadmin'] = $this->Cou->childCous($ret['couadmin']);
            sort($ret['couadmin']);

            // Promote the set of COUs so they are globally available
            $this->cur_cous =   $ret['couadmin'];
          }
        }
        
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
      
      // Admin of any COU?
      foreach($cos as $c)
      {
        if(isset($c['groups']))
        {
          foreach(array_keys($c['groups']) as $g)
          {
            $ga = explode($group_sep, $g, 2);
            
            if($ga[0] == "admin" && !empty($ga[1])
               && isset($c['groups'][$g]['member']) && $c['groups'][$g]['member'])
            {
              $ret['subadmin'] = true;
              break;
            }
          }
        }
      }
    }

    // Platform user?
    if($this->Session->check('Auth.User.name'))
      $ret['user'] = true;
    
    // API user or Org Person?
    if($this->Session->check('Auth.User.api_user_id'))
    {
      $ret['apiuser'] = true;
      $ret['cmadmin'] = true;  // API users are currently platform admins
    }
    else
    {
      $ret['orgidentities'] = $this->Session->read('Auth.User.org_identities');
    }

    return($ret);
  }
  
  /**
   * For Models that accept a CO Person ID, a CO Person Role ID, or an Org
   * Identity ID, verify that a valid ID was specified.  Also, generate an
   * array suitable for redirecting back to the controller.
   * - precondition: A copersonid, copersonroleid, or orgidentityid must be provided in $this->request (params or data)
   * - postcondition: On error, the session flash message will be set and a redirect will be generated (HTML)
   * - postcondition: On error, HTTP status returned (REST)
   * - postcondition: $redirect will be set (HTML)
   *
   * @since  COmanage Registry v0.1
   * @param  String "force" to force a redirect, "set" to set $redirect, or "default" to perform normal logic
   * @param  Array Retrieved data (with an identifier set in $data[$model])
   * @return Integer 1 OK, 0 No person specified, or -1 Specified person does not exist
   */
  
  function checkPersonID($redirectMode = "default", $data = null) {
    // Get a pointer to our model
    $req = $this->modelClass;
    $model = $this->$req;

    $rc = 0;
    $redirect = array();
    
    // Find a person
    $pids = $this->parsePersonID($data);
    
    $copid = $pids['copersonid'];
    $coprid = $pids['copersonroleid'];
    $orgiid = $pids['orgidentityid'];
    $co = null;
    
    if(!empty($this->request->params['named']['co']))
      $co = $this->request->params['named']['co'];
    elseif(!empty($this->request->data[$req]['co']))
      $co = $this->request->data[$req]['co'];
    elseif(!empty($this->request->data[$req]['co_id']))
      $co = $this->request->data[$req]['co_id'];
      
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
    elseif($coprid != null)
    {
      $redirect['controller'] = 'co_person_roles';

      $x = $model->CoPersonRole->findById($coprid);
      
      if(empty($x))
      {
        $redirect['action'] = 'index';
        $rc = -1;
      }
      else
      {
        $redirect['action'] = 'edit';
        $redirect[] = $coprid;
        if($co != null)
          $redirect['co'] = $co;
        $rc = 1;
      }
    }
    elseif($orgiid != null)
    {
      $redirect['controller'] = 'org_identities';

      $x = $model->OrgIdentity->findById($orgiid);
      
      if(empty($x))
      {
        $redirect['action'] = 'index';
        $rc = -1;
      }
      else
      {
        $redirect['action'] = 'edit';
        $redirect[] = $orgiid;
        if($co != null)
          $redirect['co'] = $co;
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
            $this->Session->setFlash(_txt('er.person.noex'), '', array(), 'error');            
            $this->redirect($redirect);
            break;
          case 0:
            $this->Session->setFlash(_txt('er.person.none'), '', array(), 'error');            
            $this->redirect($redirect);
            break;
        }
      }

      $this->set('redirect', $redirect);
    }
    
    return($rc);
  }
  
  /**
   * Verify that a document POSTed via the REST API exists, and matches the
   * invoking controller.
   * - precondition: $this->request->data holds request data
   * - postcondition: If the correct request type was made but undefined fields were provided, those fields will be set in $invalid_fields
   * - postcondition: On error, HTTP status returned (REST)
   *
   * @since  COmanage Registry v0.4
   * @param  Array Request data (as per $this->request->data)
   * @return boolean true on success, false otherwise
   */
  
  function checkRestPost() {
    if(!empty($this->request->data)) {
      // Currently, we expect all request documents to match the model name (ie: StudlySingular).
      
      $req = $this->modelClass;
      $model = $this->$req;
      $modelcc = Inflector::pluralize($req);
      
      // The inbound formats are currently lists with one entry. (Multiple entries
      // per request are not currently supported.) The format varies slightly between
      // JSON and XML.
      
      if(isset($this->request->data[$modelcc][$req])) {
        // XML
        $reqdata = $this->request->data[$modelcc][$req];
      } elseif(isset($this->request->data[$modelcc][0])) {
        // JSON
        $reqdata = $this->request->data[$modelcc][0];
      } else {
        unset($reqdata);
      }
      
      if(isset($reqdata)) {
        // Check version number against the model. Note we have to check both 'Version'
        // (JSON) and '@Version' (XML).
        
        if((!isset($reqdata['Version']) && !isset($reqdata['@Version']))
           ||
           (isset($reqdata['Version']) && $reqdata['Version'] != $this->$req->version)
           ||
           (isset($reqdata['@Version']) && $reqdata['@Version'] != $this->$req->version)) {
          $this->restResultHeader(400, "Invalid Fields");
          $this->set('invalid_fields', array('Version' => 'Unknown version'));
          
          return(false);
        }
        
        // Check if a CO is required that one was specified.
        // Note beforeFilter() may already have found a CO.
        
        if($this->requires_co && !isset($this->cur_co)) {
          $coid = -1;
          
          if(isset($reqdata['CoId']))
            $coid = $reqdata['CoId'];
          
          if($coid == -1) {
            // The CO might be implied by another attribute
            
            if(isset($reqdata['CoPersonId'])) {
              $this->loadModel('CoPerson');
              $cop = $this->CoPerson->findById($reqdata['CoPersonId']);
              
              // We've already pulled the CO data, so just set it rather than
              // re-retrieving it below
              if(isset($cop['Co']))
                $this->cur_co['Co'] = $cop['Co'];
            }
          }
          
          if(!isset($this->cur_co)) {
            if($coid == -1) {
              $this->restResultHeader(403, "CO Does Not Exist");
              return(false);
            } else {
              // Retrieve CO Object.
              
              if(!isset($this->Co)) {
                // There might be a CO object under another object (eg: CoOrgIdentityLink),
                // but it's easier if we just explicitly load the model
                
                $this->loadModel('Co');
              }
              
              $this->cur_co = $this->Co->findById($coid);
  
              if(empty($this->cur_co)) {
                $this->restResultHeader(403, "CO Does Not Exist");
                return(false);
              }
            }
          }
        }
         
        // Check the expected elements exist in the model (schema).
        // We only check top level at the moment (no recursion).
        
        $bad = array();
        
        if($req == 'CoPersonRole') {
          // We need to check Extended Attributes. This probably belongs in either
          // CoPersonRolesController or CoExtendedAttributesController, but for now
          // it's a one-off and we'll leave it here.
          
          $this->loadModel('CoExtendedAttribute');
          $extAttrs = $this->CoExtendedAttribute->findAllByCoId($this->cur_co['Co']['id']);
        }
        
        foreach(array_keys($reqdata) as $k) {
          // Skip version because we already checked it
          if($k == 'Version' || $k == '@Version')
            continue;
          
          // 'Person' is a special case that we interpret to mean either
          // 'COPersonRole' or 'OrgIdentity', and to reference an id.
          
          if($k == 'Person'
             && (isset($this->$req->validate['org_identity_id'])
                 || isset($this->$req->validate['co_person_role_id']))) {
            continue;
          }
          
          // Some models accept multiple models worth of data in one post.
          // Specifically, OrgIdentity and CoPerson allow Names. A general
          // solution could check (eg) $model->HasOne, however for now we
          // just make a special exception for name.
          
          if($k == 'Name'
             && ($this->modelClass == 'OrgIdentity'
                 || $this->modelClass == 'CoPerson')) {
            continue;
          }
          
          if(isset($extAttrs)) {
            // Check to see if this is an extended attribute
            
            foreach($extAttrs as $ea) {
              if(isset($ea['CoExtendedAttribute']['name'])
                 && $ea['CoExtendedAttribute']['name'] == $k) {
                // Skip to the next $k
                continue 2;
              }
            }
          }
          
          // Finally see if this attribute is defined in the model
          
          if(!isset($this->$req->validate[Inflector::underscore($k)])) {
            $bad[$k] = "Unknown Field";
          }
        }
        
        // Validate enums
        
        if(!empty($model->cm_enum_types)) {
          foreach(array_keys($model->cm_enum_types) as $e) {
            // cm_enum_types is, eg, "status", but we need "Status" since we
            // haven't yet converted the array to database format
            $ce = Inflector::camelize($e);
            
            // Get a pointer to the enum, foo_ti
            // $$ and ${$} is PHP variable variable syntox
            $v = $model->cm_enum_types[$e] . "i";
            global $$v;
            
            if(isset($reqdata[$ce]) && !isset(${$v}[ $reqdata[$ce] ])) {
              $bad[$ce] = "Invalid value";
            }
          }
        }
        
        // We don't currently check for extended attributes because at the
        // moment we don't flag them as required vs optional. Basically, we
        // assume they're all optional.
        
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
  
  /**
   * Convert a result to be suitable for REST views.
   *
   * @since  COmanage Registry v0.1
   * @param  Array Result set, of the format returned by (eg) $this->find()
   * @return Array Converted array
   */
  
  function convertResponse($res) {
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
                $rr[$m][$k] = (bool)true;
              else
                $rr[$m][$k] = (bool)false;
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
        
        if(isset($rr[$m])
           && !preg_match('/Co[0-9]+PersonExtendedAttribute/', $m))
        {
          // Convert keys to CamelCase (XML spec) from under_score (DB spec).
          // Currently, extended attributes are NOT inflected to keep them consistent
          // with their database definitions and to not confuse people unfamiliar
          // with Cake.
          $rr[$m] = $this->responseToCamelCase($rr[$m]);
        }
      }

      $ret[] = $rr;
    }
          
    return($ret);
  }
  
  /**
   * Convert a request from a REST transaction.
   * - precondition: $this->request->data holds request data
   *
   * @since  COmanage Registry v0.4
   * @param  For an edit operation, the corresponding existing data
   * @return Array Result set, of the format of $this->request->data
   */
  
  function convertRestPost($curdata = null) {
    // Get a pointer to our model
    $req = $this->modelClass;
    $model = $this->$req;
    $modelcc = Inflector::pluralize($req);
    
    // Cake creates an array of the form Models[Model][attr] from the POSTed data
    // for XML or Models[0][attr] for JSON (because that's how the JSON and XML
    // formats map). However, we want an array to match how form data is submitted
    // (HTML), so we pass that sub-array to requestToUnderScore to create a new array.
    
    // Unset the version, it was already checked in checkRestPost and will only
    // confuse save()
    
    if(isset($this->request->data[$modelcc][$req])) {
      // XML
      $ret[$req] = $this->requestToUnderScore($this->request->data[$modelcc][$req]);
      unset($ret[$req]['@version']);
    } elseif(isset($this->request->data[$modelcc][0])) {
      // JSON
      $ret[$req] = $this->requestToUnderScore($this->request->data[$modelcc][0]);
      unset($ret[$req]['version']);
    }
    
    // Convert any enums from long form
    
    if(!empty($model->cm_enum_types)) {
      foreach(array_keys($model->cm_enum_types) as $k) {
        // Get a pointer to the enum, foo_ti
        // $$ and ${$} is PHP variable variable syntox
        $v = $model->cm_enum_types[$k] . "i";
        global $$v;
        
        if(isset($ret[$req][$k]) && isset(${$v}[ $ret[$req][$k] ])) {
          $ret[$req][$k] = ${$v}[ $ret[$req][$k] ];
        }
      }
    }
    
    // Convert any booleans
    
    foreach(array_keys($model->validate) as $k) {
      if(isset($model->validate[$k]['rule'])
         && $model->validate[$k]['rule'][0] == 'boolean') {
        if($ret[$req][$k] == 'True')
          $ret[$req][$k] = true;
        else
          $ret[$req][$k] = false;
      }
    }
    
    // Special handling if the doc represents a Person and has a Name attribute
    
    if(($req == 'CoPerson' || $req == 'OrgIdentity')
       && isset($ret[$req]['name'])) {
      global $name_ti;
      
      // Promote name up a level so saveAll sees it as a separate object
      
      $ret['Name'] = $ret[$req]['name'];
      unset($ret[$req]['name']);
      
      if(isset($ret['Name']['type'])) {
        // Convert name type
        $ret['Name']['type'] = $name_ti[ $ret['Name']['type'] ];
      }
      
      if(isset($curdata['Name']['id'])) {
        // For edit operations, Name ID needs to be passed so we replace rather than add.
        // However, the current API spec doesn't pass the Name ID (since the name is
        // embedded in the Person), so we need to copy it over here.
        
        $ret['Name']['id'] = $curdata['Name']['id'];
      }
    }
    
    // Special handling for Extended Attributes
    
    if($req == 'CoPersonRole') {
      // Figure out the name of the dynamic model associated with this CO
      
      $eaModel = 'Co' . $this->cur_co['Co']['id'] . 'PersonExtendedAttribute';
      
      // We need to move extended attributes from $ret[$req][foo] to
      // $ret[Co#PersonExtendedAttribute][foo].
      
      // CoExtendedAttribute model should have been loaded by checkRestPost.
      // Retrieve the defined attributes so we know what to look for.
      $extAttrs = $this->CoExtendedAttribute->findAllByCoId($this->cur_co['Co']['id']);
      
      if(!empty($extAttrs)) {
        foreach($extAttrs as $ea) {
          if(isset($ea['CoExtendedAttribute']['name'])
             && isset($ret[$req][ $ea['CoExtendedAttribute']['name'] ])) {
            // Promote the value to the dynamic model and unset the original
            $ret[$eaModel][ $ea['CoExtendedAttribute']['name'] ] =
              $ret[$req][ $ea['CoExtendedAttribute']['name'] ];
            
            unset($ret[$req][ $ea['CoExtendedAttribute']['name'] ]);
          }
        }
      }
      
      // Additionally, if this is an edit operation, we need to copy the ID for the
      // current row.
      
      if(isset($curdata[$eaModel]['id'])) {
        $ret[$eaModel]['id'] = $curdata[$eaModel]['id'];
      }
    }
    
    // Flatten the Person ID for models that use it
    
    //XXX rewrite this
    if($this->requires_person) {
      if(!empty($ret[$req]['person'])
         && isset($ret[$req]['person']['type'])
         && isset($ret[$req]['person']['id'])) {
        if($ret[$req]['person']['type'] == 'CO')
          $ret[$req]['co_person_id'] = $ret[$req]['person']['id'];
        elseif($ret[$req]['person']['type'] == 'CoRole')
          $ret[$req]['co_person_role_id'] = $ret[$req]['person']['id'];
        elseif($ret[$req]['person']['type'] == 'Org')
          $ret[$req]['org_identity_id'] = $ret[$req]['person']['id'];
          
        unset($ret[$req]['person']);
      }        
    }
    
    return($ret);
  }
  
  /**
   * Generate an error string from a set of invalidFields.
   *
   * @since  COmanage Registry v0.1
   * @param  Array Invald fields, as returned by $Model->invalidFields()
   * @return String Assembled string
   */

  function fieldsErrorToString($fs) {
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
  
  /**
   * For Models that accept a CO Person ID, a CO Person Role ID or an Org Identity ID,
   * find the provided person ID.
   * - precondition: A copersonid, copersonroleid, or orgidentityid must be provided in $this->request (params or data)
   *
   * @since  COmanage Registry v0.1
   * @param  Array Retrieved data (with an identifier set in $data[$model])
   * @return Array copersonid, copersonroleid, orgidentityid (if found)
   */
  
  function parsePersonID($data = null) {
    // Get a pointer to our model
    $req = $this->modelClass;
    $model = $this->$req;
    $modelcc = Inflector::pluralize($req);
    
    // Find a person
    $copid  = null;
    $coprid  = null;
    $orgiid = null;
    
    if(!empty($data[$req]['co_person_id']))
      $copid = $data[$req]['co_person_id'];
    elseif(!empty($data[$req]['co_person_role_id']))
      $coprid = $data[$req]['co_person_role_id'];
    elseif(!empty($data[$req]['org_identity_id']))
      $orgiid = $data[$req]['org_identity_id'];
    elseif(!empty($this->request->params['named']['copersonid']))
      $copid = $this->request->params['named']['copersonid'];
    elseif(!empty($this->request->params['named']['copersonroleid']))
      $coprid = $this->request->params['named']['copersonroleid'];
    elseif(!empty($this->request->params['named']['orgidentityid']))
      $orgiid = $this->request->params['named']['orgidentityid'];
    elseif(!empty($this->request->data[$req]['co_person_id']))
      $copid = $this->request->data[$req]['co_person_id'];
    elseif(!empty($this->request->data[$req]['co_person_role_id']))
      $coprid = $this->request->data[$req]['co_person_role_id'];
    elseif(!empty($this->request->data[$req]['org_identity_id']))
      $orgiid = $this->request->data[$req]['org_identity_id'];
    elseif(isset($this->request->data[$modelcc][0]['Person'])) {
      // API / JSON
      switch($this->request->data[$modelcc][0]['Person']['Type']) {
        case 'CO':
          $copid = $this->request->data[$modelcc][0]['Person']['Id'];
          break;
        case 'CoRole':
          $coprid = $this->request->data[$modelcc][0]['Person']['Id'];
          break;
        case 'Org':
          $orgiid = $this->request->data[$modelcc][0]['Person']['Id'];
          break;
      }
    }
    elseif(isset($this->request->data[$modelcc][$req]['Person'])) {
      // API / XML
      switch($this->request->data[$modelcc][$req]['Person']['Type']) {
        case 'CO':
          $copid = $this->request->data[$modelcc][$req]['Person']['Id'];
          break;
        case 'CoRole':
          $coprid = $this->request->data[$modelcc][$req]['Person']['Id'];
          break;
        case 'Org':
          $orgiid = $this->request->data[$modelcc][$req]['Person']['Id'];
          break;
      }
    }
    elseif(isset($this->request->params['pass'][0])
       && ($this->action == 'delete'
           || $this->action == 'edit'
           || $this->action == 'view'))
    {
      // If we still haven't found anything but we're a delete/edit/view
      // operation, a person ID could be implied by the model.
      
      $rec = $model->findById($this->request->params['pass'][0]);
      
      if(isset($rec[$req]['co_person_id']))
        $copid = $rec[$req]['co_person_id'];
      elseif(isset($rec[$req]['co_person_role_id']))
        $coprid = $rec[$req]['co_person_role_id'];
      elseif(isset($rec[$req]['org_identity_id']))
        $orgiid = $rec[$req]['org_identity_id'];
    }
    
    return(array("copersonid" => $copid,
                 "copersonroleid" => $coprid,
                 "orgidentityid" => $orgiid));
  }
  
  /**
   * Convert a Response array to CamelCase format (used by XML spec) to under_score format (used by database).
   *
   * @since  COmanage Registry v0.1
   * @param  Array Request data
   * @return Array Request data
   */
  
  function requestToUnderScore($a) {
    $r = array();
    
    foreach(array_keys($a) as $k) {
      if(is_array($a[$k]))
        $r[Inflector::underscore($k)] = $this->requestToUnderScore($a[$k]);
      else
        $r[Inflector::underscore($k)] = $a[$k];
    }
    
    return($r);
  }
  
  /**
   * Convert a Response array to CamelCase format (used by XML spec) from under_score format (used by database).
   *
   * @since  COmanage Registry v0.1
   * @param  Array Response data
   * @return Array Response data
   */
  
  function responseToCamelCase($a) {
    $r = array();
    
    foreach(array_keys($a) as $k) {
      if(is_array($a[$k]))
        $r[Inflector::camelize($k)] = $this->responseToCamelCase($a[$k]);
      else
        $r[Inflector::camelize($k)] = $a[$k];
    }

    return($r);
  }

  /**
   * Prepare a REST result HTTP header.
   * - precondition: HTTP headers must not yet have been sent
   * - postcondition: CakeResponse configured with header
   *
   * @since  COmanage Registry v0.1
   * @param  integer HTTP result code
   * @param  string HTTP result comment
   */
  
  function restResultHeader($status, $txt) {
    if(isset($txt)) {
      // We need to update the text associated with $status
      
      $this->response->httpCodes(array($status => $txt));
    }
    
    $this->response->statusCode($status);
  }
}
