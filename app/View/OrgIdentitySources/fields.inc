<?php
/**
 * COmanage Registry Organizational Identity Sources Fields
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
    return false;

  // Add breadcrumbs
  print $this->element("coCrumb");
  $args = array();
  $args['plugin'] = null;
  $args['controller'] = 'org_identity_sources';
  $args['action'] = 'index';
  $args['co'] = $cur_co['Co']['id'];
  $this->Html->addCrumb(_txt('ct.org_identity_sources.pl'), $args);
  $crumbTxt = _txt('op.' . $this->action . '-a', array(_txt('ct.org_identity_sources.1')));
  $this->Html->addCrumb($crumbTxt);
  
  if($e) {
    // Populate CO ID if appropriate
    
    if(!$pool_org_identities) {
      print $this->Form->hidden('OrgIdentitySource.co_id',
                               array('default' => $cur_co['Co']['id'])). "\n";
    }
  }
  
  $l = 1;
?>
  
<script type="text/javascript">
  // JS specific to these fields

  function fields_update_gadgets() {
    // Hide or show gadgets according to current state
    
    var eppntype = document.getElementById('OrgIdentitySourceEppnIdentifierType').value;
    
    if(eppntype) {
      $("#OrgIdentitySourceEppnSuffix").closest('tr').show('fade');
    } else {
      $("#OrgIdentitySourceEppnSuffix").closest('tr').hide('fade');
    }
    
    // Set query options visibility
    var syncmode = document.getElementById('OrgIdentitySourceSyncMode').value;
    
    if(syncmode == '<?php print SyncModeEnum::Query; ?>') {
      $("#OrgIdentitySourceSyncQuerySkipKnown").closest('tr').show('fade');
      $("#OrgIdentitySourceSyncQueryMismatchMode").closest('tr').show('fade');
    } else {
      $("#OrgIdentitySourceSyncQuerySkipKnown").closest('tr').hide('fade');
      $("#OrgIdentitySourceSyncQueryMismatchMode").closest('tr').hide('fade');
    }
  }
  
  function js_local_onload() {
    fields_update_gadgets();
  }
</script>
  
<?php if(empty($plugins)): ?>
  <div class="co-info-topbox">
    <em class="material-icons">info</em>
    <?php print _txt('er.plugin.none', array(_txt('ct.org_identity_sources.pl'))); ?>
  </div>
<?php else: /* plugins */ ?>
  <ul id="<?php print $this->action; ?>_org_identity_source" class="fields form-list form-list-admin">
    <li>
      <div class="field-name">
        <div class="field-title">
          <?php print _txt('fd.desc'); ?>
          <span class="required">*</span>
        </div>
      </div>
      <div class="field-info">
        <?php print ($e ? $this->Form->input('description') : filter_var($org_identity_sources[0]['OrgIdentitySource']['description'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
      </div>
    </li>
    <li>
      <div class="field-name">
        <div class="field-title">
          <?php print $this->Form->label('sor_label',_txt('fd.sor_label')); ?>
        </div>
      </div>
      <div class="field-info">
        <?php print ($e ? $this->Form->input('sor_label') : filter_var($match_servers[0]['MatchServer']['sor_label'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
      </div>
    </li>
    <li>
      <div class="field-name">
        <div class="field-title">
          <?php print _txt('fd.plugin'); ?>
          <span class="required">*</span>
        </div>
        <div class="field-desc"><?php print _txt('fd.plugin.warn', array(_txt('ct.org_identity_sources.1'))); ?></div>
      </div>
      <div class="field-info">
        <?php
          switch($this->action) {
            case 'add':
              print $this->Form->input('plugin');
              break;
            case 'edit':
              print $this->Form->input('plugin', array('disabled' => true));
              // We need to re-include the value as hidden since it can't be changed (but is required by the model)
              print $this->Form->hidden('plugin',
                                        array('default' => filter_var($org_identity_sources[0]['OrgIdentitySource']['plugin'],FILTER_SANITIZE_SPECIAL_CHARS))) . "\n";
              break;
            default:
              print filter_var($org_identity_sources[0]['OrgIdentitySource']['plugin'],FILTER_SANITIZE_SPECIAL_CHARS);
              break;
          }

          if($this->action == 'edit') {
            // And render a link to the plugin target
            
            $plmodel = filter_var($org_identity_sources[0]['OrgIdentitySource']['plugin'],FILTER_SANITIZE_SPECIAL_CHARS);
            $pl = Inflector::underscore($plmodel);
            $plm = Inflector::tableize($plmodel);

            print $this->Html->link(_txt('op.config'),
                                    array(
                                      'plugin' => $pl,
                                      'controller' => $plm,
                                      'action' => 'edit',
                                      $org_identity_sources[0][$plmodel]['id']
                                    ),
                                    array('class' => 'configurebutton'));

            if(!empty($vv_plugin_group_attrs[$plmodel])) {
              print $this->Html->link(_txt('op.ois.conf.gr'),
                                      array(
                                        'controller' => 'co_group_ois_mappings',
                                        'action' => 'index',
                                        'org_identity_source' => $org_identity_sources[0]['OrgIdentitySource']['id']
                                      ),
                                      array('class' => 'configurebutton'));
            }
          }
        ?>
      </div>
    </li>
    <li>
      <div class="field-name">
        <div class="field-title">
          <?php print _txt('fd.status'); ?>
          <span class="required">*</span>
        </div>
      </div>
      <div class="field-info">
        <?php
          global $cm_lang, $cm_texts;
          $attrs = array();
          $attrs['value'] = (isset($org_identity_sources[0]['OrgIdentitySource']['status'])
                             ? $org_identity_sources[0]['OrgIdentitySource']['status']
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
            print _txt('en.status', null, $org_identity_sources[0]['OrgIdentitySource']['status']);
          }
        ?>
      </div>
    </li>
    <li>
      <div class="field-name">
        <div class="field-title">
          <?php print _txt('fd.ois.sync.mode'); ?>
          <span class="required">*</span>
        </div>
      </div>
      <div class="field-info">
        <?php
          global $cm_lang, $cm_texts;
          $attrs = array();
          $attrs['value'] = (isset($org_identity_sources[0]['OrgIdentitySource']['sync_mode'])
                             ? $org_identity_sources[0]['OrgIdentitySource']['sync_mode']
                             : SyncModeEnum::Manual);
          $attrs['empty'] = false;
          $attrs['onChange'] = 'fields_update_gadgets();';
          
          if($e) {
            print $this->Form->select('sync_mode',
                                      $cm_texts[ $cm_lang ]['en.sync.mode'],
                                      $attrs);

            if($this->Form->isFieldError('sync_mode')) {
              print $this->Form->error('sync_mode');
            }
          } else {
            print _txt('en.sync.mode', null, $org_identity_sources[0]['OrgIdentitySource']['sync_mode']);
          }
        ?>
      </div>
    </li>
    <li>
      <div class="field-name">
        <div class="field-title">
          <?php print _txt('fd.ois.sync.skipknown'); ?>
        </div>
        <div class="field-desc"><?php print _txt('fd.ois.sync.skipknown.desc'); ?></div>
      </div>
      <div class="field-info">
        <?php print $this->Form->input('sync_query_skip_known'); ?>
      </div>
    </li>
    <li>
      <div class="field-name">
        <div class="field-title">
          <?php print _txt('fd.ois.sync.mismatch'); ?>
        </div>
        <div class="field-desc"><?php print _txt('fd.ois.sync.mismatch.desc'); ?></div>
      </div>
      <div class="field-info">
        <?php
          global $cm_lang, $cm_texts;
          $attrs = array();
          $attrs['value'] = (isset($org_identity_sources[0]['OrgIdentitySource']['sync_query_mismatch_mode'])
            ? $org_identity_sources[0]['OrgIdentitySource']['sync_query_mismatch_mode']
            : OrgIdentityMismatchEnum::CreateNew);
          $attrs['empty'] = false;

          if($e) {
            print $this->Form->select('sync_query_mismatch_mode',
              $cm_texts[ $cm_lang ]['en.sync.query.mismatch.mode'],
              $attrs);

            if($this->Form->isFieldError('sync_query_mismatch_mode')) {
              print $this->Form->error('sync_query_mismatch_mode');
            }
          } else {
            print _txt('en.sync.query.mismatch.mode', null, $org_identity_sources[0]['OrgIdentitySource']['sync_query_mismatch_mode']);
          }
        ?>
      </div>
    </li>
    <li>
      <div class="field-name">
        <div class="field-title">
          <?php print _txt('fd.ois.sync.loginquery'); ?>
        </div>
        <div class="field-desc"><?php print _txt('fd.ois.sync.loginquery.desc'); ?></div>
      </div>
      <div class="field-info">
        <?php print $this->Form->input('sync_on_user_login'); ?>
      </div>
    </li>
    <?php if(!empty($vv_co_pipelines)): ?>
    <li>
      <div class="field-name">
        <div class="field-title">
          <?php print _txt('fd.pipeline'); ?>
        </div>
        <div class="field-desc"><?php print _txt('fd.pipeline.desc.ois'); ?></div>
      </div>
      <div class="field-info">
        <?php
          $attrs = array();
          $attrs['value'] = (isset($org_identity_sources[0]['OrgIdentitySource']['co_pipeline_id'])
                             ? $org_identity_sources[0]['OrgIdentitySource']['co_pipeline_id']
                             : '');
          $attrs['empty'] = true;

          if($e) {
            print $this->Form->select('co_pipeline_id',
                                      $vv_co_pipelines,
                                      $attrs);

            if($this->Form->isFieldError('co_pipeline_id')) {
              print $this->Form->error('co_pipeline_id');
            }
          } else {
            if(!empty($org_identity_sources[0]['OrgIdentitySource']['co_pipeline_id'])) {
              print $vv_co_pipelines[ $org_identity_sources[0]['OrgIdentitySource']['co_pipeline_id'] ];
            }
          }
        ?>
      </div>
    </li>
    <?php endif; // vv_co_pipelines ?>

    <li>
      <div class="field-name">
        <div class="field-title">
          <?php print _txt('fd.ois.eppn.type'); ?>
        </div>
        <div class="field-desc"><?php print _txt('fd.ois.eppn.type.desc'); ?></div>
      </div>
      <div class="field-info">
        <?php
          $attrs = array();
          $attrs['value'] = (isset($org_identity_sources[0]['OrgIdentitySource']['eppn_identifier_type'])
                             ? $org_identity_sources[0]['OrgIdentitySource']['eppn_identifier_type']
                             : null);
          $attrs['empty'] = true;
          $attrs['onChange'] = 'fields_update_gadgets();';
          
          if($e) {
            print $this->Form->select('eppn_identifier_type',
                                      $vv_identifier_types,
                                      $attrs);
            
            if($this->Form->isFieldError('eppn_identifier_type')) {
              print $this->Form->error('eppn_identifier_type');
            }
          } else {
            if(!empty($org_identity_sources[0]['OrgIdentitySource']['eppn_identifier_type'])) {
              print $vv_identifier_types[ $org_identity_sources[0]['OrgIdentitySource']['eppn_identifier_type'] ];
            }
          }
        ?>
      </div>
    </li>
    <li>
      <div class="field-name">
        <div class="field-title"><?php print _txt('fd.ois.eppn.suffix'); ?></div>
        <div class="field-desc"><?php print _txt('fd.ois.eppn.suffix.desc'); ?></div>
      </div>
      <div class="field-info">
        <?php print ($e ? $this->Form->input('eppn_suffix') : filter_var($org_identity_sources[0]['OrgIdentitySource']['eppn_suffix'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
      </div>
    </li>
    <li>
      <div class="field-name">
        <div class="field-title">
          <?php print _txt('fd.ois.record.hash'); ?>
        </div>
        <div class="field-desc"><?php print _txt('fd.ois.record.hash.desc'); ?></div>
      </div>
      <div class="field-info">
        <?php print $this->Form->input('hash_source_record'); ?>
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
  </ul>
<?php endif; /* plugins */
  print $this->element("changelog");