<?php
/**
 * COmanage Registry CO Services Portal View
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
 * @since         COmanage Registry v2.0.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

  // Add breadcrumbs
  print $this->element("coCrumb");
  $this->Html->addCrumb(_txt('ct.co_services.pl'));

  // Add page title
  $params = array();
  $params['title'] = $title_for_layout;

  print $this->element("pageTitleAndButtons", $params);
?>

<div id="co-services">
  
  <?php foreach ($co_services as $c): ?>
  <div class="co-card">
    <h2><?php print filter_var($c['CoService']['name'],FILTER_SANITIZE_SPECIAL_CHARS); ?></h2>
    <div class="co-card-content">
      <?php /* XXX keep the following for future RFE; these improve the portal layout:
      <div class="co-card-image">
        <img src="http://www.npr.org/about/images/press/Logos/npr_logo_rgb.JPG"/>
      </div> */ ?>
      <div class="co-card-description">
        <?php print filter_var($c['CoService']['description'],FILTER_SANITIZE_SPECIAL_CHARS); ?>
      </div>
      <div class="co-card-icons">
      <?php

        if(!empty($c['CoService']['service_url'])) {
          print $this->Html->link('<em class="material-icons" aria-hidden="true">public</em>',
            $c['CoService']['service_url'],
            array(
              'class' => 'co-card-link',
              'escape' => false,
              'title' => $c['CoService']['service_url']
            ));
        }
        if(!empty($c['CoService']['contact_email'])) {
          print $this->Html->link('<em class="material-icons" aria-hidden="true">email</em>',
            'mailto:'.$c['CoService']['contact_email'],
            array(
              'class' => 'co-card-link',
              'escape' => false,
              'title' => 'mailto:'.$c['CoService']['contact_email']
            ));
        }

      ?>
      </div>
    </div>
  </div>
  <?php endforeach; ?>

</div>

<script type="text/javascript">
$(function() {
  $(".co-card").click(function() {
    var url = $(this).find(".co-card-link").attr("href");
    if (url == "") {
      return;
    }
    location.href = url;
  });
});
</script>