<?php
/**
 * COmanage Registry jQuery sidebar buttons
 * Generates buttons in a sidebar
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
 * @since         COmanage Registry v1.0.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */
?>
<div class="sidebar">
  <ul id="menu">
    <?php
    foreach($sidebarButtons as $button => $link){
      print '<li>';
      // Clean data
      $icontitle = '<span class="ui-icon ui-icon-'
        . $link['icon']
        . '"></span>'
        . $link['title'];

      $url = $link['url'];

      $options = array();

      if(isset($link['options'])) {
        $options = (array)$link['options'];
      }

      $options['escape'] = FALSE;

      if(!empty($link['confirm'])) {
        // There is a built in Cake popup, which can be accessed by putting the confirmation text
        // as the fourth parameter to link. However, that uses a javascript popup rather than a
        // jquery popup, which is inconsistent with our look and feel.

        $options['onclick'] = "javascript:js_confirm_generic('" . _jtxt($link['confirm']) . "', '" . Router::url($url) . "'";

        if(!empty($link['confirmbtxt'])) {
          // Set the text for the confirmation button
          $options['onclick'] .= ", '" . $link['confirmbtxt'] . "'";
        }

        $options['onclick'] .= ");return false";
      }

      print $this->Html->link(
        $icontitle,
        $url,
        $options
      );
      print '</li>';
    }
    ?>
  </ul>

  <?php print $this->element('sidebarSearch'); ?>

</div>