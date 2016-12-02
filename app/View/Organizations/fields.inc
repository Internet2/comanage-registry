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
 * @since         COmanage Registry v0.1
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */

  // Determine if fields are editable
  $e = false;
  
  if(($this->action == "add" && $permissions['add']) || ($this->action == "edit" && $permissions['edit']))
    $e = true;
    
  // We shouldn't get here if we don't have at least read permission, but check just in case
  
  if(!$e && !$permissions['view'])
    return(false);

  // Add breadcrumbs
  $this->Html->addCrumb(_txt('ct.organizations.pl'), array('controller' => 'organizations', 'action' => 'index'));
  $crumbTxt = _txt('op.' . $this->action . '-a', array(_txt('ct.organizations.1')));
  $this->Html->addCrumb($crumbTxt);

?>
<table id="<?php print $this->action; ?>_organization" class="ui-widget">
  <tbody>
    <tr class="line1">
      <td>
        <?php print _txt('fd.name'); ?><span class="required">*</span>
      </td>
      <td>
        <?php print ($e ? $this->Form->input('name', array('class' => 'focusFirst')) : filter_var($organizations[0]['Organization']['name'],FILTER_SANITIZE_FULL_SPECIAL_CHARS)); ?>
      </td>
    </tr>
    <tr class="line2">
      <td>
        <?php print _txt('fd.domain'); ?>
      </td>
      <td>
        <?php print ($e ? $this->Form->input('domain') : filter_var($organizations[0]['Organization']['domain'],FILTER_SANITIZE_FULL_SPECIAL_CHARS)); ?>
      </td>
    </tr>
    <tr class="line1">
      <td>
        <?php print _txt('fd.directory'); ?>
      </td>
      <td>
        <?php print ($e ? $this->Form->input('directory') : filter_var($organizations[0]['Organization']['directory'],FILTER_SANITIZE_FULL_SPECIAL_CHARS)); ?>
      </td>
    </tr>
    <tr class="line2">
      <td>
        <?php print _txt('fd.searchbase'); ?>
      </td>
      <td>
        <?php print ($e ? $this->Form->input('search_base') : filter_var($organizations[0]['Organization']['search_base'],FILTER_SANITIZE_FULL_SPECIAL_CHARS)); ?>
      </td>
    </tr>
    <tr>
      <td>
        <em><span class="required"><?php print _txt('fd.req'); ?></span></em><br />
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
  </tbody>
</table>
