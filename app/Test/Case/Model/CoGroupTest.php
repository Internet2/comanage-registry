<?php
/**
 * COmanage Registry CoGroup Model Test
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
 * @since         COmanage Registry v3.2.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::uses('CoGroup', 'Model');

class CoGroupTest extends CakeTestCase {

  public $fixtures = array(
    'app.Co',
    'app.Cou',
    'app.CoDepartment',
    'app.CoEmailList',
    'app.CoEnrollmentFlow',
    'app.CoExpirationPolicy',
    'app.CoExtendedType',
    'app.CoGroup',
    'app.CoGroupMember',
    'app.CoNotification',
    'app.CoOrgIdentityLink',
    'app.CoPerson',
    'app.CoPersonRole',
    'app.CoProvisioningExport',
    'app.CoProvisioningTarget',
    'app.CoService',
    'app.CoSetting',
    'app.EmailAddress',
    'app.HistoryRecord',
    'app.Identifier',
    'app.Name',
    'app.SshKey',
  );

  /**
   * Set up the test case.
   */
  public function setUp() {
    parent::setUp();
    $this->CoGroup = ClassRegistry::init('CoGroup');
  }

  /**
   * Tear down the test case.
   */
  public function tearDown() {
    unset($this->CoGroup);
    parent::tearDown();
  }

  /**
   * Create a Cou with id 1 in Co with id 1.
   */
  public function createTestCou() {

    // Get a Cou object.
    $Cou = ClassRegistry::init('Cou');

    // Find Cou with id 1, should not exist.
    $args = array();
    $args['conditions']['Cou.id'] = '1';
    $args['contain'] = false;
    $result = $Cou->find('first', $args);
    $this->assertEmpty($result, "Cou with id 1 should not exist");

    // Create Cou with id 1.
    $cou = array(
      'Cou' => array(
        'co_id'       => '1',
        'name'        => 'Test COU',
        'description' => 'Test COU Description',
        'status'      => StatusEnum::Active
      )
    );
    $Cou->save($cou);
    $this->assertEquals('1', $Cou->id, "Cou should have id 1");
  }

  /**
   * Test adding default groups to a Co that does not exist.
   */
  public function testAddDefaultsNoCoId() {
    $this->setExpectedException(InvalidArgumentException::class, 'CO "CO_ID_THAT_DOES_NOT_EXIST" Not Found');
    $this->CoGroup->addDefaults('CO_ID_THAT_DOES_NOT_EXIST');
  }

  /**
   * Test adding default groups to a Cou that does not exist.
   */
  public function testAddDefaultsNoCouId() {
    $this->setExpectedException(InvalidArgumentException::class, 'COU "COU_ID_THAT_DOES_NOT_EXIST" Not Found');
    $this->CoGroup->addDefaults(1, 'COU_ID_THAT_DOES_NOT_EXIST');
  }

  /**
   * Test adding default groups to a Co.
   */
  public function testAddDefaultsToCo() {

    // Get a Co object.
    $Co = ClassRegistry::init('Co');

    // Find Co with id 2, should not exist.
    $args = array();
    $args['conditions']['Co.id'] = '2';
    $args['contain'] = false;
    $result = $Co->find('first', $args);
    $this->assertEmpty($result, "Co with id 2 should not exist");

    // Create Co with id 2.
    $co = array(
      'Co' => array(
        'name'        => 'Test CO',
        'description' => 'Test CO for CoGroupTest',
        'status'      => StatusEnum::Active
      )
    );
    $Co->save($co, array('callbacks' => false)); // do not call callbacks
    $this->assertEquals(2, $Co->id, "Newly created Co should have id 2");

    // Find all groups with Co id 2, should not find any.
    $args = array();
    $args['conditions']['CoGroup.co_id'] = '2';
    $args['conditions']['CoGroup.deleted'] = false;
    $args['contain'] = false;
    $result = $this->CoGroup->find('all', $args);
    $expected = array();
    $this->assertEquals($expected, $result, "Should not find any groups in the Co");

    // Add default groups to Co with id 2.
    $return = $this->CoGroup->addDefaults(2);
    $this->assertTrue($return);

    // Find all groups with Cou id 2, should find the default groups.
    $actual = $this->CoGroup->find('all', $args);
    $this->assertNotNull($actual);
    $this->assertNotEmpty($actual);

    // Ignore 'created' and 'modified' timestamps.
    $actual = Hash::remove($actual, '{n}.CoGroup.created');
    $actual = Hash::remove($actual, '{n}.CoGroup.modified');

    $expected = array(
      array(
        'CoGroup' => array(
          'id'               => '4',
          'co_id'            => '2',
          'cou_id'           => NULL,
          'name'             => 'CO:admins',
          'description'      => 'Test CO Administrators',
          'open'             => false,
          'status'           => 'A',
          'group_type'       => 'A',
          'auto'             => false,
          'co_group_id'      => NULL,
          'revision'         => '0',
          'deleted'          => false,
          'actor_identifier' => NULL,
        )),
      array(
        'CoGroup' => array(
          'id'               => '5',
          'co_id'            => '2',
          'cou_id'           => NULL,
          'name'             => 'CO:members:active',
          'description'      => 'Test CO Active Members',
          'open'             => false,
          'status'           => 'A',
          'group_type'       => 'MA',
          'auto'             => true,
          'co_group_id'      => NULL,
          'revision'         => '0',
          'deleted'          => false,
          'actor_identifier' => NULL,
        )),
      array(
        'CoGroup' => array(
          'id'               => '6',
          'co_id'            => '2',
          'cou_id'           => NULL,
          'name'             => 'CO:members:all',
          'description'      => 'Test CO Members',
          'open'             => false,
          'status'           => 'A',
          'group_type'       => 'M',
          'auto'             => true,
          'co_group_id'      => NULL,
          'revision'         => '0',
          'deleted'          => false,
          'actor_identifier' => NULL,
        )),
    );

    $this->assertEquals($expected, $actual);
  }

  /**
   * Test adding default groups to a Cou.
   */
  public function testAddDefaultsToCou() {

    // Get a Co object.
    $Co = ClassRegistry::init('Co');

    // Get a Cou object.
    $Cou = ClassRegistry::init('Cou');

    // Find Co with id 1 from the fixture, should exist.
    $args = array();
    $args['conditions']['Co.id'] = '1';
    $args['contain'] = false;
    $result = $Co->find('first', $args);
    $this->assertNotNull($result, "Co with id 1 should exist");
    $this->assertNotEmpty($result, "Co with id 1 should exist");

    // Find Co with id 2, should not exist.
    $args = array();
    $args['conditions']['Co.id'] = '2';
    $args['contain'] = false;
    $result = $Co->find('first', $args);
    $this->assertEmpty($result, "Co with id 2 should not exist");

    // Create Co with id 2.
    $co = array(
      'Co' => array(
        'name'        => 'Test CO',
        'description' => 'Test CO for CoGroupTest',
        'status'      => StatusEnum::Active
      )
    );
    $Co->save($co, array('callbacks' => false)); // do not call callbacks
    $this->assertEquals(2, $Co->id, "Newly created Co should have id 2");

    // Find Cou with id 1, should not exist.
    $args = array();
    $args['conditions']['Cou.id'] = '1';
    $args['contain'] = false;
    $result = $Cou->find('first', $args);
    $this->assertEmpty($result, "Cou with id 1 should not exist");

    // Create Cou with id 1.
    $cou = array(
      'Cou' => array(
        'co_id'       => 2,
        'name'        => 'Test COU',
        'description' => 'Test COU for CoGroupTest',
        'status'      => StatusEnum::Active
      )
    );
    $Cou->save($cou, array('callbacks' => false));
    $cou_id = $Cou->id;
    $this->assertEquals(1, $cou_id, "Newly created Cou should have id 1");

    // Find all groups with Cou id 1, should not find any.
    $args = array();
    $args['conditions']['CoGroup.cou_id'] = '1';
    $args['conditions']['CoGroup.deleted'] = false;
    $args['contain'] = false;
    $result = $this->CoGroup->find('all', $args);
    $expected = array();
    $this->assertEquals($expected, $result, "Should not find any groups with Cou id 1");

    // Add default groups to Cou with id 1 and Co with id 2.
    $return = $this->CoGroup->addDefaults(2, 1);
    $this->assertTrue($return, "CoGroup->AddDefaults() should return true");

    // Find all groups with Cou id 1, should find the default groups.
    $actual = $this->CoGroup->find('all', $args);
    $this->assertNotNull($actual, "Should find groups with Cou id 1");
    $this->assertNotEmpty($actual, "Should find groups with Cou id 1");

    // Ignore 'created' and 'modified' timestamps.
    $actual = Hash::remove($actual, '{n}.CoGroup.created');
    $actual = Hash::remove($actual, '{n}.CoGroup.modified');

    $expected = array(
      array(
        'CoGroup' => array(
          'id'               => '4',
          'co_id'            => '2',
          'cou_id'           => '1',
          'name'             => 'CO:COU:Test COU:admins',
          'description'      => 'Test COU Administrators',
          'open'             => false,
          'status'           => 'A',
          'group_type'       => 'A',
          'auto'             => false,
          'co_group_id'      => NULL,
          'revision'         => '0',
          'deleted'          => false,
          'actor_identifier' => NULL,
        )),
      array(
        'CoGroup' => array(
          'id'               => '5',
          'co_id'            => '2',
          'cou_id'           => '1',
          'name'             => 'CO:COU:Test COU:members:active',
          'description'      => 'Test COU Active Members',
          'open'             => false,
          'status'           => 'A',
          'group_type'       => 'MA',
          'auto'             => true,
          'co_group_id'      => NULL,
          'revision'         => '0',
          'deleted'          => false,
          'actor_identifier' => NULL,
        )),
      array(
        'CoGroup' => array(
          'id'               => '6',
          'co_id'            => '2',
          'cou_id'           => '1',
          'name'             => 'CO:COU:Test COU:members:all',
          'description'      => 'Test COU Members',
          'open'             => false,
          'status'           => 'A',
          'group_type'       => 'M',
          'auto'             => true,
          'co_group_id'      => NULL,
          'revision'         => '0',
          'deleted'          => false,
          'actor_identifier' => NULL,
        )),
    );

    $this->assertEquals($expected, $actual);
  }

  /**
   * Test obtaining the ID of the CO admin group which does not exist.
   */
  public function testAdminCoGroupIdCoNotFound() {
    $this->setExpectedException(InvalidArgumentException::class, 'Group admins Not Found');
    $this->CoGroup->adminCoGroupId(2);
  }

  /**
   * Test obtaining the ID of the admin group for CO with ID 1.
   */
  public function testAdminCoGroupIdCo() {
    $actual = $this->CoGroup->adminCoGroupId(1);
    $this->assertEquals(1, $actual, "Id of admin group should be 1");
  }

  /**
   * Test obtaining the ID of the admin group for CO with ID 2.
   */
  public function testAdminCoGroupIdCo2() {
    // Get a Co object.
    $Co = ClassRegistry::init('Co');

    // Find Co with id 2, should not exist.
    $args = array();
    $args['conditions']['Co.id'] = '2';
    $args['contain'] = false;
    $result = $Co->find('first', $args);
    $this->assertEmpty($result, "Co with id 2 should not exist");

    // Create Co with id 2.
    $co = array(
      'Co' => array(
        'name'        => 'Test CO',
        'description' => 'Test CO for CoGroupTest',
        'status'      => StatusEnum::Active
      )
    );
    $Co->save($co);
    $this->assertEquals(2, $Co->id, "Newly created Co should have id 2");

    // Add default groups to Co with id 2.
    $return = $this->CoGroup->addDefaults(2);
    $this->assertTrue($return, "CoGroup->AddDefaults() should return true");

    $actual = $this->CoGroup->adminCoGroupId(2);
    $this->assertEquals(4, $actual, "Id of admin group should be 4");
  }

  /**
   * Test obtaining the ID of the COU admin group.
   */
  public function testAdminCoGroupIdCou() {

    // Get a Co object.
    $Co = ClassRegistry::init('Co');

    // Get a Cou object.
    $Cou = ClassRegistry::init('Cou');

    // Find Co with id 2, should not exist.
    $args = array();
    $args['conditions']['Co.id'] = '2';
    $args['contain'] = false;
    $result = $Co->find('first', $args);
    $this->assertEmpty($result, "Co with id 2 should not exist");

    // Create Co with id 2.
    $co = array(
      'Co' => array(
        'name'        => 'Test CO',
        'description' => 'Test CO for CoGroupTest',
        'status'      => StatusEnum::Active
      )
    );
    $Co->save($co, array('callbacks' => false)); // do not call callbacks
    $this->assertEquals(2, $Co->id, "Newly created Co should have id 2");

    // Find Cou with id 1, should not exist.
    $args = array();
    $args['conditions']['Cou.id'] = '1';
    $args['contain'] = false;
    $result = $Cou->find('first', $args);
    $this->assertEmpty($result, "Cou with id 1 should not exist");

    // Create Cou with id 1.
    $cou = array(
      'Cou' => array(
        'co_id'       => 2,
        'name'        => 'Test COU',
        'description' => 'Test COU for CoGroupTest',
        'status'      => StatusEnum::Active
      )
    );
    $Cou->save($cou, array('callbacks' => false)); // do not call callbacks
    $cou_id = $Cou->id;
    $this->assertEquals(1, $cou_id, "Newly created Cou should have id 1");

    // Add default groups to Co with id 2 and Cou with id 1.
    $return = $this->CoGroup->addDefaults(2, 1);
    $this->assertTrue($return, "CoGroup->AddDefaults() should return true");

    $actual = $this->CoGroup->adminCoGroupId(2, 1);

    $this->assertEquals(4, $actual);
  }

  /**
   * testFindForCoPerson method
   *
   * @return void
   */
  public function testFindForCoPerson() {
    $this->markTestIncomplete('testFindForCoPerson not implemented.');
  }

  /**
   * testFindSortedMembers method
   *
   * @return void
   */
  public function testFindSortedMembers() {
    $this->markTestIncomplete('testFindSortedMembers not implemented.');
  }

  /**
   * testIsCouAdminGroup method
   *
   * @return void
   */
  public function testIsCouAdminGroup() {
    $this->markTestIncomplete('testIsCouAdminGroup not implemented.');
  }

  /**
   * testIsCouAdminOrMembersGroup method
   *
   * @return void
   */
  public function testIsCouAdminOrMembersGroup() {
    $this->markTestIncomplete('testIsCouAdminOrMembersGroup not implemented.');
  }

  /**
   * testIsCoMembersGroup method
   *
   * @return void
   */
  public function testIsCoMembersGroup() {
    $this->markTestIncomplete('testIsCoMembersGroup not implemented.');
  }

  /**
   * testIsCouMembersGroup method
   *
   * @return void
   */
  public function testIsCouMembersGroup() {
    $this->markTestIncomplete('testIsCouMembersGroup not implemented.');
  }

  /**
   * testProvisioningStatus method
   *
   * @return void
   */
  public function testProvisioningStatus() {
    $this->markTestIncomplete('testProvisioningStatus not implemented.');
  }

  /**
   * testReadOnly method
   *
   * @return void
   */
  public function testReadOnly() {
    $this->markTestIncomplete('testReadOnly not implemented.');
  }

  /**
   * testReconcileAutomaticGroup method
   *
   * @return void
   */
  public function testReconcileAutomaticGroup() {
    $this->markTestIncomplete('testReconcileAutomaticGroup not implemented.');
  }

  /**
   * Test creating a Co group.
   */
  public function testCreateCoGroup() {

    // Assert that the group does not already exist.
    $args = array();
    $args['conditions']['name'] = 'Test Group';
    $args['conditions']['CoGroup.deleted'] = false;
    $args['contain'] = false;
    $actual = $this->CoGroup->find('first', $args);
    $expected = array();
    $this->assertEquals($expected, $actual, "Test group should not exist");

    // Create the group.
    $group = array(
      'CoGroup' => array(
        'co_id'       => '1',
        'name'        => 'Test Group',
        'description' => 'Test Group Description',
        'group_type'  => GroupEnum::Standard,
        'auto'        => false,
        'open'        => false,
        'status'      => SuspendableStatusEnum::Active
      )
    );
    $result = $this->CoGroup->clear();
    $this->assertTrue($result, "Expected to clear the group");
    $actual = $this->CoGroup->save($group);

    // Test result of save operation.
    $this->assertNotNull($actual, "Test group should exist after saving");
    $this->assertNotEmpty($actual, "Test group should exist after saving");
    $actual = Hash::remove($actual, 'CoGroup.created'); // Ignore 'created' timestamp
    $actual = Hash::remove($actual, 'CoGroup.modified'); // Ignore 'modified' timestamp
    $expected = array(
      'CoGroup' => array(
        'id'               => '4',
        'co_id'            => '1',
        'name'             => 'Test Group',
        'description'      => 'Test Group Description',
        'open'             => false,
        'status'           => 'A',
        'group_type'       => 'S',
        'auto'             => false,
        'co_group_id'      => NULL,
        'revision'         => '0',
        'deleted'          => false,
        'actor_identifier' => NULL,
      ),
    );
    $this->assertEquals($expected, $actual, "Unexpected results after saving the group");

    // Find the group.
    $actual = $this->CoGroup->find('first', $args);

    // Test result of find operation.
    $this->assertNotNull($actual, "Test group not found");
    $this->assertNotEmpty($actual, "Test group not found");
    $actual = Hash::remove($actual, 'CoGroup.created'); // Ignore 'created' timestamp
    $actual = Hash::remove($actual, 'CoGroup.modified'); // Ignore 'modified' timestamp
    $expected['CoGroup']['cou_id'] = NULL;
    $this->assertEquals($expected, $actual, "Unexpected results after finding the group");
  }

  /**
   * Test creating a Cou group.
   */
  public function testCreateCouGroup() {

    // Create test Cou with id 1.
    $this->createTestCou();

    // Assert that the group does not already exist.
    $args = array();
    $args['conditions']['name'] = 'Test Group';
    $args['conditions']['CoGroup.deleted'] = false;
    $args['contain'] = false;
    $actual = $this->CoGroup->find('first', $args);
    $expected = array();
    $this->assertEquals($expected, $actual, "Test group should not exist");

    // Create the group.
    $group = array(
      'CoGroup' => array(
        'co_id'       => '1',
        'cou_id'      => '1',
        'name'        => 'Test Group',
        'description' => 'Test Group Description',
        'group_type'  => GroupEnum::Standard,
        'auto'        => false,
        'open'        => false,
        'status'      => SuspendableStatusEnum::Active
      )
    );
    $result = $this->CoGroup->clear();
    $this->assertTrue($result, "Expected to clear the group");
    $actual = $this->CoGroup->save($group);

    // Test result of save operation.
    $this->assertNotNull($actual, "Test group should exist after saving");
    $this->assertNotEmpty($actual, "Test group should exist after saving");
    $actual = Hash::remove($actual, 'CoGroup.created'); // Ignore 'created' timestamp
    $actual = Hash::remove($actual, 'CoGroup.modified'); // Ignore 'modified' timestamp
    $expected = array(
      'CoGroup' => array(
        'id'               => '7',
        'co_id'            => '1',
        'cou_id'           => '1',
        'name'             => 'Test Group',
        'description'      => 'Test Group Description',
        'open'             => false,
        'status'           => 'A',
        'group_type'       => 'S',
        'auto'             => false,
        'co_group_id'      => NULL,
        'revision'         => '0',
        'deleted'          => false,
        'actor_identifier' => NULL,
      ),
    );
    $this->assertEquals($expected, $actual, "Unexpected results after saving the group");

    // Find the group.
    $actual = $this->CoGroup->find('first', $args);

    // Test result of find operation.
    $this->assertNotNull($actual, "Test group not found");
    $this->assertNotEmpty($actual, "Test group not found");
    $actual = Hash::remove($actual, 'CoGroup.created'); // Ignore 'created' timestamp
    $actual = Hash::remove($actual, 'CoGroup.modified'); // Ignore 'modified' timestamp
    $this->assertEquals($expected, $actual, "Unexpected results after finding the group");
  }

  /**
   * Test modifying a group.
   */
  public function testModifyGroup() {

    // Create a group.
    $this->testCreateCouGroup();

    // Find the newly created group.
    $args = array();
    $args['conditions']['name'] = 'Test Group';
    $args['conditions']['CoGroup.deleted'] = false;
    $args['contain'] = false;
    $actual = $this->CoGroup->find('first', $args);
    $this->assertNotNull($actual, "Test group not found");
    $this->assertNotEmpty($actual, "Test group not found");
    $actual = Hash::remove($actual, 'CoGroup.created'); // Ignore 'created' timestamp
    $actual = Hash::remove($actual, 'CoGroup.modified'); // Ignore 'modified' timestamp
    $expected = array(
      'CoGroup' => array(
        'id'               => '7',
        'co_id'            => '1',
        'cou_id'           => '1',
        'name'             => 'Test Group',
        'description'      => 'Test Group Description',
        'open'             => false,
        'status'           => 'A',
        'group_type'       => 'S',
        'auto'             => false,
        'co_group_id'      => NULL,
        'revision'         => '0',
        'deleted'          => false,
        'actor_identifier' => NULL,
      ),
    );
    $this->assertEquals($expected, $actual, "Unexpected results after finding the group");

    // Modify the group.
    $actual['CoGroup']['description'] = 'New Description';
    $result = $this->CoGroup->save($actual);

    // Test result of save operation.
    $result = Hash::remove($result, 'CoGroup.modified'); // Ignore 'modified' timestamp
    $expected = array(
      'CoGroup' => array(
        'id'               => '7',
        'co_id'            => '1',
        'cou_id'           => '1',
        'name'             => 'Test Group',
        'description'      => 'New Description',
        'open'             => false,
        'status'           => 'A',
        'group_type'       => 'S',
        'auto'             => false,
        'co_group_id'      => NULL,
        'revision'         => '1',
        'deleted'          => false,
        'actor_identifier' => NULL,
      ),
    );
    $this->assertEquals($expected, $result, "Unexpected results after saving the modified group");

    // Find group after modification and assert modification.
    $actual = $this->CoGroup->find('first', $args);
    $actual = Hash::remove($actual, 'CoGroup.created'); // Ignore 'created' timestamp
    $actual = Hash::remove($actual, 'CoGroup.modified'); // Ignore 'modified' timestamp
    $expected['CoGroup']['revision'] = '1';
    $expected['CoGroup']['description'] = 'New Description';
    $this->assertEquals($expected, $actual);
  }

}
