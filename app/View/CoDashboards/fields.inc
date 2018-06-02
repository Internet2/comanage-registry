<?php
/**
 * COmanage Registry CO Dashboard Fields
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
 * @since         COmanage Registry v3.2.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */
?>
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
  $args['controller'] = 'co_dashboards';
  $args['action'] = 'index';
  $args['co'] = $cur_co['Co']['id'];
  $this->Html->addCrumb(_txt('ct.co_dashboards.pl'), $args);
  $crumbTxt = _txt('op.' . $this->action . '-a', array(_txt('ct.co_dashboards.1')));
  $this->Html->addCrumb($crumbTxt);

  print $this->Form->hidden('co_id', array('default' => $cur_co['Co']['id'])) . "\n";
  
  $l = 1;
?>
<script type="text/javascript">
  <!-- JS specific to these fields -->

  function visibility_update_gadgets() {
    // Hide and show accordingly.

    var visibility = document.getElementById('CoDashboardVisibility').value;

    if(visibility == '<?php print VisibilityEnum::CoGroupMember; ?>') {
      document.getElementById('visibilitygroupdiv').style.display = "block";
    } else {
      document.getElementById('visibilitygroupdiv').style.display = "none";
    }
  }
  
  function js_local_onload() {
    visibility_update_gadgets();
  }
</script>
<ul id="<?php print $this->action; ?>_co_dashboards" class="fields form-list">
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print ($e ? $this->Form->label('name', _txt('fd.name')) : _txt('fd.name')); ?>
        <span class="required">*</span>
      </div>  
    </div>
    <div class="field-info">
      <?php print ($e
                   ? $this->Form->input('name', array('class' => 'focusFirst'))
                   : filter_var($co_dashboards[0]['CoDashboard']['name'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print ($e ? $this->Form->label('description', _txt('fd.desc')) : _txt('fd.desc')); ?>
      </div>
    </div>
    <div class="field-info">
      <?php print ($e
                   ? $this->Form->input('description')
        : filter_var($co_dashboards[0]['CoDashboard']['description'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print ($e ? $this->Form->label('status', _txt('fd.status')) : _txt('fd.status')); ?>
        <span class="required">*</span>
      </div>
    </div>
    <div class="field-info">
      <?php
        global $cm_lang, $cm_texts;
        $attrs = array();
        $attrs['value'] = (isset($co_dashboards[0]['CoDashboard']['status'])
                           ? $co_dashboards[0]['CoDashboard']['status']
                           : SuspendableStatusEnum::Active);
        $attrs['empty'] = false;

        if($e) {
          print $this->Form->select('status',
                                    $cm_texts[ $cm_lang ]['en.status.susp'],
                                    $attrs);

          if($this->Form->isFieldError('status')) {
            print $this->Form->error('status');
          }
        } else {
          print _txt('en.status', null, $co_dashboards[0]['CoDashboard']['status']);
        }
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print ($e ? $this->Form->label('visibility', _txt('fd.visibility')) : _txt('fd.visibility')); ?>
        <span class="required">*</span>
      </div>
    </div>
    <div class="field-info">
      <?php
        global $cm_lang, $cm_texts;
        $attrs = array();
        $attrs['value'] = (isset($co_dashboards[0]['CoDashboard']['visibility'])
                           ? $co_dashboards[0]['CoDashboard']['visibility']
                           : VisibilityEnum::CoMember);
        $attrs['empty'] = false;
        $attrs['onchange'] = "visibility_update_gadgets();";

        if($e) {
          print $this->Form->select('visibility',
                                    $cm_texts[ $cm_lang ]['en.visibility'],
                                    $attrs);

          if($this->Form->isFieldError('visibility')) {
            print $this->Form->error('visibility');
          }
        } else {
          print _txt('en.visibility', null, $co_dashboards[0]['CoDashboard']['visibility']);
        }
      ?>
      <div id="visibilitygroupdiv" class="subfield" style="display:none">
        <?php
          if(!empty($vv_co_groups)) {
            $attrs = array();
            $attrs['value'] = (!empty($co_dashboards[0]['CoDashboard']['visibility_co_group_id'])
                               ? $co_dashboards[0]['CoDashboard']['visibility_co_group_id']
                               : null);
            $attrs['empty'] = true;

            if($e) {
              print $this->Form->label('visibility_co_group_id', _txt('fd.dashboard.visibility.group'));
              print $this->Form->select('visibility_co_group_id',
                                        $vv_co_groups,
                                        $attrs);

              if($this->Form->isFieldError('visibility_co_group_id')) {
                print $this->Form->error('visibility_co_group_id');
              }
            } else {
              print $vv_co_groups[ $co_dashboards[0]['CoDashboard']['visibility_co_group_id'] ];
            }
          }
        ?>
      </div>
    </div>
  </li>
  <?php if($e): ?>
    <li class="fields-submit">
      <div class="field-name">
        <span class="required"><?php print _txt('fd.req'); ?></span>
      </div>
      <div class="field-info">
        <?php print $this->Form->submit($submit_label, array('onClick' => 'on_submit()')); ?>
      </div>
    </li>
  <?php endif; ?>
</ul>
<?php
  print $this->element("changelog");
  