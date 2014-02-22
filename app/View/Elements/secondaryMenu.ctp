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
 
if(isset($menuContent['plugins'])) {
  $plugins = $menuContent['plugins'];
} else {
  $plugins = array();
}

/**
 * Render menu links for plugin-defined menu items.
 * - postcondition: HTML emitted
 *
 * @param HtmlHelper Helper to use to render links
 * @param Array Array of plugins as created by AppController
 * @param String Which menu items to render
 * @param Integer CO ID to render
 */

function render_plugin_menus($htmlHelper, $plugins, $menu, $coId) {
  if(!empty($plugins)) {
    foreach(array_keys($plugins) as $plugin) {
      if(isset($plugins[$plugin][$menu])) {
        foreach(array_keys($plugins[$plugin][$menu]) as $label) {
          $args = $plugins[$plugin][$menu][$label];
          
          $args['plugin'] = Inflector::underscore($plugin);
          if($menu != 'cmp') { $args['co'] = $coId; }
          
          print "<li>" . $htmlHelper->link($label, $args) . "</li>\n";
        }
      }
    }
  }
}
 
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
  <?php if($this->Session->check('Auth.User.name')): ?>
    <div id="name">
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
              if($this->Session->check('Auth.User.cos'))
                $mycos = $this->Session->read('Auth.User.cos');
              
              // Profiles
              if(isset($permissions['menu']['coprofile']) && $permissions['menu']['coprofile']) {
                $coCount = count($mycos);
                
                // Identity Submenu
                print '<li>
                         <a href="#">'._txt('me.identity').'</a>
                         <span class="sf-sub-indicator"> »</span>
                         <ul>';
                foreach ($mycos as $co) {
                  print "<li>";
                    $args = array(
                      'controller' => 'co_people',
                      'action' => 'edit',
                      $co['co_person_id'],
                      'co' => $co['co_id']
                    );
                    print $this->Html->link(_txt('me.for', array($co['co_name'])), $args);
                  print "</li>";
                }
                print '</ul>
                    </li>';
                
                // T&C Submenu
                print '<li>
                         <a href="#">'._txt('me.tandc').'</a>
                         <span class="sf-sub-indicator"> »</span>
                         <ul>';
                foreach ($mycos as $co) {
                  print "<li>";
                    $args = array(
                      'controller' => 'co_terms_and_conditions',
                      'action' => 'review',
                      'copersonid' => $co['co_person_id'],
                      'co' => $co['co_id']
                    );
                    print $this->Html->link(_txt('me.for', array($co['co_name'])), $args);
                  print "</li>";
                }
                print '</ul>
                    </li>';
                
                // Demographics submenu
                print '<li> 
                         <a href="#">'._txt('ct.co_nsf_demographics.pl').'</a>
                         <span class="sf-sub-indicator"> »</span>
                         <ul>';
                
                foreach ($menuContent['CoNsfDemographic'] as $d) {
                  print "<li>";
                    $args = array(
                      'plugin' => null,
                      'controller' => 'co_nsf_demographics',
                      'co'         => $d['co_id']
                    );
                  
                  // If the record already exists, the id is needed for edit
                  if(isset($d['id']))
                    $args[] = $d['id'];
                  
                  // Adjust the link to the NSF Demographics Controller according to 
                  // whether or not data has been set already.
                  $args['action'] = $d['action'];
                  
                  // If the record does not exist, the person id is needed for add
                  if(isset($d['co_person_id']))
                    $args['copersonid'] = $d['co_person_id'];
                    
                  print $this->Html->link(_txt('me.for', array($d['co_name'])), 
                                          $args
                                         );
                  print "</li>";
                }
                
                print '  </ul>
                      </li>';
              }
            
              // Plugin submenus
              // This rendering is a bit different from how render_plugin_menus() does it...
              foreach(array_keys($plugins) as $plugin) {
                if(isset($plugins[$plugin]['coperson'])) {
                  foreach(array_keys($plugins[$plugin]['coperson']) as $label) {
                    print '<li> 
                             <a href="#">'.$label.'</a>
                             <span class="sf-sub-indicator"> »</span>
                             <ul>';
                    
                    foreach ($mycos as $co) {
                      $args = $plugins[$plugin]['coperson'][$label];
                      
                      $args[] = $co['co_person_id'];
                      $args['plugin'] = $plugin;
                      $args['co'] = $co['co_id'];
                      
                      print "<li>" . $this->Html->link(_txt('me.for', array($co['co_name'])), $args) . "</li>\n";
                    }
                    
                    print "</ul></li>";
                  }
                }
              }
            ?>
            
            <!-- Platform Dropdown -->
            <?php if($permissions['menu']['admin']): ?>
              <li class="dropMenu">
                <a href="#">
                  <?php print _txt('me.platform');?>
                  <span class="sf-sub-indicator"> »</span>
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
                  <?php render_plugin_menus($this->Html, $plugins, 'cmp', $menuCoId); ?>
                </ul>
              </li>
            <?php endif; ?>
            
            
          </ul>
        </li>
      </ul>
    </div>
  <?php endif ?>

  <?php if(isset($vv_my_notifications)): ?>
    <div id="notification">
      <ul class="sf-menu">
        <li>
          <a class="menuTop">
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

