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
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.7
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

  // Enumerate over all attributes defined for the enrollment flow associated with this petition.
  // We do a series of <?php tags because we can't mix and match embedded tag styles.
  
  $l = 1;
  
  // Are there T&C to agree to?
  $tandc = !empty($vv_terms_and_conditions);
  
  // Track the names of the matching fields and emit them later
  $givenNameAttr = "";
  $familyNameAttr = "";
?>
  
<script type="text/javascript">
  var tandcids = Array(
    <?php
      // Use PHP to dynamically generate a list of T&C IDs into a Javascript array
      if(!empty($vv_terms_and_conditions)) {
        $i = 0;
        foreach($vv_terms_and_conditions as $t) {
          if($i > 0) { print ","; } else { $i++; }
          print '"' . $t['CoTermsAndConditions']['id'] . '"';
        }
      }
    ?>
  );
  
  function open_tandc(title, tandcUrl, id) {
    // Set title
    $("div#dialog-review").dialog("option", "title", title);
    
    // Load T&C into iframe
    $("#tandc_content").attr("src", tandcUrl);
    
    // Set up buttons
    $("div#dialog-review").dialog("option",
                                  "buttons",
                                  {
                                    "<?php print _txt('op.ok'); ?>": function() {
                                      $(this).dialog("close");
                                    }
                                  });
    
    // Open dialog
    $("#dialog-review").dialog("open");
    
    <?php if($tandc && $vv_tandc_mode == TAndCEnrollmentModeEnum::ExplicitConsent): ?>
    // Enable the checkbox
    document.getElementById('CoTermsAndConditions'+id).disabled = false;
    <?php endif; // explicit consent ?>
  }
  
  function maybe_enable_submit() {
    // If all checkboxes are enabled, enable submit
    
    var allagreed = 1;
    
    <?php if($tandc && $vv_tandc_mode == TAndCEnrollmentModeEnum::ExplicitConsent): ?>
    for(var i = 0;i < tandcids.length;i++) {
      if(!document.getElementById('CoTermsAndConditions'+tandcids[i]).checked) {
        allagreed = 0;
      }
    }
    <?php endif; // explicit consent ?>
    
    if(allagreed) {
      $(":submit").removeAttr('disabled');
    } else {
      // Reset disabled in case "I Agree" was unchecked or on initial form rendering
      $(":submit").attr('disabled', true);
    }
  }
  
<?php if($tandc): ?>
  function js_local_onload()
  {
    // Local (to this view) initializations
    
    <?php if($tandc && $vv_tandc_mode == TAndCEnrollmentModeEnum::ExplicitConsent): ?>
    // Disable checkboxes until individual T&C are reviewed
    for(var i = 0;i < tandcids.length;i++) {
      // If the box is already ticked (eg: if the form re-renders), make sure it remains enabled
      if(!document.getElementById('CoTermsAndConditions'+tandcids[i]).checked) {
        document.getElementById('CoTermsAndConditions'+tandcids[i]).disabled = true;
      }
    }
    <?php endif; // explicit consent ?>
    
    // Disable submit button until all T&C are agreed to
    maybe_enable_submit();
    //$(":submit").attr('disabled', true);
  }
<?php endif; ?>
  
  $(function() {
    $("#dialog-review").dialog({
      autoOpen: false,
      height: 725,
      width: 750,
      modal: true,
      buttons: {
        "<?php print _txt('op.ok'); ?>": function() {
          $(this).dialog("close");
        }
      }
    })
  });
</script>

<div id="petitionerMatchResults">
</div>

<div class="fields" style="overflow:hidden;">
  <?php
    // Render introductory text for new petitions
    
    if($this->action == 'add' && !empty($vv_introduction_text)) {
      print '<div class="ui-widget modelbox">' . $vv_introduction_text . "</div>";
    }
    
    // If a token was passed in, submit it as part of the form
    if(!empty($vv_petition_token)) {
      print $this->Form->hidden('token', array('default' => $vv_petition_token)) . "\n";
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
             || !isset($ea['default']) || !$ea['default'])) {
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
      
      print $this->Form->hidden($fieldName, array('default' => $ea['default'])) . "\n"; 
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
    <div class="ui-widget modelbox">
      <div class = "boxtitle">
        <strong>
          <?php
            if(isset($permissions['viewEA']) && $permissions['viewEA']) {
              print $this->Html->link($m,
                                      array('controller' => 'co_enrollment_attributes',
                                            'action' => 'view',
                                            $coe_attributes[0]['CoEnrollmentAttribute']['id']),
                                            array('class' => 'boxtitle-link', 'tabindex' => '-1'));
            } else {
              print filter_var($m,FILTER_SANITIZE_SPECIAL_CHARS);
            }
          ?>
        </strong>
        <?php
          // Is the MVPA required? We'll just check the first attribute since they
          // should all be the same.
          if($mvpa && $coe_attributes[0]['mvpa_required']) {
            print "<span class=\"required\">*</span>\n";
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
            print "</br>\n<span class=\"desc\">" . filter_var($coe_attributes[0]['description'],FILTER_SANITIZE_SPECIAL_CHARS) . "</span>\n";
            $groupDesc = true; // Set flag for later
          }
          
          if($mvpa
             && !$coe_attributes[0]['mvpa_required']
             // For attributes that are popup/checklist only, this warning doesn't make sense.
             // There's not a good way to figure out which from the configuration, so we
             // just hardcode a list here.
             && ($coe_attributes[0]['attribute'] != 'g:co_group_member'
                 &&
                 strncmp($coe_attributes[0]['attribute'], 'i:identifier:', 13))) {
            print "<br /><span class=\"required\">" . _txt('fd.pt.required') . "</span>\n";
          }
          
          if(!empty($ea['CoEnrollmentAttribute']['co_enrollment_attribute_id'])) {
            print "<br /><span class=\"required\">" . _txt('fd.pt.archived') . "</span>\n";
          } elseif(isset($ea['CoEnrollmentAttribute']['deleted'])
                   && $ea['CoEnrollmentAttribute']['deleted']) {
            print "<br /><span class=\"required\">" . _txt('fd.pt.deleted') . "</span>\n";
          }
        ?>
      </div>
      <table class="ui-widget">
        <tbody>
          <?php 
            $l = 0; // Reset zebra stripes for each box
            foreach ($coe_attributes as $ea):
              if(!$ea['hidden'] && isset($ea['default']) && !$ea['modifiable']) {
                // Since disabled values don't submit with the form, convert them to hidden
                // fields and change the fieldName for the visible attribute
                
                $hfieldName = $ea['model'] . '.' . $ea['field'];
                $fieldName = $hfieldName . "-disabled";
                
                print $this->Form->hidden($hfieldName, array('default' => $ea['default'])) . "\n";
              } else {
                $fieldName = $ea['model'] . '.' . $ea['field'];
              }
          ?>
            <tr class="line<?php print ($l % 2); $l++; ?>">
              <td>
                <?php
                  // Emit the label for this field 
                  print "<b>" . filter_var($ea['label'],FILTER_SANITIZE_SPECIAL_CHARS) . "</b>";
                  
                  if($ea['required']) {
                    if($mvpa && !$ea['mvpa_required']) {
                      print "<span class=\"required\">&dagger;</span>\n";
                    } else {
                      print "<span class=\"required\">*</span>\n";
                    }
                  }
                  
                  if(isset($ea['description'])
                    && !$groupDesc
                    && $ea['description'] != "") {
                    print "</br>\n<span class=\"desc\">" . $ea['description'] . "</span>\n";
                  }
                ?>
              </td>
              <td>
                <?php
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
                      $args = array();
                      // Set a default value if provided
                      if(isset($ea['default'])) {
                        $args['value'] = $ea['default'];
                        $args['disabled'] = !$ea['modifiable'];
                      }
                      $args['empty'] = !$ea['required'];

                      // Add focusFirst class if the first field
                      if ($coe_attribute_index == 0) {
                        $args['class'] = 'focusFirst';
                      }
                      
                      print $this->Form->select($fieldName,
                                                $ea['select'],
                                                $args);
                      
                      if($this->Form->isFieldError($fieldName)) {
                        print $this->Form->error($fieldName);
                      }
                      break;
                    case 'validateTimestamp':
                      // Handle dates specially to generate the popup calendar
                      $c = 'datepicker';
                      
                      if($ea['field'] == 'valid_from')
                        $c = 'datepicker-f';
                      elseif($ea['field'] == 'valid_until')
                        $c = 'datepicker-c';
                      
                      $args = array();

                      // Add focusFirst class if the first field
                      if ($coe_attribute_index == 0) {
                        $c = $c . ' focus-first';
                      }

                      $args['class'] = $c;
                      
                      if(isset($ea['default'])) {
                        $args['default'] = $ea['default'];
                        $args['disabled'] = !$ea['modifiable'];
                      }

                      print $this->Form->text($fieldName, $args);
                      
                      if($this->Form->isFieldError($fieldName)) {
                        print $this->Form->error($fieldName);
                      }
                      break;
                    default:
                      // Use default field
                      
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

                      // Add focusFirst class if the first field
                      if ($coe_attribute_index == 0) {
                        $args['class'] = 'focusFirst';
                      }

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
                        if ($coe_attribute_index == 0) {
                          $args['class'] = 'matchable focusFirst';
                        } else {
                          $args['class'] = 'matchable';
                        }

                        if($ea['field'] == 'given') {
                          $givenNameAttr = preg_replace('/\./', '', $ea['model']) . "Given";
                        } elseif($ea['field'] == 'family') {
                          $familyNameAttr = preg_replace('/\./', '', $ea['model']) . "Family";
                        }
                      }
                      
                      print $this->Form->input($fieldName, $args);
                      break;
                    }
                    
                    print "\n";
                  } else {
                    // Just emit the current value for this attribute, if set
                    
                    if(isset($co_petition_attribute_values[ $ea['id'] ][ $ea['field'] ])) {
                      switch($ruleType) {
                      case 'inList':
                        // It's possible this is no longer a valid selection, especially for
                        // extended types.
                        if(!empty($ea['select'][ $co_petition_attribute_values[ $ea['id'] ][ $ea['field'] ]])) {
                          print $ea['select'][ $co_petition_attribute_values[ $ea['id'] ][ $ea['field'] ]];
                        } else {
                          // No longer a valid enum, just print it
                          print $co_petition_attribute_values[ $ea['id'] ][ $ea['field'] ];
                        }
                        break;
                      case 'validateTimestamp':
                        print $this->Time->nice($co_petition_attribute_values[ $ea['id'] ][ $ea['field'] ], $vv_tz);
                        break;
                      default:
                        print $co_petition_attribute_values[ $ea['id'] ][ $ea['field'] ];
                        break;
                      }
                    }
                  }
                ?>
              </td>
            </tr>
          <?php
            $coe_attribute_index++;
            endforeach;
          ?>
        </tbody>
      </table>
    </div>
  <?php 
    endforeach; // foreach ($coea_model as $m => $coe_attributes): 
  ?>

  <?php if(!empty($vv_terms_and_conditions)): ?>
  <?php $line = 0; ?>
    <?php if($this->action == 'view'): ?>
    <div class="ui-widget modelbox">
      <div class="boxtitle">
        <b><?php print _txt('op.tc.agree'); ?></b>
      </div>
      <?php
        // We link to T&C because T&C are not considered part of the petition.
        
        print $this->Html->link(_txt('op.tc.review.pt'),
                                array('controller' => 'co_terms_and_conditions',
                                      'action' => 'review',
                                      'copersonid' => $co_petitions[0]['CoPetition']['enrollee_co_person_id'],
                                      'co' => $co_petitions[0]['CoPetition']['co_id']),
                                array('class' => 'linkbutton'));
      ?>
    </div>
    <?php elseif($vv_tandc_mode == TAndCEnrollmentModeEnum::ExplicitConsent
                 || $vv_tandc_mode == TAndCEnrollmentModeEnum::ImpliedConsent): // action == view ?>
    <div class="ui-widget modelbox">
      <div class="boxtitle">
        <b><?php print _txt('op.tc.agree'); ?></b><br />
        <?php if($vv_tandc_mode == TAndCEnrollmentModeEnum::ExplicitConsent): ?>
        <span class="descr"><?php print _txt('fd.tc.agree.desc'); ?></span>
        <?php elseif($vv_tandc_mode == TAndCEnrollmentModeEnum::ImpliedConsent): ?>
        <span class="descr"><?php print _txt('fd.tc.agree.impl'); ?></span>
        <?php endif; // vv_tandc_mode ?>
      </div>
      <table id="<?php print $this->action; ?>_co_petition_tandc" class="ui-widget">
        <tbody>
          <?php foreach($vv_terms_and_conditions as $t): ?>
          <tr class="line<?php print ($l % 2); $l++; ?>">
            <td>
              <?php
                print _txt('op.tc.agree.i');
                
                $fieldName = "CoTermsAndConditions." . $t['CoTermsAndConditions']['id'];
                $args = array();
                // Note that if authorization is set to "None", this will be empty
                // and the Controller will received the value "on" instead.
                $args['value'] = $this->Session->read('Auth.User.username');
                
                if($vv_tandc_mode == TAndCEnrollmentModeEnum::ExplicitConsent) {
                  // Explicit consent, render a checkbox
                  $args['onClick'] = "maybe_enable_submit()";
                } elseif($vv_tandc_mode == TAndCEnrollmentModeEnum::ImpliedConsent) {
                  // Implicit consent, render as hidden fields instead
                  $args['checked'] = true;
                  // Disabling the checkbox prevents the value from POSTing, and there
                  // is no READONLY attribute on a checkbox. A hack is to use javascript.
                  // A better options would be to use a hidden field here, but that is
                  // triggering a blackhole on submit. Processing entirely server side
                  // is also problematic in the edge case where a new T&C is added after
                  // the form is rendered.
                  //$args['disabled'] = true;
                  $args['onClick'] = "return false";
                }
                
                print $this->Form->checkbox($fieldName, $args);
              ?>
            </td>
            <td>
              <b><?php print filter_var($t['CoTermsAndConditions']['description'],FILTER_SANITIZE_SPECIAL_CHARS); ?></b>
            </td>
            <td>
              <button class="checkbutton"
                      type="button"
                      onClick="open_tandc('<?php print addslashes($t['CoTermsAndConditions']['description']); ?>',
                                          '<?php print addslashes($t['CoTermsAndConditions']['url']); ?>',
                                          '<?php print addslashes($t['CoTermsAndConditions']['id']); ?>')">
                <?php print _txt('op.tc.review'); ?>
              </button>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; // action == view ?>
  <?php endif; // T&C ?>
  
  <?php
    // Render conclusion text for new petitions
    
    if(!empty($vv_conclusion_text)) {
      print '<div class="ui-widget modelbox">' . $vv_conclusion_text . "</div>";
    }
  ?>
  
  <table id="<?php print $this->action; ?>_co_petition_attrs_submit" class="ui-widget">
    <tbody>
      <tr>
        <td>
          <em><span class="required"><?php print _txt('fd.req'); ?></span></em><br />
        </td>
        <td class="submitCell">
          <?php
            if($e) {
              print $this->Form->submit(_txt('op.submit'));
              print $this->Form->button(_txt('op.reset'), 
                                        array('type'=>'reset'));
            }
          ?>
        </td>
      </tr>
    </tbody>
  </table>
</div>
<?php if($this->action == 'view'): ?>
  <a href="#tabs-history" class="fieldGroupName">
    <span class="ui-icon ui-icon-circlesmall-minus"></span>
    <?php print _txt('fd.history.pt'); ?>
  </a>
  <div id="tabs-history" class="fields">
    <table id="<?php print $this->action; ?>_co_petition_attrs" class="ui-widget">
      <tbody>
        <tr>
          <th class="ui-state-default"><?php print _txt('fd.action'); ?></th>
          <th class="ui-state-default"><?php print _txt('fd.actor'); ?></th>
          <th class="ui-state-default"><?php print _txt('fd.comment'); ?></th>
          <th class="ui-state-default"><?php print _txt('fd.timestamp.tz', array($vv_tz)); ?></th>
        </tr>
        
        <?php foreach ($co_petitions[0]['CoPetitionHistoryRecord'] as $hr): ?>
        <tr class="line<?php print ($l % 2); $l++; ?>">
          <td><?php print $hr['action']; ?></td>
          <td><?php print (!empty($hr['ActorCoPerson']['PrimaryName']) ? filter_var(generateCn($hr['ActorCoPerson']['PrimaryName']),FILTER_SANITIZE_SPECIAL_CHARS) : _txt('fd.actor.self')); ?></td>
          <td><?php print $hr['comment']; ?></td>
          <td><?php print $this->Time->nice($hr['created'], $vv_tz); ?></td>
        </tr>
        <?php endforeach; // CoPetitionHistoryRecord ?>
      </tbody>
    </table>
  </div>
<?php endif; // action == view ?>

<div id="dialog-review" title="<?php print _txt('ct.co_terms_and_conditions.1'); ?>">
  <iframe id="tandc_content" title="<?php print _txt('ct.co_terms_and_conditions.1'); ?>" height="600" width="700">
  </iframe>
</div>

<script type="text/javascript">
  givenNameAttr = <?php print '"' . $givenNameAttr . '"'; ?>;
  familyNameAttr = <?php print '"' . $familyNameAttr . '"'; ?>;
</script>