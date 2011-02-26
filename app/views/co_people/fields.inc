<!--
  /*
   * COmanage Gears CO Person Common Fields
   *
   * Version: $Revision$
   * Date: $Date$
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
      echo $html->link(_txt('ct.org_people.1'),
                     array('controller' => 'org_people', 'action' => $this->action, $co_people[0]['CoPersonSource'][0]['org_person_id']),
                     array('class' => 'linkbutton'));
    }
    
    // This is for beforeFilter
    echo $this->Form->hidden('Co.id', array('default' => $cur_co['Co']['id'])). "\n";
    // And this is to populate the cross reference
    echo $this->Form->hidden('CoPersonSource.0.id'). "\n";
    echo $this->Form->hidden('CoPersonSource.0.co_id', array('default' => $cur_co['Co']['id'])). "\n";
    echo $this->Form->hidden('CoPersonSource.0.org_person_id', array('default' => $co_people[0]['CoPersonSource'][0]['org_person_id'])). "\n";
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
?>
<table id="<?php echo $this->action; ?>_co_person" class="ui-widget">
  <tbody>
    <tr class="line1">
      <td>
        <?php
          echo _txt('fd.name.h');
          
          if($e)
            echo " " . _txt('fd.name.h.desc');
        ?>
      </td>
      <td>
        <?php
          echo $this->Form->hidden('Name.id');
          echo $this->Form->hidden('Name.type', array('default' => 'P'));
          echo ($e ? $this->Form->input('Name.honorific', array('default' => $co_people[0]['Name']['honorific']))
                   : Sanitize::html($co_people[0]['Name']['honorific']));
        ?>
      </td>
    </tr>
    <tr class="line2">
      <td>
        <?php echo _txt('fd.name.g'); ?><font class="required">*</font>
      </td>
      <td>
        <?php echo ($e ? $this->Form->input('Name.given', array('default' => $co_people[0]['Name']['given']))
                       : Sanitize::html($co_people[0]['Name']['given'])); ?>
      </td>
    </tr>
    <tr class="line1">
      <td>
        <?php echo _txt('fd.name.m'); ?>
      </td>
      <td>
        <?php echo ($e ? $this->Form->input('Name.middle', array('default' => $co_people[0]['Name']['middle']))
                       : Sanitize::html($co_people[0]['Name']['middle'])); ?>
      </td>
    <tr class="line2">
      <td>
        <?php echo _txt('fd.name.f'); ?>
      </td>
      <td>
        <?php echo ($e ? $this->Form->input('Name.family', array('default' => $co_people[0]['Name']['family']))
                       : Sanitize::html($co_people[0]['Name']['family'])); ?>
      </td>
    </tr>
    <tr class="line1">
      <td>
        <?php
          echo _txt('fd.name.s');
          
          if($e)
            echo " " . _txt('fd.name.s.desc');
        ?>
      </td>
      <td>
        <?php echo ($e ? $this->Form->input('Name.suffix', array('default' => $co_people[0]['Name']['suffix']))
                       : Sanitize::html($co_people[0]['Name']['suffix'])); ?>
      </td>
    </tr>
    <tr class="line2">
      <td>
        <?php echo Sanitize::html($cur_co['Co']['name']) . " " . _txt('fd.affiliation'); ?><font class="required">*</font>
      </td>
      <td>
        <?php
          global $cm_lang, $cm_texts;
          
          echo ($e ? $this->Form->select('edu_person_affiliation',
                                         $cm_texts[ $cm_lang ]['en.affil'],
                                         (isset($co_people[0]['CoPerson']['edu_person_affiliation'])
                                          ? $co_people[0]['CoPerson']['edu_person_affiliation']
                                          : "member"),
                                         array('empty' => false))
                   : Sanitize::html($co_people[0]['CoPerson']['edu_person_affiliation']));
        ?>
      </td>
    </tr>
    <tr class="line1">
      <td>
        <?php echo $cur_co['Co']['name'] . " " . _txt('fd.title'); ?>
      </td>
      <td>
        <?php echo (($e && !$es) ? $this->Form->input('title', array('default' => $co_people[0]['CoPerson']['title']))
                                 : Sanitize::html($co_people[0]['CoPerson']['title'])); ?>
      </td>
    </tr>
    <tr class="line2">
      <td>
        <?php echo _txt('fd.o'); ?>
      </td>
      <td>
        <?php echo (($e && !$es) ? $this->Form->input('o', array('default' => $cur_co['Co']['name']))
                                 : Sanitize::html($co_people[0]['CoPerson']['o'])); ?>
      </td>
    </tr>
    <tr class="line1">
      <td>
        <?php echo _txt('fd.ou'); ?>
      </td>
      <td>
        <?php echo (($e && !$es) ? $this->Form->input('ou') : Sanitize::html($co_people[0]['CoPerson']['ou'])); ?>
      </td>
    </tr>
    <tr class="line2">
      <td>
        <?php echo _txt('fd.valid.f'); if($e && !$es) echo " " . _txt('fd.valid.f.desc'); ?>
      </td>
      <td>
        <?php echo (($e && !$es)
                    ? $this->Form->text('valid_from', array('class' => 'datepicker-f'))
                    : (($co_people[0]['CoPerson']['valid_from'] > 0) ? strftime("%F", strtotime($co_people[0]['CoPerson']['valid_from'])) : "")); ?>
      </td>
    </tr>
    <tr class="line1">
      <td>
        <?php echo _txt('fd.valid.u'); if($e && !$es) echo " " . _txt('fd.valid.u.desc'); ?>
      </td>
      <td>
        <?php echo (($e && !$es)
                    ? $this->Form->text('valid_through', array('class' => 'datepicker-u'))
                    : (($co_people[0]['CoPerson']['valid_through'] > 0) ? strftime("%F", strtotime($co_people[0]['CoPerson']['valid_through'])) : "")); ?>
      </td>
    </tr>
    <?php if($this->action != "invite"): ?>
    <tr class="line2">
      <td>
        <?php echo _txt('fd.ids'); ?>
      </td>
      <td>
        <?php
          if($e && !$es)
          {
            if(isset($co_people[0]['Identifier']))
            {
              foreach($co_people[0]['Identifier'] as $id)
              {
                // XXX we already checked for $permissions['edit'], but not ['delete']... should we?
                echo '<a class="deletebutton" title="' . _txt('op.delete') . '" onclick="javascript:js_confirm_delete(\'' . Sanitize::html($id['identifier']) . '\', \'' . $html->url(array('controller' => 'identifiers', 'action' => 'delete', $id['id'], 'co' => $cur_co['Co']['id'])) . '\')";>' . _txt('op.delete') . '</a>' . "\n";
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
    </tr>
    <tr class="line1">
      <td>
        <?php echo _txt('fd.mail'); ?>
      </td>
      <td>
        <?php
          if($e)
          {
            if(isset($co_people[0]['EmailAddress']))
            {
              foreach($co_people[0]['EmailAddress'] as $ea)
              {
                // XXX we already checked for $permissions['edit'], but not ['delete']... should we?
                echo '<a class="deletebutton" title="' . _txt('op.delete') . '" onclick="javascript:js_confirm_delete(\'' . Sanitize::html($ea['mail']) . '\', \'' . $html->url(array('controller' => 'email_addresses', 'action' => 'delete', $ea['id'], 'co' => $cur_co['Co']['id'])) . '\')";>' . _txt('op.delete') . '</a>' . "\n";
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
    </tr>
    <tr class="line2">
      <td>
        <?php echo _txt('fd.phone'); ?>
      </td>
      <td>
        <?php
          if($e)
          {
            if(isset($co_people[0]['TelephoneNumber']))
            {
              foreach($co_people[0]['TelephoneNumber'] as $t)
              {
                // XXX we already checked for $permissions['edit'], but not ['delete']... should we?
                echo '<a class="deletebutton" title="' . _txt('op.delete') . '" onclick="javascript:js_confirm_delete(\'' . Sanitize::html($t['number']) . '\', \'' . $html->url(array('controller' => 'telephone_numbers', 'action' => 'delete', $t['id'], 'co' => $cur_co['Co']['id'])) . '\')";>' . _txt('op.delete') . '</a>' . "\n";
                echo $html->link(_txt('op.edit'),
                                 array('controller' => 'telephone_numbers', 'action' => 'edit', $t['id'], 'co' => $cur_co['Co']['id']),
                                 array('class' => 'editbutton')) . "\n";
                echo $html->link($t['number'], array('controller' => 'telephone_numbers', 'action' => 'edit', $t['id'], 'co' => $cur_co['Co']['id']));
                echo " (" . _txt('en.contact', null, $t['type']) . ")<br />\n";
              }
            }
            
            echo $html->link(_txt('op.add'),
                             array('controller' => 'telephone_numbers', 'action' => 'add', 'copersonid' => $co_people[0]['CoPerson']['id'], 'co' => $cur_co['Co']['id']),
                             array('class' => 'addbutton'));
          }
          else
          {
            if(isset($co_people[0]['TelephoneNumber']))
              foreach($co_people[0]['TelephoneNumber'] as $t)
                echo Sanitize::html($t['number']) . " (" . _txt('en.contact', null, $t['type']) . ")<br />\n";
          }
        ?>
      </td>
    </tr>
    <tr class="line1">
      <td>
        <?php echo _txt('fd.address'); ?>
      </td>
      <td>
        <?php
          if($e)
          {
            if(isset($co_people[0]['Address']))
            {
              foreach($co_people[0]['Address'] as $addr)
              {
                // XXX we already checked for $permissions['edit'], but not ['delete']... should we?
                echo '<a class="deletebutton" title="' . _txt('op.delete') . '" onclick="javascript:js_confirm_delete(\'' . Sanitize::html($addr['line1']) . '\', \'' . $html->url(array('controller' => 'addresses', 'action' => 'delete', $addr['id'], 'co' => $cur_co['Co']['id'])) . '\')";>' . _txt('op.delete') . '</a>' . "\n";
                echo $html->link(_txt('op.edit'),
                                 array('controller' => 'addresses', 'action' => 'edit', $addr['id'], 'co' => $cur_co['Co']['id']),
                                 array('class' => 'editbutton')) . "\n";
                echo $html->link($addr['line1'], array('controller' => 'addresses', 'action' => 'edit', $addr['id'], 'co' => $cur_co['Co']['id']));
                echo " (" . _txt('en.contact', null, $addr['type']) . ")<br />\n";
              }
            }
                
            echo $html->link(_txt('op.add'),
                             array('controller' => 'addresses', 'action' => 'add', 'copersonid' => $co_people[0]['CoPerson']['id'], 'co' => $cur_co['Co']['id']),
                             array('class' => 'addbutton'));
          }
          else
          {
            if(isset($co_people[0]['Address']))
              foreach($co_people[0]['Address'] as $addr)
                echo Sanitize::html($addr['line1']) . " (" . _txt('en.contact', null, $addr['type']) . ")<br />\n";
          }
        ?>
      </td>
    </tr>
    <tr class="line2">
      <td>
        <?php echo _txt('fd.groups'); ?>
      </td>
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
                  echo '<a class="deletebutton" title="' . _txt('op.delete') . '" onclick="javascript:js_confirm_delete(\'' . _txt('fd.group.memin', array(Sanitize::html($g['CoGroup']['name']))) . '\', \'' . $html->url(array('controller' => 'co_group_members', 'action' => 'delete', $g['id'], 'copersonid' => $co_people[0]['CoPerson']['id'], 'co' => $cur_co['Co']['id'])) . '\')";>' . _txt('op.delete') . '</a>' . "\n";
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
                  echo Sanitize::html($g['CoGroup']['name']) . " (" . _txt('fd.group.own') . "<br />\n";
              }
            }
          }
        ?>
      </td>
    </tr>
    <?php endif; ?>
    <tr>
      <td>
        <i><font class="required"><?php echo _txt('fd.req'); ?></font></i><br />
      </td>
      <td>
        <?php
          if($e)
            echo $this->Form->submit($submit_label);
        ?>
      </td>
    </tr>
  </tbody>
</table>