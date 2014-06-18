<!--
/**
 * COmanage Registry CO Person Role Fields
 *
 * Copyright (C) 2011-14 University Corporation for Advanced Internet Development, Inc.
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
 * @copyright     Copyright (C) 2011-14 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.2
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

  if(($this->action == "add" && $permissions['add'])
      || ($this->action == "edit" && $permissions['edit']))
    $e = true;

  if($this->action == "edit" && $permissions['editself'])
    $es = true;

  // We shouldn't get here if we don't have at least read permission, but check just in case
  
  if(!$e && !$permissions['view'])
    return(false);

  if($e)
  {
    // This is for beforeFilter
    echo $this->Form->hidden('Co.id', array('default' => $cur_co['Co']['id'])). "\n";
    // And this is to link to the co person
    echo $this->Form->hidden('co_person_id', array('default' => $co_people[0]['CoPerson']['id'])). "\n";
    // Default status is 'Active'
    echo $this->Form->hidden('status', array('default' => 'A')). "\n";
    // Make sure ID gets carried over
    if(isset($co_person_roles[0]['CoPersonRole']['id']))
      print $this->Form->hidden('id', array('default' => $co_person_roles[0]['CoPersonRole']['id']));
  }
  
  // Line number, for rendering
  $l = 1;
?>

<script>
$(function() {
  // Ennumeration for tabs
  var tabid = new Object();
  tabid["role"]    = 0;
  tabid["phone"]   = 1;
  tabid["address"] = 2;

  // Turn on Tabs
  var $tabs = $( "#tabs" ).tabs();
  
  // If returning to this page via redirect, open last used tab
  <?php if(isset($this->request->params['named']['tab'])): ?>
    var selectedtab = "<?php print($this->request->params['named']['tab']); ?>";

    $tabs.tabs('option', 'active', tabid[selectedtab] );
  <?php endif; ?>
});
</script>
<div id="<?php print $this->action; ?>_co_person">
  <div id="tabs">
    <ul>
      <li>
        <a href="#tabs-role">
          <?php print _txt('fd.attrs.copr'); ?>
        </a>
      </li>
      <?php if($this->action != "add"): ?>
        <li>
          <a href="#tabs-phone">
            <?php print _txt('fd.telephone_number.number'); ?>
          </a>
        </li>
        <li>
          <a href="#tabs-address">
            <?php print _txt('fd.address'); ?>
          </a>
        </li>
      <?php endif; ?>
    </ul>
    <div id="tabs-role">
      <table id="<?php echo $this->action; ?>_co_person_role" class="ui-widget">
        <tbody>
          <?php if(!empty($permissions['cous'])): ?>
          <tr class="line<?php echo ($l % 2); $l++; ?>">
            <td>
              <?php echo Sanitize::html($cur_co['Co']['name']) . " " . _txt('fd.cou'); ?><font class="required">*</font>
            </td>
            <td>
              <?php
                $attrs['value'] = (isset($co_person_roles[0]['CoPersonRole']['cou_id'])
                                   ? $co_person_roles[0]['CoPersonRole']['cou_id']
                                   : 0);
                $attrs['empty'] = false;
                
                if($e && !$es) {
                  print $this->Form->select('cou_id',
                                            $permissions['cous'],
                                            $attrs);
                  
                  if($this->Form->isFieldError('cou_id')) {
                    print $this->Form->error('cou_id');
                  }
                } else {
                  print Sanitize::html($co_person_roles[0]['Cou']['name']);
                }
              ?>
            </td>
          <?php endif; ?>
          </tr>
          <tr class="line<?php echo ($l % 2); $l++; ?>">
            <td>
              <?php echo Sanitize::html($cur_co['Co']['name']) . " " . _txt('fd.affiliation'); ?><font class="required">*</font>
            </td>
            <td>
              <?php
                global $cm_lang, $cm_texts;
                $attrs['value'] = (isset($co_person_roles[0]['CoPersonRole']['affiliation'])
                                   ? $co_person_roles[0]['CoPersonRole']['affiliation']
                                   : "M");
                $attrs['empty'] = false;
                
                if($e && !$es) {
                  print $this->Form->select('affiliation',
                                            $cm_texts[ $cm_lang ]['en.affil'],
                                            $attrs);
                  
                  if($this->Form->isFieldError('affiliation')) {
                    print $this->Form->error('affiliation');
                  }
                } else {
                  print $cm_texts[ $cm_lang ]['en.affil'][ $co_person_roles[0]['CoPersonRole']['affiliation']];
                }
              ?>
            </td>
          </tr>
          <tr class="line<?php echo ($l % 2); $l++; ?>">
            <td>
              <?php echo $cur_co['Co']['name'] . " " . _txt('fd.title'); ?>
            </td>
            <td>
              <?php echo (($e && !$es) ? $this->Form->input('title', array('default' =>
                                                                           isset($co_person_roles[0]['CoPersonRole']['title'])
                                                                           ? $co_person_roles[0]['CoPersonRole']['title']
                                                                           : ""))
                                       : Sanitize::html($co_person_roles[0]['CoPersonRole']['title'])); ?>
            </td>
          </tr>
          <tr class="line<?php echo ($l % 2); $l++; ?>">
            <td>
              <?php echo _txt('fd.o'); ?>
            </td>
            <td>
              <?php echo (($e && !$es) ? $this->Form->input('o',
                                                            ($this->action == 'add'
                                                             ? array('default' => $cur_co['Co']['name'])
                                                             : array()))
                                       : Sanitize::html($co_person_roles[0]['CoPersonRole']['o'])); ?>
            </td>
          </tr>
          <tr class="line<?php echo ($l % 2); $l++; ?>">
            <td>
              <?php echo _txt('fd.ou'); ?>
            </td>
            <td>
              <?php echo (($e && !$es) ? $this->Form->input('ou') : Sanitize::html($co_person_roles[0]['CoPersonRole']['ou'])); ?>
            </td>
          </tr>
          <tr class="line<?php echo ($l % 2); $l++; ?>">
            <td>
              <?php echo _txt('fd.sponsor') . " ";
                    echo _txt('fd.sponsor.desc');
              ?>
            </td>
            <td>
              <?php
                if(isset($sponsors) && isset($co_person_roles[0]['CoPersonRole']['sponsor_co_person_id'])) {
                  print (($e && !$es) ? $this->Form->select('sponsor_co_person_id', $sponsors) : Sanitize::html($sponsors[ $co_person_roles[0]['CoPersonRole']['sponsor_co_person_id'] ]));
                }
              ?>
            </td>
          </tr>
          <tr class="line<?php echo ($l % 2); $l++; ?>">
            <td>
              <?php echo _txt('fd.valid_from'); if($e && !$es) echo " " . _txt('fd.valid_from.desc'); ?>
            </td>
            <td>
              <?php echo (($e && !$es)
                          ? $this->Form->text('valid_from', array('class' => 'datepicker-f'))
                          : (($co_person_roles[0]['CoPersonRole']['valid_from'] > 0) ? strftime("%F", strtotime($co_person_roles[0]['CoPersonRole']['valid_from'])) : "")); ?>
            </td>
          </tr>
          <tr class="line<?php echo ($l % 2); $l++; ?>">
            <td>
              <?php echo _txt('fd.valid_through'); if($e && !$es) echo " " . _txt('fd.valid_through.desc'); ?>
            </td>
            <td>
              <?php echo (($e && !$es)
                          ? $this->Form->text('valid_through', array('class' => 'datepicker-u'))
                          : (($co_person_roles[0]['CoPersonRole']['valid_through'] > 0) ? strftime("%F", strtotime($co_person_roles[0]['CoPersonRole']['valid_through'])) : "")); ?>
            </td>
          </tr>
          <?php
            // Check for extended attributes and render
            
            $cl = 'Co' . $cur_co['Co']['id'] . 'PersonExtendedAttribute';
            
            if(!empty($cur_co['CoExtendedAttribute']))
            {
              echo $this->Form->hidden($cl . '.id');
              
              foreach($cur_co['CoExtendedAttribute'] as $c):
          ?>
          <tr class="line<?php echo ($l % 2); $l++; ?>">
            <td>
              <?php echo $c['display_name']; ?>
            </td>
            <td>
              <?php
                $attr = strtolower($c['name']);  
                
                if($c['type'] == 'TIMESTAMP')
                {
                  echo ($e && !$es ? $this->Form->text($cl.'.'.$attr, array('class' => 'datepicker'))
                                   : (($co_person_roles[0][$cl][$attr] > 0) ? strftime("%F", strtotime($co_person_roles[0][$cl][$attr])) : ""));
                }
                else
                {
                  echo ($e && !$es ? $this->Form->input($cl.'.'.$attr,
                                                        isset($co_person_roles[0][$cl][$attr]) ? array('default' => $co_person_roles[0][$cl][$attr]) : array())
                                   : Sanitize::html($co_person_roles[0][$cl][$attr]));
                }
              ?>
            </td>
          </tr>
          <?php
              endforeach;
            }
          ?>
          <tr>
            <td>
              <i><font class="required"><?php echo _txt('fd.req'); ?></font></i><br />
            </td>
            <td>
              <?php
                if($e && !$es) {
                  echo $this->Form->submit($submit_label);
                  print $this->Form->button(_txt('op.reset'), 
                                            array('type'=>'reset'));
                }
              ?>
            </td>
          </tr>
        </tbody>
      </table>

  </div>
  <?php if($this->action != "add"): ?>

    <div id="tabs-phone" class="additionalinfo">
      <?php
        if(isset($co_person_roles[0]['TelephoneNumber'])) {
          foreach($co_person_roles[0]['TelephoneNumber'] as $t) {
            $perm = $this->Permission->selfService($permissions, $e, 'TelephoneNumber', $t['type']);
            
            if($perm == PermissionEnum::ReadWrite) {
              print '<div>';
                print '<div>';
                  print $this->Html->link($t['number'],
                                          array('controller' => 'telephone_numbers',
                                                'action' => 'edit',
                                                $t['id']))
                        . " (" . _txt('en.contact', null, $t['type']) . ")<br />\n";
                print '</div>';
                print '<div>';
                  print '<a class="deletebutton" title="' . _txt('op.delete') . '" onclick="javascript:js_confirm_delete(\'' . _jtxt(Sanitize::html($t['number'])) . '\', \'' . $this->Html->url(array('controller' => 'telephone_numbers', 'action' => 'delete', $t['id'], 'co' => $cur_co['Co']['id'])) . '\')";>' . _txt('op.delete') . '</a>' . "\n";
                  print $this->Html->link(_txt('op.edit'),
                                          array('controller' => 'telephone_numbers',
                                                'action' => 'edit',
                                                $t['id']),
                                          array('class' => 'editbutton')) . "\n";
                print '</div>';
              print '</div>';
            } elseif($perm == PermissionEnum::ReadOnly) {
              print Sanitize::html($t['number']) . " (" . _txt('en.contact', null, $t['type']) . ")<br />\n";
            }
          }
        }
        
        if($this->Permission->selfService($permissions, $e, 'TelephoneNumber') == PermissionEnum::ReadWrite) {
          print $this->Html->link(_txt('op.add'),
                                  array('controller' => 'telephone_numbers',
                                        'action' => 'add',
                                        'copersonroleid' => $co_person_roles[0]['CoPersonRole']['id']),
                                  array('class' => 'addbutton'));
        }
      ?>
    </div>

    <div id="tabs-address" class="additionalinfo">
      <?php
        if(isset($co_person_roles[0]['Address'])) {
          foreach($co_person_roles[0]['Address'] as $addr) {
            $perm = $this->Permission->selfService($permissions, $e, 'Address', $addr['type']);
            
            if($perm == PermissionEnum::ReadWrite) {
              print '<div>';
                print '<div>';
                  print $this->Html->link($addr['line1'],
                                          array('controller' => 'addresses',
                                                'action' => 'edit',
                                                $addr['id']))
                        . " (" . _txt('en.contact', null, $addr['type']) . ")<br />\n";
                print '</div>';
                print '<div>';
                  print '<a class="deletebutton" title="' . _txt('op.delete') . '" onclick="javascript:js_confirm_delete(\'' . _jtxt(Sanitize::html($addr['line1'])) . '\', \'' . $this->Html->url(array('controller' => 'addresses', 'action' => 'delete', $addr['id'], 'co' => $cur_co['Co']['id'])) . '\')";>' . _txt('op.delete') . '</a>' . "\n";
                  print $this->Html->link(_txt('op.edit'),
                                          array('controller' => 'addresses',
                                                'action' => 'edit',
                                                $addr['id']),
                                          array('class' => 'editbutton')) . "\n";
                print '</div>';
              print '</div>';
            } elseif($perm == PermissionEnum::ReadOnly) {
              print Sanitize::html($addr['line1']) . " (" . _txt('en.contact', null, $addr['type']) . ")<br />\n";
            }
          }
        }
        
        if($this->Permission->selfService($permissions, $e, 'Address') == PermissionEnum::ReadWrite) {
          print $this->Html->link(_txt('op.add'),
                                  array('controller' => 'addresses',
                                        'action' => 'add',
                                        'copersonroleid' => $co_person_roles[0]['CoPersonRole']['id']),
                                  array('class' => 'addbutton'));
        }
      ?>
    </div>
  <?php endif; // add ?>
</div>
