<?php
  /**
   * COmanage Registry CO Group Members Subnavigation Tabs
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

<?php if($this->action == 'select' || 
         $this->action == 'index'): ?>

  <nav id="cm-group-subnav-tabs" class="cm-subnav-tabs">
    <ul class="nav nav-tabs">

      <li class="nav-item">
        <?php
          // Properties
          if ($this->action == 'edit' || $this->action == 'view') {
            print '<span class="nav-link active">' . _txt('me.properties') . '</span>';
          } else {
            if ($permissions['edit']) {
              print $this->Html->link(
                _txt('me.properties'),
                array(
                  'controller' => 'co_groups',
                  'action' => 'edit',
                  $co_group['CoGroup']['id']
                ),
                array('class' => 'nav-link spin')
              );
            } else {
              print $this->Html->link(
                _txt('me.properties'),
                array(
                  'controller' => 'co_groups',
                  'action' => 'view',
                  $co_group['CoGroup']['id']
                ),
                array('class' => 'nav-link spin')
              );
            }
          }
        ?>
      </li>

      <li class="nav-item">
        <?php
          // Group memberships
          print '<span class="nav-link active">' . _txt('me.members') . '</span>';
        ?>
      </li>

      <li class="nav-item">
        <?php
          // Nested groups
          if ($this->action == 'nest') {
            print '<span class="nav-link active">' . _txt('ct.co_group_nestings.pl') . '</span>';
          } else {
            print $this->Html->link(
              _txt('ct.co_group_nestings.pl'),
              array(
                'controller' => 'co_groups',
                'action' => 'nest',
                $co_group['CoGroup']['id']
              ),
              array('class' => 'nav-link spin')
            );
          }
        ?>
      </li>

      <?php if($permissions['provision']): ?>
        <li class="nav-item">
          <?php
            // Provisioned services
            print $this->Html->link(
              _txt('op.prov.view'),
              array(
                'controller' => 'co_groups',
                'action' => 'provision',
                $co_group['CoGroup']['id']
              ),
              array('class' => 'nav-link spin')
            );
          ?>
        </li>
      <?php endif; ?>

      <li class="nav-item">
        <?php
          // Email lists
          if ($this->action == 'email_lists') {
            print '<span class="nav-link active">' . _txt('ct.co_email_lists.pl') . '</span>';
          } else {
            print $this->Html->link(
              _txt('ct.co_email_lists.pl'),
              array(
                'controller' => 'co_groups',
                'action' => 'email_lists',
                $co_group['CoGroup']['id']
              ),
              array('class' => 'nav-link spin')
            );
          }
        ?>
      </li>

    </ul>
  </nav>

<?php endif; ?>