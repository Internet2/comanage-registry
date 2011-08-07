<?php
/**
 * Static content controller.
 *
 * This file will render views from views/pages/
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       cake
 * @subpackage    cake.cake.libs.controller
 * @since         CakePHP(tm) v 0.2.9
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

/**
 * Static content controller
 *
 * Override this controller by placing a copy in controllers directory of an application
 *
 * @package       cake
 * @subpackage    cake.cake.libs.controller
 * @link http://book.cakephp.org/view/958/The-Pages-Controller
 */
class PagesController extends AppController {

/**
 * Controller name
 *
 * @var string
 * @access public
 */
	var $name = 'Pages';

/**
 * Default helper
 *
 * @var array
 * @access public
 */
	var $helpers = array('Html', 'Session');
	var $components = array('Session');

/**
 * This controller does not use a model
 *
 * @var array
 * @access public
 */
	var $uses = array();

/**
 * Displays a view
 *
 * @param mixed What page to display
 * @access public
 */
	function display() {
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
	
    function beforeFilter()
    {
      // Callback before other controller methods are invoked or views are rendered.
      //
      // Parameters:
      //   None
      //
      // Preconditions:
      //     None
      //
      // Postconditions:
      // (1) Parent called
      // (2) Auth component reconfigured to allow invite handling without login
      //
      // Returns:
      //   Nothing
      
      // The initial index page, which might allow for self sign-up,
      // should not require authentication
      if($this->params['url']['url'] == "/")
        $this->Auth->allow('display');

      // Since we're overriding, we need to call the parent to run the authz check
      parent::beforeFilter();
    }

    function isAuthorized()
    {
      // Authorization for this Controller, called by Auth component
      //
      // Parameters:
      //   None
      //
      // Preconditions:
      // (1) Session.Auth holds data used for authz decisions
      //
      // Postconditions:
      // (1) $permissions set with calculated permissions
      //
      // Returns:
      // - Array of permissions
      
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

      // Admin COmanage?
      $p['menu']['admin'] = $cmr['cmadmin'];
      
      $this->set('permissions', $p);
      return($p[$this->action]);
    }
}