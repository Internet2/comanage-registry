<?php
/**
 * COmanage Registry Org Identity Source Inventory View
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

  // Add breadcrumbs
  print $this->element("coCrumb");
  
  $args = array();
  $args['plugin'] = null;
  $args['controller'] = 'org_identity_sources';
  $args['action'] = 'index';
  $args['co'] = $cur_co['Co']['id'];
  $this->Html->addCrumb(_txt('ct.org_identity_sources.pl'), $args);
  
  $this->Html->addCrumb($title_for_layout);

  // Add page title
  $params = array();
  $params['title'] = $title_for_layout;

  print $this->element("pageTitleAndButtons", $params);
?>
<?php if(!empty($vv_source_keys)): ?>
<p><?php print _txt('rs.found.cnt', array(count($vv_source_keys))); ?></p>
<?php endif; ?>

<table id="org_identity_source_inventory" class="ui-widget">
  <thead>
    <tr class="ui-widget-header">
      <th><?php print _txt('fd.ois.record'); ?></th>
      <th><?php print _txt('fd.actions'); ?></th>
    </tr>
  </thead>
  
  <?php if(!empty($vv_source_keys)): ?>
  <tbody>
    <?php $i = 0; ?>
    <?php foreach($vv_source_keys as $skey): ?>
    <tr class="line<?php print ($i % 2)+1; ?>">
      <td>
        <?php
          print filter_var($skey,FILTER_SANITIZE_SPECIAL_CHARS);
        ?>
      </td>
      <td>
        <?php
          if($permissions['view']) {
            $args = array(
              'controller' => 'org_identity_sources',
              'action'     => 'retrieve',
              $vv_org_identity_source['id'],
              'key'        => $skey
            );
            
            print $this->Html->link(_txt('op.view'),
                                    $args,
                                    array('class' => 'viewbutton')) . "\n";
          }
        ?>
        <?php ; ?>
      </td>
    </tr>
    <?php $i++; ?>
    <?php endforeach; ?>
  </tbody>
  <?php endif; // vv_source_keys ?>
  
  <tfoot>
    <tr class="ui-widget-header">
      <th colspan="2">
      </th>
    </tr>
  </tfoot>
</table>
