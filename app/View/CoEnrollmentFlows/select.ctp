<?php
/**
 * COmanage Registry CO Enrollment Flow Select View
 *
 * Copyright (C) 2012-14 University Corporation for Advanced Internet Development, Inc.
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
 * @copyright     Copyright (C) 2012-14 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.5
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
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
    <div class="mdl-cell mdl-cell--2-col center"><?php print _txt('fd.actions'); ?></div>
  </div>

  <?php $i = 0; ?>
  <?php foreach ($co_enrollment_flows as $c): ?>
    <div class="mdl-grid">
      <div class="mdl-cell mdl-cell--9-col mdl-cell--6-col-tablet mdl-cell--2-col-phone flow-name">
        <?php print Sanitize::html($c['CoEnrollmentFlow']['name']); ?>
      </div>
      <div class="mdl-cell mdl-cell--2-col actions center">
        <?php
          if($permissions['select']) {

            // begin button
            print $this->Html->link(_txt('op.begin'),
              array(
                'controller' => 'co_petitions',
                'action' => 'start',
                'coef' => $c['CoEnrollmentFlow']['id']
              ),
              array('class' => 'co-button mdl-button mdl-js-button mdl-button--raised mdl-js-ripple-effect')
            ) . "\n";

            // QR code button
            print $this->Html->link(
              $this->Html->image(
                'qrcode-icon.png',
                array(
                  'alt' => _txt('op.begin')
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
              array('class' => 'co-button qr-button mdl-button mdl-js-button mdl-button--raised mdl-js-ripple-effect','escape' => false)
            ) . "\n";
          }
        ?>
      </div>
    </div>
    <?php $i++; ?>
  <?php endforeach; ?>
</div>
