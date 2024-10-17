<?php
/**
 * COmanage Registry MultiSelect Dropdown Widget Display View
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
 * @since         COmanage Registry v4.1.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

/*
 * $mountElementId,     string, ID of the Form input element
 * $mountElementName,   string, Name attribute of the Form input element
 * $elementParams,      array,  List of configurations used by Cakephp to create the Form Element
 * $containerPostfixId, string, A unique string that will be used to construct the ID attribute of the
 *                              module container
 * $containerClasses,   string, List of classes for the container element
 * $values,             string, List of selected Values
 */

// Figure out the widget ID so we can overwrite the dashboard's widget div
if (empty($mountElementId)) {
  return '<span>Element ID not provided</span>';
}

$mountElementId = $mountElementId;
$elementParams = $elementParams ?? [];
$containerId = 'element-container-' . str_replace('.', '-', $containerPostfixId);

// Calculate the options
$options = [];
foreach ($elementParams['options'] as $code => $value) {
  $options[] = [
    'name' => $value,
    'code' => $code
  ];
}

// Calculate the selected values
$selectedValues = null;
if (!empty($values)) {
  $selectedValues = json_encode(explode(',', $values));
}

// https://github.com/primefaces/primevue-sass-theme/tree/main
print $this->Html->css('primevue/bootstrap4.min');
print $this->Html->css('primevue-overrides')
?>

<script type="module">
  const app = Vue.createApp({
    data() {
      return {
        inputId: '<?= $mountElementId ?>',
        selected: <?= $selectedValues ?? json_encode([]) ?>,
        label: '<?= $elementParams['label'] ?>',
        optionsList: <?= json_encode($options) ?>,
        //inputValue: '<?php //= $inputValue ?>//',
        inputProps: {
          name: '<?= $mountElementName ?>'
        },
      }
    },
    watch: {
      // whenever question changes, this function will run
      selected: {
        immediate: false,
        handler(newSelected, oldSelected) {
          document.getElementById(this.inputId).value = newSelected
        }
      }
    },
    mounted() {
      if(this.selected != null && this.selected.length > 0) {
        document.getElementById(this.inputId).value = this.selected
      }
    },
    components: {
      MultiSelect : primevue.multiselect
    },
    template: `
      <label class="mr-2" :for="inputId">{{ label }}</label>
      <MultiSelect v-model="selected"
                   :inputId="inputId"
                   :inputProps="inputProps"
                   optionLabel="name"
                   optionValue="code"
                   :options="optionsList"
                   class="top-search"
                   placeholder="All">
      </MultiSelect>
   `
  })

  app.use(primevue.config.default, {
    theme: {
      options: {
        prefix: 'p'
      }
    }
  });
  app.mount('#<?= $containerId ?>')
</script>

<div class="<?= $containerClasses ?>" id="<?= $containerId ?>"></div>

