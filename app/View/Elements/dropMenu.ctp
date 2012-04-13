<?php
/*
 * COmanage Registry Dropdown Menu Bar
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

// Load the list of COs
$cos = $this->viewVars['menuContent']['cos'];
?>

<div class="menubar">
  <div class="label">
    <?php print _txt('me.label'); ?>
  </div>
  <ul class="sf-menu">

  <!-- Organizations Dropdown -->
    <li class="dropMenu">
      <a>
        <span>
          <?php print _txt('ct.organizations.pl'); ?>
        </span>
        <span class="ui-icon ui-icon-carat-1-s"></span>
      </a>
      <?php
        //loop for each CO
        if(count($cos) > 0) {
          print "<ul>";
          foreach ($cos as $menuCoId => $menuCoName) {
            print '<li>';
              print '<a>' . $menuCoName . '</a>
                     <span class="sf-sub-indicator"> »</span>';
              print '<ul>';
                if(isset($permissions['menu']['orgidentities']) && $permissions['menu']['orgidentities']) {
                  print "<li>";
                    $args = array(
                      'controller' => 'org_identities',
                      'action' => 'index',
                      'co' => $menuCoId
                    );
                    print $this->Html->link(_txt('ct.org_identities.pl'), $args);
                  print "</li>";
                }

                if(isset($permissions['menu']['cos']) && $permissions['menu']['cos']) {
                  print "<li>";
                    $args = array(
                    'controller' => 'co_people',
                    'action' => 'index',
                    'co' => $menuCoId
                    );
                    print $this->Html->link(_txt('me.population'), $args);
                  print "</li>";
                }

                if(isset($permissions['menu']['petitions']) && $permissions['menu']['petitions']) {
                  print "<li>";
                    $args = array(
                    'controller' => 'co_petitions',
                    'action' => 'index',
                    'co' => $menuCoId
                    );
                    print $this->Html->link(_txt('ct.co_petitions.pl'), $args);
                  print "</li>";
                }

                if(isset($permissions['menu']['extattrs']) && $permissions['menu']['extattrs']) {
                  print "<li>";
                    $args = array(
                    'controller' => 'co_extended_attributes',
                    'action' => 'index',
                    'co' => $menuCoId
                    );
                    print $this->Html->link(_txt('ct.co_extended_attributes.pl'), $args);
                  print "</li>";
                }

                if(isset($permissions['menu']['coef']) && $permissions['menu']['coef']) {
                  print "<li>";
                    $args = array(
                    'controller' => 'co_enrollment_flows',
                    'action' => 'index',
                    'co' => $menuCoId
                    );
                    print $this->Html->link(_txt('ct.co_enrollment_flows.pl'), $args);
                  print "</li>";
                }

                if(isset($permissions['menu']['cous']) && $permissions['menu']['cous']) {
                  print "<li>";
                    $args = array(
                    'controller' => 'cous',
                    'action' => 'index',
                    'co' => $menuCoId
                    );
                    print $this->Html->link(_txt('ct.cous.pl'), $args);
                  print "</li>";
                }

                if(isset($permissions['menu']['cogroups']) && $permissions['menu']['cogroups']) {
                  print "<li>";
                    $args = array(
                    'controller' => 'co_groups',
                    'action' => 'index',
                    'co' => $menuCoId
                    );
                    print $this->Html->link(_txt('ct.co_groups.pl'), $args);
                  print "</li>";
                }
              print "</ul>";
            }
            print "</li>";
          print "</ul>";
        }
      ?>
    </li>

    <!-- Platform Dropdown -->
    <li class="dropMenu">
      <a>
        <span>
          <?php print _txt('me.platform');?>
        </span>
        <span class="ui-icon ui-icon-carat-1-s"></span>
      </a>
      <ul>
        <li>
          <?php
            $params = array('controller' => 'cos',
                            'action'     => 'index'
                           );
            print $this->Html->link(_txt('ct.cos.pl'), $params);
          ?>
        </li>
        <li>
          <?php
            $params = array('controller' => 'organizations',
                            'action'     => 'index'
                           );
            print $this->Html->link(_txt('ct.organizations.pl'), $params);
          ?>
        </li>
        <li>
          <?php
            $params = array('controller' => 'cmp_enrollment_configurations',
                            'action'     => 'select'
                           );
            print $this->Html->link(_txt('ct.cmp_enrollment_configurations.pl'), $params);
          ?>
        </li>
      </ul>
    </li>

    <!-- Account Dropdown -->
    <li class="dropMenu">
      <a>
        <span>
          <?php print _txt('me.account') ?>
        </span>
        <span class="ui-icon ui-icon-carat-1-s"></span>
      </a>
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

            if(isset($permissions['menu']['orgprofile']) && $permissions['menu']['orgprofile']) {
              // A user can have more than one org identity (keyed to their COs) if pooling is
              // disabled, so loop through as appropriate.

              $orgIdentities = $this->Session->read('Auth.User.org_identities');

              foreach ($orgIdentities as $o) {
                // Not always available throughout the site so take backup set by menuContent in app_controller
                if(!isset($org_identities[0]['OrgIdentity']['o']))
                  $homeName = $menuContent['orgName'];
                else
                  $homeName = $org_identities[0]['OrgIdentity']['o'];

                if(isset($o['co_id'])) {
                  // Figure out the name of the CO
                  $coName = "?";

                  foreach ($mycos as $co) {
                    if($co['co_id'] == $o['co_id']) {
                      $coName = $co['co_name'];
                      break;
                    }
                  }
                  print "<li>";
                    $args = array(
                      'controller' => 'org_identities',
                      'action' => 'view',
                      $o['org_id'],
                      'co' => $o['co_id']
                    );
                    print $this->Html->link(_txt('me.for') . $homeName, $args);
                  print "</li>";
                } else {
                  print "<li>";
                    $args = array(
                      'controller' => 'org_identities',
                      'action' => 'view',
                      $o['org_id']
                    );
                    print $this->Html->link(_txt('me.for') . $homeName, $args);
                  print "</li>";
                }
              }
            }
            foreach ($mycos as $co) {
              print "<li>";
                $args = array(
                  'controller' => 'co_people',
                  'action' => 'edit',
                  $co['co_person_id'],
                  'co' => $co['co_id']
                );
                print $this->Html->link(_txt('me.for') . $co['co_name'], $args);
              print "</li>";
            }
            print '</ul>
                </li>';
          ?>

          <?php // Demographics submenu
            print '<li> 
                     <a href="#">'._txt('ct.co_nsf_demographics.pl').'</a>
                     <span class="sf-sub-indicator"> »</span>
                     <ul>';

            foreach ($mycos as $co) {
              print "<li>";
                $args = array(
                  'controller' => 'co_nsf_demographics',
                  'action' => 'editself',
                  'co' => $co['co_id']
                );
                print $this->Html->link(_txt('me.for'). $co['co_name'], $args);
              print "</li>";
            }

            print '  </ul>
                   </li>';
          }
        ?>

        <li>
          <a href="#">
            <?php print _txt('me.changepassword'); // XXX Needs to be implemented ?>
          </a>
        </li>
      </ul>
    </li>
  </ul>
</div>
