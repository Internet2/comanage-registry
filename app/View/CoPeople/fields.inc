<!--
/**
 * COmanage Registry CO Person Fields
 *
 * Copyright (C) 2010-13 University Corporation for Advanced Internet Development, Inc.
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
 * @copyright     Copyright (C) 2010-13 University Corporation for Advanced Internet Development, Inc.
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

  // Determine if fields are editable
  $e = false;
  $es = false;

  if(($this->action == "invite" && $permissions['invite'])
      || ($this->action == "edit" && $permissions['edit']))
    $e = true;

  if($this->action == "edit" && $permissions['editself'])
    $es = true;

  // We shouldn't get here if we don't have at least read permission, but check just in case
  
  if(!$e && !$permissions['view'])
    return(false);

  // Add buttons to sidebar
  $sidebarButtons = $this->get('sidebarButtons');
  
  if($e)
  {
    // Add related links to the sidebar
    $sidebarButtons[] = array(
      'icon'    => 'circle-close',
      'title'   => _txt('op.back'),
      'url'     => array(
        'controller' => 'co_people',
        'action'     => 'index', 
        'co'         => $cur_co['Co']['id'])
    );
    
    if($this->action != "invite")
    { 
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
      
      $sidebarButtons[] = array(
        'icon'    => 'image',
        'title'   => _txt('ct.co_nsf_demographics.1'),
        'url'     => $l
      );
      
      // Autogenerate Identifiers button
      if(!empty($co_identifier_assignments)) {
        $sidebarButtons[] = array(
            'icon'    => 'script',
            'title'   => _txt('op.id.auto'),
            'url'     => 'javascript:js_confirm_autogenerate();' // Does not work when added in options
          );
      }
      
      // Provisioning status
      if($permissions['provision']) {
        $sidebarButtons[] = array(
          'icon'    => 'note',
          'title'   => _txt('op.prov.view'),
          'url'     => array(
            'controller' => 'co_people', 
            'action'     => 'provision', 
            $co_people[0]['CoPerson']['id'], 
            'co'         => $cur_co['Co']['id']
          )
        ); 
      }
    }
    
    // Populate the cross reference
    print $this->Form->hidden('CoPerson.co_id', array('default' => $cur_co['Co']['id'])). "\n";
    print $this->Form->hidden('CoOrgIdentityLink.0.id'). "\n";
    if(!empty($co_people[0]['CoOrgIdentityLink'][0]['org_identity_id'])) {
      print $this->Form->hidden('CoOrgIdentityLink.0.org_identity_id', array('default' => $co_people[0]['CoOrgIdentityLink'][0]['org_identity_id'])). "\n";
    }
    // Default status is 'Pending'
    print $this->Form->hidden('status', array('default' => 'P')). "\n";
  }
  else
  {
    // Back button
    $sidebarButtons[] = array(
      'icon'    => 'circle-arrow-w',
      'title'   => _txt('op.back'),
      'url'     => array(
        'controller' => 'co_people', 
        'action' => 'index', 
        'co' => $cur_co['Co']['id'])
    );
  }
  
  // View History button
  if($this->action != "add" && $this->action != "invite") {
    $sidebarButtons[] = array(
      'icon'    => 'note',
      'title'   => _txt('op.history'),
      'url'     => array(
        'controller' => 'history_records',
        'action' => 'index',
        'copersonid' => $co_people[0]['CoPerson']['id'],
        'co' => $cur_co['Co']['id']
      )
    );
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
    // Ennumeration for tabs
    var tabid = new Object();
    tabid["name"]  = 0;
    tabid["id"]    = 1;
    tabid["email"] = 2;
    tabid["group"] = 3;
    tabid["role"]  = 4;
    tabid["orgid"] = 5;

    // Turn on Tabs
    var $tabs = $( "#tabs" ).tabs();
    
    // If returning to this page via redirect, open last used tab
    <?php if(isset($this->request->params['named']['tab'])): ?>
      var selectedtab = "<?php print($this->request->params['named']['tab']); ?>";

      $tabs.tabs('option', 'active', tabid[selectedtab] );
    <?php endif; ?>
    
    // Autogenerate dialog
    
    $("#autogenerate-dialog").dialog({
      autoOpen: false,
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

    });
  });
</script>

<div id="<?php print $this->action; ?>_co_person" style=" float:left; height:auto;">
  <div id="tabs" style="min-height:350px; min-width:550px">
    <ul>
      <li>
        <a href="#tabs-name">
          <?php print _txt('fd.name'); ?>
        </a>
      </li>
      <?php if($this->action != "invite"): ?>
        <li>
          <a href="#tabs-id">
            <?php print _txt('fd.ids'); ?>
          </a>
        </li>
        <li>
          <a href="#tabs-email">
            <?php print _txt('fd.email_address.mail'); ?>
          </a>
        </li>
        <li>
          <a href="#tabs-group">
            <?php print _txt('fd.groups'); ?>
          </a>
        </li>
        <li>
          <a href="#tabs-role">
            <?php print _txt('fd.attrs.copr'); ?>
          </a>
        </li>
        <li>
          <a href="#tabs-orgid">
            <?php print _txt('ct.org_identities.pl'); ?>
          </a>
        </li>
      <?php endif; ?>
    </ul>
    <div id="tabs-name">
      <table>
        <?php if($this->action == "compare"): ?>
          <tr>
            <th class="ui-widget-header"><?php print _txt('fd.attribute'); ?></th>
            <th class="ui-widget-header"><?php print Sanitize::html($cur_co['Co']['name']); ?></th>
            <?php if($this->action == "compare"): ?>
                <th class="ui-widget-header"><?php print (!empty($org_identities[0]['OrgIdentity']['o']) ? Sanitize::html($org_identities[0]['OrgIdentity']['o']) : _txt('fd.o')); ?></th>
            <?php endif; ?>
          </tr>
        <?php endif; ?>
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
              print $this->Form->hidden('Name.id');
              print $this->Form->hidden('Name.type', array('default' => 'P'));
              print ($e ? $this->Form->input('Name.honorific', array('default' => $co_people[0]['Name']['honorific']))
                       : Sanitize::html($co_people[0]['Name']['honorific']));
            ?>
          </td>
          <?php if($this->action == "compare"): ?>
            <td>
              <?php print Sanitize::html($org_identities[0]['Name']['honorific']); ?>
            </td>
          <?php endif; ?>
        </tr>
        <tr class="line<?php print ($l % 2); $l++; ?>">
          <th>
            <?php print _txt('fd.name.given'); ?><font class="required">*</font>
          </th>
          <td>
            <?php print ($e ? $this->Form->input('Name.given', array('default' => $co_people[0]['Name']['given']))
                           : Sanitize::html($co_people[0]['Name']['given'])); ?>
          </td>
          <?php if($this->action == "compare"): ?>
            <td>
              <?php print Sanitize::html($org_identities[0]['Name']['given']); ?>
            </td>
          <?php endif; ?>
        </tr>
        <tr class="line<?php print ($l % 2); $l++; ?>">
          <th>
            <?php print _txt('fd.name.middle'); ?>
          </th>
          <td>
            <?php print ($e ? $this->Form->input('Name.middle', array('default' => $co_people[0]['Name']['middle']))
                           : Sanitize::html($co_people[0]['Name']['middle'])); ?>
          </td>
          <?php if($this->action == "compare"): ?>
            <td>
              <?php print Sanitize::html($org_identities[0]['Name']['middle']); ?>
            </td>
          <?php endif; ?>
        </tr>
        <tr class="line<?php print ($l % 2); $l++; ?>">
          <th>
            <?php print _txt('fd.name.family'); ?>
          </th>
          <td>
            <?php print ($e ? $this->Form->input('Name.family', array('default' => $co_people[0]['Name']['family']))
                           : Sanitize::html($co_people[0]['Name']['family'])); ?>
          </td>
          <?php if($this->action == "compare"): ?>
            <td>
              <?php print Sanitize::html($org_identities[0]['Name']['family']); ?>
            </td>
          <?php endif; ?>
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
            <?php print ($e ? $this->Form->input('Name.suffix', array('default' => $co_people[0]['Name']['suffix']))
                           : Sanitize::html($co_people[0]['Name']['suffix'])); ?>
          </td>
          <?php if($this->action == "compare"): ?>
            <td>
              <?php print Sanitize::html($org_identities[0]['Name']['suffix']); ?>
            </td>
          <?php endif; ?>
        </tr>
        <tr>
          <th>
            <i><font class="required"><?php print _txt('fd.req'); ?></font></i><br />
          </th>
          <td>
            <?php
              if($e)
                print $this->Form->submit($submit_label);
            ?>
          </td>
        </tr>
      </table>
      <div style="clear:both;"></div>
    </div> <!-- tabs-name -->
    <?php if($this->action != "invite"): ?>
      <div id="tabs-id" >
        <?php print '<div style="float:left;';
          if($this->action != 'compare')
            print 'width:100%;" class="additionalinfo';
          print '">';
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
                          print " (" . $id['type'] . ")";
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
                if($e && !$es)
                {
                  $linktarget = array('controller' => 'identifiers',
                                      'action'     => 'add',
                                      'copersonid' => $co_people[0]['CoPerson']['id'],
                                      'co'         => $cur_co['Co']['id']
                                     );
                  $linkparams = array('class'  => 'addbutton');
                  print $this->Html->link(_txt('op.add'),
                                          $linktarget,
                                          $linkparams) . "\n";
                }
              ?>
             </tbody>
          </table>
        </div>
        <?php if($this->action == "compare"): ?>
          <div style="float:left">
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
      <div id="tabs-email">
        <?php print '<div style="float:left;';
          if($this->action != 'compare')
            print 'width:100%;" class="additionalinfo';
          print '">';
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
                if($e)
                {
                  if(isset($co_people[0]['EmailAddress']))
                  {
                    foreach($co_people[0]['EmailAddress'] as $ea)
                    {
                      print '<div>';
                        print '<div>';
                          print $this->Html->link($ea['mail'], array('controller' => 'email_addresses', 'action' => 'edit', $ea['id'], 'co' => $cur_co['Co']['id']));
                          print " (" . _txt('en.contact.mail', null, $ea['type']) . ", "
                          . ($ea['verified'] ? _txt('fd.email_address.verified') : _txt('fd.email_address.unverified')) . ")<br />\n";
                        print '</div>';
                        print '<div>';
                          // XXX we already checked for $permissions['edit'], but not ['delete']... should we?
                          print '<a class="deletebutton" title="' . _txt('op.delete') . '" onclick="javascript:js_confirm_delete(\'' . _jtxt(Sanitize::html($ea['mail'])) . '\', \'' . $this->Html->url(array('controller' => 'email_addresses', 'action' => 'delete', $ea['id'], 'co' => $cur_co['Co']['id'])) . '\')";>' . _txt('op.delete') . '</a>' . "\n";
                          print $this->Html->link(_txt('op.edit'),
                                                  array('controller' => 'email_addresses', 'action' => 'edit', $ea['id'], 'co' => $cur_co['Co']['id']),
                                                  array('class' => 'editbutton')) . "\n";
                        print '</div>';
                      print '</div>';
                    }
                  }
                }
                else
                {
                  if(isset($co_people[0]['EmailAddress']))
                  {
                    foreach($co_people[0]['EmailAddress'] as $ea)
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
                }
                if($e)
                {
                  $linktarget = array('controller' => 'email_addresses',
                                      'action'     => 'add',
                                      'copersonid' => $co_people[0]['CoPerson']['id'],
                                      'co'         => $cur_co['Co']['id']
                                     );
                  $linkparams = array('class'  => 'addbutton');

                  print $this->Html->link(_txt('op.add'),
                                           $linktarget,
                                           $linkparams);
                }
              ?>
            </tbody>
          </table>
        </div>
        <?php
          if($this->action == "compare"):
            $l = 1;
        ?>
          <div style="float:left">
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
      <div id="tabs-group">
        <div style="float:left;width:100%" class="additionalinfo">
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
              if(isset($co_people[0]['CoGroupMember']))
              {
                if($e && !$es)  // XXX !$es is probably the wrong permission here
                {
                  if(isset($co_people[0]['CoGroupMember']))
                  {
                    foreach($co_people[0]['CoGroupMember'] as $g)
                    {
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
                }
                else
                {
                  foreach($co_people[0]['CoGroupMember'] as $g)
                  {
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
              if(isset($co_people[0]['CoGroupMember']))
              {
                if($e && !$es)  // XXX !$es is probably the wrong permission here
                {
                  $linktarget = array('controller' => 'co_groups',
                                      'action'     => 'select',
                                      'copersonid' => $co_people[0]['CoPerson']['id'],
                                      'co'         => $cur_co['Co']['id']
                                     );
                  $linkparams = array('class'  => 'addbutton');
                  print $this->Html->link(_txt('op.add'),
                                          $linktarget,
                                          $linkparams) . "\n";
                }
              }
            ?>
          </table>
        </div>
      </div> <!-- tabs-group -->
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
                            if(!isset($permissions['cous']) || empty($permissions['cous']) || $es ||
                               (isset($r['Cou']['name']) && in_array($r['Cou']['name'], $permissions['cous'])))
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
                              if($permissions['edit'])
                                print $this->Html->link(_txt('op.edit'),
                                                 array('controller' => 'co_person_roles', 'action' => 'edit', $r['id'], 'co' => $cur_co['Co']['id']),
                                                 array('class' => 'editbutton')) . "\n";
                              if($permissions['delete'])
                                print '<a class="deletebutton" title="' . _txt('op.delete') . '" onclick="javascript:js_confirm_delete(\'' . _jtxt(Sanitize::html(generateCn($co_people[0]['Name']))) . '\', \'' . $this->Html->url(array('controller' => 'co_person_roles', 'action' => 'delete', $r['id'], 'co' => $cur_co['Co']['id'])) . '\')";>' . _txt('op.delete') . '</a>' . "\n";
                            }
                          ?>
                        </td>
                        <?php endif; // compare ?>
                      </tr>
                      <?php
                        $rcnt++;
                        endforeach; // co_people
                      ?>
                      <?php if($this->action != "compare" && $e && !$es): ?>
                        <tr>
                          <td></td>
                          <td></td>
                          <td></td>
                          <td></td>
                          <td></td>
                          <td></td>
                          <td>
                            <?php
                              $linktarget = array('controller' => 'co_person_roles',
                                                  'action'     => 'add',
                                                  'copersonid' => $co_people[0]['CoPerson']['id'],
                                                  'co'         => $cur_co['Co']['id']
                                                 );
                              $linkparams = array('class'  => 'addbutton');

                              print $this->Html->link(_txt('op.add'),
                                                      $linktarget,
                                                      $linkparams) . "\n";

                            ?>
                          </td>
                        </tr>
                      <?php endif; // compare ?>
                    </tbody>
                  </table>
                <?php endif; // invite ?>
              </td>
            </tr>
          </tbody>
        </table>
      </div> <!-- tabs-role -->
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
    <?php endif; // if not invite ?>
  </div>
</div>

<div id="autogenerate-dialog" title="<?php print _txt('op.id.auto'); ?>">
  <?php print _txt('op.id.auto.confirm'); ?>
</div>
