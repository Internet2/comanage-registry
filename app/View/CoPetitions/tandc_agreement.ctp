<?php
/**
 * COmanage Registry CO Petition T&C Fields
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
 * @since         COmanage Registry v4.0.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

  // Add breadcrumbs
  print $this->element("coCrumb");
  $this->Html->addCrumb($title_for_layout);

  // Add page title
  $params = array('title' => $title_for_layout);
  print $this->element("pageTitleAndButtons", $params);

  // Add enrollment flow information to sidebar
  $enrollmentFlowSteps = $this->get('enrollmentFlowSteps');
  $enrollmentFlowStepComplete = 'complete';

  foreach(array_keys($vv_configured_steps) as $step) {
    if($vv_configured_steps[$step]['enabled'] != RequiredEnum::NotPermitted
      // We specifically don't want "deny" to render, so we'll skip it here
      && $step != 'deny') {
      $enrollmentFlowSteps[] = array(
        'title'   => $vv_configured_steps[$step]['label'],
        'state' => $step == $vv_current_step ? 'selected' : $enrollmentFlowStepComplete
      );
      if($step == $vv_current_step) {
        $enrollmentFlowStepComplete = 'incomplete';
      }
    }
  }

  $this->set('enrollmentFlowSteps', $enrollmentFlowSteps);

  print $this->Form->create(
    'CoPetition',
    array(
      'url' => array('action' => 'tandcAgreement/' . $vv_co_petition_id),
      'inputDefaults' => array(
        'label' => false,
        'div' => false
      )
    )
  );

  if(!empty($vv_co_petition_id)) {
    print $this->Form->hidden('CoPetition.id', array('default' => $vv_co_petition_id)) . "\n";
  }

  if(!empty($vv_petition_token)) {
    print $this->Form->hidden('CoPetition.token', array('default' => $vv_petition_token)) . "\n";
  }
  
  $l = 0;
?>
<script type="text/javascript">
  var tandcids = new Array(
    <?php
      // Use PHP to dynamically generate a list of T&C IDs into a Javascript array
      if(!empty($vv_terms_and_conditions)) {
        $i = 0;
        foreach($vv_terms_and_conditions as $tac) {
          if ($i > 0){ print ",";} else { $i++; }
          print '"' . $tac['CoTermsAndConditions']['id'] . '"';
        }
      }
    ?>
  );
  
  function open_tandc(title, tandcUrl, id) {
    // Set title
    $("div#dialog-review").dialog("option", "title", title);
    
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

    // Load T&C into iframe
    $("#tandc_content").attr("src", tandcUrl);

    <?php if($vv_tandc_mode == TAndCEnrollmentModeEnum::ExplicitConsent): ?>
    // Enable the checkbox
    document.getElementById('CoTermsAndConditions' + id).disabled = false;
    <?php endif; // explicit consent ?>
  }
  
  function maybe_enable_submit() {
    // If all checkboxes are enabled, enable submit
    
    var allagreed = 1;
    
    <?php if($vv_tandc_mode == TAndCEnrollmentModeEnum::ExplicitConsent): ?>
    for(var i = 0; i < tandcids.length; i++) {
      // Search for every tid if exists at this CoPetition Form and if it is checked
      if(document.getElementById('CoTermsAndConditions' + tandcids[i]) !== null && !document.getElementById('CoTermsAndConditions' + tandcids[i]).checked) {
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
  
  function js_local_onload() {
    // Local (to this view) initializations
    
    <?php if($vv_tandc_mode == TAndCEnrollmentModeEnum::ExplicitConsent): ?>
    // Disable checkboxes until individual T&C are reviewed
    for(var i = 0; i < tandcids.length; i++) {
      // If the box is already ticked (eg: if the form re-renders), make sure it remains enabled
      if(document.getElementById('CoTermsAndConditions' + tandcids[i]) !== null && !document.getElementById('CoTermsAndConditions' + tandcids[i]).checked) {
        document.getElementById('CoTermsAndConditions' + tandcids[i]).disabled = true;
      }
    }
    <?php endif; // explicit consent ?>
    
    // Disable submit button until all T&C are agreed to
    maybe_enable_submit();
  }
  
  $(function() {
    $("#dialog-review").dialog({
      autoOpen: false,
      modal: true,
      draggable: true,
      resizable: true,
      width: document.documentElement.clientWidth * 0.8,
      height: document.documentElement.clientHeight * 0.8,
      buttons: {
        "<?php print _txt('op.ok'); ?>": function() {
          $(this).dialog("close");
        }
      }
    })
  });
</script>

<div class="ui-widget modelbox">
  <div class="tc-title">
    <strong><?php print _txt('op.tc.agree'); ?></strong><br />
    <?php if($vv_tandc_mode == TAndCEnrollmentModeEnum::ExplicitConsent): ?>
    <span class="descr"><?php print _txt('fd.tc.agree.desc'); ?></span>
    <?php elseif($vv_tandc_mode == TAndCEnrollmentModeEnum::ImpliedConsent): ?>
    <span class="descr"><?php print _txt('fd.tc.agree.impl'); ?></span>
    <?php endif; // vv_tandc_mode ?>
  </div>
  <ul id="co_petition_tandc">
    <?php foreach($vv_terms_and_conditions as $t): ?>
      <li class="line<?php print ($l % 2); $l++; ?>">
          <?php print $this->element('termsAndConditionsItem', array("t" => $t['CoTermsAndConditions']));?>
      </li>
    <?php endforeach; ?>
  </ul>
</div>

<div id="<?php print $this->action; ?>_co_petition_attrs_submit" class="submit-box">
  <div class="required-info">
    <em><span class="required"><?php print _txt('fd.req'); ?></span></em><br />
  </div>
  <div class="submit-buttons">
    <?php print $this->Form->submit(_txt('op.submit')); ?>
  </div>
</div>

<?php print $this->Form->end(); ?>

<div id="dialog-review" title="<?php print _txt('ct.co_terms_and_conditions.1'); ?>">
  <iframe id="tandc_content" title="<?php print _txt('ct.co_terms_and_conditions.1'); ?>">
  </iframe>
</div>