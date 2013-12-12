<!--
/**
 * COmanage Registry CO Localizations Fields
 *
 * Copyright (C) 2013 University Corporation for Advanced Internet Development, Inc.
 * 
 * Licensed under the Apache License, Version 2.0 (the "License"); you may not use this file except in compliance with
 * the License. You may obtain a copy of the License at
 * 
 * http://www.apache.org/licenses/LICENSE-2.0
 * 
 * Unless required by applicable law or agreed to in writing, software distributed under
 * the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY
 * KIND, either express or implied. See the License for the specific language governing
 * permissions and limitations under the License.
 *
 * @copyright     Copyright (C) 201 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.8.3
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */
-->
<?php
  // Determine if fields are editable
  $e = false;
  
  if(($this->action == "add" && $permissions['add']) || ($this->action == "edit" && $permissions['edit']))
    $e = true;
    
  // We shouldn't get here if we don't have at least read permission, but check just in case
  
  if(!$e && !$permissions['view'])
    return(false);
  
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
        <b><?php print _txt('fd.key'); ?></b></b><font class="required">*</font>
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
        <b><?php print _txt('fd.language'); ?></b><font class="required">*</font>
      </td>
      <td>
        <?php print ($e
                     ? $this->Form->input('language', array('default' => 'en_US'))
                     : Sanitize::html($co_localizations[0]['CoLocalization']['language'])); ?>
      </td>
    </tr>
    <tr class="line1">
      <td>
        <b><?php print _txt('fd.text'); ?></b><font class="required">*</font><br />
      </td>
      <td>
        <?php print ($e
                     ? $this->Form->input('text', array('size' => '80'))
                     : Sanitize::html($co_localizations[0]['CoLocalization']['text'])); ?>
      </td>
    </tr>
    <tr class="line1">
      <td>
        <b><?php print _txt('fd.text.original'); ?></b>
      </td>
      <td>
        <div id="original">
        </div>
      </td>
    </tr>
    <tr>
      <td>
        <i><font class="required"><?php echo _txt('fd.req'); ?></font></i><br />
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
