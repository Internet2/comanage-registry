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
 * @since         COmanage Registry v3.0.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */
?>

<?php if(isset($vv_my_notifications)): ?>

  <div id="notifications">
    <button class="topMenu cm-toggle" id="user-notifications">
      <span id="user-notification-count">
         <?php print $vv_my_notification_count; ?>
      </span>
      <?php if(count($vv_my_notifications) > 0): ?>
        <em class="material-icons icon-adjust">notifications_active</em>
      <?php else: ?>
        <em class="material-icons icon-adjust">notifications</em>
      <?php endif?>
      <em class="material-icons">arrow_drop_down</em>
    </button>
    <ul id="notifications-menu">

      <?php $notificationCount = 0; ?>
      <?php foreach($vv_my_notifications as $n): ?>
        <li class="notification">
          <?php
            $args = array(
              'controller' => 'co_notifications',
              'action'     => 'view',
              $n['CoNotification']['id'],
              'origin'     => base64_encode($this->request->url)
            );

            print '<span class="notification-comment">';
            print $this->Html->link($n['CoNotification']['comment'],
                                    $args,
                                    array(
                                      'class' => 'lightbox spin',
                                      'title' => _txt('op.see.notification.num',array($n['CoNotification']['id']))
                                    )
            );
            print '</span>';
            print '<span class="notification-created">';
            print $this->Time->timeAgoInWords($n['CoNotification']['created']);
            print '</span>';

            $notificationCount++;
            if($notificationCount > 4) {
              break;
            }
          ?>
        </li>
      <?php endforeach; ?>
      <li id="see-all">
      <?php
        print $this->Html->link(
          _txt('op.see.notifications'),
          [
            'controller' => 'co_notifications',
            'action' => 'index',
            'recipientcopersonid' => $vv_co_person_id_notifications,
            'sort' => 'created',
            'direction' => 'desc',
            'search.status' => NotificationStatusEnum::PendingAcknowledgment,
            'op' => 'search',
          ],
          ['class' => 'co-raised-button btn btn-default']
        );
      ?>
      </li>
    </ul>
  </div>
<?php endif; ?>

<?php if($this->Session->check('Auth.User.username')): ?>
  <?php $userCN = generateCn($this->Session->read('Auth.User.name')); ?>
  <div id="user">
    <button class="topMenu cm-toggle" id="user-panel-toggle" aria-controls="user-panel" aria-expanded="false">
      <span id="user-common-name">
        <?php print $userCN; ?>
      </span>
      <em class="material-icons icon-adjust">person</em>
      <em class="material-icons drop-arrow">arrow_drop_down</em>
    </button>
    <!-- Account Dropdown -->
    <div id="user-panel" style="display: none;">
      <div id="logout-in-panel">
        <?php
          $args = array('controller' => 'auth',
            'action'     => 'logout',
            'plugin'     => false);
          print $this->Html->link(_txt('op.logout') . ' <em class="material-icons" aria-hidden="true">logout</em>',
            $args, array('escape'=>false, 'class' => 'btn'));
        ?>
      </div>
      <div id="user-panel-user-info">
        <em class="material-icons">person</em>
        <div id="user-panel-cn"><?php print $userCN; ?></div>
        <div id="user-panel-id"><?php print $this->Session->read('Auth.User.username'); ?></div>
      </div>
      <?php
        // Profile
        if(isset($permissions['menu']['coprofile']) && $permissions['menu']['coprofile']) {
          // Link to identity for self service.
          if(isset($cur_co)) {
            foreach($menuContent['cos'] as $co) {
              // Only display a profile link for the current CO
              if($co['co_id'] == $cur_co['Co']['id']) {

                print '<div id="co-profile-buttons">';

                // The person must have an Active/GracePeriod status and at least
                // one defined role.
                if(isset($co['co_person']['status'])
                  && ($co['co_person']['status'] == StatusEnum::Active
                    || $co['co_person']['status'] == StatusEnum::GracePeriod)
                  && !empty($co['co_person']['CoPersonRole'])
                ) {
                  $args = array(
                    'plugin' => '',
                    'controller' => 'co_people',
                    'action' => 'canvas',
                    $co['co_person_id']
                  );
                  print $this->Html->link('<em class="material-icons" aria-hidden="true">account_circle</em>' . _txt('me.profile.for', array($co['co_name'])), $args,
                    array('escape' => false, 'id' => 'co-profile-link', 'class' => 'co-profile-button co-raised-button btn btn-default'));
                }

                // Group Memberships
                // Show the groups link too, if permissions allow
                if(isset($permissions['menu']['cogroups']) && $permissions['menu']['cogroups']
                   && !empty($co['co_person_id'])) {
                  $args = array(
                    'plugin' => '',
                    'controller' => 'co_groups',
                    'action' => 'select',
                    'copersonid' => $co['co_person_id'],
                    'co' => $co['co_id'],
                    'search.member' => '1',
                    'search.owner' => '1'
                  );
                  print $this->Html->link('<em class="material-icons" aria-hidden="true">group_work</em>' . _txt('op.grm.my.groupmems'), $args,
                    array('escape' => false, 'id' => 'co-mygroups-link', 'class' => 'co-profile-button co-raised-button btn btn-default'));
                }

                // UnResolved Notifications
                if(!empty($vv_co_person_id_notifications)) {
                  $args = array(
                    'controller' => 'co_notifications',
                    'action' => 'index',
                    'recipientcopersonid' => $vv_co_person_id_notifications,
                    'sort' => 'created',
                    'direction' => 'desc',
                    'search.status' => NotificationStatusEnum::PendingResolution,
                    'op' => 'search',
                  );
                  print $this->Html->link(
                          '<em class="material-icons" aria-hidden="true">mark_unread_chat_alt</em>' . _txt('op.not.unresolved', array($vv_my_notification_count_resolve)),
                          $args,
                          array(
                            'escape' => false,
                            'id' => 'unresolved-notifications-link',
                            'class' => 'co-profile-button co-raised-button btn btn-default')
                  );
                }

                print '</div>';
              }
            }
          }
        }

        // Enrollment flows
        if(isset($cur_co)) {
          // Convert the list of COs with enrollment flows defined into a more useful format
          $efcos = Hash::extract($vv_enrollment_flow_cos, '{n}.CoEnrollmentFlow.co_id');

          if(in_array($cur_co['Co']['id'], $efcos)) {
            // If we have enrollment flows, display them directly
            if(!empty($menuContent['flows']) && ($permissions['menu']['createpetition'] || $permissions['menu']['invite'])) {
              print '<div id="user-panel-flows-container">';
              print '<h2>' . _txt('me.flows') . '</h2>';
              print '<ul id="user-panel-flows" class="user-panel-list">';
              foreach($menuContent['flows'] as $flow) {
                print '<li>';
                print $this->Html->link(filter_var($flow['CoEnrollmentFlow']['name'], FILTER_SANITIZE_SPECIAL_CHARS),
                  array(
                    'controller' => 'co_petitions',
                    'action' => 'start',
                    'coef' => $flow['CoEnrollmentFlow']['id']
                  )
                );
                print '</li>';
              }
              print '</ul>';
              print '</div>';
            }
          }
        }

        // Plugin submenus
        // This rendering is a bit different from how render_plugin_menus() does it...
        // Each plugin will appear by name (e.g. "Announcements") with all appropriate 
        // COs listed beneath it. Each CO title will link to the plugin within that CO.
        if(!empty($menuContent['plugins'])) {
          $userPluginsExist = false;
          foreach(array_keys($menuContent['plugins']) as $plugin) {
            if(isset($menuContent['plugins'][$plugin]['coperson'])) {
              $userPluginsExist = true;
              break;
            }
          }
          if($userPluginsExist) {
            print '<div id="user-panel-plugins-container">';
            print '<h2>' . _txt('me.plugins') . '</h2>';
            print '<ul id="user-panel-plugins" class="user-panel-list">';
            foreach(array_keys($menuContent['plugins']) as $plugin) {
              if(isset($menuContent['plugins'][$plugin]['coperson'])) {
                foreach(array_keys($menuContent['plugins'][$plugin]['coperson']) as $label) {
                  print '<li>';
                    print '<span class="user-plugin-label">' . $label . '</span>';
                    print '<ul>';
  
                    foreach($menuContent['cos'] as $co) {
                      if(empty($co['co_person_id'])) {
                        continue;
                      }
  
                      // Get the plugin link array
                      $args = $menuContent['plugins'][$plugin]['coperson'][$label];
  
                      // Always include the co_person_id and the co_id
                      $args['copersonid'] = $co['co_person_id'];
                      $args['co'] = $co['co_id'];
                      
                      // Generate the plugin path if $args['plugin'] hasn't been passed 
                      // (it can be passed as empty '' to allow for non-plugin URLs).
                      if(!isset($args['plugin'])) {
                        $args['plugin'] = Inflector::underscore($plugin);  
                      }
                      
                      // Generate the link
                      print '<li>' . $this->Html->link($co['co_name'], $args) . "</li>\n";
                    }
  
                    print '</ul>';
                  print '</li>';
                }
              }
            }
            print "</ul></div>";
          }
        }
      ?>

      <!-- Org Ids -->
      <?php if(!empty($menuContent['orgIDs'])): ?>
        <div id="panel-orgid-container">
          <h2><?php print _txt('me.orgids'); ?></h2>
          <!-- Org Identity Data -->
          <ul id="panel-orgid" class="user-panel-list">
            <?php foreach($menuContent['orgIDs'] as $orgID): ?>
              <?php
                // Set the link text
                $orgIDLinkText = $userCN;
                if (!empty($orgID['orgID_o'])) {
                  $orgIDLinkText = $orgID['orgID_o'];
                } elseif (!empty($orgID['orgID_email'][0]['mail'])) {
                  $orgIDLinkText = $orgID['orgID_email'][0]['mail']; // XXX using the first one found...
                } elseif (!empty($orgID['orgID_title'])) {
                  $orgIDLinkText = $orgID['orgID_title'];
                } elseif (!empty($orgID['orgID_ou'])) {
                  $orgIDLinkText = $orgID['orgID_ou'];
                }
                ?>
              <li class="panel-orgid-ids">
                <?php
                  // link to the Org ID view.
                  print $this->Html->link($orgIDLinkText,
                    array(
                      'plugin'     => null,
                      'controller' => 'org_identities',
                      'action' => ('view'),
                      $orgID['orgID_id']
                    )
                  );
                ?>
              </li>
            <?php endforeach; ?>
          </ul>
        </div><!-- panel-orgid -->
      <?php endif; ?>

      <?php
        // Last login
        print $this->element("lastlogin");
      ?>
    </div>
  </div>
<?php endif ?>

<?php if(!isset($noLoginLogout) || !$noLoginLogout) : ?>
  <?php // Print the login button
    if($this->Session->check('Auth.User') == NULL) {
      $args = array('controller' => 'auth',
                    'action'     => 'login',
                    'plugin'     => false
                   );
      print $this->Html->link(_txt('op.login') . ' <em class="material-icons" aria-hidden="true">login</em>',
            $args, array('escape'=>false, 'id' => 'login', 'class' => ''));
    }
  ?>
<?php endif; ?>


