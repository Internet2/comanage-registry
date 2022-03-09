<?php
/**
 * COmanage Registry Self Service Email Widget Display View
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
  $divid = $vv_config['CoSelfServiceEmailWidget']['co_dashboard_widget_id'];
?>

<?php
// Include widget-specific css
print $this->element('css');
?>

<div id="cm-ssw-email-widget" class="cm-ssw">
  <div class="cm-ssw-display">
    <ul class="cm-ssw-field-list">
      <?php foreach($vv_email_addresses as $k => $e): ?>
        <li id="cm-ssw-display-entity-id-<?php print $e['EmailAddress']['id'] ?>" 
            data-entity-id="<?php print $e['EmailAddress']['id'] ?>">
          <?php 
            print $e['EmailAddress']['mail'];
            if($k === array_key_first($vv_email_addresses)) { // XXX when "Primary" is available, test for it here.
              print " " . $this->Badge->badgeIt(
                _txt('fd.name.primary_name'),
                $this->Badge->getBadgeColor('Primary'),
                false,
                true
              );
            }    
          ?>
        </li>
      <?php endforeach; ?>
    </ul>
    <div class="cm-self-service-submit-buttons">
      <button class="btn btn-small btn-primary"><?php print _txt('op.edit'); ?></button>
    </div>  
  </div>
  <div class="cm-ssw-update-form hidden">
    <form action="#">
      <div class="cm-ssw-update-form-rows">
      <?php foreach($vv_email_addresses as $k => $e): ?>
        <div class="cm-ssw-form-row" id="cm-ssw-form-entity-id-<?php print $e['EmailAddress']['id'] ?>" 
             data-entity-id="<?php print $e['EmailAddress']['id'] ?>">
          <span class="cm-ssw-form-row-fields">
            <span class="cm-ssw-form-field form-group">
              <label for="cm-ssw-form-field-email-<?php print $e['EmailAddress']['id'] ?>">
                <?php print _txt('pl.self_email_widget.fd.email'); ?>
              </label> 
              <input type="text" 
                     id="cm-ssw-form-field-email-<?php print $e['EmailAddress']['id'] ?>"
                     class="form-control cm-ssw-form-field-email" 
                     value="<?php print $e['EmailAddress']['mail'] ?>"/>
            </span>
            <span class="cm-ssw-form-field form-group cm-ssw-form-field-type">
              <label for="cm-ssw-form-field-email-type-<?php print $e['EmailAddress']['id'] ?>">
                 <?php print _txt('pl.self_email_widget.fd.type'); ?>
               </label> 
              <?php
                $attrs['value'] = (isset($e['EmailAddress']['type']) ? $e['EmailAddress']['type'] : "");
                $attrs['empty'] = false;
                $attrs['id'] = 'cm-ssw-form-field-email-type-' . $e['EmailAddress']['id'];
                $attrs['class'] = 'form-control cm-ssw-form-field-email-type';
                $attrs['required'] = 'required';
                print $this->Form->select('type', $vv_available_types, $attrs);
              ?>
            </span>  
            <span class="cm-ssw-form-field form-check">
              <input type="radio" class="form-check-input cm-ssw-form-field-primary" 
                     name="cm-ssw-email-primary" 
                     id="cm-ssw-email-<?php print $k; ?>"
                     <?php print ($k === array_key_first($vv_email_addresses)) ? ' checked' : ''; ?>/>
              <label class="form-check-label" for="cm-ssw-email-<?php print $k; ?>"><?php print _txt('pl.self_email_widget.primary');?></label>
            </span>
          </span>
          <span class="cm-ssw-form-row-actions">
            <span class="cm-ssw-form-field cm-ssw-form-field-delete">
              <a href="#" class="cm-ssw-form-field-delete-email-link" data-entity-id="<?php print $e['EmailAddress']['id'] ?>">
                <em class="material-icons" aria-hidden="true">delete</em>
                <?php print _txt('op.delete');?>
              </a>
            </span>
          </span>  
        </div>
      <?php endforeach; ?>
      </div>
      <div class="cm-ssw-form-row cm-ssw-form-actions">
        <div class="cm-ssw-add">
          <a href="#" class="cm-ssw-add-link">
            <em class="material-icons" aria-hidden="true">add_circle</em>
            <?php print _txt('pl.self_email_widget.add'); ?>
          </a>
        </div>
        <div class="cm-ssw-submit-buttons">
          <button class="btn btn-small cm-ssw-update-cancel"><?php print _txt('op.cancel'); ?></button>
          <button class="btn btm-small btn-primary cm-ssw-update-email-save-link"><?php print _txt('op.save'); ?></button>
        </div>
      </div>  
    </form>  
  </div>
  <div class="cm-ssw-add-form hidden">
    <form action="#">
      <div class="cm-ssw-form-row">
        <span class="cm-ssw-form-row-fields">
          <span class="cm-ssw-form-field form-group">
            <label for="cm-ssw-email-address-new">
              <?php print _txt('pl.self_email_widget.fd.email'); ?>
            </label> 
            <input type="text" id="cm-ssw-email-address-new" class="form-control cm-ssw-form-field-email" value="" required="required"/>
          </span>
          <span class="cm-ssw-form-field form-group cm-ssw-form-field-type">
            <label for="cm-ssw-form-field-email-type-new">
              <?php print _txt('pl.self_email_widget.fd.type'); ?>
            </label> 
            <?php
            $attrs['value'] = "";
            $attrs['empty'] = false;
            $attrs['id'] = 'cm-ssw-form-field-email-type-new';
            $attrs['class'] = 'form-control';
            print $this->Form->select('cm-ssw-email-type', $vv_available_types, $attrs);
            print $this->Form->hidden('cm-ssw-email-co-person-id', array('value' => $vv_co_person_id));
            ?>
          </span>  
          <span class="cm-ssw-form-field form-check">
            <input type="checkbox" class="form-check-input cm-ssw-form-field-primary" name="cm-ssw-email-make-primary" id="cm-ssw-email-make-primary"/>
            <label class="form-check-label" for="cm-ssw-email-make-primary"><?php print _txt('pl.self_email_widget.make.primary');?></label>
          </span>
        </span>
        <div class="cm-ssw-submit-buttons">
          <button class="btn btn-small cm-ssw-add-cancel"><?php print _txt('op.cancel'); ?></button>
          <button class="btn btm-small btn-primary cm-ssw-add-email-save-link"><?php print _txt('op.add'); ?></button>
        </div>
      </div>
    </form>
  </div>

  <div class="modal" id="cm-ssw-email-modal" 
       tabindex="-1" role="dialog" aria-labelledby="cm-ssw-email-modal-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="cm-ssw-email-modal-title"><?php print _txt('op.confirm'); ?></h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body" id="cm-ssw-email-modal-body">
          --
        </div>
        <div class="modal-footer" id="cm-ssw-email-modal-confirm-footer">
          <button type="button" id="cm-ssw-email-modal-cancel" 
                  class="btn btn-small" data-dismiss="modal"><?php print _txt('op.cancel'); ?></button>
          <button type="button" id="cm-ssw-email-modal-confirm" 
                  class="btn btn-small btn-primary"><?php print _txt('op.confirm'); ?></button>
        </div>
        <div class="modal-footer" id="cm-ssw-email-modal-info-footer">
          <button type="button" id="cm-ssw-email-modal-cancel"
                  class="btn btn-small btn-primary" data-dismiss="modal"><?php print _txt('op.ok'); ?></button>
        </div>
      </div>
    </div>
  </div>

  <?php
    // Include widget-specific javascript
    print $this->element('javascript'); 
  ?>
</div>
