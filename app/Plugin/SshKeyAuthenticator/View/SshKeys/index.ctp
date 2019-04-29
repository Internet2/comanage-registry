<?php
/**
 * COmanage Registry SSH Keys Index View
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
 * @since         COmanage Registry v3.3.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */
 
  // Add breadcrumbs
  print $this->element("coCrumb", array('authenticator' => 'SshKey'));
  
  // Add page title
  $params = array();
  $params['title'] = $title_for_layout;

  // Add top links
  $params['topLinks'] = array();

  if($permissions['add']) {
    $params['topLinks'][] = $this->Html->link(
      _txt('op.add-a', array(_txt('ct.ssh_keys.1'))),
      array(
        'plugin' => 'ssh_key_authenticator', // XXX can inflect from $vv_authenticator['Authenticator']['plugin']
        'controller' => 'ssh_keys',
        'action' => 'add',
        'authenticatorid' => $vv_authenticator['Authenticator']['id'],
        'copersonid' => $vv_co_person['CoPerson']['id']
      ),
      array('class' => 'addbutton')
    );
  }
  
  print $this->element("pageTitleAndButtons", $params);
?>

<table id="ssh_keys">
  <thead>
    <tr>
      <th><?php print $this->Paginator->sort('comment', _txt('fd.comment')); ?></th>
      <th><?php print _txt('fd.actions'); ?></th>
    </tr>
  </thead>
  
  <tbody>
    <?php $i = 0; ?>
<!-- XXX Arlen re should we still be emitting class=line# here? -->
    <?php foreach ($ssh_keys as $s): ?>
    <tr class="line<?php print ($i % 2)+1; ?>">
      <td>
        <?php
          print $this->Html->link(
            $s['SshKey']['comment'],
            array(
              'plugin' => 'ssh_key_authenticator', // XXX can inflect from $vv_authenticator['Authenticator']['plugin']
              'controller' => 'ssh_keys',
              'action' => 'view',
              $s['SshKey']['id']
            )
          );
        ?>
      </td>
      <td>
        <?php
          if($permissions['view']) {
            print $this->Html->link(
              _txt('op.view'),
              array(
                'plugin' => 'ssh_key_authenticator', // XXX can inflect from $vv_authenticator['Authenticator']['plugin']
                'controller' => 'ssh_keys',
                'action' => 'view',
                $s['SshKey']['id']
              ),
              array('class' => 'viewbutton')
            ) . "\n";
          }
          
          if($permissions['delete']) {
            print '<button type="button" class="deletebutton" title="' . _txt('op.delete')
              . '" onclick="javascript:js_confirm_generic(\''
              . _txt('js.remove') . '\',\''    // dialog body text
              . $this->Html->url(              // dialog confirm URL
                array(
                'plugin' => 'ssh_key_authenticator', // XXX can inflect from $vv_authenticator['Authenticator']['plugin']
                'controller' => 'ssh_keys',
                'action' => 'delete',
                $s['SshKey']['id']
                )
              ) . '\',\''
              . _txt('op.remove') . '\',\''    // dialog confirm button
              . _txt('op.cancel') . '\',\''    // dialog cancel button
              . _txt('op.remove') . '\',[\''   // dialog title
              . filter_var(_jtxt($s['SshKey']['comment']),FILTER_SANITIZE_STRING)  // dialog body text replacement strings
              . '\']);">'
              . _txt('op.delete')
              . '</button>';
          }
        ?>
      </td>
    </tr>
    <?php $i++; ?>
    <?php endforeach; ?>
  </tbody>
</table>
  
<?php print $this->element("pagination");