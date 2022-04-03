<?php
/**
 * COmanage Registry Notification Widget Display View
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
 * @since         COmanage Registry v3.2.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */
?>
<ul>
<?php if(empty($vv_widget_notifications)): ?>
  <li><?php print _txt('pl.notificationswidget.none'); ?></li>
<?php endif; ?>
<?php foreach($vv_widget_notifications as $n): ?>
  <li class="notification">
    <?php
      // XXX This is basically copied from menuUser.php
      $args = array(
        'plugin'     => null,
        'controller' => 'co_notifications',
        'action'     => 'view',
        $n['CoNotification']['id'],
        'origin'     => base64_encode($this->request->url)
      );

      print '<span class="notification-comment">';
      print $this->Html->link($n['CoNotification']['comment'],
                              $args,
                              array(
                                'class' => 'lightbox spin',
                                'title' => _txt('op.see.notification.num',array($n['CoNotification']['id']))
                              )
      );
      print '</span> ';
      print '<span class="notification-created">';
      print $this->Time->timeAgoInWords($n['CoNotification']['created']);
      print '</span>';
    ?>
    </li>
<?php endforeach; ?>
</ul>