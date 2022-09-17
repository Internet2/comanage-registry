<?php
/**
 * COmanage Registry Core APIs Fields
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
 * @since         COmanage Registry v4.0.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */
?>
<?php
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
  $args['plugin'] = 'core_api';
  $args['controller'] = 'core_apis';
  $args['action'] = 'index';
  $args['co'] = $cur_co['Co']['id'];
  $this->Html->addCrumb(_txt('ct.core_apis.pl'), $args);
  $crumbTxt = _txt('op.' . $this->action . '-a', array(_txt('ct.core_apis.1')));
  $this->Html->addCrumb($crumbTxt);

  print $this->Form->hidden('co_id', array('default' => $cur_co['Co']['id'])) . "\n";
?>
<script type="text/javascript">
  <!-- JS specific to these fields -->

  function core_api_toggle() {
    response_config_field();
    expunge_on_del_toggle();
    identifier_toggle();
  }

  function expunge_on_del_toggle() {
    let core_api = $('#CoreApiApi > option:selected').val();
    let person_write = '<?php print CoreApiEnum::CoPersonWrite;?>';
    if (core_api != undefined
        && core_api == person_write) {
      $('.core-api-expunge-on-del').show();
    } else {
      // hide
      $('.core-api-expunge-on-del').hide();

      // clear the value
      $('#CoreApiExpungeOnDelete').prop( "checked", false );
    }
  }
  
  function identifier_toggle() {
    // Identifier type is required for Core Person APIs only
    
    var api = document.getElementById('CoreApiApi').value;

    if(api == "<?php print CoreApiEnum::CoPersonRead; ?>"
       || api == "<?php print CoreApiEnum::CoPersonWrite; ?>") {
      $("#CoreApiIdentifierType").closest("li").show('fade');
    } else {
      $("#CoreApiIdentifierType").closest("li").hide('fade');
    }
  }

  // Response config is only for CO Person API. For other APIs we will need to hide this configuration
  function response_config_field() {
    let core_apis = <?php
      print json_encode(array(CoreApiEnum::CoPersonRead, CoreApiEnum::CoPersonWrite));
      ?>;
    let core_api = $('#CoreApiApi > option:selected').val();
    if (core_api != undefined
        && core_apis.includes(core_api)) {
      $('.core-api-reponse-type').show();
      $('#CoreApiIndexResponseType').attr("required","required");
      $('#CoreApiIndexResponseType').attr('data-error', 'This field is required.');
    } else {
      // hide
      $('#CoreApiIndexResponseType').removeAttr("required");
      $('#CoreApiIndexResponseType').removeAttr("data-error");
      $('.core-api-reponse-type').hide();

      // clear the value
      $('#CoreApiIndexResponseType > option:selected').removeAttr("selected");
      $('#CoreApiIndexResponseType option[value=""]').attr("selected", "selected");
    }
  }
  
  function js_local_onload() {
    core_api_toggle();
  }
</script>
<?php if(!empty($vv_api_endpoint)): ?>
<div class="co-info-topbox">
  <i class="material-icons">info</i>
  <?php print _txt('pl.coreapi.info', array($vv_api_endpoint)); ?>
</div>
<?php endif; // $vv_api_endpoint ?>
<ul id="<?php print $this->action; ?>_core_apis" class="fields form-list">
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print ($e ? $this->Form->label('status', _txt('fd.status')) : _txt('fd.status')); ?>
        <span class="required">*</span>
      </div>
    </div>
    <div class="field-info">
      <?php
        global $cm_lang, $cm_texts;
        $attrs = array();
        $attrs['value'] = (isset($core_apis[0]['CoreApi']['status'])
                           ? $core_apis[0]['CoreApi']['status']
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
          print _txt('en.status', null, $core_apis[0]['CoreApi']['status']);
        }
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print ($e ? $this->Form->label('api', _txt('pl.coreapi.api')) : _txt('pl.coreapi.api')); ?>
        <span class="required">*</span>
      </div>
    </div>
    <div class="field-info">
      <?php
        global $cm_lang, $cm_texts;
        $attrs = array();
        $attrs['value'] = (isset($core_apis[0]['CoreApi']['api'])
                           ? $core_apis[0]['CoreApi']['api']
                           : "");
        $attrs['empty'] = true;
        $attrs['onchange'] = 'core_api_toggle();';

        if($e) {
          print $this->Form->select('api',
                                    $cm_texts[ $cm_lang ]['pl.coreapi.en.api'],
                                    $attrs);

          if($this->Form->isFieldError('api')) {
            print $this->Form->error('api');
          }
        } else {
          print _txt('pl.coreapi.en.api', null, $core_apis[0]['CoreApi']['api']);
        }
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('ct.api_users.1'); ?>
        <span class="required">*</span>
      </div>
      <div class="field-desc"><?php print _txt('pl.coreapi.api_user.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php
        $attrs = array();
        $attrs['value'] = (isset($core_apis[0]['CoreApi']['api_user_id'])
                           ? $core_apis[0]['CoreApi']['api_user_id']
                           : "");
        $attrs['empty'] = true;

        if($e) {
          print $this->Form->select('api_user_id',
                                    $vv_api_users,
                                    $attrs);

          if($this->Form->isFieldError('api_user_id')) {
            print $this->Form->error('api_user_id');
          }
        } else {
          print filter_var($vv_api_users[ $core_apis[0]['CoreApi']['api_user_id'] ], FILTER_SANITIZE_SPECIAL_CHARS);
        }
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('ct.identifiers.1'); ?>
        <span class="required">*</span>
      </div>
      <div class="field-desc"><?php print _txt('pl.coreapi.identifier.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php
        $attrs = array();
        $attrs['value'] = (isset($core_apis[0]['CoreApi']['identifier_type'])
                           ? $core_apis[0]['CoreApi']['identifier_type']
                           : null);
        $attrs['empty'] = true;

        if($e) {
          print $this->Form->select('identifier_type',
                                    $vv_identifier_types,
                                    $attrs);

          if($this->Form->isFieldError('identifier_type')) {
            print $this->Form->error('identifier_type');
          }
        } else {
          if(!empty($core_apis[0]['CoreApi']['identifier_type'])) {
            print $vv_identifier_types[ $core_apis[0]['CoreApi']['identifier_type'] ];
          }
        }
      ?>
    </div>
  </li>
  <li
    class="core-api-reponse-type"
    style="<?php
    $person_api_keys = array(CoreApiEnum::CoPersonRead, CoreApiEnum::CoPersonWrite);
    print (!empty($core_apis[0]['CoreApi']['api'])
           && in_array($core_apis[0]['CoreApi']['api'], $person_api_keys))
           ? '' :  'display: none'; ?>"
  >
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('pl.coreapi.response.type'); ?>
        <span class="required">*</span>
      </div>
      <div class="field-desc"><?php print _txt('pl.coreapi.response.type.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php
      $attrs = array();
      $attrs['value'] = (isset($core_apis[0]['CoreApi']['index_response_type'])
        ? $core_apis[0]['CoreApi']['index_response_type']
        : '');
      $attrs['empty'] = true;
      $attrs['required'] = (!empty($core_apis[0]['CoreApi']['api'])
                            && in_array($core_apis[0]['CoreApi']['api'], $person_api_keys));

      if($e) {
        print $this->Form->select('index_response_type',
                                  $cm_texts[ $cm_lang ]['pl.coreapi.en.response.type'],
                                  $attrs);

        if($this->Form->isFieldError('index_response_type')) {
          print $this->Form->error('index_response_type');
        }
      } else {
        if(!empty($core_apis[0]['CoreApi']['index_response_type'])) {
          print _txt('pl.coreapi.en.response.type', null, $core_apis[0]['CoreApi']['index_response_type']);
        }
      }
      ?>
    </div>
  </li>
  <li
    class="core-api-expunge-on-del"
    style="<?php
    print (!empty($core_apis[0]['CoreApi']['api'])
           && $core_apis[0]['CoreApi']['api'] === CoreApiEnum::CoPersonWrite)
      ? '' :  'display: none'; ?>"
  >
    <div class="field-name">
      <div class="field-title"><?php print _txt('pl.coreapi.expunge.on.del'); ?></div>
    </div>
    <div class="field-info checkbox">
      <?php
      if ($e) {
        $args = array();
        $args['class'] = 'mr-1';
        $args['checked'] = (isset($core_apis[0]['CoreApi']['expunge_on_delete'])
                            && $core_apis[0]['CoreApi']['expunge_on_delete']);

        print $this->Form->checkbox('expunge_on_delete', $args);
        print $this->Form->label('expunge_on_delete', _txt('pl.coreapi.expunge.on.del'));
      } else {
        print (isset($core_apis[0]['CoreApi']['expunge_on_delete']) && $core_apis[0]['CoreApi']['expunge_on_delete'])
              ? _txt('fd.yes') : _txt('fd.no');
      }
      ?>
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
<?php
  print $this->element("changelog");
  