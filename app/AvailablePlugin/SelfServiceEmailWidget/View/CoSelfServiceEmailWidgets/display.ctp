<?php
/**
 * COmanage Registry Self Service Email Widget Display View
 *
 * This widget repurposes the Service Portal by directly
 * rendering the service portal URL (as provided by the controller).
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
 * @since         COmanage Registry v3.2.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

  // Figure out the widget ID so we can overwrite the dashboard's widget div
  $divid = $vv_config['CoSelfServiceEmailWidget']['co_dashboard_widget_id'];
?>

<div class="cm-self-service-widget cm-self-service-widget-email">
  <div class="cm-self-service-widget-display">
    <ul class="cm-self-service-widget-field-list">
      <li>
        arlen@dogenmedia.com
        <?php 
          print $this->Badge->badgeIt(
              _txt('fd.name.primary_name'),
              $this->Badge->getBadgeColor('Primary'),
              false,
              true
            );
          ?>
      </li>
      <li>adhjdm@dogenmedia.com</li>
    </ul>
    <div class="cm-self-service-submit-buttons">
      <button class="btn btn-small btn-primary"><?php print _txt('op.edit'); ?></button>
    </div>  
  </div>
  <div class="cm-self-service-widget-form hidden">
    <p>Form here!</p>
    <div class="cm-self-service-submit-buttons">
      <button class="btn btn-small cm-self-service-widget-cancel"><?php print _txt('op.cancel'); ?></button>
      <button class="btn btm-small btn-primary cm-self-service-widget-save"><?php print _txt('op.save'); ?></button>
    </div>
  </div>
</div>


<?php
// Permissions are handled by the mvpa element

