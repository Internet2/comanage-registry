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
    // Establish left-side navigation
    $('#main-menu').metisMenu();

    // Never allow MDL to apply "aria-hidden" on the fixed menu drawer (it should always be available to screen readers)
    $('#navigation-drawer').removeAttr('aria-hidden');

    // Focus any designated form element
    $('.focusFirst').focus();

    // MDL prematurely marks all required=true fields with "is-invalid" class.
    // Remove it. Must be done after MDL scripts have run (hence, window.load)
    $(window).on('load', function() {
      $('.mdl-textfield').removeClass('is-invalid');
    });

    // DESKTOP MENU DRAWER BEHAVIOR
    // Check the drawer half-closed cookie on first load and set the drawer state appropriately
    if (Cookies.get("desktop-drawer-state") == "half-closed") {
      $("#navigation-drawer").addClass("half-closed");
      $("#main").addClass("drawer-half-closed");
    }

    // Desktop hamburger menu-drawer toggle
    $('#desktop-hamburger').click(function () {
      if( $("#navigation-drawer").hasClass("half-closed")) {
        $("#navigation-drawer").removeClass("half-closed");
        $("#main").removeClass("drawer-half-closed");
        // set a cookie to hold drawer half-open state between requests
        Cookies.set("desktop-drawer-state", "open");
      } else {
        $("#navigation-drawer").addClass("half-closed");
        $("#main").addClass("drawer-half-closed");
        // ensure all the sub-menus collapse when half-closing the menu
        $("#navigation .metismenu li ul").removeClass("in");
        $("#navigation .metismenu li").removeClass("active");
        // set a cookie to hold drawer half-open state between requests
        Cookies.set("desktop-drawer-state", "half-closed");
      }
    });

    // Desktop half-closed drawer behavior
    $('#navigation-drawer a.menuTop').click(function () {
      if (Cookies.get("desktop-drawer-state") == "half-closed") {
        $("#navigation-drawer").toggleClass("half-closed");
      }
    });
    // END DESKTOP MENU DRAWER BEHAVIOR

    // USER MENU BEHAVIORS
    // Toggle the global search box
    $("#global-search label").click(function (e) {
      e.stopPropagation();
      if ($("#global-search-box").is(":visible")) {
        $("#global-search-box").hide();
        $("#global-search-box").attr("aria-expanded","false");
      } else {
        $("#global-search-box").show();
        $("#global-search-box").attr("aria-expanded","true");
      }
    });

    // Toggle the custom user panel in the user menu
    $("#user-panel-toggle").click(function(e) {
      e.stopPropagation();
      if ($("#user-panel").is(":visible")) {
        $("#user-panel").hide();
        $("#user-panel").attr("aria-expanded","false");
      } else {
        $("#user-panel").show();
        $("#user-panel").attr("aria-expanded","true");
      }
    });

    // Hide custom user menu items on click outside
    $(document).on('click', function (e) {
      if ($(e.target).closest("#user-panel, #global-search-box").length === 0) {
        $("#user-panel, #global-search-box").hide();
      }
    });

    // Accordion
    $(".accordion").accordion();

    // Make all submit buttons pretty (MDL)
    $("input:submit").addClass("spin submit-button mdl-button mdl-js-button mdl-button--raised mdl-button--colored mdl-js-ripple-effect");

    // Other buttons (jQuery)
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

    $(".editbutton").button(
      {  classes: {
      "ui-button": "highlight"
      },
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

    $(".lockbutton").button({
      icons: {
        primary: 'ui-icon-locked'
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
    
    //$("button:reset").button();
    //$("button:reset").css('float', 'left');

    $(".unlockbutton").button({
      icons: {
        primary: 'ui-icon-unlocked'
      },
      text: true
    });
    
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

    <?php /* For all calls to datepicker, wrap the calling date field in a
      container of class .modelbox-data: this allows us to show the datepicker next to
      the appropriate field because jQuery drops the div at the bottom of the body and
      that approach doesn't work well with Material Design Light (MDL). If you do not
      do this, the datepicker will float up to the top of the browser window. See
      app/View/CoGroupMembers for an example. */ ?>

    $(".datepicker").datepicker({
      changeMonth: true,
      changeYear: true,
      dateFormat: "yy-mm-dd",
      numberOfMonths: 1,
      showButtonPanel: false,
      showOtherMonths: true,
      selectOtherMonths: true,
      onSelect: function() {
        $(this).closest('.mdl-textfield').addClass('is-dirty');
      }
    }).bind('click',function () {
      $("#ui-datepicker-div").appendTo($(this).closest('.modelbox-data'));
    });

    $(".datepicker-f").datepicker({
      changeMonth: true,
      changeYear: true,
      dateFormat: "yy-mm-dd 00:00:00",
      numberOfMonths: 1,
      showButtonPanel: false,
      showOtherMonths: true,
      selectOtherMonths: true,
      onSelect: function(selectedDate) {
        $(this).closest('.mdl-textfield').addClass('is-dirty');
      }
    }).bind('click',function () {
      $("#ui-datepicker-div").appendTo($(this).closest('.modelbox-data'));
    });

    $(".datepicker-m").datepicker({
      changeMonth: true,
      dateFormat: "mm-dd",
      numberOfMonths: 1,
      showButtonPanel: false,
      showOtherMonths: true,
      selectOtherMonths: true,
      onSelect: function(selectedDate) {
        $(this).closest('.mdl-textfield').addClass('is-dirty');
      }
    }).bind('click',function () {
      $("#ui-datepicker-div").appendTo($(this).closest('.modelbox-data'));
    });

    $(".datepicker-u").datepicker({
      changeMonth: true,
      changeYear: true,
      dateFormat: "yy-mm-dd 23:59:59",
      numberOfMonths: 1,
      showButtonPanel: false,
      showOtherMonths: true,
      selectOtherMonths: true,
      onSelect: function(selectedDate) {
        $(this).closest('.mdl-textfield').addClass('is-dirty');
      }
    }).bind('click',function () {
      $("#ui-datepicker-div").appendTo($(this).closest('.modelbox-data'));
    });

    // Dialog
    // This generic dialog gets modified by the calling function
    $("#dialog").dialog({
      autoOpen: false,
      resizable: false,
      modal: true,
      buttons: {
        '<?php print _txt('op.cancel'); ?>': function() {
          $(this).dialog('close');
        },
        '<?php print _txt('op.ok'); ?>': function() {
          $(this).dialog('close');
        }
      }
    });

    // Add a spinner when a form is submitted or when any item is clicked with a "spin" class
    $("input[type='submit'], .spin").click(function() {

      var spinnerDiv = '<div id="coSpinner"></div>';
      $("body").append(spinnerDiv);

      var coSpinnerTarget = document.getElementById('coSpinner');
      // coSpinnerOpts are set in js/comanage.js
      var coSpinner = new Spinner(coSpinnerOpts).spin(coSpinnerTarget);

      // Test for invalid fields (HTML5) and turn off spinner explicitly if found
      if(document.querySelectorAll(":invalid").length) {
        coSpinner.stop();
      }

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
