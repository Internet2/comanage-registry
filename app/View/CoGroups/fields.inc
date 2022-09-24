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
    $args['search.auto'] = 'f';
    $args['search.noadmin'] = '1'; 
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

<h2 class="subtitle"><?php print _txt('in.co_group.props'); ?></h2>

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

<?php if ($permissions['viewids'] && ($this->action != 'add')): ?>
  <h2 class="subtitle"><?php print _txt('ct.identifiers.pl'); ?></h2>
  <?php if($permissions['editids']
        && !empty($co_groups[0]['CoGroup']['id'])): ?>
    <ul class="widget-actions">
      <li>
        <?php
          // Autogenerate Identifiers button
          if ($permissions['assign'] && !empty($co_identifier_assignments)) {
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
              'action' => 'add',
              'cogroupid' => $co_groups[0]['CoGroup']['id']
            ),
            array('class' => 'addbutton')
          );
        ?>
      </li>
    </ul>
  <?php endif; ?>

  <div class="table-container">
    <?php $tableCols = 2; ?>
    <table id="identifiers" class="common-table">
      <thead>
        <tr>
          <th><?php print _txt('fd.name'); ?></th>
          <th><?php print _txt('fd.type'); ?></th>
          <?php if($permissions['editids']
            && !empty($co_groups[0]['CoGroup']['id'])
            && !$co_groups[0]['CoGroup']['auto']
            && $permissions['edit']
          ): ?>
            <th class="thinActionButtonsCol"><?php print _txt('fd.actions'); ?></th>
            <?php $tableCols = 3; ?>
          <?php endif; ?>
        </tr>
      </thead>
      <tbody>
        <?php
          if(!empty($co_groups[0]['Identifier'])) {
            foreach($co_groups[0]['Identifier'] as $id) {
              print "<tr>";

              // Identifier
              print "<td>";
              if($permissions['editids']) {
                print $this->Html->link($id['identifier'],
                  array('controller' => 'identifiers',
                    'action' => $this->action,
                    $id['id']));
              } else {
                print filter_var($id['identifier'],FILTER_SANITIZE_SPECIAL_CHARS);
              }
              print "</td>";

              // Identifier Type
              print "<td>" . $vv_types['Identifier'][$id['type']] . "</td>";

              if($permissions['editids']
                && !empty($co_groups[0]['CoGroup']['id'])
                && !$co_groups[0]['CoGroup']['auto']
                && $permissions['edit']) {

                print '<td class="actions">';
                print $this->Html->link(_txt('op.edit'),
                                        array('controller' => 'identifiers',
                                              'action' => 'edit',
                                              $id['id']),
                                        array('class' => 'editbutton'));
              
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
            
                print "</td>";
              }
              print "</tr>";
            }
          } else {
            print '<tr><td colspan="' . $tableCols . '">' . _txt('in.co_group.ids.none') . '</td></tr>';
          }
        ?>
      </tbody>
    </table>
  </div>

  <div id="autogenerate-dialog" title="<?php print _txt('op.id.auto'); ?>">
    <?php print _txt('op.id.auto.confirm'); ?>
  </div>
<?php endif; // add ?>

<?php
  print $this->element("changelog");
