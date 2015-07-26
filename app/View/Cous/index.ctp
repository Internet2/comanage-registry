<!--
/**
 * COmanage Registry CO Index View
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
 * @since         COmanage Registry v0.2
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */
-->
<?php
  $params = array('title' => $title_for_layout);
  print $this->element("pageTitle", $params);

  // Add breadcrumbs
  print $this->element("coCrumb");
  $this->Html->addCrumb(_txt('ct.cous.pl'));

  if($permissions['add'])
    print $this->Html->link(_txt('op.add.new', array(_txt('ct.cous.1'))),
                            array('controller' => 'cous', 'action' => 'add', 'co' => $this->params['named']['co']),
                            array('class' => 'addbutton')) . '
    <br />
    <br />
    ';
?>

<table id="cous" class="ui-widget">
  <thead>
    <tr class="ui-widget-header">
      <th><?php print $this->Paginator->sort('name', _txt('fd.name')); ?></th>
      <th><?php print $this->Paginator->sort('name', _txt('fd.parent')); ?></th>
      <th><?php print $this->Paginator->sort('description', _txt('fd.desc')); ?></th>
      <th><?php print _txt('fd.actions'); ?></th>
    </tr>
  </thead>
  
  <tbody>
    <?php $i = 0; ?>
    <?php foreach ($cous as $c): ?>
    <tr class="line<?php print ($i % 2)+1; ?>">
      <td>
        <?php
          print $this->Html->link(
            $c['Cou']['name'],
            array(
              'controller' => 'cous',
              'action' => ($permissions['edit'] ? 'edit' : ($permissions['view'] ? 'view' : '')),
              $c['Cou']['id']
            )
          );
        ?>
      </td>
      <td>
        <?php
          if(!empty($c['ParentCou']['name'])) {
            print $this->Html->link(
              $c['ParentCou']['name'],
              array(
                'controller' => 'cous',
                'action' => ($permissions['edit'] ? 'edit' : ($permissions['view'] ? 'view' : '')),
                $c['ParentCou']['id']
              )
            );
          }
        ?>
      </td>
      <td><?php print Sanitize::html($c['Cou']['description']); ?></td>
      <td>
        <?php
          if($permissions['edit'])
            print $this->Html->link(
              _txt('op.edit'),
              array(
                'controller' => 'cous',
                'action' => 'edit', $c['Cou']['id']
              ),
              array('class' => 'editbutton')) . "\n";
            
          if($permissions['delete'])
            print '<button class="deletebutton" title="' . _txt('op.delete') . '" onclick="javascript:js_confirm_delete(\'' . _jtxt(Sanitize::html($c['Cou']['name'])) . '\', \'' . $this->Html->url(array('controller' => 'cous', 'action' => 'delete', $c['Cou']['id'])) . '\')";>' . _txt('op.delete') . '</button>';
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
      </th>
    </tr>
  </tfoot>
</table>
