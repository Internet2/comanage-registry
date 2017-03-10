<!--
/**
 * COmanage Registry LDAP Service Target Provisioning Target Fields
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
 * @since         COmanage Registry v2.0.0
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
  
  print $this->Form->hidden('co_id', array('default' => $cur_co['Co']['id'])) . "\n";
  print $this->Form->hidden('co_provisioning_target_id', array('default' => $vv_ptid)) . "\n";
?>
<table id="<?php print $this->action; ?>_co_ldap_service_target_provisioner_target" class="ui-widget">
  <tbody>
    <tr class="line1">
      <td>
        <strong class="fieldTitle"><?php print _txt('pl.ldapstprovisioner.ldap'); ?></strong><font class="required">*</font>
      </td>
      <td>
        <?php
          $attrs = array();
          $attrs['empty'] = false;
          
          print $this->Form->select('co_ldap_provisioner_target_id',
                                    $vv_ldap_provisioners,
                                    $attrs);

          if($this->Form->isFieldError('co_ldap_provisioner_target_id')) {
            print $this->Form->error('co_ldap_provisioner_target_id');
          }
        ?>
      </td>
    </tr>
    <tr class="line2">
      <td>
        <strong class="fieldTitle"><?php print _txt('pl.ldapstprovisioner.service'); ?></strong><font class="required">*</font>
      </td>
      <td>
        <?php
          $attrs = array();
          $attrs['empty'] = false;
          
          print $this->Form->select('co_service_id',
                                    $vv_enabled_services,
                                    $attrs);

          if($this->Form->isFieldError('co_service_id')) {
            print $this->Form->error('co_service_id');
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
          if($e)
            print $this->Form->submit($submit_label);
        ?>
      </td>
    </tr>
  </tbody>
</table>