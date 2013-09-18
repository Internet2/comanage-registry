<?php
/**
 * COmanage Registry Petition Fields (used to display both petitions and petition-based invitations)
 *
 * Copyright (C) 2012-13 University Corporation for Advanced Internet Development, Inc.
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
 * @copyright     Copyright (C) 2012-13 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.7
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */

  // Enumerate over all attributes defined for the enrollment flow associated with this petition.
  // We do a series of <?php tags because we can't mix and match embedded tag styles.
  
  $l = 1;
?>
  
<style>
  .modelbox{
    border: solid 1px #8dbbdd;
    margin: 5px;
    padding: 9px 38px;
    float: left;
    border-radius: 10px;
    width: 90%;
  }

  .boxtitle{
    padding: 0 0 5px 0;
  }

  .modelbox table tr>:first-child{
    min-width: 200px;
    text-align: right;
  }

  .modelbox table tr>:nth-child(2){
    min-width: 150px;
  }

  .modelbox table tr>:nth-child(2) input {
    min-width: 380px;
  } 

  .modelbox table tr>:nth-child(2) select{
    min-width: 387px;
  } 
</style>

<div>
  <div id="results">
  </div>
</div>

<div style = "overflow:hidden;">
  <?php 
    // Sort attributes by model for printing
    $coea_model = array();
    $misc_attr = array();

    foreach ($co_enrollment_attributes as $ea){
      if ($ea['hidden']) {  // Sort hidden ones out
        $hidden_attr[] = $ea;
      } else {
        if( isset($ea['group']) ) {  // Sort by group
          $coea_model[ $ea['group'] ][] = $ea;
        } else {
          // Has no group
          $coea_model[$ea['label']][] = $ea;
        }
      } 
    }

    // Print the hidden fields
    foreach ($hidden_attr as $ea) {
      if($ea['hidden'])
        $fieldName = $ea['model'] . '.' . $ea['field'];

      print $this->Form->hidden($fieldName, array('default' => $ea['default'])) . "\n"; 
    }

    // Print the ones not hidden
    foreach ($coea_model as $m => $coe_attributes): 
  ?>
    <div class="ui-widget modelbox">
      <div class = "boxtitle">
        <b><?php print $m ?></b>
        <?php
          // Only print the description once if it's the same for all items
          $groupDesc = false;  // Not grouping by default
          $descriptions = array();

          foreach($coe_attributes as $ea) {
            $descriptions[] = $ea['description'];
          }
          $descTypeCount = count(array_count_values($descriptions));

          if($descTypeCount == 1 && !empty($coe_attributes[0]['description'])) {
            print "</br>\n<font class=\"desc\">" . $ea['description'] . "</font>\n";
            $groupDesc = true; // Set flag for later
          }
        ?>

      </div>
      <table id="<?php print $this->action; ?>_co_petition_attrs_<?php print $m?>" class="ui-widget">
        <tbody>
          <?php 
            $l = 0; // Reset zebra stripes for each box
            foreach ($coe_attributes as $ea): 
              if(!$ea['hidden'] && isset($ea['default']) && !$ea['modifiable']) {
                // Since disabled values don't submit with the form, convert them to hidden
                // fields and change the fieldName for the visible attribute
                
                $hfieldName = $ea['model'] . '.' . $ea['field'];
                $fieldName = $hfieldName . "-disabled";
                
                print $this->Form->hidden($hfieldName, array('default' => $ea['default'])) . "\n";
              } else {
                $fieldName = $ea['model'] . '.' . $ea['field'];
              }
          ?>

            <tr class="line<?php print ($l % 2); $l++; ?>">
              <td>
                <?php
                  // Emit the label for this field 
                  print "<b>" . $ea['label'] . "</b>";
                  
                  if($ea['required']) {
                    print "<font class=\"required\">*</font>\n";
                  }
                  
                  if(isset($ea['description'])
                    && !$groupDesc
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
                      $args = array();
                      // Set a default value if provided
                      if(isset($ea['default'])) {
                        $args['value'] = $ea['default'];
                        $args['disabled'] = !$ea['modifiable'];
                      }
                      $args['empty'] = !$ea['required'];
                      
                      print $this->Form->select($fieldName,
                                                $ea['select'],
                                                $args);
                      
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
                      
                      $args = array();
                      $args['class'] = $c;
                      
                      if(isset($ea['default'])) {
                        $args['default'] = $ea['default'];
                        $args['disabled'] = !$ea['modifiable'];
                      }
                      
                      print $this->Form->text($fieldName, $args);
                      
                      if($this->Form->isFieldError($fieldName)) {
                        print $this->Form->error($fieldName);
                      }
                      break;
                    default:
                      // Use default field
                      
                      $args = array();
                      $args['required'] = $ea['required'];
                      
                      // Use a provided default value, if one specified
                      if(isset($ea['default'])) {
                        $args['default'] = $ea['default'];
                        $args['disabled'] = !$ea['modifiable'];
                      }
                      
                      if($permissions['match']
                         && ($fieldName == 'EnrolleeCoPerson.Name.given'
                             || $fieldName == 'EnrolleeCoPerson.Name.family')) {
                        # XXX Temp hack to enable real-time query. This should
                        # instead be enabled for fields with an appropriate flag.
                        $args['class'] = 'matchable';
                      }
                      
                      print $this->Form->input($fieldName, $args);
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
          <?php 
            endforeach; 
          ?>
        </tbody>
      </table>
    </div>
  <?php 
    endforeach; // foreach ($coea_model as $m => $coe_attributes): 
  ?>
  <table id="<?php print $this->action; ?>_co_petition_attrs" class="ui-widget">
    <tbody>
      <tr>
        <td>
          <i><font class="required"><?php echo _txt('fd.req'); ?></font></i><br />
        </td>
        <td>
          <?php
            if($e) {
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
</div>
<?php if($this->action != 'add'): ?>
  <div id="tabs-history">
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
          <td><?php print (!empty($hr['ActorCoPerson']['Name']) ? generateCn($hr['ActorCoPerson']['Name']) : _txt('fd.actor.self')); ?></td>
          <td><?php print $hr['comment']; ?></td>
          <td><?php print $this->Time->nice($hr['created']); ?></td>
        </tr>
        <?php endforeach; // CoPetitionHistoryRecord ?>
      </tbody>
    </table>
  </div>
<?php endif; // action == add ?>
