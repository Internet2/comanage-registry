<?php
  /**
   * COmanage Registry CoGroupMember Search
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
      if(t == "text" || t == "select-one" ) {
        formObj.elements[i].value = "";
      }
      if(t == "checkbox"){
        formObj.elements[i].checked = false;
      }
    }
    formObj.submit();
  }

  $(function() {
    var searchFieldsAreOpen = (Cookies.get('comanage-top-search-fields-visible') === 'true');
    if (searchFieldsAreOpen) {
      $('#coGroupsSearch').addClass('open');
      $('#top-search-fields').show();
    }

    $( "#top-search-toggle").click(function() {
      $("#top-search-fields").toggle();
      var searchFieldsAreOpen = $("#top-search-fields").is(':visible');
      Cookies.set('comanage-top-search-fields-visible', searchFieldsAreOpen);
      if (searchFieldsAreOpen) {
        $("#coGroupsSearch").addClass('open');
      } else {
        $("#coGroupsSearch").removeClass('open');
      }
    });
  });
</script>

<div id="coGroupsSearch" class="top-search">
  <?php
    print $this->Form->create('CoGroup',
      array('url' => array('action'=>'search'),
        'id'  => 'form_search'));
    print $this->Form->hidden('CoGroup.co_id', array('default' => $cur_co['Co']['id'])). "\n";
  ?>
  <fieldset>
    <legend>
      <a href="#" id="top-search-toggle">
        <em class="material-icons">search</em>
        <?php print _txt('op.filter');?>
        <em class="material-icons drop-arrow">arrow_drop_down</em>
      </a>
    </legend>

    <div id="top-search-fields">
      <div class="search-field-subgroup">
        <?php
          $args = array();
          $args['label'] = _txt('fd.name');
          $args['placeholder'] = _txt('fd.name');
          //$args['class'] = 'mdl-textfield__input';
          $args['aria-label'] = _txt('fd.name');
          // XXX shouldn't these fields be sanitized?
          $args['value'] = !empty($this->request->params['named']['Search.name']) ? $this->request->params['named']['Search.name'] : '';
          print $this->Form->input('Search.name',$args);
        ?>
      </div>

      <div class="search-field-subgroup">
        <?php
          $args = array();
          $args['label'] = _txt('fd.desc');
          $args['placeholder'] = _txt('fd.desc');
          //$args['class'] = 'mdl-textfield__input';
          $args['aria-label'] = _txt('fd.desc');
          // XXX shouldn't these fields be sanitized?
          $args['value'] = !empty($this->request->params['named']['Search.desc']) ? $this->request->params['named']['Search.desc'] : '';
          print $this->Form->input('Search.desc',$args);
        ?>
      </div>

      <div class="topSearchSubmit">
        <?php
          $args = array();
          //$args['type'] = 'button';

          // search button (submit)
          $args['aria-label'] = _txt('op.filter');
          $args['class'] = 'submit-button spin mdl-button mdl-js-button mdl-button--raised mdl-button--colored mdl-js-ripple-effect';
          print $this->Form->submit(_txt('op.filter'),$args);

          // clear button
          $args['class'] = 'clear-button spin mdl-button';
          $args['aria-label'] = _txt('op.clear.all');
          $args['onclick'] = 'clearSearch(this.form)';
          print $this->Form->button(_txt('op.clear.all'),$args);
        ?>
      </div>
    </div>
  </fieldset>
  <?php print $this->Form->end();?>
</div>