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
  $params['title'] = _txt('op.edit-a', array($co_group['CoGroup']['name']));

  print $this->element("pageTitleAndButtons", $params);
  include("tabs.inc");

?>

<h2 class="subtitle"><?php print _txt('op.manage.grm', array($cur_co['Co']['name'], $co_group['CoGroup']['name'])) ?></h2>

<?php
  /* Group Add Member Search
   * The following javascript is used to look up a CoPerson using the #group-add-member field that immediately follows it.
   * Note that the co_people/find mode "All" (AL) will simply bypass filters and perform a lookup against
   * all CoPerson records.
   */
?>
<script>
  $(function() {

    // Display warning for changes to co people who are not active (CO683)
    $("#co_people input[type='checkbox']").change(function() {
      if(!$(this).hasClass('status-active')) {
        generateFlash("<?php print _txt('in.groupmember.select') ?>", "information");
      }
    });

    // Turn off the "disabled" checkboxes using css and javascript so that nested group members are processed.
    // Do not apply the disabled attribute to these checkboxes (i.e. don't use disabled="disabled").
    $(".form-check-input.checkbox-replace, .form-check-label.checkbox-replace").on('click', function(e){
      e.preventDefault();
      return false;
    });


    $("#group-add-member").autocomplete({
      source: "<?php print $this->Html->url(array('controller' => 'co_people', 'action' => 'find', 'co' => $cur_co['Co']['id'], 'mode' => PeoplePickerModeEnum::All)); ?>",
      minLength: 3,
      select: function (event, ui) {
        $("#group-add-member").hide();
        $("#group-add-member-name").text(ui.item.label).show();
        $("#CoGroupMemberCoPersonId").val(ui.item.value);
        $("#CoGroupMemberCoPersonLabel").val(ui.item.label);
        $("#group-add-member-button").prop('disabled', false).focus();
        $("#group-add-member-clear-button").show();
        return false;
      },
      search: function (event, ui) {
        $("#group-add-member-search-container .co-loading-mini").show();
      },
      focus: function (event, ui) {
        event.preventDefault();
        $("#group-add-member-search-container .co-loading-mini").hide();
        $("#group-add-member").val(ui.item.label + " (" + ui.item.value + ")");
      },
      close: function (event, ui) {
        $("#group-add-member-search-container .co-loading-mini").hide();
      }
    }).autocomplete("instance")._renderItem = formatCoPersonAutoselectItem;

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

    $('[data-toggle="tooltip"]').tooltip();

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
  <span class="co-loading-mini-input-container">
    <input id="group-add-member" type="text" class="form-control-sm" placeholder="<?php print _txt('op.grm.add.placeholder'); ?>"/>
    <span class="co-loading-mini"><span></span><span></span><span></span></span>
  </span>
  <span id="group-add-member-name" style="display: none;"></span>
  <button id="group-add-member-button" class="btn btn-primary btn-sm" disabled="disabled"><?php print _txt('op.add'); ?></button>
  <button id="group-add-member-clear-button" class="btn btn-sm" style="display: none;"><?php print _txt('op.clear'); ?></button>
  <?php
    print $this->Form->end();
  ?>
  <div id="group-add-member-info" class="field-info">
    <span class="ui-icon ui-icon-info co-info"></span>
    <em><?php print _txt('op.grm.add.desc'); ?></em>
  </div>
</div>

<?php
  // Load the top search bar
  if(isset($permissions['search']) && $permissions['search'] ) {
    // Should be true if we're in this view, but we'll check just in case
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

  // Start the select form
  print $this->Form->create('CoGroupMember',
      array('url' => array('action' => 'updateGroup'),
        'inputDefaults' => array('label' => false,
          'div' => false))) . "\n";
  // beforeFilter needs CO ID
  print $this->Form->hidden('CoGroupMember.co_id', array('default' => $cur_co['Co']['id'])) . "\n";
  // Group ID must be global for isAuthorized
  print $this->Form->hidden('CoGroupMember.co_group_id', array('default' => $co_group['CoGroup']['id'])) . "\n";
?>


<div class="table-container">
  <table id="co_people">
    <thead>
      <tr>
        <th><?php print _txt('fd.name'); ?></th>
        <th><?php print _txt('fd.roles'); ?></th>
        <th><?php print _txt('fd.co_people.status'); ?></th>
        <th><?php print _txt('fd.perms'); ?></th>
        <th><?php print _txt('fd.actions'); ?></th>
      </tr>
    </thead>

    <tbody>
      <?php if(empty($co_people) && $hasFilters): ?>
        <tr>
          <td colspan="5">
            <div class="co-info-topbox">
              <em class="material-icons">info</em>
              <?php print _txt('in.co_group.people.none_filters'); ?>
            </div>
          </td>
        </tr>
      <?php elseif(empty($co_people)):?>
        <tr>
          <td colspan="5">
            <div class="co-info-topbox">
              <em class="material-icons">info</em>
              <?php print _txt('in.co_group.people.none'); ?>
            </div>
          </td>
        </tr>
      <?php else: ?>
        <?php $i = 0; ?>
        <?php foreach($co_people as $p): ?>
        <tr class="line<?php print ($i % 2)+1; ?>">
          <?php
            $isMember = isset($co_group_roles['members'][ $p['CoPerson']['id'] ])
              && $co_group_roles['members'][ $p['CoPerson']['id'] ];
            $isOwner = isset($co_group_roles['owners'][ $p['CoPerson']['id'] ])
              && $co_group_roles['owners'][ $p['CoPerson']['id'] ];
            $gmid = null;

            if($isMember) {
              $gmid = $co_group_roles['members'][ $p['CoPerson']['id'] ]['co_group_member_id'];
            } elseif($isOwner) {
              $gmid = $co_group_roles['owners'][ $p['CoPerson']['id'] ];
            }

            if($gmid) {
              print $this->Form->hidden('CoGroupMember.rows.'.$i.'.id',
                  array('default' => $gmid)) . "\n";
            }
            print $this->Form->hidden('CoGroupMember.rows.'.$i.'.co_person_id',
                array('default' => $p['CoPerson']['id'])) . "\n";
          ?>
          <td>
            <?php
              $memberName = (!empty($p['PrimaryName']) ? generateCn($p['PrimaryName']) : "(?)");
              if($permissions['viewUserCanvas']) {
                print $this->Html->link($memberName,
                  array('controller' => 'co_people',
                    'action' => 'canvas',
                    $p['CoPerson']['id']));
              } else {
                print filter_var($memberName,FILTER_SANITIZE_SPECIAL_CHARS);
              }
            ?>
          </td>
          <td>
            <?php
              // Is this from a nested group?
              if(!empty($co_group_roles['members'][ $p['CoPerson']['id'] ]['co_group_nesting_id'])) {
                $nestedGroup = filter_var($co_group_roles['members'][ $p['CoPerson']['id'] ]['co_group_nesting_name'],
                  FILTER_SANITIZE_SPECIAL_CHARS);
                if($permissions['viewNestedGroup']) {
                  $nestedGroup = $this->Html->link(
                    $co_group_roles['members'][$p['CoPerson']['id']]['co_group_nesting_name'],
                    array(
                      'controller' => 'co_groups',
                      'action' => 'nest',
                      $co_group_roles['members'][$p['CoPerson']['id']]['co_group_nesting_group_id']
                    )
                  );
                }
                print '<div class="group-member-via-nested">';
                print _txt('fd.co_group_member.member.via', array($nestedGroup));
                print ' <span class="group-member-via-nested-label">' . _txt('ct.co_group_nestings.1') . '</span>';
                print '</div>';
              } else {
                // Though membership information is somewhat redundant with the checkboxes,
                // it provides context among the nesting and inactive states as well as making
                // unfiltered lists easier to scan.
                if($isOwner) {
                  if($isMember) {
                    print _txt('fd.group.grmemown');
                  } else {
                    print _txt('fd.group.own.only');
                  }
                } elseif($isMember) {
                  print _txt('fd.group.mem');
                }
              }

              // Warn if membership is invalid
              if((!empty($co_group_roles['members'][ $p['CoPerson']['id'] ]['valid_from'])
                  && strtotime($co_group_roles['members'][ $p['CoPerson']['id'] ]['valid_from']) >= time())
                ||
                (!empty($co_group_roles['members'][ $p['CoPerson']['id'] ]['valid_through'])
                  && strtotime($co_group_roles['members'][ $p['CoPerson']['id'] ]['valid_through']) < time())) {
                print ' <span class="mr-1 badge badge-warning">' . _txt('fd.inactive') . '</span>';
              }
            ?>
          </td>
          <?php $statusClass = ' status-' . (str_replace(' ', '-', strtolower(_txt('en.status', null, $p['CoPerson']['status'])))); ?>
          <td class="<?php print $statusClass ?>">
            <?php
              print _txt('en.status', null, $p['CoPerson']['status']);
            ?>
          </td>
          <td>
            <?php
              // Note: do not actually disable checkboxes using $args['disabled'] = 'disabled' or Cake
              // will also disable the hidden field - allowing the memberships/ownerships for nested groups to be
              // temporarily stripped. Instead, hide the checkboxes in the front end with CSS and JavaScript, and
              // send values of nested group members too. We are using font-awesome here because pure CSS
              // replacement with a unicode check mark isn't very attractive.
              $disabledClass = '';
              $disabledAttributes = '';
              $checkboxReplaceClass = '';
              $checkboxReplaceBox = '';
              if(!empty($co_group_roles['members'][ $p['CoPerson']['id'] ]['co_group_nesting_id']) || $co_group['CoGroup']['auto']) {
                $disabledClass = ' disabled checkbox-replace-container';
                $disabledAttributes = ' data-toggle="tooltip" title="' . _txt('in.co_group.members.nested_noedit') . '"';
                $checkboxReplaceClass =  ' checkbox-replace checkbox-replace-' . ($isMember ? 'checked' : 'unchecked');
                $checkboxReplaceBox = '<i class="fa ' . ($isMember ? 'fa-check-square' : 'fa-square-o') . '"></i>';
              }
              print '<div class="form-group form-check form-check-inline' . $disabledClass . '"' . $disabledAttributes . '>';
              $args = array();
              $args['checked'] = $isMember;
              $args['class'] = 'form-check-input ' . $statusClass . $checkboxReplaceClass;
              print $checkboxReplaceBox;
              print $this->Form->checkbox('CoGroupMember.rows.'.$i.'.member',$args);
              $args = array();
              $args['class'] = 'form-check-label' . $checkboxReplaceClass;
              print $this->Form->label('CoGroupMember.rows.'.$i.'.member',_txt('fd.group.mem'),$args) . "\n";
              print '</div>';

              $disabledClass = '';
              $disabledAttributes = '';
              $checkboxReplaceClass = '';
              $checkboxReplaceBox = '';
              if(!empty($co_group_roles['members'][ $p['CoPerson']['id'] ]['co_group_nesting_id']) || $co_group['CoGroup']['auto']) {
                $disabledClass = ' disabled checkbox-replace-container';
                $disabledAttributes = ' data-toggle="tooltip" title="' . _txt('in.co_group.members.nested_noedit') . '"';
                $checkboxReplaceClass =  ' checkbox-replace checkbox-replace-' . ($isOwner ? 'checked' : 'unchecked');
                $checkboxReplaceBox = '<i class="fa ' . ($isOwner ? 'fa-check-square' : 'fa-square-o') . '"></i>';
              }
              print '<div class="form-group form-check form-check-inline' . $disabledClass . '"' . $disabledAttributes . '>';
              $args = array();
              $args['checked'] = $isOwner;
              $args['class'] = 'form-check-input ' . $statusClass . $checkboxReplaceClass;
              print $this->Form->checkbox('CoGroupMember.rows.'.$i.'.owner',$args);
              print $checkboxReplaceBox;
              $args = array();
              $args['class'] = 'form-check-label' . $checkboxReplaceClass;
              print $this->Form->label('CoGroupMember.rows.'.$i.'.owner', _txt('fd.group.own'),$args) . "\n";
            ?>
            </div>
          </td>
          <td class="actions">
            <?php
              // Show edit button for members and owners only - to allow for validity date editing
              // We should not be here if this is an automatic group, but test for that as well and exclude for automatic groups.
              // Do not show if the membership is due to a nesting.
              if (isset($gmid) && !$co_group['CoGroup']['auto'] && empty($co_group_roles['members'][ $p['CoPerson']['id'] ]['co_group_nesting_id'])) {
                print $this->Html->link(
                  _txt('op.edit'),
                  array(
                    'controller' => 'co_group_members',
                    'action' => 'edit',
                    $gmid
                  ),
                  array('class' => 'editbutton')
                );
              }
            ?>
          </td>
        </tr>
        <?php $i++; ?>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>

    <?php if(!empty($co_people)):?>
      <tfoot>
        <tr>
          <td colspan="4"></td>
          <td>
            <?php
              $options = array('style' => 'float:left;');
              if(!$co_group['CoGroup']['auto']){
                print $this->Form->submit(_txt('op.save'), $options);
              }
            ?>
          </td>
        </tr>
      </tfoot>
    <?php endif; ?>
  </table>
</div>

<?php
  print $this->Form->end();
  print $this->element("pagination");