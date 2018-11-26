<?php
/**
 * COmanage Registry CO Self Service Permissions Fields
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
 * @since         COmanage Registry v0.9
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */
?>

<script type="text/javascript">
  function js_local_onload() {
    update_type_select();
    
    // Figure out current value and set to selected
    var curvalue = "<?php print (!empty($co_self_service_permissions[0]['CoSelfServicePermission']['type'])
                                 ? $co_self_service_permissions[0]['CoSelfServicePermission']['type']
                                 : ""); ?>";
    
    document.getElementById('CoSelfServicePermissionType').value = curvalue;
  }
  
  function update_type_select() {
    var curmodel = document.getElementById('CoSelfServicePermissionModel').value;
    
    var options = new Array();
    
    <?php
      // Convert the available types for each model from a PHP array to a
      // javascript array so that we can update the Type popup
      
      foreach(array_keys($types) as $t) {
        // Create an array for this model
        print "options['" . $t . "'] = {\"" . _txt('fd.default') . "\": \"\",\n";
        
        foreach(array_keys($types[$t]) as $v) {
          // Output LocalizedString and EnumKey (v)
          print '"' . $types[$t][$v] . '": "' . $v . '",' . "\n";
        }
        
        print "};\n";
      }
    ?>
    
    // Clear the current select
    $('#CoSelfServicePermissionType').empty();
    
    // And replace with the key/value pairs for the current model
    $.each(options[curmodel], function(key, value) {
      $('#CoSelfServicePermissionType').append($("<option></option>")
        .attr("value", value).text(key));
    });
  }
</script>
<?php
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
  $args['controller'] = 'co_self_service_permissions';
  $args['action'] = 'index';
  $args['co'] = $cur_co['Co']['id'];
  $this->Html->addCrumb(_txt('ct.co_self_service_permissions.pl'), $args);
  $crumbTxt = _txt('op.' . $this->action . '-a', array(_txt('ct.co_self_service_permissions.1')));
  $this->Html->addCrumb($crumbTxt);

  print $this->Form->hidden('co_id', array('default' => $cur_co['Co']['id'])) . "\n";

  $l = 1;
?>
<ul id="<?php print $this->action; ?>_co_self_service_permissions" class="fields form-list">
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print ($e ? $this->Form->label('model', _txt('fd.model')) : _txt('fd.model')); ?>
        <span class="required">*</span>
      </div>
    </div>
    <div class="field-info">
      <?php
        $attrs = array();
        $attrs['value'] = (isset($co_self_service_permissions[0]['CoSelfServicePermission']['model'])
                           ? $co_self_service_permissions[0]['CoSelfServicePermission']['model']
                           : 0);
        $attrs['empty'] = false;
        $attrs['onchange'] = "update_type_select();";
        
        if($e) {
          print $this->Form->select('model',
                                    $models,
                                    $attrs);
          
          if($this->Form->isFieldError('model')) {
            print $this->Form->error('model');
          }
        } else {
          if(!empty($co_self_service_permissions[0]['CoSelfServicePermission']['model'])) {
            print filter_var($cous[ $co_self_service_permissions[0]['CoSelfServicePermission']['model'] ],FILTER_SANITIZE_SPECIAL_CHARS);
          }
        }
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print ($e ? $this->Form->label('type', _txt('fd.type')) : _txt('fd.type')); ?>
      </div>
      <div class="field-desc"><?php print _txt('fd.ssp.type.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php
        $attrs = array();
        $attrs['value'] = (isset($co_self_service_permissions[0]['CoSelfServicePermission']['type'])
                           ? $co_self_service_permissions[0]['CoSelfServicePermission']['type']
                           : 0);
        $attrs['empty'] = true;
        
        if($e) {
          print $this->Form->select('type',
                                    array(), // The available options will be set by update_type_select()
                                    $attrs);
          
          if($this->Form->isFieldError('type')) {
            print $this->Form->error('type');
          }
        } else {
          if(!empty($co_self_service_permissions[0]['CoSelfServicePermission']['type'])) {
            print $types[ $co_self_service_permissions[0]['CoSelfServicePermission']['model'] ][ $co_self_service_permissions[0]['CoSelfServicePermission']['type'] ];
          }
        }
      ?>
    </div>
  </li>    
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print ($e ? $this->Form->label('permission', _txt('fd.perm')) : _txt('fd.perm')); ?>
        <span class="required">*</span>
      </div>
    </div>
    <div class="field-info">
      <?php
        global $cm_texts, $cm_lang;
        
        $attrs = array();
        $attrs['value'] = (isset($co_self_service_permissions[0]['CoSelfServicePermission']['permission'])
                           ? $co_self_service_permissions[0]['CoSelfServicePermission']['permission']
                           : 0);
        $attrs['empty'] = false;
        
        if($e) {
          print $this->Form->select('permission',
                                    $cm_texts[ $cm_lang ]['en.permission'],
                                    $attrs);
          
          if($this->Form->isFieldError('permission')) {
            print $this->Form->error('permission');
          }
        } else {
          if(!empty($co_self_service_permissions[0]['CoSelfServicePermission']['permission'])) {
            print _txt('en.permission', null, $co_self_service_permissions[0]['CoSelfServicePermission']['permission']);
          }
        }
      ?>
    </div>
  </li>

  <?php if($e): ?>
    <li class="fields-submit">
      <div class="field-name"></div>
      <div class="field-info">
        <?php print $this->Form->submit($submit_label); ?>
      </div>
    </li>
  <?php endif; ?>
</ul>
