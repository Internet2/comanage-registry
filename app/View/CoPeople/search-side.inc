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

<div id="coPeopleSimpleSearch" class="side-search">
  <div class="accHeader">
    <em class="material-icons">search</em>
    <?php print _txt('fd.co_people.search');?>
  </div>
  <?php
    print $this->Form->create('CoPerson', array('url' => array('action'=>'search')));
    print $this->Form->hidden('CoPerson.co_id', array('default' => $cur_co['Co']['id'],'id'=>'SearchCoPersonCoId')). "\n";

    $args = array();
    $args['label'] = _txt('fd.name.given');
    $args['placeholder'] = _txt('fd.name.given');
    $args['value'] = !empty($this->request->params['named']['Search.givenName']) ? $this->request->params['named']['Search.givenName'] : '';
    print $this->Form->input('Search.givenName',$args);

    $args = array();
    $args['label'] = _txt('fd.name.family');
    $args['placeholder'] = _txt('fd.name.family');
    $args['value'] = !empty($this->request->params['named']['Search.familyName']) ? $this->request->params['named']['Search.familyName'] : '';
    print $this->Form->input('Search.familyName',$args);

    $args = array();
    $args['label'] = _txt('fd.identifier.identifier');
    $args['placeholder'] = _txt('fd.identifier.identifier');
    $args['value'] = !empty($this->request->params['named']['Search.identifier']) ? $this->request->params['named']['Search.identifier'] : '';
    print $this->Form->input('Search.identifier', $args);

    // This needs to pull StatusEnum values based on the model (CO-1007)
    $statusOptions = array(StatusEnum::Active,
                          StatusEnum::Approved,
                          StatusEnum::Declined,
                          StatusEnum::Deleted,
                          StatusEnum::Denied,
                          StatusEnum::Invited,
                          StatusEnum::Pending,
                          StatusEnum::PendingApproval,
                          StatusEnum::PendingConfirmation,
                          StatusEnum::Suspended);

    foreach ($statusOptions as $s) {
      $searchOptions[ $s ] = $cm_texts[ $cm_lang ]['en.status'][ $s ];
    }

    $formParams = array('options' => $searchOptions,
                        'type'    => 'select',
                        'empty'   => _txt('fd.status') . ' ' . _txt('op.select.empty'),
                        'label'   => _txt('fd.status'),
                        'value'   => (!empty($this->request->params['named']['Search.status']) ? $this->request->params['named']['Search.status'] : ''));

    print $this->Form->input('Search.status', $formParams);
    if(!empty($vv_cous)) {
      $formParams = array('options' => $vv_cous,
        'type' => 'select',
        'empty' => _txt('fd.cou') . ' ' . _txt('op.select.empty'),
        'label' => _txt('fd.cou'),
        'value' => (!empty($this->request->params['named']['Search.couid']) ? $this->request->params['named']['Search.couid'] : ''));

      print $this->Form->input('Search.couid', $formParams);
    } // cous

    //$this->Form->submit(_txt('op.search'));
    print '<div class="sideSearchSubmit">';
    $args = array();
    $args['aria-label'] = _txt('op.search');
    $args['class'] = 'submit-button spin mdl-button mdl-js-button mdl-button--raised mdl-button--colored mdl-js-ripple-effect';
    print $this->Form->submit(_txt('op.search'),$args);
    print '</div>';

    print $this->Form->end();
  ?>

</div>