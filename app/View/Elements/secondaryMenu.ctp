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
      // Print the user's name
      print '<div id="name">';
      print generateCn($this->Session->read('Auth.User.name'));
      if($this->Session->check('Auth.User.username')) {
        print ' (' . $this->Session->read('Auth.User.username') . ')';
      }
      print '</div>';
    }
  ?>

  <?php if($this->Session->check('Auth.User') != NULL) :
    // Notification Dropdown
  ?>
    <div id="notification">
      <ul class="sf-menu">
        <li>
          <a>
            <span>
              <?php
                // XXX placeholder until notifications is implemented
                $notifArray['approval'] = array();
                $notifArray['accepted'] = array();

                /*
                $notifArray['approval'] = array("Scott Koranda (UWM)",
                                   "Stuart Anderson (CalTech)",
                                   "Benn Oshrin (Internet2)");
                $notifArray['accepted'] = array("Scott Koranda (UWM)",
                                   "Stuart Anderson (CalTech)",
                                   "Heather Flanagan (Internet2)");
                */

                $notificationCount = count($notifArray['approval']) + count($notifArray['accepted']);
                print $notificationCount ? $notificationCount : 0 ?>
            </span>
            <span class="ui-icon ui-icon-mail-closed"></span>
          </a>
          <ul>
            <div>
              <?php if(count($notifArray['approval']) > 0) : ?>
                <div class='sectionTitle titleColor'>Pending Your Approval:</div>
                <div class='names'>
                  <?php foreach($notifArray['approval'] as $n) {
                    print $n;
                    print "<br>";
                  } ?>
                </div>
                <div class = "seemore">
                   <a href="#">See More &raquo;</a>
                </div>
              <?php endif; // count($notifArray['approval']) > 0 ?>

              <?php if(count($notifArray['accepted']) > 0) : ?>
                <div class='sectionTitle titleColor'>Accepted Invitations:</div>
                <div class='names'>
                  <?php  foreach($notifArray['accepted'] as $n) {
                    print $n;
                    print "<br>";
                  } ?>
                </div>
                <div class = "seemore">
                  <a href="#">See More &raquo;</a> 
                </div>
              <?php endif; // count($notifArray['accepted']) > 0 ?>

              <div class="seeOther">
                <a href="#">See Other Notifications &raquo;</a>
              </div>
            </div>
          </ul>
        </li>
      </ul>
    </div>
  <?php endif; // $this->Session->check('Auth.User') != NULL ?>

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

