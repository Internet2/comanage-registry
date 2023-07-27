<?php
/**
 * COmanage Registry API Users Generate View
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
 * @since         COmanage Registry v3.3.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */
  
  $params = array('title' => _txt('op.api.key.generate'));
  print $this->element("pageTitle", $params);

  // Add breadcrumbs
  print $this->element("coCrumb");
  
  $this->Html->addCrumb(_txt('ct.api_users.pl'), array('controller' => 'api_users', 'action' => 'index', 'co' => $cur_co['Co']['id']));
  $this->Html->addCrumb(filter_var($vv_api_user['ApiUser']['username'], FILTER_SANITIZE_SPECIAL_CHARS), array('controller' => 'api_users', 'action' => 'edit', $vv_api_user['ApiUser']['id']));
  $crumbTxt = _txt('op.api.key.generate');
  $this->Html->addCrumb($crumbTxt);
?>
<div class="co-info-topbox">
  <em class="material-icons">info</em>
  <div class="co-info-topbox-text">
    <?php print _txt('in.api.key'); ?>
  </div>
</div>

<ul id="<?php print $this->action; ?>_api_users" class="fields form-list">
  <li>
    <div class="field-name vtop">
      <div class="field-title">
        <?php print _txt('fd.api.username'); ?>
      </div>
    </div>
    <div class="field-info">
      <?php print filter_var($vv_api_user['ApiUser']['username'],FILTER_SANITIZE_SPECIAL_CHARS); ?>
    </div>
  </li>
  <li>
    <div class="field-name vtop">
      <div class="field-title">
        <?php print _txt('fd.api.key'); ?>
      </div>
    </div>
    <div class="field-info">
      <?php print $vv_api_key; ?>
    </div>
  </li>
</ul>
