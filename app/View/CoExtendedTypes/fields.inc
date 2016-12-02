<?php
/**
 * COmanage Registry CO Extended Types Fields
 *
 * Copyright (C) 2012-14 University Corporation for Advanced Internet Development, Inc.
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
 * @copyright     Copyright (C) 2012-14 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.6
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
  $args['controller'] = 'co_extended_types';
  $args['action'] = 'index';
  $args['co'] = $cur_co['Co']['id'];
  $this->Html->addCrumb(_txt('ct.co_extended_types.pl'), $args);
  $crumbTxt = _txt('op.' . $this->action . '-a', array(_txt('ct.co_extended_types.1')));
  $this->Html->addCrumb($crumbTxt);
    
  // Find the attribute
  if($this->action == 'add' && isset($this->request->query['attr'])) {
    $attr = filter_var($this->request->query['attr'],FILTER_SANITIZE_FULL_SPECIAL_CHARS);
  } elseif(isset($this->request->data['CoExtendedType']['attribute'])) {
    $attr = $this->request->data['CoExtendedType']['attribute'];
  }
    
  print $this->Form->hidden('co_id', array('default' => $cur_co['Co']['id'])) . "\n";
  print $this->Form->hidden('attribute', array('default' => $attr)) . "\n";
  
  $l = 1;
?>
<table id="<?php print $this->action; ?>_co_extended_type" class="ui-widget">
  <tbody>
    <tr class="line<?php print ($l % 2); $l++; ?>">
      <td>
        <?php print _txt('fd.attribute'); ?>
      </td>
      <td>
        <?php
          if(isset($vv_supported_attrs[ $attr ])) {
            print $vv_supported_attrs[ $attr ];
          } else {
            print $attr;
          }
        ?>
      </td>
    </tr>
    <tr class="line<?php print ($l % 2); $l++; ?>">
      <td>
        <?php print _txt('fd.name'); ?><span class="required">*</span><br />
        <em><?php print _txt('fd.an.desc'); ?></em>
      </td>
      <td>
        <?php print ($e ? $this->Form->input('name', array('class' => 'focusFirst')) : filter_var($co_extended_types[0]['CoExtendedType']['name'],FILTER_SANITIZE_FULL_SPECIAL_CHARS)); ?>
      </td>
    </tr>
    <tr class="line<?php print ($l % 2); $l++; ?>">
      <td>
        <?php print _txt('fd.name.d'); ?><span class="required">*</span>
      </td>
      <td>
        <?php print ($e ? $this->Form->input('display_name') : filter_var($co_extended_types[0]['CoExtendedType']['display_name'],FILTER_SANITIZE_FULL_SPECIAL_CHARS)); ?>
      </td>
    </tr>
    <tr class="line<?php print ($l % 2); $l++; ?>">
      <td>
        <?php print _txt('fd.status'); ?><span class="required">*</span><br />
        <em><?php print _txt('fd.status.et.desc'); ?></em>
      </td>
      <td>
        <?php
          global $cm_lang, $cm_texts;
          $attrs = array();
          $attrs['value'] = (isset($co_extended_types) ? $co_extended_types[0]['CoExtendedType']['status'] : SuspendableStatusEnum::Active);
          $attrs['empty'] = false;
          if($vv_type_in_use) {
            // A type in use cannot be suspended (see controller for more details)
            $attrs['disabled'] = true;
            
            print $this->Form->hidden('status', array('default' => true)) . "\n";
          }
          
          if($e) {
            print $this->Form->select('status',
                                      $cm_texts[ $cm_lang ]['en.status.susp'],
                                      $attrs);
            
            if($this->Form->isFieldError('status')) {
              print $this->Form->error('status');
            }
          } else {
            print _txt('en.status.susp', null, $co_extended_types[0]['CoExtendedType']['status']);
          }
        ?>
      </td>
    </tr>
    <?php if($attr == 'CoPersonRole.affiliation'): // Affiliations can map to eduPersonAffiliations ?>
    <tr class="line<?php print ($l % 2); $l++; ?>">
      <td>
        <?php print _txt('fd.affiliation.ep'); ?><br />
        <em><?php print _txt('fd.affiliation.ep.map.desc'); ?></em>
      </td>
      <td>
        <?php
          global $cm_lang, $cm_texts;
          $attrs = array();
          $attrs['value'] = (isset($co_extended_types) ? $co_extended_types[0]['CoExtendedType']['edupersonaffiliation'] : "");
          $attrs['empty'] = true;
          
          if($e) {
            print $this->Form->select('edupersonaffiliation',
                                      $cm_texts[ $cm_lang ]['en.co_person_role.affiliation'],
                                      $attrs);
            
            if($this->Form->isFieldError('edupersonaffiliation')) {
              print $this->Form->error('edupersonaffiliation');
            }
          } else {
            print _txt('en.co_person_role.affiliation', null, $co_extended_types[0]['CoExtendedType']['edupersonaffiliation']);
          }
        ?>
      </td>
    </tr>
    <?php endif; // affiliation ?>
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