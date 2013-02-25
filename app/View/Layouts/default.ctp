<?php
/**
 * COmanage Registry Default Layout
 *
 * Copyright (C) 2011-13 University Corporation for Advanced Internet Development, Inc.
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
 * @copyright     Copyright (C) 2011-13 University Corporation for Advanced Internet Development, Inc.
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
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
  <head>
    <title><?php print _txt('coordinate') . ': ' . $title_for_layout?></title>
    <!-- link rel="shortcut icon" href="favicon.ico" type="image/x-icon" -->

    <!-- Include the comanage and jquery style sheets -->
    <?php print $this->Html->css('comanage'); ?>
    <?php print $this->Html->css('jquery/ui/css/comanage-theme/jquery-ui-1.10.0.custom'); ?>
    <?php print $this->Html->css('jquery/superfish/css/superfish'); ?>
    <?php print $this->Html->css('menubar'); ?>

    <!-- Get jquery code -->
    <?php print $this->Html->script('jquery/ui/js/jquery-1.9.0.js'); ?>
    <?php print $this->Html->script('jquery/ui/js/jquery-ui-1.10.0.custom.min.js'); ?>
    <?php print $this->Html->script('jquery/superfish/js/superfish.js'); ?>

    <!-- Common script code -->
    <script type="text/javascript">
    // Function to confirm delete and then hand off
  
    function js_confirm_delete(name, url)
    {
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

    function js_confirm_generic(txt, url)
    {
      // Generate a dialog box confirming <txt>.  On confirmation, forward to <url>.

      // Set the title of the dialog    
      $("#dialog").dialog("option", "title", "<?php print _txt('op.confirm'); ?>" + " " + name);

      // Set the body of the dialog
      $("#dialog-text").text(txt);
    
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

    function js_confirm_reinvite(name, url)
    {
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
    
    function js_onload_call_hooks()
    {
      // On page load, call any defined initialization functions.
      // Make sure function is defined before calling.
      
      if(window.js_local_onload)
      {
        js_local_onload();
      }
    }
    
    function js_onsubmit_call_hooks()
    {
      // On form submit, call any defined functions.
      // Make sure function is defined before calling.
      
      if(window.js_local_onsubmit)
      {
        js_local_onsubmit();
      }
    }

    // jQuery stuff
    
    $(function() {
      // Accordion
      $(".accordion").accordion();
      
      // Make all submit buttons pretty
      $("input:submit").button();
      
      // Other buttons
      $(".addbutton").button({
        icons: {
          primary: 'ui-icon-circle-plus'
        }
      });
      
      $(".autobutton").button({
        icons: {
          primary: 'ui-icon-script'
        }
      });
      
      $(".backbutton").button({
        icons: {
          primary: 'ui-icon-circle-arrow-w'
        }
      });
      
      $(".cancelbutton").button({
        icons: {
          primary: 'ui-icon-circle-close'
        }
      });
      
      $(".checkbutton").button({
        icons: {
          primary: 'ui-icon-circle-check'
        }
      });
      
      $(".comparebutton").button({
        icons: {
          primary: 'ui-icon-person'
        },
        text: false
      });

      $(".deletebutton").button({
        icons: {
          primary: 'ui-icon-circle-close'
        },
        text: false
      });

      $(".editbutton").button({
        icons: {
          primary: 'ui-icon-pencil'
        },
        text: false
      });

      $(".forwardbutton").button({
        icons: {
          primary: 'ui-icon-circle-arrow-e'
        }
      });
      
      $(".historybutton").button({
        icons: {
          primary: 'ui-icon-note'
        }
      });
      
      $(".invitebutton").button({
        icons: {
          primary: 'ui-icon-mail-closed'
        },
        text: false
      });

      $(".linkbutton").button({
        icons: {
          primary: 'ui-icon-extlink'
        },
      });
      
      $(".logoutbutton").button({
        icons: {
          primary: 'ui-icon-power'
        },
      });

      $(".menubutton").button({
        icons: {
          primary: 'ui-icon-home'
        },
      });

      $(".menuitembutton").button({
        icons: {
          primary: 'ui-icon-circle-triangle-e'
        },
      });
      
      $(".petitionbutton").button({
        icons: {
          primary: 'ui-icon-script'
        },
        text: false
      });
      
      $(".provisionbutton").button({
        icons: {
          primary: 'ui-icon-gear'
        },
        text: false
      });
      
      $(".unlinkbutton").button({
        icons: {
          primary: 'ui-icon-cancel'
        },
        text: false
      });
      
      $(".viewbutton").button({
        icons: {
          primary: 'ui-icon-extlink'
        },
        text: false
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
    });

    // Turn on the sidebar menus
    $(function() {
      $( "#menu" ).menu();
    });
    </script>
    
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
          <?php print $this->element('links'); ?>
          <?php print $this->element('secondaryMenu'); ?>
        </div>
      </div>

      <div id="row2" class="ui-widget-header">
        <div class="contentWidth">
          <div class="headerLeft">
            <?php
              if($this->Session->check('Auth.User'))
                print $this->element('dropMenu');
            ?>
          </div>
          <div class="headerRight">
            <?php
              // Clicking on the logo will take us to the front page
              print $this->Html->link($this->Html->image('comanage-logo.png',
                                                   array('alt'     => 'COmanage','height' => 50)),
                                      '/',
                                      array('escape' => false));
            ?>
          </div>
        </div>
      </div>
    </div>

    <div id="content">
      <div>
        <?php
          $f = $this->Session->flash('error');
          
          if($f && $f != "") {
            print '
              <div class="ui-widget">
                <div class="ui-state-error ui-corner-all" style="margin-top: 20px; padding: 0 .7em;">
                  <p>
                    <span class="ui-icon ui-icon-alert" style="float: left; margin-right: .3em;"></span>
                    ' . $f . '
                  </p>
                </div>
              </div>
            ';
          }

          $f = $this->Session->flash('info');
          
          if($f && $f != "") {
            print '
              <div class="ui-widget">
                <div class="ui-state-highlight ui-corner-all" style="margin-top: 20px; padding: 0 .7em;"> 
                  <p><span class="ui-icon ui-icon-info" style="float: left; margin-right: .3em;"></span>
                    ' . $f . '
                  </p>
                </div>
              </div>
            ';
          }
          
          $f = $this->Session->flash('success');
          
          if($f && $f != "") {
            print '
              <div class="ui-widget">
                <div class="ui-state-active ui-corner-all" style="margin-top: 20px; padding: 0 .7em;"> 
                  <p><span class="ui-icon ui-icon-circle-check" style="float: left; margin-right: .3em;"></span>
                    ' . $f . '
                  </p>
                </div>
              </div>
            ';
          }
        ?>

        <?php print_r($this->Session->error()); ?>
        <!-- Display view content -->
        <?php print $this->fetch('content'); ?>
      </div>
    </div>

    <?php
      $sidebarButtons = $this->getVar('sidebarButtons');
    
      if($sidebarButtons != null):
    ?>
        <!-- Display sidebar for content -->
        <div id="sidebar">
          <ul id="menu">
          <?php
            foreach($sidebarButtons as $button => $link){
              print '<li>'; 
                print $this->Html->link(
                  '<span class="ui-icon ui-icon-' 
                    . $link['icon'] 
                    . '"></span>'
                    . $link['title'],
                  $link['url'],
                  array('escape' => FALSE)
                ); // end of a
              print '</li>';
            }
          ?>
          </ul>
        </div>
    <?php endif; ?>
    <?php if(Configure::read('debug') > 0) print $this->element('sql_dump'); ?>

    <!-- Common UI components -->

    <div id="dialog" title="Confirm">
      <p>
        <span class="ui-icon ui-icon-alert" style="float:left; margin:0 7px 20px 0;"></span>
        <span id="dialog-text"><?php print _txt('op.proceed.ok'); ?></span>
      </p>
    </div>

    <div class="contentWidth">
      <?php print $this->element('footer'); ?>
    </div>

  </body>
</html>
