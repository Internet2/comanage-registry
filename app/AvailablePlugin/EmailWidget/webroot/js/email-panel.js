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
      newEmailInvalid: false,
      newEmailInvalidClass: '',
      newEmailErrorMessage: this.txt.addFail
    }
  },
  methods: {
    generateId(prefix) {
      return  prefix + '-' + this.core.widget;
    },
    addEmail() {
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
      // pass the new address to the server  
      let url = this.core.webRoot + 'email_addresses.json';
      let data = {
        "RequestType":"EmailAddresses",
        "Version":"1.0",
        "EmailAddresses":
          [
            {
              "Version":"1.0",
              "Mail":this.$refs.newAddress.value,
              "Type":this.$refs.newAddressType.value,
              "Person":
                {
                  "Type":"CO",
                  "Id":this.core.coPersonId
                }
            }
          ]
      };

      callRegistryAPI(url, 'POST', 'json', this.addSuccessEmailCallback, '', this.addFailEmailCallback, data);
    },
    addSuccessEmailCallback(xhr) {
      this.$refs.newAddress.value = '';
      this.clearInvalid();
      this.refreshDisplay();
    },
    addFailEmailCallback(xhr) {
      if(xhr.status == 400) {
        this.newEmailInvalid = true;
        this.newEmailInvalidClass = 'is-invalid';
        this.newEmailErrorMessage = xhr.responseJSON.InvalidFields.mail[0];  
      } else {
        this.$parent.generalXhrFailCallback(xhr); 
      }
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
      this.$parent.setError('');
      this.clearInvalid();
    },
    clearInvalid() {
      this.newEmailInvalidClass = '';
      this.newEmailErrorMessage = this.txt.newEmailErrorMessage;
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
          :id="email.Id"
          :primary="false"><!-- XXX set primary when it exists, e.g. email.primary -->
        </email-item>    
      </ul>
      <div v-if="!editing" class="cm-self-service-submit-buttons">
        <button @click="showEdit" class="btn btn-small btn-primary">{{ this.txt.edit }}</button>
      </div>
      <div v-else class="cm-self-service-submit-buttons">
        <button @click="hideEdit" class="btn btn-small btn-default">{{ this.txt.done }}</button>
      </div>  
    </div>
    <div v-if="editing" class="cm-ssw-add-form">
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
            <span class="cm-ssw-form-field form-check">
              <input 
                type="checkbox"
                :id="generateId('cm-ssw-email-make-primary')"
                class="form-check-input cm-ssw-form-field-primary" 
                name="cm-ssw-email-make-primary"
                ref="new-email-address-primary"/>
              <label class="form-check-label" :for="generateId('cm-ssw-email-make-primary')">
                {{ txt.makePrimary }}
              </label>
            </span>
          </span>
          <div class="cm-ssw-submit-buttons">
            <!--button @click="hideEdit" class="btn btn-small cm-ssw-add-cancel">{{ txt.cancel }}</button-->
            <button @click.prevent="addEmail" class="btn btm-small btn-primary cm-ssw-add-email-save-link">{{ txt.add }}</button>
          </div>
        </div>
      </form>
    </div>
  `
}