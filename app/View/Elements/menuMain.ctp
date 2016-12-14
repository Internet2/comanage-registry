<?php
/*
 * COmanage Registry Dropdown Menu Bar
 * Displayed above all pages when logged in
 *
 * Version: $Revision$
 * Date: $Date$
 *
 * Copyright (C) 2012-16 University Corporation for Advanced Internet Development, Inc.
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
      print '<i class="material-icons">person</i>';
      print '<span class="menuTitle">' . _txt('me.people') . '</span>';
      print '<span class="fa arrow fa-fw"></span>';
      print '<span class="mdl-ripple"></span>';
      print '</a>';
      print '<ul aria-expanded="false">';

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
        print '<i class="material-icons">group</i>';
        print '<span class="menuTitle">' . _txt('ct.co_groups.pl') . '</span>';
        print '<span class="fa arrow fa-fw"></span>';
        print '<span class="mdl-ripple"></span>';
        print '</a>';
        print '<ul aria-expanded="false">';

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
      
      // Services Menu
      if(!empty($menuContent['services'])) {
        print '<li class="serviceMenu">';
        
        print '<a class="menuTop mdl-js-ripple-effect" aria-expanded="false" href="#">';
        print '<i class="material-icons">apps</i>';
        print '<span class="menuTitle">' . _txt('ct.co_services.pl') . '</span>';
        print '<span class="fa arrow fa-fw"></span>';
        print '<span class="mdl-ripple"></span>';
        print '</a>';
        print '<ul>';
        
        print "<li>";
        $args = array();
        $args['plugin'] = null;
        $args['controller'] = 'co_services';
        $args['action'] = 'portal';
        $args['co'] = $menuCoId;
        print $this->Html->link(_txt('fd.svc.portal'), $args);
        print "</li>";
        
        foreach($menuContent['services'] as $svc) {
          print '<li class="mdl-js-ripple-effect">';
          print $this->Html->link($svc['CoService']['description'], $svc['CoService']['service_url']);
          print '<span class="mdl-ripple"></span>';
          print "</li>";
        }
        
        // Plugins
        if(!empty($menuContent['plugins'])) {
          render_plugin_menus($this->Html, $menuContent['plugins'], 'coservices', $menuCoId);
        }
        
        print "</ul>";
        print "</li>";
      }

      // Configuration Menu
      if ($permissions['menu']['coconfig']) {
        print '<li class="configMenu">';

        print '<a class="menuTop mdl-js-ripple-effect" aria-expanded="false" href="#">';
        print '<i class="material-icons">build</i>';
        print '<span class="menuTitle">' . _txt('me.configuration') . '</span>';
        print '<span class="fa arrow fa-fw"></span>';
        print '<span class="mdl-ripple"></span>';
        print '</a>';
        print '<ul aria-expanded="false">';
        
        if (isset($permissions['menu']['cosettings']) && $permissions['menu']['cosettings']) {
          print '<li class="mdl-js-ripple-effect">';
          $args = array();
          $args['plugin'] = null;
          $args['controller'] = 'co_settings';
          $args['action'] = 'add';
          $args['co'] = $menuCoId;

          print $this->Html->link(_txt('ct.co_settings.pl'), $args);
          print '<span class="mdl-ripple"></span>';
          print "</li>";
        }

        if (isset($permissions['menu']['coattrenums']) && $permissions['menu']['coattrenums']) {
          print '<li class="mdl-js-ripple-effect">';
          $args = array();
          $args['plugin'] = null;
          $args['controller'] = 'attribute_enumerations';
          $args['action'] = 'index';
          $args['co'] = $menuCoId;

          print $this->Html->link(_txt('ct.attribute_enumerations.pl'), $args);
          print '<span class="mdl-ripple"></span>';
          print "</li>";
        }

        if (isset($permissions['menu']['cous']) && $permissions['menu']['cous']) {
          print '<li class="mdl-js-ripple-effect">';
          $args = array();
          $args['plugin'] = null;
          $args['controller'] = 'cous';
          $args['action'] = 'index';
          $args['co'] = $menuCoId;

          print $this->Html->link(_txt('ct.cous.pl'), $args);
          print '<span class="mdl-ripple"></span>';
          print "</li>";
        }

        if (isset($permissions['menu']['coef']) && $permissions['menu']['coef']) {
          print '<li class="mdl-js-ripple-effect">';
          $args = array();
          $args['plugin'] = null;
          $args['controller'] = 'co_enrollment_flows';
          $args['action'] = 'index';
          $args['co'] = $menuCoId;

          print $this->Html->link(_txt('ct.co_enrollment_flows.pl'), $args);
          print '<span class="mdl-ripple"></span>';
          print "</li>";
        }

        if (isset($permissions['menu']['coxp']) && $permissions['menu']['coxp']) {
          print '<li class="mdl-js-ripple-effect">';
          $args = array();
          $args['plugin'] = null;
          $args['controller'] = 'co_expiration_policies';
          $args['action'] = 'index';
          $args['co'] = $menuCoId;

          print $this->Html->link(_txt('ct.co_expiration_policies.pl'), $args);
          print '<span class="mdl-ripple"></span>';
          print "</li>";
        }

        if (isset($permissions['menu']['extattrs']) && $permissions['menu']['extattrs']) {
          print '<li class="mdl-js-ripple-effect">';
          $args = array();
          $args['plugin'] = null;
          $args['controller'] = 'co_extended_attributes';
          $args['action'] = 'index';
          $args['co'] = $menuCoId;

          print $this->Html->link(_txt('ct.co_extended_attributes.pl'), $args);
          print '<span class="mdl-ripple"></span>';
          print "</li>";
        }

        if (isset($permissions['menu']['exttypes']) && $permissions['menu']['exttypes']) {
          print '<li class="mdl-js-ripple-effect">';
          $args = array();
          $args['plugin'] = null;
          $args['controller'] = 'co_extended_types';
          $args['action'] = 'index';
          $args['co'] = $menuCoId;

          print $this->Html->link(_txt('ct.co_extended_types.pl'), $args);
          print '<span class="mdl-ripple"></span>';
          print "</li>";
        }

        if (isset($permissions['menu']['idassign']) && $permissions['menu']['idassign']) {
          print '<li class="mdl-js-ripple-effect">';
          $args = array();
          $args['plugin'] = null;
          $args['controller'] = 'co_identifier_assignments';
          $args['action'] = 'index';
          $args['co'] = $menuCoId;

          print $this->Html->link(_txt('ct.co_identifier_assignments.pl'), $args);
          print '<span class="mdl-ripple"></span>';
          print "</li>";
        }

        if (isset($permissions['menu']['idvalidate']) && $permissions['menu']['idvalidate']) {
          print "<li>";
          $args = array();
          $args['plugin'] = null;
          $args['controller'] = 'co_identifier_validators';
          $args['action'] = 'index';
          $args['co'] = $menuCoId;

          print $this->Html->link(_txt('ct.co_identifier_validators.pl'), $args);
          print "</li>";
        }

        if (isset($permissions['menu']['colocalizations']) && $permissions['menu']['colocalizations']) {
          print '<li class="mdl-js-ripple-effect">';
          $args = array();
          $args['plugin'] = null;
          $args['controller'] = 'co_localizations';
          $args['action'] = 'index';
          $args['co'] = $menuCoId;

          print $this->Html->link(_txt('ct.co_localizations.pl'), $args);
          print '<span class="mdl-ripple"></span>';
          print "</li>";
        }

        if (isset($permissions['menu']['comessagetemplates']) && $permissions['menu']['comessagetemplates']) {
          print '<li class="mdl-js-ripple-effect">';
          $args = array();
          $args['plugin'] = null;
          $args['controller'] = 'co_message_templates';
          $args['action'] = 'index';
          $args['co'] = $menuCoId;

          print $this->Html->link(_txt('ct.co_message_templates.pl'), $args);
          print '<span class="mdl-ripple"></span>';
          print "</li>";
        }

        if (isset($permissions['menu']['conavigationlinks']) && $permissions['menu']['conavigationlinks']) {
          print '<li class="mdl-js-ripple-effect">';
          $args = array();
          $args['plugin'] = null;
          $args['controller'] = 'co_navigation_links';
          $args['action'] = 'index';
          $args['co'] = $menuCoId;

          print $this->Html->link(_txt('ct.co_navigation_links.pl'), $args);
          print '<span class="mdl-ripple"></span>';
          print "</li>";
        }
        
        if (isset($permissions['menu']['orgidsources']) && $permissions['menu']['orgidsources']) {
          print '<li class="mdl-js-ripple-effect">';
          $args = array();
          $args['plugin'] = null;
          $args['controller'] = 'org_identity_sources';
          $args['action'] = 'index';
          $args['co'] = $menuCoId;

          print $this->Html->link(_txt('ct.org_identity_sources.pl'), $args);
          print '<span class="mdl-ripple"></span>';
          print "</li>";
        }

        if (isset($permissions['menu']['copipelines']) && $permissions['menu']['copipelines']) {
          print '<li class="mdl-js-ripple-effect">';
          $args = array();
          $args['plugin'] = null;
          $args['controller'] = 'co_pipelines';
          $args['action'] = 'index';
          $args['co'] = $menuCoId;

          print $this->Html->link(_txt('ct.co_pipelines.pl'), $args);
          print '<span class="mdl-ripple"></span>';
          print "</li>";
        }

        if (isset($permissions['menu']['coprovtargets']) && $permissions['menu']['coprovtargets']) {
          print '<li class="mdl-js-ripple-effect">';
          $args = array();
          $args['plugin'] = null;
          $args['controller'] = 'co_provisioning_targets';
          $args['action'] = 'index';
          $args['co'] = $menuCoId;

          print $this->Html->link(_txt('ct.co_provisioning_targets.pl'), $args);
          print '<span class="mdl-ripple"></span>';
          print "</li>";
        }

        if (isset($permissions['menu']['coselfsvcperm']) && $permissions['menu']['coselfsvcperm']) {
          print '<li class="mdl-js-ripple-effect">';
          $args = array();
          $args['plugin'] = null;
          $args['controller'] = 'co_self_service_permissions';
          $args['action'] = 'index';
          $args['co'] = $menuCoId;

          print $this->Html->link(_txt('ct.co_self_service_permissions.pl'), $args);
          print '<span class="mdl-ripple"></span>';
          print "</li>";
        }

        if (isset($permissions['menu']['coservices']) && $permissions['menu']['coservices']) {
          print "<li>";
          $args = array();
          $args['plugin'] = null;
          $args['controller'] = 'co_services';
          $args['action'] = 'index';
          $args['co'] = $menuCoId;

          print $this->Html->link(_txt('ct.co_services.pl'), $args);
          print "</li>";
        }

        if (isset($permissions['menu']['cotandc']) && $permissions['menu']['cotandc']) {
          print '<li class="mdl-js-ripple-effect">';
          $args = array();
          $args['plugin'] = null;
          $args['controller'] = 'co_terms_and_conditions';
          $args['action'] = 'index';
          $args['co'] = $menuCoId;

          print $this->Html->link(_txt('ct.co_terms_and_conditions.pl'), $args);
          print '<span class="mdl-ripple"></span>';
          print "</li>";
        }

        if (isset($permissions['menu']['cothemes']) && $permissions['menu']['cothemes']) {
          print "<li>";
          $args = array();
          $args['plugin'] = null;
          $args['controller'] = 'co_themes';
          $args['action'] = 'index';
          $args['co'] = $menuCoId;

          print $this->Html->link(_txt('ct.co_themes.pl'), $args);
          print "</li>";
        }
        
        if (!empty($menuContent['plugins'])) {
          render_plugin_menus($this->Html, $menuContent['plugins'], 'coconfig', $menuCoId);
        }

        print "</ul>";
        print "</li>";
      }
    }

    // Platform Menu
    if(!empty($permissions['menu']['admin']) && $permissions['menu']['admin']) {
      print'<li class="platformMenu">';
      print'<a href="#" class="menuTop mdl-js-ripple-effect" aria-expanded="false" href="#">';
      print'<i class="material-icons">settings</i>';
      print '<span class="menuTitle">' . _txt('me.platform') . '</span>';
      print '<span class="fa arrow fa-fw"></span>';
      print '<span class="mdl-ripple"></span>';
      print '</a>';
      print '<ul aria-expanded="false">';

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
    print '<a class="menuTop mdl-js-ripple-effect" aria-expanded="false" href="#">';
    print '<i class="material-icons">assignment_turned_in</i>';
    print '<span class="menuTitle">' . _txt('me.collaborations') . '</span>';
    print '<span class="fa arrow fa-fw"></span>';
    print '<span class="mdl-ripple"></span>';
    print '</a>';

    //loop over each CO
    if(count($cos) > 0) {
      print '<ul aria-expanded="false">';

      foreach($cos as $menuCoName => $menuCoData) {
        $collabMenuCoId = $menuCoData['co_id'];

        if((!isset($menuCoData['co_person']['status'])
            || ($menuCoData['co_person']['status'] != StatusEnum::Active
              && $menuCoData['co_person']['status'] != StatusEnum::GracePeriod))
          && !$permissions['menu']['admin']) {
          // Don't render this CO, the person is not an active member (or a CMP admin)
          continue;
        }

        print '<li class="mdl-js-ripple-effect">';

        // We use $menuCoData here and not $menuCoName because the former will indicate
        // 'Not a Member' for CMP Admins (where they are not a member of the CO)
        $args = array();
        $args['plugin'] = null;
        $args['controller'] = 'co_dashboards';
        $args['action'] = 'dashboard';
        $args['co'] = $collabMenuCoId;

        print $this->Html->link($menuCoData['co_name'], $args);
        print '<span class="mdl-ripple"></span>';
        print '</li>';
      }

      // Plugins
      if (!empty($menuContent['plugins'])) {
        render_plugin_menus($this->Html, $menuContent['plugins'], 'cos');
      }

      print '</ul>';
      print '</li>';
    }
  ?>

</ul>
