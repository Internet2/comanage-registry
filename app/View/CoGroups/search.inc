<?php
  /**
   * COmanage Registry CoGroup Search
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

<div id="coGroupSearch" class="top-search">
  <?php
    print $this->Form->create('CoGroups', array('type' => 'post','url' => array('action'=>'search','co' => $cur_co['Co']['id'])));
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

      <?php if(!empty($this->request->params['named']['search.groupName'])  ||
        !empty($this->request->params['named']['search.groupDesc']) ||
        !empty($this->request->params['named']['search.status'])       ||
        !empty($this->request->params['named']['search.open']) ||
        !empty($this->request->params['named']['search.auto'])     ||
        !empty($this->request->params['named']['search.groupType'])):?>

        <span id="top-search-active-filters">
          <?php if (!empty($this->request->params['named']['search.groupName'])): ?>
            <button class="top-search-active-filter deletebutton spin" aria-controls="searchGroupName" title="<?php print _txt('op.clear.filters.1');?>">
              <span class="top-search-active-filter-title"><?php print _txt('fd.name'); ?></span>
              <span class="top-search-active-filter-value"><?php print filter_var($this->request->params['named']['search.groupName'],FILTER_SANITIZE_SPECIAL_CHARS); ?></span>
            </button>
          <?php endif; ?>
          <?php if (!empty($this->request->params['named']['search.groupDesc'])): ?>
            <button class="top-search-active-filter deletebutton spin" aria-controls="searcGrouphDesc" title="<?php print _txt('op.clear.filters.1');?>">
              <span class="top-search-active-filter-title"><?php print _txt('fd.description'); ?></span>
              <span class="top-search-active-filter-value"><?php print filter_var($this->request->params['named']['search.groupDesc'],FILTER_SANITIZE_SPECIAL_CHARS); ?></span>
            </button>
          <?php endif; ?>
          <?php if (!empty($this->request->params['named']['search.status'])): ?>
            <button class="top-search-active-filter deletebutton spin" aria-controls="searchStatus" title="<?php print _txt('op.clear.filters.1');?>">
              <span class="top-search-active-filter-title"><?php print _txt('fd.status'); ?></span>
              <span class="top-search-active-filter-value"><?php print filter_var($vv_statuses[$this->request->params['named']['search.status']],FILTER_SANITIZE_SPECIAL_CHARS); ?></span>
            </button>
          <?php endif; ?>
          <?php if (!empty($this->request->params['named']['search.open'])): ?>
            <button class="top-search-active-filter deletebutton spin" aria-controls="searchOpen" title="<?php print _txt('op.clear.filters.1');?>">
              <span class="top-search-active-filter-value"><?php print filter_var($vv_status_open[$this->request->params['named']['search.open']],FILTER_SANITIZE_SPECIAL_CHARS); ?></span>
            </button>
          <?php endif; ?>
          <?php if (!empty($this->request->params['named']['search.auto'])): ?>
            <button class="top-search-active-filter deletebutton spin" aria-controls="searchAuto" title="<?php print _txt('op.clear.filters.1');?>">
              <span class="top-search-active-filter-title"><?php print _txt('fd.automatic'); ?></span>
              <span class="top-search-active-filter-value"><?php print filter_var($vv_status_bool[$this->request->params['named']['search.auto']],FILTER_SANITIZE_SPECIAL_CHARS); ?></span>
            </button>
          <?php endif; ?>
          <?php if (!empty($this->request->params['named']['search.groupType'])): ?>
            <button class="top-search-active-filter deletebutton spin" aria-controls="searchGroupType" title="<?php print _txt('op.clear.filters.1');?>">
              <span class="top-search-active-filter-title"><?php print _txt('fd.type'); ?></span>
              <span class="top-search-active-filter-value"><?php print filter_var($vv_group_type[$this->request->params['named']['search.groupType']],FILTER_SANITIZE_SPECIAL_CHARS); ?></span>
            </button>
          <?php endif; ?>
          <button id="top-search-clear-all-button" class="filter-clear-all-button spin mdl-button" aria-controls="top-search-clear">
            <?php print _txt('op.clear.filters.pl');?>
          </button>
        </span>
      <?php endif; ?>
    </legend>

    <div id="top-search-fields">
      <div class="search-field-subgroup">
        <?php
          // Name:
          $formParams = array('label'   => _txt('fd.name'),
            'value'   => (!empty($this->request->params['named']['search.groupName']) ? $this->request->params['named']['search.groupName'] : ''));
          print $this->Form->input('search.groupName', $formParams);

          // Description:
          $formParams = array('label'   => _txt('fd.description'),
            'value'   => (!empty($this->request->params['named']['search.groupDesc']) ? $this->request->params['named']['search.groupDesc'] : ''));
          print $this->Form->input('search.groupDesc', $formParams);

          // Status:
          $formParams = array('options' => $vv_statuses,
            'type'    => 'select',
            'label'   => _txt('fd.status'),
            'empty'   => _txt('op.select.all'),
            'value'   => (!empty($this->request->params['named']['search.status']) ? $this->request->params['named']['search.status'] : ''));
          print $this->Form->input('search.status', $formParams);
        ?>
      </div>

      <div class="search-field-subgroup">
        <?php
          // Open/Closed:
          $formParams = array('options' => $vv_status_open,
            'type'    => 'select',
            'label'   => _txt('fd.open'),
            'empty'   => _txt('op.select.all'),
            'value'   => (!empty($this->request->params['named']['search.open']) ? $this->request->params['named']['search.open'] : ''));
          print $this->Form->input('search.open', $formParams);

          // Managed automatically / manually
          $formParams = array('options' => $vv_status_bool,
            'type'    => 'select',
            'label'   => _txt('fd.automatic'),
            'empty'   => _txt('op.select.all'),
            'value'   => (!empty($this->request->params['named']['search.auto']) ? $this->request->params['named']['search.auto'] : ''));
          print $this->Form->input('search.auto', $formParams);

          // Group type
          $formParams = array('options' => $vv_group_type,
            'type'    => 'select',
            'label'   => _txt('fd.type'),
            'empty'   => _txt('op.select.all'),
            'value'   => (!empty($this->request->params['named']['search.groupType']) ? $this->request->params['named']['search.groupType'] : ''));
          print $this->Form->input('search.groupType', $formParams);
        ?>
      </div>

      <div class="top-search-submit">
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

  <?php print $this->Form->end(); ?>
  
</div>