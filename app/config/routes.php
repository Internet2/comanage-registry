<?php
/**
 * Routes configuration
 *
 * In this file, you set up routes to your controllers and their actions.
 * Routes are very important mechanism that allows you to freely connect
 * different urls to chosen controllers and their actions (functions).
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
 * @subpackage    cake.app.config
 * @since         CakePHP(tm) v 0.2.9
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
/**
 * Here, we are connecting '/' (base path) to controller called 'Pages',
 * its action called 'display', and we pass a param to select the view file
 * to use (in this case, /app/views/pages/home.ctp)...
 */
	Router::connect('/', array('controller' => 'pages', 'action' => 'display', 'home'));
/**
 * ...and connect the rest of 'Pages' controller's urls.
 */
	Router::connect('/pages/*', array('controller' => 'pages', 'action' => 'display'));
/**
 *
 * Map resources for REST calls
 */

Router::mapResources('addresses');
Router::mapResources('co_extended_attributes');
Router::mapResources('co_invites');
Router::mapResources('co_groups');
Router::mapResources('co_group_members');
Router::mapResources('co_org_identity_links');
Router::mapResources('co_people');
Router::mapResources('co_person_roles');
Router::mapResources('cos');
Router::mapResources('cous');
Router::mapResources('email_addresses');
Router::mapResources('identifiers');
Router::mapResources('org_identities');
Router::mapResources('organizations');
Router::mapResources('telephone_numbers');
Router::parseExtensions();