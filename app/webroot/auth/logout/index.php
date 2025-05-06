<?php
/**
 * COmanage Registry Placeholder External Auth Logout Handler
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
 * @since         COmanage Registry v0.1
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

// locate and load the Cake framework
define('DS', DIRECTORY_SEPARATOR);
define('ROOT', realpath('..' . DS . '..' . DS . '..' . DS . '..'));
define('APP_DIR', 'app');
define('WWW_ROOT', APP_DIR . DS . 'webroot' . DS);

require ROOT . DS . 'lib' . DS . 'Cake' . DS . 'bootstrap.php';

// load the app configuration
App::uses('PhpReader', 'Configure');
Configure::config('default', new PhpReader());
App::uses('CakeSession','Model/Datasource');

$re = '/(.*)\/auth\/logout(.*)/m';
$subst = '$1/users/logout$2';
$redirect_location = preg_replace($re, $subst, $_SERVER["REQUEST_URI"]);

CakeSession::delete('Auth');

header('Location: ' . $redirect_location);
