<?php

App::uses('CoMidPointProvisionerTarget', 'Model');
App::uses('MidPointRestApiClient', 'MidPointProvisioner.Lib');

class CoMidPointProvisionerTargetTest extends CakeTestCase {

// 'plugin.MidPointProvisioner.CoMidPointProvisionerTarget',

  public $fixtures = array(
    'plugin.MidPointProvisioner.CoMidPointProvisionerTarget',
    'CoExtendedType',
    //'CoIdentifierValidator',
    'CoPerson',
    'Identifier'
  );

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

  public function setUp() {
    parent::setUp();
    // $this->CoMidPointProvisionerTarget = ClassRegistry::init('CoMidPointProvisionerTarget');
    $this->api = new MidPointRestApiClient($this->coProvisioningTargetData);
  }

  /**
   * Test creating a new user.
   *
   * @return string OID of newly created user
   */
  public function testCreateUser() {
    $xml =
      '<?xml version="1.0" encoding="UTF-8"?>
       <user xmlns="http://midpoint.evolveum.com/xml/ns/public/common/common-3">
        <name>Test User</name>
        <fullName>Test User</fullName>
        <givenName>Test</givenName>
        <familyName>User</familyName>
       </user>';

    $oid = $this->api->createUser($xml);
    $this->assertTrue(is_string($oid));
    return $oid;
  }

  /**
   * Test reading a user.
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
}