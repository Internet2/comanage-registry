<?php
/**
 * COmanage Registry CO Petition PetitionerAttributes View
 *
 * Copyright (C) 2016 University Corporation for Advanced Internet Development, Inc.
 * 
 * Licensed under the Apache License, Version 2.0 (the "License"); you may not use this file except in compliance with
 * the License. You may obtain a copy of the License at
 * 
 * http://www.apache.org/licenses/LICENSE-2.0
 * 
 * Unless required by applicable law or agreed to in writing, software distributed under
 * the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY
 * KIND, either express or implied. See the License for the specific language governing
 * permissions and limitations under the License.
 *
 * @copyright     Copyright (C) 2016 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v1.1.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */
?>
<?php
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
      'url' => array('action' => 'selectOrgIdentity/' . $vv_co_petition_id),
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
<div>
  <div>
    <?php
      $submit_label = _txt('op.search');
      $skip_submit = false;
      
      if($vv_ois_mode == 'email') {
        print _txt('fd.ois.search.mail') . "<p>";
        print $this->Form->input('OrgIdentitySource.mail', array('required' => true)) . "<p>";
      } elseif($vv_ois_mode == 'email-token') {
        print _txt('fd.ois.search.token', array($vv_ois_mail)) . "<br />";
        // XXX for now we need to re-include the email address, until the token validation is actually implemented
        print $this->Form->hidden('OrgIdentitySource.mail', array('default' => $vv_ois_mail)) . "<p>";
        // Note this is OrgIdentitySource.token, not CoPetition.token (which is used by the enrollment flow)
        print $this->Form->input('OrgIdentitySource.token', array('required' => true)) . "<p>";
        
        $submit_label = _txt('op.submit');
      } elseif($vv_ois_mode == 'email-select') {
        $submit_label = _txt('op.select');
        $options = array();
        $attributes = array(
          'legend' => _txt('fd.ois.search.select', array($vv_ois_mail)),
          'separator' => '<br />'
        );
        
        // Did we emit anything enabled?
        $any_enabled = false;
        // Did we emit anything disabled?
        $any_disabled = false;
        
        foreach($vv_ois_candidates as $oisid => $ois_candidate) {
          foreach($ois_candidate as $key => $c) {
            $xid = $oisid . "/" . $key;
            $xlabel = $c['OrgIdentitySource']['description'] . ": "
                      . "<b>" . generateCn($c['OrgIdentitySourceData']['PrimaryName']) . "</b>"
                      . " (" . filter_var($key,FILTER_SANITIZE_SPECIAL_CHARS) . ")";
            
            $options[$xid] = $xlabel;
            
            if(!empty($c['OrgIdentity'])) {
              // There's already an attached Org Identity, don't allow this to be selected
              $attributes['disabled'][] = $xid;
              $any_disabled = true;
            } else {
              $any_enabled = true;
            }
          }
        }
        
        if(count($vv_ois_candidates) > 0) {
          // We want to emit any results for UX purposes, even if all options are disabled
          // (already linked to Org Identities)
          // Re-embed mail for the return
          print $this->Form->hidden('OrgIdentitySource.mail', array('default' => $vv_ois_mail)) . "<p>";
          print $this->Form->radio('OrgIdentitySource.selection', $options, $attributes);
        } else {
          print _txt('er.ois.search.mail.none', array($vv_ois_mail));
        }
        
        if(!$any_enabled) {
          $skip_submit = true;
        }
        
        if($any_disabled) {
          print "<p><i>" . _txt('fd.ois.search.select.disabled') . "</i></p>\n";
        }
      }
      
      if(!$skip_submit) {
        print '<p>';
        print $this->Form->submit($submit_label);
        print $this->Form->button(_txt('op.reset'), 
                                  array('type'=>'reset'));
        print '</p>';
      }
    ?>
  </div>
</div>
<?php
  print $this->Form->end();
