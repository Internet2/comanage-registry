<!--
/**
 * COmanage Registry Elector Data Filter Plugin Fields
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
 * @since         COmanage Registry v4.1.0
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
$args[] = $elector_data_filters[0]['DataFilter']['id'];
$this->Html->addCrumb($elector_data_filters[0]['DataFilter']['description'], $args);

$crumbTxt = _txt('op.config');
$this->Html->addCrumb($crumbTxt);

print $this->Form->hidden('data_filter_id', array('default' => $elector_data_filters[0]['DataFilter']['id'])) . "\n";
?>
<script type="text/javascript">
  function js_local_onload() {
    update_type_select();

    var curtype = "<?php
      print !empty($elector_data_filters[0]['ElectorDataFilter']['outbound_attribute_type'])
            ? $elector_data_filters[0]['ElectorDataFilter']['outbound_attribute_type']
            : "";
      ?>";

    document.getElementById('ElectorDataFilterOutboundAttributeType').value = curtype;
  }

  function enum_for_attr(attrname) {
    // Determine if an attribute has defined types. We use PHP code to
    // generate javascript based on the view variable $vv_attribute_types.

    var ret = null;

    switch(attrname) {
    <?php foreach($vv_attribute_types as $attr => $types): ?>
      case "<?php print $attr; ?>":
        ret = <?php
        //  Just sanitize the values
        foreach($types as $key => $val) {
          $types[$key] = filter_var($val, FILTER_SANITIZE_SPECIAL_CHARS);
        }
        print json_encode($types);
        ?>;
        break;
    <?php endforeach; ?>
    }

    return ret;
  }

  function update_type_select() {
    // The currently selected Attribute
    var attr = document.getElementById('ElectorDataFilterAttributeName').value;

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
    $("#ElectorDataFilterOutboundAttributeType").find('option').remove().end().append($(options));

    if(values) {
      $("#ElectorDataFilterOutboundAttributeType").closest("li").show('fade');
    } else {
      $("#ElectorDataFilterOutboundAttributeType").closest("li").hide('fade');
    }
  }
</script>
<div id="<?php print $this->action; ?>_elector_data_filter" class="explorerContainer">
  <div id="coOrgIdExplorer" class="personExplorer">
    <ul>
      <!-- Department Info -->
      <li id="fields-name" class="fieldGroup">
        <a href="#tabs-name" class="fieldGroupName">
          <em class="material-icons">indeterminate_check_box</em>
          <?php print _txt('ct.elector_data_filters.1'); ?>
        </a>

        <div id="names-container" class="fields">
          <ul  id="tabs-name-fields" class="fields form-list">
            <li>
              <div class="field-name">
                <div class="field-title">
                  <?php print _txt('pl.elector_data_filter.attribute_name'); ?>
                  <span class="required">*</span>
                </div>
                <div class="field-desc"><?php print _txt('pl.elector_data_filter.attribute_name.desc'); ?></div>
              </div>
              <div class="field-info">
                <?php
                $attrs = array();
                $attrs['value'] = (isset($elector_data_filters[0]['ElectorDataFilter']['attribute_name'])
                  ? $elector_data_filters[0]['ElectorDataFilter']['attribute_name']
                  : null);
                $attrs['empty'] = true;
                $attrs['required'] = true;
                $attrs['onChange'] = 'update_type_select()';

                if($e) {
                  print $this->Form->select('attribute_name',
                                            $vv_attribute_names,
                                            $attrs);

                  if($this->Form->isFieldError('attribute_name')) {
                    print $this->Form->error('attribute_name');
                  }
                } else {
                  if(!empty($elector_data_filters[0]['ElectorDataFilter']['attribute_name'])) {
                    print $vv_attribute_names[ $elector_data_filters[0]['ElectorDataFilter']['attribute_name'] ];
                  }
                }
                ?>
              </div>
            </li>
            <li>
              <div class="field-name">
                <div class="field-title">
                  <?php print _txt('pl.elector_data_filter.outbound_attribute_type'); ?>
                  <span class="required">*</span>
                </div>
                <div class="field-desc"><?php print _txt('pl.elector_data_filter.outbound_attribute_type.desc'); ?></div>
              </div>
              <div class="field-info">
                <?php
                $attrs = array();
                $attrs['value'] = (isset($elector_data_filters[0]['ElectorDataFilter']['outbound_attribute_type'])
                  ? $elector_data_filters[0]['ElectorDataFilter']['outbound_attribute_type']
                  : null);
                $attrs['empty'] = true;
                $attrs['required'] = true;

                if($e) {
                  print $this->Form->select('outbound_attribute_type',
                                            array(),
                                            $attrs);

                  if($this->Form->isFieldError('outbound_attribute_type')) {
                    print $this->Form->error('outbound_attribute_type');
                  }
                } else {
                  if(!empty($elector_data_filters[0]['ElectorDataFilter']['outbound_attribute_type'])) {
                    print $vv_attribute_types[ $elector_data_filters[0]['ElectorDataFilter']['outbound_attribute_type'] ];
                  }
                }
                ?>
              </div>
            </li>
            <li>
              <div class="field-name">
                <div class="field-title">
                  <?php print _txt('pl.elector_data_filter.tie_break_mode'); ?>
                  <span class="required">*</span>
                </div>
                <div class="field-desc"><?php print _txt('pl.elector_data_filter.tie_break_mode.desc'); ?></div>
              </div>
              <div class="field-info">
                <?php
                global $cm_lang, $cm_texts;
                $attrs = array();
                $attrs['value'] = (isset($elector_data_filters[0]['ElectorDataFilter']['tie_break_mode'])
                  ? $elector_data_filters[0]['ElectorDataFilter']['tie_break_mode']
                  : null);
                $attrs['empty'] = true;
                $attrs['required'] = true;

                if($e) {
                  print $this->Form->select('tie_break_mode',
                                            $cm_texts[ $cm_lang ]['en.elector_data_filter.tie.break.mode'],
                                            $attrs);

                  if($this->Form->isFieldError('tie_break_mode')) {
                    print $this->Form->error('tie_break_mode');
                  }
                } else {
                  if(!empty($elector_data_filters[0]['ElectorDataFilter']['tie_break_mode'])) {
                    print $cm_texts[ $cm_lang ]['en.elector_data_filter.tie.break.mode'][ $attrs['value'] ];
                  }
                }
                ?>
              </div>
            </li>
            <li>
              <div class="field-name">
                <div class="field-title">
                  <?php print _txt('pl.elector_data_filter.replacement_mode'); ?>
                  <span class="required">*</span>
                </div>
                <div class="field-desc"><?php print _txt('pl.elector_data_filter.replacement_mode.desc'); ?></div>
              </div>
              <div class="field-info">
                <?php
                global $cm_lang, $cm_texts;
                $attrs = array();
                $attrs['value'] = (isset($elector_data_filters[0]['ElectorDataFilter']['replacement_mode'])
                  ? $elector_data_filters[0]['ElectorDataFilter']['replacement_mode']
                  : null);
                $attrs['empty'] = true;
                $attrs['required'] = true;

                if($e) {
                  print $this->Form->select('replacement_mode',
                                            $cm_texts[ $cm_lang ]['en.elector_data_filter.replacement_mode'],
                                            $attrs);

                  if($this->Form->isFieldError('replacement_mode')) {
                    print $this->Form->error('replacement_mode');
                  }
                } else {
                  if(!empty($elector_data_filters[0]['ElectorDataFilter']['replacement_mode'])) {
                    print $cm_texts[ $cm_lang ]['en.elector_data_filter.replacement_mode'][ $attrs['value'] ];
                  }
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
        </div>
      </li><!-- fields-name -->

      <?php
      if($this->action != "add"
         && !is_null($elector_data_filters[0]["ElectorDataFilter"]["attribute_name"])) {
        $args = array(
          'edit'         => $e,
          'model'        => 'ElectorDataFilter',
          'model_param'  => 'electfilterid',
          'mvep_model'   => 'ElectorDataFilterPrecedence',
          'mvep_field'   => 'inbound_attribute_type'
        );

        print $this->element('mvep', $args);
      }
      ?>

  </div> <!-- personExplorer -->
  <?php print $this->element("changelog"); ?>
</div> <!-- explorerContainer -->