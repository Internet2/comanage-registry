<?php
/**
 * COmanage Registry CO Salesforce Provisioner Target Model
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
 * @since         COmanage Registry v3.2.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::uses("CoProvisionerPluginTarget", "Model");
App::import("SalesforceProvisioner.Model", "Salesforce");

class CoSalesforceProvisionerTarget extends CoProvisionerPluginTarget {
  // Define class name for cake
  public $name = "CoSalesforceProvisionerTarget";
  
  // Add behaviors
  public $actsAs = array('Containable');
  
  // Association rules from this model to other models
  public $belongsTo = array("CoProvisioningTarget");
  
  // Default display field for cake generated views
  public $displayField = "instance_url";
  
  // Request OAuth2 servers
  public $cmServerType = ServerEnum::Oauth2Server;
  
  // Validation rules for table elements
  public $validate = array(
    'co_provisioning_target_id' => array(
      'rule' => 'numeric',
      'required' => true
    ),
    'server_id' => array(
      'rule' => 'numeric',
      'required' => true
    ),
    'instance_url' => array(
      'rule' => array('url', true),
      'required' => false,
      'allowEmpty' => true
    )
  );
  
  /**
   * Provision for the specified CO Person.
   *
   * @since  COmanage Registry v3.2.0
   * @param  Array CO Provisioning Target data
   * @param  ProvisioningActionEnum Registry transaction type triggering provisioning
   * @param  Array Provisioning data, populated with ['CoPerson'] or ['CoGroup']
   * @return Boolean True on success
   * @throws RuntimeException
   */
  
  public function provision($coProvisioningTargetData, $op, $provisioningData) {
    // First determine what to do
    $deletePerson = false;
    $syncPerson = false;

    switch($op) {
      case ProvisioningActionEnum::CoPersonAdded:
      case ProvisioningActionEnum::CoPersonEnteredGracePeriod:
      case ProvisioningActionEnum::CoPersonExpired:
      case ProvisioningActionEnum::CoPersonPetitionProvisioned:
      case ProvisioningActionEnum::CoPersonPipelineProvisioned:
      case ProvisioningActionEnum::CoPersonReprovisionRequested:
      case ProvisioningActionEnum::CoPersonUnexpired:
      case ProvisioningActionEnum::CoPersonUpdated:
        $syncPerson = true;
        break;
      case ProvisioningActionEnum::CoPersonDeleted:
        // XXX under what circumstances do we delete a person?
        // We don't do anything here because typically we don't have any useful
        // information to process, and we've probably deprovisioned due to
        // status change/group membership loss/etc.
        break;
      default:
        // Ignore all other actions. Note group membership changes
        // are typically handled as CoPersonUpdated events.
        return true;
        break;
    }
    
    $Salesforce = new Salesforce();
    
    // If we have something to do, build an HTTP Client
    if($deletePerson || $syncPerson) {
      $Salesforce->connect($coProvisioningTargetData['CoSalesforceProvisionerTarget']['server_id'],
                           $coProvisioningTargetData['CoSalesforceProvisionerTarget']['id']);
    }
    
    if($syncPerson) {
      // First pass, we only create a contact with Email, FirstName, LastName,
      // Salutation, Suffix
      
      $sfData = array();
      
      // XXX Is there some minimal set of attributes we require to provision?
      // Right now we're only guaranteed given name...
      
      if(!empty($provisioningData['PrimaryName']['honorific'])) {
        $sfData['Salutation'] = $provisioningData['PrimaryName']['honorific'];
      }
      if(!empty($provisioningData['PrimaryName']['given'])) {
        $sfData['FirstName'] = $provisioningData['PrimaryName']['given'];
      }
      if(!empty($provisioningData['PrimaryName']['family'])) {
        $sfData['LastName'] = $provisioningData['PrimaryName']['family'];
      }
      if(!empty($provisioningData['PrimaryName']['suffix'])) {
        $sfData['Suffix'] = $provisioningData['PrimaryName']['suffix'];
      }
      
      // XXX which emailaddress? select by type?
      if(!empty($provisioningData['EmailAddress'][0]['mail'])) {
        $sfData['Email'] = $provisioningData['EmailAddress'][0]['mail'];
      }
      
      // Push the record and grab the Salesforce ID
      $r = $Salesforce->request("/services/data/v39.0/sobjects/Contact/",
                                $sfData,
                                "post");
      
      if(isset($r->success) && (bool)$r->success) {
        $sfid = (string)$r->id;
        
        $args = array(
          'Identifier' => array(
            'identifier'                => $sfid,
            'co_person_id'              => $provisioningData['CoPerson']['id'],
            'type'                      => IdentifierEnum::ProvisioningTarget,
            'login'                     => false,
            'status'                    => SuspendableStatusEnum::Active,
            'co_provisioning_target_id' => $coProvisioningTargetData['CoSalesforceProvisionerTarget']['co_provisioning_target_id']
          )
        );

        $this->CoProvisioningTarget->Co->CoPerson->Identifier->clear();
        $this->CoProvisioningTarget->Co->CoPerson->Identifier->save($args);
      } else {
        // request() should probably have thrown an error already...
        
        throw new RuntimeException(implode(';', $r->errors));
      }
    }
    
    return true;
  }
}
