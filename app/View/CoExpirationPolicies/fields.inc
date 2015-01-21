<!--
/**
 * COmanage Registry CO Expiration Policies Fields
 *
 * Copyright (C) 2014-15 University Corporation for Advanced Internet Development, Inc.
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
 * @copyright     Copyright (C) 2014-15 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.9.2
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */
-->
<script type="text/javascript">
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
  $args = array();
  $args['plugin'] = null;
  $args['controller'] = 'co_expiration_policies';
  $args['action'] = 'index';
  $args['co'] = $cur_co['Co']['id'];
  $this->Html->addCrumb(_txt('ct.co_expiration_policies.pl'), $args);
  if($this->action == "add") {
    $this->Html->addCrumb(_txt('op.add') . ' ' . _txt('ct.co_expiration_policies.1'));
  }
  if($this->action == "edit") {
    $this->Html->addCrumb(_txt('op.edit') . ' ' . _txt('ct.co_expiration_policies.1'));
  }
  if($this->action == "view") {
    $this->Html->addCrumb(_txt('op.view') . ' ' . _txt('ct.co_expiration_policies.1'));
  }
  
  print $this->Form->hidden('co_id', array('default' => $cur_co['Co']['id'])) . "\n";
  
  $l = 1;
?>
<table id="<?php print $this->action; ?>_co_expiration_policies" class="ui-widget">
  <tbody>
    <tr class="line<?php print ($l % 2); $l++; ?>">
      <td>
        <b><?php print _txt('fd.desc'); ?></b><span class="required">*</span>
      </td>
      <td>
        <?php print ($e ? $this->Form->input('description', array('size' => '60', 'class' => 'focusFirst'))
                        : Sanitize::html($co_expiration_policies[0]['CoExpirationPolicy']['description'])); ?>
      </td>
    </tr>
    <tr class="line<?php print ($l % 2); $l++; ?>">
      <td>
        <b><?php print _txt('fd.status'); ?></b><span class="required">*</span>
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
          <td colspan="2"><i><?php print _txt('fd.xp.conditions'); ?></i></td>
        </tr>
        <tr class="line<?php print ($l % 2); $l++; ?>">
          <td>
            <b><?php print _txt('fd.cou'); ?></b><br />
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
                  print Sanitize::html($co_expiration_policies[0]['CondCou']['name']);
                }
              }
            ?>
          </td>
        </tr>
        <tr class="line<?php print ($l % 2); $l++; ?>">
          <td>
            <b><?php print _txt('fd.affiliation'); ?></b><br />
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
                  print Sanitize::html($vv_copr_affiliation_types[ $co_expiration_policies[0]['CoExpirationPolicy']['cond_affiliation'] ]);
                }
              }
            ?>
          </td>
        </tr>
        <tr class="line<?php print ($l % 2); $l++; ?>">
          <td>
            <b><?php print _txt('fd.xp.before_expiry.cond'); ?></b><br />
            <span class="descr"><?php print _txt('fd.xp.before_expiry.cond.desc'); ?></span>
          </td>
          <td>
            <?php
              // Mutually exclusive with before_expiry, so blank out on key up
              $args = array();
              $args['onkeyup'] = "document.getElementById('CoExpirationPolicyCondAfterExpiry').value = ''";
              
              print ($e ? $this->Form->input('cond_before_expiry', $args)
                        : Sanitize::html($co_expiration_policies[0]['CoExpirationPolicy']['cond_before_expiry']));
            ?>
          </td>
        </tr>
        <tr class="line<?php print ($l % 2); $l++; ?>">
          <td>
            <b><?php print _txt('fd.xp.after_expiry.cond'); ?></b><br />
            <span class="descr"><?php print _txt('fd.xp.after_expiry.cond.desc'); ?></span>
          </td>
          <td>
            <?php
              // Mutually exclusive with before_expiry, so blank out on key up
              $args = array();
              $args['onkeyup'] = "document.getElementById('CoExpirationPolicyCondBeforeExpiry').value = ''";
              
              print ($e ? $this->Form->input('cond_after_expiry', $args)
                        : Sanitize::html($co_expiration_policies[0]['CoExpirationPolicy']['cond_after_expiry']));
            ?>
          </td>
        </tr>
        <tr class="line<?php print ($l % 2); $l++; ?>">
          <td>
            <b><?php print _txt('fd.status'); ?></b><br />
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
            <b><?php print _txt('fd.xp.sponsor.cond'); ?></b><br />
            <span class="descr"><?php print _txt('fd.xp.sponsor.cond.desc'); ?></span>
          </td>
          <td>
            <?php print ($e ? $this->Form->input('cond_sponsor_invalid')
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
          <td colspan="2"><i><?php print _txt('fd.xp.actions'); ?></i></td>
        </tr>
        <tr class="line<?php print ($l % 2); $l++; ?>">
          <td>
            <b><?php print _txt('fd.cou'); ?></b><br />
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
                  print Sanitize::html($co_expiration_policies[0]['ActCou']['name']);
                }
              }
            ?>
          </td>
        </tr>
        <tr class="line<?php print ($l % 2); $l++; ?>">
          <td>
            <b><?php print _txt('fd.affiliation'); ?></b><br />
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
                  print Sanitize::html($vv_copr_affiliation_types[ $co_expiration_policies[0]['CoExpirationPolicy']['act_affiliation'] ]);
                }
              }
            ?>
          </td>
        </tr>
        <tr class="line<?php print ($l % 2); $l++; ?>">
          <td>
            <b><?php print _txt('fd.status'); ?></b><br />
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
            <b><?php print _txt('fd.xp.clear_expiry.act'); ?></b><br />
            <span class="descr"><?php print _txt('fd.xp.clear_expiry.act.desc'); ?></span>
          </td>
          <td>
            <?php print ($e ? $this->Form->input('act_clear_expiry')
                            : ($co_expiration_policies[0]['CoExpirationPolicy']['act_clear_expiry']
                               ? _txt('fd.yes') : _txt('fd.no'))); ?>
          </td>
        </tr>
        <tr class="line<?php print ($l % 2); $l++; ?>">
          <td>
            <b><?php print _txt('fd.xp.notify_coadmin.act'); ?></b><br />
            <span class="descr"><?php print _txt('fd.xp.notify_coadmin.act.desc'); ?></span>
          </td>
          <td>
            <?php print ($e ? $this->Form->input('act_notify_co_admin')
                            : ($co_expiration_policies[0]['CoExpirationPolicy']['act_notify_co_admin']
                               ? _txt('fd.yes') : _txt('fd.no'))); ?>
          </td>
        </tr>
        <tr class="line<?php print ($l % 2); $l++; ?>">
          <td>
            <b><?php print _txt('fd.xp.notify_couadmin.act'); ?></b><br />
            <span class="descr"><?php print _txt('fd.xp.notify_couadmin.act.desc'); ?></span>
          </td>
          <td>
            <?php print ($e ? $this->Form->input('act_notify_cou_admin')
                            : ($co_expiration_policies[0]['CoExpirationPolicy']['act_notify_cou_admin']
                               ? _txt('fd.yes') : _txt('fd.no'))); ?>
          </td>
        </tr>
        <tr class="line<?php print ($l % 2); $l++; ?>">
          <td>
            <b><?php print _txt('fd.xp.notify_cogroup.act'); ?></b><br />
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
                  print Sanitize::html($vv_co_groups[ $co_expiration_policies[0]['CoExpirationPolicy']['act_notify_co_group_id'] ]);
                }
              }
            ?>
          </td>
        </tr>
        <tr class="line<?php print ($l % 2); $l++; ?>">
          <td>
            <b><?php print _txt('fd.xp.notify_coperson.act'); ?></b><br />
            <span class="descr"><?php print _txt('fd.xp.notify_coperson.act.desc'); ?></span>
          </td>
          <td>
            <?php print ($e ? $this->Form->input('act_notify_co_person')
                            : ($co_expiration_policies[0]['CoExpirationPolicy']['act_notify_co_person']
                               ? _txt('fd.yes') : _txt('fd.no'))); ?>
          </td>
        </tr>
        <tr class="line<?php print ($l % 2); $l++; ?>">
          <td>
            <strong class="fieldTitle"><?php print _txt('fd.xp.nsubject'); ?></strong><br />
            <span class="descr"><?php print _txt('fd.xp.nsubject.desc'); ?></span>
          </td>
          <td>
            <?php print ($e
                         ? $this->Form->input('act_notification_subject',
                                              array('default' => _txt('em.expiration.subject')))
                         : Sanitize::html($co_expiration_policies[0]['CoExpirationPolicy']['act_notification_subject'])); ?>
          </td>
        </tr>
        <tr class="line<?php print ($l % 2); $l++; ?>">
          <td>
            <strong class="fieldTitle"><?php print _txt('fd.xp.nbody'); ?></strong><br />
            <span class="descr"><?php print _txt('fd.xp.nbody.desc'); ?></span>
          </td>
          <td>
            <?php print ($e
                         ? $this->Form->input('act_notification_body',
                                              array('default' => _txt('em.expiration.body')))
                         : Sanitize::html($co_expiration_policies[0]['CoExpirationPolicy']['act_notification_body'])); ?>
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
?>
