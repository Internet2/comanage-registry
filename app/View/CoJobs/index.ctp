<?php
/**
 * COmanage Registry CO Job Index View
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
  $this->Html->addCrumb(_txt('ct.co_jobs.pl'));

  // Add page title
  $params = array();
  $params['title'] = $title_for_layout;
  print $this->element("pageTitleAndButtons", $params);
?>

<div class="table-container">
  <table id="co_jobs">
    <thead>
      <tr>
        <th><?php print $this->Paginator->sort('id', "#"); ?></th>
        <th><?php print $this->Paginator->sort('job_type', _txt('fd.job.type')); ?></th>
        <th><?php print $this->Paginator->sort('status', _txt('fd.status')); ?></th>
        <th><?php print _txt('fd.job.register_summary'); ?></th>
        <th><?php print $this->Paginator->sort('queue_time', _txt('fd.created.tz', array($vv_tz))); ?></th>
      </tr>
    </thead>

    <tbody>
      <?php $i = 0; ?>
      <?php foreach ($co_jobs as $c): ?>
      <tr class="line<?php print ($i % 2)+1; ?>">
        <td>
          <?php
            print $c['CoJob']['id'];
          ?>
        </td>
        <td>
          <?php 
            // XXX CO-1310 simplify
            if(strlen($c['CoJob']['job_type'])==2) {
              print _txt('en.job.type', null, $c['CoJob']['job_type']);
            } else {
              print filter_var($c['CoJob']['job_type'], FILTER_SANITIZE_SPECIAL_CHARS);
            }
          ?>
        </td>
        <td><?php print $this->Html->link(_txt('en.status.job', null, $c['CoJob']['status']),
                                          array(
                                            'controller' => 'co_jobs',
                                            'action'     => 'view',
                                            $c['CoJob']['id']
                                          )); ?></td>
        <td>
          <?php
            // We use register_summary (at least for now) since it's more informative.
            // (OIS jobs include OIS name.)
            
            if(!empty($c['CoJob']['register_summary'])) {
              print filter_var($c['CoJob']['register_summary'], FILTER_SANITIZE_SPECIAL_CHARS);
            }
          ?>
        </td>
        <td>
          <?php
            if($c['CoJob']['queue_time']) {
              print $this->Time->niceShort($c['CoJob']['queue_time'], $vv_tz);
            }
          ?>
        </td>
      </tr>
      <?php $i++; ?>
      <?php endforeach; ?>
    </tbody>

  </table>
</div>

<?php
  print $this->element("pagination");