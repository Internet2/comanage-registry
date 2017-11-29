<?php
/**
 * COmanage Registry CO Email List Fields
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
 * @since         COmanage Registry v3.1.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

  // Determine if fields are editable
  $e = false;

  if(($this->action == "add" && $permissions['add'])
     || ($this->action == "edit" && $permissions['edit'])) {
    $e = true;
  }
  
  if(!$e)
    return false;

  // Add breadcrumbs
  print $this->element("coCrumb");
  $args = array();
  $args['plugin'] = null;
  $args['controller'] = 'co_email_lists';
  $args['action'] = 'index';
  $args['co'] = $cur_co['Co']['id'];
  $this->Html->addCrumb(_txt('ct.co_email_lists.pl'), $args);
  $crumbTxt = _txt('op.' . $this->action . '-a', array(_txt('ct.co_email_lists.1')));
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
<table id="<?php print $this->action; ?>_co_email_list" class="ui-widget">
  <tbody>
    <tr class="line<?php print ($l%2); $l++; ?>">
      <td>
        <strong class="fieldTitle">
          <?php print ($e ? $this->Form->label('name', _txt('fd.name')) : _txt('fd.name')); ?>
        </strong><span class="required">*</span><br />
        <span class="descr"><?php print _txt('fd.el.name.desc'); ?></span>
      </td>
      <td>
        <?php print ($e ? $this->Form->input('name', array('class' => 'focusFirst')) : filter_var($co_email_lists[0]['CoEmailList']['name'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
      </td>
    </tr>
    <tr class="line<?php print ($l%2); $l++; ?>">
      <td>
        <strong class="fieldTitle">
          <?php print ($e ? $this->Form->label('description', _txt('fd.desc')) : _txt('fd.desc')); ?>
        </strong>
      </td>
      <td>
        <?php print ($e ? $this->Form->input('description') : filter_var($co_email_lists[0]['CoEmailList']['description'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
      </td>
    </tr>
    <tr class="line<?php print ($l%2); $l++; ?>">
      <td>
        <strong class="fieldTitle">
          <?php print ($e ? $this->Form->label('status', _txt('fd.status')) : _txt('fd.status')); ?>
        </strong>
      </td>
      <td>
        <?php
          global $cm_lang, $cm_texts;
          $attrs['value'] = (!empty($co_email_lists[0]['CoEmailList']) ? $co_email_lists[0]['CoEmailList']['status'] : SuspendableStatusEnum::Active);
          $attrs['empty'] = false;

          if($e) {
            print $this->Form->select('status',
                                      $cm_texts[ $cm_lang ]['en.status.susp'],
                                      $attrs);
            
            if($this->Form->isFieldError('status')) {
              print $this->Form->error('status');
            }
          } else {
            print _txt('en.status', null, $co_email_lists[0]['CoEmailList']['status']);
          }
        ?>
      </td>
    </tr>
    <?php foreach(array('members', 'admins', 'moderators') as $gt): ?>
    <tr class="line<?php print ($l%2); $l++; ?>">
      <td>
        <strong class="fieldTitle">
          <?php print ($e ? $this->Form->label($gt.'_co_group_id', _txt('fd.el.gr.'.$gt)) : _txt('fd.el.gr.'.$gt)); ?>
        </strong><br />
        <span class="descr"><?php print _txt('fd.el.gr.'.$gt.'.desc'); ?></span>
      </td>
      <td>
        <?php
          if(!empty($vv_co_groups)) {
            $attrs = array();
            $attrs['value'] = (!empty($co_email_lists[0]['CoEmailList'])
                               ? $co_email_lists[0]['CoEmailList'][$gt.'_co_group_id']
                               : null);
            $attrs['empty'] = true;

            if($e) {
              print $this->Form->select($gt.'_co_group_id',
                                        $vv_co_groups,
                                        $attrs);

              if($this->Form->isFieldError($gt.'_co_group_id')) {
                print $this->Form->error($gt.'_co_group_id');
              }
              
              // Render a direct link to the group.
              if(!empty($co_email_lists[0]['CoEmailList'][$gt.'_co_group_id'])) {
                print $this->Html->link(_txt('op.view-a', array($vv_co_groups[ $co_email_lists[0]['CoEmailList'][$gt.'_co_group_id'] ])),
                                        array(
                                          'controller' => 'co_groups',
                                          'action'     => 'edit',
                                          $co_email_lists[0]['CoEmailList'][$gt.'_co_group_id']
                                        ),
                                        array('class' => 'viewbutton'));
              }
            } else {
              if(!empty($co_email_lists[0]['CoEmailList'][$gt.'_co_group_id'])) {
                print $vv_co_groups[ $co_email_lists[0]['CoEmailList'][$gt.'_co_group_id'] ];
              }
            }
          }
        ?>
      </td>
    </tr>
    <?php endforeach; // $gt ?>
    <tr>
      <td>
        <em><span class="required"><?php print _txt('fd.req'); ?></span></em><br />
      </td>
      <td>
        <?php
          if($e) {
            print $this->Form->submit($submit_label);
          }
        ?>
      </td>

    </tr>
  </tbody>
</table>
<?php
  print $this->element("changelog");
