<?php
/**
 * COmanage Registry Organization Source Records Fields
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

  // Determine if fields are editable
  $e = false;
  
  if(!$e && !$permissions['view'])
    return false;
  
  // Add breadcrumbs
  print $this->element("coCrumb");
  
  $args = array(
    'controller' => 'organizations',
    'plugin'     => null,
    'action'     => 'index',
    'co'         => $cur_co['Co']['id']
  );
  
  $this->Html->addCrumb(_txt('ct.organizations.pl'), $args);

  $args = array(
    'controller' => 'organizations',
    'plugin'     => null,
    'action'     => 'view',
    $organization_source_records[0]['Organization']['id']
  );
  
  $this->Html->addCrumb($organization_source_records[0]['Organization']['name'], $args);
  
  $args = array(
    'controller' => 'organization_sources',
    'plugin'     => null,
    'action'     => 'view',
    $organization_source_records[0]['OrganizationSource']['id']
  );
  
  $this->Html->addCrumb($organization_source_records[0]['OrganizationSource']['description'], $args);
  $this->Html->addCrumb($organization_source_records[0]['OrganizationSourceRecord']['source_key']);
?>
<?php if($this->action == 'view' && !empty($organization_source_records[0]['OrganizationSourceRecord']['id'])): ?>
  <div class="co-info-topbox">
    <em class="material-icons">info</em>
    <div class="co-info-topbox-text">
      <?php print _txt('in.org.osr'); ?>
    </div>
  </div>
<?php endif; // view ?>

<div class="table-container">
  <table id="<?php print $this->action; ?>_organization_source_record">
    <tbody>
      <tr class="line1">
        <td>
          <?php print _txt('ct.organization_sources.1'); ?>
        </td>
        <td>
          <?php print filter_var($organization_source_records[0]['OrganizationSource']['description'],FILTER_SANITIZE_SPECIAL_CHARS); ?>
        </td>
      </tr>
      <tr class="line2">
        <td>
          <?php print _txt('fd.sorid'); ?>
        </td>
        <td>
          <?php print filter_var($organization_source_records[0]['OrganizationSourceRecord']['source_key'],FILTER_SANITIZE_SPECIAL_CHARS); ?>
        </td>
      </tr>
      <tr class="line1">
        <td>
          <?php print _txt('fd.update.last'); ?>
        </td>
        <td>
          <?php
            print $this->Time->format($organization_source_records[0]['OrganizationSourceRecord']['last_update'], "%c $vv_tz", false, $vv_tz);
          ?>
        </td>
      </tr>
      <tr class="line2">
        <td>
          <?php print _txt('fd.ois.record'); ?>
        </td>
        <td>
          <pre>
            <?php print filter_var($organization_source_records[0]['OrganizationSourceRecord']['source_record'],FILTER_SANITIZE_SPECIAL_CHARS); ?>
          </pre>
        </td>
      </tr>
    </tbody>
  </table>
</div>

<?php
  print $this->element("changelog"); 
