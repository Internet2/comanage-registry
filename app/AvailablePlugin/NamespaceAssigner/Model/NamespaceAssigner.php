<?php
/**
 * COmanage Registry Namespace Assigner Model
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
 * @link          https://www.internet2.edu/comanage COmanage Project
 * @package       registry-plugin
 * @since         COmanage Registry v4.1.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::uses('CoHttpClient', 'Lib');

class NamespaceAssigner extends AppModel {
  // Required by COmanage Plugins
  public $cmPluginType = "identifierassigner";
  
  // Document foreign keys
  public $cmPluginHasMany = array(
    "Co" => array("NamespaceAssignerSetting")
  );
  
  /**
   * Expose menu items.
   * 
   * @since COmanage Registry v4.1.0
   * @return Array with menu location type as key and array of labels, controllers, actions as values.
   */
  
  public function cmPluginMenus() {
    return array(
      "coconfig" => array(_txt('ct.namespace_assigner_settings.pl') =>
        array('icon'       => 'extension',
              'controller' => 'namespace_assigner_settings',
              'action'     => 'index')
      )
    );
  }
  
  /**
   * Assign a new Identifier.
   *
   * @since  COmanage Registry v4.1.0
   * @param  int                              $coId           CO ID for Identifier Assignment
   * @param  IdentifierAssignmentContextEnum  $context        Context in which to assign Identifier
   * @param  int                              $recordId       Record ID of type $context
   * @param  string                           $identifierType Type of identifier to assign
   * @param  string                           $emailType      Type of email address to assign
   * @return string
   * @throws InvalidArgumentException
   */
  
  public function assign($coId, $context, $recordId, $identifierType, $emailType=null) {
    if($context != IdentifierAssignmentContextEnum::CoPerson) {
      throw new InvalidArgumentException('NOT IMPLEMENTED');
    }
    
    // Pull the settings to get our configuration
    $NamespaceAssignerSetting = ClassRegistry::init('NamespaceAssigner.NamespaceAssignerSetting');

    // Map Servers to their Model names and "contain" them to our query
    $listOfServers = array_map(static function($item) {
      return $item . 'Server';
    }, $NamespaceAssignerSetting->cmServerType);

    $args = array();
    $args['conditions']['NamespaceAssignerSetting.co_id'] = $coId;
    $args['contain'] = array('Server' => $listOfServers);
    
    $cfg = $NamespaceAssignerSetting->find('first', $args);

    // Pull the CO Person and associated names
    
    $CoPerson = ClassRegistry::init('CoPerson');
    
    $args = array();
    $args['conditions']['CoPerson.id'] = $recordId;
    $args['contain'] = array('Name');
    
    $rec = $CoPerson->find('first', $args);
    
    if(empty($rec)) {
      throw new InvalidArgumentException(_txt('er.notfound', array(_txt('ct.co_people.1'), $recordId)));
    }
    
    // Look for a name of the requested type
    $name = Hash::extract($rec['Name'], '{n}[type='.$cfg['NamespaceAssignerSetting']['name_type'].']');
    
    if(empty($name)) {
      throw new InvalidArgumentException(_txt('er.namespaceassigner.name'));
    }
    
    $request = array(
      // We use the CO Person ID as the subject request ID
      'subject' => $recordId,
      'attributes' => array(
        'names' => array(
          array(
            'type' => $name[0]['type'],
            'given' => $name[0]['given'],
            'middle' => $name[0]['middle'],
            'family' => $name[0]['family'] 
          )
        )
      )
    );

    // XXX if the scenarios become more complex that this move to
    //     a dependency injection implementation
    if(!empty($cfg['Server']['HttpServer'])) {
      $token = $this->handleHttpServer($cfg, $request, $identifierType);
    } else if($cfg['Server']['HttpServer']) {
      $token = $this->handleOauth2Server($cfg, $request, $identifierType);
    }


    return $token ?? null;
  }

  /**
   * Handle HttpServer connection
   *
   * @since  COmanage Registry v4.3.0
   * @param  array  $cfg                Plugin configuration
   * @param  array  $request            Context in which to assign Identifier
   * @param  string $identifierType       Record ID of type $context
   * @return string
   */

  protected function handleHttpServer($cfg, $request, $identifierType) {
    $Http = new CoHttpClient();

    $Http->setConfig($cfg['Server']['HttpServer']);

    $url = '/v1/allocations/' . urlencode($identifierType);

    $response = $Http->post($url, json_encode($request));

    if($response->code != 200) {
      throw new RuntimeException($response->reasonPhrase);
    }

    $j = json_decode($response->body);

    if(empty($j->token)) {
      throw new RuntimeException(_txt('er.namespaceassigner.token'));
    }

    return $j->token;
  }

  /**
   * Handle HttpServer connection
   *
   * @since  COmanage Registry v4.3.0
   * @param  array  $cfg                Plugin configuration
   * @param  array  $request            Context in which to assign Identifier
   * @param  string $identifierType       Record ID of type $context
   * @return string
   */

  protected function handleOauth2Server($cfg, $request, $identifierType) {
    $Http = new CoHttpClient();

    $Http->setConfig($cfg['Server']['Oauth2Server']);

    $url = '/v1/allocations/' . urlencode($identifierType);

    // Apend the access token to the request
    $request['access_token'] = $cfg['Server']['Oauth2Server']['access_token'];

    $response = $Http->post($url, json_encode($request));

    if($response->code != 200) {
      throw new RuntimeException($response->reasonPhrase);
    }

    $j = json_decode($response->body);

    if(empty($j->token)) {
      throw new RuntimeException(_txt('er.namespaceassigner.token'));
    }

    return $j->token;
  }
}
