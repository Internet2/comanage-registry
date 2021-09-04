<?php
/**
 * COmanage Registry Standard View View
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

  // Get a pointer to our model
  $model = $this->name;
  $req = Inflector::singularize($model);
  $modelpl = Inflector::tableize($req);
  $modelu = Inflector::underscore($req);
  
  // Get a pointer to our data
  $d = $$modelpl;

  // Add page title
  $params = array();
  $params['title'] = $title_for_layout;

  // Add top links
  $params['topLinks'] = array();

  // If user has edit permission, offer an edit button in the sidebar
  if(!empty($permissions['edit']) && $permissions['edit']) {

    // special case co_people
    $editAction = 'edit';
    if ($modelpl == 'co_people') {
      $editAction = 'canvas';
    }

    $a = array('controller' => $modelpl, 'action' => $editAction, $d[0][$req]['id']);

    if(empty($d[0]['OrgIdentity']['OrgIdentitySourceRecord']['id'])
       && empty($d[0][$req]['source_'.$modelu.'_id'])) {
      // Add edit button to the top links, except for attributes attached to
      // an Org Identity that came from an Org Identity Source.
      $params['topLinks'][] = $this->Html->link(
        _txt('op.edit'),
        $a,
        array('class' => 'editbutton')
      );
    }
  }

  // Add locally configured page buttons
  if(!empty($this->plugin)) {
    if(file_exists(APP . "Plugin/" . $this->plugin . "/View/" . $model . "/buttons.inc")) {
      include(APP . "Plugin/" . $this->plugin . "/View/" . $model . "/buttons.inc");
    } elseif(file_exists(LOCAL . "Plugin/" . $this->plugin . "/View/" . $model . "/buttons.inc")) {
      include(LOCAL . "Plugin/" . $this->plugin . "/View/" . $model . "/buttons.inc");
    }
  } else {
    if(file_exists(APP . "View/" . $model . "/buttons.inc")) {
      include(APP . "View/" . $model . "/buttons.inc");
    }
  }

  print $this->element("pageTitleAndButtons", $params);
  if(file_exists(APP . "View/" . $model . "/tabs.inc")) {
    include(APP . "View/" . $model . "/tabs.inc");
  }
?>
<?php if(!empty($d[0]['OrgIdentity']['OrgIdentitySourceRecord']['description'])): ?>
<div class="ui-state-highlight ui-corner-all co-info-topbox">
  <p>
    <span class="ui-icon ui-icon-info co-info"></span>
    <strong><?php
      print _txt('op.orgid.edit.ois', array($d[0]['OrgIdentity']['OrgIdentitySourceRecord']['description']));
    ?></strong>
  </p>
</div>
<br />
<?php elseif(!empty($d[0][$req]['source_'.$modelu.'_id'])): ?>
<div class="ui-state-highlight ui-corner-all co-info-topbox">
  <p>
    <span class="ui-icon ui-icon-info co-info"></span>
    <strong><?php print _txt('op.pipeline.edit.ois'); ?></strong>
  </p>
</div>
<br />
<?php endif; // readonly ?>
<?php
  // Output the fields
  print '<div class="innerContent">';
  if(!empty($this->plugin)) {
    if(file_exists(APP . "Plugin/" . $this->plugin . "/View/" . $model . "/fields.inc")) {
      include(APP . "Plugin/" . $this->plugin . "/View/" . $model . "/fields.inc");
    } elseif(file_exists(LOCAL . "Plugin/" . $this->plugin . "/View/" . $model . "/fields.inc")) {
      include(LOCAL . "Plugin/" . $this->plugin . "/View/" . $model . "/fields.inc");
    }
  } else {
    include(APP . "View/" . $model . "/fields.inc");
  }
  print '</div>';

?>
