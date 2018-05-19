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
  
  if(!empty($vv_available_dashboards)) {
    $args = array(
      'id' => 'tempjump',
      'value' => $vv_dashboard['CoDashboard']['id'],
      'empty' => false,
      'onChange' => 'window.location.replace(document.getElementById("tempjump").value);'
    );
    
    print $this->Form->select(null, $vv_available_dashboards, $args);
  }
?>
<script type="text/javascript">
  // Load widget content into divs
  
  $(document).ready(function() {
<?php
  foreach($vv_dashboard['CoDashboardWidget'] as $w) {
    $pmodel = 'Co'.$w['plugin'];
    
    $args = array(
      'plugin' => Inflector::underscore($w['plugin']),
      'controller' => Inflector::tableize($pmodel),
      'action' => 'display',
      $w[$pmodel]['id']
    );
    
    print "$('#widget" . $w['id'] . "').load('" . $this->Html->url($args) . "');\n";
  }
?>
  });
</script>
<div class="table-container">
<?php if(!empty($vv_dashboard)): ?>
  <?php foreach($vv_dashboard['CoDashboardWidget'] as $w): ?>
    <hr />
    <h2><?php print filter_var($w['description'], FILTER_SANITIZE_SPECIAL_CHARS); ?></h2>
    <div id="widget<?php print $w['id']; ?>"></div>
  <?php endforeach; // dashboard widget ?>
<?php else: // $vv_dashboard ?>
<!-- XXX this doesn't really render correctly -->
<h1 class="firstPrompt">
  <?php print _txt('op.dashboard.select', array(filter_var($cur_co['Co']['name'],FILTER_SANITIZE_SPECIAL_CHARS)));?>
</h1>
<?php endif; ?>
</div>