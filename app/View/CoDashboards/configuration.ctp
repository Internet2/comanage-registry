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

?>

<section class="inner-content">
  <?php
    // Configuration Dashboard
    if (!empty($cur_co['Co']['id'])) {
      $menuCoId = $cur_co['Co']['id'];

      print '<ul id="configuration-menu" class="three-col">';

      if (isset($permissions['menu']['cosettings']) && $permissions['menu']['cosettings']) {
        print '<li>';
        $args = array();
        $args['plugin'] = null;
        $args['controller'] = 'co_settings';
        $args['action'] = 'add';
        $args['co'] = $menuCoId;

        print $this->Html->link(_txt('ct.co_settings.pl'), $args);
        print "</li>";
      }

      if (isset($permissions['menu']['coattrenums']) && $permissions['menu']['coattrenums']) {
        print '<li>';
        $args = array();
        $args['plugin'] = null;
        $args['controller'] = 'attribute_enumerations';
        $args['action'] = 'index';
        $args['co'] = $menuCoId;

        print $this->Html->link(_txt('ct.attribute_enumerations.pl'), $args);
        print "</li>";
      }

      if (isset($permissions['menu']['cous']) && $permissions['menu']['cous']) {
        print '<li>';
        $args = array();
        $args['plugin'] = null;
        $args['controller'] = 'cous';
        $args['action'] = 'index';
        $args['co'] = $menuCoId;

        print $this->Html->link(_txt('ct.cous.pl'), $args);
        print "</li>";
      }

      if (isset($permissions['menu']['coef']) && $permissions['menu']['coef']) {
        print '<li>';
        $args = array();
        $args['plugin'] = null;
        $args['controller'] = 'co_enrollment_flows';
        $args['action'] = 'index';
        $args['co'] = $menuCoId;

        print $this->Html->link(_txt('ct.co_enrollment_flows.pl'), $args);
        print "</li>";
      }

      if (isset($permissions['menu']['coxp']) && $permissions['menu']['coxp']) {
        print '<li>';
        $args = array();
        $args['plugin'] = null;
        $args['controller'] = 'co_expiration_policies';
        $args['action'] = 'index';
        $args['co'] = $menuCoId;

        print $this->Html->link(_txt('ct.co_expiration_policies.pl'), $args);
        print "</li>";
      }

      if (isset($permissions['menu']['extattrs']) && $permissions['menu']['extattrs']) {
        print '<li>';
        $args = array();
        $args['plugin'] = null;
        $args['controller'] = 'co_extended_attributes';
        $args['action'] = 'index';
        $args['co'] = $menuCoId;

        print $this->Html->link(_txt('ct.co_extended_attributes.pl'), $args);
        print "</li>";
      }

      if (isset($permissions['menu']['exttypes']) && $permissions['menu']['exttypes']) {
        print '<li>';
        $args = array();
        $args['plugin'] = null;
        $args['controller'] = 'co_extended_types';
        $args['action'] = 'index';
        $args['co'] = $menuCoId;

        print $this->Html->link(_txt('ct.co_extended_types.pl'), $args);
        print "</li>";
      }

      if (isset($permissions['menu']['idassign']) && $permissions['menu']['idassign']) {
        print '<li>';
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
        print '<li>';
        $args = array();
        $args['plugin'] = null;
        $args['controller'] = 'co_localizations';
        $args['action'] = 'index';
        $args['co'] = $menuCoId;

        print $this->Html->link(_txt('ct.co_localizations.pl'), $args);
        print "</li>";
      }

      if (isset($permissions['menu']['comessagetemplates']) && $permissions['menu']['comessagetemplates']) {
        print '<li>';
        $args = array();
        $args['plugin'] = null;
        $args['controller'] = 'co_message_templates';
        $args['action'] = 'index';
        $args['co'] = $menuCoId;

        print $this->Html->link(_txt('ct.co_message_templates.pl'), $args);
        print "</li>";
      }

      if (isset($permissions['menu']['conavigationlinks']) && $permissions['menu']['conavigationlinks']) {
        print '<li>';
        $args = array();
        $args['plugin'] = null;
        $args['controller'] = 'co_navigation_links';
        $args['action'] = 'index';
        $args['co'] = $menuCoId;

        print $this->Html->link(_txt('ct.co_navigation_links.pl'), $args);
        print "</li>";
      }

      if (isset($permissions['menu']['orgidsources']) && $permissions['menu']['orgidsources']) {
        print '<li>';
        $args = array();
        $args['plugin'] = null;
        $args['controller'] = 'org_identity_sources';
        $args['action'] = 'index';
        $args['co'] = $menuCoId;

        print $this->Html->link(_txt('ct.org_identity_sources.pl'), $args);
        print "</li>";
      }

      if (isset($permissions['menu']['copipelines']) && $permissions['menu']['copipelines']) {
        print '<li>';
        $args = array();
        $args['plugin'] = null;
        $args['controller'] = 'co_pipelines';
        $args['action'] = 'index';
        $args['co'] = $menuCoId;

        print $this->Html->link(_txt('ct.co_pipelines.pl'), $args);
        print "</li>";
      }

      if (isset($permissions['menu']['coprovtargets']) && $permissions['menu']['coprovtargets']) {
        print '<li>';
        $args = array();
        $args['plugin'] = null;
        $args['controller'] = 'co_provisioning_targets';
        $args['action'] = 'index';
        $args['co'] = $menuCoId;

        print $this->Html->link(_txt('ct.co_provisioning_targets.pl'), $args);
        print "</li>";
      }

      if (isset($permissions['menu']['coselfsvcperm']) && $permissions['menu']['coselfsvcperm']) {
        print '<li>';
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
        print '<li>';
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
    }
  ?>
</section>
