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
    print '<nav id="dashboard-tabs" class="cm-subnav-tabs" aria-label="' . _txt('me.menu.dashboards') . '">';
    print '<ul class="nav nav-tabs">';
    foreach($vv_available_dashboards as $dashboardId => $dashboardName) {
      print '<li class="nav-item">';
      if ($vv_dashboard['CoDashboard']['id'] == $dashboardId) {
        print '<span class="nav-link active">' . $dashboardName . '</span>';
      } else {
        print $this->Html->link(filter_var($dashboardName, FILTER_SANITIZE_SPECIAL_CHARS), array(
          'controller' => 'co_dashboards',
          'action' => 'dashboard',
          $dashboardId
        ),
          array('class' => 'nav-link spin'));
      }
      print '</li>';
    }
    print '</ul>';
    print '</nav>';
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

        print "$('#widget" . $w['id'] . "').load('" . addslashes($this->Html->url($args)) . "', function() { $('#widgetSpinner" . $w['id'] . "').hide(); });\n";
      }
    }
  }
?>
  });
</script>

<div class="table-container">
  <?php if(!empty($vv_dashboard)): ?>
    <?php
      // The Dashboard headers and footers can display user-generated HTML output AND CSS in a <style> tag. Use the html-sanitizer library.
      if(!empty($vv_dashboard['CoDashboard']['header_text']) || !empty($vv_dashboard['CoDashboard']['footer_text'])) {
        require(APP . '/Vendor/html-sanitizer-1.5/vendor/autoload.php');
        
        // We must build the Sanitizer to include our custom extension
        $builder = new HtmlSanitizer\SanitizerBuilder();
        $builder->registerExtension(new HtmlSanitizer\Extension\Basic\BasicExtension());
        $builder->registerExtension(new HtmlSanitizer\Extension\Code\CodeExtension());
        $builder->registerExtension(new HtmlSanitizer\Extension\Image\ImageExtension());
        $builder->registerExtension(new HtmlSanitizer\Extension\Listing\ListExtension());
        $builder->registerExtension(new HtmlSanitizer\Extension\Table\TableExtension());
        $builder->registerExtension(new HtmlSanitizer\Extension\Details\DetailsExtension());
        $builder->registerExtension(new HtmlSanitizer\Extension\Extra\ExtraExtension());
        
        // Our custom extension to allow <style> tags.
        $builder->registerExtension(new HtmlSanitizer\Extension\Style\StyleExtension());
  
        $sanitizer = $builder->build([
          'extensions' => ['basic', 'code', 'image', 'list', 'table', 'details', 'extra', 'style'],
          'tags' => [
            'div' => [
              'allowed_attributes' => ['class'],
            ],
            'p' => [
              'allowed_attributes' => ['class'],
            ]
          ],
          'keepstyle' => true
        ]);
      }
    ?>
    <?php if(!empty($vv_dashboard['CoDashboard']['header_text'])): ?>
      <div id="dashboard-header">
        <?php print $sanitizer->sanitize($vv_dashboard['CoDashboard']['header_text']); ?>
      </div>
    <?php endif; ?>
    <?php if(!empty($vv_dashboard['CoDashboardWidget'])): ?>
      <?php foreach($vv_dashboard['CoDashboardWidget'] as $w): ?>
        <?php if($w['status'] == StatusEnum::Active): ?>
          <div class="dashboard-widget-container">
            <h2 class="widget-title">
              <?php print filter_var($w['description'], FILTER_SANITIZE_SPECIAL_CHARS); ?>
              <span id="widgetSpinner<?php print $w['id']; ?>" class="co-loading-mini"><span></span><span></span><span></span></span>
            </h2>
            <?php if(isset($w['header']) && !empty(trim($w['header']))): ?>
              <div class="widget-header">
                <?php print filter_var($w['header'], FILTER_SANITIZE_SPECIAL_CHARS); ?>
              </div>
            <?php endif; ?>
            <div id="widget<?php print $w['id']; ?>"></div>
            <?php if(isset($w['footer']) && !empty(trim($w['footer']))): ?>
              <div class="widget-footer">
                <?php print filter_var($w['footer'], FILTER_SANITIZE_SPECIAL_CHARS); ?>
              </div>
            <?php endif; ?>
          </div>
        <?php endif; // Active ?>
      <?php endforeach; // dashboard widget ?>
    <?php else: ?>
      <?php print _txt('in.widgets.none'); ?>
    <?php endif; ?>
    <?php if(!empty($vv_dashboard['CoDashboard']['footer_text'])): ?>
      <div id="dashboard-footer">
        <?php print $sanitizer->sanitize($vv_dashboard['CoDashboard']['footer_text']); ?>
      </div>
    <?php endif; ?>
  <?php else: // $vv_dashboard ?>
  <!-- XXX this doesn't really render correctly -->
  <h1 class="firstPrompt">
    <?php print _txt('op.dashboard.select', array(filter_var($cur_co['Co']['name'],FILTER_SANITIZE_SPECIAL_CHARS)));?>
  </h1>
  <?php endif; ?>
</div>