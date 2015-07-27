<!--
/**
 * COmanage Registry CO Identifier Assignment Index View
 *
 * Copyright (C) 2012-14 University Corporation for Advanced Internet Development, Inc.
 * 
 * Licensed under the Apache License, Version 2.0 (the "License"); you may not use this file except in compliance with
 * the License. You may obtain a copy of the License at
 * 
 * http://www.apache.org/licenses/LICENSE-2.0
 * 
 * Unless required by applicable law or agreed to in writing, software distributed under
 * the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY
 * KIND, either express or implied. See the License for the specific language governing
 * permissions and limitations under the License.
 *
 * @copyright     Copyright (C) 2012-14 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.6
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */-->
<?php
  $params = array('title' => $title_for_layout);
  print $this->element("pageTitle", $params);

  // Add breadcrumbs
  print $this->element("coCrumb");
  $this->Html->addCrumb(_txt('ct.co_identifier_assignments.pl'));

  if($permissions['add'])
    print $this->Html->link(_txt('op.add-a',array(_txt('ct.co_identifier_assignments.1'))),
                            array('controller' => 'co_identifier_assignments', 'action' => 'add', 'co' => $cur_co['Co']['id']),
                            array('class' => 'addbutton')) . "\n";
    
  if($permissions['assignall']) {
    print '<a class="provisionbutton"
              title="' . _txt('op.id.auto.all') . '"
              onclick="javascript:js_confirm_autogenerate(\'' .
                                                       $this->Html->url(array('controller' => 'identifiers',
                                                                                              'action' => 'assign.json'))
                                                      . '\');">'
          . _txt('op.id.auto.all') . "</a>\n" . '
    <br />
    <br />
    ';
  }
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
      
      // Initiate the autogenerate request
      var jqxhr = $.post(targetUrl,
                         '{ "RequestType":"Identifiers",\
                             "Version":"1.0",\
                             "Identifiers":[{\
                              "Version":"1.0",\
                              "Person":{"Type":"CO","Id":"' + id + '"}\
                            }]\
                          }');
      
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

<table id="cous" class="ui-widget">
  <thead>
    <tr class="ui-widget-header">
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
      <td><?php print Sanitize::html($c['CoIdentifierAssignment']['identifier_type']); ?></td>
      <td>
        <?php
          if($permissions['edit'])
            print $this->Html->link(_txt('op.edit'),
                                    array('controller' => 'co_identifier_assignments', 'action' => 'edit', $c['CoIdentifierAssignment']['id'], 'co' => $cur_co['Co']['id']),
                                    array('class' => 'editbutton')) . "\n";
          
          if($permissions['delete'])
            print '<button class="deletebutton" title="' . _txt('op.delete') . '" onclick="javascript:js_confirm_delete(\'' . _jtxt(Sanitize::html($c['CoIdentifierAssignment']['identifier_type'])) . '\', \'' . $this->Html->url(array('controller' => 'co_identifier_assignments', 'action' => 'delete', $c['CoIdentifierAssignment']['id'], 'co' => $cur_co['Co']['id'])) . '\')";>' . _txt('op.delete') . "</button>";
        ?>
      </td>
    </tr>
    <?php $i++; ?>
    <?php endforeach; ?>
  </tbody>
  
  <tfoot>
    <tr class="ui-widget-header">
      <th colspan="3">
        <?php print $this->Paginator->numbers(); ?>
      </th>
    </tr>
  </tfoot>
</table>

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