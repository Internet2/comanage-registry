<?php
/**
 * COmanage Registry Shibboleth Embedded Discovery Service EDS Controller
 *
 * Copyright (C) 2015 University Corporation for Advanced Internet Development, Inc.
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
 * @copyright     Copyright (C) 2015 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v1.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */

App::uses('AppController', 'Controller');

class EdsController extends AppController {
  //Class name, used by Cake
	public $name = "Eds";    

	// This controller does not use a model. The CakePHP documentation says to set $uses
	// to false but that does not work at this time.
	public $uses = array();
    
  /**
   * Callback after controller methods are invoked but before views are rendered.
   *
   * @since  COmanage Registry v1.0
   */
    
	function beforeFilter() {
		if($this->name == 'Eds') {
			if ($this->request->params['action'] == 'view' && (!$this->Session->check('Auth.User'))) {
				// Allow the EDS page to render without authentication. If there is an authenticated
				// user we want Auth to run to set up authorizations.
				$this->Auth->allow();
			}            
		}        
		
		parent::beforeFilter();
	}
	
  /**
   * Prepare to display the EDS.
   * - precondition: none
   * - postcondition: noLoginLogout and title_for_layout set for view
   * 
   * @since COmanage Registry v1.0
   */
  
	function view(){
    $this->set('noLoginLogout', true);
    $this->set('title_for_layout', _txt('pl.shibboletheds.layout.title.view'));
        
		return;	
	} 
}