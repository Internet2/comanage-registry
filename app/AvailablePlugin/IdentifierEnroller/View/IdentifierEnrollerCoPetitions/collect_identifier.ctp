<?php
/**
 * COmanage Registry Identifier Enroller Fields
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

  $l = 0;
  
  $args = array();
  $args['url'] = array(
    'plugin'     => 'identifier_enroller',
    'controller' => 'identifier_enroller_co_petitions',
    'action'     => 'collectIdentifier',
    filter_var($this->request->params['pass'][0],FILTER_SANITIZE_SPECIAL_CHARS)
  );
  
  print $this->Form->create('CoPetition', $args);

  // Pass the token if we have one
  if(!empty($vv_petition_token)) {
    print $this->Form->hidden('token', array('default' => $vv_petition_token));
  }
  
  print $this->Form->hidden('co_enrollment_flow_wedge_id', array('default' => $vv_efwid));
?>
<ul id="select_identifiers" class="fields form-list form-list-admin">
  <?php foreach($vv_identifiers as $id): ?>
    <li>
      <div class="field-name">
        <div class="field-title">
          <?php print $id['label']; ?>
          <span class="required">*</span>
        </div>
        <div class="field-desc"><?php print $id['description']; ?></div>
      </div>
      <div class="field-info">
        <?php
          // We'll use the attribute ID as the input name
          
          $args = array();
          $args['label'] = false;
          $args['required'] = true;

          if(!empty($vv_default_env_values[ $id['id'] ])) {
            $args['value'] = $vv_default_env_values[ $id['id'] ];
          }
          
          print $this->Form->input($id['id'], $args);
        ?>
      </div>
    </li>
  <?php endforeach; // vv_identifiers ?>
  
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