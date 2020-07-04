<?php
/**
 * COmanage Registry Dictionary Entries Index View
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
 * @since         COmanage Registry v4.0.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

  // Add breadcrumbs
  print $this->element("coCrumb");
  
  $args = array();
  $args['controller'] = 'dictionaries';
  $args['action'] = 'index';
  $args['co'] = $cur_co['Co']['id'];
  $this->Html->addCrumb(_txt('ct.dictionaries.pl'), $args);
  
  $args = array();
  $args['controller'] = 'dictionaries';
  $args['action'] = 'edit';
  $args[] = $vv_dict_id;
  $this->Html->addCrumb($vv_dict_name, $args);
  
  $this->Html->addCrumb(_txt('ct.dictionary_entries.pl'));
  
  // Add page title
  $params = array();
  $params['title'] = $title_for_layout;

  // Add top links
  $params['topLinks'] = array();

  if($permissions['upload']) {
    $params['topLinks'][] = $this->Html->link(
      _txt('op.upload'),
      array(
        'controller' => 'dictionary_entries',
        'action' => 'upload',
        'dictionary' => $this->request->params['named']['dictionary']
      ),
      array('class' => 'notebutton')
    );
  }
  
  if($permissions['populate']) {
    $params['topLinks'][] = $this->Html->link(
      _txt('op.populate'),
      array(
        'controller' => 'dictionary_entries',
        'action' => 'populate',
        'dictionary' => $this->request->params['named']['dictionary']
      ),
      array('class' => 'runbutton')
    );
  }
  
  if($permissions['add']) {
    $params['topLinks'][] = $this->Html->link(
      _txt('op.add.new', array(_txt('ct.dictionary_entries.1'))),
      array(
        'controller' => 'dictionary_entries',
        'action' => 'add',
        'dictionary' => $this->request->params['named']['dictionary']
      ),
      array('class' => 'addbutton')
    );
  }

  print $this->element("pageTitleAndButtons", $params);
?>

<div class="table-container">
  <table id="dictionary_entries">
    <thead>
      <tr>
        <th><?php print $this->Paginator->sort('value', _txt('fd.value')); ?></th>
        <th><?php print $this->Paginator->sort('code', _txt('fd.code')); ?></th>
        <th><?php print $this->Paginator->sort('ordr', _txt('fd.ordr')); ?></th>
        <th><?php print _txt('fd.actions'); ?></th>
      </tr>
    </thead>

    <tbody>
      <?php $i = 0; ?>
      <?php foreach ($dictionary_entries as $c): ?>
      <tr class="line<?php print ($i % 2)+1; ?>">
        <td>
          <?php
            print $this->Html->link(
              $c['DictionaryEntry']['value'],
              array(
                'controller' => 'dictionary_entries',
                'action' => 'edit',
                $c['DictionaryEntry']['id']
              )
            );
          ?>
        </td>
        <td>
          <?php
            if(!empty($c['DictionaryEntry']['code'])) {
              print filter_var($c['DictionaryEntry']['code'], FILTER_SANITIZE_SPECIAL_CHARS);
            }
          ?>
        </td>
        <td>
          <?php
            if(!empty($c['DictionaryEntry']['ordr'])) {
              print filter_var($c['DictionaryEntry']['ordr'], FILTER_SANITIZE_SPECIAL_CHARS);
            }
          ?>
        </td>
        <td>
          <?php
            if($permissions['edit']) {
              print $this->Html->link(
                _txt('op.edit'),
                array(
                  'controller' => 'dictionary_entries',
                  'action' => 'edit',
                  $c['DictionaryEntry']['id']
                ),
                array('class' => 'editbutton')
              ) . "\n";
            }
            
            if($permissions['delete']) {
              print '<button type="button" class="deletebutton" title="' . _txt('op.delete')
                . '" onclick="javascript:js_confirm_generic(\''
                . _txt('js.remove') . '\',\''    // dialog body text
                . $this->Html->url(              // dialog confirm URL
                  array(
                    'controller' => 'dictionary_entries',
                    'action' => 'delete',
                    $c['DictionaryEntry']['id']
                  )
                ) . '\',\''
                . _txt('op.remove') . '\',\''    // dialog confirm button
                . _txt('op.cancel') . '\',\''    // dialog cancel button
                . _txt('op.remove') . '\',[\''   // dialog title
                . filter_var(_jtxt($c['DictionaryEntry']['value']),FILTER_SANITIZE_STRING)  // dialog body text replacement strings
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
