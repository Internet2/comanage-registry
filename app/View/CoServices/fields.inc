<?php
/**
 * COmanage Registry CO Service Fields
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
 * @since         COmanage Registry v2.0.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */
?>
<?php
  // Determine if fields are editable
  $e = false;
  
  if(($this->action == "add" && $permissions['add']) || ($this->action == "edit" && $permissions['edit']))
    $e = true;
    
  // We shouldn't get here if we don't have at least read permission, but check just in case
  
  if(!$e && !$permissions['view'])
    return(false);

  // Add breadcrumbs
  print $this->element("coCrumb");
  $args = array();
  $args['plugin'] = null;
  $args['controller'] = 'co_services';
  $args['action'] = 'index';
  $args['co'] = $cur_co['Co']['id'];
  $this->Html->addCrumb(_txt('ct.co_services.pl'), $args);
  $crumbTxt = _txt('op.' . $this->action . '-a', array(_txt('ct.co_services.1')));
  $this->Html->addCrumb($crumbTxt);

  print $this->Form->hidden('co_id', array('default' => $cur_co['Co']['id'])) . "\n";

  $l = 1;
?>
<ul id="<?php print $this->action; ?>_co_services" class="fields form-list">
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print ($e ? $this->Form->label('name', _txt('fd.name')) : _txt('fd.name')); ?>
        <span class="required">*</span>
      </div>  
    </div>
    <div class="field-info">
      <?php print ($e
                   ? $this->Form->input('name', array('class' => 'focusFirst'))
                   : filter_var($co_services[0]['CoService']['name'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print ($e ? $this->Form->label('description', _txt('fd.desc')) : _txt('fd.desc')); ?>
        <span class="required">*</span>
      </div>
    </div>
    <div class="field-info">
      <?php print ($e
                   ? $this->Form->input('description')
        : filter_var($co_services[0]['CoService']['description'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print ($e ? $this->Form->label('status', _txt('fd.status')) : _txt('fd.status')); ?>
        <span class="required">*</span>
      </div>
    </div>
    <div class="field-info">
      <?php
        global $cm_lang, $cm_texts;
        $attrs = array();
        $attrs['value'] = (isset($co_services[0]['CoService']['status'])
                           ? $co_services[0]['CoService']['status']
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
          print _txt('en.status', null, $co_services[0]['CoService']['status']);
        }
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print ($e ? $this->Form->label('visibility', _txt('fd.visibility')) : _txt('fd.visibility')); ?>
        <span class="required">*</span>
      </div>
      <div class="field-desc"><?php print _txt('fd.svc.vis.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php
        global $cm_lang, $cm_texts;
        $attrs = array();
        $attrs['value'] = (isset($co_services[0]['CoService']['visibility'])
                           ? $co_services[0]['CoService']['visibility']
                           : VisibilityEnum::CoMember);
        $attrs['empty'] = false;

        if($e) {
          print $this->Form->select('visibility',
                                    $cm_texts[ $cm_lang ]['en.visibility'],
                                    $attrs);

          if($this->Form->isFieldError('visibility')) {
            print $this->Form->error('visibility');
          }
        } else {
          print _txt('en.visibility', null, $co_services[0]['CoService']['visibility']);
        }
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print ($e ? $this->Form->label('cou_id', _txt('fd.cou')) : _txt('fd.cou')); ?>
      </div>
      <div class="field-desc"><?php print _txt('fd.svc.cou.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php
        $attrs = array();
        $attrs['value'] = (isset($co_services[0]['CoService']['cou_id'])
                           ? $co_services[0]['CoService']['cou_id']
                           : '');
        $attrs['empty'] = true;

        if($e) {
          print $this->Form->select('cou_id',
                                    $vv_cous,
                                    $attrs);

          if($this->Form->isFieldError('cou_id')) {
            print $this->Form->error('cou_id');
          }
        } else {
          print $vv_cous[ $co_services[0]['CoService']['cou_id'] ];
        }
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print ($e ? $this->Form->label('co_group_id', _txt('fd.svc.group')) : _txt('fd.svc.group')); ?>
      </div>
      <div class="field-desc"><?php print _txt('fd.svc.group.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php
        $attrs = array();
        $attrs['value'] = (isset($co_services[0]['CoService']['co_group_id'])
                           ? $co_services[0]['CoService']['co_group_id']
                           : 0);
        $attrs['empty'] = true;
        
        if($e) {
          print $this->Form->select('co_group_id',
                                    $vv_co_groups,
                                    $attrs);
          
          if($this->Form->isFieldError('co_group_id')) {
            print $this->Form->error('co_group_id');
          }
        } else {
          if(!empty( $co_services[0]['CoService']['co_group_id'])) {
            print filter_var($vv_co_groups[  $co_services[0]['CoService']['co_group_id'] ],FILTER_SANITIZE_SPECIAL_CHARS);
          }
        }
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print ($e ? $this->Form->label('service_url', _txt('fd.svc.url')) : _txt('fd.svc.url')); ?>
      </div>
      <div class="field-desc"><?php print _txt('fd.svc.url.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php print ($e
                   ? $this->Form->input('service_url')
                   : filter_var($co_services[0]['CoService']['service_url'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print ($e ? $this->Form->label('service_label', _txt('fd.svc.label')) : _txt('fd.svc.label')); ?>
      </div>
      <div class="field-desc"><?php print _txt('fd.svc.label.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php print ($e
                   ? $this->Form->input('service_label')
                   : filter_var($co_services[0]['CoService']['service_label'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print ($e ? $this->Form->label('contact_email', _txt('fd.svc.mail')) : _txt('fd.svc.mail')); ?>
      </div>
      <div class="field-desc"><?php print _txt('fd.svc.mail.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php print ($e
                   ? $this->Form->input('contact_email')
                   : filter_var($co_services[0]['CoService']['contact_email'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print ($e ? $this->Form->label('entitlement_uri', _txt('fd.svc.euri')) : _txt('fd.svc.euri')); ?>
      </div>
      <div class="field-desc"><?php print _txt('fd.svc.euri.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php print ($e
                   ? $this->Form->input('entitlement_uri')
                   : filter_var($co_services[0]['CoService']['entitlement_uri'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <?php if($e): ?>
    <li class="fields-submit">
      <div class="field-name">
        <span class="required"><?php print _txt('fd.req'); ?></span>
      </div>
      <div class="field-info">
        <?php print $this->Form->submit($submit_label, array('onClick' => 'on_submit()')); ?>
      </div>
    </li>
  <?php endif; ?>
</ul>
<?php
  print $this->element("changelog");
  