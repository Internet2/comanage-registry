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
    ),
    'middle_name' => array(
      'rule' => 'boolean'
    ),
    'email_address_type' => array(
      'rule' => 'notBlank',
      'required' => false,
      'allowEmpty' => true
    ),
    'obj_coperson' => array(
      'rule' => 'boolean'
    ),
    'platform_id_type' => array(
      'rule' => 'notBlank',
      'required' => false,
      'allowEmpty' => true
    ),
    'app_id_type' => array(
      'rule' => 'notBlank',
      'required' => false,
      'allowEmpty' => true
    )
  );
  
  /**
   * Obtain Salesforce Object identifiers for the specified CO Person, if known.
   *
   * @since  COmanage Registry v3.2.0
   * @param  Integer $coProvisionerTargetId CO Provisioning Target ID
   * @param  Integer $coPersonId            CO Person ID
   * @return Array Array of Contact ID and (if enabled) CoPerson Custom Object ID
   */
  
  public function getSalesforceIdentifiers($coProvisioningTargetId, $coPersonId) {
    $ret = array();
    
    $args = array();
    $args['conditions']['Identifier.co_person_id'] = $coPersonId;
    $args['conditions']['Identifier.type'] = IdentifierEnum::ProvisioningTarget;
    $args['conditions']['Identifier.co_provisioning_target_id'] = $coProvisioningTargetId;
    $args['contain'] = false;

    $idrec = $this->CoProvisioningTarget->Co->CoPerson->Identifier->find('first', $args);

    if(!empty($idrec['Identifier']['identifier'])) {
      // The identifier is either a simple SF Contact ID, or a compound
      // Contact ID and CoPerson Custom Object ID (separated by a slash)
      
      $ids = explode('/', $idrec['Identifier']['identifier']);
      
      $ret['contact'] = $ids[0];
      
      if(!empty($ids[1])) {
        $ret['copersonobj'] = $ids['1'];
      }
      
      // Also store the identifier_id to make it easier to update
      $ret['identifier_id'] = $idrec['Identifier']['id'];
    }
    
    return $ret;
  }
  
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
      // Middle Name needs to be enabled in Salesforce, so we don't populate it unless asked
      if($coProvisioningTargetData['CoSalesforceProvisionerTarget']['middle_name']
         && !empty($provisioningData['PrimaryName']['middle'])) {
        $sfData['MiddleName'] = $provisioningData['PrimaryName']['middle'];
      }
      if(!empty($provisioningData['PrimaryName']['family'])) {
        $sfData['LastName'] = $provisioningData['PrimaryName']['family'];
      }
      if(!empty($provisioningData['PrimaryName']['suffix'])) {
        $sfData['Suffix'] = $provisioningData['PrimaryName']['suffix'];
      }
      
      if(!empty($provisioningData['EmailAddress'])) {
        if(!empty($coProvisioningTargetData['CoSalesforceProvisionerTarget']['email_address_type'])) {
          // Look for the first address of the specified type
          
          $addrs = Hash::extract($provisioningData['EmailAddress'], '{n}[type='.$coProvisioningTargetData['CoSalesforceProvisionerTarget']['email_address_type'].']');
          
          if(!empty($addrs[0]['mail'])) {
            $sfData['Email'] = $addrs[0]['mail'];
          }
        } elseif(!empty($provisioningData['EmailAddress'][0]['mail'])) {
          // Look for the first address of any type
          $sfData['Email'] = $provisioningData['EmailAddress'][0]['mail'];
        }
      }
      
      // If we have a role, pull some attributes from it. We use the first role,
      // which can be coerced by setting 'ordr'.
      
      if(!empty($provisioningData['CoPersonRole'][0])) {
        if(!empty($provisioningData['CoPersonRole'][0]['ou'])) {
          $sfData['Department'] = $provisioningData['CoPersonRole'][0]['ou'];
        }
        
        if(!empty($provisioningData['CoPersonRole'][0]['title'])) {
          $sfData['Title'] = $provisioningData['CoPersonRole'][0]['title'];
        }
        
        // Are there telephone numbers attached to the role?
        if(!empty($provisioningData['CoPersonRole'][0]['TelephoneNumber'])) {
          $teltypes = array(
  // Home doesn't seem to render by default, so for now we won't send it
  //          ContactEnum::Home   => 'HomePhone',
            ContactEnum::Mobile => 'MobilePhone',
            ContactEnum::Office => 'Phone'
          );
          
          foreach($teltypes as $t => $s) {
            $number = Hash::extract($provisioningData['CoPersonRole'][0]['TelephoneNumber'], '{n}[type=' . $t . ']');
            
            if(!empty($number[0])) {
              $sfData[$s] = formatTelephone($number[0]);
            }
          }
        }
        
        // Do we have a mailing address?
        if(!empty($provisioningData['CoPersonRole'][0]['Address'])) {
          $addr = Hash::extract($provisioningData['CoPersonRole'][0]['Address'], '{n}[type=' . ContactEnum::Postal . ']');
          
          if(!empty($addr[0])) {
            $sfData['MailingStreet'] = $addr[0]['street'];
            
            if(!empty($addr[0]['room'])) {
              $sfData['MailingStreet'] .= "\n" . $addr[0]['room'];
            }
            
            if(!empty($addr[0]['locality'])) {
              $sfData['MailingCity'] = $addr[0]['locality'];
            }
            
            // State and Country must match SF pick lists
            if(!empty($addr[0]['state'])) {
              $sfData['MailingState'] = $addr[0]['state'];
            }
            
            if(!empty($addr[0]['country'])) {
              $sfData['MailingCountry'] = $addr[0]['country'];
            }
            
            if(!empty($addr[0]['postal_code'])) {
              $sfData['MailingPostalCode'] = $addr[0]['postal_code'];
            }
          }
        }
      }
      
      if(!empty($coProvisioningTargetData['CoSalesforceProvisionerTarget']['default_account'])) {
        // Set the Account ID for the Person
        $sfData['AccountId'] = $coProvisioningTargetData['CoSalesforceProvisionerTarget']['default_account'];
      }
      
      // Do we already have Salesforce IDs for this person?
      $sfids = $this->getSalesforceIdentifiers($coProvisioningTargetData['CoSalesforceProvisionerTarget']['co_provisioning_target_id'],
                                               $provisioningData['CoPerson']['id']);
      
      if(!empty($sfids['contact'])) {
        // Update an existing record. We'd have to retrieve the current record to see
        // which fields changed, so we send all fields again rather than consume an
        // API call.
        
        $r = $Salesforce->request("/services/data/v39.0/sobjects/Contact/" . $sfids['contact'],
                                  $sfData,
                                  "patch");
      } else {
        // Create a new record. Push the record and grab the Salesforce ID.
        $r = $Salesforce->request("/services/data/v39.0/sobjects/Contact/",
                                  $sfData,
                                  "post");
        
        if(isset($r->success) && (bool)$r->success) {
          // Store the Contact ID
          
          $sfids['contact'] = (string)$r->id;
          
          $sfids['identifier_id'] = $this->setSalesforceIdentifiers($coProvisioningTargetData['CoSalesforceProvisionerTarget']['co_provisioning_target_id'],
                                                                    $provisioningData['CoPerson']['id'],
                                                                    $sfids['contact']);
        } else {
          // request() should probably have thrown an error already...
          
          throw new RuntimeException(implode(';', $r->errors));
        }
      }
      
      // Save the CoPerson object, if enabled
      if($coProvisioningTargetData['CoSalesforceProvisionerTarget']['obj_coperson']) {
        $sfData = array();
        
        // Map identifiers

        if(!empty($provisioningData['Identifier'])) {
          // We expect a valid platform ID since we suggest setting it up as an external key.
          $ids = Hash::extract($provisioningData['Identifier'], '{n}[type='.$coProvisioningTargetData['CoSalesforceProvisionerTarget']['platform_id_type'].']');
          
          if(!empty($ids[0]['identifier'])) {
            $sfData['Platform_ID__c'] = $ids[0]['identifier'];
          }
          
          // Map the app identifier, if configured
          if(!empty($coProvisioningTargetData['CoSalesforceProvisionerTarget']['app_id_type'])) {
            $ids = Hash::extract($provisioningData['Identifier'], '{n}[type='.$coProvisioningTargetData['CoSalesforceProvisionerTarget']['app_id_type'].']');
            
            if(!empty($ids[0]['identifier'])) {
              $sfData['Application_ID__c'] = $ids[0]['identifier'];
            }
          }
          
          // Map the ORCID ID, if available
          $ids = Hash::extract($provisioningData['Identifier'], '{n}[type='.IdentifierEnum::ORCID.']');
          
          if(!empty($ids[0]['identifier'])) {
            $sfData['ORCID__c'] = $ids[0]['identifier'];
          }
        }
        
        // Map status
        $sfData['Status__c'] = _txt('en.status', null, $provisioningData['CoPerson']['status']);

        if(!empty($sfids['copersonobj'])) {
          // Update the existing record
          $r = $Salesforce->request("/services/data/v39.0/sobjects/CoPerson__c/" . $sfids['copersonobj'],
                                    $sfData,
                                    "patch");
        } else {
          // Add a new record
          
          // We need to add the key to the parent record on Add only, not on Update
          $sfData['Contact__c'] = $sfids['contact'];
          
          $r = $Salesforce->request("/services/data/v39.0/sobjects/CoPerson__c/",
                                    $sfData,
                                    "post");
          
          if(isset($r->success) && (bool)$r->success) {
            // Store the Object ID
            
            $sfids['copersonobj'] = (string)$r->id;
            
            $this->setSalesforceIdentifiers($coProvisioningTargetData['CoSalesforceProvisionerTarget']['co_provisioning_target_id'],
                                            $provisioningData['CoPerson']['id'],
                                            $sfids['contact'],
                                            $sfids['identifier_id'],
                                            $sfids['copersonobj']);
          }
        }
      }
    }
    
    return true;
  }
  
  /**
   * Store Salesforce Object identifiers for the specified CO Person.
   *
   * @since  COmanage Registry v3.2.0
   * @param  Integer $coProvisionerTargetId CO Provisioning Target ID
   * @param  Integer $coPersonId            CO Person ID
   * @param  String  $contactId             Salesforce Contact ID
   * @param  Integer $identifierId          Identifier ID, if this is an update
   * @param  string  $coPersonObjectId      Salesforce CoPerson Custom Object ID
   * @return Integer Identifier ID of saved record
   */
  
  public function setSalesforceIdentifiers($coProvisioningTargetId, $coPersonId, $contactId, $identifierId=null, $coPersonObjectId=null) {
    // Assemble an Identifier record
    $id = $contactId;
    
    if($coPersonObjectId) {
      $id .= "/" . $coPersonObjectId;      
    }
    
    $args = array(
      'Identifier' => array(
        'identifier'                => $id,
        'co_person_id'              => $coPersonId,
        'type'                      => IdentifierEnum::ProvisioningTarget,
        'login'                     => false,
        'status'                    => SuspendableStatusEnum::Active,
        'co_provisioning_target_id' => $coProvisioningTargetId
      )
    );
    
    if($identifierId) {
      // This is an update, not an insert
      $args['Identifier']['id'] = $identifierId;
    }

    $this->CoProvisioningTarget->Co->CoPerson->Identifier->clear();
    $this->CoProvisioningTarget->Co->CoPerson->Identifier->save($args);
    
    return $this->CoProvisioningTarget->Co->CoPerson->Identifier->id;
  }
}
