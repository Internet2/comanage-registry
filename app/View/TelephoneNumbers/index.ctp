<?php
/**
 * COmanage Registry Telephone Number Index View
 *
 * Portions licensed to the University Corporation for Advanced Internet
 * Development, Inc. ("UCAID") under one or more contributor license agreements.
 * See the NOTICE file distributed with this work for additional information
 * regarding copyright ownership.
 *
 * UCAID licenses this file to you under the Apache License, Version 2.0
 * (the "License"); you may not use this file except in compliance with the
 * License. You may obtain a copy of the License at:
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 * 
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.1
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

  $params = array('title' => _txt('ct.telephone_numbers.pl'));
  print $this->element("pageTitle", $params);
?>

<table id="telephone_numbers" class="ui-widget">
  <thead>
    <tr class="ui-widget-header">
      <th><?php print $this->Paginator->sort('number', _txt('fd.telephone_number.number')); ?></th>
      <th><?php print $this->Paginator->sort('type', _txt('fd.type')); ?></th>
      <!-- XXX Following needs to be I18N'd, and also render a full name, if index view sticks around -->
      <th><?php print $this->Paginator->sort('OrgIdentity.PrimaryName.family', 'Org Identity'); ?></th>
      <th><?php print $this->Paginator->sort('CoPersonRole.PrimaryName.family', 'CO Person Role'); ?></th>
      <th><?php print _txt('fd.actions'); ?></th>
    </tr>
  </thead>
  
  <tbody>
    <?php $i = 0; ?>
    <?php foreach ($telephone_numbers as $t): ?>
    <tr class="line<?php print ($i % 2)+1; ?>">
      <td>
        <?php
          print $this->Html->link(formatTelephone($t),
                                 array('controller' => 'telephone_numbers',
                                       'action' => ($permissions['edit'] ? 'edit' : ($permissions['view'] ? 'view' : '')), $t['TelephoneNumber']['id']));
        ?>
      </td>
      <td>
        <?php print _txt('en.contact', null, $t['TelephoneNumber']['type']); ?>
      </td>
      <td>
        <?php
          if(!empty($t['TelephoneNumber']['org_identity_id']))
          {
            // Generally, someone who has view permission on a telephone number can also see a person
            if($permissions['view'])
              print $this->Html->link(generateCn($t['OrgIdentity']['PrimaryName']),
                                     array('controller' => 'org_identities', 'action' => 'view', $t['OrgIdentity']['id'])) . "\n";
          }
        ?>
      </td>
      <td>
        <?php
          if(!empty($t['TelephoneNumber']['co_person_role_id']))
          {
            // Generally, someone who has view permission on a telephone number can also see a person
            if($permissions['view'])
              print $this->Html->link(generateCn($t['CoPersonRole']['PrimaryName']),
                                     array('controller' => 'co_person_roles', 'action' => 'view', $t['CoPersonRole']['id'])) . "\n";
          }
        ?>
      </td>    
      <td>    
        <?php
          if($permissions['edit']) {
            print $this->Html->link(_txt('op.edit'),
                array('controller' => 'telephone_numbers', 'action' => 'edit', $t['TelephoneNumber']['id']),
                array('class' => 'editbutton')) . "\n";
          }

          if($permissions['delete']) {
            print '<button type="button" class="deletebutton" title="' . _txt('op.delete')
              . '" onclick="javascript:js_confirm_generic(\''
              . _txt('js.remove') . '\',\''    // dialog body text
              . $this->Html->url(              // dialog confirm URL
                array(
                  'controller' => 'telephone_numbers',
                  'action' => 'delete',
                  $t['TelephoneNumber']['id']
                )
              ) . '\',\''
              . _txt('op.remove') . '\',\''    // dialog confirm button
              . _txt('op.cancel') . '\',\''    // dialog cancel button
              . _txt('op.remove') . '\',[\''   // dialog title
              . filter_var(_jtxt($t['TelephoneNumber']['number']),FILTER_SANITIZE_STRING)  // dialog body text replacement strings
              . '\']);">'
              . _txt('op.delete')
              . '</button>';
          }
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
        <?php print $this->element("pagination"); ?>
      </th>
    </tr>
  </tfoot>
</table>
