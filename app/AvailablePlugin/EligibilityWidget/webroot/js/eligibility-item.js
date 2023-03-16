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
 * @since         COmanage Registry v4.2.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */
import EligibilityDialog from './eligibility-dialog.js';
export default {
  props: {
    item: String,
    id: String,
    editing: Boolean,
    core: Object,
    txt: Object
  },
  components: {
    EligibilityDialog
  },
  methods: {
    deleteConfirm(e, widgetId, id) {
      e.prevent;
      e.stop;
      this.$refs.modal.show();
    },
    deleteItem(e,id) {
      e.prevent;
      e.stop;
      let url = this.core.webRoot + 'email_addresses/' + id + '.json';
      callRegistryAPI(url, 'DELETE', 'html', this.deleteSuccessEmailCallback, '', this.deleteFailEmailCallback);
    },
    deleteSuccessItemCallback(xhr) {
      this.$parent.$parent.setError('');
      this.$parent.$parent.successTxt = 'Deleted';
      this.$parent.refreshDisplay();
    },
    deleteFailItemCallback(xhr) {
      if(xhr.status == 400 || xhr.status == 404) {
        this.$parent.$parent.successTxt = '';
        this.$parent.$parent.setError(this.txt.deleteFail);
      } else {
        this.$parent.$parent.generalXhrFailCallback(xhr);
      }
    }
  },
  template: `
    <li :key="id" :data-entity-id="id">
      <div v-if="editing" class="cm-ssw-edit-container">
        <div class="field-actions-menu dropdown">
          <button class="cm-ssw-dropdown-toggle btn btn-primary" data-toggle="dropdown" aria-haspopup="1" aria-expanded="false">
            <em className="material-icons" aria-hidden="true">settings</em>
            {{ this.item }}
          </button>  
          <ul class="dropdown-menu">
            <li>
              <button 
                @click="deleteConfirm($event, this.core.widget, this.id)" 
                class="cm-ssw-delete-item-button btn btn-small dropdown-item">
                <em className="material-icons" aria-hidden="true">delete</em>
                {{ txt.remove }}
              </button>
            </li>
          </ul>         
          <eligibility-dialog
            ref="modal"
            type="delete"
            :title="this.txt.removeModalTitle"
            :message="this.txt.removeModalMessage"
            :id="this.id"
            :core="this.core"
            :txt="this.txt">
          </eligibility-dialog>
        </div>
      </div> 
      <div v-else class="cm-ssw-view-container">     
        <span v-else class="eligibility-item">{{ this.item }}</span> 
      </div>
    </li>
  `
}