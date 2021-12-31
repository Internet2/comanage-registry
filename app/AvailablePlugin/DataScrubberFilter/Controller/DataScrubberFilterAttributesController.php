<?php
/**
 * COmanage Registry Data Scrubber Filter Rules Controller
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
 * @package       registry-plugin
 * @since         COmanage Registry v4.1.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::uses("StandardController", "Controller");

class DataScrubberFilterAttributesController extends StandardController {
  // Class name, used by Cake
  public $name = "DataScrubberFilterAttributes";
  
  public $requires_co = true;
  
  // Establish pagination parameters for HTML views
  public $paginate = array(
    'limit' => 25,
    'order' => array(
      'attribute' => 'asc'
    )
  );
  
  /**
   * Callback before views are rendered.
   *
   * @since  COmanage Registry v4.1.0
   */

  function beforeRender() {
    parent::beforeRender();
    
    $dsfid = null;
    
    if($this->action == 'add' || $this->action == 'index') {
      // Accept DataScrubberFilter ID from the url
      
      if(!empty($this->request->params['named']['datascrubberfilter'])) {
        $dsfid = filter_var($this->request->params['named']['datascrubberfilter'],FILTER_SANITIZE_SPECIAL_CHARS);
      }
    } elseif($this->action == 'edit' || $this->action == 'view') {
      if(!empty($this->request->params['pass'][0])) {
        $dsfid = $this->DataScrubberFilterAttribute->field('data_scrubber_filter_id', array('DataScrubberFilterAttribute.id' => $this->request->params['pass'][0]));
      }
    }
    
    if($dsfid) {
      // Look up the record, including the parent Data Filter to get the description
      
      $args = array();
      $args['conditions']['DataScrubberFilter.id'] = $dsfid;
      $args['contain'] = false;
      // We can't use contain(DataFilter) probably because we're not setting up the
      // dynamic relations correctly
//      $args['contain'] = array('DataFilter');
      
      $dsfilter = $this->DataScrubberFilterAttribute->DataScrubberFilter->find('first', $args);
      
      if(!empty($dsfilter)) {
        $this->set('vv_dsfilter', $dsfilter['DataScrubberFilter']);

        // Relations aren't autobinding DataFilter...
        $DataFilter = ClassRegistry::init('DataFilter');
        
        $args = array();
        $args['conditions']['DataFilter.id'] = $dsfilter['DataScrubberFilter']['data_filter_id'];
        $args['contain'] = false;
        
        $datafilter = $DataFilter->find('first', $args);

        if(!empty($datafilter)) {
          $this->set('vv_datafilter', $datafilter['DataFilter']);
        }
      }
    }
    
    // Pull the set of attributes available for scrubbing
    
    $supportedAttrs = $this->DataScrubberFilterAttribute->supportedAttributes($this->cur_co['Co']['id']);
    
    $this->set('vv_available_attributes', $supportedAttrs);
  }
  
  /**
   * Determine the CO ID based on some attribute of the request.
   * This method is intended to be overridden by model-specific controllers.
   *
   * @since  COmanage Registry v4.1.0
   * @return Integer CO ID, or null if not implemented or not applicable.
   * @throws InvalidArgumentException
   */

  protected function calculateImpliedCoId($data = null) {
    // If a data scrubber filter is specified, use it to get to the CO ID
    
    $dsf = null;

    if(in_array($this->action, array('add', 'index'))
       && !empty($this->params->named['datascrubberfilter'])) {
      $dsf = $this->params->named['datascrubberfilter'];
    } elseif(!empty($this->request->data['DataScrubberFilter']['data_scrubber_filter_id'])) {
      $dsf = $this->request->data['DataScrubberFilter']['data_scrubber_filter_id'];
    }

    if($dsf) {
      // Map Data Scrubber Filter to Data Filter to CO... we could also do this as a join
      
      $dataFilterId = $this->DataScrubberFilterAttribute
                           ->DataScrubberFilter->field('data_filter_id', 
                                                       array('DataScrubberFilter.id' => $dsf));
      
      // Relations aren't autobinding DataFilter...
      $DataFilter = ClassRegistry::init('DataFilter');
      
      $coId = $DataFilter->field('co_id', array('DataFilter.id' => $dataFilterId));
      
      if($coId) {
        return $coId;
      } else {
        throw new InvalidArgumentException(_txt('er.notfound', array(_txt('ct.data_scrubber_filters.1'), $dsf)));
      }
    }

    return parent::calculateImpliedCoId();
  }
  
  /**
   * Determine the conditions for pagination of the index view, when rendered via the UI.
   *
   * @since  COmanage Registry v4.1.0
   * @return Array An array suitable for use in $this->paginate
   */

  function paginationConditions() {
    // Only retrieve attributes in the current data filter scrubber

    $ret = array();

    $ret['conditions']['DataScrubberFilterAttribute.data_scrubber_filter_id'] = $this->request->params['named']['datascrubberfilter'];

    return $ret;
  }
  
  /**
   * Perform a redirect back to the controller's default view.
   * - postcondition: Redirect generated
   *
   * @since  COmanage Registry v4.1.0
   */

  function performRedirect() {
    // Append the Data Scrubber Filter ID to the redirect

    if(isset($this->request->data['DataScrubberFilterAttribute']['data_scrubber_filter_id']))
      $dsfid = $this->request->data['DataScrubberFilterAttribute']['data_scrubber_filter_id'];
    elseif(isset($this->request->params['named']['datascrubberfilter']))
      $dsfid = filter_var($this->request->params['named']['datascrubberfilter'],FILTER_SANITIZE_SPECIAL_CHARS);

    $this->redirect(array(
      'plugin' => 'data_scrubber_filter',
      'controller' => 'data_scrubber_filter_attributes',
      'action' => 'index',
      'datascrubberfilter' => $dsfid)
    );
  }
  
  /**
   * Authorization for this Controller, called by Auth component
   * - precondition: Session.Auth holds data used for authz decisions
   * - postcondition: $permissions set with calculated permissions
   *
   * @since  COmanage Registry v4.1.0
   * @return Array Permissions
   */
  
  function isAuthorized() {
    $roles = $this->Role->calculateCMRoles();
    
    // Construct the permission set for this user, which will also be passed to the view.
    $p = array();
    
    // Add a new Data Scrubber Filter Attribute?
    $p['add'] = $roles['cmadmin'] || $roles['coadmin'];
    
    // Delete an existing Data Scrubber Filter Attribute?
    $p['delete'] = $roles['cmadmin'] || $roles['coadmin'];
    
    // Edit an existing Data Scrubber Filter Attribute?
    $p['edit'] = $roles['cmadmin'] || $roles['coadmin'];
    
    // View all existing Data Scrubber Filter Attribute?
    $p['index'] = $roles['cmadmin'] || $roles['coadmin'];
    
    // Edit an existing Data Scrubber Filter Attribute?
    $p['order'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // Modify ordering for display via AJAX 
    $p['reorder'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // View an existing Data Scrubber Filter Attribute?
    $p['view'] = $roles['cmadmin'] || $roles['coadmin'];
    
    $this->set('permissions', $p);
    return($p[$this->action]);
  }
}
