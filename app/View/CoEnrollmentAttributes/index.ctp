<!--
/**
 * COmanage Registry CO Enrollment Attribute Index View
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
 */
-->
<?php
  $params = array('title' => $title_for_layout);
  print $this->element("pageTitle", $params);

  print $this->Html->link(_txt('op.back'),
                          array('controller' => 'co_enrollment_flows',
                                'action' => ($permissions['edit'] ? 'edit' : 'view'),
                                Sanitize::html($this->request->params['named']['coef']),
                                'co' => $coid),
                          array('class' => 'backbutton'));
  
  if($permissions['add'])
    print $this->Html->link(_txt('op.add') . ' ' . _txt('ct.co_enrollment_attributes.1'),
                            array('controller' => 'co_enrollment_attributes', 'action' => 'add', 'coef' => Sanitize::html($this->request->params['named']['coef'])),
                            array('class' => 'addbutton')) . '
    <br />
    <br />
    ';
?>

<table id="cous" class="ui-widget">
  <thead>
    <tr class="ui-widget-header">
      <th><?php print $this->Paginator->sort('label', _txt('fd.ea.label')); ?></th>
      <th><?php print $this->Paginator->sort('attribute', _txt('fd.attribute')); ?></th>
      <th><?php print $this->Paginator->sort('ordr', _txt('fd.ea.order')); ?></th>
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
                                        'action' => ($permissions['edit'] ? 'edit' : ($permissions['view'] ? 'view' : '')), $c['CoEnrollmentAttribute']['id'], 'coef' => $this->request->params['named']['coef']));
        ?>
      </td>
      <td><?php print $available_attributes[ $c['CoEnrollmentAttribute']['attribute'] ]; ?></td>
      <td><?php print Sanitize::html($c['CoEnrollmentAttribute']['ordr']); ?></td>
      <td>
        <?php
          if($permissions['edit'])
            print $this->Html->link(_txt('op.edit'),
                                    array('controller' => 'co_enrollment_attributes', 'action' => 'edit', $c['CoEnrollmentAttribute']['id'], 'coef' => $this->request->params['named']['coef']),
                                    array('class' => 'editbutton')) . "\n";
            
          if($permissions['delete'])
            print '<button class="deletebutton" title="' . _txt('op.delete') . '" onclick="javascript:js_confirm_delete(\'' . _jtxt(Sanitize::html($c['CoEnrollmentAttribute']['label'])) . '\', \'' . $this->Html->url(array('controller' => 'co_enrollment_attributes', 'action' => 'delete', $c['CoEnrollmentAttribute']['id'], 'coef' => $this->request->params['named']['coef'])) . '\')";>' . _txt('op.delete') . '</button>';
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
