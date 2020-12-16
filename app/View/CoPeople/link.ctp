<?php
/**
 * COmanage Registry Link View
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
 * @since         COmanage Registry v0.9.1
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

  // Get a pointer to our model
  $model = $this->name;
  $req = Inflector::singularize($model);
  $modelpl = Inflector::tableize($req);
  
  if(empty($this->request->params['pass']['0'])) {
    // Render index view as people picker
    include(APP . "View/" . $model . "/index.ctp");
  }
  
  if(!empty($this->request->params['pass']['0'])) {
    $params = array('title' => $title_for_layout);
    print $this->element("pageTitle", $params);
    
    // Add breadcrumbs
    print $this->element("coCrumb");
    $args = array();
    $args['plugin'] = null;
    $args['controller'] = 'org_identities';
    $args['action'] = 'index';
    $args['co'] = $cur_co['Co']['id']; // This will be ignored if not pooled
    $this->Html->addCrumb(_txt('ct.org_identities.pl'), $args);
    $args = array(
      'controller' => 'org_identities',
      'action' => 'edit',
      $vv_org_identity['OrgIdentity']['id']
    );
    $this->Html->addCrumb(generateCn($vv_org_identity['PrimaryName']), $args);
    $this->Html->addCrumb(_txt('op.link'));
    
    // And start the form
    print $this->Form->Create(
      'CoOrgIdentityLink',
      array(
        'url' => array('action' => 'add'),
        'type'   => 'post',
        'inputDefaults' => array(
          'label' => false,
          'div'   => false
        )
      )
    );
    
    print $this->Form->hidden('org_identity_id',
                              array('default' => $vv_org_identity['OrgIdentity']['id'])) . "\n";
    
    // Set the target (new) CO Person ID
    print $this->Form->hidden('co_person_id',
                              array('default' => $vv_co_person['CoPerson']['id'])) . "\n";
  }
?>
<?php if(!empty($this->request->params['pass']['0'])): ?>
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
<div class="co-info-topbox">
  <em class="material-icons">info</em>
  <?php print _txt('op.link.confirm'); ?>
</div>
<div class="innerContent">
  <ul>
    <li>
    <?php print _txt('op.link.info',
                     array($this->Html->link(generateCn($vv_org_identity['PrimaryName']),
                                             array('controller' => 'org_identities',
                                                   'action'     => 'view',
                                                   $vv_org_identity['OrgIdentity']['id'])),
                           $this->Html->link(generateCn($vv_co_person['PrimaryName']),
                                             array('controller' => 'co_people',
                                                   'action'     => 'canvas',
                                                   $vv_co_person['CoPerson']['id'])))); ?>
    </li>
  
  <?php
    if(!empty($vv_org_identity['CoPetition'])) {
      foreach($vv_org_identity['CoPetition'] as $p) {
        if(isset($p['status'])
           && ($p['status'] == StatusEnum::PendingApproval
               || $p['status'] == StatusEnum::PendingConfirmation)) {
          print "<li>" . _txt('op.link.petition',
                              array(_txt('en.status', null, $p['status']),
                                    $this->Html->link($p['id'],
                                                      array('controller' => 'co_petitions',
                                                            'action'     => 'view',
                                                            $p['id'])))) . "</li>\n";
        }
      }
    }
  ?>
  </ul>

  <div class="checkbox">
    <?php
      print $this->Form->checkbox('confirm', array('onClick' => "maybe_enable_submit()"));
      print $this->Form->label('confirm', _txt('op.confirm.box'));
    ?>
  </div>
  
  <p>
    <?php
      print $this->Form->submit(_txt('op.link'));
    ?>
  </p>
</div>
<?php print $this->Form->end(); ?>
<?php endif; // params pass 0 ?>