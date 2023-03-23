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
 * @since         COmanage Registry v4.3.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */
import EligibilityItem from './eligibility-item.js?idx=33';
// XXX Here we do not have a way of forcing the update of the import.
//     During development use something like
//     import {isCouActive, isOISActive} from './helpers.js?idx=1';
//     and its time you update something increase the idx

import {isCouActive, isOISActive} from './helpers.js?idx=22';

export default {
  props: {
    items: Object,
    eligibleitems: Object,
    activecoulist: Object,
    core: Object,
    txt: Object
  },
  components: {
    EligibilityItem
  },
  data() {
    return {
      editing: false,
      newRoleInvalid: false,
      newRoleInvalidClass: '',
      newRoleErrorMessage: this.txt.addFail
    }
  },
  computed: {
    isDropDownEmpty() {
      //  This will return true if no options are present
      //  or if all options are currently present in the active list
      const all_sorted = this.eligibleitems
        .map(elem => elem.id)
        .sort(function(a, b) {return a - b;})

      if(all_sorted.length == 0) {
        return true
      }

      const active_sorted = this.activecoulist
        .map(el => el)
        .sort(function(a, b) {return a - b;})

      return (JSON.stringify(active_sorted) == JSON.stringify(all_sorted))
    },
    isEditing() {
      return this.editing
    },
    options() {
      // This is a good way to force select to update
      // but this will not work for the disabled fields.
      // The reason is that we calculate the disabled fields
      // on the fly
      return this.eligibleitems
    }
  },
  methods: {
    generateId(prefix) {
      return prefix + '-' + this.core.widget;
    },
    selectedOptionValue() {
      return this.$refs.newItem.selectedOptions[0].value;
    },
    verifyEligibility() {
      const oisId = parseInt(this.selectedOptionValue())
      // basic front-end validation
      if(isNaN(oisId)) {
        return;
      }
      this.$parent.successTxt = '';

      const data = {
        co_person_id: this.core.coPersonId,
        eligibility_widget_id: this.core.eligibilityWidgetId,
        ois_id: oisId
      }

      const url = `${this.core.webRoot}eligibility_widget/co_eligibility_widgets/eligibility/${this.core.eligibilityWidgetId}.json`;
      displaySpinner();
      callRegistryAPI(
        url,
        'POST',
        'json',
        this.eligibilitySuccessCallback,
        '',
        this.assignRoleFailCallback,
        data);

    },
    assignRole() {
      const couId = this.selectedOptionValue()
      // basic front-end validation: is it empty?
      if(couId == '') {
        return;
      }
      this.$parent.successTxt = '';

      const data = {
        co_person_id: this.core.coPersonId,
        eligibility_widget_id: this.core.eligibilityWidgetId,
        cou_id: parseInt(couId)
      }

      const url = `${this.core.webRoot}eligibility_widget/co_eligibility_widgets/assign/${this.core.eligibilityWidgetId}.json`;
      displaySpinner();
      callRegistryAPI(
        url,
        'POST',
        'json',
        this.assignRoleSuccessCallback,
        '',
        this.assignRoleFailCallback,
        data);
    },
    assignRoleSuccessCallback(xhr) {
      stopSpinner();
      const resp = xhr.responseJSON
      if(resp.Id != undefined && resp.Id != '') {
        this.refreshDisplay();
        this.$parent.successTxt = xhr.statusText;
        this.$parent.setError('');
      } else {
        this.newRoleInvalid = true;
        this.newRoleInvalidClass = 'is-invalid';
        this.newRoleErrorMessage = this.txt.error500;
      }
    },
    eligibilitySuccessCallback(xhr) {
      stopSpinner();
      const resp = xhr.responseJSON
      // Reset the messages before the evaluation
      this.$parent.successTxt = '';
      this.$parent.setError('');
      this.$parent.setWarning('');
      if (resp?.unmatched?.length > 0) {
        // Something did not work very well
        const message = `${resp.message} (${resp?.unmatched?.join(',')})`
        this.$parent.setWarning(message)
      } else {
        this.$parent.successTxt = resp.message
      }

      this.refreshDisplay();
    },
    assignRoleFailCallback(xhr) {
      stopSpinner();
      this.newRoleInvalid = true;
      this.newRoleInvalidClass = 'is-invalid';
      this.$parent.generalXhrFailCallback(xhr);
    },
    refreshDisplay() {
      this.$parent.getItems(); // reload items from the server
      this.$parent.getAllCouOis(); // reload items from the server
      this.$parent.getActiveCousOis(); // reload items from the server
    },
    hideEdit() {
      this.$parent.setError('');
      this.$parent.successTxt = '';
      this.$parent.setWarning('');
      this.editing = false;
    },
    calcIsActiveCou(couId) {
      return isCouActive(couId, this.activecoulist)
    },
    calcIsActiveOis(oisRegistrationId) {
      return isOISActive(oisRegistrationId, this.activecoulist)
    }
  },
  template: `
    <div class="cm-ssw-display" :class="{ editing: isEditing }">
      <ul class="cm-ssw-field-list">
        <eligibility-item
          v-if="this.items?.length > 0"
          :txt="this.txt"
          :isEditing="isEditing"
          :core="core"
          v-for="item in this.items"
          :item="item.Item"
          :id="item.id">
        </eligibility-item>            
      </ul>
      <div v-if="!editing" class="cm-self-service-submit-buttons">
        <button @click="editing=true" class="btn btn-small btn-primary">{{ this.txt.edit }}</button>
      </div>
      <div v-else class="cm-self-service-submit-buttons">
        <button @click="hideEdit" class="btn btn-small btn-default">{{ this.txt.done }}</button>
      </div>
    </div>
    <div v-if="isEditing" class="cm-ssw-add-container">
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
                  :class="this.newRoleInvalidClass"
                  :aria-label="txt.addItem"
                  required="required"
                  ref="newItem">
                  <option selected>{{ txt.select }}</option>
                  <option v-if="this.core.mode == 'CR'"
                          v-for="option in options"
                          data-model="Cou"
                          :value="option?.id"
                          :key="option?.id"
                          :disabled="calcIsActiveCou(option?.id)">{{ option?.name }}</option>
                  <option v-if="this.core.mode == 'OR'"
                          v-for="option in options"
                          data-model="OrgIdentitySource"
                          :value="option?.org_identity_source_id"
                          :key="option?.org_identity_source_id"
                          :disabled="calcIsActiveOis(option?.id)">{{ option?.description }}</option>
                </select>
                <div v-if="this.newRoleInvalid" class="invalid-feedback">
                  {{ this.newRoleErrorMessage }}
                </div>
              </span>
            </span>
            <div v-if="this.core.mode == 'CR'"
                 class="cm-ssw-submit-buttons">
              <button @click.prevent="assignRole"
                      class="btn btm-small btn-primary cm-ssw-add-item-save-link">{{ txt.add }}</button>
            </div>
            <div v-if="this.core.mode == 'OR'"
                 class="cm-ssw-submit-buttons">
              <button @click.prevent="verifyEligibility"
                      :disabled="isDropDownEmpty"
                      class="btn btm-small btn-primary cm-ssw-add-item-save-link">{{ txt.add }}</button>
            </div>
          </div>
        </form>
      </div>
    </div>  
  `
}