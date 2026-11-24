<?php

/**
 * COmanage Registry SLIP Proofings Request Vouching View
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
 * @since         COmanage Registry v4.7.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

  // Get a pointer to our model
  $model = $this->name;
  $req = Inflector::singularize($model);

  // Add breadcrumbs
  print $this->element("coCrumb");

  $args = array(
    'plugin' => null,
    'controller' => 'co_people',
    'action' => 'index',
    'co' => $cur_co['Co']['id']
  );
  $this->Html->addCrumb(_txt('me.population'), $args);

  $args = array(
    'plugin' => null,
    'controller' => 'co_people',
    'action' => 'canvas',
    $slip_proofings[0]['ClaimantCoPerson']['id']
  );
  $this->Html->addCrumb(generateCn($slip_proofings[0]['ClaimantCoPerson']['PrimaryName']), $args);

  $args = array(
    'plugin' => 'slip_plugin',
    'controller' => 'slip_proofings',
    'action' => 'index',
    'spid' => $slip_proofings[0]['SlipProofing']['slip_plugin_id'],
    'coperson' => $slip_proofings[0]['ClaimantCoPerson']['id']
  );
  $this->Html->addCrumb(_txt('ct.slip_proofings.pl'), $args);

  $args = array(
    'plugin' => 'slip_plugin',
    'controller' => 'slip_proofings',
    'action' => 'view',
    $slip_proofings[0]['SlipProofing']['id']
  );
  $this->Html->addCrumb((string)$slip_proofings[0]['SlipProofing']['id'], $args);

  $this->Html->addCrumb($title_for_layout);

  // Add page title
  $params = array();
  $params['title'] = $title_for_layout;
    
  print $this->element("pageTitleAndButtons", $params);

  if(!empty($this->plugin)) {
    if(file_exists(APP . "Plugin/" . $this->plugin . "/View/" . $model . "/tabs.inc")) {
      include(APP . "Plugin/" . $this->plugin . "/View/" . $model . "/tabs.inc");
    } elseif(file_exists(LOCAL . "Plugin/" . $this->plugin . "/View/" . $model . "/tabs.inc")) {
      include(LOCAL . "Plugin/" . $this->plugin . "/View/" . $model . "/tabs.inc");
    }
  } else {
    if(file_exists(APP . "View/" . $model . "/tabs.inc")) {
      include(APP . "View/" . $model . "/tabs.inc");
    }
  }

  $submit_label = _txt('op.submit');
  
  print $this->Form->create(
    $req,
    array(
      'inputDefaults' => array(
        'label' => false,
        'div' => false
      )
    )
  );

  // Set up the hidden values

  print $this->Form->hidden('slip_proofing_id',
                            array('default' => $this->request->params['pass'][0])). "\n";

  $l = 1;
?>
<script type="text/javascript">
  $(function() {
    $("#vouchers").autocomplete({
      // We use Manager mode because we need all People to be returned regardless of CO Settings
      source: "<?php print $this->Html->url(array('plugin' => null, 'controller' => 'co_people', 'action' => 'find', 'co' => $cur_co['Co']['id'], 'mode' => PeoplePickerModeEnum::Manager)); ?>",
      minLength: 3,
      search: function( event, ui ) {
        // Add spinner
        $(this).closest('.co-loading-mini-input-container').find(".co-loading-mini").show();
      },
      response: function( event, ui ) {
        // Remove spinner
        $(this).closest('.co-loading-mini-input-container').find(".co-loading-mini").hide();
      },
      focus: function (event, ui) {
        event.preventDefault();
        $("#vouchers").val(ui.item.label + " (" + ui.item.value + ")");
      },
      select: function(event, ui) {
        $("#vouchers").val(ui.item.label + " (" + ui.item.value + ")");
        $("#voucher_co_person_id").val(ui.item.value);
        return false;
      }
    });
  });
</script>
<ul id="<?php print $this->action; ?>_slip_vouching_requests" class="fields form-list">
  <li>
    <div class="field-name">
      <?php
        // The voucher field is a manual placeholder, so we generate the label manually too
        print '<label for="vouchers">' . _txt('pl.slipplugin.voucher_co_person_id') . "</label>";
      ?><br />
    </div>
    <div class="field-info">
      <?php
        print '<div class="cm-inline-editable-field">';
        $inlineEditableFields['voucher-val']['set'] = false;

        if(!empty($slip_proofings[0]['VoucherCoPerson']['id'])
            && !empty($slip_proofings[0]['VoucherCoPerson']['PrimaryName'])) {

          $inlineEditableFields['voucher-val']['set'] = true;

          print '<div class="cm-ief-val">';
          print $this->Html->link(
            generateCn($slip_proofings[0]['VoucherCoPerson']['PrimaryName']),
            array(
              'controller' => 'co_people',
              'action' => 'canvas',
              $slip_proofings[0]['VoucherCoPerson']['id']
            )
          );

          print $this->element('inlineEditControls', array(
            'fieldName' => 'voucher_co_person_id',
            'actions' => array('remove')
          ));

          print '</div>';
        }

        $iefClasses = "cm-ief-widget";
        if ($inlineEditableFields['voucher-val']['set']) {
          $iefClasses .= " hidden";
        }
        ?>
        <div class="' . $iefClasses . '">
          <div class="ui-widget">
            <span class="co-loading-mini-input-container">
              <input id="vouchers"
                      placeholder="<?php print _txt("op.people.picker.placeholder"); ?>"
                      type="text" />
              <span class="co-loading-mini"><span></span><span></span><span></span></span>
            </span>
            <?php print $this->Form->hidden('voucher_co_person_id',
              array(
                'id'      => 'voucher_co_person_id',
                'default' => (!empty($slip_proofings[0]['VoucherCoPerson']['id'])) ? $slip_proofings[0]['VoucherCoPerson']['id'] : null
              )); ?>
        </div>
          <div class="field-desc">
            <span class="ui-icon ui-icon-info co-info"></span>
            <em><?php print _txt('pl.slipplugin.vouch.search.desc'); ?></em>
          </div>
        </div>
        <?php
        // We need to unlock the field for autocomplete manipulation
        $this->Form->unlockField('SlipProofing.voucher_co_person_id');
      ?>
    </div>
  </li>
  <li class="fields-submit">
    <div class="field-name">
      <span class="required"><?php print _txt('fd.req'); ?></span>
    </div>
    <div class="field-info">
      <?php print $this->Form->submit($submit_label); ?>
    </div>
  </li>
</ul>
<?php print $this->Form->end();
