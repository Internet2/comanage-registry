<?php
/**
 * COmanage Registry CO API Provisioner Target Model
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

App::uses("CoProvisionerPluginTarget", "Model");

class CoApiProvisionerTarget extends CoProvisionerPluginTarget {
  // Define class name for cake
  public $name = "CoApiProvisionerTarget";
  
  // Add behaviors
  public $actsAs = array('Containable');
  
  // Association rules from this model to other models
  public $belongsTo = array(
    "CoProvisioningTarget",
    "Server"
  );
  
  // Default display field for cake generated views
  public $displayField = "server_id";
  
  // Request HTTP servers
  public $cmServerType = ServerEnum::HttpServer;
  
  protected $Http = null;
  
  // Validation rules for table elements
  public $validate = array(
    'co_provisioning_target_id' => array(
      'rule' => 'numeric',
      'required' => true,
      'allowEmpty' => false
    ),
    'server_id' => array(
      'content' => array(
        'rule' => 'numeric',
        'required' => true,
        'allowEmpty' => false,
        'unfreeze' => 'CO'
      )
    ),
    'identifier_type' => array(
      'content' => array(
        'rule' => array('validateExtendedType',
                        array('attribute' => 'Identifier.type',
                              'default' => array(IdentifierEnum::ePPN,
                                                 IdentifierEnum::ePTID,
                                                 IdentifierEnum::Mail,
                                                 IdentifierEnum::OIDCsub,
                                                 IdentifierEnum::OpenID,
                                                 IdentifierEnum::SamlPairwise,
                                                 IdentifierEnum::SamlSubject,
                                                 IdentifierEnum::UID))),
        'required' => true,
        'allowEmpty' => false
      )
    ),
    'include_attributes' => array(
      'rule' => 'boolean',
      'required' => false,
      'allowEmpty' => true
    )
  );
  
  /* We could sanity check the server configuration here, but we'd need to
     pull the HttpServer object to get it.
    
  public function beforeSave($options = array()) {
    
    return true;
  }*/
  
  /**
   * Provision for the specified CO Person.
   *
   * @since  COmanage Registry v4.0.0
   * @param  Array CO Provisioning Target data
   * @param  ProvisioningActionEnum Registry transaction type triggering provisioning
   * @param  Array Provisioning data, populated with ['CoPerson'] or ['CoGroup']
   * @return Boolean True on success
   * @throws RuntimeException
   * @todo   This is similar to CoCrowdProvisionerTarget
   */
  
  public function provision($coProvisioningTargetData, $op, $provisioningData) {
    // First determine what to do

    // Note we currently don't do anything for CoGroup actions since the
    // CIFER/TIER/TAP attributes for groups haven't yet been finalized.

    $deleteGroup = false;
    $syncGroup = false;
    $deletePerson = false;
    $syncPerson = false;

    switch($op) {
      case ProvisioningActionEnum::CoGroupAdded:
      case ProvisioningActionEnum::CoGroupUpdated:
      case ProvisioningActionEnum::CoGroupReprovisionRequested:
        // We don't currently support groups
        //$syncGroup = true;
        break;
      case ProvisioningActionEnum::CoGroupDeleted:
        $deleteGroup = true;
        break;
      case ProvisioningActionEnum::CoPersonAdded:
      case ProvisioningActionEnum::CoPersonEnteredGracePeriod:
      case ProvisioningActionEnum::CoPersonExpired:
      case ProvisioningActionEnum::CoPersonPetitionProvisioned:
      case ProvisioningActionEnum::CoPersonPipelineProvisioned:
      case ProvisioningActionEnum::CoPersonReprovisionRequested:
      case ProvisioningActionEnum::CoPersonUnexpired:
      case ProvisioningActionEnum::CoPersonUpdated:
        if($provisioningData['CoPerson']['status'] == StatusEnum::Deleted) {
          $deletePerson = true;
        } else {
          $syncPerson = true;
        }
        break;
      case ProvisioningActionEnum::CoPersonDeleted:
        $deletePerson = true;
        break;
      default:
        // Ignore all other actions. Note group membership changes
        // are typically handled as CoPersonUpdated events.
        return true;
        break;
    }
    
    if($deleteGroup || $syncGroup || $deletePerson || $syncPerson) {
      // Pull the Server configuation
      
      $args = array();
      $args['conditions']['Server.id'] = $coProvisioningTargetData['CoApiProvisionerTarget']['server_id'];
      $args['conditions']['Server.status'] = SuspendableStatusEnum::Active;
      $args['contain'] = array('HttpServer');

      $CoProvisioningTarget = new CoProvisioningTarget();
      $srvr = $CoProvisioningTarget->Co->Server->find('first', $args);
      
      if(empty($srvr)) {
        throw new InvalidArgumentException(_txt('er.notfound', array(_txt('ct.http_servers.1'), $coProvisioningTargetData['CoApiProvisionerTarget']['server_id'])));
      }
      
      $this->Http = new CoHttpClient();
      
      $this->Http->setConfig($srvr['HttpServer']);
      
      $this->Http->setRequestOptions(array(
        'header' => array(
          'Accept'        => 'application/json',
          'Content-Type'  => 'application/json'
        )
      ));
    }
    
    if($deleteGroup) {
      $this->deleteGroup($provisioningData['CoGroup']);
    }
    
    if($deletePerson) {
      $this->deletePerson($coProvisioningTargetData['CoApiProvisionerTarget'],
                          $provisioningData['CoPerson']['id'],
                          $provisioningData['Identifier']);
    }

    if($syncGroup) {
      $this->syncGroup($coProvisioningTargetData['CoApiProvisionerTarget']['co_provisioning_target_id'],
                       $provisioningData['CoGroup'],
                       $coProvisioningTargetData['CoApiProvisionerTarget']['identifier_type']);
    }
    
    if($syncPerson) {
      $this->syncPerson($coProvisioningTargetData['CoApiProvisionerTarget'],
                        $provisioningData);
    }
  }
  
  /**
   * Delete a CO Group.
   * 
   * @since  COmanage Registry v4.0.0
   * @param  Array            $coGroup CoGroup
   * @throws RuntimeException
   * @return boolean          true
   */
  
  protected function deleteGroup($coGroup) {
    throw new LogicException('NOT IMPLEMENTED');
  }
  
  /**
   * Delete a CO Person.
   * 
   * @since  COmanage Registry v4.0.0
   * @param  array            $coApiProvisionerTarget CoApiProvisioningTarget
   * @param  Integer          $coPersonId             CoPerson ID
   * @param  Array            $identifiers            Array of person's identifiers
   * @return boolean          true
   * @throws RuntimeException
   */
  
  protected function deletePerson($coApiProvisionerTarget,
                                  $coPersonId,
                                  $identifiers) {
    // Find the identifier of the requested identifier type
    // Note similar logic in deletePerson
    
    $identifierType = $coApiProvisionerTarget['identifier_type'];
    $identifier = null;
    
    $ids = Hash::extract($identifiers, '{n}[type='.$identifierType.']');

    if(empty($ids)) {
      throw new RuntimeException(_txt('er.apiprovisioner.id.none', array($identifierType)));
    }
    
    $identifier = $ids[0]['identifier'];
    
    $message = array(
      'meta' => array(
        'version' => '1.0.0',
        'objectType' => 'person'
      ),
      'person' => array(
        'meta' => array(
          'id' => $identifier,
          'deleted' => true
        )
      )
    );
    
    switch($coApiProvisionerTarget['mode']) {
      case ApiProvisionerModeEnum::POST:
        $response = $this->Http->post("/", json_encode($message));
        break;
      case ApiProvisionerModeEnum::PUT:
        // We convert PUT to DELETE since we know an exact URL
        $response = $this->Http->delete("/" . $identifier);
        break;
      default:
        throw new LogicException('NOT IMPLEMENTED');
        break;
    }
    
    if($response->code < 200 || $response->code > 299) {
      throw new RuntimeException($response->reasonPhrase);
    }
    
    return true;
  }
  
  /**
   * Filter attributes from a COmanage model to an API representation.
   *
   * @since  COmanage Registry v4.0.0
   * @param  array $attribute     COmanage model attribute
   * @param  array $subattributes Subattributes to process, COmanage name to API name
   * @param  array $meta          Metadata attributes to process, COmanage name to API name
   * @return array                Filetered attributes
   */
  
  protected function filterAttribute($attribute, $subattributes, $meta) {
    $ret = array();
    
    foreach($meta as $c => $t) {
      if($c == 'id' && !empty($attribute[$c])) {
        // We need to cast to a string (in case of integer)
        $ret['meta'][$t] = strval($attribute[$c]);
      } elseif(in_array($c, array('created', 'modified'))
               && !empty($attribute[$c])) {
        // Timestamps are already UTC, but they're not ISO 8601 format
        
        $ret['meta'][$t] = date('Y-m-d\TH:i:s\Z', strtotime($attribute[$c]));
      } elseif(!empty($attribute[$c])) {
        $ret['meta'][$t] = $attribute[$c];
      }
    }
    
    // Map COmanage subattribute name to TAP Core Schema name
    foreach($subattributes as $c => $t) {
      if($c[0] == '&') {
        // This is a function to apply to $attribute
        $f = substr($c, 1);
        
        $v = $f($attribute);
        
        if(!empty($v)) {
          $ret[$t] = $v;
        }
      } elseif(in_array($c, array('created', 'modified', 'valid_from', 'valid_through'))
               && !empty($attribute[$c])) {
        // Timestamps are already UTC, but they're not ISO 8601 format
        
        $ret[$t] = date('Y-m-d\TH:i:s\Z', strtotime($attribute[$c]));
      } elseif(!empty($attribute[$c])) {
        $ret[$t] = $attribute[$c];
      }
    }
    
    return $ret;
  }
  
  /**
   * Filter attributes from multiple instances of a COmanage model to an API representation.
   *
   * @since  COmanage Registry v4.0.0
   * @param  array $attributes    COmanage model attributes
   * @param  array $subattributes Subattributes to process, COmanage name to API name
   * @param  array $meta          Metadata attributes to process, COmanage name to API name
   * @return array                Filetered attributes
   */
  
  
  protected function filterAttributes($attributes, $subattributes, $meta) {
    $ret = array();
    
    foreach($attributes as $a) {
      $ret[] = $this->filterAttribute($a, $subattributes, $meta);
    }
    
    return $ret;
  }
  
  /**
   * Synchronize a CO Group.
   * 
   * @since  COmanage Registry v4.0.0
   * @param  Integer          $coProvisioningTargetId CoProvisioningTarget ID
   * @param  Array            $coGroup                CoGroup
   * @param  IdentifierEnum   $usernameType           Username type
   * @throws RuntimeException
   * @return boolean          true
   */
  
  protected function syncGroup($coProvisioningTargetId,
                               $coGroup,
                               $usernameType) {
    throw new LogicException('NOT IMPLEMENTED');
  }
  
  /**
   * Synchronize a CO Person.
   * 
   * @since  COmanage Registry v4.0.0
   * @param  array          $coApiProvisionerTarget CoApiProvisioningTarget
   * @param  array          $provisioningData       Provisioning Data
   * @return boolean        true
   * @throws RuntimeException
   */
  
  protected function syncPerson($coProvisioningTarget,
                                $provisioningData) {
    // Find the identifier of the requested identifier type
    // Note similar logic in deletePerson
    
    $identifierType = $coProvisioningTarget['identifier_type'];
    $identifier = null;
    
    $ids = Hash::extract($provisioningData['Identifier'], '{n}[type='.$identifierType.']');

    if(empty($ids)) {
      throw new RuntimeException(_txt('er.apiprovisioner.id.none', array($identifierType)));
    }
    
    $identifier = $ids[0]['identifier'];
    
    // Build out the message body based on the configuration

    $message = array(
      'meta' => array(
        // Note version is also specified in deletePerson()
        'version' => '1.0.0',
        'objectType' => 'person'
      )
    );
    
    if(isset($coProvisioningTarget['include_attributes'])
       && $coProvisioningTarget['include_attributes']) {
      $meta = array(
        'id' => 'id',
        'created' => 'created',
        'modified' => 'lastModified',
        'revision' => 'revision'
      );
      
      $message['person'] = array(
        'meta' => array(
          // We used the configured identifier so the target doesn't need to
          // track co_person_id
          'id' => (string)$identifier,
          'created' => date('Y-m-d\TH:i:s\Z', strtotime($provisioningData['CoPerson']['created'])),
          'lastModified' => date('Y-m-d\TH:i:s\Z', strtotime($provisioningData['CoPerson']['modified']))
        ),
        'status' => strtolower(_txt('en.status', null, $provisioningData['CoPerson']['status'])),
        'names' => $this->filterAttributes($provisioningData['Name'], 
                                           array(
                                             'honorific' => 'prefix',
                                             'given' => 'given',
                                             'middle' => 'middle',
                                             'family' => 'family',
                                             'suffix' => 'suffix',
                                             'language' => 'language',
                                             '&generateCn' => 'formatted',
                                             'type' => 'type'
                                           ),
                                           $meta),
        'identifiers' => $this->filterAttributes($provisioningData['Identifier'], 
                                                 array(
                                                   'identifier' => 'identifier',
                                                   'type' => 'type'
                                                 ),
                                                 $meta),
        'emailAddresses' => $this->filterAttributes($provisioningData['EmailAddress'], 
                                                    array(
                                                      'mail' => 'address',
                                                      'verified' => 'verified',
                                                      'type' => 'type'
                                                    ),
                                                    $meta),
        'urls' => $this->filterAttributes($provisioningData['Url'], 
                                          array(
                                            'url' => 'url',
                                            'type' => 'type'
                                          ),
                                          $meta)
      );
      
      foreach($provisioningData['CoPersonRole'] as $role) {
        $roleAttrs = $this->filterAttribute($role,
                                            array(
                                              'cou_id' => 'couId',
                                              'title' => 'title',
                                              'o' => 'organization',
                                              'ou' => 'department',
                                              'valid_from' => 'validFrom',
                                              'valid_through' => 'validThrough',
                                              'affiliation' => 'affiliation',
                                              'ordr' => 'rank'
                                            ),
                                            $meta);
        
        $roleAttrs['addresses'] = $this->filterAttributes($role['Address'], 
                                                          array(
                                                            'street' => 'streetAddress',
                                                            'room' => 'room',
                                                            'locality' => 'locality',
                                                            'state' => 'region',
                                                            'postal_code' => 'postalCode',
                                                            'country' => 'country',
                                                            'language' => 'language',
                                                            '&formatAddress' => 'formatted',
                                                            'type' => 'type'
                                                          ),
                                                          $meta);
                      
        $roleAttrs['telephoneNumbers'] = $this->filterAttributes($role['TelephoneNumber'], 
                                                                 array(
                                                                   '&formatTelephone' => 'number',
                                                                   'type' => 'type'
                                                                 ),
                                                                 $meta);
        
        $roleAttrs['status'] = strtolower(_txt('en.status', null, $provisioningData['CoPerson']['status']));
        
        $message['person']['roles'][] = $roleAttrs;
      }
      
      if(!empty($provisioningData['CoPerson']['date_of_birth'])) {
        // format needs to be YYYY-MM-DD
        $message['person']['dateOfBirth'] = date('Y-m-d', strtotime($provisioningData['CoPerson']['date_of_birth']));
      }
      
      foreach($provisioningData['CoGroupMember'] as $g) {
        $message['person']['members'][] = $g['CoGroup']['name'];
      }
    } else {
      // XXX pointer back to CO Person
      throw new LogicException('NOT IMPLEMENTED');
    }
    
    switch($coProvisioningTarget['mode']) {
      case ApiProvisionerModeEnum::POST:
        $response = $this->Http->post("/", json_encode($message));
        break;
      case ApiProvisionerModeEnum::PUT:
        $response = $this->Http->post("/" . $identifier, json_encode($message));
        break;
      default:
        throw new LogicException('NOT IMPLEMENTED');
        break;
    }
    
    if($response->code < 200 || $response->code > 299) {
      throw new RuntimeException($response->reasonPhrase);
    }
    
    return true;
  }
}
