<?php
  /**
   * COmanage Registry CO Group Nesting Subnavigation Tabs
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

<?php if($this->action == 'add'): ?>

  <?php
  $groupId = $vv_current_group;
  ?>

  <nav id="cm-group-subnav-tabs" class="cm-subnav-tabs">
    <ul class="nav nav-tabs">

      <li class="nav-item">
        <?php
          // Properties
          print $this->Html->link(
            _txt('me.properties'),
            array(
              'controller' => 'co_groups',
              'action' => 'edit',
              $groupId
            ),
            array('class' => 'nav-link spin')
          );
        ?>
      </li>

      <li class="nav-item">
        <?php
          // Group memberships
          print $this->Html->link(
            _txt('me.members'),
            array('controller' => 'co_group_members',
              'action' => 'select',
              'cogroup:' . $groupId,
              'search.members:1',
              'search.owners:1'
            ),
            array('class' => 'nav-link spin')
          );
        ?>
      </li>

      <li class="nav-item">
        <?php
          // Nested groups
          print $this->Html->link(
            _txt('ct.co_group_nestings.pl'),
            array(
              'controller' => 'co_groups',
              'action' => 'nest',
              $groupId
            ),
            array('class' => 'nav-link active')
          );
        ?>
      </li>

      <li class="nav-item">
        <?php
          // Provisioned services
          print $this->Html->link(
            _txt('op.prov.view'),
            array(
              'controller' => 'co_groups',
              'action' => 'provision',
              $groupId
            ),
            array('class' => 'nav-link spin')
          );
        ?>
      </li>

      <li class="nav-item">
        <?php
          // Email lists
          print $this->Html->link(
            _txt('ct.co_email_lists.pl'),
            array(
              'controller' => 'co_groups',
              'action' => 'email_lists',
              $groupId
            ),
            array('class' => 'nav-link spin')
          );
        ?>
      </li>

    </ul>
  </nav>

<?php endif; ?>