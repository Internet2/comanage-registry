<?php
/**
 * COmanage Registry CO Identifier Assignment Fields
 *
 * Portions licensed to the University Corporation for Advanced Internet
 * Development, Inc. ("UCAID") under one or more contributor license agreements.
 * See the NOTICE file distributed with this work for additional information
 * regarding copyright ownership.
 *
 * UCAID licenses this file to you under the Apache License, Version 2.0
 * (the "License"); you may not use this file except in compliance with the
 * License. You may obtain a copy of the License at:
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 * 
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.6
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

  // Determine if fields are editable
  $e = false;
  
  if(($this->action == "add" && $permissions['add']) || ($this->action == "edit" && $permissions['edit']))
    $e = true;
    
  // We shouldn't get here if we don't have at least read permission, but check just in case
  
  if(!$e && !$permissions['view'])
    return(false);

  // Add breadcrumbs
  print $this->element("coCrumb");
  $args = array();
  $args['plugin'] = null;
  $args['controller'] = 'co_identifier_assignments';
  $args['action'] = 'index';
  $args['co'] = $cur_co['Co']['id'];
  $this->Html->addCrumb(_txt('ct.co_identifier_assignments.pl'), $args);
  $crumbTxt = _txt('op.' . $this->action . '-a', array(_txt('ct.co_identifier_assignments.1')));
  $this->Html->addCrumb($crumbTxt);
  
  print $this->Form->hidden('co_id', array('default' => $cur_co['Co']['id'])) . "\n";
?>
<script type="text/javascript">
  <!-- JS specific to these fields -->
  
  function fields_update_gadgets() {
    // Enable or disable gadgets according to current state
    
    var context = document.getElementById('CoIdentifierAssignmentContext').value;
    var idtype = document.getElementById('CoIdentifierAssignmentIdentifierType').value;
    var algorithm = document.getElementById('CoIdentifierAssignmentAlgorithm').value;
    
    // CO People and CO Departments can have Email Addresses
    
    if(context == '<?php print IdentifierAssignmentContextEnum::CoGroup; ?>'
       || idtype != 'mail') {
      $('#CoIdentifierAssignmentEmailType').closest("li").hide('fade');
    } else {
      $('#CoIdentifierAssignmentEmailType').closest("li").show('fade');
    }
    
    // But only CO People can have Login identifiers (but not for email addresses)
    // and Group membership requirements
    
    if(context == '<?php print IdentifierAssignmentContextEnum::CoPerson; ?>'
       && idtype != 'mail') {
      $('#CoIdentifierAssignmentLogin').closest("li").show('fade');
      $('#CoIdentifierAssignmentCoGroupId').closest("li").show('fade');
    } else {
      $('#CoIdentifierAssignmentLogin').closest("li").hide('fade');
      $('#CoIdentifierAssignmentCoGroupId').closest("li").hide('fade');
    }
    
    if(algorithm == '<?php print IdentifierAssignmentEnum::Plugin; ?>') {
      $('#CoIdentifierAssignmentPlugin').closest("li").show('fade');
      $('#CoIdentifierAssignmentMaximum').closest("li").hide('fade');
      $('#CoIdentifierAssignmentMinimum').closest("li").hide('fade');
      $('#CoIdentifierAssignmentMinimumLength').closest("li").hide('fade');
      $('#CoIdentifierAssignmentFormat').closest("li").hide('fade');
      $('#CoIdentifierAssignmentPermitted').closest("li").hide('fade');
    } else if(algorithm == '<?php print IdentifierAssignmentEnum::Random; ?>') {
      $('#CoIdentifierAssignmentPlugin').closest("li").hide('fade');
      $('#CoIdentifierAssignmentMaximum').closest("li").show('fade');
      $('#CoIdentifierAssignmentMinimum').closest("li").show('fade');
      $('#CoIdentifierAssignmentMinimumLength').closest("li").show('fade');
      $('#CoIdentifierAssignmentFormat').closest("li").show('fade');
      $('#CoIdentifierAssignmentPermitted').closest("li").show('fade');
    } else {
      $('#CoIdentifierAssignmentPlugin').closest("li").hide('fade');
      $('#CoIdentifierAssignmentMaximum').closest("li").hide('fade');
      $('#CoIdentifierAssignmentMinimum').closest("li").show('fade');
      $('#CoIdentifierAssignmentMinimumLength').closest("li").show('fade');
      $('#CoIdentifierAssignmentFormat').closest("li").show('fade');
      $('#CoIdentifierAssignmentPermitted').closest("li").show('fade');
    }
  }
  
  function js_local_onload() {
    fields_update_gadgets();
  }
</script>

<ul id="<?php print $this->action; ?>_co_identifier_assignment" class="fields form-list form-list-admin">
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print ($e ? $this->Form->label('description', _txt('fd.desc')) : _txt('fd.desc')); ?>
      </div>
    </div>
    <div class="field-info">
      <?php print ($e
                   ? $this->Form->input('description', array('class' => 'focusFirst'))
                   : filter_var($co_identifier_assignments[0]['CoIdentifierAssignment']['description'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <?php print ($e ? $this->Form->label('status', _txt('fd.status')) : _txt('fd.status')); ?>
    </div>
    <div class="field-info">
      <?php
        global $cm_lang, $cm_texts;
        
        $attrs = array();
        $attrs['value'] = (isset($co_identifier_assignments) ? $co_identifier_assignments[0]['CoIdentifierAssignment']['status'] : SuspendableStatusEnum::Active);
        $attrs['empty'] = false;

        if($e) {
          print $this->Form->select('status',
                                    $cm_texts[ $cm_lang ]['en.status.susp'],
                                    $attrs);

          if($this->Form->isFieldError('status')) {
            print $this->Form->error('status');
          }
        } else {
          print _txt('en.status', null, $co_identifier_assignments[0]['CoIdentifierAssignment']['status']);
        }
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print ($e ? $this->Form->label('context', _txt('fd.ia.context')) :  _txt('fd.ia.context')); ?>
      </div>
      <div class="field-desc"><?php print _txt('fd.ia.context.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php
        global $cm_lang, $cm_texts;
        $attrs = array();
        $attrs['value'] = (isset($co_identifier_assignments)
                            ? $co_identifier_assignments[0]['CoIdentifierAssignment']['context']
                            : IdentifierAssignmentContextEnum::CoPerson);
        $attrs['empty'] = false;
        $attrs['onChange'] = 'fields_update_gadgets()';
        
        if($e) {
          print $this->Form->select('context',
                                    $cm_texts[ $cm_lang ]['en.ia.context'],
                                    $attrs);
          
          if($this->Form->isFieldError('context')) {
            print $this->Form->error('context');
          }
        } else {
          print _txt('en.ia.context', null, $co_identifier_assignments[0]['CoIdentifierAssignment']['context']);
        }
      ?>
    </div>
    <ul class="field-children">
      <li>
        <div class="field-name">
          <div class="field-title">
            <?php print ($e ? $this->Form->label('co_group_id', _txt('fd.ia.group')) : _txt('fd.ia.group')); ?>
          </div>
          <div class="field-desc"><?php print _txt('fd.ia.group.desc'); ?></div>
        </div>
        <div class="field-info">
          <?php
            $attrs = array();
            $attrs['value'] = (!empty($co_identifier_assignments[0]['CoIdentifierAssignment']['co_group_id'])
                               ? $co_identifier_assignments[0]['CoIdentifierAssignment']['co_group_id']
                               : "");
            $attrs['empty'] = true;
            
            if($e) {
              print $this->Form->select('co_group_id',
                                        $vv_available_groups,
                                        $attrs);
              
              if($this->Form->isFieldError('co_group_id')) {
                print $this->Form->error('co_group_id');
              }
            } else {
              print $vv_available_groups[ $co_identifier_assignments[0]['CoIdentifierAssignment']['co_group_id'] ];
            }
          ?>
        </div>
      </li>
    </ul>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print ($e ? $this->Form->label('identifier_type', _txt('fd.type')) :  _txt('fd.type')); ?>
      </div>
    </div>
    <div class="field-info">
      <?php
        $attrs = array();
        $attrs['value'] = (isset($co_identifier_assignments) ? $co_identifier_assignments[0]['CoIdentifierAssignment']['identifier_type'] : "");
        $attrs['empty'] = false;
        $attrs['onChange'] = 'fields_update_gadgets()';
        
        if($e) {
          print $this->Form->select('identifier_type',
                                    $identifier_types,
                                    $attrs);
          
          if($this->Form->isFieldError('identifier_type')) {
            print $this->Form->error('identifier_type');
          }
        } else {
          print $identifier_types[ filter_var($co_identifier_assignments[0]['CoIdentifierAssignment']['identifier_type'],FILTER_SANITIZE_SPECIAL_CHARS) ];
        }
      ?>
    </div>
    <ul class="field-children">
      <li>
        <div class="field-name">
          <div class="field-title">
            <?php print ($e ? $this->Form->label('email_type', _txt('fd.ia.type.email')) : _txt('fd.ia.type.email')); ?>
          </div>
          <div class="field-desc"><?php print _txt('fd.ia.type.email.desc'); ?></div>
        </div>
        <div class="field-info">
          <?php
            $attrs = array();
            $attrs['value'] = (isset($co_identifier_assignments)
                               ? $co_identifier_assignments[0]['CoIdentifierAssignment']['email_type']
                               : "");
            $attrs['empty'] = true;
            
            if($e) {
              print $this->Form->select('email_type',
                                        $email_address_types,
                                        $attrs);
              
              if($this->Form->isFieldError('email_type')) {
                print $this->Form->error('email_type');
              }
            } else {
              print $email_address_types[ filter_var($co_identifier_assignments[0]['CoIdentifierAssignment']['email_type'],FILTER_SANITIZE_SPECIAL_CHARS) ];
            }
          ?>
        </div>
      </li>
      <li>
        <div class="field-name">
          <div class="field-title">
            <?php print _txt('fd.identifier.login'); ?>
          </div>
          <div class="field-desc"><?php print _txt('fd.identifier.login.desc'); ?></div>
        </div>
        <div class="field-info checkbox">
          <?php print ($e
                       ? $this->Form->input('login') . ' ' . $this->Form->label('login', _txt('fd.identifier.login'))
                       : filter_var($co_identifier_assignments[0]['CoIdentifierAssignment']['login'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
        </div>
      </li>
    </ul>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print ($e ? $this->Form->label('fd.order', _txt('fd.order')) :  _txt('fd.order')); ?>
      </div>
    </div>
    <div class="field-info">
      <?php print ($e
                   ? $this->Form->input('ordr')
                   : filter_var($co_identifier_assignments[0]['CoIdentifierAssignment']['ordr'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print ($e ? $this->Form->label('algorithm', _txt('fd.ia.algorithm')) : _txt('fd.ia.algorithm')); ?>
      </div>
      <div class="field-desc"><?php print _txt('fd.ia.algorithm.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php
        global $cm_lang, $cm_texts;
        $attrs = array();
        $attrs['value'] = (isset($co_identifier_assignments)
                            ? $co_identifier_assignments[0]['CoIdentifierAssignment']['algorithm']
                            : IdentifierAssignmentEnum::Sequential);
        $attrs['empty'] = false;
        $attrs['onChange'] = 'fields_update_gadgets()';
        
        if($e) {
          print $this->Form->select('algorithm',
                                    $cm_texts[ $cm_lang ]['en.ia.algorithm'],
                                    $attrs);
          
          if($this->Form->isFieldError('algorithm')) {
            print $this->Form->error('algorithm');
          }
        } else {
          print _txt('en.ia.algorithm', null, $co_identifier_assignments[0]['CoIdentifierAssignment']['algorithm']);
        }
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print ($e ? $this->Form->label('plugin', _txt('fd.plugin')) : _txt('fd.plugin')); ?>
      </div>
    </div>
    <div class="field-info">
      <?php
        if(empty($plugins)) {
          print _txt('er.ia.plugin.none');
        } else {
          $attrs = array();
          $attrs['value'] = (isset($co_identifier_assignments[0]['CoIdentifierAssignment']['plugin'])
                             ? $co_identifier_assignments[0]['CoIdentifierAssignment']['plugin']
                             : "");
          $attrs['empty'] = false;
          
          if($e) {
            print $this->Form->select('plugin',
                                      $plugins,
                                      $attrs);
            
            if($this->Form->isFieldError('plugin')) {
              print $this->Form->error('plugin');
            }
          } else {
            print filter_var($co_identifier_assignments[0]['CoIdentifierAssignment']['plugin'], FILTER_SANITIZE_SPECIAL_CHARS);
          }
        }
      ?>
    </div>
  </li>
  <li>
    <div class="field-name vtop">
      <div class="field-title">
        <?php print ($e ? $this->Form->label('format', _txt('fd.ia.format')) : _txt('fd.ia.format')); ?>
      </div>
      <div class="field-desc"><?php print _txt('fd.ia.format.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php print ($e ? $this->Form->input('format',array('default'=>'(#)')) : filter_var($co_identifier_assignments[0]['CoIdentifierAssignment']['format'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
      <?php if($e): ?>
        <label for="CoIdentifierAssignmentPrefabricatedFormats"><?php print  _txt('fd.ia.format.pattern') . ': '; ?></label>
        <select
          id="CoIdentifierAssignmentPrefabricatedFormats"
          onchange="javascript:document.getElementById('CoIdentifierAssignmentFormat').value=this.value">
          <option value="(#)"><?php print _txt('fd.ia.format.p0'); ?></option>
          <option value="(g).(f)[1:.(#)]@myvo.org"><?php print _txt('fd.ia.format.p1'); ?></option>
          <option value="(g)[1:.(m:1)].(f)[2:.(#)]@myvo.org"><?php print _txt('fd.ia.format.p2'); ?></option>
          <option value="(g:1)(m:1)(f:1)(#)@myvo.org"><?php print _txt('fd.ia.format.p3'); ?></option>
        </select>
      <?php endif; ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print ($e ? $this->Form->label('minimum_length', _txt('fd.ia.minimum_length')) : _txt('fd.ia.minimum_length')); ?>
      </div>
      <div class="field-desc"><?php print _txt('fd.ia.minimum_length.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php print ($e ? $this->Form->input('minimum_length') : filter_var($co_identifier_assignments[0]['CoIdentifierAssignment']['minimum_length'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print ($e ? $this->Form->label('permitted', _txt('fd.ia.permitted')) : _txt('fd.ia.permitted')); ?>
      </div>
      <div class="field-desc"><?php print _txt('fd.ia.permitted.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php
        global $cm_lang, $cm_texts;
        $attrs = array();
        $attrs['value'] = (isset($co_identifier_assignments)
                            ? $co_identifier_assignments[0]['CoIdentifierAssignment']['permitted']
                            : PermittedCharacterEnum::AlphaNumeric);
        $attrs['empty'] = false;
        
        if($e) {
          print $this->Form->select('permitted',
                                    $cm_texts[ $cm_lang ]['en.chars.permitted'],
                                    $attrs);
          
          if($this->Form->isFieldError('permitted')) {
            print $this->Form->error('permitted');
          }
        } else {
          print _txt('en.chars.permitted', null, $co_identifier_assignments[0]['CoIdentifierAssignment']['permitted']);
        }
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print ($e ? $this->Form->label('minimum', _txt('fd.ia.minimum')) : _txt('fd.ia.minimum')); ?>
      </div>
      <div class="field-desc"><?php print _txt('fd.ia.minimum.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php print ($e ? $this->Form->input('minimum') : filter_var($co_identifier_assignments[0]['CoIdentifierAssignment']['minimum'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print ($e ? $this->Form->label('maximum', _txt('fd.ia.maximum')) : _txt('fd.ia.maximum')); ?>
      </div>
      <div class="field-desc"><?php print _txt('fd.ia.maximum.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php print ($e ? $this->Form->input('maximum') : filter_var($co_identifier_assignments[0]['CoIdentifierAssignment']['maximum'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
<!-- CO-347
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('fd.ia.exclusions'); ?>
      </div>
      <div class="field-desc"><?php print _txt('fd.ia.exclusions.desc'); ?></div>
    </div>
  </li> -->

  <?php if($e): ?>
    <li class="fields-submit">
      <div class="field-name"></div>
      <div class="field-info">
        <?php print $this->Form->submit($submit_label); //, array('onClick' => 'on_submit()')); ?>
      </div>
    </li>
  <?php endif; ?>
</ul>
