<?php
/**
 * COmanage Registry CO Enrollment Flow Index View
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
 * @since         COmanage Registry v0.3
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

  // Add breadcrumbs
  print $this->element("coCrumb");
  $this->Html->addCrumb(_txt('ct.co_enrollment_flows.pl'));

  // Add page title
  $params = array();
  $params['title'] = $title_for_layout;

  // Add top links
  $params['topLinks'] = array();

  if($permissions['add']) {
    $params['topLinks'][] = $this->Html->link(
      _txt('op.add-a', array(_txt('ct.co_enrollment_flows.1'))),
      array(
        'controller' => 'co_enrollment_flows',
        'action' => 'add',
        'co' => $cur_co['Co']['id']
      ),
      array('class' => 'addbutton')
    );
    $params['topLinks'][] = $this->Html->link(
      _txt('op.restore.ef'),
      array(
        'controller' => 'co_enrollment_flows',
        'action' => 'addDefaults',
        'co' => $cur_co['Co']['id']
      ),
      array('class' => 'addbutton')
    );
  }

  print $this->element("pageTitleAndButtons", $params);

  // Search Block
  if(!empty($vv_search_fields)) {
    print $this->element('search', array('vv_search_fields' => $vv_search_fields));
  }

?>

<div class="table-container">
  <table id="co_enrollment_flows">
    <thead>
      <tr>
        <th><?php print $this->Paginator->sort('name', _txt('fd.name')); ?></th>
        <th><?php print $this->Paginator->sort('status', _txt('fd.status')); ?></th>
        <th><?php print $this->Paginator->sort('authz_level', _txt('fd.ef.authz')); ?></th>
        <th><?php print _txt('fd.actions'); ?></th>
      </tr>
    </thead>

    <tbody>
      <?php $i = 0; ?>
      <?php foreach ($co_enrollment_flows as $c): ?>
      <tr class="line<?php print ($i % 2)+1; ?>">
        <td>
          <?php
            print $this->Html->link($c['CoEnrollmentFlow']['name'],
                                    array('controller' => 'co_enrollment_flows',
                                          'action' => ($permissions['edit'] ? 'edit' : ($permissions['view'] ? 'view' : '')), $c['CoEnrollmentFlow']['id'], 'co' => $this->request->params['named']['co']));
          ?>
        </td>
        <td><?php print _txt('en.status.temp', null, $c['CoEnrollmentFlow']['status']); ?></td>
        <td>
          <?php
            print _txt('en.enrollment.authz', null, $c['CoEnrollmentFlow']['authz_level']);

            if($c['CoEnrollmentFlow']['authz_level'] == EnrollmentAuthzEnum::CoGroupMember) {
              print " ("
                    . $this->Html->link($c['CoEnrollmentFlowAuthzCoGroup']['name'],
                                        array(
                                         'controller' => 'co_groups',
                                         'action' => 'view',
                                         $c['CoEnrollmentFlow']['authz_co_group_id']
                                        ))
                    . ")";
            }

            if($c['CoEnrollmentFlow']['authz_level'] == EnrollmentAuthzEnum::CouAdmin
               || $c['CoEnrollmentFlow']['authz_level'] == EnrollmentAuthzEnum::CouPerson) {
              print " ("
                    . $this->Html->link($c['CoEnrollmentFlowAuthzCou']['name'],
                                        array(
                                         'controller' => 'cous',
                                         'action' => 'view',
                                         $c['CoEnrollmentFlow']['authz_cou_id']
                                        ))
                    . ")";
            }
          ?>
        </td>
        <td>
          <?php
            if($permissions['select']
               && $c['CoEnrollmentFlow']['status'] == TemplateableStatusEnum::Active) {
              print $this->Html->link(_txt('op.begin'),
                                      array(
                                        'controller' => 'co_petitions',
                                        'action' => 'start',
                                        'coef' => $c['CoEnrollmentFlow']['id']
                                      ),
                                      array('class' => 'forwardbutton')) . "\n";
            }

            if($permissions['edit']) {
              print $this->Html->link(_txt('op.edit'),
                                      array('controller' => 'co_enrollment_flows', 'action' => 'edit', $c['CoEnrollmentFlow']['id']),
                                      array('class' => 'editbutton')) . "\n";
            }

            if($permissions['duplicate']) {
              print $this->Html->link(_txt('op.dupe'),
                                      array('controller' => 'co_enrollment_flows', 'action' => 'duplicate', $c['CoEnrollmentFlow']['id']),
                                      array('class' => 'copybutton')) . "\n";
            }

            if($permissions['delete']) {
              print '<button type="button" class="deletebutton" title="' . _txt('op.delete')
                . '" onclick="javascript:js_confirm_generic(\''
                . _txt('js.remove') . '\',\''    // dialog body text
                . $this->Html->url(              // dialog confirm URL
                  array(
                    'controller' => 'co_enrollment_flows',
                    'action' => 'delete',
                    $c['CoEnrollmentFlow']['id']
                  )
                ) . '\',\''
                . _txt('op.remove') . '\',\''    // dialog confirm button
                . _txt('op.cancel') . '\',\''    // dialog cancel button
                . _txt('op.remove') . '\',[\''   // dialog title
                . filter_var(_jtxt($c['CoEnrollmentFlow']['name']),FILTER_SANITIZE_STRING)  // dialog body text replacement strings
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
