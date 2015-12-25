<?php
/**
 * COmanage Registry Org Identity Source Record View
 *
 * Copyright (C) 2015 University Corporation for Advanced Internet Development, Inc.
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
 * @copyright     Copyright (C) 2015 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v1.1.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */
 
  // Add page title
  $params = array();
  $params['title'] = $title_for_layout;

  // Add top links
  $params['topLinks'] = array();
 
  print $this->element("pageTitleAndButtons", $params);
 
  $l = 1;
?>
<div class="innerContent">
  <table id="view_org_identity_source_record" class="ui-widget">
    <tbody>
      <tr class="line<?php print $l++ % 2; ?>">
        <td>
          <?php print _txt('ct.org_identity_sources.1'); ?>
        </td>
        <td>
          <?php print $vv_ois_name; ?>
        </td>
      </tr>
      <tr class="line<?php print $l++ % 2; ?>">
        <td>
          <?php print _txt('fd.name'); ?>
        </td>
        <td>
          <?php print generateCn($vv_org_source_record['PrimaryName']); ?>
        </td>
      </tr>
      <tr class="line<?php print $l++ % 2; ?>">
        <td>
          <?php print _txt('fd.email_address.mail'); ?>
        </td>
        <td>
          <?php
            if(!empty($vv_org_source_record['EmailAddress'][0]['mail'])) {
              print $vv_org_source_record['EmailAddress'][0]['mail'];
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
              print $vv_org_source_record['OrgIdentity']['o'];
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
              print $vv_org_source_record['OrgIdentity']['ou'];
            }
          ?>
        </td>
      </tr>
    </tbody>
  </table>
</div>
