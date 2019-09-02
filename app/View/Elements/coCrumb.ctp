<?php
/**
 * COmanage Registry CO Bread Crumb
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
 * @since         COmanage Registry v0.9.4
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

  // Always emit CO Name
  if(!empty($cur_co['Co']['name'])) {
    $args = array();
    $args['plugin'] = null;
    $args['controller'] = 'co_dashboards';
    $args['action'] = 'dashboard';
    $args['co'] = $cur_co['Co']['id'];
    $this->Html->addCrumb($cur_co['Co']['name'], $args);
  }
  
  // Possibly emit Authenticator specific breadcrumbs
  if(!empty($authenticator)) {
    // $authenticator = SshKey
    // $auth = ssh_key
    $auth = Inflector::underscore($authenticator);
    // $authpl = ssh_keys
    $authpl = Inflector::tableize($authenticator);
    
    $args = array();
    $args['plugin'] = null;
    $args['controller'] = 'co_people';
    $args['action'] = 'index';
    $args['co'] = $cur_co['Co']['id'];
    $this->Html->addCrumb(_txt('me.population'), $args);
    
    $args = array();
    $args['plugin'] = null;
    $args['controller'] = 'co_people';
    $args['action'] = 'canvas';
    $args[] = $vv_co_person['CoPerson']['id'];
    $this->Html->addCrumb(generateCn($vv_co_person['PrimaryName']), $args);

    $args = array();
    $args['plugin'] = null;
    $args['controller'] = 'authenticators';
    $args['action'] = 'status';
    $args['copersonid'] = $vv_co_person['CoPerson']['id'];
    $this->Html->addCrumb(_txt('ct.authenticators.pl'), $args);
    
    if($this->action == 'index') {
      $this->Html->addCrumb($vv_authenticator['Authenticator']['description']);
    } else {
      $args = array();
      $args['plugin'] = $auth . '_authenticator';
      $args['controller'] = $authpl;
      $args['action'] = 'index';
      $args['authenticatorid'] = $vv_authenticator[$authenticator.'Authenticator']['authenticator_id'];
      $args['copersonid'] = $vv_co_person['CoPerson']['id'];
      $this->Html->addCrumb($vv_authenticator['Authenticator']['description'], ($this->action != 'manage' ? $args : null));

      $this->Html->addCrumb(_txt('op.' . $this->action));
    }
  }
  
  // Possibly emit MVPA specific breadcrumbs
  if(!empty($mvpa)) {
    if(!empty($vv_pid['codeptid'])) {
      // CO Department
      $args = array(
        'plugin' => null,
        'controller' => 'co_departments',
        'action' => 'index',
        'co' => $cur_co['Co']['id']
      );
      $this->Html->addCrumb(_txt('ct.co_departments.pl'), $args);
      
      $args = array(
        'controller' => 'co_departments',
        'action' => 'edit',
        $vv_pid['codeptid']
      );
      $this->Html->addCrumb($vv_bc_name, $args);
    } elseif(!empty($vv_pid['cogroupid'])) {
      // CO Group
      $args = array(
        'plugin' => null,
        'controller' => 'co_groups',
        'action' => 'index',
        'co' => $cur_co['Co']['id']
      );
      $this->Html->addCrumb(_txt('ct.co_groups.pl'), $args);
      
      $args = array(
        'controller' => 'co_groups',
        'action' => 'edit',
        $vv_pid['cogroupid']
      );
      $this->Html->addCrumb($vv_bc_name, $args);
    } elseif(!empty($vv_pid['copersonid'])) {
      // CO Person
      $args = array(
        'plugin' => null,
        'controller' => 'co_people',
        'action' => 'index',
        'co' => $cur_co['Co']['id']
      );
      $this->Html->addCrumb(_txt('me.population'), $args);
  
      $args = array(
        'controller' => 'co_people',
        'action' => 'canvas',
        $vv_pid['copersonid']
      );
      $this->Html->addCrumb($vv_bc_name, $args);
    } elseif(!empty($vv_pid['copersonroleid'])) {
      // CO Person Role
      
      $args = array(
        'plugin' => null,
        'controller' => 'co_people',
        'action' => 'canvas',
        $vv_pbc_id
      );
      $this->Html->addCrumb($vv_pbc_name, $args);
  
      $args = array(
        'controller' => 'co_person_roles',
        'action' => 'edit',
        $vv_pid['copersonroleid']
      );
      $this->Html->addCrumb($vv_bc_name, $args);
    } else {
      // Org ID
      $args = array(
        'plugin' => null,
        'controller' => 'org_identities',
        'action' => 'index'
      );
      if(!$pool_org_identities) {
        $args['co'] = $cur_co['Co']['id'];
      }
      $this->Html->addCrumb(_txt('ct.org_identities.pl'), $args);
  
      $args = array(
        'controller' => 'orgIdentities',
        'action' => 'edit',
        $vv_pid['orgidentityid']
      );
      $this->Html->addCrumb($vv_bc_name, $args);
    }
    
    $crumbTxt = _txt('op.' . $this->action . '-a', array(_txt('ct.' . $mvpa . '.1')));
    $this->Html->addCrumb($crumbTxt);
  }
