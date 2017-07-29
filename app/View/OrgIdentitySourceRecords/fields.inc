<?php
/**
 * COmanage Registry Organizational Identity Source Records Fields
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

  // Determine if fields are editable
  $e = false;
  
  if(!$e && !$permissions['view'])
    return false;
  
  // Add breadcrumbs
  if(!$pool_org_identities) {
    print $this->element("coCrumb");
  }
  
  $args = array(
    'controller' => 'org_identities',
    'plugin'     => null,
    'action'     => 'index'
  );
  
  if(!$pool_org_identities) {
    $args['co'] = $cur_co['Co']['id'];
  }
  
  $this->Html->addCrumb(_txt('ct.org_identities.pl'), $args);
  
  $args = array(
    'controller' => 'org_identities',
    'plugin'     => null,
    'action'     => 'view',
    $org_identity_source_records[0]['OrgIdentity']['id']
  );
  
  $this->Html->addCrumb(generateCn($org_identity_source_records[0]['OrgIdentity']['PrimaryName']), $args);
  
  $args = array(
    'controller' => 'org_identity_sources',
    'plugin'     => null,
    'action'     => 'view',
    $org_identity_source_records[0]['OrgIdentitySource']['id']
  );
  
  $this->Html->addCrumb($org_identity_source_records[0]['OrgIdentitySource']['description'], $args);
  $this->Html->addCrumb($org_identity_source_records[0]['OrgIdentitySourceRecord']['sorid']);
?>
<?php if($this->action == 'view' && !empty($org_identity_source_records[0]['OrgIdentitySourceRecord']['id'])): ?>
  <div class="co-info-topbox">
    <em class="material-icons">info</em>
    <?php print _txt('in.orgid.oisr'); ?>
  </div>
<?php endif; // view ?>

<div class="table-container">
  <table id="<?php print $this->action; ?>_org_identity_source">
    <tbody>
      <tr class="line1">
        <td>
          <?php print _txt('ct.org_identity_sources.1'); ?>
        </td>
        <td>
          <?php print filter_var($org_identity_source_records[0]['OrgIdentitySource']['description'],FILTER_SANITIZE_SPECIAL_CHARS); ?>
        </td>
      </tr>
      <tr class="line2">
        <td>
          <?php print _txt('fd.sorid'); ?>
        </td>
        <td>
          <?php print filter_var($org_identity_source_records[0]['OrgIdentitySourceRecord']['sorid'],FILTER_SANITIZE_SPECIAL_CHARS); ?>
        </td>
      </tr>
      <tr class="line1">
        <td>
          <?php print _txt('fd.update.last'); ?>
        </td>
        <td>
          <?php
            print $this->Time->format($org_identity_source_records[0]['OrgIdentitySourceRecord']['last_update'], "%c $vv_tz", false, $vv_tz);
          ?>
        </td>
      </tr>
      <tr class="line2">
        <td>
          <?php print _txt('fd.ois.record'); ?>
        </td>
        <td>
          <pre>
            <?php print filter_var($org_identity_source_records[0]['OrgIdentitySourceRecord']['source_record'],FILTER_SANITIZE_SPECIAL_CHARS); ?>
          </pre>
        </td>
      </tr>
    </tbody>
  </table>
</div>

<?php
  print $this->element("changelog"); 
