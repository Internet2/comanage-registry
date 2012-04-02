<?php
/**
 * COmanage Registry Default Layout
 *
 * Copyright (C) 2011-12 University Corporation for Advanced Internet Development, Inc.
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
 * @copyright     Copyright (C) 2011-12 University Corporation for Advanced Internet Development, Inc.
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

    <!-- Include the gears and jquery style sheets -->
    <?php print $this->Html->css('comanage'); ?>
    
    <?php print $this->Html->css('jquery/ui/css/start/jquery-ui-1.8.16.custom.css'); ?>

    <!-- Get jquery code -->
    <?php print $this->Html->script('jquery/ui/js/jquery-1.6.2.min.js'); ?>
    
    <?php print $this->Html->script('jquery/ui/js/jquery-ui-1.8.16.custom.min.js'); ?>

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
    </script>
    
    <!-- Include external files and scripts -->
    <?php print $scripts_for_layout ?>
  </head>
 
  <body onload="js_onload_call_hooks()">
    <table width="100%">
      <tr>
        <td width="50%">
          <?php
            print $this->Html->image('comanage-logo.jpg',
                                     array('alt'     => 'COmanage',
                                           'height'  => 42,
                                           'width'   => 227));
          ?>
          <font face="verdana">Registry</font>
        </td>
        <td width="50%">
          <div class="right">
            <table id="userlabel" class="ui-widget ui-widget-content ui-corner-all">
              <thead>
                <tr class="ui-widget-header">
                  <th style="font-size:62.5%;">
                    <?php
                      if($this->Session->check('Auth.User'))
                      {
                        print "<div style='text-align:right'>" . generateCn($this->Session->read('Auth.User.name'));
                        if(isset($cur_co))
                          print " (" . $cur_co['Co']['name'] . ")";
                        print "</div>\n";
                      }
                      else
                        print _txt('au.not');
                    ?>
                  </th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td>
                    <?php
                      if($this->Session->check('Auth.User')) {
                        if($this->params['controller'] != 'pages' && $this->params['action'] != 'menu') {
                          print $this->Html->link(_txt('op.menu'),
                                                  "/",
                                                  array('class' => 'menubutton'));
                        }
                        
                        print $this->Html->link(_txt('op.logout'),
                                                array('controller' => 'auth', 'action' => 'logout'),
                                                array('class' => 'logoutbutton'));
                      }
                      else {
                        print $this->Html->link(_txt('op.login'),
                                                array('controller' => 'auth', 'action' => 'login'),
                                                array('class' => 'logoutbutton'));
                      }
                    ?>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </td>
      </tr>
      <tr>
        <td colspan="2">
          <?php
            $f = $this->Session->flash('error');
            
            if($f && $f != "")
            {
              print '
		<div class="ui-widget">
                  <div class="ui-state-error ui-corner-all" style="margin-top: 20px; padding: 0 .7em;"> 
                    <p><span class="ui-icon ui-icon-alert" style="float: left; margin-right: .3em;"></span>
                      ' . $f . '
                    </p>
                  </div>
		</div>
                ';
            }

            $f = $this->Session->flash('info');
            
            if($f && $f != "")
            {
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
            
            if($f && $f != "")
            {
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
          <?php print $content_for_layout ?>
        </td>
      </tr>
    </table>
    <?php if(Configure::read('debug') > 0) print $this->element('sql_dump'); ?>

    <!-- Common UI components -->
  
    <div id="dialog" title="Confirm">
      <p>
        <span class="ui-icon ui-icon-alert" style="float:left; margin:0 7px 20px 0;"></span>
        <span id="dialog-text"><?php print _txt('op.proceed.ok'); ?></span>
      </p>
    </div>
  </body>
</html>