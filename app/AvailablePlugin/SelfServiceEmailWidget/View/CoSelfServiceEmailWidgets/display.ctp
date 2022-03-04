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

<div id="cm-ssw-email-widget" class="cm-ssw">
  <div class="cm-ssw-display">
    <ul class="cm-ssw-field-list">
      <?php foreach($vv_email_addresses as $k => $e): ?>
        <li id="cm-ssw-display-entity-id-<?php print $e['EmailAddress']['id'] ?>">
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
      <?php foreach($vv_email_addresses as $k => $e): ?>
        <div class="cm-ssw-form-row" id="cm-ssw-form-entity-id-<?php print $e['EmailAddress']['id'] ?>">
          <span class="cm-ssw-form-row-fields">
            <span class="cm-ssw-form-field form-group">
              <input type="text" class="form-control cm-ssw-form-field-email" value="<?php print $e['EmailAddress']['mail'] ?>"/>
            </span>
            <span class="cm-ssw-form-field form-group">
            <?php
              $attrs['value'] = (isset($e['EmailAddress']['type']) ? $e['EmailAddress']['type'] : "");
              $attrs['empty'] = false;
              $attrs['class'] = 'form-control cm-ssw-form-field-type';
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
      <div class="cm-ssw-form-row cm-ssw-form-actions">
        <div class="cm-ssw-add">
          <a href="#" class="cm-ssw-add-link">
            <em class="material-icons" aria-hidden="true">add_circle</em>
            <?php print _txt('pl.self_email_widget.add'); ?>
          </a>
        </div>
        <div class="cm-ssw-submit-buttons">
          <button class="btn btn-small cm-ssw-update-cancel"><?php print _txt('op.cancel'); ?></button>
          <button class="btn btm-small btn-primary cm-ssw-save"><?php print _txt('op.save'); ?></button>
        </div>
      </div>  
    </form>  
  </div>
  <div class="cm-ssw-add-form hidden">
    <form action="#">
      <div class="cm-ssw-form-row">
        <span class="cm-ssw-form-row-fields">
          <span class="cm-ssw-form-field form-group">
            <input type="text" id="cm-ssw-email-address-new" class="form-control cm-ssw-form-field-email" value=""/>
          </span>
          <span class="cm-ssw-form-field form-group">
          <?php
          $attrs['value'] = "";
          $attrs['empty'] = false;
          $attrs['class'] = 'form-control cm-ssw-form-field-type';
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

  <?php
    // Include widget-specific javascript
    print $this->element('javascript'); 
  ?>
  
  <?php /*
  <div style="white-space: pre;">
    <?php print_r($vv_email_addresses); ?>
  </div> */ ?>
</div>