<?php
/**
 * COmanage Registry CO Themes Fields
 *
 * Copyright (C) 2016 MLA
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
 * @copyright     Copyright (C) 2016 MLA
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v1.1.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
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
  $args['controller'] = 'co_themes';
  $args['action'] = 'index';
  $args['co'] = $cur_co['Co']['id'];
  $this->Html->addCrumb(_txt('ct.co_themes.pl'), $args);
  $crumbTxt = _txt('op.' . $this->action . '-a', array(_txt('ct.co_themes.1')));
  $this->Html->addCrumb($crumbTxt);
  
  print $this->Form->hidden('co_id', array('default' => $cur_co['Co']['id'])) . "\n";
?>
<table id="<?php print $this->action; ?>_co_themes" class="ui-widget">
  <tbody>
    <tr class="line1">
      <td>
        <strong class="fieldTitle">
          <?php print ($e ? $this->Form->label('name', _txt('fd.name')) : _txt('fd.name')); ?>
        </strong><span class="required">*</span>
      </td>
      <td>
        <?php print ($e
                     ? $this->Form->input('name', array('size' => '80','class' => 'focusFirst'))
                     : filter_var($co_themes[0]['CoTheme']['name'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
      </td>
    </tr>
    <tr class="line2">
      <td>
        <strong class="fieldTitle">
          <?php print ($e ? $this->Form->label('hide_title', _txt('fd.th.hide_title')) : _txt('fd.th.hide_title')); ?>
        </strong>
      </td>
      <td>
        <?php print ($e
                     ? $this->Form->input('hide_title')
                     : ($co_themes[0]['CoTheme']['hide_title'] ? _txt('fd.yes') : _txt('fd.no'))); ?>
      </td>
    </tr>
    <tr class="line1">
      <td>
        <strong class="fieldTitle">
          <?php print ($e ? $this->Form->label('hide_footer_logo', _txt('fd.th.hide_footer_logo')) : _txt('fd.th.hide_foooter')); ?>
        </strong>
      </td>
      <td>
        <?php print ($e
                     ? $this->Form->input('hide_footer_logo')
                     : ($co_themes[0]['CoTheme']['hide_footer_logo'] ? _txt('fd.yes') : _txt('fd.no'))); ?>
      </td>
    </tr>
    <tr class="line2">
      <td>
        <strong class="fieldTitle">
          <?php print ($e ? $this->Form->label('header', _txt('fd.th.header')) : _txt('fd.th.header')); ?>
        </strong>
      </td>
      <td>
        <?php print ($e
                     ? $this->Form->input('header')
                     : filter_var($co_themes[0]['CoTheme']['header'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
      </td>
    </tr>
    <tr class="line1">
      <td>
        <strong class="fieldTitle">
          <?php print ($e ? $this->Form->label('footer', _txt('fd.th.footer')) : _txt('fd.th.footer')); ?>
        </strong>
      </td>
      <td>
        <?php print ($e
                     ? $this->Form->input('footer')
                     : filter_var($co_themes[0]['CoTheme']['footer'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
      </td>
    </tr>
    <tr class="line2">
      <td>
        <strong class="fieldTitle">
          <?php print ($e ? $this->Form->label('css', _txt('fd.th.css')) : _txt('fd.th.css')); ?>
        </strong>
      </td>
      <td>
        <?php print ($e
                     ? $this->Form->input('css')
                     : filter_var($co_themes[0]['CoTheme']['css'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
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
