<?php
/**
 * COmanage Registry Organizational Identity Sources Fields
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

  // Determine if fields are editable
  $e = false;
  
  if(($this->action == "add" && $permissions['add']) || ($this->action == "edit" && $permissions['edit']))
    $e = true;
    
  // We shouldn't get here if we don't have at least read permission, but check just in case
  
  if(!$e && !$permissions['view'])
    return false;

  // Add breadcrumbs
  print $this->element("coCrumb");
  $args = array();
  $args['plugin'] = null;
  $args['controller'] = 'org_identity_sources';
  $args['action'] = 'index';
  $this->Html->addCrumb(_txt('ct.org_identity_sources.pl'), $args);
  $crumbTxt = _txt('op.' . $this->action . '-a', array(_txt('ct.org_identity_sources.1')));
  $this->Html->addCrumb($crumbTxt);
  
  if($e) {
    // Populate CO ID if appropriate
    
    if(!$pool_org_identities) {
      print $this->Form->hidden('OrgIdentitySource.co_id',
                               array('default' => $cur_co['Co']['id'])). "\n";
    }
  }
?>
<?php if(empty($plugins)): ?>
<div class="ui-state-highlight ui-corner-all co-info-topbox">
  <p>
    <span class="ui-icon ui-icon-info co-info"></span>
    <strong><?php print _txt('er.plugin.none', array(_txt('ct.org_identity_sources.pl'))); ?></strong>
  </p>
</div>
<br />
<?php else: /* plugins */ ?>
<table id="<?php print $this->action; ?>_org_identity_source" class="ui-widget">
  <tbody>
    <tr class="line1">
      <td>
        <?php print _txt('fd.desc'); ?><span class="required">*</span>
      </td>
      <td>
        <?php print ($e ? $this->Form->input('description') : Sanitize::html($org_identity_sources[0]['OrgIdentitySource']['description'])); ?>
      </td>
    </tr>
    <tr class="line2">
      <td>
        <?php print _txt('fd.plugin'); ?><span class="required">*</span><br />
        <span class="descr"><?php print _txt('fd.plugin.warn', array(_txt('ct.org_identity_sources.1'))); ?></span>
      </td>
      <td>
        <?php
          switch($this->action) {
            case 'add':
              print $this->Form->input('plugin');
              break;
            case 'edit':
              print $this->Form->input('plugin', array('disabled' => true));
              // We need to re-include the value as hidden since it can't be changed (but is required by the model)
              print $this->Form->hidden('plugin',
                                        array('default' => Sanitize::html($org_identity_sources[0]['OrgIdentitySource']['plugin']))) . "\n";
              break;
            default:
              print Sanitize::html($org_identity_sources[0]['OrgIdentitySource']['plugin']);
              break;
          }
          
          if($this->action == 'edit') {
            // And render a link to the plugin target
            
            $pl = Inflector::underscore(Sanitize::html($org_identity_sources[0]['OrgIdentitySource']['plugin']));
            $plmodel = Sanitize::html($org_identity_sources[0]['OrgIdentitySource']['plugin']);
            $plm = Inflector::tableize($plmodel);
            
            print "<br />" . $this->Html->link(_txt('op.config'),
                                               array(
                                                 'plugin' => $pl,
                                                 'controller' => $plm,
                                                 'action' => 'edit',
                                                 $org_identity_sources[0][$plmodel]['id']
                                               ),
                                               array('class' => 'configurebutton'));
          }
        ?>
      </td>
    </tr>
    <tr class="line1">
      <td>
        <?php print _txt('fd.status'); ?>
      </td>
      <td>
        <?php
          global $cm_lang, $cm_texts;
          $attrs['value'] = (isset($org_identity_sources[0]['OrgIdentitySource']['status'])
                             ? $org_identity_sources[0]['OrgIdentitySource']['status']
                             : SuspendableStatusEnum::Active);
          $attrs['empty'] = false;
          
          if($e) {
            print $this->Form->select('status',
                                      $cm_texts[ $cm_lang ]['en.status.susp'],
                                      $attrs);
            
            if($this->Form->isFieldError('status')) {
              print $this->Form->error('status');
            }
          } else {
            print _txt('en.status', null, $org_identity_sources[0]['OrgIdentitySource']['status']);
          }
        ?>
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
<?php endif; /* plugins */
