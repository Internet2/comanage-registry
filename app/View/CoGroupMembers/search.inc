<?php
/**
 * COmanage Registry CoPerson Search
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
 * @since         COmanage Registry v3.3.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

// Globals
global $cm_lang, $cm_texts;

?>

<div id="coPeopleSearch" class="top-search top-search-hide-labels">
  <?php
    print $this->Form->create('CoGroupMember',array('url' => array('action'=>'search')));
    print $this->Form->hidden('co_id', array('default' => $cur_co['Co']['id'])). "\n";
    if(!empty($this->request->params['named']['cogroup'])) {
      print $this->Form->hidden('cogroup', array('default' => $this->request->params['named']['cogroup'])) . "\n";
    }
  ?>
  <fieldset>
    <legend id="top-search-toggle">
      <em class="material-icons">search</em>
      <?php print _txt('op.filter');?>

      <?php if(!empty($this->request->params['named']['search.givenName'])  ||
        !empty($this->request->params['named']['search.familyName']) ||
        !empty($this->request->params['named']['search.mail'])       ||
        !empty($this->request->params['named']['search.identifier']) ||
        !empty($this->request->params['named']['search.status'])     ||
        !empty($this->request->params['named']['search.members'])    ||
        !empty($this->request->params['named']['search.owners'])):?>

        <span id="top-search-active-filters">
          <?php if (!empty($this->request->params['named']['search.givenName'])): ?>
            <button class="top-search-active-filter deletebutton spin" aria-controls="searchGivenName" title="<?php print _txt('op.clear.filters.1');?>">
              <span class="top-search-active-filter-title"><?php print _txt('fd.name.given'); ?></span>
              <span class="top-search-active-filter-value"><?php print filter_var($this->request->params['named']['search.givenName'],FILTER_SANITIZE_SPECIAL_CHARS); ?></span>
            </button>
          <?php endif; ?>
          <?php if (!empty($this->request->params['named']['search.familyName'])): ?>
            <button class="top-search-active-filter deletebutton spin" aria-controls="searchFamilyName" title="<?php print _txt('op.clear.filters.1');?>">
              <span class="top-search-active-filter-title"><?php print _txt('fd.name.family'); ?></span>
              <span class="top-search-active-filter-value"><?php print filter_var($this->request->params['named']['search.familyName'],FILTER_SANITIZE_SPECIAL_CHARS); ?></span>
            </button>
          <?php endif; ?>
          <?php if (!empty($this->request->params['named']['search.mail'])): ?>
            <button class="top-search-active-filter deletebutton spin" aria-controls="searchMail" title="<?php print _txt('op.clear.filters.1');?>">
              <span class="top-search-active-filter-title"><?php print _txt('fd.email_address.mail'); ?></span>
              <span class="top-search-active-filter-value"><?php print filter_var($this->request->params['named']['search.mail'],FILTER_SANITIZE_SPECIAL_CHARS); ?></span>
            </button>
          <?php endif; ?>
          <?php if (!empty($this->request->params['named']['search.identifier'])): ?>
            <button class="top-search-active-filter deletebutton spin" aria-controls="searchIdentifier" title="<?php print _txt('op.clear.filters.1');?>">
              <span class="top-search-active-filter-title"><?php print _txt('fd.identifier.identifier'); ?></span>
              <span class="top-search-active-filter-value"><?php print filter_var($this->request->params['named']['search.identifier'],FILTER_SANITIZE_SPECIAL_CHARS); ?></span>
            </button>
          <?php endif; ?>
          <?php if (!empty($this->request->params['named']['search.status'])): ?>
            <button class="top-search-active-filter deletebutton spin" aria-controls="searchStatus" title="<?php print _txt('op.clear.filters.1');?>">
              <span class="top-search-active-filter-title"><?php print _txt('fd.status'); ?></span>
              <span class="top-search-active-filter-value"><?php print filter_var($vv_statuses[$this->request->params['named']['search.status']],FILTER_SANITIZE_SPECIAL_CHARS); ?></span>
            </button>
          <?php endif; ?>
          <?php if (!empty($this->request->params['named']['search.members'])): ?>
            <button class="top-search-active-filter deletebutton spin" aria-controls="searchMembers" title="<?php print _txt('op.clear.filters.1');?>">
              <span class="top-search-active-filter-title no-value"><?php print _txt('fd.members'); ?></span>
            </button>
          <?php endif; ?>
          <?php if (!empty($this->request->params['named']['search.owners'])): ?>
            <button class="top-search-active-filter deletebutton spin" aria-controls="searchOwners" title="<?php print _txt('op.clear.filters.1');?>">
              <span class="top-search-active-filter-title no-value"><?php print _txt('fd.owners'); ?></span>
            </button>
          <?php endif; ?>
          <button id="top-search-clear-all-button" class="filter-clear-all-button spin mdl-button" aria-controls="top-search-clear">
            <?php print _txt('op.clear.filters.pl');?>
          </button>
        </span>
      <?php endif; ?>
      <button class="cm-toggle" aria-expanded="false" aria-controls="top-search-fields" type="button"><em class="material-icons drop-arrow">arrow_drop_down</em></button>
    </legend>

    <div id="top-search-fields">
      <div class="search-field-subgroup">
      <?php
      $args = array();
      $args['label'] = _txt('fd.name.given');
      $args['placeholder'] = _txt('fd.name.given');
      $args['aria-label'] = _txt('fd.name.given');
      $args['value'] = !empty($this->request->params['named']['search.givenName']) ? $this->request->params['named']['search.givenName'] : '';
      print $this->Form->input('search.givenName',$args);

      $args = array();
      $args['label'] = _txt('fd.name.family');
      $args['placeholder'] = _txt('fd.name.family');
      $args['aria-label'] = _txt('fd.name.family');
      $args['value'] = !empty($this->request->params['named']['search.familyName']) ? $this->request->params['named']['search.familyName'] : '';
      print $this->Form->input('search.familyName',$args);

      $args = array();
      $args['label'] = _txt('fd.email_address.mail');
      $args['placeholder'] = _txt('fd.email_address.mail');
      $args['aria-label'] = _txt('fd.email_address.mail');
      $args['value'] = !empty($this->request->params['named']['search.mail']) ? $this->request->params['named']['search.mail'] : '';
      print $this->Form->input('search.mail', $args);
      ?>
      </div>

      <div class="search-field-subgroup">
        <?php
        $args = array();
        $args['label'] = _txt('fd.identifier.identifier');
        $args['placeholder'] = _txt('fd.identifier.identifier');
        $args['aria-label'] = _txt('fd.identifier.identifier');
        $args['value'] = !empty($this->request->params['named']['search.identifier']) ? $this->request->params['named']['search.identifier'] : '';
        print $this->Form->input('search.identifier', $args);

        if (!empty($vv_statuses)) {
          $formParams = array('options' => $vv_statuses,
            'type' => 'select',
            'empty' => ' ',
            'label' => _txt('fd.status'),
            'aria-label' => _txt('fd.status'),
            'empty' => _txt('fd.status') . ' ' . _txt('op.select.empty'),
            'value' => (!empty($this->request->params['named']['search.status']) ? $this->request->params['named']['search.status'] : ''));
          print $this->Form->input('search.status', $formParams);
        }

        ?>

        <div class="top-search-checkboxes">
          <div class="top-search-checkbox-label"><?php print _txt('fd.membership'); ?></div>
          <?php
          print $this->Form->checkbox('search.members', array('checked' => !empty($this->request->params['named']['search.members'])));
          print $this->Form->label('search.members',_txt('fd.members')) . "\n";
          print $this->Form->checkbox('search.owners', array('checked' => !empty($this->request->params['named']['search.owners'])));
          print $this->Form->label('search.owners', _txt('fd.owners')) . "\n";
          ?>
        </div>
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

  <?php print $this->Form->end();?>
</div>