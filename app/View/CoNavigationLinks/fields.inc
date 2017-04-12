<?php
/**
 * COmanage Registry CO Navigation Link Fields
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
  print $this->element("coCrumb");
  $args = array();
  $args['plugin'] = null;
  $args['controller'] = 'co_navigation_links';
  $args['action'] = 'index';
  $args['co'] = $cur_co['Co']['id'];
  $this->Html->addCrumb(_txt('ct.co_navigation_links.pl'), $args);
  $crumbTxt = _txt('op.' . $this->action . '-a', array(_txt('ct.co_navigation_links.1')));
  $this->Html->addCrumb($crumbTxt);
  
  print $this->Form->hidden('co_id', array('default' => $cur_co['Co']['id'])) . "\n";
?>

<ul id="<?php print $this->action; ?>_co_link" class="fields form-list">
    <li>
      <div class="field-name"> 
        <div class="field-title"> <?php print ($e ? $this->Form->label('description', _txt('fd.desc')) : _txt('fd.desc')); ?></div> 
      </div>
      <div class="field-info"> 
        <?php print ($e
                     ? $this->Form->input('description', array('class' => 'focusFirst'))
                     : filter_var($co_navigation_links[0]['CoNavigationLink']['description'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
      </div>
    </li>
    <li>
      <div class="field-name"> 
        <div class="field-title"> <?php print ($e ? $this->Form->label('title', _txt('fd.link.title')) : _txt('fd.link.title')); ?></div> 
      </div>
      <div class="field-info"> 
        <?php print ($e
                     ? $this->Form->input('title')
                     : filter_var($co_navigation_links[0]['CoNavigationLink']['title'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
      </div>
    </li>
    <li>
      <div class="field-name"> 
        <div class="field-title"> <?php print ($e ? $this->Form->label('url', _txt('fd.link.url')) : _txt('fd.link.url')); ?></div> 
      </div>
      <div class="field-info"> 
        <?php print ($e
                     ? $this->Form->input('url')
                     : filter_var($co_navigation_links[0]['CoNavigationLink']['url'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
      </div>
    </li>
    <li>
      <div class="field-name"> 
        <div class="field-title"> <?php print ($e ? $this->Form->label('ordr', _txt('fd.link.order')) : _txt('fd.link.order')); ?></div> 
      </div>
      <div class="field-info"> 
        <?php print ($e
                     ? $this->Form->input('ordr')
                     : filter_var($co_navigation_links[0]['CoNavigationLink']['ordr'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
      </div>
    </li>
    <li>
      <div class="field-name"> 
        <div class="field-title"> <?php print ($e ? $this->Form->label('location', _txt('fd.link.location')) : _txt('fd.link.location')); ?></div> 
      </div>
      <div class="field-info"> 
        <?php
          if($e) {
            if (!empty($this->viewVars['vv_co_link_location_options'] )) {
              // Print dropdown selector with location options
              print $this->Form->select('location', $this->viewVars['vv_co_link_location_options'], array('empty' => false));
            } 
          }
          else {
            print filter_var($co_navigation_links[0]['CoNavigationLink']['location'],FILTER_SANITIZE_SPECIAL_CHARS);
          }

          if($this->Form->isFieldError('status')) {
            print $this->Form->error('status');
          }
        ?>
      </div>
    </li>

    <?php if($e): ?>
      <li class="fields-submit">
        <div class="field-name"></div>
        <div class="field-info">
          <?php print $this->Form->submit($submit_label, array('onClick' => 'on_submit()')); ?>
        </div>
      </li>
    <?php endif; ?>
</ul>
