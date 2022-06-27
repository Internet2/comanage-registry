<?php
/*
 * COmanage Registry Dropdown Menu Bar
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
 
// Load the list of COs
if($menuContent['cos']) {
  $cos = $this->viewVars['menuContent']['cos'];
} else {
  $cos = array();
}

$efcos = array();

if(!empty($vv_enrollment_flow_cos)) {
  // Convert the list of COs with enrollment flows defined into a more useful format
  $efcos = Hash::extract($vv_enrollment_flow_cos, '{n}.CoEnrollmentFlow.co_id');
}

// Determine if we have an expanded menu selected but do not expand a menu if the menu drawer is half-closed.
// Currently this is used only for the two expandable menus in the Main Menu (People and Platform).
$selectedMenu = "";
$currentMenu = "";
$drawerState = "open";
if(!empty($vv_app_prefs['uiDrawerState'])) {
  $drawerState = filter_var($vv_app_prefs['uiDrawerState'],FILTER_SANITIZE_STRING);
}
if(!empty($vv_app_prefs['uiMainMenuSelectedParentId']) && $drawerState != 'half-closed') {
  $selectedMenu = filter_var($vv_app_prefs['uiMainMenuSelectedParentId'],FILTER_SANITIZE_STRING);
}
?>

<ul id="main-menu" class="metismenu">

  <?php
    // Output the CO submenus (People, Groups, Configuration) if a CO is selected
    if(!empty($cur_co['Co']['id'])) {
      $menuCoId = $cur_co['Co']['id'];

      // People Menu
      $currentMenu = 'peopleMenu';
      if(isset($permissions['menu']['cos']) && $permissions['menu']['cos']) {
        print '<li id="peopleMenu" class="co-expandable-menu-item' . ($selectedMenu == $currentMenu ? " active" : "") . '">';
        print '<a class="menuTop" title="' . _txt('me.people') . '" aria-expanded="' . ($selectedMenu == $currentMenu ? "true" : "false") . '" href="#">';
        print '<em class="material-icons" aria-hidden="true">person</em>';
        print '<span class="menuTitle">' . _txt('me.people') . '</span>';
        print '<em class="material-icons arrow" aria-hidden="true">chevron_left</em>';
        print '</a>';
        print '<ul aria-expanded="' . ($selectedMenu == $currentMenu ? "true" : "false") . '" class="collapse' . ($selectedMenu == $currentMenu ? " in" : "") . '">';
        print '<li>';
        $args = array();
        $args['plugin'] = null;
        $args['controller'] = 'co_people';
        $args['action'] = 'index';
        $args['co'] = $menuCoId;

        print $this->Html->link(_txt('me.population'), $args, array('class' => 'spin'));

        print "</li>";

        if(!empty($permissions['menu']['admincous'])) {
          foreach($permissions['menu']['admincous'] as $couid => $couname) {
            print '<li>';
            $args = array();
            $args['plugin'] = null;
            $args['controller'] = 'co_people';
            $args['action'] = 'index';
            $args['co'] = $menuCoId;
            $args['search.cou'] = $couid;
            $args['op'] = 'search';

            print $this->Html->link(_txt('me.population.cou', array($couname)), $args, array('class' => 'spin'));
    
            print "</li>";

          }
        }

        // XXX The permissions test org ids may be unnecessary now that the entire People section is wrapped in a
        // permissions check for $permissions['menu']['cos']
        if(isset($permissions['menu']['orgidentities']) && $permissions['menu']['orgidentities']) {
          $args = array();
          $args['plugin'] = null;
          $args['controller'] = 'org_identities';
          $args['action'] = 'index';

          if(!$pool_org_identities) {
            $args['co'] = $menuCoId;
          }

          print '<li>';
          print $this->Html->link(_txt('ct.org_identities.pl'), $args, array('class' => 'spin'));
  
          print "</li>";
        }

        if(in_array($menuCoId, $efcos)) {
          // Enrollment Flows enabled
          if(isset($permissions['menu']['createpetition']) && $permissions['menu']['createpetition']) {
            print '<li>';
            $args = array();
            $args['plugin'] = null;
            $args['controller'] = 'co_enrollment_flows';
            $args['action'] = 'select';
            $args['co'] = $menuCoId;

            print $this->Html->link(_txt('op.enroll'), $args, array('class' => 'spin'));
    
            print "</li>";
          }

          if(isset($permissions['menu']['petitions']) && $permissions['menu']['petitions']) {
            print '<li>';
            $args = array();
            $args['plugin'] = null;
            $args['controller'] = 'co_petitions';
            $args['action'] = 'index';
            $args['co'] = $menuCoId;
            $args['sort'] = 'CoPetition.created';
            $args['direction'] = 'desc';
            $args['search.status'] = StatusEnum::PendingApproval;

            print $this->Html->link(_txt('ct.co_petitions.pl'), $args, array('class' => 'spin'));
    
            print "</li>";
          }
          
          if(isset($permissions['menu']['vettingrequests']) && $permissions['menu']['vettingrequests']) {
            print '<li>';
            $args = array();
            $args['plugin'] = null;
            $args['controller'] = 'vetting_requests';
            $args['action'] = 'index';
            $args['co'] = $menuCoId;
            $args['sort'] = 'VettingRequest.created';
            $args['direction'] = 'desc';
            $args['search.status'] = VettingStatusEnum::PendingManual;

            print $this->Html->link(_txt('ct.vetting_requests.pl'), $args, array('class' => 'spin'));
    
            print "</li>";
          }
        } else {
          // Default enrollment
          if(isset($permissions['menu']['invite']) && $permissions['menu']['invite']) {
            print '<li>';
            $args = array();
            $args['plugin'] = null;
            $args['controller'] = 'org_identities';
            $args['action'] = 'find';
            $args['co'] = $menuCoId;

            print $this->Html->link(_txt('op.inv'), $args, array('class' => 'spin'));
    
            print "</li>";
          }
        }

        if(!empty($menuContent['plugins'])) {
          $pluginLinks = retrieve_plugin_menus($menuContent['plugins'], 'copeople', $menuCoId);

          foreach($pluginLinks as $plabel => $pcfg) {
            print '<li>';
            print $this->Html->link($plabel, $pcfg['url'], array('class' => 'spin'));
    
            print '</li>';
          }
        }

        print "</ul>";
        print "</li>";
      }
      // END People Menu

      // Groups Menu
      $currentMenu = 'groupMenu';
      if(isset($permissions['menu']['cogroups']) && $permissions['menu']['cogroups']) {

        print '<li id="groupMenu" class="co-expandable-menu-item' . ($selectedMenu == $currentMenu ? " active" : "") . '">';

        print '<a class="menuTop" title="' . _txt('ct.co_groups.pl') . '" aria-expanded="' . ($selectedMenu == $currentMenu ? "true" : "false") . '" href="#">';
        print '<em class="material-icons" aria-hidden="true">group</em>';
        print '<span class="menuTitle">' . _txt('ct.co_groups.pl') . '</span>';
        print '<em class="material-icons arrow" aria-hidden="true">chevron_left</em>';
        print '</a>';

        print '<ul aria-expanded="' . ($selectedMenu == $currentMenu ? "true" : "false") . '" class="collapse' . ($selectedMenu == $currentMenu ? " in" : "") . '">';

        // Regular Groups (with default filtering)
        print '<li>';
        $args = array();
        $args['plugin'] = null;
        $args['controller'] = 'co_groups';
        $args['action'] = 'index';
        $args['co'] = $menuCoId;
        $args['search.auto'] = 'f'; // filter out automatic groups by default
        $args['search.noadmin'] = '1'; // exclude administration groups by default
        print $this->Html->link(_txt('op.grm.regular'), $args, array('class' => 'spin'));
        print "</li>";

        // System Groups (automatic groups)
        print '<li>';
        $args = array();
        $args['plugin'] = null;
        $args['controller'] = 'co_groups';
        $args['action'] = 'index';
        $args['co'] = $menuCoId;
        $args['search.auto'] = 't'; // show only automatic groups
        print $this->Html->link(_txt('op.grm.system'), $args, array('class' => 'spin'));
        print "</li>";

        // All Groups
        print '<li>';
        $args = array();
        $args['plugin'] = null;
        $args['controller'] = 'co_groups';
        $args['action'] = 'index';
        $args['co'] = $menuCoId;
        print $this->Html->link(_txt('ct.co_all_groups'), $args, array('class' => 'spin'));
        print "</li>";

        // Display My Groups, My Memberships, and Open Groups only to members with an active status in the CO and at
        // least one CoPersonRole. Determine this by investigating the $menuContent. This is identical 
        // to the constraints placed on menuUser for "My Profile" and "My Group Memberships" links.
        if(isset($cur_co)) {
          foreach ($menuContent['cos'] as $co) {
            if ($co['co_id'] == $cur_co['Co']['id']) {
              if(isset($co['co_person']['status'])
                 && ($co['co_person']['status'] == StatusEnum::Active 
                     || $co['co_person']['status'] == StatusEnum::GracePeriod)
                 && !empty($co['co_person']['CoPersonRole'])) {

                // My Groups
                print '<li>';
                $args = array();
                $args['plugin'] = null;
                $args['controller'] = 'co_groups';
                $args['action'] = 'index';
                $args['co'] = $menuCoId;
                $args['search.member'] = '1'; // include groups in which current user is a member
                $args['search.owner'] = '1'; // include groups in which current user is an owner
                print $this->Html->link(_txt('op.grm.my.groups'), $args, array('class' => 'spin'));
                print "</li>";

                // My Memberships
                print '<li>';
                $args = array();
                $args['plugin'] = null;
                $args['controller'] = 'co_groups';
                $args['action'] = 'select';
                $args['copersonid'] = $this->Session->read('Auth.User.co_person_id');
                $args['co'] = $menuCoId;
                $args['search.member'] = '1';
                $args['search.owner'] = '1';
                print $this->Html->link(_txt('op.grm.my.memberships'), $args, array('class' => 'spin'));
                print "</li>";
  
                // Groups I Can Join (Open Groups)
                print '<li>';
                $args = array();
                $args['plugin'] = null;
                $args['controller'] = 'co_groups';
                $args['action'] = 'select';
                $args['copersonid'] = $this->Session->read('Auth.User.co_person_id');
                $args['co'] = $menuCoId;
                $args['search.open'] = 't'; // show only open groups
                print $this->Html->link(_txt('op.grm.join'), $args, array('class' => 'spin'));
                print "</li>";
                
              }
            }
          }
        }

        // Plugins
        if(!empty(retrieve_plugin_menus($menuContent['plugins'], 'cogroups', $menuCoId))) {
          $pluginLinks = retrieve_plugin_menus($menuContent['plugins'], 'cogroups', $menuCoId);

          foreach ($pluginLinks as $plabel => $pcfg) {
            print '<li>';
            print $this->Html->link($plabel, $pcfg['url']);
            print '</li>';
          }

        }

        print "</ul>";
        print "</li>";
      }
      // END Groups Menu
      
      // Departments Menu
      if($permissions['menu']['codepartments']) {
        print '<li id="deptMenu">';

        $linkContent = '<em class="material-icons" aria-hidden="true">business</em><span class="menuTitle">' . _txt('ct.co_departments.pl') .
          '</span>';

        $args = array();
        $args['plugin'] = null;
        $args['controller'] = 'co_departments';
        $args['action'] = 'index';
        $args['co'] = $menuCoId;

        print $this->Html->link($linkContent, $args, array('escape' => false, 'class' => 'spin', 'title' => _txt('ct.co_departments.pl')));

        print "</li>";
      }
      // END Departments Menu
      
      // Organizations Menu
      if($permissions['menu']['organizations']) {
        print '<li id="orgMenu">';

        $linkContent = '<em class="material-icons" aria-hidden="true">account_balance</em><span class="menuTitle">' . _txt('ct.organizations.pl') .
          '</span>';

        $args = array();
        $args['plugin'] = null;
        $args['controller'] = 'organizations';
        $args['action'] = 'index';
        $args['co'] = $menuCoId;

        print $this->Html->link($linkContent, $args, array('escape' => false, 'class' => 'spin', 'title' => _txt('ct.organizations.pl')));

        print "</li>";
      }
      // END Organizations Menu
      
      // Email Lists Menu
      if($permissions['menu']['colists']) {
        print '<li id="emailMenu">';

        $linkContent = '<em class="material-icons" aria-hidden="true">email</em><span class="menuTitle">' . _txt('ct.co_email_lists.pl') .
          '</span>';

        $args = array();
        $args['plugin'] = null;
        $args['controller'] = 'co_email_lists';
        $args['action'] = 'index';
        $args['co'] = $menuCoId;

        print $this->Html->link($linkContent, $args, array('escape' => false, 'class' => 'spin', 'title' => _txt('ct.co_email_lists.pl')));

        print "</li>";
      }
      // END Email Lists Menu

      // Services Menu
      if(!empty($menuContent['services'])) {
        // We either create a single click menu or a nested drop down menu, according to the contents
        // of $menuContent['services']. Start by rekeying on COU ID (using -1 for CO-wide.)
        
        $services = array();
        
        foreach($menuContent['services'] as $s) {
          $sCouId = !empty($s['CoService']['cou_id']) ? $s['CoService']['cou_id'] : -1;
          
          $services[$sCouId][] = $s;
        }
        
        if(count(array_keys($services)) > 1) {
          // Multiple entries, so render a link to each COU.
          
          print '<li id="serviceMenu">';
          print '<a class="menuTop" title="' . _txt('ct.co_services.pl') . '" aria-expanded="false" href="#">';
          print '<em class="material-icons" aria-hidden="true">apps</em>';
          print '<span class="menuTitle">' . _txt('ct.co_services.pl') . '</span>';
          print '<em class="material-icons arrow" aria-hidden="true">chevron_left</em>';
          print '</a>';
          print '<ul aria-expanded="false" class="collapse">';
          
          // COU IDs with a service portal visible to this user
          $couIds = array_keys($services);
          sort($couIds);
          
          foreach($couIds as $sCouId) {
            print '<li>';
            $args = array();
            $args['plugin'] = null;
            $args['controller'] = 'co_services';
            $args['action'] = 'portal';
            if($sCouId == -1) {
              // CO Portal
              $args['co'] = $menuCoId;
              print $this->Html->link($cur_co['Co']['name'], $args, array('class' => 'spin'));
            } else {
              $args['cou'] = $sCouId;
              print $this->Html->link($menuContent['cous'][$sCouId], $args, array('class' => 'spin'));
            }
    
    
            print "</li>";
          }
          
          print '</ul>';
        } else {
          // Single entry, so render a link straight to the appropriate dashboard.
          
          // Everything has the same COU ID, so we can just look at the first entry.
          $sCouId = key($services);
          
          print '<li id="serviceMenu">';
  
          $linkContent = '<em class="material-icons" aria-hidden="true">apps</em><span class="menuTitle">' . _txt('ct.co_services.pl') .
            '</span>';
  
          $args = array();
          $args['plugin'] = null;
          $args['controller'] = 'co_services';
          $args['action'] = 'portal';
          if($sCouId > -1) {
            $args['cou'] = $sCouId;
          } else {
            $args['co'] = $menuCoId;
          }
          print $this->Html->link($linkContent, $args, array('escape' => false, 'class' => 'spin', 'title' => _txt('ct.co_services.pl')));
  
          print "</li>";
        }
      }
      // END Services Menu

      // Jobs Menu
      if($permissions['menu']['cojobs']) {
        print '<li id="jobsMenu">';

        $linkContent = '<em class="material-icons" aria-hidden="true">assignment</em><span class="menuTitle">' . _txt('ct.co_jobs.pl') .
          '</span>';

        $args = array();
        $args['plugin'] = null;
        $args['controller'] = 'co_jobs';
        $args['action'] = 'index';
        $args['co'] = $menuCoId;

        print $this->Html->link($linkContent, $args, array('escape' => false, 'class' => 'spin', 'title' => _txt('ct.co_jobs.pl')));

        print "</li>";
      }
      // END Jobs Menu
      
      // Servers Menu
      if($permissions['menu']['servers']) {
        print '<li id="serversMenu">';

        $linkContent = '<em class="material-icons" aria-hidden="true">storage</em><span class="menuTitle">' . _txt('ct.servers.pl') .
          '</span>';

        $args = array();
        $args['plugin'] = null;
        $args['controller'] = 'servers';
        $args['action'] = 'index';
        $args['co'] = $menuCoId;

        print $this->Html->link($linkContent, $args, array('escape' => false, 'class' => 'spin', 'title' => _txt('ct.servers.pl')));

        print "</li>";
      }
      // END Servers Menu
      
      // Insert plugin menu links, if any
      if(!empty($menuContent['plugins'])) {
        $pluginLinks = retrieve_plugin_menus($menuContent['plugins'], 'comain', $menuCoId);
        
        if(!empty($pluginLinks)) {
          $itemIndex = 0;
          foreach($pluginLinks as $plabel => $pcfg) {
            $itemId = 'pluginMenu' . $itemIndex;
            print '<li id="' . $itemId . '">';

            $linkContent = '<em class="material-icons" aria-hidden="true">' . $pcfg['icon'] .
              '</em><span class="menuTitle">' . $plabel .
              '</span>';

            print $this->Html->link($linkContent, $pcfg['url'], array('escape' => false, 'class' => 'spin', 'title' => $plabel));

            print "</li>";
            $itemIndex++;
          }
        }
      }
      // END Plugins Menu
      
      // Configuration Menu
      if($permissions['menu']['coconfig']) {
        print '<li id="configMenu">';

        $linkContent = '<em class="material-icons" aria-hidden="true">settings</em><span class="menuTitle">' . _txt('me.configuration') .
          '</span>';

        $args = array();
        $args['plugin'] = null;
        $args['controller'] = 'co_dashboards';
        $args['action'] = 'configuration';
        $args['co'] = $menuCoId;

        print $this->Html->link($linkContent, $args, array('escape' => false, 'class' => 'spin', 'title' => _txt('me.configuration')));

        print "</li>";
      }
    }
    // END Configuration Menu

    // Platform Menu
    $currentMenu = "platformMenu";
    if(!empty($permissions['menu']['admin']) && $permissions['menu']['admin']) {
      print '<li id="platformMenu" class="co-expandable-menu-item' . ($selectedMenu == $currentMenu ? " active" : "") . '">';
      print '<a href="#" class="menuTop" title="' . _txt('me.platform') . '" aria-expanded="' . ($selectedMenu == $currentMenu ? "true" : "false") . '">';
      print '<em class="material-icons" aria-hidden="true">build</em>';
      print '<span class="menuTitle">' . _txt('me.platform') . '</span>';
      print '<em class="material-icons arrow" aria-hidden="true">chevron_left</em>';
      print '</a>';
      print '<ul aria-expanded="' . ($selectedMenu == $currentMenu ? "true" : "false") . '" class="collapse' . ($selectedMenu == $currentMenu ? " in" : "") . '">';
      
      if($pool_org_identities) {
        print '<li>';
        $args = array();
        $args['plugin'] = null;
        $args['controller'] = 'attribute_enumerations';
        $args['action'] = 'index';

        print $this->Html->link(_txt('ct.attribute_enumerations.pl'), $args, array('class' => 'spin'));

        print '</li>';
      } // pool_org_identities

      print '<li>';
      $args = array();
      $args['plugin'] = null;
      $args['controller'] = 'cmp_enrollment_configurations';
      $args['action'] = 'select';

      print $this->Html->link(_txt('ct.cmp_enrollment_configurations.pl'), $args, array('class' => 'spin'));
      print '</li>';

      print '<li>';
      $args = array();
      $args['plugin'] = null;
      $args['controller'] = 'cos';
      $args['action'] = 'index';

      print $this->Html->link(_txt('ct.cos.pl'), $args, array('class' => 'spin'));
      print '</li>';

      print '<li>';
      $args = array();
      $args['plugin'] = null;
      $args['controller'] = 'navigation_links';
      $args['action'] = 'index';

      print $this->Html->link(_txt('ct.navigation_links.pl'), $args, array('class' => 'spin'));
      print '</li>';

      if($pool_org_identities) {
        print '<li>';
        // If org identities are pooled, only CMP admins can define sources
        $args = array();
        $args['plugin'] = null;
        $args['controller'] = 'org_identity_sources';
        $args['action'] = 'index';

        print $this->Html->link(_txt('ct.org_identity_sources.pl'), $args, array('class' => 'spin'));

        print '</li>';
      } // pool_org_identities

      // Plugins
      if(!empty($menuContent['plugins'])) {
        $pluginLinks = retrieve_plugin_menus($menuContent['plugins'], 'cmp');
        
        foreach($pluginLinks as $plabel => $pcfg) {
          print '<li>';
          print $this->Html->link($plabel, $pcfg['url'], array('class' => 'spin'));
  
          print '</li>';
        }
      }

      print '</ul>';
      print '</li>';
    }
    // END Platform Menu

    // Collaborations Menu
    // Load the list of COs so we can count them
    if($menuContent['cos']) {
      $cos = $this->viewVars['menuContent']['cos'];
    } else {
      $cos = array();
    }

    // Only show the Collaborations menu if user has access to more than one CO
    if(count($cos) > 1) {

      print '<li id="collabMenu">';

      $linkContent = '<em class="material-icons" aria-hidden="true">transfer_within_a_station</em><span class="menuTitle">' . _txt('me.collaborations') .
        '</span>';

      print $this->Html->link($linkContent, '/', array('escape' => false, 'class' => 'spin', 'title' => _txt('me.collaborations')));

      print "</li>";
    }
    // END Collaborations Menu
  ?>
</ul>
