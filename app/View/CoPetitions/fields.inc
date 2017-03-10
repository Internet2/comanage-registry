<?php
/**
 * COmanage Registry CO Petitions Fields
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
 * @since         COmanage Registry v0.5
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */
?>

<?php
  // Determine if fields are editable
  $e = false;
  
  if(($this->action == "add" && $permissions['add']) || ($this->action == "edit" && $permissions['edit']))
    $e = true;
    
  // We shouldn't get here if we don't have at least read permission, but check just in case
  
  if(!$e && !$permissions['view'])
    return(false);

  // Add breadcrumbs (if user is logged into the platform)
  if(!isset($noLoginLogout) || !$noLoginLogout) {
    print $this->element("coCrumb");
    $args = array();
    $args['plugin'] = null;
    $args['controller'] = 'co_petitions';
    $args['action'] = 'index';
    $args['co'] = $cur_co['Co']['id'];
    $args['sort'] = 'created';
    $args['search.status'][] = PetitionStatusEnum::PendingApproval;
    $args['search.status'][] = PetitionStatusEnum::PendingConfirmation;

    $this->Html->addCrumb(_txt('ct.petitions.pl'), $args);
    $this->Html->addCrumb(_txt('op.' . $this->action . '-a', array(_txt('ct.petitions.1'))));
  }

  if(isset($cur_co))
    print $this->Form->hidden('co_id', array('default' => $cur_co['Co']['id'])) . "\n";
  
  print $this->Form->hidden('co_enrollment_flow_id', array('default' => filter_var($co_enrollment_flow_id,FILTER_SANITIZE_STRING)));

  if($this->action == 'view') {
    // Add enrollment flow information to sidebar
    
    // This logic probably belongs in the Controller.
    
    $enrollmentFlowSteps = array();
    $stopped = false; // Is the flow stopped?
    $seen = false;    // Have we seen the current step?
    
    foreach(array_keys($vv_configured_steps) as $step) {
      if($vv_configured_steps[$step]['enabled'] != RequiredEnum::NotPermitted
        // We specifically don't want "deny" to render, so we'll skip it here
        && $step != 'deny') {
        if($stopped) {
          // Flow declined/denied, so all remaining steps are stopped
          $state = 'stopped';
        } elseif($seen) {
          $state = 'incomplete';
        } elseif($step == $vv_current_step) {
          $seen = true;
          $state = 'selected';
        } else {
          $state = 'complete';
        }
        
        $enrollmentFlowSteps[] = array(
          'title' => $vv_configured_steps[$step]['label'],
          'state' => $state
        );
        
        if(($step == 'waitForConfirmation' && $co_petitions[0]['CoPetition']['status'] == PetitionStatusEnum::Declined)
           ||
           ($step == 'waitForApproval' && $co_petitions[0]['CoPetition']['status'] == PetitionStatusEnum::Denied)) {
          // No further steps will execute
          $stopped = true;
        }
      }
    }
    
    $this->set('enrollmentFlowSteps', $enrollmentFlowSteps);
  }

  $l = 0;
  
  // Unlike most other views, CO Petitions dynamically generates attributes to be
  // completed according to a configuration, not according to a model. This is a
  // lot of logic to embed in a view file, and plausibly this code should be
  // refactored at some point.
?>

<script type="text/javascript">
  $(function() {
    // Explorer menu toggles
    $(".fieldGroupName").click(function(event) {
      event.preventDefault();
      $(this).next(".fields").slideToggle("fast");
      // toggle the +/- icon:
      if ($(this).find(".ui-icon").hasClass("ui-icon-circlesmall-minus")) {
        $(this).find(".ui-icon").removeClass("ui-icon-circlesmall-minus").addClass("ui-icon-circlesmall-plus");
      } else {
        $(this).find(".ui-icon").removeClass("ui-icon-circlesmall-plus").addClass("ui-icon-circlesmall-minus");
      }
    });
  });
</script>

<div id="petition" class="personExplorer">
  <?php if($this->action != 'add'): ?>

    <a href="#tabs-petition" class="fieldGroupName">
      <span class="ui-icon ui-icon-circlesmall-minus"></span>
      <?php print _txt('ct.co_petitions.1'); ?>
    </a>

    <table id="<?php print $this->action; ?>_co_petition" class="ui-widget fields">
      <tbody>
        <tr class="line<?php print ($l % 2); $l++; ?>">
          <th><?php print _txt('fd.status'); ?></th>
          <td>
            <?php
              print '<span class="status ' . $co_petitions[0]['CoPetition']['status'] . '">';
              print _txt('en.status.pt', null, $co_petitions[0]['CoPetition']['status']);
              print '</span>';

              if($co_petitions[0]['CoPetition']['status'] == PetitionStatusEnum::PendingApproval) {
                if($permissions['approve']) {
                  print $this->Html->link(
                    _txt('op.approve'),
                    array('controller' => 'co_petitions',
                          'action' => 'approve',
                          $co_petitions[0]['CoPetition']['id'],
                          'co' => $co_petitions[0]['CoPetition']['co_id'],
                          'coef' => $co_petitions[0]['CoPetition']['co_enrollment_flow_id']),
                    array('class' => 'checkbutton approve-button')
                  );
                }
              }

              if($co_petitions[0]['CoPetition']['status'] == PetitionStatusEnum::PendingApproval
                 || $co_petitions[0]['CoPetition']['status'] == PetitionStatusEnum::PendingConfirmation) {
                if($permissions['deny']) {
                  print $this->Html->link(
                    _txt('op.deny'),
                    array('controller' => 'co_petitions',
                          'action' => 'deny',
                          $co_petitions[0]['CoPetition']['id'],
                          'co' => $co_petitions[0]['CoPetition']['co_id'],
                          'coef' => $co_petitions[0]['CoPetition']['co_enrollment_flow_id']),
                    array('class' => 'cancelbutton deny-button')
                  );
                }
              }
            ?>
          </td>
        </tr>
        <tr class="line<?php print ($l % 2); $l++; ?>">
          <th><?php print _txt('ct.co_enrollment_flows.1'); ?></th>
          <td><?php print $co_petitions[0]['CoEnrollmentFlow']['name']; ?></td>
        </tr>
        <?php if($co_petitions[0]['CoPetition']['status'] == PetitionStatusEnum::PendingConfirmation
                 && !empty($co_petitions[0]['CoInvite']['expires'])): ?>
        <tr class="line<?php print ($l % 2); $l++; ?>">
          <th><?php print _txt('fd.inv.exp'); ?></th>
          <td><?php print $this->Time->format($co_petitions[0]['CoInvite']['expires'], "%c $vv_tz", false, $vv_tz); ?></td>
        </tr>
        <?php endif; // Invitation expires ?>
        <tr class="line<?php print ($l % 2); $l++; ?>">
          <th><?php print _txt('fd.petitioner'); ?></th>
          <td>
            <?php
              if(isset($co_petitions[0]['PetitionerCoPerson']['PrimaryName'])) {
                print filter_var(generateCn($co_petitions[0]['PetitionerCoPerson']['PrimaryName']),FILTER_SANITIZE_SPECIAL_CHARS);
              } else {
                print _txt('fd.actor.self');
              }
            ?>
          </td>
        </tr>
        <tr class="line<?php print ($l % 2); $l++; ?>">
          <th><?php print _txt('fd.sponsor'); ?></th>
          <td>
            <?php
              if(isset($co_petitions[0]['SponsorCoPerson']['PrimaryName'])) {
                print filter_var(generateCn($co_petitions[0]['SponsorCoPerson']['PrimaryName']),FILTER_SANITIZE_SPECIAL_CHARS);
              }
            ?>
          </td>
        </tr>
        <tr class="line<?php print ($l % 2); $l++; ?>">
          <th><?php print _txt('fd.approver'); ?></th>
          <td>
            <?php
              if(isset($co_petitions[0]['ApproverCoPerson']['PrimaryName'])) {
                print filter_var(generateCn($co_petitions[0]['ApproverCoPerson']['PrimaryName']),FILTER_SANITIZE_SPECIAL_CHARS);
              }
            ?>
          </td>
        </tr>
        <tr class="line<?php print ($l % 2); $l++; ?>">
          <th><?php print _txt('fd.identifier.identifier'); ?></th>
          <td>
            <?php
              if(!empty($co_petitions[0]['CoPetition']['authenticated_identifier'])) {
                print filter_var($co_petitions[0]['CoPetition']['authenticated_identifier'],FILTER_SANITIZE_SPECIAL_CHARS);
              }
            ?>
          </td>
        </tr>
        <tr class="line<?php print ($l % 2); $l++; ?>">
          <th><?php print _txt('fd.created'); ?></th>
          <td><?php print $this->Time->format($co_petitions[0]['CoPetition']['created'], "%c $vv_tz", false, $vv_tz); ?></td>
        </tr>
        <tr class="line<?php print ($l % 2); $l++; ?>">
          <th><?php print _txt('fd.modified'); ?></th>
          <td><?php print $this->Time->format($co_petitions[0]['CoPetition']['modified'], "%c $vv_tz", false, $vv_tz); ?></td>
        </tr>
      </tbody>
    </table>
  <?php endif; // action != add ?>

  <div id="tabs-attributes">
    <a href="#tabs-attributes" class="fieldGroupName">
      <span class="ui-icon ui-icon-circlesmall-minus"></span>
      <?php print _txt('fd.attrs.pet'); ?>
    </a>
    <div class="fields">
      <?php include('petition-attributes.inc'); ?>
    </div>
  </div>
</div>
<?php
  print $this->element("changelog");
