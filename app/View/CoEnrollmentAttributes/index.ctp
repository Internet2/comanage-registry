<!--
/**
 * COmanage Registry CO Enrollment Attribute Index View
 *
 * Copyright (C) 2011-15 University Corporation for Advanced Internet Development, Inc.
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
 * @copyright     Copyright (C) 2011-15 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.3
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */
-->
<?php
  $params = array('title' => $title_for_layout);
  print $this->element("pageTitle", $params);

  // Add breadcrumbs
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

  // Add buttons to sidebar
  $sidebarButtons = $this->get('sidebarButtons');
  
  // Add button
  if($permissions['add']) {
    $sidebarButtons[] = array(
      'icon'    => 'circle-plus',
      'title'   => _txt('op.add') . ' ' . _txt('ct.co_enrollment_attributes.1'),
      'url'     => array(
        'controller' => 'co_enrollment_attributes', 
        'action' => 'add', 
        'coef' => $vv_coefid
      )
    );
  }

  if($permissions['order']) {
    // Reorder button
    $sidebarButtons[] = array(
      'icon'    => 'pencil',
      'title'   => _txt('op.order.attr'),
      'url'     => array(
        'controller' => 'co_enrollment_attributes',
        'action'     => 'order',
        'coef'       => $vv_coefid,
        'direction'  => 'asc',
        'sort'       => 'ordr'
      )
    );
  }

  $this->set('sidebarButtons', $sidebarButtons);

?>

<table id="co_enrollment_attributes" class="ui-widget">
  <thead>
    <tr class="ui-widget-header">
      <th><?php print $this->Paginator->sort('label', _txt('fd.ea.label')); ?></th>
      <th><?php print $this->Paginator->sort('attribute', _txt('fd.attribute')); ?></th>
      <th><?php print $this->Paginator->sort('ordr', _txt('fd.ea.order')); ?></th>
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
      <td><?php print $vv_available_attributes[ $c['CoEnrollmentAttribute']['attribute'] ]; ?></td>
      <td><?php print Sanitize::html($c['CoEnrollmentAttribute']['ordr']); ?></td>
      <td><?php print Sanitize::html(_txt('en.required', null, $c['CoEnrollmentAttribute']['required'])); ?></td>
      <td>
        <?php
          if($permissions['edit'])
            print $this->Html->link(_txt('op.edit'),
                                    array('controller' => 'co_enrollment_attributes',
                                          'action' => 'edit',
                                          $c['CoEnrollmentAttribute']['id']),
                                    array('class' => 'editbutton')) . "\n";
            
          if($permissions['delete'])
            print '<button class="deletebutton" title="' . _txt('op.delete') . '" onclick="javascript:js_confirm_delete(\'' . _jtxt(Sanitize::html($c['CoEnrollmentAttribute']['label'])) . '\', \'' . $this->Html->url(array('controller' => 'co_enrollment_attributes', 'action' => 'delete', $c['CoEnrollmentAttribute']['id'], 'coef' => $vv_coefid)) . '\')";>' . _txt('op.delete') . '</button>';
        ?>
        <?php ; ?>
      </td>
    </tr>
    <?php $i++; ?>
    <?php endforeach; ?>
  </tbody>
  
  <tfoot>
    <tr class="ui-widget-header">
      <th colspan="5">
        <?php print $this->Paginator->numbers(); ?>
      </th>
    </tr>
  </tfoot>
</table>
