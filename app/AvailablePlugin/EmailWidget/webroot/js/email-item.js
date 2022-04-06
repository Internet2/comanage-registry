/**
 * COmanage Registry Email Widget Component JavaScript - Email Item
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
import EmailDialog from './email-dialog.js';
export default {
  props: {
    mail: String,
    primary: Boolean,
    id: String,
    editing: Boolean,
    core: Object,
    txt: Object
  },
  components: {
    EmailDialog
  },
  methods: {
    deleteConfirm(e, widgetId, id) {
      e.prevent;
      e.stop;
      this.$refs.modal.show();
    },
    deleteEmail(e,id) {
      e.prevent;
      e.stop;
      let url = this.core.webRoot + 'email_addresses/' + id + '.json';
      callRegistryAPI(url, 'DELETE', 'html', this.deleteSuccessEmailCallback, '', this.deleteFailEmailCallback);
    },
    deleteSuccessEmailCallback(xhr) {
      this.$parent.refreshDisplay();
    },
    deleteFailEmailCallback(xhr) {
      if(xhr.status == 400 || xhr.status == 404) {
        this.$parent.$parent.setError(this.txt.deleteFail);
      } else {
        this.$parent.$parent.generalXhrFailCallback(xhr);
      }
    },
    makePrimaryEmail(e,id) {
      alert("Unimplemented.");
    }
  },
  template: `
    <li :key="id" :data-entity-id="id">
      <div v-if="editing && (this.core.editEmails || (!primary || this.core.deletePrimary))" class="cm-ssw-edit-container">
        <div class="field-actions-menu dropdown">
          <button class="cm-ssw-dropdown-toggle btn btn-primary" data-toggle="dropdown" aria-haspopup="1" aria-expanded="false">
            <em className="material-icons" aria-hidden="true">settings</em>
            {{ this.mail }}
            <span v-if="primary" class="mr-1 badge badge-outline-primary">{{ txt.primary }}</span>
          </button>  
          <ul class="dropdown-menu">
            <li v-if="this.core.editEmails">
              <button 
                @click="editEmail($event, this.id)" 
                class="cm-ssw-edit-item-button btn btn-small dropdown-item">
                <em className="material-icons" aria-hidden="true">edit</em>
                {{ txt.edit }}
              </button>    
            </li>
            <li v-if="!primary">
              <button 
                @click="makePrimaryEmail($event, this.id)" 
                class="cm-ssw-edit-item-button btn btn-small dropdown-item">
                <em className="material-icons" aria-hidden="true">check</em>
                {{ txt.makePrimary }}
              </button>    
            </li>
            <li v-if="!primary || this.core.deletePrimary">
              <button 
                @click="deleteConfirm($event, this.core.widget, this.id)" 
                class="cm-ssw-delete-item-button btn btn-small dropdown-item">
                <em className="material-icons" aria-hidden="true">delete</em>
                {{ txt.delete }}
              </button>
            </li>
          </ul>         
          <email-dialog
            v-if="!primary || this.core.deletePrimary"
            ref="modal"
            type="delete"
            :title="this.txt.deleteModalTitle"
            :message="this.txt.deleteModalMessage"
            :id="this.id"
            :core="this.core"
            :txt="this.txt">
          </email-dialog>
        </div>
      </div> 
      <div v-else class="cm-ssw-view-container">     
        <span v-else class="email-address">{{ this.mail }}</span> 
        <!-- XXX badges should probably be generated using a template "slot" so we can generate them using PHP 
             as we do elsewhere and pass the markup down to the child component. This is the only badge in this
             plugin, however, so we're ok for now. --> 
        <span v-if="primary" class="mr-1 badge badge-outline-primary">{{ txt.primary }}</span>
      </div>
    </li>
  `
}