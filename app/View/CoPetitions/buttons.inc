<?php
/**
 * COmanage Registry CO Enrollment Configuration Fields
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
 * @since         COmanage Registry v0.3
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

// Add buttons
if($this->action != "add") {

  // If status is pending confirmation, offer a link to resend the invite
  if($permissions['resend']
    && !empty($co_petitions[0]['CoPetition']['status'])
    && $co_petitions[0]['CoPetition']['status'] == PetitionStatusEnum::PendingConfirmation) {

    $buttonUrl = array(
      'controller'    => 'co_petitions',
      'action'        => 'resend',
      $co_petitions[0]['CoPetition']['id']
    );

    // Construct jQuery popup
    $onclickAction =  "javascript:js_confirm_generic('" .
                      _txt('op.inv.resend.confirm', array(filter_var(generateCn($co_petitions[0]['EnrolleeCoPerson']['PrimaryName']),FILTER_SANITIZE_STRING))) .
                      "', '" . Router::url($buttonUrl) . "', '" .
                      _txt('op.inv.resend') . "');
                      return false;";

    $params['topLinks'][] = $this->Html->link(
      _txt('op.inv.resend'),
      $buttonUrl,
      array(
        'class' => 'invitebutton',
        'onclick' => $onclickAction
      )
    );
  }

  if($permissions['addhistory']) {
    $params['topLinks'][] = $this->Html->link(
      _txt('op.petition.comment'),
      array(
        'controller'    => 'co_petition_history_records',
        'action'        => 'add',
        'copetitionid'  => $co_petitions[0]['CoPetition']['id']
      ),
      array(
        'class' => 'notebutton'
      )
    );
  }

  // If status is pending confirmation or approval, offer ability to flag as duplicate.
  if($permissions['dupe']
    && !empty($co_petitions[0]['CoPetition']['status'])
    && ($co_petitions[0]['CoPetition']['status'] == PetitionStatusEnum::PendingApproval
      || $co_petitions[0]['CoPetition']['status'] == PetitionStatusEnum::PendingConfirmation)) {

    $buttonUrl = array(
      'controller'    => 'co_petitions',
      'action'        => 'dupe',
      $co_petitions[0]['CoPetition']['id']
    );

    // Construct jQuery popup
    $onclickAction =  "javascript:js_confirm_generic('" .
                      _txt('op.petition.dupe.confirm') .
                      "', '" . Router::url($buttonUrl) . "', '" .
                      _txt('op.petition.dupe') . "');
                      return false;";

    $params['topLinks'][] = $this->Html->link(
      _txt('op.petition.dupe'),
      $buttonUrl,
      array(
        'class'   => 'flagbutton',
        'onclick' => $onclickAction
      )
    );
  }
}
