<?php
/**
 * COmanage Registry Eligibility Widget Display View
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
 * @since         COmanage Registry v4.2.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

  // Figure out the widget ID so we can overwrite the dashboard's widget div
  $divid = $vv_config['CoEligibilityWidget']['co_dashboard_widget_id'];
?>

<script type="module">
<?php if(Configure::read('debug') > 0): ?>
    import EligibilityPanel from '<?php print $this->webroot ?>eligibility_widget/js/eligibility-panel.js?time=<?php print time()?>';
  <?php else: ?>
    import EligibilityPanel from '<?php print $this->webroot ?>eligibility_widget/js/eligibility-panel.js';
  <?php endif; ?>

  const app = Vue.createApp({
    data() {
      return {
        items: {},
        eligibleitems: [ // XXX replace/update with data coming from plugin
          {"item":"Association for Jewish Studies","id":"23"},
          {"item":"ARLIS/NA (Art Libraries Society of North America)","id":"23","active":"true"},
          {"item":"Association for Slavic, Eastern European, and Eurasian Studies","id":"23"},
          {"item":"College Art Association","id":"23"},
          {"item":"HASTAC (Humanities, Arts, Science, and Technology Alliance and Collaboratory)","id":"23","active":"true"},
          {"item":"Modern Language Association","id":"23"},
          {"item":"Society of Architectural Historians","id":"23"},
          {"item":"Social Sciences domain hub","id":"23"},
          {"item":"Association of American University Presses","id":"23"}
        ],
        error: '',
        successTxt: '',
        core: {
          coPersonId: '<?php print $vv_co_person_id; ?>',
          coId: '<?php print $vv_co_id; ?>',
          widget: 'widget<?php print $divid; ?>',
          eligibilityWidgetId: '<?php print $vv_config['CoEligibilityWidget']['id']; ?>',
          webRoot: '<?php print $this->webroot; ?>',
        },
        txt: {
          add:                 "<?php print _txt('op.add'); ?>",
          addItem:             "Add Society", // XXX This should come from config.
          cancel:              "<?php print _txt('op.cancel'); ?>",
          confirm:             "<?php print _txt('op.confirm'); ?>",
          done:                "<?php print _txt('op.done'); ?>",
          edit :               "<?php print _txt('op.edit'); ?>",
          error401:            "<?php print _txt('er.http.401.unauth');?>",
          error500:            "<?php print _txt('er.http.500');?>",
          ok:                  "<?php print _txt('op.ok'); ?>",
          remove:              "<?php print _txt('op.remove'); ?>",
          removeModalTitle:    "<?php print _txt('pl.eligibilitywidget.modal.title.remove'); ?>",
          removeModalMessage:  "<?php print _txt('pl.eligibilitywidget.modal.body.remove'); ?>",
          removeFail:          "<?php print _txt('pl.er.eligibilitywidget.remove'); ?>",
          select:              "<?php print _txt('op.select.empty')?>"
        }
      }
    },
    components: {
      EligibilityPanel
    },
    methods: {
      getItems() {
        //let url = '<?php print $this->webroot ?>email_addresses.json?copersonid=<?php print $vv_co_person_id ?>';
        //let xhr = callRegistryAPI(url, 'GET', 'json', this.setItems, '', this.generalXhrFailCallback);
        this.setItems(); // XXX TEMPORARY - once we gather the actual items, we will use the above lines to gather the values over XHR
      },
      setItems: function(xhr){
        // this.items = xhr.responseJSON.EmailAddresses; // XXX this will be the setter function when the XHR is in place
        // XXX TEMPORARY - remove the following when the line above sets the value of this.items using XHR
        this.items = [{"Version":"1.0","Id":2265,"Item":"ARLIS/NA (Art Libraries Society of North America)","Created":"2022-08-18 22:58:10","Modified":"2022-08-18 22:58:10"},{"Version":"1.0","Id":2266,"Item":"HASTAC (Humanities, Arts, Science, and Technology Alliance and Collaboratory)","Created":"2022-09-18 22:58:10","Modified":"2022-09-18 22:58:10"}];
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
      this.getItems();
    }
  }).mount('#eligibility-widget<?php print $divid ?>');
</script>

<div id="eligibility-widget<?php print $divid ?>" class="cm-ssw" v-cloak>
  <div v-if=" this.error != undefined && this.error != '' && (this.successTxt == undefined || this.successTxt == '')" id="eligibility-widget-error<?php print $divid ?>-alert" class="alert alert-danger" role="alert">
    {{ this.error }}
  </div>
  <div v-if="this.successTxt != undefined && this.successTxt != '' && (this.error == undefined || this.error == '')" id="eligibility-widget-success<?php print $divid ?>-alert" class="alert alert-success" role="alert">
    {{ this.successTxt }}
  </div>
  <eligibility-panel
    :items="items"
    :eligibleitems="eligibleitems"
    :core="core" 
    :txt="txt">
  </eligibility-panel>
</div>
