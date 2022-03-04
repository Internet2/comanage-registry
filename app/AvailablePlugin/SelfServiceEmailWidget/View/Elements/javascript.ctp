<?php
/**
 * COmanage Registry Self Service Common Widget JavaScript
 * Contains common JavaScript for all Self Service Widgets
 *
 * This widget provides common CSS and JavaScript for all Self Service Dashboard Widgets.
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
 * @since         COmanage Registry v4.1.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */
?>
<script>
  $(function() {
    // Add an email address
    $('.cm-ssw-add-email-save-link').click(function(e) {
      e.preventDefault();
      e.stopPropagation();
      entityId = 'cm-ssw-email-widget';
      url = '/registry/email_addresses.json';
      coPersonId = $('#cm-ssw-email-co-person-id').val();
      emailAddress = $('#cm-ssw-email-address-new').val();
      
      if(emailAddress.trim() == '') {
        alert("no email address supplied"); // XXX lang
        return false;
      }
      
      emailType = $('#cm-ssw-email-type').val();
      data = {
        "RequestType":"EmailAddresses",
        "Version":"1.0",
        "EmailAddresses":
        [
          {
            "Version":"1.0",
            "Mail":emailAddress,
            "Type":emailType,
            "Description":"",
            "Verified":0,
            "Person":
            {
              "Type":"CO",
              "Id":coPersonId
            }
          }
        ]
      };
      //data = JSON.stringify(dataObj);
      callRegistryAPI(url, 'POST', 'json', addSuccessEmailCallback, entityId, commonEmailFailureCallback, data);
    });

    // Add email callback - after successful add
    function addSuccessEmailCallback(status,entityId) {
      console.log("email address added, status: " + status);
    }


    // Delete an email address
    $('.cm-ssw-form-field-delete-email-link').click(function(e) {
      e.preventDefault();
      e.stopPropagation();
      entityId = $(this).data('entity-id');
      url = '/registry/email_addresses/' + entityId + '.json';
      callRegistryAPI(url, 'DELETE', 'html', deleteSuccessEmailCallback, entityId, commonEmailFailureCallback);
    });
    
    /* COMMON FUNCTIONS */
    // The four following functions are common to most self-service widgets; however, they should be
    // localized for each widget so that they a) don't have to be loaded with the core code when not 
    // needed and b) don't conflict with each other when multiple widgets are loaded.
     
    // Common function for switching between self service widget read-only display and form for editing
    $('#cm-ssw-email-widget .cm-ssw-display').click(function(e) {
      e.preventDefault();
      e.stopPropagation();
      $(this).addClass('hidden');
      $(this).next('.cm-ssw-update-form').removeClass('hidden');
    });

    // Cancel a self service update form and return to read-only display
    $('#cm-ssw-email-widget .cm-ssw-update-cancel').click(function(e) {
      e.preventDefault();
      e.stopPropagation();
      parentWidget = $(this).closest('.cm-ssw');
      $(parentWidget).find('.cm-ssw-update-form').addClass('hidden');
      $(parentWidget).find('.cm-ssw-display').removeClass('hidden');
    });

    // Move from update form to add form
    $('#cm-ssw-email-widget .cm-ssw-add-link').click(function(e) {
      e.preventDefault();
      e.stopPropagation();
      parentWidget = $(this).closest('.cm-ssw');
      $(parentWidget).find('.cm-ssw-update-form').addClass('hidden');
      $(parentWidget).find('.cm-ssw-add-form').removeClass('hidden');
    });

    // Cancel a self service add form and return to update form
    $('#cm-ssw-email-widget .cm-ssw-add-cancel').click(function(e) {
      e.preventDefault();
      e.stopPropagation();
      parentWidget = $(this).closest('.cm-ssw');
      $(parentWidget).find('.cm-ssw-add-form').addClass('hidden');
      $(parentWidget).find('.cm-ssw-update-form').removeClass('hidden');
    });
  });
  
  // Delete email callback - after successful delete
  function deleteSuccessEmailCallback(status,entityId) {
    // remove the items from the DOM
    $("#cm-ssw-form-entity-id-" + entityId).remove();
    $("#cm-ssw-display-entity-id-" + entityId).remove();
  } 
  
  // Common failure callback (XXX temporary - will be replaced with more appropriate behavior and i18n text)
  function commonEmailFailureCallback(status,entityId) {
    console.log("AJAX failure: " + status + " for entityId " + entityId);
  }
</script>