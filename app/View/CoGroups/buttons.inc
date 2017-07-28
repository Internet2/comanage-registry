<!--
/**
 * COmanage Registry CO Group Fields
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
-->
<?php
// Determine if fields are editable or viewable
$dok = false;
$e = false;
$v = false;

if(($this->action == "add" && $permissions['add'])
  || ($this->action == "edit" && $permissions['edit'])
  || (($this->action == 'add' || $this->action == 'edit')
    && isset($co_groups[0]['CoGroup']['id'])
    && !empty($permissions['owner'])
    && in_array($co_groups[0]['CoGroup']['id'], $permissions['owner'])))
  $e = true;

if(($permissions['delete'])
  || (isset($co_groups[0]['CoGroup']['id'])
    && !empty($permissions['owner'])
    && in_array($co_groups[0]['CoGroup']['id'], $permissions['owner'])))
  $dok = true;

if(($this->action == "view" && $permissions['view'])
  || (isset($co_groups[0]['CoGroup']['id'])
    && !empty($permissions['member'])
    && in_array($co_groups[0]['CoGroup']['id'], $permissions['member']))
  || (isset($co_groups[0]['CoGroup']['open']) && $co_groups[0]['CoGroup']['open']))
  $v = true;

// We shouldn't get here if we don't have at least read permission, but check just in case
if(!$e && !$v)
  return(false);

// Add buttons
if($permissions['reconcile']) {
  // Reconcile read only groups
  
  $buttonUrl = array(
    'controller'    => 'co_groups',
    'action'        => 'reconcile',
    $co_groups[0]['CoGroup']['id'],
    'ext'           => 'json'
  );

  // Construct jQuery popup
  $onclickAction = "javascript:js_confirm_reconcile('"
                    . Router::url($buttonUrl) . "');
                    return false;";

  $params['topLinks'][] = $this->Html->link(
    _txt('op.gr.reconcile'),
    $buttonUrl,
    array(
      'class'   => 'reconcilebutton',
      'onclick' => $onclickAction
    )
  );
}

if(!empty($co_groups[0]['CoGroup']['id'])
   && !$co_groups[0]['CoGroup']['auto']) {
  // Manage group memberships
  $params['topLinks'][] = $this->Html->link(
    _txt('op.manage.grm'),
    array(
      'controller' => 'co_group_members',
      'action'     => 'select',
      'cogroup'    => $co_groups[0]['CoGroup']['id']
    ),
    array('class' => 'editbutton')
  );
}

if($permissions['provision'] && !empty($co_groups[0]['CoGroup']['id'])) {
  // Provisioning status
  $params['topLinks'][] = $this->Html->link(
    _txt('op.prov.view'),
    array(
      'controller' => 'co_groups',
      'action'     => 'provision',
      $co_groups[0]['CoGroup']['id']
    ),
    array('class' => 'runbutton')
  );
}

if($permissions['history'] && !empty($co_groups[0]['CoGroup']['id'])) {
  // View history
  $params['topLinks'][] = $this->Html->link(
    _txt('op.history'),
    array(
      'controller' => 'history_records',
      'action'     => 'index',
      'cogroupid'  => $co_groups[0]['CoGroup']['id']
    ),
    array('class' => 'notebutton')
  );
}

?>
<script type="text/javascript">
  /* JS specific to these buttons */

  function js_confirm_reconcile(targetUrl) {
    $("#reconcile-dialog").dialog("option",
                                  "buttons",
                                  [ { text: "<?php print _txt('op.cancel'); ?>", click: function() { $(this).dialog("close"); } },
                                    { text: "<?php print _txt('op.gr.reconcile'); ?>", click: function() {
                                      $(this).dialog("close");
                                      js_request_reconciliation(targetUrl);
                                    } }
                                  ] );

    // Open the dialog to confirm reconcilation
    $("#reconcile-dialog").dialog("open");
  }
  
  function js_request_reconciliation(targetUrl) {
    // Open the progress bar dialog
    $("#progressbar-dialog").dialog("open");

    // Initiate the reconciliation request
    var jqxhr = $.post(targetUrl, '{ "RequestType":"Reconciliation","Version":"1.0","Synchronous":true }');

    jqxhr.done(function(data, textStatus, jqXHR) {
      $("#progressbar-dialog").dialog("close");
      $("#result-dialog").dialog("open");
    });

    jqxhr.fail(function(jqXHR, textStatus, errorThrown) {
      // Note we're getting 200 here but it's actually a success (perhaps because no body returned; CO-984)

      $("#progressbar-dialog").dialog("close");

      if(jqXHR.status != "200") {
        $("#result-dialog").html("<p><?php print _txt('er.gr.reconcile'); ?>" + " " + errorThrown + " (" +  jqXHR.status + ")</p>");
      }

      $("#result-dialog").dialog("open");
    });
  }
  
  $(function() {
    // Define progressbar
    $("#reconcile-progressbar").progressbar({
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
    
    // Reconciliation dialog
    $("#reconcile-dialog").dialog({
      autoOpen: false,
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

<div id="progressbar-dialog" title="<?php print _txt('op.gr.reconcile'); ?>">
  <p><?php print _txt('op.gr.reconcile.wait'); ?></p>
  <div id="reconcile-progressbar"></div>
</div>

<div id="reconcile-dialog" title="<?php print _txt('op.gr.reconcile'); ?>">
  <p><?php print _txt('op.gr.reconcile.confirm'); ?></p>
</div>

<div id="result-dialog" title="<?php print _txt('op.gr.reconcile'); ?>">
  <p><?php print _txt('rs.gr.reconcile.ok'); ?></p>
</div>