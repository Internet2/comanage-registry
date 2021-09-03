<?php
/**
 * COmanage Registry CO Group Member Index View
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
 * @since         COmanage Registry v4.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */
?>

<?php
  // Add breadcrumbs
  print $this->element("coCrumb");

  $args = array();
  $args['plugin'] = null;
  $args['controller'] = 'co_groups';
  $args['action'] = 'index';
  $args['co'] = $cur_co['Co']['id'];
  $args['search.auto'] = 'f';
  $args['search.noadmin'] = '1';
  $this->Html->addCrumb(_txt('ct.co_groups.pl'), $args);

  $args = array(
    'controller' => 'co_groups',
    'action' => 'edit',
    $co_group['CoGroup']['id']
  );
  $this->Html->addCrumb($co_group['CoGroup']['name'], $args);

  $this->Html->addCrumb(_txt('ct.co_group_members.pl'));

  // Add page title
  $params = array();
  $params['title'] = _txt('op.view-a', array($co_group['CoGroup']['name']));

  print $this->element("pageTitleAndButtons", $params);
  include("tabs.inc");

?>

<h2 class="subtitle"><?php print _txt('ct.co_group_members.pl') ?></h2>

<?php if($co_group['CoGroup']['auto']): ?>
  <div class="co-info-topbox">
    <em class="material-icons">info</em>
    <?php print _txt('in.co_group.auto', array($cur_co['Co']['id'])); ?>
  </div>
<?php endif; ?>

<?php
  // Load the top search bar
  if(isset($permissions['search']) && $permissions['search'] ) {
    // Should be true if we're in this view, but we'll check just in case
    if(!empty($this->plugin)) {
      $fileLocation = APP . "Plugin/" . $this->plugin . "/View/CoGroupMembers/search.inc";
      if(file_exists($fileLocation))
        include($fileLocation);
    } else {
      $fileLocation = APP . "View/CoGroupMembers/search.inc";
      if(file_exists($fileLocation))
        include($fileLocation);
    }
  }
?>
  
<div class="table-container">
  <table id="groupMembers" class="common-table">
    <thead>
      <tr>
        <th><?php print _txt('fd.name'); ?></th>
        <th><?php print _txt('fd.roles'); ?></th>
        <th><?php print _txt('fd.co_people.status'); ?></th>
      </tr>
    </thead>

    <tbody>
      <?php if(empty($co_people) && $hasFilters): ?>
        <tr>
          <td colspan="3">
            <div class="co-info-topbox">
              <em class="material-icons">info</em>
              <?php print _txt('in.co_group.people.none_filters'); ?>
            </div>
          </td>
        </tr>
      <?php elseif(empty($co_people)):?>
        <tr>
          <td colspan="3">
            <div class="co-info-topbox">
              <em class="material-icons">info</em>
              <?php print _txt('in.co_group.people.none'); ?>
            </div>
          </td>
        </tr>
      <?php else: ?>
        <?php $i = 0; ?>
        <?php foreach($co_people as $p): ?>
        <tr class="line<?php print ($i % 2)+1; ?>">
          <?php
            $isMember = isset($co_group_roles['members'][ $p['CoPerson']['id'] ])
              && $co_group_roles['members'][ $p['CoPerson']['id'] ];
            $isOwner = isset($co_group_roles['owners'][ $p['CoPerson']['id'] ])
              && $co_group_roles['owners'][ $p['CoPerson']['id'] ];
          ?>
          <td>
            <?php
              $memberName = (!empty($p['PrimaryName']) ? generateCn($p['PrimaryName']) : "(?)");
              if($permissions['viewUserCanvas']) {
                print $this->Html->link($memberName,
                  array('controller' => 'co_people',
                    'action' => 'canvas',
                    $p['CoPerson']['id']));
              } else {
                print filter_var($memberName,FILTER_SANITIZE_SPECIAL_CHARS);
              }
            ?>
          </td>
          <td>
            <?php
              // Is this from a nested group?
              if(!empty($co_group_roles['members'][ $p['CoPerson']['id'] ]['co_group_nesting_id'])) {
                $nestedGroup = filter_var($co_group_roles['members'][ $p['CoPerson']['id'] ]['co_group_nesting_name'],
                  FILTER_SANITIZE_SPECIAL_CHARS);
                if($permissions['viewNestedGroup']) {
                  $nestedGroup = $this->Html->link(
                    $co_group_roles['members'][$p['CoPerson']['id']]['co_group_nesting_name'],
                    array(
                      'controller' => 'co_groups',
                      'action' => 'nest',
                      $co_group_roles['members'][$p['CoPerson']['id']]['co_group_nesting_group_id']
                    )
                  );
                }
                print '<div class="group-member-via-nested">';
                print _txt('fd.co_group_member.member.via', array($nestedGroup));
                print ' <span class="group-member-via-nested-label">' . _txt('ct.co_group_nestings.1') . '</span>';
                print '</div>';
              } else {
                // Provide membership information
                if($isOwner) {
                  if($isMember) {
                    print _txt('fd.group.grmemown');
                  } else {
                    print _txt('fd.group.own.only');
                  }
                } elseif($isMember) {
                  print _txt('fd.group.mem');
                }
              }

              // Warn if membership is invalid
              if((!empty($co_group_roles['members'][ $p['CoPerson']['id'] ]['valid_from'])
                  && strtotime($co_group_roles['members'][ $p['CoPerson']['id'] ]['valid_from']) >= time())
                ||
                (!empty($co_group_roles['members'][ $p['CoPerson']['id'] ]['valid_through'])
                  && strtotime($co_group_roles['members'][ $p['CoPerson']['id'] ]['valid_through']) < time())) {
                print ' <span class="mr-1 badge badge-warning">' . _txt('fd.inactive') . '</span>';
              }
            ?>
          </td>
          <?php $statusClass = ' status-' . (str_replace(' ', '-', strtolower(_txt('en.status', null, $p['CoPerson']['status'])))); ?>
          <td class="<?php print $statusClass ?>">
            <?php
              print _txt('en.status', null, $p['CoPerson']['status']);
            ?>
          </td>
        </tr>
        <?php $i++; ?>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php
  print $this->element("pagination");