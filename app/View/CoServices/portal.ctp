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

<table id="co_services">
  <thead>
    <tr>
      <th><?php print _txt('fd.desc'); ?></th>
      <th><?php print _txt('fd.svc.url'); ?></th>
      <th><?php print _txt('fd.svc.mail'); ?></th>
    </tr>
  </thead>
  
  <tbody>
    <?php $i = 0; ?>
    <?php foreach ($co_services as $c): ?>
    <tr class="line<?php print ($i % 2)+1; ?>">
      <td>
        <?php
          if(!empty($c['CoService']['service_url'])) {
            print $this->Html->link($c['CoService']['description'],
                                    $c['CoService']['service_url']);
          } else {
            print $c['CoService']['description'];
          }
        ?>
      </td>
      <td>
        <?php
          if(!empty($c['CoService']['service_url'])) {
            print $this->Html->link($c['CoService']['service_url'],
                                    $c['CoService']['service_url']);
          }
        ?>
      </td>
      <td>
        <?php
          if(!empty($c['CoService']['contact_email'])) {
            print $this->Html->link($c['CoService']['contact_email'],
                                    'mailto:'.$c['CoService']['contact_email']);
          }
        ?>
      </td>
    </tr>
    <?php $i++; ?>
    <?php endforeach; ?>
  </tbody>

</table>
