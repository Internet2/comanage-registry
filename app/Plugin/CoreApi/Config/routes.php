<?php
/**
 * COmanage Registry Core API Routes
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
 * @since         COmanage Registry v4.0.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

// The general format for Core API URLs should be /api/co/:coid/core/v1/namespace/...
// Note REST API v2 has taken the form /api/v2/objects

// COmanage CO Person Read API
Router::connect(
  '/api/co/:coid/core/v1/people/:identifier',
  array(
    'plugin'     => 'core_api',
    'controller' => 'Api',
    'action'     => 'read',
    '[method]'   => 'GET'
  )
);

// COmanage CO Person Write Update API
Router::connect(
  '/api/co/:coid/core/v1/people/:identifier',
  array(
    'plugin'     => 'core_api',
    'controller' => 'Api',
    'action'     => 'update',
    '[method]'   => 'PUT'
  )
);

/*
Router::connect(
// XXX implement this as a proxy for expunge?
  '/api/co/:coid/core/v1/people/:identifier',
  array(
    'plugin'     => 'core_api',
    'controller' => 'Api',
    'action'     => 'delete',
    '[method]'   => 'DELETE'
  )
);

Router::connect(
// XXX This needs to trigger identifier assignment and maybe some other stuff
//     provisioning should only fire after all models are saved
  '/api/co/:coid/core/v1/people',
  array(
    'plugin'     => 'core_api',
    'controller' => 'Api',
    'action'     => 'create',
    '[method]'   => 'POST'
  )
);
*/