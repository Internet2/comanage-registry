<!--
/**
 * COmanage Registry CO Person Index View
 *
 * Copyright (C) 2011-14 University Corporation for Advanced Internet Development, Inc.
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
 * @copyright     Copyright (C) 2011-14 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.1
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */

-->
<?php
  // Globals
  global $cm_lang, $cm_texts;

  // Add page title
  $params = array();
  $params['title'] = $cur_co['Co']['name'] . "People";

  // Add top links
  $params['topLinks'] = array();

  if($permissions['add']) {
    $params['topLinks'][] = $this->Html->link(
      _txt('op.inv'),
      array(
        'controller' => 'org_identities',
        'action' => 'find',
        'co' => $this->request->params['named']['co']
      ),
      array('class' => 'addbutton')
    );
  }

  print $this->element("pageTitleAndButtons", $params);

?>

<table id="co_person_roles" class="ui-widget">
  <thead>
    <tr class="ui-widget-header">
      <th><?php echo $this->Paginator->sort('PrimaryName.family', _txt('fd.name')); ?></th>
      <th><?php echo $this->Paginator->sort('o', _txt('fd.o')); ?></th>
      <th><?php echo $this->Paginator->sort('Cou.ou', _txt('fd.cou')); ?></th>
      <th><?php echo $this->Paginator->sort('title', _txt('fd.title')); ?></th>
      <th><?php echo $this->Paginator->sort('affiliation', _txt('fd.affiliation')); ?></th>
      <th><?php echo $this->Paginator->sort('valid_from', _txt('fd.valid_from')); ?></th>
      <th><?php echo $this->Paginator->sort('valid_through', _txt('fd.valid_through')); ?></th>
      <th><?php echo $this->Paginator->sort('status', _txt('fd.status')); ?></th>
      <th><?php echo _txt('fd.actions'); ?></th>
    </tr>
  </thead>
  
  <tbody>
    <?php $i = 0; ?>
    <?php foreach ($co_person_roles as $p): ?>
    <tr class="line<?php print ($i % 2)+1; ?>">
      <td><?php echo $this->Html->link(generateCn($p['PrimaryName']),
                                      array('controller' => 'co_person_roles', 'action' => ($permissions['edit'] ? 'edit' : ($permissions['view'] ? 'view' : '')), $p['CoPersonRole']['id'], 'co' => $cur_co['Co']['id'])); ?></td>
      <td><?php echo Sanitize::html($p['CoPersonRole']['o']); ?></td>
      <td><?php if(isset($p['CoPersonRole']['Cou']['name'])) echo Sanitize::html($p['CoPersonRole']['Cou']['name']); ?></td>
      <td><?php echo Sanitize::html($p['CoPersonRole']['title']); ?></td>
      <td><?php print $vv_copr_affiliation_types[ $p['CoPersonRole']['affiliation']]; ?></td>
      <td><?php if($p['CoPersonRole']['valid_from'] > 0) echo $this->Time->format('Y M d', $p['CoPersonRole']['valid_from']); ?></td>
      <td><?php if($p['CoPersonRole']['valid_through'] > 0) echo $this->Time->format('Y M d', $p['CoPersonRole']['valid_through']); ?></td>
      <td>
        <?php
          global $status_t;
          
          if(!empty($p['CoPersonRole']['status']) ) echo _txt('en.status', null, $p['CoPersonRole']['status']);
        ?>
      </td>
      <td>
        <?php
          if($permissions['compare'])
            echo $this->Html->link(_txt('op.compare'),
                                    array('controller' => 'co_person_roles', 'action' => 'compare', $p['CoPersonRole']['id'], 'co' => $cur_co['Co']['id']),
                                    array('class' => 'comparebutton')) . "\n";
        
          if($permissions['edit'])
            echo $this->Html->link(_txt('op.edit'),
                                    array('controller' => 'co_person_roles', 'action' => 'edit', $p['CoPersonRole']['id'], 'co' => $cur_co['Co']['id']),
                                    array('class' => 'editbutton')) . "\n";
            
          if($permissions['delete'])
            echo '<button class="deletebutton" title="' . _txt('op.delete') . '" onclick="javascript:js_confirm_delete(\'' . _jtxt(Sanitize::html(generateCn($p['PrimaryName']))) . '\', \'' . $this->Html->url(array('controller' => 'co_person_roles', 'action' => 'delete', $p['CoPersonRole']['id'], 'co' => $cur_co['Co']['id'])) . '\')";>' . _txt('op.delete') . '</button>' . "\n";
            
          if($permissions['invite'] && ($p['CoPersonRole']['status'] != 'A' && $p['CoPersonRole']['status'] != 'D'))
            echo '<button class="invitebutton" title="' . _txt('op.inv.resend') . '" onclick="javascript:js_confirm_reinvite(\'' . _jtxt(Sanitize::html(generateCn($p['PrimaryName']))) . '\', \'' . $this->Html->url(array('controller' => 'co_invites', 'action' => 'send', 'copersonroleid' => $p['CoPersonRole']['id'], 'co' => $cur_co['Co']['id'])) . '\')";>' . _txt('op.inv.resend') . '</button>' . "\n";
        ?>
        <?php ; ?>
      </td>
    </tr>
    <?php $i++; ?>
    <?php endforeach; ?>
  </tbody>
  
  <tfoot>
    <tr class="ui-widget-header">
      <th colspan="9">
        <?php echo $this->Paginator->numbers(); ?>
      </th>
    </tr>
  </tfoot>
</table>
