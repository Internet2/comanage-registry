<?php
/**
 * COmanage Registry Remote User Authentication Login
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

// So we don't have to put the entire app under .htaccess auth, we grab REMOTE_USER
// and stuff it into the session so the auth component knows who we authenticated.

// Since this page isn't part of the framework, we need to reconfigure
// to access the Cake session.

session_name("CAKEPHP");
session_start();

// Set the user

if(empty($_SERVER['REMOTE_USER'])) {
  print	"ERROR: REMOTE_USER is empty. Please check your configuration.";
  exit;
}
// XXX if we define no redirect path, e.g. to an internal path: co_dashboards/configuration/co:2 then CAKEPHP thinks that should come back here
// XXX as a result adds to the SESSION auth/login as the redirect path after login. This causes the login to happen twice for the case we are
// XXX visiting the COmanage homepage
if(empty($_SESSION["Auth"]["redirect"])) {
  $_SESSION["Auth"]["redirect"] = '/';
}

$_SESSION['Auth']['external']['user'] = $_SERVER['REMOTE_USER'];
$re = '/(.*)\/auth\/login(.*)/m';
$subst = '$1/users/login$2';
$redirect_location = preg_replace($re, $subst, $_SERVER["REQUEST_URI"]);
$redirect_url = $_SERVER["REQUEST_SCHEME"] . '://' . $_SERVER["SERVER_NAME"] . ':' . $_SERVER["SERVER_PORT"] . $redirect_location;

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <style>
  @keyframes loading {
    0%   { opacity: 0.3; }
    30%  { opacity: 1.0; }
    100% { opacity: 0.3; }
  }
  #co-loading {
    position: fixed;
    top: 50%;
    left: 50%;
    width: 160px;
    height: 100px;
    margin: -56px 0 0 -80px;
    padding: 0;
    line-height: 0;
    color: #9FC6E2;
    text-align: center;
  }
  #co-loading span {
    animation: 1.2s linear infinite both loading;
    background-color: #9FC6E2;
    display: inline-block;
    height: 28px;
    width: 28px;
    border-radius: 20px;
    margin: 0 2.5px;
  }
  #co-loading span:nth-child(2) {
    animation-delay: 0.2s;
  }
  #co-loading span:nth-child(3) {
    animation-delay: 0.4s;
  }
  </style>
</head>
<body onload="window.location.assign('<?php print $redirect_url; ?>')">
  <div id="co-loading"><span></span><span></span><span></span></div>
</body>
</html>
