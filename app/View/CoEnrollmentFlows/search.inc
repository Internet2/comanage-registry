<?php
/**
 * COmanage Registry CoEnrollmentFlow Search
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
 * @since         COmanage Registry v3.3
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

// Globals
global $cm_lang, $cm_texts;

?>

<div id="coEnrollmentFlowSearch" class="top-search">
  <?php
//  print $this->Form->create('CoEnrollmentFlows',array('url' => array('action'=>'search')));
//  print $this->Form->hidden('CoEnrollmentFlow.co_id', array('default' => $cur_co['Co']['id'])). "\n";
  print $this->Form->create('CoEnrollmentFlows', array('type' => 'post','url' => array('action'=>'search','co' => $cur_co['Co']['id'])));
  if(!empty($this->request->params['named']['copersonid'])) {
    print $this->Form->hidden('CoPerson.id', array('default' => filter_var($this->request->params['named']['copersonid'],FILTER_SANITIZE_SPECIAL_CHARS))). "\n";
  }
  if($permissions['select'] && $this->action == 'select') {
    print $this->Form->hidden('select', array('default' => 'true')). "\n";
  }
  ?>
  <fieldset>
    <legend id="top-search-toggle">
      <em class="material-icons">search</em>
      <?php print _txt('op.filter');?>
      <em class="material-icons drop-arrow">arrow_drop_down</em>
      
      <?php if(!empty($this->request->params['named']['search.eofName'])):?>
        
        <span id="top-search-active-filters">
         <?php if (!empty($this->request->params['named']['search.eofName'])): ?>
           <!-- The aria-controls is a name conventions. It is the named key:value used for filtering -->
           <!-- search.name => searchName, search.groupType => searchGroupType -->
           <button class="top-search-active-filter deletebutton spin" aria-controls="searchEofName" title="<?php print _txt('op.clear.filters.1');?>">
              <span class="top-search-active-filter-title"><?php print _txt('fd.name'); ?></span>
              <span class="top-search-active-filter-value"><?php print filter_var($this->request->params['named']['search.eofName'],FILTER_SANITIZE_SPECIAL_CHARS); ?></span>
           </button>
         <?php endif; ?>
         <button id="top-search-clear-all-button" class="filter-clear-all-button spin mdl-button" aria-controls="top-search-clear">
            <?php print _txt('op.clear.filters.pl');?>
         </button>
        </span>
      <?php endif; ?>
    </legend>
    
    <div id="top-search-fields">
      <div>
        <?php
        // Name:
        $formParams = array('label'   => _txt('fd.name'),
          'value'   => (!empty($this->request->params['named']['search.eofName']) ? $this->request->params['named']['search.eofName'] : ''));
        print $this->Form->input('search.eofName', $formParams);
        ?>
      </div>
      
      <div class="topSearchSubmit">
        <?php
        $args = array();
        // search button (submit)
        $args['aria-label'] = _txt('op.filter');
        $args['class'] = 'submit-button spin mdl-button mdl-js-button mdl-button--raised mdl-button--colored mdl-js-ripple-effect';
        print $this->Form->submit(_txt('op.filter'),$args);

        // clear button
        $args['id'] = 'top-search-clear';
        $args['class'] = 'clear-button spin mdl-button';
        $args['aria-label'] = _txt('op.clear');
        $args['onclick'] = 'clearTopSearch(this.form)';
        print $this->Form->button(_txt('op.clear'),$args);
        ?>
      </div>
    </div>
  </fieldset>
  
  <?php print $this->Form->end();?>
</div>