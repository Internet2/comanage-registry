<?php
/**
 * Application level Controller
 *
 * Copyright (C) 2010-14 University Corporation for Advanced Internet Development, Inc.
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
 * @copyright     Copyright (C) 2010-14 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.1, CakePHP(tm) v 0.2.9
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
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
                             'Role',
                             'Security',
                             'Session',
                             'Paginator');
  
  // We should probably add helpers here instead of in each Controller. To do so,
  // make sure to define the default Html and Form helpers.
  // public $helpers = array('Form', 'Html', 'Time', etc...);
  
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

  // Tab to flip to for pages with tabs
  public $redirectTab = null;

  /**
   * Determine which plugins of a given type are available. This is a static function suitable for use
   * before AppController is instantiated.
   *
   * @param  String Plugin type, or 'all' for all available plugins
   * @since  COmanage Registry v0.8
   * @return Array Available plugins
   */
  
  public static function availablePlugins() {
    // This function must be statically called by lang.php::_bootstrap_plugin_txt(), which under some
    // circumstances is called before AppController has been instantiated.
    
    return App::objects('plugin');
  }
  
  /**
   * Determine which plugins of a given type are available, and load them if not already loaded.
   * - postcondition: Primary Plugin Models are loaded (if requested)
   *
   * @param  String Plugin type, or 'all' for all available plugins
   * @param  String Format to return in: 'list' for list format (suitable for formhelper selects) or 'simple' for a simple list
   * @since  COmanage Registry v0.8
   * @return Array Available plugins
   */
  
  public function loadAvailablePlugins($pluginType, $format='list') {
    $ret = array();
    
    foreach(App::objects('plugin') as $p) {
      $this->loadModel($p . "." . $p);
      
      if($pluginType == 'all'
         || (isset($this->$p->cmPluginType) && $this->$p->cmPluginType == $pluginType)) {
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
    
    if(!$this->restful) {
      // Before we do anything else, check to see if a CO was provided.
      // (It might impact our authz decisions.) Note that some models (eg: MVPAs)
      // might specify a CO, but might not. As of v0.6, we no longer redirect to
      // cos/select if we don't find a CO but one is required. Instead, we throw
      // an error.
      
      // We can't check for RESTful here since $this->data isn't set yet for post data.
      
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
        
        $this->cur_co = $this->Co->findById($coid);
        
        if(!empty($this->cur_co)) {
          $this->set("cur_co", $this->cur_co);
          
          // XXX This is a hack for CO-368 and should not be relied upon.
          if(isset($this->Identifier)) {
            $this->Identifier->coId = $coid;
          }
          if(isset($this->CoIdentifierAssignment)) {
            $this->CoIdentifierAssignment->coId = $coid;
          }
          if(isset($this->CoPetition->EnrolleeCoPerson->Identifier)) {
            $this->CoPetition->EnrolleeCoPerson->Identifier->coId = $coid;
          }
          if(isset($this->CoInvite->CoPetition->EnrolleeCoPerson->Identifier)) {
            $this->CoInvite->CoPetition->EnrolleeCoPerson->Identifier->coId = $coid;
          }
          
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
            $this->Session->setFlash($e->getMessage(), '', array(), 'error');
            $this->redirect("/");
          }
          
          // See if there are any pending Terms and Conditions. If so, redirect the user.
          // But don't do this if the current request is for T&C. We might also consider
          // skipping for admins. Pending T&C are retrieved by UsersController at login.
          // It would be cleaner to retrieve them here, but more efficient once at login
          // rather than before each request.
          
          if($this->modelClass != 'CoTermsAndConditions'
             // Also skip CoSetting so that an admin can change the mode
             && $this->modelClass != 'CoSetting') {
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
        } else {
          $this->Session->setFlash(_txt('er.co.unk-a', array($coid)), '', array(), 'error');
          $this->redirect("/");
        }
      }
    }
    
    if($this->restful) {
      // Set up basic auth and attempt to login the API user, unless we're already
      // logged in (ie: via a cookie provided via an AJAX initiated REST call)
      
      if(!$this->Session->check('Auth.User.username')) {
        $this->Auth->authenticate = array('Basic');
        
//      debug(AuthComponent::password($_SERVER['PHP_AUTH_PW']));
        
        // XXX It's unclear why, as of Cake 2.3, we need to manually initialize AuthComponent
        $this->Auth->initialize($this);
        
        if(!$this->Auth->login()) {
          $this->restResultHeader(401, "Unauthorized");
          // We force an exit here to prevent any views from rendering, but also
          // to prevent Cake from dumping the default layout
          $this->response->send();
          exit;
        }
      }
      
      // Disable validation of POST data, which may be an XML document
      // (the security component doesn't know how to validate XML documents)
// XXX should re-test this and maybe cut a JIRA
      $this->Security->validatePost = false;
      $this->Security->csrfCheck = false;
    }
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
    if($this->restful != true
       && $this->Session->check('Auth.User.org_identities')) {
      $this->menuAuth();
      $this->menuContent();
      $this->getNavLinks();
      $this->getNotifications();
    }
  }
  
  /**
   * Determine the CO ID based on some attribute of the request.
   *
   * @since  COmanage Registry v0.8.4
   * @return Integer CO ID, or null if not implemented or not applicable.
   * @throws InvalidArgumentException
   */
  
  protected function calculateImpliedCoId() {
    // As a default, we'll see if we can determine the CO in a generic manner.
    // Where this doesn't work, individual Controllers can override this function.
    
    if(!$this->requires_co
       && (!$this->requires_person
           ||
           // MVPA controllers operating on org identities where pool_org_identities
           // is false will not specify/require a CO
           (isset($this->viewVars['pool_org_identities'])
            && $this->viewVars['pool_org_identities']))) {
      // Controllers that don't require a CO generally can't imply one.
      return null;
    }
    
    // Get a pointer to our model
    $req = $this->modelClass;
    $model = $this->$req;
    $modelpl = Inflector::tableize($req);
    
    // XXX This list should really be set on a per-CO basis (eg: link only applies to CoPeople)
    if($this->action == 'add'
       || $this->action == 'assign'
       || $this->action == 'index'
       || $this->action == 'link'
       || $this->action == 'select'
       || $this->action == 'review') {
      // See if what we're adding/selecting/viewing is attached to a person
      $p = $this->parsePersonID();
      
      if(!empty($p['copersonid'])
         && (isset($model->CoPerson) || isset($model->Co))) {
        $CoPerson = (isset($model->CoPerson) ? $model->CoPerson : $model->Co->CoPerson);
        
        $coId = $CoPerson->field('co_id', array('id' => $p['copersonid']));
        
        if($coId) {
          return $coId;
        } else {
          throw new InvalidArgumentException(_txt('er.notfound',
                                                  array(_txt('ct.co_people.1'),
                                                        Sanitize::html($p['copersonid']))));
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
                                                        Sanitize::html($p['copersonroleid']))));
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
                                                        Sanitize::html($p['orgidentityid']))));
        }
      } elseif(!empty($this->request->params['named']['cogroup']) && isset($model->CoGroup)) {
        // Map the group to a CO
        $coId = $model->CoGroup->field('co_id', array('id' => $this->request->params['named']['cogroup']));
        
        if($coId) {
          return $coId;
        } else {
          throw new InvalidArgumentException(_txt('er.notfound',
                                                  array(_txt('ct.co_groups.1'),
                                                        Sanitize::html($this->request->params['named']['cogroup']))));
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
   * Compare two arrays and generate a string describing what changed, suitable for
   * including in a history record.
   *
   * @since  COmanage Registry v0.7
   * @param  Array New data, in typical Cake format
   * @param  Array Old data, in typical Cake format
   * @param  Array Models to examine within new and old data
   * @return String String describing changes
   */
    
  public function changesToString($newdata, $olddata, $models) {
    global $cm_texts, $cm_lang;
    
    // We assume $newdata and $olddate are intended to have the same structure, however
    // we require $models to be specified since different controllers may pull different
    // levels of containable or recursion data, and so we don't know how many associated
    // models will appear in $newdata and/or $olddata.
    
    $changes = array();
    
    foreach($models as $model) {
      if($model == 'ExtendedAttribute') {
        // Handle extended attributes differently, as usual
        
        if(isset($this->cur_co['CoExtendedAttribute'])) {
          // First, calculate the real model name
          $eaModel = "Co" . $this->cur_co['Co']['id'] . "PersonExtendedAttribute";
          
          foreach($this->cur_co['CoExtendedAttribute'] as $extAttr) {
            $oldval = null;
            $newval = null;
            
            // Grab the name of this attribute and lowercase it to match the data model
            $eaName = strtolower($extAttr['name']);
            $eaDisplayName = $extAttr['display_name'];
            
            // Try to find the attribute in the data
            
            if(isset($newdata[$eaModel][$eaName]) && ($newdata[$eaModel][$eaName] != "")) {
              $newval = $newdata[$eaModel][$eaName];
            }
            
            if(isset($olddata[$eaModel][$eaName]) && ($olddata[$eaModel][$eaName] != "")) {
              $oldval = $olddata[$eaModel][$eaName];
            }
            
            if(isset($newval) && !isset($oldval)) {
              $changes[] = $eaDisplayName . ": " . _txt('fd.null') . " > " . $newval;
            } elseif(!isset($newval) && isset($oldval)) {
              $changes[] = $eaDisplayName . ": " . $oldval . " > " . _txt('fd.null');
            } elseif(isset($newval) && isset($oldval) && ($newval != $oldval)) {
              $changes[] = $eaDisplayName . ": " . $oldval . " > " . $newval;
            }
          }
        }
      } else {
        // Generate the union of keys among old and new
        
        $attrs = array();
        
        if(!empty($newdata[$model]) && !empty($olddata[$model])) {
          $attrs = array_unique(array_merge(array_keys($newdata[$model]), array_keys($olddata[$model])));
        } elseif(!empty($newdata[$model])) {
          $attrs = array_keys($newdata[$model]);
        } elseif(!empty($olddata[$model])) {
          $attrs = array_keys($olddata[$model]);
        }
        
        foreach($attrs as $attr) {
          // Skip some "housekeeping" keys. Don't blanket skip all *_id attributes
          // since some foreign keys should be tracked (eg: cou_id, sponsor_co_person_id).
          if($attr == 'id' || $attr == 'created' || $attr == 'modified') {
            continue;
          }
          
          // Skip nested arrays -- for now, we only deal with top level data
          if((isset($newdata[$model][$attr]) && is_array($newdata[$model][$attr]))
              || (isset($olddata[$model][$attr]) && is_array($olddata[$model][$attr]))) {
            continue;
          }
          
          if(preg_match('/.*_id$/', $attr)) {
            // Foreign keys need to be handled specially. Start by figuring out the model.
            
            if(preg_match('/.*_co_person_id$/', $attr)) {
              // This is a foreign key to a CO Person (eg: sponsor_co_person)
              
              // Chop off _co_person_id
              $afield = substr($attr, 0, strlen($attr)-13);
              $amodel = "CoPerson";
            } else {
              // Chop off _id
              $afield = substr($attr, 0, strlen($attr)-3);
              $amodel = Inflector::camelize(rtrim($attr, "_id"));
            }
            
            if(!isset($this->$amodel)) {
              $this->loadModel($amodel);
            }
            
            $ftxt = $afield;
            
            // XXX this isn't really an ideal way to see if a language key exists
            if(!empty($cm_texts[ $cm_lang ]['fd.' . $afield])) {
              $ftxt = $cm_texts[ $cm_lang ]['fd.' . $afield];
            }
            
            // Get the old and new values
            
            $oldval = (isset($olddata[$model][$attr]) && $olddata[$model][$attr] != "") ? $olddata[$model][$attr] : null;
            $newval = (isset($newdata[$model][$attr]) && $newdata[$model][$attr] != "") ? $newdata[$model][$attr] : null;
            
            // Make sure they're actually different (we may get some foreign keys here that aren't)
            
            if($oldval == $newval) {
              continue;
            }
            
            if($amodel == "CoPerson" || $amodel == "OrgIdentity") {
              // Display field is Primary Name. Pull the old and new CO People/Org Identity in
              // one query, though we won't know which one we'll get back first.
              
              $args = array();
              $args['conditions'][$amodel.'.id'] = array($oldval, $newval);
              $args['contain'][] = 'PrimaryName';
              
              $ppl = $this->$amodel->find('all', $args);
              
              if(!empty($ppl)) {
                // Walk through the result set to figure out which one is old and which is new
                
                foreach($ppl as $c) {
                  if(!empty($c[$amodel]['id']) && !empty($c['PrimaryName'])) {
                    if($c[$amodel]['id'] == $oldval) {
                      $oldval = generateCn($c['PrimaryName']) . " (" . $oldval . ")";
                    } elseif($c[$amodel]['id'] == $newval) {
                      $newval = generateCn($c['PrimaryName']) . " (" . $newval . ")";
                    }
                  }
                }
              }
            } else {
              // Lookup a human readable string (usually name or something) and prepend it to the ID
              
              $oldval = $this->$amodel->field($this->$amodel->displayField, array('id' => $oldval)) . " (" . $oldval . ")";
              $newval = $this->$amodel->field($this->$amodel->displayField, array('id' => $newval)) . " (" . $newval . ")";
            }
          } else {
            // Simple field in the model
            
            $oldval = (isset($olddata[$model][$attr]) && $olddata[$model][$attr] != "") ? $olddata[$model][$attr] : null;
            $newval = (isset($newdata[$model][$attr]) && $newdata[$model][$attr] != "") ? $newdata[$model][$attr] : null;
            
            // See if we're working with a type, and if so use the localized string instead
            // (if we can find it)
            
            if(!isset($this->$model)) {
              $this->loadModel($model);
            }
            
            if(isset($this->$model) && isset($this->$model->cm_enum_txt[$attr])) {
              $oldval = _txt($this->$model->cm_enum_txt[$attr], null, $oldval) . " (" . $oldval . ")";
              $newval = _txt($this->$model->cm_enum_txt[$attr], null, $newval) . " (" . $newval . ")";
            }
            
            // Find the localization of the field
            
            $ftxt = "(?)";
            
            if(($model == 'Name' || $model == 'PrimaryName') && $attr != 'type') {
              // Treat name specially
              $ftxt = _txt('fd.name.'.$attr);
            } else {
              // Inflect the model name and see if fd.model.attr exists
              
              $imodel = Inflector::underscore($model);
              
              // XXX this isn't really an ideal way to see if a language key exists
              if(!empty($cm_texts[ $cm_lang ]['fd.' . $imodel . '.' . $attr])) {
                $ftxt = _txt('fd.' . $imodel . '.' . $attr);
              } else {
                // Otherwise see if the attribute by itself exists
                $ftxt = _txt('fd.' . $attr);
              }
            }
          }
          
          // Finally, render the change string based on the attributes found above.
          // Notate going to or from NULL only if $newdata or $olddata (as appropriate)
          // was populated, so as to avoid noise when a related object is added or
          // deleted.
          
          if(isset($newval) && !isset($oldval)) {
            $changes[] = $ftxt . ": " . (isset($olddata) ? _txt('fd.null') . " > " : "") . $newval;
          } elseif(!isset($newval) && isset($oldval)) {
            $changes[] = $ftxt . ": " . $oldval . (isset($newdata) ? " > " . _txt('fd.null') : "");
          } elseif(isset($newval) && isset($oldval) && ($newval != $oldval)) {
            $changes[] = $ftxt . ": " . $oldval . " > " . $newval;
          }
        }
      }
    }
    
    return implode(';', $changes);
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
        
        // Try to find a CO, even if not required (some models may use it even if not required).
        // Note beforeFilter() may already have found a CO.
        
        if(!isset($this->cur_co)) {
          $coid = -1;
          
          if(isset($reqdata['CoId']))
            $coid = $reqdata['CoId'];
          
          if($coid == -1) {
            // The CO might be implied by another attribute
            
            if(isset($reqdata['Person']['Type']) && $reqdata['Person']['Type'] == 'CO') {
              $this->loadModel('CoPerson');
              $cop = $this->CoPerson->findById($reqdata['Person']['Id']);
              
              // We've already pulled the CO data, so just set it rather than
              // re-retrieving it below
              if(isset($cop['Co'])) {
                $this->cur_co['Co'] = $cop['Co'];
                $coid = $this->cur_co['Co']['id'];
              }
            }
          }
          
          if(!isset($this->cur_co) && $coid != -1) {
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
          
          if($this->requires_co && !isset($this->cur_co)) {
            // If a CO is required and we didn't find one, bail
            
            $this->restResultHeader(403, "CO Does Not Exist");
            return(false);
          }
          
          if(($this->name == 'Identifiers') && isset($this->cur_co)) {
            // XXX This is a hack for CO-368 and should not be relied upon.
            $this->loadModel('Identifier');
            $this->Identifier->coId = $coid;
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
          
          if($k == 'PrimaryName'
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
            if(isset($model->validate[$k]['content']['rule']) && $model->validate[$k]['content']['rule'][0] == 'boolean')
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
      if(isset($model->validate[$k]['content']['rule'])
         && $model->validate[$k]['content']['rule'][0] == 'boolean') {
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
        $this->set('vv_co_person_id', $copersonid);
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
    $p['menu']['cogroups'] = $roles['user'];
    
    // Manage org identity data?
    $p['menu']['orgidentities'] = $roles['admin'] || $roles['subadmin'];
    
    // Manage any CO (or COU) population?
    // XXX This permission is somewhat confusingly named (implies cmp admin managing COs)
    // as is 'admin' below (which really implies cmadmin)
    $p['menu']['cos'] = $roles['admin'] || $roles['subadmin'];
    
    // Manage any CO configuration?
    $p['menu']['coconfig'] = $roles['admin'];
    
    // Select from available enrollment flows?
    $p['menu']['createpetition'] = $roles['user'];
    
    // Review / approve petitions?
    // XXX this isn't exactly the right check, but then neither are most of the others (CO-731)
    $p['menu']['petitions'] = $roles['admin']
    // XXX A side effect of this current logic is that the link only appears when the person is viewing
    // another link with the CO specified in it (otherwise copersonid isn't set)
                              || ($roles['copersonid'] && $this->Role->isApprover($roles['copersonid']));
    
    // Manage CO extended attributes?
    $p['menu']['extattrs'] = $roles['admin'];
    
    // Manage CO extended typees?
    $p['menu']['exttypes'] = $roles['admin'];
    
    // Manage CO ID Assignment?
    $p['menu']['idassign'] = $roles['admin'];
    
    // Manage COU definitions?
    $p['menu']['cous'] = $roles['admin'];

    // Manage CO enrollment flow definitions?
    $p['menu']['coef'] = $roles['admin'];
    
    // Manage CO Localizations
    $p['menu']['colocalizations'] = $roles['admin'];
  
    // Manage CO Links?
    $p['menu']['conavigationlinks'] = $roles['admin'];

    // Manage CO provisioning targets?
    $p['menu']['coprovtargets'] = $roles['admin'];
    
    // Manage CO self service permissions?
    $p['menu']['coselfsvcperm'] = $roles['admin'];
    
    // Manage CO settings?
    $p['menu']['cosettings'] = $roles['admin'];
    
    // Manage CO terms and conditions?
    $p['menu']['cotandc'] = $roles['admin'];
  
    // Admin COmanage?
    $p['menu']['admin'] = $roles['cmadmin'];
    
    // Manage NSF Demographics?
    $p['menu']['co_nsf_demographics'] = $roles['cmadmin'];
    
    // View/Edit own Demographics profile?
    $p['menu']['nsfdemoprofile'] = $roles['user'];

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
    
    if($this->viewVars['permissions']['menu']['admin']) {
      // Show all active COs for admins
      $this->loadModel('Co');
      $params = array('conditions' => array('Co.status' => 'A'),
                      'fields'     => array('Co.id', 'Co.name'),
                      'recursive'  => false
                     );
      $codata = $this->Co->find('all', $params);
      
      foreach($codata as $data) {
        // Don't clobber the COs we've already loaded
        if(!isset($menu['cos'][ $data['Co']['name'] ])) {
          $menu['cos'][ $data['Co']['name'] ] = array(
            'co_id'   => $data['Co']['id'],
            'co_name' => $data['Co']['name'] . " (" . _txt('er.co.notmember') . ")"
          );
        }
      }
    }
    
    // Determine what menu contents plugins want available
    $plugins = $this->loadAvailablePlugins('all', 'simple');
    
    foreach($plugins as $plugin) {
      if(isset($this->$plugin->cmPluginMenus)) {
        $menu['plugins'][$plugin] = $this->$plugin->cmPluginMenus;
      }
    }
    
    $this->set('menuContent', $menu);
  }
  
  /**
   * For Models that accept a CO ID, find the provided CO ID.
   * - precondition: A coid must be provided in $this->request (params or data)
   *
   * @since  COmanage Registry v0.6
   * @return Integer The CO ID if found, or -1 if not
   */
  
  function parseCOID() {
    // Get a pointer to our model
    $req = $this->modelClass;
    $model = $this->$req;
    
    // First try to look up the CO ID based on the request. 
    $coid = $this->calculateImpliedCoId();
    
    if(!$coid) {
      $coid = -1;
      
      // Only certain actions are permitted to explicitly provide a CO ID
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
  
  /**
   * Perform a sanity check on on a standard item identifier to verify it is part
   * of the current CO.
   * - precondition: cur_co is set
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
