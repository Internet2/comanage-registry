<?php
/**
 * COmanage Registry OrgIdentity Index View
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
 * @since         COmanage Registry v0.2
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

// Globals
global $cm_lang, $cm_texts;

// Add breadcrumbs
print $this->element("coCrumb");
$this->Html->addCrumb(_txt('ct.org_identities.pl'));

// Add page title
$params = array();
$params['title'] = _txt('ct.org_identities.pl');

// Add top links
$params['topLinks'] = array();

if($permissions['add']) {
  // add new organizational identity link
  $params['topLinks'][] = $this->Html->link(
    _txt('op.add.new', array(_txt('ct.org_identities.1'))),
    array(
      'controller' => 'org_identities',
      'action' => 'add',
      'co' => ($pool_org_identities ? false : $cur_co['Co']['id'])
    ),
    array('class' => 'addbutton')
  );
  
  if(!empty($vv_org_id_sources)) {
    $params['topLinks'][] = $this->Html->link(
      _txt('op.orgid.add.ois'),
      array(
        'controller' => 'org_identity_sources',
        'action' => 'select',
        'co' => ($pool_org_identities ? false : $cur_co['Co']['id'])
      ),
      array('class' => 'addbutton')
    );
  }
}

print $this->element("pageTitleAndButtons", $params);
?>

<div class="co-info-topbox">
  <em class="material-icons">info</em>
  <?php print _txt('in.orgidentities'); ?>
</div>

<?php // Load the top search bar
// Search Block
if(!empty($vv_search_fields)) {
  print $this->element('search', array('vv_search_fields' => $vv_search_fields));
}
// Alphabet Search quick access bar
if(!empty($vv_alphabet_search)) {
  print $this->element('alphabetSearch', array('vv_alphabet_search_config' => $vv_alphabet_search));
}
?>

<div class="table-container">
  <table id="org_identities">
    <thead>
    <tr>
      <th><?php print $this->Paginator->sort('PrimaryName.family', _txt('fd.name')); ?></th>
      <th><?php print $this->Paginator->sort('o', _txt('fd.o')); ?></th>
      <th><?php print $this->Paginator->sort('ou', _txt('fd.ou')); ?></th>
      <th><?php print $this->Paginator->sort('title', _txt('fd.title')); ?></th>
      <th><?php print $this->Paginator->sort('affiliation', _txt('fd.affiliation')); ?></th>
      <th class="thinActionButtonsCol"><?php print _txt('fd.actions'); ?></th>
    </tr>
    </thead>

    <tbody>
    <?php $i = 0; ?>
    <?php foreach ($org_identities as $p): ?>
      <tr class="line<?php print ($i % 2)+1; ?>">
        <td>
          <?php
          print $this->Html->link(
            generateCn($p['PrimaryName']),
            array(
              'controller' => 'org_identities',
              'action' => ($permissions['edit'] ? 'edit' : ($permissions['view'] ? 'view' : '')),
              $p['OrgIdentity']['id']
            )
          );
          ?>
        </td>
        <td><?php print filter_var($p['OrgIdentity']['o'],FILTER_SANITIZE_SPECIAL_CHARS); ?></td>
        <td><?php print filter_var($p['OrgIdentity']['ou'],FILTER_SANITIZE_SPECIAL_CHARS); ?></td>
        <td><?php print filter_var($p['OrgIdentity']['title'],FILTER_SANITIZE_SPECIAL_CHARS); ?></td>
        <td><?php if(!empty($p['OrgIdentity']['affiliation'])) print $vv_affiliation_types[ $p['OrgIdentity']['affiliation'] ]; ?></td>

        <td class="actions">
          <?php
            if(!empty($p['OrgIdentitySourceRecord']['id'])) {
              // This Org Identity came from an OIS and so can't be edited
              // (but it can be deleted)
              
              if($permissions['view']) {
                print $this->Html->link(
                  _txt('op.view'),
                  array(
                    'controller' => 'org_identities',
                    'action' => 'view',
                    $p['OrgIdentity']['id']
                  ),
                  array('class' => 'viewbutton spin')
                ) . "\n";
              }
            } else {
              if($permissions['edit']) {
                print $this->Html->link(
                  _txt('op.edit'),
                  array(
                    'controller' => 'org_identities',
                    'action' => 'edit',
                    $p['OrgIdentity']['id']
                  ),
                  array('class' => 'editbutton spin')
                ) . "\n";
              }
            }
            
            if($permissions['delete']) {
              print '<button type="button" class="deletebutton" title="' . _txt('op.delete')
                . '" onclick="javascript:js_confirm_generic(\''
                . _txt('js.remove') . '\',\''    // dialog body text
                . $this->Html->url(              // dialog confirm URL
                  array(
                    'controller' => 'org_identities',
                    'action' => 'delete',
                    $p['OrgIdentity']['id']
                  )
                ) . '\',\''
                . _txt('op.remove') . '\',\''    // dialog confirm button
                . _txt('op.cancel') . '\',\''    // dialog cancel button
                . _txt('op.remove') . '\',[\''   // dialog title
                . filter_var(_jtxt(generateCn($p['PrimaryName'])),FILTER_SANITIZE_STRING)  // dialog body text replacement strings
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
