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
// /api/co/:coid/core/v1/people?limit=20&page=2&direction=desc
Router::connect(
  '/api/co/:coid/core/v1/people',
  array(
    'plugin'     => 'core_api',
    'controller' => 'Api',
    'action'     => 'index',
    '[method]'   => 'GET',
  )
);

// COmanage CO Person API DELETE
// /api/co/:coid/core/v1/people?identifier=1234567890@example.com
Router::connect(
  '/api/co/:coid/core/v1/people',
  array(
    'plugin'     => 'core_api',
    'controller' => 'Api',
    'action'     => 'delete',
    '[method]'   => 'DELETE',
  )
);

  // COmanage CO Person API PUT
  // /api/co/:coid/core/v1/people?identifier=1234567890@example.com
  Router::connect(
    '/api/co/:coid/core/v1/people',
    array(
      'plugin'     => 'core_api',
      'controller' => 'Api',
      'action'     => 'update',
      '[method]'   => 'PUT',
    )
  );

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

Router::connect(
  '/api/co/:coid/core/v1/people/:identifier',
  array(
    'plugin'     => 'core_api',
    'controller' => 'Api',
    'action'     => 'update',
    '[method]'   => 'PUT'
  )
);

Router::connect(
  '/api/co/:coid/core/v1/people/:identifier',
  array(
    'plugin'     => 'core_api',
    'controller' => 'Api',
    'action'     => 'delete',
    '[method]'   => 'DELETE'
  )
);

Router::connect(
  '/api/co/:coid/core/v1/people',
  array(
    'plugin'     => 'core_api',
    'controller' => 'Api',
    'action'     => 'create',
    '[method]'   => 'POST'
  )
);

// COmanage Match Resolution Callback API
Router::connect(
  '/api/co/:coid/core/v1/resolution',
  array(
    'plugin'      => 'core_api',
    'controller'  => 'Api',
    'action'      => 'resolveMatch',
    '[method]'    => 'POST'
  )
);

// Scoped identifiers are not parsed properly because they are perceived as file extensions
// Enable extensions parse to resolve this problem
Router::parseExtensions('json', 'xml');
