<?php
/**
 * COmanage Registry CO Announcements Index View
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

  // Add breadcrumbs
  print $this->element("coCrumb");
  $this->Html->addCrumb(_txt('ct.co_announcements.pl'));

  // Add page title
  $params = array();
  $params['title'] = $title_for_layout;

  // Add top links
  $params['topLinks'] = array();

  if(!empty($this->request->params['named']['filter'])
     && $this->request->params['named']['filter'] == 'active') {
    $params['topLinks'][] = $this->Html->link(
      _txt('op.view.all'),
      array(
        'plugin'     => 'announcements_widget',
        'controller' => 'co_announcements',
        'action'     => 'index',
        'co'         => $cur_co['Co']['id'],
        'sort'       => 'CoAnonuncement.created',
        'direction'  => 'desc'
      ),
      array('class' => 'searchbutton')
    );
  } else {
    $params['topLinks'][] = $this->Html->link(
      _txt('op.view.current'),
      array(
        'plugin'     => 'announcements_widget',
        'controller' => 'co_announcements',
        'action'     => 'index',
        'co'         => $cur_co['Co']['id'],
        'sort'       => 'CoAnonuncement.created',
        'direction'  => 'desc',
        'filter'     => 'active'
      ),
      array('class' => 'searchbutton')
    );
  }
  
  if($permissions['add']) {
    $params['topLinks'][] = $this->Html->link(
      _txt('op.add-a',array(_txt('ct.co_announcements.1'))),
      array(
        'plugin'     => 'announcements_widget',
        'controller' => 'co_announcements',
        'action' => 'add',
        'co' => $cur_co['Co']['id']
      ),
      array('class' => 'addbutton')
    );
  }
  
  print $this->element("pageTitleAndButtons", $params);
?>

<div class="table-container">
  <table id="co_announcements">
    <thead>
      <tr>
        <th><?php print $this->Paginator->sort('created', _txt('pl.announcementswidget.posted')); ?></th>
        <th><?php print $this->Paginator->sort('title', _txt('fd.title')); ?></th>
        <th><?php print $this->Paginator->sort('co_announcement_channel_id', _txt('ct.co_announcement_channels.1')); ?></th>
        <th><?php print $this->Paginator->sort('valid_from', _txt('fd.valid_from')); ?></th>
        <th><?php print $this->Paginator->sort('valid_through', _txt('fd.valid_through')); ?></th>
        <th><?php print _txt('fd.actions'); ?></th>
      </tr>
    </thead>

    <tbody>
      <?php $i = 0; ?>
      <?php foreach ($co_announcements as $c): ?>
      <?php
        // For each announcement, determine the permissions for the user.
        // paginationConditions should filter out any announcements the current
        // user can't at least view.
        
        $canDelete = $permissions['delete']
                     || in_array($c['CoAnnouncementChannel']['author_co_group_id'], $vv_groups);
        $canEdit = $permissions['edit']
                   || in_array($c['CoAnnouncementChannel']['author_co_group_id'], $vv_groups);
        $canView = $permissions['view']
                   || in_array($c['CoAnnouncementChannel']['reader_co_group_id'], $vv_groups);
      ?>
      <tr class="line<?php print ($i % 2)+1; ?>">
        <td>
          <?php
            if($c['CoAnnouncement']['created'] > 0) {
              print $this->Time->format('Y M d', $c['CoAnnouncement']['created']);
            }
          ?>
        </td>
        <td>
          <?php
            print $this->Html->link($c['CoAnnouncement']['title'],
                                    array('controller' => 'co_announcements',
                                          'action' => ($canEdit ? 'edit' : 'view'),
                                          $c['CoAnnouncement']['id']));
          ?>
        </td>
        <td>
          <?php
            print $this->Html->link($c['CoAnnouncementChannel']['name'],
                                    array('controller' => 'co_announcement_channels',
                                          // This only works because the default permissions for
                                          // CoAnnouncements and CoAnnouncementChannels are the same...
                                          'action' => ($permissions['edit'] ? 'edit' : ($permissions['view'] ? 'view' : '')),
                                          $c['CoAnnouncementChannel']['id']));
          ?>
        </td>
        <td>
          <?php
            if($c['CoAnnouncement']['valid_from'] > 0) {
              print $this->Time->format('Y M d', $c['CoAnnouncement']['valid_from']);
            }
          ?>
        </td>
        <td>
          <?php
            if($c['CoAnnouncement']['valid_through'] > 0) {
              print $this->Time->format('Y M d', $c['CoAnnouncement']['valid_through']);
            }
          ?>
        </td>
        <td>
          <?php
            if($canView) {
              print $this->Html->link(_txt('op.view'),
                                      array('controller' => 'co_announcements',
                                            'action' => 'view',
                                            $c['CoAnnouncement']['id']),
                                      array('class' => 'viewbutton')) . "\n";
            }

            if($canEdit) {
              print $this->Html->link(_txt('op.edit'),
                                      array('controller' => 'co_announcements',
                                            'action' => 'edit',
                                            $c['CoAnnouncement']['id']),
                                      array('class' => 'editbutton')) . "\n";
            }

            if($canDelete) {
              print '<button type="button" class="deletebutton" title="' . _txt('op.delete')
                . '" onclick="javascript:js_confirm_generic(\''
                . _txt('js.remove') . '\',\''    // dialog body text
                . $this->Html->url(              // dialog confirm URL
                  array(
                    'controller' => 'co_announcements',
                    'action' => 'delete',
                    $c['CoAnnouncement']['id']
                  )
                ) . '\',\''
                . _txt('op.remove') . '\',\''    // dialog confirm button
                . _txt('op.cancel') . '\',\''    // dialog cancel button
                . _txt('op.remove') . '\',[\''   // dialog title
                . filter_var(_jtxt($c['CoAnnouncement']['title']),FILTER_SANITIZE_STRING)  // dialog body text replacement strings
                . '\']);">'
                . _txt('op.delete')
                . '</button>';
            }
          ?>
          <?php ; ?>
        </td>
      </tr>
      <?php $i++; ?>
      <?php endforeach; ?>
    </tbody>

  </table>
</div>

<?php
  print $this->element("pagination");