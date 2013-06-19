<!--
/**
 * COmanage Registry CMP Enrollment Attribute Fields
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
    
  // Add buttons to sidebar
  $sidebarButtons = $this->get('sidebarButtons');

  $this->set('sidebarButtons', $sidebarButtons);
  
  print $this->Form->hidden('co_enrollment_flow_id', array('default' => Sanitize::html($this->request->params['named']['coef']))) . "\n";
?>
<table id="<?php print $this->action; ?>_co_enrollment_attribute" class="ui-widget">
  <tbody>
    <tr class="line1">
      <td>
        <b><?php print _txt('fd.ea.label'); ?></b><br />
        <font class="desc"><?php print _txt('fd.ea.label.desc'); ?></font>
      </td>
      <td>
        <?php print ($e
                     ? $this->Form->input('label', array('default' => _txt('fd.untitled')))
                     : Sanitize::html($co_enrollment_attributes[0]['CoEnrollmentAttribute']['label'])); ?>
      </td>
    </tr>
    <tr class="line2">
      <td>
        <b><?php print _txt('fd.ea.desc'); ?></b><br />
        <font class="desc"><?php print _txt('fd.ea.desc.desc'); ?></font>
      </td>
      <td>
        <?php print ($e
                     ? $this->Form->input('description')
                     : Sanitize::html($co_enrollment_attributes[0]['CoEnrollmentAttribute']['description'])); ?>
      </td>
    </tr>
    <tr class="line1">
      <td>
        <b><?php print _txt('fd.attribute'); ?></b>
      </td>
      <td>
        <?php
          $attrs['value'] = (isset($co_enrollment_attributes)
                              ? $co_enrollment_attributes[0]['CoEnrollmentAttribute']['attribute']
                              : null);
          $attrs['empty'] = false;
          
          if($e) {
            print $this->Form->select('attribute',
                                      $available_attributes,
                                      $attrs);
            
            if($this->Form->isFieldError('attribute')) {
              print $this->Form->error('status');
            }
          } else {
            print Sanitize::html($co_enrollment_flows[0]['CoEnrollmentAttribute']['attribute']);
          }
        ?>
      </td>
    </tr>
    <tr class="line2">
      <td>
        <b><?php print _txt('fd.required'); ?></b>
      </td>
      <td>
        <?php
          global $cm_lang, $cm_texts;
          $attrs['value'] = (isset($co_enrollment_attributes)
                                           ? $co_enrollment_attributes[0]['CoEnrollmentAttribute']['required']
                                           : RequiredEnum::Optional);
          $attrs['empty'] = false;
          
          if($e) {
            print $this->Form->select('required',
                                      $cm_texts[ $cm_lang ]['en.required'],
                                      $attrs);
            
            if($this->Form->isFieldError('required')) {
              print $this->Form->error('required');
            }
          } else {
            print _txt('en.required', null, $co_enrollment_attributes[0]['CoEnrollmentAttribute']['required']);
          }
        ?>
      </td>
    </tr>
    <tr class="line1">
      <td>
        <b><?php print _txt('fd.ea.order'); ?></b><br />
        <font class="desc"><?php print _txt('fd.ea.order.desc'); ?></font>
      </td>
      <td>
        <?php print ($e
                     ? $this->Form->input('ordr')
                     : Sanitize::html($co_enrollment_attributes[0]['CoEnrollmentAttribute']['ordr'])); ?>
      </td>
    </tr>
  </tbody>
</table>
<?php
  if($e) {
    print $this->Form->submit($submit_label, array('onClick' => 'on_submit()'));
    print $this->Form->button(_txt('op.reset'), 
                              array('type'=>'reset'));
  }
?>
