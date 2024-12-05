<?php
/**
 * COmanage Registry Global Search Element
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
 * @since         COmanage Registry v4.5.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */
?>
<div id="global-search">
  <?php
    $options = array(
      'type' => 'get',
      'url' => array(
        'plugin' => null,
        'action' => 'search'
      ),
      'id' => 'co-global-search-form'
    );
    print $this->Form->create('CoDashboard', $options);
  ?>
  
  <div id="global-search-box">
  <?php
    // output the label, visible only to screen readers
    $options = array(
      'for' => 'global-search-field'
    );
    print $this->Form->label('q', '<span class="sr-only">' . _txt('op.search.global') . '</span>', $options);
    
    // output the search field
    $options = array(
      'label' => false,
      'id' => 'global-search-field'
    );
    print $this->Form->input('q', $options);
    
    // output the submit button
    $options = array(
      'type' => 'submit',
      'class' => 'btn btn-primary btn-sm global-search-submit',
      'escape' => false
    );
    $submitButtonText = '<span class="material-icons-outlined">search</span><span class="search-button-text">' . _txt('op.search') . '</span>';
    print $this->Form->button($submitButtonText, $options);
    
    print $this->Form->hidden('co', array('default' => $cur_co['Co']['id']));
  ?>
  </div>
  
  <?php
    print $this->Form->end();
  ?>
</div>