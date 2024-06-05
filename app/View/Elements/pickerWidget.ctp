<?php
/*
 * COmanage Registry Picker Widget
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

/*
 * $inputId         , string (required)
 * $hidden          , boolean (required)
 * $placeholder     , string (required)
 * $fieldDescription,  string (required)
 *
 * */

$isRequired = $isRequired ?? false;

?>


<div class="cm-ief-widget <?php print $hidden ? 'hidden' : '' ?>">
  <label for="<?php print $inputId?>"><?php print $label?>
    <?php if($isRequired): ?>
    <span class="required">*</span>
    <?php endif ?>
  </label>
  <div class="ui-widget">
    <span class="co-loading-mini-input-container">
      <input id="<?php print $inputId?>"
             type="text"
             placeholder="<?php print $placeholder?>"
             class="form-control"
             <?php if($isRequired): ?>
             required="required"
             <?php endif ?>
             <?php if($hidden): ?>
             disabled="disabled"
             <?php endif ?>
             autocomplete="off">
      <span class="co-loading-mini"><span></span><span></span><span></span></span></span>
  </div>
  <div class="field-desc">
    <span class="ui-icon ui-icon-info co-info"></span>
    <em><?php print $fieldDescription?></em>
  </div>
</div>
