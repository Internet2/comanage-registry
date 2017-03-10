<!--
/**
 * COmanage Registry Regex Identifier Validator Plugin Fields
 * XXX unclear why this isn't in /RegexIdentifierValidator*s*/field.inc
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
 * @package       registry-plugin
 * @since         COmanage Registry v2.0.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */
-->
<?php
  // Determine if fields are editable
  $e = false;
  
  if(($this->action == "add" && $permissions['add']) || ($this->action == "edit" && $permissions['edit']))
    $e = true;
    
  // We shouldn't get here if we don't have at least read permission, but check just in case
  
  if(!$e && !$permissions['view'])
    return false;
  
  // Add breadcrumbs
  print $this->element("coCrumb");
  
  $args = array();
  $args['plugin'] = null;
  $args['controller'] = 'co_identifier_validators';
  $args['action'] = 'index';
  $args['co'] = $cur_co['Co']['id'];
  $this->Html->addCrumb(_txt('ct.co_identifier_validators.pl'), $args);
  
  $args = array();
  $args['plugin'] = null;
  $args['controller'] = 'co_identifier_validators';
  $args['action'] = 'edit';
  $args[] = $vv_ivid;
  $this->Html->addCrumb(_txt('ct.co_identifier_validators.1'), $args);  
  
  $crumbTxt = _txt('op.' . $this->action . '-a', array(_txt('ct.regex_identifier_validators.1')));
  $this->Html->addCrumb($crumbTxt);
  
  print $this->Form->hidden('co_identifier_validator_id', array('default' => $vv_ivid)) . "\n";
?>
<table id="<?php print $this->action; ?>_regex_identifier_validator" class="ui-widget">
  <tbody>
    <tr class="line1">
      <td>
        <strong class="fieldTitle"><?php print _txt('pl.regexidentifier.pattern'); ?></strong><font class="required">*</font><br />
        <font class="desc"><?php print _txt('pl.regexidentifier.pattern.desc'); ?></font>
      </td>
      <td>
        <?php print ($e ? $this->Form->input('pattern') : filter_var($regex_identifier_validators[0]['RegexIdentifierValidator']['pattern'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
      </td>
    </tr>
    <tr>
      <td>
        <i><font class="required"><?php print _txt('fd.req'); ?></font></i><br />
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
