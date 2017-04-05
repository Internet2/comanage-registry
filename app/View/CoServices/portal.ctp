<?php
/**
 * COmanage Registry CO Services Portal View
 *
 * Copyright (C) 2016 SURFnet BV
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
 * @copyright     Copyright (C) 2016 SURFnet BV
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v1.1.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
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
    <h2><?php print $c['CoService']['description']; ?></h2>
    <div class="co-card-content">
      <?php /* XXX keep the following for future RFE; these improve the portal layout:
      <div class="co-card-image">
        <img src="http://www.npr.org/about/images/press/Logos/npr_logo_rgb.JPG"/>
      </div>
      <div class="co-card-description">
        How about a description?
      </div> */ ?>
      <div class="co-card-icons">
      <?php

        if(!empty($c['CoService']['service_url'])) {
          print $this->Html->link('<i class="material-icons">public</i>',
            $c['CoService']['service_url'],
            array(
              'class' => 'co-card-link',
              'escape' => false,
              'title' => $c['CoService']['service_url']
            ));
        }
        if(!empty($c['CoService']['contact_email'])) {
          print $this->Html->link('<i class="material-icons">email</i>',
            'mailto:'.$c['CoService']['contact_email'],
            array(
              'class' => 'co-card-link',
              'escape' => false,
              'title' => 'mailto:'.$c['CoService']['contact_email']
            ));
        }

      ?>
      </div>

      <?php
/*
        if(!empty($c['CoService']['service_url'])) {
          print "Web: " . $this->Html->link($c['CoService']['service_url'],
              $c['CoService']['service_url'],
              array(
                'class' => 'co-card-link',
                'escape' => false,
                'title' => $c['CoService']['service_url']
              ));
        }
        print '<br/>';
        if(!empty($c['CoService']['contact_email'])) {
          print "Email: " . $this->Html->link($c['CoService']['contact_email'],
              'mailto:'.$c['CoService']['contact_email'],
              array(
                'class' => 'co-card-link',
                'escape' => false,
                'title' => 'mailto:'.$c['CoService']['contact_email']
              ));
        }
*/
      ?>

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