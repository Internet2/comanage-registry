<?php
  /*
   * COmanage Gears Address Index View
   *
   * Version: $Revision$
   * Date: $Date$
   *
   * Copyright (C) 2010-2011 University Corporation for Advanced Internet Development, Inc.
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
   */
?>
<h1 class="ui-state-default"><?php echo _txt('ct.addresses.pl'); ?></h1>

<table id="addresses" class="ui-widget">
  <thead>
    <tr class="ui-widget-header">
      <th><?php echo $this->Paginator->sort(_txt('fd.address.1'), 'line1'); ?></th>
      <th><?php echo $this->Paginator->sort(_txt('fd.type'), 'type'); ?></th>
      <!-- XXX Following needs to be I18N'd, and also render a full name, if index view sticks around -->
      <th><?php echo $this->Paginator->sort('Org Identity', 'OrgIdentity.Name.family'); ?></th>
      <th><?php echo $this->Paginator->sort('CO Person Role', 'CoPersonRole.Name.family'); ?></th>
      <th>Actions</th>
    </tr>
  </thead>
  
  <tbody>
    <?php $i = 0; ?>
    <?php foreach ($addresses as $a): ?>
    <tr class="line<?php print ($i % 2)+1; ?>">
      <td>
        <?php
          echo $html->link($a['Address']['line1'],
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
              echo $html->link(generateCn($a['OrgIdentity']['Name']),
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
              echo $html->link(generateCn($a['CoPersonRole']['Name']),
                               array('controller' => 'co_person_roles', 'action' => 'view', $a['CoPersonRole']['id'])) . "\n";
          }
        ?>
      </td>    
      <td>    
        <?php
          if($permissions['edit'])
            echo $html->link(_txt('op.edit'),
                             array('controller' => 'addresses', 'action' => 'edit', $a['Address']['id']),
                             array('class' => 'editbutton')) . "\n";
            
            
          if($permissions['delete'])
            echo '<button class="deletebutton" title="' . _txt('op.delete') . '" onclick="javascript:js_confirm_delete(\'' . _jtxt(Sanitize::html($a['Address']['line1'])) . '\', \'' . $html->url(array('controller' => 'addresses', 'action' => 'delete', $a['Address']['id'])) . '\')";>' . _txt('op.delete') . '</button>';
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