<?php
/**
 * COmanage Registry OrgIdentity Search
 *
 * Copyright (C) 2011-13 University Corporation for Advanced Internet Development, Inc.
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
 * @copyright     Copyright (C) 2011-13 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.8.3
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */

// Globals
global $cm_lang, $cm_texts;
?>

<script>
  $(function() {
    $( "#advancedSearch" ).accordion({
      collapsible: true,
      active     : false
    });
  });
</script>

<div id="advancedSearch">
  <h3><?php print _txt('ct.org_identities.se');?></h3>
  <div>
    <?php 
      print $this->Form->create('OrgIdentity',array('action'=>'search'));
      print $this->Form->hidden('OrgIdentity.co_id', array('default' => $cur_co['Co']['id'])). "\n";
    ?>
    <table>
      <tr>
        <td>
          <?php print $this->Form->input('Search.givenName'); ?>
        </td>
      </tr>
      <tr>
        <td>
          <?php print $this->Form->input('Search.familyName'); ?>
        </td>
      </tr>
      <tr>
        <td>
          <?php print $this->Form->input('Search.organization'); ?>
        </td>
      </tr>
      <tr>
        <td>
          <?php print $this->Form->input('Search.department'); ?>
        </td>
      </tr>
      <tr>
        <td>
          <?php print $this->Form->input('Search.title'); ?>
        </td>
      </tr>
      <tr>
        <td>
          <?php
            $searchOptions = $cm_texts[ $cm_lang ]['en.affil'];
            $formParams = array('options' => $searchOptions,
                                'type'    => 'select',
                                'empty'   => '',
                                'label'   => 'Affiliation');
            print $this->Form->input('Search.affiliation', $formParams);
          ?>
        </td>
      </tr>
      <tr>
        <td>
          <?php print $this->Form->submit('Search'); ?>
        </td>
      </tr>
    </table>

    <?php print $this->Form->end();?>
  </div>
</div>