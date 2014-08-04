<!--
/**
 * COmanage Registry CO Settings Fields
 *
 * Copyright (C) 2014 University Corporation for Advanced Internet Development, Inc.
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
 * @copyright     Copyright (C) 2014 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.9.1
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
  
  print $this->Form->hidden('co_id', array('default' => $cur_co['Co']['id'])) . "\n";
  
  $l = 1;
?>
<table id="<?php print $this->action; ?>_co_settings" class="ui-widget">
  <tbody>
    <tr class="line<?php print ($l % 2); $l++; ?>">
      <td>
        <strong class="fieldTitle"><?php print _txt('fd.de.enable'); ?></strong>
      </td>
      <td>
        <?php print ($e
                     ? $this->Form->input('enable_nsf_demo')
                     : (isset($co_settings[0]['CoSetting']['enable_nsf_demo'])
                        && $co_settings[0]['CoSetting']['enable_nsf_demo']
                        ? _txt('fd.yes') : _txt('fd.no')));
        ?>
    </tr>
    <tr class="line<?php print ($l % 2); $l++; ?>">
      <td>
        <strong class="fieldTitle"><?php print _txt('fd.ef.invval'); ?></strong><br />
        <span class="descr"><?php print _txt('fd.ef.invval.desc'); ?></span>
      </td>
      <td>
        <?php global $def_inv_validity;
              print ($e
                     ? $this->Form->input('invitation_validity',
                                          array('default' => $def_inv_validity))
                     : Sanitize::html($co_settings[0]['CoSetting']['invitation_validity'])); ?>
      </td>
    </tr>
    <tr class="line<?php print ($l % 2); $l++; ?>">
      <td>
        <strong class="fieldTitle"><?php print _txt('fd.required.addr'); ?></strong>
      </td>
      <td>
        <?php
          global $cm_lang, $cm_texts;
          
          $attrs = array();
          $attrs['value'] = (isset($co_settings[0]['CoSetting']['required_fields_addr'])
                             ? $co_settings[0]['CoSetting']['required_fields_addr']
                             : "");
          $attrs['empty'] = false;
          
          if($e) {
            print $this->Form->select('required_fields_addr',
                                      $cm_texts[ $cm_lang ]['en.required.address'],
                                      $attrs);
            
            if($this->Form->isFieldError('required_fields_addr')) {
              print $this->Form->error('required_fields_addr');
            }
          } else {
            print _txt('en.required.address', null, $co_settings[0]['CoSetting']['required_fields_addr']);
          }
        ?>
      </td>
    </tr>
    <tr class="line<?php print ($l % 2); $l++; ?>">
      <td>
        <strong class="fieldTitle"><?php print _txt('fd.required.name'); ?></strong>
      </td>
      <td>
        <?php
          global $cm_lang, $cm_texts;
          
          $attrs = array();
          $attrs['value'] = (isset($co_settings[0]['CoSetting']['required_fields_name'])
                             ? $co_settings[0]['CoSetting']['required_fields_name']
                             : "");
          $attrs['empty'] = false;
          
          if($e) {
            print $this->Form->select('required_fields_name',
                                      $cm_texts[ $cm_lang ]['en.required.name'],
                                      $attrs);
            
            if($this->Form->isFieldError('required_fields_name')) {
              print $this->Form->error('required_fields_name');
            }
          } else {
            print _txt('en.required.name', null, $co_settings[0]['CoSetting']['required_fields_name']);
          }
        ?>
      </td>
    </tr>
    <tr class="line<?php print ($l % 2); $l++; ?>">
      <td>
        <strong class="fieldTitle"><?php print _txt('fd.tc.mode.login'); ?></strong><br />
        <span class="descr"><?php print _txt('fd.tc.mode.login.desc'); ?></span>
      </td>
      <td>
        <?php
          global $cm_lang, $cm_texts;
          
          $attrs = array();
          $attrs['value'] = (isset($co_settings[0]['CoSetting']['t_and_c_login_mode'])
                             ? $co_settings[0]['CoSetting']['t_and_c_login_mode']
                             : "");
          $attrs['empty'] = false;
          
          if($e) {
            print $this->Form->select('t_and_c_login_mode',
                                      $cm_texts[ $cm_lang ]['en.tandc.mode.login'],
                                      $attrs);
            
            if($this->Form->isFieldError('t_and_c_login_mode')) {
              print $this->Form->error('t_and_c_login_mode');
            }
          } else {
            print _txt('en.tandc.mode.login', null, $co_settings[0]['CoSetting']['t_and_c_login_mode']);
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
            print $this->Form->submit($submit_label);
            print $this->Form->button(_txt('op.reset'), 
                                      array('type'=>'reset'));
          }
        ?>
      </td>
    </tr>
  </tbody>
</table>
