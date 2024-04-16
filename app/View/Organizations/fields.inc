<?php
/**
 * COmanage Registry Organizations Fields
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
  $args['controller'] = 'organizations';
  $args['action'] = 'index';
  if(isset($cur_co)) {
    $args['co'] = $cur_co['Co']['id'];
  }
  $this->Html->addCrumb(_txt('ct.organizations.pl'), $args);
  $crumbTxt = _txt('op.' . $this->action . '-a', array(_txt('ct.organizations.1')));
  $this->Html->addCrumb($crumbTxt);
    
  if(isset($cur_co))
    print $this->Form->hidden('co_id', array('default' => $cur_co['Co']['id'])) . "\n";
?>
<div id="<?php print $this->action; ?>_organization" class="explorerContainer">
  <div id="coOrgIdExplorer" class="personExplorer">
    <ul>
      <!-- Organization Info -->
      <li id="fields-name" class="fieldGroup">
        <a href="#tabs-name" class="fieldGroupName" title="<?php print _txt('op.collapse') ?>" aria-expanded="true" aria-controls="names-container">
          <em class="material-icons" aria-hidden="true">expand_less</em>
          <h2><?php print _txt('ct.organizations.1'); ?></h2>
        </a>

        <div id="names-container" class="fields">
          <ul id="tabs-name-fields" class="fields form-list">
        <div id="names-container" class="fields">
          <li>
            <div class="field-name">
              <?php print $this->Form->label('name',_txt('fd.name')); ?><span class="required">*</span>
            </div>
            <div class="field-info">
              <?php print ($e ? $this->Form->input('name', array('class' => 'focusFirst')) : filter_var($organizations[0]['Organization']['name'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
            </div>
          </li>
          <li>
            <div class="field-name">
              <?php print $this->Form->label('description',_txt('fd.desc')); ?>
            </div>
            <div class="field-info">
              <?php print ($e ? $this->Form->input('description') : filter_var($organizations[0]['Organization']['description'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
            </div>
          </li>
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
              $attrs['value'] = (isset($organizations) ? $organizations[0]['Organization']['type'] : "");
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
                print filter_var($organizations[0]['Organization']['type'],FILTER_SANITIZE_SPECIAL_CHARS);
              }
              ?>
            </span>
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
            'model'        => 'Organization',
            'model_param'  => 'orgid',
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
