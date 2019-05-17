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
 * @since         COmanage Registry v0.8.3
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

// Globals
global $cm_lang, $cm_texts;

?>

<script type="text/javascript">
  function clearSearch(formObj) {
    for (var i=0; i<formObj.elements.length; i++) {
      t = formObj.elements[i].type;
      if(t == "text" || t == "select-one") {
        formObj.elements[i].value = "";
      }
    }
    formObj.submit();
  }

  $(function() {
    $( "#top-search-toggle").click(function() {
      $("#top-search-fields").toggle();
    });
  });
</script>

<div id="coEnrollmentFlowSearch" class="top-search">
  <?php
    print $this->Form->create('CoEnrollmentFlow',array('url' => array('action'=>'search')));
    print $this->Form->hidden('CoEnrollmentFlow.co_id', array('default' => $cur_co['Co']['id'])). "\n";
  ?>
  <fieldset>
    <legend id="top-search-toggle">
      <em class="material-icons">search</em>
      <?php print _txt('op.filter');?>
      <em class="material-icons drop-arrow">arrow_drop_down</em>

      <?php if(!empty($this->request->params['named']['Search.name'])):?>

        <span id="top-search-active-filters">
         <?php if (!empty($this->request->params['named']['Search.name'])): ?>
           <span class="top-search-active-filter">
             <span class="top-search-active-filter-title"><?php print _txt('fd.name'); ?></span>
             <span class="top-search-active-filter-value"><?php print filter_var($this->request->params['named']['Search.name'],FILTER_SANITIZE_SPECIAL_CHARS); ?></span>
           </span>
         <?php endif; ?>
        </span>
      <?php endif; ?>
    </legend>

    <div id="top-search-fields">
      <div>
      <?php
      $args = array();
      // search button (submit)
      $args['label'] = _txt('fd.name');
      $args['placeholder'] = _txt('fd.name');
      $args['aria-label'] = _txt('fd.name');
      // XXX shouldn't these fields be sanitized?
      $args['value'] = !empty($this->request->params['named']['Search.name']) ? $this->request->params['named']['Search.name'] : '';
      print $this->Form->input('Search.name',$args);
      ?>
      </div>

      <div class="topSearchSubmit">
        <?php
        $args = array();
        $args['aria-label'] = _txt('op.filter');
        $args['class'] = 'submit-button spin mdl-button mdl-js-button mdl-button--raised mdl-button--colored mdl-js-ripple-effect';
        print $this->Form->submit(_txt('op.filter'),$args);

        // clear button
        $args['class'] = 'clear-button spin mdl-button';
        $args['aria-label'] = _txt('op.clear.all');
        $args['onclick'] = 'clearSearch(this.form)';
        print $this->Form->button(_txt('op.clear.all'),$args);
        ?>
      </div>
    </div>
  </fieldset>

  <?php print $this->Form->end();?>
</div>