<?php
/**
 * Application level Controller
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
 * @since         COmanage Registry v0.1, CakePHP(tm) v 0.2.9
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
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
                             'Api',
                             'Flash',
                             'RequestHandler', // For REST
                             'Role',
                             'Security',
                             'Session',
                             'Paginator');
  
  // We should probably add helpers here instead of in each Controller. To do so,
  // make sure to define the default Html and Form helpers (and Flash).
  // public $helpers = array('Form', 'Html', 'Time', 'Js', 'Permission', 'Session', 'Flash');
  
  // Determine if controller requires a CO to be selected
  public $requires_co = false;
  
  // Determine if controller allows a COU to be selected
  public $allows_cou = false;
  
  // The current CO we are working with (if any)
  public $cur_co = null;
  
  // Determine if controller requires a Person ID to be provided
  public $requires_person = false;

  // Tab to flip to for pages with tabs
  public $redirectTab = null;

  /**
   * Determine which plugins of a given type are available, and load them if not already loaded.
   * - postcondition: Primary Plugin Models are loaded (if requested)
   *
   * @param  String Plugin type, or 'all' for all available plugins
   * @param  String Format to return in: 'list' for list format (suitable for formhelper selects) or 'simple' for a simple list
   * @since  COmanage Registry v0.8
   * @return Array Available plugins
   * @todo   Merge with AppModel::loadAvailablePlugins
   */
  
  public function loadAvailablePlugins($pluginType, $format='list') {
    $ret = array();
    
    foreach(App::objects('plugin') as $p) {
      $this->loadModel($p . "." . $p);
      
      if($this->$p->isPlugin($pluginType != 'all' ? $pluginType : null)) {
        // We do this so that formhelper returns the plugin name instead of a useless index position
        
        switch($format) {
          case 'list':
            $ret[$p] = $p;
            break;
          case 'simple':
          default:
            $ret[] = $p;
            break;
        }
      }
    }
    
    return $ret;
  }
  
  /**
   * Callback before other controller methods are invoked or views are rendered.
   * - precondition:
   * - postcondition: Auth component is configured 
   * - postcondition:
   *
   * @since  COmanage Registry v0.1
   * @throws UnauthorizedException (REST)
   * @throws InvalidArgumentException
   */   
  
  public function beforeFilter() {
    // Load plugin specific texts. We have to do this here because when lang.php is
    // processed by bootstrap.php, AppController isn't loaded yet.
    // XXX CO-351 may take care of this.
    _bootstrap_plugin_txt();
    
    // XXX CO-351 Placeholder
    $this->Session->write('Config.language', 'eng');
    Configure::write('Config.language', $this->Session->read('Config.language'));
    
    // Tell the Auth module to call the controller's isAuthorized() function.
    $this->Auth->authorize = array('Controller');
    
    // Set the redirect and error message for auth failures. Note that we can generate
    // a stack trace instead of a redirect by setting unauthorizedRedirect to false.
    $this->Auth->unauthorizedRedirect = "/";
    $this->Auth->authError = _txt('er.permission');
    // Default flash key is 'auth', switch to 'error' so it maps to noty's default type
    $this->Auth->flash = array('key' => 'error');
    
    if($this->request->is('restful')) {
      // Set up basic auth and attempt to login the API user, unless we're already
      // logged in (ie: via a cookie provided via an AJAX initiated REST call)
      
      if(!$this->Session->check('Auth.User.username')) {
        $this->Auth->authenticate = array('Basic');
        
//      debug(AuthComponent::password($_SERVER['PHP_AUTH_PW']));
        
        // XXX It's unclear why, as of Cake 2.3, we need to manually initialize AuthComponent
        $this->Auth->initialize($this);
        
        if(!$this->Auth->login()) {
          $this->Api->restResultHeader(401, "Unauthorized");
          // We force an exit here to prevent any views from rendering, but also
          // to prevent Cake from dumping the default layout
          $this->response->send();
          exit;
        }
      }
      
      // In order to properly check authz for REST users (not yet fully supported, CO-91)
      // we need to know the CO ID before we can check for authorizations. We'll need to
      // parse the REST body for most use cases to find it.
      
      $this->Api->parseRestRequestDocument();
      
      // We should now be able to find the CO ID, though there won't always be one.
      // (eg: Org Identities pooled, org related record being processed.)
      
      try {
        $coid = $this->parseCOID($this->Api->getData());
        
        if($coid != -1) {
          $this->loadModel('Co');
          
          $args = array();
          $args['conditions']['Co.id'] = $coid;
          $args['contain'] = false;
          
          $this->cur_co = $this->Co->find('first', $args);
        }
      }
      catch(RuntimeException $e) {
        // This is probably $id not found... strictly speaking we should somehow
        // check authorization before returning id not found, but we can't really
        // authorize a request for an invalid id.
        
        $this->Api->restResultHeader(404, "Not Found");
        $this->response->send();
        exit;
      }
      
      // For now, since API users are considered CMP admins (CO-91), calling isAuthorized()
      // is mostly unnecessary. However, there are a couple of calls made by a
      // currently logged in user (reprovisioning, reordering enrollment attributes,
      // etc) where we do need to do this check.
      
      if(!$this->Auth->isAuthorized()) {
        $this->Api->restResultHeader(401, "Unauthorized");
        $this->response->send();
        exit;
      }
      
      // Disable validation of POST data, which may be an XML document
      // (the security component doesn't know how to validate XML documents)
// XXX should re-test this and maybe cut a JIRA
      $this->Security->validatePost = false;
      $this->Security->csrfCheck = false;
    } else { // restful
      // Since we might be delivering unauthenticated views, set a timezone.
      // See if we've collected it from the browser in a previous page load. Otherwise
      // use the system default. If the user set a preferred timezone, we'll catch that below.
      
      if(!empty($_COOKIE['cm_registry_tz_auto'])) {
        // We have an auto-detected timezone from a previous page render from the browser.
        // Adjust the default timezone. Actually, don't we want to always record times in UTC.
        //        date_default_timezone_set($_COOKIE['cm_registry_tz_auto']);
        $this->set('vv_tz', $_COOKIE['cm_registry_tz_auto']);
      } else {
        $this->set('vv_tz', date_default_timezone_get());
      }

      // Before we do anything else, check to see if a CO was provided.
      // (It might impact our authz decisions.) Note that some models (eg: MVPAs)
      // might specify a CO, but might not. As of v0.6, we no longer redirect to
      // cos/select if we don't find a CO but one is required. Instead, we throw
      // an error.
      
      // The CO might be specified as a named parameter "co" or posted as "Co.id"
      // or posted as "Model.co_id".
      
      $coid = $this->parseCOID();
      
      if($coid == -1) {
        if($this->requires_co) {
          throw new InvalidArgumentException(_txt('er.co.specify'));
        }
      } else {
        // Retrieve CO Object.  If this model doesn't have a direct relationship, we'll temporarily bind the model
        
        if(!isset($this->Co)) {
          // There might be a CO object under another object (eg: CoOrgIdentityLink),
          // but it's easier if we just explicitly load the model
          
          $this->loadModel('Co');
        }
        
        $args = array();
        $args['conditions']['Co.id'] = $coid;
        $args['contain'] = false;
        
        $this->cur_co = $this->Co->find('first', $args);
        
        if(!empty($this->cur_co)) {
          $this->set("cur_co", $this->cur_co);
          
          // Load dynamic texts. We do this here because lang.php doesn't have access to models yet.
          
          global $cm_texts;
          global $cm_lang;
          
          $this->loadModel('CoLocalization');
          
          $args = array();
          $args['conditions']['CoLocalization.co_id'] = $coid;
          $args['conditions']['CoLocalization.language'] = $cm_lang;
          $args['fields'] = array('CoLocalization.lkey', 'CoLocalization.text');
          $args['contain'] = false;
          
          $ls = $this->CoLocalization->find('list', $args);
          
          if(!empty($ls)) {
            $cm_texts[$cm_lang] = array_merge($cm_texts[$cm_lang], $ls);
          }
          
          // Perform a bit of a sanity check before we get any further
          try {
            $this->verifyRequestedId();
          }
          catch(InvalidArgumentException $e) {
            $this->Flash->set($e->getMessage(), array('key' => 'error'));
            $this->redirect("/");
          }

          // See if there are any pending Terms and Conditions. If so, redirect the user.
          // But don't do this if the current request is for T&C. We might also consider
          // skipping for admins. Pending T&C are retrieved by UsersController at login.
          // It would be cleaner to retrieve them here, but more efficient once at login
          // rather than before each request.
          
          if($this->modelClass != 'CoTermsAndConditions'
             // Also skip CoSetting so that an admin can change the mode
             && $this->modelClass != 'CoSetting'
             // We skip CoPetition because of the situation where somebody is onboarded
             // via conscription or an OIS sync (and so never actually logged into Registry)
             // and then subsequently attempts to run an enrollment flow (eg account linking).
             // Enrollment Flows can be configured to enforce T&C if needed.
             && $this->modelClass != 'CoPetition') {
            $tandc = $this->Session->read('Auth.User.tandc.pending.' . $this->cur_co['Co']['id']);
            
            if(!empty($tandc)) {
              // Un-agreed T&C, redirect to review
              
              // Pull the CO Person from the session info. There should probable be a
              // better way to get it.
              
              $cos = $this->Session->read('Auth.User.cos');
              
              $args = array(
                'controller' => 'co_terms_and_conditions',
                'action'     => 'review',
                'copersonid' => $cos[ $this->cur_co['Co']['name'] ]['co_person_id'],
                'mode'       => 'login'
              );
              
              $this->redirect($args);
            }
          }
          
          // Check to see if the user set a preferred timezone.
          
          $cos = $this->Session->read('Auth.User.cos');
          $coName = $this->cur_co['Co']['name'];
          
          if(!empty($cos[$coName]['co_person']['timezone'])) {
            $this->set('vv_tz', $cos[$coName]['co_person']['timezone']);
          }
        } else {
          $this->Flash->set(_txt('er.co.unk-a', array($coid)), array('key' => 'error'));
          $this->redirect("/");
        }
      }
    }
    
    // Update validation rules in case (eg) attribute enumerations are defined
    $model = $this->modelClass;
    
    $this->$model->updateValidationRules(isset($this->cur_co['Co']['id']) ? $this->cur_co['Co']['id'] : null);
  }

  /**
   * Callback before views are rendered.
   * - precondition: None
   * - postcondition: content and permissions for menu are set
   *
   * @since  COmanage Registry v0.5
   */

  function beforeRender() {
    // Determine what is shown for menus
    // Called before each render in case permissions change
    if(!$this->request->is('restful')) {
      $this->getTheme();
      
      if($this->Session->check('Auth.User.org_identities')) {
        $this->menuAuth();
        $this->menuContent();
        $this->getNavLinks();
        $this->getNotifications();
      }
    }
  }
  
  /**
   * Determine the CO ID based on some attribute of the request.
   *
   * @since  COmanage Registry v0.8.4
   * @param  Array $data Array of data for parsing Person ID
   * @return Integer CO ID, or null if not implemented or not applicable.
   * @throws InvalidArgumentException
   */
  
  protected function calculateImpliedCoId($data = null) {
    // As a default, we'll see if we can determine the CO in a generic manner.
    // Where this doesn't work, individual Controllers can override this function.
    
    if($this->modelClass == 'CakeError') {
      return;
    }
    
    // Determine if Org Identities are pooled
    $this->loadModel('CmpEnrollmentConfiguration');
    $orgPooled = $this->CmpEnrollmentConfiguration->orgIdentitiesPooled();
    
    // See if what we're adding/selecting/viewing is attached to a person
    $p = $this->parsePersonID($data);
    
    if(!$this->requires_co
       && (!$this->requires_person
           ||
           // MVPA controllers operating on org identities where pool_org_identities
           // is false will not specify/require a CO
           ($orgPooled
            && !empty($p['orgidentityid'])))) {
      // Controllers that don't require a CO generally can't imply one.
      return null;
    }
    
    // Get a pointer to our model
    $req = $this->modelClass;
    $model = $this->$req;
    $modelpl = Inflector::tableize($req);
    
    // XXX This list should really be set on a per-Controller basis (eg: link only applies to CoPeople)
    // As of v3.1.0, we will now look at $impliedCoIdActions. XXX Backport to other controllers. (CO-959)
    if($this->action == 'add'
       || $this->action == 'addKeyFile' // for SshKeysController
       || $this->action == 'assign'
       || $this->action == 'find'  // for OrgIdentitiesController
       || $this->action == 'index'
       || $this->action == 'link'
       || $this->action == 'select'
       || $this->action == 'review'
       // We don't currently do anything with the value for impliedCoIdActions, but we could...
       || isset($this->impliedCoIdActions[ $this->action ])) {
      if(!empty($p['codeptid']) && (isset($model->CoDepartment))) {
        $CoDepartment = $model->CoDepartment;
        
        $coId = $CoDepartment->field('co_id', array('id' => $p['codeptid']));
        
        if($coId) {
          return $coId;
        } else {
          throw new InvalidArgumentException(_txt('er.notfound',
                                                  array(_txt('ct.co_departments.1'),
                                                        filter_var($p['codeptid'],FILTER_SANITIZE_SPECIAL_CHARS))));
        }
      } elseif(!empty($p['copersonid'])
         && (isset($model->CoPerson) || isset($model->Co))) {
        $CoPerson = (isset($model->CoPerson) ? $model->CoPerson : $model->Co->CoPerson);
        
        $coId = $CoPerson->field('co_id', array('id' => $p['copersonid']));
        
        if($coId) {
          return $coId;
        } else {
          throw new InvalidArgumentException(_txt('er.notfound',
                                                  array(_txt('ct.co_people.1'),
                                                        filter_var($p['copersonid'],FILTER_SANITIZE_SPECIAL_CHARS))));
        }
      } elseif(!empty($p['copersonroleid']) && isset($model->CoPersonRole)) {
        $args = array();
        $args['conditions']['CoPersonRole.id'] = $p['copersonroleid'];
        $args['joins'][0]['table'] = 'co_person_roles';
        $args['joins'][0]['alias'] = 'CoPersonRole';
        $args['joins'][0]['type'] = 'INNER';
        $args['joins'][0]['conditions'][0] = 'CoPerson.id=CoPersonRole.co_person_id';
        $args['contain'] = false;
        
        $obj = $model->CoPersonRole->CoPerson->find('first', $args);
        
        if(!empty($obj['CoPerson']['co_id'])) {
          return $obj['CoPerson']['co_id'];
        } else {
          throw new InvalidArgumentException(_txt('er.notfound',
                                                  array(_txt('ct.co_person_roles.1'),
                                                        filter_var($p['copersonroleid'],FILTER_SANITIZE_SPECIAL_CHARS))));
        }
      } elseif(!empty($p['orgidentityid'])) {
        if(isset($model->OrgIdentity)) {
          $coId = $model->OrgIdentity->field('co_id', array('id' => $p['orgidentityid']));
        } elseif(isset($model->CoOrgIdentityLink->OrgIdentity)) {
          $coId = $model->CoOrgIdentityLink->OrgIdentity->field('co_id', array('id' => $p['orgidentityid']));
        }
        
        if($coId) {
          return $coId;
        } else {
          throw new InvalidArgumentException(_txt('er.notfound',
                                                  array(_txt('ct.org_identities.1'),
                                                        filter_var($p['orgidentityid'],FILTER_SANITIZE_SPECIAL_CHARS))));
        }
      } elseif(!empty($this->request->params['named']['cogroup']) && isset($model->CoGroup)) {
        // Map the group to a CO
        $coId = $model->CoGroup->field('co_id', array('id' => $this->request->params['named']['cogroup']));
        
        if($coId) {
          return $coId;
        } else {
          throw new InvalidArgumentException(_txt('er.notfound',
                                                  array(_txt('ct.co_groups.1'),
                                                        filter_var($this->request->params['named']['cogroup'],FILTER_SANITIZE_SPECIAL_CHARS))));
        }
      }
    }
    
    // If we get here, assume the parameter is an object ID
    
    if(!empty($this->request->params['pass'][0])) {
      try {
        $recordCoId = $model->findCoForRecord($this->request->params['pass'][0]);
      }
      catch(InvalidArgumentException $e) {
        throw new InvalidArgumentException($e->getMessage());
      }
      
      return $recordCoId;
    }
    
    // Possibly via a form
    
    if(!empty($this->request->data[$req]['id'])) {
      try {
        $recordCoId = $model->findCoForRecord($this->request->data[$req]['id']);
      }
      catch(InvalidArgumentException $e) {
        throw new InvalidArgumentException($e->getMessage());
      }
      
      return $recordCoId;
    }
    
    return null;
  }

  /**
   * For Models that accept a CO Person ID, a CO Person Role ID, or an Org
   * Identity ID, verify that a valid ID was specified.  Also, generate an
   * array suitable for redirecting back to the controller.
   * Effective with v3.1.0, a CO Department ID is also considered a "Person",
   * since MVPAs are being extended to cover Departments.
   * - precondition: A copersonid, copersonroleid, or orgidentityid must be provided in $this->request (params or data)
   * - postcondition: On error, the session flash message will be set and a redirect will be generated (HTML)
   * - postcondition: On error, HTTP status returned (REST)
   * - postcondition: $redirect will be set (HTML)
   *
   * @since  COmanage Registry v0.1
   * @param  String "force" to force a redirect, "set" to set $redirect, "calculate" to not ever redirect, or "default" to perform normal logic
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
    $codeptid = $pids['codeptid'];
    $orgiid = $pids['orgidentityid'];
    $co = null;
    
    if(!empty($this->request->params['named']['co']))
      $co = $this->request->params['named']['co'];
    elseif(!empty($this->request->data[$req]['co']))
      $co = $this->request->data[$req]['co'];
    elseif(!empty($this->request->data[$req]['co_id']))
      $co = $this->request->data[$req]['co_id'];
    
    if($this->redirectTab != null && $orgiid) {
      $redirect['tab'] = $this->redirectTab;
    }

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
        $redirect['action'] = 'canvas';
        $redirect[] = $copid;
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
    elseif($codeptid != null)
    {
      $redirect['controller'] = 'co_departments';

      $x = $model->CoDepartment->findById($codeptid);
      
      if(empty($x))
      {
        $redirect['action'] = 'index';
        $rc = -1;
      }
      else
      {
        $redirect['action'] = 'edit';
        $redirect[] = $codeptid;
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
    
    if($redirectMode == "force") {
      $this->redirect($redirect);
    } elseif($redirectMode == "set") {
      $this->set('redirect', $redirect);
    } elseif($redirectMode != "calculate") {
      switch($rc) {
        case -1:
          $this->Flash->set(_txt('er.person.noex'), array('key' => 'error'));            
          $this->redirect($redirect);
          break;
        case 0:
          $this->Flash->set(_txt('er.person.none'), array('key' => 'error'));            
          $this->redirect($redirect);
          break;
      }
      
      $this->set('redirect', $redirect);
    }
    
    return $rc;
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
   * Called from beforeRender to set CO-specific links
   * - precondition: Session.Auth holds data used for authz decisions
   * - postcondition: vv_CoNavLinks set
   *
   * @since  COmanage Registry v0.8.2
   */

  function getNavLinks() {
    // Get CMP-level navigation links 
    $this->loadModel('NavigationLink');

    $params = array('fields'     => array('NavigationLink.title', 'NavigationLink.url'),
                    'order'      => array('NavigationLink.ordr')
    );
    $linkdata = $this->NavigationLink->find('all', $params);

    // Build variable to set for view
    $vv_NavLinks = array();

    foreach ($linkdata as $l) {
      $vv_NavLinks[] = $l;
    }
    $this->set('vv_NavLinks', $vv_NavLinks);

    // Determine this CO's navigation links
    $coid = $this->parseCOID();

    if(!empty($coid)) {
      $this->loadModel('CoNavigationLink');

      $params = array('conditions' => array('CoNavigationLink.co_id' => $coid),
                      'fields'     => array('CoNavigationLink.title', 'CoNavigationLink.url'),
                      'order'      => array('CoNavigationLink.ordr')
      );
      $colinkdata = $this->CoNavigationLink->find('all', $params);

      // Build variable to set for view
      $vv_CoNavLinks = array();

      foreach ($colinkdata as $l) {
        $vv_CoNavLinks[] = $l;
      }
      $this->set('vv_CoNavLinks', $vv_CoNavLinks);
    }
  }
  
  /**
   * Obtain notifications for the currently logged in user, if any
   * - precondition: Session.Auth holds current CO Person ID
   * - postcondition: $vv_my_notifications will be set
   *
   * @since  COmanage Registry v0.8.4
   */
  
  protected function getNotifications() {
    $this->loadModel('CoNotification');
    
    $copersonid = $this->Session->read('Auth.User.co_person_id');
    
    if(!isset($this->cur_co)) {
      $mycos = $this->Session->read('Auth.User.cos');
      
      if(!empty($mycos)) {
        $n = array();
        
        foreach($mycos as $co) {
          if(!empty($co['co_person_id'])) {
            $n[] = array(
              'co_id'         => $co['co_id'],
              'co_name'       => $co['co_name'],
              'co_person_id'  => $co['co_person_id'],
              'notifications' => $this->CoNotification->pending($co['co_person_id'])
            );
          }
        }
        
        $this->set('vv_all_notifications', $n);
      }
    } else {
      if(!empty($copersonid)) {
        $this->set('vv_my_notifications', $this->CoNotification->pending($copersonid));
        $this->set('vv_co_person_id_notifications', $copersonid);
      }
    }
  }
  
  /**
   * Obtain configured theme, if any
   * - postcondition: Theme view variable set
   *
   * @since  COmanage Registry v2.0.0
   */
  
  protected function getTheme() {
    // Determine if a theme is in use
    $coTheme = null;
    
    if(!empty($this->cur_co['Co']['id'])) {
      // First see if we're in an enrollment flow
      if($this->name == 'CoPetitions') {
        $efId = $this->enrollmentFlowID();
        
        if($efId > -1) {
          // See if this enrollment flow has a theme configured
          
          $args = array();
          $args['conditions']['CoEnrollmentFlow.id'] = $efId;
          $args['contain'][] = 'CoTheme';
          
          $efConfig = $this->Co->CoEnrollmentFlow->find('first', $args);
          
          if(!empty($efConfig['CoTheme']['id'])) {
            $coTheme = $efConfig['CoTheme'];
          }
        }
      }
      
      if(!$coTheme) {
        // See if there is a CO-wide theme in effect
        
        $args = array();
        $args['conditions']['CoSetting.co_id'] = $this->cur_co['Co']['id'];
        $args['contain'][] = 'CoTheme';
        
        $settings = $this->Co->CoSetting->find('first', $args);
        
        if(!empty($settings['CoTheme']['id'])) {
          $coTheme = $settings['CoTheme'];
        }
      }
    }
    
    if(!$coTheme) {
      // See if there is a platform theme
      $args = array();
      $args['joins'][0]['table'] = 'cos';
      $args['joins'][0]['alias'] = 'Co';
      $args['joins'][0]['type'] = 'INNER';
      $args['joins'][0]['conditions'][0] = 'CoSetting.co_id=Co.id';
      $args['conditions']['Co.name'] = 'COmanage';
      $args['conditions']['Co.status'] = StatusEnum::Active;
      $args['contain'][] = 'CoTheme';
      
      $this->loadModel('CoSetting');
      
      $settings = $this->CoSetting->find('first', $args);
      
      if(!empty($settings['CoTheme']['id'])) {
        $coTheme = $settings['CoTheme'];
      }
    }
      
    if($coTheme) {
      $this->set('vv_theme_hide_title', $coTheme['hide_title']);
      $this->set('vv_theme_hide_footer_logo', $coTheme['hide_footer_logo']);
      
      if(!empty($coTheme['css'])) {
        $this->set('vv_theme_css', $coTheme['css']);
      }
      
      if(!empty($coTheme['header'])) {
        $this->set('vv_theme_header', $coTheme['header']);
      }
      
      if(!empty($coTheme['footer'])) {
        $this->set('vv_theme_footer', $coTheme['footer']);
      }
    }    
  }
  
  /**
   * Called from beforeRender to set permissions for display in menus
   * - precondition: Session.Auth holds data used for authz decisions
   * - postcondition: permissions for menu are set
   *
   * @since  COmanage Registry v0.5
   */

  function menuAuth() {
    $roles = $this->Role->calculateCMRoles();
    
    // Construct the permission set for this user, which will also be passed to the view.
    $p = array();
    
    // If permissions already exist, don't overwrite them
    if(isset($this->viewVars['permissions']))
      $p = $this->viewVars['permissions'];

    // Determine what menu options this user can see

    // View own (Org) profile?
    $p['menu']['orgprofile'] = $roles['user'];
    
    // View/Edit own (CO) profile?
    $p['menu']['coprofile'] = $roles['user'];
    
    // View/Edit CO groups?
    $p['menu']['cogroups'] = $roles['cmadmin'] || $roles['user'];
    
    // Manage org identity data?
    $p['menu']['orgidentities'] = $roles['cmadmin'] || $roles['coadmin'] || $roles['couadmin'];
    
    // Manage the CO (or COU) population?
    // XXX This permission is somewhat confusingly named (implies cmp admin managing COs)
    // as is 'admin' below (which really implies cmadmin)
    $p['menu']['cos'] = $roles['cmadmin'] || $roles['coadmin'] || $roles['couadmin'];
    // Which COUs?
    $p['menu']['admincous'] = $roles['admincous'];
    
    // Manage Authenticators?
    $p['menu']['authenticator'] = $roles['cmadmin'] || $roles['coadmin'];
    
    // Manage CO level attribute enumerations?
    $p['menu']['coattrenums'] = $roles['cmadmin'] || $roles['coadmin'];
    
    // Manage any CO configuration?
    $p['menu']['coconfig'] = $roles['cmadmin'] || $roles['coadmin'];
    
    // View CO departments?
    $p['menu']['codepartments'] = $roles['cmadmin'];
    
    if(!$roles['cmadmin']
       && $roles['user']
       && !empty($this->cur_co['Co']['id'])) {
      // Only render departments link for regular users if departments are defined
      $args = array();
      $args['conditions']['CoDepartment.co_id'] = $this->cur_co['Co']['id'];
      
      $this->loadModel('CoDepartment');
      
      $p['menu']['codepartments'] = (boolean)$this->CoDepartment->find('count', $args);
    }
    
    // Select from available enrollment flows?
    $p['menu']['createpetition'] = $roles['user'];
    
    // Invite (default enrollment) new CO people?
    $p['menu']['invite'] = $roles['cmadmin'] || $roles['coadmin'] || $roles['subadmin'];
    
    // Review / approve petitions?
    // XXX this isn't exactly the right check, but then neither are most of the others (CO-731)
    $p['menu']['petitions'] = $roles['cmadmin'] || $roles['coadmin']
    // XXX A side effect of this current logic is that the link only appears when the person is viewing
    // another link with the CO specified in it (otherwise copersonid isn't set)
                              || ($roles['copersonid'] && $this->Role->isApprover($roles['copersonid']));
    
    // Manage CO extended attributes?
    $p['menu']['extattrs'] = $roles['cmadmin'] || $roles['coadmin'];
    
    // Manage CO extended types?
    $p['menu']['exttypes'] = $roles['cmadmin'] || $roles['coadmin'];
    
    // Manage CO identifier assignments?
    $p['menu']['idassign'] = $roles['cmadmin'] || $roles['coadmin'];
    
    // Manage CO identifier validations?
    $p['menu']['idvalidate'] = $roles['cmadmin'] || $roles['coadmin'];
    
    // Manage COU definitions?
    $p['menu']['cous'] = $roles['cmadmin'] || $roles['coadmin'];

    // Manage CO Email Lists
    $p['menu']['colists'] = $roles['cmadmin'] || $roles['coadmin'];
    
    // Manage CO enrollment flow definitions?
    $p['menu']['coef'] = $roles['cmadmin'] || $roles['coadmin'];
    
    // Manage CO Jobs?
    $p['menu']['cojobs'] = $roles['cmadmin'] || $roles['coadmin'];
    
    // Manage CO Localizations?
    $p['menu']['colocalizations'] = $roles['cmadmin'] || $roles['coadmin'];
    
    // Manage CO Message Templates
    $p['menu']['comessagetemplates'] = $roles['cmadmin'] || $roles['coadmin'];
  
    // Manage CO Links?
    $p['menu']['conavigationlinks'] = $roles['cmadmin'] || $roles['coadmin'];

    // Manage CO Permissions?
    $p['menu']['copipelines'] = $roles['cmadmin'] || $roles['coadmin'];
  
    // Manage CO provisioning targets?
    $p['menu']['coprovtargets'] = $roles['cmadmin'] || $roles['coadmin'];
    
    // Manage CO self service permissions?
    $p['menu']['coselfsvcperm'] = $roles['cmadmin'] || $roles['coadmin'];
    
    // Manage CO services?
    $p['menu']['coservices'] = $roles['cmadmin'] || $roles['coadmin'];
    
    // Manage CO settings?
    $p['menu']['cosettings'] = $roles['cmadmin'] || $roles['coadmin'];
    
    // Manage CO terms and conditions?
    $p['menu']['cotandc'] = $roles['cmadmin'] || $roles['coadmin'];
    
    // Manage CO themes?
    $p['menu']['cothemes'] = $roles['cmadmin'] || $roles['coadmin'];
    
    // Manage CO expiration policies?
    $p['menu']['coxp'] = $roles['cmadmin'] || $roles['coadmin'];
      
    // Admin COmanage?
    $p['menu']['admin'] = $roles['cmadmin'];
    
    // Manage NSF Demographics?
    $p['menu']['co_nsf_demographics'] = $roles['cmadmin'];
    
    // View/Edit own Demographics profile?
    $p['menu']['nsfdemoprofile'] = $roles['user'];
    
    // Manage org identity sources? CO Admins can only do this if org identities are NOT pooled
    $this->loadModel('CmpEnrollmentConfiguration');
    
    if(!$this->CmpEnrollmentConfiguration->orgIdentitiesPooled()) {
      $p['menu']['orgidsources'] = $roles['cmadmin'] || $roles['coadmin'];
    } else {
      $p['menu']['orgidsources'] = false;
    }
    
    $this->set('permissions', $p);
  }

  /**
   * Called from beforeRender to set content for display in menus
   * - precondition: Session.Auth holds data used for authz decisions
   * - postcondition: content for menu are set
   *
   * @since  COmanage Registry v0.5
   */

  function menuContent() {
    // Get org identity ID
    $orgIDs = $this->Session->read('Auth.User.org_identities');
    
    if(!empty($orgIDs)) {
      // Find name associated with that ID
      $this->loadModel('OrgIdentity');
      $orgName = $this->OrgIdentity->read('o',$orgIDs[0]['org_id']);
      
      // Set for home ID name in menu
      $menu['orgName'] = $orgName['OrgIdentity']['o'];
    }
    
    // Determine if Org Identities are pooled
    $this->loadModel('CmpEnrollmentConfiguration');
    $this->set('pool_org_identities', $this->CmpEnrollmentConfiguration->orgIdentitiesPooled());
    
    // Set the COs for display. Start with the user's COs.
    
    if($this->Session->check('Auth.User.cos')) {
      $menu['cos'] = $this->Session->read('Auth.User.cos');
    } else {
      $menu['cos'] = array();
    }
    
    $this->loadModel('Co');
    
    if($this->viewVars['permissions']['menu']['admin']) {
      // Show all active COs for admins
      $params = array('conditions' => array('Co.status' => StatusEnum::Active),
                      'fields'     => array('Co.id', 'Co.name', 'Co.description'),
                      'recursive'  => false
                     );
      $codata = $this->Co->find('all', $params);

      foreach($codata as $data) {
        // Don't clobber the COs we've already loaded
        if(!isset($menu['cos'][ $data['Co']['name'] ])) {
          $menu['cos'][ $data['Co']['name'] ] = array(
            'co_id'   => $data['Co']['id'],
            'co_name' => $data['Co']['name'] . " (" . _txt('er.co.notmember') . ")",
            'co_desc' => $data['Co']['description']
          );
        }
      }
    }
    
    // Pull the list of services, which could be non-empty even
    // for anonymous access
    if(!empty($this->cur_co['Co']['id'])) {
      $this->loadModel('CoService');
      
      $menu['services'] = $this->CoService->findServicesByPerson($this->Role,
                                                                 $this->cur_co['Co']['id'],
                                                                 $this->Session->read('Auth.User.co_person_id'),
                                                                 false);
    }
    
    // Pull the list of COUs and their names. Primarily intended for CO Service portal.
    $args = array();
    $args['conditions']['Cou.co_id'] = $this->cur_co['Co']['id'];
    $args['fields'] = array('Cou.id', 'Cou.name');
    $args['contain'] = false;
    
    $menu['cous'] = $this->Co->Cou->find('list', $args);
    
    // Determine what menu contents plugins want available
    $plugins = $this->loadAvailablePlugins('all', 'simple');
    
    foreach($plugins as $plugin) {
      if(method_exists($this->$plugin, 'cmPluginMenus')) {
        $menu['plugins'][$plugin] = $this->$plugin->cmPluginMenus();
      }
    }
    
    $this->set('menuContent', $menu);
    
    // An a temporary workaround for CO-720, determine which COs have enrollment flows
    // defined. Once CO-828 is done, this could be replaced by examining $this->cur_co
    // (or similar) instead, since we won't have a big multi-CO menu.
    
    $args = array();
    $args['conditions']['CoEnrollmentFlow.status'] = EnrollmentFlowStatusEnum::Active;
    $args['fields'][] = 'DISTINCT CoEnrollmentFlow.co_id';
    $args['order'][] = 'CoEnrollmentFlow.co_id ASC';
    $args['contain'] = false;
    
    $this->loadModel('CoEnrollmentFlow');
    $this->set('vv_enrollment_flow_cos', $this->CoEnrollmentFlow->find('all', $args));
  }
  
  /**
   * For Models that accept a CO ID, find the provided CO ID.
   * - precondition: A coid must be provided in $this->request (params or data)
   *
   * @since  COmanage Registry v0.6
   * @param  Array $data Array of data for calculating implied CO ID
   * @return Integer The CO ID if found, or -1 if not
   */
  
  function parseCOID($data = null) {
    // Get a pointer to our model
    $req = $this->modelClass;
    $model = $this->$req;
    $coid = null;
    
    try {
      // First try to look up the CO ID based on the request.
      $coid = $this->calculateImpliedCoId($data);
    }
    catch(Exception $e) {
      // Most likely no CO found, so just keep going
    }
    
    if(!$coid) {
      $coid = -1;
      
      // Only certain actions are permitted to explicitly provide a CO ID
      // XXX Note that CoExtendedTypesController, CoDashboardsController, and others override
      // this function to support addDefaults. It might be better just to allow controllers
      // to specify a list.
      if($this->action == 'index'
         || $this->action == 'find'
         || $this->action == 'search'
         // Add and select operations only when attached directly to a CO (otherwise we need
         // to pull the CO ID from the object being attached to, eg co person).
         ||
         (isset($model->Co)
          && ($this->action == 'select' || $this->action == 'add'))) {
        if(isset($this->params['named']['co'])) {
          $coid = $this->params['named']['co'];
        }
        // CO ID can be passed via a form submission
        elseif($this->action != 'index') {
          if(isset($this->request->data['Co']['id'])) {
            $coid = $this->request->data['Co']['id'];
          } elseif(isset($this->request->data[$req]['co_id'])) {
            $coid = $this->request->data[$req]['co_id'];
          }
        }
      }
    }
    
    return $coid;
  }
  
  /**
   * For Models that accept a CO Person ID, a CO Person Role ID or an Org Identity ID,
   * find the provided person ID. Effective v3.1.0, CO Department ID is considered a person ID
   * for MVPA purposes.
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
    $deptid = null;
    $orgiid = null;
    
    if(!empty($data['co_person_id']))
      $copid = $data['co_person_id'];
    elseif(!empty($data['co_person_role_id']))
      $coprid = $data['co_person_role_id'];
    elseif(!empty($data['org_identity_id']))
      $orgiid = $data['org_identity_id'];
    elseif(!empty($data['co_department_id']))
      $deptid = $data['co_department_id'];
    elseif(!empty($data[$req]['co_person_id']))
      $copid = $data[$req]['co_person_id'];
    elseif(!empty($data[$req]['co_person_role_id']))
      $coprid = $data[$req]['co_person_role_id'];
    elseif(!empty($data[$req]['org_identity_id']))
      $orgiid = $data[$req]['org_identity_id'];
    elseif(!empty($data[$req]['co_department_id']))
      $deptid = $data[$req]['co_department_id'];
    elseif(!empty($this->request->data[$req]['co_person_id']))
      $copid = $this->request->data[$req]['co_person_id'];
    elseif(!empty($this->request->data[$req]['co_person_role_id']))
      $coprid = $this->request->data[$req]['co_person_role_id'];
    elseif(!empty($this->request->data[$req]['org_identity_id']))
      $orgiid = $this->request->data[$req]['org_identity_id'];
    elseif(!empty($this->request->data[$req]['codeptid']))
      $deptid = $this->request->data[$req]['codeptid'];
    elseif(!empty($this->request->params['named']['copersonid']))
      $copid = $this->request->params['named']['copersonid'];
    elseif(!empty($this->request->params['named']['copersonroleid']))
      $coprid = $this->request->params['named']['copersonroleid'];
    elseif(!empty($this->request->params['named']['orgidentityid']))
      $orgiid = $this->request->params['named']['orgidentityid'];
    elseif(!empty($this->request->params['named']['codeptid']))
      $deptid = $this->request->params['named']['codeptid'];
    elseif(isset($this->request->data[$modelcc][0]['Person'])) {
      // API / JSON
      switch($this->request->data[$modelcc][0]['Person']['Type']) {
        case 'CO':
          $copid = $this->request->data[$modelcc][0]['Person']['Id'];
          break;
        case 'CoRole':
          $coprid = $this->request->data[$modelcc][0]['Person']['Id'];
          break;
        case 'Dept':
          $deptid = $this->request->data[$modelcc][0]['Person']['Id'];
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
        case 'Dept':
          $deptid = $this->request->data[$modelcc][$req]['Person']['Id'];
          break;
        case 'Org':
          $orgiid = $this->request->data[$modelcc][$req]['Person']['Id'];
          break;
      }
    }
    elseif(!empty($this->request->params['pass'][0])
       && ($this->action == 'delete'
           || $this->action == 'edit'
           || $this->action == 'view'))
    {
      // If we still haven't found anything but we're a delete/edit/view
      // operation, a person ID could be implied by the model.
      
      $args = array();
      $args['conditions'][$req.'.id'] = $this->request->params['pass'][0];
      $args['contain'] = false;
      
      $rec = $model->find('first', $args);
      
      if(isset($rec[$req]['co_person_id']))
        $copid = $rec[$req]['co_person_id'];
      elseif(isset($rec[$req]['co_person_role_id']))
        $coprid = $rec[$req]['co_person_role_id'];
      elseif(isset($rec[$req]['org_identity_id']))
        $orgiid = $rec[$req]['org_identity_id'];
      elseif(isset($rec[$req]['co_department_id']))
        $deptid = $rec[$req]['co_department_id'];
    }
    
    return(array("codeptid" => $deptid,
                 "copersonid" => $copid,
                 "copersonroleid" => $coprid,
                 "orgidentityid" => $orgiid));
  }
  
  /**
   * Perform a sanity check on on a standard item identifier to verify it is part
   * of the current CO.
   *
   * @since  COmanage Registry v0.8
   * @return Boolean True if sanity check is successful
   * @throws InvalidArgumentException
   * @todo   This can probably be thrown away after CO-620 is fully implemented
   */

  public function verifyRequestedId() {
    if(!empty($this->request->params['plugin'])) {
      // If we're accessing a plugin, Cake appears to not yet have loaded the associated
      // model (probably because it's not defined in $uses anywhere), so force it to load.
      
      $m = Inflector::classify($this->request->params['plugin'])
         . "."
         . Inflector::classify($this->request->params['controller']);
      
      $this->loadModel($m);
    }
    
    if(empty($this->cur_co)) {
      // We shouldn't get here without a CO defined
      throw new LogicException(_txt('er.co.specify'));
    }
    
    // Specifically whitelist the actions we ignore
    if(!$this->action != 'index'
       && $this->action != 'add'
       && !($this->modelClass == 'CoInvite'
            && ($this->action == 'authconfirm' || $this->action == 'confirm' || $this->action == 'decline'))) {
      // Only act if a record ID parameter was passed
      if(!empty($this->request->params['pass'][0])) {
        $modelName = $this->modelClass;
        // Make sure the model has been loaded
        $this->loadModel($modelName);
        
        try {
          $recordCoId = $this->$modelName->findCoForRecord($this->request->params['pass'][0]);
        }
        catch(InvalidArgumentException $e) {
          throw new InvalidArgumentException($e->getMessage());
        }
        
        // $recordCoId could be null if we're looking up an MVPA which is attached
        // to an Org Identity. If so, that check passes.
        
        if($recordCoId && ($recordCoId != $this->cur_co['Co']['id'])) {
          throw new InvalidArgumentException(_txt('er.co.mismatch',
                                                  array($modelName,
                                                        $this->request->params['pass'][0])));
        }
      }
    }
    
    return true;
  }
}
