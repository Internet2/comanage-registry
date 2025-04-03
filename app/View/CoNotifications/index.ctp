<?php
/**
 * COmanage Registry CO Notification Index View
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
 * @since         COmanage Registry v0.8.5
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

  global $cm_lang, $cm_texts;
  // Add breadcrumbs
  print $this->element("coCrumb");
  $this->Html->addCrumb(_txt('ct.co_notifications.pl'));

  // Add page title
  $params = array();
  // Add top links
  if ($permissions['bulk']) {
    $notificationsToAcknowledge = [];
    foreach ($co_notifications as $cnot) {
      if ($cnot['CoNotification']['status'] === NotificationStatusEnum::PendingAcknowledgment) {
        $notificationsToAcknowledge[] = $cnot['CoNotification']['id'];
      }
    }
    $params['topLinks'] = array();
    $params['topLinks'][] = $this->Html->link(
      _txt('op.ack.all'),
      array(
        'controller' => 'co_notifications',
        'action' => 'acknowledgesel',
        'list' => $notificationsToAcknowledge,
        // We have to add the forward slash prefix for the Router::url to work correctly
        'origin'     => base64_encode('/' . $this->request->url),
        $vv_request_type => $vv_co_person_id,
      ),
      array('class' => 'checkbutton')
    );
  }

  $params['title'] = $title_for_layout;
  print $this->element("pageTitleAndButtons", $params);

  // It seems easier to generate the form manually than with FormHelper, since it's not really a form as Cake knows it
  $curstatus = "";
  
  if(!empty($this->request->query['status'])) {
    $curstatus = filter_var($this->request->query['status'], FILTER_SANITIZE_SPECIAL_CHARS);
  }
  
  // Construct an action URL, trying to preserve sort direction
  $sorttype = "created";
  $sortdir = "desc";
  
  if(!empty($this->request->query['sort'])) {
    $sorttype = filter_var($this->request->query['sort'], FILTER_SANITIZE_SPECIAL_CHARS);
  }
  
  if(!empty($this->request->query['direction'])) {
    $sortdir = filter_var($this->request->query['direction'], FILTER_SANITIZE_SPECIAL_CHARS);
  }
  
  $furl = $this->Html->url('/')
        . "co_notifications/index/sort:" . $sorttype
        . "/direction:" . $sortdir
        . "/" . $vv_request_type . ":" . $vv_co_person_id;

  // Search Block
  if(!empty($vv_search_fields)) {
    print $this->element('search', array('vv_search_fields' => $vv_search_fields));
  }
?>

<div class="table-container">
  <table id="co_notifications">
    <thead>
      <tr>
        <th><?php print $this->Paginator->sort('action', _txt('fd.action')); ?></th>
        <th><?php print $this->Paginator->sort('action', _txt('fd.status')); ?></th>
        <th><?php print $this->Paginator->sort('comment', _txt('fd.comment')); ?></th>
        <th><?php print $this->Paginator->sort('created', _txt('fd.created.tz', array($vv_tz))); ?></th>
        <th><?php print $this->Paginator->sort('resolution_time', _txt('fd.resolved.tz', array($vv_tz))); ?></th>
        <th><?php print _txt('fd.actions'); ?></th>
      </tr>
    </thead>

    <tbody>
      <?php $i = 0; ?>
      <?php foreach ($co_notifications as $c): ?>
      <tr class="line<?php print ($i % 2)+1; ?>">
        <td><?php print $cm_texts[$cm_lang]['en.action'][filter_var($c['CoNotification']['action'],FILTER_SANITIZE_SPECIAL_CHARS)]; ?></td>
        <td><?php print $cm_texts[$cm_lang]['en.status.not'][$c['CoNotification']['status']]; ?></td>
        <td><?php print $this->Html->link($c['CoNotification']['comment'],
                                          array(
                                            'controller' => 'co_notifications',
                                            'action'     => 'view',
                                            $c['CoNotification']['id'],
                                            'origin'     => base64_encode($this->request->url)
                                          ),
                                          array(
                                            'class' => 'spin lightbox'
                                          )); ?></td>
        <td>
          <?php
            if($c['CoNotification']['created']) {
              print $this->Time->niceShort($c['CoNotification']['created'], $vv_tz);
            }
          ?>
        </td>
        <td><?php
            if($c['CoNotification']['resolution_time']) {
              print $this->Time->niceShort($c['CoNotification']['resolution_time'], $vv_tz);
            }
          ?>
        </td>
        <td>
          <?php
          $actionPermissions = $this->Permission->getNotificationActionPermit($c['CoNotification']['id']);
          if($c['CoNotification']['status'] == NotificationStatusEnum::PendingAcknowledgment
            && $actionPermissions['acknowledge']) {
            $route =             array(
              'controller' => 'co_notifications',
              'action'     => 'acknowledge',
              $c['CoNotification']['id'],
              'origin'     => base64_encode($this->request->url)
            );
            if(isset($this->request->params['named']['origin'])) {
              $route['origin'] = $this->request->params['named']['origin'];
            }

            print $this->Html->link(
                _txt('op.ack'),
                $route,
                array('class' => 'checkbutton')) . PHP_EOL;
          }

          // This is not else/if because someone could have permission to either ack or cancel
          if(($c['CoNotification']['status'] == NotificationStatusEnum::PendingAcknowledgment
              || $c['CoNotification']['status'] == NotificationStatusEnum::PendingResolution)
            && $actionPermissions['cancel']) {
            $route =             array(
              'controller' => 'co_notifications',
              'action'     => 'cancel',
              $c['CoNotification']['id'],
              'origin'     => base64_encode($this->request->url)
            );
            if(isset($this->request->params['named']['origin'])) {
              $route['origin'] = $this->request->params['named']['origin'];
            }
            print $this->Html->link(
                _txt('op.cancel'),
                $route,
                array('class' => 'deletebutton')) . PHP_EOL;
          }
          ?>
        </td>
      </tr>
      <?php $i++; ?>
      <?php endforeach; ?>
    </tbody>

  </table>
</div>

<?php
  print $this->element("pagination");