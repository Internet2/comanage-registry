<!--
/**
 * COmanage Registry CMP Enrollment Attribute Fields
 *
 * Copyright (C) 2011-13 University Corporation for Advanced Internet Development, Inc.
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
 * @copyright     Copyright (C) 2011-13 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.3
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */
-->
<script type="text/javascript">
  function js_local_onload() {
    toggle_attr_def_div();
    toggle_attr_type_div();
    toggle_copy_to_coperson_div();
  }
  
  function ext_attr_type(attrname) {
    // Determine the type of an extended attribute. We use PHP code to generate
    // javascript based on the view variable $vv_ext_attr_types.
    
    switch(attrname) {
    <?php foreach(array_keys($vv_ext_attr_types) as $xa): ?>
      case "<?php print $xa; ?>":
        return "<?php print $vv_ext_attr_types[$xa]; ?>";
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
    document.getElementById('CoEnrollmentAttributeDefault0Value').value =
      (elementid == "def_date_val_offset" ? "+" : "") +
      document.getElementById(elementid).value
  }
  
  function toggle_attr_def_div() {
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
      $("#attr_def_val_group_div").hide();
      $("#attr_def_val_sponsor_div").hide();
      
      var curval = document.getElementById('CoEnrollmentAttributeDefault0Value').value;
      
      var curattrarr = curattr.split(":");
      var curattrcode = curattrarr[0];
      var curattrname = curattrarr[1];
      
      // if x:foo then pull foo and lookup in $vv_ext_attr_types to find type
      // (write javascript array based on $vv_ext contents?)
      
      if((curattrcode == "o" || curattrcode == "r")
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
        $("#attr_def_val_div").show();
      }
    } else {
      $("#attr_def_div").hide("fade", { "direction" : "up" });
    }
  }
  
  function toggle_attr_type_div() {
    var curattr = document.getElementById('CoEnrollmentAttributeAttribute').value;
    
    var curattrarr = curattr.split(":");
    var curattrcode = curattrarr[0];
    var curattrname = curattrarr[1];
    
    if(curattrname == "address" || curattrname == "name") {
      $("#type_div").show("fade");
    } else {
      $("#type_div").hide("fade");
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
</script>
<?php
  // Determine if fields are editable
  $e = false;
  
  if(($this->action == "add" && $permissions['add']) || ($this->action == "edit" && $permissions['edit']))
    $e = true;
    
  // We shouldn't get here if we don't have at least read permission, but check just in case
  
  if(!$e && !$permissions['view'])
    return(false);
    
  // Add buttons to sidebar
  $sidebarButtons = $this->get('sidebarButtons');

  $this->set('sidebarButtons', $sidebarButtons);
  
  // Populate cross references
  print $this->Form->hidden('co_enrollment_flow_id', array('default' => $vv_coefid)) . "\n";
  print $this->Form->hidden('CoEnrollmentAttributeDefault.0.id') . "\n";
  
  $l = 1;
?>
<table id="<?php print $this->action; ?>_co_enrollment_attribute" class="ui-widget">
  <tbody>
    <tr class="line<?php print ($l % 2); $l++; ?>">
      <td width="50%"> <!-- force a width here and below so the two tables line up -->
        <b><?php print _txt('fd.ea.label'); ?></b><br />
        <font class="desc"><?php print _txt('fd.ea.label.desc'); ?></font>
      </td>
      <td>
        <?php print ($e
                     ? $this->Form->input('label', array('default' => _txt('fd.untitled')))
                     : Sanitize::html($co_enrollment_attributes[0]['CoEnrollmentAttribute']['label'])); ?>
      </td>
    </tr>
    <tr class="line<?php print ($l % 2); $l++; ?>">
      <td>
        <b><?php print _txt('fd.ea.desc'); ?></b><br />
        <font class="desc"><?php print _txt('fd.ea.desc.desc'); ?></font>
      </td>
      <td>
        <?php print ($e
                     ? $this->Form->input('description', array('size' => 40))
                     : Sanitize::html($co_enrollment_attributes[0]['CoEnrollmentAttribute']['description'])); ?>
      </td>
    </tr>
    <tr class="line<?php print ($l % 2); $l++; ?>">
      <td>
        <b><?php print _txt('fd.attribute'); ?></b>
      </td>
      <td>
        <?php
          $attrs['value'] = (isset($co_enrollment_attributes)
                              ? $co_enrollment_attributes[0]['CoEnrollmentAttribute']['attribute']
                              : null);
          $attrs['empty'] = false;
          $attrs['onchange'] = "toggle_attr_def_div();toggle_copy_to_coperson_div();toggle_attr_type_div();";
          
          if($e) {
            print $this->Form->select('attribute',
                                      $vv_available_attributes,
                                      $attrs);
            
            if($this->Form->isFieldError('attribute')) {
              print $this->Form->error('status');
            }
          } else {
            print Sanitize::html($co_enrollment_attributes[0]['CoEnrollmentAttribute']['attribute']);
          }
        ?>
        <div id="type_div" style="display: none">
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
        <div id="copy_to_coperson_div" style="display: none">
          <?php print ($e
                       ? $this->Form->input('copy_to_coperson',
                                            array('default' => false))
                       : ($co_enrollment_attributes[0]['CoEnrollmentAttribute'][0]['copy_to_coperson']
                          ? _txt('fd.yes') : _txt('fd.no')));
                print _txt('fd.ea.attr.copy2cop');
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
                                           : RequiredEnum::Optional);
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
        <font class="desc"><?php print _txt('fd.ea.ignauth.desc'); ?></font>
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
        <b><?php print _txt('fd.ea.order'); ?></b><br />
        <font class="desc"><?php print _txt('fd.ea.order.desc'); ?></font>
      </td>
      <td>
        <?php print ($e
                     ? $this->Form->input('ordr')
                     : Sanitize::html($co_enrollment_attributes[0]['CoEnrollmentAttribute']['ordr'])); ?>
      </td>
    </tr>
  </tbody>
</table>
<div id="attr_def_div" style="display: none">
  <table id="<?php print $this->action; ?>_co_enrollment_attribute_default" class="ui-widget">
  <tbody>
    <tr class="line<?php print ($l % 2); $l++; ?>">
      <td width="50%">
        <b><?php print _txt('fd.ed.default'); ?></b><br />
      </td>
      <td>
        <div id="attr_def_val_div">
          <?php print ($e
                       ? $this->Form->input('CoEnrollmentAttributeDefault.0.value', array('size' => 40))
                       : Sanitize::html($co_enrollment_attributes[0]['CoEnrollmentAttributeDefault'][0]['value'])); ?>
        </div>
        <div id="attr_def_val_affil_div">
          <?php if($e): ?>
            <select name="def_affil_val"
                    id="def_affil_val"
                    onchange="set_attr_def_value('def_affil_val')">
              <option value=""></option>
              <?php foreach(array_keys($vv_affiliations) as $k): ?>
                <option value="<?php print Sanitize::html($k); ?>"><?php print Sanitize::html($vv_affiliations[$k]); ?></option>
              <?php endforeach; ?>
            </select>
          <?php endif; ?>
        </div>
        <div id="attr_def_val_cou_div">
          <?php if($e): ?>
            <select name="def_cou_val"
                    id="def_cou_val"
                    onchange="set_attr_def_value('def_cou_val')">
              <option value=""></option>
              <?php foreach(array_keys($vv_cous) as $k): ?>
                <option value="<?php print Sanitize::html($k); ?>"><?php print Sanitize::html($vv_cous[$k]); ?></option>
              <?php endforeach; ?>
            </select>
          <?php endif; ?>
        </div>
        <div id="attr_def_val_date_div">
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
        <div id="attr_def_val_group_div">
          <?php if($e): ?>
            <select name="def_group_val"
                    id="def_group_val"
                    onchange="set_attr_def_value('def_group_val')">
              <option value=""></option>
              <?php foreach(array_keys($vv_groups) as $k): ?>
                <option value="<?php print Sanitize::html($k); ?>"><?php print Sanitize::html($vv_groups[$k]); ?></option>
              <?php endforeach; ?>
            </select>
          <?php endif; ?>
        </div>
        <div id="attr_def_val_sponsor_div">
          <?php if($e): ?>
            <select name="def_sponsor_val"
                    id="def_sponsor_val"
                    onchange="set_attr_def_value('def_sponsor_val')">
              <option value=""></option>
              <?php foreach(array_keys($vv_sponsors) as $k): ?>
                <option value="<?php print Sanitize::html($k); ?>"><?php print Sanitize::html($vv_sponsors[$k]); ?></option>
              <?php endforeach; ?>
            </select>
          <?php endif; ?>
        </div>
      </td>
    </tr>
    <tr class="line<?php print ($l % 2); $l++; ?>">
      <td>
        <b><?php print _txt('fd.ed.modify'); ?></b><br />
        <font class="desc"><?php print _txt('fd.ed.modify.desc'); ?></font>
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
        <font class="desc"><?php print _txt('fd.hidden.desc'); ?></font>
      </td>
      <td>
        <?php // Note this is a CoEnrollmentAttribute field, not CoEnrollmentAttributeDefault
              print ($e
                     ? $this->Form->input('hidden', array('default' => false))
                     : ($co_enrollment_attributes[0]['CoEnrollmentAttribute']['hidden']
                        ? _txt('fd.yes') : _txt('fd.no'))); ?>
      </td>
    </tr>
  </table>
</div>
<?php
  if($e) {
    print $this->Form->submit($submit_label, array('onClick' => 'on_submit()'));
    print $this->Form->button(_txt('op.reset'), 
                              array('type'=>'reset'));
  }
?>
