<?php
/**
 * COmanage Registry CO Index View
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
 * @since         COmanage Registry v0.1
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

  // Add breadcrumbs
  $this->Html->addCrumb(_txt('ct.cos.pl'));

  // Add page title
  $params = array();
  $params['title'] = $title_for_layout;

  // Add top links
  $params['topLinks'] = array();

  if($permissions['add']) {
    $params['topLinks'][] = $this->Html->link(
      _txt('op.add-a', array(_txt('ct.cos.1'))),
      array(
        'controller' => 'cos',
        'action' => 'add'
      ),
      array('class' => 'addbutton spin')
    );
  }

  // Extract status of all COs to a list
  $cos_status = Hash::extract($cos, '{n}.Co.status');

  print $this->element("pageTitleAndButtons", $params);

?>


  <div class="table-container">
  <table id="cos">
    <thead>
      <tr>
        <th><?php print $this->Paginator->sort('name', _txt('fd.name')); ?></th>
        <th><?php print $this->Paginator->sort('description', _txt('fd.desc')); ?></th>
        <th><?php print $this->Paginator->sort('status', _txt('fd.status')); ?></th>
        <th><?php print _txt('fd.actions'); ?></th>
      </tr>
    </thead>

    <tbody>
      <?php $i = 0; ?>
      <?php foreach ($cos as $c): ?>
      <?php
      $statusClass = "";
        if($c['Co']['status'] == TemplateableStatusEnum::InTrash) {
          // Style status for Pending Removal
          $statusClass = " warn-level-a"; // reddish-pink
        } elseif($c['Co']['status'] == TemplateableStatusEnum::Suspended) {
          // Style status for Suspended
          $statusClass = " warn-level-b"; // yellowish
        }
      ?>
      <tr class="line<?php print (($i % 2)+1) . $statusClass;?>">
        <td>
          <?php
            print $this->Html->link(
              $c['Co']['name'],
              array(
                'controller' => 'cos',
                'action' => (($permissions['edit']
                              && $c['Co']['name'] != DEF_COMANAGE_CO_NAME
                              && $c['Co']['status'] !== TemplateableStatusEnum::InTrash)
                             ? 'edit'
                             : ($permissions['view'] ? 'view' : '')),
                $c['Co']['id']
              )
            );
          ?>
        </td>
        <td><?php print filter_var($c['Co']['description'],FILTER_SANITIZE_SPECIAL_CHARS); ?></td>
        <td>
          <?php
          print _txt('en.status.disposable', null, $c['Co']['status']);
          if($c['Co']['status'] == TemplateableStatusEnum::InTrash) {
            print '<span class="required ml-1">*</span>';
          }
          ?>
        </td>
        <td>
          <?php
            if($c['Co']['name'] != DEF_COMANAGE_CO_NAME) {
              if($c['Co']['status'] === TemplateableStatusEnum::InTrash) {
                if($permissions['edit']) {
                  print $this->Html->link(
                      _txt('op.restore'),
                      array(
                        'controller' => 'cos',
                        'action' => 'restore',
                        $c['Co']['id']
                      ),
                      array('class' => 'restorebutton spin')
                    ) . PHP_EOL;
                }
              } else {
                if($permissions['edit']) {
                  print $this->Html->link(
                      _txt('op.edit'),
                      array(
                        'controller' => 'cos',
                        'action' => 'edit',
                        $c['Co']['id']
                      ),
                      array('class' => 'editbutton spin')
                    ) . PHP_EOL;
                }

                // XXX should this (and CoEnrollmentFlow::duplicate) use js_confirm_generic?
                // XXX Should this become a background job as well?
                if($permissions['duplicate']) {
                  print $this->Html->link(
                      _txt('op.dupe'),
                      array(
                        'controller' => 'cos',
                        'action' => 'duplicate',
                        $c['Co']['id']
                      ),
                      array('class' => 'copybutton spin')
                    ) . PHP_EOL;
                }

                if($permissions['delete']) {
                  print '<button type="button" class="trashbutton" title="' . _txt('op.trash')
                    . '" onclick="javascript:js_confirm_generic(\''
                    . _txt('js.intrash') . '\',\''   // dialog body text
                    . $this->Html->url(              // dialog confirm URL
                      array(
                        'controller' => 'cos',
                        'action' => 'deleteasync',
                        $c['Co']['id']
                      )
                    ) . '\',\''
                    . _txt('op.move') . '\',\''          // dialog confirm button
                    . _txt('op.cancel') . '\',\''        // dialog cancel button
                    . _txt('js.move.trash') . '\',[\''   // dialog title
                    . filter_var(_jtxt($c['Co']['name']),FILTER_SANITIZE_STRING)  // dialog body text replacement strings
                    . '\']);">'
                    . _txt('op.trash')
                    . '</button>';
                }
              } // in Trash
            } // Platform CO
          ?>
          <?php ; ?>
        </td>
      </tr>
      <?php $i++; ?>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php  print $this->element("pagination"); ?>
<!-- Render only if at least one CO is in Pending Removal-->
<?php if(in_array(TemplateableStatusEnum::InTrash, $cos_status)): ?>
<span class="d-block required mt-2">
  <?php print _txt('fd.tobe.deleted'); ?>
</span>
<?php endif; ?>
