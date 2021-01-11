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
    $("#co_people input[type='checkbox']").change(function() {
      if(this.parentElement.previousElementSibling.className != 'Active')
        generateFlash("<?php print _txt('in.groupmember.select') ?>",
                      "information");
    });
  });
</script>

<?php
  // Add breadcrumbs
  print $this->element("coCrumb");

  if($permissions['index']) {
    $args = array();
    $args['plugin'] = null;
    $args['controller'] = 'co_groups';
    $args['action'] = 'index';
    $args['co'] = $cur_co['Co']['id'];
    $this->Html->addCrumb(_txt('ct.co_groups.pl'), $args);
  }
  
  $args = array(
    'controller' => 'co_groups',
    'action' => 'edit',
    $co_group['CoGroup']['id']
  );
  $this->Html->addCrumb($co_group['CoGroup']['name'], $args);

  $this->Html->addCrumb(_txt('ct.co_group_members.pl'));

  // Add page title
  $params = array();
  $params['title'] = _txt('op.edit-a', array($co_group['CoGroup']['name']));

  print $this->element("pageTitleAndButtons", $params);
  include("tabs.inc");

?>

<div id="group-add-member-info" class="field-info">
  <span class="ui-icon ui-icon-info co-info"></span>
  <em><?php print _txt('op.grm.add.desc'); ?></em>
</div>

<h2 class="subtitle"><?php print _txt('op.manage.grm', array($cur_co['Co']['name'], $co_group['CoGroup']['name'])) ?></h2>

<?php
  /* Group Add Member Search
   * The following javascript is used to look up a CoPerson using the #group-add-member field that immediately follows it.
   * Note that the co_people/find mode "CoPerson" (CP) will simply bypass filters and perform a lookup against
   * all CoPerson records.
   */
?>
<script>
  $(function() {

    $("#group-add-member").autocomplete({
      source: "<?php print $this->Html->url(array('controller' => 'co_people', 'action' => 'find', 'co' => $cur_co['Co']['id'], 'mode' => PeoplePickerModeEnum::CoPerson)); ?>",
      minLength: 3,
      select: function (event, ui) {
        $("#group-add-member").hide();
        $("#group-add-member-name").text(ui.item.label).show();
        $("#CoGroupMemberCoPersonId").val(ui.item.value);
        $("#CoGroupMemberCoPersonLabel").val(ui.item.label);
        $("#group-add-member-button").prop('disabled', false).focus();
        $("#group-add-member-clear-button").show();
        return false;
      }
    });

    $("#group-add-member-button").click(function(e) {
      displaySpinner();
      //e.preventDefault();
      var coPersonId = $("#CoGroupMemberCoPersonId").val();
      // Though the form's behavior should preclude the need for this, let's check to see if we have what
      // appears to be a positive integer for a CoPerson ID as a baseline test
      if(/^([1-9]\d*)$/.test(coPersonId)) {
        $("#CoGroupMemberCoPersonId").submit();
      } else {
        // shouldn't get here
        alert("member ID couldn't be parsed");
        $("#group-add-member-button").prop('disabled', true);
        stopSpinner();
        return false;
      }
    });

    $("#group-add-member-clear-button").click(function() {
      stopSpinner();
      $("#group-add-member-name").hide();
      $("#CoGroupMemberCoPersonId").val("");
      $("#group-add-member-button").prop('disabled', true).focus();
      $("#group-add-member-clear-button").hide();
      $("#group-add-member").val("").show().focus();
      return false;
    });

  });
</script>
<div id="group-add-member-search-container">
  <?php
    print $this->Form->create('CoGroupMember', array('url' => array('action' => 'addMemberById'), 'inputDefaults' => array('label' => false, 'div' => false))) . "\n";
    print $this->Form->hidden('CoGroupMember.co_id', array('default' => $cur_co['Co']['id'])) . "\n";
    print $this->Form->hidden('CoGroupMember.co_group_id', array('default' => $co_group['CoGroup']['id'])) . "\n";
    print $this->Form->hidden('CoGroupMember.co_group_name', array('default' => $co_group['CoGroup']['name'])) . "\n";
    print $this->Form->hidden('CoGroupMember.co_person_id') . "\n";
    print $this->Form->hidden('CoGroupMember.co_person_label') . "\n";
    // unlock fields so we can manipulate them with JavaScript
    $this->Form->unlockField('CoGroupMember.co_person_id');
    $this->Form->unlockField('CoGroupMember.co_person_label');
  ?>
    <label for="group-add-member" class="col-form-label-sm"><?php print _txt('op.grm.add'); ?></label>
    <input id="group-add-member" type="text" class="form-control-sm" placeholder="<?php print _txt('op.grm.add.placeholder'); ?>"/>
    <span id="group-add-member-name" style="display: none;"></span>
    <button id="group-add-member-button" class="btn btn-primary btn-sm" disabled="disabled"><?php print _txt('op.add'); ?></button>
    <button id="group-add-member-clear-button" class="btn btn-sm" style="display: none;"><?php print _txt('op.clear'); ?></button>
  <?php
    print $this->Form->end();
  ?>
</div>

<ul class="widget-actions">
  <li>
    <?php
      // Return to simple listing of group memberships
      print $this->Html->link(
        _txt('op.grm.manage.return'),
        array(
          'controller' => 'co_groups',
          'action' => 'members',
          $co_group['CoGroup']['id']
        ),
        array('class' => 'backbutton')
      );
    ?>
  </li>
</ul>

<?php // Load the top search bar
  if(isset($permissions['search']) && $permissions['search'] ) { // Should be true if we're in this view, but we'll check just in case
    if(!empty($this->plugin)) {
      $fileLocation = APP . "Plugin/" . $this->plugin . "/View/CoGroupMembers/search.inc";
      if(file_exists($fileLocation))
        include($fileLocation);
    } else {
      $fileLocation = APP . "View/CoGroupMembers/search.inc";
      if(file_exists($fileLocation))
        include($fileLocation);
    }
  }
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
      <?php foreach($co_people as $p): ?>
      <tr class="line<?php print ($i % 2)+1; ?>">
        <td>
          <?php
            print $this->Html->link(generateCn($p['PrimaryName']),
                                    array('controller' => 'co_people',
                                          'action' => 'canvas',
                                          $p['CoPerson']['id']));
          ?>
        </td>
        <td class = "<?php print _txt('en.status', null, $p['CoPerson']['status']); ?>">
          <?php
            print _txt('en.status', null, $p['CoPerson']['status']);
          ?>
        </td>
        <td>
          <?php
            $isMember = isset($co_group_roles['members'][ $p['CoPerson']['id'] ])
                        && $co_group_roles['members'][ $p['CoPerson']['id'] ];
            $isOwner = isset($co_group_roles['owners'][ $p['CoPerson']['id'] ])
                       && $co_group_roles['owners'][ $p['CoPerson']['id'] ];
            $gmid = null;

            if($isMember) {
              $gmid = $co_group_roles['members'][ $p['CoPerson']['id'] ];
            } elseif($isOwner) {
              $gmid = $co_group_roles['owners'][ $p['CoPerson']['id'] ];
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

<?php
  print $this->element("pagination");