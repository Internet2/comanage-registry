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
?>
<!DOCTYPE html>
<html>
  <head>
    <!-- <?php
      // Include version number, but only if logged in
      if($this->Session->check('Auth.User')) {
        print chop(file_get_contents(APP . "Config/VERSION"));
      }
    ?> -->
    <title><?php print _txt('coordinate') . ': ' . $title_for_layout?></title>
    <?php print $this->Html->charset(); ?>
    <?php print $this->Html->meta('favicon.ico','/favicon.ico',array('type' => 'icon')); ?>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, minimum-scale=1, user-scalable=no" />

    <!-- Include the comanage and jquery style sheets -->
    <?php
      print $this->Html->css('jquery/jquery-ui-1.11.4.custom/jquery-ui.min');
      print $this->Html->css('jquery/jquery-ui-1.11.4.custom/jquery-ui-comanage-overrides');
      print $this->Html->css('jquery/superfish/css/superfish');
      print $this->Html->css('comanage');
      print $this->Html->css('comanage-responsive');
      if ($this->controller = 'history_records') {
        // Until used more broadly, limit loading of Magnific Popup
        print $this->Html->css('jquery/magnificpopup/magnific-popup');
      }
    ?>

    <!-- Get jquery code -->
    <?php
      print $this->Html->script('jquery/jquery-1.11.3.min.js');
      print $this->Html->script('jquery/jquery-ui-1.11.4.custom/jquery-ui.min.js');
      print $this->Html->script('jquery/superfish/js/superfish.js');
      print $this->Html->script('jquery/spin.min.js');
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
        print $this->Html->script('jquery/noty/jquery.noty.js');
        print $this->Html->script('jquery/noty/layouts/topCenter.js');
        print $this->Html->script('jquery/noty/themes/comanage.js');
      ?>
      <!-- COmanage JavaScript library and onload scripts -->
      <?php
        print $this->Html->script('comanage.js');
        print $this->element('javascript');
      ?>
      <!-- Include external files and scripts -->
      <?php
        print $this->fetch('meta');
        print $this->fetch('css');
        print $this->fetch('script');
      ?>
    <?php endif // !eds ?>
  </head>

  <body class="<?php print $this->params->controller . ' ' . $this->params->action ?>"
        onload="js_onload_call_hooks()">
    <div class="header">
      <div id="row1">
        <div class="contentWidth">
          <?php print $this->element('secondaryMenu'); ?>
          <?php print $this->element('links'); ?>
        </div>
      </div>

      <div id="row2" class="ui-widget-header">
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
                    'alt' => 'COmanage',
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
                print "<h1>" . Sanitize::html($cur_co['Co']['name']) . "</h1>"; // more to go here.
              } else {
                print "<h1>" . _txt('coordinate') . "</h1>";
              }
            ?>
            <div id="coSelector">
              
            </div>
          </div>
        </div>
      </div>
      
      <?php if($this->Session->check('Auth.User')): ?>
        <div id="row3">
          <div class="contentWidth">
            <?php print $this->element('dropMenu'); ?>
          </div>
        </div>
      <?php endif ?>
      
    </div>

    <div id="main" class="contentWidth">
      <?php
        // insert the sidebar buttons if they exist
        $sidebarButtons = $this->get('sidebarButtons');
        if(!empty($sidebarButtons)) {
          print $this->element('sidebarButtons');
        }

        // display enrollment flow steps when they exist
        $enrollmentFlowSteps = $this->get('enrollmentFlowSteps');
        if(!empty($enrollmentFlowSteps)) {
          print $this->element('enrollmentFlowSteps');
        }

        // display the view content
        if(!empty($sidebarButtons) || !empty($enrollmentFlowSteps)) {
          print '<div id="content" class="contentWithSidebar">';
        } else {
          print '<div id="content">';
        }

        // insert breadcrumbs on all but the homepage if logged in
        if($this->Session->check('Auth.User')) {
          if ($this->request->here != $this->request->webroot) {
            print '<div id="breadcrumbs">' . $this->Html->getCrumbs(' > ', _txt('bc.home')) . "</div>";
          }
        }

        // insert the page internal content
        print $this->fetch('content');

        // close the view content div
        print "</div>";
      ?>

    </div>

    <!-- Common UI components -->
    <?php if($this->here != '/registry/pages/eds/index'):
      // Don't load the following UI component when loading the Shib EDS. ?>
      <div id="dialog" title="Confirm">
        <p>
          <span class="ui-icon ui-icon-alert co-alert"></span>
          <span id="dialog-text"><?php print _txt('op.proceed.ok'); ?></span>
        </p>
      </div>
    <?php endif; ?>

    <div class="contentWidth">
      <?php print $this->element('footer'); ?>
    </div>

    <?php if(Configure::read('debug') > 0): ?> 
      <div>
        <?php print $this->element('sql_dump'); ?>
      </div>
    <?php endif; ?>
  </body>
</html>
