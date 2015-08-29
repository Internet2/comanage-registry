<?php
/**
 * COmanage Registry Organization Fields
 *
 * Copyright (C) 2010-15 University Corporation for Advanced Internet Development, Inc.
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
 * @copyright     Copyright (C) 2013-15 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.2
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */

  $params = array('title' => "Add a New Organizational Person");
  print $this->element("pageTitle", $params);

  // Add breadcrumbs
  print $this->element("coCrumb");
  $args = array();
  $args['plugin'] = null;
  $args['controller'] = 'org_identities';
  $args['action'] = 'index';
  if(!$pool_org_identities) { // XXX is this required here?
    $args['co'] = $cur_co['Co']['id'];
  }
  $this->Html->addCrumb(_txt('ct.org_identities.pl'), $args);
  $crumbTxt = _txt('op.add-a', array(_txt('ct.org_identities.1')));
  $this->Html->addCrumb($crumbTxt);

  // XXX this page needs I18N and maybe sanitize_html

  // Assemble list of organizations
  $r = array();
  
  foreach($organizations as $o)
  {
    $i = $o['Organization']['id'];
    $n = $o['Organization']['name'];
    
    $r[$i] = $n;
  }

  $submit_label = "Add New Person";
  print $this->Form->create('OrgIdentity',
                           array('action' => 'selectvialdap',
                                 'inputDefaults' => array('label' => false, 'div' => false)));
?>

<table id="add_org_identity_via_ldap" class="ui-widget">
  <tbody>
    <tr class="line1">
      <td>
        Organization<span class="required">*</span>
      </td>
      <td>
        <?php print $this->Form->select('organization', $r); ?>
      </td>
    </tr>
    <tr class="line2">
      <td>
        Surname <!-- XXX allow additional/configurable search terms -->
      </td>
      <td>
        <?php print $this->Form->input('sn'); ?>
      </td>
    </tr>
    <tr>
      <td>
        <em><span class="required">* denotes required field</span></em><br />
      </td>
      <td>
        <?php print $this->Form->submit('Search'); ?>
      </td>
    </tr>
  </tbody>
</table>

<?php
  print $this->Form->end();
?>
