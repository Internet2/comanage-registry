<?php
/**
 * COmanage Registry Names Fields
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
 * @since         COmanage Registry v0.8.3
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

  // Determine if fields are editable
  $e = false;
  
  if(($this->action == "add" && $permissions['add']) || ($this->action == "edit" && $permissions['edit']))
    $e = true;
    
  // We shouldn't get here if we don't have at least read permission, but check just in case
  
  if(!$e && !$permissions['view'])
    return(false);

  $idForBreadcrumbs = '';
  $idType = null;

  // Migrate the target person from a named argument to a form field
  if($this->action == 'add' && isset($this->request->params['named']['copersonid'])) {
    $idForBreadcrumbs = filter_var($this->request->params['named']['copersonid'],FILTER_SANITIZE_SPECIAL_CHARS);
     $idType = 'person';
  } elseif(isset($this->request->data['Name']['co_person_id'])) {
    $idForBreadcrumbs = filter_var($this->request->data['Name']['co_person_id'],FILTER_SANITIZE_SPECIAL_CHARS);
     $idType = 'person';
  } elseif(!empty($names[0]['Name']['co_person_id'])) {
    $idForBreadcrumbs = filter_var($names[0]['Name']['co_person_id'],FILTER_SANITIZE_SPECIAL_CHARS);
    $idType = 'person';
  } elseif($this->action == 'add' && isset($this->request->params['named']['orgidentityid'])) {
    $idForBreadcrumbs = filter_var($this->request->params['named']['orgidentityid'],FILTER_SANITIZE_SPECIAL_CHARS);
    $idType = 'org';
  } elseif(isset($this->request->data['Name']['org_identity_id'])) {
    $idForBreadcrumbs = filter_var($this->request->data['Name']['org_identity_id'],FILTER_SANITIZE_SPECIAL_CHARS);
    $idType = 'org';
  } elseif(!empty($names[0]['Name']['org_identity_id'])) {
    $idForBreadcrumbs = filter_var($names[0]['Name']['org_identity_id'],FILTER_SANITIZE_SPECIAL_CHARS);
    $idType = 'org';
  }

  if($idType == 'org') {
    print $this->Form->hidden('org_identity_id', array('default' => $idForBreadcrumbs)) . "\n";
  } elseif($idType == 'person') {
    print $this->Form->hidden('co_person_id', array('default' => $idForBreadcrumbs)) . "\n";
  }
  
  if(isset($cur_co))
    print $this->Form->hidden('co_id', array('default' => $cur_co['Co']['id'])) . "\n";

  // Add breadcrumbs
  print $this->element("coCrumb");
  if($idType == 'person') {
    // CO Person History
    $args = array();
    $args['plugin'] = null;
    $args['controller'] = 'co_people';
    $args['action'] = 'index';
    $args['co'] = $cur_co['Co']['id'];
    $this->Html->addCrumb(_txt('me.population'), $args);

    $args = array(
      'controller' => 'co_people',
      'action' => 'canvas',
      $idForBreadcrumbs);
    $this->Html->addCrumb(_txt('ct.co_people.1'), $args);
  } elseif($idType == 'org') {
    // Org ID History
    $args = array();
    $args['plugin'] = null;
    $args['controller'] = 'org_identities';
    $args['action'] = 'index';
    if(!$pool_org_identities) {
      $args['co'] = $cur_co['Co']['id'];
    }
    $this->Html->addCrumb(_txt('ct.org_identities.pl'), $args);

    $args = array(
      'controller' => 'orgIdentities',
      'action' => 'edit',
      $idForBreadcrumbs);
    $this->Html->addCrumb(_txt('ct.org_identities.1'), $args);
  }
  $crumbTxt = _txt('op.' . $this->action . '-a', array(_txt('fd.name')));
  $this->Html->addCrumb($crumbTxt);

?>
<ul id="<?php print $this->action; ?>_name" class="fields form-list">
  <?php if(in_array('honorific', $vv_permitted_fields)): ?>
    <li>
      <span class="field-name">
        <?php
        if($e) {
          print $this->Form->label('Name.honorific', _txt('fd.name.honorific'));
          print " " . _txt('fd.name.h.desc');
        } else {
          print _txt('fd.name.honorific');
        }
        ?>
      </span>
      <span class="field-info">
        <?php
          // Carry the current status of primary_name, but to change it requires a different operation
          print $this->Form->hidden('Name.primary_name');
          print ($e ? $this->Form->input('Name.honorific') : filter_var($names[0]['Name']['honorific'],FILTER_SANITIZE_SPECIAL_CHARS));
        ?>
       </span>
    </li>
    <?php endif; // permitted fields ?>
    <?php if(in_array('given', $vv_permitted_fields)): ?>
    <li>
      <span class="field-name">
        <?php
          if($e) {
            if(in_array('given', $vv_required_fields)) {
              print $this->Form->label('Name.given', _txt('fd.name.given')) . ' <span class="required">*</span>';
            } else {
              print $this->Form->label('Name.given', _txt('fd.name.given'));
            }
          } else {
            print _txt('fd.name.given');
          }
        ?>
      </span>
      <span class="field-info">
        <?php print ($e ? $this->Form->input('Name.given', array('class' => 'focusFirst')) : filter_var($names[0]['Name']['given'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
      </span>
    </li>
  <?php endif; // permitted fields ?>
  <?php if(in_array('middle', $vv_permitted_fields)): ?>
    <li>
      <span class="field-name">
        <?php
        if($e) {
          print $this->Form->label('Name.middle', _txt('fd.name.middle'));
          if(in_array('middle', $vv_required_fields)) {
            print ' <span class="required">*</span>';
          }
        } else {
          print _txt('fd.name.middle');
        }
        ?>
      </span>
      <span class="field-info">
        <?php print ($e ? $this->Form->input('Name.middle') : filter_var($names[0]['Name']['middle'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
      </span>
    </li>
    <?php endif; // permitted fields ?>
    <?php if(in_array('family', $vv_permitted_fields)): ?>
    <li>
      <span class="field-name">
        <?php
        if($e) {
          print $this->Form->label('Name.family', _txt('fd.name.family'));
          if(in_array('family', $vv_required_fields)) {
            print ' <span class="required">*</span>';
          }
        } else {
          print _txt('fd.name.family');
        }
        ?>
      </span>
      <span class="field-info">
        <?php print ($e ? $this->Form->input('Name.family') : filter_var($names[0]['Name']['family'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
      </span>
    </li>
    <?php endif; // permitted fields ?>
    <?php if(in_array('suffix', $vv_permitted_fields)): ?>
    <li>
      <span class="field-name">
        <?php
          if($e) {
            print $this->Form->label('Name.suffix',_txt('fd.name.suffix'));
            print " " . _txt('fd.name.s.desc');
          } else {
            print _txt('fd.name.suffix');
          }
        ?>
      </span>
      <span class="field-info">
        <?php print ($e ? $this->Form->input('Name.suffix') : filter_var($names[0]['Name']['suffix'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
      </span>
    </li>
  <?php endif; // permitted fields ?>
  
  <li>
    <span class="field-name">
      <?php
      if($e) {
        print $this->Form->label('Name.type',_txt('fd.type'));
      } else {
        print _txt('fd.type');
      }
      ?>
    </span>
    <span class="field-info">
      <?php
        $attrs = array();
        $attrs['value'] = (isset($names[0]['Name']['type'])
                           ? $names[0]['Name']['type']
                           : NameEnum::Alternate);
        $attrs['empty'] = false;
        
        if($e) {
          print $this->Form->select('Name.type',
                                    $vv_available_types,
                                    $attrs);
          
          if($this->Form->isFieldError('Name.type')) {
            print $this->Form->error('Name.type');
          }
        } else {
          print $vv_available_types[ $names[0]['Name']['type'] ];
        }
      ?>
    </span>
  </li>
  <li>
    <span class="field-name">
      <?php
        if($e) {
          print $this->Form->label('Name.language', _txt('fd.language'));
        } else {
          print _txt('fd.language');
        }
      ?>
    </span>
    <span class="field-info">
      <?php
        global $cm_lang, $cm_texts;
        
        $attrs = array();
        $attrs['value'] = (isset($names[0]['Name']['language'])
                           ? $names[0]['Name']['language']
                           : getPreferredLanguage());
        $attrs['empty'] = true;
        
        if($e) {
          print $this->Form->select('Name.language',
                                    $cm_texts[ $cm_lang ]['en.language'],
                                    $attrs);
          
          if($this->Form->isFieldError('Name.language')) {
            print $this->Form->error('Name.language');
          }
        } else {
          if(!empty($names[0]['Name']['language'])) {
            print _txt('en.language', null, $names[0]['Name']['language']);
          }
        }
      ?>
    </span>
  </li>
  <li class="fields-submit">
    <span class="field-name">
      <span class="required"><?php print _txt('fd.req'); ?></span>
    </span>
    <span class="field-info">
      <?php
        if($e) {
          print $this->Form->submit($submit_label);
        }
      ?>
    </span>
  </li>
</ul>
<?php
  print $this->element("changelog");
