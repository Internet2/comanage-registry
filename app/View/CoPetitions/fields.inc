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
  
  // Approval confirmation mode and commenting 
  $approvalConfirmationMode = $co_petitions[0]['CoEnrollmentFlow']['approval_confirmation_mode'] ?? EnrollmentApprovalConfirmationModeEnum::Never;
  $approvalRequireComment = $co_petitions[0]['CoEnrollmentFlow']['approval_require_comment'] ?? EnrollmentApprovalConfirmationModeEnum::Never;
  
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
        if ($(this).find(".material-icons").text() == "expand_less") {
          $(this).find(".material-icons").text("expand_more");
          $(this).attr('title','<?php print _txt('op.expand'); ?>');
          $(this).attr('aria-expanded','false');
        } else {
          $(this).find(".material-icons").text("expand_less");
          $(this).attr('title','<?php print _txt('op.collapse'); ?>');
          $(this).attr('aria-expanded','true');
        }
      });
    });
    
    function confirmPetition(action, confirmationMode, requireCommentMode) {
      // First check if we need to enforce the Comment box. We only need to do this with 
      // JavaScript if the mode is "Approval Only" or "Denial Only". If the comment field is configured 
      // to enforce "Always", we set the field to required and let the browser handle it directly.
      if((action == 'approve' && requireCommentMode == 'AP') || (action == 'deny' && requireCommentMode == 'D')) {
        if($('#CoPetitionApproverComment').val().trim() == '') {
          msg = (action == 'approve' ? '<?php print (_txt('fd.ef.appr.invalid.appr')); ?>' : '<?php print (_txt('fd.ef.appr.invalid.deny')); ?>');
          $('#approver-comment-field-error').text(msg).show();
          $('#CoPetitionApproverComment').focus();
          return false; 
        }
      }
      
      // Check the confirmation mode and provide confirmations as necessary
      if(confirmationMode == 'AL' || (confirmationMode == 'AP' && action == 'approve') || (confirmationMode == 'D' && action == 'deny')) {
        msg = (action == 'approve' ? '<?php print (_txt('fd.ef.appr.approve.confirm')); ?>' : '<?php print (_txt('fd.ef.appr.deny.confirm')); ?>');
        confirmed = confirm(msg);
      } else {
        confirmed = true;
      }
  
      if(confirmed) {
        $('#approve-deny-buttons input').removeClass('nospin');
        displaySpinner();
        return true;
      }
      
      return false;
    }
  </script>

  <div id="<?php print $this->action; ?>_co_petitions" class="explorerContainer">
    <div id="petition" class="personExplorer">
      <ul>
        <?php if($this->action != 'add'): ?>

          <li id="fields-petition" class="fieldGroup">

            <a href="#tabs-petition" class="fieldGroupName" title="<?php print _txt('op.collapse') ?>" aria-expanded="true" aria-controls="<?php print $this->action; ?>_co_petition">
              <em class="material-icons" aria-hidden="true">expand_less</em>
              <h2><?php print _txt('ct.co_petitions.1'); ?></h2>
            </a>

            <ul id="<?php print $this->action; ?>_co_petition" class="fields form-list">
              <li>
                <div class="field-name field-name-status"><?php print _txt('fd.status'); ?></div>
                <div class="field-info">
                  <?php
                    print '<span class="status ' . $co_petitions[0]['CoPetition']['status'] . '">';
                    print _txt('en.status.pt', null, $co_petitions[0]['CoPetition']['status']);
                    print '</span>';
                    
                    if(($co_petitions[0]['CoPetition']['status'] == PetitionStatusEnum::PendingApproval
                        || $co_petitions[0]['CoPetition']['status'] == PetitionStatusEnum::PendingConfirmation)
                       && $permissions['deny']) {

                      // We create a form here as part of CO-1658, since it's cleaner than
                      // grabbing the comment and stuffing it into a GET paramater on submit.
                      // For now this isn't a problem because even though we're in fields.inc,
                      // petitions only get rendered via view, not edit. However, whenever CO-431
                      // (edit petitions) gets implemented this would change. At that time
                      // this may need to be reimplemented, possibly with the use of the HTML5
                      // "form" attribute eg <input type="text" form="approverForm" ...>
                      // (browser support should be better by then), or by simply only allowing
                      // approval/denial when viewing (not editing) the petition.

                      $args = array(
                        'type' => 'post',
                        'url' => array(
                          'controller' => 'co_petitions', 
                          'action' => 'approve',
                          $co_petitions[0]['CoPetition']['id']
                        )
                      );

                      print $this->Form->create('CoPetition', $args);

                      print '<div id="approve-deny-buttons">';  

                      if($co_petitions[0]['CoPetition']['status'] == PetitionStatusEnum::PendingApproval
                         && $permissions['approve']) {
                        print $this->Form->submit(_txt('op.approve'),
                          array(
                            'class' => 'approve-button btn btn-primary nospin',
                            'name'  => 'action',
                            'onclick' => "return confirmPetition('approve','" . $approvalConfirmationMode . "','" . $approvalRequireComment . "');")
                        );
                      }
                      print $this->Form->submit(_txt('op.deny'),
                        array(
                          'class' => 'deny-button btn btn-primary nospin',
                          'name'  => 'action',
                          'onclick' => "return confirmPetition('deny','" . $approvalConfirmationMode . "','" . $approvalRequireComment . "');")
                      );
                      
                      print '</div>';

                      $approverCommentLabel = _txt('fd.pt.approver_comment');
                      $requireComment = false; 
                      if($approvalRequireComment != EnrollmentApprovalConfirmationModeEnum::Never) {
                        $approverCommentLabel = '<span class="required">*</span> ' . _txt('fd.pt.approver_comment');
                        if($approvalRequireComment == EnrollmentApprovalConfirmationModeEnum::Always) {
                          $requireComment = true; // for require on approval or denial, we must enforce this with JavaScript only
                        }
                      }
                      print $this->Form->input('approver_comment',
                                               array(
                                                 'label' => $approverCommentLabel,
                                                 'required' => $requireComment,
                                                 'placeholder' => _txt('en.enrollment.confmode.placeholder', null, $approvalRequireComment)
                                              ));
                      print '<div id="approver-comment-field-error" class="invalid-feedback" style="display: none;"></div>';
                      print '<div class="field-desc">' . _txt('fd.pt.approver_comment.desc') . '</div>';

                      print $this->Form->end();
                    }
                  ?>
                </div>
              </li>
              <li>
                <div class="field-name"><?php print _txt('ct.co_enrollment_flows.1'); ?></div>
                <div class="field-info">
                  <?php
                  print $this->Html->link($vv_co_enrollment_flow['CoEnrollmentFlow']['name'],
                                          array('controller' => 'co_enrollment_flows',
                                                'action' => ($permissions['edit'] ? 'edit' : ($permissions['view'] ? 'view' : '')),
                                            $vv_co_enrollment_flow['CoEnrollmentFlow']['id']));

                  $badge_list = array();
                  if($vv_co_enrollment_flow['CoEnrollmentFlow']['deleted']) {
                    $badge_list[] = array(
                      'order' => $this->Badge->getBadgeOrder('Other'),
                      'text' => _txt('fd.deleted'),
                      'color' => $this->Badge->getBadgeColor('Danger'),
                    );
                  }

                  if(!is_null($vv_co_enrollment_flow['CoEnrollmentFlow']['co_enrollment_flow_id'])) {
                    $badge_list[] = array(
                      'order' => $this->Badge->getBadgeOrder('Other'),
                      'text' => _txt('fd.ef.archived'),
                      'color' => $this->Badge->getBadgeColor('Warning'),
                    );
                  }

                  if(!empty($badge_list)) {
                    print $this->element('badgeList', array('vv_badge_list' => $badge_list));
                  }
                  ?>
                </div>
              </li>
              <?php if($co_petitions[0]['CoPetition']['status'] == PetitionStatusEnum::PendingConfirmation
                       && !empty($co_petitions[0]['CoInvite']['expires'])): ?>
              <li>
                <div class="field-name"><?php print _txt('fd.inv.exp'); ?></div>
                <div class="field-info"><?php print $this->Time->format($co_petitions[0]['CoInvite']['expires'], "%c $vv_tz", false, $vv_tz); ?></div>
              </li>
              <?php endif; // Invitation expires ?>
              <li>
                <div class="field-name"><?php print _txt('fd.petitioner'); ?></div>
                <div class="field-info">
                  <?php
                    if(isset($co_petitions[0]['PetitionerCoPerson']['PrimaryName'])) {
                      print filter_var(generateCn($co_petitions[0]['PetitionerCoPerson']['PrimaryName']),FILTER_SANITIZE_SPECIAL_CHARS);
                    } else {
                      print _txt('fd.actor.self');
                    }
                  ?>
                </div>
              </li>
              <li>
                <div class="field-name"><?php print _txt('fd.sponsor'); ?></div>
                <div class="field-info">
                  <?php
                    if(isset($co_petitions[0]['SponsorCoPerson']['PrimaryName'])) {
                      print filter_var(generateCn($co_petitions[0]['SponsorCoPerson']['PrimaryName']),FILTER_SANITIZE_SPECIAL_CHARS);
                    }
                  ?>
                </div>
              </li>
              <li>
                <div class="field-name"><?php print _txt('fd.approver'); ?></div>
                <div class="field-info">
                  <?php
                    if(isset($co_petitions[0]['ApproverCoPerson']['PrimaryName'])) {
                      print filter_var(generateCn($co_petitions[0]['ApproverCoPerson']['PrimaryName']),FILTER_SANITIZE_SPECIAL_CHARS);
                    }
                  ?>
                </div>
              </li>
              <li>
                <div class="field-name"><?php print _txt('fd.pt.approver_comment'); ?></div>
                <div class="field-info">
                  <?php
                    if(isset($co_petitions[0]['CoPetition']['approver_comment'])) {
                      print filter_var($co_petitions[0]['CoPetition']['approver_comment'],FILTER_SANITIZE_SPECIAL_CHARS);
                    }
                  ?>
                </div>
              </li>
              <li>
                <div class="field-name"><?php print _txt('fd.identifier.identifier'); ?></div>
                <div class="field-info">
                  <?php
                    if(!empty($co_petitions[0]['CoPetition']['authenticated_identifier'])) {
                      print filter_var($co_petitions[0]['CoPetition']['authenticated_identifier'],FILTER_SANITIZE_SPECIAL_CHARS);
                    }
                  ?>
                </div>
              </li>
              <li>
                <div class="field-name"><?php print _txt('en.identifier.type', null, IdentifierEnum::Reference); ?></div>
                <div class="field-info">
                  <?php
                    if(!empty($co_petitions[0]['CoPetition']['reference_identifier'])) {
                      print filter_var($co_petitions[0]['CoPetition']['reference_identifier'],FILTER_SANITIZE_SPECIAL_CHARS);
                    }
                  ?>
                </div>
              </li>
              <li>
                <div class="field-name"><?php print _txt('ct.vetting_requests.1'); ?></div>
                <div class="field-info">
                  <?php
                    if(!empty($co_petitions[0]['CoPetition']['vetting_request_id'])) {
                      print $this->Html->link(
                        $co_petitions[0]['CoPetition']['vetting_request_id'],
                        array(
                          'controller'    => 'vetting_requests',
                          'action'        => 'view',
                          $co_petitions[0]['CoPetition']['vetting_request_id']
                        )
                      );
                    }
                  ?>
                </div>
              </li>
              <li>
                <div class="field-name"><?php print _txt('fd.created'); ?></div>
                <div class="field-info"><?php print $this->Time->format($co_petitions[0]['CoPetition']['created'], "%c $vv_tz", false, $vv_tz); ?></div>
              </li>
              <li>
                <div class="field-name"><?php print _txt('fd.modified'); ?></div>
                <div class="field-info"><?php print $this->Time->format($co_petitions[0]['CoPetition']['modified'], "%c $vv_tz", false, $vv_tz); ?></div>
              </li>
            </ul>
          </li>
          
          <li id="identities-petition" class="fieldGroup">
            <a href="#tabs-identities" class="fieldGroupName" title="<?php print _txt('op.collapse') ?>" aria-expanded="true" aria-controls="co_petition_identities">
              <em class="material-icons" aria-hidden="true">expand_less</em>
              <h2><?php print _txt('fd.pt.identities'); ?></h2>
            </a>

            <ul id="co_petition_identities" class="fields form-list">
              <li>
                <div class="field-name"><?php print _txt('ct.co_people.1'); ?></div>
                <div class="field-info">
                  <?php
                    if(!empty($co_petitions[0]['EnrolleeCoPerson']['id'])) {
                      print $this->Html->link(
                        generateCn($co_petitions[0]['EnrolleeCoPerson']['PrimaryName']),
                        array(
                          'controller'    => 'co_people',
                          'action'        => 'canvas',
                          $co_petitions[0]['EnrolleeCoPerson']['id']
                        )
                      );
                    }
                  ?>
                </div>
              </li>
              <li>
                <div class="field-name"><?php print _txt('ct.co_person_roles.1'); ?></div>
                <div class="field-info">
                  <?php
                    if(!empty($co_petitions[0]['CoPetition']['enrollee_co_person_role_id'])) {
                      print $this->Html->link(
                        $co_petitions[0]['CoPetition']['enrollee_co_person_role_id'],
                        array(
                          'controller'    => 'co_person_roles',
                          'action'        => 'edit',
                          $co_petitions[0]['CoPetition']['enrollee_co_person_role_id']
                        )
                      );
                    }
                  ?>
                </div>
              </li>
              <li>
                <div class="field-name"><?php print _txt('ct.org_identities.1'); ?></div>
                <div class="field-info">
                  <?php
                    if(!empty($co_petitions[0]['EnrolleeOrgIdentity']['id'])) {
                      print $this->Html->link(
                        generateCn($co_petitions[0]['EnrolleeOrgIdentity']['PrimaryName']),
                        array(
                          'controller'    => 'org_identities',
                          'action'        => 'edit',
                          $co_petitions[0]['EnrolleeOrgIdentity']['id']
                        )
                      );
                    }
                  ?>
                </div>
              </li>
              <li>
                <div class="field-name"><?php print _txt('ct.org_identity_source_records.pl'); ?></div>
                <div class="field-info">
                  <?php
                    if(!empty($co_petitions[0]['OrgIdentitySourceRecord'])) {
                      foreach($co_petitions[0]['OrgIdentitySourceRecord'] as $oisr) {
                        print $this->Html->link(
                          generateCn($oisr['OrgIdentity']['PrimaryName']),
                          array(
                            'controller'    => 'org_identity_source_records',
                            'action'        => 'view',
                            $oisr['id']
                          )
                        )
                        . " (" . $this->Html->link(
                          $oisr['OrgIdentitySource']['description'],
                          array(
                            'controller'  => 'org_identity_sources',
                            'action'      => 'edit',
                            $oisr['OrgIdentitySource']['id']
                          )
                        ) . ")<br />";
                      }
                    }
                  ?>
                </div>
              </li>
            </ul>
          </li>
        <?php endif; // action != add ?>
        
        <li id="tabs-attributes" class="fieldGroup">
          <a href="#tabs-attributes" class="fieldGroupName" title="<?php print _txt('op.collapse') ?>" aria-expanded="true" aria-controls="tabs-petition-attributes">
            <em class="material-icons" aria-hidden="true">expand_less</em>
            <h2><?php print _txt('fd.attrs.pet'); ?></h2>
          </a>

          <div id="tabs-petition-attributes" class="fields">
            <?php include('petition-attributes.inc'); ?>
          </div>
        </li>

        <?php if($this->action == 'view'): ?>
          <!-- Enroller plugin -->
          <?php
          // CO-2541, CO-2571
          if(!empty($co_petitions[0]["CoEnrollmentFlow"]["CoEnrollmentFlowWedge"])) {
            // Sort by the order field
            $wedges = Hash::sort($co_petitions[0]["CoEnrollmentFlow"]["CoEnrollmentFlowWedge"], '{n}.ordr', 'asc');
            foreach ($wedges as $wedge) {
              foreach (['LOCAL', 'APP'] as $plugin_path) {
                $file_path = constant($plugin_path)
                  . "Plugin/"
                  . $wedge["plugin"]
                  . "/View/Elements"
                  . "/petitionAttributes.ctp";

                if(file_exists($file_path)) {
                  print $this->element($wedge["plugin"] . '.petitionAttributes',
                                       array('vv_wedge' => $wedge,
                                             'vv_petition' => $co_petitions[0]['CoPetition']));
                }
              }
            }
          }
          ?>

          <!--    Petition History    -->
          <a href="#tabs-history" class="fieldGroupName" title="<?php print _txt('op.collapse') ?>" aria-expanded="true" aria-controls="tabs-history">
            <em class="material-icons" aria-hidden="true">expand_less</em>
            <h2><?php print _txt('fd.history.pt'); ?></h2>
          </a>
          <ul id="tabs-history" class="fields data-list data-table">
            <li>
              <div class="table-container">
                <table id="<?php print $this->action; ?>_co_petition_attrs">
                  <tbody>
                  <tr>
                    <th><?php print _txt('fd.action'); ?></th>
                    <th><?php print _txt('fd.actor'); ?></th>
                    <th><?php print _txt('fd.comment'); ?></th>
                    <th><?php print _txt('fd.timestamp.tz', array($vv_tz)); ?></th>
                  </tr>

                  <?php foreach ($co_petitions[0]['CoPetitionHistoryRecord'] as $hr): ?>
                    <tr class="line<?php print ($l % 2); $l++; ?>">
                      <td><?php print $hr['action']; ?></td>
                      <td><?php print (!empty($hr['ActorCoPerson']['PrimaryName']) ? filter_var(generateCn($hr['ActorCoPerson']['PrimaryName']),FILTER_SANITIZE_SPECIAL_CHARS) : _txt('fd.actor.self')); ?></td>
                      <td><?php print filter_var($hr['comment'],FILTER_SANITIZE_SPECIAL_CHARS); ?></td>
                      <td><?php print $this->Time->nice($hr['created'], $vv_tz); ?></td>
                    </tr>
                  <?php endforeach; // CoPetitionHistoryRecord ?>
                  </tbody>
                </table>
              </div>
            </li>
          </ul>
        <?php endif; // action == view ?>
      </ul>
    </div>
  </div>
<?php
  print $this->element("changelog");
