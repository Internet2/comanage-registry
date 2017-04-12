<?php
/**
 * COmanage Registry CO Model Test
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

App::uses('Co', 'Model');

class CoTest extends CakeTestCase {
  public $fixtures = array('app.co');

  public function setUp() {
    parent::setUp();
    $this->Co = ClassRegistry::init('Co');
  }

  /**
   * Test afterSave method of class Co.
   *
   * We test the afterSave method by creating and saving a new CO.
   * To check that afterSave is correctly invoked we check that
   * for the new CO the associated model CoExtendedType is created. 
   * We do not check the actual contents of the CoExtendedType instance 
   * here and leave that to the unit test for the CoExtendedType model.
   *
   * @since  COmanage Registry v0.9.4
   */
  public function testAfterSave() {
    // Data for a new CO.
    $data = array();
    $data['Co']['name'] = 'A new CO';
    $data['Co']['description'] = 'Description for A new CO';
    $data['Co']['status'] = 'A';

    // Save the data. The method afterSave() should be automatically invoked.
    $this->Co->save($data);

    // Query to find the Co just created and use containable behavior
    // to include CoExtendedType if linked to the Co.
    $params = array();
    $params['conditions'] = array();
    $params['conditions']['Co.name'] = 'A new CO';
    $params['contain'] = array();
    $params['contain'][] = 'CoExtendedType';
    $co = $this->Co->find('first', $params);

    // We just test for presence of the Co and CoExtendedType keys.
    $keys = array_keys($co);
    ksort($keys);
    $result = $keys;

    $expected = array();
    $expected[] = 'Co';
    $expected[] = 'CoExtendedType';

    $this->assertEquals($expected, $result);
  }

  /**
   * Test setup method of class Co.
   *
   * We test the setup method by checking that the associated model
   * CoExtendedType is created. We do not check the actual contents
   * of the CoExtendedType instance here and leave that to the
   * unit test for the CoExtendedType model.
   *
   * @since  COmanage Registry v0.9.4
   */
  public function testSetup() {

    // Invoke the setup() method for Co with id 2 from the fixture.
    $this->Co->setup(2);

    // Query to find the Co with id 2 and use containable behavior
    // to include CoExtendedType if linked to the Co.
    $params = array();
    $params['conditions'] = array();
    $params['conditions']['Co.id'] = 2;
    $params['contain'] = array();
    $params['contain'][] = 'CoExtendedType';
    $co = $this->Co->find('first', $params);

    // We just test for presence of the Co and CoExtendedType keys.
    $keys = array_keys($co);
    ksort($keys);
    $result = $keys;

    $expected = array();
    $expected[] = 'Co';
    $expected[] = 'CoExtendedType';

    $this->assertEquals($expected, $result);
  }
}
