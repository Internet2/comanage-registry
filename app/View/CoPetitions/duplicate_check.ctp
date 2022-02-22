<?php
/**
 * COmanage Registry CO Petition Duplicate Check View
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

// A simple local function to render an object full of match attributes in a basic list
function render_match_attributes($o) {
  print "<ul>\n";
  
  foreach($o as $k => $v) {
    if(is_array($v) || is_object($v)) {
      print "<li>$k</li>\n";
      render_match_attributes($v);
    } else {
      print "<li>$k: $v</li>\n";
    }
  }
  
  print "</ul>\n";
}
$params = array();
$params['title'] = _txt('ct.co_petition.duplicate.check');
print $this->element("pageTitleAndButtons", $params);
?>

<div class="co-info-topbox">
  <em class="material-icons">info</em>
  <?php print _txt('in.ef.match'); ?>
</div>

<div id="reconcile-table-container">
  <table id="reconcile-table" class="side-by-side">
    <thead>
    <tr>
      <td class="empty"></td>
      <?php $i = 1; ?>
      <?php foreach($vv_matches as $m): ?>
        <th class="col-names" scope="col">
          <?php print ($m->referenceId != 'new') ? _txt('fd.match.suggestion', array($i++)) : _txt('fd.match.new.record');  ?>
        </th>
      <?php endforeach; ?>
    </tr>
    </thead>
    <tbody>
    <!-- Reference IDs -->
    <tr>
      <th class="attr-title" scope="row">
        <?php print _txt('fd.match.reference.ids'); ?>
      </th>
      <?php foreach($vv_matches as $m): ?>
        <td class="reference-ids">
          <?php print ($m->referenceId != 'new') ? $m->referenceId : _txt('fd.new'); ?>
        </td>
      <?php endforeach; ?>
    </tr>
    <!-- Actions -->
    <tr>
      <th class="attr-title" scope="row">
        <?php print _txt('op.action'); ?>
      </th>
      <?php foreach($vv_matches as $m): ?>
        <td class="reconcile-actions">
          <?php
            $targetUrl = array(
              'controller' => 'co_petitions',
              'action'     => 'duplicateCheck',
              $vv_co_petition_id
            );
            $targetOptions = array(
              'class' => 'btn btn-primary',
              'confirm' => ($m->referenceId != 'new') ? _txt('op.match.select.confirm') : _txt('op.match.add.confirm'),
              'data'  => array(
                'referenceId' => $m->referenceId
              )
            );
            if(!empty($vv_petition_token)) {
              $targetUrl['token'] = $vv_petition_token;
            }
            $linkText = ($m->referenceId != 'new') ? _txt('op.select.person') : _txt('op.add.new.literal');
            print $this->Form->postLink($linkText, $targetUrl, $targetOptions); 
          ?>
        </td>
      <?php endforeach; ?>
    </tr>
    <!-- System of Record (SOR) -->
    <tr>
      <th class="attr-title" scope="row"><?php print _txt('fd.match.sor'); ?></th>
      <?php $totalRecords = 0; ?>
      <?php foreach($vv_matches as $m): ?>
        <td>
          <div class="reconcile-fields">
            <?php foreach($m->sorRecords as $s): ?>
              <?php $totalRecords++; ?>
              <span class="reconcile-sor-label">
                <?php if(!empty($s->meta->sorLabel)) print $s->meta->sorLabel; ?>
              </span>
            <?php endforeach; // $s ?>
          </div>
        </td>
      <?php endforeach; // $m ?>
    </tr>
    <!-- System of Record ID (SorID) -->
    <tr>
      <th class="attr-title" scope="row"><?php print _txt('fd.sorid'); ?></th>
      <?php foreach($vv_matches as $m): ?>
        <td>
          <div class="reconcile-fields">
            <?php foreach($m->sorRecords as $s): ?>
              <span class="reconcile-sor-id">
                <?php if(!empty($s->meta->sorId)) print $s->meta->sorId; ?>
              </span>  
            <?php endforeach; // $s ?>
          </div>  
        </td>
      <?php endforeach; // $m ?>
    </tr>
    <!-- Match ID -->
    <tr>
      <th class="attr-title" scope="row"><?php print _txt('fd.match.id'); ?></th>
      <?php foreach($vv_matches as $m): ?>
        <td>
          <div class="reconcile-fields">
            <?php foreach($m->sorRecords as $s): ?>
              <span class="reconcile-match-id">
                <?php if(!empty($s->meta->matchRequest)) print $s->meta->matchRequest; ?>
              </span>  
            <?php endforeach; // $s ?>
          </div>  
        </td>
      <?php endforeach; // $m ?>
    </tr>
    <!-- MATCH ATTRIBUTES -->
    <?php
    function render_match_attributes2($o) {
      print "<ul>\n";
      foreach($o as $k => $v) {
        if(is_array($v) || is_object($v)) {
          print "<li>$k</li>\n";
          render_match_attributes($v);
        } else {
          print "<li>$k: $v</li>\n";
        }
      }
      print "</ul>\n";
    }
    ?>
    <tr>
      <th class="attr-title" scope="row"><?php print _txt('fd.attrs.match'); ?></th>
      <?php foreach($vv_matches as $m): ?>
        <td>
          <div class="reconcile-fields">
            <?php foreach($m->sorRecords as $s): ?>
              <?php render_match_attributes($s->sorAttributes); ?>
            <?php endforeach; // $s ?>
          </div>
        </td>
      <?php endforeach; // $m ?>
    </tr>
    </tbody>
  </table>
</div>
