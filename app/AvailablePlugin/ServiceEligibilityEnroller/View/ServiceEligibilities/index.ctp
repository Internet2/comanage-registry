<?php
/**
 * COmanage Registry CO Service Eligibilities Index View
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
 * @link          https://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v4.1.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

  // Add breadcrumbs
  print $this->element("coCrumb");
  if($permissions['index']) {
    $args = array(
      'plugin' => null,
      'controller' => 'co_people',
      'action' => 'index',
      'co' => $cur_co['Co']['id']
    );
    $this->Html->addCrumb(_txt('me.population'), $args);
  }
  $args = array(
    'plugin' => null,
    'controller' => 'co_people',
    'action' => 'canvas',
    $vv_co_person[0]['CoPerson']['id']
  );

  $this->Html->addCrumb(generateCn($vv_co_person[0]['PrimaryName']), $args);
  
  $crumbTxt = _txt('ct.service_eligibilities.pl');
  $this->Html->addCrumb($crumbTxt);
  
  // Add page title
  $params = array();
  $params['title'] = _txt('ct.service_eligibilities.pl');

  // Add top links
  $params['topLinks'] = array();
  
  print $this->element("pageTitleAndButtons", $params);
?>

<div class="table-container">
  <table id="co_service_eligibilities">
    <thead>
      <tr>
        <th><?php print _txt('ct.co_services.1'); ?></th>
        <?php foreach($vv_co_person[0]['CoPersonRole'] as $role): ?>
        <th>
          <?php print filter_var($role[!empty($role['title']) ? 'title' : 'id'],FILTER_SANITIZE_STRING); ?>
        </th>
        <?php endforeach; // CoPersonRole ?>
      </tr>
    </thead>
    
    <tbody>
      <?php foreach($vv_available_services as $svc): ?>
      <tr>
        <td><?php print filter_var($svc['CoService']['description'],FILTER_SANITIZE_STRING); ?></td>
        <?php foreach($vv_co_person[0]['CoPersonRole'] as $role): ?>
        <td>
          <?php
            // Note we don't have to check allow_multiple here since each button
            // is a form that fires a page load, and allow_multiple is enforced
            // on save.
            
            $action = 'add';
            
            if(!empty($vv_eligibilities[ $role['id'] ])
               && in_array($svc['CoService']['id'], $vv_eligibilities[ $role['id'] ])) {
              // Eligibility is present, offer a remove link
              
              $action = 'remove';
            }
            
            if(in_array($role['id'], $permissions['managed'])) {
              print $this->Form->create(
                'ServiceEligibilityEnroller.ServiceEligibility',
                array(
                  'url' => array('action' => $action),
                  'inputDefaults' => array(
                    'label' => false,
                    'div' => false
                  )
                )
              );
              
              print $this->Form->hidden('co_person_role_id', array('default' => $role['id']));
              print $this->Form->hidden('co_service_id', array('default' => $svc['CoService']['id']));
              
              print $this->Form->submit(_txt('op.'.$action));
              
              print $this->Form->end();
            } else {
              // View only
              
              if($action == 'remove') {
                print _txt('pl.serviceeligibilityenroller.added');
              }
            }
          ?>
        </td>
        <?php endforeach; // CoPersonRole ?>
      </tr>
      <?php endforeach; // vv_available_services ?>
    </tbody>
  </table>
</div>
