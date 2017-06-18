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

  if($permissions['assignall']) {
    $params['topLinks'][] = '<a class="provisionbutton" title="' .
      _txt('op.id.auto.all') .
      '" onclick="javascript:js_confirm_autogenerate(\'' .
      $this->Html->url(array(
          'controller' => 'identifiers',
          'action' => 'assign.json')
      ) . '\');">' . _txt('op.id.auto.all') .
      "</a>\n";
  }

  print $this->element("pageTitleAndButtons", $params);

?>
<script type="text/javascript">
  // This is based in large part on CoProvisioningTargets/index.ctp

  // The controller passes a list of IDs, which we need to convert to a javascript array.
  // This won't scale to superhuge lists, but should be good enough for typical CO populations.
  var ids = [
    <?php
      foreach(array_keys($vv_co_people) as $id) {
        print $id . ',';
      }
    ?>
  ];

  // Have we been interrupted by the user?
  var canceled = 0;

  function js_confirm_autogenerate(targetUrl) {
    // Prep confirmation dialog
    $("#autogenerate-dialog").dialog("option",
      "buttons",
      [ { text: "<?php print _txt('op.cancel'); ?>", click: function() { $(this).dialog("close"); } },
        { text: "<?php print _txt('op.id.auto'); ?>", click: function() {
          $(this).dialog("close");
          js_request_autogenerate(targetUrl);
        } }
      ] );

    // Open the dialog to confirm autogenerate
    $("#autogenerate-dialog").dialog("open");
  }

  function js_execute_autogenerate(index, targetUrl) {
    if(!canceled && index < ids.length) {
      var id = ids[index];

      // Update the progress bar
      $("#autogenerate-progressbar").progressbar("option", "value", index);
      
      var jsondoc = '{"RequestType":"Identifiers",' +
                     '"Version":"1.0",' +
                     '"Identifiers":[{' +
                      '"Version":"1.0",' +
                      '"Person":{"Type":"CO","Id":"' + id + '"}' +
                      '}]}';

      // Initiate the autogenerate request
      
      // We need to set the contentType to json (which, as an aside, PHP does not
      // automatically parse -- see ApiComponent::parseRestRequestDocument), so we
      // need to use ajax() instead of post().
      // var jqxhr = $.post(targetUrl, jsondoc);

      var jqxhr = $.ajax({
        type: "POST",
        url: targetUrl,
   			contentType: "application/json",
        data: jsondoc
        //JSON.stringify(reqdoc)
      });

      // On success, fire the next request
      jqxhr.done(function(data, textStatus, jqXHR) {
        js_execute_autogenerate(index+1, targetUrl);
      });

      jqxhr.fail(function(jqXHR, textStatus, errorThrown) {
        // Note we're getting 200 here but it's actually a success (perhaps because no body returned; CO-984)
        if(jqXHR.status != "200") {
          $("#progressbar-dialog").dialog("close");
          $("#result-dialog").html("<p><?php print _txt('er.ia'); ?>" + " " + errorThrown + " (" +  jqXHR.status + ")</p>");
          // Configure buttons so user can elect to continue or cancel
          $("#result-dialog").dialog("option", "buttons", {
            "<?php print _txt('op.cancel'); ?>": function() {
              $(this).dialog("close");
            },
            "<?php print _txt('op.cont'); ?>": function() {
              $(this).dialog("close");
              $("#progressbar-dialog").dialog("open");
              js_execute_autogenerate(index+1, targetUrl);
            }
          });
          $("#result-dialog").dialog("open");
        } else {
          js_execute_autogenerate(index+1, targetUrl);
        }
      });
    } else {
      // We're done, close progress bar
      $("#autogenerate-progressbar").progressbar("option", "value", index);
      $("#progressbar-dialog").dialog("close");
      if(!canceled) {
        // Make sure result dialog has only one button, and reset the text
        $("#result-dialog").dialog("option", "buttons", {
          "<?php print _txt('op.ok'); ?>": function() {
            $(this).dialog("close");
          },
        });
        $("#result-dialog").html("<p><?php print _txt('rs.ia.ok'); ?></p>");
        $("#result-dialog").dialog("open");
      }

      // Reset in case user tries again
      canceled = 0;
      $("#autogenerate-progressbar").progressbar("option", "value", 0);
    }
  }

  function js_request_autogenerate(targetUrl) {
    // Open the progress bar dialog
    $("#progressbar-dialog").dialog("open");

    // Fire off the first request
    js_execute_autogenerate(0, targetUrl);
  }

  $(function() {
    // Define progressbar
    $("#autogenerate-progressbar").progressbar({
      value: 0,
      max: ids.length
    });

    // Progress bar dialog
    $("#progressbar-dialog").dialog({
      create: function() {
        // We want to know when a user cancels the operation in progress, which
        // we can't use beforeClose for since that will fire when the dialog
        // closes for any reason. Based on http://stackoverflow.com/questions/7924152
        $(this).closest('div.ui-dialog')
          .find('button.ui-dialog-titlebar-close')
          .click(function(e) {
            canceled = 1;
          });
      },
      autoOpen: false,
      modal: true,
      show: {
        effect: "fade"
      },
      hide: {
        effect: "fade"
      }
    });

    // Autogenerate dialog
    $("#autogenerate-dialog").dialog({
      autoOpen: false,
      buttons: {
        "<?php print _txt('op.cancel'); ?>": function() {
          $(this).dialog("close");
        },
        "<?php print _txt('op.id.auto'); ?>": function() {
          $(this).dialog("close");
          js_progressbar_dialog();
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

    // Result dialog
    $("#result-dialog").dialog({
      autoOpen: false,
      buttons: {
        "<?php print _txt('op.ok'); ?>": function() {
          $(this).dialog("close");
        },
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
  <table id="cous">
    <thead>
    <tr>
      <th><?php print $this->Paginator->sort('description', _txt('fd.desc')); ?></th>
      <th><?php print $this->Paginator->sort('identifier_type', _txt('fd.type')); ?></th>
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

<div id="progressbar-dialog" title="<?php print _txt('op.id.auto'); ?>">
  <p><?php print _txt('op.id.auto.wait'); ?></p>
  <div id="autogenerate-progressbar"></div>
</div>

<div id="autogenerate-dialog" title="<?php print _txt('op.id.auto'); ?>">
  <p><?php print _txt('op.id.auto.all.confirm', array(count($vv_co_people))); ?></p>
</div>

<div id="result-dialog" title="<?php print _txt('op.id.auto'); ?>">
  <p><?php print _txt('rs.ia.ok'); ?></p>
</div>