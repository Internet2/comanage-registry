<?php
  /*
   * COmanage Gears Org Identity Find View
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
<h1 class="ui-state-default"><?php echo _txt('op.find.inv', array(Sanitize::html($cur_co['Co']['name']))); ?></h1>

<table id="org_identities" class="ui-widget">
  <thead>
    <tr class="ui-widget-header">
      <th><?php echo $this->Paginator->sort(_txt('fd.name'), 'Name.family'); ?></th>
      <th><?php echo $this->Paginator->sort(_txt('fd.o'), 'o'); ?></th>
      <th><?php echo $this->Paginator->sort(_txt('fd.title'), 'title'); ?></th>
      <th><?php echo $this->Paginator->sort(_txt('fd.affiliation'), 'affiliation'); ?></th>
      <th><?php echo _txt('fd.mail'); ?></th>
      <th><?php echo _txt('op.inv'); ?></th>
    </tr>
  </thead>
  
  <tbody>
    <?php $i = 0; ?>
    <?php foreach ($org_identities as $p): ?>
    <tr class="line<?php print ($i % 2)+1; ?>">
      <td><?php echo $html->link(generateCn($p['Name']),
                                 array('controller' => 'org_identities', 'action' => 'view', $p['OrgIdentity']['id'])); ?></td>
      <td><?php echo Sanitize::html($p['OrgIdentity']['o']); ?></td>
      <td><?php echo Sanitize::html($p['OrgIdentity']['title']); ?></td>
      <td><?php   // Globals
             global $cm_lang, $cm_texts;
             echo $cm_texts[ $cm_lang ]['en.affil'][$p['OrgIdentity']['affiliation']]; ?></td>
      <td><?php foreach($p['EmailAddress'] as $ea) echo Sanitize::html($ea['mail']) . "<br />"; ?></td>
      <td><?php echo $html->link(_txt('op.inv'),
                                 array('controller' => 'co_people',
                                       'action' => 'invite',
                                       'orgidentityid' => $p['OrgIdentity']['id'],
                                       'co' => $cur_co['Co']['id']),
                                 array('class' => 'invitebutton')); ?></td>
    </tr>
    <?php $i++; ?>
    <?php endforeach; ?>
  </tbody>
  
  <tfoot>
    <tr class="ui-widget-header">
      <th colspan="8">
        <?php echo $this->Paginator->numbers(); ?>
      </td>
    </tr>
  </tfoot>
</table>
