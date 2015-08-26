<!--
/**
 * COmanage Registry CO Petition Index View
 *
 * Copyright (C) 2012-15 University Corporation for Advanced Internet Development, Inc.
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
 * @copyright     Copyright (C) 2012-15 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.5
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */
-->
<?php
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
          'sort'       => 'created',
          'direction'  => 'desc'),
    array('class' => 'searchbutton')
  );

  $params['topLinks'][] =  $this->Html->link(
    _txt('op.view.pending'),
    array(
      'controller'    => 'co_petitions',
      'action'        => 'index',
      'co'            => $cur_co['Co']['id'],
      'sort'          => 'created',
      'direction'     => 'desc',
      'search.status' => array(
        StatusEnum::PendingApproval,
        StatusEnum::PendingConfirmation
      )
    ),
    array('class' => 'searchbutton')
  );

  print $this->element("pageTitleAndButtons", $params);
?>

<div id="statusFilters">
  <?php
  print $this->Form->create('CoPetition', array('action'=>'search'));
  print $this->Form->hidden('CoPetition.co_id', array('default' => $cur_co['Co']['id'])). "\n";
  print $this->Form->submit(_txt('op.filter'));

  // Build array of options based on model validation
  $searchOptions = $cm_texts[ $cm_lang ]['en.status.pt'];

  // Build array to check off actively used filters on the page
  $selected = array();

  if(isset($this->passedArgs['search.status'])) {
    $selected = $this->passedArgs['search.status'];
  }

  // Collect parameters and print checkboxes
  $formParams = array('options'  => $searchOptions,
    'multiple' => 'checkbox',
    'label'    => false,
    'selected' => $selected);

  print $this->Form->input('search.status', $formParams);
  print $this->Form->end();
  ?>
</div>

<table id="co_people" class="ui-widget">
  <thead>
    <tr class="ui-widget-header">
      <th><?php print $this->Paginator->sort('EnrolleeCoPerson.Name.family', _txt('fd.enrollee')); ?></th>
      <th><?php print $this->Paginator->sort('status', _txt('fd.status')); ?></th>
      <th><?php print $this->Paginator->sort('CoEnrollmentFlow.name', _txt('ct.co_enrollment_flows.1')); ?></th>
      <th><?php print $this->Paginator->sort('Cou.name', _txt('fd.cou')); ?></th>
      <th><?php print $this->Paginator->sort('PetitionerCoPerson.Name.family', _txt('fd.petitioner')); ?></th>
      <th><?php print $this->Paginator->sort('SponsorCoPerson.Name.family', _txt('fd.sponsor')); ?></th>
      <th><?php print $this->Paginator->sort('ApproverCoPerson.Name.family', _txt('fd.approver')); ?></th>
      <th><?php print $this->Paginator->sort('created', _txt('fd.created.tz', array($vv_tz))); ?></th>
      <th><?php print $this->Paginator->sort('modified', _txt('fd.modified.tz', array($vv_tz))); ?></th>
      <th class="actionButtons"><?php print _txt('fd.actions'); ?></th>
    </tr>
  </thead>
  
  <tbody>
    <?php $i = 0; ?>
    <?php foreach ($co_petitions as $p): ?>
    <tr class="line<?php print ($i % 2)+1; ?>">
      <td>
        <?php
          print $this->Html->link(!empty($p['EnrolleeCoPerson']['PrimaryName'])
                                  ? generateCn($p['EnrolleeCoPerson']['PrimaryName'])
                                  : _txt('fd.enrollee.new'),
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
            print $this->Html->link(generateCn($p['PetitionerCoPerson']['PrimaryName']),
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
            print $this->Html->link(generateCn($p['SponsorCoPerson']['PrimaryName']),
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
            print $this->Html->link(generateCn($p['ApproverCoPerson']['PrimaryName']),
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
      <td class="actions">
        <?php
          if($permissions['edit']) {
            print $this->Html->link(_txt('op.view'),
                                    array('controller' => 'co_petitions',
                                          'action' => 'view',
                                          $p['CoPetition']['id']),
                                    array('class' => 'editbutton')) . "\n";
          }
          
          if($permissions['delete']) {
            $displayName = (!empty($p['EnrolleeCoPerson']['PrimaryName']) ? generateCn($p['EnrolleeCoPerson']['PrimaryName']) : _txt('fd.enrollee.new')) . ' (' . $p['CoPetition']['id'] . ')';
            print '<button type="button" class="deletebutton" title="' . _txt('op.delete')
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
              . filter_var(_jtxt($displayName),FILTER_SANITIZE_STRING)  // dialog body text replacement strings
              . '\'])";>'
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
            $options['onclick'] = "javascript:js_confirm_generic('" . _jtxt(_txt('op.inv.resend.confirm', array(generateCn($p['EnrolleeCoPerson']['PrimaryName'])))) . "', '"
                                                                 . Router::url($url) . "', '"
                                                                 . _txt('op.inv.resend') . "');return false";
            
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
        print '<tr><td colspan="10">' . _txt('ct.co_petitions.0') . '</td></tr>';
      }
    ?>
  </tbody>
  
  <tfoot>
    <tr class="ui-widget-header">
      <th colspan="10">
        <?php print $this->element("pagination"); ?>
      </th>
    </tr>
  </tfoot>
</table>
