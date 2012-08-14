<!--
/**
 * COmanage Registry CO Person Fields
 *
 * Copyright (C) 2010-12 University Corporation for Advanced Internet Development, Inc.
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
 * @copyright     Copyright (C) 2010-12 University Corporation for Advanced Internet Development, Inc.
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

  if($e)
  {
    print $this->Html->link(_txt('op.back'),
                          array('controller' => 'co_people', 'action' => 'index', 'co' => $cur_co['Co']['id']),
                          array('class' => 'cancelbutton'));
    
    if($this->action != "invite")
    {
      print $this->Html->link(_txt('ct.org_identities.1'),
                            array('controller' => 'org_identities',
                                  'action' => ($e && !$es ? 'edit' : 'view'),
                                  $co_people[0]['CoOrgIdentityLink'][0]['org_identity_id'],
                                  'co' => ($pool_org_identities ? false : $cur_co['Co']['id'])),
                            array('class' => 'linkbutton'));

     // If demographics data does not exist, demographics controller will redirect to add
      $args =  array('controller' => 'co_nsf_demographics',
                     'action'     => 'editself',
                     'co'         => ($pool_org_identities ? false : $cur_co['Co']['id']));
      $classArg = array('class' => 'linkbutton');
      print $this->Html->link(_txt('ct.co_nsf_demographics.1'),
                              $args,
                              $classArg);
    }
    
    // Populate the cross reference
    print $this->Form->hidden('CoPerson.co_id', array('default' => $cur_co['Co']['id'])). "\n";
    print $this->Form->hidden('CoOrgIdentityLink.0.id'). "\n";
    print $this->Form->hidden('CoOrgIdentityLink.0.org_identity_id', array('default' => $co_people[0]['CoOrgIdentityLink'][0]['org_identity_id'])). "\n";
    // Default status is 'Pending'
    echo $this->Form->hidden('status', array('default' => 'P')). "\n";
  }
  else
  {
    print $this->Html->link(_txt('op.back'),
                           array('controller' => 'co_people', 'action' => 'index', 'co' => $cur_co['Co']['id']),
                           array('class' => 'backbutton'));
  }
  
  if($this->action != "add" && $this->action != "invite") {
    print $this->Html->link(
      _txt('op.history'),
      array(
        'controller' => 'history_records',
        'action' => 'index',
        'copersonid' => $co_people[0]['CoPerson']['id']
      ),
      array('class' => 'historybutton')
    );
  }
  
  // Line number, for rendering
  $l = 1;
?>
<br />
<br />
<script type="text/javascript">
  <!-- JS specific to these fields -->
  
  function js_confirm_autogenerate(url)
  {
    // Generate a dialog box confirming the autogeneration of identifiers.
    // On confirmation, forward to <url>, which executes the autogeneration.
    
    // Set the title of the dialog    
    $("#dialog").dialog("option", "title", "<?php print _txt('op.id.auto'); ?>");
    
    // Set the body of the dialog
    $("#dialog-text").text('<?php print _txt('op.id.auto.confirm'); ?>');
    
    // Set the dialog buttons
    $("#dialog").dialog("option",
                        "buttons",
                        {
                          "<?php print _txt('op.cancel'); ?>": function() { $(this).dialog("close"); },
                          "<?php print _txt('op.id.auto'); ?>": function() { window.location=url; }
                        });
   
    // Open the dialog
    $('#dialog').dialog('open');
  }

// Turn on Tabs
$(function() {
    $( "#tabs" ).tabs();
  });
</script>

<div id="<?php print $this->action; ?>_co_person" style=" float:left; height:auto;">
  <div id="tabs" style="min-height:350px; width:550px">
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
      <?php endif; ?>
    </ul>
    <div id="tabs-name">
      <table>
        <tr>
          <th class="ui-state-default"><?php print _txt('fd.attribute'); ?></th>
          <th class="ui-state-default"><?php print Sanitize::html($cur_co['Co']['name']); ?></th>
          <?php if($this->action == "compare"): ?>
              <th class="ui-state-default"><?php print (!empty($org_identities[0]['OrgIdentity']['o']) ? Sanitize::html($org_identities[0]['OrgIdentity']['o']) : _txt('fd.o')); ?></th>
          <?php endif; ?>
        </tr>
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
      <div id="tabs-id">
        <div style="float:left">
          <table>
            <tbody>
              <tr>
                <th class="ui-state-default"><?php print Sanitize::html($cur_co['Co']['name']); ?></th>
              </tr>
                <?php
                  $l = 1;
                  if($e && !$es)
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
                            // XXX we already checked for $permissions['edit'], but not ['delete']... should we?
                            print '<a class="deletebutton" title="' . _txt('op.delete') . '" onclick="javascript:js_confirm_delete(\'' . _jtxt(Sanitize::html($id['identifier'])) . '\', \'' . $this->Html->url(array('controller' => 'identifiers', 'action' => 'delete', $id['id'], 'co' => $cur_co['Co']['id'])) . '\')";>' . _txt('op.delete') . '</a>' . "\n";
                            print $this->Html->link(_txt('op.edit'),
                                             array('controller' => 'identifiers', 'action' => 'edit', $id['id'], 'co' => $cur_co['Co']['id']),
                                             array('class' => 'editbutton')) . "\n";
                            print $this->Html->link($id['identifier'], array('controller' => 'identifiers', 'action' => 'edit', $id['id'], 'co' => $cur_co['Co']['id']));
                            print " (" . $id['type'] . ")<br />\n";
                          print '</td>';
                        print '</tr>';
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
        <?php
          if($e && !$es)
          {
            print $this->Html->link(_txt('op.add'),
                                    array('controller' => 'identifiers', 'action' => 'add', 'copersonid' => $co_people[0]['CoPerson']['id'], 'co' => $cur_co['Co']['id']),
                                    array('class' => 'addbutton')) . "\n";
            if(!empty($co_identifier_assignments)) {
              print '<a
                      class="autobutton"
                      title="' . _txt('op.id.auto') . '"
                      onclick="javascript:js_confirm_autogenerate(\'' . $this->Html->url(array('controller' => 'identifiers',
                                                                                               'action' => 'assign',
                                                                                               'copersonid' => $co_people[0]['CoPerson']['id'],
                                                                                               'co' => $cur_co['Co']['id'])) . '\')";>'
                      . _txt('op.id.auto')
                      . "</a>\n";
            }
          }
        ?>
        <?php if($this->action == "compare"): ?>
          <div style="float:left">
            <table>
              <tbody>
                <tr>
                  <th class="ui-state-default"><?php print (!empty($org_identities[0]['OrgIdentity']['o']) ? Sanitize::html($org_identities[0]['OrgIdentity']['o']) : _txt('fd.o')); ?></th>
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
        <div style="float:left">
          <table>
            <tbody>
              <tr>
                <th class="ui-state-default"><?php print Sanitize::html($cur_co['Co']['name']); ?></th>
              </tr>
              <?php
                $l = 1;
                if($e)
                {
                  if(isset($co_people[0]['EmailAddress']))
                  {
                    foreach($co_people[0]['EmailAddress'] as $ea)
                    {
                      print '<tr class="line';
                      print ($l % 2);
                      $l++;
                      print '">';
                        print '<td>';
                          // XXX we already checked for $permissions['edit'], but not ['delete']... should we?
                          print '<a class="deletebutton" title="' . _txt('op.delete') . '" onclick="javascript:js_confirm_delete(\'' . _jtxt(Sanitize::html($ea['mail'])) . '\', \'' . $this->Html->url(array('controller' => 'email_addresses', 'action' => 'delete', $ea['id'], 'co' => $cur_co['Co']['id'])) . '\')";>' . _txt('op.delete') . '</a>' . "\n";
                          print $this->Html->link(_txt('op.edit'),
                                                  array('controller' => 'email_addresses', 'action' => 'edit', $ea['id'], 'co' => $cur_co['Co']['id']),
                                                  array('class' => 'editbutton')) . "\n";
                          print $this->Html->link($ea['mail'], array('controller' => 'email_addresses', 'action' => 'edit', $ea['id'], 'co' => $cur_co['Co']['id']));
                          print " (" . _txt('en.contact', null, $ea['type']) . ")<br />\n";

                        print '</td>';
                      print '</tr>';
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
                          print Sanitize::html($ea['mail']) . " (" . _txt('en.contact', null, $ea['type']) . ")<br />\n";
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
          <div style="float:left">
            <table>
              <tbody>
                <tr>
                  <th class="ui-state-default"><?php print (!empty($org_identities[0]['OrgIdentity']['o']) ? Sanitize::html($org_identities[0]['OrgIdentity']['o']) : _txt('fd.o')); ?></th>
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
                          print Sanitize::html($ea['mail']) . " (" . _txt('en.contact', null, $ea['type']) . ")<br />\n";
                        print '</td>';
                      print '</tr>';

                    }
                  }
                ?>
              </tbody>
            </table>
          </div>
        <?php endif; // compare ?>
        <?php
          if($e)
          {
             print $this->Html->link(_txt('op.add'),
                                     array('controller' => 'email_addresses', 'action' => 'add', 'copersonid' => $co_people[0]['CoPerson']['id'], 'co' => $cur_co['Co']['id']),
                                     array('class' => 'addbutton'));
          }
        ?>
      </div> <!-- tabs-email -->
      <div id="tabs-group">
        <div style="float:left;">
          <table>
            <tr>
              <th class="ui-state-default"><?php print Sanitize::html($cur_co['Co']['name']); ?></th>
            </tr>
            <?php
              if(isset($co_people[0]['CoGroupMember']))
              {
                if($e && !$es)  // XXX !$es is probably the wrong permission here
                {
                  if(isset($co_people[0]['CoGroupMember']))
                  {
                    foreach($co_people[0]['CoGroupMember'] as $g)
                    {
                      print '<tr class="line';
                      print ($l % 2);
                      print '">';
                        $l++;
                        print '<td>';
                          // XXX we already checked for $permissions['edit'], but not ['delete']... should we?
                          print '<a class="deletebutton" title="' . _txt('op.delete') . '" onclick="javascript:js_confirm_delete(\'' . _jtxt(_txt('fd.group.memin', array(Sanitize::html($g['CoGroup']['name'])))) . '\', \'' . $this->Html->url(array('controller' => 'co_group_members', 'action' => 'delete', $g['id'], 'CoPersonid' => $co_people[0]['CoPerson']['id'], 'co' => $cur_co['Co']['id'])) . '\')";>' . _txt('op.delete') . '</a>' . "\n";
                          print $this->Html->link($g['CoGroup']['name'], array('controller' => 'co_groups', 'action' => 'edit', $g['co_group_id'], 'co' => $cur_co['Co']['id']));
                          print "<br />\n";
                        print '</td>';
                      print '</tr>';
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
            ?>
          </table>
        </div>
        <?php
          if(isset($co_people[0]['CoGroupMember']))
          {
            if($e && !$es)  // XXX !$es is probably the wrong permission here
            {
              print $this->Html->link(_txt('op.add'),
                                      array('controller' => 'co_groups', 'action' => 'select', 'copersonid' => $co_people[0]['CoPerson']['id'], 'co' => $cur_co['Co']['id']),
                                      array('class' => 'addbutton'));
            }
          }
        ?>
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
                        <th></th>
                        <?php if($this->action == "compare"): ?>
                          <th><?php print _txt('fd.o'); ?></th>
                          <th><?php print _txt('fd.ou'); ?></th>
                          <?php else: // compare ?>
                          <th><?php print _txt('fd.cou'); ?></th>
                          <?php endif; // compare ?>
                          <th><?php print _txt('fd.title'); ?></th>
                          <th><?php print _txt('fd.affiliation'); ?></th>
                          <th><?php print _txt('fd.valid_from'); ?></th>
                          <th><?php print _txt('fd.valid_through'); ?></th>
                          <?php if($this->action != "compare"): ?>
                          <th><?php print _txt('fd.status'); ?></th>
                          <th><?php print _txt('fd.actions'); ?></th>
                        <?php endif; // compare ?>
                      </tr>
                      <?php if($this->action == "compare"): ?>
                        <tr class="line<?php print ($l % 2); $l++; ?>">
                          <td></td>
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
                        <td><?php print $rcnt; ?></td>
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
                      <tr class="line<?php print ($l % 2); $l++; ?>">
                        <td></td>
                        <td>
                          <?php
                            print $this->Html->link(_txt('op.add'),
                                                    array('controller' => 'co_person_roles', 'action' => 'add', 'copersonid' => $co_people[0]['CoPerson']['id'], 'co' => $cur_co['Co']['id']),
                                                    array('class' => 'addbutton'));
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
    <?php endif; // if not invite ?>
</div>
