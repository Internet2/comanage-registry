<?php
  /**
   * COmanage Registry CO Group Subnavigation Tabs
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
   * @since         COmanage Registry v4.0.0
   * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
   */
?>

<?php if($this->action == 'edit'
      || $this->action == 'view'
      || $this->action == 'members'
      || $this->action == 'provision'
      || $this->action == 'nest'
      || $this->action == 'email_lists'): ?>

  <?php
  $groupId = null;
  if ($this->action == 'provision') {
    $groupId = $co_group['CoGroup']['id'];
  } else {
    $groupId = $co_groups[0]['CoGroup']['id'];
  }
  ?>

  <div id="cm-group-subnav-tabs" class="cm-subnav-tabs mdl-tabs">
    <nav class="mdl-tabs__tab-bar">
      <?php

        // Group memberships
        if ($this->action == 'members') {
          print '<span class="mdl-tabs__tab selected">' . _txt('me.members') . '</span>';
        } else {
          print $this->Html->link(
            _txt('me.members'),
            array(
              'controller' => 'co_groups',
              'action' => 'members',
              $groupId
            ),
            array('class' => 'mdl-tabs__tab')
          );
        }

        // Nested groups
        if ($this->action == 'nest') {
          print '<span class="mdl-tabs__tab selected">' . _txt('ct.co_group_nestings.pl') . '</span>';
        } else {
          print $this->Html->link(
            _txt('ct.co_group_nestings.pl'),
            array(
              'controller' => 'co_groups',
              'action' => 'nest',
              $groupId
            ),
            array('class' => 'mdl-tabs__tab')
          );
        }

        // Provisioned services
        if($this->action == 'provision' || ($permissions['provision'] && !empty($co_groups[0]['CoGroup']['id']))) {
          if ($this->action == 'provision') {
            print '<span class="mdl-tabs__tab selected">' . _txt('op.prov.view') . '</span>';
          } else {
            print $this->Html->link(
              _txt('op.prov.view'),
              array(
                'controller' => 'co_groups',
                'action' => 'provision',
                $groupId
              ),
              array('class' => 'mdl-tabs__tab')
            );
          }
        }

        // Email lists
        if ($this->action == 'email_lists') {
          print '<span class="mdl-tabs__tab selected">' . _txt('ct.co_email_lists.pl') . '</span>';
        } else {
          print $this->Html->link(
            _txt('ct.co_email_lists.pl'),
            array(
              'controller' => 'co_groups',
              'action' => 'email_lists',
              $groupId
            ),
            array('class' => 'mdl-tabs__tab')
          );
        }

        // Properties
        if ($this->action == 'edit' || $this->action == 'view') {
          print '<span class="mdl-tabs__tab selected">' . _txt('me.properties') . '</span>';
        } else {
          if ($permissions['edit']) {
            print $this->Html->link(
              _txt('me.properties'),
              array(
                'controller' => 'co_groups',
                'action' => 'edit',
                $groupId
              ),
              array('class' => 'mdl-tabs__tab')
            );
          } else {
            print $this->Html->link(
              _txt('me.properties'),
              array(
                'controller' => 'co_groups',
                'action' => 'view',
                $groupId
              ),
              array('class' => 'mdl-tabs__tab')
            );
          }
        }
      ?>
    </nav>
  </div>

<?php endif; ?>