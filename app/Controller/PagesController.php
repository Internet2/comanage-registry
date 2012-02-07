<?php
/**
 * Static content controller.
 *
 * This file will render views from views/pages/
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       registry
 * @since         COmanage Registry v0.1, CakePHP(tm) v 0.2.9
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('AppController', 'Controller');

/**
 * Static content controller
 *
 * Override this controller by placing a copy in controllers directory of an application
 *
 * @package       Cake.Controller
 * @link http://book.cakephp.org/2.0/en/controllers/pages-controller.html
 */
class PagesController extends AppController {

/**
 * Controller name
 *
 * @var string
 */
	public $name = 'Pages';

/**
 * Default helper
 *
 * @var array
 */
	public $helpers = array('Html', 'Session');

/**
 * This controller does not use a model
 *
 * @var array
 */
	public $uses = array();

/**
 * Displays a view
 *
 * @param mixed What page to display
 * @return void
 */
	public function display() {
		$path = func_get_args();

		$count = count($path);
		if (!$count) {
			$this->redirect('/');
		}
		$page = $subpage = $title_for_layout = null;

		if (!empty($path[0])) {
			$page = $path[0];
		}
		if (!empty($path[1])) {
			$subpage = $path[1];
		}
		if (!empty($path[$count - 1])) {
			$title_for_layout = Inflector::humanize($path[$count - 1]);
		}
		$this->set(compact('page', 'subpage', 'title_for_layout'));
		$this->render(implode('/', $path));
	}
	
	/**
	 * Authorization for this Controller, called by Auth component
	 * - precondition: Session.Auth holds data used for authz decisions
	 * - postcondition: $permissions set with calculated permissions
	 *
	 * @since  COmanage Registry v0.1
	 * @return boolean True if user is authorized for the current operation, false otherwise
	 */

	public function isAuthorized() {
		$cmr = $this->calculateCMRoles();
	  
		// Construct the permission set for this user, which will also be passed to the view.
		$p = array();
			    
		// Permission to render this page
		// We currently only route the main menu through here, so always allow display
		$p['display'] = true; 
		
		// Determine what menu options this user can see
		
		// View own (Org) profile?
		$p['menu']['orgprofile'] = $cmr['user'];
		
		// View/Edit own (CO) profile?
		$p['menu']['coprofile'] = $cmr['user'];
		
		// View/Edit CO groups?
		$p['menu']['cogroups'] = $cmr['user'];
		
		// Manage org identity data?
		$p['menu']['orgidentities'] = $cmr['admin'] || $cmr['subadmin'];
		
		// Manage any CO (or COU) population?
		$p['menu']['cos'] = $cmr['admin'] || $cmr['subadmin'];
		
		// Manage CO extended attributes?
		$p['menu']['extattrs'] = $cmr['admin'];
		
		// Manage COU definitions?
		$p['menu']['cous'] = $cmr['admin'];
	  
		// Manage CO enrollment flow definitions?
		$p['menu']['coef'] = $cmr['admin'];
		
		// Admin COmanage?
		$p['menu']['admin'] = $cmr['cmadmin'];
		
		// Manage NSF Demographics?
		$p['menu']['co_nsf_demographics'] = $cmr['cmadmin'];
		
		// View/Edit own Demographics profile?
		$p['menu']['nsfdemoprofile'] = $cmr['user'];
	  
		$this->set('permissions', $p);
		return($p[$this->action]);
	}
}
