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
      $("#CoExpirationPolicyActNotificationSubject").closest("tr").hide('fade');
      $("#CoExpirationPolicyActNotificationBody").closest("tr").hide('fade');
    } else {
      $("#CoExpirationPolicyActNotificationSubject").closest("tr").show('fade');
      $("#CoExpirationPolicyActNotificationBody").closest("tr").show('fade');
    }
  }
  
  function js_local_onload() {
    fields_update_gadgets();
  }  
  
  $(function() {
    // Turn on Tabs
    var $tabs = $( "#tabs" ).tabs();
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
<table id="<?php print $this->action; ?>_co_expiration_policies" class="ui-widget">
  <tbody>
    <tr class="line<?php print ($l % 2); $l++; ?>">
      <td>
        <strong class="fieldTitle">
          <?php print ($e ? $this->Form->label('description', _txt('fd.desc')) : _txt('fd.desc')); ?>
        </strong><span class="required">*</span>
      </td>
      <td>
        <?php print ($e ? $this->Form->input('description', array('size' => '60', 'class' => 'focusFirst'))
                        : filter_var($co_expiration_policies[0]['CoExpirationPolicy']['description'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
      </td>
    </tr>
    <tr class="line<?php print ($l % 2); $l++; ?>">
      <td>
        <strong class="fieldTitle">
          <?php print ($e ? $this->Form->label('status', _txt('fd.status')) : _txt('fd.status')); ?>
        </strong><span class="required">*</span>
      </td>
      <td>
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
      </td>
    </tr>
  </tbody>
</table>

<div id="tabs">
  <ul>
    <li>
      <a href="#tabs-conditions">
        <?php print _txt('fd.conditions'); ?>
      </a>
    </li>
    <li>
      <a href="#tabs-actions">
        <?php print _txt('fd.actions'); ?>
      </a>
    </li>
  </ul>
  <div id="tabs-conditions">
    <table id="<?php print $this->action; ?>_co_expiration_policies2" class="ui-widget">
      <tbody>
        <!-- Conditions -->
        <tr>
          <td colspan="2"><em><?php print _txt('fd.xp.conditions'); ?></em></td>
        </tr>
        <tr class="line<?php print ($l % 2); $l++; ?>">
          <td>
            <strong class="fieldTitle">
              <?php print ($e ? $this->Form->label('cond_cou_id', _txt('fd.cou')) : _txt('fd.cou')); ?>
            </strong><br />
            <span class="descr"><?php print _txt('fd.xp.cou.cond.desc'); ?></span>
          </td>
          <td>
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
          </td>
        </tr>
        <tr class="line<?php print ($l % 2); $l++; ?>">
          <td>
            <strong class="fieldTitle">
              <?php print ($e ? $this->Form->label('cond_affiliation', _txt('fd.affiliation')) : _txt('fd.affiliation')); ?>
            </strong><br />
            <span class="descr"><?php print _txt('fd.xp.affil.cond.desc'); ?></span>
          </td>
          <td>
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
          </td>
        </tr>
        <tr class="line<?php print ($l % 2); $l++; ?>">
          <td>
            <strong class="fieldTitle">
              <?php print ($e ? $this->Form->label('cond_before_expiry', _txt('fd.xp.before_expiry.cond')) : _txt('fd.xp.before_expiry.cond')); ?>
            </strong><br />
            <span class="descr"><?php print _txt('fd.xp.before_expiry.cond.desc'); ?></span>
          </td>
          <td>
            <?php
              // Mutually exclusive with before_expiry, so blank out on change
              // (don't use onkeyup because that causes problems with tabbing through fields)
              $args = array();
              $args['onchange'] = "document.getElementById('CoExpirationPolicyCondAfterExpiry').value = ''";
              
              print ($e ? $this->Form->input('cond_before_expiry', $args)
                        : filter_var($co_expiration_policies[0]['CoExpirationPolicy']['cond_before_expiry'],FILTER_SANITIZE_SPECIAL_CHARS));
            ?>
          </td>
        </tr>
        <tr class="line<?php print ($l % 2); $l++; ?>">
          <td>
            <strong class="fieldTitle">
              <?php print ($e ? $this->Form->label('cond_after_expiry', _txt('fd.xp.after_expiry.cond')) : _txt('fd.xp.after_expiry.cond')); ?>
            </strong><br />
            <span class="descr"><?php print _txt('fd.xp.after_expiry.cond.desc'); ?></span>
          </td>
          <td>
            <?php
              // Mutually exclusive with before_expiry, so blank out on change
              $args = array();
              $args['onchange'] = "document.getElementById('CoExpirationPolicyCondBeforeExpiry').value = ''";
              
              print ($e ? $this->Form->input('cond_after_expiry', $args)
                        : filter_var($co_expiration_policies[0]['CoExpirationPolicy']['cond_after_expiry'],FILTER_SANITIZE_SPECIAL_CHARS));
            ?>
          </td>
        </tr>
        <tr class="line<?php print ($l % 2); $l++; ?>">
          <td>
            <strong class="fieldTitle">
              <?php print ($e ? $this->Form->label('cond_count', _txt('fd.xp.count.cond')) : _txt('fd.xp.count.cond')); ?>
            </strong><br />
            <span class="descr"><?php print _txt('fd.xp.count.cond.desc'); ?></span>
          </td>
          <td>
            <?php
              print ($e ? $this->Form->input('cond_count', $args)
                        : filter_var($co_expiration_policies[0]['CoExpirationPolicy']['cond_count'],FILTER_SANITIZE_SPECIAL_CHARS));
            ?>
          </td>
        </tr>
        <tr class="line<?php print ($l % 2); $l++; ?>">
          <td>
            <strong class="fieldTitle">
              <?php print ($e ? $this->Form->label('cond_status', _txt('fd.status')) : _txt('fd.status')); ?>
            </strong><br />
            <span class="descr"><?php print _txt('fd.xp.status.cond.desc'); ?></span>
          </td>
          <td>
            <?php
              global $cm_lang, $cm_texts;
              $attrs = array();
              $attrs['value'] = (isset($co_expiration_policies[0]['CoExpirationPolicy']['cond_status'])
                                 ? $co_expiration_policies[0]['CoExpirationPolicy']['cond_status']
                                 : StatusEnum::Active);
              $attrs['empty'] = true;
              
              if($e) {
                print $this->Form->select('cond_status',
                                          $cm_texts[ $cm_lang ]['en.status'],
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
          </td>
        </tr>
        <tr class="line<?php print ($l % 2); $l++; ?>">
          <td>
            <strong class="fieldTitle">
              <?php print _txt('fd.xp.sponsor.cond'); ?>
            </strong><br />
            <span class="descr"><?php print _txt('fd.xp.sponsor.cond.desc'); ?></span>
          </td>
          <td>
            <?php print ($e ? $this->Form->input('cond_sponsor_invalid') . ' ' . $this->Form->label('cond_sponsor_invalid', _txt('fd.xp.sponsor.cond'))
                            : ($co_expiration_policies[0]['CoExpirationPolicy']['cond_sponsor_invalid']
                               ? _txt('fd.yes') : _txt('fd.no'))); ?>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
  <div id="tabs-actions">
    <table id="<?php print $this->action; ?>_co_expiration_policies3" class="ui-widget">
      <tbody>
        <!-- Actions -->
        <tr>
          <td colspan="2"><em><?php print _txt('fd.xp.actions'); ?></em></td>
        </tr>
        <tr class="line<?php print ($l % 2); $l++; ?>">
          <td>
            <strong class="fieldTitle">
              <?php print ($e ? $this->Form->label('act_cou_id', _txt('fd.cou')) : _txt('fd.cou')); ?>
            </strong><br />
            <span class="descr"><?php print _txt('fd.xp.cou.act.desc'); ?></span>
          </td>
          <td>
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
          </td>
        </tr>
        <tr class="line<?php print ($l % 2); $l++; ?>">
          <td>
            <strong class="fieldTitle">
              <?php print ($e ? $this->Form->label('act_affiliation', _txt('fd.affiliation')) : _txt('fd.affiliation')); ?>
            </strong><br />
            <span class="descr"><?php print _txt('fd.xp.affil.act.desc'); ?></span>
          </td>
          <td>
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
          </td>
        </tr>
        <tr class="line<?php print ($l % 2); $l++; ?>">
          <td>
            <strong class="fieldTitle">
              <?php print ($e ? $this->Form->label('act_status', _txt('fd.status')) : _txt('fd.status')); ?>
            </strong><br />
            <span class="descr"><?php print _txt('fd.xp.status.act.desc'); ?></span>
          </td>
          <td>
            <?php
              global $cm_lang, $cm_texts;
              $attrs = array();
              $attrs['value'] = (isset($co_expiration_policies[0]['CoExpirationPolicy']['act_status'])
                                 ? $co_expiration_policies[0]['CoExpirationPolicy']['act_status']
                                 : StatusEnum::Expired);
              $attrs['empty'] = true;
              
              if($e) {
                print $this->Form->select('act_status',
                                          $cm_texts[ $cm_lang ]['en.status'],
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
          </td>
        </tr>
        <tr class="line<?php print ($l % 2); $l++; ?>">
          <td>
            <strong class="fieldTitle">
              <?php print _txt('fd.xp.clear_expiry.act'); ?>
            </strong><br />
            <span class="descr"><?php print _txt('fd.xp.clear_expiry.act.desc'); ?></span>
          </td>
          <td>
            <?php print ($e ? $this->Form->input('act_clear_expiry') . ' ' . $this->Form->label('act_clear_expiry', _txt('fd.xp.clear_expiry.act'))
                            : ($co_expiration_policies[0]['CoExpirationPolicy']['act_clear_expiry'] ? _txt('fd.yes') : _txt('fd.no'))); ?>
          </td>
        </tr>
        <tr class="line<?php print ($l % 2); $l++; ?>">
          <td>
            <strong class="fieldTitle">
              <?php print _txt('fd.xp.notify_coadmin.act'); ?>
            </strong><br />
            <span class="descr"><?php print _txt('fd.xp.notify_coadmin.act.desc'); ?></span>
          </td>
          <td>
            <?php print ($e ? $this->Form->input('act_notify_co_admin') . ' ' . $this->Form->label('act_notify_co_admin', _txt('fd.xp.notify_coadmin.act'))
                            : ($co_expiration_policies[0]['CoExpirationPolicy']['act_notify_co_admin']
                               ? _txt('fd.yes') : _txt('fd.no'))); ?>
          </td>
        </tr>
        <tr class="line<?php print ($l % 2); $l++; ?>">
          <td>
            <strong class="fieldTitle">
              <?php print _txt('fd.xp.notify_couadmin.act'); ?>
            </strong><br />
            <span class="descr"><?php print _txt('fd.xp.notify_couadmin.act.desc'); ?></span>
          </td>
          <td>
            <?php print ($e ? $this->Form->input('act_notify_cou_admin') . ' ' . $this->Form->label('act_notify_cou_admin', _txt('fd.xp.notify_couadmin.act'))
                            : ($co_expiration_policies[0]['CoExpirationPolicy']['act_notify_cou_admin']
                               ? _txt('fd.yes') : _txt('fd.no'))); ?>
          </td>
        </tr>
        <tr class="line<?php print ($l % 2); $l++; ?>">
          <td>
            <strong class="fieldTitle">
              <?php print ($e ? $this->Form->label('act_notify_co_group_id', _txt('fd.xp.notify_cogroup.act')) : _txt('fd.xp.notify_cogroup.act')); ?>
            </strong><br />
            <span class="descr"><?php print _txt('fd.xp.notify_cogroup.act.desc'); ?></span>
          </td>
          <td>
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
          </td>
        </tr>
        <tr class="line<?php print ($l % 2); $l++; ?>">
          <td>
            <strong class="fieldTitle">
              <?php print _txt('fd.xp.notify_coperson.act'); ?>
            </strong><br />
            <span class="descr"><?php print _txt('fd.xp.notify_coperson.act.desc'); ?></span>
          </td>
          <td>
            <?php print ($e ? $this->Form->input('act_notify_co_person') . ' ' . $this->Form->label('act_notify_co_person', _txt('fd.xp.notify_coperson.act'))
                            : ($co_expiration_policies[0]['CoExpirationPolicy']['act_notify_co_person']
                               ? _txt('fd.yes') : _txt('fd.no'))); ?>
          </td>
        </tr>
        <tr class="line<?php print ($l % 2); $l++; ?>">
          <td>
            <strong class="fieldTitle">
              <?php print _txt('fd.xp.notify_sponsor.act'); ?>
            </strong><br />
            <span class="descr"><?php print _txt('fd.xp.notify_sponsor.act.desc'); ?></span>
          </td>
          <td>
            <?php print ($e ? $this->Form->input('act_notify_sponsor') . ' ' . $this->Form->label('act_notify_sponsor', _txt('fd.xp.notify_sponsor.act'))
                            : ($co_expiration_policies[0]['CoExpirationPolicy']['act_notify_sponsor']
                               ? _txt('fd.yes') : _txt('fd.no'))); ?>
          </td>
        </tr>
        <tr class="line<?php print ($l % 2); $l++; ?>">
          <td>
            <strong class="fieldTitle"><?php print _txt('fd.xp.nmt'); ?></strong><br />
            <span class="descr"><?php print _txt('fd.xp.nmt.desc'); ?></span>
          </td>
          <td>
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
          </td>
        </tr>
        <tr class="line<?php print ($l % 2); $l++; ?>">
          <td>
            <strong class="fieldTitle">
              <?php print ($e ? $this->Form->label('act_notification_subject', _txt('fd.xp.nsubject')) : _txt('fd.xp.nsubject')); ?>
            </strong><br />
            <span class="descr"><?php print _txt('fd.xp.nsubject.desc'); ?></span>
          </td>
          <td>
            <?php print ($e
                         ? $this->Form->input('act_notification_subject',
                                              array('default' => _txt('em.expiration.subject')))
                         : filter_var($co_expiration_policies[0]['CoExpirationPolicy']['act_notification_subject'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
          </td>
        </tr>
        <tr class="line<?php print ($l % 2); $l++; ?>">
          <td>
            <strong class="fieldTitle">
              <?php print ($e ? $this->Form->label('act_notification_body', _txt('fd.xp.nbody')) : _txt('fd.xp.nbody')); ?>
            </strong><br />
            <span class="descr"><?php print _txt('fd.xp.nbody.desc'); ?></span>
          </td>
          <td>
            <?php print ($e
                         ? $this->Form->input('act_notification_body',
                                              array('default' => _txt('em.expiration.body')))
                         : filter_var($co_expiration_policies[0]['CoExpirationPolicy']['act_notification_body'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</div>
 
<?php
if($e) {
  print $this->Form->submit($submit_label, array('onClick' => 'on_submit()'));
  print $this->Form->button(_txt('op.reset'), array('type'=>'reset'));
}
