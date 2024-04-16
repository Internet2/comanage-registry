/**
 * COmanage Registry Eligibility Widget Component JavaScript - Eligibility Item
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
import EligibilityDialog from './eligibility-dialog.js?item=18';
export default {
  props: {
    item: String,
    id: String,
    isEditing: Object,
    core: Object,
    txt: Object
  },
  components: {
    EligibilityDialog
  },
  methods: {
    deleteConfirm(e) {
      e.prevent;
      e.stop;
      this.$refs.modal.show();
    },
    deleteSuccessRoleCallback(xhr) {
      stopSpinner();
      this.$parent.$parent.setError('');
      this.$parent.$parent.successTxt = `Status ${xhr.responseJSON?.status}`;
      this.$parent.refreshDisplay();
    },
    deleteFailRoleCallback(xhr) {
      stopSpinner();
      if(xhr.status == 400 || xhr.status == 404) {
        this.$parent.$parent.successTxt = '';
        this.$parent.$parent.setError(this.txt.deleteFail);
      } else {
        this.$parent.$parent.generalXhrFailCallback(xhr);
      }
    },
    syncItem(e,id) {
      e.prevent;
      e.stop;

      const url = `${this.core.webRoot}eligibility_widget/co_eligibility_widgets/sync/${this.core?.eligibilityWidgetId}.json?copersonrole=${id}`;
      displaySpinner();
      callRegistryAPI(
        url,
        'PUT',
        'json',
        this.deleteSuccessRoleCallback,
        '',
        this.deleteFailRoleCallback);
    },
    deleteItem(e,id) {
      e.prevent;
      e.stop;
      const url = `${this.core.webRoot}co_person_roles/${id}.json`;
      displaySpinner();
      // XXX Delete action does not return json or xml but plain text/html. Be aware
      //     of the type or it will fail
      callRegistryAPI(
        url,
        'DELETE',
        'html',
        this.deleteSuccessRoleCallback,
        '',
        this.deleteFailRoleCallback);
    }
  },
  template: `
    <li v-if="item != undefined && id != undefined" :key="id" :data-entity-id="id">
      <div v-if="isEditing" class="cm-ssw-edit-container">
        <div class="field-actions-menu dropdown">
          <button class="cm-ssw-dropdown-toggle btn btn-primary" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            <em className="material-icons" aria-hidden="true">settings</em>
            {{ this.item }}
          </button>  
          <ul class="dropdown-menu">
            <li>
              <button 
                @click="deleteConfirm($event)"
                class="cm-ssw-delete-item-button btn btn-small dropdown-item">
                <em className="material-icons" aria-hidden="true">sync</em>
                {{ this.txt.sync }}
              </button>
            </li>
          </ul>         
<!--          <eligibility-dialog-->
<!--            v-if="this.core.mode == 'CR'"-->
<!--            ref="modal"-->
<!--            type="delete"-->
<!--            :title="this.txt.removeModalTitle"-->
<!--            :message="this.txt.removeModalMessage"-->
<!--            :id="this.id"-->
<!--            :core="this.core"-->
<!--            :txt="this.txt">-->
<!--          </eligibility-dialog>-->
          <eligibility-dialog
            ref="modal"
            type="sync"
            :title="this.txt.removeModalTitle"
            :message="this.txt.removeModalMessage"
            :id="this?.id"
            :core="this.core"
            :txt="this.txt">
          </eligibility-dialog>
        </div>
      </div> 
      <div v-else class="cm-ssw-view-container">     
        <span class="eligibility-item">{{ this?.item }}</span> 
      </div>
    </li>
  `
}