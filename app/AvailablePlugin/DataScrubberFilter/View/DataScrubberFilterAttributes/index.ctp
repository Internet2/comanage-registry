<?php
/**
 * COmanage Registry Data Scrubber Filter Attribute Index View
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

  // Add breadcrumbs
  print $this->element("coCrumb");

  $args = array();
  $args['plugin'] = null;
  $args['controller'] = 'data_filters';
  $args['action'] = 'index';
  $args['co'] = $cur_co['Co']['id'];
  $this->Html->addCrumb(_txt('ct.data_filters.pl'), $args);

  $args = array();
  $args['plugin'] = null;
  $args['controller'] = 'data_filters';
  $args['action'] = 'edit';
  $args[] = $vv_datafilter['id'];
  $this->Html->addCrumb($vv_datafilter['description'], $args);

  $args = array();
  $args['plugin'] = 'data_scrubber_filter';
  $args['controller'] = 'data_scrubber_filters';
  $args['action'] = 'edit';
  $args[] = $vv_dsfilter['id'];
  $this->Html->addCrumb(_txt('op.config'), $args);

  $crumbTxt = _txt('ct.data_scrubber_filter_attributes.pl');
  $this->Html->addCrumb($crumbTxt);
  
  // Add page title
  $params = array();
  $params['title'] = $title_for_layout;

  // Add top links
  $params['topLinks'] = array();

  if($permissions['add']) {
    $params['topLinks'][] = $this->Html->link(
      _txt('op.add-a', array(_txt('ct.data_scrubber_filter_attributes.1'))),
      array(
        'plugin' => 'data_scrubber_filter',
        'controller' => 'data_scrubber_filter_attributes',
        'action' => 'add',
        'datascrubberfilter' => $vv_dsfilter['id']
      ),
      array('class' => 'addbutton')
    );
  }

  print $this->element("pageTitleAndButtons", $params);
?>

<div class="table-container">
  <table id="data_scrubber_filter_attributes">
    <thead>
      <tr>
        <th><?php print $this->Paginator->sort('attribute', _txt('fd.attribute')); ?></th>
        <th><?php print $this->Paginator->sort('type', _txt('fd.type')); ?></th>
        <th><?php print $this->Paginator->sort('required', _txt('fd.required')); ?></th>
        <th><?php print _txt('fd.actions'); ?></th>
      </tr>
    </thead>

    <tbody>
      <?php $i = 0; ?>
      <?php foreach($data_scrubber_filter_attributes as $d): ?>
      <tr class="line<?php print ($i % 2)+1; ?>">
        <td>
          <?php
            print $this->Html->link($d['DataScrubberFilterAttribute']['attribute'],
                                    array(
                                      'plugin' => 'data_scrubber_filter',
                                      'controller' => 'data_scrubber_filter_attributes',
                                      'action' => ($permissions['edit'] ? 'edit' : ($permissions['view'] ? 'view' : '')),
                                      $d['DataScrubberFilterAttribute']['id']
                                    ));
          ?>
        </td>
        <td><?php print filter_var($d['DataScrubberFilterAttribute']['type'],FILTER_SANITIZE_SPECIAL_CHARS); ?></td>
        <td><?php print _txt('en.required', null, $d['DataScrubberFilterAttribute']['required']); ?></td>
        <td>
          <?php
            if($permissions['edit']) {
              print $this->Html->link(_txt('op.edit'),
                  array(
                    'plugin' => 'data_scrubber_filter',
                    'controller' => 'data_scrubber_filter_attributes',
                    'action' => 'edit',
                    $d['DataScrubberFilterAttribute']['id']
                  ),
                  array('class' => 'editbutton')) . "\n";
            }
            if($permissions['delete']) {
              print '<button type="button" class="deletebutton" title="' . _txt('op.delete')
                . '" onclick="javascript:js_confirm_generic(\''
                . _txt('js.remove') . '\',\''    // dialog body text
                . $this->Html->url(              // dialog confirm URL
                  array(
                    'plugin' => 'data_scrubber_filter',
                    'controller' => 'data_scrubber_filter_attributes',
                    'action' => 'delete',
                    $d['DataScrubberFilterAttribute']['id'],
                    'datascrubberfilter' => $d['DataScrubberFilterAttribute']['data_scrubber_filter_id']
                  )
                ) . '\',\''
                . _txt('op.remove') . '\',\''    // dialog confirm button
                . _txt('op.cancel') . '\',\''    // dialog cancel button
                . _txt('op.remove') . '\',[\''   // dialog title
                . filter_var(_jtxt($d['DataScrubberFilterAttribute']['attribute']),FILTER_SANITIZE_STRING)  // dialog body text replacement strings
                . '\']);">'
                . _txt('op.delete')
                . '</button>';
            }
          ?>
          <?php ; ?>
        </td>
      </tr>
      <?php $i++; ?>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php
  print $this->element("pagination"); 