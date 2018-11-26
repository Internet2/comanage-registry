<?php
/**
 * COmanage Registry CO Enrollment Source Fields
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
  $args['controller'] = 'co_enrollment_flows';
  $args['action'] = 'index';
  $args['co'] = $cur_co['Co']['id'];
  $this->Html->addCrumb(_txt('ct.co_enrollment_flows.pl'), $args);

  $args = array();
  $args['plugin'] = null;
  $args['controller'] = 'co_enrollment_flows';
  $args['action'] = 'edit';
  $args[] = $vv_ef_id;
  $this->Html->addCrumb($vv_ef_name, $args);

  $args = array();
  $args['plugin'] = null;
  $args['controller'] = 'co_enrollment_sources';
  $args['action'] = 'index';
  $args['coef'] = $vv_ef_id;
  $this->Html->addCrumb(_txt('ct.co_enrollment_sources.pl'), $args);

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
    print $this->Form->hidden('co_enrollment_flow_id', array('default' => $vv_ef_id)) . "\n";
  }
  
  $l = 1;
?>
<script type="text/javascript">
  <!-- JS specific to these fields -->
  function fields_update_gadgets() {
    // Hide or show gadgets according to current state

    // If Org Identity Mode is Search or Search/Required, show additional options
    var oim = document.getElementById('CoEnrollmentSourceOrgIdentityMode').value;

    if(oim == '<?php print EnrollmentOrgIdentityModeEnum::OISSearch;?>'
       || oim == '<?php print EnrollmentOrgIdentityModeEnum::OISSearchRequired;?>') {
      $("#CoEnrollmentSourceVerifyFamilyName").closest("tr").show('fade');
    } else {
      $("#CoEnrollmentSourceVerifyFamilyName").closest("tr").hide('fade');
    }
  }
  
  function js_local_onload() {
    fields_update_gadgets();
  }
</script>
<div class="table-container">
  <table id="<?php print $this->action; ?>_co_enrollment_source">
    <tbody>
      <tr class="line<?php print ($l % 2); $l++; ?>">
        <td>
          <b><?php print _txt('ct.org_identity_sources.1'); ?></b></strong><font class="required">*</font>
        </td>
        <td>
          <?php
            global $cm_lang, $cm_texts;

            $attrs = array();
            $attrs['value'] = (isset($co_enrollment_sources)
                               ? $co_enrollment_sources[0]['CoEnrollmentSource']['org_identity_source_id']
                               : null);
            $attrs['empty'] = false;

            if($e) {
              print $this->Form->select('org_identity_source_id',
                                        $vv_avail_ois,
                                        $attrs);

              if($this->Form->isFieldError('org_identity_source_id')) {
                print $this->Form->error('org_identity_source_id');
              }
            } else {
              print $vv_avail_ois[ $co_enrollment_sources[0]['CoEnrollmentSource']['org_identity_source_id'] ];
            }
          ?>
        </td>
      </tr>
      <tr class="line<?php print ($l % 2); $l++; ?>">
        <td>
          <strong class="fieldTitle"><?php print _txt('fd.ef.orgid'); ?></strong><font class="required">*</font><br />
          <span class="descr"><?php print _txt('fd.ef.orgid.desc'); ?></span>
        </td>
        <td>
          <?php
            global $cm_lang, $cm_texts;

            $attrs = array();
            $attrs['value'] = (!empty($co_enrollment_sources[0]['CoEnrollmentSource']['org_identity_mode'])
                               ? $co_enrollment_sources[0]['CoEnrollmentSource']['org_identity_mode']
                               : EnrollmentOrgIdentityModeEnum::None);
            $attrs['empty'] = false;
            $attrs['onchange'] = "fields_update_gadgets();";

            if($e) {
              print $this->Form->select('org_identity_mode',
                                        $cm_texts[ $cm_lang ]['en.enrollment.orgid'],
                                        $attrs);

              if($this->Form->isFieldError('org_identity_mode')) {
                print $this->Form->error('org_identity_mode');
              }
            } else {
              print _txt('en.enrollment.orgid', null, $co_enrollment_sources[0]['CoEnrollmentSource']['org_identity_mode']);
            }
          ?>
        </td>
      </tr>
      <tr class="line<?php print ($l % 2); $l++; ?>">
        <td>
          <b><?php print _txt('fd.ef.orgid.sn'); ?></b><br />
          <span class="descr"><?php print _txt('fd.ef.orgid.sn.desc'); ?></span>
        </td>
        <td>
          <?php print ($e
            ? $this->Form->input('verify_family_name')
            : filter_var($co_enrollment_sources[0]['CoEnrollmentSource']['verify_family_name'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
        </td>
      </tr>
      <tr class="line<?php print ($l % 2); $l++; ?>">
        <td>
          <b><?php print _txt('fd.order'); ?></b><br />
          <span class="descr"><?php print _txt('fd.order.es.desc'); ?></span>
        </td>
        <td>
          <?php print ($e
            ? $this->Form->input('ordr')
            : filter_var($co_enrollment_sources[0]['CoEnrollmentSource']['ordr'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
        </td>
      </tr>
    </tbody>
    <tfoot>
      <tr>
        <td>
          <em class="required"><?php print _txt('fd.req'); ?></em>
        </td>
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

<?php
  print $this->element("changelog");