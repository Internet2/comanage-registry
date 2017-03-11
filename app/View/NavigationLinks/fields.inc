<?php
/**
 * COmanage Registry Navigation Link Fields
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
 * @since         COmanage Registry v0.8.1
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
  $this->Html->addCrumb(_txt('ct.navigation_links.pl'), array('controller' => 'navigation_links', 'action' => 'index'));
  $crumbTxt = _txt('op.' . $this->action . '-a', array(_txt('ct.navigation_links.1')));
  $this->Html->addCrumb($crumbTxt);

?>

<table id="<?php print $this->action; ?>_link" class="ui-widget">
  <tbody>
    <tr class="line1">
      <td>
        <strong class="fieldTitle"><?php print ($e ? $this->Form->label('description', _txt('fd.desc')) : _txt('fd.desc')); ?></strong>
      </td>
      <td>
        <?php print ($e
                     ? $this->Form->input('description', array('class' => 'focusFirst'))
                     : filter_var($navigation_links[0]['NavigationLink']['description'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
      </td>
    </tr>
    <tr class="line2">
      <td>
        <strong class="fieldTitle"><?php print ($e ? $this->Form->label('title', _txt('fd.link.title')) : _txt('fd.link.title')); ?></strong>
      </td>
      <td>
        <?php print ($e
                     ? $this->Form->input('title')
                     : filter_var($navigation_links[0]['NavigationLink']['title'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
      </td>
    </tr>
    <tr class="line1">
      <td>
        <strong class="fieldTitle"><?php print ($e ? $this->Form->label('url', _txt('fd.link.url')) : _txt('fd.link.url')); ?></strong>
      </td>
      <td>
        <?php print ($e
                     ? $this->Form->input('url')
                     : filter_var($navigation_links[0]['NavigationLink']['url'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
      </td>
    </tr>
    <tr class="line2">
      <td>
        <strong class="fieldTitle"><?php print ($e ? $this->Form->label('ordr', _txt('fd.link.order')) : _txt('fd.link.order')); ?></strong>
      </td>
      <td>
        <?php print ($e
                     ? $this->Form->input('ordr')
                     : filter_var($navigation_links[0]['NavigationLink']['ordr'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
      </td>
    </tr>
    <tr class="line1">
      <td>
        <strong class="fieldTitle"><?php print ($e ? $this->Form->label('location', _txt('fd.link.location')) : _txt('fd.link.location')); ?></strong>
      </td>
      <td>
        <?php
          if($e) {
            if (!empty($this->viewVars['vv_link_location_options'] )) {
              // Print dropdown selector with location options
              print $this->Form->select('location', $this->viewVars['vv_link_location_options'], array('empty' => false));
            } 
          }
          else {
            print filter_var($navigation_links[0]['NavigationLink']['location'],FILTER_SANITIZE_SPECIAL_CHARS);
          }

          if($this->Form->isFieldError('status')) {
            print $this->Form->error('status');
          }
        ?>
      </td>
    </tr>

    <?php
    if($e) {
      print '<tr><td></td><td>';
      print $this->Form->submit($submit_label, array('onClick' => 'on_submit()'));
      print $this->Form->button(_txt('op.reset'), array('type'=>'reset'));
      print '</td></tr>';
    }
    ?>

  </tbody>
</table>
