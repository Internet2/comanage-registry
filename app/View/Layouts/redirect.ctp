<?php
/**
 * COmanage Registry Redirect View
 *
 * Copyright (C) 2016 University Corporation for Advanced Internet Development, Inc.
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
 * @copyright     Copyright (C) 2016 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v1.0.3
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

    <title><?php print _txt('op.processing'); ?></title>
    <?php print $this->Html->meta('favicon.ico','/favicon.ico',array('type' => 'icon')) . "\n"; ?>

    <!-- Load CSS -->
    <?php
      print $this->Html->css('jquery/jquery-ui-1.11.4.custom/jquery-ui.min') . "\n    ";
      print $this->Html->css('jquery/jquery-ui-1.11.4.custom/jquery-ui-comanage-overrides') . "\n    ";
      print $this->Html->css('mdl/mdl-1.2.0/material.css') . "\n    ";
      print $this->Html->css('co-base') . "\n    ";
      print $this->Html->css('co-responsive') . "\n    ";
      // load legacy styles while site is undergoing layout transition
      print $this->Html->css('co-legacy') . "\n    ";
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

  </head>
  <body class="redirect">

  <?php
    $bodyClasses = $this->params->controller . ' ' . $this->params->action . ' ';
    if($this->Session->check('Auth.User') != NULL) {
      $bodyClasses .= 'logged-in';
    } else {
      $bodyClasses .= 'logged-out';
    }
  ?>
  <body class="<?php print $bodyClasses ?>" onload="js_onload_call_hooks()">
    <div id="skip-to-content-box">
      <a href="#content-start" id="skip-to-content">Skip to main content.</a>
      </div>
      <div id="comanage-wrapper" class="mdl-layout mdl-js-layout mdl-layout--fixed-drawer">
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
          <div id="content-inner" class="mdl-cell mdl-cell--12-col">
            <div id="redirect-box">
              <div id="redirect-box-content">
                <?php print $this->fetch('content'); ?>
              </div>
              <div id="redirect-spinner"></div>
            </div>
          </div>
        </div>
      </main>

      <footer>
        <?php print $this->element('footer'); ?>
      </footer>

    </div>

    <!-- Load JavaScript -->
    <?php
      print $this->Html->script('mdl/mdl-1.2.0/material.min.js') . "\n    ";
      print $this->Html->script('jquery/spin.min.js') . "\n    ";
    ?>
  </body>
</html>
