<?php
/**
 * COmanage Registry CO Group Member Index View
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
 * @since         COmanage Registry v4.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */
?>

<?php
  // Add breadcrumbs
  print $this->element("coCrumb");

  $args = array();
  $args['plugin'] = null;
  $args['controller'] = 'co_groups';
  $args['action'] = 'index';
  $args['co'] = $cur_co['Co']['id'];
  $args['search.auto'] = 'f';
  $args['search.noadmin'] = '1';
  $this->Html->addCrumb(_txt('ct.co_groups.pl'), $args);

  $args = array(
    'controller' => 'co_groups',
    'action' => 'edit',
    $co_group['CoGroup']['id']
  );
  $this->Html->addCrumb($co_group['CoGroup']['name'], $args);

  $this->Html->addCrumb(_txt('ct.co_group_members.pl'));

  // Add page title
  $params = array();
  $params['title'] = _txt('op.view-a', array($co_group['CoGroup']['name']));

  print $this->element("pageTitleAndButtons", $params);
  include("tabs.inc");

?>

<h2 class="subtitle"><?php print _txt('ct.co_group_members.pl') ?></h2>

<?php if($co_group['CoGroup']['auto']): ?>
  <div class="co-info-topbox">
    <em class="material-icons">info</em>
    <div class="co-info-topbox-text">
      <?php print _txt('in.co_group.auto', array($cur_co['Co']['id'])); ?>
    </div>
  </div>
<?php endif; ?>

<?php
// Search Block
if(!empty($vv_search_fields)) {
  print $this->element('search', array('vv_search_fields' => $vv_search_fields));
}
?>
  
<div class="table-container">
  <table id="groupMembers" class="common-table">
    <thead>
      <tr>
        <th><?php print _txt('fd.name'); ?></th>
        <th><?php print _txt('fd.roles'); ?></th>
        <th><?php print _txt('fd.co_people.status'); ?></th>
      </tr>
    </thead>

    <tbody>
    <?php
      if(!empty($co_group_members)) {
        include("co_group_members_body.inc");
      } elseif (!empty($co_people)) {
        include("co_people_body.inc");
      }
    ?>
    </tbody>
  </table>
</div>

<?php
  print $this->element("pagination");