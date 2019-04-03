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
  // Add breadcrumbs
  print $this->element("coCrumb");
  $this->Html->addCrumb(_txt('ct.co_notifications.pl'));

  // Add page title
  $params = array();
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
  
  $furl = "/registry/co_notifications/index/sort:" . $sorttype
        . "/direction:" . $sortdir
        . "/" . $vv_request_type . ":" . $vv_co_person_id;
?>

<div id="notificationsFilter" class="top-filter">
  <form method="get" id="notificationStatus" action="<?php print $furl; ?>">
    <span class="filters">
      <label for="status" class="select-name"><?php print _txt('op.filter.status'); ?></label>
      <select id="status" name="status">
        <option value=""><?php print _txt('fd.unresolved'); ?></option>
        <option value="all"<?php if($curstatus == "all") print " selected";?>><?php print _txt('fd.all'); ?></option>

        <?php
          foreach(array_keys($vv_notification_statuses) as $s) {
            print "<option value=\"" . $s . "\"";

            if($s == $curstatus) {
              print " selected";
            }

            print ">" . $vv_notification_statuses[$s] . "</option>\n";
          }
        ?>
      </select>
    </span>
    <span class="submit-button">
      <input type="submit" value="<?php print _txt('op.filter')?>"/>
    </span>
  </form>
</div>

<div class="table-container">
  <table id="co_notifications">
    <thead>
      <tr>
        <th><?php print $this->Paginator->sort('action', _txt('fd.action')); ?></th>
        <th><?php print $this->Paginator->sort('comment', _txt('fd.comment')); ?></th>
        <th><?php print $this->Paginator->sort('created', _txt('fd.created.tz', array($vv_tz))); ?></th>
        <th><?php print $this->Paginator->sort('resolution_time', _txt('fd.resolved.tz', array($vv_tz))); ?></th>
      </tr>
    </thead>

    <tbody>
      <?php $i = 0; ?>
      <?php foreach ($co_notifications as $c): ?>
      <tr class="line<?php print ($i % 2)+1; ?>">
        <td><?php print filter_var($c['CoNotification']['action'],FILTER_SANITIZE_SPECIAL_CHARS); ?></td>
        <td><?php print $this->Html->link($c['CoNotification']['comment'],
                                          array(
                                            'controller' => 'co_notifications',
                                            'action'     => 'view',
                                            $c['CoNotification']['id']
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
      </tr>
      <?php $i++; ?>
      <?php endforeach; ?>
    </tbody>

  </table>
</div>

<?php
  print $this->element("pagination");