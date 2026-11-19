<!--
/**
 * COmanage Registry SLIP Proofing Fields
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
 * @package       registry-plugin
 * @since         COmanage Registry v4.7.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */
-->
<?php
  // Determine if fields are editable
  // (Proofing fields are never editable)
  $e = false;
  
  // We shouldn't get here if we don't have at least read permission, but check just in case
  
  if(!$e && !$permissions['view'])
    return false;
  
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
    $slip_proofings[0]['ClaimantCoPerson']['id']
  );
  $this->Html->addCrumb(generateCn($slip_proofings[0]['ClaimantCoPerson']['PrimaryName']), $args);

  $args = array(
    'plugin' => 'slip_plugin',
    'controller' => 'slip_proofings',
    'action' => 'index',
    'spid' => $slip_proofings[0]['SlipProofing']['slip_plugin_id'],
    'coperson' => $slip_proofings[0]['ClaimantCoPerson']['id']
  );
  $this->Html->addCrumb(_txt('ct.slip_proofings.pl'), $args);

  $crumbTxt = _txt('ct.slip_proofings.1');
  $this->Html->addCrumb($crumbTxt);
?>
<ul id="<?php print $this->action; ?>_slip_proofings" class="fields form-list form-list-admin">
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('pl.slipplugin.co_person_id'); ?>
      </div>
    </div>
    <div class="field-info">
      <?php 
        if(!empty($slip_proofings[0]['ClaimantCoPerson']['PrimaryName'])) {
          print $this->Html->link(
                  generateCn($slip_proofings[0]['ClaimantCoPerson']['PrimaryName']),
                  array(
                    'plugin'     => null,
                    'controller' => 'co_people',
                    'action'     => 'canvas',
                    $slip_proofings[0]['ClaimantCoPerson']['id']
                ));            
        }
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('fd.status'); ?>
      </div>
    </div>
    <div class="field-info">
      <?php 
        if(!empty($slip_proofings[0]['SlipProofing']['status'])) {
              print _txt('pl.slipplugin.en.status', null, $slip_proofings[0]['SlipProofing']['status']);
        }
      ?>
    </div>
  </li>
  <?php if($slip_proofings[0]['SlipProofing']['status'] == SlipProofingStatusEnum::PendingVouching): ?>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('pl.slipplugin.vouch.url'); ?>
      </div>
    </div>
    <div class="field-info">
      <?php 
        print $this->Html->link(
          $this->Html->url(array(
              'plugin'     => 'slip_plugin',
              'controller' => 'slip_proofings',
              'action'     => 'vouch',
              $slip_proofings[0]['SlipProofing']['id']
            ),
            true
          ),
          array(
            'plugin'     => 'slip_plugin',
            'controller' => 'slip_proofings',
            'action'     => 'vouch',
            $slip_proofings[0]['SlipProofing']['id']
          )
        );        
      ?>
    </div>
  </li>
  <?php endif; // status ?>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('pl.slipplugin.initiator_co_person_id'); ?>
      </div>
    </div>
    <div class="field-info">
      <?php 
        if(!empty($slip_proofings[0]['InitiatorCoPerson']['PrimaryName'])) {
          print $this->Html->link(
                  generateCn($slip_proofings[0]['InitiatorCoPerson']['PrimaryName']),
                  array(
                    'plugin'     => null,
                    'controller' => 'co_people',
                    'action'     => 'canvas',
                    $slip_proofings[0]['InitiatorCoPerson']['id']
                ));            
        }
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('pl.slipplugin.registrar_co_person_id'); ?>
      </div>
    </div>
    <div class="field-info">
      <?php 
        if(!empty($slip_proofings[0]['RegistrarCoPerson']['PrimaryName'])) {
          print $this->Html->link(
                  generateCn($slip_proofings[0]['RegistrarCoPerson']['PrimaryName']),
                  array(
                    'plugin'     => null,
                    'controller' => 'co_people',
                    'action'     => 'canvas',
                    $slip_proofings[0]['RegistrarCoPerson']['id']
                ));            
        }
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('pl.slipplugin.voucher_co_person_id'); ?>
      </div>
    </div>
    <div class="field-info">
      <?php 
        if(!empty($slip_proofings[0]['VoucherCoPerson']['PrimaryName'])) {
          print $this->Html->link(
                  generateCn($slip_proofings[0]['VoucherCoPerson']['PrimaryName']),
                  array(
                    'plugin'     => null,
                    'controller' => 'co_people',
                    'action'     => 'canvas',
                    $slip_proofings[0]['VoucherCoPerson']['id']
                ));            
        }
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('pl.slipplugin.created'); ?>
      </div>
    </div>
    <div class="field-info">
      <?php 
        if(!empty($slip_proofings[0]['SlipProofing']['created'])) {
          print $this->Time->format($slip_proofings[0]['SlipProofing']['created'], "%c $vv_tz", false, $vv_tz);
        }
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('pl.slipplugin.assignment_time'); ?>
      </div>
    </div>
    <div class="field-info">
      <?php 
        if(!empty($slip_proofings[0]['SlipProofing']['assignment_time'])) {
          print $this->Time->format($slip_proofings[0]['SlipProofing']['assignment_time'], "%c $vv_tz", false, $vv_tz);
        }
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('pl.slipplugin.vouch_request_time'); ?>
      </div>
    </div>
    <div class="field-info">
      <?php 
        if(!empty($slip_proofings[0]['SlipProofing']['vouch_request_time'])) {
          print $this->Time->format($slip_proofings[0]['SlipProofing']['vouch_request_time'], "%c $vv_tz", false, $vv_tz);
        }
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('pl.slipplugin.vouch_time'); ?>
      </div>
    </div>
    <div class="field-info">
      <?php 
        if(!empty($slip_proofings[0]['SlipProofing']['vouch_time'])) {
          print $this->Time->format($slip_proofings[0]['SlipProofing']['vouch_time'], "%c $vv_tz", false, $vv_tz);
        }
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('pl.slipplugin.resolution_time'); ?>
      </div>
    </div>
    <div class="field-info">
      <?php 
        if(!empty($slip_proofings[0]['SlipProofing']['resolution_time'])) {
          print $this->Time->format($slip_proofings[0]['SlipProofing']['resolution_time'], "%c $vv_tz", false, $vv_tz);
        }
      ?>
    </div>
  </li>
  <?php if($e): ?>
    <li class="fields-submit">
      <div class="field-name">
        <span class="required"><?php print _txt('fd.req'); ?></span>
      </div>
      <div class="field-info">
        <?php print $this->Form->submit($submit_label); ?>
      </div>
    </li>
  <?php endif; ?>
</ul>
<?php
  print $this->element("changelog");
