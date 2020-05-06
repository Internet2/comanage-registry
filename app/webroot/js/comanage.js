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
    theme: 'comanage'
  });
}

// Explicitly generate a spinner. This can normally be done by
// setting the "spin" class on an element.  (See View/Elements/javascript.ctp)
// For dynamically created elements, we can generate the spinner
// dynamically by calling this function.

// Set defaults - this is used by all full-page spinners.
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

// Set defaults - this is used by all mini (localized) spinners.
var coMiniSpinnerOpts = {
  lines: 10, // The number of lines to draw
  length: 4, // The length of each line
  width: 2, // The line thickness
  radius: 2, // The radius of the inner circle
  corners: 0.2, // Corner roundness (0..1)
  rotate: 0, // The rotation offset
  direction: 1, // 1: clockwise, -1: counterclockwise
  color: '#9FC6E2', // #rgb or #rrggbb or array of colors
  speed: 1.2, // Rounds per second
  trail: 60, // Afterglow percentage
  shadow: false, // Whether to render a shadow
  hwaccel: false, // Whether to use hardware acceleration
  className: 'mini-spinner', // The CSS class to assign to the spinner
  zIndex: 100, // The z-index (defaults to 2000000000)
  top: '18px', // Positioning offset
  left: '18px' // Positioning offset
};


// show a spinner
function displaySpinner() {
  var spinnerDiv = '<div id="coSpinner"></div>';
  $("body").append(spinnerDiv);

  var coSpinnerTarget = document.getElementById('coSpinner');
  var coSpinner = new Spinner(coSpinnerOpts).spin(coSpinnerTarget);
}

// stop a spinner explicitly
// assumes spinner is in a div with ID "coSpinner"
function stopSpinner() {
  $("#coSpinner").remove();
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
  var dbuttons = {};
  dbuttons[cxlbutton] = function() { $(this).dialog("close"); };
  dbuttons[confbutton] = function() { window.location = forwardUrl; };
  $("#dialog").dialog("option", "buttons", dbuttons);

  // Open the dialog
  $('#dialog').dialog('open');
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