<?php
/**
 * COmanage Registry MediaWiki API Client Exception
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
 * @since         COmanage Registry v3.1.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */

class MediaWikiApiClientException extends Exception {

  /**
   * Constructor for MediaWikiApiClientException
   * - precondition: 
   * - postcondition: 
   *
   * @since  COmanage Directory 3.1.0
   * @return instance
   */
   public function __construct($message, $code = 0, Exception $previous = null){
    parent::__construct($message, $code, $previous);
   }

  /**
   * Custom string representation of object
   * - precondition: 
   * - postcondition: 
   *
   * @since  COmanage Directory 3.1.0
   * @return string
   */
   public function __toString(){
    return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
   }
}
