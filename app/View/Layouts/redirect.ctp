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

  // As a general rule, all Registry pages are post-login and so shouldn't be cached
  header("Expires: Thursday, 10-Jan-69 00:00:00 GMT");
  header("Cache-Control: no-store, no-cache, max-age=0, must-revalidate");
  header("Pragma: no-cache");

  // Add X-UA-Compatible header for IE
  if (isset($_SERVER['HTTP_USER_AGENT']) && (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== false)) {
    header('X-UA-Compatible: IE=edge,chrome=1');
  }
?>
<!DOCTYPE html>
<html lang="<?php print _txt('lang'); ?>">
  <head>
    <?php print $this->Html->meta(array('name' => 'viewport', 'content' => 'width=device-width, initial-scale=1.0')) . "\n"; ?>
    <?php print $this->Html->charset() . "\n"; ?>

    <title><?php print _txt('op.processing'); ?></title>
    <?php print $this->Html->meta('favicon.ico','/favicon.ico',array('type' => 'icon')) . "\n"; ?>

    <!-- Load CSS -->
    <?php
      print $this->Html->css('jquery/jquery-ui-1.12.1.custom/jquery-ui.min') . "\n    ";
      print $this->Html->css('mdl/mdl-1.3.0/material.css') . "\n    ";
      print $this->Html->css('co-base') . "\n    ";
      print $this->Html->css('co-responsive') . "\n    ";
    ?>

    <!-- Load JavaScript -->
    <?php /* only JQuery here - other scripts at bottom */
      print $this->Html->script('jquery/jquery-3.2.1.min.js') . "\n    ";
      print $this->Html->script('jquery/jquery-ui-1.12.1.custom/jquery-ui.min.js') . "\n    ";
    ?>

    <!-- Include external files and scripts -->
    <?php
      print $this->fetch('meta');
      print $this->fetch('css');
      print $this->fetch('script');
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

    <meta http-equiv="refresh" content="1;URL='<?php print $this->Html->url($vv_meta_redirect_target); ?>'" />

    <!-- Include custom CSS -->
    <?php if(!empty($vv_theme_css)): ?>
      <style type="text/css">
        <?php print $vv_theme_css; ?>
      </style>
    <?php endif; ?>
  </head>
  <?php
    // cleanse the controller and action strings and insert them into the body classes
    $controller_stripped = preg_replace('/[^a-zA-Z0-9\-_]/', '', $this->params->controller);
    $action_stripped = preg_replace('/[^a-zA-Z0-9\-_]/', '', $this->params->action);
    $bodyClasses = $controller_stripped . ' ' .$action_stripped;

    if($this->Session->check('Auth.User') != NULL) {
      $bodyClasses .= ' logged-in';
    } else {
      $bodyClasses .= ' logged-out';
    }
    if(!empty($vv_NavLinks) || !empty($vv_CoNavLinks)) {
      $bodyClasses .=  ' with-user-defined-links';
    }
    if(!empty($vv_theme_header)) {
      $bodyClasses .=  ' with-custom-header';
    }
    if(!empty($vv_theme_footer)) {
      $bodyClasses .=  ' with-custom-footer';
    }
    if(!empty($vv_theme_hide_title)) {
      $bodyClasses .=  ' title-hidden';
    }
    if(!empty($vv_theme_hide_footer_logo)) {
      $bodyClasses .=  ' footer-hidden';
    }
  ?>
  <body class="redirect <?php print $bodyClasses ?>">
    <div id="skip-to-content-box">
      <a href="#content-start" id="skip-to-content">Skip to main content.</a>
    </div>      
    
    <!-- Include custom header -->
    <?php if(!empty($vv_theme_header)): ?>
      <header id="customHeader">
        <div class="contentWidth">
          <?php print $vv_theme_header; ?>
        </div>
      </header>
    <?php endif; ?>

    <!-- Primary layout -->
    <div id="comanage-wrapper" class="mdl-layout mdl-js-layout mdl-layout--fixed-drawer">

      <div id="top-menu">
        <?php if($this->Session->check('Auth.User')): ?>
          <div id="desktop-hamburger"><em class="material-icons">menu</em></div>
        <?php endif; ?>
        <?php if(!empty($vv_NavLinks) || !empty($vv_CoNavLinks)): ?>
          <div id="user-defined-links-top">
            <?php print $this->element('links'); // XXX allow user to set this location (e.g. top or side) ?>
          </div>
        <?php endif; ?>
        <nav id="user-menu">
          <?php print $this->element('menuUser'); ?>
        </nav>
      </div>

      <header id="banner" class="mdl-layout__header mdl-layout__header--scroll">
        <div class="mdl-layout__header-row">
          <?php if(!isset($vv_theme_hide_title) || !$vv_theme_hide_title): ?>
            <div id="collaborationTitle">
              <?php
                if(!empty($cur_co['Co']['name'])) {
                  $args = array();
                  $args['plugin'] = null;
                  $args['controller'] = 'co_dashboards';
                  $args['action'] = 'dashboard';
                  $args['co'] = $cur_co['Co']['id'];
                  print $this->Html->link($cur_co['Co']['name'],$args);
                } else {
                  print _txt('coordinate');
                }
              ?>
            </div>
          <?php endif; // $vv_theme_hide_title ?>

          <div id="logo">
            <?php
              $imgFile = 'COmanage-Logo-LG-onBlue.png';

              if(is_readable(APP . WEBROOT_DIR . DS . 'img' . DS . 'logo.png')) {
                // A custom logo has been installed, so use that instead
                $imgFile = 'logo.png';
              }

              // Clicking on the logo will take us to the front page
              print $this->Html->link(
                $this->Html->image(
                  $imgFile,
                  array(
                    'alt' => 'COmanage Logo'
                  )
                ),'/',
                array('escape' => false)
              );
            ?>
          </div>
        </div>

      </header>

      <main id="main" class="mdl-layout__content">

        <div id="content" class="mdl-grid">
          <div id="content-inner" class="mdl-cell mdl-cell--12-col">
            <div id="redirect-box">
              <div id="redirect-box-content">
                <?php print $this->fetch('content'); ?>
              </div>
              <div id="redirect-spinner"></div>
            </div>
          </div>
        </div>

        <?php if(Configure::read('debug') > 0): ?>
          <div id="debug" class="mdl-grid">
            <?php print $this->element('sql_dump'); ?>
          </div>
        <?php endif; ?>
      </main>

      <?php if(!isset($vv_theme_hide_footer_logo) || !$vv_theme_hide_footer_logo): ?>
        <footer id="co-footer">
          <?php print $this->element('footer'); ?>
        </footer>
      <?php endif; ?>

      <!-- Include custom footer -->
      <?php if(!empty($vv_theme_footer)): ?>
        <footer id="customFooter">
          <?php print $vv_theme_footer; ?>
        </footer>
      <?php endif; ?>

    </div>

    <!-- Load JavaScript -->
    <?php
      print $this->Html->script('mdl/mdl-1.3.0/material.min.js') . "\n    ";
      print $this->Html->script('jquery/spin.min.js') . "\n    ";
    ?>
  </body>
</html>
