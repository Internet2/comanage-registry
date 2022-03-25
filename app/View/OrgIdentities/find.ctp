<?php
/**
 * COmanage Registry Org Identity Find View
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
 * @since         COmanage Registry v0.2
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */
?>
<?php
  $params = array('title' => $title_for_layout);
  print $this->element("pageTitle", $params);
  
  // What operation are we finding for?
  if(!empty($this->request->params['named']['copersonid'])) {
    // If a CO Person ID was provided, we're trying to link an Org Identity to
    // and existing CO Person
    $op = "link";
  } else {
    // Otherwise we are doing default enrollment
    $op = "invite";
  }
?>


<?php if($op == 'invite'): ?>
  <div class="co-info-topbox">
    <em class="material-icons">info</em>
    <?php print _txt('in.orgid.email'); ?>
  </div>
<?php endif; // invite ?>

<div class="co-info-topbox">
  <em class="material-icons">info</em>
  <?php print _txt('in.orgid.co'); ?>
</div>

<?php // Load the top search bar
// Search Block
if(!empty($vv_search_fields)) {
  print $this->element('search', array('vv_search_fields' => $vv_search_fields));
}
// Alphabet Search quick access bar
if(!empty($vv_alphabet_search)) {
  print $this->element('alphabetSearch', array('vv_alphabet_search_config' => $vv_alphabet_search));
}
?>

<div class="table-container">
  <table id="org_identities">
    <thead>
      <tr>
        <th><?php print $this->Paginator->sort('PrimaryName.family', _txt('fd.name')); ?></th>
        <th><?php print $this->Paginator->sort('o', _txt('fd.o')); ?></th>
        <th><?php print $this->Paginator->sort('title', _txt('fd.title')); ?></th>
        <th><?php print $this->Paginator->sort('affiliation', _txt('fd.affiliation')); ?></th>
        <th><?php print _txt('fd.email_address.mail'); ?></th>
        <th><?php print _txt('op.inv'); ?></th>
      </tr>
    </thead>

    <tbody>
      <?php $i = 0; ?>
      <?php foreach ($org_identities as $p): ?>
      <tr class="line<?php print ($i % 2)+1; ?>">
        <td><?php print $this->Html->link(generateCn($p['PrimaryName']),
                                          array('controller'             => 'org_identities',
                                                'action'                 => 'view',
                                                $p['OrgIdentity']['id'],
                                                'co'                     => ($pool_org_identities ? false : $cur_co['Co']['id']))); ?></td>
        <td><?php print filter_var($p['OrgIdentity']['o'],FILTER_SANITIZE_SPECIAL_CHARS); ?></td>
        <td><?php print filter_var($p['OrgIdentity']['title'],FILTER_SANITIZE_SPECIAL_CHARS); ?></td>
        <td><?php if(!empty($p['OrgIdentity']['affiliation'])) { print _txt('en.org_identity.affiliation', null, $p['OrgIdentity']['affiliation']); } ?></td>
        <td><?php foreach($p['EmailAddress'] as $ea) print filter_var($ea['mail'],FILTER_SANITIZE_SPECIAL_CHARS) . "<br />"; ?></td>
        <td><?php
          // Don't offer an invite link for org identities that are already in the CO

          $linked = false;

          foreach($p['CoOrgIdentityLink'] as $lnk) {
            if(!empty($lnk['CoPerson']['co_id'])
               && $lnk['CoPerson']['co_id'] == $cur_co['Co']['id']) {
              $linked = true;
              break;
            }
          }

          if(!$linked) {
            if($op == 'link') {
              // CO Person specified, so link instead of invite
              print $this->Html->link(_txt('op.link'),
                                         array('controller' => 'co_people',
                                               'action' => 'link',
                                               filter_var($this->request->params['named']['copersonid'],FILTER_SANITIZE_SPECIAL_CHARS),
                                               'orgidentityid' => $p['OrgIdentity']['id']),
                                         array('class' => 'linkbutton spin lightbox'));
            } else {
              print $this->Html->link(_txt('op.inv'),
                                         array('controller' => 'co_people',
                                               'action' => 'invite',
                                               'orgidentityid' => $p['OrgIdentity']['id'],
                                               'co' => $cur_co['Co']['id']),
                                         array('class' => 'invitebutton spin'));
            }
          }
        ?></td>
      </tr>
      <?php $i++; ?>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
  
<?php
  print $this->element("pagination");
