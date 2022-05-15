<?php
/**
 * COmanage Registry Vetting Requests Fields
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

  // Determine if fields are editable
  $e = false;
  
  // We shouldn't get here if we don't have at least read permission, but check just in case
  
  if(!$e && !$permissions['view'])
    return false;

  // Add breadcrumbs
  print $this->element("coCrumb");
  
  $args = array(
    'plugin' => null,
    'controller' => 'co_people',
    'action' => 'index',
    'co' => $cur_co['Co']['id']
  );
  $this->Html->addCrumb(_txt('me.population'), $args);
  
  $args = array(
    'controller' => 'co_people',
    'action' => 'canvas',
    $vetting_requests[0]['VettingRequest']['co_person_id']
  );
  $this->Html->addCrumb(generateCn($vetting_requests[0]['CoPerson']['PrimaryName']), $args);
  
  $args = array();
  $args['plugin'] = null;
  $args['controller'] = 'vetting_requests';
  $args['action'] = 'index';
  $args['copersonid'] = $vetting_requests[0]['VettingRequest']['co_person_id'];
  $this->Html->addCrumb(_txt('ct.vetting_requests.pl'), $args);
  
  $crumbTxt = _txt('op.' . $this->action . '-a', array(_txt('ct.vetting_requests.1')));
  $this->Html->addCrumb($crumbTxt);
?>
<script type="text/javascript">
  // Load widget content into divs
  $(document).ready(function() {
<?php
  if(!empty($vv_plugin_link)) {
    print "$('#resultDiv').load('" . addslashes($this->Html->url($vv_plugin_link)) . "', function() { $('#widgetSpinner').hide(); });\n";
  }
?>
  });
</script>

<ul id="<?php print $this->action; ?>_vetting_request" class="fields form-list">
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('fd.id.seq'); ?>
      </div>
    </div>
    <div class="field-info">
      <?php print filter_var($vetting_requests[0]['VettingRequest']['id'],FILTER_SANITIZE_SPECIAL_CHARS); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('ct.co_people.1'); ?>
      </div>
    </div>
    <div class="field-info">
      <?php
        print $this->Html->link(
          generateCn($vetting_requests[0]['CoPerson']['PrimaryName']),
          array(
            'controller' => 'co_people',
            'action'     => 'canvas',
            $vetting_requests[0]['CoPerson']['id']
          )
        );
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('fd.status'); ?>
      </div>
    </div>
    <div class="field-info">
      <?php
        print _txt('en.status.vet', null, $vetting_requests[0]['VettingRequest']['status']);
        
        if(in_array($vetting_requests[0]['VettingRequest']['status'],
                    array(VettingStatusEnum::PendingManual,
                          VettingStatusEnum::PendingResult,
                          VettingStatusEnum::Requested))) {
          if($permissions['cancel']) {
            print '<button type="button" class="deletebutton" title="' . _txt('op.delete')
              . '" onclick="javascript:js_confirm_generic(\''
              . _txt('js.cancel.job') . '\',\''    // dialog body text
              . $this->Html->url(              // dialog confirm URL
                array(
                  'controller' => 'vetting_requests',
                  'action' => 'cancel',
                  $vetting_requests[0]['VettingRequest']['id']
                )
              ) . '\',\''
              . _txt('op.remove') . '\',\''    // dialog confirm button
              . _txt('op.cancel') . '\',\''    // dialog cancel button
              . _txt('op.remove') . '\',[\''   // dialog title
              . filter_var(_jtxt($vetting_requests[0]['VettingRequest']['id']),FILTER_SANITIZE_STRING)  // dialog body text replacement strings
              . '\']);">'
              . _txt('op.cancel')
              . '</button>';
          }
        }
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('ct.co_jobs.1'); ?>
      </div>
    </div>
    <div class="field-info">
      <?php 
        if(!empty($vetting_requests[0]['VettingRequest']['co_job_id'])) {
          print $this->Html->link(
            $vetting_requests[0]['VettingRequest']['co_job_id'],
            array(
              'controller' => 'co_jobs',
              'action'     => 'view',
              $vetting_requests[0]['VettingRequest']['co_job_id']
            )
          );
        }
      ?>
    </div>
  </li>
  
  <?php if($e): ?>
    <li class="fields-submit">
      <div class="field-name">
        <span class="required"><?php print _txt('fd.req'); ?></span>
      </div>
      <div class="field-info">
        <?php print $this->Form->submit($submit_label); ?>
      </div>
    </li>
  <?php endif; ?>  
</ul>

<?php if($vetting_requests[0]['VettingRequest']['status'] == VettingStatusEnum::PendingManual
         && 
         $permissions['resolve']): ?>
<div class="table-container">
  <table id="vetting-result">
    <thead>
      <tr>
        <th><?php print _txt('in.vet.review'); ?></th>
      </tr>
    </thead>
    
    <tbody>
      <tr>
        <td>
          <div id="vetting-plugin-wrapper">
            <div id="vetting-request-wrapper">
              <h2>
                <?php print filter_var($vv_current_result[0]['VettingStep']['description'],FILTER_SANITIZE_SPECIAL_CHARS); ?>
              </h2>
            
              <?php
                $args = array(
                  'type' => 'post',
                  'url' => array(
                    'plugin' => null,
                    'controller' => 'vetting_requests',
                    'action' => 'resolve',
                    $vetting_requests[0]['VettingRequest']['id']
                  )
                );

                print $this->Form->create('VettingResult', $args);

                print $this->Form->hidden('vetting_step_id', array('default' => $vv_current_result[0]['VettingStep']['id']));

                print $this->Form->submit(_txt('op.approve'),
                  array(
                    'class' => 'checkbutton approve-button spin submit-button btn btn-primary ui-button ui-corner-all ui-widget',
                    'name'  => 'action')
                );

                print $this->Form->submit(_txt('op.deny'),
                  array(
                    'class' => 'checkbutton deny-button spin submit-button btn btn-primary ui-button ui-corner-all ui-widget',
                    'name'  => 'action')
                );

                print $this->Form->input('comment',
                                         array(
                                           'label' => _txt('fd.pt.approver_comment'),
                                           'placeholder' => _txt('en.required', null, RequiredEnum::Optional)
                                        ));

                print $this->Form->end();
              ?>
            </div>
            
            <div id="resultDiv"></div>
          </div>
        </td>
      </tr>
    </tbody>
  </table>
</div>
<?php endif; // PendingManual ?>

<?php if(!empty($vetting_requests[0]['VettingResult'])): ?>
<div class="table-container">
  <table id="vetting_results">
    <thead>
      <tr>
        <th><?php print _txt('ct.vetting_steps.1'); ?></th>
        <th><?php print _txt('fd.status'); ?></th>
        <th><?php print _txt('fd.timestamp'); ?></th>
        <th><?php print _txt('ct.vetting_results.1'); ?></th>
        <th><?php print _txt('fd.actions'); ?></th>
      </tr>
    </thead>
    
    <tbody>
      <?php $i = 0; ?>
      <?php foreach($vetting_requests[0]['VettingResult'] as $result): ?>
      <tr class="line<?php print ($i % 2)+1; ?>">
        <td><?php print filter_var($result['VettingStep']['description'],FILTER_SANITIZE_SPECIAL_CHARS); ?></td>
        <td><?php print _txt('en.status.vet', null, $result['status']); ?></td>
        <td><?php print $this->Time->nice($result['modified']); ?></td>
        <td><?php print filter_var($result['comment'],FILTER_SANITIZE_SPECIAL_CHARS); ?></td>
        <td>
          <?php
            print $this->Html->link(
              _txt('op.view'),
              array(
                'controller' => 'vetting_results',
                'action'     => 'view',
                $result['id']
              ),
              array(
                'class' => 'viewbutton'
              )
            );
          ?>
        </td>
      </tr>
      <?php endforeach; // $vv_sponsored_roles ?>
    </tbody>
  </table>
</div>
<?php endif; // vetting_requests ?>
<?php
  print $this->element("changelog");
