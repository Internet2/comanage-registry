<?php
/**
 * COmanage Registry Authentication Events Index View
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

  // Add breadcrumbs
  print $this->element("coCrumb");

  $this->Html->addCrumb(_txt('ct.authentication_events.pl'));

  // Add page title
  $params = array();
  $params['title'] = _txt('ct.authentication_events.pl');

  // Add top links
  $params['topLinks'] = array();

  print $this->element("pageTitleAndButtons", $params);
?>

<div class="table-container">
  <table id="authentication_events" class="ui-widget">
    <thead>
      <tr class="ui-widget-header">
        <th><?php print $this->Paginator->sort('id', _txt('fd.id.seq')); ?></th>
        <th><?php print $this->Paginator->sort('authenticated_identifier', _txt('fd.identifier.identifier')); ?></th>
        <th><?php print $this->Paginator->sort('authentication_event', _txt('fd.event')); ?></th>
        <th><?php print $this->Paginator->sort('created', _txt('fd.created.tz', array($vv_tz))); ?></th>
        <th><?php print $this->Paginator->sort('remote_ip', _txt('fd.ip')); ?></th>
      </tr>
    </thead>

    <tbody>
      <?php $i = 0; ?>
      <?php foreach ($authentication_events as $a): ?>
      <tr class="line<?php print ($i % 2)+1; ?>">
        <td><?php print $a['AuthenticationEvent']['id']; ?></td>
        <td><?php print filter_var($a['AuthenticationEvent']['authenticated_identifier'],FILTER_SANITIZE_SPECIAL_CHARS);?></td>
        <td><?php print _txt('en.auth.event', null, $a['AuthenticationEvent']['authentication_event']); ?></td>
        <td><?php print $this->Time->niceShort($a['AuthenticationEvent']['created'], $vv_tz); ?></td>
        <td><?php print filter_var($a['AuthenticationEvent']['remote_ip'],FILTER_SANITIZE_SPECIAL_CHARS); ?></td>
      </tr>
      <?php $i++; ?>
      <?php endforeach; ?>
    </tbody>

    <tfoot>
      <tr class="ui-widget-header">
        <th colspan="5">
          <?php print $this->element("pagination"); ?>
        </th>
      </tr>
    </tfoot>
  </table>
</div>