<?php
/**
 * COmanage Registry CO Terms and Conditions Review View
 *
 * Copyright (C) 2013 University Corporation for Advanced Internet Development, Inc.
 * 
 * Licensed under the Apache License, Version 2.0 (the "License"); you may not use this file except in compliance with
 * the License. You may obtain a copy of the License at
 * 
 * http://www.apache.org/licenses/LICENSE-2.0
 * 
 * Unless required by applicable law or agreed to in writing, software distributed under
 * the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY
 * KIND, either express or implied. See the License for the specific language governing
 * permissions and limitations under the License.
 *
 * @copyright     Copyright (C) 2013 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.8.3
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */

  print $this->element("pageTitle", array('title' => _txt('fd.tc.for',
                                                          array(generateCn($vv_co_person['PrimaryName']), $cur_co['Co']['name']))));

  // Add breadcrumbs
  $this->Html->addCrumb(_txt('ct.co_terms_and_conditions.pl'));
?>
<script type="text/javascript">
  function open_tandc(title, tandcUrl, mode, agreeUrl) {
    // Set title
    $("div#dialog-review").dialog("option", "title", title);
    
    // Load T&C into iframe
    $("#tandc_content").attr("src", tandcUrl);
    
    // Set up buttons
    if(mode == 'agree') {
      $("div#dialog-review").dialog("option",
                                    "buttons",
                                    {
                                      "<?php print _txt('op.tc.agree'); ?>": function() {
                                        window.location=agreeUrl;
                                      }
                                    });
    } else {
      $("div#dialog-review").dialog("option",
                                    "buttons",
                                    {
                                      "<?php print _txt('op.ok'); ?>": function() {
                                        $(this).dialog("close");
                                      }
                                    });
    }
    
    // Open dialog
    $("#dialog-review").dialog("open");
  }
  
  $(function() {
    $("#dialog-review").dialog({
      autoOpen: false,
      height: 725,
      width: 750,
      modal: true,
      buttons: {
        "<?php print _txt('op.ok'); ?>": function() {
          $(this).dialog("close");
        }
      }
    })
  });
</script>
<?php if(empty($vv_co_terms_and_conditions)): ?>
<div class="ui-state-highlight ui-corner-all" style="margin-top: 20px; padding: 0 .7em;"> 
  <p>
    <span class="ui-icon ui-icon-info" style="float: left; margin-right: .3em;"></span>
    <strong><?php print _txt('fd.tc.none'); ?></strong>
  </p>
</div>
<?php else: ?>
<table id="cous" class="ui-widget">
  <thead>
    <tr class="ui-widget-header">
      <th><?php print _txt('fd.desc'); ?></th>
      <th><?php print _txt('fd.status'); ?></th>
      <th><?php print _txt('fd.actions'); ?></th>
    </tr>
  </thead>
  
  <tbody>
    <?php $i = 0; ?>
    <?php foreach ($vv_co_terms_and_conditions as $c): ?>
    <tr class="line<?php print ($i % 2)+1; ?>">
      <td>
        <?php print $c['CoTermsAndConditions']['description']; ?>
      </td>
      <td>
        <?php
          if(!empty($c['CoTAndCAgreement'])) {
            print _txt('fd.tc.agree.yes')
                  . " ("
                  . $c['CoTAndCAgreement'][0]['identifier']
                  . ", "
                  . $this->Time->niceShort($c['CoTAndCAgreement'][0]['agreement_time'])
                  . ")";
          } else {
            print _txt('fd.tc.agree.no');
          }
        ?>
      </td>
      <td>
        <?php if(!empty($c['CoTAndCAgreement'])): ?>
        <button class="linkbutton"
                type="button"
                onClick="open_tandc('<?php print addslashes($c['CoTermsAndConditions']['description']); ?>',
                                    '<?php print addslashes($c['CoTermsAndConditions']['url']); ?>',
                                    'review',
                                    '')">
          <?php print _txt('op.view'); ?>
        </button>
        <?php else: ?>
        <button class="checkbutton"
                type="button"
                onClick="open_tandc('<?php print addslashes($c['CoTermsAndConditions']['description']); ?>',
                                    '<?php print addslashes($c['CoTermsAndConditions']['url']); ?>',
                                    'agree',
                                    '<?php
                                        print $this->Html->url(
                                          array('controller' => 'co_terms_and_conditions',
                                                'action' => 'agree',
                                                $c['CoTermsAndConditions']['id'],
                                                'copersonid' => $vv_co_person['CoPerson']['id'],
                                                'co' => $cur_co['Co']['id'])
                                        );
                                      ?>')">
          <?php print _txt('op.tc.agree'); ?>
        </button>
        <?php endif; ?>
      </td>
    </tr>
    <?php endforeach; ?>
  </tbody>
  
  <tfoot>
    <tr class="ui-widget-header">
      <th colspan="3">
      </th>
    </tr>
  </tfoot>
</table>
<?php endif; ?>

<div id="dialog-review" title="<?php print _txt('ct.co_terms_and_conditions.1'); ?>">
  <iframe id="tandc_content" height="600" width="700">
  </iframe>
</div>