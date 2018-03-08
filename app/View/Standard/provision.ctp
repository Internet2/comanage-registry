<?php
/**
 * COmanage Registry Standard Provision View
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
 * @since         COmanage Registry v0.8
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

  print $this->element("pageTitle", array('title' => filter_var($title_for_layout, FILTER_SANITIZE_SPECIAL_CHARS)));
  
  // REST API Provisioning Request Type
  $requestType = "Unknown";

  // Add breadcrumbs
  print $this->element("coCrumb");
  $args = array();
  $args['plugin'] = null;
  $args['controller'] = $this->request->params['controller'];
  $args['action'] = 'index';
  $args['co'] = $cur_co['Co']['id'];
  if(!empty($co_person)) {
    $this->Html->addCrumb(_txt('me.population'), $args);
    
    $args = array(
      'controller' => 'co_people',
      'action' => 'canvas',
      $co_person['CoPerson']['id']);
    $this->Html->addCrumb(generateCn($co_person['PrimaryName']), $args);
    
    $requestType = 'CoPersonProvisioning';
  } elseif(!empty($co_group)) {
    $this->Html->addCrumb(_txt('ct.co_groups.pl'), $args);
    
    $args = array(
      'controller' => 'co_groups',
      'action' => 'edit',
      $co_group['CoGroup']['id']
    );
    $this->Html->addCrumb($co_group['CoGroup']['name'], $args);
    
    $requestType = 'CoGroupProvisioning';
  } elseif(!empty($co_email_list)) {
    $this->Html->addCrumb(_txt('ct.co_email_lists.pl'), $args);
    
    $args = array(
      'controller' => 'co_email_lists',
      'action' => 'edit',
      $co_email_list['CoEmailList']['id']
    );
    $this->Html->addCrumb($co_email_list['CoEmailList']['name'], $args);
    
    $requestType = 'CoEmailListProvisioning';
  }
  $this->Html->addCrumb(_txt('op.prov.view'));
?>
<script type="text/javascript">
  <!-- /* JS specific to these fields */ -->
  
  function js_confirm_provision(targetUrl) {
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
  }
  
  function js_request_provisioning(targetUrl) {
    // Open the progress bar dialog
    $("#progressbar-dialog").dialog("open");
    
    // Initiate the provisioning request
    var jqxhr = $.post(targetUrl, '{ "RequestType":"<?php print $requestType; ?>","Version":"1.0","Synchronous":true }');
    
    jqxhr.done(function(data, textStatus, jqXHR) {
                 $("#progressbar-dialog").dialog("close");
                 $("#result-dialog").dialog("open");
               });
    
    jqxhr.fail(function(jqXHR, textStatus, errorThrown) {
                // Note we're getting 200 here but it's actually a success (perhaps because no body returned; CO-984)
                
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

<div class="table-container">
  <table id="provisioning_status">
    <thead>
      <tr>
        <th><?php print _txt('fd.desc'); ?></th>
        <th><?php print _txt('fd.status'); ?></th>
        <th><?php print _txt('fd.identifier.identifier'); ?></th>
        <th><?php print _txt('fd.timestamp'); ?></th>
        <th><?php print _txt('fd.actions'); ?></th>
      </tr>
    </thead>

    <tbody>
      <?php $i = 0; ?>
      <?php foreach ($co_provisioning_status as $c): ?>
      <tr class="line<?php print ($i % 2)+1; ?>">
        <td>
          <?php print filter_var($c['CoProvisioningTarget']['description'],FILTER_SANITIZE_SPECIAL_CHARS)
                . " (" . filter_var($c['CoProvisioningTarget']['plugin'],FILTER_SANITIZE_SPECIAL_CHARS) . ")"; ?>
        </td>
        <td>
          <?php
            print _txt('en.status.prov.target', null, ($c['status']['status']));

            if(!empty($c['status']['comment'])) {
              print ": " . filter_var($c['status']['comment'],FILTER_SANITIZE_SPECIAL_CHARS);
            }

            // Display a message if this record is not eligible to be provisioned
            // to this target

            if(!empty($c['CoProvisioningTarget']['provision_co_group_id'])) {
              if(!empty($co_person)
                 && !in_array($c['CoProvisioningTarget']['provision_co_group_id'],
                              Hash::extract($co_person, 'CoGroupMember.{n}.co_group_id'))) {
                print " (" . _txt('rs.prov.inel.grmem') . ")";
              } elseif(!empty($co_group)
                       && $co_group['CoGroup']['id'] != $c['CoProvisioningTarget']['provision_co_group_id']) {
                print " (" . _txt('rs.prov.inel.group') . ")";
              }
            }
            
            if(!empty($c['CoProvisioningTarget']['skip_org_identity_source_id'])) {
              if(!empty($co_person)
                 && in_array($c['CoProvisioningTarget']['skip_org_identity_source_id'],
                             Hash::extract($co_person, 'CoOrgIdentityLink.{n}.OrgIdentity.OrgIdentitySourceRecord.org_identity_source_id'))) {
                print " (" . _txt('rs.prov.inel.ois') . ")";
              }
            }
          ?>
        </td>
        <td>
          <?php
            if(!empty($co_person['Identifier'])) {
              // Use Cake's obscure Hash syntax to pull the associated identifier record
              $ptid = $c['CoProvisioningTarget']['id'];
              $id = Hash::extract($co_person['Identifier'], '{n}[co_provisioning_target_id='.$ptid.']');

              if(!empty($id)) {
                print $this->Html->link($id[0]['identifier'],
                                        array(
                                          'controller' => 'identifiers',
                                          'action'     => 'edit',
                                          $id[0]['id']
                                        ));
              }
            }
          ?>
        </td>
        <td>
          <?php
            if($c['status']['timestamp']) {
              print $this->Time->format($c['status']['timestamp'], "%c $vv_tz", false, $vv_tz);
            }
          ?>
        </td>
        <td>
          <?php
            $url = array(
              'controller' => 'co_provisioning_targets',
              'action'     => 'provision',
              $c['CoProvisioningTarget']['id']
            );

            if(!empty($co_person)) {
              $url['copersonid'] = $co_person['CoPerson']['id'] . ".json";
            } elseif(!empty($co_group)) {
              $url['cogroupid'] = $co_group['CoGroup']['id'] . ".json";
            } elseif(!empty($co_email_list)) {
              $url['coemaillistid'] = $co_email_list['CoEmailList']['id'] . ".json";
            }

            print '<a class="provisionbutton"
                      title="' . _txt('op.prov') . '"
                      onclick="javascript:js_confirm_provision(\'' .
                        $this->Html->url($url)
                      . '\');">' . _txt('op.prov') . "</a>\n";
          ?>
        </td>
      </tr>
      <?php $i++; ?>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

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
