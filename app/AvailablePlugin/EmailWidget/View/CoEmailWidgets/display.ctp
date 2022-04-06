<?php
/**
 * COmanage Registry Email Widget Display View
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
  $divid = $vv_config['CoEmailWidget']['co_dashboard_widget_id'];
?>

<?php
// Include widget-specific css and javascript.
// Vue.js loaded this way must be loaded at the top.
print $this->Html->script('vue/vue-3.2.31.global.prod.js');
?>

<script type="module">
  import EmailPanel from '<?php print $this->webroot ?>email_widget/js/email-panel.js';
  
  const app = Vue.createApp({
    data() {
      return {
        emailAddresses: {},
        core: {
          coPersonId: '<?php print $vv_co_person_id[0] ?>',
          widget: 'widget<?php print $divid ?>',
          webRoot: '<?php print $this->webroot ?>',
          editEmails: false,  // TODO: determine this from configuration - can email be edited?
          deletePrimary: false // TODO: determine this from configuration - can primary email be deleted?
        },
        txt: {
          add:          "<?php print _txt('op.add'); ?>",
          addFail:      "<?php print _txt('pl.email_widget.modal.body.add.fail'); ?>",
          cancel:       "<?php print _txt('op.cancel'); ?>",
          confirm:      "<?php print _txt('op.confirm'); ?>",
          delete:       "<?php print _txt('op.delete'); ?>",
          deleteModalTitle: "<?php print _txt('pl.email_widget.modal.title.delete'); ?>",
          deleteModalMessage: "<?php print _txt('pl.email_widget.modal.body.delete'); ?>",
          deleteFail:   "<?php print _txt('pl.email_widget.modal.title.delete.fail'); ?>",
          edit :        "<?php print _txt('op.edit'); ?>",
          email:        "<?php print _txt('pl.email_widget.fd.email'); ?>",
          makePrimary:  "<?php print _txt('pl.email_widget.make.primary');?>",
          ok:           "<?php print _txt('op.ok'); ?>",
          primary:      "<?php print _txt('fd.name.primary_name'); ?>"
        }
      }
    },
    components: {
      EmailPanel
    },
    methods: {
      getEmailAddresses() {
        let url = '<?php print $this->webroot ?>email_addresses.json?copersonid=<?php print $vv_co_person_id[0] ?>';
        let xhr = callRegistryAPI(url, 'GET', 'json', this.setEmailAddresses);
      },
      setEmailAddresses: function(xhr){
        this.emailAddresses = xhr.responseJSON.EmailAddresses;
      }
    },
    mounted() {
      this.getEmailAddresses();
    }
  }).mount('#email-widget<?php print $divid ?>');
  
</script>

<div id="email-widget<?php print $divid ?>" class="cm-ssw">
  <email-panel 
    :emails="emailAddresses" 
    :core="core" 
    :txt="txt">
  </email-panel>
</div>
