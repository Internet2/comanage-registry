<?php
/**
 * COmanage Registry CO Person Match View
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

  if(count($matches) > 0) {
    print '<div class="co-info-matchable">';
    print '<div class="co-info-matchable-title">';
    print _txt('rs.match.possible');
    print '<a href="#" class="close-button"><em class="material-icons">close</em></a>';
    print '</div>';
    print "<ul>\n";
    print '<li class="co-info-matchable-info">';
    print _txt('rs.match.info');
    print '</li>';
    foreach ($matches as $m) {
      print "<li>";
      print '<span class="co-info-matchable-item">';
      print '<span class="co-info-matchable-name">';
      print '<em class="co-info-matchable-icon material-icons">person</em>';
      print $this->Html->link(
        generateCn($m['PrimaryName']),
        array('controller' => 'co_people', 'action' => 'canvas', $m['CoPerson']['id'])
      );
      print ' <span class="text-muted-cmg cm-id-display">ID: ' . $m['CoPerson']['id'] . '</span>';
      print '</span>';
      print '<span class="co-info-matchable-role">';
      if(isset($m['CoPersonRole'][0])) {
        
        print (!empty($m['CoPersonRole'][0]['title']) ? $m['CoPersonRole'][0]['title']  : _txt('fd.title.none'))
              . (!empty($m['CoPersonRole'][0]['affiliation']) ? ", " 
              . $vv_copr_affiliation_types[ $m['CoPersonRole'][0]['affiliation'] ] : "");
      }
      print "</span>\n";
      print "</span>\n";
      print "</li>\n";
    }
    
    print "</ul>\n";
    print "</div>\n";
  }
