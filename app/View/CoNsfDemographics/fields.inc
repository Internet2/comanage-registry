<?php
/**
 * COmanage Registry CO NSF Demographic Fields
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
 * @since         COmanage Registry v0.3
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

  // Determine if fields are editable
  $e = false;

  if(($this->action == "add" && $permissions['add']) || ($this->action == "edit" && $permissions['edit']))
    $e = true;

  // We shouldn't get here if we don't have at least read permission, but check just in case

  if(!$e && !$permissions['view'])
    return false;

  // Set hidden defaults for CO and CoPerson id so users don't need to see it
  $args = array('default' => $cur_co['Co']['id']);
  print $this->Form->hidden('co_id', $args). "\n";

  if(isset($this->request->params['named']['copersonid'])) {
    // Populate default CO Person ID for add
    $args = array('default' => filter_var($this->request->params['named']['copersonid'],FILTER_SANITIZE_SPECIAL_CHARS));
  }

  print $this->Form->hidden('co_person_id', $args). "\n";

  //globals
  global $cm_lang, $cm_texts;

  // Combine options and descriptions for display
  // Note: these options must be plain text, as the html is escaped before rendering.
  foreach($cm_texts[ $cm_lang ]['en.nsf.race'] as $key => $value)
  {
    $optionDesc['race'][$key] = $value;
    if(isset($cm_texts[ $cm_lang ]['en.nsf.race.desc'][$key]))
      $optionDesc['race'][$key] .= " - " . $cm_texts[ $cm_lang ]['en.nsf.race.desc'][$key];
  }

  // Currently has no description, but may as well leave it as an option.
  foreach($cm_texts[ $cm_lang ]['en.nsf.disab'] as $key => $value)
  {
    $optionDesc['disability'][$key] = $value;
    if(isset($cm_texts[ $cm_lang ]['en.nsf.disab.desc'][$key]))
      $optionDesc['disability'][$key] .= " - " . $cm_texts[ $cm_lang ]['en.nsf.disab.desc'][$key];
  }

  $emptyArg = array('empty' => true);
?>
<ul id="<?php print $this->action; ?>_co_nsf_demographic" class="fields form-list">
  <li>
    <div class="field-name">
      <?php print _txt('fd.de.gender'); ?>
    </div>
    <div class="field-info">
      <?php
        $attrs['value'] = (isset($co_nsf_demographics) ? $co_nsf_demographics[0]['CoNsfDemographic']['gender'] : '');
        $attrs['empty'] = false;
        
        if($e) {
          print $this->Form->select('gender',
                                    $cm_texts[ $cm_lang ]['en.nsf.gender'],
                                    $attrs);
          
          if($this->Form->isFieldError('gender')) {
            print $this->Form->error('gender');
          }
        } else {
          print _txt('en.nsf.gender', null, $CoNsfDemographics[0]['CoNsfDemographic']['gender']);
        }
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <?php print _txt('fd.de.citizen'); ?>
    </div>
    <div class="field-info">
      <?php
        $attrs['value'] = (isset($co_nsf_demographics) ? $co_nsf_demographics[0]['CoNsfDemographic']['citizenship'] : '');
        $attrs['empty'] = false;
        
        if($e) {
          print $this->Form->select('citizenship',
                                    $cm_texts[ $cm_lang ]['en.nsf.citizen'],
                                    $attrs);
          
          if($this->Form->isFieldError('citizenship')) {
            print $this->Form->error('citizenship');
          }
        } else {
          print _txt('en.nsf.citizen', null, $CoNsfDemographics[0]['CoNsfDemographic']['citizenship']);
        }
      ?>
    </div>
  </li>
  <li>
    <div class="field-name vtop">
      <?php print _txt('fd.de.ethnic'); ?>
    </div>
    <div class="field-info">
      <?php
        $attrs['value'] = (isset($co_nsf_demographics) ? $co_nsf_demographics[0]['CoNsfDemographic']['ethnicity'] : '');
        $attrs['empty'] = false;
        
        if($e) {
          print $this->Form->select('ethnicity',
                                    $cm_texts[ $cm_lang ]['en.nsf.ethnic'],
                                    $attrs);
          
          if($this->Form->isFieldError('ethnicity')) {
            print $this->Form->error('ethnicity');
          }
        } else {
          print _txt('en.nsf.ethnic', null, $CoNsfDemographics[0]['CoNsfDemographic']['ethnicity']);
        }
      ?>
    </div>
  </li>
  <li>
    <div class="field-name vtop">
      <?php print _txt('fd.de.race'); ?>
    </div>
    <div class="field-info">
      <?php 
        $args = array('type'     => 'select',
                      'multiple' => 'checkbox',
                      'options'  => $optionDesc['race'],
                      'selected' => (isset($race_options) ? $race_options : array())
                     );
        print ($e ? $this->Form->input('race', $args)
                  : _txt('en.nsf.race',
                         null,
                         $CoNsfDemographics[0]['CoNsfDemographic']['race']));
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <?php print _txt('fd.de.disab'); ?>
    </div>
    <div class="field-info">
      <?php 
        $args = array('type'     => 'select',
                      'multiple' => 'checkbox',
                      'options'  => $optionDesc['disability'],
                      'selected' => (isset($disability_options) ? $disability_options : array())
                     );
        print ($e ? $this->Form->input('disability', $args)
                  : _txt('en.nsf.disab',
                         null,
                         $CoNsfDemographics[0]['CoNsfDemographic']['disability']));
      ?>
    </div>
  </li>
  <?php if($e): ?>
    <li class="fields-submit">
      <div class="field-name"></div>
      <div class="field-info">
        <?php print $this->Form->submit($submit_label); ?>
      </div>
    </li>
  <?php endif; ?>
</ul>

<?php
  print $this->element("changelog");