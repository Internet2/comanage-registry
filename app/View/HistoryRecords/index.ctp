<?php
/**
 * COmanage Registry HistoryRecord Index View
 *
 * Copyright (C) 2012 University Corporation for Advanced Internet Development, Inc.
 * 
 * Licensed under the Apache License, Version 2.0 (the "License"); you may not use this file except in compliance with
 * the License. You may obtain a copy of the License at
 * 
 * http://www.apache.org/licenses/LICENSE-2.0
 * 
 * Unless required by applicable law or agreed to in writing, software distributed under
 * the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY
 * KIND, either express or implied. See the License for the specific language governing
 * permissions and limitations under the License.
 *
 * @copyright     Copyright (C) 2012 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.7
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */

  $params = array('title' => _txt('ct.history_records.pl'));
  print $this->element("pageTitle", $params);
?>

<table id="org_identities" class="ui-widget">
  <thead>
    <tr class="ui-widget-header">
      <th><?php print $this->Paginator->sort('action', _txt('fd.action')); ?></th>
      <th><?php print $this->Paginator->sort('created', _txt('fd.created')); ?></th>
      <th><?php print $this->Paginator->sort('comment', _txt('fd.comment')); ?></th>
      <th><?php print $this->Paginator->sort('Actor.PrimaryName.family', _txt('fd.actor')); ?></th>
      <th><?php print $this->Paginator->sort('OrgIdentity.PrimaryName.family', _txt('ct.org_identities.1')); ?></th>
      <th><?php print $this->Paginator->sort('CoPerson.PrimaryName.family', _txt('ct.co_people.1')); ?></th>
    </tr>
  </thead>
  
  <tbody>
    <?php $i = 0; ?>
    <?php foreach ($history_records as $h): ?>
    <tr class="line<?php print ($i % 2)+1; ?>">
      <td><?php print Sanitize::html($h['HistoryRecord']['action']); ?></td>
      <td><?php print $this->Time->niceShort($h['HistoryRecord']['created']); ?></td>
      <td><?php print Sanitize::html($h['HistoryRecord']['comment']); ?></td>
      <td>
        <?php
          if(!empty($h['ActorCoPerson']['id'])) {
            print $this->Html->link(
              generateCn($h['ActorCoPerson']['PrimaryName']),
              array(
                'controller' => 'co_people',
                'action' => 'view',
                $h['ActorCoPerson']['id'],
                'co' => $h['ActorCoPerson']['co_id']
              )
            );
          }
        ?>
      </td>
      <td>
        <?php
          if(!empty($h['OrgIdentity']['id'])) {
            print $this->Html->link(
              generateCn($h['OrgIdentity']['PrimaryName']),
              array(
                'controller' => 'org_identities',
                'action' => 'view',
                $h['OrgIdentity']['id'],
                'co' => (isset($h['OrgIdentity']['co_id']) ? $h['OrgIdentity']['co_id'] : false)
              )
            );
          }
        ?>
      </td>
      <td>
        <?php
          if(!empty($h['CoPerson']['id'])) {
            print $this->Html->link(
              generateCn($h['CoPerson']['PrimaryName']),
              array(
                'controller' => 'co_people',
                'action' => 'view',
                $h['CoPerson']['id'],
                'co' => $h['CoPerson']['co_id']
              )
            );
          }
        ?>
      </td>
    </tr>
    <?php $i++; ?>
    <?php endforeach; ?>
  </tbody>
  
  <tfoot>
    <tr class="ui-widget-header">
      <th colspan="6">
        <?php print $this->Paginator->numbers(); ?>
      </th>
    </tr>
  </tfoot>
</table>
