<?php
/**
 * COmanage Registry CO Petition Index View
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
 * @since         COmanage Registry v0.5
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

  // Globals
  global $cm_lang, $cm_texts;

  // Add breadcrumbs
  print $this->element("coCrumb");
  $this->Html->addCrumb(_txt('ct.petitions.pl'));

  // Add page title
  $params = array();
  $params['title'] = (!empty($cur_co['Co']['name']) ? $cur_co['Co']['name'] . ' ' : '') . _txt('ct.petitions.pl');

  // Add top links
  $params['topLinks'] = array();

  $params['topLinks'][] = $this->Html->link(
    _txt('op.view.all'),
    array('controller' => 'co_petitions',
          'action'     => 'index',
          'co'         => $cur_co['Co']['id'],
          'sort'       => 'CoPetition.created',
          'direction'  => 'desc'),
    array('class' => 'searchbutton')
  );

  $params['topLinks'][] =  $this->Html->link(
    _txt('op.view.pending.approval'),
    array(
      'controller'    => 'co_petitions',
      'action'        => 'index',
      'co'            => $cur_co['Co']['id'],
      'sort'          => 'CoPetition.created',
      'direction'     => 'desc',
      'search.status' => StatusEnum::PendingApproval
    ),
    array('class' => 'searchbutton')
  );

  $params['topLinks'][] =  $this->Html->link(
    _txt('op.view.pending.confirmation'),
    array(
      'controller'    => 'co_petitions',
      'action'        => 'index',
      'co'            => $cur_co['Co']['id'],
      'sort'          => 'CoPetition.created',
      'direction'     => 'desc',
      'search.status' => StatusEnum::PendingConfirmation,
    ),
    array('class' => 'searchbutton')
  );

  print $this->element("pageTitleAndButtons", $params);

  // Search Block
  if(!empty($vv_search_fields)) {
    print $this->element('search', array('vv_search_fields' => $vv_search_fields));
  }
?>

<div class="table-container">
  <table id="co_people">
    <thead>
      <tr>
        <th><?php print $this->Paginator->sort('EnrolleePrimaryName.family', _txt('fd.enrollee')); ?></th>
        <th><?php print $this->Paginator->sort('CoPetition.status', _txt('fd.status')); ?></th>
        <th><?php print $this->Paginator->sort('CoEnrollmentFlow.name', _txt('ct.co_enrollment_flows.1')); ?></th>
        <th><?php print $this->Paginator->sort('Cou.name', _txt('fd.cou')); ?></th>
        <th><?php print $this->Paginator->sort('PetitionerPrimaryName.family', _txt('fd.petitioner')); ?></th>
        <th><?php print $this->Paginator->sort('SponsorPrimaryName.family', _txt('fd.sponsor')); ?></th>
        <th><?php print $this->Paginator->sort('ApproverPrimaryName.family', _txt('fd.approver')); ?></th>
        <th><?php print $this->Paginator->sort('CoPetition.created', _txt('fd.created.tz', array($vv_tz))); ?></th>
        <th><?php print $this->Paginator->sort('CoPetition.modified', _txt('fd.modified.tz', array($vv_tz))); ?></th>
        <th><?php print _txt('fd.actions'); ?></th>
      </tr>
    </thead>

    <tbody>
      <?php $i = 0; ?>
      <?php foreach ($co_petitions as $p): ?>
      <tr class="line<?php print ($i % 2)+1; ?>">
        <td>
          <?php
            $displayName = (!empty($p['EnrolleePrimaryName']['id']) ? generateCn($p['EnrolleePrimaryName']) : _txt('fd.enrollee.new'));
            $displayNameWithId = (!empty($p['EnrolleePrimaryName']['id']) ? generateCn($p['EnrolleePrimaryName']) : _txt('fd.enrollee.new')) . ' (' . $p['CoPetition']['id'] . ')';
            print $this->Html->link($displayName,
                                    array(
                                      'controller' => 'co_petitions',
                                      'action' => ($permissions['edit']
                                                   ? 'view'
                                                   : ($permissions['view'] ? 'view' : '')),
                                      $p['CoPetition']['id'])
                                    );
          ?>
        </td>
        <td>
          <?php
            global $status_t;

            if(!empty($p['CoPetition']['status'])) {
              print _txt('en.status.pt', null, $p['CoPetition']['status']);
            }
          ?>
        </td>
        <td>
          <?php if(!empty($p['CoEnrollmentFlow']['name'])) { print $p['CoEnrollmentFlow']['name']; } ?>
        </td>
        <td>
          <?php if(!empty($p['Cou']['name'])) { print $p['Cou']['name']; } ?>
        </td>
        <td>
          <?php
            if(!empty($p['PetitionerCoPerson']['id'])) {
              print $this->Html->link(generateCn($p['PetitionerPrimaryName']),
                                      array(
                                        'controller' => 'co_people',
                                        'action' => 'canvas',
                                        $p['PetitionerCoPerson']['id'])
                                      );
            } else {
              print _txt('fd.actor.self');
            }
          ?>
        </td>
        <td>
          <?php
            if(isset($p['SponsorCoPerson']['id']) && $p['SponsorCoPerson']['id'] != '') {
              print $this->Html->link(generateCn($p['SponsorPrimaryName']),
                                      array(
                                        'controller' => 'co_people',
                                        'action' => 'canvas',
                                        $p['SponsorCoPerson']['id'])
                                      );
            }
          ?>
        </td>
        <td>
          <?php
            if(isset($p['ApproverCoPerson']['id']) && $p['ApproverCoPerson']['id'] != '') {
              print $this->Html->link(generateCn($p['ApproverPrimaryName']),
                                      array(
                                        'controller' => 'co_people',
                                        'action' => 'canvas',
                                        $p['ApproverCoPerson']['id'])
                                      );
            }
          ?>
        </td>
        <td>
          <?php
            if(!empty($p['CoPetition']['created'])) {
              print $this->Time->niceShort($p['CoPetition']['created'], $vv_tz);
            }
          ?>
        </td>
        <td>
          <?php
            if(!empty($p['CoPetition']['modified'])) {
              print $this->Time->niceShort($p['CoPetition']['modified'], $vv_tz);
            }
          ?>
        </td>
        <td>
          <?php
            if($permissions['edit']
               || (isset($p["permissions"]["approve"])
                   && $p["permissions"]["approve"])) {
              print $this->Html->link(_txt('op.view'),
                                      array('controller' => 'co_petitions',
                                            'action' => 'view',
                                            $p['CoPetition']['id']),
                                      array('class' => 'editbutton',
                                            'title' => _txt('op.view-a',array($displayNameWithId)),
                                            'aria-label' => _txt('op.view-a',array($displayNameWithId)))) . "\n";
            }

            if($permissions['delete']
               || (isset($p["permissions"]["deny"])
                   && $p["permissions"]["deny"])) {
              print '<button type="button" class="deletebutton" title="' . _txt('op.delete-a',array($displayNameWithId))
                . '" onclick="javascript:js_confirm_generic(\''
                . _txt('js.remove') . '\',\''    // dialog body text
                . $this->Html->url(              // dialog confirm URL
                  array(
                    'controller' => 'co_petitions',
                    'action' => 'delete',
                    $p['CoPetition']['id']
                  )
                ) . '\',\''
                . _txt('op.remove') . '\',\''    // dialog confirm button
                . _txt('op.cancel') . '\',\''    // dialog cancel button
                . _txt('op.remove') . '\',[\''   // dialog title
                . filter_var(_jtxt($displayNameWithId),FILTER_SANITIZE_STRING)  // dialog body text replacement strings
                . '\']);">'
                . _txt('op.delete')
                . '</button>';
            }

            if($permissions['resend'] && $p['CoPetition']['status'] == StatusEnum::PendingConfirmation) {
              $url = array(
                'controller' => 'co_petitions',
                'action' => 'resend',
                $p['CoPetition']['id']
              );

              $options = array();
              $options['class'] = 'invitebutton';
              $options['onclick'] = "javascript:js_confirm_generic('" . _jtxt(_txt('op.inv.resend.confirm', array(filter_var(generateCn($p['EnrolleePrimaryName']),FILTER_SANITIZE_SPECIAL_CHARS)))) . "', '"
                                                                   . Router::url($url) . "', '"
                                                                   . _txt('op.inv.resend') . "');return false";
              $options['title'] = _txt('op.inv.resend.to', array($displayNameWithId));
              $options['aria-label'] = _txt('op.inv.resend.to', array($displayNameWithId));

              print $this->Html->link(_txt('op.inv.resend'),
                                      $url,
                                      $options) . "\n";
            }
          ?>
          <?php ; ?>
        </td>
      </tr>
      <?php $i++; ?>
      <?php endforeach; // $co_petitions ?>
      <?php
        if (count($co_petitions) == 0) {
          print '<tr><td colspan="10">' . _txt('rs.search.none') . '</td></tr>';
        }
      ?>
    </tbody>
  </table>
</div>

<?php
  print $this->element("pagination");
