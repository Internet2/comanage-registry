<?php
/**
 * COmanage Registry CO Expiration Policies Fields
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
 * @since         COmanage Registry v0.9.2
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */
?>
<script type="text/javascript">
  <!-- JS specific to these fields -->
  
  function fields_update_gadgets() {
    // Hide or show gadgets according to current state
    
    // If a notification template is selected, hide the subject and body fields
    var ntemplate = document.getElementById('CoExpirationPolicyActNotificationTemplateId').value;
    
    if(ntemplate) {
      $("#CoExpirationPolicyActNotificationSubject").closest("li").hide('fade');
      $("#CoExpirationPolicyActNotificationBody").closest("li").hide('fade');
    } else {
      $("#CoExpirationPolicyActNotificationSubject").closest("li").show('fade');
      $("#CoExpirationPolicyActNotificationBody").closest("li").show('fade');
    }
  }
  
  function js_local_onload() {
    fields_update_gadgets();
  }  
  
  $(function() {
    // Explorer menu toggles
    $(".fieldGroupName").click(function(event) {
      event.preventDefault();
      $(this).next(".fields").slideToggle("fast");
      // toggle the +/- icon:
      if ($(this).find(".material-icons").text() == "indeterminate_check_box") {
        $(this).find(".material-icons").text("add_box");
      } else {
        $(this).find(".material-icons").text("indeterminate_check_box");
      }
    });
  });
</script>
<?php
  // Determine if fields are editable
  $e = false;
  
  if(($this->action == "add" && $permissions['add']) || ($this->action == "edit" && $permissions['edit']))
    $e = true;
    
  // We shouldn't get here if we don't have at least read permission, but check just in case
  
  if(!$e && !$permissions['view'])
    return false;

  // Add breadcrumbs
  print $this->element("coCrumb");
  $args = array();
  $args['plugin'] = null;
  $args['controller'] = 'co_expiration_policies';
  $args['action'] = 'index';
  $args['co'] = $cur_co['Co']['id'];
  $this->Html->addCrumb(_txt('ct.co_expiration_policies.pl'), $args);
  $crumbTxt = _txt('op.' . $this->action . '-a', array(_txt('ct.co_expiration_policies.1')));
  $this->Html->addCrumb($crumbTxt);
  
  print $this->Form->hidden('co_id', array('default' => $cur_co['Co']['id'])) . "\n";
  
  $l = 1;
?>
<ul id="<?php print $this->action; ?>_co_expiration_policies" class="fields form-list">
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print ($e ? $this->Form->label('description', _txt('fd.desc')) : _txt('fd.desc')); ?>
        <span class="required">*</span>
      </div>
    </div>
    <div class="field-info">
      <?php print ($e ? $this->Form->input('description', array('size' => '60', 'class' => 'focusFirst'))
        : filter_var($co_expiration_policies[0]['CoExpirationPolicy']['description'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
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
        $attrs['value'] = (isset($co_expiration_policies[0]['CoExpirationPolicy']['status'])
                           ? $co_expiration_policies[0]['CoExpirationPolicy']['status']
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
          if(!empty($co_expiration_policies[0]['CoExpirationPolicy']['status'])) {
            print _txt('en.status', null, $co_expiration_policies[0]['CoExpirationPolicy']['status']);
          }
        }
      ?>
    </div>
  </li>
</ul>

<div id="<?php print $this->action; ?>_co_expiration_policies_expl" class="explorerContainer">
  <div id="expiration-policies" class="personExplorer">
    <ul>
      <li id="fields-conditions" class="fieldGroup">

        <a href="#tabs-conditions" class="fieldGroupName">
          <em class="material-icons">add_box</em>
          <?php print _txt('fd.conditions'); ?>
        </a>

        <!-- Conditions -->
        <ul id="tabs-conditions" class="fields form-list form-list-admin" style="display: none;">
          <li class="fields-header">
            <?php print _txt('fd.xp.conditions'); ?>
          </li>
          <li>
            <div class="field-name">
              <div class="field-title">
                <?php print ($e ? $this->Form->label('cond_cou_id', _txt('fd.cou')) : _txt('fd.cou')); ?>
              </div>
              <div class="field-desc">
                <?php print _txt('fd.xp.cou.cond.desc'); ?>
              </div>
            </div>
            <div class="field-info">
              <?php
                $attrs = array();
                $attrs['value'] = (isset($co_expiration_policies[0]['CoExpirationPolicy']['cond_cou_id'])
                                   ? $co_expiration_policies[0]['CoExpirationPolicy']['cond_cou_id']
                                   : 0);
                $attrs['disabled'] = empty($vv_cous);
                $attrs['empty'] = true;

                if($e) {
                  print $this->Form->select('cond_cou_id',
                                            $vv_cous,
                                            $attrs);

                  if($this->Form->isFieldError('cond_cou_id')) {
                    print $this->Form->error('cond_cou_id');
                  }
                } else {
                  if(!empty($co_expiration_policies[0]['CoExpirationPolicy']['cond_cou_id'])) {
                    print filter_var($co_expiration_policies[0]['CondCou']['name'],FILTER_SANITIZE_SPECIAL_CHARS);
                  }
                }
              ?>
            </div>
          </li>
          <li>
            <div class="field-name">
              <div class="field-title">
                <?php print ($e ? $this->Form->label('cond_affiliation', _txt('fd.affiliation')) : _txt('fd.affiliation')); ?>
              </div>
              <div class="field-desc"><?php print _txt('fd.xp.affil.cond.desc'); ?></div>
            </div>
            <div class="field-info">
              <?php
                $attrs = array();
                $attrs['value'] = (isset($co_expiration_policies[0]['CoExpirationPolicy']['cond_affiliation'])
                                   ? $co_expiration_policies[0]['CoExpirationPolicy']['cond_affiliation']
                                   : 0);
                $attrs['empty'] = true;

                if($e) {
                  print $this->Form->select('cond_affiliation',
                                            $vv_copr_affiliation_types,
                                            $attrs);

                  if($this->Form->isFieldError('cond_affiliation')) {
                    print $this->Form->error('cond_affiliation');
                  }
                } else {
                  if(!empty($co_expiration_policies[0]['CoExpirationPolicy']['cond_affiliation'])) {
                    print filter_var($vv_copr_affiliation_types[ $co_expiration_policies[0]['CoExpirationPolicy']['cond_affiliation'] ],FILTER_SANITIZE_SPECIAL_CHARS);
                  }
                }
              ?>
            </div>
          </li>
          <li>
            <div class="field-name">
              <div class="field-title">
                <?php print ($e ? $this->Form->label('cond_before_expiry', _txt('fd.xp.before_expiry.cond')) : _txt('fd.xp.before_expiry.cond')); ?>
              </div>
              <div class="field-desc"><?php print _txt('fd.xp.before_expiry.cond.desc'); ?></div>
            </div>
            <div class="field-info">
              <?php
                // Mutually exclusive with before_expiry, so blank out on key up
                $args = array();
                $args['onkeyup'] = "document.getElementById('CoExpirationPolicyCondAfterExpiry').value = ''";

                print ($e ? $this->Form->input('cond_before_expiry', $args)
                          : filter_var($co_expiration_policies[0]['CoExpirationPolicy']['cond_before_expiry'],FILTER_SANITIZE_SPECIAL_CHARS));
              ?>
            </div>
          </li>
          <li>
            <div class="field-name">
              <div class="field-title">
                <?php print ($e ? $this->Form->label('cond_after_expiry', _txt('fd.xp.after_expiry.cond')) : _txt('fd.xp.after_expiry.cond')); ?>
              </div>
              <div class="field-desc"><?php print _txt('fd.xp.after_expiry.cond.desc'); ?></div>
            </div>
            <div class="field-info">
              <?php
                // Mutually exclusive with before_expiry, so blank out on key up
                $args = array();
                $args['onkeyup'] = "document.getElementById('CoExpirationPolicyCondBeforeExpiry').value = ''";

                print ($e ? $this->Form->input('cond_after_expiry', $args)
                          : filter_var($co_expiration_policies[0]['CoExpirationPolicy']['cond_after_expiry'],FILTER_SANITIZE_SPECIAL_CHARS));
              ?>
            </div>
          </li>
          <li>
            <div class="field-name">
              <div class="field-title">
                <?php print ($e ? $this->Form->label('cond_count', _txt('fd.xp.count.cond')) : _txt('fd.xp.count.cond')); ?>
              </div>
              <div class="field-desc"><?php print _txt('fd.xp.count.cond.desc'); ?></div>
            </div>
            <div class="field-info">
              <?php
                print ($e ? $this->Form->input('cond_count', $args)
                          : filter_var($co_expiration_policies[0]['CoExpirationPolicy']['cond_count'],FILTER_SANITIZE_SPECIAL_CHARS));
              ?>
            </div>
          </li>
          <li>
            <div class="field-name">
              <div class="field-title">
                <?php print ($e ? $this->Form->label('cond_status', _txt('fd.status')) : _txt('fd.status')); ?>
              </div>
              <div class="field-desc"><?php print _txt('fd.xp.status.cond.desc'); ?></div>
            </div>
            <div class="field-info">
              <?php
                global $cm_lang, $cm_texts;
                $attrs = array();
                $attrs['value'] = (isset($co_expiration_policies[0]['CoExpirationPolicy']['cond_status'])
                                   ? $co_expiration_policies[0]['CoExpirationPolicy']['cond_status']
                                   : StatusEnum::Active);
                $attrs['empty'] = true;

                if($e) {
                  // Conditions can not include Locked status because roles cannot be Locked
                  $statuses = $cm_texts[ $cm_lang ]['en.status'];
                  unset($statuses[StatusEnum::Locked]);
                  
                  print $this->Form->select('cond_status',
                                            $statuses,
                                            $attrs);

                  if($this->Form->isFieldError('cond_status')) {
                    print $this->Form->error('cond_status');
                  }
                } else {
                  if(!empty($co_expiration_policies[0]['CoExpirationPolicy']['cond_status'])) {
                    print _txt('en.status', null, $co_expiration_policies[0]['CoExpirationPolicy']['cond_status']);
                  }
                }
              ?>
            </div>
          </li>
          <li>
            <div class="field-name">
              <div class="field-title">
                <?php print _txt('fd.xp.sponsor.cond'); ?>
              </div>
              <div class="field-desc"><?php print _txt('fd.xp.sponsor.cond.desc'); ?></div>
            </div>
            <div class="field-info checkbox">
              <?php print ($e ? $this->Form->input('cond_sponsor_invalid') . ' ' . $this->Form->label('cond_sponsor_invalid', _txt('fd.xp.sponsor.cond'))
                              : ($co_expiration_policies[0]['CoExpirationPolicy']['cond_sponsor_invalid']
                                 ? _txt('fd.yes') : _txt('fd.no'))); ?>
            </div>
          </li>
        </ul>
      </li>

      <li id="fields-actions" class="fieldGroup">

        <a href="#tabs-actions" class="fieldGroupName">
          <em class="material-icons">add_box</em>
          <?php print _txt('fd.actions'); ?>
        </a>

        <!-- Actions -->
        <ul id="tabs-conditions" class="fields form-list form-list-admin" style="display: none">
          <li class="fields-header">
            <?php print _txt('fd.xp.actions'); ?>
          </li>
          <li>
            <div class="field-name">
              <div class="field-title">
                <?php print ($e ? $this->Form->label('act_cou_id', _txt('fd.cou')) : _txt('fd.cou')); ?>
              </div>
              <div class="field-desc"><?php print _txt('fd.xp.cou.act.desc'); ?></div>
            </div>
            <div class="field-info">
              <?php
                $attrs = array();
                $attrs['value'] = (isset($co_expiration_policies[0]['CoExpirationPolicy']['act_cou_id'])
                                   ? $co_expiration_policies[0]['CoExpirationPolicy']['act_cou_id']
                                   : 0);
                $attrs['disabled'] = empty($vv_cous);
                $attrs['empty'] = true;

                if($e) {
                  print $this->Form->select('act_cou_id',
                                            $vv_cous,
                                            $attrs);

                  if($this->Form->isFieldError('act_cou_id')) {
                    print $this->Form->error('act_cou_id');
                  }
                } else {
                  if(!empty($co_expiration_policies[0]['CoExpirationPolicy']['act_cou_id'])) {
                    print filter_var($co_expiration_policies[0]['ActCou']['name'],FILTER_SANITIZE_SPECIAL_CHARS);
                  }
                }
              ?>
            </div>
          </li>
          <li>
            <div class="field-name">
              <div class="field-title">
                <?php print ($e ? $this->Form->label('act_affiliation', _txt('fd.affiliation')) : _txt('fd.affiliation')); ?>
              </div>
              <div class="field-desc"><?php print _txt('fd.xp.affil.act.desc'); ?></div>
            </div>
            <div class="field-info">
              <?php
                $attrs = array();
                $attrs['value'] = (isset($co_expiration_policies[0]['CoExpirationPolicy']['act_affiliation'])
                                   ? $co_expiration_policies[0]['CoExpirationPolicy']['act_affiliation']
                                   : 0);
                $attrs['empty'] = true;

                if($e) {
                  print $this->Form->select('act_affiliation',
                                            $vv_copr_affiliation_types,
                                            $attrs);

                  if($this->Form->isFieldError('act_affiliation')) {
                    print $this->Form->error('act_affiliation');
                  }
                } else {
                  if(!empty($co_expiration_policies[0]['CoExpirationPolicy']['act_affiliation'])) {
                    print filter_var($vv_copr_affiliation_types[ $co_expiration_policies[0]['CoExpirationPolicy']['act_affiliation'] ],FILTER_SANITIZE_SPECIAL_CHARS);
                  }
                }
              ?>
            </div>
          </li>
          <li>
            <div class="field-name">
              <div class="field-title">
                <?php print ($e ? $this->Form->label('act_status', _txt('fd.status')) : _txt('fd.status')); ?>
              </div>
              <div class="field-desc"><?php print _txt('fd.xp.status.act.desc'); ?></div>
            </div>
            <div class="field-info">
              <?php
                global $cm_lang, $cm_texts;
                $attrs = array();
                $attrs['value'] = (isset($co_expiration_policies[0]['CoExpirationPolicy']['act_status'])
                                   ? $co_expiration_policies[0]['CoExpirationPolicy']['act_status']
                                   : StatusEnum::Expired);
                $attrs['empty'] = true;

                if($e) {
                  // Actions can not Lock roles because roles cannot be Locked
                  $statuses = $cm_texts[ $cm_lang ]['en.status'];
                  unset($statuses[StatusEnum::Locked]);
                  
                  print $this->Form->select('act_status',
                                            $statuses,
                                            $attrs);

                  if($this->Form->isFieldError('act_status')) {
                    print $this->Form->error('act_status');
                  }
                } else {
                  if(!empty($co_expiration_policies[0]['CoExpirationPolicy']['act_status'])) {
                    print _txt('en.status', null, $co_expiration_policies[0]['CoExpirationPolicy']['act_status']);
                  }
                }
              ?>
            </div>
          </li>
          <li>
            <div class="field-name">
              <div class="field-title">
                <?php print _txt('fd.xp.clear_expiry.act'); ?>
              </div>
              <div class="field-desc"><?php print _txt('fd.xp.clear_expiry.act.desc'); ?></div>
            </div>
            <div class="field-info checkbox">
              <?php print ($e ? $this->Form->input('act_clear_expiry') . ' ' . $this->Form->label('act_clear_expiry', _txt('fd.xp.clear_expiry.act'))
                              : ($co_expiration_policies[0]['CoExpirationPolicy']['act_clear_expiry'] ? _txt('fd.yes') : _txt('fd.no'))); ?>
            </div>
          </li>
          <li>
            <div class="field-name">
              <div class="field-title">
                <?php print _txt('fd.xp.notify_coadmin.act'); ?>
              </div>
              <div class="field-desc"><?php print _txt('fd.xp.notify_coadmin.act.desc'); ?></div>
            </div>
            <div class="field-info checkbox">
              <?php print ($e ? $this->Form->input('act_notify_co_admin') . ' ' . $this->Form->label('act_notify_co_admin', _txt('fd.xp.notify_coadmin.act'))
                              : ($co_expiration_policies[0]['CoExpirationPolicy']['act_notify_co_admin']
                                 ? _txt('fd.yes') : _txt('fd.no'))); ?>
            </div>
          </li>
          <li>
            <div class="field-name">
              <div class="field-title">
                <?php print _txt('fd.xp.notify_couadmin.act'); ?>
              </div>
              <div class="field-desc"><?php print _txt('fd.xp.notify_couadmin.act.desc'); ?></div>
            </div>
            <div class="field-info checkbox">
              <?php print ($e ? $this->Form->input('act_notify_cou_admin') . ' ' . $this->Form->label('act_notify_cou_admin', _txt('fd.xp.notify_couadmin.act'))
                              : ($co_expiration_policies[0]['CoExpirationPolicy']['act_notify_cou_admin']
                                 ? _txt('fd.yes') : _txt('fd.no'))); ?>
            </div>
          </li>
          <li>
            <div class="field-name">
              <div class="field-title">
                <?php print ($e ? $this->Form->label('act_notify_co_group_id', _txt('fd.xp.notify_cogroup.act')) : _txt('fd.xp.notify_cogroup.act')); ?>
              </div>
              <div class="field-desc"><?php print _txt('fd.xp.notify_cogroup.act.desc'); ?></div>
            </div>
            <div class="field-info">
              <?php
                $attrs = array();
                $attrs['value'] = (isset($co_expiration_policies[0]['CoExpirationPolicy']['act_notify_co_group_id'])
                                   ? $co_expiration_policies[0]['CoExpirationPolicy']['act_notify_co_group_id']
                                   : 0);
                $attrs['empty'] = true;

                if($e) {
                  print $this->Form->select('act_notify_co_group_id',
                                            $vv_co_groups,
                                            $attrs);

                  if($this->Form->isFieldError('act_notify_co_group_id')) {
                    print $this->Form->error('act_notify_co_group_id');
                  }
                } else {
                  if(!empty($co_expiration_policies[0]['CoExpirationPolicy']['act_notify_co_group_id'])) {
                    print filter_var($vv_co_groups[ $co_expiration_policies[0]['CoExpirationPolicy']['act_notify_co_group_id'] ],FILTER_SANITIZE_SPECIAL_CHARS);
                  }
                }
              ?>
            </div>
          </li>
          <li>
            <div class="field-name">
              <div class="field-title">
                <?php print _txt('fd.xp.notify_coperson.act'); ?>
              </div>
              <div class="field-desc"><?php print _txt('fd.xp.notify_coperson.act.desc'); ?></div>
            </div>
            <div class="field-info checkbox">
              <?php print ($e ? $this->Form->input('act_notify_co_person') . ' ' . $this->Form->label('act_notify_co_person', _txt('fd.xp.notify_coperson.act'))
                              : ($co_expiration_policies[0]['CoExpirationPolicy']['act_notify_co_person']
                                 ? _txt('fd.yes') : _txt('fd.no'))); ?>
            </div>
          </li>
          <li>
            <div class="field-name">
              <div class="field-title">
                <?php print _txt('fd.xp.notify_sponsor.act'); ?>
              </div>
              <div class="field-desc"><?php print _txt('fd.xp.notify_sponsor.act.desc'); ?></div>
            </div>
            <div class="field-info checkbox">
              <?php print ($e ? $this->Form->input('act_notify_sponsor') . ' ' . $this->Form->label('act_notify_sponsor', _txt('fd.xp.notify_sponsor.act'))
                              : ($co_expiration_policies[0]['CoExpirationPolicy']['act_notify_sponsor']
                                 ? _txt('fd.yes') : _txt('fd.no'))); ?>
            </div>
          </li>
          <li>
            <div class="field-name">
              <div class="field-title"><?php print _txt('fd.xp.nmt'); ?></div>
              <div class="field-desc"><?php print _txt('fd.xp.nmt.desc'); ?></div>
            </div>
            <div class="field-info">
              <?php
                $attrs = array();
                $attrs['value'] = (!empty($co_expiration_policies[0]['CoExpirationPolicy']['act_notification_template_id'])
                                   ? $co_expiration_policies[0]['CoExpirationPolicy']['act_notification_template_id']
                                   : 0);
                $attrs['empty'] = true;
                $attrs['onchange'] = "fields_update_gadgets();";

                if($e) {
                  print $this->Form->select('act_notification_template_id',
                                            $vv_message_templates,
                                            $attrs);

                  if($this->Form->isFieldError('act_notification_template_id')) {
                    print $this->Form->error('act_notification_template_id');
                  }
                } else {
                  if(!empty($co_expiration_policies[0]['CoExpirationPolicy']['act_notification_template_id'])) {
                    print filter_var($vv_message_templates[ $co_expiration_policies[0]['CoExpirationPolicy']['act_notification_template_id'] ],FILTER_SANITIZE_SPECIAL_CHARS);
                  }
                }
              ?>
            </div>
          </li>
          <li>
            <div class="field-name">
              <div class="field-title">
                <?php print ($e ? $this->Form->label('act_notification_subject', _txt('fd.xp.nsubject')) : _txt('fd.xp.nsubject')); ?>
              </div>
              <div class="field-desc"><?php print _txt('fd.xp.nsubject.desc'); ?></div>
            </div>
            <div class="field-info">
              <?php print ($e
                           ? $this->Form->input('act_notification_subject',
                                                array('default' => _txt('em.expiration.subject')))
                           : filter_var($co_expiration_policies[0]['CoExpirationPolicy']['act_notification_subject'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
            </div>
          </li>
          <li class="field-stack">
            <div class="field-name">
              <div class="field-title">
                <?php print ($e ? $this->Form->label('act_notification_body', _txt('fd.xp.nbody')) : _txt('fd.xp.nbody')); ?>
              </div>
              <div class="field-desc"><?php print _txt('fd.xp.nbody.desc'); ?></div>
            </div>
            <div class="field-info">
              <?php print ($e
                           ? $this->Form->input('act_notification_body',
                                                array('default' => _txt('em.expiration.body')))
                           : filter_var($co_expiration_policies[0]['CoExpirationPolicy']['act_notification_body'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
            </div>
          </li>
        </ul>
      </li>
      <?php if($e): ?>
        <li class="fieldGroup">
          <ul class="fields form-list">
            <li class="fields-submit">
              <div class="field-name"></div>
              <div class="field-info">
                <?php print $this->Form->submit($submit_label); ?>
              </div>
            </li>
          </ul>
        </li>
      <?php endif; ?>
    </ul>
  </div>
</div>
