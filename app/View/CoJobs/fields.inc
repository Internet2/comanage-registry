<?php
/**
 * COmanage Registry CO Jobs Fields
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
  $args['co'] = $co_jobs[0]['CoJob']['co_id'];
  $this->Html->addCrumb(_txt('ct.co_jobs.pl'), $args);
  $this->Html->addCrumb($co_jobs[0]['CoJob']['id']);
  
  $l = 1;
?>
<ul id="<?php print $this->action; ?>_co_job" class="fields form-list">
  <li>
    <div class="field-name">
      <?php print _txt('fd.job.type'); ?>
    </div>
    <div class="field-info">
      <?php
        print _txt('en.job.type', null, $co_jobs[0]['CoJob']['job_type'])
            . " (" . $co_jobs[0]['CoJob']['job_type_fk'] . ")";
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <?php print _txt('fd.job.mode'); ?>
    </div>
    <div class="field-info">
      <?php print filter_var($co_jobs[0]['CoJob']['job_mode'], FILTER_SANITIZE_STRING); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <?php print _txt('fd.status'); ?>
    </div>
    <div class="field-info">
      <?php
        print _txt('en.status.job', null, $co_jobs[0]['CoJob']['status']);
        
        // This array corresponds to CoJob::cancel()
        if(in_array($co_jobs[0]['CoJob']['status'], array(JobStatusEnum::InProgress, JobStatusEnum::Queued))) {
          print ' <button type="button" class="cancelbutton" title="' . _txt('op.cancel')
                . '" onclick="javascript:js_confirm_generic(\''
                . _txt('js.cancel.job') . '\',\''    // dialog body text
                . $this->Html->url(              // dialog confirm URL
                  array(
                    'controller' => 'co_jobs',
                    'action' => 'cancel',
                    $co_jobs[0]['CoJob']['id']
                  )
                ) . '\',\''
                . _txt('fd.yes') . '\',\''    // dialog confirm button
                . _txt('fd.no') . '\',\''    // dialog cancel button
                . _txt('op.cancel') . '\',[\''   // dialog title
                . '\']);">'
                . _txt('op.cancel')
                . '</button>';
        }
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <?php print _txt('fd.job.queue_time'); ?>
    </div>
    <div class="field-info">
      <?php print $this->Time->format($co_jobs[0]['CoJob']['queue_time'], "%c $vv_tz", false, $vv_tz); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <?php print _txt('fd.job.register_summary'); ?>
    </div>
    <div class="field-info">
      <?php print filter_var($co_jobs[0]['CoJob']['register_summary'], FILTER_SANITIZE_STRING); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <?php print _txt('fd.job.start_time'); ?>
    </div>
    <div class="field-info">
      <?php print $this->Time->format($co_jobs[0]['CoJob']['start_time'], "%c $vv_tz", false, $vv_tz); ?>
    </div>
  </li>  
  <li>
    <div class="field-name">
      <?php print _txt('fd.job.start_summary'); ?>
    </div>
    <div class="field-info">
      <?php print filter_var($co_jobs[0]['CoJob']['start_summary'], FILTER_SANITIZE_STRING); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <?php print _txt('fd.job.complete_time'); ?>
    </div>
    <div class="field-info">
      <?php print $this->Time->format($co_jobs[0]['CoJob']['complete_time'], "%c $vv_tz", false, $vv_tz); ?>
    </div>
  </li>  
  <li>
    <div class="field-name">
      <?php print _txt('fd.job.finish_summary'); ?>
    </div>
    <div class="field-info">
      <?php print filter_var($co_jobs[0]['CoJob']['finish_summary'], FILTER_SANITIZE_STRING); ?>
    </div>
  </li>
</ul>