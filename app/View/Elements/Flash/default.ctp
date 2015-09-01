<?php
/**
 * COmanage Registry Flash View Element
 *
 * Copyright (C) 2015 University Corporation for Advanced Internet Development, Inc.
 * 
 * Licensed under the Apache License, Version 2.0 (the "License"); you may not use this file except in compliance with
 * the License. You may obtain a copy of the License at
 * 
 * http://www.apache.org/licenses/LICENSE-2.0
 * 
 * Unless required by applicable law or agreed to in writing, software distributed under
 * the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY
 * KIND, either express or implied. See the License for the specific language governing
 * permissions and limitations under the License.
 *
 * @copyright     Copyright (C) 2015 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v1.0.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */

  if(!empty($message)) {
    // Strip tags then escape quotes before handing Flash message to noty.js
    $filteredMessage = filter_var(filter_var($message,FILTER_SANITIZE_STRING,FILTER_FLAG_NO_ENCODE_QUOTES),FILTER_SANITIZE_MAGIC_QUOTES);
    print "generateFlash('" . $filteredMessage . "', '" . h($key) . "');";
  }
