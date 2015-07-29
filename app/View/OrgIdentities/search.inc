<?php
/**
 * COmanage Registry OrgIdentity Search
 *
 * Copyright (C) 2011-15 University Corporation for Advanced Internet Development, Inc.
 * 
 * Licensed under the Apache License, Version 2.0 (the "License"); you may not use this file except in compliance with
 * the License. You may obtain a copy of the License at
 * 
 * http://www.apache.org/licenses/LICENSE-2.0
 * 
 * Unless required by applicable law or agreed to in writing, software distributed under
 * the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY
 * KIND, either express or implied. See the License for the specific language governing
 * permissions and limitations under the License.
 *
 * @copyright     Copyright (C) 2011-15 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.8.3
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
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

<div id="orgIdSearch" class="topSearch">
  <p><?php print _txt('op.search');?>:</p>
  <?php
  print $this->Form->create('OrgIdentity',array('action'=>'search'));
  print $this->Form->hidden('OrgIdentity.co_id', array('default' => $cur_co['Co']['id'])). "\n";
  ?>
  <?php
  $args = array();
  $args['label'] = _txt('fd.name.given');
  $args['placeholder'] = _txt('fd.name.given');
  $args['tabindex'] = '1';
  $args['value'] = !empty($this->request->params['named']['Search.givenName']) ? $this->request->params['named']['Search.givenName'] : '';
  print $this->Form->input('Search.givenName',$args);
  ?>
  <?php
  $args = array();
  $args['label'] = _txt('fd.name.family');
  $args['placeholder'] = _txt('fd.name.family');
  $args['tabindex'] = '2';
  $args['value'] = !empty($this->request->params['named']['Search.familyName']) ? $this->request->params['named']['Search.familyName'] : '';
  print $this->Form->input('Search.familyName',$args);
  ?>
  <?php
  $args = array();
  $args['label'] = _txt('fd.identifier.identifier');
  $args['placeholder'] = _txt('fd.identifier.identifier');
  $args['tabindex'] = '3';
  $args['value'] = !empty($this->request->params['named']['Search.identifier']) ? $this->request->params['named']['Search.identifier'] : '';
  print $this->Form->input('Search.identifier', $args);
  ?>
  <?php
  $args = array();
  $args['label'] = _txt('fd.o');
  $args['placeholder'] = _txt('fd.o');
  $args['tabindex'] = '4';
  $args['value'] = !empty($this->request->params['named']['Search.organization']) ? $this->request->params['named']['Search.organization'] : '';
  print $this->Form->input('Search.organization',$args);
  ?>
  <?php
  $args = array();
  $args['label'] = _txt('fd.ou');
  $args['placeholder'] = _txt('fd.ou');
  $args['tabindex'] = '5';
  $args['value'] = !empty($this->request->params['named']['Search.department']) ? $this->request->params['named']['Search.department'] : '';
  print $this->Form->input('Search.department',$args);
  ?>
  <?php
  $args = array();
  $args['label'] = _txt('fd.title');
  $args['placeholder'] = _txt('fd.title');
  $args['tabindex'] = '6';
  $args['value'] = !empty($this->request->params['named']['Search.title']) ? $this->request->params['named']['Search.title'] : '';
  print $this->Form->input('Search.title',$args);
  ?>
  <?php
  $searchOptions = $cm_texts[ $cm_lang ]['en.org_identity.affiliation'];
  $formParams = array('options' => $searchOptions,
    'type'    => 'select',
    'empty'   => _txt('fd.affiliation') . ' ' . _txt('op.select.empty'),
    'label'   => _txt('fd.affiliation'),
    'tabindex' => '7',
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
    $args['tabindex'] = '9';
    print $this->Form->button(_txt('op.clear.all'),$args);
    $args = array();
    $args['tabindex'] = '8';
    print $this->Form->submit(_txt('op.search'),$args);
    ?>
  </div>

  <?php print $this->Form->end();?>
</div>