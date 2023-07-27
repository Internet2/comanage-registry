<?php
/**
 * COmanage Registry Relink Organizational Identity View
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
?>  
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
<?php if($vv_lightbox): ?>
<div class="co-info-topbox lightbox-info">
<?php else: ?>
<div class="co-info-topbox">
<?php endif; ?>
  <em class="material-icons">info</em>
  <div class="co-info-topbox-text">
    <?php print _txt('op.relink.confirm'); ?>
  </div>
</div>
<div class="innerContent">
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
      $loader = $this->Html->tag(
        'span',
        '',
        array(
          'class' => 'spinner-grow spinner-grow-sm mr-1 align-middle invisible btn-submit-with-loader',
          'escape' => false,
          'role' => 'status',
          'aria-hidden' => true
        )
      );

      print $this->Form->button($loader . _txt('op.relink'),array(
        'type' => 'submit',
        'class' => 'btn btn-primary',
        'disabled' => true,
        'onclick' => 'javascript:showBtnSpinnerLightbox()'
      ));
    ?>
  </p>
</div>
<?php print $this->Form->end(); ?>
