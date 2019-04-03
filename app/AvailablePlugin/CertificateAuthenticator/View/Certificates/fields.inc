<!--
/**
 * COmanage Registry Certificates Authenticator Plugin Password Fields
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
 * @since         COmanage Registry v3.1.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */
-->
<?php
  // Determine if fields are editable
  $e = false;
  
  if(($this->action == "add" && $permissions['add'])
     || ($this->action == "edit" && $permissions['edit']))
    $e = true;

  print $this->Form->hidden('certificate_authenticator_id',
                            array('default' => $vv_authenticator['CertificateAuthenticator']['id'])) . "\n";
  print $this->Form->hidden('co_person_id', array('default' => $vv_co_person['CoPerson']['id'])) . "\n";
  
  // Add breadcrumbs
  print $this->element("coCrumb", array('authenticator' => 'Certificate'));
?>

<ul id="<?php print $this->action; ?>_certificate" class="fields form-list form-list-admin">
  <li>
    <div class="field-name">
      <?php print _txt('fd.desc'); ?><span class="required">*</span>
    </div>
    <div class="field-info">
      <?php print ($e ? $this->Form->input('description', array('class' => 'focusFirst')) : filter_var($certificates[0]['Certificate']['description'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <li>
    <div class="field-name vtop">
      <?php print _txt('pl.certificateauthenticator.dn.subject'); ?><span class="required">*</span>
    </div>
    <div class="field-info">
      <?php print ($e
                   ? $this->Form->input('subject_dn')
                   : filter_var($certificates[0]['Certificate']['subject_dn'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <li>
    <div class="field-name vtop">
      <?php print _txt('pl.certificateauthenticator.dn.issuer'); ?>
    </div>
    <div class="field-info">
      <?php print ($e
                   ? $this->Form->input('issuer_dn')
                   : filter_var($certificates[0]['Certificate']['issuer_dn'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <li class="modelbox-data">
    <div class="field-name">
      <?php
        if($e) {
          print $this->Form->label('valid_from', _txt('fd.valid_from.tz', array($vv_tz)));
        } else {
          print _txt('fd.valid_from');
        }
      ?><br />
      <span class="field-descr"><?php print _txt('fd.valid_from.desc'); ?></span>
    </div>
    <div class="field-info">
      <?php
        if($e) {
          $args = array(
            'class' => 'datepicker-f'
          );

          if(!empty($certificates[0]['Certificate']['valid_from'])) {
            if(!empty($vv_tz)) {
              // We need to adjust the UTC value to the user's local time
              $args['value'] = $this->Time->format($certificates[0]['Certificate']['valid_from'], "%F %T", false, $vv_tz);
            } else {
              $args['value'] = $certificates[0]['Certificate']['valid_from'];
            }
          }

          print $this->Form->text('valid_from', $args);
        } else {
          if(!empty($certificates[0]['Certificate']['valid_from'])) {
            print $this->Time->format($certificates[0]['Certificate']['valid_from'], "%c $vv_tz", false, $vv_tz);
          }
        }
      ?>
    </div>
  </li>
  <li class="modelbox-data">
    <div class="field-name">
      <?php
        if($e) {
          print $this->Form->label('valid_through', _txt('fd.valid_through.tz', array($vv_tz)));
        } else {
          print _txt('fd.valid_through');
        }
      ?><br />
      <span class="field-descr"><?php print _txt('fd.valid_through.desc'); ?></span>
    </div>
    <div class="field-info">
      <?php
        if($e) {
          $args = array(
            'class' => 'datepicker-u'
          );

          if(!empty($certificates[0]['Certificate']['valid_through'])) {
            if(!empty($vv_tz)) {
              // We need to adjust the UTC value to the user's local time
              $args['value'] = $this->Time->format($certificates[0]['Certificate']['valid_through'], "%F %T", false, $vv_tz);
            } else {
              $args['value'] = $certificates[0]['Certificate']['valid_through'];
            }
          }

          print $this->Form->text('valid_through', $args);
        } else {
          if(!empty($certificates[0]['Certificate']['valid_through'])) {
            print $this->Time->format($certificates[0]['Certificate']['valid_through'], "%c $vv_tz", false, $vv_tz);
          }
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
