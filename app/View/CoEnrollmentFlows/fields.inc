<!--
/**
 * COmanage Registry CO Enrollment Configuration Fields
 *
 * Copyright (C) 2011-12 University Corporation for Advanced Internet Development, Inc.
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
 * @copyright     Copyright (C) 2011-12 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.3
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
    return(false);
    
  if($e)
  {
    echo $this->Html->link(_txt('op.cancel'),
                            array('controller' => 'co_enrollment_flows', 'action' => 'index', 'co' => $cur_co['Co']['id']),
                            array('class' => 'cancelbutton'));
    
    if($this->action == "edit" && $permissions['edit'])
      echo $this->Html->link(_txt('op.edit.ea'),
                              array('controller' => 'co_enrollment_attributes',
                                    'action' => 'index',
                                    'coef' => $co_enrollment_flows[0]['CoEnrollmentFlow']['id']),
                              array('class' => 'linkbutton'));
  }
  else
    echo $this->Html->link(_txt('op.back'),
                           array('controller' => 'co_enrollment_flows', 'action' => 'index'),
                           array('class' => 'backbutton'));
    
  echo '
    <br />
    <br />
    ';
  
  echo $this->Form->hidden('co_id', array('default' => $cur_co['Co']['id'])) . "\n";
?>
<table id="<?php print $this->action; ?>_co_enrollment_configuration" class="ui-widget">
  <tbody>
    <tr class="line1">
      <td>
        <b><?php print _txt('fd.name'); ?></b>
      </td>
      <td>
        <?php print ($e
                     ? $this->Form->input('name', array('default' => _txt('fd.untitled')))
                     : Sanitize::html($co_enrollment_flows[0]['CoEnrollmentFlow']['name'])); ?>
      </td>
    </tr>
    <tr class="line2">
      <td>
        <b><?php print _txt('fd.ef.se'); ?></b><br />
        <font class="desc"><?php print _txt('fd.ef.se.desc'); ?></font>
      </td>
      <td>
        <?php print ($e
                     ? $this->Form->input('self_enroll')
                     : Sanitize::html($co_enrollment_flows[0]['CoEnrollmentFlow']['self_enroll'])); ?>
      </td>
    </tr>
    <tr class="line1">
      <td>
        <b><?php print _txt('fd.ef.ae'); ?></b><br />
        <font class="desc"><?php print _txt('fd.ef.ae.desc'); ?></font>
      </td>
      <td>
        <?php
          global $cm_lang, $cm_texts;
          $attrs['value'] = (isset($co_enrollment_flows)
                              ? $co_enrollment_flows[0]['CoEnrollmentFlow']['admin_enroll']
                              : AdministratorEnum::NoAdmin);
          $attrs['empty'] = false;
          
          if($e) {
            print $this->Form->select('admin_enroll',
                                      $cm_texts[ $cm_lang ]['en.admin'],
                                      $attrs);
            
            if($this->Form->isFieldError('admin_enroll')) {
              print $this->Form->error('admin_enroll');
            }
          } else {
            print _txt('en.admin', null, $co_enrollment_flows[0]['CoEnrollmentFlow']['admin_enroll']);
          }
        ?>
      </td>
    </tr>
    <tr class="line2">
      <td>
        <b><?php print _txt('fd.ef.appr'); ?></b><br />
        <font class="desc"><?php print _txt('fd.ef.appr.desc'); ?></font>
      </td>
      <td>
        <?php print ($e
                     ? $this->Form->input('approval_required')
                     : Sanitize::html($co_enrollment_flows[0]['CoEnrollmentFlow']['approval_required'])); ?>
      </td>
    </tr>
    <tr class="line1">
      <td>
        <b><?php print _txt('fd.ef.epx'); ?></b><br />
        <font class="desc"><?php print _txt('fd.ef.epx.desc'); ?></font>
      </td>
      <td>
        <?php print ($e
                     ? $this->Form->input('early_provisioning_exec')
                     : Sanitize::html($co_enrollment_flows[0]['CoEnrollmentFlow']['early_provisioning_exec'])); ?>
      </td>
    </tr>
    <tr class="line2">
      <td>
        <b><?php print _txt('fd.ef.px'); ?></b><br />
        <font class="desc"><?php print _txt('fd.ef.px.desc'); ?></font>
      </td>
      <td>
        <?php print ($e
                     ? $this->Form->input('provisioning_exec')
                     : Sanitize::html($co_enrollment_flows[0]['CoEnrollmentFlow']['provisioning_exec'])); ?>
      </td>
    </tr>
    <tr class="line1">
      <td>
        <b><?php print _txt('fd.ef.noep'); ?></b><br />
        <font class="desc"><?php print _txt('fd.ef.noep.desc'); ?></font>
      </td>
      <td>
        <?php print ($e
                     ? $this->Form->input('notify_on_early_provision')
                     : Sanitize::html($co_enrollment_flows[0]['CoEnrollmentFlow']['notify_on_early_provision'])); ?>
      </td>
    </tr>
    <tr class="line2">
      <td>
        <b><?php print _txt('fd.ef.nop'); ?></b><br />
        <font class="desc"><?php print _txt('fd.ef.nop.desc'); ?></font>
      </td>
      <td>
        <?php print ($e
                     ? $this->Form->input('notify_on_provision')
                     : Sanitize::html($co_enrollment_flows[0]['CoEnrollmentFlow']['notify_on_provision'])); ?>
      </td>
    </tr>
    <tr class="line1">
      <td>
        <b><?php print _txt('fd.ef.noa'); ?></b><br />
        <font class="desc"><?php print _txt('fd.ef.noa.desc'); ?></font>
      </td>
      <td>
        <?php print ($e
                     ? $this->Form->input('notify_on_active')
                     : Sanitize::html($co_enrollment_flows[0]['CoEnrollmentFlow']['notify_on_active'])); ?>
      </td>
    </tr>
  </tbody>
</table>
<?php
  if($e)
    print $this->Form->submit($submit_label, array('onClick' => 'on_submit()'));
?>
