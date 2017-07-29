<?php
/**
 * COmanage Registry Enrollment Flow Steps
 * Displayed with petitions in the right sidebar
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
 * @since         COmanage Registry v1.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */
?>
<div class="sidebar-content">
  <div id="enrollmentFlowSteps">
    <h3><?php print _txt('ct.co_enrollment_flows.1') ?></h3>
    <ul>
      <?php
      foreach($enrollmentFlowSteps as $flow => $step) {
        print '<li class="' . $step['state'] . '">';
        switch ($step['state']) {
          case 'complete':
            print '<em class="material-icons">done</em>';
            break;
          case 'selected':
            print '<em class="material-icons">forward</em>';
            break;
          case 'stopped':
            print '<em class="material-icons">cancel</em>';
            break;
          case 'incomplete':
            break;
        }
        print '<span class="stepText">' . $step['title'] . '</span>';
        print '</li>';
      }
      ?>
    </ul>
  </div>
</div>