<?php
/**
 * COmanage Registry CO Group Member Fields
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
 * @since         COmanage Registry v0.1
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

  // Determine if fields are editable or viewable
  $e = ($this->action == 'edit' && $permissions['edit']);
  $v = ($this->action == 'view' && $permissions['view']);
  
  // We shouldn't get here if we don't have at least read permission, but check just in case
  
  if(!$e && !$v)
    return(false);

  // Add breadcrumbs
  print $this->element("coCrumb");
  
  $args = array();
  $args['plugin'] = null;
  $args['controller'] = 'co_groups';
  $args['action'] = 'index';
  $args['co'] = $cur_co['Co']['id'];
  $this->Html->addCrumb(_txt('ct.co_groups.pl'), $args);
  $crumbTxt = _txt('op.' . $this->action . '-a', array(_txt('ct.co_groups.1')));
  $this->Html->addCrumb($crumbTxt);

  if($e)
  {
    // Populate the references
    print $this->Form->hidden('co_id', array('default' => $cur_co['Co']['id'])). "\n";
    print $this->Form->hidden('id', array('default' => $this->request->data['CoGroupMember']['id'])). "\n";
    print $this->Form->hidden('co_group_id', array('default' => $this->request->data['CoGroupMember']['co_group_id'])). "\n";
    print $this->Form->hidden('co_person_id', array('default' => $this->request->data['CoGroupMember']['co_person_id'])). "\n";
  }
?>

<div class="table-container">
  <table id="<?php print $this->action; ?>_co_group_member">
    <tbody>
      <tr class="line1">
        <td>
          <?php print _txt('fd.group.mem'); ?>
        </td>
        <td>
          <?php print ($e
                       ? $this->Form->input('member') . ' ' .  $this->Form->label('member', _txt('fd.group.mem'))
                       : ($co_group_members[0]['CoGroupMember']['member'] ? _txt('fd.yes') : _txt('fd.no'))); ?>
        </td>
      </tr>
      <tr class="line2">
        <td>
          <?php print _txt('fd.group.own'); ?>
        </td>
        <td>
          <?php print ($e
                       ? $this->Form->input('owner') . ' ' . $this->Form->label('owner', _txt('fd.group.own'))
                       : ($co_group_members[0]['CoGroupMember']['owner'] ? _txt('fd.yes') : _txt('fd.no'))); ?>
        </td>
      </tr>
      <tr>
        <td>
          <em><span class="required"><?php print _txt('fd.req'); ?></span></em><br />
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
</div>
  
<?php
  print $this->element("changelog");
