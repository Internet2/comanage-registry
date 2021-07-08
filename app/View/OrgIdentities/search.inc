<?php
/**
 * COmanage Registry OrgIdentity Search
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

$tabIndex = 1;

?>

<div id="orgIdSearch" class="top-search top-search-hide-labels">
  <?php
    print $this->Form->create('OrgIdentity',array('url' => array('action'=>'search')));
    if(!empty($cur_co['Co']['id'])) {
      print $this->Form->hidden('OrgIdentity.co_id', array('default' => $cur_co['Co']['id'])). "\n";
    }
  ?>
  <fieldset>
    <legend id="top-search-toggle">
      <em class="material-icons">search</em>
      <?php print _txt('op.filter');?>

      <?php if(!empty($this->request->params['named']['search.givenName'])  ||
        !empty($this->request->params['named']['search.familyName'])        ||
        !empty($this->request->params['named']['search.mail'])              ||
        !empty($this->request->params['named']['search.identifier'])        ||
        !empty($this->request->params['named']['search.organization'])      ||
        !empty($this->request->params['named']['search.orgIdentitySource']) ||
        !empty($this->request->params['named']['search.department'])        ||
        !empty($this->request->params['named']['search.title'])             ||
        !empty($this->request->params['named']['search.affiliation'])):?>

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
          <?php if (!empty($this->request->params['named']['search.organization'])): ?>
            <button class="top-search-active-filter deletebutton spin" aria-controls="searchOrganization" title="<?php print _txt('op.clear.filters.1');?>">
              <span class="top-search-active-filter-title"><?php print _txt('fd.o'); ?></span>
              <span class="top-search-active-filter-value"><?php print filter_var($this->request->params['named']['search.organization'],FILTER_SANITIZE_SPECIAL_CHARS); ?></span>
            </button>
          <?php endif; ?>
          <?php if (!empty($this->request->params['named']['search.orgIdentitySource'])): ?>
            <button class="top-search-active-filter deletebutton spin" aria-controls="searchOrgIdentitySource" title="<?php print _txt('op.clear.filters.1');?>">
              <span class="top-search-active-filter-title"><?php print _txt('ct.org_identity_sources.1'); ?></span>
              <span class="top-search-active-filter-value"><?php print filter_var($vv_org_id_sources[$this->request->params['named']['search.orgIdentitySource']],FILTER_SANITIZE_SPECIAL_CHARS); ?></span>
            </button>
          <?php endif; ?>
          <?php if (!empty($this->request->params['named']['search.department'])): ?>
            <button class="top-search-active-filter deletebutton spin" aria-controls="searchDepartment" title="<?php print _txt('op.clear.filters.1');?>">
              <span class="top-search-active-filter-title"><?php print _txt('fd.ou'); ?></span>
              <span class="top-search-active-filter-value"><?php print filter_var($this->request->params['named']['search.department'],FILTER_SANITIZE_SPECIAL_CHARS); ?></span>
            </button>
          <?php endif; ?>
          <?php if (!empty($this->request->params['named']['search.title'])): ?>
            <button class="top-search-active-filter deletebutton spin" aria-controls="searchTitle" title="<?php print _txt('op.clear.filters.1');?>">
              <span class="top-search-active-filter-title"><?php print _txt('fd.title'); ?></span>
              <span class="top-search-active-filter-value"><?php print filter_var($this->request->params['named']['search.title'],FILTER_SANITIZE_SPECIAL_CHARS); ?></span>
            </button>
          <?php endif; ?>
          <?php if (!empty($this->request->params['named']['search.affiliation'])): ?>
            <button class="top-search-active-filter deletebutton spin" aria-controls="searchAffiliation" title="<?php print _txt('op.clear.filters.1');?>">
              <span class="top-search-active-filter-title"><?php print _txt('fd.affiliation'); ?></span>
              <span class="top-search-active-filter-value"><?php print filter_var($vv_affiliations[$this->request->params['named']['search.affiliation']],FILTER_SANITIZE_SPECIAL_CHARS); ?></span>
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
      $args = array();
      $args['label'] = _txt('fd.name.given');
      $args['placeholder'] = _txt('fd.name.given');
      $args['value'] = !empty($this->request->params['named']['search.givenName']) ? $this->request->params['named']['search.givenName'] : '';
      print $this->Form->input('search.givenName',$args);
      ?>
      <?php
      $args = array();
      $args['label'] = _txt('fd.name.family');
      $args['placeholder'] = _txt('fd.name.family');
      $args['value'] = !empty($this->request->params['named']['search.familyName']) ? $this->request->params['named']['search.familyName'] : '';
      print $this->Form->input('search.familyName',$args);
      ?>
      <?php
      $args = array();
      $args['label'] = _txt('fd.email_address.mail');
      $args['placeholder'] = _txt('fd.email_address.mail');
      $args['value'] = !empty($this->request->params['named']['search.mail']) ? $this->request->params['named']['search.mail'] : '';
      print $this->Form->input('search.mail', $args);
      ?>
      <?php
      $args = array();
      $args['label'] = _txt('fd.identifier.identifier');
      $args['placeholder'] = _txt('fd.identifier.identifier');
      $args['value'] = !empty($this->request->params['named']['search.identifier']) ? $this->request->params['named']['search.identifier'] : '';
      print $this->Form->input('search.identifier', $args);
      ?>
      </div>

      <div class="search-field-subgroup">
      <?php
      $args = array();
      $args['label'] = _txt('fd.o');
      $args['placeholder'] = _txt('fd.o');
      $args['value'] = !empty($this->request->params['named']['search.organization']) ? $this->request->params['named']['search.organization'] : '';
      print $this->Form->input('search.organization',$args);
      ?>
      <?php
      if(!empty($vv_org_id_sources)) {
        $formParams = array(
          'options' => $vv_org_id_sources,
          'type'    => 'select',
          'empty'   => _txt('op.select.empty-a', array(_txt('ct.org_identity_sources.1'))),
          'label'   => _txt('ct.org_identity_sources.1'),
          'value'   => (!empty($this->request->params['named']['search.orgIdentitySource'])
                        ? $this->request->params['named']['search.orgIdentitySource']
                        : ''));
        print $this->Form->input('search.orgIdentitySource', $formParams);
      }
      ?>
      <?php
      $args = array();
      $args['label'] = _txt('fd.ou');
      $args['placeholder'] = _txt('fd.ou');
      $args['value'] = !empty($this->request->params['named']['search.department']) ? $this->request->params['named']['search.department'] : '';
      print $this->Form->input('search.department',$args);
      ?>
      <?php
      $args = array();
      $args['label'] = _txt('fd.title');
      $args['placeholder'] = _txt('fd.title');
      $args['value'] = !empty($this->request->params['named']['search.title']) ? $this->request->params['named']['search.title'] : '';
      print $this->Form->input('search.title',$args);
      ?>
      <?php
      $searchOptions = $cm_texts[ $cm_lang ]['en.org_identity.affiliation'];
      $formParams = array('options' => $searchOptions,
        'type'    => 'select',
        'empty'   => _txt('fd.affiliation') . ' ' . _txt('op.select.empty'),
        'label'   => _txt('fd.affiliation'),
        'value'   => (!empty($this->request->params['named']['search.affiliation']) ? $this->request->params['named']['search.affiliation'] : ''));
      print $this->Form->input('search.affiliation', $formParams);
      ?>
      </div>

      <div class="top-search-submit">
        <?php
        $args = array();
        //$args['type'] = 'button';

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