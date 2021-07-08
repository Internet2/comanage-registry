<?php
/**
 * COmanage Registry Match Server Attribute Fields
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
 * @since         COmanage Registry v4.0.0
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
  $args['controller'] = 'servers';
  $args['action'] = 'index';
  $args['co'] = $cur_co['Co']['id'];
  $this->Html->addCrumb(_txt('ct.servers.pl'), $args);
  
  $args = array();
  $args['plugin'] = null;
  $args['controller'] = 'match_servers';
  $args['action'] = 'edit';
  $args[] = $vv_msid;
  $this->Html->addCrumb($vv_server_desc, $args);
  
  $args = array();
  $args['plugin'] = null;
  $args['controller'] = 'match_server_attributes';
  $args['action'] = 'index';
  $args['matchserver'] = $vv_msid;
  $this->Html->addCrumb(_txt('ct.match_server_attributes.pl'), $args);

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
    print $this->Form->hidden('match_server_id', array('default' => $vv_msid)) . "\n";
  }

  $l = 1;
?>
<script type="text/javascript">
  function js_local_onload() {
    update_type_select();
    
    var curtype = "<?php
     print !empty($match_server_attributes[0]['MatchServerAttribute']['type'])
           ? $match_server_attributes[0]['MatchServerAttribute']['type']
           : "";
    ?>";
    
    document.getElementById('MatchServerAttributeType').value = curtype;
  }
  
  function enum_for_attr(attrname) {
    // Determine if an attribute has defined types. We use PHP code to
    // generate javascript based on the view variable $vv_available_attribute_types.

    var ret = null;

    switch(attrname) {
    <?php foreach(array_keys($vv_available_attribute_types) as $k): ?>
      case "<?php print $k; ?>":
        ret = <?php
          foreach($vv_available_attribute_types[$k] as $key => $v) {
            $vv_available_attribute_types[$k][$key] = filter_var($v, FILTER_SANITIZE_SPECIAL_CHARS);
          }
          print json_encode($vv_available_attribute_types[$k]);
          ?>;
        break;
    <?php endforeach; ?>
    }

    return ret;
  }

  function update_type_select() {
    // The currently selected Attribute
    var attr = document.getElementById('MatchServerAttributeAttribute').value;
    
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
    $("#MatchServerAttributeType").find('option').remove().end().append($(options));
    
    if(values) {
      $("#MatchServerAttributeType").closest("li").show('fade');
    } else {
      $("#MatchServerAttributeType").closest("li").hide('fade');
    }
  }
</script>
<ul id="<?php print $this->action; ?>_match_server_attribute" class="fields form-list">
  <li>
    <div class="field-name">
      <?php print $this->Form->label('attribute',_txt('fd.attribute')); ?><span class="required">*</span>
    </div>
    <div class="field-info">
      <?php 
        $attrs = array();
        $attrs['value'] = (isset($match_server_attributes)
          ? $match_server_attributes[0]['MatchServerAttribute']['attribute']
          : "");
        $attrs['empty'] = true;
        $attrs['onChange'] = 'update_type_select()';
        
        if($e) {
          print $this->Form->select('attribute',
                                    $vv_available_attributes,
                                    $attrs);
          
          if($this->Form->isFieldError('attribute')) {
            print $this->Form->error('attribute');
          }
        } else {
          print $vv_available_attributes[ $match_server_attributes[0]['MatchServerAttribute']['attribute'] ];
        }
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <?php print $this->Form->label('type',_txt('fd.type')); ?><span class="required">*</span>
    </div>
    <div class="field-info">
      <?php
        $attrs = array();
        $attrs['value'] = (isset($match_server_attributes)
          ? $match_server_attributes[0]['MatchServerAttribute']['type']
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
          print $match_server_attributes[0]['MatchServerAttribute']['type'];
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
        $attrs['value'] = (isset($match_server_attributes)
          ? $match_server_attributes[0]['MatchServerAttribute']['required']
          : RequiredEnum::Required);
        $attrs['empty'] = false;

        if($e) {
          print $this->Form->select('required',
            $cm_texts[ $cm_lang ]['en.required'],
            $attrs);

          if($this->Form->isFieldError('required')) {
            print $this->Form->error('required');
          }
        } else {
          print _txt('en.required', null, $match_server_attributes[0]['MatchServerAttribute']['required']);
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

<?php print $this->element("changelog"); ?>
