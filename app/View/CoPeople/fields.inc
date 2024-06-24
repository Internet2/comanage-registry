<?php
/**
 * COmanage Registry CO Person Fields
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

  // Globals
  global $cm_lang, $cm_texts;

  // Determine if fields are editable -- this isn't quite the right description for this view
  $e = false;
  $es = false;

  if(($this->action == "invite" && $permissions['invite'])
      || ($this->action == "canvas" && $permissions['canvas']))
    $e = true;

  if($this->action == "canvas" && $permissions['editself'])
    $es = true;

  // We shouldn't get here if we don't have at least read permission, but check just in case
  
  if(!$e && !$permissions['view'])
    return(false);

  // Add breadcrumbs
  print $this->element("coCrumb");
  if($permissions['index']) {
    $args = array();
    $args['plugin'] = null;
    $args['controller'] = 'co_people';
    $args['action'] = 'index';
    $args['co'] = $cur_co['Co']['id'];
    $this->Html->addCrumb(_txt('me.population'), $args);
  }
  if($this->action == "canvas") {
    $this->Html->addCrumb(filter_var(generateCn($co_people[0]['PrimaryName']),FILTER_SANITIZE_SPECIAL_CHARS));
  } elseif($this->action == "invite") {
    $args = array(
      'controller' => 'org_identities',
      'action' => 'edit',
      $co_people[0]['CoOrgIdentityLink'][0]['org_identity_id']
    );
    
    $this->Html->addCrumb(generateCn($co_people[0]['PrimaryName']), $args);
    $this->Html->addCrumb(_txt('op.inv'));
  } else {
    $args = array(
      'controller' => 'co_people',
      'action' => 'canvas',
      $co_people[0]['CoPerson']['id']
    );
    
    $this->Html->addCrumb(generateCn($co_people[0]['PrimaryName']), $args);
  }
  
  // Add buttons to sidebar
  $sidebarButtons = $this->get('sidebarButtons');
  
  if($e)
  {
    // Add related links to the sidebar
    
    if($this->action != "add" && $this->action != "invite") {
      // View History button
      if($permissions['history']) {
        $sidebarButtons[] = array(
          'icon'    => 'note',
          'title'   => _txt('op.history'),
          'url'     => array(
            'controller' => 'history_records',
            'action' => 'index',
            'copersonid' => $co_people[0]['CoPerson']['id']
          )
        );
      }

      // View Job History button
      if($permissions['jobhistory']) {
        $sidebarButtons[] = array(
          'icon'    => 'note',
          'title'   => _txt('op.history.job'),
          'url'     => array(
            'controller' => 'co_job_history_records',
            'action' => 'index',
            'copersonid' => $co_people[0]['CoPerson']['id']
          )
        );
      }
      
      // Resend invitation button
      // This is based on index.ctp
      if($permissions['invite']
         && ($co_people[0]['CoPerson']['status'] == StatusEnum::Pending
             || $co_people[0]['CoPerson']['status'] == StatusEnum::Invited)) {
        $sidebarButtons[] = array(
          'icon'    => 'mail-closed',
          'title'   => _txt('op.inv.resend'),
          'url'     => 'javascript:js_confirm_generic(\''
            . _txt('js.reinvite') . '\',\''    // dialog body text
            . $this->Html->url(              // dialog confirm URL
              array(
                'controller' => 'co_invites',
                'action'     => 'send',
                'copersonid' => $co_people[0]['CoPerson']['id']
              )
            ) . '\',\''
            . _txt('op.inv.resend') . '\',\''    // dialog confirm button
            . _txt('op.cancel') . '\',\''    // dialog cancel button
            . _txt('op.inv.resend') . '\',[\''   // dialog title
            . filter_var(_jtxt(generateCn($co_people[0]['PrimaryName'])),FILTER_SANITIZE_STRING)  // dialog body text replacement strings
            . '\'])'
        );
      } elseif($permissions['enroll']
               && $co_people[0]['CoPerson']['status'] == StatusEnum::PendingConfirmation) {
        if(!empty($co_people[0]['CoInvite']['CoPetition']['id'])) {
          $sidebarButtons[] = array(
          'icon'    => 'mail-closed',
          'title'   => _txt('op.inv.resend'),
          'url'     => 'javascript:js_confirm_generic(\''
            . _txt('js.reinvite') . '\',\''    // dialog body text
            . $this->Html->url(              // dialog confirm URL
              array(
                'controller' => 'co_petitions',
                'action'     => 'resend',
                $co_people[0]['CoInvite']['CoPetition']['id']
              )
            ) . '\',\''
            . _txt('op.inv.resend') . '\',\''    // dialog confirm button
            . _txt('op.cancel') . '\',\''    // dialog cancel button
            . _txt('op.inv.resend') . '\',[\''   // dialog title
            . filter_var(_jtxt(generateCn($co_people[0]['PrimaryName'])),FILTER_SANITIZE_STRING)  // dialog body text replacement strings
            . '\'])'
          );
        }
      }
      
      if(isset($vv_authenticator_count) && $vv_authenticator_count > 0) {
        // Link to authenticators for this person
        
        $sidebarButtons[] = array(
          'icon'    => 'key',
          'title'   => _txt('ct.authenticators.pl'),
          'url'     => array(
            'controller' => 'authenticators',
            'action' => 'status',
            'copersonid' => $co_people[0]['CoPerson']['id']
          )
        );
      }
      
      if(isset($vv_cluster_count) && $vv_cluster_count > 0) {
        // Link to clusters for this person
        
        $sidebarButtons[] = array(
          'icon'    => 'person',
          'title'   => _txt('ct.clusters.pl'),
          'url'     => array(
            'controller' => 'clusters',
            'action' => 'status',
            'copersonid' => $co_people[0]['CoPerson']['id']
          )
        );
      }
      
      if($permissions['iddocuments']) {
        $sidebarButtons[] = array(
          'icon'    => 'document',
          'title'   => _txt('ct.identity_documents.pl'),
          'url'     => array(
            'controller' => 'identity_documents',
            'action'     => 'index',
            'copersonid' => $co_people[0]['CoPerson']['id']
          )
        );
      }
      
      if($vv_enable_nsf_demo) {
        // Adjust the link to the NSF Demographics Controller according to whether or
        // not data has been set.
        
        $l = array();
        $l['controller'] = 'co_nsf_demographics';
        
        if(empty($co_people[0]['CoNsfDemographic']['id'])) {
          $l['action'] = 'add';
          $l['copersonid'] = $co_people[0]['CoPerson']['id'];
        } else {
          $l['action'] = 'edit';
          $l[] = $co_people[0]['CoNsfDemographic']['id'];
        }
        
        $l['co'] = $co_people[0]['CoPerson']['co_id'];
        
        // NSF Demographics
        $sidebarButtons[] = array(
          'icon'    => 'image',
          'title'   => _txt('ct.co_nsf_demographics.1'),
          'url'     => $l
        );
      }
      
      // Provisioning status
      if($permissions['provision']) {
        $sidebarButtons[] = array(
          'icon'    => 'gear',
          'title'   => _txt('op.prov.view'),
          'url'     => array(
            'controller' => 'co_people', 
            'action'     => 'provision', 
            $co_people[0]['CoPerson']['id']
          )
        ); 
      }
      
      // Terms and Conditions
      if(!empty($vv_co_tandc_count) && $vv_co_tandc_count > 0) {
        $sidebarButtons[] = array(
          'icon'    => 'tag',
          'title'   => _txt('op.tc.review'),
          'url'     => array(
            'controller' => 'co_terms_and_conditions', 
            'action'     => 'review', 
            'copersonid' => $co_people[0]['CoPerson']['id']
          )
        );
      }
      
      // View Notifications (where person is subject)
      if($permissions['notifications-subject']) {
        $sidebarButtons[] = array(
          'icon'    => 'mail-closed',
          'title'   => _txt('ct.co_notifications.pl'),
          'url'     => array(
            'controller' => 'co_notifications',
            'action' => 'index',
            'subjectcopersonid' => $co_people[0]['CoPerson']['id']
          )
        );
      }
      
      // View Petitions
      if($permissions['petitions']) {
        $sidebarButtons[] = array(
          'icon'    => 'script',
          'title'   => _txt('ct.co_petitions.pl'),
          'url'     => array(
            'controller' => 'co_petitions',
            'action' => 'index',
            // Since this is an index view, we still need the CO ID. paginationConditions
            // will restrict searching to eligible enrollments.
            'co' => $co_people[0]['CoPerson']['co_id'],
            'sort' => 'modified',
            'search.copersonid' => $co_people[0]['CoPerson']['id']
          )
        );
      }
      
      // Vetting Requests (available if Vetting Steps are defined)
      if(isset($vv_vetting_step_count) && $vv_vetting_step_count > 0) {
        $sidebarButtons[] = array(
          'icon'    => 'person',
          'title'   => _txt('ct.vetting_requests.pl'),
          'url'     => array(
            'controller' => 'vetting_requests',
            'action' => 'index',
            'copersonid' => $co_people[0]['CoPerson']['id']
          )
        );
      }
      
      // Autogenerate Identifiers button
      if($permissions['assign'] && !empty($co_identifier_assignments)) {
        $sidebarButtons[] = array(
            'icon'    => 'contact',
            'title'   => _txt('op.id.auto'),
            'url'     => 'javascript:js_confirm_autogenerate();' // Does not work when added in options
        );
      }
      
      if($permissions['delete'] && empty($co_people[0]['CoPersonRole'])) {
        $sidebarButtons[] = array(
          'icon'    => 'circle-close',
          'title'   => _txt('op.delete'),
          'url'     => 'javascript:js_confirm_generic(\''
            . _txt('js.remove') . '\',\''    // dialog body text
            . $this->Html->url(              // dialog confirm URL
              array(
                'controller' => 'co_people',
                'action'     => 'delete',
                $co_people[0]['CoPerson']['id']
              )
            ) . '\',\''
            . _txt('op.remove') . '\',\''    // dialog confirm button
            . _txt('op.cancel') . '\',\''    // dialog cancel button
            . _txt('op.remove') . '\',[\''   // dialog title
            . filter_var(_jtxt(generateCn($co_people[0]['PrimaryName'])),FILTER_SANITIZE_STRING)  // dialog body text replacement strings
            . '\'])'
        );
      }
      
      // Inject plugin menus. We intentionally leave expunge as the last option,
      // pending identity architecture analysis.
      
      if(!empty($menuContent['plugins'])) {
        $pluginLinks = retrieve_plugin_menus($menuContent['plugins'], 'canvas');
        
        foreach($pluginLinks as $label => $pcfg) {
          $sidebarButtons[] = array(
            'icon'    => $pcfg['icon'],
            'title'   => $label,
            'url'     => array_merge($pcfg['url'], array('copersonid' => $co_people[0]['CoPerson']['id']))
          );
        }
      }
      
      if($permissions['expunge']) {
        $sidebarButtons[] = array(
          'icon'    => 'circle-close',
          'title'   => _txt('op.expunge'),
          'class'   => 'lightbox',
          'url'     => array(
            'controller' => 'co_people', 
            'action'     => 'expunge', 
            $co_people[0]['CoPerson']['id']
          )
        );
      }
    }
    
    // Populate the cross reference
    // XXX This first hidden field will not be in a form in the Canvas view - is it needed?
    print $this->Form->hidden('CoPerson.co_id', array('default' => $cur_co['Co']['id'])). "\n";
    if(!$this->action != "canvas") {
      print $this->Form->hidden('CoOrgIdentityLink.0.id'). "\n";
      if(!empty($co_people[0]['CoOrgIdentityLink'][0]['org_identity_id'])) {
        print $this->Form->hidden('CoOrgIdentityLink.0.org_identity_id', array('default' => $co_people[0]['CoOrgIdentityLink'][0]['org_identity_id'])). "\n";
      }
      // Default status is 'Pending'
      print $this->Form->hidden('status', array('default' => 'P')). "\n";
    }
  }
  
  // Set buttons for rendering in sidebar  
  $this->set('sidebarButtons', $sidebarButtons);

  // Line number, for rendering
  $l = 1;
?>

<script type="text/javascript">
  <!-- /* JS specific to these fields */ -->

  function js_confirm_autogenerate() {
    // Open the dialog to confirm autogeneration of identifiers
    var $tabs = $( "#tabs" ).tabs();
    $('#autogenerate-dialog').dialog('open');
  }

  $(function() {
    // Enable collapse effect on the entire row, except Action fraction
    $('.field-data-container div.field-data').on('click', function() {
      $(this).parent('li.field-data-container').find('.collapse').collapse('toggle');
    });

    // Explorer menu toggles
    $(".fieldGroupName").click(function(event) {
      event.preventDefault();
      $(this).parent().next(".fields").slideToggle("fast");
      // toggle the +/- icon:
      if ($(this).find(".material-icons").text() == "expand_less") {
        $(this).find(".material-icons").text("expand_more");
        $(this).attr('title','<?php print _txt('op.expand'); ?>');
        $(this).attr('aria-expanded','false');
      } else {
        $(this).find(".material-icons").text("expand_less");
        $(this).attr('title','<?php print _txt('op.collapse'); ?>');
        $(this).attr('aria-expanded','true');
      }
    });

    // Autogenerate dialog
    $("#autogenerate-dialog").dialog({
      autoOpen: false,
      <?php if($this->action == 'canvas'): ?>
      buttons: [
        {
          text : "<?php print _txt('op.cancel'); ?>",
          click : function() {
            $(this).dialog("close");
          }
        },
        {
          text : "<?php print _txt('op.id.auto'); ?>",
          click: function () {
            $(this).dialog("close");
            displaySpinner();
            window.location.href = "<?php print $this->Html->url(array('controller' => 'identifiers',
            'action' => 'assign',
            'copersonid' => $co_people[0]['CoPerson']['id'])); ?>";
          }
        }
      ],
      modal: true,
      show: {
        effect: "fade"
      },
      hide: {
        effect: "fade"
      }
      <?php endif; // canvas ?>
    });
  });
</script>

<?php if($this->action != 'invite'): ?>

  <div id="<?php print $this->action; ?>_co_person" class="explorerContainer">
    <div id="coPersonExplorer" class="personExplorer">
      <ul>

        <!-- Names -->
        <li id="fields-name" class="fieldGroup">
          <div class="fieldGroupNameContainer">
            <a href="#tabs-name" class="fieldGroupName" title="<?php print _txt('op.collapse') ?>" aria-expanded="true" aria-controls="tabs-name">
              <em class="material-icons" aria-hidden="true">expand_less</em>
              <h2><?php print _txt('fd.name'); ?></h2>
            </a>
            <?php if($this->Permission->selfService($permissions, $e, 'Name') == PermissionEnum::ReadWrite): ?>
              <div class="coAddEditButtons">
                <?php
                $linktarget = array(
                  'controller' => 'names',
                  'action'     => 'add',
                  'copersonid' => $co_people[0]['CoPerson']['id'],
                  'co'         => $cur_co['Co']['id']
                );
                $linkparams = array(
                  'class'  => 'addbutton',
                  'title'  => _txt('op.add.new',[_txt('ct.names.1')])
                  //'escape' => false
                );
  
                print $this->Html->link(_txt('op.add'),
                  $linktarget,
                  $linkparams);
                ?>
              </div>
            <?php endif; // invite+permission ?>
          </div>

          <ul id="tabs-name" class="fields data-list">

            <?php foreach($co_people[0]['Name'] as $n): ?>
              <li class="field-data-container">
                <?php
                  $perm = $this->Permission->selfService($permissions, $e, 'Name', (!empty($n['type']) ? $n['type'] : null));
                ?>
                <div class="field-data force-wrap <?php if($perm == PermissionEnum::ReadOnly) { print ' field-data-alone'; }; ?>">
                  <?php
                  $primary_name_with_bg = $this->Badge->badgeIt(
                    _txt('fd.name.primary_name'),
                    $this->Badge->getBadgeColor('Secondary'),
                    false,
                    true
                  );
                  $cn = $n['primary_name'] ? filter_var(generateCn($n),FILTER_SANITIZE_SPECIAL_CHARS) . "&nbsp" . $primary_name_with_bg
                                           : filter_var(generateCn($n),FILTER_SANITIZE_SPECIAL_CHARS);
                  if($perm == PermissionEnum::ReadWrite) {
                    print $this->Html->link(
                      $cn,
                      array('controller' => 'names',
                            'action' => 'edit',
                            $n['id']),
                      array(
                        'escape' => false,
                      ));
                  } elseif($perm == PermissionEnum::ReadOnly) {
                    print $cn;
                  }
                 ?>
                </div>
                <div class="field-data data-label <?php if($perm == PermissionEnum::ReadOnly) { print ' field-data-alone'; }; ?>">
                <?php
                $badge_list = array();
                // OrgIdentitySource
                if(!empty($n['SourceName']['id']) && !empty($n['SourceName']['OrgIdentity']['OrgIdentitySourceRecord']['OrgIdentitySource']['description'])) {
                  $badge_list[] = array(
                    'order' => $this->Badge->getBadgeOrder('Source'),
                    'text' => filter_var($n['SourceName']['OrgIdentity']['OrgIdentitySourceRecord']['OrgIdentitySource']['description'],FILTER_SANITIZE_SPECIAL_CHARS),
                    'color' => $this->Badge->getBadgeColor('Light'),
                  );
                }
                // Name Type
                $badge_list[] = array(
                  'order' => $this->Badge->getBadgeOrder('Status'),
                  'text' => filter_var($vv_cop_name_types[ $n['type'] ],FILTER_SANITIZE_SPECIAL_CHARS),
                  'color' => $this->Badge->getBadgeColor('Light'),
                );
                // Name Language
                if(!empty($n['language'])) {
                  $badge_list[] = array(
                    'order' => $this->Badge->getBadgeOrder('Other'),
                    'text' => _txt('en.language', null, $n['language']),
                    'color' => $this->Badge->getBadgeColor('Light'),
                  );
                }

                if(!empty($badge_list)) {
                  print $this->element('badgeList', array('vv_badge_list' => $badge_list));
                }
                ?>
                </div>
                <div class="field-actions">
                  <?php
                  $action_args = array();
                  $action_args['vv_attr_mdl'] = "Name";
                  $action_args['vv_attr_id'] = $n['id'];
                  if(!empty($n['SourceName']['id'])) {
                    // This name came from an OIS and so can't be edited, and for the moment
                    // can't be made primary (though maybe that'll change with a use case).
                    // View button
                    $action_args['vv_actions'][] = array(
                      'order' => $this->Menu->getMenuOrder('View'),
                      'icon' => $this->Menu->getMenuIcon('View'),
                      'lightbox' => true,
                      'url' => $this->Html->url(
                        array(
                          'controller' => 'names',
                          'action' => 'view',
                          $n['id'],
                        )
                      ),
                      'label' => _txt('op.view'),
                    );
                    } else {
                      if ($perm === PermissionEnum::ReadWrite) {
                        if (!$n['primary_name']) {
                          // Delete Action button
                          $dg_url = array(
                            'controller' => 'names',
                            'action' => 'delete', $n['id'],
                            'co' => $cur_co['Co']['id']
                          );
                          $action_args['vv_actions'][] = array(
                            'order' => $this->Menu->getMenuOrder('Delete'),
                            'icon' => $this->Menu->getMenuIcon('Delete'),
                            'url' => 'javascript:void(0);',
                            'label' => _txt('op.delete'),
                            'onclick' => array(
                              'dg_bd_txt' => _txt('js.remove'),
                              'dg_url' => $this->Html->url($dg_url),
                              'dg_conf_btn' => _txt('op.remove'),
                              'dg_cancel_btn' => _txt('op.cancel'),
                              'dg_title' => _txt('op.remove'),
                              'db_bd_txt_repl_str' => filter_var(_jtxt(generateCn($n)), FILTER_SANITIZE_STRING),
                            ),
                          );

                          // Primary name Action Button
                          $action_args['vv_actions'][] = array(
                            'order' => $this->Menu->getMenuOrder('PrimaryName'),
                            'icon' => $this->Menu->getMenuIcon('PrimaryName'),
                            'label' => _txt('op.primary'),
                            'url' => $this->Html->url(
                              array(
                                'controller' => 'names',
                                'action' => 'primary',
                                $n['id'],
                                'copersonid' => $co_people[0]['CoPerson']['id'],
                              )
                            ),
                          );
                        }

                        // Edit Action button
                        $action_args['vv_actions'][] = array(
                          'order' => $this->Menu->getMenuOrder('Edit'),
                          'icon' => $this->Menu->getMenuIcon('Edit'),
                          'url' => $this->Html->url(
                            array(
                              'controller' => 'names',
                              'action' => 'edit',
                              $n['id'],
                              )
                          ),
                          'label' => _txt('op.edit'),
                        );
                      }
                    }
                  if(!empty($action_args['vv_actions'])) {
                    print $this->element('menuAction', $action_args);
                  }
                  ?>
                </div>
              </li>
              <?php endforeach; ?>
          </ul><!-- tabs-name -->
        </li><!-- fields-name -->

        <?php
          $args = array(
            'edit'         => ($e && !$es),
            'self_service' => false,
            'model'        => 'CoPerson',
            'model_param'  => 'copersonid',
            'mvpa_model'   => 'Identifier',
            'mvpa_field'   => 'identifier'
          );

          print $this->element('mvpa', $args);

          $args['edit']         = $e;
          $args['self_service'] = true;
          $args['mvpa_model']   = 'EmailAddress';
          $args['mvpa_field']   = 'mail';

          print $this->element('mvpa', $args);

          $args['edit']         = $e;
          $args['self_service'] = true;
          $args['mvpa_model']   = 'Url';
          $args['mvpa_field']   = 'url';

          print $this->element('mvpa', $args);
        ?>


        <!-- Groups -->
        <li id="fields-group" class="fieldGroup">
          <div class="fieldGroupNameContainer">
            <a href="#tabs-group" class="fieldGroupName" title="<?php print _txt('op.collapse') ?>" aria-expanded="true" aria-controls="tabs-group">
              <em class="material-icons" aria-hidden="true">expand_less</em>
              <h2><?php print _txt('fd.groups'); ?></h2>
            </a>
            <?php
            if($e) {
              print '<div class="coAddEditButtons">';
              $linktarget = array(
                'controller' => 'co_groups',
                'action'     => 'select',
                'copersonid' => $co_people[0]['CoPerson']['id'],
                'search.member' => '1',
                'search.owner' => '1'
              );
              $linkparams = array('class' => 'addbutton');
              print $this->Html->link(_txt('op.manage.grm'),
                  $linktarget,
                  $linkparams) . "\n";
              print '</div>';
            }
            ?>
          </div>
          <ul id="tabs-group" class="fields data-list">
            <?php
            if(isset($co_people[0]['CoGroupMember'])) {
              // List of NON auto groups
              $group_memberships = array();
              foreach($co_people[0]['CoGroupMember'] as $g) {
                if($e && !$es && !$g['CoGroup']['auto']) {
                  if($g['member'] || $g['owner']) {
                    print '<li class="field-data-container centered">';
                    print '<div class="field-data">';
                    if(strpos($g['CoGroup']['name'], 'CO:') !== false) {
                      $gname = $this->Badge->systemGroupHFormat($g['CoGroup']['name'], $cur_co);
                      print $this->Html->link(
                        $gname['name'],
                        array(
                          'controller' => 'co_groups',
                          'action' => 'edit',
                          $g['co_group_id'],
                          'co' => $cur_co['Co']['id'],
                        ),
                        array(
                          'escape' => false,
                        )
                      );
                    } else {
                      print $this->Html->link($g['CoGroup']['name'], array('controller' => 'co_groups', 'action' => 'edit', $g['co_group_id'], 'co' => $cur_co['Co']['id']));
                    }
                    print '</div>';

                    $badge_list = array();
                    if($g['owner']) {
                      if($g['member']) {
                        // Owner Badge
                        $badge_list[] = array(
                          'order' => $this->Badge->getBadgeOrder('Owner'),
                          'text' => _txt('fd.group.own'),
                          'color' => $this->Badge->getBadgeColor('Light'),
                        );
                        // Member Badge
                        $badge_list[] = array(
                          'order' => $this->Badge->getBadgeOrder('Member'),
                          'text' => _txt('fd.group.mem'),
                          'color' => $this->Badge->getBadgeColor('Light'),
                        );
                      } else {
                        $badge_list[] = array(
                          'order' => $this->Badge->getBadgeOrder('Owner'),
                          'text' => _txt('fd.group.own.only'),
                          'color' => $this->Badge->getBadgeColor('Light'),
                        );
                      }
                    } elseif($g['member']) {
                      $badge_list[] = array(
                        'order' => $this->Badge->getBadgeOrder('Member'),
                        'text' => _txt('fd.group.mem'),
                        'color' => $this->Badge->getBadgeColor('Light'),
                      );
                    }
                    // Warn if membership is invalid
                    if((!empty($g['valid_from'])
                        && strtotime($g['valid_from']) >= time())
                      ||
                      (!empty($g['valid_through'])
                        && strtotime($g['valid_through']) < time())) {
                      $badge_list[] = array(
                        'order' => $this->Badge->getBadgeOrder('Status'),
                        'text' => _txt('fd.inactive'),
                        'color' => $this->Badge->getBadgeColor('Warning'),
                      );
                    }
                    // Add element
                    print '<div class="field-data data-label">';
                    if(!empty($badge_list)) {
                      print $this->element('badgeList', array('vv_badge_list' => $badge_list));
                    }
                    print '</div>';

                    // XXX we already checked for $permissions['edit'], but not ['delete']... should we?

                    // Action list
                    $action_args = array();
                    $action_args['vv_attr_mdl'] = "Group";
                    $action_args['vv_attr_id'] = $g['CoGroup']['id'];
                    // Edit
                    $action_args['vv_actions'][] = array(
                      'order' => $this->Menu->getMenuOrder('Edit'),
                      'icon' => $this->Menu->getMenuIcon('Edit'),
                      'url' => $this->Html->url(
                        array(
                          'controller' => 'co_group_members',
                          'action' => 'edit',
                          $g['id'],
                        )
                      ),
                      'label' => _txt('op.edit'),
                    );
                    // Delete
                    $dg_url = array(
                      'controller' => 'co_group_members',
                      'action' => 'delete', $g['id'],
                      'copersonid' => $co_people[0]['CoPerson']['id'],
                      'return' => 'person'
                    );
                    $action_args['vv_actions'][] = array(
                      'order' => $this->Menu->getMenuOrder('Delete'),
                      'icon' => $this->Menu->getMenuIcon('Delete'),
                      'url' => 'javascript:void(0);',
                      'label' => _txt('op.delete'),
                      'onclick' => array(
                        'dg_bd_txt' => _txt('js.remove.member'),
                        'dg_url' => $this->Html->url($dg_url),
                        'dg_conf_btn' => _txt('op.remove'),
                        'dg_cancel_btn' => _txt('op.cancel'),
                        'dg_title' => _txt('op.remove'),
                        'db_bd_txt_repl_str' => filter_var(_jtxt($g['CoGroup']['name']),FILTER_SANITIZE_STRING),
                      ),
                    );

                    if(!empty($g['source_org_identity_id'])) {
                      $action_args['vv_actions'][] = array(
                        'order' => $this->Menu->getMenuOrder('View'),
                        'icon' => $this->Menu->getMenuIcon('View'),
                        'label' =>  _txt('op.view.source'),
                        'url' =>  $this->Html->url(
                          array(
                            'controller' => 'org_identities',
                            'action' => 'view',
                            $g['source_org_identity_id'],
                          )
                        ),
                      );
                    }

                    // XXX we have to kinds of actions. The ones that requires a confirmation and the ones that do not
                    print '<div class="field-actions">';
                    if(!empty($action_args['vv_actions'])) {
                      print $this->element('menuAction', $action_args);
                    }
                    print '</div>';
                    print '</li>';
                  }
                } else {
                  $badge_list = array();
                  $auto_group_format = $this->Badge->systemGroupHFormat($g['CoGroup']['name'], $cur_co);
                  if($g['member'] && !empty($auto_group_format['badge'])) {
                    // Do not present Auto Groups to non CO and Platform Administrators
                    if(!$permissions['viewautogroups'] && $g['CoGroup']['auto']) {
                      continue;
                    }
                    $badge_list = array_merge($badge_list, $auto_group_format['badge']);
                  }
                  if($g['owner']) {
                    $badge_list[] = array(
                      'order' => $this->Badge->getBadgeOrder('Owner'),
                      'text' => _txt('fd.group.own'),
                      'color' => $this->Badge->getBadgeColor('Light'),
                    );
                  }
                  if(!empty($group_memberships[$auto_group_format['key']])) {
                    $badge_text = Hash::extract($group_memberships[$auto_group_format['key']]['badge'], '{n}.text');
                    foreach($auto_group_format['badge'] as $bg) {
                      if(!in_array($bg['text'], $badge_text)) {
                        $group_memberships[$auto_group_format['key']]['badge'][] = $bg;
                      }
                    }
                  } else {
                    $group_memberships[$auto_group_format['key']] = $auto_group_format;
                  }
                }
              } // foreach($co_people[0]['CoGroupMember'] as $g)
              // Print the Groups
              foreach($group_memberships as $gr_membership) {
                print '<li class="field-data-container">';
                print '<div class="field-data field-data-alone">';
                print $gr_membership['name'];
                print '</div>';
                // Add Badge elements
                print '<div class="field-data data-label">';
                if(!empty($gr_membership['badge'])) {
                  print $this->element('badgeList', array('vv_badge_list' => $gr_membership['badge']));
                }
                print '</div>';
                print '</li>';
              }
            } // isset($co_people[0]['CoGroupMember'])
            ?>
          </ul><!-- tabs-group -->
        </li><!-- fields-group -->

        <!-- Person attributes form -->
        <li id="fields-coperson" class="fieldGroup">
          <?php
            print $this->Form->create('CoPerson', array('url' => array('action'=>'edit','id'=>'CoPersonEditAttributesForm')));
            print $this->Form->hidden('CoPerson.co_id', array('default' => $co_people[0]['CoPerson']['co_id'],'id'=>'CoPersonCoIdAgain')). "\n";
          ?>
          <div class="fieldGroupNameContainer">
            <a href="#tabs-coperson" class="fieldGroupName" title="<?php print _txt('op.collapse') ?>" aria-expanded="true" aria-controls="tabs-coperson">
              <em class="material-icons" aria-hidden="true">expand_less</em>
              <h2><?php print _txt('fd.attrs.cop'); ?></h2>
            </a>
          </div>
          <ul  id="tabs-coperson" class="fields form-list">
            <li>
              <div class="field-name vtop">
                <label for="CoPersonStatusAgain"><?php print _txt('fd.status'); ?></label>
              </div>
              <div class="field-info">
                <?php
                  if($e && $permissions['edit']
                     && !$permissions['editself']) {
                    global $cm_lang, $cm_texts;

                    $attrs = array();
                    $attrs['value'] = $co_people[0]['CoPerson']['status'];
                    $attrs['empty'] = false;
                    $attrs['id'] = 'CoPersonStatusAgain';

                    print $this->Form->select('CoPerson.status',
                                              $cm_texts[ $cm_lang ]['en.status'],
                                              $attrs);

                    if($this->Form->isFieldError('CoPerson.status')) {
                      print $this->Form->error('CoPerson.status');
                    }
                  } else {
                    if(!empty($co_people[0]['CoPerson']['status'])) {
                      print _txt('en.status', null, $co_people[0]['CoPerson']['status']);

                      if($e) {
                        // Self service... we need to include current status so the form saves
                        print $this->Form->hidden('CoPerson.status');
                      }
                    }
                  }
                ?>
                <?php if($e && $permissions['edit']
                         && !$permissions['editself']
                         &&
                         ($co_people[0]['CoPerson']['status'] == StatusEnum::PendingApproval
                          || $co_people[0]['CoPerson']['status'] == StatusEnum::PendingConfirmation)): ?>
                  <div class="field-desc">
                    <span class="ui-icon ui-icon-info co-info"></span>
                    <?php print _txt('fd.status.change'); ?>
                  </div>
                <?php endif; ?>
              </div>
            </li>
            <li class="modelbox-data">
              <div class="field-name">
                <?php print $this->Form->label('CoPerson.date_of_birth', _txt('fd.date_of_birth')); ?>
              </div>
              <div class="field-info">
                <?php
                  if($e && !$es) {
                    $args = array(
                      'class' => 'datepicker'
                    );

                    if(!empty($co_people[0]['CoPerson']['date_of_birth'])) {
                      $args['value'] = $co_people[0]['CoPerson']['date_of_birth'];
                    }

                    print $this->Form->text('date_of_birth', $args);
                  } else {
                    if(!empty($co_people[0]['CoPerson']['date_of_birth'])) {
                      print $this->Time->format($co_people[0]['CoPerson']['date_of_birth'], "%F");
                    }
                  }
                ?>
              </div>
            </li>
            <li>
              <div class="field-name">
                <?php print $this->Form->label('CoPerson.timezone', _txt('fd.timezone')); ?>
              </div>
              <div class="field-info">
                <?php
                  if($e && ($permissions['edit'] || $permissions['editself'])) {
                    $attrs = array();
                    $attrs['value'] = $co_people[0]['CoPerson']['timezone'];
                    $attrs['empty'] = true;

                    print $this->Form->select('CoPerson.timezone',
                                              // We use array_combine to force Cake to store
                                              // the string (eg: Europe/Amsterndam) and not the
                                              // index (eg: 310)
                                              array_combine(timezone_identifiers_list(),
                                                            timezone_identifiers_list()),
                                              $attrs);

                    if($this->Form->isFieldError('CoPerson.timezone')) {
                      print $this->Form->error('CoPerson.timezone');
                    }
                  } else {
                    if(!empty($co_people[0]['CoPerson']['timezone'])) {
                      print $co_people[0]['CoPerson']['timezone'];
                    }
                  }
                ?>
                <div class="co-info">
                  <span class="ui-icon ui-icon-info co-info"></span>
                  <em><?php print _txt('fd.timezone.desc'); ?></em>
                </div>
                <?php if($e &&
                         ($permissions['editself']
                          // Special check for admins, who aren't editself
                          || $this->Session->read('Auth.User.co_person_id') == $this->request->params['pass'][0])): ?>
                  <div class="co-info">
                    <span class="ui-icon ui-icon-info co-info"></span>
                    <em><?php print _txt('fd.timezone.change'); ?></em>
                  </div>
                <?php endif; ?>
              </div>
            </li>
            <?php if($e && ($permissions['edit'] || $permissions['editself'])): ?>
              <li class="fields-submit">
                <div class="field-name"></div>
                <div class="field-info">
                  <?php print $this->Form->submit(_txt('op.save')); ?>
                </div>
              </li>
            <?php endif; // edit ?>
          </ul><!-- tabs-coperson -->
          <?php print $this->Form->end(); ?>
        </li> <!-- fields-coperson -->

        <!-- Roles -->
        <li id="fields-role" class="fieldGroup">
          <div class="fieldGroupNameContainer">
            <a href="#tabs-role" class="fieldGroupName" title="<?php print _txt('op.collapse') ?>" aria-expanded="true" aria-controls="tabs-role">
              <em class="material-icons" aria-hidden="true">expand_less</em>
              <h2><?php print _txt('fd.attrs.copr'); ?></h2>
            </a>
            <?php
            if($e && !$es) {
              print '<div class="coAddEditButtons">';
              $linktarget = array('controller' => 'co_person_roles',
                'action'     => 'add',
                'copersonid' => $co_people[0]['CoPerson']['id'],
                'co'         => $cur_co['Co']['id']
              );
              $linkparams = array('class'  => 'addbutton');
  
              print $this->Html->link(_txt('op.add'),
                  $linktarget,
                  $linkparams) . PHP_EOL;
              print '</div>';
            }
            ?>
          </div>
          <!--     Table     -->
          <ul id="tabs-role" class="fields data-list">
              <?php
              if($this->action != "invite"){
                // Person Role Data
                $rcnt = 1;
                foreach($co_people[0]['CoPersonRole'] as $r) {
                  $badge_list_role = array();
                  // Set the status styling
                  $statusClass = $this->Badge->getBadgeColor('Light');
                  // Set ordering
                  $bgOrder = $this->Badge->getBadgeOrder('Other');
                  // Store the action list
                  $action_args = array();
                  $action_args['vv_attr_mdl'] = "CoPersonRole";
                  $action_args['vv_attr_id'] = $r["id"];

                  if($r['status'] == StatusEnum::Suspended
                    || $r['status'] == StatusEnum::Expired
                    || $r['status'] == StatusEnum::Deleted) {
                    // Style status for suspended, expired, deleted
                    $bgOrder = $this->Badge->getBadgeOrder('Status');
                    $statusClass = $this->Badge->getBadgeColor('Danger'); // reddish
                  } elseif($r['status'] != StatusEnum::Active) {
                    // Style status if otherwise not active
                    $bgOrder = $this->Badge->getBadgeOrder('Status');
                    $statusClass = $this->Badge->getBadgeColor('Warning'); // yellowish
                  }
                  // Independent of status, set the style based on dates if needed:
                  $validThroughDate = !empty($r['valid_through']) ? $this->Time->format($r['valid_through'], "%F", false, $vv_tz) : false;
                  $validFromDate = !empty($r['valid_from']) ? $this->Time->format($r['valid_from'], "%F", false, $vv_tz) : false;

                  if ($validThroughDate && $this->Time->isPast($validThroughDate)) {
                    // valid through date exists and is in the past
                    $bgOrder = $this->Badge->getBadgeOrder('Status');
                    $statusClass = $this->Badge->getBadgeColor('Danger'); // reddish
                  } elseif ($validFromDate && $this->Time->isFuture($validFromDate)) {
                    // valid from date is in the future
                    $bgOrder = $this->Badge->getBadgeOrder('Status');
                    $statusClass = $this->Badge->getBadgeColor('Warning'); // yellowish
                  }

                  $status = !empty($r['status']) ? _txt('en.status', null, $r['status']) : '-';
                  if($r['status'] != StatusEnum::Active) {
                    $badge_list_role[] = array(
                      'order' => $bgOrder,
                      'text' => $status,
                      'color' => $statusClass,
                    );
                  }
                  if(!empty($r['affiliation'])) {
                    $badge_list_role[] = array(
                      'order' => $this->Badge->getBadgeOrder('Title'),
                      'text' => (filter_var($vv_copr_affiliation_types[$r['affiliation']], FILTER_SANITIZE_SPECIAL_CHARS)),
                      'color' => $this->Badge->getBadgeColor('Light'),
                    );
                  }
                  if(!empty($r['title'])) {
                    $badge_list_role[] = array(
                      'order' => $this->Badge->getBadgeOrder('Title'),
                      'text' => ucfirst(filter_var($r['title'], FILTER_SANITIZE_SPECIAL_CHARS)),
                      'color' => $this->Badge->getBadgeColor('Light'),
                    );
                  }
                  $cou_name = (empty($r['Cou']['name']))
                              ? '<cite class="text-muted-cmg"><em class="mr-1 material-icons">expand_less</em>empty</cite>'
                              : '<em class="material-icons" aria-hidden="true">expand_less</em> ' . filter_var($r['Cou']['name'], FILTER_SANITIZE_SPECIAL_CHARS);

                  // COU Name Column
                  print '<li class="field-data-container centered">';
                  print '<div class="field-data flex-column">';
                  $linkparams = array(
                    'id' => 'role-content_' . md5("CoPersonRole" . $r['id']),
                    'class' => 'collapsed',
                    'aria-controls' => 'collapse_link_' . md5("CoPersonRole" . $r['id']),
                    'escape' => false,
                    'data-toggle' => 'collapse',
                    'aria-haspopup' => 'true',
                    'aria-expanded' => 'false',
                  );
                  print $this->Html->link(
                    $cou_name . ' <cite class="text-muted-cmg cm-id-display">' . _txt('fd.id.val', array($r['id'])) . '</cite>',
                    '#collapse_link_' . md5("CoPersonRole" . $r['id']),
                    $linkparams
                  );

                  // Badge Column
                  print '</div>';
                  print '<div class="field-data data-label">';
                  print $this->element('badgeList', array('vv_badge_list' => $badge_list_role));
                  print '</div>';

                  // Action Column
                  // If the use has the role of `admincous` then we will get a list the list of full path COUs under
                  // $permissions['cous']. In the case of a CO or CMP admin we will get the list under the $permissions['cous']["path"]
                  $permissionsCousPath = $permissions['cous']["path"] ?? $permissions['cous'];
                  if ($es // Editing self
                    || empty($r['Cou']) // No COU set for this person
                    || (isset($r['Cou']['name']) && in_array($r['Cou']['path'], $permissionsCousPath))
                  ) // Admin for the COU
                  {
                    // COU Admins can only edit their own folks, so we need a bit of
                    // a machination to determine if they can edit these records
                    // along side other authorized folks.

                    // Currently, users can self-edit some role level attributes,
                    // so give them an edit button, too.

                    if ($permissions['enroll']
                      && $r['status'] == StatusEnum::PendingApproval
                      && !empty($r['CoPetition'][0]['id'])
                    ) {
                      $action_args['vv_actions'][] = array(
                        'order' => $this->Menu->getMenuOrder('PetitionView'),
                        'icon' => $this->Menu->getMenuIcon('PetitionView'),
                        'url' => $this->Html->url(
                          array('controller' => 'co_petitions',
                            'action' => 'view',
                            $r['CoPetition'][0]['id'],
                            'co' => $r['CoPetition'][0]['co_id'],
                            'coef' => $r['CoPetition'][0]['co_enrollment_flow_id'])
                        ),
                        'label' => _txt('op.petition'),
                      );
                    }

                    if ($permissions['canvas']) {
                      $action_args['vv_actions'][] = array(
                        'order' => $this->Menu->getMenuOrder('Edit'),
                        'icon' => $this->Menu->getMenuIcon('Edit'),
                        'url' => $this->Html->url(
                          array(
                            'controller' => 'co_person_roles',
                            'action' => 'edit',
                            $r['id'])
                        ),
                        'label' => _txt('op.edit'),
                      );
                    }

                    if ($permissions['relink']) {
                      $rlnk_url = array(
                        'controller' => 'co_people',
                        'action' => 'relink',
                        $co_people[0]['CoPerson']['id'],
                        'copersonroleid' => $r['id'],
                      );

                      // Relink button
                      $action_args['vv_actions'][] = array(
                        'url' => 'javascript:void(0);',
                        'order' => $this->Menu->getMenuOrder('Relink'),
                        'icon' => $this->Menu->getMenuIcon('Relink'),
                        'label' => _txt('op.relink'),
                        'onclick' => array(
                          'dg_bd_txt' => _jtxt(_txt('op.relink.role.confirm')),
                          'dg_url' => $this->Html->url($rlnk_url),
                          'dg_conf_btn' => _txt('op.ok'),
                          'dg_cancel_btn' => _txt('op.cancel'),
                          'dg_title' => _txt('op.confirm'),
                          'db_bd_txt_repl_str' => '',
                        ),
                      );
                    }

                    if ($permissions['delete']) {
                      $dr_url = array(
                        'controller' => 'co_person_roles',
                        'action' => 'delete',
                        $r['id'],
                      );

                      // Delete button
                      $action_args['vv_actions'][] = array(
                        'url' => 'javascript:void(0);',
                        'order' => $this->Menu->getMenuOrder('Delete'),
                        'icon' => $this->Menu->getMenuIcon('Delete'),
                        'label' => _txt('op.delete'),
                        'onclick' => array(
                          'dg_bd_txt' => _txt('js.remove'),
                          'dg_url' => $this->Html->url($dr_url),
                          'dg_conf_btn' => _txt('op.remove'),
                          'dg_cancel_btn' => _txt('op.cancel'),
                          'dg_title' => _txt('op.remove'),
                          'db_bd_txt_repl_str' => filter_var(_jtxt(generateCn($co_people[0]['PrimaryName'])), FILTER_SANITIZE_STRING),
                        ),
                      );
                    }
                  } elseif ($permissions['view']) {
                    $action_args['vv_actions'][] = array(
                      'order' => $this->Menu->getMenuOrder('View'),
                      'icon' => $this->Menu->getMenuIcon('View'),
                      'url' => $this->Html->url(
                        array(
                          'controller' => 'co_person_roles',
                          'action' => 'view',
                          $r['id'])
                      ),
                      'label' => _txt('op.view'),
                    );
                  }

                  print '<div class="field-actions">';
                  if(!empty($action_args['vv_actions'])) {
                    print $this->element('menuAction', $action_args);
                  }
                  print '</div>';
                  // Construct the array with the fields for the card
                  $card_role_args = array();
                  $card_role_args[_txt('fd.ordr')] = !empty($r['ordr']) ? filter_var($r['ordr'], FILTER_SANITIZE_SPECIAL_CHARS) : '-';
                  $card_role_args[_txt('fd.cou')] = !empty($r['Cou']['name']) ? filter_var($r['Cou']['name'], FILTER_SANITIZE_SPECIAL_CHARS) : '-';
                  $card_role_args[_txt('fd.title')] = !empty($r['title']) ? filter_var($r['title'], FILTER_SANITIZE_SPECIAL_CHARS) : '-';
                  $card_role_args[_txt('fd.affiliation')] = !empty($r['affiliation']) ? filter_var($vv_copr_affiliation_types[$r['affiliation']], FILTER_SANITIZE_SPECIAL_CHARS) : '-';
                  $card_role_args[_txt('fd.o')] = !empty($r['o']) ? filter_var($r['o'], FILTER_SANITIZE_SPECIAL_CHARS) : '-';
                  $card_role_args[_txt('fd.valid_from')] = !empty($r['valid_from']) ? filter_var($this->Time->format($r['valid_from'], "%F %T", false, $vv_tz), FILTER_SANITIZE_SPECIAL_CHARS) : '-';
                  $card_role_args[_txt('fd.valid_through')] = !empty($r['valid_through']) ? filter_var($this->Time->format($r['valid_through'], "%F %T", false, $vv_tz), FILTER_SANITIZE_SPECIAL_CHARS) : '-';
                  $card_role_args[_txt('fd.status')] = $status;
                  ?>
                  <!-- Collapsed data -->
                  <ul class="field-children collapse" id="collapse_link_<?php print md5("CoPersonRole" . $r['id']); ?>">
                    <li class="card-data-container">
                    <?php foreach($card_role_args as $role_col => $value): ?>
                      <div>
                        <div><?php print $value; ?></div>
                        <span class="smaller text-muted-cmg"><cite><?php print $role_col; ?></cite></span>
                      </div>
                    <?php endforeach; ?>
                    </li>
                  </ul>
                  <?php
                  // Make this an element
                } // foreach
              } // invite
              ?>
          </ul><!-- tabs-role -->
        </li><!-- fields-role -->

        <!-- Org Ids -->
        <li id="fields-orgid" class="fieldGroup">
          <div class="fieldGroupNameContainer">
            <a href="#tabs-orgid" class="fieldGroupName" title="<?php print _txt('op.collapse') ?>" aria-expanded="true" aria-controls="tabs-orgid">
              <em class="material-icons" aria-hidden="true">expand_less</em>
              <h2><?php print _txt('ct.org_identities.pl'); ?></h2>
            </a>
            <?php
            if(isset($permissions['link']) && $permissions['link']) {
              print '<div class="coAddEditButtons">';
              $linktarget = array(
                'controller' => 'org_identities',
                'action'     => 'find',
                'copersonid' => $co_people[0]['CoPerson']['id']
              );
              $linkparams = array('class' => 'addbutton');
  
              print $this->Html->link(_txt('op.link'),
                $linktarget,
                $linkparams);
              print '</div>';
            }
            ?>
          </div>
          <ul id="tabs-orgid" class="fields data-list">
            <?php
            if(!empty($co_people[0]['CoOrgIdentityLink'])){
              foreach($co_people[0]['CoOrgIdentityLink'] as $link) {
                // Organization Name and Id
                $orgid_name = empty($link['OrgIdentity']['PrimaryName'])
                    ? '<cite class="text-muted-cmg"><em class="mr-1 material-icons">expand_less</em>empty</cite>'
                    : '<em class="material-icons" aria-hidden="true">expand_less</em> ' . filter_var(generateCn($link['OrgIdentity']['PrimaryName']),FILTER_SANITIZE_SPECIAL_CHARS);

                $identifier = array();
                if(!empty($link['OrgIdentity']['Identifier'])) {
                  foreach($link['OrgIdentity']['Identifier'] as $id) {
                    if(!empty($id['identifier'])
                      && $id['login']
                      && $id['status'] == StatusEnum::Active) {
                      $identifier[filter_var($id['type'],FILTER_SANITIZE_SPECIAL_CHARS)] = filter_var($id['identifier'],FILTER_SANITIZE_SPECIAL_CHARS);
                    }
                  }
                }
                if(!empty($identifier)) {
                  $badge_list_org[] = array(
                    'order' => $this->Badge->getBadgeOrder('Status'),
                    'text' => _txt('op.login'),
                    'color' => $this->Badge->getBadgeColor('Light'),
                    'outline' => true,
                  );
                }
                // XXX  For OrgIdentity.affiliation we want to use the CoPersonRole values
                //      AppModel::types($coId, $attribute)
                if(!empty($link["OrgIdentity"]["affiliation"])) {
                  $badge_list_org[] = array(
                    'order' => $this->Badge->getBadgeOrder('Title'),
                    'text' => filter_var($vv_copr_affiliation_types[$link["OrgIdentity"]["affiliation"]], FILTER_SANITIZE_SPECIAL_CHARS),
                    'color' => $this->Badge->getBadgeColor('Light'),
                  );
                }

                print '<li class="field-data-container centered">';
                print '<div class="field-data flex-column">';
                $linkparams = array(
                  'id' => 'role-content_' . md5("OrgIdentity" . $link['OrgIdentity']['id']),
                  'class' => 'collapsed',
                  'aria-controls' => 'collapse_link_' . md5("OrgIdentity" . $link['OrgIdentity']['id']),
                  'escape' => false,
                  'data-toggle' => 'collapse',
                  'aria-haspopup' => 'true',
                  'aria-expanded' => 'false',
                );
                // Name
                print $this->Html->link(
                  $orgid_name . ' <cite class="text-muted-cmg cm-id-display">' . _txt('fd.id.val', array(filter_var($link['OrgIdentity']['id'],FILTER_SANITIZE_SPECIAL_CHARS))) . '</cite>',
                  '#collapse_link_' . md5("OrgIdentity" . $link['OrgIdentity']['id']),
                  $linkparams
                );
                // Identifier list
                foreach($identifier as $type => $ident) {
                  $ident_badge_type = $this->element('badgeList',
                    array(
                      'vv_badge_list' =>
                        array(
                          array(
                            'text' => $vv_identifiers_types[$type],
                            'color' => $this->Badge->getBadgeColor('Secondary'),
                            'outline' => true,
                          ),
                        )
                    )
                  );
                  print $this->Html->tag(
                    'span',
                    $ident . ' ' . $ident_badge_type,
                    array(
                      'class' => 'smaller text-muted-cmg pl-2 force-wrap',
                      'escape' => false,
                    )
                  );
                }
                print '</div>';
                // Status/Affiliation
                print '<div class="field-data data-label">';
                if(!empty($badge_list_org)) {
                  print $this->element('badgeList', array('vv_badge_list' => $badge_list_org));
                  unset($badge_list_org);
                }
                print '</div>';
                // Actions

                $action_args = array();
                $action_args['vv_attr_mdl'] = "OrgIdentity";
                $action_args['vv_attr_id'] = $link['OrgIdentity']['id'];

                // Edit / View Action
                $label = ($e && !$es) ? 'op.edit' : 'op.view';
                $action = ($e && !$es) ? 'edit' : 'view';
                $order = ($e && !$es) ? $this->Menu->getMenuOrder('Edit') : $this->Menu->getMenuOrder('View');
                $icon = ($e && !$es) ? $this->Menu->getMenuIcon('Edit') : $this->Menu->getMenuIcon('View');
                $action_args['vv_actions'][] = array(
                  'order' => $order,
                  'icon' => $icon,
                  'url' => $this->Html->url(
                    array(
                      'controller' => 'org_identities',
                      'action' => $action,
                      $link['OrgIdentity']['id'])
                  ),
                  'label' => _txt($label),
                );

                // Relink Action
                if($permissions['relink']) {
                  $rlnk_url = array(
                    'controller' => 'co_people',
                    'action' => 'relink',
                    $co_people[0]['CoPerson']['id'],
                    'linkid' => $link['id'],
                  );

                  // Relink button
                  $action_args['vv_actions'][] = array(
                    'order' => $this->Menu->getMenuOrder('Relink'),
                    'icon' => $this->Menu->getMenuIcon('Relink'),
                    'url' => 'javascript:void(0);',
                    'label' => _txt('op.relink'),
                    'onclick' => array(
                      'dg_bd_txt' => _jtxt(_txt('op.relink.confirm')),
                      'dg_url' => $this->Html->url($rlnk_url),
                      'dg_conf_btn' => _txt('op.ok'),
                      'dg_cancel_btn' => _txt('op.cancel'),
                      'dg_title' => _txt('op.confirm'),
                      'db_bd_txt_repl_str' => '',
                    ),
                  );
                }

                // Delete Action
                if($permissions['delete']) {
                  $unlnk_url = array(
                    'controller' => 'co_org_identity_links',
                    'action' => 'delete',
                    $link['id'],
                  );

                  // Unlink button
                  $action_args['vv_actions'][] = array(
                    'order' => $this->Menu->getMenuOrder('Unlink'),
                    'icon' => $this->Menu->getMenuIcon('Unlink'),
                    'url' => 'javascript:void(0);',
                    'label' => _txt('op.unlink'),
                    'onclick' => array(
                      'dg_bd_txt' => _jtxt(_txt('op.unlink.confirm')),
                      'dg_url' => $this->Html->url($unlnk_url),
                      'dg_conf_btn' => _txt('op.ok'),
                      'dg_cancel_btn' => _txt('op.cancel'),
                      'dg_title' => _txt('op.confirm'),
                      'db_bd_txt_repl_str' => '',
                    ),
                  );
                }


                print '<div class="field-actions">';
                if(!empty($action_args['vv_actions'])) {
                  print $this->element('menuAction', $action_args);
                }
                print '</div>';

                $card_org_args = array();
                $card_org_args[_txt('fd.name')] = !empty($link['OrgIdentity']['PrimaryName'])
                                                  ? filter_var(generateCn($link['OrgIdentity']['PrimaryName']),FILTER_SANITIZE_SPECIAL_CHARS)
                                                  : '-';
                $card_org_args[_txt('fd.source')] = !empty($link['OrgIdentity']['OrgIdentitySourceRecord']['OrgIdentitySource']['description'])
                                                    ? filter_var($link['OrgIdentity']['OrgIdentitySourceRecord']['OrgIdentitySource']['description'] ,FILTER_SANITIZE_SPECIAL_CHARS)
                                                    : '-';
                $card_org_args[_txt('fd.o')] = !empty($link['OrgIdentity']['o'])
                                               ? filter_var($link['OrgIdentity']['o'], FILTER_SANITIZE_SPECIAL_CHARS) : '-';
                $card_org_args[_txt('fd.status')] = !empty($link['OrgIdentity']['status'])
                                                    ? filter_var(
                                                        $cm_texts[ $cm_lang ]['en.status.org'][ $link['OrgIdentity']['status'] ],
                                                        FILTER_SANITIZE_SPECIAL_CHARS) : '-';
                $card_org_args[_txt('fd.affiliation')] = !empty($link['OrgIdentity']['affiliation'])
                                               ? filter_var($vv_copr_affiliation_types[$link["OrgIdentity"]["affiliation"]], FILTER_SANITIZE_SPECIAL_CHARS) : '-';
                $card_org_args[_txt('fd.date_of_birth')] = !empty($link['OrgIdentity']['date_of_birth'])
                                               ? filter_var($link['OrgIdentity']['date_of_birth'], FILTER_SANITIZE_SPECIAL_CHARS) : '-';
                $card_org_args[_txt('fd.valid_from.tz', array($vv_tz))] = (isset($link['OrgIdentity']['valid_from']) && $link['OrgIdentity']['valid_from'] > 0)
                                                                          ? $this->Time->format($link['OrgIdentity']['valid_from'], "%F %T", false, $vv_tz) : '-';
                $card_org_args[_txt('fd.valid_through.tz', array($vv_tz))] = (isset($link['OrgIdentity']['valid_through']) && $link['OrgIdentity']['valid_through'] > 0)
                                                                             ? $this->Time->format($link['OrgIdentity']['valid_through'], "%F %T", false, $vv_tz) : '-';
                ?>
                <!-- Collapsed data -->
                <ul class="field-children collapse" id="collapse_link_<?php print md5("OrgIdentity" . $link['OrgIdentity']['id']); ?>">
                  <li class="card-data-container">
                    <?php foreach($card_org_args as $org_col => $value): ?>
                      <div>
                        <div><?php print $value; ?></div>
                        <span class="smaller text-muted-cmg"><cite><?php print $org_col; ?></cite></span>
                      </div>
                    <?php endforeach; ?>
                  </li>
                </ul>
                <?php

              }
            }

            ?>
          </ul><!-- tabs-orgid -->
        </li><!-- fields-orgid -->
      </ul>
    </div> <!-- personExplorer -->
    <?php print $this->element("changelog"); ?>
  </div> <!-- explorerContainer -->

<?php else: // invite ?>

  <div id="tabs-attributes">
    <div class="fields">
      <div class="modelbox">
        <div class="boxtitle"><strong><?php print _txt('fd.name'); ?></strong></div>
        <div class="modelbox-data">
          <div class="form-group">
            <?php
              print $this->Form->hidden('PrimaryName.id');
              print $this->Form->hidden('PrimaryName.type', array('default' => NameEnum::Official));
              print $this->Form->hidden('PrimaryName.primary_name', array('default' => true));

              if($e) {
                print $this->Form->label('PrimaryName.honorific', _txt('fd.name.honorific') . ' ' . _txt('fd.name.h.desc'));
                print $this->Form->input('PrimaryName.honorific',
                  array('default' => $co_people[0]['PrimaryName']['honorific'],'class' => 'form-control focusFirst'));
              } else {
                filter_var($co_people[0]['PrimaryName']['honorific'],FILTER_SANITIZE_SPECIAL_CHARS);
              }
            ?>
          </div>
          <div class="form-group">
            <?php
              if($e) {
                print $this->Form->label('PrimaryName.given', _txt('fd.name.given'));
                print '<span class="required">*</span>';
                print $this->Form->input('PrimaryName.given',
                  array('default' => $co_people[0]['PrimaryName']['given'], 'class' => 'form-control'));
              } else {
                print filter_var($co_people[0]['PrimaryName']['given'],FILTER_SANITIZE_SPECIAL_CHARS);
              }
            ?>
          </div>
          <div class="form-group">
            <?php
              if($e) {
                print $this->Form->label('PrimaryName.middle', _txt('fd.name.middle'));
                print $this->Form->input('PrimaryName.middle',
                  array('default' => $co_people[0]['PrimaryName']['middle'], 'class' => 'form-control'));
              } else {
                print filter_var($co_people[0]['PrimaryName']['middle'],FILTER_SANITIZE_SPECIAL_CHARS);
              }
            ?>
          </div>
          <div class="form-group">
            <?php
              if($e) {
                print $this->Form->label('PrimaryName.family', _txt('fd.name.family'));
                print $this->Form->input('PrimaryName.family',
                  array('default' => $co_people[0]['PrimaryName']['family'], 'class' => 'form-control'));
              } else {
                print filter_var($co_people[0]['PrimaryName']['family'],FILTER_SANITIZE_SPECIAL_CHARS);
              }
            ?>
          </div>
          <div class="form-group">
            <?php
              if($e) {
                print $this->Form->label('PrimaryName.suffix', _txt('fd.name.suffix') . ' ' . _txt('fd.name.s.desc'));
                print $this->Form->input('PrimaryName.suffix',
                  array('default' => $co_people[0]['PrimaryName']['suffix'], 'class' => 'form-control'));
              } else {
                print filter_var($co_people[0]['PrimaryName']['suffix'],FILTER_SANITIZE_SPECIAL_CHARS);
              }
            ?>
          </div>
          <div class="form-group">
            <?php print _txt('fd.type') . ': '; ?>
            <?php // CO-955 nametype official needs to exist, but probably should be configurable ?>
            <?php print _txt('en.name.type', null, NameEnum::Official); ?>
          </div>
          <div class="form-group">
            <?php
              if($e) {
                global $cm_lang, $cm_texts;

                $attrs = array();
                $attrs['value'] = (isset($co_people[0]['PrimaryName']['language'])
                  ? $co_people[0]['PrimaryName']['language']
                  : getPreferredLanguage());
                $attrs['empty'] = true;
                $attrs['class'] = 'co-selectfield form-control';

                print $this->Form->label('PrimaryName.language', _txt('fd.language'));
                print $this->Form->select('PrimaryName.language',
                  $cm_texts[ $cm_lang ]['en.language'],
                  $attrs);

                if($this->Form->isFieldError('PrimaryName.language')) {
                  print $this->Form->error('PrimaryName.language');
                }
              } else {
                if(!empty($co_people[0]['PrimaryName']['language'])) {
                  print _txt('fd.language') . ': ';
                  print _txt('en.language', null, $co_people[0]['PrimaryName']['language']);
                }
              }
            ?>
          </div>
        </div>
      </div>

      <?php if($e): ?>
        <div class="submit-box">
          <div class="required-info">
            <em><span class="required"><?php print _txt('fd.req'); ?></span></em>
          </div>
          <div class="submit-buttons">
            <div class="submit">
              <?php
                print $this->Form->submit($submit_label, array('class' => 'spin submit-button btn btn-primary'));
              ?>
            </div>
          </div>
        </div>
      <?php endif; ?>

    </div>
  </div>

<?php endif; ?>

<div id="autogenerate-dialog" class="co-dialog" title="<?php print _txt('op.id.auto'); ?>">
  <?php print _txt('op.id.auto.confirm'); ?>
</div>
