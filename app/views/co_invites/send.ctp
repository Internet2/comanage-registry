<!--
  /*
   * COmanage Gears CO Invite View
   *
   * Version: $Revision$
   * Date: $Date$
   *
   * Copyright (C) 2010-2011 University Corporation for Advanced Internet Development, Inc.
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
   */
-->
<h1 class="ui-state-default">Invitation for <?php echo $cur_co['Co']['name']; ?> Sent to <?php echo generateCn($invitee['Name']); ?></h1>

<p>
Email would be sent to <b><?php echo $invite['CoInvite']['mail']; ?></b> with the URL
<br />
<br />
<?php 
  $u = $this->Html->url(array('controller' => 'co_invites', 'action' => 'reply', $invite['CoInvite']['invitation']), true);
  
  echo $this->Html->link($u, $u);
?>
</p>