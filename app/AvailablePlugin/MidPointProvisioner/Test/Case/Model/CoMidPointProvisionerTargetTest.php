<?php

App::uses('CoMidPointProvisionerTarget', 'Model');
App::uses('MidPointRestApiClient', 'MidPointProvisioner.Lib');

class CoMidPointProvisionerTargetTest extends CakeTestCase {

  public $fixtures = array(
    'plugin.MidPointProvisioner.CoMidPointProvisionerTarget',
    'app.Address',
    'app.Co',
    'app.CoDashboard',
    'app.CoDepartment',
    'app.CoEmailList',
    'app.CoEnrollmentFlow',
    'app.CoExpirationPolicy',
    'app.CoExtendedType',
    'app.CoGroup',
    'app.CoGroupMember',
    'app.CoGroupNesting',
    'app.CoIdentifierAssignment',
    'app.CoIdentifierValidator',
    'app.CoNotification',
    'app.CoOrgIdentityLink',
    'app.CoPerson',
    'app.CoPersonRole',
    'app.CoProvisioningExport',
    'app.CoProvisioningTarget',
    'app.CoService',
    'app.CoSetting',
    'app.CoTAndCAgreement',
    'app.Cou',
    'app.EmailAddress',
    'app.HistoryRecord',
    'app.HttpServer',
    'app.Identifier',
    'app.Name',
    'app.OrgIdentity',
    'app.OrgIdentitySourceRecord',
    'app.Server',
    'app.TelephoneNumber',
    'app.Url',
    'plugin.SshKeyAuthenticator.SshKey',
    'plugin.SshKeyAuthenticator.SshKeyAuthenticator',
  );

  /** @var String ID of test CO */
  public $coId;

  /** @var HttpServer id */
  public $serverId = '2';

  /** @var MidPoint Server IP address */
  public $serverIP = '127.0.0.1';

  public $coProvisioningTargetData = array(
    'CoMidPointProvisionerTarget' => array(
      'co_provisioning_target_id' => '1',
      'server_id' => '2',
      'user_name_identifier' => 'uid'
    )
  );

  /** @var MidPointRestApiClient $api */
  public $api;

  /** @var CoMidPointProvisionerTarget $target */
  public $target;

  /** @var array Array of OIDs to be deleted during tear down */
  public $oidsToDelete = array();

  public function setUp() {
    parent::setUp();
    $this->coId = $this->createCo();
    $this->createServer($this->serverId, $this->serverIP);
    $this->target = ClassRegistry::init('CoMidPointProvisionerTarget');
    $this->api = new MidPointRestApiClient($this->serverId);
  }

  /**
   * Delete test OIDs.
   */
  public function tearDown() {
    // Delete test users.
    foreach ($this->oidsToDelete as $oid) {
      $this->api->deleteUser($oid);
    }
    parent::tearDown();
  }

  /**
   * Create test CO.
   *
   * @return CO id
   */
  public function createCo() {
    $co = array(
      'Co' => array(
        'name' => 'MidPointTestCo',
        'description' => 'MidPointTestCo',
        'status' => StatusEnum::Active
      )
    );
    $Co = ClassRegistry::init('Co');
    $Co->save($co);
    return $Co->id;
  }

  /**
   * Create test server configuration.
   *
   * @param $serverId server ID
   * @param $serverIP server IP address
   */
  public function createServer($serverId, $serverIP) {
    $serverData = array(
      'Server' => array(
        'id' => $serverId,
        'co_id' => $this->coId,
        'description' => 'Test HTTP Server',
        'server_type' => ServerEnum::HttpServer,
        'status' => StatusEnum::Active
      )
    );
    $Server = ClassRegistry::init('Server');
    $Server->save($serverData);

    $httpServerData = array(
      'HttpServer' => array(
        'server_id' => $serverId,
        'serverurl' => "https://$serverIP/midpoint/ws/rest",
        'username' => 'Administrator',
        'password' => '5ecr3t',
        'ssl_allow_self_signed' => true,
        'ssl_verify_peer' => false,
        'ssl_verify_peer_name' => false,
      )
    );
    $HttpServer = ClassRegistry::init('HttpServer');
    $HttpServer->save($httpServerData);
  }

  public function buildCoPerson($i = "", $includeIdentifier = true, $allAttributes = true) {
    $coPerson = array(
      'CoPerson' => array(
        'co_id' => $this->coId,
        'status' => StatusEnum::Active
      ),
      'PrimaryName' => array(
        'given' => 'Given' . $i,
        'family' => 'Family' . $i,
        'type' => NameEnum::Official,
      )
    );

    if ($includeIdentifier) {
      $identifier = array(
        'Identifier' => array(
          array(
            'identifier' => 'given' . $i . '.' . 'family' . $i,
            'type' => IdentifierEnum::UID,
            'status' => StatusEnum::Active
          )
        )
      );
      $coPerson = array_merge_recursive($coPerson, $identifier);
    }

    if ($allAttributes) {
      $attributes = array(
        'PrimaryName' => array(
          'honorific' => 'Dr',
          'middle' => 'Middle',
          'suffix' => 'III'
        )
      );
      $coPerson = array_merge_recursive($coPerson, $attributes);
    }

    // CakeLog::debug('generateCoPerson ' . var_export($coPerson, true));
    return $coPerson;
  }

  public function saveCoPerson($coPerson) {
    $CoPerson = ClassRegistry::init('CoPerson');
    $CoPerson->saveAssociated($coPerson);
    $coPersonId = $CoPerson->id;
    return array_merge_recursive($coPerson,
      array(
        'CoPerson' => array(
          'id' => $coPersonId
        )
      )
    );
  }

  public function buildMidPointUser($i = "", $allAttributes = true) {
    $midPointUser = array(
      'user' => array(
        'givenName' => 'Given' . $i,
        'familyName' => 'Family' . $i,
        'fullName' => 'Given' . $i . ' ' . 'Family' . $i,
        'name' => 'given' . $i . '.' . 'family' . $i
      )
    );

    if ($allAttributes) {
      $attributes = array(
        'user' => array(
          'additionalName' => 'Middle',
          'fullName' => 'Dr Given' . $i . ' Middle Family' . $i . ' III',
          'honorificPrefix' => 'Dr',
          'honorificSuffix' => 'III',
          'nickName' => 'Given' . $i,
        )
      );
      $midPointUser = array_replace_recursive($midPointUser, $attributes);
    }

    // CakeLog::debug('generateMidPointUser ' . var_export($midPointUser, true));
    return $midPointUser;
  }

  public function createAndVerifyUser($comanagePerson, $midPointUser, $toDelete = true) {
    // create user in midPoint
    $return = $this->target->createUser($this->coProvisioningTargetData, $comanagePerson);
    $this->assertTrue($return);

    // find OID returned from midPoint when creating user
    $oid = $this->target->findIdentifier($this->coProvisioningTargetData, $comanagePerson);
    $this->assertTrue(is_string($oid));

    // save midPoint OID to delete user during tear down
    if ($toDelete) {
      array_push($this->oidsToDelete, $oid);
    }

    // get user from midPoint and validate fields
    $user = $this->api->getUser($oid);
    foreach ($midPointUser['user'] as $key => $value) {
      $this->assertEquals($midPointUser['user'][$key], $user['user'][$key]);
    }

    // return midPoint OID
    return $oid;
  }

  public function deleteUser($coProvisioningTargetData, $provisioningData) {
    $oid = $this->target->findIdentifier($coProvisioningTargetData, $provisioningData);
    $this->assertTrue(is_string($oid));
    $this->assertNotEmpty($this->api->getUser($oid));
    $deleted = $this->target->deleteUser($coProvisioningTargetData, $provisioningData);
    $this->assertTrue($deleted);
    $this->assertEmpty($this->api->getUser($oid));
  }

  // tests

  public function testCalcUser() {
    $coPerson = $this->buildCoPerson();
    $coPerson = $this->saveCoPerson($coPerson);
    $generatedMidPointUser = $this->buildMidPointUser();
    $calculatedMidPointUser = $this->target->calcUser($this->coProvisioningTargetData, $coPerson);
    foreach ($generatedMidPointUser['user'] as $key => $value) {
      $this->assertEquals($generatedMidPointUser['user'][$key], $calculatedMidPointUser['user'][$key]);
    }
    foreach ($calculatedMidPointUser['user'] as $key => $value) {
      $this->assertEquals($generatedMidPointUser['user'][$key], $calculatedMidPointUser['user'][$key]);
    }
  }

  public function testCalcUserMinimal() {
    $coPerson = $this->buildCoPerson("", true, false);
    $coPerson = $this->saveCoPerson($coPerson);
    $generatedMidPointUser = $this->buildMidPointUser("", false);
    $calculatedMidPointUser = $this->target->calcUser($this->coProvisioningTargetData, $coPerson);
    foreach ($generatedMidPointUser['user'] as $key => $value) {
      $this->assertEquals($generatedMidPointUser['user'][$key], $calculatedMidPointUser['user'][$key]);
    }
    $generatedMidPointUser['user']['nickName'] = 'Given';
    foreach ($calculatedMidPointUser['user'] as $key => $value) {
      $this->assertEquals($generatedMidPointUser['user'][$key], $calculatedMidPointUser['user'][$key]);
    }
  }

  public function testCreateUser() {
    $coPerson = $this->buildCoPerson();
    $coPerson = $this->saveCoPerson($coPerson);
    $midPointUser = $this->buildMidPointUser();
    $this->createAndVerifyUser($coPerson, $midPointUser);
  }

  public function testCreateUserMinimal() {
    $coPerson = $this->buildCoPerson("", true, false);
    $coPerson = $this->saveCoPerson($coPerson);
    $midPointUser = $this->buildMidPointUser("", false);
    $this->createAndVerifyUser($coPerson, $midPointUser);
  }

  public function testDeleteUser() {
    $coPerson = $this->buildCoPerson();
    $coPerson = $this->saveCoPerson($coPerson);
    $midPointUser = $this->buildMidPointUser();
    $this->createAndVerifyUser($coPerson, $midPointUser, false);
    $this->deleteUser($this->coProvisioningTargetData, $coPerson);
  }

  public function testDeleteUserDoesNotExistNoIdentifier() {
    $coPerson = $this->buildCoPerson();
    $coPerson = $this->saveCoPerson($coPerson);
    $midPointUser = $this->buildMidPointUser();
    $oid = $this->createAndVerifyUser($coPerson, $midPointUser);
    $this->assertTrue(is_string($oid));

    $return = $this->target->deleteIdentifier($this->coProvisioningTargetData, $coPerson);
    $this->assertTrue($return);
    $expected = null;
    $actual = $this->target->findIdentifier($this->coProvisioningTargetData, $coPerson);
    $this->assertEquals($expected, $actual);

    $deleted = $this->target->deleteUser($this->coProvisioningTargetData, $coPerson);
    $this->assertFalse($deleted);
    $this->assertNotEmpty($this->api->getUser($oid));
  }

  public function testDiffUserAdd() {
    $midPointUserBefore = $this->buildMidPointUser("", false);

    $midPointUserAfter = $midPointUserBefore;
    $midPointUserAfter['user']['additionalName'] = 'Middle';

    $expectedMods = array(
      'add' => array(
        'additionalName' => 'Middle'
      )
    );

    $mods = $this->target->diffUser($midPointUserAfter, $midPointUserBefore);
    $this->assertEquals($expectedMods, $mods);
  }

  public function testDiffUserAddDeleteReplace() {
    $midPointUserBefore = $this->buildMidPointUser("", false);

    $midPointUserAfter = $midPointUserBefore;
    $midPointUserAfter['user']['additionalName'] = 'Middle';
    $midPointUserAfter['user']['givenName'] = 'New Given';

    $midPointUserBefore['user']['nickName'] = 'Given';

    $expectedMods = array(
      'add' => array(
        'additionalName' => 'Middle'
      ),
      'delete' => array(
        'nickName' => 'Given'
      ),
      'replace' => array(
        'givenName' => 'New Given'
      )
    );

    $mods = $this->target->diffUser($midPointUserAfter, $midPointUserBefore);
    $this->assertEquals($expectedMods, $mods);
  }

  public function testDiffUserAddMultiple() {
    $midPointUserBefore = $this->buildMidPointUser("", false);

    $midPointUserAfter = $midPointUserBefore;
    $midPointUserAfter['user']['additionalName'] = 'Middle';
    $midPointUserAfter['user']['nickName'] = 'Given';

    $expectedMods = array(
      'add' => array(
        'additionalName' => 'Middle',
        'nickName' => 'Given'
      )
    );

    $mods = $this->target->diffUser($midPointUserAfter, $midPointUserBefore);
    $this->assertEquals($expectedMods, $mods);
  }

  public function testDiffUserDelete() {
    $midPointUserBefore = $this->buildMidPointUser("", false);

    $midPointUserAfter = $midPointUserBefore;

    $midPointUserBefore['user']['nickName'] = 'Given';

    $expectedMods = array(
      'delete' => array(
        'nickName' => 'Given'
      )
    );

    $mods = $this->target->diffUser($midPointUserAfter, $midPointUserBefore);
    $this->assertEquals($expectedMods, $mods);
  }

  public function testDiffUserDeleteMultiple() {
    $midPointUserBefore = $this->buildMidPointUser("", false);

    $midPointUserAfter = $midPointUserBefore;

    $midPointUserBefore['user']['additionalName'] = 'Middle';
    $midPointUserBefore['user']['nickName'] = 'Given';

    $expectedMods = array(
      'delete' => array(
        'additionalName' => 'Middle',
        'nickName' => 'Given'
      )
    );

    $mods = $this->target->diffUser($midPointUserAfter, $midPointUserBefore);
    $this->assertEquals($expectedMods, $mods);
  }

  public function testDiffUserNoChanges() {
    $midPointUserBefore = $this->buildMidPointUser("", false);

    $midPointUserAfter = $midPointUserBefore;

    $mods = $this->target->diffUser($midPointUserAfter, $midPointUserBefore);
    $this->assertEmpty($mods);
  }

  public function testDiffUserReplace() {
    $midPointUserBefore = $this->buildMidPointUser("", false);

    $midPointUserAfter = $midPointUserBefore;
    $midPointUserAfter['user']['givenName'] = 'New Given';

    $expectedMods = array(
      'replace' => array(
        'givenName' => 'New Given'
      )
    );

    $mods = $this->target->diffUser($midPointUserAfter, $midPointUserBefore);
    $this->assertEquals($expectedMods, $mods);
  }

  public function testDiffUserReplaceMultiple() {
    $midPointUserBefore = $this->buildMidPointUser("", false);

    $midPointUserAfter = $midPointUserBefore;
    $midPointUserAfter['user']['familyName'] = 'New Family';
    $midPointUserAfter['user']['givenName'] = 'New Given';

    $expectedMods = array(
      'replace' => array(
        'familyName' => 'New Family',
        'givenName' => 'New Given',
      )
    );

    $mods = $this->target->diffUser($midPointUserAfter, $midPointUserBefore);
    $this->assertEquals($expectedMods, $mods);
  }

  public function testDeleteIdentifier() {
    $provisioningData = array(
      'CoPerson' => array(
        'id' => 1
      )
    );
    $coProvisioningTargetData = array(
      'CoMidPointProvisionerTarget' => array(
        'co_provisioning_target_id' => 1
      )
    );
    $oid = "OID";

    $id = $this->target->saveIdentifier($coProvisioningTargetData, $provisioningData, $oid);
    $this->assertNotEmpty($id);

    $actualIdentifier = $this->target->findIdentifier($coProvisioningTargetData, $provisioningData);
    $this->assertEquals($oid, $actualIdentifier);

    $this->target->deleteIdentifier($coProvisioningTargetData, $provisioningData);

    $expected = null;
    $actualIdentifier = $this->target->findIdentifier($coProvisioningTargetData, $provisioningData);
    $this->assertEquals($expected, $actualIdentifier);
  }

  public function testDeleteIdentifierDoesNotExist() {
    $provisioningData = array(
      'CoPerson' => array(
        'id' => 1
      )
    );
    $coProvisioningTargetData = array(
      'CoMidPointProvisionerTarget' => array(
        'co_provisioning_target_id' => 1
      )
    );

    $expected = null;
    $actualIdentifier = $this->target->findIdentifier($coProvisioningTargetData, $provisioningData);
    $this->assertEquals($expected, $actualIdentifier);

    $return = $this->target->deleteIdentifier($coProvisioningTargetData, $provisioningData);
    $this->assertFalse($return);
  }

  public function testFindIdentifierDoesNotExist() {
    $provisioningData = array(
      'CoPerson' => array(
        'id' => 1
      )
    );
    $coProvisioningTargetData = array(
      'CoMidPointProvisionerTarget' => array(
        'co_provisioning_target_id' => 1
      )
    );
    $expected = null;
    $actual = $this->target->findIdentifier($coProvisioningTargetData, $provisioningData);
    $this->assertEquals($expected, $actual);
  }

  public function testSaveAndFindIdentifier() {
    $provisioningData = array(
      'CoPerson' => array(
        'id' => 1
      )
    );
    $coProvisioningTargetData = array(
      'CoMidPointProvisionerTarget' => array(
        'co_provisioning_target_id' => 1
      )
    );
    $oid = "OID";

    $id = $this->target->saveIdentifier($coProvisioningTargetData, $provisioningData, $oid);
    $this->assertNotEmpty($id);

    $actualIdentifier = $this->target->findIdentifier($coProvisioningTargetData, $provisioningData);
    $this->assertEquals($oid, $actualIdentifier);
  }

  public function testSaveIdentifierAlreadyInUse() {
    $this->setExpectedException(OverflowException::class, 'The identifier "OID" is already in use');
    $this->testSaveAndFindIdentifier();
    $this->testSaveAndFindIdentifier();
  }

  public function testIsUserProvisionableTrue() {
    $coProvisioningTargetData = array();
    $provisionableStatus = array(StatusEnum::Active, StatusEnum::GracePeriod);
    foreach ($provisionableStatus as $status) {
      $provisioningData = array(
        'CoPerson' => array(
          'status' => $status
        )
      );
      $isUserProvisionable = $this->target->isUserProvisionable($coProvisioningTargetData, $provisioningData);
      $this->assertTrue($isUserProvisionable);
    }
  }

  public function testIsUserProvisionableFalse() {
    $coProvisioningTargetData = array();
    $provisionableStatus = array(StatusEnum::$to_api[StatusEnum::Active], StatusEnum::$to_api[StatusEnum::GracePeriod]);
    $notProvisionableStatus = array_diff(StatusEnum::$to_api, $provisionableStatus);
    foreach ($notProvisionableStatus as $status) {
      $provisioningData = array(
        'CoPerson' => array(
          'status' => $status
        )
      );
      $isUserProvisionable = $this->target->isUserProvisionable($coProvisioningTargetData, $provisioningData);
      $this->assertFalse($isUserProvisionable);
    }
  }

  public function testUpdateUserNoChanges() {
    // Create new user.
    $coPerson = $this->buildCoPerson();
    $coPerson = $this->saveCoPerson($coPerson);
    $midPointUser = $this->buildMidPointUser();
    $oid = $this->createAndVerifyUser($coPerson, $midPointUser);

    // Update user with no changes.
    $this->target->updateUser($this->coProvisioningTargetData, $coPerson);

    // Verify user.
    $user = $this->api->getUser($oid);
    foreach ($midPointUser['user'] as $key => $value) {
      $this->assertEquals($midPointUser['user'][$key], $user['user'][$key]);
    }
  }

  public function testUpdateUserAddMiddleName() {
    // Create new user.
    $coPerson = $this->buildCoPerson();
    $coPerson = $this->saveCoPerson($coPerson);
    $midPointUser = $this->buildMidPointUser();
    $oid = $this->createAndVerifyUser($coPerson, $midPointUser);

    // Add middle name
    $updatedCoPerson = $coPerson;
    $updatedCoPerson['PrimaryName']['middle'] = 'Middle';

    $updatedMidPointUser = $midPointUser;
    $updatedMidPointUser['user']['fullName'] = 'Dr Given Middle Family III';
    $updatedMidPointUser['user']['additionalName'] = 'Middle';

    // Update user.
    $this->target->updateUser($this->coProvisioningTargetData, $updatedCoPerson);

    // Verify user.
    $user = $this->api->getUser($oid);
    foreach ($updatedMidPointUser['user'] as $key => $value) {
      $this->assertEquals($updatedMidPointUser['user'][$key], $user['user'][$key]);
    }
  }

  public function testUpdateUserDeleteFamilyName() {
    // Create new user.
    $coPerson = $this->buildCoPerson();
    $coPerson = $this->saveCoPerson($coPerson);
    $midPointUser = $this->buildMidPointUser();
    $oid = $this->createAndVerifyUser($coPerson, $midPointUser);

    // Add middle name
    $updatedCoPerson = $coPerson;
    unset($updatedCoPerson['PrimaryName']['family']);

    $updatedMidPointUser = $midPointUser;
    unset($updatedMidPointUser['user']['familyName']);
    $updatedMidPointUser['user']['fullName'] = 'Dr Given Middle III';

    // Update user.
    $this->target->updateUser($this->coProvisioningTargetData, $updatedCoPerson);

    // Verify user.
    $user = $this->api->getUser($oid);
    foreach ($updatedMidPointUser['user'] as $key => $value) {
      $this->assertEquals($updatedMidPointUser['user'][$key], $user['user'][$key]);
    }
    $this->assertFalse(isset($user['user']['familyName']));
  }

  public function testUpdateUserDeleteHonorific() {
    // Create new user.
    $coPerson = $this->buildCoPerson();
    $coPerson = $this->saveCoPerson($coPerson);
    $midPointUser = $this->buildMidPointUser();
    $oid = $this->createAndVerifyUser($coPerson, $midPointUser);

    // Delete honorific
    $updatedCoPerson = $coPerson;
    unset($updatedCoPerson['PrimaryName']['honorific']);

    $updatedMidPointUser = $midPointUser;
    unset($updatedMidPointUser['user']['honorificPrefix']);
    $updatedMidPointUser['user']['fullName'] = 'Given Middle Family III';

    // Update user.
    $this->target->updateUser($this->coProvisioningTargetData, $updatedCoPerson);

    // Verify user.
    $user = $this->api->getUser($oid);
    foreach ($updatedMidPointUser['user'] as $key => $value) {
      $this->assertEquals($updatedMidPointUser['user'][$key], $user['user'][$key]);
    }
    $this->assertFalse(isset($user['user']['honorificPrefix']));
  }

}