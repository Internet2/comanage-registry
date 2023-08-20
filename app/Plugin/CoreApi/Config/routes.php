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
    'controller' => 'CoreApiPeople',
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
    'controller' => 'CoreApiPeople',
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
    'controller' => 'CoreApiPeople',
    'action'     => 'update',
    '[method]'   => 'PUT',
  )
);

// COmanage CO Person Read API
Router::connect(
  '/api/co/:coid/core/v1/people/:identifier',
  array(
    'plugin'     => 'core_api',
    'controller' => 'CoreApiPeople',
    'action'     => 'read',
    '[method]'   => 'GET'
  )
);

Router::connect(
  '/api/co/:coid/core/v1/people/:identifier',
  array(
    'plugin'     => 'core_api',
    'controller' => 'CoreApiPeople',
    'action'     => 'update',
    '[method]'   => 'PUT'
  )
);

Router::connect(
  '/api/co/:coid/core/v1/people/:identifier',
  array(
    'plugin'     => 'core_api',
    'controller' => 'CoreApiPeople',
    'action'     => 'delete',
    '[method]'   => 'DELETE'
  )
);

Router::connect(
  '/api/co/:coid/core/v1/people',
  array(
    'plugin'     => 'core_api',
    'controller' => 'CoreApiPeople',
    'action'     => 'create',
    '[method]'   => 'POST'
  )
);

// COmanage Match Resolution Callback API
Router::connect(
  '/api/co/:coid/core/v1/resolution',
  array(
    'plugin'      => 'core_api',
    'controller'  => 'CoreApiPeople',
    'action'      => 'resolveMatch',
    '[method]'    => 'POST'
  )
);

// Organizations

Router::connect(
  '/api/co/:coid/core/v1/organizations',
  array(
    'plugin'     => 'core_api',
    'controller' => 'CoreApiOrganizations',
    'action'     => 'index',
    '[method]'   => 'GET',
  )
);

Router::connect(
  '/api/co/:coid/core/v1/organizations',
  array(
    'plugin'     => 'core_api',
    'controller' => 'CoreApiOrganizations',
    'action'     => 'delete',
    '[method]'   => 'DELETE',
  )
);

Router::connect(
  '/api/co/:coid/core/v1/organizations',
  array(
    'plugin'     => 'core_api',
    'controller' => 'CoreApiOrganizations',
    'action'     => 'update',
    '[method]'   => 'PUT',
  )
);

Router::connect(
  '/api/co/:coid/core/v1/organizations',
  array(
    'plugin'     => 'core_api',
    'controller' => 'CoreApiOrganizations',
    'action'     => 'create',
    '[method]'   => 'POST'
  )
);

Router::connect(
  '/api/co/:coid/core/v1/organizations/:identifier',
  array(
    'plugin'     => 'core_api',
    'controller' => 'CoreApiOrganizations',
    'action'     => 'read',
    '[method]'   => 'GET'
  )
);

Router::connect(
  '/api/co/:coid/core/v1/organizations/:identifier',
  array(
    'plugin'     => 'core_api',
    'controller' => 'CoreApiOrganizations',
    'action'     => 'update',
    '[method]'   => 'PUT'
  )
);

Router::connect(
  '/api/co/:coid/core/v1/organizations/:identifier',
  array(
    'plugin'     => 'core_api',
    'controller' => 'CoreApiOrganizations',
    'action'     => 'delete',
    '[method]'   => 'DELETE'
  )
);

// Departments

Router::connect(
  '/api/co/:coid/core/v1/departments',
  array(
    'plugin'     => 'core_api',
    'controller' => 'CoreApiDepartments',
    'action'     => 'index',
    '[method]'   => 'GET',
  )
);

Router::connect(
  '/api/co/:coid/core/v1/departments',
  array(
    'plugin'     => 'core_api',
    'controller' => 'CoreApiDepartments',
    'action'     => 'delete',
    '[method]'   => 'DELETE',
  )
);

Router::connect(
  '/api/co/:coid/core/v1/departments',
  array(
    'plugin'     => 'core_api',
    'controller' => 'CoreApiDepartments',
    'action'     => 'update',
    '[method]'   => 'PUT',
  )
);

Router::connect(
  '/api/co/:coid/core/v1/departments',
  array(
    'plugin'     => 'core_api',
    'controller' => 'CoreApiDepartments',
    'action'     => 'create',
    '[method]'   => 'POST'
  )
);

Router::connect(
  '/api/co/:coid/core/v1/departments/:identifier',
  array(
    'plugin'     => 'core_api',
    'controller' => 'CoreApiDepartments',
    'action'     => 'read',
    '[method]'   => 'GET'
  )
);

Router::connect(
  '/api/co/:coid/core/v1/departments/:identifier',
  array(
    'plugin'     => 'core_api',
    'controller' => 'CoreApiDepartments',
    'action'     => 'update',
    '[method]'   => 'PUT'
  )
);

Router::connect(
  '/api/co/:coid/core/v1/departments/:identifier',
  array(
    'plugin'     => 'core_api',
    'controller' => 'CoreApiDepartments',
    'action'     => 'delete',
    '[method]'   => 'DELETE'
  )
);

// Petitions
// GET https://{{hos}}/registry/api/co/2/core/v1/petitions?limit=20&page=2&direction=desc
// GET https://{{hos}}/registry/api/co/2/core/v1/petitions?status=PA
// GET https://{{hos}}/registry/api/co/2/core/v1/petitions?couid=4
Router::connect(
  '/api/co/:coid/core/v1/petitions',
  array(
    'plugin'     => 'core_api',
    'controller' => 'CoreApiPetitions',
    'action'     => 'index',
    '[method]'   => 'GET',
  )
);

Router::connect(
  '/api/co/:coid/core/v1/petitions/:id',
  array(
    'plugin'     => 'core_api',
    'controller' => 'CoreApiPetitions',
    'action'     => 'read',
    '[method]'   => 'GET'
  )
);

// Scoped identifiers are not parsed properly because they are perceived as file extensions
// Enable extensions parse to resolve this problem
Router::parseExtensions('json', 'xml');
