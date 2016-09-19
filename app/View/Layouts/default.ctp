<?php
/**
 * COmanage Registry Default Layout
 *
 * Copyright (C) 2011-15 University Corporation for Advanced Internet Development, Inc.
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
 * @copyright     Copyright (C) 2011-15 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.1
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
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
    <?php print $this->Html->meta(array('name' => 'viewport', 'content' => 'width=device-width, initial-scale=1.0',  'http-equiv' => "X-UA-Compatible")) . "\n"; ?>
    <?php print $this->Html->charset() . "\n"; ?>

    <title><?php print _txt('coordinate') . ': ' . $title_for_layout; ?></title>
    <!-- <?php
      // Include version number, but only if logged in
      if($this->Session->check('Auth.User')) {
        print _txt('coordinate.version') . ' ' . chop(file_get_contents(APP . "Config/VERSION"));
      }
    ?> -->

    <?php print $this->Html->meta('favicon.ico','/favicon.ico',array('type' => 'icon')) . "\n"; ?>

    <!-- Load CSS -->
    <?php
      print $this->Html->css('jquery/jquery-ui-1.11.4.custom/jquery-ui.min') . "\n    ";
      print $this->Html->css('jquery/jquery-ui-1.11.4.custom/jquery-ui-comanage-overrides') . "\n    ";
      print $this->Html->css('mdl/mdl-1.2.0/material.min.css') . "\n    ";
      print $this->Html->css('mdl/mdl-selectfield-1.0.2/mdl-selectfield.min.css') . "\n    ";
      print $this->Html->css('jquery/metisMenu/metisMenu.min.css') . "\n    ";
      print $this->Html->css('fonts/Font-Awesome-4.6.3/css/font-awesome.min') . "\n    ";
      print $this->Html->css('co-base') . "\n    ";
      print $this->Html->css('co-responsive') . "\n    ";
      // load legacy styles while site is undergoing layout transition
      print $this->Html->css('co-legacy') . "\n    ";
      
      // Until used more broadly, limit loading of Magnific Popup
      if ($this->controller = 'history_records') {
        print $this->Html->css('jquery/magnificpopup/magnific-popup');
      }
    ?>

    <!-- Load JavaScript -->
    <?php /* only JQuery here - other scripts at bottom */
      print $this->Html->script('jquery/jquery-1.11.3.min.js') . "\n    ";
      print $this->Html->script('jquery/jquery-ui-1.11.4.custom/jquery-ui.min.js') . "\n    ";
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
    $bodyClasses = $this->params->controller . ' ' . $this->params->action;
    if($this->Session->check('Auth.User') != NULL) {
      $bodyClasses .= ' logged-in';
    } else {
      $bodyClasses .= ' logged-out';
    }
    if(!empty($vv_NavLinks) || !empty($vv_CoNavLinks)) {
      $bodyClasses .=  ' with-user-defined-links';
    }
  ?>
  <body class="<?php print $bodyClasses ?>" onload="js_onload_call_hooks()">
    <div id="skip-to-content-box">
      <a href="#content-start" id="skip-to-content">Skip to main content.</a>
    </div>
    <div id="comanage-wrapper" class="mdl-layout mdl-js-layout mdl-layout--fixed-drawer">
      <!-- Include custom header -->
      <?php if(!empty($vv_theme_header)): ?>
        <header id="customHeader">
          <div class="contentWidth">
            <?php print $vv_theme_header; ?>
          </div>
        </header>
      <?php endif; ?>
      <?php if(!empty($vv_NavLinks) || !empty($vv_CoNavLinks)): ?>
        <div id="user-defined-links-top">
          <?php print $this->element('links'); // XXX allow user to set this location (e.g. top or side) ?>
        </div>
      <?php endif; ?>
      <?php if(!isset($vv_theme_hide_title) || !$vv_theme_hide_title): ?>
        <header id="banner" role="banner" class="mdl-layout__header mdl-layout__header--scroll">
          <div class="mdl-layout__header-row">
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
      <?php endif; // $vv_theme_hide_title ?>

      <?php if($this->Session->check('Auth.User')): ?>
        <div id="navigation-drawer" class="mdl-layout__drawer">
          <nav id="navigation" role="navigation" aria-label="main menu" class="mdl-navigation">
            <?php print $this->element('menuMain'); ?>
            <?php if(!empty($vv_NavLinks) || !empty($vv_CoNavLinks)): ?>
              <div id="user-defined-links-left">
                <?php print $this->element('links'); // XXX allow user to set this location (e.g. top or side) ?>
              </div>
            <?php endif; ?>
          </nav>
        </div>
      <?php endif ?>

      <nav id="user-menu">
        <?php print $this->element('menuUser'); ?>
      </nav>

      <main id="main" class="mdl-layout__content">
        <div id="collaborationTitle">
        <?php
          if(!empty($cur_co['Co']['name'])) {
            print Sanitize::html($cur_co['Co']['name']);
          } else {
            print _txt('coordinate');
          }
        ?>
        </div>

        <div id="content" class="mdl-grid">
        <?php

          // display the view content
          if(!empty($sidebarButtons) || !empty($enrollmentFlowSteps)) {
            print '<div id="content-inner" class="mdl-cell mdl-cell--9-col">';
          } else {
            print '<div id="content-inner" class="mdl-cell mdl-cell--12-col">';
          }

          // insert breadcrumbs on all but the homepage if logged in
          if($this->Session->check('Auth.User')) {
            if ($this->request->here != $this->request->webroot) {
              print '<div id="breadcrumbs">' . $this->Html->getCrumbs(' &gt; ', _txt('bc.home')) . "</div>";
            }
          }

          // insert the anchor that is the target of accessible "skip to content" link
          print '<a name="content-start" id="content-start"></a>';

          // insert the page internal content
          print $this->fetch('content');
          print '</div>';

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
      
      <?php if(Configure::read('debug') > 0): ?>
        <div>
          <?php print $this->element('sql_dump'); ?>
        </div>
      <?php endif; ?>
    </div>

    <!-- Load JavaScript -->
    <?php
      print $this->Html->script('mdl/mdl-1.2.0/material.min.js') . "\n    ";
      print $this->Html->script('mdl/mdl-selectfield-1.0.2/mdl-selectfield.min.js') . "\n    ";
      print $this->Html->script('jquery/metisMenu/metisMenu.min.js') . "\n    ";
      print $this->Html->script('js-cookie/js.cookie-2.1.3.min.js') . "\n    ";
      print $this->Html->script('jquery/spin.min.js') . "\n    ";
      if ($this->controller = 'history_records') {
        // Until used more broadly, limit loading of Magnific Popup
        print $this->Html->script('jquery/magnificpopup/jquery.magnific-popup.min.js');
      }
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

      <!-- COmanage JavaScript library and onload scripts -->
      <?php
        print $this->Html->script('comanage.js') . "\n    ";
        print $this->element('javascript');
      ?>

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
