<?php
/**
 * COmanage Registry Recovery Widget Display View
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
 * @link          https://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v4.1.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */
?>
<ul>
<?php 
  if(!empty($vv_config['CoRecoveryWidget']['enable_confirmation_resend'])
         && $vv_config['CoRecoveryWidget']['enable_confirmation_resend']) {
    print "<li>"
          . $this->Html->link(
              _txt('pl.recoverywidget.op.confirmation_resend'),
              array(
                'plugin'            => 'recovery_widget',
                'controller'        => 'actions',
                'action'            => 'lookup',
                'recoverywidgetid'  => $vv_config['CoRecoveryWidget']['id'],
                'task'              => 'confirmation_resend'
              )
            )
          . "</li>";
  }

  if(!empty($vv_config['CoRecoveryWidget']['identifier_template_id'])) {
    print "<li>"
          . $this->Html->link(
              _txt('pl.recoverywidget.op.identifier_lookup'),
              array(
                'plugin'            => 'recovery_widget',
                'controller'        => 'actions',
                'action'            => 'lookup',
                'recoverywidgetid'  => $vv_config['CoRecoveryWidget']['id'],
                'task'              => 'identifier_lookup'
              )
            )
          . "</li>";
  }

  if(!empty($vv_config['CoRecoveryWidget']['authenticator_id'])) {
    if(!empty($vv_config['CoRecoveryWidget']['authenticator_reset_template_id'])) {
      print "<li>"
            . $this->Html->link(
                _txt('pl.recoverywidget.op.authenticator_reset'),
                array(
                  'plugin'            => 'recovery_widget',
                  'controller'        => 'actions',
                  'action'            => 'lookup',
                  'recoverywidgetid'  => $vv_config['CoRecoveryWidget']['id'],
                  'task'              => 'authenticator_reset'
                )
              )
            . "</li>";
    }
  }

  if(!empty($vv_authenticator_change_url)) {
    print "<li>"
            . $this->Html->link(
                _txt('pl.recoverywidget.op.authenticator_change'),
                $vv_authenticator_change_url
              )
            . "</li>";
  }
?>
</ul>