<?php
/**
 * COmanage Registry Relink View
 *
 * Copyright (C) 2014 University Corporation for Advanced Internet Development, Inc.
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
 * @copyright     Copyright (C) 2014 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.9.1
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */

  // Get a pointer to our model
  $model = $this->name;
  $req = Inflector::singularize($model);
  $modelpl = Inflector::tableize($req);
  
  if(empty($this->request->params['named']['tocopersonid'])) {
    // Render index view as people picker
    include(APP . "View/" . $model . "/index.ctp");
  }
  
  if(!empty($this->request->params['named']['tocopersonid'])) {
    $params = array('title' => $title_for_layout);
    print $this->element("pageTitle", $params);
    
    // Add breadcrumbs
    $args = array();
    $args['plugin'] = null;
    $args['controller'] = 'co_people';
    $args['action'] = 'index';
    $args['co'] = $cur_co['Co']['id'];
    $this->Html->addCrumb(_txt('me.population'), $args);
    $args = array(
      'controller' => 'co_people',
      'action' => 'canvas',
      $vv_co_org_identity_link['CoPerson']['id']
    );
    $this->Html->addCrumb(generateCn($vv_co_org_identity_link['CoPerson']['PrimaryName']), $args);
    $this->Html->addCrumb(_txt('op.relink'));
    
    // And start the form
    print $this->Form->Create(
      'CoOrgIdentityLink',
      array(
        'action' => 'edit/' . $vv_co_org_identity_link['CoOrgIdentityLink']['id'],
        'type'   => 'post',
        'inputDefaults' => array(
          'label' => false,
          'div'   => false
        )
      )
    );
    
    print $this->Form->hidden('org_identity_id',
                              array('default' => $vv_co_org_identity_link['CoOrgIdentityLink']['org_identity_id'])) . "\n";
    // Set the target (new) CO Person ID
    print $this->Form->hidden('co_person_id',
                              array('default' => $vv_to_co_person['CoPerson']['id'])) . "\n";
  }
?>
<?php if(!empty($this->request->params['named']['tocopersonid'])): ?>
<script type="text/javascript">
  function maybe_enable_submit() {
    // If the checkbox is checked, enable submit
    
    if(document.getElementById('CoOrgIdentityLinkConfirm').checked) {
      $(":submit").removeAttr('disabled');
    } else {
      $(":submit").attr('disabled', true);
    }
  }
  
  function js_local_onload()
  {
    // Local (to this view) initializations
    
    // Disable submit button until confirmation is ticked
    maybe_enable_submit();
  }
</script>
<div class="ui-state-highlight ui-corner-all" style="margin-top: 20px; padding: 0 .7em;"> 
  <p>
    <span class="ui-icon ui-icon-info" style="float: left; margin-right: .3em;"></span>
    <strong><?php print _txt('op.relink.confirm'); ?></strong>
  </p>
</div>
<div style="float:left;width:100%;">
  <ul>
    <li>
    <?php print _txt('op.relink.info',
                     array($this->Html->link(generateCn($vv_co_org_identity_link['OrgIdentity']['PrimaryName']),
                                             array('controller' => 'org_identities',
                                                   'action'     => 'view',
                                                   $vv_co_org_identity_link['OrgIdentity']['id'])),
                           $this->Html->link(generateCn($vv_co_org_identity_link['CoPerson']['PrimaryName']),
                                             array('controller' => 'co_people',
                                                   'action'     => 'canvas',
                                                   $vv_co_org_identity_link['CoPerson']['id'])),
                           $this->Html->link(generateCn($vv_to_co_person['PrimaryName']),
                                             array('controller' => 'co_people',
                                                   'action'     => 'canvas',
                                                   $vv_to_co_person['CoPerson']['id'])))); ?>
    </li>
  
  <?php
    if(!empty($vv_co_org_identity_link['OrgIdentity']['CoPetition'])) {
      foreach($vv_co_org_identity_link['OrgIdentity']['CoPetition'] as $p) {
        if(isset($p['status'])
           && ($p['status'] == StatusEnum::PendingApproval
               || $p['status'] == StatusEnum::PendingConfirmation)) {
          print "<li>" . _txt('op.relink.petition',
                              array(_txt('en.status', null, $p['status']),
                                    $this->Html->link($p['id'],
                                                      array('controller' => 'co_petitions',
                                                            'action'     => 'view',
                                                            $p['id'])))) . "</li>\n";
          
          // XXX if org identity is relinked away from petition pending confirmation, enrollee
          // can still confirm and attach their credentials to the linked-away identity. Perhaps
          // just document this (in the wiki?) but don't try to do anything about it.
        }
      }
    }
  ?>
  </ul>
  
  <p>
    <?php
      print $this->Form->checkbox('confirm', array('onClick' => "maybe_enable_submit()"));
      print $this->Form->label('confirm',_txt('op.confirm.box'));
    ?>
  </p>
  
  <p>
    <?php
      print $this->Form->submit(_txt('op.relink'));
    ?>
  </p>
</div>
<?php print $this->Form->end(); ?>
<?php endif; ?>