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
  
  $args = array();
  $args['plugin'] = null;
  $args['controller'] = 'co_groups';
  $args['action'] = 'edit';
  $args[] = $this->request->data['CoGroupMember']['co_group_id'];
  $this->Html->addCrumb(_txt('ct.co_groups.1'), $args);
  
  $args = array();
  $args['plugin'] = null;
  $args['controller'] = 'co_people';
  $args['action'] = 'canvas';
  $args[] = $this->request->data['CoGroupMember']['co_person_id'];
  $this->Html->addCrumb(_txt('ct.co_people.1'), $args);
  
  $this->Html->addCrumb($title_for_layout);

  if($e)
  {
    // Populate the references
    print $this->Form->hidden('co_id', array('default' => $cur_co['Co']['id'])). "\n";
    print $this->Form->hidden('id', array('default' => $this->request->data['CoGroupMember']['id'])). "\n";
    print $this->Form->hidden('co_group_id', array('default' => $this->request->data['CoGroupMember']['co_group_id'])). "\n";
    print $this->Form->hidden('co_person_id', array('default' => $this->request->data['CoGroupMember']['co_person_id'])). "\n";
  }
?>
  <script type="text/javascript">
    $(function() {
      validate_date_input('<?php print _txt('er.validation') ?>', '<?php print _txt('er.validation.date') ?>');
    });
  </script>

<ul id="<?php print $this->action; ?>_co_group_member" class="fields form-list">
  <li>
    <div class="field-name">
      <?php print $this->Form->label('member',_txt('fd.group.mem')); ?>
    </div>
    <div class="field-info">
          <?php print ($e
                       ? $this->Form->input('member')
                       : ($co_group_members[0]['CoGroupMember']['member'] ? _txt('fd.yes') : _txt('fd.no'))); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <?php print $this->Form->label('owner',_txt('fd.group.own')); ?>
    </div>
    <div class="field-info">
          <?php print ($e
                       ? $this->Form->input('owner')
                       : ($co_group_members[0]['CoGroupMember']['owner'] ? _txt('fd.yes') : _txt('fd.no'))); ?>
    </div>
  </li>
  <li class="modelbox-data">
    <div class="field-name">
      <?php
        if($e) {
          print $this->Form->label('valid_from', _txt('fd.valid_from.tz', array($vv_tz)));
        } else {
          print _txt('fd.valid_from');
        }
      ?>
      <div class="field-desc">
        <?php print _txt('fd.valid_from.desc'); ?>
      </div>
    </div>
    <div class="field-info">
      <?php
        if($e) {
          $args = array(
            'class' => 'datepicker-f'
          );

          if(isset($co_group_members[0]['CoGroupMember']['valid_from'])
            && $co_group_members[0]['CoGroupMember']['valid_from'] > 0) {
            if(!empty($vv_tz)) {
              // We need to adjust the UTC value to the user's local time
              $args['value'] = $this->Time->format($co_group_members[0]['CoGroupMember']['valid_from'], "%F %T", false, $vv_tz);
            } else {
              $args['value'] = $co_group_members[0]['CoGroupMember']['valid_from'];
            }
          }

          print $this->Form->text('valid_from', $args);
        } else {
          if(isset($co_group_members[0]['CoGroupMember']['valid_from'])
            && $co_group_members[0]['CoGroupMember']['valid_from'] > 0) {
            print $this->Time->format($co_group_members[0]['CoGroupMember']['valid_from'], "%c $vv_tz", false, $vv_tz);
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
      ?>
      <div class="field-desc">
        <?php print _txt('fd.valid_through.desc'); ?>
      </div>
    </div>
    <div class="field-info">
      <?php
        if($e) {
          $args = array(
            'class' => 'datepicker-u'
          );

          if(isset($co_group_members[0]['CoGroupMember']['valid_through'])
            && $co_group_members[0]['CoGroupMember']['valid_through'] > 0) {
            if(!empty($vv_tz)) {
              // We need to adjust the UTC value to the user's local time
              $args['value'] = $this->Time->format($co_group_members[0]['CoGroupMember']['valid_through'], "%F %T", false, $vv_tz);
            } else {
              $args['value'] = $co_group_members[0]['CoGroupMember']['valid_through'];
            }
          }

          print $this->Form->text('valid_through', $args);
        } else {
          if(isset($co_group_members[0]['CoGroupMember']['valid_through'])
            && $co_group_members[0]['CoGroupMember']['valid_through'] > 0) {
            print $this->Time->format($co_group_members[0]['CoGroupMember']['valid_through'], "%c $vv_tz", false, $vv_tz);
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
