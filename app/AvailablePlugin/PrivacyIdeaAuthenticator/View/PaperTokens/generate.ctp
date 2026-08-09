<!--
/**
 * COmanage Registry PrivacyIDEA Authenticator Plugin Paper Token Fields
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
 * @package       registry-plugin
 * @since         COmanage Registry v4.4.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */
-->
<?php
  // Add page title
  $params = array();
  $params['title'] = $title_for_layout;

  // For Authenticators during enrollment
  if(!empty($vv_co_enrollment_authenticator)
     && ($vv_co_enrollment_authenticator['CoEnrollmentAuthenticator']['required'] == RequiredEnum::Optional)
     && !empty($this->request->params['named']['onFinish'])) {
    $params['topLinks'][] = $this->Html->link(_txt('op.skip'),
                                              urldecode($this->request->params['named']['onFinish']),
                                              array('class' => 'forwardbutton'));
  }

  // Add breadcrumbs
  print $this->element("coCrumb", array('authenticator' => 'PrivacyIdea'));
?>

<?php if(!empty($vv_token_info['otps'])): ?>
  <p>
    <?php print _txt('pl.privacyideaauthenticator.paper.intro'); ?>
  </p>
  <div class="co-info-topbox">
    <em class="material-icons">info</em>
    <?php print _txt('pl.privacyideaauthenticator.paper.caution'); ?>
  </div>
  <div class="co-info-topbox warn-level-a">
    <em class="material-icons error">warning</em>
    <?php print _txt('pl.privacyideaauthenticator.paper.warning'); ?>
    <?php if(!empty($this->request->params['named']['onFinish'])): ?>
      <?php print _txt('pl.privacyideaauthenticator.paper.continue'); ?>
    <?php endif; ?>    
  </div>
  <style>
    #add_paper_token {
      width: 200px;
    }
    #add_paper_token td,
    #add_paper_token th {
     padding: 0.5em;
     text-align: center;
    }
    #add_paper_token td:first-child,
    #add_paper_token th:first-child {
     width: 50px;
    }
    @media print {
      #top-menu, #banner, #co-footer, #navigation, #customFooter, #cm-print-button {
        display: none;
      }
    }
  </style>
  <div class="table-container">
    <table id="<?php print $this->action; ?>_paper_token" class="common-table">
      <thead>
       <tr>
          <th>#</th>
          <th>OTP</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach($vv_token_info['otps'] as $i => $otp): ?>
          <tr>
            <td><?php print $i+1; ?></td>
            <td><?php print $otp; ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php if(!empty($this->request->params['named']['onFinish'])): ?>
    <?php 
      print $this->Html->link(_txt('op.cont'),
                                            urldecode($this->request->params['named']['onFinish']),
                                            array('class' => 'btn btn-primary btn-lg'));
    ?>
    <button type="button" onclick="window.print();" id="cm-print-button" class="btn btn-default btn-lg">print backup codes</button>
  <?php else: ?>
    <button type="button" onclick="window.print();" id="cm-print-button" class="btn btn-primary btn-lg">print backup codes</button>
  <?php endif; ?>
  <div id="bc-dialog" role="alertdialog" class="ui-dialog-content ui-widget-content">
    <p>
      <span class="ui-icon ui-icon-alert co-alert"></span>
      <span id="dialog-text"><?php print _txt('pl.privacyideaauthenticator.paper.dialog'); ?></span>
    </p>
  </div>
<?php elseif(!empty($this->request->params['named']['onFinish'])): ?>
  <?php
    print $this->Html->link(_txt('op.cont'),
                                          urldecode($this->request->params['named']['onFinish']),
                                          array('class' => 'btn btn-primary btn-lg'));
  ?>
<?php elseif($this->action == 'view'): ?>
  <ul id="<?php print $this->action; ?>_paper_token" class="fields form-list">
    <li>
      <div class="field-name">
        <?php print _txt('pl.privacyideaauthenticator.fd.serial'); ?>
      </div>
      <div class="field-info">
        <?php
          print filter_var($paper_tokens[0]['PaperToken']['serial'],FILTER_SANITIZE_SPECIAL_CHARS);
        ?>
      </div>
    </li>
  </ul>
<?php endif; // vv_otps, view ?>

<script>
  $(function() {
    $("#bc-dialog").dialog({
      autoOpen: true,
      resizable: false,
      modal: true,
      title: 'Notice',
      buttons: {
        '<?php print _txt('pl.privacyideaauthenticator.paper.dialog.btn'); ?>': function() {
          $(this).dialog('close');
        }
      }
    });
  });
</script>
