<?php
/**
 * COmanage Registry CO Service Tokens Generate View
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
  
  $params = array('title' => $title_for_layout);
  print $this->element("pageTitle", $params);

  // Add breadcrumbs
  print $this->element("coCrumb");
  $args = array();
  $args['plugin'] = 'co_service_token';
  $args['controller'] = 'co_service_tokens';
  $args['action'] = 'index';
  $args['copersonid'] = $vv_co_person_id;
  $this->Html->addCrumb(_txt('ct.co_service_tokens.pl'), $args);
?>
<div class="ui-state-highlight ui-corner-all co-info-topbox">
  <p>
    <span class="ui-icon ui-icon-info co-info"></span>
    <strong><?php print _txt('pl.coservicetoken.token.info'); ?></strong>
  </p>
</div>
<div class="innerContent">
<table id="<?php print $this->action; ?>_co_service_token" class="ui-widget">
  <tbody>
    <tr class="line1">
      <th class="ui-widget-header">
        <?php print _txt('ct.co_services.1'); ?>
      </th>
      <td>
        <?php print filter_var($vv_co_service['CoService']['name'], FILTER_SANITIZE_SPECIAL_CHARS); ?>
      </td>
    </tr>
    <tr class="line2">
      <th class="ui-widget-header">
        <?php print _txt('pl.coservicetoken.token'); ?>
      </th>
      <td>
        <span style="font-size:20px; font-family:courier;"><?php print filter_var($vv_token, FILTER_SANITIZE_SPECIAL_CHARS); ?></span>
      </td>
    </tr>
  </tbody>
</table>
</div>
