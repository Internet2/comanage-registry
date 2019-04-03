<?php
/**
 * COmanage Registry Standard Add View
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

  // Get a pointer to our model
  $model = $this->name;
  $req = Inflector::singularize($model);
  
  // Since this is an add operation, we may not have any data.
  // (Currently, only controllers like CoPersonRole prepopulate fields for rendering.)

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
  
  print $this->Form->create(
    $req,
    array(
      'inputDefaults' => array(
        'label' => false,
        'div' => false
      )
    )
  );
  
  if(!empty($this->plugin)) {
    if(file_exists(APP . "Plugin/" . $this->plugin . "/View/" . $model . "/fields.inc")) {
      include(APP . "Plugin/" . $this->plugin . "/View/" . $model . "/fields.inc");
    } elseif(file_exists(LOCAL . "Plugin/" . $this->plugin . "/View/" . $model . "/fields.inc")) {
      include(LOCAL . "Plugin/" . $this->plugin . "/View/" . $model . "/fields.inc");
    }
  } else {
    include(APP . "View/" . $model . "/fields.inc");
  }
  
  print $this->Form->end();
?>
