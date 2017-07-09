<?php
/**
 * COmanage Registry CO MediaWiki Provisioning Target Fields
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
 * @package       registry-plugin
 * @since         COmanage Registry v3.1.0
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
  
  print $this->Form->hidden('co_id', array('default' => $cur_co['Co']['id'])) . "\n";
  print $this->Form->hidden('co_provisioning_target_id', array('default' => $vv_ptid)) . "\n";
?>

<div class="ui-state-highlight ui-corner-all" style="margin-top: 20px; padding: 0 .7em;"> 
  <p>
    <span class="ui-icon ui-icon-info" style="float: left; margin-right: .3em;"></span>
    <strong><?php print _txt('pl.mediawikiprovisioner.info'); ?></strong>
  </p>
</div>
<br />
<table id="<?php print $this->action; ?>_co_media_wiki_provisioner_target" class="ui-widget">
  <tbody>
    <tr class="line1">
      <td>
        <?php print _txt('pl.mediawikiprovisioner.api_url'); ?><font class="required">*</font><br />
        <font class="desc"><?php print _txt('pl.mediawikiprovisioner.api_url.desc'); ?></font>
      </td>
      <td>
        <?php print ($e ? $this->Form->input('api_url', array('size' => 64)) : filter_var($co_media_wiki_provisioner_targets[0]['CoMediaWikiProvisionerTarget']['api_url'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
      </td>
    </tr>
    <tr class="line2">
      <td>
        <?php print _txt('pl.mediawikiprovisioner.consumer_key'); ?><font class="required">*</font><br />
        <font class="desc"><?php print _txt('pl.mediawikiprovisioner.consumer_key.desc'); ?></font>
      </td>
      <td>
        <?php print ($e ? $this->Form->input('consumer_key', array('size' => 64)) : filter_var($co_media_wiki_provisioner_targets[0]['CoMediaWikiProvisionerTarget']['consumer_key'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
      </td>
    </tr>
    <tr class="line1">
      <td>
        <?php print _txt('pl.mediawikiprovisioner.consumer_secret'); ?><font class="required">*</font><br />
        <font class="desc"><?php print _txt('pl.mediawikiprovisioner.consumer_secret.desc'); ?></font>
      </td>
      <td>
        <?php print ($e ? $this->Form->input('consumer_secret', array('size' => 64)) : filter_var($co_media_wiki_provisioner_targets[0]['CoMediaWikiProvisionerTarget']['consumer_secret'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
      </td>
    </tr>
    <tr class="line2">
      <td>
        <?php print _txt('pl.mediawikiprovisioner.access_token'); ?><font class="required">*</font><br />
        <font class="desc"><?php print _txt('pl.mediawikiprovisioner.access_token.desc'); ?></font>
      </td>
      <td>
        <?php print ($e ? $this->Form->input('access_token') : filter_var($co_media_wiki_provisioner_targets[0]['CoMediaWikiProvisionerTarget']['access_token'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
      </td>
    </tr>
    <tr class="line1">
      <td>
        <?php print _txt('pl.mediawikiprovisioner.access_secret'); ?><font class="required">*</font><br />
        <font class="desc"><?php print _txt('pl.mediawikiprovisioner.access_secret.desc'); ?></font>
      </td>
      <td>
        <?php print ($e ? $this->Form->input('access_secret', array('size' => 64)) : filter_var($co_media_wiki_provisioner_targets[0]['CoMediaWikiProvisionerTarget']['access_secret'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
      </td>
    </tr>
    <tr class="line2">
      <td>
        <?php print _txt('pl.mediawikiprovisioner.user_name_identifier'); ?><font class="required">*</font><br />
        <font class="desc"><?php print _txt('pl.mediawikiprovisioner.user_name_identifier.desc'); ?></font>
      </td>
      <td>
        <?php 
          if($e) {
            $args = array();
            $args['empty'] = '(choose one)';
            print $this->Form->select('user_name_identifier', $identifier_types, $args);
          } else {
            print filter_var($co_media_wiki_provisioner_targets[0]['CoMediaWikiProvisionerTarget']['user_name_identifier'],FILTER_SANITIZE_SPECIAL_CHARS);
          }
        ?>
      </td>
    </tr>
    <tr>
      <td>
        <i><font class="required"><?php print _txt('fd.req'); ?></font></i><br />
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
