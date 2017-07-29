<?php
/**
 * COmanage Registry Identifier Index View
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

  $params = array('title' => _txt('ct.identifiers.pl'));
  print $this->element("pageTitle", $params);
?>

<div class="table-container">
  <table id="identifiers">
    <thead>
      <tr>
        <th><?php print $this->Paginator->sort('identifier', _txt('fd.identifier.identifier')); ?></th>
        <th><?php print $this->Paginator->sort('type', _txt('fd.type')); ?></th>
        <th><?php print $this->Paginator->sort('login', _txt('fd.identifier.login')); ?></th>
        <th><?php print $this->Paginator->sort('status', _txt('fd.status')); ?></th>
        <!-- XXX Following needs to be I18N'd, and also render a full name, if index view sticks around -->
        <th><?php print $this->Paginator->sort('OrgIdentity.PrimaryName.family', 'Org Identity'); ?></th>
        <th><?php print $this->Paginator->sort('CoPerson.PrimaryName.family', 'CO Person'); ?></th>
        <th><?php print _txt('fd.actions'); ?></th>
      </tr>
    </thead>

    <tbody>
      <?php $i = 0; ?>
      <?php foreach ($identifiers as $a): ?>
      <tr class="line<?php print ($i % 2)+1; ?>">
        <td>
          <?php
            print $this->Html->link($a['Identifier']['identifier'],
                                   array(
                                    'controller' => 'identifiers',
                                    'action' => ($permissions['edit']
                                                 ? 'edit'
                                                 : ($permissions['view'] ? 'view' : '')),
                                    $a['Identifier']['id']
                                   ));
          ?>
        </td>
        <td>
          <?php print $a['Identifier']['type']; ?>
        </td>
        <td>
          <?php print ($a['Identifier']['login'] ? _txt('fd.true') : _txt('fd.false')); ?>
        </td>
        <td>
          <?php
            global $status_t;

            if(!empty($a['Identifier']['status'])) print _txt('en.status.susp', null, $a['Identifier']['status']);
          ?>
        </td>
        <td>
          <?php
            if(!empty($a['Identifier']['org_identity_id']))
            {
              // Generally, someone who has view permission on an attribute number can also see a person
              if($permissions['view'])
                print $this->Html->link(generateCn($a['OrgIdentity']['PrimaryName']),
                                       array('controller' => 'org_identities', 'action' => 'view', $a['OrgIdentity']['id'])) . "\n";
            }
          ?>
        </td>
        <td>
          <?php
            if(!empty($a['Identifier']['co_person_id']))
            {
              // Generally, someone who has view permission on an attribute can also see a person
              if($permissions['view'])
                print $this->Html->link(generateCn($a['CoPerson']['PrimaryName']),
                                       array('controller' => 'co_people', 'action' => 'view', $a['CoPerson']['id'])) . "\n";
            }
          ?>
        </td>
        <td>
          <?php
            if($permissions['edit']) {
              print $this->Html->link('Edit',
                  array('controller' => 'identifiers', 'action' => 'edit', $a['Identifier']['id']),
                  array('class' => 'editbutton')) . "\n";
            }

            if($permissions['delete']) {
              print '<button type="button" class="deletebutton" title="' . _txt('op.delete')
                . '" onclick="javascript:js_confirm_generic(\''
                . _txt('js.remove') . '\',\''    // dialog body text
                . $this->Html->url(              // dialog confirm URL
                  array(
                    'controller' => 'identifiers',
                    'action' => 'delete',
                    $a['Identifier']['id']
                  )
                ) . '\',\''
                . _txt('op.remove') . '\',\''    // dialog confirm button
                . _txt('op.cancel') . '\',\''    // dialog cancel button
                . _txt('op.remove') . '\',[\''   // dialog title
                . filter_var(_jtxt($a['Identifier']['identifier']),FILTER_SANITIZE_STRING)  // dialog body text replacement strings
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
