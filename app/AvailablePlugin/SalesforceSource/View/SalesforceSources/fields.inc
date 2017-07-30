<!--
/**
 * COmanage Registry Salesforce Source Plugin Fields
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
 * @since         COmanage Registry v3.1.0
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
  $args['controller'] = 'org_identity_sources';
  $args['action'] = 'index';
  $args['co'] = $cur_co['Co']['id'];
  $this->Html->addCrumb(_txt('ct.org_identity_sources.pl'), $args);

  $args = array();
  $args['plugin'] = null;
  $args['controller'] = 'org_identity_sources';
  $args['action'] = 'edit';
  $args[] = $vv_oisid;
  $this->Html->addCrumb($salesforce_sources[0]['OrgIdentitySource']['description'], $args);

  $crumbTxt = _txt('op.config');
  $this->Html->addCrumb($crumbTxt);
  
  print $this->Form->hidden('org_identity_source_id', array('default' => $vv_oisid)) . "\n";
  
  $l = 1;
?>
<ul id="<?php print $this->action; ?>_salesforce_source" class="fields form-list">
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('pl.salesforcesource.redirect_uri'); ?>
      </div>
    </div>
    <div class="field-info">
      <input type="text" readonly="true" size="60" value="<?php print $this->Html->url($vv_salesforce_redirect_url, true); ?>" />
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('pl.salesforcesource.serverurl'); ?>
        <span class="required">*</span>
      </div>
      <div class="field-desc"><?php print _txt('pl.salesforcesource.serverurl.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php print ($e ? $this->Form->input('serverurl', array('size' => 50)) : filter_var($salesforce_sources[0]['SalesforceSource']['serverurl'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('pl.salesforcesource.clientid'); ?>
        <span class="required">*</span>
      </div>
      <div class="field-desc"><?php print _txt('pl.salesforcesource.clientid.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php print ($e ? $this->Form->input('clientid', array('size' => 50)) : filter_var($salesforce_sources[0]['SalesforceSource']['clientid'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('pl.salesforcesource.secret'); ?>
        <span class="required">*</span>
      </div>
      <div class="field-desc"><?php print _txt('pl.salesforcesource.secret.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php print ($e ? $this->Form->input('client_secret', array('size' => 50)) : filter_var($salesforce_sources[0]['SalesforceSource']['secret'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('pl.salesforcesource.search.contacts'); ?>
      </div>
    </div>
    <div class="field-info">
      <?php print $this->Form->input('search_contacts', array('default' => true)); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('pl.salesforcesource.search.users'); ?>
      </div>
    </div>
    <div class="field-info">
      <?php print $this->Form->input('search_users'); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('pl.salesforcesource.custom'); ?>
      </div>
      <div class="field-desc"><?php print _txt('pl.salesforcesource.custom.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php print ($e ? $this->Form->input('custom_objects', array('size' => 50)) : filter_var($salesforce_sources[0]['SalesforceSource']['custom_objects'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <?php if(!empty($salesforce_sources[0]['SalesforceSource']['serverurl'])): ?>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('pl.salesforcesource.token'); ?>
      </div>
    </div>
    <div class="field-info">
      <?php print _txt(!empty($salesforce_sources[0]['SalesforceSource']['refresh_token']) ? 'fd.set' : 'fd.set.not'); ?>
      <?php
        if(!empty($salesforce_sources[0]['SalesforceSource']['serverurl'])) {
          $loginUrl = $salesforce_sources[0]['SalesforceSource']['serverurl']
                    . '/services/oauth2/authorize?response_type=code'
                    . '&client_id=' . $salesforce_sources[0]['SalesforceSource']['clientid']
                    . '&redirect_uri=' . urlencode($this->Html->url($vv_salesforce_redirect_url, true))
                    // We base64 encode the redirect URL to make it easy to pass back in the callback
                    . '&state=' . base64_encode($this->Html->url($vv_salesforce_redirect_url, true));
          
          print $this->Html->link(_txt('pl.salesforcesource.token.new'),
                                  $loginUrl,
                                  array('class' => 'runbutton'));
        }
      ?>
    </div>
  </li>
  <?php endif; // serverurl ?>
  
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
