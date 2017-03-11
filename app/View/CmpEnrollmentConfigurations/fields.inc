<?php
/**
 * COmanage Registry CMP Enrollment Configuration Fields
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

  // Determine if fields are editable
  $e = false;
  
  if(($this->action == "add" && $permissions['add']) || ($this->action == "edit" && $permissions['edit']))
    $e = true;
    
  print $this->Form->hidden('name');
  print $this->Form->hidden('status');
  
  // Rekey the current attribute set
  $currentAttributes = array();
  
  foreach($cmp_enrollment_configurations[0]['CmpEnrollmentAttribute'] as $a) {
    $currentAttributes[ $a['attribute'] ] = $a;
  }

  // Add breadcrumbs
  $this->Html->addCrumb(_txt('ct.cmp_enrollment_configurations.pl'), array('controller' => 'cmp_enrollment_configurations', 'action' => 'select'));
  if($this->action == "add") {
    $this->Html->addCrumb(_txt('op.add'));
  }
  if($this->action == "edit") {
    $this->Html->addCrumb(_txt('op.edit'));
  }
?>
<script type="text/javascript">
  <!-- JS specific to these fields -->
  // Keep track of the initial state of the pool setting so we know if we need to pop up a warning
  var pool_check_init_state = 0;
  
  function confirm_pool_state_swap()
  {
    // Display a warning when swapping pool state to <on>.
    
    // Set the title of the dialog    
    $("#dialog").dialog("option", "title", "<?php print _txt('fd.ef.pool'); ?>");

    // Set the body of the dialog
    if(document.getElementById('CmpEnrollmentConfigurationPoolOrgIdentities').checked) {
      $("#dialog-text").text("<?php print _txt('fd.ef.pool.on.warn'); ?>");
    } else {
      $("#dialog-text").text("<?php print _txt('fd.ef.pool.off.warn'); ?>");
    }
  
    // Set the dialog buttons
    $("#dialog").dialog("option",
                        "buttons",
                        {
                          "<?php print _txt('op.ok'); ?>": function() { $(this).dialog("close"); }
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
    
    // Show the attribute fields, if appropriate
    if(document.getElementById('CmpEnrollmentConfigurationAttrsFromEnv').checked) {
      $("#org_attributes").show('fade');
    } else {
      $("#org_attributes").hide('fade');
    }
    
    if(document.getElementById('CmpEnrollmentConfigurationAttrsFromCoef').checked) {
      // Required vs optional is set in the enrollment flow
      $("#edit_cmp_enrollment_attributes td:nth-child(2)").hide('fade');
      $("#edit_cmp_enrollment_attributes th:nth-child(2)").hide('fade');
    } else {
      $("#edit_cmp_enrollment_attributes td:nth-child(2)").show('fade');
      $("#edit_cmp_enrollment_attributes th:nth-child(2)").show('fade');
    }
    
    // Make LDAP and SAML attribute colums match the master settings
    /*
    for(i = 0;i < 18;i++)
    {
      document.getElementById('CmpEnrollmentAttribute'+i+'LdapName').disabled =
        (document.getElementById('CmpEnrollmentConfigurationAttrsFromLdap').disabled
         || !document.getElementById('CmpEnrollmentConfigurationAttrsFromLdap').checked);
      
      document.getElementById('CmpEnrollmentAttribute'+i+'SamlName').disabled =
        (document.getElementById('CmpEnrollmentConfigurationAttrsFromSaml').disabled
         || !document.getElementById('CmpEnrollmentConfigurationAttrsFromSaml').checked);
    }
    */
  }
  
  function on_submit()
  {
    // There appears to be an issue with Cake blackholing the request if text
    // fields are disabled (but not, strangely, if checkboxes or selects are
    // disabled). Renable these fields (in case they were disabled) to allow
    // the form to submit. See CO-350.
    
    /*
    for(i = 0;i < 18;i++)
    {
      document.getElementById('CmpEnrollmentAttribute'+i+'LdapName').disabled = false;
      document.getElementById('CmpEnrollmentAttribute'+i+'SamlName').disabled = false;
    }
    */
  }
  
  function js_local_onload()
  {
    // Local (to this view) initializations
    
    pool_check_init_state = document.getElementById('CmpEnrollmentConfigurationPoolOrgIdentities').checked;
    
    fields_update_gadgets();
  }
</script>
<div>
  <div>
    <table id="<?php print $this->action; ?>_cmp_enrollment_configuration" class="ui-widget">
      <tbody>
<?php if(0): ?>
        <tr class="line1">
          <td>
            <strong class="fieldTitle"><?php print _txt('fd.ef.ldap'); ?></strong><br />
            <span class="descr"><?php print _txt('fd.ef.ldap.desc'); ?></span>
          </td>
          <td>
            <?php print ($e
                         ? $this->Form->input('attrs_from_ldap', array('onClick' => 'fields_update_gadgets()', 'disabled' => true))
                         : filter_var($cmp_enrollment_configurations[0]['CmpEnrollmentConfiguration']['attrs_from_ldap'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
          </td>
        </tr>
        <tr class="line2">
          <td>
            <b><?php print _txt('fd.ef.saml'); ?></b><br />
            <span class="descr"><?php print _txt('fd.ef.saml.desc'); ?></span>
          </td>
          <td>
            <?php print ($e
                         ? $this->Form->input('attrs_from_saml', array('onClick' => 'fields_update_gadgets()', 'disabled' => true))
                         : filter_var($cmp_enrollment_configurations[0]['CmpEnrollmentConfiguration']['attrs_from_saml'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
          </td>
        </tr>
<?php endif; ?>
        <tr class="line1">
          <td>
            <strong class="fieldTitle"><?php print _txt('fd.ef.env'); ?></strong><br />
            <span class="descr"><?php print _txt('fd.ef.env.desc'); ?></span>
          </td>
          <td>
            <?php print ($e
                         ? $this->Form->input('attrs_from_env', array('onClick' => 'fields_update_gadgets()'))
                         : filter_var($cmp_enrollment_configurations[0]['CmpEnrollmentConfiguration']['attrs_from_env'],FILTER_SANITIZE_SPECIAL_CHARS));
              if($e) {
                print $this->Form->label('attrs_from_env', _txt('op.enable'));
              }
            ?>
          </td>
        </tr>
        <tr class="line2">
          <td>
            <strong class="fieldTitle"><?php print _txt('fd.ef.coef'); ?></strong><br />
            <span class="descr"><?php print _txt('fd.ef.coef.desc'); ?></span>
          </td>
          <td>
            <?php print ($e
                         ? $this->Form->input('attrs_from_coef', array('onClick' => 'fields_update_gadgets()'))
                         : filter_var($cmp_enrollment_configurations[0]['CmpEnrollmentConfiguration']['attrs_from_coef'],FILTER_SANITIZE_SPECIAL_CHARS));
              if($e) {
                print $this->Form->label('attrs_from_coef', _txt('op.enable'));
              }
            ?>
          </td>
        </tr>
        <tr class="line1">
          <td>
            <strong class="fieldTitle"><?php print _txt('fd.ef.pool'); ?></strong><br />
            <span class="descr"><?php print _txt('fd.ef.pool.desc'); ?></span>
          </td>
          <td>
            <?php print ($e
                         ? $this->Form->input('pool_org_identities', array('onClick' => 'confirm_pool_state_swap()',
                                                                           'disabled' => true))
                         : filter_var($cmp_enrollment_configurations[0]['CmpEnrollmentConfiguration']['pool_org_identities'],FILTER_SANITIZE_SPECIAL_CHARS));
              if($e) {
                print $this->Form->label('pool_org_identities', _txt('op.pool'));
              }
            ?>
          </td>
        </tr>
        <tr class="line2">
          <td>
            <?php print ($e
              ? $this->Form->label('eds_help_url',_txt('fd.ef.eds.help'),array('class'=>'fieldTitle'))
              : print '<strong class="fieldTitle">' . _txt('fd.ef.eds.help') . '</strong>'); ?><br/>
            <span class="descr"><?php print _txt('fd.ef.eds.help.desc'); ?></span>
          </td>
          <td>
            <?php print ($e
                         ? $this->Form->input('eds_help_url')
                         : filter_var($cmp_enrollment_configurations[0]['CmpEnrollmentConfiguration']['eds_help_url'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
          </td>
        </tr>
        <tr class="line1">
          <td>
            <?php print ($e
              ? $this->Form->label('eds_preferred_idps',_txt('fd.ef.eds.prefer'),array('class'=>'fieldTitle'))
              : print '<strong class="fieldTitle">' . _txt('fd.ef.eds.prefer') . '</strong>'); ?><br />
            <span class="descr"><?php print _txt('fd.ef.eds.prefer.desc'); ?></span>
          </td>
          <td>
            <?php print ($e
                         ? $this->Form->input('eds_preferred_idps')
                         : filter_var($cmp_enrollment_configurations[0]['CmpEnrollmentConfiguration']['eds_preferred_idps'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
          </td>
        </tr>
        <tr class="line2">
          <td>
            <?php print ($e
              ? $this->Form->label('eds_hidden_idps',_txt('fd.ef.eds.hide'),array('class'=>'fieldTitle'))
              : print '<strong class="fieldTitle">' . _txt('fd.ef.eds.hide') . '</strong>'); ?><br />
            <span class="descr"><?php print _txt('fd.ef.eds.hide.desc'); ?></span>
          </td>
          <td>
            <?php print ($e
                         ? $this->Form->input('eds_hidden_idps')
                         : filter_var($cmp_enrollment_configurations[0]['CmpEnrollmentConfiguration']['eds_hidden_idps'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
  
  <div id="org_attributes" style="display:none">
    <table id="<?php print $this->action; ?>_cmp_enrollment_attributes" class="ui-widget">
      <?php
        $l = 1;
        $xindex = 0;
       ?>
      <tbody>
        <tr>
          <th class="ui-state-default"><?php print _txt('fd.attribute'); ?></th>
          <th class="ui-state-default"><?php print _txt('fd.required'); ?></th>
          <th class="ui-state-default"><?php print _txt('fd.attr.env'); ?></th>
          <!-- <th class="ui-state-default"><?php /*print _txt('fd.attr.ldap');*/ ?></th>
          <th class="ui-state-default"><?php /*print _txt('fd.attr.saml');*/ ?></th>
          -->
        </tr>
        <?php foreach (array_keys($vv_availableAttributes) as $attr): ?>
        <tr class="line<?php print ($l % 2); $l++; ?>">
          <td>
            <?php
            $xprefix = 'CmpEnrollmentAttribute.' . $xindex;
            $xindex++;
            
            print $this->Form->hidden($xprefix . '.cmp_enrollment_configuration_id',
              array('value' => filter_var($cmp_enrollment_configurations[0]['CmpEnrollmentConfiguration']['id'],FILTER_SANITIZE_SPECIAL_CHARS)));
            print $this->Form->hidden($xprefix . '.attribute',
              array('value' => $attr));

            if(!empty($vv_availableAttributes[$attr]['type'])) {
              print $this->Form->hidden($xprefix . '.type',
                array('value' => filter_var($vv_availableAttributes[$attr]['type'],FILTER_SANITIZE_SPECIAL_CHARS)));
            }

            if(isset($currentAttributes[$attr]['id'])) {
              print $this->Form->hidden($xprefix . '.id',
                array('value' => $currentAttributes[$attr]['id']));
            }
            ?>
            <?php
              print ($e
              ? $this->Form->label($xprefix . '.env_name', $vv_availableAttributes[$attr]['label'])
              : $vv_availableAttributes[$attr]['label']);
            ?>
          </td>
          <td>
            <?php
              global $cm_lang, $cm_texts;
              $attrs = array();
              $attrs['value'] = (isset($currentAttributes[$attr]['required'])
                                 ? ($currentAttributes[$attr]['required'])
                                 : $vv_availableAttributes[$attr]['required']);
              $attrs['empty'] = false;
              
              if($e) {
                print $this->Form->label($xprefix.'.required',_txt('fd.required') . ': ');
                print $this->Form->select($xprefix.'.required',
                                          $cm_texts[ $cm_lang ]['en.required'],
                                          $attrs);
                
                if($this->Form->isFieldError($xprefix.'.required')) {
                  print $this->Form->error($xprefix.'.required');
                }
              } else {
                print _txt('en.required', null, $attrs['value']);
              }
            ?>
          </td>
          <td><?php
                $attrs = array();
                $attrs['default'] = $vv_availableAttributes[$attr]['env_name'];
                $attrs['size']    = '40';
                
                if(!empty($currentAttributes[$attr]['env_name'])
                   // Make sure the type matches, for attributes with a type
                   && (empty($vv_availableAttributes[$attr]['type'])
                       || ($vv_availableAttributes[$attr]['type'] == $currentAttributes[$attr]['type']))) {
                  $attrs['value'] = $currentAttributes[$attr]['env_name'];
                } else {
                  $attrs['value'] = "";
                }
                
                print ($e
                       ? $this->Form->input($xprefix . '.env_name', $attrs)
                       : filter_var($vv_availableAttributes[$attr]['env_name'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
          </td>
          <!--
          <td><?php /*print ($e
                           ? $this->Form->input($xprefix . '.ldap_name',
                                                array('default' => $vv_availableAttributes[$attr]['ldap_name'],
                                                      'size'    => '40'))
                           : filter_var($vv_availableAttributes[$attr]['env_name'],FILTER_SANITIZE_SPECIAL_CHARS));*/ ?></td>
          <td><?php /*print ($e
                           ? $this->Form->input($xprefix . '.saml_name',
                                                array('default' => $vv_availableAttributes[$attr]['saml_name'],
                                                      'size'    => '40'))
                           : filter_var($vv_availableAttributes[$attr]['env_name'],FILTER_SANITIZE_SPECIAL_CHARS));*/ ?></td>
          -->
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php
  if($e) {
    print '<div class="right">';
    print $this->Form->submit($submit_label, array('onClick' => 'on_submit()'));
    print $this->Form->button(_txt('op.reset'), array('type'=>'reset'));
    print '</div>';
  }
?>