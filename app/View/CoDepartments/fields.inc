<?php
/**
 * COmanage Registry CO Department Fields
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
 * @since         COmanage Registry v3.1.0
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
  $args['controller'] = 'co_departments';
  $args['action'] = 'index';
  if(isset($cur_co)) {
    $args['co'] = $cur_co['Co']['id'];
  }
  $this->Html->addCrumb(_txt('ct.co_departments.pl'), $args);
  $crumbTxt = _txt('op.' . $this->action . '-a', array(_txt('ct.co_departments.1')));
  $this->Html->addCrumb($crumbTxt);
    
  if(isset($cur_co))
    print $this->Form->hidden('co_id', array('default' => $cur_co['Co']['id'])) . "\n";
?>

<script type="text/javascript">
  <!-- /* JS specific to these fields */ -->
  
  function js_confirm_autogenerate() {
    // Open the dialog to confirm autogeneration of identifiers
    var $tabs = $( "#tabs" ).tabs();
    $('#autogenerate-dialog').dialog('open');
  }

  $(function() {
    // Explorer menu toggles
    $(".fieldGroupName").click(function(event) {
      event.preventDefault();
      $(this).next(".fields").slideToggle("fast");
      // toggle the +/- icon:
      if ($(this).find(".material-icons").text() == "expand_less") {
        $(this).find(".material-icons").text("expand_more");
        $(this).attr('title','<?php print _txt('op.expand'); ?>');
        $(this).attr('aria-expanded','false');
      } else {
        $(this).find(".material-icons").text("expand_less");
        $(this).attr('title','<?php print _txt('op.collapse'); ?>');
        $(this).attr('aria-expanded','true');
      }
    });
    // Autogenerate dialog
    $("#autogenerate-dialog").dialog({
      autoOpen: false,
      <?php if($this->action == 'edit'): ?>
      buttons: [
        {
          text : "<?php print _txt('op.cancel'); ?>",
          click : function() {
            $(this).dialog("close");
          }
        },
        {
          text : "<?php print _txt('op.id.auto'); ?>",
          click: function () {
            $(this).dialog("close");
            displaySpinner();
            window.location.href = "<?php print $this->Html->url(array('controller' => 'identifiers',
            'action' => 'assign',
            'codeptid' => $co_departments[0]['CoDepartment']['id'])); ?>";
          }
        }
      ],
      modal: true,
      show: {
        effect: "fade"
      },
      hide: {
        effect: "fade"
      }
      <?php endif; // edit ?>
    });
  });
</script>

<div id="<?php print $this->action; ?>_department" class="explorerContainer">
  <div id="coOrgIdExplorer" class="personExplorer">
    <ul>
      <!-- Department Info -->
      <li id="fields-name" class="fieldGroup">
        <a href="#tabs-name" class="fieldGroupName" title="<?php print _txt('op.collapse') ?>" aria-expanded="true" aria-controls="names-container">
          <em class="material-icons" aria-hidden="true">expand_less</em>
          <h2><?php print _txt('ct.co_departments.1'); ?></h2>
        </a>

        <div id="names-container" class="fields">
          <ul id="tabs-name-fields" class="fields form-list">
        <div id="names-container" class="fields">
          <li>
            <div class="field-name">
              <?php print $this->Form->label('name',_txt('fd.name')); ?><span class="required">*</span>
            </div>
            <div class="field-info">
              <?php print ($e ? $this->Form->input('name', array('class' => 'focusFirst')) : filter_var($co_departments[0]['CoDepartment']['name'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
            </div>
          </li>
          <li>
            <div class="field-name">
              <?php print $this->Form->label('description',_txt('fd.desc')); ?>
            </div>
            <div class="field-info">
              <?php print ($e ? $this->Form->input('description') : filter_var($co_departments[0]['CoDepartment']['description'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
            </div>
          </li>
          <?php if(!empty($vv_cous)): ?>
          <li>
            <div class="field-name">
              <div class="field-title">
                <?php print ($e ? $this->Form->label('cou_id', _txt('fd.cou')) : _txt('fd.cou')); ?>
              </div>
            </div>
            <div class="field-info">
              <?php
                $attrs = array();
                $attrs['value'] = (isset($co_departments[0]['CoDepartment']['cou_id'])
                                   ? $co_departments[0]['CoDepartment']['cou_id']
                                   : '');
                $attrs['empty'] = true;
        
                if($e) {
                  print $this->Form->select('cou_id',
                                            $vv_cous,
                                            $attrs);
        
                  if($this->Form->isFieldError('cou_id')) {
                    print $this->Form->error('cou_id');
                  }
                } else {
                  print $vv_cous[ $co_departments[0]['CoDepartment']['cou_id'] ];
                }
              ?>
            </div>
          </li>
          <?php endif; // vv_cous ?>
          <li>
            <span class="field-name">
              <?php
              if($e) {
                print $this->Form->label('type', _txt('fd.type'));
              } else {
                print _txt('fd.type');
              }
              ?>
            </span>
            <span class="field-info">
              <?php
              $attrs = array();
              $attrs['value'] = (isset($co_departments) ? $co_departments[0]['CoDepartment']['type'] : "");
              $attrs['empty'] = true;

              if($e) {
                print $this->Form->select('type',
                  $vv_available_types,
                  $attrs);

                if($this->Form->isFieldError('type')) {
                  print $this->Form->error('type');
                }
              } else {
                // $vv_available_types will only show the permitted types, and en.url.type will
                // not be aware of any extended types, so we just show the raw type key.
                print filter_var($co_departments[0]['CoDepartment']['type'],FILTER_SANITIZE_SPECIAL_CHARS);
              }
              ?>
            </span>
          </li>
          <li class="field-stack">
            <div class="field-name">
              <div class="field-title">
                <?php print ($e ? $this->Form->label('introduction', _txt('fd.dp.intro')) : _txt('fd.dp.intro')); ?>
              </div>
              <div class="field-desc"><?php print _txt('fd.dp.intro.desc'); ?></div>
            </div>
            <div class="field-info">
              <?php print ($e
                           ? $this->Form->input('introduction')
                           : filter_var($co_departments[0]['CoDepartment']['introduction'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
            </div>
          </li>
          <li>
            <div class="field-name">
              <div class="field-title">
                <?php print ($e ? $this->Form->label('leadership_co_group_id', _txt('fd.dp.group.leader')) : _txt('fd.dp.group.leader')); ?>
              </div>
              <div class="field-desc"><?php print _txt('fd.dp.group.leader.desc'); ?></div>
            </div>
            <div class="field-info">
              <?php
                $attrs = array();
                $attrs['value'] = (isset($co_departments[0]['CoDepartment']['leadership_co_group_id'])
                                   ? $co_departments[0]['CoDepartment']['leadership_co_group_id']
                                   : 0);
                $attrs['empty'] = true;
        
                if($e) {
                  print $this->Form->select('leadership_co_group_id',
                                            $vv_co_groups,
                                            $attrs);
        
                  if($this->Form->isFieldError('leadership_co_group_id')) {
                    print $this->Form->error('leadership_co_group_id');
                  }
                } else {
                  if(!empty( $co_departments[0]['CoDepartment']['leadership_co_group_id'])) {
                    print filter_var($vv_co_groups[ $co_departments[0]['CoDepartment']['leadership_co_group_id'] ],FILTER_SANITIZE_SPECIAL_CHARS);
                  }
                }
              ?>
            </div>
          </li>
          <li>
            <div class="field-name">
              <div class="field-title">
                <?php print ($e ? $this->Form->label('administrative_co_group_id', _txt('fd.dp.group.admin')) : _txt('fd.dp.group.admin')); ?>
              </div>
              <div class="field-desc"><?php print _txt('fd.dp.group.admin.desc'); ?></div>
            </div>
            <div class="field-info">
              <?php
                $attrs = array();
                $attrs['value'] = (isset($co_departments[0]['CoDepartment']['administrative_co_group_id'])
                                   ? $co_departments[0]['CoDepartment']['administrative_co_group_id']
                                   : 0);
                $attrs['empty'] = true;
        
                if($e) {
                  print $this->Form->select('administrative_co_group_id',
                                            $vv_co_groups,
                                            $attrs);
        
                  if($this->Form->isFieldError('administrative_co_group_id')) {
                    print $this->Form->error('administrative_co_group_id');
                  }
                } else {
                  if(!empty( $co_departments[0]['CoDepartment']['administrative_co_group_id'])) {
                    print filter_var($vv_co_groups[ $co_departments[0]['CoDepartment']['administrative_co_group_id'] ],FILTER_SANITIZE_SPECIAL_CHARS);
                  }
                }
              ?>
            </div>
          </li>
          <li>
            <div class="field-name">
              <div class="field-title">
                <?php print ($e ? $this->Form->label('support_co_group_id', _txt('fd.dp.group.support')) : _txt('fd.dp.group.support')); ?>
              </div>
              <div class="field-desc"><?php print _txt('fd.dp.group.support.desc'); ?></div>
            </div>
            <div class="field-info">
              <?php
                $attrs = array();
                $attrs['value'] = (isset($co_departments[0]['CoDepartment']['support_co_group_id'])
                                   ? $co_departments[0]['CoDepartment']['support_co_group_id']
                                   : 0);
                $attrs['empty'] = true;
        
                if($e) {
                  print $this->Form->select('support_co_group_id',
                                            $vv_co_groups,
                                            $attrs);
        
                  if($this->Form->isFieldError('support_co_group_id')) {
                    print $this->Form->error('support_co_group_id');
                  }
                } else {
                  if(!empty( $co_departments[0]['CoDepartment']['support_co_group_id'])) {
                    print filter_var($vv_co_groups[ $co_departments[0]['CoDepartment']['support_co_group_id'] ],FILTER_SANITIZE_SPECIAL_CHARS);
                  }
                }
              ?>
            </div>
          </li>
          <?php if($e): ?>
            <li class="fields-submit">
              <div class="field-name">
                <span class="required"><?php print _txt('fd.req'); ?></span>
              </div>
              <div class="field-info">
                <?php print $this->Form->submit($submit_label); ?>
              </div>
            </li>
          <?php endif; ?>
        </ul><!-- tabs-name--fields -->
        </div>
      </li><!-- fields-name -->

      <?php
        if($this->action != "add") {
          $args = array(
            'edit'         => $e,
            'self_service' => false,
            'model'        => 'CoDepartment',
            'model_param'  => 'codeptid',
            'mvpa_model'   => 'Identifier',
            'mvpa_field'   => 'identifier'
          );
          
          print $this->element('mvpa', $args);
          
          $args['mvpa_model'] = 'EmailAddress';
          $args['mvpa_field'] = 'mail';
          
          print $this->element('mvpa', $args);
          
          $args['mvpa_model'] = 'Url';
          $args['mvpa_field'] = 'url';
          
          print $this->element('mvpa', $args);
          
          $args['mvpa_model'] = 'TelephoneNumber';
          $args['mvpa_field'] = null;
          $args['mvpa_format'] = 'formatTelephone';
          
          print $this->element('mvpa', $args);
          
          $args['mvpa_model'] = 'Address';
          $args['mvpa_format'] = 'formatAddress';
          
          print $this->element('mvpa', $args);
          
          $args['mvpa_model'] = 'AdHocAttribute';
          $args['self_service'] = false;
          $args['mvpa_format'] = 'formatAdHoc';
          
          print $this->element('mvpa', $args);
        }
      ?>
    </ul>
  </div> <!-- personExplorer -->
  <?php print $this->element("changelog"); ?>
</div> <!-- explorerContainer -->
<div id="autogenerate-dialog" class="co-dialog" title="<?php print _txt('op.id.auto'); ?>">
  <?php print _txt('op.id.auto.confirm'); ?>
</div>
