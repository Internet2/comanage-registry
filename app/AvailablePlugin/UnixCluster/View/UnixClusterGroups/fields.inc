<?php
/**
 * COmanage Registry Unix Cluster Group Fields
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
    
  // We shouldn't get here if we don't have at least read permission, but check just in case
  
  if(!$e && !$permissions['view'])
    return(false);

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
  $args[] = $vv_unix_cluster['Cluster']['id'];
  $this->Html->addCrumb($vv_unix_cluster['Cluster']['description'], $args);
  
  $args = array();
  $args['plugin'] = 'unix_cluster';
  $args['controller'] = 'unix_clusters';
  $args['action'] = 'edit';
  $args[] = $vv_unix_cluster['UnixCluster']['id'];
  $this->Html->addCrumb(_txt('op.config'), $args);
  
  $crumbTxt = _txt('op.' . $this->action . '-a', array(_txt('ct.unix_cluster_groups.1')));
  $this->Html->addCrumb($crumbTxt);

  if($this->action != 'view') {
    // Populate cross references
    print $this->Form->hidden('unix_cluster_id', array('default' => $vv_unix_cluster['UnixCluster']['id'])) . "\n";
  }
?>
<ul id="<?php print $this->action; ?>_unix_cluster_groups" class="fields form-list">
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
      <?php print $this->Form->label('co_group_id', _txt('ct.co_groups.1')); ?>
    </div>
    <div class="field-info">
      <?php
        if(!empty($vv_available_groups)) {
          $attrs = array();
          // Since we don't support edit, we don't set $attrs['value'] to the current value
          $attrs['empty'] = false;

          print $this->Form->select('co_group_id',
                                    $vv_available_groups,
                                    $attrs);

          if($this->Form->isFieldError('co_group_id')) {
            print $this->Form->error('co_group_id');
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