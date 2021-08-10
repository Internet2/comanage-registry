<?php
/**
 * Routes configuration
 *
 * In this file, you set up routes to your controllers and their actions.
 * Routes are very important mechanism that allows you to freely connect
 * different urls to chosen controllers and their actions (functions).
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
 * @package       app.Config
 * @since         CakePHP(tm) v 0.2.9
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
/**
 * Here, we are connecting '/' (base path) to controller called 'Pages',
 * its action called 'display', and we pass a param to select the view file
 * to use (in this case, /app/View/Pages/home.ctp)...
 */
	Router::connect('/', array('controller' => 'pages', 'action' => 'display', 'home'));
/**
 * ...and connect the rest of 'Pages' controller's urls.
 */
	Router::connect('/pages/*', array('controller' => 'pages', 'action' => 'display'));

/**
 * Load all plugin routes.  See the CakePlugin documentation on 
 * how to customize the loading of plugin routes.
 */
	CakePlugin::routes();

/**
 * Experimental VOOT routing
 */

Router::connect(
  '/voot/groups/:memberid/:groupid',
  array('controller' => 'voot', 'action' => 'groups')
);
 
Router::connect(
  '/voot/groups/:memberid',
  array('controller' => 'voot', 'action' => 'groups')
);

Router::connect(
  '/voot/people/:memberid/:groupid',
  array('controller' => 'voot', 'action' => 'people')
);
 
/**
 * Enable REST. These *MUST* come before the default CakePHP routes.
 */

Router::mapResources(array(
	'ad_hoc_attributes',
  'addresses',
	'clusters',
	'co_departments',
	'co_email_lists',
  'co_extended_attributes',
  'co_extended_types',
  'co_invites',
  'co_groups',
  'co_group_members',
  'co_navigation_links',
  'co_nsf_demographics',
  'co_org_identity_links',
  'co_people',
  'co_person_roles',
	'co_services',
	'co_t_and_c_agreements',
	'co_terms_and_conditions',
  'cos',
  'cous',
  'email_addresses',
  'history_records',
  'identifiers',
	'identity_documents',
  'names',
  'navigation_links',
  'org_identities',
  'organizations',
  'telephone_numbers',
	'urls'
));
Router::parseExtensions();

// ApplicationPreferences uses non-standard REST routes
Router::connect(
	'/application_preferences/:tag',
	array('controller' => 'application_preferences', 'action' => 'retrieve', '[method]' => 'GET')
);
Router::connect(
	'/application_preferences/:tag',
	array('controller' => 'application_preferences', 'action' => 'store', '[method]' => 'PUT')
);

/**
 * Load the CakePHP default routes. Remove this if you do not want to use
 * the built-in default routes.
 */
	require CAKE . 'Config' . DS . 'routes.php';
