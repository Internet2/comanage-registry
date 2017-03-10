<?php
/**
 * COmanage Registry CO Invite Reply View
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
?>
<?php if(!empty($invite)): ?>
<?php
  $params = array('title' => $title_for_layout);
  print $this->element("pageTitle", $params);
  
  $verifyEmail = !empty($invite['CoInvite']['email_address_id']);
?>

<h2 class="ui-state-default"><?php print _txt(($verifyEmail ? 'fd.ev.for' : 'fd.inv.for'),
                                              array(filter_var(generateCn($invitee['PrimaryName']),FILTER_SANITIZE_SPECIAL_CHARS))); ?></h2>

<?php
  if($verifyEmail) {
    print "<p>" . _txt('fd.ev.verify.desc', array($invite['EmailAddress']['mail'])) . "</p>";
    
    print $this->Html->link(
      _txt('op.confirm'),
      array('controller' => 'co_invites',
            'action' => 'authverify',
            $invite['CoInvite']['invitation']),
      array('class' => 'checkbutton')
    );
    
    print $this->Html->link(
      _txt('op.cancel'),
      array('controller' => 'co_invites',
            'action' => 'decline',
            $invite['CoInvite']['invitation']),
      array('class' => 'cancelbutton')
    );
  } else {
    if(empty($co_petitions)) {
      // Default enrollment, put up some basic text
      print "<p>" . _txt('em.invite.body', array($invitee['Co']['name'])) . "</p>";
    }
    
    print $this->Html->link(
      _txt('op.accept'),
      array('controller' => 'co_invites',
            'action' => (isset($co_enrollment_flow['CoEnrollmentFlow']['require_authn'])
                         && $co_enrollment_flow['CoEnrollmentFlow']['require_authn']) ? 'authconfirm' : 'confirm',
            $invite['CoInvite']['invitation']),
      array('class' => 'checkbutton')
    );
    
    print $this->Html->link(
      _txt('op.decline'),
      array('controller' => 'co_invites',
            'action' => 'decline',
            $invite['CoInvite']['invitation']),
      array('class' => 'cancelbutton')
    );
  }
  
  $e = false;

  if(isset($co_petitions)) {
    include ("petition-attributes.inc");
  }
?>
<?php endif;