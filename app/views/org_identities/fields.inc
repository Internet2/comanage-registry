<!--
  /*
   * COmanage Gears Organizational Person Common Fields
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
  
  if(($this->action == "add" && $permissions['add'])
      || ($this->action == "edit" && $permissions['edit']))
    $e = true;
  
  // We shouldn't get here if we don't have at least read permission, but check just in case
  
  if(!$e && !$permissions['view'])
    return(false);
    
  if($e)
    echo $html->link(_txt('op.back'),
                     array('controller' => 'org_identities', 'action' => 'index'),
                     array('class' => 'cancelbutton'));
  else
    echo $this->Html->link(_txt('op.back'),
                           array('controller' => 'org_identities', 'action' => 'index'),
                           array('class' => 'backbutton')) . '
    ';
?>
<br />
<br />

<table id="<?php echo $this->action; ?>_org_identity" class="ui-widget">
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
          echo $this->Form->hidden('Name.type', array('default' => 'O'));
          echo ($e ? $this->Form->input('Name.honorific') : Sanitize::html($org_identities[0]['Name']['honorific']));
        ?>
      </td>
    </tr>
    <tr class="line2">
      <td>
        <?php echo _txt('fd.name.g'); ?><font class="required">*</font>
      </td>
      <td>
        <?php echo ($e ? $this->Form->input('Name.given') : Sanitize::html($org_identities[0]['Name']['given'])); ?>
      </td>
    </tr>
    <tr class="line1">
      <td>
        <?php echo _txt('fd.name.m'); ?>
      </td>
      <td>
        <?php echo ($e ? $this->Form->input('Name.middle') : Sanitize::html($org_identities[0]['Name']['middle'])); ?>
      </td>
    <tr class="line2">
      <td>
        <?php echo _txt('fd.name.f'); ?>
      </td>
      <td>
        <?php echo ($e ? $this->Form->input('Name.family') : Sanitize::html($org_identities[0]['Name']['family'])); ?>
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
        <?php echo ($e ? $this->Form->input('Name.suffix') : Sanitize::html($org_identities[0]['Name']['suffix'])); ?>
      </td>
    </tr>
    <tr class="line2">
      <td>
        <?php echo _txt('fd.affiliation'); ?><font class="required">*</font>
      </td>
      <td>
        <?php
          global $cm_lang, $cm_texts;
          
          echo ($e ? $this->Form->select('edu_person_affiliation',
                                         $cm_texts[ $cm_lang ]['en.affil'],
                                         (isset($org_identities[0]['OrgIdentity']['edu_person_affiliation'])
                                          ? $org_identities[0]['OrgIdentity']['edu_person_affiliation']
                                          : "member"),
                                         array('empty' => false))
                   : Sanitize::html($org_identities[0]['OrgIdentity']['edu_person_affiliation'])); ?>
      </td>
    </tr>
    <tr class="line1">
      <td>
        <?php echo _txt('fd.title'); ?>
      </td>
      <td>
        <?php echo ($e ? $this->Form->input('title') : Sanitize::html($org_identities[0]['OrgIdentity']['title'])); ?>
      </td>
    </tr>
    <tr class="line2">
      <td>
        <?php echo _txt('fd.o'); ?>
      </td>
      <td>
        <?php echo ($e ? $this->Form->input('o') : Sanitize::html($org_identities[0]['OrgIdentity']['o'])); ?>
      </td>
    </tr>
    <tr class="line1">
      <td>
        <?php echo _txt('fd.ou'); ?>
      </td>
      <td>
        <?php echo ($e ? $this->Form->input('ou') : Sanitize::html($org_identities[0]['OrgIdentity']['ou'])); ?>
      </td>
    </tr>
    <?php if($this->action != "add"): ?>
    <tr class="line2">
      <td>
        <?php echo _txt('fd.ids'); ?>
      </td>
      <td>
        <?php
          if($e)
          {
            if(isset($org_identities[0]['Identifier']))
            {
              foreach($org_identities[0]['Identifier'] as $id)
              {
                // XXX we already checked for $permissions['edit'], but not ['delete']... should we?
                echo '<a class="deletebutton" title="' . _txt('op.delete') .'" onclick="javascript:js_confirm_delete(\'' . _jtxt(Sanitize::html($id['identifier'])) . '\', \'' . $html->url(array('controller' => 'identifiers', 'action' => 'delete', $id['id'])) . '\')";>' . _txt('op.delete') . '</a>' . "\n";
                echo $html->link(_txt('op.edit'),
                                 array('controller' => 'identifiers', 'action' => 'edit', $id['id']),
                                 array('class' => 'editbutton')) . "\n";
                echo $html->link($id['identifier'], array('controller' => 'identifiers', 'action' => 'edit', $id['id']));
                echo " (" . _txt('en.identifier', null, $id['type']) . ")<br />\n";
              }
            }
              
            echo $html->link(_txt('op.add'),
                             array('controller' => 'identifiers', 'action' => 'add', 'orgidentityid' => $org_identities[0]['OrgIdentity']['id']),
                             array('class' => 'addbutton'));
          }
          else
          {
            if(isset($org_identities[0]['Identifier']))
              foreach($org_identities[0]['Identifier'] as $id)
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
            if(isset($org_identities[0]['EmailAddress']))
            {
              foreach($org_identities[0]['EmailAddress'] as $ea)
              {
                // XXX we already checked for $permissions['edit'], but not ['delete']... should we?
                echo '<a class="deletebutton" title="' . _txt('op.delete') .'" onclick="javascript:js_confirm_delete(\'' . _jtxt(Sanitize::html($ea['mail'])) . '\', \'' . $html->url(array('controller' => 'email_addresses', 'action' => 'delete', $ea['id'])) . '\')";>' . _txt('op.delete') .'</a>' . "\n";
                echo $html->link(_txt('op.edit'),
                                 array('controller' => 'email_addresses', 'action' => 'edit', $ea['id']),
                                 array('class' => 'editbutton')) . "\n";
                echo $html->link($ea['mail'], array('controller' => 'email_addresses', 'action' => 'edit', $ea['id']));
                echo " (" . _txt('en.contact', null, $ea['type']) . ")<br />\n";
              }
            }
              
            echo $html->link(_txt('op.add'),
                             array('controller' => 'email_addresses', 'action' => 'add', 'orgidentityid' => $org_identities[0]['OrgIdentity']['id']),
                             array('class' => 'addbutton'));
          }
          else
          {
            if(isset($org_identities[0]['EmailAddress']))
              foreach($org_identities[0]['EmailAddress'] as $ea)
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
            if(isset($org_identities[0]['TelephoneNumber']))
            {
              foreach($org_identities[0]['TelephoneNumber'] as $t)
              {
                // XXX we already checked for $permissions['edit'], but not ['delete']... should we?
                echo '<a class="deletebutton" title="' . _txt('op.delete') .'" onclick="javascript:js_confirm_delete(\'' . _jtxt(Sanitize::html($t['number'])) . '\', \'' . $html->url(array('controller' => 'telephone_numbers', 'action' => 'delete', $t['id'])) . '\')";>' . _txt('op.delete') .'</a>' . "\n";
                echo $html->link(_txt('op.edit'),
                                 array('controller' => 'telephone_numbers', 'action' => 'edit', $t['id']),
                                 array('class' => 'editbutton')) . "\n";
                echo $html->link($t['number'], array('controller' => 'telephone_numbers', 'action' => 'edit', $t['id']));
                echo " (" . _txt('en.contact', null, $t['type']) . ")<br />\n";
              }
            }
              
            echo $html->link(_txt('op.add'),
                             array('controller' => 'telephone_numbers', 'action' => 'add', 'orgidentityid' => $org_identities[0]['OrgIdentity']['id']),
                             array('class' => 'addbutton'));
          }
          else
          {
            if(isset($org_identities[0]['TelephoneNumber']))
              foreach($org_identities[0]['TelephoneNumber'] as $t)
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
            if(isset($org_identities[0]['Address']))
            {
              foreach($org_identities[0]['Address'] as $addr)
              {
                // XXX we already checked for $permissions['edit'], but not ['delete']... should we?
                echo '<a class="deletebutton" title="' . _txt('op.delete') .'" onclick="javascript:js_confirm_delete(\'' . _jtxt(Sanitize::html($addr['line1'])) . '\', \'' . $html->url(array('controller' => 'addresses', 'action' => 'delete', $addr['id'])) . '\')";>' . _txt('op.delete') .'</a>' . "\n";
                echo $html->link(_txt('op.edit'),
                                 array('controller' => 'addresses', 'action' => 'edit', $addr['id']),
                                 array('class' => 'editbutton')) . "\n";
                echo $html->link($addr['line1'], array('controller' => 'addresses', 'action' => 'edit', $addr['id']));
                echo " (" . _txt('en.contact', null, $addr['type']) . ")<br />\n";
              }
            }
                
            echo $html->link(_txt('op.add'),
                             array('controller' => 'addresses', 'action' => 'add', 'orgidentityid' => $org_identities[0]['OrgIdentity']['id']),
                             array('class' => 'addbutton'));
          }
          else
          {
            if(isset($org_identities[0]['Address']))
              foreach($org_identities[0]['Address'] as $addr)
                echo Sanitize::html($addr['line1']) . " (" . _txt('en.contact', null, $addr['type']) . ")<br />\n";
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