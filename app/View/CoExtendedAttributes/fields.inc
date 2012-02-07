<!--
/**
 * COmanage Registry CO Extended Attributes Fields
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
 * @since         COmanage Registry v0.1
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
    echo $this->Html->link(_txt('op.cancel'),
                            array('controller' => 'co_extended_attributes', 'action' => 'index', 'co' => $this->request->params['named']['co']),
                            array('class' => 'cancelbutton'));
  else
    echo $this->Html->link(_txt('op.back'),
                           array('controller' => 'co_extended_attributes', 'action' => 'index', 'co' => $this->request->params['named']['co']),
                           array('class' => 'backbutton'));
    
  echo '
    <br />
    <br />
    ';

  if(isset($this->request->params['named']['co']))
    echo $this->Form->hidden('co_id', array('default' => Sanitize::html($this->request->params['named']['co']))) . "\n";
?>
<table id="<?php echo $this->action; ?>_co" class="ui-widget">
  <tbody>
    <tr class="line1">
      <td>
        <?php echo _txt('fd.name'); ?><font class="required">*</font><br />
        <i><?php echo _txt('fd.an.desc'); ?></i>
      </td>
      <td>
        <?php echo ($e ? $this->Form->input('name') : Sanitize::html($co_extended_attributes[0]['CoExtendedAttribute']['name'])); ?>
      </td>
    </tr>
    <tr class="line2">
      <td>
        <?php echo _txt('fd.name.d'); ?><font class="required">*</font>
      </td>
      <td>
        <?php echo ($e ? $this->Form->input('display_name') : Sanitize::html($co_extended_attributes[0]['CoExtendedAttribute']['display_name'])); ?>
      </td>
    </tr>
    <tr class="line1">
      <td>
        <?php echo _txt('fd.type'); ?><font class="required">*</font>
      </td>
      <td>
        <?php
          global $cm_lang, $cm_texts;
          $attrs['value'] = (isset($co_extended_attributes) ? $co_extended_attributes[0]['CoExtendedAttribute']['type'] : "");
          $attrs['empty'] = false;
          
          if($e && $this->action=='add') {
            print $this->Form->select('type',
                                      $cm_texts[ $cm_lang ]['en.extattr'],
                                      $attrs);
            
            if($this->Form->isFieldError('type')) {
              print $this->Form->error('type');
            }
          } else {
            print _txt('en.extattr', null, $co_extended_attributes[0]['CoExtendedAttribute']['type']);
          }
          
          // Emit data type to make validation work
          if($e && $this->action=='edit')
            echo $this->Form->hidden('type');
        ?>
      </td>
      <td>
        <span class="ui-icon ui-icon-info" style="float: left; margin-right: .3em;"></span>
        <i><?php echo _txt('fd.type.warn'); ?></i>
      </td>
    </tr>
    <tr class="line2">
      <td>
        <?php echo _txt('fd.index'); ?>
      </td>
      <td>
        <?php echo ($e ? $this->Form->input('indx') : ($co_extended_attributes[0]['CoExtendedAttribute']['type'] ? _txt('fd.yes') : _txt('fd.no'))); ?>
      </td>
    </tr>
    <tr>
      <td>
        <i><font class="required"><?php echo _txt('fd.req'); ?></font></i><br />
      </td>
      <td>
        <?php
          if($e)
            echo $this->Form->submit($submit_label);
        ?>
      </td>
    </tr>
  </tbody>
</table>