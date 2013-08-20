<!--
/**
 * COmanage Registry CO Petitions Fields
 *
 * Copyright (C) 2012 University Corporation for Advanced Internet Development, Inc.
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
 * @copyright     Copyright (C) 2012 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.5
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */
-->
<script type="text/javascript">
$(document).ready(function() {
  $("input.matchable").keyup(function(event) {
    if(event.which != 13) {
      // 13 is enter/return... don't search on form submit
      // XXX Don't hardcode fields here, or /registry prefix
      $.ajax({
        url: '/registry/co_people/match/co:<?php print $cur_co['Co']['id']; ?>'
             + '/coef:' + <?php print Sanitize::html($co_enrollment_flow_id); ?>
             + '/given:' + document.getElementById('EnrolleeCoPersonNameGiven').value
             + '/family:' + document.getElementById('EnrolleeCoPersonNameFamily').value
      }).done(function(data) {
        $('div#results').html(data);
      });
    }
  });
});

$(function() {
/*  $( "#accordion" ).accordion({
    heightStyle: "fill"
  });*/
    // Ennumeration for tabs
    var tabid = new Object();
    tabid["petition"]  = 0;
    tabid["attributes"]    = 1;
    tabid["history"] = 2;

    // Turn on Tabs
    var $tabs = $( "#tabs" ).tabs();
    
    // If returning to this page via redirect, open last used tab
    <?php if(isset($this->request->params['named']['tab'])): ?>
      var selectedtab = "<?php print($this->request->params['named']['tab']); ?>";

      $tabs.tabs('option', 'active', tabid[selectedtab] );
    <?php endif; ?>
});
</script>

<?php
  // Add links to sidebar

  if($this->action != "add") {
    // Get any existing buttons
    $sidebarButtons = $this->get('sidebarButtons');

    // Add button for related Org Identity
    $sidebarButtons[]  = array(
      'icon'    => 'note',
      'title'   => _txt('ct.org_identities.1'),
      'url'     => array(
        'controller'    => 'org_identities',
        'action'        => 'view',
        $co_petitions[0]['CoPetition']['enrollee_org_identity_id'],
        'co'            => $co_petitions[0]['CoPetition']['co_id']
      )
    );

    // Add button for related CO Person
    $sidebarButtons[]  = array(
      'icon'    => 'note',
      'title'   => _txt('ct.co_people.1'),
      'url'     => array(
        'controller'    => 'co_people',
        'action'        => 'view',
        $co_petitions[0]['CoPetition']['enrollee_co_person_id'],
        'co'            => $co_petitions[0]['CoPetition']['co_id']
      )
    );

    $this->set('sidebarButtons', $sidebarButtons);
  }

  // Determine if fields are editable
  $e = false;
  
  if(($this->action == "add" && $permissions['add']) || ($this->action == "edit" && $permissions['edit']))
    $e = true;
    
  // We shouldn't get here if we don't have at least read permission, but check just in case
  
  if(!$e && !$permissions['view'])
    return(false);
    
  if(isset($cur_co))
    print $this->Form->hidden('co_id', array('default' => $cur_co['Co']['id'])) . "\n";
  
  print $this->Form->hidden('co_enrollment_flow_id', array('default' => Sanitize::html($co_enrollment_flow_id)));
  
  $l = 0;
  
  // Unlike most other views, CO Petitions dynamically generates attributes to be
  // completed according to a configuration, not according to a model. This is a
  // lot of logic to embed in a view file, and plausibly this code should be
  // refactored at some point.
?>
<div style="min-height: 350px;">
  <div id="tabs">
    <ul>
      <?php if($this->action != 'add'): ?>
        <li>
          <a href="#tabs-petition">
            <?php print _txt('ct.co_petitions.1'); ?>
          </a>
        </li>
      <?php endif; ?>

      <?php if($this->action != "invite"): ?>
        <li>
          <a href="#tabs-attributes">
            <?php print _txt('fd.attrs.pet'); ?>
          </a>
        </li>
      <?php endif; ?>
        
      <?php if($this->action != 'add'): ?>
        <li>
          <a href="#tabs-history">
            <?php print _txt('fd.history.pt'); ?>
          </a>
        </li>
      <?php endif; ?>

      
    </ul>
    <div id="tabs-petition">
      <?php if($this->action != 'add'): ?>
        <div>
          <table id="<?php print $this->action; ?>_co_petition" class="ui-widget">
            <tbody>
              <tr class="line<?php print ($l % 2); $l++; ?>">
                <th><?php print _txt('fd.status'); ?></th>
                <td>
                  <?php
                    print _txt('en.status', null, $co_petitions[0]['CoPetition']['status']);
                    
                    if($co_petitions[0]['CoPetition']['status'] == StatusEnum::PendingApproval) {
                      if($permissions['approve']) {
                        print $this->Html->link(
                          _txt('op.approve'),
                          array('controller' => 'co_petitions',
                                'action' => 'approve',
                                $co_petitions[0]['CoPetition']['id'],
                                'co' => $co_petitions[0]['CoPetition']['co_id'],
                                'coef' => $co_petitions[0]['CoPetition']['co_enrollment_flow_id']),
                          array('class' => 'checkbutton')
                        );
                      }
                      
                      if($permissions['deny']) {
                        print $this->Html->link(
                          _txt('op.deny'),
                          array('controller' => 'co_petitions',
                                'action' => 'deny',
                                $co_petitions[0]['CoPetition']['id'],
                                'co' => $co_petitions[0]['CoPetition']['co_id'],
                                'coef' => $co_petitions[0]['CoPetition']['co_enrollment_flow_id']),
                          array('class' => 'cancelbutton')
                        );
                      }
                    }
                  ?>
                </td>
              </tr>
              <tr class="line<?php print ($l % 2); $l++; ?>">
                <th><?php print _txt('fd.petitioner'); ?></th>
                <td>
                  <?php
                    if(isset($co_petitions[0]['PetitionerCoPerson']['Name'])) {
                      print generateCn($co_petitions[0]['PetitionerCoPerson']['Name']);
                    }
                  ?>
                </td>
              </tr>
              <tr class="line<?php print ($l % 2); $l++; ?>">
                <th><?php print _txt('fd.sponsor'); ?></th>
                <td>
                  <?php
                    if(isset($co_petitions[0]['SponsorCoPerson']['Name'])) {
                      print generateCn($co_petitions[0]['SponsorCoPerson']['Name']);
                    }
                  ?>
                </td>
              </tr>
              <tr class="line<?php print ($l % 2); $l++; ?>">
                <th><?php print _txt('fd.approver'); ?></th>
                <td>
                  <?php
                    if(isset($co_petitions[0]['ApproverCoPerson']['Name'])) {
                      print generateCn($co_petitions[0]['ApproverCoPerson']['Name']);
                    }
                  ?>
                </td>
              </tr>
              <tr class="line<?php print ($l % 2); $l++; ?>">
                <th><?php print _txt('fd.created'); ?></th>
                <td><?php print $this->Time->nice($co_petitions[0]['CoPetition']['created']); ?></td>
              </tr>
              <tr class="line<?php print ($l % 2); $l++; ?>">
                <th><?php print _txt('fd.modified'); ?></th>
                <td><?php print $this->Time->nice($co_petitions[0]['CoPetition']['modified']); ?></td>
              </tr>
            </tbody>
          </table>
        </div>

      <?php endif; // action == add ?>
    </div>
    <div id="tabs-attributes">

    <?php
      include('petition-attributes.inc');
    ?>
  </div>
</div>
<pre>
<?php

print_r($co_petitions[0][CoPetition]);

?>


