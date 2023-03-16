/**
 * COmanage Registry Eligibility Widget Component JavaScript - Eligibility Panel
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
 * @since         COmanage Registry v4.2.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */
import EligibilityItem from './eligibility-item.js';
export default {
  props: {
    items: Object,
    eligibleitems: Object,
    core: Object,
    txt: Object
  },
  components: {
    EligibilityItem
  },
  data() {
    return {
      editing: false,
      newItemInvalid: false,
      newItemInvalidClass: '',
      newItemErrorMessage: this.txt.addFail
    }
  },
  methods: {
    generateId(prefix) {
      return prefix + '-' + this.core.widget;
    },
    addItem() {
      alert("Item will be checked");
    },
    refreshDisplay() {
      this.$parent.getItems(); // reload items from the server
    },
    showEdit() {
      this.editing = true;
      //this.$nextTick(() => this.$refs['newAddress'].focus());
    },
    hideEdit() {
      this.editing = false;
      this.verifying = false;
      this.$parent.setError('');
      this.$parent.successTxt = '';
    }
  },
  template: `
    <div class="cm-ssw-display" :class="{ editing: editing }">
      <ul class="cm-ssw-field-list">
        <eligibility-item 
          :txt="this.txt"
          :editing="editing"
          :core="core"
          v-for="item in this.items"
          :item="item.Item"
          :id="item.Id">
        </eligibility-item>            
      </ul>
      <div v-if="!editing" class="cm-self-service-submit-buttons">
        <button @click="showEdit" class="btn btn-small btn-primary">{{ this.txt.edit }}</button>
      </div>
      <div v-else class="cm-self-service-submit-buttons">
        <button @click="hideEdit" class="btn btn-small btn-default">{{ this.txt.done }}</button>
      </div>
    </div>
    <div v-if="editing" class="cm-ssw-add-container">
      <div class="cm-ssw-add-form">
        <form action="#">
          <div class="cm-ssw-form-row">
            <span class="cm-ssw-form-row-fields">
              <span class="cm-ssw-form-field form-group">
                <label :for="generateId('cm-ssw-eligibility-item-new')">
                  {{ txt.addItem }}
                </label> 
                <select
                  :id="generateId('cm-ssw-eligibility-item-new')"
                  class="form-control"
                  :class="this.newItemInvalidClass"
                  :aria-label="txt.addItem"
                  required="required"
                  ref="newItem">
                  <option selected>{{ txt.select }}</option>
                  <option v-for="option in eligibleitems" :value="option.id">{{ option.item }}</option>
                </select>
                <div v-if="this.newItemInvalid" class="invalid-feedback">
                  {{ this.newItemErrorMessage }}
                </div>
              </span>
            </span>
            <div class="cm-ssw-submit-buttons">
              <button @click.prevent="addItem" class="btn btm-small btn-primary cm-ssw-add-item-save-link">{{ txt.add }}</button>
            </div>
          </div>
        </form>
      </div>
    </div>  
  `
}