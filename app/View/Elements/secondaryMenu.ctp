<?php
/*
 * COmanage Registry Secondary Menu Bar
 * Displayed above all pages when logged in
 *
 * Version: $Revision$
 * Date: $Date$
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
 */    
?>

<script>
  $(document).ready(function() {
    $('ul.sf-menu').superfish( {
      delay:       250,                             // one-quarter second delay on mouseout
      animation:   {opacity:'show',height:'show'},  // fade-in and slide-down animation
      speed:       1,                               // setting this to 1 millisecond is how to make sf-menus appear instantly
      autoArrows:  false,                           // disable generation of arrow mark-up
      dropShadows: true                             // disable drop shadows
    });
  });
</script>

<div id="secondaryMenu" class="rightmenu">
  <!-- Platform Dropdown -->
  <?php if(!empty($permissions['menu']['admin']) && $permissions['menu']['admin']): ?>
    <div id="platform" class="row1-dropdown">
      <ul class="sf-menu">
        <li class="dropMenu">
          <a href="#" class="menuTop">
            <span class="ui-icon ui-icon-wrench"></span>
            <?php print _txt('me.platform');?>
          </a>
          <ul>
            <li>
              <?php
                $args = array();
                $args['plugin'] = null;
                $args['controller'] = 'api_users';
                $args['action'] = 'index';
                
                print $this->Html->link(_txt('ct.api_users.pl'), $args);
              ?>
            </li>
            <li>
              <?php
                $args = array();
                $args['plugin'] = null;
                $args['controller'] = 'cmp_enrollment_configurations';
                $args['action'] = 'select';
                
                print $this->Html->link(_txt('ct.cmp_enrollment_configurations.pl'), $args);
              ?>
            </li>
            <li>
              <?php
                $args = array();
                $args['plugin'] = null;
                $args['controller'] = 'cos';
                $args['action'] = 'index';
                
                print $this->Html->link(_txt('ct.cos.pl'), $args);
              ?>
            </li>
            <li>
              <?php
                $args = array();
                $args['plugin'] = null;
                $args['controller'] = 'navigation_links';
                $args['action'] = 'index';
                
                print $this->Html->link(_txt('ct.navigation_links.pl'), $args);
              ?>
            </li>
            <li>
              <?php
                $args = array();
                $args['plugin'] = null;
                $args['controller'] = 'organizations';
                $args['action'] = 'index';
                
                print $this->Html->link(_txt('ct.organizations.pl'), $args);
              ?>
            </li>
            <?php
              if(!empty($menuContent['plugins'])) {
                render_plugin_menus($this->Html, $menuContent['plugins'], 'cmp');
              }
            ?>
          </ul>
        </li>
      </ul>
    </div>
  <?php endif; ?>

  <?php if($this->Session->check('Auth.User.name')): ?>
    <div id="name" class="row1-dropdown">
      <ul class="sf-menu">
        <li class="dropMenu">
          <a href="#" class="menuTop">
            <span id="comanage-gear"></span>
            <?php
              // Print the user's name
              print generateCn($this->Session->read('Auth.User.name'));
              print ' (' . $this->Session->read('Auth.User.username') . ')';
            ?>
          </a>
          <!-- Account Dropdown -->
          <ul>
            <?php
              // Profiles
              if(isset($permissions['menu']['coprofile']) && $permissions['menu']['coprofile']) {
                // Link to identity for self service
                
                foreach($menuContent['cos'] as $co) {
                  if(isset($co['co_person']['status'])
                     && $co['co_person']['status'] == StatusEnum::Active) {
                    print "<li>";
                    $args = array(
                      'controller' => 'co_people',
                      'action' => 'canvas',
                      $co['co_person_id']
                    );
                    print $this->Html->link(_txt('me.identity.for', array($co['co_name'])), $args);
                    print "</li>";
                  }
                }
              }
              
              // Plugin submenus
              // This rendering is a bit different from how render_plugin_menus() does it...
              foreach(array_keys($menuContent['plugins']) as $plugin) {
                if(isset($menuContent['plugins'][$plugin]['coperson'])) {
                  foreach(array_keys($menuContent['plugins'][$plugin]['coperson']) as $label) {
                    print '<li> 
                             <a href="#">'.$label.'</a>
                             <span class="sf-sub-indicator"> »</span>
                             <ul>';
                    
                    foreach($menuContent['cos'] as $co) {
                      $args = $menuContent['plugins'][$plugin]['coperson'][$label];
                      
                      $args[] = $co['co_person_id'];
                      $args['plugin'] = Inflector::underscore($plugin);
                      
                      print "<li>" . $this->Html->link(_txt('me.identity.for', array($co['co_name'])), $args) . "</li>\n";
                    }
                    
                    print "</ul></li>";
                  }
                }
              }
            ?>
          </ul>
        </li>
      </ul>
    </div>
  <?php endif ?>

  <?php if(isset($vv_my_notifications)): ?>
    <div id="notification">
      <ul class="sf-menu">
        <li>
          <a class="menuTop" href="/registry/co_notifications/index/recipientcopersonid:<?php print $vv_co_person_id; ?>">
            <span>
              <?php print count($vv_my_notifications); ?>
            </span>
            <span class="ui-icon ui-icon-mail-closed"></span>
          </a>
          <ul>
            <?php $notificationCount = 0; ?>
            <?php foreach($vv_my_notifications as $n): ?>
            <li class="names">
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

                $notificationCount++;
                if ($notificationCount > 8) {
                  break;
                }
              ?>
            </li>
            <?php endforeach; ?>
            <li class="see-all">
              <a href="/registry/co_notifications/index/recipientcopersonid:<?php print $vv_co_person_id; ?>/sort:created/direction:desc"><?php print _txt('op.see.notifications')?></a>
            </li>
          </ul>
        </li>
      </ul>
    </div>
  <?php endif; ?>

  <?php if(!isset($noLoginLogout) || !$noLoginLogout) : ?>
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

