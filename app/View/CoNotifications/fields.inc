<?php
/**
 * COmanage Registry CO Notifications Fields
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
 * @since         COmanage Registry v0.8.4
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

  // Determine if fields are editable
  $e = false;

  // XXX drop this if we don't have a manual way to create a notification
  if(($this->action == "add" && $permissions['add']) || ($this->action == "edit" && $permissions['edit']))
    $e = true;
    
  // We shouldn't get here if we don't have at least read permission, but check just in case
  
  if(!$e && !$permissions['view'])
    return(false);

  // Add breadcrumbs
  print $this->element("coCrumb");
  $args = array();
  $args['plugin'] = null;
  $args['controller'] = 'co_notifications';
  $args['action'] = 'index';
  $args['recipientcopersonid'] = $vv_co_person_id_notifications;
  $args['sort'] = 'created';
  $args['direction'] = 'desc';
  $this->Html->addCrumb(_txt('ct.co_notifications.pl'), $args);
  $this->Html->addCrumb(_txt('ct.co_notifications.1'));
  
  // XXX drop this if we don't have a manual way to create a notification
  print $this->Form->hidden('co_id', array('default' => $cur_co['Co']['id'])) . "\n";
  
  $l = 1;
?>
<ul id="<?php print $this->action; ?>_co_notification" class="fields form-list notification-list">
  <li>
    <div class="field-name">
      <?php print _txt('fd.comment'); ?>
    </div>
    <div class="field-info">
      <?php print filter_var($co_notifications[0]['CoNotification']['comment'], FILTER_SANITIZE_STRING); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <?php print _txt('fd.status'); ?>
    </div>
    <div class="field-info">
      <?php
        print '<div class="notificationStatus">' . _txt('en.status.not', null, $co_notifications[0]['CoNotification']['status']) . '</div>';
        
        if($co_notifications[0]['CoNotification']['status'] == NotificationStatusEnum::PendingAcknowledgment
           && $permissions['acknowledge']) {
          $route =             array(
            'controller' => 'co_notifications',
            'action'     => 'acknowledge',
            $co_notifications[0]['CoNotification']['id']
          );
          if(isset($this->request->params['named']['origin'])) {
            $route['origin'] = $this->request->params['named']['origin'];
          }
          print $this->Html->link(
            '<i class="fa fa-check-circle-o fa-lg mr-1" aria-hidden="true"></i>' . _txt('op.ack'),
            $route,
            array(
              'class' => 'btn btn-primary btn-sm mt-auto mb-auto mr-1',
              'escape' => false
            )
          );
        }
        
        // This is not else/if because someone could have permission to either ack or cancel
        if(($co_notifications[0]['CoNotification']['status'] == NotificationStatusEnum::PendingAcknowledgment
            || $co_notifications[0]['CoNotification']['status'] == NotificationStatusEnum::PendingResolution)
           && $permissions['cancel']) {
          $route =             array(
            'controller' => 'co_notifications',
            'action'     => 'cancel',
            $co_notifications[0]['CoNotification']['id']
          );
          if(isset($this->request->params['named']['origin'])) {
            $route['origin'] = $this->request->params['named']['origin'];
          }
          print $this->Html->link(
           '<i class="fa fa-times-circle-o fa-lg mr-1" aria-hidden="true"></i>' . _txt('op.cancel'),
            $route,
            array(
              'class' => 'btn btn-danger btn-sm mt-auto mb-auto',
              'escape' => false
           )
          );
        }
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <?php print _txt('fd.source'); ?>
    </div>
    <div class="field-info">
      <?php
        if(!empty($co_notifications[0]['CoNotification']['source_url'])) {
          print $this->Html->link(
            $co_notifications[0]['CoNotification']['source_url'],
            $co_notifications[0]['CoNotification']['source_url']
          );
        } elseif(!empty($co_notifications[0]['CoNotification']['source_controller'])) {
          print $this->Html->link(
            $co_notifications[0]['CoNotification']['source_controller'],
            array(
              'controller' => $co_notifications[0]['CoNotification']['source_controller'],
              'action'     => $co_notifications[0]['CoNotification']['source_action'],
              $co_notifications[0]['CoNotification']['source_id'],
              // Inject the CO ID in case it's needed
              'co'         => $cur_co['Co']['id']
            )
          );
        }
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <?php print _txt('fd.subject'); ?>
    </div>
    <div class="field-info">
      <?php
        if(!empty($co_notifications[0]['SubjectCoPerson']['PrimaryName'])) {
          print filter_var(generateCn($co_notifications[0]['SubjectCoPerson']['PrimaryName']),FILTER_SANITIZE_SPECIAL_CHARS);
        }
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <?php print _txt('fd.recipient'); ?>
    </div>
    <div class="field-info">
      <?php
        if(!empty($co_notifications[0]['RecipientCoPerson']['PrimaryName'])) {
          print filter_var(generateCn($co_notifications[0]['RecipientCoPerson']['PrimaryName']),FILTER_SANITIZE_SPECIAL_CHARS);
        } elseif(!empty($co_notifications[0]['RecipientCoGroup']['name'])) {
          print filter_var($co_notifications[0]['RecipientCoGroup']['name'],FILTER_SANITIZE_SPECIAL_CHARS);
        }
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <?php print _txt('fd.actor'); ?>
    </div>
    <div class="field-info">
      <?php
        if(!empty($co_notifications[0]['ActorCoPerson']['PrimaryName'])) {
          print filter_var(generateCn($co_notifications[0]['ActorCoPerson']['PrimaryName']),FILTER_SANITIZE_SPECIAL_CHARS);
        }
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <?php print _txt('fd.action'); ?>
    </div>
    <div class="field-info">
      <?php print filter_var($co_notifications[0]['CoNotification']['action'], FILTER_SANITIZE_STRING); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <?php print _txt('fd.created'); ?>
    </div>
    <div class="field-info">
      <?php print $this->Time->format($co_notifications[0]['CoNotification']['created'], "%c $vv_tz", false, $vv_tz); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <?php print _txt('fd.not.last'); ?>
    </div>
    <div class="field-info">
      <?php
        if(!empty($co_notifications[0]['CoNotification']['notification_time'])) {
          print $this->Time->format($co_notifications[0]['CoNotification']['notification_time'], "%c $vv_tz", false, $vv_tz);
        }
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <?php print _txt('fd.resolved'); ?>
    </div>
    <div class="field-info">
      <?php
        if(!empty($co_notifications[0]['CoNotification']['resolution_time'])) {
          print $this->Time->format($co_notifications[0]['CoNotification']['resolution_time'], "%c $vv_tz", false, $vv_tz);
          
          if(!empty($co_notifications[0]['ResolverCoPerson']['PrimaryName'])) {
            print " (" . filter_var(generateCn($co_notifications[0]['ResolverCoPerson']['PrimaryName']),FILTER_SANITIZE_SPECIAL_CHARS) . ")";
          }
        }
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <?php print _txt('fd.not.email.subject'); ?>
    </div>
    <div class="field-info">
      <?php print filter_var($co_notifications[0]['CoNotification']['email_subject'], FILTER_SANITIZE_STRING); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <?php print _txt('fd.not.email.body'); ?>
    </div>
    <div class="field-info field-email">
      <?php
        $fieldString = filter_var($co_notifications[0]['CoNotification']['email_body'], FILTER_SANITIZE_STRING);
        //$regexUrlFinder = "/(http|https)\:\/\/[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}(\/\S*)?/";
        $regexUrlFinder = "/(http|https)\:\/\/[a-zA-Z0-9\-\.]+\.[a-zA-Z]*(\/\S*)?/";
        foreach (explode(PHP_EOL, $fieldString) as $line) {
          if (trim($line)) {
            print '<p>';
            if (preg_match($regexUrlFinder, $line, $uri)) {
              print preg_replace($regexUrlFinder, '<a href="' . $uri[0] . '">' . $uri[0] . '</a> ', $line);
            } else {
              print $line;
            }
            print '</p>';
          }
        }
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <?php print _txt('fd.not.res.subject'); ?>
    </div>
    <div class="field-info">
      <?php print filter_var($co_notifications[0]['CoNotification']['resolution_subject'],FILTER_SANITIZE_STRING); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <?php print _txt('fd.not.res.body'); ?>
    </div>
    <div class="field-info field-email">
      <?php
        $fieldString = filter_var($co_notifications[0]['CoNotification']['resolution_body'],FILTER_SANITIZE_STRING);
        foreach (explode(PHP_EOL, $fieldString) as $line) {
          if (trim($line)) {
            print '<p>';
            if (preg_match($regexUrlFinder, $line, $uri)) {
              print preg_replace($regexUrlFinder, '<a href="' . $uri[0] . '">' . $uri[0] . '</a> ', $line);
            } else {
              print $line;
            }
            print '</p>';
          }
        }
      ?>
    </div>
  </li>
</ul>
