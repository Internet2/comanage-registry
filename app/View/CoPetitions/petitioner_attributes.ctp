<?php
/**
 * COmanage Registry CO Petition PetitionerAttributes View
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
var givenNameAttr = "";
var familyNameAttr = "";
  
$(document).ready(function() {
  $("input.matchable").keyup(function(event) {
    if(event.which != 13) {
      // 13 is enter/return... don't search on form submit
      // XXX Don't hardcode fields here, or /registry prefix
      var thisFieldId = $(this).attr("id");
      $.ajax({
        url: '/registry/co_people/match/coef:' + <?php print filter_var($co_enrollment_flow_id,FILTER_SANITIZE_URL); ?>
             + '/given:' + document.getElementById(givenNameAttr).value
             + '/family:' + document.getElementById(familyNameAttr).value
      }).done(function(data) {
        //$('#petitionerMatchResults').html(data);
        $("#matchable-for-" + thisFieldId).html(data);

        // provide a close button to manually hide matchable info
        $("#matchable-for-" + thisFieldId + " .close-button").click(function() {
          $(this).closest('.matchable-output').hide();
        });
      });
    }
  });

  // clear out existing matchable output boxes when focusing a matchable field
  $("input.matchable").focus(function() {
    $('.matchable-output').html('').show();
  });
});
</script>
<?php
  // Add breadcrumbs
  print $this->element("coCrumb");
  $this->Html->addCrumb(filter_var($title_for_layout,FILTER_SANITIZE_SPECIAL_CHARS));

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
      if ($step == $vv_current_step) {
        $enrollmentFlowStepComplete = 'incomplete';
      }
    }
  }

  $this->set('enrollmentFlowSteps', $enrollmentFlowSteps);

  // XXX is $submit_label used?
  $submit_label = _txt('op.add');
  
  print $this->Form->create(
    'CoPetition',
    array(
      'url' => array('action' => 'petitionerAttributes'),
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
?>
<div id="tabs-attributes">
  <?php
    $e = true;
    include('petition-attributes.inc');
  ?>
</div>
<?php
  print $this->Form->end();
