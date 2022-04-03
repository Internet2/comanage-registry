<?php
/**
 * COmanage Registry HttpClient Extension
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
 * @since         COmanage Registry v3.1.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::uses('HttpSocket', 'Network/Http');

class CoHttpClient extends HttpSocket {
  protected $_baseUrl = null;
  protected $_requestOptions = array();
  
  /**
   * Build a URL using the configured base URL.
   *
   * @since  COmanage Registry v3.1.0
   * @param  String $path Relative path to resource, including leading /
   * @return String URL
   */
  
  public function buildUrl($path) {
    return $this->_baseUrl . $path;
  }
  
  /**
   * Override HttpSocket delete() to apply requested configuration.
   *
   * @since  COmanage Registry v3.2.0
   * @param  String $uri     URI fragment (appended to $_baseURL) to request
   * @param  Array  $data    Array of request body data keys and values.
   * @param  Array  $request An indexed array with indexes such as 'method' or uri
   * @return Mixed           Result of request
   */
  
  public function delete($uri=null, $query=array(), $request=array()) {
    return parent::delete($this->buildurl($uri),
                          $query,
                          Hash::merge($this->_requestOptions, $request));
  }
  
  /**
   * Override HttpSocket get() to apply requested configuration.
   *
   * @since  COmanage Registry v3.2.0
   * @param  String $uri     URI fragment (appended to $_baseURL) to request
   * @param  Array  $data    Array of request body data keys and values.
   * @param  Array  $request An indexed array with indexes such as 'method' or uri
   * @return Mixed           Result of request
   */
  
  public function get($uri=null, $query=array(), $request=array()) {
    return parent::get($this->buildurl($uri),
                       $query,
                       Hash::merge($this->_requestOptions, $request));
  }
  
  /**
   * Override HttpSocket head() to apply requested configuration.
   *
   * @since  COmanage Registry v3.2.0
   * @param  String $uri     URI fragment (appended to $_baseURL) to request
   * @param  Array  $data    Array of request body data keys and values.
   * @param  Array  $request An indexed array with indexes such as 'method' or uri
   * @return Mixed           Result of request
   */
  
  public function head($uri=null, $query=array(), $request=array()) {
    return parent::head($this->buildurl($uri),
                        $query,
                        Hash::merge($this->_requestOptions, $request));
  }
  
  /**
   * Override HttpSocket patch() to apply requested configuration.
   *
   * @since  COmanage Registry v3.2.0
   * @param  String $uri     URI fragment (appended to $_baseURL) to request
   * @param  Array  $data    Array of request body data keys and values.
   * @param  Array  $request An indexed array with indexes such as 'method' or uri
   * @return Mixed           Result of request
   */
  
  public function patch($uri=null, $query=array(), $request=array()) {
    return parent::patch($this->buildurl($uri),
                         $query,
                         Hash::merge($this->_requestOptions, $request));
  }
  
  /**
   * Override HttpSocket post() to apply requested configuration.
   *
   * @since  COmanage Registry v3.2.0
   * @param  String $uri     URI fragment (appended to $_baseURL) to request
   * @param  Array  $data    Array of request body data keys and values.
   * @param  Array  $request An indexed array with indexes such as 'method' or uri
   * @return Mixed           Result of request
   */
  
  public function post($uri=null, $query=array(), $request=array()) {
    return parent::post($this->buildurl($uri),
                        $query,
                        Hash::merge($this->_requestOptions, $request));
  }
  
  /**
   * Override HttpSocket put() to apply requested configuration.
   *
   * @since  COmanage Registry v3.2.0
   * @param  String $uri     URI fragment (appended to $_baseURL) to request
   * @param  Array  $data    Array of request body data keys and values.
   * @param  Array  $request An indexed array with indexes such as 'method' or uri
   * @return Mixed           Result of request
   */
  
  public function put($uri=null, $query=array(), $request=array()) {
    return parent::put($this->buildurl($uri),
                       $query,
                       Hash::merge($this->_requestOptions, $request));
  }
  
  /**
   * Set the base URL used for REST connections.
   *
   * @since  COmanage Registry v3.1.0
   * @param  String $baseUrl Base URL
   */
  
  public function setBaseUrl($baseUrl) {
    // Some REST servers are picky about having two slashes in the path,
    // so make sure there aren't any trailing slashes.
    
    $this->_baseUrl = rtrim($baseUrl, '/');
  }
  
  /**
   * Set the HttpClient configuration based on an HttpServer configuration array.
   *
   * @since  COmanage Registry v4.0.0
   * @param  array $config  Array of HttpServer configuration
   */
  
  public function setConfig($config) {
    if(!empty($config['serverurl'])) {
      $this->setBaseUrl($config['serverurl']);
    }
    
    if(!empty($config['auth_type'])) {
      switch($config['auth_type']) {
        case HttpServerAuthType::Basic:
          $this->configAuth(
            'Basic',
            $config['username'],
            $config['password']
          );
          break;
        case HttpServerAuthType::Bearer:
          $this->setRequestOptions(array(
            'header' => array(
              'Content-Type'  => 'application/json',
              'Authorization' => 'Bearer ' . $config['password']
            )
          ));      
          break;
        case HttpServerAuthType::None:
        default:
          break;
      }
    }
    
    if(isset($config['ssl_verify_host'])) {
      $this->config['ssl_verify_host'];
    }
    
    if(isset($config['ssl_verify_peer'])) {
      $this->config['ssl_verify_peer'];
    }
  }
  
  /**
   * Set common config options for each request.
   *
   * @since  COmanage Registry v3.2.0
   * @param  Array $options Request Options
   */
  
  public function setRequestOptions($options) {
    $this->_requestOptions = $options;
  }
}
