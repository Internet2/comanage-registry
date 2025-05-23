<?php
/**
 * COmanage Registry CO Pipelines Fields
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
 * @since         COmanage Registry v2.0.0
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
  $args['controller'] = 'co_pipelines';
  $args['action'] = 'index';
  $args['co'] = $cur_co['Co']['id'];
  $this->Html->addCrumb(_txt('ct.co_pipelines.pl'), $args);
  $crumbTxt = _txt('op.' . $this->action . '-a', array(_txt('ct.co_pipelines.1')));
  $this->Html->addCrumb($crumbTxt);
  
  print $this->Form->hidden('co_id', array('default' => $cur_co['Co']['id'])) . "\n";
?>
<script type="text/javascript">
  // JS specific to these fields
  
  function fields_update_gadgets() {
    // Hide or show gadgets according to current state
    
    var match = document.getElementById('CoPipelineMatchStrategy').value;
    
    if(match == '<?php print MatchStrategyEnum::EmailAddress; ?>') {
      $("#CoPipelineMatchServerId").closest('li').hide('fade');
      $("#CoPipelineMatchType").closest('li').show('fade');
    } else if(match == '<?php print MatchStrategyEnum::External; ?>') {
      $("#CoPipelineMatchServerId").closest('li').show('fade');
      $("#CoPipelineMatchType").closest('li').hide('fade');
    } else if(match == '<?php print MatchStrategyEnum::Identifier; ?>') {
      $("#CoPipelineMatchServerId").closest('li').hide('fade');
      $("#CoPipelineMatchType").closest('li').show('fade');
    } else {
      $("#CoPipelineMatchServerId").closest('li').hide('fade');
      $("#CoPipelineMatchType").closest('li').hide('fade');
    }
    
    var syncadd = document.getElementById('CoPipelineSyncOnAdd').checked;
    var syncdel = document.getElementById('CoPipelineSyncOnDelete').checked;
    var syncupd = document.getElementById('CoPipelineSyncOnUpdate').checked;
    var createrole = document.getElementById('CoPipelineCreateRole').checked;
    
    if(createrole && (syncadd || syncupd)) {
      $("#CoPipelineSyncCouId").closest('li').show('fade');
      $("#CoPipelineSyncAffiliation").closest('li').show('fade');
      $("#CoPipelineSyncReplaceCouId").closest('li').show('fade');
      $("#CoPipelineSyncIdentifierType").closest('li').show('fade');
    } else {
      $("#CoPipelineSyncCouId").closest('li').hide('fade');
      $("#CoPipelineSyncAffiliation").closest('li').hide('fade');
      $("#CoPipelineSyncReplaceCouId").closest('li').hide('fade');
      $("#CoPipelineSyncIdentifierType").closest('li').hide('fade');
    }
    
    <?php /* XXX #CoPipelineSyncStatusOnDelete is not in the DOM
             ...remove these two lines? */ ?>
    if(createrole && syncdel) {
      $("#CoPipelineSyncStatusOnDelete").closest('li').show('fade');
    } else {
      $("#CoPipelineSyncStatusOnDelete").closest('li').hide('fade');
    }
    
    <?php if(!empty($vv_linkable_enrollment_flows)): ?>
    if(document.getElementById('CoPipelineSyncOnAdd').checked) {
      $("#CoPipelineCoEnrollmentFlowId").closest("li").show('fade');
    } else {
      $("#CoPipelineCoEnrollmentFlowId").closest("li").hide('fade');
    }
    <?php endif; // vv_linkable_enrollment_flows ?>
  }
  
  function update_type_select() {
    var match = document.getElementById('CoPipelineMatchStrategy').value;
    var curmodel = '';
    
    var options = [];
    
    if(match == '<?php print MatchStrategyEnum::EmailAddress; ?>') {
      curmodel = 'EmailAddress';
    } else if(match == '<?php print MatchStrategyEnum::Identifier; ?>') {
      curmodel = 'Identifier';
    }
    
    <?php
      // Convert the available types for each model from a PHP array to a
      // javascript array so that we can update the type popup
      
      foreach(array_keys($vv_types) as $t) {
        // Create an array for this model
        print "options['" . $t . "'] = {";
        
        foreach(array_keys($vv_types[$t]) as $v) {
          // Output LocalizedString and EnumKey (v)
          print '"' . $vv_types[$t][$v] . '": "' . $v . '",' . "\n";
        }
        
        print "};\n";
      }
    ?>
    
    // Clear the current select
    $('#CoPipelineMatchType').empty();
    
    // And replace with the key/value pairs for the current model
    if(curmodel !== '') {
      if(curmodel == 'EmailAddress') {
        // EmailAddress allows an empty value, unlike Identifier
        $('#CoPipelineMatchType').append($("<option></option>")
          .attr("value", "").text(""));
      }
      
      $.each(options[curmodel], function(key, value) {
        $('#CoPipelineMatchType').append($("<option></option>")
          .attr("value", value).text(key));
      });
    }
  }
  
  function js_local_onload() {
    update_type_select();
    fields_update_gadgets();
    
    // Figure out current value and set to selected
    var curvalue = "<?php print (!empty($co_pipelines[0]['CoPipeline']['match_type'])
                                 ? $co_pipelines[0]['CoPipeline']['match_type']
                                 : ""); ?>";
    
    document.getElementById('CoPipelineMatchType').value = curvalue;
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
  });
</script>
<ul id="<?php print $this->action; ?>_co_pipelines" class="fields form-list">
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print ($e ? $this->Form->label('name', _txt('fd.name')) : _txt('fd.name')); ?>
        <span class="required">*</span>
      </div>
    </div>
    <div class="field-info">
      <?php print ($e
                   ? $this->Form->input('name')
                   : filter_var($co_pipelines[0]['CoPipeline']['name'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print ($e ? $this->Form->label('status', _txt('fd.status')) : _txt('fd.status')); ?>
      </div>
    </div>
    <div class="field-info">
      <?php
        global $cm_lang, $cm_texts;
        $attrs['value'] = (isset($co_pipelines[0]['CoPipeline']['status'])
                           ? $co_pipelines[0]['CoPipeline']['status']
                           : SuspendableStatusEnum::Active);
        $attrs['empty'] = false;
        
        if($e) {
          print $this->Form->select('status',
                                    $cm_texts[ $cm_lang ]['en.status.susp'],
                                    $attrs);
          
          if($this->Form->isFieldError('status')) {
            print $this->Form->error('status');
          }
        } else {
          print _txt('en.status.susp', null, $co_pipelines[0]['CoPipeline']['status']);
        }
      ?>
    </div>
  </li>
</ul>

<div id="<?php print $this->action; ?>_co_pipelines_expl" class="explorerContainer">
  <div id="co-pipelines" class="personExplorer">
    <ul>
      <li id="fields-match-strategy" class="fieldGroup">

        <a href="#tabs-match" class="fieldGroupName" title="<?php print _txt('op.expand') ?>" aria-expanded="false" aria-controls="tabs-match">
          <em class="material-icons" aria-hidden="true">expand_more</em>
          <h2><?php print _txt('fd.pi.match.str'); ?></h2>
        </a>
  
        <!-- Match strategy -->
        <ul id="tabs-match" class="fields form-list" style="display: none;">
          <li>
            <div class="field-name">
              <div class="field-title">
                <?php print ($e ? $this->Form->label('CoPipeline.match_strategy', _txt('fd.pi.match.str')) : _txt('fd.pi.match.str')); ?>
                <span class="required">*</span>
              </div>
            </div>  
            <div class="field-info">
              <?php
                global $cm_lang, $cm_texts;
                $attrs = array();
                $attrs['value'] = (isset($co_pipelines[0]['CoPipeline']['match_strategy'])
                                   ? $co_pipelines[0]['CoPipeline']['match_strategy']
                                   : MatchStrategyEnum::NoMatching);
                $attrs['empty'] = false;
                $attrs['onChange'] = 'update_type_select();fields_update_gadgets();';
                
                if($e) {
                  $this->Form->unlockField('CoPipeline.match_strategy');
                  print $this->Form->select('CoPipeline.match_strategy',
                                            $cm_texts[ $cm_lang ]['en.match.strategy'],
                                            $attrs);
                  
                  if($this->Form->isFieldError('match_strategy')) {
                    print $this->Form->error('match_strategy');
                  }
                } else {
                  print _txt('en.match.strategy', null, $co_pipelines[0]['CoPipeline']['match_strategy']);
                }
              ?>
            </div>
          </li>
          <li>
            <div class="field-name">
              <div class="field-title">
                <?php print ($e ? $this->Form->label('CoPipeline.match_type', _txt('fd.pi.match.type')) : _txt('fd.pi.match.type')); ?>
                <span class="required">*</span>
              </div>
              <span class="field-desc"><?php print _txt('fd.pi.match.type.desc'); ?></span>
            </div>
            <div class="field-info">
              <?php
                $attrs = array();
                $attrs['value'] = (isset($co_pipelines[0]['CoPipeline']['match_type'])
                                   ? $co_pipelines[0]['CoPipeline']['match_type']
                                   : '');
                $attrs['empty'] = false;
                
                if($e) {
                  $this->Form->unlockField('CoPipeline.match_type');
                  print $this->Form->select('CoPipeline.match_type',
                                            array(), // The available options will be set by update_type_select()
                                            $attrs);
                  
                  if($this->Form->isFieldError('match_type')) {
                    print $this->Form->error('match_type');
                  }
                } else {
                  print filter_var($co_pipelines[0]['CoPipeline']['match_type'],FILTER_SANITIZE_SPECIAL_CHARS);
                }
              ?>
            </div>
          </li>
          <li>
            <div class="field-name">
              <div class="field-title">
                <?php print ($e ? $this->Form->label('CoPipeline.match_server', _txt('ct.match_servers.1')) : _txt('ct.match_servers.1')); ?>
                <span class="required">*</span>
              </div>
            </div>
            <div class="field-info">
              <?php
                $attrs = array();
                $attrs['value'] = (isset($co_pipelines[0]['CoPipeline']['match_server_id'])
                                   ? $co_pipelines[0]['CoPipeline']['match_server_id']
                                   : '');
                $attrs['empty'] = true;
                
                if($e) {
                  $this->Form->unlockField('CoPipeline.match_server_id');
                  print $this->Form->select('CoPipeline.match_server_id',
                                            $vv_match_servers,
                                            $attrs);
                  
                  if($this->Form->isFieldError('match_server_id')) {
                    print $this->Form->error('match_server_id');
                  }
                } else {
                  print filter_var($vv_match_servers[ $co_pipelines[0]['CoPipeline']['match_server_id'] ],FILTER_SANITIZE_SPECIAL_CHARS);
                }
              ?>
            </div>
          </li>
        </ul><!-- tabs-match -->
      </li><!-- fields-match -->

      <li id="fields-sync" class="fieldGroup">

        <a href="#tabs-sync" class="fieldGroupName" title="<?php print _txt('op.expand') ?>" aria-expanded="false" aria-controls="tabs-sync">
          <em class="material-icons" aria-hidden="true">expand_more</em>
          <h2><?php print _txt('fd.pi.sync.str'); ?></h2>
        </a>

        <!-- Sync strategy -->
        <ul id="tabs-sync" class="fields form-list" style="display: none">
          <li>
            <div class="field-name">
              <div class="field-title">
                <?php print _txt('fd.pi.sync.add'); ?>
              </div>
            </div>
            <div class="field-info checkbox">
              <?php print ($e
                           ? $this->Form->input('sync_on_add', array('onChange' => 'fields_update_gadgets()')) . ' ' .
                             $this->Form->label('sync_on_add', _txt('fd.pi.sync.add'))
                           : (isset($co_pipelines[0]['CoPipeline']['sync_on_add']) ? _txt('fd.yes') : _txt('fd.no'))); ?>
            </div>
            <ul class="field-children">
              <li style="display:none">
                <div class="field-name">
                  <div class="field-title"><?php print _txt('fd.pi.sync.add.ef'); ?></div>
                  <div class="field-desc"><?php print _txt('fd.pi.sync.add.ef.desc'); ?></div>
                </div>
                <div class="field-info">
                  <?php
                    global $cm_lang, $cm_texts;
                    $attrs = array();
                    $attrs['value'] = (!empty($co_pipelines[0]['CoPipeline']['co_enrollment_flow_id'])
                      ? $co_pipelines[0]['CoPipeline']['co_enrollment_flow_id']
                      : null);
                    $attrs['empty'] = true;

                    if($e) {
                      print $this->Form->select('co_enrollment_flow_id',
                        $vv_linkable_enrollment_flows,
                        $attrs);

                      if($this->Form->isFieldError('co_enrollment_flow_id')) {
                        print $this->Form->error('co_enrollment_flow_id');
                      }
                    } elseif(!empty($co_pipelines[0]['CoPipeline']['co_enrollment_flow_id'])) {
                      print filter_var($vv_linkable_enrollment_flows[ $co_pipelines[0]['CoPipeline']['co_enrollment_flow_id'] ],FILTER_SANITIZE_SPECIAL_CHARS);
                    }
                  ?>
                </div>
              </li>
            </ul>
          </li>
          <li>
            <div class="field-name">
              <div class="field-title">
                <?php print _txt('fd.pi.sync.upd'); ?>
              </div>
            </div>
            <div class="field-info checkbox">
              <?php print ($e
                           ? $this->Form->input('sync_on_update', array('onChange' => 'fields_update_gadgets()')) . ' ' .
                             $this->Form->label('sync_on_update', _txt('fd.pi.sync.upd'))
                           : (isset($co_pipelines[0]['CoPipeline']['sync_on_update']) ? _txt('fd.yes') : _txt('fd.no'))); ?>
            </div>
          </li>
          <li>
            <div class="field-name">
              <div class="field-title">
                <?php print _txt('fd.pi.sync.del'); ?>
              </div>
            </div>
            <div class="field-info checkbox">
              <?php print ($e
                           ? $this->Form->input('sync_on_delete', array('onChange' => 'fields_update_gadgets()')) . ' ' .
                             $this->Form->label('sync_on_delete', _txt('fd.pi.sync.del'))
                           : (isset($co_pipelines[0]['CoPipeline']['sync_on_del']) ? _txt('fd.yes') : _txt('fd.no'))); ?>
            </div>
          </li>
          <li>
            <div class="field-name">
                <div class="field-title">
                  <?php print _txt('fd.pi.sync.attributes'); ?>
                </div>
                <span class="field-desc"><?php print _txt('fd.pi.sync.attributes.desc'); ?></span>
            </div>
            <div class="field-info">
              <?php
              global $cm_lang, $cm_texts;
              $attrs['value'] = ($co_pipelines[0]['CoPipeline']['sync_coperson_attributes'] ?? '');
              $attrs['empty'] = true;
              if ($e) {
                $element = $this->Form->select(
                  'sync_coperson_attributes',
                  $cm_texts[$cm_lang]['en.sync.org.attributes'],
                  $attrs
                );

                $formParams = [
                  'options' => $cm_texts[$cm_lang]['en.sync.org.attributes'],
                  'label' => '',
                  ...$attrs
                ];

                // Parse the ID attribute
                $regexId = '/id="(.*?)"/m';
                preg_match_all($regexId, $element, $matchesId, PREG_SET_ORDER, 0);

                // Parse the Name attribute
                $regexName = '/name="(.*?)"/m';
                preg_match_all($regexName, $element, $matchesName, PREG_SET_ORDER, 0);

                // Parse the Name attribute
                $regexClass = '/class="(.*?)"/m';
                preg_match_all($regexClass, $element, $matchesClass, PREG_SET_ORDER, 0);
                print $this->element('multiSelect', [
                  'mountElementId' => $matchesId[0][1],
                  'mountElementName' => $matchesName[0][1],
                  'elementParams' => $formParams,
                  'containerPostfixId' => 'models',
                  'containerClasses' => $matchesClass[0][1] ?? '',
                  'values' => $attrs['value'] ?? '',
                  'placeholder' => _txt('fd.pi.sync.attributes.placeholder'),
                ]);

                if ($this->Form->isFieldError('sync_coperson_attributes')) {
                  print $this->Form->error('sync_coperson_attributes');
                }
              } else {
                print _txt('en.sync.org.attributes', null, $co_pipelines[0]['CoPipeline']['sync_coperson_attributes']);
              }

              ?>
            </div>
          </li>
          <li>
            <div class="field-name">
              <div class="field-title">
                <?php print _txt('fd.pi.sync.coperson.status'); ?>
              </div>
              <span class="field-desc"><?php print _txt('fd.pi.sync.coperson.status.desc'); ?></span>
            </div>
            <div class="field-info">
              <?php
                global $cm_lang, $cm_texts;
                $attrs['value'] = (isset($co_pipelines[0]['CoPipeline']['sync_coperson_status'])
                                   ? $co_pipelines[0]['CoPipeline']['sync_coperson_status']
                                   : '');
                $attrs['empty'] = true;
                if($e) {
                  print $this->Form->select('sync_coperson_status',
                                            $cm_texts[ $cm_lang ]['en.status'],
                                            $attrs);
                  if($this->Form->isFieldError('sync_coperson_status')) {
                    print $this->Form->error('sync_coperson_status');
                  }
                } else {
                  print _txt('en.status', null, $co_pipelines[0]['CoPipeline']['sync_coperson_status']);
                }
              ?>
            </div>
          </li>
          <?php if(!empty($vv_clusters)): ?>
          <li>
            <div class="field-name">
              <div class="field-title">
                <?php print _txt('fd.ef.clusters'); ?>
              </div>
              <span class="field-desc"><?php print _txt('fd.pi.sync.establish_clusters.desc'); ?></span>
            </div>
            <div class="field-info checkbox">
              <?php print ($e
                           ? $this->Form->input('establish_clusters', array('onChange' => 'fields_update_gadgets()')) . ' ' .
                             $this->Form->label('establish_clusters', _txt('fd.ef.clusters'))
                           : (isset($co_pipelines[0]['CoPipeline']['establish_clusters']) ? _txt('fd.yes') : _txt('fd.no'))); ?>
            </div>
          </li>
          <?php endif ?>
          <li>
            <div class="field-name">
              <div class="field-title"><?php print _txt('fd.pi.sync.role'); ?></div>
            </div>
            <div class="field-info checkbox">
              <?php print ($e
                           ? $this->Form->input('create_role', array('onChange' => 'fields_update_gadgets()')) . ' ' .
                             $this->Form->label('create_role', _txt('fd.pi.sync.role'))
                           : (isset($co_pipelines[0]['CoPipeline']['create_role']) ? _txt('fd.yes') : _txt('fd.no'))); ?>
            </div>
          </li>
          <li>
            <div class="field-name">
              <div class="field-title">
                <?php print ($e ? $this->Form->label('CoPipeline.sync_cou_id', _txt('fd.pi.sync.cou')) : _txt('fd.pi.sync.cou')); ?>
              </div>
            </div>
            <div class="field-info">
              <?php
                $attrs = array();
                $attrs['value'] = (isset($co_pipelines[0]['CoPipeline']['sync_cou_id'])
                                   ? $co_pipelines[0]['CoPipeline']['sync_cou_id']
                                   : '');
                $attrs['empty'] = true;

                if($e) {
                  print $this->Form->select('CoPipeline.sync_cou_id',
                                            $vv_cous,
                                            $attrs);

                  if($this->Form->isFieldError('sync_cou_id')) {
                    print $this->Form->error('sync_cou_id');
                  }

                } else {
                  print $vv_cous[ $co_pipelines[0]['CoPipeline']['sync_cou_id'] ];
                }
              ?>
          </div>
        </li>
        <li>
          <div class="field-name">
            <div class="field-title">
              <?php print ($e ? $this->Form->label('CoPipeline.sync_affiliation', _txt('fd.pi.sync.affil')) : _txt('fd.pi.sync.affil')); ?>
            </div>
            <div class="field-desc"><?php print _txt('fd.pi.sync.affil.desc'); ?></div>
          </div>
          <div class="field-info">
            <?php
              $attrs = array();
              $attrs['value'] = (isset($co_pipelines[0]['CoPipeline']['sync_affiliation'])
                                 ? $co_pipelines[0]['CoPipeline']['sync_affiliation']
                                 : '');
              $attrs['empty'] = true;
              
              if($e) {
                print $this->Form->select('CoPipeline.sync_affiliation',
                                          $vv_copr_affiliation_types,
                                          $attrs);
                
                if($this->Form->isFieldError('sync_affiliation')) {
                  print $this->Form->error('sync_affiliation');
                }
              } else {
                print $vv_copr_affiliation_types[ $co_pipelines[0]['CoPipeline']['sync_affiliation'] ];
              }
            ?>
          </div>
        </li>
        <li>
          <div class="field-name">
            <div class="field-title">
              <?php print ($e ? $this->Form->label('CoPipeline.sync_replace_cou_id', _txt('fd.pi.sync.cou.repl')) : _txt('fd.pi.sync.cou.repl')); ?>
            </div>
            <div class="field-desc"><?php print _txt('fd.pi.sync.cou.repl.desc'); ?></div>
          </div>
          <div class="field-info">
            <?php
              $attrs = array();
              $attrs['value'] = (isset($co_pipelines[0]['CoPipeline']['sync_replace_cou_id'])
                                 ? $co_pipelines[0]['CoPipeline']['sync_replace_cou_id']
                                 : '');
              $attrs['empty'] = true;
              
              if($e) {
                print $this->Form->select('CoPipeline.sync_replace_cou_id',
                                          $vv_cous,
                                          $attrs);
                
                if($this->Form->isFieldError('sync_replace_cou_id')) {
                  print $this->Form->error('sync_replace_cou_id');
                }
              } else {
                print $vv_cous[ $co_pipelines[0]['CoPipeline']['sync_replace_cou_id'] ];
              }
            ?>
          </div>
        </li>
        <li>
          <div class="field-name">
            <div class="field-title">
              <?php print ($e ? $this->Form->label('CoPipeline.sync_identifier_type', _txt('fd.pi.sync.type')) : _txt('fd.pi.sync.type')); ?>
            </div>
            <span class="field-desc"><?php print _txt('fd.pi.sync.type.desc'); ?></span>
          </div>
          <div class="field-info">
            <?php
              $attrs = array();
              $attrs['value'] = (isset($co_pipelines[0]['CoPipeline']['sync_identifier_type'])
                                 ? $co_pipelines[0]['CoPipeline']['sync_identifier_type']
                                 : '');
              $attrs['empty'] = true;
              
              if($e) {
                print $this->Form->select('CoPipeline.sync_identifier_type',
                                          $vv_identifier_types,
                                          $attrs);
                
                if($this->Form->isFieldError('sync_identifier_type')) {
                  print $this->Form->error('sync_identifier_type');
                }
              } else {
                print filter_var($co_pipelines[0]['CoPipeline']['sync_identifier_type'],FILTER_SANITIZE_SPECIAL_CHARS);
              }
            ?>
          </div>
        </li>
        <li>
          <div class="field-name">
            <div class="field-title">
              <?php print ($e ? $this->Form->label('CoPipeline.sync_status_on_delete', _txt('fd.pi.sync.del.stat')) : _txt('fd.pi.sync.del.stat')); ?>
            </div>
            <div class="field-desc"><?php print _txt('fd.pi.sync.del.stat.desc'); ?></div>
          </div>
          <div class="field-info">
            <?php
              $attrs = array();
              $attrs['value'] = (isset($co_pipelines[0]['CoPipeline']['sync_status_on_delete'])
                                 ? $co_pipelines[0]['CoPipeline']['sync_status_on_delete']
                                 : StatusEnum::Deleted);
              $attrs['empty'] = false;
              
              if($e) {
                print $this->Form->select('CoPipeline.sync_status_on_delete',
                                          $vv_delete_statuses,
                                          $attrs);
                
                  if($this->Form->isFieldError('sync_status_on_delete')) {
                    print $this->Form->error('sync_status_on_delete');
                  }
                } else {
                  print $vv_delete_statuses[ $co_pipelines[0]['CoPipeline']['sync_status_on_delete'] ];
                }
              ?>
            </div>
          </li>
        </ul><!-- tabs-sync -->
      </li><!-- fields-sync -->

      <?php if($this->action != 'add'): ?>

        <li id="fields-cxn" class="fieldGroup">

          <a href="#tabs-cxn" class="fieldGroupName" title="<?php print _txt('op.expand') ?>" aria-expanded="false" aria-controls="tabs-cxn">
            <em class="material-icons" aria-hidden="true">expand_more</em>
            <h2><?php print _txt('fd.pi.connections'); ?></h2>
          </a>

          <ul id="tabs-cxn" class="fields form-list" style="display: none;">
            <?php if(!empty($co_pipelines[0]['CoSetting'][0]['id'])): ?>
              <li>
                <div class="field-name">
                  <div class="field-title">
                    <?php
                      print $this->Html->link(_txt('fd.pi.default'),
                        array(
                          'controller' => 'co_settings',
                          'action'     => 'add',
                          'co'         => $co_pipelines[0]['CoSetting'][0]['co_id']
                        ));
                    ?>
                  </div>
                </div>
                <div class="field-info">
                  <?php
                    print $this->Html->link(_txt('op.edit'),
                      array(
                        'controller' => 'co_settings',
                        'action'     => 'add',
                        'co'         => $co_pipelines[0]['CoSetting'][0]['co_id']
                      ),
                      array('class' => 'editbutton'));
                  ?>
                </div>
              </li>
            <?php endif; ?>
            <?php foreach($co_pipelines[0]['OrgIdentitySource'] as $ois): ?>
              <li>
                <div class="field-name">
                  <div class="field-title">
                    <?php
                      print $this->Html->link($ois['description'],
                        array(
                          'controller' => 'org_identity_sources',
                          'action'     => 'edit',
                          $ois['id']
                        ));

                      print " (" . _txt('ct.org_identity_sources.1') . ")";
                    ?>
                  </div>
                </div>
                <div class="field-info">
                  <?php
                    print $this->Html->link(_txt('op.edit'),
                      array(
                        'controller' => 'org_identity_sources',
                        'action'     => 'edit',
                        $ois['id']
                      ),
                      array('class' => 'editbutton'));
                  ?>
                </div>
              </li>
            <?php endforeach; // OrgIdentitySource ?>
            <?php if(!empty($co_pipelines[0]['CoEnrollmentFlow']['id'])): ?>
              <li>
                <div class="field-name">
                  <div class="field-title">
                    <?php
                      print $this->Html->link($co_pipelines[0]['CoEnrollmentFlow']['name'],
                        array(
                          'controller' => 'co_enrollment_flows',
                          'action'     => 'edit',
                          $co_pipelines[0]['CoEnrollmentFlow']['id']
                        ));

                      print " (" . _txt('ct.co_enrollment_flows.1') . ")";
                    ?>
                  </div>
                </div>
                <div class="field-info">
                  <?php
                    print $this->Html->link(_txt('op.edit'),
                      array(
                        'controller' => 'co_enrollment_flows',
                        'action'     => 'edit',
                        $co_pipelines[0]['CoEnrollmentFlow']['id']
                      ),
                      array('class' => 'editbutton'));
                  ?>
                </div>
              </li>
            <?php endif; // CoEnrollmentFlow ?>
          </ul><!-- tabs-cxn -->
        </li><!-- fields-cxn -->
      <?php endif; // add ?>

      <?php if($e): ?>
        <li class="fieldGroup">
          <ul class="fields form-list">
            <li class="fields-submit">
              <div class="field-name"><span class="required"><?php print _txt('fd.req'); ?></span></div>
              <div class="field-info">
                <?php print $this->Form->submit($submit_label); ?>
              </div>
            </li>
          </ul>
        </li>
      <?php endif; ?>
    </ul>
  </div>
 
</div>
