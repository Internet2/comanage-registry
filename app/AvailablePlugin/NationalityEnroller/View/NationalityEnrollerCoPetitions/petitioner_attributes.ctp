<?php
/**
 * COmanage Registry Nationality Enroller Fields
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
 * @since         COmanage Registry v4.0.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

  // Maximum number of nationalities that may be self asserted
  $maxNationalities = (!empty($vv_cfg['NationalityEnroller']['collect_maximum'])
                       ? $vv_cfg['NationalityEnroller']['collect_maximum']
                       : 5);
  
  $l = 0;
  
  $args = array();
  $args['url'] = array(
    'plugin'     => 'nationality_enroller',
    'controller' => 'nationality_enroller_co_petitions',
    'action'     => 'petitionerAttributes',
    filter_var($this->request->params['pass'][0],FILTER_SANITIZE_SPECIAL_CHARS)
  );
  
  print $this->Form->create('CoPetition', $args);

  // Pass the token if we have one
  if(!empty($vv_petition_token)) {
    print $this->Form->hidden('token', array('default' => $vv_petition_token));
  }
  
  // And our wedge ID
  print $this->Form->hidden('co_enrollment_flow_wedge_id', array('default' => $vv_cfg['NationalityEnroller']['co_enrollment_flow_wedge_id']));
  
  print $this->element("pageTitle", array('title' => _txt('pl.nationalityenroller.title'),
                                          'subtitle' => _txt('pl.nationalityenroller.info')));
  
  // Declare AttributeEnumerations
  $args = array(
    'enumerables' => array()
  );
  
  for($i = 1;$i <= $vv_cfg['NationalityEnroller']['collect_maximum'];$i++) {
    $args['enumerables'][] = 'CoPetition.nationality_authority_'.$i;
  }
  
  if($vv_cfg['NationalityEnroller']['collect_residency'] && empty($vv_cfg['NationalityEnroller']['collect_residency_authority'])) {
    $args['enumerables'][] = 'CoPetition.residency_authority';
  }
  
  print $this->element('enumerations', $args); 
?>
<script type="text/javascript">
  function js_local_onload() {
    enum_update_gadgets(false);
  }
</script>

<ul id="petitioner_attributes" class="fields form-list form-list-admin">
  <?php for($i = 1;$i <= $maxNationalities;$i++): ?>
  <li id="nationality-authority-<?php print $i; ?>">
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('pl.nationalityenroller.self.nationality', array($i)); ?>
      </div>
    </div>
    <div class="field-info">
      <?php
        $args = array(
          'column' => 'nationality_authority_'.$i,
          'fieldName' => 'nationality_authority_'.$i,
          'modelName' => 'CoPetition',
          'editable' => true
        );
        
        print $this->element('enumerableField', $args);
      ?>
    </div>
  </li>
  <?php endfor; // $i ?>
  <?php if(isset($vv_cfg['NationalityEnroller']['collect_residency'])
           && $vv_cfg['NationalityEnroller']['collect_residency']): ?>
  <li id="residency-authority">
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('pl.nationalityenroller.self.residency'); ?>
      </div>
    </div>
    <div class="field-info">
      <?php if(!empty($vv_cfg['NationalityEnroller']['collect_residency_authority'])): ?>
      <?php
        // There is only one possible value
        $args = array();
        $args['label'] = $vv_cfg['NationalityEnroller']['collect_residency_authority'];
        $args['value'] = $vv_cfg['NationalityEnroller']['collect_residency_authority'];
        $args['type'] = 'checkbox';
        
        print $this->Form->input('residency_authority', $args);
      ?>
      <?php else: // collect_residency_authority ?>
      <?php
        $args = array(
          'column' => 'residency_authority',
          'fieldName' => 'residency_authority',
          'modelName' => 'CoPetition',
          'editable' => true
        );
        
        print $this->element('enumerableField', $args);
      ?>
      <?php endif; // collect_residency_authority ?>
    </div>
  </li>
  <?php endif; // collect_residency ?>
  <li class="fields-submit">
    <div class="field-name">
      <span class="required"><?php print _txt('fd.req'); ?></span>
    </div>
    <div class="field-info">
      <?php print $this->Form->submit(_txt('op.submit')); ?>
    </div>
  </li>
</ul>
<?php
  print $this->Form->end();