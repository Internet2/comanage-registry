<!--
  /*
   * COmanage Gears CO Selection View
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
<h1 class="ui-state-default"><?php echo _txt('op.select-a', array(_txt('ct.cos.1'))); ?></h1>

<p>
  <?php echo _txt('co.select'); ?>
</p>

<?php
  // Assemble list of COs
  $a = array();
  
  foreach($cos as $c)
  {
    $n = $c['Co']['name'];
    $a[ $c['Co']['id'] ] = $n;
  }

  // Generate form
  echo $this->Form->create('Co', array('action' => 'select'));
  echo $this->Form->select('co', $a, null, array('empty' => false));
  echo $this->Form->submit(_txt('op.select'));
  echo $this->Form->end();
?>