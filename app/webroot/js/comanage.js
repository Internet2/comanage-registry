/**
 * COmanage Registry Default JavaScript
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
 * @since         COmanage Registry v0.1
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

// On page load, call any defined initialization functions.
// Make sure function is defined before calling.
function js_onload_call_hooks() {
  if(window.js_local_onload) {
    js_local_onload();
  }
}

// On form submit, call any defined functions.
// Make sure function is defined before calling.
function js_onsubmit_call_hooks() {
  if(window.js_local_onsubmit) {
    js_local_onsubmit();
  }
}

// Generate flash notifications for messages
function generateFlash(text, type) {
  var n = noty({
    text: text,
    type: type,
    dismissQueue: true,
    layout: 'topCenter',
    closeWith: ['button'],
    theme: 'comanage'
  });
}

// Set an application preference
// tag     - name of preference to save (string, required)
// value   - value to save (json in the form of {"value":"something"} or {"value":null} to unset)
function setApplicationPreference(tag,value) {
  var apUrl = "/registry/application_preferences/" + tag;
  var jsonData = value;
  $.ajax({
    url: apUrl,
    type: 'PUT',
    data: jsonData
  });
}

// Generate a loading animation by revealing a persistent hidden div with CSS animation.
// An element's onclick action will trigger this to appear if it has the class "spin" class on an element.
// (See View/Elements/javascript.ctp)
// For dynamically created elements, we can generate the loading animation by calling this function.

// show loading animation
function displaySpinner() {
  $("#co-loading").show();
}

// stop a spinner explicitly
function stopSpinner() {
  $("#co-loading").hide();
}


// Returns an i18n string with tokens replaced.
// For use in JavaScript dialogs.
// text          - body text for the array with tokens {0}, {1}, etc
// replacements  - Array of strings to replace tokens
function replaceTokens(text,replacements) {
  var processedString = text;
  for (var i = 0; i < replacements.length; i++) {
    processedString = processedString.replace("{"+i+"}", replacements[i]);
  }
  return processedString;
}

// Generate a dialog box confirming <txt>.  On confirmation, forward to <url>.
// txt                - body text           (string, required)
// url                - forward url         (string, required)
// confirmbtxt        - confirm button text (string, optional)
// cancelbtxt         - cancel button text  (string, optional)
// titletxt           - dialog title text   (string, optional)
// tokenReplacements  - strings to replace tokens in dialog body text (array, optional)
function js_confirm_generic(txt, url, confirmbtxt, cancelbtxt, titletxt, tokenReplacements) {

  var bodyText = txt;
  var forwardUrl = url;
  var confbutton = confirmbtxt;
  var cxlbutton = cancelbtxt;
  var title = titletxt;
  var replacements = tokenReplacements;

  // Perform token replacements on the body text if they exist
  if (replacements != undefined) {
    bodyText = replaceTokens(bodyText,replacements);
  }

  // Set defaults for confirm, cancel, and title
  // Values for the default variables are set globally
  if(confbutton == undefined) {
    confbutton = defaultConfirmOk;
  }
  if(cxlbutton == undefined) {
    cxlbutton = defaultConfirmCancel;
  }
  if(title == undefined) {
    title = defaultConfirmTitle;
  }

  // Set the title of the dialog
  $("#dialog").dialog("option", "title", title);

  // Set the body text of the dialog
  $("#dialog-text").text(bodyText);

  // Set the dialog buttons
  var dbuttons = []
  // Cancel button
  dbuttons.push({
    text: cxlbutton,
    id: "btn-confirm-generic-cxl",
    click: function () {$(this).dialog("close");}
  });
  // Confirm button
  dbuttons.push({
    text: confbutton,
    id: "btn-confirm-generic-conf",
    click: function () {
      // Handle the submit button
      loadUiDialogSpinner($("#btn-confirm-generic-conf"));
      // loadButtonSpinner($("#btn-confirm-generic-conf"), confirmbtxt);
      // Redirect to action
      window.location = forwardUrl;
    }
  });
  $("#dialog").dialog("option", "buttons", dbuttons);

  // Open the dialog
  $('#dialog').dialog('open');
}

// Load button spinner followed by Text
// elem         - button element (object, required)
// bodyText     - button text    (string, required)
function loadButtonSpinner(elem, btnText) {
  let btn_payload = "<span class='spinner-grow spinner-grow-sm align-middle' role='status' aria-hidden='true'></span><span class='sr-only'>"
    + btnText
    + "</span> "
    + btnText;
  elem.html(btn_payload);
  elem.button("disable");
}

// Load UI Dialog co-loading-mini spinner inline with UI Buttons
// elem         - button element (object, required)
function loadUiDialogSpinner(elem) {
  // debugger;
  $pane = elem.closest('.ui-dialog-buttonpane');
  $pane.prepend('<span class="d-inline-flex align-bottom co-loading-mini"><span></span><span></span><span></span></span>');
  elem.button("disable");
}

// Turn the loader to visible and disable the submit button
// This function assumes that the view contains only one submit button
function showBtnSpinnerLightbox() {
  var $spinner = $(".btn-submit-with-loader");
  var $form = $spinner.closest('form');
  if($('.lightbox').length > 0) {
    $spinner.addClass('visible').removeClass('invisible');
    $spinner.closest("button").attr('disabled', true);
  }
  $form.submit();
}

// Generic goto page form handling for multi-page listings.
// We handle this in javascript to avoid special casing controllers.
// pageNumber         - page number         (int, required)
// maxPage            - largest page number allowed (int, required)
// intErrMsg          - error message for entering a non-integer value (string, required)
// maxErrMsg          - error message for entering a page number greater than last page (string, required)
function gotoPage(pageNumber,maxPage,intErrMsg,maxErrMsg) {
  // Just return if no value
  if (pageNumber == "") {
    stopSpinner();
    return false;
  }

  var pageNum = parseInt(pageNumber,10);
  var max = parseInt(maxPage,10);

  // Not an integer?  Return an error message.
  if (isNaN(pageNum)) {
    stopSpinner();
    alert(intErrMsg);
    return false;
  }

  // Page number too large? Return an error message.
  if(pageNum > max) {
    stopSpinner();
    alert(maxErrMsg);
    return false;
  }

  // Page number < 1? Set the page = 1.
  if(pageNum < 1) {
    pageNum = 1;
  }

  // Redirect to the new page:
  window.location = window.location.pathname.replace(new RegExp('\/page:[0-9]*', 'g'), '')+'/page:' + pageNum;
}

// Generic limit page form handling for setting the page size (records shown on a page).
// We handle this in javascript to avoid special casing controllers.
// pageLimit         - page limit                            (int, required)
// recordCount       - total number of records returned      (int, requried)
// currentPage       - current page number                   (int, required)
// currentPath       - the current URL path                  (string, required)
// currentAction     - the current controller action         (string, required)
function limitPage(pageLimit,recordCount,currentPage,currentPath,currentAction) {
  var limit = parseInt(pageLimit,10);
  var count = parseInt(recordCount,10);
  var page = parseInt(currentPage,10);
  var path = currentPath;
  var action = currentAction;

  // Just cancel this if we have bad inputs
  if (isNaN(limit) || isNaN(count) || isNaN(page)) {
    stopSpinner();
    return false;
  }

  var currentUrl = path;

  // Test if we are using an implicit URL
  if (path.indexOf(action) == -1) {
    // add the controller action explicitly
    currentUrl = currentUrl + '/' + action;
  }

  // Set the limit
  currentUrl = currentUrl.replace(new RegExp('\/limit:[0-9]*', 'g'), '')+'/limit:' + limit;

  // Set the page
  // Test to see if the new limit allows the current page to exist
  if (count / page >= limit) {
    // Current page can exist - keep the current page number
    currentUrl = currentUrl.replace(new RegExp('\/page:[0-9]*', 'g'), '')+'/page:' + page;
  } else {
    // Force the url back to page one because the new page size cannot include our current page number
    currentUrl = currentUrl.replace(new RegExp('\/page:[0-9]*', 'g'), '')+'/page:1';
  }

  // Redirect to the new page:
  window.location = currentUrl;
}

// Clear the top search form for numerous index views (people, orgids, groups)
// formObj         - form object                      (DOM form obj, required)
function clearTopSearch(formObj) {
  for (var i=0; i<formObj.elements.length; i++) {
    t = formObj.elements[i].type;
    if(t == "text" || t == "select-one") {
      formObj.elements[i].value = "";
    }
    if(t == "checkbox") {
      formObj.elements[i].checked = false;
    }
  }
  formObj.submit();
}

// Generate a dialog box with input form.  On confirmation, post to <url>.
// txt                - body text           (string, optional)
// url                - forward url         (string, required)
// submitbtxt         - submit button text  (string, optional)
// cancelbtxt         - cancel button text  (string, optional)
// titletxt           - dialog title text   (string, optional)
// lbltxt             - dialog label text   (string, optional)
// sendingtxt         - submit button text
//                      while processing    (string, optional)
function js_form_generic(txt, url, submitbtxt, cancelbtxt, titletxt, lbltxt, sendingtxt) {
  let url_str = (url) ? url : '#';
  let body_text = (txt) ? txt : 'Form';
  let cancel_btn_txt = (cancelbtxt) ? cancelbtxt : 'Cancel';
  let title_txt = (titletxt) ? titletxt : 'Provide input.';
  let label_text = (lbltxt) ? lbltxt : 'Input:';
  let submit_btn_txt = (submitbtxt) ? submitbtxt : 'Submit';
  let submit_btn_txt_sending = (sendingtxt) ? sendingtxt : 'Sending...';

  if(txt != null) {
    $("#form-dialog-legend").html(body_text);
  }
  if(lbltxt != null) {
    $("#form-dialog-input-lbl").html(label_text);
  }

  $("#form-dialog").dialog({
    modal: true,
    title: title_txt,
    buttons: [{
      text: submit_btn_txt,
      id: "btn-form-generic-submit",
      click: function () {
        let dialog_input = $(this).find('input[id="form-dialog-text"]').val();
        let $data = {};
        $data.input = dialog_input;

        // Handle the submit button
        // Handle the submit button
        // loadButtonSpinner($("#btn-form-generic-submit"), submit_btn_txt_sending);
        loadUiDialogSpinner($("#btn-form-generic-submit"));

        let jqxhr = $.ajax({
          cache: false,
          type: "POST",
          url: url_str,
          beforeSend: function(xhr) {
            xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
          },
          data: $data,
        });

        jqxhr.done((data, textStatus, xhr) => {
          $("#form-dialog").dialog('close');
          generateFlash(data, textStatus);
        });

        jqxhr.fail((xhr, textStatus, error) => {
          $("#form-dialog").dialog('close');
          // Show an error message
          // HTML Text
          let err_msg = $.parseHTML(xhr.responseText)[0].innerHTML;
          // JSON text
          try{
            //try to parse JSON
            encodedJson = $.parseJSON(xhr.responseText);
            err_msg = encodedJson.msg;
          }catch(error){
            // Plain text
            err_msg = xhr.responseText;
          }

          if(err_msg != null) {
            error = error + ': ' + err_msg;
          }
          generateFlash(error, textStatus);
        });
      },
    }, {
      text: cancel_btn_txt,
      id: "btn-form-generic-cxl",
      click: function () {
        $(this).dialog('close');
      },
    }],
    close: function () {
      //do something
    }
  });
}

// CO-2077, Do not permit illogical validity dates (eg: CoPersonRole, CoGroupMember) (eg: start dates that are after end dates)
// flashmsg          - Flash message text     (string, required)
// errormsg          - Tooltip Error message  (string, required)
function validate_date_input(flashmsg, errormsg) {
  $("input[id*='ValidFrom'], input[id*='ValidThrough']").on('change', function () {
    var $valid_from = $("input[id*='ValidFrom']");
    let valid_from_date = $valid_from.val();
    var $valid_through = $("input[id*='ValidThrough']");
    let valid_through_date = $valid_through.val();

    // In case any of the two is empty return success
    if((!valid_from_date || valid_from_date.length === 0 )
        || (!valid_through_date || valid_through_date.length === 0 )) {
      return;
    }

    let valid_from_tmstmp = new Date(valid_from_date).getTime();
    let valid_through_tmstmp = new Date(valid_through_date).getTime();
    let ddiff = valid_through_tmstmp-valid_from_tmstmp;
    if( ddiff < 0) {
      this.setCustomValidity(errormsg);
      generateFlash(flashmsg, "error");
      $("input[type='submit']").prop('disabled', true);
    } else {
      $valid_through.get(0).setCustomValidity("");
      $valid_from.get(0).setCustomValidity("");
      $("input[type='submit']").prop('disabled', false);
    }
  });
}

// CO-2263, Format CO Person autocomplete widget items for easier disambiguation
// Depends on jQuery UI - this function is fed to the _renderItem extension point for the autocomplete widget
// ul            - list for the jQuery UI autocomplete selection menu                  (DOM element)
// item          - json item used to construct the autocomplete selection list element (JSON object)
function formatCoPersonAutoselectItem(ul, item) {
  var itemMarkup = '<div class="cm-ac-item-wrapper">';
  itemMarkup += '<div class="cm-ac-name">' + item.label + '</div>';
  if(item.email != '') {
    itemMarkup += '<div class="cm-ac-subitem cm-ac-email"><span class="cm-ac-label">' + item.emailLabel + '</span>' + item.email + '</div>';
  }
  if(item.identifier != '') {
    itemMarkup += '<div class="cm-ac-subitem cm-ac-id"><span class="cm-ac-label">' + item.identifierLabel + '</span>' + item.identifier + '</div>';
  }
  itemMarkup += '</div>';
  
  return $("<li>").append(itemMarkup).appendTo(ul);
}