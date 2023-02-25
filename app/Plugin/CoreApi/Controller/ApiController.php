<?php
/**
 * COmanage Registry Core API API Controller
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
 * @package       registry-plugin
 * @since         COmanage Registry v4.0.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::uses("StandardController", "Controller");

// This is based heavily on ApiSource::ApiController... maybe merge them,
// possibly with the hypothetical API plugin type

class ApiController extends Controller {
  // Class name, used by Cake
  public $name = "Api";

  public $modelName = null;

  // Since we don't extend AppController we need to enumerate the components
  // we want to use.
  public $components = array('Api',
                             'Auth',
                             'RequestHandler', // For REST
                             'Paginator');
  
  public $uses = array(
    "Co",
    "CoJob",
    "CoreApi.CoreApi",
    "OrgIdentitySource"
  );

  /**
   * Paginator default settings
   *
   * @since  COmanage Registry v4.1.0
   */

  public $paginate = array(
    'limit' => 100,
    'maxLimit' => 1000,
    'order' => array(
      'CoPerson.id' => 'asc'
    )
  );

  // The Core API record for the current request
  protected $cur_api = null;
  
  /**
   * Callback before other controller methods are invoked or views are rendered.
   *
   * @since  COmanage Registry v4.0.0
   */   
    
  public function beforeFilter() {
    // We need to do this manually since we don't call AppController::beforeFilter
    _bootstrap_plugin_txt();
    
    // We want json views in responses
    $this->RequestHandler->renderAs($this, 'json');
    $this->layout = 'CoreApi.json';

    $this->modelName = Inflector::singularize($this->name);

    // Since we're not calling parent::beforeFilter(), we have to validate the CO
    
    // This should be provided by routes.php
    if(empty($this->request->params['coid'])) {
      $this->set('results', array('error' => _txt('er.co.specify')));
      $this->Api->restResultHeader(404);
      return;
    }
    
    $args = array();
    $args['conditions']['Co.id'] = $this->request->params['coid'];
    $args['conditions']['Co.status'] = SuspendableStatusEnum::Active;
    $args['contain'] = false;
    
    $co = $this->Co->find('first', $args);
    
    if(empty($co)) {
      $this->set('results', array('error' => _txt('er.co.unk-a', array($this->request->params['coid']))));
      $this->Api->restResultHeader(404);
      return;
    }
    
    // Check authentication
    
    // While similar to other API auth checks, this one works a bit differently.
    // We start by authenticating the API User credentials. Once we do that, we
    // need to authorize the API User by looking for a CoreApi configuration
    // that matches this request. We'll handle both authn and authz here, though
    // plausibly authz could be handled by isAuthorized instead.
    
    $authok = false;
    
    if(!empty($_SERVER['PHP_AUTH_USER'])
       && !empty($_SERVER['PHP_AUTH_PW'])) {
      $this->Auth->authenticate = array(
        'Basic' => array(
          'userModel' => 'ApiUser',
          'scope' => array(
            // Only look at active users
            'ApiUser.status' => SuspendableStatusEnum::Active,
            // The CO should be implied by the username, but since we have the
            // CO ID anyway we may as well scope it
            'ApiUser.co_id' => $this->request->params['coid'],
            // That don't have validity dates or where the dates are in effect
            'AND' => array(
              0 => array(
                'OR' => array(
                  'ApiUser.valid_from IS NULL',
                  'ApiUser.valid_from < ' => date('Y-m-d H:i:s', time())
                )
              ),
              1 => array(
                'OR' => array(
                  'ApiUser.valid_through IS NULL',
                  'ApiUser.valid_through > ' => date('Y-m-d H:i:s', time())
                )
              )
            )
            // We also want to check REMOTE_IP, but there's not a good SQL way
            // to do a regular expression comparison, so we'll do that separately.
            // XXX When migrating to PE, we should do all these checks separately
            // so we can log what failed more clearly.
          ),
          'contain' => false
        )
      );
      
      // XXX It's unclear why, as of Cake 2.3, we need to manually initialize AuthComponent
      $this->Auth->initialize($this);
      
      if($this->Auth->login()) {
        // Check the ApiUser against the runtime authentication attributes
        $apiuser = $this->Auth->user();
        
        if(!empty($apiuser['username'])
           // Maybe check remote IP, if configured
           && (empty($apiuser['remote_ip'])
               || preg_match($apiuser['remote_ip'], $_SERVER['REMOTE_ADDR']))) {
          // Authentication complete, check authorization
          
          // Try to find a matching CoreApi configuration
          $args = array();
          $args['conditions']['CoreApi.api_user_id'] = $apiuser['id'];
          // This should generally be redundant since api_users are per-CO
          $args['conditions']['CoreApi.co_id'] = $apiuser['co_id'];
          $args['conditions']['CoreApi.status'] = SuspendableStatusEnum::Active;
          $args['contain'] = false;
          
          // Which API was requested?
          $targetedController = str_replace('CoreApi', '', $this->request->params['controller']);
          $targetedSingular = Inflector::singularize($targetedController);
          switch($this->request->params['action']) {
            case 'read':    // Read single record
            case 'index':   // Read all records
              $args['conditions']['CoreApi.api'] = array(
                constant("CoreApiEnum::{$targetedSingular}Read"),
                // ApiUsers with Write permission can also read
                constant("CoreApiEnum::{$targetedSingular}Write"),
              );
              break;
            case 'create':
            case 'update':
            case 'delete':
              $args['conditions']['CoreApi.api'] = array(constant("CoreApiEnum::{$targetedSingular}Read"));
              break;
            case 'resolveMatch':
              $args['conditions']['CoreApi.api'] = CoreApiEnum::MatchCallback;
              break;
            default:
              throw new RuntimeException('NOT IMPLEMENTED');
          }
          
          // If there is more than one configuration for a given API User, it
          // is non-deterministic which one we pick.
          $coreapi = $this->CoreApi->find('first', $args);
          
          if(!empty($coreapi)) {
            // Success! Store the info we pulled.
            $this->cur_api = $coreapi;
            
            $authok = true;
          }
        }
      }
    }
    
    if(!$authok) {
      $this->Api->restResultHeader(401);
      $this->response->send();
      exit;
    }

    if($this->request->params['action'] == 'index') {
      // Filter/Validate Query parameters
      $this->params->query = $this->CoreApi->validateQueryParams($this->params->query);
      // Parse query parameters
      $this->params->query = $this->CoreApi->parseQueryParams($this->params->query);
    }
  }

  /**
   * Handle a Core API CO record create
   *
   * @since  COmanage Registry v4.1.0
   */

  public function create() {
    $modelApiName = $this->modelName;
    try {
      $ret = $this->$modelApiName->createV1($this->cur_api['CoreApi']['co_id'],
                                            $this->request->data,
                                            $this->cur_api['CoreApi']['api_user_id']);

      $this->set('results', array_values($ret));
      $this->Api->restResultHeader(201);
    }
    catch(InvalidArgumentException $e) {
      $this->set('results', array('error' => $e->getMessage()));
      $this->Api->restResultHeader(404);
    }
    catch(Exception $e) {
      $this->set('results', array('error' => $e->getMessage()));
      if(isset(_txt('en.http.status.codes')[$e->getCode()])) {
        $this->Api->restResultHeader($e->getCode());
      } else {
        $this->Api->restResultHeader(500);
      }
    }
  }

  /**
   * Handle a Core API CO Record delete request
   * /api/co/:coid/core/v1/<model>/:identifier e.g.
   * /api/co/:coid/core/v1/organizations/:identifier
   * /api/co/:coid/core/v1/departments/:identifier
   *
   * @since  COmanage Registry v4.2.0
   */

  public function delete() {
    $modelApiName = $this->modelName;
    $modelMapperName = $this->$modelApiName->mapper;

    try {
      if(empty($this->request->params['identifier'])
         && empty($this->request->query["identifier"])) {
        // We shouldn't really get here since routes.php shouldn't allow it
        throw new InvalidArgumentException(_txt('er.notprov'));
      }

      $req_identifier = !empty($this->request->params["identifier"])
                        ? $this->request->params["identifier"]
                        : (!empty($this->request->query["identifier"])
                           ? $this->request->query["identifier"]
                           : null);



      $ret = $this->$modelApiName->deleteV1($this->cur_api['CoreApi']['co_id'],
                                            $req_identifier,
                                            $this->cur_api['CoreApi']['identifier_type']);

      if(!empty($ret) && !is_bool($ret)) {
        $this->set('results', $ret);
      }
      $this->Api->restResultHeader(200);
    }
    catch(InvalidArgumentException $e) {
      $this->set('results', array('error' => $e->getMessage()));
      $this->Api->restResultHeader(404);
    }
    catch(Exception $e) {
      $this->set('results', array('error' => $e->getMessage()));
      if(isset(_txt('en.http.status.codes')[$e->getCode()])) {
        $this->Api->restResultHeader($e->getCode());
      } else {
        $this->Api->restResultHeader(500);
      }
    }
  }

  /**
   * Handle a Core API CO People Read API request.
   * /api/co/:coid/core/v1/people
   *
   * @since  COmanage Registry v4.1.0
   */

  public function index() {
    $modelApiName = $this->modelName;
    $modelMapperName = $this->$modelApiName->mapper;
    try {
      $query_filters = array();
      // Load the default ordering and pagination settings
      $this->Paginator->settings = $this->paginate;
      $this->Paginator->settings['conditions']['Identifier.type'] = $this->cur_api['CoreApi']['identifier_type'];
      $this->Paginator->settings['conditions']['Identifier.deleted'] = false;
      $this->Paginator->settings['conditions']['Identifier.identifier_id'] = null;
      if(!empty($this->request->query['identifier'])) {
        $this->Paginator->settings['conditions']['Identifier.identifier'] = $this->request->query['identifier'];
      }
      $this->Paginator->settings['conditions']['Identifier.status'] = SuspendableStatusEnum::Active;
      $this->Paginator->settings['conditions']["{$modelMapperName}.co_id"] = $this->cur_api['CoreApi']['co_id'];
      if(!empty($this->request->query["{$modelMapperName}.status"])) {
        $query_filters[] = 'status';
        $this->Paginator->settings['conditions']["{$modelMapperName}.status"] = $this->request->query["{$modelMapperName}.status"];
      }
      $modelMapper_underscore = Inflector::underscore($modelMapperName);
      $this->Paginator->settings['joins'][0]['table'] = 'identifiers';
      $this->Paginator->settings['joins'][0]['alias'] = 'Identifier';
      $this->Paginator->settings['joins'][0]['type'] = 'INNER';
      $this->Paginator->settings['joins'][0]['conditions'][0] = "Identifier.{$modelMapper_underscore}_id={$modelMapperName}.id";

      // We need all the relational data for the full mode
      if($this->cur_api['CoreApi']['index_response_type'] == ResponseTypeEnum::Full) {
        // While we're here pull the data we need
        $this->Paginator->settings['contain'] = $this->$modelApiName->index_contains;
      } elseif($this->modelName == "Person"
               && $this->cur_api['CoreApi']['index_response_type'] == ResponseTypeEnum::IdentifierList) {
        $this->Paginator->settings['contain'] = array(
          'Identifier' => array(
            'conditions' => array(
              'type ' => $this->cur_api['CoreApi']['identifier_type'],
              'deleted' => false,
              'identifier_id' => null
            )));
      }

      // Query offset
      if(!empty($this->request->query['limit'])) {
        $this->Paginator->settings['limit'] = $this->request->query['limit'];
      }
      // Order Direction
      if(!empty($this->request->query['direction'])) {
        $this->Paginator->settings['order']["{$modelMapperName}.id"] = $this->request->query['direction'];
      }
      // Page
      if(!empty($this->request->query['page'])) {
        $this->Paginator->settings['page'] = $this->request->query['page'];
      }

      $modelObj = $this->Paginator->paginate($modelMapperName);

      if(empty($modelObj)) {
        $modelObj = ClassRegistry::init($modelMapperName);
        // The model has a status enum type hint. I use the existing type hint and append the postfix
        $attr_human_readable = array();
        foreach ($query_filters as $filter) {
          $attr_human_readable[] = _txt($modelObj->cm_enum_txt[$filter], null, $this->request->query["{$modelMapperName}." . $filter]);
        }
        throw new InvalidArgumentException(
          _txt('er.notfound', array($modelApiName, implode(',', $attr_human_readable)))
        );
      }

      $ret = array();
      if($this->cur_api['CoreApi']['index_response_type'] == ResponseTypeEnum::Full) {
        $ret = $this->$modelApiName->readV1Index($this->cur_api['CoreApi']['co_id'], $modelObj);
      } elseif($this->cur_api['CoreApi']['index_response_type'] == ResponseTypeEnum::IdentifierList) {
        // Would it make sense to return the person id as well?
        $ret = Hash::extract($modelObj, '{n}.Identifier.{n}.identifier');
      }

      // Set the results
      $this->set('results', $ret);
      $this->Api->restResultHeader(200);
    }
    catch(InvalidArgumentException $e) {
      $this->set('results', array('error' => $e->getMessage()));
      $this->Api->restResultHeader(404);
    }
    catch(Exception $e) {
      $this->set('results', array('error' => $e->getMessage()));
      $this->Api->restResultHeader(500);
    }
  }

  /**
   * Authorization for this Controller, called by Auth component
   * - precondition: Session.Auth holds data used for authz decisions
   * - postcondition: $permissions set with calculated permissions
   *
   * @since  COmanage Registry v4.0.0
   * @return Array Permissions
   */

  function isAuthorized() {
    // Currently we do all meaningful checks in beforeFilter().

    return true;
  }

  /**
   * Handle a Core API CO Read API request.
   *
   * @since  COmanage Registry v4.1.0
   */

  public function read() {
    // We basically just pull the current record and return it.
    // We could inject some metadata (modified time, etc) but currently we don't.

    try {
      if(empty($this->request->params['identifier'])) {
        // We shouldn't really get here since routes.php shouldn't allow it
        throw new InvalidArgumentException(_txt('er.notprov'));
      }

      $apiModelName = $this->modelName;
      $ret = $this->$apiModelName->readV1($this->cur_api['CoreApi']['co_id'],
                                            $this->request->params['identifier'],
                                            $this->cur_api['CoreApi']['identifier_type']);

      $this->set('results', $ret);
      $this->Api->restResultHeader(200);
    }
    catch(InvalidArgumentException $e) {
      $this->set('results', array('error' => $e->getMessage()));
      $this->Api->restResultHeader(404);
    }
    catch(Exception $e) {
      $this->set('results', array('error' => $e->getMessage()));
      $this->Api->restResultHeader(500);
    }
  }

  /**
   * Handle a Core API Write API Update request.
   *
   * @since  COmanage Registry v4.0.0
   */

  public function update() {
    try {
      if(empty($this->request->params['identifier'])
          && empty($this->request->query["identifier"])) {
        // We shouldn't really get here since routes.php shouldn't allow it
        throw new InvalidArgumentException(_txt('er.notprov'));
      }

      $req_identifier = !empty($this->request->params["identifier"])
        ? $this->request->params["identifier"]
        : (!empty($this->request->query["identifier"])
          ? $this->request->query["identifier"]
          : null);

      $apiModelName = $this->modelName;
      $ret = $this->$apiModelName->upsertV1($this->cur_api['CoreApi']['co_id'],
                                            $req_identifier,
                                            $this->cur_api['CoreApi']['identifier_type'],
                                            $this->request->data,
                                            $this->cur_api['CoreApi']['api_user_id']);

      $this->set('results', $ret);
      $this->Api->restResultHeader(200);
    }
    catch(InvalidArgumentException $e) {
      $this->set('results', array('error' => $e->getMessage()));
      $this->Api->restResultHeader(404);
    }
    catch(Exception $e) {
      $this->set('results', array('error' => $e->getMessage()));
      if(isset(_txt('en.http.status.codes')[$e->getCode()])) {
        $this->Api->restResultHeader($e->getCode());
      } else {
        $this->Api->restResultHeader(500);
      }
    }
  }
}
