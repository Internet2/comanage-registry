<?php
/**
 * COmanage Registry Org Identity Source Filters Fields
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
 * @link          https://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v4.1.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

  // Determine if fields are editable
  $e = false;
  
  if(($this->action == "add" && $permissions['add']) || ($this->action == "edit" && $permissions['edit']))
    $e = true;
    
  // We shouldn't get here if we don't have at least read permission, but check just in case
  
  if(!$e && !$permissions['view'])
    return(false);

  // Add breadcrumbs
  print $this->element("coCrumb");
  $args = array();
  $args['plugin'] = null;
  $args['controller'] = 'org_identity_sources';
  $args['action'] = 'index';
  $args['co'] = $cur_co['Co']['id'];
  $this->Html->addCrumb(_txt('ct.org_identity_sources.pl'), $args);
  
  $args = array();
  $args['plugin'] = null;
  $args['controller'] = 'org_identity_sources';
  $args['action'] = 'edit';
  $args[] = $vv_ois_id;
  $this->Html->addCrumb($vv_ois_name, $args);
  
  $args = array();
  $args['plugin'] = null;
  $args['controller'] = 'org_identity_source_filters';
  $args['action'] = 'index';
  $args['oisid'] = $vv_ois_id;
  $this->Html->addCrumb(_txt('ct.org_identity_source_filters.pl'), $args);
  
  if ($this->action == 'edit') {
    $this->Html->addCrumb(_txt('op.edit'));
  }
  if ($this->action == 'view') {
    $this->Html->addCrumb(_txt('op.view'));
  }
  if ($this->action == 'add') {
    $this->Html->addCrumb(_txt('op.add'));
  }
  
  if($this->action != 'view') {
    // Populate cross references
    print $this->Form->hidden('org_identity_source_id', array('default' => $vv_ois_id)) . "\n";
  }
  
  $l = 1;
?>
<div class="table-container">
  <table id="<?php print $this->action; ?>_org_identity_source_filters">
    <tbody>
      <tr class="line<?php print ($l % 2); $l++; ?>">
        <td>
          <b><?php print _txt('ct.data_filters.1'); ?></b></strong><font class="required">*</font>
        </td>
        <td>
          <?php
            $attrs = array();
            $attrs['value'] = (isset($org_identity_source_filters)
                               ? $org_identity_source_filters[0]['OrgIdentitySourceFilter']['data_filter_id']
                               : null);
            $attrs['empty'] = false;

            if($e) {
              print $this->Form->select('data_filter_id',
                                        $vv_available_filters,
                                        $attrs);
              
              if($this->Form->isFieldError('data_filter_id')) {
                print $this->Form->error('data_filter_id');
              }
            } else {
              print $vv_avail_filters[ $org_identity_source_filters[0]['OrgIdentitySourceFilter']['data_filter_id'] ];
            }
          ?>
        </td>
      </tr>
      <tr class="line<?php print ($l % 2); $l++; ?>">
        <td>
          <b><?php print _txt('fd.order'); ?></b><br />
          <span class="descr"><?php print _txt('fd.order.filter.desc'); ?></span>
        </td>
        <td>
          <?php print ($e
            ? $this->Form->input('ordr')
            : filter_var($org_identity_source_filters[0]['OrgIdentitySourceFilter']['ordr'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
        </td>
      </tr>
    </tbody>
    <tfoot>
      <tr>
        <td>
          <em class="required"><?php print _txt('fd.req'); ?></em>
        </td>
        <td>
          <?php
            if($e) {
              print $this->Form->submit($submit_label);
            }
          ?>
        </td>
      </tr>
    </tfoot>
  </table>
</div>

<?php
  print $this->element("changelog");