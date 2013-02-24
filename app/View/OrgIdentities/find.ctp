<?php
/**
 * COmanage Registry Org Identity Find View
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
?>
<?php
  $params = array('title' => _txt('op.find.inv', array(Sanitize::html($cur_co['Co']['name']))));
  print $this->element("pageTitle", $params);
?>

<table id="org_identities" class="ui-widget">
  <thead>
    <tr class="ui-widget-header">
      <th><?php echo $this->Paginator->sort('Name.family', _txt('fd.name')); ?></th>
      <th><?php echo $this->Paginator->sort('o', _txt('fd.o')); ?></th>
      <th><?php echo $this->Paginator->sort('title', _txt('fd.title')); ?></th>
      <th><?php echo $this->Paginator->sort('affiliation', _txt('fd.affiliation')); ?></th>
      <th><?php echo _txt('fd.email_address.mail'); ?></th>
      <th><?php echo _txt('op.inv'); ?></th>
    </tr>
  </thead>
  
  <tbody>
    <?php $i = 0; ?>
    <?php foreach ($org_identities as $p): ?>
    <tr class="line<?php print ($i % 2)+1; ?>">
      <td><?php echo $this->Html->link(generateCn($p['Name']),
                                 array('controller' => 'org_identities', 'action' => 'view', $p['OrgIdentity']['id'])); ?></td>
      <td><?php echo Sanitize::html($p['OrgIdentity']['o']); ?></td>
      <td><?php echo Sanitize::html($p['OrgIdentity']['title']); ?></td>
      <td><?php   // Globals
             global $cm_lang, $cm_texts;
             if(isset($p['OrgIdentity']['affiliation'])) {
               echo $cm_texts[ $cm_lang ]['en.affil'][ $p['OrgIdentity']['affiliation'] ];
             }
          ?></td>
      <td><?php foreach($p['EmailAddress'] as $ea) echo Sanitize::html($ea['mail']) . "<br />"; ?></td>
      <td><?php echo $this->Html->link(_txt('op.inv'),
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
      </th>
    </tr>
  </tfoot>
</table>
