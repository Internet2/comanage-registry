<!--
/**
 * COmanage Registry Nationality Enrollers Plugin Fields
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
 * @since         COmanage Registry v4.0.0
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
  $args['controller'] = 'co_enrollment_flows';
  $args['action'] = 'index';
  $args['co'] = $cur_co['Co']['id'];
  $this->Html->addCrumb(_txt('ct.co_enrollment_flows.pl'), $args);

  $args = array();
  $args['plugin'] = null;
  $args['controller'] = 'co_enrollment_flows';
  $args['action'] = 'edit';
  $args[] = $nationality_enrollers[0]['CoEnrollmentFlowWedge']['co_enrollment_flow_id'];
  $this->Html->addCrumb($nationality_enrollers[0]['CoEnrollmentFlowWedge']['CoEnrollmentFlow']['name'], $args);

  $args = array();
  $args['plugin'] = null;
  $args['controller'] = 'co_enrollment_flow_wedges';
  $args['action'] = 'index';
  $args['coef'] = $nationality_enrollers[0]['CoEnrollmentFlowWedge']['co_enrollment_flow_id'];
  $args[] = $nationality_enrollers[0]['CoEnrollmentFlowWedge']['id'];
  $this->Html->addCrumb(_txt('ct.co_enrollment_flow_wedges.pl'), $args);

  $args = array();
  $args['plugin'] = null;
  $args['controller'] = 'co_enrollment_flow_wedges';
  $args['action'] = 'edit';
  $args[] = $nationality_enrollers[0]['CoEnrollmentFlowWedge']['id'];
  $this->Html->addCrumb($nationality_enrollers[0]['CoEnrollmentFlowWedge']['description'], $args);

  $crumbTxt = _txt('op.config');
  $this->Html->addCrumb($crumbTxt);
  
  print $this->Form->hidden('co_enrollment_flow_wedge_id', array('default' => $nationality_enrollers[0]['CoEnrollmentFlowWedge']['id'])) . "\n";
?>
<script type="text/javascript">
  // XXX Much copy and paste from View/IdentityDocument/fields.inc
  // Enums as constructed from the Attribute Enumeration Dictionary
  var enums = [];
  
  // We also need to track if the Dictionary is coded, so we use an object or
  // array appropriately.
  var coded = [];
  
  // And whether or not other values are permitted
  var other = [];
  
  // If editing, persisted values in the database
  var pIssuingAuthority = "<?php if(!empty($nationality_enrollers[0]['NationalityEnroller']['collect_residency_authority'])) print $nationality_enrollers[0]['NationalityEnroller']['collect_residency_authority']; ?>";
  
  <?php
    foreach($vv_enums as $type => $cfg) {
      print "other['" . $type . "'] = " . ($cfg['allow_other'] ? "true" : "false") . ";\n";
      
      if($cfg['coded']) {
        print "coded['" . $type . "'] = true\n";
        print "enums['" . $type . "'] = {\n";
        
        if(!empty($cfg['dictionary'])) {
          foreach($cfg['dictionary'] as $code => $label) {
            // might be safer not to emit a trailing comma, but it should work with modern javascript
            print addslashes($code) . ": '" . addslashes($label) . "',\n";
          }
        }
        
        print "};\n";
      } else {
        print "coded['" . $type . "'] = false\n";
        print "enums['" . $type . "'] = [\n";
        
        if(!empty($cfg['dictionary'])) {
          foreach($cfg['dictionary'] as $code => $label) {
            print "'" . addslashes($label) . "',\n";
          }
        }
        
        print "];\n";
      }
    }
  ?>
  
  function js_local_onload() {
    enum_update_gadgets();
    fields_update_gadgets();
  }
  
  function enum_set_value() {
    document.getElementById('NationalityEnrollerCollectResidencyAuthority').value = document.getElementById('issuing-authority-select').value;
  }
  
  function enum_update_gadgets() {
    var curtype = "<?php print IdentityDocumentEnum::Residency; ?>";
    //document.getElementById('IdentityDocumentDocumentType').value;
    
    if(enums[curtype] && Object.keys(enums[curtype]).length > 0) {
      $("#issuing-authority-enumeration").show("fade");
      
      // Keep the free form field if appropriately configured
      if(other[curtype]) {
        $("#issuing-authority-field").show("fade");
      } else {
        $("#issuing-authority-field").hide("fade");
      }
      
      var select = document.getElementById('issuing-authority-select');
      
      if(select.options.length > 0) {
        select.options.length = 0;
      }
      var i = 0;
      
      select.options[i++] = new Option('', '');
      
      if(coded[curtype]) {
        // We want the code as the select value
        for(j in enums[curtype]) {
          select.options[i++] = new Option(enums[curtype][j], j);
          
          if(//curtype == pDocumentType && 
             pIssuingAuthority == j) {
            select.selectedIndex = i-1;
          }
        }
      } else {
        // No code, so just use the entry as the value
        for(j in enums[curtype]) {
          select.options[i++] = new Option(enums[curtype][j]);
          
          if(//curtype == pDocumentType && 
             pIssuingAuthority == enums[curtype][j]) {
            select.selectedIndex = i-1;
          }
        }
      }
    } else {
      $("#issuing-authority-enumeration").hide("fade");
      $("#issuing-authority-field").show("fade");
    }
  }
    
  function fields_update_gadgets() {
    if(document.getElementById('NationalityEnrollerCollectResidency').checked) {
      $("#residency-authority").show("fade");
    } else {
      $("#residency-authority").hide("fade");
    }
  }
</script>
<ul id="<?php print $this->action; ?>_nationality_enroller" class="fields form-list form-list-admin">
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('pl.nationalityenroller.collect_maximum'); ?>
      </div>
      <div class="field-desc"><?php print _txt('pl.nationalityenroller.collect_maximum.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php print $this->Form->input('collect_maximum', array('default' => 5)); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('pl.nationalityenroller.collect_residency'); ?>
      </div>
      <div class="field-desc"><?php print _txt('pl.nationalityenroller.collect_residency.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php
        print $this->Form->input('collect_residency', array('onclick' => 'fields_update_gadgets();'));
      ?>
    </div>
  </li>
  <li id="residency-authority" style="display:none">
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('pl.nationalityenroller.collect_residency_authority'); ?>
      </div>
      <div class="field-desc"><?php print _txt('pl.nationalityenroller.collect_residency_authority.desc'); ?></div>
    </div>
    <div class="field-info">
      <!-- Issuing Authority might be an attribute enumeration -->
      <div id="issuing-authority-enumeration" style="display:none">
        <select id="issuing-authority-select" onchange="enum_set_value();">
        </select>
      </div>
      <div id="issuing-authority-field">
        <?php
          $args = array();
          $args['onkeyup'] = "document.getElementById('issuing-authority-select').value = ''";
          
          print $this->Form->input('collect_residency_authority', $args);
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
        <?php print $this->Form->submit($submit_label); ?>
      </div>
    </li>
  <?php endif; ?>
</ul>
