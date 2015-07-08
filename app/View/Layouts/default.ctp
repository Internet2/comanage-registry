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
    <?php echo $this->Html->charset(); ?>
    <?php echo $this->Html->meta('favicon.ico','/favicon.ico',array('type' => 'icon')); ?>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, minimum-scale=1, user-scalable=no" />

    <!-- Include the comanage and jquery style sheets -->
    <?php print $this->Html->css('jquery/ui/css/comanage-theme/jquery-ui-1.10.0.comanage'); ?>
    <?php print $this->Html->css('jquery/superfish/css/superfish'); ?>
    <?php print $this->Html->css('comanage'); ?>
    <?php print $this->Html->css('comanage-responsive'); ?>

    <!-- Get jquery code -->
    <?php print $this->Html->script('jquery/ui/js/jquery-1.9.0.js'); ?>
    <?php print $this->Html->script('jquery/ui/js/jquery-ui-1.10.0.custom.min.js'); ?>
    <?php print $this->Html->script('jquery/superfish/js/superfish.js'); ?>
    <?php print $this->Html->script('jquery/spin.min.js'); ?>

    <script type="text/javascript">
    function js_onload_call_hooks() {
      // On page load, call any defined initialization functions.
      // Make sure function is defined before calling.
      
      if(window.js_local_onload) {
        js_local_onload();
      }
    }
    </script>

    <?php if($this->here != '/registry/shibboleth_embedded_discovery_service/eds/view'):
      // Don't load the following scripts when loading the Shib EDS. ?>

      <?php print $this->Html->script('jquery/noty/jquery.noty.js'); ?>
      <?php print $this->Html->script('jquery/noty/layouts/topCenter.js'); ?>
      <?php print $this->Html->script('jquery/noty/themes/comanage.js'); ?>

      <!-- Common script code -->
      <script type="text/javascript">

      function generateFlash(text, type) {
        var n = noty({
          text: text,
          type: type,
          dismissQueue: true,
          layout: 'topCenter',
          theme: 'comanage'
        });
      }

      // Function to confirm delete and then hand off
      function js_confirm_delete(name, url) {
        // Generate a dialog box confirming the removal of <name>.  On confirmation, forward to <url>, which executes the delete.

        // Set the title of the dialog
        $("#dialog").dialog("option", "title", "<?php print _txt('op.remove'); ?>" + " " + name);

        // Set the body of the dialog
        // XXX need to I18N this, but arg passing currently only works within php not javascript
        $("#dialog-text").text("Are you sure you wish to remove " + name + "?  This action cannot be undone.");

        // Set the dialog buttons
        $("#dialog").dialog("option",
                            "buttons",
                            {
                              "<?php print _txt('op.cancel'); ?>": function() { $(this).dialog("close"); },
                              "<?php print _txt('op.remove'); ?>": function() { window.location=url; }
                            });

        // Open the dialog
        $('#dialog').dialog('open');
      }

      function js_confirm_generic(txt, url, confirmbtxt, cancelbtxt) {
        // Generate a dialog box confirming <txt>.  On confirmation, forward to <url>.
        // Use confirmbtxt and cancelbtxt as text for the buttons, if provided.

        var confbutton = confirmbtxt;
        var cxlbutton = cancelbtxt;

        if(confbutton == undefined) confbutton = "<?php print _txt('op.ok'); ?>";
        if(cxlbutton == undefined) cxlbutton = "<?php print _txt('op.cancel'); ?>";

        // Set the title of the dialog
        $("#dialog").dialog("option", "title", "<?php print _txt('op.confirm'); ?>");

        // Set the body of the dialog
        $("#dialog-text").text(txt);

        // Set the dialog buttons
        var dbuttons = {};
        dbuttons[cxlbutton] = function() { $(this).dialog("close"); };
        dbuttons[confbutton] = function() { window.location=url; };

        $("#dialog").dialog("option",
                            "buttons",
                            dbuttons);

        // Open the dialog
        $('#dialog').dialog('open');
      }

      function js_confirm_reinvite(name, url) {
        // Generate a dialog box confirming a resend of an invitation to <name>.  On confirmation, forward to <url>, which executes the invite.

        // Set the title of the dialog
        $("#dialog").dialog("option", "title", "<?php print _txt('op.inv.resend'); ?>");

        // Set the body of the dialog
        // XXX need to I18N this, but arg passing currently only works within php not javascript
        $("#dialog-text").text("Are you sure you wish to resend an invitation to " + name + "?  Any previous invitation will be invalidated.");

        // Set the dialog buttons
        $("#dialog").dialog("option",
                            "buttons",
                            {
                              "<?php print _txt('op.cancel'); ?>": function() { $(this).dialog("close"); },
                              "<?php print _txt('op.inv.resend'); ?>": function() { window.location=url; }
                            });

        // Open the dialog
        $('#dialog').dialog('open');
      }

      function js_confirm_verification(email, url) {
        // Generate a dialog box confirming a request to verify <email>. On confirmation, forward to <url>, which executes the sending.
        // XXX This should be merged with js_confirm_reinvite, above, perhaps as part of CO-753.

        // Set the title of the dialog
        $("#dialog").dialog("option", "title", "<?php print _txt('op.verify'); ?>");

        // Set the body of the dialog
        // XXX need to I18N this, but arg passing currently only works within php not javascript
        $("#dialog-text").text("Are you sure you wish to send a verification request to " + email + "? Any previous request will be invalidated.");

        // Set the dialog buttons
        $("#dialog").dialog("option",
                            "buttons",
                            {
                              "<?php print _txt('op.cancel'); ?>": function() { $(this).dialog("close"); },
                              "<?php print _txt('op.verify'); ?>": function() { window.location=url; }
                            });

        // Open the dialog
        $('#dialog').dialog('open');
      }


      function js_onsubmit_call_hooks() {
        // On form submit, call any defined functions.
        // Make sure function is defined before calling.

        if(window.js_local_onsubmit) {
          js_local_onsubmit();
        }
      }

      // jQuery stuff

      $(function() {
        // Focus any designated form element
        $(".focusFirst").focus();

        // Accordion
        $(".accordion").accordion();

        // Make all submit buttons pretty
        $("input:submit").button();
        $("form .submit").css('float','left');

        // Other buttons
        $(".addbutton").button({
          icons: {
            primary: 'ui-icon-circle-plus'
          },
          text: true
        });

        $(".autobutton").button({
          icons: {
            primary: 'ui-icon-script'
          },
          text: true
        });

        $(".backbutton").button({
          icons: {
            primary: 'ui-icon-circle-arrow-w'
          },
          text: true
        });

        $(".cancelbutton").button({
          icons: {
            primary: 'ui-icon-circle-close'
          },
          text: true
        });

        $(".checkbutton").button({
          icons: {
            primary: 'ui-icon-circle-check'
          },
          text: true
        });

        $(".comparebutton").button({
          icons: {
            primary: 'ui-icon-person'
          },
          text: true
        });

        $(".configurebutton").button({
          icons: {
            primary: 'ui-icon-pencil'
          },
          text: true
        });

        $(".copybutton").button({
          icons: {
            primary: 'ui-icon-copy'
          },
          text: true
        });

        $(".deletebutton").button({
          icons: {
            primary: 'ui-icon-circle-close'
          },
          text: true
        });

        $(".editbutton").button({
          icons: {
            primary: 'ui-icon-pencil'
          },
          text: true
        });

        $(".forwardbutton").button({
          icons: {
            primary: 'ui-icon-circle-arrow-e'
          },
          text: true
        });

        $(".historybutton").button({
          icons: {
            primary: 'ui-icon-note'
          },
          text: true
        });

        $(".invitebutton").button({
          icons: {
            primary: 'ui-icon-mail-closed'
          },
          text: true
        });

        $(".linkbutton").button({
          icons: {
            primary: 'ui-icon-extlink'
          },
          text: true
        });

        $(".logoutbutton").button({
          icons: {
            primary: 'ui-icon-power'
          },
          text: true
        });

        $(".menubutton").button({
          icons: {
            primary: 'ui-icon-home'
          },
          text: true
        });

        $(".menuitembutton").button({
          icons: {
            primary: 'ui-icon-circle-triangle-e'
          },
          text: true
        });

        $(".searchbutton").button({
          icons: {
            primary: 'ui-icon-search'
          },
          text: true
        });

        $(".petitionbutton").button({
          icons: {
            primary: 'ui-icon-script'
          },
          text: true
        });

        $(".provisionbutton").button({
          icons: {
            primary: 'ui-icon-gear'
          },
          text: true
        });

        $(".primarybutton").button({
          icons: {
            primary: 'ui-icon-arrowthickstop-1-n'
          },
          text: true
        });

        $(".relinkbutton").button({
          icons: {
            primary: 'ui-icon-link'
          },
          text: true
        });

        $("button:reset").button();
        $("button:reset").css('float', 'left');

        $(".unlinkbutton").button({
          icons: {
            primary: 'ui-icon-cancel'
          },
          text: true
        });

        $(".viewbutton").button({
          icons: {
            primary: 'ui-icon-extlink'
          },
          text: true
        });

        // Datepickers

        $(".datepicker").datepicker({
          changeMonth: true,
          changeYear: true,
          dateFormat: "yy-mm-dd",
          numberOfMonths: 1,
          showButtonPanel: false,
          showOtherMonths: true,
          selectOtherMonths: true
        });

        $(".datepicker-f").datepicker({
          changeMonth: true,
          changeYear: true,
          dateFormat: "yy-mm-dd 00:00:00",
          numberOfMonths: 1,
          showButtonPanel: false,
          showOtherMonths: true,
          selectOtherMonths: true
        });

        $(".datepicker-m").datepicker({
          changeMonth: true,
          dateFormat: "mm-dd",
          numberOfMonths: 1,
          showButtonPanel: false,
          showOtherMonths: true,
          selectOtherMonths: true
        });

        $(".datepicker-u").datepicker({
          changeMonth: true,
          changeYear: true,
          dateFormat: "yy-mm-dd 23:59:59",
          numberOfMonths: 1,
          showButtonPanel: false,
          showOtherMonths: true,
          selectOtherMonths: true
        });

        // Dialog
        // This generic dialog gets modified by the calling function

        $("#dialog").dialog({
          autoOpen: false,
          resizable: false,
          modal: true,
          buttons: {
            '<?php _txt('op.cancel'); ?>': function() {
              $(this).dialog('close');
            },
            '<?php _txt('op.ok'); ?>': function() {
              $(this).dialog('close');
            }
          }
        });

        // Add a spinner when a form is submitted or when any item is clicked with a "spin" class
        $("input[type='submit'],.spin").click(function() {

          var spinnerDiv = '<div id="coSpinner"></div>';
          $("body").append(spinnerDiv);

          var coSpinnerOpts = {
            lines: 13, // The number of lines to draw
            length: 20, // The length of each line
            width: 8, // The line thickness
            radius: 20, // The radius of the inner circle
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
          var coSpinnerTarget = document.getElementById('coSpinner');
          var coSpinner = new Spinner(coSpinnerOpts).spin(coSpinnerTarget);

          // Test for invalid fields (HTML5) and turn off spinner explicitly if found
          if(document.querySelectorAll(":invalid").length) {
            coSpinner.stop();
          }

        });

        // Turn on the sidebar menus
        $( "#menu" ).menu();

        // Flash Messages
        <?php
          $f = $this->Session->flash('error');

          if($f && $f != "") {
            print "generateFlash('". str_replace(array("'", "\n"), array("\'", ""), $f) . "', 'error');";
          }

          // auth = errors from AuthComponent
          $f = $this->Session->flash('auth');

          if($f && $f != "") {
            print "generateFlash('". str_replace(array("'", "\n"), array("\'", ""), $f) . "', 'error');";
          }

          $f = $this->Session->flash('info');

          if($f && $f != "") {
            print "generateFlash('". str_replace(array("'", "\n"), array("\'", ""), $f) . "', 'info');";
          }

          $f = $this->Session->flash('success');

          if($f && $f != "") {
            print "generateFlash('". str_replace(array("'", "\n"), array("\'", ""), $f) . "', 'success');";
          }

          $f = $this->Session->error();

          if($f && $f != "") {
            print "generateFlash('". str_replace(array("'", "\n"), array("\'", ""), $f) . "', 'error');";
          }
        ?>

      });

      </script>

    <?php endif; ?>
    
    <!-- Include external files and scripts -->
    <?php
      print $this->fetch('meta');
      print $this->fetch('css');
      print $this->fetch('script');
    ?>
  </head>

  <body onload="js_onload_call_hooks()">
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
            // Clicking on the logo will take us to the front page
            print $this->Html->link($this->Html->image('comanage-logo.png',
                array('alt'     => 'COmanage','height' => 50)),
              '/',
              array('escape' => false));
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
        // insert the sidebar when it exists
        $sidebarButtons = $this->getVar('sidebarButtons');
        if($sidebarButtons != null):
      ?>
          <!-- Display sidebar menu for content -->
          <!-- Note: sidebar is now a top menu -->
          <div id="sidebar">
            <ul id="menu">
            <?php
              foreach($sidebarButtons as $button => $link){
                print '<li>'; 
                  // Clean data
                  $icontitle = '<span class="ui-icon ui-icon-' 
                               . $link['icon'] 
                               . '"></span>'
                               . $link['title'];
                  
                  $url = $link['url'];
                  
                  $options = array();
                  
                  if(isset($link['options'])) {
                    $options = (array)$link['options'];
                  }
                  
                  $options['escape'] = FALSE;
                  
                  if(!empty($link['confirm'])) {
                    // There is a built in Cake popup, which can be accessed by putting the confirmation text
                    // as the fourth parameter to link. However, that uses a javascript popup rather than a
                    // jquery popup, which is inconsistent with our look and feel.
                    
                    $options['onclick'] = "javascript:js_confirm_generic('" . _jtxt($link['confirm']) . "', '" . Router::url($url) . "'";
                    
                    if(!empty($link['confirmbtxt'])) {
                      // Set the text for the confirmation button
                      $options['onclick'] .= ", '" . $link['confirmbtxt'] . "'";
                    }
                    
                    $options['onclick'] .= ");return false";
                  }
                  
                  print $this->Html->link(
                    $icontitle,
                    $url,
                    $options
                  );
                print '</li>';
              }
            ?>
            </ul>

            <?php // Advanced Search (CO-139)
              if(isset($permissions['search']) && $permissions['search'] ) {
                // Get a pointer to our model
                $model = $this->name;
                if(!empty($this->plugin)) {
                  $fileLocation = APP . "Plugin/" . $this->plugin . "/View/" . $model . "/search.inc";
                  if(file_exists($fileLocation))
                    include($fileLocation);
                } else {
                  $fileLocation = APP . "View/" . $model . "/search.inc";
                  if(file_exists($fileLocation))
                    include($fileLocation);
                }
              }
            ?>
          </div>
      <?php endif; ?>

      <?php
        /* display the view content */
        if($sidebarButtons != null) {
          print '<div id="content" class="contentWithSidebar">';
        } else {
          print '<div id="content">';
        }

        // insert breadcrumbs on all but the homepage
        if ($this->request->here != $this->request->webroot) {
          echo '<div id="breadcrumbs">' . $this->Html->getCrumbs(' > ', _txt('bc.home')) . "</div>";
        }

        // insert the page internal content
        print $this->fetch('content');
        print "</div>";
      ?>

    </div>

    <!-- Common UI components -->

    <?php if($this->here != '/registry/shibboleth_embedded_discovery_service/eds/view'):
      // Don't load the following UI component when loading the Shib EDS. ?>
      <div id="dialog" title="Confirm">
        <p>
          <span class="ui-icon ui-icon-alert" style="float:left; margin:0 7px 20px 0;"></span>
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
