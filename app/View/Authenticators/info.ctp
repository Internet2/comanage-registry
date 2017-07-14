<?php
/**
 * COmanage Registry Authenticator (Standard) Info View
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
 * @since         COmanage Registry v3.1.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

  // Add breadcrumbs
  print $this->element("coCrumb");

  $args = array();
  $args['plugin'] = null;
  $args['controller'] = 'co_people';
  $args['action'] = 'index';
  $args['co'] = $cur_co['Co']['id'];
  $this->Html->addCrumb(_txt('me.population'), $args);
  
  $args = array();
  $args['plugin'] = null;
  $args['controller'] = 'co_people';
  $args['action'] = 'canvas';
  $args[] = $vv_co_person['CoPerson']['id'];
  $this->Html->addCrumb(generateCn($vv_co_person['PrimaryName']), $args);

  $args = array();
  $args['plugin'] = null;
  $args['controller'] = 'authenticators';
  $args['action'] = 'status';
  $args['copersonid'] = $vv_co_person['CoPerson']['id'];
  $this->Html->addCrumb(_txt('ct.authenticators.1'), $args);
  
  $this->Html->addCrumb($vv_authenticator['Authenticator']['description']);
  
  // Add page title
  $params = array();
  $params['title'] = $title_for_layout;

  // Add top links
  $params['topLinks'] = array();
  
  print $this->element("pageTitleAndButtons", $params);
?>  
<ul id="<?php print $this->action; ?>_authenticator_info" class="fields form-list form-list-admin">
  <li>
    <div class="field-name">
      <div class="field-title"><?php print _txt('fd.status'); ?></div>
    </div>
    <div class="field-info">
      <?php print filter_var($vv_status['comment'],FILTER_SANITIZE_SPECIAL_CHARS); ?>
    </div>
  </li>
</ul>
