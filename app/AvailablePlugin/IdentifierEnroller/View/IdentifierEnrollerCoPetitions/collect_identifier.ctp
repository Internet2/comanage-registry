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
?>
<table id="select_identifiers" class="ui-widget">
  <tbody>
    <?php foreach($vv_identifiers as $id): ?>
    <tr class="line<?php print $l++ % 2; ?>">
      <td>
        <strong class="fieldTitle">
          <?php print $id['CoEnrollmentAttribute']['label']; ?>
        </strong><span class="required">*</span><br />
        <span class="descr"><?php print $id['CoEnrollmentAttribute']['description']; ?></span>
      </td>
      <td>
        <?php
          // We'll use the attribute ID as the input name
          
          $args = array();
          $args['label'] = false;
          $args['required'] = true;
          
          print $this->Form->input($id['CoEnrollmentAttribute']['id'], $args);
        ?>
      </td>
    </tr>
    <?php endforeach; // vv_identifiers ?>
    <tr>
      <td>
        <em><span class="required"><?php print _txt('fd.req'); ?></span></em><br />
      </td>
      <td>
        <?php
          print $this->Form->submit(_txt('op.submit'));
          print $this->Form->button(_txt('op.reset'),
                                    array('type'=>'reset'));
        ?>
      </td>
    </tr>
  </tbody>
</table>
<?php
  print $this->Form->end();