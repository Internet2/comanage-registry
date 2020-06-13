<?php
/**
 * COmanage Registry CO Identifier Assignment Index View
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
 * @since         COmanage Registry v0.6
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

  // Add breadcrumbs
  print $this->element("coCrumb");
  $this->Html->addCrumb(_txt('ct.co_identifier_assignments.pl'));

  // Add page title
  $params = array();
  $params['title'] = $title_for_layout;

  // Add top links
  $params['topLinks'] = array();

  if($permissions['add']) {
    $params['topLinks'][] = $this->Html->link(
      _txt('op.add-a',array(_txt('ct.co_identifier_assignments.1'))),
      array(
        'controller' => 'co_identifier_assignments',
        'action' => 'add',
        'co' => $cur_co['Co']['id']
      ),
      array('class' => 'addbutton')
    );
  }

  if($permissions['order']) {
    // Reorder button
    $params['topLinks'][] = $this->Html->link(
      _txt('op.order-a', array(_txt('ct.co_identifier_assignments.pl'))),
      array(
        'controller' => 'co_identifier_assignments',
        'action'     => 'order',
        'co'         => $cur_co['Co']['id'],
        'direction'  => 'asc',
        'sort'       => 'ordr'
      ),
      array('class' => 'movebutton')
    );
  }
  
  if($permissions['assignall']) {
    $params['topLinks'][] = '<a class="provisionbutton" title="' .
      _txt('op.id.auto.all') .
      '" onclick="javascript:js_confirm_autogenerate(\'' .
      $this->Html->url(array(
          'controller' => 'co_identifier_assignments',
          'action' => 'assignall',
          'co' => $cur_co['Co']['id'])
      ) . '\');">' . _txt('op.id.auto.all') .
      "</a>\n";
  }

  print $this->element("pageTitleAndButtons", $params);

?>
<script type="text/javascript">
  function js_confirm_autogenerate(targetUrl) {
    // Prep confirmation dialog
    $("#autogenerate-dialog").dialog("option",
      "buttons",
      [ { text: "<?php print _txt('op.cancel'); ?>", click: function() { $(this).dialog("close"); } },
        { text: "<?php print _txt('op.id.auto'); ?>", click: function() {
          $(this).dialog("close");
          window.location.href = targetUrl;
        } }
      ] );

    // Open the dialog to confirm autogenerate
    $("#autogenerate-dialog").dialog("open");
  }

  $(function() {
    // Autogenerate dialog
    $("#autogenerate-dialog").dialog({
      autoOpen: false,
      buttons: {
        "<?php print _txt('op.cancel'); ?>": function() {
          $(this).dialog("close");
        },
        "<?php print _txt('op.id.auto'); ?>": function() {
          $(this).dialog("close");
        }
      },
      modal: true,
      show: {
        effect: "fade"
      },
      hide: {
        effect: "fade"
      }
    });
  });
</script>

<div class="table-container">
  <table id="co_identifier_assignments">
    <thead>
    <tr>
      <th><?php print $this->Paginator->sort('description', _txt('fd.desc')); ?></th>
      <th><?php print $this->Paginator->sort('identifier_type', _txt('fd.type')); ?></th>
      <th><?php print $this->Paginator->sort('ordr', _txt('fd.order')); ?></th>
      <th><?php print $this->Paginator->sort('context', _txt('fd.ia.context')); ?></th>
      <th><?php print _txt('fd.actions'); ?></th>
    </tr>
    </thead>

    <tbody>
    <?php $i = 0; ?>
    <?php foreach ($co_identifier_assignments as $c): ?>
      <tr class="line<?php print ($i % 2)+1; ?>">
        <td>
          <?php
          print $this->Html->link($c['CoIdentifierAssignment']['description'],
            array('controller' => 'co_identifier_assignments',
              'action' => ($permissions['edit'] ? 'edit' : ($permissions['view'] ? 'view' : '')), $c['CoIdentifierAssignment']['id'], 'co' => $cur_co['Co']['id']));
          ?>
        </td>
        <td><?php print filter_var($c['CoIdentifierAssignment']['identifier_type'],FILTER_SANITIZE_SPECIAL_CHARS); ?></td>
        <td><?php print $c['CoIdentifierAssignment']['ordr']; ?></td>
        <td><?php print _txt('en.ia.context', null, $c['CoIdentifierAssignment']['context']); ?></td>
        <td>
          <?php
          if($permissions['edit']) {
            print $this->Html->link(_txt('op.edit'),
                array('controller' => 'co_identifier_assignments', 'action' => 'edit', $c['CoIdentifierAssignment']['id'], 'co' => $cur_co['Co']['id']),
                array('class' => 'editbutton')) . "\n";
          }
          if($permissions['delete']) {
            print '<button type="button" class="deletebutton" title="' . _txt('op.delete')
              . '" onclick="javascript:js_confirm_generic(\''
              . _txt('js.remove') . '\',\''    // dialog body text
              . $this->Html->url(              // dialog confirm URL
                array(
                  'controller' => 'co_identifier_assignments',
                  'action' => 'delete',
                  $c['CoIdentifierAssignment']['id'],
                  'co' => $cur_co['Co']['id']
                )
              ) . '\',\''
              . _txt('op.remove') . '\',\''    // dialog confirm button
              . _txt('op.cancel') . '\',\''    // dialog cancel button
              . _txt('op.remove') . '\',[\''   // dialog title
              . filter_var(_jtxt($c['CoIdentifierAssignment']['description']),FILTER_SANITIZE_STRING)  // dialog body text replacement strings
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

<?php print $this->element("pagination"); ?>

<div id="autogenerate-dialog" title="<?php print _txt('op.id.auto'); ?>">
  <p><?php print _txt('op.id.auto.all.confirm'); ?></p>
</div>
