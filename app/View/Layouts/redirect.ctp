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
      print $this->Html->css('jquery/jquery-ui-1.13.2.custom/jquery-ui.min') . "\n    ";
      print $this->Html->css('bootstrap/bootstrap-4.5.3-dist/css/bootstrap.min.css') . "\n    ";
      print $this->Html->css('co-color') . "\n    ";
      print $this->Html->css('co-base') . "\n    ";
      print $this->Html->css('co-responsive') . "\n    ";
    ?>

    <!-- Load JavaScript -->
    <?php /* only JQuery and Bootstrap here - other scripts at bottom */
      print $this->Html->script('jquery/jquery-3.5.1.min.js') . "\n    ";
      print $this->Html->script('bootstrap/bootstrap-4.5.3-dist/js/bootstrap.min.js') . "\n    ";
      print $this->Html->script('jquery/jquery-ui-1.13.2.custom/jquery-ui.min.js') . "\n    ";
    ?>

    <!-- Include external files and scripts -->
    <?php
      print $this->fetch('meta');
      print $this->fetch('css');
      print $this->fetch('script');
    ?>

    <meta http-equiv="refresh" content="1;URL='<?php print $this->Html->url($vv_meta_redirect_target); ?>'" />

    <!-- Include custom CSS -->
    <?php if(!empty($vv_theme_css)): ?>
      <style type="text/css">
        <?php
        if(is_array($vv_theme_css)) {
            foreach ($vv_theme_css as $theme_css) {
              print $theme_css . PHP_EOL . PHP_EOL;
            }
        }
        ?>
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
    if(!empty($vv_ui_mode)) {
      if($vv_ui_mode === EnrollmentFlowUIMode::Basic) {
        $bodyClasses .= ' ui-mode-basic';
      } else {
        $bodyClasses .= ' ui-mode-full';
      }
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
    <div id="comanage-wrapper">

      <div id="top-menu">
        <?php if(!empty($vv_NavLinks) || !empty($vv_CoNavLinks)): ?>
          <div id="user-defined-links-top">
            <?php print $this->element('links'); // XXX allow user to set this location (e.g. top or side) ?>
          </div>
        <?php endif; ?>
        <nav id="user-menu">
          <?php print $this->element('menuUser'); ?>
        </nav>
      </div>

      <header id="banner">
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

      </header>

      <?php
        $mainCssClasses = 'cm-main-full';
        if(!empty($vv_ui_mode)) {
          if($vv_ui_mode === EnrollmentFlowUIMode::Basic) {
            $mainCssClasses = 'cm-main-basic';
          }
        }
      ?>
      <main id="main" class="<?php print $mainCssClasses; ?>">

        <div id="content">
          <div id="content-inner">
            <div id="redirect-box">
              <?php print $this->fetch('content'); ?>
            </div>
          </div>
        </div>

        <!-- Include custom footer -->
        <?php if(!empty($vv_theme_footer)): ?>
          <footer id="customFooter">
            <?php print $vv_theme_footer; ?>
          </footer>
        <?php endif; ?>

        <?php if(Configure::read('debug') > 0): ?>
          <div id="debug">
            <?php print $this->element('sql_dump'); ?>
          </div>
        <?php endif; ?>
      </main>

      <?php if(!isset($vv_theme_hide_footer_logo) || !$vv_theme_hide_footer_logo): ?>
        <footer id="co-footer">
          <?php print $this->element('footer'); ?>
        </footer>
      <?php endif; ?>

    </div>
  </body>
</html>
