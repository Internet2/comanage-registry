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
 * @since         COmanage Registry v4.0.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

// Globals
global $cm_lang, $cm_texts;

?>

<div id="coEnrollmentFlow<?php print ucfirst($this->action); ?>Search" class="top-search">
  <?php
  print $this->Form->create('CoEnrollmentFlows', array('type' => 'post','url' => array('action'=>'search','co' => $cur_co['Co']['id'])));

  if($permissions['select'] && $this->action == 'select') {
    print $this->Form->hidden('RedirectAction.select', array('default' => 'true')). PHP_EOL;
  } elseif($permissions['index'] && $this->action == 'index') {
    print $this->Form->hidden('RedirectAction.index', array('default' => 'true')). PHP_EOL;
  }

  $search_params = array();
  if(isset($this->request->params['named'])) {
    foreach($this->request->params['named'] as $key => $params) {
      if(!empty($params) && $key !== 'co') {
        $search_params[$key] = $params;
      }
    }
  }

  ?>
  <fieldset>
    <legend id="top-search-toggle">
      <em class="material-icons">search</em>
      <?php print _txt('op.filter');?>

      <?php if(!empty($search_params)):?>
        <span id="top-search-active-filters">
          <?php foreach($search_params as $key => $params): ?>
            <?php
            // Construct aria-controls string
            $key_fields = explode('.', $key);
            $aria_controls = $key_fields[0] . ucfirst($key_fields[1]);
            ?>
            <button class="top-search-active-filter deletebutton spin" aria-controls="<?php print $aria_controls; ?>" title="<?php print _txt('op.clear.filters.1');?>">
               <span class="top-search-active-filter-title"><?php print $vv_search_fields[$key]['label']; ?></span>
               <span class="top-search-active-filter-value">
                 <?php
                 $value = $params;
                 if(isset($vv_search_fields[$key]['enum'])
                   && isset($cm_texts[ $cm_lang ][$vv_search_fields[$key]['enum']][$params])) {
                   $value = $cm_texts[ $cm_lang ][$vv_search_fields[$key]['enum']][$params];
                 }
                 print filter_var($value, FILTER_SANITIZE_SPECIAL_CHARS);
                 ?>
               </span>
            </button>
          <?php endforeach; ?>
         <button id="top-search-clear-all-button" class="filter-clear-all-button spin btn" aria-controls="top-search-clear">
            <?php print _txt('op.clear.filters.pl');?>
         </button>
        </span>
      <?php endif; ?>
      <button class="cm-toggle" aria-expanded="false" aria-controls="top-search-fields" type="button"><em class="material-icons drop-arrow">arrow_drop_down</em></button>
    </legend>

    <div id="top-search-fields">
      <?php
      $i = 0;
      $field_subgroup_columns = array();
      foreach($vv_search_fields as $key => $options) {
        $formParams = array(
          'label' => $options['label'],
          'type' => !empty($options['type']) ? $options['type'] : 'text',
          'value' => (!empty($this->request->params['named'][$key]) ? $this->request->params['named'][$key] : '')
        );
        if(isset($options['empty'])) {
          $formParams['empty'] = $options['empty'];
        }
        if(isset($options['options'])) {
          $formParams['options'] = $options['options'];
        }
        $idx = ($i % 2);
        $field_subgroup_columns[$idx][$key] = $this->Form->input($key, $formParams);
        $i++;
      }
      ?>
      <?php if(sizeof($field_subgroup_columns) == 1): ?>
        <div><?php print current(current($field_subgroup_columns)); ?></div>
      <?php else: ?>
        <div class="search-field-subgroup">
          <?php foreach($field_subgroup_columns[0] as $field_name => $finput): ?>
            <?php print $finput; ?>
          <?php endforeach; ?>
        </div>
        <div class="search-field-subgroup">
          <?php foreach($field_subgroup_columns[1] as $field_name => $finput): ?>
            <?php print $finput; ?>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <div class="topSearchSubmit">
        <?php
        $args = array();
        // search button (submit)
        $args['aria-label'] = _txt('op.filter');
        $args['class'] = 'submit-button spin btn btn-primary';
        print $this->Form->submit(_txt('op.filter'),$args);

        // clear button
        $args['id'] = 'top-search-clear';
        $args['class'] = 'clear-button spin btn btn-default';
        $args['aria-label'] = _txt('op.clear');
        $args['onclick'] = 'clearTopSearch(this.form)';
        print $this->Form->button(_txt('op.clear'),$args);
        ?>
      </div>
    </div>
  </fieldset>

  <?php print $this->Form->end();?>
</div>