<!--
  /*
   * COmanage Gears Telephone Number Index View
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
<h1 class="ui-state-default"><?php echo _txt('ct.telephone_numbers.pl'); ?></h1>

<table id="telephone_numbers" class="ui-widget">
  <thead>
    <tr class="ui-widget-header">
      <th><?php echo $this->Paginator->sort(_txt('fd.phone'), 'number'); ?></th>
      <th><?php echo $this->Paginator->sort(_txt('fd.type'), 'type'); ?></th>
      <!-- XXX Following needs to be I18N'd, and also render a full name, if index view sticks around -->
      <th><?php echo $this->Paginator->sort('Org Person', 'OrgPerson.Name.family'); ?></th>
      <th><?php echo $this->Paginator->sort('CO Person', 'CoPerson.Name.family'); ?></th>
      <th>Actions</th>
    </tr>
  </thead>
  
  <tbody>
    <?php $i = 0; ?>
    <?php foreach ($telephone_numbers as $t): ?>
    <tr class="line<?php print ($i % 2)+1; ?>">
      <td>
        <?php
          echo $html->link($t['TelephoneNumber']['number'],
                           array('controller' => 'telephone_numbers',
                                 'action' => ($permissions['edit'] ? 'edit' : ($permissions['view'] ? 'view' : '')), $t['TelephoneNumber']['id']));
        ?>
      </td>
      <td>
        <?php echo _txt('en.contact', null, $t['TelephoneNumber']['type']); ?>
      </td>
      <td>
        <?php
          if(!empty($t['TelephoneNumber']['org_person_id']))
          {
            // Generally, someone who has view permission on a telephone number can also see a person
            if($permissions['view'])
              echo $html->link(generateCn($t['OrgPerson']['Name']),
                               array('controller' => 'org_people', 'action' => 'view', $t['OrgPerson']['id'])) . "\n";
          }
        ?>
      </td>
      <td>
        <?php
          if(!empty($t['TelephoneNumber']['co_person_id']))
          {
            // Generally, someone who has view permission on a telephone number can also see a person
            if($permissions['view'])
              echo $html->link(generateCn($t['CoPerson']['Name']),
                               array('controller' => 'co_people', 'action' => 'view', $t['CoPerson']['id'])) . "\n";
          }
        ?>
      </td>    
      <td>    
        <?php
          if($permissions['edit'])
            echo $html->link('Edit',
                             array('controller' => 'telephone_numbers', 'action' => 'edit', $t['TelephoneNumber']['id']),
                             array('class' => 'editbutton')) . "\n";
            
            
          if($permissions['delete'])
            echo '<button class="deletebutton" title="' . _txt('op.delete') . '" onclick="javascript:js_confirm_delete(\'' . _jtxt(Sanitize::html($t['TelephoneNumber']['number'])) . '\', \'' . $html->url(array('controller' => 'telephone_numbers', 'action' => 'delete', $t['TelephoneNumber']['id'])) . '\')";>' . _txt('op.delete') . '</button>';
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