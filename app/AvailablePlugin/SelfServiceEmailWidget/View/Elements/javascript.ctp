<?php
/**
 * COmanage Registry Self Service Email Widget JavaScript
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
    $('#cm-ssw-email-widget .cm-ssw-add-email-save-link').click(function(e) {
      e.preventDefault();
      e.stopPropagation();
      var entityId = 'cm-ssw-email-widget';
      var url = '/registry/email_addresses.json';
      var coPersonId = $('#cm-ssw-email-co-person-id').val();
      var emailAddress = $('#cm-ssw-email-address-new').val();
      
      if(emailAddress.trim() === '') {
        $('#cm-ssw-email-address-new').addClass('is-invalid');
        
        //$('<div class="invalid-feedback">< ? php print _txt('pl.self_email_widget.modal.body.add.none'); ? ></div>').insertAfter('#cm-ssw-email-address-new');
        cmSswEmailWidgetModal(
          '<?php print _txt('pl.self_email_widget.modal.title.add.none'); ?>',
          '<?php print _txt('pl.self_email_widget.modal.body.add.none'); ?>',
          'info'
        );
        // $('#cm-ssw-email-address-new').focus(); // XXX Modal steals focus.
        return false;
      }
      
      emailType = $('#cm-ssw-form-field-email-type-new').val();
      data = {
        "RequestType":"EmailAddresses",
        "Version":"1.0",
        "EmailAddresses":
        [
          {
            "Version":"1.0",
            "Mail":emailAddress,
            "Type":emailType,
            "Person":
            {
              "Type":"CO",
              "Id":coPersonId
            }
          }
        ]
      };
      
      callRegistryAPI(url, 'POST', 'json', addSuccessEmailCallback, entityId, addFailEmailCallback, data);
    });
    
    // Check for change events on the update form, and mark the form and the rows that changed
    $('#cm-ssw-email-widget .cm-ssw-update-form input, ' +
      '#cm-ssw-email-widget .cm-ssw-update-form select').on('change', cmSswCheckEmailFormChange);

    // Update all email addresses
    $('#cm-ssw-email-widget .cm-ssw-update-email-save-link').click(function(e) {
      e.preventDefault();
      e.stopPropagation();
      
      // Only send the form along if things have changed:
      if($('#cm-ssw-email-widget .cm-ssw-update-form').hasClass('changed')) {
        var coPersonId = $('#cm-ssw-email-co-person-id').val();
        
        // Iterate over the email addresses that have changed
        $('#cm-ssw-email-widget .cm-ssw-update-form .cm-ssw-form-row.changed').each(function () {
          
          var emailAddress = $(this).find('.cm-ssw-form-field-email').val();
          if (emailAddress === undefined || emailAddress.trim() === '') {
            // Perhaps someone emptied an email address field without deleting the row.
            $(this).addClass('cm-ssw-error');
            $(this).find('.cm-ssw-form-field-email').addClass('is-invalid');
            cmSswEmailWidgetModal(
              '<?php print _txt('pl.self_email_widget.modal.title.add.none'); ?>',
              '<?php print _txt('pl.self_email_widget.modal.body.add.none'); ?>',
              'info'
            );
            // $(this).find('.cm-ssw-form-field-email').focus(); // XXX Modal steals focus
            return false;
          }
          
          var entityId = $(this).data('entity-id');
          var url = '/registry/email_addresses/' + entityId + '.json';
          var emailType = $(this).find('.cm-ssw-form-field-email-type').val();
          
          data = {
            "RequestType": "EmailAddresses",
            "Version": "1.0",
            "EmailAddresses":
              [
                {
                  "Version": "1.0",
                  "Mail": emailAddress,
                  "Type": emailType,
                  "Person":
                    {
                      "Type": "CO",
                      "Id": coPersonId
                    }
                }
              ]
          };

          callRegistryAPI(url, 'PUT', 'html', updateSuccessEmailCallback, entityId, updateFailEmailCallback, data);
        });
      } else {
        cmSswEmailWidgetModal(
          '<?php print _txt('pl.self_email_widget.modal.title.update.nochange'); ?>',
          '<?php print _txt('pl.self_email_widget.modal.body.update.nochange'); ?>',
          'info'
        );
      }
    });

    // Delete an email address
    $('#cm-ssw-email-widget .cm-ssw-form-field-delete-email-link').on('click', cmSswClickEmailDelete);
    
    /* VIEW CHANGE FUNCTIONS */
    // The four following functions are mostly common to most self-service widgets; however, they should be
    // localized for each widget so that they a) don't have to be loaded with the core code when not 
    // needed and b) don't conflict with each other when multiple self-service widgets are loaded.
     
    // Functions for switching between self service widget read-only display and form for editing
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
      // If changes were made to the form, rebuild and reset the form.
      if($('#cm-ssw-email-widget .cm-ssw-update-form').hasClass('changed')) {
        refreshEmailDisplay();
      }
    });

    // Move from update form to add form
    $('#cm-ssw-email-widget .cm-ssw-add-link').click(function(e) {
      e.preventDefault();
      e.stopPropagation();
      parentWidget = $(this).closest('.cm-ssw');
      $(parentWidget).find('.cm-ssw-update-form').addClass('hidden');
      $(parentWidget).find('.cm-ssw-add-form').removeClass('hidden');
      $('#cm-ssw-email-address-new').focus();
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
  
  /* UPDATE HANDLERS */
  /* These handlers are used above and will be reattached to DOM elements on DOM rebuild. */
  
  // Mark the update form when changed
  function cmSswCheckEmailFormChange() {
    $('#cm-ssw-email-widget .cm-ssw-update-form').addClass('changed');
    $(this).closest('.cm-ssw-form-row').addClass('changed');
  }
  
  // Delete an email address
  function cmSswClickEmailDelete(e) {
    e.preventDefault();
    e.stopPropagation();
    entityId = $(this).data('entity-id');
    url = '/registry/email_addresses/' + entityId + '.json';

    $('#cm-ssw-email-modal-confirm').on('click', function() {
      callRegistryAPI(url, 'DELETE', 'html', deleteSuccessEmailCallback, entityId, deleteFailEmailCallback);
    });
    cmSswEmailWidgetModal(
      '<?php print _txt('pl.self_email_widget.modal.title.delete'); ?>',
      '<?php print _txt('pl.self_email_widget.modal.body.delete'); ?>',
      'confirm'
    );
  }

  /* AJAX CALLBACKS */
  /* Callbacks for success and error. */

  // Add email success callback
  function addSuccessEmailCallback(xhr, entityId) {
    /* XXX Restore the following if you want a success message
    cmSswEmailWidgetModal(
      '< ? php print _txt('pl.self_email_widget.modal.title.add.success'); ? >',
      '< ? php print _txt('pl.self_email_widget.modal.body.add.success'); ? >',
      'info'
    ); */
    refreshEmailDisplay();
  }

  // Add email failure callback
  function addFailEmailCallback(xhr, entityId) {
    errorMsg = '<?php print _txt('pl.self_email_widget.modal.body.add.fail'); ?>';
    if(xhr.status === 400) {
      var data = JSON.parse(xhr.responseText);
      if(data.InvalidFields !== undefined) {
        errorMsg = '';
        for(var key in data.InvalidFields) {
          if(data.InvalidFields.hasOwnProperty(key)){
            errorMsg += `${data.InvalidFields[key]} `;
          }
        }
      }
    }
    
    $('#cm-ssw-email-address-new').addClass('is-invalid');
    
    cmSswEmailWidgetModal(
      '<?php print _txt('pl.self_email_widget.modal.title.add.fail'); ?>',
      errorMsg,
      'error',
      xhr
    );
  }
  
  // Update email success callback
  function updateSuccessEmailCallback(xhr, entityId) {
    $('#cm-ssw-email-widget .cm-ssw-update-form').removeClass('changed');
    $('.cm-ssw-form-field-email').removeClass('is-invalid');
    $('#cm-ssw-form-entity-id-' + entityId).removeClass('changed cm-ssw-error');
    cmSswEmailWidgetModal(
      '<?php print _txt('pl.self_email_widget.modal.title.update.success'); ?>',
      '<?php print _txt('pl.self_email_widget.modal.body.update.success'); ?>',
      'info'
    );
  }

  // Update email failure callback
  function updateFailEmailCallback(xhr, entityId) {
    errorMsg = '<?php print _txt('pl.self_email_widget.modal.body.update.fail'); ?>';
    if(xhr.status === 400) {
      var data = JSON.parse(xhr.responseText);
      if(data.InvalidFields !== undefined) {
        errorMsg = '';
        for(var key in data.InvalidFields) {
          if(data.InvalidFields.hasOwnProperty(key)){
            errorMsg += `${data.InvalidFields[key]} `;
          }
        }
      }
    }
    //$('#cm-ssw-form-entity-id-' + entityId).addClass('cm-ssw-error');
    $('#cm-ssw-form-entity-id-' + entityId).find('.cm-ssw-form-field-email').addClass('is-invalid');
    
    cmSswEmailWidgetModal(
      '<?php print _txt('pl.self_email_widget.modal.title.update.fail'); ?>',
      errorMsg,
      'error',
      xhr
    );
  }
  
  // Delete email success callback
  function deleteSuccessEmailCallback(xhr, entityId) {
    // remove the items from the DOM
    $("#cm-ssw-form-entity-id-" + entityId).remove();
    $("#cm-ssw-display-entity-id-" + entityId).remove();
    /* XXX Restore the following if you want a success message.
    cmSswEmailWidgetModal(
      '< ? php print _txt('pl.self_email_widget.modal.title.delete.success'); ? >',
      '< ? php print _txt('pl.self_email_widget.modal.body.delete.success'); ? >',
      'info'
    ); */
  }

  // Delete email failure callback
  function deleteFailEmailCallback(xhr, entityId) {
    cmSswEmailWidgetModal(
      '<?php print _txt('pl.self_email_widget.modal.title.delete.fail'); ?>',
      '<?php print _txt('pl.self_email_widget.modal.body.delete.fail'); ?>',
      'error',
      xhr
    );
  }

  // Common failure callback
  function commonEmailFailureCallback(xhr, entityId) {
    console.log("AJAX failure: " + xhr.status + " for entityId " + entityId);
    cmSswEmailWidgetModal(
      '<?php print _txt('pl.self_email_widget.modal.title.common.fail'); ?>',
      '<?php print _txt('pl.self_email_widget.modal.body.common.fail'); ?> <br>' + xhr.responseText,
      'error',
      xhr
    );
  }
  
  function refreshEmailDisplay() {
    // Clear out the new address form
    $('#cm-ssw-email-address-new').val('');
    // Change the view
    $('#cm-ssw-email-widget .cm-ssw-update-form').addClass('hidden');
    $('#cm-ssw-email-widget .cm-ssw-add-form').addClass('hidden');
    $('#cm-ssw-email-widget .cm-ssw-display').removeClass('hidden');
    // Rebuild the data
    var coPersonId = $('#cm-ssw-email-co-person-id').val();
    url = '/registry/email_addresses.json?copersonid=' + coPersonId;
    callRegistryAPI(url, 'GET', 'json', rebuildSuccessEmailCallback, '', rebuildFailEmailCallback);
  }

  // Rebuild email success callback
  function rebuildSuccessEmailCallback(xhr, entityId) {
    var data = JSON.parse(xhr.responseText);
    console.log(data);
    if(data.EmailAddresses !== undefined) {      
      if(data.EmailAddresses.length > 0) {        
        // Regenerate the output. Begin by throwing away the existing display elements and form rows.
        $('#cm-ssw-email-widget .cm-ssw-display ul.cm-ssw-field-list').empty();
        $('#cm-ssw-email-widget .cm-ssw-update-form .cm-ssw-update-form-rows').empty();

        // Now iterate over the data and rebuild the output.
        data.EmailAddresses.forEach(function(em,i) {
          // Generate the display output
          var output = '';
          output += '<li id="cm-ssw-display-entity-id-' + em.Id + '" data-entity-id="' + em.Id + '">';
          output += em.Mail;
          if(i === 0) { // XXX Test for Primary - this is just mocked up for now and placed on the first.
            output += ' <span class="mr-1 badge badge-outline-primary"><?php print _txt('fd.primary'); ?></span>'
          }
          output += '</li>';
          // Replace the display element
          $('#cm-ssw-email-widget .cm-ssw-display ul.cm-ssw-field-list').append(output);
        
          // Build a template for each form row - we'll do DOM manipulation after we append it.
          output = '';
          output += '<div class="cm-ssw-form-row" id="cm-ssw-form-entity-id-' + em.Id + '" data-entity-id="' + em.Id + '">';
          output +=   '<span class="cm-ssw-form-row-fields">';
          output +=     '<span class="cm-ssw-form-field form-group">';
          output +=       '<label for="cm-ssw-form-field-email-' + em.Id + '"><?php print _txt('pl.self_email_widget.fd.email'); ?></label>';
          output +=       '<input type="text" id="cm-ssw-form-field-email-' + em.Id + '" class="form-control cm-ssw-form-field-email" value="' + em.Mail + '">';
          output +=     '</span>';
          output +=     '<span class="cm-ssw-form-field form-group cm-ssw-form-field-type">';
          output +=       '<label for="cm-ssw-form-field-email-type-' + em.Id + '"><?php print _txt('pl.self_email_widget.fd.type'); ?></label>';
          // create a stub for the type:
          output +=       '<span id="cm-ssw-form-field-email-type-stub">--</span>';
          output +=     '</span>';
          output +=     '<span class="cm-ssw-form-field form-check">';
          output +=       '<input type="radio" class="form-check-input cm-ssw-form-field-primary"';
          output +=              'name="cm-ssw-email-primary" id="cm-ssw-email-' + em.Id + '"';
          // XXX Need to test for primary (when it exists) and check the radio. For now, we'll default to the first.
          if(i === 0) {
            output +=             ' checked';
          }
          output +=              '>';
          output +=       '<label class="form-check-label" for="cm-ssw-email-' + em.Id + '"><?php print _txt('pl.self_email_widget.primary');?></label>';
          output +=     '</span>';
          output +=   '</span>';
          output +=   '<span class="cm-ssw-form-row-actions">';
          output +=     '<span class="cm-ssw-form-field cm-ssw-form-field-delete">';
          output +=       '<a href="#" class="cm-ssw-form-field-delete-email-link" data-entity-id="' + em.Id + '">';
          output +=         '<em class="material-icons" aria-hidden="true">delete</em>';
          output +=         '<?php print _txt('op.delete');?>';
          output +=       '</a>';
          output +=     '</span>';
          output +=   '</span>';
          output += '</div>';
          $('#cm-ssw-email-widget .cm-ssw-update-form .cm-ssw-update-form-rows').append(output);
          // Copy in the type field and set its values:
          var emailTypeFieldTemplate = $("#cm-ssw-form-field-email-type-new").clone().attr('id','cm-ssw-form-field-email-type-' + em.Id).val(em.Type);
          $('#cm-ssw-form-field-email-type-stub').replaceWith(emailTypeFieldTemplate);
        });
        // Reattach event handlers:
        $('#cm-ssw-email-widget .cm-ssw-update-form input, ' +
          '#cm-ssw-email-widget .cm-ssw-update-form select').on('change', cmSswCheckEmailFormChange);
        $('#cm-ssw-email-widget .cm-ssw-form-field-delete-email-link').on('click', cmSswClickEmailDelete);
      }
    } else {
      // There was some issue during rebuild - show the refresh failure message.
      rebuildFailEmailCallback(xhr, entityId);
    }
  }

  // Rebuild email failure callback
  function rebuildFailEmailCallback(xhr, entityId) {
    cmSswEmailWidgetModal(
      '<?php print _txt('pl.self_email_widget.modal.title.refresh.fail'); ?>',
      '<?php print _txt('pl.self_email_widget.modal.body.refresh.fail'); ?>',
      'error',
      xhr
    );
  }
  
  // Modal Dialog Handler
  function cmSswEmailWidgetModal(title, body, type, xhr) {
    $('#cm-ssw-email-modal-title').text(title);
    $('#cm-ssw-email-modal-body').text(body);
    // hide common elements
    $('#cm-ssw-email-modal .modal-footer').hide();
    $('#cm-ssw-email-modal-errors').hide();
    switch(type) {
      case 'confirm': 
        $("#cm-ssw-email-modal-confirm-footer").show();
        break;
      case 'error':
        // catch standard issues and override the modal text
        if(xhr.status === 401) {
          $('#cm-ssw-email-modal-body').html(' <?php print _txt('pl.self_email_widget.error.401'); ?>');
        }
        if(xhr.status === 500) {
          $('#cm-ssw-email-modal-body').html(' <?php print _txt('pl.self_email_widget.error.500'); ?>');
        }
        $('#cm-ssw-email-modal-info-footer').show();
        break;
      default: // info
        $("#cm-ssw-email-modal-info-footer").show();
    }
    $('#cm-ssw-email-modal-confirm').on('click', function () {
      $('#cm-ssw-email-modal').modal('hide');
      $(this).off('click');
    });
    $('#cm-ssw-email-modal').modal('show');
  }  
</script>