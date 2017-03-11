<?php
/**
 * COmanage Registry jQuery onload JavaScript
 * Applies jQuery widgets and flash messages
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
 * @since         COmanage Registry v0.9.4
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */
?>

<script type="text/javascript">
  $(function() {
    // Focus any designated form element
    $(".focusFirst").focus();

    // Accordion
    $(".accordion").accordion();

    // Make all submit buttons pretty
    $("input:submit").button();

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

    $(".contactbutton").button({
      icons: {
        primary: 'ui-icon-contact'
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

    $(".flagbutton").button({
      icons: {
        primary: 'ui-icon-flag'
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

    $(".movebutton").button({
      icons: {
        primary: 'ui-icon-arrow-4'
      },
      text: true
    });

    $(".notebutton").button({
      icons: {
        primary: 'ui-icon-note'
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

    $(".reconcilebutton").button({
      icons: {
        primary: 'ui-icon-transferthick-e-w'
      },
      text: true
    });

    $(".relinkbutton").button({
      icons: {
        primary: 'ui-icon-link'
      },
      text: true
    });

    $(".runbutton").button({
      icons: {
        primary: 'ui-icon-gear'
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
    $("#menu").menu();
    
    // Turn on tooltips for menuTop
    $(".menuTop").tooltip({
      position: { my: "right+15 top", at: "left bottom" }
    });

    // Flash Messages
    <?php
      print $this->Flash->render('error');
      print $this->Flash->render('success');
      print $this->Flash->render('information');
    ?>
  });

  // Define default text for confirm dialog
  var defaultConfirmOk = "<?php print _txt('op.ok'); ?>";
  var defaultConfirmCancel = "<?php print _txt('op.cancel'); ?>";
  var defaultConfirmTitle = "<?php print _txt('op.confirm'); ?>";

</script>
