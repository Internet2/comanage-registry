<?php
/**
 * COmanage Registry OrgIdentity Index View
 *
 * Copyright (C) 2011-12 University Corporation for Advanced Internet Development, Inc.
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
 * @since         COmanage Registry v0.2
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */

// Globals
global $cm_lang, $cm_texts;

  $params = array('title' => _txt('ct.org_identities.pl'));
  print $this->element("pageTitle", $params);

  if($permissions['add']) {
    // Add button to sidebar
    $sidebarButtons = $this->get('sidebarButtons');

    $sidebarButtons[]  = array(
      'icon'    => 'circle-plus',
      'title'   => _txt('op.add.new', array(_txt('ct.org_identities.1'))),
      'url'     => array(
        'controller'    => 'org_identities',
        'action' => 'add',
        'co' => ($pool_org_identities ? false : $this->params['named']['co'])
      )
    );

    $this->set('sidebarButtons', $sidebarButtons);
  }
?>

<div class="ui-state-highlight ui-corner-all" style="margin-top: 20px; padding: 0 .7em;"> 
  <p>
    <span class="ui-icon ui-icon-info" style="float: left; margin-right: .3em;"></span>
    <strong><?php print _txt('in.orgidentities'); ?></strong>
  </p>
</div>
<br /> 
<table id="org_identities" class="ui-widget" style=" width:100%;">
  <thead>
    <tr class="ui-widget-header">
      <th><?php echo $this->Paginator->sort('Name.family', _txt('fd.name')); ?></th>
      <th><?php echo $this->Paginator->sort('o', _txt('fd.o')); ?></th>
      <th><?php echo $this->Paginator->sort('ou', _txt('fd.ou')); ?></th>
      <th><?php echo $this->Paginator->sort('title', _txt('fd.title')); ?></th>
      <th><?php echo $this->Paginator->sort('affiliation', _txt('fd.affiliation')); ?></th>
      <th style="width:70px"><?php echo _txt('fd.actions'); ?></th>
    </tr>
  </thead>
  
  <tbody>
    <?php $i = 0; ?>
    <?php foreach ($org_identities as $p): ?>
    <tr class="line<?php print ($i % 2)+1; ?>">
      <td><?php print $this->Html->link(
            generateCn($p['Name']),
            array(
              'controller' => 'org_identities',
              'action' => ($permissions['edit'] ? 'edit' : ($permissions['view'] ? 'view' : '')),
              $p['OrgIdentity']['id'],
              'co' => ($pool_org_identities ? false : $this->params['named']['co'])
            )
          ); ?></td>
      <td><?php echo Sanitize::html($p['OrgIdentity']['o']); ?></td>
      <td><?php echo Sanitize::html($p['OrgIdentity']['ou']); ?></td>
      <td><?php echo Sanitize::html($p['OrgIdentity']['title']); ?></td>
      <td><?php if(isset($p['OrgIdentity']['affiliation'])) print _txt('en.affil', null, $p['OrgIdentity']['affiliation']); ?></td>
      
      <td>
        <?php
          if($permissions['edit'])
            print $this->Html->link(
              _txt('op.edit'),
              array(
                'controller' => 'org_identities',
                'action' => 'edit',
                $p['OrgIdentity']['id'],
                'co' => ($pool_org_identities ? false : $this->request->params['named']['co'])
              ),
              array('class' => 'editbutton')
            ) . "\n";
            
          if($permissions['delete'])
            echo '<button class="deletebutton" title="' . _txt('op.delete') . '" onclick="javascript:js_confirm_delete(\'' . Sanitize::html(generateCn($p['Name'])) . '\', \'' . $this->Html->url(array('controller' => 'org_identities', 'action' => 'delete', $p['OrgIdentity']['id'], 'co' => ($pool_org_identities ? false : $this->request->params['named']['co']))) . '\')";>' . _txt('op.delete') . '</button>';
        ?>
        <?php ; ?>
      </td>
    </tr>
    <?php $i++; ?>
    <?php endforeach; ?>
  </tbody>
  
  <tfoot>
    <tr class="ui-widget-header">
      <th colspan="7">
        <?php echo $this->Paginator->numbers(); ?>
      </th>
    </tr>
  </tfoot>
</table>
