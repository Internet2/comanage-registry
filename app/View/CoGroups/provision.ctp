<!--
/**
 * COmanage Registry CO Group Provision View
 *
 * Copyright (C) 2013 University Corporation for Advanced Internet Development, Inc.
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
 * @copyright     Copyright (C) 2013 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.8.2
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */
-->
<?php
// XXX this is basically the same as the version in CoPeople
  $params = array('title' => _txt('fd.prov.status.for', array($co_group['CoGroup']['name'])));
  print $this->element("pageTitle", $params);

  // Add breadcrumbs
  $args = array();
  $args['plugin'] = null;
  $args['controller'] = 'co_groups';
  $args['action'] = 'index';
  $args['co'] = $cur_co['Co']['id'];
  $this->Html->addCrumb(_txt('ct.co_groups.pl'), $args);
  $this->Html->addCrumb(_txt('fd.prov.status'));

?>
<script type="text/javascript">
  <!-- /* JS specific to these fields */ -->
  
  function js_confirm_provision(targetUrl) {
    // Update the OK button target
    $("#provision-dialog").dialog("option",
                                  "buttons",
                                  [ { text: "<?php print _txt('op.cancel'); ?>", click: function() { $(this).dialog("close"); } },
                                    { text: "<?php print _txt('op.prov'); ?>", click: function() {
                                      $(this).dialog("close");
                                      js_request_provisioning(targetUrl);
                                    } }
                                  ] );
    
    // Open the dialog to confirm provisioning
    $("#provision-dialog").dialog("open");
    
    $( ".selector" ).dialog( "option", "buttons", [ { text: "Ok", click: function() { $( this ).dialog( "close" ); } } ] );
  }
  
  function js_request_provisioning(targetUrl) {
    // Open the progress bar dialog
    // XXX should we trap beforeClose() and cancel in progress operation?
    $("#progressbar-dialog").dialog("open");
    
    // Initiate the provisioning request
// XXX remove co_person/provision (including documentation)
    var jqxhr = $.post(targetUrl, '{ "RequestType":"CoPersonProvisioning","Version":"1.0","Synchronous":true }');
    
    jqxhr.done(function(data, textStatus, jqXHR) {
                 $("#progressbar-dialog").dialog("close");
                 $("#result-dialog").dialog("open");
               });
    
    jqxhr.fail(function(jqXHR, textStatus, errorThrown) {
                // Note we're getting 200 here but it's actually a success (perhaps because no body returned)
                // XXX JIRA: return content or 204 No Content instead
                // XXX should grab error message from json body if possible
                
                $("#progressbar-dialog").dialog("close");
                
                if(jqXHR.status != "200") {
                  $("#result-dialog").html("<p><?php print _txt('er.prov'); ?>" + " " + errorThrown + " (" +  jqXHR.status + ")</p>");
                }
                
                $("#result-dialog").dialog("open");
               });
  }
  
  $(function() {
    // Define progressbar
    $("#provision-progressbar").progressbar({
      value: false
    });
    
    // Progress bar dialog
    $("#progressbar-dialog").dialog({
      autoOpen: false,
      modal: true,
      show: {
        effect: "fade"
      },
      hide: {
        effect: "fade"
      }
    });
    
    // Provisioning dialog
    $("#provision-dialog").dialog({
      autoOpen: false,
      buttons: {
        "<?php print _txt('op.cancel'); ?>": function() {
          $(this).dialog("close");
        },
        "<?php print _txt('op.prov'); ?>": function() {
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
          // Refresh the page after provisioning to get latest status
          // XXX this could ultimately be replaced by an AJAX query
          location.reload();
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

<table id="provisioning_status" class="ui-widget">
  <thead>
    <tr class="ui-widget-header">
      <th><?php print _txt('fd.desc'); ?></th>
      <th><?php print _txt('fd.status'); ?></th>
      <th><?php print _txt('fd.timestamp'); ?></th>
      <th><?php print _txt('fd.actions'); ?></th>
    </tr>
  </thead>
  
  <tbody>
    <?php $i = 0; ?>
    <?php foreach ($co_provisioning_status as $c): ?>
    <tr class="line<?php print ($i % 2)+1; ?>">
      <td>
        <?php print Sanitize::html($c['CoProvisioningTarget']['description'])
              . " (" . Sanitize::html($c['CoProvisioningTarget']['plugin']) . ")"; ?>
      </td>
      <td>
        <?php
          print _txt('en.status.prov.target', null, ($c['status']['status']));
          
          if(!empty($c['status']['comment'])) {
            print ": " . Sanitize::html($c['status']['comment']);
          }
        ?>
      </td>
      <td>
        <?php
          if($c['status']['timestamp']) {
            print $this->Time->nice($c['status']['timestamp']);
          }
        ?>
      </td>
      <td>
        <?php
          print '<a class="provisionbutton"
                    title="' . _txt('op.prov') . '"
                    onclick="javascript:js_confirm_provision(\'' .
                      $this->Html->url(array('controller' => 'co_provisioning_targets',
                                                             'action' => 'provision',
                                                             $c['CoProvisioningTarget']['id'],
                                                             'cogroupid' => $co_group['CoGroup']['id'] . ".json"))
                    . '\');">' . _txt('op.prov') . "</a>\n";
        ?>
      </td>
    </tr>
    <?php $i++; ?>
    <?php endforeach; ?>
  </tbody>
  
  <tfoot>
    <tr class="ui-widget-header">
      <th colspan="4">
      </th>
    </tr>
  </tfoot>
</table>

<div id="progressbar-dialog" title="<?php print _txt('op.prov'); ?>">
  <p><?php print _txt('op.prov.wait'); ?></p>
  <div id="provision-progressbar"></div>
</div>

<div id="provision-dialog" title="<?php print _txt('op.prov'); ?>">
  <p><?php print _txt('op.prov.confirm'); ?></p>
</div>

<div id="result-dialog" title="<?php print _txt('op.prov'); ?>">
  <p><?php print _txt('rs.prov.ok'); ?></p>
</div>
