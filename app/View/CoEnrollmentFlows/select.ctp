<?php
/**
 * COmanage Registry CO Enrollment Flow Select View
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
 * @since         COmanage Registry v0.5
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

  // Add breadcrumbs
  print $this->element("coCrumb");
  $crumbTxt = _txt('op.select-a',array(_txt('ct.enrollment_flows.1')));
  $this->Html->addCrumb($crumbTxt);

  // Add page title
  $params = array();
  $params['title'] = $title_for_layout;

  print $this->element("pageTitleAndButtons", $params);

?>

<div id="co_enrollment_flows" class="co-grid co-grid-with-header mdl-shadow--2dp">
  <div class="mdl-grid co-grid-header">
    <div class="mdl-cell mdl-cell--9-col"><?php print _txt('fd.name'); ?></div>
    <div class="mdl-cell mdl-cell--2-col actions"><?php print _txt('fd.actions'); ?></div>
  </div>

  <?php $i = 0; ?>
  <?php foreach ($co_enrollment_flows as $c): ?>
    <div class="mdl-grid">
      <div class="mdl-cell mdl-cell--9-col mdl-cell--6-col-tablet mdl-cell--2-col-phone first-cell">
        <?php print filter_var($c['CoEnrollmentFlow']['name'],FILTER_SANITIZE_SPECIAL_CHARS); ?>
      </div>
      <div class="mdl-cell mdl-cell--2-col actions">
        <?php
          if($permissions['select']) {

            // begin button
            print $this->Html->link(_txt('op.begin') . ' <em class="material-icons" aria-hidden="true">forward</em>',
              array(
                'controller' => 'co_petitions',
                'action' => 'start',
                'coef' => $c['CoEnrollmentFlow']['id']
              ),
              array(
                'class' => 'co-button mdl-button mdl-js-button mdl-button--raised mdl-button--colored mdl-js-ripple-effect',
                'escape' => false
              )
            ) . "\n";

            // QR code button - requires GD2 library
            if (extension_loaded ("gd")) {
              print $this->Html->link(
                $this->Html->image(
                  'qrcode-icon.png',
                  array(
                    'alt' => _txt('op.display.qr.for',array(filter_var($c['CoEnrollmentFlow']['name'],FILTER_SANITIZE_SPECIAL_CHARS)))
                  )
                ),
                array(
                  'controller' => 'qrcode',
                  '?' => array(
                    'c' => $this->Html->url(
                      array(
                        'controller' => 'co_petitions',
                        'action' => 'start',
                        'coef' => $c['CoEnrollmentFlow']['id']
                      ),
                      array(
                        'full' => true,
                        'escape' => false
                      )
                    )
                  )
                ),
                array(
                  'class' => 'co-button qr-button mdl-button mdl-js-button mdl-button--raised mdl-button--colored mdl-js-ripple-effect', 
                  'escape' => false,
                  'title'  => _txt('op.display.qr.for',array($c['CoEnrollmentFlow']['name']))
                )
              ) . "\n";
            }
          }
        ?>
      </div>
    </div>
    <?php $i++; ?>
  <?php endforeach; ?>
  <div class="clearfix"></div>
</div>
