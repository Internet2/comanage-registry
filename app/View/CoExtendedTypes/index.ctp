<!--
/**
 * COmanage Registry CO Extended Type Index View
 *
 * Copyright (C) 2012 University Corporation for Advanced Internet Development, Inc.
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
 * @copyright     Copyright (C) 2012 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.6
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */
-->
<?php
  $params = array('title' => $title_for_layout);
  print $this->element("pageTitle", $params);
?>
<?php
  if(!isset($any_extended) || !$any_extended):
?>
<div class="ui-state-highlight ui-corner-all" style="margin-top: 20px; padding: 0 .7em;"> 
  <p>
    <span class="ui-icon ui-icon-info" style="float: left; margin-right: .3em;"></span>
    <strong><?php print _txt('et.default'); ?></strong>
  </p>
</div>
<br />
<?php endif; ?>
<?php
  // XXX on select this input should reload /index/attr:foo
  print _txt('fd.et.forattr') .
        $this->Form->select('swapper',
                            $supported_attrs,
                            array(
                              'value' => Sanitize::html($this->request->params['named']['attr']),
                              'empty' => false
                            )) . '
    <br />
    <br />
    ';
  
  if($permissions['add'])
    print $this->Html->link(_txt('op.add') . ' ' . _txt('ct.co_extended_types.1'),
                            array('controller' => 'co_extended_types',
                                  'action' => 'add',
                                  'co' => $cur_co['Co']['id'],
                                  'attr' => Sanitize::html($this->request->params['named']['attr'])),
                            array('class' => 'addbutton')) . '
    <br />
    <br />
    ';
?>

<table id="cos" class="ui-widget">
  <thead>
    <tr class="ui-widget-header">
      <th><?php print $this->Paginator->sort('attribute', _txt('fd.attribute')); ?></th>
      <th><?php print $this->Paginator->sort('name', _txt('fd.name')); ?></th>
      <th><?php print $this->Paginator->sort('display_name', _txt('fd.name.d')); ?></th>
      <th><?php print $this->Paginator->sort('status', _txt('fd.status')); ?></th>
      <th><?php print _txt('fd.actions'); ?></th>
    </tr>
  </thead>
  
  <tbody>
    <?php $i = 0; ?>
    <?php foreach ($co_extended_types as $c): ?>
    <tr class="line<?php print ($i % 2)+1; ?>">
      <td><?php print Sanitize::html($c['CoExtendedType']['attribute']); ?></td>
      <td>
        <?php
          print $this->Html->link($c['CoExtendedType']['name'],
                                  array('controller' => 'co_extended_types',
                                        'action' => ($permissions['edit'] ? 'edit' : ($permissions['view'] ? 'view' : '')),
                                        $c['CoExtendedType']['id'],
                                        'co' => $cur_co['Co']['id'],
                                        'attr' => Sanitize::html($this->request->params['named']['attr'])));
        ?>
      </td>
      <td><?php print Sanitize::html($c['CoExtendedType']['display_name']); ?></td>
      <td><?php print _txt('en.status', null, $c['CoExtendedType']['status']); ?></td>
      <td>
        <?php
          if($permissions['edit'])
            print $this->Html->link(_txt('op.edit'),
                                    array('controller' => 'co_extended_types',
                                          'action' => 'edit',
                                          $c['CoExtendedType']['id'],
                                          'co' => $cur_co['Co']['id'],
                                          'attr' => Sanitize::html($this->request->params['named']['attr'])),
                                    array('class' => 'editbutton')) . "\n";
            
          if($permissions['delete'])
            print '<button class="deletebutton" title="' . _txt('op.delete') . '" onclick="javascript:js_confirm_delete(\'' . _jtxt(Sanitize::html($c['CoExtendedType']['name'])) . '\', \'' . $this->Html->url(array('controller' => 'co_extended_types', 'action' => 'delete', $c['CoExtendedType']['id'], 'co' => $cur_co['Co']['id'], 'attr' => Sanitize::html($this->request->params['named']['attr']))) . '\')";>' . _txt('op.delete') . '</button>';
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
        <?php echo $this->Paginator->numbers(); ?>
      </th>
    </tr>
  </tfoot>
</table>
