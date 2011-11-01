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

<?php
  // The fields to render are determined by the CMP Enrollment Flow configuration,
  // which is passed to us in $cmp_ef_attributes. The minor gotcha is that MVPAs
  // allow multiple versions their attributes where the enrollment flow configuration
  // interface doesn't currently allow for this (although it should). So we'll
  // follow the config exactly on an add, but on an edit if the fields aren't pulled
  // from LDAP or SAML we'll allow multiple. This will probably need to get rewritten
  // at some point.
  
  // Track which models we've emitted hidden fields for
  $emitted = array();
  
  $l = 1;
?>
<table id="<?php print $this->action; ?>_org_identity" class="ui-widget">
  <tbody>
    <?php foreach ($cmp_ef_attribute_order as $f): ?>
    <?php
      // Find this attribute in the CMP enrollment flow configuration
      $a = find_ef_attribute($cmp_ef_attributes['CmpEnrollmentAttribute'], $f['attr'], $f['type']);
      
      // Skip this one if not found or not permitted
      if(!$a || $a['required'] == RequiredEnum::NotPermitted)
        continue;
      
      // Convert table:column into usable bits
      if(preg_match('/:/', $f['attr']))
      {
        $attr = preg_split('/:/', $f['attr'], 2);
        $m = Inflector::classify($attr[0]);  // names -> Name
        
        // Associated models must be referenced as Model.0.field for HABTM,
        // or Model.field for HasOne
        if(isset($f['assoc']) && $f['assoc'] != 'hasone')
          $d = '.0.';
        else
          $d = '.';
          
        $fattr = $m.$d.$attr[1];
        
        // Create hidden fields for associated models
        
        if(!isset($emitted[$m]))
        {
          print $this->Form->hidden($m.$d.'id');
          print $this->Form->hidden($m.$d.'type', array('default' => $f['type']));
          
          $emitted[$m] = true;
        }
      }
      else
      {
        // Set up matching array
        $attr[0] = 'org_identities';
        $attr[1] = $f['attr'];
        $m = Inflector::classify($attr[0]);  // names -> Name
        $fattr = $attr[1];
      }
    ?>
    <tr class="line<?php print ($l % 2); $l++; ?>">
      <td>
        <?php
          print $f['label'];
          
          if($a['required'] == RequiredEnum::Required)
            print '<font class="required">*</font>';
          
          if($e && isset($f['desc']))
            print " " . $f['desc'];
        ?>
      </td>
      <td>
        <?php
          if(isset($f['select']))
          {
            // Render a select element
            
            print ($e ? $this->Form->select($fattr,
                                            $f['select']['options'],
                                            (isset($org_identities[0][$m][ $attr[1] ])
                                             ? $org_identities[0][$m][ $attr[1] ]
                                             : $f['select']['default']),
                                            array('empty' => false))
                      : Sanitize::html($org_identities[0][$m][ $attr[1] ]));
          }
          else
          {
            // Use default rendering
            
            print ($e ? $this->Form->input($fattr) : Sanitize::html($org_identities[0][$m][ $attr[1] ]));
          }
        ?>
      </td>
    </tr>
    <?php endforeach; ?>
    <?php if($this->action != "add"): ?>
    <tr class="line<?php print ($l % 2); $l++; ?>">
      <td>
        <?php print _txt('fd.ids'); ?>
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
                print '<a class="deletebutton" title="' . _txt('op.delete') .'" onclick="javascript:js_confirm_delete(\'' . _jtxt(Sanitize::html($id['identifier'])) . '\', \'' . $html->url(array('controller' => 'identifiers', 'action' => 'delete', $id['id'])) . '\')";>' . _txt('op.delete') . '</a>' . "\n";
                print $html->link(_txt('op.edit'),
                                  array('controller' => 'identifiers', 'action' => 'edit', $id['id']),
                                  array('class' => 'editbutton')) . "\n";
                print $html->link($id['identifier'], array('controller' => 'identifiers', 'action' => 'edit', $id['id']));
                print " (" . _txt('en.identifier', null, $id['type']) . ")<br />\n";
              }
            }
              
            print $html->link(_txt('op.add'),
                              array('controller' => 'identifiers', 'action' => 'add', 'orgidentityid' => $org_identities[0]['OrgIdentity']['id']),
                              array('class' => 'addbutton'));
          }
          else
          {
            if(isset($org_identities[0]['Identifier']))
              foreach($org_identities[0]['Identifier'] as $id)
                print Sanitize::html($id['identifier']) . " (" . _txt('en.identifier', null, $id['type']) . ")<br />\n";
          }
        ?>
      </td>
    </tr>
    <tr class="line<?php print ($l % 2); $l++; ?>">
      <td>
        <?php print _txt('fd.mail'); ?>
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
                print '<a class="deletebutton" title="' . _txt('op.delete') .'" onclick="javascript:js_confirm_delete(\'' . _jtxt(Sanitize::html($ea['mail'])) . '\', \'' . $html->url(array('controller' => 'email_addresses', 'action' => 'delete', $ea['id'])) . '\')";>' . _txt('op.delete') .'</a>' . "\n";
                print $html->link(_txt('op.edit'),
                                  array('controller' => 'email_addresses', 'action' => 'edit', $ea['id']),
                                  array('class' => 'editbutton')) . "\n";
                print $html->link($ea['mail'], array('controller' => 'email_addresses', 'action' => 'edit', $ea['id']));
                print " (" . _txt('en.contact', null, $ea['type']) . ")<br />\n";
              }
            }
              
            print $html->link(_txt('op.add'),
                              array('controller' => 'email_addresses', 'action' => 'add', 'orgidentityid' => $org_identities[0]['OrgIdentity']['id']),
                              array('class' => 'addbutton'));
          }
          else
          {
            if(isset($org_identities[0]['EmailAddress']))
              foreach($org_identities[0]['EmailAddress'] as $ea)
                print Sanitize::html($ea['mail']) . " (" . _txt('en.contact', null, $ea['type']) . ")<br />\n";
          }
        ?>
      </td>
    </tr>
    <tr class="line<?php print ($l % 2); $l++; ?>">
      <td>
        <?php print _txt('fd.phone'); ?>
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
                print '<a class="deletebutton" title="' . _txt('op.delete') .'" onclick="javascript:js_confirm_delete(\'' . _jtxt(Sanitize::html($t['number'])) . '\', \'' . $html->url(array('controller' => 'telephone_numbers', 'action' => 'delete', $t['id'])) . '\')";>' . _txt('op.delete') .'</a>' . "\n";
                print $html->link(_txt('op.edit'),
                                  array('controller' => 'telephone_numbers', 'action' => 'edit', $t['id']),
                                  array('class' => 'editbutton')) . "\n";
                print $html->link($t['number'], array('controller' => 'telephone_numbers', 'action' => 'edit', $t['id']));
                print " (" . _txt('en.contact', null, $t['type']) . ")<br />\n";
              }
            }
              
            print $html->link(_txt('op.add'),
                              array('controller' => 'telephone_numbers', 'action' => 'add', 'orgidentityid' => $org_identities[0]['OrgIdentity']['id']),
                              array('class' => 'addbutton'));
          }
          else
          {
            if(isset($org_identities[0]['TelephoneNumber']))
              foreach($org_identities[0]['TelephoneNumber'] as $t)
                print Sanitize::html($t['number']) . " (" . _txt('en.contact', null, $t['type']) . ")<br />\n";
          }
        ?>
      </td>
    </tr>
    <tr class="line<?php print ($l % 2); $l++; ?>">
      <td>
        <?php print _txt('fd.address'); ?>
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
                print '<a class="deletebutton" title="' . _txt('op.delete') .'" onclick="javascript:js_confirm_delete(\'' . _jtxt(Sanitize::html($addr['line1'])) . '\', \'' . $html->url(array('controller' => 'addresses', 'action' => 'delete', $addr['id'])) . '\')";>' . _txt('op.delete') .'</a>' . "\n";
                print $html->link(_txt('op.edit'),
                                  array('controller' => 'addresses', 'action' => 'edit', $addr['id']),
                                  array('class' => 'editbutton')) . "\n";
                print $html->link($addr['line1'], array('controller' => 'addresses', 'action' => 'edit', $addr['id']));
                print " (" . _txt('en.contact', null, $addr['type']) . ")<br />\n";
              }
            }
                
            print $html->link(_txt('op.add'),
                              array('controller' => 'addresses', 'action' => 'add', 'orgidentityid' => $org_identities[0]['OrgIdentity']['id']),
                              array('class' => 'addbutton'));
          }
          else
          {
            if(isset($org_identities[0]['Address']))
              foreach($org_identities[0]['Address'] as $addr)
                print Sanitize::html($addr['line1']) . " (" . _txt('en.contact', null, $addr['type']) . ")<br />\n";
          }
        ?>
      </td>
    </tr>
    <?php endif; ?>
    <tr>
      <td>
        <i><font class="required"><?php print _txt('fd.req'); ?></font></i><br />
      </td>
      <td>
        <?php
          if($e)
            print $this->Form->submit($submit_label);
        ?>
      </td>
    </tr>
  </tbody>
</table>