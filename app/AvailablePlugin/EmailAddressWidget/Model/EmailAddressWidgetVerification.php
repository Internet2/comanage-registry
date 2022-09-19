<?php
/**
 * COmanage Registry Email Address Widget Model
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
 * @since         COmanage Registry v4.1.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::uses("EmailAddress", "Model");
App::uses("CoMessageTemplate", "Model");

class EmailAddressWidgetVerification extends AppModel {
  // Define class name for cake
  public $name = "EmailAddressWidgetVerification";

  // Add behaviors
  public $actsAs = array('Containable');

  // Association rules from this model to other models
  public $belongsTo = array(
    "CoEmailAddressWidget" => array(
      'foreignKey' => 'co_email_address_widget_id'
    )
  );

  // Validation rules for table elements
  public $validate = array(
    'email' => 'email',
    'type' => array(
      'rule' => 'alphaNumeric',
      'required' => true
    ),
    'token' => array(
      'rule' => '/^[a-zA-Z0-9\-]+$/',
      'required' => true
    ),
    'co_email_address_widget_id' => array(
      'rule' => 'numeric',
      'required' => true,
      'allowEmpty' => false
    ),
  );
  
  /**
   * Verify email address using the token passed to the end user via email.
   * Return the outcome (success or failure) to the front end which will continue
   * the process through the Registry API or fail out.
   *
   * @since COmanage Registry v4.1.0
   * @param string $token     Token used for verification
   * @param string $requester The id of the CO Person requesting the verification
   * @param int    $coid      CO Id
   * @return string outcome of verification
   */
  public function verify($token, $requester, $coid) {
    if(empty($token)) {
      return "fail"; // token doesn't exist
    }

    $args = array();
    $args['conditions']['token'] = $token;
    $args['contain'] = array('CoEmailAddressWidget');
    $rec = $this->find('first',$args);

    if(empty($rec)) {
      return "fail"; // token doesn't match
    }

    $CoPerson = ClassRegistry::init('CoPerson');
    $co_person_id = $CoPerson->idForIdentifier($coid, $requester);

    if($rec['EmailAddressWidgetVerification']['co_person_id'] != $co_person_id) {
      return "fail"; // copersonid does not match
    }

    // Check if the verification token is still valid or expired
    $timeElapsed = time() - strtotime($rec['EmailAddressWidgetVerification']['created']);
    $timeWindow = (int)$rec["CoEmailAddressWidget"]["verification_validity"] * 60;

    if($timeElapsed > $timeWindow) {
      // Delete the record and return
      $this->delete($rec['EmailAddressWidgetVerification']['id']);
      return "timeout"; // token timed out
    }

    // Create the new CO Person Email record
    $emailAttrs = array(
      'mail' => $rec['EmailAddressWidgetVerification']['email'],
      'type' => $rec['EmailAddressWidgetVerification']['type'],
      'verified' => true,
      'co_person_id' => $rec['EmailAddressWidgetVerification']['co_person_id']
    );

    try {
      $EmailAddress = ClassRegistry::init('EmailAddress');
      if(!$EmailAddress->save($emailAttrs, array("provision" => true,
                                                 "trustVerified" => true))) {
        return "nosave";
      }
      // Delete the Verification Request table record and return
      $this->delete($rec['EmailAddressWidgetVerification']['id']);
      return "success";
    } catch(Exception $e) {
      throw new RuntimeException($e->getMessage());
    }
  }
}
