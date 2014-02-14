<?php
/*
 * COmanage Registry Secondary Menu Bar
 * Displayed above all pages when logged in
 *
 * Version: $Revision$
 * Date: $Date$
 *
 * Copyright (C) 2012 University Corporation for Advanced Internet Development, Inc.
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
 */
?>

<script>
  $(document).ready(function() {
    $('ul.sf-menu').superfish( {
      delay:       1000,                            // one second delay on mouseout
      animation:   {opacity:'show',height:'show'},  // fade-in and slide-down animation
      speed:       'fast',                          // faster animation speed
      autoArrows:  false,                           // disable generation of arrow mark-up
      dropShadows: true                             // disable drop shadows
    });
  });
</script>

<div id="secondaryMenu" class="rightmenu">
  <?php
  if($this->Session->check('Auth.User.name')) {
    ?>
    <div id="name">
      <ul class="sf-menu">
        <li>
          <span id="comanage-gear"></span>
          <?php
            // Print the user's name
            print generateCn($this->Session->read('Auth.User.name'));
            if($this->Session->check('Auth.User.username')) {
              print ' (' . $this->Session->read('Auth.User.username') . ')';
            }
          ?>
        </li>
      </ul>
    </div>
  <?php
  }
  ?>

  <?php if(isset($vv_my_notifications)): ?>
    <div id="notification">
      <ul class="sf-menu">
        <li>
          <a>
            <span>
              <?php print count($vv_my_notifications); ?>
            </span>
            <span class="ui-icon ui-icon-mail-closed"></span>
          </a>
          <ul>
            <div>
              <!-- XXX use this div class? -->
              <!-- XXX list maybe 10 max, then link to index view -->
              <?php foreach($vv_my_notifications as $n): ?>
              <div class="names">
                <?php
                  $args = array(
                    'controller' => 'co_notifications',
                    'action'     => 'view',
                    $n['CoNotification']['id']
                  );
                  
                  print $this->Html->link($n['CoNotification']['comment']
                                          . " ("
                                          . $this->Time->timeAgoInWords($n['CoNotification']['created'])
                                          . ")",
                                          $args);
                ?>
              </div>
              <?php endforeach; ?>
            </div>
          </ul>
        </li>
      </ul>
    </div>
  <?php endif; ?>

  <?php if (!isset($noLoginLogout)) : ?>
  <div id="logout">
    <span>
      <?php // Print the login/logout buttons
        if($this->Session->check('Auth.User') != NULL) {
          $args = array('controller' => 'auth',
                        'action'     => 'logout',
                        'plugin'     => false);
          print $this->Html->link(_txt('op.logout'), $args);
        } else {
          $args = array('controller' => 'auth',
                        'action'     => 'login',
                        'plugin'     => false
                       );
          print $this->Html->link(_txt('op.login'), $args);
        }
      ?>
    </span>
  </div>
  <?php endif; ?>

</div>

