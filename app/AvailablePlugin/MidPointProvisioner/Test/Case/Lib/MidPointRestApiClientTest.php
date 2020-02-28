<?php

App::uses('MidPointRestApiClient', 'MidPointProvisioner.Lib');

class MidPointRestApiClientTest extends CakeTestCase {

  public $fixtures = array(
    'app.Server',
    'app.HttpServer',
  );

  /** @var HttpServer id */
  public $serverId = '2';

  /** @var MidPoint Server IP address */
  public $serverIP = '127.0.0.1';

  /** @var MidPointRestApiClient $api */
  public $api;

  /** @var array Array of OIDs to be deleted during tear down */
  public $oidsToDelete = array();

  /** @var array Array representation of minimal test user */
  public $minimalUser = array(
    'user' => array(
      'name' => 'Test User',
      'fullName' => 'Test User',
      'givenName' => 'Test',
      'familyName' => 'User'
    )
  );

  /** @var string XML representation of minimal test user */
  public $minimalUserXml =
    '<?xml version="1.0" encoding="UTF-8"?>
       <user xmlns="http://midpoint.evolveum.com/xml/ns/public/common/common-3">
        <name>Test User</name>
        <fullName>Test User</fullName>
        <givenName>Test</givenName>
        <familyName>User</familyName>
       </user>';

  /** @var array Array representation of test user */
  public $user = array(
    'user' => array(
      'name' => 'Test User',
      'fullName' => 'Test User',
      'givenName' => 'Test',
      'familyName' => 'User',
      'additionalName' => 'Middle',
      'nickName' => 'Test',
      'honorificPrefix' => 'Dr',
      'honorificSuffix' => 'III',
      'emailAddress' => 'test.user@example.org'
    )
  );

  /** @var string XML representation of test user */
  public $userXml =
    '<?xml version="1.0" encoding="UTF-8"?>
       <user xmlns="http://midpoint.evolveum.com/xml/ns/public/common/common-3">
        <name>Test User</name>
        <fullName>Test User</fullName>
        <givenName>Test</givenName>
        <familyName>User</familyName>
        <additionalName>Middle</additionalName>
        <nickName>Test</nickName>
        <honorificPrefix>Dr</honorificPrefix>
        <honorificSuffix>III</honorificSuffix>
        <emailAddress>test.user@example.org</emailAddress>
       </user>';

  /**
   * Set up API connection to midPoint.
   */
  public function setUp() {
    parent::setUp();
    $this->createTestServer($this->serverId, $this->serverIP);
    $this->api = new MidPointRestApiClient($this->serverId);
  }

  /**
   * Delete test OIDs.
   */
  public function tearDown() {
    // Delete test users.
    foreach ($this->oidsToDelete as $oid) {
      $this->deleteUser($oid);
    }
    parent::tearDown();
  }

  /**
   * Create test server configuration.
   *
   * @param $serverId server ID
   * @param $serverIP server IP address
   */
  public function createTestServer($serverId, $serverIP) {
    $serverData = array(
      'Server' => array(
        'id' => $serverId,
        'co_id' => '2',
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

  public function testBuildUserXml() {
    $actualXml = $this->api->buildUserXml($this->user);
    $expectedXml = $this->userXml;
    $this->assertXmlStringEqualsXmlString($expectedXml, $actualXml);
  }

  public function testBuildUserXmlMinimal() {
    $actualXml = $this->api->buildUserXml($this->minimalUser);
    $expectedXml = $this->minimalUserXml;
    $this->assertXmlStringEqualsXmlString($expectedXml, $actualXml);
  }

  public function testBuildUserModsAddGivenName() {
    $mods = array(
      'add' => array(
        'givenName' => 'Test'
      )
    );
    $expectedXml =
      '<?xml version="1.0" encoding="UTF-8"?>
        <objectModification xmlns="http://midpoint.evolveum.com/xml/ns/public/common/api-types-3" xmlns:c="http://midpoint.evolveum.com/xml/ns/public/common/common-3" xmlns:t="http://prism.evolveum.com/xml/ns/public/types-3">
        <itemDelta>
            <t:modificationType>add</t:modificationType>
            <t:path>c:givenName</t:path>
            <t:value>Test</t:value>
        </itemDelta>
        </objectModification>';
    $actualXml = $this->api->buildUserModificationXml($mods);
    $this->assertXmlStringEqualsXmlString($expectedXml, $actualXml);
  }

  public function testBuildUserModsAddGivenNameEmptyValue() {
    $mods = array(
      'add' => array(
        'givenName' => ''
      )
    );
    $expectedXml =
      '<?xml version="1.0" encoding="UTF-8"?>
        <objectModification xmlns="http://midpoint.evolveum.com/xml/ns/public/common/api-types-3" xmlns:c="http://midpoint.evolveum.com/xml/ns/public/common/common-3" xmlns:t="http://prism.evolveum.com/xml/ns/public/types-3">
        <itemDelta>
            <t:modificationType>add</t:modificationType>
            <t:path>c:givenName</t:path>
            <t:value></t:value>
        </itemDelta>
        </objectModification>';
    $actualXml = $this->api->buildUserModificationXml($mods);
    $this->assertXmlStringEqualsXmlString($expectedXml, $actualXml);
  }

  public function testBuildUserModsAddGivenNameNoValue() {
    $mods = array(
      'add' => array(
        'givenName' => null
      )
    );
    $expectedXml =
      '<?xml version="1.0" encoding="UTF-8"?>
        <objectModification xmlns="http://midpoint.evolveum.com/xml/ns/public/common/api-types-3" xmlns:c="http://midpoint.evolveum.com/xml/ns/public/common/common-3" xmlns:t="http://prism.evolveum.com/xml/ns/public/types-3">
        <itemDelta>
            <t:modificationType>add</t:modificationType>
            <t:path>c:givenName</t:path>
        </itemDelta>
        </objectModification>';
    $actualXml = $this->api->buildUserModificationXml($mods);
    $this->assertXmlStringEqualsXmlString($expectedXml, $actualXml);
  }


  public function testBuildUserModsDeleteGivenName() {
    $mods = array(
      'delete' => array(
        'givenName' => 'Test'
      )
    );
    $expectedXml =
      '<?xml version="1.0" encoding="UTF-8"?>
        <objectModification xmlns="http://midpoint.evolveum.com/xml/ns/public/common/api-types-3" xmlns:c="http://midpoint.evolveum.com/xml/ns/public/common/common-3" xmlns:t="http://prism.evolveum.com/xml/ns/public/types-3">
        <itemDelta>
            <t:modificationType>delete</t:modificationType>
            <t:path>c:givenName</t:path>
            <t:value>Test</t:value>
        </itemDelta>
        </objectModification>';
    $actualXml = $this->api->buildUserModificationXml($mods);
    $this->assertXmlStringEqualsXmlString($expectedXml, $actualXml);
  }

  public function testBuildUserModsDeleteGivenNameEmptyValue() {
    $mods = array(
      'delete' => array(
        'givenName' => ''
      )
    );
    $expectedXml =
      '<?xml version="1.0" encoding="UTF-8"?>
        <objectModification xmlns="http://midpoint.evolveum.com/xml/ns/public/common/api-types-3" xmlns:c="http://midpoint.evolveum.com/xml/ns/public/common/common-3" xmlns:t="http://prism.evolveum.com/xml/ns/public/types-3">
        <itemDelta>
            <t:modificationType>delete</t:modificationType>
            <t:path>c:givenName</t:path>
            <t:value></t:value>
        </itemDelta>
        </objectModification>';
    $actualXml = $this->api->buildUserModificationXml($mods);
    $this->assertXmlStringEqualsXmlString($expectedXml, $actualXml);
  }

  public function testBuildUserModsDeleteGivenNameNoValue() {
    $mods = array(
      'delete' => array(
        'givenName' => null
      )
    );
    $expectedXml =
      '<?xml version="1.0" encoding="UTF-8"?>
        <objectModification xmlns="http://midpoint.evolveum.com/xml/ns/public/common/api-types-3" xmlns:c="http://midpoint.evolveum.com/xml/ns/public/common/common-3" xmlns:t="http://prism.evolveum.com/xml/ns/public/types-3">
        <itemDelta>
            <t:modificationType>delete</t:modificationType>
            <t:path>c:givenName</t:path>
        </itemDelta>
        </objectModification>';
    $actualXml = $this->api->buildUserModificationXml($mods);
    $this->assertXmlStringEqualsXmlString($expectedXml, $actualXml);
  }

  public function testBuildUserModsReplaceGivenName() {
    $mods = array(
      'replace' => array(
        'givenName' => 'Test'
      )
    );
    $expectedXml =
      '<?xml version="1.0" encoding="UTF-8"?>
        <objectModification xmlns="http://midpoint.evolveum.com/xml/ns/public/common/api-types-3" xmlns:c="http://midpoint.evolveum.com/xml/ns/public/common/common-3" xmlns:t="http://prism.evolveum.com/xml/ns/public/types-3">
        <itemDelta>
            <t:modificationType>replace</t:modificationType>
            <t:path>c:givenName</t:path>
            <t:value>Test</t:value>
        </itemDelta>
        </objectModification>';
    $actualXml = $this->api->buildUserModificationXml($mods);
    $this->assertXmlStringEqualsXmlString($expectedXml, $actualXml);
  }

  public function testBuildUserModsReplaceGivenNameEmptyValue() {
    $mods = array(
      'replace' => array(
        'givenName' => ''
      )
    );
    $expectedXml =
      '<?xml version="1.0" encoding="UTF-8"?>
        <objectModification xmlns="http://midpoint.evolveum.com/xml/ns/public/common/api-types-3" xmlns:c="http://midpoint.evolveum.com/xml/ns/public/common/common-3" xmlns:t="http://prism.evolveum.com/xml/ns/public/types-3">
        <itemDelta>
            <t:modificationType>replace</t:modificationType>
            <t:path>c:givenName</t:path>
            <t:value></t:value>
        </itemDelta>
        </objectModification>';
    $actualXml = $this->api->buildUserModificationXml($mods);
    $this->assertXmlStringEqualsXmlString($expectedXml, $actualXml);
  }

  public function testBuildUserModsReplaceGivenNameNoValue() {
    $mods = array(
      'replace' => array(
        'givenName' => null
      )
    );
    $expectedXml =
      '<?xml version="1.0" encoding="UTF-8"?>
        <objectModification xmlns="http://midpoint.evolveum.com/xml/ns/public/common/api-types-3" xmlns:c="http://midpoint.evolveum.com/xml/ns/public/common/common-3" xmlns:t="http://prism.evolveum.com/xml/ns/public/types-3">
        <itemDelta>
            <t:modificationType>replace</t:modificationType>
            <t:path>c:givenName</t:path>
        </itemDelta>
        </objectModification>';
    $actualXml = $this->api->buildUserModificationXml($mods);
    $this->assertXmlStringEqualsXmlString($expectedXml, $actualXml);
  }


  public function testBuildUserModsReplaceGivenNameAndFamilyName() {
    $mods = array(
      'replace' => array(
        'givenName' => 'Test1',
        'familyName' => 'User1'
      )
    );
    $expectedXml =
      '<?xml version="1.0" encoding="UTF-8"?>
        <objectModification xmlns="http://midpoint.evolveum.com/xml/ns/public/common/api-types-3" xmlns:c="http://midpoint.evolveum.com/xml/ns/public/common/common-3" xmlns:t="http://prism.evolveum.com/xml/ns/public/types-3">
        <itemDelta>
            <t:modificationType>replace</t:modificationType>
            <t:path>c:givenName</t:path>
            <t:value>Test1</t:value>
        </itemDelta>
        <itemDelta>
            <t:modificationType>replace</t:modificationType>
            <t:path>c:familyName</t:path>
            <t:value>User1</t:value>
        </itemDelta>
        </objectModification>';
    $actualXml = $this->api->buildUserModificationXml($mods);
    $this->assertXmlStringEqualsXmlString($expectedXml, $actualXml);
  }

  public function testBuildUserModsReplaceFamilyName() {
    $mods = array(
      'replace' => array(
        'familyName' => 'User1'
      )
    );
    $expectedXml =
      '<?xml version="1.0" encoding="UTF-8"?>
        <objectModification xmlns="http://midpoint.evolveum.com/xml/ns/public/common/api-types-3" xmlns:c="http://midpoint.evolveum.com/xml/ns/public/common/common-3" xmlns:t="http://prism.evolveum.com/xml/ns/public/types-3">
        <itemDelta>
            <t:modificationType>replace</t:modificationType>
            <t:path>c:familyName</t:path>
            <t:value>User1</t:value>
        </itemDelta>
        </objectModification>';
    $actualXml = $this->api->buildUserModificationXml($mods);
    $this->assertXmlStringEqualsXmlString($expectedXml, $actualXml);
  }

  public function createMinimalTestUser() {
    $oid = $this->createUser($this->minimalUserXml);
    $user = $this->api->getUser($oid);
    $this->assertEquals('Test User', $user['user']['name']);
    $this->assertEquals('Test User', $user['user']['fullName']);
    $this->assertEquals('Test', $user['user']['givenName']);
    $this->assertEquals('User', $user['user']['familyName']);
    return $oid;
  }

  public function createTestUser() {
    $oid = $this->createUser($this->userXml);
    $user = $this->api->getUser($oid);
    $this->assertEquals('Test User', $user['user']['name']);
    $this->assertEquals('Test User', $user['user']['fullName']);
    $this->assertEquals('Test', $user['user']['givenName']);
    $this->assertEquals('User', $user['user']['familyName']);
    $this->assertEquals('Middle', $user['user']['additionalName']);
    $this->assertEquals('Test', $user['user']['nickName']);
    $this->assertEquals('Dr', $user['user']['honorificPrefix']);
    $this->assertEquals('III', $user['user']['honorificSuffix']);
    $this->assertEquals('test.user@example.org', $user['user']['emailAddress']);
    return $oid;
  }

  public function createUser($xml, $toDelete = true) {
    $oid = $this->api->createUser($xml);
    $this->assertTrue(is_string($oid));
    if ($toDelete) {
      array_push($this->oidsToDelete, $oid);
    }
    return $oid;
  }

  public function deleteUser($oid) {
    $this->api->deleteUser($oid);
    $this->assertEmpty($this->api->getUser($oid));
  }

  /**
   * Test creating a user.
   */
  public function testCreateUser() {
    $this->createTestUser();
  }

  /**
   * Test creating a minimal user.
   */
  public function testCreateMinimalUser() {
    $this->createMinimalTestUser();
  }

  /**
   * Test creating a user that already exists.
   */
  public function testCreateUserAlreadyExists() {
    $this->setExpectedException(RuntimeException::class, '409');
    $this->createMinimalTestUser();
    $this->createMinimalTestUser();
  }

  /**
   * Test deleting a user.
   */
  public function testDeleteUser() {
    $oid = $this->createUser($this->minimalUserXml, false);
    $this->deleteUser($oid);
  }

  /**
   * Test deleting a user that does not exist.
   */
  public function testDeleteUserDoesNotExist() {
    $this->setExpectedException(RuntimeException::class, '404');
    $this->deleteUser("does-not-exist");
  }

  /**
   * Test getting a user.
   */
  public function testGetUser() {
    $oid = $this->createMinimalTestUser();
    $user = $this->api->getUser($oid);
    $this->assertEquals('Test', $user['user']['givenName']);
    $this->assertEquals('User', $user['user']['familyName']);
    $this->assertEquals('Test User', $user['user']['fullName']);
    $this->assertEquals('Test User', $user['user']['name']);
  }

  /**
   * Test getting a user that does not exist.
   */
  public function testGetUserDoesNotExist() {
    $user = $this->api->getUser( "does-not-exist");
    $this->assertEmpty($user);
  }

  public function testModifyUserReplaceGivenName() {
    // Create new minimal user.
    $oid = $this->createMinimalTestUser();

    // Replace givenName with new value.
    $xml =
      '<?xml version="1.0" encoding="UTF-8"?>
        <objectModification xmlns="http://midpoint.evolveum.com/xml/ns/public/common/api-types-3" xmlns:c="http://midpoint.evolveum.com/xml/ns/public/common/common-3" xmlns:t="http://prism.evolveum.com/xml/ns/public/types-3">
        <itemDelta>
            <t:modificationType>replace</t:modificationType>
            <t:path>c:givenName</t:path>
            <t:value>NewGivenName</t:value>
        </itemDelta>
        </objectModification>';
    $this->assertTrue($this->api->modifyUser($oid, $xml));

    // Verify modified user.
    $user = $this->api->getUser($oid);
    $this->assertEquals('NewGivenName', $user['user']['givenName']);
    $this->assertEquals('User', $user['user']['familyName']);
    $this->assertEquals('Test User', $user['user']['fullName']);
    $this->assertEquals('Test User', $user['user']['name']);
  }

  public function testModifyUserReplaceGivenNameAndFamilyName() {
    // Create new minimal user.
    $oid = $this->createMinimalTestUser();

    // Replace givenName with new value.
    $xml =
      '<?xml version="1.0" encoding="UTF-8"?>
        <objectModification xmlns="http://midpoint.evolveum.com/xml/ns/public/common/api-types-3" xmlns:c="http://midpoint.evolveum.com/xml/ns/public/common/common-3" xmlns:t="http://prism.evolveum.com/xml/ns/public/types-3">
        <itemDelta>
            <t:modificationType>replace</t:modificationType>
            <t:path>c:givenName</t:path>
            <t:value>NewGivenName</t:value>
        </itemDelta>
        <itemDelta>
            <t:modificationType>replace</t:modificationType>
            <t:path>c:familyName</t:path>
            <t:value>NewFamilyName</t:value>
        </itemDelta>
        </objectModification>';
    $this->assertTrue($this->api->modifyUser($oid, $xml));

    // Verify modified user.
    $user = $this->api->getUser($oid);
    $this->assertEquals('NewGivenName', $user['user']['givenName']);
    $this->assertEquals('NewFamilyName', $user['user']['familyName']);
    $this->assertEquals('Test User', $user['user']['fullName']);
    $this->assertEquals('Test User', $user['user']['name']);
  }

  public function testModifyUserReplaceGivenNameAlreadyExistsAndFamilyName() {
    // Create new minimal user.
    $oid = $this->createMinimalTestUser();

    // Replace givenName with new value.
    $xml =
      '<?xml version="1.0" encoding="UTF-8"?>
        <objectModification xmlns="http://midpoint.evolveum.com/xml/ns/public/common/api-types-3" xmlns:c="http://midpoint.evolveum.com/xml/ns/public/common/common-3" xmlns:t="http://prism.evolveum.com/xml/ns/public/types-3">
        <itemDelta>
            <t:modificationType>replace</t:modificationType>
            <t:path>c:givenName</t:path>
            <t:value>Test</t:value>
        </itemDelta>
        <itemDelta>
            <t:modificationType>replace</t:modificationType>
            <t:path>c:familyName</t:path>
            <t:value>NewFamilyName</t:value>
        </itemDelta>
        </objectModification>';
    $this->assertTrue($this->api->modifyUser($oid, $xml));

    // Verify modified user.
    $user = $this->api->getUser($oid);
    $this->assertEquals('Test', $user['user']['givenName']);
    $this->assertEquals('NewFamilyName', $user['user']['familyName']);
    $this->assertEquals('Test User', $user['user']['fullName']);
    $this->assertEquals('Test User', $user['user']['name']);
  }

  public function testModifyUserReplaceGivenNameNoValueAndFamilyName() {
    // Create new minimal user.
    $oid = $this->createMinimalTestUser();

    // Replace givenName with new value.
    $xml =
      '<?xml version="1.0" encoding="UTF-8"?>
        <objectModification xmlns="http://midpoint.evolveum.com/xml/ns/public/common/api-types-3" xmlns:c="http://midpoint.evolveum.com/xml/ns/public/common/common-3" xmlns:t="http://prism.evolveum.com/xml/ns/public/types-3">
        <itemDelta>
            <t:modificationType>replace</t:modificationType>
            <t:path>c:givenName</t:path>
        </itemDelta>
        <itemDelta>
            <t:modificationType>replace</t:modificationType>
            <t:path>c:familyName</t:path>
            <t:value>NewFamilyName</t:value>
        </itemDelta>
        </objectModification>';
    $this->assertTrue($this->api->modifyUser($oid, $xml));

    // Verify modified user.
    $user = $this->api->getUser($oid);
    $this->assertFalse(isset($user['user']['givenName']));
    $this->assertEquals('NewFamilyName', $user['user']['familyName']);
    $this->assertEquals('Test User', $user['user']['fullName']);
    $this->assertEquals('Test User', $user['user']['name']);
  }

  public function testModifyUserReplaceGivenNameAlreadyExists() {
    // Create new minimal user.
    $oid = $this->createMinimalTestUser();

    // Replace givenName with new value.
    $xml =
      '<?xml version="1.0" encoding="UTF-8"?>
        <objectModification xmlns="http://midpoint.evolveum.com/xml/ns/public/common/api-types-3" xmlns:c="http://midpoint.evolveum.com/xml/ns/public/common/common-3" xmlns:t="http://prism.evolveum.com/xml/ns/public/types-3">
        <itemDelta>
            <t:modificationType>replace</t:modificationType>
            <t:path>c:givenName</t:path>
            <t:value>Test</t:value>
        </itemDelta>
        </objectModification>';
    $this->assertTrue($this->api->modifyUser($oid, $xml));

    // Verify modified user.
    $user = $this->api->getUser($oid);
    $this->assertEquals('Test', $user['user']['givenName']);
    $this->assertEquals('User', $user['user']['familyName']);
    $this->assertEquals('Test User', $user['user']['fullName']);
    $this->assertEquals('Test User', $user['user']['name']);
  }

  public function testModifyUserReplaceGivenNameEmptyValue() {
    // Create new minimal user.
    $oid = $this->createMinimalTestUser();

    // Replace givenName with new value.
    $xml =
      '<?xml version="1.0" encoding="UTF-8"?>
        <objectModification xmlns="http://midpoint.evolveum.com/xml/ns/public/common/api-types-3" xmlns:c="http://midpoint.evolveum.com/xml/ns/public/common/common-3" xmlns:t="http://prism.evolveum.com/xml/ns/public/types-3">
        <itemDelta>
            <t:modificationType>replace</t:modificationType>
            <t:path>c:givenName</t:path>
            <t:value></t:value>
        </itemDelta>
        </objectModification>';
    $this->assertTrue($this->api->modifyUser($oid, $xml));

    // Verify modified user.
    $user = $this->api->getUser($oid);
    $this->assertEquals('', $user['user']['givenName']);
    $this->assertEquals('User', $user['user']['familyName']);
    $this->assertEquals('Test User', $user['user']['fullName']);
    $this->assertEquals('Test User', $user['user']['name']);
  }

  public function testModifyUserReplaceGivenNameNoValue() {
    // Create new minimal user.
    $oid = $this->createMinimalTestUser();

    // Replace givenName with new value.
    $xml =
      '<?xml version="1.0" encoding="UTF-8"?>
        <objectModification xmlns="http://midpoint.evolveum.com/xml/ns/public/common/api-types-3" xmlns:c="http://midpoint.evolveum.com/xml/ns/public/common/common-3" xmlns:t="http://prism.evolveum.com/xml/ns/public/types-3">
        <itemDelta>
            <t:modificationType>replace</t:modificationType>
            <t:path>c:givenName</t:path>
        </itemDelta>
        </objectModification>';
    $this->assertTrue($this->api->modifyUser($oid, $xml));

    // Verify modified user.
    $user = $this->api->getUser($oid);
    $this->assertFalse(isset($user['user']['givenName']));
    $this->assertEquals('User', $user['user']['familyName']);
    $this->assertEquals('Test User', $user['user']['fullName']);
    $this->assertEquals('Test User', $user['user']['name']);
  }

  public function testModifyUserDeleteGivenName() {
    // Create new minimal user.
    $oid = $this->createMinimalTestUser();

    // Delete givenName with existing value 'Test'.
    $xml =
      '<?xml version="1.0" encoding="UTF-8"?>
        <objectModification xmlns="http://midpoint.evolveum.com/xml/ns/public/common/api-types-3" xmlns:c="http://midpoint.evolveum.com/xml/ns/public/common/common-3" xmlns:t="http://prism.evolveum.com/xml/ns/public/types-3">
        <itemDelta>
            <t:modificationType>delete</t:modificationType>
            <t:path>c:givenName</t:path>
            <t:value>Test</t:value>
        </itemDelta>
        </objectModification>';
    $this->assertTrue($this->api->modifyUser($oid, $xml));

    // Verify modified user.
    $user = $this->api->getUser($oid);
    $this->assertFalse(isset($user['user']['givenName']));
    $this->assertEquals('User', $user['user']['familyName']);
    $this->assertEquals('Test User', $user['user']['fullName']);
    $this->assertEquals('Test User', $user['user']['name']);
  }

  public function testModifyUserDeleteGivenNameDoesNotExist() {
    // Create new minimal user.
    $oid = $this->createMinimalTestUser();

    // Delete givenName with value that does not exist.
    $xml =
      '<?xml version="1.0" encoding="UTF-8"?>
        <objectModification xmlns="http://midpoint.evolveum.com/xml/ns/public/common/api-types-3" xmlns:c="http://midpoint.evolveum.com/xml/ns/public/common/common-3" xmlns:t="http://prism.evolveum.com/xml/ns/public/types-3">
        <itemDelta>
            <t:modificationType>delete</t:modificationType>
            <t:path>c:givenName</t:path>
            <t:value>does-not-exist</t:value>
        </itemDelta>
        </objectModification>';
    $this->assertTrue($this->api->modifyUser($oid, $xml));

    // Verify modified user.
    $user = $this->api->getUser($oid);
    $this->assertEquals('Test', $user['user']['givenName']);
    $this->assertEquals('User', $user['user']['familyName']);
    $this->assertEquals('Test User', $user['user']['fullName']);
    $this->assertEquals('Test User', $user['user']['name']);
  }

  public function testModifyUserDeleteGivenNameNoValue() {
    // Create new minimal user.
    $oid = $this->createMinimalTestUser();

    // Delete givenName with no value.
    $xml =
      '<?xml version="1.0" encoding="UTF-8"?>
        <objectModification xmlns="http://midpoint.evolveum.com/xml/ns/public/common/api-types-3" xmlns:c="http://midpoint.evolveum.com/xml/ns/public/common/common-3" xmlns:t="http://prism.evolveum.com/xml/ns/public/types-3">
        <itemDelta>
            <t:modificationType>delete</t:modificationType>
            <t:path>c:givenName</t:path>
        </itemDelta>
        </objectModification>';
    $this->assertTrue($this->api->modifyUser($oid, $xml));

    // Verify modified user.
    $user = $this->api->getUser($oid);
    $this->assertEquals('Test', $user['user']['givenName']);
    $this->assertEquals('User', $user['user']['familyName']);
    $this->assertEquals('Test User', $user['user']['fullName']);
    $this->assertEquals('Test User', $user['user']['name']);
  }

  public function testModifyUserDeleteGivenNameEmptyValue() {
    // Create new minimal user.
    $oid = $this->createMinimalTestUser();

    // Delete givenName with no value.
    $xml =
      '<?xml version="1.0" encoding="UTF-8"?>
        <objectModification xmlns="http://midpoint.evolveum.com/xml/ns/public/common/api-types-3" xmlns:c="http://midpoint.evolveum.com/xml/ns/public/common/common-3" xmlns:t="http://prism.evolveum.com/xml/ns/public/types-3">
        <itemDelta>
            <t:modificationType>delete</t:modificationType>
            <t:path>c:givenName</t:path>
            <t:value></t:value>
        </itemDelta>
        </objectModification>';
    $this->assertTrue($this->api->modifyUser($oid, $xml));

    // Verify modified user.
    $user = $this->api->getUser($oid);
    $this->assertEquals('Test', $user['user']['givenName']);
    $this->assertEquals('User', $user['user']['familyName']);
    $this->assertEquals('Test User', $user['user']['fullName']);
    $this->assertEquals('Test User', $user['user']['name']);
  }

  public function testModifyUserAddAdditionalName() {
    // Create new minimal user.
    $oid = $this->createMinimalTestUser();

    // Delete givenName with existing value 'Test'.
    $xml =
      '<?xml version="1.0" encoding="UTF-8"?>
        <objectModification xmlns="http://midpoint.evolveum.com/xml/ns/public/common/api-types-3" xmlns:c="http://midpoint.evolveum.com/xml/ns/public/common/common-3" xmlns:t="http://prism.evolveum.com/xml/ns/public/types-3">
        <itemDelta>
            <t:modificationType>add</t:modificationType>
            <t:path>c:additionalName</t:path>
            <t:value>Middle</t:value>
        </itemDelta>
        </objectModification>';
    $this->assertTrue($this->api->modifyUser($oid, $xml));

    // Verify modified user.
    $user = $this->api->getUser($oid);
    $this->assertEquals('Test', $user['user']['givenName']);
    $this->assertEquals('User', $user['user']['familyName']);
    $this->assertEquals('Test User', $user['user']['fullName']);
    $this->assertEquals('Test User', $user['user']['name']);
    $this->assertEquals('Middle', $user['user']['additionalName']);
  }

  public function testModifyUserAddInvalidProperty() {

    $this->setExpectedException(RuntimeException::class, '500');

    // Create new minimal user.
    $oid = $this->createMinimalTestUser();

    // Delete givenName with existing value 'Test'.
    $xml =
      '<?xml version="1.0" encoding="UTF-8"?>
        <objectModification xmlns="http://midpoint.evolveum.com/xml/ns/public/common/api-types-3" xmlns:c="http://midpoint.evolveum.com/xml/ns/public/common/common-3" xmlns:t="http://prism.evolveum.com/xml/ns/public/types-3">
        <itemDelta>
            <t:modificationType>add</t:modificationType>
            <t:path>c:invalidProperty</t:path>
            <t:value>Middle</t:value>
        </itemDelta>
        </objectModification>';
    $this->assertTrue($this->api->modifyUser($oid, $xml));
  }
}