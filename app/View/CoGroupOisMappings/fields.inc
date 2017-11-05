<?php
/**
 * COmanage Registry CO Group OIS Mappings Fields
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
 * @package       registry
 * @since         COmanage Registry v2.0.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
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
  $args['controller'] = 'co_group_ois_mappings';
  $args['action'] = 'index';
  $args['org_identity_source'] = $vv_ois_id;
  $this->Html->addCrumb(_txt('ct.co_group_ois_mappings.pl'), $args);
  $crumbTxt = _txt('op.' . $this->action . '-a', array(_txt('ct.co_group_ois_mappings.1')));
  $this->Html->addCrumb($crumbTxt);
  
  print $this->Form->hidden('org_identity_source_id', array('default' => $vv_ois_id)) . "\n";
?>

<div class="table-container">
  <table id="<?php print $this->action; ?>_co_group_ois_mapping">
    <tbody>
      <tr class="line1">
        <td>
          <?php print $this->Form->label('attribute', _txt('fd.attribute')); ?><span class="required">*</span>
        </td>
        <td>
          <?php
            $attrs = array();
            $attrs['value'] = (!empty($co_group_ois_mappings[0]['CoGroupOisMapping']['attribute'])
                               ? $co_group_ois_mappings[0]['CoGroupOisMapping']['attribute']
                               : "");
            $attrs['empty'] = false;

            print $this->Form->select('attribute',
                                      $vv_ois_group_attrs,
                                      $attrs);

            if($this->Form->isFieldError('attribute')) {
              print $this->Form->error('attribute');
            }
          ?>
        </td>
      </tr>
      <tr class="line2">
        <td>
          <?php print $this->Form->label('comparison', _txt('fd.comparison')); ?><span class="required">*</span>
        </td>
        <td>
          <?php
            global $cm_lang, $cm_texts;

            $attrs = array();
            $attrs['value'] = (!empty($co_group_ois_mappings[0]['CoGroupOisMapping']['comparison'])
                               ? $co_group_ois_mappings[0]['CoGroupOisMapping']['comparison']
                               : "");
            $attrs['empty'] = false;

            print $this->Form->select('comparison',
                                      $cm_texts[ $cm_lang ]['en.comparison'],
                                      $attrs);

            if($this->Form->isFieldError('comparison')) {
              print $this->Form->error('comparison');
            }
          ?>
        </td>
      </tr>
      <tr class="line1">
        <td>
          <?php print $this->Form->label('pattern', _txt('fd.pattern')); ?><span class="required">*</span><br />
          <span class="descr"><?php print _txt('fd.ois.pattern.desc'); ?></span>
        </td>
        <td>
          <?php print ($e ? $this->Form->input('pattern') : filter_var($co_group_ois_mappings[0]['CoGroupOisMapping']['pattern'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
        </td>
      </tr>
      <tr class="line2">
        <td>
          <?php print $this->Form->label('co_group_id', _txt('fd.ois.gr.map')); ?><span class="required">*</span><br />
          <span class="descr"><?php print _txt('fd.ois.gr.map.desc'); ?></span>
        </td>
        <td>
          <?php
            $attrs = array();
            $attrs['value'] = (!empty($co_group_ois_mappings[0]['CoGroupOisMapping']['co_group_id'])
                               ? $co_group_ois_mappings[0]['CoGroupOisMapping']['co_group_id']
                               : "");
            $attrs['empty'] = false;

            print $this->Form->select('co_group_id',
                                      $vv_groups,
                                      $attrs);

            if($this->Form->isFieldError('co_group_id')) {
              print $this->Form->error('co_group_id');
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
            }
          ?>
        </td>
      </tr>
    </tbody>
  </table>
</div>