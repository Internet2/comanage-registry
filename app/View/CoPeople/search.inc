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

<div id="coPeopleSearch" class="topSearch">
  <?php
  print $this->Form->create('CoPerson',array('url' => array('action'=>'search')));
  print $this->Form->hidden('CoPerson.co_id', array('default' => $cur_co['Co']['id'])). "\n";
  ?>
  <fieldset>
    <legend><?php print _txt('op.search');?>:</legend>

    <?php
    $args = array();
    $args['label'] = _txt('fd.name.given');
    $args['placeholder'] = _txt('fd.name.given');
    $args['aria-label'] = _txt('fd.name.given');
    $args['tabindex'] = '1';
// XXX shouldn't these fields be sanitized?
    $args['value'] = !empty($this->request->params['named']['Search.givenName']) ? $this->request->params['named']['Search.givenName'] : '';
    print $this->Form->input('Search.givenName',$args);

    $args = array();
    $args['label'] = _txt('fd.name.family');
    $args['placeholder'] = _txt('fd.name.family');
    $args['aria-label'] = _txt('fd.name.family');
    $args['tabindex'] = '2';
    $args['value'] = !empty($this->request->params['named']['Search.familyName']) ? $this->request->params['named']['Search.familyName'] : '';
    print $this->Form->input('Search.familyName',$args);

    $args = array();
    $args['label'] = _txt('fd.email_address.mail');
    $args['placeholder'] = _txt('fd.email_address.mail');
    $args['aria-label'] = _txt('fd.email_address.mail');
    $args['tabindex'] = '3';
    $args['value'] = !empty($this->request->params['named']['Search.mail']) ? $this->request->params['named']['Search.mail'] : '';
    print $this->Form->input('Search.mail', $args);

    $args = array();
    $args['label'] = _txt('fd.identifier.identifier');
    $args['placeholder'] = _txt('fd.identifier.identifier');
    $args['aria-label'] = _txt('fd.identifier.identifier');
    $args['tabindex'] = '4';
    $args['value'] = !empty($this->request->params['named']['Search.identifier']) ? $this->request->params['named']['Search.identifier'] : '';
    print $this->Form->input('Search.identifier', $args);

    $formParams = array('options' => $vv_statuses,
      'type'    => 'select',
      'empty'   => ' ',
      'label'   => _txt('fd.status'),
      'aria-label'   => _txt('fd.status'),
      'empty'   => _txt('fd.status') . ' ' . _txt('op.select.empty'),
      'onchange' => 'this.form.submit()',
      'tabindex' => '5',
      'value'   => (!empty($this->request->params['named']['Search.status']) ? $this->request->params['named']['Search.status'] : ''));

    print $this->Form->input('Search.status', $formParams);

    if (!empty($vv_cous)) {
      $formParams = array('options' => $vv_cous,
        'type' => 'select',
        'empty' => ' ',
        'label' => _txt('fd.cou'),
        'aria-label' => _txt('fd.cou'),
        'empty' => _txt('fd.cou') . ' ' . _txt('op.select.empty'),
        'onchange' => 'this.form.submit()',
        'tabindex' => '6',
        'value' => (!empty($this->request->params['named']['Search.couid']) ? $this->request->params['named']['Search.couid'] : ''));

      print $this->Form->input('Search.couid', $formParams);
    }
    ?>

    <div class="topSearchSubmit">
      <?php
      $args = array();
      $args['type'] = 'button';
      $args['class'] = 'clearButton spin';
      $args['aria-label'] = _txt('op.clear.all');
      $args['onclick'] = 'clearSearch(this.form)';
      $args['tabindex'] = '8';
      print $this->Form->button(_txt('op.clear.all'),$args);
      $args = array();
      $args['tabindex'] = '7';
      $args['aria-label'] = _txt('op.search');
      print $this->Form->submit(_txt('op.search'),$args);
      ?>
    </div>
  </fieldset>
  <?php print $this->Form->end();?>
</div>