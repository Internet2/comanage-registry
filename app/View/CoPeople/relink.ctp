<?php
/**
 * COmanage Registry Relink View
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
 * @since         COmanage Registry v0.9.1
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

  // Get a pointer to our model
  $model = $this->name;
  $req = Inflector::singularize($model);
  $modelpl = Inflector::tableize($req);
  
  if(empty($this->request->params['named']['tocopersonid'])) {
    // Render index view as people picker
    include(APP . "View/" . $model . "/index.ctp");
  }
  
  if(!empty($this->request->params['named']['tocopersonid'])) {
    $params = array('title' => $title_for_layout);
    print $this->element("pageTitle", $params);
    
    // Add breadcrumbs
    print $this->element("coCrumb");
    $args = array();
    $args['plugin'] = null;
    $args['controller'] = 'co_people';
    $args['action'] = 'index';
    $args['co'] = $cur_co['Co']['id'];
    $this->Html->addCrumb(_txt('me.population'), $args);
    if(!empty($vv_co_org_identity_link['CoPerson'])) {
      $args = array(
        'controller' => 'co_people',
        'action' => 'canvas',
        $vv_co_org_identity_link['CoPerson']['id']
      );
      $this->Html->addCrumb(generateCn($vv_co_org_identity_link['CoPerson']['PrimaryName']), $args);
    } elseif(!empty($vv_co_person_role['CoPersonRole'])) {
      $args = array(
        'controller' => 'co_people',
        'action' => 'canvas',
        $vv_co_person_role['CoPersonRole']['co_person_id']
      );
      $this->Html->addCrumb(generateCn($vv_co_person_role['CoPerson']['PrimaryName']), $args);
    }
    $this->Html->addCrumb(_txt('op.relink'));
    
    // And start the form according to what we're relinking
    
    if(!empty($vv_co_org_identity_link['CoPerson'])) {
      print $this->Form->create(
        'CoOrgIdentityLink',
        array(
          'url' => array(
            'action' => 'edit',
            $vv_co_org_identity_link['CoOrgIdentityLink']['id']
          ),
          'type'   => 'post',
          'inputDefaults' => array(
            'label' => false,
            'div'   => false
          )
        )
      );
      
      print $this->Form->hidden('org_identity_id',
                                array('default' => $vv_co_org_identity_link['CoOrgIdentityLink']['org_identity_id'])) . "\n";
    } elseif(!empty($vv_co_person_role['CoPersonRole'])) {
      print $this->Form->create(
        'CoPersonRole',
        array(
          'url' => array(
            'action' => 'relink',
            $vv_co_person_role['CoPersonRole']['id']
          ),
          'type'   => 'post',
          'inputDefaults' => array(
            'label' => false,
            'div'   => false
          )
        )
      );
    }
    
    // Set the target (new) CO Person ID
    print $this->Form->hidden('co_person_id',
                              array('default' => $vv_to_co_person['CoPerson']['id'])) . "\n";
  }
?>
<?php
  if(!empty($this->request->params['named']['tocopersonid'])) {
    // Pull the appropriate confirmation message
    
    if(!empty($vv_co_org_identity_link['CoPerson'])) {
      include(APP . "View/" . $model . "/relink-org.ctp");
    } elseif(!empty($vv_co_person_role['CoPersonRole'])) {
      include(APP . "View/" . $model . "/relink-role.ctp");
    }
  }
?>
