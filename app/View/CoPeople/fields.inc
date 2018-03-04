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
  $args = array();
  $args['plugin'] = null;
  $args['controller'] = 'co_people';
  $args['action'] = 'index';
  $args['co'] = $cur_co['Co']['id'];
  $this->Html->addCrumb(_txt('me.population'), $args);
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
      
      if($permissions['expunge']) {
        $sidebarButtons[] = array(
          'icon'    => 'circle-close',
          'title'   => _txt('op.expunge'),
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
    // Explorer menu toggles
    $(".fieldGroupName").click(function(event) {
      event.preventDefault();
      $(this).next(".fields").slideToggle("fast");
      // toggle the +/- icon:
      if ($(this).find(".material-icons").text() == "indeterminate_check_box") {
        $(this).find(".material-icons").text("add_box");
      } else {
        $(this).find(".material-icons").text("indeterminate_check_box");
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
            'copersonid' => $co_people[0]['CoPerson']['id'],
            'co' => $cur_co['Co']['id'])); ?>";
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

<div id="<?php print $this->action; ?>_co_person" class="explorerContainer">
  <div id="coPersonExplorer" class="personExplorer">
    <ul>

      <!-- Names -->
      <li id="fields-name" class="fieldGroup">
        <?php if($this->action != 'invite' && $this->Permission->selfService($permissions, $e, 'Name') == PermissionEnum::ReadWrite): ?>
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
              //'escape' => false
            );

            //print $this->Html->link('<span class="ui-button-icon-primary ui-icon ui-icon-circle-plus"></span>' . _txt('op.add'),
            print $this->Html->link(_txt('op.add'),
              $linktarget,
              $linkparams);
            ?>
          </div>
        <?php endif; // invite+permission ?>

        <a href="#tabs-name" class="fieldGroupName">
          <em class="material-icons">indeterminate_check_box</em>
          <?php print _txt('fd.name'); ?>
        </a>

        <ul id="tabs-name" class="fields data-list">
          <?php if($this->action == 'invite'): ?>
            <li class="field-data-container">
              <table>
                <tr class="line<?php print ($l % 2); $l++; ?>">
                  <th>
                    <?php
                    print _txt('fd.name.honorific');

                    if($e)
                      print " " . _txt('fd.name.h.desc');
                    ?>
                  </th>
                  <td>
                    <?php
                    print $this->Form->hidden('PrimaryName.id');
                    print $this->Form->hidden('PrimaryName.type', array('default' => NameEnum::Official));
                    print $this->Form->hidden('PrimaryName.primary_name', array('default' => true));
                    print ($e ? $this->Form->input('PrimaryName.honorific',
                      array('default' => $co_people[0]['PrimaryName']['honorific']))
                      : filter_var($co_people[0]['PrimaryName']['honorific'],FILTER_SANITIZE_SPECIAL_CHARS));
                    ?>
                  </td>
                </tr>
                <tr class="line<?php print ($l % 2); $l++; ?>">
                  <th>
                    <?php print _txt('fd.name.given'); ?><span class="required">*</span>
                  </th>
                  <td>
                    <?php print ($e ? $this->Form->input('PrimaryName.given',
                      array('default' => $co_people[0]['PrimaryName']['given']))
                      : filter_var($co_people[0]['PrimaryName']['given'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
                  </td>
                </tr>
                <tr class="line<?php print ($l % 2); $l++; ?>">
                  <th>
                    <?php print _txt('fd.name.middle'); ?>
                  </th>
                  <td>
                    <?php print ($e ? $this->Form->input('PrimaryName.middle',
                      array('default' => $co_people[0]['PrimaryName']['middle']))
                      : filter_var($co_people[0]['PrimaryName']['middle'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
                  </td>
                </tr>
                <tr class="line<?php print ($l % 2); $l++; ?>">
                  <th>
                    <?php print _txt('fd.name.family'); ?>
                  </th>
                  <td>
                    <?php print ($e ? $this->Form->input('PrimaryName.family',
                      array('default' => $co_people[0]['PrimaryName']['family']))
                      : filter_var($co_people[0]['PrimaryName']['family'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
                  </td>
                </tr>
                <tr class="line<?php print ($l % 2); $l++; ?>">
                  <th>
                    <?php
                    print _txt('fd.name.suffix');
                    if($e)
                      print " " . _txt('fd.name.s.desc');
                    ?>
                  </th>
                  <td>
                    <?php print ($e ? $this->Form->input('PrimaryName.suffix',
                      array('default' => $co_people[0]['PrimaryName']['suffix']))
                      : filter_var($co_people[0]['PrimaryName']['suffix'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
                  </td>
                </tr>
                <tr class="line<?php print ($l % 2); $l++; ?>">
                  <th>
                    <?php print _txt('fd.type'); ?>
                  </th>
                  <td>
                    <?php // CO-955 nametype official needs to exist, but probably should be configurable ?>
                    <?php print _txt('en.name.type', null, NameEnum::Official); ?>
                  </td>
                </tr>
                <tr class="line<?php print ($l % 2); $l++; ?>">
                  <th>
                    <?php print _txt('fd.language'); ?>
                  </th>
                  <td>
                    <?php
                    global $cm_lang, $cm_texts;

                    $attrs = array();
                    $attrs['value'] = (isset($co_people[0]['PrimaryName']['language'])
                      ? $co_people[0]['PrimaryName']['language']
                      : getPreferredLanguage());
                    $attrs['empty'] = true;

                    if($e) {
                      print $this->Form->select('PrimaryName.language',
                        $cm_texts[ $cm_lang ]['en.language'],
                        $attrs);

                      if($this->Form->isFieldError('PrimaryName.language')) {
                        print $this->Form->error('PrimaryName.language');
                      }
                    } else {
                      if(!empty($co_people[0]['PrimaryName']['language'])) {
                        print _txt('en.language', null, $co_people[0]['PrimaryName']['language']);
                      }
                    }
                    ?>
                  </td>
                </tr>
                <tr>
                  <th>
                    <em><span class="required"><?php print _txt('fd.req'); ?></span></em><br />
                  </th>
                  <td>
                    <?php
                    if($e) {
                      print $this->Form->submit($submit_label);
                    }
                    ?>
                  </td>
                </tr>
              </table>
            </li>
          <?php else: // invite ?>
          <?php foreach($co_people[0]['Name'] as $n): ?>
            <li class="field-data-container">
              <?php
                $perm = $this->Permission->selfService($permissions, $e, 'Name', (!empty($n['type']) ? $n['type'] : null));
              ?>
              <div class="field-data <?php if($perm == PermissionEnum::ReadOnly) { print ' field-data-alone'; }; ?>">
                <?php
                if($perm == PermissionEnum::ReadWrite) {
                  print $this->Html->link(generateCn($n),
                    array('controller' => 'names',
                          'action' => 'edit',
                          $n['id']));
                } elseif($perm == PermissionEnum::ReadOnly) {
                  print filter_var(generateCn($n),FILTER_SANITIZE_SPECIAL_CHARS);
                }

                  print " ("
                    . ($n['primary_name'] ? (_txt('fd.name.primary_name') . ", ") : "")
                    . (!empty($n['SourceName']['id'])
                      ? filter_var($n['SourceName']['OrgIdentity']['OrgIdentitySourceRecord']['OrgIdentitySource']['description'],FILTER_SANITIZE_SPECIAL_CHARS) . ", "
                      : "")
                    . filter_var($vv_cop_name_types[ $n['type'] ],FILTER_SANITIZE_SPECIAL_CHARS)
                    . (!empty($n['language']) ? (", " . _txt('en.language', null, $n['language'])) : "")
                    . ")";
                ?>
              </div>
              <div class="field-actions">
                <?php
                  if(!empty($n['SourceName']['id'])) {
                    // This name came from an OIS and so can't be edited, and for the moment
                    // can't be made primary (though maybe that'll change with a use case).
                    print $this->Html->link(_txt('op.view'),
                      array('controller' => 'names',
                        'action' => 'view',
                        $n['id']),
                      array('class' => 'viewbutton'));
                  } else {
                    if ($perm == PermissionEnum::ReadWrite) {
                      if (!$n['primary_name']) {
                        print '<a class="deletebutton" title="' . _txt('op.delete')
                          . '" onclick="javascript:js_confirm_generic(\''
                          . _txt('js.remove') . '\',\''    // dialog body text
                          . $this->Html->url(              // dialog confirm URL
                            array(
                              'controller' => 'names',
                              'action' => 'delete', $n['id'],
                              'co' => $cur_co['Co']['id']
                            )
                          ) . '\',\''
                          . _txt('op.remove') . '\',\''    // dialog confirm button
                          . _txt('op.cancel') . '\',\''    // dialog cancel button
                          . _txt('op.remove') . '\',[\''   // dialog title
                          . filter_var(_jtxt(generateCn($n)), FILTER_SANITIZE_STRING)  // dialog body text replacement strings
                          . '\']);">'
                          . _txt('op.delete')
                          . '</a>';

                        print $this->Html->link(_txt('op.primary'),
                          array('controller' => 'names',
                            'action' => 'primary',
                            $n['id'],
                            'copersonid' => $co_people[0]['CoPerson']['id']),
                          array('class' => 'primarybutton'));
                      }

                      print $this->Html->link(_txt('op.edit'),
                          array('controller' => 'names',
                            'action' => 'edit',
                            $n['id']),
                          array('class' => 'editbutton')) . "\n";
                    }
                  }  
                ?>
              </div>
            </li>
            <?php endforeach; ?>
          <?php endif; // invite ?>
        </ul><!-- tabs-name -->
      </li><!-- fields-name -->

      <?php
        if($this->action != "invite") {
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
        }
      ?>

      <?php if($this->action != "invite"): ?>
        <!-- Groups -->
        <li id="fields-group" class="fieldGroup">
          <?php
          if($e) {
            print '<div class="coAddEditButtons">';
            $linktarget = array(
              'controller' => 'co_groups',
              'action'     => 'select',
              'copersonid' => $co_people[0]['CoPerson']['id']
            );
            $linkparams = array('class' => 'addbutton');
            print $this->Html->link(_txt('op.manage.grm'),
                $linktarget,
                $linkparams) . "\n";
            print '</div>';
          }
          ?>
          <a href="#tabs-group" class="fieldGroupName">
            <em class="material-icons">indeterminate_check_box</em>
            <?php print _txt('fd.groups'); ?>
          </a>
          <ul id="tabs-group" class="fields data-list">
            <?php
            if(isset($co_people[0]['CoGroupMember'])) {
              foreach($co_people[0]['CoGroupMember'] as $g) {
                if($e && !$es && !$g['CoGroup']['auto']) {
                  if($g['member'] || $g['owner']) {
                    print '<li class="field-data-container"><div class="field-data">';
                    print $this->Html->link($g['CoGroup']['name'], array('controller' => 'co_groups', 'action' => 'edit', $g['co_group_id'], 'co' => $cur_co['Co']['id']));
                    if($g['owner']) {
                      if($g['member']) {
                        print ' ' . _txt('fd.group.grmemown');
                      } else {
                        print ' ' . _txt('fd.group.own.only');
                      }
                    } elseif($g['member']) {
                      print ' ' . _txt('fd.group.mem');
                    }
                    
                    // Warn if membership is invalid
                    if((!empty($g['valid_from'])
                        && strtotime($g['valid_from']) >= time())
                       ||
                       (!empty($g['valid_through'])
                        && strtotime($g['valid_through']) < time())) {
                      print " (" . _txt('fd.inactive') . ")";
                    }

                    print '</div><div class="field-actions">';
                    print $this->Html->link(_txt('op.edit'),
                      array('controller' => 'co_group_members',
                            'action' => 'edit',
                            $g['id']),
                      array('class' => 'editbutton'));
                    
                    // XXX we already checked for $permissions['edit'], but not ['delete']... should we?
                    print '<a class="deletebutton" title="' . _txt('op.delete')
                      . '" onclick="javascript:js_confirm_generic(\''
                      . _txt('js.remove.member') . '\',\''    // dialog body text
                      . $this->Html->url(                     // dialog confirm URL
                        array(
                          'controller' => 'co_group_members',
                          'action' => 'delete', $g['id'],
                          'copersonid' => $co_people[0]['CoPerson']['id'],
                          'return' => 'person'
                        )
                      ) . '\',\''
                      . _txt('op.remove') . '\',\''    // dialog confirm button
                      . _txt('op.cancel') . '\',\''    // dialog cancel button
                      . _txt('op.remove') . '\',[\''   // dialog title
                      . filter_var(_jtxt($g['CoGroup']['name']),FILTER_SANITIZE_STRING)  // dialog body text replacement strings
                      . '\']);">'
                      . _txt('op.delete')
                      . '</a>';

                    if(!empty($g['source_org_identity_id'])) {
                      print $this->Html->link(_txt('op.view.source'),
                        array('controller' => 'org_identities',
                          'action' => 'view',
                          $g['source_org_identity_id']),
                        array('class' => 'viewbutton'));
                    }
                    print '</div></li>';
                  }
                } else {
                  if($g['member'])
                    print '<li class="field-data-container"><div class="field-data field-data-alone">' . filter_var($g['CoGroup']['name'],FILTER_SANITIZE_SPECIAL_CHARS) . "</div></li>\n";
                  if($g['owner'])
                    print '<li class="field-data-container"><div class="field-data field-data-alone">' . filter_var($g['CoGroup']['name'],FILTER_SANITIZE_SPECIAL_CHARS) . " (" . _txt('fd.group.own') . ")</div></li>\n";
                }
              }
            }
            ?>
          </ul><!-- tabs-group -->
        </li><!-- fields-group -->

        <!-- Person attributes form -->
        <li id="fields-coperson" class="fieldGroup">
          <?php
            print $this->Form->create('CoPerson', array('url' => array('action'=>'edit','id'=>'CoPersonEditAttributesForm')));
            print $this->Form->hidden('CoPerson.co_id', array('default' => $co_people[0]['CoPerson']['co_id'],'id'=>'CoPersonCoIdAgain')). "\n";
          ?>
          <a href="#tabs-coperson" class="fieldGroupName">
            <em class="material-icons">indeterminate_check_box</em>
            <?php print _txt('fd.attrs.cop'); ?>
          </a>
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
                  $linkparams) . "\n";
              print '</div>';
            }
          ?>
          <a href="#tabs-role" class="fieldGroupName">
            <em class="material-icons">indeterminate_check_box</em>
            <?php print _txt('fd.attrs.copr'); ?>
          </a>
          <ul id="tabs-role" class="fields data-list data-table">
            <li>
              <?php if($this->action != "invite"): ?>
                <!-- Person Role Data -->
                <table id="<?php print $this->action; ?>_co_person_roles">
                  <tbody>
                  <tr class="line<?php print ($l % 2); $l++; ?>">
                    <th><?php print _txt('fd.ordr'); ?></th>
                    <th><?php print _txt('fd.cou'); ?></th>
                    <th><?php print _txt('fd.title'); ?></th>
                    <th><?php print _txt('fd.affiliation'); ?></th>
                    <th><?php print _txt('fd.valid_from'); ?></th>
                    <th><?php print _txt('fd.valid_through'); ?></th>
                    <th><?php print _txt('fd.status'); ?></th>
                    <th><?php print _txt('fd.actions'); ?></th>
                  </tr>
                  <?php
                  $rcnt = 1;
                  foreach($co_people[0]['CoPersonRole'] as $r):

                    // Set the status styling
                    $statusClass = "status-normal";

                    if($r['status'] == StatusEnum::Suspended 
                       || $r['status'] == StatusEnum::Expired
                       || $r['status'] == StatusEnum::Deleted) {
                      // Style status for suspended, expired, deleted
                      $statusClass = "warn-level-a"; // reddish-pink
                    } elseif($r['status'] != StatusEnum::Active) {
                      // Style status if otherwise not active
                      $statusClass = "warn-level-b"; // yellowish
                    }

                    // Independent of status, set the style based on dates if needed:
                    $validThroughDate = !empty($r['valid_through']) ? $this->Time->format($r['valid_through'], "%F", false, $vv_tz) : false;
                    $validFromDate = !empty($r['valid_from']) ? $this->Time->format($r['valid_from'], "%F", false, $vv_tz) : false;

                    if ($validThroughDate && $this->Time->isPast($validThroughDate)) {
                      // valid through date exists and is in the past
                      $statusClass = "warn-level-a";
                    } elseif ($validFromDate && $this->Time->isFuture($validFromDate)) {
                      // valid from date is in the future
                      $statusClass = "warn-level-b";
                    }
                    
                  ?>
                  <tr class="line<?php print ($l % 2) . ' ' . $statusClass; $l++; ?>">
                    <td><?php if (isset($r['ordr'])) {
                        print filter_var($r['ordr'], FILTER_SANITIZE_SPECIAL_CHARS);
                      } ?></td>
                    <td><?php if (isset($r['Cou']['name'])) {
                        print filter_var($r['Cou']['name'], FILTER_SANITIZE_SPECIAL_CHARS);
                      } ?></td>
                    <td><?php if (isset($r['title'])) {
                        print filter_var($r['title'], FILTER_SANITIZE_SPECIAL_CHARS);
                      } ?></td>
                    <td><?php if (isset($r['affiliation'])) {
                        print $vv_copr_affiliation_types[$r['affiliation']];
                      } ?></td>
                    <td>
                      <?php
                        if (isset($r['valid_from']) && $r['valid_from'] > 0) {
                          print $this->Time->format($r['valid_from'], "%F %T", false, $vv_tz);
                        }
                      ?>
                    </td>
                    <td>
                      <?php
                        if (isset($r['valid_through']) && $r['valid_through'] > 0) {
                          print $this->Time->format($r['valid_through'], "%F %T", false, $vv_tz);
                        }
                      ?>
                    </td>
                    <td>
                      <?php 
                        if (isset($r['status'])) {
                          print _txt('en.status', null, $r['status']);
                        } 
                      ?>
                    </td>
                    <td>
                      <?php
                        if ($es // Editing self
                          || empty($r['Cou']) // No COU set for this person
                          || (isset($r['Cou']['name']) && in_array($r['Cou']['name'], $permissions['cous']))
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
                            print $this->Html->link(_txt('op.petition'),
                              array('controller' => 'co_petitions',
                                'action' => 'view',
                                $r['CoPetition'][0]['id'],
                                'co' => $r['CoPetition'][0]['co_id'],
                                'coef' => $r['CoPetition'][0]['co_enrollment_flow_id']),
                              array('class' => 'petitionbutton'));
                          }

                          if ($permissions['canvas']) {
                            print $this->Html->link(_txt('op.edit'),
                                array('controller' => 'co_person_roles', 'action' => 'edit', $r['id']),
                                array('class' => 'editbutton')) . "\n";
                          }

                          if ($permissions['relink']) {
                            print '<a class="relinkbutton" title="' . _txt('op.relink') . '" onclick="javascript:js_confirm_generic(\'' . _jtxt(_txt('op.relink.role.confirm')) . '\', \'' . $this->Html->url(array('controller' => 'co_people', 'action' => 'relink', $co_people[0]['CoPerson']['id'], 'copersonroleid' => $r['id'])) . '\');">' . _txt('op.relink') . '</a>' . "\n";
                          }

                          if ($permissions['delete']) {
                            print '<button type="button" class="deletebutton" title="' . _txt('op.delete')
                              . '" onclick="javascript:js_confirm_generic(\''
                              . _txt('js.remove') . '\',\''    // dialog body text
                              . $this->Html->url(              // dialog confirm URL
                                array(
                                  'controller' => 'co_person_roles',
                                  'action' => 'delete',
                                  $r['id']
                                )
                              ) . '\',\''
                              . _txt('op.remove') . '\',\''    // dialog confirm button
                              . _txt('op.cancel') . '\',\''    // dialog cancel button
                              . _txt('op.remove') . '\',[\''   // dialog title
                              . filter_var(_jtxt(generateCn($co_people[0]['PrimaryName'])), FILTER_SANITIZE_STRING)  // dialog body text replacement strings
                              . '\']);">'
                              . _txt('op.delete')
                              . '</button>';
                          }
                        } elseif ($permissions['view']) {
                          print $this->Html->link(_txt('op.view'),
                              array('controller' => 'co_person_roles', 'action' => 'view', $r['id']),
                              array('class' => 'viewbutton')) . "\n";
                        }
                      ?>
                    </td>
                  </tr>
                  <?php
                    $rcnt++;
                    endforeach; // co_people
                  ?>
                  </tbody>
                </table>
              <?php endif; // invite ?>
            </li>
          </ul><!-- tabs-role -->
        </li><!-- fields-role -->

        <!-- SSH keys -->
        <li id="fields-sshkey" class="fieldGroup">
          <?php
            if($e) {
              print '<div class="coAddEditButtons">';
              $linktarget = array(
                'controller' => 'ssh_keys',
                'action'     => 'add',
                'copersonid' => $co_people[0]['CoPerson']['id']
              );
              $linkparams = array('class'  => 'addbutton');
              
              print $this->Html->link(_txt('op.add'),
                                      $linktarget,
                                      $linkparams);
              print '</div>';
            }
          ?>
          <a href="#tabs-sshkeys" class="fieldGroupName">
            <em class="material-icons">indeterminate_check_box</em>
            <?php print _txt('ct.ssh_keys.pl'); ?>
          </a>
          <ul id="tabs-sshkeys" class="fields data-list">

            <?php
            if($e)
            {
              if(isset($co_people[0]['SshKey'])) {
                foreach($co_people[0]['SshKey'] as $sk) {
                  print '<li class="field-data-container">';
                  print '<div class="field-data">';
                  print $this->Html->link($sk['comment'], array('controller' => 'ssh_keys', 'action' => 'edit', $sk['id']));
                  print ' (' . _txt('en.sshkey.type', null, $sk['type']). ')';
                  print '</div>';
                  print '<div class="field-actions">';
                  // XXX we already checked for $permissions['edit'], but not ['delete']... should we?
                  print '<button type="button" class="deletebutton" title="' . _txt('op.delete')
                    . '" onclick="javascript:js_confirm_generic(\''
                    . _txt('js.remove') . '\',\''    // dialog body text
                    . $this->Html->url(              // dialog confirm URL
                      array(
                        'controller' => 'ssh_keys',
                        'action' => 'delete',
                        $sk['id']
                      )
                    ) . '\',\''
                    . _txt('op.remove') . '\',\''    // dialog confirm button
                    . _txt('op.cancel') . '\',\''    // dialog cancel button
                    . _txt('op.remove') . '\',[\''   // dialog title
                    . filter_var(_jtxt($sk['comment']),FILTER_SANITIZE_STRING)  // dialog body text replacement strings
                    . '\']);">'
                    . _txt('op.delete')
                    . '</button>';
                  print $this->Html->link(_txt('op.edit'),
                      array('controller' => 'ssh_keys', 'action' => 'edit', $sk['id']),
                      array('class' => 'editbutton')) . "\n";
                  print '</div>';
                  print '</li>';
                }
              }
            } else {
              if(isset($co_people[0]['SshKey'])) {
                foreach($co_people[0]['SshKey'] as $sk) {
                  print '<li class="field-data-container">';
                  print filter_var($sk['comment'],FILTER_SANITIZE_SPECIAL_CHARS) . "\n";
                  print '</li>';
                }
              }
            }
            ?>
          </ul><!-- tabs-sshkey -->
        </li><!-- fields-sshkey -->

        <!-- Org Ids -->
        <li id="fields-orgid" class="fieldGroup">
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
          <a href="#tabs-orgid" class="fieldGroupName">
            <em class="material-icons">indeterminate_check_box</em>
            <?php print _txt('ct.org_identities.pl'); ?>
          </a>
          <ul id="tabs-orgid" class="fields data-list data-table">
            <li>
              <!-- Org Identity Data -->
              <table id="<?php print $this->action; ?>_org_identities">
                <tbody>
                <tr class="line<?php print ($l % 2); $l++; ?>">
                  <th><?php print _txt('fd.name'); ?></th>
                  <th><?php print _txt('fd.source'); ?></th>
                  <th><?php print _txt('fd.o'); ?></th>
                  <th><?php print _txt('fd.valid_from.tz', array($vv_tz)); ?></th>
                  <th><?php print _txt('fd.valid_through.tz', array($vv_tz)); ?></th>
                  <th><?php print _txt('fd.identifier.ids.login'); ?></th>
                  <th><?php print _txt('fd.actions'); ?></th>
                </tr>
                <?php if(!empty($co_people[0]['CoOrgIdentityLink'])): ?>
                <?php foreach($co_people[0]['CoOrgIdentityLink'] as $link): ?>
                  <tr class="line<?php print ($l % 2); $l++; ?>">
                    <td>
                      <?php
                        if(!empty($link['OrgIdentity']['PrimaryName'])) {
                          print filter_var(generateCn($link['OrgIdentity']['PrimaryName']),FILTER_SANITIZE_SPECIAL_CHARS);
                        }

                        print " (" . filter_var($link['OrgIdentity']['id'],FILTER_SANITIZE_SPECIAL_CHARS) . ")";
                      ?>
                    </td>
                    <td>
                      <?php
                        if(!empty($link['OrgIdentity']['OrgIdentitySourceRecord']['OrgIdentitySource']['description'])) {
                          print filter_var($link['OrgIdentity']['OrgIdentitySourceRecord']['OrgIdentitySource']['description'] ,FILTER_SANITIZE_SPECIAL_CHARS);
                        }
                      ?>
                    </td>
                    <td>
                      <?php
                        if(!empty($link['OrgIdentity']['o'])) {
                          print filter_var($link['OrgIdentity']['o'], FILTER_SANITIZE_SPECIAL_CHARS);
                        }  
                      ?>
                    </td>
                    <td>
                      <?php
                        if(isset($link['OrgIdentity']['valid_from']) && $link['OrgIdentity']['valid_from'] > 0) {
                          print $this->Time->format($link['OrgIdentity']['valid_from'], "%F %T", false, $vv_tz);
                        }  
                      ?>
                    </td>
                    <td>
                      <?php
                        if(isset($link['OrgIdentity']['valid_through']) && $link['OrgIdentity']['valid_through'] > 0) {
                          print $this->Time->format($link['OrgIdentity']['valid_through'], "%F %T", false, $vv_tz);
                        }  
                      ?>
                    </td>
                    <td>
                      <?php
                        if(!empty($link['OrgIdentity']['Identifier'])) {
                          foreach($link['OrgIdentity']['Identifier'] as $id) {
                            if(!empty($id['identifier'])
                              && $id['login']
                              && $id['status'] == StatusEnum::Active) {
                              print filter_var($id['identifier'],FILTER_SANITIZE_SPECIAL_CHARS)
                                . " (" . filter_var($id['type'],FILTER_SANITIZE_SPECIAL_CHARS) . ")<br />";
                            }
                          }
                        }
                      ?>
                    </td>
                    <td>
                      <?php
                        print $this->Html->link(_txt($e && !$es ? 'op.edit' : 'op.view'),
                            array('controller' => 'org_identities',
                              'action' => ($e && !$es ? 'edit' : 'view'),
                              $link['OrgIdentity']['id']),
                            array('class' => ($e && !$es ? 'editbutton' : 'viewbutton'))) . "\n";

                        if($permissions['relink']) {
                        print '<a class="relinkbutton" title="' . _txt('op.relink') . '" onclick="javascript:js_confirm_generic(\'' . _jtxt(_txt('op.relink.confirm')) . '\', \'' . $this->Html->url(array('controller' => 'co_people', 'action' => 'relink', $co_people[0]['CoPerson']['id'], 'linkid' => $link['id'])) . '\');">' . _txt('op.relink') . '</a>' . "\n";
                      }

                      if($permissions['delete']
                        && count($co_people[0]['CoOrgIdentityLink']) > 1) {
                        // An Org Identity Link can only be removed if there is at least one other remaining
                        // XXX Is this still a valid restriction? As of 0.9.1 one can relink an org identity
                        // away, even if it's the last one attached to the CO Preson.

                        print '<a class="unlinkbutton" title="' . _txt('op.unlink') . '" onclick="javascript:js_confirm_generic(\'' . _jtxt(_txt('op.unlink.confirm')) . '\', \'' . $this->Html->url(array('controller' => 'co_org_identity_links', 'action' => 'delete', $link['id'])) . '\');">' . _txt('op.unlink') . '</a>' . "\n";
                      }
                      ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
              </table>
            </li>
          </ul><!-- tabs-orgid -->
        </li><!-- fields-orgid -->
      <?php endif; ?>
      
    </ul>
  </div> <!-- personExplorer -->
  <?php print $this->element("changelog"); ?>
</div> <!-- explorerContainer -->

<div id="autogenerate-dialog" title="<?php print _txt('op.id.auto'); ?>">
  <?php print _txt('op.id.auto.confirm'); ?>
</div>


