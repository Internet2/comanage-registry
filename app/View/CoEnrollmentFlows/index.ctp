<!--
/**
 * COmanage Registry CO Enrollment Flow Index View
 *
 * Copyright (C) 2011-12 University Corporation for Advanced Internet Development, Inc.
 * 
 * Licensed under the Apache License, Version 2.0 (the "License"); you may not use this file except in compliance with
 * the License. You may obtain a copy of the License at
 * 
 * http://www.apache.org/licenses/LICENSE-2.0
 * 
 * Unless required by applicable law or agreed to in writing, software distributed under
 * the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY
 * KIND, either express or implied. See the License for the specific language governing
 * permissions and limitations under the License.
 *
 * @copyright     Copyright (C) 2011-12 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.3
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */-->
<?php
  $params = array('title' => $title_for_layout);
  print $this->element("pageTitle", $params);

  if($permissions['add'])
    print $this->Html->link(_txt('op.add') . ' ' . _txt('ct.co_enrollment_flows.1'),
                            array('controller' => 'co_enrollment_flows', 'action' => 'add', 'co' => $this->request->params['named']['co']),
                            array('class' => 'addbutton')) . '
    <br />
    <br />
    ';
?>

<table id="cous" class="ui-widget">
  <thead>
    <tr class="ui-widget-header">
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
      <td><?php print _txt('en.status', null, $c['CoEnrollmentFlow']['status']); ?></td>
      <td>
        <?php
          print _txt('en.enrollment.authz', null, $c['CoEnrollmentFlow']['authz_level']);
          
          if($c['CoEnrollmentFlow']['authz_level'] == EnrollmentAuthzEnum::CoGroupMember) {
            print " ("
                  . $this->Html->link($c['CoEnrollmentFlow']['authz_co_group_id'],
                                      array(
                                       'controller' => 'co_groups',
                                       'action' => 'view',
                                       $c['CoEnrollmentFlow']['authz_co_group_id'],
                                       'co' => $c['CoEnrollmentFlow']['co_id']
                                      ))
                  . ")";
          }
          
          if($c['CoEnrollmentFlow']['authz_level'] == EnrollmentAuthzEnum::CouAdmin
             || $c['CoEnrollmentFlow']['authz_level'] == EnrollmentAuthzEnum::CouPerson) {
            print " ("
                  . $this->Html->link($c['CoEnrollmentFlow']['authz_cou_id'],
                                      array(
                                       'controller' => 'cous',
                                       'action' => 'view',
                                       $c['CoEnrollmentFlow']['authz_cou_id'],
                                       'co' => $c['CoEnrollmentFlow']['co_id']
                                      ))
                  . ")";
          }
        ?>
      </td>
      <td>
        <?php
          if($permissions['edit'])
            print $this->Html->link(_txt('op.edit'),
                                    array('controller' => 'co_enrollment_flows', 'action' => 'edit', $c['CoEnrollmentFlow']['id'], 'co' => $this->request->params['named']['co']),
                                    array('class' => 'editbutton')) . "\n";
            
          if($permissions['delete'])
            print '<button class="deletebutton" title="' . _txt('op.delete') . '" onclick="javascript:js_confirm_delete(\'' . _jtxt(Sanitize::html($c['CoEnrollmentFlow']['name'])) . '\', \'' . $this->Html->url(array('controller' => 'co_enrollment_flows', 'action' => 'delete', $c['CoEnrollmentFlow']['id'], 'co' => $this->request->params['named']['co'])) . '\')";>' . _txt('op.delete') . '</button>';
        ?>
        <?php ; ?>
      </td>
    </tr>
    <?php $i++; ?>
    <?php endforeach; ?>
  </tbody>
  
  <tfoot>
    <tr class="ui-widget-header">
      <th colspan="4">
        <?php print $this->Paginator->numbers(); ?>
      </td>
    </tr>
  </tfoot>
</table>
