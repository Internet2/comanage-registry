<!--
/**
 * COmanage Registry CO Person Index View
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
 * @copyright     Copyright (C) 2010-12 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.1
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */
-->
<h1 class="ui-state-default"><?php echo $cur_co['Co']['name']; ?> People</h1>

<?php
  if($permissions['enroll'] && !empty($co_enrollment_flows)) {
    print $this->Html->link(_txt('op.enroll'),
                            array('controller' => 'co_enrollment_flows', 'action' => 'select', 'co' => $cur_co['Co']['id']),
                            array('class' => 'addbutton')) . '
    <br />
    <br />
    ';    
  } elseif($permissions['add']) {
    print $this->Html->link(_txt('op.inv'),
                            array('controller' => 'org_identities', 'action' => 'find', 'co' => $cur_co['Co']['id']),
                            array('class' => 'addbutton')) . '
    <br />
    <br />
    ';
  }  
?>

<table id="co_people" class="ui-widget">
  <thead>
    <tr class="ui-widget-header">
      <th><?php echo $this->Paginator->sort('Name.family', _txt('fd.name')); ?></th>
      <th><?php echo $this->Paginator->sort('status', _txt('fd.status')); ?></th>
      <th><?php echo _txt('fd.roles'); ?></th>
      <th><?php echo _txt('fd.actions'); ?></th>
    </tr>
  </thead>
  
  <tbody>
    <?php $i = 0; ?>
    <?php foreach ($co_people as $p): ?>
    <tr class="line<?php print ($i % 2)+1; ?>">
      <td>
        <?php
          // Is this a person in a COU of the currently logged in person?
          $myPerson = false;
          
          // We look at COU here if set for the role
          if($permissions['edit']
             && (!isset($pr['cou_id'])
                 || $pr['cou_id'] == ''
                 || in_array($pr['Cou']['name'], $permissions['cous'])))
            $myPerson = true;
          
          print $this->Html->link(generateCn($p['Name']),
                                  array(
                                    'controller' => 'co_people',
                                    'action' => ($permissions['edit']
                                                 ? 'edit'
                                                 : ($permissions['view'] ? 'view' : '')),
                                    $p['CoPerson']['id'],
                                    'co' => $cur_co['Co']['id'])
                                  );
        ?></td>
      <td>
        <?php
          global $status_t;
          
          if(!empty($p['CoPerson']['status']) ) echo _txt('en.status', null, $p['CoPerson']['status']);
        ?>
      </td>
      <td>
        <?php
          foreach ($p['CoPersonRole'] as $pr) {
            if($myPerson) {
              echo $this->Html->link(_txt('op.edit'),
                                      array('controller' => 'co_person_roles',
                                            'action' => ($permissions['edit'] ? "edit" : "view"),
                                            $pr['id'],
                                            'co' => $cur_co['Co']['id']),
                                      array('class' => 'editbutton'));
            
              echo $this->Html->link($pr['title'],
                                      array('controller' => 'co_person_roles',
                                            'action' => ($permissions['edit'] ? "edit" : "view"),
                                            $pr['id'],
                                            'co' => $cur_co['Co']['id']));
            }
            else
              print $pr['title'];
            
            if(isset($pr['Cou']['name']))
              print " (" . $pr['Cou']['name'] . ")";
            
            print "<br />\n";
          }
        ?>
      </td>
      <td>
        <?php
          if($permissions['compare'])
            echo $this->Html->link(_txt('op.compare'),
                                    array('controller' => 'co_people', 'action' => 'compare', $p['CoPerson']['id'], 'co' => $cur_co['Co']['id']),
                                    array('class' => 'comparebutton')) . "\n";
          
          if($myPerson) {
            // Edit actions are unavailable if not
            
            if($permissions['edit'])
              echo $this->Html->link(_txt('op.edit'),
                                      array('controller' => 'co_people', 'action' => 'edit', $p['CoPerson']['id'], 'co' => $cur_co['Co']['id']),
                                      array('class' => 'editbutton')) . "\n";
            
            // Can't delete a CO Person if they have any roles
            if(empty($p['CoPersonRole']) && $permissions['delete'])
              echo '<button class="deletebutton" title="' . _txt('op.delete') . '" onclick="javascript:js_confirm_delete(\'' . _jtxt(Sanitize::html(generateCn($p['Name']))) . '\', \'' . $this->Html->url(array('controller' => 'co_people', 'action' => 'delete', $p['CoPerson']['id'], 'co' => $cur_co['Co']['id'])) . '\')";>' . _txt('op.delete') . '</button>' . "\n";
              
            if($permissions['invite'] && ($p['CoPerson']['status'] != 'A' && $p['CoPerson']['status'] != 'D'))
              echo '<button class="invitebutton" title="' . _txt('op.inv.resend') . '" onclick="javascript:js_confirm_reinvite(\'' . _jtxt(Sanitize::html(generateCn($p['Name']))) . '\', \'' . $this->Html->url(array('controller' => 'co_invites', 'action' => 'send', 'copersonid' => $p['CoPerson']['id'], 'co' => $cur_co['Co']['id'])) . '\')";>' . _txt('op.inv.resend') . '</button>' . "\n";
          }
        ?>
        <?php ; ?>
      </td>
    </tr>
    <?php $i++; ?>
    <?php endforeach; // $co_people ?>
  </tbody>
  
  <tfoot>
    <tr class="ui-widget-header">
      <th colspan="4">
        <?php echo $this->Paginator->numbers(); ?>
      </td>
    </tr>
  </tfoot>
</table>