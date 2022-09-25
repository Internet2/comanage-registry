<?php
/**
 * COmanage Registry Email Address Widget Display View
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

  // Figure out the widget ID so we can overwrite the dashboard's widget div
  $divid = $vv_config['CoEmailAddressWidget']['co_dashboard_widget_id'];
?>

<?php
// Include widget-specific css and javascript.
// Vue.js loaded this way must be loaded at the top.
print $this->Html->script('vue/vue-3.2.31.global.prod.js');
?>

<script type="module">
  <?php if(Configure::read('debug') > 0): ?>
    import EmailPanel from '<?php print $this->webroot ?>email_address_widget/js/email-panel.js?time=<?php print time()?>';
  <?php else: ?>
    import EmailPanel from '<?php print $this->webroot ?>email_address_widget/js/email-panel.js';
  <?php endif; ?>

  const app = Vue.createApp({
    data() {
      return {
        emailAddresses: {},
        error: '',
        successTxt: '',
        core: {
          coPersonId: '<?php print $vv_co_person_id; ?>',
          coId: '<?php print $vv_co_id; ?>',
          widget: 'widget<?php print $divid; ?>',
          emailAddressWidgetId: '<?php print $vv_config['CoEmailAddressWidget']['id']; ?>',
          webRoot: '<?php print $this->webroot; ?>',
          // Fallback to 'official' email type if no default is set in configuration
          defaultEmailType: '<?php print !empty($vv_config['CoEmailAddressWidget']['type']) ? $vv_config['CoEmailAddressWidget']['type'] : 'official'; ?>',
          messageTemplateId: '<?php print !empty($vv_config['CoEmailAddressWidget']['co_message_template_id']) ? $vv_config['CoEmailAddressWidget']['co_message_template_id'] : ''; ?>',
          deleteLast: false // TODO: determine this from configuration - can last remaining email be deleted?
        },
        txt: {
          add:                 "<?php print _txt('op.add'); ?>",
          addFail:             "<?php print _txt('er.db.save-a', array('EmailAddress')) ?>",
          cancel:              "<?php print _txt('op.cancel'); ?>",
          confirm:             "<?php print _txt('op.confirm'); ?>",
          delete:              "<?php print _txt('op.delete'); ?>",
          deleteModalTitle:    "<?php print _txt('pl.emailaddresswidget.modal.title.delete'); ?>",
          deleteModalMessage:  "<?php print _txt('pl.emailaddresswidget.modal.body.delete'); ?>",
          deleteFail:          "<?php print _txt('er.delete'); ?>",
          done:                "<?php print _txt('op.done'); ?>",
          edit :               "<?php print _txt('op.edit'); ?>",
          email:               "<?php print _txt('pl.emailaddresswidget.fd.email'); ?>",
          error401:            "<?php print _txt('er.http.401.unauth');?>",
          error500:            "<?php print _txt('er.http.500');?>",
          errorInvalid:        "<?php print _txt('er.mt.invalid', array("Email Format"));?>",
          ok:                  "<?php print _txt('op.ok'); ?>",
          token:               "<?php print _txt('pl.emailaddresswidget.fd.token'); ?>",
          tokenMsg:            "<?php print _txt('pl.emailaddresswidget.fd.token.msg'); ?>",
          tokenError:          "<?php print _txt('er.emailaddresswidget.fd.token'); ?>",
          verify:              "<?php print _txt('op.verify'); ?>"
        }
      }
    },
    components: {
      EmailPanel
    },
    methods: {
      getEmailAddresses() {
        let url = '<?php print $this->webroot ?>email_addresses.json?copersonid=<?php print $vv_co_person_id ?>';
        let xhr = callRegistryAPI(url, 'GET', 'json', this.setEmailAddresses, '', this.generalXhrFailCallback);
      },
      setEmailAddresses: function(xhr){
        this.emailAddresses = xhr.responseJSON.EmailAddresses;
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
      this.getEmailAddresses();
    }
  }).mount('#email-widget<?php print $divid ?>');
  
</script>

<div id="email-widget<?php print $divid ?>" class="cm-ssw" v-cloak>
  <div v-if=" this.error != undefined && this.error != '' && (this.successTxt == undefined || this.successTxt == '')" id="email-widget-error<?php print $divid ?>-alert" class="alert alert-danger" role="alert">
    {{ this.error }}
  </div>
  <div v-if="this.successTxt != undefined && this.successTxt != '' && (this.error == undefined || this.error == '')" id="email-widget-success<?php print $divid ?>-alert" class="alert alert-success" role="alert">
    {{ this.successTxt }}
  </div>
  <email-panel
    :emails="emailAddresses" 
    :core="core" 
    :txt="txt">
  </email-panel>
</div>
