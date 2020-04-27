<?php
/**
 * COmanage Registry SshKeys Add View
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
 * @since         COmanage Registry v0.1
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

  // Determine if fields are editable
  $e = false;
  
  if($permissions['add'])
    $e = true;
  
  // We shouldn't get here if we don't have at least read permission, but check just in case
  
  if(!$e && !$permissions['view'])
    return(false);

  // Get a pointer to our model
  $model = $this->name;
  $req = Inflector::singularize($model);

  // Add page title
  $params = array();
  $params['title'] = $title_for_layout;
  
  // For Authenticators during enrollment
  if(!empty($vv_co_enrollment_authenticator)
     && ($vv_co_enrollment_authenticator['CoEnrollmentAuthenticator']['required'] == RequiredEnum::Optional)
     && !empty($this->request->params['named']['onFinish'])) {
    $params['topLinks'][] = $this->Html->link(_txt('op.skip'),
                                              urldecode($this->request->params['named']['onFinish']),
                                              array('class' => 'forwardbutton'));
  }
  
  print $this->element("pageTitleAndButtons", $params);

  $submit_label = _txt('op.add');

  $args = array();
  $args['type'] = 'file';
  $args['inputDefaults']['label'] = false;
  $args['inputDefaults']['div'] = false;

  print $this->Form->create($req, $args);

  print $this->Form->hidden('authenticator_id',
                             array('default' => $vv_authenticator['SshKeyAuthenticator']['authenticator_id'])) . "\n";
  print $this->Form->hidden('ssh_key_authenticator_id',
                            array('default' => $vv_authenticator['SshKeyAuthenticator']['id'])) . "\n";
  print $this->Form->hidden('co_person_id', array('default' => $vv_co_person['CoPerson']['id'])) . "\n";

  // Add breadcrumbs
  print $this->element("coCrumb", array('authenticator' => 'SshKey'));
?>

<!-- As of v3.2.0 (CO-1616), we no longer allow manual editing of SSH Keys -->

<?php if($e): ?>
  <ul id="add_ssh_key_upload" class="fields form-list">
    <li>
      <div class="field-name">
        <?php print _txt('op.upload.new', array(_txt('ct.ssh_keys.1'))); ?>
      </div>
      <div class="field-info">
        <?php
          print $this->Form->file('SshKey.keyFile');
        ?>
      </div>
    </li>
    <li class="fields-submit">
      <div class="field-name"></div>
      <div class="field-info">
        <?php print $this->Form->submit(_txt('op.upload')); ?>
      </div>
    </li>
  </ul>
<?php endif; ?>

<?php print $this->Form->end(); ?>
