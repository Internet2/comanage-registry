<?php
/**
 * COmanage Registry CO Localizations Fields
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

  // Add breadcrumbs
  print $this->element("coCrumb");
  $args = array();
  $args['plugin'] = null;
  $args['controller'] = 'co_localizations';
  $args['action'] = 'index';
  $args['co'] = $cur_co['Co']['id'];
  $this->Html->addCrumb(_txt('ct.co_localizations.pl'), $args);
  $crumbTxt = _txt('op.' . $this->action . '-a', array(_txt('ct.co_localizations.1')));
  $this->Html->addCrumb($crumbTxt);
  
  print $this->Form->hidden('co_id', array('default' => $cur_co['Co']['id'])) . "\n";
?>
<script type="text/javascript">
  function populate_default_text() {
    var cm_texts = new Array();
    
    <?php
      // Populate the array with the current translation strings
      
      foreach(array_keys($vv_cm_texts) as $k) {
        print 'cm_texts["' . $k . '"] = "' . str_replace('"', '\"', $vv_cm_texts[$k]) . '";' . "\n";
      }
    ?>
    
    $("#original").text(cm_texts[document.getElementById('CoLocalizationLkey').value]);
  }
  
  function js_local_onload() {
    populate_default_text();
  }
</script>
<table id="<?php print $this->action; ?>_co_localizations" class="ui-widget">
  <tbody>
    <tr class="line1">
      <td>
        <strong class="fieldTitle">
          <?php print ($e ? $this->Form->label('lkey', _txt('fd.key')) : _txt('fd.key')); ?>
        </strong><span class="required">*</span>
      </td>
      <td>
        <?php
          global $cm_lang, $cm_texts;
          $attrs['value'] = (isset($co_localizations[0]['CoLocalization']['lkey'])
                             ? $co_localizations[0]['CoLocalization']['lkey']
                             : "");
          $attrs['empty'] = false;
          $attrs['onChange'] = 'populate_default_text()';
          
          if($e) {
            // Make sure both the value and the displayed text are the key
            $keys = array_keys($vv_cm_texts);
            
            print $this->Form->select('lkey',
                                      array_combine($keys, $keys),
                                      $attrs);
            
            if($this->Form->isFieldError('lkey')) {
              print $this->Form->error('lkey');
            }
          } else {
            print $co_localizations[0]['CoLocalization']['lkey'];
          }
        ?>
      </td>
    </tr>
    <tr class="line2">
      <td>
        <strong class="fieldTitle">
          <?php print ($e ? $this->Form->label('language', _txt('fd.language')) : _txt('fd.language')); ?>
        </strong><span class="required">*</span>
      </td>
      <td>
        <?php print ($e
                     ? $this->Form->input('language', array('default' => 'en_US'))
                     : filter_var($co_localizations[0]['CoLocalization']['language'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
      </td>
    </tr>
    <tr class="line1">
      <td>
        <strong class="fieldTitle">
          <?php print ($e ? $this->Form->label('text', _txt('fd.text')) : _txt('fd.text')); ?>
        </strong><span class="required">*</span><br />
      </td>
      <td>
        <?php print ($e
                     ? $this->Form->input('text', array('size' => '80','class' => 'focusFirst'))
                     : filter_var($co_localizations[0]['CoLocalization']['text'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
      </td>
    </tr>
    <tr class="line1">
      <td>
        <strong class="fieldTitle">
          <?php print _txt('fd.text.original'); ?>
        </strong>
      </td>
      <td>
        <div id="original">
        </div>
      </td>
    </tr>
    <tr>
      <td>
        <em><span class="required"><?php print _txt('fd.req'); ?></span></em><br />
      </td>
      <td>
        <?php
          if($e) {
            print $this->Form->submit($submit_label, array('onClick' => 'on_submit()'));
            print $this->Form->button(_txt('op.reset'), 
                                      array('type'=>'reset'));
          }
        ?>
      </td>
    </tr>
  </tbody>
</table>
