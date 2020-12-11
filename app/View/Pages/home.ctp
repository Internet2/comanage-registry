<?php
/**
 * COmanage Registry Home Layout
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

  if($this->Session->check('Auth.User')) {
    $userInfo = $this->Session->read('Auth.User');
  } else {
    $userInfo = null;
  }
  
  // Figure out if we have an error to display
  $err = "";
  
  if(!empty($userInfo['cos'])) {
    // Make sure there is at least one active or grace period role
    $active = false;
    
    foreach($userInfo['cos'] as $co) {
      if(!empty($co['co_person']['CoPersonRole'])) {
        foreach($co['co_person']['CoPersonRole'] as $r) {
          if($r['status'] == StatusEnum::Active
             || $r['status'] == StatusEnum::GracePeriod) {
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
  <div class="co-info-topbox">
    <em class="material-icons error">info</em>
    <?php print $err; ?>
  </div>
<?php else: // $err ?>
<div id="fpDashboard">
  <?php
    // Render some text according to the user's current state
    if(!empty($userInfo['cos'])) {
      // Valid user
      print $this->element("lastlogin");
      print '<h1 class="firstPrompt">' . _txt('op.home.select', array(_txt('coordinate'))) . '</h1>';

      // Load the list of COs
      if($menuContent['cos']) {
        $cos = $this->viewVars['menuContent']['cos'];
      } else {
        $cos = array();
      }

      print '<h2>' . _txt('op.home.collabs') . '</h2>';
      print '<div id="fpCoList" class="co-grid co-grid-with-header container">';
      print '<div class="row co-grid-header">';
      print '  <div class="col">' . _txt('fd.name') . '</div>';
      print '  <div class="col">' . _txt('fd.desc') . '</div>';
      print '</div>';

      //loop over each CO
      if(count($cos) > 0) {
        $i = 0;
        foreach($cos as $menuCoName => $menuCoData) {
          $collabMenuCoId = $menuCoData['co_id'];

          if((!isset($menuCoData['co_person']['status'])
              || ($menuCoData['co_person']['status'] != StatusEnum::Active
                && $menuCoData['co_person']['status'] != StatusEnum::GracePeriod)
              || empty($menuCoData['co_person']['CoPersonRole']))
            && !$permissions['menu']['admin']) {
            // Don't render this CO, the person is not an active member (or a CMP admin)
            continue;
          }

          print '<div class="row co-row spin">';
          print '<div class="col collab-name">';
          // We use $menuCoData here and not $menuCoName because the former will indicate
          // 'Not a Member' for CMP Admins (where they are not a member of the CO)
          $args = array();
          $args['plugin'] = null;
          $args['controller'] = 'co_dashboards';
          $args['action'] = 'dashboard';
          $args['co'] = $collabMenuCoId;

          print $this->Html->link($menuCoData['co_name'], $args, array('class' => 'co-link'));

          print '</div><div class="col collab-desc">';

          if (!empty($menuCoData['co_person']['Co']['description'])) {
            print filter_var($menuCoData['co_person']['Co']['description'],FILTER_SANITIZE_SPECIAL_CHARS);
          } elseif (!empty($menuCoData['co_desc'])) {
            print filter_var($menuCoData['co_desc'],FILTER_SANITIZE_SPECIAL_CHARS);
          }
          print '</div></div>';
          $i++;
        }

  // Allow the whole div to be clicked:
  ?>
  <script type="text/javascript">
    $(function() {
      $("#fpCoList .co-row").click(function () {
        location.href = $(this).find(".collab-name > a.co-link").attr('href');
      });
    });
  </script>
  <?php

      } else {
        print '<p>' . _txt('op.home.no.collabs') .  '</p>';
      }

      print '</div>';
    } elseif(!$userInfo) {
      // Please login
      print '<h1 class="loginMsg">' . _txt('op.home.login', array(_txt('coordinate'))) . '</h1>';
      // Print the login button
      $buttonClasses = "btn btn-primary focusFirst";
      $args = array('controller' => 'auth',
        'action'     => 'login',
        'plugin'     => false
      );
      print ' <div id="welcome-login">';
      print $this->Html->link(_txt('op.login') . ' <span class="fa fa-sign-in"></span>',
        $args, array('escape'=>false, 'id' => 'welcome-login-button', 'class' => $buttonClasses));
      print '</div>';
    }

  ?>
</div>
<?php endif; // $err ?>