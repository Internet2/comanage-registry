<?php
/**
 * COmanage Registry CO Group Fields
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
  if($permissions['index']) {
    $args = array();
    $args['plugin'] = null;
    $args['controller'] = 'co_groups';
    $args['action'] = 'index';
    $args['co'] = $cur_co['Co']['id'];
    $this->Html->addCrumb(_txt('ct.co_groups.pl'), $args);
  }
  $crumbTxt = _txt('op.' . $this->action . '-a', array(_txt('ct.co_groups.1')));
  $this->Html->addCrumb($crumbTxt);

  // Populate the reference
  print $this->Form->hidden('co_id', array('default' => $cur_co['Co']['id'])). "\n";
  
  // Populate group metadata.
  print $this->Form->hidden('group_type', array('default' => GroupEnum::Standard)). "\n";
  print $this->Form->hidden('auto', array('default' => false)). "\n";
  // COU ID is not currently supported except for automatic groups
  print $this->Form->hidden('cou_id', array('default' => null)). "\n";
  
  $l = 1;
?>

<script type="text/javascript">
  <!-- /* JS specific to these fields */ -->
  
  function js_confirm_autogenerate() {
    // Open the dialog to confirm autogeneration of identifiers
    var $tabs = $( "#tabs" ).tabs();
    $('#autogenerate-dialog').dialog('open');
  }

  $(function() {
    // Autogenerate dialog
    $("#autogenerate-dialog").dialog({
      autoOpen: false,
      <?php if($this->action == 'edit'): ?>
      buttons: [
        {
          text : "<?php print _txt('op.cancel'); ?>",
          click : function() {
            $(this).dialog("close");
          }
        },
        {
          text : "<?php print _txt('op.id.auto'); ?>",
          click: function () {
            $(this).dialog("close");
            displaySpinner();
            window.location.href = "<?php print $this->Html->url(array('controller' => 'identifiers',
            'action' => 'assign',
            'cogroupid' => $co_groups[0]['CoGroup']['id'])); ?>";
          }
        }
      ],
      modal: true,
      show: {
        effect: "fade"
      },
      hide: {
        effect: "fade"
      }
      <?php endif; // edit ?>
    });
  });
</script>

<ul id="<?php print $this->action; ?>_co_group" class="fields form-list">
  <?php if(!empty($co_groups[0]['CoGroup']['group_type'])
    && $co_groups[0]['CoGroup']['group_type'] != GroupEnum::Standard): ?>
    <li>
      <div class="field-name">
        <?php print _txt('fd.type'); ?>
      </div>
      <div class="field-info">
        <?php print filter_var(_txt('en.group.type', null, $co_groups[0]['CoGroup']['group_type']),FILTER_SANITIZE_SPECIAL_CHARS); ?>
      </div>
    </li>
  <?php endif; // group_type ?>
  <li>
    <div class="field-name">
      <div class="field-title"><?php print ($e ? $this->Form->label('name', _txt('fd.name')) . ' <span class="required">*</span>' : _txt('fd.name')); ?></div>
    </div>
    <div class="field-info">
      <?php print ($e ? $this->Form->input('name', array('class' => 'focusFirst')) : filter_var($co_groups[0]['CoGroup']['name'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title"><?php print ($e ? $this->Form->label('description', _txt('fd.desc')) : _txt('fd.desc')); ?></div>
    </div>
    <div class="field-info">
      <?php print ($e ? $this->Form->input('description') : filter_var($co_groups[0]['CoGroup']['description'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title"><?php print ($e ? $this->Form->label('status', _txt('fd.status')) : _txt('fd.status')); ?></div>
    </div>
    <div class="field-info">
      <?php
        global $cm_lang, $cm_texts;
        
        $attrs = array();
        $attrs['value'] = (isset($co_groups) ? $co_groups[0]['CoGroup']['status'] : SuspendableStatusEnum::Active);
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
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title"><?php print _txt('fd.open'); ?></div>
    </div>
    <div class="field-info">
      <?php print ($e ? $this->Form->input('open') : ($co_groups[0]['CoGroup']['open'] ? _txt('fd.yes') : _txt('fd.no'))); ?>
      <?php
        if ($e) {
          print $this->Form->label('open',_txt('fd.open'));
        }
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title"><?php print _txt('fd.co_group.nesting_mode_all'); ?></div>
      <div class="field-desc"><?php print _txt('fd.co_group.nesting_mode_all.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php print ($e ? $this->Form->input('nesting_mode_all') : ($co_groups[0]['CoGroup']['nesting_mode_all'] ? _txt('fd.yes') : _txt('fd.no'))); ?>
      <?php
        if ($e) {
          print $this->Form->label('nesting_mode_all',_txt('fd.co_group.nesting_mode_all'));
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

<?php if($this->action != 'add'): ?>
<h2><?php print _txt('ct.identifiers.pl'); ?></h2>  
<?php if(!empty($co_groups[0]['CoGroup']['id'])
      // XXX It's not clear why we don't permit related models (eg: identifiers,
      // nested groups, email lists) to be added to automatic groups...
      && !$co_groups[0]['CoGroup']['auto']
      && $permissions['edit']): ?>
  <ul class="widget-actions">
    <li>
      <?php
      if($e) {
        // Autogenerate Identifiers button
        if($permissions['assign'] && !empty($co_identifier_assignments)) {
          print $this->Html->link(
            _txt('op.id.auto'),
            'javascript:js_confirm_autogenerate();',
            array('class' => 'contactbutton')
          );
        }
        
        // Add an identifier
        print $this->Html->link(
          _txt('op.add-a', array(_txt('ct.identifiers.1'))),
          array(
            'controller' => 'identifiers',
            'action'     => 'add',
            'cogroupid'    => $co_groups[0]['CoGroup']['id']
          ),
          array('class' => 'addbutton')
        );
      }
      ?>
    </li>
  </ul>
<?php endif; ?>
<div class="table-container">
  <table id="identifiers" class="common-table">
    <thead>
      <tr>
        <th><?php print _txt('fd.name'); ?></th>
        <th><?php print _txt('fd.type'); ?></th>
        <th class="actionButtons"><?php print _txt('fd.actions'); ?></th>
      </tr>
    </thead>
    <tbody>
      <?php
        if(!empty($co_groups[0]['Identifier'])) {
          foreach($co_groups[0]['Identifier'] as $id) {
            print "<tr>";
            
            // Identifier
            print "<td>" . $this->Html->link($id['identifier'],
                                             array('controller' => 'identifiers',
                                                   'action' => $this->action,
                                                   $id['id'])) . "</td>";
            
            // Identifier Type
            print "<td>" . $vv_types['Identifier'][ $id['type'] ] . "</td>";
            
            print '<td class="actions">';
            
            if($e) {
              if($permissions['edit']) {
                print $this->Html->link(_txt('op.edit'),
                                        array('controller' => 'identifiers',
                                              'action' => 'edit',
                                              $id['id']),
                                        array('class' => 'editbutton'));
              }
              
              if($permissions['delete']) {
                print '<a class="deletebutton" title="' . _txt('op.delete')
                  . '" onclick="javascript:js_confirm_generic(\''
                  . _txt('js.remove') . '\',\''    // dialog body text
                  . $this->Html->url(              // dialog confirm URL
                    array(
                      'controller' => 'identifiers',
                      'action' => 'delete',
                      $id['id'],
                      'return' => 'group'
                    )
                  ) . '\',\''
                  . _txt('op.remove') . '\',\''    // dialog confirm button
                  . _txt('op.cancel') . '\',\''    // dialog cancel button
                  . _txt('op.remove') . '\',[\''   // dialog title
                  . filter_var(_jtxt($id['identifier']),FILTER_SANITIZE_STRING)  // dialog body text replacement strings
                  . '\']);">'
                  . _txt('op.delete')
                  . '</a>';
              }
            } else {
              if($permissions['view']) {
                print $this->Html->link(_txt('op.view'),
                                        array('controller' => 'identifiers',
                                              'action' => 'view',
                                              $id['id']),
                                        array('class' => 'viewbutton'));
              }
            }
            
            print "</td>";
            
            print "</tr>";
          }
        }
      ?>
    </tbody>
  </table>
</div>

<h2><?php print _txt('ct.co_group_nestings.pl'); ?></h2>
<?php if(!empty($co_groups[0]['CoGroup']['id'])
      && !$co_groups[0]['CoGroup']['auto']
      && $permissions['nest']): ?>
  <ul class="widget-actions">
    <li>
      <?php
      if($e) {
        // Add a nested group
        print $this->Html->link(
          _txt('op.add-a', array(_txt('ct.co_group_nestings.1'))),
          array(
            'controller' => 'co_group_nestings',
            'action'     => 'add',
            'cogroup'    => $co_groups[0]['CoGroup']['id']
          ),
          array('class' => 'addbutton')
        );
      }
      ?>
    </li>
  </ul>
<?php endif; ?>
<div class="table-container">
  <div id="nestings">
    <?php foreach(array('source' => 'CoGroupNesting',
                        'target' => 'SourceCoGroupNesting') as $k => $m): ?>
    <table class="common-table">
      <thead>
        <tr>
          <th>
            <?php print _txt('fd.co_group.'.$k.'.pl'); ?> -
            <em><?php print _txt('fd.co_group.'.$k.'.desc', array(filter_var($co_groups[0]['CoGroup']['name'],FILTER_SANITIZE_STRING))); ?></em>
          </th>
          <th class="actionButtons"><?php print _txt('fd.actions'); ?></th>
        </tr>
      </thead>
      <tbody>
        <?php if(!empty($co_groups[0][$m])): ?>
        <?php foreach($co_groups[0][$m] as $n): ?>
        <tr>
          <td><?php
            // The model that we want to render, as contain'd by CoGroupNesting
            $gnm = ($k == 'source' ? "CoGroup" : "TargetCoGroup");

            print $this->Html->link($n[$gnm]['name'],
                                    array('controller' => 'co_groups',
                                          'action' => $this->action,
                                          $n[$gnm]['id']));
            
            if($n['negate']) {
              print " (" . _txt('fd.co_group_nesting.negated') . ")";
            }
          ?></td>
          <td class="actions"><?php
            if($e && $permissions['nest']) {
              print '<a class="deletebutton" title="' . _txt('op.remove')
                . '" onclick="javascript:js_confirm_generic(\''
                . _txt('js.remove.nesting') . '\',\''    // dialog body text
                . $this->Html->url(              // dialog confirm URL
                  array(
                    'controller' => 'co_group_nestings',
                    'action' => 'delete',
                    $n['id']
                  )
                ) . '\',\''
                . _txt('op.remove') . '\',\''    // dialog confirm button
                . _txt('op.cancel') . '\',\''    // dialog cancel button
                . _txt('op.remove') . '\',[\''   // dialog title
                . filter_var(_jtxt($n[$gnm]['name']),FILTER_SANITIZE_STRING)  // dialog body text replacement strings
                . '\']);">'
                . _txt('op.remove')
                . '</a>';
            }
          ?></td>
        </tr>
        <?php endforeach; // $n ?>
        <?php else: ?>
            <tr><td colspan="2"><?php print _txt('in.co_group.'.$k.'.none') ?></td></tr>
        <?php endif; // $m ?>
      </tbody>
    </table>
    <?php endforeach; // $k, $m ?>
  </div>
</div>
  
<h2><?php print _txt('ct.co_email_lists.pl'); ?></h2>
<div class="table-container">
  <table id="emailLists" class="common-table">
    <thead>
      <tr>
        <th><?php print _txt('fd.name'); ?></th>
        <th><?php print _txt('fd.status'); ?></th>
        <th><?php print _txt('fd.type'); ?></th>
        <th class="actionButtons"><?php print _txt('fd.actions'); ?></th>
      </tr>
    </thead>
    <tbody>
      <?php
        $mailingListsExist = false;
        foreach(array('Admin', 'Member', 'Moderator') as $listType) {
          if(!empty($co_groups[0]['EmailList'.$listType])) {
            $mailingListsExist = true;
            foreach($co_groups[0]['EmailList'.$listType] as $el) {
              print "<tr>";
              
              // List name
              print "<td>" . $this->Html->link($el['name'],
                                               array('controller' => 'co_email_lists',
                                                     'action' => $this->action,
                                                     $el['id'])) . "</td>";
              
              // List's status
              print "<td>";
              if(!empty($el['status'])) {
                print _txt('en.status', null, $el['status']);
              }
              print "</td>";
              
              // List's usage
              print "<td>" . _txt('fd.el.gr.'.Inflector::tableize($listType)) . "</td>";
              
              if($e) {
                print '<td class="actions">'
                      . $this->Html->link(_txt('op.edit'),
                                          array('controller' => 'co_email_lists',
                                                'action' => 'edit',
                                                $el['id']),
                                          array('class' => 'viewbutton'))
                      . "</td>";
              }
              
              print "</tr>\n";
            }
          }
        }

        if(!$mailingListsExist) {
          print '<tr><td colspan="4">' . _txt('in.co_email_lists.none') . '</td></tr>';
        }
      ?>
    </tbody>
  </table>
</div>

<?php
  // Index the nested groups for rendering purposes
  $nGroups = array();
  
  if(!empty($co_groups[0]['CoGroupNesting'])) {
    foreach($co_groups[0]['CoGroupNesting'] as $n) {
      // We filter_var here since these names are probably going to be printed
      $nGroups[ $n['id'] ] = filter_var($n['CoGroup']['name'],FILTER_SANITIZE_SPECIAL_CHARS);
    }
  }

?>

<h2><?php print _txt('ct.co_group_members.pl'); ?></h2>

<?php if($co_groups[0]['CoGroup']['auto']): ?>
  <div class="co-info-topbox">
    <em class="material-icons">info</em>
    <?php print _txt('in.co_group.auto', array($cur_co['Co']['id'])); ?>
  </div>
<?php endif; ?>

<?php if(!empty($co_groups[0]['CoGroup']['id'])
      && !$co_groups[0]['CoGroup']['auto']
      && $e): ?>
  <ul class="widget-actions">
    <li>
      <?php
      // Manage group memberships
      print $this->Html->link(
        _txt('op.manage.grm'),
        array(
          'controller' => 'co_group_members',
          'action'     => 'select',
          'cogroup'    => $co_groups[0]['CoGroup']['id']
        ),
        array('class' => 'editbutton')
      );
      ?>
    </li>
  </ul>
<?php endif; ?>
<div class="table-container">
  <table id="groupMembers" class="common-table">
    <thead>
      <tr>
        <th><?php print _txt('fd.name'); ?></th>
        <th><?php print _txt('fd.co_people.status'); ?></th>
        <th><?php print _txt('fd.roles'); ?></th>
        <th class="actionButtons"><?php print _txt('fd.actions'); ?></th>
      </tr>
    </thead>
    <tbody>
      <?php
        foreach($vv_co_group_members as $g) {
          print '<tr>';

          // Member name
          print '<td>';
          if($permissions['admin']) {
            print $this->Html->link((!empty($g['CoPerson']['PrimaryName'])
                                     ? generateCn($g['CoPerson']['PrimaryName'])
                                     : "(?)"),
                                    array('controller' => 'co_people',
                                          'action' => 'canvas',
                                          $g['CoPerson']['id']));
          } else {
            print filter_var(generateCn($g['CoPerson']['PrimaryName']),FILTER_SANITIZE_SPECIAL_CHARS);
          }
          print '</td>';

          // Member's CO Person status
          print '<td>';
          if(!empty($g['CoPerson']['status'])) {
            print _txt('en.status', null, $g['CoPerson']['status']);
          }
          print '</td>';

          // Group role (owner vs member)
          print '<td>';
          
          // Is this from a nested group?
          if(!empty($g['CoGroupMember']['co_group_nesting_id'])) {
            print _txt('fd.co_group_member.member.via', array($nGroups[ $g['CoGroupMember']['co_group_nesting_id'] ]));
          } else {
            if($g['CoGroupMember']['owner']) {
              if($g['CoGroupMember']['member']) {
                print _txt('fd.group.grmemown');
              } else {
                print _txt('fd.group.own.only');
              }
            } elseif($g['CoGroupMember']['member']) {
              print _txt('fd.group.mem');
            }
          }
          
          // Warn if membership is invalid
          if((!empty($g['CoGroupMember']['valid_from'])
              && strtotime($g['CoGroupMember']['valid_from']) >= time())
             ||
             (!empty($g['CoGroupMember']['valid_through'])
              && strtotime($g['CoGroupMember']['valid_through']) < time())) {
            print " (" . _txt('fd.inactive') . ")";
          }
          
          print '</td>';

          print '<td class="actions">';
          if($e) {
            // Do not show edit or delete buttons if this is an automatic group
            // or if the membership is due to a nesting.
            if(!$co_groups[0]['CoGroup']['auto'] && !$g['CoGroupMember']['co_group_nesting_id']) {
              print $this->Html->link(
                _txt('op.edit'),
                array(
                  'controller' => 'co_group_members',
                  'action' => 'edit',
                  $g['CoGroupMember']['id']
                ),
                array('class' => 'editbutton')
              );

              if($dok) {
                print '<a class="deletebutton" title="' . _txt('op.delete')
                  . '" onclick="javascript:js_confirm_generic(\''
                  . _txt('js.remove.member') . '\',\''    // dialog body text
                  . $this->Html->url(              // dialog confirm URL
                    array(
                      'controller' => 'co_group_members',
                      'action' => 'delete',
                      $g['CoGroupMember']['id'],
                      'copersonid' => $g['CoGroupMember']['co_person_id'],
                      'return' => 'group'
                    )
                  ) . '\',\''
                  . _txt('op.remove') . '\',\''    // dialog confirm button
                  . _txt('op.cancel') . '\',\''    // dialog cancel button
                  . _txt('op.remove') . '\',[\''   // dialog title
                  . filter_var(_jtxt($co_groups[0]['CoGroup']['name']),FILTER_SANITIZE_STRING)  // dialog body text replacement strings
                  . '\']);">'
                  . _txt('op.delete')
                  . '</a>';
              }
            }
          }
          print '</td>';
          print '</tr>';
        }
      ?>
    </tbody>
  </table>
</div>
<div id="autogenerate-dialog" class="co-dialog" title="<?php print _txt('op.id.auto'); ?>">
  <?php print _txt('op.id.auto.confirm'); ?>
</div>
<?php endif; // add ?>
<?php
  print $this->element("changelog");
