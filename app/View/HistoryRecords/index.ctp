<?php
/**
 * COmanage Registry HistoryRecord Index View
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
 * @since         COmanage Registry v0.7
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

  // Add breadcrumbs
  print $this->element("coCrumb");
  if(isset($this->request->params['named']['copersonid'])) {
    // CO Person History
    $args = array();
    $args['plugin'] = null;
    $args['controller'] = 'co_people';
    $args['action'] = 'index';
    $args['co'] = $cur_co['Co']['id'];
    $this->Html->addCrumb(_txt('me.population'), $args);

    $args = array(
      'controller' => 'co_people',
      'action' => 'canvas',
      filter_var($this->request->params['named']['copersonid'],FILTER_SANITIZE_SPECIAL_CHARS));
    if (isset($display_name)) {
      $this->Html->addCrumb($display_name, $args);
    } else {
      $this->Html->addCrumb(_txt('ct.co_people.1'), $args);
    }

  } elseif(isset($this->request->params['named']['orgidentityid'])) {
    // Org ID History
    $args = array();
    $args['plugin'] = null;
    $args['controller'] = 'org_identities';
    $args['action'] = 'index';
    if(!$pool_org_identities) {
      $args['co'] = $cur_co['Co']['id'];
    }
    $this->Html->addCrumb(_txt('ct.org_identities.pl'), $args);

    $args = array(
      'controller' => 'orgIdentities',
      'action' => 'edit',
      filter_var($this->request->params['named']['orgidentityid'],FILTER_SANITIZE_SPECIAL_CHARS));
    $this->Html->addCrumb(_txt('ct.org_identities.1'), $args);
  }
  $this->Html->addCrumb(_txt('ct.history_records.pl'));

  // Add page title
  $params = array();
  $params['title'] = _txt('ct.history_records.pl');

  // Add top links
  $params['topLinks'] = array();

  if($permissions['add']) {
    $args = array();
    $args['controller'] = 'history_records';
    $args['action'] = 'add';

    if(isset($this->request->params['named']['copersonid'])) {
      $args['copersonid'] = filter_var($this->request->params['named']['copersonid'],FILTER_SANITIZE_SPECIAL_CHARS);
    } elseif(isset($this->request->params['named']['orgidentityid'])) {
      $args['orgidentityid'] = filter_var($this->request->params['named']['orgidentityid'],FILTER_SANITIZE_SPECIAL_CHARS);
    }

    $params['topLinks'][] = $this->Html->link(
      _txt('op.add-a', array(_txt('ct.history_records.1'))),
      $args,
      array('class' => 'addbutton')
    );
  }

  print $this->element("pageTitleAndButtons", $params);

?>

<div class="table-container">
  <table id="history_records">
    <thead>
      <tr>
        <th><?php print $this->Paginator->sort('id', _txt('fd.id.seq')); ?></th>
        <th><?php print $this->Paginator->sort('created', _txt('fd.created.tz', array($vv_tz))); ?></th>
        <th><?php print $this->Paginator->sort('comment', _txt('fd.comment')); ?></th>
        <th><?php print $this->Paginator->sort('Actor.PrimaryName.family', _txt('fd.actor')); ?></th>
        <th><?php print $this->Paginator->sort('OrgIdentity.PrimaryName.family', _txt('ct.org_identities.1')); ?></th>
        <th><?php print $this->Paginator->sort('CoPerson.PrimaryName.family', _txt('ct.co_people.1')); ?></th>
        <th><?php print _txt('fd.action'); ?></th>
      </tr>
    </thead>

    <tbody>
      <?php $i = 0; ?>
      <?php foreach ($history_records as $h): ?>
      <tr class="line<?php print ($i % 2)+1; ?>">
        <td><?php print $h['HistoryRecord']['id']; ?></td>
        <td><?php print $this->Time->niceShort($h['HistoryRecord']['created'], $vv_tz); ?></td>
        <td><?php print filter_var($h['HistoryRecord']['comment'],FILTER_SANITIZE_SPECIAL_CHARS) . "\n";?></td>
        <td>
          <?php
            if(!empty($h['ActorCoPerson']['id'])) {
              print $this->Html->link(
                (!empty($h['ActorCoPerson']['PrimaryName']) ? filter_var(generateCn($h['ActorCoPerson']['PrimaryName']),FILTER_SANITIZE_SPECIAL_CHARS) : _txt('fd.deleted')),
                array(
                  'controller' => 'co_people',
                  'action' => 'view',
                  $h['ActorCoPerson']['id']
                )
              );
            }
          ?>
        </td>
        <td>
          <?php
            if(!empty($h['OrgIdentity']['id'])) {
              print $this->Html->link(
                (!empty($h['OrgIdentity']['PrimaryName']) ? filter_var(generateCn($h['OrgIdentity']['PrimaryName']),FILTER_SANITIZE_SPECIAL_CHARS) : _txt('fd.deleted')),
                array(
                  'controller' => 'org_identities',
                  'action' => 'view',
                  $h['OrgIdentity']['id']
                )
              );
            }
          ?>
        </td>
        <td>
          <?php
            if(!empty($h['CoPerson']['id'])) {
              print $this->Html->link(
                (!empty($h['CoPerson']['PrimaryName']) ? filter_var(generateCn($h['CoPerson']['PrimaryName']),FILTER_SANITIZE_SPECIAL_CHARS) : _txt('fd.deleted')),
                array(
                  'controller' => 'co_people',
                  'action' => 'canvas',
                  $h['CoPerson']['id']
                )
              );
            }
          ?>
        </td>
        <td>
          <?php
            print $this->Html->link(
              _txt('op.view'),
              array(
                'controller' => 'history_records',
                'action'     => 'view',
                $h['HistoryRecord']['id']
              ),
              array(
                'class' => 'viewbutton lightbox'
              )
            );
          ?>
        </td>
      </tr>
      <?php $i++; ?>
      <?php endforeach; ?>
    </tbody>

  </table>
</div>

<?php print $this->element("pagination"); ?>

<script type="text/javascript">
  $(document).ready(function() {
    $('a.lightbox').magnificPopup({
      type:'iframe'
    });
  });
</script>

