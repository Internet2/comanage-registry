<?php
/**
 * COmanage Registry Email Widget Model
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

class EmailWidgetEmail extends AppModel {
  // Define class name for cake
  public $name = "EmailWidgetEmail";
  
  public $hasMany = array(
    "EmailWidgetEmail"
  );
	
  // Validation rules for table elements
  public $validate = array(
    'email' => 'email',
    'type' => array(
      'rule' => 'alphaNumeric',
      'required' => true
    ),
    'token' => array(
      'rule' => 'alphaNumeric',
      'required' => true
    )
	);
  
  /**
   * Generate a token for email verification and save it in a new record
   * along with the email address and email type to be  added.
   * This is step one of a two-step email verification process.
   * 
   * @since COmanage Registry v4.1.0
   * @param string $emailAddress Email address to be added
   * @param string $emailType    Type of email address to be added 
   * @return integer id of the new row
   */
  public function generateToken($emailAddress, $emailType, $primary) {
    $token = bin2hex(random_bytes(3));
    
    $fields = array(
      'email' => $emailAddress,
      'type' => $emailType,
      'token' => $token,
      'primary_email' => $primary
    );
    
    $this->EmailWidgetEmail->save($fields);
    $id = $this->EmailWidgetEmail->id;
    
    return $id;
  }
  
  /**
   * Verify email address using the token passed to the end user via email.
   * Return the outcome (success or failure) to the front end which will continue
   * the process through the Registry API or fail out.
   *
   * @since COmanage Registry v4.1.0
   * @param string $token Token used for verification
   * @param string $id row id for token lookup
   * @return string outcome of verification
   */
  public function verify($token, $id, $coPersonId) {
    $args = array();
    $args['conditions']['id'] = $id;
    $rec = $this->find('first',$args);
    if($rec['EmailWidgetEmail']['token'] == $token) {
      // If more than 10 minutes have elapsed (600 seconds) fail with "timeout".
      $timeElapsed =  time() - strtotime($rec['EmailWidgetEmail']['created']);
      if($timeElapsed < 600) {
        $emailAttrs = array(
          'mail' => $rec['EmailWidgetEmail']['email'],
          'type' => $rec['EmailWidgetEmail']['type'],
          'verified' => true,
          // 'primary_email' => $rec['EmailWidgetEmail']['primary_email'], // TODO uncomment when available
          'co_person_id' => $coPersonId
        );
        try {
          $this->EmailAddress = new EmailAddress();
          $this->EmailAddress->save($emailAttrs);
          // test for success
          if(!empty($this->EmailAddress->id)) {
            $this->delete($id);
            return "success";  
          } else {
            return "nosave";
          }
        } catch(Exception $e) {
          throw new RuntimeException($e->getMessage());
        }  
      } else {
        return "timeout"; // token timed out
      }
    } 
    return "fail"; // token doesn't match
  }
}
