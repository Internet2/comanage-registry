<!--
/**
 * COmanage Registry CO Person Fields
 *
 * Copyright (C) 2010-14 University Corporation for Advanced Internet Development, Inc.
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
 * @copyright     Copyright (C) 2010-14 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.1
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */
-->
<?php
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
  $args = array();
  $args['plugin'] = null;
  $args['controller'] = 'co_people';
  $args['action'] = 'index';
  $args['co'] = $cur_co['Co']['id'];
  $this->Html->addCrumb(_txt('me.population'), $args);
  if($this->action == "view") {
    $this->Html->addCrumb($co_people[0]['PrimaryName']['given'] . ' ' . $co_people[0]['PrimaryName']['family']);
  } else {
    $args = array(
      'controller' => 'co_people',
      'action' => 'view',
      $co_people[0]['CoPerson']['id']);
    $this->Html->addCrumb($co_people[0]['PrimaryName']['given'] . ' ' . $co_people[0]['PrimaryName']['family'], $args);
    if($this->action == "canvas") {
      $this->Html->addCrumb(_txt('op.edit'));
    }
    if($this->action == "invite") {
      $this->Html->addCrumb(_txt('op.invite'));
    }
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
          'url'     => 'javascript:js_confirm_delete("'
                        . Sanitize::html(generateCn($co_people[0]['PrimaryName']))
                        . '","'
                        . $this->Html->url(array('controller' => 'co_people',
                                                 'action'     => 'delete',
                                                 $co_people[0]['CoPerson']['id']))
                        . '");'
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
      if ($(this).find(".ui-icon").hasClass("ui-icon-circlesmall-minus")) {
        $(this).find(".ui-icon").removeClass("ui-icon-circlesmall-minus").addClass("ui-icon-circlesmall-plus");
      } else {
        $(this).find(".ui-icon").removeClass("ui-icon-circlesmall-plus").addClass("ui-icon-circlesmall-minus");
      }
    });

    // Autogenerate dialog
    $("#autogenerate-dialog").dialog({
      autoOpen: false,
      <?php if($this->action == 'canvas'): ?>
      buttons: {
        "<?php print _txt('op.cancel'); ?>": function() {
          $(this).dialog("close");
        },
        "<?php print _txt('op.id.auto'); ?>": function() {
          window.location="<?php print $this->Html->url(array('controller' => 'identifiers',
                                                              'action' => 'assign',
                                                              'copersonid' => $co_people[0]['CoPerson']['id'],
                                                              'co' => $cur_co['Co']['id'])); ?>"
        }
      },
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
  <div id="personExplorer">
    <ul>
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
            <span class="ui-icon ui-icon-circlesmall-minus"></span>
          <?php print _txt('fd.name'); ?>
        </a>

        <ul class="fields">
          <li>

            <div id="tabs-name" class="additionalinfo">
            <table>
            <?php if($this->action == 'invite'): ?>
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
                    : Sanitize::html($co_people[0]['PrimaryName']['honorific']));
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
                    : Sanitize::html($co_people[0]['PrimaryName']['given'])); ?>
                </td>
              </tr>
              <tr class="line<?php print ($l % 2); $l++; ?>">
                <th>
                  <?php print _txt('fd.name.middle'); ?>
                </th>
                <td>
                  <?php print ($e ? $this->Form->input('PrimaryName.middle',
                    array('default' => $co_people[0]['PrimaryName']['middle']))
                    : Sanitize::html($co_people[0]['PrimaryName']['middle'])); ?>
                </td>
              </tr>
              <tr class="line<?php print ($l % 2); $l++; ?>">
                <th>
                  <?php print _txt('fd.name.family'); ?>
                </th>
                <td>
                  <?php print ($e ? $this->Form->input('PrimaryName.family',
                    array('default' => $co_people[0]['PrimaryName']['family']))
                    : Sanitize::html($co_people[0]['PrimaryName']['family'])); ?>
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
                    : Sanitize::html($co_people[0]['PrimaryName']['suffix'])); ?>
                </td>
              </tr>
              <tr class="line<?php print ($l % 2); $l++; ?>">
                <th>
                  <?php print _txt('fd.type'); ?>
                </th>
                <td>
                  <?php print _txt('en.name', null, NameEnum::Official); ?>
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
                  <i><span class="required"><?php print _txt('fd.req'); ?></span></i><br />
                </th>
                <td>
                  <?php
                  if($e) {
                    print $this->Form->submit($submit_label);
                    print $this->Form->button(_txt('op.reset'),
                      array('type'=>'reset'));
                  }
                  ?>
                </td>
              </tr>
            <?php elseif($this->action == "compare"): ?>
              <!-- Simply list the org names and then the CO names -->
              <tr>
                <th class="ui-widget-header"><?php print Sanitize::html($cur_co['Co']['name']); ?></th>
              </tr>
              <?php foreach($co_people[0]['Name'] as $n): ?>
                <tr>
                  <td>
                    <?php
                    print generateCn($n)
                      . " ("
                      . ($n['primary_name'] ? (_txt('fd.name.primary_name') . ", ") : "")
                      . _txt('en.name', null, $n['type'])
                      . (!empty($n['language']) ? (", " . _txt('en.language', null, $n['language'])) : "")
                      . ")";
                    ?>
                  </td>
                </tr>
              <?php endforeach; ?>
              <tr>
                <th class="ui-widget-header"><?php print (!empty($org_identities[0]['OrgIdentity']['o']) ? Sanitize::html($org_identities[0]['OrgIdentity']['o']) : _txt('fd.o')); ?></th>
              </tr>
              <?php foreach($org_identities[0]['Name'] as $n): ?>
                <tr>
                  <td>
                    <?php
                    print generateCn($n)
                      . " ("
                      . ($n['primary_name'] ? (_txt('fd.name.primary_name') . ", ") : "")
                      . _txt('en.name', null, $n['type'])
                      . (!empty($n['language']) ? (", " . _txt('en.language', null, $n['language'])) : "")
                      . ")";
                    ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php else: // invite/compare ?>
              <?php foreach($co_people[0]['Name'] as $n): ?>
                <div>
                  <div>
                    <?php
                    $perm = $this->Permission->selfService($permissions,
                                                           $e,
                                                           'Name',
                                                           (!empty($n['type']) ? $n['type'] : null));
                    
                    if($perm == PermissionEnum::ReadWrite) {
                      print $this->Html->link(generateCn($n),
                        array('controller' => 'names',
                          'action' => 'edit',
                          $n['id'],
                          'co' => $cur_co['Co']['id']));
                    } elseif($perm == PermissionEnum::ReadOnly) {
                      print generateCn($n);
                    }

                    print "&nbsp;("
                      . ($n['primary_name'] ? (_txt('fd.name.primary_name') . ", ") : "")
                      . _txt('en.name', null, $n['type'])
                      . (!empty($n['language']) ? (", " . _txt('en.language', null, $n['language'])) : "")
                      . ")";
                    ?>
                  </div>
                  <div>
                    <?php
                    if($perm == PermissionEnum::ReadWrite) {
                      if(!$n['primary_name']) {
                        print '<a class="deletebutton" title="' . _txt('op.delete') .'" onclick="javascript:js_confirm_delete(\'' . _jtxt(Sanitize::html(generateCn($n))) . '\', \'' . $this->Html->url(array('controller' => 'names', 'action' => 'delete', $n['id'], 'co' => $cur_co['Co']['id'])) . '\')";>' . _txt('op.delete') .'</a>' . "\n";

                        print $this->Html->link(_txt('op.primary'),
                            array('controller' => 'names',
                              'action'     => 'primary',
                              $n['id'],
                              'copersonid' => $co_people[0]['CoPerson']['id'],
                              'co'         => $cur_co['Co']['id']),
                            array('class' => 'primarybutton')) . "\n";
                      }

                      print $this->Html->link(_txt('op.edit'),
                          array('controller' => 'names',
                            'action' => 'edit',
                            $n['id'],
                            'co' => $cur_co['Co']['id']),
                          array('class' => 'editbutton')) . "\n";
                    }
                    ?>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php endif; // invite ?>
            </table>
            </div> <!-- tabs-name -->
          </li>
        </ul>
      </li>
      <?php if($this->action != "invite"): ?>
        <li id="fields-id" class="fieldGroup">
          <?php
            if($e && !$es) {
              print '<div class="coAddEditButtons">';
              $linktarget = array(
                'controller' => 'identifiers',
                'action'     => 'add',
                'copersonid' => $co_people[0]['CoPerson']['id'],
                'co'         => $cur_co['Co']['id']
              );
              $linkparams = array('class'  => 'addbutton');
              print $this->Html->link(_txt('op.add'),
                $linktarget,
                $linkparams);
              print '</div>';
            }
          ?>
          <a href="#tabs-id" class="fieldGroupName">
            <span class="ui-icon ui-icon-circlesmall-minus"></span>
            <?php print _txt('fd.ids'); ?>
          </a>
          <ul class="fields">
            <li>
              <div id="tabs-id" >
                <?php print '<div';
                if($this->action != 'compare')
                  print ' class="additionalinfo"';
                print '>';
                ?>
                <table>
                  <tbody>
                  <?php if($this->action == "compare"): ?>
                    <tr>
                      <th class="ui-widget-header"><?php print Sanitize::html($cur_co['Co']['name']); ?></th>
                      <?php if($e && !$es): ?>
                        <th class="ui-widget-header"><?php print _txt('fd.actions'); ?></th>
                      <?php endif; ?>
                    </tr>
                  <?php endif; ?>

                  <?php
                  $l = 1;
                  if($e && !$es)
                  {
                    if(isset($co_people[0]['Identifier']))
                    {
                      foreach($co_people[0]['Identifier'] as $id)
                      {
                        print '<div>';
                        print '<div>';
                        print $this->Html->link($id['identifier'], array('controller' => 'identifiers', 'action' => 'edit', $id['id'], 'co' => $cur_co['Co']['id']));
                        print " &#160;(" . $id['type'] . ")";
                        print '</div>';
                        print '<div>';
                        // XXX we already checked for $permissions['edit'], but not ['delete']... should we?
                        print '<a class="deletebutton" title="' . _txt('op.delete') . '" onclick="javascript:js_confirm_delete(\'' . _jtxt(Sanitize::html($id['identifier'])) . '\', \'' . $this->Html->url(array('controller' => 'identifiers', 'action' => 'delete', $id['id'], 'co' => $cur_co['Co']['id'])) . '\')";>' . _txt('op.delete') . '</a>' . "\n";
                        print $this->Html->link(_txt('op.edit'),
                          array('controller' => 'identifiers', 'action' => 'edit', $id['id'], 'co' => $cur_co['Co']['id']),
                          array('class' => 'editbutton'));
                        print '</div>';
                        print '</div>';
                      }
                    }
                  }
                  else
                  {
                    if(isset($co_people[0]['Identifier']))
                    {
                      foreach($co_people[0]['Identifier'] as $id)
                      {

                        print '<tr class="line';
                        print ($l % 2);
                        print '">';
                        $l++;
                        print '<td>';
                        print Sanitize::html($id['identifier']) . " (" . $id['type'] . ")<br />\n";
                        print '</td>';
                        print '</tr>';
                      }
                    }
                  }
                  ?>
                  </tbody>
                </table>
              </div>
              <?php if($this->action == "compare"): ?>
                <div>
                  <table>
                    <tbody>
                    <tr>
                      <th class="ui-widget-header"><?php print (!empty($org_identities[0]['OrgIdentity']['o']) ? Sanitize::html($org_identities[0]['OrgIdentity']['o']) : _txt('fd.o')); ?></th>
                    </tr>
                    <?php
                    if(isset($org_identities[0]['Identifier']))
                    {
                      foreach($org_identities[0]['Identifier'] as $id)
                      {
                        print '<tr class="line';
                        print ($l % 2);
                        print '">';
                        $l++;
                        print '<td>';
                        print Sanitize::html($id['identifier']) . " (" . $id['type'] . ")<br />\n";
                        print '</td>';
                        print '</tr>';
                      }
                    }
                    ?>
                    </tbody>
                  </table>
                </div>
              <?php endif; // compare ?>
              </div> <!-- tabs-id -->
            </li>
          </ul>
        </li>
        <li id="fields-email" class="fieldGroup">
          <?php
            if($this->Permission->selfService($permissions, $e, 'EmailAddress') == PermissionEnum::ReadWrite) {
              print '<div class="coAddEditButtons">';
              $linktarget = array(
                'controller' => 'email_addresses',
                'action'     => 'add',
                'copersonid' => $co_people[0]['CoPerson']['id'],
                'co'         => $cur_co['Co']['id']
              );
              $linkparams = array('class'  => 'addbutton');
              
              print $this->Html->link(_txt('op.add'),
                $linktarget,
                $linkparams);
              print '</div>';
            }
          ?>
          <a href="#tabs-email" class="fieldGroupName">
            <span class="ui-icon ui-icon-circlesmall-minus"></span>
            <?php print _txt('fd.email_address.mail'); ?>
          </a>
          <ul class="fields">
            <li>
              <div id="tabs-email">
                <?php print '<div';
                if($this->action != 'compare')
                  print ' class="additionalinfo"';
                print '>';
                ?>
                <table>
                  <tbody>
                  <?php if($this->action == "compare"): ?>
                    <tr>
                      <th class="ui-widget-header"><?php print Sanitize::html($cur_co['Co']['name']); ?></th>
                      <?php if($e && !$es): ?>
                        <th class="ui-widget-header"><?php print _txt('fd.actions'); ?></th>
                      <?php endif; ?>
                    </tr>
                  <?php endif; ?>
                  <?php
                  $l = 1;
                  
                  if(isset($co_people[0]['EmailAddress'])) {
                    foreach($co_people[0]['EmailAddress'] as $ea) {
                      // Determine the permission for this attribute
                      
                      $perm = $this->Permission->selfService($permissions,
                                                                       $e,
                                                                       'EmailAddress',
                                                                       (!empty($ea['type']) ? $ea['type'] : null));
                      
                      if($perm == PermissionEnum::ReadWrite) {
                        print '<div>';
                        print '<div>';
                        print $this->Html->link($ea['mail'], array('controller' => 'email_addresses', 'action' => 'edit', $ea['id'], 'co' => $cur_co['Co']['id']));
                        print " &nbsp;(" . _txt('en.contact.mail', null, $ea['type']) . ", "
                          . ($ea['verified'] ? _txt('fd.email_address.verified') : _txt('fd.email_address.unverified')) . ")<br />\n";
                        print '</div>';
                        print '<div>';
                        print '<a class="deletebutton" title="' . _txt('op.delete') . '" onclick="javascript:js_confirm_delete(\'' . _jtxt(Sanitize::html($ea['mail'])) . '\', \'' . $this->Html->url(array('controller' => 'email_addresses', 'action' => 'delete', $ea['id'], 'co' => $cur_co['Co']['id'])) . '\')";>' . _txt('op.delete') . '</a>' . "\n";
                        print $this->Html->link(_txt('op.edit'),
                            array('controller' => 'email_addresses', 'action' => 'edit', $ea['id'], 'co' => $cur_co['Co']['id']),
                            array('class' => 'editbutton')) . "\n";
                        print '</div>';
                        print '</div>';
                      } elseif($perm == PermissionEnum::ReadOnly) {
                        print '<tr class="line';
                        print ($l % 2);
                        print '">';
                        $l++;
                        print '<td>';
                        print Sanitize::html($ea['mail']) . " (" . _txt('en.contact', null, $ea['type']) . ")\n";
                        print '</td>';
                        print '</tr>';
                      }
                    }
                  }
                  ?>
                  </tbody>
                </table>
              </div>
              <?php
              if($this->action == "compare"):
                $l = 1;
                ?>
                <div>
                  <table>
                    <tbody>
                    <tr>
                      <th class="ui-widget-header"><?php print (!empty($org_identities[0]['OrgIdentity']['o']) ? Sanitize::html($org_identities[0]['OrgIdentity']['o']) : _txt('fd.o')); ?></th>
                    </tr>
                    <?php
                    if(isset($org_identities[0]['EmailAddress']))
                    {
                      foreach($org_identities[0]['EmailAddress'] as $ea)
                      {
                        print '<tr class="line';
                        print ($l % 2);
                        print '">';
                        $l++;
                        print '<td>';
                        print Sanitize::html($ea['mail']) . " (" . _txt('en.contact', null, $ea['type']) . ")\n";
                        print '</td>';
                        print '</tr>';

                      }
                    }
                    ?>
                    </tbody>
                  </table>
                </div>
              <?php endif; // compare ?>
              </div> <!-- tabs-email -->
            </li>
          </ul>
        </li>
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
            <span class="ui-icon ui-icon-circlesmall-minus"></span> 
            <?php print _txt('fd.groups'); ?>
          </a>
          <ul class="fields">
            <li>
              <div id="tabs-group">
                <div class="additionalinfo">
                  <table>
                    <?php if($this->action == "compare"): ?>
                      <tr>
                        <th class="ui-widget-header"><?php print Sanitize::html($cur_co['Co']['name']); ?></th>
                        <?php if($e && !$es): ?>
                          <th class="ui-widget-header"><?php print _txt('fd.actions'); ?></th>
                        <?php endif; ?>
                      </tr>
                    <?php endif; ?>
                    <?php
                    if(isset($co_people[0]['CoGroupMember'])) {
                      if($e && !$es) { // XXX !$es is probably the wrong permission here
                        if(isset($co_people[0]['CoGroupMember'])) {
                          foreach($co_people[0]['CoGroupMember'] as $g) {
                            print '<div>';
                            print '<div>';
                            print $this->Html->link($g['CoGroup']['name'], array('controller' => 'co_groups', 'action' => 'edit', $g['co_group_id'], 'co' => $cur_co['Co']['id']));
                            print '</div>';
                            print '<div>';
                            // XXX we already checked for $permissions['edit'], but not ['delete']... should we?
                            print '<a class="deletebutton" title="' . _txt('op.delete') . '" onclick="javascript:js_confirm_delete(\'' . _jtxt(_txt('fd.group.memin', array(Sanitize::html($g['CoGroup']['name'])))) . '\', \'' . $this->Html->url(array('controller' => 'co_group_members', 'action' => 'delete', $g['id'], 'copersonid' => $co_people[0]['CoPerson']['id'], 'co' => $cur_co['Co']['id'])) . '\')";>' . _txt('op.delete') . '</a>' . "\n";
                            print '</div>';
                            print '</div>';
                          }
                        }
                      } else {
                        foreach($co_people[0]['CoGroupMember'] as $g) {
                          print '<tr class="line';
                          print ($l % 2);
                          print '">';
                          $l++;
                          print '<td>';
                          if($g['member'])
                            print Sanitize::html($g['CoGroup']['name']) . "<br />\n";
                          if($g['owner'])
                            print Sanitize::html($g['CoGroup']['name']) . " (" . _txt('fd.group.own') . ")<br />\n";
                          print '</td>';
                          print '</tr>';
                        }
                      }
                    }
                    ?>
                  </table>
                </div>
              </div> <!-- tabs-group -->
            </li>
          </ul>
        </li>
        <li id="fields-coperson" class="fieldGroup">
          <?php
            print $this->Form->end();
            // Restart the form since the Form elements emitted above for add/invite cause blackholes for edit
            print $this->Form->create('CoPerson', array('action'=>'edit'));
            print $this->Form->hidden('CoPerson.co_id', array('default' => $co_people[0]['CoPerson']['co_id'])). "\n";
          ?>
          <a href="#tabs-coperson" class="fieldGroupName">
            <span class="ui-icon ui-icon-circlesmall-minus"></span>
            <?php print _txt('fd.attrs.cop'); ?>
          </a>
          <ul class="fields">
            <li>
              <div id="tabs-coperson">
                <?php print '<div class="additionalinfo">'; ?>
                <table>
                  <tbody>
                    <tr class="line1">
                      <th class="ui-widget-header">
                        <?php print _txt('fd.status'); ?>
                      </th>
                      <td>
                        <?php
                          if($e && $permissions['edit']) {
                            global $cm_lang, $cm_texts;
                            
                            $attrs = array();
                            $attrs['value'] = $co_people[0]['CoPerson']['status'];
                            $attrs['empty'] = false;
                            
                            print $this->Form->select('CoPerson.status',
                                                      $cm_texts[ $cm_lang ]['en.status'],
                                                      $attrs);
                            
                            if($this->Form->isFieldError('CoPerson.status')) {
                              print $this->Form->error('CoPerson.status');
                            }
                          } else {
                            if(!empty($co_people[0]['CoPerson']['status'])) {
                              print _txt('en.status', null, $co_people[0]['CoPerson']['status']);
                            }
                          }
                        ?>
                        <?php if($co_people[0]['CoPerson']['status'] == StatusEnum::PendingApproval
                                 || $co_people[0]['CoPerson']['status'] == StatusEnum::PendingConfirmation): ?>
                        <div>
                          <span class="ui-icon ui-icon-info" style="float: left; margin-right: .3em;"></span>
                          <i><?php print _txt('fd.status.change'); ?></i>
                        </div>
                        <?php endif; ?>
                      </td>
                    </tr>
                    <?php if($e && $permissions['edit']): ?>
                    <tr class="line2">
                      <td></td>
                      <td>
                        <?php
                          print $this->Form->submit($submit_label);
                          print $this->Form->button(_txt('op.reset'),
                                                    array('type'=>'reset'));
                        ?>
                      </td>
                    </tr>
                    <?php endif; // edit ?>
                  </tbody>
                </table>
              </div>
              </div> <!-- tabs-coperson -->
            </li>
          </ul>
        </li>
        <li id="fields-role" class="fieldGroup">
          <?php
            if($this->action != "compare" && $e && !$es) {
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
            <span class="ui-icon ui-icon-circlesmall-minus"></span> 
            <?php print _txt('fd.attrs.copr'); ?>
          </a>
          <ul class="fields">
            <li>
              <div id="tabs-role">
                <table>
                  <tbody>
                  <tr>
                    <td valign="top">
                      <?php if($this->action != "invite"): ?>
                        <!-- Person Role Data -->
                        <table id="<?php print $this->action; ?>_co_person_roles" class="ui-widget">
                          <tbody>
                          <tr class="line<?php print ($l % 2); $l++; ?>">
                            <?php if($this->action == "compare"): ?>
                              <th class="ui-widget-header"><?php print _txt('fd.o'); ?></th>
                              <th class="ui-widget-header"><?php print _txt('fd.ou'); ?></th>
                            <?php else: // compare ?>
                              <th class="ui-widget-header"><?php print _txt('fd.cou'); ?></th>
                            <?php endif; // compare ?>
                            <th class="ui-widget-header"><?php print _txt('fd.title'); ?></th>
                            <th class="ui-widget-header"><?php print _txt('fd.affiliation'); ?></th>
                            <th class="ui-widget-header"><?php print _txt('fd.valid_from'); ?></th>
                            <th class="ui-widget-header"><?php print _txt('fd.valid_through'); ?></th>
                            <?php if($this->action != "compare"): ?>
                              <th class="ui-widget-header"><?php print _txt('fd.status'); ?></th>
                              <th class="ui-widget-header"><?php print _txt('fd.actions'); ?></th>
                            <?php endif; // compare ?>
                          </tr>
                          <?php if($this->action == "compare"): ?>
                            <tr class="line<?php print ($l % 2); $l++; ?>">
                              <td>
                                <?php
                                if(isset($org_identities[0]['OrgIdentity']['o']))
                                  print Sanitize::html($org_identities[0]['OrgIdentity']['o']);
                                ?>
                              </td>
                              <td>
                                <?php
                                if(isset($org_identities[0]['OrgIdentity']['ou']))
                                  print Sanitize::html($org_identities[0]['OrgIdentity']['ou']);
                                ?>
                              </td>
                              <td>
                                <?php
                                if(isset($org_identities[0]['OrgIdentity']['title']))
                                  print Sanitize::html($org_identities[0]['OrgIdentity']['title']);
                                ?>
                              </td>
                              <td>
                                <?php
                                if(isset($org_identities[0]['OrgIdentity']['affiliation']))
                                  print $cm_texts[ $cm_lang ]['en.affil'][ $org_identities[0]['OrgIdentity']['affiliation'] ];
                                ?>
                              </td>
                            </tr>
                          <?php endif; // compare ?>
                          <?php
                          $rcnt = 1;
                          foreach($co_people[0]['CoPersonRole'] as $r):
                            ?>
                            <tr class="line<?php print ($l % 2); $l++; ?>">
                              <?php if($this->action == "compare"): ?>
                                <td><?php if(isset($r['o'])) print Sanitize::html($r['o']); ?></td>
                                <td><?php if(isset($r['ou'])) print Sanitize::html($r['ou']); ?></td>
                              <?php else: // compare ?>
                                <td><?php if(isset($r['Cou']['name'])) print Sanitize::html($r['Cou']['name']); ?></td>
                              <?php endif; // compare ?>
                              <td><?php if(isset($r['title'])) print Sanitize::html($r['title']); ?></td>
                              <td><?php if(isset($r['affiliation'])) print $cm_texts[ $cm_lang ]['en.affil'][ $r['affiliation'] ]; ?></td>
                              <td><?php if(isset($r['valid_from']) && $r['valid_from'] > 0) print $this->Time->format('Y M d', $r['valid_from']); ?></td>
                              <td><?php if(isset($r['valid_through']) && $r['valid_through'] > 0) print $this->Time->format('Y M d', $r['valid_through']); ?></td>
                              <?php if($this->action != "compare"): ?>
                                <td><?php if(isset($r['status'])) print _txt('en.status', null, $r['status']); ?></td>
                                <td>
                                  <?php
                                  if($es // Editing self
                                    || empty($r['Cou']) // No COU set for this person
                                    || (isset($r['Cou']['name']) && in_array($r['Cou']['name'], $permissions['cous']))) // Admin for the COU
                                  {
                                    // COU Admins can only edit their own folks, so we need a bit of
                                    // a machination to determine if they can edit these records
                                    // along side other authorized folks.

                                    // Currently, users can self-edit some role level attributes,
                                    // so give them an edit button, too.

                                    if($permissions['enroll']
                                      && $r['status'] == StatusEnum::PendingApproval) {
                                      print $this->Html->link(_txt('op.petition'),
                                        array('controller' => 'co_petitions',
                                          'action' => 'view',
                                          $r['CoPetition'][0]['id'],
                                          'co' => $r['CoPetition'][0]['co_id'],
                                          'coef' => $r['CoPetition'][0]['co_enrollment_flow_id']),
                                        array('class' => 'petitionbutton'));
                                    }
                                    if($permissions['canvas'])
                                      print $this->Html->link(_txt('op.edit'),
                                          array('controller' => 'co_person_roles', 'action' => 'edit', $r['id'], 'co' => $cur_co['Co']['id']),
                                          array('class' => 'editbutton')) . "\n";
                                    if($permissions['delete'])
                                      print '<a class="deletebutton" title="' . _txt('op.delete') . '" onclick="javascript:js_confirm_delete(\'' . _jtxt(Sanitize::html(generateCn($co_people[0]['PrimaryName']))) . '\', \'' . $this->Html->url(array('controller' => 'co_person_roles', 'action' => 'delete', $r['id'], 'co' => $cur_co['Co']['id'])) . '\')";>' . _txt('op.delete') . '</a>' . "\n";
                                  } elseif($permissions['view']) {
                                    print $this->Html->link(_txt('op.view'),
                                        array('controller' => 'co_person_roles', 'action' => 'view', $r['id'], 'co' => $cur_co['Co']['id']),
                                        array('class' => 'viewbutton')) . "\n";
                                  }
                                  ?>
                                </td>
                              <?php endif; // compare ?>
                            </tr>
                            <?php
                            $rcnt++;
                          endforeach; // co_people
                          ?>
                          </tbody>
                        </table>
                      <?php endif; // invite ?>
                    </td>
                  </tr>
                  </tbody>
                </table>
              </div> <!-- tabs-role -->
            </li>
          </ul>
        </li>
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
            <span class="ui-icon ui-icon-circlesmall-minus"></span>
            <?php print _txt('ct.ssh_keys.pl'); ?>
          </a>
          <ul class="fields">
            <li>
              <div id="tabs-sshkeys">
                <?php print '<div class="additionalinfo">'; ?>
                <table>
                  <tbody>
                  <?php
                  $l = 1;
                  if($e)
                  {
                    if(isset($co_people[0]['SshKey'])) {
                      foreach($co_people[0]['SshKey'] as $sk) {
                        print '<div>';
                        print '<div>';
                        print $this->Html->link($sk['comment'], array('controller' => 'ssh_keys', 'action' => 'edit', $sk['id']));
                        print '</div>';
                        print '<div>';
                        // XXX we already checked for $permissions['edit'], but not ['delete']... should we?
                        // We use Sanitize::paranoid in the delete construction because a comment like 'user@host' will break
                        // the javascript. There's probably a better filter we could do here (this also removes spaces).
                        print '<a class="deletebutton" title="' . _txt('op.delete') . '" onclick="javascript:js_confirm_delete(\'' . _jtxt(Sanitize::paranoid($sk['comment'])) . '\', \'' . $this->Html->url(array('controller' => 'ssh_keys', 'action' => 'delete', $sk['id'])) . '\')";>' . _txt('op.delete') . '</a>' . "\n";
                        print $this->Html->link(_txt('op.edit'),
                            array('controller' => 'ssh_keys', 'action' => 'edit', $sk['id']),
                            array('class' => 'editbutton')) . "\n";
                        print '</div>';
                        print '</div>';
                      }
                    }
                  } else {
                    if(isset($co_people[0]['SshKey'])) {
                      foreach($co_people[0]['SshKey'] as $sk) {
                        print '<tr class="line';
                        print ($l % 2);
                        print '">';
                        $l++;
                        
                        print '<td>';
                        print Sanitize::html($sk['comment']) . "\n";
                        print '</td>';
                        print '</tr>';
                      }
                    }
                  }
                  ?>
                  </tbody>
                </table>
              </div>
              </div> <!-- tabs-sshkey -->
            </li>
          </ul>
        </li>
        <li id="fields-orgid" class="fieldGroup">
          <a href="#tabs-orgid" class="fieldGroupName">
            <span class="ui-icon ui-icon-circlesmall-minus"></span>
            <?php print _txt('ct.org_identities.pl'); ?>
          </a>
          <ul class="fields">
            <li>
              <div id="tabs-orgid">
                <table>
                  <tbody>
                  <tr>
                    <td valign="top">
                      <!-- Org Identity Data -->
                      <table id="<?php print $this->action; ?>_org_identities" class="ui-widget">
                        <tbody>
                        <tr class="line<?php print ($l % 2); $l++; ?>">
                          <th class="ui-widget-header"></th>
                          <th class="ui-widget-header"><?php print _txt('fd.o'); ?></th>
                          <th class="ui-widget-header"><?php print _txt('fd.ou'); ?></th>
                          <th class="ui-widget-header"><?php print _txt('fd.title'); ?></th>
                          <th class="ui-widget-header"><?php print _txt('fd.affiliation'); ?></th>
                          <th class="ui-widget-header"><?php print _txt('fd.actions'); ?></th>
                        </tr>
                        <?php foreach($co_people[0]['CoOrgIdentityLink'] as $link): ?>
                          <tr class="line<?php print ($l % 2); $l++; ?>">
                            <td>
                              <?php print $link['OrgIdentity']['id']; ?>
                            </td>
                            <td>
                              <?php
                              if(isset($link['OrgIdentity']['o']))
                                print Sanitize::html($link['OrgIdentity']['o']);
                              ?>
                            </td>
                            <td>
                              <?php
                              if(isset($link['OrgIdentity']['ou']))
                                print Sanitize::html($link['OrgIdentity']['ou']);
                              ?>
                            </td>
                            <td>
                              <?php
                              if(isset($link['OrgIdentity']['title']))
                                print Sanitize::html($link['OrgIdentity']['title']);
                              ?>
                            </td>
                            <td>
                              <?php
                              if(isset($link['OrgIdentity']['affiliation']))
                                print $cm_texts[ $cm_lang ]['en.affil'][ $link['OrgIdentity']['affiliation'] ];
                              ?>
                            </td>
                            <td>
                              <?php
                              print $this->Html->link(_txt('op.view'),
                                  array('controller' => 'org_identities',
                                    'action' => ($e && !$es ? 'edit' : 'view'),
                                    $link['OrgIdentity']['id'],
                                    'co' => ($pool_org_identities ? false : $cur_co['Co']['id'])),
                                  array('class' => ($e && !$es ? 'editbutton' : 'viewbutton'))) . "\n";

                              if($permissions['delete']
                                && count($co_people[0]['CoOrgIdentityLink']) > 1) {
                                // An Org Identity Link can only be removed if there is at least one other remaining

                                print '<a class="unlinkbutton" title="' . _txt('op.unlink') . '" onclick="javascript:js_confirm_generic(\'' . _jtxt(_txt('op.unlink.confirm')) . '\', \'' . $this->Html->url(array('controller' => 'co_org_identity_links', 'action' => 'delete', $link['id'], 'co' => $cur_co['Co']['id'])) . '\')";>' . _txt('op.unlink') . '</a>' . "\n";
                              }
                              ?>
                            </td>
                          </tr>
                        <?php endforeach; ?>
                        </tbody>
                      </table>
                    </td>
                  </tr>
                  </tbody>
                </table>
              </div> <!-- tabs-orgid -->
            </li>
          </ul>
        </li>
      <?php endif; ?>
    </ul>
  </div>
</div>

<div id="autogenerate-dialog" title="<?php print _txt('op.id.auto'); ?>">
  <?php print _txt('op.id.auto.confirm'); ?>
</div>

