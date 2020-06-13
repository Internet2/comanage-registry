<?php
  /*
   * COmanage Registry Inline Edit Controls
   * Used to generate inline edit actions for a field
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
   * @since         COmanage Registry v3.3.0
   * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
   */
?>

<?php
  /**
   * @param $fieldName  - String used to build unique IDs and ARIA attributes for the controls
   * @param $actions    - Array of action names, used to specify which actions to include in the output
   * @param $visible    - Boolean true or empty; if true, flag the controls to be visible by default
   */

  $cssClasses = 'cm-ief-controls';
  if (!empty($visible) && $visible) {
    $cssClasses .= ' cm-ief-controls-visible';
  }

?>

<div class="<?php print $cssClasses ?>">
  <div class="cm-ief-actions" id="cm-ief-actions-<?php print $fieldName ?>">
    <?php
      // Print out the actions in the order defined in the call to this element.
      // Action JavaScript behaviors are defined on each calling page.
      foreach ($actions as $action) {
        if($action == 'remove') {
          print '<button class="cm-ief-button cm-ief-button-remove" id="cm-ief-button-remove-' . $fieldName . '" title="' . _txt('op.remove') . '">';
          print '<em class="material-icons">clear</em>';
          print '</button>';
        }
      }
    ?>
  </div>
</div>