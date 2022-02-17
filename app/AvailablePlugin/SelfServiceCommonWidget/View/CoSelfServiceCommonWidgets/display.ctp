<?php
/**
 * COmanage Registry Self Service Common Widget Display View
 *
 * This widget provides common CSS and JavaScript for all Self Service Dashboard Widgets
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

  // Figure out the widget ID so we can overwrite the dashboard's widget div
  $divid = $vv_config['CoSelfServiceCommonWidget']['co_dashboard_widget_id'];
  
  // Include common CSS and JavaScript directly below. In particular, we do *not* attempt to build a 
  // <script src=""> tag because it will generate an undesirable synchronous ajax request as the widgets are loaded.
?>

<style type="text/css">
  .cm-self-service-widget {
    border: 1px solid var(--cmg-color-gray-border);
    padding: 1em;
  }
  .cm-self-service-widget .hidden {
    display: none;
  }
  .cm-self-service-widget-display,
  .cm-self-service-widget-form {
    cursor: pointer;
    display: flex;
    justify-content: space-between;
    align-items: center;
  }
  ul.cm-self-service-widget-field-list {
    list-style: none;
    display: flex;
    margin: 0;
    padding: 0;
  }
  ul.cm-self-service-widget-field-list li {
    padding: 0 1em;
  }
</style>

<script>
  $(function() {
    
    // Common function for switching between self service widget read-only display and form for editing
    $('.cm-self-service-widget-display').click(function(e) {
      e.preventDefault();
      e.stopPropagation();
      $(this).addClass('hidden');
      $(this).next('.cm-self-service-widget-form').removeClass('hidden');
    });
  
    // Cancel a self service form and return to read-only display
    $('.cm-self-service-widget-cancel').click(function(e) {
      e.preventDefault();
      e.stopPropagation();
      parentWidget = $(this).closest('.cm-self-service-widget');
      $(parentWidget).find('.cm-self-service-widget-form').addClass('hidden');
      $(parentWidget).find('.cm-self-service-widget-display').removeClass('hidden');
    });
    
  });
</script>