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
    
    // Pull the CO Person and associated names. Note we specifically look for a
    // name of type _official_, and if we don't find one we fail. (This could
    // eventually become configurable.)
    
    $CoPerson = ClassRegistry::init('CoPerson');
    
    $args = array();
    $args['conditions']['CoPerson.id'] = $recordId;
    $args['contain'] = array('Name');
    
    $rec = $CoPerson->find('first', $args);
    
    if(empty($rec)) {
      throw new InvalidArgumentException(_txt('er.notfound', array(_txt('ct.co_people.1'), $recordId)));
    }
    
    $name = Hash::extract($rec['Name'], '{n}[type=official]');
    
    if(empty($name)) {
      throw new InvalidArgumentException(_txt('er.namespaceassigner.name'));
    }
    
    // Pull the settings to see what the server connection information is
    $NamespaceAssignerSetting = ClassRegistry::init('NamespaceAssigner.NamespaceAssignerSetting');
    
    $args = array();
    $args['conditions']['NamespaceAssignerSetting.co_id'] = $coId;
    $args['contain'] = array('Server' => array('HttpServer'));
    
    $cfg = $NamespaceAssignerSetting->find('first', $args);
    
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
}
