<?php
/**
 * COmanage Registry Cou Fixture for CouTest
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

class CouTestCouFixture extends CakeTestFixture {
    // Import schema for the model from the default database.
    // The fixture data itself will be written to test and
    // not default.
    public $import = array('model' => 'Cou', 'connection' => 'default');

    public function init() {

      $records = array();

      // A test COU.
      $arecord = array();
      $arecord['id']          = 1;
      $arecord['co_id']       = 2;
      $arecord['name']        = 'Test COU 1';
      $arecord['description'] = 'Description for Test COU 1';
      $arecord['parent_id']   = null;
      $arecord['lft']         = 15;
      $arecord['rght']        = 16;
      $arecord['created']     = date('Y-m-d H:i:s');
      $arecord['modified']    = date('Y-m-d H:i:s');
      $records[] = $arecord;

      // A second test COU.
      $arecord = array();
      $arecord['id']          = 2;
      $arecord['co_id']       = 2;
      $arecord['name']        = 'Test COU 2';
      $arecord['description'] = 'Description for Test COU 2';
      $arecord['parent_id']   = null;
      $arecord['lft']         = 17;
      $arecord['rght']        = 18;
      $arecord['created']     = date('Y-m-d H:i:s');
      $arecord['modified']    = date('Y-m-d H:i:s');
      $records[] = $arecord;

      // A third COU.
      $arecord = array();
      $arecord['id']          = 3;
      $arecord['co_id']       = 2;
      $arecord['name']        = 'Test COU 3';
      $arecord['description'] = 'Description for Test COU 3';
      $arecord['parent_id']   = null;
      $arecord['lft']         = 19;
      $arecord['rght']        = 26;
      $arecord['created']     = date('Y-m-d H:i:s');
      $arecord['modified']    = date('Y-m-d H:i:s');
      $records[] = $arecord;

      // A fourth COU, child to Test COU 3.
      $arecord = array();
      $arecord['id']          = 4;
      $arecord['co_id']       = 2;
      $arecord['name']        = 'Test COU 4';
      $arecord['description'] = 'Description for Test COU 4';
      $arecord['parent_id']   = 3;
      $arecord['lft']         = 20;
      $arecord['rght']        = 23;
      $arecord['created']     = date('Y-m-d H:i:s');
      $arecord['modified']    = date('Y-m-d H:i:s');
      $records[] = $arecord;

      // A fifth COU, child to Test COU 4.
      $arecord = array();
      $arecord['id']          = 5;
      $arecord['co_id']       = 2;
      $arecord['name']        = 'Test COU 5';
      $arecord['description'] = 'Description for Test COU 5';
      $arecord['parent_id']   = 4;
      $arecord['lft']         = 21;
      $arecord['rght']        = 22;
      $arecord['created']     = date('Y-m-d H:i:s');
      $arecord['modified']    = date('Y-m-d H:i:s');
      $records[] = $arecord;

      // A sixth COU, child to Test COU 3.
      $arecord = array();
      $arecord['id']          = 6;
      $arecord['co_id']       = 2;
      $arecord['name']        = 'Test COU 6';
      $arecord['description'] = 'Description for Test COU 6';
      $arecord['parent_id']   = 3;
      $arecord['lft']         = 24;
      $arecord['rght']        = 25;
      $arecord['created']     = date('Y-m-d H:i:s');
      $arecord['modified']    = date('Y-m-d H:i:s');
      $records[] = $arecord;

      // A seventh COU in a different CO.
      $arecord = array();
      $arecord['id']          = 7;
      $arecord['co_id']       = 3;
      $arecord['name']        = 'Test COU 1';
      $arecord['description'] = 'Description for Test COU 1';
      $arecord['parent_id']   = null;
      $arecord['lft']         = 26;
      $arecord['rght']        = 27;
      $arecord['created']     = date('Y-m-d H:i:s');
      $arecord['modified']    = date('Y-m-d H:i:s');
      $records[] = $arecord;
      
      $this->records = $records;

      parent::init();
    }
}
