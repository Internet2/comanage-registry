<?php
  /*
   * COmanage Gears Organizational Person Index View
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
<h1 class="ui-state-default"><?php echo _txt('ct.org_people.pl'); ?></h1>

<?php
  if($permissions['add'])
    echo $this->Html->link(_txt('op.add.new', array(_txt('ct.org_people.1'))),
                           array('controller' => 'org_people', 'action' => 'add'),
                           array('class' => 'addbutton')) .
    $this->Html->link('Add Person via LDAP',  // XXX need to I18N this
                      array('controller' => 'org_people', 'action' => 'addvialdap'),
                      array('class' => 'addbutton')) . '
    <br />
    <br />
    ';
?>

<table id="org_people" class="ui-widget">
  <thead>
    <tr class="ui-widget-header">
      <th><?php echo $this->Paginator->sort(_txt('fd.name'), 'Name.family'); ?></th>
      <th><?php echo $this->Paginator->sort(_txt('fd.o'), 'o'); ?></th>
      <th><?php echo $this->Paginator->sort(_txt('fd.ou'), 'ou'); ?></th>
      <th><?php echo $this->Paginator->sort(_txt('fd.title'), 'title'); ?></th>
      <th><?php echo $this->Paginator->sort(_txt('fd.affiliation'), 'edu_person_affiliation'); ?></th>
      <th><?php echo $this->Paginator->sort(_txt('fd.orgid'), 'organization_id'); ?></th>
      <th><?php echo _txt('fd.actions'); ?></th>
    </tr>
  </thead>
  
  <tbody>
    <?php $i = 0; ?>
    <?php foreach ($org_people as $p): ?>
    <tr class="line<?php print ($i % 2)+1; ?>">
      <td><?php echo $html->link(generateCn($p['Name']),
                                 array('controller' => 'org_people', 'action' => ($permissions['edit'] ? 'edit' : ($permissions['view'] ? 'view' : '')), $p['OrgPerson']['id'])); ?></td>
      <td><?php echo Sanitize::html($p['OrgPerson']['o']); ?></td>
      <td><?php echo Sanitize::html($p['OrgPerson']['ou']); ?></td>
      <td><?php echo Sanitize::html($p['OrgPerson']['title']); ?></td>
      <td><?php echo Sanitize::html($p['OrgPerson']['edu_person_affiliation']); ?></td>
      <td><?php if($p['OrgPerson']['organization_id'] != "") echo $html->link($p['OrgPerson']['organization_id'],
                                                                              array('controller' => 'organizations', 'action' => 'view', $p['OrgPerson']['organization_id'])); ?></td>
      <td>
        <?php
          if($permissions['edit'])
            echo $html->link(_txt('op.edit'),
                             array('controller' => 'org_people', 'action' => 'edit', $p['OrgPerson']['id']),
                             array('class' => 'editbutton')) . "\n";
            
          if($permissions['delete'])
            echo '<button class="deletebutton" title="' . _txt('op.delete') . '" onclick="javascript:js_confirm_delete(\'' . Sanitize::html(generateCn($p['Name'])) . '\', \'' . $html->url(array('controller' => 'org_people', 'action' => 'delete', $p['OrgPerson']['id'])) . '\')";>' . _txt('op.delete') . '</button>';
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
      </td>
    </tr>
  </tfoot>
</table>