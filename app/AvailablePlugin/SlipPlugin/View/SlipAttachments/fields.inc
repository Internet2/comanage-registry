<?php
/**
 * COmanage Registry SLIP Attachments Fields
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
 * @since         COmanage Registry v4.7.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

  // We shouldn't get here if we don't have at least read permission, but check just in case
  
  if(!$permissions['view']) {
    return(false);
  }
  
  // Add breadcrumbs
  print $this->element("coCrumb");

  $args = array(
    'plugin' => null,
    'controller' => 'co_people',
    'action' => 'index',
    'co' => $cur_co['Co']['id']
  );
  $this->Html->addCrumb(_txt('me.population'), $args);

  $args = array(
    'plugin' => null,
    'controller' => 'co_people',
    'action' => 'canvas',
    $slip_attachments[0]['SlipProofing']['ClaimantCoPerson']['id']
  );
  $this->Html->addCrumb(generateCn($slip_attachments[0]['SlipProofing']['ClaimantCoPerson']['PrimaryName']), $args);

  $args = array(
    'plugin' => 'slip_plugin',
    'controller' => 'slip_proofings',
    'action' => 'index',
    'spid' => $slip_attachments[0]['SlipProofing']['slip_plugin_id'],
    'coperson' => $slip_attachments[0]['SlipProofing']['ClaimantCoPerson']['id']
  );
  $this->Html->addCrumb(_txt('ct.slip_proofings.pl'), $args);

  $args = array(
    'plugin' => 'slip_plugin',
    'controller' => 'slip_proofings',
    'action' => 'view',
    $slip_attachments[0]['SlipProofing']['id']
  );
  $this->Html->addCrumb((string)$slip_attachments[0]['SlipProofing']['id'], $args);

  $args = array(
    'plugin' => 'slip_plugin',
    'controller' => 'slip_attachments',
    'action' => 'index',
    'sprid' => $slip_attachments[0]['SlipProofing']['id']
  );
  $this->Html->addCrumb(_txt('ct.slip_attachments.pl'), $args);

  $this->Html->addCrumb($title_for_layout);
?>
<?php if($this->action == 'view'): ?>
<ul id="<?php print $this->action; ?>_attachment" class="fields form-list">
  <li>
    <div class="field-name">
      <?php print _txt('fd.description'); ?>
    </div>
    <div class="field-info">
      <?php print (filter_var($slip_attachments[0]['SlipAttachment']['description'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <?php print _txt('ct.slip_attachments.1'); ?>
    </div>
    <div class="field-info">
      <?php
        print $this->Html->link(
                _txt('op.view'),
                array(
                  'plugin'     => 'slip_plugin',
                  'controller' => 'slip_attachments',
                  'action'     => 'deliver',
                  $slip_attachments[0]['SlipAttachment']['id']
              ));
      ?>
    </div>
  </li>
</ul>
<?php endif;