<!--
/**
 * COmanage Registry CO Petitions Fields
 *
 * Copyright (C) 2012 University Corporation for Advanced Internet Development, Inc.
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
 * @copyright     Copyright (C) 2012 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.5
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */
-->
<script type="text/javascript">
$(document).ready(function() {
  $("input.matchable").keyup(function(event) {
    if(event.which != 13) {
      // 13 is enter/return... don't search on form submit
      // XXX Don't hardcode fields here, or /registry prefix
      $.ajax({
        url: '/registry/co_people/match/co:<?php print $cur_co['Co']['id']; ?>'
             + '/given:' + document.getElementById('EnrolleeCoPersonNameGiven').value
             + '/family:' + document.getElementById('EnrolleeCoPersonNameFamily').value
      }).done(function(data) {
        $('div#results').html(data);
      });
    }
  });
});
</script>
<?php

  // Determine if fields are editable
  $e = false;
  
  if(($this->action == "add" && $permissions['add']) || ($this->action == "edit" && $permissions['edit']))
    $e = true;
    
  // We shouldn't get here if we don't have at least read permission, but check just in case
  
  if(!$e && !$permissions['view'])
    return(false);
    
  if($e)
    print $this->Html->link(
      _txt('op.cancel'),
      array('controller' => 'co_people', 'action' => 'index', 'co' => $cur_co['Co']['id']),
      array('class' => 'cancelbutton')
    );

  echo '
    <br />
    <br />
    ';
    
  if(isset($cur_co))
    print $this->Form->hidden('co_id', array('default' => $cur_co['Co']['id'])) . "\n";
  
  print $this->Form->hidden('co_enrollment_flow_id', array('default' => Sanitize::html($co_enrollment_flow_id)));
  
  $l = 0;
  
  // Unlike most other views, CO Petitions dynamically generates attributes to be
  // completed according to a configuration, not according to a model. This is a
  // lot of logic to embed in a view file, and plausibly this code should be
  // refactored at some point.
?>

<?php if($this->action != 'add'): ?>
<h2 class="ui-state-default"><?php print _txt('ct.co_petitions.1'); ?></h2>

<table id="<?php print $this->action; ?>_co_petition" class="ui-widget">
  <tbody>
    <tr class="line<?php print ($l % 2); $l++; ?>">
      <th><?php print _txt('fd.status'); ?></th>
      <td>
        <?php
          print _txt('en.status', null, $co_petitions[0]['CoPetition']['status']);
          
          if($co_petitions[0]['CoPetition']['status'] == StatusEnum::PendingApproval) {
            if($permissions['approve']) {
              print $this->Html->link(
                _txt('op.approve'),
                array('controller' => 'co_petitions',
                      'action' => 'approve',
                      $co_petitions[0]['CoPetition']['id'],
                      'co' => $co_petitions[0]['CoPetition']['co_id'],
                      'coef' => $co_petitions[0]['CoPetition']['co_enrollment_flow_id']),
                array('class' => 'checkbutton')
              );
            }
            
            if($permissions['deny']) {
              print $this->Html->link(
                _txt('op.deny'),
                array('controller' => 'co_petitions',
                      'action' => 'deny',
                      $co_petitions[0]['CoPetition']['id'],
                      'co' => $co_petitions[0]['CoPetition']['co_id'],
                      'coef' => $co_petitions[0]['CoPetition']['co_enrollment_flow_id']),
                array('class' => 'cancelbutton')
              );
            }
          }
        ?>
      </td>
    </tr>
    <tr class="line<?php print ($l % 2); $l++; ?>">
      <th><?php print _txt('fd.petitioner'); ?></th>
      <td>
        <?php
          if(isset($co_petitions[0]['PetitionerCoPerson']['Name'])) {
            print generateCn($co_petitions[0]['PetitionerCoPerson']['Name']);
          }
        ?>
      </td>
    </tr>
    <tr class="line<?php print ($l % 2); $l++; ?>">
      <th><?php print _txt('fd.sponsor'); ?></th>
      <td>
        <?php
          if(isset($co_petitions[0]['SponsorCoPerson']['Name'])) {
            print generateCn($co_petitions[0]['SponsorCoPerson']['Name']);
          }
        ?>
      </td>
    </tr>
    <tr class="line<?php print ($l % 2); $l++; ?>">
      <th><?php print _txt('fd.approver'); ?></th>
      <td>
        <?php
          if(isset($co_petitions[0]['ApproverCoPerson']['Name'])) {
            print generateCn($co_petitions[0]['ApproverCoPerson']['Name']);
          }
        ?>
      </td>
    </tr>
    <tr class="line<?php print ($l % 2); $l++; ?>">
      <th><?php print _txt('fd.created'); ?></th>
      <td><?php print $this->Time->nice($co_petitions[0]['CoPetition']['created']); ?></td>
    </tr>
    <tr class="line<?php print ($l % 2); $l++; ?>">
      <th><?php print _txt('fd.modified'); ?></th>
      <td><?php print $this->Time->nice($co_petitions[0]['CoPetition']['modified']); ?></td>
    </tr>
  </tbody>
</table>
<?php endif; // action == add ?>

<?php
  // Enumerate over all attributes defined for the enrollment flow associated with this petition.
  // We do a series of <?php tags because we can't mix and match embedded tag styles.
?>

<h2 class="ui-state-default"><?php print _txt('fd.attrs.pet'); ?></h2>

<table id="<?php print $this->action; ?>_co_petition_attrs" class="ui-widget">
  <tbody>
    <?php foreach ($co_enrollment_attributes as $ea): ?>
    <?php $fieldName = $ea['model'] . '.' . $ea['field']; ?>
    <?php if($ea['hidden']): ?>
    <?php print $this->Form->hidden($fieldName, array('default' => $ea['default'])) . "\n"; ?>
    <?php else: ?>
    <tr class="line<?php print ($l % 2); $l++; ?>">
      <td>
        <?php
          // Emit the label for this field
          
          print "<b>" . $ea['label'] . "</b>";
          
          if($ea['required']) {
            print "<font class=\"required\">*</font>\n";
          }
          
          if(isset($ea['description'])
             && $ea['description'] != "") {
            print "</br>\n<font class=\"desc\">" . $ea['description'] . "</font>\n";
          }
        ?>
      </td>
      <td>
        <?php
          // Emit the field itself, according to the type of field
          
          // The type of validation rule can influence what we output.
          $ruleType = 'default';
          
          if(isset($ea['validate']['rule'][0]))
            $ruleType = $ea['validate']['rule'][0];
            
          // XXX need to retrieve current values for edit and view
          
          if($e) {
            switch($ruleType) {
            case 'inList':
              // This is a select
              $attrs = array();
//              $attrs['value'] = (isset($co_person_roles[0]['CoPersonRole']['affiliation'])
//                                 ? $co_person_roles[0]['CoPersonRole']['affiliation']
//                                 : "M");
              $attrs['empty'] = !$ea['required'];
              
              print $this->Form->select($fieldName,
                                        $ea['select'],
                                        $attrs);
              
              if($this->Form->isFieldError($fieldName)) {
                print $this->Form->error($fieldName);
              }
              break;
            case 'validateTimestamp':
              // Handle dates specially to generate the popup calendar
              $c = 'datepicker';
              
              if($ea['field'] == 'valid_from')
                $c = 'datepicker-f';
              elseif($ea['field'] == 'valid_until')
                $c = 'datepicker-c';
              
              print $this->Form->text($fieldName, array('class' => $c));
              
              if($this->Form->isFieldError($fieldName)) {
                print $this->Form->error($fieldName);
              }
              break;
            default:
              // Use default field
              if($permissions['match']
                 && ($fieldName == 'EnrolleeCoPerson.Name.given'
                     || $fieldName == 'EnrolleeCoPerson.Name.family')) {
                # XXX Temp hack to enable real-time query. This should
                # instead be enabled for fields with an appropriate flag.
                print $this->Form->input($fieldName, array('class' => 'matchable'));
              } else {
                print $this->Form->input($fieldName);
              }
              break;
            }
            
            print "\n";
          } else {
            // Just emit the current value for this attribute, if set
            
            if(isset($co_petition_attribute_values[ $ea['id'] ][ $ea['field'] ])) {
              switch($ruleType) {
              case 'inList':
                print $ea['select'][ $co_petition_attribute_values[ $ea['id'] ][ $ea['field'] ]];
                break;
              case 'validateTimestamp':
                print $this->Time->nice($co_petition_attribute_values[ $ea['id'] ][ $ea['field'] ]);
                break;
              default:
                print $co_petition_attribute_values[ $ea['id'] ][ $ea['field'] ];
                break;
              }
            }
          }
        ?>
      </td>
    </tr>
    <?php endif; ?>
    <?php endforeach; ?>
    <tr>
      <td>
        <i><font class="required"><?php echo _txt('fd.req'); ?></font></i><br />
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

<div>
  <div style="float:right;width:35%" id="results">
  </div>
</div>

<?php if($this->action != 'add'): ?>
<h2 class="ui-state-default"><?php print _txt('fd.history.pt'); ?></h2>

<table id="<?php print $this->action; ?>_co_petition_attrs" class="ui-widget">
  <tbody>
    <tr>
      <th class="ui-state-default"><?php print _txt('fd.action'); ?></th>
      <th class="ui-state-default"><?php print _txt('fd.actor'); ?></th>
      <th class="ui-state-default"><?php print _txt('fd.comment'); ?></th>
      <th class="ui-state-default"><?php print _txt('fd.timestamp'); ?></th>
    </tr>
    
    <?php foreach ($co_petitions[0]['CoPetitionHistoryRecord'] as $hr): ?>
    <tr class="line<?php print ($l % 2); $l++; ?>">
      <td><?php print $hr['action']; ?></td>
      <td><?php print generateCn($hr['ActorCoPerson']['Name']); ?></td>
      <td><?php print $hr['comment']; ?></td>
      <td><?php print $this->Time->nice($hr['created']); ?></td>
    </tr>
    <?php endforeach; // CoPetitionHistoryRecord ?>
  </tbody>
</table>
<?php endif; // action == add ?>
