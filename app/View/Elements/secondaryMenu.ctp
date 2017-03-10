<?php
/*
 * COmanage Registry Secondary Menu Bar
 * Displayed above all pages when logged in
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
 * @since         COmanage Registry v?
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
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

    $('#co-top-username').hover(
      function() {
        $('#co-top-id').show();
      },
      function() {
        $('#co-top-id').hide();
      }
    );
  });
</script>

<div id="secondaryMenu" class="rightmenu">
  <!-- Platform Dropdown -->
  <?php if(!empty($permissions['menu']['admin']) && $permissions['menu']['admin']): ?>
    <div id="platform" class="row1-dropdown">
      <ul class="sf-menu">
        <li class="dropMenu">
          <a href="#" class="menuTop" >
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
                $args['controller'] = 'authentication_events';
                $args['action'] = 'index';
                
                print $this->Html->link(_txt('ct.authentication_events.pl'), $args);
              ?>
            </li>
            <?php if($pool_org_identities): ?>
            <li>
              <?php
                $args = array();
                $args['plugin'] = null;
                $args['controller'] = 'attribute_enumerations';
                $args['action'] = 'index';
                
                print $this->Html->link(_txt('ct.attribute_enumerations.pl'), $args);
              ?>
            </li>
            <?php endif; // pool_org_identities ?>
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
            <?php if($pool_org_identities): ?>
            <li>
              <?php
                // If org identities are pooled, only CMP admins can define sources
                $args = array();
                $args['plugin'] = null;
                $args['controller'] = 'org_identity_sources';
                $args['action'] = 'index';
                
                print $this->Html->link(_txt('ct.org_identity_sources.pl'), $args);
              ?>
            </li>
            <?php endif; // pool_org_identities ?>
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
          <a href="#" class="menuTop" id="co-top-username">
            <span id="co-top-id">
              <span id="co-top-id-name"><?php
                print filter_var($this->Session->read('Auth.User.username'),FILTER_SANITIZE_SPECIAL_CHARS);
              ?></span>
              <span id="co-top-id-arrow"></span>
            </span>
            <span id="comanage-gear"></span>
            <?php
              // Print the user's name
              print filter_var(generateCn($this->Session->read('Auth.User.name')),FILTER_SANITIZE_SPECIAL_CHARS);
            ?>
          </a>
          <!-- Account Dropdown -->
          <ul>
            <?php
              // Profiles
              if(isset($permissions['menu']['coprofile']) && $permissions['menu']['coprofile']) {
                // Link to identity for self service.
                
                foreach($menuContent['cos'] as $co) {
                  // The person must have an Active/GracePeriod status and at least
                  // one defined role.
                  
                  if(isset($co['co_person']['status'])
                     && ($co['co_person']['status'] == StatusEnum::Active
                         || $co['co_person']['status'] == StatusEnum::GracePeriod)
                     && !empty($co['co_person']['CoPersonRole'])) {
                    print "<li>";
                    $args = array(
                      'plugin' => '',
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
                      if(empty($co['co_person_id']))
                        continue;
                      
                      $args = $menuContent['plugins'][$plugin]['coperson'][$label];
                      $args['copersonid'] = $co['co_person_id'];
                      $args['plugin'] = Inflector::underscore($plugin);
                      
                      print "<li>" . $this->Html->link($co['co_name'], $args) . "</li>\n";
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
          <a class="menuTop" href="/registry/co_notifications/index/recipientcopersonid:<?php print $vv_co_person_id_notifications; ?>">
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
              <a href="/registry/co_notifications/index/recipientcopersonid:<?php print $vv_co_person_id_notifications; ?>/sort:created/direction:desc"><?php print _txt('op.see.notifications')?></a>
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
