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
    // Restore fields
    $("input[id*='ValidFrom'], input[id*='ValidThrough']").focusin(function() {
      var $valid_from = $("input[id*='ValidFrom'");
      var $valid_through = $("input[id*='ValidThrough'");

      $valid_through.get(0).setCustomValidity("");
      $valid_from.get(0).setCustomValidity("");
      $("input[type='submit']").prop('disabled', false);
    });

    // Lightbox
    $('a.lightbox').magnificPopup({
      type:'ajax',
      preloader: true,
      showCloseBtn: true,
      enableEscapeKey: true,
      closeOnBgClick: false,
      tLoading: '',
      callbacks: {
        open: function() {
          // Will fire when this exact popup is opened
          // this - is Magnific Popup object
          displaySpinner();
        },
        close: function() {
        },
        updateStatus: function(data) {
          // console.log('Status changed', data);
          // "data" is an object that has two properties:
          // "data.status" - current status type, can be "loading", "error", "ready"
          // "data.text" - text that will be displayed (e.g. "Loading...")
          // you may modify this properties to change current status or its text dynamically
          if(data.status !== 'loading') {
            stopSpinner();
          }
          if(data.status == 'error') {
            // discard and show noty
            this.close();
            generateFlash(data.text, data.status);
          }
        }
      },
      ajax: {
        settings: {
          cache: false
        },
        tError: 'Permission Denied' //  Error message, can contain %curr% and %total% tags if gallery is enabled
        // tError: '<a href="%url%">View</a> load failed.' //  Error message, can contain %curr% and %total% tags if gallery is enabled
      }
    });

    // Handle Action Menu Observers
    $('.field-actions .dropdown-menu').each( (key, elem) => {
      add_observer(elem, 'li', 'highlight');
    });
    $('.td-field-actions .dropdown-menu').each( (key, elem) => {
      add_observer(elem, 'tr', 'highlight');
    });

    $('#user-panel-toggle,#user-notifications,#global-search').on('click', function() {
      if($(window).width() < 768) {
        if ($('#navigation-drawer').is(':visible')) {
          $('#co-hamburger').trigger('click');
        }
      }
    });

    // Establish left-side navigation
    $('#main-menu').metisMenu({
      toggle: false
    });

    // Focus any designated form element
    $('.focusFirst').focus();

    // DESKTOP MENU DRAWER BEHAVIOR

    // Hamburger menu-drawer toggle
    $('#co-hamburger').click(function () {
      if($(window).width() < 768) {
        // Mobile mode
        $("#navigation-drawer").removeClass("half-closed").toggle();
      } else {
        // Desktop mode
        if ($("#navigation-drawer").hasClass("half-closed")) {
          $("#navigation-drawer").removeClass("half-closed");
          $("#main").removeClass("drawer-half-closed");
          // save user's application preference for drawer state
          setApplicationPreference("uiDrawerState",{"value":"open"});
        } else {
          $("#navigation-drawer").addClass("half-closed");
          $("#main").addClass("drawer-half-closed");
          // ensure all the sub-menus collapse when half-closing the menu
          $("#navigation .metismenu li ul").removeClass("mm-show");
          $("#navigation .metismenu li").removeClass("mm-active");
          // save user's application preference for drawer state
          setApplicationPreference("uiDrawerState",{"value":"half-closed"});
        }
      }
    });

    // Catch the edge-case of browser resize causing menu-drawer
    // to remain hidden and vice versa.
    $(window).resize(function() {
      if($( window ).width() > 767) {
        $("#navigation-drawer").show();
      } else {
        $("#navigation-drawer").hide();
      }
    });

    // Desktop half-closed drawer behavior & expandable menu items
    $('#navigation-drawer a.menuTop').click(function () {
      if($("#navigation-drawer").hasClass("half-closed") && $(this).attr("aria-expanded") == "true") {
        // widen the menu when we open a.menuTop so we can see the menu items
        $("#navigation-drawer").removeClass("half-closed").addClass("intermediate-open");
      }
      if($("#navigation-drawer").hasClass("intermediate-open") && $(this).attr("aria-expanded") == "false") {
        // close it back down if we're in the intermediate state and we close a.menuTop
        $("#navigation-drawer").addClass("half-closed").removeClass("intermediate-open");
      }

      // Save the ID of the most recently expanded menuTop item in an Application Preference
      if ($(this).attr("aria-expanded") == "true") {
        var parentId = $(this).parent().attr("id");
        setApplicationPreference("uiMainMenuSelectedParentId",{"value":parentId});
      } else {
        setApplicationPreference("uiMainMenuSelectedParentId",{"value":null});
      }
    });

    // END DESKTOP MENU DRAWER BEHAVIOR

    // USER MENU BEHAVIORS
    // Toggle the custom user panel in the user menu
    $("#user-panel-toggle").click(function(e) {
      e.stopPropagation();
      if ($("#user-panel").is(":visible")) {
        $("#user-panel").hide();
        $(this).attr("aria-expanded","false");
      } else {
        $("#user-panel").show();
        $(this).attr("aria-expanded","true");
      }
    });

    // Toggle the notifications panel in the user menu
    $("#user-notifications").click(function(e) {
      e.stopPropagation();
      if ($("#notifications-menu").is(":visible")) {
        $("#notifications-menu").hide();
        $(this).attr("aria-expanded","false");
      } else {
        $("#notifications-menu").show();
        $(this).attr("aria-expanded","true");
      }
    });

    // Hide interface items on click outside
    $(document).on('click', function (e) {
      if ($(e.target).closest("#user-panel, #notification-menu").length === 0) {
        $("#user-panel, #notifications-menu").hide();
      }
      if ($(e.target).closest(".cm-inline-editable-field").length === 0) {
        $(".cm-inline-editable-field").removeClass('active');
      }
    });
    // END USER MENU BEHAVIOR

    // Toggle the top search filter box
    $("#top-search-toggle").click(function(e) {
      e.preventDefault();
      e.stopPropagation();
      const appPrefsTag = $(this).attr('data-filters-id');
      if ($("#top-search-fields").hasClass("visible")) {
        // Close MultiSelect dropdowns if any
        const $form = $(this).closest('form')
        // Multi Select elements
        $form.find('.p-multiselect').each(function() {
          // find the container
          const containerDiv = $(this).parent();
          const containerId = containerDiv.attr('id');
          window['closeDropdown' + containerId]();
        })
        $("#top-search-fields").removeClass("visible").addClass("invisible");
        $("#top-search-toggle button.cm-toggle").attr("aria-expanded","false").attr("aria-label","<?php print _txt('me.menu.filters.open'); ?>");
        $("#top-search-toggle .drop-arrow").text("arrow_drop_down");
        setApplicationPreference(appPrefsTag,{"value":null});
      } else {
        $("#top-search-fields").removeClass("invisible").addClass("visible");
        $("#top-search-toggle button.cm-toggle").attr("aria-expanded","true").attr("aria-label","<?php print _txt('me.menu.filters.close'); ?>");
        $("#top-search-toggle .drop-arrow").text("arrow_drop_up");
        setApplicationPreference(appPrefsTag,{"value":"open"});
      }
    });

    // Clear a specific top search filter by clicking the filter button
    $("#top-search-toggle button.top-search-active-filter").click(function(e) {
      e.preventDefault();
      e.stopPropagation();
      $(this).hide();
      filterId = '#' + $(this).attr("aria-controls");
      $(filterId).val("");
      $(this).closest('form').submit();
    });

    // Clear all top filters from the filter bar
    $("#top-search-clear-all-button").click(function(e) {
      e.preventDefault();
      e.stopPropagation();
      $(this).hide();
      $("#top-search-toggle .top-search-active-filter").hide();
      $("#top-search-clear").click();
    });

    // Inline Edit Controls: disable default button behavior
    // Individual behaviors are defined within each page
    $(".cm-ief-controls button").click(function(e) {
      e.preventDefault();
      e.stopPropagation();
    });

    // Reveal inline edit controls when keyboard focuses the data
    // Note that there is currently no related blur (though a click outside will hide these elements)
    $(".cm-inline-editable-field a").focus(function() {
      $(this).closest('.cm-inline-editable-field').addClass('active');
    });

    // Accordion
    $(".accordion").accordion();

    // Add classes to all submit buttons
    // NOTE: This is not working for the lightbox
    $("input:submit").addClass("spin submit-button btn btn-primary");

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

    $(".trashbutton").button({
      icons: {
        primary: 'ui-icon-trash'
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

    $(".schedulebutton").button({
      icons: {
        primary: ' ui-icon-clock'
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

    $(".restorebutton").button({
      icons: {
        primary: 'ui-icon-arrowrefresh-1-e'
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
      yearRange: "c-90:+10",
      dateFormat: "yy-mm-dd",
      numberOfMonths: 1,
      showButtonPanel: false,
      showOtherMonths: true,
      selectOtherMonths: true
    }).bind('click',function () {
      $("#ui-datepicker-div").appendTo($(this).closest('.modelbox-data'));
    });

    $(".datepicker-f").datepicker({
      changeMonth: true,
      changeYear: true,
      yearRange: "c-90:+10",
      dateFormat: "yy-mm-dd 00:00:00",
      numberOfMonths: 1,
      showButtonPanel: false,
      showOtherMonths: true,
      selectOtherMonths: true
    }).bind('click',function () {
      $("#ui-datepicker-div").appendTo($(this).closest('.modelbox-data'));
    });

    $(".datepicker-m").datepicker({
      changeMonth: true,
      dateFormat: "mm-dd",
      numberOfMonths: 1,
      showButtonPanel: false,
      showOtherMonths: true,
      selectOtherMonths: true
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
      selectOtherMonths: true
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

    // Add loading animation when a form is submitted or when any item with a "spin" class is clicked.
    $("input[type='submit'], .spin").click(function(e) {
      
      // Start a spinner only if CTRL, CMD, or SHIFT is not pressed (which loads a new tab or window).
      if(!(e.ctrlKey || e.metaKey || e.shiftKey)) {
        displaySpinner();
  
        // XXX This is a workaround. Currently the sidebar actions in CO Person Canvas
        //     are part of the form. As a result the spinner is dismissed immediately after
        if($(this).hasClass('ignore-invalid')) {
          return;
        }
  
        // Test for invalid fields (HTML5) and turn off spinner explicitly if found.
        // Also let the .nospin class override the spin class.
        if(document.querySelectorAll(":invalid").length || $(this).hasClass('nospin')) {
          stopSpinner();
        }
      }
      
    });

    // Flash Messages
    <?php
      print $this->Flash->render('error');
      print $this->Flash->render('success');
      print $this->Flash->render('information');
    ?>
  });

  // Observers list
  var observer = new Array();
  // Options for the Dropdown Action Menu Observer
  const cmActionMenuOptions = {
    attributes: true,
    attributeFilter: ['class']
  };

  // Mutation observer handler for Dropdown action menus
  // element              - Parent element containing target     (string, required)
  // target_element       - DOM element to observe               (string, required)
  // modify_class         - Class name apended to targte_element (string, required)
  function add_observer(element, target_element, modify_class) {
    observer[element] = new MutationObserver((mutationList) => {
      // Use traditional 'for loops' for IE 11
      for(const mutation of mutationList) {
        if (mutation.type === 'attributes') {
          bs_dropdown = mutation.target.parentNode;
          if($(bs_dropdown).hasClass('show')) {
            if(target_element === 'tr') {
              $(bs_dropdown).closest(target_element).prev(target_element).addClass(target_element + '-' + modify_class);
            }
            $(bs_dropdown).closest(target_element).addClass(modify_class);
          } else {
            if(target_element === 'tr') {
              $(bs_dropdown).closest(target_element).prev(target_element).removeClass(target_element + '-' + modify_class);
            }
            $(bs_dropdown).closest(target_element).removeClass(modify_class);
          }
        }
      }
    });
    observer[element].observe(element,cmActionMenuOptions);
  }

  // Copy a value to browser clipboard (only works on HTTPS)
  // (This is here rather than in comanage.js to more easily use the i18n strings.) 
  async function copyValue(val,callingElement) {
    try {
      // remove extra white spaces and trim the value
      let valWithNormalizedSpaces = val.replace(/\s+/g, ' ').trim();
      // copy to clipboard
      await navigator.clipboard.writeText(valWithNormalizedSpaces);
      // provide feedback
      $(callingElement).find('.material-icons').text('thumb_up');
      $(callingElement).attr('aria-label', '<?php print _txt('op.copy.value.ok'); ?>');
      // reset feedback
      setTimeout(() => $(callingElement).find('.material-icons').text('content_copy'), 800);
      setTimeout(() => $(callingElement).attr('aria-label', '<?php print _txt('op.copy.value'); ?>'), 2200);
    } catch($e) {
      // this will be rendered if browser is not on HTTPS
      let msg = '<?php print _txt('er.javascript.copy') ?>';
      if(window.location.protocol != 'https:') {
        msg += ' ' + '<?php print _txt('er.javascript.requires.https') ?>';
      }
      alert(msg + "\n\n" + $e);
    }
  }

  // Define default text for confirm dialog
  var defaultConfirmOk = "<?php print _txt('op.ok'); ?>";
  var defaultConfirmCancel = "<?php print _txt('op.cancel'); ?>";
  var defaultConfirmTitle = "<?php print _txt('op.confirm'); ?>";
  
  // Define default text for session timeout and unknown errors
  var defaultHttp500 = "<?php print _txt('er.500'); ?>";
  var defaultHttp401 = "<?php print _txt('er.timeout'); ?>";
</script>
