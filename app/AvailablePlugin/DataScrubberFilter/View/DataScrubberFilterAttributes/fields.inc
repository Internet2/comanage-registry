<?php
/**
 * COmanage Registry Data Scrubber Filter Attribute Fields
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
 * @link          https://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v4.1.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

  // Determine if fields are editable
  $e = false;

  if(($this->action == "add" && $permissions['add']) || ($this->action == "edit" && $permissions['edit'])) {
    $e = true;
  }

  // We shouldn't get here if we don't have at least read permission, but check just in case
  
  if(!$e && !$permissions['view'])
    return(false);

  // Add breadcrumbs
  print $this->element("coCrumb");

  $args = array();
  $args['plugin'] = null;
  $args['controller'] = 'data_filters';
  $args['action'] = 'index';
  $args['co'] = $cur_co['Co']['id'];
  $this->Html->addCrumb(_txt('ct.data_filters.pl'), $args);

  $args = array();
  $args['plugin'] = null;
  $args['controller'] = 'data_filters';
  $args['action'] = 'edit';
  $args[] = $vv_datafilter['id'];
  $this->Html->addCrumb($vv_datafilter['description'], $args);

  $args = array();
  $args['plugin'] = 'data_scrubber_filter';
  $args['controller'] = 'data_scrubber_filters';
  $args['action'] = 'edit';
  $args[] = $vv_dsfilter['id'];
  $this->Html->addCrumb(_txt('op.config'), $args);
  
  $args = array();
  $args['plugin'] = 'data_scrubber_filter';
  $args['controller'] = 'data_scrubber_filter_attributes';
  $args['action'] = 'index';
  $args['datascrubberfilter'] = $vv_dsfilter['id'];
  $this->Html->addCrumb(_txt('ct.data_scrubber_filter_attributes.pl'), $args);

  if($this->action == 'edit') {
    $this->Html->addCrumb(_txt('op.edit'));
  }
  if($this->action == 'view') {
    $this->Html->addCrumb(_txt('op.view'));
  }
  if($this->action == 'add') {
    $this->Html->addCrumb(_txt('op.add'));
  }
  
  if($this->action != 'view') {
    // Populate cross references
    print $this->Form->hidden('data_scrubber_filter_id', array('default' => $vv_dsfilter['id'])) . "\n";
  }

  $l = 1;
?>
<script type="text/javascript">
  function js_local_onload() {
    update_type_select();
    
    var curtype = "<?php
     print !empty($data_scrubber_filter_attributes[0]['DataScrubberFilterAttribute']['type'])
           ? $data_scrubber_filter_attributes[0]['DataScrubberFilterAttribute']['type']
           : "";
    ?>";
    
    document.getElementById('DataScrubberFilterAttributeType').value = curtype;
  }
  
  function enum_for_attr(attrname) {
    // Determine if an attribute has defined types. We use PHP code to
    // generate javascript based on the view variable $vv_available_attributes.

    var ret = null;

    switch(attrname) {
    <?php foreach($vv_available_attributes as $attr => $val): ?>
    <?php if(is_array($val)): ?>
      case "<?php print $attr; ?>":
        ret = <?php
          foreach($val as $key => $label) {
            $val[$key] = filter_var($label, FILTER_SANITIZE_SPECIAL_CHARS);
          }
          print json_encode($val);
          ?>;
        break;
    <?php endif; ?>
    <?php endforeach; ?>
    }

    return ret;
  }

  function update_type_select() {
    // The currently selected Attribute
    var attr = document.getElementById('DataScrubberFilterAttributeAttribute').value;
    
    // The valid enumerations for this Attribute
    var values = enum_for_attr(attr);
    
    // Replacement configuration for the Select
    var options = "<option value=''></option>";
    
    if(values) {
      // Update the select
      for(let id of Object.keys(values)) {
        options += "<option value='" + id + "'>" + values[id] + "</option>";
      }
    }
    
    // This will create an empty set if values is null
    $("#DataScrubberFilterAttributeType").find('option').remove().end().append($(options));
    
    if(values) {
      $("#DataScrubberFilterAttributeType").closest("li").show('fade');
    } else {
      $("#DataScrubberFilterAttributeType").closest("li").hide('fade');
    }
  }
</script>
<ul id="<?php print $this->action; ?>_data_scrubber_filter_attributes" class="fields form-list">
  <li>
    <div class="field-name">
      <?php print $this->Form->label('attribute',_txt('fd.attribute')); ?><span class="required">*</span>
    </div>
    <div class="field-info">
      <?php 
        $attrs = array();
        $attrs['value'] = (isset($data_scrubber_filter_attributes)
          ? $data_scrubber_filter_attributes[0]['DataScrubberFilterAttribute']['attribute']
          : "");
        $attrs['empty'] = true;
        $attrs['onChange'] = 'update_type_select()';
        
        if($e) {
          print $this->Form->select('attribute',
                                    // If we don't repeat the array, the keys are integers
                                    array_combine(array_keys($vv_available_attributes), array_keys($vv_available_attributes)),
                                    $attrs);
          
          if($this->Form->isFieldError('attribute')) {
            print $this->Form->error('attribute');
          }
        } else {
          print $data_scrubber_filter_attributes[0]['DataScrubberFilterAttribute']['attribute'];
        }
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <?php print $this->Form->label('type',_txt('fd.type')); ?>
    </div>
    <div class="field-info">
      <?php
        $attrs = array();
        $attrs['value'] = (isset($data_scrubber_filter_attributes)
          ? $data_scrubber_filter_attributes[0]['DataScrubberFilterAttribute']['type']
          : "");
        $attrs['empty'] = true;

        if($e) {
          print $this->Form->select('type',
                                    array(),
                                    $attrs);

          if($this->Form->isFieldError('type')) {
            print $this->Form->error('type');
          }
        } else {
          print $data_scrubber_filter_attributes[0]['DataScrubberFilterAttribute']['type'];
        }
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <?php print $this->Form->label('required',_txt('fd.required')); ?><span class="required">*</span>
    </div>
    <div class="field-info">
      <?php
        global $cm_lang, $cm_texts;
        
        $attrs = array();
        $attrs['value'] = (isset($data_scrubber_filter_attributes)
          ? $data_scrubber_filter_attributes[0]['DataScrubberFilterAttribute']['required']
          : RequiredEnum::NotPermitted);
        $attrs['empty'] = false;

        if($e) {
          print $this->Form->select('required',
            $cm_texts[ $cm_lang ]['en.required'],
            $attrs);

          if($this->Form->isFieldError('required')) {
            print $this->Form->error('required');
          }
        } else {
          print _txt('en.required', null, $data_scrubber_filter_attributes[0]['DataScrubberFilterAttribute']['required']);
        }
      ?>
    </div>
  </li>
  <?php if($e): ?>
    <li class="fields-submit">
      <div class="field-name">
        <span class="required"><?php print _txt('fd.req'); ?></span>
      </div>
      <div class="field-info">
        <?php print $this->Form->submit($submit_label); ?>
      </div>
    </li>
  <?php endif; ?>
</ul>

<?php
  print $this->element("changelog");
