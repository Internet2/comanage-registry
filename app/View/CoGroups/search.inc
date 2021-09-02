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

      <?php if(!empty($this->request->params['named']['search.groupName'])  ||
        !empty($this->request->params['named']['search.groupDesc']) ||
        !empty($this->request->params['named']['search.status'])    ||
        !empty($this->request->params['named']['search.open'])      ||
        !empty($this->request->params['named']['search.auto'])      ||
        !empty($this->request->params['named']['search.group_type']) ||
        !empty($this->request->params['named']['search.member']) ||
        !empty($this->request->params['named']['search.owner']) ||
        !empty($this->request->params['named']['search.noadmin'])):?>

        <?php $hasFilters = true; ?>

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
          <?php if (!empty($this->request->params['named']['search.group_type'])): ?>
            <button class="top-search-active-filter deletebutton spin" aria-controls="searchGroupType" title="<?php print _txt('op.clear.filters.1');?>">
              <span class="top-search-active-filter-title"><?php print _txt('fd.type'); ?></span>
              <span class="top-search-active-filter-value"><?php print filter_var($vv_group_type[$this->request->params['named']['search.group_type']],FILTER_SANITIZE_SPECIAL_CHARS); ?></span>
            </button>
          <?php endif; ?>
          <?php if (!empty($this->request->params['named']['search.member'])): ?>
            <button class="top-search-active-filter deletebutton spin" aria-controls="searchMember" title="<?php print _txt('op.clear.filters.1');?>">
              <span class="top-search-active-filter-title no-value">
                <?php
                  if($this->action == 'select') {
                    print _txt('fd.co_group_member.member');
                  } else {
                    print _txt('fd.co_group.my_memberships');
                  }
                ?>
              </span>
            </button>
          <?php endif; ?>
          <?php if (!empty($this->request->params['named']['search.owner'])): ?>
            <button class="top-search-active-filter deletebutton spin" aria-controls="searchOwner" title="<?php print _txt('op.clear.filters.1');?>">
              <span class="top-search-active-filter-title no-value">
                <?php
                  if($this->action == 'select') {
                    print _txt('fd.co_group_member.owner');
                  } else {
                    print _txt('fd.co_group.my_ownerships');
                  }
                ?>
              </span>
            </button>
          <?php endif; ?>
          <?php if (!empty($this->request->params['named']['search.noadmin'])): ?>
            <button class="top-search-active-filter deletebutton spin" aria-controls="searchNoadmin" title="<?php print _txt('op.clear.filters.1');?>">
              <span class="top-search-active-filter-title no-value"><?php print _txt('fd.co_group.no_admin'); ?></span>
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
      <div id="top-search-fields-subgroups">
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

          <div class="top-search-checkboxes input">
            <div class="top-search-checkbox-label"><?php print _txt('fd.membership'); ?></div>
            <div class="top-search-checkbox-fields">
              <div class="form-check form-check-inline">
                <?php
                  print $this->Form->checkbox('search.member', array('checked' => !empty($this->request->params['named']['search.member']), 'class' => 'form-check-input'));
                  if($this->action == 'select') {
                    print $this->Form->label('search.member',_txt('fd.co_group_member.member')) . "\n";
                  } else {
                    print $this->Form->label('search.member',_txt('fd.co_group.my_memberships')) . "\n";
                  }
                ?>
              </div>
              <div class="form-check form-check-inline">
                <?php
                  print $this->Form->checkbox('search.owner', array('checked' => !empty($this->request->params['named']['search.owner']), 'class' => 'form-check-input'));
                  if($this->action == 'select') {
                    print $this->Form->label('search.owner',_txt('fd.co_group_member.owner')) . "\n";
                  } else {
                    print $this->Form->label('search.owner', _txt('fd.co_group.my_ownerships')) . "\n";
                  }
                ?>
              </div>
            </div>
          </div>
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
              'value'   => (!empty($this->request->params['named']['search.group_type']) ? $this->request->params['named']['search.group_type'] : ''));
            print $this->Form->input('search.group_type', $formParams);
          ?>

          <div class="top-search-checkboxes input">
            <div class="top-search-checkbox-label"><?php print _txt('fd.co_group.admin'); ?></div>
            <div class="top-search-checkbox-fields">
              <div class="form-check form-check-inline">
                <?php
                  print $this->Form->checkbox('search.noadmin', array('checked' => !empty($this->request->params['named']['search.noadmin']), 'class' => 'form-check-input'));
                  print $this->Form->label('search.noadmin',_txt('fd.co_group.no_admin')) . "\n";
                ?>
              </div>
            </div>
          </div>
        </div>
      </div>  

      <div id="top-search-submit">
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
          $args['type'] = 'button';
          print $this->Form->button(_txt('op.clear'),$args);
        ?>
      </div>
    </div>
  </fieldset>

  <?php print $this->Form->end(); ?>
  
</div>