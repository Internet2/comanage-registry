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
?>
<div class="co-info-topbox">
  <em class="material-icons">info</em>
  <?php print _txt('in.ef.match'); ?>
</div>
<?php foreach($vv_matches as $m): ?>
  <?php
    $targetUrl = array(
      'controller' => 'co_petitions',
      'action'     => 'duplicateCheck',
      $vv_co_petition_id
    );
    
    $targetOptions = array(
      'class' => 'linkbutton',
      'data'  => array(
        'referenceId' => $m->referenceId
      )
    );
    
    if(!empty($vv_petition_token)) {
      $targetUrl['token'] = $vv_petition_token;
    }
  ?>
  <div class="table-container">
  <table>
    <thead>
      <tr>
        <td colspan="5">
          <?php print $this->Form->postLink(_txt('op.select-a', array($m->referenceId)), $targetUrl, $targetOptions); ?>
        </td>
      </tr>
    </thead>
    
    <tbody>
      <tr>
        <th><?php print _txt('fd.match.sor'); ?></th>
        <?php foreach($m->sorRecords as $s): ?>
        <td><?php if(!empty($s->meta->sorLabel)) print $s->meta->sorLabel; ?></td>
        <?php endforeach; // $s ?>
      </tr>
      <tr>
        <th><?php print _txt('fd.sorid'); ?></th>
        <?php foreach($m->sorRecords as $s): ?>
        <td><?php if(!empty($s->meta->sorId)) print $s->meta->sorId; ?></td>
        <?php endforeach; // $s ?>
      </tr>
      <tr>
        <th><?php print _txt('fd.match.id'); ?></th>
        <?php foreach($m->sorRecords as $s): ?>
        <td><?php if(!empty($s->meta->matchRequest)) print $s->meta->matchRequest; ?></td>
        <?php endforeach; // $s ?>
      </tr>
      <tr>
        <th><?php print _txt('fd.attrs.match'); ?></th>
        <?php foreach($m->sorRecords as $s): ?>
        <td>
        <?php render_match_attributes($s->sorAttributes); ?>
        </td>
        <?php endforeach; // $s ?>
      </tr>
    </thead>
  </table>
  </div>
<?php endforeach; // $m ?>