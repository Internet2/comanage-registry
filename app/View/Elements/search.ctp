<?php
/**
 * COmanage Registry Search Element
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
// Get a pointer to our model
$controller = $this->name;
$req = Inflector::singularize($controller);
$controller_route_name = Inflector::underscore($controller);
$formArgs =  array(
  'type' => 'post',
  'url' => array(
    'action'=>'search',
    'co' => $cur_co['Co']['id'])
);

// If i have named parameters not related to search fields
// get them and add them into the form. But always skip "page":
// a newly submitted search should always land on page one. 
if(!empty($this->request->params['named'])) {
  $attributes = array_diff_key($this->request->params['named'], $vv_search_fields);
  foreach($attributes as $attr_name => $attr_value) {
    if($attr_name !== 'page') {
      $formArgs['url'][$attr_name] = $attr_value;
    }
  }
}

print $this->Form->create($req,$formArgs);

// List of search fields
$search_fields = array_keys($vv_search_fields);

// Boolean to distinguish between search filters and sort parameters
$hasActiveFilters = false;

?>

<div id="<?php print $req . ucfirst($this->request->action); ?>Search" class="top-search" aria-label="<?php print _txt('me.menu.filters'); ?>">
  <?php

  // Action
  print $this->Form->hidden('RedirectAction.' . $this->request->action, array('default' => 'true')). PHP_EOL;

  // Named parameters
  // Discard op.search named param and 'page'
  $search_params = array();
  if(isset($this->request->params['named'])) {
    foreach ($this->request->params['named'] as $param => $value) {
      if(strpos($param, 'search') === false
         && $param !== "op"
         && $param !== "page") {
        print $this->Form->hidden($this->request->action . '.named.' . $param, array('default' => filter_var($value, FILTER_SANITIZE_SPECIAL_CHARS))) . "\n";
      } else {
        $search_params[$param] = $value;
      }
    }
  }

  // Passed parameters
  foreach ($this->request->params['pass'] as $idx => $value) {
    print $this->Form->hidden($this->request->action . '.'. $idx . '.pass', array('default' => filter_var($value,FILTER_SANITIZE_SPECIAL_CHARS))). "\n";
  }

  ?>
  <fieldset onclick="event.stopPropagation();" aria-label="<?php print _txt('me.menu.filters.form'); ?>">
    <legend id="top-search-toggle">
      <em class="material-icons" aria-hidden="true">search</em>
      <?php print _txt('op.filter');?>

      <?php if(!empty($search_params)):?>
        <span id="top-search-active-filters">
          <?php foreach($search_params as $key => $params): ?>
            <?php
              if(!in_array($key, $search_fields)) {
                continue;
              }
              // Construct aria-controls string
              $key_fields = explode('.', $key);
              $aria_controls = $key_fields[0] . ucfirst($key_fields[1]);

              // We have named filters - not just a sort.
              $hasActiveFilters = true;
            ?>
            <button class="top-search-active-filter deletebutton spin"
                    type="button" aria-controls="<?php print $aria_controls; ?>"
                    title="<?php print _txt('op.clear.filters.1');?>">
               <span class="top-search-active-filter-title<?php print !is_null(filter_var(urldecode($params), FILTER_VALIDATE_BOOLEAN,FILTER_NULL_ON_FAILURE)) ? " no-value" : "" ?>">
                 <?php print $vv_search_fields[$key]['label']; ?>
               </span>
               <?php if(is_null(filter_var(urldecode($params), FILTER_VALIDATE_BOOLEAN,FILTER_NULL_ON_FAILURE))):?>
               <span class="top-search-active-filter-value">
                 <?php
                 $value = $params;
                 // Get user friendly name from an Enumerator Class
                 // XXX How should we handle dynamic Enumerator lists?
                 if(isset($vv_search_fields[$key]['enum'])
                    && isset($cm_texts[ $cm_lang ][$vv_search_fields[$key]['enum']][$params])) {
                   $value = $cm_texts[ $cm_lang ][$vv_search_fields[$key]['enum']][$params];
                   print filter_var($value, FILTER_SANITIZE_SPECIAL_CHARS);
                   continue;
                 }
                 // Get user friendly name from the dropdown Select List
                 // XXX Currently we do not have a use case where the grouping name would create a namespace
                 if (isset($vv_search_fields[$key]['options'])) {
                   // Outside any groups
                   if (isset($vv_search_fields[$key]['options'][$value])) {
                     print filter_var($vv_search_fields[$key]['options'][$value], FILTER_SANITIZE_SPECIAL_CHARS);
                   } else {
                     // Inside a group
                     foreach(array_keys($vv_search_fields[$key]['options']) as $optgroup) {
                       if( is_array($vv_search_fields[$key]['options'][$optgroup])
                           && isset($vv_search_fields[$key]['options'][$optgroup][$value]) ) {
                         print filter_var($vv_search_fields[$key]['options'][$optgroup][$value], FILTER_SANITIZE_SPECIAL_CHARS);
                         print $this->Html->tag('span','(' . $optgroup . ')', array('class' => 'ml-1') );
                         break;
                       }
                     }
                   }
                 } else {
                   print filter_var(urldecode($value), FILTER_SANITIZE_SPECIAL_CHARS);
                 }
                 ?>
               </span>
               <?php endif; ?>
            </button>
          <?php endforeach; ?>
          <?php if($hasActiveFilters): ?>
             <button id="top-search-clear-all-button" class="filter-clear-all-button spin btn" type="button" aria-controls="top-search-clear" onclick="event.stopPropagation()">
                <?php print _txt('op.clear.filters.pl');?>
             </button>
          <?php endif; ?>
        </span>
      <?php endif; ?>
      <button class="cm-toggle" aria-expanded="false" aria-controls="top-search-fields" type="button"  aria-label="<?php print _txt('me.menu.filters.open'); ?>">
        <em class="material-icons drop-arrow" aria-hidden="true">arrow_drop_down</em>
      </button>
    </legend>

    <div id="top-search-fields">
      <?php
      $i = 0;
      $field_subgroup_columns = array();
      $field_checkbox_columns = array();
      foreach($vv_search_fields as $key => $options) {
        if(strpos($key, 'search') === false) {
          continue;
        }
        if($options['type'] == 'checkbox') {
          $checkBoxformParams = array(
            'checked' => !empty($this->request->params['named'][$key]),
            'class' => 'form-check-input',
          );

          $field_checkbox_columns[$options['column']][ $options['group'] ][$key] = array(
            $this->Form->label($key, $options['label']),
            $this->Form->checkbox($key, $checkBoxformParams)
          );
          $i++;
          continue;
        }

        $formParams = array(
          'label' => $options['label'],
          'aria-label' => $options['label'],
          'type' => !empty($options['type']) ? $options['type'] : 'text',
          'value' => (!empty($this->request->params['named'][$key]) ? urldecode($this->request->params['named'][$key]) : ''),
          'required' => false,
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
        <div id="top-search-fields-subgroups" class="mb-2">
          <div class="search-field-subgroup">
            <!--     No checkboxes      -->
            <?php foreach($field_subgroup_columns[0] as $field_name => $finput): ?>
              <?php print $finput; ?>
            <?php endforeach; ?>

            <!--     Checkboxes      -->
            <?php if(!empty($field_checkbox_columns[0])): ?>
            <div class="top-search-checkboxes input">
              <?php foreach($field_checkbox_columns[0] as $group => $fcheckboxes): ?>
              <div class="top-search-checkbox-fields">
                <?php foreach($fcheckboxes as $fcheckbox): ?>
                  <div class="form-check form-check-inline">
                  <?php
                  [$label, $checkbox] = $fcheckbox;
                  print $checkbox;
                  print $label;
                  ?>
                </div>
                <?php endforeach; ?>
              </div>
              <?php endforeach; ?>
            </div>
            <?php endif; ?>

          </div>
          <div class="search-field-subgroup">
            <!--     No checkboxes      -->
            <?php foreach($field_subgroup_columns[1] as $field_name => $finput): ?>
              <?php print $finput; ?>
            <?php endforeach; ?>

            <!--     Checkboxes      -->
            <?php if(!empty($field_checkbox_columns[1])): ?>
            <div class="top-search-checkboxes input">
              <?php foreach($field_checkbox_columns[1] as $group => $fcheckboxes): ?>
                <div class="top-search-checkbox-fields">
                  <?php foreach($fcheckboxes as $fcheckbox): ?>
                    <div class="form-check form-check-inline">
                      <?php
                      [$label, $checkbox] = $fcheckbox;
                      print $checkbox;
                      print $label;
                      ?>
                    </div>
                  <?php endforeach; ?>
                </div>
              <?php endforeach; ?>
            </div>
            <?php endif; ?>

          </div>
        </div>
      <?php endif; ?>

      <?php $rebalanceColumns = ($i > 1) && ($i % 2 != 0) ? ' class="tss-rebalance"' : ''; ?>
      <div id="top-search-submit"<?php print $rebalanceColumns ?>>
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
</div>

  <?php if(!empty($vv_search_fields['qaxsbar'])): ?>
<div id="filter-block-qaxsbar" class="listControl" aria-label="<?php print _txt('me.filter.field', array($vv_search_fields['qaxsbar']['label'])); ?>">
  <span class="qaxsbar-col-name"><?php print $vv_search_fields['qaxsbar']['label']; ?></span>
  <ul>
    <?php
    $args = array();
    $args['controller'] = $controller_route_name;
    $args['action'] = $this->request->action;
    if($this->request->action == 'index') {
      $args['co'] = $cur_co['Co']['id'];
    } else if(!empty($this->request->params['pass'][0])) {
      $args[] = $this->request->params['pass'][0];
    }
    // Merge (propagate) all prior search criteria, except status and page
    $args = array_merge($args, $this->request->params['named']);
    unset($args['search.' . $vv_search_fields['qaxsbar']['field']], $args['page']);
    $field_search = array();
    $field_search_key = 'search.' . $vv_search_fields['qaxsbar']['field'];
    if(!empty($this->request->params['named'][$field_search_key])
       && is_array($this->request->params['named'][$field_search_key]) ) {
      $field_search = $this->request->params['named']['search.' . $vv_search_fields['qaxsbar']['field']];
    }
    $index = 0;
    foreach($vv_search_fields['qaxsbar']['enum'] as $code => $enum_val) {
      $selected = in_array($code, $field_search, true) ? true : false;
      $args = array(
        'label' => $enum_val,
        'aria-label' => $enum_val,
        'type' => $vv_search_fields['qaxsbar']['type'],
        'class' => 'mr-2',
        'value' => $code,
        'div' => false,
      );
      if($vv_search_fields['qaxsbar']['type'] === 'checkbox') {
        $args['checked'] = $selected;
      }
      print '<li class="field-values">';
      print $this->Form->input('search.' . $vv_search_fields['qaxsbar']['field'] . '.' . $index, $args);
      print '</li>';
      $index++;
    }
    ?>
  </ul>
</div>
  <?php endif; ?>
  <?php print $this->Form->end();?>