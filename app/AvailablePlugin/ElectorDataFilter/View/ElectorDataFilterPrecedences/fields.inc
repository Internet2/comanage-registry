<?php
/**
 * COmanage Registry Elector Data Filter Precedences Fields
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
 * @since         COmanage Registry v0.1
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

// Determine if fields are editable
$e = false;

if(($this->action == "add" && $permissions['add']) || ($this->action == "edit" && $permissions['edit']))
  $e = true;

// We shouldn't get here if we don't have at least read permission, but check just in case

if(!$e && !$permissions['view'])
  return(false);

$elector_data_filter_id = isset($elector_data_filters[0]["ElectorDataFilter"]["id"])
                           ? $elector_data_filters[0]["ElectorDataFilter"]["id"]
                           : $elector_data_filter_precedences[0]["ElectorDataFilter"]["id"];

$data_filter_id = isset($elector_data_filters[0]["ElectorDataFilter"]["id"])
                  ? $elector_data_filters[0]["DataFilter"]["id"]
                  : $elector_data_filter_precedences[0]["ElectorDataFilter"]["DataFilter"]["id"];

$elector_data_filter_attribute_name = isset($elector_data_filters[0]["ElectorDataFilter"]["id"])
  ? $elector_data_filters[0]["ElectorDataFilter"]["attribute_name"]
  : $elector_data_filter_precedences[0]["ElectorDataFilter"]["attribute_name"];

$ata_filter_description = isset($elector_data_filters[0]['DataFilter']['description'])
                                ? $elector_data_filters[0]['DataFilter']['description']
                                : $elector_data_filter_precedences[0]["ElectorDataFilter"]["DataFilter"]["description"];

// We can emit all attributes because the ones we don't want should be null
print $this->Form->hidden('elector_data_filter_id', array('default' => $elector_data_filter_id)) . PHP_EOL;

//if(isset($cur_co))
//  print $this->Form->hidden('co_id', array('default' => $cur_co['Co']['id'])) . PHP_EOL;

// Add breadcrumbs
print $this->element("coCrumb");
// Data filters index view
$args = array();
$args['plugin'] = null;
$args['controller'] = 'data_filters';
$args['action'] = 'index';
$args['co'] = $cur_co['Co']['id'];
$this->Html->addCrumb(_txt('ct.data_filters.pl'), $args);

// Elector Data filter edit view
$args = array();
$args['plugin'] = null;
$args['controller'] = 'data_filters';
$args['action'] = 'edit';
$args[] = $data_filter_id;
$this->Html->addCrumb($ata_filter_description, $args);

// Elector Datafilter configuration view
$args = array();
$args['plugin'] = 'elector_data_filter';
$args['controller'] = 'elector_data_filters';
$args['action'] = 'edit';
$args[] = $elector_data_filter_id;
$this->Html->addCrumb(_txt('pl.elector_data_filter_precedence.precedence',
                           array($elector_data_filter_attribute_name)
                      ),
                      $args);

// Action crumb
if($this->action == "add") {
  $crumbTxt = _txt('op.add');
} elseif ($this->action == 'edit') {
  $crumbTxt = _txt('op.edit');
} else {
  $crumbTxt = _txt('op.view');
}
$this->Html->addCrumb($crumbTxt);
?>
  <ul id="<?php print $this->action; ?>_data_filter_preferences" class="fields form-list">
    <li>
      <div class="field-name">
        <div class="field-title">
          <?php print ($e ? $this->Form->label('inbound_attribute_type', _txt('pl.elector_data_filter_precedence.inbound_attribute_type'))
                          : _txt('pl.elector_data_filter_precedence.inbound_attribute_type'));
          ?>
          <span class="required">*</span>
        </div>
        <div class="field-desc"><?php print _txt('pl.elector_data_filter_precedence.inbound_attribute_type.desc'); ?></div>
      </div>
      <div class="field-info">
        <?php
        $attrs = array();
        $attrs['value'] = (isset($elector_data_filter_precedences[0]['ElectorDataFilterPrecedences']['inbound_attribute_type'])
          ? $elector_data_filter_precedences[0]['ElectorDataFilterPrecedences']['inbound_attribute_type']
          : null);
        $attrs['empty'] = true;
        $attrs['required'] = true;

        if($e) {
          print $this->Form->select('inbound_attribute_type',
                                    $vv_attribute_types,
                                    $attrs);

          if($this->Form->isFieldError('inbound_attribute_type')) {
            print $this->Form->error('inbound_attribute_type');
          }
        } else {
          if(!empty($elector_data_filter_precedences[0]['ElectorDataFilterPrecedences']['inbound_attribute_type'])) {
            print $vv_attribute_types[ $elector_data_filter_precedences[0]['ElectorDataFilterPrecedences']['inbound_attribute_type'] ];
          }
        }
        ?>
      </div>
    </li>
    <li>
      <div class="field-name">
        <div class="field-title">
          <?php print ($e ? $this->Form->label('org_identity_source_id', _txt('ct.org_identity_sources.1')) : _txt('ct.org_identity_source.1')); ?>
        </div>
        <div class="field-desc"><?php print _txt('pl.elector_data_filter_precedence.plugin.desc'); ?></div>
      </div>
      <div class="field-info">
        <?php
        global $cm_lang, $cm_texts;

        $attrs = array();
        $attrs['value'] = !empty($elector_data_filter_precedences[0]['ElectorDataFilterPrecedences']['org_identity_source_id'])
                          ? $elector_data_filter_precedences[0]['ElectorDataFilterPrecedences']['org_identity_source_id']
                          : null;
        $attrs['empty'] = true;

        if($e) {
          print $this->Form->select('org_identity_source_id',
                                    $vv_avail_ois,
                                    $attrs);

          if($this->Form->isFieldError('org_identity_source_id')) {
            print $this->Form->error('org_identity_source_id');
          }
        } else {
          print $vv_avail_ois[ $elector_data_filter_precedences[0]['ElectorDataFilterPrecedences']['org_identity_source_id'] ];
        }
        ?>
      </div>
    </li>
    <li>
      <div class="field-name">
        <div class="field-title">
          <?php print ($e ? $this->Form->label('ordr', _txt('fd.order')) : _txt('fd.order')); ?>
          <span class="required">*</span>
        </div>
        <div class="field-desc"><?php print _txt('pl.elector_data_filter_precedence.order.desc'); ?></div>
      </div>
      <div class="field-info">
        <?php print ($e
          ? $this->Form->input('ordr')
          : filter_var($elector_data_filter_precedences[0]['ElectorDataFilterPrecedences']['ordr'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
      </div>
    </li>
    <?php if($e): ?>
      <li class="fields-submit">
      <span class="field-name">
        <span class="required"><?php print _txt('fd.req'); ?></span>
      </span>
        <span class="field-info">
        <?php print $this->Form->submit($submit_label); ?>
      </span>
      </li>
    <?php endif; ?>
  </ul>
<?php
print $this->element("changelog");
