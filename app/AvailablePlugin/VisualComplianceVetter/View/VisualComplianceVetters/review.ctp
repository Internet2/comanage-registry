<?php
/**
 * COmanage Registry Visual Compliance Vetter Review View
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
 * @package       registry-plugin
 * @since         COmanage Registry v4.1.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

// Convert the raw result back to JSON so we can parse it.

if(empty($vv_result['VettingResult']['raw'])) {
  return;
}

$response = json_decode($vv_result['VettingResult']['raw']);

$alertClass = "";

switch($response->smaxalert) {
  case "_R":
  case "DR":
  case "TR":
    $alertClass = "warn-level-a";
    break;
  case "_Y":
    $alertClass = "warn-level-b";
    break;
}
?>
<ul id="<?php print $this->action; ?>_visual_compliance_vetter" class="fields form-list">
  <li class="<?php print $alertClass; ?>">
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('pl.visualcompliancevetter.field.alerttype'); ?>
      </div>
    </div>
    <div class="field-info">
      <?php
        if($response->stransstatus == 'Passed') {
          print _txt('pl.visualcompliancevetter.result.passed');
        } else {
          print _txt('pl.visualcompliancevetter.result.'.$response->smaxalert);
        }
      ?>
    </div>
  </li>
</ul>
<?php foreach($response->searches as $s): ?>
<ul class="fields form-list">
  <li>
    <div class="field-name">
      <div class="field-title"><?php print _txt('pl.visualcompliancevetter.search_request'); ?></div>
    </div>
    <div class="field-info"><?php print filter_var($s->sdistributedid,FILTER_SANITIZE_SPECIAL_CHARS); ?></div>
  </li>
  
  <?php foreach(array_keys(get_object_vars($s)) as $k): ?>
  <?php if(!in_array($k, array('sdistributedid', 'results')) && !empty($s->$k)): ?>
  <li>
    <div class="field-name">
      <div class="field-title"><?php print _txt('pl.visualcompliancevetter.field.'.$k); ?></div>
    </div>
    <div class="field-info"><?php print $s->$k; ?></div>
  </li>
  <?php endif; // $s->$k ?>
  <?php endforeach; // array_keys ?>
  
  <li>
    <?php foreach($s->results as $r): ?>
    <?php
      $alertClass = "";
      
      switch($r->alerttype) {
        case "_R":
        case "DR":
        case "TR":
          $alertClass = "warn-level-a";
          break;
        case "_Y":
          $alertClass = "warn-level-b";
          break;
      }
    ?>
    <ul class="fields form-list">
      <li>
        <ul>
          <li class="<?php print $alertClass; ?>">
            <div class="field-name">
              <div class="field-title"><?php print _txt('pl.visualcompliancevetter.field.alerttype'); ?></div>
            </div>
            <div class="field-info"><?php print 
            !empty($r->alerttype) ? _txt('pl.visualcompliancevetter.result.'.$r->alerttype) : _txt('pl.visualcompliancevetter.result.passed');
            ?></div>
          </li>
          <li>
            <div class="field-name align-top">
              <div class="field-title"><?php print _txt('pl.visualcompliancevetter.field.dp_id'); ?></div>
            </div>
            <div class="field-info"><?php print filter_var($r->dp_id,FILTER_SANITIZE_SPECIAL_CHARS); ?></div>
          </li>
          <li>
            <div class="field-name align-top">
              <div class="field-title"><?php print _txt('pl.visualcompliancevetter.field.list'); ?></div>
            </div>
            <div class="field-info"><?php print filter_var($r->list,FILTER_SANITIZE_SPECIAL_CHARS); ?></div>
          </li>
          <li>
            <div class="field-name align-top">
              <div class="field-title"><?php print _txt('pl.visualcompliancevetter.field.category'); ?></div>
            </div>
            <div class="field-info"><?php print filter_var($r->category,FILTER_SANITIZE_SPECIAL_CHARS); ?></div>
          </li>
          <li>
            <div class="field-name align-top">
              <div class="field-title"><?php print _txt('pl.visualcompliancevetter.field.name'); ?></div>
            </div>
            <div class="field-info"><?php print filter_var($r->name,FILTER_SANITIZE_SPECIAL_CHARS); ?></div>
          </li>
          <li>
            <div class="field-name align-top">
              <div class="field-title"><?php print _txt('pl.visualcompliancevetter.field.notes'); ?></div>
            </div>
            <div class="field-info"><?php print filter_var($r->notes,FILTER_SANITIZE_SPECIAL_CHARS); ?></div>
          </li>
        </ul>
      </li>
    </ul>
    <?php endforeach; // $r ?>
  </li>
</ul>  
<?php endforeach; // $response->searches ?>
