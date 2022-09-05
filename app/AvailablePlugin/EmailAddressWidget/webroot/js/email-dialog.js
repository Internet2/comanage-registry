/**
 * COmanage Registry Email Widget Component JavaScript - Dialog Box
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
export default {
  props: {
    title: String,
    message: String,
    id: String,
    type: String,
    core: Object,
    txt: Object
  },
  methods: {
    generateId(prefix) {
      return  prefix + '-' + this.core.widget + '-' + this.id;
    },
    show() {
      jQuery(this.$el).modal('show');
    },
    delete(e,id) {
      this.$parent.deleteEmail(e,id);
      jQuery(this.$el).modal('hide');
    }
  },
  beforeMount() {
    jQuery(this.$el).modal();
  },
  template: `
    <div 
      className="modal" 
      :id="generateId('cm-ssw-widget-email-modal')"
      tabIndex="-1" 
      role="dialog" 
      :aria-labelledby="generateId('cm-ssw-email-modal-title')" 
      aria-hidden="true">
      <div className="modal-dialog modal-dialog-centered" role="document">
        <div className="modal-content">
          <div className="modal-header">
            <h5 className="modal-title" :id="generateId('cm-ssw-email-modal-title')">
                {{ this.title }}
            </h5>
            <button type="button" className="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div className="modal-body">
            {{ this.message }}
          </div>
          <div className="modal-footer">
            <button type="button" className="btn btn-small" data-dismiss="modal">
              {{ this.txt.cancel }}
            </button>
            <!-- TODO: expand this modal to handle other types -->            
            <button v-if="this.type == 'delete'" @click="this.delete($event, this.id)" type="button" className="btn btn-small btn-primary">
              {{ this.txt.delete }}
            </button>
          </div>
        </div>
      </div>
    </div>
    `
}