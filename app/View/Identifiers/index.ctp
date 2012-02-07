<!--
/**
 * COmanage Registry Identifier Index View
 *
 * Copyright (C) 2010-12 University Corporation for Advanced Internet Development, Inc.
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
 * @since         COmanage Registry v0.1
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */
-->
<h1 class="ui-state-default"><?php echo _txt('ct.identifiers.pl'); ?></h1>

<table id="identifiers" class="ui-widget">
  <thead>
    <tr class="ui-widget-header">
      <th><?php echo $this->Paginator->sort('identifier', _txt('fd.id')); ?></th>
      <th><?php echo $this->Paginator->sort('type', _txt('fd.type')); ?></th>
      <th><?php echo $this->Paginator->sort('login', _txt('fd.login')); ?></th>
      <!-- XXX Following needs to be I18N'd, and also render a full name, if index view sticks around -->
      <th><?php echo $this->Paginator->sort('OrgIdentity.Name.family', 'Org Identity'); ?></th>
      <th><?php echo $this->Paginator->sort('CoPerson.Name.family', 'CO Person'); ?></th>
      <th><?php echo _txt('fd.actions'); ?></th>
    </tr>
  </thead>
  
  <tbody>
    <?php $i = 0; ?>
    <?php foreach ($identifiers as $a): ?>
    <tr class="line<?php print ($i % 2)+1; ?>">
      <td>
        <?php
          echo $this->Html->link($a['Identifier']['identifier'],
                                 array(
                                  'controller' => 'identifiers',
                                  'action' => ($permissions['edit']
                                               ? 'edit'
                                               : ($permissions['view'] ? 'view' : '')),
                                  $a['Identifier']['id']
                                 ));
        ?>
      </td>
      <td>
        <?php echo _txt('en.identifier', null, $a['Identifier']['type']); ?>
      </td>
      <td>
        <?php echo ($a['Identifier']['login'] ? _txt('fd.true') : _txt('fd.false')); ?>
      </td>
      <td>
        <?php
          if(!empty($a['Identifier']['org_identity_id']))
          {
            // Generally, someone who has view permission on an attribute number can also see a person
            if($permissions['view'])
              echo $this->Html->link(generateCn($a['OrgIdentity']['Name']),
                                     array('controller' => 'org_identities', 'action' => 'view', $a['OrgIdentity']['id'])) . "\n";
          }
        ?>
      </td>
      <td>
        <?php
          if(!empty($a['Identifier']['co_person_id']))
          {
            // Generally, someone who has view permission on an attribute can also see a person
            if($permissions['view'])
              echo $this->Html->link(generateCn($a['CoPerson']['Name']),
                                     array('controller' => 'co_people', 'action' => 'view', $a['CoPerson']['id'])) . "\n";
          }
        ?>
      </td>    
      <td>    
        <?php
          if($permissions['edit'])
            echo $this->Html->link('Edit',
                                   array('controller' => 'identifiers', 'action' => 'edit', $a['Identifier']['id']),
                                   array('class' => 'editbutton')) . "\n";
            
            
          if($permissions['delete'])
            echo '<button class="deletebutton" title="' . _txt('op.delete') . '" onclick="javascript:js_confirm_delete(\'' . _jtxt(Sanitize::html($a['Identifier']['identifier'])) . '\', \'' . $this->Html->url(array('controller' => 'identifiers', 'action' => 'delete', $a['Identifier']['id'])) . '\')";>' . _txt('op.delete') . '</button>';
        ?>
        <?php ; ?>
      </td>
    </tr>
    <?php $i++; ?>
    <?php endforeach; ?>
  </tbody>
  
  <tfoot>
    <tr class="ui-widget-header">
      <th colspan="6">
        <?php echo $this->Paginator->numbers(); ?>
      </td>
    </tr>
  </tfoot>
</table>