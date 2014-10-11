<!--
/**
 * COmanage Registry CO Person Match View
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
-->
<?php
  if(count($matches) > 0) {
    print '<h2 class="ui-state-default">Possible Matches</h2>';
    print "<ul>\n";
    
    foreach ($matches as $m) {
      print "<li>";
      print $this->Html->link(
        generateCn($m['PrimaryName']),
        array('controller' => 'co_people', 'action' => 'edit', $m['CoPerson']['id'], 'co' => $m['CoPerson']['co_id'])
      );
      if(isset($m['CoPersonRole'][0])) {
        print " ("
              . (!empty($m['CoPersonRole'][0]['title']) ? $m['CoPersonRole'][0]['title'] . ", " : "")
              . (!empty($m['CoPersonRole'][0]['affiliation'])
                 ? $vv_copr_affiliation_types[ $m['CoPersonRole'][0]['affiliation'] ]
                 : "")
              . ")";
      }
      print "</li>\n";
    }
    
    print "</ul>\n";
  }
