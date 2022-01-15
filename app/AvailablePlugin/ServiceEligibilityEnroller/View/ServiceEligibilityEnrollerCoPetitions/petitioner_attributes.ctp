<?php
/**
 * COmanage Registry Service Eligibility Enroller Petitioner Attributes Fields
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
 * @since         COmanage Registry v4.1.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

  // Maximum number of nationalities that may be self asserted
  $maxNationalities = (!empty($vv_cfg['NationalityEnroller']['collect_maximum'])
                       ? $vv_cfg['NationalityEnroller']['collect_maximum']
                       : 5);
  
  $l = 0;
  
  $args = array();
  $args['url'] = array(
    'plugin'     => 'service_eligibility_enroller',
    'controller' => 'service_eligibility_enroller_co_petitions',
    'action'     => 'petitionerAttributes',
    filter_var($this->request->params['pass'][0],FILTER_SANITIZE_SPECIAL_CHARS)
  );
  
  print $this->Form->create('CoPetition', $args);

  // Pass the token if we have one
  if(!empty($vv_petition_token)) {
    print $this->Form->hidden('token', array('default' => $vv_petition_token));
  }
  
  // And our wedge ID
  print $this->Form->hidden('co_enrollment_flow_wedge_id', array('default' => $vv_efwid));
  
  print $this->element("pageTitle", array('title' => _txt('ct.service_eligibilities.1'),
                                          'subtitle' => _txt('pl.serviceeligibilityenroller.info')));
?>
<ul id="service_eligibilities" class="fields form-list form-list-admin">
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('ct.service_eligibilities.1'); ?>
        <?php if($vv_settings['ServiceEligibilitySetting']['require_selection']): ?>
        <span class="required">*</span>
        <?php endif; // require_selection ?>
      </div>
    </div>
    <div class="field-info">
      <?php
        $args = array();
        $args['options'] = Hash::combine($vv_available_services, '{n}.CoService.id', '{n}.CoService.description');
        $args['empty'] = !$vv_settings['ServiceEligibilitySetting']['require_selection'];
        $args['multiple'] = $vv_settings['ServiceEligibilitySetting']['allow_multiple'];
        $args['required'] = $vv_settings['ServiceEligibilitySetting']['require_selection'];
        
        print $this->Form->input('eligibilities', $args);
      ?>
    </div>
  </li>
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