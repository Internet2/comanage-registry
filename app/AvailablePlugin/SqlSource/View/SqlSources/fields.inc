<!--
/**
 * COmanage Registry SQL Source Plugin Fields
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
  
  // Add breadcrumbs
  print $this->element("coCrumb");

  $args = array();
  $args['plugin'] = null;
  $args['controller'] = 'org_identity_sources';
  $args['action'] = 'index';
  $args['co'] = $cur_co['Co']['id'];
  $this->Html->addCrumb(_txt('ct.org_identity_sources.pl'), $args);

  $args = array();
  $args['plugin'] = null;
  $args['controller'] = 'org_identity_sources';
  $args['action'] = 'edit';
  $args[] = $vv_oisid;
  $this->Html->addCrumb($sql_sources[0]['OrgIdentitySource']['description'], $args);

  $crumbTxt = _txt('op.config');
  $this->Html->addCrumb($crumbTxt);
  
  print $this->Form->hidden('org_identity_source_id', array('default' => $vv_oisid)) . "\n";
?>
<script type="text/javascript">
  <!-- JS specific to these fields -->
  
  function mode_update_gadgets() {
    var tablemode = document.getElementById('SqlSourceTableMode').value;
    
    if(tablemode == '<?php print SqlSourceTableModeEnum::Flat; ?>') {
      document.getElementById('flattypes').style.display = "block";
    } else {
      document.getElementById('flattypes').style.display = "none";
    }
  }
  
  function js_local_onload() {
    mode_update_gadgets();
  }
</script>
<ul id="<?php print $this->action; ?>_sql_source" class="fields form-list form-list-admin">
  <li>
    <div class="field-name">
      <?php print ($e ? $this->Form->label('server_id', _txt('fd.server')) : _txt('fd.server')); ?>
      <span class="required">*</span>
    </div>
    <div class="field-info">
      <?php
        $attrs = array();
        $attrs['value'] = (isset($sql_sources[0]['SqlSource']['server_id'])
                           ? $sql_sources[0]['SqlSource']['server_id']
                           : null);
        // Since required=true, this will render blank by default but not allow save until a server is selected
        $attrs['empty'] = true;

        if($e) {
          print $this->Form->select('server_id',
                                    $vv_servers,
                                    $attrs);

          if($this->Form->isFieldError('server_id')) {
            print $this->Form->error('server_id');
          }
        } else {
          if(!empty($sql_sources[0]['SqlSource']['server_id'])) {
            print filter_var($vv_servers[ $sql_sources[0]['SqlSource']['server_id'] ], FILTER_SANITIZE_SPECIAL_CHARS);
          }
        }
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print ($e ? $this->Form->label('table_mode', _txt('pl.sqlsource.table_mode')) : _txt('pl.sqlsource.table_mode')); ?>
        <span class="required">*</span>
      </div>
    </div>
    <div class="field-info">
      <?php
        global $cm_lang, $cm_texts;
        $attrs = array();
        $attrs['value'] = (!empty($sql_sources[0]['SqlSource']['table_mode']) ? $sql_sources[0]['SqlSource']['table_mode'] : "");
        $attrs['empty'] = true;
        $attrs['onChange'] = 'mode_update_gadgets()';

        if($e) {
          print $this->Form->select('table_mode',
                                    $cm_texts[ $cm_lang ]['pl.sqlsource.en.mode.table'],
                                    $attrs);

          if($this->Form->isFieldError('table_mode')) {
            print $this->Form->error('table_mode');
          }
        } else {
          print _txt('pl.apisource.en.mode.poll', null, $sql_sources[0]['SqlSource']['table_mode']);
        }
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <?php print $this->Form->label('source_table', _txt('pl.sqlsource.source_table')); ?>
      <span class="required">*</span>
    </div>
    <div class="field-info">
      <?php print ($e ? $this->Form->input('source_table', array('class' => 'focusFirst')) : filter_var($sql_sources[0]['SqlSource']['source_table'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <div id="flattypes">
  <?php foreach(array(
    'name'              => NameEnum::Official,
    'address'           => ContactEnum::Office,
    'email_address'     => EmailAddressEnum::Official,
    'identifier'        => IdentifierEnum::National,
    'telephone_number'  => ContactEnum::Office,
    'url'               => UrlEnum::Official
  ) as $m => $mt): ?>
  <li>
    <?php
      $ms = Inflector::pluralize($m);
      $typeVar = 'vv_' . $ms . '_types';
    ?>
    <span class="field-name">
      <?php
      if($e) {
        print $this->Form->label($m.'_type',_txt('pl.sqlsource.field.type', array(_txt('ct.'.$ms.'.1'))));
      } else {
        print _txt('pl.sqlsource.field.type', array(_txt('ct.'.$ms.'.1')));
      }
      ?>
      <span class="required">*</span>
    </span>
    <span class="field-info">
      <?php
        $attrs = array();
        $attrs['value'] = (isset($sql_sources[0]['SqlSource'][$m.'_type'])
                           ? $sql_sources[0]['SqlSource'][$m.'_type']
                           : $mt);
        $attrs['empty'] = false;

        if($e) {
          print $this->Form->select($m.'_type',
                                    $$typeVar,
                                    $attrs);

          if($this->Form->isFieldError($m.'_type')) {
            print $this->Form->error($m.'_type');
          }
        } else {
          print $$typeVar[ $sql_sources[0]['SqlSource'][$m.'_type'] ];
        }
      ?>
    </span>
  </li>
<?php endforeach; // $m $ms ?>
</div>
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
