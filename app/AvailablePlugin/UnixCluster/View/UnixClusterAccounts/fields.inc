<?php
/**
 * COmanage Registry Unix Cluster Account Fields
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
 * @since         COmanage Registry v3.3.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

  // Determine if fields are editable
  $e = false;
  
  if(($this->action == "add" && $permissions['add']) || ($this->action == "edit" && $permissions['edit']))
    $e = true;
  
  // We don't currently allow any self-editing
  $es = false;
  
  // We shouldn't get here if we don't have at least read permission, but check just in case
  
  if(!$e && !$permissions['view'])
    return(false);
    
  // Make sure we were given a valid CO Person ID
  if(empty($vv_co_person['CoPerson']))
    return false;

  // Add breadcrumbs
  print $this->element("coCrumb");
  $args = array();
  $args['plugin'] = null;
  $args['controller'] = 'co_people';
  $args['action'] = 'index';
  $args['co'] = $cur_co['Co']['id'];
  $this->Html->addCrumb(_txt('me.population'), $args);
  
  $args = array();
  $args['plugin'] = null;
  $args['controller'] = 'co_people';
  $args['action'] = 'canvas';
  $args[] = $vv_co_person['CoPerson']['id'];
  $this->Html->addCrumb(generateCn($vv_co_person['PrimaryName']), $args);
  
  $args = array();
  $args['plugin'] = null;
  $args['controller'] = 'clusters';
  $args['action'] = 'status';
  $args['copersonid'] = $vv_co_person['CoPerson']['id'];
  $this->Html->addCrumb(_txt('ct.clusters.pl'), $args);
  
  $args = array();
  $args['plugin'] = 'unix_cluster';
  $args['controller'] = 'unix_cluster_accounts';
  $args['action'] = 'index';
  $args['ucid'] = $vv_unix_cluster['UnixCluster']['id'];
  $args['copersonid'] = $vv_co_person['CoPerson']['id'];
  $this->Html->addCrumb(_txt('pl.unixcluster.accounts',  array($vv_unix_cluster['Cluster']['description'])), $args);
  
  $this->Html->addCrumb($title_for_layout);
  
  if($this->action != 'view') {
    // Populate cross references
    print $this->Form->hidden('unix_cluster_id', array('default' => $vv_unix_cluster['UnixCluster']['id'])) . "\n";
    print $this->Form->hidden('co_person_id', array('default' => $vv_co_person['CoPerson']['id'])) . "\n";
  }
?>
<ul id="<?php print $this->action; ?>_unix_cluster_accounts" class="fields form-list">
  <li>
    <div class="field-name">
      <?php print _txt('ct.unix_clusters.1'); ?>
    </div>
    <div class="field-info">
      <?php print filter_var($vv_unix_cluster['Cluster']['description'], FILTER_SANITIZE_SPECIAL_CHARS); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print ($e ? $this->Form->label('sync_mode', _txt('pl.unixcluster.fd.sync_mode')) : _txt('pl.unixcluster.fd.sync_mode')); ?>
        <span class="required">*</span>
      </div>
      <span class="field-desc"><?php print _txt('pl.unixcluster.fd.sync_mode.acct.desc'); ?></span>
    </div>
    <div class="field-info">
      <?php
        global $cm_lang, $cm_texts;
        $attrs = array();
        // If the value isn't already set, we're creating a new account manually,
        // so default to manual attribute syncing for this account
        $attrs['value'] = (isset($unix_cluster_accounts) ? $unix_cluster_accounts[0]['UnixClusterAccount']['sync_mode'] : UnixClusterSyncEnum::Manual);
        $attrs['empty'] = false;
        
        if($e) {
          print $this->Form->select('sync_mode',
                                    $cm_texts[ $cm_lang ]['pl.unixcluster.en.sync'],
                                    $attrs);
          
          if($this->Form->isFieldError('sync_mode')) {
            print $this->Form->error('sync_mode');
          }
        } else {
          print filter_var($unix_cluster_accounts[0]['UnixClusterAccount']['sync_mode'],FILTER_SANITIZE_SPECIAL_CHARS);
        }
      ?>
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
        // We should pull this from the Model validation rules...
        $validStatuses = array(
          StatusEnum::Active              => 'Active',
          StatusEnum::Deleted             => 'Deleted',
          StatusEnum::Duplicate           => 'Duplicate',
          StatusEnum::Expired             => 'Expired',
          StatusEnum::GracePeriod         => 'Grace Period',
          StatusEnum::PendingApproval     => 'Pending Approval',
          StatusEnum::Suspended           => 'Suspended'
        );
        
        $attrs = array();
        $attrs['value'] = (isset($unix_cluster_accounts[0]['UnixClusterAccount']['status'])
                           ? $unix_cluster_accounts[0]['UnixClusterAccount']['status']
                           : StatusEnum::Active);
        $attrs['empty'] = false;
        
        if($e && !$es) {
          print $this->Form->select('status',
                                    $validStatuses,
                                    $attrs);
          
          if($this->Form->isFieldError('status')) {
            print $this->Form->error('status');
          }
        } else {
          if(!empty($unix_cluster_accounts[0]['UnixClusterAccount']['status'])) {
            print _txt('en.status', null, $unix_cluster_accounts[0]['UnixClusterAccount']['status']);
          }
        }
      ?>
    </div>
  </li> 
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print (($e && !$es) ? $this->Form->label('username', _txt('pl.unixcluster.fd.username')) : _txt('pl.unixcluster.fd.username')); ?>
        <span class="required">*</span>
      </div>
    </div>
    <div class="field-info">
      <?php print (($e && !$es) ? $this->Form->input('username') : filter_var($unix_cluster_accounts[0]['UnixClusterAccount']['username'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print (($e && !$es) ? $this->Form->label('uid', _txt('pl.unixcluster.fd.uid')) : _txt('pl.unixcluster.fd.uid')); ?>
        <span class="required">*</span>
      </div>
    </div>
    <div class="field-info">
      <?php print (($e && !$es) ? $this->Form->input('uid') : filter_var($unix_cluster_accounts[0]['UnixClusterAccount']['uid'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print ($e ? $this->Form->label('gecos', _txt('pl.unixcluster.fd.gecos')) : _txt('pl.unixcluster.fd.gecos')); ?>
      </div>
    </div>
    <div class="field-info">
      <?php print ($e ? $this->Form->input('gecos') : filter_var($unix_cluster_accounts[0]['UnixClusterAccount']['gecos'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <?php print $this->Form->label('login_shell', _txt('pl.unixcluster.fd.login_shell')); ?>
      <span class="required">*</span>
    </div>
    <div class="field-info">
      <?php
        $attrs = array();
        $attrs['value'] = (isset($unix_cluster_accounts) ? $unix_cluster_accounts[0]['UnixClusterAccount']['login_shell'] : UnixShellEnum::Bash);
        $attrs['empty'] = false;
        
        if($e) {
          print $this->Form->select('login_shell',
                                    // In v5 use ReflectionClass...
                                    array(
                                      UnixShellEnum::Bash => "/bin/bash",
                                      UnixShellEnum::Csh  => "/bin/csh",
                                      UnixShellEnum::Dash => "/bin/dash",
                                      UnixShellEnum::Ksh  => "/bin/ksh",
                                      UnixShellEnum::Sh   => "/bin/sh",
                                      UnixShellEnum::Tcsh => "/bin/tcsh",
                                      UnixShellEnum::Zsh  => "/bin/zsh"
                                    ),
                                    $attrs);
          
          if($this->Form->isFieldError('login_shell')) {
            print $this->Form->error('login_shell');
          }
        } else {
          if(!empty($unix_cluster_accounts[0]['UnixClusterAccount']['login_shell'])) {
            print filter_var($unix_cluster_accounts[0]['UnixClusterAccount']['login_shell'],FILTER_SANITIZE_SPECIAL_CHARS);
          }
        }
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print (($e && !$es) ? $this->Form->label('home_directory', _txt('pl.unixcluster.fd.home_directory')) : _txt('pl.unixcluster.fd.home_directory')); ?>
        <span class="required">*</span>
      </div>
    </div>
    <div class="field-info">
      <?php print (($e && !$es) ? $this->Form->input('home_directory') : filter_var($unix_cluster_accounts[0]['UnixClusterAccount']['home_directory'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <?php print $this->Form->label('primary_co_group_id', _txt('pl.unixcluster.fd.primary_co_group_id')); ?>
        <span class="required">*</span>
    </div>
    <div class="field-info">
      <?php
// XXX This should use unix cluster groups instead
        if(!empty($vv_available_groups)) {
          $attrs = array();
// XXX default value here should be Cluster Default Group
          $attrs['value'] = (isset($unix_cluster_accounts) ? $unix_cluster_accounts[0]['UnixClusterAccount']['primary_co_group_id'] : "");
// XXX if $e && !$es make the select read only
          $attrs['empty'] = false;

          print $this->Form->select('primary_co_group_id',
                                    $vv_available_groups,
                                    $attrs);

          if($this->Form->isFieldError('primary_co_group_id')) {
            print $this->Form->error('primary_co_group_id');
          }
        }
      ?>
    </div>
  </li>
  <li class="modelbox-data">
    <div class="field-name">
      <?php
        if($e && !$es) {
          print $this->Form->label('valid_from', _txt('fd.valid_from.tz', array($vv_tz)));
        } else {
          print _txt('fd.valid_from');
        }
      ?><br />
      <span class="field-descr"><?php print _txt('fd.valid_from.desc'); ?></span>
    </div>
    <div class="field-info">
      <?php
        if($e && !$es) {
          $args = array(
            'class' => 'datepicker-f'
          );

          if(!empty($unix_cluster_accounts[0]['UnixClusterAccount']['valid_from'])) {
            if(!empty($vv_tz)) {
              // We need to adjust the UTC value to the user's local time
              $args['value'] = $this->Time->format($unix_cluster_accounts[0]['UnixClusterAccount']['valid_from'], "%F %T", false, $vv_tz);
            } else {
              $args['value'] = $unix_cluster_accounts[0]['UnixClusterAccount']['valid_from'];
            }
          }

          print $this->Form->text('valid_from', $args);
        } else {
          if(!empty($unix_cluster_accounts[0]['UnixClusterAccount']['valid_from'])) {
            print $this->Time->format($unix_cluster_accounts[0]['UnixClusterAccount']['valid_from'], "%c $vv_tz", false, $vv_tz);
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
        if($e && !$es) {
          $args = array(
            'class' => 'datepicker-u'
          );

          if(!empty($unix_cluster_accounts[0]['UnixClusterAccount']['valid_through'])) {
            if(!empty($vv_tz)) {
              // We need to adjust the UTC value to the user's local time
              $args['value'] = $this->Time->format($unix_cluster_accounts[0]['UnixClusterAccount']['valid_through'], "%F %T", false, $vv_tz);
            } else {
              $args['value'] = $unix_cluster_accounts[0]['UnixClusterAccount']['valid_through'];
            }
          }

          print $this->Form->text('valid_through', $args);
        } else {
          if(!empty($unix_cluster_accounts[0]['UnixClusterAccount']['valid_through'])) {
            print $this->Time->format($unix_cluster_accounts[0]['UnixClusterAccount']['valid_through'], "%c $vv_tz", false, $vv_tz);
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

<?php
  print $this->element("changelog");