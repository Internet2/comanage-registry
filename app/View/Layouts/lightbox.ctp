<?php
/**
 * COmanage Registry Lightbox Layout
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
 * @since         COmanage Registry v1.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

  // As a general rule, all Registry pages are post-login and so shouldn't be cached
  header("Expires: Thursday, 10-Jan-69 00:00:00 GMT");
  header("Cache-Control: no-store, no-cache, max-age=0, must-revalidate");
  header("Pragma: no-cache");
?>
<!DOCTYPE html>
<html>
  <head>
    <title><?php print _txt('coordinate') . ': ' . filter_var($title_for_layout,FILTER_SANITIZE_STRING)?></title>
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

  <?php
    // cleanse the controller and action strings and insert them into the body classes
    $controller_stripped = preg_replace('/[^a-zA-Z0-9\-_]/', '', $this->params->controller);
    $action_stripped = preg_replace('/[^a-zA-Z0-9\-_]/', '', $this->params->action);
    $bodyClasses = $controller_stripped . ' ' .$action_stripped;

    $redirect_url = $_SERVER["REQUEST_SCHEME"] . '://' . $_SERVER["SERVER_NAME"] . $this->request->here . '/render:norm';
  ?>

  <!-- Body element will only be loaded if we load lightbox as a standalone layout.  -->
  <!-- Otherwise we will find ourselves using the existing body. So we choose to hide the body when not -->
  <!-- in the context of another layout -->
  <body class="<?php print $bodyClasses ?>" onload="whereami()">

    <div id="lightboxContent" class="light-box">
      <?php
        // insert the page internal content
        print $this->fetch('content');
      ?>
    </div>
    <script type="text/javascript">
      function whereami() {
        // Hide lightbox Content
        document.getElementById('lightboxContent').style.display = 'none';

        let is_lightbox = document.getElementsByClassName("light-box").length;
        let is_logged_in = document.getElementsByClassName("logged-in").length;

        if(is_lightbox > 0 && is_logged_in == 0) {
          // Add a spinner into the body
          document.body.innerHTML = '<div id="co-loading"><span></span><span></span><span></span></div>';
          // reload my parent
          window.location.assign('<?php print $redirect_url; ?>');
        } else {
          // Show the content
          document.getElementById('lightboxContent').style.display = 'block';
        }
      }
    </script>
  </body>
</html>
