<?php
/**
 * COmanage Registry Standard Dashboard Widgets (SDW) Controller
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

App::uses("StandardController", "Controller");

class SDWController extends StandardController {
  public $requires_co = true;
  
  // The requested CO Person ID to render the widget for
  protected $reqCoPersonId;
  
  /**
   * Callback before other controller methods are invoked or views are rendered.
   * - postcondition: $plugins set
   *
   * @since  COmanage Registry v3.2.0
   * @throws InvalidArgumentException
   */
  
  function beforeFilter() {
    // Force the model class to be that of our child model/controller.
    // (Otherwise StandardController will think we're Authenticator, since
    // that's first in $uses.)
    $this->modelClass = Inflector::singularize($this->name);

    if(!empty($this->request->params['pass'][0])) {
      // Pull the backend config for the plugin
      $args = array();
      $args['conditions'][$this->modelClass.".id"] = $this->request->params['pass'][0];
      $args['contain'] = false;
      
      $this->{$this->modelClass}->setConfig($this->{$this->modelClass}->find('first', $args));
    }

    parent::beforeFilter();

    // It's plausible at some point we might support rendering for users other
    // than the logged in user.
    $this->reqCoPersonId = $this->Session->read('Auth.User.co_person_id');
  }
  
  /**
   * Callback before views are rendered.
   *
   * @since  COmanage Registry v3.2.0
   */

  public function beforeRender() {
    parent::beforeRender();

    // Get a pointer to our model names
    $req = $this->modelClass;
    $modelpl = Inflector::tableize($req);

    // Find the ID of our parent
    $dwid = -1;
    
    if(!empty($this->data[$req]['co_dashboard_widget_id'])) {
      $this->set('vv_dwid', $this->data[$req]['co_dashboard_widget_id']);
    }
    
    if($this->action == 'display') {
      $this->layout = 'widget';
    }
  }

  /**
   * Determine the CO ID based on some attribute of the request.
   * This method is intended to be overridden by model-specific controllers.
   *
   * @since  COmanage Registry v3.2.0
   * @return Integer CO ID, or null if not implemented or not applicable.
   * @throws InvalidArgumentException
   */

  protected function calculateImpliedCoId($data = null) {
    if(!empty($this->request->params['named']['authenticatorid'])) {
      // Pull the CO from the Authenticator

      $coId = $this->Authenticator->field('co_id',
                                          array('id' => $this->request->params['named']['authenticatorid']));

      if($coId) {
        return $coId;
      } else {
        throw new InvalidArgumentException(_txt('er.notfound',
                                                array(_txt('ct.authenticators.1'),
                                                      filter_var($this->request->params['named']['authenticatorid'],FILTER_SANITIZE_SPECIAL_CHARS))));
      }
    }

    return parent::calculateImpliedCoId();
  }
  
  /**
   * Calculate Widget visibility.
   *
   * @since  COmanage Registry v3.2.0
   * @param  Array $roles Current roles, as returned by RoleComponent::calculateCMRoles()
   * @return Array Permissions array
   */
  
  protected function calculateParentPermissions(array $roles) {
    if($roles['cmadmin'] || $roles['coadmin']) {
      // For now, platform and CO admins (within the CO) can always view widgets
      
      return array('display' => true);
    }
    
    $model = Inflector::singularize($this->name);
    
    $p = array(
      'display' => false
    );
    
    if(!empty($this->request->params['pass'][0])) {
      // $id is the instantiated widget (eg: NotficationsWidget:id). We need to use
      // that to get the associated dashboard visibility configuration.
      
      $args = array();
      $args['conditions'][$model.'.id'] = $this->request->params['pass'][0];
      $args['contain'] = array('CoDashboardWidget' => 'CoDashboard');
      
      $db = $this->$model->find('first', $args);
      
      if(!$db) {
        return $p;
      }
      
      $p['display'] = $this->$model->CoDashboardWidget->CoDashboard->authorize($db['CoDashboardWidget'], 
                                                                               $this->Session->read('Auth.User.co_person_id'),
                                                                               $this->Role);
      
      if($this->action == 'display' && !$p['display']) {
        // Ordinarily this shouldn't happen, since the CoDashboard shouldn't render
        // if visibility is not permitted. However, there may be edge cases where
        // we could end up here (default dashboard is not visible to current user),
        // so this will be more graceful error than our usual redirect to /registry.
        
        throw new RuntimeException(_txt('er.permission'));
      }
    }
    
    return $p;
  }
  
  /**
   * Perform a redirect back to the controller's default view.
   * - postcondition: Redirect generated
   *
   * @since  COmanage Registry v3.2.0
   */
  
  public function performRedirect() {
    $req = $this->modelClass;
    $modelpl = Inflector::tableize($req);
    
    // We're assuming either default view_contains, or that view_contains
    // includes CoDashboardWidget.
    
    if(!empty($this->viewVars[$modelpl][0]['CoDashboardWidget']['co_dashboard_id'])) {
      $target = array();
      $target['plugin'] = null;
      $target['controller'] = "co_dashboard_widgets";
      $target['action'] = 'index';
      $target['codashboard'] = $this->viewVars[$modelpl][0]['CoDashboardWidget']['co_dashboard_id'];

      $this->redirect($target);
    }
    
    parent::performRedirect();
  }
}
