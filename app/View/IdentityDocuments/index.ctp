<?php
/**
 * COmanage Registry IdentityDocuments Index View
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
  
  $this->Html->addCrumb(_txt('ct.identity_documents.pl'));

  // Add page title
  $params = array();
  $params['title'] = _txt('ct.identity_documents.pl');

  // Add top links
  $params['topLinks'] = array();

  if($permissions['add']) {
    $args = array();
    $args['controller'] = 'identity_documents';
    $args['action'] = 'add';
    $args['copersonid'] = filter_var($this->request->params['named']['copersonid'],FILTER_SANITIZE_SPECIAL_CHARS);
    
    $params['topLinks'][] = $this->Html->link(
      _txt('op.add-a', array(_txt('ct.identity_documents.1'))),
      $args,
      array('class' => 'addbutton')
    );
  }

  print $this->element("pageTitleAndButtons", $params);
?>

<div class="table-container">
  <table id="identity_documents">
    <thead>
      <tr>
        <th><?php print $this->Paginator->sort('document_type', _txt('fd.identity_documents.document_type')); ?></th>
        <th><?php print $this->Paginator->sort('issuing_authority', _txt('fd.identity_documents.issuing_authority')); ?></th>
        <th><?php print _txt('fd.action'); ?></th>
      </tr>
    </thead>

    <tbody>
      <?php $i = 0; ?>
      <?php foreach($identity_documents as $d): ?>
      <tr class="line<?php print ($i % 2)+1; ?>">
        <td>
          <?php
            if(!empty($d['IdentityDocument']['document_type'])) {
              print $this->Html->link(
                _txt('en.id.type', null, $d['IdentityDocument']['document_type']),
                array(
                  'controller' => 'identity_documents',
                  'action' => ($permissions['edit'] ? 'edit' : 'view'),
                  $d['IdentityDocument']['id']
                )
              );
              
              if(!empty($d['IdentityDocument']['document_subtype'])) {
                print " (" . filter_var($d['IdentityDocument']['document_subtype'],FILTER_SANITIZE_SPECIAL_CHARS) . ")";
              }
            }
          ?>
        </td>
        <td><?php print filter_var($d['IdentityDocument']['issuing_authority'],FILTER_SANITIZE_SPECIAL_CHARS) . "\n";?></td>
        <td>
          <?php
            if($permissions['edit']) {
              print $this->Html->link(
                  _txt('op.edit'),
                  array(
                    'controller' => 'identity_documents',
                    'action' => 'edit',
                    $d['IdentityDocument']['id']
                  ),
                  array('class' => 'editbutton')) . "\n";
            }
            
            if($permissions['delete']) {
              print '<button type="button" class="deletebutton" title="' . _txt('op.delete')
                . '" onclick="javascript:js_confirm_generic(\''
                . _txt('js.remove') . '\',\''    // dialog body text
                . $this->Html->url(              // dialog confirm URL
                  array(
                    'controller' => 'identity_documents',
                    'action' => 'delete',
                    $d['IdentityDocument']['id']
                  )
                ) . '\',\''
                . _txt('op.remove') . '\',\''    // dialog confirm button
                . _txt('op.cancel') . '\',\''    // dialog cancel button
                . _txt('op.remove') . '\',[\''   // dialog title
                . _txt('en.id.type', null, $d['IdentityDocument']['document_type'])  // dialog body text replacement strings
                . '\']);">'
                . _txt('op.delete')
                . '</button>';
            }
          ?>
        </td>
      </tr>
      <?php $i++; ?>
      <?php endforeach; ?>
    </tbody>

  </table>
</div>

<?php print $this->element("pagination");