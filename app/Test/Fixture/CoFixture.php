<?php
/**
 * COmanage Registry CO Fixture
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

class CoFixture extends CakeTestFixture {
    // Import schema for the model from the default database.
    // The fixture data itself will be written to test and
    // not default.
    public $import = array('model' => 'Co', 'connection' => 'default');

    public function init() {

      $records = array();

      // Mimic the internal CO always created during deployment.
      $arecord = array();
      $arecord['id']          = 1;
      $arecord['name']        = 'COmanage';
      $arecord['description'] = 'COmanage Registry Internal CO';
      $arecord['status']      = SuspendableStatusEnum::Active;
      $arecord['created']     = date('Y-m-d H:i:s');
      $arecord['modified']    = date('Y-m-d H:i:s');
      $records[] = $arecord;

      // A second CO.
      $arecord = array();
      $arecord['id']          = 2;
      $arecord['name']        = 'Test CO 1';
      $arecord['description'] = 'Description for Test CO 1';
      $arecord['status']      = SuspendableStatusEnum::Active;
      $arecord['created']     = date('Y-m-d H:i:s');
      $arecord['modified']    = date('Y-m-d H:i:s');
      $records[] = $arecord;

      // A third CO.
      $arecord = array();
      $arecord['id']          = 3;
      $arecord['name']        = 'Test CO 2';
      $arecord['description'] = 'Description for Test CO 2';
      $arecord['status']      = SuspendableStatusEnum::Active;
      $arecord['created']     = date('Y-m-d H:i:s');
      $arecord['modified']    = date('Y-m-d H:i:s');
      $records[] = $arecord;

      $this->records = $records;

      parent::init();
    }
}
