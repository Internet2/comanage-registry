<?php
/**
 * COmanage Registry Attribute Enumeration JavaScript
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
 * @since         COmanage Registry v4.0.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

// eg: "CoPersonRole"
$mname = (!empty($modelName) ? $modelName : Inflector::singularize($this->name));
// eg: "co_person_roles"
$tname = Inflector::tableize($this->name);
?>
<?php if($editable): ?>
  <div id="<?php print $column; ?>-enumeration" style="display:none" class="mb-1">
    <select id="<?php print $column; ?>-select" onchange='enum_set_value("<?php print $mname . Inflector::camelize($column); ?>", "<?php print $column; ?>");'>
    </select>
  </div>
  <div id="<?php print $column; ?>-field">
    <?php
      $args = array();
      $args['onkeyup'] = "document.getElementById('".$column."-select').value = ''";
      
      if(!empty($fieldName)) {
        $args['label'] = false;
        print $this->Form->input($fieldName, $args);
      } else {
        print $this->Form->input($mname.'.'.$column, $args);
      }
    ?>
  </div>
<?php else: // $editable ?>
  <?php print filter_var($$tname[0][$mname][$column],FILTER_SANITIZE_SPECIAL_CHARS); ?>
<?php endif; // $editable ?>
