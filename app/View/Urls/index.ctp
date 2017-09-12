<?php
/**
 * COmanage Registry URL Index View
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
 * @since         COmanage Registry v3.1.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

  $params = array('title' => _txt('ct.urls.pl'));
  print $this->element("pageTitle", $params);
?>

<div class="table-container">
  <table id="urls">
    <thead>
      <tr>
        <th><?php print $this->Paginator->sort('url', _txt('fd.url.url')); ?></th>
        <th><?php print $this->Paginator->sort('type', _txt('fd.type')); ?></th>
        <!-- XXX Following needs to be I18N'd, and also render a full name, if index view sticks around -->
        <th><?php print $this->Paginator->sort('OrgIdentity.PrimaryName.family', 'Org Identity'); ?></th>
        <th><?php print $this->Paginator->sort('CoPerson.PrimaryName.family', 'CO Person'); ?></th>
        <th><?php print _txt('fd.actions'); ?></th>
      </tr>
    </thead>

    <tbody>
      <?php $i = 0; ?>
      <?php foreach ($urls as $u): ?>
      <tr class="line<?php print ($i % 2)+1; ?>">
        <td>
          <?php
            print $this->Html->link($u['Url']['url'],
                                   array('controller' => 'urls',
                                         'action' => ($permissions['edit'] ? 'edit' : ($permissions['view'] ? 'view' : '')), $u['Url']['id']));
          ?>
        </td>
        <td>
          <?php print _txt('en.url.type', null, $u['Url']['type']); ?>
        </td>
        <td>
          <?php
            if(!empty($u['Url']['org_identity_id']))
            {
              // Generally, someone who has view permission on an attribute can also see a person
              if($permissions['view'])
                print $this->Html->link(generateCn($u['OrgIdentity']['PrimaryName']),
                                       array('controller' => 'org_identities', 'action' => 'view', $u['OrgIdentity']['id'])) . "\n";
            }
          ?>
        </td>
        <td>
          <?php
            if(!empty($u['Url']['co_person_id']))
            {
              // Generally, someone who has view permission on an attribute can also see a person
              if($permissions['view'])
                print $this->Html->link(generateCn($u['CoPerson']['PrimaryName']),
                                       array('controller' => 'co_people', 'action' => 'view', $u['CoPerson']['id'])) . "\n";
            }
          ?>
        </td>
        <td>
          <?php
            if($permissions['edit']) {
              print $this->Html->link('Edit',
                  array('controller' => 'urls', 'action' => 'edit', $u['Url']['id']),
                  array('class' => 'editbutton')) . "\n";

            }
            if($permissions['delete']) {
              print '<button type="button" class="deletebutton" title="' . _txt('op.delete')
                . '" onclick="javascript:js_confirm_generic(\''
                . _txt('js.remove') . '\',\''    // dialog body text
                . $this->Html->url(              // dialog confirm URL
                  array(
                    'controller' => 'urls',
                    'action' => 'delete',
                    $u['Url']['id']
                  )
                ) . '\',\''
                . _txt('op.remove') . '\',\''    // dialog confirm button
                . _txt('op.cancel') . '\',\''    // dialog cancel button
                . _txt('op.remove') . '\',[\''   // dialog title
                . filter_var(_jtxt($u['Url']['url']),FILTER_SANITIZE_URL)  // dialog body text replacement strings
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
  </table>
</div>
  
<?php
  print $this->element("pagination");
