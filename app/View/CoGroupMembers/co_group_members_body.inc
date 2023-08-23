<?php
/**
 * COmanage Registry CO Group Members index view
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
 * @since         COmanage Registry v4.3.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */
?>

<?php if(empty($co_group_members) && $hasFilters): ?>
  <tr>
    <td colspan="5">
      <div class="co-info-topbox">
        <em class="material-icons">info</em>
        <div class="co-info-topbox-text">
          <?php print _txt('in.co_group.people.none_filters'); ?>
        </div>
      </div>
    </td>
  </tr>
<?php elseif(empty($co_group_members)):?>
  <tr>
    <td colspan="5">
      <div class="co-info-topbox">
        <em class="material-icons">info</em>
        <div class="co-info-topbox-text">
          <?php print _txt('in.co_group.people.none'); ?>
        </div>
      </div>
    </td>
  </tr>
<?php else: ?>
  <?php $i = 0; ?>
  <?php foreach($co_group_members as $p): ?>
    <tr class="line<?php print ($i % 2)+1; ?>">
      <?php
      $isMember = filter_var($p["CoGroupMember"]["member"], FILTER_VALIDATE_BOOLEAN);
      $isOwner = filter_var($p["CoGroupMember"]["owner"], FILTER_VALIDATE_BOOLEAN);

      if($p["CoGroupMember"]["member"] || $p["CoGroupMember"]["owner"]) {
        print $this->Form->hidden('CoGroupMember.rows.'.$i.'.id', array('default' => $p["CoGroupMember"]["id"])) . PHP_EOL;
      }
      print $this->Form->hidden('CoGroupMember.rows.'.$i.'.co_person_id', array('default' => $p["CoPerson"]["id"])) . PHP_EOL;
      ?>
      <td>
        <?php
        $primary_name = array_filter(
          $p["CoPerson"]["Name"],
          static function($item) {
            return $item['primary_name'] == true;
          }
        );
        $primary_name = array_pop($primary_name);
        $memberName = (!empty($primary_name) ? generateCn($primary_name) : "(?)");
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
        if(!empty($p["CoGroupNesting"]["id"])) {
          $nestedGroup = filter_var($p["CoGroupNesting"]['CoGroup']['name'], FILTER_SANITIZE_SPECIAL_CHARS);
          if($permissions['viewNestedGroup']) {
            $nestedGroup = $this->Html->link(
              $nestedGroup,
              array(
                'controller' => 'co_groups',
                'action' => 'nest',
                $p["CoGroupNesting"]['CoGroup']['id']
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
        if((!empty($p["CoGroupMember"]['valid_from'])
            && strtotime($p["CoGroupMember"]['valid_from']) >= time())
          ||
          (!empty($p["CoGroupMember"]['valid_through'])
            && strtotime($p["CoGroupMember"]['valid_through']) < time())) {
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
      <?php if($this->action == "select"): ?>
      <td>
        <?php
        // Note: do not actually disable checkboxes using $args['disabled'] = 'disabled' or Cake
        // will also disable the hidden field - allowing the memberships/ownerships for nested groups to be
        // temporarily stripped. Instead, hide the checkboxes in the front end with CSS and JavaScript, and
        // send values of nested group members too. We are using material-icons here because pure CSS
        // replacement with a unicode check mark isn't very attractive.
        $disabledClass = '';
        $disabledAttributes = '';
        $checkboxReplaceClass = '';
        $checkboxReplaceBox = '';
        if(!empty($p["CoGroupNesting"]['CoGroup']['id']) || $co_group['CoGroup']['auto']) {
          $disabledClass = ' disabled checkbox-replace-container';
          $disabledAttributes = ' data-toggle="tooltip" title="' . _txt('in.co_group.members.nested_noedit') . '"';
          $checkboxReplaceClass =  ' checkbox-replace checkbox-replace-' . ($isMember ? 'checked' : 'unchecked');
          $checkboxReplaceBox = '<em class="material-icons">' . ($isMember ? 'check_box' : 'check_box_outline_blank') . '</em>';
        }
        print '<div class="form-group form-check form-check-inline' . $disabledClass . '"' . $disabledAttributes . '>';
        $args = array();
        $args['checked'] = $isMember;
        $args['class'] = 'form-check-input ' . $statusClass . $checkboxReplaceClass;
        print $checkboxReplaceBox;
        print $this->Form->checkbox('CoGroupMember.rows.'.$i.'.member',$args);
        $args = array();
        $args['class'] = 'form-check-label' . $checkboxReplaceClass;
        print $this->Form->label('CoGroupMember.rows.'.$i.'.member',_txt('fd.group.mem'),$args) . PHP_EOL;
        print '</div>';

        $disabledClass = '';
        $disabledAttributes = '';
        $checkboxReplaceClass = '';
        $checkboxReplaceBox = '';
        if(!empty($p["CoGroupNesting"]['CoGroup']['id']) || $co_group['CoGroup']['auto']) {
          $disabledClass = ' disabled checkbox-replace-container';
          $disabledAttributes = ' data-toggle="tooltip" title="' . _txt('in.co_group.members.nested_noedit') . '"';
          $checkboxReplaceClass =  ' checkbox-replace checkbox-replace-' . ($isOwner ? 'checked' : 'unchecked');
          $checkboxReplaceBox = '<em class="material-icons">' . ($isOwner ? 'check_box' : 'check_box_outline_blank') . '</em>';
        }
        print '<div class="form-group form-check form-check-inline' . $disabledClass . '"' . $disabledAttributes . '>';
        $args = array();
        $args['checked'] = $isOwner;
        $args['class'] = 'form-check-input ' . $statusClass . $checkboxReplaceClass;
        print $this->Form->checkbox('CoGroupMember.rows.'.$i.'.owner',$args);
        print $checkboxReplaceBox;
        $args = array();
        $args['class'] = 'form-check-label' . $checkboxReplaceClass;
        print $this->Form->label('CoGroupMember.rows.'.$i.'.owner', _txt('fd.group.own'),$args) . PHP_EOL;
        ?>
        </div>
      </td>
      <td class="actions">
        <?php
        // Show edit button for members and owners only - to allow for validity date editing
        // We should not be here if this is an automatic group, but test for that as well and exclude for automatic groups.
        // Do not show if the membership is due to a nesting.
        if (!$co_group['CoGroup']['auto'] && empty($p["CoGroupNesting"]["id"])) {
          print $this->Html->link(
            _txt('op.edit'),
            array(
              'controller' => 'co_group_members',
              'action' => 'edit',
              $p["CoGroupMember"]["id"]
            ),
            array('class' => 'editbutton')
          );
        }
        ?>
      </td>
      <?php endif; ?>
    </tr>
    <?php $i++; ?>
  <?php endforeach; ?>
<?php endif; ?>