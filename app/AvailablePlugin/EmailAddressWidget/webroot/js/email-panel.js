/**
 * COmanage Registry Email Widget Component JavaScript - Email Panel
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
 * @since         COmanage Registry v4.1.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */
import EmailItem from './email-item.js';
export default {
  props: {
    emails: Object,
    core: Object,
    txt: Object
  },
  components: {
    EmailItem
  },
  data() {
    return {
      editing: false,
      verifying: false,
      newEmailInvalid: false,
      newEmailInvalidClass: '',
      newEmailErrorMessage: this.txt.addFail,
      token: '',
      tokenInvalid: false,
      tokenInvalidClass: '',
      tokenErrorMessage: this.txt.tokenError
    }
  },
  methods: {
    generateId(prefix) {
      return prefix + '-' + this.core.widget;
    },
    genToken() {
      const newEmailAddress = this.$refs.newAddress.value.trim();
      // basic front-end validation: is it empty?
      if(newEmailAddress == '') {
        return;
      }
      // basic front-end validation: does it contain '@' and '.'?
      if(newEmailAddress.indexOf('@') == -1 || newEmailAddress.indexOf('.') == -1) {
        this.newEmailInvalid = true;
        this.newEmailInvalidClass = 'is-invalid';
        this.newEmailErrorMessage = this.txt.errorInvalid;
        return;
      }

      this.$parent.setError('');
      this.$parent.successTxt = '';

      const url = `/registry/email_address_widget/co_email_address_widgets/gentoken/${this.core.emailAddressWidgetId}?email=${encodeURIComponent(this.$refs.newAddress.value)}&copersonid=${this.core.coPersonId}`;
      displaySpinner();
      callRegistryAPI(
        url,
        'GET',
        'json',
        this.genTokenSuccessCallback,
        '',
        this.genTokenFailCallback);
    },
    genTokenSuccessCallback(xhr) {
      stopSpinner();
      this.token = xhr.responseJSON['token'];
      if(this.token != undefined && this.token != '') {
        this.verifying = true;
        this.$nextTick(() => this.$refs['token'].focus());
      } else {
        this.newEmailInvalid = true;
        this.newEmailInvalidClass = 'is-invalid';
        this.newEmailErrorMessage = this.txt.error500;
      }
    },
    genTokenFailCallback(xhr) {
      stopSpinner();
      this.newEmailInvalid = true;
      this.newEmailInvalidClass = 'is-invalid';
      this.$parent.generalXhrFailCallback(xhr);
    },
    verifyEmail() {
      const token = this.$refs.token.value.trim();
      // basic front-end validation: is it empty?
      if(token == '') {
        return;
      }

      const url = `/registry/email_address_widget/email_address_widget_verifications/verify/${encodeURIComponent(token)}`;
      displaySpinner();
      callRegistryAPI(
        url,
        'GET',
        'json',
        this.verifySuccessCallback,
        '',
        this.verifyFailCallback);
    },
    verifySuccessCallback(xhr) {
      stopSpinner();
      // Only 201 will be returned on success
      this.tokenInvalid = false;
      this.tokenInvalidClass = '';
      this.$refs.token.value = '';
      this.verifying = false;
      this.clearInvalid();
      this.refreshDisplay();
      this.$parent.successTxt = xhr.statusText;
      this.$parent.setError('');
    },
    verifyFailCallback(xhr) {
      stopSpinner();
      this.$parent.generalXhrFailCallback(xhr);
    },
    refreshDisplay() {
      this.$parent.getEmailAddresses(); // reload the addresses from the server
    },
    showEdit() {
      this.editing = true;
      this.$nextTick(() => this.$refs['newAddress'].focus());
    },
    hideEdit() {
      this.editing = false;
      this.verifying = false;
      this.$parent.setError('');
      this.$parent.successTxt = '';
      this.clearInvalid();
    },
    clearInvalid() {
      this.newEmailInvalid = false;
      this.newEmailInvalidClass = '';
      this.newEmailErrorMessage = this.txt.newEmailErrorMessage;
      this.tokenInvalid = false;
      this.tokenInvalidClass = '';
      this.tokenErrorMessage = this.txt.tokenError;
    }
  },
  template: `
    <div class="cm-ssw-display" :class="{ editing: editing }">
      <ul class="cm-ssw-field-list">
        <email-item 
          :txt="this.txt"
          :editing="editing"
          :core="core"
          v-for="email in this.emails"
          :mail="email.Mail"
          :id="email.Id">
        </email-item>    
      </ul>
      <div v-if="!editing" class="cm-self-service-submit-buttons">
        <button @click="showEdit" class="btn btn-small btn-primary">{{ this.txt.edit }}</button>
      </div>
      <div v-else class="cm-self-service-submit-buttons">
        <button @click="hideEdit" class="btn btn-small btn-default">{{ this.txt.done }}</button>
      </div>  
    </div>
    <div v-if="editing" class="cm-ssw-add-container">
      <div v-if="verifying" class="cm-ssw-add-form cm-ssw-verify-form">
        <form action="#">
          <div class="cm-ssw-form-row">
            <span class="cm-ssw-form-row-fields">
              <span class="cm-ssw-form-field form-group">
                <label :for="generateId('cm-ssw-email-address-token')">
                  {{ txt.token }}
                </label>
                <p>{{ txt.tokenMsg }}</p>
                <input 
                  type="text" 
                  :id="generateId('cm-ssw-email-address-token')"
                  class="form-control cm-ssw-form-field-token" 
                  :class="this.tokenInvalidClass"
                  value="" 
                  required="required"
                  ref="token"/>
                  <div v-if="this.tokenInvalid" class="invalid-feedback">
                    {{ this.tokenErrorMessage }}
                  </div>
              </span>
            </span>
            <div class="cm-ssw-submit-buttons">
              <button @click.prevent="verifyEmail" class="btn btm-small btn-primary cm-ssw-add-email-save-link">{{ txt.verify }}</button>
            </div>
          </div>
        </form>      
      </div>
      <div v-else class="cm-ssw-add-form">
        <form action="#">
          <div class="cm-ssw-form-row">
            <span class="cm-ssw-form-row-fields">
              <span class="cm-ssw-form-field form-group">
                <label :for="generateId('cm-ssw-email-address-new')">
                  {{ txt.email }}
                </label> 
                <input 
                  type="text" 
                  :id="generateId('cm-ssw-email-address-new')"
                  class="form-control cm-ssw-form-field-email" 
                  :class="this.newEmailInvalidClass"
                  value="" 
                  required="required"
                  ref="newAddress"/>
                  <div v-if="this.newEmailInvalid" class="invalid-feedback">
                    {{ this.newEmailErrorMessage }}
                  </div>
              </span>
              <input 
                type="hidden"
                :id="generateId('cm-ssw-email-type-new')"
                :value="core.defaultEmailType"
                ref="newAddressType"/>
            </span>
            <div class="cm-ssw-submit-buttons">
              <button @click.prevent="genToken" class="btn btm-small btn-primary cm-ssw-add-email-save-link">{{ txt.add }}</button>
            </div>
          </div>
        </form>
      </div>
    </div>  
  `
}