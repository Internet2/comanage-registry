<!--
/**
 * COmanage Registry CO Invite Reply View
 *
 * Copyright (C) 2010-13 University Corporation for Advanced Internet Development, Inc.
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
 * @copyright     Copyright (C) 2011-13 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.1
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */
-->
<?php if(!empty($invite)): ?>
<?php
  $params = array('title' => $title_for_layout);
  print $this->element("pageTitle", $params);
  
  $verifyEmail = !empty($invite['CoInvite']['email_address_id']);
?>

<h2 class="ui-state-default"><?php print _txt(($verifyEmail ? 'fd.ev.for' : 'fd.inv.for'),
                                              array(generateCn($invitee['PrimaryName']))); ?></h2>

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