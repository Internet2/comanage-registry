<?php
/**
 * COmanage Registry CO Job History Records Index View
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
  
  if(!empty($co_job_history_records[0])) {
    if(!empty($this->request->params['named']['copersonid'])) {
      $args = array();
      $args['plugin'] = null;
      $args['controller'] = 'co_people';
      $args['action'] = 'index';
      $args['co'] = $cur_co['Co']['id'];
      $this->Html->addCrumb(_txt('ct.co_people.pl'), $args);
      
      $args = array();
      $args['plugin'] = null;
      $args['controller'] = 'co_people';
      $args['action'] = 'canvas';
      $args[] = $co_job_history_records[0]['CoPerson']['id'];
      $this->Html->addCrumb(generateCn($co_job_history_records[0]['CoPerson']['PrimaryName']), $args);
    } elseif(!empty($this->request->params['named']['orgidentityid'])) {
      $args = array();
      $args['plugin'] = null;
      $args['controller'] = 'org_identities';
      $args['action'] = 'index';
      $args['co'] = $cur_co['Co']['id'];
      $this->Html->addCrumb(_txt('ct.org_identities.pl'), $args);
      
      $args = array();
      $args['plugin'] = null;
      $args['controller'] = 'org_identities';
      $args['action'] = 'edit';
      $args[] = $co_job_history_records[0]['OrgIdentity']['id'];
      $this->Html->addCrumb(generateCn($co_job_history_records[0]['OrgIdentity']['PrimaryName']), $args);
    } else {
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
    }
  }
  
  $this->Html->addCrumb(_txt('ct.co_job_history_records.pl'));
  
  // Add page title
  $params = array();
  $params['title'] = $title_for_layout;
  print $this->element("pageTitleAndButtons", $params);
?>
<?php
  // Search Block
  if(!empty($vv_search_fields)) {
    print $this->element('search', array('vv_search_fields' => $vv_search_fields));
  }
?>
<div class="table-container">
  <table id="co_jobs">
    <thead>
      <tr>
        <th><?php print $this->Paginator->sort('id', "#"); ?></th>
        <th><?php print $this->Paginator->sort('CoJob.job_type', _txt('fd.job.type')); ?></th>
        <th><?php print $this->Paginator->sort('record_key', _txt('fd.key')); ?></th>
        <th><?php print $this->Paginator->sort('comment', _txt('fd.comment')); ?></th>
        <th><?php print $this->Paginator->sort('status', _txt('fd.status')); ?></th>
        <th><?php print $this->Paginator->sort('created', _txt('fd.created.tz', array($vv_tz))); ?></th>
      </tr>
    </thead>

    <tbody>
      <?php $i = 0; ?>
      <?php foreach ($co_job_history_records as $c): ?>
      <tr class="line<?php print ($i % 2)+1; ?>">
        <td>
          <?php
            print $this->Html->link($c['CoJobHistoryRecord']['id'],
                                    array(
                                      'controller' => 'co_job_history_records',
                                      'action'     => 'view',
                                      $c['CoJobHistoryRecord']['id']
                                    ),
                                    array(
                                      'class' => 'lightbox'
                                    ));
          ?>
        </td>
        <td>
          <?php 
            // XXX CO-1310 Simplify this
            if(strlen($c['CoJob']['job_type'])==2) {
              print _txt('en.job.type', null, $c['CoJob']['job_type']);
            } else {
              print filter_var($c['CoJob']['job_type'], FILTER_SANITIZE_SPECIAL_CHARS);
            }
          ?>
        </td>
        <td>
          <?php
            if(!empty($c['CoJobHistoryRecord']['record_key'])) {
              print filter_var($c['CoJobHistoryRecord']['record_key'], FILTER_SANITIZE_SPECIAL_CHARS);
            }
          ?>
        </td>
        <td>
          <?php
            if(!empty($c['CoJobHistoryRecord']['comment'])) {
              print filter_var($c['CoJobHistoryRecord']['comment'], FILTER_SANITIZE_SPECIAL_CHARS);
            }
          ?>
        </td>
        <td>
          <?php print filter_var(_txt('en.status.job', null, $c['CoJobHistoryRecord']['status'])); ?>
        </td>
        <td>
          <?php
            if($c['CoJobHistoryRecord']['created']) {
              print $this->Time->niceShort($c['CoJobHistoryRecord']['created'], $vv_tz);
            }
          ?>
        </td>
      </tr>
      <?php $i++; ?>
      <?php endforeach; ?>
    </tbody>

  </table>
</div>

<?php print $this->element("pagination"); ?>
