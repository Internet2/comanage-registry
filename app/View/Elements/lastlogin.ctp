<?php
/**
 * COmanage Registry Last Login View Element
 *
 * Copyright (C) 2017 University Corporation for Advanced Internet Development, Inc.
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
 * @copyright     Copyright (C) 2017 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v1.1.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */

  // See note in UsersController::login why we only pull the authenticated identifier
  // and not any others.
  $lastlogin = $this->Session->read('Auth.User.lastlogin');
  
  // Make sure we have at least one record to render
  $a = Hash::extract($lastlogin, '{s}.AuthenticationEvent.authenticated_identifier');
  
  if(!empty($a)):
?>
<div id="lastLogin">
  <div class="ui-state-highlight ui-corner-all co-info-topbox">
    <?php foreach($lastlogin as $u => $l): ?>
    <?php if(!empty($l)): ?>
    <p>
      <span class="ui-icon ui-icon-info co-info"></span>
      <strong><?php print _txt('in.login.last', array($u,
                                                      $l['AuthenticationEvent']['created'],
                                                      ($l['AuthenticationEvent']['remote_ip'] ?: "?"))); ?></strong>
    </p>
    <?php endif; // !empty ?>
    <?php endforeach; ?>
  </div>
</div>
<?php
  endif; // $lastlogin