<!--
/**
 * COmanage Registry Federation Source Plugin Fields
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
 * @package       registry-plugin
 * @since         COmanage Registry v4.4.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */
-->
<?php
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
  $args['controller'] = 'organization_sources';
  $args['action'] = 'index';
  $args['co'] = $cur_co['Co']['id'];
  $this->Html->addCrumb(_txt('ct.organization_sources.pl'), $args);

  $args = array();
  $args['plugin'] = null;
  $args['controller'] = 'organization_sources';
  $args['action'] = 'edit';
  $args[] = $vv_osid;
  $this->Html->addCrumb($federation_sources[0]['OrganizationSource']['description'], $args);

  $crumbTxt = _txt('op.config');
  $this->Html->addCrumb($crumbTxt);
  
  print $this->Form->hidden('organization_source_id', array('default' => $vv_osid)) . "\n";
?>
<script type="text/javascript">
  function fields_update_entities(self) {
    const mdq = '<?= MetadataProtocol::MDQ?>'
    const mode = ($(self).find(":checked").val())
    if (mode === mdq) {
      $('#mdq-entities-selector').show()
    } else {
      $('#mdq-entities-selector').hide()
    }
  }
</script>

<?php if(empty($vv_servers)): ?>
<div class="co-info-topbox">
  <em class="material-icons">info</em>
  <div class="co-info-topbox-text">
    <?php print _txt('er.server.none', array(_txt('en.server', null, ServerEnum::HttpServer))); ?>
  </div>
</div>
<?php else: // vv_servers ?>
<ul id="<?php print $this->action; ?>_federation_source" class="fields form-list form-list-admin">
  <li>
    <div class="field-name">
      <?php print ($e ? $this->Form->label('server_id', _txt('fd.server')) : _txt('fd.server')); ?>
      <span class="required">*</span>
    </div>
    <div class="field-info">
      <?php
        $attrs = array();
        $attrs['value'] = (isset($federation_sources[0]['FederationSource']['server_id'])
                           ? $federation_sources[0]['FederationSource']['server_id']
                           : null);
        // Since required=true, this will render blank by default but not allow save until a server is selected
        $attrs['empty'] = true;

        if($e) {
          print $this->Form->select('server_id',
                                    $vv_servers,
                                    $attrs);

          if($this->Form->isFieldError('server_id')) {
            print $this->Form->error('server_id');
          }
        } else {
          if(!empty($federation_sources[0]['FederationSource']['server_id'])) {
            print filter_var($vv_servers[ $federation_sources[0]['FederationSource']['server_id'] ], FILTER_SANITIZE_SPECIAL_CHARS);
          }
        }
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print ($e ? $this->Form->label('protocol', _txt('pl.federationsource.protocol')) : _txt('pl.federationsource.protocol')); ?>
        <span class="required">*</span>
      </div>
    </div>
    <div class="field-info">
      <?php
        global $cm_lang, $cm_texts;

        $attrs = array();
        $attrs['value'] = ($federation_sources[0]['FederationSource']['protocol'] ?? '');
        $attrs['empty'] = true;
        $attrs['onchange'] = 'fields_update_entities(this)';

        if($e) {
          print $this->Form->select('protocol',
                                    $cm_texts[ $cm_lang ]['pl.federationsource.en.protocol'],
                                    $attrs);

          if($this->Form->isFieldError('protocol')) {
            print $this->Form->error('protocol');
          }
        } else {
          print _txt('pl.federationsource.en.protocol', null, filter_var($federation_sources[0]['FederationSource']['protocol'],FILTER_SANITIZE_SPECIAL_CHARS));
        }
      ?>
    </div>
  </li>
  <?php if(!empty($federation_sources[0]['FederationSource']['protocol']) && $federation_sources[0]['FederationSource']['protocol'] === MetadataProtocol::MDQ): ?>
  <li id="mdq-entities-selector">
    <div class="field-name">
      <div class="field-title">
        <?php print ($e ? $this->Form->label('entities', _txt('pl.federationsource.entities')) : _txt('pl.federationsource.entities')); ?>
      </div>
    </div>
    <div class="field-info">
      <?php
      global $cm_lang, $cm_texts;

      $attrs = array();
      $attrs['value'] = ($federation_sources[0]['FederationSource']['entities'] ?? '');
      $attrs['empty'] = true;

      if($e) {
        print $this->Form->select('entities',
                                  $cm_texts[ $cm_lang ]['pl.federationsource.en.entities'],
                                  $attrs);

        if($this->Form->isFieldError('entities')) {
          print $this->Form->error('entities');
        }
      } else {
        print _txt('pl.federationsource.en.entities', null, filter_var($federation_sources[0]['FederationSource']['entities'],FILTER_SANITIZE_SPECIAL_CHARS));
      }
      ?>
    </div>
  </li>
  <?php endif; ?>
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
<?php endif; // vv_servers 