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
<div class="ui-state-highlight ui-corner-all co-info-topbox">
  <p>
    <span class="ui-icon ui-icon-info co-info"></span>
    <strong><?php print $err; ?></strong>
  </p>
</div>
<?php else: // $err ?>
<div id="fpDashboard">
  <?php
    // Render some text according to the user's current state
    if(!empty($userInfo['cos'])) {
      // Valid user
      print $this->element("lastlogin");
      
      print '<h1>' . _txt('op.home.select', array(_txt('coordinate'))) . '</h1>';

      // Load the list of COs
      if($menuContent['cos']) {
        $cos = $this->viewVars['menuContent']['cos'];
      } else {
        $cos = array();
      }

      print '<table id="fpCoList" class="ui-widget">';
      print '<caption>' . _txt('op.home.collabs') . '</caption>';
      print '<thead>';
      print '  <tr class="ui-widget-header">';
      print '    <th scope="col">' . _txt('fd.name') . '</th>';
      print '    <th scope="col">' . _txt('fd.desc') . '</th>';
      print '  </tr>';
      print '</thead>';

      print '<tbody>';

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

          print '<tr class="line';
          print ($i % 2)+1;
          print '"><td>';
          // We use $menuCoData here and not $menuCoName because the former will indicate
          // 'Not a Member' for CMP Admins (where they are not a member of the CO)
          $args = array();
          $args['plugin'] = null;
          $args['controller'] = 'co_dashboards';
          $args['action'] = 'dashboard';
          $args['co'] = $collabMenuCoId;

          print $this->Html->link($menuCoData['co_name'], $args);
          print '</td><td>';
          if (!empty($menuCoData['co_person']['Co']['description'])) {
            print filter_var($menuCoData['co_person']['Co']['description'],FILTER_SANITIZE_SPECIAL_CHARS);
          } elseif (!empty($menuCoData['co_desc'])) {
            print filter_var($menuCoData['co_desc'],FILTER_SANITIZE_SPECIAL_CHARS);
          }
          print '</td></tr>';
          $i++;
        }
      } else {
        print '<tr class="line1" colspan="2"><td>' . _txt('op.home.no.collabs') .  '</td></tr>';
      }
      
      print '</tbody>';
      print '</table>';
    } elseif(!$userInfo) {
      // Please login
      print '<h1 class="loginMsg">' . _txt('op.home.login', array(_txt('coordinate'))) . '</h1>';
    }
  ?>
</div>
<?php endif; // $err ?>