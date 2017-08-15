<?php
/**
 * COmanage Registry CO Job History Records Fields
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

  // Determine if fields are editable
  $e = false;
  
  // We shouldn't get here if we don't have at least read permission, but check just in case
  
  if(!$e && !$permissions['view'])
    return(false);

  // Add breadcrumbs
  print $this->element("coCrumb");
  $args = array();
  $args['plugin'] = null;
  $args['controller'] = 'co_jobs';
  $args['action'] = 'index';
  $args['co'] = $cur_co['Co']['id'];
  $this->Html->addCrumb(_txt('ct.co_jobs.pl'), $args);

  $args = array();
  $args['plugin'] = null;
  $args['controller'] = 'co_jobs';
  $args['action'] = 'view';
  $args[] = $co_job_history_records[0]['CoJobHistoryRecord']['co_job_id'];
  $this->Html->addCrumb($co_job_history_records[0]['CoJobHistoryRecord']['co_job_id'], $args);

  $this->Html->addCrumb(_txt('ct.co_job_history_records.1'));
  
  $l = 1;
?>
<ul id="<?php print $this->action; ?>_co_job_history_records" class="fields form-list">
  <li>
    <div class="field-name">
      <?php print _txt('ct.co_jobs.1'); ?>
    </div>
    <div class="field-info">
      <?php
        print $this->Html->link($co_job_history_records[0]['CoJobHistoryRecord']['co_job_id'],
                                array(
                                  'controller' => 'co_jobs',
                                  'action'     => 'view',
                                  $co_job_history_records[0]['CoJobHistoryRecord']['co_job_id']
                                ));
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <?php print _txt('fd.key'); ?>
    </div>
    <div class="field-info">
      <?php print filter_var($co_job_history_records[0]['CoJobHistoryRecord']['record_key'], FILTER_SANITIZE_STRING); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <?php print _txt('fd.comment'); ?>
    </div>
    <div class="field-info">
      <?php print filter_var($co_job_history_records[0]['CoJobHistoryRecord']['comment'], FILTER_SANITIZE_STRING); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <?php print _txt('fd.status'); ?>
    </div>
    <div class="field-info">
      <?php print _txt('en.status.job', null, $co_job_history_records[0]['CoJobHistoryRecord']['status']); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <?php print _txt('ct.co_people.1'); ?>
    </div>
    <div class="field-info">
      <?php
        if(!empty($co_job_history_records[0]['CoJobHistoryRecord']['co_person_id'])) {
          print $this->Html->link(generateCn($co_job_history_records[0]['CoPerson']['PrimaryName']),
                                  array(
                                    'controller' => 'co_people',
                                    'action'     => 'canvas',
                                    $co_job_history_records[0]['CoJobHistoryRecord']['co_person_id']
                                  ));
        }
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <?php print _txt('ct.org_identities.1'); ?>
    </div>
    <div class="field-info">
      <?php
        if(!empty($co_job_history_records[0]['CoJobHistoryRecord']['org_identity_id'])) {
          print $this->Html->link(generateCn($co_job_history_records[0]['OrgIdentity']['PrimaryName']),
                                  array(
                                    'controller' => 'org_identities',
                                    'action'     => 'edit',
                                    $co_job_history_records[0]['CoJobHistoryRecord']['org_identity_id']
                                  ));
        }
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <?php print _txt('fd.created'); ?>
    </div>
    <div class="field-info">
      <?php print $this->Time->format($co_job_history_records[0]['CoJobHistoryRecord']['created'], "%c $vv_tz", false, $vv_tz); ?>
    </div>
  </li>
</ul>
