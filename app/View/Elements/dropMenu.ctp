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

<ul class="sf-menu">
<!-- Collaborations Dropdown -->
  <li class="dropMenu collabMenu">
    <a class="menuTop">
      <span>
        <?php print _txt('me.collaborations'); ?>
      </span>
      <span class="sf-sub-indicator"> »</span>
    </a>
    <?php
      //loop over each CO
      if(count($cos) > 0) {
        print "<ul>";
        
        foreach($cos as $menuCoName => $menuCoData) {
          $collabMenuCoId = $menuCoData['co_id'];
          
          if((!isset($menuCoData['co_person']['status'])
              || ($menuCoData['co_person']['status'] != StatusEnum::Active
                  && $menuCoData['co_person']['status'] != StatusEnum::GracePeriod))
             && !$permissions['menu']['admin']) {
            // Don't render this CO, the person is not an active member (or a CMP admin)
            continue;
          }
          
          print '<li>';
          
          // We use $menuCoData here and not $menuCoName because the former will indicate
          // 'Not a Member' for CMP Admins (where they are not a member of the CO)
          $args = array();
          $args['plugin'] = null;
          $args['controller'] = 'co_dashboards';
          $args['action'] = 'dashboard';
          $args['co'] = $collabMenuCoId;
          
          print $this->Html->link($menuCoData['co_name'], $args);
          
          print "</li>";
        }
        
        // Plugins
        if (!empty($menuContent['plugins'])) {
          render_plugin_menus($this->Html, $menuContent['plugins'], 'cos');
        }
        
        print "</ul>";
      }
    ?>
  </li> 

  <?php
    // Output the CO submenus (People, Groups, Configuration) if a CO is selected
    if(!empty($cur_co['Co']['id'])) {
      $menuCoId = $cur_co['Co']['id'];

      print '<li class="dropMenu peopleMenu">';
      print '<a class="menuTop">';
      print '<span>' . _txt('me.people') . '</span>';
      print '<span class="sf-sub-indicator"> »</span>';
      print '</a>';
      print '<ul>';

      if (isset($permissions['menu']['cos']) && $permissions['menu']['cos']) {
        print "<li>";
        $args = array();
        $args['plugin'] = null;
        $args['controller'] = 'co_people';
        $args['action'] = 'index';
        $args['co'] = $menuCoId;
        
        print $this->Html->link(_txt('me.population'), $args);
        print "</li>";
        
        if(!empty($permissions['menu']['admincous'])) {
          foreach($permissions['menu']['admincous'] as $couid => $couname) {
            print "<li>";
            $args = array();
            $args['plugin'] = null;
            $args['controller'] = 'co_people';
            $args['action'] = 'index';
            $args['co'] = $menuCoId;
            $args['Search.couid'] = $couid;
            
            print $this->Html->link(_txt('me.population.cou', array($couname)), $args);
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

        print "<li>";
        print $this->Html->link(_txt('ct.org_identities.pl'), $args);
        print "</li>";
      }

      if (in_array($menuCoId, $efcos)) {
        // Enrollment Flows enabled
        if (isset($permissions['menu']['createpetition']) && $permissions['menu']['createpetition']) {
          print "<li>";
          $args = array();
          $args['plugin'] = null;
          $args['controller'] = 'co_enrollment_flows';
          $args['action'] = 'select';
          $args['co'] = $menuCoId;

          print $this->Html->link(_txt('op.enroll'), $args);
          print "</li>";
        }
        
        if (isset($permissions['menu']['petitions']) && $permissions['menu']['petitions']) {
          print "<li>";
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
          print "</li>";
        }
      } else {
        // Default enrollment
        if (isset($permissions['menu']['invite']) && $permissions['menu']['invite']) {
          print "<li>";
          $args = array();
          $args['plugin'] = null;
          $args['controller'] = 'org_identities';
          $args['action'] = 'find';
          $args['co'] = $menuCoId;

          print $this->Html->link(_txt('op.inv'), $args);
          print "</li>";
        }
      }

      if(!empty($menuContent['plugins'])) {
        render_plugin_menus($this->Html, $menuContent['plugins'], 'copeople', $menuCoId);
      }

      print "</ul>";
      print "</li>";

      // Groups
      if (isset($permissions['menu']['cogroups']) && $permissions['menu']['cogroups']) {
        print '<li class="dropMenu groupMenu">';

        print '<a class="menuTop">';
        print '<span>' . _txt('ct.co_groups.pl') . '</span>';
        print '<span class="sf-sub-indicator"> »</span>';
        print '</a>';
        print '<ul>';

        print "<li>";
        $args = array();
        $args['plugin'] = null;
        $args['controller'] = 'co_groups';
        $args['action'] = 'select';
        $args['co'] = $menuCoId;
        print $this->Html->link(_txt('op.grm.my.groups'), $args);
        print "</li>";

        print '<li>';
        $args = array();
        $args['plugin'] = null;
        $args['controller'] = 'co_groups';
        $args['action'] = 'index';
        $args['co'] = $menuCoId;
        print $this->Html->link(_txt('ct.co_all_groups'), $args);
        print "</li>";
        
        // Plugins
        if (!empty($menuContent['plugins'])) {
          render_plugin_menus($this->Html, $menuContent['plugins'], 'cogroups', $menuCoId);
        }
        
        print "</ul>";
        print "</li>";
      }
      
      // Services
      if(!empty($menuContent['services'])) {
        print '<li class="dropMenu serviceMenu">';
        
        print '<a class="menuTop">';
        print '<span>' . _txt('ct.co_services.pl') . '</span>';
        print '<span class="sf-sub-indicator"> »</span>';
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
          print "<li>";
          print $this->Html->link($svc['CoService']['name'], $svc['CoService']['service_url']);
          print "</li>";
        }
        
        // Plugins
        if(!empty($menuContent['plugins'])) {
          render_plugin_menus($this->Html, $menuContent['plugins'], 'coservices', $menuCoId);
        }
        
        print "</ul>";
        print "</li>";
      }

      // Configuration
      if ($permissions['menu']['coconfig']) {
        print '<li class="dropMenu configMenu">';

        print '<a class="menuTop">';
        print '<span>' . _txt('me.configuration') . '</span>';
        print '<span class="sf-sub-indicator"> »</span>';
        print '</a>';
        print '<ul>';
        
        if (isset($permissions['menu']['cosettings']) && $permissions['menu']['cosettings']) {
          print "<li>";
          $args = array();
          $args['plugin'] = null;
          $args['controller'] = 'co_settings';
          $args['action'] = 'add';
          $args['co'] = $menuCoId;

          print $this->Html->link(_txt('ct.co_settings.pl'), $args);
          print "</li>";
        }

        if (isset($permissions['menu']['coattrenums']) && $permissions['menu']['coattrenums']) {
          print "<li>";
          $args = array();
          $args['plugin'] = null;
          $args['controller'] = 'attribute_enumerations';
          $args['action'] = 'index';
          $args['co'] = $menuCoId;

          print $this->Html->link(_txt('ct.attribute_enumerations.pl'), $args);
          print "</li>";
        }

        if (isset($permissions['menu']['cous']) && $permissions['menu']['cous']) {
          print "<li>";
          $args = array();
          $args['plugin'] = null;
          $args['controller'] = 'cous';
          $args['action'] = 'index';
          $args['co'] = $menuCoId;

          print $this->Html->link(_txt('ct.cous.pl'), $args);
          print "</li>";
        }

        if (isset($permissions['menu']['coef']) && $permissions['menu']['coef']) {
          print "<li>";
          $args = array();
          $args['plugin'] = null;
          $args['controller'] = 'co_enrollment_flows';
          $args['action'] = 'index';
          $args['co'] = $menuCoId;

          print $this->Html->link(_txt('ct.co_enrollment_flows.pl'), $args);
          print "</li>";
        }

        if (isset($permissions['menu']['coxp']) && $permissions['menu']['coxp']) {
          print "<li>";
          $args = array();
          $args['plugin'] = null;
          $args['controller'] = 'co_expiration_policies';
          $args['action'] = 'index';
          $args['co'] = $menuCoId;

          print $this->Html->link(_txt('ct.co_expiration_policies.pl'), $args);
          print "</li>";
        }

        if (isset($permissions['menu']['extattrs']) && $permissions['menu']['extattrs']) {
          print "<li>";
          $args = array();
          $args['plugin'] = null;
          $args['controller'] = 'co_extended_attributes';
          $args['action'] = 'index';
          $args['co'] = $menuCoId;

          print $this->Html->link(_txt('ct.co_extended_attributes.pl'), $args);
          print "</li>";
        }

        if (isset($permissions['menu']['exttypes']) && $permissions['menu']['exttypes']) {
          print "<li>";
          $args = array();
          $args['plugin'] = null;
          $args['controller'] = 'co_extended_types';
          $args['action'] = 'index';
          $args['co'] = $menuCoId;

          print $this->Html->link(_txt('ct.co_extended_types.pl'), $args);
          print "</li>";
        }

        if (isset($permissions['menu']['idassign']) && $permissions['menu']['idassign']) {
          print "<li>";
          $args = array();
          $args['plugin'] = null;
          $args['controller'] = 'co_identifier_assignments';
          $args['action'] = 'index';
          $args['co'] = $menuCoId;

          print $this->Html->link(_txt('ct.co_identifier_assignments.pl'), $args);
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
          print "<li>";
          $args = array();
          $args['plugin'] = null;
          $args['controller'] = 'co_localizations';
          $args['action'] = 'index';
          $args['co'] = $menuCoId;

          print $this->Html->link(_txt('ct.co_localizations.pl'), $args);
          print "</li>";
        }

        if (isset($permissions['menu']['comessagetemplates']) && $permissions['menu']['comessagetemplates']) {
          print "<li>";
          $args = array();
          $args['plugin'] = null;
          $args['controller'] = 'co_message_templates';
          $args['action'] = 'index';
          $args['co'] = $menuCoId;

          print $this->Html->link(_txt('ct.co_message_templates.pl'), $args);
          print "</li>";
        }

        if (isset($permissions['menu']['conavigationlinks']) && $permissions['menu']['conavigationlinks']) {
          print "<li>";
          $args = array();
          $args['plugin'] = null;
          $args['controller'] = 'co_navigation_links';
          $args['action'] = 'index';
          $args['co'] = $menuCoId;

          print $this->Html->link(_txt('ct.co_navigation_links.pl'), $args);
          print "</li>";
        }
        
        if (isset($permissions['menu']['orgidsources']) && $permissions['menu']['orgidsources']) {
          print "<li>";
          $args = array();
          $args['plugin'] = null;
          $args['controller'] = 'org_identity_sources';
          $args['action'] = 'index';
          $args['co'] = $menuCoId;

          print $this->Html->link(_txt('ct.org_identity_sources.pl'), $args);
          print "</li>";
        }

        if (isset($permissions['menu']['copipelines']) && $permissions['menu']['copipelines']) {
          print "<li>";
          $args = array();
          $args['plugin'] = null;
          $args['controller'] = 'co_pipelines';
          $args['action'] = 'index';
          $args['co'] = $menuCoId;

          print $this->Html->link(_txt('ct.co_pipelines.pl'), $args);
          print "</li>";
        }

        if (isset($permissions['menu']['coprovtargets']) && $permissions['menu']['coprovtargets']) {
          print "<li>";
          $args = array();
          $args['plugin'] = null;
          $args['controller'] = 'co_provisioning_targets';
          $args['action'] = 'index';
          $args['co'] = $menuCoId;

          print $this->Html->link(_txt('ct.co_provisioning_targets.pl'), $args);
          print "</li>";
        }

        if (isset($permissions['menu']['coselfsvcperm']) && $permissions['menu']['coselfsvcperm']) {
          print "<li>";
          $args = array();
          $args['plugin'] = null;
          $args['controller'] = 'co_self_service_permissions';
          $args['action'] = 'index';
          $args['co'] = $menuCoId;

          print $this->Html->link(_txt('ct.co_self_service_permissions.pl'), $args);
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
          print "<li>";
          $args = array();
          $args['plugin'] = null;
          $args['controller'] = 'co_terms_and_conditions';
          $args['action'] = 'index';
          $args['co'] = $menuCoId;

          print $this->Html->link(_txt('ct.co_terms_and_conditions.pl'), $args);
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

  ?>
</ul>
