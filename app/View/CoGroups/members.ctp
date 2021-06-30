<?php
  /**
   * COmanage Registry CO Group Members Listing
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

  // Get a pointer to our model
  $model = $this->name;
  $req = Inflector::singularize($model);

  // Add page title & page buttons
  $params = array();
  $params['title'] = $title_for_layout;

  // For Authenticators during enrollment
  if(!empty($vv_co_enrollment_authenticator)
    && ($vv_co_enrollment_authenticator['CoEnrollmentAuthenticator']['required'] == RequiredEnum::Optional)
    && !empty($this->request->params['named']['onFinish'])) {
    $params['topLinks'][] = $this->Html->link(_txt('op.skip'),
      urldecode($this->request->params['named']['onFinish']),
      array('class' => 'forwardbutton'));
  }

  if(!empty($this->plugin)) {
    if(file_exists(APP . "Plugin/" . $this->plugin . "/View/" . $model . "/buttons.inc")) {
      include(APP . "Plugin/" . $this->plugin . "/View/" . $model . "/buttons.inc");
    } elseif(file_exists(LOCAL . "Plugin/" . $this->plugin . "/View/" . $model . "/buttons.inc")) {
      include(LOCAL . "Plugin/" . $this->plugin . "/View/" . $model . "/buttons.inc");
    }
  } else {
    if(file_exists(APP . "View/" . $model . "/buttons.inc")) {
      include(APP . "View/" . $model . "/buttons.inc");
    }
  }
  print $this->element("pageTitleAndButtons", $params);
  if(file_exists(APP . "View/" . $model . "/tabs.inc")) {
    include(APP . "View/" . $model . "/tabs.inc");
  }


  // Determine if fields are editable or viewable
  $dok = false;
  $e = false;
  $v = false;

  if($permissions['edit']
    || (isset($co_groups[0]['CoGroup']['id'])
      && !empty($permissions['owner'])
      && in_array($co_groups[0]['CoGroup']['id'], $permissions['owner'])))
    $e = true;

  if($permissions['delete']
    || (isset($co_groups[0]['CoGroup']['id'])
      && !empty($permissions['owner'])
      && in_array($co_groups[0]['CoGroup']['id'], $permissions['owner'])))
    $dok = true;

  if($permissions['view']
    || (isset($co_groups[0]['CoGroup']['id'])
      && !empty($permissions['member'])
      && in_array($co_groups[0]['CoGroup']['id'], $permissions['member']))
    || (isset($co_groups[0]['CoGroup']['open']) && $co_groups[0]['CoGroup']['open']))
    $v = true;

  // We shouldn't get here if we don't have at least read permission, but check just in case
  if(!$e && !$v)
    return(false);
  
  // Add breadcrumbs
  print $this->element("coCrumb");
  if($permissions['index']) {
    $args = array();
    $args['plugin'] = null;
    $args['controller'] = 'co_groups';
    $args['action'] = 'index';
    $args['co'] = $cur_co['Co']['id'];
    $this->Html->addCrumb(_txt('ct.co_groups.pl'), $args);
  }
  if($e) {
    $crumbTxt = _txt('op.edit-a', array(_txt('ct.co_groups.1')));
  } else {
    $crumbTxt = _txt('op.view-a', array(_txt('ct.co_groups.1')));
  }
  $this->Html->addCrumb($crumbTxt);

  // Index the nested groups for rendering purposes
  $nGroups = array();

  if(!empty($co_groups[0]['CoGroupNesting'])) {
    foreach($co_groups[0]['CoGroupNesting'] as $n) {
      // We filter_var here since these names are probably going to be printed
      $nGroups[ $n['id'] ] = filter_var($n['CoGroup']['name'],FILTER_SANITIZE_SPECIAL_CHARS);
    }
  }

  $l = 1;
?>

  <h2 class="subtitle"><?php print _txt('ct.co_group_members.pl'); ?></h2>

  <?php if($co_groups[0]['CoGroup']['auto']): ?>
    <div class="co-info-topbox">
      <em class="material-icons">info</em>
      <?php print _txt('in.co_group.auto', array($cur_co['Co']['id'])); ?>
    </div>
  <?php endif; ?>

  <?php if(!empty($co_groups[0]['CoGroup']['id'])
    && !$co_groups[0]['CoGroup']['auto']
    && $e): ?>
    <ul class="widget-actions">
      <li>
        <?php
          // Bulk manage / select group memberships
          print $this->Html->link(
            _txt('op.grm.manage.bulk'),
            array(
              'controller' => 'co_group_members',
              'action'     => 'select',
              'cogroup'    => $co_groups[0]['CoGroup']['id'],
              'search.members' => 1,
              'search.owners'  => 1
            ),
            array('class' => 'editbutton')
          );
        ?>
      </li>
    </ul>
  <?php endif; ?>

  <div class="table-container">
    <?php $tableCols = 3; ?>
    <table id="groupMembers" class="common-table">
      <thead>
        <tr>
          <th><?php print _txt('fd.name'); ?></th>
          <th><?php print _txt('fd.roles'); ?></th>
          <th><?php print _txt('fd.co_people.status'); ?></th>
          <?php if($e): ?>
            <th class="actionButtons"><?php print _txt('fd.actions'); ?></th>
            <?php $tableCols = 4; ?>
          <?php endif; ?>
        </tr>
      </thead>
      <tbody>
        <?php
          foreach($vv_co_group_members as $g) {
            print '<tr>';

            // Member name
            print '<td>';
            if($permissions['admin']) {
              print $this->Html->link((!empty($g['CoPerson']['PrimaryName'])
                ? generateCn($g['CoPerson']['PrimaryName'])
                : "(?)"),
                array('controller' => 'co_people',
                  'action' => 'canvas',
                  $g['CoPerson']['id']));
            } else {
              print filter_var(generateCn($g['CoPerson']['PrimaryName']),FILTER_SANITIZE_SPECIAL_CHARS);
            }
            print '</td>';

            // Group role (owner vs member)
            print '<td>';

            // Is this from a nested group?
            if(!empty($g['CoGroupMember']['co_group_nesting_id'])) {
              print _txt('fd.co_group_member.member.via', array($nGroups[ $g['CoGroupMember']['co_group_nesting_id'] ]));
            } else {
              if($g['CoGroupMember']['owner']) {
                if($g['CoGroupMember']['member']) {
                  print _txt('fd.group.grmemown');
                } else {
                  print _txt('fd.group.own.only');
                }
              } elseif($g['CoGroupMember']['member']) {
                print _txt('fd.group.mem');
              }
            }

            // Warn if membership is invalid
            if((!empty($g['CoGroupMember']['valid_from'])
                && strtotime($g['CoGroupMember']['valid_from']) >= time())
              ||
              (!empty($g['CoGroupMember']['valid_through'])
                && strtotime($g['CoGroupMember']['valid_through']) < time())) {
              print " (" . _txt('fd.inactive') . ")";
            }

            print '</td>';

            // Member's CO Person status
            print '<td>';
            if(!empty($g['CoPerson']['status'])) {
              print _txt('en.status', null, $g['CoPerson']['status']);
            }
            print '</td>';

            if($e) {
              print '<td class="actions">';
              // Do not show edit or delete buttons if this is an automatic group
              // or if the membership is due to a nesting.
              if (!$co_groups[0]['CoGroup']['auto'] && !$g['CoGroupMember']['co_group_nesting_id']) {
                print $this->Html->link(
                  _txt('op.edit'),
                  array(
                    'controller' => 'co_group_members',
                    'action' => 'edit',
                    $g['CoGroupMember']['id']
                  ),
                  array('class' => 'editbutton')
                );

                if ($dok) {
                  print '<a class="deletebutton" title="' . _txt('op.delete')
                    . '" onclick="javascript:js_confirm_generic(\''
                    . _txt('js.remove.member') . '\',\''    // dialog body text
                    . $this->Html->url(              // dialog confirm URL
                      array(
                        'controller' => 'co_group_members',
                        'action' => 'delete',
                        $g['CoGroupMember']['id'],
                        'copersonid' => $g['CoGroupMember']['co_person_id'],
                        'return' => 'group'
                      )
                    ) . '\',\''
                    . _txt('op.remove') . '\',\''    // dialog confirm button
                    . _txt('op.cancel') . '\',\''    // dialog cancel button
                    . _txt('op.remove') . '\',[\''   // dialog title
                    . filter_var(_jtxt($co_groups[0]['CoGroup']['name']), FILTER_SANITIZE_STRING)  // dialog body text replacement strings
                    . '\']);">'
                    . _txt('op.delete')
                    . '</a>';
                }
              }
              print '</td>';
            }
            print '</tr>';
          }

          if(empty($vv_co_group_members)) {
            print '<tr><td colspan="' . $tableCols . '">' . _txt('in.co_group.members.none') . '</td></tr>';
          }
        ?>
      </tbody>
    </table>
  </div>
<?php
  print $this->element("changelog");
