<?php
/**
 * COmanage Registry Urls Fields
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

  // We can emit all attributes because the ones we don't want should be null
  print $this->Form->hidden('co_department_id', array('default' => $vv_pid['codeptid'])) . "\n";
  print $this->Form->hidden('organization_id', array('default' => $vv_pid['organizationid'])) . "\n";
  print $this->Form->hidden('org_identity_id', array('default' => $vv_pid['orgidentityid'])) . "\n";
  print $this->Form->hidden('co_person_id', array('default' => $vv_pid['copersonid'])) . "\n";
  
  if(isset($cur_co))
    print $this->Form->hidden('co_id', array('default' => $cur_co['Co']['id'])) . "\n";
  
  // Add breadcrumbs
  print $this->element("coCrumb", array('mvpa' => 'urls'));
?>
<ul id="<?php print $this->action; ?>_url" class="fields form-list">
  <li>
    <span class="field-name">
      <div class="field-title">
      <?php
        if($e) {
          print $this->Form->label('url', _txt('fd.url.url'));
        } else {
          print _txt('fd.url.url');
        }
      ?>
      </div>
    </span>
    <span class="field-info">
      <?php
        // Force a text field since cake incorrectly generates a select on edit
        print ($e ? $this->Form->text('url', array('class' => 'focusFirst')) : filter_var($urls[0]['Url']['url'],FILTER_SANITIZE_SPECIAL_CHARS));
      ?>
    </span>
  </li>
  <li>
    <div class="field-name">
      <?php print $this->Form->label('description',_txt('fd.desc')); ?>
    </div>
    <div class="field-info">
      <?php print ($e ? $this->Form->input('description') : filter_var($urls[0]['Url']['description'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
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
        $attrs['value'] = (isset($urls) ? $urls[0]['Url']['type'] : "");
        $attrs['empty'] = false;

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
          print filter_var($urls[0]['Url']['type'],FILTER_SANITIZE_SPECIAL_CHARS);
        }
      ?>
    </span>
  </li>
  <?php if(!empty($urls[0]['Url']['SourceUrl']['id'])): ?>
    <li>
      <span class="field-name">
        <?php print _txt('fd.source'); ?><br />
      </span>
      <span class="field-info">
        <?php print $this->Html->link(_txt('ct.urls.1'),
                                      array('controller' => 'urls',
                                            'action'     => 'view',
                                            $urls[0]['SourceUrl']['id']));
        ?>,
        <?php print $this->Html->link(_txt('ct.org_identities.1'),
                                      array('controller' => 'org_identities',
                                            'action'     => 'view',
                                            $urls[0]['SourceUrl']['org_identity_id']));
        ?>
      </span>
    </li>
  <?php endif; // source id ?>

  <?php if($e): ?>
    <li class="fields-submit">
      <span class="field-name">
        <span class="required"><?php print _txt('fd.req'); ?></span>
      </span>
      <span class="field-info">
        <?php print $this->Form->submit($submit_label); ?>
      </span>
    </li>
  <?php endif; ?>
</ul>
<?php
  print $this->element("changelog");
