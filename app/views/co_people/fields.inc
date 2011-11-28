<!--
  /*
   * COmanage Gears CO Person Common Fields
   *
   * Version: $Revision: 81 $
   * Date: $Date: 2011-07-17 19:53:10 -0400 (Sun, 17 Jul 2011) $
   *
   * Copyright (C) 2010-2011 University Corporation for Advanced Internet Development, Inc.
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
   */
-->
<?php
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
    echo $html->link(_txt('op.back'),
                     array('controller' => 'co_people', 'action' => 'index', 'co' => $cur_co['Co']['id']),
                     array('class' => 'cancelbutton'));
    
    if($this->action != "invite")
    {
      echo $html->link(_txt('ct.org_identities.1'),
                     array('controller' => 'org_identities',
                           'action' => ($e && !$es ? 'edit' : 'view'),
                           $co_people[0]['CoOrgIdentityLink'][0]['org_identity_id'],
                           'co' => ($pool_org_identities ? false : $cur_co['Co']['id'])),
                     array('class' => 'linkbutton'));
    }
    
    // Populate the cross reference
    echo $this->Form->hidden('CoPerson.co_id', array('default' => $cur_co['Co']['id'])). "\n";
    echo $this->Form->hidden('CoOrgIdentityLink.0.id'). "\n";
    echo $this->Form->hidden('CoOrgIdentityLink.0.org_identity_id', array('default' => $co_people[0]['CoOrgIdentityLink'][0]['org_identity_id'])). "\n";
    // Default status is 'Pending'
    echo $this->Form->hidden('status', array('default' => 'P')). "\n";
    
    echo '
    <br />
    <br />
    ';
  }
  else
  {
    echo $this->Html->link(_txt('op.back'),
                           array('controller' => 'co_people', 'action' => 'index', 'co' => $cur_co['Co']['id']),
                           array('class' => 'backbutton')) . '
    <br />
    <br />
    ';
  }
  
  // Line number, for rendering
  $l = 1;
?>
<table id="<?php echo $this->action; ?>_co_person" class="ui-widget">
  <tbody>
    <tr>
      <th class="ui-state-default"><?php echo _txt('fd.attrs.cop'); ?></th>
      <?php if($this->action != "invite"): ?>
      <th class="ui-state-default"><?php echo _txt('fd.attrs.copr'); ?></th>
      <?php endif; // invite ?>
    </tr>
    <tr>
      <td valign="top">
        <!-- Person Data -->
        <table id="<?php echo $this->action; ?>_co_person_data" class="ui-widget">
          <tbody>
            <?php if($this->action == "compare"): ?>
            <tr>
              <th class="ui-state-default"><?php echo _txt('fd.attribute'); ?></th>
              <th class="ui-state-default"><?php echo Sanitize::html($cur_co['Co']['name']); ?></th>
              <th class="ui-state-default"><?php echo (!empty($org_identities[0]['OrgIdentity']['o']) ? Sanitize::html($org_identities[0]['OrgIdentity']['o']) : _txt('fd.o')); ?></th>
            </tr>
            <?php endif; ?>
            <tr class="line<?php echo ($l % 2); $l++; ?>">
              <th>
                <?php
                  echo _txt('fd.name.h');
                  
                  if($e)
                    echo " " . _txt('fd.name.h.desc');
                ?>
              </th>
              <td>
                <?php
                  echo $this->Form->hidden('Name.id');
                  echo $this->Form->hidden('Name.type', array('default' => 'P'));
                  echo ($e ? $this->Form->input('Name.honorific', array('default' => $co_people[0]['Name']['honorific']))
                           : Sanitize::html($co_people[0]['Name']['honorific']));
                ?>
              </td>
            <?php if($this->action == "compare"): ?>
              <td>
                <?php echo Sanitize::html($org_identities[0]['Name']['honorific']); ?>
              </td>
            <?php endif; ?>
            </tr>
            <tr class="line<?php echo ($l % 2); $l++; ?>">
              <th>
                <?php echo _txt('fd.name.g'); ?><font class="required">*</font>
              </th>
              <td>
                <?php echo ($e ? $this->Form->input('Name.given', array('default' => $co_people[0]['Name']['given']))
                               : Sanitize::html($co_people[0]['Name']['given'])); ?>
              </td>
            <?php if($this->action == "compare"): ?>
              <td>
                <?php echo Sanitize::html($org_identities[0]['Name']['given']); ?>
              </td>
            <?php endif; ?>
            </tr>
            <tr class="line<?php echo ($l % 2); $l++; ?>">
              <th>
                <?php echo _txt('fd.name.m'); ?>
              </th>
              <td>
                <?php echo ($e ? $this->Form->input('Name.middle', array('default' => $co_people[0]['Name']['middle']))
                               : Sanitize::html($co_people[0]['Name']['middle'])); ?>
              </td>
            <?php if($this->action == "compare"): ?>
              <td>
                <?php echo Sanitize::html($org_identities[0]['Name']['middle']); ?>
              </td>
            <?php endif; ?>
            </tr>
            <tr class="line<?php echo ($l % 2); $l++; ?>">
              <th>
                <?php echo _txt('fd.name.f'); ?>
              </th>
              <td>
                <?php echo ($e ? $this->Form->input('Name.family', array('default' => $co_people[0]['Name']['family']))
                               : Sanitize::html($co_people[0]['Name']['family'])); ?>
              </td>
            <?php if($this->action == "compare"): ?>
              <td>
                <?php echo Sanitize::html($org_identities[0]['Name']['family']); ?>
              </td>
            <?php endif; ?>
            </tr>
            <tr class="line<?php echo ($l % 2); $l++; ?>">
              <th>
                <?php
                  echo _txt('fd.name.s');
                  
                  if($e)
                    echo " " . _txt('fd.name.s.desc');
                ?>
              </th>
              <td>
                <?php echo ($e ? $this->Form->input('Name.suffix', array('default' => $co_people[0]['Name']['suffix']))
                               : Sanitize::html($co_people[0]['Name']['suffix'])); ?>
              </td>
            <?php if($this->action == "compare"): ?>
              <td>
                <?php echo Sanitize::html($org_identities[0]['Name']['suffix']); ?>
              </td>
            <?php endif; ?>
            </tr>
            <?php if($this->action != "invite"): ?>
            <tr class="line<?php echo ($l % 2); $l++; ?>">
              <th>
                <?php echo _txt('fd.ids'); ?>
              </th>
              <td>
                <?php
                  if($e && !$es)
                  {
                    if(isset($co_people[0]['Identifier']))
                    {
                      foreach($co_people[0]['Identifier'] as $id)
                      {
                        // XXX we already checked for $permissions['edit'], but not ['delete']... should we?
                        echo '<a class="deletebutton" title="' . _txt('op.delete') . '" onclick="javascript:js_confirm_delete(\'' . _jtxt(Sanitize::html($id['identifier'])) . '\', \'' . $html->url(array('controller' => 'identifiers', 'action' => 'delete', $id['id'], 'co' => $cur_co['Co']['id'])) . '\')";>' . _txt('op.delete') . '</a>' . "\n";
                        echo $html->link(_txt('op.edit'),
                                         array('controller' => 'identifiers', 'action' => 'edit', $id['id'], 'co' => $cur_co['Co']['id']),
                                         array('class' => 'editbutton')) . "\n";
                        echo $html->link($id['identifier'], array('controller' => 'identifiers', 'action' => 'edit', $id['id'], 'co' => $cur_co['Co']['id']));
                        echo " (" . _txt('en.identifier', null, $id['type']) . ")<br />\n";
                      }
                    }
                    
                    echo $html->link(_txt('op.add'),
                                     array('controller' => 'identifiers', 'action' => 'add', 'copersonid' => $co_people[0]['CoPerson']['id'], 'co' => $cur_co['Co']['id']),
                                     array('class' => 'addbutton'));
                  }
                  else
                  {
                    if(isset($co_people[0]['Identifier']))
                      foreach($co_people[0]['Identifier'] as $id)
                        echo Sanitize::html($id['identifier']) . " (" . _txt('en.identifier', null, $id['type']) . ")<br />\n";
                  }
                ?>
              </td>
            <?php if($this->action == "compare"): ?>
              <td>
                <?php
                  if(isset($org_identities[0]['Identifier']))
                    foreach($org_identities[0]['Identifier'] as $id)
                      echo Sanitize::html($id['identifier']) . " (" . _txt('en.identifier', null, $id['type']) . ")<br />\n";
                ?>
              </td>
            <?php endif; ?>
            </tr>
            <tr class="line<?php echo ($l % 2); $l++; ?>">
              <th>
                <?php echo _txt('fd.mail'); ?>
              </th>
              <td>
                <?php
                  if($e)
                  {
                    if(isset($co_people[0]['EmailAddress']))
                    {
                      foreach($co_people[0]['EmailAddress'] as $ea)
                      {
                        // XXX we already checked for $permissions['edit'], but not ['delete']... should we?
                        echo '<a class="deletebutton" title="' . _txt('op.delete') . '" onclick="javascript:js_confirm_delete(\'' . _jtxt(Sanitize::html($ea['mail'])) . '\', \'' . $html->url(array('controller' => 'email_addresses', 'action' => 'delete', $ea['id'], 'co' => $cur_co['Co']['id'])) . '\')";>' . _txt('op.delete') . '</a>' . "\n";
                        echo $html->link(_txt('op.edit'),
                                         array('controller' => 'email_addresses', 'action' => 'edit', $ea['id'], 'co' => $cur_co['Co']['id']),
                                         array('class' => 'editbutton')) . "\n";
                        echo $html->link($ea['mail'], array('controller' => 'email_addresses', 'action' => 'edit', $ea['id'], 'co' => $cur_co['Co']['id']));
                        echo " (" . _txt('en.contact', null, $ea['type']) . ")<br />\n";
                      }
                    }
                    
                    echo $html->link(_txt('op.add'),
                                     array('controller' => 'email_addresses', 'action' => 'add', 'copersonid' => $co_people[0]['CoPerson']['id'], 'co' => $cur_co['Co']['id']),
                                     array('class' => 'addbutton'));
                  }
                  else
                  {
                    if(isset($co_people[0]['EmailAddress']))
                      foreach($co_people[0]['EmailAddress'] as $ea)
                        echo Sanitize::html($ea['mail']) . " (" . _txt('en.contact', null, $ea['type']) . ")<br />\n";
                  }
                ?>
              </td>
            <?php if($this->action == "compare"): ?>
              <td>
                <?php
                  if(isset($org_identities[0]['EmailAddress']))
                    foreach($org_identities[0]['EmailAddress'] as $ea)
                      echo Sanitize::html($ea['mail']) . " (" . _txt('en.contact', null, $ea['type']) . ")<br />\n";
                ?>
              </td>
            <?php endif; ?>
            </tr>
            <tr class="line<?php echo ($l % 2); $l++; ?>">
              <th>
                <?php echo _txt('fd.groups'); ?>
              </th>
              <td>
                <?php
                  if(isset($co_people[0]['CoGroupMember']))
                  {
                    if($e && !$es)  // XXX !$es is probably the wrong permission here
                    {
                      if(isset($co_people[0]['CoGroupMember']))
                      {
                        foreach($co_people[0]['CoGroupMember'] as $g)
                        {
                          // XXX we already checked for $permissions['edit'], but not ['delete']... should we?
                          echo '<a class="deletebutton" title="' . _txt('op.delete') . '" onclick="javascript:js_confirm_delete(\'' . _jtxt(_txt('fd.group.memin', array(Sanitize::html($g['CoGroup']['name'])))) . '\', \'' . $html->url(array('controller' => 'co_group_members', 'action' => 'delete', $g['id'], 'CoPersonid' => $co_people[0]['CoPerson']['id'], 'co' => $cur_co['Co']['id'])) . '\')";>' . _txt('op.delete') . '</a>' . "\n";
                          echo $html->link($g['CoGroup']['name'], array('controller' => 'co_groups', 'action' => 'edit', $g['co_group_id'], 'co' => $cur_co['Co']['id']));
                          echo "<br />\n";
                        }
                      }
                        
                      echo $html->link(_txt('op.add'),
                                       array('controller' => 'co_groups', 'action' => 'select', 'copersonid' => $co_people[0]['CoPerson']['id'], 'co' => $cur_co['Co']['id']),
                                       array('class' => 'addbutton'));
                    }
                    else
                    {
                      foreach($co_people[0]['CoGroupMember'] as $g)
                      {
                        if($g['member'])
                          echo Sanitize::html($g['CoGroup']['name']) . "<br />\n";
                          
                        if($g['owner'])
                          echo Sanitize::html($g['CoGroup']['name']) . " (" . _txt('fd.group.own') . ")<br />\n";
                      }
                    }
                  }
                ?>
              </td>
            </tr>
            <?php endif; ?>
            <tr>
              <th>
                <i><font class="required"><?php echo _txt('fd.req'); ?></font></i><br />
              </th>
              <td>
                <?php
                  if($e)
                    echo $this->Form->submit($submit_label);
                ?>
              </td>
            </tr>
          </tbody>
        </table>
      </td>
      <td valign="top">
        <?php if($this->action != "invite"): ?>
        <!-- Person Role Data -->
        <table id="<?php echo $this->action; ?>_co_person_roles" class="ui-widget">
          <tbody>
            <tr class="line<?php echo ($l % 2); $l++; ?>">
              <th></th>
              <?php if($this->action == "compare"): ?>
              <th><?php echo _txt('fd.o'); ?></th>
              <th><?php echo _txt('fd.ou'); ?></th>
              <?php else: // compare ?>
              <th><?php echo _txt('fd.cou'); ?></th>
              <?php endif; // compare ?>
              <th><?php echo _txt('fd.title'); ?></th>
              <th><?php echo _txt('fd.affiliation'); ?></th>
              <th><?php echo _txt('fd.valid.f'); ?></th>
              <th><?php echo _txt('fd.valid.u'); ?></th>
              <?php if($this->action != "compare"): ?>
              <th><?php echo _txt('fd.status'); ?></th>
              <th><?php echo _txt('fd.actions'); ?></th>
              <?php endif; // compare ?>
            </tr>
            <?php if($this->action == "compare"): ?>
            <tr class="line<?php echo ($l % 2); $l++; ?>">
              <td></td>
              <td>
                <?php
                  if(isset($org_identities[0]['OrgIdentity']['o']))
                    echo Sanitize::html($org_identities[0]['OrgIdentity']['o']);
                ?>
              </td>
              <td>
                <?php
                  if(isset($org_identities[0]['OrgIdentity']['ou']))
                    echo Sanitize::html($org_identities[0]['OrgIdentity']['ou']);
                ?>
              </td>
              <td>
                <?php
                  if(isset($org_identities[0]['OrgIdentity']['title']))
                    echo Sanitize::html($org_identities[0]['OrgIdentity']['title']);
                ?>
              </td>
              <td>
                <?php
                  if(isset($org_identities[0]['OrgIdentity']['edu_person_affiliation']))
                    echo Sanitize::html($org_identities[0]['OrgIdentity']['edu_person_affiliation']);
                ?>
              </td>
            </tr>
            <?php endif; // compare ?>
            <?php
              $rcnt = 1;
              foreach($co_people[0]['CoPersonRole'] as $r):
            ?>
            <tr class="line<?php echo ($l % 2); $l++; ?>">
              <td><?php print $rcnt; ?></td>
              <?php if($this->action == "compare"): ?>
              <td><?php if(isset($r['o'])) echo Sanitize::html($r['o']); ?></td>
              <td><?php if(isset($r['ou'])) echo Sanitize::html($r['ou']); ?></td>
              <?php else: // compare ?>
              <td><?php if(isset($r['Cou']['name'])) echo Sanitize::html($r['Cou']['name']); ?></td>
              <?php endif; // compare ?>
              <td><?php if(isset($r['title'])) echo Sanitize::html($r['title']); ?></td>
              <td><?php if(isset($r['edu_person_affiliation'])) echo Sanitize::html($r['edu_person_affiliation']); ?></td>
              <td><?php if(isset($r['valid_from']) && $r['valid_from'] > 0) echo $this->Time->format('Y M d', $r['valid_from']); ?></td>
              <td><?php if(isset($r['valid_through']) && $r['valid_through'] > 0) echo $this->Time->format('Y M d', $r['valid_through']); ?></td>
              <?php if($this->action != "compare"): ?>
              <td><?php if(isset($r['status'])) echo _txt('en.status', null, $r['status']); ?></td>
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
                    
                    if($permissions['edit'])
                      echo $html->link(_txt('op.edit'),
                                       array('controller' => 'co_person_roles', 'action' => 'edit', $r['id'], 'co' => $cur_co['Co']['id']),
                                       array('class' => 'editbutton')) . "\n";
                      
                    if($permissions['delete'])
                      echo '<a class="deletebutton" title="' . _txt('op.delete') . '" onclick="javascript:js_confirm_delete(\'' . _jtxt(Sanitize::html(generateCn($co_people[0]['Name']))) . '\', \'' . $html->url(array('controller' => 'co_person_roles', 'action' => 'delete', $r['id'], 'co' => $cur_co['Co']['id'])) . '\')";>' . _txt('op.delete') . '</a>' . "\n";
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
            <tr class="line<?php echo ($l % 2); $l++; ?>">
              <td></td>
              <td>
                <?php
                  echo $html->link(_txt('op.add'),
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