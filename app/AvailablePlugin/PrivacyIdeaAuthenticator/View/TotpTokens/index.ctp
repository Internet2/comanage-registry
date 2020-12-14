<?php
/**
 * COmanage Registry TOTP Tokens Index View
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
 * @package       registry-plugin
 * @since         COmanage Registry v4.0.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */
 
  // Add breadcrumbs
  print $this->element("coCrumb", array('authenticator' => 'PrivacyIdea'));
  
  // Add page title
  $params = array();
  $params['title'] = $title_for_layout;

  // Add top links
  $params['topLinks'] = array();

  if($permissions['add']) {
    $params['topLinks'][] = $this->Html->link(
      _txt('op.add-a', array($title_for_layout)),
      array(
        'plugin' => 'privacy_idea_authenticator', // XXX can inflect from $vv_authenticator['Authenticator']['plugin']
        'controller' => 'totp_tokens', // XXX this will nede to be updated when we support other token types
        'action' => 'add',
        'authenticatorid' => $vv_authenticator['Authenticator']['id'],
        'copersonid' => $vv_co_person['CoPerson']['id']
      ),
      array('class' => 'addbutton')
    );
  }
  
  print $this->element("pageTitleAndButtons", $params);
?>
<table id="privacy_ideas">
  <thead>
    <tr>
      <th><?php print $this->Paginator->sort('token_type', _txt('pl.privacyideaauthenticator.fd.token_type')); ?></th>
      <th><?php print $this->Paginator->sort('serial', _txt('pl.privacyideaauthenticator.fd.serial')); ?></th>
      <th><?php print _txt('fd.actions'); ?></th>
    </tr>
  </thead>
  
  <tbody>
    <?php $i = 0; ?>
    <?php foreach($totp_tokens as $t): ?>
    <tr class="line<?php print ($i % 2)+1; ?>">
      <td><?php print _txt('en.privacyideaauthenticator.token_type', null, $t['PrivacyIdeaAuthenticator']['token_type']); ?></td>
      <td>
        <?php
          print $this->Html->link(
            $t['TotpToken']['serial'],
            array(
              'plugin' => 'privacy_idea_authenticator', // XXX can inflect from $vv_authenticator['Authenticator']['plugin']
              'controller' => 'totp_tokens',
              'action' => 'view',
              $t['TotpToken']['id']
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
                'plugin' => 'privacy_idea_authenticator',
                'controller' => 'totp_tokens',
                'action' => 'view',
                $t['TotpToken']['id']
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
                'plugin' => 'privacy_idea_authenticator', // XXX can inflect from $vv_authenticator['Authenticator']['plugin']
                'controller' => 'totp_tokens',
                'action' => 'delete',
                $t['TotpToken']['id']
                )
              ) . '\',\''
              . _txt('op.remove') . '\',\''    // dialog confirm button
              . _txt('op.cancel') . '\',\''    // dialog cancel button
              . _txt('op.remove') . '\',[\''   // dialog title
              . filter_var(_jtxt($t['TotpToken']['serial']),FILTER_SANITIZE_STRING)  // dialog body text replacement strings
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