<?php
/**
 * COmanage Registry Sponsor Renewal Widget Display View
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
 * @link          https://www.internet2.edu/comanage COmanage Project
 * @package       registry-plugin
 * @since         COmanage Registry v4.1.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

// For now, we don't inject breadcrumbs since for most sponsors they won't make
// sense. We could probably come up with a smarter approach, though.

// This view replicates much from CoPersonRoles/index.ctp

$title = _txt('pl.sponsormanager.sponsor', array(generateCn($vv_sponsor['PrimaryName'])));
print $this->element("pageTitle", array('title' => $title));
?>
<?php if(!empty($vv_settings['SponsorManagerSetting']['renewal_window'])): ?>
  <div class="co-info-topbox">
  <em class="material-icons">info</em>
  <div class="co-info-topbox-text">
  <?php
    // Calculate the start of the renewal window, as now+window. In other
    // words, if today is March 1 and the renewal window is 30 days, we'll
    // accept renewals on roles expiring March 31 or later.
    $renewalWindowStart = new DateTime('+' . $vv_settings['SponsorManagerSetting']['renewal_window'] . ' days');
    
    print _txt('pl.sponsormanager.renewal_window.info', array($renewalWindowStart->format("j M Y")));
  ?>
  </div>
</div>
<?php endif; ?>

<div id="sponsoreeFilter" class="top-filter">
  <?php
    $getUrl = $this->Html->url(array(
      'plugin' => 'sponsor_manager',
      'controller' => 'sponsors',
      'action' => 'review',
      'copersonid' => $this->request->params['named']['copersonid']
    ));
    
    $filter = !empty($this->request->query['filter'])
              ? $this->request->query['filter']
              : ReviewFilterEnum::Default;
  ?>
  <!-- Selector for which Sponsorees to view -->
  <form method="get" action="<?php print $getUrl; ?>">
    <span class="filters">
      <label class="select-name" for="filter"><?php print _txt('op.filter'); ?></label>
      <select name="filter" id="filter">
        <?php foreach($vv_filter_modes as $a => $label): ?>
        <option value="<?php print $a; ?>"<?php if($filter == $a) print " selected"; ?>><?php print $label; ?></option>
        <?php endforeach; ?>
      </select>
    </span>
    <span class="submit-button">
      <input type="submit" value="<?php print _txt('op.filter')?>"/>
    </span>
  </form>
</div>

<div class="table-container">
  <table id="sponsorees">
    <thead>
      <tr>
        <th><?php print _txt('fd.id.seq'); ?></th>
        <th><?php print $this->Paginator->sort('CoPerson.PrimaryName.family', _txt('fd.name')); ?></th>
        <th><?php print _txt('ct.identifiers.1'); ?></th>
        <th><?php print _txt('ct.email_addresses.1'); ?></th>
        <?php if($vv_settings['SponsorManagerSetting']['show_o']): ?>
        <th><?php print $this->Paginator->sort('o', _txt('fd.o')); ?></th>
        <?php endif; // show_o ?>
        <?php if($vv_settings['SponsorManagerSetting']['show_cou']): ?>
        <th><?php print $this->Paginator->sort('Cou.ou', _txt('fd.cou')); ?></th>
        <?php endif; // show_cou ?>
        <?php if($vv_settings['SponsorManagerSetting']['show_title']): ?>
        <th><?php print $this->Paginator->sort('title', _txt('fd.title')); ?></th>
        <?php endif; // show_title ?>
        <?php if($vv_settings['SponsorManagerSetting']['show_affiliation']): ?>
        <th><?php print $this->Paginator->sort('affiliation', _txt('fd.affiliation')); ?></th>
        <?php endif; // show_affiliation ?>
        <th><?php print $this->Paginator->sort('valid_from', _txt('fd.valid_from')); ?></th>
        <th><?php print $this->Paginator->sort('valid_through', _txt('fd.valid_through')); ?></th>
        <th><?php print $this->Paginator->sort('status', _txt('fd.status')); ?></th>
        <th><?php print _txt('fd.actions'); ?></th>
      </tr>
    </thead>
    
    <tbody>
      <?php $i = 0; ?>
      <?php foreach($vv_sponsored_roles as $p): ?>
      <?php 
        $canRenew = false;
        
        if($p['CoPersonRole']['status'] == StatusEnum::Active
           || $p['CoPersonRole']['status'] == StatusEnum::Expired
           || $p['CoPersonRole']['status'] == StatusEnum::GracePeriod) {
          $canRenew = true;
          
          if(!empty($vv_settings['SponsorManagerSetting']['renewal_window'])
             && !empty($p['CoPersonRole']['valid_through'])) {
            // Only render the renew button if the role is within the
            // renewal window. This is similar logic to SponsorManager::renew
            
            // Diff the window start and the role expiration
            $interval = $renewalWindowStart->diff(new DateTime($p['CoPersonRole']['valid_through']));
            
            // If invert is 1, the diff is negative (meaning the window start is
            // _earlier_ than the expiration date) and therefore the role is within
            // the renewal window.
            
            $canRenew = (bool)$interval->invert;
          }
        }

        $class = "line" . (($i++ % 2) + 1);
        
        if($p['CoPersonRole']['status'] == StatusEnum::Expired) {
          $class = "warn-level-a";
        } elseif($canRenew) {
          $class = "warn-level-b";
        }
      ?>
      <tr class="<?php print $class; ?>">
        <td><?php print $p['CoPersonRole']['id']; ?></td>
        <td>
          <?php
            if($permissions['canvas']) {
              print $this->Html->link(generateCn($p['CoPerson']['PrimaryName']),
                                      array('plugin' => null, 'controller' => 'co_people', 'action' => 'canvas', $p['CoPersonRole']['co_person_id']));
            } else {
              print filter_var(generateCn($p['CoPerson']['PrimaryName'],FILTER_SANITIZE_SPECIAL_CHARS));
            }
          ?>
        </td>
        <td><?php if(!empty($p['CoPerson']['Identifier'][0]['identifier'])) print filter_var($p['CoPerson']['Identifier'][0]['identifier'],FILTER_SANITIZE_SPECIAL_CHARS); ?></td>
        <td><?php if(!empty($p['CoPerson']['EmailAddress'][0]['mail'])) print filter_var($p['CoPerson']['EmailAddress'][0]['mail'],FILTER_SANITIZE_SPECIAL_CHARS); ?></td>
        <?php if($vv_settings['SponsorManagerSetting']['show_o']): ?>
        <td><?php print filter_var($p['CoPersonRole']['o'],FILTER_SANITIZE_SPECIAL_CHARS); ?></td>
        <?php endif; // show_o ?>
        <?php if($vv_settings['SponsorManagerSetting']['show_cou']): ?>
        <td><?php if(isset($p['Cou']['name'])) print filter_var($p['Cou']['name'],FILTER_SANITIZE_SPECIAL_CHARS); ?></td>
        <?php endif; // show_cou ?>
        <?php if($vv_settings['SponsorManagerSetting']['show_title']): ?>
        <td><?php print filter_var($p['CoPersonRole']['title'],FILTER_SANITIZE_SPECIAL_CHARS); ?></td>
        <?php endif; // show_title ?>
        <?php if($vv_settings['SponsorManagerSetting']['show_affiliation']): ?>
        <td><?php if(!empty($p['CoPersonRole']['affiliation'])) print $vv_copr_affiliation_types[ $p['CoPersonRole']['affiliation'] ]; ?></td>
        <?php endif; // show_affiliation ?>
        <td>
          <?php
            if($p['CoPersonRole']['valid_from'] > 0) {
              $v = $p['CoPersonRole']['valid_from'];
              
              if(!empty($vv_tz)) {
                // We need to adjust the UTC value to the user's local time
                $v = $this->Time->format($p['CoPersonRole']['valid_through'], "%F %T", false, $vv_tz);
              }
              
              print $this->Time->format('Y M d', $v);
            }
          ?>
        </td>
        <td>
          <?php
            if($p['CoPersonRole']['valid_through'] > 0)  {
              $v = $p['CoPersonRole']['valid_through'];
              
              if(!empty($vv_tz)) {
                // We need to adjust the UTC value to the user's local time
                $v = $this->Time->format($p['CoPersonRole']['valid_through'], "%F %T", false, $vv_tz);
              }
              
              print $this->Time->format('Y M d', $v);
            }
          ?>
        </td>
        <td>
          <?php
            if(!empty($p['CoPersonRole']['status']) ) print _txt('en.status', null, $p['CoPersonRole']['status']);
          ?>
        </td>
        <td>
          <?php
            if($p['CoPersonRole']['status'] == StatusEnum::Pending
               || $p['CoPersonRole']['status'] == StatusEnum::PendingApproval
               || $p['CoPersonRole']['status'] == StatusEnum::PendingConfirmation
               || $p['CoPersonRole']['status'] == StatusEnum::PendingVetting) {
              print '<button type="button" class="trashbutton" title="' . _txt('pl.sponsormanager.cancel')
                . '" onclick="javascript:js_confirm_generic(\''
                . _txt('pl.sponsormanager.cancel.confirm', array(_jtxt(generateCn($p['CoPerson']['PrimaryName'])))) . '\',\''    // dialog body text
                . $this->Html->url(              // dialog confirm URL
                  array(
                    'plugin'          => 'sponsor_manager',
                    'controller'      => 'sponsors',
                    'action'          => 'cancel',
                    'copersonid'      => $this->request->params['named']['copersonid'],
                    'copersonroleid'  => $p['CoPersonRole']['id'],
                    'filter'          => $filter
                  )
                ) . '\',\''
                . _txt('op.confirm') . '\',\''    // dialog confirm button
                . _txt('op.cancel') . '\',\''    // dialog cancel button
                . _txt('op.confirm') . '\',[\''   // dialog title
                . filter_var(_jtxt(generateCn($p['CoPerson']['PrimaryName'])),FILTER_SANITIZE_STRING)  // dialog body text replacement strings
                . '\']);">'
                . _txt('pl.sponsormanager.cancel')
                . '</button>';
            }
            
            if($p['CoPersonRole']['status'] == StatusEnum::Active
               || $p['CoPersonRole']['status'] == StatusEnum::GracePeriod) {
              print '<button type="button" class="deletebutton" title="' . _txt('pl.sponsormanager.expire')
                . '" onclick="javascript:js_confirm_generic(\''
                . _txt('pl.sponsormanager.expire.confirm', array(_jtxt(generateCn($p['CoPerson']['PrimaryName'])))) . '\',\''    // dialog body text
                . $this->Html->url(              // dialog confirm URL
                  array(
                    'plugin'          => 'sponsor_manager',
                    'controller'      => 'sponsors',
                    'action'          => 'expire',
                    'copersonid'      => $this->request->params['named']['copersonid'],
                    'copersonroleid'  => $p['CoPersonRole']['id'],
                    'filter'          => $filter
                  )
                ) . '\',\''
                . _txt('pl.sponsormanager.expire') . '\',\''    // dialog confirm button
                . _txt('op.cancel') . '\',\''    // dialog cancel button
                . _txt('pl.sponsormanager.expire') . '\',[\''   // dialog title
                . filter_var(_jtxt(generateCn($p['CoPerson']['PrimaryName'])),FILTER_SANITIZE_STRING)  // dialog body text replacement strings
                . '\']);">'
                . _txt('pl.sponsormanager.expire')
                . '</button>';
            }
            
            if($canRenew) {
              print '<button type="button" class="addbutton" title="' . _txt('pl.sponsormanager.renew')
                . '" onclick="javascript:js_confirm_generic(\''
                . _txt('pl.sponsormanager.renew.confirm', array(_jtxt(generateCn($p['CoPerson']['PrimaryName'])))) . '\',\''    // dialog body text
                . $this->Html->url(              // dialog confirm URL
                  array(
                    'plugin'          => 'sponsor_manager',
                    'controller'      => 'sponsors',
                    'action'          => 'renew',
                    'copersonid'      => $this->request->params['named']['copersonid'],
                    'copersonroleid'  => $p['CoPersonRole']['id'],
                    'filter'          => $filter
                  )
                ) . '\',\''
                . _txt('pl.sponsormanager.renew') . '\',\''    // dialog confirm button
                . _txt('op.cancel') . '\',\''    // dialog cancel button
                . _txt('pl.sponsormanager.renew') . '\',[\''   // dialog title
                . filter_var(_jtxt(generateCn($p['CoPerson']['PrimaryName'])),FILTER_SANITIZE_STRING)  // dialog body text replacement strings
                . '\']);">'
                . _txt('pl.sponsormanager.renew')
                . '</button>';
            }
          ?>
        </td>
      </tr>
      <?php endforeach; // $vv_sponsored_roles ?>
    </tbody>
  </table>
</div>

<?php
  print $this->element("pagination");