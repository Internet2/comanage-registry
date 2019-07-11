<?php

App::uses('MidPointRestApiClient', 'MidPointProvisioner.Lib');

class MidPointRestApiClientTest extends CakeTestCase {

  public $coProvisioningTargetData = array(
    'CoMidPointProvisionerTarget' => array(
      'serverurl' => 'https://172.22.0.6:443/midpoint',
      'username' => 'Administrator',
      'password' => '5ecr3t',
      'ssl_allow_self_signed' => 1,
      'ssl_verify_host' => 0,
      'ssl_verify_peer' => 0,
      'ssl_verify_peer_name' => 0
    )
  );

  /** @var MidPointRestApiClient $api */
  public $api;

  public $minimalUserOid;

  public $toDelete = array();

  /** @var string XML representation of new minimal user */
  public $minimalUserXml =
    '<?xml version="1.0" encoding="UTF-8"?>
       <user xmlns="http://midpoint.evolveum.com/xml/ns/public/common/common-3">
        <name>Test User</name>
        <fullName>Test User</fullName>
        <givenName>Test</givenName>
        <familyName>User</familyName>
       </user>';

  public $minimalUser = array(
    'user' => array(
      'name' => 'Test User',
      'fullName' => 'Test User',
      'givenName' => 'Test',
      'familyName' => 'User'
    )
  );

  public function setUp() {
    parent::setUp();
    CakeLog::debug('MidPointRestApiClientTest setUp');
    $this->api = new MidPointRestApiClient($this->coProvisioningTargetData);
  }

  public function tearDown() {
    // Delete test users.
    foreach ($this->toDelete as $oid) {
      CakeLog::debug("TearDown Deleting $oid");
      $this->deleteUser($oid);
    }
    parent::tearDown();
    CakeLog::debug('MidPointRestApiClientTest tearDown');
  }

  public function testBuildUser() {
    $actualXml = $this->api->buildUserXml($this->minimalUser);
    $this->assertXmlStringEqualsXmlString($this->minimalUserXml, $actualXml);
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

  public function createMinimalUser() {
    $oid = $this->createUser($this->minimalUserXml);
    $user = $this->api->getUser($oid);
    $this->assertEquals('Test', $user['user']['givenName']);
    $this->assertEquals('User', $user['user']['familyName']);
    $this->assertEquals('Test User', $user['user']['fullName']);
    $this->assertEquals('Test User', $user['user']['name']);
    //return ($oid, $user);
    return $oid;
  }

  public function createUser($xml, $toDelete = true) {
    $oid = $this->api->createUser($xml);
    $this->assertTrue(is_string($oid));
    if ($toDelete) {
      array_push($this->toDelete, $oid);
    }
    return $oid;
  }

  public function deleteUser($oid) {
    $this->api->deleteUser($oid);
    $this->assertEmpty($this->api->getUser($oid));
  }

  public function modifyUserNOTUSED($op, $name, $value) {
    // Template XML modification.
    $xml =
      '<?xml version="1.0" encoding="UTF-8"?>
        <objectModification xmlns="http://midpoint.evolveum.com/xml/ns/public/common/api-types-3" xmlns:c="http://midpoint.evolveum.com/xml/ns/public/common/common-3" xmlns:t="http://prism.evolveum.com/xml/ns/public/types-3">
        <itemDelta>
            <t:modificationType>$op</t:modificationType>
            <t:path>c:$name</t:path>
            <t:value>$value</t:value>
        </itemDelta>
        </objectModification>';

    // Adjust XML template.
    CakeLog::debug("modifyAttribute 0 $xml");
    $xml = str_replace('$op', $op, $xml);
    $xml = str_replace('$name', $name, $xml);
    //if (isset($value)) {
      $xml = str_replace('$value', $value, $xml);
    //}
    CakeLog::debug("modifyAttribute 1 $xml");

    // Create new minimal user.
    $oid = $this->createUser($this->minimalUserXml);

    // Verify newly created user.
    $user = $this->api->getUser($oid);
    foreach ($this->minimalUser as $expectedName => $expectedValue) {
      $this->assertEquals($expectedValue, $user['user'][$expectedName]);
    }

    // Modify user.
    $this->assertTrue($this->api->modifyUser($oid, $xml));

    // Verify modified user.
    $user = $this->api->getUser($oid);
    CakeLog::debug('modified user '.var_export($user, true));
    if ($op === 'delete') {
      $this->assertFalse(isset($user['user'][$name]));
    } else {
      $this->assertEquals($value, $user['user'][$name]);
    }

    foreach ($this->minimalUser as $expectedName => $expectedValue) {
      // Ignore modified attribute.
      if ($expectedName === $name) {
        continue;
      }
      $this->assertEquals($expectedValue, $user['user'][$expectedName]);
    }
  }

  /**
   * Test creating a user.
   */
  public function testCreateMinimalUser() {
    $this->createUser($this->minimalUserXml);
  }

  /**
   * Test creating a user that already exists.
   */
  public function testCreateUserAlreadyExists() {
    $this->setExpectedException(RuntimeException::class, '409');
    $this->createUser($this->minimalUserXml);
    $this->createUser($this->minimalUserXml);
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
    $oid = $this->createUser($this->minimalUserXml);
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
    $oid = $this->createMinimalUser();

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
    $oid = $this->createMinimalUser();

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
    $oid = $this->createMinimalUser();

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
    $oid = $this->createMinimalUser();

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
    $oid = $this->createMinimalUser();

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
    $oid = $this->createMinimalUser();

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
    $oid = $this->createMinimalUser();

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
    $oid = $this->createMinimalUser();

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
    $oid = $this->createMinimalUser();

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
    $oid = $this->createMinimalUser();

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
    $oid = $this->createMinimalUser();

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
    $oid = $this->createMinimalUser();

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
    $oid = $this->createMinimalUser();

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