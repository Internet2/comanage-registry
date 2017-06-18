<?php
/**
 * COmanage Registry CO Group Member Select View
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
 * @since         COmanage Registry v0.1
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */
?>

<script type="text/javascript">
  $(document).ready(function () {
    // Display warning for changes to co people who are not active (CO683)
    $("input[type='checkbox']").change(function() {
      if(this.parentElement.previousElementSibling.className != 'Active')
        generateFlash("<?php print _txt('in.groupmember.select') ?>",
                      "information");
    });
  });
</script>

<?php
  // Add breadcrumbs
  print $this->element("coCrumb");

  $args = array();
  $args['plugin'] = null;
  $args['controller'] = 'co_groups';
  $args['action'] = 'index';
  $args['co'] = $cur_co['Co']['id'];
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
  $params['title'] = _txt('op.grm.edit', array($cur_co['Co']['name'], $co_group['CoGroup']['name']));

  print $this->element("pageTitleAndButtons", $params);

?>

<div class="table-container">
  <table id="co_people">
    <thead>
      <tr>
        <th><?php print _txt('fd.name'); ?></th>
        <th><?php print _txt('fd.co_people.status'); ?></th>
        <th><?php print _txt('fd.perms'); ?></th>
      </tr>
      <?php
        print $this->Form->create('CoGroupMember',
                                  array('url' => array('action' => 'updateGroup'),
                                        'inputDefaults' => array('label' => false,
                                                                 'div' => false))) . "\n";
        // beforeFilter needs CO ID
        print $this->Form->hidden('CoGroupMember.co_id', array('default' => $cur_co['Co']['id'])) . "\n";
        // Group ID must be global for isAuthorized
        print $this->Form->hidden('CoGroupMember.co_group_id', array('default' => $co_group['CoGroup']['id'])) . "\n";
      ?>
    </thead>

    <tbody>
      <?php $i = 0; ?>
      <?php foreach ($co_people as $p): ?>
      <tr class="line<?php print ($i % 2)+1; ?>">
        <td>
          <?php
            print $this->Html->link(generateCn($p['PrimaryName']),
                                    array('controller' => 'co_people',
                                          'action' => 'canvas',
                                          $p['CoPerson']['id'],
                                          'co' => $cur_co['Co']['id']));
          ?>
        </td>
        <td class = "<?php print _txt('en.status', null, $p['CoPerson']['status']); ?>">
          <?php
            print _txt('en.status', null, $p['CoPerson']['status']);
          ?>
        </td>
        <td>
          <?php
            $isMember = isset($co_group_roles['members'][$p['CoPerson']['id']])
                        && $co_group_roles['members'][$p['CoPerson']['id']];
            $isOwner = isset($co_group_roles['owners'][$p['CoPerson']['id']])
                       && $co_group_roles['owners'][$p['CoPerson']['id']];
            $gmid = null;

            if($isMember) {
              $gmid = $co_group_roles['members'][$p['CoPerson']['id']];
            } elseif($isOwner) {
              $gmid = $co_group_roles['owners'][$p['CoPerson']['id']];
            }

            if($gmid) {
              print $this->Form->hidden('CoGroupMember.rows.'.$i.'.id',
                                        array('default' => $gmid)) . "\n";
            }
            print $this->Form->hidden('CoGroupMember.rows.'.$i.'.co_person_id',
                                     array('default' => $p['CoPerson']['id'])) . "\n";
            print $this->Form->checkbox('CoGroupMember.rows.'.$i.'.member',
                                        array('checked' => $isMember, 'disabled' => $co_group['CoGroup']['auto']));
            print $this->Form->label('CoGroupMember.rows.'.$i.'.member',_txt('fd.group.mem')) . "\n";
            print $this->Form->checkbox('CoGroupMember.rows.'.$i.'.owner',
                                        array('checked' => $isOwner, 'disabled' => $co_group['CoGroup']['auto']));
            print $this->Form->label('CoGroupMember.rows.'.$i.'.owner', _txt('fd.group.own')) . "\n";
          ?>
        </td>
      </tr>
      <?php $i++; ?>
      <?php endforeach; ?>
    </tbody>

    <tfoot>
      <tr>
        <th colspan="3">
        </th>
      </tr>
      <tr>
        <td colspan="2"></td>
        <td>
          <?php
            $options = array('style' => 'float:left;');
            if(!$co_group['CoGroup']['auto']){
              print $this->Form->submit(_txt('op.save'), $options);
            }
            print $this->Form->end();
          ?>
        </td>
      </tr>
    </tfoot>
  </table>
</div>