<?php
/**
 * COmanage Registry Attribute Enumeration Fields
 *
 * Copyright (C) 2016 SCG
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
 * @copyright     Copyright (C) 2016 SCG
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v1.1.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */

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
  $args['controller'] = 'attribute_enumerations';
  $args['action'] = 'index';
  if(!empty($cur_co['Co']['id'])) {
    $args['co'] = $cur_co['Co']['id'];
  }
  $this->Html->addCrumb(_txt('ct.attribute_enumerations.pl'), $args);
  $crumbTxt = _txt('op.' . $this->action . '-a', array(_txt('ct.attribute_enumerations.1')));
  $this->Html->addCrumb($crumbTxt);
  
  if(!empty($cur_co['Co']['id'])) {
    print $this->Form->hidden('co_id', array('default' => $cur_co['Co']['id'])) . "\n";
  }
  
  $l = 1;
?>
<table id="<?php print $this->action; ?>_attribute_enumeration" class="ui-widget">
  <tbody>
    <tr class="line<?php print ($l % 2); $l++; ?>">
      <td>
        <?php print _txt('fd.attribute'); ?><span class="required">*</span><br />
      </td>
      <td>
        <?php
          if($e) {
            $attrs = array();
            $attrs['value'] = (isset($attribute_enumerations)
                               ? $attribute_enumerations[0]['AttributeEnumeration']['attribute']
                               : null);
            $attrs['empty'] = false;
            
            print $this->Form->select('attribute',
                                      $vv_supported_attrs,
                                      $attrs);
            
            if($this->Form->isFieldError('attribute')) {
              print $this->Form->error('attribute');
            }
          } else {
            if(!empty($attribute_enumerations[0]['AttributeEnumeration']['attribute'])) {
              print $attribute_enumerations[0]['AttributeEnumeration']['attribute'];
            }
          }
        ?>
      </td>
    </tr>
    <tr class="line<?php print ($l % 2); $l++; ?>">
      <td>
        <?php print _txt('fd.value'); ?><br />
        <em><?php print _txt('fd.ae.optvalue.desc'); ?></em>
      </td>
      <td>
        <?php print ($e ?
                     $this->Form->input('optvalue', array('class' => 'focusFirst', 'size' => 60))
                     : filter_var($attribute_enumerations[0]['AttributeEnumeration']['name'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
      </td>
    </tr>
    <tr class="line<?php print ($l % 2); $l++; ?>">
      <td>
        <?php print _txt('fd.status'); ?><span class="required">*</span><br />
        <em><?php print _txt('fd.status.ae.desc'); ?></em>
      </td>
      <td>
        <?php
          global $cm_lang, $cm_texts;
          $attrs = array();
          $attrs['value'] = (isset($attribute_enumerations)
                             ? $attribute_enumerations[0]['AttributeEnumeration']['status']
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
            print _txt('en.status.susp', null, $attribute_enumerations[0]['AttributeEnumeration']['status']);
          }
        ?>
      </td>
    </tr>
    <tr>
      <td>
        <em><span class="required"><?php print _txt('fd.req'); ?></span></em><br />
      </td>
      <td>
        <?php
          if($e) {
            print $this->Form->submit($submit_label);
            print $this->Form->button(_txt('op.reset'), 
                                      array('type'=>'reset'));
          }
        ?>
      </td>
    </tr>
  </tbody>
</table>
<?php
  print $this->element("changelog");
