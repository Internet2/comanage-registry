<!--
/**
 * COmanage Registry CO Grouper Provisioning Target Fields
 *
 * Copyright (C) 2012-14 University Corporation for Advanced Internet Development, Inc.
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
 * @copyright     Copyright (C) 2012-14 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry-plugin
 * @since         COmanage Registry v0.8.3
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
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
  
  print $this->Form->hidden('co_id', array('default' => $cur_co['Co']['id'])) . "\n";
  print $this->Form->hidden('co_provisioning_target_id', array('default' => $vv_ptid)) . "\n";
?>
<div class="ui-state-highlight ui-corner-all" style="margin-top: 20px; padding: 0 .7em;"> 
  <p>
    <span class="ui-icon ui-icon-info" style="float: left; margin-right: .3em;"></span>
    <strong><?php print _txt('pl.grouperprovisioner.info'); ?></strong>
  </p>
</div>
<br />
<table id="<?php print $this->action; ?>_co_grouper_provisioner_target" class="ui-widget">
  <tbody>
    <tr class="line1">
      <td>
        <?php print _txt('pl.grouperprovisioner.serverurl'); ?><font class="required">*</font><br />
        <font class="desc"><?php print _txt('pl.grouperprovisioner.serverurl.desc'); ?></font>
      </td>
      <td>
        <?php print ($e ? $this->Form->input('serverurl', array('size' => 50)) : filter_var($co_grouper_provisioner_targets[0]['CoGrouperProvisionerTarget']['serverurl'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
      </td>
    </tr>
    <tr class="line2">
      <td>
        <?php print _txt('pl.grouperprovisioner.contextpath'); ?><font class="required">*</font><br />
        <font class="desc"><?php print _txt('pl.grouperprovisioner.contextpath.desc'); ?></font>
      </td>
      <td>
        <?php print ($e ? $this->Form->input('contextpath', array('size' => 20)) : filter_var($co_grouper_provisioner_targets[0]['CoGrouperProvisionerTarget']['contextpath'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
      </td>
    </tr>
    <tr class="line1">
      <td>
        <?php print _txt('pl.grouperprovisioner.login'); ?><font class="required">*</font><br />
        <font class="desc"><?php print _txt('pl.grouperprovisioner.login.desc'); ?></font>
      </td>
      <td>
        <?php print ($e ? $this->Form->input('login', array('size' => 24)) : filter_var($co_grouper_provisioner_targets[0]['CoGrouperProvisionerTarget']['login'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
      </td>
    </tr>
    <tr class="line2">
      <td>
        <?php print _txt('pl.grouperprovisioner.password'); ?><font class="required">*</font><br />
        <font class="desc"><?php print _txt('pl.grouperprovisioner.password.desc'); ?></font>
      </td>
      <td>
        <?php print ($e ? $this->Form->input('password') : filter_var($co_grouper_provisioner_targets[0]['CoGrouperProvisionerTarget']['password'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
      </td>
    </tr>
    <tr class="line1">
      <td>
        <?php print _txt('pl.grouperprovisioner.stem'); ?><font class="required">*</font><br />
        <font class="desc"><?php print _txt('pl.grouperprovisioner.stem.desc'); ?></font>
      </td>
      <td>
        <?php print ($e ? $this->Form->input('stem', array('size' => 50)) : filter_var($co_grouper_provisioner_targets[0]['CoGrouperProvisionerTarget']['stem'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
      </td>
    </tr>
    <tr class="line2">
      <td>
        <?php print _txt('pl.grouperprovisioner.loginidentifier'); ?><font class="required">*</font><br />
        <font class="desc"><?php print _txt('pl.grouperprovisioner.loginidentifier.desc'); ?></font>
      </td>
      <td>
        <?php 
        	if($e) {
            $args = array();
            $args['empty'] = '(choose one)';
        		print $this->Form->select('login_identifier', $identifier_types, $args);
        	} else {
        		print filter_var($co_grouper_provisioner_targets[0]['CoGrouperProvisionerTarget']['login_identifier'],FILTER_SANITIZE_SPECIAL_CHARS);
        	}
        ?>
      </td>
    </tr>
    <tr class="line1">
      <td>
        <?php print _txt('pl.grouperprovisioner.emailidentifier'); ?><font class="required">*</font><br />
        <font class="desc"><?php print _txt('pl.grouperprovisioner.emailidentifier.desc'); ?></font>
      </td>
      <td>
        <?php 
        	if($e) {
            $args = array();
            $args['empty'] = '(choose one)';
        		print $this->Form->select('email_identifier', $email_address_types, $args);
        	} else {
        		print filter_var($co_grouper_provisioner_targets[0]['CoGrouperProvisionerTarget']['email_identifier'],FILTER_SANITIZE_SPECIAL_CHARS);
        	}
        ?>
      </td>
    </tr>
    <tr class="line2">
      <td>
        <?php print _txt('pl.grouperprovisioner.subjectview'); ?><font class="required">*</font><br />
        <font class="desc"><?php print _txt('pl.grouperprovisioner.subjectview.desc'); ?></font>
      </td>
      <td>
        <?php 
        if ($e) {
          $args = array();
          $args['size'] = 30;
          if (!isset($co_grouper_provisioner_targets[0]['CoGrouperProvisionerTarget']['subject_view'])) {
          	$args['default'] = $viewDefault;	
          }
        	print $this->Form->input('subject_view', $args);
        } else {
        	print filter_var($co_grouper_provisioner_targets[0]['CoGrouperProvisionerTarget']['subject_view'],FILTER_SANITIZE_SPECIAL_CHARS);
        }
        ?>
      </td>
    </tr>
    <tr>
      <td>
        <i><font class="required"><?php print _txt('fd.req'); ?></font></i><br />
      </td>
      <td>
        <?php
          if($e) {
            print $this->Form->submit($submit_label);
            print $this->Form->button(_txt('op.reset'), 
                                      array('type'=>'reset'));
          }
        ?>
      </td>
    </tr>
  </tbody>
</table>
