<?php
/**
 * COmanage Registry Redirect View
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
 * @since         COmanage Registry v1.0.3
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */
?>
<!DOCTYPE html>
<html>
  <head>
    <title>
      <?php print _txt('op.processing'); ?>
    </title>
    <?php print $this->Html->charset(); ?>
    <?php print $this->Html->meta('favicon.ico','/favicon.ico',array('type' => 'icon')); ?>
    <meta name="viewport" content="width=device-width, initial-scale=1" />

    <!-- Handle the redirect -->
    <meta http-equiv="refresh" content="1;URL='<?php print $this->Html->url($vv_meta_redirect_target); ?>'" />

    <!-- Include the comanage and jquery style sheets -->
    <?php
    print $this->Html->css('jquery/jquery-ui-1.11.4.custom/jquery-ui.min');
    print $this->Html->css('jquery/jquery-ui-1.11.4.custom/jquery-ui-comanage-overrides');
    print $this->Html->css('jquery/superfish/css/superfish');
    print $this->Html->css('comanage');
    print $this->Html->css('comanage-responsive');
    ?>

    <!-- Get jquery code -->
    <?php
    print $this->Html->script('jquery/jquery-1.11.3.min.js');
    print $this->Html->script('jquery/jquery-ui-1.11.4.custom/jquery-ui.min.js');
    print $this->Html->script('jquery/superfish/js/superfish.js');
    print $this->Html->script('jquery/spin.min.js');
    ?>

    <script type="text/javascript">
      // Add a spinner to this page
      var coSpinnerOpts = {
        top: '75%',
        lines: 13, // The number of lines to draw
        length: 6, // The length of each line
        width: 3, // The line thickness
        radius: 6, // The radius of the inner circle
        corners: 0.4, // Corner roundness (0..1)
        rotate: 0, // The rotation offset
        direction: 1, // 1: clockwise, -1: counterclockwise
        color: '#9FC6E2', // #rgb or #rrggbb or array of colors
        speed: 1.2, // Rounds per second
        trail: 60, // Afterglow percentage
        shadow: false, // Whether to render a shadow
        hwaccel: false, // Whether to use hardware acceleration
        className: 'spinner', // The CSS class to assign to the spinner
        zIndex: 100 // The z-index (defaults to 2000000000)
      };
      $(function() {
        var coSpinnerTarget = document.getElementById('redirect-spinner');
        var coSpinner = new Spinner(coSpinnerOpts).spin(coSpinnerTarget);
      });

    </script>

    <!-- Include custom CSS -->
    <?php if(!empty($vv_theme_css)): ?>
      <style type="text/css">
        <?php print $vv_theme_css; ?>
      </style>
    <?php endif; ?>
  </head>

  <body  class="<?php print $this->params->controller . ' ' . $this->params->action ?>">

    <!-- Include custom header -->
    <?php if(!empty($vv_theme_header)): ?>
      <header id="customHeader">
        <div class="contentWidth">
          <?php print $vv_theme_header; ?>
        </div>
      </header>
    <?php endif; ?>

    <nav id="row1" aria-label="user and platform menus">
      <div class="contentWidth">
        <?php print $this->element('secondaryMenu'); ?>
        <?php print $this->element('links'); ?>
      </div>
    </nav>

    <?php if(!isset($vv_theme_hide_title) || !$vv_theme_hide_title): ?>
      <header id="row2" class="ui-widget-header">
        <div class="contentWidth">

          <div class="headerRight">
            <?php
              $imgFile = 'comanage-logo.png';

              if(is_readable(APP . WEBROOT_DIR . DS . 'img' . DS . 'logo.png')) {
                // A custom logo has been installed, so use that instead
                $imgFile = 'logo.png';
              }

              // Clicking on the logo will take us to the front page
              print $this->Html->link(
                $this->Html->image(
                  $imgFile,
                  array(
                    'alt' => 'COmanage Logo',
                    'height' => 50
                  )
                ),'/',
                array('escape' => false)
              );
            ?>
          </div>

          <div class="headerLeft">
            <?php
              if(!empty($cur_co['Co']['name'])) {
                print '<div id="collaborationTitle">' . filter_var($cur_co['Co']['name'],FILTER_SANITIZE_SPECIAL_CHARS) . '</div>'; // more to go here.
              } else {
                print '<div id="collaborationTitle">' . _txt('coordinate') . '</div>';
              }
            ?>
          </div>
        </div>
      </header>
    <?php endif; // $vv_theme_hide_title ?>

    <?php if($this->Session->check('Auth.User')): ?>
      <nav id="row3" aria-label="main menu">
        <div class="contentWidth">
          <?php print $this->element('dropMenu'); ?>
        </div>
      </nav>
    <?php endif ?>

    <main id="main" class="contentWidth">
      <div id="content">
        <div id="redirect-box">
          <div id="redirect-box-content">
            <?php print $this->fetch('content'); ?>
          </div>
          <div id="redirect-spinner"></div>
        </div>
      </div>
    </main>

    <!-- Include custom footer -->
    <?php if(!empty($vv_theme_footer)): ?>
      <footer id="customFooter">
        <div class="contentWidth">
          <?php print $vv_theme_footer; ?>
        </div>
      </footer>
    <?php endif; ?>

    <?php if(!isset($vv_theme_hide_footer_logo) || !$vv_theme_hide_footer_logo): ?>
      <footer class="contentWidth">
        <?php print $this->element('footer'); ?>
      </footer>
    <?php endif; ?>
  </body>
</html>
