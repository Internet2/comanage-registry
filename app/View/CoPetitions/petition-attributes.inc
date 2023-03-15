<?php
/**
 * COmanage Registry Petition Fields (used to display both petitions and petition-based invitations)
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
 * @link          https://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.7
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

  // Enumerate over all attributes defined for the enrollment flow associated with this petition.
  // We do a series of <?php tags because we can't mix and match embedded tag styles.
  
  $l = 1;
  
  // Track the names of the matching fields and emit them later
  $givenNameAttr = "";
  $familyNameAttr = "";
?>
<script type="text/javascript">
  $(function() {
    $("#sponsors").autocomplete({
      source: "<?php
        // We inject the Petition ID and token into the request in case we need
        // autocomplete for new enrollees (unregistered users)
        $sponsorurl = array(
          'controller' => 'co_people',
          'action' => 'find',
          'co' => $cur_co['Co']['id'],
          'mode' => PeoplePickerModeEnum::Sponsor,
          'petitionid' => $vv_co_petition_id,
          'token' => $vv_petition_token
        );
        
        print $this->Html->url($sponsorurl);
      ?>",
      minLength: 3,
      select: function(event, ui) {
        $("#sponsors").val(ui.item.label);
        $("#EnrolleeCoPersonRoleSponsorCoPersonId").val(ui.item.value);
        return false;
      }
    });

    // Used in inline-edit mode for Sponsor (i.e. when sponsor list > 50)
    $("#cm-ief-button-remove-EnrolleeCoPersonRoleSponsorCoPersonId").click(function() {
      reset_sponsor();
      $(this).closest(".cm-ief-val").hide();
      $(this).closest(".cm-inline-editable-field").find(".cm-ief-widget").removeClass('hidden');
      $("#sponsors").focus();
    });

    $("#managers").autocomplete({
      source: "<?php
        // We inject the Petition ID and token into the request in case we need
        // autocomplete for new enrollees (unregistered users)
        $managerurl = array(
          'controller' => 'co_people',
          'action' => 'find',
          'co' => $cur_co['Co']['id'],
          'mode' => PeoplePickerModeEnum::Manager,
          'petitionid' => $vv_co_petition_id,
          'token' => $vv_petition_token
        );
        
        print $this->Html->url($managerurl);
      ?>",
      minLength: 3,
      select: function(event, ui) {
        $("#managers").val(ui.item.label);
        $("#EnrolleeCoPersonRoleManagerCoPersonId").val(ui.item.value);
        return false;
      }
    });

    // Used in inline-edit mode for Manager
    $("#cm-ief-button-remove-EnrolleeCoPersonRoleManagerCoPersonId").click(function() {
      reset_manager();
      $(this).closest(".cm-ief-val").hide();
      $(this).closest(".cm-inline-editable-field").find(".cm-ief-widget").removeClass('hidden');
      $("#managers").focus();
    });
  });
  
  function reset_manager() {
    $("#managers").val("");
    $("#EnrolleeCoPersonRoleManagerCoPersonId").val(null);
  }
  
  function reset_sponsor() {
    $("#sponsors").val("");
    $("#EnrolleeCoPersonRoleSponsorCoPersonId").val(null);
  }
</script>

<div class="fields">
  <?php
  // Render conclusion text for new petitions
  if(!empty($vv_introduction_text_pa)) {
    print '<div class="modelbox d-flex"><div class="petition-attr-introduction">' . $vv_introduction_text_pa . "</div></div>";
  }
  ?>

  <?php
    // Render introductory text for new petitions
    
    if($this->action == 'add' && !empty($vv_introduction_text)) {
      print '<div class="modelbox">' . $vv_introduction_text . "</div>";
    }
    
    // If a token was passed in, submit it as part of the form
    if(!empty($vv_petition_token)) {
      print $this->Form->hidden('token', array('default' => $vv_petition_token)) . PHP_EOL;
    }
  ?>

  <?php 
  // Sort attributes by model for printing
  $coea_model = array();
  $misc_attr = array();
  $hidden_attr = array();

  foreach($co_enrollment_attributes as $ea) {
    // Sort hidden ones out, but if this is view only don't separate out
    // attributes with default values
    if($ea['hidden']
       && ($this->action != 'view'
              // We have to special case sponsor since the controller might
              // inject a default
           || ($ea['attribute'] != 'r:sponsor_co_person_id'
               && (!isset($ea['default']) || !$ea['default'])))) {
      $hidden_attr[] = $ea;
    } else {
      if(isset($ea['group'])) {  // Sort by group
        $coea_model[ $ea['group'] ][] = $ea;
      } else {
        // Has no group
        if(!empty($ea['label'])) {
          $coea_model[ $ea['label'] ][] = $ea;
        }
      }
    }
  }

  // Print the hidden fields
  foreach($hidden_attr as $ea) {
    if($ea['hidden']) {
      $fieldName = $ea['model'] . '.' . $ea['field'];
    }
    
    // Use isset() instead of !empty() here since some default values are 0.
    if(isset($ea['default'])) {
      print $this->Form->hidden($fieldName, array('default' => $ea['default'])) . PHP_EOL;
    }
  }

  // Print the ones not hidden
  $coe_attribute_index = 0;
  foreach($coea_model as $m => $coe_attributes):

    // Skip any attributes that were deleted before the creation of this petition.
    // For multi-component attributes we just look at the first.
    if(isset($coe_attributes[0]['CoEnrollmentAttribute']['deleted'])
       && $coe_attributes[0]['CoEnrollmentAttribute']['deleted']
       && ($coe_attributes[0]['CoEnrollmentAttribute']['modified']
           < $co_petitions[0]['CoPetition']['created'])) {
      continue;
    }

    $mvpa = (count($coe_attributes) > 1);
  ?>
    <div class="modelbox">
      <div class="boxtitle">
        <?php
          // Output the box title
          if(isset($permissions['viewEA']) && $permissions['viewEA']) {
            print $this->Html->link($m,
                                    array('controller' => 'co_enrollment_attributes',
                                          'action' => 'view',
                                          $coe_attributes[0]['CoEnrollmentAttribute']['id']),
                                          array('class' => 'boxtitle-link'));
          } else {
            print filter_var($m,FILTER_SANITIZE_SPECIAL_CHARS);
          }

          // Is the MVPA required? We'll just check the first attribute since they
          // should all be the same.
          if(($mvpa 
              && isset($coe_attributes[0]['mvpa_required'])
              && $coe_attributes[0]['mvpa_required'] 
              && $this->action != 'view')
             || 
             (!$mvpa && $coe_attributes[0]['CoEnrollmentAttribute']['required'])) {
            print '<span class="required">*</span>' . PHP_EOL;
          }
          
          // Only print the description once if it's the same for all items
          $groupDesc = false;  // Not grouping by default
          $descriptions = array();
          
          foreach($coe_attributes as $ea) {
            if(!empty($ea['description'])) {
              $descriptions[] = $ea['description'];
            }
          }
          $descTypeCount = count(array_count_values($descriptions));
          
          if($descTypeCount == 1 && !empty($coe_attributes[0]['description'])) {
            print "\n<div class=\"desc\">" . filter_var($coe_attributes[0]['description'],FILTER_SANITIZE_SPECIAL_CHARS) . "</div>\n";
            $groupDesc = true; // Set flag for later
          }
          
          if($mvpa
             && (!isset($coe_attributes[0]['mvpa_required'])
                 || !$coe_attributes[0]['mvpa_required'])
             // For attributes that are popup/checklist only, this warning doesn't make sense.
             // There's not a good way to figure out which from the configuration, so we
             // just hardcode a list here.
             && ($coe_attributes[0]['attribute'] != 'g:co_group_member'
             && strncmp($coe_attributes[0]['attribute'], 'i:identifier:', 13))) {
            print '<br /><span class="required">' . _txt('fd.pt.required') . '</span>' . PHP_EOL;
          }
          
          if(!empty($ea['CoEnrollmentAttribute']['co_enrollment_attribute_id'])) {
            print '<br /><span class="required">' . _txt('fd.pt.archived') . '</span>' . PHP_EOL;
          } elseif(isset($ea['CoEnrollmentAttribute']['deleted'])
                   && $ea['CoEnrollmentAttribute']['deleted']) {
            print '<br /><span class="required">' . _txt('fd.pt.deleted') . '</span>' . PHP_EOL;
          }
        ?>
      </div>

      <div class="modelbox-data">
      <?php
        $l = 0; // Reset zebra stripes

        // Output the fields
        foreach ($coe_attributes as $ea):

          // Output disabled fields
          if(!$ea['hidden'] && isset($ea['default']) && !$ea['modifiable']) {
            // Since disabled values don't submit with the form, convert them to hidden
            // fields and change the fieldName for the visible attribute

            $hfieldName = $ea['model'] . '.' . $ea['field'];
            $fieldName = $hfieldName . "-disabled";

            print $this->Form->hidden($hfieldName, array('default' => $ea['default'])) . PHP_EOL;
          } else {
            $fieldName = $ea['model'] . '.' . $ea['field'];
          }

          // Emit the field itself, according to the type of field

          // The type of validation rule can influence what we output.
          $ruleType = 'default';

          if(isset($ea['validate']['content']['rule'][0])) {
            $ruleType = $ea['validate']['content']['rule'][0];
          }

          // XXX need to retrieve current values for edit and view

          if($e) {
            switch($ruleType) {
            case 'inList':
              // This is a select
              print '<div class="form-group">';
              print "\n";

              $args = array();
              // Set a default value if provided
              if(isset($ea['default'])) {
                $args['value'] = $ea['default'];
                $args['disabled'] = !$ea['modifiable'];
              }

              // Calculate what we should do for the COU field
              if(strpos($fieldName, 'cou_id') !== false){
                $ea['required'] = !($ea['allow_empty'] && $vv_allow_empty_cou);
              }
              $args['empty'] = !$ea['required'];
              $args['class'] = 'co-selectfield form-control';
              $args['aria-label'] = $m;

              // Add focusFirst class if the first field
              if ($coe_attribute_index == 0) {
                $args['class'] .= ' focusFirst';
              }

              // Render the form field, label, and error (if any)
              print $this->Form->select($fieldName, $ea['select'], $args);
              // We use only the aria-label attribute on select boxes. A label would look like this:
              // print $this->Form->label($fieldName, $m, array('class' => ''));
              if($this->Form->isFieldError($fieldName)) {
                print $this->Form->error($fieldName, array('class' => 'is-invalid'));
              }

              print "</div>\n";
              break;
            case 'date':
            case 'validateTimestamp':
              // Handle dates specially to generate the popup calendar
              print '<div class="form-group">';

              $args = array();

              // Add starting classes
              $args['class'] = 'form-control';

              if($ea['field'] == 'valid_from') {
                $args['class'] .= ' datepicker-f';
              } elseif($ea['field'] == 'valid_through') {
                $args['class'] .= ' datepicker-u';
              } else {
                $args['class'] .= ' datepicker';
              }

              // Add focusFirst class if the first field
              if ($coe_attribute_index == 0) {
                $args['class'] .= ' focus-first';
              }

              if(isset($ea['default'])) {
                $args['default'] = $ea['default'];
                $args['disabled'] = !$ea['modifiable'];
              }

              // Render the form field, label, and error (if any)
              print $this->Form->label($fieldName, $ea['label']);
              print $this->Form->text($fieldName, $args);
              /* XXX CO-1329: field errors on text fields are output by default; check to see if
                 an error is being properly produced for dates and if this next statement is needed. */
              if($this->Form->isFieldError($fieldName)) {
                print $this->Form->error($fieldName);
              }

              print "</div>\n";
              break;
            default:
              // Use default field - a text input
              print '<div class="form-group">';

              $args = array();
              // An attribute is required if (1) it is part of an MVPA that is required
              // and the field itself is required, or (2) it is not part of an MVPA and
              // the field itself is required
              $args['required'] = false;
              if(isset($ea['mvpa_required'])) {
                if($ea['mvpa_required']) {
                  $args['required'] = $ea['required'];
                }
              } else {
                $args['required'] = $ea['required'];
              }

              // Use a provided default value, if one specified
              if(isset($ea['default'])) {
                $args['default'] = $ea['default'];
                $args['disabled'] = !$ea['modifiable'];
              }

              // Add starting classes
              $args['class'] = 'form-control';

              // Add focusFirst class if the first field
              if ($coe_attribute_index == 0) {
                $args['class'] .= ' focusFirst';
              }

              $matchable = false;
              $matchableInfoId = "";
              if($permissions['match']
                 // We can only have one name field match because of how the
                 // javascript keyup triggered code works. It's a bit tricky
                 // to try to guess the right field (CO Person Official Name?
                 // Org Identity Official Name, but only if also copied to the
                 // CO Person record?), so instead we'll just use the first
                 // name we see.

                 // We'll need to loop through twice, once for given and once
                 // for family
                 && ($givenNameAttr == "" || $familyNameAttr == "")
                 && strstr($ea['attribute'], ':name:')
                 && ($ea['field'] == 'given' || $ea['field'] == 'family')) {
                # XXX Temp hack to enable real-time query. This should
                # instead be enabled for fields with an appropriate flag.
                $args['class'] .= ' matchable';
                $matchable = true;

                if($ea['field'] == 'given') {
                  $givenNameAttr = preg_replace('/\./', '', $ea['model']) . "Given";
                  $matchableInfoId = 'matchable-for-' . $givenNameAttr;
                } elseif($ea['field'] == 'family') {
                  $familyNameAttr = preg_replace('/\./', '', $ea['model']) . "Family";
                  $matchableInfoId = 'matchable-for-' . $familyNameAttr;
                }
              }

              // Set the "required" symbols
              // XXX probably should do this at the top of all field output if used for dates and selects as well.
              $requiredSymbol = '';
              if($ea['required']) {
                if($mvpa && !$ea['mvpa_required']) {
                  $requiredSymbol = ' <span class="required">&dagger;</span>';
                } else {
                  $requiredSymbol = ' <span class="required">*</span>';
                }
              }
              
              if($fieldName == 'EnrolleeCoPersonRole.sponsor_co_person_id') {
                // This only applies when sponsorList() has thrown an OverflowException
                // and we use the people picker, otherwise the sponsor field will
                // be a dropdown via inList

                if($ea['modifiable']) {
                  // Use a people picker
                  print '<div class="cm-inline-editable-field">';
                  $inlineEditableFields['sponsor-val']['set'] = false;

                  if(!empty($vv_default_sponsor)) {
                    $inlineEditableFields['sponsor-val']['set'] = true;

                    print '<div class="cm-ief-val">';
                    print generateCn($vv_default_sponsor['PrimaryName']);
                    print $this->element('inlineEditControls', array(
                      'fieldName' => 'EnrolleeCoPersonRoleSponsorCoPersonId',
                      'actions' => array('remove'),
                      'visible' => true
                    ));
                    print '</div>';
                  }

                  $iefClasses = "cm-ief-widget";
                  if ($inlineEditableFields['sponsor-val']['set']) {
                    $iefClasses .= " hidden";
                  }
                  $requiredHtml = "";
                  $requiredLabel = "";
                  if($args['required']) {
                    $requiredHtml = ' required="required"';
                    $requiredLabel = '<span class="required">*</span>';
                  }

                  print '<div class="' . $iefClasses . '">';
                  print ' <label for="sponsors">'
                        .   filter_var($m,FILTER_SANITIZE_SPECIAL_CHARS)
                        .   $requiredLabel
                        . '</label>
                           <div class="ui-widget">
                             <input id="sponsors" type="text" class="form-control"' . $requiredHtml . '/>'
                        . $this->Form->hidden($fieldName,
                                              array(
                                                // We don't support two sponsors, but if we did this id would break
                                                'id'      => 'EnrolleeCoPersonRoleSponsorCoPersonId',
                                                'default' => (isset($ea['default']) ? $ea['default'] : null)
                                              ))
                        . '</div>
                           <div class="field-desc">
                             <span class="ui-icon ui-icon-info co-info"></span>
                             <em>' . _txt('fd.sponsor.desc') . '</em>
                           </div>';
                  print '</div>'; // cm-ief-widget
                  print '</div>'; // cm-inline-editable-field
                  // We need to unlock the field for autocomplete manipulation
                  $this->Form->unlockField($fieldName);
                } elseif(!empty($vv_default_sponsor)) {
                  // Just print out the name
                  print generateCn($vv_default_sponsor['PrimaryName']);
                }
              } elseif($fieldName == 'EnrolleeCoPersonRole.manager_co_person_id') {
                // Manager always uses a people picker
                
                if($ea['modifiable']) {
                  // Use a people picker
                  print '<div class="cm-inline-editable-field">';
                  $inlineEditableFields['manager-val']['set'] = false;

                  if(!empty($vv_default_manager)) {
                    $inlineEditableFields['manager-val']['set'] = true;

                    print '<div class="cm-ief-val">';
                    print generateCn($vv_default_manager['PrimaryName']);
                    print $this->element('inlineEditControls', array(
                      'fieldName' => 'EnrolleeCoPersonRoleManagerCoPersonId',
                      'actions' => array('remove'),
                      'visible' => true
                    ));
                    print '</div>';
                  }

                  $iefClasses = "cm-ief-widget";
                  if ($inlineEditableFields['manager-val']['set']) {
                    $iefClasses .= " hidden";
                  }
                  $requiredHtml = "";
                  $requiredLabel = "";
                  if($args['required']) {
                    $requiredHtml = ' required="required"';
                    $requiredLabel = '<span class="required">*</span>';
                  }

                  print '<div class="' . $iefClasses . '">';
                  print ' <label for="managers">'
                        .   filter_var($m,FILTER_SANITIZE_SPECIAL_CHARS)
                        .   $requiredLabel
                        . '</label>
                           <div class="ui-widget">
                             <input id="sponsors" type="text" class="form-control"' . $requiredHtml . '/>'
                        . $this->Form->hidden($fieldName,
                                              array(
                                                // We don't support two managers, but if we did this id would break
                                                'id'      => 'EnrolleeCoPersonRoleManagerCoPersonId',
                                                'default' => (isset($ea['default']) ? $ea['default'] : null)
                                              ))
                        . '</div>
                           <div class="field-desc">
                             <span class="ui-icon ui-icon-info co-info"></span>
                             <em>' . _txt('fd.manager.desc') . '</em>
                           </div>';
                  print '</div>'; // cm-ief-widget
                  print '</div>'; // cm-inline-editable-field
                  // We need to unlock the field for autocomplete manipulation
                  $this->Form->unlockField($fieldName);
                } elseif(!empty($vv_default_manager)) {
                  // Just print out the name
                  print generateCn($vv_default_manager['PrimaryName']);
                }
              } else {
                if($fieldName == 'EnrolleeCoPersonRole.sponsor_co_person_id-disabled') {
                  $args['value'] = generateCn($vv_default_sponsor['PrimaryName']);
                } elseif($fieldName == 'EnrolleeCoPersonRole.manager_co_person_id-disabled') {
                  $args['value'] = generateCn($vv_default_manager['PrimaryName']);
                }
                
                // Render the form field, label, and error (if any)
                // error is output by default with class "error-message"
                print $this->Form->label($fieldName, filter_var($ea['label'],FILTER_SANITIZE_SPECIAL_CHARS) . $requiredSymbol);
                print $this->Form->input($fieldName, $args);
              }

              print "</div>" . PHP_EOL;

              if ($matchable) {
                print '<div class="matchable-output" id="' . $matchableInfoId . '">';
                print '</div>';
              }
              break;
            }

            print PHP_EOL;

            // If there is a description for the field, render it under the field and label
            if(isset($ea['description'])
              && !$groupDesc
              && $ea['description'] != "") {
              print PHP_EOL . '<div class="desc">' . $ea['description'] . '</div>' . PHP_EOL;
            }
          } else {

            // Emit the current values for this attribute, if set, with labels
            print '<div class="modelbox-data-field">';
            // Emit the label
            print '<div class="modelbox-data-label">';
            print filter_var($ea['label'],FILTER_SANITIZE_SPECIAL_CHARS);

            /* XXX Don't show required status for view (read-only):
            if($ea['required']) {
              if($mvpa && !$ea['mvpa_required']) {
                print "<span class=\"required\">&dagger;</span>\n";
              } else {
                print "<span class=\"required\">*</span>\n";
              }
            } */

            if(isset($ea['description'])
              && !$groupDesc
              && $ea['description'] != "") {
              print '<div class="desc">' . $ea['description'] . "</div>\n";
            }
            print '</div>'; // modelbox-data-label

            // Emit the value
            print '<div class="modelbox-data-value">';
            if(isset($co_petition_attribute_values[ $ea['id'] ][ $ea['field'] ])) {
              switch($ruleType) {
              case 'inList':
                // It's possible this is no longer a valid selection, especially for extended types.
                if(!empty($ea['select'][ $co_petition_attribute_values[ $ea['id'] ][ $ea['field'] ]])) {
                  print $ea['select'][ $co_petition_attribute_values[ $ea['id'] ][ $ea['field'] ]];
                } else {
                  // No longer a valid enum, just print it
                  print $co_petition_attribute_values[ $ea['id'] ][ $ea['field'] ];
                }
                break;
              case 'date':
              case 'validateTimestamp':
                // Not all fields should render with timezone adjustment.
                // In particular, date_of_birth is referenced independent of timezones.
                // There may be others over time, but for now we just hardcode it.

                if($ea['field'] == 'date_of_birth') {
                  print $this->Time->format($co_petition_attribute_values[ $ea['id'] ][ $ea['field'] ], '%F');
                } else {
                  // CO-2573 We *should* adjust for timezone here but we don't.
                  // CoPetition was never updated to adjust valid_from/valid_through dates
                  // for timezones on save (even though CoPersonRoles was). As such, the
                  // attribute saved was in the browser local timezone and not UTC.
                  // Fixing the save at this point will result in inconsistent attributes
                  // depending on when the petition was created, so we simply drop the
                  // timezone adjustment here and render the original value, which in most
                  // cases will be good enough. (And the operational value in CoPersonRoles
                  // is correct, so the actual impact of this is minimal.)
                  // This shoud be fixed in PE, however.
                  print $this->Time->nice($co_petition_attribute_values[ $ea['id'] ][ $ea['field'] ]); //$vv_tz);
                }
                break;
              default:
                if($ea['field'] == 'sponsor_co_person_id'
                   && !empty($co_petition_attribute_values[ $ea['id'] ]['sponsorPrimaryName'])) {
                  // Special case the sponsor
                  print $co_petition_attribute_values[ $ea['id'] ]['sponsorPrimaryName'];
                } elseif($ea['field'] == 'manager_co_person_id'
                   && !empty($co_petition_attribute_values[ $ea['id'] ]['managerPrimaryName'])) {
                  // Special case the manager
                  print $co_petition_attribute_values[ $ea['id'] ]['managerPrimaryName'];
                } else {
                  // Otherwise just print out the field value
                  print $co_petition_attribute_values[ $ea['id'] ][ $ea['field'] ];
                }
                break;
              }
            }
            print '</div>'; // modelbox-data-value
            print '</div>'; // modelbox-data-field
          }
          $coe_attribute_index++;
        endforeach;
      ?>
      </div><!-- modelbox-data -->
      <span class="clearfix"></span>
    </div><!-- modelbox -->
  <?php endforeach; // foreach ($coea_model as $m => $coe_attributes): ?>
  
  <?php
    // Render conclusion text for new petitions
    if(!empty($vv_conclusion_text)) {
      print '<div class="modelbox d-flex">' . $vv_conclusion_text . "</div>";
    }
  ?>

  <?php if($e): ?>
    <div id="<?php print $this->action; ?>_co_petition_attrs_submit" class="submit-box">
      <div class="required-info">
        <em><span class="required"><?php print _txt('fd.req'); ?></span></em><br />
      </div>
      <div class="submit-buttons">
        <?php print $this->Form->submit(_txt('op.submit')); ?>
      </div>
    </div>
  <?php endif; ?>
  <span class="clearfix"></span>
</div>

<script type="text/javascript">
  givenNameAttr = <?php print '"' . $givenNameAttr . '"'; ?>;
  familyNameAttr = <?php print '"' . $familyNameAttr . '"'; ?>;
</script>
