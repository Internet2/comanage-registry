<!--
/**
 * COmanage Registry Unix Cluster Plugin Fields
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
 * @since         COmanage Registry v3.3.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */
-->
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
  $args['controller'] = 'clusters';
  $args['action'] = 'index';
  $args['co'] = $cur_co['Co']['id'];
  $this->Html->addCrumb(_txt('ct.clusters.pl'), $args);
  
  $args = array();
  $args['plugin'] = null;
  $args['controller'] = 'clusters';
  $args['action'] = 'edit';
  $args[] = $unix_clusters[0]['UnixCluster']['cluster_id'];
  $this->Html->addCrumb($unix_clusters[0]['Cluster']['description'], $args);
  
  $crumbTxt = _txt('op.' . $this->action . '-a', array($unix_clusters[0]['Cluster']['description']));
  $this->Html->addCrumb($crumbTxt);
  
  print $this->Form->hidden('cluster_id', array('default' => $vv_clusterid)) . "\n";
?>
<ul id="<?php print $this->action; ?>_cluster" class="fields form-list">
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print ($e ? $this->Form->label('sync_mode', _txt('pl.unixcluster.fd.sync_mode')) : _txt('pl.unixcluster.fd.sync_mode')); ?>
        <span class="required">*</span>
      </div>
      <span class="field-desc"><?php print _txt('pl.unixcluster.fd.sync_mode.desc'); ?></span>
    </div>
    <div class="field-info">
      <?php
        global $cm_lang, $cm_texts;
        $attrs = array();
        $attrs['value'] = (isset($unix_clusters) ? $unix_clusters[0]['UnixCluster']['sync_mode'] : UnixClusterSyncEnum::Full);
        $attrs['empty'] = false;
        
        if($e) {
          print $this->Form->select('sync_mode',
                                    $cm_texts[ $cm_lang ]['pl.unixcluster.en.sync'],
                                    $attrs);
          
          if($this->Form->isFieldError('sync_mode')) {
            print $this->Form->error('sync_mode');
          }
        } else {
          print filter_var($unix_clusters[0]['UnixCluster']['sync_mode'],FILTER_SANITIZE_SPECIAL_CHARS);
        }
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print ($e ? $this->Form->label('username_type', _txt('pl.unixcluster.fd.username_type')) : _txt('pl.unixcluster.fd.username_type')); ?>
        <span class="required">*</span>
      </div>
      <span class="field-desc"><?php print _txt('pl.unixcluster.fd.username_type.desc'); ?></span>
    </div>
    <div class="field-info">
      <?php
        $attrs = array();
        $attrs['value'] = (isset($unix_clusters) ? $unix_clusters[0]['UnixCluster']['username_type'] : IdentifierEnum::Network);
        $attrs['empty'] = false;
        
        if($e) {
          print $this->Form->select('username_type',
                                    $vv_identifier_types,
                                    $attrs);
          
          if($this->Form->isFieldError('username_type')) {
            print $this->Form->error('username_type');
          }
        } else {
          print _txt('en.identifier.type', null, filter_var($unix_clusters[0]['UnixCluster']['username_type'],FILTER_SANITIZE_SPECIAL_CHARS));
        }
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print ($e ? $this->Form->label('uid_type', _txt('pl.unixcluster.fd.uid_type')) : _txt('pl.unixcluster.fd.uid_type')); ?>
        <span class="required">*</span>
      </div>
      <span class="field-desc"><?php print _txt('pl.unixcluster.fd.uid_type.desc'); ?></span>
    </div>
    <div class="field-info">
      <?php
        $attrs = array();
        $attrs['value'] = (isset($unix_clusters) ? $unix_clusters[0]['UnixCluster']['uid_type'] : IdentifierEnum::UID);
        $attrs['empty'] = false;
        
        if($e) {
          print $this->Form->select('uid_type',
                                    $vv_identifier_types,
                                    $attrs);
          
          if($this->Form->isFieldError('uid_type')) {
            print $this->Form->error('uid_type');
          }
        } else {
          print _txt('en.identifier.type', null, filter_var($unix_clusters[0]['UnixCluster']['uid_type'],FILTER_SANITIZE_SPECIAL_CHARS));
        }
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print ($e ? $this->Form->label('uid_type', _txt('pl.unixcluster.fd.default_shell')) : _txt('pl.unixcluster.fd.default_shell')); ?>
        <span class="required">*</span>
      </div>
    </div>
    <div class="field-info">
      <?php
        $attrs = array();
        $attrs['value'] = (isset($unix_clusters) ? $unix_clusters[0]['UnixCluster']['default_shell'] : UnixShellEnum::Bash);
        $attrs['empty'] = false;
        
        if($e) {
          print $this->Form->select('default_shell',
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
          
          if($this->Form->isFieldError('default_shell')) {
            print $this->Form->error('default_shell');
          }
        } else {
          print filter_var($unix_clusters[0]['UnixCluster']['default_shell'],FILTER_SANITIZE_SPECIAL_CHARS);
        }
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print ($e ? $this->Form->label('homedir_prefix', _txt('pl.unixcluster.fd.homedir_prefix')) : _txt('pl.unixcluster.fd.homedir_prefix')); ?>
        <span class="required">*</span>
      </div>
      <span class="field-desc"><?php print _txt('pl.unixcluster.fd.homedir_prefix.desc'); ?></span>
    </div>
    <div class="field-info">
      <?php print ($e ? $this->Form->input('homedir_prefix') : filter_var($unix_clusters[0]['UnixCluster']['homedir_prefix'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print ($e ? $this->Form->label('homedir_subdivisions', _txt('pl.unixcluster.fd.homedir_subdivisions')) : _txt('pl.unixcluster.fd.homedir_subdivisions')); ?>
      </div>
      <span class="field-desc"><?php print _txt('pl.unixcluster.fd.homedir_subdivisions.desc'); ?></span>
    </div>
    <div class="field-info">
      <?php print ($e ? $this->Form->input('homedir_subdivisions', array('default' => 0)) : filter_var($unix_clusters[0]['UnixCluster']['homedir_subdivisions'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print ($e ? $this->Form->label('groupname_type', _txt('pl.unixcluster.fd.groupname_type')) : _txt('pl.unixcluster.fd.groupname_type')); ?>
        <span class="required">*</span>
      </div>
      <span class="field-desc"><?php print _txt('pl.unixcluster.fd.groupname_type.desc'); ?></span>
    </div>
    <div class="field-info">
      <?php
        $attrs = array();
        $attrs['value'] = (isset($unix_clusters) ? $unix_clusters[0]['UnixCluster']['groupname_type'] : IdentifierEnum::Network);
        $attrs['empty'] = false;
        
        if($e) {
          print $this->Form->select('groupname_type',
                                    $vv_identifier_types,
                                    $attrs);
          
          if($this->Form->isFieldError('groupname_type')) {
            print $this->Form->error('groupname_type');
          }
        } else {
          print _txt('en.identifier.type', null, filter_var($unix_clusters[0]['UnixCluster']['groupname_type'],FILTER_SANITIZE_SPECIAL_CHARS));
        }
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print ($e ? $this->Form->label('gid_type', _txt('pl.unixcluster.fd.gid_type')) : _txt('pl.unixcluster.fd.gid_type')); ?>
        <span class="required">*</span>
      </div>
      <span class="field-desc"><?php print _txt('pl.unixcluster.fd.gid_type.desc'); ?></span>
    </div>
    <div class="field-info">
      <?php
        $attrs = array();
        $attrs['value'] = (isset($unix_clusters) ? $unix_clusters[0]['UnixCluster']['gid_type'] : IdentifierEnum::GID);
        $attrs['empty'] = false;
        
        if($e) {
          print $this->Form->select('gid_type',
                                    $vv_identifier_types,
                                    $attrs);
          
          if($this->Form->isFieldError('gid_type')) {
            print $this->Form->error('gid_type');
          }
        } else {
          print _txt('en.identifier.type', null, filter_var($unix_clusters[0]['UnixCluster']['gid_type'],FILTER_SANITIZE_SPECIAL_CHARS));
        }
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print ($e ? $this->Form->label('default_co_group_id', _txt('pl.unixcluster.fd.default_co_group_id')) : _txt('pl.unixcluster.fd.default_co_group_id')); ?>
      </div>
      <div class="field-desc"><?php print _txt('pl.unixcluster.fd.default_co_group_id.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php
        $attrs = array();
        $attrs['value'] = (isset($unix_clusters[0]['UnixCluster']['default_co_group_id'])
                           ? $unix_clusters[0]['UnixCluster']['default_co_group_id']
                           : null);
        $attrs['empty'] = _txt('pl.unixcluster.fd.default_co_group_id.create');

        if($e) {
          print $this->Form->select('default_co_group_id',
                                    $vv_available_groups,
                                    $attrs);

          if($this->Form->isFieldError('default_co_group_id')) {
            print $this->Form->error('default_co_group_id');
          }
        } else {
          print $vv_available_groups[ $unix_clusters[0]['UnixCluster']['default_co_group_id'] ];
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