<?php
/**
 * COmanage Registry CO Enrollment Attribute Fields
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
 * @since         COmanage Registry v0.3
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */
?>

<script type="text/javascript">
  function js_local_onload() {
    initialize_attr_select();
  }

  const available_attributes_nested = <?php print json_encode($vv_available_attributes_nested);?>;
  const vv_attributes_properties = <?php print json_encode($vv_attributes_properties);?>;
  var stored_attribute = null;
  // Ensure that fields are updated once
  let fields_updated = false;

  function enum_for_attr(attrname) {
    // Determine if an attribute has defined enumerations. We use PHP code to
    // generate javascript based on the view variable $vv_enums.
    
    var ret = undefined;
    
    switch(attrname) {
    <?php foreach(array_keys($vv_enums) as $k): ?>
      case "<?php print $k; ?>":
        ret = new Array(<?php print '"' . join('","', array_map(function($v) { return filter_var($v, FILTER_SANITIZE_SPECIAL_CHARS); }, array_values($vv_enums[$k]))) . '"'; ?>);
        break;
    <?php endforeach; ?>
    }
    
    return ret;
  }
  
  function ext_attr_type(attrname) {
    // Determine the type of an extended attribute. We use PHP code to generate
    // javascript based on the view variable $vv_ext_attr_types.
    
    switch(attrname) {
    <?php foreach(array_keys($vv_ext_attr_types) as $xa): ?>
      case "<?php print $xa; ?>":
        return "<?php print filter_var($vv_ext_attr_types[$xa],FILTER_SANITIZE_SPECIAL_CHARS); ?>";
        break;
    <?php endforeach; ?>
    }
    
    // We shouldn't get here
    return "UNKNOWN";
  }
  
  function set_attr_def_from_date(datetype) {
    // Offset date requires a + prefix
    document.getElementById('CoEnrollmentAttributeDefault0Value').value =
      (datetype == "offset" ? "+" : "") +
      document.getElementById('def_date_val_' + datetype).value;
  }
  
  function set_attr_def_date_radio(datetype) {
    document.getElementById('def_date_' + datetype).checked=true;
    set_attr_def_from_date(datetype)
  }
  
  function set_attr_def_value(elementid) {
    // Offset date requires a + prefix
    const input_value =
      (elementid == "def_date_val_offset" ? "+" : "") + document.getElementById(elementid).value;

    // Update Form Input Field
    document.getElementById('CoEnrollmentAttributeDefault0Value').value = input_value;
    // Save state
    const $default = $('div[id^="attr_def_val_"').find(':visible');
    if($default.length > 0) {
        if($('#type_fields_div').is(':visible')) {
            const $selected_option = $('#type_fields_div > select').find(':selected');
            $selected_option.attr('data-default', input_value);
        } else {
            const $selected_option = $('#attr_fields_div > select').find(':selected');
            $selected_option.attr('data-default', input_value);
        }
    }
  }
  
  function toggle_attr_def_div(curval) {
    // For now, only CO Person Role attributes (type 'r'), Organizational Identity
    // attributes (type 'o'), or Extended Attributes (type 'x') can have default values.
    
    var curattr = document.getElementById('CoEnrollmentAttributeAttribute').value;
    var attrtype = curattr[0];
    
    if(attrtype == "r" || attrtype == "o" || attrtype == "x" || attrtype == "g") {
      $("#attr_def_div").show("fade", { "direction" : "up" });
      
      // Adjust the gadgets shown
      
      $("#attr_def_val_div").hide();
      $("#attr_def_val_affil_div").hide();
      $("#attr_def_val_cou_div").hide();
      $("#attr_def_val_date_div").hide();
      $("#attr_def_val_enum_div").hide();
      $("#attr_def_val_group_div").hide();
      $("#attr_def_val_sponsor_div").hide();

      // Update the Form input value
      document.getElementById('CoEnrollmentAttributeDefault0Value').value = curval;
      
      var curattrarr = curattr.split(":");
      var curattrcode = curattrarr[0];
      var curattrname = curattrarr[1];
      
      // if x:foo then pull foo and lookup in $vv_ext_attr_types to find type
      // (write javascript array based on $vv_ext contents?)
      
      var enums = enum_for_attr(curattr);
      
      if(enums != undefined) {
        // Enumerations are defined for the attribute, so generate an appropriate select
        var options = "<option value=''></option>";
        for(var i=0;i < enums.length;i++) {
          options += "<option value='" + enums[i] + "'>" + enums[i] + "</option>";
        }
        $("#def_enum_val").find('option').remove().end().append($(options));
        
        document.getElementById('def_enum_val').value = curval;
        $("#attr_def_val_enum_div").show("fade");
      } else if((curattrcode == "o" || curattrcode == "r")
         && curattrname == "affiliation") {
        // Set current value before showing
        document.getElementById('def_affil_val').value = curval;
        $("#attr_def_val_affil_div").show("fade");
      } else if(curattrcode == "r" && curattrname == "cou_id") {
        // Set current value before showing
        document.getElementById('def_cou_val').value = curval;
        $("#attr_def_val_cou_div").show("fade");
      } else if(curattrcode == "r" && curattrname == "sponsor_co_person_id") {
        // Set current value before showing
        document.getElementById('def_sponsor_val').value = curval;
        $("#attr_def_val_sponsor_div").show("fade");
      } else if((curattrcode == "r"
                 && (curattrname == "valid_from" || curattrname == "valid_through"))
                ||
                (curattrcode == "x"
                 && ext_attr_type(curattrname) == "<?php print ExtendedAttributeEnum::Timestamp; ?>")) {
        // Set current value before showing
        var fixedre = /^[0-9]{4}\-[0-2][0-9]\-[0-9]{2}$/;   // YYYY-MM-DD
        var nextre = /^[0-2][0-9]\-[0-9]{2}$/;              // MM-DD
        var offsetre = /^\+[0-9]+$/;                        // +DD
        
        if(fixedre.test(curval)) {
          document.getElementById('def_date_val_fixed').value = curval;
          set_attr_def_date_radio('fixed');
        } else if(nextre.test(curval)) {
          document.getElementById('def_date_val_next').value = curval;
          set_attr_def_date_radio('next');
        } else if(offsetre.test(curval)) {
          // Trim the leading plus (it will get added back to curval by set_attr_def_date_radio)
          document.getElementById('def_date_val_offset').value = curval.replace("+", "");
          set_attr_def_date_radio('offset');
        }
        
        $("#attr_def_val_date_div").show("fade");
      } else if(curattrcode == "g") {
        // Set current value before showing
        document.getElementById('def_group_val').value = curval;
        $("#attr_def_val_group_div").show("fade");
      } else {
        // Set current value before showing
        document.getElementById('def_val_div').value = curval;
        $("#attr_def_val_div").show("fade");
      }
    } else {
      $("#attr_def_div").hide("fade", { "direction" : "up" });
    }
  }
  
  function toggle_attr_type_div() {
    // Show or hide language selectors, required fields, and login selectors
    
    var curattr = document.getElementById('CoEnrollmentAttributeAttribute').value;
    
    var curattrarr = curattr.split(":");
    var curattrcode = curattrarr[0];
    var curattrname = curattrarr[1];
    
    // Current required fields
    var curreqfields = document.getElementById('CoEnrollmentAttributeRequiredFields').value;
    
    if(curattrname == "address") {
      $("#type_div").show("fade");
      $("#address_required_fields_div").show("fade");
      $("#login_div").hide("fade");
      $("#name_required_fields_div").hide("fade");
      
      // Synchronize the popup and the actual field that holds the value
      
      if(curreqfields == "") {
        // No current value, set field to default
        document.getElementById('CoEnrollmentAttributeAddressFields').options[0].selected = true;
        document.getElementById('CoEnrollmentAttributeRequiredFields').value
          = document.getElementById('CoEnrollmentAttributeAddressFields').value;
      } else {
        // Set popup to current field value
        document.getElementById('CoEnrollmentAttributeAddressFields').value = curreqfields;
      }
    } else if(curattrname == "name") {
      $("#type_div").show("fade");
      $("#address_required_fields_div").hide("fade");
      $("#login_div").hide("fade");
      $("#name_required_fields_div").show("fade");
      // Make sure the popup matches the current required_fields value
      document.getElementById('CoEnrollmentAttributeNameFields').value=
        document.getElementById('CoEnrollmentAttributeRequiredFields').value
      
      // Synchronize the popup and the actual field that holds the value
      
      if(curreqfields == "") {
        // No current value, set field to default
        document.getElementById('CoEnrollmentAttributeNameFields').options[0].selected = true;
        document.getElementById('CoEnrollmentAttributeRequiredFields').value
         = document.getElementById('CoEnrollmentAttributeNameFields').value;
      } else {
        // Set popup to current field value
        document.getElementById('CoEnrollmentAttributeNameFields').value = curreqfields;
      }
    } else if(curattrname == "identifier") {
      $("#type_div").hide("fade");
      $("#address_required_fields_div").hide("fade");
      $("#login_div").show("fade");
      $("#name_required_fields_div").hide("fade");
    } else {
      $("#type_div").hide("fade");
      $("#address_required_fields_div").hide("fade");
      $("#login_div").hide("fade");
      $("#name_required_fields_div").hide("fade");
      
      // Blank the current required fields
      document.getElementById('CoEnrollmentAttributeRequiredFields').value = "";
    }
  }
  
  function toggle_copy_to_coperson_div() {
    var curattr = document.getElementById('CoEnrollmentAttributeAttribute').value;
    var attrtype = curattr[0];
    
    if(attrtype == "i") {
      $("#copy_to_coperson_div").show("fade");
    } else {
      $("#copy_to_coperson_div").hide("fade");
      document.getElementById('CoEnrollmentAttributeCopyToCoperson').checked=false;
    }
  }

  function initialize_attr_select() {
      trigger_update_fields($('#CoEnrollmentAttributeMdlField'));
  }

  function trigger_update_fields(element) {
      let databind = $("option:selected", element).data('bind');
      let value = $("option:selected", element).attr('value');
      fields_updated = false;

      if( databind !== null
          && databind !== ''
          && $.isPlainObject(databind)) {
          let binded_select = $(element).closest('div').next().find('select');
          update_attr_fields(binded_select.attr('id'), databind, value);
          $(element).closest('div').next().show();
      } else {
          if (element.id !== 'CoEnrollmentAttributeTypeField') {
              $(element).closest('div').next().hide();
          }
          // Update the hidden field
          document.getElementById('CoEnrollmentAttributeAttribute').value = value;
      }

      // Force run only once. Run while in the deepest level of recursion
      if(!fields_updated) {
          if(document.getElementById('CoEnrollmentAttributeRequiredFields') != null) {
              document.getElementById('CoEnrollmentAttributeRequiredFields').value = '';
              let default_val = ($("option:selected", element).data('default') == null) ? '' : $("option:selected", element).data('default');
              toggle_attr_def_div(default_val);
              toggle_copy_to_coperson_div();
              toggle_attr_type_div();
              fields_updated = true;
          }
      }
  }

  function update_attr_fields(select_obj_id, options_object, parent_class) {
      let select = $('#' + select_obj_id);
      if(stored_attribute === null) {
          stored_attribute = document.getElementById('CoEnrollmentAttributeAttribute').value;
      }
      let curattr_hmn = $.map(vv_attributes_properties[stored_attribute], function(item) {
          return item;
      });
      // Reset the select list
      select.empty();
      $.each(options_object, function (key, data) {
          append_option(select, key, data, parent_class, curattr_hmn);
      });
      select.change();
  }

  function append_option(parent, value, text, attrMdl, attributesProperty) {
      let option = $('<option/>');
      let cur_value = '';
      option.attr("value", value);
      option.attr("data-class", attrMdl);
      if ($.isPlainObject(text)) {
          option.attr("data-bind", JSON.stringify(text));
          option.text(value);
          cur_value = value;
      } else {
          option.text(text);
          cur_value = text;
      }
      if ($.inArray(cur_value, attributesProperty) !== -1) {
          option.attr('selected', 'selected');
          <?php if(!empty($co_enrollment_attributes[0]['CoEnrollmentAttributeDefault'][0]['value'])): ?>
          option.attr('data-default', '<?php print filter_var($co_enrollment_attributes[0]['CoEnrollmentAttributeDefault'][0]['value'],FILTER_SANITIZE_SPECIAL_CHARS); ?>');
          <?php endif; ?>
      }
      parent.append(option);
  }

  $(function() {
    <?php if($vv_sponsor_mode != SponsorEligibilityEnum::None && !isset($vv_sponsors)): ?>
    // Sponsor Auto-complete
    $("#sponsors").autocomplete({
      source: "<?php print $this->Html->url(array('controller' => 'co_people', 'action' => 'find', 'co' => $cur_co['Co']['id'], 'mode' => PeoplePickerModeEnum::Sponsor)); ?>",
      minLength: 3,
      select: function(event, ui) {
        $("#sponsors").val(ui.item.label);
        document.getElementById('CoEnrollmentAttributeDefault0Value').value = ui.item.value;
        return false;
      }
    });

    // Used in inline-edit mode for Sponsor (i.e. when sponsor list > 50)
    $("#cm-ief-button-remove-sponsor_co_person_id").click(function() {
      reset_sponsor();
      $(this).closest(".cm-ief-val").hide();
      $(this).closest(".cm-inline-editable-field").find(".cm-ief-widget").removeClass('hidden');
      $("#sponsors").focus();
    });
    <?php endif; //vv_sponsor_mode ?>
  });

  <?php if($vv_sponsor_mode !== SponsorEligibilityEnum::None && !isset($vv_sponsors)): ?>
  function reset_sponsor() {
    $("#sponsors").val("");
    document.getElementById('CoEnrollmentAttributeDefault0Value').value = null;
  }
  <?php endif; //vv_sponsor_mode ?>
</script>
<?php
  // Determine if fields are editable
  $e = false;
  $display = '';

  if(($this->action == "add" && $permissions['add']) || ($this->action == "edit" && $permissions['edit'])) {
    $e = true;
    $display = 'none';
  }


// We shouldn't get here if we don't have at least read permission, but check just in case
  
  if(!$e && !$permissions['view'])
    return(false);

  // Add breadcrumbs
  print $this->element("coCrumb");
  $args = array();
  $args['plugin'] = null;
  $args['controller'] = 'co_enrollment_flows';
  $args['action'] = 'index';
  $args['co'] = $cur_co['Co']['id'];
  $this->Html->addCrumb(_txt('ct.co_enrollment_flows.pl'), $args);

  $args = array();
  $args['plugin'] = null;
  $args['controller'] = 'co_enrollment_flows';
  $args['action'] = 'edit';
  $args[] = $vv_coefid;
  $this->Html->addCrumb($vv_ef_name, $args);

  $args = array();
  $args['plugin'] = null;
  $args['controller'] = 'co_enrollment_attributes';
  $args['action'] = 'index';
  $args['coef'] = $vv_coefid;
  $this->Html->addCrumb(_txt('ct.co_enrollment_attributes.pl'), $args);

  if ($this->action == 'edit') {
    $this->Html->addCrumb(_txt('op.edit'));
  }
  if ($this->action == 'view') {
    $this->Html->addCrumb(_txt('op.view'));
  }
  if ($this->action == 'add') {
    $this->Html->addCrumb(_txt('op.add'));
  }
  
  if($this->action != 'view') {
    // Populate cross references
    print $this->Form->hidden('co_enrollment_flow_id', array('default' => $vv_coefid)) . "\n";
    print $this->Form->hidden('CoEnrollmentAttributeDefault.0.id') . "\n";
    print $this->Form->hidden('CoEnrollmentAttributeDefault.0.co_enrollment_attribute_id') . "\n";
  }

  $co_enrollment_attribute_attribute = !empty($co_enrollment_attributes[0]['CoEnrollmentAttribute']['attribute'])
                                       ? filter_var($co_enrollment_attributes[0]['CoEnrollmentAttribute']['attribute'],FILTER_SANITIZE_SPECIAL_CHARS)
                                       : '';
  print $this->Form->hidden('attribute', array('value' => $co_enrollment_attribute_attribute)) . "\n";
  print $this->Form->hidden('CoEnrollmentAttributeDefault.0.value', array('value' => filter_var($co_enrollment_attributes[0]['CoEnrollmentAttributeDefault'][0]['value'],FILTER_SANITIZE_SPECIAL_CHARS))) . "\n";

  $l = 1;
?>
<div class="table-container">
  <table id="<?php print $this->action; ?>_co_enrollment_attribute">
    <tbody>
      <tr class="line<?php print ($l % 2); $l++; ?>">
        <td width="50%"> <!-- force a width here and below so the two tables line up -->
          <b><?php print _txt('fd.ea.label'); ?></b><br />
          <span class="descr"><?php print _txt('fd.ea.label.desc'); ?></span>
        </td>
        <td>
          <?php print ($e
            ? $this->Form->input('label', array('default' => _txt('fd.untitled'),'class' => 'focusFirst'))
            : filter_var($co_enrollment_attributes[0]['CoEnrollmentAttribute']['label'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
        </td>
      </tr>
      <tr class="line<?php print ($l % 2); $l++; ?>">
        <td>
          <b><?php print _txt('fd.ea.desc'); ?></b><br />
          <span class="descr"><?php print _txt('fd.ea.desc.desc'); ?></span>
        </td>
        <td>
          <?php print ($e
            ? $this->Form->input('description', array('size' => 40))
            : filter_var($co_enrollment_attributes[0]['CoEnrollmentAttribute']['description'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
        </td>
      </tr>
      <tr class="line<?php print ($l % 2); $l++; ?>">
        <td>
          <b><?php print _txt('fd.attribute'); ?></b>
        </td>
        <td>
          <?php if($e): ?>
            <div id="mdl_fields_div">
              <?php print _txt('fd.class.attr'); ?>:<br />
                <select id="CoEnrollmentAttributeMdlField" onchange="trigger_update_fields(this);">
                <?php
                  $mdl_options = array_keys($vv_available_attributes_nested);
                  $currentAttr = $co_enrollment_attributes[0]['CoEnrollmentAttribute']['attribute'];
                  $attrProps = $vv_attributes_properties[$currentAttr];
                  foreach($mdl_options as $mdl) {
                    $selected = ($mdl === $attrProps['attrMdl']) ? 'selected' : '';
                    print '<option value="' . $mdl . '" '
                         . $selected
                         . ' data-bind="' . htmlspecialchars(json_encode($vv_available_attributes_nested[$mdl]), ENT_QUOTES, 'UTF-8') .
                         '">' . $mdl . '</option>';
                  }
                ?>
                </select>
            </div>
            <div id="attr_fields_div">
              <?php print _txt('fd.name.attr'); ?>:<br />
              <select id="CoEnrollmentAttributeNameField" onchange="trigger_update_fields(this);"></select>
            </div>
            <div id="type_fields_div" style="display: none">
              <?php print _txt('fd.type.attr'); ?>:<br />
              <select id="CoEnrollmentAttributeTypeField" onchange="trigger_update_fields(this);"></select>
            </div>
          <?php else: ?>
            <div id="attr_print_div">
              <?php
                $attrProps = $vv_attributes_properties[ $co_enrollment_attribute_attribute ];
                print $attrProps['attrName'] . '(' . implode(', ', array_slice(array_filter($attrProps), '1')) . ')';
              ?>
            </div>
          <?php endif; ?>
          <div id="type_div" style="display: none">
            <?php print _txt('fd.name.language'); ?>:<br />
            <?php
            global $cm_lang, $cm_texts;

            $attrs = array();
            $attrs['value'] = (isset($co_enrollment_attributes[0]['CoEnrollmentAttribute']['language'])
              ? $co_enrollment_attributes[0]['CoEnrollmentAttribute']['language']
              : getPreferredLanguage());
            $attrs['empty'] = true;

            print $this->Form->select('language',
              $cm_texts[ $cm_lang ]['en.language'],
              $attrs);

            if($this->Form->isFieldError('language')) {
              print $this->Form->error('language');
            }
            ?>
          </div>
          <div id="address_required_fields_div" style="display: none">
            <?php print _txt('fd.address.fields.req'); ?><br />
            <select
              id="CoEnrollmentAttributeAddressFields"
              onchange="javascript:document.getElementById('CoEnrollmentAttributeRequiredFields').value=this.value">
              <?php
              global $cm_lang, $cm_texts;

              foreach($cm_texts[ $cm_lang ]['en.required.address'] as $key => $txt):
                ?>
                <option value="<?php print $key; ?>"><?php print $txt; ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div id="name_required_fields_div" style="display: none">
            <?php print _txt('fd.name.fields.req'); ?><br />
            <select
              id="CoEnrollmentAttributeNameFields"
              onchange="javascript:document.getElementById('CoEnrollmentAttributeRequiredFields').value=this.value">
              <?php
              global $cm_lang, $cm_texts;

              foreach($cm_texts[ $cm_lang ]['en.required.name'] as $key => $txt):
                ?>
                <option value="<?php print $key; ?>"><?php print $txt; ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div id="required_fields_div" style="display: none">
            <?php
            if($e) {
              print $this->Form->input('required_fields');
            }
            ?>
          </div>
          <div id="copy_to_coperson_div" style="display: none">
            <?php print ($e
              ? $this->Form->input('copy_to_coperson',
                array('default' => false))
              : ($co_enrollment_attributes[0]['CoEnrollmentAttribute']['copy_to_coperson']
                ? _txt('fd.yes') : _txt('fd.no')));
            print _txt('fd.ea.attr.copy2cop');
            ?>
          </div>
          <div id="login_div" style="display: none">
            <?php print ($e
              ? $this->Form->input('login', array('default' => false))
              : ($co_enrollment_attributes[0]['CoEnrollmentAttribute']['login']
                ? _txt('fd.yes') : _txt('fd.no')));
            print _txt('fd.identifier.login.desc');
            ?>
          </div>
        </td>
      </tr>
      <tr class="line<?php print ($l % 2); $l++; ?>">
        <td>
          <b><?php print _txt('fd.required'); ?></b>
        </td>
        <td>
          <?php
          global $cm_lang, $cm_texts;
          $attrs['value'] = (isset($co_enrollment_attributes)
            ? $co_enrollment_attributes[0]['CoEnrollmentAttribute']['required']
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
            print _txt('en.required', null, $co_enrollment_attributes[0]['CoEnrollmentAttribute']['required']);
          }
          ?>
        </td>
      </tr>
      <?php if(isset($vv_attributes_from_env) && $vv_attributes_from_env): ?>
        <tr class="line<?php print ($l % 2); $l++; ?>">
          <td>
            <b><?php print _txt('fd.ea.ignauth'); ?></b><br />
            <span class="descr"><?php print _txt('fd.ea.ignauth.desc'); ?></span>
          </td>
          <td>
            <?php
            print ($e
              ? $this->Form->input('ignore_authoritative',
                array('default' => false))
              : ($co_enrollment_attributes[0]['CoEnrollmentAttribute']['ignore_authoritative']
                ? _txt('fd.yes') : _txt('fd.no')));
            ?>
          </td>
        </tr>
      <?php endif; ?>
      <tr class="line<?php print ($l % 2); $l++; ?>">
        <td>
          <b><?php print _txt('fd.ea.default_env'); ?></b><br />
          <span class="descr"><?php print _txt('fd.ea.default_env.desc'); ?></span>
        </td>
        <td>
          <?php print ($e
            ? $this->Form->input('default_env', array('size' => 40))
            : filter_var($co_enrollment_attributes[0]['CoEnrollmentAttribute']['default_env'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
        </td>
      </tr>
      <tr class="line<?php print ($l % 2); $l++; ?>">
        <td>
          <b><?php print _txt('fd.ea.order'); ?></b><br />
          <span class="descr"><?php print _txt('fd.ea.order.desc'); ?></span>
        </td>
        <td>
          <?php print ($e
            ? $this->Form->input('ordr')
            : filter_var($co_enrollment_attributes[0]['CoEnrollmentAttribute']['ordr'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
        </td>
      </tr>
    </tbody>
    <tbody id="attr_def_div" style="display: <?php print $display;?>">
      <tr class="line<?php print ($l % 2); $l++; ?>">
        <td width="50%">
          <b><?php print _txt('fd.ed.default'); ?></b><br />
        </td>
        <td>
          <div id="attr_def_val_div">
            <?php if($e): ?>
                <input name="def_val_div"
                       id="def_val_div"
                       size="40"
                       maxlength="80"
                       type="text"
                       onchange="set_attr_def_value('def_val_div')"
                />
            <?php else: ?>
              <?php print filter_var($co_enrollment_attributes[0]['CoEnrollmentAttributeDefault'][0]['value'],FILTER_SANITIZE_SPECIAL_CHARS); ?>
            <?php endif; ?>
          </div>
          <div id="attr_def_val_affil_div">
            <?php if($e): ?>
              <select name="def_affil_val"
                      id="def_affil_val"
                      onchange="set_attr_def_value('def_affil_val')">
                <option value=""></option>
                <?php foreach(array_keys($vv_affiliations) as $k): ?>
                  <option value="<?php print filter_var($k,FILTER_SANITIZE_SPECIAL_CHARS); ?>">
                    <?php print filter_var($vv_affiliations[$k],FILTER_SANITIZE_SPECIAL_CHARS); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            <?php endif; ?>
          </div>
          <div id="attr_def_val_cou_div">
            <?php if($e): ?>
              <select name="def_cou_val"
                      id="def_cou_val"
                      onchange="set_attr_def_value('def_cou_val')">
                <?php if($vv_allow_empty_cou): ?>
                  <option value=""></option>
                <?php endif;?>
                <?php foreach($permissions['cous'] as $k => $value): ?>
                  <option value="<?php print filter_var($k,FILTER_SANITIZE_SPECIAL_CHARS); ?>">
                    <?php print filter_var($value,FILTER_SANITIZE_SPECIAL_CHARS); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            <?php endif; ?>
          </div>
          <div id="attr_def_val_date_div" class="modelbox-data">
            <?php if($e): ?>
              <input name="def_date_type"
                     id="def_date_fixed"
                     type="radio"
                     value="fixed"
                     onchange="set_attr_def_from_date('fixed')"
                />
              <?php print _txt('fd.ed.date.fixed'); ?>
              <input name="def_date_val_fixed"
                     id="def_date_val_fixed"
                     class="datepicker"
                     onclick="set_attr_def_date_radio('fixed')"
                     onchange="set_attr_def_value('def_date_val_fixed')"
                />
              <br />
              <input name="def_date_type"
                     id="def_date_next"
                     type="radio"
                     value="next"
                     onchange="set_attr_def_from_date('next')"
                />
              <?php print _txt('fd.ed.date.next'); ?>
              <input name="def_date_val_next"
                     id="def_date_val_next"
                     size="12"
                     class="datepicker-m"
                     onclick="set_attr_def_date_radio('next')"
                     onchange="set_attr_def_value('def_date_val_next')"
                />
              <?php print _txt('fd.ed.date.next-note'); ?>
              <br />
              <input name="def_date_type"
                     id="def_date_offset"
                     size="6"
                     type="radio"
                     value="offset"
                     onchange="set_attr_def_from_date('offset')"
                />
              <input name="def_date_val_offset"
                     id="def_date_val_offset"
                     size="4"
                     onclick="set_attr_def_date_radio('offset')"
                     onkeyup="set_attr_def_value('def_date_val_offset')"
                />
              <?php print _txt('fd.ed.date.offset'); ?>
              <br />
            <?php endif; ?>
          </div>
          <div id="attr_def_val_enum_div">
            <?php if($e): ?>
              <select name="def_enum_val"
                      id="def_enum_val"
                      onchange="set_attr_def_value('def_enum_val')">
                <option value=""></option>
              </select>
            <?php endif; ?>
          </div>
          <div id="attr_def_val_group_div">
            <?php if($e): ?>
              <select name="def_group_val"
                      id="def_group_val"
                      onchange="set_attr_def_value('def_group_val')">
                <option value=""></option>
                <?php foreach(array_keys($vv_groups) as $k): ?>
                  <option value="<?php print filter_var($k,FILTER_SANITIZE_SPECIAL_CHARS); ?>">
                    <?php print filter_var($vv_groups[$k],FILTER_SANITIZE_SPECIAL_CHARS); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            <?php endif; ?>
          </div>
          <div id="attr_def_val_sponsor_div">
            <?php if($e && $vv_sponsor_mode != SponsorEligibilityEnum::None): ?>
              <?php if(isset($vv_sponsors)): ?>
                <!-- Use a simple dropdown -->
                <select name="def_sponsor_val"
                        id="def_sponsor_val"
                        onchange="set_attr_def_value('def_sponsor_val')">
                  <option value=""></option>
                  <?php foreach(array_keys($vv_sponsors) as $k): ?>
                    <option value="<?php print filter_var($k,FILTER_SANITIZE_SPECIAL_CHARS); ?>">
                      <?php print filter_var($vv_sponsors[$k],FILTER_SANITIZE_SPECIAL_CHARS); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              <?php else: // vv_sponsors ?>
                <!-- Use a people picker -->
                <div class="cm-inline-editable-field">
                  <?php
                    $inlineEditableFields['sponsor-val']['set'] = false;

                    if(!empty($vv_sponsor)) {
                      $inlineEditableFields['sponsor-val']['set'] = true;
                      print '<div class="cm-ief-val">';

                      print $this->Html->link(
                        generateCn($vv_sponsor['PrimaryName']),
                        array(
                          'controller' => 'co_people',
                          'action' => 'canvas',
                          $vv_sponsor['CoPerson']['id']
                        )
                      );

                      print $this->element('inlineEditControls', array(
                        'fieldName' => 'sponsor_co_person_id',
                        'actions' => array('remove')
                      ));
                      print '</div>';
                    }

                    $iefClasses = "cm-ief-widget";
                    if ($inlineEditableFields['sponsor-val']['set']) {
                      $iefClasses .= " hidden";
                    }
                  ?>
                  <div class="<?php print $iefClasses; ?>">
                      <div class="ui-widget">
                        <input id="sponsors" type="text" />
                        <input type="hidden" name="def_sponsor_val" id="def_sponsor_val" />
                      </div>
                      <div class="field-desc">
                        <span class="ui-icon ui-icon-info co-info"></span>
                        <em><?php print _txt('fd.sponsor.desc'); ?></em>
                      </div>
                    <?php endif; // vv_sponsors ?>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </td>
      </tr>
      <tr class="line<?php print ($l % 2); $l++; ?>">
        <td>
          <b><?php print _txt('fd.ed.modify'); ?></b><br />
          <span class="descr"><?php print _txt('fd.ed.modify.desc'); ?></span>
        </td>
        <td>
          <?php print ($e
            ? $this->Form->input('CoEnrollmentAttributeDefault.0.modifiable',
              array('default' => true))
            : ($co_enrollment_attributes[0]['CoEnrollmentAttributeDefault'][0]['modifiable']
              ? _txt('fd.yes') : _txt('fd.no'))); ?>
        </td>
      </tr>
      <tr class="line<?php print ($l % 2); $l++; ?>">
        <td>
          <b><?php print _txt('fd.hidden'); ?></b><br />
          <span class="descr"><?php print _txt('fd.hidden.desc'); ?></span>
        </td>
        <td>
          <?php // Note this is a CoEnrollmentAttribute field, not CoEnrollmentAttributeDefault
          print ($e
            ? $this->Form->input('hidden', array('default' => false))
            : ($co_enrollment_attributes[0]['CoEnrollmentAttribute']['hidden']
              ? _txt('fd.yes') : _txt('fd.no'))); ?>
        </td>
      </tr>
    </tbody>
    <tfoot>
      <tr>
        <td></td>
        <td>
          <?php
          if($e) {
            print $this->Form->submit($submit_label);
          }
          ?>
        </td>
      </tr>
    </tfoot>
  </table>
</div>

<?php print $this->element("changelog"); ?>
