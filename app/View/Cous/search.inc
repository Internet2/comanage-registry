<?php
  /**
   * COmanage Registry Cou Search
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

<div id="couSearch" class="top-search">
  <?php
    print $this->Form->create('Cou', array('type' => 'post','url' => array('action'=>'search','co' => $cur_co['Co']['id'])));
  ?>
  <fieldset>
    <legend id="top-search-toggle">
      <em class="material-icons">search</em>
      <?php print _txt('op.filter');?>

      <?php if(!empty($this->request->params['named']['search.couName'])
               || !empty($this->request->params['named']['search.couDesc'])
               || !empty($this->request->params['named']['search.parentCou'])): ?>

        <span id="top-search-active-filters">
          <?php if (!empty($this->request->params['named']['search.couName'])): ?>
            <button class="top-search-active-filter deletebutton spin" aria-controls="searchCouName" title="<?php print _txt('op.clear.filters.1');?>">
              <span class="top-search-active-filter-title"><?php print _txt('fd.name'); ?></span>
              <span class="top-search-active-filter-value"><?php print filter_var($this->request->params['named']['search.couName'],FILTER_SANITIZE_SPECIAL_CHARS); ?></span>
            </button>
          <?php endif; ?>
          <?php if (!empty($this->request->params['named']['search.couDesc'])): ?>
            <button class="top-search-active-filter deletebutton spin" aria-controls="searchCouDesc" title="<?php print _txt('op.clear.filters.1');?>">
              <span class="top-search-active-filter-title"><?php print _txt('fd.description'); ?></span>
              <span class="top-search-active-filter-value"><?php print filter_var($this->request->params['named']['search.couDesc'],FILTER_SANITIZE_SPECIAL_CHARS); ?></span>
            </button>
          <?php endif; ?>
          <?php if (!empty($this->request->params['named']['search.parentCou'])): ?>
            <button class="top-search-active-filter deletebutton spin" aria-controls="searchParentCou" title="<?php print _txt('op.clear.filters.1');?>">
              <span class="top-search-active-filter-title"><?php print _txt('fd.parent'); ?></span>
              <span class="top-search-active-filter-value">
                <?php
                foreach(array_keys($vv_cou_list) as $optgroup) {
                  if(isset($vv_cou_list[$optgroup][$this->request->params['named']['search.parentCou']])) {
                    print filter_var($vv_cou_list[$optgroup][$this->request->params['named']['search.parentCou']], FILTER_SANITIZE_SPECIAL_CHARS);
                    break;
                  }
                }
                if(isset($vv_cou_list[$this->request->params['named']['search.parentCou']])) {
                  print filter_var($vv_cou_list[$this->request->params['named']['search.parentCou']], FILTER_SANITIZE_SPECIAL_CHARS);;
                }
                ?>
              </span>
            </button>
          <?php endif; ?>
          <button id="top-search-clear-all-button" class="filter-clear-all-button spin btn" aria-controls="top-search-clear">
            <?php print _txt('op.clear.filters.pl');?>
          </button>
        </span>
      <?php endif; ?>
      <button class="cm-toggle" aria-expanded="false" aria-controls="top-search-fields" type="button"><em class="material-icons drop-arrow">arrow_drop_down</em></button>
    </legend>

    <div id="top-search-fields">
      <div class="search-field-subgroup">
        <?php
          // Name:
          $formParams = array('label'   => _txt('fd.name'),
            'value'   => (!empty($this->request->params['named']['search.couName']) ? $this->request->params['named']['search.couName'] : ''));
          print $this->Form->input('search.couName', $formParams);

          // Description:
          $formParams = array('label'   => _txt('fd.description'),
            'value'   => (!empty($this->request->params['named']['search.couDesc']) ? $this->request->params['named']['search.couDesc'] : ''));
          print $this->Form->input('search.couDesc', $formParams);
        ?>
      </div>

      <div class="search-field-subgroup">
        <?php
        // Parent COU:
        $formParams = array('options' => $vv_cou_list,
          'type'    => 'select',
          'label'   => _txt('fd.parent'),
          'empty'   => _txt('op.select.all'),
          'value'   => (!empty($this->request->params['named']['search.parentCou']) ? $this->request->params['named']['search.parentCou'] : ''));
        print $this->Form->input('search.parentCou', $formParams);
        ?>
      </div>

      <div class="top-search-submit">
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

  <?php print $this->Form->end(); ?>
  
</div>