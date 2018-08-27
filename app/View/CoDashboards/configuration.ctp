<?php
  /**
   * COmanage Registry CO Configuration Page View
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

  // Add breadcrumbs
  print $this->element("coCrumb");
  $this->Html->addCrumb(_txt('me.configuration'));

  // Add page title
  $params = array();
  $params['title'] = $title_for_layout;

  print $this->element("pageTitleAndButtons", $params);
  
  // For everything except CO Settings, we want to order by the localized text string.
  $configMenuItems = array(
    _txt('ct.attribute_enumerations.pl') => array(
      'icon'          => 'format_list_numbered',
      'permissionKey' => 'coattrenums',
      'controller'    => 'attribute_enumerations',
      'action'        => 'index'
    ),
    _txt('ct.authenticators.pl') => array(
      'icon'          => 'lock',
      'permissionKey' => 'authenticator',
      'controller'    => 'authenticators',
      'action'        => 'index'
    ),
    _txt('ct.cous.pl') => array(
      'icon'          => 'people_outline',
      'permissionKey' => 'cous',
      'controller'    => 'cous',
      'action'        => 'index'
    ),
    _txt('ct.co_dashboards.pl') => array(
      'icon'          => 'dashboard',
      'permissionKey' => 'dashboards',
      'controller'    => 'co_dashboards',
      'action'        => 'index'
    ),
    _txt('ct.co_enrollment_flows.pl') => array(
      'icon'          => 'forward',
      'permissionKey' => 'coef',
      'controller'    => 'co_enrollment_flows',
      'action'        => 'index'
    ),
    _txt('ct.co_expiration_policies.pl') => array(
      'icon'          => 'access_alarm',
      'permissionKey' => 'coxp',
      'controller'    => 'co_expiration_policies',
      'action'        => 'index'
    ),
    _txt('ct.co_extended_attributes.pl') => array(
      'icon'          => 'developer_board',
      'permissionKey' => 'extattrs',
      'controller'    => 'co_extended_attributes',
      'action'        => 'index'
    ),
    _txt('ct.co_extended_types.pl') => array(
      'icon'          => 'widgets',
      'permissionKey' => 'exttypes',
      'controller'    => 'co_extended_types',
      'action'        => 'index'
    ),
    _txt('ct.co_identifier_assignments.pl') => array(
      'icon'          => 'person_pin',
      'permissionKey' => 'idassign',
      'controller'    => 'co_identifier_assignments',
      'action'        => 'index'
    ),
    _txt('ct.co_identifier_validators.pl') => array(
      'icon'          => 'check_circle',
      'permissionKey' => 'idvalidate',
      'controller'    => 'co_identifier_validators',
      'action'        => 'index'
    ),
    _txt('ct.co_localizations.pl') => array(
      'icon'          => 'translate',
      'permissionKey' => 'colocalizations',
      'controller'    => 'co_localizations',
      'action'        => 'index'
    ),
    _txt('ct.co_message_templates.pl') => array(
      'icon'          => 'message',
      'permissionKey' => 'comessagetemplates',
      'controller'    => 'co_message_templates',
      'action'        => 'index'
    ),
    _txt('ct.co_navigation_links.pl') => array(
      'icon'          => 'navigation',
      'permissionKey' => 'conavigationlinks',
      'controller'    => 'co_navigation_links',
      'action'        => 'index'
    ),
    _txt('ct.org_identity_sources.pl') => array(
      'icon'          => 'sync',
      'permissionKey' => 'orgidsources',
      'controller'    => 'org_identity_sources',
      'action'        => 'index'
    ),
    _txt('ct.co_pipelines.pl') => array(
      'icon'          => 'input',
      'permissionKey' => 'copipelines',
      'controller'    => 'co_pipelines',
      'action'        => 'index'
    ),
    _txt('ct.co_provisioning_targets.pl') => array(
      'icon'          => 'cloud_upload',
      'permissionKey' => 'coprovtargets',
      'controller'    => 'co_provisioning_targets',
      'action'        => 'index'
    ),
    _txt('ct.co_self_service_permissions.pl') => array(
      'icon'          => 'room_service',
      'permissionKey' => 'coselfsvcperm',
      'controller'    => 'co_self_service_permissions',
      'action'        => 'index'
    ),
    _txt('ct.co_services.pl') => array(
      'icon'          => 'apps',
      'permissionKey' => 'coservices',
      'controller'    => 'co_services',
      'action'        => 'index'
    ),
    _txt('ct.co_terms_and_conditions.pl') => array(
      'icon'          => 'assignment_late',
      'permissionKey' => 'cotandc',
      'controller'    => 'co_terms_and_conditions',
      'action'        => 'index'
    ),
    _txt('ct.co_themes.pl') => array(
      'icon'          => 'wallpaper', //palette//'invert_colors', // text_format
      'permissionKey' => 'cothemes',
      'controller'    => 'co_themes',
      'action'        => 'index'
    )
  );
  
  // Insert plugin items, along with 'plugin' => config item
  
  if(!empty($menuContent['plugins'])) {
    $pluginLinks = retrieve_plugin_menus($menuContent['plugins'], 'coconfig', $cur_co['Co']['id']);
    
    foreach($pluginLinks as $label => $pcfg) {
      // $pcfg['url']['co'] is set, but will be overridden below
      $configMenuItems[$label] = $pcfg['url'];
      $configMenuItems[$label]['icon'] = $pcfg['icon'];
    }
  }
  
  ksort($configMenuItems);
  
  // Insert CO Settings to the front of the list
  
  $configMenuItems = array_merge(
    array(
      _txt('ct.co_settings.pl') => array(
        'icon'          => 'settings',
        'permissionKey' => 'cosettings',
        'controller'    => 'co_settings',
        'action'        => 'add'
      )
    ),
    $configMenuItems
  );
?>

<section class="inner-content">
  <?php
    // Configuration Dashboard
    if (!empty($cur_co['Co']['id'])) {
      $menuCoId = $cur_co['Co']['id'];

      print '<ul id="configuration-menu" class="three-col">';
      
      foreach($configMenuItems as $label => $cfg) {
        if((!empty($cfg['permissionKey'])
            // Standard menu item
            && isset($permissions['menu'][ $cfg['permissionKey'] ])
            && $permissions['menu'][ $cfg['permissionKey'] ])
           ||
            // Plugin menu item
           (!empty($cfg['plugin']))) {
          
          print '<li>';
          print '<em class="material-icons" aria-hidden="true">' . $cfg['icon'] . '</em> ';
          $args = array();
          $args['plugin'] = (!empty($cfg['plugin']) ? $cfg['plugin'] : null);
          $args['controller'] = $cfg['controller'];
          $args['action'] = $cfg['action'];
          $args['co'] = $menuCoId;

          print $this->Html->link($label, $args);
          print "</li>";
        }
      }

      print "</ul>";
    }
  ?>
</section>
