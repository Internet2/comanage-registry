<?php
/**
 * COmanage Registry Lightbox Layout
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
 * @since         COmanage Registry v1.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
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
    <title><?php print _txt('coordinate') . ': ' . filter_var($title_for_layout,FILTER_SANITIZE_STRING)?></title>
    <?php print $this->Html->charset(); ?>
    <?php print $this->Html->meta('favicon.ico','/favicon.ico',array('type' => 'icon')); ?>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, minimum-scale=1, user-scalable=no" />

    <!-- Load CSS -->
    <?php
    print $this->Html->css('jquery/jquery-ui-1.12.1.custom/jquery-ui.min') . "\n    ";
    print $this->Html->css('mdl/mdl-1.3.0/material.min.css') . "\n    ";
    print $this->Html->css('fonts/Font-Awesome-4.6.3/css/font-awesome.min') . "\n    ";
    print $this->Html->css('co-base');
    print $this->Html->css('co-responsive');
    print $this->Html->css('co-lightbox');
    ?>

    <!-- Load JavaScript -->
    <?php
    print $this->Html->script('jquery/jquery-3.5.1.min.js') . "\n    ";
    print $this->Html->script('jquery/jquery-ui-1.12.1.custom/jquery-ui.min.js') . "\n    ";
    ?>

    <!-- Get timezone detection -->
    <?php print $this->Html->script('jstimezonedetect/jstz.min.js'); ?>
    <script type="text/javascript">
      // Determines the time zone of the browser client
      var tz = jstz.determine();
      // This won't be available for the first delivered page, but after that the
      // server side should see it and process it
      document.cookie = "cm_registry_tz_auto=" + tz.name() + "; path=/; SameSite=Strict";
    </script>


    <?php if($this->here != $this->Html->url('/') .'pages/eds/index'):
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
    <?php endif // !eds ?>

      <!-- Include external files and scripts -->
      <?php
      print $this->fetch('meta');
      print $this->fetch('css');
      print $this->fetch('script');
      ?>
  </head>

  <?php
    // cleanse the controller and action strings and insert them into the body classes
    $controller_stripped = preg_replace('/[^a-zA-Z0-9\-_]/', '', $this->params->controller);
    $action_stripped = preg_replace('/[^a-zA-Z0-9\-_]/', '', $this->params->action);
    $bodyClasses = $controller_stripped . ' ' .$action_stripped;
  ?>

  <body class="<?php print $bodyClasses ?>" onload="js_onload_call_hooks()">

    <div id="lightboxContent">
      <?php
        // insert the page internal content
        print $this->fetch('content');
      ?>
    </div>

    <?php if(Configure::read('debug') > 0): ?>
      <div>
        <?php print $this->element('sql_dump'); ?>
      </div>
    <?php endif; ?>
  </body>
</html>
