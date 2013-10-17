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

?>

<div class="menubar">
  <ul class="sf-menu">

  <!-- Organizations Dropdown -->
    <li class="dropMenu">
      <a>
        <span>
          <?php print _txt('me.collaborations'); ?>
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

                print '<li>';
                  print '<a>' . _txt('me.people') . '</a>
                         <span class="sf-sub-indicator"> »</span>';
                  print '<ul>';
                  
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
                  
                  render_plugin_menus($this->Html, $plugins, 'copeople', $menuCoId);
                  
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
                
                render_plugin_menus($this->Html, $plugins, 'cos', $menuCoId);
                
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
                    
                    render_plugin_menus($this->Html, $plugins, 'coconfig', $menuCoId);
                    
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

    <!-- Platform Dropdown -->
    <?php if($permissions['menu']['admin']): ?>
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
              $args['controller'] = 'organizations';
              $args['action'] = 'index';
              
              print $this->Html->link(_txt('ct.organizations.pl'), $args);
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
              $args['controller'] = 'navigation_links';
              $args['action'] = 'index';
              
              print $this->Html->link(_txt('ct.navigation_links.pl'), $args);
            ?>
          </li>
          <?php render_plugin_menus($this->Html, $plugins, 'cmp', $menuCoId); ?>
        </ul>
      </li>
    <?php endif; ?>

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
      </ul>
    </li>
  </ul>
</div>
