<?php
/**
 * COmanage Registry CO Enrollment Source Index View
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
 * @since         COmanage Registry v2.0.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

  // Add breadcrumbs
  print $this->element("coCrumb");
  $args = array();
  $args['plugin'] = null;
  $args['controller'] = 'co_enrollment_flows';
  $args['action'] = 'index';
  $args['co'] = $cur_co['Co']['id'];
  $this->Html->addCrumb(_txt('ct.co_enrollment_flows.pl'), $args);
  
  $args = array();
  $args['plugin'] = null;
  $args['controller'] = 'co_enrollment_flows';
  $args['action'] = 'edit';
  $args[] = $vv_ef_id;
  $this->Html->addCrumb($vv_ef_name, $args);
  
  $this->Html->addCrumb(_txt('ct.co_enrollment_sources.pl'));

  // Add page title
  $params = array();
  $params['title'] = $title_for_layout;

  // Add top links
  $params['topLinks'] = array();

  if($permissions['add']) {
    $params['topLinks'][] = $this->Html->link(
      _txt('op.add-a', array(_txt('ct.co_enrollment_sources.1'))),
      array(
        'controller' => 'co_enrollment_sources',
        'action' => 'add',
        'coef' => $vv_ef_id
      ),
      array('class' => 'addbutton')
    );
  }

  /* XXX ordering not yet supported
  if($permissions['order']) {
    // Reorder button
    $params['topLinks'][] = $this->Html->link(
      _txt('op.order.attr'),
      array(
        'controller' => 'co_enrollment_sources',
        'action'     => 'order',
        'coef'       => $vv_ef_id,
        'direction'  => 'asc',
        'sort'       => 'ordr'
      ),
      array('class' => 'movebutton')
    );
  }*/

  print $this->element("pageTitleAndButtons", $params);
?>

<div class="table-container">
  <table id="co_enrollment_sources">
    <thead>
      <tr>
  <!-- XXX this is sorting by ID, but rendering by name -->
        <th><?php print $this->Paginator->sort('org_identity_source_id', _txt('ct.org_identity_sources.1')); ?></th>
        <th><?php print $this->Paginator->sort('org_identity_mode', _txt('fd.ef.orgid')); ?></th>
        <th class="order"><?php print $this->Paginator->sort('ordr', _txt('fd.order')); ?></th>
        <th><?php print _txt('fd.actions'); ?></th>
      </tr>
    </thead>

    <tbody>
      <?php $i = 0; ?>
      <?php foreach ($co_enrollment_sources as $c): ?>
      <tr class="line<?php print ($i % 2)+1; ?>">
        <td>
          <?php
            print $this->Html->link($vv_all_ois[ $c['CoEnrollmentSource']['org_identity_source_id'] ],
                                    array('controller' => 'co_enrollment_sources',
                                          'action' => ($permissions['edit'] ? 'edit' : ($permissions['view'] ? 'view' : '')),
                                          $c['CoEnrollmentSource']['id']));

            if(!isset($vv_avail_ois[ $c['CoEnrollmentSource']['org_identity_source_id'] ])) {
              // This source has been disabled
              print "&nbsp;(" . _txt('en.status.susp', null, SuspendableStatusEnum::Suspended) . ")";
            }
          ?>
        </td>
        <td><?php print _txt('en.enrollment.orgid', null, $c['CoEnrollmentSource']['org_identity_mode']); ?></td>
        <td><?php print $c['CoEnrollmentSource']['ordr']; ?></td>
        <td>
          <?php
            if($permissions['edit']) {
              print $this->Html->link(_txt('op.edit'),
                  array(
                    'controller' => 'co_enrollment_sources',
                    'action' => 'edit',
                    $c['CoEnrollmentSource']['id']
                  ),
                  array('class' => 'editbutton')) . "\n";
            }
            if($permissions['delete']) {
              print '<button type="button" class="deletebutton" title="' . _txt('op.delete')
                . '" onclick="javascript:js_confirm_generic(\''
                . _txt('js.remove') . '\',\''    // dialog body text
                . $this->Html->url(              // dialog confirm URL
                  array(
                    'controller' => 'co_enrollment_sources',
                    'action' => 'delete',
                    $c['CoEnrollmentSource']['id'],
                    'coef' => $vv_ef_id
                  )
                ) . '\',\''
                . _txt('op.remove') . '\',\''    // dialog confirm button
                . _txt('op.cancel') . '\',\''    // dialog cancel button
                . _txt('op.remove') . '\',[\''   // dialog title
                . filter_var(_jtxt($vv_all_ois[ $c['CoEnrollmentSource']['org_identity_source_id'] ]),FILTER_SANITIZE_STRING)  // dialog body text replacement strings
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