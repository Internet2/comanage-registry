<!--
  /*
   * COmanage Gears CO Invite View
   *
   * Version: $Revision: 81 $
   * Date: $Date: 2011-07-17 19:53:10 -0400 (Sun, 17 Jul 2011) $
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
   * permissions and limitations under the License.   *
   */
-->
<h1 class="ui-state-default"><?php echo _txt('op.inv-t', array(Sanitize::html(generateCn($co_people[0]['Name'])), Sanitize::html($cur_co['Co']['name']))); ?></h1>

<?php
  $submit_label = _txt('op.inv.send');
  echo $this->Form->create('CoPerson',
                           array('action' => 'add',
                                 'inputDefaults' => array('label' => false, 'div' => false)));
  include("fields.inc");
  echo $this->Form->end();
?>