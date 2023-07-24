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
 * @since         COmanage Registry v4.3.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

  // Figure out the widget ID so we can overwrite the dashboard's widget div
  $divid = $vv_config['CoEligibilityWidget']['co_dashboard_widget_id'];
?>

<script type="module">
<?php if(Configure::read('debug') > 0): ?>
    import EligibilityPanel from '<?php print $this->webroot ?>eligibility_widget/js/eligibility-panel.js?time=<?php print time()?>';
    import {constructItems} from '<?php print $this->webroot ?>eligibility_widget/js/helpers.js?time=<?php print time()?>';
  <?php else: ?>
    import EligibilityPanel from '<?php print $this->webroot ?>eligibility_widget/js/eligibility-panel.js';
    import {constructItems} from '<?php print $this->webroot ?>eligibility_widget/js/helpers.js';
  <?php endif; ?>

  const app = Vue.createApp({
    data() {
      return {
        items: {},
        allCous: <?php print json_encode($vv_all_cous ?? []); ?>,
        allCousList: <?php print json_encode($vv_all_cous_list ?? []); ?>,
        allOises: <?php print json_encode($vv_all_oises ?? []); ?>,
        activeCouList: <?php print json_encode($vv_active_cou_ids ?? []); ?>,
        activeOisList: <?php print json_encode($vv_active_ois_ids ?? []); ?>,
        error: '',
        successTxt: '',
        warningTxt: '',
        core: {
          coPersonId: '<?php print $vv_co_person_id; ?>',
          coId: '<?php print $vv_co_id; ?>',
          widget: 'widget<?php print $divid; ?>',
          eligibilityWidgetId: '<?php print $vv_config['CoEligibilityWidget']['id']; ?>',
          webRoot: '<?php print $this->webroot; ?>',
          mode: <?php print json_encode($vv_config['CoEligibilityWidget']['mode']);?>,
        },
        txt: {
          add:                 "<?php print _txt('pl.op.eligibilitywidget.verify'); ?>",
          addItem:             "<?php print _txt('pl.op.eligibilitywidget.verify-a', array('Society')); ?>",
          addFail:             "<?php print _txt('er.db.save-a', array('CoPersonRole')) ?>",
          cancel:              "<?php print _txt('op.cancel'); ?>",
          confirm:             "<?php print _txt('op.confirm'); ?>",
          done:                "<?php print _txt('op.done'); ?>",
          edit :               "<?php print _txt('pl.op.eligibilitywidget.verify'); ?>",
          error401:            "<?php print _txt('er.http.401.unauth');?>",
          error500:            "<?php print _txt('er.http.500');?>",
          ok:                  "<?php print _txt('op.ok'); ?>",
          remove:              "<?php print _txt('op.remove'); ?>",
          sync:                "<?php print _txt('pl.op.eligibilitywidget.sync'); ?>",
          leave:               "<?php print _txt('op.svc.leave'); ?>",
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
    computed: {
      isSuccess() {
        return this.successTxt != undefined
               && this.successTxt !== ''
               && (this.error == undefined || this.error === '')
               && (this.warningTxt == undefined || this.warningTxt === '')
      },
      isFailure() {
        return this.error != undefined
               && this.error !== ''
               && (this.successTxt == undefined || this.successTxt === '')
               && (this.warningTxt == undefined || this.warningTxt === '')
      },
      isWarning() {
        return this.warningTxt != undefined
               && this.warningTxt !== ''
               && (this.error == undefined || this.error == '')
               && (this.successTxt == undefined || this.successTxt == '')
      }
    },
    methods: {
      responseHandle(items) {
        return constructItems(this.allCousList, items)
      },
      getItems() {
        // Currently CoPersonRoles do not support filtering by OIS. Neither by on/off flag or OIS ID.
        // As a result i will get all the Roles and i will filter them using Javascript.
        const url = '<?php print $this->webroot ?>eligibility_widget/co_eligibility_widgets/personroles/<?php print $vv_config['CoEligibilityWidget']['id'];?>.json?copersonid=<?php print $vv_co_person_id ?>';
        // Since we are defining a successCallback function nothing will be returned
        callRegistryAPI(url, 'GET', 'json', this.setItems, '', this.generalXhrFailCallback);
      },
      getActiveCousOis() {
        const url = '<?php print $this->webroot ?>eligibility_widget/co_eligibility_widgets/active/<?php print $vv_config['CoEligibilityWidget']['id'];?>.json';
        // Since we are defining a successCallback function nothing will be returned
        callRegistryAPI(url, 'GET', 'json', this.setActive, '', this.generalXhrFailCallback);
      },
      getAllCouOis() {
        // XXX Here we need to fetch all the whitelisted records. Either COUs or OIS Registration. The co_id need to be replaced
        //     with the Eligibility Widget Id
        const url = '<?php print $this->webroot ?>eligibility_widget/co_eligibility_widgets/all/<?php print $vv_config['CoEligibilityWidget']['id'];?>.json?';
        // Since we are defining a successCallback function nothing will be returned
        callRegistryAPI(url, 'GET', 'json', this.setAll, '', this.generalXhrFailCallback);
      },
      setItems(xhr) {
        this.items = this.responseHandle(xhr.responseJSON.CoPersonRoles); // XXX this will be the setter function when the XHR is in place
      },
      setActive(xhr) {
        this.activeCouList = xhr.responseJSON?.cous
        this.activeOisList = xhr.responseJSON?.oises

      },
      setAll(xhr) {
        this.allCous = xhr.responseJSON?.cous
        this.allOises = xhr.responseJSON?.oises
      },
      setError(txt) {
        this.error = txt;
      },
      setWarning(txt) {
        this.warningTxt = txt;
      },
      generalXhrFailCallback(xhr) {
        stopSpinner();
        this.successTxt = ''
        this.setWarning('')
        if(xhr?.responseJSON?.message) {
          this.setError(xhr.responseJSON.message)
        } else if(xhr.statusText != undefined && xhr.statusText != '') {
          this.setError(xhr.statusText)
        } else {
          this.setError(this.txt.error500);
        }
      }
    },
    mounted() {
      this.getItems();
    }
  }).mount('#eligibility-widget<?php print $divid ?>');
</script>

<!-- TEMPLATE -->
<div id="eligibility-widget<?php print $divid ?>"
     class="cm-ssw"
     v-cloak>
  <div v-if="isFailure"
       id="eligibility-widget-error<?php print $divid ?>-alert"
       class="alert alert-danger"
       role="alert">
    {{ this.error }}
  </div>
  <div v-if="isSuccess"
       id="eligibility-widget-success<?php print $divid ?>-alert"
       class="alert alert-success"
       role="alert">
    {{ this.successTxt }}
  </div>
  <div v-if="isWarning"
       id="eligibility-widget-waring<?php print $divid ?>-alert"
       class="alert alert-warning"
       role="alert">
    {{ this.warningTxt }}
  </div>
  <eligibility-panel
    v-if="this.core.mode == 'CR'"
    :items="items"
    :eligibleitems="allCous"
    :activecoulist="activeCouList"
    :core="core"
    :txt="txt">
  </eligibility-panel>
  <eligibility-panel
    v-if="this.core.mode == 'OR'"
    :items="items"
    :eligibleitems="allOises"
    :activecoulist="activeOisList"
    :core="core"
    :txt="txt">
  </eligibility-panel>
</div>
