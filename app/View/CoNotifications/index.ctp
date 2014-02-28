<?php
/**
 * COmanage Registry CO Notification Index View
 *
 * Copyright (C) 2014 University Corporation for Advanced Internet Development, Inc.
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
 * @copyright     Copyright (C) 2014 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.8.5
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */

  $params = array('title' => _txt('ct.co_notifications.pl'));
  print $this->element("pageTitle", $params);
?>

<table id="org_identities" class="ui-widget">
  <thead>
    <tr class="ui-widget-header">
      <th><?php print $this->Paginator->sort('action', _txt('fd.action')); ?></th>
      <th><?php print $this->Paginator->sort('comment', _txt('fd.comment')); ?></th>
      <th><?php print $this->Paginator->sort('created', _txt('fd.created')); ?></th>
      <th><?php print $this->Paginator->sort('resolution_time', _txt('fd.resolved')); ?></th>
    </tr>
  </thead>
  
  <tbody>
    <?php $i = 0; ?>
    <?php foreach ($co_notifications as $c): ?>
    <tr class="line<?php print ($i % 2)+1; ?>">
      <td><?php print Sanitize::html($c['CoNotification']['action']); ?></td>
      <td><?php print $this->Html->link(Sanitize::html($c['CoNotification']['comment']),
                                        array(
                                          'controller' => 'co_notifications',
                                          'action'     => 'view',
                                          $c['CoNotification']['id']
                                        )); ?></td>
      <td>
        <?php
          if($c['CoNotification']['created']) {
            print $this->Time->niceShort($c['CoNotification']['created']);
          }
        ?>
      </td>
      <td><?php
          if($c['CoNotification']['resolution_time']) {
            print $this->Time->niceShort($c['CoNotification']['resolution_time']);
          }
        ?>
      </td>
    </tr>
    <?php $i++; ?>
    <?php endforeach; ?>
  </tbody>
  
  <tfoot>
    <tr class="ui-widget-header">
      <th colspan="4">
        <?php print $this->Paginator->numbers(); ?>
      </th>
    </tr>
  </tfoot>
</table>
