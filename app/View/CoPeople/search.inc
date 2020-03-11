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
 * @since         COmanage Registry v0.8.3
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

// Globals
global $cm_lang, $cm_texts;

?>

<div id="coPeopleSearch" class="top-search top-search-hide-labels">
  <?php
    print $this->Form->create('CoPerson',array('url' => array('action'=>'search')));
    print $this->Form->hidden('CoPerson.co_id', array('default' => $cur_co['Co']['id'])). "\n";
    if(!empty($this->request->params['named']['copetitionid'])) {
      print $this->Form->hidden('CoPetition.id', array('default' => filter_var($this->request->params['named']['copetitionid'],FILTER_SANITIZE_SPECIAL_CHARS))). "\n";
    }
    print $this->Form->hidden('currentAction', array('default' => filter_var($this->request->action,FILTER_SANITIZE_SPECIAL_CHARS)));
    if ($this->request->action == 'relink') {
      // pass the relink parameters through the search
      if(!empty($vv_co_org_identity_link['CoOrgIdentityLink'])) {
        print $this->Form->hidden('Relink.id', array('default' => $vv_co_org_identity_link['CoOrgIdentityLink']['co_person_id']));
        print $this->Form->hidden('Relink.orgidlinkid', array('default' => $vv_co_org_identity_link['CoOrgIdentityLink']['id']));
      } elseif(!empty($vv_co_person_role['CoPersonRole'])) {
        print $this->Form->hidden('Relink.id', array('default' => $vv_co_person_role['CoPersonRole']['co_person_id']));
        print $this->Form->hidden('Relink.roleid', array('default' => $vv_co_person_role['CoPersonRole']['id']));
      }
    }
  ?>
  <fieldset>
    <legend id="top-search-toggle">
      <em class="material-icons">search</em>
      <?php print _txt('op.filter');?>

      <?php if(!empty($this->request->params['named']['Search.givenName'])  ||
        !empty($this->request->params['named']['Search.familyName']) ||
        !empty($this->request->params['named']['Search.mail'])       ||
        !empty($this->request->params['named']['Search.identifier']) ||
        !empty($this->request->params['named']['Search.status'])     ||
        !empty($this->request->params['named']['Search.couid'])):?>

        <span id="top-search-active-filters">
          <?php if (!empty($this->request->params['named']['Search.givenName'])): ?>
            <button class="top-search-active-filter deletebutton spin" aria-controls="SearchGivenName" title="<?php print _txt('op.clear.filters.1');?>">
              <span class="top-search-active-filter-title"><?php print _txt('fd.name.given'); ?></span>
              <span class="top-search-active-filter-value"><?php print filter_var($this->request->params['named']['Search.givenName'],FILTER_SANITIZE_SPECIAL_CHARS); ?></span>
            </button>
          <?php endif; ?>
          <?php if (!empty($this->request->params['named']['Search.familyName'])): ?>
            <button class="top-search-active-filter deletebutton spin" aria-controls="SearchFamilyName" title="<?php print _txt('op.clear.filters.1');?>">
              <span class="top-search-active-filter-title"><?php print _txt('fd.name.family'); ?></span>
              <span class="top-search-active-filter-value"><?php print filter_var($this->request->params['named']['Search.familyName'],FILTER_SANITIZE_SPECIAL_CHARS); ?></span>
            </button>
          <?php endif; ?>
          <?php if (!empty($this->request->params['named']['Search.mail'])): ?>
            <button class="top-search-active-filter deletebutton spin" aria-controls="SearchMail" title="<?php print _txt('op.clear.filters.1');?>">
              <span class="top-search-active-filter-title"><?php print _txt('fd.email_address.mail'); ?></span>
              <span class="top-search-active-filter-value"><?php print filter_var($this->request->params['named']['Search.mail'],FILTER_SANITIZE_SPECIAL_CHARS); ?></span>
            </button>
          <?php endif; ?>
          <?php if (!empty($this->request->params['named']['Search.identifier'])): ?>
            <button class="top-search-active-filter deletebutton spin" aria-controls="SearchIdentifier" title="<?php print _txt('op.clear.filters.1');?>">
              <span class="top-search-active-filter-title"><?php print _txt('fd.identifier.identifier'); ?></span>
              <span class="top-search-active-filter-value"><?php print filter_var($this->request->params['named']['Search.identifier'],FILTER_SANITIZE_SPECIAL_CHARS); ?></span>
            </button>
          <?php endif; ?>
          <?php if (!empty($this->request->params['named']['Search.status'])): ?>
            <button class="top-search-active-filter deletebutton spin" aria-controls="SearchStatus" title="<?php print _txt('op.clear.filters.1');?>">
              <span class="top-search-active-filter-title"><?php print _txt('fd.status'); ?></span>
              <span class="top-search-active-filter-value"><?php print filter_var($vv_statuses[$this->request->params['named']['Search.status']],FILTER_SANITIZE_SPECIAL_CHARS); ?></span>
            </button>
          <?php endif; ?>
          <?php if (!empty($this->request->params['named']['Search.couid'])): ?>
            <button class="top-search-active-filter deletebutton spin" aria-controls="SearchCouid" title="<?php print _txt('op.clear.filters.1');?>">
              <span class="top-search-active-filter-title"><?php print _txt('fd.cou'); ?></span>
              <span class="top-search-active-filter-value"><?php print filter_var($vv_cous[$this->request->params['named']['Search.couid']],FILTER_SANITIZE_SPECIAL_CHARS); ?></span>
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
      // XXX shouldn't these fields be sanitized?
      $args['value'] = !empty($this->request->params['named']['Search.givenName']) ? $this->request->params['named']['Search.givenName'] : '';
      print $this->Form->input('Search.givenName',$args);

      $args = array();
      $args['label'] = _txt('fd.name.family');
      $args['placeholder'] = _txt('fd.name.family');
      $args['aria-label'] = _txt('fd.name.family');
      $args['value'] = !empty($this->request->params['named']['Search.familyName']) ? $this->request->params['named']['Search.familyName'] : '';
      print $this->Form->input('Search.familyName',$args);

      $args = array();
      $args['label'] = _txt('fd.email_address.mail');
      $args['placeholder'] = _txt('fd.email_address.mail');
      $args['aria-label'] = _txt('fd.email_address.mail');
      $args['value'] = !empty($this->request->params['named']['Search.mail']) ? $this->request->params['named']['Search.mail'] : '';
      print $this->Form->input('Search.mail', $args);
      ?>
      </div>

      <div class="search-field-subgroup">
      <?php
      $args = array();
      $args['label'] = _txt('fd.identifier.identifier');
      $args['placeholder'] = _txt('fd.identifier.identifier');
      $args['aria-label'] = _txt('fd.identifier.identifier');
      $args['value'] = !empty($this->request->params['named']['Search.identifier']) ? $this->request->params['named']['Search.identifier'] : '';
      print $this->Form->input('Search.identifier', $args);

      $formParams = array('options' => $vv_statuses,
        'type'    => 'select',
        'empty'   => ' ',
        'label'   => _txt('fd.status'),
        'aria-label'   => _txt('fd.status'),
        'empty'   => _txt('fd.status') . ' ' . _txt('op.select.empty'),
        'value'   => (!empty($this->request->params['named']['Search.status']) ? $this->request->params['named']['Search.status'] : ''));

      print $this->Form->input('Search.status', $formParams);

      if (!empty($vv_cous)) {
        $formParams = array('options' => $vv_cous,
          'type' => 'select',
          'empty' => ' ',
          'label' => _txt('fd.cou'),
          'aria-label' => _txt('fd.cou'),
          'empty' => _txt('fd.cou') . ' ' . _txt('op.select.empty'),
          'value' => (!empty($this->request->params['named']['Search.couid']) ? $this->request->params['named']['Search.couid'] : ''));

        print $this->Form->input('Search.couid', $formParams);
      }
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

  <?php print $this->Form->end();?>
</div>