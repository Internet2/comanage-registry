<?php
/**
 * COmanage Registry JSON (VOOT) People View
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
 * @since         COmanage Registry v0.6
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */
  
  $people = array();
  
  if(isset($co_people)) {
    foreach($co_people as $p) {
      // We need to determine if the current person is a member or admin.
      // This should probably be set for us by the controller, but hey it's experimental.
      
      $pid = $p['CoPerson']['id'];
      $grole = 'none';
      
      if(isset($co_group_members)
         && in_array($pid, $co_group_members)) {
        $grole = 'member';
      }
      
      if(isset($co_group_owners)
         && in_array($pid, $co_group_owners)) {
        $grole = 'owner';
      }
      
      // Is there a usable email address?
      $email = array();
      
      if(isset($p['EmailAddress'][0]['mail'])) {
        $email[] = array(
          'value' => $p['EmailAddress'][0]['mail'],
          'type'  => 'email'
        );
      }
      
      $people[] = array(
        'id'                    => $pid,
        'displayName'           => generateCn($p['PrimaryName']),
        'name'                  => array(
          'formatted'             => generateCn($p['PrimaryName']),
          'givenName'             => $p['PrimaryName']['given'],
          'familyName'            => $p['PrimaryName']['family'],
        ),
        'emails'                => $email,
        'voot_membership_role'  => $grole
      );
    }
  }
  
  print json_encode(array("startIndex" => 0,
                          "totalResults" => count($people),
                          "itemsPerPage" => count($people),
                          "entry" => $people));
?>