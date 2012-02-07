<!--
/**
 * COmanage Registry Email Address Index View
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
<h1 class="ui-state-default"><?php echo _txt('ct.email_addresses.pl'); ?></h1>

<table id="email_addresses" class="ui-widget">
  <thead>
    <tr class="ui-widget-header">
      <th><?php echo $this->Paginator->sort('email', _txt('fd.mail')); ?></th>
      <th><?php echo $this->Paginator->sort('type', _txt('fd.type')); ?></th>
      <!-- XXX Following needs to be I18N'd, and also render a full name, if index view sticks around -->
      <th><?php echo $this->Paginator->sort('OrgIdentity.Name.family', 'Org Identity'); ?></th>
      <th><?php echo $this->Paginator->sort('CoPerson.Name.family', 'CO Person'); ?></th>
      <th><?php echo _txt('fd.actions'); ?></th>
    </tr>
  </thead>
  
  <tbody>
    <?php $i = 0; ?>
    <?php foreach ($email_addresses as $e): ?>
    <tr class="line<?php print ($i % 2)+1; ?>">
      <td>
        <?php
          echo $this->Html->link($e['EmailAddress']['mail'],
                                 array('controller' => 'email_addresses',
                                       'action' => ($permissions['edit'] ? 'edit' : ($permissions['view'] ? 'view' : '')), $e['EmailAddress']['id']));
        ?>
      </td>
      <td>
        <?php echo _txt('en.contact', null, $e['EmailAddress']['type']); ?>
      </td>
      <td>
        <?php
          if(!empty($e['EmailAddress']['org_identity_id']))
          {
            // Generally, someone who has view permission on an attribute can also see a person
            if($permissions['view'])
              echo $this->Html->link(generateCn($e['OrgIdentity']['Name']),
                                     array('controller' => 'org_identities', 'action' => 'view', $e['OrgIdentity']['id'])) . "\n";
          }
        ?>
      </td>
      <td>
        <?php
          if(!empty($e['EmailAddress']['co_person_role_id']))
          {
            // Generally, someone who has view permission on an attribute can also see a person
            if($permissions['view'])
              echo $this->Html->link(generateCn($e['CoPerson']['Name']),
                                     array('controller' => 'co_people', 'action' => 'view', $e['CoPerson']['id'])) . "\n";
          }
        ?>
      </td>    
      <td>    
        <?php
          if($permissions['edit'])
            echo $this->Html->link('Edit',
                                   array('controller' => 'email_addresses', 'action' => 'edit', $e['EmailAddress']['id']),
                                   array('class' => 'editbutton')) . "\n";
            
            
          if($permissions['delete'])
            echo '<button class="deletebutton" title="' . _txt('op.delete') . '" onclick="javascript:js_confirm_delete(\'' . _jtxt(Sanitize::html($e['EmailAddress']['mail'])) . '\', \'' . $this->Html->url(array('controller' => 'email_addresses', 'action' => 'delete', $e['EmailAddress']['id'])) . '\')";>' . _txt('op.delete') . '</button>';
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