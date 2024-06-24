<?php
/**
 * COmanage Registry CO Group Index View
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

  if($this->action == 'select') {
    // Add breadcrumbs
    print $this->element("coCrumb");
    $args = array(
      'controller' => 'co_people',
      'action' => 'canvas',
      $vv_co_person_id
    );
    $this->Html->addCrumb(_txt('ct.co_people.1'), $args);
    $this->Html->addCrumb(_txt('op.manage.grm'));

    // Add page title
    $params = array();
    $params['title'] = _txt('op.gr.memadd', array($name_for_title));

  } else {
    // Add breadcrumbs
    $this->Html->addCrumb(_txt('ct.co_groups.pl'));

    // Add page title
    $params = array();
    $params['title'] =  _txt('ct.co_groups.pl');

    // Add top links
    $params['topLinks'] = array();

    if($permissions['add']) {
      $params['topLinks'][] = $this->Html->link(
        _txt('op.add-a', array(_txt('ct.co_groups.1'))),
        array(
          'controller' => 'co_groups',
          'action' => 'add',
          'co' => $cur_co['Co']['id']
        ),
        array('class' => 'addbutton')
      );
    }

    if($permissions['reconcile']) {
      $url = array();
      $url['controller'] = 'co_groups';
      $url['action'] = 'reconcile';
      $url['ext'] = 'json';
      $url['?'] = array('coid' => $cur_co['Co']['id']);
      $jsLink = $this->Html->url($url);
      $options = array();
      $options['class'] = 'reconcilebutton';
      $options['onclick'] = "javascript:js_confirm_reconcile('$jsLink');";
      $params['topLinks'][] = $this->Html->tag('a',_txt('op.gr.reconcile.all'), $options);
    }

    if($permissions['groupmanage']) {
      $params['topLinks'][] = $this->Html->link(
        _txt('op.grm.manage'),
        array(
          'controller' => 'co_groups',
          'action' => 'select',
          'copersonid' => $this->Session->read('Auth.User.co_person_id'),
          'co' => $cur_co['Co']['id'],
          'search.member' => 't',
          'search.owner' => 't'
        ),
        array('class' => 'linkbutton')
      );
    }
  }

  print $this->element("pageTitleAndButtons", $params);
  
?>
<script type="text/javascript">
  // This is based in large part on CoProvisioningTargets/index.ctp
  
  // IDs of the members groups to be reconciled individually
  var ids = [ ];
  
  // Have we been interrupted by the user?
  var canceled = 0;
  
  function js_confirm_reconcile(targetUrl) {
    // Prep confirmation dialog
    $("#reconcile-dialog").dialog("option",
                                     "buttons",
                                     [ { text: "<?php print _txt('op.cancel'); ?>", click: function() { $(this).dialog("close"); } },
                                       { text: "<?php print _txt('op.gr.reconcile'); ?>", click: function() {
                                         $(this).dialog("close");
                                         js_request_reconcile(targetUrl);
                                       } }
                                     ] );
    
    // Open the dialog to confirm autogenerate
    $("#reconcile-dialog").dialog("open");
  }
  
  function js_execute_reconcile(index, baseUrl) {
    if(!canceled && index < ids.length) {
      var id = ids[index];
      
      // Update the progress bar
      $("#reconcile-progressbar").progressbar("option", "value", index);
      
      // Initiate the reconcile request
      var jqxhr = $.ajax({
        url: baseUrl + "/" + id + ".json",
        type: 'PUT'
        });
      
      // On success, fire the next request
      jqxhr.done(function(data, textStatus, jqXHR) {
                  js_execute_reconcile(index+1, baseUrl);
                });
      
      jqxhr.fail(function(jqXHR, textStatus, errorThrown) {
                  if(jqXHR.status != "200") {
                    $("#progressbar-dialog").dialog("close");
                    $("#result-dialog").html("<p><?php print _txt('er.gr.reconcile'); ?>" + " " + errorThrown + " (" +  jqXHR.status + ")</p>");
                    // Configure buttons so user can elect to continue or cancel
                    $("#result-dialog").dialog("option", "buttons", {
                      "<?php print _txt('op.cancel'); ?>": function() {
                        $(this).dialog("close");
                      },
                      "<?php print _txt('op.cont'); ?>": function() {
                        $(this).dialog("close");
                        $("#progressbar-dialog").dialog("open");
                        js_execute_reconcile(index+1, baseUrl);
                      }
                    });
                    $("#result-dialog").dialog("open");
                  } else {
                    js_execute_reconcile(index+1, baseUrl);
                  }
                });
    } else {
      // We're done, close progress bar
      $("#reconcile-progressbar").progressbar("option", "value", index);
      $("#progressbar-dialog").dialog("close");
      if(!canceled) {
        // Make sure result dialog has only one button, and reset the text
        $("#result-dialog").dialog("option", "buttons", {
          "<?php print _txt('op.ok'); ?>": function() {
            $(this).dialog("close");
          },
        });
        $("#result-dialog").html("<p><?php print _txt('rs.gr.reconcile.ok'); ?></p>");
        $("#result-dialog").dialog("open");
      }
      
      // Reset in case user tries again
      canceled = 0;
      $("#reconcile-progressbar").progressbar("option", "value", 0);
    }
  }
  
  function js_request_reconcile(targetUrl) {
    // Open the progress bar dialog
    $("#progressbar-dialog").dialog("open");
    
    // Reconcile existence of members groups
    var jqxhr = $.ajax({
      url: targetUrl,
      type: 'PUT'
      })
        .done(function(data, textStatus, jqXHR) {
          var groups = data["CoGroups"];
          for (var i = 0; i < groups.length; i++) {
            var group = groups[i];
            var id = group["Id"];
            ids.push(id);
            }
          // Reset max on progress bar now that ids is updated
          $("#reconcile-progressbar").progressbar("option", "max", ids.length);
          
          // Fire off the first group reconcile
          var baseUrl = targetUrl.split('?')[0];
          baseUrl = baseUrl.slice(0,-5);
          js_execute_reconcile(0, baseUrl);
          })
        .fail(function(jqXHR, textStatus, errorThrown) {
                  $("#progressbar-dialog").dialog("close");
                  $("#result-dialog").html("<p><?php print _txt('er.gr.reconcile'); ?>" + " " + errorThrown + " (" +  jqXHR.status + ")</p>");
                  // Configure buttons so user can cancel
                  $("#result-dialog").dialog("option", "buttons", {
                    "<?php print _txt('op.cancel'); ?>": function() {
                      $(this).dialog("close");
                    }
                  });
                  $("#result-dialog").dialog("open");
                });
  }
  
  $(function() {
    // Define progressbar, note that ids.length is 0 initially
    $("#reconcile-progressbar").progressbar({
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
    $("#reconcile-dialog").dialog({
      autoOpen: false,
      buttons: {
        "<?php print _txt('op.cancel'); ?>": function() {
          $(this).dialog("close");
        },
        "<?php print _txt('op.gr.reconcile'); ?>": function() {
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

<?php
  // Search Block
  if(!empty($vv_search_fields)) {
    print $this->element('search', array('vv_search_fields' => $vv_search_fields));
  }

  // Begin the select form (if in select mode)
  if($permissions['select'] && $this->action == 'select') {
    // We're using slightly the wrong permission here... edit group instead of add group member
    // (though they work out the same)
    print $this->Form->create('CoGroupMember',
        array('url' => array('action' => 'update'),
          'inputDefaults' => array('label' => false,
            'div' => false))) . "\n";
    // beforeFilter needs CO ID
    print $this->Form->hidden('CoGroupMember.co_id', array('default' => $cur_co['Co']['id'])) . "\n";
    // Group ID must be global for isAuthorized
    print $this->Form->hidden('CoGroupMember.co_person_id', array('default' => $vv_co_person_id)) . "\n";
  }
?>

<div class="table-container">
  <table id="co_groups">
    <thead>
      <tr>
        <th><?php print $this->Paginator->sort('name', _txt('fd.name')); ?></th>
        <th><?php print $this->Paginator->sort('description', _txt('fd.desc')); ?></th>
        <th><?php print $this->Paginator->sort('open', _txt('fd.open')); ?></th>
        <th><?php print $this->Paginator->sort('status', _txt('fd.status')); ?></th>
        <th><?php print _txt('fd.actions'); ?></th>
      </tr>
    </thead>

    <tbody>
      <?php
      $named_params = array_keys($this->request->params["named"]);
      $hasFilters = array_filter($named_params, function ($item) {
        return strpos($item, 'search.') !== false;
      });
      if(empty($co_groups) && !empty($hasFilters)): ?>
        <tr>
          <td colspan="5">
            <div class="co-info-topbox">
              <em class="material-icons">info</em>
              <div class="co-info-topbox-text">
                <?php print _txt('in.co_group.none_filters'); ?>
              </div>
            </div>
          </td>
        </tr>
      <?php elseif(empty($co_groups)):?>
        <tr>
          <td colspan="5">
            <div class="co-info-topbox">
              <em class="material-icons">info</em>
              <div class="co-info-topbox-text">
                <?php print _txt('in.co_group.none'); ?>
              </div>
            </div>
          </td>
        </tr>
      <?php else: ?>
        <?php $i = 0; ?>
        <?php foreach ($co_groups as $c): ?>
        <tr class="line<?php print ($i % 2)+1; ?>">
          <td>
            <?php
              // In addition to the usual permissions, an owner can edit and a member can view
              // Anyone can view an open group

              $d = $permissions['delete'];
              $e = $permissions['edit'];
              $v = $permissions['view'] || $c['CoGroup']['open'];

              if(!empty($permissions['owner'])
                 && in_array($c['CoGroup']['id'], $permissions['owner'])) {
                $d = true;
                $e = true;
              }

              if(!empty($permissions['member'])
                 && in_array($c['CoGroup']['id'], $permissions['member'])) {
                $v = true;
              }

              if(!empty($c['CoGroup']['group_type'])) {
                if($c['CoGroup']['group_type'] != GroupEnum::Standard) {
                  // Non-standard groups can't be deleted
                  $d = false;
                }

                if($c['CoGroup']['auto']) {
                  // Automatic groups can't be edited
                  $e = false;
                }
              }

              if($e) {
                print $this->Html->link($c['CoGroup']['name'],
                  array('controller' => 'co_groups',
                    'action' => 'edit',
                    $c['CoGroup']['id'],
                    'search.members:1',
                    'search.owners:1'
                  ));
              } else if($v) {
                print $this->Html->link($c['CoGroup']['name'],
                  array('controller' => 'co_groups',
                    'action'     => 'view',
                    $c['CoGroup']['id'],
                    'search.members:1',
                    'search.owners:1'
                  ));
              } else {
                print filter_var($c['CoGroup']['name'],FILTER_SANITIZE_SPECIAL_CHARS);
              }
            ?>
          </td>
          <td><?php print filter_var($c['CoGroup']['description'],FILTER_SANITIZE_SPECIAL_CHARS); ?></td>
          <td><?php print $c['CoGroup']['open'] ? _txt('fd.open') : _txt('fd.closed'); ?></td>
          <td>
            <?php
              print _txt('en.status', null, $c['CoGroup']['status']);
            ?>
          </td>
          <td class="actions">
            <?php
              if($this->action == 'select') {
                if($permissions['select']) {
                  print '<fieldset>';
                  print $this->Form->hidden('CoGroupMember.rows.'.$i.'.co_group_id',
                                            array('default' => $c['CoGroup']['id'])) . "\n";

                  // We toggle the disabled status of the checkbox based on a person's permissions.
                  // A CO(U) Admin can edit any membership or ownership.
                  // A group owner can edit any membership or ownership for that group.
                  // Anyone can add or remove themself from or two an open group.
                  // Membership in members groups is automatically managed and so we toggle
                  // disabled status for members groups.

                  $gmID = null;
                  $isMember = false;
                  $isOwner = false;

                  foreach($c['CoGroupMember'] as $cgm) {
                    // Walk the CoGroupMemberships for this CoGroup to find the target CO Person
                    if($cgm['co_person_id'] == $vv_co_person_id) {
                      $gmID = $cgm['id'];
                      $isMember = $cgm['member'];
                      $isOwner = $cgm['owner'];
                      break;
                    }
                  }

                  if($gmID) {
                    // Populate the cross reference
                    print $this->Form->hidden('CoGroupMember.rows.'.$i.'.id',
                                              array('default' => $gmID)) . "\n";
                  }

                  $disabled = false;
                  $disabledClass = '';
                  if(!($e || $permissions['selectany'] || $c['CoGroup']['open'] || $isOwner) || $c['CoGroup']['auto']) {
                    $disabled = true;
                    $disabledClass = ' disabled';
                  }
                  print '<div class="form-group form-check form-check-inline' . $disabledClass . '">';
                  $args = array();
                  $args['checked'] = $isMember;
                  $args['disabled'] =  $disabled;
                  $args['class'] = 'form-check-input';
                  print $this->Form->checkbox('CoGroupMember.rows.'.$i.'.member',$args);
                  $args = array();
                  $args['class'] = 'form-check-label';
                  print $this->Form->label('CoGroupMember.rows.'.$i.'.member',_txt('fd.group.mem'),$args) . "\n";
                  print '</div>';

                  $disabled = false;
                  $disabledClass = '';
                  if(!($e || $permissions['selectany'] || $isOwner) || $c['CoGroup']['auto']) {
                    $disabled = true;
                    $disabledClass = ' disabled';
                  }
                  print '<div class="form-group form-check form-check-inline' . $disabledClass . '">';
                  $args = array();
                  $args['checked'] = $isOwner;
                  $args['disabled'] = $disabled;
                  $args['class'] = 'form-check-input';
                  print $this->Form->checkbox('CoGroupMember.rows.'.$i.'.owner',$args);
                  $args = array();
                  $args['class'] = 'form-check-label';
                  print $this->Form->label('CoGroupMember.rows.'.$i.'.owner', _txt('fd.group.own'),$args) . "\n";
                  print '</div>';
                  print '</fieldset>';
                }
              } else {
                if($e) {
                  print $this->Html->link(_txt('me.members'),
                                          array('controller' => 'co_group_members',
                                            'action'     => 'select',
                                            'cogroup:' . $c['CoGroup']['id'],
                                            'search.members:1',
                                            'search.owners:1'),
                                          array('class' => 'comparebutton'));
                  print $this->Html->link(_txt('op.edit'),
                                          array('controller' => 'co_groups',
                                                'action' => 'edit',
                                                $c['CoGroup']['id']),
                                          array('class' => 'editbutton'));
                }
                elseif($v) {
                  print $this->Html->link(_txt('me.members'),
                                          array('controller' => 'co_group_members',
                                            'action'     => 'index',
                                            'cogroup:' . $c['CoGroup']['id'],
                                            'search.members:1',
                                            'search.owners:1'),
                                          array('class' => 'comparebutton'));

                  print $this->Html->link(_txt('op.view'),
                                          array('controller' => 'co_groups',
                                                'action'     => 'view',
                                                $c['CoGroup']['id']),
                                          array('class'      => 'viewbutton'));
                }

                if($d) {
                  print '<button type="button" class="deletebutton" title="' . _txt('op.delete')
                    . '" onclick="javascript:js_confirm_generic(\''
                    . _txt('js.remove') . '\',\''    // dialog body text
                    . $this->Html->url(              // dialog confirm URL
                      array(
                        'controller' => 'co_groups',
                        'action' => 'delete',
                        $c['CoGroup']['id'],
                        'co' => $this->params['named']['co']
                      )
                    ) . '\',\''
                    . _txt('op.remove') . '\',\''    // dialog confirm button
                    . _txt('op.cancel') . '\',\''    // dialog cancel button
                    . _txt('op.remove') . '\',[\''   // dialog title
                    . filter_var(_jtxt($c['CoGroup']['name']),FILTER_SANITIZE_STRING)  // dialog body text replacement strings
                    . '\']);">'
                    . _txt('op.delete')
                    . '</button>';
                }
              }

              if(!empty($c['CoGroupMember'][0]['source_org_identity_id'])) {
                print $this->Html->link(_txt('op.view.source'),
                                        array('controller' => 'org_identities',
                                              'action' => 'view',
                                              $c['CoGroupMember'][0]['source_org_identity_id']),
                                        array('class' => 'viewbutton'));
              }
            ?>
          </td>
        </tr>
        <?php $i++; ?>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>

    <?php if($this->action == 'select'): ?>
      <tfoot>
        <tr>
          <td colspan="4"></td>
          <td>
            <?php print $this->Form->submit(_txt('op.save')); ?>
          </td>
        </tr>
      </tfoot>
    <?php endif; ?>
  </table>
</div>

<?php
  if($this->action == 'select') {
    print $this->Form->end();
  }
  
  print $this->element("pagination");
?>

<div id="progressbar-dialog" class="co-dialog" title="<?php print _txt('op.gr.reconcile.all'); ?>">
  <p><?php print _txt('op.gr.reconcile.wait'); ?></p>
  <div id="reconcile-progressbar"></div>
</div>

<div id="reconcile-dialog" class="co-dialog" title="<?php print _txt('op.gr.reconcile.all'); ?>">
  <p><?php print _txt('op.gr.reconcile.all.confirm'); ?></p>
</div>

<div id="result-dialog" class="co-dialog" title="<?php print _txt('op.gr.reconcile.all'); ?>">
  <p><?php print _txt('rs.gr.reconcile.ok'); ?></p>
</div>
