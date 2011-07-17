<!--
  /*
   * COmanage Gears Email Address Index View
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
-->
<h1 class="ui-state-default"><?php echo _txt('ct.email_addresses.pl'); ?></h1>

<table id="email_addresses" class="ui-widget">
  <thead>
    <tr class="ui-widget-header">
      <th><?php echo $this->Paginator->sort(_txt('fd.mail'), 'email'); ?></th>
      <th><?php echo $this->Paginator->sort(_txt('fd.type'), 'type'); ?></th>
      <!-- XXX Following needs to be I18N'd, and also render a full name, if index view sticks around -->
      <th><?php echo $this->Paginator->sort('Org Identity', 'OrgIdentity.Name.family'); ?></th>
      <th><?php echo $this->Paginator->sort('CO Person Role', 'CoPersonRole.Name.family'); ?></th>
      <th>Actions</th>
    </tr>
  </thead>
  
  <tbody>
    <?php $i = 0; ?>
    <?php foreach ($email_addresses as $e): ?>
    <tr class="line<?php print ($i % 2)+1; ?>">
      <td>
        <?php
          echo $html->link($e['EmailAddress']['mail'],
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
            // Generally, someone who has view permission on a telephone number can also see a person
            if($permissions['view'])
              echo $html->link(generateCn($e['OrgIdentity']['Name']),
                               array('controller' => 'org_identities', 'action' => 'view', $e['OrgIdentity']['id'])) . "\n";
          }
        ?>
      </td>
      <td>
        <?php
          if(!empty($e['EmailAddress']['co_person_role_id']))
          {
            // Generally, someone who has view permission on a telephone number can also see a person
            if($permissions['view'])
              echo $html->link(generateCn($e['CoPersonRole']['Name']),
                               array('controller' => 'co_person_roles', 'action' => 'view', $e['CoPersonRole']['id'])) . "\n";
          }
        ?>
      </td>    
      <td>    
        <?php
          if($permissions['edit'])
            echo $html->link('Edit',
                             array('controller' => 'email_addresses', 'action' => 'edit', $e['EmailAddress']['id']),
                             array('class' => 'editbutton')) . "\n";
            
            
          if($permissions['delete'])
            echo '<button class="deletebutton" title="' . _txt('op.delete') . '" onclick="javascript:js_confirm_delete(\'' . _jtxt(Sanitize::html($e['EmailAddress']['mail'])) . '\', \'' . $html->url(array('controller' => 'email_addresses', 'action' => 'delete', $e['EmailAddress']['id'])) . '\')";>' . _txt('op.delete') . '</button>';
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