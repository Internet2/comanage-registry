<?php
/**
 * COmanage Registry CO Person Expunge View
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
 * @since         COmanage Registry v0.9
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */
  
  $params = array('title' => $title_for_layout);
  print $this->element("pageTitle", $params);
  
  print $this->Form->Create(
    'CoPerson',
    array(
      'action' => 'expunge/' . $vv_co_person['CoPerson']['id'],
      'type'   => 'post',
      'inputDefaults' => array(
        'label' => false,
        'div'   => false
      )
    )
  );
?>
<script type="text/javascript">
  function maybe_enable_submit() {
    // If the checkbox is checked, enable submit
    
    if(document.getElementById('CoPersonConfirm').checked) {
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
    <strong><?php print _txt('op.expunge.confirm', array(generateCn($vv_co_person['PrimaryName']))); ?></strong>
  </p>
</div>
<div style="float:left;width:100%;">
  <p>
    <?php print _txt('op.expunge.info'); ?>
    
    <ul>
      <li><?php print _txt('op.expunge.info.cop', array(generateCn($vv_co_person['PrimaryName']),
                                                        $this->Html->url(array('controller' => 'co_people',
                                                                               'action'     => 'view',
                                                                               $vv_co_person['CoPerson']['id'])),
                                                        $this->Html->url(array('controller' => 'history_records',
                                                                               'action'     => 'index',
                                                                               'copersonid' => $vv_co_person['CoPerson']['id'])))); ?></li>
      <?php foreach($vv_co_person['CoPersonRole'] as $cr): ?>
      <li><?php print _txt('op.expunge.info.copr', array(!empty($cr['Cou']['name']) ? $cr['Cou']['name'] : $vv_co_person['Co']['name'],
                                                         (!empty($cr['title']) ? $cr['title'] : _txt('fd.title.none')),
                                                         $this->Html->url(array('controller' => 'co_person_roles',
                                                                                'action'     => 'view',
                                                                                $cr['id'])))); ?></li>
      <?php endforeach; ?>
      <?php
        foreach($vv_co_person['CoOrgIdentityLink'] as $lnk) {
          if(count($lnk['OrgIdentity']['CoOrgIdentityLink']) > 1) {
            // There's a link to another identity, so we won't purge this org identity
            print "<li>" . _txt('op.expunge.info.org.no', array(generateCn($lnk['OrgIdentity']['PrimaryName']),
                                                                $this->Html->url(array('controller' => 'org_identities',
                                                                                       'action'     => 'view',
                                                                                       $lnk['org_identity_id'])))) . "</li>\n";
          } else {
            print "<li>" . _txt('op.expunge.info.org', array(generateCn($lnk['OrgIdentity']['PrimaryName']),
                                                             $this->Html->url(array('controller' => 'org_identities',
                                                                                    'action'     => 'view',
                                                                                    $lnk['org_identity_id'])))) . "</li>\n";
          }
        }
      ?>
    </ul>
  </p>
  
  <?php
    // Determine how many history records would be updated but not deleted
    $hrcnt = 0; 
    
    if(!empty($vv_co_person['HistoryRecordActor'])) {
      foreach($vv_co_person['HistoryRecordActor'] as $h) {
        if($h['co_person_id'] != $vv_co_person['CoPerson']['id']) {
          $hrcnt++;
        }
      }
    }
    
    if($hrcnt > 0):
  ?>
  <p>
    <span class="ui-icon ui-icon-info" style="float: left; margin-right: .3em;"></span>
    <strong><?php print _txt('op.expunge.info.hist', array($hrcnt,
                                                           $this->Html->url(array('controller'      => 'history_records',
                                                                                  'action'          => 'index',
                                                                                  'actorcopersonid' => $vv_co_person['CoPerson']['id'])))); ?></strong>
  </p>
  <?php endif; ?>
  
  <?php if(!empty($vv_co_person['CoNotificationActor'])): ?>
  <p>
    <span class="ui-icon ui-icon-info" style="float: left; margin-right: .3em;"></span>
    <strong><?php print _txt('op.expunge.info.not.act', array(count($vv_co_person['CoNotificationActor']),
                                                              $this->Html->url(array('controller'      => 'co_notifications',
                                                                                     'action'          => 'index',
                                                                                     'actorcopersonid' => $vv_co_person['CoPerson']['id'])))); ?></strong>
  </p>
  <?php endif; ?>
  
  <?php if(!empty($vv_co_person['CoNotificationRecipient'])): ?>
  <p>
    <span class="ui-icon ui-icon-info" style="float: left; margin-right: .3em;"></span>
    <strong><?php print _txt('op.expunge.info.not.rec', array(count($vv_co_person['CoNotificationRecipient']),
                                                              $this->Html->url(array('controller'          => 'co_notifications',
                                                                                     'action'              => 'index',
                                                                                     'recipientcopersonid' => $vv_co_person['CoPerson']['id'])))); ?></strong>
  </p>
  <?php endif; ?>
  
  <?php if(!empty($vv_co_person['CoNotificationResolver'])): ?>
  <p>
    <span class="ui-icon ui-icon-info" style="float: left; margin-right: .3em;"></span>
    <strong><?php print _txt('op.expunge.info.not.res', array(count($vv_co_person['CoNotificationResolver']),
                                                              $this->Html->url(array('controller'         => 'co_notifications',
                                                                                     'action'             => 'index',
                                                                                     'resolvercopersonid' => $vv_co_person['CoPerson']['id'])))); ?></strong>
  </p>
  <?php endif; ?>
  
  <p>
    <?php
      print $this->Form->checkbox('confirm', array('onClick' => "maybe_enable_submit()"))
            . " " . _txt('op.expunge.ack');
    ?>
  </p>
  
  <p>
    <?php
      print $this->Form->submit(_txt('op.expunge'));
    ?>
  </p>
</div>
<?php print $this->Form->end(); ?>
