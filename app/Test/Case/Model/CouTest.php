<?php
/**
 * COmanage Registry Cou Model Test
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

App::uses('Cou', 'Model');

class CouTest extends CakeTestCase {
  public $fixtures = array('app.cou', 'app.co', 'app.cogroup');

  public function setUp() {
    parent::setUp();
    $this->Cou = ClassRegistry::init('Cou');
  }

  /**
   * Test allCous method of class Cou.
   *
   * @since  COmanage Registry v0.9.4
   */
  public function testAllCous() {
    // Test with default format = 'hash'. 
    $result = $this->Cou->allCous(2);

    $expected = array();
    $expected[1] = 'Test COU 1';
    $expected[2] = 'Test COU 2';
    $expected[3] = 'Test COU 3';
    $expected[4] = 'Test COU 4';
    $expected[5] = 'Test COU 5';
    $expected[6] = 'Test COU 6';

    $this->assertEquals($expected, $result);

    // Test with format = 'names'. 
    $result = $this->Cou->allCous(2, 'names');

    $expected = array();
    $expected[] = 'Test COU 1';
    $expected[] = 'Test COU 2';
    $expected[] = 'Test COU 3';
    $expected[] = 'Test COU 4';
    $expected[] = 'Test COU 5';
    $expected[] = 'Test COU 6';

    $this->assertEquals($expected, $result);

    // Test with format = 'names'. 
    $result = $this->Cou->allCous(2, 'ids');

    $expected = array();
    $expected[] = 1;
    $expected[] = 2;
    $expected[] = 3;
    $expected[] = 4;
    $expected[] = 5;
    $expected[] = 6;

    $this->assertEquals($expected, $result);
  }

  /**
  * Test beforeDelete method of class Cou.
  *
  * The primary function of the method at this time
  * is to delete the admin and member groups for a Cou
  * so we test that after the method is called we no
  * longer can search and find the admin group nor
  * the member group for the Cou.
  *
  * @since COmanage Registry v0.9.4
  */
  public function testBeforeDelete() {

    // Use the Cou from the fixture with id of 6 since that is
    // a child Cou and not a parent.
    $this->Cou->id = 6;

    // Call beforeDelete() and test it returns true.
    $ret = $this->Cou->beforeDelete(true);
    $this->assertEquals(true, $ret);

    // Verify that the admin and member group for Cou with
    // id = 6 are no longer available.
    $params = array();
    $params['conditions'] = array();
    $params['conditions']['CoGroup.name'] = 'members:Test COU 6';
    $params['contain'] = false;
    $result = $this->Cou->Co->CoGroup->find('first', $params);

    $expected = array();

    $this->assertEquals($expected, $result);

    $params = array();
    $params['conditions'] = array();
    $params['conditions']['CoGroup.name'] = 'admin:Test COU 6';
    $params['contain'] = false;
    $result = $this->Cou->Co->CoGroup->find('first', $params);

    $expected = array();

    $this->assertEquals($expected, $result);
  }

  /**
  * Test potentialParents method of class Cou.
  *
  * @since COmanage Registry v0.9.4
  */
  public function testPotentialParents() {
    
    $coId = 2;

    // Prepare the expected result by finding all COUs.
    $params = array();
    $params['conditions'] = array();
    $params['conditions']['Cou.co_id'] = $coId;
    $params['contain'] = false;
    $params['fields'] = array();
    $params['fields'][] = 'Cou.id';
    $params['fields'][] = 'Cou.name';
    $cous = $this->Cou->find('all', $params);
    
    $expected = array();
    foreach($cous as $i => $cou) {
      $expected[$cou['Cou']['id']] = $cou['Cou']['name'];
    }
    ksort($expected);

    // Now invoke the method to get the potential parents
    // and compare to expected.
    $result = $this->Cou->potentialParents(null, $coId);
    ksort($result);

    $this->assertEquals($expected, $result);

    // Repeat but now with COU with id = 1.
    $couId = 1;

    // Prepare the expected result by finding all COUs
    // except for the one with ID = 1 and creating array
    // keyed by index with name for value.
    $params = array();
    $params['conditions'] = array();
    $params['conditions']['Cou.id !='] = 1;
    $params['conditions']['Cou.co_id'] = $coId;
    $params['contain'] = false;
    $params['fields'] = array();
    $params['fields'][] = 'Cou.id';
    $params['fields'][] = 'Cou.name';
    $cous = $this->Cou->find('all', $params);
    
    $expected = array();
    foreach($cous as $i => $cou) {
      $expected[$cou['Cou']['id']] = $cou['Cou']['name'];
    }
    ksort($expected);

    // Now invoke the method to get the potential parents
    // and compare to expected.
    $result = $this->Cou->potentialParents($couId, $coId);
    ksort($result);

    $this->assertEquals($expected, $result);

  }

  /**
  * Test childCous method of class Cou.
  *
  * @since COmanage Registry v0.9.4
  */
  public function testChildCous() {

    $coId = 2;

    $couParentName = 'Test COU 3';

    // Prepare the expected result. Note that children here
    // means all descendents in the tree, not just direct
    // children.
    $expected = array();
    $expected['4'] = 'Test COU 4';
    $expected['5'] = 'Test COU 5';
    $expected['6'] = 'Test COU 6';

    // Now invoke the method to find descendents.
    $result = $this->Cou->childCous($couParentName, $coId);
    ksort($result);

    $this->assertEquals($expected, $result);

    // Again but include the parent in the returned result.
    $expected['3'] = 'Test COU 3';
    $result = $this->Cou->childCous($couParentName, $coId, true);
    ksort($result);

    $this->assertEquals($expected, $result);
  }

  /**
  * Test isInCo method of class Cou.
  *
  * @since COmanage Registry v0.9.4
  */
  public function testIsInCo() {

    // A positive test.
    $coId = 2;
    $couId = 1;

    $expected = true;
    $result = $this->Cou->isInCo($couId, $coId);

    $this->assertEquals($expected, $result);

    // A negative test where existing Cou is not in
    // an existing Co.
    $coId = 2;
    $couId = 7;

    $expected = false;
    $result = $this->Cou->isInCo($couId, $coId);

    $this->assertEquals($expected, $result);

    // A negative test where non-existing Cou is not in
    // an existing Co.
    $coId = 3;
    $couId = 999;

    $expected = false;
    $result = $this->Cou->isInCo($couId, $coId);

    $this->assertEquals($expected, $result);

    // A negative test where existing Cou is not in
    // a non-existing Co.
    $coId = 999;
    $couId = 7;

    $expected = false;
    $result = $this->Cou->isInCo($couId, $coId);

    $this->assertEquals($expected, $result);

    // A negative test where non-existing Cou is not in
    // a non-existing Co.
    $coId = 999;
    $couId = 999;

    $expected = false;
    $result = $this->Cou->isInCo($couId, $coId);

    $this->assertEquals($expected, $result);
  }

  /**
  * Test isChildCou method of class Cou.
  *
  * @since COmanage Registry v0.9.4
  */
  public function testIsChildCou() {
    $coId = 2;

    // A positive test of direct descendent.
    $parentCouId = 3;
    $candidateChildCouId = 4;

    $expected = true;
    $result = $this->Cou->isChildCou($parentCouId, $candidateChildCouId);

    $this->assertEquals($expected, $result);

    // A positive test of deeper descendent.
    $parentCouId = 3;
    $candidateChildCouId = 5;

    $expected = true;
    $result = $this->Cou->isChildCou($parentCouId, $candidateChildCouId);

    $this->assertEquals($expected, $result);

    // A negative test.
    $parentCouId = 3;
    $candidateChildCouId = 1;

    $expected = false;
    $result = $this->Cou->isChildCou($parentCouId, $candidateChildCouId);

    $this->assertEquals($expected, $result);
  }
}
