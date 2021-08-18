<?php
/**
 * COmanage Registry CO Enrollment Attribute Index View
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
  $args[] = $vv_coefid;
  $this->Html->addCrumb($vv_ef_name, $args);
  
  $this->Html->addCrumb(_txt('ct.co_enrollment_attributes.pl'));

  // Add page title
  $params = array();
  $params['title'] = $title_for_layout;

  // Add top links
  $params['topLinks'] = array();

  if($permissions['add']) {
    $params['topLinks'][] = $this->Html->link(
      _txt('op.add-a', array(_txt('ct.co_enrollment_attributes.1'))),
      array(
        'controller' => 'co_enrollment_attributes',
        'action' => 'add',
        'coef' => $vv_coefid
      ),
      array('class' => 'addbutton')
    );
  }

  if($permissions['order']) {
    // Reorder button
    $params['topLinks'][] = $this->Html->link(
      _txt('op.order.attr'),
      array(
        'controller' => 'co_enrollment_attributes',
        'action'     => 'order',
        'coef'       => $vv_coefid,
        'direction'  => 'asc',
        'sort'       => 'ordr'
      ),
      array('class' => 'movebutton')
    );
  }

  print $this->element("pageTitleAndButtons", $params);

?>

<div class="table-container">
  <table id="co_enrollment_attributes">
    <thead>
      <tr>
        <th><?php print $this->Paginator->sort('label', _txt('fd.ea.label')); ?></th>
        <th><?php print $this->Paginator->sort('attribute', _txt('fd.attribute')); ?></th>
        <th class="order"><?php print $this->Paginator->sort('ordr', _txt('fd.ea.order')); ?></th>
        <th><?php print $this->Paginator->sort('required', _txt('fd.required')); ?></th>
        <th><?php print _txt('fd.actions'); ?></th>
      </tr>
    </thead>

    <tbody>
      <?php $i = 0; ?>
      <?php foreach ($co_enrollment_attributes as $c): ?>
      <tr class="line<?php print ($i % 2)+1; ?>">
        <td>
          <?php
            print $this->Html->link($c['CoEnrollmentAttribute']['label'],
                                    array('controller' => 'co_enrollment_attributes',
                                          'action' => ($permissions['edit'] ? 'edit' : ($permissions['view'] ? 'view' : '')),
                                          $c['CoEnrollmentAttribute']['id']));
          ?>
        </td>
        <td>
          <?php
            $attrProps = $vv_attributes_properties[ $c['CoEnrollmentAttribute']['attribute'] ];
            print $attrProps['attrName'] . ' (' . implode(', ', array_slice(array_filter($attrProps), '1')) . ')';
          ?>
        </td>
        <td><?php print filter_var($c['CoEnrollmentAttribute']['ordr'],FILTER_SANITIZE_SPECIAL_CHARS); ?></td>
        <td><?php print filter_var(_txt('en.required', null, $c['CoEnrollmentAttribute']['required']),FILTER_SANITIZE_SPECIAL_CHARS); ?></td>
        <td>
          <?php
            if($permissions['edit']) {
              print $this->Html->link(_txt('op.edit'),
                  array(
                    'controller' => 'co_enrollment_attributes',
                    'action' => 'edit',
                    $c['CoEnrollmentAttribute']['id']
                  ),
                  array('class' => 'editbutton')) . "\n";
            }
            if($permissions['delete']) {
              print '<button type="button" class="deletebutton" title="' . _txt('op.delete')
                . '" onclick="javascript:js_confirm_generic(\''
                . _txt('js.remove') . '\',\''    // dialog body text
                . $this->Html->url(              // dialog confirm URL
                  array(
                    'controller' => 'co_enrollment_attributes',
                    'action' => 'delete',
                    $c['CoEnrollmentAttribute']['id'],
                    'coef' => $vv_coefid
                  )
                ) . '\',\''
                . _txt('op.remove') . '\',\''    // dialog confirm button
                . _txt('op.cancel') . '\',\''    // dialog cancel button
                . _txt('op.remove') . '\',[\''   // dialog title
                . filter_var(_jtxt($c['CoEnrollmentAttribute']['label']),FILTER_SANITIZE_STRING)  // dialog body text replacement strings
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