<!--
/**
 * COmanage Registry CO Navigation Link Fields
 *
 * Copyright (C) 2013-15 University Corporation for Advanced Internet Development, Inc.
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
 * @since         COmanage Registry v0.8.1
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */
-->
<?php
  // Determine if fields are editable
  $e = false;
  
  if(($this->action == "add" && $permissions['add']) || ($this->action == "edit" && $permissions['edit']))
    $e = true;
    
  // We shouldn't get here if we don't have at least read permission, but check just in case
  
  if(!$e && !$permissions['view'])
    return(false);

  // Add breadcrumbs
  $args = array();
  $args['plugin'] = null;
  $args['controller'] = 'co_navigation_links';
  $args['action'] = 'index';
  $args['co'] = $cur_co['Co']['id'];
  $this->Html->addCrumb(_txt('ct.co_navigation_links.pl'), $args);
  if($this->action == "add") {
    $this->Html->addCrumb(_txt('op.add') . ' ' . _txt('ct.co_navigation_links.1'));
  }
  if($this->action == "edit") {
    $this->Html->addCrumb(_txt('op.edit') . ' ' . _txt('ct.co_navigation_links.1'));
  }
  if($this->action == "view") {
    $this->Html->addCrumb(_txt('op.view') . ' ' . _txt('ct.co_navigation_links.1'));
  }
  
  print $this->Form->hidden('co_id', array('default' => $cur_co['Co']['id'])) . "\n";
?>

<table id="<?php print $this->action; ?>_co_link" class="ui-widget">
  <tbody>
    <tr class="line1">
      <td>
        <b><?php print _txt('fd.desc'); ?></b><br />
      </td>
      <td>
        <?php print ($e
                     ? $this->Form->input('description', array('class' => 'focusFirst'))
                     : Sanitize::html($co_navigation_links[0]['CoNavigationLink']['description'])); ?>
      </td>
    </tr>
    <tr class="line2">
      <td>
        <b><?php print _txt('fd.link.title'); ?></b><br />
      </td>
      <td>
        <?php print ($e
                     ? $this->Form->input('title')
                     : Sanitize::html($co_navigation_links[0]['CoNavigationLink']['title'])); ?>
      </td>
    </tr>
    <tr class="line1">
      <td>
        <b><?php print _txt('fd.link.url'); ?></b><br />
      </td>
      <td>
        <?php print ($e
                     ? $this->Form->input('url')
                     : Sanitize::html($co_navigation_links[0]['CoNavigationLink']['url'])); ?>
      </td>
    </tr>
    </tr>
    <tr class="line2">
      <td>
        <b><?php print _txt('fd.link.order'); ?></b><br />
      </td>
      <td>
        <?php print ($e
                     ? $this->Form->input('ordr')
                     : Sanitize::html($co_navigation_links[0]['CoNavigationLink']['ordr'])); ?>
      </td>
    </tr>
    <tr class="line1">
      <td>
        <b><?php print _txt('fd.link.location'); ?></b><br />
      </td>
      <td>
        <?php
          if($e) {
            if (!empty($this->viewVars['vv_co_link_location_options'] )) {
              // Print dropdown selector with location options
              print $this->Form->select('location', $this->viewVars['vv_co_link_location_options'], array('empty' => false));
            } 
          }
          else {
            print Sanitize::html($co_navigation_links[0]['CoNavigationLink']['location']);
          }

          if($this->Form->isFieldError('status')) {
            print $this->Form->error('status');
          }
        ?>
      </td>
    </tr>

    <?php
    if($e) {
      print '<tr><td></td><td>';
      print $this->Form->submit($submit_label, array('onClick' => 'on_submit()'));
      print $this->Form->button(_txt('op.reset'), array('type'=>'reset'));
      print '</td></tr>';
    }
    ?>

  </tbody>
</table>
