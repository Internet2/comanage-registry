<?php
/**
 * COmanage Registry Announcements Widget Display View
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
 * @since         COmanage Registry v3.2.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */
?>
<ul>
<?php foreach($vv_widget_announcements as $a): ?>
  <li>
    <b><?php print $a['CoAnnouncement']['title']; ?></b>
    <?php if(!empty($a['PosterCoPerson']['PrimaryName']['id'])): ?>
      <br /><i><?php print filter_var(generateCn($a['PosterCoPerson']['PrimaryName']), FILTER_SANITIZE_SPECIAL_CHARS); ?></i>
    <?php endif; // PrimaryName ?>
    <p>
      <?php
        // Render HTML or not according to channel configuration
        if(isset($a['CoAnnouncementChannel']['publish_html']) && $a['CoAnnouncementChannel']['publish_html']) {
          print $a['CoAnnouncement']['body'];
        } else {
          print filter_var($a['CoAnnouncement']['body'], FILTER_SANITIZE_SPECIAL_CHARS);
        }
      ?>
      <?php  ?>
    </p>
  </li>
<?php endforeach; ?>
</ul>