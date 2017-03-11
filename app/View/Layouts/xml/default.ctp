<?php
/**
 * COmanage Registry XML Layout
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
 * @since         COmanage Registry v0.4
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

if(isset($invalid_fields) || isset($memberships)) {
  $xa['ErrorResponse']['@Version'] = '1.0';
  
  // Invalid fields

  if(isset($invalid_fields)) {
    foreach(array_keys($invalid_fields) as $f) {
      $xa['ErrorResponse']['InvalidFields']['InvalidField'][] = array(
        '@Field' => $f,
        'Error' => $invalid_fields[$f]
      );
    }
  }

  if(isset($memberships)) {
    foreach(array_keys($memberships) as $f) {
      $xa['Memberships']['Membership'][] = array(
        '@Id' => $f,
        'Name' => $memberships[$f]
      );
    }
  }
  
  $xobj = Xml::fromArray($xa, array('format' => 'tags'));
  print $xobj->asXML();
}

print $this->fetch('content');
