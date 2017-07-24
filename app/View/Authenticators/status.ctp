<?php
/**
 * COmanage Registry Authenticator Status View
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

  // Add breadcrumbs
  print $this->element("coCrumb");

  $args = array();
  $args['plugin'] = null;
  $args['controller'] = 'co_people';
  $args['action'] = 'index';
  $args['co'] = $cur_co['Co']['id'];
  $this->Html->addCrumb(_txt('me.population'), $args);
  
  $args = array();
  $args['plugin'] = null;
  $args['controller'] = 'co_people';
  $args['action'] = 'canvas';
  $args[] = $vv_co_person['CoPerson']['id'];
  $this->Html->addCrumb(generateCn($vv_co_person['PrimaryName']), $args);

  $this->Html->addCrumb(_txt('ct.authenticators.pl'));
  
  // Add page title
  $params = array();
  $params['title'] = $title_for_layout;

  // Add top links
  $params['topLinks'] = array();
  
  print $this->element("pageTitleAndButtons", $params);
?>

<table id="authenticators">
  <thead>
    <tr>
      <th><?php print $this->Paginator->sort('description', _txt('fd.desc')); ?></th>
      <th><?php print _txt('fd.status'); ?></th>
      <th><?php print _txt('fd.actions'); ?></th>
    </tr>
  </thead>
  
  <tbody>
    <?php $i = 0; ?>
    <?php foreach ($vv_authenticator_status as $c): ?>
    <tr class="line<?php print ($i % 2)+1; ?>">
      <td>
        <?php
          print filter_var($c['description'],FILTER_SANITIZE_SPECIAL_CHARS);
        ?>
      </td>
      <td>
        <?php
          print _txt('en.status.authr', null, $c['status']['status'])
                . " (" . $c['status']['comment'] . ")";
        ?>
      </td>
      <td>
        <?php
          // $plugin = "PasswordAuthenticator"
          $plugin = filter_var($c['plugin'],FILTER_SANITIZE_SPECIAL_CHARS);
          // $pl = "password_authenticator"
          $pl = Inflector::underscore($plugin);
          // Authenticator Store Model, "passwords"
          $plamodel = Inflector::pluralize(str_replace("_authenticator", "", $pl));
          
          if(!empty($vv_co_person['CoPerson']['id'])) {
            if($c['multiple']) {
              // Multiple Authenticators per instantiation, so hand off to the index page to manage
              if($permissions['manage']
                 && $c['status']['status'] != AuthenticatorStatusEnum::Locked) {
                print $this->Html->link(
                  _txt('op.manage'),
                  array(
                    'plugin' => $pl,
                    'controller' => $plamodel,
                    'action' => 'index',
                    'authenticatorid' => $c['id'],
                    'copersonid' => $vv_co_person['CoPerson']['id']
                  ),
                  array('class' => 'editbutton')
                );
              }
            } else {
              if($permissions['info']
                 && $c['status']['status'] != AuthenticatorStatusEnum::Locked) {
              
                print $this->Html->link(
                  _txt('op.view'),
                  array(
                    'plugin' => $pl,
                    'controller' => $plamodel,
                    'action' => 'info',
                    'authenticatorid' => $c['id'],
                    'copersonid' => $vv_co_person['CoPerson']['id']
                  ),
                  array('class' => 'viewbutton')
                );
              }
              
              if($permissions['manage']
                 && $c['status']['status'] != AuthenticatorStatusEnum::Locked) {
              
                print $this->Html->link(
                  _txt('op.manage'),
                  array(
                    'plugin' => $pl,
                    'controller' => $plamodel,
                    'action' => 'manage',
                    'authenticatorid' => $c['id'],
                    'copersonid' => $vv_co_person['CoPerson']['id']
                  ),
                  array('class' => 'editbutton')
                );
              }
                            
              if($permissions['reset']
                 && $c['status']['status'] != AuthenticatorStatusEnum::Locked
                 && $c['status']['status'] != AuthenticatorStatusEnum::NotSet) {
                print '<button type="button" class="deletebutton" title="' . _txt('op.reset')
                  . '" onclick="javascript:js_confirm_generic(\''
                  . _txt('js.auth.reset') . '\',\''    // dialog body text
                  . $this->Html->url(              // dialog confirm URL
                    array(
                      'plugin' => $pl,
                      'controller' => $plamodel,
                      'action' => 'reset',
                      'authenticatorid' => $c['id'],
                      'copersonid' => $vv_co_person['CoPerson']['id']
                    )
                  ) . '\',\''
                  . _txt('op.reset') . '\',\''    // dialog confirm button
                  . _txt('op.cancel') . '\',\''  // dialog cancel button
                  . _txt('op.reset') . '\',[\''   // dialog title
                  . filter_var(_jtxt($c['description']),FILTER_SANITIZE_STRING)  // dialog body text replacement strings
                  . '\',\''
                  . filter_var(_jtxt(generateCn($vv_co_person['PrimaryName'])),FILTER_SANITIZE_STRING)  // dialog body text replacement strings
                  . '\']);">'
                  . _txt('op.reset')
                  . '</button>';
              }
            }
            
            if($permissions['lock']
               && $c['status']['status'] != AuthenticatorStatusEnum::Locked) {
              print '<button type="button" class="lockbutton" title="' . _txt('op.lock')
                . '" onclick="javascript:js_confirm_generic(\''
                . _txt('js.auth.lock') . '\',\''    // dialog body text
                . $this->Html->url(              // dialog confirm URL
                  array(
                    'controller' => 'authenticators',
                    'action' => 'lock',
                    $c['id'],
                    'copersonid' => $vv_co_person['CoPerson']['id']
                  )
                ) . '\',\''
                . _txt('op.lock') . '\',\''    // dialog confirm button
                . _txt('op.cancel') . '\',\''  // dialog cancel button
                . _txt('op.lock') . '\',[\''   // dialog title
                . filter_var(_jtxt($c['description']),FILTER_SANITIZE_STRING)  // dialog body text replacement strings
                . '\',\''
                . filter_var(_jtxt(generateCn($vv_co_person['PrimaryName'])),FILTER_SANITIZE_STRING)  // dialog body text replacement strings
                . '\']);">'
                . _txt('op.lock')
                . '</button>';
            } elseif($permissions['unlock']
                     && $c['status']['status'] == AuthenticatorStatusEnum::Locked) {
              print '<button type="button" class="unlockbutton" title="' . _txt('op.unlock')
                . '" onclick="javascript:js_confirm_generic(\''
                . _txt('js.auth.unlock') . '\',\''    // dialog body text
                . $this->Html->url(              // dialog confirm URL
                  array(
                    'controller' => 'authenticators',
                    'action' => 'unlock',
                    $c['id'],
                    'copersonid' => $vv_co_person['CoPerson']['id']
                  )
                ) . '\',\''
                . _txt('op.unlock') . '\',\''    // dialog confirm button
                . _txt('op.cancel') . '\',\''  // dialog cancel button
                . _txt('op.unlock') . '\',[\''   // dialog title
                . filter_var(_jtxt($c['description']),FILTER_SANITIZE_STRING)  // dialog body text replacement strings
                . '\',\''
                . filter_var(_jtxt(generateCn($vv_co_person['PrimaryName'])),FILTER_SANITIZE_STRING)  // dialog body text replacement strings
                . '\']);">'
                . _txt('op.unlock')
                . '</button>';
            }
          }
        ?>
      </td>
    </tr>
    <?php $i++; ?>
    <?php endforeach; ?>
  </tbody>
</table>
