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

?>

<script type="text/javascript">
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
  <h3><?php print _txt('ct.org_identities.fi');?></h3>
  <div>
    <?php 
      print $this->Form->create('OrgIdentity',array('url' => array('action'=>'search')));
      print $this->Form->hidden('OrgIdentity.co_id', array('default' => $cur_co['Co']['id'])). "\n";
    ?>
    <table>
      <tr>
        <td>
          <?php
            $args = array();
            $args['label'] = _txt('fd.name.given');
            $args['value'] = !empty($this->request->params['named']['search.givenName']) ? $this->request->params['named']['search.givenName'] : '';
            print $this->Form->input('search.givenName',$args);
          ?>
        </td>
      </tr>
      <tr>
        <td>
          <?php
            $args = array();
            $args['label'] = _txt('fd.name.family');
            $args['value'] = !empty($this->request->params['named']['search.familyName']) ? $this->request->params['named']['search.familyName'] : '';
            print $this->Form->input('search.familyName',$args);
          ?>
        </td>
      </tr>
      <tr>
        <td>
          <?php
            $args = array();
            $args['label'] = _txt('fd.identifier.identifier');
            $args['value'] = !empty($this->request->params['named']['search.identifier']) ? $this->request->params['named']['search.identifier'] : '';
            print $this->Form->input('search.identifier', $args);
          ?>
        </td>
      </tr>
      <tr>
        <td>
          <?php
            $args = array();
            $args['label'] = _txt('fd.o');
            $args['value'] = !empty($this->request->params['named']['search.organization']) ? $this->request->params['named']['search.organization'] : '';
            print $this->Form->input('search.organization',$args);
          ?>
        </td>
      </tr>
      <tr>
        <td>
          <?php
            $args = array();
            $args['label'] = _txt('fd.ou');
            $args['value'] = !empty($this->request->params['named']['search.department']) ? $this->request->params['named']['search.department'] : '';
            print $this->Form->input('search.department',$args);
          ?>
        </td>
      </tr>
      <tr>
        <td>
          <?php
            $args = array();
            $args['label'] = _txt('fd.title');
            $args['value'] = !empty($this->request->params['named']['search.title']) ? $this->request->params['named']['search.title'] : '';
            print $this->Form->input('search.title',$args);
          ?>
        </td>
      </tr>
      <tr>
        <td>
          <?php
            $searchOptions = $cm_texts[ $cm_lang ]['en.org_identity.affiliation'];
            $formParams = array('options' => $searchOptions,
                                'type'    => 'select',
                                'empty'   => '',
                                'label'   => _txt('fd.affiliation'),
                                'value'   => (!empty($this->request->params['named']['search.affiliation']) ? $this->request->params['named']['search.affiliation'] : ''));
            print $this->Form->input('search.affiliation', $formParams);
          ?>
        </td>
      </tr>
      <tr>
        <td>
          <?php print $this->Form->submit(_txt('op.filter')); ?>
        </td>
      </tr>
    </table>

    <?php print $this->Form->end();?>
  </div>
</div>