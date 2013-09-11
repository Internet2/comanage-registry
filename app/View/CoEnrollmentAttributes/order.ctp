<!--
/**
 * COmanage Registry CO Enrollment Attribute Order View
 *
 * Copyright (C) 2011-13 University Corporation for Advanced Internet Development, Inc.
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
 * @copyright     Copyright (C) 2011-13 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.8.2
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */
-->

<style>
  .order .ui-icon{
    float: left;
  }

  .order {
    padding-left: 18px;
  }
</style>

<?php
  // Set page title
  $params = array('title' => $title_for_layout);
  print $this->element("pageTitle", $params);

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
  
  $this->set('sidebarButtons', $sidebarButtons);

  // Enable and configure drag/drop sorting 
  $this->Js->get('#sortable');
  $this->Js->sortable(array(
    'complete' => '$.post("/registry/co_enrollment_attributes/reorder", $("#sortable").sortable("serialize"))',
    ));
?>

<table id="enrollment_attributes" class="ui-widget">
  <thead>
    <tr class="ui-widget-header">
      <th><?php print _txt('fd.ea.order'); ?></th>
      <th><?php print _txt('fd.ea.label'); ?></th>
      <th><?php print _txt('fd.attribute'); ?></th>
    </tr>
  </thead>
  
  <tbody id="sortable">
    <?php foreach ($co_enrollment_attributes as $c): ?>
      <tr id = "CoEnrollmentAttributeId_<?php print $c['CoEnrollmentAttribute']['id']?>" class="line1">
         <td class = "order">
          <span class="ui-icon ui-icon-arrowthick-2-n-s"></span> 
        </td>
        <td>
          <?php
            print $this->Html->link($c['CoEnrollmentAttribute']['label'],
                                    array('controller' => 'co_enrollment_attributes',
                                          'action' => ($permissions['edit'] ? 'edit' : ($permissions['view'] ? 'view' : '')),
                                          $c['CoEnrollmentAttribute']['id'],
                                          'coef' => $vv_coefid));
          ?>
        </td>
        <td><?php print $vv_available_attributes[ $c['CoEnrollmentAttribute']['attribute'] ]; ?></td>
      </tr>
    <?php endforeach; ?>
  </tbody>
  
  <tfoot>
    <tr class="ui-widget-header">
      <th colspan="3">
        <?php print $this->Paginator->numbers(); ?>
      </th>
    </tr>
  </tfoot>
</table>

<?php 
  // Buffer javascript and run after page elements are in place
  print $this->Js->writeBuffer();
?>