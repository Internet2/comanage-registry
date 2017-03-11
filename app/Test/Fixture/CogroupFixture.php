<?php
/**
 * COmanage Registry CoGroup Fixture
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
 * @since         COmanage Registry v0.9.4
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

class CoGroupFixture extends CakeTestFixture {
    // Import schema for the model from the default database.
    // The fixture data itself will be written to test and
    // not default.
    public $import = array('model' => 'CoGroup', 'connection' => 'default');

    public function init() {

      $records = array();

      // The admin group for the COmanage CO.
      $arecord = array();
      $arecord['id']          = 1;
      $arecord['co_id']       = 1;
      $arecord['name']        = 'admin';
      $arecord['description'] = 'COmanage Platform Administrators';
      $arecord['open']        = 0;
      $arecord['status']      = SuspendableStatusEnum::Active;
      $arecord['created']     = date('Y-m-d H:i:s');
      $arecord['modified']    = date('Y-m-d H:i:s');
      $records[] = $arecord;

      // The members group for the COmanage CO.
      $arecord = array();
      $arecord['id']          = 2;
      $arecord['co_id']       = 1;
      $arecord['name']        = 'members';
      $arecord['description'] = 'COmanage CO Members';
      $arecord['open']        = 0;
      $arecord['status']      = SuspendableStatusEnum::Active;
      $arecord['created']     = date('Y-m-d H:i:s');
      $arecord['modified']    = date('Y-m-d H:i:s');
      $records[] = $arecord;

      // The admin group for a CO.
      $arecord = array();
      $arecord['id']          = 3;
      $arecord['co_id']       = 2;
      $arecord['name']        = 'admin';
      $arecord['description'] = 'Test CO 1 Administrators';
      $arecord['open']        = 0;
      $arecord['status']      = SuspendableStatusEnum::Active;
      $arecord['created']     = date('Y-m-d H:i:s');
      $arecord['modified']    = date('Y-m-d H:i:s');
      $records[] = $arecord;

      // The member group for a CO.
      $arecord = array();
      $arecord['id']          = 4;
      $arecord['co_id']       = 2;
      $arecord['name']        = 'members';
      $arecord['description'] = 'Test CO 1 Members';
      $arecord['open']        = 0;
      $arecord['status']      = SuspendableStatusEnum::Active;
      $arecord['created']     = date('Y-m-d H:i:s');
      $arecord['modified']    = date('Y-m-d H:i:s');
      $records[] = $arecord;

      // The admin group for a COU.
      $arecord = array();
      $arecord['id']          = 5;
      $arecord['co_id']       = 2;
      $arecord['name']        = 'admin:Test COU 6';
      $arecord['description'] = 'Test COU 6 Administrators';
      $arecord['open']        = 0;
      $arecord['status']      = SuspendableStatusEnum::Active;
      $arecord['created']     = date('Y-m-d H:i:s');
      $arecord['modified']    = date('Y-m-d H:i:s');
      $records[] = $arecord;

      // The members group for a COU.
      $arecord = array();
      $arecord['id']          = 6;
      $arecord['co_id']       = 2;
      $arecord['name']        = 'members:Test COU 6';
      $arecord['description'] = 'Test COU 6 Members';
      $arecord['open']        = 0;
      $arecord['status']      = SuspendableStatusEnum::Active;
      $arecord['created']     = date('Y-m-d H:i:s');
      $arecord['modified']    = date('Y-m-d H:i:s');
      $records[] = $arecord;

      $this->records = $records;

      parent::init();
    }
}
