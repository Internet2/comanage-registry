<?php
/**
 * COmanage Registry CO NSF Demographic Index View
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
 * @since         COmanage Registry v0.3
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

  // Add page title
  $params = array();
  $params['title'] = $title_for_layout;

  // Add top links
  $params['topLinks'] = array();

  if($permissions['add']) {
    $params['topLinks'][] = $this->Html->link(
      _txt('op.add-a', array(_txt('ct.co_nsf_demographics.1'))),
      array(
        'controller' => 'co_nsf_demographics',
        'action' => 'add'
      ),
      array('class' => 'addbutton')
    );
  }

  print $this->element("pageTitleAndButtons", $params);

  // Globals
  global $cm_lang, $cm_texts;
?>

<table id="co_nsf_demographics" class="ui-widget">
  <thead>
    <tr class="ui-widget-header">
      <th><?php print $this->Paginator->sort('gender', _txt('fd.de.gender')); ?></th>
      <th><?php print $this->Paginator->sort('citizenship', _txt('fd.de.citizen')); ?></th>
      <th><?php print $this->Paginator->sort('ethnicity', _txt('fd.de.ethnic')); ?></th>
      <th><?php print $this->Paginator->sort('race', _txt('fd.de.race')); ?></th>
      <th><?php print $this->Paginator->sort('disability', _txt('fd.de.disab')); ?></th>
      <th><?php print _txt('fd.actions'); ?></th>
    </tr>
  </thead>
  <tbody>
    <?php $i = 0; ?>
    <?php foreach ($co_nsf_demographics as $c): ?>
    <tr class="line<?php print ($i % 2)+1; ?>">
      <td><?php print $cm_texts[ $cm_lang ]['en.nsf.gender'][ $c['CoNsfDemographic']['gender'] ]; ?></td>
      <td><?php print $cm_texts[ $cm_lang ]['en.nsf.citizen'][ $c['CoNsfDemographic']['citizenship'] ]; ?></td>
      <td><?php print $cm_texts[ $cm_lang ]['en.nsf.ethnic'][ $c['CoNsfDemographic']['ethnicity'] ]; ?></td>
      <td>
        <?php 
          $counter = 0;
          foreach($c['CoNsfDemographic']['race'] as $demo)
          {
            if($counter > 0)
              print "; <br>";
            print filter_var($demo,FILTER_SANITIZE_SPECIAL_CHARS);
            $counter++;
          }
        ?>
      </td>
      <td>
        <?php 
          $counter = 0;
          foreach($c['CoNsfDemographic']['disability'] as $demo)
          {
            if($counter > 0)
              print "; <br>";
            print filter_var($demo,FILTER_SANITIZE_SPECIAL_CHARS);
            $counter++;
          }
        ?>
      </td>
      <td>
        <?php
          if($permissions['edit'])
          {
            $args = array('controller' => 'co_nsf_demographics',
                          'action' => 'edit',
                          $c['CoNsfDemographic']['id']
                         );
            $classArgs = array('class' => 'editbutton');
            print $this->Html->link(_txt('op.edit'),
                                    $args,
                                    $classArgs) . "\n";
          }

          if($permissions['delete'])
          {
            $args = array('controller' => 'co_nsf_demographics',
                          'action' => 'delete',
                          $c['CoNsfDemographic']['id']
                         );
            print '<button type="button" class="deletebutton" title="' . _txt('op.delete')
              . '" onclick="javascript:js_confirm_generic(\''
              . _txt('op.delete.consfdemographics') . '\',\'' // dialog body text
              . $this->Html->url($args) . '\',\''             // dialog confirm URL
              . _txt('op.remove') . '\',\''    // dialog confirm button
              . _txt('op.cancel') . '\',\''    // dialog cancel button
              . _txt('op.remove') . '\');">'   // dialog title
              . _txt('op.delete')
              . '</button>';
          }
        ?>
      </td>
    </tr>
    <?php $i++; ?>
    <?php endforeach; ?>
  </tbody>
  <tfoot>
    <tr class="ui-widget-header">
      <th colspan="7">
        <?php print $this->element("pagination"); ?>
      </th>
    </tr>
  </tfoot>
</table>
