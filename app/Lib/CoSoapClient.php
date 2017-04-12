<?php
/**
 * COmanage Registry SoapClient Extension
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
 * @package       registry-plugin
 * @since         COmanage Registry v2.0.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

class CoSoapClient extends SoapClient {
  public function __construct($wsdl, $options = array()) {
    // Under a variety of circumstances, SoapClient causes a fatal error rather
    // than throw a SoapFault. See, eg, https://bugs.php.net/bug.php?id=47584
    
    // So we first try to open the WSDL manually. If that fails, we'll throw
    // an error rather than continue the initialization.
    
    // Note this should throw an error on failure, so probably don't need the extra check
    $w = fopen($wsdl, "r");

    if(!$w) {
      throw new RuntimeException(_txt('er.soap.wsdl', array($wsdl)));
    }
    
    fclose($w);
    
    parent::__construct($wsdl, $options);
  }
}
