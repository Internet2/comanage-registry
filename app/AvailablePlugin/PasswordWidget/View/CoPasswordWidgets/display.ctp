<?php
/**
 * COmanage Registry Password Widget Display View
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
 * @since         COmanage Registry v4.2.1
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

  // Figure out the widget ID so we can overwrite the dashboard's widget div
  $divid = $vv_config['CoPasswordWidget']['co_dashboard_widget_id'];
  
  // Determine the password type (as defined by the password authenticator)
  $pwType = "CR"; // This is the default - Crypt format, required for Self-Select passwords.
  if($vv_pw_authenticator['format_sha1_ldap'] == 1) {
    $pwType = "SH"; // Salted SHA 1
  }
  if($vv_pw_authenticator['format_plaintext'] == 1) {
    $pwType = "NO"; // plaintext
  }
?>

<script type="module">
  <?php if(Configure::read('debug') > 0): ?>
    import PasswordPanel from '<?php print $this->webroot ?>password_widget/js/password-panel.js?time=<?php print time()?>';
  <?php else: ?>
    import PasswordPanel from '<?php print $this->webroot ?>password_widget/js/password-panel.js';
  <?php endif; ?>

  const app = Vue.createApp({
    data() {
      return {
        error: '',
        successTxt: '',
        passwords: {},
        pwinfo: {
          id: '',
          pwAuthId: "<?php print $vv_pw_authenticator['PasswordAuthenticator']['id']; ?>",
          pwType: "<?php print $pwType; ?>",
          pwMinLength: "<?php print $vv_pw_authenticator['PasswordAuthenticator']['min_length']; ?>",
          pwMaxLength: "<?php print $vv_pw_authenticator['PasswordAuthenticator']['max_length']; ?>",
          pwRetrievedAuthId: '',
          pwRetrievedPersonId: '',
          pwRetrievedType: ''
        },
        core: {
          coPersonId: '<?php print $vv_co_person_id; ?>',
          coId: '<?php print $vv_co_id; ?>',
          webRoot: '<?php print $this->webroot; ?>',
          widget: 'widget<?php print $divid; ?>'
        },
        txt: {
          add:                 "<?php print _txt('pl.passwordwidget.add'); ?>",
          addFail:             "<?php print _txt('er.passwordwidget.add'); ?>",
          cancel:              "<?php print _txt('op.cancel'); ?>",
          change:              "<?php print _txt('pl.passwordwidget.change'); ?>",
          confirm:             "<?php print _txt('op.confirm'); ?>",
          done:                "<?php print _txt('op.done'); ?>",
          edit:                "<?php print _txt('op.edit'); ?>",
          error401:            "<?php print _txt('er.http.401.unauth');?>",
          error500:            "<?php print _txt('er.http.500');?>",
          ok:                  "<?php print _txt('op.ok'); ?>",
          passwordConfirm:     "<?php print _txt('pl.passwordwidget.fd.password.confirm'); ?>",
          passwordNew:         "<?php print _txt('pl.passwordwidget.fd.password.new'); ?>",
          passwordIsSet:       "<?php print _txt('pl.passwordwidget.info.password.set'); ?>",
          passwordIsNotSet:    "<?php print _txt('pl.passwordwidget.info.password.not.set'); ?>",
          errorMatch:          "<?php print _txt('er.passwordwidget.match'); ?>",
          errorMaxLength:      "<?php print _txt('er.passwordwidget.maxlength', array($vv_pw_authenticator['PasswordAuthenticator']['max_length'])); ?>",
          errorMinLength:      "<?php print _txt('er.passwordwidget.minlength', array($vv_pw_authenticator['PasswordAuthenticator']['min_length'])); ?>"
        }
      }
    },
    components: {
      PasswordPanel
    },
    methods: {
      getPasswords() {
        let url = '<?php print $this->webroot ?>/password_authenticator/passwords.json?copersonid=<?php print $vv_co_person_id ?>';
        let xhr = callRegistryAPI(url, 'GET', 'json', this.setPasswordInfo, '', this.generalXhrFailCallback);
      },
      setPasswordInfo: function(xhr){
        let passwords = [];
        if(xhr.responseJSON !== undefined && xhr.responseJSON.Passwords !== undefined) {
          passwords = xhr.responseJSON.Passwords;
        }
        if(passwords.length == 1) {
          this.pwinfo.id = passwords[0].Id;
          this.pwinfo.pwRetrievedAuthId = passwords[0].PasswordAuthenticatorId;
          this.pwinfo.pwRetrievedType = passwords[0].PasswordType;
          this.pwinfo.pwRetrievedPersonId = passwords[0].Person.Id;
          // do some sanity checking here for what we believe we should have (via the Authenticator) and what the API returned
          // XXX do the above
        } else {
          // error - we have more than one password
        }
      },
      setError(txt) {
        this.error = txt;
      },
      generalXhrFailCallback(xhr) {
        stopSpinner();
        this.successTxt = '';
        if(xhr.statusText != undefined && xhr.statusText != '') {
          this.setError(xhr.statusText)
          console.log('Status Code:', xhr.status)
        } else {
          console.error(xhr);
          this.setError(this.txt.error500);
        }
      }
    },
    mounted() {
      this.getPasswords();
    }
  }).mount('#password-widget<?php print $divid ?>');
  
</script>

<div id="password-widget<?php print $divid ?>" class="cm-ssw" v-cloak>
  <div v-if=" this.error != undefined && this.error != '' && (this.successTxt == undefined || this.successTxt == '')" id="password-widget-error<?php print $divid ?>-alert" class="alert alert-danger" role="alert">
    {{ this.error }}
  </div>
  <div v-if="this.successTxt != undefined && this.successTxt != '' && (this.error == undefined || this.error == '')" id="password-widget-success<?php print $divid ?>-alert" class="alert alert-success" role="alert">
    {{ this.successTxt }}
  </div>
  <password-panel
    :pwinfo="pwinfo" 
    :core="core" 
    :txt="txt">
  </password-panel>
</div>
