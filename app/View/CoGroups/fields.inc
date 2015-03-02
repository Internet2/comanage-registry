<!--
/**
 * COmanage Registry CO Group Fields
 *
 * Copyright (C) 2010-13 University Corporation for Advanced Internet Development, Inc.
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
 * @copyright     Copyright (C) 2011-13 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.1
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */
-->
<?php
  // Determine if fields are editable or viewable
  $dok = false;
  $e = false;
  $v = false;
  
  if(($this->action == "add" && $permissions['add'])
     || ($this->action == "edit" && $permissions['edit'])
     || (($this->action == 'add' || $this->action == 'edit')
         && isset($co_groups[0]['CoGroup']['id'])
         && !empty($permissions['owner'])
         && in_array($co_groups[0]['CoGroup']['id'], $permissions['owner'])))
    $e = true;

  if(($permissions['delete'])
     || (isset($co_groups[0]['CoGroup']['id'])
         && !empty($permissions['owner'])
         && in_array($co_groups[0]['CoGroup']['id'], $permissions['owner'])))
    $dok = true;

  if(($this->action == "view" && $permissions['view'])
     || (isset($co_groups[0]['CoGroup']['id'])
         && !empty($permissions['member'])
         && in_array($co_groups[0]['CoGroup']['id'], $permissions['member']))
     || (isset($co_groups[0]['CoGroup']['open']) && $co_groups[0]['CoGroup']['open']))
    $v = true;

  // We shouldn't get here if we don't have at least read permission, but check just in case
  
  if(!$e && !$v)
    return(false);

  // Add breadcrumbs
  $args = array();
  $args['plugin'] = null;
  $args['controller'] = 'co_groups';
  $args['action'] = 'index';
  $args['co'] = $cur_co['Co']['id'];
  $this->Html->addCrumb(_txt('ct.co_groups.pl'), $args);
  if($this->action == "add") {
    $this->Html->addCrumb(_txt('op.add') . ' ' . _txt('ct.co_groups.1'));
  }
  if($this->action == "edit") {
    $this->Html->addCrumb(_txt('op.edit') . ' ' . _txt('ct.co_groups.1'));
  }
  if($this->action == "view") {
    $this->Html->addCrumb(_txt('op.view') . ' ' . _txt('ct.co_groups.1'));
  }
  
  // Add buttons to sidebar
  $sidebarButtons = $this->get('sidebarButtons');

  if($e && !empty($co_groups[0]['CoGroup']['id'])) {
    // Manage group memberships
    $sidebarButtons[] = array(
      'icon'    => 'extlink',
      'title'   => _txt('op.manage.grm'),
      'url'     => array(
        'controller' => 'co_group_members',
        'action'     => 'select',
        'cogroup'    => $co_groups[0]['CoGroup']['id'],
        'co'         => $cur_co['Co']['id']
      )
    );
  }

  if($permissions['provision'] && !empty($co_groups[0]['CoGroup']['id'])) {
    // Provisioning status
    $sidebarButtons[] = array(
      'icon'    => 'note',
      'title'   => _txt('op.prov.view'),
      'url'     => array(
        'controller' => 'co_groups', 
        'action'     => 'provision', 
        $co_groups[0]['CoGroup']['id'], 
        'co'         => $cur_co['Co']['id']
      )
    ); 
  }
  
  // Set buttons for rendering in sidebar  
  $this->set('sidebarButtons', $sidebarButtons);
  
  // Populate the reference
  echo $this->Form->hidden('co_id', array('default' => $cur_co['Co']['id'])). "\n";

?>
<table id="<?php echo $this->action; ?>_co_group" class="ui-widget">
  <tbody>
    <tr class="line1">
      <td>
        <?php echo _txt('fd.name'); ?><span class="required">*</span>
      </td>
      <td>
        <?php echo ($e ? $this->Form->input('name', array('class' => 'focusFirst')) : Sanitize::html($co_groups[0]['CoGroup']['name'])); ?>
      </td>
    </tr>
    <tr class="line2">
      <td>
        <?php echo _txt('fd.desc'); ?>
      </td>
      <td>
        <?php echo ($e ? $this->Form->input('description') : Sanitize::html($co_groups[0]['CoGroup']['description'])); ?>
      </td>
    </tr>
    <tr class="line1">
      <td>
        <?php echo _txt('fd.open'); ?>
      </td>
      <td>
        <?php echo ($e ? $this->Form->input('open') : ($co_groups[0]['CoGroup']['open'] ? _txt('fd.yes') : _txt('fd.no'))); ?>
      </td>
    </tr>
    <tr class="line2">
      <td>
        <?php echo _txt('fd.status'); ?>
      </td>
      <td>
        <?php
          global $cm_lang, $cm_texts;
          $attrs['value'] = (isset($co_groups) ? $co_groups[0]['CoGroup']['status'] : 'A');
          $attrs['empty'] = false;
          
          if($e) {
            print $this->Form->select('status',
                                      $cm_texts[ $cm_lang ]['en.status.susp'],
                                      $attrs);
            
            if($this->Form->isFieldError('status')) {
              print $this->Form->error('status');
            }
          } else {
            print _txt('en.status', null, $co_groups[0]['CoGroup']['status']);
          }
        ?>
      </td>
    </tr>
    <tr>
      <td>
        <i><span class="required"><?php echo _txt('fd.req'); ?></span></i><br />
      </td>
      <td>
        <?php
          if($e) {
            echo $this->Form->submit($submit_label);
            print $this->Form->button(_txt('op.reset'), 
                                      array('type'=>'reset'));
          }
        ?>
      </td>
    </tr>
    <tr class="line1">
      <td>
        <?php echo _txt('fd.members'); ?>
      </td>
      <td>
      <?php
        if(isset($co_group_members)) {
          if($e) {
            foreach($co_group_members as $c) {
              foreach($c['CoPerson']['CoGroupMember'] as $g) {
                // We get all the group memberships, but are only interested in the current group
                if($g['co_group_id'] == $co_groups[0]['CoGroup']['id']) {
                  // Do not show edit or delete buttons if this is a members group.
                  if(!$isMembersGroup) {
                  	// XXX the edit/delete buttons should really be right aligned
                  	print $this->Html->link(_txt('op.edit'),
                                          array('controller' => 'co_group_members',
                                                'action' => 'edit',
                                                $g['id']),
                                          array('class' => 'editbutton'));
                  
	                  if($dok) {
	                    print '<a class="deletebutton" title="' . _txt('op.delete') . '" onclick="javascript:js_confirm_delete(\'' . _jtxt(_txt('fd.group.memin', array(Sanitize::html($co_groups[0]['CoGroup']['name'])))) . '\', \'' . $this->Html->url(array('controller' => 'co_group_members', 'action' => 'delete', $g['id'], 'copersonid' => $g['co_person_id'], 'co' => $cur_co['Co']['id'])) . '\')";>' . _txt('op.delete') . '</a>' . "\n";
	                  }
                  }
                  
                  print $this->Html->link(generateCn($c['CoPerson']['PrimaryName']),
                                          array('controller' => 'co_people',
                                                'action' => 'canvas',
                                                $c['CoPerson']['id']));
                  
                  // Print person's status
                  if( !empty($c['CoPerson']['status']) ) {
                    print ' (' 
                      . _txt('en.status', null, $c['CoPerson']['status'])
                      .')';
                  }
                  
                  if($g['owner']) {
                    if(!$g['member']) {
                      print " " . _txt('fd.group.own.only');
                    } else {
                      print " " . _txt('fd.group.own');
                    }
                  }
                  
                  print "<br />\n";
                  break;
                }
              }
            }
          } else {
            foreach($co_group_members as $c)
              echo Sanitize::html(generateCn($c['CoPerson']['PrimaryName'])) . "<br />\n";
          }
        }
      ?>
      </td>
    </tr>
  </tbody>
</table>
