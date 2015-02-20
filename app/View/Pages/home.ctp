<?php
/**
 * COmanage Registry Home Layout
 *
 * Copyright (C) 2012-14 University Corporation for Advanced Internet Development, Inc.
 * 
 * Licensed under the Apache License, Version 2.0 (the "License"); you may not use this file except in compliance with
 * the License. You may obtain a copy of the License at
 * 
 * http://www.apache.org/licenses/LICENSE-2.0
 * 
 * Unless required by applicable law or agreed to in writing, software distributed under
 * the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY
 * KIND, either express or implied. See the License for the specific language governing
 * permissions and limitations under the License.
 *
 * @copyright     Copyright (C) 2012-14 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.4
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */

  if($this->Session->check('Auth.User')) {
    $userInfo = $this->Session->read('Auth.User');
  } else {
    $userInfo = null;
  }
  
  // Figure out if we have an error to display
  $err = "";
  
  if(!empty($userInfo['cos'])) {
    // Make sure there is at least one active role
    $active = false;
    
    foreach($userInfo['cos'] as $co) {
      if(!empty($co['co_person']['CoPersonRole'])) {
        foreach($co['co_person']['CoPersonRole'] as $r) {
          if($r['status'] == StatusEnum::Active) {
            $active = true;
            break 2;
          }
        }
      }
    }
    
    if(!$active) {
      $err = _txt('er.auth.roles');
    }
  } elseif(empty($userInfo['cos'])
           && !empty($userInfo['org_identities'])) {
    // Valid org identity, but no COs
    $err = _txt('er.auth.co');
  } elseif(empty($userInfo['org_identities'])
           && !empty($userInfo['username'])) {
    // Not a registered identity
    $err = _txt('er.auth.org', array($userInfo['username']));
  } elseif(empty($userInfo['username'])
           && !empty($userInfo)) {
    // No username retrieved (this should have been caught by UsersController)
    $err = _txt('er.auth.empty');
  }
?>

<?php if($err != ""): ?>
<div class="ui-state-highlight ui-corner-all" style="margin-top: 20px; padding:0 .7em;"> 
  <p>
    <span class="ui-icon ui-icon-info" style="float: left; margin-right: .3em;"></span>
    <strong><?php print $err; ?></strong>
  </p>
</div>
<?php else: // $err ?>
<div id="firstPrompt">
  <?php
    // Render some text according to the user's current state
    if(!empty($userInfo['cos'])) {
      // Valid user
      print _txt('op.home.select', array(_txt('coordinate')));
    } elseif(!$userInfo) {
      // Please login
      print _txt('op.home.login', array(_txt('coordinate')));
    }
  ?>
</div>
<?php endif; // $err ?>