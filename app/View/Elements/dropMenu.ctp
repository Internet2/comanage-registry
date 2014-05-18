<?php
/*
 * COmanage Registry Dropdown Menu Bar
 * Displayed above all pages when logged in
 *
 * Version: $Revision$
 * Date: $Date$
 *
 * Copyright (C) 2012-13 University Corporation for Advanced Internet Development, Inc.
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
if(isset($this->viewVars['menuContent']['cos']))
  $cos = $this->viewVars['menuContent']['cos'];
else
  $cos = array();

?>

<ul class="sf-menu">

<!-- Organizations Dropdown -->
  <li class="dropMenu">
    <a class="menuTop">
      <span>
        <?php print _txt('me.collaborations'); ?>
      </span>
      <span class="sf-sub-indicator"> »</span>
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

              print '<li>';
                print '<a>' . _txt('me.people') . '</a>
                       <span class="sf-sub-indicator"> »</span>';
                print '<ul>';

                if(isset($permissions['menu']['cos']) && $permissions['menu']['cos']) {
                  print "<li>";
                    $args = array();
                    $args['plugin'] = null;
                    $args['controller'] = 'co_people';
                    $args['action'] = 'index';
                    $args['co'] = $menuCoId;
                    
                    print $this->Html->link(_txt('me.population'), $args);
                  print "</li>";
                }

                if(isset($permissions['menu']['orgidentities']) && $permissions['menu']['orgidentities']) {
                  $args = array();
                  $args['plugin'] = null;
                  $args['controller'] = 'org_identities';
                  $args['action'] = 'index';
                  
                  if(!$pool_org_identities) {
                    $args['co'] = $menuCoId;
                  }
                  
                  print "<li>";
                    print $this->Html->link(_txt('ct.org_identities.pl'), $args);
                  print "</li>";
                }

                if(isset($permissions['menu']['createpetition']) && $permissions['menu']['createpetition']) {
                  print "<li>";
                    $args = array();
                    $args['plugin'] = null;
                    $args['controller'] = 'co_enrollment_flows';
                    $args['action'] = 'select';
                    $args['co'] = $menuCoId;
                    
                    print $this->Html->link(_txt('op.enroll'), $args);
                  print "</li>";
                }

                if(isset($permissions['menu']['petitions']) && $permissions['menu']['petitions']) {
                  print "<li>";
                    $args = array();
                    $args['plugin'] = null;
                    $args['controller'] = 'co_petitions';
                    $args['action'] = 'index';
                    $args['co'] = $menuCoId;
                    $args['sort'] = 'created';
                    $args['Search.status'][] = StatusEnum::PendingApproval;
                    $args['Search.status'][] = StatusEnum::PendingConfirmation;

                    print $this->Html->link(_txt('ct.co_petitions.pl'), $args);
                  print "</li>";
                }
                
                if(!empty($plugins)) {
                  render_plugin_menus($this->Html, $plugins, 'copeople', $menuCoId);
                }
                
                print "</ul>";
              print "</li>";

              if(isset($permissions['menu']['cogroups']) && $permissions['menu']['cogroups']) {
                print "<li>";
                  $args = array();
                  $args['plugin'] = null;
                  $args['controller'] = 'co_groups';
                  $args['action'] = 'index';
                  $args['co'] = $menuCoId;
                  
                  print $this->Html->link(_txt('ct.co_groups.pl'), $args);
                print "</li>";
              }
              
              if(!empty($plugins)) {
                render_plugin_menus($this->Html, $plugins, 'cos', $menuCoId);
              }
              
              if($permissions['menu']['cos']) {
                print '<li>';
                  print '<a>' . _txt('me.configuration') . '</a>
                         <span class="sf-sub-indicator"> »</span>';
                  print '<ul>';

                  if(isset($permissions['menu']['coef']) && $permissions['menu']['coef']) {
                    print "<li>";
                      $args = array();
                      $args['plugin'] = null;
                      $args['controller'] = 'co_enrollment_flows';
                      $args['action'] = 'index';
                      $args['co'] = $menuCoId;
                      
                      print $this->Html->link(_txt('ct.co_enrollment_flows.pl'), $args);
                    print "</li>";
                  }
  
                  if(isset($permissions['menu']['cous']) && $permissions['menu']['cous']) {
                    print "<li>";
                      $args = array();
                      $args['plugin'] = null;
                      $args['controller'] = 'cous';
                      $args['action'] = 'index';
                      $args['co'] = $menuCoId;
                      
                      print $this->Html->link(_txt('ct.cous.pl'), $args);
                    print "</li>";
                  }

                  if(isset($permissions['menu']['extattrs']) && $permissions['menu']['extattrs']) {
                    print "<li>";
                      $args = array();
                      $args['plugin'] = null;
                      $args['controller'] = 'co_extended_attributes';
                      $args['action'] = 'index';
                      $args['co'] = $menuCoId;
                      
                      print $this->Html->link(_txt('ct.co_extended_attributes.pl'), $args);
                    print "</li>";
                  }
  
                  if(isset($permissions['menu']['exttypes']) && $permissions['menu']['exttypes']) {
                    print "<li>";
                      $args = array();
                      $args['plugin'] = null;
                      $args['controller'] = 'co_extended_types';
                      $args['action'] = 'index';
                      $args['co'] = $menuCoId;
                      
                      print $this->Html->link(_txt('ct.co_extended_types.pl'), $args);
                    print "</li>";
                  }
                  
                  if(isset($permissions['menu']['idassign']) && $permissions['menu']['idassign']) {
                    print "<li>";
                      $args = array();
                      $args['plugin'] = null;
                      $args['controller'] = 'co_identifier_assignments';
                      $args['action'] = 'index';
                      $args['co'] = $menuCoId;
                      
                      print $this->Html->link(_txt('ct.co_identifier_assignments.pl'), $args);
                    print "</li>";
                  }
                  
                  if(isset($permissions['menu']['coprovtargets']) && $permissions['menu']['coprovtargets']) {
                    print "<li>";
                      $args = array();
                      $args['plugin'] = null;
                      $args['controller'] = 'co_provisioning_targets';
                      $args['action'] = 'index';
                      $args['co'] = $menuCoId;
                      
                      print $this->Html->link(_txt('ct.co_provisioning_targets.pl'), $args);
                    print "</li>";
                  }
                  
                  if(isset($permissions['menu']['cotandc']) && $permissions['menu']['cotandc']) {
                    print "<li>";
                      $args = array();
                      $args['plugin'] = null;
                      $args['controller'] = 'co_terms_and_conditions';
                      $args['action'] = 'index';
                      $args['co'] = $menuCoId;
                      
                      print $this->Html->link(_txt('ct.co_terms_and_conditions.pl'), $args);
                    print "</li>";
                  }
                  
                  if(isset($permissions['menu']['conavigationlinks'])) {
                    print "<li>";
                      $args = array();
                      $args['plugin'] = null;
                      $args['controller'] = 'co_navigation_links';
                      $args['action'] = 'index';
                      $args['co'] = $menuCoId;
                      
                      print $this->Html->link(_txt('ct.co_navigation_links.pl'), $args);
                    print "</li>";
                  }
                  
                  if(isset($permissions['menu']['colocalizations'])) {
                    print "<li>";
                      $args = array();
                      $args['plugin'] = null;
                      $args['controller'] = 'co_localizations';
                      $args['action'] = 'index';
                      $args['co'] = $menuCoId;
                      
                      print $this->Html->link(_txt('ct.co_localizations.pl'), $args);
                    print "</li>";
                  }
                  
                  if(!empty($plugins)) {
                    render_plugin_menus($this->Html, $plugins, 'coconfig', $menuCoId);
                  }
                  
                  print "</ul>";
                print "</li>";
              }
              
            print "</ul>";
          }
          print "</li>";
        print "</ul>";
      }
    ?>
  </li>
</ul>
