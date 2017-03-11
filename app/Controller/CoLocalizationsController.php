<?php
/**
 * COmanage Registry CO Localizations Controller
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
 * @since         COmanage Registry v0.8.3
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::uses("StandardController", "Controller");

class CoLocalizationsController extends StandardController {
  // Class name, used by Cake
  public $name = "CoLocalizations";
  
  // Establish pagination parameters for HTML views
  public $paginate = array(
    'limit' => 25,
    'order' => array(
      'CoLocalizations.lkey' => 'asc'
    )
  );
  
  // This controller needs a CO to be set
  public $requires_co = true;

  /**
   * Callback after controller methods are invoked but before views are rendered.
   * - precondition: Request Handler component has set $this->request->params
   * - postcondition: $cous may be set.
   *
   * @since  COmanage Registry v0.8.3
   */
  
  function beforeRender() {
    // We use cm_texts_orig here because we want the original translations, not
    // the translations merged with the localized texts
    global $cm_texts_orig;
    global $cm_lang;
    
    if(!$this->request->is('restful')) {
      // Provide a list of the current keys and translations (in the current language).
      // At the moment, we don't support arrays -- only simple key/value pairs, but that
      // only eliminates enumerations.
      
      $texts = array();
      
      foreach(array_keys($cm_texts_orig[$cm_lang]) as $k) {
        if(!is_array($cm_texts_orig[$cm_lang][$k])
           // Also skip strings that can already be dynamically changed
           && !preg_match('/^em\./', $k)) {
          $texts[$k] = $cm_texts_orig[$cm_lang][$k];
        }
      }
      
      $this->set('vv_cm_texts', $texts);
    }
    
    parent::beforeRender();
  }
  
  /**
   * Perform any dependency checks required prior to a write (add/edit) operation.
   * - postcondition: Session flash message updated (HTML) or HTTP status returned (REST)
   *
   * @since  COmanage Registry v0.8.3
   * @param  Array Request data
   * @param  Array Current data
   * @return boolean true if dependency checks succeed, false otherwise.
   */
  
  function checkWriteDependencies($reqdata, $curdata = null) {
    // Make sure we don't already have an entry for this coid+key+language.
    
    $args = array();
    $args['conditions']['CoLocalization.co_id'] = $reqdata['CoLocalization']['co_id'];
    $args['conditions']['CoLocalization.lkey'] = $reqdata['CoLocalization']['lkey'];
    $args['conditions']['CoLocalization.language'] = $reqdata['CoLocalization']['language'];
    if(!empty($curdata['CoLocalization']['id'])
       && !empty($reqdata['CoLocalization']['id'])
       && ($curdata['CoLocalization']['id'] == $reqdata['CoLocalization']['id'])) {
      // We're editing the current record
      $args['conditions']['CoLocalization.id <>'] = $reqdata['CoLocalization']['id'];
    }
    $args['contain'] = false;
    
    if($this->CoLocalization->find('count', $args)) {
      if($this->request->is('restful')) {
        // XXX
        //$this->Api->restResultHeader(403, "Identifier In Use");
      } else {
        $this->Flash->set(_txt('er.loc.exists',
                               array(filter_var($reqdata['CoLocalization']['lkey'],FILTER_SANITIZE_SPECIAL_CHARS),
                                     filter_var($reqdata['CoLocalization']['language'],FILTER_SANITIZE_SPECIAL_CHARS))),
                          array('key' => 'error'));
      }
        
      return false;
    }
    
    return true;
  }
  
  /**
   * Authorization for this Controller, called by Auth component
   * - precondition: Session.Auth holds data used for authz decisions
   * - postcondition: $permissions set with calculated permissions
   *
   * @since  COmanage Registry v0.8.3
   * @return Array Permissions
   */
  
  function isAuthorized() {
    $roles = $this->Role->calculateCMRoles();
    
    // Construct the permission set for this user, which will also be passed to the view.
    $p = array();
    
    // Determine what operations this user can perform
    
    // Add a new CO Localization?
    $p['add'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // Delete an existing CO Localization?
    $p['delete'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // Edit an existing CO Localization?
    $p['edit'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // View all existing CO Localization?
    $p['index'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // View an existing CO Localization?
    $p['view'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    $this->set('permissions', $p);
    return $p[$this->action];
  }
}
