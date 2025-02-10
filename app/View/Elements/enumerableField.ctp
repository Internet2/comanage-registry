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

// We work with three fields for each attribute:
// (1) #attribute-select: The select list with the enumeration values
// (2) #attribute-other: If "allow other values" is enabled, the free form field
// (3) #attribute-field: The actual field, which will be hidden if there is an
//     enumeration, and whose name can vary depending on whether we are the operational
//     attribute or in a petition

// Some shortcuts
$field = !empty($fieldName) ? $fieldName : ($mname . "." . $column);
// Camel Case field
$cField = $mname . Inflector::camelize($column);

$enumEnabled = !empty($vv_enums[$field]['dictionary']);
$allowOther = isset($vv_enums[$field]['allow_other']) && $vv_enums[$field]['allow_other'];
?>
<?php if($editable): ?>
  <!-- (1) The select widget -->

  <div id="<?php print $column; ?>-enumeration" style="display:none" class="mb-1">
    <select class="form-control" id="<?php print $cField; ?>Select" onchange="enum_set_value('Select', '<?php print $mname . Inflector::camelize($column); ?>', '<?php print $column; ?>')">
    </select>
  </div>

  <!-- (2) The "other" widget -->

  <div id="<?php print $column; ?>-other" style="display:none" class="mb-1">
    <?php
      $args = array();
      // onkeyup
      $args['onchange'] = "enum_set_value('Other', '" . $mname . Inflector::camelize($column) . "', '" . $column . "')";
      $args['class'] = 'form-control';
      $args['label'] = $allowOther ? _txt('fd.ae.attr.other') : false;
      $args['id'] = $cField . "Other";
      
      print $this->Form->input($column."-other", $args);
    ?>
  </div>

  <!-- (3) The actual field -->

  <div id="<?php print $column; ?>-field" style="display:none" class="mb-1">
    <?php
      $args = array();
      $args['class'] = 'form-control';
      $args['required'] = isset($required) && $required;
      $args['label'] = false;

      if(!empty($default)) {
        $args['value'] = $default;
      }

      if(!empty($fieldName)) {
        print $this->Form->input($fieldName, $args);
      } else {
        print $this->Form->input($mname.'.'.$column, $args);
      }
    ?>
  </div>

<?php else: // $editable ?>
  <?php 
    if(!empty($$tname[0][$mname][$column])) {
      // Standard field
      print filter_var($$tname[0][$mname][$column],FILTER_SANITIZE_SPECIAL_CHARS);
    } elseif(isset($fieldName, $default) && !empty($vv_enums[$fieldName]['dictionary'][$default])) {
      // Petition view, value set that corresponds to dictionary entry
      print filter_var($vv_enums[$fieldName]['dictionary'][$default],FILTER_SANITIZE_SPECIAL_CHARS);
    } elseif(!empty($default)) {
      // Petition view, value set that does not correspond to dictionary entry
      print filter_var($default,FILTER_SANITIZE_SPECIAL_CHARS);
    }
  ?>
<?php endif; // $editable ?>
