<?php
/**
 * COmanage Registry CO Dashboard Dashboard View
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
 * @since         COmanage Registry v0.9.2
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

  // Add breadcrumbs
  print $this->element("coCrumb");

  // Add page title
  $params = array();
  $params['title'] = $title_for_layout;
  print $this->element("pageTitle", $params);

  // Add dashboard navigation if we have more than one dashboard
  if(!empty($vv_available_dashboards) && count($vv_available_dashboards) > 1) {
    print '<div id="dashboard-tabs" class="mdl-tabs">';
    print '<nav class="mdl-tabs__tab-bar">';
    foreach($vv_available_dashboards as $dashboardId => $dashboardName) {
      if ($vv_dashboard['CoDashboard']['id'] == $dashboardId) {
        print '<span class="mdl-tabs__tab selected">' . $dashboardName . '</span>';
      } else {
        print $this->Html->link(filter_var($dashboardName, FILTER_SANITIZE_SPECIAL_CHARS), array(
          'controller' => 'co_dashboards',
          'action' => 'dashboard',
          $dashboardId
        ),
          array('class' => 'mdl-tabs__tab'));
      }
    }
    print '</nav>';
    print '</div>';
  }
?>

<script type="text/javascript">
  // Load widget content into divs
  $(document).ready(function() {
<?php
  if(!empty($vv_dashboard)) {
    foreach($vv_dashboard['CoDashboardWidget'] as $w) {
      if($w['status'] == StatusEnum::Active) {
        $pmodel = 'Co'.$w['plugin'];
        
        $args = array(
          'plugin' => Inflector::underscore($w['plugin']),
          'controller' => Inflector::tableize($pmodel),
          'action' => 'display',
          $w[$pmodel]['id']
        );

        print "var coSpinnerTarget" . $w['id'] . " = document.getElementById('widgetSpinner" . $w['id'] . "');\n";
        print "var coSpinner" . $w['id'] . " = new Spinner(coMiniSpinnerOpts).spin(coSpinnerTarget" . $w['id'] . ");\n";
        print "$('#widget" . $w['id'] . "').load('" . addslashes($this->Html->url($args)) . "', function() { coSpinner" . $w['id'] . ".stop(); });\n";
      }
    }
  }
?>
  });
</script>

<div class="table-container">
  <?php if(!empty($vv_dashboard)): ?>
    <?php /* XXX Keep if we want to display the dashboard description, but this might be better served with a
             display field used specifically for this purpose, or perhaps an HTML widget
    <?php if(!empty($vv_dashboard['CoDashboard']['description'])): ?>
      <div id="dashboard-desc">
        <?php print $vv_dashboard['CoDashboard']['description']; ?>
      </div>
    <?php endif; ?>
    */ ?>
    <?php if(!empty($vv_dashboard['CoDashboardWidget'])): ?>
      <?php foreach($vv_dashboard['CoDashboardWidget'] as $w): ?>
        <?php if($w['status'] == StatusEnum::Active): ?>
          <div class="dashboard-widget-container">
            <h2 class="widget-title">
              <?php print filter_var($w['description'], FILTER_SANITIZE_SPECIAL_CHARS); ?>
              <span id="widgetSpinner<?php print $w['id']; ?>" class="mini-spinner"></span>
            </h2>
            <div id="widget<?php print $w['id']; ?>"></div>
          </div>
        <?php endif; // Active ?>
      <?php endforeach; // dashboard widget ?>
    <?php else: ?>
      <?php print _txt('in.widgets.none'); ?>
    <?php endif; ?>
  <?php else: // $vv_dashboard ?>
  <!-- XXX this doesn't really render correctly -->
  <h1 class="firstPrompt">
    <?php print _txt('op.dashboard.select', array(filter_var($cur_co['Co']['name'],FILTER_SANITIZE_SPECIAL_CHARS)));?>
  </h1>
  <?php endif; ?>
</div>