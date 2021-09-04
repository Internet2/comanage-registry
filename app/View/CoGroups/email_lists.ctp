<?php
  /**
   * COmanage Registry CO Group Email Lists
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

  if($permissions['edit_email_lists'])
    $e = true;

  if($permissions['delete'])
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
    $args['search.auto'] = 'f';
    $args['search.noadmin'] = '1';
    $this->Html->addCrumb(_txt('ct.co_groups.pl'), $args);
  }
  if($e) {
    $crumbTxt = _txt('op.edit-a', array(_txt('ct.co_groups.1')));
  } else {
    $crumbTxt = _txt('op.view-a', array(_txt('ct.co_groups.1')));
  }
  $this->Html->addCrumb($crumbTxt);

  $l = 1;
?>

<h2 class="subtitle"><?php print _txt('in.co_group.email_lists'); ?></h2>

<div class="table-container">
  <?php $tableCols = 3; ?>
  <table id="emailLists" class="common-table">
    <thead>
      <tr>
        <th><?php print _txt('fd.name'); ?></th>
        <th><?php print _txt('fd.status'); ?></th>
        <th><?php print _txt('fd.type'); ?></th>
        <?php if($e): ?>
          <th class="actionButtons"><?php print _txt('fd.actions'); ?></th>
          <?php $tableCols = 4; ?>
        <?php endif; ?>
      </tr>
    </thead>
    <tbody>
      <?php
        $mailingListsExist = false;
        foreach(array('Admin', 'Member', 'Moderator') as $listType) {
          if(!empty($co_groups[0]['EmailList'.$listType])) {
            $mailingListsExist = true;
            foreach($co_groups[0]['EmailList'.$listType] as $el) {
              print "<tr>";

              // List name
              print "<td>";
              if($e) {
                print $this->Html->link($el['name'],
                  array('controller' => 'co_email_lists',
                    'action' => 'edit',
                    $el['id']));
              } else {
                print filter_var($el['name'],FILTER_SANITIZE_SPECIAL_CHARS);
              }
              print "</td>";

              // List's status
              print "<td>";
              if(!empty($el['status'])) {
                print _txt('en.status', null, $el['status']);
              }
              print "</td>";

              // List's usage
              print "<td>" . _txt('fd.el.gr.'.Inflector::tableize($listType)) . "</td>";

              if($e) {
                print '<td class="actions">'
                  . $this->Html->link(_txt('op.edit'),
                    array('controller' => 'co_email_lists',
                      'action' => 'edit',
                      $el['id']),
                    array('class' => 'viewbutton'))
                  . "</td>";
              }

              print "</tr>\n";
            }
          }
        }

        if(!$mailingListsExist) {
          print '<tr><td colspan="' . $tableCols . '">' . _txt('in.co_email_lists.none') . '</td></tr>';
        }
      ?>
    </tbody>
  </table>
</div>

