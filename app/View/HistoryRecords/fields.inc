<?php
/**
 * COmanage Registry History Record Fields
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
 * @since         COmanage Registry v0.9
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

  // Determine if fields are editable
  $e = false;
  
  if(($this->action == "add" && $permissions['add']) || ($this->action == "edit" && $permissions['edit']))
    $e = true;
    
  // We shouldn't get here if we don't have at least read permission, but check just in case
  if(!$e && !$permissions['view'])
    return false;

  // Add breadcrumbs
  print $this->element("coCrumb");
  if(isset($this->request->params['named']['copersonid'])) {
    // CO Person History
    $args = array();
    $args['plugin'] = null;
    $args['controller'] = 'co_people';
    $args['action'] = 'index';
    $args['co'] = $cur_co['Co']['id'];
    $this->Html->addCrumb(_txt('me.population'), $args);

    $args = array(
      'controller' => 'co_people',
      'action' => 'canvas',
      filter_var($this->request->params['named']['copersonid'],FILTER_SANITIZE_SPECIAL_CHARS));
    /* XXX When we carry the display name through all actions, we can uncomment this section;
       It works now for add, but not for the index of history records.
    if (isset($display_name)) {
      $this->Html->addCrumb($display_name, $args);
    } else { */
      $this->Html->addCrumb(_txt('ct.co_people.1'), $args);
    /*}*/

    $args = array(
      'controller' => 'history_records',
      'action' => 'index',
      'copersonid' => filter_var($this->request->params['named']['copersonid'],FILTER_SANITIZE_SPECIAL_CHARS));
    $this->Html->addCrumb(_txt('ct.history_records.pl'), $args);

  } elseif(isset($this->request->params['named']['orgidentityid'])) {
    // Org ID History
    $args = array();
    $args['plugin'] = null;
    $args['controller'] = 'org_identities';
    $args['action'] = 'index';
    if(!$pool_org_identities) {
      $args['co'] = $cur_co['Co']['id'];
    }
    $this->Html->addCrumb(_txt('ct.org_identities.pl'), $args);

    $args = array(
      'controller' => 'orgIdentities',
      'action' => 'edit',
      filter_var($this->request->params['named']['orgidentityid'],FILTER_SANITIZE_SPECIAL_CHARS));
    $this->Html->addCrumb(_txt('ct.org_identities.1'), $args);

    $args = array(
        'controller' => 'history_records',
        'action' => 'index',
        'orgidentityid' => filter_var($this->request->params['named']['orgidentityid'],FILTER_SANITIZE_SPECIAL_CHARS));
    $this->Html->addCrumb(_txt('ct.history_records.pl'), $args);
  }

  $this->Html->addCrumb(_txt('op.' . $this->action));

  // Set up the hidden values
  
  if(isset($this->request->params['named']['copersonid'])) {
    print $this->Form->hidden('co_person_id',
                              array('default' => filter_var($this->request->params['named']['copersonid'],FILTER_SANITIZE_SPECIAL_CHARS))). "\n";
  } elseif(isset($this->request->params['named']['orgidentityid'])) {
    print $this->Form->hidden('org_identity_id',
                              array('default' => filter_var($this->request->params['named']['orgidentityid'],FILTER_SANITIZE_SPECIAL_CHARS))). "\n";
  }
  
  print $this->Form->hidden('action', array('default' => ActionEnum::CommentAdded));
  print $this->Form->hidden('actor_co_person_id', array('default' => $this->Session->read('Auth.User.co_person_id')));
  
  $l = 1;
?>
<table id="<?php print $this->action; ?>_history_record" class="ui-widget">
  <tbody>
    <tr class="line<?php print $l%2;$l++; ?>">
      <td>
        <?php print _txt('fd.comment'); ?><span class="required">*</span>
      </td>
      <td>
        <?php print ($e
                     ? $this->Form->input('comment',
                                          array('class' => 'focusFirst',
                                                'size'  => '80'))
                     : filter_var($history_records[0]['HistoryRecord']['comment'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
      </td>
    </tr>
    <?php if($this->action == 'view'): ?>
    <tr class="line<?php print $l%2;$l++; ?>">
      <td>
        <?php print _txt('fd.action'); ?>
      </td>
      <td>
        <?php print filter_var($history_records[0]['HistoryRecord']['action'],FILTER_SANITIZE_SPECIAL_CHARS); ?>
      </td>
    </tr>
    <tr class="line<?php print $l%2;$l++; ?>">
      <td>
        <?php print _txt('ct.co_people.1'); ?>
      </td>
      <td>
        <?php
          if(!empty($history_records[0]['CoPerson']['id'])) {
            print $this->Html->link(
              generateCn($history_records[0]['CoPerson']['PrimaryName']),
              array(
                'controller' => 'co_people',
                'action' => 'canvas',
                $history_records[0]['CoPerson']['id']
              )
            );
          }
        ?>
      </td>
    </tr>
    <tr class="line<?php print $l%2;$l++; ?>">
      <td>
        <?php print _txt('ct.co_person_roles.1'); ?>
      </td>
      <td>
        <?php
          if(!empty($history_records[0]['CoPersonRole']['id'])) {
            print $this->Html->link(
              filter_var($history_records[0]['CoPersonRole']['title'],FILTER_SANITIZE_SPECIAL_CHARS)
              . " (" . $history_records[0]['CoPersonRole']['id'] . ")",
              array(
                'controller' => 'co_person_roles',
                'action' => 'view',
                $history_records[0]['CoPersonRole']['id']
              )
            );
          }
        ?>
      </td>
    </tr>
    <tr class="line<?php print $l%2;$l++; ?>">
      <td>
        <?php print _txt('ct.org_identities.1'); ?>
      </td>
      <td>
        <?php
          if(!empty($history_records[0]['OrgIdentity']['id'])) {
            print $this->Html->link(
              generateCn($history_records[0]['OrgIdentity']['PrimaryName']),
              array(
                'controller' => 'org_identities',
                'action' => 'view',
                $history_records[0]['OrgIdentity']['id']
              )
            );
          }
        ?>
      </td>
    </tr>
    <tr class="line<?php print $l%2;$l++; ?>">
      <td>
        <?php print _txt('ct.co_groups.1'); ?>
      </td>
      <td>
        <?php
          if(!empty($history_records[0]['CoGroup']['id'])) {
            print $this->Html->link(
              $history_records[0]['CoGroup']['name'],
              array(
                'controller' => 'co_groups',
                'action' => 'view',
                $history_records[0]['CoGroup']['id']
              )
            );
          }
        ?>
      </td>
    </tr>
    <tr class="line<?php print $l%2;$l++; ?>">
      <td>
        <?php print _txt('fd.actor'); ?>
      </td>
      <td>
        <?php
          if(!empty($history_records[0]['ActorCoPerson']['id'])) {
            print $this->Html->link(
              generateCn($history_records[0]['ActorCoPerson']['PrimaryName']),
              array(
                'controller' => 'co_people',
                'action' => 'view',
                $history_records[0]['ActorCoPerson']['id']
              )
            );
          }
        ?>
      </td>
    </tr>
    <tr class="line<?php print $l%2;$l++; ?>">
      <td>
        <?php print _txt('fd.created'); ?>
      </td>
      <td>
        <?php print $this->Time->format($history_records[0]['HistoryRecord']['created'], "%c $vv_tz", false, $vv_tz); ?>
      </td>
    </tr>
    <?php endif; // view ?>
    <?php if($this->action != 'view'): ?>
      <tr>
        <td>
          <em><span class="required"><?php print _txt('fd.req'); ?></span></em>
        </td>
        <td>
          <?php
            if($e) {
              print $this->Form->submit($submit_label);
              print $this->Form->button(_txt('op.reset'),
                                        array('type'=>'reset'));
            }
          ?>
        </td>
      </tr>
    <?php endif ?>
  </tbody>
</table>
