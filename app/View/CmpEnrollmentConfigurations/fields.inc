<!--
/**
 * COmanage Registry CMP Enrollment Configuration Fields
 *
 * Copyright (C) 2011-12 University Corporation for Advanced Internet Development, Inc.
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
 * @copyright     Copyright (C) 2011-12 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.3
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */
-->
<?php
  // Determine if fields are editable
  $e = false;
  
  if(($this->action == "add" && $permissions['add']) || ($this->action == "edit" && $permissions['edit']))
    $e = true;
    
  print $this->Form->hidden('name');
  print $this->Form->hidden('status');
?>
<script type="text/javascript">
  <!-- JS specific to these fields -->
  // Keep track of the initial state of the pool setting so we know if we need to pop up a warning
  var pool_check_init_state = 0;
  
  function confirm_pool_state_swap()
  {
    // Display a warning when swapping pool state to <on>.
    
    // Set the title of the dialog    
    $("#dialog").dialog("option", "title", "<?php echo _txt('fd.ef.pool'); ?>");

    // Set the body of the dialog
    if(document.getElementById('CmpEnrollmentConfigurationPoolOrgIdentities').checked) {
      $("#dialog-text").text("<?php echo _txt('fd.ef.pool.on.warn'); ?>");
    } else {
      $("#dialog-text").text("<?php echo _txt('fd.ef.pool.off.warn'); ?>");
    }
  
    // Set the dialog buttons
    $("#dialog").dialog("option",
                        "buttons",
                        {
                          "<?php echo _txt('op.ok'); ?>": function() { $(this).dialog("close"); }
                        });
   
    // Open the dialog, but only if we're switching away from the original state
    if(pool_check_init_state !=
       document.getElementById('CmpEnrollmentConfigurationPoolOrgIdentities').checked) {
      $('#dialog').dialog('open');
    }
  }
  
  function fields_update_gadgets()
  {
    // Enable or disable gadgets according to current state
    
// XXX clean this up    
    // Treat self_enroll values as a set
    document.getElementById('CmpEnrollmentConfigurationSelfRequireAuthn').disabled =
      !document.getElementById('CmpEnrollmentConfigurationSelfEnroll').checked;
      
    // And treat admin_enroll values as a set
    document.getElementById('CmpEnrollmentConfigurationAdminConfirmEmail').disabled =
      (document.getElementById('CmpEnrollmentConfigurationAdminEnroll').value == 'N');

    document.getElementById('CmpEnrollmentConfigurationAdminRequireAuthn').disabled =
      (document.getElementById('CmpEnrollmentConfigurationAdminEnroll').value == 'N');
    
    document.getElementById('CmpEnrollmentConfigurationAttrsFromLdap').disabled =
    document.getElementById('CmpEnrollmentConfigurationAttrsFromSaml').disabled =
      ((document.getElementById('CmpEnrollmentConfigurationSelfRequireAuthn').disabled
        || !document.getElementById('CmpEnrollmentConfigurationSelfRequireAuthn').checked)
       &&
       (document.getElementById('CmpEnrollmentConfigurationAdminRequireAuthn').disabled
        || !document.getElementById('CmpEnrollmentConfigurationAdminRequireAuthn').checked));
    
    // Make LDAP and SAML attribute colums match the master settings
    for(i = 0;i < 18;i++)
    {
      document.getElementById('CmpEnrollmentAttribute'+i+'LdapName').disabled =
        (document.getElementById('CmpEnrollmentConfigurationAttrsFromLdap').disabled
         || !document.getElementById('CmpEnrollmentConfigurationAttrsFromLdap').checked);
      
      document.getElementById('CmpEnrollmentAttribute'+i+'SamlName').disabled =
        (document.getElementById('CmpEnrollmentConfigurationAttrsFromSaml').disabled
         || !document.getElementById('CmpEnrollmentConfigurationAttrsFromSaml').checked);
    }
  }
  
  function on_submit()
  {
    // There appears to be an issue with Cake blackholing the request if text
    // fields are disabled (but not, strangely, if checkboxes or selects are
    // disabled). Renable these fields (in case they were disabled) to allow
    // the form to submit. See CO-350.
    
    for(i = 0;i < 18;i++)
    {
      document.getElementById('CmpEnrollmentAttribute'+i+'LdapName').disabled = false;
      document.getElementById('CmpEnrollmentAttribute'+i+'SamlName').disabled = false;
    }
  }
  
  function js_local_onload()
  {
    // Local (to this view) initializations
    
    pool_check_init_state = document.getElementById('CmpEnrollmentConfigurationPoolOrgIdentities').checked;
      
    fields_update_gadgets();
  }
</script>
<div class="accordion">
  <h3><a href="#"><?php print _txt('fd.ef.cf.cmp'); ?></a></h3>
  <div>
    <table id="<?php print $this->action; ?>_cmp_enrollment_configuration" class="ui-widget">
      <tbody>
        <tr class="line1">
          <td>
            <b><?php print _txt('fd.ef.ldap'); ?></b><br />
            <font class="desc"><?php print _txt('fd.ef.ldap.desc'); ?></font>
          </td>
          <td>
            <?php print ($e
                         ? $this->Form->input('attrs_from_ldap', array('onClick' => 'fields_update_gadgets()'))
                         : Sanitize::html($cmp_enrollment_configurations[0]['CmpEnrollmentConfiguration']['attrs_from_ldap'])); ?>
          </td>
        </tr>
        <tr class="line2">
          <td>
            <b><?php print _txt('fd.ef.saml'); ?></b><br />
            <font class="desc"><?php print _txt('fd.ef.saml.desc'); ?></font>
          </td>
          <td>
            <?php print ($e
                         ? $this->Form->input('attrs_from_saml', array('onClick' => 'fields_update_gadgets()'))
                         : Sanitize::html($cmp_enrollment_configurations[0]['CmpEnrollmentConfiguration']['attrs_from_saml'])); ?>
          </td>
        </tr>
        <tr class="line1">
          <td>
            <b><?php print _txt('fd.ef.coef'); ?></b><br />
            <font class="desc"><?php print _txt('fd.ef.coef.desc'); ?></font>
          </td>
          <td>
            <?php print ($e
                         ? $this->Form->input('attrs_from_coef')
                         : Sanitize::html($cmp_enrollment_configurations[0]['CmpEnrollmentConfiguration']['attrs_from_coef'])); ?>
          </td>
        </tr>
        <tr class="line2">
          <td>
            <b><?php print _txt('fd.ef.pool'); ?></b><br />
            <font class="desc"><?php print _txt('fd.ef.pool.desc'); ?></font>
          </td>
          <td>
            <?php print ($e
                         ? $this->Form->input('pool_org_identities', array('onClick' => 'confirm_pool_state_swap()'))
                         : Sanitize::html($cmp_enrollment_configurations[0]['CmpEnrollmentConfiguration']['pool_org_identities'])); ?>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
  
  <h3><a href="#"><?php print _txt('fd.attrs.org'); ?></a></h3>
  <div>
    <table id="<?php print $this->action; ?>_cmp_enrollment_attributes" class="ui-widget">
      <?php
        // $this->data['CmpEnrollmentAttribute'] is sorted by order returned by the database,
        // but we want to render in a "rational order" (whatever that means)
        
        // We basically need to output almost the same output for each row, so do so
        // via an array ($cmp_ef_attribute_order, set by the controller) of the few things that change.
        
        $l = 1;
       ?>
      <tbody>
        <tr>
          <th class="ui-state-default"><?php print _txt('fd.attribute'); ?></th>
          <th class="ui-state-default"><?php print _txt('fd.required'); ?></th>
          <th class="ui-state-default"><?php print _txt('fd.attr.ldap'); ?></th>
          <th class="ui-state-default"><?php print _txt('fd.attr.saml'); ?></th>
        </tr>
        <?php foreach ($cmp_ef_attribute_order as $f): ?>
        <tr class="line<?php print ($l % 2); $l++; ?>">
          <?php
            $a = find_ef_attribute($this->request->data['CmpEnrollmentAttribute'], $f['attr'], $f['type']);
            
            if($a)
            {
              print $this->Form->hidden('CmpEnrollmentAttribute.'.$a['_index'].'.id');
              print $this->Form->hidden('CmpEnrollmentAttribute.'.$a['_index'].'.attribute');
              print $this->Form->hidden('CmpEnrollmentAttribute.'.$a['_index'].'.type');
            }
          ?>
          <td><?php print $f['label']; ?></td>
          <td>
            <?php
              global $cm_lang, $cm_texts;
              $attrs['value'] = (isset($a['required']) ? $a['required'] : RequiredEnum::Optional);
              $attrs['empty'] = false;
              
              if($e) {
                print $this->Form->select('CmpEnrollmentAttribute.'.$a['_index'].'.required',
                                          $cm_texts[ $cm_lang ]['en.required'],
                                          $attrs);
                
                if($this->Form->isFieldError('CmpEnrollmentAttribute.'.$a['_index'].'.required')) {
                  print $this->Form->error('CmpEnrollmentAttribute.'.$a['_index'].'.required');
                }
              } else {
                print _txt('en.required', null, $a['required']);
              }
            ?>
          </td>
          <td>
            <?php print ($e
                         ? $this->Form->input('CmpEnrollmentAttribute.'.$a['_index'].'.ldap_name')
                         : Sanitize::html($a['ldap_name'])); ?>
          </td>
          <td>
            <?php print ($e
                         ? $this->Form->input('CmpEnrollmentAttribute.'.$a['_index'].'.saml_name')
                         : Sanitize::html($a['saml_name'])); ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php
  if($e)
    print $this->Form->submit($submit_label, array('onClick' => 'on_submit()'));
?>