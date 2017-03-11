<?php
/**
 * COmanage Registry CO Selection View
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

  // Add page title
  $params = array();
  $params['title'] =  _txt('op.select-a', array(_txt('ct.cos.1')));
  print $this->element("pageTitle", $params);

?>

<p>
  <?php print _txt('co.select'); ?>
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
  
  print $this->Form->create('Co', array('url' => array('action' => 'select')));
  print $this->Form->select('co', $a, array('empty' => false));
  print $this->Form->submit(_txt('op.select'));
  print $this->Form->end();
?>
