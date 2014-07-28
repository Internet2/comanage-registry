<!--
/**
 * COmanage Registry Names Fields
 *
 * Copyright (C) 2013-14 University Corporation for Advanced Internet Development, Inc.
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
 * @copyright     Copyright (C) 2013-14 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.8.3
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
  
  // Migrate the target person from a named argument to a form field
  if(isset($this->request->params['named']['copersonid']))
    echo $this->Form->hidden('co_person_id', array('default' => Sanitize::html($this->request->params['named']['copersonid']))) . "\n";
  elseif(isset($this->request->data['Name']['co_person_id']))
    echo $this->Form->hidden('co_person_id', array('default' => Sanitize::html($this->request->data['Name']['co_person_id']))) . "\n";
  elseif(isset($this->request->params['named']['orgidentityid']))
    echo $this->Form->hidden('org_identity_id', array('default' => Sanitize::html($this->request->params['named']['orgidentityid']))) . "\n";
  elseif(isset($this->request->data['Name']['org_identity_id']))
    echo $this->Form->hidden('org_identity_id', array('default' => Sanitize::html($this->request->data['Name']['org_identity_id']))) . "\n";
    
  if(isset($cur_co))
    print $this->Form->hidden('co_id', array('default' => $cur_co['Co']['id'])) . "\n";
?>
<table id="<?php echo $this->action; ?>_name" class="ui-widget">
  <tbody>
    <?php $l = 1; ?>
    <tr class="line<?php print ($l % 2); $l++; ?>">
      <td>
        <?php
        print _txt('fd.name.honorific');
        
        if($e)
          print " " . _txt('fd.name.h.desc');
        ?>
      </td>
      <td>
        <?php
          // Carry the current status of primary_name, but to change it requires a different operation
          print $this->Form->hidden('Name.primary_name');
          print ($e ? $this->Form->input('Name.honorific') : Sanitize::html($names[0]['Name']['honorific']));
        ?>
       </td>
    </tr>
    <tr class="line<?php print ($l % 2); $l++; ?>">
      <td>
        <?php print _txt('fd.name.given'); ?>
        <?php if(in_array('given', $required_fields)): ?>
        <span class="required">*</span>
        <?php endif; // required_fields ?>
      </td>
      <td>
        <?php print ($e ? $this->Form->input('Name.given', array('class' => 'focusFirst')) : Sanitize::html($names[0]['Name']['given'])); ?>
      </td>
    </tr>
    <tr class="line<?php print ($l % 2); $l++; ?>">
      <td>
        <?php print _txt('fd.name.middle'); ?>
        <?php if(in_array('middle', $required_fields)): ?>
        <span class="required">*</span>
        <?php endif; // required_fields ?>
      </td>
      <td>
        <?php print ($e ? $this->Form->input('Name.middle') : Sanitize::html($names[0]['Name']['middle'])); ?>
      </td>
    <tr class="line<?php print ($l % 2); $l++; ?>">
      <td>
        <?php print _txt('fd.name.family'); ?>
        <?php if(in_array('family', $required_fields)): ?>
        <span class="required">*</span>
        <?php endif; // required_fields ?>
      </td>
      <td>
        <?php print ($e ? $this->Form->input('Name.family') : Sanitize::html($names[0]['Name']['family'])); ?>
      </td>
    </tr>
    <tr class="line<?php print ($l % 2); $l++; ?>">
      <td>
        <?php
          print _txt('fd.name.suffix');
          
          if($e)
            print " " . _txt('fd.name.s.desc');
        ?>
      </td>
      <td>
        <?php echo ($e ? $this->Form->input('Name.suffix') : Sanitize::html($names[0]['Name']['suffix'])); ?>
      </td>
    </tr>
    <tr class="line<?php print ($l % 2); $l++; ?>">
      <td>
        <?php print _txt('fd.type'); ?>
      </td>
      <td>
        <?php
          $attrs = array();
          $attrs['value'] = (isset($names[0]['Name']['type'])
                             ? $names[0]['Name']['type']
                             : NameEnum::Alternate);
          $attrs['empty'] = false;
          
          if($e) {
            print $this->Form->select('Name.type',
                                      $vv_available_types,
                                      $attrs);
            
            if($this->Form->isFieldError('Name.type')) {
              print $this->Form->error('Name.type');
            }
          } else {
            print _txt('en.name', null, $names[0]['Name']['type']);
          }
        ?>
      </td>
    </tr>
    <tr class="line<?php print ($l % 2); $l++; ?>">
      <td>
        <?php print _txt('fd.language'); ?>
      </td>
      <td>
        <?php
          global $cm_lang, $cm_texts;
          
          $attrs = array();
          $attrs['value'] = (isset($names[0]['Name']['language'])
                             ? $names[0]['Name']['language']
                             : getPreferredLanguage());
          $attrs['empty'] = true;
          
          if($e) {
            print $this->Form->select('Name.language',
                                      $cm_texts[ $cm_lang ]['en.language'],
                                      $attrs);
            
            if($this->Form->isFieldError('Name.language')) {
              print $this->Form->error('Name.language');
            }
          } else {
            print _txt('en.language', null, $names[0]['Name']['language']);
          }
        ?>
      </td>
    </tr>
    <tr>
      <td>
        <i><span class="required"><?php echo _txt('fd.req'); ?></span></i><br />
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
