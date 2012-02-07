<?php
/**
 * COmanage Registry Address Index View
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

?>
<h1 class="ui-state-default"><?php echo _txt('ct.addresses.pl'); ?></h1>

<table id="addresses" class="ui-widget">
  <thead>
    <tr class="ui-widget-header">
      <th><?php echo $this->Paginator->sort('line1', _txt('fd.address.1')); ?></th>
      <th><?php echo $this->Paginator->sort('type', _txt('fd.type')); ?></th>
      <!-- XXX Following needs to be I18N'd, and also render a full name, if index view sticks around -->
      <th><?php echo $this->Paginator->sort('OrgIdentity.Name.family', 'Org Identity'); ?></th>
      <th><?php echo $this->Paginator->sort('CoPersonRole.Name.family', 'CO Person Role'); ?></th>
      <th><?php echo _txt('fd.actions'); ?></th>
    </tr>
  </thead>
  
  <tbody>
    <?php $i = 0; ?>
    <?php foreach ($addresses as $a): ?>
    <tr class="line<?php print ($i % 2)+1; ?>">
      <td>
        <?php
          echo $this->Html->link($a['Address']['line1'],
                                 array('controller' => 'addresses',
                                       'action' => ($permissions['edit'] ? 'edit' : ($permissions['view'] ? 'view' : '')), $a['Address']['id']));
        ?>
      </td>
      <td>
        <?php echo _txt('en.contact', null, $a['Address']['type']); ?>
      </td>
      <td>
        <?php
          if(!empty($a['Address']['org_identity_id']))
          {
            // Generally, someone who has view permission on a telephone number can also see a person
            if($permissions['view'])
              echo $this->Html->link(generateCn($a['OrgIdentity']['Name']),
                                     array('controller' => 'org_identities', 'action' => 'view', $a['OrgIdentity']['id'])) . "\n";
          }
        ?>
      </td>
      <td>
        <?php
          if(!empty($a['Address']['co_person_role_id']))
          {
            // Generally, someone who has view permission on a telephone number can also see a person
            if($permissions['view'])
              echo $this->Html->link(generateCn($a['CoPersonRole']['Name']),
                                     array('controller' => 'co_person_roles', 'action' => 'view', $a['CoPersonRole']['id'])) . "\n";
          }
        ?>
      </td>    
      <td>    
        <?php
          if($permissions['edit'])
            echo $this->Html->link(_txt('op.edit'),
                                   array('controller' => 'addresses', 'action' => 'edit', $a['Address']['id']),
                                   array('class' => 'editbutton')) . "\n";
            
            
          if($permissions['delete'])
            echo '<button class="deletebutton" title="' . _txt('op.delete') . '" onclick="javascript:js_confirm_delete(\'' . _jtxt(Sanitize::html($a['Address']['line1'])) . '\', \'' . $this->Html->url(array('controller' => 'addresses', 'action' => 'delete', $a['Address']['id'])) . '\')";>' . _txt('op.delete') . '</button>';
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
      </td>
    </tr>
  </tfoot>
</table>