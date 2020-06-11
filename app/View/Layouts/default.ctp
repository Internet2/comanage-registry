<?php
/**
 * COmanage Registry Default Layout
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
 * @since         COmanage Registry v0.1
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

    <title><?php print filter_var($title_for_layout,FILTER_SANITIZE_STRING); ?></title>
    <!-- <?php
      // Include version number, but only if logged in
      if($this->Session->check('Auth.User')) {
        print _txt('coordinate.version') . ' ' . chop(file_get_contents(APP . "Config/VERSION"));
      }
    ?> -->

    <?php print $this->Html->meta('favicon.ico','/favicon.ico',array('type' => 'icon')) . "\n"; ?>

    <!-- Allow pages to request periodic refresh -->
    <?php
    if(!empty($vv_refresh_interval)) {
      print $this->Html->meta(array(
        'http-equiv' => 'refresh',
        'content' => $vv_refresh_interval)
      );
    }
    ?>

    <!-- Load CSS -->
    <?php
      print $this->Html->css('jquery/jquery-ui-1.12.1.custom/jquery-ui.min') . "\n    ";
      print $this->Html->css('mdl/mdl-1.3.0/material.min.css') . "\n    ";
      print $this->Html->css('jquery/metisMenu/metisMenu.min.css') . "\n    ";
      print $this->Html->css('fonts/Font-Awesome-4.6.3/css/font-awesome.min') . "\n    ";
      print $this->Html->css('co-base') . "\n    ";
      print $this->Html->css('co-responsive') . "\n    ";

      // Until used more broadly, limit loading of Magnific Popup
      if ($this->controller = 'history_records') {
        print $this->Html->css('jquery/magnificpopup/magnific-popup');
      }
    ?>

    <!-- Load JavaScript -->
    <?php /* only JQuery here - other scripts at bottom */
      print $this->Html->script('jquery/jquery-3.5.1.min.js') . "\n    ";
      print $this->Html->script('jquery/jquery-ui-1.12.1.custom/jquery-ui.min.js') . "\n    ";
    ?>

    <!-- Include external files and scripts -->
    <?php
      print $this->fetch('meta');
      print $this->fetch('css');
      print $this->fetch('script');
    ?>

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

    // add further body classes as needed
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
  <body class="<?php print $bodyClasses ?>" onload="js_onload_call_hooks()">
    <div id="skip-to-content-box">
      <a href="#content-start" id="skip-to-content">Skip to main content.</a>
    </div>

    <!-- Primary layout -->
    <div id="comanage-wrapper" class="mdl-layout mdl-js-layout mdl-layout--fixed-drawer">

      <!-- Include custom header -->
      <?php if(!empty($vv_theme_header)): ?>
        <header id="customHeader">
          <div class="contentWidth">
            <?php print $vv_theme_header; ?>
          </div>
        </header>
      <?php endif; ?>

      <div id="top-menu">
        <?php if($vv_ui_mode === EnrollmentFlowUIMode::Full): ?>
          <button id="desktop-hamburger" class="cm-toggle" aria-controls="navigation-drawer"><em class="material-icons">menu</em></button>
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

      <?php if($vv_ui_mode === EnrollmentFlowUIMode::Full): ?>
        <div id="navigation-drawer" class="mdl-layout__drawer">
          <nav id="navigation" aria-label="main menu" class="mdl-navigation">
            <?php print $this->element('menuMain'); ?>
            <?php if(!empty($vv_NavLinks) || !empty($vv_CoNavLinks)): ?>
              <div id="user-defined-links-left">
                <?php print $this->element('links'); // XXX allow user to set this location (e.g. top or side) ?>
              </div>
            <?php endif; ?>
          </nav>
        </div>
      <?php endif ?>

      <?php
        $mainCssClasses = 'cm-main-full mdl-layout__content';
        if(!empty($vv_ui_mode)) {
          if($vv_ui_mode === EnrollmentFlowUIMode::Basic) {
            $mainCssClasses = 'cm-main-basic';
          }
        }
      ?>
      <main id="main" class="<?php print $mainCssClasses; ?>">

        <div id="content" class="mdl-grid">
        <?php

          // display the view content
          if(!empty($sidebarButtons) || !empty($enrollmentFlowSteps)) {
            print '<div id="content-inner" class="mdl-cell mdl-cell--9-col">';
          } else {
            print '<div id="content-inner" class="mdl-cell mdl-cell--12-col">';
          }

          // insert breadcrumbs on all but the homepage
          if( $vv_ui_mode === EnrollmentFlowUIMode::Full
              && $this->request->here !== $this->request->webroot) {
            print '<div id="breadcrumbs">' . $this->Html->getCrumbs(' &gt; ', _txt('bc.home')) . "</div>";
          }

          // insert the anchor that is the target of accessible "skip to content" link
          print '<a id="content-start"></a>';

          // insert the page internal content
          print $this->fetch('content');
          print '</div>'; // end #content-inner

          if(!empty($sidebarButtons) || !empty($enrollmentFlowSteps)) {
            print '<div id="right-sidebar" class="mdl-cell mdl-cell--3-col mdl-cell--9-col-tablet mdl-cell--9-col-phone">';

            // insert the sidebar buttons if they exist
            $sidebarButtons = $this->get('sidebarButtons');
            if (!empty($sidebarButtons)) {
              print $this->element('sidebarButtons');
            }

            // display enrollment flow steps when they exist
            $enrollmentFlowSteps = $this->get('enrollmentFlowSteps');
            if (!empty($enrollmentFlowSteps)) {
              print $this->element('enrollmentFlowSteps');
            }
            print "</div>";
          }
        ?>
        </div>

        <!-- Include custom footer -->
        <?php if(!empty($vv_theme_footer)): ?>
          <footer id="customFooter">
            <?php print $vv_theme_footer; ?>
          </footer>
        <?php endif; ?>

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

    </div>

    <!-- Load JavaScript -->
    <?php
      print $this->Html->script('mdl/mdl-1.3.0/material.min.js') . "\n    ";
      print $this->Html->script('jquery/metisMenu/metisMenu.min.js') . "\n    ";
      print $this->Html->script('js-cookie/js.cookie-2.1.3.min.js') . "\n    ";
      print $this->Html->script('jquery/spin.min.js') . "\n    ";
      if ($this->controller = 'history_records') {
        // Until used more broadly, limit loading of Magnific Popup
        print $this->Html->script('jquery/magnificpopup/jquery.magnific-popup.min.js') . "\n    ";
      }
      print $this->Html->script('comanage.js') . "\n    ";
    ?>

    <!-- Get timezone detection -->
    <?php print $this->Html->script('jstimezonedetect/jstz.min.js'); ?>
    <script type="text/javascript">
      // Determines the time zone of the browser client
      var tz = jstz.determine();
      // This won't be available for the first delivered page, but after that the
      // server side should see it and process it
      document.cookie = "cm_registry_tz_auto=" + tz.name() + "; path=/";
    </script>


    <?php if($this->here != '/registry/pages/eds/index'):
      // Don't load the following scripts when loading the Shib EDS. ?>

      <!-- noty scripts -->
      <?php
        print $this->Html->script('jquery/noty/jquery.noty.js') . "\n    ";
        print $this->Html->script('jquery/noty/layouts/topCenter.js') . "\n    ";
        print $this->Html->script('jquery/noty/themes/comanage.js') . "\n    ";
      ?>

      <!-- COmanage JavaScript onload scripts -->
      <?php print $this->element('javascript'); ?>

      <!-- Common UI components -->
      <div id="dialog" title="Confirm" role="alertdialog">
        <p>
          <span class="ui-icon ui-icon-alert co-alert"></span>
          <span id="dialog-text"><?php print _txt('op.proceed.ok'); ?></span>
        </p>
      </div>
    <?php endif // !eds ?>

  </body>
</html>
