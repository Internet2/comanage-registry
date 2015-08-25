<!--
/**
 * COmanage Registry CO Group Fields
 *
 * Copyright (C) 2010-15 University Corporation for Advanced Internet Development, Inc.
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
 * @copyright     Copyright (C) 2010-15 University Corporation for Advanced Internet Development, Inc.
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
  print $this->element("coCrumb");
  $args = array();
  $args['plugin'] = null;
  $args['controller'] = 'co_groups';
  $args['action'] = 'index';
  $args['co'] = $cur_co['Co']['id'];
  $this->Html->addCrumb(_txt('ct.co_groups.pl'), $args);
  $crumbTxt = _txt('op.' . $this->action . '-a', array(_txt('ct.co_groups.1')));
  $this->Html->addCrumb($crumbTxt);

  // Populate the reference
  print $this->Form->hidden('co_id', array('default' => $cur_co['Co']['id'])). "\n";

?>
<table id="<?php print $this->action; ?>_co_group" class="ui-widget">
  <tbody>
    <tr class="line1">
      <td>
        <?php print _txt('fd.name'); ?><span class="required">*</span>
      </td>
      <td>
        <?php print ($e ? $this->Form->input('name', array('class' => 'focusFirst')) : Sanitize::html($co_groups[0]['CoGroup']['name'])); ?>
      </td>
    </tr>
    <tr class="line2">
      <td>
        <?php print _txt('fd.desc'); ?>
      </td>
      <td>
        <?php print ($e ? $this->Form->input('description') : Sanitize::html($co_groups[0]['CoGroup']['description'])); ?>
      </td>
    </tr>
    <tr class="line1">
      <td>
        <?php print _txt('fd.open'); ?>
      </td>
      <td>
        <?php print ($e ? $this->Form->input('open') : ($co_groups[0]['CoGroup']['open'] ? _txt('fd.yes') : _txt('fd.no'))); ?>
      </td>
    </tr>
    <tr class="line2">
      <td>
        <?php print _txt('fd.status'); ?>
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
        <em><span class="required"><?php print _txt('fd.req'); ?></span></em><br />
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
    <tr>
      <td></td>
      <td id="groupMembers">
        <h3><?php print _txt('ct.co_group_members.pl'); ?></h3>
        <ul>
        <?php
          if(isset($co_group_members)) {
            if($e) {
              foreach($co_group_members as $c) {
                foreach($c['CoPerson']['CoGroupMember'] as $g) {
                  // We get all the group memberships, but are only interested in the current group
                  if($g['co_group_id'] == $co_groups[0]['CoGroup']['id']) {
                    print '<li>';
                    // Do not show edit or delete buttons if this is a members group.
                    if(!$isMembersGroup) {
                      if($dok) {
                        print '<a class="deletebutton right" title="' . _txt('op.delete')
                          . '" onclick="javascript:js_confirm_generic(\''
                          . _txt('js.remove.member') . '\',\''    // dialog body text
                          . $this->Html->url(              // dialog confirm URL
                            array(
                              'controller' => 'co_group_members',
                              'action' => 'delete', $g['id'],
                              'copersonid' => $g['co_person_id'],
                              'return' => 'group'
                            )
                          ) . '\',\''
                          . _txt('op.remove') . '\',\''    // dialog confirm button
                          . _txt('op.cancel') . '\',\''    // dialog cancel button
                          . _txt('op.remove') . '\',[\''   // dialog title
                          . _jtxt(filter_var($co_groups[0]['CoGroup']['name'],FILTER_SANITIZE_STRING))  // dialog body text replacement strings
                          . '\'])";>'
                          . _txt('op.delete')
                          . '</a>';
                      }
                      print $this->Html->link(_txt('op.edit'),
                        array(
                          'controller' => 'co_group_members',
                          'action' => 'edit',
                          $g['id']
                        ),
                        array('class' => 'editbutton right'));
                    }

                    if($permissions['admin']) {
                      print $this->Html->link(generateCn($c['CoPerson']['PrimaryName']),
                                              array('controller' => 'co_people',
                                                    'action' => 'canvas',
                                                    $c['CoPerson']['id']));
                    } else {
                      print generateCn($c['CoPerson']['PrimaryName']);
                    }

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
                    print "</li>\n";
                    break;
                  }
                }
              }
            } else {
              foreach($co_group_members as $c) {
                print '<li>';
                print Sanitize::html(generateCn($c['CoPerson']['PrimaryName'])) . "<br />\n";
                print '</li>';
              }
            }
            if (count($co_group_members) == 0) {
              print "<li>" . _txt('ct.co_group_members.0') . "</li>";
            }
          }
        ?>
        </ul>
      </td>
    </tr>
  </tbody>
</table>
<?php
  print $this->element("changelog");
