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

// Convert the list of COs with enrollment flows defined into a more useful format
$efcos = Hash::extract($vv_enrollment_flow_cos, '{n}.CoEnrollmentFlow.co_id');
?>

<ul id="main-menu" class="metismenu">

  <?php
    // Output the CO submenus (People, Groups, Configuration) if a CO is selected
    if(!empty($cur_co['Co']['id'])) {
      $menuCoId = $cur_co['Co']['id'];

      // People Menu
      print '<li class="peopleMenu">';
      print '<a class="menuTop mdl-js-ripple-effect" aria-expanded="false" href="#">';
      print '<em class="material-icons" aria-hidden="true">person</em>';
      print '<span class="menuTitle">' . _txt('me.people') . '</span>';
      print '<span class="fa arrow fa-fw"></span>';
      print '<span class="mdl-ripple"></span>';
      print '</a>';
      print '<ul aria-expanded="false" class="collapse">';

      if (isset($permissions['menu']['cos']) && $permissions['menu']['cos']) {
        print '<li class="mdl-js-ripple-effect">';
        $args = array();
        $args['plugin'] = null;
        $args['controller'] = 'co_people';
        $args['action'] = 'index';
        $args['co'] = $menuCoId;

        print $this->Html->link(_txt('me.population'), $args);
        print '<span class="mdl-ripple"></span>';
        print "</li>";

        if(!empty($permissions['menu']['admincous'])) {
          foreach($permissions['menu']['admincous'] as $couid => $couname) {
            print '<li class="mdl-js-ripple-effect">';
            $args = array();
            $args['plugin'] = null;
            $args['controller'] = 'co_people';
            $args['action'] = 'index';
            $args['co'] = $menuCoId;
            $args['Search.couid'] = $couid;

            print $this->Html->link(_txt('me.population.cou', array($couname)), $args);
            print '<span class="mdl-ripple"></span>';
            print "</li>";

          }
        }
      }

      if (isset($permissions['menu']['orgidentities']) && $permissions['menu']['orgidentities']) {
        $args = array();
        $args['plugin'] = null;
        $args['controller'] = 'org_identities';
        $args['action'] = 'index';

        if (!$pool_org_identities) {
          $args['co'] = $menuCoId;
        }

        print '<li class="mdl-js-ripple-effect">';
        print $this->Html->link(_txt('ct.org_identities.pl'), $args);
        print '<span class="mdl-ripple"></span>';
        print "</li>";
      }

      if (in_array($menuCoId, $efcos)) {
        // Enrollment Flows enabled
        if (isset($permissions['menu']['createpetition']) && $permissions['menu']['createpetition']) {
          print '<li class="mdl-js-ripple-effect">';
          $args = array();
          $args['plugin'] = null;
          $args['controller'] = 'co_enrollment_flows';
          $args['action'] = 'select';
          $args['co'] = $menuCoId;

          print $this->Html->link(_txt('op.enroll'), $args);
          print '<span class="mdl-ripple"></span>';
          print "</li>";
        }
        
        if (isset($permissions['menu']['petitions']) && $permissions['menu']['petitions']) {
          print '<li class="mdl-js-ripple-effect">';
          $args = array();
          $args['plugin'] = null;
          $args['controller'] = 'co_petitions';
          $args['action'] = 'index';
          $args['co'] = $menuCoId;
          $args['sort'] = 'CoPetition.created';
          $args['direction'] = 'desc';
          $args['search.status'][] = StatusEnum::PendingApproval;
          $args['search.status'][] = StatusEnum::PendingConfirmation;

          print $this->Html->link(_txt('ct.co_petitions.pl'), $args);
          print '<span class="mdl-ripple"></span>';
          print "</li>";
        }
      } else {
        // Default enrollment
        if (isset($permissions['menu']['invite']) && $permissions['menu']['invite']) {
          print '<li class="mdl-js-ripple-effect">';
          $args = array();
          $args['plugin'] = null;
          $args['controller'] = 'org_identities';
          $args['action'] = 'find';
          $args['co'] = $menuCoId;

          print $this->Html->link(_txt('op.inv'), $args);
          print '<span class="mdl-ripple"></span>';
          print "</li>";
        }
      }

      if(!empty($menuContent['plugins'])) {
        render_plugin_menus($this->Html, $menuContent['plugins'], 'copeople', $menuCoId);
      }

      print "</ul>";
      print "</li>";

      // Groups Menu
      if (isset($permissions['menu']['cogroups']) && $permissions['menu']['cogroups']) {
        print '<li class="groupMenu">';

        print '<a class="menuTop mdl-js-ripple-effect" aria-expanded="false" href="#">';
        //print '<span class="fa fa-users fa-fw"></span>';
        print '<em class="material-icons" aria-hidden="true">group</em>';
        print '<span class="menuTitle">' . _txt('ct.co_groups.pl') . '</span>';
        print '<span class="fa arrow fa-fw"></span>';
        print '<span class="mdl-ripple"></span>';
        print '</a>';
        print '<ul aria-expanded="false" class="collapse">';

        print '<li class="mdl-js-ripple-effect">';
        $args = array();
        $args['plugin'] = null;
        $args['controller'] = 'co_groups';
        $args['action'] = 'select';
        $args['co'] = $menuCoId;
        print $this->Html->link(_txt('op.grm.my.groups'), $args);
        print '<span class="mdl-ripple"></span>';
        print "</li>";

        print '<li class="mdl-js-ripple-effect">';
        $args = array();
        $args['plugin'] = null;
        $args['controller'] = 'co_groups';
        $args['action'] = 'index';
        $args['co'] = $menuCoId;
        print $this->Html->link(_txt('ct.co_all_groups'), $args);
        print '<span class="mdl-ripple"></span>';
        print "</li>";

        // Plugins
        if (!empty($menuContent['plugins'])) {
          render_plugin_menus($this->Html, $menuContent['plugins'], 'cogroups', $menuCoId);
        }

        print "</ul>";
        print "</li>";
      }
      
      // Departments Menu
      if($permissions['menu']['codepartments']) {
        print '<li class="configMenu">';

        $linkContent = '<em class="material-icons" aria-hidden="true">business</em><span class="menuTitle">' . _txt('ct.co_departments.pl') .
          '</span><span class="mdl-ripple"></span>';

        $args = array();
        $args['plugin'] = null;
        $args['controller'] = 'co_departments';
        $args['action'] = 'index';
        $args['co'] = $menuCoId;

        print $this->Html->link($linkContent, $args, array('class' => 'mdl-js-ripple-effect', 'escape' => false,));

        print "</li>";
      }
      
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
          
          print '<li class="serviceMenu">';
          print '<a class="menuTop mdl-js-ripple-effect" aria-expanded="false" href="#">';
          print '<em class="material-icons" aria-hidden="true">apps</em>';
          print '<span class="menuTitle">' . _txt('ct.co_services.pl') . '</span>';
          print '<span class="fa arrow fa-fw"></span>';
          print '<span class="mdl-ripple"></span>';
          print '</a>';
          print '<ul aria-expanded="false" class="collapse">';
          
          // COU IDs with a service portal visible to this user
          $couIds = array_keys($services);
          sort($couIds);
          
          foreach($couIds as $sCouId) {
            print '<li class="mdl-js-ripple-effect">';
            $args = array();
            $args['plugin'] = null;
            $args['controller'] = 'co_services';
            $args['action'] = 'portal';
            if($sCouId == -1) {
              // CO Portal
              $args['co'] = $menuCoId;
              print $this->Html->link($cur_co['Co']['name'], $args);
            } else {
              $args['cou'] = $sCouId;
              print $this->Html->link($menuContent['cous'][$sCouId], $args);
            }
    
            print '<span class="mdl-ripple"></span>';
            print "</li>";
          }
          
          print '</ul>';
        } else {
          // Single entry, so render a link straight to the appropriate dashboard.
          
          // Everything has the same COU ID, so we can just look at the first entry.
          $sCouId = key($services);
          
          print '<li class="serviceMenu">';
  
          $linkContent = '<em class="material-icons" aria-hidden="true">apps</em><span class="menuTitle">' . _txt('ct.co_services.pl') .
            '</span><span class="mdl-ripple"></span>';
  
          $args = array();
          $args['plugin'] = null;
          $args['controller'] = 'co_services';
          $args['action'] = 'portal';
          if($sCouId > -1) {
            $args['couid'] = $sCouId;
          } else {
            $args['co'] = $menuCoId;
          }
          print $this->Html->link($linkContent, $args, array('class' => 'mdl-js-ripple-effect', 'escape' => false));
  
          print "</li>";
        }
        
        // Plugins
        /* XXX These need a home
        if(!empty($menuContent['plugins'])) {
          render_plugin_menus($this->Html, $menuContent['plugins'], 'coservices', $menuCoId);
        } */
      }

      // Jobs Menu
      if ($permissions['menu']['cojobs']) {
        print '<li class="configMenu">';

        $linkContent = '<em class="material-icons" aria-hidden="true">assignment</em><span class="menuTitle">' . _txt('ct.co_jobs.pl') .
          '</span><span class="mdl-ripple"></span>';

        $args = array();
        $args['plugin'] = null;
        $args['controller'] = 'co_jobs';
        $args['action'] = 'index';
        $args['co'] = $menuCoId;

        print $this->Html->link($linkContent, $args, array('class' => 'mdl-js-ripple-effect', 'escape' => false,));

        print "</li>";
      }
      
      // Configuration Menu
      if ($permissions['menu']['coconfig']) {
        print '<li class="configMenu">';

        $linkContent = '<em class="material-icons" aria-hidden="true">build</em><span class="menuTitle">' . _txt('me.configuration') .
          '</span><span class="mdl-ripple"></span>';

        $args = array();
        $args['plugin'] = null;
        $args['controller'] = 'co_dashboards';
        $args['action'] = 'configuration';
        $args['co'] = $menuCoId;

        print $this->Html->link($linkContent, $args, array('class' => 'mdl-js-ripple-effect', 'escape' => false,));

        print "</li>";
      }
    }

    // Platform Menu
    if(!empty($permissions['menu']['admin']) && $permissions['menu']['admin']) {
      print '<li class="platformMenu">';
      print '<a href="#" class="menuTop mdl-js-ripple-effect" aria-expanded="false">';
      print '<em class="material-icons" aria-hidden="true">settings</em>';
      print '<span class="menuTitle">' . _txt('me.platform') . '</span>';
      print '<span class="fa arrow fa-fw"></span>';
      print '<span class="mdl-ripple"></span>';
      print '</a>';
      print '<ul aria-expanded="false" class="collapse">';

      print '<li class="mdl-js-ripple-effect">';
      $args = array();
      $args['plugin'] = null;
      $args['controller'] = 'api_users';
      $args['action'] = 'index';
      print $this->Html->link(_txt('ct.api_users.pl'), $args);
      print '<span class="mdl-ripple"></span>';
      print '</li>';

      if($pool_org_identities) {
        print '<li class="mdl-js-ripple-effect">';
        $args = array();
        $args['plugin'] = null;
        $args['controller'] = 'attribute_enumerations';
        $args['action'] = 'index';

        print $this->Html->link(_txt('ct.attribute_enumerations.pl'), $args);
        print '<span class="mdl-ripple"></span>';
        print '</li>';
      } // pool_org_identities

      print '<li class="mdl-js-ripple-effect">';
      $args = array();
      $args['plugin'] = null;
      $args['controller'] = 'cmp_enrollment_configurations';
      $args['action'] = 'select';

      print $this->Html->link(_txt('ct.cmp_enrollment_configurations.pl'), $args);
      print '<span class="mdl-ripple"></span>';
      print '</li>';

      print '<li class="mdl-js-ripple-effect">';
      $args = array();
      $args['plugin'] = null;
      $args['controller'] = 'cos';
      $args['action'] = 'index';

      print $this->Html->link(_txt('ct.cos.pl'), $args);
      print '<span class="mdl-ripple"></span>';
      print '</li>';

      print '<li class="mdl-js-ripple-effect">';
      $args = array();
      $args['plugin'] = null;
      $args['controller'] = 'navigation_links';
      $args['action'] = 'index';

      print $this->Html->link(_txt('ct.navigation_links.pl'), $args);
      print '<span class="mdl-ripple"></span>';
      print '</li>';

      if($pool_org_identities) {
        print '<li class="mdl-js-ripple-effect">';
        // If org identities are pooled, only CMP admins can define sources
        $args = array();
        $args['plugin'] = null;
        $args['controller'] = 'org_identity_sources';
        $args['action'] = 'index';

        print $this->Html->link(_txt('ct.org_identity_sources.pl'), $args);
        print '<span class="mdl-ripple"></span>';
        print '</li>';
      } // pool_org_identities

      // Plugins
      if(!empty($menuContent['plugins'])) {
        render_plugin_menus($this->Html, $menuContent['plugins'], 'cmp');
      }

      print '</ul>';
      print '</li>';
    }

    // Collaborations Menu
    print '<li class="collabMenu">';

    $linkContent = '<em class="material-icons" aria-hidden="true">transfer_within_a_station</em><span class="menuTitle">' . _txt('me.collaborations') .
      '</span><span class="mdl-ripple"></span>';

    print $this->Html->link($linkContent, '/', array('class' => 'mdl-js-ripple-effect', 'escape' => false,));

    print "</li>";

    // Plugins
    if (!empty($menuContent['plugins'])) {
      render_plugin_menus($this->Html, $menuContent['plugins'], 'cos');
    }
  ?>

</ul>
