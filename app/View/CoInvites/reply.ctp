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
<?php if(isset($vv_confirmation_expired) && $vv_confirmation_expired): ?>
<div class="co-info-topbox">
  <em class="material-icons">info</em>
  <div class="co-info-topbox-text">
    <?php print _txt('in.inv.exp.resent'); ?>
  </div>
</div>
<?php else: /* $vv_confirmation_expired */ ?>
<?php if(!empty($invite)): ?>
<?php
  $params = array('title' => $title_for_layout);
  print $this->element("pageTitle", $params);
  
  $verifyEmail = !empty($invite['CoInvite']['email_address_id']);
?>

<div class="invitation">
  <span class="invitation-text">
    <?php print _txt(($verifyEmail ? 'fd.ev.for' : 'fd.inv.for'),
      array(filter_var(generateCn($invitee['PrimaryName']),FILTER_SANITIZE_SPECIAL_CHARS))); ?>
  </span>

<?php
  if($verifyEmail) {
    print "<p>" . _txt('fd.ev.verify.desc', array($invite['EmailAddress']['mail'])) . "</p>";
  } elseif(empty($co_petitions)) {
    // Default enrollment, put up some basic text
    print "<p>" . _txt('em.invite.body', array($invitee['Co']['name'])) . "</p>";
  }
  
  if(!isset($vv_confirmation_expired) || !$vv_confirmation_expired) {
    include "buttons.inc";
  }
?>
</div>

<?php
  $e = false;

  if(isset($co_petitions)) {
    include ("petition-attributes.inc");
  }
?>
<?php endif; // empty($invite) ?>
<?php endif; // vv_confirmation_expired ?>