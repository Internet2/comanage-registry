<?php
/**
 * COmanage Registry QR Code Controller - Outputs a QR code as PNG file
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
 * @since         COmanage Registry v3.0.0
 */

App::uses("StandardController", "Controller");

class QrcodeController extends StandardController {
  // Class name, used by Cake
  public $name = "Qrcode";

  // No model is used
  public $uses = array();

  /**
   * Build a QR Code from a string
   * Example: /registry/qrcode?c=http://www.internet2.edu  - will produce a QR code for the supplied URL
   * Example: /registry/qrcode?c=COmanage&d - will produce a QR code of the string "COmanage" and force a PNG download
   *
   * @since  COmanage Registry v3.0.0
   * @param  String c from request query string: content to be encoded in QR code, typically a URL
   * @param  String download from request query string: force a download of the PNG file
   * @return response as a PNG file
   */
  public function index() {
    // Import the qrcode library
    App::import('Vendor', 'phpqrcode', array('file' => 'phpqrcode-1.1.4/phpqrcode/phpqrcode.php'));

    // QR defaults
    $type = 'image/png';
    $size = 7;
    $margin = 4;
    $content = 'http://www.internet2.edu/products-services/trust-identity/comanage/';
    $download = false;
    $downloadFileName = 'comanage-qr.png';

    if (isset($this->request->query['c'])) {
      $content = filter_var($this->request->query['c'],FILTER_SANITIZE_URL);
    }
    if (isset($this->request->query['download'])) {
      $download = true;
    }

    // Generate the qr code
    $qr = QRcode::png($content,false,1,$size,$margin,false);
    $this->response->body($qr);
    if ($download) {
      $this->response->download($downloadFileName);
    }

    // Set the content type
    $this->response->type($type);

    // Return response object - there is no view
    $this->autoRender = false;
    return $this->response;
  }

  /**
   * Authorization for this Controller, called by Auth component
   * - precondition: Session.Auth holds data used for authz decisions
   * - postcondition: $permissions set with calculated permissions
   *
   * @since  COmanage Registry v3.0.0
   * @return Array Permissions
   */

  function isAuthorized() {
    $roles = $this->Role->calculateCMRoles();

    // Construct the permission set for this user
    $p = array();

    // Every authenticated user can get at the QR code generator
    $p['index'] = $roles['user'];

    $this->set('permissions', $p);
    return $p[$this->action];
  }
}