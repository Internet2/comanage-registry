<?php
/**
 * COmanage Registry Organization Source Record View
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
 * @since         COmanage Registry v4.4.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */
  
  $key = isset($this->request->params['named']['key'])
         ? filter_var($this->request->params['named']['key'],FILTER_SANITIZE_SPECIAL_CHARS)
         : null;

  // Add page title
  $params = array();
  $params['title'] = $title_for_layout;

  // Add breadcrumbs
  print $this->element("coCrumb");

  $args = array(
    'controller' => 'organization_sources',
    'plugin'     => null,
    'action'     => 'index',
    'co'         => $cur_co['Co']['id']
  );
  
  $this->Html->addCrumb(_txt('ct.organization_sources.pl'), $args);
  
  $args = array(
    'controller' => 'organization_sources',
    'plugin'     => null,
    'action'     => 'query',
    $vv_organization_source['OrganizationSource']['id']
  );

  $this->Html->addCrumb($vv_organization_source['OrganizationSource']['description'], $args);
  $this->Html->addCrumb($vv_organization_record['Organization']['source_key']);
  // Add top links
  $params['topLinks'] = array();

  if(!isset($vv_not_found) || !$vv_not_found) {
    // Unlike Org Identity Sources, we only have "sync", not "sync" and "create", so we
    // only need to change the label

    if(!empty($permissions['sync']) && $permissions['sync']) {
      $label = !empty($vv_os_record) ? _txt('op.org.sync.os') : _txt('op.org.add.os');
      $buttonclass = !empty($vv_os_record) ? "reconcilebutton" : "addbutton";

      $args = array(
        'controller' => 'organization_sources',
        'action'     => 'sync',
        $vv_organization_source['OrganizationSource']['id'],
        'key'        => $key
      );

      $params['topLinks'][] = $this->Html->link(
        $label,
        $args,
        array('class' => $buttonclass)
      );
    }

    if(!empty($vv_os_record)) {
      if(!empty($permissions['view']) && $permissions['view']) {
        // View the Organization for this record

        $params['topLinks'][] = $this->Html->link(
          _txt('op.view-a', array(_txt('ct.organizations.1'))),
          array(
            'controller' => 'organizations',
            'action'     => 'view',
            $vv_os_record['OrganizationSourceRecord']['organization_id']
          ),
          array('class' => 'viewbutton')
        );
      }

      // View Source Record button
      $params['topLinks'][] = $this->Html->link(
        _txt('op.view-a', array(_txt('ct.organization_source_records.1'))),
        array(
          'controller' => 'organization_source_records',
          'action'     => 'view',
          $vv_os_record['OrganizationSourceRecord']['id']
        ),
        array('class' => 'viewbutton')
      );
    }
  }

  print $this->element("pageTitleAndButtons", $params);

  $l = 1;
?>
<?php if(!empty($vv_os_record['OrganizationSourceRecord']['organization_id'])): ?>
  <div class="co-info-topbox">
    <em class="material-icons">info</em>
    <div class="co-info-topbox-text">
      <?php print _txt('in.org.os'); ?>
    </div>
  </div>
<?php endif; // view ?>
<?php if(isset($vv_not_found) && $vv_not_found): ?>
<div class="co-info-topbox">
  <em class="material-icons">info</em>
  <div class="co-info-topbox-text">
    <?php print _txt('in.org.os.notfound'); ?>
  </div>
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
    <table id="view_organization_source_record">
      <tbody>
        <tr class="line<?php print $l++ % 2; ?>">
          <td>
            <?php print _txt('ct.organization_sources.1'); ?>
          </td>
          <td>
            <?php
              print $vv_organization_source['OrganizationSource']['description'];

              if($vv_organization_source['OrganizationSource']['status'] != SuspendableStatusEnum::Active) {
                print " (" . _txt('en.status.susp', null, $vv_organization_source['OrganizationSource']['status']) . ")";
              }
            ?>
          </td>
        </tr>
        <tr class="line<?php print $l++ % 2; ?>">
          <td>
            <?php print _txt('fd.sorid'); ?>
          </td>
          <td>
            <?php print $vv_organization_record['Organization']['source_key']; ?>
          </td>
        </tr>
        <tr class="line<?php print $l++ % 2; ?>">
          <td>
            <?php
              print _txt('fd.name');
            ?>
          </td>
          <td>
          <?php
            if(!empty($vv_organization_record['Organization']['logo_url'])) {
              print $this->Html->image($vv_organization_record['Organization']['logo_url'], array('alt' => 'logo')) . "\n";
            }

            print filter_var($vv_organization_record['Organization']['name'],FILTER_SANITIZE_SPECIAL_CHARS);
          ?>
          </td>
        </tr>
        <tr class="line<?php print $l++ % 2; ?>">
          <td>
            <?php print _txt('fd.desc'); ?>
          </td>
          <td>
            <?php
              if(!empty($vv_organization_record['Organization']['description'])) {
                print filter_var($vv_organization_record['Organization']['description'],FILTER_SANITIZE_SPECIAL_CHARS);
              }
            ?>
          </td>
        </tr>
        <tr class="line<?php print $l++ % 2; ?>">
          <td>
            <?php print _txt('fd.os.scope.saml'); ?>
          </td>
          <td>
            <?php
              if(!empty($vv_organization_record['Organization']['saml_scope'])) {
                print filter_var($vv_organization_record['Organization']['saml_scope'],FILTER_SANITIZE_SPECIAL_CHARS);
              }
            ?>
          </td>
        </tr>
        <tr class="line<?php print $l++ % 2; ?>">
          <td>
            <?php print _txt('fd.type'); ?>
          </td>
          <td>
            <?php
              if(!empty($vv_organization_record['Organization']['type'])) {
                print _txt('en.organization.type', null, $vv_organization_record['Organization']['type']);
              }
            ?>
          </td>
        </tr>
        <?php if(!empty($vv_organization_record['Address'])) foreach($vv_organization_record['Address'] as $addr): ?>
        <tr class="line<?php print $l++ % 2; ?>">
          <td>
            <?php print _txt('fd.address') . " (" . $addr['type'] . ")"; ?>
          </td>
          <td>
          <?php print filter_var(formatAddress($addr),FILTER_SANITIZE_SPECIAL_CHARS); ?>
          </td>
        </tr>
        <?php endforeach; // address ?>
        <?php if(!empty($vv_organization_record['EmailAddress'])) foreach($vv_organization_record['EmailAddress'] as $email): ?>
        <tr class="line<?php print $l++ % 2; ?>">
          <td>
            <?php print _txt('fd.email_address.mail') . " (" . $email['type'] . ")"; ?>
          </td>
          <td>
          <?php print filter_var($email['mail'],FILTER_SANITIZE_SPECIAL_CHARS); ?>
          </td>
        </tr>
        <?php endforeach; // email ?>
        <?php if(!empty($vv_organization_record['Identifier'])) foreach($vv_organization_record['Identifier'] as $id): ?>
        <tr class="line<?php print $l++ % 2; ?>">
          <td>
            <?php 
              print _txt('fd.identifier.identifier') . " (" 
                    . $id['type'] 
                    . ((isset($id['language']) && $id['language']) ? ", " . $id['language'] : "")
                    . ((isset($id['login']) && $id['login']) ? ", " . _txt('fd.identifier.login') : "")
                    . ")"; ?>
          </td>
          <td>
          <?php print filter_var($id['identifier'],FILTER_SANITIZE_SPECIAL_CHARS); ?>
          </td>
        </tr>
        <?php endforeach; // identifier ?>
        <?php if(!empty($vv_organization_record['TelephoneNumber'])) foreach($vv_organization_record['TelephoneNumber'] as $phone): ?>
        <tr class="line<?php print $l++ % 2; ?>">
          <td>
            <?php print _txt('fd.telephone_number.number') . " (" . $phone['type'] . ")"; ?>
          </td>
          <td>
          <?php print filter_var(formatTelephone($phone),FILTER_SANITIZE_SPECIAL_CHARS); ?>
          </td>
        </tr>
        <?php endforeach; // telephone ?>
        <?php if(!empty($vv_organization_record['Url'])) foreach($vv_organization_record['Url'] as $url): ?>
        <tr class="line<?php print $l++ % 2; ?>">
          <td>
            <?php print _txt('fd.url.url') . " ("
                        . $url['type']
                        . ((isset($url['language']) && $url['language']) ? ", " . $url['language'] : "")
                        . ")"; ?>
          </td>
          <td>
            <?php print $url['url']; ?>
          </td>
        </tr>
        <?php endforeach; // url ?>
        <?php if(!empty($vv_organization_record['Contact'])) foreach($vv_organization_record['Contact'] as $ct): ?>
        <tr class="line<?php print $l++ % 2; ?>">
          <td>
            <?php print _txt('ct.contacts.1') . " (" . $ct['type'] . ")"; ?>
          </td>
          <td>
            <ul>
              <li><?php print filter_var(generateCn($ct),FILTER_SANITIZE_SPECIAL_CHARS); ?></li>
              <?php
                if(!empty($ct['mail'])) {
                  print "<li>" . filter_var($ct['mail']) . "</li\n";
                }

                if(!empty($ct['number'])) {
                  print "<li>" . filter_var($ct['number']) . "</li\n";
                }
              ?>
            </ul>
          </td>
        </tr>
        <?php endforeach; // Contact ?>
        <?php if(!empty($vv_organization_record['AdHocAttribute'])): ?>
        <tr class="line<?php print $l++ % 2; ?>">
          <td>
            <?php print _txt('ct.ad_hoc_attributes.pl'); ?>
          </td>
          <td>
            <ul>
              <?php foreach($vv_organization_record['AdHocAttribute'] as $aha): ?>
              <li><?php print filter_var($aha['tag'],FILTER_SANITIZE_SPECIAL_CHARS) . ": " . filter_var($aha['value'],FILTER_SANITIZE_SPECIAL_CHARS); ?></li>
              <?php endforeach; ?>
            </ul>
          </td>
        </tr>
        <?php endif; // AdHocAttribute ?>
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
      </tbody>
    </table>
  </div>
</div>
<?php endif; // vv_not_found
