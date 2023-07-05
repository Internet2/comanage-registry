/**
 * COmanage Registry Password Widget Component JavaScript - Password Panel
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

export default {
  props: {
    pwinfo: Object,
    core: Object,
    txt: Object
  },
  data() {
    return {
      editing: false,
      passwordInvalid: false,
      passwordErrorMessage: ''
    }
  },
  methods: {
    generateId(prefix) {
      return prefix + '-' + this.core.widget;
    },
    refreshDisplay() {
      this.$parent.getPasswords(); // check on the password(s) from the server
    },
    showEdit() {
      this.editing = true;
      this.$nextTick(() => {
        if(this.pwinfo.id == '') {
          this.$refs?.passwordNew?.focus()
        } else {
          this.$refs?.passwordCurrent?.focus()
        }
      });
    },
    hideEdit() {
      this.editing = false;
      this.$parent.setError('');
      this.$parent.successTxt = '';
      this.clearInvalid();
    },
    clearInvalid() {
      this.passwordInvalid = false;
      this.passwordInvalidClass = '';
      this.passwordErrorMessage = '';
    },
    submitPassword() {
      const pw = this.$refs.passwordNew.value;
      const pwConf = this.$refs.passwordConfirm.value;
      let pwCurr;
      if(this.pwinfo.id != '') {
        pwCurr = this.$refs.passwordCurrent.value;
      }
      let pwMethod = 'POST';
      let url = `${this.core.webRoot}password_widget/co_password_widgets/manage.json`
      
      // basic front-end validation: do the passwords match?
      if(pw != pwConf) {
        this.passwordErrorMessage = this.txt.errorMatch;
        this.passwordInvalidClass = 'is-invalid';
        this.passwordInvalid = true;
        return;
      }
      // basic front-end validation: is it empty?
      if(pw == '') {
        return;
      }
      // basic front-end validation: is it the correct length?
      if(pw.length < this.pwinfo.pwMinLength || pw.length > this.pwinfo.pwMaxLength) {
        this.passwordErrorMessage = pw.length < this.pwinfo.pwMinLength ? this.txt.errorMinLength : this.txt.errorMaxLength;
        this.passwordInvalidClass = 'is-invalid';
        this.passwordInvalid = true;
        return;
      }

      this.$parent.setError('');
      this.$parent.successTxt = '';

      let pwDataSimple = {
        "Password": {
          "password_authenticator_id":this.pwinfo.pwAuthId,
          "co_person_id": this.core.coPersonId,
          "password":pw,
          "password2":pwConf,
          "co_password_widget_id": this.core.passWidgetId
        }
      };

      if(this.pwinfo.id != '') {
        pwDataSimple['Current'] = {
          'password': pwCurr
        }
      }

      displaySpinner();
      callRegistryAPI(
        url,
        pwMethod,
        'json',
        this.updatePwSuccessCallback,
        '',
        this.updatePwFailCallback,
        pwDataSimple
      );
    },
    updatePwSuccessCallback(xhr) {
      stopSpinner();
      if(xhr.responseJSON !== undefined) {
        this.hideEdit();
        this.refreshDisplay();
        this.$parent.successTxt = xhr.statusText;
        this.$parent.setError('');
      } else {
        this.passwordInvalid = true;
        this.passwordInvalidClass = 'is-invalid';
        this.passwordErrorMessage = this.txt.error500;
      }
    },
    updatePwFailCallback(xhr) {
      stopSpinner();
      this.passwordInvalid = true;
      this.passwordInvalidClass = 'is-invalid';
      this.$parent.generalXhrFailCallback(xhr);
    }
  },
  template: `
    <div class="cm-ssw-display" :class="{ editing: editing }">
      <div class="cm-ssw-password-message">
        {{ this.pwinfo.id == '' ? this.txt.passwordIsNotSet : this.txt.passwordIsSet }}
      </div>
      <div v-if="!editing" class="cm-self-service-submit-buttons">
        <button @click="showEdit" class="btn btn-small btn-primary">
          {{ this.txt.edit }}
        </button>
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
              <span v-if="this.pwinfo.id != ''" 
                    class="cm-ssw-form-field form-group">
                <label :for="generateId('cm-ssw-password-current')">
                  {{ txt.passwordCurrent }}
                </label> 
                <!-- For the current password it is unrealistic to assume that we know min and max length. -->
                <!-- The configuration could be different now -->
                <input 
                  type="password" 
                  :id="generateId('cm-ssw-password-current')"
                  class="form-control cm-ssw-form-field-password"
                  value="" 
                  required="required"
                  ref="passwordCurrent"/>
              </span>
              <span class="cm-ssw-form-field form-group">
                <label :for="generateId('cm-ssw-password')">
                  {{ txt.passwordNew }}
                </label> 
                <input 
                  type="password" 
                  :id="generateId('cm-ssw-password')"
                  class="form-control cm-ssw-form-field-password" 
                  :class="this.passwordInvalidClass"
                  :minlength="this.pwinfo.pwMinLength"
                  :maxlength="this.pwinfo.pwMaxLength"
                  value="" 
                  required="required"
                  ref="passwordNew"/>
                  <div v-if="this.passwordInvalid" class="invalid-feedback">
                    {{ this.passwordErrorMessage }}
                  </div>
              </span>
              <span class="cm-ssw-form-field form-group">
                <label :for="generateId('cm-ssw-password-again')">
                  {{ txt.passwordConfirm }}
                </label> 
                <input 
                  type="password" 
                  :id="generateId('cm-ssw-password-again')"
                  class="form-control cm-ssw-form-field-password" 
                  :class="this.passwordInvalidClass"
                  :minlength="this.pwinfo.pwMinLength"
                  :maxlength="this.pwinfo.pwMaxLength"
                  value="" 
                  required="required"
                  ref="passwordConfirm"/>
              </span>
              <input 
                type="hidden"
                :id="generateId('cm-ssw-password-type')"
                :value="this.pwinfo.pwType"
                ref="passwordType"/>
            </span>
            <div class="cm-ssw-submit-buttons">
              <button @click.prevent="submitPassword" class="btn btm-small btn-primary cm-ssw-add-email-save-link">
                {{ this.pwinfo.id == '' ? txt.add : txt.reset }}
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>  
  `
}