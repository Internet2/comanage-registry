<!--
/**
 * COmanage Registry CO Extended Types Fields
 *
 * Copyright (C) 2012 University Corporation for Advanced Internet Development, Inc.
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
 * @copyright     Copyright (C) 2012 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.6
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
    
  // Find the attribute
  if(isset($this->request->params['named']['attr'])) {
    $attr = $this->request->params['named']['attr'];
  } else {
    $attr = $this->request->data['CoExtendedType']['attribute'];
  }
    
  print $this->Form->hidden('co_id', array('default' => $cur_co['Co']['id'])) . "\n";
  print $this->Form->hidden('attribute', array('default' => Sanitize::html($attr))) . "\n";
?>
<table id="<?php print $this->action; ?>_co_extended_type" class="ui-widget">
  <tbody>
    <tr class="line1">
      <td>
        <?php print _txt('fd.attribute'); ?>
      </td>
      <td>
        <?php
          // Generate the localized version of the model name we're adding a type for
          print _txt('ct.' . Inflector::tableize($attr) . '.1');
        ?>
      </td>
    </tr>
    <tr class="line2">
      <td>
        <?php print _txt('fd.name'); ?><span class="required">*</span><br />
        <i><?php print _txt('fd.an.desc'); ?></i>
      </td>
      <td>
        <?php print ($e ? $this->Form->input('name', array('class' => 'focusFirst')) : Sanitize::html($co_extended_types[0]['CoExtendedType']['name'])); ?>
      </td>
    </tr>
    <tr class="line1">
      <td>
        <?php print _txt('fd.name.d'); ?><span class="required">*</span>
      </td>
      <td>
        <?php print ($e ? $this->Form->input('display_name') : Sanitize::html($co_extended_types[0]['CoExtendedType']['display_name'])); ?>
      </td>
    </tr>
    <tr class="line2">
      <td>
        <?php print _txt('fd.status'); ?><span class="required">*</span>
      </td>
      <td>
        <?php
          global $cm_lang, $cm_texts;
          $attrs['value'] = (isset($co_extended_types) ? $co_extended_types[0]['CoExtendedType']['status'] : StatusEnum::Active);
          $attrs['empty'] = false;
          
          if($e) {
            print $this->Form->select('status',
                                      $cm_texts[ $cm_lang ]['en.status'],
                                      $attrs);
            
            if($this->Form->isFieldError('status')) {
              print $this->Form->error('status');
            }
          } else {
            print _txt('en.status', null, $co_extended_types[0]['CoExtendedType']['status']);
          }
        ?>
      </td>
    </tr>
    <tr>
      <td>
        <i><span class="required"><?php print _txt('fd.req'); ?></span></i><br />
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
  </tbody>
</table>