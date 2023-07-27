<?php
/**
 * COmanage Registry API Users Fields
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
 * @since         COmanage Registry v0.8.4
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

  // Determine if fields are editable
  $e = false;
  
  if(($this->action == "add" && $permissions['add']) || ($this->action == "edit" && $permissions['edit']))
    $e = true;
    
  // We shouldn't get here if we don't have at least read permission, but check just in case
  
  if(!$e && !$permissions['view'])
    return false;

  // Add breadcrumbs
  $this->Html->addCrumb(_txt('ct.api_users.pl'), array('controller' => 'api_users', 'action' => 'index', 'co' => $cur_co['Co']['id']));
  $crumbTxt = _txt('op.' . $this->action . '-a', array(_txt('ct.api_users.1')));
  $this->Html->addCrumb($crumbTxt);

  // Inject the CO ID
  print $this->Form->hidden('co_id', array('default' => $cur_co['Co']['id']));
  // Alter the request data username field. We want to remove the co prefix so that we can present it properly. The correct format will be restructured on the beforeSave event
  if (!empty($this->request->data["ApiUser"]["username"])) {
    $usernameSplit = explode('.', $this->request->data["ApiUser"]["username"], 2);
    if (!empty($usernameSplit[1])) {
      $this->request->data["ApiUser"]["username"] = $usernameSplit[1];
    }
  }

?>
  <script type="text/javascript">
    $(function() {
      validate_date_input('<?php print _txt('er.validation') ?>', '<?php print _txt('er.validation.date') ?>');
    });
  </script>
<?php if($cur_co['Co']['name'] == DEF_COMANAGE_CO_NAME): ?>
<div class="co-info-topbox">
  <em class="material-icons">info</em>
  <div class="co-info-topbox-text">
    <?php print _txt('in.api.cmp'); ?>
  </div>
</div>
<?php endif; // co name == DEF_COMANAGE_CO_NAME ?>
<ul id="<?php print $this->action; ?>_api_users" class="fields form-list">
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print ($e ? $this->Form->label('username', _txt('fd.api.username')) : _txt('fd.api.username')); ?>
        <span class="required">*</span>
      </div>
      <div class="field-desc"><?php print _txt('fd.api.username.desc'); ?></div>
    </div>
    <div class="field-info field-info-flex">
      <?php
        $prefix = 'co_' . $cur_co['Co']['id'] . '.';
        print '<span class="api-username-prefix">' . $prefix . '</span>';
        print ($e ? $this->Form->input('username', array(
                                                    'class'     => 'focusFirst',
                                                    'maxlength' => 50 - strlen($prefix),
                                                  )
                                      )
                  : explode('.' , filter_var($api_users[0]['ApiUser']['username'],FILTER_SANITIZE_SPECIAL_CHARS), 2)[1]);
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print ($e ? $this->Form->label('password', _txt('fd.api.key')) : _txt('fd.api.key')); ?>
        <span class="required">*</span>
      </div>
      <?php if($this->action == 'add'): ?>
      <div class="field-desc"><?php print _txt('fd.api.key.desc'); ?></div>
    <?php endif; // add ?>
    </div>
    <div class="field-info">
      <span class="field-info-prefix">
        <?php print (!empty($api_users[0]['ApiUser']['password']) ? _txt('fd.set') : _txt('fd.set.not')); ?>
      </span>
      <?php
        
        if($permissions['generate'] && !empty($api_users[0]['ApiUser']['id'])) {
          print '<button type="button" class="provisionbutton" title="' . _txt('op.api.key.generate')
            . '" onclick="javascript:js_confirm_generic(\''
            . _txt('op.api.key.confirm') . '\',\''    // dialog body text
            . $this->Html->url(              // dialog confirm URL
                array(
                  'controller'   => 'api_users',
                  'action'       => 'generate',
                  $api_users[0]['ApiUser']['id']
                )
              ) . '\',\''
            . _txt('op.api.key.generate') . '\',\''    // dialog confirm button
            . _txt('op.cancel') . '\',\''    // dialog cancel button
            . _txt('op.api.key.generate') . '\',[\''   // dialog title
            . ''  // dialog body text replacement strings
            . '\']);">'
            . _txt('op.api.key.generate')
            . '</button>';
        }
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('fd.status'); ?>
        <span class="required">*</span>
      </div>
    </div>
    <div class="field-info">
      <?php
        global $cm_lang, $cm_texts;
        $attrs = array();
        $attrs['value'] = (isset($api_users[0]['ApiUser']['status'])
                           ? $api_users[0]['ApiUser']['status']
                           : SuspendableStatusEnum::Active);
        $attrs['empty'] = false;

        if($e) {
          print $this->Form->select('status',
                                    $cm_texts[ $cm_lang ]['en.status.susp'],
                                    $attrs);

          if($this->Form->isFieldError('status')) {
            print $this->Form->error('status');
          }
        } else {
          print _txt('en.status', null, $api_users[0]['ApiUser']['status']);
        }
      ?>
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

          if(!empty($api_users[0]['ApiUser']['valid_from'])) {
            if(!empty($vv_tz)) {
              // We need to adjust the UTC value to the user's local time
              $args['value'] = $this->Time->format($api_users[0]['ApiUser']['valid_from'], "%F %T", false, $vv_tz);
            } else {
              $args['value'] = $api_users[0]['ApiUser']['valid_from'];
            }
          }

          print $this->Form->text('valid_from', $args);
        } else {
          if(!empty($api_users[0]['ApiUser']['valid_from'])) {
            print $this->Time->format($api_users[0]['ApiUser']['valid_from'], "%c $vv_tz", false, $vv_tz);
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

          if(!empty($api_users[0]['ApiUser']['valid_through'])) {
            if(!empty($vv_tz)) {
              // We need to adjust the UTC value to the user's local time
              $args['value'] = $this->Time->format($api_users[0]['ApiUser']['valid_through'], "%F %T", false, $vv_tz);
            } else {
              $args['value'] = $api_users[0]['ApiUser']['valid_through'];
            }
          }

          print $this->Form->text('valid_through', $args);
        } else {
          if(!empty($api_users[0]['ApiUser']['valid_through'])) {
            print $this->Time->format($api_users[0]['ApiUser']['valid_through'], "%c $vv_tz", false, $vv_tz);
          }
        }
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print ($e ? $this->Form->label('remote_ip', _txt('fd.ip')) : _txt('fd.ip')); ?>
      </div>
      <div class="field-desc"><?php print _txt('fd.api.ip.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php print ($e
        ? $this->Form->input('remote_ip')
        : filter_var($api_users[0]['ApiUser']['remote_ip'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <?php if($cur_co['Co']['id'] > 1): ?>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print ($e ? $this->Form->label('privileged', _txt('fd.api.priv')) : _txt('fd.api.priv')); ?>
      </div>
      <div class="field-desc"><?php print _txt('fd.api.priv.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php print $e ? $this->Form->input('privileged')
                     : ($api_users[0]['ApiUser']['privileged'] ? _txt('fd.true') : _txt('fd.false')); ?>
    </div>
  </li>
  <?php else: // co > 1 ?>
    <?php print $this->Form->hidden('privileged', array('default' => true)); ?>
  <?php endif; // co > 1 ?>
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
