<?php
/**
 * COmanage Registry API Component
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
 * @since         COmanage Registry v0.9.3
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */
 
class ApiComponent extends Component {
  // Invoking controller
  protected $controller = null;
  // CakeRequest object
  protected $request = null;
  // CakeResponse object
  protected $response = null;
  // Request document, as parsed from JSON or XML
  protected $reqData = null;
  // Request document, translated for Cake format
  protected $reqConvData = null;
  // Model associated with Controller for request
  protected $reqModel = null;
  protected $reqModelName = null;
  // Plural inflected name of model
  protected $reqModelNamePl = null;
  // Invalid fields from request body
  protected $invalidFields = null;
  
  /**
   * Verify that a document POSTed via the REST API exists, and matches the
   * invoking controller.
   * 
   * @since  COmanage Registry v0.9.3
   * @return True on success
   * @throws InvalidArgumentException
   */
  
  public function checkRestPost() {
    if(!$this->reqData) {
      $this->parseRestRequestDocument();
    }
    
    if(!$this->reqData) {
      throw new InvalidArgumentException("Bad Request", 400);
    }
    
    // Check version number against the model. Note we have to check both 'Version'
    // (JSON) and '@Version' (XML).
    
    if((!isset($this->reqData['Version']) && !isset($this->reqData['@Version']))
       ||
       (isset($this->reqData['Version']) && $this->reqData['Version'] != $this->reqModel->version)
       ||
       (isset($this->reqData['@Version']) && $this->reqData['@Version'] != $this->reqModel->version)) {
      $this->invalidFields['Version'][] = "Unknown version";
      throw new InvalidArgumentException("Invalid Fields", 400);
    }
    
    // Use the model to validate the provided fields
    
    if(!empty($this->reqModel->validate['type']['content']['rule'][0])
       && $this->reqModel->validate['type']['content']['rule'][0] == 'validateExtendedType') {
      // If the model supports extended types, we need to determine the CO ID,
      // which we have to calculate from the requested person date.
      
      $coId = null;
      
      if(!empty($this->reqConvData['co_person_id'])) {
        $coId = $this->reqModel->CoPerson->field('co_id',
                                                 array('CoPerson.id' => $this->reqConvData['co_person_id']));
      } elseif(!empty($this->reqConvData['co_person_role_id'])) {
        $coPersonId = $this->reqModel->CoPersonRole->field('co_person_id',
                                                           array('CoPersonRole.id' => $this->reqConvData['co_person_role_id']));

        if($coPersonId) {
          $coId = $this->reqModel->CoPersonRole->CoPerson->field('co_id',
                                                                 array('CoPerson.id' => $coPersonId));
        }
      }
      
      if($coId) {
        $vrule = $this->reqModel->validate['type']['content']['rule'];
        $vrule[1]['coid'] = $coId;
        
        $this->reqModel->validator()->getField('type')->getRule('content')->rule = $vrule;
      }
    }
 
    $this->reqModel->set($this->reqConvData);
    
    if(!$this->reqModel->validates()) {
      $this->invalidFields = $this->reqModel->validationErrors;
      throw new InvalidArgumentException("Invalid Fields", 400);
    }
    
    return true;
  }
  
  /**
   * Convert the contents of a request from a REST transaction to Cake format.
   * - preconditions: $this->reqData holds original request data
   * - postconditions: $this->reqConvData holds converted data
   *
   * @since  COmanage Registry v0.9.3
   */
  
  protected function convertRestPost() {
    if(!$this->reqData || $this->reqConvData) { 
      throw new RuntimeException('Unexpected state (convertRestPost)');
    }
    
    // Walk through the array of attributes, converting as we go
    
    foreach(array_keys($this->reqData) as $attr) {
      $dbattr = Inflector::underscore($attr);
      
      if($attr == 'Version'
         || $attr == '@Version') {
        // Don't copy version
        continue;
      } elseif($attr == '@Id') {
        // @Id is id, but via XML
        $this->reqConvData['id'] = $this->reqData[$attr];
      } elseif($attr == 'Person'
               && !empty($this->reqData[$attr]['Id'])
               && !empty($this->reqData[$attr]['Type'])) {
        // Flatten back to the appropriate key
        
        switch($this->reqData[$attr]['Type']) {
          case 'CO':
            $this->reqConvData['co_person_id'] = $this->reqData[$attr]['Id'];
            break;
          case 'CoRole':
            $this->reqConvData['co_person_role_id'] = $this->reqData[$attr]['Id'];
            break;
          case 'Dept':
            $this->reqConvData['co_department_id'] = $this->reqData[$attr]['Id'];
            break;
          case 'Group':
            $this->reqConvData['co_group_id'] = $this->reqData[$attr]['Id'];
            break;
          case 'Org':
            $this->reqConvData['org_identity_id'] = $this->reqData[$attr]['Id'];
            break;
          case 'Organization':
            $this->reqConvData['organization_id'] = $this->reqData[$attr]['Id'];
            break;
        }
      } elseif(isset($this->reqModel->cm_enum_types[$dbattr])) {
        // Convert the wire attribute back to the appropriate 
        $enumClass = $this->reqModel->cm_enum_types[$dbattr];
        
        if(isset($enumClass::$from_api[ $this->reqData[$attr] ])) {
          $this->reqConvData[$dbattr] = $enumClass::$from_api[ $this->reqData[$attr] ];
        }
        // else invalid value, but we'll skip that for now
      } else {
        // Inflect the attribute. We don't do anything special with extended attributes
        // here (other than pass them along) since we probably don't know what CO
        // we're operating under yet. (We may need to parse the inbound document to
        // find an identifier to use to associate with a CO.)
        
        $this->reqConvData[$dbattr] = $this->reqData[$attr];
      }
    }
  }

  /**
   * Convert a result to be suitable for REST views.
   *
   * @since  COmanage Registry v0.9.3
   * @param  Array Result set, of the format returned by (eg) $this->find()
   * @return Array Converted array
   */
  
  public function convertRestResponse($res) {
    $ret = array();
    
    foreach($res as $r) {
      // We may get a bunch of associated data, but we only care about the Model
      // we're currently working with
      $rr = array();
      
      foreach(array_keys($r) as $m) {
        if($m == $this->reqModelName) {
          // Copy all keys, inflecting the key name
          
          foreach(array_keys($r[$this->reqModelName]) as $k) {
            if(isset($this->reqModel->cm_enum_types[$k])
               && !((isset($r[$m]['co_person_id']) || isset($r[$m]['co_person_role_id']))
                    &&
                    isset($this->reqModel->validate[$k]['content']['rule'][0])
                    &&
                    $this->reqModel->validate[$k]['content']['rule'][0] == 'validateExtendedType')) {
              // Convert database format to API format, but not if it's an
              // extended type and attached to a CO Person
              $enumClass = $this->reqModel->cm_enum_types[$k];
              
              if(isset($enumClass::$to_api[ $r[$m][$k] ])) {
                $rr[$m][Inflector::camelize($k)] = $enumClass::$to_api[ $r[$m][$k] ];
              }
              // else invalid value, but we'll skip that for now
            } else {
              // Simply copy the value
              $rr[$m][Inflector::camelize($k)] = $r[$m][$k];
            }
          }
        } elseif(preg_match('/Co[0-9]+PersonExtendedAttribute/', $m)) {
          // Extended Attributes need to be handled specially. Currently, extended
          // attributes are NOT inflected to keep them consistent with their
          // database definitions.
          
          foreach(array_keys($r[$m]) as $attr) {
            // Don't copy metadata
            if(!in_array($attr, array('id', 'co_person_role_id', 'created', 'modified', 'CoPersonRole'))) {
              $rr[$m][$attr] = $r[$m][$attr];
            }
          }
        }
      }
      
      $ret[] = $rr;
    }
    
    return $ret;
  }
  
  /**
   * Obtain the API data parsed from the request body.
   * 
   * @since  COmanage Registry v0.9.3
   * @return Array Request data, in usual Cake format
   */
  
  public function getData() {
    return $this->reqConvData;
  }
  
  /**
   * Obtain invalid fields from request body, based on model validation.
   * 
   * @since  COmanage Registry v0.9.3
   * @return Array Array of invalid fields, if any
   * @throws InvalidArgumentException
   */
  
  public function getInvalidFields() {
    return $this->invalidFields;
  }
  
  /**
   * Callback to perform Component specific initializations at startup.
   * 
   * @since  COmanage Registry v0.9.3
   */
  
  public function initialize(Controller $controller) {
    // Grab the request and response objects for use elsewhere in the Component
    
    $this->controller = $controller;
    $this->request = $controller->request;
    $this->response = $controller->response;
    
    if($controller->name != 'Authenticators') {
      // SAMController already cleans this up in beforeFilter, but apparently that
      // doesn't get called earlier enough for initialize(). This is a big mess
      // that hopefully gets cleaned up in Registry PE.
      if($controller->modelClass == 'Authenticator') {
        $mName = Inflector::singularize($controller->name);
        $pName = Inflector::classify($this->request->plugin);
        $pModel = $pName . "." . $mName;
        
        $this->reqModel = ClassRegistry::init($pModel);
        $this->reqModelName = Inflector::singularize($controller->name);
      } else {
        $mName = $controller->modelClass;
        $this->reqModel = $controller->$mName;
        $this->reqModelName = $controller->modelClass;
      }
      $this->reqModelNamePl = Inflector::pluralize($this->reqModelName);
    }

    // Add a detector so we can call request->is('restful')
    // If we want to check the Accept header we'll need a slightly more complicated detector
    $this->request->addDetector('restful', array('param' => 'ext', 'options' => array('json', 'xml')));
  }
  
  /**
   * Parse an API request document. (The parsed document is available via getData().)
   * 
   * @since  COmanage Registry v0.9.3
   */
  
  public function parseRestRequestDocument() {
    if(!empty($this->reqData)) {
      // No need to reparse
      return;
    }
    
    if(!empty($this->request->data)) {
      // Currently, we expect all request documents to match the model name (ie: StudlySingular).
      
      // The inbound formats are currently lists with one entry. (Multiple entries
      // per request are not currently supported.) The format varies slightly between
      // JSON and XML.
      
      if(isset($this->request->data[$this->reqModelNamePl][$this->reqModelName])) {
        // XML
        $this->reqData = $this->request->data[$this->reqModelNamePl][$this->reqModelName];
      } elseif(isset($this->request->data[$this->reqModelNamePl][0])) {
        // JSON
        $this->reqData = $this->request->data[$this->reqModelNamePl][0];
      }
    } else {
      // In some instances, PHP doesn't set $_POST and so Cake doesn't see the request body.
      // $_POST isn't set when the client sets the content type to application/json.
      // PHP can't handle that by default, and Cake seems not to pick up on it.
      // Here's a workaround, based on CakeRequest::_readInput().
      
      switch($this->request->params['ext']) {
        case 'json':
          $doc = file_get_contents("php://input");
          if(!empty($doc)) {
            $json = json_decode($doc, true);
            
            if(!empty($json[$this->reqModelNamePl][0])) {
              $this->reqData = $json[$this->reqModelNamePl][0];
            } else {
              // Just copy the whole document. We do this for (eg) co_provisioning/provision
              // which uses a different format.
              $this->reqData = $json;
            }
          }
          break;
        case 'xml':
          $doc = file_get_contents("php://input");
          if(!empty($doc)) {
            $xml = Xml::toArray(Xml::build($doc));
            $this->reqData = $xml[$this->reqModelNamePl][$this->reqModelName];
          }
          break;
        default:
          break;
      }
    }
    
    if(!empty($this->reqData)) {
      $this->convertRestPost($this->reqData);
    }
    // else DELETE or other bodyless request
  }
  
  /**
   * Determine the requested COID based on the requested URL, and specifically
   * its query parameters.
   *
   * @since  COmanage Registry v3.3.0
   * @param  $model   Cake Model
   * @param  $request Cake Request
   * @param  $data    Parsed PUT/POST body
   * @return int      CO ID, or null if not found
   */
  
  public function requestedCOID($model, $request, $data) {
    // As of Registry v3.3.0, CO level API users are allowed to assert a CO ID
    // for REST operations that meet the following requirements:
    //
    // (1) The request does not include a specific ID (eg view by CO, not view by ID)
    // (2) The requested model directly belongsTo the parent link
    // (Note Registry v5 implements this as a per-model check instead, but
    // we don't have the infrastructure for that.)
    
    if(empty($request->params['pass'])) {
      // For historical reasons, the query string keys aren't standard
      $permittedKeys = array(
        'GET' => array(
          'clusterid' => 'Cluster',
          'codeptid' => 'CoDepartment',
          'cogroupid' => 'CoGroup',
          'coid' => 'Co',
          'copersonid' => 'CoPerson',
          'copersonroleid' => 'CoPersonRole',
          'couid' => 'Cou',
          'organizationid' => 'Organization',
          'orgidentityid' => 'OrgIdentity'
        ),
        'POST' => array(
          'cluster_id' => 'Cluster',
          'co_department_id' => 'CoDepartment',
          'co_group_id' => 'CoGroup',
          'co_id' => 'Co',
          'co_person_id' => 'CoPerson',
          'co_person_role_id' => 'CoPersonRole',
          'cou_id' => 'Cou',
          'org_identity_id' => 'OrgIdentity',
          'organization_id' => 'Organization'
        )
      );
      
      // PUT and POST are basically the same
      $permittedKeys['PUT'] = $permittedKeys['POST'];
      
      if(!empty($model->permittedApiFilters)) {
        // Merge in the plugin's additional permitted key
        foreach(array('GET', 'POST', 'PUT') as $a) {
          $permittedKeys[$a] = array_merge($permittedKeys[$a], $model->permittedApiFilters);
        }
      }
      
      if(!empty($permittedKeys[$request->method()])) {
        foreach($permittedKeys[$request->method()] as $k => $m) {
          // For plugins, $m is of the form Plugin.Model, but belongsTo[]
          // will be keyed only on Model
          $b = strstr($m, '.');
          
          if($b) {
            $b = ltrim($b, '.');
          } else {
            $b = $m;
          }
          
          if(!empty($model->belongsTo[$b])) {
            if($request->method() == 'GET') {
              if(!empty($request->query[$k])) {
                if($k == 'coid') {
                  return $request->query['coid'];
                } else {
                  // For any other key, we need to map it to a CO
                  $ParentModel = ClassRegistry::init($m);
                  return $ParentModel->findCoForRecord($request->query[$k]);
                }
              }
            } else {
              if(!empty($data[$k])) {
                if($k == 'co_id') {
                  return $data['co_id'];
                } else {
                  // For any other key, we need to map it to a CO
                  $ParentModel = ClassRegistry::init($m);
                  return $ParentModel->findCoForRecord($data[$k]);
                }
              }
            }
          }
        }
        // Since i am here no direct CO relation found. Get the parents and retry
        $mparents = $model->belongsTo ?: array();
        foreach ($mparents as $mname => $mproperties) {
          // Load the model before performing any more logic
          $model = ClassRegistry::init($mname);
          // Get up the tree until we find if we can associate the key with the model
          $coid = $this->requestedCOID($model, $request, $data);
          if(!is_null($coid)) {
            return $coid;
          }
        }
      }
    }

    return null;
  }
  
  /**
   * Prepare a REST result HTTP header.
   * - precondition: HTTP headers must not yet have been sent
   * - postcondition: CakeResponse configured with header
   *
   * @since  COmanage Registry v0.9.3
   * @param  integer HTTP result code
   * @param  string HTTP result comment
   */
  
  public function restResultHeader($status, $txt=null) {
    if(isset($txt)) {
      // We need to update the text associated with $status
      
      $this->response->httpCodes(array($status => $txt));
    }
    
    $this->response->statusCode($status);
  }
}
