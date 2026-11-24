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

  $attachment = $slip_attachments[0]['SlipAttachment'];
  $proofing = $slip_attachments[0]['SlipProofing'];
  $imageMimeTypes = array(
    'image/jpeg',
    'image/png',
    'image/webp',
    'image/gif',
  );

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
    $proofing['ClaimantCoPerson']['id']
  );
  $this->Html->addCrumb(generateCn($proofing['ClaimantCoPerson']['PrimaryName']), $args);

  $args = array(
    'plugin' => 'slip_plugin',
    'controller' => 'slip_proofings',
    'action' => 'index',
    'spid' => $proofing['slip_plugin_id'],
    'coperson' => $proofing['ClaimantCoPerson']['id']
  );
  $this->Html->addCrumb(_txt('ct.slip_proofings.pl'), $args);

  $args = array(
    'plugin' => 'slip_plugin',
    'controller' => 'slip_proofings',
    'action' => 'view',
    $proofing['id']
  );
  $this->Html->addCrumb((string)$proofing['id'], $args);

  $args = array(
    'plugin' => 'slip_plugin',
    'controller' => 'slip_attachments',
    'action' => 'index',
    'sprid' => $proofing['id']
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
      <?php print (filter_var($attachment['description'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <?php print _txt('ct.slip_attachments.1'); ?>
    </div>
    <div class="field-info">
      <?php
        $attachementLink = $this->Html->url(
          array(
            'plugin'     => 'slip_plugin',
            'controller' => 'slip_attachments',
            'action'     => 'deliver',
            $attachment['id']
          )
        );
      ?>
      <a href="<?php print $attachementLink?>">
        <?php if(in_array($attachment['mime_type'], $imageMimeTypes, true)): ?>
          <img src="data:<?php echo h($attachment['mime_type']); ?>;base64,<?php echo base64_encode($attachment['file_contents']); ?>"
            class="slip-image" alt="<?php print _txt('ct.slip_attachments.1'); ?>" />
        <?php endif; ?>
        <?php print _txt('op.view'); ?>
      </a>
    </div>
  </li>
</ul>
<?php endif;