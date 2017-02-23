<?php
/**
 * COmanage Registry CO Service Fields
 *
 * Copyright (C) 2016-17 SURFnet BV
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
 * @copyright     Copyright (C) 2016-17 SURFnet BV
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v1.1.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */
?>
<?php
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
  $args['controller'] = 'co_services';
  $args['action'] = 'index';
  $args['co'] = $cur_co['Co']['id'];
  $this->Html->addCrumb(_txt('ct.co_services.pl'), $args);
  $crumbTxt = _txt('op.' . $this->action . '-a', array(_txt('ct.co_services.1')));
  $this->Html->addCrumb($crumbTxt);

  print $this->Form->hidden('co_id', array('default' => $cur_co['Co']['id'])) . "\n";

  $l = 1;
?>
<table id="<?php print $this->action; ?>_co_services" class="ui-widget">
  <tbody>
    <tr class="line<?php print ($l % 2); $l++; ?>">
      <td>
        <strong class="fieldTitle">
          <?php print ($e ? $this->Form->label('name', _txt('fd.name')) : _txt('fd.name')); ?>
        </strong><span class="required">*</span>
      </td>
      <td>
        <?php print ($e
                     ? $this->Form->input('name', array('class' => 'focusFirst'))
                     : filter_var($co_services[0]['CoService']['name'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
      </td>
    </tr>
    <tr class="line<?php print ($l % 2); $l++; ?>">
      <td>
        <strong class="fieldTitle">
          <?php print ($e ? $this->Form->label('description', _txt('fd.desc')) : _txt('fd.desc')); ?>
        </strong>
      </td>
      <td>
        <?php print ($e
                     ? $this->Form->input('description', array('class' => 'focusFirst'))
                     : filter_var($co_services[0]['CoService']['description'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
      </td>
    </tr>
    <tr class="line<?php print ($l % 2); $l++; ?>">
      <td>
        <strong class="fieldTitle">
          <?php print ($e ? $this->Form->label('status', _txt('fd.status')) : _txt('fd.status')); ?>
        </strong>
        <span class="required">*</span>
      </td>
      <td>
        <?php
          global $cm_lang, $cm_texts;
          $attrs = array();
          $attrs['value'] = (isset($co_services[0]['CoService']['status'])
                             ? $co_services[0]['CoService']['status']
                             : SuspendableStatusEnum::Active);
          $attrs['empty'] = false;

          if($e) {
            print $this->Form->select('status',
                                      $cm_texts[ $cm_lang ]['en.status.susp'],
                                      $attrs);

            if($this->Form->isFieldError('status')) {
              print $this->Form->error('status');
            }
          } else {
            print _txt('en.status', null, $co_services[0]['CoService']['status']);
          }
        ?>
      </td>
    </tr>
    <tr class="line<?php print ($l % 2); $l++; ?>">
      <td>
        <strong class="fieldTitle">
          <?php print ($e ? $this->Form->label('visibility', _txt('fd.visibility')) : _txt('fd.visibility')); ?>
        </strong>
        <span class="required">*</span><br />
        <span class="descr"><?php print _txt('fd.svc.vis.desc'); ?></span>
      </td>
      <td>
        <?php
          global $cm_lang, $cm_texts;
          $attrs = array();
          $attrs['value'] = (isset($co_services[0]['CoService']['visibility'])
                             ? $co_services[0]['CoService']['visibility']
                             : VisibilityEnum::CoMember);
          $attrs['empty'] = false;

          if($e) {
            print $this->Form->select('visibility',
                                      $cm_texts[ $cm_lang ]['en.visibility'],
                                      $attrs);

            if($this->Form->isFieldError('visibility')) {
              print $this->Form->error('visibility');
            }
          } else {
            print _txt('en.visibility', null, $co_services[0]['CoService']['visibility']);
          }
        ?>
      </td>
    </tr>
    <tr class="line<?php print ($l % 2); $l++; ?>">
      <td>
        <strong class="fieldTitle">
          <?php print ($e ? $this->Form->label('co_group_id', _txt('fd.svc.group')) : _txt('fd.svc.group')); ?>
        </strong><br />
        <span class="descr"><?php print _txt('fd.svc.group.desc'); ?></span>
      </td>
      <td>
        <?php
          $attrs = array();
          $attrs['value'] = (isset($co_services[0]['CoService']['co_group_id'])
                             ? $co_services[0]['CoService']['co_group_id']
                             : 0);
          $attrs['empty'] = true;
          
          if($e) {
            print $this->Form->select('co_group_id',
                                      $vv_co_groups,
                                      $attrs);
            
            if($this->Form->isFieldError('co_group_id')) {
              print $this->Form->error('co_group_id');
            }
          } else {
            if(!empty( $co_services[0]['CoService']['co_group_id'])) {
              print filter_var($vv_co_groups[  $co_services[0]['CoService']['co_group_id'] ],FILTER_SANITIZE_SPECIAL_CHARS);
            }
          }
        ?>
      </td>
    </tr>
    <tr class="line<?php print ($l % 2); $l++; ?>">
      <td>
        <strong class="fieldTitle">
          <?php print ($e ? $this->Form->label('service_url', _txt('fd.svc.url')) : _txt('fd.svc.url')); ?>
        </strong><br />
        <span class="descr"><?php print _txt('fd.svc.url.desc'); ?></span>
      </td>
      <td>
        <?php print ($e
                     ? $this->Form->input('service_url')
                     : filter_var($co_services[0]['CoService']['service_url'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
      </td>
    </tr>
    <tr class="line<?php print ($l % 2); $l++; ?>">
      <td>
        <strong class="fieldTitle">
          <?php print ($e ? $this->Form->label('contact_email', _txt('fd.svc.mail')) : _txt('fd.svc.mail')); ?>
        </strong><br />
        <span class="descr"><?php print _txt('fd.svc.mail.desc'); ?></span>
      </td>
      <td>
        <?php print ($e
                     ? $this->Form->input('contact_email')
                     : filter_var($co_services[0]['CoService']['contact_email'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
      </td>
    </tr>
    <tr class="line<?php print ($l % 2); $l++; ?>">
      <td>
        <strong class="fieldTitle">
          <?php print ($e ? $this->Form->label('entitlement_uri', _txt('fd.svc.euri')) : _txt('fd.svc.euri')); ?>
        </strong><br />
        <span class="descr"><?php print _txt('fd.svc.euri.desc'); ?></span>
      </td>
      <td>
        <?php print ($e
                     ? $this->Form->input('entitlement_uri')
                     : filter_var($co_services[0]['CoService']['entitlement_uri'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
      </td>
    </tr>
    
    <?php
    if($e) {
      print '<tr><td></td><td>';
      print $this->Form->submit($submit_label, array('onClick' => 'on_submit()'));
      print $this->Form->button(_txt('op.reset'), array('type'=>'reset'));
      print '</td></tr>';
    }
    ?>
  </tbody>
</table>
<?php
  print $this->element("changelog");
  