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

  /** @var string XML representation of new minimal user */
  public $newUserXml =
    '<?xml version="1.0" encoding="UTF-8"?>
       <user xmlns="http://midpoint.evolveum.com/xml/ns/public/common/common-3">
        <name>Test User</name>
        <fullName>Test User</fullName>
        <givenName>Test</givenName>
        <familyName>User</familyName>
       </user>';

  public function setUp() {
    parent::setUp();
    $this->api = new MidPointRestApiClient($this->coProvisioningTargetData);
  }

  public function tearDown() {
    // TODO delete test user
    parent::tearDown();
  }

  /**
   * Test creating a new user.
   *
   * @return string OID of newly created user
   */
  public function testCreateUser() {
    $oid = $this->api->createUser($this->newUserXml);
    $this->assertTrue(is_string($oid));
    return $oid;
  }

  /**
   * Test creating a user that already exists.
   *
   * @depends testCreateUser
   */
  public function testCreateUserAlreadyExists() {
    $this->setExpectedException(RuntimeException::class, '409');
    $this->api->createUser($this->newUserXml);
  }

  /**
   * Test getting a user.
   *
   * @depends testCreateUser
   * @param $oid OID of user
   * @return string $oid OID of user
   */
  public function testGetUser($oid) {
    $user = $this->api->getUser($oid);
    $this->assertEquals('Test', $user['user']['givenName']);
    $this->assertEquals('User', $user['user']['familyName']);
    $this->assertEquals('Test User', $user['user']['fullName']);
    $this->assertEquals('Test User', $user['user']['name']);
    return $oid;
  }

  /**
   * Test getting a user that does not exist.
   */
  public function testGetUserDoesNotExist() {
    $user = $this->api->getUser('does-not-exist');
    $this->assertEmpty($user);
  }

  /**
   * Test deleting a user.
   *
   * @depends testGetUser
   * @param $oid OID of user
   */
  public function testDeleteUser($oid) {
    $this->api->deleteUser($oid);

    $user = $this->api->getUser($oid);
    $this->assertEmpty($user);
  }

  /**
   * Test deleting a user that does not exist.
   */
  public function testDeleteUserDoesNotExist() {
    $this->setExpectedException(RuntimeException::class, '404');
    $this->api->deleteUser('does-not-exist');
  }
}