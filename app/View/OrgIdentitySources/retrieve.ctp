<?php
/**
 * COmanage Registry Org Identity Source Record View
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
 * @since         COmanage Registry v2.0.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */
  
  $key = isset($this->request->params['named']['key'])
         ? filter_var($this->request->params['named']['key'],FILTER_SANITIZE_SPECIAL_CHARS)
         : null;

  // Add page title
  $params = array();
  $params['title'] = $title_for_layout;

  // Add breadcrumbs
  if(!$pool_org_identities) {
    print $this->element("coCrumb");
  }

  $args = array(
    'controller' => 'org_identity_sources',
    'plugin'     => null,
    'action'     => 'index'
  );
  
  if(!$pool_org_identities) {
    $args['co'] = $cur_co['Co']['id'];
  }
  
  $this->Html->addCrumb(_txt('ct.org_identity_sources.pl'), $args);
  
  $args = array(
    'controller' => 'org_identity_sources',
    'plugin'     => null,
    'action'     => 'query',
    $vv_org_identity_source['id']
  );

  $this->Html->addCrumb($vv_org_identity_source['description'], $args);
  $this->Html->addCrumb($key);

  // Add top links
  $params['topLinks'] = array();

  if(!isset($vv_not_found) || !$vv_not_found) {
    if(empty($vv_ois_record)) {
      if(!empty($permissions['create']) && $permissions['create']) {
        // Create a new Org Identity from this record. We might be in the middle
        // of an enrollment flow, in which case we change the text label.

        $label = _txt('op.orgid.add.ois');

        $args = array(
          'controller' => 'org_identity_sources',
          'action'     => 'create',
          $vv_org_identity_source['id'],
          'key'        => $key
        );

        if(!empty($this->request->params['named']['copetitionid'])) {
          $label = _txt('op.orgid.petition.ois');
          $args['copetitionid'] = filter_var($this->request->params['named']['copetitionid'],FILTER_SANITIZE_SPECIAL_CHARS);
        }

        $params['topLinks'][] = $this->Html->link(
          $label,
          $args,
          array('class' => 'addbutton')
        );
      }
    } else {
      if(!empty($permissions['view']) && $permissions['view']) {
        // View the Org Identity for this record

        $params['topLinks'][] = $this->Html->link(
          _txt('op.view-a', array(_txt('ct.org_identities.1'))),
          array(
            'controller' => 'org_identities',
            'action'     => 'view',
            $vv_ois_record['OrgIdentitySourceRecord']['org_identity_id']
          ),
          array('class' => 'viewbutton')
        );
      }

      // View Source Record button
      $params['topLinks'][] = $this->Html->link(
        _txt('op.view-a', array(_txt('ct.org_identity_source_records.1'))),
        array(
          'controller' => 'org_identity_source_records',
          'action'     => 'view',
          $vv_ois_record['OrgIdentitySourceRecord']['id']
        ),
        array('class' => 'viewbutton')
      );

      if($vv_org_identity_source['status'] == SuspendableStatusEnum::Active
         && !empty($permissions['sync']) && $permissions['sync']) {
        // Resync the Org Identity for this record

        $params['topLinks'][] = $this->Html->link(
          _txt('op.orgid.sync.ois'),
          array(
            'controller' => 'org_identity_sources',
            'action'     => 'sync',
            $vv_org_identity_source['id'],
            'key'        => $key
          ),
          array('class' => 'reconcilebutton')
        );
      }
    }
  }

  print $this->element("pageTitleAndButtons", $params);

  $l = 1;
?>
<?php if(!empty($vv_ois_record)
         && !empty($this->request->params['named']['copetitionid'])): ?>
  <div class="co-info-topbox">
    <em class="material-icons">info</em>
    <?php print _txt('er.ois.pt.linked'); ?>
  </div>
<?php endif; ?>
<?php if(!empty($vv_ois_record['OrgIdentitySourceRecord']['org_identity_id'])): ?>
  <div class="co-info-topbox">
    <em class="material-icons">info</em>
    <?php print _txt('in.orgid.ois'); ?>
  </div>
<?php endif; // view ?>
<?php if(isset($vv_not_found) && $vv_not_found): ?>
<div class="ui-state-highlight ui-corner-all co-info-topbox">
  <p>
    <span class="ui-icon ui-icon-info co-info"></span>
    <strong><?php print _txt('in.orgid.ois.notfound'); ?></strong>
  </p>
</div>
<br />
<?php else: // vv_not_found ?>
<script>
  $(function() {
    // Toggle source record formatting
    $("#source-record-format-toggle").click(function (e) {
      e.preventDefault();
      $("code.source-record").toggleClass("source-record-formatted");
    });
  });
</script>
<div class="innerContent">
  <div class="table-container">
    <table id="view_org_identity_source_record">
      <tbody>
        <tr class="line<?php print $l++ % 2; ?>">
          <td>
            <?php print _txt('ct.org_identity_sources.1'); ?>
          </td>
          <td>
            <?php
              print $vv_org_identity_source['description'];

              if($vv_org_identity_source['status'] != SuspendableStatusEnum::Active) {
                print " (" . _txt('en.status.susp', null, $vv_org_identity_source['status']) . ")";
              }
            ?>
          </td>
        </tr>
        <tr class="line<?php print $l++ % 2; ?>">
          <td>
            <?php print _txt('fd.sorid'); ?>
          </td>
          <td>
            <?php print $key; ?>
          </td>
        </tr>
        <?php if(!empty($vv_org_source_record['Name'])) foreach($vv_org_source_record['Name'] as $name): ?>
        <tr class="line<?php print $l++ % 2; ?>">
          <td>
            <?php
              print _txt('fd.name') . " (";

              if(isset($name['primary_name']) && $name['primary_name'])
                print _txt('fd.name.primary_name') . ", ";

            print filter_var($name['type'],FILTER_SANITIZE_SPECIAL_CHARS) . ")";
            ?>
          </td>
          <td>
          <?php print filter_var(generateCn($name),FILTER_SANITIZE_SPECIAL_CHARS); ?>
          </td>
        </tr>
        <?php endforeach; // name ?>
        <tr class="line<?php print $l++ % 2; ?>">
          <td>
            <?php print _txt('fd.date_of_birth'); ?>
          </td>
          <td>
            <?php
              if(!empty($vv_org_source_record['OrgIdentity']['date_of_birth'])) {
                print filter_var($vv_org_source_record['OrgIdentity']['date_of_birth'],FILTER_SANITIZE_SPECIAL_CHARS);
              }
            ?>
          </td>
        </tr>
        <tr class="line<?php print $l++ % 2; ?>">
          <td>
            <?php print _txt('fd.affiliation'); ?>
          </td>
          <td>
            <?php
              if(!empty($vv_org_source_record['OrgIdentity']['affiliation'])) {
              print filter_var($vv_org_source_record['OrgIdentity']['affiliation'],FILTER_SANITIZE_SPECIAL_CHARS);
              }
            ?>
          </td>
        </tr>
        <tr class="line<?php print $l++ % 2; ?>">
          <td>
            <?php print _txt('fd.valid_from.tz', array('UTC')); ?>
          </td>
          <td>
            <?php
              if(!empty($vv_org_source_record['OrgIdentity']['valid_from'])) {
              print filter_var($vv_org_source_record['OrgIdentity']['valid_from'],FILTER_SANITIZE_SPECIAL_CHARS);
              }
            ?>
          </td>
        </tr>
        <tr class="line<?php print $l++ % 2; ?>">
          <td>
            <?php print _txt('fd.valid_through.tz', array('UTC')); ?>
          </td>
          <td>
            <?php
              if(!empty($vv_org_source_record['OrgIdentity']['valid_through'])) {
              print filter_var($vv_org_source_record['OrgIdentity']['valid_through'],FILTER_SANITIZE_SPECIAL_CHARS);
              }
            ?>
          </td>
        </tr>
        <tr class="line<?php print $l++ % 2; ?>">
          <td>
            <?php print _txt('fd.title'); ?>
          </td>
          <td>
            <?php
              if(!empty($vv_org_source_record['OrgIdentity']['title'])) {
              print filter_var($vv_org_source_record['OrgIdentity']['title'],FILTER_SANITIZE_SPECIAL_CHARS);
              }
            ?>
          </td>
        </tr>
        <tr class="line<?php print $l++ % 2; ?>">
          <td>
            <?php print _txt('fd.o'); ?>
          </td>
          <td>
            <?php
              if(!empty($vv_org_source_record['OrgIdentity']['o'])) {
              print filter_var($vv_org_source_record['OrgIdentity']['o'],FILTER_SANITIZE_SPECIAL_CHARS);
              }
            ?>
          </td>
        </tr>
        <tr class="line<?php print $l++ % 2; ?>">
          <td>
            <?php print _txt('fd.ou'); ?>
          </td>
          <td>
            <?php
              if(!empty($vv_org_source_record['OrgIdentity']['ou'])) {
              print filter_var($vv_org_source_record['OrgIdentity']['ou'],FILTER_SANITIZE_SPECIAL_CHARS);
              }
            ?>
          </td>
        </tr>
        <?php if(!empty($vv_org_source_record['Address'])) foreach($vv_org_source_record['Address'] as $addr): ?>
        <tr class="line<?php print $l++ % 2; ?>">
          <td>
            <?php print _txt('fd.address') . " (" . $addr['type'] . ")"; ?>
          </td>
          <td>
          <?php print filter_var(formatAddress($addr),FILTER_SANITIZE_SPECIAL_CHARS); ?>
          </td>
        </tr>
        <?php endforeach; // address ?>
        <?php if(!empty($vv_org_source_record['EmailAddress'])) foreach($vv_org_source_record['EmailAddress'] as $email): ?>
        <tr class="line<?php print $l++ % 2; ?>">
          <td>
            <?php print _txt('fd.email_address.mail') . " (" . $email['type'] . ")"; ?>
          </td>
          <td>
          <?php print filter_var($email['mail'],FILTER_SANITIZE_SPECIAL_CHARS); ?>
          </td>
        </tr>
        <?php endforeach; // email ?>
        <?php if(!empty($vv_org_source_record['Identifier'])) foreach($vv_org_source_record['Identifier'] as $id): ?>
        <tr class="line<?php print $l++ % 2; ?>">
          <td>
            <?php 
              print _txt('fd.identifier.identifier') . " (" 
                    . $id['type'] 
                    . ((isset($id['login']) && $id['login']) ? ", " . _txt('fd.identifier.login') : "")
                    . ")"; ?>
          </td>
          <td>
          <?php print filter_var($id['identifier'],FILTER_SANITIZE_SPECIAL_CHARS); ?>
          </td>
        </tr>
        <?php endforeach; // identifier ?>
        <?php if(!empty($vv_org_source_record['TelephoneNumber'])) foreach($vv_org_source_record['TelephoneNumber'] as $phone): ?>
        <tr class="line<?php print $l++ % 2; ?>">
          <td>
            <?php print _txt('fd.telephone_number.number') . " (" . $phone['type'] . ")"; ?>
          </td>
          <td>
          <?php print filter_var(formatTelephone($phone),FILTER_SANITIZE_SPECIAL_CHARS); ?>
          </td>
        </tr>
        <?php endforeach; // telephone ?>
        <?php if(!empty($vv_org_source_record['Url'])) foreach($vv_org_source_record['Url'] as $url): ?>
        <tr class="line<?php print $l++ % 2; ?>">
          <td>
            <?php print _txt('fd.url.url') . " (" . $url['type'] . ")"; ?>
          </td>
          <td>
            <?php print $url['url']; ?>
          </td>
        </tr>
        <?php endforeach; // url ?>
        <?php if(!empty($vv_mapped_groups)): ?>
        <tr class="line<?php print $l++ % 2; ?>">
          <td>
            <?php print _txt('fd.group.mem.map'); ?>
          </td>
          <td>
            <ul>
              <?php
                foreach($vv_mapped_groups as $g) {
                  print "<li>" . filter_var($g['CoGroup']['name'],FILTER_SANITIZE_SPECIAL_CHARS);
                  if(!empty($g['CoGroupMember']['valid_from']) || !empty($g['CoGroupMember']['valid_through'])) {
                    print " (" 
                          . (!empty($g['CoGroupMember']['valid_from']) ? $g['CoGroupMember']['valid_from'] : "")
                          . " - "
                          . (!empty($g['CoGroupMember']['valid_through']) ? $g['CoGroupMember']['valid_through'] : "")
                          . ")";
                  }
                  print "</li>\n";
                }
              ?>
            </ul>
          </td>
        </tr>
        <?php endif; // mapped groups ?>
        <?php if(!empty($vv_org_source_record['AdHocAttribute'])): ?>
        <tr class="line<?php print $l++ % 2; ?>">
          <td>
            <?php print _txt('ct.ad_hoc_attributes.pl'); ?>
          </td>
          <td>
            <ul>
              <?php foreach($vv_org_source_record['AdHocAttribute'] as $aha): ?>
              <li><?php print filter_var($aha['tag'],FILTER_SANITIZE_SPECIAL_CHARS) . ": " . filter_var($aha['value'],FILTER_SANITIZE_SPECIAL_CHARS); ?></li>
              <?php endforeach; ?>
            </ul>
          </td>
        </tr>
        <?php endif; // AdHocAttribute ?>
        <?php if(!empty($vv_org_source_record['OrgIdentity']['manager_identifier'])): ?>
        <tr class="line<?php print $l++ % 2; ?>">
          <td>
            <?php print _txt('fd.manager'); ?>
          </td>
          <td>
            <?php print $vv_org_source_record['OrgIdentity']['manager_identifier']; ?>
          </td>
        </tr>
        <?php endif; // manager_identifier ?>
        <?php if(!empty($vv_org_source_record['OrgIdentity']['sponsor_identifier'])): ?>
        <tr class="line<?php print $l++ % 2; ?>">
          <td>
            <?php print _txt('fd.sponsor'); ?>
          </td>
          <td>
            <?php print $vv_org_source_record['OrgIdentity']['sponsor_identifier']; ?>
          </td>
        </tr>
        <?php endif; // sponsor_identifier ?>
        <tr class="line<?php print $l++ % 2; ?>">
          <td>
            <?php print _txt('fd.ois.record'); ?><br />
            <span class="descr"><?php print _txt('fd.ois.record.desc'); ?></span>
          </td>
          <td>
            <button id="source-record-format-toggle" class="btn btn-link">
              <?php print _txt('op.ois.toggle.format') ?>
            </button>
            <code class="source-record">
              <?php
                if(!empty($vv_raw_source_record)) {
                print filter_var($vv_raw_source_record,FILTER_SANITIZE_SPECIAL_CHARS);
                }
              ?>
            </code>
          </td>
        </tr>
        <?php if($vv_org_identity_source['hash_source_record']): ?>
        <tr class="line<?php print $l++ % 2; ?>">
          <td>
            <?php print _txt('fd.ois.record.hashed'); ?>
          </td>
          <td>
            <?php
              if(!empty($vv_source_record_hash)) {
                print $vv_source_record_hash;
              }
            ?>
          </td>
        </tr>
        <?php endif; // hash_source_record ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; // vv_not_found
