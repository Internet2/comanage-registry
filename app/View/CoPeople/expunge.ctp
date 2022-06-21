<?php
/**
 * COmanage Registry CO Person Expunge View
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
 * @since         COmanage Registry v0.8.5
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

  $params = array('title' => $title_for_layout);
  print $this->element("pageTitle", $params);

  // Add breadcrumbs
  print $this->element("coCrumb");
  $args = array();
  $args['plugin'] = null;
  $args['controller'] = 'co_people';
  $args['action'] = 'index';
  $args['co'] = $cur_co['Co']['id'];
  $this->Html->addCrumb(_txt('me.population'), $args);
  $args = array();
  $args['controller'] = 'co_people';
  $args['action'] = 'canvas';
  $args[] = $vv_co_person['CoPerson']['id'];
  $this->Html->addCrumb(generateCn($vv_co_person['PrimaryName']), $args);
  $this->Html->addCrumb(_txt('op.expunge'));

  print $this->Form->Create(
    'CoPerson',
    array(
      'url' => array('action' => 'expunge/' . $vv_co_person['CoPerson']['id']),
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
<?php if($vv_lightbox): ?>
<div class="co-info-topbox lightbox-info">
<?php else: ?>
 <div class="co-info-topbox">
<?php endif; ?>
  <em class="material-icons mr-1">info</em>
  <?php print _txt('op.expunge.confirm', array(filter_var(generateCn($vv_co_person['PrimaryName']),FILTER_SANITIZE_SPECIAL_CHARS))); ?>
</div>
<div class="innerContent">
  <p>
    <?php print _txt('op.expunge.info'); ?>
    
    <ul>
      <li><?php print _txt('op.expunge.info.cop', array(filter_var(generateCn($vv_co_person['PrimaryName']),FILTER_SANITIZE_SPECIAL_CHARS),
                                                        $this->Html->url(array('controller' => 'co_people',
                                                                               'action'     => 'canvas',
                                                                               $vv_co_person['CoPerson']['id'])),
                                                        $this->Html->url(array('controller' => 'history_records',
                                                                               'action'     => 'index',
                                                                               'copersonid' => $vv_co_person['CoPerson']['id'])))); ?></li>
      <?php foreach($vv_co_person['CoPersonRole'] as $cr): ?>
      <li><?php print _txt('op.expunge.info.copr', array(!empty($cr['Cou']['name']) ? filter_var($cr['Cou']['name'],FILTER_SANITIZE_SPECIAL_CHARS) : filter_var($vv_co_person['Co']['name'],FILTER_SANITIZE_SPECIAL_CHARS),
                                                         (!empty($cr['title']) ? filter_var($cr['title'],FILTER_SANITIZE_SPECIAL_CHARS) : _txt('fd.title.none')),
                                                         $this->Html->url(array('controller' => 'co_person_roles',
                                                                                'action'     => 'view',
                                                                                $cr['id'])))); ?></li>
      <?php endforeach; ?>
      <?php
        foreach($vv_co_person['CoOrgIdentityLink'] as $lnk) {
          if(count($lnk['OrgIdentity']['CoOrgIdentityLink']) > 1) {
            // There's a link to another identity, so we won't purge this org identity
            print "<li>" . _txt('op.expunge.info.org.no', array(filter_var(generateCn($lnk['OrgIdentity']['PrimaryName']),FILTER_SANITIZE_SPECIAL_CHARS),
                                                                $this->Html->url(array('controller' => 'org_identities',
                                                                                       'action'     => 'view',
                                                                                       $lnk['org_identity_id'])))) . "</li>\n";
          } else {
            print "<li>" . _txt('op.expunge.info.org', array(filter_var(generateCn($lnk['OrgIdentity']['PrimaryName']),FILTER_SANITIZE_SPECIAL_CHARS),
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
        if($h['co_person_id'] != $vv_co_person['CoPerson']['id']
           &&
           (!empty($vv_co_person['CoOrgIdentityLink'][0]['org_identity_id'])
            && $h['org_identity_id'] != $vv_co_person['CoOrgIdentityLink'][0]['org_identity_id'])) {
          $hrcnt++;
        }
      }
    }
    
    if($hrcnt > 0):
  ?>
  <p>
    <span class="ui-icon ui-icon-info co-info"></span>
    <strong><?php print _txt('op.expunge.info.hist', array($hrcnt,
                                                           $this->Html->url(array('controller'      => 'history_records',
                                                                                  'action'          => 'index',
                                                                                  'actorcopersonid' => $vv_co_person['CoPerson']['id'])))); ?></strong>
  </p>
  <?php endif; ?>
  
  <?php if(!empty($vv_co_person['CoNotificationActor'])): ?>
  <p>
    <span class="ui-icon ui-icon-info co-info"></span>
    <strong><?php print _txt('op.expunge.info.not.act', array(count($vv_co_person['CoNotificationActor']),
                                                              $this->Html->url(array('controller'      => 'co_notifications',
                                                                                     'action'          => 'index',
                                                                                     'actorcopersonid' => $vv_co_person['CoPerson']['id'],
                                                                                     '?'               => array('status' => 'all'))))); ?></strong>
  </p>
  <?php endif; ?>
  
  <?php if(!empty($vv_co_person['CoNotificationRecipient'])): ?>
  <p>
    <span class="ui-icon ui-icon-info co-info"></span>
    <strong><?php print _txt('op.expunge.info.not.rec', array(count($vv_co_person['CoNotificationRecipient']),
                                                              $this->Html->url(array('controller'          => 'co_notifications',
                                                                                     'action'              => 'index',
                                                                                     'recipientcopersonid' => $vv_co_person['CoPerson']['id'],
                                                                                     '?'                   => array('status' => 'all'))))); ?></strong>
  </p>
  <?php endif; ?>
  
  <?php if(!empty($vv_co_person['CoNotificationResolver'])): ?>
  <p>
    <span class="ui-icon ui-icon-info co-info"></span>
    <strong><?php print _txt('op.expunge.info.not.res', array(count($vv_co_person['CoNotificationResolver']),
                                                              $this->Html->url(array('controller'         => 'co_notifications',
                                                                                     'action'             => 'index',
                                                                                     'resolvercopersonid' => $vv_co_person['CoPerson']['id'],
                                                                                     '?'                  => array('status' => 'all'))))); ?></strong>
  </p>
  <?php endif; ?>

  <div class="checkbox">
    <?php
      print $this->Form->checkbox('confirm', array('onClick' => "maybe_enable_submit()"));
      print $this->Form->label('confirm',_txt('op.confirm.box'));
    ?>
  </div>
  
  <p>
    <?php
      $loader = $this->Html->tag(
        'span',
        '',
        array(
          'class' => 'spinner-grow spinner-grow-sm mr-2 align-middle invisible btn-submit-with-loader',
          'escape' => false,
          'role' => 'status',
          'aria-hidden' => 'true'
        )
      );

      $button_text = $this->Html->tag(
        'span',
        _txt('op.expunge'),
        array(
          'escape' => false,
          'role' => 'status'
        )
      );

      print $this->Form->button($loader . $button_text, array(
        'type' => 'submit',
        'class' => 'btn btn-primary d-flex align-items-center',
        'disabled' => true,
        'onclick' => 'javascript:showBtnSpinnerLightbox()'
      ));
    ?>
  </p>
</div>
<?php print $this->Form->end(); ?>
