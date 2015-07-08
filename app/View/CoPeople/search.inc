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
  function clearAdvancedSearch(formObj) {
    for (var i=0; i<formObj.elements.length; i++) {
      t = formObj.elements[i].type;
      if(t == "text" || t == "select-one") {
        formObj.elements[i].value = "";
      }
    }
    formObj.submit();
  }

  $(function() {
    $( "#advancedSearch" ).accordion({
      collapsible: true
    });
    // Collapse search accordian on small devices (on page load)
    if (window.innerWidth < 992) {
      $( "#advancedSearch" ).accordion("option","active",false);
    }
    // Collapse or show accordian on window resize as well
    window.onresize = function () {
      if (window.innerWidth > 991) {
        $( "#advancedSearch" ).accordion("option","active",0);
      }
      if (window.innerWidth < 992) {
        $( "#advancedSearch" ).accordion("option","active",false);
      }
    }
  });
</script>

<div id="advancedSearch">
  <h3><?php print _txt('ct.co_people.se');?></h3>
  <div>
    <?php 
      print $this->Form->create('CoPerson',array('action'=>'search'));
      print $this->Form->hidden('CoPerson.co_id', array('default' => $cur_co['Co']['id'])). "\n";
    ?>
    <table>
      <tr>
        <td>
          <?php
            $args = array();
            $args['label'] = _txt('fd.name.given');
            $args['value'] = isset($this->request->params['named']['Search.givenName']) ? $this->request->params['named']['Search.givenName'] : '';
            print $this->Form->input('Search.givenName',$args);
          ?>
        </td>
      </tr>
      <tr>
        <td>
          <?php
            $args = array();
            $args['label'] = _txt('fd.name.family');
            $args['value'] = isset($this->request->params['named']['Search.familyName']) ? $this->request->params['named']['Search.familyName'] : '';
            print $this->Form->input('Search.familyName',$args);
          ?>
        </td>
      </tr>
      <tr>
        <td>
          <?php
            $args = array();
            $args['label'] = _txt('fd.identifier.identifier');
            $args['value'] = isset($this->request->params['named']['Search.identifier']) ? $this->request->params['named']['Search.identifier'] : '';
            print $this->Form->input('Search.identifier', $args);
          ?>
        </td>
      </tr>
      <tr>
        <td>
          <?php
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
                                'empty'   => ' ',
                                'label'   => _txt('fd.status'),
                                'value'   => (isset($this->request->params['named']['Search.status']) ? $this->request->params['named']['Search.status'] : ''));
            
            print $this->Form->input('Search.status', $formParams);
          ?>
        </td>
      </tr>
      <?php if(!empty($vv_cous)): ?>
      <tr>
        <td>
          <?php
            $formParams = array('options' => $vv_cous,
                                'type'    => 'select',
                                'empty'   => ' ',
                                'label'   => _txt('fd.cou'),
                                'value'   => (isset($this->request->params['named']['Search.couid']) ? $this->request->params['named']['Search.couid'] : ''));
            
            print $this->Form->input('Search.couid', $formParams);
          ?>
        </td>
      </tr>
      <?php endif; // cous ?>
      <tr>
        <td>
          <?php print $this->Form->submit(_txt('op.search')); ?>
          <button type="button" onclick="clearAdvancedSearch(this.form)" class="clearButton spin">
            <?php print _txt('op.clear.all');?>
          </button>
        </td>
      </tr>
    </table>

    <?php print $this->Form->end();?>

  </div>
</div>