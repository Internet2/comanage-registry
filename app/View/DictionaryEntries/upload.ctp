<?php
/**
 * COmanage Registry Dictionary Entries Upload View
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

  // Add breadcrumbs
  print $this->element("coCrumb");
  
  $args = array();
  $args['controller'] = 'dictionaries';
  $args['action'] = 'index';
  $args['co'] = $cur_co['Co']['id'];
  $this->Html->addCrumb(_txt('ct.dictionaries.pl'), $args);
  
  $args = array();
  $args['controller'] = 'dictionaries';
  $args['action'] = 'edit';
  $args[] = $vv_dict_id;
  $this->Html->addCrumb($vv_dict_name, $args);

  $args = array();
  $args['controller'] = 'dictionary_entries';
  $args['action'] = 'index';
  $args['dictionary'] = $vv_dict_id;
  $this->Html->addCrumb(_txt('ct.dictionary_entries.pl'), $args);
  
  $this->Html->addCrumb(_txt('op.upload'));
  
  // Add page title
  $params = array();
  $params['title'] = $title_for_layout;
  
  print $this->element("pageTitleAndButtons", $params);
  
  // Some browsers will inject this, breaking the security field check.
  // We don't need the field, we just need SecurityComponent to not choke on it.
  $this->Form->unlockField('file.full_path');
?>

<div class="table-container">
  <table id="upload_dictionary_entries" class="ui-widget">
    <tbody>
      <tr class="line1">
        <td>
          <?php
            print $this->Form->create('DictionaryEntry', array('type' => 'file',
                                                               'url'  => array('action' => 'upload')));
            
            print $this->Form->hidden('DictionaryEntry.dictionary_id', 
                                      array('default' => filter_var($vv_dict_id,FILTER_SANITIZE_SPECIAL_CHARS)));
            
            print $this->Form->file('DictionaryEntry.file');
            
            print "<br />" . $this->Form->checkbox('replace');
            print _txt('op.upload.replace');
          ?>
        </td>
      </tr>
      <tr>
        <td>
          <?php 
            print $this->Form->submit(_txt('op.upload')); 
            print $this->Form->end();
          ?>
        </td>
      </tr>
    </tbody>
  </table>
</div>
