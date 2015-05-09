<?php
/**
 * COmanage Registry CO Petition PetitionerAttributes View
 *
 * Copyright (C) 2015 University Corporation for Advanced Internet Development, Inc.
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
 * @copyright     Copyright (C) 2015 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.9.4
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */
?>
<script type="text/javascript">
var givenNameAttr = "";
var familyNameAttr = "";
  
$(document).ready(function() {
  $("input.matchable").keyup(function(event) {
    if(event.which != 13) {
      // 13 is enter/return... don't search on form submit
      // XXX Don't hardcode fields here, or /registry prefix
      $.ajax({
        url: '/registry/co_people/match/coef:' + <?php print Sanitize::html($co_enrollment_flow_id); ?>
             + '/given:' + document.getElementById(givenNameAttr).value
             + '/family:' + document.getElementById(familyNameAttr).value
      }).done(function(data) {
        $('div#results').html(data);
      });
    }
  });
});
</script>
<?php
  $params = array('title' => $title_for_layout);

  print $this->element("enrollmentCrumbs");
  print $this->element("pageTitle", $params);

  $submit_label = _txt('op.add');
  
  print $this->Form->create(
    'CoPetition',
    array(
      'action' => 'petitionerAttributes',
      'inputDefaults' => array(
        'label' => false,
        'div' => false
      )
    )
  );
  
  if(!empty($vv_co_petition_id)) {
    print $this->Form->hidden('CoPetition.id', array('default' => $vv_co_petition_id)) . "\n";
  }
  
  if(!empty($vv_petition_token)) {
    print $this->Form->hidden('CoPetition.token', array('default' => $vv_petition_token)) . "\n";
  }
?>
<div>
  <div id="tabs-attributes">
    <?php
      $e = true;  
      
      include('petition-attributes.inc');
    ?>
  </div>
</div>
<?php
  print $this->Form->end();
