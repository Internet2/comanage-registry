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
    $( ".clearButton").button();
  });
</script>

<div id="orgIdSearch" class="topSearch">
  <?php
    print $this->Form->create('OrgIdentity',array('url' => array('action'=>'search')));
    if(!empty($cur_co['Co']['id'])) {
      print $this->Form->hidden('OrgIdentity.co_id', array('default' => $cur_co['Co']['id'])). "\n";
    }
  ?>
  <fieldset>
  <legend><?php print _txt('op.search');?>:</legend>
  <?php
  $args = array();
  $args['label'] = _txt('fd.name.given');
  $args['placeholder'] = _txt('fd.name.given');
  $args['tabindex'] = $tabIndex++;
  $args['value'] = !empty($this->request->params['named']['Search.givenName']) ? $this->request->params['named']['Search.givenName'] : '';
  print $this->Form->input('Search.givenName',$args);
  ?>
  <?php
  $args = array();
  $args['label'] = _txt('fd.name.family');
  $args['placeholder'] = _txt('fd.name.family');
  $args['tabindex'] = $tabIndex++;
  $args['value'] = !empty($this->request->params['named']['Search.familyName']) ? $this->request->params['named']['Search.familyName'] : '';
  print $this->Form->input('Search.familyName',$args);
  ?>
  <?php
  $args = array();
  $args['label'] = _txt('fd.email_address.mail');
  $args['placeholder'] = _txt('fd.email_address.mail');
  $args['tabindex'] = $tabIndex++;
  $args['value'] = !empty($this->request->params['named']['Search.mail']) ? $this->request->params['named']['Search.mail'] : '';
  print $this->Form->input('Search.mail', $args);
  ?>
  <?php
  $args = array();
  $args['label'] = _txt('fd.identifier.identifier');
  $args['placeholder'] = _txt('fd.identifier.identifier');
  $args['tabindex'] = $tabIndex++;
  $args['value'] = !empty($this->request->params['named']['Search.identifier']) ? $this->request->params['named']['Search.identifier'] : '';
  print $this->Form->input('Search.identifier', $args);
  ?>
  <?php
  $args = array();
  $args['label'] = _txt('fd.o');
  $args['placeholder'] = _txt('fd.o');
  $args['tabindex'] = $tabIndex++;
  $args['value'] = !empty($this->request->params['named']['Search.organization']) ? $this->request->params['named']['Search.organization'] : '';
  print $this->Form->input('Search.organization',$args);
  ?>
  <?php
  if(!empty($vv_org_id_sources)) {
    $formParams = array(
      'options' => $vv_org_id_sources,
      'type'    => 'select',
      'empty'   => _txt('op.select.empty-a', array(_txt('ct.org_identity_sources.1'))),
      'label'   => _txt('ct.org_identity_sources.1'),
      'tabindex' => $tabIndex++,
      'onchange' => 'this.form.submit()',
      'value'   => (!empty($this->request->params['named']['Search.orgIdentitySource'])
                    ? $this->request->params['named']['Search.orgIdentitySource']
                    : ''));
    print $this->Form->input('Search.orgIdentitySource', $formParams);
  }
  ?>
  <?php
  $args = array();
  $args['label'] = _txt('fd.ou');
  $args['placeholder'] = _txt('fd.ou');
  $args['tabindex'] = $tabIndex++;
  $args['value'] = !empty($this->request->params['named']['Search.department']) ? $this->request->params['named']['Search.department'] : '';
  print $this->Form->input('Search.department',$args);
  ?>
  <?php
  $args = array();
  $args['label'] = _txt('fd.title');
  $args['placeholder'] = _txt('fd.title');
  $args['tabindex'] = $tabIndex++;
  $args['value'] = !empty($this->request->params['named']['Search.title']) ? $this->request->params['named']['Search.title'] : '';
  print $this->Form->input('Search.title',$args);
  ?>
  <?php
  $searchOptions = $cm_texts[ $cm_lang ]['en.org_identity.affiliation'];
  $formParams = array('options' => $searchOptions,
    'type'    => 'select',
    'empty'   => _txt('fd.affiliation') . ' ' . _txt('op.select.empty'),
    'label'   => _txt('fd.affiliation'),
    'tabindex' => $tabIndex++,
    'onchange' => 'this.form.submit()',
    'value'   => (!empty($this->request->params['named']['Search.affiliation']) ? $this->request->params['named']['Search.affiliation'] : ''));
  print $this->Form->input('Search.affiliation', $formParams);
  ?>

  <div class="topSearchSubmit">
    <?php
    $args = array();
    $args['type'] = 'button';
    $args['class'] = 'clearButton spin';
    $args['onclick'] = 'clearSearch(this.form)';
    // We need to be clever with $tabIndex to put these in the correct order
    $args['tabindex'] = $tabIndex+1;
    print $this->Form->button(_txt('op.clear.all'),$args);
    $args = array();
    $args['tabindex'] = $tabIndex;
    print $this->Form->submit(_txt('op.search'),$args);
    $tabIndex += 2;
    ?>
  </div>
  </fieldset>
  <?php print $this->Form->end();?>
</div>