<!--
  /*
   * COmanage Gears CO Person Role Index View
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
<h1 class="ui-state-default"><?php echo $cur_co['Co']['name']; ?> People</h1>

<?php

  // Globals
  global $cm_lang, $cm_texts;

  if($permissions['add'])
    echo $this->Html->link(_txt('op.inv'),
                           array('controller' => 'org_identities', 'action' => 'find', 'co' => $this->params['named']['co']),
                           array('class' => 'addbutton')) . '
    <br />
    <br />
    ';
?>

<table id="co_person_roles" class="ui-widget">
  <thead>
    <tr class="ui-widget-header">
      <th><?php echo $this->Paginator->sort(_txt('fd.name'), 'Name.family'); ?></th>
      <th><?php echo $this->Paginator->sort(_txt('fd.o'), 'o'); ?></th>
      <th><?php echo $this->Paginator->sort(_txt('fd.cou'), 'Cou.ou'); ?></th>
      <th><?php echo $this->Paginator->sort(_txt('fd.title'), 'title'); ?></th>
      <th><?php echo $this->Paginator->sort(_txt('fd.affiliation'), 'affiliation'); ?></th>
      <th><?php echo $this->Paginator->sort(_txt('fd.valid.f'), 'valid_from'); ?></th>
      <th><?php echo $this->Paginator->sort(_txt('fd.valid.u'), 'valid_through'); ?></th>
      <th><?php echo $this->Paginator->sort(_txt('fd.status'), 'status'); ?></th>
      <th><?php echo _txt('fd.actions'); ?></th>
    </tr>
  </thead>
  
  <tbody>
    <?php $i = 0; ?>
    <?php foreach ($co_person_roles as $p): ?>
    <tr class="line<?php print ($i % 2)+1; ?>">
      <td><?php echo $html->link(generateCn($p['Name']),
                                 array('controller' => 'co_person_roles', 'action' => ($permissions['edit'] ? 'edit' : ($permissions['view'] ? 'view' : '')), $p['CoPersonRole']['id'], 'co' => $cur_co['Co']['id'])); ?></td>
      <td><?php echo Sanitize::html($p['CoPersonRole']['o']); ?></td>
      <td><?php if(isset($p['CoPersonRole']['Cou']['name'])) echo Sanitize::html($p['CoPersonRole']['Cou']['name']); ?></td>
      <td><?php echo Sanitize::html($p['CoPersonRole']['title']); ?></td>
      <td><?php echo $cm_texts[ $cm_lang ]['en.affil'][ $p['CoPersonRole']['affiliation']]; ?></td>
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
            echo $html->link(_txt('op.compare'),
                             array('controller' => 'co_person_roles', 'action' => 'compare', $p['CoPersonRole']['id'], 'co' => $cur_co['Co']['id']),
                             array('class' => 'comparebutton')) . "\n";
        
          if($permissions['edit'])
            echo $html->link(_txt('op.edit'),
                             array('controller' => 'co_person_roles', 'action' => 'edit', $p['CoPersonRole']['id'], 'co' => $cur_co['Co']['id']),
                             array('class' => 'editbutton')) . "\n";
            
          if($permissions['delete'])
            echo '<button class="deletebutton" title="' . _txt('op.delete') . '" onclick="javascript:js_confirm_delete(\'' . _jtxt(Sanitize::html(generateCn($p['Name']))) . '\', \'' . $html->url(array('controller' => 'co_person_roles', 'action' => 'delete', $p['CoPersonRole']['id'], 'co' => $cur_co['Co']['id'])) . '\')";>' . _txt('op.delete') . '</button>' . "\n";
            
          if($permissions['invite'] && ($p['CoPersonRole']['status'] != 'A' && $p['CoPersonRole']['status'] != 'D'))
            echo '<button class="invitebutton" title="' . _txt('op.inv.resend') . '" onclick="javascript:js_confirm_reinvite(\'' . _jtxt(Sanitize::html(generateCn($p['Name']))) . '\', \'' . $html->url(array('controller' => 'co_invites', 'action' => 'send', 'copersonroleid' => $p['CoPersonRole']['id'], 'co' => $cur_co['Co']['id'])) . '\')";>' . _txt('op.inv.resend') . '</button>' . "\n";
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
      </td>
    </tr>
  </tfoot>
</table>
